// globals for tax Setup configuration pane

// tax class - functions for finance page sales tax items
class taxConfig {
    #taxTable = null;
    #debug = 0;
    #conid = null;
    #taxSaveBTN = null;
    #taxAddNewBTN = null;

    // edit modal fields
    #taxEditModal = null;
    #taxTitle = null;
    #taxHeading = null;
    #taxField = null;
    #taxLabel = null;
    #taxRate = null;
    #taxGLNum = null;
    #taxGLLabel = null;
    #taxItemsDiv = null;
    #taxActive = null;

    #dirty = false;

    // constants
    #taxFields = ['tax1', 'tax2', 'tax3', 'tax4', 'tax5'];

    constructor(conid, debug) {
        this.#debug = debug;
        this.#conid = conid;
        this.#taxSaveBTN = document.getElementById('taxSaveBtn');
        this.#taxAddNewBTN = document.getElementById('taxAddNewBtn');

        let id = document.getElementById('editTax');
        if (id) {
            this.#taxEditModal = new bootstrap.Modal(id, {focus: true, backdrop: 'static'});
            this.#taxTitle = document.getElementById('tax-title');
            this.#taxHeading = document.getElementById('tax-heading');
            this.#taxRate = document.getElementById('taxRate');
            this.#taxLabel = document.getElementById('taxLabel');
            this.#taxActive = document.getElementById('taxActive');
            this.#taxGLNum = document.getElementById('taxGLNum');
            this.#taxGLLabel = document.getElementById('taxGLLabel');
            this.#taxItemsDiv = document.getElementById('taxItemsDiv');
        }
    }

    open() {
        let script = "scripts/finance_updateGetTaxConfig.php";

        let postdata = {
            ajax_request_action: 'getTax',
        };
        clear_message();
        clearError();
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
                checkRefresh(data);
                tax.draw(data);
                show_message(data['success'], 'success');
            },
            error: function (jqXHR, textStatus, errorThrown) {
                showError("ERROR in " + script + ": " + textStatus, jqXHR);
                return false;
            }
        });
    }

    draw(data) {
        let _this = this;
        // if this is from the config file, change updated by and set things to dirty
        if (data.taxList.length == 1) {
            if (Number(data.taxList[0].updatedBy) < 0) {
                // from config
                data.taxList[0].updatedBy = null;
                this.dataChanged();
            }
        }
        // show initial tax Config table
        this.#taxTable = new Tabulator('#taxConfigTable', {
            data: data.taxList,
            layout: "fitDataTable",
            index: "taxField",
            columns: [
                {title: 'Edit', formatter: this.editbutton, hozAlign:"center", headerHozAlign: "center", headerSort: false },
                {title: "Active", field: "active", editor: 'list', editorParams: { values: ['Y', 'N'], }, headerSort:false, },
                {title: "Con Id", field: "conid", headerSort:false },
                {title: "taxField", field: "taxField", headerSort:false , },
                {title: "Receipt Label", field: "label", width: 300, editor: 'input', editorParams: { elementAttributes: { maxlength: 64 }}, headerSort:false },
                {title: "Tax Rate (%)", field: "rate", editor: 'number', editorParams: { min: 0, max: 99 }, headerSort:false, },
                {title: "Taxable", field: "taxItemsDisplay", headerSort:false, width: 300, formatter: 'textarea', },
                {title: "GL Num", field: "glNum", headerSort: false, editor: "input", editorParams: {maxlength: "16"}, width: 120, },
                {title: "GL Label", field: "glLabel", headerSort: false, editor: "input", editorParams: {maxlength: "64"}, width: 300, },
                {title: "Last Update", field: "lastUpdate", headerSort:false, },
                {title: "Updated By", field: "updatedBy", headerSort:false , },
                { field: "taxItems", visible: false, },
            ]});

        this.#taxTable.on("dataChanged", function (data) {
            _this.dataChanged();
        });
        this.#taxTable.on("cellEdited", cellChanged);
        this.#taxAddNewBTN.disabled = data['taxList'].length >= 5;
    }

    editbutton(cell, formatterParams, onRendered) {
        let row = cell.getRow();
        let taxField = row.getData().taxField;
        return '<button class="btn btn-primary btn-sm" style = "--bs-btn-padding-y: .0rem; --bs-btn-padding-x: .3rem; --bs-btn-font-size: .75rem;",' +
                    ' onclick="tax.editTax(\'' + taxField + '\');">Edit</button>';
    }

    editTax(row) {
        console.log("editTax: " + row);
        let taxrow = this.#taxTable.getRow(row);
        let rowData = taxrow.getData();
        console.log(rowData);
        this.#taxTitle.innerHTML = rowData.taxField;
        this.#taxHeading.innerHTML = rowData.taxField + ' for ' + rowData.conid;
        this.#taxLabel.value = rowData.label;
        this.#taxRate.value = rowData.rate;
        this.#taxActive.value = rowData.active;
        this.#taxGLNum.value = rowData.glNum;
        this.#taxGLLabel.value = rowData.glLabel;
        this.#taxItemsDiv.innerHTML = rowData.taxItemsDisplay;
        this.#taxEditModal.show();
    }

    close() {
        if (this.#taxTable) {
            this.#taxTable.off("dataChanged");
            this.#taxTable.off("cellEdited");
            this.#taxTable.destroy();
            this.#taxTable = null;
            this.#taxSaveBTN.innerHTML = "Save Changes";
            this.#taxSaveBTN.disabled = true;
        }
    }

    dataChanged() {
        //data - the updated table data
        this.#taxSaveBTN.innerHTML = "Save Changes*";
        this.#taxSaveBTN.disabled = false;
        this.#dirty = true;
    };

    addNew() {
        // figure which row doesn't yet exist
        let thisRow = '';
        let thisId = '';
        let row = false;
        let tableData = this.#taxTable.getData();
        for (let i = 0; i < this.#taxFields.length; i++) {
            for (let j = 0; j < tableData.length; j++) {
                thisId = tableData[j].taxField;
                if (thisId == this.#taxFields[i])
                    break;
            }
            if (thisId != this.#taxFields[i]) {
                thisRow = this.#taxFields[i];
                break;
            }
        }
        if (thisRow == '') {
            show_message("All five possible tax rows already exist, cannot add more.", 'error');
            return;
        }
        this.#taxTable.addRow({conid: this.#conid, taxField: thisRow, active: 'Y'});
    }

    // save the table back to the database
    save() {
        let _this = this;

        if (this.#taxTable != null) {
            this.#taxSaveBTN.innerHTML = "Saving...";
            this.#taxSaveBTN.disabled = true;

            let script = "scripts/finance_updateGetTaxConfig.php";

            let postdata = {
                ajax_request_action: 'updateTax',
                tabledata: JSON.stringify(this.#taxTable.getData()),
                tablename: 'taxList',
            };
            clear_message();
            clearError();
            this.#dirty = false;
            //console.log(postdata);
            $.ajax({
                url: script,
                method: 'POST',
                data: postdata,
                success: function (data, textStatus, jhXHR) {
                    if (data['error']) {
                        show_message(data['error'], 'error');
                        // reset save button
                        _this.dataChanged();
                        _this.#taxSaveBTN.disabled = false;
                        _this.#taxSaveBTN.innerHTML = "Save Changes*";
                        return false;
                    }
                    checkRefresh(data);
                    tax.close();
                    tax.draw(data);
                    show_message(data['success'], 'success');
                },
                error: function (jqXHR, textStatus, errorThrown) {
                    showError("ERROR in " + script + ": " + textStatus, jqXHR);
                    _this.dataChanged();
                    _this.#taxSaveBTN.disabled = false;
                    _this.#taxSaveBTN.innerHTML = "Save Changes*";
                    return false;
                }
            });
        }
    }
};
