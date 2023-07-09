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
if (!array_key_exists('spaceid', $_POST)) {
    ajaxError('Invalid arguments');
    return;
}
if (!array_key_exists('priceid', $_POST)) {
    ajaxError('Invalid arguments');
    return;
}

$spaceid = $_POST['spaceid'];
$priceid = $_POST['priceid'];

if ($priceid > 0) {

// get the details of the item requested
    $priceQ = <<<EOS
SELECT vsp.code, vsp.description, units, price, requestable, vs.shortname, vs.name
FROM vendorSpacePrices vsp
JOIN vendorSpaces vs ON (vsp.spaceId = vs.id)
WHERE vsp.spaceId = ? and vsp.id = ?;
EOS;
    $priceR = dbSafeQuery($priceQ, 'ii', array($spaceid, $priceid));
} else {
    $priceQ = <<<EOS
SELECT shortname, name, description
FROM vendorSpaces
WHERE id = ?;
EOS;
    $priceR = dbSafeQuery($priceQ, 'i', array($spaceid));
}
$price = fetch_safe_assoc($priceR);
if ($price === null || $price === false)  {
    ajaxError('Invalid arguments');
    return;
}
$shortname = $price['shortname'];
$spacename = $price['name'];

$response['div'] = $shortname . "_div";
// see if there already is an entry for this space for this vendor
$vendorQ = "SELECT id, item_requested, item_approved, item_purchased, price, paid, transid, membershipCredits FROM vw_VendorSpace WHERE conid = ? and spaceId = ? and vendorId = ?";
$vendorR = dbSafeQuery($vendorQ, 'iii', array($conid, $spaceid, $vendor));
$vendorspace = fetch_safe_assoc($vendorR);
if ($vendorspace === null || $vendorspace === false) {
    $vendorspace = array();
    $vendorspace['id'] = -1;
    $vendorspace['item_requested'] = 0;
}

// now add/update the vendor_space record for the number requested
if ($vendorspace['id'] < 0) {
    if ($priceid > 0) {
        // insert a new record because its a new request
        $insertQ = "INSERT INTO vendor_space(conid, vendorId, spaceId, item_requested) VALUES(?, ?, ?, ?);";
        $rowid = dbSafeInsert($insertQ, "iiii", array($conid, $vendor, $spaceid, $priceid));
    }
} else if ($priceid > 0) {
    // update for new/changed item
    $updateQ = "UPDATE vendor_space SET item_requested = ? WHERE id = ?;";
    $numrows = dbSafeCmd($updateQ, "ii", array($priceid, $vendorspace['id']));
} else {
    // clear cancelled item
    $updateQ = 'UPDATE vendor_space SET item_requested = NULL WHERE id = ?;';
    $numrows = dbSafeCmd($updateQ, 'i', array($vendorspace['id']));
}

$vendorQ = "SELECT email FROM vendors where id=?;";
$vendorR = dbSafeQuery($vendorQ, 'i', array($vendor));
$v_email = fetch_safe_assoc($vendorR)['email'];

$response['email']=$v_email;
load_email_procs();
$return_arr = send_email($conf['regadminemail'], $v_email, $vendor_conf[$shortname], $spacename . " Request", request($spacename, $price, $vendor, $vendor_conf[$shortname]), null);

if (array_key_exists('error_code', $return_arr)) {
    $error_code = $return_arr['error_code'];
} else {
    $error_code = null;
}
if (array_key_exists('email_error', $return_arr)) {
    $response['error'] = 'Unable to send receipt email, error: ' . $return_arr['email_error'] . ', Code: $error-code';
} else {
    $response['success'] = "Request sent";
}

ajaxSuccess($response);
?>
