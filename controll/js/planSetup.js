// globals for plan Setup configuration pane

// finance class - functions for finance page including payment plans and money related transactions
class PlansSetup {
    #plansTable = null;
    #debug = 0;
    #conid = null;
    #planArray = [];
    #planAddEditModal = null;
    #planTitleDiv = null;
    #planHeadingDiv = null;
    #planSaveBTN = null;
    #planSaveChangesBTN = null;


    constructor(conid, debug) {
        this.#debug = debug;
        this.#conid = conid;

        // set up modals
        var id = document.getElementById('addEditPlan');
        if (id) {
            this.#planAddEditModal = new bootstrap.Modal(id, {focus: true, backdrop: 'static'});
            this.#planTitleDiv = document.getElementById('plan-title');
            this.#planHeadingDiv = document.getElementById('plan-heading');
            this.#planSaveBTN = document.getElementById('plan-saveRow-btn');
        }
        this.#planSaveChangesBTN = document.getElementById('planSaveBtn');

        // show initial plans table
        this.#plansTable = new Tabulator('#paymentPlanTable', {
            data: paymentPlans,
            layout: "fitDataTable",
            index: "id",
            movableRows: true,
            columns: [
                { rowHandle: true, formatter: "handle", frozen: true, width: 30, minWidth: 30, maxWidth: 30, headerSort: false },
                {title: "Edit", formatter: this.editbutton, hozAlign:"left", headerSort: false },
                {title: "ID", field: "id", visible: true, width: 65, visible: false, formatter: "textarea" },
                {title: "Name", field: "name", headerFilter: true, headerSort: true, },
                {title: "Description", field: "description", maxWidth: 250, headerFilter: true, headerSort: true, formatter: "textarea", },
                {title: "Category List", field: "catList", headerWordWrap: true, headerSort: false, headerFilter: true, width: 120, formatter: splitlist, },
                {title: "Include List", field: "memList", headerWordWrap: true, headerSort: false, headerFilter: true, width: 120, formatter: splitlist, },
                {title: "Exclude List", field: "excludeList", headerWordWrap: true, headerSort: false, headerFilter: true, width: 120, formatter: splitlist, },
                {title: "Portals", field: "portalList", headerSort: false, headerFilter: true, width: 120, formatter: splitlist, },
                {title: "% Down Payment", field: "downPercent", width: 80, headerSort: false, headerWordWrap: true },
                {title: "$ Down Payment", field: "downAmt", width: 100, headerSort: false, headerWordWrap: true },
                {title: "Min Payment", field: "minPayment", width: 100, headerSort: false, headerWordWrap: true },
                {title: "Max # Pmts", field: "numPaymentMax", width: 80, headerSort: false, headerWordWrap: true },
                {title: "Pay By Date", field: "payByDate", width: 120, headerSort: true, headerWordWrap: true, },
                {title: "Pay Type", field: "payType", headerSort: true, headerWordWrap: true, width: 90,
                    headerFilter: 'list', headerFilterParams: { values: ['auto', 'manual'], },
                },
                {title: "Mod", field: "modify", headerSort:false, headerFilter: false, width: 70, },
                {title: "Re- mind", field: "reminders",  headerWordWrap: true, headerSort:false, headerFilter: false, width: 70, },
                {title: "Down Incl Non Plan", field: "downIncludeNonPlan",  headerWordWrap: true, headerSort:false, headerFilter: false,
                    width: 70, },
                {title: "Last Pmt Part", field: "lastPaymentPartial", headerWordWrap: true, headerSort:false, headerFilter: false, width: 70,  },
                {title: "Act", field: "active", headerSort:true, width: 80,
                    headerFilter: 'list', headerFilterParams: { values: ['Y', 'N'], },
                },
                { title: "Sort Order", field: "sortorder", headerSort: true, visible: false },
                { title: "Delete", field: "uses", formatter: deleteicon, hozAlign: "center", headerSort: false,
                    cellClick: function (e, cell) {
                        deleterow(e, cell.getRow());
                    }
                },
                { field: "required", visible: false, },
                { field: "to_delete", visible: false, },

       ]});
    }

    open() {
        console.log("open stub called");
    }

    editbutton(cell, formatterParams, onRendered) {
        var index = cell.getRow().getIndex()
        return '<button class="btn btn-secondary" style = "--bs-btn-padding-y: .0rem; --bs-btn-padding-x: .3rem; --bs-btn-font-size: .75rem;",' +
            ' onclick="plans.editPlan(' + index + ');">Edit</button>';
    }

    // add/edit plan modal functions
    addNew() {
        this.#planTitleDiv.innerHTML = 'Add New Payment Plan';
        this.#planHeadingDiv.innerHTML = 'Add New Payment Plan';
        this.#planAddEditModal.show();
        this.#planSaveBTN.innerHTML = 'Add Plan';
    }
};

