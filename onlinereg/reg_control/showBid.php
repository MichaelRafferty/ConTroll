<?php
require_once "lib/base.php";
//initialize google session
$need_login = google_init("page");

$page = "artshow";
if(!$need_login or !checkAuth($need_login['sub'], $page)) {
    bounce_page("index.php");
}

if(!isset($_GET) || !isset($_GET['id'])) {
?>
    <div>No Id Provided</div>
<?php
    exit();
}

// Heck with it, I'm just getting something working. Will do it right next
// month. -BSA
$renderer_conf = get_conf('renderer');
$renderer_url = ($renderer_conf ? $renderer_conf['url'] : 'http://localhost:3000/'); // set default

$id= sql_safe($_GET['id']);
$con = get_con();
$conid = $con['id'];

$query = "SELECT v.name artist_name, ats.art_key artist_id, i.title work_name,
i.item_key work_id, i.material,
i.min_price, i.sale_price, i.type
FROM artItems i
JOIN artshow ats ON ats.id = i.artshow
JOIN artist a ON a.id = ats.artid
JOIN vendors v on v.id = a.vendor
WHERE ats.artid = " . sql_safe($id) . "
AND i.type IN ('art', 'nfs')
AND   i.conid = " . sql_safe($conid);

$results = dbQuery($query);

// (U) Redirect to explanatory message if no results.
if ($results->num_rows == 0) {
    header('Content-Type: text/html');
    echo <<<EOM
<!DOCTYPE html>
<html>
<head><title>Nothing to print</title></head>
<body>
<h1>These are not the droids you’re looking for.</h1>
<p>You don't actually have any bidsheets to print.</p>
<p><small>For further information, please contact
<a href="mailto:artshow@bsfs.org">artshow@bsfs.org</a>.</small></p>
</body>
</html>
EOM;
    exit();
}

// Marshal art show pieces to XML.
$bidsxml = new XMLWriter;
$tmphandle = tmpfile();

$tmpmeta = stream_get_meta_data($tmphandle);
$tmpuri = $tmpmeta['uri'];
if (!$bidsxml->openURI($tmpuri)) {
    // I have no idea how this could even happen, though.
    header('Content-Type: text/html');
    echo $error_msg;
    exit();
}

$bidsxml->startDocument('1.0', 'UTF-8');
$bidsxml->startElement('bidsheets');
while($row = $results->fetch_assoc()) {
    $bidsxml->startElement('bidsheet');
    $bidsxml->writeElement('convention', $con['label']);
    // Artist block
    $bidsxml->startElement('artist');
    $bidsxml->writeAttribute('number', $row['artist_id']);
    if($row['artist_name'] != '') {
        $bidsxml->writeElement('name', $row['artist_name']);
    } else {
        $bidsxml->writeElement('name', $row['per_name']);
    }
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
curl_setopt($ch, CURLOPT_URL, $renderer_url . "bidsheets");
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
    // Damnit, Jim, we have an error.
    header('Content-Type: text/html');
    echo $error_msg;
}
curl_close($ch);

?>
