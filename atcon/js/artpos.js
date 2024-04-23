// cart object
var cart = null;

// tab fields
var find_tab = null;
var pay_tab = null;
var current_tab = null;

// find people fields
var id_div = null;
var pattern_field = null;
var find_result_table = null;
var number_search = null;
var find_perid = null;
var name_search = '';

// add/edit person fields
var add_index_field = null;
var add_perid_field = null;
var add_memIndex_field = null;
var add_first_field = null;
var add_middle_field = null;
var add_last_field = null;
var add_legalName_field = null;
var add_suffix_field = null;
var add_addr1_field = null;
var add_addr2_field = null;
var add_city_field = null;
var add_state_field = null;
var add_postal_code_field = null;
var add_country_field = null;
var add_email_field = null;
var add_phone_field = null;
var add_badgename_field = null;
var add_contact_field = null;
var add_share_field = null;
var add_header = null;
var addnew_button = null;
var clearadd_button = null;
var add_results_table = null;
var add_results_div = null;
var add_mode = true;
var add_mem_select = null;
var add_mt_dataentry = `
    <select id='ae_mem_sel' name='age' style="width:300px;" tabindex='30'>
    </select>
`;
var add_edit_dirty_check = false;
var add_edit_initial_state = "";
var add_edit_current_state = "";
var add_edit_prior_tab = null;

// review items
var review_div = null;
var country_select = null;
var review_missing_items = 0;
var review_required_fields = ['first_name', 'last_name', 'email_addr', 'address_1', 'city', 'state', 'postal_code' ];
var review_prompt_fields = ['phone'];
var review_editable_fields = [
    'first_name', 'middle_name', 'last_name', 'suffix',
    'legalName',
    'badge_name',
    'email_addr', 'phone',
    'address_1',
    'address_2',
    'city', 'state', 'postal_code',
    'share_reg_ok', 'contact_ok'
];


// pay items
var pay_div = null;
var pay_button_pay = null;
var pay_button_rcpt = null;
var pay_button_ercpt = null;
var pay_button_print = null;
var pay_tid = null;
var discount_mode = 'none';
var num_coupons = 0;
var couponList = null;
var couponSelect = null;
var coupon = null;
var coupon_discount = Number(0).toFixed(2);
var cart_total = Number(0).toFixed(2);
var pay_prior_discount = null;
// print items
var print_div = null;
var printed_obj = null;

// Data Items
var unpaid_table = [];
var result_membership = [];
var result_perinfo = [];
var membership_select = null;
var add_perinfo = [];
var add_membership = [];
var new_perid = -1;
var memList = null;
var memListMap = null;
var catList = null;
var ageList = null;
var typeList = null;
var changeModal = null;
var cashChangeModal = null;

// notes items
var notes = null;
var notesIndex = null;
var notesType = null;
var notesPriorValue = null;

// global items
var conid = null;
var conlabel = null;
var user_id = 0;
var hasManager = false;
var isCashier = false;
var badgePrinterAvailable = false;
var receiptPrinterAvailable = false;
var non_primary_categories = ['add-on', 'addon', 'cancel'];
var upgradable_types = ['one-day', 'oneday', 'virtual'];

// filter criteria
var filt_excat = null; // array of exclude category
var filt_cat = null;  // array of categories to include
var filt_age = null;  // array of ages to include
var filt_type = null; // array of types to include
var filt_shortname_regexp = null; // regexp item;
var startdate = null;
var enddate = null;

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
    review_tab = document.getElementById("review-tab");
    pay_tab = document.getElementById("pay-tab");
    print_tab = document.getElementById("print-tab");

    // cart
    //cart = new regpos_cart();

    // find people
    pattern_field = document.getElementById("find_pattern");
    pattern_field.addEventListener('keyup', (e)=> { if (e.code === 'Enter') find_record('search'); });
    pattern_field.focus();
    id_div = document.getElementById("find_results");

    // pay items
    pay_div = document.getElementById('pay-div');

    // add events
    find_tab.addEventListener('shown.bs.tab', find_shown)
    pay_tab.addEventListener('shown.bs.tab', pay_shown)

    // notes items
    notes = new bootstrap.Modal(document.getElementById('Notes'), { focus: true, backldrop: 'static' });

    // change membership
    changeModal = new bootstrap.Modal(document.getElementById('Change'), { focus: true, backldrop: 'static' });

    // cash payment requires change
    cashChangeModal = new bootstrap.Modal(document.getElementById('CashChange'), { focus: true, backldrop: 'static' });

    bootstrap.Tab.getOrCreateInstance(find_tab).show();

    // load the initial data and the proceed to set up the rest of the system
    var postData = {
        ajax_request_action: 'loadInitialData',
        nopay: !isCashier,
    };
    $.ajax({
        method: "POST",
        url: "scripts/regpos_loadInitialData.php",
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

    // set starting stages of left and right windows
}


// void transaction - needs to be written to actually void out a transaction in progress
function void_trans() {
    var postData = {
        ajax_request_action: 'voidPayment',
        user_id: user_id,
        pay_tid: pay_tid,
        cart_membership: cart.getCartMembership(),
    };
    $("button[name='void_btn']").attr("disabled", true);
    $.ajax({
        method: "POST",
        url: "scripts/regpos_voidPayment.php",
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
            start_over(0);
        },
        error: function (jqXHR, textstatus, errorThrown) {
            $("button[name='void_btn']").attr("disabled", false);
            showAjaxError(jqXHR, textstatus, errorThrown);
        }
    });
}

// if no memberships or payments have been added to the database, this will reset for the next customer
function start_over(reset_all) {
    if (!confirm_discard_add_edit(false))
        return;

    if (!cart.confirmDiscardCartEntry(-1,false))
        return;

    if (reset_all > 0)
        clear_message();

    if (base_manager_enabled) {
        base_toggleManager();
    }
    // empty cart
    cart.startOver();
    if (isCashier) {
        find_unpaid_button.hidden = false;
    }
    // empty search strings and results
    pattern_field.value = "";
    if (find_result_table != null) {
        find_result_table.destroy();
        find_result_table = null;
    }
    id_div.innerHTML = "";
    unpaid_table = null;

    // reset data to call up
    result_perinfo = [];
    result_membership = [];
    emailAddreesRecipients = [];
    last_email_row = '';

    // reset tabs to initial values
    find_tab.disabled = false;
    add_tab.disabled = false;
    review_tab.disabled = true;
    pay_tab.disabled = true;
    print_tab.disabled = true;
    cart.hideNext();
    cart.hideVoid();
    pay_button_pay = null;
    pay_button_rcpt = null;
    pay_button_ercpt = null;
    receeiptEmailAddresses_div = null;
    pay_button_print = null;
    pay_tid = null;
    pay_prior_discount = null;

    // set tab to find-tab
    bootstrap.Tab.getOrCreateInstance(find_tab).show();
    pattern_field.focus();
}

// show the full perinfo record as a hover in the table
function build_record_hover(e, cell, onRendered) {
    var data = cell.getData();
    //console.log(data);
    var hover_text = 'Person id: ' + data['perid'] + '<br/>' +
        (data['first_name'] + ' ' + data['middle_name'] + ' ' + data['last_name']).trim() + '<br/>' +
        data['legalName'] + '<br/>';
        data['address_1'] + '<br/>';
    if (data['address_2'] != '') {
        hover_text += data['address_2'] + '<br/>';
    }
    hover_text += data['city'] + ', ' + data['state'] + ' ' + data['postal_code'] + '<br/>';
    if (data['country'] != '' && data['country'] != 'USA') {
        hover_text += data['country'] + '<br/>';
    }
    hover_text += 'Badge Name: ' + badge_name_default(data['badge_name'], data['first_name'], data['last_name']) + '<br/>' +
        'Email: ' + data['email_addr'] + '<br/>' + 'Phone: ' + data['phone'] + '<br/>' +
        'Active:' + data['active'] + ' Contact?:' + data['contact_ok'] + ' Share?:' + data['share_reg_ok'] + '<br/>' +
        'Membership: ' + data['reg_label'] + '<br/>';

    return hover_text;
}

// add search person/transaction from result_perinfo record to the cart
function add_to_cart(index, table) {
    var rt = null;
    var rm = null;
    var perid;
    var mrows;

    if (table == 'result') {
        rt = result_perinfo;
        rm = result_membership;
    }

    if (table == 'add') {
        rt = add_perinfo;
        rm = add_membership;
    }

    if (index >= 0) {
        if (rt[index]['banned'] == 'Y') {
            alert("Please ask " + (result_perinfo[index]['first_name'] + ' ' + rt[index]['last_name']).trim() +" to talk to the Registration Administrator, you cannot add them at this time.")
            return;
        }
        perid = rt[index]['perid'];
        if (cart.notinCart(perid)) {
            mrows = find_memberships_by_perid(rm, perid);
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

    if (table == 'result') {
        if (find_result_table !== null) {
            find_result_table.replaceData(result_perinfo);
        } else {
            draw_as_records();
        }
    }
    clear_message();
}

// remove person and all of their memberships from the cart
function remove_from_cart(perid) {
    cart.remove(perid);

    if (find_result_table !== null) {
        find_result_table.replaceData(result_perinfo);
    } else {
        draw_as_records();
    }
    clear_message();
}


// draw_record: find_record found rows from search.  Display them in the non table format used by transaction and perid search, or a single row match for string.
function draw_record(row, first) {
    var data = result_perinfo[row];
    var prim = find_primary_membership_by_perid(result_membership, data['perid']);
    var label = "No Membership";
    if (prim != null) {
        label = result_membership[prim]['label'];
    }
    var html = `
<div class="container-fluid">
    <div class="row mt-2">
        <div class="col-sm-3">`;
    if (first) {
        html += `<button class="btn btn-primary btn-sm" id="add_btn_all" onclick="add_to_cart(-` + number_search + `, 'result');">Add All Cart</button>`;
    }
    html += `</div>
        <div class="col-sm-5">`;
    if (cart.notinCart(data['perid'])) {
        if (data['banned'] == 'Y') {
            html += `
            <button class="btn btn-danger btn-sm" id="add_btn_1" onclick="add_to_cart(` + row + `, 'result');">B</button>`;
        } else {
            html += `
            <button class="btn btn-success btn-sm" id="add_btn_1" onclick="add_to_cart(` + row + `, 'result');">Add to Cart</button>`;
        }
    } else {
        html += `
            <i>In Cart</i>`
    }
    html += `</div>
        <div class="col-sm-2">`;
    if (data['open_notes'] != null && data['open_notes'].length > 0) {
        html += '<button type="button" class="btn btn-sm btn-info p-0" onclick="show_perinfo_notes(' + data['index'] + ', \'result\')">View Notes</button>';
    }
    html += `</div>
        <div class="col-sm-2">`;
    if (hasManager && base_manager_enabled) {
        html += '<button type="button" class="btn btn-sm btn-secondary p-0" onClick="edit_perinfo_notes(0, \'result\')">Edit Notes</button>';
    }

    html += `
            </div>
        </div>
        <div class="row">
            <div class="col-sm-3">` + 'Badge Name:' + `</div>
            <div class="col-sm-9">` + badge_name_default(data['badge_name'], data['first_name'], data['last_name']) + `</div>
        </div>
        <div class="row">
            <div class="col-sm-3">Name:</div>
            <div class="col-sm-9">` +
            data['first_name'] + ' ' + data['middle_name'] + ' ' + data['last_name'] + `
            </div>
        </div>  
        <div class="row">
            <div class="col-sm-3">Legal Name:</div>
            <div class="col-sm-9">` + data['legalName'] + `</div>
        </div>
        <div class="row">
            <div class="col-sm-3">Address:</div>
            <div class="col-sm-9">` + data['address_1'] + `</div>
        </div>
`;
    if (data['address_2'] != '') {
        html += `
    <div class="row">
        <div class="col-sm-3"></div>
        <div class="col-sm-9">` + data['address_2'] + `</div>
    </div>
`;
    }
    html += `
    <div class="row">
       <div class="col-sm-3"></div>
       <div class="col-sm-9">` + data['city'] + ', ' + data['state'] + ' ' + data['postal_code'] + `</div>
    </div>
`;
    if (data['country'] != '' && data['country'] != 'USA') {
        html += `
    <div class="row">
       <div class="col-sm-3"></div>
       <div class="col-sm-9">` + data['country'] + `</div>
    </div>
`;
    }
    html += `
    <div class="row">
       <div class="col-sm-3">Email Address:</div>
       <div class="col-sm-9">` + data['email_addr'] + `</div>
    </div>
    <div class="row">
       <div class="col-sm-3">Phone::</div>
       <div class="col-sm-9">` + data['phone'] + `</div>
    </div>
    <div class="row">
       <div class="col-sm-3"></div>
       <div class="col-sm-auto">Active: ` + data['active'] + `</div>
       <div class="col-sm-auto">Contact OK: ` + data['contact_ok'] + `</div>
       <div class="col-sm-auto">Share Reg: ` + data['share_reg_ok'] + `</div>
    </div>
    <div class="row">
       <div class="col-sm-3">Membership Type:</div>
       <div class="col-sm-9">` + label + `</div>
    </div>
`;
    return html;
}

// tabulator perinfo formatters:

// tabulator formatter for the add cart column, displays the "add" record and "trans" to add the transaction to the card as appropriate
// filters for ones already in the cart, and statuses that should not be allowed to be added to the cart
function addCartIcon(cell, formatterParams, onRendered) { //plain text value
    var tid;
    var html = '';
    var banned = cell.getRow().getData().banned;
    if (banned == undefined) {
        tid = Number(cell.getRow().getData().tid);
        html = '<button type="button" class="btn btn-sm btn-success p-0" style="--bs-btn-font-size: 75%;" onclick="add_unpaid(' + tid + ')">Pay</button > ';
        return html;
    }
    if (banned == 'Y') {
        return '<button type="button" class="btn btn-sm btn-danger pt-0 pb-0" style="--bs-btn-font-size: 75%;" onclick="add_to_cart(' +
            cell.getRow().getData().index + ', \'' + formatterParams['t'] + '\')">B</button>';
    } else if (cart.notinCart(cell.getRow().getData().perid)) {
        html = '<button type="button" class="btn btn-sm btn-success p-0" style="--bs-btn-font-size: 75%;" onclick="add_to_cart(' +
            cell.getRow().getData().index + ', \'' + formatterParams['t'] + '\')">Add</button>';
        tid = cell.getRow().getData().tid;
        if (tid != '' && tid !== undefined && tid !== null) {
            html += '&nbsp;<button type="button" class="btn btn-sm btn-success p-0" style="--bs-btn-font-size: 75%;" onclick="add_to_cart(' + (-tid) + ', \'' + formatterParams['t'] + '\')">Tran</button>';
        }
        return html;
    }
    return '<span style="font-size: 75%;">In Cart';
}

// tabulator formatter for the notes, displays the "O" (open)  and "E" (edit) note for this person
function perNotesIcons(cell, formatterParams, onRendered) { //plain text value
    var index = cell.getRow().getData().index;
    var open_notes = cell.getRow().getData().open_notes;
    var html = "";
    if (open_notes != null && open_notes.length > 0 && !(base_manager_enabled && hasManager)) {
        html += '<button type="button" class="btn btn-sm btn-info p-0" style="--bs-btn-font-size: 75%;"  onclick="show_perinfo_notes(' + index + ', \'' + formatterParams['t'] + '\')">O</button>';
    }
    if (hasManager && base_manager_enabled) {
        var btnclass = "btn-secondary";
        if (open_notes != null && open_notes.length > 0)
            btnclass = "btn-info";
        html += ' <button type="button" class="btn btn-sm ' + btnclass + ' p-0" style="--bs-btn-font-size: 75%;" onclick="edit_perinfo_notes(' + index + ', \'' + formatterParams['t'] + '\')">E</button>';
    }
    if (html == "")
        html = "&nbsp;"; // blank draws nothing
    return html;
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

// add selected membership as a new item in the card under this perid.
function add_membership_cart(rownum, selectname) {
    var select = document.getElementById(selectname);
    var membership = find_memLabel(select.value.trim());
    cart.addMembership(rownum, membership);
}

// search the online database for a set of records matching the criteria
// find_type: empty: search for memberships
//              unpaid: return all unpaid
//  possible meanings of find_pattern
//      numeric: search for tid or perid matches
//      alphanumeric: search for names in name, badge_name, email_address fields
//
function find_person(find_type) {
    if (find_result_table != null) {
        find_result_table.destroy();
        find_result_table = null;
    }
    id_div.innerHTML = "";
    clear_message();
    name_search = pattern_field.value.toLowerCase().trim();
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
        alert("Found Person!");
        console.log(data['person']);
        return;
    } else { // I'm not sure how we'd get here
        show_message(data['num_rows'] + " found.  Multiple people not yet supported.");
        return;
    }

    // string search, returning more than one row show tabulator table
    if (isNaN(name_search) && result_perinfo.length > 1)  {
        // table
        find_result_table = new Tabulator('#find_results', {
            maxHeight: "600px",
            data: result_perinfo,
            layout: "fitColumns",
            initialSort: [
                {column: "fullname", dir: "asc"},
            ],
            columns: [
                {field: "perid", visible: false,},
                {field: "index", visible: false, },
                {title: "Name", field: "fullname", headerFilter: true, headerWordWrap: true, tooltip: build_record_hover,},
                {field: "last_name", visible: false,},
                {field: "first_name", visible: false,},
                {field: "middle_name", visible: false,},
                {field: "suffix", visible: false,},
                {field: "legalName", visible: false,},
                {title: "Badge Name", field: "badge_name", headerFilter: true, headerWordWrap: true, tooltip: true,},
                {title: "Zip", field: "postal_code", headerFilter: true, headerWordWrap: true, tooltip: true, maxWidth: 70, width: 70},
                {title: "Email Address", field: "email_addr", headerFilter: true, headerWordWrap: true, tooltip: true,},
                {title: "Reg", field: "reg_label", headerFilter: true, headerWordWrap: true, tooltip: true, maxWidth: 120, width: 120,},
                {title: "Note",width: 45, headerSort: false, headerFilter: false, formatter: perNotesIcons, formatterParams: {t:"result"}, },
                {title: "Cart", width: 90, headerFilter: false, headerSort: false, formatter: addCartIcon, formatterParams: {t:"result"},},
                {field: "index", visible: false,},
            ],
        });
    } else if (result_perinfo.length > 0) {  // one row string, or all perinfo/tid searches, display in record format
        if ((!isNaN(name_search)) && regtids.length == 1 && (attach_count > 0 || print_count > 0)) {
            // only 1 transaction returned and it was search by number, and it's been attached for payment before
            // add it to the cart and go to payment
            for (row in result_membership) {
                if ((result_membership[row]['tid'] == tid) || (result_membership[row]['rstid']==name_search)) {
                    index = result_membership[row]['pindex'];
                    add_to_cart(index, 'result');
                }
            }
            added_payable_trans_to_cart();
            return;
        }
        number_search = Number(name_search);
        draw_as_records();
        return;
    }
    // no rows show the diagnostic
    id_div.innerHTML = `"container-fluid">
    <div class="row mt-3">
        <div class="col-sm-4">No matching records found</div>
        <div class="col-sm-auto"><button class="btn btn-primary btn-sm" type="button" id="not_found_add_new" onclick="not_found_add_new();">Add New Person</button>
        </div>
    </div>
</div>
`;
    id_div.innerHTML = id_div.innerHTML = 'No matching records found'
}

function draw_as_records() {
    var html = '';
    var first = false;
    var row;
    if (result_perinfo.length > 1) {
        first = true;
    }
    for (row in result_perinfo) {
        html += draw_record(row, first);
        first = false;
    }
    html += '</div>';
    id_div.innerHTML = html;
}
// when searching, if clicking on the add new button, switch to the add/edit tab
function not_found_add_new() {
    id_div.innerHTML = '';
    pattern_field.value = '';

    bootstrap.Tab.getOrCreateInstance(add_tab).show();
}

// switch to the review tab when the review button is clicked
function start_review() {
    if (!confirm_discard_add_edit(false))
        return;
    cart.hideNoChanges();

    // set tab to review-tab
    bootstrap.Tab.getOrCreateInstance(review_tab).show();
    review_tab.disabled = false;  
}

// create the review data screen from the cart
function review_update() {
    cart.updateReviewData();
    review_shown();
    if (review_missing_items > 0) {
        setTimeout(review_nochanges, 100);
    } else {
        review_nochanges();
    }
}

function added_payable_trans_to_cart() {
    // clear any search remains
    if (add_results_table != null) {
        add_results_table.destroy();
        add_results_table = null;
    }
    if (find_result_table != null) {
        find_result_table.destroy();
        find_result_table = null;
    }
    id_div.innerHTML = '';
    cart.showNoChanges();
}


// no changes button pressed:
// if everything is paid, go to print.  If cashier (has a find_unpaid button), to go Pay, else put up the diagnostic
//      to ask them to move on to the cashier.
function review_nochanges() {
    // first check to see if any required fields still exist
    if (review_missing_items > 0) {
        if (!confirm("Proceed ignoring check for " + review_missing_items.toString() + " missing data items (shown in yellow)?")) {
            return false; // confirm answered no, return not safe to discard
        }
    }

    cart.hideNoChanges();
    // submit the current card data to update the database, retrieve all TID's/PERID's/REGID's of inserted data
    var postData = {
        ajax_request_action: 'updateCartElements',
        cart_perinfo: cart.getCartPerinfo(),
        cart_perinfo_map: cart.getCartMap(),
        cart_membership: cart.getCartMembership(),
        user_id: user_id,
    };
    $.ajax({
        method: "POST",
        url: "scripts/regpos_updateCartElements.php",
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
            reviewed_update_cart(data);
        },
        error: showAjaxError,
    });
}

// reviewed_update_cart:
//  all the data from the cart has been updated in the database, now apply the id's and proceed to the next step
function reviewed_update_cart(data) {
    pay_tid = data['master_tid'];
    // update cart elements
    var unpaid_rows = cart.updateFromDB(data);

    // set tab to review-tab
    if (unpaid_rows == 0) {
        goto_print();
        return;
    }

    // Once saved, move them to next step
    if (isCashier) {
        bootstrap.Tab.getOrCreateInstance(pay_tab).show();
    } else {
        cart.showNext();
        cart.hideStartOver();
        cart.freeze();
        var el = document.getElementById('review-btn-update');
        if (el)
            el.hidden = true;
        el = document.getElementById('review-btn-nochanges');
        if (el)
            el.hidden = true;
        el = document.getElementById('review_status');
        if (el)
            el.innerHTML = "Completed: Send customer to cashier with id of " + pay_tid;
    }
}

// change tab to the print screen
function goto_print() {  
    printed_obj = null;
    bootstrap.Tab.getOrCreateInstance(print_tab).show();    
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
    var total_amount_due = cart.getTotalPrice() - (cart.getTotalPaid() + Number(coupon_discount));

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
        coupon: prow['coupon'],
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

// add_badge_to_print:
//      create the parameters for a single badge
//
function add_badge_to_print(index) {
    return cart.getBadge(index);
}
// Send one or all of the badges to the printer
function print_badge(index) {
    var rownum = 0;
    var cartlen = cart.getCartLength();

    var params = [];
    var badges = [];
    if (index >= 0) {
        params.push(add_badge_to_print(index));
        badges.push(index);
    } else {
        while (rownum < cartlen) {
            params.push(add_badge_to_print(rownum));
            badges.push(rownum);
            rownum++;
        }
    }
    var postData = {
        ajax_request_action: 'printBadge',
        params: params,
        badges: badges,
    };
    $("button[name='print_btn']").attr("disabled", true);
    $.ajax({
        method: "POST",
        url: "scripts/regpos_printBadge.php",
        data: postData,
        success: function (data, textstatus, jqxhr) {
            if (data['error'] !== undefined) {
                show_message(data['error'], 'error');
                return;
            }
            PrintComplete(data);
        },
        error: function (jqXHR, textstatus, errorThrown) {
            $("button[name='print_btn']").attr("disabled", false);
            pay_button_pay.disabled = false;
            showAjaxError(jqXHR, textstatus, errorThrown);
        },
    });
}

function PrintComplete(data) {
    var badges = data['badges'];
    var regs = [];
    var index;
    for (index in badges) {
        if (printed_obj.get(index) == 0) {
            var rparams = cart.addToPrintCount(index);
            printed_obj.set(index, 1);
            regs.push({ regid: rparams[0], printcount: rparams[1]});
        }
    }
    if (regs.length > 0) {
        var postData = {
            ajax_request_action: 'updatePrintcount',
            regs: regs,
            user_id: user_id,
            tid: pay_tid,
        };
        $.ajax({
            method: "POST",
            url: "scripts/regpos_updatePrintcount.php",
            data: postData,
            success: function (data, textstatus, jqxhr) {
                if (data['error'] !== undefined) {
                    show_message(data['error'], 'error');
                    return;
                }
            },
            error: showAjaxError,
        });
    }
    $("button[name='print_btn']").attr("disabled", false);
    print_shown();
    show_message(data['message'], 'success');
}

// tab shown events - state mapping for which tab is shown
function find_shown() {
    cart.clearInReview();
    cart.unfreeze();
    current_tab = find_tab;
    cart.drawCart();
}

function add_shown() {
    cart.clearInReview();
    cart.unfreeze();
    current_tab = add_tab;
    clear_message();
    cart.drawCart();
}

function review_shown() {
    // draw review section
    current_tab = review_tab;
    review_div.innerHTML = cart.buildReviewData();
    cart.setInReview();
    cart.unfreeze();
    cart.setCountrySelect();
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

// apply_coupon - apply and compute the discount for a coupon, also show the rules for the coupon if applied
//  a = apply coupon from select
//  r = remove coupon
//  in any case need to re-show the pay tab with the details
function apply_coupon(cmd) {
    if (cmd == 'r') {
        var curCoupon = coupon.getCouponId();
        cart.clearCoupon(curCoupon);
        coupon = null;
        coupon = new Coupon();
        coupon_discount = Number(0).toFixed(2);
        pay_shown();
        return;
    }
    if (cmd == 'a') {
        var couponId = document.getElementById("pay_couponSelect").value;
        coupon = null;
        coupon = new Coupon();
        if (couponId == '') {
            show_message("Coupon cleared, no coupon applied", 'success');
            return;
        }
        coupon.LoadCoupon(couponId);
    }
    return;
}

function pay_shown() {
    if (!isCashier) {
        show_message("You do not have permission to handle payments", "warning");
        return;
    }
    cart.clearInReview();
    cart.freeze();
    current_tab = pay_tab;
    cart.drawCart();

    if (pay_prior_discount === null) {
        pay_prior_discount = cart.getPriorDiscount();
    }

    var total_amount_due = cart.getTotalPrice() - (cart.getTotalPaid() + pay_prior_discount + Number(coupon_discount));
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
            cart.hideVoid();
        } else {
            goto_print();
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
    if (num_coupons > 0 && !cart.priorCouponInCart()) { // cannot apply a coupon if one was already in the cart (and of course, there need to be valid coupons right now)
        if (!coupon.isCouponActive()) { // no coupon applied yet
            pay_html += `
    <div class="row mt-3">
        <div class="col-sm-2 ms-0 me-2 p-0">Coupon:</div>
        <div class="col-sm-auto ms-0 me-2 p-0">
` + couponSelect + `
        </div>
        <div class="col-sm-auto ms-0 me-0 p-0">
            <button class="btn btn-secondary btn-sm" type="button" id="pay-btn-coupon" onclick="apply_coupon('a');">Apply Coupon</button>
        </div>  
    </div>
`;
        } else {
            // now display the amount due
            pay_html += `
    <div class="row mt-1">
        <div class="col-sm-2 ms-0 me-2 p-0">Coupon:</div>
        <div class="col-sm-auto ms-0 me-2 p-0">` + coupon.getNameString() + `</div>
         <div class="col-sm-auto ms-0 me-0 p-0">
            <button class="btn btn-secondary btn-sm" type="button" id="pay-btn-coupon" onclick="apply_coupon('r');">Remove Coupon</button>
        </div>  
    </div>
    <div class="row mt-1">
        <div class="col-sm-1 ms-0 me-0">&nbsp;</div>
        <div class="col-sm-11 ms-0 me-0 p-0">` + coupon.couponDetails() + `</div>
    </div>
`;
        }
    }
    // add prior discounts to screen if any
    if (pay_prior_discount > 0) {
        pay_html += `
    <div class="row mt-2">
        <div class="col-sm-2 ms-0 me-2 p-0">Prior Discount:</div>
        <div class="col-sm-auto m-0 p-0 ms-0 me-2 p-0" id="pay-amt-due">$` + Number(pay_prior_discount).toFixed(2) + `</div>
    </div>
`;
    }
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
            cart.showVoid();
            cart.hideStartOver();
        } else {
            cart.hideVoid();
            cart.showStartOver();
        }
    }
}

function print_shown() {
    cart.clearInReview();
    find_tab.disabled = true;
    add_tab.disabled = true;
    review_tab.disabled = true;
    cart.hideStartOver();
    cart.showNext();
    cart.hideVoid();
    cart.freeze();
    current_tab = print_tab;
    var new_print = false;
    if (printed_obj == null) {
        new_print = true;
        printed_obj = new map();
    }
    cart.drawCart();

    // draw the print screen
    var print_html = `<div id='printBody' class="container-fluid form-floating">
`;
    if (badgePrinterAvailable === false) {
        print_html += 'No printer selected, unable to print badges.  </div>';
        print_div.innerHTML = print_html;
        return;
    }
    print_html += cart.printList(new_print);
    print_html += `
    <div class="row mt-4">
        <div class="col-sm-2 ms-0 me-2 p-0">&nbsp;</div>
        <div class="col-sm-auto ms-0 me-2 p-0">
            <button class="btn btn-primary btn-sm" type="button" id="pay-print-all" name="print_btn" onclick="print_badge(-1);">Print All</button>
        </div>
    </div>
    <div class="row mt-4">
        <div class="col-sm-12 m-0 mt-4 p-0" id="pt-status"></div>
    </div>
</div>`;

    print_div.innerHTML = print_html;
}
