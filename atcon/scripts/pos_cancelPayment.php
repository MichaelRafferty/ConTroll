 <?php
// library AJAX Processor: pos_cancelPayment.php
// ConTroll Registration System
// Author: Syd Weinstein
// send a cancel pay request to the terminal

require_once '../lib/base.php';
require_once('../../lib/log.php');
require_once('../../lib/term__load_methods.php');

// use common global Ajax return functions
global $returnAjaxErrors, $return500errors;
$returnAjaxErrors = true;
$return500errors = true;

$con = get_conf('con');
$conid = $con['id'];
$ajax_request_action = '';
if ($_POST && $_POST['ajax_request_action']) {
    $ajax_request_action = $_POST['ajax_request_action'];
}
if ($ajax_request_action != 'cancelPayRequest') {
    RenderErrorAjax('Invalid calling sequence.');
    exit();
}

if (!(check_atcon('cashier', $conid) || check_atcon('data_entry', $conid))) {
    $message_error = 'No permission.';
    RenderErrorAjax($message_error);
    exit();
}

$user_id = $_POST['user_id'];
if ($user_id != getSessionVar('user')) {
    ajaxError('Invalid credentials passed');
}

$user_perid = $user_id;

$log = get_conf('log');
logInit($log['term']);

if (!array_key_exists('requestId', $_POST) || empty($_POST['requestId'])) {
    RenderErrorAjax('Invalid calling sequence.');
}

$requestId = $_POST['requestId'];

$terminal = getSessionVar('terminal');
if ($terminal == NULL) {
    ajaxSuccess(array ('error' => 'No current terminal assigned to this station.'));
    exit();
}
$name = $terminal['name'];
$checkout = term_cancelPayment($name, $requestId, true);
$response = [];
$response['status'] = 'success';
$response['message'] = 'Payment $requestId cancelled, status = ' . $checkout['status'];
ajaxSuccess($response);
exit();

function resetTerminalStatus($name) {
    $updQ = <<<EOS
UPDATE terminals
SET currentOperator = 0, currentOrder = '', currentPayment = '', controllStatus = '', controllStatusChanged = now()
WHERE name = ?;
EOS;
    dbSafeCmd($updQ, 's', array($name));
}