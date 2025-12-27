/*
 *  exhibitor portals - invoice processing
 */

class VendorInvoice {
    // Items related to building and paying the vendor invoice
    #vendorInvoice = null;
    #regionYearId = null;
    #membershipCostDiv = null;
    #regionName = '';
    #includedMemberships = 0;
    #additionalMemberships = 0;
    #additionalCost = [];
    #totalSpacePrice = 0;
    #currentPrefix = null;
    #currentType = null;
    #currentOrdinal = null;
    #formValid = false;
    #validateMessage = '';
    #token = null;
    #purchaseLabel = null;

    constructor() {
        let id = document.getElementById('vendor_invoice');
        if (id != null) {
            this.#vendorInvoice = new bootstrap.Modal(id, {focus: true, backdrop: 'static'});
        }
        this.#membershipCostDiv = document.getElementById("membershipCost");
    }

    // openInvoice: display the vendor invoice (and registration items)
    openInvoice(id) {
        this.#regionName = '';
        this.#includedMemberships = 0;
        this.#additionalMemberships = 0;
        let html = '';
        let tabindex = 200;

        this.#regionYearId = id;
        if (config['debug'] & 1)
            console.log("regionYearId: " + this.#regionYearId);
        let region = exhibits_spaces[this.#regionYearId];
        let regionList = region_list[this.#regionYearId];
        if (config['debug'] & 1) {
            console.log("regionList");
            console.log(regionList);
            console.log("Region Spaces");
            console.log(region);
        }

        this.#regionName = regionList.name;
        let spacePriceName = '';
        this.#totalSpacePrice = 0;

        // fill in the variable items
        document.getElementById("vendor_invoice_title").innerHTML = "<strong>Pay " + this.#regionName + ' Invoice</strong>';

        // refresh the items spaces purchased area
        var ret = drawExhitorTopBlocks('You', exhibitor_spacelist, region, regionList, this.#regionYearId,
            'vendor_inv_approved_for', 'vendor_inv_included', 'vendor_inv_included_mbr');
        this.#includedMemberships = ret[0];
        this.#additionalMemberships = ret[1];
        spacePriceName = ret[2];
        this.#totalSpacePrice = ret[3];
        document.getElementById('vendor_inv_cost').innerHTML = currencyFmt.format(Number(this.#totalSpacePrice).toFixed(2));
        document.getElementById('vendorSpacePrice').value = this.#totalSpacePrice;
        document.getElementById('vendor_inv_region_id').value = this.#regionYearId;

        this.#membershipCostDiv.hidden = (this.#includedMemberships == 0 && this.#additionalMemberships == 0);
        this.#vendorInvoice.show();
        setTimeout(() => {
            document.getElementById('agreeNone').focus({focusVisible: true});
        }, 600);
    }

    // update invoice for the Cost of Memberships and total Cost when an additional member is started
    updateCost(regionYearId, item) {
        let regionList = region_list[regionYearId];
        let price = Number(regionList.additionalMemPrice);
        let fname = document.getElementById('a_' + item + '_fname').value;
        let cost = 0;
        this.#additionalCost[item] = fname == '' ? 0 : Number(regionList.additionalMemPrice);
        for (var num in this.#additionalCost) {
            cost += this.#additionalCost[num];
        }
        if (config['debug'] & 1)
            console.log('Pre totalSpacePrice: ' + String(cost));
        document.getElementById('vendor_inv_mbr_cost').innerHTML = currencyFmt.format(Number(cost).toFixed(2));
        cost += Number(this.#totalSpacePrice);
        if (config['debug'] & 1)
            console.log('After adding totalSpacePrice: ' + String(cost));
        document.getElementById('vendor_inv_cost').innerHTML = currencyFmt.format(Number(cost).toFixed(2));
    }

    // submit the invoice for payment processing
    makePurchase(token, label) {
        this.#token = token;
        this.#purchaseLabel = label;
        this.#currentOrdinal = 0;
        this.#currentType = 'i';
        this.payValidate();
    }


    payValidate() {
        clear_message('inv_result_message');
        this.#validateMessage = '';
        if (this.#currentType == 'i') {
            while (this.#currentOrdinal < this.#includedMemberships) {
                this.#currentPrefix = 'i_' + this.#currentOrdinal + '_';
                if (document.getElementById(this.#currentPrefix + 'fname').value != '' ||
                    document.getElementById(this.#currentPrefix + 'lname').value != '') {
                    let message = inclProfiles[this.#currentOrdinal].validate(null, 'inv_result_message', payValidate, payValidate, '', true);
                    if (message != '') {
                        this.#formValid = false;
                        this.#validateMessage += '<br/>&nbsp;<br/>For included member ' +  (this.#currentOrdinal + 1) + message;
                    }
                }
                this.#currentOrdinal++;
            }
            this.#currentType = 'a'
            this.#currentOrdinal = 0;
        }

        while (this.#currentOrdinal < this.#additionalMemberships) {
            this.#currentPrefix = 'a_' + this.#currentOrdinal + '_';
            if (document.getElementById(this.#currentPrefix + 'fname').value != '' ||
                document.getElementById(this.#currentPrefix + 'lname').value != '') {
                let message = addlProfiles[this.#currentOrdinal].validate(null, 'inv_result_message', payValidate, payValidate, '', true);
                if (message != '') {
                    this.#formValid = false;
                    this.#validateMessage += '<br/>&nbsp;<br/>For additional member ' +  (this.#currentOrdinal + 1) + message;
                }
            }
            this.#currentOrdinal++;
        }

        let cc_fields = {
            cc_fname: 'First Name',
            cc_lname: 'Last Name',
            cc_street: 'Street Address',
            cc_city: 'City',
            cc_state: 'State/Province',
            cc_zip: 'Zip Code / Postal Code',
            cc_phone: 'Phone Number',
        };
        let ccKeys = Object.keys(cc_fields);

        for (let index = 0; index < ccKeys.length; index++) {
            let field = document.getElementById(ccKeys[index]);
            let value = field.value;
            if (value == undefined || value == '') {
                let name = cc_fields[ccKeys[index]];
                this.#validateMessage += '<br/>The credit cart payment information field ' + name + ' is required and cannot be empty';
                this.#formValid = false;
                field.classList.add('need');
            } else {
                field.classList.remove('need');
            }
        }

        if (!this.#formValid) {
            show_message('Please correct the items marked in red to process the payment.' + this.#validateMessage, 'error', 'inv_result_message')
            return;
        }

        this.processPay();
    }

    processPay() {
        if (this.#purchaseLabel && this.#purchaseLabel != '') {
            this.#purchaseLabel = 'unknown';
        }
        if (!this.#token)
            this.#token = 'test';

        if (this.#token == 'test_ccnum') {  // this is the test form
            this.#token = document.getElementById(this.#token).value;
        }

        let submitId = document.getElementById(this.#purchaseLabel);
        submitId.disabled = true;
        let formData = $('#vendor_invoice_form').serialize()
        formData += "&nonce=" + this.#token;
        clear_message('inv_result_message');
        $.ajax({
            url: 'scripts/spacePayment.php',
            method: 'POST',
            data: formData,
            success: function (data, textStatus, jqXhr) {
                if (config['debug'] & 1)
                    console.log(data);
                if (data['error']) {
                    show_message(data['error'], 'error', 'inv_result_message');
                    let submitId = document.getElementById(this.#purchaseLabel);
                    submitId.disabled = false;
                } else if (data['status'] == 'error') {
                    show_message(data['data'], 'error', 'inv_result_message');
                    let submitId = document.getElementById(this.#purchaseLabel);
                    submitId.disabled = false;
                } else if (data['status'] == 'success') {
                    this.#vendorInvoice.hide();
                    show_message(data['message'] + "<p>Welcome to " + config['label'] + " Exhibitor Space. You may contact " + config['vemail'] +
                        " with any questions.  One of our coordinators will be in touch to help you get setup.</p>");
                    if (data['exhibitor_spacelist']) {
                        exhibitor_spacelist = data['exhibitor_spacelist'];
                    }
                    updatePaidStatusBlock();
                } else {
                    show_message('There was an unexpected error, please email ' + config['vemail'] + ' to let us know.  Thank you.', 'error', 'inv_result_message');
                    let submitId = document.getElementById(this.#purchaseLabel);
                    submitId.disabled = false;
                }
            }
        });
    }

    // update the paid status block to show the confirmed space
    updatePaidStatusBlock() {
        let blockname = region_list[regionYearId].shortname + '_div';
        let blockdiv = document.getElementById(blockname);

        // get the list item for this
        let region_spaces = exhibits_spaces[regionYearId];
        let spaceStatus = ''
        let exSpaceKeys = Object.keys(exhibitor_spacelist);
        for (let exSpaceIdx in exSpaceKeys) {
            if (region_spaces[exSpaceKeys[exSpaceIdx]]) { // space is in our region
                let region = region_spaces[exSpaceKeys[exSpaceIdx]];
                let space = exhibitor_spacelist[exSpaceKeys[exSpaceIdx]];
                if (space.item_purchased) {
                    let timePurchased = new Date(space.time_purchased)
                    spaceStatus += space.requested_description + " in " + this.#regionName + " for " +
                        currencyFmt.format(Number(space.requested_price).toFixed(2)) + " at " + timePurchased + "<br/>";
                }
            }
        }

        if (spaceStatus == '') {
            blockdiv.innerHTML = "<div class='col-sm-auto p-0'><button class='btn btn-primary' onclick = 'exhibitorRequest.openReq(regionYearId, 0);' > Request " +
                this.#regionName + " Space</button></div>";
            return;
        }

        spaceStatus += "<button class='btn btn-primary m-1' onclick='exhibitorReceipt.showReceipt(" + regionYearId + ");' > Show receipt for " +
            this.#regionName + " space</button>";
        if (region_list[regionYearId].portalType == 'artist') {
            spaceStatus += "<button class='btn btn-primary m-1' onclick='auctionItemRegistration.open(regionYearId);'>Open Item Registration</button>";
        }
        blockdiv.innerHTML = '<div class="col-sm-auto p-0">You have purchased:<br/>' + spaceStatus + "</div>";

    }
}

function updateCost(regionYearId, item) {
    if (vendorInvoice == null)
        return;

    vendorInvoice.updateCost(regionYearId, item);
}

function payValidate() {
    if (vendorInvoice == null)
        return;

    vendorInvoice.payValidate();
}
