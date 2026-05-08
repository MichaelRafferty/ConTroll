<?php
    //  Reg Totals over time report
    // 3 arguments
    //  start date (defaults to epoch if not entered)
    //  end date (defaults to now if not entered)
    //  monthly - m: break into columns by month, t: totals only

    require_once '../../lib/phpReports.php';
// use common global Ajax return functions
    global $returnAjaxErrors, $return500errors;
    $returnAjaxErrors = true;
    $return500errors = true;

    $perm = 'reg_admin';
    $response = array ('post' => $_POST, 'get' => $_GET, 'perm' => $perm);
    $authToken = new authToken('script');
    $response['tokenStatus'] = $authToken->checkToken();
    if (!$authToken->isLoggedIn()) {
        $response['error'] = 'Authentication Failed';
        ajaxSuccess($response);
        exit();
    }
    if (!$authToken->checkAuth($perm)) {
        $response['error'] = 'Insufficient Permissions';
        ajaxSuccess($response);
        exit();
    }

    $response = loadReportInfo($authToken);
    $response['post'] = $_POST;
    $response['get'] = $_GET;
    $response['tokenStatus'] = $authToken->checkToken();
    $postVars = $response['postVars'];

    $errorMsg = '';
    $continue = true;
    if (array_key_exists("startdate", $postVars) && trim($postVars['startdate']) != '')
        $startDate = trim($postVars['startdate']);
    else
        $startDate = '2001-01-01';

    if (array_key_exists('enddate', $postVars) && trim($postVars['enddate']) != '')
        $endDate = $postVars['enddate'];
    else
        $endDate = date('Y-m-d');

    if (array_key_exists('monthly', $postVars) && trim($postVars['monthly']) != '')
        $groupBy = strtolower(substr(trim($postVars['monthly']), 0, 1));
    else
        $groupBy = 't';

    $startDateObj = date_create($startDate);
    if ($startDateObj === false) {
        $errorMsg .= "Data Start Date is not a valid date in the format YYYY-MM-DD\n";
        $continue = false;
    }

    $endDateObj = date_create($endDate);
    if ($endDateObj === false) {
        $errorMsg .= "Data End Date is not a valid date in the format YYYY-MM-DD\n";
        $continue = false;
    }

    if ($groupBy != 't' && $groupBy != 'm') {
        $errorMsg .= "Group by must be m or t only\n";
    }

    if (!$continue) {
        $response['error'] = str_replace("\n", "<br/>\n", $errorMsg);
        $response['status'] = 'error';
        ajaxSuccess($response);
        exit();
    }

    $startStr = date_format($startDateObj, 'Y-m');
    $endStr = date_format($endDateObj, 'Y-m');
    $conid = getConfValue('con', 'id', '-1');

    // now load the data from the database
    $rQ = <<<EOS
WITH compRegs AS (
SELECT t.complete_date, r.id, m.memType, m.memCategory, m.memAge, r.paid, r.price, r.status,
       m.label, m.glLabel, 
       DATE_FORMAT(CASE 
           WHEN r.price = 0 THEN r.create_date
           ELSE IFNULL(t.complete_date, r.create_date)
       END, '%Y-%m') AS month
FROM reg r
LEFT OUTER JOIN transaction t ON r.complete_trans = t.id
LEFT OUTER JOIN perinfo p ON r.perid = p.id
JOIN memList m ON r.memId = m.id
JOIN ageList a ON a.conid = r.conid AND a.ageType = m.memAge
WHERE r.conid IN (?, ?) AND IFNULL(p.deceased, 'N') != 'Y'
)
SELECT * FROM compRegs WHERE month BETWEEN ? AND ?; 
EOS;

    $rR = dbSafeQuery($rQ, 'iiss', array($conid, $conid + 1, $startStr, $endStr));

    // get the months (horizontal columns for the table while looping the actual data into the reg array
    $regs = [];
    $colTotals = [];
    while ($rL = $rR->fetch_assoc()) {
        $regs[] = $rL;
        if ($groupBy == 'm') {
            if (!array_key_exists($rL['month'], $colTotals)) {
                $colTotals[$rL['month']] = 1;
            } else {
                $colTotals[$rL['month']]++;
            }
        }
    }

    // in YYYY-MM format, can just sort the month array in ascii order to get the columns in order
    $cols = array_keys($colTotals);
    sort($cols, SORT_STRING);
    $colTotals['Total'] = $rR->num_rows;
    $top = "&nbsp;\n" . $rR->num_rows . " registrations loaded\n";
    $rR->free();

    // build the tabulator specs, for the columns across a row, col1 = the title of the row
    $columns = [];
    $columns[] = [
        'title' => "Registration Counts,<br/>Run: " . date('Y-m-d H-i-s'),
        'field' => 'rowTitle',
        'width' => '250',
        'headerSort' => false,
        'headerWordWrap' => true,
        'formatter' => 'html',
    ];

    $labelRowCols = ['rowTitle' => 'label'];
    // if doing it by month, loop over the months adding each column
    if ($groupBy == 'm') {
        foreach ($cols as $col) {
            $columns[] = [
                    'title' => $col,
                    'field' => $col,
                    'width' => 90,
                    'headerSort' => false,
                     'hozAlign' => 'right',
                    'headerHozAlign' => 'right',
            ];
            $labelRowCols[$col] = '';
        }
    }

    // and the last column is the total column
    $columns[] = [
        'title' => 'Total',
        'field' => 'total',
        'width' => '90',
        'headerSort' => false,
        'hozAlign' => 'right',
        'headerHozAlign' => 'right',
    ];
    $labelRowCols['total'] = '';

    // build base specifications for the tabulator table, add the data later to the table specs
    $tableData = []; // array by row of columnar data: title, [month 1 ... month n,] total
    $tableSpecs = [
        'maxHeight' => '1000px',
        'layout' => 'fitColumns',
        'index' => 'rowName',
        'columns' => $columns,
    ];
    
    $attGL = [];
    $compGL = [];
    $onlGL = [];
    $wsfsGL = [];
    $preGL = [];
    $otherGL = [];
    for ($i = 0; $i < count($regs); $i++) {
        //attending memberships by gl label
        $reg = $regs[$i];
        $status = $reg['status'];
        $type = $reg['memType'];
        $cat = $reg['memCategory'];
        $paid = (float) $reg['paid'];
        $price =(float) $reg['price'];
        $age = $reg['memAge'];
        $month = $reg['month'];
        $glLabel = $reg['glLabel'];
        $label = $reg['label'];
        if ($glLabel != '') { // Sections 1-5
            if (($type == 'full' || $type == 'oneday') && ($status == 'paid' || $status == 'plan') &&
                ($price > 0.0 || $age == 'child' || $age == 'kit' || str_contains(strtolower($label), 'upgrade'))) {
                // Section 1: attending section rules
                //      status is paid or plan, type = full or one day, price > 0 or label contains 'upgrade', or any age child
                if (!array_key_exists($glLabel, $attGL)) {
                    $attGL[$glLabel] = [];
                    $attGL[$glLabel]['rowTitle'] = $glLabel;
                    for ($c = 1; $c < count($columns); $c++) {
                        $attGL[$glLabel][$columns[$c]['field']] = 0;
                    }
                }
                if ($groupBy == 'm') {
                    $attGL[$glLabel][$month]++;
                }
                $attGL[$glLabel]['total']++;
            } else if ($price == 0 && ($type == 'full' || $type == 'oneday')  && ($status == 'paid' || $status == 'plan')) {
                // Section 2: Comp Attending
                if (!array_key_exists($glLabel, $compGL)) {
                    $compGL[$glLabel] = [];
                    $compGL[$glLabel]['rowTitle'] = $glLabel;
                    for ($c = 1; $c < count($columns); $c++) {
                        $compGL[$glLabel][$columns[$c]['field']] = 0;
                    }
                }
                if ($groupBy == 'm') {
                    $compGL[$glLabel][$month]++;
                }
                $compGL[$glLabel]['total']++;
            } else if ($type == 'virtual' && ($status == 'paid' || $status == 'plan')) {
                // Section 3: Online
                if (!array_key_exists($glLabel, $onlGL)) {
                    $onlGL[$glLabel] = [];
                    $onlGL[$glLabel]['rowTitle'] = $glLabel;
                    for ($c = 1; $c < count($columns); $c++) {
                        $onlGL[$glLabel][$columns[$c]['field']] = 0;
                    }
                }
                if ($groupBy == 'm') {
                    $onlGL[$glLabel][$month]++;
                }
                $onlGL[$glLabel]['total']++;
            } else if ($type == 'wsfs'  && $status == 'paid') {
                // Section 4: WSFS
                if (!array_key_exists($glLabel, $wsfsGL)) {
                    $wsfsGL[$glLabel] = [];
                    $wsfsGL[$glLabel]['rowTitle'] = $glLabel;
                    for ($c = 1; $c < count($columns); $c++) {
                        $wsfsGL[$glLabel][$columns[$c]['field']] = 0;
                    }
                }
                if ($groupBy == 'm') {
                    $wsfsGL[$glLabel][$month]++;
                }
                $wsfsGL[$glLabel]['total']++;
            } else if (strtolower($type) == 'presupport' && $status == 'paid') {
                // Section 5: Presupport
                if (!array_key_exists($glLabel, $preGL)) {
                    $preGL[$glLabel] = [];
                    $preGL[$glLabel]['rowTitle'] = $glLabel;
                    for ($c = 1; $c < count($columns); $c++) {
                        $preGL[$glLabel][$columns[$c]['field']] = 0;
                    }
                }
                if ($groupBy == 'm') {
                    $preGL[$glLabel][$month]++;
                }
                $preGL[$glLabel]['total']++;
            } else if ($type != 'donation' && ($status == 'paid' || $status == 'plan')) {
                // Section 6: Other, not donation
                if ($glLabel == '')
                    $glLabel = 'Label: ' . $reg['label'];
                if (!array_key_exists($glLabel, $otherGL)) {
                    $otherGL[$glLabel] = [];
                    $otherGL[$glLabel]['rowTitle'] = $glLabel;
                    for ($c = 1; $c < count($columns); $c++) {
                        $otherGL[$glLabel][$columns[$c]['field']] = 0;
                    }
                }
                if ($groupBy == 'm') {
                    $otherGL[$glLabel][$month]++;
                }
                $otherGL[$glLabel]['total']++;
            }
        }
        
    }
    // header row showing date range
    $labelRowCols['rowTitle'] = "Date Range:<br/>$startDate to $endDate";
    $tableData[] = $labelRowCols;

    // Section 1 Attending
    $labelRowCols['rowTitle'] = '<b>Attending Memberships</b>';
    $tableData[] = $labelRowCols;
    $GLs = array_keys($attGL);
    sort($GLs, SORT_STRING);
    $first = true;
    $totals = [];
    $totals['rowTitle'] = '<b>Total Attending</b>';
    foreach ($GLs AS $gl) {
        if ($first) {
            $totals = $attGL[$gl];
            $totals['rowTitle'] = '<b>Total Attending</b>';
            $first = false;
        } else {
            foreach ($attGL[$gl] as $name => $value) {
                if ($name != 'rowTitle')
                    $totals[$name] += $value;
            }
        }
        $tableData[] = $attGL[$gl];
    }
    $tableData[] = $totals;
    $tableData[] = [];
    
    // Section 2 Comps
    $labelRowCols['rowTitle'] = '<b>Comp Memberships</b>';
    $tableData[] = $labelRowCols;
    $GLs = array_keys($compGL);
    sort($GLs, SORT_STRING);
    $first = true;
    $totals = [];
    $totals['rowTitle'] = '<b>Total Comps</b>';
    foreach ($GLs AS $gl) {
        if ($first) {
            $totals = $compGL[$gl];
            $totals['rowTitle'] = '<b>Total Comps</b>';
            $first = false;
        } else {
            foreach ($compGL[$gl] as $name => $value) {
                if ($name != 'rowTitle')
                    $totals[$name] += $value;
            }
        }
        $tableData[] = $compGL[$gl];
    }
    $tableData[] = $totals;
    $tableData[] = [];

    // Section 3 Online
    if (count($onlGL) > 0) {
        $labelRowCols['rowTitle'] = '<b>Online Memberships</b>';
        $tableData[] = $labelRowCols;
        $GLs = array_keys($onlGL);
        sort($GLs, SORT_STRING);
        $first = true;
        $totals = [];
        $totals['rowTitle'] = '<b>Total Online</b>';
        foreach ($GLs as $gl) {
            if ($first) {
                $totals = $onlGL[$gl];
                $totals['rowTitle'] = '<b>Total Online</b>';
                $first = false;
            } else {
                foreach ($onlGL[$gl] as $name => $value) {
                    if ($name != 'rowTitle')
                        $totals[$name] += $value;
                }
            }
            $tableData[] = $onlGL[$gl];
        }
        $tableData[] = $totals;
        $tableData[] = [];
    }
    
    // Section 4 WSFS
    if (count($wsfsGL) > 0) {
        $labelRowCols['rowTitle'] = '<b>WSFS Memberships</b>';
        $tableData[] = $labelRowCols;
        $GLs = array_keys($wsfsGL);
        sort($GLs, SORT_STRING);
        $first = true;
        $totals = [];
        $totals['rowTitle'] = '<b>Total WSFS</b>';
        foreach ($GLs as $gl) {
            if ($first) {
                $totals = $wsfsGL[$gl];
                $totals['rowTitle'] = '<b>Total WSFS</b>';
                $first = false;
            } else {
                foreach ($wsfsGL[$gl] as $name => $value) {
                    if ($name != 'rowTitle')
                        $totals[$name] += $value;
                }
            }
            $tableData[] = $wsfsGL[$gl];
        }
        $tableData[] = $totals;
        $tableData[] = [];
    }
    
    // Section 5 PreSupport
    if (count($preGL) > 0) {
        $labelRowCols['rowTitle'] = '<b>Presupport Memberships</b>';
        $tableData[] = $labelRowCols;
        $GLs = array_keys($preGL);
        sort($GLs, SORT_STRING);
        $first = true;
        $totals = [];
        $totals['rowTitle'] = '<b>Total Presupport</b>';
        foreach ($GLs as $gl) {
            if ($first) {
                $totals = $preGL[$gl];
                $totals['rowTitle'] = '<b>Total Presupport</b>';
                $first = false;
            } else {
                foreach ($preGL[$gl] as $name => $value) {
                    if ($name != 'rowTitle')
                        $totals[$name] += $value;
                }
            }
            $tableData[] = $preGL[$gl];
        }
        $tableData[] = $totals;
        $tableData[] = [];
    }
    
    // Section 6 Other
    if (count($otherGL) > 0) {
        $labelRowCols['rowTitle'] = '<b>Other Memberships</b>';
        $tableData[] = $labelRowCols;
        $GLs = array_keys($otherGL);
        sort($GLs, SORT_STRING);
        $first = true;
        $totals = [];
        $totals['rowTitle'] = '<b>Total Other</b>';
        foreach ($GLs as $gl) {
            if ($first) {
                $totals = $otherGL[$gl];
                $totals['rowTitle'] = '<b>Total Other</b>';
                $first = false;
            } else {
                foreach ($otherGL[$gl] as $name => $value) {
                    if ($name != 'rowTitle')
                        $totals[$name] += $value;
                }
            }
            $tableData[] = $otherGL[$gl];
        }
        $tableData[] = $totals;
    }
    
    $response['top'] = $top;
    $tableSpecs['data'] = $tableData;
    $response['tableSpecs'] = $tableSpecs;
    $response['csvfile'] = 'regTotals';
    $response['status'] = 'success';
    $response['success'] = 'Report Complete';
    ajaxSuccess($response);
