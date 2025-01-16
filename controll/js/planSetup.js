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
    #planEditIndex = null;

    // edit item
    #editSelTable = null;
    #editSelButtons = null;
    #editSelLabel = null;
    #editSelIndex = null;
    #editSelItem = null;
    #editSelField = null;
    #editSelHidden = null;
    #editSelValues = null;
    #editSelRow = null;
    #categoryList = null;
    #categoryListDiv = null;
    #includeList = null;
    #includeListDiv = null;
    #excludeList = null;
    #excludeListDiv = null;
    #portalList = null;
    #portalListDiv = null;
    #portals = [ { portal: 'portal' }, { portal: 'artist'}, { portal: 'vendor'}, { portal: 'exhibitor'}, { portal: 'fan'} ];

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
            this.#categoryListDiv = document.getElementById('categoryListDiv');
            this.#includeList = document.getElementById('includeList');
            this.#includeListDiv = document.getElementById('includeListDiv');
            this.#excludeList = document.getElementById('excludeList');
            this.#excludeListDiv = document.getElementById('excludeListDiv');
            this.#portalList = document.getElementById('portalList');
            this.#portalListDiv = document.getElementById('portalListDiv');
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
                {title: "ID", field: "id", width: 65, visible: false, },
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

    // editList - build the select list for the page
    editList(type) {
        if (this.#editSelTable) {
            this.#editSelTable.destroy();
            this.#editSelTable = null;
        }

        this.#editSelButtons.hidden = true;
        this.#editSelLabel.innerHTML = '';
        var data = null;
        this.#editSelIndex = 'id;'

        switch (type) {
            case 'category':
                this.#editSelItem = 'catList';
                this.#editSelValues = this.#categoryList.value.split(',');
                this.#editSelLabel.innerHTML = "<b>Select which Categories apply to this payment plan:</b>"
                this.#editSelField = this.#categoryListDiv;
                this.#editSelHidden = this.#categoryList;
                data = memCategories;
                this.#editSelIndex = 'memCategory';
                this.#editSelTable = new Tabulator('#editSelTable', {
                    data: data,
                    layout: "fitDataTable",
                    index: this.#editSelIndex,
                    columns: [
                        {title: "Category", field: "memCategory", width: 200, },
                        {title: "Notes", field: "notes", width: 750, headerFilter: true, },
                    ],
                });
                break;

            case 'include':
                this.#editSelItem = 'includeList';
                this.#editSelValues = this.#includeList.value.split(',');
                this.#editSelLabel.innerHTML = "<b>Select which Memberships apply to this payment plan:</b>"
                this.#editSelField = this.#includeListDiv;
                this.#editSelHidden = this.#includeList;
                data = memLabels;
                this.#editSelTable = new Tabulator('#editSelTable', {
                    data: memLabels,
                    layout: "fitDataTable",
                    index: "id",
                    pagination: true,
                    paginationSize: 9999,
                    paginationSizeSelector: [10, 25, 50, 100, 250, true], //enable page size select element with these options
                    columns: [
                        {title: "ID", field: "id", width: 90, headerSort: true },
                        {title: "ConId", field: "conid", width: 120, headerFilter: true, headerSort: true },
                        {title: "Label", field: "label", width: 600, headerFilter: true, headerSort: true },
                    ],
                });
                break;

            case 'exclude':
                this.#editSelItem = 'excludeList';
                this.#editSelValues = this.#excludeList.value.split(',');
                this.#editSelLabel.innerHTML = "<b>Select which Memberships to exclude from this payment plan:</b>"
                this.#editSelField = this.#excludeListDiv;
                this.#editSelHidden = this.#excludeList;
                data = memLabels;
                this.#editSelTable = new Tabulator('#editSelTable', {
                    data: memLabels,
                    layout: "fitDataTable",
                    index: "id",
                    pagination: true,
                    paginationSize: 9999,
                    paginationSizeSelector: [10, 25, 50, 100, 250, true], //enable page size select element with these options
                    columns: [
                        {title: "ID", field: "id", width: 90, headerSort: true },
                        {title: "ConId", field: "conid", width: 120, headerFilter: true, headerSort: true },
                        {title: "Label", field: "label", width: 600, headerFilter: true, headerSort: true },
                    ],
                });
                break;

            case 'portal':
                this.#editSelItem = 'portalList';
                this.#editSelValues = this.#portalList.value.split(',');
                this.#editSelLabel.innerHTML = "<b>Select which Memberships portals will have access to this payment plan:</b>"
                this.#editSelField = this.#portalListDiv;
                this.#editSelHidden = this.#portalList;
                data = memLabels;
                this.#editSelTable = new Tabulator('#editSelTable', {
                    data: this.#portals,
                    layout: "fitDataTable",
                    index: "portal",
                    columns: [
                        {title: "Portal", field: "portal", width: 200, headerSort: true },
                    ],
                });
                break;
        }
        this.#editSelButtons.hidden = false;
        this.#editSelTable.on("cellClick", plans.clickedSelection)
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
        this.#editSelHidden.value = filter;
        if (filter == '') {
            this.#editSelField.innerHTML = '<i>None</i>';
        } else {
            this.#editSelField.innerHTML = filter.replace(/,/g, '<br/>');
        }
        this.closeSelTable();
        this.#editSelRow[this.#editSelItem] = filter;
        this.#editSelRow[this.#editSelItem + 'Array'] = filter.split(',');
        this.#editSelButtons.hidden = true;
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

    editPlan(index) {
        this.#planEditIndex = index;
        var row = this.#plansTable.getRow(index).getData();
        // first copy all the fields to the fields in the form
        document.getElementById('planName').value = row.name;
        document.getElementById('planDescription').innerHTML = row.description;
        this.#categoryList.value = row.catList;
        if (row.catList == null || row.catList == '') {
            this.#categoryListDiv.innerHTML = '<i>None</i>';
        } else {
            this.#categoryListDiv.innerHTML = row.catList.replace(/,/g, '<br/>');
        }
        this.#includeList.value = row.memList;
        if (row.memList == null || row.memList == '') {
            this.#includeListDiv.innerHTML = '<i>None</i>';
        } else {
            this.#includeListDiv.innerHTML = row.memList.replace(/,/g, '<br/>');
        }
        this.#excludeList.value = row.excludeList;
        if (row.excludeList == null || row.excludeList == '') {
            this.#excludeListDiv.innerHTML = '<i>None</i>';
        } else {
            this.#excludeListDiv.innerHTML = row.excludeList.replace(/,/g, '<br/>');
        }
        this.#portalList.value = row.portalList;
        if (row.portalList == null || row.portalList == '') {
            this.#portalListDiv.innerHTML = '<i>None</i>';
        } else {
            this.#portalListDiv.innerHTML = row.portalList.replace(/,/g, '<br/>');
        }
        document.getElementById('downPaymentPercent').value = row.downPercent;
        document.getElementById('downPaymentAmount').value = row.downAmt;
        document.getElementById('minPayment').value = row.minPayment;
        document.getElementById('maxNumPayments').value = row.numPaymentMax;
        document.getElementById('payByDate').value = row.payByDate;
        document.getElementById('paymentType').value = row.payType;
        document.getElementById('modifyPlan').value = row.modify;
        document.getElementById('reminders').value = row.reminders;
        document.getElementById('downPaymentIncludes').value = row.downIncludeNonPlan;
        document.getElementById('lastPartial').value = row.lastPaymentPartial;
        document.getElementById('active').value = row.active;

        this.#planTitleDiv.innerHTML = 'Edit Payment Plan: ' + row.name;
        this.#planHeadingDiv.innerHTML = 'Edit Payment Plan: '  + row.name;
        this.#planAddEditModal.show();
        this.#planSaveBTN.innerHTML = 'Save Changes';    }
};

function SetInitialSel() {
    plans.setInitialSel();
}