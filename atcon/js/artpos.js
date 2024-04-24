// cart object
var cart = null;

// tab fields
var find_tab = null;
var pay_tab = null;
var current_tab = null;

// find person fields
var id_div = null;
var badgeid_field = null;
var current_person = null;


// pay items
var pay_div = null;
var pay_button_pay = null;
var pay_button_rcpt = null;
var pay_button_ercpt = null;
var pay_button_print = null;
var pay_tid = null;
var discount_mode = 'none';
var cart_total = Number(0).toFixed(2);
// print items
var print_div = null;
var printed_obj = null;

// Data Items
var unpaid_table = [];
var result_membership = [];
var result_perinfo = [];
var add_perinfo = [];
var cashChangeModal = null;

// global items
var conid = null;
var conlabel = null;
var user_id = 0;
var hasManager = false;
var badgePrinterAvailable = false;
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
    add_edit_prior_tab = add_tab;
    pay_tab = document.getElementById("pay-tab");

    // cart
    cart = new artpos_cart();

    // find people
    badgeid_field = document.getElementById("find_perid");
    badgeid_field.addEventListener('keyup', (e)=> { if (e.code === 'Enter') find_person('search'); });
    badgeid_field.focus();
    id_div = document.getElementById("find_results");

    // pay items
    pay_div = document.getElementById('pay-div');

    // add events
    find_tab.addEventListener('shown.bs.tab', find_shown)
    add_tab.addEventListener('shown.bs.tab', add_shown)
    pay_tab.addEventListener('shown.bs.tab', pay_shown)

    // cash payment requires change
    cashChangeModal = new bootstrap.Modal(document.getElementById('CashChange'), { focus: true, backldrop: 'static' });

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
    // map the memIds and labels for the pre-coded memberships.  Doing it now because it depends on what the database sends.
    // tables
    conlabel =  data['label'];
    conid = data['conid'];
    user_id = data['user_id']
    hasManager = data['hasManager'];
    receiptPrinterAvailable = data['receiptPrinter'] === true;
}

// if no memberships or payments have been added to the database, this will reset for the next customer
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
    cart.hideNext();
    cart.hideAdd();
    pay_button_pay = null;
    pay_button_rcpt = null;
    pay_button_ercpt = null;
    receeiptEmailAddresses_div = null;
    pay_button_print = null;
    pay_tid = null;

    // set tab to find-tab
    bootstrap.Tab.getOrCreateInstance(find_tab).show();
    badgeid_field.focus();
}

// switch to the add tab
function goto_add() {
    bootstrap.Tab.getOrCreateInstance(add_tab).show();
}

// switch to the pay tab
function goto_pay() {
    bootstrap.Tab.getOrCreateInstance(pay_tab).show();
}
// add search person/transaction from result_perinfo record to the cart
function add_to_cart(index, table) {
    var rt = result_perinfo;
    var perid;
    var mrows;

    if (index >= 0) {
        perid = rt[index]['perid'];
        if (cart.notinCart(perid)) {
            cart.add(rt[index], mrows)
        }
    } else {
        var row;
        index = -index;
        for (row in result_membership) {
            if (result_membership[row]['tid'] == index) {
                var prow = result_membership[row]['pindex'];
                perid = result_perinfo[prow]['perid'];
                if (result_perinfo[prow]['banned'] == 'Y') {
                    alert("Please ask " + (result_perinfo[prow]['first_name'] + ' ' + result_perinfo[prow]['last_name']).trim() + " to talk to the Registration Administrator, you cannot add them at this time.")
                    return;
                } else if (cart.notinCart(perid)) {
                    mrows = find_memberships_by_perid(result_membership, perid);
                    cart.add(result_perinfo[prow], mrows);
                }
            }
        }
    }
    clear_message();
}

// remove person and all of their memberships from the cart
function remove_from_cart(perid) {
    cart.remove(perid);
    clear_message();
}


// draw_person: find_person found someone.  Display their details
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
       <div class="col-sm-3">Phone::</div>
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

// display the note popup with the requested notes
function show_perinfo_notes(index, where) {
    var note = null;
    var fullname = null;
    notesType = null;

    if (where == 'cart') {
        note = cart.getPerinfoNote(index);
        fullname = cart.getFullName(index);
        notesType = 'PC';
    }
    if (where == 'result') {
        note = result_perinfo[index]['open_notes'];
        fullname = result_perinfo[index]['fullname'];
        notesType = 'PR';
    }
    if (where == 'add') {
        note = add_perinfo[index]['open_notes']
        fullname = add_perinfo[index]['fullname'];
        notesType = 'add';
    }

    if (notesType == null)
        return;

    notesIndex = index;

    notes.show();
    document.getElementById('NotesTitle').innerHTML = "Notes for " + fullname;
    document.getElementById('NotesBody').innerHTML = note.replace(/\n/g, '<br/>');
    var notes_btn = document.getElementById('close_note_button');
    notes_btn.innerHTML = "Close";
    notes_btn.disabled = false;
}
// edit_perinfo_notes: display in an editor the perinfo notes field
// only managers can edit the notes
function edit_perinfo_notes(index, where) {
    var note = null;
    var fullname = null;

    if (!hasManager || !base_manager_enabled)
        return;

    notesType = null;
    if (where == 'cart') {
        note = cart.getPerinfoNote(index);
        fullname = cart.getFullName(index);
        notesType = 'PC';
    }
    if (where == 'result') {
        note = result_perinfo[index]['open_notes'];
        fullname = result_perinfo[index]['fullname'];
        notesType = 'PR';
    }
    if (where == 'add') {
        note = add_perinfo[index]['open_notes']
        fullname = add_perinfo[index]['fullname'];
        notesType = 'add';
    }
    if (notesType == null)
        return;

    notesIndex = index;
    notesPriorValue = note;
    if (notesPriorValue === null) {
        notesPriorValue = '';
    }

    notes.show();
    document.getElementById('NotesTitle').innerHTML = "Editing Notes for " +fullname;
    document.getElementById('NotesBody').innerHTML = '<textarea name="perinfoNote" class="form-control" id="perinfoNote" cols=60 wrap="soft" style="height:400px;">' +
        notesPriorValue + "</textarea>";
    var notes_btn = document.getElementById('close_note_button');
    notes_btn.innerHTML = "Save and Close";
    notes_btn.disabled = false;
}

// show the registration element note, anyone can add a new note, so it needs a save and close button
function show_reg_note(index, count) {
    var bodyHTML = '';
    var note = cart.getRegNote(index);
    var fullname = cart.getRegFullName(index);
    var label = cart.getRegLabel(index);
    var newregnote = cart.getNewRegNote(index);

    notesType = 'RC';
    notesIndex = index;

    if (count > 0) {
        bodyHTML = note.replace(/\n/g, '<br/>');
    }
    bodyHTML += '<br/>&nbsp;<br/>Enter/Update new note:<br/><input type="text" name="new_reg_note" id="new_reg_note" maxLength=64 size=60>'

    notes.show();
    document.getElementById('NotesTitle').innerHTML = "Registration Notes for " + fullname + '<br/>Membership: ' + label;
    document.getElementById('NotesBody').innerHTML = bodyHTML;
    if (newregnote !== undefined) {
        document.getElementById('new_reg_note').value = newregnote;
    }
    var notes_btn = document.getElementById('close_note_button');
    notes_btn.innerHTML = "Save and Close";
    notes_btn.disabled = false;
}

// save_note
//  save and update the note based on type
function save_note() {
    if (document.getElementById('close_note_button').innerHTML == "Save and Close") {
        if (notesType == 'RC') {
            cart.setRegNote(notesIndex, document.getElementById("new_reg_note").value);
        }
        if (notesType == 'PC' && hasManager && base_manager_enabled) {
            cart.setPersonNote(notesIndex, document.getElementById("perinfoNote").value);
        }
        if (notesType == 'PR' && hasManager && base_manager_enabled) {
            var new_note = document.getElementById("perinfoNote").value;
            if (new_note != notesPriorValue) {
               result_perinfo[notesIndex]['open_notes'] = new_note;
                // search for matching names
                var postData = {
                    ajax_request_action: 'updatePerinfoNote',
                    perid: result_perinfo[notesIndex]['perid'],
                    notes: result_perinfo[notesIndex]['open_notes'],
                    user_id: user_id,
                };
                document.getElementById('close_note_button').disabled = true;
                $.ajax({
                    method: "POST",
                    url: "scripts/regpos_updatePerinfoNote.php",
                    data: postData,
                    success: function (data, textstatus, jqxhr) {
                        if (data['error'] !== undefined) {
                            show_message(data['error'], 'error');
                            document.getElementById('close_note_button').disabled = falser;
                            return;
                        }
                        if (data['message'] !== undefined) {
                            show_message(data['message'], 'success');
                        }
                        if (data['warn'] !== undefined) {
                            show_message(data['warn'], 'warn');
                        }
                    },
                    error: function (jqXHR, textstatus, errorThrown) {
                        document.getElementById('close_note_button').disabled = false;
                        showAjaxError(jqXHR, textstatus, errorThrown);
                    }
                });
            }
        }
    }
    notesType = null;
    notesIndex = null;
    notesPriorValue = null;
    notes.hide();
}

// select the row (tid) from the unpaid list and add it to the cart, switch to the payment tab (used by find unpaid)
// marks it as a tid (not perid) add by inverting it.  (add_to_cart will deal with the inversion)
function add_unpaid(tid) {
    add_to_cart(-Number(tid), 'result');
    // force a new transaction for the payment as the cashier is not the same as the check-in in this case.
    added_payable_trans_to_cart();
}

// find the person by badge id, in prep for loading any art already won by bid
function find_person(find_type) {
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
            found_person(data);
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
function found_person(data) {
    if(data['num_rows'] == 1) { // one person found
        current_person = data['person'];
        // put the person details in the cart, populate the cart with the art they have to check out
        draw_person();
        data['art'].forEach((artItem) => cart.add(artItem));
        find_tab.disabled = true;
        add_tab.disabled = false;
        cart.showAdd();
        if (cart.getCartLength() > 0) {
            pay_tab.disabled = false;
            cart.showPay();
        }
        return;
    } else { // I'm not sure how we'd get here
        show_message(data['num_rows'] + " found.  Multiple people not yet supported.");
        return;
    }
}

// when searching, if clicking on the add new button, switch to the add/edit tab
function not_found_add_new() {
    id_div.innerHTML = '';
    badgeid_field.value = '';

    bootstrap.Tab.getOrCreateInstance(add_tab).show();
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
    var postData = {
        ajax_request_action: 'processPayment',
        cart_membership: cart.getCartMembership(),
        new_payment: prow,
        change: crow,
        user_id: user_id,
        pay_tid: pay_tid,
    };
    pay_button_pay.disabled = true;
    $.ajax({
        method: "POST",
        url: "scripts/regpos_processPayment.php",
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
//  payment entered into the database correctly, update the payment cart and the memberships with the updated paid amounts
function updatedPayment(data) {
    cart.updatePmt(data);
    pay_shown();
}

var last_receipt_type = '';
// Create a receipt and send it to the receipt printer
function print_receipt(receipt_type) {
    last_receipt_type = receipt_type;

    // header text
    var header_text = cart.receiptHeader(user_id, pay_tid);
    // optional footer text
    var footer_text = '';
    // server side will print the receipt
    var postData = {
        ajax_request_action: 'printReceipt',
        header: header_text,
        prows: cart.getCartPerinfo(),
        mrows: cart.getCartMembership(),
        pmtrows: cart.getCartPmt(),
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
            url: "scripts/regpos_printReceipt.php",
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
}

function add_shown() {
    cart.unfreeze();
    current_tab = add_tab;
    clear_message();
    cart.drawCart();
    cart.showPay();
}

var emailAddreesRecipients = [];
var last_email_row = '';
function toggleRecipientEmail(row) {
    var emailCheckbox = document.getElementById('emailAddr_' + row.toString());
    var email_address = cart.getEmail(row);
    if (emailCheckbox.checked) {
        if (!emailAddreesRecipients.includes(email_address)) {
            emailAddreesRecipients.push(email_address);
        }
    } else {
        if (emailAddreesRecipients.includes(email_address)) {
            for (var index=0; index < emailAddreesRecipients.length; index++) {
                if (emailAddreesRecipients[index] == email_address)
                    emailAddreesRecipients.splice(index,  1);
            }
        }
    }
    pay_button_ercpt.disabled = emailAddreesRecipients.length == 0;
}

function checkbox_check() {
    var emailCheckbox = document.getElementById('emailAddr_' + last_email_row.toString());
    emailCheckbox.checked = true;
    pay_button_ercpt.hidden = false;
    pay_button_ercpt.disabled = false;
}


function pay_shown() {
    cart.freeze();
    current_tab = pay_tab;
    cart.drawCart();

    var total_amount_due = cart.getTotalPrice();
    if (total_amount_due  < 0.01) { // allow for rounding error, no need to round here
        // nothing more to pay       
        print_tab.disabled = false;
        cart.showNext();
        if (pay_button_pay != null) {
            var rownum;
            pay_button_pay.hidden = true;
            pay_button_rcpt.hidden = false;
            var email_html = '';
            var email_count = 0;
            last_email_row = -1;
            var cartlen = cart.getCartLength();
            rownum = 0;
            while (rownum < cartlen) {
                var email_addr = cart.getEmail(rownum);
                if (emailRegex.test(email_addr)) {
                    email_html += '<div class="row"><div class="col-sm-1 text-end pe-2"><input type="checkbox" id="emailAddr_' + rownum.toString() +
                        '" name="receiptEmailAddrList" onclick="toggleRecipientEmail(' + rownum.toString() + ')"/></div><div class="col-sm-8">' +
                        '<label for="emailAddr_' + rownum.toString() + '">' + email_addr + '</label></div></div>';
                    email_count++;
                    last_email_row = rownum;
                }
                rownum++;
            }
            if (email_html.length > 2) {
                pay_button_ercpt.hidden = false;
                pay_button_ercpt.disabled = false;
                receeiptEmailAddresses_div.innerHTML = '<div class="row mt-2"><div class="col-sm-9 p-0">Email receipt to:</div></div>' +
                    email_html;
                if (email_count == 1) {
                    emailAddreesRecipients.push(cart.getEmail(last_email_row));
                    setTimeout(checkbox_check, 100);
                }
            }
            pay_button_print.hidden = false;
            document.getElementById('pay-amt').value='';
            document.getElementById('pay-desc').value='';
            document.getElementById('pay-amt-due').innerHTML = '';
            document.getElementById('pay-check-div').hidden = true;
            document.getElementById('pay-ccauth-div').hidden = true;
            cart.hideAdd();
        } else {
            cart.showNext();
        }
    } else {
        if (pay_button_pay != null) {
            pay_button_pay.hidden = false;
            pay_button_rcpt.hidden = true;
            pay_button_ercpt.hidden = true;
            pay_button_ercpt.disabled = true;
            pay_button_print.hidden = true;
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
        <div class="col-sm-auto m-0 p-0 ms-0 me-2 p-0"><input type="number" class="no-spinners" id="pay-amt" name="paid-amt" size="6"/></div>
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
        <div class="col-sm-auto ms-0 me-2 p-0">
            <button class="btn btn-primary btn-sm" type="button" id="pay-btn-print" onclick="goto_print();" hidden>Print Badges</button>
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
        pay_button_print = document.getElementById('pay-btn-print');
        if (cart.getPmtLength() > 0) {
            cart.hideStartOver();
        } else {
            cart.showAdd();
            cart.showStartOver();
        }
    }
}
