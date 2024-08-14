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

// now build the update statement for the items to update
$nocheckboxes = array_key_exists('honorcheckboxes', $_POST) == false;
$updatename = array_key_exists('conflictFormName', $_POST) ? $_POST['conflictFormName'] == 'checked' : false;
$updatebadge = array_key_exists('conflictFormBadge', $_POST) ? $_POST['conflictFormBadge'] == 'checked' : false;
$updateemail = array_key_exists('conflictFormEmail', $_POST) ? $_POST['conflictFormEmail'] == 'checked' : false;
$updatephone = array_key_exists('conflictFormPhone', $_POST) ? $_POST['conflictFormPhone'] == 'checked' : false;
$updateaddr = array_key_exists('conflictFormAddr', $_POST) ? $_POST['conflictFormAddr'] == 'checked' : false;
$updateflags = array_key_exists('conflictFormFlags', $_POST) ? $_POST['conflictFormFlags'] == 'checked' : false;
$do_update = $nocheckboxes || $updatename || $updatebadge || $updateemail || $updatephone || $updateaddr || $updateflags;

if ($do_update) {
    $query = "UPDATE perinfo SET active='Y', ";
    $types = '';
    $values = array();
    $addcomma = false;
    var_error_log($_POST);

    // name fields if all or checked
    if ($nocheckboxes || $updatename) {
        $query .= 'first_name = ?, middle_name = ?, last_name = ?, suffix = ?, legalName = ?, pronouns = ?';
        $types .= 'ssssss';
        array_push($values, trim($_POST['first_name']), trim($_POST['middle_name']),
            trim($_POST['last_name']),trim($_POST['suffix']),trim($_POST['legalName']),trim($_POST['pronouns']));
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

    // flags if all or checked
    if ($nocheckboxes || $updateflags) {
        if ($addcomma) {
            $query .= ", ";
        } else {
            $addcomma = true;
        }
        if (array_key_exists('conflictFormNewShareReg', $_POST)) {
            $shareReg = trim($_POST["conflictFormNewShareReg"]);
            if ($shareReg != 'Y' && $shareReg != 'N') {
                error_log("Inavalid shareReg value of $shareReg");
                $shareReg = 'Y';
            }
        } else {
            $shareReg = 'Y';
        }
        if (array_key_exists('conflictFormNewContactOK', $_POST)) {
            $contactOK = trim($_POST["conflictFormNewContactOK"]);
            if ($contactOK != 'Y' && $contactOK != 'N') {
                error_log("Inavalid contactOK value of $contactOK");
                $contactOK = 'Y';
            }
        } else {
            $contactOK = 'Y';
        }
        $query .= 'share_reg_ok = ?, contact_ok = ?, updatedBy = ?';
        $types .= 'ssi';
        array_push($values, $shareReg);
        array_push($values, $contactOK);
        array_push($values, $_SESSION['user_id']);
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
$errors = '';
$perid = $_POST['oldID'];
$newperid = $_POST['newID'];
$query2 = "UPDATE perinfo SET change_notes=CONCAT(IFNULL(change_notes,''), '<br/>', ?) WHERE id=?;";
$types = 'si';
$values = array($changeLog, $perid);
$response['second_q'] = $query2;
$rows = dbSafeCmd($query2, $types, $values);
if ($rows === false || $rows != 1) {
    $errors .= "Unable to add $changeLog to person $perid<br/>\n";
}

$response['changeLog'] = $changeLog;
if ($errors != '') {
    $response['errors'] = $errors;
    error_log($errors);
}

ajaxSuccess($response);
?>