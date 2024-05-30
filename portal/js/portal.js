// Main portal javascript, also requires base.js, vendor_profile.js,

var matchTable = null;

// initial setup
window.onload = function () {
    additional_cost = {};
}

// login functions
// loginWithEmail: dev only
function loginWithEmail(id = null) {
    var emaildiv = document.getElementById('dev_email');
    if (!emaildiv) {
        return;
    }
    var dev_email = emaildiv.value;
    if (dev_email == null || dev_email == "") {
        show_message('Please enter a valid email address', 'warn');
        return
    }
        var data = {
        'email': dev_email,
        'type': 'dev',
        'id': id,
    }
    $.ajax({
        method: 'POST',
        url: 'scripts/processLoginRequest.php',
        data: data,
        success: function (data, textStatus, jqXhr) {
            if (data['error']) {
                show_message(data['error'], 'error');
            } else {
                if (config['debug'] & 1)
                    console.log(data);
                if (data['count'] == 1) {
                    location.href = config.uri;
                }
                show_message("returned " + data['count'] + " matching records.");
                if (matchTable != null) {
                    matchTable.destroy();
                    matchTable = null;
                }
                matchTable = new Tabulator('#matchList', {
                    maxHeight: "600px",
                    data: data['matches'],
                    layout: "fitColumns",
                    responsiveLayout:true,
                    pagination: true,
                    paginationSize: 10,
                    paginationSizeSelector: [10, 25, 50, 100, true], // enable page size select with these options
                    columns: [
                        // phone, badge_name, legalName, address, addr_2, city, state, zip, country, creation_date, update_date, active, banned,
                        { title: 'T', field: 'tablename', headerWordWrap: true, headerFilter: true, width: 50, },
                        { title: 'ID', field: 'id', hozAlign: "right", width:65, headerWordWrap: true, headerFilter: false, },
                        { title: 'Name', field: 'fullname', headerWordWrap: true, headerFilter: true, tooltip: true },
                        { title: 'Phone', field: 'phone', headerWordWrap: true, headerFilter: true, tooltip: true},
                        { title: 'Address', field: 'address', headerWordWrap: true, headerFilter: true, tooltip: true},
                        { title: 'City', field: 'city', headerWordWrap: true, headerFilter: true, tooltip: true, },
                        { title: 'State', field: 'state', headerWordWrap: true, headerFilter: true, tooltip: true, },
                        { title: 'Zip', field: 'zip', headerWordWrap: true, headerFilter: true, tooltip: true, },
                        { title: 'Created', field: 'creation_date', headerWordWrap: true, headerFilter: false, tooltip: true, headerSort: true, },
                        { title: 'Act', field: 'active', headerWordWrap: true, headerFilter: true, tooltip: false, width: 50 },
                        { title: 'Ban', field: 'banned', headerWordWrap: true, headerFilter: true, tooltip: false, width: 50 },
                        { title: 'Actions', width: 100, hozAlign: "center", headerFilter: false, headerSort: false, formatter: loginSelectIcon, },
                    ],
                });
            }
        }
    });
}
// loginSelectIcon: deal with matches in dev list
function loginSelectIcon(cell, formatterParams, onRendered) {
    var id = cell.getRow().getData().id;
    return "<button type='button' class='btn btn-small btn-primary pt-0 pb-0' onclick='loginWithEmail(" + id + ");'>Login</button>";
}
// loginWithToken: show email for token
function loginWithToken() {
    var token_email = document.getElementById('token_email_div');
    if (!token_email) {
        return;
    }
    token_email.hidden = false;
}

function tokenEmailChanged() {
    var token_email = document.getElementById('token_email');
    if (!token_email) {
        document.getElementById('sendLinkBtn').disabled = true;
        return;
    }
    var email = token_email.value;
    if (email == null || email == "") {
        document.getElementById('sendLinkBtn').disabled = true;
        return;
    }

    document.getElementById('sendLinkBtn').disabled = !validateAddress(email);
}
// sendLink: send the login linkl
function sendLink() {
    var token_email = document.getElementById('token_email').value;
    if (!validateAddress(token_email)) {
        show_message('Please enter a valid email address', 'warn');
        return
    }
    var data = {
        'email': token_email,
        'type': 'token',
    }
    $.ajax({
        method: 'POST',
        url: 'scripts/processLoginRequest.php',
        data: data,
        success: function (data, textStatus, jqXhr) {
            if (data['error']) {
                show_message(data['error'], 'error');
            } else {
                if (config['debug'] & 1)
                    console.log(data);
                show_message("Link sent, check your email and click on the link to login.");
            }
        }
    });
}
// request permission to apply for space in a region that requires 'permission' to apply
function requestPermission(id, tag) {
    var data = {
        'regionYearId': id,
        'type': config['portalType'],
        'name': config['portalName'],
        'tag' : tag
    }
    $.ajax({
        method: 'POST',
        url: 'scripts/requestPermission.php',
        data: data,
        success: function(data, textStatus, jqXhr) {
            if(data['error']) {
                show_message(data['error'], 'error');
            } else {
                if (config['debug'] & 1)
                    console.log(data);
                // now redraw that section of the screen to show permission requested
                document.getElementById(tag).innerHTML = data['block'];
                show_message(data['message'], 'success');
            }
        }
    });
}
