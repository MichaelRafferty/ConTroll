// Items related to building and paying the vendor invoice
var vendor_invoice = null;
var totalSpacePrice = 0;
var regionYearId = null;
var membershipCostdiv = null;

// set up vendor invoice items
function vendorInvoiceOnLoad() {
    id = document.getElementById('vendor_invoice');
    if (id != null) {
        vendor_invoice = new bootstrap.Modal(id, { focus: true, backdrop: 'static' });
    }
    membershipCostdiv = document.getElementById("membershipCost");
}

// openInvoice: display the vendor invoice (and registration items)
function openInvoice(id) {
    var regionName = '';
    var includedMemberships = 0;
    var additionalMemberships = 0;
    var html = '';
    var priceIdx = 0;

    regionYearId = id;
    totalSpacePrice = 0;

    if (config['debug'] & 1)
        console.log("regionYearId: " + regionYearId);
    var region = exhibits_spaces[regionYearId];

    var regionList = region_list[regionYearId];
    var portalName = 'Exhibitor';
    var attendeeName = 'Exhibitor';
    var attendeeNameLC = 'Exhibitors';
    var portalType = regionList.portalType
    switch (portalType) {
        case 'artist':
            portalName = 'Artist';
            attendeeName = 'Artist';
            attendeeNameLC = 'artist';
            break;
        case 'vendor':
            portalName = 'Vendor';
            attendeeName = 'Vendor';
            attendeeNameLC = 'vendor';
            break;
    }
    var mailin = exhibitor_info['mailin'];
    if (config['debug'] & 1) {
        console.log("regionList");
        console.log(regionList);
        console.log("Region Spaces");
        console.log(region);
    }

    regionName = regionList.name;

    // refresh the items spaces purchased area
    html = "You are approved for:<br/>\n";
    var exSpaceKeys = Object.keys(exhibitor_spacelist);
    for (var exSpaceIdx in exSpaceKeys) {
        if (region[exSpaceKeys[exSpaceIdx]]) { // space is in our region
            var space = exhibitor_spacelist[exSpaceKeys[exSpaceIdx]];
            var prices = region[exSpaceKeys[exSpaceIdx]].prices;
            if (space.item_approved) {
                html += space.approved_description + " in " + regionName + " for $" + Number(space.approved_price).toFixed(2) + "<br/>";
                totalSpacePrice += Number(space.approved_price);
                // find price item in prices
                for (priceIdx = 0; priceIdx < prices.length; priceIdx++) {
                    if (prices[priceIdx].id == space.item_approved)
                        break;
                }
                includedMemberships = Math.max(includedMemberships, prices[priceIdx].includedMemberships);
                additionalMemberships = Math.max(additionalMemberships, prices[priceIdx].additionalMemberships);
            }
        }
    }
    if (regionList['mailinFee'] > 0 && exhibitor_info['mailin'] == 'Y') {
        html += "Mail in fee of $" + Number(regionList['mailinFee']).toFixed(2) + "<br/>\n";
            totalSpacePrice += Number(regionList['mailinFee']);
    }
    html += "____________________________<br/>\nTotal price for spaces $" + Number(totalSpacePrice).toFixed(2)+ "<br/>\n";

    document.getElementById('vendor_inv_approved_for').innerHTML = html;

    // fill in the variable items
    document.getElementById("vendor_invoice_title").innerHTML = "<strong>Pay " + regionName + ' Invoice</strong>';

    var spaces = includedMemberships + additionalMemberships;
    html = ''; // Clear it in case spaces = 0
    if (spaces > 0) {
        html = "<p>This space comes with " +
            (includedMemberships > 0 ? includedMemberships : "no") +
            " memberships included and " +
            (additionalMemberships > 0 ? "the " : "no ") + "right to purchase " +
            (additionalMemberships > 0 ? "up to " + additionalMemberships : "no") +
            " additional memberships at a reduced rate of $" + Number(regionList.additionalMemPrice).toFixed(2) + ".</p>";
    }
    if((includedMemberships == 0) && (additionalMemberships ==0)) {
        html += "<input type='hidden' name='agreeNone' value='on'></input>"
    }
    if (spaces > 0) {
        switch (portalType) {
            case 'artist':
                if (mailin == 'N') {
                    html += "<p>All non mail-in artists must have a membership. Included and additional discounted memberships can only be purchased while paying for your space.";
                } else {
                    html += "<p>Mail-in artists do not need a membership. Included and additional discounted memberships, however, can only be purchased while paying for your space.";
                }
                break;
            case 'vendor':
                html += "<p>All vendors must have a membership. Additional discounted memberships can be purchased while paying for your space.";
                break;
            default:
                html += "<p>All exhibitors must have a membership. Included and additional discounted memberships can be purchased while paying for your space.";
        }

        html += " If you do not purchase them now while paying your space invoice, you can purchase them at https://reg.boskone.org.</p>" +
            "<p>If you are unsure who will be using the registrations please wait and make the purchase using https://reg.boskone.org. The $50 rate will always be available. (Waiting until you know the name will make Boskone Atcon registration and badge pickup faster.) " +
            "<p>Dealers and/or Artists that are also Program participants do not need to buy memberships; however, we will confirm that they meet Program requirements." +
            "<p><input type='checkbox' style='transform: scale(2);' name='agreeNone' id='agreeNone'> &nbsp;&nbsp;" +
            "If you do not wish to purchase any memberships at this time, check this box to acknowledge the requirement for memberships above.</p>";

        if (portalType == 'artist' && mailin == 'N') {
            html += "<p>In addition, all non-mail-in artists need to declare an on-site agent. " +
                "This is the person that will be contacted if there are any issues with setup, operation, or teardown of your exhibit. " +
                "The agent needs a membership, and you can be the agent.</p>" +
                "<p><input type='radio' name='agent' id='agent_self' value='self' style='transform: scale(1.5);'>&nbsp;&nbsp;&nbsp;I will be my own agent and my membership is not one of the ones below.<br/>" +
                "<input type='radio' name='agent' id='agent_first' value='first' style='transform: scale(1.5);'>&nbsp;&nbsp;&nbsp;The first membership below will be my agent.<br/>";

            var ry = exhibitor_regionyears[regionYearId];
            if (ry['perid']) {
                html += "<input type='radio' name='agent' id='agent_perid' value='p" + ry['perid'] + "' style='transform: scale(1.5);'>&nbsp;&nbsp;&nbsp;Assign " +
                    ry['p_first_name'] + ' ' + ry['p_last_name'] + ' as my agent.<br/>';
            } else if (ry['newid']) {
                html += "<input type='radio' name='agent' id='agent_newid' value='n" + ry['newid'] + "' style='transform: scale(1.5);'>&nbsp;&nbsp;&nbsp;Assign " +
                    ry['n_first_name'] + ' ' + ry['n_last_name'] + ' as my agent.<br/>';
            } else if (exhibitor_info['perid']) {
                html += "<input type='radio' name='agent' id='agent_perid' value='p" + exhibitor_info['perid'] + "' style='transform: scale(1.5);'>&nbsp;&nbsp;&nbsp;Assign " +
                    exhibitor_info['p_first_name'] + ' ' + exhibitor_info['p_last_name'] + ' as my agent.<br/>';
            } else if (exhibitor_info['newid']) {
                html += "<input type='radio' name='agent' id='agent_newid' value='n" + exhibitor_info['newid'] + "' style='transform: scale(1.5);'>&nbsp;&nbsp;&nbsp;Assign " +
                    exhibitor_info['n_first_name'] + ' ' + exhibitor_info['n_last_name'] + ' as my agent.<br/>';
            }
            html += "<input type='radio' name='agent' id='agent_request' value='request' style='transform: scale(1.5);'>&nbsp;&nbsp;&nbsp;Please assign my agent as per my request below.<br/>" +
                "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type='text' name='agent_request' placeholder='Enter your agent request here if needed' size='120'></p>"
        }
    }
    document.getElementById('vendor_inv_included').innerHTML = html;
    document.getElementById('vendor_inv_cost').innerHTML = Number(totalSpacePrice).toFixed(2);
    document.getElementById('vendorSpacePrice').value = totalSpacePrice;
    document.getElementById('vendor_inv_region_id').value = regionYearId;

    membershipCostdiv.hidden =  (includedMemberships == 0 && additionalMemberships == 0) ;

    var html = '';
    // now build the included memberships
    if (includedMemberships > 0) {
        html = "<input type='hidden' name='incl_mem_count' value='" + includedMemberships + "'>\n" +
            "<div class='row'><div class='col-sm-auto p-2 pe-0'><strong>Included Memberships: (up to " + includedMemberships + ")</strong>" +
            "<input type='hidden' name='includedMemberships' value='" + String(includedMemberships) + "'></div></div>";
        for (var mnum = 0; mnum < includedMemberships; mnum++) {
            // name fields including legal name
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
<div class='row'>
    <div class='col-sm-12 ms-0 me-0 p-0'>
        <label for="legalname_i_` + mnum + `" class='form-label-sm'><span class='text-dark' style='font-size: 10pt;'>Legal Name: for checking against your ID. It will only be visible to Registration Staff.</label><br/>
        <input class='form-control-sm' type='text' name="legalname_i_` + mnum + `" id=legalname_i_` + mnum + `" size=64 maxlength='64' placeholder='Defaults to First Name Middle Name Last Name, Suffix'/>
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
        <input class="form-control-sm" type="text" name="state_i_` + mnum + `" id='state_i_` + mnum + `' size="10" maxlength=16" />
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
        <input class="form-control-sm" type="email" name="email_i_` + mnum + `" id='email_i_` + mnum + `' size="35" maxlength="254" />
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
        html += "<hr/>";
    }

    // now build the additional memberships
    if (additionalMemberships > 0) {
        html += "<input type='hidden' name='addl_mem_count' value='" + additionalMemberships + "'>\n" +
            "<div class='row'><div class='col-sm-auto p-2 pe-0'><strong>Additional Memberships: (up to " + additionalMemberships + ")</strong>" +
            "<input type='hidden' name='additionalMemberships' value='" + String(additionalMemberships) + "'></div></div>";
        for (var mnum = 0; mnum < additionalMemberships; mnum++) {
            // name fields includeing legal name
            html += `
<div class="row mt-4">
    <div class="col-sm-auto p-0">Additional Member ` + (mnum + 1) + `:</div>
</div>
<div class="row">
    <div class="col-sm-auto ms-0 me-2 p-0">
        <label for="fname_a_` + mnum + `" class="form-label-sm"><span class="text-dark" style="font-size: 10pt;"><span class='text-info'>*</span>First Name</span></label><br/>
        <input class="form-control-sm" type="text" name="fname_a_` + mnum + `" id="fname_a_` + mnum + `" size="22" maxlength="32" onchange="updateCost(` + regionYearId + "," + mnum + `)"/>
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
<div class='row'>
    <div class='col-sm-12 ms-0 me-0 p-0'>
        <label for="legalname_a_` + mnum + `" class='form-label-sm'><span class='text-dark' style='font-size: 10pt;'>Legal Name: for checking against your ID. It will only be visible to Registration Staff.</label><br/>
        <input class='form-control-sm' type='text' name="legalname_a_` + mnum + `" id=legalname_a_` + mnum + `" size=64 maxlength='64' placeholder='Defaults to First Name Middle Name Last Name, Suffix'/>
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
        <input class="form-control-sm" type="text" name="state_a_` + mnum + `" id='state_a_` + mnum + `' size="10" maxlength="16" />
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
        <input class="form-control-sm" type="email" name="email_a_` + mnum + `" id='email_a_` + mnum + `' size="35" maxlength="254" />
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
        html += "<hr/>";
    }
    document.getElementById("vendor_inv_included_mbr").innerHTML = html;
    vendor_invoice.show();
}

// update invoice for the Cost of Memberships and total Cost when an additional member is started
function updateCost(regionYearId, item) {
    var regionList = region_list[regionYearId];
    var price = Number(regionList.additionalMemPrice);
    var fname = document.getElementById('fname_a_' + item).value;
    var cost = 0;
    additional_cost[item] = fname == '' ? 0 : Number(regionList.additionalMemPrice);
    for (var num in additional_cost) {
        cost += additional_cost[num];
    }
    if (config['debug'] & 1)
        console.log('Pre totalSpacePrice: ' + String(cost));
    document.getElementById('vendor_inv_mbr_cost').innerHTML = Number(cost).toFixed(2);
    cost += Number(totalSpacePrice);
    if (config['debug'] & 1)
        console.log('After adding totalSpacePrice: ' + String(cost));
    document.getElementById('vendor_inv_cost').innerHTML = Number(cost).toFixed(2);
}

// submit the invoice for payment processing
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
            if (data['error']) {
                show_message(data['error'], 'error', 'inv_result_message');
                var submitId = document.getElementById(purchase_label);
                submitId.disabled = false;
            } else if (data['status'] == 'error') {
                show_message(data['data'], 'error', 'inv_result_message');
                var submitId = document.getElementById(purchase_label);
                submitId.disabled = false;
            } else if (data['status'] == 'success') {
                vendor_invoice.hide();
                show_message(data['message'] + "<p>Welcome to " + config['label'] + " Exhibitor Space. You may contact " + config['vemail'] +
                    " with any questions.  One of our coordinators will be in touch to help you get setup.</p>");
                if (data['exhibitor_spacelist']) {
                    exhibitor_spacelist = data['exhibitor_spacelist'];
                }
                updatePaidStatusBlock();
            } else {
                show_message('There was an unexpected error, please email ' + config['vemail'] + ' to let us know.  Thank you.', 'error', 'inv_result_message');
                var submitId = document.getElementById(purchase_label);
                submitId.disabled = false;
            }
        }
    });
}

// update the paid status block to show the confirmed space
function updatePaidStatusBlock() {
    var blockname = region_list[regionYearId].shortname + '_div';
    var blockdiv = document.getElementById(blockname);

    // get the name for this region
    var regionName = region_list[regionYearId].name;
    // get the list item for this
    var region_spaces = exhibits_spaces[regionYearId];
    var spaceStatus = ''
    var exSpaceKeys = Object.keys(exhibitor_spacelist);
    for (var exSpaceIdx in exSpaceKeys) {
        if (region_spaces[exSpaceKeys[exSpaceIdx]]) { // space is in our region
            var region = region_spaces[exSpaceKeys[exSpaceIdx]];
            var space = exhibitor_spacelist[exSpaceKeys[exSpaceIdx]];
            if (space.item_purchased) {
                var timePurchased = new Date(space.time_purchased)
                spaceStatus += space.requested_description + " in " + regionName + " for $" + Number(space.requested_price).toFixed(2) +
                    " at " + timePurchased + "<br/>";
            }
        }
    }

    if (spaceStatus == '') {
        blockdiv.innerHTML = "<div class='col-sm-auto p-0'><button class='btn btn-primary' onclick = 'exhibitorRequest.openReq(regionYearId, 0);' > Request " + regionName + " Space</button></div>";
        return;
    }

    spaceStatus += "<button class='btn btn-primary m-1' onclick='exhibitorReceipt.showReceipt(" + regionYearId + ");' > Show receipt for " + regionName + " space</button>";
    if (region_list[regionYearId].portalType == 'artist') {
        spaceStatus += "<button class='btn btn-primary m-1' onclick='auctionItemRegistration.open();'>Open Item Registration</button>";
    }
    blockdiv.innerHTML = '<div class="col-sm-auto p-0">You have purchased:<br/>' + spaceStatus + "</div>";

}
