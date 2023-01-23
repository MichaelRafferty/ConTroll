var discount_memcost=55;

function virtual_req() {
    $.ajax({
        url: 'scripts/requestVirtual.php',
        data: $('#virtual_req_form').serialize(),
        method: 'POST',
        success: function(data, textstatus, jqXHR) {
            if(data['status']=='error') {
                alert(data['message']);
            } else {
                //alert("Thank you for your interest in Joining our virtual " + data['virtual'] + " space. Your .");
                console.log(data)
                openInvoice('virtual',1,20);
            }
        }
    });
}

function alley_req() {
    $.ajax({
        url: 'scripts/requestAlley.php',
        data: $('#alley_req_form').serialize(),
        method: 'POST',
        success: function(data, textstatus, jqXHR) {
            if(data['status']=='error') {
                alert(data['message']);
            } else {
                alert("you requested " + data['alley'] + " tables.  Thank you for your interest, your request has been sent to the artist alley coordinator who may contact you with more questions.");
                console.log(data)
            }
        }
    });
}

function dealer_req() {
    $.ajax({
        url: 'scripts/requestDealer.php',
        data: $('#dealer_req_form').serialize(),
        method: 'POST',
        success: function(data, textstatus, jqXHR) {
            if(data['status']=='error') {
                alert(data['message']);
            } else {
                alert("you requested " + data['dealer_6'] + " 6x6 spaces and " + data['dealer_10'] + " 10x10 spaces.  Thank you for your interest, your request has been sent to the dealers room coordinator who may contact you with more questions.");
                console.log(data)
            }
        }
    });
}

function register() {
    if(!$("#vendor_reg").valid()) { 
        alert("Please correct problems with the form");
        return null;
    } 
    $.ajax({
        url: 'scripts/registerVendor.php',
        data: $('#vendor_reg').serialize(),
        method: 'POST',
        success: function(data, textstatus, jqXHR) {
            if(data['status'] == 'error') {
                alert(data['message']);
            } else {
                alert("Thank you for registering for an account with the Balticon Vendors portal.  Please login to your account to request space.");
                console.log(data);
                $('#registration').dialog('close');
            }
        }
    });
}

function updateProfile() {
    if(!$("#vendor_update").valid()) { 
        alert("Please correct problems with the form");
        return null;
    } 
    $.ajax({
        url: 'scripts/updateProfile.php',
        data: $('#vendor_update').serialize(),
        method: 'POST',
        success: function(data, textstatus, jqXHR) {
            if(data['status'] == 'error') {
                alert(data['message']);
            } else {
                console.log(data);
                $('#updateProfile').dialog('close');
            }
        }
    });
}

function forceChangePassword() {
    if(!$("#changepw").valid()) { 
        alert("Please correct problems with the form");
        return null;
    } 
    $.ajax({
        url: 'scripts/changePassword.php',
        data: $('#changepw').serialize(),
        method: 'POST',
        success: function(data, textstatus, jqXHR) {
            if(data['status'] == 'error') {
                alert(data['message']);
            } else {
                console.log(data);
                location.reload();
            }
        }
    });
}

function changePassword() {
    if(!$("#changepw").valid()) { 
        alert("Please correct problems with the form");
        return null;
    } 
    $.ajax({
        url: 'scripts/changePassword.php',
        data: $('#changepw').serialize(),
        method: 'POST',
        success: function(data, textstatus, jqXHR) {
            if(data['status'] == 'error') {
                alert(data['message']);
            } else {
                console.log(data);
                $('#changePassword').dialog('close');
            }
        }
    });
}

function resetPassword() {
    var email = prompt('What is your login email?');
    $.ajax({
        method: 'GET',
        url: 'scripts/resetPassword.php',
        data: {'login' : email},
        success: function(data, textStatus, jqXhr) {
            if(data['status'] == 'error') {
                alert(data['message']);
            } else {
                console.log(data);
                $('#notes').empty().html("<p>A password reset email has been sent to " + data['email'] + " please change your password as soon as you login.<br/>" +
                    "Please check your spam folder, but if you did not receive an email, or have any other problems, please contact artshow@bsfs.org for assistance.</p>");
            }
        }
    });
}

function request(access) {
    $.ajax({
        method: "POST",
        url: 'scripts/makeRequest.php',
        data: {'access' : access},
        success: function(data, textStatus, jqXhr) {
            if(data['status'] == 'error') {
                alert(data['message']);
            } else {
                console.log(data);
                //location.reload();
            }
        }
    });
}

function makePurchase($token, $label) {
    switch ($label) {
        case 'card-button1': // artist alley
            sendPayment('alley_invoice', $token);
            break;
        case 'card-button2': // dealer
            sendPayment('dealer_invoice', $token);
            break;
        default:
            alert("Unknown Purchase Request");
    }
    return;
}

function sendPayment(invoice, $token) {
    var formData = $('#' + invoice + '_form').serialize()
    formData += "&nonce=" + $token;
    $.ajax({
        url: 'scripts/'+invoice+'Payment.php',
        method: 'POST',
        data: formData,
        success: function(data, textStatus, jqXhr) {
            console.log(data);
            if(data['status'] == 'error') {
                alert(data['message']);
            } else if (data['status'] == 'success') {
                //alert('call succeeded');
                alert("Welcome to Balticon 56's Vendor Space. You may contact dealers@balticon.org or artist_alley@balticon.org with any questions.  One of our coordinators will be in touch to help you get setup.");
                location.reload();
            } else {
                alert('There was an unexpected error, please email dealers@balticon.org or artist_alley@balticon.org to let us know.  Thank you.');
            }
        }
    });
}

function updateMemCount(mem, box) {
    var mem_cost = parseInt($('#alley_mem_cost').val());
    var total_cost = parseInt($('#alley_total').val());
    if(box.checked) {
        mem_cost -= discount_memcost;
        total_cost -= discount_memcost;
    } else {
        mem_cost += discount_memcost;
        total_cost += discount_memcost;
    }

    $('#alley_member_cost').text(mem_cost);
    $('#alley_member_total').val(mem_cost);
    $('#alley_total').val(total_cost);
    $('#alley_total_cost').text(total_cost);

}

function updateDealerPaid() {
    var num = $('#dealer_num_paid').val();

    switch(num) {
        case '1':
            $('#dealer_paid1').show();
            $('#dealer_paid2').hide();
            break;
        case '2':
            $('#dealer_paid1').show();
            $('#dealer_paid2').show();
            break;
        case '0':
        default:
            num='0';
            $('#dealer_paid1').hide();
            $('#dealer_paid2').hide();
            break;

    }
    $('#dealer_mem_cost').text(num * 55);
    $('#dealer_paid_mem_count').val(num);
    $('#dealer_invoice_cost').text((num * 55) + parseInt($('#dealer_space_cost').text()));
    $('#dealer_cost').val((num * 55) + parseInt($('#dealer_space_cost').text()));
}

function openInvoice(invoice, count, price, type="") {
    switch(invoice) {
      case 'dealer':
        var t_size = 0;
        if(type == 'dealer_10') { t_size=10; } 
        else {t_size=6;}

        $('#dealer_count').text(count);
        $('#dealer_item_count').val(count);
        $('#dealer_price').text(price);
        $('#dealer_size').text(t_size + "x" + t_size); 
        $('#dealer_type').val(t_size);
        var cost = count * price;
        $('#dealer_space_cost').text(cost);
        $('#dealer_space_sub').val(cost);
        $('#dealer_invoice_cost').text(cost);
        $('#dealer_cost').val(cost);
        $('#dealer_mem_count').text(count);
        $('#dealer_free_mem').val(count);
        $('#dealer_mem_cost').text();
        
        if(count < 2) {
           $('#dealer_mem2').hide(); 
        }
        $('#dealer_paid1').hide(); 
        $('#dealer_paid2').hide(); 

        $('#dealer_invoice').dialog('open');
        break;
      case 'alley':
        $('#alley_count').text(count);
        $('#alley_item_count').val(count);
        $('#alley_membership_count').text(count);
        $('#alley_mem_count').val(count);
        $('#alley_price').text(price);
        $('#alley_mem_price').text('55');
        $('#alley_mem_cost').val(discount_memcost);
        var mem_cost = discount_memcost * count;
        $('#alley_member_cost').text(mem_cost);
        $('#alley_member_total').val(mem_cost);
        var cost = count * price;
        $('#alley_total_cost').text(cost);
        $('#alley_invoice_cost').text(cost);
        $('#alley_table_cost').val(cost);

        $('#alley_total').val(cost+mem_cost);
        $('#alley_total_cost').text(cost+mem_cost);
        
        $('#alley_invoice').dialog('open');
    
        if(count < 2) {
            $('#alley_mem2_need').hide();
            $('#alley_mem2').hide();
        }

        break;
      case 'virtual':
        $('#virtual_price').text(price);
        $('#virtual_total_cost').text(price);
        $('#virtual_total').val(price);
        $('#virtual_table_count').val(1);
        $('#virtual_table_sub').val(price);
        $('#virtual_invoice').dialog('open');
        break;
    }
}
