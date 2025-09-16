<?php

// check table DML to see if the table is current
//
function checkTableDML($table, $fname) : int {
    $lines = file('Reg_Install_Schema/' . $fname);
    $dbcreateR = dbQuery("show create table $table;");
    //var_dump($dbcreateR);
    $dbcreate = $dbcreateR->fetch_row()[1];
    $dbcreate = str_replace('"', '`', $dbcreate);
    $dbcreate = explode(PHP_EOL, $dbcreate);
    //var_dump($lines);
    //var_dump($dbcreate);

    // find start of create table in file version
    for ($startLine = 0; $startLine < sizeof($lines); $startLine++) {
        if (str_starts_with($lines[$startLine], 'CREATE TABLE '))
            break;
    }
    $match = true;
    for ($sqlLine = 0; $sqlLine < sizeof($dbcreate) && $startLine < sizeof($lines); $startLine++, $sqlLine++) {
        $sql = str_replace(PHP_EOL, '', $dbcreate[$sqlLine]);
        $line = str_replace(PHP_EOL, '', $lines[$startLine]);
        //var_dump(array("start", $sqlLine, $startLine, $sql, $line));
        // skip over foreign key constraints, they are handled in zz_foreign_keys.sql
        while (preg_match('/ *CONSTRAINT .* FOREIGN KEY .*/', $sql) && $sqlLine < sizeof($dbcreate)) {
            $sqlLine++;
            $sql = str_replace(PHP_EOL, '', $dbcreate[$sqlLine]);
            //var_dump(array('in skip', $sqlLine, $startLine, $sql, $line));
        }

        if ($sql == ')')   // end of the SQL
            break;

        if ($sql == $line)
            continue;

        // possible ok mismatches: SQL has charset, but line has no charset, take charset off SQL
        if (str_contains($sql, 'CHARACTER SET utf8mb4')) {
            $nsql = str_replace('CHARACTER SET utf8mb4 ', '', $sql);
            if ($nsql == $line)
                continue;
        }

        if (str_ends_with($sql,',') && !str_ends_with($line, ',')) {
            $nsql = preg_replace('/(.*),$/', '\1', $sql);
            //var_dump($nsql);
            if ($nsql == $line)
                continue;
        }

        var_dump(array('end', $sql, $line));
        $match = false;
    }

    if (!(str_starts_with($sql, ')') && str_starts_with($line, ')'))) {
        $match = false;
        logEcho("Database DML for table $table missing lines starting with '$line'");
    }

    return $match;
}
