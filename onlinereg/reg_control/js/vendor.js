//import { TabulatorFull as Tabulator } from 'tabulator-tables';
// globals required for exhibitorProfile.js
exhibitor_info = null;
exhibitorProfile = null;
region_list = null;
exhibits_spaces = null;
exhibitor_spacelist = null;
regions = null;
spaces =null;
country_options = null;

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
    #spaceRow = null;
    #exhibitorId = null;
    #regionId = null;
    #regionYearId = null;
    #regionGroupId = '';

    // approvals items
    #approvalsTable = null;
    #approvalValues = ['none', 'requested', 'approved', 'denied', 'hide'];
    #approvalRow = null;

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
        var id = document.getElementById('profile');

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
        for (var idO in ownerKeys) {
            var owner = ownerKeys[idO];
            var ownerId = owner.replaceAll(' ', '-');
            this.#ownerTabs[ownerId] = document.getElementById(ownerId + '-content');

            // regions within owners
            var regions = regionOwners[owner];
            var regionKeys = Object.keys(regions);
            for (var idR in regionKeys) {
                var region = regions[regionKeys[idR]];
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
            url: "scripts/exhibitorsGetData.php",
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
        this.#regionGroupId = groupid;
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
        this.drawSpacesTable(data,  groupid, true);
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
            "        <div class='col-sm-12' id='" + groupid + "-exh-table-div'></div>\n" +
            "    </div>\n" +
            "    <div class='row'>\n" +
            "        <div class='col-sm-12'>\n" +
            "            <button class='btn btn-secondary' id='addExhibitorBtn' onClick=" + '"exhibitor.addNew();"' + ">Add New Exhibitor</button>\n" +
            "        </div>\n" +
            "    </div>\n" +
            "</div></div>\n"

        return html;
    }

    // drawSpacesTable - now that the DOM is created, draw the actual table
    drawSpacesTable(data, groupid, newTable) {
        // build new data array
        var regions = [];
        var region = null;
        var currentRegion = -1;
        var spaces = data['detail'];
        var spaceKeys = Object.keys(spaces);
        var spaceHTML = '';
        var req = 0;
        var app = 0;
        var pur = 0;
        for (var idS in spaceKeys) {
            var space = spaces[idS];
            var newRegion = space['exhibitsRegionYearId'];
            if (newRegion != currentRegion) {
                // change in region
                if (currentRegion > 0) {
                    region['space'] = spaceHTML + "</div>";
                    region['req'] = req;
                    region['app'] = app;
                    region['pur'] = pur;
                    regions[currentRegion] = make_copy(region);
                    spaceHTML = '';
                    req = 0;
                    app = 0;
                    pur = 0;
                }
                currentRegion = newRegion;
                spaceHTML = '<div class="container-fluid">';
                req += space['requested_units'];
                app += space['approved_units'];
                pur += space['purchased_units'];
                region = { eYRid: currentRegion, regionId: space['regionId'], regionYearId: space['exhibitsRegionYearId'],
                    exhibitorId: space['exhibitorId'], exhibitorName: space['exhibitorName'], website: space['website'], exhibitorEmail: space['exhibitorEmail'],
                    transid: space['transid'], s1: space['b1'], s2: space['b2'], s3: space['b3'], };
            }
            // add the space data as a formatted region
            spaceHTML += '<div class="row">' +
                '<div class="col-sm-12"><STRONG>' + space['spaceName'] + '</STRONG></div></div>';

            if (blankIfNull(space['requested_units']) != '') {
                spaceHTML += '<div class="row"><div class="col-sm-2' + (blankIfNull(space['approved_units']) == '' ? ' text-danger' : '') + '">Requested: </div>' +
                    '<div class="col-sm-2 text-right">' + blankIfNull(space['requested_units']) + '</div>' +
                    '<div class="col-sm-3">' + blankIfNull(space['requested_description']) + '</div>' +
                    '<div class="col-sm-4">' + blankIfNull(space['time_requested']) + '</div>' +
                    '</div>';
            }

            if (blankIfNull(space['approved_units']) != '') {
                spaceHTML += '<div class="row"><div class="col-sm-2">Approved: </div>' +
                    '<div class="col-sm-2 text-right">' + blankIfNull(space['approved_units']) + '</div>' +
                    '<div class="col-sm-3">' + blankIfNull(space['approved_description']) + '</div>' +
                    '<div class="col-sm-4">' + blankIfNull(space['time_approved']) + '</div>' +
                    '</div>';
            }
            if (blankIfNull(space['purchased_units']) != '') {
                spaceHTML += '<div class="row"><div class="row"><div class="col-sm-2">Purchased: </div>' +
                '<div class="col-sm-2 text-right">' + blankIfNull(space['purchased_units']) + '</div>' +
                '<div class="col-sm-3">' + blankIfNull(space['purchased_description']) + '</div>' +
                '<div class="col-sm-4">' + blankIfNull(space['time_purchased']) + '</div>' +
                '</div>';
            }
        }
        if (currentRegion > 0) {
            region['space'] = spaceHTML + "</div>";
            region['req'] = req;
            region['app'] = app;
            region['pur'] = pur;
            regions.push(make_copy(region));
        }

        console.log("regions:");
        console.log(regions);
        if (newTable) {
            this.#spacesTable = new Tabulator('#' + groupid + '-spaces-table-div', {
                data: regions,
                layout: "fitDataTable",
                index: 'eYRid',
                pagination: true,
                paginationSize: 25,
                paginationSizeSelector: [10, 25, 50, 100, 250, true], //enable page size select element with these options
                columns: [
                    {
                        title: "Exhibitor Space Requests Detail:", columns: [
                            {title: "eYRid", field: "eYRid", visible: false},
                            {title: "regionId", field: "regionId", visible: false},
                            {field: "transid", visible: false},
                            {field: "app", visible: false},
                            {field: "req", visible: false},
                            {field: "pur", visible: false},
                            {title: "exhibitorId", field: "exhibitorId", visible: false},
                            {title: "Name", field: "exhibitorName", width: 200, headerSort: true, headerFilter: true,},
                            {title: "Website", field: "website", width: 200, headerSort: true, headerFilter: true,},
                            {title: "Email", field: "exhibitorEmail", width: 200, headerSort: true, headerFilter: true,},
                            {title: "Requested, Approved, Purchased", field: "space", width: 800, formatter: this.htmlFormatter, variableHeight: true,},
                            {
                                title: "",
                                field: "s1",
                                formatter: this.spaceApprovalButton,
                                formatterParams: {name: 'Approve Req'},
                                maxWidth: 200,
                                hozAlign: "center",
                                cellClick: this.spApprovalReq,
                                headerSort: false,
                            },
                            {
                                title: "",
                                field: "s2",
                                formatter: this.spaceApprovalButton,
                                formatterParams: {name: 'Approve Other'},
                                maxWidth: 200,
                                hozAlign: "center",
                                cellClick: this.spApprovalOther,
                                headerSort: false,
                            },
                            {
                                title: "",
                                field: "s3",
                                formatter: this.spaceApprovalButton,
                                formatterParams: {name: 'Receipt'},
                                maxWidth: 200,
                                hozAlign: "center",
                                cellClick: exhibitors.spaceReceipt,
                                headerSort: false,
                            },
                        ]
                    }
                ]
            });
        } else {
            this.#spacesTable.replaceData(regions);
        }
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
                        {title: "Name", field: "exhibitorName", width: 200, headerSort: true, headerFilter: true,},
                        {title: "Website", field: "website", headerSort: true, headerFilter: true,},
                        {title: "Email", field: "exhibitorEmail", headerSort: true, headerFilter: true,},
                        {title: "Approval", field: "approval", headerSort: true, headerFilter: 'list', headerFilterParams: {values: this.#approvalValues},},
                        {title: "Timestamp", field: "updateDate", headerSort: true, },
                        {title: "", field: "b1", formatter: this.approvalButton, formatterParams: {name: 'Approve'}, width: 100, hozAlign: "center", cellClick: this.exhApprove, headerSort: false,},
                        {title: "", field: "b2", formatter: this.approvalButton, formatterParams: {name: 'Reset'}, width: 80, hozAlign: "center", cellClick: this.exhReset, headerSort: false,},
                        {title: "", field: "b3", formatter: this.approvalButton, formatterParams: {name: 'Deny'}, width: 80, hozAlign: "center", cellClick: this.exhDeny, headerSort: false,},
                        {title: "", field: "b4", formatter: this.approvalButton, formatterParams: {name: 'Hide'}, width: 80, hozAlign: "center", cellClick: this.exhHide, headerSort: false,},
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
                        {title: "Name", field: "exhibitorName", width: 200, headerSort: true, headerFilter: true, tooltip: this.buildRecordHover,},
                        {title: "Email", field: "exhibitorEmail", headerSort: true, headerFilter: true,},
                        {title: "Phone", field: "exhibitorPhone", width: 140, headerSort: true, headerFilter: true,},
                        {title: "Website", field: "website", headerSort: true, headerFilter: true,},
                        {title: "Contact Id", field: "contactId", visible: false, },
                        {title: "Contact Name", field: "contactName", headerSort: true, headerFilter: true, },
                        {title: "Contact Email", field: "contactEmail", headerSort: true, headerFilter: true,},
                        {title: "Con Phone", field: "contactPhone", width: 140, headerSort: true, headerFilter: true,},
                        {title: "City", field: "city", width: 140, headerSort: true, headerFilter: true,},
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
        var exhibitorRow = cell.getRow()
        var exhibitorData = exhibitorRow.getData();
        exhibitors.editExhibitor(exhibitorData, exhibitorRow);
    }

    // approve an approval request
    exhApprove(e, cell) {
        var exhibitorRow = cell.getRow()
        var exhibitorData = exhibitorRow.getData();
        exhibitors.processApprovalChange('approved', exhibitorData, exhibitorRow);
    }

    // reset an approval back to request
    exhReset(e, cell) {
        var exhibitorRow = cell.getRow()
        var exhibitorData = exhibitorRow.getData();
        exhibitors.processApprovalChange('requested', exhibitorData, exhibitorRow);
    }

    // deny an approval request
    exhDeny(e, cell) {
        var exhibitorRow = cell.getRow()
        var exhibitorData = exhibitorRow.getData();
        exhibitors.processApprovalChange('denied', exhibitorData, exhibitorRow);
    }

    // hid a region (hide status)
    exhHide(e, cell) {
        var exhibitorRow = cell.getRow()
        var exhibitorData = exhibitorRow.getData();
        exhibitors.processApprovalChange('hide', exhibitorData, exhibitorRow);
    }


    // html formatter
    htmlFormatter(cell, formatterParams, onRendered) {
        return cell.getValue();
    }
    // button formatters

    // edit exhibitor Record
    editbutton(cell, formatterParams, onRendered) {
        return '<button class="btn btn-secondary" style = "--bs-btn-padding-y: .0rem; --bs-btn-padding-x: .3rem; --bs-btn-font-size: .75rem;">Edit</button>';
    }
    // change exhibitor password buttons
    resetpwbutton(cell, formatterParams, onRendered) {
        return '<button class="btn btn-secondary" style = "--bs-btn-padding-y: .0rem; --bs-btn-padding-x: .3rem; --bs-btn-font-size: .75rem;">Reset' + formatterParams['name'] +
        'PW</button>';
    }

    // tabulator button formatters
    spaceApprovalButton(cell, formatterParams, onRendered) {
        var name = formatterParams['name'];
        var data = cell.getData();
        if (name.startsWith('Approve')) {
            var req = data['req'] || 0;
            var app = data['app'] || 0;
            var pur = data['pur'] || 0;

            if (req > 0 && (pur < app || pur == 0)) {
                if (app > 0 && name == 'Approve Other')
                    return '<button class="btn btn-small btn-warning" style = "--bs-btn-padding-y: .0rem; --bs-btn-padding-x: .3rem; --bs-btn-font-size: .75rem;">Change</button>';
                if (app == 0)
                    return '<button class="btn btn-small btn-primary" style = "--bs-btn-padding-y: .0rem; --bs-btn-padding-x: .3rem; --bs-btn-font-size: .75rem;", >' + name + '</button>';
                }
            }

        var transid = data['transid'] || 0;
        // receipt buttons
        if (transid > 0)
            return '<button class="btn btn-small btn-secondary" style = "--bs-btn-padding-y: .0rem; --bs-btn-padding-x: .3rem; --bs-btn-font-size: .75rem;">' + name + '</button>';
        return '';
    }

    // request approval buttons
    approvalButton(cell, formatterParams, onRendered) {
        var data = cell.getData();
        var id = data['id'];
        var approval = data['approval'] || 'none';
        var name = formatterParams['name'];
        var color = 'secondary';

        switch (approval) {
            case 'none':
            case 'requested':
                if (name == 'Reset')
                    return '';
                break;
            case 'approved':
                if (name == 'Approve')
                    return '';
                break;
            case 'denied':
                if (name == 'Deny')
                    return '';
                break;
            case 'hide':
                if (name == 'Hide')
                    return '';
                break;
        }
        switch (name) {
            case 'Approve':
                color = 'primary';
                break;
            case 'Reset':
                color = 'secondary';
                break;
            case 'Deny':
                color = 'warning'
                break;
            case 'Hide':
                color = 'danger';
                break;
        }
        return '<button class="btn btn-small btn-' + color + '" style = "--bs-btn-padding-y: .0rem; --bs-btn-padding-x: .3rem; --bs-btn-font-size: .75rem;">' + name + '</button>';
    }

    // editExhibitor - Populate edit vendor modal with current data
    editExhibitor(exhibitor, exhibitorRow = null) {
        if (this.#debug & 4)
            console.log(exhibitor);
    exhibitor_info = exhibitor;
    exhibitorProfile.profileModalOpen('update', exhibitor['exhibitorId'], exhibitor['contactId'], exhibitorRow);
    }

    // reset an exhibitor's password
    resetpw(e, cell) {
        var exhibitorId = cell.getRow().getCell("exhibitorId").getValue();
        $.ajax({
            url: 'scripts/exhibitorsSetPassword.php',
            method: "POST",
            data: { 'exhibitorId': exhibitorId, type: 'exhibitor' },
            success: function (data, textStatus, jqXhr) {
                if(data['error'] != undefined) { console.log(data['error']); }
                alert(data['password']);
            },
            error: function (jqXHR, textStatus, errorThrown) {
                showError("ERROR in emailReceipt: " + textStatus, jqXHR);
            }
        });
    }

    // reset a contact's password
    resetCpw(e, cell) {
        var contactId = cell.getRow().getCell("contactId").getValue();
        $.ajax({
            url: 'scripts/exhibitorsSetPassword.php',
            method: "POST",
            data: { 'contactId': contactId, type: 'contact' },
            success: function (data, textStatus, jqXhr) {
                if(data['error'] != undefined) { console.log(data['error']); }
                alert(data['password']);
            },
            error: function (jqXHR, textStatus, errorThrown) {
                showError("ERROR in emailReceipt: " + textStatus, jqXHR);
            }
        });
    }

    // processApprovalChange - change the value of the approval record for this exhibitor
    processApprovalChange(value, approvalData, approvalRow) {
        this.#approvalRow = approvalRow
        $.ajax({
            url: 'scripts/exhibitorsSetApproval.php',
            method: "POST",
            data: { approvalData: approvalData, approvalValue: value },
            success: function (data, textstatus, jqXHR) {
                exhibitors.approvalChangeSuccess(data);
                },
            error: showAjaxError
        });
    }

    // approvalChangeSuccess - successful return from setting the record
    approvalChangeSuccess(data) {
        if (data['status'] == 'error') {
            show_message(data['message'], 'error');
        } else {
            if (data['message'])
                show_message(data['message'], 'success')
            if (this.#approvalRow) {
                this.#approvalRow.update(data['info']);
            }
        }
    }

    // spaceApprovals - process a space approval
    spApprovalReq(e, cell) {
        exhibitors.spaceApprovalReq(e, cell);
    }

    spApprovalOther(e, cell) {
        exhibitors.spaceApprovalOther(e, cell);
    }

    spaceApprovalReq(e, cell) {
        this.#spaceRow = cell.getRow();
        var exhibitorData = this.#spaceRow.getData();
        $.ajax({
            url: 'scripts/exhibitorsSpaceApproval.php',
            method: "POST",
            data: { exhibitorData: exhibitorData, approvalType: 'req' },
            success: function (data, textstatus, jqXHR) {
                exhibitors.spaceApprovalSuccess(data);
            },
            error: showAjaxError
        });
    }

    spaceApprovalOther(e, cell) {
        this.#spaceRow = cell.getRow();
        var exhibitorData = this.#spaceRow.getData();
        this.#exhibitorId = exhibitorData['exhibitorId'];
        this.#regionId = exhibitorData['regionId'];
        this.#regionYearId = exhibitorData['regionYearId'];

        console.log("Space Approval for " + exhibitorData['exhibitorName'] + " of type other");

        $.ajax({
            url: 'scripts/exhibitorGetSingleData.php',
            method: "POST",
            data: { regionId: exhibitorData['regionId'], exhibitorId: exhibitorData['exhibitorId'] },
            success: function (data, textstatus, jqXHR) {
                exhibitors.spaceAppDataSuccess(data);
            },
            error: showAjaxError
        });

    }

    // spaceApprovalSuccess - successful return from marking the space approval
    spaceApprovalSuccess(data) {
        if (data['status'] == 'error') {
            show_message(data['message'], 'error');
        } else {
            if (data['message'])
                show_message(data['message'], 'success')
            if (this.#spaceRow) {
                this.#spaceRow.update(data['info']);
            }
        }
    }

    // spaceAppDataSuccess - set Javascript globals and open the request up
    spaceAppDataSuccess(data) {
        region_list = data['region_list'];
        exhibits_spaces = data['exhibits_spaces'];
        exhibitor_info = data['exhibitor_info'];
        exhibitor_spacelist = data['exhibitor_spacelist'];
        regions = data['regions'];
        spaces = data['spaces'];
        country_options = data['country_options'];
        exhibitorRequest.openReq(this.#regionYearId, 2);
    }

    // update the row just changed
    UpdateSpaceRow(details) {
        if (this.#spaceRow) {
            this.drawSpacesTable(details, this.#regionGroupId, false);
        }
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
    exhibitorRequestOnLoad();
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
