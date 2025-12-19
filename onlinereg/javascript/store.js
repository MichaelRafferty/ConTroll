// count = total count of badges
// total = sum(prices) * qty of badges
// memTypeCount = array by memId of counts
// badges = array of the data for individual badges
var badges = { count: 0, total: 0, memTypeCount: {}, badges: [] };
// prices = array by memId of prices for badges
var prices = {};
var $purchase_label = 'purchase';
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
    profile.clearNext();
    clear_message('addMessageDiv');

    // build name and legal name
    var name = formData.fname + " " + formData.mname + " " + formData.lname + " " + formData.suffix;
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
  
    var badgename = badgeNameDefault(formData.badge_name, formData.badgeNameL2, formData.fname, formData.lname);
    // add this person to the "who is paying" "person" list
    var option = $(document.createElement('option'))
        .append(name)
        .data('info', formData)
        .attr('value', name);
    $("#personList").append(option);

    // and make it select the first item on the list
    if ($("#personList").val() == undefined) {
        $("#personList").val(name);
    }

    // build badge block in Badges list
    var memId = formData.memId;
    // find matching mtype in array
    var found = false;
    var mtype = null;
    for (var row in mtypes) {
        var mbrType = mtypes[row];
        if (mbrType.id == memId) {
            mtype = mbrType;
            found = true;
            break;
        }
    }

    var age_text='unknown';
    var labeldivtext = 'Unknown';
    var addon = '';

    if (found) {
        age_text = mtype.memAge;
        labeldivtext = shortnames[mtype.id];
        if (mtype.memCategory == 'addon' || mtype.memCategory == 'add-on')
            addon += "<br/>&nbsp;Add On to<br/>&nbsp;Membership";
    }

    var age_color = 'text-white';
    if (age_text != 'adult' && age_text != 'child' && age_text != 'youth' && age_text != 'kit')
        age_color = 'text-black';
    var re = /\-+/g;
    labeldivtext = labeldivtext.replace(re, '-<br/>');

    var bdivid="badge" + badges.count;
    var html = "<div id='" + bdivid + "' data-index='" + (badges.count - 1) + "' class='container-fluid border border-2 border-dark'>\n" +
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
    var toRemove = document.getElementById(bdivid);
    var i = toRemove.getAttribute('data-index');
    var badge_age = badges.badges[i].age;

    badges.memTypeCount[badge_age] -= 1;
    badges.count -= 1;
    repriceCart();

    badges.badges[i]={};
    toRemove.remove();
}

function updateAddr() {
    var selOpt = $("#personList option:selected");
    var optData = selOpt.data('info');
    $('#cc_email').val(optData.email1);
}

function mp_ajax_error(JqXHR, textStatus, errorThrown) {
    alert("ERROR! " + textStatus + ' ' + errorThrown);
    $('#' + $purchase_label).removeAttr("disabled");
}

function mp_ajax_success(data, textStatus, jqXHR) {
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
    if (label != '') {
        $purchase_label = label;
    }
    if (token == 'test_ccnum') {  // this is the test form
        token = document.getElementById(token).value;
    }

    // validate CC email address for receipt
    var cc_email = document.getElementById('cc_email').value;
    if (!validateAddress(cc_email)) {
        alert("The 'who's paying for the order' email address is not valid, please use the Edit button to put in a valid email address for the receipt");
        $('#cc_email').addClass('need');
        return false;
    }
    $('#cc_email').removeClass('need');

    $('#' + $purchase_label).attr("disabled", "disabled");
    var postdata = badges.badges;
    if (postdata.length == 0) {
        alert("You don't have any memberships to buy, please add some memberships");
        if (newBadge != null) {
            newBadge.show();
            profile.setFocus('fname');
        }
        return false;        
    }
    var data = {
        badges: JSON.stringify(badges),
        nonce: token,
        purchaseform: URLparamsToArray($('#purchaseForm').serialize()),
        couponCode: coupon.getCouponCode(),
        couponSerial: coupon.getCouponSerial(),
        couponSubtotal: couponSubtotal,
        couponDiscount: couponDiscount,
        total: totalDue,
    }
    if (config.debug > 0) {
        console.log("MP Data");
        console.log(data);
    }
    $.ajax({
        url: "scripts/makePurchase.php",
        data: data,
        method: 'POST',
        success: mp_ajax_success,
        error: mp_ajax_error
    });
}

function newBadgeModalOpen() {
    if (newBadge != null) {
        profile.clearNext();
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
    var html = '';
    var nbrs = badges.memTypeCount;
    var total = 0;
    var mbrtotal = 0;
    var cartDiscountable = false;
    var couponmemberships = 0;
    var couponPrimaryMemberships = 0;
    var primaryMemberships = 0;

    if (typeof mtypes != 'undefined' && mtypes != null) {
        for (var row in mtypes) {
            var mbrType = mtypes[row];
            var num = num = nbrs[mbrType.id];
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
        subTotalColDiv.innerHTML = '$' + Number(total).toFixed(2);
    }

    // now compute discountable totals
    total = 0;
    var maxMbrDiscounts = coupon.getMaxMemberships();
    var couponDiscounts = 0;
    var thisDiscount = 0;
    var itemtype = '';
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
        var cartDiscount = coupon.CartDiscount(mbrtotal);
        couponDiscounts += cartDiscount;
        total -= cartDiscount;
    }
    couponDiscount = Number(couponDiscounts);
    couponDiscountDiv.innerHTML = "$" + Number(couponDiscounts).toFixed(2) + html;
    totalCostDiv.innerHTML = "$" + Number(total).toFixed(2) + html;

    // now set the proper div for the payment
    emptyCart.hidden =  primaryMemberships > 0;
    noChargeCart.hidden = primaryMemberships == 0 || badges.total > 0;
    chargeCart.hidden = primaryMemberships == 0 || badges.total == 0;
    totalDue = total;
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

window.onload = function () {
    var badge_modal = document.getElementById('anotherBadge');
    if (badge_modal != null) {
        anotherBadge = new bootstrap.Modal(badge_modal, { focus: true, backdrop: 'static' });
    }

    var new_badge = document.getElementById('newBadge');
    if (new_badge != null) {
        newBadge = new bootstrap.Modal(new_badge, { focus: true, backdrop: 'static' });
        profile = new Profile();
        profile.hideAgeText(true);
        profile.hideAgeDiv(true);
    }

    addToCartBtn = document.getElementById("addToCartBtn");
    emptyCart = document.getElementById("emptyCart");
    noChargeCart = document.getElementById("noChargeCart");
    chargeCart = document.getElementById("chargeCart");

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
        for (var row in mtypes) {
            var mbrType = mtypes[row];
            var memId = mbrType.id;
            prices[memId] = Number(mbrType.price);
            badges.memTypeCount[memId] = 0;
            shortnames[memId] = mbrType.shortname.replace(',','<br/>');
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
