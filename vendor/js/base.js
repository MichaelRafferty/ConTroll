
function clear_message(div='result_message') {
    show_message('', '', div);
}

// show_message:
// apply colors to the message div and place the text in the div, first clearing any existing class colors
// type:
//  error: (white on red) bg-danger
//  warn: (black on yellow-orange) bg-warning
//  success: (white on green) bg-success
function show_message(message, type = 'success', div='result_message') {
    if (div == null)
        div = 'result_message';

    var message_div = document.getElementById(div);

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
    message_div.scrollIntoView(false);
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

// validate RFC-5311/2 addresses regexp pattern from https://regex101.com/r/3uvtNl/1, found by searching validate RFC-5311/2  addresses
function validateAddress(addr) {
    const regPattern = /^((?:[A-Za-z0-9!#$%&'*+\-\/=?^_`{|}~]|(?<=^|\.)"|"(?=$|\.|@)|(?<=".*)[ .](?=.*")|(?<!\.)\.){1,64})(@)((?:[A-Za-z0-9.\-])*(?:[A-Za-z0-9])\.(?:[A-Za-z0-9]){2,})$/gm;
    return regPattern.test(String(addr).toLowerCase());
}
