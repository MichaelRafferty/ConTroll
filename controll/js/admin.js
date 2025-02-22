//import { TabulatorFull as Tabulator } from 'Tabulator';
//import Jquery from 'Jquery';
//import JqueryUI from 'Jquery UI';

// main screen
var message_div = null;
// classes
var users = null;
var printers = null;
var userid = null;

conid = null;
// keys items
keysTable = null;
// menu items
menuTable = null;
menuData = null;
menuSaveBtn = null;
menuRedoBtn = null;
menuUndoBtn = null;
menuDirty = false;
// atcon user items
atconTable = null;
atconData = null;
atconSaveBtn = null;
atconRedoBtn = null;
atconUndoBtn = null;
atconDirty = false;

// debug meaning
//  1 = console.logs
//  2 = show hidden table fields
//  4 = show hidden div
debug = 0;
var add_modal = null;
var add_result_table = null;
var add_pattern_field = null;
var addTitle = null;
var addName = null;
var addType = null;
var fixUserid = null;

window.onload = function initpage() {
    debug = config.debug;
    conid = config.debug;
    var id = document.getElementById('user-lookup');
    if (id != null) {
        add_modal = new bootstrap.Modal(id, {focus: true, backdrop: 'static'});
        add_pattern_field = document.getElementById("add_name_search");
        add_pattern_field.addEventListener('keyup', (e) => {
            if (e.code === 'Enter') add_find();
        });
        id.addEventListener('shown.bs.modal', () => {
            add_pattern_field.focus()
        });
        addTitle = document.getElementById('addTitle');
        addName = document.getElementById('addName');
    }

    menuSaveBtn = document.getElementById('menu-save');
    menuUndoBtn = document.getElementById('menu-undo');
    menuRedoBtn = document.getElementById('menu-redo');
    if (config.hasOwnProperty('msg')) {
        show_message(config.msg, 'success');
    }
    if (config.buildNext > 0) {
        console.log("Requested to build " + (Number(conid) + 1) + " setup");
        buildNewYear();
    }
}

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

function clearPermissions(userid) {
    clearError();
    clear_message();
    var formdata = $("#" + userid).serialize();
    $('#test').append(formdata);
    $.ajax({
        url: 'scripts/admin_permUpdate.php',
        method: 'POST',
        data: formdata+"&action=clear",
        success: function (data, textStatus, jhXHR) {
            $('#test').append(JSON.stringify(data, null, 2));
            location.reload();
        }
    });
}

function updatePermissions(userid) {
    clearError();
    clear_message();
    var formdata = $("#" + userid).serialize();
    $('#test').append(formdata);
    $.ajax({
        url: 'scripts/admin_permUpdate.php',
        method: 'POST',
        data: formdata+"&action=update",
        success: function (data, textStatus, jhXHR) {
            $('#test').append(JSON.stringify(data, null, 2));
            location.reload();
        }
    });
}

// addFindPerson - find the person to add for a new user account
function addFindPerson() {
    addType = 'newuser';
    add_modal.show();
    addTitle.innerHTML = 'Lookup Person to Add as User';
}

// updatePerid - find/set a perid for a user missing one
function updatePerid(id, email) {
    addType = 'fixuser';
    fixUserid = id;
    add_modal.show();
    addTitle.innerHTML = 'Add Missing Person Record to User';
    var name_search = document.getElementById('add_name_search');
    name_search.value = email;
    add_find();
}

// get the list of people for the match
function add_find() {
    clear_message('result_message_user');
    var name_search = document.getElementById('add_name_search').value.toLowerCase().trim();
    if (name_search == null || name_search == '')  {
        show_message("No search criteria specified", "warn", 'result_message_user');
        return;
    }

    // search for matching names
    $("button[name='addSearch']").attr("disabled", true);
    test.innerHTML = '';
    clear_message('result_message_user');
    if (add_result_table) {
        add_result_table.destroy();
        add_result_table = null;
    }

    clearError();
    clear_message();
    $.ajax({
        method: "POST",
        url: "scripts/mergeFindRecord.php",
        data: { name_search: name_search, },
        success: function (data, textstatus, jqxhr) {
            $("button[name='mergeSearch']").attr("disabled", false);
            if (data.error !== undefined) {
                show_message(data.error, 'error', 'result_message_user');
                return;
            }
            if (data.message !== undefined) {
                show_message(data.message, 'success', 'result_message_user');
            }
            if (data.warn !== undefined) {
                show_message(data.warn, 'warn', 'result_message_user');
            }
            add_found(data);
        },
        error: function (jqXHR, textStatus, errorThrown) {
            $("button[name='addSearch']").attr("disabled", false);
            showError("ERROR in addFindRecord: " + textStatus, jqXHR);
        }
    });
}

// add_found - display a list of potential users to add
function add_found(data) {
    var perinfo = data.perinfo;
    var name_search = data.name_search;
    if (perinfo.length > 0) {
        add_result_table = new Tabulator('#add_search_results', {
            maxHeight: "600px",
            data: perinfo,
            layout: "fitColumns",
            initialSort: [
                {column: "fullname", dir: "asc"},
            ],
            columns: [
                {width: 100, headerFilter: false, headerSort: false, formatter: addNewUser, formatterParams: {t: "result"},},
                {title: "perid", field: "perid", width: 100, },
                {field: "index", visible: debug > 2,},
                {title: "Name", field: "fullname", width: 200, headerFilter: true, headerWordWrap: true, tooltip: build_record_hover,},
                {field: "last_name", visible: debug > 2,},
                {field: "first_name", visible: debug > 2,},
                {field: "middle_name", visible: debug > 2,},
                {field: "suffix", visible: debug > 2,},
                {title: "Badge Name", field: "badge_name", width: 200, headerFilter: true, headerWordWrap: true, tooltip: true,},
                {title: "Zip", field: "postal_code", headerFilter: true, headerWordWrap: true, tooltip: true, maxWidth: 100, width: 100},
                {title: "Email Address", field: "email_addr", width: 200, headerFilter: true, headerWordWrap: true, tooltip: true,},
                {field: "index", visible: debug > 2,},
            ],
        });
    }
}

// show the full perinfo record as a hover in the table
function build_record_hover(e, cell, onRendered) {
    var data = cell.getData();
    //console.log(data);
    var hover_text = 'Person id: ' + data.perid + '<br/>' +
        (data.first_name + ' ' + data.middle_name + ' ' + data.last_name).trim() + '<br/>' +
        data.address_1 + '<br/>';
    if (data.address_2 != '') {
        hover_text += data.address_2 + '<br/>';
    }
    hover_text += data.city + ', ' + data.state + ' ' + data.postal_code + '<br/>';
    if (data.country != '' && data.country != 'USA') {
        hover_text += data.country + '<br/>';
    }
    hover_text += 'Badge Name: ' + badge_name_default(data.badge_name, data.first_name, data.last_name) + '<br/>' +
        'Email: ' + data.email_addr + '<br/>' + 'Phone: ' + data.phone + '<br/>' +
        'Active:' + data.active + ' Contact?:' + data.contact_ok + ' Share?:' + data.share_reg_ok + '<br/>';

    return hover_text;
}

// tabulator formatter for the merge column for the find results, displays the "Select" to mark the user to add
function addNewUser(cell, formatterParams, onRendered) { //plain text value
    var tid;
    var html = '';
    var color = 'btn-success';
    var perid = cell.getRow().getData().perid;
    var label = 'Add User'
    if (addType == 'fixuser')
        label = 'Add Perid';

    return '<button type="button" class="btn btn-sm ' + color + ' pt-0 pb-0" style="--bs-btn-font-size: 75%;" onclick="selectUser(' + perid + ')">' + label + '</button>';
}
function selectUser(perid) {
    clearError();
    clear_message();

    if (addType == 'newuser') {
        $('#test').append('create=' + perid);
        var script = 'scripts/permCreate.php';
        var data = {perid: perid};
    } else if (addType == 'fixuser' && fixUserid != null) {
        var script = 'scripts/permFixPerid.php';
        var data = { perid: perid,  userid: fixUserid};
    } else {
        showError("Invalid addType passed or no Userid passed");
        return;
    }
    $.ajax({
        url: script,
        method: 'POST',
        data: data,
        success: function (data, textStatus, jhXHR) {
            $('#test').append(JSON.stringify(data, null, 2));
            if (data.error) {
                showError(data.error);
                add_modal.hide();
                return false;
            }
            location.reload();
        },
        error: function (jqXHR, textStatus, errorThrown) {
            showError("ERROR in " + script + ": " + textStatus, jqXHR);
            return false;
        }
    });
}

function settab(tabname) {
    // close all of the other tabs
    clear_message();
    clearMenuTable();
    clearAtconTable();
    if (keysTable) {
        keysTable.destroy();
        keysTable = null;
    }
    if (users) {
        users.close();
        users = null;
    }
    if (users) {
        users.close();
        users = null;
    }

    // now open the relevant one, and create the class if needed
    switch (tabname) {
        case 'menu-pane':
            getMenu();
            break;
        case 'keys-pane':
            console.log(tabname);
        case 'atconUsers-pane':
            loadAtconUsers();
            break;
        case 'atconPrinters-pane':
            loadAtconPrinters();
            break;

    }
}
function cellChanged(cell) {
    dirty = true;
    cell.getElement().style.backgroundColor = "#fff3cd";
}

function deleteicon(cell, formattParams, onRendered) {
    var value = cell.getValue();
    if (value == 0)
        return "&#x1F5D1;";
    return value;
}

function deleterow(e, row) {
    var count = row.getCell("uses").getValue();
    if (count == 0) {
        row.getCell("to_delete").setValue(1);
        row.getCell("uses").setValue('<span style="color:red;"><b>Del</b></span>');
    }
}

// menu tab items
function getMenu() {
    script = 'scripts/admin_getMenu.php';
    postData = {
        action: 'getMenu'
    }
    clearError();
    clear_message();
    $.ajax({
        url: script,
        method: 'POST',
        data: postData,
        success: function (data, textStatus, jhXHR) {
            openMenu(data);
        },
        error: function (jqXHR, textStatus, errorThrown) {
            showError("ERROR in getMenu: " + textStatus, jqXHR);
        },
    });
}

function openMenu(data) {
    if (data.error) {
        show_message(data.error, 'error');
        return;
    }
    if (data.warn) {
        show_message(data.warn, 'warn');
        return;
    }

    if (menuTable != null) {

    }
    if (data.menu) {
        menuTable = new Tabulator('#menuTableDiv', {
            movableRows: true,
            history: true,
            data: data.menu,
            layout: "fitDataTable",
            columns: [
                { rowHandle: true, formatter: "handle", frozen: true, width: 30, minWidth: 30, maxWidth: 30, headerSort: false },
                { field: "id", visible: debug > 0 },
                { title: "Auth Name", field: "name" },
                { title: "Page", field: "page" },
                { title: "Menu Name", field: "display" },
                { field: "sortOrder", visible: debug > 0 },
            ],
        });
        menuTable.on("rowMoved", function (row) {
            menuRowMoved(row)
        });
    }
    if (data.success) {
        show_message(data.success, 'success');
    }
}

function menuRowMoved(row) {
    menuSaveBtn.innerHTML = "Save Changes*";
    menuSaveBtn.disabled = false;
    menuDirty = true;
    menuCheckUndoRedo();
}

function menuCheckUndoRedo() {
    var undosize = menuTable.getHistoryUndoSize();
    menuUndoBtn.disabled = undosize <= 0;
    menuRedoBtn.disabled = menuTable.getHistoryRedoSize() <= 0;
    return undosize;
}

function undoMenu() {
    if (menuTable != null) {
        menuTable.undo();

        if (menuCheckUndoRedo() <= 0) {
            menuDirty = false;
            menuSaveBtn.innerHTML = "Save Changes";
            menuSaveBtn.disabled = true;
        }
    }
};

function redoMenu() {
    if (menuTable != null) {
        menuTable.redo();

        if (menuCheckUndoRedo() > 0) {
            menuDirty = true;
            menuSaveBtn.innerHTML = "Save Changes*";
            menuSaveBtn.disabled = false;
        }
    }
};

function saveMenu() {
    menuSaveBtn.innerHTML = "Saving...";
    menuSaveBtn.disabled = true;

    var script = "scripts/admin_saveMenu.php";

    var postdata = {
        ajax_request_action: 'saveMenu',
        tabledata: JSON.stringify(menuTable.getData()),
    };
    clearError();
    clear_message();
    $.ajax({
        url: script,
        method: 'POST',
        data: postdata,
        success: function (data, textStatus, jhXHR) {
           clearMenuTable(data);
           openMenu(data);
        },
        error: function (jqXHR, textStatus, errorThrown) {
            showError("ERROR in " + script + ": " + textStatus, jqXHR);
            return false;
        }
    });
};

function clearMenuTable() {
    if (menuTable) {
        menuTable.destroy();
        menuTable = null;
        menuDirty = false;
        menuSaveBtn.innerHTML = 'Save Changes';
        menuSaveBtn.disabled = true;
        menuUndoBtn.disabled = true;
        menuRedoBtn.disabled = true;
    }
}

function clearAtconTable() {
    if (atconTable) {
        atconTable.destroy();
        atconTable = null;
        atconTable = false;
        atconSaveBtn.innerHTML = 'Save Changes';
        atconSaveBtn.disabled = true;
        atconUndoBtn.disabled = true;
        atconRedoBtn.disabled = true;
    }
}

function buildNewYear() {
    var script = 'scripts/admin_buildNewYear.php'
    var postdata = {
        conid: conid,
        action: 'build'
    }
    $.ajax({
        url: script,
        method: 'POST',
        data: postdata,
        success: function (data, textStatus, jhXHR) {
            window.location="/admin.php?msg=" + encodeURI(data['success']);
        },
        error: function (jqXHR, textStatus, errorThrown) {
            showError("ERROR in " + script + ": " + textStatus, jqXHR);
            return false;
        }
    });
}