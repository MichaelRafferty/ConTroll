<?php
exit(); 	//disable for now
// Registration Portal Oauth Test harness - tstart.php - redirect to portal
require_once("lib/base.php");

$portal_conf = get_conf('portal');
$redirect = $portal_conf['portalsite'];

// draw harness top
index_page_init("Auth Test Harness - Request");

$retdata = 'Nom';
$returl = $portal_conf['portalsite'] . '/auth/authcomplete';
$apikey = 'testAPIkey';
$app = 'Test Harness';

$args = json_encode(array('retdata' => $retdata, 'returl' => $returl, 'apikey' => $apikey, 'app' => $app ));
echo <<<EOS
<h1>Auth Test Harness - Requester</h1>
<p>Forming request for:</p>
<UL>
    <LI>retdata: $retdata</LI>
    <LI>return: $returl</LI>
    <LI>apikey: $apikey</LI>
    <LI>app: $app</LI>
</UL>
EOS;
$oauth = encryptCipher($args, true);
$url = "$redirect?oauth=$oauth";
echo "<pre>Click to: <a href='$url' target='_new'>$url</a>\n\n</pre>\n";

