<?php

function calc_panels($thirds) {
    $full = floor($thirds / 3);
    $rem = $thirds % 3;

    if ($full > 0) {
        $result = "$full ";
    } else {
        $result = "";
    }

    if ($rem > 0) {
        $result .= "$rem/3";
    }

    return trim($result);
}

function calc_tables($quarters) {
    $full = floor($quarters/ 4);
    $rem = $quarters% 4;

    if ($full > 0) {
        $result = "$full ";
    } else {
        $result = "";
    }

    if ($rem == 2) {
        $result .= "1/2";
    } else if ($rem > 0){
        $result .= "$rem/4";
    }

    return trim($result);
}

?>
