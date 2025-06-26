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

if ((!array_key_exists('ajax_request_action', $_POST)) || $_POST['ajax_request_action'] != 'delete' ||
        (!array_key_exists('newperid', $_POST))) {
    $response['error'] = 'Parameter Error';
    ajaxSuccess($response);
    exit();
}

$newperid = $_POST['newperid'];
$con_conf = get_conf('con');
$conid = $con_conf['id'];

// first validate that this person can be deleted
$nQ = <<<EOS
WITH mby AS (
SELECT n.id, count(*) manages
FROM newperson n
JOIN newperson nm ON nm.managedByNew = n.id
WHERE n.id = ?
GROUP BY n.id
), regs AS (
	SELECT newperid, sum(paid) AS paid
    FROM reg r
    WHERE newperid = ? and conid = ?
    GROUP BY newperid
)
SELECT n.*, IFNULL(r.paid, 0.00) AS paid, IFNULL(m.manages, 0) AS manages,
    TRIM(REGEXP_REPLACE(CONCAT_WS(' ', n.first_name, n.middle_name, n.last_name, n.suffix), '  *', ' ')) AS fullName
FROM newperson n
LEFT OUTER JOIN regs r ON n.id = r.newperid
LEFT OUTER JOIN mby m ON n.id = m.id
WHERE n.id = ?;
EOS;

$nR = dbSafeQuery($nQ, 'iiii', array($newperid, $newperid, $conid, $newperid));
if ($nR === false || $nR->num_rows != 1) {
    $response['error'] = 'Select newperson failed';
    ajaxSuccess($response);
}

$newperson = [];
$newperson = $nR->fetch_assoc();
$nR->free();

$response['newperson'] = $newperson;

// check if valid: must be unpaid, unmanaged and no perid
    if ($newperson['manages'] > 0) {
        $response['error'] = 'Cannot delete ' . $newperson['fullName'] . ' as they manage ' . $newperson['manages'] . ' persons';
        ajaxSuccess($response);
        return;
    }
    if ($newperson['paid'] > 0) {
        $response['error'] = 'Cannot delete ' . $newperson['fullName'] . ' as they have ' . $newperson['paid'] . ' on memberships. All memberships must be unpaid.';
        ajaxSuccess($response);
        return;
    }

    if ($newperson['perid'] != null) {
        $response['error'] = 'Cannot delete ' . $newperson['fullName'] . ' as they have already been matched to perid ' . $newperson['perid'] . '.';
        ajaxSuccess($response);
        return;
    }

// ok, to delete this new person we need to walk the history tree
// first the memberPolicies and memberInterests
    $delcnt = dbSafeCmd("DELETE FROM memberPolicies WHERE newperid = ?;", 'i', array($newperid));
    $delcnt += dbSafeCmd("DELETE FROM memberInterests WHERE newperid = ?;", 'i', array($newperid));

// next the reg entries
    $delcnt += dbSafeCmd('DELETE FROM reg WHERE newperid = ?;', 'i', array ($newperid));

// next the transactions
    $updcnt = dbSafeCmd('UPDATE newperson SET transid = NULL WHERE id = ?;', 'i', array($newperid));
    $delcnt += dbSafeCmd('DELETE FROM transaction WHERE newperid = ?;', 'i', array ($newperid));

// last the newperson itself
    $delcnt += dbSafeCmd('DELETE FROM newperson WHERE id = ?;', 'i', array ($newperid));


$response['success'] = 'Deleted the newperson ' . $newperson['fullName'] . ", $delcnt records deleted.";
ajaxSuccess($response);
