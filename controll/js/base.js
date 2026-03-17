globalCustomTextEditorInit = false;

// global constants for controll back end
const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

// new functions for token items
function checkRefresh(data) {
    if (data.hasOwnProperty('tokenStatus') && data.tokenStatus == 'refresh'){
        console.log("refresh token called with " + data.tokenStatus);
        window.open('/index.php?refresh', '_blank');
    }
}

function test(method, formData, resultDiv) {
    $.ajax({
        url: "scripts/authEcho.php",
        data: formData,
        method: method,
        success: function (data, textStatus, jqXhr) {
            if (data.error) {
                alert(data.error);
            } else {
                $(resultDiv).empty().append(JSON.stringify(data, null, 2));
            }
        }
    });
}

function setCellChanged(cell) {
    setFieldChanged(cell.getElement());
}

function setFieldChanged(field) {
    if (!field.classList.contains('unsavedChangeBGColor'))
        field.classList.add('unsavedChangeBGColor');
}

function clearCellFieldChanged(cell) {
    clearFieldChanged(cell.getElement());
}

function clearFieldChanged(field) {
    field.classList.remove('unsavedChangeBGColor');
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

// saveEdit - a common return from the base.php mce editor modal
var editTableDiv = null;
var editFieldDiv = null;
var editIndexDiv = null;
var editClassDiv = null;
var editFieldArea = null;
var editor_modal = null;
var editTitleDiv = null;
var editFieldNameDiv = null;
var editTextOnly = false;

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
function showEdit(classname, table, index, field, titlename, textitem, textOnly = false) {
    if (editTableDiv == null)
        editRefs();

    if (editor_modal == null)
        return; // tiymce area not loaded

    if (textitem == null)
        textitem = '';

    if (textOnly) {
        textitem = textitem.replaceAll('\n', '<br/>');
    }
    editTableDiv.innerHTML = table;
    editFieldDiv.innerHTML = field;
    editFieldNameDiv.innerHTML = field + ':';
    editIndexDiv.innerHTML = index;
    editClassDiv.innerHTML = classname;
    editFieldArea.value = textitem;
    editTitleDiv.innerHTML = "Editing " + table + " " + titlename + "<br/>" + field;
    editTextOnly = textOnly;

    editor_modal.show();
    if (globalCustomTextEditorInit) {
        // update the text block
        tinyMCE.get("editFieldArea").focus();
        tinyMCE.get("editFieldArea").load();
    } else {
        tinyMCE.init({
            selector: 'textarea#editFieldArea',
            height: 500,
            min_height: 400,
            menubar: false,
            license_key: 'gpl',
            plugins: 'advlist lists image link charmap fullscreen help nonbreaking preview searchreplace',
            toolbar: [
                'help undo redo searchreplace copy cut paste pastetext | fontsizeinput styles h1 h2 h3 h4 h5 h6 | ' +
                'bold italic underline strikethrough language removeformat | ' +
                'visualchars nonbreaking charmap hr | ' +
                'preview fullscreen ',
                'alignleft aligncenter alignright alignnone | outdent indent | numlist bullist checklist | forecolor backcolor | link image'
            ],
            content_langs: [
                { title: 'English', code: 'en' },
                { title: 'French Canadian', code: 'fr-CA' },
                { title: 'French', code: 'fr' },
                { title: 'Spanish', code: 'es' },
                { title: 'German', code: 'de' },
                { title: 'Portuguese', code: 'pt' },
                { title: 'Chinese', code: 'zh' }
            ],
            link_default_target: '_blank',
            content_style: 'body {font - family:Helvetica,Arial,sans-serif; font-size:14px }',
            placeholder: 'Edit the description here...',
            auto_focus: 'editFieldArea',
            init_instance_callback: function (editor) {
                editor.setContent(textitem);
            }
        });
        // Prevent Bootstrap dialog from blocking focusin
        document.addEventListener('focusin', (e) => {
            if (e.target.closest(".tox-tinymce, .tox-tinymce-aux, .moxman-window, .tam-assetmanager-root") !== null) {
                e.stopImmediatePropagation();
            }
        });
        globalCustomTextEditorInit = true;
    }
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

    if (editTextOnly) {
        editValue = editValue.replace(/<\/p>/g, "\n");
        editValue = editValue.replace(/<p>/g, "");
        editValue = editValue.replace(/<br[ ]*>/g, "\n");
        editValue = editValue.replace(/<br\/>/g, "\n");
        editValue = editValue.replace(/&rsquo;/g, "'");
        editValue = editValue.replace(/&lsquo;/g, "'");
        editValue = editValue.replace(/&nbsp;/g, " ");
        editValue = editValue.replace(/<[^>]+>/g, ''); // strip any left over
    }

    editor_modal.hide();

    // force a save and get the field from tinyMCE
    switch (editClass) {
        case 'exhibits':
            exhibits.editReturn(editTable, editField,  editIndex, editValue);
            break;

        case 'customText':
            customText.editReturn(editTable, editField, editIndex, editValue);
            break;

        default:
            show_message("Bad class passed to showEdit: " + editClass, 'error');
    }

}

// blankIfNull - return empty string if argument is nullk
function blankIfNull(value) {
    if (value == null)
        return '';
    return value;
}

// pass object to a window.open via a post with json data
function downloadFilePost(format, fileName, tableData, excludeList = null, fieldList = null) {
    // create the form
    var form = document.createElement('form');
    form.method = 'POST';
    form.action = 'scripts/downloadFile.php';
    // append it to the body
    document.body.appendChild(form);
    // create the file name to suggest to save it to....
    var field = document.createElement('input');
    field.type = 'text';
    field.name = 'format';
    field.value = format;
    form.appendChild(field);
    var field = document.createElement('input');
    field.type = 'text';
    field.name = 'filename';
    field.value = fileName;
    form.appendChild(field);
    if (excludeList != null) {
        field = document.createElement('input');
        field.type = 'text';
        field.name = 'excludeList';
        field.value = JSON.stringify(excludeList);
        form.appendChild(field);
    };
    if (fieldList != null) {
        field = document.createElement('input');
        field.type = 'text';
        field.name = 'fieldList';''
        field.value = JSON.stringify(fieldList);
        form.appendChild(field);
    };
    // create the data table element
    var tablejson = document.createElement('input');
    tablejson.type = 'text';
    tablejson.name = 'table'
    tablejson.value = tableData;
    form.appendChild(tablejson);
    // now open the window
    form.submit();
    document.body.removeChild(form);
}
