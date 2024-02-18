// Exhibitor Request Space/approve space related functions
//  instance of the class must be a javascript variable names exhibitorProfile
class ExhibitorRequest {

// items related to requesting space (not approvals)
    #exhibitor_request = null;
    #exhibitor_req_btn = null;
    #totalUnitsRequested_div = null;
    #totalUnitsRequestedRow = null;
    #countCombined = false;
    #cancelType = 0;
    #regionYearId = 0;
    #unitLimit = 0;
    #unitsRequested = 0;

// init
    constructor() {
        var id = document.getElementById('exhibitor_req');
        if (id != null) {
            this.#exhibitor_request = new bootstrap.Modal(id, {focus: true, backdrop: 'static'});
            this.#exhibitor_req_btn = document.getElementById('exhibitor_req_btn');
        }
    }

// openReq - update the modal for this space
//      add all of the spaces in this 'region'

    openReq(regionYearId, cancel) {
        var spaceHtml = '';
        var regionName = '';
        var totalUnitsRequested = 0;

        this.#cancelType = cancel;
        this.#regionYearId = regionYearId;

        //console.log("open request modal for id =" + spaceid);
        var region = exhibits_spaces[regionYearId];

        if (!region)
            return;

        var regionList = region_list[regionYearId];
        this.#countCombined = regionList['purchaseAreaTotals'] == 'combined';
        if (config['debug'] & 1) {
            console.log("regionList");
            console.log(regionList);
            console.log("Region Spaces");
            console.log(region);
        }

        regionName = regionList.name;
        var mailIn = exhibitor_info.mailin == 'Y';
        this.#unitLimit = mailIn ? regionList.mailinMaxUnits : regionList.inPersonMaxUnits;
        if (config['debug'] & 1) {
            console.log("Unit limit: " + this.#unitLimit + ", countCombined: " + this.#countCombined);
        }

        // determine number of spaces in the region
        var keys = Object.keys(region);
        var spaceCount = keys.length;
        if (spaceCount == 0)
            return;

        var colWidth = 4;
        if (spaceCount < 3) {
            colWidth = 12 / spaceCount;
        }
        var index = 0;
        var col = 0;
        var last = 0;
        var space = null;
        var req_item = -1;
        var sel = '';

        if (mailIn) {
            if (cancel != 2) {
                spaceHtml += "<div class='row'>\n<div class='col-sm-12 p-0 m-2'><i>You are requesting space as mail-in. " +
                    "If this is not correct, please dismiss this form using the 'Cancel' button in grey below and update your profile.</i></div>"
            } else {
                spaceHtml += "<div class='row'>\n<div class='col-sm-12 p-0 m-2'><i>This exhibitor is requesting space as mail-in.</i></div>";
            }
        }

        for (index = 0; index < spaceCount; index += 3) { // look over the spaces up to 3 per row
            spaceHtml += "<div class='row'>\n";
            last = index + 3;
            if (last > spaceCount)
                last = spaceCount;
            for (col = index; col < last; col++) {
                space = region[keys[col]];
                var reg_item = -1;
                var exSpace = exhibitor_spacelist[keys[col]];
                if (exSpace) {
                    if (exSpace.item_approved)
                        req_item = exSpace.item_approved;
                    else
                        req_item = exSpace.item_requested;
                }

                // build option pulldown
                var options = "<option value='-1'" + (reg_item == -1 ? ' selected>' : '>') + (cancel ? 'Cancel' : 'No') + " Space Requested</option>\n";
                var prices = space.prices;
                var price_keys = Object.keys(prices).sort();
                var units = '';
                for (var priceid in price_keys) {
                    var price = prices[price_keys[priceid]];
                    if ((price.requestable == 1 && (price.units <= this.#unitLimit || this.#unitLimit == 0) || this.#cancelType == 2)) {
                        if (this.#unitLimit > 0) {
                            units = ' (' + String(price.units) + ' unit' + (price.units > 1 ? 's' : '') + ')';
                        }
                        sel = "'>";
                        if (exSpace) {
                            if (req_item == price.id) {
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
                    "<div class='row p-1'><div class='col-sm-auto p-0 pe-2'>\n" +
                    "<label htmlFor='exhibitor_req_price_id'>How many spaces are you requesting?</label>\n" +
                    "</div>\n" +
                    "<div class='col-sm-auto p-0'>\n" +
                    "<select name='exhbibitor_req_price_id_" + keys[col] + "' id='exhibitor_req_price_id_" + keys[col] + "' onchange='exhibitorRequest.updateTotalUnits(" + regionYearId + "," + this.#unitLimit + ");'>\n" +
                    options + "\n</select></div></div>\n" +
                    "</div></div></div>\n";
            }
            spaceHtml += "</div>\n";
            // now check if we need to track limits
        }
        // add until limit if needed
        spaceHtml += "</div>\n<div class='row mt-2' id='TotalUnitsRequestedRow'" + (this.#unitLimit > 0 && this.#countCombined ? '' : ' hidden') + ">\n" +
                "<div class='col-sm-auto p-0 m-0 ms-4'><b>Total Requestable unit limit: " + String(this.#unitLimit) + "</b></div>\n" +
                "<div class='col-sm-auto p-0 m-0 ms-4'><b>Total Units Requested:</b></div>" +
                "<div class='col-sm-auto p-0 m-0 ms-2' id='totalUnitsRequested'>" + totalUnitsRequested + "</div>\n" +
            "</div>\n";

        document.getElementById("spaceHtml").innerHTML = spaceHtml;
        this.#totalUnitsRequested_div = document.getElementById('totalUnitsRequested');
        this.#totalUnitsRequestedRow = document.getElementById('TotalUnitsRequestedRow');

        // update fields
        var prompt = ' ';
        if (cancel == 1)
            prompt = 'Change/Cancel ';
        if (cancel == 2)
            prompt = 'Approve ';
        document.getElementById("exhibitor_req_title").innerHTML = "<strong>" + prompt + regionName + ' Space Request</strong>';
        if (cancel == 0)
            prompt = 'Request';
        this.#exhibitor_req_btn.innerHTML = prompt + regionName + ' Space';
        var selection = document.getElementById('exhibibitor_req_price_id');
        //selection.innerHTML = options;
        //if (cancel) selection.value = cancel;
        this.#exhibitor_req_btn.setAttribute('onClick', "exhibitorRequest.spaceReq(" + regionYearId + ',' + cancel + ')');

        // see if over limit already
        this.#exhibitor_request.show();
        this.updateTotalUnits(this.#regionYearId);
    }

// updateTotalUnits -update the total units requested pulldown and color it if it's too large
    updateTotalUnits(regionYearId) {
        var region = exhibits_spaces[regionYearId];
        if (!region)
            return;

        var requestedUnits = 0;
        var keys = Object.keys(region);
        var field;
        var id;
        for (var key in keys) {
            var priceId = keys[key];

            field = document.getElementById('exhibitor_req_price_id_' + String(keys[key]));
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
        this.#totalUnitsRequested_div.innerHTML = String(requestedUnits);
        this.#unitsRequested = requestedUnits;

        var mailIn = exhibitor_info.mailin == 'Y';
        var regionList = region_list[regionYearId];

        if (requestedUnits > this.#unitLimit && this.#unitLimit > 0 && this.#countCombined) {
            this.#totalUnitsRequestedRow.classList.add('bg-warning');
            if (this.#cancelType != 2)
                this.#exhibitor_req_btn.disabled = true;
            else
                this.#exhibitor_req_btn.classList.add('bg-warning');
        } else {
            this.#totalUnitsRequestedRow.classList.remove('bg-warning');
            if (this.#cancelType != 2)
                this.#exhibitor_req_btn.disabled = false;
            else
                this.#exhibitor_req_btn.classList.remove('bg-warning');
        }
    }

// Space Request - call scripts/spaceRequest.php to add a request record
    spaceReq(regionYearId, cancel) {
        //console.log("spaceReq called for " + spaceId);
        if (this.#unitsRequested <= 0 && !cancel) {
            show_message("Select an amount of space to request", 'error', 'sr_message_div');
            return;
        }

        clear_message('sr_message_div');
        clear_message();
        var dataobj = {
            regionYearId: regionYearId,
            requests: $('#exhibitor_req_form').serialize(),
            'type': config['portalType'],
            'name': config['portalName'],
        };
        var url = 'scripts/spaceReq.php';
        if (cancel == 2) {
            url = 'scripts/exhibitorsSpaceApproval.php';
            dataobj['approvalType'] = 'other';
            dataobj['exhibitorId'] = exhibitor_info['exhibitorId'];
            dataobj['exhibitorYearId'] = exhibitor_info['exhibitorYearId'];
        }
        var _this = this;
        $.ajax({
            url: url,
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
                    _this.#exhibitor_request.hide();
                    show_message(data['success'], 'success');
                    if (cancel == 2)
                        exhibitors.UpdateSpaceRow(data);
                    else
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
    updateRequestStatusBlock(regionYearId) {
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
            blockdiv.innerHTML = "<div class=\"col-sm-auto p-0\"><button class='btn btn-primary' onclick = 'exhibitorRequest.openReq(regionYearId, 0);' > Request " + regionName + " Space</button></div>";
            return;
        }

        blockdiv.innerHTML = '<div class="col-sm-auto p-0">Request pending authorization for:<br/>' + spaceStatus +
            "<button class='btn btn-primary' onclick = 'exhibitorRequest.openReq(" + regionYearId + ", 1);' > Change/Cancel " + regionName + " Space</button></div>";
    }
}

exhibitorRequest = null;
// init
function exhibitorRequestOnLoad() {
    exhibitorRequest = new ExhibitorRequest();
}
