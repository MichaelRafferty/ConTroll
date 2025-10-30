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
        let max=80;
        let decimals = '';
        let html = '';
        let name = sectionName + '__' + param.name;
        let id = ' id="' + name + '" name="' + name + '"';

        if (value === undefined)
            value = '';
        switch (param.datatype.type) {
            case 'i': // integer number
                html = '<input type=number placeholder="' + param.placeholder + '" ' + id + ' value="' + value + '"';
                if (param.datatype.modifier != '') {
                    html += 'max="' + '9'.repeat(Number(param.datatype.modifier)) + '"';
                }
                html += ' onchange="configEditor.changed(' + "'" + name + "'" + ');">';
                break;

            case 'd': // decimal number
                break;

            case 's': // string
            case 'r': // relative path name
            case 'a': // absolute path name
            case 'h': // URI (http/https/mailto
                max = param.datatype.modifier != '' ?  Number(param.datatype.modifier) : 256;
                size = max > 75 ? 80 : (max + 5);
                html = '<input type=text placeholder="' + param.placeholder + '" ' + id + ' value="' + value + '"' +
                    ' size="' + size + '" maxlength="' + max + '" onchange="configEditor.changed(' + "'" + name + "'" + ');"> (Max Length: ' + max + ')';
                break;
            case 't': // textarea
                size = param.datatype.modifier.split(',');
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
                let options = param.datatype.modifier.substring(1).split(',');
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
        let param = pos[1];
        let field = null;
        if (this.#fieldList.hasOwnProperty(name)) {
            field = this.#fieldList[name];
        } else {
            field = document.getElementById(name);
            this.#fieldList[name] = field;
        }

        let changed = this.#initialConfig[section][param] != field.value;
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
                errormsg += this.valiateParam(sectionName, param);
            }
        }
        if (errormsg != '') {
            show_message(errormsg, 'error')
            return;
        }
        this.#saveBtn.disabled = false;
    }

// validateParam - validate a specific parameter according to its configuration
    validateParam(sectionName, param) {
        return '';
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
        console.log("save called");
    }

// discard the changes, reload the config
    discard() {
        let names = Object.keys(this.#fieldsChanged);

        for (let name of names) {
            if (this.#fieldsChanged[name]) {
                let field = this.#fieldList[name];
                let pos = name.split('__', 2);
                let section = pos[0];
                let param = pos[1];
                field.value = this.#initialConfig[section][param];
                field.style.backgroundColor = '';
                this.#fieldsChanged[name] = false;
            }
        }

        this.#saveBtn.disabled = true;
        this.#discardBtn.disabled = true;
    }
}
