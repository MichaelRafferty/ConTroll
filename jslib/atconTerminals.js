// Class Atcon square Terminals
// all functions to configure the Square Terminals for atcon

class Terminals {
    constructor(terminals, locations) {
        // Search tabulator elements
        this.terminalList = null;
        this.validLocations = locations;

        // Users HTML elements
        this.addbtn = document.getElementById('terminals_add_btn');
        this.terminals_savebtn = document.getElementById('terminals_save_btn');
        this.terminals_undobtn = document.getElementById('terminals_undo_btn');
        this.terminals_redobtn = document.getElementById('terminals_redo_btn');
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
                { title: "Name", field: "name", editor: true, minWidth: 150, headerSort: true, headerFilter: 'input'  },
                { title: "Status", field: "status", minWidth: 100, headerSort: true, headerFilter:true, headerWordWrap: true, },
                { title: "Status Changed", field: "statusChanged", minWidth: 150, headerSort: true, headerFilter:true, headerWordWrap: true, },
                { title: "Product Type", field: "productType", minWidth: 150, headerSort: true, headerFilter:true, headerWordWrap: true, },
                { title: "Location ID", field: "locationId", headerSort: true, headerFilter:true,  headerWordWrap: true,
                    editor: "list", editorParams: {
                        values: this.validLocations,
                        defaultValue: this.validLocations[0],
                        emptyValue: this.validLocations[0],
                    }
                },
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

    // terminals editing
    // process press of undo button
    undo_terminal() {
        'use strict';
        this.terminalList.undo();

        if (this.terminalList.getHistoryUndoSize() <= 0) {
            this.terminals_undobtn.disabled = true;
            this.dirty = false;
            this.terminals_savebtn.innerHTML = "Save";
            this.terminals_savebtn.disabled = true;
        }
        if (this.terminalList.getHistoryRedoSize() > 0) {
            this.terminals_redobtn.disabled = false;
        }
    }

    // process press of redo button
    redo_terminal() {
        'use strict';
        this.terminalList.redo();

        if (this.terminalList.getHistoryUndoSize() > 0) {
            this.terminals_undobtn.disabled = false;
            if (this.dirty === false) {
                this.dirty = true;
                this.terminals_savebtn.innerHTML = "Save*";
                this.terminals_savebtn.disabled = false;
            }
        }

        if (this.terminalList.getHistoryRedoSize() <= 0) {
            this.servers_redobtn.disabled = true;
        }
    }

    changed() {
        this.dirty = true;
        this.terminals_savebtn.innerHTML = "Save*";
        this.terminals_savebtn.disabled = false;
        if (this.serverlist.getHistoryUndoSize() > 0) {
            this.servers_undobtn.disabled = false;
        }
        if (this.terminalList.getHistoryUndoSize() > 0) {
            this.terminals_undobtn.disabled = false;
        }
    }

    addTerminal() {
        this.terminalList.addData([{name: "New Server", delete: '🗑'}], true);
    }

    // save the servers and terminals table and refresh it
    save() {
        "use strict";

        this.terminals_savebtn.disabled = true;
        // build the dataset of the table
        var servers = this.serverlist.getData();
        var terminals = this.terminalList.getData();
        var postData = {
            ajax_request_action: 'updateTerminals',
            servers: servers,
            terminals: terminals,
        };
        $.ajax({
            method: "POST",
            url: "scripts/admin_updateTerminals.php",
            data: postData,
            success: function (data, textstatus, jqxhr) {
                if (data['error'] !== undefined) {
                    show_message(data['error'], 'error');
                    this.savebtn.disabled = false;
                    return;
                }
                if (data['message'] !== undefined) {
                    show_message(data['message'], 'success');
                }
                loadInitialData('terminals');
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
               this.terminals_savebtn.disabled = false;
        this.terminals_savebtn.innerHTML = "Save";
    }

}

// external call to Users functions: when tabulator calls the function, the 'this' pointer is wrong
function terminals_changed(data) {
    terminals.changed();
}
