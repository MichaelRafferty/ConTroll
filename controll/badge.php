<?php
global $db_ini;
require_once "lib/base.php";
require_once '../lib/policies.php';
require_once '../lib/profile.php';

//initialize google session
$need_login = google_init("page");

$page = "badge";

$con = get_con();
$conid = $con['id'];

$conf = get_conf('con');
$google = get_conf('google');
$reg_conf = get_conf('reg');
$debug = get_conf('debug');
$usps = get_conf('usps');
$url = $google['redirect_base'];

if(!$need_login or !checkAuth($need_login['sub'], $page)) {
    bounce_page("index.php");
}

// Get list of freebie badge types for pulldown
$freeSQL = <<<EOS
SELECT M.id, M.label
FROM memList M
WHERE M.conid = ? and M.memCategory in ('freebie', 'goh');
EOS;

$freeMems = [];
$freeR = dbSafeQuery($freeSQL, 'i', array($conid));
if ($freeR == false) {
    header("Location: $url?msg=Error%20Loading%20memList");
    exit();
}
while($free = $freeR->fetch_assoc()) {
    $freeMems[] = $free;
}
$freeR->free();

$cdn = getTabulatorIncludes();
page_init($page,
    /* css */ array('css/base.css', $cdn['tabcss'], $cdn['tabbs5']
                   ),
    /* js  */ array($cdn['tabjs'],
                    'js/badge.js',
                    'js/people_add.js',
                    'js/people_find.js'
                   ),
              $need_login);


    $freeSelect = "<option disabled='disabled' selected='true' value='-1'> -- select an option --</option>\\n";
    foreach ($freeMems as $free) {
        $freeSelect .= "<option value='" . $free['id'] . "'>" . $free['label'] . "</option>\\n";
    }

if (array_key_exists('controll_freebadge', $debug))
    $debug_freebadge=$debug['controll_freebadge'];
else
    $debug_freebadge = 0;

$useUSPS = false;
if (($usps != null) && array_key_exists('secret', $usps) && ($usps['secret'] != ''))
    $useUSPS = true;

$policies = getPolicies();
$config_vars = array();
$config_vars['label'] = $con['label'];
$config_vars['regadminemail'] = $conf['regadminemail'];
$config_vars['debug'] = $debug_freebadge;
$config_vars['conid'] = $conid;
$config_vars['required'] = $reg_conf['required'];
$config_vars['useUSPS'] = $useUSPS;
?>
<script type='text/javascript'>
    var config = <?php echo json_encode($config_vars); ?>;
    var policies = <?php echo json_encode($policies, JSON_FORCE_OBJECT | JSON_HEX_QUOT); ?>;
    var freeMems = <?php echo json_encode($freeMems, JSON_FORCE_OBJECT | JSON_HEX_QUOT); ?>;
    var freeSelect = <?php echo json_encode($freeSelect, JSON_FORCE_OBJECT | JSON_HEX_QUOT); ?>;
</script>
<div id='edit-person' class='modal modal-xl fade' tabindex='-1' aria-labelledby='Edit Person'
     aria-hidden='true' style='--bs-modal-width: 98%;'>
    <div class='modal-dialog'>
        <div class='modal-content'>
            <div class='modal-header bg-primary text-bg-primary'>
                <div class='modal-title'>
                    <strong id='editTitle'>Editing <span id='editPersonName'>Name</span></strong>
                </div>
                <button type='button' class='btn-close' data-bs-dismiss='modal' aria-label='Close'></button>
            </div>
            <div class='modal-body' style='padding: 4px; background-color: lightcyan;'>
                <div class='container-fluid'>
                    <div class='row mt-2'>
                        <div class='col-sm-12'><h2 class='size=h3'>Profile/Policies</h2></div>
                    </div>
                    <?php
                        drawEditPersonBlock($conid, $useUSPS, $policies, 'find', true, true, '', array (), 200, true, 'f_');
                    ?>
                </div>
                <div id='find_edit_message' class='mt-4 p-2'></div>l
            </div>
            <div class='modal-footer'>
                <button class='btn btn-sm btn-secondary' type='button' data-bs-dismiss='modal'>Cancel</button>
                <button class='btn btn-sm btn-primary' type='button' id='updateExisting' onClick='saveEdit()'>Update Existing Person</button>
            </div>
        </div>
    </div>
</div>
<div id='add-person' class='modal modal-xl fade' tabindex='-1' aria-labelledby='Add Person'
     aria-hidden='true' style='--bs-modal-width: 98%;'>
    <div class='modal-dialog'>
        <div class='modal-content'>
            <div class='modal-header bg-primary text-bg-primary'>
                <div class='modal-title'>
                    <strong id='addTitle'>Add New Person</strong>
                </div>
                <button type='button' class='btn-close' data-bs-dismiss='modal' aria-label='Close'></button>
            </div>
            <div class='modal-body' style='padding: 4px; background-color: lightcyan;'>
                <div class='container-fluid'>
                    <div class='row mt-2'>
                        <div class='col-sm-12'><h2 class='size=h3'>Profile/Policies</h2></div>
                    </div>
                    <?php
                        drawEditPersonBlock($conid, $useUSPS, $policies, 'add', true, true, '', array (), 1000, true, 'a_');
                    ?>
                </div>
                <div class='row mt-2'>
                    <div class='col-sm-12' id='addMatchTable'></div>
                </div>
                <div id='add_message' class='mt-4 p-2'></div>
            </div>
            <div class='modal-footer'>
                <button class='btn btn-sm btn-secondary' type='button' data-bs-dismiss='modal'>Cancel</button>
                <button class='btn btn-sm btn-primary' type='button' onclick='addCheckExists();'>Check If Already Exists</button>
                <button class='btn btn-sm btn-secondary' type='button' onclick='addClearForm();'>Clear Add Person Form</button>
                <button class='btn btn-sm btn-secondary' type='button' id='addPersonBTN' onclick='saveAdd();' disabled>Add New Person
            </div>
        </div>
    </div>
</div>
<div class='container-fluid'>
    <div class='row mt-2'>
        <div class="col-sm-auto">
            <button class="btn btn-primary btn-sm" type="button" onClick="findExisting();">Find Prior Member</button>
            <input type="text" id="findName" name="findName" size="80" placeholder="Name/Perid/Email/Address"/>
        </div>
        <div class='col-sm-auto'>
            <button class='btn btn-primary btn-sm' type='button' onClick='addNew();'>Add New Person</button>
        </div>
    </div>
    <div class="row mt-4">
        <div class='col-sm-12' id='select-list'></div>
    </div>
    <div class='row mt-2'>
        <div class="col-sm-12">
            <h1 class="h3">Your current free membership watch list:</h1>
        </div>
        <div class='row mt-2'>
            <div class='col-sm-12' id="watch-list"></div>
        </div>
    </div>
</div>
<div class='container-fluid'>
    <div class='row mt-2'>
        <div class="col-sm-12" id="result_message"></div>
    </div>
</div>
<pre id='test'></pre>
<?php
    page_foot($page);
