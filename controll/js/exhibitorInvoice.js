// Items related to building and paying the exhibitor invoice
class ExhibitorInvoice {
    #exhibitorInvoiceModal = null;
    #totalSpacePrice = 0;
    #regionYearId = null;
    #exhibitorId = null;
    #exhibitorYearId = null;
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
    #paymentDiv = null;
    #includedMemberships = 0;
    #additionalMemberships = 0;
    #uspsChecked = [];
    #uspsAddress = null;
    #firstStar = '';
    #addrStar = '';
    #allStar = '';
    #currentSuffix = null;
    #uspsDiv = null;

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
        this.#paymentDiv = document.getElementById('paymentDiv');

        this.#totalAmountDue = 0;
    }

// update showing the pay button and the payment fields
    #updatePaymentDiv() {
        if (this.#totalAmountDue == 0) {
            this.#payButton.disabled = false;
            this.#paymentDiv.hidden = true;
        } else {
            this.#payButton.disabled = !(document.getElementById('pt-cash').checked ||
                document.getElementById('pt-check').checked || document.getElementById('pt-credit').checked)
            this.#paymentDiv.hidden = false;
        }
    }

// openInvoice: display the vendor invoice (and registration items)
    openInvoice(exhibitorInfo, regionYearId) {
        var regionName = '';
        var spacePriceName = '';
        var html = '';
        var priceIdx = 0;

        this.#regionYearId = regionYearId;
        this.#exhibitorId = exhibitorInfo.exhibitorId;
        this.#exhibitorYearId = exhibitorInfo.exhibitorYearId;
        this.#totalSpacePrice = 0;

        if (config.debug & 1) {
            console.log("regionYearId: " + regionYearId);
        }
        var region = exhibits_spaces[regionYearId];
        var regionList = region_list[regionYearId];
        var portalName = 'ConTroll';
        var attendeeName = 'Exhibitor';
        var attendeeNameLC = 'Exhibitors';
        var portalType = regionList.portalType
        var exhibitorName = exhibitor_info.exhibitorName;
        switch (portalType) {
            case 'artist':
                portalName = 'Artist';
                attendeeName = 'Artist';
                attendeeNameLC = 'artist';
                exhibitorName = exhibitor_info.artistName;
                if (exhibitorName == null || exhibitorName == '') {
                    exhibitorName = exhibitor_info.exhibitorName;
                }
                break;
            case 'vendor':
                portalName = 'Vendor';
                attendeeName = 'Vendor';
                attendeeNameLC = 'vendor';
                break;
        }
        this.#mailin = exhibitor_info.mailin;
        if (config.debug & 1) {
            console.log("regionList");
            console.log(regionList);
            console.log("Region Spaces");
            console.log(region);
        }

        regionName = regionList.name;

        // refresh the items spaces purchased area
        html = exhibitorName + " is approved for:<br/>\n";
        var exSpaceKeys = Object.keys(exhibitor_spacelist);
        this.#includedMemberships = 0;
        this.#additionalMemberships = 0;
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
                    if (this.#includedMemberships < prices[priceIdx].includedMemberships) {
                        spacePriceName = prices[priceIdx].description;
                        this.#includedMemberships = prices[priceIdx].includedMemberships;
                    }
                    if (this.#additionalMemberships < prices[priceIdx].additionalMemberships) {
                        spacePriceName = prices[priceIdx].description;
                        this.#additionalMemberships = prices[priceIdx].additionalMemberships;
                    }
                }
            }
        }
        if (regionList.mailinFee > 0 && this.#mailin == 'Y') {
            html += "Mail in fee of $" + Number(regionList.mailinFee).toFixed(2) + "<br/>\n";
            this.#totalSpacePrice += Number(regionList.mailinFee);
        }
        html += "____________________________<br/>\nTotal price for spaces $" + Number(this.#totalSpacePrice).toFixed(2) + "<br/>\n";

        document.getElementById('vendor_inv_approved_for').innerHTML = html;

        // fill in the variable items
        document.getElementById("vendor_invoice_title").innerHTML = "<strong>Pay " + regionName + ' Invoice for ' + exhibitorName + '</strong>';

        var spaces = this.#includedMemberships + this.#additionalMemberships;
        // make the strings for the number of included additional memberships available to purchase
        html = '<p>';
        if (spaces == 0) { // no additional or included memberships
            html += regionName + ' ' +  spacePriceName + ' spaces do not come with any memberships as part of the space purchase. ' +
                ' Please purchase your attending memberships to the convention separately at ' +
                '<a href="' + config.regserver + '">' + config.regserver + '</a>.';
        } else if (this.#includedMemberships == 0) {
            html += regionName + ' ' +  spacePriceName + ' spaces come with the option to purchase up to ' + this.#additionalMemberships +
                ' membership' + (this.#additionalMemberships > 1 ? 's' : '') + ' at  the discounted price of $' +
                Number(regionList.additionalMemPrice).toFixed(2) + '. ' +
                'Purchase those memberships here. ' +
                'Any additional memberships beyond those you purchase here need to be purchased separately at ' +
                '<a href="' + config.regserver + '">' + config.regserver + '</a>.';
        } else if (this.#additionalMemberships == 0) {
            html += regionName + ' ' +  spacePriceName + ' spaces come with ' + this.#includedMemberships + ' membership' + (this.#includedMemberships > 1 ? 's' : '') +
                ' as part of the space purchase. Please enter those memberships here. ' +
                'Any additional memberships to the convention need to be purchased separately at ' +
                '<a href="' + config.regserver + '">' + config.regserver + '</a>.';
        } else {
            html += regionName + ' ' +  spacePriceName + ' spaces come with ' + this.#includedMemberships + ' membership' + (this.#includedMemberships > 1 ? 's' : '') +
                ' as part of the space purchase. In addition it comes with the right to purchase up to ' + this.#additionalMemberships +
                ' membership' + (this.#additionalMemberships > 1 ? 's' : '') + ' at  the discounted price of $' +
                Number(regionList.additionalMemPrice).toFixed(2) + '. ' +
                'Use the included memberships first, and then add the additional memberships if desired. If you need more memberships beyond that they need to' +
                ' be purchased separately at ' +
                '<a href="' + config.regserver + '">' + config.regserver + '</a>.';
        }
        html += "</p>\n";
        if (spaces == 0) {
            html += "<input type='hidden' name='agreeNone' value='on'></input>"
        }
        html += "<input type='hidden' name='exhibitorId' value='" + this.#exhibitorId + "'></input>\n" +
            "<input type='hidden' name='exhibitorYearId' value='"+ this.#exhibitorYearId + "'></input>\n";
        if (spaces > 0) {
             if (portalType == 'artist' && this.#mailin == 'N') {
                html += "<p>In addition, all non-mail-in artists need to declare an on-site agent. " +
                    "This is the person that will be contacted if there are any issues with setup, operation, or teardown of your exhibit. " +
                    "The agent needs a membership, and you can be the agent.</p>" +
                    "<p><input type='radio' name='agent' id='agent_self' value='self' style='transform: scale(1.5);'>&nbsp;&nbsp;&nbsp;I will be my own agent and my membership is not one of the ones below.<br/>" +
                    "<input type='radio' name='agent' id='agent_first' value='first' style='transform: scale(1.5);'>&nbsp;&nbsp;&nbsp;The first membership below will be my agent.<br/>";

                if (exhibitor_info.perid) {
                    html += "<input type='radio' name='agent' id='agent_perid' value='p" + exhibitor_info.perid + "' style='transform: scale(1.5);'>&nbsp;&nbsp;&nbsp;Assign " +
                        exhibitor_info.p_first_name + ' ' + exhibitor_info.p_last_name + ' as my agent.<br/>';
                } else if (exhibitor_info.newperid) {
                    html += "<input type='radio' name='agent' id='agent_newid' value='n" + exhibitor_info.newperid + "' style='transform:" +
                        " scale(1.5);'>&nbsp;&nbsp;&nbsp;Assign " +
                        exhibitor_info.n_first_name + ' ' + exhibitor_info.n_last_name + ' as my agent.<br/>';
                }
                html += "<input type='radio' name='agent' id='agent_request' value='request' style='transform: scale(1.5);'>&nbsp;&nbsp;&nbsp;Please assign my agent as per my request below.<br/>" +
                    "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type='text' name='agent_request' placeholder='Enter your agent request here if needed' size='120'></p>"
            }
        }
        document.getElementById('vendor_inv_included').innerHTML = html;
        this.#totalAmountDue = Number(this.#totalSpacePrice);
        this.#totalInvCost.innerHTML = Number(this.#totalSpacePrice).toFixed(2);
        document.getElementById('vendorSpacePrice').value = this.#totalSpacePrice;document.getElementById('vendor_inv_region_id').value = regionYearId;

       this.#membershipCostdiv.hidden = (this.#includedMemberships == 0 && this.#additionalMemberships == 0);

        html = '';
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
            switch (config.required) {
                case 'all':
                    this.#allStar = '<span class="text-danger">&bigstar;</span>';
                case 'addr':
                    this.#addrStar = '<span class="text-danger">&bigstar;</span>';
                case 'first':
                    this.#firstStar = '<span class="text-danger">&bigstar;</span>';
            }
        }
        if (this.#includedMemberships > 0) {
            html += "<input type='hidden' name='incl_mem_count' value='" + this.#includedMemberships + "'>\n" +
                "<div class='container-fluid'>\n" +
                "<div class='row'><div class='col-sm-auto p-2 pe-0'><strong>Included Memberships: (up to " + this.#includedMemberships + ")</strong>" +
                "<input type='hidden' name='includedMemberships' value='" + String(this.#includedMemberships) + "'></div></div>";
            for (var mnum = 0; mnum < this.#includedMemberships; mnum++) {
                // name fields including legal name
                html += this.#drawMembershipBlock('Included', mnum, '_i_' + mnum, country_options, false);
            }
            html += "<hr/>";
        }

        // now build the additional memberships
        if (this.#additionalMemberships > 0) {
            html += "<input type='hidden' name='addl_mem_count' value='" + this.#additionalMemberships + "'>\n" +
                "<div class='row'><div class='col-sm-auto p-2 pe-0'><strong>Additional Memberships: (up to " + this.#additionalMemberships + ")</strong>" +
                "<input type='hidden' name='additionalMemberships' value='" + String(this.#additionalMemberships) + "'></div></div>";
            for (var mnum = 0; mnum < this.#additionalMemberships; mnum++) {
                // name fields includeing legal name
                html += this.#drawMembershipBlock('Additional', mnum, '_a_' + mnum, country_options, true);
            }
            html += "</div><hr/>";
        }
        document.getElementById("vendor_inv_included_mbr").innerHTML = html;
        // fill in default information for the values of the addresses
        for (mnum = 0; mnum < this.#includedMemberships; mnum++) {
            document.getElementById('addr_i_' + mnum).value = exhibitor_info.addr;
            document.getElementById('addr2_i_' + mnum).value = exhibitor_info.addr2;
            document.getElementById('city_i_' + mnum).value = exhibitor_info.city;
            document.getElementById('state_i_' + mnum).value = exhibitor_info.state;
            document.getElementById('zip_i_' + mnum).value = exhibitor_info.zip;
            document.getElementById('country_i_' + mnum).value = exhibitor_info.country;
            document.getElementById('email_i_' + mnum).value = exhibitor_info.exhibitorEmail;
            document.getElementById('phone_i_' + mnum).value = exhibitor_info.exhibitorPhone;
        }
        for (mnum = 0; mnum < this.#additionalMemberships; mnum++) {
            document.getElementById('addr_a_' + mnum).value = exhibitor_info.addr;
            document.getElementById('addr2_a_' + mnum).value = exhibitor_info.addr2;
            document.getElementById('city_a_' + mnum).value = exhibitor_info.city;
            document.getElementById('state_a_' + mnum).value = exhibitor_info.state;
            document.getElementById('zip_a_' + mnum).value = exhibitor_info.zip;
            document.getElementById('country_a_' + mnum).value = exhibitor_info.country;
            document.getElementById('email_a_' + mnum).value = exhibitor_info.exhibitorEmail;
            document.getElementById('phone_a_' + mnum).value = exhibitor_info.exhibitorPhone;
        }
        this.#exhibitorInvoiceModal.show();
        this.#updatePaymentDiv();
    }

// draw a membership block
    #drawMembershipBlock(label, mnum, suffix, country_options, doOnChange) {
        var html = `
<div class="row mt-4">
    <div class="col-sm-auto p-0">` + label + ' Member ' + (mnum + 1) + `:</div>
</div>
<div class="row">
    <div class="col-sm-8">
        <div class="container-fluid">
            <div class="row">
                <div class="col-sm-auto ms-0 me-2 p-0">
                    <label for="fname` + suffix + `" class="form-label-sm"><span class="text-dark" style="font-size: 10pt;">` + this.#firstStar + `First Name</span></label><br/>
                    <input class="form-control-sm" type="text" name="fname` + suffix + `" id="fname` + suffix +
                        '" size="22" maxlength="32"' + (doOnChange ? 'onchange="exhibitorInvoice.updateCost(' + this.#regionYearId + "," + mnum + ');"' : '') + `/>
                </div>
                <div class="col-sm-auto ms-0 me-2 p-0">
                    <label for="mname` + suffix + `" class="form-label-sm"><span class="text-dark" style="font-size: 10pt;">Middle Name</span></label><br/>
                    <input class="form-control-sm" type="text" name="mname` + suffix + `" id="mname` + suffix + `" size="8" maxlength="32" />
                </div>
                <div class="col-sm-auto ms-0 me-2 p-0">
                    <label for="lname` + suffix + `" class="form-label-sm"><span class="text-dark" style="font-size: 10pt;">` + this.#allStar + `Last Name</span></label><br/>
                    <input class="form-control-sm" type="text" name="lname` + suffix + `" id="lname` + suffix + `" size="22" maxlength="32" />
                </div>
                <div class="col-sm-auto ms-0 me-0 p-0">
                    <label for="suffix` + suffix + `" class="form-label-sm"><span class="text-dark" style="font-size: 10pt;">Suffix</span></label><br/>
                    <input class="form-control-sm" type="text" name="suffix` + suffix + `" id='suffix` + suffix + `' size="4" maxlength="4" />
                </div>
            </div>
            <div class='row'>
                <div class='col-sm-12 ms-0 me-0 p-0'>
                    <label for="legalName` + suffix + `" class='form-label-sm'><span class='text-dark' style='font-size: 10pt;'>Legal Name: for checking against your ID. It will only be visible to Registration Staff.</label><br/>
                    <input class='form-control-sm' type='text' name="legalName` + suffix + `" id=legalName` + suffix + `" size=64 maxlength='64' placeholder='Defaults to First Name Middle Name Last Name, Suffix'/>
                </div>
            </div>
`;
                // address fields
                html += `
            <div class="row">
                <div class="col-sm-12 ms-0 me-0 p-0">
\                    <label for="addr` + suffix + `" class="form-label-sm"><span class="text-dark" style="font-size: 10pt;">` + this.#addrStar + `Address</span></label><br/>
                    <input class="form-control-sm" type="text" name='addr` + suffix + `' id='addr` + suffix + `' size=64 maxlength="64" />
                </div>
            </div>
            <div class="row">
                <div class="col-sm-12 ms-0 me-0 p-0">
                    <label for="addr2` + suffix + `" class="form-label-sm"><span class="text-dark" style="font-size: 10pt;">Company/2nd Address line</span></label><br/>
                    <input class="form-control-sm" type="text" name='addr2` + suffix + `' id='addr2` + suffix + `' size=64 maxlength="64" '/>
                </div>
            </div>
            <div class="row">
                <div class="col-sm-auto ms-0 me-2 p-0">
                    <label for="city` + suffix + `" class="form-label-sm"><span class="text-dark" style="font-size: 10pt;">` + this.#addrStar + `City</span></label><br/>
                    <input class="form-control-sm" type="text" name="city` + suffix + `" id='city` + suffix + `' size="22" maxlength="32" />
                </div>
                <div class="col-sm-auto ms-0 me-2 p-0">
                    <label for="state` + suffix + `" class="form-label-sm"><span class="text-dark" style="font-size: 10pt;">` + this.#addrStar + `State</span></label><br/>
                    <input class="form-control-sm" type="text" name="state` + suffix + `" id='state` + suffix + `' size="10" maxlength=16" />
                </div>
                <div class="col-sm-auto ms-0 me-2 p-0">
                    <label for="zip` + suffix + `" class="form-label-sm"><span class="text-dark" style="font-size: 10pt;">` + this.#addrStar + `Zip</span></label><br/>
                    <input class="form-control-sm" type="text" name="zip` + suffix + `" id='zip` + suffix + `' size="5" maxlength="10" />
                </div>
                <div class="col-sm-auto ms-0 me-0 p-0">
                    <label for="country` + suffix + `" class="form-label-sm"><span class="text-dark" style="font-size: 10pt;">Country</span></label><br/>
                    <select class="form-control-sm" name="country` + suffix + `" id='country` + suffix + `' >
            ` + country_options + `
                    </select>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-4" id="uspsBlock` + suffix + `"></div>
</div>
<div class="row">
    <div class="col-sm-12">
        <div class="container-fluid">
            <div class="row">
                <div class="col-sm-auto ms-0 me-2 p-0">
                    <label for="email` + suffix + `" class="form-label-sm"><span class="text-dark" style="font-size: 10pt;">` + this.#firstStar + `Email</span></label><br/>
                    <input class="form-control-sm" type="email" name="email` + suffix + `" id='email` + suffix + `' size="35" maxlength="254" />
                </div>
                <div class="col-sm-auto ms-0 me-2 p-0">
                    <label for="phone` + suffix + `" class="form-label-sm"><span class="text-dark" style="font-size: 10pt;">Phone</span></label><br/>
                    <input class="form-control-sm" type="text" name="phone` + suffix + `" id='phone` + suffix + `' size="18" maxlength="15" />
                </div>
                <div class="col-sm-auto ms-0 p-0">
                    <label for="badgename` + suffix + `" class="form-label-sm"><span class="text-dark" style="font-size: 10pt;">Badge Name (optional)</span></label><br/>
                    <input class="form-control-sm" type="text" name="badgename` + suffix + `" id='badgename` + suffix + `' size="35" maxlength="32"  placeholder='defaults to first and last name'/>
                </div>
            </div>
        </div>
    </div>
</div>
`;
    return html;
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
        if (config.debug & 1)
            console.log('Pre this.#totalSpacePrice: ' + String(this.#totalAmountDue));
        this.#totalMembershipCost.innerHTML = Number(this.#totalAmountDue).toFixed(2);
        this.#totalAmountDue += Number(this.#totalSpacePrice);
        if (config.debug & 1)
            console.log('After adding this.#totalSpacePrice: ' + String(this.#totalAmountDue));
        this.#totalInvCost.innerHTML = Number(this.#totalAmountDue).toFixed(2);
        this.#updatePaymentDiv();
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

        if (prow == null && this.#totalAmountDue > 0) {
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
                this.#currentSuffix = '_i_' + mnum;
                if (document.getElementById('fname' + this.#currentSuffix).value != '' ||
                    document.getElementById('lname' + this.#currentSuffix).value != '') {
                    if (!this.#checkValid(this.#currentSuffix))
                        valid = false;
                }
            }
            for (mnum = 0; mnum < this.#additionalMemberships; mnum++) {
                this.#currentSuffix = '_a_' + mnum;
                if (document.getElementById('fname' + this.#currentSuffix).value != '' ||
                    document.getElementById('lname' + this.#currentSuffix).value != '') {
                    if (!this.#checkValid(this.#currentSuffix))
                        valid = false;
                }
            }

            if (!valid) {
                show_message('Please correct the items marked in yellow to process the payment.' +
                    '<br/>For fields in the membership area that are required and not available, use /r to indicate not available.',
                    'warn', 'inv_result_message')
                return;
            }

            // fields are now validated, apply USPS validation to each item?
            if (config.useUSPS) {
                // now validate the membership fields
                for (mnum = 0; mnum < this.#includedMemberships; mnum++) {
                    this.#currentSuffix = '_i_' + mnum;
                    if (document.getElementById('fname' + this.#currentSuffix).value != '' ||
                        document.getElementById('lname' + this.#currentSuffix).value != '') {
                        if (this.#checkMembershipUSPS(this.#currentSuffix))
                            return;
                    }
                }
                for (mnum = 0; mnum < this.#additionalMemberships; mnum++) {
                    this.#currentSuffix = '_a_' + mnum;
                    if (document.getElementById('fname' + this.#currentSuffix).value != '' ||
                        document.getElementById('lname' + this.#currentSuffix).value != '') {
                        if (this.#checkMembershipUSPS(this.#currentSuffix))
                            return;
                    }
                }
            }

            if (pay_amt > 0) {
                var prow = {
                    index: 2, amt: pay_amt, ccauth: ccauth, checkno: checkno, desc: this.#payDescription.value, type: ptype, nonce: 'offline',
                };
            }
        }
        // process payment

        this.#payButton.disabled = true;
        var formData = $('#vendor_invoice_form').serialize()
        formData += "&nonce=" + 'admin&amtDue=' + this.#totalAmountDue;
        clear_message('inv_result_message');
        $.ajax({
            url: 'scripts/exhibitorsSpacePayment.php',
            method: 'POST',
            data: formData,
            success: function(data, textStatus, jqXhr) {
                exhibitorInvoice.paySuccess(data);
            },
            error: function (jqXHR, textStatus, errorThrown) {
                showAjaxError(jqXHR, textStatus, errorThrown, 'inv_result_message');
                exhibitorInvoice.enablePayButton();
                return false;
            }
        });
    }

    // enablePayButton - for AJAX, re-enable the pay button
        enablePayButton() {
        this.#payButton.disabled = false;
    }

    // pay succeedd - deal with it
        paySuccess(data) {
            if (config.debug & 1)
                console.log(data);
            if (data.error) {
                show_message(data.error, 'error', 'inv_result_message');
                this.#payButton.disabled = false;
            } else if (data.status == 'error') {
                show_message(data.data, 'error', 'inv_result_message');
                this.#payButton.disabled = false;
            } else if (data.status == 'success') {
                this.#exhibitorInvoiceModal.hide();
                show_message(data.message + "Payment for space recorded.");
                if (data.exhibitor_spacelist) {
                    exhibitor_spacelist = data.exhibitor_spacelist;
                }
                exhibitors.open(fulltabname);
            } else {
                show_message('There was an unexpected error, please email ' + config.vemail + ' to let us know.  Thank you.', 'error', 'inv_result_message');
                this.#payButton.disabled = false;
            }
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

        // if first name or last name is set, do the check, else it's not in use, skip it

        if (config.required != '') {
            if (!this.#checkNonBlank(document.getElementById('fname' + suffix)))
                valid = false;
        }

        if (config.required == 'all') {
            if (!this.#checkNonBlank(document.getElementById('lname' + suffix)))
                valid = false;
        }

        if (config.required == 'all' || config.required == 'addr') {
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
    #checkMembershipUSPS(suffix) {
        if (this.#uspsChecked[suffix])  // don't check it twice if we get all the way through the check on it.
            return false;

        var country = document.getElementById('country' + suffix);
        var state = document.getElementById('state' + suffix).value;
        if (country.value != 'USA' && state.value != '/r') {
            this.#uspsChecked[suffix] = true;
            return false;
        }

        // get address fields
        var addr = document.getElementById('addr' + suffix).value;
        var addr2 = document.getElementById('addr2' + suffix).value;
        var city = document.getElementById('city' + suffix).value;
        var zip = document.getElementById('zip' + suffix).value;

        var script = "scripts/uspsCheck.php";
        var data = {
            addr: addr,
            addr2: addr2,
            city: city,
            state: state,
            zip: zip,
        };
        $.ajax({
            url: script,
            data: data,
            method: 'POST',
            success: function (data, textStatus, jqXhr) {
                if (data.status == 'error') {
                    show_message(data.message, 'error', 'inv_result_message');
                    return false;
                }
                exhibitorInvoice.showValidatedAddress(data);
                return true;
            },
            error: function (jqXHR, textStatus, errorThrown) {
                showAjaxError(jqXHR, textStatus, errorThrown, 'inv_result_message');
                return false;
            },
        })
        return true;
    }

    // display the usps result
    showValidatedAddress(data) {
        var html = '';
        clear_message('inv_result_message');
        if (data.error) {
            var errormsg = data.error;
            if (errormsg.substring(0, 5) == '400: ') {
                errormsg = errormsg.substring(5);
            }
            html = "<h4>USPS Returned an error<br/>validating the address</h4>" +
                "<pre>" + errormsg + "</pre>\n";
        } else {
            this.#uspsAddress = data.address;
            if (this.#uspsAddress.address2 == undefined)
                this.#uspsAddress.address2 = '';

            html = "<h4>USPS Returned: " + this.#uspsAddress.valid + "</h4>";
            // ok, we got a valid uspsAddress, if it doesn't match, show the block
            var orig = data.post;
            if (orig.addr == this.#uspsAddress.address && orig.addr2 == this.#uspsAddress.address2 &&
                orig.city == this.#uspsAddress.city && orig.state == this.#uspsAddress.state &&
                orig.zip == this.#uspsAddress.zip) {
                this.useMyAddress();
                return;
            }

            html += "<pre>" + this.#uspsAddress.address + "\n";
            if (this.#uspsAddress.address2)
                html += this.#uspsAddress.address2 + "\n";
            html += this.#uspsAddress.city + ', ' + this.#uspsAddress.state + ' ' + this.#uspsAddress.zip + "</pre>\n";

            if (this.#uspsAddress.valid == 'Valid')
                html += '<button class="btn btn-sm btn-primary m-1 mb-2" onclick="exhibitorInvoice.useUSPS();">' +
                    'Update using the USPS validated address' +
                    '</button>'
        }
        html += '<button class="btn btn-sm btn-secondary m-1 mb-2 " onclick="exhibitorInvoice.useMyAddress();">' +
            'Update using the address as Entered' +
            '</button><br/>' +
            '<button class="btn btn-sm btn-secondary m-1 mt-2" onclick="exhibitorInvoice.redoAddress();">' +
            'I fixed the address, validate it again' +
            '</button>';

        this.#uspsDiv = document.getElementById('uspsBlock' + this.#currentSuffix);
        this.#uspsDiv.innerHTML = html;
        this.#uspsDiv.scrollIntoView({behavior: 'instant', block: 'center'});
    }

// address update functions
    // usps address post functions
    useUSPS() {
        document.getElementById('addr' + this.#currentSuffix).value = this.#uspsAddress.address;
        var a2 = document.getElementById('addr2' + this.#currentSuffix);
        if (this.#uspsAddress.address2)
            a2.value = this.#uspsAddress.address2;
        else
            a2.value = '';
        document.getElementById('city' + this.#currentSuffix).value = this.#uspsAddress.city;
        document.getElementById('state' + this.#currentSuffix).value = this.#uspsAddress.state;
        document.getElementById('zip' + this.#currentSuffix).value = this.#uspsAddress.zip;
        this.#uspsDiv.innerHTML = '';
        this.pay();
    }

    useMyAddress() {
        this.#uspsDiv.innerHTML = '';
        this.#uspsChecked[this.#currentSuffix] = true;
        this.pay();
    }

    redoAddress() {
        this.#uspsDiv.innerHTML = '';
        this.pay();
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
                } else if (data.error !== undefined) {
                    show_message(data.error, 'error');
                } else if (data.message !== undefined) {
                    show_message(data.message, 'success');
                } else if (data.warn !== undefined) {
                    show_message(data.warn, 'success');
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