// Items related to building and paying the vendor invoice
var vendor_invoice = null;
var totalSpacePrice = 0;

// set up vendor invoice items
function vendorInvoiceOnLoad() {
    id = document.getElementById('vendor_invoice');
    if (id != null) {
        vendor_invoice = new bootstrap.Modal(id, { focus: true, backdrop: 'static' });
    }
}

// openInvoice: display the vendor invoice (and registration items)
function openInvoice(regionId) {
    var regionName = '';
    var includedMemberships = 0;
    var additionalMemberships = 0;
    var html = '';
    var priceIdx = 0;

    if (config['debug'] & 1)
        console.log("regionId: " + regionId);
    var region = exhibits_spaces[regionId];

    var regionList = region_list[regionId];
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
    html += "____________________________<br/>\nTotal price for spaces $" + Number(totalSpacePrice).toFixed(2)+ "<br/>\n";

    document.getElementById('vendor_inv_approved_for').innerHTML = html;

    // fill in the variable items
    document.getElementById("vendor_invoice_title").innerHTML = "<strong>Pay " + regionName + ' Invoice</strong>';

    var spaces = includedMemberships + additionalMemberships;
    html = "<p>This space comes with " +
        (includedMemberships > 0 ? includedMemberships : "no") +
        " memberships included and " +
        (additionalMemberships > 0 ? "the " : "no ") + "right to purchase " +
        (additionalMemberships > 0 ? "up to " +  additionalMemberships  : "no") +
        " additional memberships at a reduced rate of $" + Number(regionList.additionalMemPrice).toFixed(2) + ".</p>";
    if (spaces > 0) {
        html += "<p>All vendors must have a membership for everyone working in their space. Included and additional discounted memberships can only be purchased while paying for your space. " +
            "If you do not purchase them now while paying your space invoice, you will have to purchase them at the current membership rates.</p>" +
            "<p>If you are unsure who will be using the registrations please use the first name of ‘Provided’ and a last name of ‘At Con’. The on-site registration desk will update the membership to the name on their ID.</p>" +
            "<p>Program participants do not need to buy memberships; however, we will confirm that they meet the requirements to waive the membership cost.  If they do not, they will need to purchase a membership on-site at the on-site rates.</p>" +
            "<p><input type='checkbox' style='transform: scale(2);' name='agreeNone' id='add-new-comment'agreeNone'> &nbsp;&nbsp;If you do not wish to purchase any memberships at this time, check this box to acknowledge the requirement for memberships above.</p>"
    }
    document.getElementById('vendor_inv_included').innerHTML = html;
    document.getElementById('vendor_inv_cost').innerHTML = Number(totalSpacePrice).toFixed(2);
    //document.getElementById('vendor_inv_item_id').value = price.id

    var html = '';
    // now build the included memberships
    if (includedMemberships > 0) {
        html = "<input type='hidden' name='incl_mem_count' value='" + includedMemberships + "'>\n" +
            "<div class='row'><div class='col-sm-auto p-2 pe-0'><strong>Included Memberships: (up to " + includedMemberships + ")</strong></div></div>";
        for (var mnum = 0; mnum < includedMemberships; mnum++) {
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
    if (additionalMemberships > 0) {
        html += "<input type='hidden' name='addl_mem_count' value='" + additionalMemberships + "'>\n" +
            "<div class='row'><div class='col-sm-auto p-2 pe-0'><strong>Additional Memberships: (up to " + additionalMemberships + ")</strong></div></div>";
        for (var mnum = 0; mnum < additionalMemberships; mnum++) {
            // name fields
            html += `
<div class="row mt-4">
    <div class="col-sm-auto p-0">Additional Member ` + (mnum + 1) + `:</div>
</div>
<div class="row">
    <div class="col-sm-auto ms-0 me-2 p-0">
        <label for="fname_a_` + mnum + `" class="form-label-sm"><span class="text-dark" style="font-size: 10pt;"><span class='text-info'>*</span>First Name</span></label><br/>
        <input class="form-control-sm" type="text" name="fname_a_` + mnum + `" id="fname_a_` + mnum + `" size="22" maxlength="32" onchange="updateCost(` + regionId + "," + mnum + `)"/>
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

// update invoice for the Cost of Memberships and total Cost when an additional member is started
function updateCost(regionId, item) {
    var regionList = region_list[regionId];
    var price = Number(regionList.additionalMemPrice);
    var fname = document.getElementById('fname_a_' + item).value;
    var cost = 0;
    additional_cost[item] = fname == '' ? 0 : Number(regionList.additionalMemPrice);
    for (var num in additional_cost) {
        cost += additional_cost[num];
    }
    console.log(cost);
    document.getElementById('vendor_inv_mbr_cost').innerHTML = Number(cost).toFixed(2);
    cost += Number(totalSpacePrice);
    console.log(cost);
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
