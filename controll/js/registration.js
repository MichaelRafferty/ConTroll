// cart object
var cart = null;

// tab fields
var find_tab = null;
var add_tab = null;
var review_tab = null;
var pay_tab = null;
var current_tab = null;

// find people fields
var id_div = null;
var pattern_field = null;
var find_result_table = null;
var number_search = null;
var memLabel = null;
var find_unpaid_button = null;
var find_perid = null;
var name_search = '';

// add/edit person fields
var add_index_field = null;
var add_perid_field = null;
var add_memIndex_field = null;
var add_first_field = null;
var add_middle_field = null;
var add_last_field = null;
var add_suffix_field = null;
var add_legalName = null;
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
    <select id='ae_mem_sel' name='age' style="width:500px;" tabindex='30'>
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
var pay_button_ercpt = null;
var pay_tid = null;
var discount_mode = 'none';
var num_coupons = 0;
var couponList = null;
var couponSelect = null;
var coupon = null;
var coupon_discount = Number(0).toFixed(2);
var cart_total = Number(0).toFixed(2);
var pay_prior_discount = null;
var cc_html = '';
var $purchase_label = 'purchase';

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
var Manager = false;
var non_primary_categories = ['add-on', 'addon', 'cancel'];
var upgradable_types = ['one-day', 'oneday', 'virtual'];

// filter criteria
var filt_excat = null; // array of exclude category
var filt_cat = null;  // array of categories to include
var filt_age = null;  // array of ages to include
var filt_type = null; // array of types to include
var filt_conid = null; // array of conid's to include
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

    // cart
    cart = new reg_cart();

    // find people
    pattern_field = document.getElementById("find_pattern");
    pattern_field.addEventListener('keyup', (e)=> { if (e.code === 'Enter') find_record('search'); });
    pattern_field.focus();
    id_div = document.getElementById("find_results");
    find_unpaid_button = document.getElementById("find_unpaid_btn");

    // add/edit people
    add_index_field = document.getElementById("perinfo-index");
    add_perid_field = document.getElementById("perinfo-perid");
    add_memIndex_field = document.getElementById("membership-index");
    add_first_field = document.getElementById("fname");
    add_middle_field = document.getElementById("mname");
    add_last_field = document.getElementById("lname");
    add_legalName_field = document.getElementById("legalName");
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
    coupon = new Coupon();

    // add events
    find_tab.addEventListener('shown.bs.tab', find_shown)
    add_tab.addEventListener('shown.bs.tab', add_shown)
    review_tab.addEventListener('shown.bs.tab', review_shown)
    pay_tab.addEventListener('shown.bs.tab', pay_shown)

    // notes items
    notes = new bootstrap.Modal(document.getElementById('Notes'), { focus: true, backdrop: 'static' });

    // change membership
    changeModal = new bootstrap.Modal(document.getElementById('Change'), { focus: true, backdrop: 'static' });

    // cash payment requires change
    cashChangeModal = new bootstrap.Modal(document.getElementById('CashChange'), { focus: true, backdrop: 'static' });

    bootstrap.Tab.getOrCreateInstance(find_tab).show();

    // load the initial data and the proceed to set up the rest of the system
    var postData = {
        ajax_request_action: 'loadInitialData',
    };
    $.ajax({
        method: "POST",
        url: "scripts/reg_loadInitialData.php",
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
function loadInitialData(data) {
    // map the memIds and labels for the pre-coded memberships.  Doing it now because it depends on what the database sends.
    // tables
    conlabel =  data['label'];
    conid = data['conid'];
    user_id = data['user_id']
    Manager = data['Manager'];
    startdate = data['startdate'];
    enddate = data['enddate'];
    memList = data['memLabels'];
    catList = data['memCategories'];
    ageList = data['ageList'];
    typeList = data['memTypes'];
    cc_html = data['cc_html'];
    discount_mode = data['discount'];
    if (discount_mode === undefined || discount_mode === null || discount_mode == '')
        discount_mode = 'none';

    // build memListMap from memList
    memListMap = new map();
    var index = 0;
    while (index < memList.length) {
        memListMap.set(memList[index]['id'], index);
        index++;
    }

    // build membership_select options

    filt_excat = non_primary_categories.slice(0);
    filt_excat.push('upgrade', 'yearahead');
    filt_cat = null;
    filt_type = null;
    filt_age = null;
    filt_conid = [Number(conid)];
    filt_shortname_regexp = null;
    var match = memList.filter(mem_filter);
    membership_select = '';
    var membership_selectlist = [];
    for (var row in match) {
        if (match[row]['canSell'] == 1 || Manager) {
            var option = '<option value="' + match[row]['id'] + '">' + match[row]['label'] + ", $" + match[row]['price'] +
                ' (' + match[row]['enddate'] + '; ' + match[row]['id'] + ')' + "</option>\n";
            membership_select += option;
            membership_selectlist.push({price: match[row]['price'], option: option});
        }
    }

    // set up coupon items
    num_coupons = data['num_coupons'];
    couponList = data['couponList'];
    // build coupon select
    if (num_coupons <= 0) {
        couponSelect = '';
    } else {
        couponSelect = '<select name="couponSelect" id="pay_couponSelect">' + "\n<option value=''>No Coupon</option>\n";
        for (var row in couponList) {
            var item = couponList[row];
            couponSelect += "<option value='" + item['id'] + "'>" + item['code'] + ' (' + item['name'] + ")</option>\n";
        }
        couponSelect += "</select>\n";
    }

    cart.set_initialData(membership_select, membership_selectlist)

    // set up initial values
    result_perinfo = [];
    result_membership = [];

    // set starting stages of left and right windows
    clear_add(1);
}


// search memLabel functions
// mem_filter - select specific rows from memList based on
//  filt_cat: memCategories to include
//  filt_type: memTypes to include
//  filt_age: ageList to include
//  filt_shortname_regexp: filter on shortname field
//  lastly, if it passes everything else filt_excat: anything except this list of memCategories
function mem_filter(cur, idx, arr) {
    //if (cur['canSell'] == 0)
       // return false;

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
    if (filt_conid != null) {
        if (!filt_conid.includes(Number(cur['conid'])))
            return false;
    }

    return true;
}

// map id to MemLabel entry
function find_memLabel(id) {
    var rownum = memListMap.get(id);
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

// given a perid, find it''s primary membership in the result_membership array
function find_primary_membership_by_perid(tbl, perid) {
    var regitems = find_memberships_by_perid(tbl, perid);
    var mem_index = null;
    for (var item in regitems) {
        var mi_row = find_memLabel(regitems[item]['memId']);
        if (mi_row['conid'] != conid)
            continue;

        if (non_primary_categories.includes(mi_row['memCategory']))
            continue;

        mem_index = regitems[item]['index'];
        break;
    }
    return mem_index;
}

// badge_name_default: build a default badge name if its empty
function badge_name_default(badge_name, first_name, last_name) {
    if (badge_name === undefined | badge_name === null || badge_name === '') {
        var default_name = (first_name + ' ' + last_name).trim();
        return '<i>' + default_name.replace(/ +/, ' ') + '</i>';
    }
    return badge_name;
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
        url: "scripts/reg_voidPayment.php",
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

    // empty cart
    cart.startOver();
    find_unpaid_button.hidden = false;
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
    cart.hideNext();
    cart.hideVoid();
    pay_button_pay = null;
    pay_button_ercpt = null;
    receeiptEmailAddresses_div = null;
    pay_tid = null;
    pay_prior_discount = null;

    clear_add(reset_all);
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
        data['legalName'] + '<br/>' +
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

// remove single membership item from the cart (leaving other memberships and person information
function delete_membership(index) {
    cart.deleteMembership(index);
}

// change single membership item from the cart - only allow items of the same class with higher prices
function change_membership(index) {
    cart.changeMembership(index);
}
// save_membership_change
// update saved cart row with new memId
function save_membership_change() {
    cart.saveMembershipChange();
}

// common confirm add/edit screen dirty, if the tab isn't shown switch to it if dirty
function confirm_discard_add_edit(silent) {
    if (!add_edit_dirty_check || cart.isFrozen()) // don't check if dirty, or if the cart is frozen, return ok to discard
        return true;

    add_edit_current_state = $("#add-edit-form").serialize();
    if (add_edit_initial_state == add_edit_current_state)
        return true; // no changes found

    if (silent)
        return false;

    // show the add/edit screen if it's hidden
    bootstrap.Tab.getOrCreateInstance(add_tab).show();

    return confirm("Discard current data in add/edit screen?");
}

// event handler for beforeunload event, prevents leaving with unsaved data
function check_all_unsaved(e) {
    // data editing checks
    if (!confirm_discard_add_edit(true))  {
        e.preventDefault();
        e.returnValue="You have unsaved member changes, leave anyway";
        return;
    }

    if (!cart.confirmDiscardCartEntry(-1, true)) {
        e.preventDefault();
        e.returnValue="You have unsaved cart changes, leave anyway";
        return;
    }

    delete e['returnValue'];
}

// populate the add/edit screen from a cart item, and switch to add/edit
function edit_from_cart(perid) {
    if (!confirm_discard_add_edit(false))
            return;

    clear_add(1);
    cart.getAddEditFields(perid);

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
function clear_add(reset_all) {
    // reset to empty all of the add/edit fields
    add_index_field.value = "";
    add_perid_field.value = "";
    add_first_field.value = "";
    add_middle_field.value = "";
    add_last_field.value = "";
    add_legalName_field.value = "";
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
    add_edit_initial_state = $("#add-edit-form").serialize();
    add_edit_current_state = "";
    if (reset_all > 0)
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
    var new_legalName = add_legalName_field.value.trim();
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

    if (new_legalName == '') {
        new_legalName = ((new_first + ' ' + new_middle).trim() + ' ' + new_last + ' ' + new_suffix).trim();
    }

    if (add_mode == false && edit_index != '') { // update perinfo/meminfo and cart_perinfo and cart_memberships
        var row = {};
        row['first_name'] = new_first;
        row['middle_name'] = new_middle;
        row['last_name'] = new_last;
        row['suffix'] = new_suffix;
        row['legalName'] = new_legalName;
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

        var mrow = null;
        if (new_badgememId != null) {
            var mrow = {};
            if (new_memindex == '') {
                mrow['printcount'] = 0;
                mrow['perid'] = edit_perid;
                mrow['pindex'] = edit_index;
            }
            var mi_row = find_memLabel(new_badgememId);
            mrow['price'] = mi_row['price'];
            mrow['memId'] = mi_row['id'];
            mrow['memCategory'] = mi_row['memCategory'];
            mrow['memType'] = mi_row['memType'];
            mrow['memAge'] = mi_row['memAge'];
            mrow['shortname'] = mi_row['shortname'];
            mrow['label'] = mi_row['label'];
        }
        cart.updateEntry(edit_index, new_memindex, row, mrow);

        // clear the fields that should not be preserved between adds.  Allowing a second person to be added using most of the same data as default.
        add_first_field.value = "";
        add_middle_field.value = "";
        add_suffix_field.value = "";
        add_legalName_field.value = "";
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
        cart.drawCart();
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
    $("button[name='find_btn']").attr("disabled", true);
    $.ajax({
        method: "POST",
        url: "scripts/reg_findRecord.php",
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
            add_found(data);
            $("button[name='find_btn']").attr("disabled", false);
        },
        error: function (jqXHR, textstatus, errorThrown) {
            $("button[name='find_btn']").attr("disabled", false);
            showAjaxError(jqXHR, textstatus, errorThrown);
        }
    });
}

// add_found: all the tasks post search for matching records for adding a record to the cart
function add_found(data) {
    var rowindex;
// see if they already exist (if add to cart)
    add_perinfo = data['perinfo'];
    add_membership = data['membership'];
    
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
                {field: "legalName", visible: false,},
                {title: "Badge Name", field: "badge_name", headerFilter: true, headerWordWrap: true, tooltip: true,},
                {title: "Zip", field: "postal_code", headerFilter: true, headerWordWrap: true, tooltip: true, maxWidth: 70, width: 70},
                {title: "Email Address", field: "email_addr", headerFilter: true, headerWordWrap: true, tooltip: true,},
                {title: "Reg", field: "reg_label", headerFilter: true, headerWordWrap: true, tooltip: true, maxWidth: 120, width: 120,},
                {title: "Nt", width: 45, headerSort: false, headerFilter: false, formatter: perNotesIcons, formatterParams: {t:"add"}, },
                {title: "Cart", width: 100, headerFilter: false, headerSort: false, formatter: addCartIcon, formatterParams: {t:"add"},},
                {field: "index", visible: false,},
                {field: "open_notes", visible: false,},
            ],
        });
        addnew_button.innerHTML = "Add New";
        add_edit_initial_state = $("#add-edit-form").serialize();
        $("button[name='find_btn']").attr("disabled", false);
        return;
    }
    add_new_to_cart();

}

// add_new_to_cart - not in system or operator said they are really new, add them to the cart
function add_new_to_cart() {
    //var edit_index = add_index_field.value.trim();
    //var edit_perid = add_perid_field.value.trim();
    //var new_memindex = add_memIndex_field.value.trim();
    var new_first = add_first_field.value.trim();
    var new_middle = add_middle_field.value.trim();
    var new_last = add_last_field.value.trim();
    var new_suffix = add_suffix_field.value.trim();
    var new_legalName = add_legalName_field.value.trim();
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

    if (new_legalName == '') {
        new_legalName = ((new_first + ' ' + new_middle).trim() + ' ' + new_last + ' ' + new_suffix).trim();
    }
    //var new_contact = add_contact_field.value.trim();
    //var new_share = add_share_field.value.trim();

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
        legalName: new_legalName,
        badge_name: new_badgename,
        address_1: new_addr1, address_2: new_addr2, city: new_city, state: new_state, postal_code: new_postal_code,
        country: new_country, email_addr: new_email, phone: new_phone,
        share_reg_ok: 'Y', contact_ok:'Y', new_contact:'Y', active: 'Y', banned: 'N',
    };
    var mi_row = find_memLabel(new_badgememId);
    var mrow = {
        perid: new_perid, conid: mi_row['conid'],
        price: mi_row['price'], paid: 0, tid: '', index: cart.getCartLength(), printcount: 0,
        memCategory: mi_row['memCategory'], memType: mi_row['memType'], memAge: mi_row['memAge'],
        shortname: mi_row['shortname'], memId: new_badgememId, label: mi_row['label'],
    }
    new_perid--;

    add_first_field.value = "";
    add_middle_field.value = "";
    add_email_field.value = "";
    add_phone_field.value = "";
    add_badgename_field.value = "";
    cart.add(row, [mrow]);

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
        <div class="col-sm-3 pt-1 pb-1">`;
    if (first) {
        html += `<button class="btn btn-primary btn-sm" id="add_btn_all" onclick="add_to_cart(-` + number_search + `, 'result');">Add All Cart</button>`;
    }
    html += `</div>
        <div class="col-sm-5 pt-1 pb-1">`;
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
    if (Manager) {
        html += '<button type="button" class="btn btn-sm btn-secondary p-0" onClick="edit_perinfo_notes(0, \'result\')">Edit Notes</button>';
    }

    html += `
            </div>
        </div>
        <div class="row">
            <div class="col-sm-3">Person ID:</div>
            <div class="col-sm-9">` + data['perid'] + `</div>
        </div>
        <div class="row">
            <div class="col-sm-3">Badge Name:</div>
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
        if (tid != '' && tid != undefined && tid != null) {
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
    if (open_notes != null && open_notes.length > 0 && !Manager) {
        html += '<button type="button" class="btn btn-sm btn-info p-0" style="--bs-btn-font-size: 75%;"  onclick="show_perinfo_notes(' + index + ', \'' + formatterParams['t'] + '\')">O</button>';
    }
    if (Manager) {
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

    if (!Manager)
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
        if (notesType == 'PC' && Manager) {
            cart.setPersonNote(notesIndex, document.getElementById("perinfoNote").value);
        }
        if (notesType == 'PR' && Manager) {
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
                    url: "scripts/reg_updatePerinfoNote.php",
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
function find_record(find_type) {
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
        url: "scripts/reg_findRecord.php",
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
function found_record(data) {
    var row;
    var mrow;
    var index;
    var tid;
    var mperid;
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
        for (mrow in result_membership) {
            tid = result_membership[mrow]['tid'];
            if (!trantbl.includes(tid)) {
                trantbl.push(tid);
            }
        }
        if (trantbl.length == 1) { // only 1 row, add it to the cart and go to pay tab
            tid = trantbl[0];
            for (row in result_membership) {
                if (result_membership[row]['tid'] == tid) {
                    index = result_membership[row]['pindex'];
                    add_to_cart(index, 'result');
                }
            }
            added_payable_trans_to_cart(); // build the master transaction and attach records
            return;
        }

        // build the data table for tabulator
        unpaid_table = [];
        // multiple entries unpaid, display table to choose which one
        for (var trow in trantbl) {
            tid = trantbl[trow];
            var price = 0;
            var paid = 0;
            var names = '';
            var num_mem = 0;
            var prowindex = 0;
            var prow = null;
            mperid = -1;
            for (mrow in result_membership) {
                if (result_membership[mrow]['tid'] == tid) {
                    prowindex = result_membership[mrow]['pindex'];
                    prow = result_perinfo[prowindex];
                    num_mem++;
                    price += Number(result_membership[mrow]['price']);
                    paid += Number(result_membership[mrow]['paid']);
                    // show each name only once
                    if (mperid != result_membership[mrow]['perid']) {
                        if (names != '') {
                            names += '; ';
                        }
                        names += (prow['last_name'] + ', ' + prow['first_name'] + ' ' + prow['middle_name'] + ' ' + prow['suffix']).replace(/\s+/g, ' ').trim();
                        mperid = result_membership[mrow]['perid'];
                    }
                }
            }
            
            row = { tid: tid, names: names, num_mem: num_mem, price: price, paid: paid, index: trow };
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
                { title: "TID", field: "tid", headerFilter: true, headerWordWrap: true, width: 70, maxWidth: 70, hozAlign: 'right', },
                { title: "Names", field: "names", headerFilter: true, headerSort: true, headerWordWrap: true, tooltip: true, },
                { title: "#M", field: "num_mem", minWidth:50, maxWidth: 50, headerSort: false, hozAlign: 'right', },
                { title: "Price", field: "price", maxWidth: 80, minWidth: 80, headerSort: false, hozAlign: 'right', },
                { title: "Paid", field: "paid", maxWidth: 80, minWidth: 80, headerSort: false, hozAlign: 'right', },
                { title: "Cart", width: 50, formatter: addCartIcon, formatterParams: {t:"unpaid"}, headerSort: false, },
                { field: "index", visible: false, },
            ],
        });
        return;
    }
    // sum print and attach counts
    var print_count = 0;
    var attach_count = 0;
    var regtids = [];
    var rowindex;
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
        row = result_perinfo[rowindex];
        var primmem = find_primary_membership_by_perid(result_membership, row['perid']);
        if (primmem != null) {
            row['reg_label'] = result_membership[primmem]['label'];
            tid = result_membership[primmem]['tid'];
            if (tid != '') {
                var other = false;
                mperid = row['perid'];
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
                {title: "Per ID", field: "perid", headerWordWrap: true, width: 80, visible: false, hozAlign: 'right',},
                {field: "index", visible: false, },
                {title: "Name", field: "fullName", headerFilter: true, headerWordWrap: true, tooltip: build_record_hover,},
                {field: "last_name", visible: false,},
                {field: "first_name", visible: false,},
                {field: "middle_name", visible: false,},
                {field: "suffix", visible: false,},
                {field: "legalName", visible: false,},
                {title: "Badge Name", field: "badge_name", headerFilter: true, headerWordWrap: true, tooltip: true,},
                {title: "Zip", field: "postal_code", headerFilter: true, headerWordWrap: true, tooltip: true, maxWidth: 70, width: 70},
                {title: "Email Address", field: "email_addr", headerFilter: true, headerWordWrap: true, tooltip: true,},
                {title: "Reg", field: "reg_label", headerFilter: true, headerWordWrap: true, tooltip: true, maxWidth: 120, width: 120,},
                {title: "Nt",width: 45, headerSort: false, headerFilter: false, formatter: perNotesIcons, formatterParams: {t:"result"}, },
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
// if everything is put up next customer
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
        url: "scripts/reg_updateCartElements.php",
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
    bootstrap.Tab.getOrCreateInstance(pay_tab).show();
}

// setPayType: shows/hides the appropriate fields for that payment type
function setPayType(ptype) {
    var elcheckno = document.getElementById('pay-check-div');
    var elccauth = document.getElementById('pay-ccauth-div');
    var elonline = document.getElementById('pay-online-div');
    var econfirm = document.getElementById('');

    elcheckno.hidden = ptype != 'check';
    elccauth.hidden = ptype != 'credit';
    elonline.hidden = ptype != 'online';
    pay_button_pay.disabled = ptype == 'online';

    if (ptype != 'check') {
        document.getElementById('pay-checkno').value = null;
    }
    if (ptype != 'credit') {
        document.getElementById('pay-ccauth').value = null;
    }
}

// Process a payment against the transaction
function pay(nomodal, prow = null, nonce = null) {
    var checked = false;
    var ccauth = null;
    var checkno = null;
    var desc = null;
    var ptype = null;
    var total_amount_due = cart.getTotalPrice() - (cart.getTotalPaid() + Number(coupon_discount));
    var pt_cash = document.getElementById('pt-cash').checked;
    var pt_check = document.getElementById('pt-check').checked;
    var pt_online = document.getElementById('pt-online').checked;
    var pt_credit = document.getElementById('pt-credit').checked;
    var pt_discount = document.getElementById('pt-discount');
    if (pt_discount)
        pt_discount = pt_discount.checked;
    else
        pt_discount = false;

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
            if (pt_cash) {
                if (nomodal == '') {
                    cashChangeModal.show();
                    document.getElementById("CashChangeBody").innerHTML = "Customer owes $" + total_amount_due.toFixed(2) + ", and tendered $" + pay_amt.toFixed(2) +
                        "<br/>Confirm change give to customer of $" + (pay_amt - total_amount_due).toFixed(2);
                    return;
                }
            } else {
                elamt.style.backgroundColor = 'var(--bs-warning)';
                if (pt_online)
                    $('#' + $purchase_label).removeAttr("disabled");
                return;
            }
        }
        if (pay_amt <= 0) {
            elamt.style.backgroundColor = 'var(--bs-warning)';
            if (pt_online)
                $('#' + $purchase_label).removeAttr("disabled");
            return;
        }

        elamt.style.backgroundColor = '';

        var elptdiv = document.getElementById('pt-div');
        elptdiv.style.backgroundColor = '';

        var eldesc = document.getElementById('pay-desc');
        if (pt_discount) {
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

        if (pt_check) {
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

        if (pt_credit) {
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
        if (pt_online) {
            ptype = 'online';
            if (nonce == null) {
                alert("Credit Card Processing Error: Unable to obtain nonce token");
                $('#' + $purchase_label).removeAttr("disabled");
                return;
            }
            checked = true;
        }

        if (pt_cash) {
            ptype = 'cash';
            checked = true;
        }

        if (!checked) {
            elptdiv.style.backgroundColor = 'var(--bs-warning)';
            if (pt_online)
                $('#' + $purchase_label).removeAttr("disabled");
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
                index: cart.getPmtLength(), amt: pay_amt, ccauth: ccauth, checkno: checkno, desc: eldesc.value, type: ptype, nonce: nonce,
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
        nonce: nonce,
        user_id: user_id,
        pay_tid: pay_tid,
    };
    pay_button_pay.disabled = true;
    $.ajax({
        method: "POST",
        url: "scripts/reg_processPayment.php",
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
            } else if (data['status'] == 'error') {
                show_message(data['data'], 'error');
            }
            if (!stop)
                updatedPayment(data);
            pay_button_pay.disabled = false;
            if (pt_online)
                $('#' + $purchase_label).removeAttr("disabled");
        },
        error: function (jqXHR, textstatus, errorThrown) {
            pay_button_pay.disabled = false;
            if (pt_online)
                $('#' + $purchase_label).removeAttr("disabled");
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

// Create a receipt and email it
function email_receipt(receipt_type) {
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
    pay_button_ercpt.disabled = true;
    $.ajax({
        method: "POST",
        url: "scripts/reg_emailReceipt.php",
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
            pay_button_ercpt.disabled = false;
        },
        error: function (jqXHR, textstatus, errorThrown) {
            pay_button_ercpt.disabled = false;
            showAjaxError(jqXHR, textstatus, errorThrown);
        }
    });
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
        cart.showNext();
        if (pay_button_pay != null) {
            var rownum;
            pay_button_pay.hidden = true;
            pay_button_ercpt.hidden = false;
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
            document.getElementById('pay-amt').value='';
            document.getElementById('pay-desc').value='';
            document.getElementById('pay-amt-due').innerHTML = '';
            document.getElementById('pay-check-div').hidden = true;
            document.getElementById('pay-ccauth-div').hidden = true;
            document.getElementById('pay-online-div').hidden = true;
            cart.hideVoid();
        }
    } else {
        if (pay_button_pay != null) {
            pay_button_pay.hidden = false;
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
    if (num_coupons > 0 && cart.allowAddCouponToCart()) { // cannot apply a coupon if one was already in the cart (and of course, there need to be valid coupons right now)
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
            <label for="pt-credit">Offline Credit Card</label>
            <input type="radio" id="pt-online" name="payment_type" value="credit" onchange='setPayType("online");'/>
            <label for="pt-online">Online Credit Card</label>
            <input type="radio" id="pt-check" name="payment_type" value="check" onchange='setPayType("check");'/>
            <label for="pt-check">Check</label>
            <input type="radio" id="pt-cash" name="payment_type" value="cash" onchange='setPayType("cash");'/>
            <label for="pt-cash">Cash</label>
`;
        if (discount_mode != "none") {
            if (discount_mode == 'any' || ((discount_mode == 'manager' || discount_mode == 'active') && Manager)) {
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
    <div class="row mb-2" id="pay-online-div" hidden>
        <div class="col-sm-12 ms-0 me-0 p-0">` + cc_html + `</div>  
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
            <button class="btn btn-primary btn-sm" type="button" id="pay-btn-ercpt" onclick="email_receipt('email');" hidden disabled>Email Receipt</button>
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
        pay_button_ercpt = document.getElementById('pay-btn-ercpt');
        receeiptEmailAddresses_div = document.getElementById('receeiptEmailAddresses');
        if (receeiptEmailAddresses_div)
            receeiptEmailAddresses_div.innerHTML = '';
        if (cart.getPmtLength() > 0) {
            cart.showVoid();
            cart.hideStartOver();
        } else {
            cart.hideVoid();
            cart.showStartOver();
        }
    }
}

// process online credit card payment
function makePurchase(token, label) {
    if (label != '') {
        $purchase_label = label;
    }
    if (token == 'test_ccnum') {  // this is the test form
        token = document.getElementById(token).value;
    }

    $('#' + $purchase_label).attr("disabled", "disabled");
    pay('', null, token);
}

// items from base.js in atcon moved here

// dayFromLabel(label)
// return the full day name from a memList/memLabel label.
function dayFromLabel(label) {
    var pattern_fa = /^mon\s.*$/i;
    var pattern_ff = /^monday.*$/i;
    var pattern_ma = /.*\s+mon\s.*$/i;
    var pattern_mf = /.*\s+monday.*$/i;
    if (pattern_fa.test(label) || pattern_ff.test(label) || pattern_ma.test(label) || pattern_mf.test(label))
        return "Monday;"

    pattern_fa = /^tue\s.*$/i;
    pattern_ff = /^tueday.*$/i;
    pattern_ma = /.*\s+tue\s.*$/i;
    pattern_mf = /.*\s+tueday.*$/i;
    if (pattern_fa.test(label) || pattern_ff.test(label) || pattern_ma.test(label) || pattern_mf.test(label))
        return "Tuesday;"


    pattern_fa = /^wed\s.*$/i;
    pattern_ff = /^wednesday.*$/i;
    pattern_ma = /.*\s+wed\s.*$/i;
    pattern_mf = /.*\s+wednesday.*$/i;
    if (pattern_fa.test(label) || pattern_ff.test(label) || pattern_ma.test(label) || pattern_mf.test(label))
        return "Wednesday;"

    pattern_fa = /^thu\s.*$/i;
    var pattern_faa = /^thur\s.*$/i;
    pattern_ff = /^thursday.*$/i;
    pattern_ma = /.*\s+thu\s.*$/i;
    var pattern_maa = /.*\s+thur\s.*$/i;
    pattern_mf = /.*\s+thursday.*$/i;
    if (pattern_fa.test(label) || pattern_faa.test(label) || pattern_ff.test(label) ||
        pattern_ma.test(label) || pattern_maa.test(label) || pattern_mf.test(label))
        return "Thursday;"

    pattern_fa = /^fri\s.*$/i;
    pattern_ff = /^friday.*$/i;
    pattern_ma = /.*\s+fri\s.*$/i;
    pattern_mf = /.*\s+friday.*$/i;
    if (pattern_fa.test(label) || pattern_ff.test(label) || pattern_ma.test(label) || pattern_mf.test(label))
        return "Friday;"

    pattern_fa = /^sat\s.*$/i;
    pattern_ff = /^saturday.*$/i;
    pattern_ma = /.*\s+sat\s.*$/i;
    pattern_mf = /.*\s+saturday.*$/i;
    if (pattern_fa.test(label) || pattern_ff.test(label) || pattern_ma.test(label) || pattern_mf.test(label))
        return "Saturday;"

    pattern_fa = /^sun\s.*$/i;
    pattern_ff = /^sunday.*$/i;
    pattern_ma = /.*\s+sun\s.*$/i;
    pattern_mf = /.*\s+sunday.*$/i;
    if (pattern_fa.test(label) || pattern_ff.test(label) || pattern_ma.test(label) || pattern_mf.test(label))
        return "Sunday;"

    return "";
}

// obsolete code, soon to be dropped from the file
function hideBlock(block) {
    $(block + "Form").hide();
    $(block + "ShowLink").show();
    $(block + "HideLink").hide();
}

function showBlock(block) {
    $(block + "Form").show();
    $(block + "ShowLink").hide();
    $(block + "HideLink").show();
}

function addShowHide(block, id) {
    var show = $(document.createElement("a"));
    var hide = $(document.createElement("a"));
    show.addClass('showlink');
    hide.addClass('hidelink');
    show.attr('id',id+"ShowLink");
    hide.attr('id',id+"HideLink");
    show.attr('href',"javascript:void(0)");
    hide.attr('href',"javascript:void(0)");
    show.click(function() {showBlock("#" + id);});
    hide.click(function() {hideBlock("#" + id);});
    show.append("(show)");
    hide.append("(hide)");
    block.append(" ").append(show).append(" ").append(hide);
    var container = $(document.createElement("form"));
    container.attr('id',id+"Form");
    container.attr('name', id);
    block.append(container);
    show.click()
    return container;
}

function displaySearchResults(data, callback) {
    var resDiv = $("#searchResultHolder");
    resDiv.empty();
    if(data["error"]) { showError(data["error"]); return false;}
    if(data["count"]) {
        $("#resultCount").empty().html("(" + data["count"] + ")");
    } else { $("#resultCount").empty().html("(0)"); }

    for (var resultSet in data["results"]) {
        if (data["results"][resultSet].length == 0) { continue; }
        var setTitle = $(document.createElement("span"));
        setTitle.addClass('blocktitle');
        setTitle.append(resultSet);
        resDiv.append(setTitle)
        var resContainer = addShowHide(resDiv, resultSet);
        var result;
        for (result in data["results"][resultSet]) {
            var user = data["results"][resultSet][result];
            var userDiv = $(document.createElement("div"));

            userDiv.attr('userid', user['id']);
            userDiv.data('obj', data["results"][resultSet][result]);
            userDiv.addClass('button').addClass('searchResult').addClass('half');
            var flags = $(document.createElement("div"));
            flags.addClass('right').addClass('half').addClass('notice');
            userDiv.append(flags);
            if(user['label']) { userDiv.append(user['label']+"<br/>"+"<hr/>"); }
            if(user['full_name']) { userDiv.append(user['full_name']+"<br/>"); }
            else { userDiv.append("***NO NAME***<br/>");}
            if(user['badge_name']) { userDiv.append(user['badge_name']+"<br/>"); }
            userDiv.append($(document.createElement("hr")));
            if(user['address']) { userDiv.append(user['address']+"<br/>"); }
            else { userDiv.append("***NO STREET ADDR***<br/>"); }
            if(user['addr_2']) { userDiv.append(user['addr_2']+"<br/>"); }
            if(user['locale']) { userDiv.append(user['locale']+"<br/>"); }
            else { userDiv.append("***NO CITY/STATE/ZIP***<br/>"); }
            userDiv.append($(document.createElement("hr")));
            if(user['email_addr']) { userDiv.append(user['email_addr']+"<br/>"); }
            if(user['phone']) { userDiv.append(user['phone']+"<br/>"); }
            if(user['banned'] == 'Y') {
                flags.append('banned<br/>');
                userDiv.addClass('banned');
            }
            else if (user['active'] == 'N') {
                flags.append('inactive<br/>');
                userDiv.addClass('inactive');
            }
            resContainer.append(userDiv);
            userDiv.click(function () {callback($(this).data('obj'));});
        }
    }

}

function submitForm(formObj, formUrl, succFunc, errFunc) {
    var postData = $(formObj).serialize();
    if(succFunc == null) {
        succFunc = function(data, textStatus, jsXhr) {
            $('#test').empty().append(JSON.stringify(data, null, 2));
        }
    }

    $.ajax({
        url: formUrl,
        type: "POST",
        data: postData,
        success: succFunc,
        error: function(JqXHR, textStatus, errorThrown) {
            $('#test').empty().append(JSON.stringify(data, null, 2));
        }
    });
}

var tracker = [];
function track(formName) {
    tracker[formName] = {};
    $(formName + " :input").each(function() {
        tracker[formName][$(this).attr('name')] = false;
        $(this).on("change", function () {
            tracker[formName][$(this).attr('name')] = true;
        });
    });
}

function submitUpdateForm(formObj, formUrl, succFunc, errFunc) {
    var postData = "id="+$(formObj + " :input[name=id]").val();
    for(var key in tracker[formObj]) {
        if(tracker[formObj][key]) {
            if ($(formObj + " :input[name="+key+"]").attr('type')=='radio') {
                postData += "&" + key + "=" + $(formObj +" :input[name=" + key + "]:checked").val();
            } else if ($(formObj + " :input[name="+key+"]").attr('type')=='checkbox') {
                postData += "&" + key + "=" + $(formObj +" :input[name=" + key + "])").attr('checked');
            } else {
                postData += "&" + key + "=" + $(formObj +" :input[name=" + key + "]").val();
            }
        }
    }
    if(succFunc == null) {
        succFunc = function(data, textStatus, jqXHR) {
            $('#test').empty().append(JSON.stringify(data));
        }
    }
    $.ajax({
        url: formUrl,
        type: "POST",
        data: postData,
        success: succFunc,
        error: function(JqXHR, textStatus, errorThrown) {
            $('#test').empty().append(JSON.stringify(JqXHR));
        }
    });
}

function testValid(formObj) {
    var errors = 0;

    $(formObj + " :required").map(function() {
        if(!$(this).val()) {
            $(this).addClass('need');
            errors++;
        } else {
            $(this).removeClass('need');
        }
    });

    return (errors == 0);
}

function getForm(formObj, formUrl, succFunc, errFunc) {
    var getData = $(formObj).serialize();
    if(succFunc == null) {
        succFunc = function(data, textStatus, jqXHR) {
            $('#test').empty().append(JSON.stringify(data, null, 2));
        }
    }
    $.ajax({
        url: formUrl,
        type: "GET",
        data: getData,
        success: succFunc,
        error: function(JqXHR, textStatus, errorThrown) {
            $('#test').empty().append(JSON.stringify(JqXHR, null, 2));
            if(errFunc != null) { errFunc(); }
        }
    });
}
