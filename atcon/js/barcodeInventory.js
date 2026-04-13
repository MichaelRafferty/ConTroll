
//buttons
var inventoryButton = null;
var inventoryOverride = null;
var inventoryNoChange = null;
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
var lastData = null;
var printQty = null;
var printQtyType = null;
var origInvButtonState = true;
var origOverrideButtonState = false;
var origNoChangeButtonState = false;
var fecthedQty = -1;

// set up static data and listeners
window.onload = function init_page() {
    // input fields
    inventoryTypeSelect = document.getElementById("inventoryMode");
    scanField = document.getElementById("barcode");
    inventoryButton = document.getElementById("inventoryButton");
    inventoryOverride = document.getElementById("inventoryOverride");
    inventoryNoChange = document.getElementById("inventoryNoChange");
    inventoryButton = document.getElementById("inventoryButton");
    printDiv = document.getElementById("printDiv");
    bidDiv = document.getElementById("bidDiv");
    quantityField = document.getElementById("quantity");
    bidField = document.getElementById("bid");
    bidderField = document.getElementById("bidder");
    toAuctionField = document.getElementById("toAuction");
    printmode = document.getElementById("printmode");
    printQty = document.getElementById("printQty");
    printQtyType = document.getElementById("printQtyType");
    scanField.addEventListener('keyup', (e)=> { if (e.code === 'Enter' && !inProcess) fetchValues(); });
    inventoryTypeSelect.focus();
    }

// mode changed, set the hidden div values, and clear saved values
function inventoryModeChange() {
    inventoryButton.disabled = true;
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
    scanField.value = '';
    if (mode == 'checkin')
        printmode.innerHTML = 'Received Qty: ';
    if (mode == 'checkout')
        printmode.innerHTML = 'Return Qty:&nbsp;&nbsp;&nbsp; ';
    lastScan = '---------------------------';
    clear_message();
}

function printQtyChanged(quantity = null, fetchedQty = -1) {
    let dbQuantity = fetchedQty == -1 ? Number(printQty.innerHTML) : fetchedQty;
    let inputQuantity = quantity == null ? quantityField.value : quantity;
    if (dbQuantity == inputQuantity) {
        inventoryButton.disabled = fetchedQty != -1;
        inventoryOverride.hidden = true;
        inventoryNoChange.hidden = fetchedQty == -1;
        inventoryNoChange.disabled = fetchedQty == -1;
    } else {
        inventoryButton.disabled = true;
        inventoryOverride.hidden = false;
        inventoryNoChange.hidden = true
        inventoryNoChange.disabled = true;
    }
}

function fetchValues() {
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

    // fetch the values
    lastScan = scancode;
    fecthedQty = -1;
    scanned = scancode.split(',');
    item = scanned[0].trim();
    let script = 'scripts/artinventory_barcodeInventory.php';
    $.ajax({
        method: "POST",
        url: script,
        data: { pollitem: item, },
        success: function(data, textStatus, jqXhr) {
            inProcess = false;
            fetchValuesSuccess(data);
        },
        error: function (jqXHR, textstatus, errorThrown) {
            inProcess = false;
            inventoryButton.disabled = false;
            showAjaxError(jqXHR, textstatus, errorThrown);
        },
    });
}

// deal with the values
function fetchValuesSuccess(data) {if (data.error) {
        show_message(data.error, 'error');
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
    lastData = data;  // this fetch is the latest data
    return inventory(0, data);
}

// process inventory update, mode 0 = scan entered, mode 1 = inventory button pressed
function inventory(mode, data = null) {
    clear_message();

    // get the current scan code, it should not be emtpy
    let scancode = scanField.value;
    if (scancode == '') {
        show_message('Please scan a barcode', 'warn');
        return;
    }

    let scanDiffer = lastScan != scancode;
    lastScan = scancode;
    if (scanDiffer) {
        fetchValues();
        return;
    }
    let type = inventoryTypeSelect.value;
    if (type == '') {
        clearScreen();
        show_message('Please select an inventory mode', 'warn');
        return;
    }

    if (data == null)  // if called again without a fetch, use the last data item
        data = lastData;

    print = data.item.type == 'print';

    if (print) {
        if (type == 'bid') {
            clearScreen();
            show_message(scancode + "  is a print. You cannot record a bid on a print.", 'error');
            return;
        }
        printDiv.hidden = false;
        bidDiv.hidden = true;

        if (mode == 0) {
            if (type == 'checkout') {
                quantity = data.item.quantity;
                printQtyType.innerHTML = 'Return';
            } else {
                quantity = data.item.original_qty;
                printQtyType.innerHTML = 'Entered';
            }
            printQty.innerHTML = quantity;
            quantityField.value = quantity;
            printQtyChanged(quantity, fecthedQty);
            fecthedQty = quantity;
        } else {
            quantity = Number(quantityField.value);
        }

        if (isNaN(quantity)) {
            show_message("Please enter a numeric quantity", 'error');
            return;
        }

        let minquantity = type == 'checkin' ? 1 : 0;
        if (quantity == '' || quantity < minquantity) {
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

        if (mode == 0) {
            if (data.item.final_price == null || Number(data.item.final_price) == 0) {
                bidField.value = '';
                bidderField.value = '';
                dbBid.innerHTML = 'No bid';
                dbBidder.innerHTML = 'No Bid';
            } else {
                bidField.value = data.item.final_price;
                bidderField.value = data.item.bidder;
                dbBid.innerHTML = data.item.final_price;
                dbBidder.innerHTML = data.item.bidder;
                if (data.item.status == 'To Auction') {
                    toAuctionField.checked = true;
                    show_message("This item is currently in the auction. You cannot enter a bid on it.<br/>" +
                    "if there is an error in the bid or bidder see the administrator.", 'error');
                    return;
                }
            }
            inventoryOverride.hidden = true;
            inventoryNoChange.hidden = false;
            inventoryOverride.disabled = true;
            inventoryNoChange.disabled = false;
            inventoryButton.disabled = false;
            return;
        }
        if (mode != 2 || bidField.value != '') {
            bid = Number(bidField.value);
            if (isNaN(bid) || bid <= 0) {
                show_message("Please enter a numeric bid greater than 0", 'error');
                return;
            }

            toAuction = toAuctionField.checked ? 1 : 0;
            bidder = Number(bidderField.value);
            if (isNaN(bidder) || bidder < 1) {
                show_message("Please enter the bidder's badge id (numeric)", 'error');
                return;
            }
        } else {
            bid = bidField.value;
            bidder = bidderField.value;
            toAuction = toAuctionField.checked ? 1 : 0;
        }

        // check that the bid is valid vs the prior bid and set the override, or no change items as appropriate
        if ((data.item.final_price > bid) || (data.item.final_price == bid && data.item.bidder != bidder)) {
            // new bid is not higher than the current bid
            inventoryOverride.hidden = false;
            inventoryNoChange.hidden = true;
            inventoryOverride.disabled = false;
            inventoryNoChange.disabled = true;
            inventoryButton.disabled = true;
        } else if (data.item.final_price == bid && data.item.bidder == bidder) {
            inventoryOverride.hidden = true;
            inventoryNoChange.hidden = false;
            inventoryOverride.disabled = true;
            inventoryNoChange.disabled = false;
            inventoryButton.disabled = true;
        } else {
            inventoryOverride.hidden = true;
            inventoryNoChange.hidden = true;
            inventoryOverride.disabled = true;
            inventoryNoChange.disabled = true;
            inventoryButton.disabled = false;
        }
    } else {
        bidDiv.hidden = true;
    }

    inProcess = true;
    origInvButtonState = inventoryButton.disabled;
    origOverrideButtonState = inventoryOverride.disabled;
    origNoChangeButtonState = inventoryNoChange.disabled;
    inventoryButton.disabled = true;
    inventoryOverride.disabled = true;
    inventoryNoChange.disabled = true;

    let script = 'scripts/artinventory_barcodeInventory.php';
    $.ajax({
            method: "POST",
            url: script,
            data: {
                type: type,
                item: item,
                quantity: quantity,
                bid: bid,
                print: print ?  '1' : '0',
                toAuction: toAuction,
                bidder: bidder,
                mode: mode,
            },
            success: function(data, textStatus, jqXhr) {
                inProcess = false;
                inventoryButton.disabled = origInvButtonState;
                inventoryOverride.disabled = origOverrideButtonState;
                inventoryNoChange.disabled = origNoChangeButtonState;
                inventoryUpdate(data);                ;
            },
        error: function (jqXHR, textstatus, errorThrown) {
            inProcess = false;
            inventoryButton.disabled = origInvButtonState;
            inventoryOverride.disabled = origOverrideButtonState;
            inventoryNoChange.disabled = origNoChangeButtonState;
            showAjaxError(jqXHR, textstatus, errorThrown);
        },
    });
}

function inventoryUpdate(data) {
    if (data.error) {
        show_message(data.error, 'error');
        scanField.focus();
        return;
    }

    clearScreen();
    if (data.warn) {
        show_message(data.warn, 'warn');
    }

    if (data.message) {
        show_message(data.message, 'success');
    }
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
    print = false;
    scanField.focus();
    inProcess = false;
    printDiv.hidden = true;
    bidDiv.hidden = true;
    inventoryButton.disabled = true;
    inventoryOverride.disabled = false;
    inventoryNoChange.disabled = false;
    inventoryOverride.hidden = true;
    inventoryNoChange.hidden = true;
    return;
}
