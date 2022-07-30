<?php
global $db_ini;

require_once "../lib/base.php";
require_once "../lib/ajax_functions.php";

$check_auth = google_init("ajax");
$perm = "virtual";

$response = array("post" => $_POST, "get" => $_GET, "perm"=>$perm);


if($check_auth == false || !checkAuth($check_auth['sub'], $perm)) {
    $response['error'] = "Authentication Failed";
    $response['status']='error';
    ajaxSuccess($response);
    exit();
}

require_once("../../../lib/email__load_methods.php");
$con = get_conf('con');

if(!isset($_POST)
    or !isset($_POST['day']) or $_POST['day'] == ""
    or !isset($_POST['time']) or $_POST['time'] == ""
    or !isset($_POST['duration']) or $_POST['duration'] == ""
    or !isset($_POST['type']) or $_POST['type'] == ""
    or !isset($_POST['title']) or $_POST['title'] == ""
    or !isset($_POST['panelists']) or $_POST['panelists'] == ""
    or !isset($_POST['description']) or $_POST['description'] == ""
    or !isset($_POST['reg']) or $_POST['reg'] == ""
    or !isset($_POST['account']) or $_POST['account'] == ""
    or !isset($_POST['id']) or $_POST['id'] == ""
    or !isset($_POST['password']) or $_POST['password'] == "") {
        $response['status']='error';
        $response['error'] = "Missing Data";
        ajaxSuccess($response); exit();
    }

$time = $_POST['time'];
$sort = $time;
$tech = $time - 1 . "30";
if($time > 12) { $time = $time -12 . ":00 PM"; }
else if ($time < 12) { $time = $time . ":00 AM"; }
else { $time = "12 Noon"; }

$date = new DateTime();
$linkText = "";
switch($_POST['type']) {
    case 'Zoom':
        $linkText = "<a href='" . htmlspecialchars($_POST['reg']) . "'>Zoom Registration: "
            . htmlspecialchars($_POST['reg']) . "</a>";
        break;
    case 'YouTube':
        $linkText = "<a href='" . htmlspecialchars($_POST['reg']) . "'>YouTube Video"
            . htmlspecialchars($_POST['reg']) . "</a>";
        break;
    //case 'Twitch':
    //    $linkText = "<a href='https://www.twitch.tv/bsfsbalticon" .  "'>Twitch Channel https://www.twitch.tv/bsfsbalticon</a>";
    //    break;
    case 'Other':
    default:
        $linkText = "<a href='" . htmlspecialchars($_POST['reg']) . "'>"
            . htmlspecialchars($_POST['reg']) . "</a>";
}

$response = array(
    'create' => $date->getTimestamp(),
    'day' => htmlspecialchars($_POST['day']),
    'tech' => $tech,
    'time' => $time,
    'duration' => htmlspecialchars($_POST['duration']),
    'sort' => $sort,
    'title' => htmlspecialchars($_POST['title']),
    'panelists' => htmlspecialchars($_POST['panelists']),
    'desc' => htmlspecialchars($_POST['description']),
    'tags' => htmlspecialchars($_POST['tags']),
    'link' => $linkText,
    'account' => htmlspecialchars($_POST['account']),
    'id'=>htmlspecialchars($_POST['id']),
    'password'=>htmlspecialchars($_POST['password'])

);

$file = '/var/regJournal/testSchedule/';
$file = $file . $response['day'];
file_put_contents($file, serialize($response) . PHP_EOL, FILE_APPEND | LOCK_EX);
ajaxSuccess($response);

$emailString = $response['day'] . " at " . $response['time'] . " for " . $response['duration'] . "<br/>\n"
    . "<strong>" . $response['title'] . "</strong><br/>\n"
    . "<em>" . $response['tags'] . "</em>\n"
    . $response['panelists'] . "<br/>\n"
    . $response['desc'] . "<br/>\n"
    . $response['link'] . "<br/>\n";

$return_arr = send_email($con['regadminemail'], /* to needs fixing */ 'VBmembers@balticon.org', /* cc */ null, "New Virtual " . $condata['label'] . " OnlineItem",
     "We've created a new link for a Virtual " . $condata['label'] . " event". "<br/>" . $emailString . "<br/>Thank you for your support for Virtual " . $condata['label'] . "\n", /* htmlbody */ null);

}

?>
