// count = total count of badges
// total = sum(prices) * qty of badges
// memTypeCount = array by memId of counts
// badges = array of the data for individual badges
var badges = { count: 0, total: 0, memTypeCount: {}, badges: [] };
// prices = array by memId of prices for badges
var prices = {};
var $purchase_label = 'card-button';
// shortnames are the memLabel short names for the memAge
var shortnames = {};
// anotherbadge = bootstrap 5 modal for the add another modal popup
var anotherBadge = null;
// newBadge = bootstrap 5 modal for the add badge modal popop
var newBadge = null;
// local variables for coupon processing
var coupon = null;
var couponSubtotal = 0;
var couponDiscount = 0;
var totalDue = 0;

// pricing area
var memSummaryDiv = null;
var totalCostDiv = null;
var subTotalColDiv = null;
var couponDiscountDiv = null;

// checkout area
var emptyCart = null;
var noChargeCart = null;
var chargeCart = null;

// usps related fields
var addToCartBtn = null;

var profile = null;

// formatting items
// locale/currency
var currencyFmt = null;
var locale = null;

// credit card related
var ccType = null;
var currentPaymentIntentId = null;
var currentElementsId = null;
var currentOrder = null;
var currentCurrency = 'usd';
var currencyMultiplier = 100;
var currentOrderId = null;
var ccInProcess = false;
var currentToken = null
var currentNonce = null;
var currentOrderBadges = null;
var currentTransId = null;

// process the form for validation and add to the badge array if valud
function process(formRef) {
    formData = URLparamsToArray($('#' + formRef).serialize(), true);
    formData.policyInterest = URLparamsToArray($('#editPolicies').serialize(), true);

    clear_message('addMessageDiv');
    let message = '';
    // check if there are too many limited memberships in the cart
    if (coupon.getMemGroup() == formData.memId) {
        let cur = badges.memTypeCount[formData.memId];
        let lim = coupon.getLimitMemberships();
        if (badges.memTypeCount[formData.memId] >= coupon.getLimitMemberships()) {
            $message += "<br/>You already have the maximum number of memberships of this membership type in your cart based on the coupon applied. " +
                "You must choose a different membership type.";
            valid = false;
        }
    }
    if (!profile.validate(formData, 'addMessageDiv', addMembership, redoAddress, message))
        return false;

    addMembership(formData);
    return true;
}

// countryChange - if USPS and USA, then change button
function countryChange() {
    if (!profile.hasUSPSDiv())
        return;

    clear_message('addMessageDiv');
    if (profile.country() == 'USA') {
        addToCartBtn.innerHTML = 'Validate Address To Add Membership To Cart';
    } else {
        addToCartBtn.innerHTML = 'Add Membership To Cart';
    }
}

function redoAddress() {
    process("newBadgeForm");
}

function addMembership(formData) {
    // clear for next use: first name, middle name, last name, suffix (entire name field set), and the badgename.  To make virtual easier, clear the email addresses.
    profile.clearNext(true);
    clear_message('addMessageDiv');

    // build name and legal name
    let name = formData.fname + " " + formData.mname + " " + formData.lname + " " + formData.suffix;
    name = name.trim();
    if (formData.legalName=='') {
        formData.legalName = name;
    }

    badges.count +=  1;
    if (badges.memTypeCount[formData.memId] == null)
        badges.memTypeCount[formData.memId] = 0;
    badges.memTypeCount[formData.memId] += 1;
    badges.badges.push(formData);

    repriceCart();
  
    let badgename = badgeNameDefault(formData.badge_name, formData.badgeNameL2, formData.fname, formData.lname);
    // add this person to the "who is paying" "person" list
    let option = $(document.createElement('option'))
        .append(name)
        .data('info', formData)
        .attr('value', name);
    $("#personList").append(option);

    // and make it select the first item on the list
    if ($("#personList").val() == undefined) {
        $("#personList").val(name);
    }

    // build badge block in Badges list
    let memId = formData.memId;
    // find matching mtype in array
    let found = false;
    let mtype = null;
    for (let row in mtypes) {
        let mbrType = mtypes[row];
        if (mbrType.id == memId) {
            mtype = mbrType;
            found = true;
            break;
        }
    }

    let age_text='unknown';
    let labeldivtext = 'Unknown';
    let addon = '';

    if (found) {
        age_text = mtype.memAge;
        labeldivtext = shortnames[mtype.id];
        if (mtype.memCategory == 'addon' || mtype.memCategory == 'add-on')
            addon += "<br/>&nbsp;Add On to<br/>&nbsp;Membership";
    }

    let age_color = 'text-white';
    if (age_text != 'adult' && age_text != 'child' && age_text != 'youth' && age_text != 'kit')
        age_color = 'text-black';
    let re = /\-+/g;
    labeldivtext = labeldivtext.replace(re, '-<br/>');

    let bdivid="badge" + badges.count;
    let html = "<div id='" + bdivid + "' data-index='" + (badges.count - 1) + "' class='container-fluid border border-2 border-dark'>\n" +
        "  <div class='row'>\n" +
        "    <div class='col-sm-3 p-0 m-0 text-wrap " + age_text + "'>\n" +
        "      <h4><span class='badge " + age_color + ' ' + age_text + " text-wrap'>" + labeldivtext + "</span></h4>" + addon + "\n" +
        "    </div>\n" +
        "    <div class='col-sm-8'>\n" +
        "      <p class='text-body'>Full Name:<br/><strong>" + name + "</strong><br/>Badge Name:<br/><strong>" + badgename + "</strong></p>\n" +
        "    </div>\n" +
        "    <div class='col-sm-1'>\n" +
        "      <button class='btn btn-sm btn-secondary' onclick='removeBadge(" + '"' +  bdivid + '"' + ")'>X</button>\n" +
        "    </div>\n" +
        "  </div>\n" +
        "</div>\n";

    $('#badge_list').append(html);

    // set the fields for the paid by fields
    updateAddr();
    // for the another badge modal, update his name
    $('#oldBadgeName').empty().append(name);

    // toggle the modals from newBadgeto anotherBadge
    newBadgeModalClose();
    anotherBadgeModalOpen();
}

function removeBadge(bdivid) {
    let toRemove = document.getElementById(bdivid);
    let i = toRemove.getAttribute('data-index');
    let memId = badges.badges[i].memId;

    badges.memTypeCount[memId] -= 1;
    badges.count -= 1;
    badges.badges[i]={};

    repriceCart();
    toRemove.remove();
}

function updateAddr() {
    let selOpt = $("#personList option:selected");
    let optData = selOpt.data('info');
    $('#cc_email').val(optData.email1);
}

function ol_ajax_error(JqXHR, textStatus, errorThrown) {
    alert("ERROR! " + textStatus + ' ' + errorThrown);
    $('#' + $purchase_label).removeAttr("disabled");
}

function bo_ajax_success(data, textStatus, jqXHR) {
    if (data.status == 'error') {
        if (data.error)
            alert("Purchase Failed: " + data.error);
        else if (data.data)
            alert("Purchase Failed: " + data.data);
        else
            alert("Purchase Failed: Unknown error");
        $('#' + $purchase_label).removeAttr("disabled");
        return;
    }
    if (data.status == 'echo') {
        console.log(data);
        $('#' + $purchase_label).removeAttr("disabled");
        return;
    }
    // ok the order now succeeded, process to process the payment
    if (data.hasOwnProperty('nextStep')) {
        let nextStep = data.nextStep;
        if (nextStep == 'receipt') {
            ccInProcess = false;
            currentOrder = null;
            currentNonce = null;
            currentToken = null;
            currentOrderId = null;
            window.location.href = "receipt.php?trans=" + data.trans;
            return;
        }
        if (nextStep == 'payment') {
            currentOrderId = data.results.orderId;
            currentOrderBadges = data.results.badges;
            currentTransId = data.results.transid;
            ccInProcess = false;
            return payOrder(data);
        }
        alert("Purchase failed: Unknown next step: "  + nextStep);
        $('#' + $purchase_label).removeAttr("disabled");
    }
}

function mp_ajax_success(data, textStatus, jqXHR) {
    if (data.status == 'next') {
        //console.log('need actions');
        let status = stripe_nextActions(data);
        return;
    }
    if (data.status == 'error') {
        if (data.error)
            alert("Purchase Failed: " + data.error);
        if (data.data)
            alert("Purchase Failed: " + data.data);
        $('#' + $purchase_label).removeAttr("disabled");
    } else if (data.status == 'echo') {
        console.log(data);
        $('#' + $purchase_label).removeAttr("disabled");
    } else {
        window.location.href = "receipt.php?trans=" + data.trans;
        $('#' + $purchase_label).removeAttr("disabled");
    }
}
    
function makePurchase(token, label) {
    // if in the middle of a payment, like cc failed, retry, this needs to go to payorder to continue the order
    if (currentOrderId && ccInProcess == true) {
        return payOrder();
    }

    // if square or test, nonce is a string, if strip, its an object
    let nonce = null;
    if (label == 'stripe-confirm')
        nonce = JSON.stringify(token);
    else if (token == 'test_ccnum')
        nonce = document.getElementById(token).value;
    else
        nonce = token;

    currentToken = token;
    currentNonce = nonce;

    if (label != '') {
        $purchase_label = label;
    }
    if (token == 'test_ccnum') {  // this is the test form
        token = document.getElementById(token).value;
    }

    // validate CC email address for receipt
    let cc_email = document.getElementById('cc_email').value;
    if (!validateAddress(cc_email)) {
        alert("The 'who's paying for the order' email address is not valid, please use the Edit button to put in a valid email address for the receipt");
        $('#cc_email').addClass('need');
        return false;
    }
    $('#cc_email').removeClass('need');

    $('#' + $purchase_label).attr("disabled", "disabled");
    let postdata = badges.badges;
    if (postdata.length == 0) {
        alert("You don't have any memberships to buy, please add some memberships");
        if (newBadge != null) {
            newBadge.show();
            profile.setFocus('fname');
        }
        return false;        
    }
    let data = {
        badges: JSON.stringify(badges),
        nonce: token,
        purchaseform: URLparamsToArray($('#purchaseForm').serialize()),
        couponCode: coupon.getCouponCode(),
        couponSerial: coupon.getCouponSerial(),
        couponSubtotal: couponSubtotal,
        couponDiscount: couponDiscount,
        cancelOrderId: currentOrderId,
        action: 'buildOrder',
    }
    if (config.debug > 0) {
        console.log("MP Data");
        console.log(data);
    }
    $.ajax({
        url: "scripts/buildOrder.php",
        data: data,
        method: 'POST',
        success: bo_ajax_success,
        error: ol_ajax_error,
    });
}

function payOrder(data) {
    //console.log('pay order called');
    //console.log(data);
    //console.log('ccInProcess: ' + ccInProcess);
    //console.log('currentOrderId: ' + currentOrderId);
    args = {
        data: JSON.stringify(data),
        action: 'payOrder',
        nonce: currentNonce,
    };
    $.ajax({
        url: "scripts/makePurchase.php",
        data: args,
        method: 'POST',
        success: mp_ajax_success,
        error: ol_ajax_error,
    });
}

// pay action receipt - a callback from stripe to complete an authorization required transaction
function payActionComplete(paymentIntent, post, payParams) {
    //console.log("completed action");
    //console.log(paymentIntent);
    let id = document.getElementById("card-button");
    if (id)
        id.disabled = true;

    let data = post;
    data.action = 'paymentComplete';
    data.payParams = payParams;
    data.paymentIntent = paymentIntent;
    $.ajax({
        url: "scripts/makePurchase.php",
        data: data,
        method: 'POST',
        success: mp_ajax_success,
        error: ol_ajax_error,
    });
}

function newBadgeModalOpen() {
    if (newBadge != null) {
        profile.clearNext(true);
        newBadge.show();
        profile.setFocus('fname');
    }
}

function newBadgeModalClose() {
    if (newBadge != null) {
        newBadge.hide();
    }
}

function anotherBadgeModalOpen() {
    if (anotherBadge != null) {
        anotherBadge.show();
    }
}

function anotherBadgeModalClose() {
    if (anotherBadge != null) {
        anotherBadge.hide();
    }
}

function couponModalOpen() {
    coupon.ModalOpen(badges.count);
}

function couponModalClose() {
    coupon.ModalClose(badges.count == 0);
}

function addCouponCode() {
    coupon.addCouponCode();
}

function removeCouponCode() {
    coupon.RemoveCouponCode();
}

function repriceCart() {
    if (config.debug > 0) {
        console.log(mtypes);
        console.log(badges);
    }
    let html = '';
    let nbrs = badges.memTypeCount;
    let total = 0;
    let mbrtotal = 0;
    let cartDiscountable = false;
    let couponmemberships = 0;
    let couponPrimaryMemberships = 0;
    let primaryMemberships = 0;

    if (typeof mtypes != 'undefined' && mtypes != null) {
        for (let row in mtypes) {
            let mbrType = mtypes[row];
            let num = nbrs[mbrType.id];
            if (num > 0) {

                if (isPrimary(config.conid, mbrType.memType, mbrType.memCategory, mbrType.price, 'coupon')) {
                    couponPrimaryMemberships += num;
                    mbrtotal += num * Number(mbrType.price).toFixed(2);
                }
                if (coupon.isCouponActive()) {
                    if ((coupon.memId != null && coupon.memId == mbrType.memId) || coupon.memId == null)
                        couponmemberships += num;
                }
                if (isPrimary(config.conid, mbrType.memType, mbrType.memCategory, mbrType.price)) {
                    primaryMemberships += num;
                }
                total += num * Number(mbrType.price).toFixed(2);
            }
        }
    }

    if (coupon.isCouponActive()) {
        // first compute un-discounted cart total to get is it sufficient for the discount
        if (mbrtotal >= coupon.getMinCart() && couponPrimaryMemberships >= coupon.getMinMemberships())
            cartDiscountable = true;
        // reset total for below
        couponSubtotal = Number(total);
        subTotalColDiv.innerHTML = currencyFmt.format(Number(total).toFixed(2));
    }

    // now compute discountable totals
    total = 0;
    let maxMbrDiscounts = coupon.getMaxMemberships();
    let couponDiscounts = 0;
    let thisDiscount = 0;
    let itemtype = '';
    for (row in mtypes) {
        mbrType = mtypes[row];
        if (nbrs[mbrType.id] > 0) {
            num = nbrs[mbrType.id];
        } else {
            continue;
        }
        // need to set num here
        if (mbrType.memCategory == 'add-on' || mbrType.memCategory == 'addon')
            itemtype = ' Add-ons: ';
        else
            itemtype = ' Memberships: ';

        if (mbrType.discountable && cartDiscountable) {
            if (maxMbrDiscounts >= num) {
                thisDiscount = num * Number(mbrType.discount).toFixed(2);
                couponDiscounts += thisDiscount;
                maxMbrDiscounts -= num;
            } else {
                thisDiscount = maxMbrDiscounts * Number(mbrType.discount).toFixed(2);
                couponDiscounts += thisDiscount;
                maxMbrDiscounts = 0;
            }
            total += num * Number(mbrType.price).toFixed(2) - thisDiscount;
        } else {
            total += num * Number(mbrType.price).toFixed(2)
        }
        html += mbrType.shortname + itemtype + num + ' x ' + mbrType.price + '<br/>';
    }
    memSummaryDiv.innerHTML = html;
    badges.total = total;

    html = '';
    if (cartDiscountable)  {
        let cartDiscount = coupon.CartDiscount(mbrtotal);
        couponDiscounts += cartDiscount;
        total -= cartDiscount;
    }
    couponDiscount = Number(couponDiscounts);
    couponDiscountDiv.innerHTML = currencyFmt.format(Number(couponDiscounts).toFixed(2)) + html;
    totalCostDiv.innerHTML = currencyFmt.format(Number(total).toFixed(2)) + html;

    // now set the proper div for the payment
    emptyCart.hidden =  primaryMemberships > 0;
    noChargeCart.hidden = primaryMemberships == 0 || badges.total > 0;
    chargeCart.hidden = primaryMemberships == 0 || badges.total == 0;
    totalDue = total;
    if (totalDue > 0)
        startCCPay(totalDue * currencyMultiplier);
    ccInProcess = false;
}

function togglePopup() {
    if (anotherBadge != null) {
        anotherBadge.hide();
    }
    if (newBadge != null) {
        newBadge.show();
        profile.setFocus('fname');
    }
}

function checkRefresh(data = null) {
    return;
}

// interest functions
// check for need to open the notes section
function updateInterestSelect(id) {
    let checked = document.getElementById('i_' + id).checked;
    let prompt = document.getElementById('i_p_' + id).innerHTML;
    if (prompt != '') {
        document.getElementById('i_d_' + id).hidden = !checked;
        document.getElementById('i_t_' + id).hidden = !checked;
        document.getElementById('i_i_' + id).hidden = !checked;
    }
}

// on exit, check to see if an order is in progress and delete those items if so
function onExit(event) {
    if (ccInProcess == false || currentOrderId == null)
        return null;

    //console.log("cancelling prior order and removing items inserted by this run of onlinereg")
    let data = {
        action: 'cancelitems',
        orderId: currentOrderId,
        transid: currentTransId,
        badges: JSON.stringify(currentOrderBadges),
    };
    $.ajax({
        url: "scripts/buildOrder.php",
        data: data,
        method: 'POST',
        success: function (data, textstatus, jqxhr) {
            if (data.error !== undefined) {
                alert(data.error);
            }
        },
        error: ol_ajax_error,
    });
    return null;
}

window.onload = function () {
// formatting items
    currentCurrency = config.ccCurrency;
    currencyMultiplier = config.currencyMultiplier;
    locale = config.locale;
    currencyFmt = new Intl.NumberFormat(locale, {
        style: 'currency',
        currency: config.currency,
    });

    let badge_modal = document.getElementById('anotherBadge');
    if (badge_modal != null) {
        anotherBadge = new bootstrap.Modal(badge_modal, { focus: true, backdrop: 'static' });
    }

    let new_badge = document.getElementById('newBadge');
    if (new_badge != null) {
        newBadge = new bootstrap.Modal(new_badge, {focus: true, backdrop: 'static'});
        addToCartBtn = document.getElementById("addToCartBtn");
        emptyCart = document.getElementById("emptyCart");
        noChargeCart = document.getElementById("noChargeCart");
        chargeCart = document.getElementById("chargeCart");

        profile = new Profile();
        profile.hideAgeText(true);
        profile.hideAgeDiv(true);
        profile.hideAgeField(hide = true);
        profile.hideAgeAsOfLabel(hide = true);
        if (profile.hasUSPSDiv()) {
            if (profile.country() == 'USA')
                addToCartBtn.innerHTML = 'Validate Address To Add Membership To Cart';
        }

        coupon = new Coupon();
        memSummaryDiv = document.getElementById('memSummaryDiv');
        totalCostDiv = document.getElementById('totalCostDiv');
        subTotalColDiv = document.getElementById('subTotalColDiv');
        couponDiscountDiv = document.getElementById('couponDiscountDiv');

        if (typeof mtypes != 'undefined' && mtypes != null) { //v we got here from index (purchase a badge, not some other page)
            for (let row in mtypes) {
                let mbrType = mtypes[row];
                let memId = mbrType.id;
                prices[memId] = Number(mbrType.price);
                badges.memTypeCount[memId] = 0;
                shortnames[memId] = mbrType.shortname.replace(',', '<br/>');
                mbrType.primary = !(mbrType.price == 0 || (mbrType.memCategory != 'standard' && mbrType.memCategory != 'virtual'));
                mbrType.discount = 0;
                mbrType.discountable = false;
            }

            repriceCart();

            if (coupon.couponError() == false) {
                newBadge.show();
                profile.setFocus('fname');
            }
        }
    }

    window.addEventListener('beforeunload', event => {
        onExit(event);
    });
}
