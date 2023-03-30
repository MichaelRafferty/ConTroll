<?php
require_once("base.php");

$stylemod = array(
    'lbl' => array(
        'Reset'          => "\x1B*",
        '7cpi'           => "\x1BT",
        '10cpi'          => "\x1BU",
        '12cpi'          => "\x1BM",
        '16cpi'          => "\x1BP",
        '20cpi'          => "\x1BS",
        '32cpl'          => "\x1Dt\x20",
        '38cpl'          => "\x1Dt\x26",
        '50cpl'          => "\x1Dt\x32",
        'Landscape'      => "\x1DV\x01",
        'Truncate'       => "\x1DT\x00",
        'FeedLength282'  => "\x1DL\x02\x61",
        'VertStrtPt95'   => "\x1BY\x19\n",
        'VertStrtPt97'   => "\x1BY\x1a\n",
        'VertStrtPt99'   => "\x1BY\x1c\n",
        'DblHighON'      => "\x1D\x12",
        'DblHighOFF'     => "\x1D\x13",
        'DblWideON'      => "\x0E",
        'DblWideOFF'     => "\x14",
        'InverseON'      => "\x1D\x1E",
        'InverseOFF'     => "\x1D\x1F"
    ) // end lbl
);

/* function printMod returns a string of binary printer control commands
 ** $type is a  type of printer
 **** "lbl" == Dymo LabelWriter SE450
 ** $mods is an array of controls to pull back
 */
function printMod($type, $mods):string {
  global $stylemod;
  $ret = '';
  foreach($mods as $mod) {
    $ret .= $stylemod[$type][$mod];
  }
  return $ret;
}

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

function lookupType($type):string {
    global $badgeTypes;
    return $badgeTypes[$type];
}

function init_file($printer):string {
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
        case "badgese450":
            $temp = fopen($tempfile, 'w');
            if (!$temp) {
                $response['error'] = 'Unable to get open file';
                $response['error_message'] = error_get_last();
                ajaxSuccess($response);
                exit();
            }

            $ctrlLine = printMod('lbl',
                array('Reset', '38cpl', 'Landscape', 'Truncate', 'VertStrtPt97'));
            fwrite($temp, $ctrlLine);
            fclose($temp);
            break;
        default:
            if (!copy(dirname(__FILE__) . '/init.ps', $tempfile)) {
                $response['error'] = 'Unable to copy init.ps file';
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
    case 'badgese450':
        write_se450($badge, $tempfile);
        break;
    default:
        write_ps($badge, $tempfile);
        break;
    }
}

function write_ps($badge, $tempfile): void {
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

function write_se450($badge, $tempfile):void {
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
function print_badge($printer, $tempfile): string|false
{
    $queue = $printer[2];
    $name = $printer[0];
    if ($queue == '0' || $name == 'None') return 0; // this token is the temp file only print queue

    $server = $printer[1];
    $printerType = $printer[3];
    $options = '';
    switch ($printerType) {
        // turbo 330 -o PageSize=w82h248  -o orientation-requested=5
        case 'badge330':
            $options = '-o PageSize=w82h248 -o orientation-requested=5';
            break;
        // turbo 450 -o PageSize=30252_Address
        case 'badge450':
            $options = '-o PageSize=30252_Address';
            break;
        default:
            break;
    }
    // all the extra stuff for exec is for debugging issues.
    $command = "lpr -H$server -P$queue $options < $tempfile";
    $output = [];
    $result_code = 0;
    $result = exec($command,$output,$result_code);
    web_error_log("executing command '$command' returned '$result', code: $result_code");
    //var_error_log($output);
    return $result_code;
}

function print_receipt($printer, $receipt):string | false {
    $queue = $printer[2];
    $name = $printer[0];
    if ($queue == '0' || $name == 'None') {
        web_error_log($receipt);
        return 0; // this token is the log only print queue
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

    $server = $printer[1];
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
