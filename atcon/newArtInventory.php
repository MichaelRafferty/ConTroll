<?php

require("lib/base.php");

if (!isset($_SESSION['user'])) {
    header("Location: /index.php");
    exit(0);
}
$tab = 'Art Inventory';
$page = "Atcon Art Inventory";
$mode = 'inventory';

if (isset($_GET['mode'])) {
    if ($_GET['mode'] == 'sales') {
        $mode = 'sales';
    } 
    if ($mode == 'sales') {
        $tab = 'sales';
    }
}


page_init($page, $tab,
    /* css */ array('https://unpkg.com/tabulator-tables@5.4.4/dist/css/tabulator.min.css','css/atcon.css','css/registration.css','css/mockup.css'),
    /* js  */ array( //'https://cdn.jsdelivr.net/npm/luxon@3.1.0/build/global/luxon.min.js',
                    'https://unpkg.com/tabulator-tables@5.4.4/dist/js/tabulator.min.js','js/atcon.js','js/artInventory.js')
    );

db_connect();

$con = get_conf("con");
$conid=$con['id'];
$label = $con['label'];
$conInfoQ = <<<EOS
SELECT DATE(startdate) as start, DATE(enddate) as end
FROM conlist
WHERE id=?;
EOS;

?>
<script type="text/javascript">
    var mode = '<?php echo $mode; ?>';
    var conid = '<?php echo $label; ?>';
</script>
<?php

$conInfoR = dbSafeQuery($conInfoQ, 'i', array($conid));
$conInfo = fetch_safe_assoc($conInfoR);
$startdate = $conInfo['start'];
$enddate = $conInfo['end'];
$method='manager';

/*
var_dump($_SESSION);
echo $conid;
*/

?>
<div id="pos" class="container-fluid">
    <div class="row mt-2">
        <div class="col-sm-7">
            <div id="pos-tabs">
                 <ul class="nav nav-pills mb-2" id="tab-ul" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="find-tab" data-bs-toggle="pill" data-bs-target="#find-pane" type="button" role="tab" aria-controls="nav-find" aria-selected="true">Find</button>
                    </li>
                </ul>
                <div class="tab-content" id="find-content">          
                    <div class="tab-pane fade show active" id="find-pane" role="tabpanel" aria-labelledby="reg-tab" tabindex="0">
                        <div class="container-fluid">
                            <div class="row">
                                <div class="col-sm-12 text-bg-primary mb-2">
                                    <div class="text-bg-primary m-2">
                                       Find Item 
                                    </div>
                                </div>
                            </div>
                            <div class="row mt-1">
                                <div class="col-sm-2">
                                    Artist #:
                                </div>
                                <div class="col-sm-3">
                                    <input type="number" id="artist_num_lookup" name="artist" min=100 max=300 placeholder="Artist #"/>
                                </div>
                                <div class="col-sm-2">
                                    Item #:
                                </div>
                                <div class="col-sm-3">
                                    <input type="number" id="item_num_lookup" name="item" min=0 max=100 placeholder="Item #"/>
                                </div>
                            </div>
                            <div class="row mt-3">
                                <div class="col-sm-4">
                                    <button type="button" class="btn btn-small btn-primary" id="find_search_btn" onclick="find_item('search');">Find Item</button>
                                </div>
                            </div>
                            <div class="row mt-3">
                                <div class="col-sm-12 text-bg-secondary">
                                    Search Results
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-sm-12" id="find_results">
                                </div>
                            </div>
                        </div>
                    </div>
                 </div>
            </div>
        </div>
        <div class="col-sm-5">
            <div id="cart"></div>
            <div class="row">
                <div class="col-sm-12 mt-3">
                    <button type="button" class="btn btn-success btn-small" id="complete_btn" onclick="complete_over();" hidden>Complete Transaction</button>
                    <button type="button" class="btn btn-primary btn-small" id="review_btn" onclick="start_review();" hidden>Review Data</button>
                    <button type="button" class="btn btn-warning btn-small" id="startover_btn" onclick="start_over();">Start Over</button>
                    <button type="button" class="btn btn-warning btn-small" id="void_btn" onclick="void_trans();" hidden>Void</button>
                    <button type="button" class="btn btn-primary btn-small" id="next_btn" onclick="start_over(0);" hidden>Next Customer</button>
                </div>
            </div>
        </div>       
    </div>
<pre id='test'></pre>
