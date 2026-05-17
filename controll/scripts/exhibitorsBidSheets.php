<?php
require_once('../lib/base.php');
require_once('../../lib/pdfPrintArtShowSheets.php');
require_once '../lib/sessionAuth.php';
require_once('../../lib/email__load_methods.php');

// use common global Ajax return functions
global $returnAjaxErrors, $return500errors;
$returnAjaxErrors = true;
$return500errors = true;

$perm = 'exhibitor';
$response = array ('post' => $_POST, 'get' => $_REQUEST, 'perm' => $perm);
$authToken = new authToken('script');
$response['tokenStatus'] = $authToken->checkToken();
if (!$authToken->isLoggedIn() || !$authToken->checkAuth($perm)) {
    $response['error'] = 'Authentication Failed';
    ajaxSuccess($response);
    exit();
}

if(!array_key_exists('type', $_REQUEST) || !array_key_exists('region', $_REQUEST) || !array_key_exists('eyid', $_REQUEST)) {
    echo "Invalid Arguments\n";
    exit;
}

$eyID = $_REQUEST['eyid'];
$region = $_REQUEST['region'];
$email  = false;
if (array_key_exists('email', $_REQUEST)) {
    $email = $_REQUEST['email'] == 'true';
}

if (str_contains($eyID, ','))
    $eyIDlist = explode(',', $eyID);
else
    $eyIDlist = array($eyID);

if (array_key_exists('emailTo', $_REQUEST)) {
    $sendTo = $_REQUEST['emailTo'];
    $output = true;
    load_email_procs();
} else {
    $output = false;
}

foreach ($eyIDlist as $id) {
    switch ($_REQUEST['type']) {
        case 'bidsheets':
            $response = pdfPrintBidSheets($id, $region, $response, $id == $eyIDlist[0], $id == $eyIDlist[count($eyIDlist) - 1], $output);
            break;
        case 'printshop':
            $response = pdfPrintShopPriceSheets($id, $region, $response, $id == $eyIDlist[0], $id == $eyIDlist[count($eyIDlist) - 1], $output);
            break;
        case 'control':
            $response = pdfArtistControlSheet($id, $region, $response, $email, $id == $eyIDlist[0], $id == $eyIDlist[count($eyIDlist) - 1], $output);
            break;
        default:
    }
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
        $subject = "Updated Artist Controll Sheet for " . $response['artistName'];
        $from = $response['ownerEmail'];
        $ownerName = $response['ownerName'];
        $artistName = $response['artistName'];
        $to = $sendTo;
        $cc = $response['ownerEmail'];
        $text = <<<EOS
Dear $artistName,

Here is your latest Artist Control Sheet.

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
                $error .= "<br/>Error Code: " . $return_arr['error_code'];
            }
            $response['error'] = $error;
        }
        unlink($tempfile);
        ajaxSuccess($response);
        exit();
    }
}
