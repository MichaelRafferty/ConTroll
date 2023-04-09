// cart fields
var void_button = null;
var startover_button = null;
var review_button = null;
var next_button = null;
var cart_div = null;
var in_review = false;
var freeze_cart = false;
var total_price = 0;
var total_paid = 0;

// cart items
var membership_select = null;
var upgrade_select = null;
var yearahead_select = null;
var addon_select = null;
var unpaid_rows = 0;
var num_rows = 0;
var membership_rows = 0;
var needmembership_rows = 0;
var cart_membership = [];
var cart_perinfo = [];
var cart_perinfo_map = {};

// tab fields
var find_tab = null;
var add_tab = null;
var review_tab = null;
var pay_tab = null;
var print_tab = null;
var current_tab = null;

// find people fields
var id_div = null;
var find_result_table = null;
var number_search = null;
var memLabel = null;
var find_unpaid_button = null;
var find_perid = null;

// add/edit person fields
var add_index_field = null;
var add_perid_field = null;
var add_memIndex_field = null;
var add_first_field = null;
var add_middle_field = null;
var add_last_field = null;
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
    <select id='ae_mem_sel' name='age' style="width:300px;" tabindex='15'>
    </select>
`;
var add_edit_dirty_check = false;
var add_edit_initial_state = "";
var add_edit_current_state = "";
var add_edit_prior_tab = null;

// review items
var review_div = null;
var country_select = null;

// pay items
var pay_div = null;
var pay_button_pay = null;
var pay_button_rcpt = null;
var pay_button_print = null;
var pay_tid = null;

// print items
var print_div = null;
var printed_obj = null;

// Data Items
var unpaid_table = [];
var cart_pmt = [];
var cart_perid = [];
var result_membership = [];
var result_perinfo = [];
var add_perinfo = [];
var add_membership = [];
var new_perid = -1;
var memList = null;
var memListMap = null;
var catList = null;
var ageList = null;
var typeList = null;
var changeModal = null;
var changeTitle = null;
var changeBody = null;
var cashChangeModal = null
var cashChangeTitle = null;
var cashChangeBody = null;

// notes items
var notes = null;
var notesTitle = null;
var notesBody = null;
var notesButton = null;
var notesIndex = null;
var notesType = null;
var notesLocation = null;
var notesPriorValue = null;

// global items
var conid = null;
var conlabel = null;
var user_id = 0;
var hasManager = false;
var badgePrinterAvailable = false;
var receiptPrinterAvailable = false;

// filter criteria
var filt_excat = null; // array of exclude category
var filt_cat = null;  // array of categories to include
var filt_age = null;  // array of ages to include
var filt_type = null; // array of types to include
var filt_shortname_regexp = null; // regexp item;
var startdate = null;
var enddate = null;

// initialization
// lookup all DOM elements
// ask to load mappimg tables
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
    cart_div = document.getElementById("cart");
    void_button = document.getElementById("void_btn");
    startover_button = document.getElementById("startover_btn");
    review_button = document.getElementById("review_btn");
    next_button = document.getElementById("next_btn");
    complete_button = document.getElementById("complete_btn");

    // find people
    pattern_field = document.getElementById("find_pattern");
    pattern_field.addEventListener('keyup', (e)=> { if (e.code === 'Enter') find_record('search'); });
    id_div = document.getElementById("find_results");
    find_unpaid_button = document.getElementById("find_unpaid_btn");

    // add/edit people
    add_index_field = document.getElementById("perinfo-index");
    add_perid_field = document.getElementById("perinfo-perid");
    add_memIndex_field = document.getElementById("membership-index");
    add_first_field = document.getElementById("fname");
    add_middle_field = document.getElementById("mname");
    add_last_field = document.getElementById("lname");
    add_suffix_field = document.getElementById("suffix");
    add_addr1_field = document.getElementById("addr");
    add_addr2_field = document.getElementById("addr2");
    add_city_field = document.getElementById("city");
    add_state_field = document.getElementById("state");
    add_postal_code_field = document.getElementById("zip");
    add_country_field = document.getElementById("country");
    add_email_field = document.getElementById("email");
    add_phone_field = document.getElementById("phone");
    add_badgename_field = document.getElementById("badgename");
    add_contact_field = document.getElementById("contact_ok");
    add_share_field = document.getElementById("share_reg_ok");
    add_header = document.getElementById("add_header");
    addnew_button = document.getElementById("addnew-btn");
    clearadd_button = document.getElementById("clearadd-btn");
    add_results_div = document.getElementById("add_results");
    add_mem_select = document.getElementById("ae_mem_select");
    add_edit_initial_state = $("#add-edit-form").serialize();
    window.addEventListener("beforeunload", check_all_unsaved);

        // review items
    review_div = document.getElementById('review-div');
    country_select = document.getElementById('country').innerHTML;

    // pay items
    pay_div = document.getElementById('pay-div');

    // print itmes
    print_div = document.getElementById('print-div');

    // add events
    find_tab.addEventListener('shown.bs.tab', find_shown)
    add_tab.addEventListener('shown.bs.tab', add_shown)
    review_tab.addEventListener('shown.bs.tab', review_shown)
    pay_tab.addEventListener('shown.bs.tab', pay_shown)
    print_tab.addEventListener('shown.bs.tab', print_shown)

    // notes items
    notes = new bootstrap.Modal(document.getElementById('Notes'), { focus: true, backldrop: 'static' });
    notesTitle = document.getElementById('NotesTitle');
    notesBody = document.getElementById('NotesBody');
    notesButton = document.getElementById('close_note_button');

    // change membership
    changeModal = new bootstrap.Modal(document.getElementById('Change'), { focus: true, backldrop: 'static' });
    changeTitle = document.getElementById("ChangeTitle");
    changeBody = document.getElementById("ChangeBody");

    // cash payment requires change
    cashChangeModal = new bootstrap.Modal(document.getElementById('CashChange'), { focus: true, backldrop: 'static' });
    cashChangeTitle = document.getElementById("CashChangeTitle");
    cashChangeBody = document.getElementById("CashChangeBody");

    bootstrap.Tab.getOrCreateInstance(find_tab).show();

    // load the initial data and the proceed to set up the rest of the system
    var postData = {
        ajax_request_action: 'loadInitialData',
        nopay: find_unpaid_button == null,
    };
    $.ajax({
        method: "POST",
        url: "scripts/regposTasks.php",
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
    // map the memIds and labels for the pre-coded memberships.  Doing it now because it depends on what the datbase sends.
    // tabls
    conlabel =  data['label'];
    conid = data['conid'];
    user_id = data['user_id']
    hasManager = data['hasManager'];
    badgePrinterAvailable = data['badgePrinter'] === true;
    receiptPrinterAvailable = data['receiptPrinter'] === true;
    startdate = data['startdate'];
    enddate = data['enddate'];
    memList = data['memLabels'];
    catList = data['memCategories'];
    ageList = data['ageList'];
    typeList = data['memTypes'];

    // build memListMap from memList
    memListMap = {};
    var index = 0;
    while (index < memList.length) {
        map_set(memListMap, memList[index]['id'], index);
        index++;
    }

    // build membership_select options
    filt_excat = ['upgrade', 'yearahead', 'add-on', 'addon'];
    filt_cat = null;
    filt_type = null;
    filt_age = null;
    filt_shortname_regexp = null;
    var match = memList.filter(mem_filter);
    membership_select = '';
    membership_selectlist = [];
    for (var row in match) {
        var option = '<option value="' + match[row]['id'] + '">' + match[row]['label'] + ", $" + match[row]['price'] + "</option>\n";
        membership_select += option;
        membership_selectlist.push({price:  match[row]['price'], option: option});
    }
    // upgrade_select
    filt_excat = null;
    filt_cat = new Array('upgrade')
    filt_shortname_regexp = null;
    match = memList.filter(mem_filter);
    upgrade_select = [];
    for (var row in match) {
        var label = match[row]['label'];
        day = label.replace(/.*upgrade +(...).*/i, '$1').toLowerCase();
        if (day.length > 3)
            day = (match[row]['label']).toLowerCase().substr(0, 3);
        upgrade_select[day] = '<option value="' + match[row]['id'] + '">' + match[row]['label'] + ", $" + match[row]['price'] + "</option>\n";
    }
    // yearahead_select
    filt_cat = new Array('yearahead')
    filt_shortname_regexp = null;
    match = memList.filter(mem_filter);
    yearahead_select = '';
    yearahead_selectlist = [];
    for (var row in match) {
        var option = '<option value="' + match[row]['id'] + '">' + match[row]['label'] + ", $" + match[row]['price'] + "</option>\n";
        yearahead_select += option;
        yearahead_selectlist.push({price:  match[row]['price'], option: option});
        }
    // addon_select
    filt_cat = ['addon', 'add-on']
    filt_shortname_regexp = null;
    match = memList.filter(mem_filter);
    addon_select = '';
    for (row in match) {
        addon_select += '<option value="' + match[row]['id'] + '">' + match[row]['label'] + ", $" + match[row]['price'] + "</option>\n";
    }

    // set up initial values
    result_perinfo = [];
    result_membership = [];

    // set starting stages of left and right windows
    clear_add();
    draw_cart();
}

// function map_access(obj, prop)
//      access the map (object) with the property vaslue prop
//   deals with difficult calling sequence to _map objects
function map_access(obj, prop) {
    return obj[prop];
}

// function map_set(obj, prop, value)
//      inverse of map_access, sets the value of the property
function map_set(obj, prop, value) {
    obj[prop] = value;
}
// make_copy(associative array)
// javascript passes by reference, can't slice an associative array, so you need to do a horrible JSON kludge
function make_copy(arr) {
    return JSON.parse(JSON.stringify(arr));  // horrible way to make an independent copy of an associative array
}

// search memLabel functions
// mem_filter - select specific rows from memList based on
//  filt_cat: memCategories to include
//  filt_type: memTypes to include
//  filt_age: ageList to include
//  filt_shortname_regexp: filter on shortname field
//  lastly, if it passes everything else filt_excat: anything except this list of memCategories
function mem_filter(cur, idx, arr) {
    if (cur['canSell'] == 0)
        return false;

    if (filt_cat != null) {
        if (!filt_cat.includes(cur['memCategory'].toLowerCase()))
            return false;
    }
    if (filt_type != null) {
        if (!filt_type.includes(cur['memType'].toLowerCase()))
            return false;
    }
    if (filt_age != null) {
        if (!filt_age.includes(cur['memAge'].toLowerCase()))
            return false;
    }
    if (filt_shortname_regexp != null) {
        if (!filt_shortname_regexp.test(cur['shortname']))
            return false;
    }
    if (filt_excat != null) {
        if (filt_excat.includes(cur['memCategory'].toLowerCase()))
            return false;
    }

    return true;
}

// map id to MemLabel entry
function find_memLabel(id) {
    var rownum = map_access(memListMap, id);
    if (rownum === undefined) {
        return null;
    }
    return memList[rownum];
}

// search result_membership functions
// filter to return a single perid from result_membership.filter
function rm_perid_filter(cur, idx, arr) {
    return cur['perid'] == find_perid;
}

// map perid to result_membership row
function find_memberships_by_perid(tbl, perid) {
    find_perid = perid;
    return tbl.filter(rm_perid_filter);
}

// badge_name_default: build a default badge name if its empty
function badge_name_default(badge_name, first_name, last_name) {
    if (badge_name === undefined | badge_name === null || badge_name === '') {
        var default_name = (first_name + ' ' + last_name).trim();
        return '<i>' + default_name.replace(/ +/, ' ') + '</i>';
    }
    return badge_name;
}
// given a perid, find it''s primary membership in the result_membership array
function find_primary_membership_by_perid(tbl, perid) {
    var regitems = find_memberships_by_perid(tbl, perid);
    var mem_index = null;
    for (var item in regitems) {
        var mi_row = find_memLabel(regitems[item]['memId']);
        if (mi_row['conid'] != conid)
            continue;

        memtype = mi_row['memCategory'];
        if (memtype == 'upgrade' || memtype == 'rollover' || memtype == 'freebie') {
            mem_index = regitems[item]['index'];
            break;
        }
        if (memtype == 'standard' || memtype == 'yearahead') {
            mem_index = regitems[item]['index'];
        }
    }
    return mem_index;
}

// void transaction - needs to be written to actually void out a transaction in progress
// TODO: write this
function void_trans() {
    start_over(0);
}

// if no memberships or payments have been added to the database, this will reset for the next customer
// TODO: add how to tell if it's allowed to be shown as enabled
function start_over(reset_all) {
    if (!confirm_discard_add_edit(false))
        return;

    if (!confirm_discard_cart_entry(-1,false))
        return;

    clear_message();
    // empty cart
    cart_membership = [];
    cart_perinfo = [];
    cart_perid = [];
    cart_pmt = [];
    freeze_cart = false;
    if (find_unpaid_button != null) {
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

    // reset tabs to initial values
    find_tab.disabled = false;
    add_tab.disabled = false;
    review_tab.disabled = true;
    pay_tab.disabled = true;
    print_tab.disabled = true;
    next_button.hidden = true;
    void_button.hidden = true;
    pay_button_pay = null;
    pay_button_rcpt = null;
    pay_button_print = null;
    in_review = false;
    pay_tid = null;

    clear_add();
    // set tab to find-tab
    bootstrap.Tab.getOrCreateInstance(find_tab).show();

    draw_cart();
}

// show the full perinfo record as a hover in the table
function build_record_hover(e, cell, onRendered) {
    var data = cell.getData();
    //console.log(data);
    var hover_text = (data['first_name'] + ' ' + data['middle_name'] + ' ' + data['last_name']).trim() + '<br/>' +
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
        if (map_access(cart_perinfo_map, rt[index]['perid']) === undefined) {
            var perid = rt[index]['perid'];
            cart_perinfo.push(make_copy(rt[index]));
            var mrows = find_memberships_by_perid(rm, perid);
            for (var mrownum in mrows) {
                cart_membership.push(make_copy(mrows[mrownum]));
            }
        }
    } else {
        var row;
        index = -index;
        for (row in result_membership) {
            if (result_membership[row]['tid'] == index) {
                var prow = result_membership[row]['pindex'];
                var perid = result_perinfo[prow]['perid'];
                if (result_perinfo[prow]['banned'] == 'Y') {
                    alert("Please ask " + (result_perinfo[prow]['first_name'] + ' ' + result_perinfo[prow]['last_name']).trim() + " to talk to the Registration Administrator, you cannot add them at this time.")
                    return;
                } else if (map_access(cart_perinfo_map, perid) === undefined) {
                    cart_perinfo.push(make_copy(result_perinfo[prow]));
                    map_set(cart_perinfo_map, perid, prow); // make a dummy entry to prevent adding it twice
                    var mrows = find_memberships_by_perid(result_membership, perid);
                    for (var mrownum in mrows) {
                        cart_membership.push(make_copy(mrows[mrownum]));
                    }
                }
            }
        }
    }

    draw_cart();
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
    if (!confirm_discard_add_edit(false))
        return;

    var index = map_access(cart_perinfo_map, perid);

    if (!confirm_discard_cart_entry(index, false))
        return;

    var mrows = find_memberships_by_perid(cart_membership, perid);
    // need to splice backwards so the indicies don't change
    var delrows = [];
    var splicerow = null;
    for (var mrownum in mrows) {
        splicerow = mrows[mrownum]['index'];
        delrows.push(Number(splicerow));
    }
    delrows = delrows.reverse();
    for (splicerow in delrows)
        cart_membership.splice(delrows[splicerow], 1);

    cart_perinfo.splice(index, 1);
    // splices loses me the index number for the cross-reference, so the cart needs renumbering
    draw_cart();
    if (find_result_table !== null) {
        find_result_table.replaceData(result_perinfo);
    } else {
        draw_as_records();
    }
    clear_message();
}

// remove single membership item from the cart (leaving other memberships and person information
function delete_membership(index) {
    if (cart_membership[index]['tid'] != '') {
        if (confirm("Confirm delete for " + cart_membership[index]['label'])) {
            cart_membership[index]['todelete'] = 1;
            cart_perinfo[cart_membership['pindex']]['dirty'] = true;
        }
    } else {
        cart_membership.splice(index, 1);
    }
    draw_cart();
}

// change single membership item from the cart - only allow items of the same class with higher prices
// var changeRow = null;
function change_membership(index) {
    changeRow = index;
    mrow = cart_membership[index];
    prow = cart_perinfo[mrow['pindex']];
    changeTitle.innerHTML = "Change Membership Type for " + (prow['first_name'] + ' ' + prow['last_name']).trim();
    var html = '<div id="ChangePrior">Current Membership ' + mrow['label'] + "</div>\n";
    html += '<div id="ChangeTo">Change to:<br/><select name="change_membership_id" id="change_membership_id">' + "\n";
    // build select list here
    var optionrows = membership_selectlist;
    if (mrow['memCategory'] == 'yearahead'&& mrow['conid'] != conid)
        optionrows = yearahead_selectlist;
    var price = mrow['price'];
    for (var row in optionrows) {
        if (optionrows[row]['price'] >= price)
            html += optionrows[row]['option'];
    }

    html += "</select></div>\n";

    changeBody.innerHTML = html;
    changeModal.show();
}
// save_membership_change
// update saved cart row with new memId
function save_membership_change() {
    if (changeRow == null)
        return;

    mrow = cart_membership[changeRow];
    var newMemid = document.getElementById("change_membership_id").value;
    mrow['memId'] = newMemid;

    var mi_row = find_memLabel(newMemid);
    mrow['memCategory'] = mi_row['memCategory'];
    mrow['memType'] = mi_row['memType'];
    mrow['memAge'] = mi_row['memAge'];
    mrow['shortname'] = mi_row['shortname'];
    mrow['label'] = mi_row['label'];
    mrow['price'] = mi_row['price'];
    cart_perinfo_map[mrow['pindex']]['dirty'] = true;

    changeRow = null;
    changeModal.hide();
    draw_cart();
}


// cart_renumber:
// rebuild the indicies in the cart_perinfo and cart_membership tables
// for shoprt cut reasons indicies are used to allow usage of the filter functions built into javascript
// this rebuilds the index and perinfo cross reference maps.  It needs to be called whenever the number of items in cart is changed.
function cart_renumber() {
    var index;
    cart_perinfo_map = {};
    for (index = 0; index < cart_perinfo.length; index++) {
        cart_perinfo[index]['index'] = index;
        map_set(cart_perinfo_map, cart_perinfo[index]['perid'], index);
    }

    for (index = 0; index < cart_membership.length; index++) {
        cart_membership[index]['index'] = index;
        cart_membership[index]['pindex'] = map_access(cart_perinfo_map, cart_membership[index]['perid']);
    }
}

// common confirm add/edit screen dirty, if the tab isn't shown switch to it if direy
function confirm_discard_add_edit(silent) {
    if (!add_edit_dirty_check || freeze_cart) // don't check if dirty, or if the cart is frozed return ok to discard
        return true;

    add_edit_current_state = $("#add-edit-form").serialize();
    if (add_edit_initial_state == add_edit_current_state)
        return true; // no changes found

    if (silent)
        return false;

    // show the add/edit screen if it's hidden
    bootstrap.Tab.getOrCreateInstance(add_tab).show();

    if (!confirm("Discard current data in add/edit screen?")) {
        return false; // confirm answered no, return not safe to discard
    }

    return true;
}

function confirm_discard_cart_entry(index, silent) {
    if (freeze_cart) {
        return true;
    }

    var dirty = false;
    if (index >= 0) {
        dirty = cart_perinfo[index]['dirty'] === true;
    } else {
        for (var row in cart_perinfo) {
            dirty ||= cart_perinfo[row]['dirty'] === true;
        }
    }

    if (!dirty)
        return true;

    if (silent)
        return false;

    var msg = "Discard updated cart items?";
    if (index >= 0)
        msg = "Discard updated cart items for " + (cart_perinfo[index]['first_name'] + ' ' + cart_perinfo[index]['last_name']).trim();

    if (!confirm(msg)) {
        return false; // confirm answered no, return not safe to discard
    }

    return true;
}

// event handler for beforeunload event, prevents leaving with unsaved data
function check_all_unsaved(e) {
    // data editing checks
    if (!confirm_discard_add_edit(true))  {
        e.preventDefault();
        e.returnValue="You have unsaved member changes, leave anyway";
        return;
    }

    if (!confirm_discard_cart_entry(-1, true)) {
        e.preventDefault();
        e.returnValue="You have unsaved cart changes, leave anyway";
        return;
    }

    delete e['returnValue'];
    return;
}

// populate the add/edit screen from a cart item, and switch to add/edit
function edit_from_cart(perid) {
    if (!confirm_discard_add_edit(false))
            return;

    clear_add();
    var cartrow = cart_perinfo[map_access(cart_perinfo_map, perid)];

    // set perinfo values
    add_index_field.value = cartrow['index'];
    add_perid_field.value = cartrow['perid'];   
    add_memIndex_field.value = '';
    add_first_field.value = cartrow['first_name'];
    add_middle_field.value = cartrow['middle_name'];
    add_last_field.value = cartrow['last_name'];
    add_suffix_field.value = cartrow['suffix'];
    add_addr1_field.value = cartrow['address_1'];
    add_addr2_field.value = cartrow['address_2'];
    add_city_field.value = cartrow['city'];
    add_state_field.value = cartrow['state'];
    add_postal_code_field.value = cartrow['postal_code'];
    add_country_field.value = cartrow['country'];
    add_email_field.value = cartrow['email_addr'];
    add_phone_field.value = cartrow['phone'];
    add_badgename_field.value = cartrow['badge_name'];
    add_contact_field.value = cartrow['contact_ok'];
    add_share_field.value = cartrow['share_reg_ok'];

    // membership items - see if there is a membership item in the member list for this row
    var mem_index = find_primary_membership_by_perid(cart_membership, cartrow['perid']);
   
    if (mem_index == null) {
        // none found put in select
        add_mem_select.innerHTML = add_mt_dataentry;
        document.getElementById("ae_mem_sel").innerHTML = membership_select;
    } else {
        add_memIndex_field.value = mem_index;
        if (Number(cart_membership[mem_index]['price']) == Number(cart_membership[mem_index]['paid'])) {
            // already paid, just display the label
            add_mem_select.innerHTML = cart_membership[mem_index]['label'];
        } else {
            add_mem_select.innerHTML = add_mt_dataentry;
            var mtel = document.getElementById("ae_mem_sel");
            mtel.innerHTML = membership_select;
            mtel.value = cart_membership[mem_index]['memId'];
        }
    }
    // set page values
    add_header.innerHTML = `
<div class="col-sm-12 text-bg-primary mb-2">
        <div class="text-bg-primary m-2">
            Edit Person and Membership
        </div>
    </div>`;
    addnew_button.innerHTML = "Update to Cart";
    clearadd_button.innerHTML = "Discard Update";
    add_mode = false;
    add_edit_dirty_check = true;
    add_edit_initial_state = $("#add-edit-form").serialize();
    add_edit_current_state = "";
    add_edit_prior_tab = current_tab;
    bootstrap.Tab.getOrCreateInstance(add_tab).show();
}

// Clear the add/edit screen back to completely empty (startup)
function clear_add() {
    // first map the memId's for the existing'
    add_index_field.value = "";
    add_perid_field.value = "";
    add_first_field.value = "";
    add_middle_field.value = "";
    add_last_field.value = "";
    add_suffix_field.value = "";
    add_addr1_field.value = "";
    add_addr2_field.value = "";
    add_city_field.value = "";
    add_state_field.value = "";
    add_postal_code_field.value = "";
    add_country_field.value = "";
    add_email_field.value = "";
    add_phone_field.value = "";
    add_badgename_field.value = "";
    add_contact_field.value = 'Y';
    add_share_field.value = 'Y';
    add_country_field.value = 'USA';
    add_header.innerHTML = `
<div class="col-sm-12 text-bg-primary mb-2">
        <div class="text-bg-primary m-2">
            Add New Person and Membership
        </div>
    </div>`;
    add_first_field.style.backgroundColor = '';
    add_last_field.style.backgroundColor = '';
    add_addr1_field.style.backgroundColor = '';
    add_city_field.style.backgroundColor = '';
    add_state_field.style.backgroundColor = '';
    add_postal_code_field.style.backgroundColor = '';
    add_email_field.style.backgroundColor = '';
    add_mem_select.innerHTML = add_mt_dataentry;
    add_mem_select.style.backgroundColor = '';
    document.getElementById("ae_mem_sel").innerHTML = membership_select;
    if (add_results_table != null) {
        add_results_table.destroy();
        add_results_table = null;
        add_results_div.innerHTML = "";
    }
    add_mode = true;
    add_edit_dirty_check = true;
    add_edit_initial_state = $("#add-edit-form").serialize();;
    add_edit_current_state = "";
    clear_message();
    if (clearadd_button.innerHTML != 'Clear Add Person Form') {
        addnew_button.innerHTML = "Add to Cart";
        clearadd_button.innerHTML = 'Clear Add Person Form';
        // change back to the prior tab
        bootstrap.Tab.getOrCreateInstance(add_edit_prior_tab).show();
        add_edit_prior_tab = add_tab;
    }
}

// add record from the add/edit screen to the cart.  If it's already in the cart, update the cart record.
function add_new() {
    var edit_index = add_index_field.value.trim();    
    var edit_perid = add_perid_field.value.trim();
    var new_memindex = add_memIndex_field.value.trim();
    var new_first = add_first_field.value.trim();
    var new_middle = add_middle_field.value.trim();
    var new_last = add_last_field.value.trim();
    var new_suffix = add_suffix_field.value.trim();
    var new_addr1 = add_addr1_field.value.trim();
    var new_addr2 = add_addr2_field.value.trim();
    var new_city = add_city_field.value.trim();
    var new_state = add_state_field.value.trim();
    var new_postal_code = add_postal_code_field.value.trim();
    var new_country = add_country_field.value.trim();
    var new_email = add_email_field.value.trim();
    var new_phone = add_phone_field.value.trim();
    var new_badgename = add_badgename_field.value.trim();
    var bt_field = document.getElementById("ae_mem_sel");
    var new_badgememId = null;
    if (bt_field) {
        new_badgememId = bt_field.value.trim();
    }
    var new_contact = add_contact_field.value.trim();
    var new_share = add_share_field.value.trim();

    if (add_mode == false && edit_index != '') { // update perinfo/meminfo and cart_perinfo and cart_memberships
        var row = cart_perinfo[edit_index];
        row['first_name'] = new_first;
        row['middle_name'] = new_middle;
        row['last_name'] = new_last;
        row['suffix'] = new_suffix;
        row['badge_name'] = new_badgename;
        row['address_1'] = new_addr1;
        row['address_2'] = new_addr2;
        row['city'] = new_city;
        row['state'] = new_state;
        row['postal_code'] = new_postal_code;
        row['country'] = new_country;
        row['email_addr'] = new_email;
        row['phone'] = new_phone;
        row['share_reg_ok'] = new_share;
        row['contact_ok'] = new_contact;
        row['share_reg_ok'] = new_share;
        row['active'] = 'Y';
        row['dirty'] = true;
        if (new_badgememId != null) {
            var mrow = null;
            if (new_memindex != '') {
                mrow = cart_membership[new_memindex];
            } else {
                var ind = cart_membership.length;
                cart_membership.push({ index: ind, printcount: 0, tid: 0 });
                mrow = cart_membership[ind];
                mrow['perid'] = edit_perid;
                mrow['pindex'] = edit_index;
            }
            var mi_row = find_memLabel(new_badgememId);
            mrow['price'] = mi_row['price'];
            if (!('paid' in mrow)) {
                mrow['paid'] = 0;
            }
            if (!('tid' in mrow)) {
                mrow['tid'] = '';
            }
            mrow['memId'] = mi_row['id'];
            mrow['memCategory'] = mi_row['memCategory'];
            mrow['memType'] = mi_row['memType'];
            mrow['memAge'] = mi_row['memAge'];
            mrow['shortname'] = mi_row['shortname'];
            mrow['printcount'] = 0;
            mrow['label'] = mi_row['label'];
        }

        // clear the fields that should not be preserved between adds.  Allowing a second person to be added using most of the same data as default.
        add_first_field.value = "";
        add_middle_field.value = "";
        add_email_field.value = "";
        add_phone_field.value = "";
        add_badgename_field.value = "";
        add_index_field.value = "";
        add_perid_field.value = "";
        add_memIndex_field.value = "";
        add_header.innerHTML = `
<div class="col-sm-12 text-bg-primary mb-2">
        <div class="text-bg-primary m-2">
            Add New Person and Membership
        </div>
    </div>`;
        add_first_field.style.backgroundColor = '';
        add_last_field.style.backgroundColor = '';
        add_addr1_field.style.backgroundColor = '';
        add_city_field.style.backgroundColor = '';
        add_state_field.style.backgroundColor = '';
        add_postal_code_field.style.backgroundColor = '';
        add_email_field.style.backgroundColor = '';
        add_mem_select.innerHTML = add_mt_dataentry;
        add_mem_select.style.backgroundColor = '';
        add_mem_sel = document.getElementById("ae_mem_sel");
        add_mem_sel.innerHTML = membership_select;
        add_mem_sel.value = new_badgememId;
        if (add_results_table != null) {
            add_results_table.destroy();
            add_results_table = null;
            add_results_div.innerHTML = "";
        }
        addnew_button.innerHTML = "Add to Cart";
        clearadd_button.innerHTML = 'Clear Add Person Form';
        add_edit_dirty_check = true;
        add_edit_initial_state = $("#add-edit-form").serialize();
        add_edit_current_state = "";
        draw_cart();
        bootstrap.Tab.getOrCreateInstance(add_edit_prior_tab).show();
        add_edit_prior_tab = add_tab;
        return;
    }

    // we've searched this first/last name already and are displaying the table, so just go add the manually entered person
    if (add_results_table != null) {
        add_results_table.destroy();
        add_results_table = null;
        add_new_to_cart();
        return;
    }

    clear_message();
    var name_search = (new_first + ' ' + new_last).toLowerCase().trim();
    if (name_search == null || name_search == '') {
        show_message("First name or Last Name must be specified", "warn");
        return;
    }

    // look for matching records for this person being added to check for duplicates
    var postData = {
        ajax_request_action: 'findRecord',
        find_type: 'addnew',
        name_search: name_search,
    };
    $.ajax({
        method: "POST",
        url: "scripts/regposTasks.php",
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
            add_found(data);
        },
        error: showAjaxError,
    });
}

// add_found: all the tasks post search for matching records for adding a record to the cart
function add_found(data) {
// see if they already exist (if add to cart)
    add_perinfo = data['perinfo'];
    add_membership = data['membership'];
    var name_search = data['name_search'];
    
    if (add_perinfo.length > 0) {
        // find primary membership for each add_perinfo record
        for (rowindex in add_perinfo) {
            var row = add_perinfo[rowindex];
            var primmem = find_primary_membership_by_perid(add_membership, row['perid']);
            if (primmem != null) {
                row['reg_label'] = add_membership[primmem]['label'];
                var tid = add_membership[primmem]['tid'];
                if (tid != '') {
                    var other = false;
                    var mperid = row['perid'];
                    for (var mem in add_membership) {
                        if (add_membership[mem]['perid'] != mperid && add_membership[mem]['tid'] == tid) {
                            other = true;
                            break;
                        }
                    }
                    if (other) {
                        row['tid'] = tid;
                    }
                }
            } else {
                row['reg_label'] = 'No Membership';
                row['reg_tid'] = '';
            }
        }
        // table
        add_results_table = new Tabulator('#add_results', {
            maxHeight: "600px",
            data: add_perinfo,
            layout: "fitColumns",
            initialSort: [
                {column: "fullname", dir: "asc"},
                {column: "badge_name", dir: "asc"},
            ],
            columns: [
                {field: "perid", visible: false,},
                {title: "Name", field: "fullname", headerFilter: true, headerWordWrap: true, tooltip: build_record_hover,},
                {field: "last_name", visible: false,},
                {field: "first_name", visible: false,},
                {field: "middle_name", visible: false,},
                {field: "suffix", visible: false,},
                {title: "Badge Name", field: "badge_name", headerFilter: true, headerWordWrap: true, tooltip: true,},
                {title: "Zip", field: "postal_code", headerFilter: true, headerWordWrap: true, tooltip: true, maxWidth: 70, width: 70},
                {title: "Email Address", field: "email_addr", headerFilter: true, headerWordWrap: true, tooltip: true,},
                {title: "Reg", field: "reg_label", headerFilter: true, headerWordWrap: true, tooltip: true, maxWidth: 80, width: 80,},
                {title: "Note", width: 45, headerSort: false, headerFilter: false, formatter: perNotesIcons, formatterParams: {t:"add"}, },
                {title: "Cart", width: 80, headerFilter: false, headerSort: false, formatter: addCartIcon, formatterParams: {t:"add"},},
                {field: "index", visible: false,},
                {field: "open_notes", visible: false,},
            ],
        });
        addnew_button.innerHTML = "Add New";
        add_edit_initial_state = $("#add-edit-form").serialize();
        return;
    }
    add_new_to_cart();
}

// add_new_to_cart - not in system or operator said they are really new, add them to the cart
function add_new_to_cart() {
    var edit_index = add_index_field.value.trim();
    var edit_perid = add_perid_field.value.trim();
    var new_memindex = add_memIndex_field.value.trim();
    var new_first = add_first_field.value.trim();
    var new_middle = add_middle_field.value.trim();
    var new_last = add_last_field.value.trim();
    var new_suffix = add_suffix_field.value.trim();
    var new_addr1 = add_addr1_field.value.trim();
    var new_addr2 = add_addr2_field.value.trim();
    var new_city = add_city_field.value.trim();
    var new_state = add_state_field.value.trim();
    var new_postal_code = add_postal_code_field.value.trim();
    var new_country = add_country_field.value.trim();
    var new_email = add_email_field.value.trim();
    var new_phone = add_phone_field.value.trim();
    var new_badgename = add_badgename_field.value.trim();
    var bt_field = document.getElementById("ae_mem_sel");
    var new_badgememId = null;
    if (bt_field) {
        new_badgememId = bt_field.value.trim();
    }
    var new_contact = add_contact_field.value.trim();
    var new_share = add_share_field.value.trim();

    clear_message();
    // look for missing data
    // look for missing fields
    var missing_fields = 0;
    if (new_first == '') {
        missing_fields++;
        add_first_field.style.backgroundColor = 'var(--bs-warning)';
    } else {
        add_first_field.style.backgroundColor = '';
    }
    if (new_last == '') {
        missing_fields++;
        add_last_field.style.backgroundColor = 'var(--bs-warning)';
    } else {
        add_last_field.style.backgroundColor = '';
    }

    if (new_addr1 == '') {
        missing_fields++;
        add_addr1_field.style.backgroundColor = 'var(--bs-warning)';
    } else {
        add_addr1_field.style.backgroundColor = '';
    }

    if (new_city == '') {
        missing_fields++;
        add_city_field.style.backgroundColor = 'var(--bs-warning)';
    } else {
        add_city_field.style.backgroundColor = '';
    }

    if (new_state == '') {
        missing_fields++;
        add_state_field.style.backgroundColor = 'var(--bs-warning)';
    } else {
        add_state_field.style.backgroundColor = '';
    }

    if (new_postal_code == '') {
        missing_fields++;
        add_postal_code_field.style.backgroundColor = 'var(--bs-warning)';
    } else {
        add_postal_code_field.style.backgroundColor = '';
    }

    if (new_email == '') {
        missing_fields++;
        add_email_field.style.backgroundColor = 'var(--bs-warning)';
    } else {
        add_email_field.style.backgroundColor = '';
    }

    if (missing_fields > 0) {
        if (add_results_table != null) {
            add_results_table.destroy();
            add_results_table = null;
            add_results_div.includes = "";
            addnew_button.innerHTML = "Add to Cart";
        }
        add_header.innerHTML = `
<div class="col-sm-12 text-bg-warning mb-2">
        <div class="text-bg-warning m-2">
            Add New Person and Membership (* = Required Data)
        </div>
    </div>`;
        return;
    }

    var row = {
        perid: new_perid, first_name: new_first, middle_name: new_middle, last_name: new_last, suffix: new_suffix,
        badge_name: new_badgename,
        address_1: new_addr1, address_2: new_addr2, city: new_city, state: new_state, postal_code: new_postal_code,
        country: new_country, email_addr: new_email, phone: new_phone,
        share_reg_ok: 'Y', contact_ok:'Y', new_contact:'Y', active: 'Y', banned: 'N', index: cart_perinfo.length,

    };
    var memId = document.getElementById("ae_mem_sel").value;
    var mi_row = find_memLabel(memId);
    var mrow = {
        perid: new_perid, conid: mi_row['conid'],
        price: mi_row['price'], paid: 0, tid: '', index: cart_membership.length, printcount: 0,
        memCategory: mi_row['memCategory'], memType: mi_row['memType'], memAge: mi_row['memAge'],
        shortname: mi_row['shortname'], memId: memId, label: mi_row['label'], pindex: cart_perinfo.length,
    }
    new_perid--;

    add_first_field.value = "";
    add_middle_field.value = "";
    add_email_field.value = "";
    add_phone_field.value = "";
    add_badgename_field.value = "";
    cart_perinfo.push(make_copy(row));
    cart_membership.push(make_copy(mrow));

    draw_cart();
    if (add_results_table != null) {
        add_results_table.destroy();
        add_results_table = null;
        add_results_div.innerHTML = "";
        addnew_button.innerHTML = "Add to Cart";
    }
    add_header.innerHTML = `
<div class="col-sm-12 text-bg-primary mb-2">
        <div class="text-bg-primary m-2">
            Add New Person and Membership
        </div>
    </div>`;
    add_edit_dirty_check = true;
    add_edit_initial_state = $("#add-edit-form").serialize();
    add_edit_current_state = "";
}

// format all of the memberships for one record in the cart
function draw_cart_row(rownum) {
    var row = cart_perinfo[rownum];
    var membername = (row['first_name'] + ' ' + row['middle_name'] + ' ' + row['last_name']).trim();
    if (row['suffix'] != '') {
        membername += ', ' + row['suffix'];
    }
    var mrow;
    var rowlabel;
    var membership_found = false;
    var mem_is_membership = false;
    var membership_html = '';
    var rollover_html = '';
    var upgrade_html = '';
    var yearahead_html = '';
    var addon_html = '';
    var yearahead_eligible = false;
    var upgrade_eligible = false;
    var day = null;
    var col1 = '';
    var perid = row['perid'];

    // now loop over the memberships, sorting them by groups
    var mrows = find_memberships_by_perid(cart_membership, perid);
    for (var mrownum in mrows) {
        var mrow = mrows[mrownum];
        if (mrow['todelete'] !== undefined)
            continue;

        var category = mrow['memCategory'];
        if (category == 'yearahead' && mrow['conid'] == conid)
            category = 'standard'; // last years yearahead is this year's standard
        var memType = mrow['memType'];
        mem_is_membership = false;
        // col1 choices
        //  X = delete element from cart
        var allow_delete = mrow['regid'] <=  0;
        var allow_delete_mgr = hasManager && mrow['paid'] == 0 && mrow['printcount'] == 0;
        var allow_change_mgr = hasManager && mrow['regid'] >0 && mrow['paid'] >= 0 && mrow['printcount'] == 0 && (category == 'standard' || category == 'yearahead') && memType == 'full';
        col1 = '';
        if (allow_delete || allow_delete_mgr) {
            col1 += '<button type = "button" class="btn btn-small btn-secondary pt-0 pb-0 ps-1 pe-1 m-0" onclick = "delete_membership(' +
                mrow['index'] + ')" >X</button >';
        }
        // C = change membership type
        if (allow_change_mgr) {
            col1 += '<button type = "button" class="btn btn-small btn-warning pt-0 pb-0 ps-1 pe-1 m-0" onclick = "change_membership(' +
                mrow['index'] + ')" >C</button >';
        }

        var label = mrow['label'];
        if (!freeze_cart) {
            var notes_count = 0;
            if (mrow['reg_notes_count'] !== undefined && mrow['reg_notes_count'] !== null) {
                notes_count = Number(mrow['reg_notes_count']);
            }
            var btncolor = 'btn-info';
            if (mrow['new_reg_note'] !== undefined && mrow['new_reg_note'] !== '')
                btncolor = 'btn-warning';
            label += ' <button type = "button" class="btn btn-small ' + btncolor + ' pt-0 pb-0 ps-1 pe-1 m-0" onclick = " +show_reg_note(' +
                    mrow['index'] + ', ' + notes_count + ')" >N:' + notes_count.toString() +  '</button >';
            }

        switch (category) {
            case 'standard':
                yearahead_eligible = true;
                if (mrow['memType'] == 'oneday') {
                    upgrade_eligible = true;
                    day = (mrow['label']).toLowerCase().substr(0, 3);
                }
                // no break - fall through
            case 'freebie':
                mem_is_membership = true;
                membership_html += `
    <div class="row">
        <div class="col-sm-1 p-0">` + col1 + `</div>
        <div class="col-sm-7 p-0">` + label + `</div>
        <div class="col-sm-2 text-end">` + Number(mrow['price']).toFixed(2) + `</div>
        <div class="col-sm-2 text-end">` + Number(mrow['paid']).toFixed(2) + `</div>
    </div>
`;
                break;
            case 'upgrade':
                mem_is_membership = true;
                yearahead_eligible = true;
                upgrade_eligible = false;
                day = null;
                upgrade_html += `
    <div class="row">
        <div class="col-sm-1 p-0">` + col1 + `</div>
        <div class="col-sm-7 p-0">` + label + `</div>
        <div class="col-sm-2 text-end">` + Number(mrow['price']).toFixed(2) + `</div>
        <div class="col-sm-2 text-end">` + Number(mrow['paid']).toFixed(2) + `</div>
    </div>
`;
                break;
            case 'yearahead':
                yearahead_html += `
    <div class="row">
        <div class="col-sm-1 p-0">` + col1 + `</div>
        <div class="col-sm-7 p-0">` + label + `</div>
        <div class="col-sm-2 text-end">` + Number(mrow['price']).toFixed(2) + `</div>
        <div class="col-sm-2 text-end">` + Number(mrow['paid']).toFixed(2) + `</div>
    </div>
`;
                break;
            case 'rollover':
                membership_found = true;
                yearahead_eligible = true;
                rollover_html += `
    <div class="row">
        <div class="col-sm-1 p-0">` + col1 + `</div>
        <div class="col-sm-7 p-0">` + label + `</div>
        <div class="col-sm-2 text-end">` + Number(mrow['price']).toFixed(2) + `</div>
        <div class="col-sm-2 text-end">` + Number(mrow['paid']).toFixed(2) + `</div>
    </div>
`;
                break;
            case 'addon':
            case 'add-on':
                rowlabel = 'Addon:';
                addon_html += `
    <div class="row">
        <div class="col-sm-1 p-0">` + col1 + `</div>
        <div class="col-sm-7 p-0">` + label + `</div>
        <div class="col-sm-2 text-end">` + Number(mrow['price']).toFixed(2) + `</div>
        <div class="col-sm-2 text-end">` + Number(mrow['paid']).toFixed(2) + `</div>
    </div>
`;
                break;
        }

        total_price += Number(mrow['price']);
        total_paid += Number(mrow['paid']);
        if (mem_is_membership)
            membership_found = true;
        if (mrow['paid'] != mrow['price']) {
            unpaid_rows++;
        }
    }
    // first row - member name, remove button
    var rowhtml = '<div class="row">';
    if (membership_found) {
        rowhtml += '<div class="col-sm-8 text-bg-success">Member: '
    } else {
        rowhtml += '<div class="col-sm-8 text-bg-info">Non Member: '
    }
    rowhtml += membername + '</div>';
    if (!freeze_cart) {
        rowhtml += `
        <div class="col-sm-2 p-0 text-center"><button type="button" class="btn btn-small btn-secondary pt-0 pb-0 ps-1 pe-1" onclick="edit_from_cart(` + perid + `)">Edit</button></div>
        <div class="col-sm-2 p-0 text-center"><button type="button" class="btn btn-small btn-secondary pt-0 pb-0 ps-1 pe-1" onclick="remove_from_cart(` + perid + `)">Remove</button></div>
`;
    }
    rowhtml += '</div>'; // end of member name row

    // second row - badge name
    rowhtml += `
    <div class="row">
        <div class="col-sm-3 p-0">Badge Name:</div>
        <div class="col-sm-5 p-0">` + badge_name_default(row['badge_name'], row['first_name'], row['last_name']) + `</div>
        <div class="col-sm-2 p-0 text-center">`;
    if (!freeze_cart && row['open_notes'] != null && row['open_notes'].length > 0) {
        rowhtml += '<button type="button" class="btn btn-sm btn-info p-0" onclick="show_perinfo_notes(' + row['index'] + ', \'cart\')">View Notes</button>';
    }
    rowhtml += `</div>
        <div class="col-sm-2 p-0 text-center">`;
    if (hasManager && !freeze_cart) {
        var btncolor = 'btn-secondary';
        if (row['open_notes_pending'] !== undefined && row['open_notes_pending'] === 1)
            btncolor = 'btn-warning';
        rowhtml += '<button type="button" class="btn btn-sm ' + btncolor +  ' p-0" onclick="edit_perinfo_notes(' + row['index'] + ', \'cart\')">Edit Notes</button>';
    }
    rowhtml += `</div>
    </div>
`;  // end of second row - badge name

    if (rollover_html != '') {
        rowhtml += `<div class="row">
            <div class="col-sm-auto p-0">Rollover:</div>
</div>
` + rollover_html;
    }
    // reg items:
    //
    // membership rows

    if (rollover_html == '' || membership_html != '') {
        rowhtml += `<div class="row">
        <div class="col-sm-auto p-0">Memberships:</div>
</div>
`;
    }

    if (membership_html != '') {
        rowhtml += membership_html;
    }

    // if no base membership, create a pulldown row for it.
    // header row already output above before membership html was output
    if (!membership_found && !freeze_cart) {
        rowhtml += `<div class="row">
        <div class="col-sm-1 p-0">&nbsp;</div>
        <div class="col-sm-9 p-0"><select id="cart-madd-` + rownum + `" name="cart-addid">
` + membership_select + `
            </select>
        </div>
        <div class="col-sm-2 p-0 text-center"><button type="button" class="btn btn-small btn-info pt-0 pb-0 ps-1 pe-1" onclick="add_membership_cart(` + rownum + ", 'cart-madd-" + rownum + `')">Add</button>
        </div>
    </div>`;
    }

    // add in remainder of cart:
    if (upgrade_html != '') {
        rowhtml += `<div class="row">
            <div class="col-sm-auto p-0">Upgrade:</div>
</div>
` + upgrade_html;
    } else if (upgrade_eligible && !freeze_cart) {
        rowhtml += `<div class="row">
            <div class="col-sm-auto p-0">Upgrade:</div>
</div>
<div class="row">
        <div class="col-sm-1 p-0">&nbsp;</div>
        <div class="col-sm-9 p-0"><select id="cart-mupg-` + rownum + `" name="cart-addid">
`;
        // allow for mismatches to show the entire select, if matched, just use that onecol1
        if (day !== null && upgrade_select[day] !== undefined) {
            rowhtml += upgrade_select[day];
        } else {
            for (var upgrow in upgrade_select) {
                rowhtml += upgrade_select[upgrow];
            }
        }
        rowhtml += `
            </select>
        </div>
        <div class="col-sm-2 p-0 text-center"><button type="button" class="btn btn-small btn-secondary pt-0 pb-0 ps-1 pe-1" onclick="add_membership_cart(` + rownum + ", 'cart-mupg-" + rownum + `')">Add</button></div >
</div>
`;
    }

    if (yearahead_select != '') {
        if (yearahead_html != '') {
            rowhtml += `<div class="row">
            <div class="col-sm-auto p-0">Next Year:</div>
</div>
` + yearahead_html;
        } else if (yearahead_eligible && !freeze_cart) {
            rowhtml += `<div class="row">
            <div class="col-sm-auto p-0">Next Year:</div>
</div>
<div class="row">
        <div class="col-sm-1 p-0">&nbsp;</div>
        <div class="col-sm-9 p-0"><select id="cart-mya-` + rownum + `" name="cart-addid">
` + yearahead_select + `
            </select>
        </div>
        <div class="col-sm-2 p-0 text-center"><button type="button" class="btn btn-small btn-secondary pt-0 pb-0 ps-1 pe-1" onclick="add_membership_cart(` + rownum + ", 'cart-mya-" + rownum + `')">Add</button></div >
</div>
`;
        }
    }

    if (addon_select != '') {
        if (addon_html != '' || !freeze_cart) {
            rowhtml += `<div class="row">
            <div class="col-sm-auto p-0">Add Ons:</div>
</div>
` + addon_html;
        }
        if (!freeze_cart) {
            rowhtml += `
<div class="row">
        <div class="col-sm-1 p-0">&nbsp;</div>
        <div class="col-sm-9 p-0"><select id="cart-maddon-` + rownum + `" name="cart-addid">
` + addon_select + `
            </select>
        </div>
        <div class="col-sm-2 p-0 text-center"><button type="button" class="btn btn-small btn-secondary pt-0 pb-0 ps-1 pe-1" onclick="add_membership_cart(` + rownum + ", 'cart-maddon-" + rownum + `')">Add</button></div >
</div>
`;
        }
    }

    if (membership_found)
        membership_rows++
    else
        needmembership_rows++;

    return rowhtml;
}


// draw a payment row in the cart
function draw_cart_pmtrow(prow) {
 //   index: cart_pmt.length, amt: pay_amt, ccauth: ccauth, checkno: checkno, desc: eldesc.value, type: ptype,

    var pmt = cart_pmt[prow];
    var code = '';
    if (pmt['type'] == 'check') {
        code = pmt['checkno'];
    } else if (pmt['type'] == 'credit') {
        code = pmt['ccauth'];
    }
    return`<div class="row">
    <div class="col-sm-2 p-0">` + pmt['type'] + `</div>
    <div class="col-sm-6 p-0">` + pmt['desc'] + `</div>
    <div class="col-sm-2 p-0">` + code + `</div>
    <div class="col-sm-2 text-end">` + Number(pmt['amt']).toFixed(2) + `</div>
</div>
`;
}


// draw/update by redrawing the entire cart
function draw_cart() {
    cart_renumber(); // to keep indexing intact, renumber the index and pindex each time
    total_price = 0;
    total_paid = 0;
    num_rows = 0;
    membership_rows = 0;  
    needmembership_rows = 0;
    var html = `
<div class="container-fluid">
<div class="row">
    <div class="col-sm-8 text-bg-primary">Member</div>
    <div class="col-sm-2 text-bg-primary text-end">Price</div>
    <div class="col-sm-2 text-bg-primary text-end">Paid</div>
</div>
`;
    unpaid_rows = 0;
    for (rownum in cart_perinfo) {
        num_rows++;
        html += draw_cart_row(rownum);
    }
    html += `<div class="row">
    <div class="col-sm-8 p-0 text-end">Total:</div>
    <div class="col-sm-2 text-end">$` + Number(total_price).toFixed(2) + `</div>
    <div class="col-sm-2 text-end">$` + Number(total_paid).toFixed(2) + `</div>
</div>
`;
    total_price = Number(total_price.toFixed(2));
    total_paid = Number(total_paid.toFixed(2));
    if (cart_pmt.length > 0) {
        html += `
<div class="row mt-3">
    <div class="col-sm-8 text-bg-primary">Payment</div>
    <div class="col-sm-2 text-bg-primary">Code</div>
    <div class="col-sm-2 text-bg-primary text-end">Amount</div>
</div>
`;
        var total_pmt = 0;
        for (var prow in cart_pmt) {
            html += draw_cart_pmtrow(prow);
            total_pmt += Number(cart_pmt[prow]['amt']);
        }
        html += `<div class="row">
    <div class="col-sm-8 p-0 text-end">Payment Total:</div>`;
    total_pmt = Number(total_pmt.toFixed(2));
        html += `
    <div class="col-sm-4 text-end">$` + total_pmt.toFixed(2) + `</div>
</div>
`;
    }
    if (needmembership_rows > 0) {
        var person = needmembership_rows > 1 ? " people" : " person";
        var need = needmembership_rows > 1 ? "need memberships" : "needs a membership";
        html += `<div class="row mt-3">
    <div class="col-sm-12">Cannot proceed to "Review" because ` + needmembership_rows + person + " still " + need + `.  Use "Edit" button to add memberships for them or "Remove" button to take them out of the cart.
    </div>
`;
    } else if (num_rows > 0) {
        review_button.hidden = in_review;       
    }
    html += '</div>'; // ending the container fluid
    //console.log(html);
    cart_div.innerHTML = html;
    startover_button.hidden = num_rows == 0;
    if (needmembership_rows > 0 || (membership_rows == 0 && unpaid_rows == 0)) {
        review_tab.disabled = true;
        review_button.hidden = true;
    }
    if (freeze_cart) {
        review_tab.disabled = true;
        review_button.hidden = true;
        startover_button.hidden = true;
    }
    if (find_unpaid_button != null) {
        find_unpaid_button.hidden = num_rows > 0;
    }
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
        html += `<button class="btn btn-primary btn-small" id="add_btn_all" onclick="add_to_cart(-` + number_search + `, 'result');">Add All Cart</button>`;
    }
    html += `</div>
        <div class="col-sm-5">`;
    if (map_access(cart_perinfo_map, data['perid']) === undefined) {
        if (data['banned'] == 'Y') {
            html += `
            <button class="btn btn-danger btn-small" id="add_btn_1" onclick="add_to_cart(` + row + `, 'result');">B</button>`;
        } else {
            html += `
            <button class="btn btn-success btn-small" id="add_btn_1" onclick="add_to_cart(` + row + `, 'result');">Add to Cart</button>`;
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
    if (hasManager) {
        html += ' <span class="bg-warning pt-1 pb-1"><strong>Edit Notes</strong></span>';
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

// tabulator formatter for the add cart column, displays the "add" record and "trans" to add the tranaction to the card as appropriate
// filters for ones already in the cart, and statuses that should not be allowed to be added to the cart
function addCartIcon(cell, formatterParams, onRendered) { //plain text value
    var html = '';
    var banned = cell.getRow().getData().banned;
    if (banned == undefined) {
        var tid = Number(cell.getRow().getData().tid);
        html = '<button type="button" class="btn btn-sm btn-success p-0" onclick="add_unpaid(' + tid + ')">Pay</button > ';
        return html;
    }
    if (banned == 'Y') {
        return '<button type="button" class="btn btn-sm btn-danger pt-0 pb-0" onclick="add_to_cart(' +
            cell.getRow().getData().index + ', \'' + formatterParams['t'] + '\')">B</button>';
    } else if (map_access(cart_perinfo_map, cell.getRow().getData().perid) === undefined) {
        html = '<button type="button" class="btn btn-sm btn-success p-0" onclick="add_to_cart(' +
            cell.getRow().getData().index + ', \'' + formatterParams['t'] + '\')">Add</button>';
        var tid = cell.getRow().getData().tid;
        if (tid != '' && tid != undefined && tid != null) {
            html += '&nbsp;<button type="button" class="btn btn-sm btn-success p-0" onclick="add_to_cart(' + (-tid) + ', \'' + formatterParams['t'] + '\')">Tran</button>';
        }
        return html;
    }
    return '<span style="font-size: 75%;">In Cart';
}

// tabulator formatter for the notes, displays the "O" record and "A" notes for this person
function perNotesIcons(cell, formatterParams, onRendered) { //plain text value
    var index = cell.getRow().getData().index;
    var open_notes = cell.getRow().getData().open_notes;
    var html = "";
    if (open_notes != null && open_notes.length > 0) {
        html += '<button type="button" class="btn btn-sm btn-info p-0" onclick="show_perinfo_notes(' + index + ', \'' + formatterParams['t'] + '\')">O</button>';
    }
    if (hasManager) {
        html += ' <button type="button" class="btn btn-sm btn-secondary p-0" onclick="edit_perinfo_notes(' + index + ', \'' + formatterParams['t'] + '\')">E</button>';
    }
    if (html == "")
        html = "&nbsp;"; // blank draws nothing
    return html;
}

// display the note popup with the requested notes
function show_perinfo_notes(index, where) {
    notesLocation = null;
    if (where == 'cart') {
        notesLocation = cart_perinfo[index];
        notesType = 'PC';
    }
    if (where == 'result') {
        notesLocation = result_perinfo[index];
        notesType = 'PR';
    }
    if (where == 'add') {
        notesLocation = add_perinfo[index];
    }
    if (notesLocation == null)
        return;

    notesIndex = index;

    notesTitle.innerHTML = "Notes for " + notesLocation['fullname'];
    notesBody.innerHTML = notesLocation['open_notes'].replace(/\n/g, '<br/>');
    notesButton.innerHTML = "Close";
    notes.show();
}
// edit_perinfo_notes: display in an editor the perinfo notes field
// only managers can edit the notes
function edit_perinfo_notes(index, where) {
    if (!hasManager)
        return;
    notesLocation = null;
    if (where == 'cart') {
        notesLocation = cart_perinfo[index];
        notesType = 'PC';
    }
    if (where == 'result') {
        notesLocation = result_perinfo[index];
        notesType = 'PR';
    }
    if (where == 'add') {
        notesLocation = add_perinfo[index];
    }
    if (notesLocation == null)
        return;

    notesIndex = index;
    notesPriorValue = notesLocation['open_notes'];
    if (notesPriorValue === null) {
        notesPriorValue = '';
    }

    notesTitle.innerHTML = "Editing Notes for " + notesLocation['fullname'];
    notesBody.innerHTML = '<textarea name="perinfoNote" class="form-control" id="perinfoNote" cols=60 wrap="soft" style="height:400px;">' +
        notesPriorValue + "</textarea>";
    notesButton.innerHTML = "Save and Close";
    notes.show();
}

// show the registration element note, anyone can add a new note, so it needs a save and close button
function show_reg_note(index, count) {
    var bodyHTML = '';
    notesLocation = cart_membership[index];
    var prow = cart_perinfo[notesLocation['pindex']];

    notesType = 'RC';
    notesIndex = index;

    if (count > 0) {
        bodyHTML = notesLocation['reg_notes'].replace(/\n/g, '<br/>');
    }
    bodyHTML += '<br/>&nbsp;<br/>Enter/Update new note:<br/><input type="text" name="new_reg_note" id="new_reg_note" maxLength=64 size=60>'
    notesTitle.innerHTML = "Registration Notes for " + prow['fullname'] + '<br/>Membership: ' + notesLocation['label'];
    notesBody.innerHTML = bodyHTML;
    if (notesLocation['new_reg_note'] !== undefined) {
        document.getElementById('new_reg_note').value = notesLocation['new_reg_note'];
    }
    notesButton.innerHTML = "Save and Close";
    notes.show();
}

// save_note
//  save and update the note based on type
function save_note() {
    if (notesButton.innerHTML == "Save and Close") {
        if (notesType == 'RC') {
            notesLocation['new_reg_note'] = document.getElementById("new_reg_note").value;
            cart_perinfo[notesLocation['pindex']]['dirty'] = true;
            draw_cart();
        }
        if (notesType == 'PC' && hasManager) {
            notesLocation['open_notes'] = document.getElementById("perinfoNote").value;
            notesLocation['open_notes_pending'] = 1;
            notesLocation['dirty'] = true;
            draw_cart();
        }
        if (notesType == 'PR' && hasManager) {
            var new_note = document.getElementById("perinfoNote").value;
            if (new_note != notesPriorValue) {
                notesLocation['open_notes'] = new_note;
                // search for matching names
                var postData = {
                    ajax_request_action: 'updatePerinfoNote',
                    perid: notesLocation['perid'],
                    notes: notesLocation['open_notes'],
                    user_id: user_id,
                };
                $.ajax({
                    method: "POST",
                    url: "scripts/regposTasks.php",
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
                    },
                    error: showAjaxError,
                });
            }
        }
    }
    notesType = null;
    notesLocation = null;
    notesIndex = null;
    notesPriorValue = null;
    notes.hide();
}

// select the row (tid) from the unpaid list and add it to the cart, switch to the payment tab (used by find unpaid)
// marks it as a tid (not perid) add by inverting it.  (add_to_cart will deal with the inversion)
function add_unpaid(tid) {
    add_to_cart(-Number(tid), 'result');
    // force a new transaction for the payment as the cashier is not the same as the check-in in this case.
    review_nochanges();
}

// add selected membership as a new item in the card under this perid.
function add_membership_cart(rownum, selectname) {
    var select = document.getElementById(selectname);
    var membership = find_memLabel(select.value.trim());
    var row = cart_perinfo[rownum];
   
    cart_membership.push({
        perid: row['perid'],
        price: membership['price'],
        paid: 0,
        tid: 0,
        index: cart_membership.length,
        printcount: 0,
        conid: membership['conid'],
        memCategory: membership['memCategory'],
        memType: membership['memType'],
        memAge: membership['memAge'],
        shortname: membership['shortname'],
        pindex: row['index'],
        memId: membership['id'],
        label: membership['label'],
        regid: -1,
    });
  
    draw_cart();
}

// search the online database for a set of records matching the criteria
// find_type: empty: search for memberships
//              unpaid: return all unpaid
//  possible meanings of find_pattern
//      numeric: search for tid or perid matches
//      alphanumeric: search for names in name, badge_name, email_address fields
//
function find_record(find_type) {
    if (find_result_table != null) {
        find_result_table.destroy();
        find_result_table = null;
    }
    id_div.innerHTML = "";
    clear_message();
    var name_search = pattern_field.value.toLowerCase().trim();
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
    $.ajax({
        method: "POST",
        url: "scripts/regposTasks.php",
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
            found_record(data);
        },
        error: showAjaxError,
    });
}

// successful return from 2 AXAJ call - processes found records
// unpaid: one record: put it in the cart and go to pay screen
//      multiple records: show table of records with pay icons
// normal:
//      single row: display record
//      multiple rows: display table of records with add/trans buttons
function found_record(data) {
    var find_type = data['find_type'];
    result_perinfo = data['perinfo'];
    result_membership = data['membership'];
    name_search = data['name_search'];

    // unpaid search: Only used by Cashier
    // zero found: status message
    // 1 found: add it to cart and go to pay
    // 2 or more found: display a table of transactions
    if (find_type == 'unpaid') {
        if (result_membership.length == 0) { // no unpaid records
            id_div.innerHTML = 'No unpaid records found';
            return;
        }
        var trantbl = [];
        // loop over unpaid memberships and finding distinct transactions (should this move to a second SQL query?)
        for (var mrow in result_membership) {
            var tid = result_membership[mrow]['tid'];
            if (!trantbl.includes(tid)) {
                trantbl.push(tid);
            }
        }
        if (trantbl.length == 1) { // only 1 row, add it to the cart and go to pay tab
            var tid = trantbl[0];
            for (var row in result_membership) {
                if (result_membership[row]['tid'] == tid) {
                    var index = result_membership[row]['pindex'];
                    add_to_cart(index, 'result');
                }
            }
            review_nochanges(); // build the master transaction and attach records
            return;
        }

        // build the data table for tabulator
        unpaid_table = [];
        // multiple entries unpaid, display table to choose which one
        for (var trow in trantbl) {
            var tid = trantbl[trow];
            var price = 0;
            var paid = 0;
            var names = '';
            var num_mem = 0;
            var prowindex = 0;
            var prow = null;
            for (var mrow in result_membership) {
                if (result_membership[mrow]['tid'] == tid) {
                    prowindex = result_membership[mrow]['pindex'];
                    prow = result_perinfo[prowindex];
                    num_mem++;
                    price += Number(result_membership[mrow]['price']);
                    paid += Number(result_membership[mrow]['paid']);
                    if (names != '') {
                        names += '; ';
                    }
                    names += (prow['last_name'] + ', ' + prow['first_name'] + ' ' + prow['middle_name'] + ' ' + prow['suffix']).replace(/\s+/g, ' ').trim();
                }
            }
            
            var row = { tid: tid, names: names, num_mem: num_mem, price: price, paid: paid, index: trow };
            unpaid_table.push(row);
        }
        // and instantiate the table into the find_results DOM object (div)
        find_result_table = new Tabulator('#find_results', {
            maxHeight: "600px",
            data: unpaid_table,
            layout: "fitColumns",
            initialSort: [
                { column: "names", dir: "asc" },
            ],
            columns: [             
                { title: "TID", field: "tid", headerFilter: true, headerWordWrap: true, width: 50, maxWidth: 50, hozAlign: 'right', },
                { title: "Names", field: "names", headerFilter: true, headerSort: true, headerWordWrap: true, tooltip: true, },
                { title: "#M", field: "num_mem", minWidth: 30, maxWidth: 30, headerSort: false, hozAlign: 'right', },
                { title: "Price", field: "price", maxWidth: 50, minWidth: 50, headerSort: false, hozAlign: 'right', },
                { title: "Paid", field: "paid", maxWidth: 50, minWidth: 50, headerSort: false, hozAlign: 'right', },
                { title: "Cart", width: 40, formatter: addCartIcon, formatterParams: {t:"unpaid"}, headerSort: false, },
                { field: "index", visible: false, },
            ],
        });
        return;
    }
    // sum print and attach counts
    var print_count = 0;
    var attach_count = 0;
    var regtids = [];
    for (rowindex in result_membership) {
        print_count += Number(result_membership[rowindex]['printcount']);
        attach_count += Number(result_membership[rowindex]['attachcount']);
        if (!regtids.includes(result_membership[rowindex]['rstid'])) {
            regtids.push(result_membership[rowindex]['rstid']);
        }
    }
    // not unpaid search... mark the type of the primary membership in the person row for the table
    // find primary membership for each result_perinfo record
    for (rowindex in result_perinfo) {
        var row = result_perinfo[rowindex];
        var primmem = find_primary_membership_by_perid(result_membership, row['perid']);
        if (primmem != null) {
            row['reg_label'] = result_membership[primmem]['label'];
            var tid = result_membership[primmem]['tid'];
            if (tid != '') {
                var other = false;
                var mperid = row['perid'];
                for (var mem in result_membership) {
                    if (result_membership[mem]['perid'] != mperid && result_membership[mem]['tid'] == tid) {
                        other = true;
                        break;
                    }
                }
                if (other) {
                    row['tid'] = tid;
                }
            }
        } else {
            row['reg_label'] = 'No Membership';
            row['reg_tid'] = '';
        }
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
                {title: "Badge Name", field: "badge_name", headerFilter: true, headerWordWrap: true, tooltip: true,},
                {title: "Zip", field: "postal_code", headerFilter: true, headerWordWrap: true, tooltip: true, maxWidth: 70, width: 70},
                {title: "Email Address", field: "email_addr", headerFilter: true, headerWordWrap: true, tooltip: true,},
                {title: "Reg", field: "reg_label", headerFilter: true, headerWordWrap: true, tooltip: true, maxWidth: 80, width: 80,},
                {title: "Note",width: 45, headerSort: false, headerFilter: false, formatter: perNotesIcons, formatterParams: {t:"result"}, },
                {title: "Cart", width: 80, headerFilter: false, headerSort: false, formatter: addCartIcon, formatterParams: {t:"result"},},
                {field: "index", visible: false,},
            ],
        });
    } else if (result_perinfo.length > 0) {  // one row string, or all perinfo/tid searches, display in record format
        if ((!isNaN(name_search)) && regtids.length == 1 && (attach_count > 0 || print_count > 0)) {
            // only 1 transaction returned and it was search by numbrer, and it's been attached for payment before
            // add it to the cart and go to payment
            for (var row in result_membership) {
                if (result_membership[row]['tid'] == tid) {
                    var index = result_membership[row]['pindex'];
                    add_to_cart(index, 'result');
                }
            }
            review_nochanges(); // build the master transaction and attach records
            return;
        }
        number_search = Number(name_search);
        draw_as_records();
        return;
    }
    // no rows show the diagnostic
    id_div.innerHTML = `<div class="container-fluid">
<div class="row mt-3">
    <div class="col-sm-4">No matching records found</div>
    <div class="col-sm-auto"><button class="btn btn-primary btn-small" type="button" id="not_found_add_new" onclick="not_found_add_new();">Add New Person</button>
    </div>
</div>
</div>
`;
    id_div.innerHTML = id_div.innerHTML = 'No matching records found'
}

function draw_as_records() {
    var html = '';
    var first = false;
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

    // set tab to review-tab
    bootstrap.Tab.getOrCreateInstance(review_tab).show();
    review_tab.disabled = false;  
}

// create the review data screen from the cart
function review_update() {
// loop over cart looking for changes in data table
    var rownum = null;
    var data_row
    var el;
    var field;
    for (rownum in cart_perinfo) {
        // update all the fields on the review page
        for (field in cart_perinfo[rownum]) {
            el = document.getElementById('c' + rownum + '-' + field);
            if (el) {
                if (cart_perinfo[rownum][field] != el.value) {
                   // alert("updating  row " + rownum + ":" + rownum + ":" + field + " from '" + cart_perinfo[rownum][field] + "' to '" + el.value + "'");
                    cart_perinfo[rownum][field] = el.value;
                }
            }
        }

    }
    review_shown();
}

// no changes button presssed:
// if everything is paid, go to print.  If cashier (has a find_unpaid button), to go Pay, else put up the diagnostic
//      to ask them to move on to the cashier.
function review_nochanges() {
    // submit the current card data to update the database, retrieve all TID's/PERID's/REGID's of inserted data
    var postData = {
        ajax_request_action: 'updateCartElements',
        cart_perinfo: cart_perinfo,
        cart_perinfo_map: cart_perinfo_map,
        cart_membership: cart_membership,
        user_id: user_id,
    };
    $.ajax({
        method: "POST",
        url: "scripts/regposTasks.php",
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
    var updated_perinfo = data['updated_perinfo'];
    for (var rownum in updated_perinfo) {
        var newrow = updated_perinfo[rownum];
        var cartrow = cart_perinfo[newrow['rownum']]
        cartrow['perid'] = newrow['perid'];
    }
    var updated_membership = data['updated_membership'];
    for (var rownum in updated_membership) {
        var newrow = updated_membership[rownum];
        var cartrow = cart_membership[newrow['rownum']];
        //array('rownum' => $row, 'perid' => $cartrow['perid'], 'create_trans' => $master_perid, 'id' => $new_regid);
        cartrow['create_trans'] = newrow['create_trans'];
        cartrow['regid'] = newrow['id'];
        cartrow['perid'] = newrow['perid'];
    }

    // redraw the cart with the new id's and maps.
    draw_cart();

    // set tab to review-tab
    if (unpaid_rows == 0) {
        goto_print();
        return;
    }

    // Once saved, move them to next step
    if (find_unpaid_button != null) {
        bootstrap.Tab.getOrCreateInstance(pay_tab).show();
    } else {
        next_button.hidden = false;
        startover_button.hidden = true;
        document.getElementById('review-btn-update').hidden = true;
        document.getElementById('review-btn-nochanges').hidden = true;
        document.getElementById('review_status').innerHTML = "Completed: Send customer to cashier with id of " + pay_tid;
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
function pay(nomodal) {
    var checked = false;
    var rownum = null;
    var mrows = null;
    var mrownum = null;
    var ccauth = null;
    var checkno = null;
    var desc = null;
    var ptype = null;
    var total_amount_due = total_price - total_paid;

    if (nomodal != '') {
        cashChangeModal.hide();
    }

    // validate the payment entry: It must be >0 and <= amount due
    //      a payment type must be specified
    //      for check: the check number is required
    //      for credit card: the auth code is required
    //      for discount: description is required, it's optional otherwise
    var elamt = document.getElementById('pay-amt');
    var pay_amt = Number(elamt.value);
    if (pay_amt <= 0 || pay_amt > total_amount_due) {
        if (document.getElementById('pt-cash').checked) {
            if (nomodal == '') {
                cashChangeBody.innerHTML = "Customer owes $" + total_amount_due.toFixed(2) + ", and tendered $" + pay_amt.toFixed(2) +
                    "<br/>Confirm change give to customer of $" + (pay_amt - total_amount_due).toFixed(2);
                cashChangeModal.show();
                return;
            }
        } else {
            elamt.style.backgroundColor = 'var(--bs-warning)';
            return;
        }
    }

    elamt.style.backgroundColor = '';

    var elptdiv = document.getElementById('pt-div');
    elptdiv.style.backgroundColor = '';

    var eldesc = document.getElementById('pay-desc');
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
        if (pay_amt > total_amount_due) {
            change = pay_amt - total_amount_due;
            pay_amt = total_amount_due;
            crow = {
                index: cart_pmt.length + 1, amt: change, ccauth: ccauth, checkno: checkno, desc: eldesc.value, type: 'change',
            }
        }
        var prow = {
            index: cart_pmt.length, amt: pay_amt, ccauth: ccauth, checkno: checkno, desc: eldesc.value, type: ptype,
        };
        // process payment
        var postData = {
            ajax_request_action: 'processPayment',
            cart_membership: cart_membership,
            new_payment: prow,
            change: crow,
            user_id: user_id,
            pay_tid: pay_tid,
        };
        $.ajax({
            method: "POST",
            url: "scripts/regposTasks.php",
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
                updatedPayment(data);
            },
            error: showAjaxError,
        });
    }
}

// updatedPayment:
//  payment entered into the database correctly, update the payment cart and the memberships with the updated paid amounts
 function updatedPayment(data) {
     if (data['prow']) {
         cart_pmt.push(data['prow']);
     }
     if (data['crow']) {
         cart_pmt.push(data['crow']);
     }
     if (data['cart_membership']) {
         cart_membership = data['cart_membership'];
     }
     pay_shown();
}

// Create a receipt and send it to the receipt printer
function print_receipt() {
    // optional header text:
    var d = new Date();
    var payee = (cart_perinfo[0]['first_name'] + ' ' + cart_perinfo[0]['last_name']).trim();
    var header_text = "\nReceipt for payment to " + conlabel + "\nat " + d.toLocaleString() + "\nBy: " + payee + ", Cashier: " + user_id + ", Transaction: " + pay_tid;
    // optional footer text
    var footer_text = '';
    // server side will print the receipt
    var postData = {
        ajax_request_action: 'printReceipt',
        header: header_text,
        prows: cart_perinfo,
        mrows: cart_membership,
        pmtrows: cart_pmt,
        footer: footer_text,
    };
    if (receiptPrinterAvailable) {
        $.ajax({
            method: "POST",
            url: "scripts/printformTasks.php",
            data: postData,
            success: function (data, textstatus, jqxhr) {
                if (data['error'] !== undefined) {
                    show_message(data['error'], 'error');
                    return;
                }
                if (data['message'] !== undefined) {
                    show_message(data['message'], 'success');
                    return;
                }
                if (data['warn'] !== undefined) {
                    show_message(data['warn'], 'success');
                    return;
                }
                clear_message();
            },
            error: showAjaxError,
        });
    } else {
        show_message("Receipt printer not available, you must select a receipt printer at login");
    }
}

// add_badge_to_print:
//      create the parameters for a single badge
//
function add_badge_to_print(index) {
    row = cart_perinfo[index];
    mrow = find_primary_membership_by_perid(cart_membership, row['perid']);
    printrow = cart_membership[mrow];

    var params = {};
    params['type'] = printrow['memType'];
    params['badge_name'] = row['badge_name'];
    params['full_name'] = (row['first_name']+' '+row['last_name']).trim();
    params['category'] = printrow['memCategory'];
    params['badge_id'] = row['perid'];
    params['day'] = 'Friday'; // need day calc here...
    params['age'] = printrow['memAge'];
    return params;
}
// Send one or all of the badges to the printer
function print_badge(index) {
    var rownum = null;
    var mrow = null;
    var row = null;

    var params = [];
    var badges = [];
    if (index >= 0) {
        params.push(add_badge_to_print(index));
        badges.push(index);
    } else {
        for (rownum in cart_perinfo) {
            params.push(add_badge_to_print(rownum));
            badges.push(rownum);
        }
    }
    var postData = {
        ajax_request_action: 'printBadge',
        params: params,
        badges: badges,
    };
    $.ajax({
        method: "POST",
        url: "scripts/printformTasks.php",
        data: postData,
        success: function (data, textstatus, jqxhr) {
            if (data['error'] !== undefined) {
                show_message(data['error'], 'error');
                return;
            }
            PrintComplete(data);
        },
        error: showAjaxError,
    });
}

function PrintComplete(data) {
    var badges = data['badges'];
    var regs = [];
    for (index in badges) {
        if (map_access(printed_obj, index) == 0) {
            row = cart_perinfo[index];
            mrow = find_primary_membership_by_perid(cart_membership, row['perid']);
            cart_membership[mrow]['printcount']++;
            map_set(printed_obj, index, 1);
            regs.push({ regid: cart_membership[mrow]['regid'], printcount: cart_membership[mrow]['printcount']});
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
            url: "scripts/regposTasks.php",
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
    print_shown();
    show_message(data['message'], 'success');
}

// tab shown events - state mapping for which tab is shown
function find_shown(current, previous) {
    in_review = false;
    freeze_cart = false;
    current_tab = find_tab;
    draw_cart();
}

function add_shown(current, previous) {
    in_review = false;
    freeze_cart = false;
    current_tab = add_tab;
    clear_message();
    draw_cart();
}

function review_shown(current, previous) {
    // draw review section
    var review_html = `
<div id='reviewBody' class="container-fluid form-floating">
  <form id='reviewForm' action='javascript: return false; ' class="form-floating">
`;
    var rownum = null;
    var row;
    for (rownum in cart_perinfo) {
        row = cart_perinfo[rownum];
        mrow = find_primary_membership_by_perid(cart_membership, row['perid']);
        review_html += `<div class="row">
        <div class="col-sm-1 m-0 p-0">Mbr ` + (Number(rownum) + 1) + '</div>';
        if (mrow == null) {
            review_html += '<div class="col-sm-8 text-bg-info">No Membership</div>';
        } else {
            review_html += '<div class="col-sm-8 text-bg-success">Membership: ' + cart_membership[mrow]['label'] + '</div>';
        }
        
        review_html += `
    </div>
    <input type="hidden" id='c` + rownum + `-index' value="` + row['index'] + `"/>
    <div class="row mt-1">
        <div class="col-sm-1 m-0 p-0">N:</div>
        <div class="col-sm-auto ms-0 me-2 p-0">
            <input type="text" name="c` + rownum + `-first_name" id='c` + rownum + `-first_name' size="22" maxlength="32" tabindex="1" value="` + row['first_name'] + `"/>
        </div>
        <div class="col-sm-auto ms-0 me-2 p-0">
            <input type="text" name="c` + rownum + `-middle_name" id='c` + rownum + `-middle_name' size="6" maxlength="32" tabindex="2" value="` + row['middle_name'] + `"/>
        </div>
        <div class="col-sm-auto ms-0 me-2 p-0">
            <input type="text" name="c` + rownum + `-last_name" id='c` + rownum + `-last_name' size="22" maxlength="32" tabindex="3" value="` + row['last_name'] + `"/>
        </div>
        <div class="col-sm-auto ms-0 me-0 p-0">
            <input type="text" name="c` + rownum + `-suffix" id='c` + rownum + `-suffix' size="4" maxlength="4" tabindex="4" value="` + row['suffix'] + `"/>
        </div>
    </div>
    <div class="row">
        <div class="col-sm-1 m-0 p-0">BN:</div>
        <div class="col-sm-auto ms-0 me-0 p-0">
            <input type="text" name='c` + rownum + `-badge_name' id='c` + rownum + `-badge_name' size=64 maxlength="64" tabindex='5' value="` + row['badge_name'] + `"/>
        </div>
    </div>
    <div class="row">
        <div class="col-sm-1 m-0 p-0">EM:</div>
        <div class="col-sm-auto ms-0 me-2 p-0">
            <input type="text" name='c` + rownum + `-email_addr' id='c` + rownum + `-email_addr' size=50 maxlength="64" tabindex='5'  value="` + row['email_addr'] + `"/>
        </div>
         <div class="col-sm-auto ms-0 me-0 p-0">
            <input type="text" name='c` + rownum + `-phone' id='c` + rownum + `-phone' size=15 maxlength="15" tabindex='5'  value="` + row['phone'] + `"/>
        </div>
    </div>
    <div class="row">
        <div class="col-sm-1 m-0 p-0">A1:</div>
        <div class="col-sm-auto ms-0 me-0 p-0">
            <input type="text" name='c` + rownum + `-address_1' id='c` + rownum + `-address_1' size=64 maxlength="64" tabindex='5'  value="` + row['address_1'] + `"/>
        </div>
    </div>
    <div class="row">
        <div class="col-sm-1 m-0 p-0">A2:</div>
        <div class="col-sm-auto ms-0 me-0 p-0">
            <input type="text" name='c` + rownum + `-address_2' id='c` + rownum + `-address_2' size=64 maxlength="64" tabindex='5'  value="` + row['address_2'] + `"/>
        </div>
    </div>
    <div class="row">
        <div class="col-sm-1 m-0 p-0">A3:</div>
        <div class="col-sm-auto ms-0 me-2 p-0">
            <input type="text" name="c` + rownum + `-city" id='c` + rownum + `-city' size="22" maxlength="32" tabindex="7" value="` + row['city'] + `"/>
        </div>
        <div class="col-sm-auto ms-0 me-2 p-0">
            <input type="text" name="c` + rownum + `-state" id='c` + rownum + `-state' size="2" maxlength="2" tabindex="8" value="` + row['state'] + `"/>
        </div>
        <div class="col-sm-auto ms-0 me-2 p-0">
            <input type="text" name="c` + rownum + `-postal_code" id='c` + rownum + `-postal_code' size="10" maxlength="10" tabindex="9" value="` + row['postal_code'] + `"/>
        </div>
    </div>
    <div class="row">
        <div class="col-sm-1 m-0 p-0 pt-2">A4:</div>
        <div class="col-sm-auto ms-0 me-0 ps-0 pe-0 pt-2 pb-1">
            <select name='c` + rownum + `-country' id='c` + rownum + `-country' tabindex='10'>
                ` + country_select + `
            </select>
        </div>
    </div>
    <div class="row mb-4">
        <div class="col-sm-1 m-0 p-0">Flags:</div>
        <div class="col-sm-auto ms-0 me-2 p-0">Share Reg?</div>
        <div class="col-sm-auto ms-0 me-2 p-0">
            <select name='c` + rownum + `-share_reg_ok' id='c` + rownum + `-share_reg_ok' tabindex='11'>
               <option value="Y" ` + (row['share_reg_ok'] == 'Y' ? 'selected' : '')+ `>Y</option>
               <option value="N" ` + (row['share_reg_ok'] == 'N' ? 'selected' : '') + `>N</option>
            </select>
        </div>
        <div class="col-sm-auto ms-0 me-2 p-0">Contact OK?</div>
        <div class="col-sm-auto ms-0 me-2 p-0">
            <select name='c` + rownum + `-contact_ok' id='c` + rownum + `-contact_ok' tabindex='11'>
                <option value="Y" ` + (row['contact_ok'] == 'Y' ? 'selected' : '') + `>Y</option>
                <option value="N" ` + (row['contact_ok'] == 'N' ? 'selected' : '') + `>N</option>
            </select>
        </div>
    </div>
`;
    }
    review_html += `
    <div class="row mt-2">
        <div class="col-sm-1 m-0 p-0">&nbsp;</div>
        <div class="col-sm-auto m-0 p-0">
            <button class="btn btn-primary btn-small" type="button" id="review-btn-update" onclick="review_update();">Update All</button>
            <button class="btn btn-primary btn-small" type="button" id="review-btn-nochanges" onclick="review_nochanges();">No Changes</button>
        </div>
    </div>
    <div class="row">
        <div class="col-sm-12" id="review_status"></div>
    </div>
  </form>
</div>
`
    in_review = true;
    freeze_cart = false;
    current_tab = review_tab;
    review_div.innerHTML = review_html;
    for (rownum in cart_perinfo) {
        row = cart_perinfo[rownum];
        selid = document.getElementById('c' + rownum + '-country');
        selid.value = row['country'];
    }
    draw_cart();
}

function pay_shown(current, previous) {
    in_review = false;
    freeze_cart = true;
    current_tab = pay_tab;
    draw_cart();
    if (total_paid == total_price) {
        // nothing more to pay       
        print_tab.disabled = false;
        next_button.hidden = false;
        if (pay_button_pay != null) { 
            pay_button_pay.hidden = true;
            pay_button_rcpt.hidden = false;
            pay_button_print.hidden = false;
            document.getElementById('pay-amt').value='';
            document.getElementById('pay-desc').value='';
            document.getElementById('pay-amt-due').innerHTML = '';
            document.getElementById('pay-check-div').hidden = true;
            document.getElementById('pay-ccauth-div').hidden = true;
            void_button.hidden = true;
        }        
    } else {
        if (pay_button_pay != null) {
            pay_button_pay.hidden = false;
            pay_button_rcpt.hidden = true;
            pay_button_print.hidden = true;
        }
        var total_amount_due = (total_price - total_paid).toFixed(2);

        // draw the pay screen

        var pay_html = `
<div id='payBody' class="container-fluid form-floating">
  <form id='payForm' action='javascript: return false; ' class="form-floating">
    <div class="row">
        <div class="col-sm-2 ms-0 me-2 p-0">Amount Due:</div>
        <div class="col-sm-auto m-0 p-0 ms-0 me-2 p-0" id="pay-amt-due">$` + total_amount_due + `</div>
    </div>
    <div class="row">
        <div class="col-sm-2 ms-0 me-2 p-0">Amount Paid:</div>
        <div class="col-sm-auto m-0 p-0 ms-0 me-2 p-0"><input type="number" class="no-spinners" id="pay-amt" name-"paid-amt size="6"/></div>
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
            <input type="radio" id="pt-discount" name="payment_type" value="discount" onchange='setPayType("discount");'/>
            <label for="pt-discount">Discount</label>
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
            <button class="btn btn-primary btn-small" type="button" id="pay-btn-pay" onclick="pay('');">Confirm Pay</button>
        </div>
        <div class="col-sm-auto ms-0 me-2 p-0">
            <button class="btn btn-primary btn-small" type="button" id="pay-btn-rcpt" onclick="print_receipt();" hidden>Print Receipt</button>
        </div>
        <div class="col-sm-auto ms-0 me-2 p-0">
            <button class="btn btn-primary btn-small" type="button" id="pay-btn-print" onclick="goto_print();" hidden>Print Badges</button>
        </div>
    </div>
  </form>
    <div class="row mt-4">
        <div class="col-sm-12 p-0" id="pay_status"></div>
    </div>
</div>
`;

        pay_div.innerHTML = pay_html;
        pay_button_pay = document.getElementById('pay-btn-pay');
        pay_button_rcpt = document.getElementById('pay-btn-rcpt');
        pay_button_print = document.getElementById('pay-btn-print');
        void_button.hidden = false;
    }
}

function print_shown(current, previous) {
    in_review = false;
    find_tab.disabled = true;
    add_tab.disabled = true;
    review_tab.disabled = true;
    startover_button.hidden = true;
    next_button.hidden = false;
    void_button.hidden = true;
    freeze_cart = true;
    current_tab = print_tab;
    var new_print = false;
    if (printed_obj == null) {
        new_print = true;
        printed_obj = {};
    }
    draw_cart();

    // draw the print screen
    var print_html = `<div id='printBody' class="container-fluid form-floating">
`;
    if (badgePrinterAvailable === false) {
        print_html += 'No printer selected, unable to print badges.  Please log out and back in with the proper printer selected.</div>';
        print_div.innerHTML = print_html;
        return;
    }
    var rownum;
    var crow;
    for (rownum in cart_perinfo) {
        crow = cart_perinfo[rownum];
        mrow = find_primary_membership_by_perid(cart_membership, crow['perid']);
        if (new_print) {
            map_set(printed_obj, crow['index'], 0);
        }
        print_html += `
    <div class="row">
        <div class="col-sm-2 ms-0 me-2 p-0">
            <button class="btn btn-primary btn-small" type="button" id="pay-print-` + cart_perinfo[rownum]['index'] + `" onclick="print_badge(` + crow['index'] + `);">Print</button>
        </div>
        <div class="col-sm-auto ms-0 me-2 p-0">            
            <span class="text-bg-success"> Membership: ` + cart_membership[mrow]['label'] + `</span> (Times Printed: ` +
            cart_membership[mrow]['printcount'] + `)<br/>
              ` + crow['badge_name'] + '/' + (crow['first_name'] + ' ' + crow['last_name']).trim() + `
        </div>
     </div>`;
    }

    print_html += `
    <div class="row mt-4">
        <div class="col-sm-2 ms-0 me-2 p-0">&nbsp;</div>
        <div class="col-sm-auto ms-0 me-2 p-0">
            <button class="btn btn-primary btn-small" type="button" id="pay-print-all" onclick="print_badge(-1);">Print All</button>
        </div>
    </div>
    <div class="row mt-4">
        <div class="col-sm-12 m-0 mt-4 p-0" id="pt-status"></div>
    </div>
</div>`;

    print_div.innerHTML = print_html;
}
