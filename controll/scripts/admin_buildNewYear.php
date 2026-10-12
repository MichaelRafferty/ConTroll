<?php
// build the missing current year and the required new year items for ConTroll setup
// validation:
//  if conlist for this year doesn't exist:
//      if one for last year, build this years from that, if not give an error
// registration items for this year (new year)
//      first build the agelist for this year if empty (from prior year)
//      second build the price break dates for this year if empty and prior year exists
// registration items next year (new year + 1)
//      first check for conlist entries and build conid + 1 if not found
//      second build the agelist for the new year if empty
//      build yearahead and volunteer rollover memlist entries for the next year if empty
//
// build membership rules for this year if missing and last year exists
// build exhibits config for this year if missing and last year exists

require_once "../lib/base.php";
require_once '../lib/exhibitorsCheckOrBuild.php';
require_once '../lib/sessionAuth.php';

// use common global Ajax return functions
global $returnAjaxErrors, $return500errors;
$returnAjaxErrors = true;
$return500errors = true;

$perm = 'admin';
$response = array ('post' => $_POST, 'get' => $_GET, 'perm' => $perm);
$authToken = new authToken('script');
$response['tokenStatus'] = $authToken->checkToken();
if (!$authToken->isLoggedIn() || !$authToken->checkAuth($perm)) {
    $response['error'] = 'Authentication Failed';
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
$lastConid = $conid - 1;
$nextConid = $conid + 1;
$response['conid'] = $conid;

// check if conlist exists for conid + 1
$conlQ = <<<EOS
SELECT id, name, label, startdate, enddate
FROM conlist
WHERE id between ? and ?
ORDER BY id;
EOS;

$conlR = dbSafeQuery($conlQ, 'ii', array($lastConid, $nextConid));
if ($conlR === false) {
    $response['error'] = 'Con List Query Failed';
    ajaxSuccess($response);
    exit();
}
$lastYear = null;
$thisYear = null;
$nextYear = null;
while ($row = $conlR->fetch_assoc()) {
    switch ($row['id']) {
        case $lastConid:
            $lastYear = $row;
            break;
        case $conid:
            $thisYear = $row;
            break;
        case $nextConid:
            $nextYear = $row;
            break;
        default:
            $response['error'] = "An invalid conid was returned for the three year period of $lastConid through $nextConid of " . $row['id'] .
                "<br/>There is a data error in the ConTroll database, please contact the system administrator";
            ajaxSuccess($response);
            exit();
    }
}
$conlR->free();
$message = '';
    $insQ = <<<EOS
INSERT INTO conlist(id, name, label, startdate, enddate)
VALUES (?,?,?,?,?);
EOS;
if ($thisYear == null) {
    if ($lastYear == null) {
        $response['error'] = "$conid is not yet set up, cannot set up next year, please use Current Convention Setup to set up this year";
        ajaxSuccess($response);
        exit();
    }
    // ok build this year from last year
    // need to create the next year conlist entry
    $typestr = 'issss';
    $valArray = array($nextConid,
        str_replace(strval($lastConid), strval($conid), $lastYear['name']),
        str_replace(strval($lastConid), strval($conid), $lastYear['label']),
        startEndDateToNextYear($lastYear['startdate']),
        startEndDateToNextYear($lastYear['enddate'])
    );
    $newId = dbSafeInsert($insQ, $typestr, $valArray);
    if ($newId === false) {
        $response['error'] = "Insert of $nextConid conlist entry failed";
        ajaxSuccess($response);
        exit();
    }

    $message .= "Added conlist entry for $nextConid as $newId<br/>\n";
}


// ok, we have the conid, and the rest will run anyway on a re-do, so do the history table truncates now
$maxHistoryYears = getConfValue('con', 'maxHistoryYears', 99999);
$maxPerinfoHistoryYears = getConfValue('con', 'maxPerinfoHistoryYears', $maxHistoryYears);
$maxRegHistoryYears = getConfValue('con', 'maxRegHistoryYears', $maxHistoryYears);
$maxExhibitorHistoryYears = getConfValue('con', 'maxExhibitorHistoryYears', $maxHistoryYears);
$maxArtItemsHistoryYears = getConfValue('con', 'maxArtItemsHistoryYears', $maxHistoryYears);

if ($maxRegHistoryYears < 99999) {
    // truncate the reg history
    $delQ = <<<EOS
DELETE FROM regHistory
WHERE create_date < DATE_SUB(NOW(), INTERVAL ? YEAR) OR change_date < DATE_SUB(NOW(), INTERVAL ? YEAR);
EOS;
    $numRdel = dbSafeCmd($delQ, 'ii', array($maxRegHistoryYears, $maxRegHistoryYears));
    $message .= "$numRdel regHistory rows deleted for delete older than $maxRegHistoryYears year(s).<br/>\n";
}

if ($maxArtItemsHistoryYears < 99999) {
    // truncate the artItems history
    $delQ = <<<EOS
DELETE FROM artItemsHistory
WHERE historyDate < DATE_SUB(NOW(), INTERVAL ? YEAR);
EOS;
    $numRdel = dbSafeCmd($delQ, 'i', array($maxArtItemsHistoryYears));
    $message .= "$numRdel artItemsHistory rows deleted for delete older than $maxArtItemsHistoryYears year(s).<br/>\n";
}

if ($maxExhibitorHistoryYears < 99999) {
    // truncate the exhibitors history
    $delQ = <<<EOS
DELETE FROM exhibitorsHistory
WHERE historyDate < DATE_SUB(NOW(), INTERVAL ? YEAR);
EOS;
    $numRdel = dbSafeCmd($delQ, 'i', array($maxExhibitorHistoryYears));
    $message .= "$numRdel exhibitorsHistory rows deleted for delete older than $maxExhibitorHistoryYears year(s).<br/>\n";
}

if ($maxPerinfoHistoryYears < 99999) {
    // truncate the perinfo history
    $delQ = <<<EOS
DELETE FROM perinfoHistory
WHERE historyDate < DATE_SUB(NOW(), INTERVAL ? YEAR);
EOS;
    $numRdel = dbSafeCmd($delQ, 'i', array($maxPerinfoHistoryYears));
    $message .= "$numRdel perinfoHistory rows deleted for delete older than $maxPerinfoHistoryYears year(s).<br/>\n";
}

if ($nextYear == null) {
    // need to create the next year conlist entry
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

// build the taxList for this if needed and for next
for ($year = $conid; $year <= $nextConid; $year++) {
    /// taxlist
    $checkTaxQ = <<<EOS
SELECT COUNT(*) FROM taxList WHERE conid = ?;
EOS;
    $checkTaxR = dbSafeQuery($checkTaxQ, 'i', array ($year));
    $taxCount = $checkTaxR->fetch_row()[0];
    $checkTaxR->free();
    if ($taxCount == 0) {
        $taxInsQ = <<<EOS
INSERT INTO taxList(conid, taxField, label, rate, active, lastUpdate, updatedBy)
SELECT ?, taxField, label, rate, active, NOW(), NULL
FROM taxList
WHERE conid = ?;
EOS;
        $numRows = dbSafeCmd($taxInsQ, 'ii', array ($year, $year - 1));
        $message .= "$numRows taxList entries inserted for $year<br/>\n";
        // build taxable items
        $taxInsQ = <<<EOS
INSERT INTO taxItems(conid, item, taxField, taxable, lastUpdate, updatedBy, sortorder)
SELECT ?, item, taxField, taxable, NOW(), NULL, sortorder
FROM taxItems
WHERE conid = ?;
EOS;
        $numRows = dbSafeCmd($taxInsQ, 'ii', array ($year, $year - 1));
        $message .= "$numRows taxItems entries inserted for $year<br/>\n";
    }

    // build the agelist if needed
    $checkAgeQ = <<<EOS
    SELECT COUNT(*) FROM ageList WHERE conid = ?;
    EOS;
    $checkAgeR = dbSafeQuery($checkAgeQ, 'i', array ($year));
    $ageCount = $checkAgeR->fetch_row()[0];
    $checkAgeR->free();
    if ($ageCount == 0) {
        $ageInsQ = <<<EOS
    INSERT INTO ageList(conid, ageType, label, shortname, sortorder, badgeFlag, verify)
    SELECT ?, ageType, label, shortname, sortorder, badgeFlag, verify
    FROM ageList
    WHERE conid = ?;
    EOS;
        $numRows = dbSafeCmd($ageInsQ, 'ii', array ($year, $year-1));
        $message .= "$numRows ageList entries inserted for $nextConid<br/>\n";
    }
}

// build the memList entries for this year (non yearahead and non rollover) and year + 1 for volunteer rollover and yearahead

// year + 1 volunteer rollover
$checkMLQ1 = <<<EOS
SELECT id, conid, sort_order, memCategory, memType, memAge, label, cartDesc, notes, rptGrouping, badgeLabel, atcon, online
FROM memList
WHERE label = ? AND conid = ?;
EOS;
// year + 1 yearahead
$checkMLQ2 = <<<EOS
SELECT id, conid, sort_order, memCategory, memType, memAge, label, cartDesc, notes, rptGrouping, badgeLabel, atcon, online
FROM memList
WHERE memCategory = ? AND conid = ?;
EOS;
// this year others (notes 'auto created by rollover' is for the pushed rollover types we don't want to auto carry forward,
// as they might conflict with ones pushed by rollovers automatically.
$checkMLQ3 = <<<EOS
SELECT id, conid, sort_order, memCategory, memType, memAge, label, cartDesc, notes, rptGrouping, badgeLabel, atcon, online
FROM memList
WHERE conid = ? AND substring(IFNULL(notes, ''), 1, 24) != 'Auto created by rollover' AND NOT (memCategory = 'yearahead' OR label = 'Rollover-volunteer');
EOS;
$insML = <<<EOS
INSERT INTO memList(conid, sort_order, memCategory, memType, memAge, label, cartDesc, notes, rptGrouping, badgeLabel, atcon, online)
VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?);
EOS;

// memid Map maps existing memId's to new memId's for build price breaks.
$memIdMap = [];

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
        //conid, sort_order, memCategory, memType, memAge, label, cartDesc, notes, rptGrouping, badgeLabel, atcon, online
        $valueArr = array(
            $nextConid, $row['sort_order'], $row['memCategory'], $row['memType'], $row['memAge'], $row['label'], $row['cartDesc'],
            $row['notes'], $row['rptGrouping'], $row['badgeLabel'], $row['atcon'], $row['online']
        );
        $newMemId = dbSafeInsert($insML, 'iisssssdssss', $valueArr);
        if ($newMemId !== false) {
            $memIdMap[$row['id']] = $newMemId;
            $numRows++;
        } else {
            $message .= 'Failed to insert rollover-volunteer memList entry for ' . $row['id'] . "<br/>\n";
        }
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
            $nextConid, $row['sort_order'], $row['memCategory'], $row['memType'], $row['memAge'], $row['label'], $row['cartDesc'],
            $row['notes'], $row['rptGrouping'], $row['badgeLabel'], $row['atcon'], $row['online']
        );
        $newMemId = dbSafeInsert($insML, 'iisssssdssss', $valueArr);
        if ($newMemId !== false) {
            $memIdMap[$row['id']] = $newMemId;
            $numRows++;
        } else {
            $message .= "Failed to insert yearahead memList entry for " . $row['id'] . "<br/>\n";
        }
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
        $valueArr = array (
            $conid, $row['sort_order'], $row['memCategory'], $row['memType'], $row['memAge'], $row['label'], $row['cartDesc'],
            $row['notes'], $row['rptGrouping'], $row['badgeLabel'], $row['atcon'], $row['online']
        );
        $newMemId = dbSafeInsert($insML, 'iisssssdssss', $valueArr);
        if ($newMemId !== false) {
            $memIdMap[$row['id']] = $newMemId;
            $numRows++;
        } else {
            $message .= 'Failed to insert current year memList entry for ' . $row['id'] . "<br/>\n";
        }
    }
    $message .= "$numRows normal memLis entries added for $conid<br/>\n";
}

// build the price break list (dateRanges) for this year from last year
$checkDRQ = <<<EOS
SELECT id, conid, nickname, startdate, enddate, nickname, sortorder
FROM  dateRanges
WHERE conid = ?;
EOS;

$checkDR = dbSafeQuery($checkDRQ, 'i', array($conid));
$numFound = $checkDR->num_rows;
$checkDR->free();

if ($numFound == 0) {
    $numRows = 0;
    $insDRQ = <<<EOS
INSERT INTO dateRanges(conid, nickname, startdate, enddate, sortorder)
VALUES (?, ?, ?, ?, ?);
EOS;
    $dr = [];
    $getDR = dbSafeQuery($checkDRQ, 'i', array($lastConid));
    while ($row = $getDR->fetch_assoc()) {
        $dr[] = $row;
    }
    $getDR->free();
    // now loop over them updating start and end dates
    foreach ($dr AS $row) {
        $newStartDate =  startEndDateToNextYear($row['startdate']);
        $newEndDate = startEndDateToNextYear($row['enddate']);
        $numRows += dbSafeCmd($insDRQ, 'isssi', array($conid, $row['nickname'], $newStartDate, $newEndDate, $row['sortorder']));
    }

    $message .= "$numRows Price Break Date Range entries inserted for $year<br/>\n";
}

// ok, memList now created, need to do everything in the memIdMapfor the memListDates entries
if (count($memIdMap) > 0) {
    $mldQ = <<<EOS
SELECT id, memId, glNum, price, startdate, enddate, nickName
FROM memListDates
WHERE memId = ?;
EOS;
    $insMLD = <<<EOS
INSERT INTO memListDates(memId, glNum, price, startdate, enddate, nickName)
VALUES (?, ?, ?, ?, ?, ?);
EOS;

    $numRows = 0;
    // the map has the old to new memId's we need to copy over
    foreach ($memIdMap AS $oldMemId => $newMemId) {
        // get the memListdates for this memId and make new ones
        $getMLD = dbSafeQuery($mldQ, 'i', array($oldMemId));
        while ($row = $getMLD->fetch_assoc()) {
            $newStartDate = startEndDateToNextYear($row['startdate']);
            $newEndDate = startEndDateToNextYear($row['enddate']);
            $numRows += dbSafeCmd($insMLD, 'isdsss', array($newMemId, $row['glNum'], $row['price'], $newStartDate, $newEndDate, $row['nickName']));
        }
        $getMLD->free();
    }
    $message .= "$numRows memListDates entries inserted for $year<br/>\n";
}

// Membership Rules
$msg = updateRules($conid, $memIdMap);
if (str_starts_with($msg, 'Error: ') ) {
    error_log("updateRules returned $msg");
}
$message .= "$msg";
// check if the current exhibits year exists and if not, try to build it from last year
$msg = exhibitorCheckOrBuildYear($conid, $memIdMap);
if (str_starts_with($msg, 'Error: ') ) {
    error_log("exhibitorCheckOrBuildYear returned $msg");
}
$message .= "$msg<br/>\n<br/>NOTE: Check the current and next year's configuration in registration, rules, and exhibits for any issues in performing the auto create.";
$response['success'] = $message;
ajaxSuccess($response);

function updateRules($conid, $memIdMap) {
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
            $rule['memList'] = updateRuleMemlist($conid, $rule['memList'], $memIdMap);
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
            $step['memList'] = updateRuleMemlist($conid, $step['memList'], $memIdMap);
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

function updateRuleMemList($conid, $list, $memIdMap) {
    $listItems = explode(',', $list);
    $newListItems = [];

    // loop over list looking up new item to replace old one
    foreach ($listItems as $item) {
        if (array_key_exists($item, $memIdMap))
            $newListItems[] = $memIdMap[$item];
    }

    if (count($newListItems) == 0)
        return null;

    return join(',', $newListItems);
    }
