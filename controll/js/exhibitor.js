//import { TabulatorFull as Tabulator } from 'tabulator-tables';
// globals required for exhibitorProfile.js
exhibitor_info = null;
exhibitorProfile = null;
region_list = null;
exhibits_spaces = null;
exhibitor_spacelist = null;
exhibitor_perm = null;
regions = null;
spaces =null;
country_options = null;
tabname = null;
fulltabname = null;
regionid = null;
exhibitorsData = null;
customText = null;
configEditor = null;
checkConfigReload = true;
exhibitors = null;
emailBulkSend = null;
var currencyFmt = null;
var vendorInvoice = null;
var profile = null;
var fileManager = null;

// globals for exhibits configuration
exhibits = null;

// exhibitors class - functions for space owner to review and approve spaces requested by exhibitors
class exhibitorsAdm {
    // global items
    #conid = null;
    #exhibitorConid = null;
    #debug = 0;
    #debugVisible = false;
    #message_div = null;
    #result_message_div = null;
    #cacheDirty = false;
    #scriptName = config.scriptName;
    #currentTab = '';
    #currentSubtab = '';
    #exhibitsRegionYearId = null;

    // Space items
    #spacesTable = null;
    #spaceRow = null;
    #exhibitorId = null;
    #regionId = null;
    #regionYearId = null;
    #regionGroupId = '';
    #regionName = '';
    #spaceDetailModal = null;
    #locationsModal = null;
    #locationsUsed = "";

    // approvals items
    #approvalsTable = null;
    #approvalValues = ['none', 'requested', 'approved', 'denied', 'hide'];
    #approvalRow = null;
    #approvalPay = 0;
    // exhibitor items
    #exhibitorsTable = null;
    #pricelists = null;
    #importModal = null;
    #importHTML = null;
    #importTable = null;

    // Owner items
    #ownerTabs = {};
    #currentOwner = null;
    #currentPane = '';

    // Region items
    #currentRegion = null;
    #regionTabs = {};
    #regionType = null;
    #portalType = null;

    // Spaces items
    #currentSpace = null;
    #currentSpaceTab = null;
    #spacesTabs = null;

    // mail order - exhibitor choice items
    #exhibitorChooseModal = null;
    #exhibitorChooseTitle = null;
    #exhibitorHtml = null;
    #exhibitorListTable = null;

    // history items
    #historyModal = null;
    #historyTitle = null;
    #historyDiv = null;
    #historyRow = null;

    // colors
    #approvedColor = '';

    constructor(conid, debug) {
        this.#debug = debug;
        this.#conid = conid;
        this.#exhibitorConid = config.exhibitorConid;
        this.#message_div = document.getElementById('test');
        this.#result_message_div = document.getElementById('result_message');
        currencyFmt = new Intl.NumberFormat(config.locale, {
            style: 'currency',
            currency: config.currency,
        });

        if (this.#debug & 1) {
            console.log("Debug = " + debug);
            console.log("conid = " + conid);
            console.log("exhibitorConid = " + this.#exhibitorConid);
        }
        if (this.#debug & 2) {
            this.#debugVisible = true;
        }

        // exhibitors
        exhibitorProfile = new ExhibitorProfile(this.#debug, config.portalType);
        let id = document.getElementById("import_exhibitor");
        if (id)
            this.#importModal = new bootstrap.Modal(id, {focus: true, backdrop: 'static'});
        this.#importHTML = document.getElementById('importHTML');

        id = document.getElementById("space_detail");
        if (id)
            this.#spaceDetailModal = new bootstrap.Modal(id, {focus: true, backdrop: 'static'});

        id = document.getElementById("locations_edit");
        if (id)
            this.#locationsModal = new bootstrap.Modal(id, {focus: true, backdrop: 'static'});

        id = document.getElementById("exhibitor_choose");
        if (id) {
            this.#exhibitorChooseModal = new bootstrap.Modal(id, {focus: true, backdrop: 'static'});
            this.#exhibitorChooseTitle = document.getElementById('exhibitor_choose_title');
            this.#exhibitorHtml = document.getElementById('exhibitorHtml');
        }

        id = document.getElementById('history');
        if (id != null) {
            this.#historyModal = new bootstrap.Modal(id, { focus: true, backdrop: 'static' });
            this.#historyTitle = document.getElementById('historyTitle');
            this.#historyDiv = document.getElementById('history-div');
        }

        // owners
        this.#ownerTabs.overview = document.getElementById('overview-content');
        this.#ownerTabs.configuration = document.getElementById('configuration-pane');
        this.#ownerTabs.customtext = document.getElementById('customtext-pane');
        this.#ownerTabs.configEdit = document.getElementById('configEdit-pane');
        this.#ownerTabs.fileManager = document.getElementById('fileManager-pane');
        this.#currentOwner = this.#ownerTabs.overview;
        this.#currentPane = 'overview';
        let ownerKeys = Object.keys(regionOwners);
        for (let idO in ownerKeys) {
            let owner = ownerKeys[idO];
            let ownerId = owner.replaceAll(' ', '-');
            this.#ownerTabs[ownerId] = document.getElementById(ownerId + '-content');

            // regions within owners (regionsInOwner)
            let regionsInOwner = regionOwners[owner];
            let regionKeys = Object.keys(regionsInOwner);
            for (let idR in regionKeys) {
                let region = regionsInOwner[regionKeys[idR]];
                let regionId = region.name.replaceAll(' ', '-');
                this.#regionTabs[regionId] = document.getElementById(regionId + '-div');
            }
        }

        if (this.#debug & 4) {
            console.log("ownerTabs");
            console.log(this.#ownerTabs);
            console.log("regionTabs");
            console.log(this.#regionTabs);
        }
        if (config.initialTab != '' && config.initialTab != 'overview') {
            const triggerTabList = document.querySelectorAll('#exhibitor-tab button')
            triggerTabList.forEach(triggerEl => {
                const tabTrigger = new bootstrap.Tab(triggerEl)

                triggerEl.addEventListener('click', event => {
                    event.preventDefault()
                    tabTrigger.show()
                })
            })

            let selectors = '#exhibitor-tab button[data-bs-target="#' + config.initialTab + '-pane"]';
            let triggerEl = document.querySelector(selectors);
            if (triggerEl)
                bootstrap.Tab.getInstance(triggerEl).show(); // Select tab by name

            setTimeout( function() {
                exhibitors.settabOwner(config.initialTab + '-pane');
                }, 250);

        }
    };

    // set / get functions

    getApprovalPay() {
        return this.#approvalPay;
    }

    setCurrentSubtab(tab) {
        this.#currentSubtab = tab;
    }

    setCacheDirty() {
        this.#cacheDirty = true;
    }

    // common code for changing tabs
    // top level - overview, owner
    settabOwner(tabname) {
        // need to add the do you wish to save dirty data item
        clearError();
        clear_message();
        this.#currentTab = tabname.replace('-pane', '');

        if (this.#currentOwner) {
            this.#currentOwner.hidden = true;
        }
        this.#ownerTabs[this.#currentTab].hidden = false;
        this.#currentOwner = this.#ownerTabs[this.#currentTab];
        this.#currentPane = this.#currentTab;
        if (this.#currentTab != 'configuration') {
            if (exhibits) {
                exhibits.close();
                exhibits = null;
            }
        }
        if (customText != null)
            customText.close();

        if (configEditor && checkConfigReload) {
            if (configEditor.close()) {
                checkConfigReload = true;
                configEditor = null;
            } else {
                checkConfigReload = false;
            }
        }

        if (this.#currentRegion) {
            this.#currentRegion.hidden = true;
            this.#currentRegion = null;
        }

        switch (this.#currentTab) {
            case 'overview':
                config.initialTab = ''
                config.initialSubtab = ''
                return;
                ;


            case 'configuration':
                if (exhibits == null)
                    exhibits = new exhibitssetup(config.exhibitorConid, config.debug);
                exhibits.open();
                return;

            case 'customtext':
                if (customText == null)
                    customText = new customTextSetup();
                customText.open();
                config.initialTab = ''
                config.initialSubtab = ''
                return;

            case 'configEdit':
                if (configEditor == null) {
                    this.loadConfigEditor();
                }
                checkConfigReload = true;
                config.initialTab = ''
                config.initialSubtab = ''
                return;

            case 'fileManager':
                fileManager.open();
                return;
        }

        if (this.#cacheDirty) {
            window.location.href = this.#scriptName + '?tab=' + this.#currentTab;
            return;
        }

        let ownerLookup = regionOwnersTabNames[tabname];
        let regionsInOwner = regionOwners[ownerLookup];
        let regionKey = Object.keys(regionsInOwner)[0];
        let region = regionsInOwner[regionKey];
        this.settabRegion(region.name.replaceAll(' ', '-') + '-pane');
    }

// change exhibitor year being displayed
    changeExhibitorConid() {
        let newConventionConid = document.getElementById('limitConid').value;
        if (newConventionConid == this.#exhibitorConid)
            return; // no change
        let href = '?exhibitorConid=' + newConventionConid;
        if (this.#currentTab != '')
            href += '&tab=' + this.#currentTab;
        if (this.#currentSubtab != '')
            href += '&subtab=' + this.#currentSubtab;
        window.location.href = href;
    }

// configuration editor
    loadConfigEditor() {
        let script = 'scripts/configEditLoadData.php';
        let postData = {
            load_type: 'conf',
            perm: 'exhibitor'
        }
        let _this = this;
        clearError();
        clear_message();
        $.ajax({
            url: script,
            method: 'POST',
            data: postData,
            success: function (data, textStatus, jhXHR) {
                if (data.error) {
                    show_message(data.error, 'error');
                    return;
                }
                if (data.warn) {
                    show_message(data.error, 'warn');
                    return;
                }
                checkRefresh(data);
                _this.openConfigEditor(data);
            },
            error: function (jqXHR, textStatus, errorThrown) {
                showError("ERROR in getMenu: " + textStatus, jqXHR);
            },
        });
    }

    openConfigEditor(data) {
        if (data.success) {
            show_message(data.success, 'success');
        }
        configEditor = new ConfigEditor(data);
    }

    // second level - region
    settabRegion(tabname) {
        // need to add the do you wish to save dirty data item
        clearError();
        clear_message();
        let content = tabname.replace('-pane', '');
        if (this.#currentRegion)
            this.#currentRegion.hidden = true;
        this.#regionTabs[content].hidden = false;
        this.#currentRegion = this.#regionTabs[content];

        // now re-draw the specific tab
        this.#currentSpaceTab = tabname;
        this.open(tabname);
    }

    settabData(tabname) {
        clearError();
        clear_message();

        let content = tabname.replace('-pane', '');
        if (this.#currentSpace)
            this.#currentSpace.hidden = true;
        this.#spacesTabs[content].hidden = false;
        this.#currentSpace = this.#spacesTabs[content];
    }

    updateSpace() {
        this.open(this.#currentSpaceTab);
    }

    // open(tabname) - fetch the data and re-draw the region tab
    open(newtabname, message = null) {
        fulltabname = newtabname;
        tabname = regionTabNames[newtabname].name;
        regionid = regionTabNames[newtabname].id;

        if (this.#debug & 1)
            console.log("opening from " + newtabname + " as " + tabname + ", " + regionid);

        // get the data for this tab
        $.ajax({
            url: "scripts/exhibitorsGetData.php",
            method: "POST",
            data: { region: tabname, regionId: regionid, exhibitorConid: config.exhibitorConid },
            success: function (data, textstatus, jqXHR) {
                checkRefresh(data);
                exhibitors.draw(data, message);
            },
            error: function (jqXHR, textStatus, errorThrown) {
                showError("ERROR in getExhibitorData: " + textStatus, jqXHR);
                return false;
            }
        })
    }

    draw(data, message = null) {
        if (data.error) {
            show_message(data.error, 'error');
            this.#message_div.innerHTML = "Query:\n" + data.query + "\n\n" + "Args: " + data.args.toString();
            return;
        }
        this.#regionType = data.regionType;
        this.#portalType = data.portalType
        this.#exhibitsRegionYearId = data.exhibitsRegionYearId;
        this.#message_div.innerHTML = '';
        this.#pricelists = data.price_list;
        if (data.locationsUsed)
            this.#locationsUsed = data.locationsUsed;

        this.#regionId = data.post.regionId;

        if (this.#debug & 8)
            console.log(data);

        let regionName = data.post.region;
        let divId = regionName.replaceAll(' ','-') + '-div';
        let dataDiv = document.getElementById(divId)

        // build up the html for this tab
        let html = this.drawSummary(data);
        // add in tabs for spaces, approvals and exhibitor
        let region = regions[regionName];
        let groupid = 'data-' + region.id;
        this.#regionGroupId = groupid;
        this.#regionName = regionName;
        html += "<ul class='nav nav-tabs mb-3' id='" + groupid + "-tab' role='tablist'>\n" +
            "<li class='nav-item' role='presentation'>\n" +
            "<button class='nav-link active' id='" + groupid + "-spaces-tab' data-bs-toggle='pill' data-bs-target='#" + groupid + "-spaces-pane' type='button' role='tab' aria-controls='nav-spaces'\n" +
            "       aria-selected='true' onclick=" + '"' + "exhibitors.settabData('" + groupid + "-spaces-pane');" + '"' + ">Space Requests\n" +
            "</button>\n" +
            "</li>\n";
        if (region.requestApprovalRequired != 'None') {
            html += "<li class='nav-item' role='presentation'>\n" +
                "<button class='nav-link' id='" + groupid + "-app-tab' data-bs-toggle='pill' data-bs-target='#" + groupid + "-app-pane' type='button' role='tab' aria-controls='nav-app'\n" +
                "       aria-selected='false' onclick=" + '"' + "exhibitors.settabData('" + groupid + "-app-pane');" + '"' + ">Approval Requests\n" +
                "</button>\n" +
                "</li>\n";
        }
        if (config.exhibitorConid == config.conid) {
            html += "<li class='nav-item' role='presentation'>\n" +
                "<button class='nav-link' id='" + groupid + "-exh-tab' data-bs-toggle='pill' data-bs-target='#" + groupid + "-exh-pane' type='button' role='tab' aria-controls='nav-exh'\n" +
                "       aria-selected='false' onclick=" + '"' + "exhibitors.settabData('" + groupid + "-exh-pane');" + '"' + ">Exhibitors Information\n" +
                "</button>\n" +
                "</li>\n";
        }
        html += "</ul>\n";

        html += this.drawSpaces(data, groupid);
        if (region.requestApprovalRequired != 'None') {
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
        if (region.requestApprovalRequired != 'None') {
            this.drawApprovalsTable(data, groupid);
        }
        this.drawExhibitorsTable(data,  groupid);
        if (message)
            show_message(message, 'success');
    }

    redraw(data) {
        let regionName = data.post.region;
        let region = regions[regionName];
        this.drawSpacesTable(data,  null, false);
        if (region.requestApprovalRequired != 'None') {
            this.#approvalsTable.replaceData(data.approvals);
        }
        this.#exhibitorsTable.replaceData(data.exhibitors);
    }
    // summary status at the top of the screen
    drawSummary(data, full = true) {
        if (!data.hasOwnProperty('summary'))
            return;

        let summary = data.summary;
        if (!summary)
            return;

        let html = '';
        if (full)
            html += '<div class="container-fluid" id="summary_div">';

        for (let spaceid in summary) {
            let space = summary[spaceid];
            let remaining =  space.unitsAvailable - space.approved;
            html += `    <div class="row mt-0 mb-0 p-0">
        <div class="col-sm-auto">
            <span style="font-size: 125%; font-weight: bold;">` + space.name + ` Registrations: </span>
        </div>
        <div class="col-sm-auto ms-2 pt-1">New: ` + space.new + `</div>
        <div class="col-sm-auto ms-2 pt-1">Pending: ` + space.pending + `</div>
        <div class="col-sm-auto ms-2 pt-1">Purchased: ` + space.purchased + `</div>
        <div class="col-sm-auto ms-2 pt-1">Remaining: ` + remaining + `</div>
    </div>
        `;
        }

        html += '<hr/>';

        if (full)
            html += '</div>';
        return html;
    }

    // drawSpaces
    // update the space detail section from the detail portion of the returned data
    drawSpaces(data, groupid) {
        if (this.#spacesTable !== null) {
            this.#spacesTable.destroy();
            this.#spacesTable = null;
        }
        let html = "<div class='tab-content ms-2' id='" + groupid + "-spaces-content' hidden>\n" +
            "<div class='container-fluid'>\n" +
            "    <div class='row'>\n" +
            "        <div class='col-sm-12' id='" + groupid + "-spaces-table-div'></div>\n" +
            "    </div>\n" +
            "    <div class='row mt-2 mb-3' id='" + groupid + "-spaces-csv-div'>\n"+
            "       <div class='col-sm-auto p-1 ps-3 pe-3 tabulator-paginator paginationBGColor' id='" + groupid + "-tabSpacesPaginationDiv'></div>\n" +
            "       <div class='col-sm-auto p-1 ms-4'>\n";
        if (config.exhibitorConid == config.conid)
            html +=
            "            <button class='btn btn-sm btn-secondary ms-1 me-1' id='addExhibitorSpaceBtn' onClick=" + '"exhibitors.addNewSpace();">' +
            "               Add New / Pay for Exhibitor Space to Existing Exhibitor</button>\n" +
            "            <button class='btn btn-sm btn-secondary ms-1 me-1' id='addExhibitorBtn2' onClick=" + '"exhibitors.addNew();">' +
            "               Add New Exhibitor</button>\n";
        if (data.usesInventory == 'Y') {
            html +=
            "            <button class='btn btn-sm btn-secondary ms-1 me-1' id='sendInvReminder' onClick=" + '"exhibitors.sendInvReminder();">' +
            "               Send Inventory Reminder Email For Those Missing Inventory</button>\n";
        }
        html +=
            "           <button id='" + groupid + "-spaces-csv' type='button' class='btn btn-info btn-sm'" +
            "               onclick='exhibitors.spacesDownload(\"csv\"); return false;'>Download CSV</button>\n" +
            "           <button id='" + groupid + "-spaces-xlsx' type='button' class='btn btn-info btn-sm'" +
            "               onclick='exhibitors.spacesDownload(\"xlsx\"); return false;'>Download Excel</button>\n" +
            "       </div>\n" +
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
        let html = "<div class='tab-content ms-2' id='" + groupid + "-app-content' hidden>\n" +
            "<div class='container-fluid'>\n" +
            "    <div class='row'>\n" +
            "        <div class='col-sm-12' id='" + groupid + "-app-table-div'></div>\n" +
            "    </div>\n" +
            "    <div class='row mt-2 mb-3' id='" + groupid + "-app-csv-div'>\n"+
            "       <div class='col-sm-auto p-1 ps-3 pe-3 tabulator-paginator paginationBGColor' id='" + groupid + "-tabAppPaginationDiv'></div>\n" +
            "       <div class='col-sm-auto p-1 ms-4'>\n" +
            "           <button id='" + groupid + "-app-csv' type='button' class='btn btn-info btn-sm'" +
            "               onclick='exhibitors.appDownload(\"csv\"); return false;'>Download CSV</button>\n" +
            "           <button id='" + groupid + "-app-xlsx' type='button' class='btn btn-info btn-sm'" +
            "               onclick='exhibitors.appDownload(\"xlsx\"); return false;'>Download Excel</button>\n" +
            "       </div>\n" +
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
        let html = "<div class='tab-content ms-2' id='" + groupid + "-exh-content' hidden>\n" +
            "<div class='container-fluid'>\n" +
            "    <div class='row'>\n" +
            "        <div class='col-sm-12' id='" + groupid + "-exh-table-div'></div>\n" +
            "    </div>\n" +
            "    <div class='row mt-2'>\n" +
            "       <div class='col-sm-auto p-1 ps-3 pe-3 tabulator-paginator' id='" + groupid + "-tabExhPaginationDiv'></div>\n" +
            "        <div class='col-sm-auto'>\n" +
            "            <button class='btn btn-sm btn-secondary ms-1 me-1' id='addExhibitorBtn' onClick=" + '"exhibitors.addNew();"' + ">Add New Exhibitor</button>\n" +
            "            <button class='btn btn-sm btn-secondary ms-1 me-1' id='importExhibitorBtn' onClick=" + '"exhibitors.importPast();"' + ">Import Past Exhibitors</button>" +
            "            <button id='" + groupid + "-exh-csv' type='button' class='btn btn-info btn-sm'" +
            "               onclick='exhibitors.exhDownload(\"csv\"); return false;'>Download CSV</button>\n" +
        "           <button id='" + groupid + "-exh-xlsx' type='button' class='btn btn-info btn-sm'" +
        "               onclick='exhibitors.exhDownload(\"xlsx\"); return false;'>Download Excel</button>\n" +
            "        </div>\n" +
            "    </div>\n" +
            "</div></div>\n"

        return html;
    }

    // build spaces Items
    #buildSpacesItems(data) {
        let regionsLocal = [];
        let region = null;
        //let currentRegion = -1;
        let currentExhibitor = -1;
        let spaces = data.detail;
        let spaceKeys = Object.keys(spaces);
        let spaceHTML = '';
        let spaceSUM = '';
        let spaceStage = '';
        let req = 0;
        let app = 0;
        let pur = 0;
        let inv = 0;
        let transid = 0;
        let spaceSUMPurchased = '';
        let spaceSUMApproved = '';
        let spaceSUMRequested = '';
        let newExhibitor = -1;
        let space = -1;
        let idS = ''
        for (idS in spaceKeys) {
            space = spaces[idS];
            //let newRegion = space.exhibitsRegionYearId;
            newExhibitor = space.exhibitorId
            if (newExhibitor != currentExhibitor) {
                // change in region
                if (currentExhibitor > 0) {
                    if (spaceSUMPurchased != '') {
                        spaceSUM = spaceSUMPurchased;
                    } else if (spaceSUMApproved == spaceSUMRequested) {
                        spaceSUM = '<div style="background-color: #e9ffe9;">' + spaceSUMApproved.substring(0, spaceSUMApproved.length - 5) +
                            "</div><br/>";
                    } else {
                        if (spaceSUMApproved != '') {
                            if (spaceSUMRequested != '')
                                spaceSUM = '<div style="background-color: #e9ffe9;">Approved:<br/>' +
                                    spaceSUMApproved.substring(0, spaceSUMApproved.length - 5) +
                                    '</div><div style="background-color: #e9e9ff;">Requested:<br/>' +
                                    spaceSUMRequested.substring(0, spaceSUMRequested.length - 5) +
                                    "</div><br/>";
                            else
                                spaceSUM += '<div style="background-color: #e9ffe9;">' +
                                    spaceSUMApproved.substring(0, spaceSUMApproved.length - 5) + "</div><br/>";
                        } else {
                            spaceSUM = '<div style="background-color: #e9e9ff;">' +
                                spaceSUMRequested.substring(0, spaceSUMRequested.length - 5) + "</div><br/>";
                        }
                    }
                    spaceSUM = spaceSUM.substring(0, spaceSUM.length - 5);
                    region.space = spaceHTML + "</div>";
                    region.summary = spaceSUM;
                    region.stage = spaceStage;
                    region.req = req;
                    region.app = app;
                    region.pur = pur;
                    region.inv = inv;
                    region.transid = transid;
                    region.requested = spaceSUMRequested;
                    region.approved = spaceSUMApproved;
                    region.purchased = spaceSUMPurchased;
                    regionsLocal.push(make_copy(region));
                    spaceHTML = '';
                    spaceStage = '';
                    spaceSUM = '';
                    req = 0;
                    app = 0;
                    pur = 0;
                    inv = 0;
                    transid = 0;
                    spaceSUMPurchased = '';
                    spaceSUMApproved = '';
                    spaceSUMRequested = '';
                }
                currentExhibitor = newExhibitor;
                spaceSUM = '';
                spaceHTML = '<div class="container-fluid" style="width: 80%;">';
                region = {
                    id: space.exhibitorId,
                    exhibitorNumber: space.exhibitorNumber,
                    eYRid: currentExhibitor,
                    exhibitorRegionYearId: space.exhibitorRegionYearId,
                    mailInAllowed: space.mailInAllowed,
                    regionId: space.regionId,
                    regionYearId: space.exhibitsRegionYearId,
                    exhibitorId: space.exhibitorId,
                    exhibitorName: space.exhibitorName,
                    fullExhName: space.fullExhName,
                    artistName: space.artistName,
                    website: space.website,
                    exhibitorEmail: space.exhibitorEmail,
                    agentRequest: space.agentRequest,
                    agentName: space.agentName,
                    transid: transid,
                    exhibitorYearId: space.exhibitorYearId,
                    locations: space.locations,
                    s1: space.b1,
                };
            }
            req += space.requested_units == null ? 0 : Number(space.requested_units);
            app += space.approved_units == null ? 0 : Number(space.approved_units);
            pur += space.purchased_units == null ? 0 : Number(space.purchased_units);
            inv += space.invCount == null ? 0 : Number(space.invCount);
            if (Number(space.transid) > 0 && transid == 0)
                transid = Number(space.transid);

            // add the space data as a formatted region
            if (Number(space.requested_units) > 0 || Number(space.approved_units) > 0 || Number(space.purchased_units) > 0) {
                // detail first
                spaceHTML += '<div class="row">' +
                    '<div class="col-sm-12"><STRONG>' + space.spaceName + '</STRONG></div></div>';

                if (blankIfNull(space.requested_units) != '') {
                    spaceHTML += '<div class="row">' +
                        '<div class="col-sm-2' + (blankIfNull(space.approved_units) == '' ? ' text-danger' : '') + '">Requested: </div>' +
                        '<div class="col-sm-1 text-end">' + blankIfNull(space.requested_units) + '</div>' +
                        '<div class="col-sm-5">' + blankIfNull(space.requested_description) + '</div>' +
                        '<div class="col-sm-4">' + blankIfNull(space.time_requested) + '</div>' +
                        '</div>';
                }

                if (blankIfNull(space.approved_units) != '') {
                    spaceHTML += '<div class="row">' +
                        '<div class="col-sm-2">Approved: </div>' +
                        '<div class="col-sm-1 text-end">' + blankIfNull(space.approved_units) + '</div>' +
                        '<div class="col-sm-5">' + blankIfNull(space.approved_description) + '</div>' +
                        '<div class="col-sm-4">' + blankIfNull(space.time_approved) + '</div>' +
                        '</div>';
                }

                if (blankIfNull(space.purchased_units) != '') {
                    spaceHTML += '<div class="row">' +
                        '<div class="col-sm-2">Purchased: </div>' +
                        '<div class="col-sm-1 text-end">' + blankIfNull(space.purchased_units) + '</div>' +
                        '<div class="col-sm-5">' + blankIfNull(space.purchased_description) + '</div>' +
                        '<div class="col-sm-4">' + blankIfNull(space.time_purchased) + '</div>' +
                        '</div>';
                }
                // space summary stuff
                if (Number(space.purchased_units) > 0) {
                    spaceStage = 'Purchased';
                    spaceSUMPurchased += space.purchased_description + ' of ' + space.spaceName + "<br/>";
                }
                if (Number(space.approved_units) > 0) {
                    spaceSUMApproved += space.approved_description + ' of ' + space.spaceName + "<br/>";
                    if (spaceStage == '' || spaceStage == 'Requested')
                        spaceStage = 'Approved';
                }
                if (Number(space.requested_units) > 0) {
                    spaceSUMRequested += space.requested_description + ' of ' + space.spaceName + "<br/>";
                    if (spaceStage == '')
                        spaceStage = 'Requested';
                }
            }
            // now do agent stuff
            if (blankIfNull(region.agentRequest) != '') {
                spaceHTML += '<div class="row"><div class="col-sm-2">Agent Request: </div>' +
                    '<div class="col-sm-10">' + blankIfNull(region.agentRequest) + '</div>' +
                    '</div>';
            }
            if (blankIfNull(region.agentName) != '') {
                spaceHTML += '<div class="row"><div class="col-sm-2">Agent Name: </div>' +
                    '<div class="col-sm-10">' + blankIfNull(region.agentName) + '</div>' +
                    '</div>';
            }
        }
        if (currentExhibitor > 0) {
            if (spaceSUMPurchased != '') {
                spaceSUM = spaceSUMPurchased;
            } else if (spaceSUMApproved == spaceSUMRequested) {
                spaceSUM = '<div style="background-color: #e9ffe9;">' + spaceSUMApproved.substring(0, spaceSUMApproved.length - 5) +
                    "</div><br/>";
            } else {
                if (spaceSUMApproved != '') {
                    if (spaceSUMRequested != '')
                        spaceSUM = '<div style="background-color: #e9ffe9;">Approved:<br/>' +
                            spaceSUMApproved.substring(0, spaceSUMApproved.length - 5) +
                            '</div><div style="background-color: #e9e9ff;">Requested:<br/>' +
                            spaceSUMRequested.substring(0, spaceSUMRequested.length - 5) +
                            "</div><br/>";
                    else
                        spaceSUM += '<div style="background-color: #e9ffe9;">' +
                            spaceSUMApproved.substring(0, spaceSUMApproved.length - 5) + "</div><br/>";
                } else {
                    spaceSUM = '<div style="background-color: #e9e9ff;">' +
                        spaceSUMRequested.substring(0, spaceSUMRequested.length - 5) + "</div><br/>";
                }
            }
            spaceSUM = spaceSUM.substring(0, spaceSUM.length - 5);
            region.space = spaceHTML + "</div>";
            region.summary = spaceSUM;
            region.stage = spaceStage;
            region.req = req;
            region.app = app;
            region.pur = pur;
            region.inv = inv;
            region.transid = transid;
            region.requested = spaceSUMRequested;
            region.approved = spaceSUMApproved;
            region.purchased = spaceSUMPurchased;
            spaceHTML = '';
            spaceStage = '';
            spaceSUM = '';
            req = 0;
            app = 0;
            pur = 0;
            inv = 0;
            transid = 0;
            spaceSUMPurchased = '';
            spaceSUMApproved = '';
            spaceSUMRequested = '';
            regionsLocal.push(make_copy(region));
        }
        return regionsLocal;
    }
    // drawSpacesTable - now that the DOM is created, draw the actual table
    drawSpacesTable(data, groupid, newTable) {
        // build new data array
        let regionsLocal = this.#buildSpacesItems(data);
        let usesInventory = data.usesInventory == 'Y';

        if (this.#debug & 8) {
            console.log("regions:");
            console.log(regionsLocal);
        }
        if (newTable) {
            var _this = this;
            document.getElementById(groupid + '-tabSpacesPaginationDiv').innerHTML = '';
            document.getElementById(groupid + '-tabSpacesPaginationDiv').hidden = regionsLocal.length <= 25;
            this.#spacesTable = new Tabulator('#' + groupid + '-spaces-table-div', {
                data: regionsLocal,
                layout: "fitDataTable",
                index: 'id',
                pagination: regionsLocal > 10,
                paginationSize: 25,
                paginationElement: document.getElementById(groupid + '-tabSpacesPaginationDiv'),
                paginationSizeSelector: [10, 25, 50, 100, 250, true], //enable page size select element with these options
                columns: [
                    {title: "Actions", field: "s1", formatter: this.spaceButtons, maxWidth: 900, headerSort: false, },
                    {title: "ID", field: "id", visible: true, width: 65, hozAlign:"right" },
                    {title: "RegionId", field: "regionId", visible: false},
                    {title: "Exh Num", field: "exhibitorNumber", headerWordWrap: true,  width: 75, hozAlign:"right" },
                    {title: "regionYearId", field: "regionYearId", visible: false},
                    {title: "ExhibitorYearId", field: "exhibitorYearId", visible: false},
                    {title: "ExhibitorRegionYearId", field: "exhibitorRegionYearId", visible: false},
                    {title: "Mail In Allowed", field: "mailInAllowed", width: 75, headerWordWrap: true, visible: false},
                    {field: "transid", visible: false},
                    {field: "app", visible: false},
                    {field: "req", visible: false},
                    {field: "pur", visible: false},
                    {title: "Inv Items", headerWordWrap: true, field: "inv", visible: usesInventory, width: 75, hozAlign:"right" },
                    {title: "locations", field: "locations", visible: false},
                    {title: "exhibitorId", field: "exhibitorId", visible: false},
                    {title: "artistName", field: "artistName", visible: false},
                    {title: "exhibitorName", field: "exhibitorName", visible: false},
                    {title: "Name", field: "fullExhName", width: 200, headerSort: true, headerFilter: true, formatter: "html"},
                    {title: "Website", field: "website", width: 200, headerSort: true, headerFilter: true,},
                    {title: "Email", field: "exhibitorEmail", width: 200, headerSort: true, headerFilter: true,},
                    {title: "Stage", field: "stage", headerSort: true, formatter: this.stageColor,
                        headerFilter: 'list', headerFilterParams: { values: ['Requested', 'Purchased', 'Approved'], },},
                    {title: "Summary", field: "summary", minWidth: 200, headerSort: false, headerFilter: true, formatter: "html", },
                    {field: "space", visible: false},
                ]});
        } else {
            this.#spacesTable.replaceData(regionsLocal);
        }
    }

    // formatter for this table
    stageColor(cell, formatterParams, onRendered) {
        let data = cell.getValue();
        switch (data) {
            case 'Requested':
                cell.getElement().style.backgroundColor = "#e9e9ff";
                break;
            case 'Approved':
                cell.getElement().style.backgroundColor = "#e9ffe9";
                break;
            default:
                cell.getElement().style.backgroundColor = '';
        }
        return data;
    }
    // download buttons for spaces table
    // save off the data file
    spacesDownload(format) {
        if (this.#spacesTable == null)
            return;

        let filename = this.#regionName.replace(' ', '-') + '-spaces';
        let tabledata = JSON.stringify(this.#spacesTable.getData("active"));
        let excludeList = ['s1','space'];
        downloadFilePost(format, filename, tabledata, excludeList);
    }

    // drawApprovalsTable - now that the DOM is created, draw the actual table
    drawApprovalsTable(data, groupid) {
        document.getElementById(groupid + '-tabAppPaginationDiv').innerHTML = '';
        document.getElementById(groupid + '-tabAppPaginationDiv').hidden = data.approvals.length <= 25;
        this.#approvalsTable = new Tabulator('#' + groupid + '-app-table-div', {
            data: data.approvals,
            layout: "fitDataTable",
            pagination: data.approvals.length > 10,
            paginationSize: 25,
            paginationElement: document.getElementById(groupid + '-tabAppPaginationDiv'),
            paginationSizeSelector: [10, 25, 50, 100, 250, true], //enable page size select element with these options
            columns: [
                {title: "Exhibitor Approval Requests Detail:", columns: [
                        {title: "", formatter: this.approvalButton, formatterParams: {name: 'Approve'}, width: 100, hozAlign: "center",
                            cellClick: this.exhApprove, headerSort: false, },
                        {title: "", formatter: this.approvalButton, formatterParams: {name: 'Reset'}, width: 80, hozAlign: "center",
                            cellClick: this.exhReset, headerSort: false, },
                        {title: "", formatter: this.approvalButton, formatterParams: {name: 'Deny'}, width: 80, hozAlign: "center",
                            cellClick: this.exhDeny, headerSort: false, },
                        {title: "", formatter: this.approvalButton, formatterParams: {name: 'Hide'}, width: 80, hozAlign: "center",
                            cellClick: this.exhHide, headerSort: false, },
                        {title: "Region", field: "name", headerSort: true, headerFilter: true },
                        {title: "id", field: "id", visible: false},
                        {title: "exhibitorId", field: "exhibitorId", visible: false},
                        {title: "exhibitorName", field: "exhibitorName", visible: false},
                        {title: "artistName", field: "artistName", visible: false},
                        {title: "Name", field: "fullExhName", width: 200, headerSort: true, headerFilter: true,formatter: "html"},
                        {title: "Website", field: "website", headerSort: true, headerFilter: true,},
                        {title: "Email", field: "exhibitorEmail", headerSort: true, headerFilter: true,},
                        {title: "Approval", field: "approval", headerSort: true, headerFilter: 'list', headerFilterParams: {values: this.#approvalValues},},
                        {title: "Timestamp", field: "updateDate", headerSort: true, },
                    ]
                }
            ]
        });
    }

    // download buttons for approvals table
    // save off the data file
    appDownload(format) {
        if (this.#approvalsTable == null)
            return;

        let filename = this.#regionName.replace(' ', '-') + '-apps';
        let tabledata = JSON.stringify(this.#approvalsTable.getData("active"));
        let excludeList = ['used','b1'];
        downloadFilePost(format, filename, tabledata, excludeList);
    }

    // drawExhibitorsTable
    // update the exhibitors div with the table of exhibitors
    drawExhibitorsTable(data, groupid) {
        exhibitorsData = data.exhibitors;
        document.getElementById(groupid + '-tabExhPaginationDiv').innerHTML = '';
        document.getElementById(groupid + '-tabExhPaginationDiv').hidden = data.exhibitors.length <= 25;
        this.#exhibitorsTable = new Tabulator('#' + groupid + '-exh-table-div', {
            data: data.exhibitors,
            index: "exhibitorId",
            layout: "fitDataTable",
            pagination: data.exhibitors > 10,
            paginationElement: document.getElementById(groupid + '-tabExhPaginationDiv'),
            paginationSize: 25,
            paginationSizeSelector: [10, 25, 50, 100, 250, true], //enable page size select element with these options
            columns: [
                {title: "Exhibitors:", columns: [
                        {title: "", formatter: this.exhButtons, hozAlign: "center", headerSort: false,},
                        {title: "Exh Id", field: "exhibitorId", visible: true, headerWordWrap: true, width: 75, },
                        {title: "Name", field: "fullExhName", width: 250, headerSort: true, headerFilter: true, tooltip: this.buildRecordHover,
                            formatter: "html", },
                        {title: "Regions", field: "regions", width: 200, headerSort: true, headerFilter: true, formatter: "html", },
                        {title: "Email", field: "exhibitorEmail", headerSort: true, headerFilter: true, width: 250, },
                        {title: "Phone", field: "exhibitorPhone", width: 140, headerSort: true, headerFilter: true,},
                        {title: "Website", field: "website", headerSort: true, headerFilter: true, width: 250, },
                        {title: "Exhibitor Notes", field: "exhNotes", headerFilter: true, width: 250, formatter: "textarea" },
                        {title: "Contact Id", field: "exhibitorYearId", visible: false, },
                        {title: "Contact", field: "contact", headerSort: true, headerFilter: true,
                            width: 250, formatter: this.toHTML, },
                        {title: "Contact Name", field: "contactName", headerSort: true, headerFilter: true, formatter: "textarea", visible: false, },
                        {title: "Contact Email", field: "contactEmail", headerSort: true, headerFilter: true, width: 250, visible: false, },
                        {title: "Con Phone", field: "contactPhone", width: 140, headerSort: true, headerFilter: true, visible: false, },
                        {title: "Full Address", field: "fullAddress", width: 200, headerWordWrap: true, headerSort: true,
                            headerFilter: true, formatter: this.toHTML, },
                        {title: "Mail In", field: "mailin", visible: true, headerWordWrap: true, width: 75, },
                        {title: "Salex Tax ID", field: "salesTaxId", visible: true, headerWordWrap: true, width: 150, },
                        {title: "City", field: "city", width: 140, headerSort: true, headerFilter: true, visible: false, },
                        {title: "State/Prov", field: "state", headerSort: true, headerFilter: true, visible: false,},
                        {title: "Contact Notes", field: "state",  headerFilter: true, formatter: "textarea", visible: false,},
                        {title: "Exhibitor Name", field: "exhibitorName",  headerFilter: true, formatter: "textarea", visible: false,},
                        {title: "Artist Name", field: "artistName",  headerFilter: true, formatter: "textarea", visible: false,},
                    ]
                }
            ]
        });
    }

    // download buttons for exhibitors table
    // save off the data file
    exhDownload(format) {
        if (this.#exhibitorsTable == null)
            return;

        let filename = this.#regionName.replace(' ', '-') + '-exh';
        let tabledata = JSON.stringify(this.#exhibitorsTable.getData("active"));
        let excludeList = ['password','contactPassword','fullAddress','contact'];
        downloadFilePost(format, filename, tabledata, excludeList);
    }

    // show the full vendor record as a hover in the table
    buildRecordHover(e, cell, onRendered) {
        let data = cell.getData();
        //console.log(data);
        let hover_text = 'Exhibitor id: ' + data.exhibitorId + '<br/>' +
            data.exhibitorName + '<br/>' +
            'Artist Name: ' + data.artistName + '<br/>' +
            'Artist Payee: ' + (data.artistPayee == '' ? '<i>(None Entered)</i>' : data.artistPayee) + '<br/>' +
            'Website: ' + data.website + '<br/>' +
            data.fullAddress + '<br/>' +
            'Needs New Password: ' + (data.needs_new ? 'Yes' : 'No') +  '<br/>' +
            'Publicize: ' + (data.publicity ? 'Yes' : 'No') +  '<br/>' +
            'Mail In: ' + data.mailin + '<br/>' +
            'Exhibitor Notes: ' + data.exhNotes.replaceAll('\n', '<br/>&nbsp;&nbsp;&nbsp;&nbsp;') + '<br/>' +
            'Sales Tax ID: ' + data.salesTaxId + '<br/>' +
            'Contact Name: ' + data.contactName + '<br/>' +
            'Contact Email: ' + data.contactEmail + '<br/>' +
            'Contact Phone: ' + data.contactPhone + '<br/>' +
            'Contact Notes: ' + data.contactNotes.replaceAll('\n', '<br/>&nbsp;&nbsp;&nbsp;&nbsp;') + '<br/>';

        hover_text += 'Description:<br/>&nbsp;&nbsp;&nbsp;&nbsp;' + data.description.replaceAll('\n', '<br/>&nbsp;&nbsp;&nbsp;&nbsp;');
        return hover_text;
    }

    // importPastModalOpen
    // get the available past vendors for the import and show the modal
    importPastModalOpen() {
        this.#importHTML.innerHTML = "<div class='row'><div class='col-sm-12' id='Importtable'></div></div>";
        if (this.#importTable) {
            this.#importTable.off("rowClick");
            this.#importTable.destroy();
            this.#importTable = null;
        }
        clear_message();
        clearError();
        $.ajax({
            url: 'scripts/exhibitorsGetPastForImport.php',
            method: "POST",
            data: { portalType: 'admin', portalName: 'Admin' },
            success: function (data, textstatus, jqXHR) {
                checkRefresh(data);
                exhibitors.importDataSuccess(data);
            },
            error: function (jqXHR, textStatus, errorThrown) {
                showError("ERROR in exhibitorsGetPastForImport: " + textStatus, jqXHR);
            }
        });
    }

    // process the data and draw the table
    importDataSuccess(data) {
        if (data.status == 'warn') {
            show_message($data.message, 'warn');
            return;
        }
        if (data.status == 'error') {
            show_message($data.message, 'error');
            return;
        }
        let _this = this;
        this.#importTable = new Tabulator('#Importtable', {
            data: data.past,
            layout: "fitDataTable",
            index: 'id',
            pagination: data.past > 10,
            paginationSize: 25,
            paginationSizeSelector: [10, 25, 50, 100, 250, true], //enable page size select element with these options
            columns: [
                { title: "Import", field: "import", headerSort: true, hozAlign: "center",
                    formatter:"tickCross", editorParams: { tristate: false, }, },
                { title: "ID", field: 'id', headerSort: true, },
                { title: "Exh Nbr", field: "exhibitorNumber", headerSort: true, headerWordWrap: true, width: 80, },
                { title: "Name", field: "fullExhName", headerSort: true, headerFilter: true, width: 300, formatter: 'html' },
                { title: "Exhibitor Website", field: "website", headerSort: true, headerFilter: true, width: 200, },
                { title: "Exhibitor Email", field: "exhibitorEmail", headerSort: true, headerFilter: true, width: 300, },
                { title: "Contact Name", field: "contactName", headerSort: true, headerFilter: true, width: 300, },
                { title: "Contact Email", field: "contactEmail", headerSort: true, headerFilter: true, width: 300, },
                { title: "City", field: "city", headerSort: true, headerFilter: true, width: 150 },
                { title: "St", field: "state", headerSort: true, headerFilter: true, width: 65 },
                { title: "Zip/PC", field: "zip", headerSort: true, headerFilter: true, width: 120 },
        ]});
        this.#importTable.on("rowClick", function(e, row){
            let cell = row.getCell('import');
            let contents = cell.getValue();
            contents = !contents;
            cell.setValue(contents);
        });

        this.#importModal.show();
    }

    importPastExhibitors() {
        let data = this.#importTable.getData();
        this.#importModal.hide();
        clear_message();
        clearError();
        $.ajax({
            url: 'scripts/exhibitorsImportPast.php',
            method: "POST",
            data: { past: JSON.stringify(data) },
            success: function (data, textstatus, jqXHR) {
                checkRefresh(data);
                exhibitors.importSuccess(data);
            },
            error: function (jqXHR, textStatus, errorThrown) {
                showError("ERROR in exhibitorsImportPast: " + textStatus, jqXHR);
            }
        });
    }

    importSuccess(data) {
        this.#exhibitorsTable.replaceData(data.exhibitors);
    }

// add new functions
    addNew() {
        exhibitorProfile.setPortalType(this.#portalType);
        exhibitorProfile.profileModalOpen('add');
    }

    importPast() {
        exhibitors.importPastModalOpen();
    }

// button callout functions
    edit(exhId) {
        let exhibitorRow = this.#exhibitorsTable.getRow(exhId)
        let exhibitorData = exhibitorRow.getData();
        exhibitorProfile.setPortalType(this.#portalType);
        exhibitors.editExhibitor(exhibitorData, exhibitorRow);
    }

    // approve an approval request
    exhApprove(e, cell) {
        let exhibitorRow = cell.getRow()
        let exhibitorData = exhibitorRow.getData();
        if (exhibitorData.b1 > 0)
            exhibitors.processApprovalChange('approved', exhibitorData, exhibitorRow);
    }

    // reset an approval back to request
    exhReset(e, cell) {
        let exhibitorRow = cell.getRow()
        let exhibitorData = exhibitorRow.getData();
        if (exhibitorData.b1 > 0)
            exhibitors.processApprovalChange('requested', exhibitorData, exhibitorRow);
    }

    // deny an approval request
    exhDeny(e, cell) {
        let exhibitorRow = cell.getRow()
        let exhibitorData = exhibitorRow.getData();
        if (exhibitorData.b1 > 0)
            exhibitors.processApprovalChange('denied', exhibitorData, exhibitorRow);
    }

    // hid a region (hide status)
    exhHide(e, cell) {
        let exhibitorRow = cell.getRow()
        let exhibitorData = exhibitorRow.getData();
        if (exhibitorData.b1 > 0)
            exhibitors.processApprovalChange('hide', exhibitorData, exhibitorRow);
    }

    // space detail button click
    showDetail(id) {
        let row = this.#spacesTable.getRow(id).getData();
        let details = row.space;
        let exhibitorId = row.exhibitorId;
        let exhibitorRow =  this.#exhibitorsTable.getRow(exhibitorId);
        let exhibitorData = exhibitorRow.getData();
        exhibitorData.exhibitorNumber = row.exhibitorNumber;
        exhibitorData.exhibitorRegionYearId = row.exhibitorRegionYearId;
        let exhibitor = row.exhibitorName;
        let mailInAllowed = row.mailInAllowed;

        // build exhibitor info block
        let exhibitorInfo = this.buildExhibitorInfoBlock(exhibitorData, mailInAllowed);

        document.getElementById('space-detail-title').innerHTML = "<strong>Space Detail for " + exhibitor + "(" + exhibitorId + ":" + exhibitorData.exhibitorYearId +
            "," +  exhibitorData.exhibitorRegionYearId  + ")</strong>";
        document.getElementById("spaceDetailHTML").innerHTML = details;
        document.getElementById("exhibitorInfoHTML").innerHTML = exhibitorInfo;
        this.#spaceDetailModal.show();
    }

    buildExhibitorInfoBlock(exhibitorData, mailInAllowed) {
        let none = '<i>(None Entered)</i>';
        let weburl = blankIfNull(exhibitorData.website).trim();
        let website = weburl;
        if (weburl != "") {
            if (weburl.substring(0, 8) != 'https://')
                weburl = 'https://' + weburl;
        } else {
            website = none;
        }
        let exhibitorName = blankIfNull(exhibitorData.exhibitorName).trim() == '' ? none :  exhibitorData.exhibitorName.trim();
        let artistName = blankIfNull(exhibitorData.artistName).trim() == '' ? none :  exhibitorData.artistName.trim();
        let artistPayee = blankIfNull(exhibitorData.artistPayee).trim() == '' ? none :  exhibitorData.artistPayee.trim();
        let exhibitorPhone = blankIfNull(exhibitorData.exhibitorPhone).trim() == '' ? none :  exhibitorData.exhibitorPhone.trim();
        let exhibitorInfo = `
            <div class="row">
                <div class="col-sm-4">Exhibitor Id/Number:</div>
                <div class="col-sm-8 p-0 ms-0 me-0">` + exhibitorData.exhibitorId + '/' + exhibitorData.exhibitorNumber + `</div>
            </div>
            <div class="row">
                <div class="col-sm-4">Business Name:</div>
                <div class="col-sm-8 p-0 ms-0 me-0">` + exhibitorName + `</div>
            </div>
            <div class="row">
                <div class="col-sm-4">Artist Name:</div>
                <div class="col-sm-8 p-0 ms-0 me-0">` + artistName + `</div>
            </div>
            <div class="row">
                <div class="col-sm-4">Artist Payee:</div>
                <div class="col-sm-8 p-0 ms-0 me-0">` + artistPayee + `</div>
            </div>
            <div class='row'>
                <div class='col-sm-4'>Business Email:</div>
                <div class='col-sm-8 p-0 ms-0 me-0'>` + exhibitorData.exhibitorEmail + `</div>   
            </div>
            <div class='row'>
                <div class='col-sm-4'>Business Phone:</div>
                <div class='col-sm-8 p-0 ms-0 me-0'>` + exhibitorPhone + `</div>   
            </div>
                <div class='row'>
                <div class='col-sm-4'>Sales Tax ID:</div>
                <div class='col-sm-8 p-0 ms-0 me-0'>` + exhibitorData.salesTaxId + `</div>   
            </div>
            <div class='row'>
                <div class='col-sm-4'>Website:</div>
                <div class='col-sm-8 p-0 ms-0 me-0'>` +
                    (weburl != '' ? '<a href="' + weburl + '" target="_blank">' : '') + website +
                    (weburl != '' ? '</a>' : '') + `</div>   
            </div>
            <div class='row'>
                <div class='col-sm-4'>Desc.:</div>
                <div class='col-sm-8 p-0 ms-0 me-0'>` + blankIfNull(exhibitorData.description).trim() + `</div>   
            </div>
`;

        if (blankIfNull(exhibitorData.exhNotes).trim() != '')
            exhibitorInfo += `<div class='row'>
                <div class='col-sm-4'>Exhibitor Notes:</div>
                <div class='col-sm-8 p-0 ms-0 me-0'>` + exhibitorData.exhNotes.replace(/\n/g, '<br/>').trim() + `</div>
            </div>
`;

        if (blankIfNull(exhibitorData.contactNotes).trim() != '')
            exhibitorInfo += `<div class='row'>
                <div class='col-sm-4'>Contact Notes.:</div>
                <div class='col-sm-8 p-0 ms-0 me-0'>` + exhibitorData.contactNotes.replace(/\n/g, '<br/>').trim() + `</div>
            </div>
`;

        if (mailInAllowed == 'Y') {
            exhibitorInfo += `<div class="row">
                <div class='col-sm-4'>Mail-In:</div>
                <div class='col-sm-8 p-0 ms-0 me-0'>` + exhibitorData.mailin + `</div>   
            </div>
`;
        }
        exhibitorInfo += `<div class='row'>
                <div class='col-sm-4'>Address:</div>
                <div class='col-sm-8 p-0 ms-0 me-0'>` + exhibitorData.addr.trim() + `</div>        
            </div>
`;
        if (blankIfNull(exhibitorData.addr2).trim() != '') {
            exhibitorInfo += `<div class='row'>
                <div class='col-sm-4'>&nbsp;</div>
                <div class='col-sm-8 p-0 ms-0 me-0'>` + exhibitorData.addr2.trim() + `</div>   
            </div>
`;
        }

        exhibitorInfo += `<div class='row'>
                <div class='col-sm-4'>&nbsp;</div>
                <div class='col-sm-8 p-0 ms-0 me-0'>` + exhibitorData.city + ', ' + exhibitorData.state + ' ' + exhibitorData.zip + `</div>   
            </div>
             <div class='row'>
                <div class='col-sm-4'>&nbsp;</div>
                <div class='col-sm-8 p-0 ms-0 me-0'>` + exhibitorData.country + `</div>   
            </div>
`;
        return exhibitorInfo;
    }
    // locations button click
    showLocations(id) {
        let row = this.#spacesTable.getRow(id).getData();
        let summary = row.summary;
        let exhibitorId = row.exhibitorId;
        let locations = row.locations;
        let exhibitorRow = this.#exhibitorsTable.getRow(exhibitorId);
        let exhibitorData = exhibitorRow.getData();
        exhibitorData.exhibitorNumber = row.exhibitorNumber;
        exhibitorData.exhibitorRegionYearId = row.exhibitorRegionYearId;
        let exhibitor = row.exhibitorName;
        let mailInAllowed = row.mailInAllowed;

        // build exhibitor info block
        let exhibitorInfo = this.buildExhibitorInfoBlock(exhibitorData, mailInAllowed);

        // build locations used block
        let locationsusedHTML = "<div class='row p-0 m-0'>"
        let colno = 0;

        for (let location in this.#locationsUsed) {
            colno++;
            if (colno > 12) {
                locationsusedHTML += "</div>\n<div class='row p-0 m-0'>";
                colno = 0;
            }
            locationsusedHTML += '<div class="col-sm-1 p-0 m-0">' + this.#locationsUsed[location] + '</div>';
        }
        locationsusedHTML += "</div>\n";


        exhibitorData = this.#spacesTable.getRow(id).getData();
        document.getElementById('locations-edit-title').innerHTML = "<strong>Locations for " + exhibitor +
            " (" + exhibitorId + ":" + exhibitorData.exhibitorYearId + "," + exhibitorData.exhibitorRegionYearId +  ")</strong>";
        document.getElementById("spaceHTML").innerHTML = summary.replace("\n", "<br/>");
        document.getElementById("locationsVal").value = locations;
        document.getElementById("spaceRowId").value = id;
        document.getElementById("locationsExhibitorInfoHTML").innerHTML = exhibitorInfo;
        document.getElementById("locationsUsedHTML").innerHTML = locationsusedHTML;
        this.#locationsModal.show();
    }

    // button formatters

    // exhButtons - three buttons for the exhibitor Record
    exhButtons(cell, formatterParams, onRendered) {
        let row = cell.getData();
        let id = row.exhibitorId;
        // edit button
        let buttons = '<button class="btn btn-secondary" style="--bs-btn-padding-y: .0rem; --bs-btn-padding-x: .3rem; ' +
            '--bs-btn-font-size: .75rem;" onclick="exhibitors.edit(' + id + ');">Edit</button>';

        buttons += '<button class="btn btn-secondary m-1 ms-2" style="--bs-btn-padding-y: .0rem; --bs-btn-padding-x: .3rem; ' +
            '--bs-btn-font-size: .75rem;" onclick="exhibitors.history(' + id + ');">Hist</button>';

        buttons += '<br/>' + '<button class="btn btn-secondary m-1" style="--bs-btn-padding-y: .0rem; --bs-btn-padding-x: .3rem; ' +
            '--bs-btn-font-size: .75rem;" onclick="exhibitors.resetpw(' + id + ');">Reset Exh PW</button>';

        buttons += '<br/>' + '<button class="btn btn-secondary" style="--bs-btn-padding-y: .0rem; --bs-btn-padding-x: .3rem; ' +
            '--bs-btn-font-size: .75rem;" onclick="exhibitors.resetCpw(' + id + ');">Reset Con PW</button>';

        return buttons;
    }

    toHTML(cell,  formatterParams, onRendered) {
        let item = cell.getValue();
        return item;
    }

    submitLocations() {
        let locations = document.getElementById('locationsVal').value.trim();
        let rowId = document.getElementById('spaceRowId').value;
        let row = this.#spacesTable.getRow(rowId);
        row.getCell("locations").setValue(locations);
        this.#locationsModal.hide();

        let exhibitorRegionYearId = row.getCell("exhibitorRegionYearId").getValue();
        let regionYearId = row.getCell("regionYearId").getValue();

        clear_message();
        clearError();
        $.ajax({
            url: 'scripts/exhibitorsUpdateLocations.php',
            method: "POST",
            data: { 'exhibitorRegionYearId': exhibitorRegionYearId, exhibitsRegionYearId: regionYearId, locations: locations },
            success: function (data, textStatus, jqXhr) {
                checkRefresh(data);
                exhibitors.locationsUpdateSuccess(data);
            },
            error: showAjaxError
        });
    }

    locationsUpdateSuccess(data) {
        if (data.error) {
            show_message(data.error, 'error');
            return;
        }
        if (data.warning) {
            show_message(data.warning, 'warn');
            return;
        }
        if (data.success) {
            show_message(data.success, 'success');
        } else {
            clear_message();
        }

        this.#locationsUsed = data.locationsUsed;
    }

    // tabulator button formatters

    spaceButtons(cell, formatterParams, onRendered) {
        let data = cell.getData();
        let req = data.req || 0;
        let app = data.app || 0;
        let pur = data.pur || 0;
        let transid = data.transid || 0;
        let agentRequest = data.agentRequest || '';
        let id = data.id;
        let exhibitorRegionYearId = data.exhibitorRegionYearId;
        let buttons = '';
        let approvalBtns = req > 0 && pur == 0;
        let paidBtns = transid > 0;
        let invBtns = data.inv > 0;
        let agentBtns = agentRequest != '' && !agentRequest.startsWith('Processed: ');

        // determine if we need the margin for the first section of buttons
        let margin = (invBtns || agentBtns) ? ' mb-2' : '';

        // details button
        buttons += '<button class="btn btn-sm btn-info' + margin + '" style = "--bs-btn-padding-y: .0rem; --bs-btn-padding-x: .3rem; --bs-btn-font-size: .75rem;" ' +
            'onclick="exhibitors.showDetail(' + id + ', true)" >Details</button>&nbsp;';

        // approval buttons
        if (config.exhibitorConid == config.conid) {
            if (approvalBtns) {
                if (data.approved != data.requested) {
                    if (app == 0) {
                        buttons += '<button class="btn btn-sm btn-primary' + margin + '" style = "--bs-btn-padding-y: .0rem; --bs-btn-padding-x: .3rem; --bs-btn-font-size: .75rem;" ' +
                            'onclick="exhibitors.spaceApprovalReq(' + id + ')" >Approve Req</button>&nbsp;';
                    } else {
                        buttons += '<button class="btn btn-sm btn-warning' + margin + '" style = "--bs-btn-padding-y: .0rem; --bs-btn-padding-x: .3rem;' +
                            ' --bs-btn-font-size: .75rem;" ' +
                            'onclick="exhibitors.spaceApprovalReq(' + id + ')" >Revert to Orig Req</button>&nbsp;';
                    }
                }
                if (app > 0) {
                    buttons += '<button class="btn btn-sm btn-warning' + margin + '" style = "--bs-btn-padding-y: .0rem; --bs-btn-padding-x: .3rem; --bs-btn-font-size: .75rem;" ' +
                        'onclick="exhibitors.spaceApprovalOther(' + id + ')" >Change</button>&nbsp;' +
                        '<button class="btn btn-sm btn-warning' + margin + '" style = "--bs-btn-padding-y: .0rem; --bs-btn-padding-x: .3rem; --bs-btn-font-size: .75rem;" ' +
                        'onclick="exhibitors.spaceApprovalOther(' + id + ', 1)" >Change&Pay</button>&nbsp;';
                }
                if (app == 0)
                    buttons += '<button class="btn btn-sm btn-primary' + margin + '" style = "--bs-btn-padding-y: .0rem; --bs-btn-padding-x: .3rem; --bs-btn-font-size: .75rem;" ' +
                        'onclick="exhibitors.spaceApprovalOther(' + id + ')" >Approve Other</button>&nbsp;';
                // force a break after the approval buttons
                buttons += "<br/>";
            }
        }

        margin = (invBtns || agentBtns) ? 'mb-2' : '';
        // receipt button and locations
        if (paidBtns) {
            buttons += '<button class="btn btn-sm btn-secondary ' + margin + '" style = "--bs-btn-padding-y: .0rem; --bs-btn-padding-x: .3rem;' +
                ' --bs-btn-font-size: .75rem;" ' +
                'onclick="exhibitors.spaceReceipt(' + id + ')" >Receipt</button>&nbsp;';
            if (config.exhibitorConid == config.conid) {
                buttons += '<button class="btn btn-sm btn-primary ' + margin + '" style = "--bs-btn-padding-y: .0rem; --bs-btn-padding-x: .3rem;' +
                    ' --bs-btn-font-size: .75rem;" ' +
                    'onclick="exhibitors.showLocations(' + id + ', true)" >Locations</button>&nbsp;';
            }

            buttons += "<br/>";
        }

        margin = agentBtns ? 'mb-2' : '';
        // inventory button
        if (invBtns) {
            if (config.exhibitorConid == config.conid) {
                buttons += '<button class="btn btn-sm btn-secondary' + margin + '" style = "--bs-btn-padding-y: .0rem; --bs-btn-padding-x: .3rem; --bs-btn-font-size: .75rem;" ' +
                    'onclick="exhibitors.printBidSheets(' + id + ')" >Bid Sheets</button>&nbsp;';
                buttons += '<button class="btn btn-sm btn-secondary' + margin + '" style = "--bs-btn-padding-y: .0rem; --bs-btn-padding-x: .3rem; --bs-btn-font-size: .75rem;" ' +
                    'onclick="exhibitors.printPriceTags(' + id + ')" >Price Tags</button>&nbsp;';
            }
            buttons += '<button class="btn btn-sm btn-secondary' + margin + '" style = "--bs-btn-padding-y: .0rem; --bs-btn-padding-x: .3rem; --bs-btn-font-size: .75rem;" ' +
                'onclick="exhibitors.printControlSheet(' + id + ', false)" >Control Sheet</button>&nbsp;';
            buttons += '<button class="btn btn-sm btn-warning' + margin + '" style = "--bs-btn-padding-y: .0rem; --bs-btn-padding-x: .3rem; --bs-btn-font-size: .75rem;" ' +
                'onclick="exhibitors.printControlSheet(' + id + ', true)" >Control Sheet w/Emails</button>&nbsp;';

            buttons += "<br/>";
        }

        // agent
        if (agentBtns && config.exhibitorConid == config.conid) {
            buttons += '<button class="btn btn-sm btn-secondary" style = "--bs-btn-padding-y: .0rem; --bs-btn-padding-x: .3rem; --bs-btn-font-size: .75rem;" ' +
                'onclick="exhibitors.spaceAgent(' + id + ');" >Agent</button>&nbsp;';
        }

        return buttons;
    }

    // request approval buttons
    approvalButton(cell, formatterParams, onRendered) {
        let data = cell.getData();
        let id = data.id;
        let b1 = data.b1;
        let approval = data.approval || 'none';
        let name = formatterParams.name;
        let color = 'secondary';

        switch (approval) {
            case 'none':
            case 'requested':
                if (name == 'Reset' && b1 > 0)
                    return '';
                break;
            case 'approved':
                if (name == 'Approve' && b1 > 0)
                    return '';
                break;
            case 'denied':
                if (name == 'Deny' && b1 > 0)
                    return '';
                break;
            case 'hide':
                if (name == 'Hide' && b1 > 0)
                    return '';
                break;
        }

        if (b1 < 0) {
            if (name == 'Approve' && b1 == -1) {
                return "Allocated";
            }
            return '';
        }

        if (config.exhibitorConid != config.conid)
            return '';

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
        return '<button class="btn btn-sm btn-' + color + '" style = "--bs-btn-padding-y: .0rem; --bs-btn-padding-x: .3rem; --bs-btn-font-size: .75rem;">' + name + '</button>';
    }

    // editExhibitor - Populate edit vendor modal with current data
    editExhibitor(exhibitor, exhibitorRow = null) {
        if (this.#debug & 4)
            console.log(exhibitor);
    exhibitor_info = exhibitor;
    exhibitorProfile.profileModalOpen('update', exhibitor.exhibitorId, exhibitor.exhibitorYearId, exhibitorRow);
    }

    // history - call up and display the history for an exhibitor
    history(exhibitorId) {
        clear_message();
        clearError();

        this.#historyRow = this.#exhibitorsTable.getRow(exhibitorId).getData();
        $.ajax({
            url: 'scripts/exhibitorsGetHistory.php',
            method: "POST",
            data: { 'exhibitorId': exhibitorId, type: 'exhibitor' },
            success: function (data, textStatus, jqXhr) {
                checkRefresh(data);
                exhibitors.displayHistory(data);
            },
            error: function (jqXHR, textStatus, errorThrown) {
                showError("ERROR in emailReceipt: " + textStatus, jqXHR);
            }
        });
    }

    // display the history modal
    displayHistory(data) {
        if (data.error) {
            show_message(data.error, 'error');
            return;
        }
        // now build the modal data and call it up
        let title = "Exhibitor Change History for " + this.#historyRow.exhibitorId + ' (' + this.#historyRow.exhibitorName + ')';
        this.#historyTitle.innerHTML = title
        title += "<br/>Artist Name:  " + this.#historyRow.artistName + ", Email: " + this.#historyRow.exhibitorEmail;
        // build the history display
        let html = '<div class="row"><div class="col-sm-12"><h1 class="h3">' + title + '</h1></div></div>';
        // format the heading lines-line 1
        html += "<div class='row'>\n" +
            "<div class='col-sm-2'>History Date</div>\n" +
            "<div class='col-sm-3'>Artist Name</div>\n" +
            "<div class='col-sm-3'>Artist Payee</div>\n" +
            "<div class='col-sm-3'>Business Name</div>\n" +
            "</div>\n";
        // format the heading lines-line 2
        html += "<div class='row'>\n" +
            "<div class='col-sm-1'></div>\n" +
            "<div class='col-sm-3'>Business Email</div>\n" +
            "<div class='col-sm-2'>Business Phone</div>\n" +
            "<div class='col-sm-2'>Tax ID</div>\n" +
            "<div class='col-sm-4'>Website</div>\n" +
            "</div>\n";
        // format the heading lines-line 3
        html += "<div class='row'>\n" +
            "<div class='col-sm-1'></div>\n" +
            "<div class='col-sm-1'>Publicity</div>\n" +
            "<div class='col-sm-3'>Addr Line 1</div>\n" +
            "<div class='col-sm-2'>Addr Line 2</div>\n" +
            "<div class='col-sm-2'>City</div>\n" +
            "<div class='col-sm-1'>State</div>\n" +
            "<div class='col-sm-1'>Zip</div>\n" +
            "<div class='col-sm-1'>Country</div>\n" +
            "</div>\n";
        // format the heading lines-line 4
        html += "<div class='row'>\n" +
            "<div class='col-sm-1'></div>\n" +
            "<div class='col-sm-1'>Archive</div>\n" +
            "<div class='col-sm-3'>Ship Addr Line 1</div>\n" +
            "<div class='col-sm-2'>Ship Addr Line 2</div>\n" +
            "<div class='col-sm-2'>Ship City</div>\n" +
            "<div class='col-sm-1'>Ship State</div>\n" +
            "<div class='col-sm-1'>Ship Zip</div>\n" +
            "<div class='col-sm-1'>Ship Ctry</div>\n" +
            "</div>\n";

        // format the current line
        let current = data.history[0];
        let color = '';
        let prior = data.history[0];
        let rowColor = false;
        for (let i = 0; i < data.history.length; i++) {
            current = data.history[i];
            let curColor = rowColor ? "#FFFFFF" : "#F0F0F0 ";
            rowColor = !rowColor;
            // line 1
            html += "<div class='row pt-1 pb-1' style='background-color: " + curColor + ";'>\n";

            // history date
            html += "<div class='col-sm-2'>" + current.historyDate + "</div>\n";
            // artist name
            color = prior.artistName != current.artistName ? ' historyChangedBGColor' : '';
            html += "<div class='col-sm-3" + color + "'>" + current.artistName + "</div>\n";
            // artist payee
            color = prior.artistPayee != current.artistPayee ? ' historyChangedBGColor' : '';
            html += "<div class='col-sm-3" + color + "'>" + current.artistPayee + "</div>\n";
            // exhibitor name
            color = prior.exhibitorName != current.exhibitorName ? ' historyChangedBGColor' : '';
            html += "<div class='col-sm-3" + color + "'>" + current.artistName + "</div>\n";
            html += "</div>\n";

            // line 2
            html += "<div class='row' style='background-color: " + curColor + ";'><div class='col-sm-1'></div>\n";
            // Business Email
            color = prior.exhibitorEmail != current.exhibitorEmail ? ' historyChangedBGColor' : '';
            html += "<div class='col-sm-3" + color + "'>" + current.exhibitorEmail + "</div>\n";
            // Business Email
            color = prior.exhibitorPhone != current.exhibitorPhone ? ' historyChangedBGColor' : '';
            html += "<div class='col-sm-2" + color + "'>" + current.exhibitorPhone + "</div>\n";
            // Sales Tax ID
            color = prior.salesTaxId != current.salesTaxId ? ' historyChangedBGColor' : '';
            html += "<div class='col-sm-2" + color + "'>" + current.salesTaxId + "</div>\n";
            // Website
            color = prior.website != current.website ? ' historyChangedBGColor' : '';
            html += "<div class='col-sm-2" + color + "'>" + current.website + "</div>\n";
            html += "</div>\n";

            // line 3
            html += "<div class='row' style='background-color: " + curColor + ";'><div class='col-sm-1'></div>\n";
            // Publicity
            color = prior.publicity != current.publicity ? ' historyChangedBGColor' : '';
            html += "<div class='col-sm-1" + color + "'>" + current.publicity + "</div>\n";
            // street addr
            color = prior.addr != current.addr ? ' historyChangedBGColor' : '';
            html += "<div class='col-sm-3" + color + "'>" + current.addr + "</div>\n";
            // addr2
            color = prior.addr2 != current.addr2 ? ' historyChangedBGColor' : '';
            html += "<div class='col-sm-2" + color + "'>" + current.addr2 + "</div>\n";
            // city
            color = prior.city != current.city ? ' historyChangedBGColor' : '';
            html += "<div class='col-sm-2" + color + "'>" + current.city + "</div>\n";
            // state
            color = prior.state != current.state ? ' historyChangedBGColor' : '';
            html += "<div class='col-sm-1" + color + "'>" + current.state + "</div>\n";
            // zip
            color = prior.zip != current.zip ? ' historyChangedBGColor' : '';
            html += "<div class='col-sm-1" + color + "'>" + current.zip + "</div>\n";
            // country
            color = prior.country != current.country ? ' historyChangedBGColor' : '';
            html += "<div class='col-sm-1" + color + "'>" + current.country + "</div>\n";
            html += "</div>\n";

            // line 4
            html += "<div class='row' style='background-color: " + curColor + ";'><div class='col-sm-1'></div>\n";
            // Archived
            color = prior.archived != current.archived ? ' historyChangedBGColor' : '';
            html += "<div class='col-sm-1" + color + "'>" + current.archived + "</div>\n";
            // ship treet addr
            color = prior.shipAddr != current.shipAddr ? ' historyChangedBGColor' : '';
            html += "<div class='col-sm-3" + color + "'>" + current.shipAddr + "</div>\n";
            // ship addr2
            color = prior.shipAddr2 != current.shipAddr2 ? ' historyChangedBGColor' : '';
            html += "<div class='col-sm-2" + color + "'>" + current.shipAddr2 + "</div>\n";
            // ship city
            color = prior.shipCity != current.shipCity ? ' historyChangedBGColor' : '';
            html += "<div class='col-sm-2" + color + "'>" + current.shipCity + "</div>\n";
            // ship state
            color = prior.shipState != current.shipState ? ' historyChangedBGColor' : '';
            html += "<div class='col-sm-1" + color + "'>" + current.shipState + "</div>\n";
            // ship zip
            color = prior.shipZip != current.shipZip ? ' historyChangedBGColor' : '';
            html += "<div class='col-sm-1" + color + "'>" + current.shipZip + "</div>\n";
            // ship country
            color = prior.shipCountry != current.shipCountry ? ' historyChangedBGColor' : '';
            html += "<div class='col-sm-1" + color + "'>" + current.shipCountry + "</div>\n";
            html += "</div>\n";

            // if current, or a difference, show description or notes
            if (current.historyDate == 'current' && current.description != null && current.description != "") {
                html += "<div class='row' style='background-color: " + curColor + ";'><div class='col-sm-1 text-end'>Desc:</div>\n" +
                    "<div class='col-sm-11'>" + current.description.trim() + "</div>\n</div>\n";
            } else if (current.description != prior.description) {
                color = ' historyChangedBGColor';
                html += "<div class='row' style='background-color: " + curColor + ";'><div class='col-sm-1 text-end'>Desc:</div>\n" +
                    "<div class='col-sm-11" + color + "'>" + current.description.trim() + "</div>\n</div>\n";
            }

            if (current.historyDate == 'current' && current.notes != null && current.notes != "") {
                html += "<div class='row' style='background-color: " + curColor + ";'><div class='col-sm-1 text-end'>Notes:</div>\n" +
                    "<div class='col-sm-11'>" + current.notes.trim() + "</div>\n</div>\n";
            } else if (current.notes != prior.notes) {
                color = ' historyChangedBGColor';
                html += "<div class='row' style='background-color: " + curColor + ";'><div class='col-sm-1 text-end'>Notes:</div>\n" +
                    "<div class='col-sm-11" + color + "'>" + current.notes.trim() + "</div>\n</div>\n";
            }

            prior = current;
        }
        this.#historyDiv.innerHTML = html;
        this.#historyModal.show();

        if (data.warn)
            show_message(data.warn, 'warn', 'history_message_div');

        if (data.message)
            show_message(data.message, 'success', 'history_message_div');
    }

    // reset an exhibitor's password
    resetpw(exhibitorId) {
        clear_message();
        clearError();
        $.ajax({
            url: 'scripts/exhibitorsSetPassword.php',
            method: "POST",
            data: { 'exhibitorId': exhibitorId, type: 'exhibitor' },
            success: function (data, textStatus, jqXhr) {
                if(data.error != undefined) { console.log(data.error); }
                alert(data.password);
                checkRefresh(data);
            },
            error: function (jqXHR, textStatus, errorThrown) {
                showError("ERROR in emailReceipt: " + textStatus, jqXHR);
            }
        });
    }

    // reset a contact's password
    resetCpw(exhibitorId) {
        let exhibitorYearId = this.#exhibitorsTable.getRow(exhibitorId).getCell("exhibitorYearId").getValue();
        clear_message();
        clearError();
        $.ajax({
            url: 'scripts/exhibitorsSetPassword.php',
            method: "POST",
            data: { 'exhibitorYearId': exhibitorYearId, type: 'contact' },
            success: function (data, textStatus, jqXhr) {
                if(data.error != undefined) { console.log(data.error); }
                checkRefresh(data);
                alert(data.password);
            },
            error: function (jqXHR, textStatus, errorThrown) {
                showError("ERROR in emailReceipt: " + textStatus, jqXHR);
            }
        });
    }

    // processApprovalChange - change the value of the approval record for this exhibitor
    processApprovalChange(value, approvalData, approvalRow) {
        this.#approvalRow = approvalRow
        clear_message();
        clearError();
        $.ajax({
            url: 'scripts/exhibitorsSetApproval.php',
            method: "POST",
            data: { approvalData: approvalData, approvalValue: value },
            success: function (data, textstatus, jqXHR) {
                checkRefresh(data);
                exhibitors.approvalChangeSuccess(data);
                },
            error: showAjaxError
        });
    }

    // approvalChangeSuccess - successful return from setting the record
    approvalChangeSuccess(data) {
        if (data.status == 'error') {
            show_message(data.message, 'error');
        } else {
            if (data.message)
                show_message(data.message, 'success')
            if (this.#approvalRow) {
                this.#approvalRow.update(data.info);
            }
        }
    }
        // show the receipt
    spaceReceipt(id) {
        this.#spaceRow = this.#spacesTable.getRow(id);
        let exhibitorData = this.#spaceRow.getData();
        this.#regionYearId = exhibitorData.regionYearId;
        this.#exhibitorId = exhibitorData.exhibitorId;
        exhibitorReceipt.showReceipt(this.#regionYearId, this.#exhibitorId);
    }

    printBidSheets(id) {
        this.#spaceRow = this.#spacesTable.getRow(id);
        let exhibitorData = this.#spaceRow.getData();
        let script = "scripts/exhibitorsBidSheets.php?type=bidsheets&region=" + exhibitorData.regionYearId + "&eyid=" + exhibitorData.exhibitorYearId;
        window.open(script, "_blank")
    }

    printPriceTags(id) {
        this.#spaceRow = this.#spacesTable.getRow(id);
        let exhibitorData = this.#spaceRow.getData();
        let script = "scripts/exhibitorsBidSheets.php?type=printshop&region=" + exhibitorData.regionYearId + "&eyid=" + exhibitorData.exhibitorYearId;
        window.open(script, "_blank")
    }

    printControlSheet(id, email) {
        this.#spaceRow = this.#spacesTable.getRow(id);
        let exhibitorData = this.#spaceRow.getData();
        let script = "scripts/exhibitorsBidSheets.php?type=control&region=" + exhibitorData.regionYearId + "&eyid=" + exhibitorData.exhibitorYearId + '&email=' + email;
        window.open(script, "_blank")
    }


    // process appove requested
    spaceApprovalReq(id) {
        this.#spaceRow = this.#spacesTable.getRow(id);
        let exhibitorData = this.#spaceRow.getData();
        let req = exhibitorData.req || 0;
        let app = exhibitorData.app || 0;
        let pur = exhibitorData.pur || 0;
        if (req == 0 || pur > 0)
            return; // suppress click if there is nothing to approve

        clear_message();
        clearError();
        this.#regionYearId = exhibitorData.regionYearId;
        $.ajax({
            url: 'scripts/exhibitorsSpaceApproval.php',
            method: "POST",
            data: { exhibitorData: exhibitorData, approvalType: 'req' },
            success: function (data, textstatus, jqXHR) {
                checkRefresh(data);
                exhibitors.spaceApprovalSuccess(data);
            },
            error: showAjaxError
        });
    }

    // process approve other than requested
    spaceApprovalOther(id, pay = 0) {
        this.#spaceRow = this.#spacesTable.getRow(id);
        this.#approvalPay = pay;
        let exhibitorData = this.#spaceRow.getData();
        let req = exhibitorData.req || 0;
        let app = exhibitorData.app || 0;
        let pur = exhibitorData.pur || 0;
        if (req == 0 || pur > 0)
            return; // suppress click if there is nothing to approve

        this.#exhibitorId = exhibitorData.exhibitorId;
        this.#regionId = exhibitorData.regionId;
        this.#regionYearId = exhibitorData.regionYearId;

        if (this.#debug & 1)
            console.log("Space Approval for " + exhibitorData.exhibitorName + " of type other");

        clear_message();
        clearError();
        $.ajax({
            url: 'scripts/exhibitorGetSingleData.php',
            method: "POST",
            data: { regionId: exhibitorData.regionId, exhibitorId: exhibitorData.exhibitorId },
            success: function (data, textstatus, jqXHR) {
                checkRefresh(data);
                exhibitors.spaceAppDataSuccess(data);
            },
            error: showAjaxError
        });

    }

    // spaceApprovalSuccess - successful return from marking the space approval
    spaceApprovalSuccess(data) {
        if (data.status == 'error') {
            show_message(data.message, 'error');
        } else {
            if (data.message)
                show_message(data.message, 'success');
            if (this.#debug & 8)
                console.log(data.detail);
            let exhRow = this.#buildSpacesItems(data);
            if (this.#debug & 8)
                console.log(exhRow);
            this.#spacesTable.updateData(exhRow);
            if (data.hasOwnProperty('summary'))
               document.getElementById('summary_div').innerHTML = this.drawSummary(data, false);
        }
    }

    // spaceAppDataSuccess - set Javascript globals and open the request up
    spaceAppDataSuccess(data) {
        region_list = data.region_list;
        exhibits_spaces = data.exhibits_spaces;
        exhibitor_info = data.exhibitor_info;
        exhibitor_spacelist = data.exhibitor_spacelist;
        exhibitor_perm = data.exhibitor_perm;
        // don't overwrite regions, it's already loaded and its correct for all uses in vendor, exhibitorRequest doesn't use it.
        spaces = data.spaces;
        country_options = data.country_options;
        exhibitorRequest.openReq(this.#regionYearId, 2);
    }

    // process the agent request
    spaceAgentRequest(id) {
        this.#spaceRow = this.#spacesTable.getRow(id);

        show_message("Not Yet", 'warn');
    }

    // add exhibitor space - selecting the exhibitor and the space
    addNewSpace() {
        clear_message();
        this.#exhibitorChooseTitle.innerHTML = 'Add Space to Which Exhibitor? (only exhibitors with no paid spaces in this region are shown)';
        let script = 'scripts/exhibitorsGetList.php';
        let data = {
            regionId: this.#regionId,
            action: 'list',
            exhibitorConid: config.exhibitorConid,
        };
        clear_message();
        clearError();
        $.ajax({
            url: script,
            data: data,
            method: "POST",
            success: function (data, textstatus, jqXHR) {
                checkRefresh(data);
                exhibitors.getListSuccess(data);
            },
            error: showAjaxError
        });
    }

    // getListSuccess - process the return of the list data
    getListSuccess(data) {
        if (data.error) {
            console.log(data);
            show_message(data.error, 'error');
            return;
        }
        if (data.exhibitors.length == 0) {
            show_message('All exhibitors have already paid for their space.  Use Add New Exhibitor if necessary to add this mail order exhibitor.', 'warn');
            return;
        }
        if (data.message) {
            show_message(data.message, 'success', 'ce_message_div');
        }
        this.#exhibitorChooseModal.show();
        this.#exhibitorListTable = new Tabulator('#exhibitorHtml', {
            data: data.exhibitors,
            layout: "fitDataTable",
            index: 'id',
            pagination: data.exhibitors > 0,
            paginationSize: 25,
            paginationSizeSelector: [10, 25, 50, 100, 250, true], //enable page size select element with these options
            columns: [
                {title: "Actions", field: "s1", formatter: this.exhibitorListButtons, maxWidth: 300, headerSort: false, },
                {title: "ID", field: "exhibitorId", visible: true, width: 65, },
                {title: "Artist Name", field: "artistName", headerFilter: true, visible: true, width: 200, },
                {title: "Name", field: "exhibitorName", headerFilter: true, visible: true, width: 200, },
                {title: "Email", field: "exhibitorEmail", headerFilter: true, visible: true, width: 200, },
                {title: "Website", field: "website", headerFilter: true, visible: true, width: 200, },
                {title: "City", field: "city", visible: true, headerFilter: true, width: 200, },
                {title: "St/Prov", field: "state", visible: true, headerFilter: true, width: 100, },
                {title: "Zip/PC", field: "zip", visible: true, headerFilter: true, width: 100, },
        ]});
    }

    // buttons for the exhibitorListTable
    exhibitorListButtons(cell, formatterParams, onRendered) {
        let data = cell.getData();
        let id = data.exhibitorId;
        let buttons = '';

        // add space button button
        buttons += '<button class="btn btn-sm btn-primary" style = "--bs-btn-padding-y: .0rem; --bs-btn-padding-x: .3rem; --bs-btn-font-size: .75rem;" ' +
            'onclick="exhibitors.addPaySpace(' + id + ')" >Add/Pay Space</button>&nbsp;';

        return buttons;
    }

    // add/pay for space for an existing vendor
    addPaySpace(id) {
        clear_message();
        this.#exhibitorId = id; // which exhibitor are we using

        this.#exhibitorChooseModal.hide();
        this.#exhibitorListTable.destroy();
        this.#exhibitorListTable = null;
        console.log("add/pay for space for exhibitor " + this.#exhibitorId + ', in exhibitsRegion ' + this.#regionId);

        let script = 'scripts/exhibitorGetSingleData.php';
        let data = {
            exhibitorId: this.#exhibitorId,
            regionId: this.#regionId,
            action: 'get',
        };
        clear_message();
        clearError();
        $.ajax({
            url: script,
            data: data,
            method: "POST",
            success: function (data, textstatus, jqXHR) {
                checkRefresh(data);
                exhibitors.getAddPaySpaceSuccess(data);
            },
            error: showAjaxError
        });
    }

    // now we have the data draw the scrren
    getAddPaySpaceSuccess(data) {
        if (data.error) {
            console.log(data);
            show_message(data.error, 'error');
            return;
        }

        if (data.message) {
            show_message(data.message, 'success');
        }
        console.log('getAddPaySpaceSuccess');
        console.log(data);

        region_list = data.region_list;
        exhibits_spaces = data.exhibits_spaces;
        exhibitor_info = data.exhibitor_info;
        exhibitor_spacelist = data.exhibitor_spacelist;
        this.#regionYearId = data.exhibitor_perm.exhibitsRegionYearId;
        // don't overwrite regions, it's already loaded and its correct for all uses in vendor, exhibitorRequest doesn't use it.
        spaces = data.spaces;
        country_options = data.country_options;
        exhibitorRequest.openReq(this.#regionYearId, 3);
    }

    // email related functions
    sendInvReminder() {
        emailBulkSend = new EmailBulkSend('result_message', 'scripts/sendBatch.php');

        let email = prompt("Would you like to send a test invitation reminder email?\n" +
            "If so please enter the address to send the test to in the box below and click ok.\n" +
            "If you don't provide a test address, you will be sending emails to a lot of people.\n" +
            "You will be give a chance to review the number of emails to be sent before they are sent out.\n" +
            "Clicking cancel will cancel the sending of these emails.\n");
        let action = "none";

        if (email == null)
            return false;

        if (email == '') {
            action = 'full';
        } else {
            action = 'test';
        }

        let data = { action: action, email: email, type: 'invReminder', regionName: this.#regionName, exhibitsRegionYearId: this.#exhibitsRegionYearId };
        emailBulkSend.getEmailAndList('scripts/sendEmail.php', data);
    }
};

exhibitors = null;

// hook to public class function for exhibitor draw
function updateExhibitorDataDraw(data, textStatus, jqXHR) {
    checkRefresh(data);
    exhibitors.redraw(data);
}

// create class on page render
window.onload = function initpage() {
    exhibitors = new exhibitorsAdm(config.conid, config.debug);
    fileManager = new FileManager();
    exhibitorRequestOnLoad();
    exhibitorReceiptOnLoad();
    exhibitorInvoiceOnLoad();

    pwEyeToggle('pw1');
    pwEyeToggle('pw2');
    pwEyeToggle('cpw1');
    pwEyeToggle('cpw2');
}
