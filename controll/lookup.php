<?php
require_once "lib/base.php";

//initialize google session
$need_login = google_init("page");

$page = "lookup";

$con = get_con();
$conid = $con['id'];

$conf = get_conf('con');
$google = get_conf('google');
$reg_conf = get_conf('reg');
$debug = get_conf('debug');
$url = $google['redirect_base'];

if(!$need_login or !checkAuth($need_login['sub'], $page)) {
    bounce_page("index.php");
}

$cdn = getTabulatorIncludes();
page_init($page,
    /* css */ array('css/base.css', $cdn['tabcss'], $cdn['tabbs5']
                   ),
    /* js  */ array($cdn['tabjs'],
                    'js/lookup.js',
                   ),
              $need_login);


if (array_key_exists('controll_lookup', $debug))
    $debug_lookup=$debug['controll_lookup'];
else
    $debug_lookup = 0;


$config_vars = array();
$config_vars['label'] = $con['label'];
$config_vars['regadminemail'] = $conf['regadminemail'];
$config_vars['debug'] = $debug_lookup;
$config_vars['conid'] = $conid;
?>
<script type='text/javascript'>
    var config = <?php echo json_encode($config_vars); ?>;
</script>
<div class='container-fluid'>
    <div class='row mt-2'>
        <div class="col-sm-12">
            <h1 class="h3">Lookup Registrations:</h1>
        </div>
    </div>
    <div class='row mt-2'>
        <div class='col-sm-1'><label for="find_pattern"  class='form-label-sm'>Search for:</label></div>
        <div class='col-sm-auto'>
            <input type='text' id='find_pattern' name='find_pattern' maxlength='90' size='90' tabindex='10'
                   placeholder='Name/Portion of (Name, Address, Email, Badgename, Legal Name), Person ID or Transaction ID'/>
        </div>
    </div>
    <div class='row mt-3'>
        <div class='col-sm-1'></div>
        <div class='col-sm-auto'>
            <button class='btn btn-primary btn-sm' type='button' onClick='findRegs();' tabindex='20'>Find Registrations</button>
        </div>
    </div>
    <div class='row mt-2'>
        <div class='col-sm-12 p-0 m-0' id='lookupTable'></div>
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
