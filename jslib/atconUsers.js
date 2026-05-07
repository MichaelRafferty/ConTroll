// Atcon Users Class - all functions and data related to configuring atcon Users

class Users {
    #userList = null;
    #addList = null;

    constructor(users) {
        // Search tabulator elements
        this.#userList = null;
        this.#addList = null;

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

    // get functions
    getAddList() {
        return this.#addList;
    }
    // process press of undo button
    undo() {
        'use strict';
        this.#userList.undo();

        if (this.#userList.getHistoryUndoSize() <= 0) {
            this.undobtn.disabled = true;
            this.dirty = false;
            this.savebtn.innerHTML = "Save";
            this.savebtn.disabled = true;
        }
        if (this.#userList.getHistoryRedoSize() > 0) {
            this.redobtn.disabled = false;
        }
    }

    // process press of redo button
    redo() {
        'use strict';
        this.#userList.redo();

        if (this.#userList.getHistoryUndoSize() > 0) {
            this.undobtn.disabled = false;
            if (this.dirty === false) {
                this.dirty = true;
                this.savebtn.innerHTML = "Save*";
                this.savebtn.disabled = false;
            }
        }

        if (this.#userList.getHistoryRedoSize() <= 0) {
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
        if (this.#userList.getHistoryUndoSize() > 0) {
            this.undobtn.disabled = false;
        }
    }

    // invert TickCross cell only if the row is not this user
    invertnotme(e, cell) {
        'use strict';

        let me = cell.getRow().getCell('id').getValue();
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
        let row = cell.getData();
        if (row.deceased == 'Y') {
            return 'Deceased';
        }
        if (row.banned == 'Y') {
            return 'B';
        }
        return '<button type="button" class="btn btn-sm btn-secondary p-0" onclick="users.addSearchRow(' + row.id + ')">Add</button >';
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

        if (this.#userList !== null) {
            this.#userList.destroy();
            this.#userList = null;
            this.searchdiv.hidden = true;
            this.addbtn.disabled = false;
        }
        this.savebtn.disabled = true;
        this.savebtn.innerHTML = 'Save';

        this.#userList = new Tabulator('#userTab', {
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
        this.#userList.on("dataChanged", users_changed);
    }

    // used to enable the search button when the search field has something in it.  (target of an on.change)
    search_name_changed() {
        "use strict";
        this.searchbtn.disabled = this.search_field.value.trim().length <= 0;
    }

    // close the search block
    cancelSearch() {
        if (this.#addList !== null) {
            this.#addList.destroy();
            this.#addList = null;
        }
        this.searchbtn.innerHTML = 'Search Users';
        this.searchdiv.hidden = true;
        this.searchbtn.disabled = true;
        this.addbtn.disabled = false;
        this.search_field.value = '';
        clear_message();
    }
    // perform the search
    search() {
        // if tabulator table exists, button is to close search, destroy the table and hide the block
        if (this.#addList !== null) {
            this.#addList.destroy();
            this.#addList = null;
        }

        // ok, new search
        let  postData = {
            ajax_request_action: 'searchUsers',
            search_string: this.search_field.value.trim(),
        };
        $.ajax({
            method: "POST",
            url: "scripts/admin_searchUsers.php",
            data: postData,
            success: function (data, textstatus, jqxhr) {
                checkRefresh(data);
                users.showSearch(data);
            },
            error: showAjaxError,
        });
    }

    // show search block
    addUser() {
        "use strict";
        this.addbtn.disabled = true;
        this.searchdiv.hidden = false;
        this.searchbtn.disabled = true;
        this.search_field.value = '';
        if (this.#addList !== null) {
            this.#addList.destroy();
            this.#addList = null;
        }

        clear_message();
    }

    // show the search results returned from the server
    showSearch(data) {
        "use strict";
        if (data['error'] !== undefined) {
            show_message(data['error'], 'error');
            return;
        }
        let  numrows = data['rows'];
        if (numrows <= 0) {
            show_message('Your search criteria returned no matchs', 'warn');
            return;
        }
        show_message(data['message'], 'success');

        this.#addList = new Tabulator('#searchTab', {
            data: data['data'],
            index: "id",
            layout: "fitData",
            maxHeight: "800px",
            movableRows: false,
            history: false,
            columns: [
                {title: "Add", headerSort: false, hozAlign: "center", formatter: this.tabAddButton, minWidth: 50},
                {title: "perid", field: "id", headerSort: true, width: 150, formatter: users.idStatus, },
                {title: "First Name", field: "first_name", headerSort: true, headerFilter: true},
                {title: "Last Name", field: "last_name", headerSort: true, headerFilter: true},
                {title: "Badge Name", field: "badgename", headerSort: true, headerFilter: true, formatter: 'html',},
                {title: "Email Address", field: "email_addr", headerSort: true, headerFilter: true},
            ],
        });
    }

    // formatter for deceased rows
    idStatus(cell, formatterParams, onRendered) {
        let deceased = cell.getRow().getData().deceased;
        let value = cell.getValue();
        let row =  users.getAddList().getRow(value);
        let element = row.getElement();
        element.style.backgroundColor = deceased == 'Y' ? '#FFE0E0' : '';
        return value;
    }

    // process the add button on a search row, the perid of the row (id) is passed in and created by the formatter on the column
    addSearchRow(id) {
        'use strict';

        let  row = this.#addList.getRow(id);
        let  rowData = row.getData();
        this.#userList.clearFilter(true);
        this.#userList.addRow({
            id: rowData['id'],
            name: (rowData['first_name'] + ' ' + rowData['last_name']).trim(),
            new_password: '-',
            delete: "🗑",
        }, true);
        row.delete();
        let  rowCount = this.#addList.getDataCount();
        if (rowCount <= 0) {
            this.search();  // close the search block, it's now empty
        }
    }

    // save the users table and refresh it
    save() {
        "use strict";

        if (this.#addList !== null) {
            this.search(); // close the search block
        }

        this.savebtn.disabled = true;
        // build the dataset of the table
        let  data = this.#userList.getData();
        let  postData = {
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
                checkRefresh(data);
                if (data['message'] !== undefined) {
                    show_message(data['message'], 'success');
                }
                loadInitialData('users');
            },
            error: showAjaxError,
        });
    }

    close() {
        if (this.#userList !== null) {
            this.#userList.destroy();
            this.#userList = null;
            this.searchdiv.hidden = true;
            this.addbtn.disabled = false;
        }
        this.savebtn.disabled = true;
        this.savebtn.innerHTML = 'Save';
    }

// tabulator formatter to blank out the field if the row is the current user in atcon
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
