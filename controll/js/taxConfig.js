// globals for tax Setup configuration pane

// tax class - functions for finance page sales tax items
class taxConfig {
    #taxTable = null;
    #debug = 0;
    #conid = null;
    #taxSaveBTN = null;
    #taxAddNewBTN = null;
    #taxList = null;
    #taxable = null;

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
    #taxItemsTable = null;
    #editFieldsDirty = false;
    #taxSaveRowBtn = null;
    #taxRowBeforeEdit = null;

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
            this.#taxSaveRowBtn = document.getElementById('tax-saveRow-btn');
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
        this.#taxable = data.taxable;
        this.#taxList = data.taxList;
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
        this.#taxTable.on("cellEdited", taxCellChanged);
        this.#taxAddNewBTN.disabled = data['taxList'].length >= 5;
    }

    cellChanged(cell) {
        this.#dirty = true;
        cellChanged(cell);
    }

    editbutton(cell, formatterParams, onRendered) {
        let row = cell.getRow();
        let taxField = row.getData().taxField;
        return '<button class="btn btn-primary btn-sm" style = "--bs-btn-padding-y: .0rem; --bs-btn-padding-x: .3rem; --bs-btn-font-size: .75rem;",' +
                    ' onclick="tax.editTax(\'' + taxField + '\');">Edit</button>';
    }

    editTax(row) {
        clear_message('tax_message_div');
        //console.log("editTax: " + row);
        let taxrow = this.#taxTable.getRow(row);
        this.#taxField = row;
        this.#taxRowBeforeEdit = taxrow.getData();
        this.#taxTitle.innerHTML = this.#taxRowBeforeEdit.taxField;
        this.#taxHeading.innerHTML = this.#taxRowBeforeEdit.taxField + ' for ' + this.#taxRowBeforeEdit.conid;
        this.#taxLabel.value = this.#taxRowBeforeEdit.label;
        this.#taxRate.value = this.#taxRowBeforeEdit.rate;
        this.#taxActive.value = this.#taxRowBeforeEdit.active;
        this.#taxGLNum.value = this.#taxRowBeforeEdit.glNum;
        this.#taxGLLabel.value = this.#taxRowBeforeEdit.glLabel;
        //this.#taxItemsDiv.innerHTML = this.#taxRowBeforeEdit.taxItemsDisplay;
        // build the edit area for the taxable items
        let taxables = this.#taxRowBeforeEdit.taxItems;
        for (let i = 0; i < this.#taxable.length; i++) {
            let tax = this.#taxable[i];
            if (taxables.hasOwnProperty(tax.item)) {
                this.#taxable[i].taxable = taxables[tax.item].taxable;
            } else {
                this.#taxable[i].taxable = '-';
            }
        }

        if (this.#taxItemsTable != null) {
            this.#taxItemsTable.replaceData(this.#taxable);
        } else {
            this.#taxItemsTable = new Tabulator('#taxItemsDiv', {
                data: this.#taxable,
                layout: "fitData",
                index: "item",
                columns: [
                    {title: 'Item', field: "item", headerSort:false, visible: false, },
                    {title: "Label", field: "label", headerSort:false, minWidth: 410, },
                    {title: "Taxable (editable)", headerWordWrap: true, field: "taxable", editor: 'list', editorParams: { values: ['-', 'Y', 'N'], },
                        headerSort:false, hozAlign: "center", width: 100 },
                    {title: "Default Taxable", headerWordWrap: true, field: "defaultValue",
                        headerSort:false,  hozAlign: "center", width: 100, },
                ],
            });
            this.#taxItemsTable.on("cellEdited", taxItemCellChanged);
        }
        this.#editFieldsDirty = false;
        clearFieldChanged(this.#taxActive);
        clearFieldChanged(this.#taxLabel);
        clearFieldChanged(this.#taxRate);
        clearFieldChanged(this.#taxGLNum);
        clearFieldChanged(this.#taxGLLabel);
        this.#taxSaveRowBtn.disabled = true;
        this.#taxSaveRowBtn.innerHTML = "Save Changes";
        this.#taxEditModal.show();
    }

    itemCellChanged(cell) {
        this.#editFieldsDirty = true;
        cellChanged(cell);
        this.#taxSaveRowBtn.disabled = false;
        this.#taxSaveRowBtn.innerHTML = "Save Changes*";
    }

    editFieldChanged(field) {
        this.#editFieldsDirty = true;
        setFieldChanged(document.getElementById(field));
        this.#taxSaveRowBtn.disabled = false;
        this.#taxSaveRowBtn.innerHTML = "Save Changes*";
    }
    
    saveEdit() {
        clear_message('tax_message_div');

        // build the current values to update the table, only use the changed values
        let active = this.#taxActive.value;
        let label = this.#taxLabel.value.trim();
        let rate = this.#taxRate.value;
        let glNum = this.#taxGLNum.value.trim();
        let glLabel = this.#taxGLLabel.value;
        let taxItemsData = this.#taxItemsTable.getData();
        let taxItems = {};
        for (let i = 0; i < taxItemsData.length; i++) {
            let item = taxItemsData[i];
            taxItems[item.item] = item;
        }
        let oldItemsData = this.#taxRowBeforeEdit.taxItems;
        let oldItems = {};
        for (let i = 0; i < oldItemsData.length; i++) {
            let item = oldItemsData[i];
            oldItems[item.item] = item;
        }

        let valid = true;
        let message = '';
        // some validation
        if (label.trim() == '') {
            message += "Receipt Label cannot be empty<br/>";
            valid = false;;
        }
        if (rate <= 0 || rate >= 100) {
            message += "Rate must be greather than 0 and less than 100<br/>";
            valid = false;
        }

        if (!valid) {
            show_message(message, 'error', 'tax_message_div');
            return;
        }

        if (glNum == '') {
            glNum = null;
        }

        if (glLabel == '') {
            glLabel = null;
        }

        //console.log("oldItems: " + JSON.stringify(oldItems));
        //console.log("taxItems: " + JSON.stringify(taxItems));

        let update = {};
        update.taxField = this.#taxField;
        if (this.#taxRowBeforeEdit.active != active)
            update.active = active;
        if (this.#taxRowBeforeEdit.label != label)
            update.label = label;
        if (this.#taxRowBeforeEdit.rate != rate)
            update.rate = rate;
        if (this.#taxRowBeforeEdit.glNum != glNum)
            update.glNum = glNum;
        if (this.#taxRowBeforeEdit.glLabel != glLabel)
            update.glLabel = glLabel;

        // now build the taxItems[] and taxItemsDisplay
        let newItems = [];
        let changed = false;
        let sortOrder = 10;
        let taxItemsDisplay = '';
        for (let i = 0; i < this.#taxable.length; i++) {
            let item = this.#taxable[i];
           //console.log("item: " + JSON.stringify(item));
            let newItem = taxItems[item.item].taxable
            let oldItem = '-';
            if (oldItems.hasOwnProperty(item.item)) {
                oldItem = oldItems[item.item].taxable;
            }
            if (newItem != oldItem)
                changed = true;
            if (newItem != '-' && newItem != item.defaultValue) {
                taxItemsDisplay += ',' + item.item + '=' + newItem;
                newItems.push({conid: this.#conid, taxField: this.#taxField, item: item.item, taxable: newItem, sortOrder: sortOrder});
            }
            sortOrder += 10;
        }
        update.taxItems = newItems;
        update.taxItemsDisplay = taxItemsDisplay.substring(1);
        let updates = [];
        updates.push(update);
        //console.log("update: " + JSON.stringify(update));
        this.#taxTable.updateData(updates);
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

function taxCellChanged(cell) {
    tax.cellChanged(cell);
}

function taxItemCellChanged(cell) {
    tax.itemCellChanged(cell);
}
