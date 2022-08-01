<?php
global $db_ini;

require_once "../lib/base.php";

$check_auth = google_init("ajax");
$perm = "badge";

$response = array("post" => $_POST, "get" => $_GET, "perm"=>$perm);

if($check_auth == false || !checkAuth($check_auth['sub'], $perm)) {
    $response['error'] = "Authentication Failed";
    ajaxSuccess($response);
    exit();
}

if(!isset($_POST) || !isset($_POST['newID'])) {
    $response['error'] = "No Data";
    ajaxSuccess($response);
    exit();
}

$changeLog = $check_auth['email'] . ": " . date(DATE_ATOM)
    . ": updating from conflict " . $_POST['newID'] . "=>"
    . $_POST['oldID'] . ": ";

// Note which old record it matches
$rows = dbSafeCmd("UPDATE newperson SET perid=? WHERE id = ?", "ii", array($_POST['oldID'],$_POST['newID']));

// now build the update statement for the items to update
$nocheckboxes = array_key_exists('honorcheckboxes', $_POST) == false;
$updatename = array_key_exists('conflictFormName', $_POST) ? $_POST['conflictFormName'] == 'checked' : false;
$updatebadge = array_key_exists('conflictFormBadge', $_POST) ? $_POST['conflictFormBadge'] == 'checked' : false;
$updateemail = array_key_exists('conflictFormEmail', $_POST) ? $_POST['conflictFormEmail'] == 'checked' : false;
$updatephone = array_key_exists('conflictFormPhone', $_POST) ? $_POST['conflictFormPhone'] == 'checked' : false;
$updateaddr = array_key_exists('conflictFormAddr', $_POST) ? $_POST['conflictFormAddr'] == 'checked' : false;
$do_update = $nocheckboxes || $updatename || $updatebadge || $updateemail || $updatephone || $updateaddr;

if ($do_update) {
    $query = "UPDATE perinfo SET ";
    $types = '';
    $values = array();
    $addcomma = false;

    // name fields if all or checked
    if ($nocheckboxes || $updatename) {
        $query .= 'first_name = ?, middle_name = ?, last_name = ?, suffix = ?';
        $types .= 'ssss';
        array_push($values, trim($_POST['first_name']), trim($_POST['middle_name']),
            trim($_POST['last_name']),trim($_POST['suffix']));
        $addcomma = true;
    }

    // badgename if all or checked
    if ($nocheckboxes || $updatebadge) {
        if ($addcomma) {
            $query .= ", ";
        } else {
            $addcomma = true;
        }
        $query .= 'badge_name = ?';
        $types .= 's';
        array_push($values, trim($_POST['badge_name']));
    }

    // address fields if all or checked
    if ($nocheckboxes || $updateaddr) {
        if ($addcomma) {
            $query .= ", ";
        } else {
            $addcomma = true;
        }
        $query .= 'address = ?, addr_2 = ?, city = ?, state = ?, zip = ?, country = ?';
        $types .= 'ssssss';
        array_push($values, trim($_POST['address']),trim($_POST['addr_2']),trim($_POST['city']),trim($_POST['state']),trim($_POST['zip']),trim($_POST['country']));
    }

    // email if all or checked
    if ($nocheckboxes || $updateemail) {
        if ($addcomma) {
            $query .= ", ";
        } else {
            $addcomma = true;
        }
        $query .= 'email_addr = ?';
        $types .= 's';
        array_push($values, trim($_POST['email_addr']));
    }


    // phone if all or checked
    if ($nocheckboxes || $updatephone) {
        if ($addcomma) {
            $query .= ", ";
        } else {
            $addcomma = true;
        }
        $query .= 'phone = ?';
        $types .= 's';
        array_push($values, trim($_POST['phone']));
    }

    $query .= " WHERE id=?;";
    $types .= 'i';
    array_push($values, $_POST['oldID']);
    $response['first_q'] = $query;
    $rows = dbSafeCmd($query, $types, $values);
}

$query2 = "UPDATE perinfo SET change_notes=CONCAT(change_notes, '<br/>', ?) WHERE id=?;";
$types = 'si';
$values = array($changeLog, $_POST['oldID']);
$response['second_q'] = $query2;
$rows = dbSafeCmd($query2, $types, $values);

dbSafeCmd('UPDATE reg SET perid=? WHERE newperid=?;', 'ii', array($_POST['oldID'], $_POST['newID']));
dbSafeCmd('UPDATE transaction SET perid=? WHERE newperid=?;', 'ii', array($_POST['oldID'], $_POST['newID']));

$response['changeLog'] = $changeLog;

ajaxSuccess($response);
?>
