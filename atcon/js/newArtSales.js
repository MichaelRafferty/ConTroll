//tabs
var find_tab = null;

//buttons
var startover_button;
var inventory_update_button;
var location_change_button;

//find item fields
var artist_field = null;
var item_field = null;
var find_result_table = null;
var id_div = null;
var cart_div = null;
var customer_div = null;

//tables
var datatbl = new Array();
var locations = new Array();
var cart_items = new Array();
var cart = new Array();
var actionlist = new Array();

// global items
var conid = null;
var conlabel = null;
var user_id = 0;
var hasManager = false;
var customer_id = null;

window.onload = function initpage() {
    // set up the constants for objects on the screen

    find_tab = document.getElementById("find-tab");
    current_tab = find_tab;

    customer_div = document.getElementById("customer");

    // find people
    pattern_field = document.getElementById("find_pattern");
    pattern_field.addEventListener('keyup', (e)=> { if (e.code === 'Enter') find_record('search'); });
    id_div = document.getElementById("find_results");

    start_over();
}

function start_over() {
    customer_id = null;
    customer_div.hidden = true;
}

// search the online database for a set of records matching the criteria
// find_type: empty: search for memberships
//  possible meanings of find_pattern
//      numeric: search for perid matches
//      alphanumeric: search for names in name, badge_name, email_address fields//
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
        ajax_request_action: 'getCustomer',
        find_type: find_type,
        name_search: name_search,
    };
    $("button[name='find_btn']").attr("disabled", true);
    $.ajax({
        method: "POST",
        url: "scripts/artsales_getCustomer.php",
        data: postData,
        success: function (data, textstatus, jqxhr) {
            document.getElementById('test').innerHTML = JSON.stringify(data, null, 2);
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

// successful return from 1 AXAJ call - processes found records
//      single row: display record
//      multiple rows: display table of records with add/trans buttons
function found_record(data) {
    var find_type = data['find_type'];
    result_perinfo = data['perinfo'];
    name_search = data['name_search'];

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
                {title: "Cart", width: 100, headerFilter: false, headerSort: false, formatter: addCartIcon, formatterParams: {t:"result"},},
                {field: "index", visible: false,},
            ],
        });
    } else if (result_perinfo.length > 0) {  // one row string, or all perinfo/tid searches, display in record format
        if(!isNaN(name_search)) {
        //if number search use as customer
            number_search = Number(name_search);
            set_customer(result_perinfo[0]);
        } else {
        //else draw as records()
            draw_as_records();
        }
        return;
    }    
}

function set_customer(customer) {
    customer_id = customer['perid'];
    var customer_name = document.getElementById('customer-name');
    name = '';
    if(customer['badge_name'] != '') {
        name = customer['badge_name'];
    } else {
        name = customer['first_name'] + ' ' + customer['last_name']; 
    }
    name += ' (' + customer['perid'] + ')';

    customer_name.innerHTML = name;
    customer_div.hidden=false;

}

// show the full perinfo record as a hover in the table
function build_record_hover(e, cell, onRendered) {
    var data = cell.getData();
    //console.log(data);
    var hover_text = 'Person id: ' + data['perid'] + '<br/>' +
        (data['first_name'] + ' ' + data['middle_name'] + ' ' + data['last_name']).trim() + '<br/>' ;
    hover_text += 'Badge Name: ' + badge_name_default(data['badge_name'], data['first_name'], data['last_name']) + '<br/>' +
        'Email: ' + data['email_addr'] + '<br/>' + 'Phone: ' + data['phone'] + '<br/>' ;

    return hover_text;
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

// draw_record: find_record found rows from search.  Display them in the non table format used by transaction and perid search, or a single row match for string.
function draw_record(row, first) {
    var data = result_perinfo[row];
    var html = `
<div class="container-fluid">
    <div class="row mt-2">`;
    //
    // put use customer function here!
    //
    html += ` 
<div class="container-fluid">
    <div class="row mt-2">
        <div class="col-sm-3"></div>
        <div class="col-sm-5">`;
    html += `<button class='btn btn-primary btn-small' id='set_customer' onclick="set_customer(result_perinfo[` + row + `]);">Set Customer</button>`;
    html += `
         </div>
        <div class="col-sm-2"></div>
        <div class="col-sm-2"></div>
    </div>`;
    html += `
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
`;
    html += `
    <div class="row">
       <div class="col-sm-3">Email Address:</div>
       <div class="col-sm-9">` + data['email_addr'] + `</div>
    </div>
    <div class="row">
       <div class="col-sm-3">Phone::</div>
       <div class="col-sm-9">` + data['phone'] + `</div>
    </div>
`;
    return html;
}


// badge_name_default: build a default badge name if its empty
function badge_name_default(badge_name, first_name, last_name) {
    if (badge_name === undefined | badge_name === null || badge_name === '') {
        var default_name = (first_name + ' ' + last_name).trim();
        return '<i>' + default_name.replace(/ +/, ' ') + '</i>';
    }
    return badge_name;
}


// tabulator formatter for the add cart column, displays the "add" record and "trans" to add the tranaction to the card as appropriate
// filters for ones already in the cart, and statuses that should not be allowed to be added to the cart
function addCartIcon(cell, formatterParams, onRendered) {
    html = '';
    html = '<button type="button" class="btn btn-sm btn-success p-0" onclick="set_customer(result_perinfo[' + cell.getRow().getData().index + '])">Set Customer</button>';
    return html;
}
