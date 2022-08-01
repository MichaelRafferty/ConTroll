<?php
require("../lib/base.php");
require("../lib/ajax_functions.php");

if(isset($_POST) && count($_POST)>0) {
    $method='POST';
    $data = "user=".$_SESSION['user']."&passwd=".$_SESSION['passwd'];
    foreach ($_POST as $key => $value) {
        $data .= "&$key=$value";
    }
} else {
    $data = "method=GET&user=".$_SESSION['user']."&passwd=".$_SESSION['passwd'];
    foreach ($_GET as $key => $value) {
        $data .= "&$key=$value";
    }
}

    header('content-type: application/json');
    echo callHome("editPerson.php", "POST", $data);

?>

