// Main Vendor javascript, also requires base.js, vendor_profile.js,

var passwordLine1 = null;
var passwordLine2 = null;
var creatingAccountMsgDiv = null;
var change_password = null;
var changePasswordTitleDiv = null;
var purchase_label = 'purchase';
var additional_cost = {};

function changePassword(field) {
    var pw = document.getElementById('newPw').value;
    if (pw.length < 8) {
        show_message("New is too short.  It must be at least 8 characters.", 'warn', 'cp_result_message');
        return;
    }
    if (document.getElementById('newPw2').value != pw) {
        show_message("New passwords do not match", 'warn', 'cp_result_message');
        return;
    }
    clear_message('cp_result_message');
    $.ajax({
        url: 'scripts/changePassword.php',
        data: $('#changepw').serialize(),
        method: 'POST',
        success: function(data, textstatus, jqXHR) {
            if(data['status'] == 'error') {
                show_message(data['message'], 'error', 'cp_result_message');
            } else {
                if (config['debug'] & 1)
                    console.log(data);
                location.reload();
            }
        }
    });
}

function resetPassword() {
    var email = prompt('What is your login email?');
    $.ajax({
        method: 'POST',
        url: 'scripts/resetPassword.php',
        data: {'login' : email, 'type': config['portalType'], 'name': config['portalName']},
        success: function(data, textStatus, jqXhr) {
            if(data['error']) {
                show_message(data['error'], 'error');
            } else {
                if (config['debug'] & 1)
                    console.log(data);
                show_message(data['message'], 'success');
            }
        }
    });
}

// open the change password modal changing the appropriate fields
function changePasswordOpen() {
    if (changePasswordTitleDiv == null)
        changePasswordTitleDiv = document.getElementById('changePasswordTitle');

    if (config['loginType'] == 'c')
        changePasswordTitleDiv.innerHTML = "Change " + config['portalName'] + " Portal Contact Password";
    else
        changePasswordTitleDiv.innerHTML = "Change " + config['portalName'] + " Portal Account Password";

    change_password.show();
}

// change to the other portal
function switchPortal() {
    window.location = config['portalName'] == 'Artist' ? config['vendorsite'] : config['artistsite'];
}

window.onload = function () {
    id = document.getElementById('changePassword');
    if (id != null) {
        change_password = new bootstrap.Modal(id, { focus: true, backdrop: 'static' });
    }

    switchPortalbtn = document.getElementById('switchPortalbtn');
    if (switchPortalbtn != null) {
        switchPortalbtn.innerHTML = 'Switch to ' + (config['portalName'] == 'Artist' ? 'Vendor' : 'Artist') + ' Portal';
    }

    vendorProfileOnLoad();
    vendorRequestOnLoad();
    vendorInvoiceOnLoad();
    //console.log(vendor_spaces);
}
