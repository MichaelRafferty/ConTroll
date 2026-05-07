//import { TabulatorFull as Tabulator } from 'Tabulator';
//import Jquery from 'Jquery';
//import JqueryUI from 'Jquery UI';

// main screen
var message_div = null;
// classes
var users = null;
var printers = null;
var userid = null;
var configEditor = null;
var checkConfigReload = true;

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
var fileManager = null;

// initial setup
window.onload = function initpage() {
    debug = config.debug;
    conid = config.debug;
    let id = document.getElementById('user-lookup');
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

    fileManager = new FileManager();
    checkRefresh(config);
}

window.onbeforeunload = function() {
    let $message = ''

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
    let formdata = $("#" + userid).serialize();
    $('#test').append(formdata);
    $.ajax({
        url: 'scripts/admin_permUpdate.php',
        method: 'POST',
        data: formdata+"&action=clear",
        success: function (data, textStatus, jhXHR) {
            $('#test').append(JSON.stringify(data, null, 2));
            checkRefresh(data);
            location.reload();
        }
    });
}

function updatePermissions(userid) {
    clearError();
    clear_message();
    let formdata = $("#" + userid).serialize();
    $('#test').append(formdata);
    $.ajax({
        url: 'scripts/admin_permUpdate.php',
        method: 'POST',
        data: formdata+"&action=update",
        success: function (data, textStatus, jhXHR) {
            $('#test').append(JSON.stringify(data, null, 2));
            checkRefresh(data);
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
    let name_search = document.getElementById('add_name_search');
    name_search.value = email;
    add_find();
}

// get the list of people for the match
function add_find() {
    clear_message('result_message_user');
    let name_search = document.getElementById('add_name_search').value.toLowerCase().trim();
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
            checkRefresh(data);
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
    let perinfo = data.perinfo;
    let name_search = data.name_search;
    if (perinfo.length > 0) {
        add_result_table = new Tabulator('#add_search_results', {
            maxHeight: "600px",
            data: perinfo,
            index: "perid",
            layout: "fitColumns",
            initialSort: [
                {column: "fullName", dir: "asc"},
            ],
            columns: [
                {width: 100, headerFilter: false, headerSort: false, formatter: addNewUser, formatterParams: {t: "result"},},
                {title: "perid", field: "perid", width: 100, formatter: idStatus, },
                {field: "index", visible: debug > 2,},
                {title: "Name", field: "fullName", width: 200, headerFilter: true, headerWordWrap: true, tooltip: build_record_hover,},
                {field: "last_name", visible: debug > 2,},
                {field: "first_name", visible: debug > 2,},
                {field: "middle_name", visible: debug > 2,},
                {field: "suffix", visible: debug > 2,},
                {title: "Badge Name", field: "badgename", width: 200, headerFilter: true, headerWordWrap: true, tooltip: true, formatter: 'html',},
                {title: "Zip", field: "postal_code", headerFilter: true, headerWordWrap: true, tooltip: true, maxWidth: 100, width: 100},
                {title: "Email Address", field: "email_addr", width: 200, headerFilter: true, headerWordWrap: true, tooltip: true,},
                {field: "index", visible: debug > 2,},
            ],
        });
    }
}

// show the full perinfo record as a hover in the table
function build_record_hover(e, cell, onRendered) {
    let data = cell.getData();
    //console.log(data);
    let hover_text = 'Person id: ' + data.perid + '<br/>' +
        (data.first_name + ' ' + data.middle_name + ' ' + data.last_name).trim() + '<br/>' +
        data.address_1 + '<br/>';
    if (data.address_2 != '') {
        hover_text += data.address_2 + '<br/>';
    }
    hover_text += data.city + ', ' + data.state + ' ' + data.postal_code + '<br/>';
    if (data.country != '' && data.country != 'USA') {
        hover_text += data.country + '<br/>';
    }
    hover_text += 'Badge Name: ' + badgeNameDefault(data.badge_name, data.badgeNameL2, data.first_name, data.last_name) + '<br/>' +
        'Email: ' + data.email_addr + '<br/>' + 'Phone: ' + data.phone;

    return hover_text;
}

// tabulator formatter for the merge column for the find results, displays the "Select" to mark the user to add
function addNewUser(cell, formatterParams, onRendered) { //plain text value
    let color = 'btn-success';
    let row = cell.getData();
    let label = 'Add User'
    let disabled = '';

    if (addType == 'fixuser')
        label = 'Add Perid';
    if (row.deceased == 'Y') {
        color = 'btn-warning';
        label = 'Deceased';
        disabled = ' disabled';
    }  else if (row.banned == 'Y') {
        color = 'btn-danger';
        label = 'Banned';
        disabled = ' disabled';
    }

    return '<button type="button" class="btn btn-sm ' + color + ' pt-0 pb-0" style="--bs-btn-font-size: 75%;"' +
        disabled + ' onclick="selectUser(' + row.perid + ')">' + label + '</button>';
}

// formatter for deceased rows
function idStatus(cell, formatterParams, onRendered) {
    let deceased = cell.getRow().getData().deceased;
    let value = cell.getValue();
    let row =  add_result_table.getRow(value);
    let element = row.getElement();
    element.style.backgroundColor = deceased == 'Y' ? '#FFE0E0' : '';
    return value;
}

function selectUser(perid) {
    clearError();
    clear_message();
    let script = '';
    let data = null;

    if (addType == 'newuser') {
        $('#test').append('create=' + perid);
        script = 'scripts/permCreate.php';
        data = {perid: perid};
    } else if (addType == 'fixuser' && fixUserid != null) {
        script = 'scripts/permFixPerid.php';
        data = { perid: perid,  userid: fixUserid};
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
            checkRefresh(data);
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
    if (printers) {
        printers.close();
        printers = null;
    }
    if (configEditor && checkConfigReload) {
        if (configEditor.close()) {
            checkConfigReload = true;
            configEditor = null;
        } else {
            checkConfigReload = false;
        }
    }

    // now open the relevant one, and create the class if needed
    switch (tabname) {
        case 'menu-pane':
            getMenu();
            break;
        case 'keys-pane':
            console.log(tabname);
            break;
        case 'atconUsers-pane':
            loadAtconUsers();
            break;
        case 'atconPrinters-pane':
            loadAtconPrinters();
            break;
        case 'configEdit-pane':
            if (configEditor == null) {
                loadConfigEditor();
            }
            checkConfigReload = true;
            break;
        case 'fileManager-pane':
            fileManager.open();
            break;
    }
}

// atcon call up functions
function loadAtconUsers() {
    script = 'scripts/admin_atconLoadData.php';
    postData = {
        load_type: 'users'
    }
    clearError();
    clear_message();
    $.ajax({
        url: script,
        method: 'POST',
        data: postData,
        success: function (data, textStatus, jhXHR) {
            if (data.error) {
                show_message(data.error, 'error');
                return;
            }
            checkRefresh(data);
            if (data.warn) {
                show_message(data.error, 'warn');
                return;
            }
            openUsers(data);
        },
        error: function (jqXHR, textStatus, errorThrown) {
            showError("ERROR in getMenu: " + textStatus, jqXHR);
        },
    });
}

function openUsers(data) {
    if (data.success) {
        show_message(data.success, 'success');
    }
    users = new Users(data.users)
}

function loadAtconPrinters() {
    script = 'scripts/admin_atconLoadData.php';
    postData = {
        load_type: 'printers'
    }
    clearError();
    clear_message();
    $.ajax({
        url: script,
        method: 'POST',
        data: postData,
        success: function (data, textStatus, jhXHR) {
            if (data.error) {
                show_message(data.error, 'error');
                return;
            }
            checkRefresh(data);
            if (data.warn) {
                show_message(data.error, 'warn');
                return;
            }
            openPrinters(data);
        },
        error: function (jqXHR, textStatus, errorThrown) {
            showError("ERROR in getMenu: " + textStatus, jqXHR);
        },
    });
}

function openPrinters(data) {
    if (data.success) {
        show_message(data.success, 'success');
    }
    printers = new Printers(data.servers, data.printers)
}

// configuration editor
function loadConfigEditor() {
    script = 'scripts/configEditLoadData.php';
    postData = {
        load_type: 'conf',
        perm: 'admin'
    }
    clearError();
    clear_message();
    $.ajax({
        url: script,
        method: 'POST',
        data: postData,
        success: function (data, textStatus, jhXHR) {
            if (data.error) {
                show_message(data.error, 'error');
                return;
            }
            checkRefresh(data);
            if (data.warn) {
                show_message(data.error, 'warn');
                return;
            }
            openConfigEditor(data);
        },
        error: function (jqXHR, textStatus, errorThrown) {
            showError("ERROR in getMenu: " + textStatus, jqXHR);
        },
    });
}

function openConfigEditor(data) {
    if (data.success) {
        show_message(data.success, 'success');
    }
    configEditor = new ConfigEditor(data);
}

function cellChanged(cell) {
    dirty = true;
    setCellChanged(cell);
}

function deleteicon(cell, formattParams, onRendered) {
    let value = cell.getValue();
    if (value == 0)
        return "&#x1F5D1;";
    return value;
}

function deleterow(e, row) {
    let count = row.getCell("uses").getValue();
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
            checkRefresh(data);
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
    let undosize = menuTable.getHistoryUndoSize();
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

    let script = "scripts/admin_saveMenu.php";

    let postdata = {
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
            checkRefresh(data);
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
    let script = 'scripts/admin_buildNewYear.php'
    let postdata = {
        conid: conid,
        action: 'build'
    }
    $.ajax({
        url: script,
        method: 'POST',
        data: postdata,
        success: function (data, textStatus, jhXHR) {
            checkRefresh(data);
            window.location="/admin.php?msg=" + encodeURI(data.success);
        },
        error: function (jqXHR, textStatus, errorThrown) {
            showError("ERROR in " + script + ": " + textStatus, jqXHR);
            return false;
        }
    });
}

//  load/refresh the data from the server.  Which items are refreshed depends on the loadtype field
//  Possible loadtypes:
//      all
//      users
//      printers
function loadInitialData(loadtype) {
    'use strict';

    let postData = {
        ajax_request_action: 'loadData',
        load_type: loadtype
    };
    $.ajax({
        method: "POST",
        url: "scripts/admin_atconLoadData.php",
        data: postData,
        success: function(data, textstatus, jqxhr) {
            checkRefresh(data);
            if (data.message !== undefined) {
                show_message(data.message, 'success');
            }
            if (data.userid !== undefined) {
                userid = data.userid;
                if (users == null) {
                    users = new Users(data.users);
                } else {
                    users.loadUsers(data.users);
                    users.dirty = false;
                }
            }
            if (data.servers !== undefined) {
                if (printers == null) {
                    printers = new Printers(data.servers, data.printers);
                } else {
                    printers.loadPrinters(data.servers, data.printers);
                    printers.dirty = false;
                    printers.serverNameToDelete = null;
                }
            }
        },
        error: showAjaxError,
    });
}
// atcon tabs common functions
// Useful tabulator functions (formatters, invert toggle)

// shows password when editing it, shows ***** for a changed password or a string about the password for existing or needs for new rows
function tabPasswordFormatter(cell, formatterParams, onRendered) {
    "use strict";

    //cell - the cell component
    //formatterParams - parameters set for the column
    //onRendered - function to call when the formatter has been rendered

    let curval = cell.getValue();
    if (curval === undefined || curval === null || curval === '') {
        return 'Keep Existing Password';
    }
    if (curval === '-') {
        return 'Needs Initial Password';
    }
    return '******';
}

function localServersList() {
    let servers = printers.serverlist.getData();
    let distinctServers = new Array();
    for (let i = 0; i < servers.length; i++) {
        if (servers[i].local === true || Number(servers[i].local) === 1)
            distinctServers[servers[i].serverName] = 1;
    }
    return Object.keys(distinctServers);
}
function invertTickCross(e,cell) {
    'use strict';

    let value = cell.getValue();
    if (value === undefined) {
        value = false;
    }
    if (value === 0 || Number(value) === 0)
        value = false;
    else if (value === "1" || Number(value) > 0)
        value = true;

    cell.setValue(!value, true);
}
