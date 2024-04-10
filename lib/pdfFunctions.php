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

    $currentPDF->setXY($x, $y);
    // set vertical size to point size * 1.2
    $vsize = (($fontStack[count($fontStack) - 1][2])/72) * 1.1;
    $currentPDF->MultiCell($hsize, $vsize, $string);
}

function fitprintXY($x, $y, $hsize, $string) {
    global $currentPDF, $fontStack;

    $len = $currentPDF->GetStringWidth($string);
    if ($len > $hsize) {
        $font = $fontStack[count($fontStack) - 1];
        pushFont($font[0], $font[1], $font[2]/1.4);
        mprintXY($x, $y - (($font[2]/72) * 0.7), $hsize - 0.15, $string);
        popFont();
    } else {
        printXY($x, $y, $string);
    }
}

?>
