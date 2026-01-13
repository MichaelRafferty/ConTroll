<?php
require_once "lib/base.php";
require_once 'lib/sessionAuth.php';

$page = 'lookup';
$authToken = new authToken('web');
if (!$authToken->isLoggedIn() || !$authToken->checkAuth($page)) {
    bounce_page('index.php');
}

$con = get_con();
$conid = $con['id'];

$conf = get_conf('con');
$google = get_conf('google');
$reg_conf = get_conf('reg');
$url = $google['redirect_base'];

$cdn = getTabulatorIncludes();
page_init($page,
    /* css */ array('css/base.css', $cdn['tabcss'], $cdn['tabbs5']
                   ),
    /* js  */ array($cdn['tabjs'],
                    'js/lookup.js',
                   ),
              $authToken);

$config_vars = array();
$config_vars['label'] = $con['label'];
$config_vars['regadminemail'] = $conf['regadminemail'];
$config_vars['debug'] = getConfValue('debug', 'controll_lookup', 0);
$config_vars['conid'] = $conid;
$config_vars['tokenStatus'] = $authToken->checkToken();
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
