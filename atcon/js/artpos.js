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
var current_person = null;
var stats_div = null;

// art items
var add_found_div = null;
var artFoundItems = null;
var itemCode_field = null;
var artistNumber_field = null;
var pieceNumber_field = null;
var unitNumber_field = null;

// pay items
var pay_div = null;
var pay_button_pay = null;
var pay_button_rcpt = null;
var pay_button_ercpt = null;
var pay_tid = null;
var pay_InitialCart = true;
var discount_mode = 'none';
var cart_total = Number(0).toFixed(2);

// release items
var releaseModal = null;
var releaseTitleDiv = null;
var releaseBodyDiv = null;
var releaseTable = null;

// Data Items
var unpaid_table = [];
var result_perinfo = [];
var cashChangeModal = null;

// global items
var conid = null;
var conlabel = null;
var user_id = 0;
var hasManager = false;
var receiptPrinterAvailable = false;

const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

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
    find_tab.addEventListener('shown.bs.tab', find_shown)
    add_tab.addEventListener('shown.bs.tab', add_shown)
    pay_tab.addEventListener('shown.bs.tab', pay_shown)
    release_tab.addEventListener('shown.bs.tab', release_shown)

    // cash payment requires change
    cashChangeModal = new bootstrap.Modal(document.getElementById('CashChange'), { focus: true, backldrop: 'static' });

    // release works in a modal
    releaseModal = new bootstrap.Modal(document.getElementById('ReleaseArt'), { focus: true, backldrop: 'static' });
    releaseTitleDiv = document.getElementById("ReleaseArtTitle");
    releaseBodyDiv = document.getElementById("ReleaseArtBody");

    bootstrap.Tab.getOrCreateInstance(find_tab).show();

    // load the initial data and the proceed to set up the rest of the system
    var postData = {
        ajax_request_action: 'loadInitialData',
    };
    $.ajax({
        method: "POST",
        url: "scripts/artpos_loadInitialData.php",
        data: postData,
        success: function (data, textstatus, jqxhr) {
            if (data['error'] !== undefined) {
                show_message(data['error'], 'error');
                return;
            }
            if (data['message'] !== undefined) {
                show_message(data['message'], 'success');
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
    conlabel =  data['label'];
    conid = data['conid'];
    user_id = data['user_id']
    hasManager = data['hasManager'];
    receiptPrinterAvailable = data['receiptPrinter'] === true;
    find_shown();
}

// if no artSales or payments have been added to the database, this will reset for the next customer
function start_over(reset_all) {
    if (reset_all > 0)
        clear_message();

    if (base_manager_enabled) {
        base_toggleManager();
    }
    // empty cart
    cart.startOver();
    // empty search strings and results
    badgeid_field.value = "";
    id_div.innerHTML = "";
    unpaid_table = null;

    // reset data to call up
    emailAddreesRecipients = [];
    last_email_row = '';

    // reset tabs to initial values
    find_tab.disabled = false;
    add_tab.disabled = true;
    pay_tab.disabled = true;
    release_tab.disabled = true;
    cart.hideNext();
    cart.hideAdd();
    pay_button_pay = null;
    pay_button_rcpt = null;
    pay_button_ercpt = null;
    receeiptEmailAddresses_div = null;
    pay_tid = null;
    pay_InitialCart = true;

    // set tab to find-tab
    bootstrap.Tab.getOrCreateInstance(find_tab).show();
    badgeid_field.focus();
}

// switch to the add tab
function goto_find() {
    bootstrap.Tab.getOrCreateInstance(find_tab).show();
}

// switch to the add tab
function goto_add() {
    bootstrap.Tab.getOrCreateInstance(add_tab).show();
}

// switch to the pay tab
function goto_pay() {
    bootstrap.Tab.getOrCreateInstance(pay_tab).show();
}

function goto_release() {
    if (current_tab != release_tab) {
        bootstrap.Tab.getOrCreateInstance(release_tab).show();
    } else {
        release_shown();
    }
}

// draw_person: findPerson found someone.  Display their details
function draw_person() {
    var html = `
    <div class="container-fluid">
        <div class="row">
            <div class="col-sm-3">Person ID:</div>
            <div class="col-sm-9">` + current_person['id'] + `</div>
        </div>
        <div class="row">
            <div class="col-sm-3">` + 'Badge Name:' + `</div>
            <div class="col-sm-9">` + badge_name_default(current_person['badge_name'], current_person['first_name'], current_person['last_name']) + `</div>
        </div>
        <div class="row">
            <div class="col-sm-3">Name:</div>
            <div class="col-sm-9">` +
            current_person['first_name'] + ' ' + current_person['middle_name'] + ' ' + current_person['last_name'] + `
            </div>
        </div>  
        <div class="row">
            <div class="col-sm-3">Address:</div>
            <div class="col-sm-9">` + current_person['address'] + `</div>
        </div>
`;
    if (current_person['address_2'] != '') {
        html += `
    <div class="row">
        <div class="col-sm-3"></div>
        <div class="col-sm-9">` + current_person['addr_2'] + `</div>
    </div>
`;
    }
    html += `
    <div class="row">
       <div class="col-sm-3"></div>
       <div class="col-sm-9">` + current_person['city'] + ', ' + current_person['state'] + ' ' + current_person['postal_code'] + `</div>
    </div>
`;
    if (current_person['country'] != '' && current_person['country'] != 'USA') {
        html += `
    <div class="row">
       <div class="col-sm-3"></div>
       <div class="col-sm-9">` + current_person['country'] + `</div>
    </div>
`;
    }
    html += `
    <div class="row">
       <div class="col-sm-3">Email Address:</div>
       <div class="col-sm-9">` + current_person['email_addr'] + `</div>
    </div>
    <div class="row">
       <div class="col-sm-3">Phone:</div>
       <div class="col-sm-9">` + current_person['phone'] + `</div>
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
            if (data['error'] !== undefined) {
                show_message(data['error'], 'error');
                $("button[name='find_btn']").attr("disabled", false);
                return;
            }
            if (data['message'] !== undefined) {
                show_message(data['message'], 'success');
            }
            if (data['warn'] !== undefined) {
                show_message(data['warn'], 'warn');
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

// successful return from 2 AJAX call - processes found records
// unpaid: one record: put it in the cart and go to pay screen
//      multiple records: show table of records with pay icons
// normal:
//      single row: display record
//      multiple rows: display table of records with add/trans buttons
function foundPerson(data) {
    if (data['num_rows'] == 1) { // one person found
        current_person = data['person'];
        // put the person details in the cart, populate the cart with the art they have to purchase
        draw_person();
        data['art'].forEach((artItem) => {
            if (pay_tid == null) {
                pay_tid = artItem['transid'];
            }
            cart.add(artItem);
        });
        if (data['payment']) {
            data['payment'].forEach((paymentItem) => {
                cart.addPmt(paymentItem);
            });
        }
        find_tab.disabled = true;
        add_tab.disabled = false;
        cart.showAdd();
        if (cart.getCartLength() > 0) {
            pay_tab.disabled = false;
            cart.showPay();
        }
        cart.drawCart();
        cart.showStartOver();
        if (data['release'] > 0 && cart.getCartLength() == 0) {
            release_tab.disabled = false;
            cart.showRelease();
        }
        return;
    } else { // I'm not sure how we'd get here
        show_message(data['num_rows'] + " found.  Multiple people not yet supported.");
        return;
    }
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
    };

    $("button[name='findArtBtn']").attr("disabled", true);
    $.ajax({
        method: "POST",
        url: "scripts/artpos_getArt.php",
        data: postData,
        success: function (data, textstatus, jqxhr) {
            if (data['error'] !== undefined) {
                show_message(data['error'], 'error');
                $("button[name='findArtBtn']").attr("disabled", false);
                return;
            }
            if (data['message'] !== undefined) {
                show_message(data['message'], 'success');
            }
            if (data['warn'] !== undefined) {
                show_message(data['warn'], 'warn');
            }
            foundArt(data);
            $("button[name='findArtBtn']").attr("disabled", false);
        },
        error: function (jqXHR, textstatus, errorThrown) {
            $("button[name='findArtBtn']").attr("disabled", false);
            showAjaxError(jqXHR, textstatus, errorThrown);
        }
    });
}

// foundArt - process the returned array of art items to select from
function foundArt(data) {
    var html = '';
    var valid = true;
    var btn_color = 'btn-primary';
    artFoundItems = data['items'];
    if (data['items'].length == 1) {
        var item = data['items'][0];
        html  = '<div class="row mt-4 mb-1"><div class="col-sm-12 bg-primary text-white">Item Details</div></div>';
        html += '<div class="row"><div class="col-sm-4">Artist Number:</div><div class="col-sm-8">' + item['exhibitorNumber'] + '</div></div>';
        html += '<div class="row"><div class="col-sm-4">Artist Item #:</div><div class="col-sm-8">' + item['item_key'] + '</div></div>';
        html += '<div class="row"><div class="col-sm-4">Type:</div><div class="col-sm-8">' + item['type'] + '</div></div>';
        html += '<div class="row"><div class="col-sm-4">Status:</div><div class="col-sm-8">' + item['status'] + '</div></div>';
        html += '<div class="row"><div class="col-sm-4">Artist Name:</div><div class="col-sm-8">' + item['exhibitorName'] + '</div></div>';
        html += '<div class="row"><div class="col-sm-4">Title:</div><div class="col-sm-8">' + item['title'] + '</div></div>';
        html += '<div class="row"><div class="col-sm-4">Material:</div><div class="col-sm-8">' + item['material'] + '</div></div>';
        if (item['bidder'] != null && item['bidder'] != '' && item['bidder'] != current_person['id']) {
            valid = false;
            html += '<div class="row"><div class="col-sm-4 bg-warning">Already Sold:</div><div class="col-sm-8 bg-warning">Item has already been sold to someone else.</div></div>';
        }

        if (item['type'] == 'print') {
            html += '<div class="row"><div class="col-sm-4">Sale Price:</div><div class="col-sm-8">$' + Number(item['sale_price']).toFixed(2) + '</div></div>';

            if (item['quantity'] <= 0) {
                html += '<div class="row"><div class="col-sm-4 bg-warning">Quantity:</div><div class="col-sm-8 bg-warning">System shows all of this item is already sold, remaining quantity is 0.</div></div>';
                btn_color = 'btn-warning';
            }
        }

        if (valid) {
            switch (item['type']) {
                case 'art':
                    if (item['sale_price'] == 0 || item['sale_price'] < item['min_price']) {
                        html += '<div class="row"><div class="col-sm-4 bg-danger text-white">Quick Sale:</div><div class="col-sm-8 bg-danger text-white">Item is not available for quick sale.</div></div>';
                        valid = false;
                        break;
                    }
                    if (item['status'].toLowerCase() == 'checked in') {
                        priceType = 'Quick Sale Price:';
                        priceField = 'sale_price';
                    } else {
                        priceType = 'Final Price:';
                        priceField = 'final_price';
                    }
                    break;

                case 'nfs':
                    html += '<div class="row"><div class="col-sm-4 bg-danger text-white">Not For Sale:</div><div class="col-sm-8 bg-danger text-white">You cannot buy a Not For Sale item.</div></div>';
                    valid = false;
                    break;
                case 'print':
                    html += '<div class="row"><div class="col-sm-4">Remaining Quantity:</div><div class="col-sm-8">' + item['quantity'] + '</div></div>';
                    priceType = 'Sale Price:'
                    priceField = 'sale_price';
                    break;
            }
        }

        if (valid) {
            htmlLine = '';
            switch (item['status'].toLowerCase()) {
                case 'checked in':
                    // currently nothing special for checked in items, this will be for sale at priceType via priceField
                    break;

                case 'bid':
                    item['status'] = 'Sold Bid Sheet';
                    htmlLine = '<div class="row"><div class="col-sm-4">Final Price:</div><div class="col-sm-8">' +
                        '<input type=number inputmode="numeric" class="no-spinners" id="art-final-price" name="art-final-price" style="width: 9em;" value="' + item['final_price'] + '"/></div></div>';
                    break;

                case 'nfs':
                    valid = false;
                    html += '<div class="row"><div class="col-sm-4 bg-danger text-white">Not For Sale:</div><div class="col-sm-8 bg-danger text-white">You cannot buy a Not For Sale item.</div></div>';
                    break;

                case 'removed from show':
                    html += '<div class="row"><div class="col-sm-4 bg-danger text-white">Removed:</div><div class="col-sm-8 bg-danger text-white">System shows item has been removed from the show.</div></div>';
                    valid = false;
                    break;

                case 'to auction':
                    html += '<div class="row"><div class="col-sm-4 bg-warning">Auction:</div><div class="col-sm-8 bg-warning">System shows item has been sent to the voice auction. Sell anyway?</div></div>';
                    btn_color = 'btn-warning';
                    break;

                case 'checked out':
                    html += '<div class="row"><div class="col-sm-4 bg-warning">Checked Out:</div><div class="col-sm-8 bg-warning">System shows item has been returned to the artist. Sell anyway?</div></div>';
                    btn_color = 'btn-warning';
                    break;

                case 'quicksale/sold':
                    html += '<div class="row"><div class="col-sm-4 bg-danger text-white">Released:</div><div class="col-sm-8 bg-danger text-white">System shows item already been sold via quicksale.</div></div>';
                    valid = false;
                    break;

                case 'purchased/released':
                    html += '<div class="row"><div class="col-sm-4 bg-danger text-white">Released:</div><div class="col-sm-8 bg-danger text-white">System shows item already been released to a purchaser.</div></div>';
                    valid = false;
                    break;

                case 'sold bid sheet':
                case 'sold at auction':
                    if (item['final_price'] == item['paid']) {
                        html += '<div class="row"><div class="col-sm-4 bg-danger text-white">Sold:</div><div class="col-sm-8 bg-danger text-white">System shows item already been sold and paid for.</div></div>';
                        valid = false;
                    }
                    break;

            }
        }

        if (valid) {
            if (cart.notinCart(item['id'])) {
                if (htmlLine != '') {
                    html += htmlLine;
                } else {
                    html += '<div class="row"><div class="col-sm-4">' + priceType + '</div><div class="col-sm-8">$' + Number(item[priceField]).toFixed(2) + '</div></div>';
                    html += '<div class="row mt-2"><div class="col-sm-4"></div><div class="col-sm-8"><button class="btn btn-sm ' + btn_color + '" type="button" onclick="addToCart(-1);">Add Art Item to Cart</button></div></div>';
                }
            } else {
                html += '<div class="row mt-2"><div class="col-sm-4"></div><div class="col-sm-auto bg-warning">Already in Cart</div></div>';
            }
        }
    }

    add_found_div.innerHTML = html;
    return;
}

// addToCart - add this row index to the cart
function addToCart(index) {
    var item = null;
    if (index < 0 && artFoundItems.length > 0) {
        item = artFoundItems[0];
    } else if (index >= artFoundItems.length) {
        return;
    } else {
        item = artFoundItems[index];
    }

    var finalPriceField = document.getElementById('art-final-price');
    if (finalPriceField) {
        var enteredPrice = finalPriceField.value;
        if (enteredPrice == null)
            enteredPrice = 0;
        var finalPrice = item['final_price'];
        if (finalPrice == null || finalPrice < 0) {
            if (item['sale_price'] == null || item['sale_price'] == 0)
                finalPrice = item['min_price'];
            else
                finalPrice = item['sale_price'];
        }
        if (enteredPrice < finalPrice) {
            if (confirm("Entered final price is less than system's sell price of " + finalPrice + ", sell at this price anyway?"))
                item['final_price'] = Number(finalPrice).toFixed(2);
            else
                return;
        } else {
            item['final_price'] = enteredPrice;
        }
    }

    cart.add(item);
    add_found_div.innerHTML = "";
}

// initArtSales - create/update artSales records for this cart to prepare for payment, create master transaction if none exists
function initArtSales() {
    // submit the current card data to update the database, retrieve updated cart
    var postData = {
        ajax_request_action: 'initArtSales',
        cart_art: JSON.stringify(cart.getCartArt()),
        cart_art_map: JSON.stringify(cart.getCartMap()),
        pay_tid: pay_tid,
        perid: current_person['id'],
        user_id: user_id,
    };
    $.ajax({
        method: "POST",
        url: "scripts/artpos_initArtSales.php",
        data: postData,
        success: function (data, textstatus, jqxhr) {
            if (data['error'] !== undefined) {
                show_message(data['error'], 'error');
                return;
            }
            if (data['message'] !== undefined) {
                show_message(data['message'], 'success');
            }
            if (data['warn'] !== undefined) {
                show_message(data['warn'], 'success');
            }
            initArtSalesComplete(data);
        },
        error: showAjaxError,
    });
}

// initArtSalesComplete - now update the cart with the new data and call pay_shown again to draw it.
//  all the data from the cart has been updated in the database, now apply the id's and proceed to the next step
function initArtSalesComplete(data) {
    pay_tid = data['pay_tid'];
    // update cart elements
    var unpaid_rows = cart.updateFromDB(data);
    pay_shown();
}

// setPayType: shows/hides the appropriate fields for that payment type
function setPayType(ptype) {
    var elcheckno = document.getElementById('pay-check-div');
    var elccauth = document.getElementById('pay-ccauth-div');

    elcheckno.hidden = ptype != 'check';
    elccauth.hidden = ptype != 'credit';

    if (ptype != 'check') {
        document.getElementById('pay-checkno').value = null;
    }
    if (ptype != 'credit') {
        document.getElementById('pay-ccauth').value = null;
    }
}

// Process a payment against the transaction
function pay(nomodal, prow = null) {
    var checked = false;
    var ccauth = null;
    var checkno = null;
    var desc = null;
    var ptype = null;
    var total_amount_due = cart.getTotalPrice() - cart.getTotalPaid();

    if (nomodal != '') {
        cashChangeModal.hide();
    }

    if (prow == null) {
        // validate the payment entry: It must be >0 and <= amount due
        //      a payment type must be specified
        //      for check: the check number is required
        //      for credit card: the auth code is required
        //      for discount: description is required, it's optional otherwise
        var elamt = document.getElementById('pay-amt');
        var pay_amt = Number(elamt.value);
        if (pay_amt > 0 && pay_amt > total_amount_due) {
            if (document.getElementById('pt-cash').checked) {
                if (nomodal == '') {
                    cashChangeModal.show();
                    document.getElementById("CashChangeBody").innerHTML = "Customer owes $" + total_amount_due.toFixed(2) + ", and tendered $" + pay_amt.toFixed(2) +
                        "<br/>Confirm change give to customer of $" + (pay_amt - total_amount_due).toFixed(2);
                    return;
                }
            } else {
                elamt.style.backgroundColor = 'var(--bs-warning)';
                return;
            }
        }
        if (pay_amt <= 0) {
            elamt.style.backgroundColor = 'var(--bs-warning)';
            return;
        }

        elamt.style.backgroundColor = '';

        var elptdiv = document.getElementById('pt-div');
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
        if (document.getElementById('pt-credit').checked) {
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

        if (!checked) {
            elptdiv.style.backgroundColor = 'var(--bs-warning)';
            return;
        }

        if (pay_amt > 0) {
            var crow = null;
            var change = 0;
            if (pay_amt > total_amount_due) {
                change = pay_amt - total_amount_due;
                pay_amt = total_amount_due;
                crow = {
                    index: cart.getPmtLength() + 1, amt: change, ccauth: ccauth, checkno: checkno, desc: eldesc.value, type: 'change',
                }
            }
            prow = {
                index: cart.getPmtLength(), amt: pay_amt, ccauth: ccauth, checkno: checkno, desc: eldesc.value, type: ptype,
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
        perid: current_person['id'],
        pay_tid: pay_tid,
    };
    pay_button_pay.disabled = true;
    $.ajax({
        method: "POST",
        url: "scripts/artpos_processPayment.php",
        data: postData,
        success: function (data, textstatus, jqxhr) {
            var stop = true;
            clear_message();
            if (typeof data == 'string') {
                show_message(data, 'error');
            } else if (data['error'] !== undefined) {
                show_message(data['error'], 'error');
            } else if (data['message'] !== undefined) {
                show_message(data['message'], 'success');
                stop = false;
            } else if (data['warn'] !== undefined) {
                show_message(data['warn'], 'success');
                stop = false;
            }
            if (!stop)
                updatedPayment(data);
            pay_button_pay.disabled = false;
        },
        error: function (jqXHR, textstatus, errorThrown) {
            pay_button_pay.disabled = false;
            showAjaxError(jqXHR, textstatus, errorThrown);
        },
    });
}


// updatedPayment:
//  payment entered into the database correctly, update the payment cart and the art with the updated paid amounts
function updatedPayment(data) {
    cart.updatePmt(data);
    pay_shown();
}

var last_receipt_type = '';
// Create a receipt and send it to the receipt printer
function print_receipt(receipt_type) {
    last_receipt_type = receipt_type;
    var d = new Date();
    var payee = (current_person['first_name'] + ' ' + current_person['last_name']).trim();

    // header text
    var header_text =  "\nReceipt for payment to " + conlabel + "\nat " + d.toLocaleString() + "\nBy: " + payee + ", Cashier: " + user_id + ", Transaction: " + pay_tid + "\n";
    // optional footer text
    var footer_text = '';
    // server side will print the receipt
    var postData = {
        ajax_request_action: 'printReceipt',
        header: header_text,
        person: current_person,
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
                } else if (data['error'] !== undefined) {
                    show_message(data['error'], 'error');
                } else if (data['message'] !== undefined) {
                    show_message(data['message'], 'success');
                } else if (data['warn'] !== undefined) {
                    show_message(data['warn'], 'success');
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
function find_shown() {
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
            if (data['error'] !== undefined) {
                show_message(data['error'], 'error');
                return;
            }
            if (data['message'] !== undefined) {
                show_message(data['message'], 'success');
            }
            updateStats(data);
        },
        error: showAjaxError,
    });
}

function updateStats(data) {
    stats_div.innerHTML = '<div class="col-sm-2">Stats:</div>' +
        '<div class="col-sm-3">Active Customers: ' + data['active_customers'] + '</div>' +
        '<div class="col-sm-3">Awaiting Payment: ' + data['need_pay'] + '</div>' +
        '<div class="col-sm-4">Awaiting Release: ' + data['need_release'] + '</div>';
    cart.showStartOver();
}

function add_shown() {
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
}

var emailAddreesRecipients = [];

// show the pay tab, and its current dataset, if first call, update artSales in the database.
function pay_shown() {
    if (pay_InitialCart) {
        pay_InitialCart = false;
        initArtSales();
    }
    cart.freeze();
    current_tab = pay_tab;
    cart.drawCart();

    var total_amount_due = cart.getTotalPrice() - cart.getTotalPaid();
    if (total_amount_due  < 0.01) { // allow for rounding error, no need to round here
        // nothing more to pay
        cart.showNext();
        cart.showRelease();
        cart.hideStartOver();
        cart.hideAdd();
        add_tab.disabled = true;
        if (pay_button_pay != null) {
            var rownum;
            pay_button_pay.hidden = true;
            pay_button_rcpt.hidden = false;
            var email_html = '';
            var email_addr = current_person['email_addr'];
            if (emailRegex.test(email_addr)) {
                email_html += '<div class="row"><div class="col-sm-1 pe-2"></div><div class="col-sm-8">' + email_addr + '</div></div>';
            }
            if (email_html.length > 2) {
                pay_button_ercpt.hidden = false;
                pay_button_ercpt.disabled = false;
                pay_button_ercpt.disabled = false;
                receeiptEmailAddresses_div.innerHTML = '<div class="row mt-2"><div class="col-sm-9 p-0">Email receipt to:</div></div>' + email_html;
                emailAddreesRecipients.push(current_person['email_addr']);
            }
            document.getElementById('pay-amt').value='';
            document.getElementById('pay-desc').value='';
            document.getElementById('pay-amt-due').innerHTML = '';
            document.getElementById('pay-check-div').hidden = true;
            document.getElementById('pay-ccauth-div').hidden = true;
            cart.hideAdd();
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
        var pay_html = `
<div id='payBody' class="container-fluid form-floating">
  <form id='payForm' action='javascript: return false; ' class="form-floating">
    <div class="row pb-2">
        <div class="col-sm-auto ms-0 me-2 p-0">New Payment Transaction ID: ` + pay_tid + `</div>
    </div>
    `;

    // add prior discounts to screen if any
    pay_html += `
    <div class="row mt-1">
        <div class="col-sm-2 ms-0 me-2 p-0">Amount Due:</div>
        <div class="col-sm-auto m-0 p-0 ms-0 me-2 p-0" id="pay-amt-due">$` + Number(total_amount_due).toFixed(2) + `</div>
    </div>
    <div class="row mt-2">
        <div class="col-sm-2 ms-0 me-2 p-0">Amount Paid:</div>
        <div class="col-sm-auto m-0 p-0 ms-0 me-2 p-0"><input type="number" inputmode="numeric" class="no-spinners" id="pay-amt" name="paid-amt" style="width: 7em;"/></div>
    </div>
    <div class="row">
        <div class="col-sm-2 m-0 mt-2 me-2 mb-2 p-0">Payment Type:</div>
        <div class="col-sm-auto m-0 mt-2 p-0 ms-0 me-2 mb-2 p-0" id="pt-div">
            <input type="radio" id="pt-credit" name="payment_type" value="credit" onchange='setPayType("credit");'/>
            <label for="pt-credit">Credit Card</label>
            <input type="radio" id="pt-check" name="payment_type" value="check" onchange='setPayType("check");'/>
            <label for="pt-check">Check</label>
            <input type="radio" id="pt-cash" name="payment_type" value="cash" onchange='setPayType("cash");'/>
            <label for="pt-cash">Cash</label>
`;
        if (discount_mode != "none") {
            if (discount_mode == 'any' || (discount_mode == 'manager' && hasManager) || (discount_mode == 'active' && hasManager && base_manager_enabled)) {
                pay_html += `
            <input type="radio" id="pt-discount" name="payment_type" value="discount" onchange='setPayType("discount");'/>
            <label for="pt-discount">Discount</label>
`;
            }
        }
        pay_html += `
        </div>
    </div>
    <div class="row mb-2" id="pay-check-div" hidden>
        <div class="col-sm-2 ms-0 me-2 p-0">Check Number:</div>
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
    <div id="receeiptEmailAddresses" class="container-fluid"></div>
  </form>
    <div class="row mt-4">
        <div class="col-sm-12 p-0" id="pay_status"></div>
    </div>
</div>
`;

        pay_div.innerHTML = pay_html;
        pay_button_pay = document.getElementById('pay-btn-pay');
        pay_button_rcpt = document.getElementById('pay-btn-rcpt');
        pay_button_ercpt = document.getElementById('pay-btn-ercpt');
        receeiptEmailAddresses_div = document.getElementById('receeiptEmailAddresses');
        if (receeiptEmailAddresses_div)
            receeiptEmailAddresses_div.innerHTML = '';
        if (cart.getPmtLength() > 0) {
            cart.hideStartOver();
        } else {
            cart.showAdd();
            cart.showStartOver();
        }
    }
}

// release_shown - show the release tab
function release_shown() {
    current_tab = release_tab;
    pay_tab.disabled = true;
    cart.hideAdd();
    cart.showNext();
    cart.hideStartOver();
    clear_message();

    // search for matching names
    var postData = {
        ajax_request_action: 'findRelease',
        perid: current_person['id'],
    };
    $.ajax({
        method: "POST",
        url: "scripts/artpos_findRelease.php",
        data: postData,
        success: function (data, textstatus, jqxhr) {
            if (data['error'] !== undefined) {
                show_message(data['error'], 'error');
                return;
            }
            if (data['message'] !== undefined) {
                show_message(data['message'], 'success');
            }
            if (data['warn'] !== undefined) {
                show_message(data['warn'], 'warn');
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
    releaseTitleDiv.innerHTML = 'Check Artwork Purchased by ' + (current_person['first_name'] + ' ' + current_person['last_name']).trim();

    if (releaseTable != null) {
        releaseTable.destroy();
        releaseTable = null;
    }

    releaseTable = new Tabulator('#ReleaseArtBody', {
        maxHeight: "600px",
        data: data['art'],
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
        data: { art: JSON.stringify(data), perid: current_person['id'], user_id: user_id, },
        success: function (data, textstatus, jqxhr) {
            if (data['error'] !== undefined) {
                show_message(data['error'], 'error');
                return;
            }
            if (data['warn'] !== undefined) {
                show_message(data['warn'], 'warn');
            }
            if (data['message'] !== undefined) {
                show_message(data['message'], 'success');
            }
            if (data['num_remain'] > 0) {
                if (confirm(data['num_remain'] + ' items are still not released, return to release?'))
                    release_shown();
            } else {
                cart.hideRelease();
                start_over();
            }
        },
        error: showAjaxError
    });
}
