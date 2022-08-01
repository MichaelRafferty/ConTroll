<?php
require("../lib/base.php");
require("../lib/ajax_functions.php");
require("../lib/conreg.php");

if($_SESSION['printer'] >= 0) {
    $printer = $_SESSION['printer'];
    $badge['type'] = $_POST['type'];
    $badge['badge_name'] = $_POST['badge_name'];
    $badge['category'] = $_POST['category'];
    $badge['id'] = $_POST['id'];
    $badge['day'] = $_POST['day'];
    $badge['age'] = $_POST['age'];

    $full_count = 0;
    $day_count = 0;

    $response = "";

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

    #sleep(1);
//    sleep(30);

    header('content-type: application/json');
    ajaxSuccess(array('post' => $_POST, 
                      'badge'=>$badges,
                      'full' => $full_count,
                      'day' => $day_count,
                      'print' => $response,
                      'response'=>$home));
} else {
    $data .= "&printed=false";
}

?>

