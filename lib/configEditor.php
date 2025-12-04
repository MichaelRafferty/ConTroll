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

// check if locked and if not, lock the config file to prevent two people writing to it at once
function configLock($user_perid) : string {
    global $filePath, $configFile;

    $lockfile = "$filePath" . ".lock";
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
    if ($fileHandle === false) {
        return "Unable to open $lockfile, seek assistance.";
    }
    fwrite($fileHandle, $lock);
    fclose($fileHandle);
    return '';
}

// remove a lock file, we are done with it
function configUnlock($user_perid) : void {
    global $filePath, $configFile;

    $lockfile = "$filePath" . '.lock';
    error_log("$lockfile unlocked by $user_perid");
    $status = unlink($lockfile);
    if ($status === false) {
        error_log("Unable to delete $lockfile");
    }
}

// check if the prior data has already been modified
function checkCurrent($fields) : string {
    global $filePath;

    $warnmsg = '';
    $existing = parse_ini_file($filePath, true);
    foreach ($fields as $field) {
        $initial = $field['initial'];
        if (array_key_exists($field['section'], $existing)) {
            $sect = $existing[$field['section']];
            if (array_key_exists($field['param'], $sect)) {
                $ondisk = $sect[$field['param']];
                if ($ondisk != $initial) {

                    // mismatch, generate the warning
                    $warnmsg .= 'The field ' . $field['section'] . "'" . $field['param'] . " has been updated from '$initial' to '$ondisk'<br/>\n";
                }
            }
        }
    }

    if ($warnmsg != '') {
        $warnmsg = "The following fields were updated while you were editing:<br/>" . $warnmsg .
            "Save off your changes, reload the editor an make them again taking into account the newer values.";
    }
    return $warnmsg;
}

// update the file with all of the data, both initial and new


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
    $sectionTitle = '';
    $HRPrefix = '';
    $curFieldName = '';
// parse the file
    $lineNo = 0;
    foreach ($master as $line) {
        $lineNo++;
        if (preg_match('/^\s*\[[^]]+]\s*$/', $line)) {
            if ($sectionName != '') {
                if ($curFieldName != '')
                    $config[$curFieldName] = $section;
                $control[$sectionName] = $config;
            }
            $sectionName = preg_replace('/^\s*\[([^]]+)]\s*$/', '$1', $line);
            $sections[$sectionName] = array('name' => $sectionName, 'title' => $sectionTitle);
            $config = [];
            $curFieldName = '';
            $section = [];
            $HRPrefix = '';
            $sectionTitle = '';
            continue;
        }

        if (str_starts_with($line, ';;;;; ')) {
            $item = mb_substr($line, 6);
            if (str_starts_with($item, 'HR ')) {
                $HRPrefix = mb_substr($item, 3);
            } else {
                $sectionTitle = $item;
            }
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
                if ($HRPrefix != '') {
                    $section['hr'] = $HRPrefix;
                    $HRPrefix = '';
                }
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

// update the configuration, return the number of fields updated
function updateConfig($user_perid, $fields) : string {
    global $filePath, $controlPath;
    // first open a new file to make the overwrite atomic
    $newfile = $filePath . ".new";
    $fileHandle = fopen($newfile, 'w');
    if ($fileHandle === false) {
        $response['error'] = "Cannot open $newfile for writing the new configuration, seek assistance.";
        ajaxSuccess($response);
        exit();
    }

    $current_config = parse_ini_file($filePath, true);
    if (!is_readable($controlPath)) {
        $response['error'] = "Master control file does not exist";
        ajaxSuccess($response);
        exit();
    }
    $master = file($controlPath);
    $status = '';

    // first output  header lines
    $header = ";;; reg_conf.ini\n" .
        ";;;; updated by $user_perid on " . date('Y-m-d H:i:s') . "\n" .
        "; this is the section of the config file the reg admin can edit.  It has text items and other options that if changed\n" .
        "; will not break the system such that it requires major database work to recover it.\n\n";

    if (fwrite($fileHandle, $header) === false)  {
        $response['error'] = "Error writing header to $newfile, seek assistance.";
        ajaxSuccess($response);
        exit();
    }

    // loop over all the data in the control file
    $sectionName='missing';
    $needOutput = false;
    $fieldName = 'missing';
    $contents = '';
    $blank = 'O';
    $first=true;
    $updates = 0;
    foreach ($master as $line) {
        if (preg_match('/^\s*\[[^]]+]\s*$/', $line)) {
            if ($needOutput) {
                $status .= outputLine($fileHandle, $sectionName, $fieldName, $blank, $contents);
                $needOutput = false;
            }

            $sectionName = preg_replace('/^\s*\[([^]]+)]\s*$/', '$1', $line);
            fwrite($fileHandle, $line);
            if ($first) {
                fwrite($fileHandle, "version=\"reg_conf.ini last updated by $user_perid on " . date('Y-m-d H:i:s') . '"' . PHP_EOL);
                $first = false;
            }
            continue;
        }
        if (str_starts_with($line, ';; N:')) {
            if ($needOutput) {
                $status .= outputLine($fileHandle, $sectionName, $fieldName, $blank, $contents);
            }
            $fieldName = trim(mb_substr($line, 5));
            if (array_key_exists($sectionName . '__' . $fieldName, $fields)) {
                $contents = $fields[$sectionName . '__' . $fieldName]['new'];
                $updates++;
            } else {
                $contents = '';
                if (array_key_exists($sectionName, $current_config)) {
                    $secConf = $current_config[$sectionName];
                    if (array_key_exists($fieldName, $secConf)) {
                        $contents = $secConf[$fieldName];
                    }
                }
            }
            $blank = 'O';
            $needOutput = true;
            continue;
        }
        if (str_starts_with($line, ';; B:')) {
            $blank = trim(mb_substr($line, 5, 1));
            if ($blank == '')
                $blank = 'O';
            continue;
        }
        if (str_starts_with($line, ';; '))
            continue;

        if (strlen($line) < 2 || str_starts_with($line, ';')) {
            fwrite($fileHandle, $line);
        }
    }
    // shell written out
    if ($needOutput) {
        $status .= outputLine($fileHandle, $sectionName, $fieldName, $blank, $contents);
    }
    fclose($fileHandle);

    // now move the existing file to .bak and the .new to the main name
    $moveStatus = rename($filePath, $filePath . ".bak");
    if ($moveStatus === false) {
        $status .= "Cannot rename $filePath to $filePath.bak, seek assistance.<br/>";
    } else {
        $moveStatus = rename($filePath . ".new", $filePath);
        if ($moveStatus === false) {
            $status .= "Cannot rename $filePath.new to $filePath, seek assistance.<br/>";
        }
    }
    $status .= "$updates values updated";
    return $status;
}

function outputLine($fileHandle, $sectionName, $fieldName, $blank, $contents) : string {
    $status = '';
    if ($contents != null && $contents != '') {
        fwrite($fileHandle, $fieldName . '="' . str_replace('"', '\\"', $contents) . '"' . PHP_EOL);
    } else {
        switch ($blank) {
            case 'M':
                $status = "Mandatory field $sectionName:$fieldName is empty<br/>\n";
                break;
            case 'E':
                fwrite($fileHandle, $fieldName . "=\n");
                break;
            case 'B':
                fwrite($fileHandle, $fieldName . '=""' . PHP_EOL);
                break;
        }
    }
    return $status;
}
