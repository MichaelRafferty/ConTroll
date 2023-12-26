<?php
require_once("base.php");

$badgeTypes = array(
    'bsfs'     => 'B',
    'discount' => 'M',
    'freebie'  => 'F',
    'goh'      => 'G',
    'rollover' => 'R',
    'standard' => 'M',
    'yearahead' => 'M',
    'premium'  => 'M',
    'test'     => 'X',
    'Attending' => 'A',
    'Vendor' => 'V',
    'voter' => 'V',
    'NoRights' => 'N'
);

function lookupType($type)//:string {
{
    global $badgeTypes;
    return $badgeTypes[$type];
}

function init_file($printer)//:string {
{
    if ($printer[0] == 'None' && $printer[2] == '') {
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

    $printerType = $printer[3];
    switch($printerType) {
        default:
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
    $printerType = $printer[3];
    switch ($printerType) {
        default:
            write_ps($badge, $tempfile);
            break;
    }
}

function write_ps($badge, $tempfile)//: void {
{
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
    $type = lookupType($badge['category']);
    $id = $badge['id'];

    if($badge['age'] == 'youth') { $type = 'Y'; }

    if(strtolower($badge['type'])=='oneday') {
        #$day = date("D");
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

    if($badge['age'] == 'child') {
        fwrite($temp, "newpath\n"
            . "68 4 moveto\n"
            . "68 16 lineto\n"
            . "114 16 lineto\n"
            . "114 4 lineto\n"
            . "closepath fill\n"
            . "1 setgray\n"
            . "72 6\n"
            . "2 copy moveto\n"
            . "childFont setfont\n"
            . "(child) show\n\n"
            . "0 setgray\n\n");
      }
    if($badge['age'] == 'kit') {
        fwrite($temp, "newpath\n"
            . "68 4 moveto\n"
            . "68 16 lineto\n"
            . "114 16 lineto\n"
            . "114 4 lineto\n"
            . "closepath fill\n"
            . "1 setgray\n"
            . "72 6\n"
            . "2 copy moveto\n"
            . "childFont setfont\n"
            . "( kit ) show\n\n"
            . "0 setgray\n\n");
      }

    #fwrite($temp, "grestore\nshowpage\n%%EOF\n");
    fwrite($temp, "\nshowpage\n");
    fclose($temp);
}

function write_se450($badge, $tempfile)//:void {
{
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

    #$badge_name2 = preg_replace("/#/", "", $badge['badge_name']);
    $badge_name2 = $badge['badge_name'];
    $badge_name = html_entity_decode($badge_name2, ENT_QUOTES | ENT_HTML401);
    $name = $badge_name;
    $namelen = strlen($name);
    if($namelen > 16) {
        $len = strrpos(substr($badge_name,1,16), ' ');
        if($len === false || $len === 0) { $len = 16; }
        else { $len +=1; }
        $name = substr($badge_name, 0, $len);
        $name2 = substr($badge_name, $len, 22);
        $name .= "\n"
            . printmod('lbl', array('7cpi', 'DblHighOFF', 'DblWideOFF'))
            . $name2;
    } else { $name .= "\n"; }

    $output = printmod('lbl', array('10cpi', 'DblHighON', 'DblWideON'));
    $output .= $name;
    $output .= "\n";

    //info line
    $type = lookupType($badge['category']);
    $id = $badge['id'];

    if($badge['age'] == 'youth') {
        $type='Y';
    }

    if(strtolower($badge['type'])=='oneday') {
        #$day = date("D");
        $day = substr($badge['day'], 0, 3);
    } else { $day = ""; }

    if($badge['age'] == 'child') {
        $age = sprintf("%schild%s",
                printmod('lbl', array('InverseON')),
                printmod('lbl', array('InverseOFF'))
                );
    } else if($badge['age'] == 'kit') {
        $age = sprintf("%s kit %s",
                printmod('lbl', array('InverseON')),
                printmod('lbl', array('InverseOFF'))
                );
    } else { $age = ""; }

    $output .= sprintf("%s%3s%s%5s%1s%s\n",
            printmod('lbl', array('10cpi', 'DblHighON', 'DblWideON')),
            $day,
            printmod('lbl', array('10cpi', 'DblHighOFF', 'DblWideOFF')),
            "", $type, $id);

    $output .= sprintf("%s%11s%s",
            printmod('lbl', array('10cpi', 'DblHighOFF', 'DblWideOFF')),
            "", $age);

    $output .= "\f";

    fwrite($temp, $output);
    fclose($temp);
}

// print_badge: printer contains array(4) of display name, server, queue name (printer), printer type
function print_badge($printer, $tempfile)//: string|false
{
    $queue = $printer[2];
    $codepage = $printer[3];
    $name = $printer[0];
    $result_code = 0;

    if (mb_substr($queue, 0, 1) == '0' || $name == 'None') { // return link to badge
        $atcon_conf = get_conf('atcon');
        $location = $atcon_conf['badges'];
        $newname = "ps/" . basename($tempfile) . ".ps";
        $command = "cp $tempfile " . "$location/$newname";
        $output = [];
        $result = exec($command,$output,$result_code);
        web_error_log("executing command '$command' returned '$result', code: $result_code",'badgePrn');
        if($result_code == 0) { 
            web_error_log("Badge saved at $newname",'badgePrn');
            $result_code=$newname;
        }
    }  else { // print to a printer
        $server = $printer[1];
        $printerType = $printer[3];
        $options = '';
        switch ($codepage) {
            // turbo 330. et al, -o PageSize=w82h248  -o orientation-requested=5
            case 'Dymo3xxPS':
                $options = '-o PageSize=w82h248 -o orientation-requested=5';
                break;
            // turbo 450 et al, -o PageSize=30252_Address
            case 'Dymo4xxPS':
                $options = '-o PageSize=30252_Address';
                break;
            default:
                break;
        }
        // all the extra stuff for exec is for debugging issues.
        $command = "lpr -H$server -P$queue $options < $tempfile";
        $output = [];
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
    $queue = $printer[2];
    $server = $printer[1];
    $name = $printer[0];
    $codepage = $printer[3];

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
    $command = "lpr -H$server -P$queue $options < $tempfile";
    $result_code = 0;
    $result = exec($command,$output,$result_code);
    web_error_log("executing command '$command' returned '$result', code: $result_code");
    //var_error_log($output);
    return $result_code;
}
