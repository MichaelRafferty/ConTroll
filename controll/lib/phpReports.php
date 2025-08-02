<?php
require_once "base.php";
// library functions to make php reports in the reports processor easier to run

function loadReportInfo(): array
{
    if ((!array_key_exists('postVars', $_POST)) || (!array_key_exists('report', $_POST)) || (!array_key_exists('group', $_POST))
        || (!array_key_exists('prefix', $_POST)) || $_POST['action'] != 'fetch') {
        $response['error'] = 'Invalid Arguments';
        ajaxSuccess($response);
        exit();
    }

    $group = $_POST['group'];
    $reportName = $_POST['report'];
    $prefix = $_POST['prefix'];
    $groupParams = parse_ini_file(__DIR__ . "/../reports/$group", true);
    $hdrAuth = $groupParams['group']['auth'];
    $report = $groupParams[$reportName];
    $reportAuth = $report['auth'];

    $check_auth = google_init('ajax');
    $response = array ('perm' => $reportAuth);

    if ($check_auth == false || !checkAuth($check_auth['sub'], $reportAuth) || !checkAuth($check_auth['sub'], $hdrAuth)) {
        $response['error'] = 'Authentication Failed';
        ajaxSuccess($response);
        exit();
    }

    $con = get_conf('con');

    $respose['group'] = $_POST['group'];
    $response['reportName'] = $_POST['report'];
    $response['prefix'] = $_POST['prefix'];
    $response['hdrAuth'] = $hdrAuth;
    $response['reportName'] = $reportName;
    $response['prefix'] = $prefix;
    $response['report'] = $report;
    $response['postVars'] = $_POST['postVars'];
    $response['conid'] = $con['id'];
    return $response;
}
