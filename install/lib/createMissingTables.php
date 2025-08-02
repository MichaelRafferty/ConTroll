<?php

// checks for completeness of schema, adding tables, data or procs/key as needed
//  options: the array returned by getoptions.
function createMissingTables($options) : int {
    $errors = 0;
    $mysqlConf = get_conf('mysql');
    logEcho('Checking for missing tables/functions/procedures/keys in the database ' . $mysqlConf['db_name']);

    // get list of tables;
    $tables = [];
    $sql = "show tables;";
    $sqlR = dbQuery($sql);
    while ($row = $sqlR->fetch_row()) {
        $tables[$row[0]] = true;
    }
    $sqlR->free();

    // process the sql scripts in the Reg_Install_Schema directory
    $dir = new DirectoryIterator('Reg_Install_Schema');
    $dataLoads = [];
    $procLoads = [];

    // loop over each file
    foreach ($dir as $entry) {
        if (!$entry->isFIle())
            continue;

        $fname = $entry->getFileName();
        // skip non SQL files
        if (!str_ends_with($fname, '.sql')) {
            continue;
        }
        // save data loads for last
        if (str_starts_with($fname, 'data_')) {
            $dataLoads[] = $fname;
            continue;
        }
        // save procedure/view loads for later
        if (str_starts_with($fname, 'zz_')) {
            $procLoads[] = $fname;
            continue;
        }
        // skip over the schema load, it's already been handled
        if ($fname == 'create_reg_schema.sql')
            continue;

        // ok we have a schema file to process
        $table = preg_replace('/^[^_]*_(.*)\.sql$/', '\1', $fname);

        if (array_key_exists($table, $tables)) {
            if (array_key_exists('s', $options)) {
                if (checkTableDML($table, $fname)) {
                    logEcho("$table exists in the database and is current", true);
                } else {
                    logEcho("$table exists in the database and is out of date");
                }
            } else {
                logEcho("$table exists in the database", true);
            }
        } else {
            if (array_key_exists('c', $options)) {
                $sql = file_get_contents('Reg_Install_Schema/' . $fname);
                logEcho("Creating $table from $fname");
                dbMultiQuery($sql);
                // skip over result sets
                dbNextResult();
                dbNextResult();
                dbNextResult();
                // PHP mysqli isn't returning a valid result from a DML multi-query, use select to verify it actually worked
                $sql = <<<EOS
SELECT TABLE_SCHEMA, TABLE_NAME, TABLE_TYPE
FROM  information_schema.TABLES 
WHERE TABLE_SCHEMA LIKE ? AND TABLE_TYPE LIKE 'BASE TABLE' AND TABLE_NAME = ?;
EOS;
                $sqlR = dbSafeQuery($sql, 'ss', array($mysqlConf['db_name'], $table));
                if ($sqlR === false || $sqlR->num_rows <= 0) {
                    logEcho("Unable to create $table");
                    $errors++;
                }
            } else {
                logEcho("$table is not in the database, need -c to create tables");
                $errors++;
            }
        }
    }

    if ($errors == 0) {
        if (sizeof($dataLoads) > 0) {
            logEcho("Creating initial data values");
            foreach ($dataLoads as $fname) {
                $table = preg_replace('/^[^_]*_(.*)\.sql$/', '\1', $fname);
                $checkSQLR = dbQuery("SELECT count(*) AS occurs FROM $table;");
                if ($checkSQLR === false) {
                    logEcho("Error querying $table for rowcount");
                    $errors++;
                }
                $rowcnt = $checkSQLR->fetch_row()[0];
                if ($rowcnt > 0) {
                    logEcho("Skipping table $table as it already has $rowcnt rows of data");
                    continue;
                }
                $sql = file_get_contents('Reg_Install_Schema/' . $fname);
                logEcho("Loading $table from $fname");
                $set = dbMultiQuery($sql);
                // skip over result sets
                dbNextResult();
                dbNextResult();
                dbNextResult();
                dbNextResult();
                dbNextResult();
                $checkSQLR = dbQuery("SELECT count(*) AS occurs FROM $table;");
                if ($checkSQLR === false) {
                    logEcho("Error querying $table for rowcount");
                    $errors++;
                }
                $rowcnt = $checkSQLR->fetch_row()[0];
                if ($rowcnt <= 0) {
                    logEcho("Error inserting data into $table");
                    $errors++;
                }
            }
        }
    }

        // zz_ are procs to create, views to create and foreign keys on tables

    if ($errors == 0) {
        if (sizeof($procLoads) > 0)  {
            logEcho('Processing post creation scripts');
            foreach ($procLoads as $fname) {
                if ($fname == 'zz_foreign_keys.sql') {
                    $keys = file('Reg_Install_Schema/' . $fname);
                    // process foreign keys one at a time
                    foreach ($keys as $key) {
                        $key = str_replace(PHP_EOL, '', $key);
                        // ALTER TABLE artshow_reg ADD CONSTRAINT `artshow_reg_conid_fk` FOREIGN KEY (`conid`) REFERENCES `conlist` (`id`) ON UPDATE CASCADE;
                        $table = preg_replace('/ALTER TABLE ([^ ]*).*/', '$1', $key);
                        $constraint = preg_replace('/.*ADD CONSTRAINT ([^ ]*).*/', '$1', $key);
                        $table = str_replace('`','', $table);
                        $constraint = str_replace('`','', $constraint);
                        // check if key already exists
                        $checkSQL = <<<EOS
SELECT constraint_name
FROM information_schema.REFERENTIAL_CONSTRAINTS
WHERE CONSTRAINT_SCHEMA = ?
AND TABLE_NAME = ?
AND CONSTRAINT_NAME = ?;
EOS;
                        $params = array($mysqlConf['db_name'], $table, $constraint);
                        $checkR = dbSafeQuery($checkSQL, 'sss', $params);
                        if ($checkR->num_rows > 0) {
                            if (array_key_exists('f', $options)) {
                                $dropSQL = <<<EOS
ALTER TABLE $table
DROP CONSTRAINT `$constraint`;
EOS;
                                dbCmd($dropSQL);
                            } else {
                                logEcho("Constraint $constraint exists for $table", true);
                                continue;
                            }
                        }
                        $num_rows = dbCmd($key);
                        if ($num_rows === false) {
                            logEcho("Adding constraint $constraint to table $table failed");
                            $errors++;
                        } else {
                            logEcho("Constraint $constraint for table $table added");
                        }
                    }
                } else {
                    $procsqlLines = file('Reg_Install_Schema/' . $fname);
                    $procSQL = [];
                    $procType = [];
                    $curproc = '';
                    $cursql = '';
                    $curtype = '';
                    for ($startLine = 0; $startLine < sizeof($procsqlLines); $startLine++) {
                        $line = str_replace(PHP_EOL, '', $procsqlLines[$startLine]);
                        // not running within mysql command line processor, we don't use delimiter replacement for it's parser
                        IF (str_starts_with($line, 'DELIMITER '))
                            continue;
                        if ($line == 'END ;;')
                            $line = 'END;';
                        IF (preg_match('/ *-- /', $line)) {
                            continue;
                        }


                        if (str_starts_with($line, 'DROP ')) {
                            if ($curproc != '') {
                                $procSQL[$curproc] = $cursql;
                                $procType[$curproc] = $curtype;
                            }
                            $curproc = preg_replace('/^DROP [^`"]*[`"]([^`"]*).*$/', '\1', $line);
                            $cursql = ''; //$line . PHP_EOL;
                            $curtype = preg_replace('/^DROP ([^ ]*).*$/', '\1', $line);
                            continue;
                        }
                        if (str_starts_with($line, 'CREATE FUNCTION "') || str_starts_with($line, 'CREATE PROCEDURE "')) {
                            $line = preg_replace('/(CREATE[ ]+[^ ]+[ ]+)"([^"]+)"(.*)$/', '\1\2\3', $line);
                        }
                        $cursql .= $line . PHP_EOL;
                    }
                    if ($curproc != '') {
                        $procSQL[$curproc] = $cursql;
                        $procType[$curproc] = strtoupper($curtype);
                    }
                    logEcho("Processing Views/Procedures/Functions");
                    foreach ($procSQL as $item => $sql) {
                        $pt = $procType[$item];
                        switch ($pt) {
                            case 'VIEW':
                                $checkSQL = <<<EOS
SELECT TABLE_NAME
FROM information_schema.VIEWS
WHERE TABLE_SCHEMA = ?
AND TABLE_NAME = ?;
EOS;
                                break;
                            case 'FUNCTION':
                                $checkSQL = <<<EOS
SELECT ROUTINE_NAME
FROM information_schema.ROUTINES
WHERE ROUTINE_SCHEMA = ?
AND ROUTINE_TYPE = 'FUNCTION'
AND ROUTINE_NAME = ?;
EOS;
                                break;
                            case 'PROCEDURE':
                                $checkSQL = <<<EOS
SELECT ROUTINE_NAME
FROM information_schema.ROUTINES
WHERE ROUTINE_SCHEMA = ?
AND ROUTINE_TYPE = 'PROCEDURE'
AND ROUTINE_NAME = ?;
EOS;
                                break;
                        }
                        $checkSQLR = dbSafeQuery($checkSQL, 'ss', array($mysqlConf['db_name'], $item));
                        if ($checkSQLR === false || $checkSQLR->num_rows <= 0) {
                            logEcho("Adding $pt $item");
                            $cR = dbQuery($sql);
                            if ($cR === false) {
                                logEcho("You don't have sufficient permissions to add this $pt, Please seek help from a Database Administrator to create $item from zz_routines.sql");
                                //exit();
                            }
                        } else if (array_key_exists('p', $options)) { // replace view/proc/functions
                            logEcho("Drop/Replace $pt $item");
                            dbQuery("DROP $pt IF EXISTS `$item`;");
                            $cR = dbQuery($sql);
                            if ($cR === false) {
                                logEcho("You don't have sufficient permissions to add this $pt, Please seek help from a Database Administrator to create $item from zz_routines.sql");
                                //exit();
                            }
                        } else {
                            logEcho("$pt $item exists", true);
                        }
                    }
                }
            }
        }
    }

    logEcho('Completed adding missing items from '. $mysqlConf['db_name']);
    if ($errors > 0)
        logEcho("Errors while adding missing items, cannot continue");
    return $errors;
}
