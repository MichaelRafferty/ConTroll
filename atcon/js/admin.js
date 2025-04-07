//import { TabulatorFull as Tabulator } from 'Tabulator';
//import Jquery from 'Jquery';
//import JqueryUI from 'Jquery UI';

// main screen
var message_div = null;
// classes
var users = null;
var printers = null;
var userid = null;
var terminals = null;

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
    if (terminals !== null && terminals.dirty) {
        $message += 'You have unsaved changes in the Terminals tab. ';
    }
    if ($message !== '') {
        return $message + "If you leave this page, you will lose them.";
    }
    return null;
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
            if (data['terminals'] !== undefined {
                if (terminals == null) {
                    terminals = new Terminals(data['terminals']);
                } else {
                    terminals.loadTerminals(data['terminals']);
                    terminals.dirty = false;
                }
            }
        },
        error: showAjaxError,
    });
}

function settab(pane) {
    clear_message();
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
