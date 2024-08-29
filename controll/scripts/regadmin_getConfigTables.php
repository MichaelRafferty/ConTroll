<?php
global $db_ini;

require_once "../lib/base.php";
require_once "../../lib/memRules.php";
$check_auth = google_init("ajax");
$perm = "reg_admin";

$response = array("post" => $_POST, "get" => $_GET, "perm"=>$perm);

if($check_auth == false || !checkAuth($check_auth['sub'], $perm)) {
    $response['error'] = "Authentication Failed";
    ajaxSuccess($response);
    exit();
}

if (!isset($_POST) || !isset($_POST['ajax_request_action']) || !isset($_POST['tablename'])
    || !isset($_POST['indexcol'])) {
    $response['error'] = 'Invalid Parameters';
    ajaxSuccess($response);
    exit();
}

$tablename = $_POST['tablename'];
$indexcol = $_POST['indexcol'];
$action = $_POST['ajax_request_action'];

$con=get_con();
$conid= $con['id'];
$nextconid = $conid + 1;

switch ($tablename) {
    case 'policy':
        $policySQL = <<<EOS
        SELECT p.policy, p.prompt, p.description, p.sortOrder, p.required, p.defaultValue, p.createDate, p.updateDate, p.updateBy, p.active,
               p.policy AS policyKey, COUNT(*) AS uses
        FROM policies p
        LEFT OUTER JOIN memberPolicies mP ON p.policy = mP.policy
        GROUP BY p.policy, p.prompt, p.description, p.sortOrder, p.required, p.defaultValue, p.createDate, p.updateDate, p.updateBy, p.active
        ORDER BY sortorder, policy;
        EOS;

        $result = dbQuery($policySQL);
        $policies = array ();
        while ($row = $result->fetch_assoc()) {
            array_push($policies, $row);
        }
        $result->free();
        $response['policies'] = $policies;
        break;

    case 'interests':
        $interestsSQL = <<<EOS
SELECT i.interest, i.description, i.notifyList, i.sortOrder, i.createDate, i.updateDate, i.updateBy, i.active, i.csv,
       i.interest AS interestKey, COUNT(*) AS uses
FROM interests i
LEFT OUTER JOIN memberInterests mI ON i.interest = mI.interest
GROUP BY  i.interest, i.description, i.notifyList, i.sortOrder, i.createDate, i.updateDate, i.updateBy, i.active, i.csv
ORDER BY i.sortorder, i.interest;
EOS;

        $result = dbQuery($interestsSQL);
        $interests = array();
        while ($row = $result->fetch_assoc()) {
            array_push($interests, $row);
        }
        $result->free();
        $response['interests'] = $interests;
        break;

    case 'customText':
        $customTextSQL = <<<EOS
SELECT ROW_NUMBER() OVER (ORDER BY appName, appPage, appSection, txtItem) AS rownum,
    appName, appPage, appSection, txtItem, contents
FROM controllTxtItems
ORDER BY appName, appPage, appSection, txtItem
EOS;

        $result = dbQuery($customTextSQL);
        $customText = array();
        while ($row = $result->fetch_assoc()) {
            array_push($customText, $row);
        }
        $result->free();
        $response['customText'] = $customText;
        break;

    case 'rules':
        $data = getRulesData($conid);
        $response['ageList'] = $data['ageList'];
        $response['ageListIdx'] = $data['ageListIdx'];
        $response['memTypes'] = $data['memTypes'];
        $response['memCategories'] = $data['memCategories'];
        $response['memList'] = $data['memList'];
        $response['memListIdx'] = $data['memListIdx'];
        $response['memRules'] = $data['memRules'];
        // now the memList items for filling in that field
        $memSQL = <<<EOS
SELECT *
FROM memList
WHERE conid IN (?, ?)
ORDER BY sort_order;
EOS;
        $result = dbSafeQuery($memSQL, 'ii', array($conid, $nextconid));
        $memListItems = array();
        $memListIdx = [];
        while ($row = $result->fetch_assoc()) {
            array_push($memListItems, $row);
            $memListIdx[$row['id']] = $row;
        }
        $result->free();
        $response['memListFull'] = $memListItems;
        $response['memListFullIdx'] = $memListIdx;
        break;

    default:
        $response['error'] = 'Invalid table';
}

ajaxSuccess($response);
?>
