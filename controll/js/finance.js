//import { TabulatorFull as Tabulator } from 'tabulator-tables';
// globals required for exhibitorProfile.js
const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

// globals for finance page
var finance = null;
var fileManager = null
var plans = null;
var payors = null;
var tax = null;

// finance class - functions for finance page including payment plans and money related transactions
class Finance {
    // global items
    #conid = null;
    #debug = 0;
    #debugVisible = false;
    #message_div = null;
    #result_message_div = null;

    // pane items
    #financeTabs = {};
    #currentPane = null;

    constructor(conid, debug) {
        this.#debug = debug;
        this.#conid = conid;
        this.#message_div = document.getElementById('test');
        this.#result_message_div = document.getElementById('result_message');

        this.#financeTabs['overview'] = document.getElementById('overview-content');
        this.#financeTabs['taxConfig'] = document.getElementById('taxConfig-pane');
        this.#financeTabs['paymentPlans'] = document.getElementById('paymentPlans-pane');
        this.#financeTabs['payorPlans'] = document.getElementById('payorPlans-pane');
        this.#financeTabs['coupon'] = document.getElementById('coupon-pane');
        this.#financeTabs['fileManager'] = document.getElementById('fileManager-pane');
        this.#currentPane = this.#financeTabs['overview'];
        if (this.#debug & 1) {
            console.log("Debug = " + debug);
            console.log("conid = " + conid);
        }
        if (this.#debug & 2) {
            this.#debugVisible = true;
        }

    };

    // common code for changing tabs
    // top level - overview, payment plans, etc.
    setFinanceTab(tabname) {
        // need to add the do you wish to save dirty data item
        clearError();
        clear_message();
        var content = tabname.replace('-pane', '');

        if (this.#currentPane) {
            this.#currentPane.hidden = true;
        }
        this.#financeTabs[content].hidden = false;
        this.#currentPane = this.#financeTabs[content];
        if (plans) {
            plans.close();
            plans = null;
        }
        if (payors) {
            payors.close();
            payors = null;
        }
        if (coupons) {
            coupons.close();
            coupons = null;
        }
        if (tax) {
            tax.close();
            tax = null;
        }

        this.#currentPane.hidden = false;

        switch(content) {
            case 'taxConfig':
                if (tax == null)
                    tax = new taxConfig(config['conid'], config['debug']);
                tax.open();
                break;

            case 'paymentPlans':
                if (plans == null)
                    plans = new PlansSetup(config['conid'], config['debug']);
                plans.open();
                break;

            case 'payorPlans':
                if (payors == null)
                    payors = new Payors(config['conid'], config['debug']);
                payors.open();
                break;

            case 'coupon':
                if (coupons == null)
                    coupons = new Coupon();
                coupons.open();
                break;

            case 'fileManager':
                fileManager.open();
                break;
        }
    }
};

function deleteicon(cell, formattParams, onRendered) {
    var value = cell.getValue();
    if (value == 0)
        return "&#x1F5D1;";
    return value;
}

function splitlist(cell, formattParams, onRendered) {
    var value = cell.getValue();
    if (value) {
        value = value.toString().replace(',', ',<br/>');
        return value;
    }
    return '';
}

function cellChanged(cell) {
    dirty = true;
    cell.getElement().style.backgroundColor = "#fff3cd";
}

function deleterow(e, row) {
    var count = row.getCell("uses").getValue();
    if (count == 0) {
        row.getCell("to_delete").setValue(1);
        row.getCell("uses").setValue('<span style="color:red;"><b>Del</b></span>');
    }
}

// create class on page render
window.onload = function initpage() {
    finance = new Finance(config['conid'], config['debug']);
    fileManager = new FileManager();
}
