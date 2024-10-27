<?php
// Registration Portal Oauth Test harness - tend.php - dump authentication return
require_once("lib/base.php");

$portal_conf = get_conf('portal');
$redirect = $portal_conf['portalsite'];

// draw harness top
index_page_init("Auth Test Harness - Results");

if (!array_key_exists('oauth', $_REQUEST)) {
    echo "<h1>No oauth parameter passed</h1>";
    exit();
}

$args = decryptCipher($_GET['oauth'], true);
if ($args == null || $args == '') {
    echo '<h1>Invalid oauth parameter passed</h1>';
    exit();
}

echo <<<EOS
<h1>Authentication returned:</h1>
<pre>
EOS;
var_dump($args);
echo <<<EOS
</pre>

EOS;


