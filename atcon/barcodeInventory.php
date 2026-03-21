<?php

require("lib/base.php");
if (!isSessionVar('user')) {
    header("Location: /index.php");
    exit(0);
}

$con = get_conf("con");
$conid=$con['id'];
$label = $con['label'];

if (!check_atcon('artinventory', $conid)) {
    header('Location: /index.php');
    exit(0);
}

$page = 'Barcode Art Inventory';
$manager = check_atcon('manager', $conid) ? 'true' : 'false';

$cdn = getTabulatorIncludes();
barcode_page_init($page,
    /* css */ array($cdn['tabcss'], $cdn['tabbs5']),
    /* js  */ array( //$cdn['luxon'],
                    $cdn['tabjs'],'js/barcodeInventory.js')
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

$conInfoR = dbSafeQuery($conInfoQ, 'i', array($conid));
$conInfo = $conInfoR->fetch_assoc();
$startdate = $conInfo['start'];
$enddate = $conInfo['end'];
$method='manager';
?>
<div id="whoami" hidden><?php echo getSessionVar('user');?></div>
<script type='text/javascript'>
    var conid = '<?php echo $label; ?>';
    var manager = <?php echo $manager; ?>;
</script>
<div id="main">
    Inventory Mode: <select id="inventoryMode" onchange="inventoryModeChange();">
        <option value="checkin">Check In</option>
        <option value="bid">Record Bids</option>
        <option value="checkout">Check Out</option>
    </select>
    &nbsp;
    <input type="text" id="barcode" placeholder="Scan Here" size="20"/>
    <br/>
    <div class='mt-3' id="printDiv" hidden>
        <span id="printmode">Received Quantity: </span>
        <input type='number' id='quantity' placeholder='Qty' size="5" style="width: 80px;"/>
    </div>
    <div class='mt-3' id='bidDiv' hidden>
        New high bid:
        <input type='number' id='bid' placeholder='New Bid' size='20'/>
    </div>
    <br/>
    <button class="btn btn-primary mt-2" id="inventoryButton" onclick="inventory(1);">Inventory</button>
</div>
<div id='result_message' class="mt-2"></div>
<pre id='test'></pre>
