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
        this.loadTerminals(terminals, locations);
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
                { title: "Name", field: "name", minWidth: 150, headerSort: true, headerFilter: 'input'  },
                { title: "Status", field: "status", minWidth: 100, headerSort: true, headerFilter:true, headerWordWrap: true, },
                { title: "Status Changed", field: "statusChanged", minWidth: 150, headerSort: true, headerFilter:true, headerWordWrap: true, },
                { title: "Product Type", field: "productType", minWidth: 150, headerSort: true, headerFilter:true, headerWordWrap: true, },
                { title: "Location ID", field: "locationId", headerSort: true, headerFilter:true,  headerWordWrap: true, },
                { title: "Square ID", field: "squareId", headerSort: true, headerFilter:true, headerWordWrap: true, },
                { title: "Device ID", field: "deviceId", headerSort: true, headerFilter:true, headerWordWrap: true, },
                { title: "Square Code", field: "squareCode", headerSort: true, headerFilter:true, headerWordWrap: true, },
                { title: "Pair By", field: "pairBy", headerWordWrap: true, },
                { title: "Paired At", field: "pairedAt", headerWordWrap: true, headerSort: true, headerFilter: true, },
                { title: "Delete", field: "delete", headerSort: false, hozAlign: "center", cellClick: function (e, cell) {
                            cell.getRow().delete();
                    },
                },
            ],
        });
        this.terminalList.on("dataChanged", terminals_changed);
    }

// tabulator formatter for the actions column, displays the update badge, remove, and edit person buttons
// filters for ones already in the cart, and statuses that should not be allowed to be added to the cart
    termActions(cell, formatterParams, onRendered) { //plain text value
        var html = '';
        var data = cell.getData();
        var btns = "";
        btns += '<button class="btn btn-primary me-1" style = "--bs-btn-padding-y: .0rem; --bs-btn-padding-x: .3rem; --bs-btn-font-size: .75rem;",' +
            ' onclick="terminals.refreshStatus(\'' + data.name + '\')">Refresh</button>';
        return btns;
    }

    addTerminal() {
        this.terminalList.addData([{name: "New Server", delete: '🗑'}], true);

        /*
         editor: "list", editorParams: {
                        values: this.validLocations,
                        defaultValue: this.validLocations[0],
                        emptyValue: this.validLocations[0],
                    }
         */
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