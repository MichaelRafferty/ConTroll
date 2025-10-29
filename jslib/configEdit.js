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

    drawParam(sectionName, param) {
        // R: role
        let editable = param.role.editable == 1;
        //editable=false;
        let visible = param.role.vis == 'V' || editable;
        //visible=false;
        let visibleStart = visible ? '' : '<span style="color: lightgrey;">';
        let visibleEnd = visible ? '' : '</span>';

        // N: name
        let html = "<div class='row mt-2'><div class='col-sm-auto'><h4><b>" + visibleStart + param.name + visibleEnd + "</h4></b></div></div>\n";

        // H: hint
        html += "<div class='row mt-1'><div class='col-sm-12'>"  + visibleStart + param.hint + visibleEnd + '</div></div>\n';

        // the field itself
        html += "<div class='row mt-1'><div class='col-sm-2'>" + visibleStart + param.name + visibleEnd + "</div>";
        // the field, using P, and D
        if (editable) {
            html += '<div class="col-sm-auto">' + this.formatInput(sectionName, param, this.#currentConfig[param.name]) + '</div>';
        } else {
            html += '<div class="col-sm-auto">' + visibleStart + this.#currentConfig[param.name] + visibleEnd + '</span></div>';
        }

        //  P, H, D, B

        html += "</div>\n";

        return html;
    }

    formatInput(sectionName, param, value) {
        let size = '';
        let max=80;
        let decimals = '';
        let html = '';
        let id = ' id="' + sectionName + '_' + param.name + '" name="' + sectionName + '_' + param.name + '"';
        switch (param.datatype.type) {
            case 'i': // integer number
                html = '<input type=number placeholder="' + param.placeholder + '" ' + id + ' value="' + value + '"';
                if (param.datatype.modifier != '') {
                    html += 'max="' + '9'.repeat(Number(param.datatype.modifier)) + '"';
                }
                html += '>';
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
                    ' size="' + size + '" maxlength="' + max + '"> (Max Length: ' + max + ')';
                break;
            case 't': // textarea
                size = param.datatype.modifier.split(',');
                if (size[0] < 50) size[0] = 50;
                if (size[1] < 3) size[0] = 3;
                html = '<textarea placeholder="' + param.placeholder + '" ' + id + ' cols="' + size[0] + '" rows="' + size[1] +
                    '">' + value + '</textarea>';
                break;

            case 'e': // email address
                max=256;
                size=80;
                html = '<input type=email placeholder="' + param.placeholder + '" ' + id + ' value="' + value + '"' +
                    ' size="' + size + '" maxlength="' + max + '"> (Max Length: ' + max + ')';
                break;

            case 'l': // list (option)
                html = '<select ' + id + '>\n';
                let options = param.datatype.modifier.substring(1).split(',');
                for (let option of options) {
                    html += '<option value="' + option + '"' + (value == option ? ' selected' : '') + '>' + option + '</option>';
                }
                html += '</select>';
                break;

            default: // who knows
                html = '<input type=text placeholder="' + param.placeholder + '" ' + id + ' value="' + value + '" size="80"/>';
        }
        return html;
    }

// close the tab
    close() {
        // check if dirty, and complain
        console.log("close called");

        this.#saveBtn.disabled = true;
        this.#saveBtn.innerHTML = 'Save';
    }

// save the changes back
    save() {
        console.log("save called");
    }

// discard the changes, reload the config
    discard() {
        console.log("discard called");
    }
}
