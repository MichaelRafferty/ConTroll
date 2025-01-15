<?php
require_once "lib/base.php";
require_once "../lib/paymentPlans.php";

//initialize google session
$need_login = google_init("page");

$page = "finance";
if(!$need_login or !checkAuth($need_login['sub'], $page)) {
    bounce_page("index.php");
}

$con = get_con();
$conid = $con['id'];
$debug = get_conf('debug');
$regConf = get_conf('reg');
$conConf = get_conf('con');
$usps = get_conf('usps');
if (array_key_exists('controll_finance', $debug))
    $debug_finance = $debug['controll_finance'];
else
    $debug_finance = 0;


$cdn = getTabulatorIncludes();
page_init($page,
    /* css */ array($cdn['tabcss'], $cdn['tabbs5'],'css/base.css'),
    /* js  */ array(
                    //$cdn['luxon'],
                    $cdn['tabjs'],
                    'js/tinymce/tinymce.min.js',
                    'js/finance.js',
                    'js/planSetup.js'
                   ),
              $need_login);


$config_vars = array();
$config_vars['pageName'] = 'finance';
$config_vars['label'] = $con['label'];
$config_vars['vemail'] = $conConf['regadminemail'];
$config_vars['debug'] = $debug_finance;
$config_vars['conid'] = $conid;
$paymentPlans = getPaymentPlans(false);

// modals
//bs_tinymceModal();

?>
<div class="container-fluid" id='main'>
    <ul class='nav nav-tabs mb-3' id='finance-tab' role='tablist'>
        <li class='nav-item' role='presentation'>
            <button class='nav-link active' id='overview-tab' data-bs-toggle='pill' data-bs-target='#overview-pane' type='button' role='tab'
                    aria-controls='nav-overview' aria-selected="true" onclick="finance.setFinanceTab('overview-pane');">Overview
            </button>
        </li>
        <li class='nav-item' role='presentation'>
            <button class='nav-link' id='paymentplans-tab' data-bs-toggle='pill' data-bs-target='#paymentplans-pane' type='button' role='tab'
                    aria-controls='nav-configuration' aria-selected='false' onclick="finance.setFinanceTab('paymentplans-pane');">
                Payment Plan Configuration
            </button>
        </li>
<?php
// additional computed tabs go here
?>
    </ul>
<script type='text/javascript'>
    var config = <?php echo json_encode($config_vars); ?>;
    var paymentPlans = <?php echo json_encode($paymentPlans); ?>;
</script>
    <div class='tab-content ms-2' id='overview-content'>
        <div class='container-fluid'>
            <div class='row'>
                <div class='col-sm-12'>
                    <h3 style='text-align: center;'><strong>Finance Overview</strong></h3>
                </div>
            </div>
            <div class='row'>
                <div class="col-sm-12">
                    <p>The Finance tab handles functions related to money:</p>
                    <ol>
                        <li>Payment Plan Configuration</li>
                        <li>Payor Plans</li>
                        <li>Refunds</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
    <div class='tab-pane fade' id='paymentPlan-pane' role='tabpanel' aria-labelledby='paymentPlan-tab' tabindex='0'></div>
    <div id='result_message' class='mt-4 p-2'></div>
    <pre id='test'></pre>
</div>

<?php
page_foot($page);
?>
