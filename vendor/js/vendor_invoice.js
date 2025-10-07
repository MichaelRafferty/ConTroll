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
    var tabindex = 200;

    regionYearId = id;
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
    var spacePriceName = '';

    // fill in the variable items
    document.getElementById("vendor_invoice_title").innerHTML = "<strong>Pay " + regionName + ' Invoice</strong>';

    // refresh the items spaces purchased area
    var ret = drawExhitorTopBlocks('You', exhibitor_spacelist, region, regionList, regionYearId,
        'vendor_inv_approved_for', 'vendor_inv_included', 'vendor_inv_included_mbr');
    includedMemberships = ret[0];
    additionalMemberships = ret[1];
    spacePriceName = ret[2];
    totalSpacePrice = ret[3];
    document.getElementById('vendor_inv_cost').innerHTML = Number(totalSpacePrice).toFixed(2);
    document.getElementById('vendorSpacePrice').value = totalSpacePrice;
    document.getElementById('vendor_inv_region_id').value = regionYearId;

    membershipCostdiv.hidden =  (includedMemberships == 0 && additionalMemberships == 0) ;
    vendor_invoice.show();
    setTimeout(() => { document.getElementById('agreeNone').focus({focusVisible: true}); }, 600);
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
    clear_message('inv_result_message');
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
        spaceStatus += "<button class='btn btn-primary m-1' onclick='auctionItemRegistration.open(regionYearId);'>Open Item Registration</button>";
    }
    blockdiv.innerHTML = '<div class="col-sm-auto p-0">You have purchased:<br/>' + spaceStatus + "</div>";

}
