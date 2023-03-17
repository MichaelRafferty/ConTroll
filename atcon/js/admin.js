//import { TabulatorFull as Tabulator } from 'Tabulator';
//import Jquery from 'Jquery';
//import JqueryUI from 'Jquery UI';

// main screen
var message_div = null;
// classes
var users = null;
var printers = null;

var anydirty = false;
var userid = null;

// search screen
window.onload = (function() {
    'use strict';
    message_div = document.getElementById("result_message");

    loadInitialData('all');
});

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
    invertnotme(cell) {
        'use strict';

        var me = cell.getRow().getCell('id').getValue();
        if (me !== userid) {
            invertTickCross(cell);
        }
    }

    // tabulator formatter to blank out the field if the row is this user
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

    // tabulator formatted to create an add button based on the id (perid) column of the search table
    tabAddButton(cell, formatterParams, onRendered) {
        "use strict";

        //cell - the cell component
        //formatterParams - parameters set for the column
        //onRendered - function to call when the formatter has been rendered

        var id = cell.getRow().getCell('id').getValue();
        return '<button type="button" class="btn btn-sm btn-secondary p-0" onclick="users.addSearchRow(' + id + ')">Add</button >';
    }

    // create the tabulator users table and load it with the 'users' data
    loadUsers(users) {
        'use strict';

        if (this.userlist !== null) {
            this.userlist.destroy();
            this.userlist = null;
            this.searchdiv.hidden = true;
            this.addbtn.disabled = false;
        }
        this.savebtn.disabled = false;
        this.savebtn.innerHTML = 'Save';

        this.userlist = new Tabulator ('#userTab', {
            data: users,
            index: "id",
            layout: "fitData",
            maxHeight: "300px",
            movableRows: false,
            history: true,
            columns: [
                { title: "perid", field: "id", headerSort: true, width: 150,  },
                { title: "Name", field: "name", headerSort: true, headerFilter:true },
                { title: "Check-In", field: "data_entry", headerSort: false, formatter: "tickCross", cellClick: invertTickCross, headerFilter:true, headerWordWrap: true },
                { title: "Cashier", field: "cashier", headerSort: false, formatter: "tickCross", cellClick: invertTickCross, headerFilter:true },
                { title: "Art Inven", field: "artinventory", headerSort: false, formatter: "tickCross", cellClick: invertTickCross, headerFilter:true, headerWordWrap: true },
                { title: "Art Sales", field: "artsales", headerSort: false, formatter: "tickCross", cellClick: invertTickCross, headerFilter:true, headerWordWrap: true },
                { title: "Admin", field: "manager", headerSort: false, formatter: "tickCross", cellClick: this.invertnotme, headerFilter: true },
                { title: "Optional New Password", field: 'new_password',  headerSort: false, editor: 'input', headerFilter:false, headerWordWrap: true, minWidth: 200, formatter: tabPasswordFormatter },
                { title: "Delete", field: "delete", headerSort: false, hozAlign: "center", cellClick: function (e, cell) {
                        if (cell.getRow().getCell('id').getValue() !== userid) {
                            cell.getRow().delete();
                        }
                    }, formatter: this.blankIfMe },
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
            url: "scripts/adminTasks.php",
            data: postData,
            success: function(data, textstatus, jqxhr) {
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
            show_message('Your search criteria returned no matchs', 'warning');
            return;
        }
        show_message(data['message'], 'success');
        this.searchbtn.innerHTML = 'Close Search';

        this.addlist = new Tabulator ('#searchTab', {
            data: data['data'],
            index: "id",
            layout: "fitData",
            maxHeight: "300px",
            movableRows: false,
            history: false,
            columns: [
                { title: "perid", field: "id", headerSort: true, width: 150,  },
                { title: "First Name", field: "first_name", headerSort: true, headerFilter:true },
                { title: "Last Name", field: "last_name", headerSort: true, headerFilter:true },
                { title: "Badge Name", field: "badge_name", headerSort: true, headerFilter:true },
                { title: "Email Address", field: "email_addr", headerSort: true, headerFilter:true },
                { title: "Add", headerSort: false, hozAlign: "center", formatter: this.tabAddButton, minWidth:50},
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
            url: "scripts/adminTasks.php",
            data: postData,
            success: function(data, textstatus, jqxhr) {
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
}

// external call to Users functions: when tabulator calls the function, the this pointer is wrong
function users_changed(data) {
    users.changed();
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
        url: "scripts/adminTasks.php",
        data: postData,
        success: function(data, textstatus, jqxhr) {
            if (data['message'] !== undefined) {
                show_message(data['message'], 'success');
            }
            if (data['userid'] !== undefined) {
                userid = data['userid'];
                users = new Users(data['users']);
            }
            /*
            if (data['servers'] !== undefined)
                loadServers(data['servers']);
            if (data['printers'] !== undefined)
                loadPrinters(data['printers']);
             */
        },
        error: showAjaxError,
    });
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

function invertTickCross(e,cell) {
    'use strict';

    var value = cell.getValue();
    if (value === undefined) {
        value = false;
    }
    cell.setValue(!value, true);
}
