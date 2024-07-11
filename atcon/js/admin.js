//import { TabulatorFull as Tabulator } from 'Tabulator';
//import Jquery from 'Jquery';
//import JqueryUI from 'Jquery UI';

// main screen
var message_div = null;
// classes
var users = null;
var printers = null;
var userid = null;

// search screen
window.onload = (function() {
    'use strict';
    message_div = document.getElementById("result_message");

    loadInitialData('all');
});

window.onbeforeunload = function() {
    var $message = ''

    if (users !== null && users.dirty)  {
        $message += 'You have unsaved changes in the Users tab. ';
    }
    if (printers !== null && printers.dirty) {
        $message += 'You have unsaved changes in the Printers tab. ';
    }
    if ($message !== '') {
        return $message + "If you leave this page, you will lose them.";
    }
    return null;
}

class Users {
    constructor(users) {
        // Search tabulator elements
        this.userlist = null;
        this.addlist = null;

        // Users HTML elements
        this.savebtn = document.getElementById('users_save_btn');
        this.undobtn = document.getElementById('users_undo_btn');
        this.redobtn = document.getElementById('users_redo_btn');
        this.addbtn = document.getElementById('users_add_user_btn');
        this.searchbtn = document.getElementById('users_search_btn');
        this.searchdiv = document.getElementById('addUser');
        this.search_field = document.getElementById('name_search');

        // load initial data
        this.loadUsers(users);
        this.dirty = false;
    }

    // process press of undo button
    undo() {
        'use strict';
        this.userlist.undo();

        if (this.userlist.getHistoryUndoSize() <= 0) {
            this.undobtn.disabled = true;
            this.dirty = false;
            this.savebtn.innerHTML = "Save";
            this.savebtn.disabled = true;
        }
        if (this.userlist.getHistoryRedoSize() > 0) {
            this.redobtn.disabled = false;
        }
    }

    // process press of redo button
    redo() {
        'use strict';
        this.userlist.redo();

        if (this.userlist.getHistoryUndoSize() > 0) {
            this.undobtn.disabled = false;
            if (this.dirty === false) {
                this.dirty = true;
                this.savebtn.innerHTML = "Save*";
                this.savebtn.disabled = false;
            }
        }

        if (this.userlist.getHistoryRedoSize() <= 0) {
            this.redobtn.disabled = true;
        }
    }

    // process on.changed for tabulator table to mark dirty and enable/relabel save button
    changed() {
        'use strict'
        //data - the updated table data changed
        this.dirty = true;
        this.savebtn.innerHTML = "Save*";
        this.savebtn.disabled = false;
        if (this.userlist.getHistoryUndoSize() > 0) {
            this.undobtn.disabled = false;
        }
    }

    // invert TickCross cell only if the row is not this user
    invertnotme(e, cell) {
        'use strict';

        var me = cell.getRow().getCell('id').getValue();
        if (me !== userid) {
            invertTickCross(e, cell);
        }
    }

    // tabulator formatted to create an add button based on the id (perid) column of the search table
    tabAddButton(cell, formatterParams, onRendered) {
        "use strict";

        //cell - the cell component
        //formatterParams - parameters set for the column
        //onRendered - function to call when the formatter has been rendered

        var id = cell.getRow().getCell('id').getValue();
        return '<button type="button" class="btn btn-sm btn-secondary p-0" onclick="users.addSearchRow(' + id + ')">Add</button >';
    }

    // there are issues mapping the header filter to the rows, as tick cross filtering would need to be tri state, (true, false, any).
    tickCrossFilterEval(headerValue, rowValue, rowData, filterParams) {
        console.log("Header Value: " + headerValue + ", rowValue = " + rowValue);
        return true;
    }

    tickCrossFilterSelect
    // create the tabulator users table and load it with the 'users' data
    loadUsers(users) {
        'use strict';

        if (this.userlist !== null) {
            this.userlist.destroy();
            this.userlist = null;
            this.searchdiv.hidden = true;
            this.addbtn.disabled = false;
        }
        this.savebtn.disabled = true;
        this.savebtn.innerHTML = 'Save';

        this.userlist = new Tabulator('#userTab', {
            data: users,
            index: "id",
            layout: "fitData",
            maxHeight: "300px",
            movableRows: false,
            history: true,
            columns: [
                {title: "perid", field: "id", headerSort: true, width: 150,},
                {title: "Name", field: "name", headerSort: true, headerFilter: true},
                {
                    title: "Check-In",
                    field: "data_entry",
                    headerSort: false,
                    formatter: "tickCross",
                    cellClick: invertTickCross,
                    headerFilter: "tickCross", headerFilterParams:{ tristate: true },
                    headerWordWrap: true
                },
                {title: "Cashier", field: "cashier", headerSort: false, formatter: "tickCross", cellClick: invertTickCross, headerFilter: "tickCross", headerFilterParams:{ tristate: true }, },
                {
                    title: "Art Inven",
                    field: "artinventory",
                    headerSort: false,
                    formatter: "tickCross",
                    cellClick: invertTickCross,
                    headerFilter: "tickCross", headerFilterParams:{ tristate: true },
                    headerWordWrap: true
                },
                {
                    title: "Art Sales",
                    field: "artsales",
                    headerSort: false,
                    formatter: "tickCross",
                    cellClick: invertTickCross,
                    headerFilter: "tickCross", headerFilterParams:{ tristate: true },
                    headerWordWrap: true
                },
                {
                    title: "Art Show",
                    field: "artshow",
                    headerSort: false,
                    formatter: "tickCross",
                    cellClick: invertTickCross,
                    headerFilter: "tickCross", headerFilterParams:{ tristate: true },
                    headerWordWrap: true
                },
                {
                    title: "Vol-Roll",
                    field: "vol_roll",
                    headerSort: false,
                    formatter: "tickCross",
                    cellClick: invertTickCross,
                    headerFilter: "tickCross", headerFilterParams:{ tristate: true },
                    headerWordWrap: true
                },
                {title: "Mgr", field: "manager", headerSort: false, formatter: "tickCross", cellClick: this.invertnotme, headerFilter: "tickCross", headerFilterParams:{ tristate: true }, },
                {
                    title: "Optional New Password",
                    field: 'new_password',
                    headerSort: false,
                    editor: 'input',
                    headerFilter: false,
                    headerWordWrap: true,
                    minWidth: 200,
                    formatter: tabPasswordFormatter
                },
                {
                    title: "Delete", field: "delete", headerSort: false, hozAlign: "center", cellClick: function (e, cell) {
                        if (cell.getRow().getCell('id').getValue() !== userid) {
                            cell.getRow().delete();
                        }
                    }, formatter: this.blankIfMe
                },
            ],
        });
        this.userlist.on("dataChanged", users_changed);
    }

    // used to enable the search button when the search field has something in it.  (target of an on.change)
    search_name_changed() {
        "use strict";
        this.searchbtn.disabled = this.search_field.value.trim().length <= 0;
    }

    // either close the search block, or perform the search
    search() {
        // if tabulator table exists, button is to close search, destroy the table and hide the block
        if (this.addlist !== null) {
            this.addlist.destroy();
            this.addlist = null;
            this.searchbtn.innerHTML = 'Search Users';
            this.searchdiv.hidden = false;
            this.searchbtn.disabled = true;
            this.search_field.value = '';
            show_message('', '');
            return;
        }

        // ok, new search
        var postData = {
            ajax_request_action: 'searchUsers',
            search_string: this.search_field.value.trim(),
        };
        $.ajax({
            method: "POST",
            url: "scripts/admin_searchUsers.php",
            data: postData,
            success: function (data, textstatus, jqxhr) {
                users.showSearch(data);
            },
            error: showAjaxError,
        });
    }

    // show search block
    addUser() {
        "use strict";
        users.addbtn.disabled = true;
        users.searchdiv.hidden = false;
        users.searchbtn.disabled = true;
        users.search_field.value = '';
        show_message('', '');
    }

    // show the search results returned from the server
    showSearch(data) {
        "use strict";
        if (data['error'] !== undefined) {
            show_message(data['error'], 'error');
            return;
        }
        var numrows = data['rows'];
        if (numrows <= 0) {
            show_message('Your search criteria returned no matchs', 'warn');
            return;
        }
        show_message(data['message'], 'success');
        this.searchbtn.innerHTML = 'Close Search';

        this.addlist = new Tabulator('#searchTab', {
            data: data['data'],
            index: "id",
            layout: "fitData",
            maxHeight: "300px",
            movableRows: false,
            history: false,
            columns: [
                {title: "perid", field: "id", headerSort: true, width: 150,},
                {title: "First Name", field: "first_name", headerSort: true, headerFilter: true},
                {title: "Last Name", field: "last_name", headerSort: true, headerFilter: true},
                {title: "Badge Name", field: "badge_name", headerSort: true, headerFilter: true},
                {title: "Email Address", field: "email_addr", headerSort: true, headerFilter: true},
                {title: "Add", headerSort: false, hozAlign: "center", formatter: this.tabAddButton, minWidth: 50},
            ],
        });
    }

    // process the add button on a search row, the perid of the row (id) is passed in and created by the formatter on the column
    addSearchRow(id) {
        'use strict';

        var row = this.addlist.getRow(id);
        var rowData = row.getData();
        this.userlist.addRow({
            id: rowData['id'],
            name: (rowData['first_name'] + ' ' + rowData['last_name']).trim(),
            new_password: '-',
            delete: "🗑",
        }, true);
        row.delete();
        var rowCount = this.addlist.getDataCount();
        if (rowCount <= 0) {
            this.search();  // close the search block, it's now empty
        }
    }

    // save the users table and refresh it
    save() {
        "use strict";

        if (this.addlist !== null) {
            this.search(); // close the search block
        }

        this.savebtn.disabled = true;
        // build the dataset of the table
        var data = this.userlist.getData();
        var postData = {
            ajax_request_action: 'updateUsers',
            data: data,
        };
        $.ajax({
            method: "POST",
            url: "scripts/admin_updateUsers.php",
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
                loadInitialData('users');
            },
            error: showAjaxError,
        });
    }

// tabulator formatter to blank out the field if the row is not a local server
    blankIfMe(cell, formatterParams, onRendered) {
        "use strict";

        //cell - the cell component
        //formatterParams - parameters set for the column
        //onRendered - function to call when the formatter has been rendered

        if (cell.getRow().getCell('id').getValue() === userid) {
            return '';
        }
        return cell.getValue();
    }
}

// external call to Users functions: when tabulator calls the function, the 'this' pointer is wrong
function users_changed(data) {
    users.changed();
}

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
        this.serverlist.addData([{local:true, serverName: "NewServer", address:"", location:"", active: false, delete: '🗑'}], true);
    }

    addPrinter() {
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

//  load/refresh the data from the server.  Which items are refreshed depends on the loadtype field
//  Possible loadtypes:
//      all
//      users
//      printers
function loadInitialData(loadtype) {
    'use strict';

    var postData = {
        ajax_request_action: 'loadData',
        load_type: loadtype
    };
    $.ajax({
        method: "POST",
        url: "scripts/admin_loadData.php",
        data: postData,
        success: function(data, textstatus, jqxhr) {
            if (data['message'] !== undefined) {
                show_message(data['message'], 'success');
            }
            if (data['userid'] !== undefined) {
                userid = data['userid'];
                if (users == null) {
                    users = new Users(data['users']);
                } else {
                    users.loadUsers(data['users']);
                    users.dirty = false;
                }
            }
            if (data['servers'] !== undefined) {
                if (printers == null) {
                    printers = new Printers(data['servers'], data['printers']);
                } else {
                    printers.loadPrinters(data['servers'], data['printers']);
                    printers.dirty = false;
                    printers.serverNameToDelete = null;
                }
            }
        },
        error: showAjaxError,
    });
}

function settab(pane) {
    show_message('', '');
}

// Useful tabulator functions (formatters, invert toggle)

// shows password when editing it, shows ***** for a changed password or a string about the password for existing or needs for new rows
function tabPasswordFormatter(cell, formatterParams, onRendered) {
    "use strict";

    //cell - the cell component
    //formatterParams - parameters set for the column
    //onRendered - function to call when the formatter has been rendered

    var curval = cell.getValue();
    if (curval === undefined || curval === null || curval === '') {
        return 'Keep Existing Password';
    }
    if (curval === '-') {
        return 'Needs Initial Password';
    }
    return '******';
}

function localServersList() {
    var servers = printers.serverlist.getData();
    var distinctServers = new Array();
    for (var i = 0; i < servers.length; i++) {
        if (servers[i]['local'] === true || Number(servers[i]['local']) === 1)
            distinctServers[servers[i]['serverName']] = 1;
    }
    return Object.keys(distinctServers);
}
function invertTickCross(e,cell) {
    'use strict';

    var value = cell.getValue();
    if (value === undefined) {
        value = false;
    }
    if (value === 0 || Number(value) === 0)
        value = false;
    else if (value === "1" || Number(value) > 0)
        value = true;

    cell.setValue(!value, true);
}
