<?php
require_once('../lib/base.php');
require_once('../../lib/email__load_methods.php');
require_once '../lib/email.php';

// use common global Ajax return functions
global $returnAjaxErrors, $return500errors;
$returnAjaxErrors = true;
$return500errors = true;

$response = array('post' => $_POST, 'get' => $_GET);

$vendor = $_SESSION['id'];

global $con;
$con = get_con();
$conid=$con['id'];
$conf = get_conf('con');
$vendor_conf = get_conf('vendor');

$response['conid'] = $conid;

// validate that the items passed are the VendorSpace id and the VendorSpacePrice id
if (!array_key_exists('regionYearId', $_POST)) {
    ajaxError('Invalid arguments');
    return;
}
if (!array_key_exists('type', $_POST)) {
    ajaxError('Invalid arguments');
    return;
}
if (!array_key_exists('name', $_POST)) {
    ajaxError('Invalid arguments');
    return;
}

$regionYearId = $_POST['regionYearId'];
$portalType = $_POST['type'];
$portalName = $_POST['name'];

// get the information for this space
$ryQ = <<<EOS
SELECT ery.ownerName, ery.ownerEmail, ert.requestApprovalRequired, er.name
FROM exhibitsRegionYears ery
JOIN exhibitsRegions er ON (ery.exhibitsRegion = er.id)
JOIN exhibitsRegionTypes ert ON (er.regionType = ert.regionType)
WHERE ery.id = ?;
EOS;
$ryR = dbSafeQuery($ryQ, 'i', array($regionYearId));
if ($ryR === false || $ryR->num_rows != 1) {
    ajaxError('Invalid space identifier passed');
    return;
}
$ryL = $ryR->fetch_assoc();
$owner = $ryL['ownerName'];
$email = $ryL['ownerEmail'];
$approvalReq = $ryL['requestApprovalRequired'];
$regionName = $ryL['name'];
$ryR->free();

// returns success and the block
$date = date_create('now');
$date = date_format($date, 'F j, Y') . ' at ' . date_format($date, 'g:i A');

$upQ = <<<EOS
UPDATE exhibitorApprovals
SET approval = ?
WHERE exhibitorId = ? AND exhibitsRegionYearId = ?;
EOS;
if ($approvalReq == 'None') {
    // we should never be here, this space no longer requires approval, update the approval record
    $num_rows = dbSafeCmd($upQ, 'sii', array('approved', $vendor, $regionYearId));
    if ($num_rows == false) {
        $response['error'] = "Unable to update space approval for approved";
        ajaxSuccess($response);
        exit();
    }
    $block = <<<EOS
<div class='col-sm-auto p-0'><?php
    <p>Your request for permission was approved automatically.</p>
    <p>This space needs to display the buy space block (perhaps put that in the lib directory)</p>
</div>
EOS;
} else {
    // store the request in the database
    $num_rows = dbSafeCmd($upQ, 'sii', array('requested', $vendor, $regionYearId));
    if ($num_rows == false) {
        $response['error'] = 'Unable to update space approval for requested';
        ajaxSuccess($response);
        exit();
    }
    // now send the email
    load_email_procs();
    $emails = approval($vendor, $regionName, $owner, $email, $portalName);
    $contactName = $emails[0];
    $contactEmail = $emails[1];
    $return_arr = send_email($conf['regadminemail'], $email, $contactEmail, "$portalName access to $regionName",$emails[2], $emails[3]);

    if (array_key_exists('error_code', $return_arr)) {
        $error_code = $return_arr['error_code'];
    } else {
        $error_code = null;
    }
    if (array_key_exists('email_error', $return_arr)) {
        $response['error'] = 'Unable to send receipt email, error: ' . $return_arr['email_error'] . ', Code: $error-code';
    } else {
        $response['success'] = 'Request sent';
    }

    $block = <<<EOS
<div class='col-sm-auto p-0'><?php
    <p>You requested permission for this space on $date and $owner has not yet processed that request.</p>
    <p>Please email $owner at <a href='mailto:$email'>$email</a> if you need to follow-up on this request.</p>
</div>
EOS;
}

$response['block'] = $block;
$response['message'] = "Your request has been sent, a copy of the email was sent to your contact email address.";
ajaxSuccess($response);
