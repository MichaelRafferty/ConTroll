
//buttons
var inventoryButton = null;
var scanField = null;
var inventoryTypeSelect = null;
var printDiv = null;
var bidDiv = null;
var inProcess = false;
var quantityField = null;
var printmode = null;
var lastScan = '';
var print = false;
var scanned = '';
var quantity = 1;
var item = '';
var bidField = null;
var bid = -1;
var bidderField = null;
var bidder = -1;
var toAuctionField = null;
var toAuction = 0;

// set up static data and listeners
window.onload = function init_page() {
    // input fields
    inventoryTypeSelect = document.getElementById("inventoryMode");
    scanField = document.getElementById("barcode");
    inventoryButton = document.getElementById("inventoryButton");
    printDiv = document.getElementById("printDiv");
    bidDiv = document.getElementById("bidDiv");
    quantityField = document.getElementById("quantity");
    bidField = document.getElementById("bid");
    bidderField = document.getElementById("bidder");
    toAuctionField = document.getElementById("toAuction");
    printmode = document.getElementById("printmode");
    scanField.addEventListener('keyup', (e)=> { if (e.code === 'Enter' && !inProcess) fetchValues(0); });
    inventoryTypeSelect.focus();
    }

// mode changed, set the hidden div values, and clear saved values
function inventoryModeChange() {
    inventoryButton.disabled = false;
    let mode = inventoryTypeSelect.value;
    printDiv.hidden = true;
    bidDiv.hidden = true;
    if (mode == 'bid') {
        bidField.value = '';
        bidderField.value = '';
        bid = '';
        bidder = -1;
        toAuctionField.checked = false;
        toAuction = 0;
        quantityField.value = 1;
    }
    inProcess = false;
    scanField.focus();
    if (mode == 'checkin')
        printmode.innerHTML = 'Received Qty: ';
    if (mode == 'checkout')
        printmode.innerHTML = 'Return Qty:&nbsp;&nbsp;&nbsp; ';
    lastScan = '---------------------------';
    clear_message();
}

function fetchValues(mode) {
    // get the current scan code, it should not be emtpy
    let scancode = scanField.value;
    if (scancode == '') {
        show_message('Please scan a barcode', 'warn');
        return;
    }
    let type = inventoryTypeSelect.value;
    if (type == '') {
        show_message('Please select an inventory mode', 'warn');
        return;
    }
    let scanDiffer = lastScan != scancode;
    if (!scanDiffer)
        return inventory(mode);

    // fetch the values
    scanned = scancode.split(',');
    item = scanned[0].trim();
    let script = 'scripts/artinventory_barcodeInventory.php';
    $.ajax({
        method: "POST",
        url: script,
        data: { pollitem: item, },
        success: function(data, textStatus, jqXhr) {
            fetchValuesSuccess(data, mode);                ;
        },
        error: function (jqXHR, textstatus, errorThrown) {
            inProcess = false;
            inventoryButton.disabled = false;
            showAjaxError(jqXHR, textstatus, errorThrown);
        },
    });
}

// deal with the values
function fetchValuesSuccess(data, mode) {
    if (data.error) {
        show_message(data.error, 'error');
        inProcess = false;
        inventoryButton.disabled = false;
        scanField.focus();
        return;
    }
    if (data.numRows != 1) {
        clearScreen();
        show_message("Item " + data.pollitem + ' not found', 'error');
        return;
    }
    if (data.item.conid != config.conid) {
        clearScreen();
        show_message("Item " + data.pollitem + ' (' + data.item.title + ') for Artist ' + data.item.exhibitorNumber +
            '<br/>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;is from conid ' + data.item.conid + '.  This is conid ' + config.conid, 'error');
        return;
    }

    let type = inventoryTypeSelect.value;
    if (type == 'bid' && data.item.type == 'nfs') {
        clearScreen();
        show_message("Item " + data.pollitem + ' (' + data.item.title + ') for Artist ' + data.item.exhibitorNumber +
            '<br/>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;is NOT FOR SALE and cannot be bid on', 'error');
        return;
    }

    if (data.item.type != 'print') {
        scanField.value = data.item.id;
    }
    return inventory(mode);
}

// process inventory update, mode 0 = scan entered, mode 1 = inventory button pressed
function inventory(mode) {
    clear_message();
    
    // get the current scan code, it should not be emtpy
    let scancode = scanField.value;
    if (scancode == '') {
        show_message('Please scan a barcode', 'warn');
        return;
    }
    
    let scanDiffer = lastScan != scancode;
    lastScan = scancode;
    let type = inventoryTypeSelect.value;
    if (type == '') {
        show_message('Please select an inventory mode', 'warn');
        return;
    }
    
    if (scanDiffer) {
        // new scan, force mode = 0, so quantity and bid can be updated as needed
        // and get the new values from the scan string
        mode = 0;
        scanned = scancode.split(',');
        item = scanned[0].trim();
        print = scanned.length > 1;
    }

    if (print) {
        if (type == 'bid') {
            clearScreen();
            show_message(scancode + "  is a print. You cannot record a bid on a print.", 'error');
            return;
        }
        printDiv.hidden = false;
        bidDiv.hidden = true;

        if (scanDiffer) {
            if (type == 'checkout')
                quantity = '';
            else {
                quantity = Number(scanned[1].trim());
                quantity = Number(quantity);
                quantityField.value = quantity;
            }
        } else
            quantity = quantityField.value;

        if (isNaN(quantity)) {
            show_message("Please enter a numeric quantity", 'error');
            return;
        }

        let minquantity = type == 'checkin' ? 1 : 0;
        if ((!scanDiffer && quantity == '') || quantity < minquantity) {
            show_message("Print quantity cannot be less than " + minquantity + " for " + type, 'error')
            return;
        }
        if (mode == 0)
            return;
    } else {
        printDiv.hidden = true;
    }

    // for bid, do the same sort of processing for print except for the bid value
    if (type == 'bid') {
        printDiv.hidden = true;
        bidDiv.hidden = false;

        if (scanDiffer) {
            bid = '';
            bidField.value = '';
            toAuction = 0;
            toAuctionField.checked = false;
        } else {
            bid = Number(bidField.value);
            if (isNaN(bid) || bid <= 0) {
                show_message("Please enter a numeric bid greater than 0", 'error');
                return;
            }
        }
        if (mode == 0)
            return;
        toAuction = toAuctionField.checked ? 1 : 0;
        bidder = Number(bidderField.value);
        if (isNaN(bidder) || bidder < 1) {
            show_message("Please enter the bidder's badge id (numeric)", 'error');
            return;
        }
    } else {
        bidDiv.hidden = true;
    }

    inProcess = true;
    inventoryButton.disabled = true;

    let script = 'scripts/artinventory_barcodeInventory.php';
    $.ajax({
            method: "POST",
            url: script,
            data: { type: type, item: item, quantity: quantity, bid: bid, print: print ?  '1' : '0', toAuction: toAuction, bidder: bidder },
            success: function(data, textStatus, jqXhr) {
                inventoryUpdate(data);                ;
            },
        error: function (jqXHR, textstatus, errorThrown) {
            inProcess = false;
            inventoryButton.disabled = false;
            showAjaxError(jqXHR, textstatus, errorThrown);
        },
    });
}

function inventoryUpdate(data) {
    if (data.error) {
        show_message(data.error, 'error');
        inProcess = false;
        inventoryButton.disabled = false;
        scanField.focus();
        return;
    }

    if (data.warn) {
        show_message(data.warn, 'warn');
    }

    if (data.message) {
        show_message(data.message, 'success');
    }

    clearScreen();
}

function clearScreen() {
    scanField.value = '';
    lastScan = '---------------------------';
    quantity = inventoryTypeSelect.value == 'checkin' ? 1 : 0;
    quantityField.value = quantity;
    bidField.value = '';
    bid = '';
    bidderField.value = '';
    bidder = -1;
    toAuction = 0;
    toAuctionField.checked = false;
    inProcess = false;
    printDiv.hidden = true;
    bidDiv.hidden = true;
    print = false;
    inventoryButton.disabled = false;
    scanField.focus();
    return;
}
