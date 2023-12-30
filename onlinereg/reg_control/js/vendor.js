var summaryDiv = null;
var vendortable = null;
var spacestable = null;
var update_profile = null;
var approve_space = null;
var price_lists = null;
var add_space= null;
var space_map = {};
var receipt_modal = null;
var receipt_email_address = null;

$(document).ready(function () {
    id = document.getElementById('update_profile');
    if (id != null) {
        update_profile = new bootstrap.Modal(id, { focus: true, backdrop: 'static' });
    }
    id = document.getElementById('approve_space');
    if (id != null) {
        approve_space = new bootstrap.Modal(id, { focus: true, backdrop: 'static' });
    }
    id = document.getElementById('add_vendorSpace');
    if (id != null) {
        add_space = new bootstrap.Modal(id, { focus: true, backdrop: 'static' });
    }
    id = document.getElementById('receipt');
    if (id != null) {
        receipt_modal = new bootstrap.Modal(id, { focus: true, backdrop: 'static' });
        $('#receipt').on('hide.bs.modal', function () {
            receipt_email_address = null;
        });
    }
    getData();

    for (var opt in spacePriceList) {
        var price = spacePriceList[opt];
        space_map[price['id']] = opt;
    }
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

function approval(index, appType) {
    var row = spacestable.getRow(index);
    var data = row.getData();
    var req = data['requested_units'] || 0;
    var app = data['approved_units'] || 0;
    var pur = data['purchased_units'] || 0;

    if (req > 0 && (app < pur || pur == 0))
        approveReq(data, appType);

    return '';
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
                    {title: "Email", field: "email", headerSort: true, headerFilter: true,},
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
                            { title: "Timestamp", field: "time_requested", headerSort:true, headerFilter: false },
                        ]
                    },
                    {title: "Approved", columns: [
                            { title: "Units", field: "approved_units", headerSort:false, headerFilter: false, },
                            { title: "Description", field: "approved_description", headerSort:false, headerFilter: false, },
                            { title: "Timestamp", field: "time_approved", headerSort:true, headerFilter: false },
                        ]
                    },
                    {title: "Purchased", columns: [
                            { title: "Units", field: "purchased_units", headerSort:false, headerFilter: false, },
                            { title: "Description", field: "purchased_description", headerSort:false, headerFilter: false, },
                            { title: "Timestamp", field: "time_purchased", headerSort:true, headerFilter: false },
                            { field: "transid", visible: false },
                        ]
                    },
                    {title: "", formatter: actionbuttons, hozAlign: "left", headerSort: false,},
               ]
            }
        ]
    });
}

// editVendor - Populate edit vendor modal with current data
function editVendor(vendor) {
    console.log(vendor);
    document.getElementById('vendorAddEditTitle').innerHTML = "Update Vendor Profile";
    document.getElementById('vendorAddUpdatebtn').innerHTML = "Update";
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

// add a new vendor to the vendors table
function addNewVendor() {
    document.getElementById('vendorAddEditTitle').innerHTML = "Add New Vendor";
    document.getElementById('vendorAddUpdatebtn').innerHTML = "Add";
    document.getElementById("ev_name").value = '';
    document.getElementById("ev_email").value = '';
    document.getElementById("ev_website").value = '';
    document.getElementById("ev_description").value = '';
    document.getElementById("ev_addr").value = '';
    document.getElementById("ev_addr2").value = '';
    document.getElementById("ev_city").value = '';
    document.getElementById("ev_state").value = '';
    document.getElementById("ev_zip").value = '';
    document.getElementById("ev_publicity").checked = true;
    document.getElementById("ev_vendorId").value = -1;
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
function approveReq(data, appType) {
    // populate the space in the approval form
    document.getElementById('sr_vendorId').value = data.vendorId;
    document.getElementById('sr_spaceId').value = data.spaceId;
    document.getElementById('sr_id').value = data.id;

    if (appType == 'r') {  // r = approve requested space
        approveSpace(data.item_requested);
        return;
    }

    var btn = document.getElementById('approve_button');
    if (appType == 'o') {
        document.getElementById('approve_header').className = "modal-header bg-primary text-bg-primary";
        btn.className = "btn btn-sm btn-primary";
        btn.innerHTML = "Approve";
        document.getElementById('approve_title').innerHTML = "Approve Vendor Space Request";
    } else if (appType == 'c') {
        document.getElementById('approve_header').className = "modal-header bg-warning text-bg-warning";
        btn.className = "btn btn-sm btn-warning";
        btn.innerHTML = "Change";
        document.getElementById('approve_title').innerHTML = "Change Vendor Space Approval";
    } else {
        show_message("Invalid approval type", 'error');
        return;
    }

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
function approveSpace(override) {
    data = $('#space_request').serialize();
    if (override >= 0) {
        data = data + '&sr_approved=' + override.toString();
    }

    console.log(data);
    $.ajax({
        url: 'scripts/updateVendorSpace.php',
        data: data,
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

// allow entry of a new space type for a vendor
function addNewSpace() {
    // clear the form
    document.getElementById("as_vendor").value = 0;
    document.getElementById("as_space").value = 0;
    document.getElementById("as_spaceType").value = 0;
    document.getElementById("as_state").value = 'R';
    document.getElementById("as_checkno").value = '';
    document.getElementById("as_payment").value = '';
    document.getElementById("as_included").value = 0;
    document.getElementById('as_additional').value = 0;
    document.getElementById("as_totaldue").value = '';
    document.getElementById('as_payment').value = '';
    document.getElementById('as_checkno').value = '';
    document.getElementById('as_desc').value = '';
    add_space.show();
}

// populate the space type pulldown for the add space modal
function selectSpaceType() {
    var spaceid = document.getElementById('as_space').value;
    var options = "<option value='0'>No Space Selected</option>\n";
    for (var opt in spacePriceList) {
        var price = spacePriceList[opt];
        if (price['spaceId'] == spaceid) {
            options += "<option value='" + price['id'] + "'>" + price['description'] + " (for $" + price['price'] + ")</option>\n";
        }
    }
    document.getElementById("as_spaceType").innerHTML = options;
}

// set the total amount due and set the limits on the included and additional memberships
function selectSpacePrice() {
    var spaceid = document.getElementById('as_space').value;
    var spaceid = document.getElementById('as_space').value;
    var priceid = document.getElementById("as_spaceType").value;
    var price = spacePriceList[space_map[priceid]];

    // set initial price for just the spaces
    document.getElementById('as_totaldue').value = price['price'];

    // build the included select list
    var opt = "<option value='0'>0</option>\n";
    for (var index = 1; index <= price['includedMemberships']; index++) {
        opt += "<option value='" + index + "'>" + index + "</option>\n";
    }
    document.getElementById("as_included").innerHTML = opt;

    // build the optional select list
    var opt = "<option value='0'>0</option>\n";
    for (var index = 1; index <= price['additionalMemberships']; index++) {
        opt += "<option value='" + index + "'>" + index + " ($" + (index * price['additionalPrice']) + ")</option>\n";
    }
    document.getElementById("as_additional").innerHTML = opt;
}

// update the price for the number of additional memberships purchased
function selectSpaceAdditional() {
    var spaceid = document.getElementById('as_space').value;
    var priceid = document.getElementById("as_spaceType").value;
    var price = spacePriceList[space_map[priceid]];
    var additional = document.getElementById('as_additional').value;

    // set new price for the spaces plus additional
    document.getElementById('as_totaldue').value = parseFloat(parseFloat(price['price'])  + additional * parseFloat(price['additionalPrice'])).toFixed(2);
}

// add vendor space to the vendor_spaces table from the modal
function addVendorSpace() {
    // validate minimum requirements
    var dataarr = $('#add_space_form').serializeArray();
    var data = {};
    for (var item in dataarr) {
        data[dataarr[item]['name']] = dataarr[item]['value'];
    }

    var missing_items = '';
    if (data['vendor'] <= 0) {
        missing_items += 'vendor,';
    }
    if (data['space'] <=  0) {
        missing_items += 'space,';
    }
    if (data['type'] <=  0) {
        missing_items += 'space type,';
    }
    if (data['state'] == 'P') {
        if (data['checkno'] == '') {
            missing_items += 'check number,';
        }
        if (data['payment'] == '') {
            missing_items += 'payment amount,';
        }
    }

    if (missing_items != '') {
        missing_items = "Required fields missing: " + missing_items.substring(0, missing_items.length-1);
        alert(missing_items);
        return;
    }

    $.ajax({
        url: 'scripts/addVendorSpace.php',
        data: data,
        method: 'POST',
        success: function(data, textstatus, jqXHR) {
            if(data['status'] == 'error') {
                alert(data['message']);
            } else {
                console.log(data);
                if (data['success'])
                    alert(data['success']);
                add_space.hide();
                getData();
            }
        }
    });
}

// display receipt: use the modal to show the receipt
function displayReceipt(data) {
    document.getElementById('receipt-div').innerHTML = data['receipt_html'];
    document.getElementById('receipt-tables').innerHTML = data['receipt_tables'];
    document.getElementById('receipt-text').innerHTML = data['receipt'];
    receipt_email_address = data['payor_email'];
    document.getElementById('emailReceipt').innerHTML = "Email Receipt to " + data['payor_name'] + ' at ' + receipt_email_address;
    document.getElementById('receiptTitle').innerHTML = "Registration Receipt for " + data['payor_name'];
    receipt_modal.show();
}

function receipt_email(addrchoice) {
    var email = receipt_email_address;
    var success='';
    if (addrchoice == 'reg') {
        email = document.getElementById('regadminemail').innerHTML;
        success = 'Receipt sent to Regadmin at ' + email;
    }

    if (receipt_email_address == null)
        return;

    if (success == '')
        success = document.getElementById('emailReceipt').innerHTML.replace("Email Receipt to", "Receipt sent to");

    var data = {
        email: email,
        okmsg: success,
        text: document.getElementById('receipt-text').innerHTML,
        html: document.getElementById('receipt-tables').innerHTML,
        subject: document.getElementById('receiptTitle').innerHTML,
        success: success,
    };
    $.ajax({
        method: "POST",
        url: "scripts/emailReceipt.php",
        data: data,
        success: function (data, textstatus, jqxhr) {
            if (data['error'] !== undefined) {
                show_message(data['error'], 'error');
                return;
            }
            if (data['success'] !== undefined) {
                show_message(data['success'], 'success');
            }
            if (data['warn'] !== undefined) {
                show_message(data['warn'], 'warn');
            }
        },
        error: function (jqXHR, textStatus, errorThrown) {
            showError("ERROR in emailReceipt: " + textStatus, jqXHR);
        }
    });
}
// receipt - display a receipt for the transaction for this badge
function receipt(index) {
    var row = spacestable.getRow(index);
    var transid = row.getCell("transid").getValue();
    $.ajax({
        method: "POST",
        url: "scripts/getReceipt.php",
        data: { transid },
        success: function (data, textstatus, jqxhr) {
            if (data['error'] !== undefined) {
                show_message(data['error'], 'error');
                return;
            }
            if (data['success'] !== undefined) {
                show_message(data['success'], 'success');
            }
            if (data['warn'] !== undefined) {
                show_message(data['warn'], 'warn');
            }
            displayReceipt(data);
            if (data['success'] !== undefined)
                show_message(data.success, 'success');
        },
        error: function (jqXHR, textStatus, errorThrown) {
            showError("ERROR in getReceipt: " + textStatus, jqXHR);
        }
    });

}
function actionbuttons(cell, formatterParams, onRendered) {
    var btns = "";
    var data = cell.getData();
    var transid = data['transid'] || 0;
    var index = cell.getRow().getIndex();
    var req = data['requested_units'] || 0;
    var app = data['approved_units'] || 0;
    var pur = data['purchased_units'] || 0;

    if (req > 0 && (pur < app || pur == 0)) {
        if (app > 0) {
            btns += '<button class="btn btn-small btn-warning" style = "--bs-btn-padding-y: .0rem; --bs-btn-padding-x: .3rem; --bs-btn-font-size: .75rem;", onclick="approval(' + index + ",'c'" + ')">Change</button>';
        } else {
            btns += '<button class="btn btn-small btn-primary" style = "--bs-btn-padding-y: .0rem; --bs-btn-padding-x: .3rem; --bs-btn-font-size: .75rem;", onclick="approval(' + index + ",'r'" + ')">Approve Req.</button>' +
                '<button class="btn btn-small btn-primary" style = "--bs-btn-padding-y: .0rem; --bs-btn-padding-x: .3rem; --bs-btn-font-size: .75rem;", onclick="approval(' + index + ",'o'" +  ')">Approve Other</button>';
        }
    }

    // receipt buttons
    if (transid > 0)
        btns += '<button class="btn btn-small btn-secondary" style = "--bs-btn-padding-y: .0rem; --bs-btn-padding-x: .3rem; --bs-btn-font-size: .75rem;", onclick="receipt(' + index + ')">Receipt</button>';
    return btns;
}
