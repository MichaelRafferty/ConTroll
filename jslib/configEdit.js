// Configuration Editor Class - all functions and data related to displaying, editing and saving the reg_conf configuration file.
// Used by any section that edits a portion of reg_conf.ini

class ConfigEditor {
// privates
    #locale = 'en-US';
    #currencyFmt = null;

// DOM objects
    #saveBtn = null;
    #discardBtn = null;
    #configDiv = null;
    #fieldList = [];
    #fieldsChanged = [];

//  Saved items
    #initialConfig = null;
    #sections = null;
    #myPerm = '';
    #myAuths = [];
    #currentConfig = null;
    #control = [];

// initialization
    constructor(data) {
        "use strict";

        this.#locale = config.locale;
        this.#currencyFmt = new Intl.NumberFormat(this.#locale, {
            style: 'currency',
            currency: config.currency,
        });
// lookup all DOM elements
        this.#saveBtn = document.getElementById('saveBTN');
        this.#discardBtn = document.getElementById('discardBTN');
        this.#configDiv = document.getElementById('configDiv');

        this.#control = data.control;
        this.#sections = data.sections;
        this.#myPerm = data.perm;
        this.#myAuths = data.auths;
        this.#initialConfig = make_copy(data.currentConfig);
        this.#currentConfig = make_copy(data.currentConfig);

        this.drawConfig();
    }

// drawConfig - loop over the sections and parameters and draw the configuration edit screen
    drawConfig() {
        let html = '';
        for (let section in this.#sections) {
            let sectionName = this.#sections[section];
            html += "<div class='row mt-4'><div class='col-sm-1'></div><div class='col-sm-auto'><h3>Section: <b>[" + sectionName + "]</b></h3></div></div>\n";
            let config = this.#control[sectionName];
            for (let paramName in config) {
                let param = config[paramName];
                html += this.drawParam(sectionName, param);
            }
        }
        this.#configDiv.innerHTML = html;
    }

// drawParam - Format a specific parameter in the configuration edit screen based on its datatype and options
    drawParam(sectionName, param) {
        // R: role
        let editable = param.role.editable == 1;
        //editable=false;
        let visible = param.role.vis == 'V' || editable;
        //visible=false;
        let visibleStart = visible ? '' : '<span style="color: lightgrey;">';
        let visibleEnd = visible ? '' : '</span>';

        if (!visible)
            return;

        // N: name
        let html = "<div class='row mt-2'><div class='col-sm-auto'><h4><b>" + visibleStart + param.name + visibleEnd + "</h4></b></div></div>\n";

        // H: hint
        html += "<div class='row mt-1'><div class='col-sm-12'>"  + visibleStart + param.hint + visibleEnd + '</div></div>\n';

        // the field itself
        html += "<div class='row mt-1'><div class='col-sm-2'>" + visibleStart + param.name + visibleEnd + "</div>";
        // the field, using P, and D
        if (editable) {
            html += '<div class="col-sm-auto">' + this.formatInput(sectionName, param, this.#currentConfig[sectionName][param.name]) + '</div>';
        } else {
            html += '<div class="col-sm-auto">' + visibleStart + this.#currentConfig[sectionName][param.name] + visibleEnd + '</span></div>';
        }

        //  P, H, D, B

        html += "</div>\n";

        return html;
    }

    // formatInput - for a single parameter, output the form field
    formatInput(sectionName, param, value) {
        let size = '';
        let max= 80;
        let decimals = '';
        let html = '';
        let name = sectionName + '__' + param.name;
        let id = ' id="' + name + '" name="' + name + '"';
        let modifier = param.datatype.modifier;
        if (modifier == undefined || modifier == null)
            modifier = '';

        if (value === undefined)
            value = '';
        switch (param.datatype.type) {
            case 'i': // integer number
            case 'd': // decimal number
                html = '<input type=number placeholder="' + param.placeholder + '" ' + id + ' value="' + value + '"';
                if (modifier != '') {
                    let digits = modifier.split(',', 1);
                    html += ' max="' + '9'.repeat(Number(digits)) + '"';
                }
                html += ' onchange="configEditor.changed(' + "'" + name + "'" + ');">';
                break;

            case 's': // string
            case 'r': // relative path name
            case 'a': // absolute path name
            case 'h': // URI (http/https/mailto
                max = modifier != '' ?  Number(modifier) : 256;
                size = max > 75 ? 80 : (max + 5);
                html = '<input type=text placeholder="' + param.placeholder + '" ' + id + ' value="' + value + '"' +
                    ' size="' + size + '" maxlength="' + max + '" onchange="configEditor.changed(' + "'" + name + "'" + ');"> (Max Length: ' + max + ')';
                break;
            case 't': // textarea
                size = modifier.split(',');
                if (size[0] < 50) size[0] = 50;
                if (size[1] < 3) size[0] = 3;
                html = '<textarea placeholder="' + param.placeholder + '" ' + id + ' cols="' + size[0] + '" rows="' + size[1] +
                    '" onchange="configEditor.changed(' + "'" + name + "'" + ');">' + value + '</textarea>';
                break;

            case 'e': // email address
                max=256;
                size=80;
                html = '<input type=email placeholder="' + param.placeholder + '" ' + id + ' value="' + value + '"' +
                    ' size="' + size + '" maxlength="' + max + '" onchange="configEditor.changed(' + "'" + name + "'" + ');"> (Max Length: ' + max + ')';
                break;

            case 'l': // list (option)
                html = '<select ' + id + ' onchange="configEditor.changed(' + "'" + name + "'" + ');">\n';
                let options = modifier.substring(1).split(',');
                for (let option of options) {
                    html += '<option value="' + option + '"' + (value == option ? ' selected' : '') + '>' + option + '</option>';
                }
                if (value == '') {
                    html += '<option value="" selected>--</option>\n';
                }
                html += '</select>';
                break;

            default: // who knows
                html = '<input type=text placeholder="' + param.placeholder + '" ' + id + ' value="' + value +
                    '" size="80" onchange="configEditor.changed(' + "'" + name + "'" + ');"/>';
        }
        return html;
    }

    changed(name) {
        let pos = name.split('__', 2);
        let section = pos[0];
        let paramName = pos[1];
        let field = null;
        if (this.#fieldList.hasOwnProperty(name)) {
            field = this.#fieldList[name];
        } else {
            field = document.getElementById(name);
            this.#fieldList[name] = field;
        }

        let changed = this.#initialConfig[section][paramName] != field.value;
        this.#fieldsChanged[name] = changed;
        field.style.backgroundColor = changed ?  "#fff3cd" : '';

        this.needSave();
    }

    needSave() {
        let names = Object.keys(this.#fieldsChanged);
        let changes = 0;
        for (let name of names) {
            if (this.#fieldsChanged[name])
                changes++;
        }

        this.#saveBtn.disabled = changes == 0;
        this.#saveBtn.innerHTML = changes == 0 ? 'Save' : 'Save*';
        this.#discardBtn.disabled = changes == 0;

        return changes;
    }

    validateConfig() {
        let errormsg = '';
        for (let section in this.#sections) {
            let sectionName = this.#sections[section];
            let config = this.#control[sectionName];
            for (let paramName in config) {
                let param = config[paramName];
                errormsg += this.validateParam(sectionName, param);
            }
        }
        if (errormsg != '') {
            show_message(errormsg, 'error')
            return false;
        }
        this.#saveBtn.disabled = false;
        this.#saveBtn.innerHTML = 'Save*';
        return true;
    }

// validateParam - validate a specific parameter according to its configuration
    validateParam(sectionName, param) {
        let name = sectionName + '__' + param.name;
        let field = undefined;

        clear_message();
        clearError();

        let errmsg = '';
        if (this.#fieldList.hasOwnProperty(name)) {
            field = this.#fieldList[name];
        } else {
            field = document.getElementById(name);
            this.#fieldList[name] = field;
        }

        let value = field.value;
        if (typeof value === "string")
            value = value.trim();
        if (value == undefined || value == null) {
            value = '';
        }
        if (value == '') {
            // empty string, check what to do if empty
            if (param.blank == 'M') { // mandatory
                errmsg = "Section " + sectionName + ", Parameter: " + param.name + " cannot be empty<br/>\n";
                field.style.backgroundColor = "#ff8f8f";
                return errmsg;
            }
            return '';
        }

        let modifier = param.datatype.modifier;
        if (modifier == undefined || modifier == null)
            modifier = '';

        switch (param.datatype.type) {
            case 'i': // integer number
                value = Number(value);
                if (!Number.isInteger(value)) {
                    errmsg = " is not a valid integer";
                    break;
                }
                if (modifier != '') {
                    let digits = modifier.split(',', 1);
                    if (Number(value) > Number('9'.repeat(Number(digits)))) {
                        errmsg = "is too large";
                    }
                }
                break;

            case 'd': // decimal number
                value = Number(value);
                if (Number.isNaN(value)) {
                    errmsg = "is not a valid number";
                    break;
                }
                if (modifier != '') {
                    let digits = modifier.split(',', 1);
                    if (Number(value) > Number('9'.repeat(Number(digits)))) {
                        errmsg = "is too large";
                    }
                }
                break;

            case 's': // string
                if (value.length > Number(modifier)) {
                    errmsg = "is too long";
                }
                break;

            case 'r': // relative path name
                if (value.substring(0,1) == '/') {
                    errmsg = "is not a relative path, it cannot start with /";
                    break;
                }
                break;

            case 'a': // absolute path name
                if (value.substring(0,1) != '/') {
                    errmsg = "is not an absolute path, it must start with /";
                    break;
                }

            case 'h': // URI (http/https/mailto
                try {
                    let URLobj = new URL(value);
                } catch (error) {
                    errmsg = "is not a valid URI";
                }
                break;

            case 't': // textarea
                // nothing to check right now
                break;

            case 'e': // email address
                if (!validateAddress(value))
                    errmsg = "is not a valid email address";
                break;

            case 'l': // list (option)
                // nothing to check right now
                break;
        }


        if (errmsg != '') {
            errmsg = "Section " + sectionName + ", Parameter: " + param.name + " " + errmsg + "</br>";
            field.style.backgroundColor = "#ffafaf";
        }
        return errmsg;
    }

// close the tab
    close() {
        // check if dirty, and complain
        let changes = this.needSave();
        if (changes > 0) {
            if (!confirm('You have unsaved changes.  You asked to navigate away from this tab.  Click "OK" to discard the changes, or "Cancel" to keep them,' +
                ' and then click the "Configuration Editor" tab to return to this screen')) {
                return false;
            }
        }

        this.#saveBtn.disabled = true;
        this.#saveBtn.innerHTML = 'Save';
        return true;
    }

// save the changes back
    save() {
        if (!this.validateConfig())
            return false;

        // create the list of changed values along with their initial values
        let changes = this.needSave();  // see if we still need to save, after validation
        if (changes == 0) {
            show_message("No changes found, nothing to save", 'warn');
            return false;
        }

        // disable the save button to avoid double clicks....
        this.#saveBtn.disabled = true;
        this.#saveBtn.innerHTML = 'Saving...';

        let changedItems = [];
        let names = Object.keys(this.#fieldsChanged);
        for (let name of names) {
            if (this.#fieldsChanged[name]) {
                let pos = name.split('__', 2);
                let section = pos[0];
                let paramName = pos[1];

                let item = { fieldName: name, section: section, param: paramName,
                    initial: this.#initialConfig[section][paramName], new: this.#fieldList[name].value };
                changedItems.push(item);
            }
        }

        clearError();
        clear_message();

        let script = 'configEditSaveReloadChanges.php';
        let data = {
            task: 'update',
            fields: JSON.stringify(changedItems),
        }
        $.ajax({
            url: script,
            method: 'POST',
            data: data,
            success: function (data, textStatus, jhXHR) {
                if (data.error) {
                    showError(data.error);
                    this.#saveBtn.disabled = false;
                    this.#saveBtn.innerHTML = 'Save*';
                    return false;
                }
                this.#control = data.control;
                this.#sections = data.sections;
                this.#myPerm = data.perm;
                this.#myAuths = data.auths;
                this.#initialConfig = make_copy(data.currentConfig);
                this.#currentConfig = make_copy(data.currentConfig);
                drawConfig();
                if (data.message)
                    show_messgage(data.message, 'success');
                return true;
            },
            error: function (jqXHR, textStatus, errorThrown) {
                showError("ERROR in " + script + ": " + textStatus, jqXHR);
                this.#saveBtn.disabled = false;
                this.#saveBtn.innerHTML = 'Save*';
                return false;
            }
        });
    }

// discard the changes, reload the config
    discard() {
        let names = Object.keys(this.#fieldsChanged);

        for (let name of names) {
            if (this.#fieldsChanged[name]) {
                let field = this.#fieldList[name];
                let pos = name.split('__', 2);
                let section = pos[0];
                let paramName = pos[1];
                field.value = this.#initialConfig[section][paramName];
                field.style.backgroundColor = '';
                this.#fieldsChanged[name] = false;
            }
        }

        this.#saveBtn.disabled = true;
        this.#saveBtn.innerHTML = 'Save';
        this.#discardBtn.disabled = true;
    }
}
