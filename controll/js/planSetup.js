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

    // edit item
    #editSelTable = null;
    #editSelButtons = null;
    #editSelLabel = null;
    #editSelIndex = null;
    #editSelItem = null;
    #editSelField = null;
    #editSelValues = null;
    #editSelRow = null;
    #categoryList = null;

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
            this.#categoryList = document.getElementById('categoryList');
            this.#editSelLabel = document.getElementById('editSelLabel');
            this.#editSelButtons = document.getElementById('editSelButtons');
            this.#editSelButtons.hidden = true;
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
                {title: "ID", field: "id", width: 65, visible: false, formatter: "textarea" },
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

    getselIndex() {
        return this.#editSelIndex;
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

    // editCategories - select the category list for this plan
    editCategoryList() {
        if (this.#editSelTable) {
            this.#editSelTable.destroy();
            this.#editSelTable = null;
        }

        this.#editSelButtons.hidden = true;
        this.#editSelLabel.innerHTML = '';
        this.#editSelIndex = null;

        var tableField = null;
        this.#editSelItem = 'catList';
        this.#editSelValues = this.#categoryList.innerHTML.split(',');
        this.#editSelLabel.innerHTML = "<b>Select which Categories apply to this payment plan:</b>"
        tableField = '#editSelTable';
        this.#editSelField = this.#categoryList;
        this.#editSelButtons.hidden = false;
        this.#editSelTable = new Tabulator(tableField, {
            data: memCategories,
            layout: "fitDataTable",
            index: "memCategory",
            columns: [
                {title: "Category", field: "memCategory", width: 200, },
                {title: "Notes", field: "notes", width: 750, headerFilter: true, },
            ],
        });
        this.#editSelTable.on("cellClick", plans.clickedSelection)
        this.#editSelIndex = 'memCategory';
        setTimeout(SetInitialSel, 100);
    }

    // table functions
    // setInitialSel - set the initial selected items based on the current values
    setInitialSel() {
        var rows = this.#editSelTable.getRows();
        for (var row of rows) {
            var name = row.getCell(this.#editSelIndex).getValue().toString();
            if (this.#editSelValues.includes(name)) {
                row.getCell(this.#editSelIndex).getElement().style.backgroundColor = "#C0FFC0";
            }
        }
        if (this.#editSelIndex == 'id')
            this.#editSelTable.setPageSize(25);
    }

    // toggle the selection color of the clicked cell
    clickedSelection(e, cell) {
        var filtercell = cell.getRow().getCell(plans.getselIndex());
        var value = filtercell.getValue();
        if (filtercell.getElement().style.backgroundColor) {
            filtercell.getElement().style.backgroundColor = "";
        } else {
            filtercell.getElement().style.backgroundColor = "#C0FFC0";
        }
    }

    // set all/clear all sections in table based on direction
    setEditSel(direction) {
        var rows = this.#editSelTable.getRows();
        for (var row of rows) {
            row.getCell(plans.getselIndex()).getElement().style.backgroundColor = direction ? "#C0FFC0" : "";
        }
    }

    // retrieve the selected rows and set the field values
    applyEditSel() {
        // store all the fields back into the table row
          var filter = '';
        var rows = null;
        rows = this.#editSelTable.getRows();
        for (var row of rows) {
            if (row.getCell(plans.getselIndex()).getElement().style.backgroundColor != '') {
                filter += ',' + row.getCell(plans.getselIndex()).getValue();
            }
        }
        if (filter != '')
            filter = filter.substring(1);
        //console.log(filter);
        this.#editSelField.innerHTML = filter;
        this.closeSelTable();
        this.#editSelRow[this.#editSelItem] = filter;
        this.#editSelRow[this.#editSelItem + 'Array'] = filter.split(',');
    }

    closeSelTable() {
        if (this.#editSelTable) {
            this.#editSelTable.destroy();
            this.#editSelTable = null;
        }
        this.#editSelButtons.hide = true;
        this.#editSelLabel.innerHTML = '';
        this.#editSelIndex = null;
    }
};

function SetInitialSel() {
    plans.setInitialSel();
}