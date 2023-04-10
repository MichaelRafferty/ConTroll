// vim: ts=4 sw=4 expandtab

function test(method, formData, resultDiv) {
    $.ajax({
        url: "scripts/authEcho.php",
        data: formData,
        method: method,
        success: function(data, textStatus, jqXhr) {
            if(data['error']) {
                alert(data['error']);
            } else {
                $(resultDiv).empty().append(JSON.stringify(data, null, 2));
            }
        }
    });
}

// convert url parameters to associative array
function URLparamsToArray(urlargs) {
    const params = new URLSearchParams(urlargs);
    const result = {};
    for (const [key, value] of params) {
        result[key] = value;
    }
    return result;
}

function showError(str) {
    $('#test').empty().append(str);
}

function showAlert(str) {
    $('#alertInner').empty().html(str);
    $('#alert').show();
}

message_div = null;
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

function showAjaxError(data, textStatus, jqXHR) {
    'use strict';
    if (data && data.responseText) {
        show_message(data.responseText, 'error');
    } else {
        show_message('An error occurred on the server.', 'error');
    }
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
            if (data['error'] !== undefined) {
                show_message(data['error'], 'error');
                return;
            }
            base_changePrintersBody.innerHTML = data['selectList'];
            base_changePrintersModal.show();
        },
        error: showAjaxError,
    });
}

var page_head_printers_div = null;
var badge_printer_select = null;
var receipt_printer_select = null;
var generic_printer_select = null;

// base_changePrintersSubmit - update the printers in the session file and on the screen
function base_changePrintersSubmit() {
    if (page_head_printers_div === null) {
        page_head_printers_div = document.getElementById("page_head_printers");
        badge_printer_select = document.getElementById("badge_printer");
        receipt_printer_select = document.getElementById("receipt_printer");
        generic_printer_select = document.getElementById("generic_printer");
    }

    // get the three selected values
    var badge_prntr = badge_printer_select.value;
    var receipt_prntr = receipt_printer_select.value;
    var generic_prntr = generic_printer_select.value;

    // load the printer select list
    var postData = {
        ajax_request_action: 'printerSessionUpdate',
        badge: badge_prntr,
        receipt: receipt_prntr,
        generic: generic_prntr,
    };
    $.ajax({
        method: "POST",
        url: "scripts/base_printerSessionUpdate.php",
        data: postData,
        success: function (data, textstatus, jqxhr) {
            if (data['error'] !== undefined) {
                show_message(data['error'], 'error');
                return;
            }
            base_changePrinterDisplay(data);
        },
        error: showAjaxError,
    });
}

// base_changePrinterDisplay
//  data: receipt, generic, badge print strings
function base_changePrinterDisplay(data) {
    var html = 'Badge: ' + data['badge'] + '&nbsp; <button type="button" class="btn btn-sm btn-secondary pt-0 pb-0" onclick="base_changePrintersShow();">Chg</button><br/>' +
    'Receipt: ' + data['receipt'] + '<br/>' +
    'General: ' + data['generic'];

    page_head_printers_div.innerHTML = html;
    base_changePrintersModal.hide();
}

// obsolete code, soon to be dropped from the file
/*
function hideBlock(block) {
    $(block + "Form").hide();
    $(block + "ShowLink").show();
    $(block + "HideLink").hide();
}

function showBlock(block) {
    $(block + "Form").show();
    $(block + "ShowLink").hide();
    $(block + "HideLink").show();
}

function addShowHide(block, id) {
    var show = $(document.createElement("a"));
    var hide = $(document.createElement("a"));
    show.addClass('showlink');
    hide.addClass('hidelink');
    show.attr('id',id+"ShowLink");
    hide.attr('id',id+"HideLink");
    show.attr('href',"javascript:void(0)");
    hide.attr('href',"javascript:void(0)");
    show.click(function() {showBlock("#" + id);});
    hide.click(function() {hideBlock("#" + id);});
    show.append("(show)");
    hide.append("(hide)");
    block.append(" ").append(show).append(" ").append(hide);
    container = $(document.createElement("form"));
    container.attr('id',id+"Form");
    container.attr('name', id);
    block.append(container);
    show.click()
    return container;
}

function displaySearchResults(data, callback) {
    var resDiv = $("#searchResultHolder");
    resDiv.empty();
    if(data["error"]) { showError(data["error"]); return false;}
    if(data["count"]) {
        $("#resultCount").empty().html("(" + data["count"] + ")");
    } else { $("#resultCount").empty().html("(0)"); }

    for (var resultSet in data["results"]) {
      if (data["results"][resultSet].length == 0) { continue; }
      var setTitle = $(document.createElement("span"));
      setTitle.addClass('blocktitle');
      setTitle.append(resultSet);
      resDiv.append(setTitle)
      var resContainer = addShowHide(resDiv, resultSet);
      for (result in data["results"][resultSet]) {
        var user = data["results"][resultSet][result];
        var userDiv = $(document.createElement("div"));

        userDiv.attr('userid', user['id']);
        userDiv.data('obj', data["results"][resultSet][result]);
        userDiv.addClass('button').addClass('searchResult').addClass('half');
        flags = $(document.createElement("div"));
        flags.addClass('right').addClass('half').addClass('notice');
        userDiv.append(flags);
        if(user['label']) { userDiv.append(user['label']+"<br/>"+"<hr/>"); }
        if(user['full_name']) { userDiv.append(user['full_name']+"<br/>"); }
            else { userDiv.append("***NO NAME***<br/>");}
        if(user['badge_name']) { userDiv.append(user['badge_name']+"<br/>"); }
        userDiv.append($(document.createElement("hr")));
        if(user['address']) { userDiv.append(user['address']+"<br/>"); }
            else { userDiv.append("***NO STREET ADDR***<br/>"); }
        if(user['addr_2']) { userDiv.append(user['addr_2']+"<br/>"); }
        if(user['locale']) { userDiv.append(user['locale']+"<br/>"); }
            else { userDiv.append("***NO CITY/STATE/ZIP***<br/>"); }
        userDiv.append($(document.createElement("hr")));
        if(user['email_addr']) { userDiv.append(user['email_addr']+"<br/>"); }
        if(user['phone']) { userDiv.append(user['phone']+"<br/>"); }
        if(user['banned'] == 'Y') {
            flags.append('banned<br/>');
            userDiv.addClass('banned');
        }
        else if (user['active'] == 'N') {
            flags.append('inactive<br/>');
            userDiv.addClass('inactive');
        }
        resContainer.append(userDiv);
        userDiv.click(function () {callback($(this).data('obj'));});
      }
    }

}

function submitForm(formObj, formUrl, succFunc, errFunc) {
    var postData = $(formObj).serialize();
    if(succFunc == null) {
        succFunc = function(data, textStatus, jsXhr) {
            $('#test').empty().append(JSON.stringify(data, null, 2));
        }
    };

    $.ajax({
      url: formUrl,
      type: "POST",
      data: postData,
      success: succFunc,
      error: function(JqXHR, textStatus, errorThrown) {
        $('#test').empty().append(JSON.stringify(data, null, 2));
      }
   });
}

var tracker = new Array();
function track(formName) {
    tracker[formName] = new Object;
    $(formName + " :input").each(function() {
        tracker[formName][$(this).attr('name')] = false;
        $(this).on("change", function () {
            tracker[formName][$(this).attr('name')] = true;
        });
    });
}

function submitUpdateForm(formObj, formUrl, succFunc, errFunc) {
    var postData = "id="+$(formObj + " :input[name=id]").val();
    for(var key in tracker[formObj]) {
      if(tracker[formObj][key]) {
        if ($(formObj + " :input[name="+key+"]").attr('type')=='radio') {
          postData += "&" + key + "=" + $(formObj +" :input[name=" + key + "]:checked").val();
        } else if ($(formObj + " :input[name="+key+"]").attr('type')=='checkbox') {
          postData += "&" + key + "=" + $(formObj +" :input[name=" + key + "])").attr('checked');
        } else {
          postData += "&" + key + "=" + $(formObj +" :input[name=" + key + "]").val();
        }
      }
    }
    if(succFunc == null) {
      succFunc = function(data, textStatus, jqXHR) {
        $('#test').empty().append(JSON.stringify(data));
        }
    };
    $.ajax({
      url: formUrl,
      type: "POST",
      data: postData,
      success: succFunc,
      error: function(JqXHR, textStatus, errorThrown) {
        $('#test').empty().append(JSON.stringify(JqXHR));
      }
   });
}

function testValid(formObj) {
  var errors = 0;

  $(formObj + " :required").map(function() {
    if(!$(this).val()) {
      $(this).addClass('need');
      errors++;
    } else {
      $(this).removeClass('need');
    }
  });

  return (errors == 0);
}

function getForm(formObj, formUrl, succFunc, errFunc) {
    var getData = $(formObj).serialize();
    if(succFunc == null) {
      succFunc = function(data, textStatus, jqXHR) {
        $('#test').empty().append(JSON.stringify(data, null, 2));
        }
    };
    $.ajax({
      url: formUrl,
      type: "GET",
      data: getData,
      success: succFunc,
      error: function(JqXHR, textStatus, errorThrown) {
        $('#test').empty().append(JSON.stringify(JqXHR, null, 2));
        if(errFunc != null) { errFunc(); }
      }
    });
}
*/
