<?php
global $db_ini;

require_once "../lib/base.php";

$check_auth = google_init("ajax");
$perm = "search";

$response = array("post" => $_POST, "get" => $_GET, "perm"=>$perm);


if($check_auth == false || !checkAuth($check_auth['sub'], $perm)) {
    $response['error'] = "Authentication Failed";
    ajaxSuccess($response);
    exit();
}

if(!isset($_GET) || !isset($_GET['id'])) {
    $response['error'] = "No Data";
    ajaxSuccess($response);
    exit();
}

$newPQ = <<<EOQ
SELECT id, perid, first_name, middle_name, last_name, suffix, legalName
    , concat_ws(' ', first_name, middle_name, last_name, suffix) as full_name
    , address, addr_2, city, state, zip
    , concat_ws(' ', city, state, zip) as locale, country
    , badge_name, email_addr, phone, share_reg_ok, contact_ok
FROM newperson
WHERE id = ?;
EOQ;

if(strtolower(substr($_GET['id'],0,1)) == 'f') {
    $id = dbQuery("select min(id) id from newperson where perid IS NULL;")->fetch_row()[0];
} else if(strtolower(substr($_GET['id'],0,1)) == 'l') {
    $id = dbQuery("select max(id) id from newperson where perid IS NULL;")->fetch_row()[0];
} else {
    $id = $_GET['id'];
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
if ($newRes != null) {

    $results = array(
        'exact_match' => array(),
        'banned' => array(),
        'info_update' => array(),
        'addr_update' => array(),
        'phone_update' => array(),
        'email_update' => array(),
        'name_change' => array(),
        'other'=>array());

    $nameQ = <<<EOQ
SELECT id, first_name, middle_name, last_name, suffix, legalName
    , concat_ws(' ', first_name, middle_name, last_name, suffix) as full_name
    , address, addr_2, city, state, zip
    , concat_ws(' ', city, state, zip) as locale, country
    , badge_name, email_addr, phone, active, banned, share_reg_ok, contact_ok
FROM perinfo
WHERE last_name=? AND first_name like ?
ORDER BY active DESC, id ASC;
EOQ;

    $nameA = dbSafeQuery($nameQ, "ss",
        array(
            html_entity_decode(trim($newRes['last_name']), ENT_QUOTES),
            html_entity_decode(substr(trim($newRes['first_name']),0,2), ENT_QUOTES) ."%"
        ));
    $response['nameQ'] = $nameQ;

    $emailQ = <<<EOQ
SELECT id, first_name, middle_name, last_name, suffix, legalName
    , concat_ws(' ', first_name, middle_name, last_name, suffix) as full_name
    , address, addr_2, concat_ws(' ', city, state, zip) as locale, country
    , badge_name, email_addr, phone, active, banned, share_reg_ok, contact_ok
FROM perinfo
WHERE email_addr=? AND (last_name != ? OR first_name != ?)
ORDER BY active DESC, id ASC;
EOQ;

    $emailA = dbSafeQuery($emailQ, 'sss', array(
            html_entity_decode(trim($newRes['email_addr']), ENT_QUOTES),
            html_entity_decode(trim($newRes['last_name']), ENT_QUOTES),
            html_entity_decode(trim($newRes['first_name']), ENT_QUOTES)
        ));
    $response['emailQ'] = $emailQ;
    $countFound = 0;

    while($nameRes = fetch_safe_assoc($nameA)) {
        /* set a large number of boolean values to improve readability */
        $mname_match = (strtoupper(is_null($newRes['middle_name']) ? '' : $newRes['middle_name']) == strtoupper(is_null($nameRes['middle_name']) ? '' : $nameRes['middle_name']));
        $badge_match = (strtoupper(is_null($newRes['badge_name']) ? '' : $newRes['badge_name']) == strtoupper(is_null($nameRes['badge_name']) ? '' : $nameRes['badge_name']));
        $addr_match = (strtoupper(is_null($newRes['address']) ? '' : $newRes['address']) == strtoupper(is_null($nameRes['address']) ? '' : $nameRes['address']))
             && (strtoupper(is_null($newRes['locale']) ? '' : $newRes['locale']) == strtoupper(is_null($nameRes['locale']) ? '' : $nameRes['locale']))
             && ($newRes['country']=='USA' ||
                    (strtoupper(is_null($newRes['country']) ? '' : $newRes['country']) == strtoupper(is_null($nameRes['country']) ? '' : $nameRes['country'])));
        $addr2_match = (strtoupper(is_null($newRes['addr_2']) ? '' : $newRes['addr_2']) == strtoupper(is_null($nameRes['addr_2']) ? '' : $nameRes['addr_2']));
        $email_match = (strtoupper(is_null($newRes['email_addr']) ? '' : $newRes['email_addr']) == strtoupper(is_null($nameRes['email_addr']) ? '' : $nameRes['email_addr']));
        $phone_match = (strtoupper(is_null($newRes['phone']) ? '' : $newRes['phone']) == strtoupper(is_null($nameRes['phone']) ? '' : $nameRes['phone']));
        $phone_onenull = ((is_null($newRes['phone']) ? '' : $newRes['phone'])  == '') || ((is_null($nameRes['phone']) ? '' : $nameRes['phone']) == '');
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
        $mname_match = (strtoupper(is_null($newRes['middle_name']) ? '' : $newRes['middle_name']) == strtoupper(is_null($nameRes['middle_name']) ? '' : $nameRes['middle_name']));
        $badge_match = (strtoupper(is_null($newRes['badge_name']) ? '' : $newRes['badge_name']) == strtoupper(is_null($nameRes['badge_name']) ? '' : $nameRes['badge_name']));
        $addr_match = (strtoupper(is_null($newRes['address']) ? '' : $newRes['address']) == strtoupper(is_null($nameRes['address']) ? '' : $nameRes['address']))
            && (strtoupper(is_null($newRes['locale']) ? '' : $newRes['locale']) == strtoupper(is_null($nameRes['locale']) ? '' : $nameRes['locale']))
            && (strtoupper(is_null($newRes['country']) ? '' : $newRes['country']) == strtoupper(is_null($nameRes['country']) ? '' : $nameRes['country']));
        $addr2_match = (strtoupper(is_null($newRes['addr_2']) ? '' : $newRes['addr_2']) == strtoupper(is_null($nameRes['addr_2']) ? '' : $nameRes['addr_2']));
        $phone_match = (strtoupper(is_null($newRes['phone']) ? '' : $newRes['phone']) == strtoupper(is_null($nameRes['phone']) ? '' : $nameRes['phone']));
        $phone_onenull = ((is_null($newRes['phone']) ? '' : $newRes['phone'])  == '') || ((is_null($nameRes['phone']) ? '' : $nameRes['phone']) == '');
        $banned = ($nameRes['banned'] == 'Y');

        if($addr_match && ($phone_match || $phone_onenull)) {
            $countFound += 1;
            $results['name_change'][count($results['name_change'])] = $nameRes;
        }
    }
} else {
    $countFound = 0;
    $results = null;
}

$response['count'] = $countFound;
$response['results']=$results;


ajaxSuccess($response);
?>
