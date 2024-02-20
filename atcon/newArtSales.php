<?php

require("lib/base.php");

if (!isset($_SESSION['user'])) {
    header("Location: /index.php");
    exit(0);
}
$con = get_conf("con");
$conid=$con['id'];
$label = $con['label'];
$tab = 'Art Show Cashier';
$page = "Atcon Art Show Cashier";
$mode = 'sales';

if (!check_atcon('artsales', $conid)) {
    header('Location: /index.php');
    exit(0);
}

page_init($page, $tab,
    /* css */ array('https://unpkg.com/tabulator-tables@5.6.1/dist/css/tabulator.min.css',
                 // 'https://unpkg.com/tabulator-tables@5.6.1/dist/css/tabulator_bootstrap5.min.css',
		    'css/atcon.css','css/registration.css'),
    /* js  */ array( //'https://cdn.jsdelivr.net/npm/luxon@3.1.0/build/global/luxon.min.js',
                    'https://unpkg.com/tabulator-tables@5.6.1/dist/js/tabulator.min.js','js/atcon.js','js/newArtSales.js')
    );

db_connect();

$conInfoQ = <<<EOS
SELECT DATE(startdate) as start, DATE(enddate) as end
FROM conlist
WHERE id=?;
EOS;

?>
<script type="text/javascript">
    var mode = '<?php echo $mode; ?>';
    var conid = '<?php echo $label; ?>';
    var manager = false;
    <?php if(check_atcon('manager', $conid)) { ?>
        manager = true;
    <?php } ?>
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
<div id="whoami" hidden><?php echo $_SESSION['user'];?></div>
<div id="pos" class="container-fluid">
    <div class="row mt-2">
        <div class="col-sm-7">
            <div id="pos-tabs">
               <ul class="nav nav-pills mb-2" id="tab-ul" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="customer-tab" data-bs-toggle="pill" data-bs-target="#customer-pane" type="button" role="tab" aria-controls="nav-customer" aria-selected="true">Set Customer</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="find-tab" data-bs-toggle="pill" data-bs-target="#find-pane" type="button" role="tab" aria-controls="nav-find" aria-selected="false" disabled>Find Item</button>
                    </li>
                     <li class="nav-item" role="presentation">
                        <button class="nav-link" id="pay-tab" data-bs-toggle="pill" data-bs-target="#pay-pane" type="button" role="tab" aria-controls="nav-pay" aria-selected="false" disabled>Payment</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="print-tab" data-bs-toggle="pill" data-bs-target="#print-pane" type="button" role="tab" aria-controls="nav-print" aria-selected="false" disabled>Print Receipts</button>
                    </li>
                </ul>

                <div class="tab-content" id="customer-content">
                    <div class="tab-pane fade show active" id="customer-pane" role="tabpanel" aria-labelledby="reg-tab" tabindex="0">
                        <div class="container-fluid">
                            <div class="row">
                                <div class="col-sm-12 text-bg-primary mb-2">
                                    <div class="text-bg-primary m-2">
                                       Set Customer
                                    </div>
                                </div>
                            </div>
                            <div class="row mt-1">
                                <div class="col-sm-4">
                                    <label for="find_pattern" >Search for:</label>
                                </div>
                                <div class="col-sm-8">
                                    <input type="text" id="find_pattern" name="find_name" maxlength="50" size="50" placeholder="Name/Portion of Name, Person (Badge) ID or TransID"/>
                                </div>
                            </div>
                            <div class="row mt-3">
                                <div class="col-sm-4"> <?php # go anon ?>
                                </div>
                                <div class="col-sm-8">
                                    <button type="button" class="btn btn-small btn-primary" id="find_search_btn" name="find_btn" onclick="find_record('search');">Find Record</button>
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
                    <div class="tab-pane fade" id="find-pane" role="tabpanel" aria-labelledby="reg-tab" tabindex="0">
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
                                    <?php
$artistQ = <<<EOS
SELECT S.art_key, V.name 
FROM artshow S
JOIN artist A ON A.id=S.artid
JOIN vendors V on V.id=A.vendor
WHERE S.conid=?
EOS;
$artistR = dbSafeQuery($artistQ, 'i', array($conid));
                                    ?>
                                    <select id="artist_num_lookup" name="artist" min=100 max=300 placeholder="Artist #">
                                        <?php 
while($artist = fetch_safe_assoc($artistR)) {
    echo "<option value='" . $artist['art_key'] . "'>". $artist['art_key'] . " - " . $artist['name'] . "</option>";
}
                                        ?>
                                    </select>
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
            <div class="container-fluid">
                <div class="row mt-3" id='customer' hidden>
                    <div class='col-sm-12 mt-3 text-bg-success'>
                        Customer: <span id='customer-name'></span>
                    </div>
                </div>
            </div>
            <div id="cart"></div>
            <div class="row"> <!--- button row -->
                <div class="col-sm-12 mt-3">
                    <button type="button" class="btn btn-success btn-small" id="inventory_btn" onclick="inventory();" hidden>Update Inventory</button>
                    <button type="button" class="btn btn-success btn-small" id="location_change_btn" onclick="change_locs();" hidden>Set Changed Locations</button>
                    <button type="button" class="btn btn-warning btn-small" id="startover_btn" onclick="start_over();">Start Over</button>
                    <button type="button" class="btn btn-warning btn-small" id="void_btn" onclick="void_trans();" hidden>Void</button>
                    <button type="button" class="btn btn-primary btn-small" id="next_btn" onclick="start_over(0);" hidden>Next Customer</button>
                </div>
            </div>
        </div>       
    </div>
    <div id='result_message' class='mt-4 p-2'></div>
</div>
<pre id='test'></pre>
