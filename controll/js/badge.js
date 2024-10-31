// badge Javascript - Free Badges (comps)
addPerson = null;
findPerson = null;
matchList = null;

// edit
editPerson = null;
editTitle = null;
editPersonName = null;
updateExisting = null;

// watchlist
watchList = null;
watchMembers = [];
watchTable = null;
findNameField = null;
selectList = null;
selectTable = null;

// initialization at DOM complete
window.onload = function initpage() {
    // set up the pre-defined fields
    var id = document.getElementById('edit-person');
    if (id) {
        editPerson = new bootstrap.Modal(id);
        editTitle = document.getElementById('editTitle');
        editPersonName = document.getElementById('editPersonName');
        updateExisting = document.getElementById('updateExisting');
    }
    watchList = document.getElementById('watch-list');
    findNameField = document.getElementById('findName');
    selectList = document.getElementById('select-list');
    getWatchList();

}

// load/reload the watch list
function getWatchList() {
    // load the initial data and the proceed to set up the rest of the system
    var postData = {
        ajax_request_action: 'loadWatchList',
    };
    $.ajax({
        method: "POST",
        url: "scripts/badge_getWatchList.php",
        data: postData,
        success: function (data, textstatus, jqxhr) {
            if (data.error !== undefined) {
                show_message(data.error, 'error');
                return;
            }
            if (data.message !== undefined) {
                show_message(data.message, 'success');
            }
            loadWatchList(data);
        },
        error: showAjaxError,
    });
}

// load the watch list
function loadWatchList(data) {
    watchMembers = data['watchMembers'];
    if (watchTable == null) {
        watchTable = new Tabulator('#watch-list', {
            maxHeight: "600px",
            data: watchMembers,
            layout: "fitColumns",
            index: 'id',
            pagination: true,
            paginationAddRow: "table",
            paginationSize: 10,
            paginationSizeSelector: [10, 25, 50, 100, 250, true], //enable page size select element with these options
            initialSort: [
                {column: "last_name", dir: "asc"},
                {column: "first_name", dir: "asc"},
            ],
            columns: [
                {title: "Actions", headerFilter: false, headerSort: false, formatter: addWatchIcon },
                {title: "Perid", field: "id", headerFilter: true, width: 120, maxWidth: 120, },
                {title: "Name", field: "fullName", headerFilter: true, headerWordWrap: true, tooltip: watchBuildRecordHover,},
                {field: "last_name", visible: false,},
                {field: "first_name", visible: false,},
                {field: "middle_name", visible: false,},
                {field: "suffix", visible: false,},
                {field: "legalName", visible: false,},
                {title: "Badge Name", field: "badge_name", headerFilter: true, headerWordWrap: true, tooltip: true,},
                {title: "Zip", field: "zip", headerFilter: true, headerWordWrap: true, tooltip: true, maxWidth: 120, width: 120},
                {title: "Email Address", field: "email_addr", headerFilter: true, headerWordWrap: true, tooltip: true,},
                {title: "Memberships", field: "memberships", headerFilter: true, headerWordWrap: true, tooltip: true, maxWidth: 300, width: 300,},
                {field: "index", visible: false,},
            ],
        });
    } else {
        watchTable.replaceData(watchMembers);
    }
}

// hover format
// show the full perinfo record as a hover in the table
function watchBuildRecordHover(e, cell, onRendered) {
    var data = cell.getData();
    //console.log(data);
    var hover_text = 'Person id: ' + data.id + '<br/>' +
        'Full Name: ' + data.fullName + '<br/>' +
        'Pronouns: ' + data.pronouns + '<br/>' +
        'Legal Name: ' + data.legalName + '<br/>' +
        data.address + '<br/>';
    if (data.addr_2 != '') {
        hover_text += data.addr_2 + '<br/>';
    }
    hover_text += data.city + ', ' + data.state + ' ' + data.postal_code + '<br/>';
    if (data.country != '' && data.country != 'USA') {
        hover_text += data.country + '<br/>';
    }
    hover_text += 'Badge Name: ' + badgeNameDefault(data.badge_name, data.first_name, data.last_name) + '<br/>' +
        'Email: ' + data.email_addr + '<br/>' + 'Phone: ' + data.phone + '<br/>';
    if (data.managedBy) {
        hover_text += 'Managed by: (' + data.managedBy + ') ' + data.mgrFullName + '</br>';
    } else if (data.cntManages > 0) {
        hover_text += 'Manages: ' + data.cntManages + '<br/>';
    }
    hover_text += 'Active:' + data.active;

    // append the policies to the active flag line
    var policies = data.policies;
    for (var row in policies) {
        var policyName = policies[row].policy;
        var policyResp = policies[row].response;
        hover_text += ', ' + policyName + ': ' + policyResp;
    }

    hover_text += '<br/>' +
        'Memberships: ' + data.memberships + '<br/>';

    return hover_text;
}

// badgeNameDefault: build a default badge name if its empty
function badgeNameDefault(badge_name, first_name, last_name) {
    if (badge_name === undefined | badge_name === null || badge_name === '') {
        var default_name = (first_name + ' ' + last_name).trim();
        return '<i>' + default_name.replace(/ +/, ' ') + '</i>';
    }
    return badge_name;
}

// tabulator formatter for the actions column, displays the update badge, remove, and edit person buttons
// filters for ones already in the cart, and statuses that should not be allowed to be added to the cart
function addWatchIcon(cell, formatterParams, onRendered) { //plain text value
    var html = '';
    var data = cell.getRow().getData();

    if (data.banned == 'Y') {
        return '<button type="button" class="btn btn-sm btn-danger pt-0 pb-0" style="--bs-btn-font-size: 75%;" onclick="removeFromList(' +
            data.id + '\')">Remove</button>';
    } else {
        html += '<button type="button" class="btn btn-sm btn-secondary pt-0 pb-0" style="--bs-btn-font-size: 75%;" onclick="removeFromList(' +
            data.id + ')">Remove</button>&nbsp;' +
            '<button type="button" class="btn btn-sm btn-secondary pt-0 pb-0" style="--bs-btn-font-size: 75%;" onclick="editPerson(' +
            data.id + ')">Edit Person</button>';
    }
    if (data.memberships == '') {
        '<button type="button" class="btn btn-sm btn-primary pt-0 pb-0" style="--bs-btn-font-size: 75%;" onclick="updateBadge(' +
        data.id + ')">Update Badge</button>';
    }
    return html;
}

// findExisting - search the database for perid's that match the string
function findExisting() {
    if (!findNameField)
        return false;

    var findName = findNameField.value;
    if (findName.length === undefined || findName.length < 3) {
        show_message("Name to find must be 3 or more characters long, consider using one letter from first a blank and two letters from last as a minimum.", 'error');
        return;
    }

    var postData = {
        type: 'find',
        pattern: findName,
        excludeFree: 1,
    };
    $.ajax({
        method: "POST",
        url: "scripts/people_findPerson.php",
        data: postData,
        success: function (data, textstatus, jqxhr) {
            if (data.error !== undefined) {
                show_message(data.error, 'error');
                return;
            }
            if (data.success !== undefined) {
                show_message(data.success, 'success');
            }
            loadSelectList(data);
        },
        error: showAjaxError,
    });
}

// select list functions - for items to add to watch list

// load - create a new list from the ajax query
function loadSelectList(data) {
    if ((!data.matches) || data.matches.length == 0) {
        if (selectTable) {
            selectTable.destroy();
            selectTable = null;
        }
        return;
    }

    var matches = data.matches;

    if (selectTable) {
        selectTable.replaceData(matches);
        return;
    }

    selectTable = new Tabulator('#select-list', {
        maxHeight: "600px",
        data: matches,
        layout: "fitColumns",
        index: 'id',
        pagination: true,
        paginationAddRow: "table",
        paginationSize: 10,
        paginationSizeSelector: [10, 25, 50, 100, 250, true], //enable page size select element with these options
        initialSort: [
            {column: "last_name", dir: "asc"},
            {column: "first_name", dir: "asc"},
        ],
        columns: [
            {title: "Actions", headerFilter: false, headerSort: false, width: 75, formatter: addSelectIcon },
            {title: "Perid", field: "id", headerFilter: true, width: 120, maxWidth: 120, },
            {title: "Name", field: "fullName", headerFilter: true, headerWordWrap: true, tooltip: watchBuildRecordHover,},
            {field: "last_name", visible: false,},
            {field: "first_name", visible: false,},
            {field: "middle_name", visible: false,},
            {field: "suffix", visible: false,},
            {field: "legalName", visible: false,},
            {title: "Badge Name", field: "badge_name", headerFilter: true, headerWordWrap: true, tooltip: true,},
            {title: "Zip", field: "zip", headerFilter: true, headerWordWrap: true, tooltip: true, maxWidth: 120, width: 120},
            {title: "Email Address", field: "email_addr", headerFilter: true, headerWordWrap: true, tooltip: true,},
            {title: "Memberships", field: "memberships", headerFilter: true, headerWordWrap: true, tooltip: true, maxWidth: 300, width: 300,},
            {field: "index", visible: false,},
        ],
    });
}

// formatter for add icon
function addSelectIcon(cell, formatterParams, onRendered) { //plain text value
    var html = '';
    var data = cell.getRow().getData();

    if (data.banned == 'Y') {
        return '<strong class="ps-1 pe-1" style="background-color: red; color: white;">B</strong>';
    } else {
        html += '<button type="button" class="btn btn-sm btn-primary pt-0 pb-0" style="--bs-btn-font-size: 75%;" onclick="addToList(' +
            data.id + ');">Add</button>';
    }

    return html;
}

// add button - write to list and refresh screen
function addToList(perid) {
    if (!perid)
        return false;

    var postData = {
        ajax_request_action: 'addWatch',
        perid: perid,
    };
    $.ajax({
        method: "POST",
        url: "scripts/badge_addWatch.php",
        data: postData,
        success: function (data, textstatus, jqxhr) {
            if (data.error !== undefined) {
                show_message(data.error, 'error');
                return;
            }
            if (data.success !== undefined) {
                show_message(data.success, 'success');
            }
            if (selectTable) {
                selectTable.deleteRow(perid);
            }
            loadWatchList(data);
        },
        error: showAjaxError,
    });
}

// remove button - remove from watch list
// add button - write to list and refresh screen
function removeFromList(perid) {
    if (!perid)
        return false;

    var postData = {
        ajax_request_action: 'removeWatch',
        perid: perid,
    };
    $.ajax({
        method: "POST",
        url: "scripts/badge_removeWatch.php",
        data: postData,
        success: function (data, textstatus, jqxhr) {
            if (data.error !== undefined) {
                show_message(data.error, 'error');
                return;
            }
            if (data.success !== undefined) {
                show_message(data.success, 'success');
            }
            loadWatchList(data);
        },
        error: showAjaxError,
    });
}