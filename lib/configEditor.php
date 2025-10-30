<?php
// ConTroll Registration System, Copyright 2015-2025, Michael Rafferty, Licensed under the GNU Affero General Public License, Version 3.
// library AJAX Processor: configEditor.php
// Author: Syd Weinstein
// all common functions related to the configuration editor

global $filePath, $controlPath, $configFile;

// find the config directory based on our current directory, it may be up one or two more than where we are
function setConfigDirs() : void {
    global $filePath, $controlPath, $configFile;

    $configDir = '../config';
    $sampleDir = '../config-sample';
    if (!is_dir($configDir)) {
        $configDir = "../$configDir";
        $sampleDir = "../$sampleDir";
    }
    if (!is_dir($configDir)) {
        $configDir = "../$configDir";
        $sampleDir = "../$sampleDir";
    }

    $configFile = 'reg_conf.ini';
    $controlFile = 'reg_conf.ini.sample';
    $filePath = "$configDir/$configFile";
    $controlPath = "$sampleDir/$controlFile";
}

function configLock($user_perid) : string {
    global $filePath, $configFile;

    $lockfile = "$filePath/$configFile" . ".lock";
    $now = time();
    if (is_readable($lockfile)) {
        $lock = file($lockfile, FILE_IGNORE_NEW_LINES);
        $lock = $lock[0];
        [$file_perid, $file_time] = explode(',', $lock);
        $age = 300 - ($now - $file_time);
        if ($age > 0 && $file_perid != $user_perid) {
            error_log("$lockfile locked at  $file_time by $file_perid, requested to lock by $user_perid");
            $age = ($age / 60) + 1;
            return "File is locked by $file_perid, please try again after $age minutes.";
        }
    }

    // ok, there might be a lock, but we need to write a new one, it's expired, or is from us
    $lock = "$user_perid,$now\n";
    $fileHandle = fopen($lockfile, 'w');
    fwrite($fileHandle, $lock);
    flock($fileHandle, LOCK_EX);
    return '';
}

// remove a lock file, we are done with it
function configUnlock($user_perid) : void {
    global $filePath, $configFile;

    $lockfile = "$filePath/$configFile" . '.lock';
    error_log("$lockfile unlocked by $user_perid");
    unlink($lockfile);
}

// load all new data for the config editor
function loadConfigEditor($perm, $auths) : array {
    global $filePath, $controlPath, $configFile;

    if ($controlPath)
// loadData:
    if (!is_readable($filePath)) {
        $response['error'] = "Configuration file $configFile does not exist";
        ajaxSuccess($response);
        exit();
    }
    if (!is_writable($filePath)) {
        $response['error'] = "Configuration file $configFile does not have write permission to be updated";
        ajaxSuccess($response);
        exit();
    }
    if (!is_readable($controlPath)) {
        $response['error'] = "Master control file for $configFile does not exist";
        ajaxSuccess($response);
        exit();
    }
    $response = array ();
//  load the reg_conf.ini into a special array for return
    $current_config = parse_ini_file($filePath, true);
    $response['currentConfig'] = $current_config;

// now load the configuration file
    $master = file($controlPath, FILE_IGNORE_NEW_LINES);
    $control = [];
    $sections = [];
    $section = [];
    $config = [];
    $sectionName = '';
    $curFieldName = '';
// parse the file
    $lineNo = 0;
    foreach ($master as $line) {
        $lineNo++;
        if (preg_match('/^\s*\[[^]]+]\s*$/', $line)) {
            if ($sectionName != '') {
                $control[$sectionName] = $config;
            }
            $sectionName = preg_replace('/^\s*\[([^]]+)]\s*$/', '$1', $line);
            $sections[] = $sectionName;
            $config = [];
            $curFieldName = '';
            $section = [];
            continue;
        }

        // skip all non control lines
        if (!str_starts_with($line, ';; '))
            continue;

        // get the line within the config
        $code = mb_substr($line, 3, 1);
        $continue = mb_substr($line, 4, 1) == '+';

        switch ($code) {
            case 'N': // field name
                if ($curFieldName != '') {
                    $config[$curFieldName] = $section;
                }
                $section = [];
                $curFieldName = mb_substr($line, 5);
                $section['name'] = $curFieldName;
                break;

            case 'R': // role
                $roleArr = explode(',', mb_substr($line, 5), 2);
                $section['role'] = [ 'vis' => $roleArr[0], 'perm' => $roleArr[1], 'editable' => ($perm == 'admin' || in_array($roleArr[1], $auths)) ? 1 : 0 ];
                break;

            case 'P': // placeholder
                $section['placeholder'] = mb_substr($line, 5);
                break;

            case 'H': // hint
                if ($continue) {
                    $section['hint'] .= "<br/>" . mb_substr($line, 5);
                } else {
                    $section['hint'] = mb_substr($line, 5);
                }
                break;

            case 'D': // datatype
                $datatype = mb_substr($line, 5, 1);
                $modifier = mb_substr($line, 6);
                $section['datatype'] = [ 'type' => $datatype, 'modifier' => $modifier ];
                break;

            case 'B': // blank action
                $section['blank'] = mb_substr($line, 5, 1);
                break;

            default:
                $response['error'] = "invalid line at $lineNo in section $sectionName on field $curFieldName: $line";
                return $response;
        }
    }
    if ($curFieldName != '') {
        $config[$curFieldName] = $section;
    }

    if ($sectionName != '') {
        $control[$sectionName] = $config;
    }
    $response['perm'] = $perm;
    $response['auths'] = $auths;
    $response['control'] = $control;
    $response['sections'] = $sections;

    return $response;
}
