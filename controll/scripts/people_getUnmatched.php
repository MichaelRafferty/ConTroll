<?php
require_once "../lib/base.php";
require_once '../lib/sessionAuth.php';

// use common global Ajax return functions
global $returnAjaxErrors, $return500errors;
$returnAjaxErrors = true;
$return500errors = true;

$perm = 'people';
$response = array ('post' => $_POST, 'get' => $_GET, 'perm' => $perm);
$authToken = new authToken('script');
$response['tokenStatus'] = $authToken->checkToken();
if (!$authToken->isLoggedIn() || !$authToken->checkAuth($perm)) {
    $response['error'] = 'Authentication Failed';
    ajaxSuccess($response);
    exit();
}

if ((!array_key_exists('ajax_request_action', $_POST)) || $_POST['ajax_request_action'] != 'unmatched') {
    $response['error'] = 'Parameter Error';
    ajaxSuccess($response);
    exit();
}

$limit = 50;
$ctQ = <<<EOS
SELECT count(*) new
FROM newperson n
WHERE perid IS NULL;
EOS;

$searchPattern = '';
$cte = '';
$cteJoin = '';
$newperid = 0;
if (array_key_exists('searchPattern', $_POST) && $_POST['searchPattern'] != '') {
    $searchPattern = $_POST['searchPattern'];
    if (is_numeric($searchPattern)) {
        $newperid = $searchPattern;
        $cte = <<<EOS
), ids AS (
    SELECT ? AS matchId
EOS;
    } else {
        $searchPattern = '%' . $_POST['searchPattern'] . '%';
        $cte = <<<EOS
), ids AS (
    SELECT id AS matchId
    FROM newperson
    WHERE
        (LOWER(legalName) LIKE ?
        OR LOWER(badge_name) LIKE ?
        OR LOWER(badgeNameL2) LIKE ?
        OR LOWER(address) LIKE ?
        OR LOWER(addr_2) LIKE ?
        OR LOWER(email_addr) LIKE ?
        OR LOWER(CONCAT(first_name, ' ', last_name)) LIKE ?
        OR LOWER(CONCAT(last_name, ' ', first_name)) LIKE ?
        OR LOWER(TRIM(REGEXP_REPLACE(CONCAT_WS(' ', first_name, middle_name, last_name, suffix), ' +', ' '))) LIKE ?)
    ORDER BY last_name, first_name, id
EOS;
    }
    $cteJoin = 'JOIN ids ON ids.matchId = n.id';
}

$unQ = <<<EOS
WITH mby AS (
SELECT n.id, count(nm.id) manages
FROM newperson n
LEFT OUTER JOIN newperson nm ON nm.managedByNew = n.id
WHERE n.perid IS NULL
GROUP BY n.id
), regs AS (
SELECT n.id, count(*) as numRegs, IFNULL(sum(r.price), 0.00) AS price, IFNULL(sum(r.paid), 0.00) AS paid, GROUP_CONCAT(m.label SEPARATOR ', ') AS regs
FROM newperson n
JOIN reg r ON r.newperid = n.id
JOIN memList m ON r.memId = m.id
WHERE r.perid IS NULL AND n.perid IS NULL AND r.status IN ('paid', 'unpaid', 'plan', 'upgraded')
GROUP BY n.id
$cte
)
SELECT n.*, mby.manages, r.numRegs, r.price, r.paid, r.regs,
TRIM(REGEXP_REPLACE(CONCAT_WS(' ', n.first_name, n.middle_name, n.last_name, n.suffix), ' +', ' ')) AS fullName,
CASE     
	WHEN mgrP.id IS NOT NULL THEN TRIM(CONCAT_WS(' ', mgrP.first_name, mgrP.last_name))
    WHEN mgrN.id IS NOT NULL THEN TRIM(CONCAT_WS(' ', mgrN.first_name, mgrN.last_name))
    ELSE null
END AS manager,
CASE     
	WHEN mgrP.id IS NOT NULL THEN 'p'
    WHEN mgrN.id IS NOT NULL THEN 'n'
    ELSE null
END AS managerType, IFNULL(n.managedBy, n.managedByNew) AS managerId, mgrN.id AS npidManager, mgrP.id AS ppidManager, mgrN.perid AS ppidPerid
FROM newperson n
$cteJoin
LEFT OUTER JOIN mby ON mby.id = n.id
LEFT OUTER JOIN newperson mgrN ON n.managedByNew = mgrN.id
LEFT OUTER JOIN perinfo mgrP ON n.managedBy = mgrP.id
LEFT OUTER JOIN regs r ON n.id = r.id
WHERE n.perid IS NULL
ORDER BY mby.manages DESC, manager, n.createtime
LIMIT $limit;
EOS;

$ctR = dbQuery($ctQ);
if ($ctR === false) {
    $response['error'] = 'Count unmatched failed';
    ajaxSuccess($response);
}

$unmatchedCnt = $ctR->fetch_row()[0];
$ctR->free();
$unmatched = [];

if ($unmatchedCnt > 0) {
    if ($cte == '')
        $unR = dbQuery($unQ);
    else if ($newperid > 0)
        $unR = dbSafeQuery($unQ,'i', array($newperid));
    else
        $unR = dbSafeQuery($unQ,'sssssssss', array($searchPattern, $searchPattern, $searchPattern, $searchPattern, $searchPattern,
            $searchPattern, $searchPattern, $searchPattern, $searchPattern));

    if ($unR === false) {
        $response['error'] = 'Select unmatched failed';
        ajaxSuccess($response);
    }

    while ($unL = $unR->fetch_assoc()) {
        $unL['badgename'] = badgeNameDefault($unL['badge_name'], $unL['badgeNameL2'], $unL['first_name'], $unL['last_name']);
        if ($unL['npidManager'] != null && $unL['ppidManager'] == null && $unL['ppidPerid'] != null) {
            $unl['managedBy'] = $unL['ppidPerid'];
            $unL['managedId'] = $unL['ppidPerid'];
            $unL['managerType'] = 'p';
        }

        $unmatched[] = $unL;
    }
    $unR->free();
}

$response['unmatched'] = $unmatched;
if (count($unmatched) < $limit)
    $response['success'] = "$unmatchedCnt unmatched new people found";
else
    $response['success'] = "Too many records were matched, only the first $limit unmatched new people returned";

$response['numUnmatched'] = $unmatchedCnt;

ajaxSuccess($response);
