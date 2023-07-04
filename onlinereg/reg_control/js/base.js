
function test(method, formData, resultDiv) {
    $.ajax({
        url: "scripts/authEcho.php",
        data: formData,
        method: method,
        success: function (data, textStatus, jqXhr) {
            if (data['error']) {
                alert(data['error']);
            } else {
                $(resultDiv).empty().append(JSON.stringify(data, null, 2));
            }
        }
    });
}


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
    show.attr('id', id + "ShowLink");
    hide.attr('id', id + "HideLink");
    show.attr('href', "javascript:void(0)");
    hide.attr('href', "javascript:void(0)");
    show.click(function () { showBlock("#" + id); });
    hide.click(function () { hideBlock("#" + id); });
    show.append("(show)");
    hide.append("(hide)");
    block.append(" ").append(show).append(" ").append(hide);
    container = $(document.createElement("form"));
    container.attr('id', id + "Form");
    container.attr('name', id);
    block.append(container);
    show.click()
    return container;
}


function displaySearchResults(data, callback) {
    var resDiv = $("#searchResultHolder");
    resDiv.empty();
    if (data["error"]) {
        showError(data["error"]);
        return false;
    }
    if (data["count"]) {
        $("#resultCount").empty().html("(" + data["count"] + ")");
    } else {
        $("#resultCount").empty().html("(0)");
    }

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
            if (user['label']) { userDiv.append(user['label'] + "<br/>" + "<hr/>"); }
            if (user['full_name']) { userDiv.append(user['full_name'] + "<br/>"); }
            else { userDiv.append("***NO NAME***<br/>"); }
            if (user['badge_name']) { userDiv.append(user['badge_name'] + "<br/>"); }
            userDiv.append($(document.createElement("hr")));
            if (user['address']) { userDiv.append(user['address'] + "<br/>"); }
            else { userDiv.append("***NO STREET ADDR***<br/>"); }
            if (user['addr_2']) { userDiv.append(user['addr_2'] + "<br/>"); }
            if (user['locale']) { userDiv.append(user['locale'] + "<br/>"); }
            else { userDiv.append("***NO CITY/STATE/ZIP***<br/>"); }
            userDiv.append($(document.createElement("hr")));
            if (user['email_addr']) { userDiv.append(user['email_addr'] + "<br/>"); }
            if (user['phone']) { userDiv.append(user['phone'] + "<br/>"); }
            if (user['banned'] == 'Y') {
                flags.append('banned<br/>');
                userDiv.addClass('banned');
            }
            else if (user['active'] == 'N') {
                flags.append('inactive<br/>');
                userDiv.addClass('inactive');
            }
            resContainer.append(userDiv);
            userDiv.click(function () { callback($(this).data('obj')); });
        }
    }
}

function submitForm(formObj, formUrl, succFunc, errFunc) {
    var postData = $(formObj).serialize();
    if (succFunc == null) {
        succFunc = function (data, textStatus, jsXhr) {
            $('#test').empty().append(JSON.stringify(data, null, 2));
        }
    };

    $.ajax({
        url: formUrl,
        type: "POST",
        data: postData,
        success: succFunc,
        error: function (JqXHR, textStatus, errorThrown) {
            $('#test').empty().append(JSON.stringify(data, null, 2));
        }
    });
}

var tracker = new Array();
function track(formName) {
    tracker[formName] = new Object;
    $(formName + " :input").each(function () {
        tracker[formName][$(this).attr('name')] = false;
        $(this).on("change", function () {
            tracker[formName][$(this).attr('name')] = true;
        });
    });
}


function submitUpdateForm(formObj, formUrl, succFunc, errFunc) {
    var postData = "id=" + $(formObj + " :input[name=id]").val();
    for (var key in tracker[formObj]) {
        if (tracker[formObj][key]) {
            if ($(formObj + " :input[name=" + key + "]").attr('type') == 'radio') {
                postData += "&" + key + "=" + $(formObj + " :input[name=" + key + "]:checked").val();
            } else if ($(formObj + " :input[name=" + key + "]").attr('type') == 'checkbox') {
                postData += "&" + key + "=" + $(formObj + " :input[name=" + key + "])").attr('checked');
            } else {
                postData += "&" + key + "=" + $(formObj + " :input[name=" + key + "]").val();
            }
        }
    }
    if (succFunc == null) {
        succFunc = function (data, textStatus, jqXHR) {
            $('#test').empty().append(JSON.stringify(data));
        }
    };
    $.ajax({
        url: formUrl,
        type: "POST",
        data: postData,
        success: succFunc,
        error: function (JqXHR, textStatus, errorThrown) {
            $('#test').empty().append(JSON.stringify(JqXHR));
        }
    });
}

function testValid(formObj) {
    var errors = 0;

    $(formObj + " :required").map(function () {
        if (!$(this).val()) {
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
    if (succFunc == null) {
        succFunc = function (data, textStatus, jqXHR) {
            $('#test').empty().append(JSON.strignify(data, null, 2));
        }
    };
    $.ajax({
        url: formUrl,
        type: "GET",
        data: getData,
        success: succFunc,
        error: function (JqXHR, textStatus, errorThrown) {
            $('#test').empty().append(JSON.stringify(JqXHR, null, 2));
            if (errFunc != null) { errFunc(); }
        }
    });
}

function showError(str, data = null) {
    $('#test').empty();
    if (str != null) {
        if (Array.isArray(str))
            str = JSON.stringify(str, null, 2);
        if (typeof str == 'string') {
            strtype = 'string';
            try {
                JSON.parse(str);
                strtype = 'json';
            } catch (error) {
                strtype = 'string'
            }
            if (strtype == 'json')
                str = JSON.stringify(str, null, 2);

            if (str.trim() != '')
                $('#test').append('<STRONG>' + str + '</STRONG>');
        }
    }
    if (data != null) {
        $('#test').append('<BR/>' + JSON.stringify(data, null, 2));
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
}j

function showAlert(str) {
    $('#alertInner').empty().html(str);
    $('#alert').show();
}

function notnullorempty(str) {
    if (str === null)
        return false;
    if (str.trim() == "")
        return false;

    return true;        
}
