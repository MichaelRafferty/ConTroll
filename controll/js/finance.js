//import { TabulatorFull as Tabulator } from 'tabulator-tables';
// globals required for exhibitorProfile.js
const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

// globals for finance page
finance = null;
plans = null;

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
        this.#currentPane = content;
        if (content != 'paymentPlans') {
            if (plans) {
                plans.close();
                plans = null;
            }
        }

        if (this.#currentPane) {
            this.#currentPane.hidden = true;
            this.#currentPane = null;
        }

        if (content == 'overview')
            return;

        if (content == 'paymentplans') {
            if (plans == null)
                plans = new plansSetup(config['conid'], config['debug']);
            plans.open();
            return;
        }
    }
};

// create class on page render
window.onload = function initpage() {
    finance = new Finance(config['conid'], config['debug']);
}