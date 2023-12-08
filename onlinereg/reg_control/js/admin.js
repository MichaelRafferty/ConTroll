current = null;
next = null;
mem = null;
var merge = null;
var add_modal = null;
var add_result_table = null;
var add_pattern_field = null;
var addTitle = null;
var addName = null;
var addType = null;
var fixUserid = null;

window.onload = function initpage() {
    var id = document.getElementById('user-lookup');
    if (id != null) {
        add_modal = new bootstrap.Modal(id, {focus: true, backdrop: 'static'});
        add_pattern_field = document.getElementById("add_name_search");
        add_pattern_field.addEventListener('keyup', (e) => {
            if (e.code === 'Enter') add_find('search');
        });
        id.addEventListener('shown.bs.modal', () => {
            add_pattern_field.focus()
        });
        addTitle = document.getElementById('addTitle');
        addName = document.getElementById('addName');
    }
}

function clearPermissions(userid) {
    var formdata = $("#" + userid).serialize();
    $('#test').append(formdata);
    $.ajax({
        url: 'scripts/permUpdate.php',
        method: 'POST',
        data: formdata+"&action=clear",
        success: function (data, textStatus, jhXHR) {
            $('#test').append(JSON.stringify(data, null, 2));
            location.reload();
        }
    });
}

function updatePermissions(userid) {
    var formdata = $("#" + userid).serialize();
    $('#test').append(formdata);
    $.ajax({
        url: 'scripts/permUpdate.php',
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
    clear_message();
    var name_search = document.getElementById('add_name_search').value.toLowerCase().trim();
    if (name_search == null || name_search == '')  {
        show_message("No search criteria specified", "warn");
        return;
    }

    // search for matching names
    $("button[name='addSearch']").attr("disabled", true);
    test.innerHTML = '';
    clear_message();
    if (add_result_table) {
        add_result_table.destroy();
        add_result_table = null;
    }

    clearError();
    $.ajax({
        method: "POST",
        url: "scripts/mergeFindRecord.php",
        data: { name_search: name_search, },
        success: function (data, textstatus, jqxhr) {
            $("button[name='mergeSearch']").attr("disabled", false);
            if (data['error'] !== undefined) {
                show_message(data['error'], 'error');
                return;
            }
            if (data['message'] !== undefined) {
                show_message(data['message'], 'success');
            }
            if (data['warn'] !== undefined) {
                show_message(data['warn'], 'warn');
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
    var perinfo = data['perinfo'];
    var name_search = data['name_search'];
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
                {field: "index", visible: false,},
                {title: "Name", field: "fullname", width: 200, headerFilter: true, headerWordWrap: true, tooltip: build_record_hover,},
                {field: "last_name", visible: false,},
                {field: "first_name", visible: false,},
                {field: "middle_name", visible: false,},
                {field: "suffix", visible: false,},
                {title: "Badge Name", field: "badge_name", width: 200, headerFilter: true, headerWordWrap: true, tooltip: true,},
                {title: "Zip", field: "postal_code", headerFilter: true, headerWordWrap: true, tooltip: true, maxWidth: 100, width: 100},
                {title: "Email Address", field: "email_addr", width: 200, headerFilter: true, headerWordWrap: true, tooltip: true,},
                {field: "index", visible: false,},
            ],
        });
    }
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
            if (data['error']) {
                showError(data['error']);
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
    switch (tabname) {
        case 'users-pane':
            if (current != null)
                current.close();
            if (next != null)
                next.close();
            if (mem != null)
                mem.close();
            if (merge != null)
                merge.close();

            break;

        case 'consetup-pane':            
            if (next != null)
                next.close();
            if (current != null)
                current.close();
            if (mem != null)
                mem.close();
            if (merge != null)
                merge.close();
            if (current == null)
                current = new consetup('current');
            current.open();
            break;

        case 'nextconsetup-pane':
            if (current != null)
                current.close();
            if (mem != null)
                mem.close();
            if (next != null)
                next.close();
            if (merge != null)
                merge.close();
            if (next == null)
                next = new consetup('next');
            next.open();
            break;
        case 'memconfig-pane':
            if (current != null)
                current.close();
            if (next != null)
                next.close();
            if (mem != null)
                mem.close();
            if (merge != null)
                merge.close();
            if (mem == null)
                mem = new memsetup();
            mem.open();
            break;
        case 'merge-pane':
            if (current != null)
                current.close();
            if (next != null)
                next.close();
            if (mem != null)
                mem.close();
            if (merge != null)
                merge.close();
            if (merge == null)
                merge = new mergesetup();
            merge.open();
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
