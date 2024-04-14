<?php
// PDF Functions - functions to make using FPDF easier, only works with one PDF document at a time

// stacks for line width, fonts
$lineWidthStack = array();
$fontStack = array();
$currentPDF = null;

// initPDF - set up routines
function initPDF($pdf, $initialLineWidth, $initialFontFamily, $initialFontWeight, $initialFontSize): void {
    global $currentPDF, $fontStack, $lineWidthStack;

    $currentPDF = $pdf;
    $pdf->SetAutoPageBreak(false);
    $pdf->SetFont($initialFontFamily, $initialFontWeight, $initialFontSize);
    $fontStack[] = array($initialFontFamily, $initialFontWeight, $initialFontSize);
    $pdf->SetLineWidth($initialLineWidth);
    $lineWidthStack[] = $initialLineWidth;
}

// LineWidth stack usage (Push, POP)
// NOTE: will not empty array, last element is the default value from init call.
function pushLineWidth($width) {
    global $lineWidthStack, $currentPDF;

    $lineWidthStack[] = $width;
    $currentPDF->SetLineWidth($width);
}

function popLineWidth() {
    global $lineWidthStack, $currentPDF;

    if (count($lineWidthStack) > 1)
        array_pop($lineWidthStack);
    $currentPDF->SetLineWidth($lineWidthStack[count($lineWidthStack) - 1]);
}

// Font Stack usage (Push, POP(
function pushFont($fontFamily, $fontWeight, $fontSize) {
    global $fontStack, $currentPDF;

    $fontStack[] = array($fontFamily, $fontWeight, $fontSize);
    $currentPDF->SetFont($fontFamily, $fontWeight, $fontSize);
}

function popFont() {
    global $fontStack, $currentPDF;

    if (count($fontStack) > 1)
        array_pop($fontStack);

    $font = $fontStack[count($fontStack) - 1];
    $currentPDF->SetFont($font[0], $font[1], $font[2]);
}

// string print functions
function printXY($x, $y, $string) {
    global $currentPDF;

    $currentPDF->setXY($x, $y);
    $currentPDF->Cell(0, 0, $string);
}

function mprintXY($x, $y, $hsize, $string) {
    global $currentPDF, $fontStack, $lineWidthStack;

    // set vertical size to point size + leading
    $fontHeight = ($fontStack[count($fontStack) - 1][2])/72;
    $vsize = $fontHeight * 1.1;
    $currentPDF->setXY($x, $y - ($fontHeight + (1/72))/2);
    $currentPDF->MultiCell($hsize, $vsize, $string, 0, 'L', false);
    return $currentPDF->GetY();
}

function fitprintXY($x, $y, $hsize, $string) {
    global $currentPDF, $fontStack;

    $len = $currentPDF->GetStringWidth($string);
    if ($len > $hsize) {
        $font = $fontStack[count($fontStack) - 1];
        $newFontSize = $font[2]/1.4;
        pushFont($font[0], $font[1], $newFontSize);
        $newY = $y - ($newFontSize / 144);  // strange centerline stype of mprintXY.
        mprintXY($x, $newY, $hsize - 0.15, $string);
        popFont();
    } else {
        printXY($x, $y, $string);
    }
    return $currentPDF->GetY();
}

function centerPrintXY($x, $y, $hsize, $string) {
    global $currentPDF, $fontStack;

    $pt = 1.0/72.0;

    $len = $currentPDF->GetStringWidth($string);
    if ($len > $hsize)
        return mprintXY($x, $y, $hsize, $string);
    printXY($x + ($hsize - ($len + 2 * $pt)) / 2, $y, $string);
    return null;
}

function rightPrintXY($x, $y, $hsize, $string) {
    global $currentPDF, $fontStack;

    $pt = 1.0/72.0;

    $len = $currentPDF->GetStringWidth($string);
    if ($len > $hsize)
        return mprintXY($x, $y, $hsize, $string);
    printXY($x + ($hsize - ($len + 2 * $pt)), $y, $string);
    return null;
}
?>
