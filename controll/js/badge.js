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
                {title: "Perid", field: "perid", headerFilter: true, width: 120, maxWidth: 120, },
                {title: "Name", field: "fullName", headerFilter: true, headerWordWrap: true, tooltip: watchBuildRecordHover,},
                {field: "last_name", visible: false,},
                {field: "first_name", visible: false,},
                {field: "middle_name", visible: false,},
                {field: "suffix", visible: false,},
                {field: "legalName", visible: false,},
                {title: "Badge Name", field: "badge_name", headerFilter: true, headerWordWrap: true, tooltip: true,},
                {title: "Zip", field: "postal_code", headerFilter: true, headerWordWrap: true, tooltip: true, maxWidth: 120, width: 120},
                {title: "Email Address", field: "email_addr", headerFilter: true, headerWordWrap: true, tooltip: true,},
                {title: "Memberships", field: "memberships", headerFilter: true, headerWordWrap: true, tooltip: true, maxWidth: 300, width: 300,},
                {field: "index", visible: false,},
            ],
        });
    }
}

// hover format
// show the full perinfo record as a hover in the table
function watchBuildRecordHover(e, cell, onRendered) {
    var data = cell.getData();
    //console.log(data);
    var hover_text = 'Person id: ' + data.perid + '<br/>' +
        'Full Name: ' + data.fullName + '<br/>' +
        'Pronouns: ' + data.pronouns + '<br/>' +
        'Legal Name: ' + data.legalName + '<br/>' +
        data.address_1 + '<br/>';
    if (data.address_2 != '') {
        hover_text += data.address_2 + '<br/>';
    }
    hover_text += data.city + ', ' + data.state + ' ' + data.postal_code + '<br/>';
    if (data.country != '' && data.country != 'USA') {
        hover_text += data.country + '<br/>';
    }
    hover_text += 'Badge Name: ' + this.badgeNameDefault(data.badge_name, data.first_name, data.last_name) + '<br/>' +
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
        'Membership: ' + data.reg_label + '<br/>';

    return hover_text;
}

// tabulator formatter for the actions column, displays the update badge, remove, and edit person buttons
// filters for ones already in the cart, and statuses that should not be allowed to be added to the cart
function addWatchIcon(cell, formatterParams, onRendered) { //plain text value
    var html = '';
    var data = cell.getRow().getData();

    if (data.banned == 'Y') {
        return '<button type="button" class="btn btn-sm btn-danger pt-0 pb-0" style="--bs-btn-font-size: 75%;" onclick="removeFromList(' +
            data.perid + '\')">Remove</button>';
    } else {
        html += '<button type="button" class="btn btn-sm btn-secondary pt-0 pb-0" style="--bs-btn-font-size: 75%;" onclick="removeFromList(' +
            data.perid + '\')">Remove</button>&nbsp;' +
            '<button type="button" class="btn btn-sm btn-secondary pt-0 pb-0" style="--bs-btn-font-size: 75%;" onclick="editPerson(' +
            data.perid + '\')">Edit Person</button>';
    }
    if (data.memberships == '') {
        '<button type="button" class="btn btn-sm btn-primary pt-0 pb-0" style="--bs-btn-font-size: 75%;" onclick="updateBadge(' +
        data.perid + '\')">Update Badge</button>';
    }
    return html;
}