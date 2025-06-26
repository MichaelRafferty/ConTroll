<?php
global $db_ini;

// build the missing current year and the required new year items for ConTroll setup
// registration items next year
//      first check for conlist entries and build conid + 1 if not found
//      second build the agelist for the new year if empty
//      build yearahead and volunteer rollover memlist entries for the new year if empty
//
// build membership rules for this year if missing and last year exists
// build exhibits config for this year if missing and last year exists

require_once "../lib/base.php";
require_once '../lib/exhibitorsCheckOrBuild.php';

$check_auth = google_init("ajax");
$perm = "admin";

$response = array("post" => $_POST, "get" => $_GET, "perm"=>$perm);

if($check_auth == false || !checkAuth($check_auth['sub'], $perm)) {
    $response['error'] = "Authentication Failed";
    ajaxSuccess($response);
    exit();
}

if ((!array_key_exists('action', $_POST)) && $_POST['action'] != 'build') {
    $response['error'] = 'Parameter Error';
    ajaxSuccess($response);
    exit();
}

$con=get_conf('con');
$conid= $con['id'];
$nextConid = $conid + 1;
$response['conid'] = $conid;

// check if conlist exists for conid + 1
$conlQ = <<<EOS
SELECT id, name, label, startdate, enddate
FROM conlist
WHERE id in (?, ?);
EOS;

$conlR = dbSafeQuery($conlQ, 'ii', array($conid, $nextConid));
if ($conlR === false) {
    $response['error'] = 'Con List Query Failed';
    ajaxSuccess($response);
    exit();
}
$thisYear = null;
$nextYear = null;
while ($row = $conlR->fetch_assoc()) {
    if ($row['id'] == $conid)
        $thisYear = $row;
    else
        $nextYear = $row;
}
$conlR->free();
if ($thisYear == null) {
    $response['error'] = "$conid is not yet set up, cannot set up next year, please use Current Convention Setup to set up this year";
    ajaxSuccess($response);
    exit();
}

$message = '';
if ($nextYear == null) {
    // need to create the next year conlist entry
    $insQ = <<<EOS
INSERT INTO conlist(id, name, label, startdate, enddate)
VALUES (?,?,?,?,?);
EOS;
    $typestr = 'issss';
    $valArray = array($nextConid,
                      str_replace(strval($conid), strval($nextConid), $thisYear['name']),
                      str_replace(strval($conid), strval($nextConid), $thisYear['label']),
                      startEndDateToNextYear($thisYear['startdate']),
                      startEndDateToNextYear($thisYear['enddate'])
    );
    $newId = dbSafeInsert($insQ, $typestr, $valArray);
    if ($newId === false) {
        $response['error'] = "Insert of $nextConid conlist entry failed";
        ajaxSuccess($response);
        exit();
    }

    $message .= "Added conlist entry for $nextConid as $newId<br/>\n";
}

// build the agelist if needed
$checkAgeQ = <<<EOS
SELECT count(*) FROM ageList WHERE conid = ?;
EOS;
$checkAgeR = dbSafeQuery($checkAgeQ, 'i', array($nextConid));
$ageCount = $checkAgeR->fetch_row()[0];
$checkAgeR->free();
if ($ageCount == 0) {
    $ageInsQ = <<<EOS
INSERT INTO ageList(conid, ageType, label, shortname, sortorder, badgeFlag)
SELECT ?, ageType, label, shortname, sortorder, badgeFlag
FROM ageList
WHERE conid = ?;
EOS;
    $numRows = dbSafeCmd($ageInsQ, 'ii', array($nextConid, $conid));
    $message .= "$numRows ageList entries inserted for $nextConid<br/>\n";
}

// build the memList entries for this year (non uearahead and non rollover) and year + 1 for volunteer rollover and yearahead

// year + 1 volunteer rollover
$checkMLQ1 = <<<EOS
SELECT conid, sort_order, memCategory, memType, memAge, label, notes, price, startdate, enddate, atcon, online, glNum, glLabel
FROM memList
WHERE label = ? AND conid = ?;
EOS;
// year + 1 yearahead
$checkMLQ2 = <<<EOS
SELECT conid, sort_order, memCategory, memType, memAge, label, notes, price, startdate, enddate, atcon, online, glNum, glLabel
FROM memList
WHERE memCategory = ? AND conid = ?;
EOS;
// this year others (note startdate == enddate is for the pushed rollover types we don;t want to auto carry forward,
// as they might conflict with ones pushed by rollovers automatically.
$checkMLQ3 = <<<EOS
SELECT conid, sort_order, memCategory, memType, memAge, label, notes, price, startdate, enddate, atcon, online, glNum, glLabel
FROM memList
WHERE conid = ? AND startdate != enddate AND NOT (memCategory = 'yearahead' OR label = 'Rollover-volunteer');
EOS;
$insML = <<<EOS
INSERT INTO memList(conid, sort_order, memCategory, memType, memAge, label, notes, price, startdate, enddate, atcon, online, glNum, glLabel)
VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?);
EOS;

// next year rollover volunteer
$checkMLR = dbSafeQuery($checkMLQ1, 'si', array ('Rollover-volunteer', $nextConid));
$numFound = $checkMLR->num_rows;
$checkMLR->free();
if ($numFound == 0) {
    // get the current roes
    $rows = [];
    $getMLR = dbSafeQuery($checkMLQ1, 'si', array ('Rollover-volunteer', $conid));
    while ($row = $getMLR->fetch_assoc()) {
        $rows[] = $row;
    }
    $getMLR->free();

    // now insert the new ones
    $numRows = 0;
    foreach ($rows as $row) {
        $valueArr = array(
            $nextConid, $row['sort_order'], $row['memCategory'], $row['memType'], $row['memAge'], $row['label'], $row['notes'], $row['price'],
            startEndDateTimeToNextYear($row['startdate']), startEndDateTimeToNextYear($row['enddate']),
            $row['atcon'], $row['online'], $row['glNum'], $row['glLabel']
        );
        $numRows += dbSafeCmd($insML, 'iisssssdssssss', $valueArr);
    }
    $message .= "$numRows Rollover-volunteer memList entries added for $nextConid<br/>\n";
}

// next year yearahead
$checkMLR = dbSafeQuery($checkMLQ2, 'si', array('yearahead', $nextConid));
$numFound = $checkMLR->num_rows;
$checkMLR->free();
if ($numFound == 0) {
// get the current roes
    $rows = [];
    $getMLR = dbSafeQuery($checkMLQ2, 'si', array ('yearahead', $conid));
    while ($row = $getMLR->fetch_assoc()) {
        $rows[] = $row;
    }
    $getMLR->free();

    // now insert the new ones
    $numRows = 0;
    foreach ($rows as $row) {
        $valueArr = array(
            $nextConid, $row['sort_order'], $row['memCategory'], $row['memType'], $row['memAge'], $row['label'], $row['notes'], $row['price'],
            startEndDateTimeToNextYear($row['startdate']), startEndDateTimeToNextYear($row['enddate']),
            $row['atcon'], $row['online'], $row['glNum'], $row['glLabel']
        );
        $numRows += dbSafeCmd($insML, 'iisssssdssssss', $valueArr);
    }
    $message .= "$numRows yearahead memList entries added for $nextConid<br/>\n";
}

// this year other
$checkMLR = dbSafeQuery($checkMLQ3, 'i', array($conid));
$numFound = $checkMLR->num_rows;
$checkMLR->free();
if ($numFound == 0) {
    // get the current roes
    $rows = [];
    $getMLR = dbSafeQuery($checkMLQ3, 'i', array ($conid - 1));
    while ($row = $getMLR->fetch_assoc()) {
        $rows[] = $row;
    }
    $getMLR->free();

    // now insert the new ones
    $numRows = 0;
    foreach ($rows as $row) {
        $valueArr = array(
            $conid, $row['sort_order'], $row['memCategory'], $row['memType'], $row['memAge'], $row['label'], $row['notes'], $row['price'],
            startEndDateTimeToNextYear($row['startdate']), startEndDateTimeToNextYear($row['enddate']),
            $row['atcon'], $row['online'], $row['glNum'], $row['glLabel']
        );
        $numRows += dbSafeCmd($insML, 'iisssssdssssss', $valueArr);
    }
    $message .= "$numRows normal memList entries added for $conid<br/>\n";
}

$msg = updateRules($conid);
if (str_starts_with($msg, 'Error: ') ) {
    error_log("updateRules returned $msg");
}
$message .= "$msg";
// check if the current exhibits year exists and if not, try to build it from last year
$msg = exhibitorCheckOrBuildYear($conid);
if (str_starts_with($msg, 'Error: ') ) {
    error_log("exhibitorCheckOrBuildYear returned $msg");
}
$message .= "$msg<br/>\n<br/>NOTE: Check the current and next year's configuration in registration, rules, and exhibits for any issues in performing the auto create.";
$response['success'] = $message;
ajaxSuccess($response);

function updateRules($conid) {
    $msg = '';

    $getRQ = <<<EOS
SELECT name, conid, optionName, description, typeList, catList, ageList, memList
FROM memRules
WHERE conid = ?;
EOS;
    $getRIQ = <<<EOS
SELECT name, conid, step, ruleType, applyTo, typeList, catList, ageList, memList
FROM memRuleSteps
WHERE conid = ?;
EOS;
    $insR = <<<EOS
INSERT INTO memRules(name, conid, optionName, description, typeList, catList, ageList, memList)
VALUES (?,?,?,?,?,?,?,?);
EOS;
    $insRS = <<<EOS
INSERT INTO memRuleSteps(name, conid, step, ruleType, applyTo, typeList, catList, ageList, memList)
VALUES (?,?,?,?,?,?,?,?,?);
EOS;
    // check if rules already created
    $ruleR = dbSafeQuery($getRQ, 'i', array($conid));
    if ($ruleR === false) {
        $msg = 'Error retrieving rules to update<br/>\n';
        return $msg;
    }
    if ($ruleR->num_rows > 0) {
        $msg = "Update of membership rules skipped, $conid already has " . $ruleR->num_rows . " rules.";
        $ruleR->free();
        return $msg;
    }
    $ruleR->free();
    $ruleR = dbSafeQuery($getRQ, 'i', array($conid - 1));

    $rules = [];
    while ($rule = $ruleR->fetch_assoc()) {
        $rules[] = $rule;
    }
    $ruleR->free();

    $stepR = dbSafeQuery($getRIQ, 'i', array($conid - 1));
    if ($stepR === false) {
        $msg = 'Error retrieving rule items (steps) to update<br/>\n';
        return $msg;
    }

    // get the steps to update
    $steps = [];
    while ($step = $stepR->fetch_assoc()) {
        $steps[] = $step;
    }
    $stepR->free();

    // loop over rules updating memList
    $numRulesUpd = 0;
    foreach ($rules as $rule) {
        if (array_key_exists('memList', $rule) && $rule['memList'] != null)
            $rule['memList'] = updateRuleMemlist($conid, $rule['memList']);
        else
            $rule['memList'] = null;

        $valArray = array($rule['name'], $conid, $rule['optionName'], $rule['description'], 
                          $rule['typeList'], $rule['catList'], $rule['ageList'], $rule['memList']);
        $newRuleId = dbSafeInsert($insR, 'sissssss', $valArray);
        if ($newRuleId !== false)
            $numRulesUpd ++;
    }

    // loop over the steps updating memList
    $numStepsUpd = 0;
    foreach ($steps as $step) {
        if (array_key_exists('memList', $step) && $step['memList'] != null)
            $step['memList'] = updateRuleMemlist($conid, $step['memList']);
        else
            $step['memList'] = null;

        $valArray = array($step['name'], $conid, $step['step'], $step['ruleType'], $step['applyTo'],
                          $step['typeList'], $step['catList'], $step['ageList'], $step['memList']);
        $newStepId = dbSafeInsert($insRS, 'siissssss', $valArray);
        if ($newStepId !== false)
            $numStepsUpd++;
    }

    $msg = "$numRulesUpd membership rules created<br/>\n$numStepsUpd membership rule steps created<br/>\n";
    return $msg;
}

function updateRuleMemList($conid, $list) {
    $listItems = explode(',', $list);
    $newListItems = [];

    $getQ = <<<EOS
SELECT startdate, enddate
FROM memList
WHERE id = ?;
EOS;
    $getRQ = <<<EOS
SELECT mn.id
FROM memList mn
JOIN memList mp ON (mp.sort_order = mn.sort_order AND mp.memCategory = mn.memCategory AND mp.memType = mn.memType AND mp.memAge = mn.memAge
    AND mp.label = mn.label AND mp.price = mn.price AND mp.atcon = mn.atcon AND mp.online = mn.online)
WHERE mn.startdate = ? AND mn.enddate = ? AND mp.id = ?;
EOS;

    // loop over list looking up new item to replace old one
    foreach ($listItems as $item) {
        $mR = dbSafeQuery($getQ,'i', array($item));
        if ($mR === false || $mR->num_rows != 1) {
            error_log("bad memList query in updateRuleMemList");
            continue;
        }
        [$startdate, $enddate] = $mR->fetch_row();
        $mR->free();
        $startdate = startEndDateTimeToNextYear($startdate);
        $enddate = startEndDateTimeToNextYear($enddate);

        $mR = dbSafeQuery($getRQ, 'ssi', array($startdate, $enddate, $item));
        if ($mR === false || $mR->num_rows != 1) {
            error_log('bad memList replacement query in updateRuleMemList');
            continue;
        }
        $newItem = $mR->fetch_row()[0];
        $mR->free();
        $newListItems[] = $newItem;
    }

    if (count($newListItems) == 0)
        return null;

    return join(',', $newListItems);
    }
