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
function printMod($type, $mods) {
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
    'premium'  => 'M',
    'test'     => 'X',
    'Attending' => 'A',
    'Vendor' => 'V',
    'voter' => 'V',
    'NoRights' => 'N'
);

function lookupType($type) {
    global $badgeTypes;
    return $badgeTypes[$type];
}

function init_file($printer) {
    switch($printer) {
        case 0:
        case 1:
        case 2:
        case 3:
        case 4:
            return init_ps();
        case 'old4':
        case 'old5':
            return init_se450();
    }
    return null;
}

function init_ps() {
    $tempfile = tempnam(sys_get_temp_dir(), "badgePrn");
    web_error_log("Writing to $tempfile");
    if(!$tempfile) {
        $response['error'] = "Unable to get unique file";
        $response['error_message'] = error_get_last();
        //var_error_log($response);
        ajaxSuccess($response);
        exit();
    }

    if(!copy(dirname(__FILE__) . "/init.ps", $tempfile)) {
        $response['error'] = "Unable to copy init.ps file";
        $response['error_message'] = error_get_last();
        //var_error_log($response);
        ajaxSuccess($response);
        exit();
    }

    return $tempfile;
}

function init_se450() {
    $tempfile = tempnam(sys_get_temp_dir(), "badgePrn");
    if(!$tempfile) {
        $response['error'] = "Unable to get unique file";
        $response['error_message'] = error_get_last();
        ajaxSuccess($response);
        exit();
    }

    $temp = fopen($tempfile, "w");
    if(!$temp) {
        $response['error'] = "Unable to get open file";
        $response['error_message'] = error_get_last();
        ajaxSuccess($response);
        exit();
    }

    $ctrlLine = printMod('lbl',
        array('Reset', '38cpl', 'Landscape', 'Truncate', 'VertStrtPt97'));
    fwrite($temp, $ctrlLine);
    fclose($temp);

    return $tempfile;
}

function write_badge($badge, $tempfile, $printer) {
    switch($printer) {
        case 0:
        case 1:
        case 2:
        case 3:
        case 4:
            write_ps($badge, $tempfile);
            break;
        case 'old4':
        case 'old5':
            write_se450($badge, $tempfile);
            break;
    }
}

function write_ps($badge, $tempfile) {
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
    $age = "";
    $day = "";

    if($badge['age'] == 'youth') { $type = 'Y'; }

    if(strtolower($badge['type'])=='oneday') {
        #$day = date("D");
        $day = substr($badge['day'], 0, 3);
        fwrite($temp, ""
            . "16 4\n"
            . "2 copy moveto\n"
            . "firstline setfont\n"
            . "($day) show\n\n");
    } else { $day = ""; }

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

function write_se450($badge, $tempfile) {
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
    $age = "";
    $day = "";

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


function print_badge($queue, $tempfile) {
    switch($queue) {
        case 0:
        case 1:
        case 2:
        case 3:
        case 4:
            return print_ps($queue, $tempfile);
            break;
        case 'old4':
        case 'old5':
            return print_se450($queue, $tempfile);
            break;
    }
}


function print_ps($queue, $tempfile) {
    $printerName = "label" . $queue;
    // turbo 450 -o PageSize=30252_Address
    // turbo 330 -o PageSize=w82h248  -o orientation-requested=5
    $result = exec("lpr -P $printerName -o PageSize=30252_Address $tempfile");
    return $result;
}


function print_se450($queue, $tempfile) {
    $printerName = "label" . $queue;
    $result = exec("lp -d $printerName $tempfile");
    return $result;
}


?>
