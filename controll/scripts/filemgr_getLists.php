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

$response['admin'] = $admin;
$response['reg_staff'] = $reg_staff;
$response['reg_admin'] = $regAdmin;
$response['exhibitorRole'] = $exhibitor;
$response['finance'] = $finance;
$loadType = $_POST['load_type'];
$origDir = null;
$origName = null;
$newName = null;
if (array_key_exists('action', $_POST)) {
    $action = $_POST['action'];
    if (array_key_exists('origDir', $_POST))
        $origDir = $_POST['origDir'];
    if (array_key_exists('origName', $_POST))
        $origName = $_POST['origName'];
    if (array_key_exists('newName', $_POST))
        $newName = $_POST['newName'];
} else {
    $action = 'load';
}

switch ($action) {
    case 'rename':
        ini_set('display_errors', 0);
        $existingPath = "../$origDir/$origName";
        $newPath = "../$origDir/$newName";
        if (file_exists($newPath)) {
            $response['warn'] = "Can not rename $origName to $newName because $newName already exists. You may not overwrite an existing file.";
            ajaxSuccess($response);
            exit();
        }
        try {
            if (rename($existingPath, $newPath))
                $response['success'] = "Renaming $origDir/$origName to $origDir/$newName";
            else {
                $error = error_get_last();
                $errorMsg = $error['message'];
                $response['warn'] = "Error: Can not rename $origName to $newName due to $errorMsg.";
                ajaxSuccess($response);
                exit();
            }
        }
        catch (exception $e) {
            $errorMsg = $e->getMessage();
            $response['warn'] = "Error: Can not rename $origName to $newName due to $errorMsg.";
            ajaxSuccess($response);
            exit();
        }
        break;
    case 'delete':
        ini_set('display_errors', 0);
        $existingPath = "../$origDir/$origName";
        if (!file_exists($existingPath)) {
            $response['warn'] = "Can not delete $origName because it no longer exists.";
            ajaxSuccess($response);
            exit();
        }
        try {
            if (unlink($existingPath))
                $response['success'] = "Deleted $origDir/$origName";
            else {
                $error = error_get_last();
                $errorMsg = $error['message'];
                $response['warn'] = "Error: Can not delete $origName due to $errorMsg.";
                ajaxSuccess($response);
                exit();
            }
        }
        catch (exception $e) {
            $errorMsg = $e->getMessage();
            $response['warn'] = "Error: Can not delete $origName due to $errorMsg.";
            ajaxSuccess($response);
            exit();
        }

        $response['success'] = "Deleting $origDir/$origName";
        break;
}

$imgCount = 0;

// controll images
    if (($admin || $regAdmin || $reg_staff) && ($loadType == 'all' || $loadType == 'controll' || $loadType == 'images')) {
        $imgCount += loadDir('controll', '../images', 'images', $response);
    }
    if (($admin || $finance) && ($loadType == 'all' || $loadType == 'report' || $loadType == 'reportdata')) {
        $imgCount += loadDir('report', '../reportdata', 'reportdata', $response);
    }
    if (($admin || $reg_staff || $regAdmin) && ($loadType == 'all' || $loadType == 'online' || $loadType == 'onlinereg')) {
        $imgCount += loadDir('online', '../../onlinereg/images', 'onlineregimages', $response);
    }
    if (($admin || $reg_staff || $regAdmin) && ($loadType == 'all' || $loadType == 'portal')) {
        $imgCount += loadDir('portal', '../../portal/images', 'portalimages', $response);
    }
    if (($admin || $exhibitor) && ($loadType == 'all' || $loadType == 'exhibitor' || $loadType == 'vendor')) {
        $imgCount += loadDir('exhibitor', '../../vendor/images', 'vendorimages', $response);
    }
if (array_key_exists('success', $response))
    $response['success'] .= "<br/>$imgCount files found";
else
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
