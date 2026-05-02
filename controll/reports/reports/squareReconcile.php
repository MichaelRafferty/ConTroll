<?php
    //  Reconcile Square Transaction Files against the ConTroll Database
    // 4 arguments: start date, end date, transaction csv, items csv
    // Can compare all payments/orders including reg, space, art

    require_once '../../lib/phpReports.php';
// use common global Ajax return functions
    global $returnAjaxErrors, $return500errors;
    $returnAjaxErrors = true;
    $return500errors = true;

    $perm = 'finance';
    $response = array ('post' => $_POST, 'get' => $_GET, 'perm' => $perm);
    $authToken = new authToken('script');
    $response['tokenStatus'] = $authToken->checkToken();
    if (!$authToken->isLoggedIn()) {
        $response['error'] = 'Authentication Failed';
        ajaxSuccess($response);
        exit();
    }
    if (!$authToken->checkAuth($perm)) {
        $response['error'] = 'Insufficient permissions';
        ajaxSuccess($response);
        exit();
    }
    $response = loadReportInfo($authToken);
    $response['post'] = $_POST;
    $response['get'] = $_GET;
    $response['tokenStatus'] = $authToken->checkToken();
    $postVars = $response['postVars'];


    $continue = true;
    $errorMsg = '';
    $startDate = '';
    $endDate = '';
    $transFile = '';
    $filePath = '../../reportdata/';
    if (array_key_exists("startdate", $postVars))
        $startDate = $postVars['startdate'];
    if (array_key_exists('enddate', $postVars))
        $endDate = $postVars['enddate'];
    if (array_key_exists('itemfile', $postVars))
         $itemsFile = $postVars['itemfile'];
    if (array_key_exists('transfile', $postVars))
        $transFile = $postVars['transfile'];

    // parse the prompts
    if ($startDate == '') {
        $errorMsg .= "Data Start Date not provided and is required\n";
        $continue = false;
    } else {
        $startDate = date_create($startDate);
        if ($startDate === false) {
            $errorMsg .= "Data Start Date is not a valid date in the format YYYY-MM-DD\n";
            $continue = false;
        }
    }

    if ($endDate == '') {
        $errorMsg .= "Data End Date not provided and is required\n";
        $continue = false;
    } else {
        $endDate = date_create($endDate);
        if ($endDate === false) {
            $errorMsg .= "Data End Date is not a valid date in the format YYYY-MM-DD\n";
            $continue = false;
        }
    }

    if ($transFile == '') {
        $errorMsg .= "Transaction CSV file not provided and is required\n";
        $continue = false;
    } else {
        $transFile = $filePath . $transFile;
        if (!is_readable($transFile)) {
            $errorMsg .= "Transaction CSV file not readable in the reportdata directory\n";
            $continue = false;
        }
    }

    if (!$continue) {
        $response['error'] = str_replace("\n", "<br/>\n", $errorMsg);
        $response['status'] = 'error';
        ajaxSuccess($response);
        exit();
    }

    // ok, the arguments are real and parsed, now get the square data
    $transactions = loadToCSVArray($transFile);

    // now load the data from the database for transactions and payments
    $tQ = <<<EOS
SELECT t.*
FROM transaction t
WHERE t.complete_date BETWEEN ? AND ? AND t.paid > 0
ORDER by id;
EOS;

    $startStr = date_format($startDate, "Y-m-d 00:00:00");
    $endStr = date_format($endDate, 'Y-m-d 23:59:59');
    $tR = dbSafeQuery($tQ, 'ss', array($startStr, $endStr));
    if ($tR == false) {
        errorReturn($response, 'Error in transaction query');
    }

    $dbTrans = [];
    $dbTids = [];
    while ($tL = $tR->fetch_assoc()) {
        $paymentId = $tL['ccPaymentId'] == null ? $tL['id'] : $tL['ccPaymentId'];
        $tL['matched'] = 0;
        $dbTrans[$paymentId] = $tL;
        $dbTids[$tL['id']] = $paymentId;
    }
    $tR->free();
    $pQ = <<<EOS
select t.id, t.complete_date, p.* 
FROM transaction t
JOIN payments p ON p.transid = t.id
where  t.complete_date between ? and ?
ORDER BY p.id;
EOS;
    $pR = dbSafeQuery($pQ, 'ss', array($startStr, $endStr));
    if ($pR == false) {
        errorReturn($response, 'Error in payments query');
    }

    $dbPayments =[];
    while ($pL = $pR->fetch_assoc()) {
        $id = $pL['receipt_url'];
        if ($id == null)
            $id = $pL['transid'];
        else {
            $pos = strrpos($id, '/');
            if ($pos !== false)
                $id = substr($id,$pos + 1);
        }
        $dbPayments[$id] = $pL;
    }
    $pR->free();


    $output = "&nbsp;\n" . count($transactions) . " transactions loaded\n" .
            count($dbTrans) . " transactions loaded from database\n" .
            count($dbPayments) . " payments loaded from database\n\n";

    // loop over transactions in square and check against transactions and payments in database
    $noMatchCount = 0;
    $issueCnt = 0;
    $first = true;
    $nonMatchedTrans = [];
    for ($i = 0; $i < count($transactions) ; $i++) {
        $transIssues = '';
        $trans = $transactions[$i];
        $transid = $trans['Transaction ID'];
        $paymentid = $trans['Payment ID'];
        $dbt = null;
        // primary match is square transaction id
        // next match is square payment id in trans
        // next match is square payment id in payments
        // last choice is to look at description and try to find the raw transaction id
        if (array_key_exists($transid, $dbTrans)) {
            $dbt = $dbTrans[$transid];
            $dbTrans[$transid]['matched'] = 1;
        } else if (array_key_exists($paymentid, $dbTrans)) {
            $dbt = $dbTrans[$paymentid];
            $dbTrans[$paymentid]['matched'] = 1;
        } else if (array_key_exists($paymentid, $dbPayments)) {
            $pmt = $dbPayments[$paymentid];
            $tid = $pmt['transid'];
            if (array_key_exists($tid, $dbTids)) {
                $tid = $dbTids[$tid];
                if (array_key_exists($tid, $dbTrans)) {}
                    $dbt = $dbTrans[$tid];
                    $dbTrans[$tid]['matched'] = 1;
            }
        } else {
            $desc = $trans['Description'];
            $matches = [];
            // 'reg\.\d+', 'pplan\.\d+', 'sp.\d+', 'mail\.\d+', 'plan\.\d+', 'art\.\d+'
            if (preg_match_all('/reg\.\d+|pplan\.\d+|sp\.\d+|mail\.\d+|plan\.\d+|art\.\d+/', $desc, $matches,
                PREG_OFFSET_CAPTURE | PREG_PATTERN_ORDER)) {
                foreach ($matches[0] as $match) {
                    // get section matched by the id
                    $section = substr($desc, $match[1]);
                    // if multiple items, take off the next item in the list
                    $comma = strpos($section, ',');
                    if ($comma !== false)
                        $section = substr($section, 0, $comma);
                    $metadata = explode('~', $section);
                    // now find the db transaction id in the metadata
                    $type = substr($metadata[0], 0, 2);
                    switch ($type) {
                        case 're':
                            $tid = $metadata[4];
                            break;
                        case 'pp':
                            $tid = $metadata[5];
                            break;
                        case 'sp':
                            $tid = $metadata[5];
                            break;
                        case 'pl':
                            $tid = $metadata[8];
                            break;
                        case 'ar':
                            $tid = $metadata[5];
                            break;
                        default: // note mail doesn't have the tid, so it should not be the only item in the record hopefully,
                                 // if that happens, we need to modify the note version and it's contents
                            $tid = null;
                    }
                    if ($tid != null)
                        break;
                }
                if ($tid != null) { // we have a match try to use that
                    if (array_key_exists($tid, $dbTids)) {
                        $tid = $dbTids[$tid];
                        if (array_key_exists($tid, $dbTrans))
                            $dbt = $dbTrans[$tid];
                    }
                }
            }
        }
        if ($dbt == null) {
            // no matching transaction, mark transaction not found
            $transactions[$i]['matched'] = 0;
            $nonMatchedTrans[] = $transactions[$i];
            $noMatchCount++;
        } else {
            $transactions[$i]['matched'] = 1;
            // now check for any issues with this transaction
            // need to check: price, tax/tax1, tax2, ... tax5, paid in transaction

            // Date,Time,Time Zone,Gross Sales,Discounts,Service Charges,Net Sales,Gift Card Sales,Tax,Tip,Partial Refunds,Total Collected,
            //      Source,Card,Card Entry Methods,Cash,Square Gift Card,Other Tender,Other Tender Type,Tender Note,Fees,Net Total,Transaction ID,
            //      Payment ID,Card Brand,PAN Suffix,Device Name,Staff Name,Staff ID,Details,Description,Event Type,Location,Dining Option,Customer ID,
            //      Customer Name,Customer Reference ID,Device Nickname,Third Party Fees,Deposit ID,Deposit Date,Deposit Details,Fee Percentage Rate,
            //      Fee Fixed Rate,Refund Reason,Discount Name,Transaction Status,Cash App,Order Reference ID,Fulfillment Note,Free Processing Applied,
            //      Channel,Unattributed Tips,Table Info,International Fee
            if (compareSquareDB($trans['Gross Sales'], $dbt['price']))
                $transIssues .= "Square Gross Sales of " . $trans['Gross Sales'] . " does not match transaction price of " . $dbt['price'] . PHP_EOL;
            $net = $dbt['price'] - ($dbt['couponDiscountCart'] + $dbt['couponDiscountReg']);
            if (compareSquareDB($trans['Net Sales'], $net))
                $transIssues .= 'Square Net Sales of ' . $trans['Net Sales'] . ' does not match transaction price of ' . $net . PHP_EOL;
            if ($dbt['tax1'] != null) {
                $tax = $dbt['tax1'];
                if ($dbt['tax2'] != null)
                    $tax += $dbt['tax2'];
                if ($dbt['tax3'] != null)
                    $tax += $dbt['tax3'];
                if ($dbt['tax4'] != null)
                    $tax += $dbt['tax4'];
                if ($dbt['tax5'] != null)
                    $tax += $dbt['tax5'];
            } else
                $tax = $dbt['tax'];
            if (compareSquareDB($trans['Tax'], $tax))
                $transIssues .= 'Square Tax of ' . $trans['Tax'] . ' does not match transaction tax of ' . $tax . PHP_EOL;
            if (compareSquareDB($trans['Total Collected'], $dbt['withtax']))
                $transIssues .= 'Square Total Collected of ' . $trans['Total Collected'] .
                        ' does not match transaction withtax of ' . $dbt['withtax'] . PHP_EOL;

            if ($transIssues != '') {
                if ($first) {
                    $output .= "&nbsp;\n<h2>Transactions with Mismatched Data</h2>";
                    $first = false;
                }
                $output .= "Transaction Issues on " . $trans['Date'] . ' ' . $trans['Time'] . ' for ' . $trans['Transaction ID'] . ' vs ' .
                        $dbt['id'] . ' (' . $dbt['complete_date'] . ") for:\n&nbsp;&nbsp;&nbsp;&nbsp;" . $trans['Description'] . PHP_EOL .
                        $transIssues . PHP_EOL . PHP_EOL;
                $issueCnt++;
            }

            // need to check payment: type, pre-tax, tax, amount
            //TODO need to check items
        }
    }

    if ($noMatchCount > 0) {
        $output .= "&nbsp;\n<h2>Transactions in Square, not in ConTroll</h2>\n";
        // these did not find a record in ConTroll, this is most likely something sold outside of ConTroll, or a bug in the report matching.
        foreach ($nonMatchedTrans as $trans) {
            $output .= $trans['Date'] . ' ' . $trans['Time'] . ' for ' . $trans['Transaction ID'] . ") for:\n&nbsp;&nbsp;&nbsp;&nbsp;" .
                $trans['Description'] . PHP_EOL;
        }
    }

    // count unmatched db transactions
    $dbUnmatched = [];
    foreach ($dbTrans as $dbt) {
        if ($dbt['matched'] == 0) {
            $dbUnmatched[] = $dbt;
        }
    }

    if (count($dbUnmatched) > 0) {
        $output = $output .= "&nbsp;\n<h2>Transactions in ConTroll, not in Square</h2>\n";
        foreach ($dbUnmatched as $dbt) {
            $output .= $dbt['complete_date'] . ' of type ' . $dbt['type'] . "\n";
        }
    }

    $output .= "&nbsp;\n<h2>Summary</h2>\n$issueCnt transactions had data issues.\n";
    $output .= "$noMatchCount lines did not match a transaction.\n";
    $output .= count($dbUnmatched) . " ConTroll transactions did not match a square transaction.\n";

    $output = str_replace("\n", "<br/>\n", $output);

//echo $query; exit();
    $response['output'] = $output;
    $response['status'] = 'success';
    $response['success'] = 'Report Complete';
    ajaxSuccess($response);

function errorReturn($ret, $errmsg) {
    $rtn['error'] = $errmsg;
    ajaxSuccess($rtn);
    exit();
}

function compareSquareDB($sq, $db) {
    $sq = str_replace('$', '', $sq);
    $sq = str_replace(',', '', $sq);
    if ($sq < 0)
        $sq = -$sq;
    return (0+$sq) != (0+$db);
}
