<?php
require_once('../lib/base.php');
require_once('../../lib/db_functions.php');
require_once('../../lib/pdfPrintArtShowSheets.php');
require_once('../../lib/email__load_methods.php');

// use common global Ajax return functions
global $returnAjaxErrors, $return500errors;
$returnAjaxErrors = true;
$return500errors = true;

$response = array('post' => $_POST, 'get' => $_REQUEST);
if(!array_key_exists('type', $_REQUEST) || !array_key_exists('region', $_REQUEST) || !isSessionVar('eyID')) {
    echo "Invalid Session\n";
    exit;
}

$eyID = getSessionVar('eyID');
$conid = getConfValue('con', 'id');
if (array_key_exists('region', $_REQUEST))
    $region = $_REQUEST['region'];
else {
    $response['error'] = 'Invalid calling sequence';
    echo "<h1>Invalid calling sequence</h1>\n";
    return $response;
}

if (array_key_exists('type', $_REQUEST)) {
    $type = $_REQUEST['type'];
} else {
    $type = 'unknown';
}

if (array_key_exists('emailTo', $_REQUEST)) {
    $sendTo = $_REQUEST['emailTo'];
    $output = true;
    load_email_procs();
} else {
    $output = false;
}


if ($type == 'control' &&  array_key_exists('conid', $_REQUEST)) {
    $conyear = $_REQUEST['conid'];
    if ($conyear == null)
        $conyear = $conid;
    if ($conid != $conyear && $conyear > 0) {
        // translate region to the appropriate region for that year
        $cyQ = <<<EOS
SELECT erycy.id, exycy.id
FROM exhibitsRegionYears ery 
JOIN exhibitsRegionYears erycy ON ery.exhibitsRegion = erycy.exhibitsRegion
join exhibitorYears exy ON exy.id = ?
join exhibitorYears exycy ON exy.exhibitorId = exycy.exhibitorId and exycy.conid = erycy.conid
where ery.id = ? and erycy.conid = ?;
EOS;
        $cyR = dbSafeQuery($cyQ, 'iii', array($eyID, $region, $conyear));
        if ($cyR !== false && $cyR->num_rows == 1) {
            [$region, $eyID] = $cyR->fetch_row();
            $cyR->free();
        }
    }
}


switch($_REQUEST['type']) {
    case 'bidsheets':
        $response = pdfPrintBidSheets($eyID, $region, $response,true, true, $output);
        break;
    case 'printshop':
        $response = pdfPrintShopPriceSheets($eyID, $region, $response,true, true, $output);
        break;
    case 'control':
        $response = pdfArtistControlSheet($eyID, $region, $response, false, true, true, $output);
        break;
    default:
        echo "<h1>Error Invalid sheet type, please seek assistance</h1\n";
}

if ($output) {
    // write the file to a temp file, and prepare to email it

    if (array_key_exists('success', $response) && $response['success'] == true) {
        if (!array_key_exists('pdf', $response) || $response['pdf'] == '') {
            ajaxSuccess($response);
            exit();
        }
        // get the output file name in the temp directory
        $tempfile = tempnam(sys_get_temp_dir(), 'exhibitorsBidSheets');
        if (!$tempfile) {
            $response['error'] = 'Unable to get unique file to save the output';
            $response['error_message'] = error_get_last();
            ajaxSuccess($response);
            exit();
        }
        // write the output to temp file
        $temp = fopen($tempfile, 'w');
        if (!$temp) {
            $response['error'] = 'Unable to get open file';
            $response['error_message'] = error_get_last();
            ajaxSuccess($response);
            exit();
        }
        fwrite($temp, $response['pdf']);
        fclose($temp);

        // ok, we have the data in the file, now email it
        $att = [[$tempfile, $response['filename'], 'application/pdf']];
        $subject = 'Your Artist Controll Sheet for ' . $response['artistName'];
        $from = $response['ownerEmail'];
        $ownerName = $response['ownerName'];
        $artistName = $response['artistName'];
        $to = $sendTo;
        $cc = $response['ownerEmail'];
        $text = <<<EOS
Dear $artistName,

Here is your requested Artist Control Sheet.

Respectfully submitted,
$ownerName

EOS;

        $html = null;

        $return_arr = send_email($from, $to, $cc, $subject, $text, $html, $att);
        $status = $return_arr['status'];
        if ($status == 'success') {
            $response['message'] = $response['message'] . "<br/>Control sheet sent to $to";
        } else {
            $error = "Error sending controll sheet to $to: " . $return_arr['email_error'];
            if (array_key_exists('error_code', $return_arr)) {
                $error .= '<br/>Error Code: ' . $return_arr['error_code'];
            }
            $response['error'] = $error;
        }
        unlink($tempfile);
        ajaxSuccess($response);
        exit();
    }
}
