<?php

function ajaxError($msg) {
    header('HTTP/1.1 401 Unauthorized');
    header('content-type: application/json');
    die(json_encode(array('message' => $msg)));
}

function ajaxSuccess($msg) {
    header('Content-Type: application/json');
    print json_encode($msg);
}

?>
