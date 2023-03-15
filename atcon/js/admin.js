//import { TabulatorFull as Tabulator } from 'Tabulator';
//import Jquery from 'Jquery';
//import JqueryUI from 'Jquery UI';

// main screen
var message_div = null;
var data = null;
var userlist = null;
var dirty = false;
var savebtn = null;
var undobtn = null;
var redobtn = null;
var addbtn = null;
var userid = null;

// search screen
var searchbtn = null;
var searchdiv = null;
var search_field = null;
var addsearch = null;
var addlist = null;

$(document).ready(function() {
    'use strict';

    savebtn = document.getElementById('save_btn');
    undobtn = document.getElementById('undo_btn');
    redobtn = document.getElementById('redo_btn');
    addbtn = document.getElementById('add_user_btn');
    searchbtn = document.getElementById('search_btn');
    addsearch = document.getElementById('add-search');
    searchdiv = document.getElementById('addUser');
    search_field = document.getElementById('name_search');

    loadInitialData('all');
});

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
            userid = data['userid'];
            if (data['message'] !== undefined) {
                show_message(data['message'], 'success');
            }
            loadUsers(data['users']);
        },
        error: showAjaxError,
    });
}

function undo() {
    'use strict';
    userlist.undo();

    var undoCount = userlist.getHistoryUndoSize();
    if (undoCount <= 0) {
        undobtn.disabled = true;
        dirty = false;
        savebtn.innerHTML = "Save";
        savebtn.disabled = true;
    }
    var redoCount = userlist.getHistoryRedoSize();
    if (redoCount > 0) {
        redobtn.disabled = false;
    }
}

function redo() {
    'use strict';
    userlist.redo();

    var undoCount = userlist.getHistoryUndoSize();
    if (undoCount > 0) {
        undobtn.disabled = false;
        if (dirty === false) {
            dirty = true;
            savebtn.innerHTML = "Save*";
            savebtn.disabled = false;
        }

    }
    var redoCount = userlist.getHistoryRedoSize();
    if (redoCount <= 0) {
        redobtn.disabled = true;
    }
}

function changed(item) {
    'use strict'
    //data - the updated table datachanged
    dirty = true;
    savebtn.innerHTML = "Save*";
    savebtn.disabled = false;
    if (userlist.getHistoryUndoSize() > 0) {
        undobtn.disabled = false;
    }
}

function invertnotme(cell) {
    'use strict';

    var me = cell.getRow().getCell('id').getValue();
    if (me !== userid) {
        invert(cell);
    }
}

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

function tabAddButton(cell, formatterParams, onRendered) {
    "use strict";

    //cell - the cell component
    //formatterParams - parameters set for the column
    //onRendered - function to call when the formatter has been rendered

    var id = cell.getRow().getCell('id').getValue();
    var button = '<button type="button" class="btn btn-sm btn-secondary p-0" onclick="addSearchRow(' + id + ')">Add</button >';
    return button;
}

function blankIfMe(cell, formatterParams, onRendered) {
    "use strict";

    //cell - the cell component
    //formatterParams - parameters set for the column
    //onRendered - function to call when the formatter has been rendered

    if (cell.getRow().getCell('id').getValue() === userid) {
        return '';
    }
    return cell.getValue();
}
function loadUsers(data) {
    'use strict';

    if (userlist !== null) {
        userlist.destroy();
        userlist = null;
        searchdiv.hidden = true;
        addbtn.disabled = false;
    }
    savebtn.disabled = false;
    savebtn.innerHTML = 'Save';

    userlist = new Tabulator ('#userTab', {
        data: data,
        index: "id",
        layout: "fitData",
        maxHeight: "300px",
        movableRows: false,
        history: true,
        columns: [
            { title: "perid", field: "id", headerSort: true, width: 150,  },
            { title: "Name", field: "name", headerSort: true, headerFilter:true },
            { title: "Check-In", field: "data_entry", headerSort: false, formatter: "tickCross", cellClick: invert, headerFilter:true, headerWordWrap: true },
            { title: "Cashier", field: "cashier", headerSort: false, formatter: "tickCross", cellClick: invert, headerFilter:true },
            { title: "Art Inven", field: "artinventory", headerSort: false, formatter: "tickCross", cellClick: invert, headerFilter:true, headerWordWrap: true },
            { title: "Art Sales", field: "artsales", headerSort: false, formatter: "tickCross", cellClick: invert, headerFilter:true, headerWordWrap: true },
            { title: "Admin", field: "manager", headerSort: false, formatter: "tickCross", cellClick: invertnotme, headerFilter: true },
            { title: "Optional New Password", field: 'new_password',  headerSort: false, editor: 'input', headerFilter:false, headerWordWrap: true, minWidth: 200, formatter: tabPasswordFormatter },
            { title: "Delete", field: "delete", headerSort: false, hozAlign: "center", cellClick: function (e, cell) {
                if (cell.getRow().getCell('id').getValue() !== userid) {
                    cell.getRow().delete();
                }
            }, formatter: blankIfMe },
        ],
    });
    userlist.on("dataChanged", changed);
}

function addUser() {
    "use strict";
    addbtn.disabled = true;
    searchdiv.hidden = false;
    searchbtn.disabled = true;
    search_field.value = '';
    show_message('', '');
}

function search_name_changed() {
    "use strict";
    searchbtn.disabled = search_field.value.trim().length <= 0;
}

function search() {
    if (addlist !== null) {
        addlist.destroy();
        addlist = null;
        searchbtn.innerHTML = 'Search Users';
        searchdiv.hidden = false;
        searchbtn.disabled = true;
        search_field.value = '';
        show_message('', '');
        return;
    }
    var postData = {
        ajax_request_action: 'searchUsers',
        search_string: search_field.value.trim(),
    };
    $.ajax({
        method: "POST",
        url: "scripts/adminTasks.php",
        data: postData,
        success: function(data, textstatus, jqxhr) {
            showSearch(data);
        },
        error: showAjaxError,
    });
}

function showSearch(data) {
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
    searchbtn.innerHTML = 'Close Search';

    addlist = new Tabulator ('#searchTab', {
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
            { title: "Add", headerSort: false, hozAlign: "center", formatter: tabAddButton, minWidth:50},
        ],
    });
}

function invert(e,cell) {
    'use strict';

    var value = cell.getValue();
    if (value === undefined) {
        value = false;
    }
    cell.setValue(!value, true);
}

function addSearchRow(id) {
    'use strict';

    var row = addlist.getRow(id);
    var rowData = row.getData();
    userlist.addRow({
        id: rowData['id'],
        name: (rowData['first_name'] + ' ' + rowData['last_name']).trim(),
        new_password: '-',
        delete: "🗑",
    }, true);
    row.delete();
    var rowCount = addlist.getDataCount();
    if (rowCount <= 0) {
        addlist.destroy();
        addlist = null;
        searchbtn.innerHTML = 'Search Users';
        searchdiv.hidden = false;
        searchbtn.disabled = true;
        search_field.value = '';
        show_message('', '');
        return;
    }
}

function save() {
    "use strict";

    if (addlist !== null) {
        addlist.destroy();
        addlist = null;
        searchbtn.innerHTML = 'Search Users';
        searchdiv.hidden = false;
        searchbtn.disabled = true;
        search_field.value = '';
        searchdiv.hidden = true;
        addbtn.disabled = false;
        show_message('', '');
    }

    savebtn.disabled = true;
    // build the dataset of the table
    var data = userlist.getData();
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
                savebtn.disabled = false;
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
