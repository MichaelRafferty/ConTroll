// items related to requesting space (not approvals)
var vendor_request = null;

// init
function vendorRequestOnLoad() {
    id = document.getElementById('vendor_req');
    if (id != null) {
        vendor_request = new bootstrap.Modal(id, {focus: true, backdrop: 'static'});
    }
}

// openReq - update the modal for this space
function openReq(regionId, cancel) {
    //console.log("open request modal for id =" + spaceid);
    var region = exhibits_spaces[regionId];
    if (!region)
        return;
    if (config['debug'] & 1)
        console.log(region);

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
    var spaceHtml = '';
    for (index = 0; index < spaceCount; index += 3) { // look over the spaces up to 3 per row
        spaceHtml += '<div class="row">' + "\n";
        last = index + 3;
        if (last > spaceCount)
            last = spaceCount;
        for (col = index; col < last; col++) {
            space = region[keys[col]];

            // build option pulldown
            var options = "<option value='-1'>" + (cancel ? 'Cancel' : 'No') + " Space Requested</option>\n";
            var prices = space.prices;
            var price_keys = Object.keys(prices).sort();
            for (var priceid in price_keys) {
                var price = prices[price_keys[priceid]];
                if (price.requestable == 1)
                    options += "<option value='" + price.id + "'>" + price.description + ' for ' + Number(price.price).toFixed(2) + "</option>\n";
            }

            // now build block
            spaceHtml += "<div class='col-sm-" + colWidth + " p-0 m-0'>\n" +
                "<div class='container-fluid'><div class='container-fluid m-1 border border-3 border-primary'>\n" +
                "<div class='row'><div class='col-sm-12 p-0 m-0' style='text-align: center;'>\n" + space['spaceName'] + "</div></div>\n" +
                "<div class='row'><div class='col-sm-12 p-2 m-0'>\n" + space['description'] + "</div></div>\n" +
                "<div className='row p-1'><div className='col-sm-auto p-0 pe-2'>\n" +
                "<label htmlFor='vendor_req_price_id'>How many spaces are you requesting?</label>\n" +
                "</div>\n" +
                "<div className='col-sm-auto p-0'>" + "\n" +
                "<select name='vendor_req_price_id_" + keys[col] +  "' id='vendor_req_price_id" + keys[col] + "'>\n" +
                options + "\n</select></div></div>" + "\n" +
            '</div></div></div>' + "\n";
        }
        spaceHtml += '</div>' + "\n";
    }
    // build option list


    document.getElementById("spaceHtml").innerHTML = spaceHtml;

    // update fields
    document.getElementById("vendor_req_title").innerHTML = "<strong>" + (cancel ? 'Change/Cancel ' : '') + region.name + ' Space Request</strong>';
    document.getElementById("vendor_req_btn").innerHTML = (cancel ? "Change/Cancel " : "Request ") + region.name + ' Space';
    var selection = document.getElementById('vendor_req_price_id');
    //selection.innerHTML = options;
    //if (cancel) selection.value = cancel;
    document.getElementById('vendor_req_btn').setAttribute('onClick', "spaceReq(" + region.id + ',' + cancel + ')');
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
