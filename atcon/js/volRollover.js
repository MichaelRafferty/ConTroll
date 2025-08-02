// list fields
var startover_button = null;

// list items
var list_perinfo = [];

// find people fields
var id_div = null;
var find_result_table = null;
var number_search = null;
var memLabel = null;

// Data Items
var result_perinfo = [];

// global items
var conid = null;
var conlabel = null;
var user_id = 0;
var rollover_memId = null;
var rollover_label = null;
var rollover_shortname = null;

// initialization
// lookup all DOM elements
// ask to load mappimg tables
window.onload = function initpage() {
    // set up the constants for objects on the screen
    // Rolledover list
    list_div = document.getElementById("list");
    startover_button = document.getElementById("startover_btn");

    // find people
    pattern_field = document.getElementById("find_pattern");
    pattern_field.addEventListener('keyup', (e)=> { if (e.code === 'Enter') find_record(); });
    id_div = document.getElementById("find_results");

    // load the initial data and the proceed to set up the rest of the system
    var postData = {
        ajax_request_action: 'loadInitialData',
    };
    $.ajax({
        method: "POST",
        url: "scripts/volRollover_loadInitialData.php",
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

// load fields from database to javascript array
function loadInitialData(data) {
    // map the memIds and labels for the pre-coded memberships.  Doing it now because it depends on what the datbase sends.
    // tabls
    conlabel =  data['label'];
    conid = data['conid'];
    user_id = data['user_id']
    rollover_memId = data['rollover_memId'];
    rollover_label = data['rollover_label'];
    rollover_shortname = data['rollover_shortname'];

    // set up initial values
    result_perinfo = [];

    // set starting stages of left and right windows
    draw_list();
}

// make_copy(associative array)
// javascript passes by reference, can't slice an associative array, so you need to do a horrible JSON kludge
function make_copy(arr) {
    return JSON.parse(JSON.stringify(arr));  // horrible way to make an independent copy of an associative array
}

// if no memberships or payments have been added to the database, this will reset for the next customer
function start_over(reset_all) {
    clear_message();
    // empty cart
    list_perinfo = [];

    // empty search strings and results
    pattern_field.value = "";
    if (find_result_table != null) {
        find_result_table.destroy();
        find_result_table = null;
    }
    id_div.innerHTML = "";

    // reset data to call up
    result_perinfo = [];
    draw_list();
}

// badge_name_default: build a default badge name if its empty
function badge_name_default(badge_name, first_name, last_name) {
    if (badge_name === undefined | badge_name === null || badge_name === '') {
        var default_name = (first_name + ' ' + last_name).trim();
        return '<i>' + default_name.replace(/ +/, ' ') + '</i>';
    }
    return badge_name;
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
        'Membership: ' + data['label'] + '<br/>';

    return hover_text;
}

// rollover this member and add them to the list
function rollover_member(index) {
    var rt = result_perinfo[index];

    if (rt['banned'] == 'Y') {
        alert("Please ask " + (result_perinfo[index]['first_name'] + ' ' + rt[index]['last_name']).trim() +" to talk to the Registration Administrator, you cannot roll them over at this time.")
        return;
    }
    if (!(rt['roll_regid'] === undefined || rt['roll_regid'] === null)) {
        show_message("This member already has a valid membership in the next convention", "error");
        return;
    }

    // load the initial data and the proceed to set up the rest of the system
    var postData = {
        ajax_request_action: 'rolloverMember',
        member: rt,
        rollover_memId: rollover_memId,
        rollover_shortname: rollover_shortname,
        index: index,
        user_id: user_id,
    };
    $.ajax({
        method: "POST",
        url: "scripts/volRollover_rolloverMember.php",
        data: postData,
        success: function (data, textstatus, jqxhr) {
            if (data['error'] !== undefined) {
                show_message(data['error'], 'error');
                return;
            }
            if (data['message'] !== undefined) {
                show_message(data['message'], 'success');
            }
            member_rolled_over(data);
        },
        error: showAjaxError,
    });
}

// member_rolled_over:
//  database entry added, add to table
function member_rolled_over(data) {
    var index = data['index'];

    result_perinfo[index]['roll_regid'] = data['member']['roll_regid'];
    result_perinfo[index]['shortname'] = data['member']['shortname'];
    result_perinfo[index]['roll_tid'] = data['member']['roll_tid'];
    list_perinfo.push(make_copy(result_perinfo[index]));
    if (find_result_table != null)
        find_result_table.replaceData(result_perinfo);
    else
        draw_record();

    draw_list();
}

// format all of the memberships for one record in the cart
function draw_list_row(rownum) {
    var row = list_perinfo[rownum];
    var membername = (row['first_name'] + ' ' + row['middle_name'] + ' ' + row['last_name']).trim();
    if (row['suffix'] != '') {
        membername += ', ' + row['suffix'];
    }

    var perid = row['perid'];
    var rowhtml = `<div class="row">
        <div class="col-sm-8">Member: ` + membername + `</div>
        <div class="col-sm-2 text-end">` + row['roll_tid'] + `</div>
        <div class="col-sm-2 text-end">` + row['roll_regid'] + `</div>
    </div>`;

    // second row - badge name
    rowhtml += `
    <div class="row mb-2">
        <div class="col-sm-3 p-0">Badge Name:</div>
        <div class="col-sm-5 p-0">` + badge_name_default(row['badge_name'], row['first_name'], row['last_name']) + `</div>
    </div>`;

    return rowhtml;
}

// draw/update by redrawing the entire cart
function draw_list() {
    var html = `
<div class="container-fluid">
<div class="row">
    <div class="col-sm-8 text-bg-primary">Member</div>
    <div class="col-sm-2 text-bg-primary text-end">Trans ID</div>
    <div class="col-sm-2 text-bg-primary text-end">Reg ID</div>
</div>
`;
    for (rownum in list_perinfo) {
        html += draw_list_row(rownum);
    }
    html += `<div class="row">
</div>
`;
    html += '</div>'; // ending the container fluid
    //console.log(html);
    list_div.innerHTML = html;
}

// draw_record: find_record found rows from search.  Display them in the non table format used by transaction and perid search, or a single row match for string.
function draw_record() {
    var data = result_perinfo[0];
    var html = `
<div class="container-fluid">
    <div class="row mt-2">
        <div class="col-sm-3">`;
    html += `</div>
        <div class="col-sm-5">`;
    if (data['roll_regid'] === undefined || data['roll_regid'] === null) {
        if (data['banned'] == 'Y') {
            html += `
            <button class="btn btn-danger btn-sm" id="add_btn_1" onclick="rollover_member(0);">B</button>`;
        } else if (data['memCategory'] == 'eligible') {
            html += `
            <button class="btn btn-success btn-sm" id="add_btn_1" onclick="rollover_member(0);">Rollover</button>`;
        } else {
            html += `
            <button class="btn btn-danger btn-sm disabled" id="add_btn_1" onclick="javascript:void(0)">Not Eliglble: ` + data['memCategory'] + `</button>`;
        }
    } else {
        html += `
            <i>` + data['shortname'] + '</i>';
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
       <div class="col-sm-9">` + data['label'] + `</div>
    </div>
</div>
`;
    id_div.innerHTML = html;
}

// tabulator perinfo formatters:

// tabulator formatter for the add cart column, displays the "add" record and "trans" to add the tranaction to the card as appropriate
// filters for ones already in the cart, and statuses that should not be allowed to be added to the cart
function addListIcon(cell, formatterParams, onRendered) { //plain text value
    var html = '';
    var banned = cell.getRow().getData().banned;
    var shortname = cell.getRow().getData().shortname;
    var memCategory = cell.getRow().getData().memCategory;
    if (banned == undefined) {
        var tid = Number(cell.getRow().getData().tid);
        html = '<button type="button" class="btn btn-sm btn-success p-0" onclick="add_unpaid(' + tid + ')">Pay</button > ';
        return html;
    }
    if (banned == 'Y') {
        return '<button type="button" class="btn btn-sm btn-danger pt-0 pb-0" onclick="rollover_member(' +
            cell.getRow().getData().index + ')">B</button>';
    } else if (memCategory != 'eligible') {
        return '<button type="button" class="btn btn-sm btn-danger pt-0 pb-0 disabled" onclick="javascript:void(0)">Not Eligible (' + memCategory + ')</button>';
    } else if (shortname === undefined || shortname === null) {
        html = '<button type="button" class="btn btn-sm btn-success p-0" onclick="rollover_member(' +
            cell.getRow().getData().index + ')">Rollover</button>';
        return html;
    }
    return shortname;
}

// search the online database for a set of records matching the criteria
//  possible meanings of find_pattern
//      numeric: perid matches
//      alphanumeric: search for names in name, badge_name, email_address fields
//
function find_record() {
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
        name_search: name_search,
        rollover_memId: rollover_memId,
    };
    $.ajax({
        method: "POST",
        url: "scripts/volRollover_findRecord.php",
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
    name_search = data['name_search'];

    // string search, returning more than one row show tabulator table
    if (isNaN(name_search) && result_perinfo.length > 1)  {
        // table
        find_result_table = new Tabulator('#find_results', {
            maxHeight: "600px",
            data: result_perinfo,
            layout: "fitColumns",
            initialSort: [
                {column: "fullName", dir: "asc"},
            ],
            columns: [
                {title: "Perid", field: "perid", maxWidth: 60, headerSort: false, },
                {field: "index", visible: false, },
                {title: "Name", field: "fullName", headerFilter: true, headerWordWrap: true, tooltip: build_record_hover,},
                {field: "last_name", visible: false,},
                {field: "first_name", visible: false,},
                {field: "middle_name", visible: false,},
                {field: "suffix", visible: false,},
                {title: "Badge Name", field: "badge_name", headerFilter: true, headerWordWrap: true, tooltip: true,},
                {title: "Zip", field: "postal_code", headerFilter: true, headerWordWrap: true, tooltip: true, maxWidth: 70, width: 70},
                {title: "Email Address", field: "email_addr", headerFilter: true, headerWordWrap: true, tooltip: true,},
                {title: "Reg", field: "label", headerFilter: true, headerWordWrap: true, tooltip: true, maxWidth: 80, width: 80,},
                {title: "Rollover", width: 80, headerFilter: false, headerSort: false, formatter: addListIcon, formatterParams: {t:"result"},},
                {field: "index", visible: false,},
            ],
        });
    } else if (result_perinfo.length > 0) {  // one row string,  perinfo, display in record format
        draw_record();
        return;
    }
    // no rows show the diagnostic
    id_div.innerHTML = `<div class="container-fluid">
<div class="row mt-3">
    <div class="col-sm-4">No matching records found</div>
    <div class="col-sm-auto"><button class="btn btn-primary btn-sm" type="button" id="not_found_add_new" onclick="not_found_add_new();">Add New Person</button>
    </div>
</div>
</div>
`;
    id_div.innerHTML = id_div.innerHTML = 'No matching records found'
}
