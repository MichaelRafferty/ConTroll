var summaryDiv = null;
var vendortable = null;
var spacestable = null;
var update_profile = null;
var approve_space = null;
var price_lists = null;
$(document).ready(function () {
    id = document.getElementById('update_profile');
    if (id != null) {
        update_profile = new bootstrap.Modal(id, { focus: true, backdrop: 'static' });
    }
    id = document.getElementById('approve_space');
    if (id != null) {
        approve_space = new bootstrap.Modal(id, { focus: true, backdrop: 'static' });
    }
    getData();
});

function getData() {
    $.ajax({
        url: "scripts/getVendorData.php",
        method: "GET",
        success: draw,
        error: function (jqXHR, textStatus, errorThrown) {
            showError("ERROR in getVendorData: " + textStatus, jqXHR);
            return false;
        }
    })
}

// draw/update the database items into tables on the screen
function draw(data, textStatus, jqXHR) {
    draw_summary(data);
    draw_vendor(data);
    draw_spaces(data);
    price_lists = data['price_list'];
}

// summary status at the top of the screen
function draw_summary(data) {
    var summary = data['summary'];
    if (!summary)
        return;

    html = '<div class="container-fluid">';

    for (var spaceid in summary) {
        space = summary[spaceid];
        remaining =  space['unitsAvailable'] - space['approved'];
        html += `    <div class="row mt-1 mb-1 p-0">
        <div class="col-sm-auto">
            <span style="font-size: 125%; font-weight: bold;">` + space['name'] + ` Registrations: </span>
        </div>
        <div class="col-sm-auto ms-2 pt-1">New: ` + space['new'] + `</div>
        <div class="col-sm-auto ms-2 pt-1">Pending: ` + space['pending'] + `</div>
        <div class="col-sm-auto ms-2 pt-1">Purchased: ` + space['purchased'] + `</div>
        <div class="col-sm-auto ms-2 pt-1">Remaining: ` + remaining + `</div>
    </div>
        `;
    }

    html += '</div>';
    if (summaryDiv == null)
        summaryDiv = document.getElementById("summary-div");

    summaryDiv.innerHTML = html;
}

// button formatters
function editbutton(cell, formatterParams, onRendered) {
    return '<button class="btn btn-secondary" style = "--bs-btn-padding-y: .0rem; --bs-btn-padding-x: .3rem; --bs-btn-font-size: .75rem;">Edit</button>';
}
function resetpwbutton(cell, formatterParams, onRendered) {
    return '<button class="btn btn-secondary" style = "--bs-btn-padding-y: .0rem; --bs-btn-padding-x: .3rem; --bs-btn-font-size: .75rem;">ResetPW</button>';
}

function approvalbutton(cell, formatterParams, onRendered) {
    var data = cell.getData();
    var req = data['requested_units'];
    if (req == null)
        return '';

    var app = data['approved_units'];
    if (app == null)
        app = 0;

    var pur = data['purchased_units'];
    if (pur == null)
        pur = 0;

    if (pur >= app && app > 0)
        return '';

    return '<button class="btn btn-secondary" style = "--bs-btn-padding-y: .0rem; --bs-btn-padding-x: .3rem; --bs-btn-font-size: .75rem;">Approve</button>';
}

// show the full vendor record as a hover in the table
function build_record_hover(e, cell, onRendered) {
    var data = cell.getData();
    //console.log(data);
    var hover_text = 'Vendor id: ' + data['id'] + '<br/>' +
        data['name'] + '<br/>' +
        'Website: ' + data['website'] + '<br/>' +
        data['addr'] + '<br/>';
    if (data['addr2'] != '') {
        hover_text += data['addr2'] + '<br/>';
    }
    hover_text += data['city'] + ', ' + data['state'] + ' ' + data['zip'] + '<br/>' +
        'Needs New Password: ' + (data['needs_new'] ? 'Yes' : 'No') +  '<br/>' +
        'Publicize: ' + (data['publicity'] ? 'Yes' : 'No') +  '<br/>';
    hover_text += 'Description:<br/>&nbsp;&nbsp;&nbsp;&nbsp;' + data['description'].replaceAll('\n', '<br/>&nbsp;&nbsp;&nbsp;&nbsp;');
    return hover_text;
}

// button callout functions
function edit(e, cell) {
    vendor = cell.getRow().getData();
    return editVendor(vendor);
}

function approval(e, cell) {
    var data = cell.getData();
    var req = data['requested_units'];

    if (req == null)
        return '';

    var app = data['approved_units'];
    if (app == null)
        app = 0;

    var pur = data['purchased_units'];
    if (pur == null)
        pur = 0;

    if (pur >= app && app > 0)
        return '';

    approveReq(data);
}

function resetpw(e, cell) {
    vendor = cell.getRow().getCell("id").getValue();
    $.ajax({
        url: 'scripts/setPassword.php',
        method: "POST",
        data: { 'vendorId': vendor },
        success: function (data, textStatus, jqXhr) {
            if(data['error'] != undefined) { console.log(data['error']); }
            alert(data['password']);
        }
    });
}

// draw_vendor
// update the VendorList div with the table of vendors
function draw_vendor(data) {
    if (vendortable !== null) {
        vendortable.destroy();
        vendortable = null;
    }
    vendortable = new Tabulator('#VendorList', {
        data: data['vendors'],
        layout: "fitDataTable",
        pagination: true,
        paginationSize: 25,
        paginationSizeSelector: [10, 25, 50, 100, 250, true], //enable page size select element with these options
        columns: [
            {title: "Vendors:", columns: [
                    {title: "id", field: "id", visible: false},
                    {title: "Name", field: "name", headerSort: true, headerFilter: true, tooltip: build_record_hover,},
                    {title: "Website", field: "website", headerSort: true, headerFilter: true,},
                    {title: "City", field: "city", headerSort: true, headerFilter: true,},
                    {title: "State", field: "state", headerSort: true, headerFilter: true,},
                    {title: "", formatter: editbutton, hozAlign: "center", cellClick: edit, headerSort: false,},
                    {title: "", formatter: resetpwbutton, hozAlign: "center", cellClick: resetpw, headerSort: false,},
                ]
            }
        ]
    });
}


// draw_spaces
// update the space detail section from the detail portion of the returned data
function draw_spaces(data) {
    if (spacestable !== null) {
        spacestable.destroy();
        spacestable = null;
    }

    var requested = data['summary']['requested'];
    var approved = data['summary']['approved'];
    var purchased = data['summary']['purchased'];

    spacestable = new Tabulator('#SpaceDetail', {
        data: data['detail'],
        layout: "fitDataTable",
        pagination: true,
        paginationSize: 25,
        paginationSizeSelector: [10, 25, 50, 100, 250, true], //enable page size select element with these options
        columns: [
            {title: "Vendor Space Detail:", columns: [
                    {title: "id", field: "id", visible: false},
                    {title: "vendorId", field: "vendorId", visible: false},
                    {title: "spaceId", field: "spaceId", visible: false},
                    {title: "requested_code", field: "requested_code", visible: false},
                    {title: "approved_code", field: "approved_code", visible: false},
                    {title: "purchased_code", field: "purchased_code", visible: false},
                    {title: "Name", field: "vendorName", headerSort: true, headerFilter: true,},
                    {title: "Website", field: "website", headerSort: true, headerFilter: true,},
                    {title: "Email", field: "email", headerSort: true, headerFilter: true,},
                    {title: "Space", field: "spaceName", headerSort: true, headerFilter: true },
                    {title: "Requested", columns: [
                            { title: "Units", field: "requested_units", headerSort:false, headerFilter: false, },
                            { title: "Description", field: "requested_description", headerSort:false, headerFilter: false, },
                        ]
                    },
                    {title: "Approved", columns: [
                            { title: "Units", field: "approved_units", headerSort:false, headerFilter: false, },
                            { title: "Description", field: "approved_description", headerSort:false, headerFilter: false, },
                        ]
                    },
                    {title: "Purchased", columns: [
                            { title: "Units", field: "purchased_units", headerSort:false, headerFilter: false, },
                            { title: "Description", field: "purchased_description", headerSort:false, headerFilter: false, },
                        ]
                    },
                    {title: "", formatter: approvalbutton, hozAlign: "center", cellClick: approval, headerSort: false,},
               ]
            }
        ]
    });
}

// editVendor - Populate edit vendor modal with current data
function editVendor(vendor) {
    console.log(vendor);
    document.getElementById("ev_name").value = vendor.name;
    document.getElementById("ev_email").value = vendor.email;
    document.getElementById("ev_website").value = vendor.website;
    document.getElementById("ev_description").value = vendor.description;
    document.getElementById("ev_addr").value = vendor.addr;
    document.getElementById("ev_addr2").value = vendor.addr2;
    document.getElementById("ev_city").value = vendor.city;
    document.getElementById("ev_state").value = vendor.state;
    document.getElementById("ev_zip").value = vendor.zip;
    document.getElementById("ev_publicity").checked = vendor.publicity == 1;
    document.getElementById("ev_vendorId").value = vendor.id;
    update_profile.show();
}

// updateProfile - update the database profile for this vendor
function updateProfile() {
    $.ajax({
        url: 'scripts/updateVendorProfile.php',
        data: $('#vendor_update').serialize(),
        method: 'POST',
        success: function(data, textstatus, jqXHR) {
            if(data['status'] == 'error') {
                alert(data['message']);
            } else {
                console.log(data);
                if (data['success'])
                    show_message(data['success'], 'success');
                update_profile.hide();
                getData();
            }
        }
    });
}

// process approving requested units
function approveReq(data) {
    // populate the space in the approval form
    document.getElementById('sr_vendorId').value = data.vendorId;
    document.getElementById('sr_spaceId').value = data.spaceId;
    document.getElementById('sr_id').value = data.id;
    document.getElementById('sr_name').innerHTML = data.vendorName;
    document.getElementById('sr_email').innerHTML = data.email;
    document.getElementById('sr_website').innerHTML = data.website;
    document.getElementById('sr_spaceName').innerHTML = data.spaceName;
    document.getElementById('sr_reqUnits').innerHTML = data.requested_units;
    document.getElementById('sr_reqDescription').innerHTML = data.requested_description;
    document.getElementById('sr_appOption').innerHTML = "\n" + '<select name="sr_approved" id="sr_approved"><option value="0">Not Approved</option>' + price_lists[data.spaceId] + "</select>\n";
    if (data.item_approved)
        document.getElementById('sr_approved').value = data.item_approved;
    // make the form visible
    approve_space.show();
}

// handle the space approval
function approveSpace() {
    $.ajax({
        url: 'scripts/updateVendorSpace.php',
        data: $('#space_request').serialize(),
        method: 'POST',
        success: function(data, textstatus, jqXHR) {
            if(data['error']) {
                show_message($data['error'], 'error');
            } else {
                console.log(data);
                if (data['success'])
                    show_message(data['success'], 'success');
                approve_space.hide();
                getData();
            }
        }
    });
}
