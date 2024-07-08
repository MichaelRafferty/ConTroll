<?php

function ajaxError($msg) {
    header('HTTP/1.1 401 Unauthorized');
    header('content-type: application/json; charset=utf-8');
    die(json_encode(array('message' => $msg)));
}

function ajaxSuccess($msg) {
    header('Content-Type: application/json; charset=utf-8');
    print json_encode($msg);
}

?>
