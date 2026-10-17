// globals for Exhibits General Ledger Setup configuration pane

// glExhConfig class - functions for finance page configuring exhibitor gl accounts
class glExhConfig {
    #glTable = null;
    #glTablePagination = false;
    #debug = 0;
    #conid = null;
    #glSaveBTN = null;
    #glUndoBTN = null;
    #glRedoBTN = null;
    #glList = null;
    #gl = null;
    #glEditorList = null;
    #glNumSelect = null;
    #glPaginationDiv = null;

    #dirty = false;

    constructor(conid, debug) {
        this.#debug = debug;
        this.#conid = conid;
        this.#glSaveBTN = document.getElementById('exhSaveBtn');
        this.#glUndoBTN = document.getElementById('exh-undo');
        this.#glRedoBTN = document.getElementById('exh-redo');
    }

    open() {
        let script = "scripts/finance_updateGetExhGLConfig.php";

        let postdata = {
            ajax_request_action: 'getExhGL',
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
                exh.draw(data);
                show_message(data['success'], 'success');
            },
            error: function (jqXHR, textStatus, errorThrown) {
                showError("ERROR in " + script + ": " + textStatus, jqXHR);
                return false;
            }
        });
    }

    drawGLTable(list) {
        let _this = this;

        this.#glTable = new Tabulator('#exhConfigTable', {
            data: list,
            layout: "fitDataTable",
            history: true,
            columns: [
                // tableName, fieldName, glNum, id, title
                {title: "GL Num (Click cell to edit)", field: "glNum", headerSort: false, formatter: "lookup", formatterParams: this.#glEditorList,
                    headerFilter: true, editor: "list", editorParams: {values: this.#glEditorList}, minWidth: 300, },
                {title: "Table Row Id", field: "id", headerWordWrap: true, headerFilter: true, visible: true, },
                {title: "Table Name", field: "tableName", headerWordWrap: true, headerFilter: true, },
                {title: "Field Name", field: "fieldName", headerWordWrap: true, headerFilter: true, },
                {title: "Exhibits Area", field: "shortname", headerWordWrap: true, headerFilter: true },
                {title: "Item", field: "title", headerWordWrap: true, headerFilter: true },
                {title: "Label", field: "label", headerWordWrap: true, headerFilter: true },
            ]});

        this.#glTable.on("dataChanged", function (data) {
            _this.dataChanged();
        });
        this.#glTable.on("cellEdited", exhCellChanged);
    }

    dateHeaderFilter(header, value, filterParams, columnDef, rowData) {
    }

    draw(data) {
        this.#glList = data.exhGLList;
        this.#glEditorList = data.glEditorList;
        this.#glNumSelect = data.glNumSelect;
        this.#gl = data.gl;
        // show initial gl Config table
        this.drawGLTable(data.exhGLList);
    }

    cellChanged(cell) {
        this.#dirty = true;
        cellChanged(cell);
        this.checkUndoRedo();
    }

    undo() {
        if (this.#glTable != null) {
            this.#glTable.undo();

            if (this.checkUndoRedo() <= 0) {
                this.#dirty = false;
                this.#glSaveBTN.innerHTML = "Save Changes";
                this.#glSaveBTN.disabled = true;
            }
        }
    };

    redo() {
        if (this.#glTable != null) {
            this.#glTable.redo();

            if (this.checkUndoRedo() > 0) {
                this.#dirty = true;
                this.#glSaveBTN.innerHTML = "Save Changes*";
                this.#glSaveBTN.disabled = false;
            }
        }
    };

    // set undo / redo status
    checkUndoRedo() {
        let undosize = this.#glTable.getHistoryUndoSize();
        this.#glUndoBTN.disabled = undosize <= 0;
        this.#glRedoBTN.disabled = this.#glTable.getHistoryRedoSize() <= 0;
        return undosize;
    }

    close() {
        if (this.#glTable) {
            this.#glTable.off("dataChanged");
            this.#glTable.off("cellEdited");
            this.#glTable.destroy();
            this.#glTable = null;
            this.#glSaveBTN.innerHTML = "Save Changes";
            this.#glSaveBTN.disabled = true;
        }
    }

    dataChanged() {
        //data - the updated table data
        this.#glSaveBTN.innerHTML = "Save Changes*";
        this.#glSaveBTN.disabled = false;
        this.#dirty = true;
        this.checkUndoRedo();
    };

    // save the table back to the database
    save() {
        let _this = this;

        if (this.#glTable != null) {
            this.#glSaveBTN.innerHTML = "Saving...";
            this.#glSaveBTN.disabled = true;

            let script = "scripts/finance_updateGetExhGLConfig.php";

            let postdata = {
                ajax_request_action: 'updateExhgl',
                tabledata: JSON.stringify(this.#glTable.getData()),
                tablename: 'memListDates ',
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
                        _this.#glSaveBTN.disabled = false;
                        _this.#glSaveBTN.innerHTML = "Save Changes*";
                        return false;
                    }
                    checkRefresh(data);
                    exh.close();
                    exh.draw(data);
                    show_message(data['success'], 'success');
                },
                error: function (jqXHR, textStatus, errorThrown) {
                    showError("ERROR in " + script + ": " + textStatus, jqXHR);
                    _this.dataChanged();
                    _this.#glSaveBTN.disabled = false;
                    _this.#glSaveBTN.innerHTML = "Save Changes*";
                    return false;
                }
            });
        }
    }

    // download the table
    downloadGLs(format) {
        if (this.#glTable == null)
            return;

        let filename = 'gl'
        let tabledata = JSON.stringify(this.#glTable.getData("active"));
        let fieldList = ['glNum', 'glLabel', 'description', 'sortOrder', 'active', 'createDate', 'updateDate', 'updateBy'];
        downloadFilePost(format, filename, tabledata, null, fieldList);
    }
};

function exhCellChanged(cell) {
    exh.cellChanged(cell);
}
