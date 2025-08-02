// Class Atcon Printers
// all functions to configure the printers for atcon

class Printers {
    constructor(servers, printers) {
        // Search tabulator elements
        this.serverlist = null;
        this.printerlist = null;

        // Users HTML elements
        this.addbtn = document.getElementById('printers_add_btn');
        this.printers_savebtn = document.getElementById('printers_save_btn');
        this.printers_undobtn = document.getElementById('printers_undo_btn');
        this.printers_redobtn = document.getElementById('printers_redo_btn');
        this.printers_addbtn = document.getElementById('printers_add_btn');
        this.servers_addbtn = document.getElementById('servers_add_btn');
        this.servers_undobtn = document.getElementById('servers_undo_btn');
        this.servers_redobtn = document.getElementById('servers_redo_btn');

        // load initial data
        this.loadPrinters(servers, printers);
        this.dirty = false;
        this.serverNameToDelete = null;
    }

    loadPrinters(servers, printers) {
        'use strict';

        if (this.serverlist !== null) {
            this.serverlist.destroy();
            this.serverlist = null;
        }

        if (this.printerlist !== null) {
            this.printerlist.destroy();
            this.printerlist = null;
        }

        this.serverlist = new Tabulator ('#serversTable', {
            data: servers,
            index: "serverName",
            layout: "fitData",
            maxHeight: "300px",
            movableRows: false,
            history: true,
            columns: [
                { title: "Local", field: "local", headerSort: true, formatter: "tickCross" },
                { title: "Server", field: "serverName", editor: 'input', editable: localonly, minWidth: 150, headerSort: true, headerFilter: true  },
                { title: "Address", field: "address", editor: 'input', editable: localonly, headerSort: true, headerFilter:true,  },
                { title: "Location", field: "location", editor: 'input', minWidth: 200, headerSort: false, headerFilter:true,  },
                { title: "Active", field: "active", headerSort: false, formatter: "tickCross", cellClick: invertTickCross, headerFilter:true },
                { title: "Delete", field: "delete", headerSort: false, hozAlign: "center", cellClick: function (e, cell) {
                        if (localonly(cell)) {
                            printersDeleteServer(cell.getRow().getCell('serverName').getValue());
                            cell.getRow().delete();
                        }
                    },
                },
                { field: "oldServerName", visible: false, }
            ],
        });
        this.serverlist.on("dataChanged", printers_changed);

        this.printers_savebtn.disabled = true;
        this.printers_savebtn.innerHTML = 'Save';

        this.printerlist = new Tabulator ('#printersTable', {
            data: printers,
            layout: "fitData",
            maxHeight: "300px",
            movableRows: false,
            history: true,
            index: 'serverName',
            columns: [
                { title: "Server", field: "serverName", editor: "list", editorParams: { valuesLookup: localServersList, }, editable: printerLocalonly, minWidth: 150, headerSort: true, headerFilter: 'input'  },
                { title: "Printer", field: "printerName", editor: "input", editable: printerLocalonly, minWidth: 150, headerSort: true, headerFilter:true },
                { title: "Type", field: "printerType", headerSort: true, headerFilter:true,
                    editor: "list", editorParams: {
                        values: ["generic", "receipt", "badge"],
                        defaultValue: "generic",
                        emptyValue: "generic",
                    }
                },
                { title: "Code Page", field: "codePage", headerSort: true, headerFilter:true,
                    editor: "list", editorParams: {
                        values: ["PS", "HPCL", "Dymo4xxPS", "Dymo3xxPS", "DymoSEL", "Windows-1252", "ASCII", "7bit", "8bit", "UTF-8", "UTF-16"],
                        defaultValue: "ASCII",
                        emptyValue: "ASCII",
                    }
                },
                { title: "Active", field: "active", headerSort: false, formatter: "tickCross", cellClick: invertTickCross, headerFilter:true },
                { title: "Printer Test", formatter: function (cell, onRendered, sucess, cancelCallback, editorParams) {
                        if (cell.getRow().getCell('active').getValue() != 1) { return ""; }
                        return '<button type="button" class="btn btn-sm btn-secondary p-0" onclick="printers.printTest(\'' +
                            cell.getRow().getCell('serverName').getValue().trim() + '\',\'' +
                            cell.getRow().getCell('printerName').getValue().trim() + '\',\'' +
                            cell.getRow().getCell('printerType').getValue().trim() + '\',\'' +
                            cell.getRow().getCell('codePage').getValue().trim() + '\')">Test</button>';
                    },
                },
                {
                    title: "Delete", field: "delete", headerSort: false, hozAlign: "center", cellClick: function (e, cell) {
                        if (printerLocalonly(cell)) {
                            cell.getRow().delete();
                        }
                    },
                },
            ],
        });
        this.printerlist.on("dataChanged", printers_changed);
    }

    deleteServer(serverName) {
        var rows = this.printerlist.searchRows("serverName","=", serverName);
        rows.forEach(function (currentValue, index, arr) { currentValue.delete(); });
    }

    // servers editing
    // process press of undo button
    undo_server() {
        'use strict';
        this.serverlist.undo();

        if (this.serverlist.getHistoryUndoSize() <= 0) {
            this.servers_undobtn.disabled = true;
            this.dirty = false;
            this.printers_savebtn.innerHTML = "Save";
            this.printers_savebtn.disabled = true;
        }
        if (this.serverlist.getHistoryRedoSize() > 0) {
            this.servers_redobtn.disabled = false;
        }
    }

    // process press of redo button
    redo_server() {
        'use strict';
        this.serverlist.redo();

        if (this.serverlist.getHistoryUndoSize() > 0) {
            this.servers_undobtn.disabled = false;
            if (this.dirty === false) {
                this.dirty = true;
                this.printers_savebtn.innerHTML = "Save*";
                this.printers_savebtn.disabled = false;
            }
        }

        if (this.serverlist.getHistoryRedoSize() <= 0) {
            this.servers_redobtn.disabled = true;
        }
    }

    // printers editing
    // process press of undo button
    undo_printer() {
        'use strict';
        this.printerlist.undo();

        if (this.printerlist.getHistoryUndoSize() <= 0) {
            this.printers_undobtn.disabled = true;
            this.dirty = false;
            this.printers_savebtn.innerHTML = "Save";
            this.printers_savebtn.disabled = true;
        }
        if (this.printerlist.getHistoryRedoSize() > 0) {
            this.printers_redobtn.disabled = false;
        }
    }

    // process press of redo button
    redo_printer() {
        'use strict';
        this.printerlist.redo();

        if (this.printerlist.getHistoryUndoSize() > 0) {
            this.printers_undobtn.disabled = false;
            if (this.dirty === false) {
                this.dirty = true;
                this.printers_savebtn.innerHTML = "Save*";
                this.printers_savebtn.disabled = false;
            }
        }

        if (this.printerlist.getHistoryRedoSize() <= 0) {
            this.servers_redobtn.disabled = true;
        }
    }
    changed() {
        this.dirty = true;
        this.printers_savebtn.innerHTML = "Save*";
        this.printers_savebtn.disabled = false;
        if (this.serverlist.getHistoryUndoSize() > 0) {
            this.servers_undobtn.disabled = false;
        }
        if (this.printerlist.getHistoryUndoSize() > 0) {
            this.printers_undobtn.disabled = false;
        }
    }

    addServer() {
        this.serverlist.clearFilter(true);
        this.serverlist.addData([{local:true, serverName: "NewServer", address:"", location:"", active: false, delete: '🗑'}], true);
    }

    addPrinter() {
        this.printerlist.clearFilter(true);
        this.printerlist.addData([{serverName: "New Server", printerName: "NewPrinter", printerType: "generic", active: false, delete: '🗑'}], true);
    }

    // save the servers and printers table and refresh it
    save() {
        "use strict";

        this.printers_savebtn.disabled = true;
        // build the dataset of the table
        var servers = this.serverlist.getData();
        var printers = this.printerlist.getData();
        var postData = {
            ajax_request_action: 'updatePrinters',
            servers: servers,
            printers: printers,
        };
        $.ajax({
            method: "POST",
            url: "scripts/admin_updatePrinters.php",
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
                loadInitialData('printers');
            },
            error: showAjaxError,
        });
    }

    printTest(server, printer, type, codepage) {
        var serverData = this.serverlist.searchData('serverName', '=', server);
        var serverRow = serverData[0];

        var postData = {
            ajax_request_action: 'printTest',
            server: serverRow.address,
            printer: printer,
            type: type,
            codepage: codepage
        };
        $.ajax({
            method: "POST",
            url: "scripts/admin_printTest.php",
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
        if (this.printerlist !== null) {
            this.printerlist.destroy();
            this.printerlist = null;
        }

        if (this.serverlist !== null) {
            this.serverlist.destroy();
            this.serverlist = null;
        }

        this.printers_savebtn.disabled = false;
        this.printers_savebtn.innerHTML = "Save";
    }

}

// external call to Users functions: when tabulator calls the function, the 'this' pointer is wrong
function printers_changed(data) {
    printers.changed();
}
function printersDeleteServer(serverName) {
    printers.deleteServer(serverName);
}

// Check if this server is of type local, used to limit actions to local servers only
function localonly(cell) {
    var local = cell.getRow().getCell('local').getValue();
    return local === true || Number(local) === 1;
}

// Check if the server for this printer is of type local, used to limit actions to local printers only
function printerLocalonly(cell) {
    var server = cell.getRow().getCell('serverName').getValue();
    var rows = printers.serverlist.searchRows("serverName", "=", server);
    if (rows[0] !== undefined) {
        var local = rows[0].getCell('local').getValue();
        return local === true || Number(local) === 1;
    }
    return true;
}