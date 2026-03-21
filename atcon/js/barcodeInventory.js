
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
    scanField.addEventListener('keyup', (e)=> { if (e.code === 'Enter' && !inProcess) inventory(0); });
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
            show_message("This is a print, you cannot record a bid on a print", 'error');
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
                show_message("Please enter a numeric bid > 0", 'error');
                return;
            }
        }
        if (mode == 0)
            return;
        toAuction = toAuctionField.checked ? 1 : 0;
        bidder = Number(bidderField.value);
        if (isNaN(bidder) || bidder < 1) {
            show_message("Please enter a numeric bidder id", 'error');
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
    bidderField
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
