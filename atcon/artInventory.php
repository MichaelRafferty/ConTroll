<?php

require("lib/base.php");

if (!isset($_SESSION['user'])) {
    header("Location: /index.php");
    exit(0);
}
$con = get_conf("con");
$conid=$con['id'];
$label = $con['label'];
$tab = 'Art Inventory';
$page = "Atcon Art Inventory";
$mode = 'artinventory';

if (!check_atcon('artinventory', $conid)) {
    header('Location: /index.php');
    exit(0);
}

page_init($page, $tab,
    /* css */ array('https://unpkg.com/tabulator-tables@5.5.0/dist/css/tabulator.min.css','css/atcon.css','css/registration.css'),
    /* js  */ array( //'https://cdn.jsdelivr.net/npm/luxon@3.1.0/build/global/luxon.min.js',
                    'https://unpkg.com/tabulator-tables@5.5.0/dist/js/tabulator.min.js','js/atcon.js','js/artInventory.js')
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
                                <div class="col-sm-4">
                                    <?php
$artistQ = <<<EOS
SELECT S.art_key, V.name 
FROM artshow S
JOIN artist A ON A.id=S.artid
JOIN vendors V on V.id=A.vendor
WHERE S.conid=? ORDER BY S.art_key
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
                                <!---find by location--->
                                </div>
                                <div class="col-sm-2">
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
                    <button type="button" class="btn btn-success btn-small" id="inventory_btn" onclick="inventory();" hidden>Update Inventory</button>
                    <button type="button" class="btn btn-success btn-small" id="location_change_btn" onclick="change_locs();" hidden>Set Changed Locations</button>
                    <button type="button" class="btn btn-warning btn-small" id="startover_btn" onclick="start_over();">Start Over</button>
                    <button type="button" class="btn btn-warning btn-small" id="void_btn" onclick="void_trans();" hidden>Void</button>
                    <button type="button" class="btn btn-primary btn-small" id="next_btn" onclick="start_over(0);" hidden>Next Customer</button>
                </div>
            </div>
        </div>       
    </div>
<pre id='test'></pre>
