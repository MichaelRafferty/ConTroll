<?php

function ajaxError($msg) {
    header('content-type: application/json');
    print json_encode(array('status' => 'error', 'message' => $msg));
}

function ajaxSuccess($msg) {
    header('Content-Type: application/json');
    print json_encode($msg);
}

?>
