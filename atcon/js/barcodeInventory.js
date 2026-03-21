
//buttons
var inventoryButton = null;
var scanField = null;
var inventoryTypeSelect = null;
var printDiv = null;
var inProcess = false;
var quantityField = 1;
var printmode = null;

window.onload = function init_page() {
    // input fields
    inventoryTypeSelect = document.getElementById("inventoryMode");
    scanfield = document.getElementById("barcode");
    inventoryButton = document.getElementById("inventoryButton");
    printDiv = document.getElementById("printDiv");
    quantityField = document.getElementById("quantity");
    printmode = document.getElementById("printmode");
    scanfield.addEventListener('keyup', (e)=> { if (e.code === 'Enter') inventory(0); });
    inventoryTypeSelect.focus();
    }

function inventoryModeChange() {
    inventoryButton.disabled = false;
    let mode = inventoryTypeSelect.value;
    inprocess = false;
    scanfield.focus();
    if (mode == 'checkin')
        printmode.innerHTML = 'Received Quantity: ';
    if (mode == 'checkout')
        printmode.innerHTML = 'Return Quantity: ';
}

function inventory(mode) {
    if (inProcess)
        return;

    clear_message();
    let scancode = scanfield.value;
    if (scancode == '') {
        show_message('Please scan a barcode', 'warn');
        return;
    }
    let scanned = scancode.split(',');
    let item = scanned[0].trim();
    let print = scanned.length > 1;
    printDiv.hidden = !print;
    let quantity = 1;
    let type = inventoryTypeSelect.value;

    if (print) {
        if (type == 'bid') {
            show_message("This is a print, you cannot record a bid on a print", 'error');
            return;
        }
        quantity = scanned[1].trim();
        quantityField.value = quantity;
        if (mode == 0)
            return;
    }
    inProcess = true;
    inventoryButton.disabled = true;

    let script = 'scripts/artinventory_barcodeInventory.php';
    $.ajax({
            method: "POST",
            url: script,
            data: { type: type, item: item, quantity: quantity, print: print, },
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
        scanfield.focus();
        return;
    }

    if (data.warn) {
        show_message(data.warn, 'warn');
    }

    if (data.message) {
        show_message(data.message, 'success');
    }

    scanfield.value = '';
    inProcess = false;
    inventoryButton.disabled = false;
    scanfield.focus();
    return;
}
