// Main Vendor javascript, also requires base.js, vendor_profile.js,

var passwordLine1 = null;
var passwordLine2 = null;
var creatingAccountMsgDiv = null;
var vendor_request = null;
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

// openReq - update the modal for this space
function openReq(spaceid, cancel) {
    //console.log("open request modal for id =" + spaceid);
    var space = vendor_spaces[spaceid];
    if (!space)
        return;
    //console.log(space);

    // build option list
    var options = "<option value='-1'>" + (cancel ? 'Cancel' : 'No') + " Space Requested</option>\n";
    var prices = space.prices;
    var price_keys = Object.keys(prices).sort();
    for (var priceid in price_keys) {
        var price = prices[price_keys[priceid]];
        if (price.requestable == 1)
            options += "<option value='" + price.id + "'>" + price.description + ' for ' + Number(price.price).toFixed(2) + "</option>\n";
    }

    // update fields
    document.getElementById("vendor_req_title").innerHTML = "<strong>" + (cancel ? 'Change/Cancel ' : '') + space.name + ' Space Request</strong>';
    document.getElementById("vendor_req_btn").innerHTML = (cancel ? "Change/Cancel " : "Request ") + space.name + ' Space';
    var selection = document.getElementById('vendor_req_price_id');
    selection.innerHTML = options;
    if (cancel) selection.value = cancel;
    document.getElementById('vendor_req_btn').setAttribute('onClick', "spaceReq(" + space.id + ',' + cancel + ')');
    vendor_request.show();
}

// Space Request - call scripts/spaceRequest.php to add a request record
function spaceReq(spaceId, cancel) {
    //console.log("spaceReq called for " + spaceId);

    var opt = document.getElementById('vendor_req_price_id');
    //console.log(opt);
    //console.log(opt.value);
    if (opt.value <= 0 && !cancel) {
        alert("Select an amount of space to resquest");
        return;
    }
    dataobj = {
        spaceid: spaceId,
        priceid: opt.value,
    };
    $.ajax({
        url: 'scripts/spaceReq.php',
        data: dataobj,
        method: 'POST',
        success: function (data, textstatus, jqxhr) {
            if (config['debug'] & 1)
                console.log(data);
            if (data['error'] !== undefined) {
                show_message(data['error'], 'error');
                return;
            }
            if (data['success'] !== undefined) {
                show_message(data['success'], 'success');
                vendor_request.hide();
                document.getElementById(data['div']).innerHTML = "<div class='col-sm-auto'><button class='btn btn-primary' onClick='location.reload()'>Click here to refresh page to update status</button></div>";
            }
            if (data['warn'] !== undefined) {
                show_message(data['warn'], 'warn');
            }
        },
        error: showAjaxError
    })
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
    id = document.getElementById('vendor_req');
    if (id != null) {
        vendor_request = new bootstrap.Modal(id, { focus: true, backdrop: 'static' });
    }

    id = document.getElementById('changePassword');
    if (id != null) {
        change_password = new bootstrap.Modal(id, { focus: true, backdrop: 'static' });
    }

    switchPortalbtn = document.getElementById('switchPortalbtn');
    if (switchPortalbtn != null) {
        switchPortalbtn.innerHTML = 'Switch to ' + (config['portalName'] == 'Artist' ? 'Vendor' : 'Artist') + ' Portal';
    }

    vendorProfileOnLoad();
    vendorInvoiceOnLoad();
    //console.log(vendor_spaces);
}
