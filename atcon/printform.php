<?php

require_once "lib/base.php";

if (!isset($_SESSION['user'])) {
    header('Location: /index.php');
    exit(0);
}

$con = get_conf('con');
$conid = $con['id'];
$method = 'cashier';
$page = 'Print Form';

if (!check_atcon($method, $conid)) {
    header('Location: /index.php');
    exit(0);
}

page_init($page, 'printform',
    /* css */ array(),
    /* js  */ array('js/printform.js')
);

//var_dump($_SESSION);
//echo $conid;

// get list of ages for pulldown
$ageSQL = <<<EOS
SELECT ageType
FROM ageList
WHERE conid = ?
ORDER BY sortorder;
EOS;
$ages = array();
$ageQ = dbSafeQuery($ageSQL, 'i', array($conid));
while ($l = fetch_safe_assoc($ageQ)) {
    $ages[] = $l['ageType'];
}
mysqli_free_result($ageQ);

// categorys is memTypes
$categorySQL = <<<EOS
SELECT memCategory
FROM memCategories
ORDER BY sortorder;
EOS;
$categories = array();
$categoryQ = dbQuery($categorySQL);
while ($l = fetch_safe_assoc($categoryQ)) {
    $categories[] = $l['memCategory'];
}
mysqli_free_result($categoryQ);

$durationSQL = <<<EOS
SELECT memType
FROM memTypes
ORDER BY sortorder;
EOS;
$durations = array();
$durationQ = dbQuery($durationSQL);
while ($l = fetch_safe_assoc($durationQ)) {
    $durations[] = $l['memType'];
}
mysqli_free_result($durationQ);
// compute days from con duration in conlist
$daySQL = <<<EOS
SELECT startdate, enddate
FROM conlist
WHERE id = ?;
EOS;
$dayQ = dbSafeQuery($daySQL, 'i', array($conid));
$l = fetch_safe_assoc($dayQ);
$day = strtotime($l['startdate']);
$end = strtotime($l['enddate']);

$days=array();
while ($day <= $end) {
    $days[] = date('l', $day);
    $day += 24*60*60;
}
mysqli_free_result($dayQ);
//var_error_log($days);

?>
<div class='container-fluid mt-4'>
    <form method='POST' id='newBadge' name="newBadge" class='form-floating' action='javascript:void(0);'>
        <div class="row">
            <div class="col-sm-4">
                <div class='form-floating mb-3'>
                    <input type='text' name='badge_name' id='badge_name' placeholder='Badge Name' maxlength=32 size=32 class='form-control' required/>
                    <label for='badge_name'>Badge Name:</label>
                </div>
            </div>
        </div>
        <div class='row'>
            <div class='col-sm-4'>
                <div class='form-floating mb-3'>
                    <input type='number' name='badge_id' id='badge_id' placeholder='Badge ID' min='1' max='999999' class='no-spinners form-control' required/>
                    <label for='badge_id'>Badge ID:</label>
                </div>
            </div>
        </div>
        <div class='row'>
            <div class="col-sm-1">
                <label for="type">Duration:</label>
            </div>
            <div class='col-sm-auto'>
                <select name="type" id="type">
                    <?php foreach ($durations as $duration)  echo "<option>$duration</option>\n";  ?>
                </select>
            </div>
            <div class='col-sm-auto'>
                <label for="day">Day:</label>
            </div>
            <div class="col-sm-auto">
                <select name='day' id='day'>
                    <option></option>
                    <?php foreach ($days as $day)  echo "<option>$day</option>\n";  ?>
                </select>
            </div>
        </div>
        <div class='row mt-2'>
            <div class='col-sm-1'>
                <label for="age">Age:</label>
            </div>
            <div class='col-sm-auto'>
                <select name='age' id='age'>
                    <?php foreach ($ages as $age)  echo "<option>$age</option>\n";  ?>
                </select>
            </div>
        </div>
        <div class='row mt-2'>
            <div class='col-sm-1'>
                <label for='age'>Category:</label>
            </div>
            <div class='col-sm-auto'>
                <select name='category' id='category'>
                    <?php foreach ($categories as $category) echo "<option>$category</option>\n"; ?>
                </select>
            </div>
        </div>
        <div class="row mt-4">
            <div class="col-sm-auto">
                <button type="submit" class="btn btn-primary" id="printform_btn" onclick="printTestLabel();">Print</button>
            </div>
        </div>
    </form>
</div>
<div id='result_message' class='mt-4 p-2'></div>
<pre id='test'></pre>
</body>
</html>
