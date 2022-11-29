<?php
require_once "lib/base.php";

if (!isset($_SESSION['user'])) {
    header("Location: /index.php");
    exit(0);
}

$page = "Register";

page_init($page, 'printform',
    /* css */ array('css/registration.css','css/atcon.css'),
    /* js  */ array('js/atcon.js')
    );

$con = get_conf("con");
$conid=$con['id'];
$method='cashier';

?>

<form id='newBadge' action='javascript:void(0);'>
<label>Badge Name: <input type='text' size=36 name='badge_name' id='badge_name'/></label><br/>
Category:<select name='category' id='category'>
    <option>voter</option>
    <option>NoRights</option>
    </select><br/>
<label>Badge Id: <input type='number' size='6' name='id' id='badge_id'/></label><br/>
Duration: <select name='type' id='type'>
    <option value='full'>Full Convention</option>
    <option value='oneday'>One Day</option>
    </select>
Day: <select name='day' id='day'>
    <option>Wednesday</option>
    <option>Thursday</option>
    <option>Friday</option>
    <option>Saturday</option>
    <option>Sunday</option>
    </select><br/>
Age: <select name='age' id='age'>
    <option value='adult'>Adult</option>
    <option value='youth'>Youth</option>
    <option value='child'>Child</option>
    <option value='kit'>Kid in Tow</option>
    </select><br/>

    <input type='submit' onclick='printTestLabel();'/>
</form>

</div>

<pre id='test'></pre>
<div id='alert' class='popup'>
    <div id='alertInner'>
    </div>
    <button class='center' onclick='$("#alert").hide();'>Close</button>
</div>
</body></html>
