<?php
require_once "../lib/base.php";
require_once '../lib/sessionAuth.php';

// use common global Ajax return functions
global $returnAjaxErrors, $return500errors;
$returnAjaxErrors = true;
$return500errors = true;

$perm = 'club';
$response = array ('post' => $_POST, 'get' => $_GET, 'perm' => $perm);
$authToken = new authToken('script');
$response['tokenStatus'] = $authToken->checkToken();
if (!$authToken->isLoggedIn() || !$authToken->checkAuth($perm)) {
    $response['error'] = 'Authentication Failed';
    ajaxSuccess($response);
    exit();
}

$con = get_con();
$conid = $con['id'];

$entryQ_fields = "SELECT concat_ws(' ', P.first_name, P.middle_name, P.last_name, P.suffix) as name, B.type, B.year, B.perid, B.id ";
$entryQ_tables = "FROM perinfo as P"
    . " JOIN club as B ON B.perid=P.id ";
$entryQ_where = "";
$entryQ_order = "ORDER BY B.type ASC, P.last_name DESC;";

$entryQ = $entryQ_fields . $entryQ_tables . $entryQ_where . $entryQ_order;

$response['query']=$entryQ;
$response['club']=array();

$entryR = dbQuery($entryQ);
while($club = fetch_safe_assoc($entryR)) {
  array_push($response['club'], $club);
}

ajaxSuccess($response);
