<?php
require_once "lib/base.php";
//initialize google session
$need_login = google_init("page");

$page = "virtual";
if(!$need_login or !checkAuth($need_login['sub'], $page)) {
    bounce_page("index.php");
}

page_init($page,
    /* css */ array('css/base.css'
                   ),
    /* js  */ array('/javascript/d3.js',
                    'js/base.js',
                    'js/virtual.js'
                   ),
              $need_login);
?>
<div id='main'>
<span class='blocktitle'>Create Event</span>
<form action='javascript:void(0)' id='newEvent'>
<table>
<tr><td>Day:</td><td colspan=2><select name='day'><option>Friday</option><option>Saturday</option><option>Sunday</option><option>Monday</option></select></td></tr>
<tr><td colspan="1">Attendee Start Time:</td><td><select name='time'> <?php /* on change set tech start */?>
    <option value=10>10 AM</option>
    <option value=11>11 AM</option>
    <option value=12>Noon</option>
    <option value=13>1 PM</option>
    <option value=14>2 PM</option>
    <option value=15>3 PM</option>
    <option value=16>4 PM</option>
    <option value=17>5 PM</option>
    <option value=18>6 PM</option>
    <option value=19>7 PM</option>
    <option value=20>8 PM</option>
    <option value=21>9 PM</option>
    <option value=22>10 PM</option>
</select></td><td>Duration: <input type='text' value='50 minutes' name='duration'/></td></tr>
<tr><td>Title:</td><td colspan=2><input id='eventTitle' size=60 type='text' name='title' placeholder='Title'/></td></tr>
<tr><td>Panelists:</td><td colspan=2><input id='eventPeople' size=60 type='text' name='panelists' placeholder='Moderator & Panelists'/></td></tr>
<tr><td colspan=3>Descrition:<br/><textarea id='eventDesc' name='description' cols=80 rows=5 placeholder='description'></textarea></td></tr>
<tr><td>Tags:</td><td colspan=2><input name='tags' size=60 type='text' placeholder='Tags / Tracks'/></td></tr>
<tr><td colspan=3><hr/></td></tr>
<tr><td>Event Type</td><td colspan=2>
    <label><input type='radio' name='type' value='Zoom' onclick='$("#reg").val("");'/>Zoom</label>
    <label><input type='radio' name='type' value='YouTube' onclick='$("#reg").val("");'/>YouTube</label>
    <label><input type='radio' name='type' value='Twitch' onclick='$("#reg").val("https://www.twitch.tv/bsfsbalticon");'/>Twitch</label>
    <label><input type='radio' name='type' value='Other' onclick='$("#reg").val("");'/>Other</label>
</td></tr>
<tr><td>Registration</td><td colspan=2><input type='text' id=reg name='reg' size=60 placeholder='Attendee Registration Link'/></td></tr>
<tr><td>Account</td><td colspan=2><input type='text' name='account' size=20 placeholder='Host Account'/></td></tr>
<tr><td>Call ID</td><td colspan=2><input type='text' name='id' size=15 placeholder='Zoom Call ID'/></td></tr>
<tr><td>Password</td><td colspan=2><input type='text' name='password' size=15 placeholder='Zoom Call Password'/></td></tr>
</table>
<input type='submit' onclick='submitForm("#newEvent", "scripts/createVBItem.php", eventCreated, null)'/>
</form>
<hr/>
<?php
$schedule = array('Friday'=>array(), 'Saturday'=>array(), 'Sunday'=>array(), 'Monday'=>array());
$newItems = $schedule;
$numItems = array('Total'=>0, 'Friday'=>0, 'Saturday'=>0, 'Sunday'=>0, 'Monday'=>0);
$numNew = $numItems;

$dir = "/var/regJournal/testSchedule/";

$date = new DateTime();
$date->add(DateInterval::createFromDateString('yesterday'));

//echo $date->getTimestamp();


foreach ($schedule as $key => $value) {
    $f = fopen($dir . $key, 'r') or die("Unable to open $dir$key\n");
    while ($ln = fgets($f)) {
        $item = unserialize($ln);
        //echo $item['create'];
        if($item['create'] > $date->getTimestamp()) {
            $numNew['total'] = $numNew['total']+1;
            array_push($newItems[$key], $item);
        }
        $numItems['total'] = $numItems['total']+1;
        array_push($schedule[$key], $item);
    }
}
?>

New: <?php echo $numNew['total']; ?> Items <br/>
<?php
//var_dump($newItems);
foreach($newItems as $key => $value) {
    echo "<h3>$key</h3>\n";
    $sorted = usort($value, function($a, $b) { return $a['sort'] > $b['sort']; });
    $time = 0;
    foreach($value as $event) {
        if($event['sort'] > $time) {
            echo "<h4>" . $event['time'] . "</h4>\n";
            $time = $event['sort'];
        }
        ?>
        <p>
        <strong><?php echo $event['title']; ?></strong> (<?php echo $event['duration'];?>)<br/>
        <?php echo $event['panelists']; ?><br/>
        <?php echo $event['desc']; ?><br/>
        <em><?php echo $event['tags']; ?></em><br/>
        Registration: <?php echo $event['link']; ?><br/>
        Tech Time: <?php echo $event['tech']; ?><br/>
        <?php echo $event['account']; ?><br/>
        <?php echo $event['id'] . " " . $event['password']; ?><br/>
        </p>
        <?php
    }
}

?>
<hr/>
Full Schedule: <?php echo $numItems['total']; ?><br/>
<?php foreach ($schedule as $key => $value) {
    echo "<h3>$key</h3>\n";
    $sorted = usort($value, function($a, $b) { return $a['sort'] > $b['sort']; });
    $time = 9;
    foreach($value as $event) {
        if($event['sort'] > $time) {
            echo "<h4>" . $event['time'] . "</h4>\n";
            $time = $event['sort'];
        }
        ?>
        <p>
        <strong><?php echo $event['title']; ?></strong> (<?php echo $event['duration'];?>)<br/>
        <?php echo $event['panelists']; ?><br/>
        <?php echo $event['desc']; ?>
        <em><?php echo $event['tags']; ?></em><br/>
        Registration: <?php echo $event['link']; ?><br/>
        Tech Time: <?php echo $event['tech']; ?><br/>
        <?php echo $event['account']; ?><br/>
        <?php echo $event['id'] . " " . $event['password']; ?><br/>
        </p>
        <?php
    }
}?>

</div>
<div id='test'></div>
