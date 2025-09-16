<?php
// loadReport - load a .rpt type processor report and output same
require_once "../lib/base.php";
require_once "../../lib/log.php";

$check_auth = google_init("ajax");
$perm = "gen_rpts";

$response = array("perm"=>$perm);

if($check_auth == false || !checkAuth($check_auth['sub'], $perm)) {
    $response['error'] = "Authentication Failed";
    ajaxSuccess($response);
    exit();
}

if ((!array_key_exists('action', $_POST)) || (!array_key_exists('report', $_POST)) || (!array_key_exists('group', $_POST))
    || (!array_key_exists('prefix', $_POST))|| $_POST['action'] != "fetch") {
    $response['error'] = 'Invalid Arguments';
    ajaxSuccess($response);
    exit();
}

$reportName = $_POST['report'];
$group = $_POST['group'];
$prefix = $_POST['prefix'];
// load the .grp file
$groupParams = parse_ini_file(__DIR__ . "/../reports/$group", true);
$hdrAuth = $groupParams['group']['auth'];
$report = $groupParams[$reportName];

$response['hdrAuth'] = $hdrAuth;
$response["reportName"] = $reportName;
$response["prefix"] = $prefix;
$response["report"] = $report;

if (!checkAuth($check_auth['sub'], $hdrAuth)) {
    $response['error'] = 'You do not have permission to access this report group';
    ajaxSuccess($response);
    exit();
}
$template = $report['template'];
$response['description'] = $report['description'];
$reportParams = parse_ini_file(__DIR__ . "/../reports/$prefix/$template", true);
if ($reportParams === false) {
    $response['error'] = 'Report template not found or had errors in it.  Seek assistance.';
    ajaxSuccess($response);
    exit();
}
$reportHdr = $reportParams['report'];
$reportAuth = $reportHdr['auth'];
if (array_key_exists('index', $reportHdr))
    $response['index'] = $reportHdr['index'];
if (array_key_exists('csvfile', $reportHdr))
    $response['csvfile'] = $reportHdr['csvfile'];
if ($reportAuth != $hdrAuth) {
    if (!checkAuth($check_auth['sub'], $reportAuth)) {
        $response['error'] = 'You do not have permission to access this specific report';
        ajaxSuccess($response);
        exit();
    }
}

$debug = get_conf('debug');
if (array_key_exists('controll_reports', $debug)) {
    $dumpSQL = $debug['controll_reports'];
    $log_conf = get_conf('log');
    logInit($log_conf['controll']);
} else {
    $dumpSQL = 0;
}

$response["reportTitle"] = $reportHdr['name'];
if (array_key_exists('totals', $reportHdr))
    $response['calcPosition'] = $reportHdr['totals'];
if (array_key_exists('subtotals', $reportHdr))
    $response['groupby'] = $reportHdr['subtotals'];

$fieldArr = [];
$postVars = [];
if (array_key_exists('postVars', $_POST)) {
    $postVars = $_POST['postVars'];
}

$sections = array_keys($reportParams);
$sql = '';
sort($sections);
// ok, we now have the report itself, build the SQL
// first the CTE's if any
$first = true;
foreach ($sections AS $key => $section) {
    if (!str_starts_with($section, 'C'))
        continue;

    // build the cte..
    $cte = $reportParams[$section];
    if ($first) {
        $sql .= "WITH ";
        $first = false;
    } else {
        $sql .= "), ";
    }
    $sql .= $cte['name'] .  ' AS (' . PHP_EOL . $cte['select'] . PHP_EOL . $cte['tables'] . PHP_EOL . $cte['where'] . PHP_EOL;
}
if ($first == false)
    $sql .= ")\n";
// now the main body of the select
$sql .= "SELECT DISTINCT" . PHP_EOL;
$first = '';
foreach ($sections AS $key => $section) {
    if (!str_starts_with($section, 'F'))
        continue;

    // F start is a sql/field setting
    $fields = $reportParams[$section];
    $sql .= $first . $fields['sql'] . ' AS ' . $fields['name'] . PHP_EOL;
    unset($fields['sql']);
    $fieldArr[] = $fields;
    $first = ', ';
}

// after the main SQL stuff we need the from/joins
$first = "FROM ";
foreach ($sections AS $key => $section) {
    if (!str_starts_with($section, 'T'))
        continue;
    $table = $reportParams[$section];
    if (array_key_exists('left', $table))
        $sql .= "LEFT OUTER ";
    $sql .= $first . $table['name'];
    if (array_key_exists('alias', $table) && $table['alias'] != "")
        $sql .= " " . $table['alias'];
    if (array_key_exists('join', $table) && $table['join'] != "")
        $sql .= " ON " . $table['join'];
    $sql .= PHP_EOL;
    $first = "JOIN ";
}

// now the where clause
if (array_key_exists('where', $reportParams)) {
    $sql .= "WHERE" . PHP_EOL;
    $clause = $reportParams['where'];
    $wkeys = array_keys($clause);
    sort($wkeys);
    foreach ($wkeys AS $value) {
        $sql .= $clause[$value] . PHP_EOL;
    }
}
// now the group by clause
if (array_key_exists('group', $reportParams)) {
    $sql .= 'GROUP BY ' . PHP_EOL;
    $clause = $reportParams['group'];
    $skeys = array_keys($clause);
    sort($wkeys);
    $first = '';
    foreach ($wkeys as $value) {
        if ($first == '') {
            $first = ', ';
        }
        else {
            $sql .= $first;
        }
        $sql .= $clause[$value] . PHP_EOL;
    }
}

// now the order by clause
if (array_key_exists('sort', $reportParams)) {
    $sql .= 'ORDER BY ' . PHP_EOL;
    $clause = $reportParams['sort'];
    $skeys = array_keys($clause);
    sort($wkeys);
    $first = "";
    foreach ($wkeys AS $value) {
        if ($first == "") {
            $first = ", ";
        } else {
            $sql .= $first;
        }
        $sql .= $clause[$value] . PHP_EOL;
    }
}

// now terminate the SQL statement
$sql .= ';' . PHP_EOL;

// find the parameters if any
$paramArray = [];
$typeStr = '';
foreach ($sections as $key => $section) {
    if (!str_starts_with($section, 'P'))
        continue;

    $param = $reportParams[$section];
    $typeStr .= $param['datatype'];
    $value = null;
    switch ($param['type']) {
        case 'config':
            $value = getConfValue($param['section'], $param['item'], null);
            break;

        case 'constant':
            $value = $param['value'];
            break;
        case 'prompt':
            if (array_key_exists($param['item'], $postVars))
                $value = $postVars[$param['item']];
    }
    $paramArray[] = $value;
}

if ($dumpSQL > 0) {
    logWrite(array("message" => "report $reportName SQL Dump", "SQL" => $sql, "typeStr" => $typeStr, "params" => $paramArray));
}

// run the SQL and get the data
if ($typeStr != "") {
    $rows = dbSafeQuery($sql, $typeStr, $paramArray);
} else {
    $rows = dbQuery($sql);
}
if ($rows === false) {
    $response['error'] = "Database Query Error, seek assistance";
    $response['sql'] = $sql;
    ajaxSuccess($response);
    exit();
}

$data = [];
while ($row = $rows->fetch_assoc()) {
    $data[] = $row;
}
$rows->free();
$response['data'] = $data;
$response['fields'] = $fieldArr;
$response['success'] = count($data) . " rows returned";

ajaxSuccess($response);
