<?php
require_once "../lib/base.php";
require_once '../lib/sessionAuth.php';

// use common global Ajax return functions
global $returnAjaxErrors, $return500errors;
$returnAjaxErrors = true;
$return500errors = true;

$response = array ('post' => $_POST, 'get' => $_GET);
$authToken = new authToken('script');
$response['tokenStatus'] = $authToken->checkToken();

if (!$authToken->isLoggedIn()) {
    $response['error'] = 'Authentication Failed';
    ajaxSuccess($response);
    exit();
}

$admin = $authToken->checkAuth('admin');
$reg_staff = $authToken->checkAuth('reg_staff');
$regAdmin = $authToken->checkAuth('reg_admin');
$exhibitor = $authToken->checkAuth('exhibitor');
$finance = $authToken->checkAuth('finance');

// must have one of these permissions
if (!($admin || $reg_staff || $regAdmin || $exhibitor || $finance)) {
    $response['error'] = 'Authentication Failed';
    ajaxSuccess($response);
    exit();
}

$sourceDir = getConfValue('atcon', 'badges', '/usr/tmp');
$filename = $_REQUEST['fn'];
$type = substr($filename, 0, 5);
$file = substr($filename, 5);
if (str_contains($file, '/')) {
    echo "Invalid arguments: $filename\n";
}

switch ($type) {
    case '/pdf/':
        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="' . $file . '"');
        echo file_get_contents("$sourceDir/pdf/$file");
        break;

    case '/txt/':
        echo <<<EOS
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8"/>
    <title>Generic Printer Output: $file</title>
</head>
<body>
    <pre>
EOS;
        echo file_get_contents("$sourceDir/txt/$file");
        echo <<<EOS
    </pre>
</body>
</html>
EOS;

        break;
    default:
        echo "Invalid file type: $type\n";
}
return 0;
