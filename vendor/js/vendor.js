// Main Vendor javascript, also requires base.js, vendor_profile.js,

var change_password = null;
var changePasswordTitleDiv = null;
var purchase_label = 'purchase';
var additional_cost = {};
var switchPortalbtn = null;
exhibitorProfile = null;
si_password = null;

// initial setup
window.onload = function () {
    id = document.getElementById('changePassword');
    if (id != null) {
        change_password = new bootstrap.Modal(id, { focus: true, backdrop: 'static' });
    }

    switchPortalbtn = document.getElementById('switchPortalbtn');
    if (switchPortalbtn != null) {
        switchPortalbtn.innerHTML = 'Switch to ' + (config['portalName'] == 'Artist' ? 'Vendor' : 'Artist') + ' Portal';
    }

    exhibitorProfile = new ExhibitorProfile(config['debug']);
    exhibitorRequestOnLoad();
    auctionItemRegistrationOnLoad()
    vendorInvoiceOnLoad()
    exhibitorReceiptOnLoad();
    if (typeof exhibitor_info !== 'undefined') {
        if (exhibitor_info['needReview']) {
            exhibitorProfile.profileModalOpen('review');
        }
    }

    // login
    pwEyeToggle('si_password');
    // change password
    pwEyeToggle('oldPw');
    pwEyeToggle('newPw');
    pwEyeToggle('newPw2');
    // signup
    pwEyeToggle('pw1');
    pwEyeToggle('pw2');
    pwEyeToggle('cpw1');
    pwEyeToggle('cpw2');

}

// execute the change password request
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
    var param = $('#changepw').serialize();
    if (typeof pwtype === 'undefined') {
        param += '&pwType=' + config['loginType'];
    } else {
        param += '&pwType=' + pwtype;
    }
    console.log(param);
    $.ajax({
        url: 'scripts/changePassword.php',
        data: param,
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

// request a reset password link via email
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
    window.location = "/switchPortal.php?site=" + encodeURIComponent(config['portalName'] == 'Artist' ? config['vendorsite'] : config['artistsite']);
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
