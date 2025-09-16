<?php
require_once("base.php");
require_once('../../lib/pdfFunctions.php');
require_once('../../lib/pdf/tfpdf/tfpdf.php');
require_once('../../lib/pdf/fpdf-barcode/src/Barcode.php');

$con = get_conf('con');
$conid = $con['id'];

$badgeTypeQ = "SELECT memCategory, badgeLabel FROM memCategories WHERE active='Y';";
$badgeTypeR = dbQuery($badgeTypeQ);
$badgeTypes = array();

while($badgeType = $badgeTypeR->fetch_assoc()) {
$badgeTypes[$badgeType['memCategory']] = $badgeType['badgeLabel'];
}

$badgeFlagQ = "SELECT ageType, badgeFlag FROM ageList WHERE conid=? and badgeFlag is not null;";
$badgeFlagR = dbSafeQuery($badgeFlagQ, 'i', array($conid));
$badgeFlags = array();

while($badgeFlag = $badgeFlagR->fetch_assoc()) {
$badgeFlags[$badgeFlag['ageType']] = $badgeFlag['badgeFlag'];
}

function init_file($printer)//:string {
{
    if ($printer['name'] == 'None' && $printer['queue'] == '') {
        $response['error'] = "You have no printer defined, you cannot print a badge.";
        ajaxSuccess($response);
        exit();
    }
    $tempfile = tempnam(sys_get_temp_dir(), 'badgePrn');
    //web_error_log("Writing to $tempfile");
    if (!$tempfile) {
        $response['error'] = 'Unable to get unique file';
        $response['error_message'] = error_get_last();
        //var_error_log($response);
        ajaxSuccess($response);
        exit();
    }

    $codepage = $printer['code'];
    switch($codepage) {
        case 'PS':
            $atcon = get_conf('atcon');
            if (array_key_exists('badgeps', $atcon)) {
                $filename = $atcon['badgeps'];
            } else {
                $filename = dirname(__FILE__) . '/init.ps';
            }
            if (!copy($filename, $tempfile)) {
                $response['error'] = 'Unable to copy badge ps header file';
                $response['error_message'] = error_get_last();
                //var_error_log($response);
                ajaxSuccess($response);
                exit();
            }
    }
    return $tempfile;
}

function write_badge($badge, $tempfile, $printer):void {
    $codepage = $printer['code'];
    switch ($codepage) {
        case 'PS':
            write_ps($badge, $tempfile);
            break;
        case 'Dymo3xx':
            write_pdf($badge, $tempfile, 3);
            break;
        default:
            write_pdf($badge, $tempfile, 4);
    }
}

function write_pdf($badge, $tempfile, $originType)//: void {
{
    global $badgeTypes, $badgeFlags;

    $temp = fopen($tempfile, 'w');
    if (!$temp) {
        $response['error'] = 'Unable to get open file';
        $response['error_message'] = error_get_last();
        ajaxSuccess($response);
        exit();
    }

    $useBarcode = getConfValue('atcon', 'badgeBarcode', 0) == 1;

    // set up pdf
    $pdf = new Barcode('L', 'in', array(3.25, 1.125));

    // set up fonts
    $pdf->AddFont('Roboto', '', 'Roboto-Regular.ttf', true);
    $pdf->AddFont('Roboto', 'B', 'Roboto-Bold.ttf', true);
    //$pdf->AddFont('Roboto', 'BI', 'Roboto-BoldItalic.ttf', true);
    $pdf->AddFont('Roboto', 'BK', 'Roboto-Black.ttf', true);
    $pdf->AddFont('Roboto', 'KI', 'Roboto-BlackItalic.ttf', true);
    $pdf->AddFont('Roboto', 'SC', 'Roboto_SemiCondensed-Bold.ttf', true);
    $pdf->AddFont('Roboto', 'C', 'Roboto_Condensed-Bold.ttf', true);

    initPDF($pdf, 0.008, 'Roboto', '', 11);

    $pdf->setLeftMargin(0);
    $pdf->setRightMargin(0);
    $pdf->setTopMargin(0);
    $pdf->AddPage();

    $xmargin = 12/72.0;
    $ymargin = 15/72.0;

    // output an image if any
    $image = getConfValue('atcon', 'badgeLogo');
    if ($image != '') {
        $imageW = getConfValue('atcon', 'badgeLogoWidth', 1);
        $imageH = getConfValue('atcon', 'badgeLogoHeight', 1);
        $pdf->image("../../config/$image", 2.2, 0.1, $imageW, $imageH);
    }

    // Lines 1 and 2 - Badge Name
    //build badge name
    $bn = $badge['badge_name'] == '' ? $badge['full_name'] : $badge['badge_name'];
    $lines = explode('~~', $bn, 2);
    if (count($lines) > 1) {
        $line1 = $lines[0];
        $line2 = $lines[1];
    } else {
        $line1 = $bn;
        $line2 = '';
    }

    $x = $xmargin;
    $y = $ymargin;
    // badge name line 1: trim to what fits in 3.05" width using Roboto Bold 22pt font
    //  intelligent split based on blanks or character count if no blanks
    pushFont('Roboto', 'B', 22);
    $maxWidth = 3.05;
    $lineWidth = $pdf->getStringWidth($line1);
    if ($lineWidth > $maxWidth && $line2 != '') {
        // try a narrower font first
        popFont();
        pushFont('Roboto', 'SC', 22);
        $lineWidth = $pdf->getStringWidth($line1);
        if ($lineWidth > $maxWidth) {
            popFont();
            pushFont('Roboto', 'C', 22);
            $lineWidth = $pdf->getStringWidth($line1);
        }
        if ($lineWidth > $maxWidth) {
            // restore original font
            popFont();
            pushFont('Roboto', 'B', 22);
            // undo the split and let it split naturally
            $line1 = str_replace('~~', ' ', $bn);
            $line2 = '';
        }
    }
    $maxLoop = 0;
    while ($lineWidth > $maxWidth && $maxLoop < 30) {
        //echo "lineWidth = $lineWidth, maxWidth = $maxWidth\n";
        $lastBlank = strrpos($line1, ' ');
        //echo $lastBlank . PHP_EOL;
        if ($lastBlank === false) {
            // no blanks, move 1 character from line1 to line2
            $line2 = mb_substr($line1, -1) . $line2;
            $line1 = mb_substr($line1, 0, mb_strlen($line1) - 1);
            //echo "one character: line1='$line1', line2='$line2'" . PHP_EOL;
        } else {
            // pull back to a blank
            $line2 = mb_substr($line1, $lastBlank) . $line2;
            $line1 = mb_substr($line1, 0, $lastBlank);
            //echo "last blank: line1='$line1', line2='$line2'" . PHP_EOL;
        }
        $lineWidth = $pdf->getStringWidth($line1);

        $maxLoop++;
    }
    //echo "x=$x, y=$y\n";
    printXY($x - 6/72, $y, $line1);
    popFont();

    // line 2: overflow of the badge name
    $line2 = trim($line2);
    if ($line2 != '') {
        $y = $ymargin + 22/72;
        pushFont('Roboto', 'B', 16);
        $lineWidth = $pdf->getStringWidth($line2);
        //echo "lineWidth = $lineWidth\n";
        if ($lineWidth > $maxWidth) {
            popFont();
            // try semicondensed
            pushFont('Roboto', 'SC', 16);
            $lineWidth = $pdf->getStringWidth($line2);
            if ($lineWidth > $maxWidth) {
                // try condensed
                popFont();
                pushFont('Roboto', 'C', 16);
                $lineWidth = $pdf->getStringWidth($line2);
            }
            if ($lineWidth > $maxWidth) {
                //echo "font 17\n";
                popFont();
                pushFont('Roboto', 'B', 15);
                $lineWidth = $pdf->getStringWidth($line2);
            }
            if ($lineWidth > $maxWidth) {
                // try semicondensed
                popFont();
                pushFont('Roboto', 'SC', 15);
                $lineWidth = $pdf->getStringWidth($line2);
            }
            if ($lineWidth > $maxWidth) {
                // try condensed
                popFont();
                pushFont('Roboto', 'C', 15);
                $lineWidth = $pdf->getStringWidth($line2);
            }
            //echo "lineWidth = $lineWidth\n";
            if ($lineWidth > $maxWidth) {
                popFont();
                //echo "font 14\n";
                pushFont('Roboto', 'B', 14);
                $lineWidth = $pdf->getStringWidth($line2);
            }
            if ($lineWidth > $maxWidth) {
                // try semicondensed
                popFont();
                pushFont('Roboto', 'SC', 14);
                $lineWidth = $pdf->getStringWidth($line2);
            }
            if ($lineWidth > $maxWidth) {
                // try condensed
                popFont();
                pushFont('Roboto', 'C', 14);
                $lineWidth = $pdf->getStringWidth($line2);
            }
        }
        $maxLoop = 0;
        while ($lineWidth > $maxWidth && $maxLoop < 40) {
            $line2 = mb_substr($line2, 0, mb_strlen($line2) - 1);
            $lineWidth = $pdf->getStringWidth($line2);
            //echo "line2='$line2'" . PHP_EOL;
            //echo "lineWidth = $lineWidth\n";
            $maxLoop++;
        }
        //echo "x=$x, y=$y\n";
        printXY($x - 6/72, $y, $line2);
        popFont();
    }

    $type = '';
    if ($badge['category'] == 'test') {
        $type = 'test';
    } else {
        $type = $badgeTypes[$badge['category']];
    }
    $pid = $badge['id'];

    // line3 Day of Week: left margin, 1.5 lines high
    if (strtolower($badge['type']) == 'oneday') {
        $day = substr($badge['day'], 0, 3);

        $x = $xmargin;
        $y = $ymargin + 42 / 72;
        pushFont('Roboto', 'KI', 24);
        printXY($x - 6 / 72, $y, $day);
        popFont();
    }

    // line3 info: type and badge number, indented 100pt
    $x = $xmargin + 100/72;
    $y = $ymargin + 38/72;
    printXY($x, $y, $type . ' ' . $pid);

    // line4 limitation indented 100 pts, in reverse video

    if (array_key_exists($badge['age'], $badgeFlags)) {
        $flag = $badgeFlags[$badge['age']];

        $x = $xmargin + 100/72;
        $y = $ymargin + 52/72;
        $pdf->Rect($x, $y - 7 / 72, 90 / 72, 14 / 72, 'DF');
        $pdf->SetTextColor(255, 255, 255);
        pushFont('Roboto', 'BK', 11);
        centerPrintXY($x, $y, 85 / 72, $flag);
        $pdf->SetTextColor(0, 0, 0);
    }

    if ($useBarcode) {
        // line 4 Barcode - if desired

        $y = $ymargin + 54/72;
        $x = $xmargin;
        $pdf->code128($x, $y, $pid, 90 / 72, 8 / 72);
    }

    $output = $pdf->Output('S');
    fwrite($temp, $output);
    fclose($temp);
    }

function write_ps($badge, $tempfile)//: void {
{
    global $badgeTypes, $badgeFlags;

    $temp = fopen($tempfile, "a");
    if(!$temp) {
        $response['error'] = "Unable to get open file";
        $response['error_message'] = error_get_last();
        ajaxSuccess($response);
        exit();
    }

    //build badge name
    if($badge['badge_name'] == "") {
      $badge['badge_name'] = $badge['full_name'];
    }

    $badge_name = html_entity_decode($badge['badge_name'], ENT_QUOTES | ENT_HTML401);
    $name = $badge_name;
    $name2 = "";
    $namelen = strlen($name);
    if($namelen > 16) {
        $len = strrpos(substr($badge_name,1,16), ' ');
        if($len === false || $len === 0) { $len = 16; }
        else { $len +=1; }
        $name = substr($badge_name, 0, $len);
        $name2 = substr($badge_name, $len, 20);
    }

    fwrite($temp, "16\n"
        . "pageHeight 72 mul 22 sub\n"
        . "2 copy moveto\n"
        . "firstline setfont\n"
        . "($name) show\n\n");

    if($name2 != "") {
        fwrite($temp, "16\n"
        . "pageHeight 72 mul 40 sub\n"
        . "2 copy moveto\n"
        . "secondline setfont\n"
        . "($name2) show\n\n");
    }

    //info line
    $type='';
    if($badge['category'] == 'test') { $type = 'test'; }
    else { $type = $badgeTypes[$badge['category']]; }
    $id = $badge['id'];

    if(strtolower($badge['type'])=='oneday') {
        $day = substr($badge['day'], 0, 3);
        fwrite($temp, ""
            . "16 4\n"
            . "2 copy moveto\n"
            . "firstline setfont\n"
            . "($day) show\n\n");
    }

    fwrite($temp, "72 20\n"
        . "2 copy moveto\n"
        . "details setfont\n"
        . "($type $id) show\n\n");

    if(array_key_exists($badge['age'], $badgeFlags)) {
        $flag = $badgeFlags[$badge['age']];
        $flagLen = mb_strlen($flag);
        $offset = ceil($flagLen / 2);
        $wordoffset = floor($flagLen/2);
        $start = 91 - 8*$offset;
        $end = 91 + 8*$offset;
        $word = $start + 4;
        
        fwrite($temp, "newpath\n"
            . "$start 4 moveto\n"
            . "$start 16 lineto\n"
            . "$end 16 lineto\n"
            . "$end 4 lineto\n"
            . "closepath fill\n"
            . "1 setgray\n"
            . "$word 6\n"
            . "2 copy moveto\n"
            . "childFont setfont\n"
            . "($flag) show\n\n"
            . "0 setgray\n\n");

    }

    #fwrite($temp, "grestore\nshowpage\n%%EOF\n");
    fwrite($temp, "\nshowpage\n");
    fclose($temp);
}

// print_badge: printer contains array(4) of display name, server, queue name (printer), printer type
function print_badge($printer, $tempfile)//: string|false
{
//error_log($printer['name'] . ' ' . $printer['host'] . ' ' . $printer['queue'] . ' ' . $printer['type'] . ' ' . $printer['code']);

    $queue = $printer['queue'];
    $codepage = $printer['code'];
    $suffix = $codepage = 'PS' ? 'ps' : 'pdf';
    $name = $printer['name'];
    $result_code = 0;

    if (mb_substr($queue, 0, 1) == '0' || $name == 'None') { // return link to badge
        web_error_log("trying to save file");
        $atcon_conf = get_conf('atcon');
        $location = $atcon_conf['badges'];
        $newname = "$suffix/" . basename($tempfile) . ".$suffix";
        $command = "cp $tempfile " . "$location/$newname";
        $output = [];
        $result = exec($command,$output,$result_code);
        web_error_log("executing command '$command' returned '$result', code: $result_code",'badgePrn');
        if($result_code == 0) { 
            web_error_log("Badge saved at $newname",'badgePrn');
            $result_code='' . $newname;
        } else {
            web_error_log("Badge Not Saved: $command");
        }
    }  else { // print to a printer
        web_error_log("trying to print to printer");
        $server = $printer['host'];
        $options = '';
        switch ($codepage) {
            // turbo 330. et al, -o PageSize=w82h248  -o orientation-requested=5
            case 'Dymo3xx':
                $options = '-o PageSize=w82h248 -o orientation-requested45';
                break;
            // turbo 450 et al, -o PageSize=30252_Address
            case 'Dymo4xx':
                $options = '-o PageSize=30252_Address';
                break;
            default:
                break;
        }
        // all the extra stuff for exec is for debugging issues.
        $serverArg = '';
        if ($server != '')
            $serverArg = "-H$server";
        $command = "lpr $serverArg -P$queue $options < $tempfile";
        $output = [];
        web_error_log("About to execute '$command'", '');
        $result = exec($command,$output,$result_code);
        web_error_log("executing command '$command' returned '$result', code: $result_code",'badgePrn');
    }

    unlink($tempfile); // TODO make this a configuration option
    return $result_code;
}

// print receipt - used for both receipts and generic printouts
// will do character set conversions as needed for the code pages
// printer: printer control array (name, server, queue, codepage (encoding)
function print_receipt($printer, $receipt)//:string | false {
{
    $queue = $printer['queue'];
    $server = $printer['host'];
    $name = $printer['name'];
    $codepage = $printer['code'];

    switch ($codepage) {
        case 'UTF-8':
            break;

        case 'ASCII':
        case '7bit':
        case '8bit':
        case 'UTF-16':
        $receipt = mb_convert_encoding($receipt, $codepage, 'UTF-8');
            break;

        default: // use Windows-1252 default
            //$receipt = iconv('UTF-8', 'Windows-1252', $receipt);
            $receipt = mb_convert_encoding($receipt, 'Windows-1252', 'UTF-8');
    }

    $tempfile = tempnam(sys_get_temp_dir(), 'rcptPrn');
    //web_error_log("Writing to $tempfile");
    if (!$tempfile) {
        $response['error'] = 'Unable to get unique file';
        $response['error_message'] = error_get_last();
        //var_error_log($response);
        ajaxSuccess($response);
        exit();
    }

    $temp = fopen($tempfile, 'w');
    fwrite($temp, $receipt);
    fclose($temp);

    if (mb_substr($queue, 0, 1) == '0' || $name == 'None') {
        web_error_log($receipt);
        return 0; // this token is the log only print queue
    }

    $options = '';

    // all the extra stuff for exec is for debugging issues.
    // Temporarly save the output to a file to help with why it's dying
    $serverArg = '';
    if ($server != '')
        $serverArg = "-H$server";
    $command = "lpr $serverArg -P$queue $options < $tempfile";
    $result_code = 0;
    $result = exec($command,$output,$result_code);
    web_error_log("executing command '$command' returned '$result', code: $result_code");
    unlink($tempfile); // TODO make this a configuration option
    //var_error_log($output);
    return $result_code;
}
