<?php
global $db_ini;

require_once '../lib/base.php';
require_once('../../../lib/email__load_methods.php');
$check_auth = google_init('ajax');
$perm = 'vendor';

$response = array('post' => $_POST, 'get' => $_GET, 'perm' => $perm);

if ($check_auth == false || !checkAuth($check_auth['sub'], $perm)) {
    $response['error'] = 'Authentication Failed';
    ajaxSuccess($response);
    exit();
}

$con = get_con();
$conf = get_conf('con');
$vendor_conf = get_conf('vendor');
$conid = $con['id'];

if (!(array_key_exists('vendorId', $_POST) && array_key_exists('spaceId',$_POST) && array_key_exists('id',$_POST) && array_key_exists('operation',$_POST))) {
      ajaxError('No Data');
      return;
}

$vendorId = $_POST['vendorId'];
$spaceId = $_POST['spaceId'];
$vsID = $_POST['id'];
$operation = $_POST['operation'];

if ($operation == 'approve') {
    $updateQ = <<<EOS
UPDATE vendor_space
SET item_approved = ?
WHERE id = ?
EOS;
    $approved = $_POST['sr_approved'];
    if ($approved == 0)
        $approved = null;
    $numRows = dbSafeCmd($updateQ, 'ii', array($approved, $vsID));
    if ($numRows == 1) {
        $response['success'] = 'Space Approved';
        // now send that vendor an email telling them their space is approved
        $vendorQ = <<<EOS
SELECT v.name, v.email, vs.shortname, vs.name AS spacename, vs.approved_description
FROM vendors v
JOIN vw_VendorSpace vs ON (v.id = vs.vendorId AND vs.spaceId = ?)
WHERE v.id = ?
EOS;
        $vendorL = fetch_safe_assoc(dbSafeQuery($vendorQ, 'ii', array($spaceId, $vendorId)));
        $vendorname = $vendorL['name'];
        $spacename = $vendorL['spacename'];
        $desc = $vendorL['approved_description'];
        $label = $conf['label'];
        $site = $vendor_conf['vendorsite'];
        $body = <<<EOS
Dear $vendorname

An approval has been entered against your space request in $label $spacename.

You have been approved for $desc.  Please sign into the vendor portal at $site to purchase your space and memberships.

Thank you.
$label
EOS;

        load_email_procs();
        $return_arr = send_email($conf['regadminemail'], $vendorL['email'], $vendor_conf[$vendorL['shortname']], $spacename . ' Approval', $body, null);

        if (array_key_exists('error_code', $return_arr)) {
            $error_code = $return_arr['error_code'];
        } else {
            $error_code = null;
        }
        if (array_key_exists('email_error', $return_arr)) {
            $response['error'] = 'Unable to send receipt email, error: ' . $return_arr['email_error'] . ', Code: $error-code';
        } else {
            $response['success'] .= ', Request sent';
        }
    }

    ajaxSuccess($response);
    return;
}
