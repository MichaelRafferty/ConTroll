// Balticon Reg System
// ATCON System
// Author: Syd Weinstein
// Client Side for index.php
//      Login
//      Logout
//      Change Password

// Change Password Support
// Placeholders for field elements
var change_passwd_btn = null;
var old_password = null;
var new_password = null;
var confirm_password = null;
var message_div = null;
var idval = null;

// change_pw: called by the "Change Password" button
// fields_ok = no errors detected, when false, stop processing for more errors and prevent password change
// message = message to display in message_div for progress/warning/error messages
function change_pw() {
    "use strict";
    var fields_ok = true;
    var message = '';
    var type = '';
    if (change_passwd_btn === null) {
        // Only initialize the element placeholders once per load, can wait until called as not needed until then,
        // preventing need for an atload function
        change_passwd_btn = document.getElementById("change_pw_btn");
        old_password = document.getElementById("old_password");
        new_password = document.getElementById("new_password");
        confirm_password = document.getElementById("confirm_new");
        idval = document.getElementById("idval");
        message_div = document.getElementById("result_message");
    }

    // prevent pressing the button again while the back end is processing the password change
    change_passwd_btn.disabled = true;

    // validation checks before actually changing the password
    // not using form validation because there is no 'form submit' involved

    // a new password is required
    if (new_password.value === '') {
        fields_ok = false;
        if (message !== '') {
            message += ', ';
        }
        message += "New Password required";
        type = 'warn';
        new_password.style.backgroundColor = 'var(--bs-warning)';
    } else {
        new_password.style.backgroundColor = '';
    }
    // a confirm_password is required
    if (confirm_password.value === '') {
        fields_ok = false;
        if (message !== '') {
            message += ', ';
        }
        message += "Confirm New password required";
        type = 'warn';
        confirm_password.style.backgroundColor = 'var(--bs-warning)';
    } else {
        confirm_password.style.backgroundColor = '';
    }

    // if ok so far, check or match of new and confirm passwords
    if (fields_ok && new_password.value !== confirm_password.value) {
        fields_ok = false;
        new_password.style.backgroundColor = 'var(--bs-danger)';
        confirm_password.style.backgroundColor = 'var(--bs-danger)';
        if (message !== '') {
            message += ', ';
        }
        message += "New passwords do not match";
        type = 'error';
    } else if (fields_ok) {
        new_password.style.backgroundColor = '';
        confirm_password.style.backgroundColor = '';
    }

    // do not allow the new and old passwords to match
    if (fields_ok && new_password.value === old_password.value) {
        fields_ok = false;
        message = "New password cannot be the same as the current password";
        type = 'error';
        new_password.style.backgroundColor = 'var(--bs-danger)';
        confirm_password.style.backgroundColor = 'var(--bs-danger)';
        old_password.style.backgroundColor = 'var(--bs-danger)';
    } else if (fields_ok) {
        new_password.style.backgroundColor = '';
        old_password.style.backgroundColor = '';
        confirm_password.style.backgroundColor = '';
    }

    // if everything ok, call the change_password php page to actually validate the old password and if correct
    // change the password.
    if (fields_ok) {
        var postData = {
            ajax_request_action: 'change_passwd',
            idval: idval.value,
            old: old_password.value,
            new: new_password.value,
        };
        $.ajax({
            url: '/scripts/changePassword.php',
            type: "POST",
            data: postData,
            success: changeSucess,
            error: function(jqXHR, exception) {
                message = JSON.stringify(jqXHR);
                show_message(message,  'error');
                change_passwd_btn.disabled = false;
            }
        });
    } else {
        // reenable the button to allow them to correct the error
        change_passwd_btn.disabled = false;
    }
    // if we found an error, diplay it.
    show_message(message,  type);
}
// changeSuccess: ajax call completion processing
// if contains error: error message
// if contain message: success message
function changeSucess(data, textStatus, jqXHR) {
    "use strict";
    var data_json = null;
    try {
        data_json = JSON.parse(data);
    } catch (error) {
        console.log(error);
        return;
    }

    //console.log(data_json);
    if (data_json.hasOwnProperty("error")) {
        show_message(data_json.error,  'error');
    } else if (data_json.hasOwnProperty("message")) {
        show_message(data_json.message,  'success');
    } else {
        show_message(JSON.stringify(data), 'warn');
    }
    change_passwd_btn.disabled = false;
}

// show_message:
// apply colors to the message div and place the text in the div, first clearing any existing class colors
// type:
//  error: (white on red) bg-danger
//  warn: (black on yellow-orange) bg-warning
//  success: (white on green) bg-success
function show_message(message, type) {
    "use strict";
    if (message_div.classList.contains('bg-danger')) {
        message_div.classList.remove('bg-danger');
    }
    if (message_div.classList.contains('bg-success')) {
        message_div.classList.remove('bg-sucess');
    }
    if (message_div.classList.contains('bg-warning')) {
        message_div.classList.remove('bg-warning');
    }
    if (message_div.classList.contains('text-white')) {
        message_div.classList.remove('text-white');
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
