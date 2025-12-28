// Common items related to building the vendor invoice page for exhibtor tab of controll and exhibitors portals
// draw approved for section

var inclProfiles = [];
var addlProfiles = [];

function drawExhitorTopBlocks(name, exhibitor_spacelist, region, regionList, regionYearId,
                              approved, included, members, doTerms = true, warnType = 'need') {
    var includedMemberships = 0;
    var additionalMemberships = 0;
    var spacePriceName
    var totalSpacePrice = 0;
    var regionName = regionList.name;
    var portalType = regionList.portalType
    var mailin = exhibitor_info['mailin'];
    var tabindex = 10;

    var html = "You are approved for:<br/>\n";
    var exSpaceKeys = Object.keys(exhibitor_spacelist);
    for (var exSpaceIdx in exSpaceKeys) {
        if (region[exSpaceKeys[exSpaceIdx]]) { // space is in our region
            var space = exhibitor_spacelist[exSpaceKeys[exSpaceIdx]];
            var prices = region[exSpaceKeys[exSpaceIdx]].prices;
            if (space.item_approved) {
                html += space.approved_description + " in " + regionName + " for " +
                    currencyFmt.format(Number(space.approved_price).toFixed(2)) + "<br/>";
                totalSpacePrice += Number(space.approved_price);
                // find price item in prices
                for (priceIdx = 0; priceIdx < prices.length; priceIdx++) {
                    if (prices[priceIdx].id == space.item_approved)
                        break;
                }
                if (includedMemberships < prices[priceIdx].includedMemberships) {
                    spacePriceName = prices[priceIdx].description;
                    includedMemberships = prices[priceIdx].includedMemberships;
                }
                if (additionalMemberships < prices[priceIdx].additionalMemberships) {
                    spacePriceName = prices[priceIdx].description;
                    additionalMemberships = prices[priceIdx].additionalMemberships;
                }
            }
        }
    }
    if (regionList['mailinFee'] > 0 && exhibitor_info['mailin'] == 'Y') {
        html += "Mail in fee of " +
            currencyFmt.format(Number(regionList['mailinFee']).toFixed(2))+ "<br/>\n";
        totalSpacePrice += Number(regionList['mailinFee']);
    }
    html += "____________________________<br/>\nTotal price for spaces " +
        currencyFmt.format(Number(totalSpacePrice).toFixed(2)) + "<br/>&nbsp;<br/>\n";
    document.getElementById(approved).innerHTML = html;

    var spaces = includedMemberships + additionalMemberships;
    // make the strings for the number of included additional memberships available to purchase
    html = '<p>';
    if (spaces == 0) { // no additional or included memberships
        html += regionName + ' ' +  spacePriceName + ' spaces do not come with any memberships as part of the space purchase. ' +
            ' Please purchase your attending memberships to the convention separately at ' +
            '<a href="' + config['regserver'] + '">' + config['regserver'] + '</a>.';
    } else if (includedMemberships == 0) {
        html += regionName + ' ' +  spacePriceName + ' spaces come with the option to purchase up to ' + additionalMemberships  +
            ' membership' + (additionalMemberships > 1 ? 's' : '') + ' at  the discounted price of ' +
            currencyFmt.format(Number(regionList.additionalMemPrice).toFixed(2)) + '. ' +
            'Purchase those memberships here. ' +
            'Any additional memberships beyond those you purchase here need to be purchased separately at ' +
            '<a href="' + config['regserver'] + '">' + config['regserver'] + '</a>.';
    } else if (additionalMemberships == 0) {
        html += regionName + ' ' +  spacePriceName + ' spaces come with ' + includedMemberships + ' membership' + (includedMemberships > 1 ? 's' : '') +
            ' as part of the space purchase. Please enter those memberships here. ' +
            'Any additional memberships to the convention need to be purchased separately at ' +
            '<a href="' + config['regserver'] + '">' + config['regserver'] + '</a>.';
    } else {
        html += regionName + ' ' +  spacePriceName + ' spaces come with ' + includedMemberships + ' membership' + (includedMemberships > 1 ? 's' : '') +
            ' as part of the space purchase. In addition it comes with the right to purchase up to ' + additionalMemberships +
            ' membership' + (additionalMemberships > 1 ? 's' : '') + ' at  the discounted price of ' +
            currencyFmt.format(Number(regionList.additionalMemPrice).toFixed(2)) + '. ' +
            'Use the included memberships first, and then add the additional memberships if desired. If you need more memberships beyond that they need to' +
            ' be purchased separately at ' +
            '<a href="' + config['regserver'] + '">' + config['regserver'] + '</a>.';
    }
    html += "</p>\n";
    if (spaces == 0) {
        html += "<input type='hidden' name='agreeNone' value='on'></input>"
    }
    if (spaces > 0) {
        if (doTerms) {
            var terms = '';
            var defterms = '';
            switch (portalType) {
                case 'artist':
                    if (mailin == 'N') {
                        terms = config['termsArtistOnsite'];
                        if (terms == '')
                            defterms = "<p>All non mail-in artists must have a membership. " +
                                "Included and additional discounted memberships can only be purchased while paying for your space.";
                    } else {
                        terms = config['termsArtistMailin'];
                        if (terms == '')
                            defterms = "<p>Mail-in artists do not need a membership. " +
                                "Included and additional discounted memberships, however, can only be purchased while paying for your space.";
                    }
                    break;
                case 'vendor':
                    terms = config['termsVendor'];
                    if (terms == '')
                        defterms = "<p>All vendors must have a membership. " +
                            "Included and additional discounted memberships can only be purchased while paying for your space.";
                    break;
                default:
                    defterms = "<p>All exhibitors must have a membership. " +
                        "Included and additional discounted memberships can only be purchased while paying for your space.";
            }
            if (terms == '') {
                html += defterms + " If you do not purchase them now while paying your space invoice, " +
                    "you will have to purchase them at the current membership rates.</p>" +
                    "<p>If you are unsure who will be using the registrations please use the first name of ‘Provided’ and a last name of ‘At Con’. " +
                    "The on-site registration desk will update the membership to the name on their ID.</p>" +
                    "<p>Program participants do not need to buy memberships; however, we will confirm that they meet the requirements to waive the membership cost. " +
                    "If they do not, they will need to purchase a membership on-site at the on-site rates.</p>";
            } else {
                html += terms;
            }
            html += "<p><input type='checkbox' style='transform: scale(2);' name='agreeNone' id='agreeNone' tabindex=" + tabindex + "> &nbsp;&nbsp;" +
                "If you do not wish to purchase any memberships at this time, check this box to acknowledge the requirement for memberships above.</p>";
            tabindex += 2;
        }

        if (portalType == 'artist' && mailin == 'N') {
            html += "<p>In addition, all non-mail-in artists need to declare an on-site agent. " +
                "This is the person that will be contacted if there are any issues with setup, operation, or teardown of your exhibit. " +
                "The agent needs a membership, and you can be the agent.</p>" +
                "<p><label for='agent_self'><span name='agent'>" +
                "<input type='radio' name='agent' id='agent_self' value='self' style='transform: scale(1.5);' tabindex=" + tabindex + ">" +
                "&nbsp;&nbsp;&nbsp;I will be my" +
                " own agent and my membership is not one of the ones below.</span></label><br/>" +
                "<label for='agent_first'><span name='agent'>" +
                "<input type='radio' name='agent' id='agent_first' value='first' style='transform: scale(1.5);' tabindex=" + (tabindex + 2) + ">" +
                "&nbsp;&nbsp;&nbsp;The first membership below is for myself or my agent.</span></label><br/>";
            tabindex += 4;

            if (doTerms) {
                var ry = exhibitor_regionyears[regionYearId];
            }
            if (doTerms && ry['perid']) {
                html += "<label for='agent_perid'><span name='agent'>" +
                    "<input type='radio' name='agent' id='agent_perid' value='p" + ry['perid'] + "' style='transform: scale(1.5);'" +
                    " tabindex=" + tabindex + ">&nbsp;&nbsp;&nbsp;Assign " + ry['p_first_name'] + ' ' + ry['p_last_name'] + ' as my agent.' +
                    '</span></label><br/>';
            } else if (doTerms && ry['newperid']) {
                html += "<label for='agent_newid'><span name='agent'>" +
                    "<input type='radio' name='agent' id='agent_newid' value='n" + ry['newperid'] + "' style='transform: scale(1.5);'" +
                    " tabindex=" + tabindex + 2 + ">&nbsp;&nbsp;&nbsp;Assign " + ry['n_first_name'] + ' ' + ry['n_last_name'] + ' as my agent.' +
                    '</span></label><br/>';
            } else if (exhibitor_info['perid']) {
                html += "<label for='agent_perid'><span name='agent'>" +
                    "<input type='radio' name='agent' id='agent_perid' value='p" + exhibitor_info['perid'] + "' style='transform: scale(1.5);'>" +
                    "&nbsp;&nbsp;&nbsp;Assign " + exhibitor_info['p_first_name'] + ' ' + exhibitor_info['p_last_name'] + ' as my agent.' +
                    '</span></label><br/>';
            } else if (exhibitor_info['newperid']) {
                html += "<label for='agent_newid'><span name='agent'>" +
                    "<input type='radio' name='agent' id='agent_newid' value='n" + exhibitor_info['newperid'] + "' style='transform: scale(1.5);'" +
                    " tabindex=" + tabindex  + 2 + ">&nbsp;&nbsp;&nbsp;Assign " + exhibitor_info['n_first_name'] + ' ' + exhibitor_info['n_last_name'] +
                    ' as my agent.</span></label><br/>';
            }
            tabindex += 10;
            html += "<label for='agent_request'><span name='agent'>" +
                "<input type='radio' name='agent' id='agent_request' value='request' style='transform: scale(1.5);'" +
                " tabindex=" + tabindex + ">&nbsp;&nbsp;&nbsp;Please assign my agent as per my request below.</span></label><br/>" +
                "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;" +
                "<input type='text' name='agent_request' id='agent_request_text' placeholder='Enter your agent request here if needed' size='120' tabindex=" + (tabindex + 2) +"></p>"
            tabindex += 4;
        }
    }
    document.getElementById(included).innerHTML = html;

    html = '';
    // now build the included memberships
    if (includedMemberships > 0 || additionalMemberships > 0) {
        html += `
             <div class="row" style="width:100%;">
                <div class="col-sm-12">
                    <p class="text-body">
                        <b>Note:</b> Please provide the legal name that will match a valid form of ID. 
                        The legal name will not be publicly visible.  
                        If you don't provide one, it will default to your First, Middle, Last Names and Suffix.
                    </p>
                    <p class="text-body">
                        Items marked with <span class="text-danger">&bigstar;</span> are required fields.
                        If the information is not available, enter /r for the field.
                    </p>
                </div>
            </div>
`;
    }
    tabindex = 200;
    if (includedMemberships > 0) {
        html += "<input type='hidden' name='incl_mem_count' value='" + includedMemberships + "'>\n" +
            "<div class='container-fluid'>\n" +
            "<div class='row'><div class='col-sm-auto p-2 pe-0'><strong>Included Memberships: (up to " + includedMemberships + ")</strong>" +
            "<input type='hidden' name='includedMemberships' value='" + String(includedMemberships) + "'></div></div>";
        for (mnum = 0; mnum < includedMemberships; mnum++) {
            // name fields including legal name
            html += drawExhibitorMembershipBlock('Included', mnum, 'i_' + mnum + '_', country_options, regionYearId, tabindex, false,
                doTerms ? '' : 'exhibitorInvoice.');
            tabindex += 100;
        }
        html += "<hr/>";
    }

    // now build the additional memberships
    if (additionalMemberships > 0) {
        html += "<input type='hidden' name='addl_mem_count' value='" + additionalMemberships + "'>\n" +
            "<div class='row'><div class='col-sm-auto p-2 pe-0'><strong>Additional Memberships: (up to " + additionalMemberships + ")</strong>" +
            "<input type='hidden' name='additionalMemberships' value='" + String(additionalMemberships) + "'></div></div>";
        for (mnum = 0; mnum < additionalMemberships; mnum++) {
            // name fields includeing legal name
            html += drawExhibitorMembershipBlock('Additional', mnum, 'a_' + mnum + '_', country_options, regionYearId, tabindex, true,
                doTerms ? '' : 'exhibitorInvoice.');
            tabindex += 100;
        }
        html += "</div><hr/>";
    }
    document.getElementById(members).innerHTML = html;
    // fill in default information for the values of the addresses
    for (mnum = 0; mnum < includedMemberships; mnum++) {
        inclProfiles[mnum] = new Profile('i_' + mnum + '_', config.portalName, warnType);
        inclProfiles[mnum].setAll('', '', '', '', '', '', exhibitor_info.addr, exhibitor_info.addr2, exhibitor_info.city,
                exhibitor_info.state, exhibitor_info.zip, exhibitor_info.country, exhibitor_info.exhibitorPhone,
                '', '', '');
        inclProfiles[mnum].setEmail(exhibitor_info.exhibitorEmail);
    }
    for (mnum = 0; mnum < additionalMemberships; mnum++) {
        addlProfiles[mnum] = new Profile('a_' + mnum + '_', config.portalName, warnType);
        addlProfiles[mnum].setAll('', '', '', '', '', '', exhibitor_info.addr, exhibitor_info.addr2, exhibitor_info.city,
            exhibitor_info.state, exhibitor_info.zip, exhibitor_info.country, exhibitor_info.exhibitorPhone,
            '', '', '');
        addlProfiles[mnum].setEmail(exhibitor_info.exhibitorEmail);
    }
    return [includedMemberships, additionalMemberships, spacePriceName, totalSpacePrice];
}

function drawExhibitorMembershipBlock(label, mnum, prefix, country_options, regionYearId, tabindex, doOnChange, className = '') {
    var reqFirstStar = config['firstStar'];
    var reqAddrStar = config['addrStar'];
    var reqAllStar = config['allStar'];

    var html = `
<div class="row mt-4">
    <div class="col-sm-auto p-0">` + label + ' Member ' + (mnum + 1) + `:</div>
</div>
<div class="row">
    <div class="col-sm-8">
        <div class="container-fluid">
            <div class="row">
                <div class="col-sm-auto ms-0 me-2 p-0">
                    <label for="` + prefix + `fname" class="form-label-sm">
                    <span class="text-dark" style="font-size: 10pt;">Preferred Name: ` + reqFirstStar + `First</span>
                    </label><br/>
                    <input class="form-control-sm" type="text" name="` + prefix + 'fname" id="' + prefix + 'fname" size="22" maxlength="32"' +
        (doOnChange ? 'onchange="' + className + 'updateCost(' + regionYearId + "," + mnum + ');"' : '') + ' tabindex=' + tabindex + `/>
                </div>`;
    tabindex += 2;
    html += `
                <div class="col-sm-auto ms-0 me-2 p-0">
                    <label for="` + prefix + `mname" class="form-label-sm"><span class="text-dark" style="font-size: 10pt;">Middle</span></label><br/>
                    <input class="form-control-sm" type="text" name="` + prefix + 'mname" id="' + prefix + 'mname" size="8" maxlength="32"' +
                        ' tabindex=' + tabindex + `/>
                </div>`;
    tabindex += 2;
    html += `
                <div class="col-sm-auto ms-0 me-2 p-0">
                    <label for="lname` + 'lname" class="form-label-sm"><span class="text-dark" style="font-size: 10pt;">' +
                        reqAllStar + `Last</span></label><br/>
                    <input class="form-control-sm" type="text" name="` + prefix + 'lname" id="' + prefix + 'lname" size="22" maxlength="32"' +
                        ' tabindex=' + tabindex + `/>
                </div>`;
    tabindex += 2;
    html += `
                <div class="col-sm-auto ms-0 me-0 p-0">
                    <label for="` + prefix + `suffix" class="form-label-sm"><span class="text-dark" style="font-size: 10pt;">Suffix</span></label><br/>
                    <input class="form-control-sm" type="text" name="` + prefix + 'suffix" id="' + prefix + 'suffix" size="4" maxlength="4"' +
                        ' tabindex=' + tabindex + `/>
                    </div>`;
    tabindex += 2;
    html += `
            </div>
            <div class='row'>
                <div class='col-sm-12 ms-0 me-0 p-0'>
                    <label for="` + prefix + `legalName" class='form-label-sm'>
                        <span class='text-dark' style='font-size: 10pt;'>
                            Legal Name: for checking against your ID. It will only be visible to Registration Staff.
                        </span></label><br/>
                    <input class='form-control-sm' type='text' name="` + prefix + 'legalName" id="' + prefix + 'legalName" size="64" maxlength="64"' +
                        ' placeholder="Defaults to First Name Middle Name Last Name, Suffix"' + ' tabindex=' + tabindex + `/>
                </div>
            </div>
`;
    tabindex += 2;
    // address fields
    html += `
            <div class="row">
                <div class="col-sm-12 ms-0 me-0 p-0">
                    <label for="` + prefix + `addr" class="form-label-sm"><span class="text-dark" style="font-size: 10pt;">` +
                        reqAddrStar + `Address</span></label><br/>
                    <input class="form-control-sm" type="text" name="` + prefix + 'addr" id="' + prefix + 'addr" size="64" maxlength="64"' +
                        ' tabindex=' + tabindex + `/>
                </div>
            </div>`;
    tabindex += 2;
    html += `
            <div class="row">
                <div class="col-sm-12 ms-0 me-0 p-0">
                    <label for="` + prefix + `addr2" class="form-label-sm"><span class="text-dark" style="font-size: 10pt;">Company/2nd Address line</span></label><br/>
                    <input class="form-control-sm" type="text" name="` + prefix + 'addr2" id="' + prefix + 'addr2" size="64" maxlength="64"' +
                        ' tabindex=' + tabindex + `/>
                </div>
            </div>`;
    tabindex += 2;
    html += `
            <div class="row">
                <div class="col-sm-auto ms-0 me-2 p-0">
                    <label for="` + prefix + `city" class="form-label-sm"><span class="text-dark" style="font-size: 10pt;">` +
                        reqAddrStar + `City</span></label><br/>
                    <input class="form-control-sm" type="text" name="` + prefix + 'city" id="' + prefix + 'city" size="22" maxlength="32"' +
                        ' tabindex=' + tabindex + `/>
                </div>`;
    tabindex += 2;
    html += `
                <div class="col-sm-auto ms-0 me-2 p-0">
                    <label for="` + prefix + `state" class="form-label-sm"><span class="text-dark" style="font-size: 10pt;">` +
                        reqAddrStar + `State/Prov</span></label><br/>
                    <input class="form-control-sm" type="text" name="` + prefix + 'state" id="' + prefix + 'state" size="10" maxlength="16"' +
                        ' tabindex=' + tabindex + `/>
                </div>`;
    tabindex += 2;
    html += `
                <div class="col-sm-auto ms-0 me-2 p-0">
                    <label for="` + prefix + `zip" class="form-label-sm"><span class="text-dark" style="font-size: 10pt;">` +
                        reqAddrStar + `Zip/PC</span></label><br/>
                    <input class="form-control-sm" type="text" name="` + prefix + 'zip" id="' + prefix + 'zip" size="5" maxlength="10"' +
                        ' tabindex=' + tabindex + `/>
                </div>`;
    tabindex += 2;
    html += `
                <div class="col-sm-auto ms-0 me-0 p-0">
                    <label for="` + prefix + `country" class="form-label-sm"><span class="text-dark" style="font-size: 10pt;">Country</span></label><br/>
                    <select class="form-control-sm" name="` + prefix + 'country" id="' + prefix + 'country" tabindex = ' + tabindex + `>
            ` + country_options + `
                    </select>
                </div>
            </div>`;
    tabindex += 2;
    html += `
        </div>
    </div>
    <div class="col-sm-4" id="` + prefix + `uspsblock"></div>
</div>
<div class="row">
    <div class="col-sm-12">
        <div class="container-fluid">
            <div class="row">
                <div class="col-sm-auto ms-0 me-2 p-0">
                    <label for="` + prefix + `email" class="form-label-sm"><span class="text-dark" style="font-size: 10pt;">` +
                        reqFirstStar + `Email</span></label><br/>
                    <input class="form-control-sm" type="email" name="` + prefix + 'email1" id="' + prefix + 'email1" size="35" maxlength="254"' +
                        ' tabindex=' + tabindex + `/>
                </div>`;
    tabindex += 2;
    html += `
                <div class="col-sm-auto ms-0 me-2 p-0">
                    <label for="` + prefix + `phone" class="form-label-sm"><span class="text-dark" style="font-size: 10pt;">Phone</span></label><br/>
                    <input class="form-control-sm" type="text" name="` + prefix + 'phone" id="' + prefix + 'phone" size="18" maxlength="15"' +
                     ' tabindex=' + tabindex + `/>
                </div>`;
    tabindex += 2;
    html += `
                <div class="col-sm-auto ms-0 me-2 p-0">
                    <label for="` + prefix + `age" class="form-label-sm ps-2">
                        <span class="text-dark" style="font-size: 10pt;">Age as of ` + config.ageByDate + `</span>
                    </label>
                    <br/>
                    <select class="form-control-sm" name="` + prefix + 'age" id="' + prefix + 'age" tabindex=' + tabindex + `/>
                        ` + ageOptions + `
                    </select>
                </div>`;
    tabindex += 2;
    html += `
            </div>
            <div class="row">
                <div class="col-sm-auto me-1 p-0">
                    <label for="` + prefix + `badge_name" class="form-label-sm">
                        <span class="text-dark" style="font-size: 10pt;">Badge Name (optional)</span>
                    </label><br/>
                    <input class="form-control-sm" type="text" name="` + prefix + 'badge_name" id="' + prefix + 'badge_name" ' +
                        'size="35" maxlength="32" placeholder="defaults to first and last name" tabindex=' + tabindex + `/>
                </div>`;
    tabindex += 2;
    html += `
                 <div class="col-sm-auto ms-1 p-0">
                    <label for="` + prefix + `badgeNameL2" " class="form-label-sm">
                        <span class="text-dark" style="font-size: 10pt;">Badge Line 2 (optional)</span>
                    </label><br/>
                    <input class="form-control-sm" type="text" name="` + prefix + 'badgeNameL2" id="' + prefix + 'badgeNameL2" ' +
                        'size="35" maxlength="32" tabindex=' + tabindex + `/>
                </div>`;
    tabindex += 2;
    html += `
            </div>
        </div>
    </div>
</div>
`;
    if (policies == null || policies.length == 0)
        return html;

    if (policyHeader && policyHeader != '') {
        html += '<div class="row">\n<div class="col-sm-auto">' + policyHeader + "</div>\n</div>\n";
    }
    for (let index = 0; index < policies.length; index++) {
        let policy = policies[index];
        let prompt = policy['prompt'];
        let name = policy['policy'];
        let description = policy['description'];
        if (prompt.match(/<a href/))
            prompt = prompt.replace(/(<a href=[^>]*)/g, '$1 tabindex="' + (tabindex + 1) + '"');
        if (description.match(/<a href/))
            description = description.replace(/(<a href=[^>]*)/g, '$1 tabindex="' + (tabindex + 3) + '"');
        if (policy.required == 'Y')
            prompt = "<span class='text-danger'>&bigstar;</span>" + prompt;
        let checked = policy.defaultValue == 'Y' ? 'checked' : '';
        html += `
 <div class='row'>
    <div class='col-sm-12'>
    <p class='text-body'>
        <label>
            <input type="checkbox" ` + checked + ' name="' + prefix + 'p_' + name + '" id="' + prefix + 'p_' + name +
                '" value="Y" tabindex="' + tabindex + `">
            <span id="` + prefix + 'l_' + name + '" name="' + prefix + 'l_' + name + '">' + prompt + `</span>
        </label>
`;
        if (description != '') {
            html += `
        <span class="small">
            <a href="javascript:void(0)" onClick='$("#` + prefix + name + `Tip").toggle();'>
                <img src="/lib/infoicon.png"  alt="click this info icon for more information" style="max-height: 25px;"
                    tabindex="` + (tabindex + 2) + `"/>
            </a>
        </span>
    </p>
    <div id="` + prefix + name + `Tip" class="padded highlight" style="display:none">
        <p class="text-body">` + description + `
        <span class="small">
            <a href="javascript:void(0)" onClick='$("#` + prefix + name + `Tip").toggle()'>
                <img src="/lib/closeicon.png" alt="click this close icon to close the more information window" style="max-height: 25px;"
                     tabindex="` + (tabindex + 3) + `"/>
                </a>
            </span>
        </p>
    </div>
    `;
        }
    tabindex += 5;
    }

    if (policyFooter && policyFooter != '') {
        html += '<div class="row">\n<div class="col-sm-auto">' + policyFooter + "</div>\n</div>\n";
    }

    return html;
}
