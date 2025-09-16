<?php

// validate MYSQL portion of config file
//  options: the array returned by getoptions.
function validateConfigMYSQL($options) : int {
    logEcho("Validating [mysql] section of reg_conf.ini");
    $mysqlConf = get_conf('mysql');
    if ($mysqlConf === null || $mysqlConf === false) {
        logEcho("Error: [mysql] section of reg_conf.ini is missing, cannot continue");
        return 1;
    }

    if (sizeof($mysqlConf) < 1) {
        logEcho("Error: [mysql] section of reg_conf.ini is empty, cannot continue");
    }

    // required contents of [mysql]
    $reqVars = array(
        'host' => "Host name without port",
        'user' => "mysql user name",
        'password' =>  "mysql password for user",
        'db_name' => "mysql database (schema) name"
    );
    $optVars = array(
        'port' => '3306',
        'sql_mode' => "REAL_AS_FLOAT,PIPES_AS_CONCAT,ANSI_QUOTES,IGNORE_SPACE,ONLY_FULL_GROUP_BY,ANSI,STRICT_ALL_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION",
        'db_timezone' => "US/Eastern",
        'php_timezone' => "AMERICA/NEW_YORK"
    );

    $errors = 0;
    foreach ($reqVars AS $key => $desc) {
        if (array_key_exists($key, $mysqlConf)) {
            if ($key == 'host') {
                if (str_contains($mysqlConf[$key], ':')) {
                    $msg = <<<EOS
Warning: the host parameter has a ':' in it,
if this is an IPv6 hard coded address, please us DNS name, like 'localhost'.
  
Do not put the port on the host line,
use the port line in the configuration file
EOS;
                    logEcho($msg);
                }
            }
            if (strlen($mysqlConf[$key]) < 1) {
                logEcho("$key is empty, needs to contain '$desc'");
                $errors++;
            }
        } else {
            logEcho("$key is missing, needs to contain '$desc'");
            $errors++;
        }
    }

    foreach ($optVars AS $key => $example) {
        if (array_key_exists($key, $mysqlConf)) {
            if (strlen($mysqlConf[$key]) < 1) {
                logEcho("$key is empty, example of proper contents is" . PHP_EOL . "'$example'");
                $errors++;
            }
        } else {
            logEcho("Warning: $key is missing and is usually required, example of proper contents is " . PHP_EOL . "'$example'");
        }
    }

    if ($errors == 0) {
        // attempt to open the database
        if (db_connect(true)) {
            logEcho('Connected to database server');
        } else {
            logEcho('Unable to connect to database, check reg_conf.ini[mysql] connecton parameters');
            $errors++;
        }

        // see if the schema exists
        $dbName = $mysqlConf['db_name'];
        $dbServer = $mysqlConf['host'];
        $dbUser = $mysqlConf['user'];
        $schemaQ = <<<EOS
SELECT SCHEMA_NAME
FROM INFORMATION_SCHEMA.SCHEMATA
WHERE SCHEMA_NAME = ?;
EOS;
        $schemaR = dbSafeQuery($schemaQ, 's', array($dbName));
        // one row means the database exists and the name is in the schema, try and access that database
        if ($schemaR->num_rows == 1) {
            db_close();
            if (db_connect() == false) {
                logEcho("You have no access to the $dbName database");
                $errors++;
            }
        } else {
            // not 1 means the database does not exist, without the 'c' parameter there is no permission to create it
            if (array_key_exists('c', $options)) {
                // load the create schema command from Reg_Install_Schema/create_reg_schema.sql
                $create_sql = file_get_contents('Reg_Install_Schema/create_reg_schema.sql');
                if ($create_sql === false) {
                    logEcho("Unable to load create schema config from Reg_Install_Schema/create_reg_schema.sql.");
                    $errors++;
                } else {
                    $create_sql = str_replace('"reg"', '"' . $dbName . '"', $create_sql);
                    logEcho("Creating Database $dbName");
                    logEcho($create_sql, true);
                    $num_rows = dbCmd($create_sql);
                    if ($num_rows === false) {
                        $msg = <<<EOS

Unable to create the database $dbName

This is most likely because the database user $dbUser
does not have the permissions to create databases.

Get a database user with the proper permissions to run the SQL command:
$create_sql

and then give the database user $dbUser all rights to the database $dbName.
 
EOS;

                        logEcho($msg);
                        $errors++;
                    } else {
                        db_close();
                        if (db_connect() == false) {
                            logEcho("Schema Create did not give you access to $dbName database");
                            $errors++;
                        }
                    }
                }
            } else {
                $msg = <<<EOS

The database $dbName does not exist on $dbServer,
and you did not give the -c option to allow trying to create it.

If this system is running CWP or CPanel, 
    use the create database menu item in your account portal.
If you know you have create database permissions for user $dbUser, then
    if you wish to have this InstallSystem.php create the database, 
    rerun the InstallSystem.php program with the '-c' option.

If you are unsure if you have permissions, then create the database
outside of this program using a database user that does have the permissions.

EOS;

                logEcho($msg);
                $errors++;
            }
        }
    }

    logEcho('Completed validating [mysql] section of reg_conf.ini');
    if ($errors > 0)
        logEcho("Errors in [mysql] section of reg_conf.ini, cannot continue");
    return $errors;
}
