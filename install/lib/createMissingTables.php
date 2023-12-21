<?php

// validate MYSQL portion of config file
//  options: the array returned by getoptions.
function createMissingTables($options) : int {
    $errors = 0;
    $mysqlConf = get_conf('mysql');
    logEcho('Checking for missing tables/functions/procedures/keys in the database ' . $mysqlConf['db_name']);

    logEcho('Completed adding missing items from '. $mysqlConf['db_name']);
    if ($errors > 0)
        logEcho("Errors while adding missing items, cannot continue");
    return $errors;
}
?>
