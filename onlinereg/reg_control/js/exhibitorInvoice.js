// Items related to building and paying the exhibitor invoice
class ExhibitorInvoice {
    #exhibitorInvoiceModal = null;
    #totalSpacePrice = 0;
    #regionYearId = null;
    #exhibitorId = null;
    #membershipCostdiv = null;
    #mailin = null;
    #additional_cost = [];
    #elcheckno = null;
    #elCcauth = null;
    #econfirm = null;
    #payCheckno = null;
    #payCcauth = null;
    #payButton = null;
    #payAmt = null;
    #totalInvCost = null;
    #totalMembershipCost = null;
    #payDescription = null;
    #totalAmountDue = 0;
    #paymentTypeDiv = null;
    #includedMemberships = 0;
    #additionalMemberships = 0;
    #uspsChecked = [];

// constructor function - intializes dom objects and inital privates
    constructor() {
        var id = document.getElementById('vendor_invoice');
        if (id != null) {
            this.#exhibitorInvoiceModal = new bootstrap.Modal(id, {focus: true, backdrop: 'static'});
        }
        this.#membershipCostdiv = document.getElementById("membershipCost");
        this.#elcheckno = document.getElementById('pay-check-div');
        this.#elCcauth = document.getElementById('pay-ccauth-div');
        this.#econfirm = document.getElementById('');
        this.#payCheckno = document.getElementById('pay-checkno');
        this.#payCcauth = document.getElementById('pay-ccauth');
        this.#payDescription = document.getElementById('pay-desc');
        this.#payButton = document.getElementById('pay-btn-pay');
        this.#payAmt = document.getElementById('pay-amt');
        this.#totalInvCost = document.getElementById('vendor_inv_cost');
        this.#totalMembershipCost = document.getElementById('vendor_inv_mbr_cost');
        this.#paymentTypeDiv = document.getElementById('pt-div');

        this.#totalAmountDue = 0;
    }

// openInvoice: display the vendor invoice (and registration items)
    openInvoice(exhibitorId, regionYearId) {
        var regionName = '';
        var html = '';
        var priceIdx = 0;

        this.#regionYearId = regionYearId;
        this.#exhibitorId = exhibitorId;
        this.#totalSpacePrice = 0;

        if (config['debug'] & 1) {
            console.log("regionYearId: " + regionYearId);
        }
        var region = exhibits_spaces[regionYearId];
        var regionList = region_list[regionYearId];
        var portalName = 'ConTroll';
        var attendeeName = 'Exhibitor';
        var attendeeNameLC = 'Exhibitors';
        var portalType = regionList.portalType
        var exhibitorName = exhibitor_info['exhibitorName'];
        switch (portalType) {
            case 'artist':
                portalName = 'Artist';
                attendeeName = 'Artist';
                attendeeNameLC = 'artist';
                exhibitorName = exhibitor_info['artistName'];
                if (exhibitorName == null || exhibitorName == '') {
                    exhibitorName = exhibitor_info['exhibitorName'];
                }
                break;
            case 'vendor':
                portalName = 'Vendor';
                attendeeName = 'Vendor';
                attendeeNameLC = 'vendor';
                break;
        }
        this.#mailin = exhibitor_info['mailin'];
        if (config['debug'] & 1) {
            console.log("regionList");
            console.log(regionList);
            console.log("Region Spaces");
            console.log(region);
        }

        regionName = regionList.name;

        // refresh the items spaces purchased area
        html = exhibitorName + " is approved for:<br/>\n";
        var exSpaceKeys = Object.keys(exhibitor_spacelist);
        for (var exSpaceIdx in exSpaceKeys) {
            if (region[exSpaceKeys[exSpaceIdx]]) { // space is in our region
                var space = exhibitor_spacelist[exSpaceKeys[exSpaceIdx]];
                var prices = region[exSpaceKeys[exSpaceIdx]].prices;
                if (space.item_approved) {
                    html += space.approved_description + " in " + regionName + " for $" + Number(space.approved_price).toFixed(2) + "<br/>";
                    this.#totalSpacePrice += Number(space.approved_price);
                    // find price item in prices
                    for (priceIdx = 0; priceIdx < prices.length; priceIdx++) {
                        if (prices[priceIdx].id == space.item_approved)
                            break;
                    }
                    this.#includedMemberships = Math.max(this.#includedMemberships, prices[priceIdx].includedMemberships);
                    this.#additionalMemberships = Math.max(this.#additionalMemberships, prices[priceIdx].additionalMemberships);
                }
            }
        }
        if (regionList['mailinFee'] > 0 && this.#mailin == 'Y') {
            html += "Mail in free of $" + Number(regionList['mailinFee']).toFixed(2) + "<br/>\n";
            this.#totalSpacePrice += Number(regionList['mailinFee']);
        }
        html += "____________________________<br/>\nTotal price for spaces $" + Number(this.#totalSpacePrice).toFixed(2) + "<br/>\n";

        document.getElementById('vendor_inv_approved_for').innerHTML = html;

        // fill in the variable items
        document.getElementById("vendor_invoice_title").innerHTML = "<strong>Pay " + regionName + ' Invoice for ' + exhibitorName + '</strong>';

        var spaces = this.#includedMemberships + this.#additionalMemberships;
        html = "<p>This space comes with " +
            (this.#includedMemberships > 0 ? this.#includedMemberships : "no") +
            " memberships included and " +
            (this.#additionalMemberships > 0 ? "the " : "no ") + "right to purchase " +
            (this.#additionalMemberships > 0 ? "up to " + this.#additionalMemberships : "no") +
            " additional memberships at a reduced rate of $" + Number(regionList.additionalMemPrice).toFixed(2) + ".</p>";
        if ((this.#includedMemberships == 0) && (this.#additionalMemberships == 0)) {
            html += "<input type='hidden' name='agreeNone' value='on'></input>"
        }
        if (spaces > 0) {
             if (portalType == 'artist' && this.#mailin == 'N') {
                html += "<p>In addition, all non-mail-in artists need to declare an on-site agent. " +
                    "This is the person that will be contacted if there are any issues with setup, operation, or teardown of your exhibit. " +
                    "The agent needs a membership, and you can be the agent.</p>" +
                    "<p><input type='radio' name='agent' id='agent_self' value='self' style='transform: scale(1.5);'>&nbsp;&nbsp;&nbsp;I will be my own agent and my membership is not one of the ones below.<br/>" +
                    "<input type='radio' name='agent' id='agent_first' value='first' style='transform: scale(1.5);'>&nbsp;&nbsp;&nbsp;The first membership below will be my agent.<br/>";

                if (exhibitor_info['perid']) {
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
        this.#totalAmountDue = Number(this.#totalSpacePrice);
        this.#totalInvCost.innerHTML = Number(this.#totalSpacePrice).toFixed(2);
        document.getElementById('vendorSpacePrice').value = this.#totalSpacePrice;
        document.getElementById('vendor_inv_region_id').value = regionYearId;

       this.#membershipCostdiv.hidden = (this.#includedMemberships == 0 && this.#additionalMemberships == 0);

        html = '';
        var firstStar = '';
        var addrStar = '';
        var allStar = '';
        // now build the included memberships
        if (this.#includedMemberships > 0 || this.#additionalMemberships > 0) {
            html += `
             <div class="row" style="width:100%;">
                <div class="col-sm-12">
                    <p class="text-body">
                        <b>Note:</b> Please provide your legal name that will match a valid form of ID. 
                        Your legal name will not be publicly visible.  
                        If you don't provide one, it will default to your First, Middle, Last Names and Suffix.
                    </p>
                    <p class="text-body">
                        Items marked with <span class="text-danger">&bigstar;</span> are required fields.
                        If the information is not available, enter /r for the field.
                    </p>
                </div>
            </div>
`;
            // cascading list of required fields, each case adds more so the breaks fall into the next section
            switch (config['required']) {
                case 'all':
                    allStar = '<span class="text-danger">&bigstar;</span>';
                case 'addr':
                    addrStar = '<span class="text-danger">&bigstar;</span>';
                case 'first':
                    firstStar = '<span class="text-danger">&bigstar;</span>';
            }
        }
        if (this.#includedMemberships > 0) {
            html += "<input type='hidden' name='incl_mem_count' value='" + this.#includedMemberships + "'>\n" +
                "<div class='container-fluid'>\n" +
                "<div class='row'><div class='col-sm-auto p-2 pe-0'><strong>Included Memberships: (up to " + this.#includedMemberships + ")</strong>" +
                "<input type='hidden' name='this.#includedMemberships' value='" + String(this.#includedMemberships) + "'></div></div>";
            for (var mnum = 0; mnum < this.#includedMemberships; mnum++) {
                // name fields including legal name
                html += `
<div class="row mt-4">
    <div class="col-sm-auto p-0">Included Member ` + (mnum + 1) + `:</div>
</div>
<div class="row">
    <div class="col-sm-auto ms-0 me-2 p-0">
        <label for="fname_i_` + mnum + `" class="form-label-sm"><span class="text-dark" style="font-size: 10pt;">` + firstStar + `First Name</span></label><br/>
        <input class="form-control-sm" type="text" name="fname_i_` + mnum + `" id="fname_i_` + mnum + `" size="22" maxlength="32"/>
    </div>
    <div class="col-sm-auto ms-0 me-2 p-0">
        <label for="mname_i_` + mnum + `" class="form-label-sm"><span class="text-dark" style="font-size: 10pt;">Middle Name</span></label><br/>
        <input class="form-control-sm" type="text" name="mname_i_` + mnum + `" id="mname_i_` + mnum + `" size="8" maxlength="32" />
    </div>
    <div class="col-sm-auto ms-0 me-2 p-0">
        <label for="lname_i_` + mnum + `" class="form-label-sm"><span class="text-dark" style="font-size: 10pt;">` + allStar + `Last Name</span></label><br/>
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
        <label for="addr_i_` + mnum + `" class="form-label-sm"><span class="text-dark" style="font-size: 10pt;">` + addrStar + `Address</span></label><br/>
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
        <label for="city_i_` + mnum + `" class="form-label-sm"><span class="text-dark" style="font-size: 10pt;">` + addrStar + `City</span></label><br/>
        <input class="form-control-sm" type="text" name="city_i_` + mnum + `" id='city_i_` + mnum + `' size="22" maxlength="32" />
    </div>
    <div class="col-sm-auto ms-0 me-2 p-0">
        <label for="state_i_` + mnum + `" class="form-label-sm"><span class="text-dark" style="font-size: 10pt;">` + addrStar + `State</span></label><br/>
        <input class="form-control-sm" type="text" name="state_i_` + mnum + `" id='state_i_` + mnum + `' size="10" maxlength=16" />
    </div>
    <div class="col-sm-auto ms-0 me-2 p-0">
        <label for="zip_i_` + mnum + `" class="form-label-sm"><span class="text-dark" style="font-size: 10pt;">` + addrStar + `Zip</span></label><br/>
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
        <label for="email_i_` + mnum + `" class="form-label-sm"><span class="text-dark" style="font-size: 10pt;">` + firstStar + `Email</span></label><br/>
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
        if (this.#additionalMemberships > 0) {
            html += "<input type='hidden' name='addl_mem_count' value='" + this.#additionalMemberships + "'>\n" +
                "<div class='row'><div class='col-sm-auto p-2 pe-0'><strong>Additional Memberships: (up to " + this.#additionalMemberships + ")</strong>" +
                "<input type='hidden' name='this.#additionalMemberships' value='" + String(this.#additionalMemberships) + "'></div></div>";
            for (var mnum = 0; mnum < this.#additionalMemberships; mnum++) {
                // name fields includeing legal name
                html += `
<div class="row mt-4">
    <div class="col-sm-auto p-0">Additional Member ` + (mnum + 1) + `:</div>
</div>
<div class="row">
    <div class="col-sm-auto ms-0 me-2 p-0">
        <label for="fname_a_` + mnum + `" class="form-label-sm"><span class="text-dark" style="font-size: 10pt;">` + firstStar + `First Name</span></label><br/>
        <input class="form-control-sm" type="text" name="fname_a_` + mnum + `" id="fname_a_` + mnum + `" size="22" maxlength="32" onchange="exhibitorInvoice.updateCost(` + regionYearId + "," + mnum + `)"/>
    </div>
    <div class="col-sm-auto ms-0 me-2 p-0">
        <label for="mname_a_` + mnum + `" class="form-label-sm"><span class="text-dark" style="font-size: 10pt;">Middle Name</span></label><br/>
        <input class="form-control-sm" type="text" name="mname_a_` + mnum + `" id="mname_a_` + mnum + `" size="8" maxlength="32" />
    </div>
    <div class="col-sm-auto ms-0 me-2 p-0">
        <label for="lname_a_` + mnum + `" class="form-label-sm"><span class="text-dark" style="font-size: 10pt;">` + allStar + `Last Name</span></label><br/>
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
        <label for="addr_a_` + mnum + `" class="form-label-sm"><span class="text-dark" style="font-size: 10pt;">` + addrStar + `>Address</span></label><br/>
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
        <label for="city_a_` + mnum + `" class="form-label-sm"><span class="text-dark" style="font-size: 10pt;">` + addrStar + `City</span></label><br/>
        <input class="form-control-sm" type="text" name="city_a_` + mnum + `" id='city_a_` + mnum + `' size="22" maxlength="32" />
    </div>   
    <div class="col-sm-auto ms-0 me-2 p-0">
        <label for="state_a_` + mnum + `" class="form-label-sm"><span class="text-dark" style="font-size: 10pt;">` + addrStar + `State</span></label><br/>
        <input class="form-control-sm" type="text" name="state_a_` + mnum + `" id='state_a_` + mnum + `' size="10" maxlength="16" />
    </div>
    <div class="col-sm-auto ms-0 me-2 p-0">
        <label for="zip_a_` + mnum + `" class="form-label-sm"><span class="text-dark" style="font-size: 10pt;">` + addrStar + `Zip</span></label><br/>
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
        <label for="email_a_` + mnum + `" class="form-label-sm"><span class="text-dark" style="font-size: 10pt;">` + firstStar + `Email</span></label><br/>
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
            html += "</div><hr/>";
        }
        document.getElementById("vendor_inv_included_mbr").innerHTML = html;
        // fill in default information for the values of the addresses
        for (mnum = 0; mnum < this.#includedMemberships; mnum++) {
            document.getElementById('addr_i_' + mnum).value = exhibitor_info['addr'];
            document.getElementById('addr2_i_' + mnum).value = exhibitor_info['addr2'];
            document.getElementById('city_i_' + mnum).value = exhibitor_info['city'];
            document.getElementById('state_i_' + mnum).value = exhibitor_info['state'];
            document.getElementById('zip_i_' + mnum).value = exhibitor_info['zip'];
            document.getElementById('country_i_' + mnum).value = exhibitor_info['country'];
            document.getElementById('email_i_' + mnum).value = exhibitor_info['exhibitorEmail'];
            document.getElementById('phone_i_' + mnum).value = exhibitor_info['exhibitorPhone'];
        }
        for (mnum = 0; mnum < this.#additionalMemberships; mnum++) {
            document.getElementById('addr_a_' + mnum).value = exhibitor_info['addr'];
            document.getElementById('addr2_a_' + mnum).value = exhibitor_info['addr2'];
            document.getElementById('city_a_' + mnum).value = exhibitor_info['city'];
            document.getElementById('state_a_' + mnum).value = exhibitor_info['state'];
            document.getElementById('zip_a_' + mnum).value = exhibitor_info['zip'];
            document.getElementById('country_a_' + mnum).value = exhibitor_info['country'];
            document.getElementById('email_a_' + mnum).value = exhibitor_info['exhibitorEmail'];
            document.getElementById('phone_a_' + mnum).value = exhibitor_info['exhibitorPhone'];
        }
        this.#exhibitorInvoiceModal.show();
    }

// update invoice for the Cost of Memberships and total Cost when an additional member is started
    updateCost(regionYearId, item) {
        var regionList = region_list[regionYearId];
        var price = Number(regionList.additionalMemPrice);
        var fname = document.getElementById('fname_a_' + item).value;
        this.#totalAmountDue = 0;
        this.#additional_cost[item] = fname == '' ? 0 : Number(regionList.additionalMemPrice);
        for (var num in this.#additional_cost) {
            this.#totalAmountDue += this.#additional_cost[num];
        }
        if (config['debug'] & 1)
            console.log('Pre this.#totalSpacePrice: ' + String(this.#totalAmountDue));
        this.#totalMembershipCost.innerHTML = Number(this.#totalAmountDue).toFixed(2);
        this.#totalAmountDue += Number(this.#totalSpacePrice);
        if (config['debug'] & 1)
            console.log('After adding this.#totalSpacePrice: ' + String(this.#totalAmountDue));
        this.#totalInvCost.innerHTML = Number(this.#totalAmountDue).toFixed(2);
    }

// setPayType: shows/hides the appropriate fields for that payment type
    setPayType(ptype) {
        this.#elcheckno.hidden = ptype != 'check';
        this.#elCcauth.hidden = ptype != 'credit';
        this.#payButton.disabled = ptype == 'online';

        if (ptype != 'check') {
            this.#payCheckno.value = null;
        }
        if (ptype != 'credit') {
            this.#payCcauth.value = null;
        }
    }

// Process a payment against the transaction
    pay() {
        var checked = false;
        var ccauth = null;
        var checkno = null;
        var desc = null;
        var ptype = null;
        var pt_cash = document.getElementById('pt-cash').checked;
        var pt_check = document.getElementById('pt-check').checked;
        var pt_credit = document.getElementById('pt-credit').checked;
        var valid = true;
        var mnum = 0;

        clear_message('inv_result_message');

        if (prow == null) {
            // validate the payment entry: It must be >0 and <= amount due
            //      a payment type must be specified
            //      for check: the check number is required
            //      for credit card: the auth code is required
            //      for discount: description is required, it's optional otherwise
            var pay_amt = Number(this.#payAmt.value);
            if (pay_amt > 0 && pay_amt > this.#totalAmountDue) {
                this.#payAmt.style.backgroundColor = 'var(--bs-warning)';
                valid = false;
            }
            if (pay_amt <= 0) {
                this.#payAmt.style.backgroundColor = 'var(--bs-warning)';
                valid = false;
            }

            this.#payAmt.style.backgroundColor = '';
            this.#paymentTypeDiv.style.backgroundColor = '';

            if (pt_check) {
                ptype = 'check';
                checkno = this.#payCheckno.value;
                if (checkno == null || checkno == '') {
                    this.#payCheckno.style.backgroundColor = 'var(--bs-warning)';
                    return;
                } else {
                    this.#payCheckno.style.backgroundColor = '';
                }
                checked = true;
            }

            if (pt_credit) {
                ptype = 'credit';
                ccauth = this.#payCcauth.value;
                if (ccauth == null || ccauth == '') {
                    this.#payCcauth.style.backgroundColor = 'var(--bs-warning)';
                    return;
                } else {
                    this.#payCcauth.style.backgroundColor = '';
                }
                checked = true;
            }

            if (pt_cash) {
                ptype = 'cash';
                checked = true;
            }

            if (!checked) {
                this.#paymentTypeDiv.style.backgroundColor = 'var(--bs-warning)';
                valid = false;
            }

            // now validate the membership fields
            for (mnum = 0; mnum < this.#includedMemberships; mnum++) {
                if (!this.#checkValid('_i_' + mnum))
                    valid = false;
            }
            for (mnum = 0; mnum < this.#additionalMemberships; mnum++) {
                if (!this.#checkValid('_a_' + mnum))
                    valid = false;
            }

            if (!valid) {
                show_message('Please correct the items marked in yellow to process the payment.' +
                    '<br/>For fields in the membership area that are required and not available, use /r to indicate not available.',
                    'warn', 'inv_result_message')
                return;
            }

            // fields are now validated, apply USPS validation to each item?
            if (config['useUSPS']) {
                // now validate the membership fields
                for (mnum = 0; mnum < this.#includedMemberships; mnum++) {
                    if (this.#checkMembershipUSPS('_i_' + mnum))
                        return;
                }
                for (mnum = 0; mnum < this.#additionalMemberships; mnum++) {
                    if (this.#checkMembershipUSPS('_a_' + mnum))
                        return;
                }
            }

            if (pay_amt > 0) {
                var prow = {
                    index: 2, amt: pay_amt, ccauth: ccauth, checkno: checkno, desc: this.#payDescription.value, type: ptype, nonce: 'offline',
                };
            }
        }
        // process payment
        var postData = {
            ajax_request_action: 'processPayment',
            cart_membership: cart.getCartMembership(),
            new_payment: prow,
            nonce: 'offline',
            user_id: user_id,
            pay_tid: pay_tid,
        };
        pay_button_pay.disabled = true;
        $.ajax({
            method: "POST",
            url: "scripts/reg_processPayment.php",
            data: postData,
            success: function (data, textstatus, jqxhr) {
                var stop = true;
                clear_message();
                if (typeof data == 'string') {
                    show_message(data, 'error');
                } else if (data['error'] !== undefined) {
                    show_message(data['error'], 'error');
                } else if (data['message'] !== undefined) {
                    show_message(data['message'], 'success');
                    stop = false;
                } else if (data['warn'] !== undefined) {
                    show_message(data['warn'], 'success');
                    stop = false;
                } else if (data['status'] == 'error') {
                    show_message(data['data'], 'error');
                }
                if (!stop)
                    updatedPayment(data);
                pay_button_pay.disabled = false;
            },
            error: function (jqXHR, textstatus, errorThrown) {
                pay_button_pay.disabled = false;
                showAjaxError(jqXHR, textstatus, errorThrown);
            },
        });
    }

    // check if value is non blank
    #checkNonBlank(id) {
        if (id.value == '') {
            id.style.backgroundColor = 'var(--bs-warning)';
            return false;
        }
        id.style.backgroundColor = '';
        return true;
    }

    // check the additional membership section for valid entries
    #checkValid(suffix) {
        var id = null;
        var value = null;
        var country = null;
        var valid = true;

        if (config['required'] != '') {
            if (!this.#checkNonBlank(document.getElementById('fname' + suffix)))
                valid = false;
        }

        if (config['required'] == 'all') {
            if (!this.#checkNonBlank(document.getElementById('lname' + suffix)))
                valid = false;
        }

        if (config['required'] == 'all' || config['required'] == 'addr') {
            if (!this.#checkNonBlank(document.getElementById('addr' + suffix)))
               valid = false;

            if (!this.#checkNonBlank(document.getElementById('city' + suffix)))
                valid = false;

            if (!this.#checkNonBlank(document.getElementById('state' + suffix)))
                valid = false;

            country = document.getElementById('state' + suffix).value;
            id = document.getElementById('state' + suffix);
            value = id.value;
            if (value == '') {
                valid = false;
                id.style.backgroundColor = 'var(--bs-warning)';
            } else {
                if (country == 'USA') {
                    if (value.length != 2) {
                        valid = false;
                        id.style.backgroundColor = 'var(--bs-warning)';
                    } else {
                        id.style.backgroundColor = '';
                    }
                } else {
                    id.style.backgroundColor = '';
                }
            }

            if (!this.#checkNonBlank(document.getElementById('zip' + suffix)))
                valid = false;
        }

        id = document.getElementById('email' + suffix);
        value = id.value;
        if (value != '/r' && !emailRegex.test(value)) {
            valid = false;
            id.style.backgroundColor = 'var(--bs-warning)';
        } else {
            id.style.backgroundColor = '';
        }

        return valid;
    }

    // do USPS for a membership
    this.#checkMembershipUSPS(suffix) {
        if (uspsChecked[suffix])  // don't check it twice if we get all the way through the check on it.
            return false;

        var country = document.getElementById('country' + suffix);
        if (country.value != 'USA') {
            uspsChecked[suffix] = true;
            return false;
        }

        var person =



    }

// Create a receipt and email it
    email_receipt(receipt_type) {
        // header text
        var header_text = cart.receiptHeader(user_id, pay_tid);
        // optional footer text
        var footer_text = '';
        // server side will print the receipt
        var postData = {
            ajax_request_action: 'printReceipt',
            header: header_text,
            prows: cart.getCartPerinfo(),
            mrows: cart.getCartMembership(),
            pmtrows: cart.getCartPmt(),
            footer: footer_text,
            receipt_type: receipt_type,
            email_addrs: emailAddreesRecipients,
        };
        pay_button_ercpt.disabled = true;
        $.ajax({
            method: "POST",
            url: "scripts/reg_emailReceipt.php",
            data: postData,
            success: function (data, textstatus, jqxhr) {
                clear_message();
                if (typeof data == "string") {
                    show_message(data,  'error');
                } else if (data['error'] !== undefined) {
                    show_message(data['error'], 'error');
                } else if (data['message'] !== undefined) {
                    show_message(data['message'], 'success');
                } else if (data['warn'] !== undefined) {
                    show_message(data['warn'], 'success');
                }
                pay_button_ercpt.disabled = false;
            },
            error: function (jqXHR, textstatus, errorThrown) {
                pay_button_ercpt.disabled = false;
                showAjaxError(jqXHR, textstatus, errorThrown);
            }
        });
    }
}

exhibitorInvoice = null;
// init
function exhibitorInvoiceOnLoad() {
    exhibitorInvoice = new ExhibitorInvoice();
}