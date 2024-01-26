var profileModal = null;
var profileMode = "unknown";
var profileUseType = "unknown";
var switchPortalbtn = null;
var passwordLine1 = null;
var passwordLine2 = null;
var profileIntroDiv = null;
var profileSubmitBtn = null;
var profileModalTitle = null;
var creatingAccountMsgDiv = null;
var vendor_request = null;
var vendor_invoice = null;
var change_password = null;
var changePasswordTitleDiv = null;
var purchase_label = 'purchase';
var additional_cost = {};

const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
const fieldlist = ["exhibitorName", "exhibitorEmail", "exhibitorPhone", "description", "contactName", "contactEmail", "contactPhone", "pw1", "pw2",
    "addr", "city", "state", "zip", "country", "shipCompany", "shipAddr", "shipCity", "shipState", "shipZip", "shipCountry"];
const copyFromFieldList = [ 'exhibitorName', 'addr', 'addr2', 'city', 'state', 'zip', 'country'];
const copyToFieldList = ['shipCompany', 'shipAddr', 'shipAddr2', 'shipCity', 'shipState', 'shipZip', 'shipCountry'];
//  copy the address fields to the ship to address fields
function copyAddressToShipTo() {
    for (var fieldnum in copyFromFieldList) {
        document.getElementById(copyToFieldList[fieldnum]).value = document.getElementById(copyFromFieldList[fieldnum]).value;
    }
}

// submit the profile or both register and update, which type is in profileMode, set by the modal open
function submitProfile(dataType) {
    // replace validator with direct validation as it doesn't work well with bootstrap
    var valid = true;
    var m2= '';

    for (var fieldnum in fieldlist) {
        var field = document.getElementById(fieldlist[fieldnum]);
        switch (fieldlist[fieldnum]) {
            case 'exhibitorEmail':
            case 'contactEmail':
                if (emailRegex.test(field.value)) {
                    field.style.backgroundColor = '';
                } else {
                    field.style.backgroundColor = 'var(--bs-warning)';
                    valid = false;
                }
                break;
            case 'pw1':
                if (profileUseType != 'register')
                    break;
                var field2 = document.getElementById("pw2");
                if (field.value == field2.value && field.value.length >= 8) {
                    field.style.backgroundColor = '';
                } else {
                    field.style.backgroundColor = 'var(--bs-warning)';
                    valid = false;
                }
                break;
            case 'pw2':
                if (profileUseType != 'register')
                    break;
                var field2 = document.getElementById("pw1");
                if (field.value == field2.value && field.value.length >= 8) {
                    field.style.backgroundColor = '';
                } else {
                    field.style.backgroundColor = 'var(--bs-warning)';
                    valid = false;
                }
                break;
            case 'description':
                var value = tinyMCE.activeEditor.getContent();
                if (value == null) {
                    value = false;
                    m2 = " and the description field which also is required.";
                } else if (value.trim() == '') {
                    value = false;
                    m2 = " and the description field which also is required.";
                }
                break;

            default:
                if (dataType == 'artist' && fieldlist[fieldnum].substring(0, 3) == 'ship') {
                    if (config['debug' & 16])
                        console.log("skipping " + fieldlist[fieldnum]);
                    break;
                }
                if (field.value.length > 1) {
                    field.style.backgroundColor = '';
                } else {
                    field.style.backgroundColor = 'var(--bs-warning)';
                    valid = false;
                }
        }
    }

    if (!valid) {
        show_message("Fill in required missing fields highlighted in this color" + m2, "warn", 'au_result_message');
        return null;
    }
    clear_message('au_result_message');
    tinyMCE.triggerSave();

    //
    $.ajax({
        url: 'scripts/vendorAddUpdate.php',
        data: $('#exhibitorProfileForm').serialize(),
        method: 'POST',
        success: function(data, textstatus, jqXHR) {
            if(data['status'] == 'error') {
                show_message(data['message'], 'error', 'au_result_message');
            } else {
                profileModalClose();
                if (profileUseType == 'register')
                    show_message("Thank you for registering for an account with the " + config['label'] + ' ' + config['portalName'] + " portal.  Please log in using your contact email address and password." + "<br/" + data['message]']);
                else
                    show_message(data['message'], 'success')
                if (data['info']) {
                    if (config['debug'] & 7) {
                        console.log("before update of vendor_info");
                        console.log(vendor_info);
                    }
                    vendor_info = data['info'];
                    if (config['debug'] & 7) {
                        console.log("after update of vendor_info");
                        console.log(vendor_info);
                    }
                    if (config['debug'] & 1)
                        console.log(data);
                }
            }
        },
        error: showAjaxError
    });
}

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
    clear_message();
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
function openInvoice(spaceId, sortorder) {
    console.log("spaceid: " + spaceId + ", sortorder: " + sortorder);
    var space = vendor_spaces[spaceId];
    var price = space.prices[sortorder];
    console.log(space);
    console.log(price);

    // fill in the variable items
    document.getElementById("vendor_invoice_title").innerHTML = "<strong>Pay " + space.name + ' Invoice</strong>';
    document.getElementById('vendor_inv_approved_for').innerHTML = vendor_info.name + " you are approved for " + price.description;
    var spaces = price.includedMemberships + price.additionalMemberships;
    var html = "<p>This space comes with " +
        (price.includedMemberships > 0 ? price.includedMemberships : "no") +
        " memberships included and " +
        (price.additionalMemberships > 0 ? "the " : "no ") + "right to purchase " +
        (price.additionalMemberships > 0 ? "up to " +  price.additionalMemberships  : "no") +
        " additional memberships at a reduced rate of $" + Number(space.additionalMemPrice).toFixed(2) + ".</p>";
    if (spaces > 0) {
        html += "<p>All vendors must have a membership for everyone working in their space. Included and additional discounted memberships can only be purchased while paying for your space. " +
            "If you do not purchase them now while paying your space invoice, you will have to purchase them at the current membership rates.</p>" +
            "<p>If you are unsure who will be using the registrations please use the first name of ‘Provided’ and a last name of ‘At Con’. The on-site registration desk will update the membership to the name on their ID.</p>" +
            "<p>Program participants do not need to buy memberships; however, we will confirm that they meet the requirements to waive the membership cost.  If they do not, they will need to purchase a membership on-site at the on-site rates.</p>" +
            "<p><input type='checkbox' style='transform: scale(2);' name='agreeNone' id='add-new-comment'agreeNone'> &nbsp;&nbsp;If you do not wish to purchase any memberships at this time, check this box to acknowledge the requirement for memberships above.</p>"
    }
    document.getElementById('vendor_inv_included').innerHTML = html;
    document.getElementById('dealer_space_cost').innerHTML = Number(price.price).toFixed(2);
    document.getElementById('vendor_inv_cost').innerHTML = Number(price.price).toFixed(2);
    document.getElementById('vendor_inv_item_id').value = price.id

    var html = '';
    // now build the included memberships
    if (price.includedMemberships > 0) {
        html = "<input type='hidden' name='incl_mem_count' value='" + price.includedMemberships + "'>\n" +
            "<div class='row'><div class='col-sm-auto p-2 pe-0'><strong>Included Memberships: (up to " + price.includedMemberships + ")</strong></div></div>";
        for (var mnum = 0; mnum < price.includedMemberships; mnum++) {
            // name fields
            html += `
<div class="row mt-4">
    <div class="col-sm-auto p-0">Included Member ` + (mnum + 1) + `:</div>
</div>
<div class="row">
    <div class="col-sm-auto ms-0 me-2 p-0">
        <label for="fname_i_` + mnum + `" class="form-label-sm"><span class="text-dark" style="font-size: 10pt;"><span class='text-info'>*</span>First Name</span></label><br/>
        <input class="form-control-sm" type="text" name="fname_i_` + mnum + `" id="fname_i_` + mnum + `" size="22" maxlength="32"/>
    </div>
    <div class="col-sm-auto ms-0 me-2 p-0">
        <label for="mname_i_` + mnum + `" class="form-label-sm"><span class="text-dark" style="font-size: 10pt;">Middle Name</span></label><br/>
        <input class="form-control-sm" type="text" name="mname_i_` + mnum + `" id="mname_i_` + mnum + `" size="8" maxlength="32" />
    </div>
    <div class="col-sm-auto ms-0 me-2 p-0">
        <label for="lname_i_` + mnum + `" class="form-label-sm"><span class="text-dark" style="font-size: 10pt;"><span class='text-info'>*</span>Last Name</span></label><br/>
        <input class="form-control-sm" type="text" name="lname_i_` + mnum + `" id="lname_i_` + mnum + `" size="22" maxlength="32" />
    </div>
    <div class="col-sm-auto ms-0 me-0 p-0">
        <label for="suffix_i_` + mnum + `" class="form-label-sm"><span class="text-dark" style="font-size: 10pt;">Suffix</span></label><br/>
        <input class="form-control-sm" type="text" name="suffix_i_` + mnum + `" id='suffix_i_` + mnum + `' size="4" maxlength="4" />
    </div>
</div>
`;
            // address fields
            html += `
<div class="row">
    <div class="col-sm-12 ms-0 me-0 p-0">
        <label for="addr_i_` + mnum + `" class="form-label-sm"><span class="text-dark" style="font-size: 10pt;"><span class='text-info'>*</span>Address</span></label><br/>
        <input class="form-control-sm" type="text" name='addr_i_` + mnum + `' id='addr_i_` + mnum + `' size=64 maxlength="64" />
    </div>
</div>
<div class="row">
    <div class="col-sm-12 ms-0 me-0 p-0">
        <label for="addr2_i_` + mnum + `" class="form-label-sm"><span class="text-dark" style="font-size: 10pt;">Company/2nd Address line</span></label><br/>
        <input class="form-control-sm" type="text" name='addr2_i_` + mnum + `' id='addr2_i_` + mnum + `' size=64 maxlength="64" '/>
    </div>
</div>
<div class="row">
    <div class="col-sm-auto ms-0 me-2 p-0">
        <label for="city_i_` + mnum + `" class="form-label-sm"><span class="text-dark" style="font-size: 10pt;"><span class='text-info'>*</span>City</span></label><br/>
        <input class="form-control-sm" type="text" name="city_i_` + mnum + `" id='city_i_` + mnum + `' size="22" maxlength="32" />
    </div>
    <div class="col-sm-auto ms-0 me-2 p-0">
        <label for="state_i_` + mnum + `" class="form-label-sm"><span class="text-dark" style="font-size: 10pt;"><span class='text-info'>*</span>State</span></label><br/>
        <input class="form-control-sm" type="text" name="state_i_` + mnum + `" id='state_i_` + mnum + `' size="2" maxlength="2" />
    </div>
    <div class="col-sm-auto ms-0 me-2 p-0">
        <label for="zip_i_` + mnum + `" class="form-label-sm"><span class="text-dark" style="font-size: 10pt;"><span class='text-info'>*</span>Zip</span></label><br/>
        <input class="form-control-sm" type="text" name="zip_i_` + mnum + `" id='zip_i_` + mnum + `' size="5" maxlength="10" />
    </div>
    <div class="col-sm-auto ms-0 me-0 p-0">
        <label for="country_i_` + mnum + `" class="form-label-sm"><span class="text-dark" style="font-size: 10pt;">Country</span></label><br/>
        <select class="form-control-sm" name="country_i_` + mnum + `" id='country_i_` + mnum + `' >
` + country_options + `
        </select>
    </div>
</div>
<div class="row">
    <div class="col-sm-auto ms-0 me-2 p-0">
        <label for="email_i_` + mnum + `" class="form-label-sm"><span class="text-dark" style="font-size: 10pt;"><span class='text-info'>*</span>Email</span></label><br/>
        <input class="form-control-sm" type="email" name="email_i_` + mnum + `" id='email_i_` + mnum + `' size="35" maxlength="64" />
    </div>
    <div class="col-sm-auto ms-0 me-2 p-0">
        <label for="phone_i_` + mnum + `" class="form-label-sm"><span class="text-dark" style="font-size: 10pt;">Phone</span></label><br/>
        <input class="form-control-sm" type="text" name="phone_i_` + mnum + `" id='phone_i_` + mnum + `' size="18" maxlength="15" />
    </div>
    <div class="col-sm-auto ms-0 p-0">
        <label for="badgename_i_` + mnum + `" class="form-label-sm"><span class="text-dark" style="font-size: 10pt;">Badge Name (optional)</span></label><br/>
        <input class="form-control-sm" type="text" name="badgename_i_` + mnum + `" id='badgename_i_` + mnum + `' size="35" maxlength="32"  placeholder='defaults to first and last name'/>
    </div>
</div>
`;
        }
    }
    html += "<hr/>";

        // now build the additional memberships
    if (price.additionalMemberships > 0) {
        html += "<input type='hidden' name='addl_mem_count' value='" + price.additionalMemberships + "'>\n" +
            "<div class='row'><div class='col-sm-auto p-2 pe-0'><strong>Additional Memberships: (up to " + price.additionalMemberships + ")</strong></div></div>";
        for (var mnum = 0; mnum < price.additionalMemberships; mnum++) {
            // name fields
            html += `
<div class="row mt-4">
    <div class="col-sm-auto p-0">Additional Member ` + (mnum + 1) + `:</div>
</div>
<div class="row">
    <div class="col-sm-auto ms-0 me-2 p-0">
        <label for="fname_a_` + mnum + `" class="form-label-sm"><span class="text-dark" style="font-size: 10pt;"><span class='text-info'>*</span>First Name</span></label><br/>
        <input class="form-control-sm" type="text" name="fname_a_` + mnum + `" id="fname_a_` + mnum + `" size="22" maxlength="32" onchange="updateCost(` + spaceId + ",'" + sortorder + "'," + mnum + `)"/>
    </div>
    <div class="col-sm-auto ms-0 me-2 p-0">
        <label for="mname_a_` + mnum + `" class="form-label-sm"><span class="text-dark" style="font-size: 10pt;">Middle Name</span></label><br/>
        <input class="form-control-sm" type="text" name="mname_a_` + mnum + `" id="mname_a_` + mnum + `" size="8" maxlength="32" />
    </div>
    <div class="col-sm-auto ms-0 me-2 p-0">
        <label for="lname_a_` + mnum + `" class="form-label-sm"><span class="text-dark" style="font-size: 10pt;"><span class='text-info'>*</span>Last Name</span></label><br/>
        <input class="form-control-sm" type="text" name="lname_a_` + mnum + `" id="lname_a_` + mnum + `" size="22" maxlength="32" />
    </div>
    <div class="col-sm-auto ms-0 me-0 p-0">
        <label for="suffix_a_` + mnum + `" class="form-label-sm"><span class="text-dark" style="font-size: 10pt;">Suffix</span></label><br/>
        <input class="form-control-sm" type="text" name="suffix_a_` + mnum + `" id='suffix_a_` + mnum + `' size="4" maxlength="4" />
    </div>
</div>
`;
            // address fields
            html += `
<div class="row">
    <div class="col-sm-12 ms-0 me-0 p-0">
        <label for="addr_a_` + mnum + `" class="form-label-sm"><span class="text-dark" style="font-size: 10pt;"><span class='text-info'>*</span>Address</span></label><br/>
        <input class="form-control-sm" type="text" name='addr_a_` + mnum + `' id='addr_a_` + mnum + `' size=64 maxlength="64" />
    </div>
</div>
<div class="row">
    <div class="col-sm-12 ms-0 me-0 p-0">
        <label for="addr2_a_` + mnum + `" class="form-label-sm"><span class="text-dark" style="font-size: 10pt;">Company/2nd Address line</span></label><br/>
        <input class="form-control-sm" type="text" name='addr2_a_` + mnum + `' id='addr2_a_` + mnum + `' size=64 maxlength="64" '/>
    </div>
</div>
<div class="row">
    <div class="col-sm-auto ms-0 me-2 p-0">
        <label for="city_a_` + mnum + `" class="form-label-sm"><span class="text-dark" style="font-size: 10pt;"><span class='text-info'>*</span>City</span></label><br/>
        <input class="form-control-sm" type="text" name="city_a_` + mnum + `" id='city_a_` + mnum + `' size="22" maxlength="32" />
    </div>   
    <div class="col-sm-auto ms-0 me-2 p-0">
        <label for="state_a_` + mnum + `" class="form-label-sm"><span class="text-dark" style="font-size: 10pt;"><span class='text-info'>*</span>State</span></label><br/>
        <input class="form-control-sm" type="text" name="state_a_` + mnum + `" id='state_a_` + mnum + `' size="2" maxlength="2" />
    </div>
    <div class="col-sm-auto ms-0 me-2 p-0">
        <label for="zip_a_` + mnum + `" class="form-label-sm"><span class="text-dark" style="font-size: 10pt;"><span class='text-info'>*</span>Zip</span></label><br/>
        <input class="form-control-sm" type="text" name="zip_a_` + mnum + `" id='zip_a_` + mnum + `' size="5" maxlength="10" />
    </div>
    <div class="col-sm-auto ms-0 me-0 p-0">
        <label for="country_a_` + mnum + `" class="form-label-sm"><span class="text-dark" style="font-size: 10pt;">Country</span></label><br/>
        <select class="form-control-sm" name="country_a_` + mnum + `" id='country_a_` + mnum + `' >
` + country_options + `
        </select>
    </div>
</div>
<div class="row">
    <div class="col-sm-auto ms-0 me-2 p-0">
        <label for="email_a_` + mnum + `" class="form-label-sm"><span class="text-dark" style="font-size: 10pt;"><span class='text-info'>*</span>Email</span></label><br/>
        <input class="form-control-sm" type="email" name="email_a_` + mnum + `" id='email_a_` + mnum + `' size="35" maxlength="64" />
    </div>
    <div class="col-sm-auto ms-0 me-2 p-0">
        <label for="phone_a_` + mnum + `" class="form-label-sm"><span class="text-dark" style="font-size: 10pt;">Phone</span></label><br/>
        <input class="form-control-sm" type="text" name="phone_a_` + mnum + `" id='phone_a_` + mnum + `' size="18" maxlength="15" />
    </div>
    <div class="col-sm-auto ms-0 p-0">
        <label for="badgename_a_` + mnum + `" class="form-label-sm"><span class="text-dark" style="font-size: 10pt;">Badge Name (optional)</span></label><br/>
        <input class="form-control-sm" type="text" name="badgename_a_` + mnum + `" id='badgename_a_` + mnum + `' size="35" maxlength="32"  placeholder='defaults to first and last name'/>
    </div>
</div>
`;
        }
    }
    html += "<hr/>";
    document.getElementById("vendor_inv_included_mbr").innerHTML = html;
    vendor_invoice.show();
}

// update Cost for Memberships and total Cost when an additional member is started
function updateCost(spaceId, sortorder, item) {
    var space = vendor_spaces[spaceId];
    var price = space.prices[sortorder];
    var fname = document.getElementById('fname_a_' + item).value;
    var cost = 0;
    additional_cost[item] = fname == '' ? 0 : Number(space.additionalMemPrice);
    for (var num in additional_cost) {
        cost += additional_cost[num];
    }
    console.log(cost);
    document.getElementById('vendor_inv_mbr_cost').innerHTML = Number(cost).toFixed(2);
    cost += Number(price.price);
    console.log(cost);
    document.getElementById('vendor_inv_cost').innerHTML = Number(cost).toFixed(2);
}

function makePurchase(token, label) {
    if (label && label != '') {
        purchase_label = label;
    }
    if (!token)
        token = 'test';

    if (token == 'test_ccnum') {  // this is the test form
        token = document.getElementById(token).value;
    }

    var submitId = document.getElementById(purchase_label);
    submitId.disabled = true;
    var formData = $('#vendor_invoice_form').serialize()
    formData += "&nonce=" + token;
    $.ajax({
        url: 'scripts/spacePayment.php',
        method: 'POST',
        data: formData,
        success: function(data, textStatus, jqXhr) {
            if (config['debug'] & 1)
                console.log(data);
            if(data['error']) {
                alert(data['error']);
                var submitId = document.getElementById(purchase_label);
                submitId.disabled = false;
            } else if (data['status'] == 'success') {
                //alert('call succeeded');
                alert(data['message']);
                alert("Welcome to " + config['label'] + " Exhibitor Space. You may contact " + config['vemail'] + " with any questions.  One of our coordinators will be in touch to help you get setup.");
                location.reload();
            } else {
                alert('There was an unexpected error, please email ' + config['vemail'] + 'to let us know.  Thank you.');
                var submitId = document.getElementById(purchase_label);
                submitId.disabled = false;
            }
        }
    });
}

function profileModalOpen(useType) {
    if (profileModal != null) {
        // set items as registration use of the modal
        if (profileIntroDiv == null) {
            profileIntroDiv = document.getElementById("profileIntro");
            passwordLine1 = document.getElementById("passwordLine1");
            passwordLine2 = document.getElementById("passwordLine2");
            profileMode = document.getElementById('profileMode');
            profileSubmitBtn = document.getElementById('profileSubmitBtn');
            profileModalTitle = document.getElementById('modalTitle');
            creatingAccountMsgDiv = document.getElementById('creatingAccountMsg');
        }
        if (useType == 'register') {
            profileIntroDiv.innerHTML = '<p>This form creates an account on the ' + config['conName'] + ' ' + config['portalName'] + ' Portal.</p>';
            profileSubmitBtn.innerHTML = 'Register ' + config['portalName'];
            profileModalTitle.innerHTML = "New " + config['portalName'] + ' Registration;'
            creatingAccountMsgDiv.hidden = false;
        } else { // update
            profileIntroDiv.innerHTML = '<p>This form updates your account on the ' + config['conName'] + ' ' + config['portalName'] + ' Portal.</p>';
            profileSubmitBtn.innerHTML = 'Update ' + config['portalName'] + ' Profile';
            profileModalTitle.innerHTML = "Update " + config['portalName'] + ' Profile';
            creatingAccountMsgDiv.hidden = true;
            var keys = Object.keys(vendor_info);
            for (var keyindex in keys) {
                var key = keys[keyindex];
                if (key == 'eNeedNew' || key == 'cNeedNew' || key == 'eConfirm' || key == 'cConfirm')
                    continue;

                var value=vendor_info[key];
                if (config['debug'] & 16)
                    console.log(key + ' = "' + value + '"');
                var id = document.getElementById(key);
                if (id) {
                    if (key != 'publicity')
                        id.value = value;
                    else
                        id.checked = value == 1;
                } else  if (config['debug'] & 16)
                    console.log("field not found " + key);
            }
        }
        profileMode.value = useType;
        profileUseType = useType;
        passwordLine1.hidden = useType != 'register';
        passwordLine2.hidden = useType != 'register';
        profileModal.show();
        tinyMCE.init({
            selector: 'textarea#description',
            height: 400,
            min_height: 400,
            menubar: false,
            plugins: 'advlist lists image link charmap fullscreen help nonbreaking preview searchreplace',
            toolbar: [
                'help undo redo searchreplace copy cut paste pastetext | fontsizeinput styles h1 h2 h3 h4 h5 h6 | ' +
                'bold italic underline strikethrough removeformat | ' +
                'visualchars nonbreaking charmap hr | ' +
                'preview fullscreen ',
                'alignleft aligncenter alignright alignnone | outdent indent | numlist bullist checklist | forecolor backcolor | link image'
            ],
            content_style: 'body {font - family:Helvetica,Arial,sans-serif; font-size:14px }',
            placeholder: 'Edit the description here...',
            auto_focus: 'reg-description'
        });
    }
}

function profileModalClose() {
    if (profileModal != null) {
        profileModal.hide();
    }
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

// change to the other portl
function switchPortal() {
    window.location = config['portalName'] == 'Artist' ? config['vendorsite'] : config['artistsite'];
}

window.onload = function () {
    var id = document.getElementById('profile');
    if (id != null) {
        profileModal = new bootstrap.Modal(id, { focus: true, backdrop: 'static' });
    }

    id = document.getElementById('vendor_req');
    if (id != null) {
        vendor_request = new bootstrap.Modal(id, { focus: true, backdrop: 'static' });
    }

    id = document.getElementById('vendor_invoice');
    if (id != null) {
        vendor_invoice = new bootstrap.Modal(id, { focus: true, backdrop: 'static' });
    }

    id = document.getElementById('changePassword');
    if (id != null) {
        change_password = new bootstrap.Modal(id, { focus: true, backdrop: 'static' });
    }

    switchPortalbtn = document.getElementById('switchPortalbtn');
    if (switchPortalbtn != null) {
        switchPortalbtn.innerHTML = 'Switch to ' + (config['portalName'] == 'Artist' ? 'Vendor' : 'Artist') + ' Portal';
    }
    //console.log(vendor_spaces);
}

function clear_message(div='result_message') {
    show_message('', '', div);
}

// show_message:
// apply colors to the message div and place the text in the div, first clearing any existing class colors
// type:
//  error: (white on red) bg-danger
//  warn: (black on yellow-orange) bg-warning
//  success: (white on green) bg-success
function show_message(message, type = 'success', div='result_message') {
    var message_div = document.getElementById(div);

    if (message_div.classList.contains('bg-danger')) {
        message_div.classList.remove('bg-danger');
    }
    if (message_div.classList.contains('bg-success')) {
        message_div.classList.remove('bg-success');
    }
    if (message_div.classList.contains('bg-warning')) {
        message_div.classList.remove('bg-warning');
    }
    if (message_div.classList.contains('text-white')) {
        message_div.classList.remove('text-white');
    }
    if (message === undefined || message === '') {
        message_div.innerHTML = '';
        return;
    }
    if (type === 'error') {
        message_div.classList.add('bg-danger');
        message_div.classList.add('text-white');
    }
    if (type === 'success') {
        message_div.classList.add('bg-success');
        message_div.classList.add('text-white');
    }
    if (type === 'warn') {
        message_div.classList.add('bg-warning');
    }
    message_div.innerHTML = message;
}

function showAjaxError(jqXHR, textStatus, errorThrown) {
    'use strict';
    var message = '';
    if (jqXHR && jqXHR.responseText) {
        message = jqXHR.responseText;
    } else {
        message = 'An error occurred on the server.';
    }
    if (textStatus != '' && textStatus != 'error')
        message += '<BR/>' + textStatus;
    message += '<BR/>Error Thrown: ' + errorThrown;
    show_message(message, 'error');
}
