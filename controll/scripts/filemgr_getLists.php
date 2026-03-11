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

if (!array_key_exists('load_type', $_POST)) {
    $response['error'] = 'Parameter Error';
    ajaxSuccess($response);
    exit();
}

$loadType = $_POST['load_type'];
$imgCount = 0;

// controll images
    if ($admin && ($loadType == 'all' || $loadType == 'controll')) {
        $imgCount += loadDir('controll', '../images', 'images', $response);
    }
    if ($admin && ($loadType == 'all' || $loadType == 'report')) {
        $imgCount += loadDir('report', '../reportdata', 'reportdata', $response);
    }
    if ($admin && ($loadType == 'all' || $loadType == 'online')) {
        $imgCount += loadDir('online', '../../onlinereg/images', 'onlineregimages', $response);
    }
    if ($admin && ($loadType == 'all' || $loadType == 'portal')) {
        $imgCount += loadDir('portal', '../../portal/images', 'portalimages', $response);
    }
    if ($admin && ($loadType == 'all' || $loadType == 'exhibitor')) {
        $imgCount += loadDir('exhibitor', '../../vendor/images', 'vendorimages', $response);
    }

$response['success'] = "$imgCount files found";
ajaxSuccess($response);

function loadDir($section, $path, $dir, &$response) {
    $response[$section]  = 1;
    $dirList = scandir($path);
    $fileInfo = [];
    $fileCount = 0;
    foreach ($dirList as $file) {
        if (str_starts_with($file, '.'))
            continue;
        if (filetype("$path/$file") != 'file')
            continue;

        $fileInfo[$file] = array(
            'size' => filesize("$path/$file"),
            'created' => date('Y-m-d H:i:s', filectime("$path/$file")),
            'modified' => date('Y-m-d H:i:s', filemtime("$path/$file")),
            'path' => "$dir/$file",
        );
        $fileCount++;
    }
    $response[$section . 'Files'] = $fileInfo;
    return $fileCount;
}
