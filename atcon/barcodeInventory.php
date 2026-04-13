<?php

require("lib/base.php");
if (!isSessionVar('user')) {
    header("Location: /index.php");
    exit(0);
}

$con = get_conf("con");
$conid=$con['id'];
$label = $con['label'];
$config_vars['conid'] = $conid;
$config_vars['label'] = $label;

if (!check_atcon('artinventory', $conid)) {
    header('Location: /index.php');
    exit(0);
}

$page = 'Barcode Based Art Inventory';
$manager = check_atcon('manager', $conid) ? 'true' : 'false';

$cdn = getTabulatorIncludes();
barcode_page_init($page,
    /* css */ array($cdn['tabcss'], $cdn['tabbs5'], 'css/style.css'),
    /* js  */ array( //$cdn['luxon'],
                    $cdn['tabjs'],'js/barcodeInventory.js'),
        $config_vars
    );

?>
<div id="whoami" hidden><?php echo getSessionVar('user');?></div>
<script type='text/javascript'>
    var conid = '<?php echo $label; ?>';
    var manager = <?php echo $manager; ?>;
</script>
<div id="main">
    <div id="inventoryModeDiv">
        <label for="inventoryMode"> Inventory Mode: </label>
        <select id="inventoryMode" onchange="inventoryModeChange(); tabindex = 101">
            <option value="">-- SELECT A MODE --</option>
            <option value="checkin">Check In</option>
            <option value="bid">Record Bids</option>
            <option value="checkout">Check Out</option>
        </select>
    </div>
    <div id="barcodeDiv mt-2">
        <label for="barcode" class="mt-2 me-2">Scan Barcode: </label>
        <input type="text" id="barcode" placeholder="Scan Here" size="20" tabindex="110"/>
    </div>
    <div id="printDiv" class="mt-4" hidden>
        <div>
            <span id='printQtyType'>Entered</span> Qty:&emsp;&emsp; <span id='printQty'>x</span>
        </div>
        <div id="printDBInfo" class="mt-2">
            <label for="quantity" class="me-3" id="printmode">Received Qty: </label>
            <input type='number' id='quantity' class='no-spinners' placeholder='Qty' size="5" style="width: 80px;"
                   onchange="printQtyChanged();" tabindex = "120"/>
        </div>
    </div>
    <div id='bidDiv' hidden>
        <div id="curBidInfo" class="mt-4">
            Current Bidder:&ensp; <span id="dbBidder">x</span>, High Bid: <span id="dbBid">x</span>
        </div>
        <div id="newBidInfo" class="mt-2">
            <label for="bidder" class='me-5'>Bidder: </label>
            &nbsp;&nbsp;&nbsp;<input type='number' class='no-spinners' id='bidder' placeholder='High Bidder' size='20' tabindex="130"/>
            <br/>
            <label for="bid" class="mt-2 me-3">New high bid: </label>
            <input type='number' id='bid' class='no-spinners' placeholder='New Bid' size='20' tabindex="140"/>
            <label for="toAuction" class="ms-3">To Auction: </label>
            <input type="checkbox" id="toAuction" tabindex="1"/>
        </div>
    </div>
    <br/>
    <button class="btn btn-primary mt-2" id="inventoryButton" onclick="inventory(1);" tabindex= "160" disabled>Update</button>
    <button class="btn btn-primary mt-2 ms-1" id="inventoryNoChange" onclick="inventory(2);" tabindex= "165" hidden>No Change</button>
    <button class="btn btn-warning mt-2 ms-1" id="inventoryOverride" onclick="inventory(3);" tabindex= "170" hidden>Override</button>
    <button class="btn btn-secondary mt-2 ms-3" id="clearButton" onclick="clearScreen();" tabindex= "180">Start Over</button>
    <button class="btn btn-secondary mt-2 ms-3" id="closeButton" onclick="window.close();" tabindex= "190">Close Window</button>
</div>
<div id='result_message' class="mt-2"></div>
<pre id='test'></pre>
