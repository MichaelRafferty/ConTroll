<?php
// outputCSV - output array of data in CSV Format with automatic labels from the associative array

function outputCSV($fileName, $tableData, $excludeList = null, $fieldList = null) : void {
    header('Cache-Control: max-age=0');
    header('Expires: 0');
    header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT'); // always modified
    header('Cache-Control: cache, must-revalidate');               // HTTP/1.1
    header('Pragma: public');                                      // HTTP/1.0
    header('Content-Type: application/force-download');
    header('Content-Type: application/octet-stream');
    header('Content-Type: application/download');
    header('Content-type: application/csv');
    header('Content-Disposition: attachment;filename="' . $fileName . '.csv"');
    header('Content-Transfer-Encoding: binary');
    $csv = fopen('php://output', 'w');
//add BOM to fix UTF-8 in Excel
    fputs($csv, $bom = (chr(0xEF) . chr(0xBB) . chr(0xBF)));

// get the header line, apply field order if it exists and skip the exclude list values
    $keys = array_keys($tableData[0]);
    $labels = $keys;
    if ($fieldList != null) {
        $keys = [];
        $labels = [];
        foreach ($fieldList as $field) {
            if (is_array($field)) {
                if (!array_is_list($field)) {
                    $keys[] = $field['key'];
                    $labels[] = $field['label'];
                }
                else {
                    $keys = $field[0];
                    $labels[] = $field[count($field) - 1];
                }
            }
            else {
                $keys[] = $field;
                $labels[] = $field;
            }
        }
    }
    else if ($excludeList != null) {
        $labels = [];
        foreach ($keys as $key) {
            if (!in_array($key, $excludeList)) {
                $labels[] = $key;
            }
        }
        $keys = $labels;
    }
    fputcsv($csv, $labels, ',', "\"", "\"", PHP_EOL);

// now loop over the rows applying the field order or the exclude list or just output it....
    foreach ($tableData as $row) {
        if ($fieldList != null || $excludeList != null) {
            $out = [];
            foreach ($keys as $key) {
                $out[] = $row[$key];
            }
            fputcsv($csv, $out, ',', "\"", "\"", PHP_EOL);
        }
        else {
            fputcsv($csv, $row, ',', "\"", "\"", PHP_EOL);
        }
    }

    fclose($csv);
}