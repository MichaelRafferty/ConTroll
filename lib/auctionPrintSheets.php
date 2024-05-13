<?php
require_once("../lib/base.php");




/*
 * prints bidsheets for exhibitorYearId in region
 */
function bidsheets($eyID, $region, $response_in, $config) {
global $returnAjaxErrors, $return500errors;
$response = $response_in;
$con = $config['con'];
$render_url = $config['render_url'];
$debug_conf = $config['debug'];

$artQ = <<<EOS
SELECT e.exhibitorName artist_name, ery.exhibitorNumber artist_id,
    i.title work_name, i.item_key work_id, i.material,
    i.min_price, i.sale_price, i.type
FROM exhibitorRegionYears ery
    JOIN exhibitorYears ey ON ey.id=ery.exhibitorYearId
    JOIN exhibitors e ON e.id=ey.exhibitorId
    JOIN artItems i ON i.exhibitorRegionYearId = ery.id
WHERE ery.exhibitorYearId=? and ery.exhibitsRegionYearId=? AND i.type in ('art', 'nfs');
EOS;

$artR = dbSafeQuery($artQ, 'ii', array($eyID, $region));
    
if($artR->num_rows == 0) {
    $response['num_rows'] = $artR->num_rows;
    $response['status'] = "No Results";
    if($returnAjaxErrors) { 
        ajaxSuccess($response);
        exit(); 
    } else { return $response; }
}
// Marshal art show pieces to XML.
$bidsxml = new XMLWriter;
$tmphandle = tmpfile();
// Frak PHP with a rusty chainsaw. Can't [] the output of a function before PHP 5.4.
$tmpmeta = stream_get_meta_data($tmphandle);
$tmpuri = $tmpmeta['uri'];
if (!$bidsxml->openURI($tmpuri)) {
    // I have no idea how this could even happen, though.
    $response['status'] = 'error';
    $response['error'] = $error_msg;
    if($returnAjaxErrors) { 
        ajaxSuccess($response);
        exit(); 
    } else { return $response; }
}

$bidsxml->startDocument('1.0', 'UTF-8');
$bidsxml->startElement('bidsheets');
while($row = $artR->fetch_assoc()) {
    $bidsxml->startElement('bidsheet');
    $bidsxml->writeElement('convention', $con['label']);
    // Artist block
    $bidsxml->startElement('artist');
    $bidsxml->writeAttribute('number', $row['artist_id']);
    $bidsxml->writeElement('name', $row['artist_name']);
    $bidsxml->endElement();
    // Artwork block
    $bidsxml->startElement('artwork');
    $bidsxml->writeAttribute('number', $row['work_id']);
    $bidsxml->writeElement('name', $row['work_name']);
    $bidsxml->writeElement('medium', $row['material']);
    if ($row['type'] == 'nfs') {
        $bidsxml->writeElement('not-for-sale');
    } else {
        if ($row['min_price']) {
            $bidsxml->writeElement('minimum', $row['min_price']);
            if ($row['sale_price']) {
                $bidsxml->writeElement('quicksale', $row['sale_price']);
            }
        }
    }
    $bidsxml->endElement(); // </artwork>
    $bidsxml->endElement(); // </bidsheet>
}
$bidsxml->endElement(); // </bidsheets>
$bidsxml->flush();
// Push to docrenderer and send its PDF output back to client.
$cfile = new CURLFile($tmpuri, 'text/xml', 'data');
// $cfile = '@'.$tmpuri;
$docrender_out = tmpfile();
$post = array('data' => $cfile);
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $render_url . "bidsheets");
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_FILE, $docrender_out);
// curl defaults to outputting the result to stdout.
if (curl_exec($ch)) {
    header('Content-Type: application/pdf');
    $filename = $con['name'] . "-bidsheets-" . time() . ".pdf";
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    $outmeta = stream_get_meta_data($docrender_out);
    readfile($outmeta['uri']);
} else {
    $response['status'] = 'error';
    $response['error'] = $error_msg;
    if($returnAjaxErrors) { 
        ajaxSuccess($response);
        exit(); 
    } else { return $response; }
    // Damnit, Jim, we have an error.
}
curl_close($ch);

$response['status'] = 'Success';
return $response;
}


function copysheets($eyID, $region, $response_in, $config) {
global $returnAjaxErrors, $return500errors;
$response = $response_in;
$con = $config['con'];
$render_url = $config['render_url'];
$debug_conf = $config['debug'];

$artQ = <<<EOS
SELECT e.exhibitorName artist_name, ery.exhibitorNumber artist_id,
    i.title work_name, i.item_key work_id, i.material,
    i.original_qty, i.sale_price, i.type
FROM exhibitorRegionYears ery
    JOIN exhibitorYears ey ON ey.id=ery.exhibitorYearId
    JOIN exhibitors e ON e.id=ey.exhibitorId
    JOIN artItems i ON i.exhibitorRegionYearId = ery.id
WHERE ery.exhibitorYearId=? and ery.exhibitsRegionYearId=? AND i.type in ('print');
EOS;

$artR = dbSafeQuery($artQ, 'ii', array($eyID, $region));

if($artR->num_rows == 0) {
    $response['num_rows'] = $artR->num_rows;
    $response['status'] = "No Results";
    if($returnAjaxErrors) {
        ajaxSuccess($response);
        exit();
    } else { return $response; }
}

// Marshal art show pieces to XML.
$bidsxml = new XMLWriter;
$tmphandle = tmpfile();
// Frak PHP with a rusty chainsaw. Can't [] the output of a function before PHP 5.4.
$tmpmeta = stream_get_meta_data($tmphandle);
$tmpuri = $tmpmeta['uri'];
if (!$bidsxml->openURI($tmpuri)) {
    // I have no idea how this could even happen, though.
    $response['status'] = 'error';
    $response['error'] = $error_msg;
    if($returnAjaxErrors) { 
        ajaxSuccess($response);
        exit(); 
    } else { return $response; }
}

$bidsxml->startDocument('1.0', 'UTF-8');
$bidsxml->startElement('copysheets');
while($row = $artR->fetch_assoc()) {
    $bidsxml->startElement('copysheet');
    $bidsxml->writeElement('convention', $con['label']);
    // Artist block
    $bidsxml->startElement('artist');
    $bidsxml->writeAttribute('number', $row['artist_id']);
    $bidsxml->writeElement('name', $row['artist_name']);
    $bidsxml->endElement();
    // Artwork block
    $bidsxml->startElement('artwork');
    $bidsxml->writeAttribute('number', $row['work_id']);
    $bidsxml->writeElement('name', $row['work_name']);
    $bidsxml->writeElement('medium', $row['material']);
    $bidsxml->writeElement('price', $row['sale_price']);
    $bidsxml->writeElement('copies', $row['original_qty']);
    $bidsxml->endElement(); // </artwork>
    $bidsxml->endElement(); // </copysheet>
}
$bidsxml->endElement(); // </copysheets>
$bidsxml->flush();

// Push to docrenderer and send its PDF output back to client.
$cfile = new CURLFile($tmpuri, 'text/xml', 'data');
// $cfile = '@'.$tmpuri;
$docrender_out = tmpfile();
$post = array('data' => $cfile);
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $render_url . "printsheets");
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_FILE, $docrender_out);
// curl defaults to outputting the result to stdout.
if (curl_exec($ch)) {
    header('Content-Type: application/pdf');
    $filename = $con['name'] . "-copysheets-" . time() . ".pdf";
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    $outmeta = stream_get_meta_data($docrender_out);
    readfile($outmeta['uri']);
} else {
    $response['status'] = 'error';
    $response['error'] = $error_msg;
    if($returnAjaxErrors) { 
        ajaxSuccess($response);
        exit(); 
    } else { return $response; }
    // Damnit, Jim, we have an error.
}

$response['status'] = 'Success';
return $response;
}

