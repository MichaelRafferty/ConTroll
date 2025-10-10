<?php

// validate remainder of the config file, except for mysql
//  options: the array returned by getoptions.
function validateConfigFiles($options) : int {
    global $configData;  // this is specific to walking the configuration, it needs $configData and will need to track it's new name
    $version = 1.43;

    logEcho('Validating configuration file merge of reg_conf.ini, reg_admin.ini and reg_secret.ini');

    if ($configData == null) {
        logEcho('Validating configuration file failed - data could not be loaded');
        return 1;
    }

    /*
     * Validate that all 3 config files can be found
     */

    // localize the path, try going up a couple of directories
    $path = __DIR__ . '/../config';
    if (!is_dir($path)) {
        $path = __DIR__ . '/../../config';
        if (!is_dir($path)) {
            $path = __DIR__ . '/../../../config';
        }
    }
    if (!is_dir($path)) {
        logEcho('Unable to find the configuration directory');
        return 1;
    }

    $errors = 0;

    $adminFile = $path . '/reg_admin.ini';
    $confFile = $path . '/reg_conf.ini';
    $secretFile = $path . '/reg_secret.ini';

    $errors += validateConfigFile('secrets', $secretFile, $version >= 1.5, array('api','oauth','usps'));
    $errors += validateConfigFile('admin', $adminFile, $version >= 1.5, array());

    //TODO: the conf sample file is going to be converted to a parsable file for web usage, use that syntax to totally validate that file
    $errors += validateConfigFile('conf', $confFile, $version >= 1.5, array());

    return $errors;
}

function validateConfigFile($section, $file, $req, $optional) {
    $errors = 0;
    if (!is_readable($file)) {
        logEcho("Unable to read the configuration $section file $file");
        if ($req) {
            $errors++;
            logEcho("The configuration file $file is no longer optional and is required.");
            return $errors;
        }
    } else {
        $data = parse_ini_file($file, true);
    }

    // file must exist and be parsable
    if ($data === false) {
        $errors++;
        logEcho("There is a parsing error in the $section file $file, validation of that file cannot continue");
    } else {
        // verify the comnfiguration file against the sample configuration file
        $sample = str_replace('/config/', '/config-sample/', $file) . '.sample';
        $sampleData = parse_ini_file($sample, true);
        if ($sampleData === false) {
            $errors++;
            logEcho("The configuration $section sample file $sample cannot be parsed, this means there is an error with your git clone or you modified the sample file.\n" .
                'Configuration validation of $file cannot continue.');
            return $errors;
        }

        // check that all sections in the sample file are in the config file
        $sections = array_keys($sampleData);
        foreach ($sections as $section) {
            if (!array_key_exists($section, $data)) {
                if (!in_array($section, $optional)) {
                    $errors++;
                    logEcho("The required section $section is missing from $file");
                } else {
                    logEcho("The optional section $section is missing from $file", true);
                }
            } else {
                // check that all required values are present (non empty fields in the sample file)
                foreach ($sampleData[$section] as $key => $value) {
                    if ($value != '') {
                        // this is a required field, make sure it's not empty in the config file
                        if (!array_key_exists($key, $data[$section])) {
                            $errors++;
                            logEcho("The required field $key is missing from $file section $section");
                        } else {
                            $cvalue = $data[$section][$key];
                            if ($cvalue == '' || $cvalue == null) {
                                $errors++;
                                logEcho("The required field $key cannot be empty in $file section $section");
                            }
                        }
                    } else {
                        if (!array_key_exists($key, $data[$section])) {
                            logEcho("The optional field $key is not found in $file section $section", true);
                        } else {
                            $cvalue = $data[$section][$key];
                            if ($cvalue == '' || $cvalue == null) {
                                logEcho("The optional field $key is empty in $file section $section", true);
                            }
                        }
                    }
                }
            }
        }
    }
    return $errors;
}
