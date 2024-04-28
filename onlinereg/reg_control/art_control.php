<?php
require_once "lib/base.php";
//initialize google session
$need_login = google_init("page");

$page = "art_control";
if(!$need_login or !checkAuth($need_login['sub'], $page)) {
    bounce_page("index.php");
}

page_init($page,
    /* css */ array('css/base.css',
                    'https://unpkg.com/tabulator-tables@5.6.1/dist/css/tabulator.min.css',
                    //'css/art_control.css'
                   ),
    /* js  */ array('https://unpkg.com/tabulator-tables@5.6.1/dist/js/tabulator.min.js',
                    'js/d3.js',
                    'js/base.js',
                    //'js/table.js',
                    'js/art_control.js'
                   ),
              $need_login);

$con = get_con();
$conid = $con['id'];

$conf = get_conf('con');

$regionsQ = <<<EOS
SELECT eR.id, eR.shortname
FROM exhibitsRegionTypes eRT
    JOIN exhibitsRegions eR on eR.regionType=eRT.regionType
WHERE eRT.usesInventory='Y';
EOS;

$regionsR = dbQuery($regionsQ);

$regions = array();
while($region = $regionsR->fetch_assoc()) {
    $regions[] = $region;
}

?>
<div id="main">
    <ul class='nav nav-tabs mb-3' id='region-tabs' role='tablist'>
        <li class='nav-item active' role='presentation'>
            <button class='nav-link' id='overview-tab' data-bs-toggle='pill' type='button' role='tab' aria-controls='nav-overview' aria-selected='true' onclick="setRegion('overview',null)"> Overview </button>
        
<?php 
$regionNum = 0;
foreach ($regions as $region) {
    $name = $region['shortname'];
    $id = $region['id'];
    $ariaSelected = 'false';
    if($regionNum++ == 0) { $ariaSelected = 'true'; }
?>
        <li class='nav-item' role='presentation'>
            <button class='nav-link' id='<?php echo $name; ?>-tab' data-bs-toggle='pill' type='button' role='tab' aria-controls='nav-<?php echo $name; ?>' aria-selected='false' onclick="setRegion(<?php echo '\'' . $name . '\',' . $id; ?>)">
            <?php echo $name; ?>
            </button>
        </li>
<?php } ?>
    </ul>
<div class="container-fluid">
    <div class="row">
        <div class="col-sm-auto p-0">
            <div id='artItems_table'></div>
            <div id="artItems_buttons">
                <button id='item-undo' type='button' class='btn btn-secondary btn-sm' onclick="undoItem(); return false;" disabled>Undo</button>
                <button id='item-redo' type='button' class='btn btn-secondary btn-sm' onclick="redoItem(); return false;" disabled>Redo</button>
                <button id='item-addnew' type='button' class='btn btn-secondary btn-sm' onclick="addnewItem(); return false;" disabled>Add New</button>
                <button id='item-save' type='button' class='btn btn-secondary btn-sm' onclick="saveItem(); return false;" disabled>Save</button>
            </div>
        </div>
    </div>
    <div id='result_message' class='mt-4 p-2'></div>
</div>
</div>
<pre id='test'>
</pre>
<?php
page_foot($page);
?>
