<?php
require("../lib/base.php");
require("../lib/ajax_functions.php");
require("../lib/conreg.php");

if(isset($_POST) && count($_POST)>0) {
    $method='POST';
    $data = "user=".$_SESSION['user']."&passwd=".$_SESSION['passwd'];
    foreach ($_POST as $key => $value) {
        $data .= "&$key=$value";
    }
} else {
    $data = "user=".$_SESSION['user']."&passwd=".$_SESSION['passwd'];
    foreach ($_GET as $key => $value) {
        $data .= "&$key=$value";
    }
}

if($_SESSION['printer'] >= 0) {
    $printer = $_SESSION['printer'];
    $badges = json_decode($_POST['badgeList'], true);

    if(empty($badges)) {
        header('content-type: application/json');
        ajaxSuccess(array('post' => $_POST, 
                      'session' => $_SESSION, 
                      'badge'=>$badges,
                      'full' => $full_count,
                      'day' => $day_count,
                      'error' => "No Badges Sent"));
        exit();
    }

    $data .= "&printed=true";
    $home = callHome("atconPrintBadge.php", "POST", $data);
    
    $full_count = 0;
    $day_count = 0;

    $response = "";
    foreach($badges as $badge) {
        $resp = "no call";
        if($badge['type'] == 'full') {
            $file_full = init_file($printer);
            write_badge($badge, $file_full, $printer);
            print_badge($printer, $file_full);
            $full_count += 1;
        } else {
            $file_1day = init_file($printer);
            write_badge($badge, $file_1day, $printer);
            print_badge($printer, $file_1day);
            $day_count += 1;
        }
    }

    #sleep(1);

    header('content-type: application/json');
    ajaxSuccess(array('post' => $_POST, 
                      'session' => $_SESSION, 
                      'badge'=>$badges,
                      'full' => $full_count,
                      'day' => $day_count,
                      'print' => $response,
                      'response'=>$home));
} else {
    $data .= "&printed=false";
    echo callHome("atconPrintBadge.php", "POST", $data);
}

?>

