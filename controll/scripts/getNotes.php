<?php
global $db_ini;

require_once "../lib/base.php";
$check_auth = google_init("ajax");
$perm = "reg_staff";

$response = array("post" => $_POST, "get" => $_GET, "perm"=>$perm);

if($check_auth == false || !checkAuth($check_auth['sub'], $perm)) {
    $response['error'] = "Authentication Failed";
    ajaxSuccess($response);
    exit();
}

if (array_key_exists('rid', $_POST)) {
    $regid = $_POST['rid'];
    $nQ = <<<EOS
SELECT logdate, userid, tid, notes
FROM regActions
WHERE regid = ? AND action = 'notes';
EOS;
    $nR = dbSafeQuery($nQ, 'i', array($regid));
    if ($nR === false) {
        $response['error'] = 'Error retrieving notes records';
        ajaxSuccess($response);
        exit();
    }
    $notes = [];
    while ($note = $nR->fetch_assoc()) {
        $notes[] = $note;
    }
    $nR->free();
    $response['notes'] = $notes;
    $pQ = <<<EOS
SELECT m.label, CASE 
    WHEN p.id IS NOT NULL THEN TRIM(REGEXP_REPLACE(CONCAT(p.first_name, ' ', p.middle_name, ' ', p.last_name, ' ', p.suffix), '  *', ' '))
    WHEN n.id IS NOT NULL THEN TRIM(REGEXP_REPLACE(CONCAT(n.first_name, ' ', n.middle_name, ' ', n.last_name, ' ', n.suffix), '  *', ' '))
    ELSE 'Unknown'
END AS fullName
FROM reg r
JOIN memLabel m ON m.id = r.memid
LEFT OUTER JOIN perinfo p ON p.id = r.perid
LEFT OUTER JOIN newperson n ON n.id = r.newperid
WHERE r.id = ?;
EOS;
    $pR = dbSafeQuery($pQ, 'i', array($regid));
    if ($pR === false || $pR->num_rows != 1) {
        $response['error'] = 'Error retrieving person records';
        ajaxSuccess($response);
        exit();
    }
    $person = $pR->fetch_row();
    $pR->free();
    $response['label'] = $person[0];
    $response['fullName'] = $person[1];
} else {
    $response['error'] = 'Improper calling sequence';
}

ajaxSuccess($response);
exit();
