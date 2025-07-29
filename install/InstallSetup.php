<?php
// Need DB library
require_once(__DIR__ . '/../lib/global.php');
require_once(__DIR__ . '/../lib/db_functions.php');
require_once('lib/base.php');
require_once('lib/validateConfigMYSQL.php');
require_once('lib/validateConfigFile.php');
require_once('lib/createMissingTables.php');
require_once('lib/createMissingRecords.php');
require_once('lib/checkTableDML.php');
global $dbObject;
global $logFile;
global $options;
// setup parameters
$systemName = 'ConTroll';
$patchLevelFile = 'Reg_Install_Schema/AA_Patchlevel.txt';
$phpMajor = 8;
$phpMinor = 2;

// get command line options
$options = getopt("cfhinopstv");

if (array_key_exists('h', $options)) {
    echo <<<EOS
InstallSetup options:
    -c  Allow creation of the database (schema) if it doesn't exist.
    -f  Drop and re-apply foreign keys
    -h  Display this option list an exit.
    -i  Suppress phpinfo in logfile.
    -n  Suppress database schema checks
    -o  Overwrite the logfile if it exists, if omitted logfile will be appended.
    -p  Drop and re-apply views, functions and procedures
    -s  Validate existing database schema
    -t  Create missing tables, functions, keys, procedures
    -v  Suppress validating the config file

EOS;
    exit(0);
}
// Startup Banner - Always to STDOUT
echo "$systemName InstallSetup" . PHP_EOL;

// mandatory first check, is the PHP version greater than the minumum
$phpVersion = phpversion();
if ($phpVersion === false) {
    echo "Unable to verify PHP version, exiting" . PHP_EOL;
    exit(1);
}
$phpVerArray = explode('.', $phpVersion);
// minimum is $phpMajor.$phpMinor, don't even continue if it's less that that
if ($phpVerArray[0] < $phpMajor || $phpVerArray[1] < $phpMinor) {
    echo "$systemName requires a minimum of PHP $phpMajor.$phpMinor, you are running $phpVersion, exiting" . PHP_EOL;
    exit(2);
}
if ($phpVerArray[0] > $phpMajor) {
    echo "$systemName has not been tested on PHP versions greater than $phpMajor, letting the program continue, but be aware there probably will be issues." . PHP_EOL;
} else if ($phpVerArray[1] > $phpMinor) {
    echo "$systemName has not been tested on PHP versions greater than $phpMajor.$phpMinor, letting the program continue, but be aware there may be issues." . PHP_EOL;
} else if ($phpVerArray[2] < 2) {
    echo "Your PHP is an early release of $phpMajor.$phpMinor, if a newer release of $phpMajor.$phpMinor you should consider upgrading." . PHP_EOL;
}

echo "Current PHP Version is $phpVersion" . PHP_EOL;
echo "Please check that the WEB version is running the same version.  Note: php_fpm is recommended." . PHP_EOL;

$cwd = getcwd();
$cwdArray = explode('/', $cwd);
$last = sizeof($cwdArray) - 1;
$dirName = $cwdArray[$last];
$depoName = $cwdArray[$last - 1];
$siteName = $cwdArray[$last - 2];

if ($dirName != 'install' || ($depoName != $systemName && $depoName != 'ConTroll' && $depotName != 'BalticonReg')) {
    echo "This program must be run from the install directory in the depot, not $cwd" . PHP_EOL;
    exit(3);
}

if (!array_key_exists('i', $options)) {
    ob_start();
    phpinfo(INFO_GENERAL | INFO_CONFIGURATION | INFO_MODULES);
    $phpinfo = ob_get_contents();
    ob_end_clean();
} else {
    $phpinfo = "-i flag passed, getinfo skipped";
}

if (array_key_exists('o', $options)) {
    $mode = 'w';
    echo "Logfile is being written to $siteName/InstallSetup.log" . PHP_EOL;
} else {
    $mode = 'a';
    echo "Logfile is being appended to $siteName/InstallSetup.log" . PHP_EOL;
}

$logFile = fopen('../../InstallSetup.log', $mode);
fwrite($logFile, PHP_EOL . PHP_EOL . date('Y-m-d H:i:s') . ": Start of $systemName" . PHP_EOL . "Git Depo Path: $siteName/$depoName" . PHP_EOL . PHP_EOL);
fwrite($logFile, "PHP Command Line Version: " . $phpVersion . PHP_EOL . $phpinfo . PHP_EOL . PHP_EOL);

// Validating the config file - db section so it can check for a database
$error = validateConfigMYSQL($options);
if ($error) {
    echo "Exiting due to errors in the [MYSQL] portion of the config file." . PHP_EOL;
    fclose($logFile);
    exit($error);
}

if (array_key_exists('n', $options)) {
    logEcho("Skipping database schema creation/checks due to -n option");
} else {
    $error = createMissingTables($options);
    if ($error) {
        echo 'Exiting due to errors creating all of the missing tables in the database.' . PHP_EOL;
        fclose($logFile);
        exit($error);
    }
}

$error = createMissingRecords($options);
if ($error) {
    echo 'Exiting due to errors creating missing records in the databas.' . PHP_EOL;
    fclose($logFile);
    exit($error);
}

if (is_readable($patchLevelFile)) {
    $lines = file($patchLevelFile);
    if (str_starts_with($lines[0],'Current=')) {
        $current = str_replace('Current=', '', $lines[0]);
        $current = str_replace(PHP_EOL, '', $current);
        $checkSQL = <<<EOS
SELECT MAX(id)
FROM patchLog;
EOS;
        $checkR = dbQuery($checkSQL);
        if ($checkR === false) {
            logEcho("Unable to check patchlevel, query failed");
        } else if ($checkR->num_rows != 1) {
            logEcho("No patches are in the system, but the current Schema Patch Level for this release is $current");
        } else {
            $dblevel = $checkR->fetch_row()[0];
            if ($dblevel == $current) {
                logEcho("Database and system are current at patch# $current");
            } else {
                logEcho("Database is at patch# $dblevel, but the system is at patch# $current");
            }
        }
    }

} else {
    logEcho("Inable to check database patch level because the file Reg_Install_Schema/AA_Patchlevel.txt is missing");
}

if (array_key_exists('v', $options)) {
    logEcho('Skipping configuration file validation due to the -v option');
} else {
    $error = validateConfigFile($options);
    if ($error) {
        echo 'Exiting due to errors in the config file.' . PHP_EOL;
        fclose($logFile);
        exit($error);
    }
}

fclose($logFile);
exit(0);
