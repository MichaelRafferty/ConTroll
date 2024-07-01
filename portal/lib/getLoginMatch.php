<?php
// getLoginMatch: retrieve all rows from perinfo and newperinfo that match the info the user provided

function getLoginMatch($email, $id = null, $validationType = null) {
    $response = [];
    // first the perinfo table items
// check if it's a numeric response
    if (is_numeric($email)) {
        $regcountQ = <<<EOS
SELECT id, last_name, first_name, middle_name, suffix, email_addr, phone, badge_name, legalName, address, addr_2, city, state, zip, country, creation_date, update_date, active, banned,
    CASE 
        WHEN IFNULL(last_name, '') != '' THEN
            TRIM(REGEXP_REPLACE(CONCAT(IFNULL(last_name, ''), ', ', IFNULL(first_name, ''),' ', IFNULL(middle_name, ''), ' ', IFNULL(suffix, '')), '  *', ' ')) 
        ELSE
            TRIM(REGEXP_REPLACE(CONCAT(IFNULL(first_name, ''),' ', IFNULL(middle_name, ''), ' ', IFNULL(suffix, '')), '  *', ' ')) 
        END AS fullname,
    'p' AS tablename
FROM perinfo
WHERE id = ? AND IFNULL(first_name,'') != 'Merged' AND IFNULL(middle_name,'') != 'into';
EOS;
        $regcountR = dbSafeQuery($regcountQ, 'i', array($email));
    } else if ($id != NULL) {
// first get the perid items
        $regcountQ = <<<EOS
SELECT id, last_name, first_name, middle_name, suffix, email_addr, phone, badge_name, legalName, address, addr_2, city, state, zip, country, creation_date, update_date, active, banned,
    CASE 
        WHEN IFNULL(last_name, '') != '' THEN
            TRIM(REGEXP_REPLACE(CONCAT(IFNULL(last_name, ''), ', ', IFNULL(first_name, ''),' ', IFNULL(middle_name, ''), ' ', IFNULL(suffix, '')), '  *', ' ')) 
        ELSE
            TRIM(REGEXP_REPLACE(CONCAT(IFNULL(first_name, ''),' ', IFNULL(middle_name, ''), ' ', IFNULL(suffix, '')), '  *', ' ')) 
        END AS fullname,
    'p' AS tablename
FROM perinfo
WHERE email_addr = ? AND id = ?;
EOS;
        $regcountR = dbSafeQuery($regcountQ, 'si', array($email, $id));
    } else {
        $regcountQ = <<<EOS
SELECT id, last_name, first_name, middle_name, suffix, email_addr, phone, badge_name, legalName, address, addr_2, city, state, zip, country, creation_date, update_date, active, banned,
    CASE 
        WHEN IFNULL(last_name, '') != '' THEN
            TRIM(REGEXP_REPLACE(CONCAT(IFNULL(last_name, ''), ', ', IFNULL(first_name, ''),' ', IFNULL(middle_name, ''), ' ', IFNULL(suffix, '')), '  *', ' ')) 
        ELSE
            TRIM(REGEXP_REPLACE(CONCAT(IFNULL(first_name, ''),' ', IFNULL(middle_name, ''), ' ', IFNULL(suffix, '')), '  *', ' ')) 
        END AS fullname,
    'p' AS tablename
FROM perinfo
WHERE email_addr = ? AND IFNULL(first_name,'') != 'Merged' AND IFNULL(middle_name,'') != 'into'
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
    if (is_numeric($email)) {
        $regcountQ = <<<EOS
SELECT n.id, n.last_name, n.first_name, n.middle_name, n.suffix, n.email_addr, n.phone, n.badge_name, n.legalName, n.address, n.addr_2, n.city, n.state, n.zip, n.country,
    createtime AS creation_date, 'Y' AS active, 'N' AS banned,
    CASE 
        WHEN IFNULL(n.last_name, '') != '' THEN
            TRIM(REGEXP_REPLACE(CONCAT(IFNULL(n.last_name, ''), ', ', IFNULL(n.first_name, ''),' ', IFNULL(n.middle_name, ''), ' ', IFNULL(n.suffix, '')), '  
            *', ' ')) 
        ELSE
            TRIM(REGEXP_REPLACE(CONCAT(IFNULL(n.first_name, ''),' ', IFNULL(n.middle_name, ''), ' ', IFNULL(n.suffix, '')), '  *', ' ')) 
        END AS fullname,
    'n' AS tablename
FROM newperson n
LEFT OUTER JOIN perinfo p ON n.perid = p.id
WHERE n.id = ? AND p.id IS NULL
ORDER BY fullname;
EOS;
        $regcountR = dbSafeQuery($regcountQ, 'i', array($email));
    } else if ($id != NULL) {
        $regcountQ = <<<EOS
SELECT n.id, n.last_name, n.first_name, n.middle_name, n.suffix, n.email_addr, n.phone, n.badge_name, n.legalName, n.address, n.addr_2, n.city, n.state, n.zip, n.country,
    n.createtime AS creation_date, 'Y' AS active, 'N' AS banned,
    CASE 
        WHEN IFNULL(n.last_name, '') != '' THEN
            TRIM(REGEXP_REPLACE(CONCAT(IFNULL(n.last_name, ''), ', ', IFNULL(n.first_name, ''),' ', IFNULL(n.middle_name, ''), ' ', IFNULL(n.suffix, '')), '  
            *', ' ')) 
        ELSE
            TRIM(REGEXP_REPLACE(CONCAT(IFNULL(n.first_name, ''),' ', IFNULL(n.middle_name, ''), ' ', IFNULL(n.suffix, '')), '  *', ' ')) 
        END AS fullname,
    'n' AS tablename
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
    CASE 
        WHEN IFNULL(n.last_name, '') != '' THEN
            TRIM(REGEXP_REPLACE(CONCAT(IFNULL(n.last_name, ''), ', ', IFNULL(n.first_name, ''),' ', IFNULL(n.middle_name, ''), ' ', IFNULL(n.suffix, '')), '  
            *', ' ')) 
        ELSE
            TRIM(REGEXP_REPLACE(CONCAT(IFNULL(n.first_name, ''),' ', IFNULL(n.middle_name, ''), ' ', IFNULL(n.suffix, '')), '  *', ' ')) 
        END AS fullname,
    'n' AS tablename
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

    // now lets add in the perinfoIdentity items (which just give us alternate emails to look for
    // if the provider is known, we search for that provider, else we search for email as the provider.
    if (isSessionVar('oauth2')) {
        $regcountQ = <<<EOS
SELECT id, last_name, first_name, middle_name, suffix, p.email_addr, phone, badge_name, legalName, address, addr_2, city, state, zip, country, creation_date, 
update_date, active, banned,
    CASE 
        WHEN IFNULL(last_name, '') != '' THEN
            TRIM(REGEXP_REPLACE(CONCAT(IFNULL(last_name, ''), ', ', IFNULL(first_name, ''),' ', IFNULL(middle_name, ''), ' ', IFNULL(suffix, '')), '  *', ' ')) 
        ELSE
            TRIM(REGEXP_REPLACE(CONCAT(IFNULL(first_name, ''),' ', IFNULL(middle_name, ''), ' ', IFNULL(suffix, '')), '  *', ' ')) 
        END AS fullname,
    'p' AS tablename
FROM perinfoIdentities pi
JOIN perinfo p ON (p.id = pi.perid)
WHERE pi.email_addr = ? AND pi.provider = ? AND (pi.subscriberID = ? OR pi.subscriberID IS NULL) 
  AND pi.email_addr != p.email_addr
  AND IFNULL(first_name,'') != 'Merged' AND IFNULL(middle_name,'') != 'into';
EOS;
        $regcountR = dbSafeQuery($regcountQ, 'sss', array($email, getSessionVar('oauth2'), getSessionVar('subscriberId')));
        if ($regcountR == false) {
            return('Query Error - seek assistance');
        }

        $count += $regcountR->num_rows;
        $response['count'] = $count;
        while ($person = $regcountR->fetch_assoc()) {
            $matches[] = $person;
        }
        $regcountR->free();
    }

    if ($validationType != null && $validationType == 'token') {
        $regcountQ = <<<EOS
SELECT id, last_name, first_name, middle_name, suffix, p.email_addr, phone, badge_name, legalName, address, addr_2, city, state, zip, country, creation_date, 
update_date, active, banned,
    CASE 
        WHEN IFNULL(last_name, '') != '' THEN
            TRIM(REGEXP_REPLACE(CONCAT(IFNULL(last_name, ''), ', ', IFNULL(first_name, ''),' ', IFNULL(middle_name, ''), ' ', IFNULL(suffix, '')), '  *', ' ')) 
        ELSE
            TRIM(REGEXP_REPLACE(CONCAT(IFNULL(first_name, ''),' ', IFNULL(middle_name, ''), ' ', IFNULL(suffix, '')), '  *', ' ')) 
        END AS fullname,
    'p' AS tablename
FROM perinfoIdentities pi
JOIN perinfo p ON (p.id = pi.perid)
WHERE pi.email_addr = ? AND pi.provider = ? AND pi.email_addr != p.email_addr
  AND IFNULL(first_name,'') != 'Merged' AND IFNULL(middle_name,'') != 'into';
EOS;
        $regcountR = dbSafeQuery($regcountQ, 'ss', array($email, 'email'));
        if ($regcountR == false) {
            return('Query Error - seek assistance');
        }

        $count += $regcountR->num_rows;
        $response['count'] = $count;
        while ($person = $regcountR->fetch_assoc()) {
            $matches[] = $person;
        }
        $regcountR->free();
    }

    $response['matches'] = $matches;

    // now we have them all
    if ($count == 0) {
        $response['error'] = 'No matching emails found';
    } else if ($count == 1) {
        setSessionVar('id', $matches[0]['id']);
        setSessionVar('idType', $matches[0]['tablename']);
        $response['status'] = 'success';
    }
    return $response;
}
