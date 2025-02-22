<?php
// items related to using memRules

function getRulesData($conid, $regadmin = false, $atcon = false) {
    $data = [];
// get ageList, memTypes, memCategories, memList
    $ageList = array();
    $ageListIdx = array();
    $QR = dbSafeQuery('SELECT * FROM ageList WHERE conid = ? ORDER BY sortorder;', 'i', array($conid));
    while ($row = $QR->fetch_assoc()) {
        $ageList[] = $row;
        $ageListIdx[$row['ageType']] = $row;
    }
    $QR->free();
    $data['ageList'] = $ageList;
    $data['ageListIdx'] = $ageListIdx;

    $QR = dbQuery("SELECT * FROM memTypes WHERE active = 'Y' ORDER BY sortorder;");
    $memTypes = array();
    while ($row = $QR->fetch_assoc()) {
        $memTypes[$row['memType']] = $row;
    }
    $QR->free();
    $data['memTypes'] = $memTypes;

    if ($regadmin) {
        $QR = "SELECT * FROM memCategories ORDER BY sortorder;";
    } else {
        $QR = "SELECT * FROM memCategories WHERE active = 'Y' ORDER BY sortorder;";
    }
    $QR = dbQuery($QR);
    $memCategories = array();
    while ($row = $QR->fetch_assoc()) {
        $memCategories[$row['memCategory']] = $row;
    }
    $QR->free();
    $data['memCategories'] = $memCategories;

    $memList = array();
    $memListIdx = array();
    if ($atcon) {
        $where = "AND startdate <= NOW() AND enddate > NOW()  AND atcon = 'Y'";
    } else {
        $where = $regadmin ? '' : "AND startdate <= NOW() AND enddate > NOW()  AND online = 'Y'";
    }

    // variable sort orders
    if ($regadmin || $atcon) {
        $orderBy = 'conid, mt.sortorder, m.label';
    } else {
        $orderBy = 'm.sort_order';
    }
    $QQ = <<<EOS
SELECT m.*, m.id as memId
FROM memList m
JOIN memTypes mt ON m.memType = mt.memType
WHERE ((conid = ? AND memCategory != 'yearahead') OR (conid = ? AND memCategory = 'yearahead'))
$where
ORDER BY $orderBy;
EOS;
    $QR = dbSafeQuery($QQ, 'ii', array($conid, $conid + 1));
    while ($row = $QR->fetch_assoc()) {
        $memList[] = $row;
        $memListIdx[$row['id']] = $row;
    }
    $QR->free();
    $data['memList'] = $memList;
    $data['memListIdx'] = $memListIdx;

// now get the Membership Rules
    $memRules = array();
    $QQ = <<<EOS
SELECT r.name, r.optionName, r.description, r.typeList, r.catList, r.ageList, r.memList, 0 AS uses, r.name AS origName
FROM memRules r
ORDER BY name;
EOS;
    $QR = dbQuery($QQ);
    while ($row = $QR->fetch_assoc()) {
        if ($row['typeList'] != null && $row['typeList'] != '') {
            $row['typeListArray'] = explode(',', $row['typeList']);
        }
        if ($row['catList'] != null && $row['catList'] != '') {
            $row['catListArray'] = explode(',', $row['catList']);
        }
        if ($row['ageList'] != null && $row['ageList'] != '') {
            $row['ageListArray'] = explode(',', $row['ageList']);
        }
        if ($row['memList'] != null && $row['memList'] != '') {
            $row['memListArray'] = explode(',', $row['memList']);
        }
        $memRules[$row['name']] = $row;
    }
    $QR->free();
// now the more difficult task, get the membership rule items
    $QQ = <<<EOS
SELECT name, step, ruleType, applyTo, typeList, catList, ageList, memList, 0 AS uses, name AS origName, step AS origStep
FROM memRuleSteps
ORDER BY name, step;
EOS;

    $currentName = null;
    $currentRules = array();
    $QR = dbQuery($QQ);
    while ($row = $QR->fetch_assoc()) {
        if ($currentName != $row['name']) {
            if ($currentName != null) {
                $memRules[$currentName]['ruleset'] = $currentRules;
                $currentRules = array();
            }
            $currentName = $row['name'];
        }
        if ($row['typeList'] != null && $row['typeList'] != '') {
            $row['typeListArray'] = explode(',', $row['typeList']);
        }
        if ($row['catList'] != null && $row['catList'] != '') {
            $row['catListArray'] = explode(',', $row['catList']);
        }
        if ($row['ageList'] != null && $row['ageList'] != '') {
            $row['ageListArray'] = explode(',', $row['ageList']);
        }
        if ($row['memList'] != null && $row['memList'] != '') {
            $row['memListArray'] = explode(',', $row['memList']);
        }
        $currentRules[$row['step']] = $row;
    }
    if ($currentName != null)
        $memRules[$currentName]['ruleset'] = $currentRules;

    $data['memRules'] = $memRules;
    return $data;
}
