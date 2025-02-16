// badge Javascript - Free Badges (comps)
addPerson = null;
findPerson = null;
matchList = null;

// edit
editPersonModal = null;
editTitle = null;
editPersonName = null;
updateExisting = null;
editCurrentPerid = null;
memberPolicies = null;
memberInterests = null;
memberManaged = null;

// add items
addPersonModal = null;
addMatchTable = null;
addPersonBTN = null;

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
        editPersonModal = new bootstrap.Modal(id);
        editTitle = document.getElementById('editTitle');
        editPersonName = document.getElementById('editPersonName');
        updateExisting = document.getElementById('updateExisting');
    }
    id = document.getElementById('add-person');
    if (id) {
        addPersonModal = new bootstrap.Modal(id);
        addPersonBtn = document.getElementById('addPersonBTN');
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
                {title: "Actions", headerFilter: false, headerSort: false, width: 170, formatter: addWatchIcon },
                {title: "Perid", field: "id", headerFilter: true, width: 120, maxWidth: 120, },
                {title: "Name", field: "fullName", headerWordWrap: true, headerFilter: true, headerFilterFunc: fullNameHeaderFilter,
                    tooltip: watchBuildRecordHover, formatter: "textarea", },
                {field: "last_name", visible: false,},
                {field: "first_name", visible: false,},
                {field: "middle_name", visible: false,},
                {field: "suffix", visible: false,},
                {field: "legalname", visible: false,},
                {field: "pronouns", visible: false,},
                {field: "address", visible: false,},
                {field: "addr_2", visible: false,},
                {field: "phone", visible: false,},
                {field: "country", visible: false,},
                {field: "city", visible: false,},
                {field: "state", visible: false,},
                {title: "Badge Name", field: "badge_name", headerFilter: true, headerWordWrap: true, tooltip: true,},
                {title: "Zip", field: "zip", headerFilter: true, headerWordWrap: true, tooltip: true, maxWidth: 120, width: 120},
                {title: "Email Address", field: "email_addr", headerFilter: true, headerWordWrap: true, tooltip: true,},
                {title: "Memberships", field: "memberships", headerFilter: true, headerWordWrap: true, tooltip: true,
                    maxWidth: 500, width: 300, formatter: membershipFormatter,},
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
        'Legal Name: ' + data.legalname + '<br/>' +
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
            data.id + ')">Edit</button>';
    }
    return html;
}

// build either select list or membership name
function membershipFormatter(cell, formatterParams, onRendered) {
    var html = '';
    var data = cell.getRow().getData();

    if (data.memberships) {
        return data.memberships;
    }

    html +=  '<select name="m_' + data.id + '" id="m_' + data.id + '">' +
        freeSelect + '</select><br/>' +
        '&nbsp;<button type="button" class="btn btn-sm btn-primary pt-0 pb-0" style="--bs-btn-font-size: 75%;" onclick="updateBadge(' +
            data.id + ')">Assign Badge</button>&nbsp;' +
            '<select name="m_' + data.id + '" id="m_' + data.id + '">' +
            freeSelect + '</select>';
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
            {title: "Actions", headerFilter: false, headerSort: false, width: 80, formatter: addSelectIcon },
            {title: "Perid", field: "id", headerFilter: true, width: 120, maxWidth: 120, },
            {title: "Name", field: "fullName",  headerWordWrap: true, headerFilter: true, headerFilterFunc: fullNameHeaderFilter,
                tooltip: watchBuildRecordHover, formatter: "textarea", },
            {field: "last_name", visible: false,},
            {field: "first_name", visible: false,},
            {field: "middle_name", visible: false,},
            {field: "suffix", visible: false,},
            {field: "legalname", visible: false,},
            {field: "pronouns", visible: false,},
            {field: "address", visible: false,},
            {field: "addr_2", visible: false,},
            {field: "phone", visible: false,},
            {field: "country", visible: false,},
            {field: "city", visible: false,},
            {field: "state", visible: false,},
            {title: "Badge Name", field: "badge_name", headerFilter: true, headerWordWrap: true, tooltip: true,},
            {title: "Zip", field: "zip", headerFilter: true, headerWordWrap: true, tooltip: true, maxWidth: 120, width: 120},
            {title: "Email Address", field: "email_addr", headerFilter: true, headerWordWrap: true, tooltip: true,},
            {title: "Memberships", field: "memberships", headerFilter: true, headerWordWrap: true, tooltip: true, maxWidth: 300, width: 300, formatter: "textarea", },
            {field: "index", visible: false,},
        ],
    });
}

// formatter for watch icon
function addSelectIcon(cell, formatterParams, onRendered) { //plain text value
    var html = '';
    var data = cell.getRow().getData();

    if (data.banned == 'Y') {
        return '<strong class="ps-1 pe-1" style="background-color: red; color: white;">B</strong>';
    } else {
        html += '<button type="button" class="btn btn-sm btn-primary pt-0 pb-0" style="--bs-btn-font-size: 75%;" onclick="addToList(' +
            data.id + ');">Watch</button>';
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

// edit person stuff
function editPerson(perid) {
    // ok get the values if this person
    editCurrentPerid = perid;
    // get the rest of the data
    var postdata = {
        type: 'details',
        perid: perid,
    };
    var script = 'scripts/people_findGetDetails.php';
    clear_message();
    clearError();
    $.ajax({
        url: script,
        method: 'POST',
        data: postdata,
        success: function (data, textStatus, jhXHR) {
            findDetailsSuccess(data);
        },
        error: function (jqXHR, textStatus, errorThrown) {
            showError("ERROR in " + script + ": " + textStatus, jqXHR);
            show_message("ERROR in " + script + ": " + jqXHR.responseText, 'error');
            return false;
        }
    });
}

function findDetailsSuccess(dataFound) {
    var i;  // index
    if (dataFound['error']) {
        show_message(dataFound['error'], 'error');
        return;
    }
    if (dataFound['warn']) {
        show_message(dataFound['warn'], 'warn');
        return;
    }

    memberPolicies = dataFound['policies'];
    memberInterests = dataFound['interests'];
    memberManaged = dataFound['managed'];

    var data = watchTable.getRow(editCurrentPerid).getData();
    document.getElementById('f_fname').value = data.first_name;
    document.getElementById('f_mname').value = data.middle_name;
    document.getElementById('f_lname').value = data.last_name;
    document.getElementById('f_suffix').value = data.suffix;
    document.getElementById('f_legalname').value = data.legalname;
    document.getElementById('f_pronouns').value = data.pronouns;
    document.getElementById('f_addr').value = data.address;
    document.getElementById('f_addr2').value = data.addr_2;
    document.getElementById('f_country').value = data.country;
    document.getElementById('f_city').value = data.city;
    document.getElementById('f_state').value = data.state;
    document.getElementById('f_zip').value = data.zip;
    document.getElementById('f_email1').value = data.email_addr;
    document.getElementById('f_email2').value = data.email_addr;
    document.getElementById('f_phone').value = data.phone;
    document.getElementById('f_badgename').value = data.badge_name;

    // loop over the policies
    var keys = Object.keys(memberPolicies);
    for (i = 0; i < keys.length; i++) {
        var policy = memberPolicies[keys[i]];
        var response = policy.response;
        if (response === null || response === undefined) {
            response = policy.defaultValue;
        }
        document.getElementById('p_f_' + policy.policy).checked = response == 'Y';
    }
    editPersonName.innerHTML = data.fullName + ' (' + data.id + ')';
    editPersonModal.show();
}

function saveEdit() {
    var email1 = document.getElementById('f_email1').value;
    var email2 = document.getElementById('f_email2').value;

    if (email1 != email2) {
        show_message("Email addresses do not match.", 'warn');
        return false;
    }

    if (!validateAddress(email1)) {
        show_message("Invalid Email address: " + email1, 'warn');
        return false;
    }

    var postData = {
        action: 'updatePerinfo',
        perid: editCurrentPerid,
        firstName: document.getElementById('f_fname').value,
        middleName: document.getElementById('f_mname').value,
        lastName: document.getElementById('f_lname').value,
        suffix: document.getElementById('f_suffix').value,
        legalName: document.getElementById('f_legalname').value,
        pronouns: document.getElementById('f_pronouns').value,
        address: document.getElementById('f_addr').value,
        addr2: document.getElementById('f_addr2').value,
        country: document.getElementById('f_country').value,
        city: document.getElementById('f_city').value,
        state: document.getElementById('f_state').value,
        zip: document.getElementById('f_zip').value,
        emailAddr: email1,
        phone: document.getElementById('f_phone').value,
        badgeName: document.getElementById('f_badgename').value,
        oldPolicies: JSON.stringify(memberPolicies),
    };

    // now the policies
    var keys = Object.keys(memberPolicies);
    var i;
    var newPolicies = {};
    for (i = 0; i < keys.length; i++) {
        var policy = memberPolicies[keys[i]];
        if (document.getElementById('p_f_' + policy.policy).checked) {
            newPolicies['p_' + policy.policy] = 'Y';
        }
    }
    postData['newPolicies'] = JSON.stringify(newPolicies);
    $.ajax({
        method: "POST",
        url: "scripts/badge_updateEdit.php",
        data: postData,
        success: function (data, textstatus, jqxhr) {
            if (data.error !== undefined) {
                show_message(data.error, 'error', 'find_edit_message');
                return;
            }
            if (data.success !== undefined) {
                show_message(data.success, 'success');
            }
            editPersonModal.hide();
            getWatchList();
        },
        error: showAjaxError,
    });
}

// update badge - pull select value and update the badge, then redraw the list
function updateBadge(perid) {
    var memId = document.getElementById('m_' + perid).value;

    if (memId < 0) {
        show_message("You must select a membership from the select list in the Memberships column.", 'warn');
        return false;
    }

    var postData = {
        action: 'updateMembership',
        perid: perid,
        memId: memId,
    };

    $.ajax({
        method: "POST",
        url: "scripts/badge_addMembership.php",
        data: postData,
        success: function (data, textstatus, jqxhr) {
            if (data.error !== undefined) {
                show_message(data.error, 'error', 'find_edit_message');
                return;
            }
            if (data.warn !== undefined) {
                show_message(data.error, 'warn', 'find_edit_message');
                return;
            }
            if (data.success !== undefined) {
                show_message(data.success, 'success');
            }
            getWatchList();
        },
        error: showAjaxError,
    });
}

// add new person items
function addNew() {
    addClearForm();
    addPersonModal.show();
}

// clear the add form
function addClearForm() {
    document.getElementById('a_fname').value = '';
    document.getElementById('a_mname').value = '';
    document.getElementById('a_lname').value = '';
    document.getElementById('a_suffix').value = '';
    document.getElementById('a_legalname').value = '';
    document.getElementById('a_pronouns').value = '';
    document.getElementById('a_addr').value = '';
    document.getElementById('a_addr2').value = '';
    document.getElementById('a_country').value = 'USA';
    document.getElementById('a_city').value = '';
    document.getElementById('a_state').value = '';
    document.getElementById('a_zip').value = '';
    document.getElementById('a_email1').value = '';
    document.getElementById('a_email2').value = '';
    document.getElementById('a_phone').value = '';
    document.getElementById('a_badgename').value = '';

    // loop over the policies
    var keys = Object.keys(policies);
    for (i = 0; i < keys.length; i++) {
        var policy = policies[keys[i]];
        document.getElementById('p_a_' + policy.policy).checked = policy.defaultValue == 'Y';
    }
    addPersonBtn.disabled = true;
    if (addMatchTable != null) {
        addMatchTable.destroy();
        addMatchTable = null;
    }
}

// check if the person on the form exists
// check if a close match for this person exists and display a table of matches.
function addCheckExists() {
    var email1 = document.getElementById('a_email1').value;
    var email2 = document.getElementById('a_email2').value;
    if (email1 == '') {
        show_message("Email addresses cannot be empty, use /r if refused", 'error', 'add_message');
        return;
    }
    if (email1 != email2 && email1 != '/r') {
        show_message("Email addresses do not match", 'error', 'add_message');
        return;
    }

    clear_message('add_message');
    clearError();
    var postdata = {
        type: 'check',
        firstName: document.getElementById('a_fname').value,
        middleName: document.getElementById('a_mname').value,
        lastName: document.getElementById('a_lname').value,
        suffix: document.getElementById('a_suffix').value,
        legalName: document.getElementById('a_legalname').value,
        pronouns: document.getElementById('a_pronouns').value,
        badgeName: document.getElementById('a_badgename').value,
        address: document.getElementById('a_addr').value,
        addr2: document.getElementById('a_addr2').value,
        city: document.getElementById('a_city').value,
        state: document.getElementById('a_state').value,
        zip: document.getElementById('a_zip').value,
        country: document.getElementById('a_country').value,
        emailAddr: email1,
        phone: document.getElementById('a_phone').value,
    };
    var script = 'scripts/people_checkExists.php';
    $.ajax({
        url: script,
        method: 'POST',
        data: postdata,
        success: function (data, textStatus, jhXHR) {
            addCheckSuccess(data);
        },
        error: function (jqXHR, textStatus, errorThrown) {
            showError("ERROR in " + script + ": " + textStatus, jqXHR);
            show_message("ERROR in " + script + ": " + jqXHR.responseText, 'error', 'add_message');
            return false;
        }
    });
}

function addCheckSuccess(dataFound) {
    if (dataFound['error']) {
        show_message(dataFound['error'], 'error', 'add_message');
        return;
    }
    if (dataFound['warn']) {
        show_message(dataFound['warn'], 'warn', 'add_message');
        return;
    }
    if (dataFound['success']) {
        show_message(dataFound['success'], 'success', 'add_message');
    }

    var matched = dataFound['matches'];
    if (matched.length > 0) {
        addMatchTable = new Tabulator('#addMatchTable', {
            data: matched,
            layout: "fitDataTable",
            index: "id",
            pagination: true,
            paginationAddRow: "table",
            paginationSize: 10,
            paginationSizeSelector: [10, 25, 50, 100, 250, true], //enable page size select element with these options
            columns: [
                {title: "Match", formatter: addSelectButton, headerSort: false},
                {title: "ID", field: "id", width: 120, headerHozAlign: "right", hozAlign: "right", headerSort: true},
                {title: "Mgr Id", field: "managerId", width: 120, headerHozAlign: "right", hozAlign: "right", headerWordWrap: true, headerSort: false},
                {title: "Manager", field: "manager", width: 200, headerSort: true, headerFilter: true,},
                {title: "Full Name", field: "fullName", width: 300, headerSort: true, headerFilter: true, headerFilterFunc: fullNameHeaderFilter,
                    formatter: "textarea", },
                {title: "Badge Name", field: "badge_name", width: 200, headerSort: true, headerFilter: true, },
                {title: "Full Address", field: "fullAddr", width: 400, headerSort: true, headerFilter: true, formatter: "textarea", },
                {title: "Email", field: "email_addr", width: 250, headerSort: true, headerFilter: true,},
                {title: "Phone", field: "phone", width: 150, headerSort: true, headerFilter: true,},
                {field: 'first_name', visible: false,},
                {field: 'middle_name', visible: false,},
                {field: 'last_name', visible: false,},
                {field: 'suffix', visible: false,},
                {field: 'legalName', visible: false,},
                {field: 'pronouns', visible: false,},
                {field: 'address', visible: false,},
                {field: 'addr_2', visible: false,},
                {field: 'city', visible: false,},
                {field: 'state', visible: false,},
                {field: 'zip', visible: false,},
                {field: 'country', visible: false,},
                {field: 'active', visible: false,},
                {field: 'banned', visible: false,},
            ],
        });
    } else {
        if (addMatchTable != null) {
            addMatchTable.destroy();
            addMatchTable = null;
        }
    }
    addPersonBtn.disabled = false;
}

// select button: chose this person instead of adding a new one
function addSelectButton(cell, formatterParams, onRendered) {
    var row = cell.getRow();
    var index = row.getIndex()

    return '<button class="btn btn-primary" style = "--bs-btn-padding-y: .0rem; --bs-btn-padding-x: .3rem; --bs-btn-font-size: .75rem;",' +
        ' onclick="addSelectPerson(' + index + ');">Watch</button>';
}

// add the selected person to the watch list
function addSelectPerson(index) {
    var row = addMatchTable.getRow(index).getData();
    if (addMatchTable != null) {
        addMatchTable.destroy();
        addMatchTable = null;
    }
    addPersonBtn.disabled = true;
    addPersonModal.hide();
    clear_message('add_message')
    addToList(row.id);
}

// saveAdd - we have checked if they exist, now actually add them and then add them to the watch list
function saveAdd() {
    var email1 = document.getElementById('a_email1').value;
    var email2 = document.getElementById('a_email2').value;
    if (email1 == '') {
        show_message("Email addresses cannot be empty, use /r if refused", 'error', 'add_message');
        return;
    }
    if (email1 != email2 && email1 != '/r') {
        show_message("Email addresses do not match", 'error', 'add_message');
        return;
    }

    var newPolicies = {};
    // loop over the policies
    var keys = Object.keys(policies);
    for (i = 0; i < keys.length; i++) {
        var policy = policies[keys[i]];
        newPolicies['p_' + policy.policy] = document.getElementById('p_a_' + policy.policy).checked ? 'Y' : 'N';
    }
    var postdata = {
        type: 'add',
        firstName: document.getElementById('a_fname').value,
        middleName: document.getElementById('a_mname').value,
        lastName: document.getElementById('a_lname').value,
        suffix: document.getElementById('a_suffix').value,
        legalName: document.getElementById('a_legalname').value,
        pronouns: document.getElementById('a_pronouns').value,
        badgeName: document.getElementById('a_badgename').value,
        address: document.getElementById('a_addr').value,
        addr2: document.getElementById('a_addr2').value,
        city: document.getElementById('a_city').value,
        state: document.getElementById('a_state').value,
        zip: document.getElementById('a_zip').value,
        country: document.getElementById('a_country').value,
        emailAddr: email1,
        phone: document.getElementById('a_phone').value,
        newPolicies: JSON.stringify(newPolicies),
    };

    var script = 'scripts/people_addNewPerson.php';
    $.ajax({
        url: script,
        method: 'POST',
        data: postdata,
        success: function (data, textStatus, jhXHR) {
            addSuccess(data);
        },
        error: function (jqXHR, textStatus, errorThrown) {
            showError("ERROR in " + script + ": " + textStatus, jqXHR);
            show_message("ERROR in " + script + ": " + jqXHR.responseText, 'error', 'add_message');
            return false;
        }
    });
}

function addSuccess(data) {
    if (data['error']) {
        show_message(data['error'], 'error', 'add_message');
        return;
    }
    if (data['warn']) {
        show_message(data['warn'], 'warn', 'add_message');
        return;
    }

    if (data['success']) {
        show_message(data['success'], 'success');
    }

    addPersonBtn.disabled = true;
    addPersonModal.hide();
    clear_message('add_message')
    addToList(data.perid);
}