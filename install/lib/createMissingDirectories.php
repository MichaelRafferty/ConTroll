<?php

// checks for the required directories that are not part of the git tree
//  options: the array returned by getoptions.
function createMissingDirectories($options) : int {
    $directories = ['backups', 'config', 'cronlog', 'crons', 'reglogs', 'scripts',
        'onlinereg/images', 'vendor/images', 'portal/images', 'controll/images' ];
    $errors = 0;

    $path = __DIR__ . '/../config-sample';
    if (!is_dir($path)) {
        $path = __DIR__ . '/../../config-sample';
        if (!is_dir($path)) {
            $path = __DIR__ . '/../../../config-sample';
        }
    }
    if (!is_dir($path)) {
        logEcho('Unable to find the home directory of the project');
        return 1;
    }

    // make it a pure path
    $path = str_replace('config-sample', '', $path);

    foreach ($directories as $dir) {
        if (!is_dir("$path/$dir")) {
            logEcho("Directory $dir not found at $path/$dir, attempting to create it");
            if (mkdir("$path/$dir", 0755, false)) {
                logEcho('Directory $dir created.');
            } else {
                $errors++;
                logEcho("Failed to create directory $path/$dir");
            }
        } else {
            logEcho('Directory $dir exists at $path/dir', true);
        }
    }
    if ($errors > 0)
        logEcho("Errors while creating missing directories");
    return $errors;
}
