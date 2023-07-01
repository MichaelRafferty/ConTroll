var discount_memcost = 55;
var registration = null;
var dealer_req = null;
var alley_req = null;
var virtual_req = null;
var dealer_invoice = null;
var alley_invoice = null;
var virtual_invoice = null;
var update_profile = null;
var change_password = null;

// Space Request - call scripts/spaceRequest.php to add a request record
function spaceReq(space, spacename, spacetitle, spaceReq) {
    console.log("spaceReq called for " + space + ' on ' + spacename);
    var opt = document.getElementById(spacename);
    console.log(opt);
    console.log(opt.value);
    if (opt.value == 0) {
        alert("Select an amount of space to resquest");
        return;
    }
    dataobj = {
        spaceid: space,
        priceid: opt.value,
    };
    $.ajax({
        url: 'scripts/spaceReq.php',
        data: dataobj,
        method: 'POST',
        success: function (data, textstatus, jqxhr) {
            console.log(data);
            if (data['error'] !== undefined) {
                show_message(data['error'], 'error');
                return;
            }
            if (data['success'] !== undefined) {
                show_message(data['success'], 'success');
                spaceReq.hide();
                document.getElementById(data['div']).innerHTML = "<div class='col-sm-auto'><button class='btn btn-primary' onClick='location.reload()'>Click here to refresh page to update status</button></div>";
            }
            if (data['warn'] !== undefined) {
                show_message(data['warn'], 'warn');
            }
        },
        error: showAjaxError
    })
}


function alleyReq() {
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

function dealerReq() {
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

const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
const fieldlist = ["name", "email", "pw1", "pw2", "description", "addr", "city", "state", "zip"];
function register() {
    // replace validator with direct validation as it doesn't work well with bootstrap
    var valid = true;


    for (var fieldnum in fieldlist) {
        var field = document.getElementById(fieldlist[fieldnum]);
        switch (fieldlist[fieldnum]) {
            case 'email':
                if (emailRegex.test(field.value)) {
                    field.style.backgroundColor = '';
                } else {
                    field.style.backgroundColor = 'var(--bs-warning)';
                    valid = false;
                }
                break;
            case 'pw1':
                var field2 = document.getElementById("pw2");
                if (field.value == field2.value && field.value.length >= 4) {
                    field.style.backgroundColor = '';
                } else {
                    field.style.backgroundColor = 'var(--bs-warning)';
                    valid = false;
                }
                break;
            case 'pw2':
                var field2 = document.getElementById("pw1");
                if (field.value == field2.value && field.value.length >= 4) {
                    field.style.backgroundColor = '';
                } else {
                    field.style.backgroundColor = 'var(--bs-warning)';
                    valid = false;
                }
                break;
            default:
                if (field.value.length > 1) {
                    field.style.backgroundColor = '';
                } else {
                    field.style.backgroundColor = 'var(--bs-warning)';
                    valid = false;
                }
        }
    }
    if (!valid)
        return null;

    //
    $.ajax({
        url: 'scripts/registerVendor.php',
        data: $('#registrationForm').serialize(),
        method: 'POST',
        success: function(data, textstatus, jqXHR) {
            if(data['status'] == 'error') {
                alert(data['message']);
            } else {
                alert("Thank you for registering for an account with the Balticon Vendors portal.  Please login to your account to request space.");
                console.log(data);
                registrationModalClose();
            }
        }
    });
}

function updateProfile() {
    $.ajax({
        url: 'scripts/updateProfile.php',
        data: $('#vendor_update').serialize(),
        method: 'POST',
        success: function(data, textstatus, jqXHR) {
            if(data['status'] == 'error') {
                alert(data['message']);
            } else {
                console.log(data);
                update_profile.hide();
            }
        }
    });
}

function changePassword(field) {
    if (document.getElementById('pw2').value != document.getElementById('pw').value) {
        alert("New passwords do not match");
        return;
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

        dealer_invoice.show();
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
        
        alley_invoice.show();
    
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
        virtual_invoice.show();
        break;
    }
}

function registrationModalOpen() {
    if (registration != null) {
        registration.show();
    }
}

function registrationModalClose() {
    if (registration != null) {
        registration.hide();
    }
}

window.onload = function () {
    var id = document.getElementById('registration');
    if (id != null) {
        registration = new bootstrap.Modal(id, { focus: true, backdrop: 'static' });
    }
    id = document.getElementById('dealer_req');
    if (id != null) {
        dealer_req = new bootstrap.Modal(id, { focus: true, backdrop: 'static' });
    }
    id = document.getElementById('alley_req');
    if (id != null) {
        alley_req = new bootstrap.Modal(id, { focus: true, backdrop: 'static' });
    }
    id = document.getElementById('virtual_req');
    if (id != null) {
        virtual_req = new bootstrap.Modal(id, { focus: true, backdrop: 'static' });
    }
    id = document.getElementById('dealer_invoice');
    if (id != null) {
        dealer_invoice = new bootstrap.Modal(id, { focus: true, backdrop: 'static' });
    }
    id = document.getElementById('alley_invoice');
    if (id != null) {
        alley_invoice = new bootstrap.Modal(id, { focus: true, backdrop: 'static' });
    }
    id = document.getElementById('virtual_invoice_req');
    if (id != null) {
        virtual_invoice = new bootstrap.Modal(id, { focus: true, backdrop: 'static' });
    }
    id = document.getElementById('update_profile');
    if (id != null) {
        update_profile = new bootstrap.Modal(id, { focus: true, backdrop: 'static' });
    }
    id = document.getElementById('changePassword');
    if (id != null) {
        change_password = new bootstrap.Modal(id, { focus: true, backdrop: 'static' });
    }
}

var message_div = null;
// show_message:
// apply colors to the message div and place the text in the div, first clearing any existing class colors
// type:
//  error: (white on red) bg-danger
//  warn: (black on yellow-orange) bg-warning
//  success: (white on green) bg-success
function show_message(message, type) {
    "use strict";
    if (message_div === null ) {
        message_div = document.getElementById('result_message');
    }
    if (message_div.classList.contains('bg-danger')) {
        message_div.classList.remove('bg-danger');
    }
    if (message_div.classList.contains('bg-success')) {
        message_div.classList.remove('bg-success');
    }
    if (message_div.classList.contains('bg-warning')) {
        message_div.classList.remove('bg-warning');
    }
    if (message_div.classList.contains('text-white')) {
        message_div.classList.remove('text-white');
    }
    if (message === undefined || message === '') {
        message_div.innerHTML = '';
        return;
    }
    if (type === 'error') {
        message_div.classList.add('bg-danger');
        message_div.classList.add('text-white');
    }
    if (type === 'success') {
        message_div.classList.add('bg-success');
        message_div.classList.add('text-white');
    }
    if (type === 'warn') {
        message_div.classList.add('bg-warning');
    }
    message_div.innerHTML = message;
}
function clear_message() {
    show_message('', '');
}

function showAjaxError(jqXHR, textStatus, errorThrown) {
    'use strict';
    var message = '';
    if (jqXHR && jqXHR.responseText) {
        message = jqXHR.responseText;
    } else {
        message = 'An error occurred on the server.';
    }
    if (textStatus != '' && textStatus != 'error')
        message += '<BR/>' + textStatus;
    message += '<BR/>Error Thrown: ' + errorThrown;
    show_message(message, 'error');
}
