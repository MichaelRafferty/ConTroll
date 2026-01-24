// vim: ts=4 sw=4 expandtab

// map class - create/map/unmap object values
// deals with dynamic names for properties easily when you can't use dot notation
class map {
    #map_obj = null;

    constructor() {
        this.#map_obj = {};
    }

    // isSet - is the property set
    isSet(prop) {
        return this.#map_obj.hasOwnProperty(prop);
    }

    // get - return the value of a property
    get(prop) {
        if (this.isSet(prop))
            return this.#map_obj[prop];
        return undefined;
    }

    // set: set the property to a value
    set(prop, value) {
        this.#map_obj[prop] = value;
    }

    // remove property from object
    clear(prop) {
        if (this.isSet(prop)) {
            delete this.#map_obj[prop];
        }
    }

    // for ajax use - get entire map
    getMap() {
        return make_copy(this.#map_obj);
    }
}

function test(method, formData, resultDiv) {
    $.ajax({
        url: "scripts/authEcho.php",
        data: formData,
        method: method,
        success: function(data, textStatus, jqXhr) {
            if(data.error) {
                alert(data.error);
            } else {
                $(resultDiv).empty().append(JSON.stringify(data, null, 2));
            }
        }
    });
}

// tabulator custom header filter function for numeric comparisions
//
function numberHeaderFilter(headerValue, rowValue, rowData, filterParams) {
    var option = headerValue.substring(0,1);
    var value = headerValue;
    if (option == '<' || option == '>' || option == '=') {
        var suboption = headerValue.substring(1, 1);
        if (suboption == '=') {
            option += suboption;
            value = value.substring(2);
        } else {
            value = value.substring(1);
        }
    }

    switch (option) {
        case '<':
            return Number(rowValue) < Number(value);
        case '<=':
            return Number(rowValue) <= Number(value);
        case '>':
            return Number(rowValue) > Number(value);
        case '>=':
            return Number(rowValue) >= Number(value);
        default:
            return Number(rowValue) == Number(value);
    }
}

// fullNameHeaderFilter: Custom header filter for substring and first/last substring for FullName with first_name and last_name fields in the table
function fullNameHeaderFilter(headerValue, rowValue, rowData, filterParams) {
    var header = headerValue.toLowerCase();
    var value = rowValue.toLowerCase();
    if (value.includes(header))
        return true;

    var parts = header.split(' ');
    if (parts.length < 2)
        return false;

    var first = rowData.first_name.toLowerCase();
    var last = rowData.last_name.toLowerCase();
    if (parts.length == 3) {
        var middle = rowData.middle_name.toLowerCase();
        return first.includes(parts[0]) && middle.includes(parts[1]) && last.includes(parts[2]);
    }

    if (parts.length == 2) {
        return first.includes(parts[0]) && last.includes(parts[1]);
    }

    return false;
}

function showError(str) {
    $('#test').empty().append(str);
}

function showAlert(str) {
    $('#alertInner').empty().html(str);
    $('#alert').show();
}

// dayFromLabel(label)
// return the full day name from a memList/memLabel label.
function dayFromLabel(label) {
    var pattern_fa = /^mon\s.*$/i;
    var pattern_ff = /^monday.*$/i;
    var pattern_ma = /.*\s+mon\s.*$/i;
    var pattern_mf = /.*\s+monday.*$/i;
    if (pattern_fa.test(label) || pattern_ff.test(label) || pattern_ma.test(label) || pattern_mf.test(label))
        return "Monday;"
    
    pattern_fa = /^tue\s.*$/i;
    pattern_ff = /^tueday.*$/i;
    pattern_ma = /.*\s+tue\s.*$/i;
    pattern_mf = /.*\s+tueday.*$/i;
    if (pattern_fa.test(label) || pattern_ff.test(label) || pattern_ma.test(label) || pattern_mf.test(label))
        return "Tuesday;"

    
    pattern_fa = /^wed\s.*$/i;
    pattern_ff = /^wednesday.*$/i;
    pattern_ma = /.*\s+wed\s.*$/i;
    pattern_mf = /.*\s+wednesday.*$/i;
    if (pattern_fa.test(label) || pattern_ff.test(label) || pattern_ma.test(label) || pattern_mf.test(label))
        return "Wednesday;"

    pattern_fa = /^thu\s.*$/i;
    var pattern_faa = /^thur\s.*$/i;
    pattern_ff = /^thursday.*$/i;
    pattern_ma = /.*\s+thu\s.*$/i;
    var pattern_maa = /.*\s+thur\s.*$/i;
    pattern_mf = /.*\s+thursday.*$/i;
    if (pattern_fa.test(label) || pattern_faa.test(label) || pattern_ff.test(label) ||
        pattern_ma.test(label) || pattern_maa.test(label) || pattern_mf.test(label))
        return "Thursday;"

    pattern_fa = /^fri\s.*$/i;
    pattern_ff = /^friday.*$/i;
    pattern_ma = /.*\s+fri\s.*$/i;
    pattern_mf = /.*\s+friday.*$/i;
    if (pattern_fa.test(label) || pattern_ff.test(label) || pattern_ma.test(label) || pattern_mf.test(label))
        return "Friday;"

    pattern_fa = /^sat\s.*$/i;
    pattern_ff = /^saturday.*$/i;
    pattern_ma = /.*\s+sat\s.*$/i;
    pattern_mf = /.*\s+saturday.*$/i;
    if (pattern_fa.test(label) || pattern_ff.test(label) || pattern_ma.test(label) || pattern_mf.test(label))
        return "Saturday;"

    pattern_fa = /^sun\s.*$/i;
    pattern_ff = /^sunday.*$/i;
    pattern_ma = /.*\s+sun\s.*$/i;
    pattern_mf = /.*\s+sunday.*$/i;
    if (pattern_fa.test(label) || pattern_ff.test(label) || pattern_ma.test(label) || pattern_mf.test(label))
        return "Sunday;"

    return "";
}

// base_changePrinters:
// open the modal popup and allow selecting new printers
var base_changePrintersModal = null;
var base_changePrintersBody = null;
function base_changePrintersShow() {
    if (base_changePrintersModal === null) {
        base_changePrintersModal = new bootstrap.Modal(document.getElementById('Base_changePrinters'), {focus: true, backldrop: 'static'});
        base_changePrintersBody = document.getElementById('Base_changePrintersBody');
    }

    // load the printer select list
    var postData = {
        ajax_request_action: 'printerSelectList',
    };
    $.ajax({
        method: "POST",
        url: "scripts/base_showPrinterSelect.php",
        data: postData,
        success: function (data, textstatus, jqxhr) {
            if (data.error !== undefined) {
                show_message(data.error, 'error');
                return;
            }
            base_changePrintersBody.innerHTML = data.selectList;
            var value = document.getElementById('currentBadgePrinter').innerHTML;
            if (value !== undefined && value.length > 10)
                document.getElementById('badge_printer').value = value;

            value = document.getElementById('currentReceiptPrinter').innerHTML;
            if (value !== undefined && value.length > 10)
                document.getElementById('receipt_printer').value = value;

            value = document.getElementById('currentGeneralPrinter').innerHTML;
            if (value !== undefined && value.length > 10)
                document.getElementById('generic_printer').value = value;

            value = document.getElementById('currentCCTerminal').innerHTML;
            if (value !== undefined && value.length > 10)
                document.getElementById('square_terminal').value = value;

            base_changePrintersModal.show();
            clear_message();
        },
        error: showAjaxError,
    });
}

// base_changePrintersSubmit - update the printers in the session file and on the screen
function base_changePrintersSubmit() {
    // get the three selected values, use the DOM directly as Modal changes the values and they can't be cached
    var badge_prntr = document.getElementById("badge_printer").value;
    var receipt_prntr = document.getElementById("receipt_printer").value;
    var generic_prntr = document.getElementById("generic_printer").value;
    var square_term = document.getElementById("square_terminal").value;
    document.getElementById('currentBadgePrinter').innerHTML = badge_prntr;
    document.getElementById('currentReceiptPrinter').innerHTML = receipt_prntr;
    document.getElementById('currentGeneralPrinter').innerHTML = generic_prntr;
    document.getElementById('currentCCTerminal').innerHTML = square_term;
    base_changePrintersModal.hide();

    // load the printer select list
    var postData = {
        ajax_request_action: 'printerSessionUpdate',
        badge: badge_prntr,
        receipt: receipt_prntr,
        generic: generic_prntr,
        terminal: square_term,
    };
    $.ajax({
        method: "POST",
        url: "scripts/base_printerSessionUpdate.php",
        data: postData,
        success: function (data, textstatus, jqxhr) {
            if (data.error !== undefined) {
                show_message(data.error, 'error');
                return;
            }
            if (typeof pos !== 'undefined' && pos !== null) {
                pos.setPrinterData(data);
            }
            base_changePrinterDisplay(data);
            clear_message();
        },
        error: showAjaxError,
    });
}

// base_changePrinterDisplay
//  data: receipt, generic, badge print strings
function base_changePrinterDisplay(data) {
    var html = 'Badge: ' + data.badge + '&nbsp; <button type="button" class="btn btn-sm btn-secondary pt-0 pb-0" onclick="base_changePrintersShow();">Chg</button><br/>' +
    'Receipt: ' + data.receipt + '<br/>' +
    'General: ' + data.generic + '<br/>' +
    'Terminal: ' + data.terminal;

    document.getElementById("page_head_printers").innerHTML = html;
    if (typeof current_tab !== 'undefined') {
        badgePrinterAvailable = data.badge !== 'None';
        receiptPrinterAvailable = data.receipt !== 'None';
        if (current_tab == print_tab) {
            print_shown();
        }
    }
}

// base_toggleManager:
//  toggle the manager enabled setting
var baseManagerEnabled = false;
var page_banner = null;
var base_navitem = null;
var base_toggle = null;
var base_nav_div = null;
var base_user_div = null;
var base_managerOverrideModal = null;
var base_managerPassword = null;
var base_password_modal_error_div = null;
var inConTroll = false;
function base_toggleManager() {
    if (base_navitem === null) {
        page_banner = document.getElementById("page_banner");
        base_navitem = document.getElementById("base_navbar");
        base_toggle = document.getElementById("base_toggleMgr");
        base_nav_div = document.getElementById("base_nav_div");
        base_user_div = document.getElementById("base_user_div");
        base_password_modal_error_div = document.getElementById("base_password_modal_error");

        base_managerOverrideModal = new bootstrap.Modal(document.getElementById('base_managerOverride'), {focus: true, backldrop: 'static'});
    }

    if (baseManagerEnabled === false) {
        // use modal popup to ask for password
        base_managerOverrideModal.show();
        base_managerPassword = document.getElementById("base_managerPassword");
        base_managerPassword.style.backgroundColor = '';
        return;
    }
    baseManagerEnabled = false;
    // restore normal primary navbar)
    page_banner.classList.remove("bg-warning");
    base_user_div.classList.remove("bg-warning");
    base_nav_div.classList.remove("bg-warning");
    base_navitem.classList.remove("bg-warning");
    page_banner.classList.add("bg-primary");
    page_banner.classList.add("text-white");
    base_user_div.classList.add("bg-primary");
    base_user_div.classList.add("text-bg-primary");
    base_nav_div.classList.add("bg-primary");
    base_navitem.classList.add("bg-primary");
    base_navitem.classList.add("navbar-dark");
    base_toggle.innerHTML = "Enable Mgr";
    base_toggle.classList.remove("btn-primary");
    base_toggle.classList.add("btn-warning");

    if (typeof cart) {
        // is there a cart element
        if (cart.getCartLength() > 0)
            cart.drawCart();
    }
    if (typeof current_tab !== 'undefined') {
        if (current_tab == pay_tab) {
            pay_shown();
        }
    }
}

function base_managerOverrideSubmit() {
    // validate the password
    var passwd = base_managerPassword.value;
    if (passwd.length <= 0) {
        base_managerPassword.style.backgroundColor = 'var(--bs-warning)';
        return;
    }

    // clear the password field for the next popup
    base_managerPassword.value = '';
    var postData = {
        ajax_request_action: 'managerPasswordVerify',
        passwd: passwd,
    };
    $.ajax({
        method: "POST",
        url: "scripts/base_managerPasswordVerify.php",
        data: postData,
        success: function (data, textstatus, jqxhr) {
            if (data.error !== undefined) {
                base_password_modal_error_div.innerHTML = "Error: " + data.error;
                return;
            }
            base_managerOverrideComplete(data);
        },
        error: showAjaxError,
    });
}

function base_managerOverrideComplete(data) {
    if (data.manager === true) {
        baseManagerEnabled = true;
        // make navbar background warning (yellow)
        page_banner.classList.remove("bg-primary")
        page_banner.classList.remove("text-white")
        base_nav_div.classList.remove("bg-primary");
        base_user_div.classList.remove("bg-primary");
        base_user_div.classList.remove("text-bg-primary");
        base_navitem.classList.remove("bg-primary");
        base_navitem.classList.remove("navbar-dark");
        page_banner.classList.add("bg-warning");
        base_nav_div.classList.add("bg-warning");
        base_user_div.classList.add("bg-warning");
        base_navitem.classList.add("bg-warning");
        base_toggle.innerHTML = "Disable Mgr";
        base_toggle.classList.remove("btn-warning");
        base_toggle.classList.add("btn-primary");
        base_managerOverrideModal.hide();

        if (typeof cart) {
            // is there a cart element
            if (cart.getCartLength() > 0)
                cart.drawCart();
        }
        if (typeof current_tab !== 'undefined') {
            if (current_tab == pay_tab) {
                pay_shown();
            }
        }
        return;
    }
    base_managerPassword.style.backgroundColor = '';
}
