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

// build the memList entries for this year for volunteer rollover and yearahead
// first volunteer rollover
$checkMLQ1 = <<<EOS
SELECT conid, sort_order, memCategory, memType, memAge, label, notes, price, startdate, enddate, atcon, online
FROM memList
WHERE label = ? AND conid = ?;
EOS;
$checkMLQ2 = <<<EOS
SELECT conid, sort_order, memCategory, memType, memAge, label, notes, price, startdate, enddate, atcon, online
FROM memList
WHERE memCategory = ? AND conid = ?;
EOS;
$insML = <<<EOS
INSERT INTO memList(conid, sort_order, memCategory, memType, memAge, label, notes, price, startdate, enddate, atcon, online)
VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?);
EOS;

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
            $row['atcon'], $row['online']
        );
        $numRows += dbSafeCmd($insML, 'iisssssdssss', $valueArr);
    }
    $message .= "$numRows Rollover-volunteer memList entries added for $nextConid<br/>\n";
}

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
            $row['atcon'], $row['online']
        );
        $numRows += dbSafeCmd($insML, 'iisssssdssss', $valueArr);
    }
    $message .= "$numRows yearahead memList entries added for $nextConid<br/>\n";
}


// check if the current exhibits year exists and if not, try to build it from last year
$msg = exhibitorCheckOrBuildYear($conid);
if ($msg != '') {
    $message .= "Error: $msg <br/>\n";
    error_log("checkOrBuildYear returned $msg");
}
$message .= "<br/>&nbsp;<br/>NOTE: Check the current and next years configuration in registration, rules, and exhibits for any issues in performing the auto create.";
$response['success'] = $message;
ajaxSuccess($response);
?>
