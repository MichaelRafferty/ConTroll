<?php
// outputFile - output array of data in various File Format with automatic labels from the associative array

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

function outputFile($format, $sheetname, $fileName, $tableData, $excludeList = null, $fieldList = null) : void {
    header('Cache-Control: max-age=0');
    header('Expires: 0');
    header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT'); // always modified
    header('Cache-Control: must-revalidate');               // HTTP/1.1
    header('Pragma: public');                                      // HTTP/1.0
    header('Content-Description: File Transfer');

    switch ($format) {
        case 'csv':
            header('Content-Type: application/csv');
            break;
        case 'xlsx':
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            $spreadsheet = new Spreadsheet();
            $worksheet = $spreadsheet->getActiveSheet();
            $worksheet->setTitle($sheetname);
            break;
    }
    header('Content-Disposition: attachment;filename="' . $fileName . '.' . $format . '"');

    if ($format == 'csv') {
        $fh = fopen('php://output', 'w');
        // add BOM to fix UTF-8 in Excel
        fputs($fh, chr(0xEF) . chr(0xBB) . chr(0xBF));
    }

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

    if ($format == 'csv') {
        fputcsv($fh, $labels, ',', "\"", "\"", PHP_EOL);
    } else {
        $rows = [$labels];
    }

// now loop over the rows applying the field order or the exclude list or just output it....
    foreach ($tableData as $row) {
        if ($fieldList != null || $excludeList != null) {
            $out = [];
            foreach ($keys as $key) {
                $out[] = $row[$key];
            }
        }
        else {
            $out = $row;
        }

        if ($format == 'csv') {
            fputcsv($fh, $out, ',', "\"", "\"", PHP_EOL);
        } else {
            $rows[] = $out;
        }
    }

    switch ($format) {
        case 'csv':
            fclose($fh);
            break;

        case 'xlsx':
            $worksheet->fromArray($rows);
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
    }
}