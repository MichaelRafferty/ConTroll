<?php
// ConTroll Registration System, Copyright 2015-2025, Michael Rafferty, Licensed under the GNU Affero General Public License, Version 3.
// library AJAX Processor: admin_atconLoadData.php
// Author: Syd Weinstein
// load all objects needed at start of atcon admin functions

require_once('../lib/base.php');

// use common global Ajax return functions
global $returnAjaxErrors, $return500errors;
$returnAjaxErrors = true;
$return500errors = true;

$check_auth = google_init('ajax');
$perm = 'admin';

$response = array ('post' => $_POST, 'get' => $_GET, 'perm' => $perm);

if ($check_auth == false || !checkAuth($check_auth['sub'], $perm)) {
    $response['error'] = 'Authentication Failed';
    ajaxSuccess($response);
    exit();
}

if (!array_key_exists('load_type', $_POST)) {
    $response['error'] = 'Parameter Error';
    ajaxSuccess($response);
    exit();
}

$con=get_conf('con');
$conid= $con['id'];

// loadData:
//  users or all: return an array of objects of valid users of ATCON with their permission atoms
//  printers or all: return an array of server and printers
$loadtypes = $_POST['load_type'];
$response['load_type'] = $loadtypes;
$response['conid'] = $conid;
if ($loadtypes == 'all' || $loadtypes == 'users') {
    // load authorized users of ATCON along with their allowed roles
    $users = [];
    $query = <<<EOS
SELECT perid, concat(P.first_name, ' ', P.last_name) as name, A.auth
FROM atcon_user U
LEFT OUTER JOIN atcon_auth A ON (A.authuser = U.id)
JOIN perinfo P ON (P.id=U.perid)
WHERE conid=?
ORDER BY perid, auth;
EOS;
    $userQ = dbSafeQuery($query, 'i', array($conid));
    while ($user = $userQ->fetch_assoc()) {
        $perid = $user['perid'];
        if (isset($users[$perid])) {
            $users[$perid][$user['auth']] = true;
        } else {
            $users[$perid] = [
                'id' => $perid,
                'name' => $user['name'],
                'delete' => "🗑", //html_entity_decode("&#x1F5D1;"),
            ];
            $users[$perid][$user['auth']] = true;
        }
    }
    $userQ->free();

    $data = [];
    foreach ($users as $user) {
        $data[] = $user;
    }
    $response['users'] = $data;
    $response['userid'] = getSessionVar('user');
}
if ($loadtypes == 'all' || $loadtypes == 'printers') {
    // first synchronize any changes to the global printer before loading the data.
    // do not delete a server that disappeared, it may have moved local.
    // do synchronize all printers on global servers that are loaded locally via stored procedure syncServerPrinters
    dbCmd("CALL syncServerPrinters;");

    $servers = [];
    $printers = [];

    $serverSQL = <<<EOS
SELECT serverName, address, location, active, local, IF(local = 1, '🗑', '') as `delete`, serverName as oldServerName
FROM servers
UNION
SELECT g.serverName, g.address, '' as location, 0 as active, 0 as local,  '' as `delete`, g.serverName as oldServerName
FROM printservers.servers g
LEFT OUTER JOIN servers s ON (g.serverName = s.serverName)
WHERE s.serverName IS NULL
ORDER BY active DESC, serverName;
EOS;
    $serverQ = dbQuery($serverSQL);
    while ($server = $serverQ->fetch_assoc()) {
        $servers[] = $server;
    }
    $response['servers'] = $servers;
    $serverQ->free();

    $printersSQl = <<<EOS
SELECT p.serverName, p.printerName, p.printerType, p.codePage, p.active, IF(s.local = 1, '🗑', '') as `delete`
FROM printers p
JOIN servers s ON (p.serverName = s.serverName)
WHERE s.active = 1
ORDER BY s.local DESC, serverName, printerType, printerName;
EOS;
    $printerQ = dbQuery($printersSQl);
    while ($printer = $printerQ->fetch_assoc()) {
        $printers[] = $printer;
    }
    $response['printers'] = $printers;
    $printerQ->free();
}
ajaxSuccess($response);
