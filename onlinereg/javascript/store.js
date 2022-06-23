var badges ={'count': 0, 'total':0, 
    'adults':0, 'militarys':0, 'youths':0, 'childs':0, 'kits':0, 'badges':[]};
var prices = {};
var $purchase_label = 'purchase';

//function flashSect2(sect, i, max) {
//  if(i < max) { 
//    $(sect).toggleClass('highlight');
//    setTimeout(function() { flashSect2(sect, i+1, max); }, 600);
//  }
//}

function setPrice(group, price) {
  prices[group]=price;
}

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
  if($('#email1').val() =='' || $('#email2').val() == '' || $('#email1').val() != $('#email2').val()) { 
    $('#email1').addClass('need');
    $('#email2').addClass('need');
    valid=false;
  } else {
    $('#email1').removeClass('need');
    $('#email2').removeClass('need');
  }

  if($('#fname').val()=='') { valid = false; $('#fname').addClass('need'); }
  else { $('#fname').removeClass('need'); }
  if($('#lname').val()=='') { valid = false; $('#lname').addClass('need'); }
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


  if(!valid) { return false; }


  var formData = formObj.serializeObject();
  
  $('#fname').val('');
  $('#mname').val('');
  $('#lname').val('');
  $('#suffix').val('');
  $('#badgename').val('');

  var badgeList = $('#badge_list');
  var adults = $('#adults');
  var militarys = $('#militarys');
  var youths= $('#youths');
  var childs = $('#childs');
  var kits = $('#kits');
  var total = $('#total');

  
  //var badges ={'total':0, 'adults':0, 'childs':0, 'badges':[]};

  badges['count']= badges['count']+1;
  if(formData['age']=='adult') { badges['adults']=badges['adults']+1; }
  if(formData['age']=='military') { badges['militarys']=badges['militarys']+1; }
  if(formData['age']=='youth') { badges['youths']=badges['youths']+1; }
  if(formData['age']=='child') { badges['childs']=badges['childs']+1; }
  if(formData['age']=='kit') { badges['kits']=badges['kits']+1; }
  badges['total'] += prices[formData['age']];
  badges['badges'].push($.extend(true, {}, formData));

  adults.empty().text(badges['adults']);
  militarys.empty().text(badges['militarys']);
  youths.empty().text(badges['youths']);
  childs.empty().text(badges['childs']);
  kits.empty().text(badges['kits']);
  total.empty().text(badges['total']);

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
       
    var age_text = formData['age'];
    if (age_text != 'adult' && age_text != 'military' && age_text != 'child' && age_text != 'youth' && age_text != 'kit') {
        age_text = 'unknown';
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

    var labeldivtext = age_text;
    if (age_text == 'kit') {
        labeldivtext = 'in tow';
    } else if (age_text == 'youth') {
        labeldivtext = 'YA';
    }
    labelDiv.html('<h4><span class="badge text-white"' + age_text + '">' + labeldivtext + '</span></h4>');

    $('#badge_list').append(badgeDiv);
  //if(badgeDetails.width() > (badgeDiv.width()-65)) { 
  //  badgeDiv.width(badgeDetails.width()+65);
  //}


    updateAddr();
    $('#oldBadgeName').empty().append(name);
    $('#anotherBadge').dialog('open');
}

function removeBadge(index) {
  var toRemove = $('#'+index);
  var i = toRemove.data('index');
  var badge_age = badges['badges'][i]['age'];
  badges[badge_age+'s'] -= 1;
  badges['count'] -= 1;
  badges['total'] -= prices[badges['badges'][i]['age']];

  $('#adults').empty().text(badges['adults']);
  $('#militarys').empty().text(badges['militarys']);
  $('#youths').empty().text(badges['youths']);
  $('#childs').empty().text(badges['childs']);
  $('#kits').empty().text(badges['kits']);
  $('#total').empty().text(badges['total']);

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
        $('#newBadge').dialog('open');
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
