// message functions to write messages to the results div
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
        message_div.classList.add('bg-danger','text-white');
    }
    if (type === 'success') {
        message_div.classList.add('bg-success','text-white');
    }
    if (type === 'warn') {
        message_div.classList.add('bg-warning');
    }
    message_div.innerHTML = message;
}

function showAjaxError(jqXHR, textStatus, errorThrown, divElement = null) {
    var message = '';
    if (jqXHR && jqXHR.responseText) {
        message = jqXHR.responseText;
    } else {
        message = 'An error occurred on the server.';
    }
    if (textStatus != '' && textStatus != 'error')
        message += '<BR/>' + textStatus;
    message += '<BR/>Error Thrown: ' + errorThrown;
    show_message(message, 'error', divElement);
}

// validate RFC-5311/2 addresses regexp pattern from https://regex101.com/r/3uvtNl/1, found by searching validate RFC-5311/2  addresses
function validateAddress(addr) {
    const regPattern = /^((?:[A-Za-z0-9!#$%&'*+\-\/=?^_`{|}~]|(?<=^|\.)"|"(?=$|\.|@)|(?<=".*)[ .](?=.*")|(?<!\.)\.){1,64})(@)((?:[A-Za-z0-9.\-])*(?:[A-Za-z0-9])\.(?:[A-Za-z0-9]){2,})$/gm;
    return regPattern.test(String(addr).toLowerCase());
}

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

// make_copy(associative array)
// javascript passes by reference, can't slice an associative array, so you need to do a horrible JSON kludge
function make_copy(arr) {
    return JSON.parse(JSON.stringify(arr));  // horrible way to make an independent copy of an associative array
}

// isPrimary(memConid, memType, memCategory,memPrice, usage)
//      return if the membership is a primary one
//      usage = 'all' generic
//              'coupon' - eligible as primary for a coupon
//              'print' - able to be printed
function isPrimary(memConid, memType, memCategory, memPrice, usage = 'all') {
    if (config.conid != memConid) // must be a current year membership to be primary, no year aheads for next year
        return false;

    if (!(memType == 'full' || memType == 'oneday' || memType == 'virtual'))
        return false;   // must be one of these main types to even be considered a primary

    if (usage == 'all')
        return true;    // the basic case, it's a primary if it's one of these types

    if (usage == 'coupon') {
        if (memPrice == 0 || memType != 'full')
            return false; // free memberships and oneday/virtual are not eligible for coupons
    }

    if (usage == 'print') {
        if (memCategory == 'virtual')
            return false; // virtual cannot be printed
    }

    // we got this far, all the 'falses; are called out, so it must be true
    return true;

}

// try to open new window/tab with fallback to using same window
function openWindowWithFallback(uri, target = '_blank') {
    var status = window.open(uri, target);
    if (status)
        status.focus();

    setTimeout(function() {
        if (status)
            status.focus();
        else
            window.location.href = uri;

    }, 1000); // Adjust timeout as needed
}