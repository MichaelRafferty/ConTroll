// globals for plan Setup configuration pane

// finance class - functions for finance page including payment plans and money related transactions
class Payors {
    #payorsTable = null;
    #debug = 0;
    #conid = null;
    #dirty = false;

    // payor plan items
    #payorPlans = null;

    // edit item
    #portals = [ { portal: 'portal' }, { portal: 'artist'}, { portal: 'vendor'}, { portal: 'exhibitor'}, { portal: 'fan'} ];

    constructor(conid, debug) {
        this.#debug = debug;
        this.#conid = conid;

        // set up modals
        var id = document.getElementById('editPlan');
        if (id) {
            console.log('edit plan not yet')
        }
        //this.#planSaveChangesBTN = document.getElementById('planSaveBtn');
    }

    open() {
        var _this = this;
        // load the current payor plan data
        var script = "scripts/finance_getPayors.php";

        var postdata = {
            ajax_request_action: 'payorPlans',
        };
        clear_message();
        this.#dirty = false;
        //console.log(postdata);
        $.ajax({
            url: script,
            method: 'POST',
            data: postdata,
            success: function (data, textStatus, jhXHR) {
                if (data['error']) {
                    show_message(data['error'], 'error');
                    return false;
                }
                _this.#payorPlans = data['payorPlans'];
                payors.draw();
                show_message(data['success'], 'success');
            },
            error: function (jqXHR, textStatus, errorThrown) {
                showError("ERROR in " + script + ": " + textStatus, jqXHR);
                return false;
            }
        });
    }

    draw() {
        // show initial plans table
        this.#payorsTable = new Tabulator('#payorPlansTable', {
            data: this.#payorPlans,
            layout: "fitDataTable",
            index: "id",
            columns: [
                {title: "Actions", },
                {title: "ID", field: "id", width: 65, visible: false, },
                {title: "Perid", field: "perid", width: 100, hozAlign: "right", headerFilter: true, },
                {title: "Name", field: "fullName",  headerWordWrap: true, headerFilter: true, width: 250,
                    headerFilterFunc: fullNameHeaderFilter,
                    formatter: "textarea", },
                {title: "Plan ID", field: "planId", width: 65, headerWordWrap: true, visible: false, },
                {title: "Plan Name", field: "name", width: 100,headerWordWrap: true, headerFilter: true, },
                {title: "Initial Amt", field: "initialAmt", headerWordWrap: true, width: 100,
                     headerHozAlign: "right", hozAlign:"right", headerFilter: true, headerFilterFunc: numberHeaderFilter, },
                {title: "Non Plan Amt", field: "nonPlanAmt", width: 100, headerWordWrap: true, headerFilter: true,
                    headerHozAlign: "right", hozAlign:"right", headerFilterFunc: numberHeaderFilter, },
                {title: "Down Pmt", field: "downPayment", width: 100, headerWordWrap: true, headerFilter: true,
                    headerHozAlign: "right", hozAlign:"right", headerFilterFunc: numberHeaderFilter, },
                {title: "Opening Bal", field: "openingBalance", width: 100, headerWordWrap: true, headerFilter: true,
                    headerHozAlign: "right", hozAlign:"right", headerFilterFunc: numberHeaderFilter, },
                {title: "Min Pmt", field: "minPayment", width: 100, headerWordWrap: true, headerFilter: true,
                    headerHozAlign: "right", hozAlign:"right", headerFilterFunc: numberHeaderFilter, },
                {title: "Final Pmt", field: "finalPayment", width: 100, headerWordWrap: true, headerFilter: true,
                    headerHozAlign: "right", hozAlign:"right", headerFilterFunc: numberHeaderFilter, },
                {title: "Num Pmts", field: "numPayments", width: 65, headerWordWrap: true, headerFilter: true,
                    headerHozAlign: "right", hozAlign:"right", headerFilterFunc: numberHeaderFilter, },
                {title: "Days Btwn", field: "daysBetween", width: 65, headerWordWrap: true, headerFilter: true,
                    headerHozAlign: "right", hozAlign:"right", headerFilterFunc: numberHeaderFilter, },
                {title: "Pay By Date", field: "payByDate", width: 130, headerWordWrap: true, headerFilter: true, },
                {title: "Pay Type", field: "payType", width: 65, headerWordWrap: true, headerFilter: true, },
                {title: "Status", field: "status", headerWordWrap: true, headerFilter: true, },
                {title: "Bal Due", field: "balanceDue", width: 100, headerWordWrap: true, headerFilter: true,
                    headerHozAlign: "right", hozAlign:"right", headerFilterFunc: numberHeaderFilter, },
                {title: "Create Date", field: "createDate", width: 130, headerWordWrap: true, headerFilter: true, headerSort: true, },
                {title: "Pmts Made", field: "paymentsMade", width: 65, headerWordWrap: true, headerFilter: true,
                    headerHozAlign: "right", hozAlign:"right", headerFilterFunc: numberHeaderFilter, },
                {title: "Last Pmt Date", field: "lastPaymentDate", width: 130, headerWordWrap: true, headerFilter: true, headerSort: true, },
                {title: "Last Pmt Amt", field: "lastPaymentAmt", width: 100, headerWordWrap: true, headerFilter: true,
                    headerHozAlign: "right", hozAlign: "right", headerFilterFunc: numberHeaderFilter, },
                /*{title: "Next Pmt Due", field: "nextPatmentDue", width: 100, headerWordWrap: true, headerFilter: true, headerSort: true, },*/
            ],
        });
    }

    close() {
        if (this.#payorsTable) {
            //this.#payorsTable.off("dataChanged");
            //this.#payorsTable.off("rowMoved");
            //this.#payorsTable.off("cellEdited");
            this.#payorsTable.destroy();
            this.#payorsTable = null;
            //this.#planSaveChangesBTN.innerHTML = "Save Changes";
            //this.#planSaveChangesBTN.disabled = true;
        }
    }


    editbutton(cell, formatterParams, onRendered) {
        var index = cell.getRow().getIndex()
        return '<button class="btn btn-secondary" style = "--bs-btn-padding-y: .0rem; --bs-btn-padding-x: .3rem; --bs-btn-font-size: .75rem;",' +
            ' onclick="payors.editPmt(' + index + ');">Edit</button>';
    }

    /*
    editPlan(index) {
        this.#planEditIndex = index;
        var row = this.#payorsTable.getRow(index).getData();
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
        this.#planSaveBTN.innerHTML = 'Save Changes Back to Prior Screen';
    }

    saveAddEdit() {
        // get the data
        var newRow = {
            id: this.#planEditIndex,
            name: document.getElementById('planName').value,
            description: document.getElementById('planDescription').innerHTML,
            categoryList: this.#categoryList.value,
            includeList: this.#includeList.value,
            excludeList: this.#excludeList.value,
            cportalList: this.#portalList.value,
            downPercent: document.getElementById('downPaymentPercent').value,
            downAmt: document.getElementById('downPaymentAmount').value,
            minPayment: document.getElementById('minPayment').value,
            numPaymentMax: document.getElementById('maxNumPayments').value,
            payByDate: document.getElementById('payByDate').value,
            payType: document.getElementById('paymentType').value,
            modify: document.getElementById('modifyPlan').value,
            reminders: document.getElementById('reminders').value,
            downIncludeNonPlan: document.getElementById('downPaymentIncludes').value,
            lastPaymentPartial: document.getElementById('lastPartial').value,
            active: document.getElementById('active').value
        }
        if (this.#planEditIndex == null) {
            newRow['id'] =  -99;
            newRow['torder'] = 99999;
            this.#payorsTable.addRow(newRow);
        } else {
            this.#payorsTable.updateData([newRow]);
        }
        plans.dataChanged();
        this.#planAddEditModal.hide();
    }
    */
};
