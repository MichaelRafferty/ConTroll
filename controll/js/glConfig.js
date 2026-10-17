// globals for gl Setup configuration pane

// gl class - functions for finance page configuring gl accounts
class glConfig {
    #glTable = null;
    #glTablePagination = false;
    #debug = 0;
    #conid = null;
    #glSaveBTN = null;
    #glAddNewBTN = null;
    #glUndoBTN = null;
    #glRedoBTN = null;
    #glList = null;
    #glPaginationDiv = null;

    // edit modal fields
    #glEditModal = null;
    #glLabel = null;
    #glGLNum = null;
    #glGLLabel = null;
    #glItemsDiv = null;
    #glActive = null;
    #editFieldsDirty = false;
    #glSaveRowBtn = null;

    #dirty = false;

    // constants
    #enumYN = ['Y', 'N'];

    constructor(conid, debug) {
        this.#debug = debug;
        this.#conid = conid;
        this.#glSaveBTN = document.getElementById('glSaveBtn');
        this.#glAddNewBTN = document.getElementById('glAddNewBtn');
        this.#glUndoBTN = document.getElementById('gl-undo');
        this.#glRedoBTN = document.getElementById('gl-redo');

        let id = document.getElementById('editGL');
        if (id) {
            this.#glEditModal = new bootstrap.Modal(id, {focus: true, backdrop: 'static'});
            this.#glLabel = document.getElementById('glLabel');
            this.#glActive = document.getElementById('glActive');
            this.#glGLNum = document.getElementById('glGLNum');
            this.#glGLLabel = document.getElementById('glGLLabel');
            this.#glItemsDiv = document.getElementById('glItemsDiv');
            this.#glSaveRowBtn = document.getElementById('tax-saveRow-btn');
        }
    }

    open() {
        let script = "scripts/finance_updateGetGLConfig.php";

        let postdata = {
            ajax_request_action: 'getGL',
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
                gl.draw(data);
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
        this.#glPaginationDiv = this.#glPaginationDiv = document.getElementById('glPaginationDiv');
        this.#glPaginationDiv.innerHTML = '';
        this.#glPaginationDiv.hidden = list.length <= 25;

        this.#glTable = new Tabulator('#glConfigTable', {
            data: list,
            layout: "fitDataTable",
            history: true,
            pagination: this.#glTablePagination,
            paginationSize: 25,
            paginationAddRow:"table",
            paginationElement: this.#glPaginationDiv,
            paginationSizeSelector: [10, 25, 50, 100, 250, true], //enable page size select element with these options
            index: "glNum",
            columns: [
                { title: "Delete", field: "uses", formatter: deleteicon, hozAlign: "center", headerSort: false,
                    cellClick: function (e, cell) {
                        deleterow(e, cell.getRow());
                    }
                },
                {title: "Active", field: "active", editor: 'list', editorParams: { values: this.#enumYN, },
                    headerSort:false, headerFilter: true, headerFilterParams: { values: this.#enumYN, }, },
                {title: "Sort Order", field: "sortOrder", headerSort:true, editor: "number", width: 100, hozAlign: "right",
                    headerWordWrap: true, headerFilter: true, headerFilterFunc:numberHeaderFilter,},
                {title: "GL Num", field: "glNum", headerSort: false,
                    headerFilter: true, editor: "input", editorParams: {maxlength: "16"}, width: 120, },
                {title: "GL Label", field: "glLabel", headerSort: true,
                    headerFilter: true, editor: "input", editorParams: {maxlength: "64"}, width: 450, },
                {title: "Description", field: "description", editor: "textarea", formatter: "textarea", width: 500, },
                {title: "Last Update", field: "updateDate", headerSort:false, },
                {title: "Updated By", field: "updateBy", headerSort:false , headerWordWrap: true, },
                { field: "to_delete", visible: false, },
                { field: "keyfield", visible: false,},
            ]});

        this.#glTable.on("dataChanged", function (data) {
            _this.dataChanged();
        });
        this.#glTable.on("cellEdited", glCellChanged);
    }

    draw(data) {
        this.#glList = data.glList;
        // show initial gl Config table
        this.drawGLTable(data.glList);
    }

    cellChanged(cell) {
        this.#dirty = true;
        cellChanged(cell);
        this.checkUndoRedo();
    }

    addNew() {
        let _this = this;

        this.#glTable.clearFilter(true);
        this.#glTable.addRow({
            active: 'Y',
            sortOrder: 0,
        }, false).then(function (row) {
            if (_this.#glTablePagination) {
                row.getTable().setPageToRow(row).then(function () {
                    setCellChanged(row.getCell("active"));
                    setCellChanged(row.getCell("sortOrder"));
                    setCellChanged(row.getCell("glNum"));
                    setCellChanged(row.getCell("glLabel"));
                    setCellChanged(row.getCell("description"));
                    _this.checkUndoRedo();
                });
            } else {
                setCellChanged(row.getCell("active"));
                setCellChanged(row.getCell("sortOrder"));
                setCellChanged(row.getCell("glNum"));
                setCellChanged(row.getCell("glLabel"));
                setCellChanged(row.getCell("description"));
                this.checkUndoRedo();
            }
        });
    };

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

    itemCellChanged(cell) {
        this.#editFieldsDirty = true;
        cellChanged(cell);
        this.#glSaveRowBtn.disabled = false;
        this.#glSaveRowBtn.innerHTML = "Save Changes*";
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

            let script = "scripts/finance_updateGetGLConfig.php";

            let postdata = {
                ajax_request_action: 'updategl',
                tabledata: JSON.stringify(this.#glTable.getData()),
                tablename: 'glList',
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
                    gl.close();
                    gl.draw(data);
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

function glCellChanged(cell) {
    gl.cellChanged(cell);
}
