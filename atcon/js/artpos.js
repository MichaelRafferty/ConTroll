// cart object
var cart = null;

// tab fields
var find_tab = null;
var pay_tab = null;
var add_tab = null;
var release_tab = null;
var current_tab = null;

// find person fields
var id_div = null;
var badgeid_field = null;
var currentPerson = null;
var stats_div = null;
var showStats_div = null;
var statsTable = null;
var active_customers = null;
var awaiting_payment = null;
var awaiting_release = null;
var searchResultsModal = null;
var searchData = null;

// art items
var add_found_div = null;
var artFoundItems = null;
var itemCode_field = null;
var artistNumber_field = null;
var pieceNumber_field = null;
var unitNumber_field = null;
var artTable = null;
var currentArtist = null;
var itemDetailsDiv = null;

// pay items
var pay_div = null;
var pay_button_pay = null;
var pay_button_rcpt = null;
var pay_button_ercpt = null;
var pay_tid = null;
var pay_currentOrderId = null;
var pay_InitialCart = true;
var discount_mode = 'none';
var total_art_due = 0;
var total_tax_due = 0;
var total_amount_due = 0;
var tax_label = 'Sales Tax';
var orderMsg = '';
var payOverride = 0;
var payPoll = 0;

// release items
var releaseModal = null;
var releaseTitleDiv = null;
var releaseBodyDiv = null;
var releaseTable = null;

// Data Items
var unpaid_table = [];
var result_perinfo = [];
var cashChangeModal = null;

// inventory items
var inventoryModal = null;
var inventoryTitleDiv = null;
var inventoryBodyDiv = null;
var inventoryCurrentIndex = null;
var invChangeBtn = null;
var invNoChangeBtn = null;
var inventoryUpdates = null;

// global items
var conid = null;
var conlabel = null;
var user_id = 0;
var hasManager = false;
var receiptPrinterAvailable = false;

const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
const statusCodes = {
    'Entered': 'Ent',
    'Not In Show': 'NIS',
    'Checked In': 'In',
    'Removed from Show': 'Rem',
    'BID': 'Bid',
    'Quicksale/Sold': 'QS',
    'To Auction': 'Auc',
    'Sold Bid Sheet': 'SBS',
    'Sold at Auction': 'SAuc',
    'Checked Out': 'Out',
    'Purchased/Released': 'Pur'
};

// initialization
// lookup all DOM elements
// load mapping tables
window.onload = function initpage() {
    // set up the constants for objects on the screen

    find_tab = document.getElementById("find-tab");
    current_tab = find_tab;
    add_tab = document.getElementById("add-tab");
    pay_tab = document.getElementById("pay-tab");
    release_tab = document.getElementById("release-tab");

    // cart
    cart = new artpos_cart();

    // find people
    badgeid_field = document.getElementById("find_perid");
    badgeid_field.addEventListener('keyup', (e)=> { if (e.code === 'Enter') findPerson('search'); });
    badgeid_field.focus();
    id_div = document.getElementById("find_results");
    stats_div = document.getElementById("stats-div");
    showStats_div = document.getElementById("showStats-div");
    var id = document.getElementById("SearchResultsModal");
    if (id) {
        searchResultsModal = new bootstrap.Modal(id, { focus: true, backdrop: 'static' });
    }

    artistNumber_field = document.getElementById("artistNumber");
    itemCode_field = document.getElementById("itemCode");
    itemCode_field.addEventListener('keyup', (e)=> { if (e.code === 'Enter') findArt('code'); });
    pieceNumber_field = document.getElementById("pieceNumber");
    pieceNumber_field.addEventListener('keyup', (e)=> { if (e.code === 'Enter') findArt('piece'); });
    unitNumber_field = document.getElementById("unitNumber");
    unitNumber_field.addEventListener('keyup', (e)=> { if (e.code === 'Enter') findArt('unit'); });

    // art items
    add_found_div = document.getElementById('add-found-div');
    // pay items
    pay_div = document.getElementById('pay-div');

    // add events
    find_tab.addEventListener('shown.bs.tab', findShown)
    add_tab.addEventListener('shown.bs.tab', addShown)
    pay_tab.addEventListener('shown.bs.tab', payShown)
    release_tab.addEventListener('shown.bs.tab', releaseShown)

    // cash payment requires change
    cashChangeModal = new bootstrap.Modal(document.getElementById('CashChange'), { focus: true, backdrop: 'static' });

    // release works in a modal
    releaseModal = new bootstrap.Modal(document.getElementById('ReleaseArt'), { focus: true, backdrop: 'static' });
    releaseTitleDiv = document.getElementById("ReleaseArtTitle");
    releaseBodyDiv = document.getElementById("ReleaseArtBody");

    // inline inventory in a modal
    if (config.inlineInventory == 1) {
        inventoryModal = new bootstrap.Modal(document.getElementById('Inventory'), { focus: true, backdrop: 'static' });
        inventoryTitleDiv = document.getElementById("InventoryTitle");
        inventoryBodyDiv = document.getElementById("InventoryBody");
        invNoChangeBtn = document.getElementById("invNoChange_button");
        invChangeBtn = document.getElementById("invChange_button");
    }

    bootstrap.Tab.getOrCreateInstance(find_tab).show();

    // check of payPoll (terminal in use) before leave
    window.addEventListener('beforeunload', event => {
        onExit(event);
    })

    // load the initial data and the proceed to set up the rest of the system
    var postData = {
        ajax_request_action: 'loadInitialData',
    };
    $.ajax({
        method: "POST",
        url: "scripts/artpos_loadInitialData.php",
        data: postData,
        success: function (data, textstatus, jqxhr) {
            if (data.error !== undefined) {
                show_message(data.error, 'error');
                return;
            }
            if (data.message !== undefined) {
                show_message(data.message, 'success');
            }
            loadInitialData(data);
        },
        error: showAjaxError,
    });
}

// load mapping tables from database to javascript array
// also retrieve session data about printers
function loadInitialData(data) {
    // load database and instace items for startup
    conlabel =  data.label;
    conid = data.conid;
    user_id = data.user_id
    hasManager = data.hasManager;
    receiptPrinterAvailable = data.receiptPrinter === true;
    findShown();
}

// if no artSales or payments have been added to the database, this will reset for the next customer
function startOver(reset_all) {
    if (payPoll == 1) {
        if (!confirm("You are leaving without polling the terminal for payment completion.\n" +
            'Please use the "Payment Complete" button to check if the payment is complete,\n' +
            'or tthe "Cancel Payment" buttons to cancel the payment request and release the terminal.\n' +
            "Do you wish to leave anyway without releasing the terminal?")) {
            return;
        }

        // cancel terminal request
        var postData = {
            ajax_request_action: 'cancelPayRequest',
            requestId: payCurrentRequest,
            user_id: user_id,
        };
        clear_message();
        $.ajax({
            method: "POST",
            url: "scripts/artpos_cancelPayment.php",
            data: postData,
            success: function (data, textstatus, jqxhr) {
                if (typeof data == 'string') {
                    show_message(data, 'error');
                    return;
                }

                if (data.error !== undefined) {
                    show_message(data.error, 'error');
                    return;
                }

                if (data.status == 'error') {
                    show_message(data.data, 'error');
                    return;
                }

                if (data.warn !== undefined) {
                    show_message(data.warn, 'warn');
                    if (data.hasOwnProperty('paid') && data.paid == 1) {
                        // it paid while waiting for the poll, process the payment
                        payPoll = 1;
                        pay('');
                        payPoll = 0;
                    }
                    return;
                }

                if (data.message !== undefined) {
                    show_message(data.message, 'success');
                }

                startOver(reset_all);
            },
            error: function (jqXHR, textstatus, errorThrown) {
                document.getElementById('pollRow').hidden = false;
                showAjaxError(jqXHR, textstatus, errorThrown);
            },
        });
        payPoll = 0;
        return;
    }
    if (reset_all > 0)
        clear_message();

    if (baseManagerEnabled) {
        base_toggleManager();
    }

    hideStats();
    currentPerson = null;
    // empty cart
    cart.startOver();
    cart.hideRelease();
    // empty search strings and results
    badgeid_field.value = "";
    id_div.innerHTML = "";
    unpaid_table = null;
    add_found_div.innerHTML = '';

    // reset data to call up
    emailAddreesRecipients = [];
    last_email_row = '';

    // reset tabs to initial values
    find_tab.disabled = false;
    add_tab.disabled = true;
    pay_tab.disabled = true;
    release_tab.disabled = true;
    cart.hideNext();
    pay_button_pay = null;
    pay_button_rcpt = null;
    pay_button_ercpt = null;
    receeiptEmailAddresses_div = null;
    pay_tid = null;
    pay_currentOrderId = null;
    pay_InitialCart = true;

    // set tab to find-tab
    if (current_tab != find_tab) {
        bootstrap.Tab.getOrCreateInstance(find_tab).show();
    } else {
        findShown();
    }
    badgeid_field.focus();
}

// switch to the add tab
function gotoFind() {
    bootstrap.Tab.getOrCreateInstance(find_tab).show();
}

// switch to the add tab
function gotoAdd() {
    bootstrap.Tab.getOrCreateInstance(add_tab).show();
}

// switch to the pay tab
function gotoPay() {
    bootstrap.Tab.getOrCreateInstance(pay_tab).show();
}

// build the order
function buildOrder() {
    var postData = {
        ajax_request_action: 'buildOrder',
        cart_art: JSON.stringify(cart.getCartArt()),
        perid: currentPerson.id,
        pay_tid: pay_tid,
    };
    if (pay_currentOrderId) {
        postData.cancelOrder = pay_currentOrderId;
        pay_currentOrderId = null;
    }

    clear_message();
    $.ajax({
        method: "POST",
        url: "scripts/artpos_buildOrder.php",
        data: postData,
        success: function (data, textstatus, jqxhr) {
            var stop = true;
            if (typeof data == 'string') {
                show_message(data, 'error');
            } else if (data.error !== undefined) {
                show_message(data.error, 'error');
            } else if (data.message !== undefined) {
                show_message(data.message, 'success');
                stop = false;
            } else if (data.warn !== undefined) {
                show_message(data.warn, 'warn');
                stop = false;
            } else if (data.status == 'error') {
                show_message(data.data, 'error');
            } else
                stop = false;
            if (!stop)
                buildOrderSuccess(data);
        },
        error: function (jqXHR, textstatus, errorThrown) {
            showAjaxError(jqXHR, textstatus, errorThrown);
        },
    });
}

function buildOrderSuccess(data) {
    pay_currentOrderId = data.rtn.orderId;
    total_art_due = data.rtn.preTaxAmt;
    total_tax_due = data.rtn.taxAmt;
    total_amount_due = data.rtn.totalAmt;
    taxLabel = data.rtn.taxLabel;
    show_message("Credit Card Order #" + pay_currentOrderId + " created.<br/>" + orderMsg);
    payShown();
}

function gotoRelease() {
    if (current_tab != release_tab) {
        bootstrap.Tab.getOrCreateInstance(release_tab).show();
    } else {
        releaseShown();
    }
}

// draw_person: findPerson found someone.  Display their details
function draw_person() {
    var html = `
    <div class="container-fluid">
        <div class="row">
            <div class="col-sm-3">Person ID:</div>
            <div class="col-sm-9">` + currentPerson.id + `</div>
        </div>
        <div class="row">
            <div class="col-sm-3">` + 'Badge Name:' + `</div>
            <div class="col-sm-9">` + badge_name_default(currentPerson.badge_name, currentPerson.first_name, currentPerson.last_name) + `</div>
        </div>
        <div class="row">
            <div class="col-sm-3">Name:</div>
            <div class="col-sm-9">` +
            currentPerson.first_name + ' ' + currentPerson.middle_name + ' ' + currentPerson.last_name + `
            </div>
        </div>  
        <div class="row">
            <div class="col-sm-3">Address:</div>
            <div class="col-sm-9">` + currentPerson.address + `</div>
        </div>
`;
    if (currentPerson.address_2 != '') {
        html += `
    <div class="row">
        <div class="col-sm-3"></div>
        <div class="col-sm-9">` + currentPerson.addr_2 + `</div>
    </div>
`;
    }
    html += `
    <div class="row">
       <div class="col-sm-3"></div>
       <div class="col-sm-9">` + currentPerson.city + ', ' + currentPerson.state + ' ' + currentPerson.postal_code + `</div>
    </div>
`;
    if (currentPerson.country != '' && currentPerson.country != 'USA') {
        html += `
    <div class="row">
       <div class="col-sm-3"></div>
       <div class="col-sm-9">` + currentPerson.country + `</div>
    </div>
`;
    }
    html += `
    <div class="row">
       <div class="col-sm-3">Email Address:</div>
       <div class="col-sm-9">` + currentPerson.email_addr + `</div>
    </div>
    <div class="row">
       <div class="col-sm-3">Phone:</div>
       <div class="col-sm-9">` + currentPerson.phone + `</div>
    </div>
</div>
`;
    id_div.innerHTML = html;
}

// badge_name_default: build a default badge name if its empty
function badge_name_default(badge_name, first_name, last_name) {
    if (badge_name === undefined | badge_name === null || badge_name === '') {
        var default_name = (first_name + ' ' + last_name).trim();
        return '<i>' + default_name.replace(/ +/, ' ') + '</i>';
    }
    return badge_name;
}

// find the person by badge id, in prep for loading any art already won by bid
function findPerson(find_type) {
    id_div.innerHTML = "";
    searchResultsModal.hide();
    clear_message();
    cart.startOver();
    var name_search = badgeid_field.value.toLowerCase().trim();
    if ((name_search == null || name_search == '') && find_type == 'search') {
        show_message("No search criteria specified", "warn");
        return;
    }

    // search for matching names
    var postData = {
        ajax_request_action: 'findRecord',
        find_type: find_type,
        name_search: name_search,
    };
    $("button[name='find_btn']").attr("disabled", true);
    $.ajax({
        method: "POST",
        url: "scripts/artpos_findPerson.php",
        data: postData,
        success: function (data, textstatus, jqxhr) {
            if (data.error !== undefined) {
                show_message(data.error, 'error');
                $("button[name='find_btn']").attr("disabled", false);
                return;
            }
            foundPerson(data);
            $("button[name='find_btn']").attr("disabled", false);
        },
        error: function (jqXHR, textstatus, errorThrown) {
            $("button[name='find_btn']").attr("disabled", false);
            showAjaxError(jqXHR, textstatus, errorThrown);
        }
    });
}

// successful return from 2 AJAX calls - processes found records
// unpaid: one record: put it in the cart and go to pay screen
// normal:
//      single row: display record
function foundPerson(data) {
    if (data.num_rows == 1) { // one person found
        searchData = data;
        currentPerson = data.person;
        // draw the person in the modal
        draw_person();
        searchResultsModal.show();
        if (data.message !== undefined) {
            show_message(data.message, 'success', 'searchResultMessage');``
        }
        if (data.warn !== undefined) {
            show_message(data.warn, 'warn', 'searchResultMessage');
        }
    } else { // I'm not sure how we'd get here, we are searching by perid (badgeid)
        show_message(data.num_rows + " found.  Multiple people not yet supported.");
        return;
    }
}

// clear result and try again
function searchResultsClose() {
    id_div.innerHTML = "";
    searchResultsModal.hide();
    clear_message();
    searchData = null;
    badgeid_field.focus();
}
// select this person and actually start processing them
function startCheckout() {
        if (currentPerson == null || currentPerson.id == null) {
            show_message("No person selected", "warn");
            return;
        }

        id_div.innerHTML = "";
        searchResultsModal.hide();

        searchData.art.forEach((artItem) => {
            if (pay_tid == null) {
                pay_tid = artItem.transid;
            }
            cart.add(artItem);
        });
        if (searchData.payment) {
            searchData.payment.forEach((paymentItem) => {
                cart.addPmt(paymentItem);
            });
        }
        find_tab.disabled = true;
        add_tab.disabled = false;
        if (cart.getCartLength() > 0) {
            pay_tab.disabled = false;
            cart.showPay();
        }
        cart.drawCart();
        cart.showStartOver();
        if (searchData.release > 0 && cart.getCartLength() == 0) {
            release_tab.disabled = false;
            cart.showRelease();
            gotoRelease();
            searchData = null;
            return;
        }
        gotoAdd();
        searchData = null;
        return;
}

// findArt: find art matching the criteria with the right parameters
function findArt(findType) {
    var artistNumber = null;
    var pieceNumber = null;
    var unitNumber = null;
    var itemId = null;
    var itemCode = null;

    add_found_div.innerHTML = '';

    switch (findType) {
        case 'code':
            itemCode = itemCode_field.value;
            var fields = itemCode.split(',');
            itemId = fields[0];
            unitNumber = fields[1];
            itemCode_field.value = '';
            itemCode_field.focus();
            break;

        case 'unit':
            unitNumber = unitNumber_field.value;
        // fall into piece
        case 'piece':
            artistNumber = artistNumber_field.value;
            pieceNumber = pieceNumber_field.value;
            break;

        default:
            itemCode = itemCode_field.value;
            if (itemCode != null && itemCode != '') {
                var fields = itemCode.split(',');
                itemId = fields[0];
                unitNumber = fields[1];
            } else {
                itemCode = null;
            }
            artistNumber = artistNumber_field.value;
            if (artistNumber == '') {
                artistNumber = null;
            }
            pieceNumber = pieceNumber_field.value;
            if (pieceNumber == '') {
                pieceNumber = null;
            }
    }

    var postData = {
        artistNumber: artistNumber,
        pieceNumber: pieceNumber,
        unitNumber: unitNumber,
        itemId: itemId,
        findType: findType,
        region: config.region,
    };

    $("button[name='findArtBtn']").attr("disabled", true);
    $.ajax({
        method: "POST",
        url: "scripts/artpos_getArt.php",
        data: postData,
        success: function (data, textstatus, jqxhr) {
            if (data.error !== undefined) {
                show_message(data.error, 'error');
                $("button[name='findArtBtn']").attr("disabled", false);
                return;
            }
            if (data.message !== undefined) {
                show_message(data.message, 'success');
            }
            if (data.warn !== undefined) {
                show_message(data.warn, 'warn');
            }
            currentArtist = artistNumber;
            foundArt(data);
            $("button[name='findArtBtn']").attr("disabled", false);
        },
        error: function (jqXHR, textstatus, errorThrown) {
            $("button[name='findArtBtn']").attr("disabled", false);
            showAjaxError(jqXHR, textstatus, errorThrown);
        }
    });
}

// Common routine to draw the item record details for both found art and inventory updates.
function drawItemDetails(item, full = false) {
    var html = '';
    var valid = true;
    var btn_color = 'btn-primary';
    var priceType = '';
    var priceField = '';

    var cols = full ? '2' : '4';

    html  = '<div class="row mt-4 mb-1"><div class="col-sm-11 ms-3 bg-primary text-white">Item Details</div></div>';
    html += '<div class="row m-0 p-0"><div class="col-sm-' + cols + '">Artist Number:</div><div class="col-sm-auto">' + item.exhibitorNumber + '</div></div>';
    html += '<div class="row m-0 p-0"><div class="col-sm-' + cols + '">Artist Item #:</div><div class="col-sm-auto">' + item.item_key + '</div></div>';
    html += '<div class="row m-0 p-0"><div class="col-sm-' + cols + '">Type:</div><div class="col-sm-auto">' + item.type + '</div></div>';
    html += '<div class="row m-0 p-0"><div class="col-sm-' + cols + '">Status:</div><div class="col-sm-auto">' + item.status + '</div></div>';
    html += '<div class="row m-0 p-0"><div class="col-sm-' + cols + '">Artist Name:</div><div class="col-sm-7">' + item.exhibitorName + '</div></div>';
    html += '<div class="row m-0 p-0"><div class="col-sm-' + cols + '">Title:</div><div class="col-sm-7">' + item.title + '</div></div>';
    html += '<div class="row m-0 p-0"><div class="col-sm-' + cols + '">Material:</div><div class="col-sm-7">' + item.material + '</div></div>';
    if (item.bidder != null && item.bidder != '' && item.bidder != currentPerson.id) {
        btn_color = 'btn-warning';
        if (config.inlineInventory != 1)
            valid = false;
        if (item.status != 'BID' && item.status != 'To Auction')
            html += '<div class="row m-0 p-0"><div class="col-sm-' + cols + ' bg-warning">Already Sold:</div>' +
                '<div class="col-sm-7 bg-warning">Item has already been sold to someone else.</div></div>';
        else {
            html += '<div class="row m-0 p-0"><div class="col-sm-' + cols + ' bg-warning">Bidder Mismatch:</div>' +
                '<div class="col-sm-7 bg-warning">Someone else is the high bidder.</div></div>';
            priceType = 'Current Bid';
        }
    }

    if (item.type == 'print') {
        html += '<div class="row m-0 p-0"><div class="col-sm-' + cols + '">Sale Price:</div><div class="col-sm-auto">$' + Number(item.sale_price).toFixed(2) + '</div></div>';

        if (item.quantity <= 0) {
            html += '<div class="row m-0 p-0"><div class="col-sm-' + cols + ' bg-warning">Quantity:</div><div class="col-sm-7 bg-warning">System shows all of this item is already sold, remaining quantity is 0.</div></div>';
            btn_color = 'btn-warning';
            if (config.inlineInventory != 1)
                valid = false;
        }
    }

    if (valid) {
        switch (item.type) {
            case 'art':
                if (full) {
                    html += '<div class="row m-0 p-0"><div class="col-sm-' + cols + '">Minimum Bid:</div>' +
                        '<div class="col-sm-7">' + Number(item.min_price).toFixed(2) + '</div></div>';
                    if (item.bidder == null || item.bidder == '') {
                        html += '<div class="row m-0 p-0"><div class="col-sm-' + cols + '">Quick Sale Price:</div>' +
                            '<div class="col-sm-7">' + Number(item.sale_price).toFixed(2) + '</div></div>';
                    }
                    if (item.final_price != null && item.final_price > 0)
                        html += '<div class="row m-0 p-0"><div class="col-sm-' + cols + '">Final Price:</div>' +
                            '<div class="col-sm-7">' + Number(item.final_price).toFixed(2) + '</div></div>';
                    if (item.bidder != null && item.bidder > 0)
                        html += '<div class="row m-0 p-0"><div class="col-sm-' + cols + '">Bidder:</div>' +
                            '<div class="col-sm-7">' + item.bidder + '</div></div>';
                }
                if (item.sale_price == 0 || Number(item.sale_price) < Number(item.min_price)) {
                    html += '<div class="row m-0 p-0"><div class="col-sm-' + cols + ' bg-danger text-white">Quick Sale:</div>' +
                        '<div class="col-sm-7 bg-danger text-white">Item is not available for quick sale.</div></div>';
                    valid = false;
                    break;
                }
                if (item.status.toLowerCase() == 'checked in') {
                    priceType = 'Quick Sale Price:';
                    priceField = 'sale_price';
                } else {
                    if (priceType == '')
                        priceType = 'Final Price:';
                    priceField = 'final_price';
                }
                break;

            case 'nfs':
                html += '<div class="row m-0 p-0"><div class="col-sm-' + cols + ' bg-danger text-white">Not For Sale:</div>' +
                    '<div class="col-sm-7 bg-danger text-white">You cannot buy a Not For Sale item.</div></div>';
                valid = false;
                break;
            case 'print':
                if (full) {
                    html += '<div class="row m-0 p-0"><div class="col-sm-' + cols + '">Original Quantity:</div>' +
                        '<div class="col-sm-7">' + item.original_qty + '</div></div>';
                }
                html += '<div class="row m-0 p-0"><div class="col-sm-' + cols + '">Remaining Quantity:</div>' +
                    '<div class="col-sm-7">' + item.quantity + '</div></div>';
                priceType = 'Sale Price:'
                priceField = 'sale_price';
                break;
        }
    }

    if (valid) {
        htmlLine = '';
        switch (item.status.toLowerCase()) {
            case 'entered':
            case 'not in show':
                if (config.inlineInventory == 1)
                    btn_color = 'btn-warning';
                else
                    valid = 'false';
                break;

            case 'checked in':
                // currently nothing special for checked in items, this will be for sale at priceType via priceField
                break;

            case 'bid':
                if (btn_color != 'btn-warning' && config.inlineInventory != 1) {
                    htmlLine = '<div class="row m-0 p-0"><div class="col-sm-' + cols + '">Final Price:</div><div class="col-sm-7">' +
                        '<input type=number inputmode="numeric" class="no-spinners" id="art-final-price" name="art-final-price" ' +
                            'style="width: 9em;" value="' + item.final_price + '"/></div></div>';
                }
                break;

            case 'nfs':
                valid = false;
                html += '<div class="row m-0 p-0"><div class="col-sm-' + cols + ' bg-danger text-white">Not For Sale:</div><div class="col-sm-7 bg-danger text-white">You cannot buy a Not For Sale item.</div></div>';
                break;

            case 'removed from show':
                html += '<div class="row m-0 p-0"><div class="col-sm-' + cols + ' bg-danger text-white">Removed:</div><div class="col-sm-7 bg-danger text-white">System shows item has been removed from the show.</div></div>';
                btn_color = 'btn-warning';
                break;

            case 'to auction':
                html += '<div class="row m-0 p-0"><div class="col-sm-' + cols + ' bg-warning">Auction:</div><div class="col-sm-7 bg-warning">System shows item has been sent to the voice auction. Sell anyway?</div></div>';
                btn_color = 'btn-warning';
                break;

            case 'checked out':
                html += '<div class="row m-0 p-0"><div class="col-sm-' + cols + ' bg-warning">Checked Out:</div><div class="col-sm-7 bg-warning">System shows item has been returned to the artist. Sell anyway?</div></div>';
                btn_color = 'btn-warning';
                break;

            case 'quicksale/sold':
                html += '<div class="row m-0 p-0"><div class="col-sm-' + cols + ' bg-danger text-white">Released:</div><div class="col-sm-7 bg-danger text-white">System shows item already been sold via quicksale.</div></div>';
                valid = false;
                break;

            case 'purchased/released':
                html += '<div class="row m-0 p-0"><div class="col-sm-' + cols + ' bg-danger text-white">Released:</div><div class="col-sm-7 bg-danger text-white">System shows item already been released to a purchaser.</div></div>';
                valid = false;
                break;

            case 'sold bid sheet':
            case 'sold at auction':
                if (item.final_price == item.paid) {
                    html += '<div class="row"><div class="col-sm-4 bg-danger text-white">Sold:</div><div class="col-sm-7 bg-danger text-white">System shows item already been sold and paid for.</div></div>';
                    valid = false;
                }
                break;
        }
    }

    step = {};
    step.html = html;
    step.valid = valid;
    step.color = btn_color;
    step.priceType = priceType;
    step.priceField = priceField;
    return step;
}

// foundArt - process the returned array of art items to select from
function foundArt(data) {
    artFoundItems = data.items;
    if (data.items.length == 1) {
        var item = data.items[0];
        var details = drawItemDetails(item, false);
        html = '<div id="itemDetailsDiv" class="container-fluid">' + details.html + '</div><div class="container-fluid">';
        valid = details.valid;
        btn_color = details.color;
        priceField = details.priceField;
        priceType = details.priceType;

        if (valid) {
            if (cart.notinCart(item.id)) {
                if (htmlLine != '') {
                    html += htmlLine;
                } else {
                    if (item.type != 'print' && Number(item[priceField]) > 0 ) {
                        html += '<div class="row"><div class="col-sm-4">' + priceType + '</div><div class="col-sm-8">$' +
                            Number(item[priceField]).toFixed(2) + '</div></div>';
                    }
                    if ((config.inlineInventory == 1 && item.type == 'art') || btn_color == 'btn-warning')
                        html += '<div class="row mt-2"><div class="col-sm-4"></div><div class="col-sm-8"><button class="btn btn-sm ' + btn_color +
                            '" type="button" onclick="updateInventory(-1);">Update Art Item Inventory</button></div></div>';
                    else
                        html += '<div class="row mt-2"><div class="col-sm-4"></div><div class="col-sm-8"><button class="btn btn-sm ' + btn_color +
                            '" type="button" onclick="addToCart(-1);">Add Art Item to Cart</button></div></div>';
                }
            } else {
                html += '<div class="row mt-2"><div class="col-sm-4"></div><div class="col-sm-auto bg-warning">Already in Cart</div></div>';
            }
        }
    } else {
        // multiple rows returned - do we want to show them and let them select which ones to add to cart
        if (artTable != null){
            artTable.destroy();
            artTable = null;
        }
        html = '<div class="row"><div class="col-sm-12" id="artTable"></div></div>';
    }
    html += '</div>';
    add_found_div.innerHTML = html;
    if (data.items.length == 1) {
        itemDetailsDiv = document.getElementById("itemDetailsDiv");
        return; // one item we are done
    }

    // multiple rows fill the table
    artTable = new Tabulator('#artTable', {
        data: data.items,
        index: 'item_key',
        layout: "fitColumns",
        pagination: data.items.length > 10,
        paginationSize: 10,
        paginationSizeSelector: [10, 25, 50, true], //enable page size select element with these options

        columns: [
            {title: "Artist " + currentArtist, headerWordWrap: true, formatter: itemAction, width: 70, headerSort: false, },
            {field: "id", visible: false },
            {title: "Piece #", field: "item_key", headerWordWrap: true,  headerSort: true, headerFilter: true, maxWidth: 70, width: 70, hozAlign: 'right', headerHozAlign: 'right' },
            {title: "Title", field: "title", headerFilter: true, maxWidth: 300, minWidth: 100, },
            {title: "Type", field: "type", maxWidth: 100, width: 100, headerSort: true, headerFilter: true, },
            {title: "Status", field: "status", minWidth: 100,  },
            {title: "Bidder", field: "bidder", minWidth: 70, hozAlign: 'right', headerHozAlign: 'right' },
            {title: "Current Price", field: "final_price", headerWordWrap: true, hozAlign: 'right', headerHozAlign: 'right', headerSort: false, },
            {field: "minPrice", visible: false, },
            {field: "sale_price", visible: false, },
        ],
    });

    return;
}

// itemAction - what to do with this row
function itemAction(cell, formatterParams, onRendered) {
    var row = cell.getData();
    if (!cart.notinCart(row.id))
        return 'Cart';

    var color ='primary';
    if (row.type == 'nfs') // not for sale = not for sale, require admin to change the status in the back end art inventory
        return '';

    // if its already sold or returned to the artist, require admin to change the status in the back end art inventory
    if (row.status == 'Checked Out' || row.status == 'Purchased/Released' || row.status == 'Quicksale/Sold')
        return '';

    // if it's not checked in, or checked in as 'missing' (not in show), allow inventory overide if inline, else nothing
    if (row.status == 'Entered' || row.status == 'Not In Show') {
        if (config.inlineInventory == 1)
            return '<button class="btn btn-sm btn-warning" style= "--bs-btn-padding-y: .0rem; --bs-btn-padding-x: .3rem; --bs-btn-font-size: .75rem;",' +
                ' onclick="updateInventory(' + row.item_key + ');">Inv</button>';

        return '';
    }

    if (row.status == 'Removed from Show') // warn anything in removed from show status, be it add or inventory
        color = 'warning';

    if (row.type == 'print') {
        if (row.quantity <= 0) {
            // only allow inLineInventory to fix remaining quantity for a print
            if (config.inlineInventory != 1)
                return '';

            return '<button class="btn btn-sm btn-warning" style= "--bs-btn-padding-y: .0rem; --bs-btn-padding-x: .3rem; --bs-btn-font-size: .75rem;",' +
                ' onclick="updateInventory(' + row.item_key + ');">Inv</button>';
        }

        return '<button class="btn btn-sm btn-' + color + '" style= "--bs-btn-padding-y: .0rem; --bs-btn-padding-x: .3rem; --bs-btn-font-size: .75rem;",' +
            ' onclick="addToCart(' + row.item_key + ');">Add</button>';
    }

    if (config.inlineInventory == 1) {
        // auto warn anything that is not marked for this person
        if (row.bidder != null && row.bidder != currentPerson.id)
            color = 'warning'

        return '<button class="btn btn-sm btn-' + color + '" style= "--bs-btn-padding-y: .0rem; --bs-btn-padding-x: .3rem; --bs-btn-font-size: .75rem;",' +
            ' onclick="updateInventory(' + row.item_key + ');">Inv</button>';
    }

    // if not us, and bid, to auction, sold bid sheet, sold at auction, deny it
    if (row.bidder != null && row.bidder != currentPerson.id)
        return '';

    return '<button class="btn btn-sm btn-' + color + '" style= "--bs-btn-padding-y: .0rem; --bs-btn-padding-x: .3rem; --bs-btn-font-size: .75rem;",' +
        ' onclick="addToCart(' + row.item_key + ');">Add</button>';
}

// updateInventory - open modal and allow updating the inventory for this art item
function updateInventory(itemKey) {
    var item = null;

    if (artFoundItems.length == 0)
        return;

    if (itemKey < 0) {
        item = artFoundItems[0];
    } else {
        if (artTable == null)
            return;

        item = artTable.getRow(itemKey).getData();
        if (item == null)
            return;
    }

    inventoryCurrentIndex = item.item_key;
    updateInventoryStep(item, false);
    inventoryModal.show();
}

// update art inventory step - decide if anything needs changing in the inventory
function updateInventoryStep(item, repeatPass) {
    // build the contents for the modal, first the item data as it presently exists:
    var details = drawItemDetails(item, true);
    inventoryUpdates = [];
    html = '<h1 class="h4">Update Inventory Record:</h1>' + details.html;

    // general status issues first
    if (item.status == 'Entered' || item.status == 'Not In Show') {
        html += '<div class="row mt-4"><div class="col-sm-12">The item is not available for sale because of it\'s status.' +
            ' If you are sure the item is checked in, click "Update Inventory Record" to set it to "Checked In"</div></div>';
        inventoryUpdates.push({field: 'status', value: 'Checked In'});

        if (item.type == 'print') {
            html += '<div class="row"><div class="col-sm-12">The item is of type print, please verify it\'s available quantity before updating the record: ' +
                '<input type="number" class="no-spinners" inputmode="numeric" id="availQty" name="availQty" size="20" placeholder="Avail Qty" ' +
                    ' min=0 max=9999 value="' + item.quantity + '"></div></div>';
            inventoryUpdates.push({field: 'quantity', id: 'availQty', type: 'i'});
        }
    }

    // print is covered in general status.
    // NFS is only allowed to be checked in, if it even gets this far, (as a safety valve)
    if (item.type == 'art' && config.roomStatus != 'precon' && config.roomStatus != 'closed' && repeatPass == false) {
        // that leaves art.
        // based on the room status: No sales if 'precon', no sales if 'closed', that leaves bids and checkout.
        //      'bids' will allow quicksale and updating the bidder, but only adding it to the cart if quicksale.
        //      'checkout' will allow quicksale and updating the bidder and highest bid and adds everything to the cart
        //
        // based on item status:
        //   if the status is 'Checked out', 'Purchased/Release', 'Quicksale Sold' we should not allow anything else to be done as those are final statues
        //      (except for releasing Quicksale Sold which is not an inventory item.)
        //   For the status: 'Checked In', Removed from Show: allow setting for quicksale and allow going to cart as quicksale
        //   For the status: 'Checked In', Removed from Show, BID: update bidder, and allow going to the cart as checkout for this person

        // quicksale Y/N (note entered will become checked in)
        if ((item.status == 'Entered' || item.status == 'Checked In' || item.status == 'Removed from Show') && item.bidder == null) {
            html += '<div class="row mt-2"><div class="col-sm-12">Is this a quick sale? ' +
                '<select id="quickSaleYN" name="quickSaleYN"><option value="N">No</option><option value="Y">Yes</option></select>' +
                '</div></div>';
            inventoryUpdates.push({field: '', id: 'quickSaleYN', type: 'p'});
            valid = false;
        }

        // bid item
        if (item.status == 'Entered' || item.status == 'Checked In' || item.status == 'Removed from Show' || item.status == 'BID') {
            // update bidder if bid and not use
            if (item.bidder != null && item.bidder != currentPerson.id) {
                html += '<div class="row mt-2"><div class="col-sm-12">This item is current bid on by ' + item.bidder + ', change it to this person? ' +
                    '<select id="updateBidderYN" name="updateBidderYN"><option value="N">No</option><option value="Y">Yes</option></select>' +
                    '</div></div>';
                inventoryUpdates.push({field: '', id: 'updateBidderYN', type: 'p'});
                inventoryUpdates.push({field: 'bidder', value: currentPerson.id, type: 'i'});
                valid = false;
            }

            html += '<div class="row mt-2"><div class="col-sm-12">Current High bid? ' +
                '<input type="number" class="no-spinners" inputmode="numeric" id="finalPrice" name="finalPrice" size="20" placeholder="High Bid" ' +
                ' min=1 max=9999999 value="' + (item.final_price > item.min_price ? item.final_price : item.min_price) + '"></div></div>';
            inventoryUpdates.push({field: 'final_price', id: 'finalPrice', type: 'd',
                prior: item.final_price > item.min_price ? item.final_price : item.min_price });
        }

        // to Auction Item:
        if (item.status == 'To Auction') {
            // update bidder if not us
            // update final price
            if (item.bidder != null && item.bidder != currentPerson.id) {
                html += '<div class="row mt-2"><div class="col-sm-12">This item was last bid on by ' + item.bidder + ', change it to this person? ' +
                    '<select id="updateBidderYN" name="updateBidderYN"><option value="N">No</option><option value="Y">Yes</option></select>' +
                    '</div></div>';
                inventoryUpdates.push({field: '', id: 'updateBidderYN', type: 'p'});
                inventoryUpdates.push({field: 'bidder', value: currentPerson.id, type: 'i'});
                valid = false;
            }

            html += '<div class="row mt-2"><div class="col-sm-12">Final Bid Price? ' +
                '<input type="number" class="no-spinners" inputmode="numeric" id="finalPrice" name="finalPrice" size="20" placeholder="High Bid" ' +
                ' min=1 max=9999999 value="' + (item.final_price > item.min_price ? item.final_price : item.min_price) + '"></div></div>';
            inventoryUpdates.push({field: 'final_price', id: 'finalPrice', type: 'd',
                prior: (item.final_price > item.min_price ? item.final_price : item.min_price) - 0.01 });
            inventoryUpdates.push({field: 'status',  value: 'Sold at Auction'});
        }

        // Checked Out Item
        if (item.status == 'Checked Out') {
            // update bidder, as there should be none
            // update final price
            inventoryUpdates.push({field: 'bidder', value: currentPerson.id, type: 'i'});
            valid = false;
            html += '<div class="row mt-2"><div class="col-sm-12">Final Bid Price? ' +
                '<input type="number" class="no-spinners" inputmode="numeric" id="finalPrice" name="finalPrice" size="20" placeholder="High Bid" ' +
                ' min=1 max=9999999 value="' + (item.final_price > item.min_price ? item.final_price : item.min_price) + '"></div></div>';
            inventoryUpdates.push({field: 'final_price', id: 'finalPrice', type: 'd',
                prior: item.min_price - 0.01 });
            inventoryUpdates.push({field: 'status',  value: 'Bid'});
        }
    }


    invNoChangeBtn.disabled = (!details.valid) || (details.color == 'btn-warning');
    invChange_button.disabled = inventoryUpdates.length == 0;
    btn_color = details.color;
    inventoryBodyDiv.innerHTML = html +
        '<div class="row mt-2"><div class="col-sm-12" id="inv_result_msg"></div></div>';

    if (!invNoChangeBtn.disabled && invChange_button.disabled) {
        if (config.roomStatus != 'precon' && config.roomStatus != 'closed') {
            addItemToCart(item);
            add_found_div.innerHTML = "";
        } else {
            show_message("Room Status is " + config.roomStatus + ", cannot add items to the cart.", 'warn');
        }
        inventoryModal.hide();
    }
}

// actually update the inventory record
function invUpdate(doUpdate) {
    var item = null;

    if (artFoundItems.length == 1)
        item = artFoundItems[0];
    else
        item = artTable.getRow(inventoryCurrentIndex).getData();

    if (!doUpdate) {
        addItemToCart(item);
        add_found_div.innerHTML = '';
        inventoryModal.hide();
        return;
    }

    if (inventoryUpdates == null || inventoryUpdates.length == 0) {
        show_message('Nothing to update', 'warn');
        inventoryModal.hide();
        return;
    }

    for (var index = 0; index < inventoryUpdates.length; index++) {
        if (inventoryUpdates[index].hasOwnProperty('id')) {
            inventoryUpdates[index].value = document.getElementById(inventoryUpdates[index].id).value;
        }
    }


    var postData = {
        ajax_request_action: 'inlineUpdate',
        item: JSON.stringify(item),
        perid: currentPerson.id,
        user_id: user_id,
        updates: JSON.stringify(inventoryUpdates),
    };
    $.ajax({
        method: "POST",
        url: "scripts/artpos_updateInventory.php",
        data: postData,
        success: function (data, textstatus, jqxhr) {
            if (data.error !== undefined) {
                show_message(data.error, 'error', 'inv_result_msg');
                return;
            }
            if (data.message !== undefined) {
                show_message(data.message, 'success', 'inv_result_msg');
            }
            if (data.warn !== undefined) {
                show_message(data.warn, 'warn', 'inv_result_msg');
            }
            if (data.hasOwnProperty('item')) { // successful update
                if (artFoundItems.length > 1) {
                    var row = artTable.getRow(inventoryCurrentIndex);
                    row.update(data.item).then(row.reformat());
                } else {
                    artFoundItems = [data.item];
                    // redraw the item details block
                    step = drawItemDetails(data.item);
                    itemDetailsDiv.innerHTML = step.html;
                }
                updateInventoryStep(data.item, true);
            }
        },
        error: showAjaxError,
    });
}

// addToCart - add this row index to the cart
function addToCart(itemKey) {
    var item = null;

    if (artFoundItems.length == 0)
        return;

    if (itemKey < 0) {
        item = artFoundItems[0];
    } else {
        if (artTable == null)
            return;

        item = artTable.getRow(itemKey).getData();
        if (item == null)
            return;
    }
    if (config.inlineInventory == 0 || (config.roomStatus != 'precon' && config.roomStatus != 'closed')) {
        addItemToCart(item);
        if (artFoundItems.length > 1) {
            var row = artTable.getRow(inventoryCurrentIndex);
            row.reformat();
        } else {
            add_found_div.innerHTML = "";
        }
    } else {
        show_message("Room Status is " + config.roomStatus + ", cannot add items to the cart.", 'warn');
    }
}

// addItemToCart - knowing the item, add it to the cart
function addItemToCart(item) {
    var finalPriceField = document.getElementById('art-final-price');
    if (finalPriceField) {
        var enteredPrice = Number(finalPriceField.value);
        if (enteredPrice == null)
            enteredPrice = 0;
        var finalPrice = Number(item.final_price);
        if (finalPrice == null || finalPrice < 0) {
            if (item.sale_price == null || item.sale_price == 0)
                finalPrice = item.min_price;
            else
                finalPrice = item.sale_price;
        }
        if (enteredPrice < finalPrice) {
            if (confirm("Entered final price is less than system's sell price of " + finalPrice + ", sell at this price anyway?"))
                item.final_price = Number(finalPrice).toFixed(2);
            else
                return;
        } else {
            item.final_price = enteredPrice;
        }
    }

    cart.add(item);
}

// initArtSales - create/update artSales records for this cart to prepare for payment, create master transaction if none exists
function initArtSales() {
    // submit the current card data to update the database, retrieve updated cart
    var postData = {
        ajax_request_action: 'initArtSales',
        cart_art: JSON.stringify(cart.getCartArt()),
        cart_art_map: JSON.stringify(cart.getCartMap()),
        pay_tid: pay_tid,
        perid: currentPerson.id,
        user_id: user_id,
    };
    $.ajax({
        method: "POST",
        url: "scripts/artpos_initArtSales.php",
        data: postData,
        success: function (data, textstatus, jqxhr) {
            if (data.error !== undefined) {
                show_message(data.error, 'error');
                return;
            }
            if (data.message !== undefined) {
                show_message(data.message, 'success');
            }
            if (data.warn !== undefined) {
                show_message(data.warn, 'success');
            }
            initArtSalesComplete(data);
        },
        error: showAjaxError,
    });
}

// initArtSalesComplete - now update the cart with the new data and call payShown again to draw it.
//  all the data from the cart has been updated in the database, now apply the id's and proceed to the next step
function initArtSalesComplete(data) {
    pay_tid = data.pay_tid;
    if (data.message !== undefined) {
        orderMsg = data.message;
    }
    if (data.warn !== undefined) {
        orderMsg = data.warn;
    }

    // update cart elements
    var unpaid_rows = cart.updateFromDB(data);
    payShown();
}

// setPayType: shows/hides the appropriate fields for that payment type
function setPayType(ptype) {
    var elcheckno = document.getElementById('pay-check-div');
    var elccauth = document.getElementById('pay-ccauth-div');
    var elcashtendered = document.getElementById('pay-cash-div');

    elcheckno.hidden = ptype != 'check';
    elccauth.hidden = ptype != 'credit';
    elcashtendered.hidden = ptype != 'cash';

    if (ptype != 'check') {
        document.getElementById('pay-checkno').value = null;
    }
    if (ptype != 'credit') {
        document.getElementById('pay-ccauth').value = null;
    }
    if (ptype != 'cash') {
        document.getElementById('pay-tendered').value = null;
    }
}

// overridePay - pay returned the terminal was unavailable, operator said to override it
function overridePay(){
    payOverride = 1;
    pay('');
}

// payPoll - poll to see if the payment is complete
function payPollfcn(action) {
    document.getElementById('pollRow').hidden = true;
    if (action == 1) { // asked to poll for is it complete
        payPoll = 1;
        pay('');
        return;
    }
    // cancel terminal request
    var postData = {
        ajax_request_action: 'cancelPayRequest',
        requestId: payCurrentRequest,
        user_id: user_id,
    };
    clear_message();
    $.ajax({
        method: "POST",
        url: "scripts/artpos_cancelPayment.php",
        data: postData,
        success: function (data, textstatus, jqxhr) {
            cancelSuccess(data);
        },
        error: function (jqXHR, textstatus, errorThrown) {
            document.getElementById('pollRow').hidden = false;
            pay_button_pay.disabled = true;
            showAjaxError(jqXHR, textstatus, errorThrown);
        },
    });
}

function cancelSuccess(data) {
    pay_button_pay.disabled = false;

    // things that stop us cold....
    if (typeof data == 'string') {
        show_message(data, 'error');
        document.getElementById('pollRow').hidden = false;
        return;
    }

    if (data.error !== undefined) {
        show_message(data.error, 'error');
        document.getElementById('pollRow').hidden = false;
        return;
    }

    if (data.status == 'error') {
        show_message(data.data, 'error');
        document.getElementById('pollRow').hidden = false;
        return;
    }

    if (data.warn !== undefined) {
        show_message(data.warn, 'warn');
        // warn means we could not get the terminal, ask if we want to override it
        if (data.status != 'OFFLINE') {
            document.getElementById('overrideRow').hidden = false;
            return;
        }
    }

    payPoll = 0;
    payCurrentRequest = null;
    // and things that continue
    if (data.message !== undefined) {
        show_message(data.message, 'success');
    }

    document.getElementById('pollRow').hidden = true;
    pay_button_pay.disabled = false;
    payShown();
}

// Process a payment against the transaction
function pay(nomodal, prow = null) {
    var checked = false;
    var ccauth = null;
    var checkno = null;
    var desc = null;
    var ptype = null;

    if (nomodal != '') {
        cashChangeModal.hide();
    }

    if (pay_currentOrderId == null) {
        show_message("No order in progress, you have reached an error condition, start over or seek assistance", "error");
        return;
    }

    if (prow == null) {
        // validate the payment entry: It must be >0 and <= amount due
        //      a payment type must be specified
        //      for check: the check number is required
        //      for credit card: the auth code is required
        //      for discount: description is required, it's optional otherwise

       if (document.getElementById('pt-cash').checked) {
           amtTendered = Number(document.getElementById('pay-tendered').value)
            if (nomodal == '' && amtTendered > total_amount_due) {
                cashChangeModal.show();
                var tendered = Number(document.getElementById('pay-tendered').value);
                document.getElementById("CashChangeBody").innerHTML = "Customer owes $" + total_amount_due.toFixed(2) + ", and tendered $" + amtTendered.toFixed(2) +
                    "<br/>Confirm change given to customer of $" + (amtTendered - total_amount_due).toFixed(2);
                return;
            }

            if (amtTendered < total_amount_due) {
                show_message("Cannot pay less than total amount due of " + total_amount_due.toFixed(2), "error");
                return;
            }
        }

        var elptdiv = document.getElementById('pt-div');
        var elterminal = document.getElementById('pt-terminal');
        elptdiv.style.backgroundColor = '';

        var eldesc = document.getElementById('pay-desc');
        var elptdisc = document.getElementById('pt-discount');
        if (elptdisc != null) {
            if (document.getElementById('pt-discount').checked) {
                ptype = 'discount';
                desc = eldesc.value;
                if (desc == null || desc == '') {
                    eldesc.style.backgroundColor = 'var(--bs-warning)';
                    return;
                } else {
                    eldesc.style.backgroundColor = '';
                }
                checked = true;
            } else {
                eldesc.style.backgroundColor = '';
            }
        }

        if (document.getElementById('pt-check').checked) {
            ptype = 'check';
            var elcheckno = document.getElementById('pay-checkno');
            checkno = elcheckno.value;
            if (checkno == null || checkno == '') {
                elcheckno.style.backgroundColor = 'var(--bs-warning)';
                return;
            } else {
                elcheckno.style.backgroundColor = '';
            }
            checked = true;
        }
        var creditRadio = document.getElementById('pt-credit');
        if (creditRadio != null && creditRadio.checked) {
            ptype = 'credit';
            var elccauth = document.getElementById('pay-ccauth');
            ccauth = elccauth.value;
            if (ccauth == null || ccauth == '') {
                elccauth.style.backgroundColor = 'var(--bs-warning)';
                return;
            } else {
                elccauth.style.backgroundColor = '';
            }
            checked = true;
        }

        if (document.getElementById('pt-cash').checked) {
            ptype = 'cash';
            checked = true;
        }

        if (elterminal && elterminal.checked) {
            ptype = 'terminal';
            checked = true;
        }

        if (!checked) {
            elptdiv.style.backgroundColor = 'var(--bs-warning)';
            return;
        }

        if (total_amount_due > 0) {
            var crow = null;
            var change = 0;
            if (ptype == 'cash') {
                amtTendered = Number(document.getElementById('pay-tendered').value) > total_amount_due;
                if (amtTendered > total_amount_due) {
                    change = -thisPay_total;
                    crow = {
                        index: cart.getPmtLength() + 1, amt: change, ccauth: ccauth, checkno: checkno, desc: eldesc.value, type: 'change',
                    }
                }
            }
            prow = {
                index: cart.getPmtLength(), amt: total_amount_due, tax: total_tax_due, pretax: total_art_due, ccauth: ccauth, checkno: checkno, desc: eldesc.value, type: ptype,
            };
        }
    }
    // process payment
    var art = cart.getCartArt();
    var artJSON = JSON.stringify(art);
    var postData = {
        ajax_request_action: 'processPayment',
        cart_art: artJSON,
        new_payment: prow,
        change: crow,
        user_id: user_id,
        payor: currentPerson,
        pay_tid: pay_tid,
        order_id: pay_currentOrderId,
        override: payOverride,
        poll: payPoll,
        preTaxAmt: total_art_due,
        taxAmt: total_tax_due,
        totalAmtDue: total_amount_due,
    };
    pay_button_pay.disabled = true;
    clear_message();
    $.ajax({
        method: "POST",
        url: "scripts/artpos_processPayment.php",
        data: postData,
        success: function (data, textstatus, jqxhr) {
            paySuccess(data);
        },
        error: function (jqXHR, textstatus, errorThrown) {
            pay_button_pay.disabled = false;
            showAjaxError(jqXHR, textstatus, errorThrown);
        },
    });
}

// process payment return success
function paySuccess(data) {
    // things that stop us cold....
    if (typeof data == 'string') {
        show_message(data, 'error');
        if (data.includes("cancelled")) {
            payPoll = 0;
            payCurrentRequest = null;
            pay_button_pay.disabled = false;
        } else if (payPoll == 1)
            document.getElementById('pollRow').hidden = false;
        return;
    }

    if (data.error !== undefined) {
        show_message(data.error, 'error');
        if (data.error.includes("cancelled")) {
            payPoll = 0;
            payCurrentRequest = null;
            pay_button_pay.disabled = false;
        }  else if (payPoll == 1)
            document.getElementById('pollRow').hidden = false;
        return;
    }

    if (data.status == 'error') {
        show_message(data.data, 'error');
        if (data.error.includes("cancelled")) {
            payPoll = 0;
            payCurrentRequest = null;
            pay_button_pay.disabled = false;
        } else if (payPoll == 1)
            document.getElementById('pollRow').hidden = false;
        return;
    }

    if (data.warn !== undefined) {
        show_message(data.warn, 'warn');
        // warn means we could not get the terminal, ask if we want to override it
        if (data.status != 'OFFLINE') {
            document.getElementById('overrideRow').hidden = false;
            return;
        }
    }

    payPoll = 0;
    pay_button_pay.disabled = false;
    // and things that continue
    if (data.message !== undefined) {
        show_message(data.message, 'success');
    }
    if (data.hasOwnProperty('poll')) {
        if (data.poll == 1) {
            if (data.id) {
                payCurrentRequest = data.id;
            }
            document.getElementById('pollRow').hidden = false;
            pay_button_pay.disabled = true;
            payPoll = 1;
            return;
        }
    }

    payCurrentRequest = null;
    cart.updatePmt(data);
    total_art_due -= data.preTaxAmt;
    total_tax_due -= data.taxAmt;
    total_amount_due -= data.approved_amt;
    payShown();
}

var last_receipt_type = '';
// Create a receipt and send it to the receipt printer
function print_receipt(receipt_type) {
    last_receipt_type = receipt_type;
    var d = new Date();
    var payee = (currentPerson.first_name + ' ' + currentPerson.last_name).trim();

    // header text
    var header_text =  "\nReceipt for payment to " + conlabel + "\nat " + d.toLocaleString() + "\nBy: " + payee + ", Cashier: " + user_id + ", Transaction: " + pay_tid + "\n";
    // optional footer text
    var footer_text = '';
    // server side will print the receipt
    var postData = {
        ajax_request_action: 'printReceipt',
        header: header_text,
        person: currentPerson,
        arows: JSON.stringify(cart.getCartArt()),
        pmtrows: JSON.stringify(cart.getCartPmt()),
        footer: footer_text,
        receipt_type: receipt_type,
        email_addrs: emailAddreesRecipients,
    };
    if (receiptPrinterAvailable || receipt_type == 'email') {
        if (receipt_type == 'email')
            pay_button_ercpt.disabled = true;
        else
            pay_button_rcpt.disabled = true;

        $.ajax({
            method: "POST",
            url: "scripts/artpos_printReceipt.php",
            data: postData,
            success: function (data, textstatus, jqxhr) {
                clear_message();
                if (typeof data == "string") {
                    show_message(data,  'error');
                } else if (data.error !== undefined) {
                    show_message(data.error, 'error');
                } else if (data.message !== undefined) {
                    show_message(data.message, 'success');
                } else if (data.warn !== undefined) {
                    show_message(data.warn, 'success');
                }
                if (last_receipt_type == 'email')
                    pay_button_ercpt.disabled = false;
                else
                    pay_button_rcpt.disabled = false;
            },
            error: function (jqXHR, textstatus, errorThrown) {
                if (last_receipt_type == 'email')
                    pay_button_ercpt.disabled = false;
                else
                    pay_button_rcpt.disabled = false;
                showAjaxError(jqXHR, textstatus, errorThrown);
            }
        });
    } else {
        show_message("Receipt printer not available, Please use the \"Chg\" button in the banner to select the proper printers.");
    }
}

// tab shown events - state mapping for which tab is shown
function findShown() {
    cart.unfreeze();
    current_tab = find_tab;
    cart.drawCart();
    pay_InitialCart = true;
    // get statistics
    $.ajax({
        method: "POST",
        url: "scripts/artpos_stats.php",
        data: { stats: 'all' },
        success: function (data, textstatus, jqxhr) {
            if (data.error !== undefined) {
                show_message(data.error, 'error');
                return;
            }
            if (data.message !== undefined) {
                show_message(data.message, 'success');
            }
            updateStats(data);
        },
        error: showAjaxError,
    });
}

function updateStats(data) {
    active_customers = data.active_customers;
    awaiting_payment = data.need_pay;
    awaiting_release =  data.need_release;
    var html = '<div class="col-sm-2">Stats:</div>';
    if (active_customers.length > 0) {
        html += '<div class="col-sm-3 text-primary" onclick="showStats(' + "'active'" + ');">Active Customers: ' + active_customers.length + '</div>';
    } else {
        html += '<div class="col-sm-3">Active Customers: 0</div>';
    }
    if (awaiting_payment.length > 0) {
        html += '<div class="col-sm-3 text-primary" onclick="showStats(' + "'payment'" + ');">Awaiting Payment: ' + awaiting_payment.length + '</div>';
    } else {
        html += '<div class="col-sm-3">Awaiting Payment: 0</div>';
    }
    if (awaiting_release.length > 0) {
        html += '<div class="col-sm-3 text-primary" onclick="showStats(' + "'release'" + ');">Awaiting Release: ' + awaiting_release.length + '</div>';
    } else {
        html += '<div class="col-sm-3">Awaiting Release: 0</div>';
    }
    stats_div.innerHTML = html;
    cart.showStartOver();
}

// statistics display functions
function hideStats() {
    if (statsTable) {
        statsTable.destroy();
        statsTable = null;
    }
    showStats_div.innerHTML = '';
}
function showStats(which) {
    var data = null;
    switch (which) {
        case 'active':
            data = active_customers;
            break;
        case 'payment':
            data = awaiting_payment;
            break;
        case 'release':
            data = awaiting_release;
            break;
    }

    if (statsTable) {
        statsTable.destroy();
        statsTable = null;
    }

    if (data == null)
        return;

    showStats_div.innerHTML = '<div class="row"><div class="col-sm-12" id="statsTableDiv"></div></div>' +
        '<div class="row mt-2 mb-2"><div class="col-sm-auto"><button class="btn btn-sm btn-primary" onclick="hideStats();">Hide Detail</button></div></div>';
    statsTable = new Tabulator('#statsTableDiv', {
        maxHeight: "400px",
        data: data,
        index: 'perid',
        layout: "fitColumns",
        pagination: true,
        paginationSize: 10,
        paginationSizeSelector: [10, 25, 50, 100, 250, true], //enable page size select element with these options

        columns: [
            {title: "Badge #", field: "perid",  headerSort: true, headerFilter: true, maxWidth: 140, width: 140, hozAlign: 'right', headerHozAlign: 'right' },
            {title: "Name", field: "name", headerFilter: true, maxWidth: 500, width: 500, },
            {title: "# Items", field: "items", maxWidth: 100, width: 100, hozAlign: 'right', headerHozAlign: 'right', headerSort: false, },
        ],
    });

    statsTable.on("cellClick", personClicked);
}

function personClicked(e, cell) {
    badgeid_field.value = cell.getData().perid;
    findPerson('search');
}

function addShown() {
    cart.unfreeze();
    current_tab = add_tab;
    clear_message();
    cart.drawCart();
    if (cart.getCartLength() > 0) {
        cart.showPay();
    }
    cart.showStartOver();
    pay_InitialCart = true;
    artistNumber_field.value = null;
    pieceNumber_field.value = null;
    unitNumber_field.value = null;
    itemCode_field.value = null;
    itemCode_field.focus();
}

var emailAddreesRecipients = [];

// show the pay tab, and its current dataset, if first call, update artSales in the database.
function payShown() {
    if (pay_InitialCart) {
        pay_InitialCart = false;
        initArtSales();
        return;
    }
    if (pay_currentOrderId == null) {
        buildOrder();
        return;
    }
    cart.freeze();
    current_tab = pay_tab;
    cart.drawCart();
    thisPay_art = 0;
    thisPay_tax = 0;
    thisPay_total = 0;

    if (total_amount_due  < 0.01) { // allow for rounding error, no need to round here
        // nothing more to pay
        cart.showNext();
        cart.showRelease();
        cart.hideStartOver();
        add_tab.disabled = true;
        if (pay_button_pay != null) {
            var rownum;
            pay_button_pay.hidden = true;
            pay_button_rcpt.hidden = false;
            var email_html = '';
            var email_addr = currentPerson.email_addr;
            if (emailRegex.test(email_addr)) {
                email_html += '<div class="row"><div class="col-sm-1 pe-2"></div><div class="col-sm-8">' + email_addr + '</div></div>';
            }
            if (email_html.length > 2) {
                pay_button_ercpt.hidden = false;
                pay_button_ercpt.disabled = false;
                pay_button_ercpt.disabled = false;
                receeiptEmailAddresses_div.innerHTML = '<div class="row mt-2"><div class="col-sm-9 p-0">Email receipt to:</div></div>' + email_html;
                emailAddreesRecipients.push(currentPerson.email_addr);
            }
            document.getElementById('pay-desc').value='';
            document.getElementById('pay-check-div').hidden = true;
            document.getElementById('pay-ccauth-div').hidden = true;
        } else {
            cart.showNext();
            cart.showRelease();
            cart.hideStartOver();
        }
    } else {
        if (pay_button_pay != null) {
            pay_button_pay.hidden = false;
            pay_button_rcpt.hidden = true;
            pay_button_ercpt.hidden = true;
            pay_button_ercpt.disabled = true;
        }

        // draw the pay screen
        var payHtml = `
<div id='payBody' class="container-fluid form-floating">
  <form id='payForm' action='javascript: return false; ' class="form-floating">
    <div class="row pb-2">
        <div class="col-sm-auto ms-0 me-2 p-0">New Payment Transaction ID: ` + pay_tid + `</div>
    </div>
`;

        // column headings
        payHtml += `
    <div class="row mt-1">
        <div class="col-sm-6 ms-0 me-0 p-0"></div>
        <div class="col-sm-3 ms-0 me-0 p-0 text-end"><b>Balance Due</b></div>
    </div>        
`;
        // if tax rate exists show tax items
        if (config.taxRate > 0) {
            payHtml += `
    <div class="row mt-1">
        <div class="col-sm-6 m-0 p-0">Art Total:</div>
        <div class="col-sm-3 m-0 p-0 text-end" id="total-art-due">$` + Number(total_art_due).toFixed(2) + `</div>
    </div>
    <div class="row mt-2">
        <div class="col-sm-6 m-0 p-0">` + config.taxLabel + ' ' + config.taxRate + ` % sales tax:</div>
        <div class="col-sm-3 m-0 p-0 text-end" id="total-tax-due">$` + Number(total_tax_due).toFixed(2) + `</div>
    </div>
`;
    }

    payHtml += `
    <div class="row mt-1">
        <div class="col-sm-6 m-0 p-0">Amount Due:</div>
        <div class="col-sm-3 m-0 p-0 text-end" id="total-amt-due">$` + Number(total_amount_due).toFixed(2) + `</div>
    </div>
    <div class="row">
        <div class="col-sm-2 m-0 mt-2 me-2 mb-2 p-0">Payment Type:</div>
        <div class="col-sm-auto m-0 mt-2 p-0 mb-2 p-0" id="pt-div">
`;
        if (config.terminal == 1) {
            payHtml += `
            <input type="radio" id="pt-terminal" name="payment_type" value="terminal" onclick='setPayType("terminal");'/>
            <label for="pt-terminal">Credit Card Terminal&nbsp;&nbsp;&nbsp;</label>
`;
        }
        if (config.creditoffline == 1) {
            payHtml += `
            <input type="radio" id="pt-credit" name="payment_type" value="credit" onclick='setPayType("credit");'/>
            <label for="pt-credit">Offline Credit Card&nbsp;&nbsp;&nbsp;</label>
`;
        }

        payHtml += `            
            <input type="radio" id="pt-check" name="payment_type" value="check" onclick='setPayType("check");'/>
            <label for="pt-check">Check&nbsp;&nbsp;&nbsp;</label>
            <input type="radio" id="pt-cash" name="payment_type" value="cash" onclick='setPayType("cash");'/>
            <label for="pt-cash">Cash</label>
`;
        if (discount_mode != "none") {
            if (discount_mode == 'any' || (discount_mode == 'manager' && hasManager) || (discount_mode == 'active' && hasManager && baseManagerEnabled)) {
                payHtml += `
            <input type="radio" id="pt-discount" name="payment_type" value="discount" onclick='setPayType("discount");'/>
            <label for="pt-discount">Discount</label>
`;
            }
        }
        payHtml += `
        </div>
    </div>
    <div class="row mb-2" id="pay-cash-div" hidden>
        <div class="col-sm-2 ms-0 me-2 p-0">Amt Tendered:</div>
        <div class="col-sm-auto m-0 p-0 ms-0 me-2 p-0"><input type="number" class="no-spinners" id="pay-tendered" name="paid-tendered" size="6"/></div>
    </div>
    <div class="row mb-2" id="pay-check-div" hidden>
        <div class="col-sm-2 ms-0 me-2 p-0">Check #:</div>
        <div class="col-sm-auto m-0 p-0 ms-0 me-2 p-0"><input type="text" size="8" maxlength="10" name="pay-checkno" id="pay-checkno"/></div>
    </div>
    <div class="row mb-2" id="pay-ccauth-div" hidden>
        <div class="col-sm-2 ms-0 me-2 p-0">CC Auth Code:</div>
        <div class="col-sm-auto m-0 p-0 ms-0 me-2 p-0"><input type="text" size="15" maxlength="16" name="pay-ccauth" id="pay-ccauth"/></div>
    </div>
    <div class="row">
        <div class="col-sm-2 ms-0 me-2 p-0">Description:</div>
        <div class="col-sm-auto m-0 p-0 ms-0 me-2 p-0"><input type="text" size="60" maxlength="64" name="pay-desc" id="pay-desc"/></div>
    </div>
    <div class="row mt-3">
        <div class="col-sm-2 ms-0 me-2 p-0">&nbsp;</div>
        <div class="col-sm-auto ms-0 me-2 p-0">
            <button class="btn btn-primary btn-sm" type="button" id="pay-btn-pay" onclick="pay('');">Confirm Pay</button>
        </div>
        <div class="col-sm-auto ms-0 me-2 p-0">
            <button class="btn btn-primary btn-sm" type="button" id="pay-btn-ercpt" onclick="print_receipt('email');" hidden disabled>Email Receipt</button>
        </div>
        <div class="col-sm-auto ms-0 me-2 p-0">
            <button class="btn btn-primary btn-sm" type="button" id="pay-btn-rcpt" onclick="print_receipt('print');" hidden>Print Receipt</button>
        </div>
    </div>
    <div class="row mt-3" id="overrideRow" hidden>
        <div class="col-sm-auto ms-0 me-2 p-0">
            <button class="btn btn-warning btn-sm" type="button" id="pay-btn-override" onclick="overridePay();">Override</button>
        </div>
        <div class="col-sm-10 ms-0 me-2 p-0" id="override_msg">
            <p>The terminal is marked as not available, override the status to take control and use it anyway?</p>
            <p>This will cancel any payment in process on the terminal.</p>
        </div>
    </div>
     <div class="row mt-3" id="pollRow" hidden>
        <div class="col-sm-auto ms-0 me-2 p-0">
            <button class="btn btn-primary btn-sm" type="button" id="pay-poll-complete" onclick="payPollfcn(1);">Payment Complete</button>
        </div>
        <div class="col-sm-auto ms-0 me-2 p-0">
            <button class="btn btn-primary btn-sm" type="button" id="pay-poll-cancel" onclick="payPollfcn(0);">Cancel Payment</button>
        </div>
    </div>    
    <div id="receeiptEmailAddresses" class="container-fluid"></div>
  </form>
    <div class="row mt-4">
        <div class="col-sm-12 p-0" id="pay_status"></div>
    </div>
</div>
`;

        pay_div.innerHTML = payHtml;
        pay_button_pay = document.getElementById('pay-btn-pay');
        pay_button_rcpt = document.getElementById('pay-btn-rcpt');
        pay_button_ercpt = document.getElementById('pay-btn-ercpt');
        receeiptEmailAddresses_div = document.getElementById('receeiptEmailAddresses');
        if (receeiptEmailAddresses_div)
            receeiptEmailAddresses_div.innerHTML = '';
        if (cart.getPmtLength() > 0) {
            cart.hideStartOver();
        } else {
            cart.showStartOver();
        }
    }
}

// releaseShown - show the release tab
function releaseShown() {
    current_tab = release_tab;
    pay_tab.disabled = true;
    cart.showNext();
    cart.hideStartOver();
    clear_message();

    // search for matching names
    var postData = {
        ajax_request_action: 'findRelease',
        perid: currentPerson.id,
    };
    $.ajax({
        method: "POST",
        url: "scripts/artpos_findRelease.php",
        data: postData,
        success: function (data, textstatus, jqxhr) {
            if (data.error !== undefined) {
                show_message(data.error, 'error');
                return;
            }
            if (data.message !== undefined) {
                show_message(data.message, 'success');
            }
            if (data.warn !== undefined) {
                show_message(data.warn, 'warn');
            }
            foundRelease(data);
        },
        error: function (jqXHR, textstatus, errorThrown) {
            $("button[name='find_btn']").attr("disabled", false);
            showAjaxError(jqXHR, textstatus, errorThrown);
        }
    });
}

function foundRelease(data) {
    releaseTitleDiv.innerHTML = 'Check Artwork Purchased by ' + (currentPerson.first_name + ' ' + currentPerson.last_name).trim();

    var art = data.art;
    if (releaseTable != null) {
        releaseTable.destroy();
        releaseTable = null;
    }

    if (art.length == 0) {
        // nothing to release, probably was just prints
        releaseModal.hide();
        show_message("No artwork to release.  Prints are auto-released on purchase." , "warn");
        document.getElementById('release_btn').hidden = true;
        return;
    }

    releaseTable = new Tabulator('#ReleaseArtBody', {
        maxHeight: "600px",
        data: art,
        index: 'id',
        layout: "fitColumns",
        initialSort: [
            {column: "exhibitorNumber", dir: "asc"},
            {column: "item_key", dir: "asc"},
        ],
        pagination: true,
        paginationSize: 25,
        paginationSizeSelector: [25, 50, 100, 250, true], //enable page size select element with these options

        columns: [
            {field: "id", visible: false,},
            {field: "artSalesId", visible: false,},
            {title: "CO", field: "released", maxWidth: 90, width: 90, hozAlign: 'center', formatter: "tickCross", cellClick: invertTickCross, headerSort: true, },
            {title: "Exh #", field: "exhibitorNumber",  headerSort: true, headerFilter: true, headerWordWrap: true, maxWidth: 100, width: 100, hozAlign: 'right', },
            {title: "Item #", field: "item_key", headerFilter: true, headerWordWrap: true,  maxWidth: 100, width: 100, hozAlign: 'right' },
            {title: "Exhibitor Name", field: "exhibitorName", headerFilter: true, headerWordWrap: true,  maxWidth: 250, width: 250, },
            {title: "Qty", field: "purQuantity", maxWidth: 90, width: 90, hozAlign: 'right' },
            {title: "Type", field: "type", headerFilter: true, width: 120, maxWidth: 120, },
            {title: "Title", field: "title", headerSort: true, headerFilter: true, headerWordWrap: true, maxWidth: 400, width: 400, },
            {title: "Material", field: "material", headerSort: true, headerFilter: true,  width: 300, maxWidth: 300, },
        ],
    });

    releaseModal.show();
}

function releaseSetAll(value) {
    if (releaseTable == null)
        return;

    var counts = releaseTable.getDataCount();
    for (var index = 1; index <= counts;  index++) {
        var row = releaseTable.getRowFromPosition(index);
        var cell = row.getCell('released');
        cell.setValue(value);
    }
}

function invertTickCross(e,cell) {
    'use strict';

    var value = cell.getValue();
    if (value === undefined) {
        value = false;
    }
    if (value === 0 || Number(value) === 0)
        value = false;
    else if (value === "1" || Number(value) > 0)
        value = true;

    cell.setValue(!value, true);
}

function processRelease() {
    var data = releaseTable.getData();
    releaseModal.hide();
    clear_message();
    $.ajax({
        url: 'scripts/artpos_processRelease.php',
        method: "POST",
        data: { art: JSON.stringify(data), perid: currentPerson.id, user_id: user_id, },
        success: function (data, textstatus, jqxhr) {
            if (data.error !== undefined) {
                show_message(data.error, 'error');
                return;
            }
            if (data.warn !== undefined) {
                show_message(data.warn, 'warn');
            }
            if (data.message !== undefined) {
                show_message(data.message, 'success');
            }
            if (data.num_remain > 0) {
                if (confirm(data.num_remain + ' items are still not released, return to release?'))
                    releaseShown();
            } else {
                cart.hideRelease();
                startOver(0);
            }
        },
        error: showAjaxError
    });
}

// combined exit change check
function onExit() {
    // if they have a terminal action in process, as if they want to leave install of 'poll' for it's status
    if (payPoll == 1) {
        var currentOrder = pay_currentOrderId;
        var user_id = user_id;
        pay_currentOrderId = null;
        // cancel terminal request
        var postData = {
            ajax_request_action: 'cancelPayRequest',
            requestId: payCurrentRequest,
            user_id: user_id,
        };
        var _this = this;
        clear_message();
        $.ajax({
            method: "POST",
            url: "scripts/artpos_cancelPayment.php",
            data: postData,
            success: function (data, textstatus, jqxhr) {
                if (typeof data == 'string') {
                    show_message(data, 'error');
                    return;
                }

                if (data.error !== undefined) {
                    show_message(data.error, 'error');
                    return;
                }

                if (data.status == 'error') {
                    show_message(data.data, 'error');
                    return;
                }

                if (data.warn !== undefined) {
                    show_message(data.warn, 'warn');
                }

                if (data.message !== undefined) {
                    show_message(data.message, 'success');
                }
                if (currentOrder && currentOrder != '') {
                    var postData = {
                        ajax_request_action: 'cancelOrder',
                        orderId: currentOrder,
                        user_id: user_id,
                    };
                    $.ajax({
                        method: "POST",
                        url: "scripts/artpos_cancelOrder.php",
                        data: postData,
                        success: function (data, textstatus, jqxhr) {
                            if (data.error !== undefined) {
                                show_message(data.error, 'error');
                                return;
                            }
                            if (data.warn !== undefined) {
                                show_message(data.warn, 'warn');
                                if (data.hasOwnProperty('paid') && data.paid == 1) {
                                    // it paid while waiting for the poll, process the payment
                                    _payPoll = 1;
                                    _pay_currentOrderId = currentOrder;
                                    _pay('');
                                    _payPoll = 0;
                                    _pay_currentOrderId = null;
                                }
                                return;
                            }
                            if (data.message !== undefined) {
                                show_message(data.message, 'success');
                            }
                        },
                        error: function (jqXHR, textstatus, errorThrown) {
                            $("button[name='find_btn']").attr("disabled", false);
                            showAjaxError(jqXHR, textstatus, errorThrown);
                        }
                    });
                }
            },
            error: function (jqXHR, textstatus, errorThrown) {
                document.getElementById('pollRow').hidden = false;
                _pay_button_pay.disabled = true;
                showAjaxError(jqXHR, textstatus, errorThrown);
            },
        });
        payPoll = 0;
        return true;
    }
    if (pay_currentOrderId && pay_currentOrderId != '') {
        var postData = {
            ajax_request_action: 'cancelOrder',
            orderId: pay_currentOrderId,
            user_id: user_id,
        };
        $.ajax({
            method: "POST",
            url: "scripts/artpos_cancelOrder.php",
            data: postData,
            success: function (data, textstatus, jqxhr) {
                if (data.error !== undefined) {
                    show_message(data.error, 'error');
                    return;
                }
                if (data.warn !== undefined) {
                    show_message(data.warn, 'warn');
                    return;
                }
                if (data.message !== undefined) {
                    show_message(data.message, 'success');
                }
            },
            error: function (jqXHR, textstatus, errorThrown) {
                $("button[name='find_btn']").attr("disabled", false);
                showAjaxError(jqXHR, textstatus, errorThrown);
            }
        });
        pay_currentOrderId = null;
    }
    startOver(1);
    return true;
}
