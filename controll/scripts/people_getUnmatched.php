<?php
require_once "../lib/base.php";

$check_auth = google_init("ajax");
$perm = "people";

$response = array("post" => $_POST, "get" => $_GET, "perm"=>$perm);

if($check_auth == false || !checkAuth($check_auth['sub'], $perm)) {
    $response['error'] = "Authentication Failed";
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
END AS managerType, IFNULL(n.managedBy, n.managedByNew) AS managerId
FROM newperson n
LEFT OUTER JOIN mby ON mby.id = n.id
LEFT OUTER JOIN newperson mgrN ON n.managedByNew = mgrN.id
LEFT OUTER JOIN perinfo mgrP ON n.managedBy = mgrP.id
LEFT OUTER JOIN regs r ON n.id = r.id
WHERE n.perid IS NULL
ORDER BY mby.manages DESC, manager, n.createtime
LIMIT $limit;
EOS;

$ctR = dbQuery($ctQ);
if ($ctQ === false) {
    $response['error'] = 'Count unmatched failed';
    ajaxSuccess($response);
}

$unmatchedCnt = $ctR->fetch_row()[0];
$ctR->free();
$unmatched = [];

if ($unmatchedCnt > 0) {

    $unR = dbQuery($unQ);
    if ($unR === false) {
        $response['error'] = 'Select unmatched failed';
        ajaxSuccess($response);
    }

    while ($unL = $unR->fetch_assoc()) {
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
