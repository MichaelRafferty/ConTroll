<?php
global $db_ini;

require_once "../lib/base.php";

$check_auth = google_init("ajax");
$perm = "reg_admin";

$response = array("post" => $_POST, "get" => $_GET, "perm"=>$perm);

if ($check_auth == false || !checkAuth($check_auth['sub'], $perm)) {
    $response['error'] = 'Authentication Failed';
    $response['error'] = 'Authentication Failed';
    ajaxSuccess($response);
    exit();
}

//var_error_log($_POST);

if (!(array_key_exists('action', $_POST) && array_key_exists('rules', $_POST))) {
    $response['error'] = 'Argument Error';
    ajaxSuccess($response);
    exit();
}
$action=$_POST['action'];
try {
    $rules = json_decode($_POST['rules'], true, 512, JSON_THROW_ON_ERROR);
} catch (Exception $e) {
    $msg = 'Caught exception on json_decode: ' . $e->getMessage() . PHP_EOL . 'JSON error: ' . json_last_error_msg() . PHP_EOL;
    $response['error'] = $msg;
    error_log($msg);
    ajaxSuccess($response);
    exit();
}

// loop over the rules, updating the data and deleting the rule steps as needed
$dR = <<<EOS
DELETE FROM memRules
WHERE name = ?;
EOS;
$dRI = <<<EOS
DELETE FROM memRuleSteps
WHERE name = ?;
EOS;
$dRIsingle = <<<EOS
DELETE FROM memRuleSteps
WHERE name = ? and step = ?;
EOS;

$numdel = 0;
$numupd = 0;
$numins = 0;
$ruleItems = [];
foreach ($rules as $name => $rule) {
    if (array_key_exists('to_delete', $rule) && $rule['to_delete'] == 1) {
        $numdel += dbSafeCmd($dRI, 's', array($rule['origName']));
        $numdel += dbSafeCmd($dR, 's', array($rule['origName']));
    } else {
        if (array_key_exist('ruleet', $rule))
            $ruleItems = $rule['ruleset'];
        foreach ($ruleItems as $ruleItem) {
            if (array_key_exists('to_delete', $ruleItem) && $ruleItem['to_delete'] == 1) {
                $numdel += dbSafeCmd($dRIsingle, 'si', array($ruleItem['origName'], $ruleItem['origStep']));
            }
        }
    }
}

$uR = <<<EOS
UPDATE memRules
SET name = ?, optionName = ?, description = ?, typeList = ?, catList = ?, ageList = ?, memList = ?
WHERE name = ?;
EOS;
$uRI = <<<EOS
UPDATE memRuleSteps 
SET name = ?, step = ?, ruleType = ?, applyTo = ?, typeList = ?, catList = ?, ageList = ?, memList = ?
WHERE name = ? AND step = ?;
EOS;

// ok, all the deletes are now done, do the updates next
foreach ($rules as $name => $rule) {
    if (array_key_exists('to_delete', $rule) && $rule['to_delete'] == 1)
        continue; // don't update delete lines
    if (is_numeric($rule['origName']) && $rule['origName'] < 0)
        continue; // don't update inserts

    // we have a row we can update
    $optionName = '';
    if (array_key_exists('optionName', $rule) && $rule['optionName'] != null)
        $optionName = $rule['optionName'];

    $description = '';
    if (array_key_exists('description', $rule) && $rule['description'] != null)
        $description = $rule['description'];
    
    $typeList = null;
    if (array_key_exists('typeList', $rule) && $rule['typeList'] != '')
        $typeList = $rule['typeList'];

    $catList = null;
    if (array_key_exists('catList', $rule) && $rule['catList'] != '')
        $catList = $rule['catList'];

    $ageList = null;
    if (array_key_exists('ageList', $rule) && $rule['ageList'] != '')
        $ageList = $rule['ageList'];

    $memList = null;
    if (array_key_exists('memList', $rule) && $rule['memList'] != '')
        $memList = $rule['memList'];
    
    $numupd += dbSafeCmd($uR, 'ssssssss', array($rule['name'], $optionName, $description, $typeList, $catList, $ageList, $memList, $rule['origName']));
    $ruleItems = [];
    if (array_key_exist('ruleet', $rule))
        $ruleItems = $rule['ruleset'];
    $ruleName = $rule['name'];
    foreach ($ruleItems as $ruleItem) {
        if (array_key_exists('to_delete', $ruleItem) && $ruleItem['to_delete'] == 1)
            continue; // don't update delete lines
        if ($ruleItem['origStep'] < 0) // don't update insert items
            continue;

        $typeList = null;
        if (array_key_exists('typeList', $ruleItem) && $ruleItem['typeList'] != '')
            $typeList = $ruleItem['typeList'];

        $catList = null;
        if (array_key_exists('catList', $ruleItem) && $ruleItem['catList'] != '')
            $catList = $ruleItem['catList'];

        $ageList = null;
        if (array_key_exists('ageList', $ruleItem) && $ruleItem['ageList'] != '')
            $ageList = $ruleItem['ageList'];

        $memList = null;
        if (array_key_exists('memList', $ruleItem) && $ruleItem['memList'] != '')
            $memList = $ruleItem['memList'];
        
        $numupd += dbSafeCmd($uRI, 'sisssssssi', array($ruleName, $ruleItem['step'], $ruleItem['ruleType'], $ruleItem['applyTo'],
            $typeList, $catList, $ageList, $memList, $rule['name'], $ruleItem['origStep']));
    }
}

// last all the inserts
$iR = <<<EOS
INSERT into memRules(name, optionName, description, typeList, catList, ageList, memList) 
VALUES (?, ? ,?, ?, ?, ?, ?);
EOS;
$iRI = <<<EOS
INSERT into memRuleSteps(name, step, ruleType, applyTo, typeList, catList, ageList, memList)   
VALUES (?, ? ,?, ?, ?, ?, ?, ?);
EOS;

foreach ($rules as $name => $rule) {
    if (array_key_exists('to_delete', $rule) && $rule['to_delete'] == 1)
        continue; // don't update delete lines
    if (is_numeric($rule['origName']) && $rule['origName'] < 0) {
        // do insert
        $optionName = '';
        if (array_key_exists('optionName', $rule) && $rule['optionName'] != null)
            $optionName = $rule['optionName'];

        $description = '';
        if (array_key_exists('description', $rule) && $rule['description'] != null)
            $description = $rule['description'];

        $typeList = null;
        if (array_key_exists('typeList', $rule) && $rule['typeList'] != '')
            $typeList = $rule['typeList'];

        $catList = null;
        if (array_key_exists('catList', $rule) && $rule['catList'] != '')
            $catList = $rule['catList'];

        $ageList = null;
        if (array_key_exists('ageList', $rule) && $rule['ageList'] != '')
            $ageList = $rule['ageList'];

        $memList = null;
        if (array_key_exists('memList', $rule) && $rule['memList'] != '')
            $memList = $rule['memList'];

        $inskey = dbSafeInsert($iR, 'sssssss', array($rule['name'], $optionName, $description,
            $typeList, $catList,  $ageList, $memList));
        if ($inskey)
            $numins++;
        }

    $ruleItems = [];
    if (array_key_exist('ruleet', $rule))
        $ruleItems = $rule['ruleset'];
    $ruleName = $rule['name'];
    foreach ($ruleItems as $ruleItem) {
        if (array_key_exists('to_delete', $ruleItem) && $ruleItem['to_delete'] == 1)
            continue; // don't update delete lines
        if ($ruleItem['origStep'] < 0) {
            // new row do the insert
            $typeList = null;
            if (array_key_exists('typeList', $ruleItem) && $ruleItem['typeList'] != '')
                $typeList = $ruleItem['typeList'];

            $catList = null;
            if (array_key_exists('catList', $ruleItem) && $ruleItem['catList'] != '')
                $catList = $ruleItem['catList'];

            $ageList = null;
            if (array_key_exists('ageList', $ruleItem) && $ruleItem['ageList'] != '')
                $ageList = $ruleItem['ageList'];

            $memList = null;
            if (array_key_exists('memList', $ruleItem) && $ruleItem['memList'] != '')
                $memList = $ruleItem['memList'];
            
            $inskey = dbSafeInsert($iRI, 'sissssss', array ($ruleName, $ruleItem['step'], $ruleItem['ruleType'], $ruleItem['applyTo'],
                $typeList, $catList, $ageList, $memList));
            if ($inskey)
                $numins++;
        }
    }
}

$response['success'] = 'All rules updated';
$response['numins'] = $numins;
$response['numupd'] = $numupd;
$response['numdel'] = $numdel;
ajaxSuccess($response);
?>
