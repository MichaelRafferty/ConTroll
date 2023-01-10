// cart fields
var void_button = null;
var startover_button = null;
var review_button = null;
var cart_div = null;
var cart_div = null;
var in_review = false;

// cart items
var membership_select = null;

// tab fields
var find_tab = null;
var add_tab = null;
var review_tab = null;
var pay_tab = null;
var print_tab = null;

// find people fields
var id_div = null;
var name_field = null;
var perid_field = null;
var transid_field = null;
var find_result_table = null;

// add new person fields
var add_first_field = null;
var add_middle_field = null;
var add_last_field = null;
var add_addr1_field = null;
var add_addr2_field = null;
var add_city_field = null;
var add_state_field = null;
var add_postal_code_field = null;
var add_country_field = null;
var add_email_field = null;
var add_phone_field = null;
var add_badgename_field = null;
var add_badgetype_field = null;
var add_contact_field = null;
var add_header = null;
var addnew_button = null;
var add_results_table = null;
var add_results_div = null;

// Data tables
var datatbl = new Array();
var cart = new Array();
var cart_perid = new Array();
var new_perid = -1;

window.onload = function initpagbe() {
    // tabls
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
    complete_button = document.getElementById("complete_btn");
    membership_select = document.getElementById("memType").innerHTML;
    upgrade_select = membership_select.split("\n").filter((element) => element.includes("Upgrade")).join("\n");

    // find people
    name_field = document.getElementById("find_name");
    id_div = document.getElementById("find_results");
    perid_field = document.getElementById("find_perid");
    transid_field = document.getElementById("find_transid");

    // add people
    add_first_field = document.getElementById("fname");
    add_middle_field = document.getElementById("mname");
    add_last_field = document.getElementById("lname");
    add_addr1_field = document.getElementById("addr");
    add_addr2_field = document.getElementById("addr2");
    add_city_field = document.getElementById("city");
    add_state_field = document.getElementById("state");
    add_postal_code_field = document.getElementById("zip");
    add_country_field = document.getElementById("country");
    add_email_field = document.getElementById("email");
    add_phone_field = document.getElementById("phone");
    add_badgename_field = document.getElementById("badgename");
    add_badgetype_field = document.getElementById("memType");
    add_contact_field = document.getElementById("contact_ok");
    add_header = document.getElementById("add_header");
    addnew_button = document.getElementById("addnew-btn");
    add_results_div = document.getElementById("add_results")

    // add events
    find_tab.addEventListener('shown.bs.tab', find_shown)
    add_tab.addEventListener('shown.bs.tab', add_shown)
    review_tab.addEventListener('shown.bs.tab', review_shown)
    pay_tab.addEventListener('shown.bs.tab', pay_shown)
    print_tab.addEventListener('shown.bs.tab', print_shown)

    draw_cart();
}

function start_over() {
    // empty cart
    cart = new Array();
    cart_perid = new Array();
    // empty search strings and results
    name_field.value = "";
    perid_field.value = "";
    transid_field.value = "";
    if (find_result_table != null) {
        find_result_table.destroy();
        find_result_table = null;
    }
    id_div.innerHTML = "";
    datatbl = new Array();
    // reset data to call up
    // result_data = mockup_data;
    // max_index = result_data.length;
    // reset tabs to initial values
    find_tab.disabled = false;
    add_tab.disabled = false;
    review_tab.disabled = true;
    pay_tab.disabled = true;
    print_tab.disabled = true;
    in_review = false;

    clear_add();
    // set tab to find-tab    
    bootstrap.Tab.getOrCreateInstance(find_tab).show();

    draw_cart();
}


// result data
var mockup_data = [
    {
        perid: 1, first_name: "John", middle_name: "Q.", last_name: "Smith", badge_name: "John Smith",
        address_1: "123 Any St", address_2: '', city: 'Philadelphia', state: 'PA', postal_code: '19101-0000', country: 'USA',
        email_addr: 'john.q.public@gmail.com', phone: '215-555-2368',
        share_reg: 'Y', contact_ok: 'Y', active: 'Y', banned: 'N',
        mem_type: 'standard full adult', reg_type: 'adult', price: 75, paid: 75, tid: 11, index:0, 
    },
    {
        perid: 2, first_name: "Jane", middle_name: "Q.", last_name: "Smith", badge_name: "Jane Smith",
        address_1: "123 Any St", address_2: '', city: 'Philadelphia', state: 'PA', postal_code: '19101-0000', country: 'USA',
        email_addr: 'jane.q.public@gmail.com', phone: '215-555-2368',
        share_reg: 'Y', contact_ok: 'Y', active: 'Y', banned: 'N',
        mem_type: 'standard full adult', reg_type: 'adult', price: 75, paid: 75, tid: 11, index:1,
    },
    {
        perid: 3, first_name: "Amy", middle_name: "", last_name: "Jones", badge_name: "Lady Amy",
        address_1: "1023 Chestnut St", address_2: '', city: 'Philadelphia', state: 'PA', postal_code: '19103-0000', country: 'USA',
        email_addr: 'ladyamy@gmail.com', phone: '215-555-5432',
        share_reg: 'Y', contact_ok: 'Y', active: 'Y', banned: 'N',
        mem_type: 'standard full student', reg_type: 'student', price: 40, paid: 40, tid: 13, index:2,
    },
    {
        perid: 4, first_name: "John", middle_name: "", last_name: "Doe", badge_name: "Unknown Attendee",
        address_1: "Unknown Monument", address_2: '', city: 'Philadelphia', state: 'PA', postal_code: '19103-0000', country: 'USA',
        email_addr: 'lost@aol.com', phone: '',
        share_reg: 'Y', contact_ok: 'Y', active: 'Y', banned: 'N',
        mem_type: '', reg_type: '', price: 0, paid: 0, tid: '', index: 3,
    },
    {
        perid: 5, first_name: "Bad", middle_name: "", last_name: "Mewber", badge_name: "Baddie",
        address_1: "Unknown Location", address_2: '', city: 'Philadelphia', state: 'PA', postal_code: '19103-0000', country: 'USA',
        email_addr: 'abuse@aol.com', phone: '',
        share_reg: 'N', contact_ok: 'N', active: 'Y', banned: 'Y',
        mem_type: '', reg_type: '', price: 0, paid: 0, tid: '', index: 4,
    },
    {
        perid: 6, first_name: "No", middle_name: "", last_name: "Membership", badge_name: "Just Person",
        address_1: "Unknown Location", address_2: '', city: 'Philadelphia', state: 'PA', postal_code: '19103-0000', country: 'USA',
        email_addr: 'abuse@aol.com', phone: '',
        share_reg: 'Y', contact_ok: 'Y', active: 'Y', banned: 'N',
        mem_type: '', reg_type: '', price: 0, paid: 0, tid: '', index: 5,
    },
    {
        perid: 7, first_name: "Day", middle_name: "", last_name: "Membership", badge_name: "Just Person",
        address_1: "Unknown Location", address_2: '', city: 'Philadelphia', state: 'PA', postal_code: '19103-0000', country: 'USA',
        email_addr: 'abuse@aol.com', phone: '',
        share_reg: 'Y', contact_ok: 'Y', active: 'Y', banned: 'N',
        mem_type: 'standard oneday adult', reg_type: 'Fri adult', price: 35, paid: 35, tid: '14', index: 6,
    },
];
var result_data = mockup_data;
var max_index = result_data.length;

function build_record_hover(e, cell, onRendered) {
    data = cell.getData();
    //console.log(data);
    hover_text = data['first_name'] + ' ' + data['middle_name'] + ' ' + data['last_name'] + '<br/>' +
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
        'Active:' + data['active'] + ' Contact?:' + data['contact_ok'] + ' Share?:' + data['share_reg'] + ' Banned:' + data['banned'] + '<br/>' +
        'Membership: ' + data['reg_type'] + '<br/>';

    return hover_text;
}

function add_to_cart(index) {
    if (index >= 0) {
        if (result_data[index]['banned'] == 'Y') {
            alert("Please as " + (result_data[index]['first_name'] + ' ' + result_data[index]['last_name']).trim() +" to talk to the Registration Administrator, you cannot add them at this time.")
            return;
        }
        if (cart_perid.includes(result_data[index]['perid']) == false) {
            cart.push(result_data[index]);
            cart_perid.push(result_data[index]['perid'])
        }
    } else {
        var row;
        index = -index;
        for (row in result_data) {
            if (result_data[row]['tid'] == index) {
                if (result_data[row]['banned'] == 'Y') {
                    alert("Please as " + (result_data[row]['first_name'] + ' ' + result_data[row]['last_name']).trim() + " to talk to the Registration Administrator, you cannot add them at this time.")
                    return;
                } else if (cart_perid.includes(result_data[row]['perid']) == false) {
                    cart.push(result_data[row]);
                    cart_perid.push(result_data[row]['perid']);
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

function clear_add() {
    add_first_field.value = "";
    add_middle_field.value = "";
    add_last_field.value = "";
    add_addr1_field.value = "";
    add_addr2_field.value = "";
    add_city_field.value = "";
    add_state_field.value = "";
    add_postal_code_field.value = "";
    add_country_field.value = "";
    add_email_field.value = "";
    add_phone_field.value = "";
    add_badgename_field.value = "";
    add_badgetype_field.selectedIndex = 0;
    add_contact_field.selectedIndex = 0;
    add_country_field.selectedIndex = 0;
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
    add_badgetype_field.style.backgroundColor = '';
    if (add_results_table != null) {
        add_results_table.destroy();
        add_results_table = null;
        add_results_div.innerHTML = "";
    };
    addnew_button.innerHTML = "Add to Cart";
}

function add_new() {
    var new_first = add_first_field.value.trim();
    var new_middle = add_middle_field.value.trim();
    var new_last = add_last_field.value.trim();
    var new_addr1 = add_addr1_field.value.trim();
    var new_addr2 = add_addr2_field.value.trim();
    var new_city = add_city_field.value.trim();
    var new_state = add_state_field.value.trim();
    var new_postal_code = add_postal_code_field.value.trim();
    var new_country = add_country_field.value.trim();
    var new_email = add_email_field.value.trim();
    var new_phone = add_phone_field.value.trim();
    var new_badgename = add_badgename_field.value.trim();
    var new_badgetype = add_badgetype_field.value.trim();
    var new_price = Number(add_badgetype_field.options[add_badgetype_field.selectedIndex].innerHTML.replace(/.*\(/, '').replace(/\).*/, '').replace(/\$/, ''));
    var new_contact = add_contact_field.value.trim();

    // see if they already exist (if add to cart)
    var rownum;
    var matchcount = 0;
    var matches = new Array();
    var namematch = new RegExp(('^' + new_first + '.*' + new_last + '$').toLowerCase());

    if (addnew_button.innerHTML == 'Add to Cart') {
        for (rownum in result_data) {
            row = result_data[rownum];

            if (namematch.test((row['first_name'] + ' ' + row['last_name']).toLowerCase())) {
                matches.push(row);
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
            columns: [
                {
                    title: 'Potential Matches, use "Cart" column to selct or press "Add New" to add your new record',
                    columns: [
                        { title: "ID", field: "perid", hozAlign: "right", tooltip: build_record_hover, width: 50, },
                        { title: "Last Name", field: "last_name", headerFilter: true, headerWordWrap: true, tooltip: true, },
                        { title: "First Name", field: "first_name", headerFilter: true, headerWordWrap: true, tooltip: true, },
                        { title: "Middle Name", field: "middle_name", headerFilter: false, headerWordWrap: true, tooltip: true, headerSort: false, maxWidth: 60, width: 60 },
                        { title: "Badge Name", field: "badge_name", headerFilter: true, headerWordWrap: true, tooltip: true, },
                        { title: "Email Address", field: "email_addr", headerFilter: true, headerWordWrap: true, tooltip: true, },
                        { title: "Reg", field: "mem_type", headerFilter: true, headerWordWrap: true, tooltip: true, maxWidth: 80, width: 80, },
                        {
                            title: "Cart", width: 45, hozAlign: "center", headerFilter: false, headerSort: false,
                            cellClick: addCartClick, formatter: addCartIcon,
                        },
                        { field: "index", visible: false, },
                    ],
                },
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

        if (new_badgetype == '') {
            missing_fields++;
            add_badgetype_field.style.backgroundColor = 'var(--bs-warning)';
        } else {
            add_badgetype_field.style.backgroundColor = '';
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
        var age = new_badgetype.replace(/.* /, '');
        var row = {
            perid: new_perid, first_name: new_first, middle_name: new_middle, last_name: new_last, badge_name: new_badgename,
            address_1: new_addr1, address_2: new_addr2, city: new_city, state: new_state, postal_code: new_postal_code,
            country: new_country, email_addr: new_email, phone: new_phone,
            share_reg: 'Y', contact_ok, new_contact, active: 'Y', banned: 'N',
            mem_type: new_badgetype.replace(/_/g, ' '), reg_type: age, price: new_price, paid: 0, tid: 0, index: max_index,
        };
        new_perid--;
        max_index++;

        add_first_field.value = "";
        add_middle_field.value = "";
        add_email_field.value = "";
        add_phone_field.value = "";
        result_data.push(row);
        cart.push(row);
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
    row = cart[rownum];
    var seltxt = membership_select;
    var rowhtml = '<div class="row">';
    if (row['reg_type'] == '') {
        rowhtml += '<div class="col-sm-8 text-bg-info">'
    } else {
        rowhtml += '<div class="col-sm-8 text-bg-success">'
    }
    if (row['reg_type'] != '') {
        rowhtml += 'Membership: ' + row['mem_type'] + '</div>';
    } else {
        rowhtml += 'No Membership</div>';
    }
    if (row['mem_type'] == 'standard oneday adult' || row['mem_type'].includes("upgrade")) {
        seltxt = upgrade_select;
        rowhtml += `
    <div class="col-sm-2 p-0 text-end"><button type="button" class="btn btn-small btn-info pt-0 pb-0 ps-1 pe-1" onclick="upgrade_membership_cart(` + rownum + ", 'cart-mt-" + rownum + `')">Upgrade</button></div>`
    } else if (row['mem_type']== '') {
        rowhtml += `
        <div class="col-sm-2 p-0 text-end"><button type="button" class="btn btn-small btn-info pt-0 pb-0 ps-1 pe-1" onclick="add_membership_cart(` + rownum + ", 'cart-mt-" + rownum + `')">Add</button></div >`
    } else if (row['tid'] == '') {
        rowhtml += `
        <div class="col-sm-2 p-0 text-end"><button type="button" class="btn btn-small btn-info pt-0 pb-0 ps-1 pe-1" onclick="add_membership_cart(` + rownum + ", 'cart-mt-" + rownum + `')">Chg</button></div >`
    } else {
            rowhtml += `
    <div class="col-sm-2"></div>`
    }
    rowhtml += `        
    <div class="col-sm-2 p-0 text-end"><button type="button" class="btn btn-small btn-secondary pt-0 pb-0 ps-1 pe-1" onclick="remove_from_cart(` + rownum + `)">Remove</button></div>
</div>`;

    if (row['reg_type'] == '' || row['tid'] == '' || row['mem_type'] == 'standard oneday adult' || row['mem_type'].includes('upgrade')) {
        rowhtml += `
<div class="row">
    <div class="col-sm-auto ps-0 pe-1">` + ((row['reg_type'] == '' || row['mem_type'] == 'standard oneday adult') ? 'Add' : 'Chg') + `:</div>
    <div class="col-sm-auto ps-0 pe-0"><select id="cart-mt-` + rownum + `" name="cart-age">
` + seltxt + `
        </select>
    </div>
</div>`;
    }
    rowhtml += `
<div class="row">
    <div class="col-sm-8">` + row['badge_name'] + `</div>
    <div class="col-sm-2 text-end">` + row['price'] + `</div>
    <div class="col-sm-2 text-end">` + row['paid'] + `</div>
</div>
<div class="row">
    <div class="col-sm-8">` + row['first_name'] + ' ' + row['middle_name'] + ' ' + row['last_name'] + `</div>
</div>
`;
    return rowhtml;
}

function draw_cart() {
    var total_price = 0;
    var total_paid = 0;
    var row;
    var num_rows = 0;
    var membership_rows = 0;
    var unpaid_rows = 0;
    var needmembership_rows = 0;
    var html = `
<div class="container-fluid">
<div class="row">
    <div class="col-sm-8 text-bg-primary">Badge</div>
    <div class="col-sm-2 text-bg-primary">Price</div>
    <div class="col-sm-2 text-bg-primary">Paid</div>
</row>
`;
    for (rownum in cart) {
        num_rows++;
        row = cart[rownum]
        if (row['reg_type'] == '') {
            needmembership_rows++;
        } else {
            if (row['paid'] != row['price']) {
                unpaid_rows++;
            } else {
                membership_rows++;
            }
        }
        html += draw_cart_row(rownum);
        total_price += row['price'];
        total_paid += row['paid'];
    }
    html += `<div class="row">
    <div class="col-sm-8 text-end">Totals:</div>
    <div class="col-sm-2 text-end">` + total_price + `</div>
    <div class="col-sm-2 text-end">` + total_paid + `</div>
</div>
`;
    if (needmembership_rows > 0) {
        var person = needmembership_rows > 1 ? " people" : " person";
        var need = needmembership_rows > 1 ? "need memberships" : "needs a membership";
        html += `<div class="row mt-3">
    <div class="col-sm-12">Cannot proceed to "Review" because ` + needmembership_rows + person + " still " + need + `.  Use "Add" button to add memberships for them or "Remove" button to take them out of the cart.
    </div>
`;
    } else if (num_rows > 0) {
        review_button.hidden = in_review;
    }
    cart_div.innerHTML = html;
    startover_button.hidden = num_rows == 0;
    if (needmembership_rows > 0 || membership_rows == 0) {
        review_tab.disabled = true;
        review_button.hidden = true;
    }
}

function draw_record(row, first) {
    var data = result_data[row];
    var html = `
<div class="container-fluid">
    <div class="row mt-2">
        <div class="col-sm-3">`;
    if (first) {
        html += `<button class="btn btn-primary btn-small" id="add_btn_all" onclick="add_to_cart(-` + transid_search + `);">Add All Cart</button>`;
    }
    html += `</div>
        <div class="col-sm-9">`;
    if (cart_perid.includes(data['perid']) == false) {
        html += `
            <button class="btn btn-success btn-small" id="add_btn_1" onclick="add_to_cart(` + row + `);">Add to Cart</button>`;
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
       <div class="col-sm-auto">Banned: ` + data['banned'] + `</div>
    </div>
    <div class="row">
       <div class="col-sm-3">Membership Type:</div>
       <div class="col-sm-9">` + data['mem_type'] + `</div>
    </div>
`;
    return html;
}

function addCartIcon(cell, formatterParams, onRendered) { //plain text value
    if (cell.getRow().getData().banned == 'Y') {
        return '<button type="button" class="btn btn-sm btn-danger pt-0 pb-0">B</button>';
    } else if (cart_perid.includes(cell.getRow().getData().perid) == false) {
        return '<button type="button" class="btn btn-sm btn-success p-0">Add</button>';
    }
    return '<span style="font-size: 75%;">In Cart';
};

function addCartClick(e, cell) {
    var index = cell.getRow().getData().index;
    add_to_cart(index);
}

function add_membership_cart(rownum, selectname) {
    var select = document.getElementById(selectname);
    var badgetype = select.value.trim();
    var price = Number(select.options[select.selectedIndex].innerHTML.replace(/.*\(/, '').replace(/\).*/, '').replace(/\$/, ''));

    row['mem_type'] = badgetype.replace(/_/g, ' ');
    row['reg_type'] = badgetype.replace(/.*_/, '');
    row['price'] = price;
    draw_cart();
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
    var badgetype = select.value.trim();
    var price = Number(select.options[select.selectedIndex].innerHTML.replace(/.*\(/, '').replace(/\).*/, '').replace(/\$/, ''));

    row['mem_type'] = badgetype.replace(/_/g, ' ');
    row['reg_type'] = row['mem_type'].replace(/.* /, '');
    row['price'] = price;
    draw_cart();
}

function find_record() {
    if (find_result_table != null) {
        find_result_table.destroy();
        find_result_table = null;
    }
    var html = '';

    datatbl = new Array();
    id_div.innerHTML = "";
   
    name_search = name_field.value.toLowerCase();
 
    if (name_search != '') {
        // mockup of name search results
        for (rowindex in result_data) {
            var row = result_data[rowindex];
            var sourcestring = row['last_name'] + ' ' + row['first_name'] + ' ' + row['badge_name'] + ' ' + row['email_addr'];
            sourcestring = sourcestring.toLowerCase();
            if (sourcestring.includes(name_search)) {
                datatbl.push(row);
            }
         }

        if (datatbl.length > 0) {
            // table
            find_result_table = new Tabulator('#find_results', {
                maxHeight: "600px",
                data: datatbl,
                layout: "fitColumns",
                columns: [
                    { title: "ID", field: "perid", hozAlign: "right", tooltip: build_record_hover, width: 50, },
                    { title: "Last Name", field: "last_name", headerFilter: true, headerWordWrap: true, tooltip: true, },
                    { title: "First Name", field: "first_name", headerFilter: true, headerWordWrap: true, tooltip: true, },
                    { title: "Middle Name", field: "middle_name", headerFilter: false, headerWordWrap: true, tooltip: true, headerSort: false, maxWidth: 60, width: 60 },
                    { title: "Badge Name", field: "badge_name", headerFilter: true, headerWordWrap: true, tooltip: true, },
                    { title: "Email Address", field: "email_addr", headerFilter: true, headerWordWrap: true, tooltip: true, },
                    { title: "Reg", field: "mem_type", headerFilter: true, headerWordWrap: true, tooltip: true, maxWidth: 80, width: 80, },
                    {
                        title: "Cart", width: 45, hozAlign: "center", headerFilter: false, headerSort: false,
                        cellClick: addCartClick, formatter: addCartIcon,
                    },
                    { field: "index", visible: false, },
                ],
            });
            //id_div.innerHTML = "name search results";            
        } else {
            id_div.innerHTML = 'No matching records found'
        }
        return;
    }

    perid_search = perid_field.value;
   
    if (perid_search > 0) {       
        if (perid_search > 0) {
            html = '';
            for (row in result_data) {
                if (result_data[row]['perid'] == perid_search) {
                    html += draw_record(row, false);
                }
            }
            if (html != '') {
                html += '</div>';
                id_div.innerHTML = html;
            } else {
                id_div.innerHTML = 'No matching records found'
            }
            return;
        }
    }

    transid_search = transid_field.value
    if (transid_search > 0) {
        html = '';
        var first = true;
        for (row in result_data) {
            if (result_data[row]['tid'] == transid_search) {
                html += draw_record(row, first);
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

function start_review() {
    // set tab to review-tab
    bootstrap.Tab.getOrCreateInstance(review_tab).show();
  
}

// tab shown events
function find_shown(current, previous) {
    in_review = false;
    draw_cart();
}

function add_shown(current, previous) {
    in_review = false;
    draw_cart();
}

function review_shown(current, previous) {
    in_review = true;
    draw_cart();
}

function pay_shown(current, previous) {
    in_review = false;
    draw_cart();
}

function print_shown(current, previous) {
    in_review = false;
    draw_cart();
}