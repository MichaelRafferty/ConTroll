// globals for tax Setup configuration pane

// tax class - functions for finance page sales tax items
class taxConfig {
    #taxTable = null;
    #debug = 0;
    #conid = null;
    #taxSaveBTN = null;
    #taxAddNewBTN = null;

    #dirty = false;

    // constants
    #taxFields = ['tax1', 'tax2', 'tax3', 'tax4', 'tax5'];

    constructor(conid, debug) {
        this.#debug = debug;
        this.#conid = conid;
        this.#taxSaveBTN = document.getElementById('taxSaveBtn');
        this.#taxAddNewBTN = document.getElementById('taxAddNewBtn');
    }

    open() {
        var script = "scripts/finance_updateGetTaxConfig.php";

        var postdata = {
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
        var _this = this;
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
                {title: "Con Id", field: "conid", headerSort:false },
                {title: "taxField", field: "taxField", headerSort:false , },
                {title: "Receipt Label", field: "label", width: 600, editor: 'input', editorParams: { elementAttributes: { maxlength: 64 }}, headerSort:false },
                {title: "Tax Rate (%)", field: "rate", editor: 'number', editorParams: { min: 0, max: 99 }, headerSort:false, },
                {title: "Active", field: "active", editor: 'list', editorParams: { values: ['Y', 'N'], }, headerSort:false, },
                {title: "GL Num", field: "glNum", headerSort: false, editor: "input", editorParams: {maxlength: "16"}, width: 120, },
                {title: "GL Label", field: "glLabel", headerSort: false, editor: "input", editorParams: {maxlength: "64"}, width: 600, },
                {title: "Last Update", field: "lastUpdate", headerSort:false, },
                {title: "Updated By", field: "updatedBy", headerSort:false , },
            ]});

        this.#taxTable.on("dataChanged", function (data) {
            _this.dataChanged();
        });
        this.#taxTable.on("cellEdited", cellChanged);
        this.#taxAddNewBTN.disabled = data['taxList'].length >= 5;
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
        var _this = this;

        if (this.#taxTable != null) {
            this.#taxSaveBTN.innerHTML = "Saving...";
            this.#taxSaveBTN.disabled = true;

            var script = "scripts/finance_updateGetTaxConfig.php";

            var postdata = {
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
