<?php
// ConTroll Registration System, Copyright 2015-2025, Michael Rafferty, Licensed under the GNU Affero General Public License, Version 3.
// library AJAX Processor: configEditor.php
// Author: Syd Weinstein
// all common functions related to the configuration editor

function loadConfigEditor($perm, $auths) : array {
    $configDir = '../config';
    $sampleDir = '../config-sample';
    if (!is_dir($configDir)) {
        $configDir = "../$configDir";
        $sampleDir = "../$sampleDir";
    }

    $configFile = 'reg_conf.ini';
    $controlFile = 'reg_conf.ini.sample';
    $filePath = "$configDir/$configFile";
    $controlPath = "$sampleDir/$controlFile";
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
    $current_config = parse_ini_file($filePath);
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
