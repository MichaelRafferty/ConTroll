// items related to requesting space (not approvals)
var vendor_request = null;
var vendor_req_btn = null;
var totalUnitsRequested_div = null;
var totalUnitsRequestedRow = null;

// init
function vendorRequestOnLoad() {
    id = document.getElementById('vendor_req');
    if (id != null) {
        vendor_request = new bootstrap.Modal(id, {focus: true, backdrop: 'static'});
    }
}

// openReq - update the modal for this space
//      add all of the spaces in this 'region'
function openReq(regionYearId, cancel) {
    var spaceHtml = '';
    var regionName = '';
    var totalUnitsRequested = 0;

    //console.log("open request modal for id =" + spaceid);
    var region = exhibits_spaces[regionYearId];

    if (!region)
        return;

    var regionList = region_list[regionYearId];
    if (config['debug'] & 1) {
        console.log("regionList");
        console.log(regionList);
        console.log("Region Spaces");
        console.log(region);
    }

    regionName = regionList.name;
    var mailIn = vendor_info.mailin == 'Y';
    var unitLimit = mailIn ? regionList.mailinMaxUnits : regionList.inPersonMaxUnits;
    if (config['debug'] & 1) {
        console.log("Unit limit: " + unitLimit);
    }

    // determine number of spaces in the region
    var keys = Object.keys(region);
    var spaceCount = keys.length;
    if (spaceCount == 0)
        return;

    var colWidth = 4;
    if (spaceCount < 3) {
        colWidth = 12/spaceCount;
    }
    var index = 0;
    var col = 0;
    var last = 0;
    var space = null;
    var req_item = -1;
    var sel = '';

    if (mailIn) {
        spaceHtml += "<div class='row'>\n<div class='col-sm-12 p-0 m-2'><i>You are requesting space as mail-in. " +
            "If this is not correct, please dismiss this form using the 'Cancel' button in grey below and update your profile.</i></div>"
    }

    for (index = 0; index < spaceCount; index += 3) { // look over the spaces up to 3 per row
        spaceHtml += "<div class='row'>\n";
        last = index + 3;
        if (last > spaceCount)
            last = spaceCount;
        for (col = index; col < last; col++) {
            space = region[keys[col]];
            reg_item = -1;
            exSpace = exhibitor_spacelist[keys[col]];
            if (exSpace)
                req_item = exSpace.item_requested;

            // build option pulldown
            var options = "<option value='-1'" + (reg_item == -1 ? ' selected>' : '>') + (cancel ? 'Cancel' : 'No') + " Space Requested</option>\n";
            var prices = space.prices;
            var price_keys = Object.keys(prices).sort();
            var units =  '';
            for (var priceid in price_keys) {
                var price = prices[price_keys[priceid]];
                if (price.requestable == 1 && (price.units <= unitLimit || unitLimit == 0)) {
                    if (unitLimit > 0) {
                        units = ' (' + String(price.units) + ' unit' + (price.units > 1 ? 's' : '') + ')';
                    }
                    sel = "'>";
                    if (exSpace) {
                        if (exSpace.item_requested == price.id) {
                            totalUnitsRequested += Number(price.units);
                            sel = "' selected>";
                        }
                    }
                    options += "<option value='" + price.id + sel + price.description + ' for ' + Number(price.price).toFixed(2) + units + "</option>\n";
                }
            }

            // now build block
            spaceHtml += "<div class='col-sm-" + colWidth + " p-0 m-0'>\n" +
                "<div class='container-fluid'><div class='container-fluid m-1 border border-3 border-primary'>\n" +
                "<div class='row'><div class='col-sm-12 p-0 m-0' style='text-align: center;'>\n" + space['spaceName'] + "</div></div>\n" +
                "<div class='row'><div class='col-sm-12 p-2 m-0'>\n" + space['description'] + "</div></div>\n" +
                "<div className='row p-1'><div className='col-sm-auto p-0 pe-2'>\n" +
                "<label htmlFor='vendor_req_price_id'>How many spaces are you requesting?</label>\n" +
                "</div>\n" +
                "<div className='col-sm-auto p-0'>\n" +
                "<select name='vendor_req_price_id_" + keys[col] +  "' id='vendor_req_price_id_" + keys[col] + "' onchange='updateTotalUnits(" + regionYearId + "," + unitLimit + ");'>\n" +
                options + "\n</select></div></div>\n" +
            "</div></div></div>\n";
        }
        spaceHtml += "</div>\n";
        // now check if we need to track limits
    }
    // add until limit if needed
     spaceHtml += "<div class='row mt-2' id='TotalUnitsRequestedRow'" + (unitLimit > 0 ? '' : ' hidden') + ">\n<div class='col-sm-auto p-0 m-0 ms-4'><b>Total Requestable unit limit: " + String(unitLimit) + "</b></div>\n" +
         "<div class='col-sm-auto p-0 m-0 ms-4'><b>Total Units Requested:</b></div>" +
         "<div class='col-sm-auto p-0 m-0 ms-2' id='totalUnitsRequested'>" + totalUnitsRequested + "</div>\n" +
         "</div>\n";

    document.getElementById("spaceHtml").innerHTML = spaceHtml;

    // update fields
    document.getElementById("vendor_req_title").innerHTML = "<strong>" + (cancel ? 'Change/Cancel ' : '') + regionName + ' Space Request</strong>';
    document.getElementById("vendor_req_btn").innerHTML = (cancel ? "Change/Cancel " : "Request ") + regionName + ' Space';
    var selection = document.getElementById('vendor_req_price_id');
    //selection.innerHTML = options;
    //if (cancel) selection.value = cancel;
    document.getElementById('vendor_req_btn').setAttribute('onClick', "spaceReq(" + regionYearId + ',' + cancel + ')');
    vendor_request.show();
}

// updateTotalUnits -update the total units requested pulldown and color it if it's too large
function updateTotalUnits(regionYearId, unitLimit) {
    var region = exhibits_spaces[regionYearId];
    if (!region)
        return;

    var requestedUnits = 0;
    var keys = Object.keys(region);
    var field;
    var id;
    for (var key in keys) {
        var priceId = keys[key];

        field = document.getElementById('vendor_req_price_id_' + String(keys[key]));
        var value = field.value;
        if (value > 0) {
            var prices = region[keys[key]].prices;
            for (var priceIdx in prices) {
                if (prices[priceIdx].id == value) {
                    requestedUnits += Number(prices[priceIdx].units);
                }
            }
        }
    }
    if (totalUnitsRequested_div == null)
        totalUnitsRequested_div = document.getElementById('totalUnitsRequested');

    totalUnitsRequested_div.innerHTML = String(requestedUnits);

    var mailIn = vendor_info.mailin == 'Y';
    var regionList = region_list[regionYearId];
    var unitLimit = mailIn ? regionList.mailinMaxUnits : regionList.inPersonMaxUnits;
    if (totalUnitsRequestedRow == null) {
        totalUnitsRequestedRow = document.getElementById('TotalUnitsRequestedRow');
        vendor_req_btn = document.getElementById('vendor_req_btn');
    }
    if (requestedUnits > unitLimit && unitLimit > 0) {
        totalUnitsRequestedRow.classList.add('bg-warning');
        vendor_req_btn.disabled = true;
    } else {
        totalUnitsRequestedRow.classList.remove('bg-warning');
        vendor_req_btn.disabled = false;
    }
}

// Space Request - call scripts/spaceRequest.php to add a request record
function spaceReq(regionYearId, cancel) {
    //console.log("spaceReq called for " + spaceId);

    if (totalUnitsRequested_div == null)
        totalUnitsRequested_div = document.getElementById('totalUnitsRequested');

    if (Number(totalUnitsRequested_div.innerHTML) <= 0 && !cancel) {
        show_message("Select an amount of space to request", 'error', 'sr_message_div');
        return;
    }

    clear_message('sr_message_div');
    clear_message();
    dataobj = {
        regionYearId: regionYearId,
        requests: $('#vendor_req_form').serialize(),
        'type': config['portalType'],
        'name': config['portalName'],
    };
    $.ajax({
        url: 'scripts/spaceReq.php',
        data: dataobj,
        method: 'POST',
        success: function (data, textstatus, jqxhr) {
            if (config['debug'] & 1)
                console.log(data);
            if (data['error'] !== undefined) {
                show_message(data['error'], 'error', 'sr_message_div');
                return;
            }
            if (data['exhibitor_spacelist']) {
                exhibitor_spacelist = data['exhibitor_spacelist'];
            }
            if (data['success'] !== undefined) {
                vendor_request.hide();
                show_message(data['success'], 'success');
                updateRequestStatusBlock(regionYearId);
            }
            if (data['warn'] !== undefined) {
                show_message(data['warn'], 'warn', 'sr_message_div');
            }
        },
        error: showAjaxError
    })
}

// update the request status block to show the new request
function updateRequestStatusBlock(regionYearId) {
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
            if (space.item_requested) {
                var timeRequested = new Date(space.time_requested)
                spaceStatus += space.requested_description + " in " + regionName + " for $" + Number(space.requested_price).toFixed(2) +
                    " at " + timeRequested + "<br/>";
            }
        }
    }

    if (spaceStatus == '') {
        blockdiv.innerHTML = "<div class=\"col-sm-auto p-0\"><button class='btn btn-primary' onclick = 'openReq(regionYearId, 0);' > Request " + regionName + " Space</button></div>";
        return;
    }

    blockdiv.innerHTML = '<div class="col-sm-auto p-0">Request pending authorization for:<br/>' + spaceStatus +
        "<button class='btn btn-primary' onclick = 'openReq(" + regionYearId + ", 1);' > Change/Cancel " + regionName + " Space</button></div>";
}
