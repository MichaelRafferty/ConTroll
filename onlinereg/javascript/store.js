// count = total count of badges
// total = sum(prices) * qty of badges
// agecount = array by ageType (memAge) of counts
// badges = array of the data for individual badges
var badges = { 'count': 0, 'total': 0, 'agecount': [], 'badges': [] };
// prices = array by ageType (memAge) of prices for badges
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

// pricing area
var memSummaryDiv = null;
var totalCostDiv = null;
var subTotalColDiv = null;
var couponDiscountDiv = null;

// checkout area
var emptyCart = null;
var noChargeCart = null;
var chargeCart = null;


// convert a form post string to an arrray
// convert url parameters to associative array
function URLparamsToArray(urlargs, doTrim = false) {
    const params = new URLSearchParams(urlargs);
    const result = {};
    for (const [key, value] of params) {
        if (doTrim)
            result[key] = value.trim();
        else
            result[key] = value;
    }
    return result;
}

// process the form for validation and add to the badge array if valud
function process(formRef) {
    var valid = true;
    var formData = URLparamsToArray($(formRef).serialize(), true);

    // validation
    // emails must not be blank and must match
    if (formData['email1'] == '' || formData['email2'] == '' || formData['email1'] != formData['email2']) {
        $('#email1').addClass('need');
        $('#email2').addClass('need');
        valid = false;
    } else {
        $('#email1').removeClass('need');
        $('#email2').removeClass('need');
    }

    // first name is required
    if (formData['fname'] == '') {
        valid = false;
        $('#fname').addClass('need');
    } else {
        $('#fname').removeClass('need');
    }

    // last name is required
    if (formData['lname'] == '') {
        valid = false;
        $('#lname').addClass('need');
    } else {
        $('#lname').removeClass('need');

    }

    // address 1 is required, address 2 is optional
    if(formData['addr'] =='') {
        valid = false;
        $('#addr').addClass('need');
    } else {
        $('#addr').removeClass('need');
    }

    // city/state/zip required
    if (formData['city'] =='') {
        valid = false;
        $('#city').addClass('need');
    } else {
        $('#city').removeClass('need');
    }

    if (formData['state'] =='') {
        valid = false;
        $('#state').addClass('need');
    } else {
        $('#state').removeClass('need');
    }

    if (formData['zip']=='') {
        valid = false;
        $('#zip').addClass('need');
    } else {
        $('#zip').removeClass('need');
    }

    // a membership type is required
    if (formData['age'] =='') {
        valid = false;
        $('#age').addClass('need');
    } else {
        $('#age').removeClass('need');
    }

    if (badges['agecount'][formData['age']] == null)
        badges['agecount'][formData['age']] = 0;

    // check if there are too many limited memberships in the cart
    if (coupon.getMemGroup() == formData['age']) {
        var cur = badges['agecount'][formData['age']];
        var lim = coupon.getLimitMemberships();
        if (badges['agecount'][formData['age']] >= coupon.getLimitMemberships()) {
            alert("You already have the maximum numbero of badges of this membership type in your cart based on the coupon applied. You must choose a different membership type.");
            valid = false;
        }
    }

    // don't continue to process if any are missing
    if (!valid)
        return false;

    // clear for next use: first name, middle name, last name, suffix (entire name field set), and the badgename.  To make virtual easier, clear the email addresses.
    $('#fname').val('');
    $('#mname').val('');
    $('#lname').val('');
    $('#suffix').val('');
    $('#email1').val('');
    $('#email2').val('');
    $('#badgename').val('');

    badges['count'] +=  1;
    badges['agecount'][formData['age']] += 1;
    //badges['total'] += prices[formData['age']];
    badges['badges'].push(formData);

    repriceCart();
  
    var badgename = formData['badgename'];
    if (formData['badgename']=='') {
        badgename = (formData['fname']+" "+formData['lname']).trim();
    }

    var name = formData['fname'] + " " + formData['mname'] + " " + formData['lname'] + " " + formData['suffix'];

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
    var group_text = formData['age'].split('_');
    var age_text = group_text[group_text.length -1];
    var age_color = 'text-white';
    if (age_text != 'adult' && age_text != 'military' && age_text != 'child' && age_text != 'youth' && age_text != 'kit' && age_text != 'student')
        age_color = 'text-black';
    var labeldivtext = shortnames[formData['age']];
    var addon = '';
    if (age_text == 'unknown')
        labeldivtext = 'Unknown';
    if (group_text[0] == 'addon')
        addon += "<br/>&nbsp;Add On to<br/>&nbsp;Membership";
    var re = /\-+/g;
    labeldivtext = labeldivtext.replace(re, '-<br/>');

    var bdivid="badge" + badges['count'];
    var html = "<div id='" + bdivid + "' data-index='" + (badges['count'] - 1) + "' class='container-fluid border border-2 border-dark'>\n" +
        "  <div class='row'>\n" +
        "    <div class='col-sm-3 p-0 m-0 " + age_text + "'>\n" +
        "      <h4><span class='badge " + age_color + ' ' + age_text + "'>" + labeldivtext + "</span></h4>" + addon + "\n" +
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
    var badge_age = badges['badges'][i]['age'];

    badges['agecount'][badge_age] -= 1;
    badges['count'] -= 1;
    repriceCart();

    badges['badges'][i]={};
    toRemove.remove();
}



function updateAddr() {
    var selOpt = $("#personList option:selected");
    var optData = selOpt.data('info');
    
    $('#cc_fname').val(optData['fname']);
    $('#cc_lname').val(optData['lname']);
    $('#cc_street').val(optData['addr']);
    $('#cc_city').val(optData['city']);
    $("#cc_state").val(optData['state']);
    $('#cc_zip').val(optData['zip']);
    $('#cc_country').val(optData['country']);
    $('#cc_email').val(optData['email1']);

    $('.ccdata').attr('readonly', 'readonly');

}
   
function toggleAddr() {
    $('.ccdata').attr('readonly', false);
}

function mp_ajax_error(JqXHR, textStatus, errorThrown) {
    alert("ERROR! " + textStatus + ' ' + errorThrown);
    $('#' + $purchase_label).removeAttr("disabled");
}

function mp_ajax_success(data, textStatus, jqXHR) {
    if (data['status'] == 'error') {
        alert("Transaction Failed: " + data['data']);
        $('#' + $purchase_label).removeAttr("disabled");
    } else if (data['status'] == 'echo') {
        console.log(data);
        $('#' + $purchase_label).removeAttr("disabled");
    } else {
        window.location.href = "receipt.php?trans=" + data['trans'];
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

    $('#' + $purchase_label).attr("disabled", "disabled");
    var postdata = badges['badges'];
    if (postdata.length == 0) {
        alert("You don't have any badges to buy, please add some badges");
        if (newBadge != null) {
            newBadge.show();
        }
        return false;        
    }
    var data = {
        badges: badges,
        nonce: token,
        purchaseform: URLparamsToArray($('#purchaseForm').serialize()),
        couponCode: coupon.getCouponCode(),
        couponSerial: coupon.getCouponSerial(),
    }
    console.log("MP Data");
    console.log(data);
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
        newBadge.show();
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
    coupon.ModalOpen(badges['count']);
}

function couponModalClose() {
    coupon.ModalClose(badges['count'] == 0);
}

function addCouponCode() {
    coupon.AddCouponCode();
}

function removeCouponCode() {
    coupon.RemoveCouponCode();
}

function repriceCart() {
    //console.log(mtypes);
    //console.log(badges);
    var html = '';
    var nbrs = badges['agecount'];
    var total = 0;
    var mbrtotal = 0;
    var cartDiscountable = false;
    var couponmemberships = 0;
    var primarymemberships = 0;

    for (var row in mtypes) {
        var mbrtype = mtypes[row];
        var num = 0;
        if (nbrs[mbrtype['memGroup']] > 0) {
            num = nbrs[mbrtype['memGroup']];
            if (mbrtype['primary']) {
                primarymemberships += num;
                if (coupon.isCouponActive()) {
                    if ((coupon.memId != null && coupon.memId == mbrtype['memId']) || coupon.memId == null)
                        couponmemberships += num;
                }
                mbrtotal += num * Number(mbrtype['price']).toFixed(2)
            }
            total += num * Number(mbrtype['price']).toFixed(2);
        }
    }

    if (coupon.isCouponActive()) {
        // first compute un-discounted cart total to get is it sufficient for the discount
        if (mbrtotal >= coupon.getMinCart() && primarymemberships >= coupon.getMinMemberships()  && primarymemberships <= coupon.getMaxMemberships())
            cartDiscountable = true;
        // reset total for below
        subTotalColDiv.innerHTML = '$' + Number(total).toFixed(2);
    }

    // now compute discountable totals
    total = 0;
    var maxMbrDiscounts = coupon.getMaxMemberships();
    var couponDiscounts = 0;
    var thisDiscount = 0;
    var itemtype = '';
    for (row in mtypes) {
        mbrtype = mtypes[row];
        num = 0;
        if (nbrs[mbrtype['memGroup']] > 0) {
            num = nbrs[mbrtype['memGroup']];
        }
        // need to set num here
        if (mbrtype['memCategory'] == 'add-on' || mbrtype['memCategory'] == 'addon')
            itemtype = ' Add-ons: ';
        else
            itemtype = ' Memberships: ';

        if (mbrtype['discountable'] && cartDiscountable) {
            if (maxMbrDiscounts >= num) {
                thisDiscount = num * Number(mbrtype['discount']).toFixed(2);
                couponDiscounts += thisDiscount;
                maxMbrDiscounts -= num;
            } else {
                thisDiscount = maxMbrDiscounts * Number(mbrtype['discount']).toFixed(2);
                couponDiscounts += thisDiscount;
                maxMbrDiscounts = 0;
            }
            total += num * Number(mbrtype['price']).toFixed(2) - thisDiscount;
        } else {
            total += num * Number(mbrtype['price']).toFixed(2)
        }
        html += mbrtype['shortname'] + itemtype + num + ' x ' + mbrtype['price'] + '<br/>';
    }
    memSummaryDiv.innerHTML = html;
    badges['total'] = total;

    html = '';
    if (cartDiscountable)  {
        var cartDiscount = coupon.CartDiscount(mbrtotal);
        couponDiscounts += cartDiscount;
        total -= cartDiscount;
    }
    couponDiscountDiv.innerHTML = "$" + Number(couponDiscounts).toFixed(2) + html;
    totalCostDiv.innerHTML = "$" + Number(total).toFixed(2) + html;

    // now set the proper div for the payment
    emptyCart.hidden =  primarymemberships > 0;
    noChargeCart.hidden = primarymemberships == 0 || badges['total'] > 0;
    chargeCart.hidden = primarymemberships == 0 || badges['total'] == 0;
}

function togglePopup() {
    if (anotherBadge != null) {
        anotherBadge.hide();
    }
    if (newBadge != null) {
        newBadge.show();
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
    }

    emptyCart = document.getElementById("emptyCart");
    noChargeCart = document.getElementById("noChargeCart");
    chargeCart = document.getElementById("chargeCart");

    coupon = new Coupon();
    memSummaryDiv = document.getElementById('memSummaryDiv');
    totalCostDiv = document.getElementById('totalCostDiv');
    subTotalColDiv = document.getElementById('subTotalColDiv');
    couponDiscountDiv = document.getElementById('couponDiscountDiv');


    for (var row in mtypes) {
        var mbrtype = mtypes[row];
        var group = mbrtype['memGroup'];
        prices[group]= Number(mbrtype['price']);
        badges['agecount'][group] = 0;
        shortnames[group] = mbrtype['shortname'];
        mbrtype['primary'] = !(mbrtype['price'] == 0 || (mbrtype['memCategory'] != 'standard' && mbrtype['memCategory'] != 'virtual'));
        mbrtype['discount'] = 0;
        mbrtype['discountable'] = false;
    }

    repriceCart();

    if (coupon.couponError() == false)
        newBadge.show();

}
