<?php
// getLoginMatch: retrieve all rows from perinfo and newperinfo that match the info the user provided

function getLoginMatch($email, $id = null) {
    $response = [];
// first get the perid items
    if ($id != NULL) {
        $regcountQ = <<<EOS
SELECT id, last_name, first_name, middle_name, suffix, email_addr, phone, badge_name, legalName, address, addr_2, city, state, zip, country, creation_date, update_date, active, banned,
    TRIM(REGEXP_REPLACE(CONCAT(IFNULL(last_name, ''), ', ', IFNULL(first_name, ''),' ', IFNULL(middle_name, ''), ' ', IFNULL(suffix, '')), '  *', ' ')) AS fullname, 'p' AS tablename
FROM perinfo
WHERE email_addr = ? AND id = ?;
EOS;
        $regcountR = dbSafeQuery($regcountQ, 'si', array($email, $id));
    } else {
        $regcountQ = <<<EOS
SELECT id, last_name, first_name, middle_name, suffix, email_addr, phone, badge_name, legalName, address, addr_2, city, state, zip, country, creation_date, update_date, active, banned,
    TRIM(REGEXP_REPLACE(CONCAT(IFNULL(last_name, ''), ', ', IFNULL(first_name, ''),' ', IFNULL(middle_name, ''), ' ', IFNULL(suffix, '')), '  *', ' ')) AS fullname, 'p' AS tablename
FROM perinfo
WHERE email_addr = ? AND first_name != 'Merged' AND middle_name != 'into'
ORDER BY fullname;
EOS;
        $regcountR = dbSafeQuery($regcountQ, 's', array($email));
    }
    if ($regcountR == false) {
        return('Query Error - seek assistance');
    }
    $matches = [];
    $count = $regcountR->num_rows;
    while ($person = $regcountR->fetch_assoc()) {
        $matches[] = $person;
    }
    $regcountR->free();

// now add in the newperson records
    if ($id != NULL) {
        $regcountQ = <<<EOS
SELECT n.id, n.last_name, n.first_name, n.middle_name, n.suffix, n.email_addr, n.phone, n.badge_name, n.legalName, n.address, n.addr_2, n.city, n.state, n.zip, n.country,
    n.createtime AS creation_date, 'Y' AS active, 'N' AS banned,
    TRIM(REGEXP_REPLACE(CONCAT(IFNULL(n.last_name, ''), ', ', IFNULL(n.first_name, ''),' ', IFNULL(n.middle_name, ''), ' ', IFNULL(n.suffix, '')), '  *', ' ')) AS fullname, 'n' AS tablename
FROM newperson n
LEFT OUTER JOIN perinfo p ON n.perid = p.id
WHERE n.email_addr = ? AND n.id = ? AND p.id IS NULL
ORDER BY fullname;
EOS;
        $regcountR = dbSafeQuery($regcountQ, 'si', array($email, $id));
    } else {
        $regcountQ = <<<EOS
SELECT n.id, n.last_name, n.first_name, n.middle_name, n.suffix, n.email_addr, n.phone, n.badge_name, n.legalName, n.address, n.addr_2, n.city, n.state, n.zip, n.country,
    createtime AS creation_date, 'Y' AS active, 'N' AS banned,
    TRIM(REGEXP_REPLACE(CONCAT(IFNULL(n.last_name, ''), ', ', IFNULL(n.first_name, ''),' ', IFNULL(n.middle_name, ''), ' ', IFNULL(n.suffix, '')), '  *', ' ')) AS fullname, 'n' AS tablename
FROM newperson n
LEFT OUTER JOIN perinfo p ON n.perid = p.id
WHERE n.email_addr = ? AND p.id IS NULL
ORDER BY fullname;
EOS;
        $regcountR = dbSafeQuery($regcountQ, 's', array($email));
    }

    if ($regcountR == false) {
        return('Query Error - seek assistance');
    }

    $count += $regcountR->num_rows;
    $response['count'] = $count;
    while ($person = $regcountR->fetch_assoc()) {
        $matches[] = $person;
    }
    $regcountR->free();
    $response['matches'] = $matches;

    if ($count == 0) {
        $response['error'] = 'No matching emails found';
    } else if ($count == 1) {
        $_SESSION['id'] = $matches[0]['id'];
        $_SESSION['idType'] = $matches[0]['tablename'];
        $response['status'] = 'success';
    }
    return $response;
}
