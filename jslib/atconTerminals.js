// Class Atcon square Terminals
// all functions to configure the Square Terminals for atcon

statusDetailsModal = null;
addTerminalModal = null;

class Terminals {
    #addbtn = null;
    #terminalList = null;
    #validLocations = null;
    #statusDetailsTitle = null;
    #statusDetailsBody = null;
    #newTerminalName = null;
    #newTerminalLocation = null;
    #pairBlock = null;
    #addName = null;
    #addId = null;
    
    constructor(terminals, locations) {
        // Search tabulator elements
        this.#validLocations = locations;

        // Users HTML elements
        this.#addbtn = document.getElementById('terminals_add_btn');
        var id = document.getElementById('statusDetails');
        if (id) {
            statusDetailsModal = new bootstrap.Modal(id, {focus: true, backdrop: 'static'});
            this.#statusDetailsTitle = document.getElementById('statusDetailsTitle');
            this.#statusDetailsBody = document.getElementById('statusDetailsBody');
        }
        id = document.getElementById('addTerminal');
        if (id) {
            addTerminalModal = new bootstrap.Modal(id, {focus: true, backdrop: 'static'});
            this.#newTerminalName = document.getElementById('newTerminalName');
            this.#newTerminalLocation = document.getElementById('newTerminalLocation');
            this.#pairBlock = document.getElementById('pairBlock');
        }
        
        // load initial data
        this.loadTerminals(terminals);
        // refresh the table
        for (var i = 0; i < terminals.length; i++) {
            this.refreshStatus(terminals[i].name,true);
        }
        this.dirty = false;
    }

    loadTerminals(terminals) {
        'use strict';

        if (this.#terminalList !== null) {
            this.#terminalList.destroy();
            this.#terminalList = null;
        }

        this.#terminalList = new Tabulator ('#terminalsTable', {
            data: terminals,
            layout: "fitData",
            maxHeight: "300px",
            movableRows: false,
            history: true,
            index: 'name',
            columns: [
                { title: "Actions", minWidth: 230, formatter: this.termActions, },
                { title: "Name", field: "name", minWidth: 150, headerSort: true, headerFilter: true },
                { title: "Status", field: "status", minWidth: 100, headerSort: true, headerFilter:true, headerWordWrap: true, },
                { title: "Status Updated", field: "statusChanged", minWidth: 150, headerSort: true, headerFilter:true, headerWordWrap: true, },
                { title: "Square Code", field: "squareCode", width: 100, headerSort: true, headerFilter:true, headerWordWrap: true, },
                { title: "Battery Level", field: "batteryLevel", width: 100, headerSort: true, headerFilter:true, headerWordWrap: true,
                    formatter: "progress", formatterParams: { min: 0, max: 100, color: ["red", "yellow", "green"], legend: true, },
                },
                { title: "External Power", field: "externalPower", minWidth: 100, headerSort: true, headerFilter:true, headerWordWrap: true, },
                { title: "Current Order", field: "currentOrder", minWidth: 150, headerSort: true, headerFilter:true, headerWordWrap: true, },
                { title: "Current Payment", field: "currentPayment", minWidth: 150, headerSort: true, headerFilter:true, headerWordWrap: true, },
            ],
        });
    }

// tabulator formatter for the actions column, displays the update badge, remove, and edit person buttons
// filters for ones already in the cart, and statuses that should not be allowed to be added to the cart
    termActions(cell, formatterParams, onRendered) { //plain text value
        var data = cell.getData();
        var btns = "";
        btns += '<button class="btn btn-primary me-1" style = "--bs-btn-padding-y: .0rem; --bs-btn-padding-x: .3rem; --bs-btn-font-size: .75rem;" ' +
            ' onclick="terminals.details(\'' + data.name + '\')">Details</button>';
        btns += '<button class="btn btn-secondary me-1" style = "--bs-btn-padding-y: .0rem; --bs-btn-padding-x: .3rem; --bs-btn-font-size: .75rem;" ' +
            ' onclick="terminals.refreshStatus(\'' + data.name + '\')">Refresh</button>';

        if (!data.currentOrder) {
            btns += '<button class="btn btn-warning me-1" style = "--bs-btn-padding-y: .0rem; --bs-btn-padding-x: .3rem; --bs-btn-font-size: .75rem;" ' +
                ' onclick="terminals.deleteTerminal(\'' + data.name + '\')">Delete</button>';
        }

        return btns;
    }

    // add and create a new terminal using the Square Device API
    addTerminal() {
        // clear the fields
        this.#newTerminalName.value = '';
        this.#newTerminalLocation.value = '';
        this.#pairBlock.hidden = true;
        this.#terminalList.clearFilter(true);
        document.getElementById('AddTerminalCreate').disabled = false;
        document.getElementById('createName').innerHTML = '';
        document.getElementById('createSquareCode').innerHTML = '';
        document.getElementById('createProductType').innerHTML = '';
        document.getElementById('createSquareId').innerHTML = '';
        document.getElementById('createLocationId').innerHTML = '';
        document.getElementById('createPairBy').innerHTML = '';
        document.getElementById('createStatus').innerHTML = '';
        document.getElementById('createStatusChanged').innerHTML = '';
        addTerminalModal.show();
    }

    createTerminal() {
        clear_message('add_result_message');
        var name = document.getElementById('newTerminalName').value;
        var location = document.getElementById('newTerminalLocation').value;

        if (name == undefined || name == null || name == '') {
            show_message('New terminal name required', 'error', 'add_result_message');
            return;
        }

        if (this.#terminalList.getRow(name)) {
            show_message('The terminal "' + name + '" already exists', 'error', 'add_result_message');
            return;
        }

        if (location == undefined || location == null || location == '') {
            show_message('You must select a location', 'error', 'add_result_message');
            return;
        }
        show_message("Creating the new terminal: " + name + " at location " + location, 'add_result_message');
        var postData = {
            ajax_request_action: 'create',
            terminal: name,
            location: location,
        };
        $.ajax({
            method: "POST",
            url: "scripts/admin_createTerminal.php",
            data: postData,
            success: function (data, textstatus, jqxhr) {
                terminals.createTerminalSuccess(data);
            },
            error: showAjaxError,
        });
    }

    createTerminalSuccess(data) {
        if (data['error'] !== undefined) {
            show_message(data['error'], 'error', 'add_result_message');
            return;
        }
        if (data['message'] !== undefined) {
            show_message(data['message'], 'success', 'add_result_message');
        }
        var terminal = data.terminal;
        this.#addName = terminal.name;
        this.#addId = terminal.id;
        document.getElementById('createName').innerHTML = terminal.name;
        document.getElementById('createSquareCode').innerHTML = terminal.code;
        document.getElementById('createProductType').innerHTML = terminal.product_type;
        document.getElementById('createSquareId').innerHTML = terminal.id;
        document.getElementById('createLocationId').innerHTML = terminal.location_id;
        document.getElementById('createPairBy').innerHTML = terminal.pair_by;
        document.getElementById('createStatus').innerHTML = terminal.status;
        document.getElementById('createStatusChanged').innerHTML = terminal.status_changed_at;
        this.#pairBlock.hidden = false;
        document.getElementById('AddTerminalCreate').disabled = true;
    }

    // in theory it's now paired, get the device id by calling get device code
    createProceed() {
        show_message("Getting the new terminals details: " + this.#addName, 'add_result_message');
        var postData = {
            ajax_request_action: 'codeStatus',
            terminal: this.#addName,
            id: this.#addId,
        };
        $.ajax({
            method: "POST",
            url: "scripts/admin_getTerminalCodes.php",
            data: postData,
            success: function (data, textstatus, jqxhr) {
                terminals.createProceedSuccess(data);
            },
            error: showAjaxError,
        });
    }

    createProceedSuccess(data) {
        if (data['error'] !== undefined) {
            show_message(data['error'], 'error', 'add_result_message');
            return;
        }
        if (data['message'] !== undefined) {
            show_message(data['message'], 'success', 'add_result_message');
        }

        if (data['ok'] == 0)
            return;

        this.#terminalList.replaceData(data['terminals']);
        this.refreshStatus(this.#addName, true);
        this.#addName = null;
        this.#addId = null;
        addTerminalModal.hide();
    }

    deleteTerminal(name) {
        if (!confirm("Have you already deleted the device code at the Square dashboard in " +
            "'Settings' -> 'Device Mangement' -> 'Device codes' " +
            "using the three dots to the right and the 'delete' action?"))
            return;

        var postData = {
            ajax_request_action: 'delete',
            terminal: name,
        };
        $.ajax({
            method: "POST",
            url: "scripts/admin_deleteTerminal.php",
            data: postData,
            success: function (data, textstatus, jqxhr) {
                if (data['error'] !== undefined) {
                    show_message(data['error'], 'error');
                    return;
                }
                if (data['message'] !== undefined) {
                    show_message(data['message'], 'success');
                }
                terminals.loadTerminals(data['terminals']);
            },
            error: showAjaxError,
        });
    }

    // show details
    details(terminal) {
        // load fields from terminal row
        var data = this.#terminalList.getRow(terminal).getData();
        var keys = Object.keys(data);
        for (var i = 0; i < keys.length; i++) {
            var key = keys[i];
            var field = 'details' + String(key[0]).toUpperCase() + String(key).slice(1);
            document.getElementById(field).innerHTML = data[key];
        }
        statusDetailsModal.show();
    }

    // refresh the status, use the status call to fetch the non controll status, and then fetch the database item
    refreshStatus(terminal, silent = false) {
        var postData = {
            ajax_request_action: 'refreshStatus',
            terminal: terminal,
        };
        $.ajax({
            method: "POST",
            url: "scripts/admin_getTerminalStatus.php",
            data: postData,
            success: function (data, textstatus, jqxhr) {
                terminals.refreshStatusSuccess(terminal, silent, data)
            },
            error: showAjaxError,
        });
    }

    refreshStatusSuccess(terminal, silent, data) {
        if (data['error'] !== undefined) {
            show_message(data['error'], 'error');
            return;
        }
        if (data['message'] !== undefined && !silent) {
            show_message(data['message'], 'success');
        }

        this.#terminalList.updateRow(terminal,  data['updatedRow']);
    }

    close() {
        if (this.#terminalList !== null) {
            this.#terminalList.destroy();
            this.#terminalList = null;
        }
    }

}
