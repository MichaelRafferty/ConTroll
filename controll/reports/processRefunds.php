<?php
// hardcode: This entire routine is hardcoded to the Balticon CC vendor, and is not portable to Square, and needs to be rewritten calling some function in the credit card processing .php file to make it portable
require_once "../lib/base.php";
require_once(__DIR__ . "/../../../lib/log.php");

$need_login = google_init("page");
$page = "reg_admin";

if(!$need_login or !checkAuth($need_login['sub'], $page)) {
    bounce_page("index.php");
}


$con = get_conf("con");
$conid=$con['id'];
$log = get_conf('log');
logInit($log['cancel'] . "_processing");
$ccauth = get_conf('cc');
$cclink = get_conf('cc-connect');

header('Content-Type: application/csv');
header('Content-Disposition: attachment; filename="refunds_results.csv"');

$memList = dbSafeQuery("SELECT id, label from memList WHERE memCategory='cancel' and conid=?", 'i', array($conid));

$memTypes = array();
while ($memArray = $memList->fetch_assoc()) {
    $memTypes[$memArray['label']] = $memArray['id'];
}

//get list of transactions
$txnQ = <<<EOS
SELECT DISTINCT M.label, Y.transid, Y.description, Y.cc_txn_id, Y.amount
FROM memLabel M
JOIN reg R ON (R.memId=M.id)
JOIN payments Y ON (Y.transid=R.create_trans)
WHERE M.id=?;
EOS;

$txnR = dbSafeQuery($txnQ, 'i', array($memTypes['Request Refund']));
$failed_refunds = array();

$badgeQ = <<<EOS
SELECT R.create_trans, M.label, R.id,
       TRIM(REGEXP_REPLACE(CONCAT(P.first_name, ' ', P.middle_name, ' ', P.last_name, ' ', P.suffix), ' +', ' ')) AS name,
       P.email_addr, P.phone, P.address, P.addr_2, P.city, P.state, P.zip, P.country
FROM reg R
JOIN perinfo P ON (P.id=R.perid)
JOIN memLabel M ON (M.id=R.memId)
WHERE R.create_trans=?;
EOS;

echo "Transaction, Label, Reference, Name, Email, Phone, Address, Addr_2, City, State, Zip, Country, Result, Reason\n";

while($txn = $txnR->fetch_assoc()) {
    $result = "failed";
    $reason = "unknown";

    if($txn['description'] != 'Balticon Online Registration') {
        $result = "failed";
        $reason = "Offline Transaction";
    } else if ($txn['cc_txn_id'] == '') {
        $result = "failed";
        $reason = "No Credit Card Transaction";
    } else {
        $ccreturn = array(
            'ssl_merchant_id'=>$ccauth['ssl_merchant_id'],
            'ssl_user_id'=>$ccauth['ssl_user_id'],
            'ssl_pin'=>$ccauth['ssl_pin'],
            'ssl_transaction_type'=>'CCRETURN',
            'ssl_test_mode'=>$cclink['test_mode'],
            'ssl_show_form'=>'false',
            'ssl_result_format'=>'ASCII',
            'ssl_txn_id'=>$txn['cc_txn_id'],
            'ssl_amount'=>$txn['amount']
        );
        logWrite(array('transid'=>$txn['transid'], $ccreturn));
        $args = "";
        foreach($ccreturn as $key => $value) {
            if($args != "") { $args .= "&"; }
            $args.="$key=$value";
        }

        $url = "https://".$cclink['host'].$cclink['site'];
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $args);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);

        $resp_array = array();
        $response = curl_exec($ch);
        $error_string = curl_error($ch);

        $response_lines = preg_split("/\n/", $response);
        foreach($response_lines as $line) {
        $line_array = preg_split("/=/", $line);
            if($line_array[1]!="") { $resp_array[$line_array[0]]=$line_array[1]; }
        }

        $log_keys = array(
            "ssl_result",
            "ssl_result_message",
            "ssl_amount",
            "ssl_txn_id",
            "ssl_txn_time",
            "ssl_approval_code",
            "ssl_status",
            "errorName",
            "errorMessage"
        );

        $db_keys = array(
            "ssl_result_message",
            "ssl_amount",
            "ssl_txn_id",
            "ssl_txn_time",
            "ssl_approval_code",
            "ssl_status",
            "ssl_card_number",
            "ssl_description"
        );

        $log_resp = array_intersect_key($resp_array, array_flip($log_keys));
        $db_resp = array_intersect_key($resp_array, array_flip($db_keys));

        if(isset($resp_array['ssl_result']) and ($resp_array['ssl_result'] == 0)) {
            $result='success';
            $reason = $resp_array['ssl_result_message'];
            $dbUpdateQ = "INSERT INTO payments (transid, type, category, description, amount, time, cc, cc_txn_id, cc_approval_code, txn_time) VALUES"
                . " ("
                . $txn['transid'] . ", 'return', 'reg', 'Automated Registration Return'"
                . ", '" . $db_resp['ssl_amount'] . "', 'current_timestamp()'"
                . ", '" . $db_resp['ssl_card_number'] . "', '"
                . $db_resp['ssl_txn_id'] . "', '"
                . $db_resp['ssl_approval_code'] . "', '"
                . $db_resp['ssl_txn_time'] . "');";
            dbInsert($dbUpdateQ);
        echo $dbUpdateQ;
        } else {
            $result='failed';
            $reason=$resp_array['errorMessage'];
        }

        logWrite(array('transid'=>$txn['transid'], $log_resp));
    }

    $badgeR = dbSafeQuery($badgeQ, 'i', array($txn['transid']));
    while($badge = fetch_safe_array($badgeR)) {
        $regid = $badge[2];
        if($result=='success') {
            $badgeUpdate = "UPDATE reg SET memId=" . $memTypes['Refunded'] . " WHERE id=$regid;";
            dbQuery($badgeUpdate);
        }

        for($i = 0 ; $i < count($badge); $i++) {
            printf("\"%s\",", $badge[$i]);
        }
        echo "$result,$reason\n";
    }
}
