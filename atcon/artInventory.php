<?php

require("lib/base.php");

if (!isset($_SESSION['user'])) {
    header("Location: /index.php");
    exit(0);
}
$con = get_conf("con");
$conid=$con['id'];
$label = $con['label'];
$tab = 'atconArtInventory';
$page = "Atcon Art Inventory";
$mode = 'artinventory';

if (!check_atcon('artinventory', $conid)) {
    header('Location: /index.php');
    exit(0);
}

$cdn = getTabulatorIncludes();
page_init($page, $tab,
    /* css */ array($cdn['tabcss'], $cdn['tabbs5'],
		    'css/atcon.css','css/registration.css'),
    /* js  */ array( //$cdn['luxon'],
                    $cdn['tabjs'],'js/artInventory.js')
    );

db_connect();

$region = '';
if(array_key_exists('region', $_GET)) { 
    $region = $_GET['region'];
}


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
    var region = '<?php echo $region; ?>';
    <?php if(check_atcon('manager', $conid)) { ?>
        manager = true;
    <?php } ?>
</script>
<?php

$conInfoR = dbSafeQuery($conInfoQ, 'i', array($conid));
$conInfo = $conInfoR->fetch_assoc();
$startdate = $conInfo['start'];
$enddate = $conInfo['end'];
$method='manager';

$regionQ = <<<EOS
SELECT xR.shortname AS regionName
FROM exhibitsRegionTypes xRT
    JOIN exhibitsRegions xR ON xR.regionType=xRT.regionType
    JOIN exhibitsRegionYears xRY ON xRY.exhibitsRegion = xR.id
WHERE xRT.active='Y' AND xRT.usesInventory='Y' AND xRY.conid=?;
EOS;
$regionR = dbSafeQuery($regionQ, 'i', array($conid));
$setRegion = false;
if(($regionR->num_rows==1) && ($region=='')) { $setRegion = true; }

/** /
var_dump($_SESSION);
echo $conid;
/**/

?>
<div id="whoami" hidden><?php echo $_SESSION['user'];?></div>
<div id="main">
    <ul class='nav nav-tabs mb-3' id='region-tabs' role='tablist'>
        <?php
            while($regionInfo = $regionR->fetch_assoc()) {
                if($setRegion) { $region = $regionInfo['regionName']; }
                $isRegion = false;
                if($region == $regionInfo['regionName']) { $isRegion = true; }
                $regionName = $regionInfo['regionName']; 
                $actual_link = 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF'];
                ?>
        <li class='nav-item' role='presentation'>
            <button class='nav-link <?php if($isRegion) { echo 'active'; } ?>' id='<?php echo $regionName; ?>-tab'data-bs-toggle='pill' type='button' role='tab' aria-controls='nav-<?php echo $regionName; ?>' aria-selected='<?php echo $isRegion?'true':'false'; ?>'
                    onclick='window.location = "<?php echo $actual_link . '?region=' . $regionName; ?>"'>
                <?php echo $regionName; ?>
            </button>
        </li>
                <?php
            }
        ?>
    </ul>
</div>
<div id="pos" class="container-fluid">
    <div class="row mt-2">
        <div class="col-sm-7">
            <div id="pos-tabs">
                <div class="tab-content" id="find-content">          
<?php if($region != '') { ?>
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
SELECT xRY.id AS regionYear, xR.shortname AS regionName,
    eRY.exhibitorNumber as art_key, 
    CASE
        WHEN e.artistName IS NOT NULL AND e.artistName != e.exhibitorName AND e.artistName != '' THEN e.artistName
        ELSE e.exhibitorName 
    END as name
FROM exhibitsRegionTypes xRT
    JOIN exhibitsRegions xR ON xR.regionType=xRT.regionType
    JOIN exhibitsRegionYears xRY ON xRY.exhibitsRegion = xR.id
    JOIN exhibitorRegionYears eRY ON eRY.exhibitsRegionYearId = xRY.id
    JOIN exhibitorYears eY ON eY.id = eRY.exhibitorYearId
    JOIN exhibitors e ON e.id=eY.exhibitorId
WHERE xRT.active='Y' AND xRT.usesInventory='Y' AND xRY.conid=? 
    AND xR.shortname=? AND eRY.exhibitorNumber is not null
ORDER BY xRY.id;
EOS;
$artistR = dbSafeQuery($artistQ, 'is', array($conid, $region));
                                    ?>
                                    <select id="artist_num_lookup" name="artist" min=100 max=300 placeholder="Artist #">
                                        <?php 
while($artist = $artistR->fetch_assoc()) {
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
                                    <button type="button" class="btn btn-sm btn-primary" id="find_search_btn" onclick="find_item('search');">Find Item</button>
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
<?php } else { ?>
                    <p>Plese select a region from the tab list above</p>
<?php } ?>
                 </div>
            </div>
        </div>
        <div class="col-sm-5">
            <div id="cart"></div>
            <div class="row">
                <div class="col-sm-12 mt-3">
                    <button type="button" class="btn btn-success btn-sm" id="inventory_btn" onclick="inventory();" hidden>Update Inventory</button>
                    <button type="button" class="btn btn-success btn-sm" id="location_change_btn" onclick="change_locs();" hidden>Set Changed Locations</button>
                    <button type="button" class="btn btn-warning btn-sm" id="startover_btn" onclick="start_over();">Start Over</button>
                    <button type="button" class="btn btn-warning btn-sm" id="void_btn" onclick="void_trans();" hidden>Void</button>
                    <button type="button" class="btn btn-primary btn-sm" id="next_btn" onclick="start_over(0);" hidden>Next Customer</button>
                </div>
            </div>
        </div>       
    </div>
<pre id='test'></pre>
