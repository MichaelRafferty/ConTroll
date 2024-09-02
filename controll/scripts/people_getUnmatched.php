<?php
global $db_ini;

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

$unQ = <<<EOS
WITH mby AS (
SELECT n.id, count(*) manages
FROM newperson n
JOIN newperson nm ON nm.managedByNew = n.id
WHERE n.perid IS NULL
GROUP BY n.id
), regs AS (
SELECT n.id, count(*) as numRegs, sum(price) AS price, sum(paid) AS paid
FROM newperson n
JOIN reg r ON r.newperid = n.id
WHERE r.perid IS NULL AND n.perid IS NULL AND r.status IN ('paid', 'unpaid', 'plan', 'upgraded')
GROUP BY n.id
)
SELECT n.*, mby.manages, r.numRegs, r.price, r.paid,
TRIM(REGEXP_REPLACE(
    CONCAT(IFNULL(n.first_name, ''),' ', IFNULL(n.middle_name, ''), ' ', IFNULL(n.last_name, ''), ' ',  IFNULL(n.suffix, '')),
    '  *', ' ')) AS fullName,
CASE     
	WHEN mgrP.id IS NOT NULL THEN TRIM(CONCAT(mgrP.first_name, ' ', mgrP.last_name))
    WHEN mgrN.id IS NOT NULL THEN TRIM(CONCAT(mgrN.first_name, ' ', mgrN.last_name))
    ELSE null
END AS manager,
CASE     
	WHEN mgrP.id IS NOT NULL THEN 'p'
    WHEN mgrN.id IS NOT NULL THEN 'n'
    ELSE null
END AS managerType
FROM newperson n
LEFT OUTER JOIN mby ON mby.id = n.id
LEFT OUTER JOIN newperson mgrN ON n.managedByNew = mgrN.id
LEFT OUTER JOIN perinfo mgrP ON n.managedBy = mgrP.id
LEFT OUTER JOIN regs r ON n.id = r.id
WHERE n.perid IS NULL
ORDER BY mby.manages, manager, n.createtime
EOS;

$unR = dbQuery($unQ);
if ($unR === false) {
    $response['error'] = 'Select unmatched failed';
    ajaxSuccess($response);
}

$unmatched = [];
while ($unL = $unR->fetch_assoc()) {
    $unmatched[] = $unL;
}
$unR->free();

$response['unmatched'] = $unmatched;
$response['numUnmatched'] = count($unmatched);

ajaxSuccess($response);
?>
