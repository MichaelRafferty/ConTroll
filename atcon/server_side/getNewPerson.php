<?php
require_once "lib/base.php";

$perm="data_entry";
$con = get_con();
$conid=$con['id'];
$response = array("post" => $_POST, "get" => $_GET);
$check_auth=false;
if(isset($_POST) && isset($_POST['user']) && isset($_POST['passwd'])) {
    $check_auth = check_atcon($_POST['user'], $_POST['passwd'], $perm, $conid);
}

if($check_auth == false) {
    $response['error'] = "Authentication Failed";
    ajaxSuccess($response);
    exit();
}

if(!isset($_POST) || !isset($_POST['id'])) {
    $response['error'] = "No Data";
    ajaxSuccess($response);
    exit();
}

$newPQ = <<<EOS
SELECT id, perid, first_name, middle_name, last_name, suffix, concat_ws(' ', first_name, middle_name, last_name, suffix) as full_name
    , address, addr_2, concat_ws(' ', city, state, zip) as locale, country, badge_name, email_addr, phone
FROM newperson
WHERE id =?;
EOS;

if(strtolower(substr($_POST['id'],0,1)) == 'f') {
    $id = dbQuery("select min(id) from newperson where perid IS NULL;")->fetch_array();
    $id = $id[0];
} else if(strtolower(substr($_POST['id'],0,1)) == 'l') {
    $id = dbQuery("select max(id) from newperson where perid IS NULL;")->fetch_array();
    $id = $id[0];
} else {
    $id = $_POST['id'];
}

//$response['newPQ'] = $newPQ;

$newPA = dbSafeQuery($newPQ, 'i', array($id));
if(!$newPA) {
    $response['error'] = 'Query Failed';
    $response['newPQ'] = $newPQ;
    ajaxSuccess($response);
    exit();
}

$newRes = fetch_safe_assoc($newPA);

$response['new'] = $newRes;

$results = array(
    'exact_match' => array(),
    'banned' => array(),
    'info_update' => array(),
    'addr_update' => array(),
    'phone_update' => array(),
    'email_update' => array(),
    'name_change' => array(),
    'other'=>array());

$nameQ = <<<EOS
SELECT id, first_name, middle_name, last_name, suffix, concat_ws(' ', first_name, middle_name, last_name, suffix) as full_name
    , address, addr_2, concat_ws(' ', city, state, zip) as locale, country, badge_name, email_addr, phone, active, banned
FROM perinfo
WHERE last_name=? AND first_name like ?
ORDER BY active DESC, id ASC;
EOS;

$emailQ = <<<EOS
SELECT id, first_name, middle_name, last_name, suffix, concat_ws(' ', first_name, middle_name, last_name, suffix) as full_name
    , address, addr_2, concat_ws(' ', city, state, zip) as locale, country, badge_name, email_addr, phone, active, banned
FROM perinfo
WHERE email_addr=? AND (last_name != ? OR first_name != ?)
ORDER BY active DESC, id ASC;
EOS;

$nameA = dbSafeQuery($nameQ, 'ss', array(
        html_entity_decode(trim($newRes['last_name']), ENT_QUOTES),
        html_entity_decode(substr(trim($newRes['first_name']),0, 2), ENT_QUOTES) . "%"
        ));
$response['nameQ'] = $nameQ;
$emailA = dbSafeQuery($emailQ, 'sss', array(
        html_entity_decode(trim($newRes['email_addr'], ENT_QUOTES)),
        html_entity_decode(trim($newRes['last_name'], ENT_QUOTES)),
        html_entity_decode(trim($newRes['first_name'], ENT_QUOTES))
        ));
$response['emailQ'] = $emailQ;
$countFound = 0;

while($nameRes = fetch_safe_assoc($nameA)) {
    /* set a large number of boolean values to improve readability */
    $mname_match = (strtoupper($newRes['middle_name']) == strtoupper($nameRes['middle_name']));
    $badge_match = (strtoupper($newRes['badge_name']) == strtoupper($nameRes['badge_name']));
    $addr_match = (strtoupper($newRes['address']) == strtoupper($nameRes['address']))
         && (strtoupper($newRes['locale']) == strtoupper($nameRes['locale']))
         && ($newRes['country']=='USA' or (strtoupper($newRes['country']) == strtoupper($nameRes['country'])));
    $addr2_match = (strtoupper($newRes['addr_2']) == strtoupper($nameRes['addr_2']));
    $email_match = (strtoupper($newRes['email_addr']) == strtoupper($nameRes['email_addr']));
    $phone_match = ($newRes['phone'] == $nameRes['phone']);
    $phone_onenull = ($newRes['phone'] == '') || ($nameRes['phone'] == '');
    $banned = ($nameRes['banned'] == 'Y');

    if($addr_match && $email_match && ($phone_match || $phone_onenull)) { // exact match
        $countFound += 1;
        if($banned) {
            $results['banned'][count($results['banned'])] = $nameRes;
        } else if(!$badge_match || !$addr2_match || !$mname_match ||
                ($phone_onenull && !$phone_match)) {
            array_push($results['info_update'], $nameRes);
        } else {
            array_push($results['exact_match'], $nameRes);
        }
    } else if($addr_match && $email_match && !($phone_match || $phone_onenull)) { // phone update
        $countFound += 1;
        array_push($results['phone_update'], $nameRes);
    } else if($addr_match && !$email_match && ($phone_match || $phone_onenull)) { // email update
        $countFound += 1;
        array_push($results['email_update'], $nameRes);
    } else if(!$addr_match && $email_match && ($phone_match || $phone_onenull)) { // addr update
        $countFound += 1;
        array_push($results['addr_update'], $nameRes);
    } else {
        $countFound +=1;
        array_push($results['other'], $nameRes);
    }
}

while($nameRes = fetch_safe_assoc($emailA)) {
    /* set a large number of booleans to improve later readability */
    $mname_match = (strtoupper($newRes['middle_name']) == strtoupper($nameRes['middle_name']));
    $badge_match = (strtoupper($newRes['badge_name']) == strtoupper($nameRes['badge_name']));
    $addr_match = (strtoupper($newRes['address']) == strtoupper($nameRes['address']))
        && (strtoupper($newRes['locale']) == strtoupper($nameRes['locale']))
        && (strtoupper($newRes['country']) == strtoupper($nameRes['country']));
    $addr2_match = (strtoupper($newRes['addr_2']) == strtoupper($nameRes['addr_2']));
    $phone_match = ($newRes['phone'] == $nameRes['phone']);
    $phone_onenull = ($newRes['phone'] == '') || ($nameRes['phone'] == '');
    $banned = ($nameRes['banned'] == 'Y');

    if($addr_match && ($phone_match || $phone_onenull)) {
        $countFound += 1;
        $results['name_change'][count($results['name_change'])] = $nameRes;
    }
}

$response['count'] = $countFound;
$response['results']=$results;


ajaxSuccess($response);
?>
