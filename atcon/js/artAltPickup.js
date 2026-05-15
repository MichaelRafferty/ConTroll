// alternate pickup auth class
var altPickupAuth
var locale = 'en-us';
var currencyFmt = null;

// initialization
// lookup all DOM elements
// load mapping tables
window.onload = function initpage() {
    // current items
    locale = config.locale;
    currencyFmt = new Intl.NumberFormat(locale, {
        style: 'currency',
        currency: config.currency,
    });

    // class
    altPickupAuth = new AltPickupAuth();
};

class AltPickupAuth {
    // local variables
    #authList = null;
    #authListTable = null;
    #debug = 0;
    #dirty = false;

    // dom elements
    #savebtn = null;
    #undobtn = null;
    #redobtn = null;
    #addbtn = null;

    // addnew items
    #addNewModal = null
    #addNewTitle = null;
    #addNewBidder = null;
    #addNewPickup = null;
    #lastBidder = null;
    #bidderName = null;
    #bidderValid = false;
    #lastPickup = null;
    #pickupName = null;
    #pickupValid = false;
    #addNewBtn = null;
    #authPagination = false;

    constructor(debug = 0) {
        this.#debug = debug;
        this.#savebtn = document.getElementById('artalt_save_btn');
        this.#undobtn = document.getElementById('artalt_undo_btn');
        this.#redobtn = document.getElementById('artalt_redo_btn');
        this.#addbtn = document.getElementById('artalt_add_pickup_btn');
        let id = document.getElementById('AddNewPickup');
        if (id) {
            this.#addNewModal = new bootstrap.Modal(id, { focus: true, backdrop: 'static' });
            this.#addNewTitle = document.getElementById('AddNewPickupTitle');
            this.#addNewBidder = document.getElementById('bidderPerid');
            this.#bidderName = document.getElementById('addNewBidderName');
            this.#pickupName = document.getElementById('addNewPickupName');
            this.#addNewBtn = document.getElementById('addNewBtn');
            this.#addNewPickup = document.getElementById('pickupPerid');
            this.#addNewBidder.addEventListener('keyup', (e)=> { if (e.code === 'Enter') altPickupAuth.addNewBidderCheck(); });
            this.#addNewPickup.addEventListener('keyup', (e)=> { if (e.code === 'Enter') altPickupAuth.addNewPickup(); });
        }
        this.loadInitialData();
    }

    // load the initial data for the startup display
    loadInitialData() {
        // load the initial data and the proceed to set up the rest of the system
        var postData = {
            ajax_request_action: 'loadInitialData',
        };
        $.ajax({
            method: "POST",
            url: "scripts/artAltPickup_updateGetData.php",
            data: postData,
            success: function (data, textstatus, jqxhr) {
                if (data.error !== undefined) {
                    show_message(data.error, 'error');
                    return;
                }
                if (data.message !== undefined) {
                    show_message(data.message, 'success');
                }
                altPickupAuth.draw(data);
            },
            error: showAjaxError,
        });
    }

    // draw or replace the auth list table
    draw(data) {
        if (this.#authListTable) {
            this.#authListTable.replaceData(data.authList);
        } else {
            this.#authPagination = data.authList.length > 25;
            this.#authListTable = new Tabulator('#pickupAuthTable', {
                data: data.authList,
                layout: "fitData",
                maxHeight: "800px",
                movableRows: false,
                history: true,
                index: 'ordinal',
                pagination: this.#authPagination,
                paginationSize: 10,
                paginationSizeSelector: [10, 25, 50, true], //enable page size select element with these options
                paginationElement: document.getElementById('tabPaginationDiv'),
                columns: [
                    {title: "Actions", field: 'ordinal', headerWordWrap: true, width: 100, formatter: altPickupAuth.addActions, responsive:0 },
                    {title: "Conid", field: "conid", headerWordWrap: true, width: 70, headerSort: false, visible: this.#debug > 0, },
                    {title: "Bidder Perid ", field: "bidderPerid", headerWordWrap: true, hozAlign: 'right', headerHozAlign: 'right',
                        headerSort: true, headerFilter: true, headerFilterFunc:numberHeaderFilter, width: 100, },
                    {title: "Bidder Full Name", field: "bidderFullName", headerSort: true, headerWordWrap: true, width: 300,
                        headerFilter: true, headerFilterFunc: fullNameHeaderFilter, },
                    {title: "Pickup Perid ", field: "pickupPerid", headerWordWrap: true, hozAlign: 'right', headerHozAlign: 'right',
                        headerSort: true, headerFilter: true, headerFilterFunc:numberHeaderFilter, width: 100, },
                    {title: "Pickup Full Name", field: "pickupFullName", headerSort: true, headerWordWrap: true,  width: 300, headerFilter: true, },
                    {title: "Date Created", field: "createDate", headerSort: true,  headerWordWrap: true, width: 190,
                        headerFilter: true, headerFilterFunc:dateStringHeaderFilter, },
                    {title: "Created By", field: "createdBy", headerWordWrap: true, hozAlign: 'right', headerHozAlign: 'right',
                        headerSort: true, headerFilter: true, headerFilterFunc:numberHeaderFilter, width: 100, },
                    {title: "Active", field: "active", headerSort: false, headerFilter: true, width: 80, hozAlign: 'center',},
                    {title: "Cancel Date", field: "deactivateDate", headerSort: true,  headerWordWrap: true, width: 190,
                        headerFilter: true, headerFilterFunc:dateStringHeaderFilter, },
                    {title: "Cancelled By", field: "deactivatedBy", headerWordWrap: true, hozAlign: 'right', headerHozAlign: 'right',
                        headerSort: true, headerFilter: true, headerFilterFunc:numberHeaderFilter, width: 100, },
                    {field: 'first_name', visible: false,},
                    {field: 'middle_name', visible: false,},
                    {field: 'last_name', visible: false,},
                ],
            });
            this.#authListTable.on("dataChanged", changed);
        }
        this.#savebtn.disabled = true;
        this.#savebtn.innerHTML = "Save";
    }

    // process on.changed for tabulator table to mark dirty and enable/relabel save button
    changed() {
        'use strict'
        //data - the updated table data changed
        if (!this.#dirty) {
            this.#dirty = true;
            this.#savebtn.innerHTML = "Save*";
            this.#savebtn.disabled = false;
        }
        this.checkUndoRedo();
    }

    // formatters
    addActions(cell, formatterParams, onRendered) {
        "use strict";

        let row = cell.getRow();
        let rowData = row.getData();
        if (rowData.active == 'Y') {
            return '<button type="button" class="btn btn-sm btn-primary p-0" ' +
                'onclick="altPickupAuth.toggleActive(' + rowData.ordinal + ", 'N')" + '">Deactivate</button >';
        }
        return '<button type="button" class="btn btn-sm btn-primary p-0"' +
            'onclick="altPickupAuth.toggleActive(' + rowData.ordinal + ", 'Y')" + '">Activate</button >';
    }

    // process action
    toggleActive(position, value) {
        let row = this.#authListTable.getRow(position);
        if (!row) {
            show_message("Row not found at position:" + position, 'error');
            return;
        }

        row.update({active: value});
        if (value == 'Y') {
            row.getCell('deactivateDate').setValue('');
            row.getCell('deactivatedBy').setValue('');
        }
        row.reformat();
        setCellChanged(row.getCell('active'));
        if (value == 'Y') {
            setCellChanged(row.getCell('deactivateDate'));
            setCellChanged(row.getCell('deactivatedBy'));
        }
    }

    // bottom of page buttons
    addnew() {
        this.#addNewBidder.value = '';
        this.#addNewPickup.value = '';
        this.#bidderName.innerHTML = '';
        this.#bidderValid = false;
        this.#pickupName.innerHTML = '';
        this.#pickupValid = false;
        clear_message();
        clear_message('addNewMessage');
        this.#addNewModal.show();
        this.#addNewBidder.focus();
    }

    addNewClose() {
        this.#addNewBidder.value = '';
        this.#addNewPickup.value = '';
        this.#addNewModal.hide();
    }

    addNewBidderCheck() {
        let perid = this.#addNewBidder.value;
        if (perid == this.#lastBidder)
            return; // no change (enter + onchange call)
        clear_message('addNewMessage');
        this.#bidderValid = false;
        this.#lastBidder = perid;
        if (perid.trim() === '' || perid <= 0) {
            show_message("Bidder Badge ID cannot be empty, zero or negative.", 'error', 'addNewMessage');
            this.#addNewBidder.focus();
            return;
        }
        let script = 'scripts/artAltPickup_getBidderName.php';
        let postData = {
            ajax_request_action: 'newBidderCheck',
            perid: perid,
        }
        $.ajax({
            method: "POST",
            url: script,
            data: postData,
            success: function (data, textstatus, jqxhr) {
                altPickupAuth.addNewBidderSuccess(data);
            },
            error: showAjaxError,
        });
    }

    addNewBidderSuccess(data) {
        this.#lastBidder = null;
        if (data.error) {
            this.#bidderName.innerHTML = '';
            show_message(data.error, 'error', 'addNewMessage');
            this.#addNewBidder.focus();
            return;
        }
        if (data.message) {
            show_message(data.message, 'success', 'addNewMessage');
        }
        this.#bidderName.innerHTML = "Bidder: " + data.fullName;
        this.#bidderValid = true;
        this.#addNewPickup.focus();
    }

    addNewPickupCheck() {
        let perid = this.#addNewPickup.value;
        if (perid == this.#lastPickup)
            return; // no change (enter + onchange call)
        clear_message('addNewMessage');
        this.#pickupValid = false;
        this.#lastPickup = perid;
        if (perid.trim() === '' || perid <= 0) {
            show_message("Pickup Badge ID cannot be empty, zero or negative.", 'error', 'addNewMessage');
            this.#addNewPickup.focus();
            return;
        }
        if (this.#addNewBidder.value == this.#addNewPickup.value) {
            show_message("Bidder and Pickup cannot be the same.", 'error', 'addNewMessage');
            this.#addNewPickup.focus();
            return;
        }
        let script = 'scripts/artAltPickup_getBidderName.php';
        let postData = {
            ajax_request_action: 'newPickupCheck',
            perid: perid,
        }
        $.ajax({
            method: "POST",
            url: script,
            data: postData,
            success: function (data, textstatus, jqxhr) {
                altPickupAuth.addNewPickupSuccess(data);
            },
            error: showAjaxError,
        });

    }

    addNewPickupSuccess(data) {
        this.#lastPickup = null;
        if (data.error) {
            this.#bidderName.innerHTML = '';
            show_message(data.error, 'error', 'addNewMessage');
            this.#addNewPickup.focus();
            return;
        }
        if (data.message) {
            show_message(data.message, 'success', 'addNewMessage');
        }
        this.#pickupName.innerHTML = "Pickup: " + data.fullName;
        this.#pickupValid = true;
        this.#addNewBtn.focus();
    }

    addNewPickup() {
        // validate that we have two valid perids and they don't match.
        if (!this.#bidderValid) {
            show_message("Please enter a valid bidder badge ID.", 'error', 'addNewMessage');
            this.#addNewBidder.focus();
            return;
        }
        if (!this.#pickupValid) {
            show_message("Please enter a valid pickup person badge ID.", 'error', 'addNewMessage');
            this.#addNewPickup.focus();
            return;
        }

        clear_message('addNewMessage');
        let bidder = this.#addNewBidder.value;
        let pickup = this.#addNewPickup.value;
        if (bidder == pickup) {
            show_message("Bidder and Pickup cannot be the same.", 'error', 'addNewMessage');
            this.#addNewPickup.focus();
            return;
        }

        // ok, we have both, now add them to the table
        let data = this.#authListTable.getData();
        this.#authListTable.addRow({ ordinal: data.length, conid: config.conid, bidderPerid: bidder,
            bidderFullName: this.#bidderName.innerHTML.replace("Bidder: ", ""),
            pickupPerid: pickup, pickupFullName: this.#pickupName.innerHTML.replace("Pickup: ", ""),
            active: 'Y', first_name: '', middle_name: '', last_name: ''}).then(function (row) {
            if (_this.#authPagination) {
                row.getTable().setPageToRow(row).then(function () {
                    setCellChanged(row.getCell('bidderPerid'));
                    setCellChanged(row.getCell('bidderFullName'));
                    setCellChanged(row.getCell('pickupPerid'));
                    setCellChanged(row.getCell('pickupFullName'));
                    setCellChanged(row.getCell('active'));
                    _this.checkUndoRedo();
                });
            } else {
                setCellChanged(row.getCell('bidderPerid'));
                setCellChanged(row.getCell('bidderFullName'));
                setCellChanged(row.getCell('pickupPerid'));
                setCellChanged(row.getCell('pickupFullName'));
                setCellChanged(row.getCell('active'));
                _this.checkUndoRedo();
            }
        });

        this.addNewClose();
    }

    // process press of undo button
    undo() {
        'use strict';
        this.#authListTable.undo();

        if (this.checkUndoRedo() <= 0) {
            this.#dirty = false;
            this.#dirty = false;
            this.#savebtn.innerHTML = "Save";
            this.#savebtn.disabled = true;
        }
    }

    // process press of redo button
    redo() {
        'use strict';
        this.#authListTable.redo();

        if (this.checkUndoRedo() > 0) {
            if (this.#dirty === false) {
                this.#dirty = true;
                this.#savebtn.innerHTML = "Save*";
                this.#savebtn.disabled = false;
            }
        }
    }

    // set undo / redo status for buttons
    checkUndoRedo() {
        var undosize = this.#authList.getHistoryUndoSize();
        this.#undoBtn.disabled = undosize <= 0;
        this.#redoBtn.disabled = this.#authListTable.getHistoryRedoSize() <= 0;
        return undosize;
    }

    save() {
        let script = 'scripts/artAltPickup_updateGetData.php';
        let postData = {
            ajax_request_action: 'save',
            rows: JSON.stringify(this.#authListTable.getData()),
        };
        $.ajax({
            method: "POST",
            url: script,
            data: postData,
            success: function (data, textstatus, jqxhr) {
                if (data.error !== undefined) {
                    show_message(data.error, 'error');
                    return;
                }
                if (data.message !== undefined) {
                    show_message(data.message, 'success');
                }
                altPickupAuth.draw(data);
            },
            error: showAjaxError,
        });
    }

    download(format) {
        if (this.#authListTable == null)
            return;

        let filename = 'altPickupAuths';
        let tabledata = JSON.stringify(this.#authListTable.getData("active"));
        let excludeList = [];
        downloadFilePost(format, filename, tabledata, excludeList);
    }
};

function changed() {
    altPickupAuth.changed();
}
