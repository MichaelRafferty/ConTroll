
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
            else if (user['label']) {
                userDiv.addClass('hasMembership');
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
            $('#test').empty().append(JSON.stringify(JqXHR));
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

// old style error message block
//
function clearError() {
    $('#test').empty();
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

function clear_message(div='result_message') {
    show_message('', '', div);
}

// show_message:
// apply colors to the message div and place the text in the div, first clearing any existing class colors
// type:
//  error: (white on red) bg-danger
//  warn: (black on yellow-orange) bg-warning
//  success: (white on green) bg-success
function show_message(message, type, div='result_message') {
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
}

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

// make_copy(associative array)
// javascript passes by reference, can't slice an associative array, so you need to do a horrible JSON kludge
function make_copy(arr) {
    return JSON.parse(JSON.stringify(arr));  // horrible way to make an independent copy of an associative array
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

// saveEdit - a common return from the base.php mce editor modal
var editTableDiv = null;
var editFieldDiv = null;
var editIndexDiv = null;
var editClassDiv = null;
var editFieldArea = null;
var editor_modal = null;
var editTitleDiv = null;
var editFieldNameDiv = null;

function editRefs() {
    editTableDiv = document.getElementById("editTable");
    editFieldDiv = document.getElementById("editField");
    editIndexDiv = document.getElementById("editIndex");
    editClassDiv = document.getElementById("editClass");
    editFieldArea = document.getElementById("editFieldArea");
    editTitleDiv = document.getElementById("editTitle");
    editFieldNameDiv = document.getElementById("editFieldName");
    id = document.getElementById('tinymce-modal');
    if (id != null) {
        editor_modal = new bootstrap.Modal(id, {focus: true, backdrop: 'static'});
    }
}
function showEdit(classname, table, index, field, titlename, textitem) {
    if (editTableDiv == null)
        editRefs();

    if (editor_modal == null)
        return; // tiymce area not loaded

    if (textitem == null)
        textitem = '';

    editTableDiv.innerHTML = table;
    editFieldDiv.innerHTML = field;
    editFieldNameDiv.innerHTML = field + ':';
    editIndexDiv.innerHTML = index;
    editClassDiv.innerHTML = classname;
    editFieldArea.innerHTML = textitem;
    editTitleDiv.innerHTML = "Editing " + table + " " + titlename + " " + field;

    editor_modal.show();
    tinyMCE.init({
        selector: 'textarea#editFieldArea',
        height: 800,
        min_height: 400,
        menubar: false,
        plugins: 'advlist lists image link charmap fullscreen help nonbreaking preview searchreplace',
        toolbar:  [
            'help undo redo searchreplace copy cut paste pastetext | fontsizeinput styles h1 h2 h3 h4 h5 h6 | ' +
            'bold italic underline strikethrough removeformat | '+
            'visualchars nonbreaking charmap hr | ' +
            'preview fullscreen ',
            'alignleft aligncenter alignright alignnone | outdent indent | numlist bullist checklist | forecolor backcolor | link image'
        ],
        content_style: 'body {font - family:Helvetica,Arial,sans-serif; font-size:14px }',
        placeholder: 'Edit the description here...',
        auto_focus: 'editFieldArea'
    });
    tinyMCE.activeEditor.setContent(textitem);
}

// save the modal edit values back
function saveEdit() {
    if (editTableDiv == null)
        editRefs();

    if (editor_modal == null)
        return; // tiymce area not loaded

    var editTable = editTableDiv.innerHTML;
    var editField = editFieldDiv.innerHTML;
    var editIndex = editIndexDiv.innerHTML;
    var editClass = editClassDiv.innerHTML;
    var editValue = tinyMCE.activeEditor.getContent();
    tinyMCE.remove();
    editor_modal.hide();

    // force a save and get the field from tinyMCE
        switch (editClass) {
            case 'vendor':
                vendor.editReturn(editTable, editField,  editIndex, editValue);
                break;
            default:
        }

}
