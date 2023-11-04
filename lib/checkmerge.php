<?php
// check merge, check the perinfo table for potential merge records


function checkmerge($remainPid = 0, $min_matches = 5) {
    $response = [];

// get the perinfo table for all (if = 0, or just the singe pid for != 0
    $perinfoQ = <<<EOS
SELECT id, last_name, first_name, middle_name, suffix, badge_name, email_addr, address, addr_2, city, state, zip, country
FROM perinfo
EOS;

    if ($remainPid > 0) {
        $perinfoQ .= "\nWHERE id = ?;";
        $perinfoR = dbSafeQuery($perinfoQ, 'i', array($remainPid));
    } else {
        $perinfoQ .= ";\n";
        $perinfoR = dbQuery($perinfoQ);
    }

    $perinfo = [];
    $matches = [];

    while ($perinfoL = $perinfoR->fetch_assoc()) {
        $perinfo[] = $perinfoL;
    }

    $response['perinfo'] = $perinfo;

    if ($perinfoR->num_rows == 0) {
        $response['error'] = 'No matching person for remaining perId';
        return $response;
    }

    $allPerinfo = [];
    $allPerinfoR = dbQuery("SELECT id, last_name, first_name, middle_name, suffix, badge_name, email_addr, address, addr_2, city, state, zip, country FROM perinfo;");
    while ($allL = $allPerinfoR->fetch_assoc()) {
        $allPerinfo[] = $allL;
    }

    // now check every row against every other row for matches

    $fields = array_keys($perinfo[0]);
    foreach ($perinfo as $row) {
        $rowkey = 'r' . $row['id'];
        // loop over each rowb computing the count of matching fields, not counting spaces, or empty fields
        foreach ($allPerinfo as $checkrow) {
            if ($checkrow['id'] <= $row['id'])
                continue;
            $matchkey = 'r' . $checkrow['id'];
            $match = 0;
            foreach ($fields as $key) {
                if (array_key_exists($key, $row) && array_key_exists($key, $checkrow) && $row[$key] !== null && $checkrow[$key] !== null) {
                    if (strlen($row[$key]) > 0 && $row[$key] != '/n') {
                        if (strtolower(trim($row[$key])) == strtolower(trim($checkrow[$key])))
                            $match++;
                    }
                }
            }
            if ($match >= $min_matches) {
                $matches[$rowkey][$matchkey] = $match;
            }
        }
    }

    $response['matches'] = $matches;

    $values = [];
    foreach ($matches as $key => $match) {
        $ids = '(' . substr($key, 1);
        foreach ($match as $mkey => $count) {
            $ids .= ', ' . substr($mkey, 1);
        }
        $ids .= ');';
        $permatchR = dbQuery('SELECT id, last_name, first_name, middle_name, suffix, badge_name, email_addr, address, addr_2, city, state, zip, country FROM perinfo WHERE id IN ' . $ids);
        $values[$key] = [];
        while ($permatchL = $permatchR->fetch_assoc())
            $values[$key][] = $permatchL;
    }

    $response['values'] = $values;
    $response['success'] = 'success';
    return $response;
}
