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

$.fn.serializeObject = function()
{
    var o = {};
    var a = this.serializeArray();
    $.each(a, function() {
        if (o[this.name] !== undefined) {
            if (!o[this.name].push) {
                o[this.name] = [o[this.name]];
            }
            o[this.name].push(this.value || '');
        } else {
            o[this.name] = this.value || '';
        }
    });
    return o;
};


function process(formObj) {
    var valid = true; 
    if ($('#email1').val() == '' || $('#email2').val() == '' || $('#email1').val() != $('#email2').val()) {
        $('#email1').addClass('need');
        $('#email2').addClass('need');
        valid = false;
    } else {
        $('#email1').removeClass('need');
        $('#email2').removeClass('need');
    }

    if ($('#fname').val() == '') { valid = false; $('#fname').addClass('need'); }
    else { $('#fname').removeClass('need'); }
    if ($('#lname').val() == '') { valid = false; $('#lname').addClass('need'); }
    else { $('#lname').removeClass('need'); }
    if($('#addr').val()=='') { valid = false; $('#addr').addClass('need'); }
    else { $('#addr').removeClass('need'); }
    if($('#city').val()=='') { valid = false; $('#city').addClass('need'); }
    else { $('#city').removeClass('need'); }
    if($('#state').val()=='') { valid = false; $('#state').addClass('need'); }
    else { $('#state').removeClass('need'); }
    if($('#zip').val()=='') { valid = false; $('#zip').addClass('need'); }
    else { $('#zip').removeClass('need'); }
    if($('#age').val()=='') { valid = false; $('#age').addClass('need'); }
    else { $('#age').removeClass('need'); }

    if (!valid) { return false; }


    var formData = formObj.serializeObject();
  
    $('#fname').val('');
    $('#mname').val('');
    $('#lname').val('');
    $('#suffix').val('');
    $('#suffix').val('');
    $('#badgename').val('');

    // reference to badge_list area of screen
    var badgeList = $('#badge_list');
    // reference to tolal cost on screen
    var total = $('#total');

    badges['count'] +=  1;
    badges['agecount'][formData['age']] += 1;
    //badges['total'] += prices[formData['age']];
    badges['badges'].push($.extend(true, {}, formData));

    repriceCart();
  
  var badgename = formData['badgename'];
  if(formData['badgename']=='') { 
    badgename = formData['fname']+" "+formData['lname']; 
  }

  var name = formData['fname'] + " " + formData['mname'] + " " 
    + formData['lname'] + " " + formData['suffix'];

  var option = $(document.createElement('option'))
    .append(name)
    .data('info', formData)
    .attr('value', name);
  $("#personList").append(option);

    if ($("#personList").val() == undefined) { $("#personList").val(name); }

    var thisid = 'badge' + badges['count'];
    // blocks for each badge
    var badgeDiv = $(document.createElement('div'))
        .attr('id', thisid)
        .data('index', badges['count'] - 1)
        .attr('class', 'container-fluid border border-2 border-dark');

    var optDiv = $(document.createElement('div'))
        .addClass('col-1')
        .append($(document.createElement('button'))
            .append('X')
            .data('index', thisid)
            .addClass("btn btn-sm btn-secondary")
            .on('click', function () { removeBadge($(this).data('index')); })
    );

    var blockDiv = $(document.createElement('div'))
        .addClass('row');
    var labelDiv = $(document.createElement('div'))
        .addClass('col-3 p-0 m-0');
       
    var group_text = formData['age'].split('_');
    var age_text = group_text[group_text.length -1];
    var age_color = 'text-white';
    if (age_text != 'adult' && age_text != 'military' && age_text != 'child' && age_text != 'youth' && age_text != 'kit' && age_text != 'student') {
        age_color = 'text-black';
        labelDiv.addClass('unknown');
    } else {
        labelDiv.addClass(age_text)
    }

    var badgeDetails = $(document.createElement('div'))
        .addClass('col-8')
        .html("<p class='text-body'>Full Name:<br/><strong>" + name + "<br/></strong>Badge Name:<br/><strong>" + badgename + "</strong></p>");

    badgeDiv.append(blockDiv);
    blockDiv.append(labelDiv);
    blockDiv.append(badgeDetails);
    blockDiv.append(optDiv);

    var labeldivtext = shortnames[formData['age']];
    var addon = '';
    if (age_text == 'unknown')
        labeldivtext = 'Unknown';
    if (group_text[0] == 'addon')
        addon += "<br/>&nbsp;Add On to<br/>&nbsp;Membership";

    labelDiv.html('<h4><span class="badge ' + age_color + '"' + age_text + '">' + labeldivtext + '</span></h4>' + addon);

    $('#badge_list').append(badgeDiv);

    updateAddr();
    $('#oldBadgeName').empty().append(name);
    newBadgeModalClose();
    anotherBadgeModalOpen();
}

function removeBadge(index) {
    var toRemove = $('#'+index);
    var i = toRemove.data('index');
    var badge_age = badges['badges'][i]['age'];

    badges['agecount'][badge_age] -= 1;
    badges['count'] -= 1;
    //badges['total'] -= prices[badge_age];

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

function buildBadgeDiv(b) {
  var badgeDiv = "Name: " + b['fname'] + " " +b['mname'] + " " + b['lname'] + " " + b['suffix'] + "<br/>";

  badgeDiv += "Badge Name: ";
  if(b['badgename']=="") { badgeDiv += b['fname'] + " " + b['lname']; }
  else { badgeDiv += b['badgename']; }
  badgeDiv += "<br/>";
  badgeDiv += b['age'] + " ($" + b['price'] + ")";
  return badgeDiv;
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
    } else {
        window.location.href = "receipt.php?trans=" + data['trans'];
        $('#' + $purchase_label).removeAttr("disabled");
    }
}
    
function makePurchase($token, $label) {
    if ($label != '') {
        $purchase_label = $label;
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

    $.ajax({
        url: "scripts/makePurchase.php",
        data: $('#purchaseForm').serialize()
            + "&total=" + badges['total']
            + "&badgeList=" + JSON.stringify(postdata)
            + "&nonce=" + $token,
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
    coupon.ModalOpen();
}

function couponModalClose() {
    coupon.ModalClose();
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
    for (var row in mtypes) {
        var mbrtype = mtypes[row];
        var num = 0;
        if (nbrs[mbrtype['memGroup']] > 0) {
            num = nbrs[mbrtype['memGroup']];
        }
        // need to set num here
        if (mbrtype['discount'] > 0) {
            html += mbrtype['shortname'] + ' Memberships: ' + num + ' x (' + mbrtype['price'] + ' - ' + mbrtype['discount'] + ' = ' + Number(mbrtype['price'] - mbrtype['discount']).toFixed(2) + ')'  + '<br/>';
            total += num * Number(mbrtype['price'] - mbrtype['discount']).toFixed(2)
        } else {
            html += mbrtype['shortname'] + ' Memberships: ' + num + ' x ' + mbrtype['price'] + '<br/>';
            total += num * Number(mbrtype['price']).toFixed(2)
        }
    }
    memSummaryDiv.innerHTML = html;
    badges['total'] = total;
    totalCostDiv.innerHTML = "Total Cost: $" + total.toFixed(2);
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
        //newBadge.show();
    }

    coupon = new Coupon();
    memSummaryDiv = document.getElementById('memSummaryDiv');
    totalCostDiv = document.getElementById('totalCostDiv');

    for (var row in mtypes) {
        var mbrtype = mtypes[row];
        var group = mbrtype['memGroup'];
        prices[group]= Number(mbrtype['price']);
        badges['agecount'][group] = 0;
        shortnames[group] = mbrtype['shortname'];
    }

    repriceCart();
}
