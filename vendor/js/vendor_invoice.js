/*
 *  exhibitor portals - invoice processing
 */

class Vendor_invoice {
    // Items related to building and paying the vendor invoice
    #vendorVnvoice = null;
    #regionYearId = null;
    #membershipCostdiv = null;
    #regionName = '';
    #includedMemberships = 0;
    #additionalMemberships = 0;
    #additionalCost = [];
    #totalSpacePrice = 0;

    constructor() {
        let id = document.getElementById('vendor_invoice');
        if (id != null) {
            this.#vendorVnvoice = new bootstrap.Modal(id, {focus: true, backdrop: 'static'});
        }
        this.#membershipCostdiv = document.getElementById("membershipCost");
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
        let region = exhibits_spaces[regionYearId];

        let regionList = region_list[regionYearId];
        /*
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

         */
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
        var ret = drawExhitorTopBlocks('You', exhibitor_spacelist, region, regionList, regionYearId,
            'vendor_inv_approved_for', 'vendor_inv_included', 'vendor_inv_included_mbr');
        this.#includedMemberships = ret[0];
        this.#additionalMemberships = ret[1];
        spacePriceName = ret[2];
        this.#totalSpacePrice = ret[3];
        document.getElementById('vendor_inv_cost').innerHTML = currencyFmt.format(Number(this.#totalSpacePrice).toFixed(2));
        document.getElementById('vendorSpacePrice').value = this.#totalSpacePrice;
        document.getElementById('vendor_inv_region_id').value = this.#regionYearId;

        membershipCostdiv.hidden = (this.#includedMemberships == 0 && this.#additionalMemberships == 0);
        vendor_invoice.show();
        setTimeout(() => {
            document.getElementById('agreeNone').focus({focusVisible: true});
        }, 600);
    }

    // update invoice for the Cost of Memberships and total Cost when an additional member is started
    updateCost(regionYearId, item) {
        let regionList = region_list[regionYearId];
        let price = Number(regionList.additionalMemPrice);
        let fname = document.getElementById('fname_a_' + item).value;
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
        if (label && label != '') {
            purchase_label = label;
        }
        if (!token)
            token = 'test';

        if (token == 'test_ccnum') {  // this is the test form
            token = document.getElementById(token).value;
        }

        let submitId = document.getElementById(purchase_label);
        submitId.disabled = true;
        let formData = $('#vendor_invoice_form').serialize()
        formData += "&nonce=" + token;
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
                    let submitId = document.getElementById(purchase_label);
                    submitId.disabled = false;
                } else if (data['status'] == 'error') {
                    show_message(data['data'], 'error', 'inv_result_message');
                    let submitId = document.getElementById(purchase_label);
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
                    let submitId = document.getElementById(purchase_label);
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
