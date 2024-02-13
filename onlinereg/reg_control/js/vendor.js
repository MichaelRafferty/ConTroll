//import { TabulatorFull as Tabulator } from 'tabulator-tables';
// globals required for exhibitorProfile.js
vendor_info = null;
exhibitorProfile = null;

// exhibitors class - functions for spae ownerto review and approve spaces requested by exhibitors
class exhibitorsAdm {

    // global items
    #conid = null;
    #debug = 0;
    #debugVisible = false;
    #message_div = null;
    #result_message_div = null;

    // Space items
    #spacesTable = null;

    // approvals items
    #approvalsTable = null;
    #approvalValues = ['none', 'requested', 'approved', 'denied', 'hide'];

    // exhibitor items
    #exhibitorsTable = null;
    #pricelists = null;

    // Owner items
    #ownerTabs = {};
    #currentOwner = null;

    // Region items
    #currentRegion = null;
    #regionTabs = {};

    // Spaces items
    #currentSpace = null;
    #spacesTabs = {};

    constructor(conid, debug) {
        this.#debug = debug;
        this.#conid = conid;
        this.#message_div = document.getElementById('test');
        this.#result_message_div = document.getElementById('result_message');
        id = document.getElementById('profile');

        if (this.#debug & 1) {
            console.log("Debug = " + debug);
            console.log("conid = " + conid);
        }
        if (this.#debug & 2) {
            this.#debugVisible = true;
        }

        // exhibitors
        exhibitorProfile = new ExhibitorProfile(this.#debug, config['portalType']);

        // owners
        this.#ownerTabs['overview'] = document.getElementById('overview-content');
        this.#currentOwner = this.#ownerTabs['overview'];
        var ownerKeys = Object.keys(regionOwners);
        for (var id in ownerKeys) {
            var owner = ownerKeys[id];
            var ownerId = owner.replaceAll(' ', '-');
            this.#ownerTabs[ownerId] = document.getElementById(ownerId + '-content');

            // regions within owners
            var regions = regionOwners[owner];
            var regionKeys = Object.keys(regions);
            for (var id in regionKeys) {
                var region = regions[regionKeys[id]];
                var regionId = region['name'].replaceAll(' ', '-');
                this.#regionTabs[regionId] = document.getElementById(regionId + '-div');
            }
        }

        if (this.#debug & 4) {
            console.log("ownerTabs");
            console.log(this.#ownerTabs);
            console.log("regionTabs");
            console.log(this.#regionTabs);
        }
    };

    // common code for changing tabs
    // top level - overview, owner
    settabOwner(tabname) {
        // need to add the do you wish to save dirty data item
        clearError();
        clear_message();
        var content = tabname.replace('-pane', '');

        if (this.#currentOwner)
            this.#currentOwner.hidden = true;
        this.#ownerTabs[content].hidden = false;
        this.#currentOwner = this.#ownerTabs[content];
        if (this.#currentRegion) {
            this.#currentRegion.hidden = true;
            this.#currentRegion = null;
        }
        var ownerLookup = regionOwnersTabNames[tabname];
        var regions = regionOwners[ownerLookup];
        var regionKey = Object.keys(regions)[0];
        var region = regions[regionKey];
        this.settabRegion(region['name'].replaceAll(' ', '-') + '-pane');
    }

    // second level - region
    settabRegion(tabname) {
        // need to add the do you wish to save dirty data item
        clearError();
        clear_message();
        var content = tabname.replace('-pane', '');
        if (this.#currentRegion)
            this.#currentRegion.hidden = true;
        this.#regionTabs[content].hidden = false;
        this.#currentRegion = this.#regionTabs[content];

        // now re-draw the specific tab
        this.open(tabname);
    }

    settabData(tabname) {
        clearError();
        clear_message();

        var content = tabname.replace('-pane', '');
        if (this.#currentSpace)
            this.#currentSpace.hidden = true;
        this.#spacesTabs[content].hidden = false;
        this.#currentSpace = this.#spacesTabs[content];
    }

    // open(tabname) - fetch the data and re-draw the region tab
    open(tabname) {
        if (this.#debug & 1)
            console.log("opening " + tabname)

        // get the data for this tab
        $.ajax({
            url: "scripts/getExhibitorData.php",
            method: "POST",
            data: { region: regionTabNames[tabname]['name'], regionId: regionTabNames[tabname]['id']},
            success: getExhibitorDataDraw,
            error: function (jqXHR, textStatus, errorThrown) {
                showError("ERROR in getExhibitorData: " + textStatus, jqXHR);
                return false;
            }
        })
    }

    draw(data) {
        if (data['error']) {
            show_message(data['error'], 'error');
            this.#message_div.innerHTML = "Query:\n" + data['query'] + "\n\n" + "Args: " + data['args'].toString();
            return;
        }
        this.#message_div.innerHTML = '';
        this.#pricelists = data['price_list'];

        if (this.#debug & 8)
            console.log(data);

        var regionName = data['post']['region'];
        var divId = regionName.replaceAll(' ','-') + '-div';
        var dataDiv = document.getElementById(divId)

        // build up the html for this tab
        var html = this.drawSummary(data);
        // add in tabs for spaces, approvals and exhibitor
        var region = regions[regionName];
        var groupid = 'data-' + region['id'];
        html += "<ul class='nav nav-tabs mb-3' id='" + groupid + "-tab' role='tablist'>\n" +
            "<li class='nav-item' role='presentation'>\n" +
            "<button class='nav-link active' id='" + groupid + "-spaces-tab' data-bs-toggle='pill' data-bs-target='#" + groupid + "-spaces-pane' type='button' role='tab' aria-controls='nav-spaces'\n" +
            "       aria-selected='true' onclick=" + '"' + "exhibitors.settabData('" + groupid + "-spaces-pane');" + '"' + ">Space Requests\n" +
            "</button>\n" +
            "</li>\n";
        if (region['requestApprovalRequired'] != 'None') {
            html += "<li class='nav-item' role='presentation'>\n" +
                "<button class='nav-link' id='" + groupid + "-app-tab' data-bs-toggle='pill' data-bs-target='#" + groupid + "-app-pane' type='button' role='tab' aria-controls='nav-app'\n" +
                "       aria-selected='false' onclick=" + '"' + "exhibitors.settabData('" + groupid + "-app-pane');" + '"' + ">Approval Requests\n" +
                "</button>\n" +
                "</li>\n";
        }
        html += "<li class='nav-item' role='presentation'>\n" +
            "<button class='nav-link' id='" + groupid + "-exh-tab' data-bs-toggle='pill' data-bs-target='#" + groupid + "-exh-pane' type='button' role='tab' aria-controls='nav-exh'\n" +
            "       aria-selected='false' onclick=" + '"' + "exhibitors.settabData('" + groupid + "-exh-pane');" + '"' + ">Exhibitors Information\n" +
            "</button>\n" +
            "</li>\n";
        html += "</ul>\n";

        html += this.drawSpaces(data, groupid);
        if (region['requestApprovalRequired'] != 'None') {
            html += this.drawApprovals(data,  groupid);
        }
        html += this.drawExhibitors(data, groupid);
        dataDiv.innerHTML = html;

        this.#spacesTabs = {}
        this.#spacesTabs[groupid + '-spaces'] = document.getElementById(groupid + '-spaces-content');
        this.#spacesTabs[groupid + '-app'] = document.getElementById(groupid + '-app-content');
        this.#spacesTabs[groupid + '-exh'] = document.getElementById(groupid + '-exh-content');
        this.settabData(groupid + '-spaces-pane');
        this.drawSpacesTable(data,  groupid);
        if (region['requestApprovalRequired'] != 'None') {
            this.drawApprovalsTable(data, groupid);
        }
        this.drawExhibitorsTable(data,  groupid);
    }

    // summary status at the top of the screen
    drawSummary(data) {
        var summary = data['summary'];
        if (!summary)
            return;

        var html = '<div class="container-fluid">';

        for (var spaceid in summary) {
            var space = summary[spaceid];
            var remaining =  space['unitsAvailable'] - space['approved'];
            html += `    <div class="row mt-0 mb-0 p-0">
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

        html += '<hr/></div>';
        return html;
    }

    // drawSpaces
    // update the space detail section from the detail portion of the returned data
    drawSpaces(data, groupid) {
        if (this.#spacesTable !== null) {
            this.#spacesTable.destroy();
            this.#spacesTable = null;
        }
        var html = "<div class='tab-content ms-2' id='" + groupid + "-spaces-content' hidden>\n" +
            "<div class='container-fluid'>\n" +
            "    <div class='row'>\n" +
            "        <div class='col-sm-12' id='" + groupid + "-spaces-table-div'></div>\n" +
            "    </div>\n" +
            "    <div class='row'>\n" +
            "        <div class='col-sm-12'>\n" +
            "            <button class='btn btn-secondary' id='addVendorSpaceBtn' onClick=" + '"exhibitor.addNewSpace();"' + ">Add New Vendor Space</button>\n" +
            "        </div>\n" +
            "    </div>\n" +
            "</div></div>\n"

        return html;
    }

    // drawApprovals
    // update the approvals detail section from the detail portion of the returned data
    drawApprovals(data, groupid) {
        if (this.#approvalsTable !== null) {
            this.#approvalsTable.destroy();
            this.#approvalsTable = null;
        }
        var html = "<div class='tab-content ms-2' id='" + groupid + "-app-content' hidden>\n" +
            "<div class='container-fluid'>\n" +
            "    <div class='row'>\n" +
            "        <div class='col-sm-12' id='" + groupid + "-app-table-div'></div>\n" +
            "    </div>\n" +
            //"    <div class='row'>\n" +
            //"        <div class='col-sm-12'>\n" +
            //"            <button class='btn btn-secondary' id='addVendorSpaceBtn' onClick=" + '"exhibitor.addNewSpace();"' + ">Add New Vendor Space</button>\n" +
            //"        </div>\n" +
            //"    </div>\n" +
            "</div></div>\n"

        return html;
    }

    // drawExhibitors
    // update the exhibitor detail section from the detail portion of the returned data
    drawExhibitors(data, groupid) {
        if (this.#exhibitorsTable !== null) {
            this.#exhibitorsTable.destroy();
            this.#exhibitorsTable = null;
        }
        var html = "<div class='tab-content ms-2' id='" + groupid + "-exh-content' hidden>\n" +
            "<div class='container-fluid'>\n" +
            "    <div class='row'>\n" +
            "        <div class='col-sm-12' id='" + groupid + "-exh-table-div'>Hello Exhibitors!</div>\n" +
            "    </div>\n" +
            //"    <div class='row'>\n" +
            //"        <div class='col-sm-12'>\n" +
            //"            <button class='btn btn-secondary' id='addVendorSpaceBtn' onClick=" + '"exhibitor.addNewSpace();"' + ">Add New Vendor Space</button>\n" +
            //"        </div>\n" +
            //"    </div>\n" +
            "</div></div>\n"

        return html;
    }

    // drawSpacesTable - now that the DOM is created, draw the actual table
    drawSpacesTable(data, groupid) {
        var requested = data['summary']['requested'];
        var approved = data['summary']['approved'];
        var purchased = data['summary']['purchased'];

        this.#spacesTable = new Tabulator('#' + groupid + '-spaces-table-div', {
            data: data['detail'],
            layout: "fitDataTable",
            pagination: true,
            paginationSize: 25,
            paginationSizeSelector: [10, 25, 50, 100, 250, true], //enable page size select element with these options
            columns: [
                {title: "Exhibitor Space Requests Detail:", columns: [
                        {title: "id", field: "id", visible: false},
                        {title: "vendorId", field: "vendorId", visible: false},
                        {title: "spaceId", field: "spaceId", visible: false},
                        {title: "requested_code", field: "requested_code", visible: false},
                        {title: "approved_code", field: "approved_code", visible: false},
                        {title: "purchased_code", field: "purchased_code", visible: false},
                        {title: "Name", field: "exhibitorName", width: 150, headerSort: true, headerFilter: true,},
                        {title: "Website", field: "website", width: 150, headerSort: true, headerFilter: true,},
                        {title: "Email", field: "exhibitorEmail", headerSort: true, headerFilter: true,},
                        {title: "Space", field: "spaceName", width: 180, headerSort: true, headerFilter: true },
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
                        {title: "", formatter: this.exhibitorSpacesActionButtons, hozAlign: "left", headerSort: false,},
                    ]
                }
            ]
        });
    }

    // drawApprovalsTable - now that the DOM is created, draw the actual table
    drawApprovalsTable(data, groupid) {
        this.#approvalsTable = new Tabulator('#' + groupid + '-app-table-div', {
            data: data['approvals'],
            layout: "fitDataTable",
            pagination: true,
            paginationSize: 25,
            paginationSizeSelector: [10, 25, 50, 100, 250, true], //enable page size select element with these options
            columns: [
                {title: "Exhibitor Approval Requests Detail:", columns: [
                        {title: "Region", field: "name", headerSort: true, headerFilter: true },
                        {title: "id", field: "id", visible: false},
                        {title: "exhibitorId", field: "exhibitorId", visible: false},
                        {title: "Name", field: "exhibitorName", headerSort: true, headerFilter: true,},
                        {title: "Website", field: "website", headerSort: true, headerFilter: true,},
                        {title: "Email", field: "exhibitorEmail", headerSort: true, headerFilter: true,},
                        {title: "Approval", field: "approval", headerSort: true, headerFilter: 'list', headerFilterParams: {values: this.#approvalValues},},
                        {title: "Timestamp", field: "updateDate", headerSort: true, },
                        {title: "", formatter: this.exhibitorApprovalActionButtons, hozAlign: "left", headerSort: false,},
                    ]
                }
            ]
        });
    }

    // drawExhibitorsTable
    // update the exhibitors div with the table of exhibitors
    drawExhibitorsTable(data, groupid) {
        this.#exhibitorsTable = new Tabulator('#' + groupid + '-exh-table-div', {
            data: data['exhibitors'],
            layout: "fitDataTable",
            pagination: true,
            paginationSize: 25,
            paginationSizeSelector: [10, 25, 50, 100, 250, true], //enable page size select element with these options
            columns: [
                {title: "Vendors:", columns: [
                        {title: "Exhibitor Id", field: "exhibitorId", visible: false,},
                        {title: "Name", field: "exhibitorName", headerSort: true, headerFilter: true, tooltip: this.buildRecordHover,},
                        {title: "Email", field: "exhibitorEmail", headerSort: true, headerFilter: true,},
                        {title: "Phone", field: "exhibitorPhone", headerSort: true, headerFilter: true,},
                        {title: "Website", field: "website", headerSort: true, headerFilter: true,},
                        {title: "Contact Id", field: "contactId", visible: false, },
                        {title: "Contact Name", field: "contactName", headerSort: true, headerFilter: true, },
                        {title: "Contact Email", field: "contactEmail", headerSort: true, headerFilter: true,},
                        {title: "Contact Phone", field: "contactPhone", headerSort: true, headerFilter: true,},
                        {title: "City", field: "city", headerSort: true, headerFilter: true,},
                        {title: "State", field: "state", headerSort: true, headerFilter: true,},
                        {title: "", formatter: this.editbutton, hozAlign: "center", cellClick: this.edit, headerSort: false,},
                        {title: "", formatter: this.resetpwbutton, formatterParams: {name: 'Exh'}, hozAlign: "center", cellClick: this.resetpw, headerSort: false,},
                        {title: "", formatter: this.resetpwbutton, formatterParams: {name: 'Con'}, hozAlign: "center", cellClick: this.resetCpw, headerSort: false,},
                    ]
                }
            ]
        });
    }

    // show the full vendor record as a hover in the table
    buildRecordHover(e, cell, onRendered) {
        var data = cell.getData();
        //console.log(data);
        var hover_text = 'Exhibitor id: ' + data['id'] + '<br/>' +
            data['exhibitorName'] + '<br/>' +
            'Website: ' + data['website'] + '<br/>' +
            data['addr'] + '<br/>';
        if (data['addr2'] != '') {
            hover_text += data['addr2'] + '<br/>';
        }
        hover_text += data['city'] + ', ' + data['state'] + ' ' + data['zip'] + '<br/>' +
            data['country'] + '<br/>' +
            'Needs New Password: ' + (data['needs_new'] ? 'Yes' : 'No') +  '<br/>' +
            'Publicize: ' + (data['publicity'] ? 'Yes' : 'No') +  '<br/>';
        hover_text += 'Description:<br/>&nbsp;&nbsp;&nbsp;&nbsp;' + data['description'].replaceAll('\n', '<br/>&nbsp;&nbsp;&nbsp;&nbsp;');
        return hover_text;
    }

// button callout functions
    edit(e, cell) {
        var exhibitor = cell.getRow().getData();
        exhibitors.editExhibitor(exhibitor);
    }

    // button formatters
    editbutton(cell, formatterParams, onRendered) {
        return '<button class="btn btn-secondary" style = "--bs-btn-padding-y: .0rem; --bs-btn-padding-x: .3rem; --bs-btn-font-size: .75rem;">Edit</button>';
    }
    resetpwbutton(cell, formatterParams, onRendered) {
        return '<button class="btn btn-secondary" style = "--bs-btn-padding-y: .0rem; --bs-btn-padding-x: .3rem; --bs-btn-font-size: .75rem;">Reset' + formatterParams['name'] +
        'PW</button>';
    }

    // tabulator button formatters (need to be global, not in class
    exhibitorSpacesActionButtons(cell, formatterParams, onRendered) {
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

    exhibitorApprovalActionButtons(cell, formatterParams, onRendered) {
        var btns = "";
        var data = cell.getData();
        var id = data['id'];
        var approval = data['approval'] || 'none';

        if (approval != 'none')
            btns += '<button class="btn btn-small btn-secondary" style = "--bs-btn-padding-y: .0rem; --bs-btn-padding-x: .3rem; --bs-btn-font-size: .75rem;", ' +
                'onclick="exhibibitorSetApproval(' + id + ", 'none')" + '";>Reset</button>';
        if (approval != 'approved')
            btns += '<button class="btn btn-small btn-secondary" style = "--bs-btn-padding-y: .0rem; --bs-btn-padding-x: .3rem; --bs-btn-font-size: .75rem;", ' +
                'onclick="exhibibitorSetApproval(' + id + ", 'approved')" + '";>Approve</button>';
        if (approval != 'deny')
            btns += '<button class="btn btn-small btn-secondary" style = "--bs-btn-padding-y: .0rem; --bs-btn-padding-x: .3rem; --bs-btn-font-size: .75rem;", ' +
                'onclick="exhibibitorSetApproval(' + id + ", 'none')" + '";>Deny</button>';
        if (approval != 'deny')
            btns += '<button class="btn btn-small btn-secondary" style = "--bs-btn-padding-y: .0rem; --bs-btn-padding-x: .3rem; --bs-btn-font-size: .75rem;", ' +
                'onclick="exhibibitorSetApproval(' + id + ", 'hide')" + '";>Hide</button>';
        return btns;
    }

    // editExhibitor - Populate edit vendor modal with current data
    editExhibitor(exhibitor) {
        if (this.#debug & 4)
            console.log(exhibitor);
    vendor_info = exhibitor;
    exhibitorProfile.profileModalOpen('update', exhibitor['exhibitorId'], exhibitor['contactId']);
    }

    // reset an exhibitor's password
    resetpw(e, cell) {
        var exhibitorId = cell.getRow().getCell("exhibitorId").getValue();
        $.ajax({
            url: 'scripts/setPassword.php',
            method: "POST",
            data: { 'exhibitorId': exhibitorId, type: 'exhibitor' },
            success: function (data, textStatus, jqXhr) {
                if(data['error'] != undefined) { console.log(data['error']); }
                alert(data['password']);
            }
        });
    }

    // reset an contact's password
    resetCpw(e, cell) {
        var contactId = cell.getRow().getCell("contactId").getValue();
        $.ajax({
            url: 'scripts/setPassword.php',
            method: "POST",
            data: { 'contactId': contactId, type: 'contact' },
            success: function (data, textStatus, jqXhr) {
                if(data['error'] != undefined) { console.log(data['error']); }
                alert(data['password']);
            }
        });
    }
};

exhibitors = null;

// hook to public class function for exhibitor draw
function getExhibitorDataDraw(data, textStatus, jqXHR) {
    exhibitors.draw(data);
}

// create class on page render
window.onload = function initpage() {
    exhibitors = new exhibitorsAdm(config['conid'], config['debug']);
}

/*
var summaryDiv = null;
var vendortable = null;
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


 */
