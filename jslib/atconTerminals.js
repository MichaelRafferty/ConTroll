// Class Atcon square Terminals
// all functions to configure the Square Terminals for atcon

statusDetailsModal = null;

class Terminals {
    #addbtn = null;
    #terminalList = null;
    #validLocations = null;
    #statusDetailsTitle = null;
    #statusDetailsBody = null;
    
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
        
        // load initial data
        this.loadTerminals(terminals);
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
                { title: "Actions",  formatter: this.termActions, },
                { title: "Name", field: "name", minWidth: 150, headerSort: true, headerFilter: true },
                { title: "Status", field: "status", minWidth: 100, headerSort: true, headerFilter:true, headerWordWrap: true, },
                { title: "Status Changed", field: "statusChanged", minWidth: 150, headerSort: true, headerFilter:true, headerWordWrap: true, },
                { title: "Square Code", field: "squareCode", width: 100, headerSort: true, headerFilter:true, headerWordWrap: true, },
                { title: "Battery Level", field: "batteryLevel", width: 100, headerSort: true, headerFilter:true, headerWordWrap: true,
                    formatter: "progress", formatterParams: { min: 0, max: 100, color: ["red", "yellow", "green"], legend: true, },
                },
                { title: "External Power", field: "externalPower", width: 100, headerSort: true, headerFilter:true, headerWordWrap: true, },
                { title: "Current Order", field: "currentOrder", width: 150, headerSort: true, headerFilter:true, headerWordWrap: true, },
                { title: "Current Payment", field: "currentPayment", width: 150, headerSort: true, headerFilter:true, headerWordWrap: true, },
            ],
        });
    }

// tabulator formatter for the actions column, displays the update badge, remove, and edit person buttons
// filters for ones already in the cart, and statuses that should not be allowed to be added to the cart
    termActions(cell, formatterParams, onRendered) { //plain text value
        var html = '';
        var data = cell.getData();
        var btns = "";
        btns += '<button class="btn btn-primary me-1" style = "--bs-btn-padding-y: .0rem; --bs-btn-padding-x: .3rem; --bs-btn-font-size: .75rem;",' +
            ' onclick="terminals.details(\'' + data.name + '\')">Details</button>';
        btns += '<button class="btn btn-secondary me-1" style = "--bs-btn-padding-y: .0rem; --bs-btn-padding-x: .3rem; --bs-btn-font-size: .75rem;",' +
            ' onclick="terminals.refreshStatus(\'' + data.name + '\')">Refresh</button>';

        if (!data.currentOrder) {
            btns += '<button class="btn btn-warning me-1" style = "--bs-btn-padding-y: .0rem; --bs-btn-padding-x: .3rem; --bs-btn-font-size: .75rem;",' +
                ' onclick="terminals.deleteTerminal(\'' + data.name + '\')">Delete</button>';
        }

        return btns;
    }

    addTerminal() {
        this.#terminalList.addData([{name: "New"}], true);

        /*
         editor: "list", editorParams: {
                        values: this.validLocations,
                        defaultValue: this.validLocations[0],
                        emptyValue: this.validLocations[0],
                    }
         */
    }

    deleteTerminal(name) {
        if (!confirm("Have you already deleted the device at the Square dashboard in 'Settings' -> 'Device Mangement' -> 'Devices' using " +
            "the three dots to the right and the 'forget' action?"))
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
            console.log(key + '=>' + field);
            document.getElementById(field).innerHTML = data[key];
        }
        statusDetailsModal.show();
    }

    terminalPair(terminal) {
        var postData = {
            ajax_request_action: 'pairt',
            terminal: terminal,
        };
        $.ajax({
            method: "POST",
            url: "scripts/admin_pairTerminal.php",
            data: postData,
            success: function (data, textstatus, jqxhr) {
                if (data['error'] !== undefined) {
                    show_message(data['error'], 'error');
                    return;
                }
                if (data['message'] !== undefined) {
                    show_message(data['message'], 'success');
                }
            },
            error: showAjaxError,
        });
    }

    close() {
        if (this.#terminalList !== null) {
            this.#terminalList.destroy();
            this.#terminalList = null;
        }
    }

}