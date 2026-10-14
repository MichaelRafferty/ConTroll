<?php
// memMapCleanup: post db patch 60 this will walk the memList table and fix up the bundles

global $db_ini;
if (!$db_ini) {
    $db_ini = parse_ini_file(__DIR__ . '/../config/reg_conf.ini', true);
}
require_once(__DIR__ . '/../lib/global.php');
require_once(__DIR__ . '/../lib/db_functions.php');
require_once(__DIR__ . '/../lib/log.php');
db_connect();

$findBundlesSQL = <<<EOS
SELECT id, label, notes
FROM memList
WHERE label LIKE 'Bundle: %';
EOS;

$regR = dbQuery($findBundlesSQL);
if ($regR === false) {
    echo "Error executing the query $findBundlesSQL\n";
    exit(1);
}

if ($regR->num_rows == 0) {
    echo "No bundles to process, you are done.\n";
    exit(0);
}

$bundles = [];
while ($reqL = $regR->fetch_assoc()) {
    $bundles[] = $reqL;
}
$regR->free();

// now load the memListMap Records
$mlmSQL = <<<EOS
SELECT * FROM memListMap;
EOS;

$mlmR = dbQuery($mlmSQL);
if ($mlmR === false) {
    echo "Error executing the query $mlmSQL\n";
    exit(1);
}

if ($mlmR->num_rows == 0) {
    echo "No memListMap, you cannot continue, get help.\n";
    exit(0);
}

$mlm = [];
while ($mlmL = $mlmR->fetch_assoc()) {
    $mlm[$mlmL['oldMemId']] = $mlmL;
}
$mlmR->free();

//var_dump($bundles);
//var_dump($mlm);

$updNotesSQL = <<<EOS
UPDATE memList SET notes = ?
WHERE id = ?;
EOS;

$numfixed = 0;
// loop over the bundles and work on the map
foreach ($bundles as $bundle) {
    echo "Starting bundle: " . $bundle['id'] . ': ' . $bundle['label'] . PHP_EOL;
    $notes = explode('/', $bundle['notes'], 2);
    if (count($notes) != 2) {
        echo "Skipping bundle, improper notes field: " . $bundle['notes'] . PHP_EOL;
        continue;
    }
    $oldContents = str_replace(' ', '', $notes[0]);
    $memIds = explode(',', $oldContents);
    var_dump($notes);
    var_dump($memIds);
    // now rebuild the bundle
    $newContents = "";
    foreach ($memIds AS $memId) {
        if (!array_key_exists($memId, $mlm)) {
            echo "Skipping memId: " . $memId . " because it is not in the memListMap\n";
            continue;
        }
        if ($newContents != "")
            $newContents .= ",";
        $newContents .= $mlm[$memId]['newMemId'];
    }
    if ($newContents == $oldContents) {
        echo "No change\n";
        continue;
    }
    echo "Mapping $oldContents to $newContents\n";
    $newNote = $newContents . '/' . $notes[1];
    $numUpd = dbSafeCmd($updNotesSQL, 'si', array($newNote, $bundle['id']));
    if ($numUpd != 1)
        echo "Failed to update notes for bundle: " . $bundle['id'] . PHP_EOL;
    $numfixed++;
}

echo "Update Complete, $numfixed bundles updated\n";
exit(0);
