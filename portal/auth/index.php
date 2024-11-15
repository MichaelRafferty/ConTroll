<?php
// Registration Portal Oauth Test harness - index.php - redirect to portal
require_once("../lib/base.php");

$portal_conf = get_conf('portal');
header('location:' . $portal_conf['portalsite']);
exit();
