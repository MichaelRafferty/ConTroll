// Configuration Editor Class - all functions and data related to displaying, editing and saving the reg_conf configuration file.
// Used by any section that edits a portion of reg_conf.ini

class ConfigEditor {
// privates
    #locale = 'en-US';
    #currencyFmt = null;

// DOM objects
    #saveBtn = null;
    #discardBtn = null;
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
