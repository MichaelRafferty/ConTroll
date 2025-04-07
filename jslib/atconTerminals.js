// Class Atcon square Terminals
// all functions to configure the Square Terminals for atcon

class Terminals {
    constructor(terminals, locations) {
        // Search tabulator elements
        this.terminalList = null;
        this.validLocations = locations;

        // Users HTML elements
        this.addbtn = document.getElementById('terminals_add_btn');
        this.terminals_addbtn = document.getElementById('terminals_add_btn');
        
        // load initial data
        this.loadTerminals(terminals);
        this.dirty = false;
    }

    loadTerminals(terminals) {
        'use strict';

        if (this.terminalList !== null) {
            this.terminalList.destroy();
            this.terminalList = null;
        }

        this.terminalList = new Tabulator ('#terminalsTable', {
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
                { title: "Current Order", field: "currentOrder", width: 150, headerSort: true, headerFilter:true, headerWordWrap: true, },
                { title: "Current Payment", field: "currentPayment", width: 150, headerSort: true, headerFilter:true, headerWordWrap: true, },
                { title: "Control Status", field: "controlStatus", width: 150, headerSort: true, headerFilter:true, headerWordWrap: true, },
                { title: "Cntrol Status Changed", field: "controlStatusChanged", width: 150, headerSort: true, headerFilter:true, headerWordWrap: true, },
                { title: "Product Type", field: "productType", minWidth: 150, headerSort: true, headerFilter:true, headerWordWrap: true, },
                { title: "Location ID", field: "locationId", headerSort: true, headerFilter:true,  headerWordWrap: true, },
                { title: "Square ID", field: "squareId", headerSort: true, headerFilter:true, headerWordWrap: true, },
                { title: "Device ID", field: "deviceId", headerSort: true, headerFilter:true, headerWordWrap: true, },
                { title: "Pair By", field: "pairBy", headerWordWrap: true, },
                { title: "Paired At", field: "pairedAt", headerWordWrap: true, headerSort: true, headerFilter: true, },
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
            ' onclick="terminals.refreshStatus(\'' + data.name + '\')">Refresh</button>';

        if (!data.currentOrder) {
            btns += '<button class="btn btn-warning me-1" style = "--bs-btn-padding-y: .0rem; --bs-btn-padding-x: .3rem; --bs-btn-font-size: .75rem;",' +
                ' onclick="terminals.deleteTerminal(\'' + data.name + '\')">Delete</button>';
        }

        return btns;
    }

    addTerminal() {
        this.terminalList.addData([{name: "New"}], true);

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
        if (this.terminalList !== null) {
            this.terminalList.destroy();
            this.terminalList = null;
        }
    }

}