<?php
// process the current directory
$dir = new DirectoryIterator('.');
$fks = [];
global $fks;

// loop over each file
foreach ($dir as $entry) {
	if (!$entry->isFIle())
		continue;
	$fname = $entry->getFileName();
    if ($fname == 'zz_foreign_keys.sql' || $fname == 'create_reg_schema.sql')
        continue;

	if (!str_ends_with($fname, '.sql'))
		continue;

    echo "$fname:\n";
    $lines = file($fname);
    $localname = pathinfo($fname, PATHINFO_FILENAME);

    if ($localname == 'reg_routines') {
        strip_creator($localname, $lines);
    } else if (str_starts_with($fname, 'data_')) {
        if ($fname != 'data_zzTxt.sal') {
            clean_data($localname, $lines);
        }
    } else {
        strip_fk($localname, $lines);
    }
}
file_put_contents('zz_foreign_keys.sql', implode("\n", $fks) . "\n");

function strip_fk($fname, $lines)  {
    global $fks;
    // open the file and loop over each line
    $newsql = [];
    $priorline = null;
    $table = preg_replace('/^[^_]*_(.*)$/', '\1', $fname);

    foreach ($lines as $line) {
        if (str_starts_with($line, '/*!')) {// skip comments, but let CREATE TRIGGER exist for later processing
            if (preg_match('/CREATE.*TRIGGER/i', $line)) {
                $line = preg_replace('/\/\*[^ ]* CREATE.* TRIGGER /i', 'CREATE DEFINER=CURRENT_USER TRIGGER ', $line);
            } else {
                continue;
            }
        }

        if (str_starts_with($line, 'CREATE DATABASE')) // this already exists as it's own sql file
            continue;

        if (str_starts_with($line, 'USE `')) // We are already in the right database
            continue;

        if (str_starts_with($line, '-- Dump completed on ')) // don't need the comment for one difference for no reason
            continue;

        // trigger items
        if (preg_match('/CREATE.* DEFINER=.* TRIGGER/i', $line)) { // create trigger
            $line = preg_replace('/DEFINER=[^ \*\/]*/i', 'DEFINER=CURRENT_USER ', $line);
        }

        if (preg_match('/END \*\/;;/i', $line)) {
            $line = preg_replace('/END \*\/;;/i', 'END;;', $line);
        }

        $line = str_replace("\n", '', $line);
        $line = str_replace("utf8mb4_0900_ai_ci", "utf8mb4_general_ci", $line);
        if (preg_match('/^ *CONSTRAINT .* FOREIGN KEY/i', $line)) {
            // foreign key constraint
            // eg: 'CONSTRAINT `artItems_artshow_fk` FOREIGN KEY (`artshow`) REFERENCES `artshow` (`id`) ON UPDATE CASCADE,'
            // does it end in a comma?
            $ends_in_comma = mb_substr($line, -1) == ',';
            if ($ends_in_comma) {
                $line = mb_substr($line, 0, mb_strlen($line) - 1);
            }
            $fks[] = "ALTER TABLE $table ADD " . ltrim($line) . ";";
            if (!$ends_in_comma) {
                $priorline = mb_substr($priorline, 0, mb_strlen($priorline) - 1);
            }
            continue;
        }

        // strip off the auto increment values, for perinfo set it to 100
        if (preg_match('/AUTO_INCREMENT=\d+/', $line)) {
            if ($table == 'perinfo') {
                $line = preg_replace('/AUTO_INCREMENT=\d+\s/', 'AUTO_INCREMENT=100 ', $line);
            } else {
                $line = preg_replace('/AUTO_INCREMENT=\d+\s/', ' ', $line);
            }
        }

        if ($priorline !== null)
            $newsql[] = $priorline;
        $priorline = $line;
    }
    if ($priorline !== null)
        $newsql[] = $priorline;

    rename($fname . '.sql', $fname . '.old');
    file_put_contents($fname . '.sql', implode("\n", $newsql) . "\n");
}

function strip_creator($fname, $lines) {
    $in_temp_view = false;
    $newsql = [];
    foreach ($lines as $line) {
        $line = str_replace("\n", '', $line);

        if (str_starts_with($line, 'CREATE DATABASE')) // this already exists as it's own sql file
            continue;

        if (str_starts_with($line, 'USE `')) // We are already in the right database
            continue;

        if (!$in_temp_view) {
            if (str_starts_with($line, '-- Temporary view structure')) {
                $in_temp_view = true;
                continue;
            }
        }
        if ($in_temp_view) {
            if (str_starts_with($line, '-- Final view structure'))  {
                $in_temp_view = false;
            } else {
                continue;
            }
        }

        $line = str_replace('utf8mb4_0900_ai_ci', 'utf8mb4_general_ci', $line);

        if (str_contains($line, 'DROP VIEW IF EXISTS')) { // take off comment from drop view
            $line = preg_replace('/^\/\*!\d+ (.*) *\*\/;$/', '$1;', $line);
        } elseif (str_contains($line, 'CREATE ALGORITHM=UNDEFINED')) { // create view
            $line = preg_replace('/^\/\*!\d+ (.*) *\*\/$/', '$1', $line);
        } elseif (str_contains($line, ' DEFINER=') && !str_contains($line, 'CREATE')) {
            $line = 'SQL SECURITY INVOKER';
        } else if (str_contains($line, 'DROP FUNCTION IF EXISTS')) { // take off comment from drop view
            $line = preg_replace('/^\/\*!\d+ (.*) *\*\/;$/', '$1;', $line);
        } else if (str_contains($line, 'DROP PROCEDURE IF EXISTS')) { // take off comment from drop view
            $line = preg_replace('/^\/\*!\d+ (.*) *\*\/;$/', '$1;', $line);
        } else  if (preg_match("/CREATE DEFINER=/i", $line)) { // create function or proc
            $line = preg_replace("/DEFINER=[\"'`][^\"'`]*[\"'`]@[\"'`][^\"'`]*[\"'`] */i", "", $line);
            $line .= "\nSQL SECURITY INVOKER";
        } else if (preg_match("/CREATE.* DEFINER=.* TRIGGER/i", $line)) { // create trigger
            $line = preg_replace("/DEFINER=[^ \*\/]*/i", "DEFINER=CURRENT_USER ", $line);
        } else if (str_contains($line, ' VIEW')) {
            $line = preg_replace('/^\/\*!\d+ (.*) *\*\/;$/', '$1;', $line);
        } else if (str_starts_with($line, '/*!')) // strip comments
            continue;
        $newsql[] = $line;
    }

    rename($fname . '.sql', $fname . '.old');
    if ($fname == 'reg_routines')
        $fname = 'zz_routines';
    file_put_contents($fname . '.sql', implode("\n", $newsql) . "\n");
}

function clean_data($fname, $lines)
{
    $newsql = [];
    foreach ($lines as $line) {
        if (str_starts_with($line, 'CREATE DATABASE')) // this already exists as it's own sql file
            continue;

        if (str_starts_with($line, 'USE `')) // We are already in the right database
            continue;

        $line = str_replace("\n", '', $line);
        if (str_contains($line, 'ALTER TABLE')) {
            $line = preg_replace('/^\/\*!\d+ (.*) *\*\/;$/', '$1;', $line);
        } else if (str_starts_with($line, '/*!'))
            // skip comment lines
            continue;

        $newsql[] = $line;
    }
    rename($fname . '.sql', $fname . '.old');
    file_put_contents($fname . '.sql', implode("\n", $newsql) . "\n");
}
