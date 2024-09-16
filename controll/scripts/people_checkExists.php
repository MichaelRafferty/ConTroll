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

if ((!array_key_exists('type', $_POST)) || $_POST['type'] != 'check') {
    $response['error'] = 'Parameter Error';
    ajaxSuccess($response);
    exit();
}

$firstName = array_key_exists('firstName', $_POST) ? trim(strtolower($_POST['firstName'])) : '';
$middleName = array_key_exists('middleName', $_POST) ? trim(strtolower($_POST['middleName'])) : '';
$lastName = array_key_exists('lastName', $_POST) ? trim(strtolower($_POST['lastName'])) : '';
$suffix = array_key_exists('suffix', $_POST) ? trim(strtolower($_POST['suffix'])) : '';
$legalName = array_key_exists('legalName', $_POST) ? trim(strtolower($_POST['legalName'])) : '';
$pronouns = array_key_exists('pronouns', $_POST) ? trim(strtolower($_POST['pronouns'])) : '';
$badgeName = array_key_exists('badgeName', $_POST) ? trim(strtolower($_POST['badgeName'])) : '';
$address = array_key_exists('address', $_POST) ? trim(strtolower($_POST['address'])) : '';
$addr2 = array_key_exists('addr2', $_POST) ? trim(strtolower($_POST['addr2'])) : '';
$city = array_key_exists('city', $_POST) ? trim(strtolower($_POST['city'])) : '';
$state = array_key_exists('state', $_POST) ? trim(strtolower($_POST['state'])) : '';
$zip = array_key_exists('zip', $_POST) ? trim(strtolower($_POST['zip'])) : '';
$country = array_key_exists('country', $_POST) ? trim(strtolower($_POST['country'])) : '';
$emailAddr = array_key_exists('emailAddr', $_POST) ? trim(strtolower($_POST['emailAddr'])) : '';
$phone = array_key_exists('phone', $_POST) ? trim(strtolower($_POST['phone'])) : '';
$phoneCheck = str_replace('(', '',
    str_replace(')', '',
        str_replace('-', '',
            str_replace(' ', '', $phone))));

if ($firstName . $middleName .  $lastName .  $suffix .  $legalName .  $pronouns .  $badgeName .  $address .  $addr2 .
    $city .  $state .  $zip . $emailAddr .  $phone == '') {
    $response['error'] = 'The form cannot be empty, you need something to match on beyond just country';
    ajaxSuccess($response);
    exit();
}

// does anyone match this person?
$mQ = <<<EOS
SELECT p.id, p.last_name, p.first_name, p.middle_name, p.suffix, p.email_addr, p.phone, p.badge_name, p.legalname, p.pronouns, 
    p.address, p.addr_2, p.city, p.state, p.zip, p.country, p.banned, 
    p.creation_date, p.update_date, p.active, p.open_notes,
    p.managedBy, p.managedByNew, p.lastverified, p.managedreason,
    REPLACE(REPLACE(REPLACE(REPLACE(LOWER(TRIM(IFNULL(p.phone, ''))), ')', ''), '(', ''), '-', ''), ' ', '') AS phoneCheck,
    TRIM(REGEXP_REPLACE(CONCAT(IFNULL(p.first_name, ''),' ', IFNULL(p.middle_name, ''), ' ', IFNULL(p.last_name, ''), ' ',  
        IFNULL(p.suffix, '')), '  *', ' ')) AS fullName,
    TRIM(REGEXP_REPLACE(CONCAT(IFNULL(p.address, ''),' ', IFNULL(p.addr_2, ''), ' ', IFNULL(p.city, ''), ' ',
        IFNULL(p.state, ''), ' ', IFNULL(p.zip, ''), ' ', IFNULL(p.country, '')), '  *', ' ')) AS fullAddr,
    CASE
        WHEN mp.id IS NOT NULL THEN 
            TRIM(REGEXP_REPLACE(CONCAT(IFNULL(mp.first_name, ''),' ', IFNULL(mp.middle_name, ''), ' ',
                IFNULL(mp.last_name, ''), ' ', IFNULL(mp.suffix, '')), '  *', ' ')) 
        WHEN mn.id IS NOT NULL THEN
            TRIM(REGEXP_REPLACE(CONCAT(IFNULL(mn.first_name, ''),' ', IFNULL(mn.middle_name, ''), ' ',
                IFNULL(mn.last_name, ''), ' ', IFNULL(mn.suffix, '')), '  *', ' '))
        ELSE ''
    END AS manager,
    CASE
        WHEN mp.id IS NOT NULL THEN mp.id
        WHEN mn.id IS NOT NULL THEN mn.id
        ELSE NULL
    END AS managerId
FROM perinfo p
LEFT OUTER JOIN perinfo mp ON (p.managedBy = mp.id)
LEFT OUTER JOIN newperson mn ON (p.managedByNew = mn.id)
WHERE
EOS;

$and = '';
$typestr = '';
$valueArr = [];
if ($firstName != '') {
    $firstName2ch = mb_substr($firstName, 0, 2);
    $mQ .= $and . "(lower(p.first_name) = ? OR lower(p.first_name) like ? OR SOUNDEX(p.first_name) = SOUNDEX(?))\n";
    $and = 'AND ';
    $typestr .= 'sss';
    $valueArr[] = $firstName;
    $valueArr[] = $firstName2ch . '%';
    $valueArr[] = $firstName;
}
if ($middleName != '') {
    $middleName2ch = mb_substr($middleName, 0, 2);
    $mQ .= $and . "(lower(p.middle_name) = ? OR lower(p.middle_name) like ? OR SOUNDEX(p.middle_name) = SOUNDEX(?))\n";
    $and = 'AND ';
    $typestr .= 'sss';
    $valueArr[] = $middleName;
    $valueArr[] = $middleName2ch . '%';
    $valueArr[] = $middleName;
}
if ($lastName != '') {
    $lastName4ch = mb_substr($lastName, 0, 4);
    $mQ .= $and . "(lower(p.last_name) = ? OR lower(p.last_name) like ? OR SOUNDEX(p.last_name) = SOUNDEX(?))\n";
    $and = 'AND ';
    $typestr .= 'sss';
    $valueArr[] = $lastName;
    $valueArr[] = $lastName4ch . '%';
    $valueArr[] = $lastName;
}
if ($legalName != '') {
    $mQ .= $and . "(lower(p.legalName) = ? OR lower(p.legalName) like ?)\n";
    $and = 'AND ';
    $typestr .= 'ss';
    $valueArr[] = $legalName;
    $valueArr[] = '%' . $legalName . '%';
}
if ($badgeName != '') {
    $mQ .= $and . "(lower(p.badge_name) = ? OR lower(p.badge_name) like ?)\n";
    $and = 'AND ';
    $typestr .= 'ss';
    $valueArr[] = $badgeName;
    $valueArr[] = '%' . $badgeName . '%';
}
if ($address != '') {
    $typestr .= 'ssss';
    $valueArr[] = $address;
    $valueArr[] = '%' . $address . '%';
    $valueArr[] = $address;
    $valueArr[] = '%' . $address . '%';
    $mQ .= $and . "(lower(p.address) = ? OR lower(p.address) like ? OR lower(p.addr_2) = ? OR lower(p.addr_2) like ?)";
}
if ($addr2 != '') {
    $typestr .= 'ssss';
    $valueArr[] = $addr2;
    $valueArr[] = '%' . $addr2 . '%';
    $valueArr[] = $addr2;
    $valueArr[] = '%' . $addr2 . '%';
    $$mQ .= $and . '(lower(p.address) = ? OR lower(p.address) like ? OR lower(p.addr_2) = ? OR lower(p.addr_2) like ?)';
}

if ($city != '') {
    $mQ .= $and . "(lower(p.city) = ? OR lower(p.city) like ?\n";
    $and = 'AND ';
    $typestr .= 'ss';
    $valueArr[] = $city;
    $valueArr[] = '%' . $city . '%';
}

if ($state != '') {
    $mQ .= $and . "(lower(p.state) = ? OR lower(p.state) like ?\n";
    $and = 'AND ';
    $typestr .= 'ss';
    $valueArr[] = $state;
    $valueArr[] = '%' . $state . '%';
}

if ($zip != '') {
    $zipch = str_replace(' ', '', str_replace('-', '', $zip));
    $mQ .= $and . "REPLACE(REPLACE(' ', '', lower(p.zip)), '-', '') = ? OR REPLACE(REPLACE(' ', '', lower([/zip)), '-', '') like ?\n";
    $and = 'AND ';
    $typestr .= 'ss';
    $valueArr[] = $zipch;
    $valueArr[] = '%' . $zipch . '%';
}

if ($emailAddr != '') {
    $mQ .= $and . "(lower(p.email_addr) = ? OR lower(p.email_addr) like ?)\n";
    $and = 'AND ';
    $typestr .= 'ss';
    $valueArr[] = $emailAddr;
    $valueArr[] = '%' . $emailAddr . '%';
}

if ($phone != '') {
    $phonech = str_replace('(', '', str_replace(')', '', str_replace('-', '',
                 str_replace(' ', '', $phone))));

    $mQ .= $and . "(phoneChk = ? OR phoneChk like ?)\n";
    $and = 'AND ';
    $typestr .= 'ss';
    $valueArr[] = $phonech;
    $valueArr[] = '%' . $phonech . '%';
}
$mQ .= ";\n";

$mR = dbSafeQuery($mQ, $typestr, $valueArr);
if ($mR === false) {
    $response['error'] = 'Select potential matches failed';
    ajaxSuccess($response);
    return;
}

$pids = [];
$matches= [];
while ($match = $mR->fetch_assoc()) {
    $matches[] = $match;
    $pids[] = $match['id'];
}
$mR->free();

$response['matches'] = $matches;
$response['success'] = count($matches) . ' potential matches found';

ajaxSuccess($response);
?>
