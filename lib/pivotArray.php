<?php
// ConTroll ReportWriter/Tabulator support function to pivot a SQL select array
// pivotArray - convert an array of associative array pivoting the rows and the columns
//      source = SQL return fetch_assoc() array of rows of the select
//      keyFields = array of the fields that when concatenated together with '/' will become the new column key
//      rowLabel = name to assign to the first associative element of the pivoted array
function pivotArray($source, $keyfields, $rowLabel) : array | null {
    if (!is_array($source))
        return null;    // if not passed an array

    $pivot = [];
    $keys = array_keys($source[0]);
    // keys = the new rows for the array, build the top row with the field names
    $keyrow = [];
    for ($i = 1; $i < count($source); $i++) {
        $row = '';
        foreach ($keyfields as $keyfield) {
            if ($row != '')
                $row .= '/';
            $row .= $source[$i][$keyfield];
        }
        $keyrow[] = $row;
    }
    var_dump([ 'keyrow' => $keyrow ]);
    $rows = [];
    for ($i = 0; $i < count($keys); $i++) {
        if (in_array($keys[$i], $keyfields, true))
            continue;

        // new row
        $valuekey = $keys[$i];
        $row = [$rowLabel => $valuekey ];
        for ($s = 0; $s < count($source); $s++) {
            $key = '';
            $v = 0;
            foreach ($keyfields as $keyfield) {
                if ($key != '')
                    $key .= '/';
                $key .= $source[$s][$keyfield];
            }
            $row[$key] = $source[$s][$valuekey];
        }

        $rows[] = $row;
    }

    return $rows;
}

/* Test code:
$testArray = [
    [ 'label' => 'Artist', 'type' => 'full', 'category' => 'artist', 'Printed' => '', 'Unprinted' => '3' ],
    [ 'label' => 'Dealer', 'type' => 'full', 'category' => 'dealer', 'Printed' => '', 'Unprinted' => '11' ],
    [ 'label' => 'General', 'type' => 'full', 'category' => 'standard', 'Printed' => '', 'Unprinted' => '90' ],
    [ 'label' => 'General', 'type' => 'full', 'category' => 'yearahead', 'Printed' => '', 'Unprinted' => '31' ],
    [ 'label' => 'Kid in Tow', 'type' => 'full', 'category' => 'standard', 'Printed' => '', 'Unprinted' => '2' ],
    [ 'label' => 'Other Comp', 'type' => 'full', 'category' => 'freebie', 'Printed' => '', 'Unprinted' => '1' ],
    [ 'label' => 'Program Participant', 'type' => 'full', 'category' => 'freebie', 'Printed' => '', 'Unprinted' => '1' ],
    [ 'label' => 'Staff', 'type' => 'full', 'category' => 'freebie', 'Printed' => '', 'Unprinted' => '4' ],
    [ 'label' => 'Student', 'type' => 'full', 'category' => 'standard', 'Printed' => '', 'Unprinted' => '1' ],
    ['label' => 'Teens', 'type' => 'full', 'category' => 'standard', 'Printed' => '', 'Unprinted' => '2' ],
];
$pivotArray = pivotArray($testArray, array('label', 'type', 'category'), 'rowName');
var_dump(['pivot returned' => $pivotArray]);
*/
