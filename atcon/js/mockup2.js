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
var result_perinfo = null;
var result_membership = null;
var max_perinfo_index = null;
var max_membership_index = null;
var unpaid_rows = 0;
var num_rows = 0;
var membership_rows = 0;
var needmembership_rows = 0;

// tab fields
var find_tab = null;
var add_tab = null;
var review_tab = null;
var pay_tab = null;
var print_tab = null;

// find people fields
var id_div = null;
var search_field = null;
var find_result_table = null;
var number_search = null;
var memLabel = null;
var find_unpaid_button = null;

// add new person fields
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
var add_mem_field = null;
var add_header = null;
var addnew_button = null;
var clearadd_button = null;
var add_results_table = null;
var add_results_div = null;
var add_mode = true;
var add_mem_select = null;
var add_mt_dataentry = `
    <select id='ae_mem_sel' name='age' style="width:300px;" tabindex='15' title='Age as of <?php echo substr($condata[' startdate'], 0, 10); ?> (the first day of the convention)'>
    </select>
`;

// review items
var review_div = null;
var country_select = null;

// pay items
var pay_div = null;
var pay_button_pay = null;
var pay_button_rcpt = null;
var pay_button_print = null;

// print items
var print_div = null;
var print_arr = null;

// Data tables
var datatbl = new Array();
var cart = new Array();
var cart_pmt = new Array();
var cart_perid = new Array();
var new_perid = -1;

// filter criteria
var filt_cat = null;  // array
var filt_age = null;  // array
var filt_type = null; // array
var filt_shortname_regexp = null; // regexp item;

window.onload = function initpage() {
    // map the memIds and labels for the pre-coded memberships.  Doing it now because it depends on what the datbase sends.
    // tabls

    memLabels = JSON.parse(memLabelsJSON);
    var row;
    var match;
    for (row in mockup_membership) {
        filt_cat = new Array(mockup_membership[row]['memCategory'].toLowerCase());
        filt_age = new Array(mockup_membership[row]['memAge'].toLowerCase());
        filt_type = new Array(mockup_membership[row]['memType'].toLowerCase())
        filt_shortname_regexp = new RegExp(mockup_membership[row]['shortname'], 'i');
        match = memLabels.filter(mem_filter);
        if (match.length > 0) {
            mockup_membership[row]['memId'] = match[0]['id'];
            mockup_membership[row]['shortname'] = match[0]['shortname'];
            mockup_membership[row]['label'] = match[0]['label'];
            mockup_membership[row]['price'] = match[0]['price'];
            if (mockup_membership[row]['paid'] > 0)
                mockup_membership[row]['paid'] = match[0]['price'];
        }
    }

    // build membership_select options
    filt_cat = new Array('standard', 'freebie', 'artist', 'dealer')
    filt_type = null;
    filt_age = null;
    filt_shortname_regexp = null;
    match = memLabels.filter(mem_filter);
    membership_select = '';
    for (row in match) {
        membership_select += '<option value="' + match[row]['id'] + '">' + match[row]['label'] + "</option>\n";
    }
    // upgrade_select
    filt_cat = new Array('upgrade')
    filt_age = null;
    filt_type = null;
    filt_shortname_regexp = null;
    match = memLabels.filter(mem_filter);
    upgrade_select = '';
    for (row in match) {
        upgrade_select += '<option value="' + match[row]['id'] + '">' + match[row]['label'] + "</option>\n";
    }
    // yearahead_select
    filt_cat = new Array('yearahead')
    filt_age = null;
    filt_type = null;
    filt_shortname_regexp = null;
    match = memLabels.filter(mem_filter);
    yearahead_select = '';
    for (row in match) {
        yearahead_select += '<option value="' + match[row]['id'] + '">' + match[row]['label'] + "</option>\n";
    }
    // addon_select
    filt_cat = new Array('addon', 'add-on')
    filt_age = null;
    filt_type = null;
    filt_shortname_regexp = null;
    match = memLabels.filter(mem_filter);
    addon_select = '';
    for (row in match) {
        addon_select += '<option value="' + match[row]['id'] + '">' + match[row]['label'] + "</option>\n";
    }

    // set up initial values
    result_perinfo = mockup_perinfo.slice(0);
    result_membership = mockup_membership.slice(0);
    max_perinfo_index = result_perinfo.length;
    max_membership_index = result_membership.length;

    find_tab = document.getElementById("find-tab");
    add_tab = document.getElementById("add-tab");
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
    id_div = document.getElementById("find_results");
    find_unpaid_button = document.getElementById("find_unpaid_btn");

    // add people
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
    add_share_field = document.getElementById("share_reg");
    add_header = document.getElementById("add_header");
    addnew_button = document.getElementById("addnew-btn");
    clearadd_button = document.getElementById("clearadd-btn");
    add_results_div = document.getElementById("add_results");
    add_mem_select = document.getElementById("ae_mem_select");

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

    clear_add();
    draw_cart();
}

    // result data
var mockup_perinfo = [
    {
        perid: 1, first_name: "John", middle_name: "Q.", last_name: "Smith", suffix: "", badge_name: "John Smith",
        address_1: "123 Any St", address_2: '', city: 'Philadelphia', state: 'PA', postal_code: '19101-0000', country: 'USA',
        email_addr: 'john.q.public@gmail.com', phone: '215-555-2368',
        share_reg: 'Y', contact_ok: 'Y', active: 'Y', banned: 'N', index: 0,
    },
    {
        perid: 2, first_name: "Jane", middle_name: "Q.", last_name: "Smith", suffix: "", badge_name: "Jane Smith",
        address_1: "123 Any St", address_2: '', city: 'Philadelphia', state: 'PA', postal_code: '19101-0000', country: 'USA',
        email_addr: 'jane.q.public@gmail.com', phone: '215-555-2368',
        share_reg: 'Y', contact_ok: 'Y', active: 'Y', banned: 'N', index: 1,
    },
    {
        perid: 3, first_name: "Amy", middle_name: "", last_name: "Jones", suffix: "", badge_name: "Lady Amy",
        address_1: "1023 Chestnut St", address_2: '', city: 'Philadelphia', state: 'PA', postal_code: '19103-0000', country: 'USA',
        email_addr: 'ladyamy@gmail.com', phone: '215-555-5432',
        share_reg: 'Y', contact_ok: 'Y', active: 'Y', banned: 'N', index: 2,
    },
    {
        perid: 4, first_name: "John", middle_name: "", last_name: "Doe", suffix: "", badge_name: "Unknown Attendee",
        address_1: "Unknown Monument", address_2: '', city: 'Philadelphia', state: 'PA', postal_code: '19103-0000', country: 'USA',
        email_addr: 'lost@aol.com', phone: '',
        share_reg: 'Y', contact_ok: 'Y', active: 'Y', banned: 'N', index: 3,
    },
    {
        perid: 5, first_name: "Bad", middle_name: "", last_name: "Member", suffix: "", badge_name: "Baddie",
        address_1: "Unknown Location", address_2: '', city: 'Philadelphia', state: 'PA', postal_code: '19103-0000', country: 'USA',
        email_addr: 'abuse@aol.com', phone: '',
        share_reg: 'N', contact_ok: 'N', active: 'Y', banned: 'Y', index: 4,
    },
    {
        perid: 6, first_name: "No", middle_name: "", last_name: "Membership", suffix: "", badge_name: "Just Person",
        address_1: "Unknown Location", address_2: '', city: 'Philadelphia', state: 'PA', postal_code: '19103-0000', country: 'USA',
        email_addr: 'abuse@aol.com', phone: '',
        share_reg: 'Y', contact_ok: 'Y', active: 'Y', banned: 'N', index: 5,
    },
    {
        perid: 7, first_name: "Day", middle_name: "", last_name: "Membership", suffix: "", badge_name: "Just Person",
        address_1: "Unknown Location", address_2: '', city: 'Philadelphia', state: 'PA', postal_code: '19103-0000', country: 'USA',
        email_addr: 'abuse@aol.com', phone: '',
        share_reg: 'Y', contact_ok: 'Y', active: 'Y', banned: 'N', index: 6,
    },
    {
        perid: 8, first_name: "Unpaid", middle_name: "A", last_name: "Membership", suffix: "", badge_name: "Unpaid A",
        address_1: "Unknown Location", address_2: '', city: 'Philadelphia', state: 'PA', postal_code: '19103', country: 'USA',
        email_addr: 'abuse@aol.com', phone: '215-555-2368',
        share_reg: 'Y', contact_ok: 'Y', active: 'Y', banned: 'N', index: 7,
    },
    {
        perid: 9, first_name: "Unpaid", middle_name: "B", last_name: "Membership", suffix: "", badge_name: "Unpaid B",
        address_1: "Unknown Location", address_2: '', city: 'Philadelphia', state: 'PA', postal_code: '19103', country: 'USA',
        email_addr: 'abuse@aol.com', phone: '215-555-2368',
        share_reg: 'Y', contact_ok: 'Y', active: 'Y', banned: 'N', index: 8,
    },
    {
        perid: 10, first_name: "Different", middle_name: "", last_name: "Unpaid", suffix: "", badge_name: "DIfferent Unpaid",
        address_1: "Unknown Location", address_2: '', city: 'Philadelphia', state: 'PA', postal_code: '19103', country: 'USA',
        email_addr: 'abuse@aol.com', phone: '215-555-2368',
        share_reg: 'Y', contact_ok: 'Y', active: 'Y', banned: 'N', index: 9,
    },
];

var mockup_membership = [
    {
        perid: 1, 
        reg_type: 'adult', price: 75, paid: 75, tid: 11, index: 0, printed: 0,
        memCategory: 'standard', memType: 'full', memAge: 'adult', shortname: 'General', pindex: 0,
        memId: null, label: null,
    },
    {
        perid: 2, 
        reg_type: 'adult', price: 75, paid: 75, tid: 11, index: 1, printed: 0,
        memCategory: 'standard', memType: 'full', memAge: 'adult', shortname: 'General', pindex: 1,
        memId: null, label: null,
    },
    {
        perid: 3, 
        reg_type: 'student', price: 40, paid: 40, tid: 13, index: 2, printed: 0,
        memCategory: 'standard', memType: 'full', memAge: 'student', shortname: 'Student', pindex: 2,
        memId: null, label: null,
    },
    {
        perid: 7,
        reg_type: 'Fri adult', price: 35, paid: 35, tid: '14', index: 3, printed: 0,
        memCategory: 'standard', memType: 'oneday', memAge: 'adult', shortname: 'Fri General', pindex: 6,
        memId: null, label: null,
    },
    {
        perid: 8,
        reg_type: 'adult', price: 75, paid: 0, tid: '15', index: 4, printed: 0,
        memCategory: 'standard', memType: 'full', memAge: 'adult', shortname: 'General', pindex: 7,
        memId: null, label: null,
    },
    {
        perid: 9,
        reg_type: 'student', price: 40, paid: 0, tid: '15', index: 5, printed: 0,
        memCategory: 'standard', memType: 'full', memAge: 'student', shortname: 'Student', pindex: 8,
        memId: null, label: null,
    },
    {
        perid: 10,
        reg_type: 'adult', price: 75, paid: 0, tid: '16', index: 6, printed: 0,
        memCategory: 'standard', memType: 'full', memAge: 'adult', shortname: 'General', pindex: 9,
        memId: null, label: null,
    },
];

// search memLabel functions
function mem_filter(cur, idx, arr) {
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

    return true;
}

var find_memid = null;
function memLabel_filter(cur, idx, arr) {
    return cur['id'] == find_memid;
}

function find_memLabel(id) {
    find_memid = id;
    var match = memLabels.filter(memLabel_filter);
    if (match.length > 0)
        return match[0];
    return null;
}

// search result_membership functions
var find_perid = null;
function rm_perid_filter(cur, idx, arr) {
    return cur['perid'] == find_perid;
}

function find_memberships_by_perid(perid) {
    find_perid = perid;
    var match = result_membership.filter(rm_perid_filter);
    return match;
}

function find_primary_membership_by_perid(perid) {
    var regitems = find_memberships_by_perid(perid);
    var mem_index = null;
    for (item in regitems) {
        memtype = regitems[item]['memCategory'];
        if (memtype == 'upgrade' || memtype == 'rollover' || memtype == 'freebie') {
            mem_index = regitems[item]['index'];
            break;
        }
        if (memtype == 'standard') {
            mem_index = regitems[item]['index'];
        }
    }
    return mem_index;
}

function void_trans() {
    start_over(0);
}

function start_over(reset_all) {
    // empty cart
    cart = new Array();
    cart_perid = new Array();
    cart_pmt = new Array();
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
    datatbl = new Array();

    // reset data to call up
    if (reset_all) {
        result_perinfo = mockup_perinfo.slice(0);
        result_membership = mockup_membership.slice(0);
        max_perinfo_index = result_perinfo.length;
        max_membership_index = result_membership.length;
    }

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

    clear_add();
    // set tab to find-tab
    bootstrap.Tab.getOrCreateInstance(find_tab).show();

    draw_cart();
}

function build_record_hover(e, cell, onRendered) {
    data = cell.getData();
    //console.log(data);
    hover_text = (data['first_name'] + ' ' + data['middle_name'] + ' ' + data['last_name']).trim() + '<br/>' +
        data['address_1'] + '<br/>';
    if (data['address_2'] != '') {
        hover_text += data['address_2'] + '<br/>';
    }
    hover_text += data['city'] + ', ' + data['state'] + ' ' + data['postal_code'] + '<br/>';
    if (data['country'] != '' && data['country'] != 'USA') {
        hover_text += data['country'] + '<br/>';
    }
    hover_text += 'Badge Name: ' + data['badge_name'] + '<br/>' +
        'Email: ' + data['email_addr'] + '<br/>' + 'Phone: ' + data['phone'] + '<br/>' +
        'Active:' + data['active'] + ' Contact?:' + data['contact_ok'] + ' Share?:' + data['share_reg'] + '<br/>' +
        'Membership: ' + data['reg_type'] + '<br/>';

    return hover_text;
}

function add_to_cart(index) {
    if (index >= 0) {
        if (result_perinfo[index]['banned'] == 'Y') {
            alert("Please ask " + (result_perinfo[index]['first_name'] + ' ' + result_perinfo[index]['last_name']).trim() +" to talk to the Registration Administrator, you cannot add them at this time.")
            return;
        }
        if (cart_perid.includes(result_perinfo[index]['perid']) == false) {
            cart.push(result_perinfo[index]['index']);
            cart_perid.push(result_perinfo[index]['perid'])
        }
    } else {
        var row;
        index = -index;
        for (row in result_membership) {
            if (result_membership[row]['tid'] == index) {
                prow = result_membership[row]['pindex'];
                if (result_perinfo[prow]['banned'] == 'Y') {
                    alert("Please ask " + (result_perinfo[prow]['first_name'] + ' ' + result_perinfo[prow]['last_name']).trim() + " to talk to the Registration Administrator, you cannot add them at this time.")
                    return;
                } else if (cart_perid.includes(result_perinfo[prow]['perid']) == false) {
                    cart.push(result_perinfo[prow]['index']);
                    cart_perid.push(result_perinfo[prow]['perid']);
                }
            }
        }
    }
    draw_cart();
}

function remove_from_cart(index) {
    cart.splice(index, 1);
    cart_perid.splice(index, 1);
    draw_cart();
}

function delete_membership(index) {
    result_membership.splice(index, 1);
    draw_cart();
}

function edit_from_cart(index) {
    clear_add();
    cartrow = result_perinfo[cart[index]];

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
    add_share_field.value = cartrow['share_reg'];

    // membership items - see if there is a membership item in the member list for this row
    var mem_index = find_primary_membership_by_perid(cartrow['perid']);
   
    if (mem_index == null) {
        // none found put in select
        add_mem_select.innerHTML = add_mt_dataentry;
        document.getElementById("ae_mem_sel").innerHTML = membership_select;
    } else {
        add_memIndex_field.value = mem_index;
        if (Number(result_membership[mem_index]['tid']) > 0) {
            // already paid, just display the lael
            add_mem_select.innerHTML = result_membership[mem_index]['label'];
        } else {
            add_mem_select.innerHTML = add_mt_dataentry;
            var mtel = document.getElementById("ae_mem_sel");
            mtel.innerHTML = membership_select;
            mtel.value = result_membership[mem_index]['memId'];
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
    bootstrap.Tab.getOrCreateInstance(add_tab).show();
}

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
    add_mem_select.innerHTML = add_mt_dataentry;
    add_mem_select.style.backgroundColor = '';
    document.getElementById("ae_mem_sel").innerHTML = membership_select;
    if (add_results_table != null) {
        add_results_table.destroy();
        add_results_table = null;
        add_results_div.innerHTML = "";
    };
    addnew_button.innerHTML = "Add to Cart";
    clearadd_button.innerHTML = 'Clear Add Person Form';
    add_mode = true;
}

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
        new_badgememId = bt_field.value.trim();;
    }
    var new_contact = add_contact_field.value.trim();
    var new_share = add_share_field.value.trim();

    if (add_mode == false && edit_index != '') { // update perinfo/meminfo and cart and cart
        var row = result_perinfo[edit_index];
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
        row['share_reg'] = new_share;
        row['contact_ok'] = new_contact;
        row['share_reg'] = new_share;
        row['active'] = 'Y';
        if (new_badgememId != null) {
            var mrow = null;
            if (new_memindex != '') {
                mrow = result_membership[new_memindex];
            } else {
                var ind = result_membership.length;
                result_membership.push({ index: ind, printed: 0 });
                mrow = result_membership[ind];
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
            mrow['label'] = mi_row['label'];
        }

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
        add_mem_select.innerHTML = add_mt_dataentry;
        add_mem_select.style.backgroundColor = '';
        document.getElementById("ae_mem_sel").innerHTML = membership_select;
        if (add_results_table != null) {
            add_results_table.destroy();
            add_results_table = null;
            add_results_div.innerHTML = "";
        };
        addnew_button.innerHTML = "Add to Cart";
        clearadd_button.innerHTML = 'Clear Add Person Form';
        draw_cart();
        return;
    }

    // see if they already exist (if add to cart)
    var rownum;
    var matchcount = 0;
    var matches = new Array();
    var namematch = new RegExp((new_first + '.*' + new_last).toLowerCase(), 'i');

    if (addnew_button.innerHTML == 'Add to Cart') {
        for (rownum in result_perinfo) {
            row = result_perinfo[rownum];
            var sourcestring = (row['first_name'] + ' ' + row['last_name']).toLowerCase();
            if (namematch.test(sourcestring)) {
                var matchrow = JSON.parse(JSON.stringify(row));  // horrible way to make an independent copy of an associative array
                matchrow['fullname'] = (row['last_name'] + ', ' + row['first_name'] + ' ' + row['middle_name'] + ' ' + row['suffix']).replace(/\s+/g, ' ').trim();
                var primmem = find_primary_membership_by_perid(matchrow['perid']);
                if (primmem != null) {
                    matchrow['reg_label'] = result_membership[primmem]['label'];
                    var tid = result_membership[primmem]['tid'];
                    if (tid != '') {
                        var other = false;
                        var mperid = matchrow['perid'];
                        for (var mem in result_membership) {
                            if (result_membership[mem]['perid'] != mperid && result_membership[mem]['tid'] == tid) {
                                other = true;
                                break;
                            }
                        }
                        if (other) {
                            matchrow['tid'] = tid;
                        }
                    }
                } else {
                    matchrow['reg_label'] = 'No Membership';
                    matchrow['reg_tid'] = '';
                }
                matches.push(matchrow);
                matchcount++;
            }
        }
    }

    if (matchcount > 0) {
        // table
        add_results_table = new Tabulator('#add_results', {
            maxHeight: "600px",
            data: matches,
            layout: "fitColumns",
            initialSort: [
                { column: "fullname", dir: "asc" },
                { column: "badge_name", dir: "asc" },
            ],
            columns: [
                { field: "perid", visible: false, },
                { title: "Name", field: "fullname", headerFilter: true, headerWordWrap: true, tooltip: build_record_hover, },
                { field: "last_name", visible: false, },
                { field: "first_name", visible: false, },
                { field: "middle_name", visible: false, },
                { field: "suffix", visible: false, },
                { title: "Badge Name", field: "badge_name", headerFilter: true, headerWordWrap: true, tooltip: true, },
                { title: "Zip", field: "postal_code", headerFilter: true, headerWordWrap: true, tooltip: true, maxWidth: 70, width: 70 },
                { title: "Email Address", field: "email_addr", headerFilter: true, headerWordWrap: true, tooltip: true, },
                { title: "Reg", field: "reg_label", headerFilter: true, headerWordWrap: true, tooltip: true, maxWidth: 80, width: 80, },
                { title: "Cart", width: 70, headerFilter: false, headerSort: false, formatter: addCartIcon, },
                { field: "index", visible: false, },
            ],
        });
        addnew_button.innerHTML = "Add New";
    } else {
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

        if (missing_fields > 0) {
            if (add_results_table != null) {
                add_results_table.destroy();
                add_results_table = null;
                add_results_div.includes = "";
                addnew_button.innerHTML = "Add to Cart";
            };
            add_header.innerHTML = `
<div class="col-sm-12 text-bg-warning mb-2">
        <div class="text-bg-warning m-2">
            Add New Person and Membership (* = Required Data)
        </div>
    </div>`;
            return;
        }
        ////var age = new_badgetype.replace(/.* /, '');
        var row = {
            perid: new_perid, first_name: new_first, middle_name: new_middle, last_name: new_last, suffix: new_suffix,
            badge_name: new_badgename,
            address_1: new_addr1, address_2: new_addr2, city: new_city, state: new_state, postal_code: new_postal_code,
            country: new_country, email_addr: new_email, phone: new_phone,
            share_reg: 'Y', contact_ok, new_contact, active: 'Y', banned: 'N', index: result_perinfo.length,

        };
        var memId = document.getElementById("ae_mem_sel").value;
        var mi_row = find_memLabel(memId);
        var mrow = {
            perid: new_perid,
            reg_type: mi_row[''], price: mi_row['price'], paid: 0, tid: '', index: result_membership.length, printed: 0,
            memCategory: mi_row['memCategory'], memType: mi_row['memType'], memAge: mi_row['memAge'],
            shortname: mi_row['shortname'], memId: memId, label: mi_row['label'], pindex: result_perinfo.length,
        }
        ////        mem_type: new_badgetype.replace(/_/g, ' '), reg_type: age, price: new_price, paid: 0, tid: 0, index: max_index,
        new_perid--;

        add_first_field.value = "";
        add_middle_field.value = "";
        add_email_field.value = "";
        add_phone_field.value = "";
        add_badgename_field.value = "";
        result_perinfo.push(row);
        result_membership.push(mrow);
        cart.push(row['index']);
        cart_perid.push(row['perid']);
        draw_cart();
        if (add_results_table != null) {
            add_results_table.destroy();
            add_results_table = null;
            add_results_div.innerHTML = "";
            addnew_button.innerHTML = "Add to Cart";
        };
        add_header.innerHTML = `
<div class="col-sm-12 text-bg-primary mb-2">
        <div class="text-bg-primary m-2">
            Add New Person and Membership
        </div>
    </div>`;
    }
}

function draw_cart_row(rownum) {
    var row = result_perinfo[cart[rownum]];
    var membername = (row['first_name'] + ' ' + row['middle_name'] + ' ' + row['last_name']).trim();
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
    var col1 = '';

    // now loop over the memberships
    for (membership in result_membership) {
        if (result_membership[membership]['perid'] == row['perid']) {
            mem_is_membership = false;
            mrow = result_membership[membership];
            col1 = (Number(mrow['tid']) > 0 || freeze_cart) ? '&nbsp;' :
                '<button type = "button" class="btn btn-small btn-secondary pt-0 pb-0 ps-1 pe-1 m-0" onclick = "delete_membership(' +
                membership + ')" >X</button >';
            
            switch (mrow['memCategory']) {
                case 'standard':
                    yearahead_eligible = true;
                    if (mrow['memType'] == 'oneday')
                        upgrade_eligible = true;
                    // no break - fall through
                case 'freebie':
                    mem_is_membership = true;
                    membership_html += `
    <div class="row">
        <div class="col-sm-1 p-0">` + col1 + `</div>
        <div class="col-sm-7 p-0">` + mrow['label'] + `</div>
        <div class="col-sm-2 text-end">` + mrow['price'] + `</div>
        <div class="col-sm-2 text-end">` + mrow['paid'] + `</div>
    </div>
`;
                    break;
                case 'upgrade':
                    mem_is_membership = true;
                    yearahead_eligible = true;
                    upgrade_eligible = false;
                    upgrade_html += `
    <div class="row">
        <div class="col-sm-1 p-0">` + col1 + `</div>
        <div class="col-sm-7 p-0">` + mrow['label'] + `</div>
        <div class="col-sm-2 text-end">` + mrow['price'] + `</div>
        <div class="col-sm-2 text-end">` + mrow['paid'] + `</div>
    </div>
`;
                    break;
                case 'yearahead':
                    yearahead_html += `
    <div class="row">
        <div class="col-sm-1 p-0">` + col1 + `</div>
        <div class="col-sm-7 p-0">` + mrow['label'] + `</div>
        <div class="col-sm-2 text-end">` + mrow['price'] + `</div>
        <div class="col-sm-2 text-end">` + mrow['paid'] + `</div>
    </div>
`;
                    break;
                case 'rollver':
                    membership_found = true;
                    yearahead_eligible = true;
                    rollover_html += `
    <div class="row">
        <div class="col-sm-1 p-0">` + col1 + `</div>
        <div class="col-sm-7 p-0">` + mrow['label'] + `</div>
        <div class="col-sm-2 text-end">` + mrow['price'] + `</div>
        <div class="col-sm-2 text-end">` + mrow['paid'] + `</div>
    </div>
`;
                    break;
                case 'addon':
                case 'add-on':
                    rowlabel = 'Addon:';
                    addon_html += `
    <div class="row">
        <div class="col-sm-1 p-0">` + col1 + `</div>
        <div class="col-sm-7 p-0">` + mrow['label'] + `</div>
        <div class="col-sm-2 text-end">` + mrow['price'] + `</div>
        <div class="col-sm-2 text-end">` + mrow['paid'] + `</div>
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
        <div class="col-sm-2 p-0 text-center"><button type="button" class="btn btn-small btn-secondary pt-0 pb-0 ps-1 pe-1" onclick="edit_from_cart(` + rownum + `)">Edit</button></div>
        <div class="col-sm-2 p-0 text-center"><button type="button" class="btn btn-small btn-secondary pt-0 pb-0 ps-1 pe-1" onclick="remove_from_cart(` + rownum + `)">Remove</button></div>
`;
    }
    rowhtml += '</div>'; // end of member name row

    // second row - badge namee
    rowhtml += `
    <div class="row">
        <div class="col-sm-3 p-0">Badge Name:</div>
        <div class="col-sm-auto p-0">` + row['badge_name'] + `</div>
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
` + upgrade_select + `
            </select>
        </div>
        <div class="col-sm-2 p-0 text-center"><button type="button" class="btn btn-small btn-secondary pt-0 pb-0 ps-1 pe-1" onclick="add_membership_cart(` + rownum + ", 'cart-mupg-" + rownum + `')">Add</button></div >
</div>
`;
    }

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

    if (membership_found)
        membership_rows++
    else
        needmembership_rows++;

    return rowhtml;
}

function draw_cart_pmtrow(prow) {
 //   index: cart_pmt.length, amt: pay_amt, ccauth: ccauth, checkno: checkno, desc: eldesc.value, type: ptype,

    var pmt = cart_pmt[prow];
    code = '';
    if (pmt['type'] == 'Check') {
        code = pmt['checkno'];
    } else if (pmt['type'] == 'Credit Card') {
        code = pmt['ccauth'];
    }
    var html = `<div class="row">
    <div class="col-sm-2 p-0">` + pmt['type'] + `</div>
    <div class="col-sm-6 p-0">` + pmt['desc'] + `</div>
    <div class="col-sm-2 p-0">` + code + `</div>
    <div class="col-sm-2 text-end">` + pmt['amt'] + `</div>
</div>
`;
    return html;
}

function draw_cart() {
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
    for (rownum in cart) {
        num_rows++;     
        html += draw_cart_row(rownum);   
    }
    html += `<div class="row">
    <div class="col-sm-8 p-0 text-end">Total:</div>
    <div class="col-sm-2 text-end">$` + total_price + `</div>
    <div class="col-sm-2 text-end">$` + total_paid + `</div>
</div>
`;
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
    <div class="col-sm-8 p-0 text-end">Payment Total:</div>
    <div class="col-sm-4 text-end">$` + total_pmt + `</div>
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

function draw_record(row, first) {
    var data = result_perinfo[row];
    var prim = find_primary_membership_by_perid(data['perid']);
    var label = "No Membership";
    if (prim != null) {
        label = result_membership[prim]['label'];
    }
    var html = `
<div class="container-fluid">
    <div class="row mt-2">
        <div class="col-sm-3">`;
    if (first) {
        html += `<button class="btn btn-primary btn-small" id="add_btn_all" onclick="add_to_cart(-` + number_search + `);">Add All Cart</button>`;
    }
    html += `</div>
        <div class="col-sm-9">`;
    if (cart_perid.includes(data['perid']) == false) {
        if (data['banned'] == 'Y') {
            html += `
            <button class="btn btn-danger btn-small" id="add_btn_1" onclick="add_to_cart(` + row + `);">B</button>`;
        } else {
            html += `
            <button class="btn btn-success btn-small" id="add_btn_1" onclick="add_to_cart(` + row + `);">Add to Cart</button>`;
        }
    } else {
        html += `
            <i>In Cart</i>`
    }
        html += `
        </div>
        <div class="row">
            <div class="col-sm-3">` + 'Badge Name:' + `</div>
            <div class="col-sm-9">` + data['badge_name'] + `</div>
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
       <div class="col-sm-auto">Share Reg: ` + data['share_reg'] + `</div>
    </div>
    <div class="row">
       <div class="col-sm-3">Membership Type:</div>
       <div class="col-sm-9">` + label + `</div>
    </div>
`;
    return html;
}

function addCartIcon(cell, formatterParams, onRendered) { //plain text value
    var html = '';
    var banned = cell.getRow().getData().banned;
    if (banned == undefined) {
        var tid = Number(cell.getRow().getData().tid);
        html = '<button type="button" class="btn btn-sm btn-success p-0" onclick="add_unpaid(' + tid + ')">Pay</button > ';
        return html;
    }
    if (banned == 'Y') {
        return '<button type="button" class="btn btn-sm btn-danger pt-0 pb-0">B</button>';
    } else if (cart_perid.includes(cell.getRow().getData().perid) == false) {
        html = '<button type="button" class="btn btn-sm btn-success p-0" onclick="add_to_cart(' +
            cell.getRow().getData().index + ')">Add</button>';
        var tid = cell.getRow().getData().tid;
        if (tid != '' && tid != undefined && tid != null) {
            html += '&nbsp;<button type="button" class="btn btn-sm btn-success p-0" onclick="add_to_cart(' + (-tid) + ')">Tran</button>';
        }
        return html;
    }
    return '<span style="font-size: 75%;">In Cart';
};

function add_unpaid(tid) {
    add_to_cart(-Number(tid));
    bootstrap.Tab.getOrCreateInstance(pay_tab).show();
}

function upgrade_membership_cart(rownum, selectname) {
    var select = document.getElementById(selectname);
    var badgetype = select.value.trim();
    var price = Number(select.options[select.selectedIndex].innerHTML.replace(/.*\(/, '').replace(/\).*/, '').replace(/\$/, ''));

    row['mem_type'] = badgetype.replace(/_/g, ' ');
    row['reg_type'] = row['mem_type'].replace(/.* /, '');
    row['price'] = price;
    row['paid'] = 0;
    row['tid'] = '';
    draw_cart();
}

function add_membership_cart(rownum, selectname) {
    var select = document.getElementById(selectname);
    var membership = find_memLabel(select.value.trim());
    var row = result_perinfo[cart[rownum]];
   
    result_membership.push({
        perid: row['perid'],
        reg_type: membership['memType'],
        price: membership['price'],
        paid: 0,
        tid: '',
        index: result_membership.length,
        printed: 0,
        memCategory: membership['memCategory'],
        memType: membership['memType'],
        memAge: membership['memAge'],
        shortname: membership['shortname'],
        pindex: row['index'],
        memId: membership['id'],
        label: membership['label']
    });
  
    draw_cart();
}

function find_record(find_type) {
    if (find_result_table != null) {
        find_result_table.destroy();
        find_result_table = null;
    }
    var html = '';

    datatbl = new Array();
    id_div.innerHTML = "";

    if (find_type == 'unpaid') {
        var mdatatbl = result_membership.filter(function (e) { return Number(e['paid']) != Number(e['price']); });
        if (mdatatbl.length == 0) { // no unpaid records
            id_div.innerHTML = 'No unpaid records found';
            return;
        }
        var trantbl = new Array();
        // loop over unpaid memberships and find differing transactions
        for (var mrow in mdatatbl) {
            var tid = mdatatbl[mrow]['tid'];
            if (!trantbl.includes(tid)) {
                trantbl.push(tid);
            }
        }
        if (trantbl.length == 1) { // only 1 row, add it to the card and go to pay tab
            var tid = trantbl[0];
            for (var row in result_membership) {
                if (result_membership[row]['tid'] == tid) {
                    var index = result_membership[row]['pindex'];
                    add_to_cart(index);
                }
            }
            bootstrap.Tab.getOrCreateInstance(pay_tab).show();
            return;
        }
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
            datatbl.push(row);
        }
        find_result_table = new Tabulator('#find_results', {
            maxHeight: "600px",
            data: datatbl,
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
                { title: "Cart", width: 40, formatter: addCartIcon, headerSort: false, },
                { field: "index", visible: false, },
            ],
        });
        return;
    }
    name_search = find_pattern.value.toLowerCase().trim();
    if (name_search == null)
        name_search = '';

    if (isNaN(name_search)) {
        var ns_regexp = new RegExp(name_search.replace(/ /g, '.*'), 'i');
        // mockup of name search results
        for (rowindex in result_perinfo) {
            var row = result_perinfo[rowindex];
            var sourcestring = row['first_name'] + ' ' + row['last_name'] + ' ' + row['badge_name'] + ' ' + row['email_addr'];
         
            if (ns_regexp.test(sourcestring)) {
                var matchrow = JSON.parse(JSON.stringify(row));  // horrible way to make an independent copy of an associative array
                matchrow['fullname'] = (row['last_name'] + ', ' + row['first_name'] + ' ' + row['middle_name'] + ' ' + row['suffix']).replace(/\s+/g, ' ').trim();
                var primmem = find_primary_membership_by_perid(matchrow['perid']);
                if (primmem != null) {
                    matchrow['reg_label'] = result_membership[primmem]['label'];
                    var tid = result_membership[primmem]['tid'];
                    if (tid != '') {
                        var other = false;
                        var mperid = matchrow['perid'];
                        for (var mem in result_membership) {
                            if (result_membership[mem]['perid'] != mperid && result_membership[mem]['tid'] == tid) {
                                other = true;
                                break;
                            }
                        }
                        if (other) {
                            matchrow['tid'] = tid;
                        }                        
                    }
                } else {
                    matchrow['reg_label'] = 'No Membership';
                    matchrow['reg_tid'] = '';
                }
                datatbl.push(matchrow);
            }
         }

        if (datatbl.length > 0) {
            // table
            find_result_table = new Tabulator('#find_results', {
                maxHeight: "600px",
                data: datatbl,
                layout: "fitColumns",
                initialSort: [
                    { column: "fullname", dir: "asc" },                   
                    ],
                columns: [
                    { field: "perid", visible: false, },
                    { title: "Name", field: "fullname", headerFilter: true, headerWordWrap: true, tooltip: build_record_hover, },
                    { field: "last_name", visible: false, },
                    { field: "first_name", visible: false, },
                    { field: "middle_name", visible: false, },
                    { field: "suffix", visible: false, },
                    { title: "Badge Name", field: "badge_name", headerFilter: true, headerWordWrap: true, tooltip: true, },
                    { title: "Zip", field: "postal_code", headerFilter: true, headerWordWrap: true, tooltip: true, maxWidth: 70, width: 70 },
                    { title: "Email Address", field: "email_addr", headerFilter: true, headerWordWrap: true, tooltip: true, },
                    { title: "Reg", field: "reg_label", headerFilter: true, headerWordWrap: true, tooltip: true, maxWidth: 80, width: 80, },
                    { title: "Cart", width: 70, headerFilter: false, headerSort: false, formatter: addCartIcon, },
                    { field: "index", visible: false, },
                ],
            });
        } else {
            id_div.innerHTML = `<div class="container-fluid">
<div class="row mt-3">
    <div class="col-sm-4">No matching records found</div>
    <div class="col-sm-auto"><button class="btn btn-primary btn-small" type="button" id="not_found_add_new" onclick="not_found_add_new();">Add New Person</button>
    </div>
</div>
</div>
`;
        }
        return;
    }

    number_search = Number(name_search);

    if (number_search > 0) {
        html = '';
        for (row in result_perinfo) {
            if (result_perinfo[row]['perid'] == number_search) {
                html += draw_record(row, false);
            }
        }
        if (html != '') {
            html += '</div>';
            id_div.innerHTML = html;
            return;
        }
   
        var first = true;
        for (row in result_membership) {
            if (result_membership[row]['tid'] == number_search) {
                prow = result_membership[row]['pindex'];
                html += draw_record(prow, first);
                first = false;
            }
        }
        if (html != '') {
            html += `
</div>`;
            id_div.innerHTML = html;
        } else {
            id_div.innerHTML = 'No matching records found'
        }

        return;
    }

    id_div.innerHTML = "No search criteria specified";
}

function not_found_add_new() {
    id_div.innerHTML = '';
    pattern_field.value = '';

    bootstrap.Tab.getOrCreateInstance(add_tab).show();
}

function start_review() {
    // set tab to review-tab
    bootstrap.Tab.getOrCreateInstance(review_tab).show();
    review_tab.disabled = false;  
}

function review_update() {
// loop over cart looking for changes in data table
    var rownum = null;
    var index;
    var data_row
    var el;
    var field;
    for (rownum in cart) {
        index = cart[rownum];        
        // update all the fields on the review page
        for (field in result_perinfo[index]) {
            el = document.getElementById('c' + rownum + '-' + field);
            if (el) {
                if (result_perinfo[index][field] != el.value) {
                   // alert("updating  row " + rownum + ":" + index + ":" + field + " from '" + result_perinfo[index][field] + "' to '" + el.value + "'");
                    result_perinfo[index][field] = el.value;
                }
            }
        }

    }
    review_shown();
}

function review_nochanges() {
    // set tab to review-tab
    if (unpaid_rows == 0) {
        goto_print();
    } else {
        bootstrap.Tab.getOrCreateInstance(pay_tab).show();
    }
}

function goto_print() {  
    print_arr = null;
    bootstrap.Tab.getOrCreateInstance(print_tab).show();    
}

function pay_type(ptype) {
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

function pay() {
    var checked = false;
    var rownum = null;
    var mrows = null;
    var mrownum = null;
    var ccauth = null;
    var checkno = null;
    var desc = null;
    var ptype = null;

    var elamt = document.getElementById('pay-amt');
    var pay_amt = Number(elamt.value);
    if (pay_amt <= 0) {
        elamt.style.backgroundColor = 'var(--bs-warning)';
        return;
    }
    elamt.style.backgroundColor = '';

    var elptdiv = document.getElementById('pt-div');
    elptdiv.style.backgroundColor = '';

    var eldesc = document.getElementById('pay-desc');
    if (document.getElementById('pt-discount').checked) {
        ptype = 'Discount';
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
        ptype = 'Check';
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
        ptype = 'Credit Card';
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
        ptype = 'Cash';
        checked = true;
    }

   
    if (!checked) {
        elptdiv.style.backgroundColor = 'var(--bs-warning)';
        return;
    }
    if (pay_amt > 0) {
        var prow = {
            index: cart_pmt.length, amt: pay_amt, ccauth: ccauth, checkno: checkno, desc: eldesc.value, type: ptype,
        };
        cart_pmt.push(prow);
    }

    for (rownum in cart) {
        mrows = find_memberships_by_perid(result_perinfo[cart[rownum]]['perid']);
        for (mrownum in mrows) {
            mrow = result_membership[mrows[mrownum]['index']];

            if (mrow['paid'] < mrow['price']) {
                amt = Math.min(pay_amt, mrow['price'] - mrow['paid']);
                mrow['paid'] += amt;
                pay_amt -= amt;
                if (pay_amt <= 0) break;
            }
        }
    }

    pay_shown();
}

function print_receipt() {
    var d = new Date();

    var html = 'Receipt for payment to ' + conid + ' at ' + d.toLocaleString() + `
<div class="container-fluid">
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
    <div class="col-sm-8 p-0 text-end">Payment Total:</div>
    <div class="col-sm-4 text-end">$` + total_pmt + `</div>
</div>
</div>
`;
        document.getElementById('pay_status').innerHTML = html;
}

function print_badge(index) {
    var rownum = null;
    var mrow = null;
    var row = null;
    
    var pt_html = '';

    if (index >= 0) {
        row = result_perinfo[index];
        mrow = find_primary_membership_by_perid(row['perid']);        
        if (print_arr.includes(index)) {
            result_membership[mrow]['printed']++;
            print_arr = print_arr.filter(function (el) { return el != index });
        }
        pt_html += '<br/>' + row['badge_name'] + ' printed';
    } else {
        for (rownum in cart) {
            row = result_perinfo[cart[rownum]];
            mrow = find_primary_membership_by_perid(row['perid']);
            if (print_arr.includes(row['index'])) {        
                result_membership[mrow]['printed']++;
                print_arr = print_arr.filter(function (el) { return el != mrow });
            }
            pt_html += '<br/>' + row['badge_name'] + ' printed';
        }
    }
    print_shown();
    document.getElementById('pt-status').innerHTML = pt_html;
}

// tab shown events
function find_shown(current, previous) {
    in_review = false;
    freeze_cart = false;
    draw_cart();
}

function add_shown(current, previous) {
    in_review = false;
    freeze_cart = false;
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
    for (rownum in cart) {
        row = result_perinfo[cart[rownum]];
        mrow = find_primary_membership_by_perid(row['perid']);
        review_html += `<div class="row">
        <div class="col-sm-1 m-0 p-0">Mbr ` + (Number(rownum) + 1) + '</div>';
        if (mrow == null) {
            review_html += '<div class="col-sm-8 text-bg-info">No Membership</div>';
        } else {
            review_html += '<div class="col-sm-8 text-bg-success">Membership: ' + result_membership[mrow]['label'] + '</div>';
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
        <div class="col-sm-1 m-0 p-0">EP:</div>
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
            <select name='c` + rownum + `-share_reg' id='c` + rownum + `-share_reg' tabindex='11'>
               <option value="Y" ` + (row['share_reg'] == 'Y' ? 'selected' : '')+ `>Y</option>
               <option value="N" ` + (row['share_reg'] == 'N' ? 'selected' : '') + `>N</option>
            </select>
        </div>
        <div class="col-sm-auto ms-0 me-2 p-0">Share Reg?</div>
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
  </form>
</div>
`
    in_review = true;
    freeze_cart = false;
    review_div.innerHTML = review_html;
    draw_cart();
}

function pay_shown(current, previous) {
    in_review = false;
    freeze_cart = true;
    draw_cart();
    if (total_paid == total_price) {
        // nothing more to pay       
        print_tab.disabled = false;
        next_button.hidden = false;
        if (pay_button_pay != null) { 
            pay_button_pay.hidden = true;
            pay_button_rcpt.hidden = false;
            pay_button_print.hidden = false;
        }        
    } else {
        if (pay_button_pay != null) {
            pay_button_pay.hidden = false;
            pay_button_rcpt.hidden = true;
            pay_button_print.hidden = true;
        }
        var total_amount_due = total_price - total_paid;

        var pay_html = `
<div id='payBody' class="container-fluid form-floating">
  <form id='payForm' action='javascript: return false; ' class="form-floating">
    <div class="row">
        <div class="col-sm-2 ms-0 me-2 p-0">Amount Due:</div>
        <div class="col-sm-auto m-0 p-0 ms-0 me-2 p-0">$` + total_amount_due + `</div>
    </div>
    <div class="row">
        <div class="col-sm-2 ms-0 me-2 p-0">Amount Paid:</div>
        <div class="col-sm-auto m-0 p-0 ms-0 me-2 p-0"><input type="number" class="no-spinners" id="pay-amt" name-"paid-amt size="6"/></div>
    </div>
    <div class="row">
        <div class="col-sm-2 m-0 mt-2 me-2 mb-2 p-0">Payment Type:</div>
        <div class="col-sm-auto m-0 mt-2 p-0 ms-0 me-2 mb-2 p-0" id="pt-div">
            <input type="radio" id="pt-credit" name="payment_type" value="credit" onchange='pay_type("credit");'/>
            <label for="pt-credit">Credit Card</label>
            &nbsp;&nbsp;&nbsp;&nbsp;<input type="radio" id="pt-check" name="payment_type" value="check" onchange='pay_type("check");'/>
            <label for="pt-check">Check</label>
            &nbsp;&nbsp;&nbsp;&nbsp;<input type="radio" id="pt-cash" name="payment_type" value="cash" onchange='pay_type("cash");'/>
            <label for="pt-cash">Cash</label>
            &nbsp;&nbsp;&nbsp;&nbsp;<input type="radio" id="pt-discount" name="payment_type" value="discount" onchange='pay_type("discount");'/>
            <label for="pt-discount">Discount</label>
        </div>
    </div>
    <div class="row" id="pay-check-div" hidden>
        <div class="col-sm-2 ms-0 me-2 p-0">Check Number:</div>
        <div class="col-sm-auto m-0 p-0 ms-0 me-2 p-0"><input type="text" size="8" maxlength="10" name="pay-checkno" id="pay-checkno"/></div>
    </div>
    <div class="row" id="pay-ccauth-div" hidden>
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
            <button class="btn btn-primary btn-small" type="button" id="pay-btn-pay" onclick="pay();">Confirm Pay</button>
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
    var new_print = false;
    if (print_arr == null) {
        new_print = true;
        print_arr = new Array();
    }
    draw_cart();

    var print_html = `<div id='printBody' class="container-fluid form-floating">
`;
    var rownum;
    var crow;
    for (rownum in cart) {
        crow = result_perinfo[cart[rownum]];
        mrow = find_primary_membership_by_perid(crow['perid']);
        if (new_print) {
            print_arr.push(crow['index']);
        }
        print_html += `
    <div class="row">
        <div class="col-sm-2 ms-0 me-2 p-0">
            <button class="btn btn-primary btn-small" type="button" id="pay-print-` + cart[rownum]['index'] + `" onclick="print_badge(` + crow['index'] + `);">Print</button>
        </div>
        <div class="col-sm-auto ms-0 me-2 p-0">            
            <span class="text-bg-success"> Membership: ` + result_membership[mrow]['label'] + `</span> (Times Printed: ` +
            result_membership[mrow]['printed'] + `)<br/>
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