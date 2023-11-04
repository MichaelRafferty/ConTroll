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

    if (str_starts_with($fname, 'data_'))
        continue;

	if (!str_ends_with($fname, '.sql'))
		continue;

    echo "$fname:\n";
    $lines = file($fname);
    $localname = pathinfo($fname, PATHINFO_FILENAME);

    if ($localname == 'reg_routines') {
        strip_creator($localname, $lines);
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

        // strip off the auto increment values
        if (preg_match('/AUTO_INCREMENT=\d+/', $line)) {
            $line = preg_replace('/AUTO_INCREMENT=\d+\s/', ' ', $line);
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
    $newsql = [];
    foreach ($lines as $line) {
        $line = str_replace("\n", '', $line);
        $line = str_replace('utf8mb4_0900_ai_ci', 'utf8mb4_general_ci', $line);
        // remove the DEFINER= part of : /*!50013 DEFINER=`root`@`localhost` SQL SECURITY and change the SECURITY to INVOKER
        if (preg_match("/50013 DEFINER=/i", $line)) {
            $line = preg_replace("/DEFINER=[\"'`][^\"'`]*[\"'`]@[\"'`][^\"'`]*[\"'`] */i", "", $line);
            $line = str_replace('SQL SECURITY DEFINER', 'SQL SECURITY INVOKER', $line);
        }

        // get rid of definer within CREATE DEFINER='root'@'localhost' PROCEDURE...
        if (preg_match("/CREATE DEFINER=/i", $line)) {
            $line = preg_replace("/DEFINER=[\"'`][^\"'`]*[\"'`]@[\"'`][^\"'`]*[\"'`] */i", "", $line);
        }
        $newsql[] = $line;
    }

    rename($fname . '.sql', $fname . '.old');
    file_put_contents($fname . '.sql', implode("\n", $newsql) . "\n");
}
