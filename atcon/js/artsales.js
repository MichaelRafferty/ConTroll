$(document).ready(function() {
    $('#chpw').hide();
});

function setUser(data, textStatus, jqXHR) {
    $('#initialDialog').dialog('close');
    if(data['error'] != undefined) {
        $("#test").empty().append(data['error']); 
        return false;
    }
    $('#test').empty().append(JSON.stringify(data, null, 2));
    $('#userPerid').empty().append(data['id']);
    $('#userName').empty().append(data['name']);
    if(data['badge'] != undefined && data['badge'] != '') {
        $('#userBadge').empty().append('(' + data['badge'] + ')') ;
    } else {
        $('#userBadge').empty();
    }


    $('#cartList').empty();
    if(data['items']) {
        alert('items already reserved');
    }

    $('#userDiv').data('perid', data['id']);
    $('#userDiv').data('paid', 0);
    $('#userDiv').data('subtotal',0);
    $('#userDiv').data('tax',0);
    $('#userDiv').data('total',0);
}

function fetchAnon() {
    $('#fetchUserId').val('15832');
    $('#fetchUserSubmit').click();
}

function getItem() {
    var artist = $('#artArtist').val();
    var item = $('#artItem').val();

    var itemList = $('#userDiv').data('items');
    if(itemList == undefined) {
        itemList = [];
    }
    if(itemList[artist + '_' + item] != undefined) {
        alert("Item already in list");
    } else {
        $.ajax({
            data: "artist="+artist+"&item="+item,
            method: "POST",
            url: "scripts/artItem.php",
            success: function(data, textStatus, jqXhr) {
                if(data['noitem']!=undefined) {
                    alert("No matching Item Found");
                } else {
                    addItem(data['item']);
                }
                // $('#test').empty().append(JSON.stringify(data, null, 2));
            }
        });
    }
}

function checkbox(name, check, disable) {
    var checkbox = $(document.createElement('input'))
        .attr('type', 'checkbox')
        .attr('name', name)
        .attr('id', name)
        .attr('checked', check)
        .attr('disabled', disable);

    return checkbox;
}

function removeItem(name) {
    $('#' + name).remove();

    var newItemList = [];
    var oldItemList = $('#userDiv').data('items');
    for(var i = 0; i< oldItemList.length; i++) {
        if(oldItemList[i] != name) {
            newItemList.push(oldItemList[i])
        }
    }

    $('#userDiv').data('items',newItemList);
    updateTotal();
}

function removeBtn(art_key, item_key) {
    var btn = $(document.createElement('button'))
        .on('click', function() {removeItem(art_key + '_' + item_key)})
        .append('X');

    return btn;
}

function addItem(item) {
    if((item.type == 'nfs') || (item.status == 'Not For Sale')){
        alert("Item is Not For Sale");
        return false;
    }
    if((item.status != 'Checked In') &&
       (item.status != 'Sold Bid Sheet') &&
       (item.status != 'To Auction')){ 
        alert("Item is not currently available for checkout.\n" +
              "It may be sold, marked as bid, or not checked in by the artist");
        return false; 
    }

    var itemRow = $(document.createElement('tr'));
    itemRow.attr('id', item.art_key + '_' + item.item_key)
    itemRow.data('artist', item.art_key);
    itemRow.data('item', item.item_key);
    itemRow.attr('type', item.type);

    var artistName = item.name;
    if(item.art_name != undefined && item.art_name != '') {
        artistName = item.art_name;
    }
    itemRow.append($(document.createElement('td')).append(item.title));
    itemRow.append($(document.createElement('td')).append(artistName));
    itemRow.append($(document.createElement('td')).append(item.type));
    if(item.type == 'print') {
        itemRow.data('price', item.sale_price);
        itemRow.append($(document.createElement('td'))
            .append(item.sale_price));
        itemRow.append($(document.createElement('td'))
            .append($(document.createElement('input'))
                .attr('type', 'number')
                .attr('key', item.art_key + '_' + item.item_key)
                .attr('id', 'qty_' + item.art_key + '_' + item.item_key)
                .attr('max', item.quantity)
                .attr('min', 0)
                .addClass('quantity')
                .val(1)
                .on('change', function(e) {
                    var key = $(this).attr('id');
                    var price = +$('#' + key).data('price');
                    var qty = +$('#qty_' + key).val();
                    $('#total_'+key).empty().append(qty * price);
                    updateTotal();
                })));
        itemRow.append($(document.createElement('td'))
            .attr('id', 'total_' + item.art_key + '_' + item.item_key)
            .append(item.sale_price));
        itemRow.append($(document.createElement('td'))
            .append(checkbox('depart_' + item.art_key + '_' + item.item_key,
                             'checked', 'disabled')));
        itemRow.append($(document.createElement('td'))
            .append(removeBtn(item.art_key, item.item_key)));
    }

    today =new Date()
    if(item.type == 'art') {
        itemRow.append($(document.createElement('td'))
            .attr('colspan', 2)
            .append('Min: $' + item.min_price + ' QS: $' + item.sale_price));
        itemRow.append($(document.createElement('td'))
            .append($(document.createElement('input'))
                .attr('type', 'number')
                .attr('min', item.min_price)
                .attr('id', 'total_' + item.art_key + '_' + item.item_key)
                .addClass('required')
                .addClass('bid')
                .on('change', function(e) { updateTotal(); })));
        itemRow.append($(document.createElement('td'))
            .append(checkbox('depart_' + item.art_key + '_' + item.item_key,
                             (today.getDay() < 3)?'checked':undefined, undefined)));
        itemRow.append($(document.createElement('td'))
            .append(removeBtn(item.art_key, item.item_key)));
    }

    var itemList = $('#userDiv').data('items');
    if(itemList == undefined) {
        itemList = [item.art_key + '_' + item.item_key];
    } else {
        itemList.push(item.art_key + '_' + item.item_key);
    }
    $('#userDiv').data('items', itemList);

    $('#cartList').append(itemRow);
    updateTotal();
}

function artList() {
    var itemList = $('#userDiv').data('items');
    var resultList = [];

    for(i in itemList) {
        item = itemList[i];

        var type = $('#' + item).attr('type');
        var artistN = $('#' + item).data('artist');
        var itemN = $('#' + item).data('item');
        var depart = ($('#depart_' + artistN + '_' + itemN).is(':checked'))?
            'departing':'staying';

        resItem = {artist: artistN,
                   item: itemN,
                   depart: depart,
                   type: type};
        
        switch(type) {
            case 'art':
                resItem['bid'] = +$('#total_' + item).val();
                break;
            case 'print':
                qty = +$('#qty_' + item).val();
                resItem['qty']=qty;
                break;
            default:
                alert("Bad Item Type in " + item);
        }

        resultList.push(resItem);
    }

    return(resultList);
}

function updateTotal() {
    var itemList = $('#userDiv').data('items');
    var subtotal = 0;

    for(i in itemList) {
        item = itemList[i];

        var type = $('#' + item).attr('type');
        switch(type) {
            case 'art':
                subtotal += +$('#total_' + item).val();
                break;
            case 'print':
                var price = +$('#' + item).data('price');
                var qty = +$('#qty_' + item).val();
                subtotal += price * qty;
                break;
            default:
                alert("Bad Item Type in " + item);
        }
    }

    $('#userDiv').data('subtotal', subtotal);
    $('#cartSubtotal').empty().append(subtotal);

    var tax = Math.round(subtotal * 0.06 * 100 + 0.01)/100
    $('#userDiv').data('tax', tax);
    $('#cartTax').empty().append(tax.toFixed(2));

    $('#userDiv').data('total', subtotal + tax);
    $('#cartTotal').empty().append((subtotal + tax).toFixed(2));

    $('#transTotal').empty().append((subtotal + tax).toFixed(2));
    $('#userDiv').data('total', subtotal+tax);

    var paid = $('#userDiv').data('paid');
    $('#transPaid').empty().append(paid.toFixed(2));
    if(paid > (subtotal+tax)) {
        $('#transChange').addClass('red')
            .empty().append("(" + (subtotal + tax - paid).toFixed(2) + ")");
    } else {
        $('#transChange').removeClass('red')
            .empty().append((subtotal + tax - paid).toFixed(2));
    }

    if(subtotal > paid) {
        $('.payment').attr('disabled', false);
    } else {
        $('.payment').attr('disabled', 'disabled');
    }
}

function takePayment(type) {
  var paymentData = {
    type: type,
    category: 'reg',
  };
  switch(type) {
    case "check":
      $('#checkTransactionId').empty().append($('#transactionForm').data('id'));
      $('#checkNo').val('');
      $('#checkAmt').val('');
      $('#checkDesc').val('');
      $('.payBtn').removeAttr('disabled');
      $('#checkPayment').dialog('open');
      break;
    case "cash":
      $('.payBtn').removeAttr('disabled');
      $('#cashTransactionId').empty().append($('#transactionForm').data('id'));
      $('#cashAmt').val('');
      $('#cashDesc').val('');
      $('#cashPayment').dialog('open');
      break;
    case "credit":
      $('.payBtn').removeAttr('disabled');
      $('#creditTransactionId').empty().append($('#transactionForm').data('id'));
	$('#offlineTransactionId').empty().append($('#transactionForm').data('id'));
    $('#offlineView').val('');
    $('#offlineAmt').val('');
    $('#offlineDesc').val('');
      $('#offline').dialog('open');

      $('#creditAmt').val('');
      $('#creditTrack').val('');
      $('#creditDesc').val('');
      $('#creditNum').val('');
      $('#creditFirstName').val('');
      $('#creditLastName').val('');
      $('#creditExpMo').val('');
      $('#creditExpYr').val('');
      break;
    case "discount":
        $('.payBtn').removeAttr('disabled');
      $('#discountTransactionId').empty().append($('#transactionForm').data('id'));
      $('#discountAmt').val('');
      $('#discountDesc').val('');
      $('#discountPayment').dialog('open');
      break;
    default:
      alert("Unknown Payment Type");
  }


  $('#' + type + 'PaymentSub').empty()
    .append('$' + $('#userDiv').data('subtotal').toFixed(2));
  $('#' + type + 'PaymentTax').empty()
    .append('$' + $('#userDiv').data('tax').toFixed(2));
  $('#' + type + 'PaymentTotal').empty()
    .append('$' + $('#userDiv').data('total').toFixed(2));

  if(type == 'credit') {
    $('#offlineView').val($('#userDiv').data('total').toFixed(2));
    $('#offlineAmt').val($('#userDiv').data('total').toFixed(2));
    $('#creditView').val($('#userDiv').data('total').toFixed(2));
    $('#creditAmt').val($('#userDiv').data('total').toFixed(2));
    $('#offlineCode').focus();
  }
}

function makePayment(type) {
    $(".payBtn").attr('disabled', 'disabled');
    var description = "";
    if(type == 'check') { description = $('#checkNo').val(); }
    description += ": " + $("#" + type + "Desc").val();

    var postData = "perid=" + $('#userDiv').data('perid')
        + "&amount=" + $("#" + type + "Amt").val()
        + "&description=" + description
        + "&type=" + type
        + "&items=" + JSON.stringify(artList());

    if($("#userDiv").data('transid') != undefined) {
        postData += "&transid="+$("#userDiv").data('transid');
    }
    if(type=="credit") {
	postData += "&cc_approval_code="+$("#offlineCode").val();
    }

    $.ajax({
        url: "scripts/atconArtPayment.php",
        method: "POST",
        data: postData,
        error: function(jqXHR, textStatus, errorThrown) {
            showError(JSON.stringify(jqXHR, null, 2));
        },
        success: function(data, textStatus, jqXHR) {
            $('#userDiv').data('transid', data['transid']);
            $('#userDiv').data('payment', data['payment']);
            if(data['error'] && data['error']!='') { 
                $('#test').empty().append(data['error']); 
                alert(data['error']);
                if(data['payError'] && data['payError']!='') { 
                    $('#test').append(data['payError']); 
                }
            } else if(data['payError'] && data['payError']!='') { 
                $('#test').empty().append(data['payError']); 
            }

            var paid = +$("#userDiv").data('paid');
            if(!isNaN(data['amount'])) {
                paid += +data['amount'];
            }

            if(data['change'] > 0) { alert("Change Due: " + data['change']); }

            if(data['type'] == 'credit') {
                checkSignature(data['transid'], data['payment'], true);
            } else if(data['complete']=='true') {
                checkReceipt(data['transid']);
            }
        }
    });

}

function completeTransaction(trans) {
    window.location.href=window.location.pathname;
}

function checkSignature(transid, payment, first) {
    $('#signatureHolder').empty().append("Printing Signature for " + transid + ".");
    $('#signatureHolder').data('transid', transid);
    $('#signatureHolder').data('payment', payment);
    $('#signature').dialog('open');
    if(!first) {
        $.ajax({
            method: "POST",
            url: "scripts/atconPrintSignature.php",
            data: {transid: transid,
                payment: payment,
            }
        });
    }
}
function checkReceipt(transid) {
    $('#receiptHolder').empty().append("Printing Receipt for " + transid + ".");
    $('#receiptHolder').data('transid', transid);
    $('#receipt').dialog('open');
    $.ajax({
        method: "POST",
        url: "scripts/atconArtReceipt.php",
        data: {transid: transid},
        success: showReceipt
    });
}

function showReceipt(data, textStatus, jqXhr) {

    $('#receiptHolder').empty().append(
        "Printing Receipt " + data['transid'] + "<br/><pre>"
        + JSON.stringify(data['artItems'], null, 2) + "</pre>"
        + "Total Due: " + data['transinfo']['withtax'] + "<br/>"
        + "Paid: " + data['transinfo']['amount'] + "<br/>"
        + "Change: " + data['transinfo']['change_due'] + "<br/>"
    );
}
