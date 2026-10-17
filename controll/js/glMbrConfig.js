// globals for Membership General Ledger Setup configuration pane

// glMbrConfig class - functions for finance page configuring membership gl accounts
class glMbrConfig {
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
        this.#glSaveBTN = document.getElementById('mbrSaveBtn');
        this.#glUndoBTN = document.getElementById('mbr-undo');
        this.#glRedoBTN = document.getElementById('mbr-redo');
    }

    open() {
        let script = "scripts/finance_updateGetMbrGLConfig.php";

        let postdata = {
            ajax_request_action: 'getMbrGL',
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
                mbr.draw(data);
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
        this.#glTablePagination = list.length > 25;
        this.#glPaginationDiv = this.#glPaginationDiv = document.getElementById('mbrPaginationDiv');
        this.#glPaginationDiv.innerHTML = '';
        this.#glPaginationDiv.hidden = list.length <= 25;

        this.#glTable = new Tabulator('#mbrConfigTable', {
            data: list,
            layout: "fitDataTable",
            history: true,
            pagination: this.#glTablePagination,
            paginationSize: 25,
            paginationAddRow:"table",
            paginationSizeSelector: [10, 25, 50, 100, 250, true], //enable page size select element with these options
            paginationElement: this.#glPaginationDiv,
            index: "mdId",
            columns: [
                // memId, mdId, memType, memCategory, memAge, label, startdate, enddate, glNum
                {title: "GL Num (Click cell to edit)", field: "glNum", headerSort: false, formatter: "lookup", formatterParams: this.#glEditorList,
                    headerFilter: true, editor: "list", editorParams: {values: this.#glEditorList}, minWidth: 300, },
                {title: "Mem Id", field: "memid", headerWordWrap: true, headerFilter: true },
                {title: "MLD Id", field: "mdId", headerWordWrap: true, visible: false, },
                {title: "Mem Type", field: "memType", headerWordWrap: true, headerFilter: true, },
                {title: "Mem Category", field: "memCategory", headerWordWrap: true, headerFilter: true, },
                {title: "Mem Age", field: "memAge", headerWordWrap: true, headerFilter: true, },
                {title: "Label", field: "label", headerWordWrap: true, formatter: "textarea", headerFilter: true },
                {title: "Start Date", field: "startdate", headerWordWrap: true,
                    headerFilter: "input", headerFilterFunc: dateStringHeaderFilter, headerFilterFuncParams: {field: 'startdate'}, },
                {title: "End Date", field: "enddate", headerWordWrap: true,
                    headerFilter: "input", headerFilterFunc: dateStringHeaderFilter, headerFilterFuncParams: {field: 'startdate'}, },
            ]});

        this.#glTable.on("dataChanged", function (data) {
            _this.dataChanged();
        });
        this.#glTable.on("cellEdited", mbrCellChanged);
    }

    dateHeaderFilter(header, value, filterParams, columnDef, rowData) {
    }

    draw(data) {
        this.#glList = data.mbrGLList;
        this.#glEditorList = data.glEditorList;
        this.#glNumSelect = data.glNumSelect;
        this.#gl = data.gl;
        // show initial gl Config table
        this.drawGLTable(data.mbrGLList);
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

            let script = "scripts/finance_updateGetMbrGLConfig.php";

            let postdata = {
                ajax_request_action: 'updateMbrgl',
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
                    mbr.close();
                    mbr.draw(data);
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

function mbrCellChanged(cell) {
    mbr.cellChanged(cell);
}
