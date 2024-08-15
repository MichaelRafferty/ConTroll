//import { TabulatorFull as Tabulator } from 'tabulator-tables';

class memsetup {
    #current_proposed = ' ';
    #next_proposed = ' ';
    #message_div = null;
    #memsetup_pane = null;
    // memTypes
    #memtypetable = null;
    #memtype_dirty = false;
    #memtype_savebtn = null;
    #memtype_undobtn = null;
    #memtype_redobtn = null;    
    // memCategories
    #categorytable = null;
    #category_dirty = false;
    #category_savebtn = null;
    #category_undobtn = null;
    #category_redobtn = null;
    // current agelist
    #curagetable = null;
    #curage_dirty = false;
    #curage_savebtn = null;
    #curage_undobtn = null;
    #curage_redobtn = null;
    // next ageList
    #nextagetable = null;
    #nextage_dirty = false;
    #nextage_savebtn = null;
    #nextage_undobtn = null;
    #nextage_redobtn = null;
    #current_conid = null;
    #next_conid = null;

    constructor() {
        this.#message_div = document.getElementById('test');
        this.#memsetup_pane = document.getElementById('memconfig-pane');
    };

    memtype_dataChanged(data) {
        //data - the updated table data
        if (!this.#memtype_dirty) {
            this.#memtype_savebtn.innerHTML = "Save Changes*";
            this.#memtype_savebtn.disabled = false;
            this.#memtype_dirty = true;
        }
        this.checkTypeUndoRedo();
    };

    memtype_rowMoved(row) {
        this.#memtype_savebtn.innerHTML = "Save Changes*";
        this.#memtype_savebtn.disabled = false;
        this.#memtype_dirty = true;
        this.checkTypeUndoRedo();
    }

    category_rowMoved(row) {
        this.#category_savebtn.innerHTML = "Save Changes*";
        this.#category_savebtn.disabled = false;
        this.#category_dirty = true;
        this.checkCatUndoRedo();
    }

    category_dataChanged(data) {
        //data - the updated table data
        if (!this.#category_dirty) {
            this.#category_savebtn.innerHTML = "Save Changes*";
            this.#category_savebtn.disabled = false;
            this.#category_dirty = true;
        }
        this.checkCatUndoRedo();
    };

    curage_dataChanged(data) {
        //data - the updated table data
        if (!this.#curage_dirty) {
            this.#curage_savebtn.innerHTML = "Save Changes*";
            this.#curage_savebtn.disabled = false;
            this.#curage_dirty = true;
        }
        this.checkCurageUndoRedo();
    };

    curage_rowMoved(row) {
        this.#curage_savebtn.innerHTML = "Save Changes*";
        this.#curage_savebtn.disabled = false;
        this.#curage_dirty = true;
        this.checkCurageUndoRedo();
    }

    nextage_dataChanged(data) {
        //data - the updated table data
        if (!this.#nextage_dirty) {
            this.#nextage_savebtn.innerHTML = "Save Changes*";
            this.#nextage_savebtn.disabled = false;
            this.#nextage_dirty = true;
        }
        this.checkNextageUndoRedo();
    };

    nextage_rowMoved(row) {
        this.#nextage_savebtn.innerHTML = "Save Changes*";
        this.#nextage_savebtn.disabled = false;
        this.#nextage_dirty = true;
        this.checkNextageUndoRedo();
    }

    draw(data, textStatus, jhXHR) {
        var _this = this;
        //console.log('in draw');
        //console.log(data);
        this.#current_proposed = ' ';
        this.#next_proposed = ' ';

        if (data['current-agelist-type'] == 'proposed')
            this.#current_proposed = ' Proposed ';

        if (data['next-agelist-type'] == 'proposed')
            this.#next_proposed = ' Proposed ';

        this.current_conid = data['current_id'];
        this.next_conid = data['next_id'];

        var html = `<h4><strong>Membership Setup Tables:</strong></h4>
<div class="container-fluid">
<div class="row">
<div class="col-sm-auto p-2 border border-2 border-primary">
<h5>Membership Types</h5>
<div id="types-div"></div>
<div id="types-buttons">  
    <button id="types-undo" type="button" class="btn btn-secondary btn-sm" onclick="mem.undoTypes(); return false;" disabled>Undo</button>
    <button id="types-redo" type="button" class="btn btn-secondary btn-sm" onclick="mem.redoTypes(); return false;" disabled>Redo</button>
    <button id="types-addrow" type="button" class="btn btn-secondary btn-sm" onclick="mem.addrowTypes(); return false;">Add New</button>
    <button id="types-save" type="button" class="btn btn-primary btn-sm"  onclick="mem.saveTypes(); return false;" disabled>Save Changes</button>
</div>
</div>
<div class="col-sm-auto p-2 border border-2 border-primary">
<h5>Membership Categories</h5>
<div id="cat-div"></div>
<div id="cat-buttons">  
    <button id="cat-undo" type="button" class="btn btn-secondary btn-sm" onclick="mem.undoCat(); return false;" disabled>Undo</button>
    <button id="cat-redo" type="button" class="btn btn-secondary btn-sm" onclick="mem.redoCat(); return false;" disabled>Redo</button>
    <button id="cat-addrow" type="button" class="btn btn-secondary btn-sm" onclick="mem.addrowCat(); return false;">Add New</button>
    <button id="cat-save" type="button" class="btn btn-primary btn-sm"  onclick="mem.saveCat(); return false;" disabled>Save Changes</button>
</div>
</div>
</div>
<div class="row">
<div class="col-sm-auto p-2 border border-2 border-primary">
<h5>Current Convention Age List (` + this.current_conid + `)</h5>
<div id="curage-div"></div>
<div id="curage-buttons">  
    <button id="curage-undo" type="button" class="btn btn-secondary btn-sm" onclick="mem.undoCurAge(); return false;" disabled>Undo</button>
    <button id="curage-redo" type="button" class="btn btn-secondary btn-sm" onclick="mem.redoCurAge(); return false;" disabled>Redo</button>
    <button id="curage-addrow" type="button" class="btn btn-secondary btn-sm" onclick="mem.addrowCurAge(); return false;">Add New</button>
    <button id="curage-save" type="button" class="btn btn-primary btn-sm"  onclick="mem.saveCurAge(); return false;" disabled>Save Changes</button>
</div>
</div>
<div class="col-sm-auto p-2 border border-2 border-primary">
<h5>Next Convention Age List (` + this.next_conid + `)</h5>
<div id="nextage-div"></div>
<div id="nextage-buttons">  
    <button id="nextage-undo" type="button" class="btn btn-secondary btn-sm" onclick="mem.undoNextAge(); return false;" disabled>Undo</button>
    <button id="nextage-redo" type="button" class="btn btn-secondary btn-sm" onclick="mem.redoNextAge(); return false;" disabled>Redo</button>
    <button id="nextage-addrow" type="button" class="btn btn-secondary btn-sm" onclick="mem.addrowNextAge(); return false;">Add New</button>
    <button id="nextage-save" type="button" class="btn btn-primary btn-sm"  onclick="mem.saveNextAge(); return false;" disabled>Save Changes</button>
</div>
</div>
</div>
`;

        this.#memsetup_pane.innerHTML = html;
        this.#message_div.innerHTML = '';
        this.#memtype_savebtn = document.getElementById('types-save');
        this.#memtype_undobtn = document.getElementById('types-undo');
        this.#memtype_redobtn = document.getElementById('types-redo');

        this.#category_savebtn = document.getElementById('cat-save');
        this.#category_undobtn = document.getElementById('cat-undo');
        this.#category_redobtn = document.getElementById('cat-redo');

        this.#curage_savebtn = document.getElementById('curage-save');
        this.#curage_undobtn = document.getElementById('curage-undo');
        this.#curage_redobtn = document.getElementById('curage-redo');

        this.#nextage_savebtn = document.getElementById('nextage-save');
        this.#nextage_undobtn = document.getElementById('nextage-undo');
        this.#nextage_redobtn = document.getElementById('nextage-redo');

        this.draw_memtype(data, textStatus, jhXHR);
        this.draw_memcat(data, textStatus, jhXHR);
        this.draw_curage(data, textStatus, jhXHR);
        this.draw_nextage(data, textStatus, jhXHR);
    };

    draw_memtype(data, textStatus, jhXHR) {
        var _this = this;

        this.#memtype_dirty = false;

        if (this.#memtypetable != null) {
            this.#memtypetable.off("dataChanged");
            this.#memtypetable.off("rowMoved")
            this.#memtypetable.off("cellEdited");
            this.#memtypetable.destroy();
        }

        this.#memtypetable = null;
        // memtype table
        this.#memtypetable = new Tabulator('#types-div', {
            maxHeight: "300px",
            movableRows: true,
            history: true,
            data: data['memtypes'],
            layout: "fitDataTable",
            columns: [
                { rowHandle: true, formatter: "handle", frozen: true, width: 30, minWidth: 30, maxWidth: 30, headerSort: false },
                { field: "memtypekey", visible: false },
                {
                    title: "Type", field: "memType", headerSort: true, width: 150, editable: reqEditable,
                    editor: "input", editorParams: { elementAttributes: { maxlength: "16" } }, validator: [ "unique", "required" ]
                },
                {
                    title: "Notes", field: "notes", headerSort: true, width: 350, editable: reqEditable,
                    editor: "input", editorParams: { elementAttributes: { maxlength: "1024" } },
                },
                {
                    title: "Active", field: "active", headerSort: true,  editable: reqEditable,
                    editor: "list", editorParams: { values: ["Y", "N"], }, validator: "required"
                },
                { title: "Sort Order", field: "sortorder", headerSort: true, visible: false },
                {
                    title: "Delete", field: "uses", formatter: deleteicon, hozAlign: "center", headerSort: false,
                    cellClick: function (e, cell) {
                        deleterow(e, cell.getRow());
                    }
                },
                { field: "to_delete", visible: false, }
            ]
        });

        this.#memtypetable.on("dataChanged", function (data) {
            _this.memtype_dataChanged(data);
        });
        this.#memtypetable.on("rowMoved", function (row) {
            _this.memtype_rowMoved(row)
        });
        this.#memtypetable.on("cellEdited", cellChanged);
    };

    draw_memcat(data, textStatus, jhXHR) {
        var _this = this;

        this.#category_dirty = false;

        if (this.#categorytable != null) {
            this.#categorytable.off("dataChanged");
            this.#categorytable.off("rowMoved")
            this.#categorytable.off("cellEdited");
            this.#categorytable.destroy();
        }

        this.#categorytable = null;
        // category table
        this.#categorytable = new Tabulator('#cat-div', {
            maxHeight: "300px",
            history: true,
            movableRows: true,
            data: data['categories'],
            layout: "fitDataTable",
            columns: [
                { rowHandle: true, formatter: "handle", frozen: true, width: 30, minWidth: 30, maxWidth: 30, headerSort: false },
                { field: "memcatkey", visible: false },
                {
                    title: "Category", field: "memCategory", width: 150, headerSort: true, editable: reqEditable,
                    editor: "input", editorParams: { elementAttributes: { maxlength: "16" } }, validator: [ "unique", "required" ]
                },
                {
                    title: "Notes", field: "notes", headerSort: true, width: 350, editable: reqEditable,
                    editor: "input", editorParams: { elementAttributes: { maxlength: "1024" } },
                },
                {
                    title: "Only One", field: "onlyOne", headerWordWrap: true, headerSort: true, editable: reqEditable,
                    editor: "list", editorParams: { values: ["Y", "N"], }, width: 70, validator: "required" },
                {
                    title: "Stand Alone", field: "standAlone", headerWordWrap: true, headerSort: true, editable: reqEditable,
                    editor: "list", editorParams: { values: ["Y", "N"], }, width: 75, validator: "required" },
                {
                    title: "Variable Price", field: "variablePrice", headerWordWrap: true, headerSort: true, editable: reqEditable,
                    editor: "list", editorParams: { values: ["Y", "N"], }, width: 85, validator: "required"
                },
                {
                    title: "Badge Label", field: "badgeLabel", width: 150, headerSort: true, editable: reqEditable,
                    editor: "input", editorParams: { elementAttributes: { maxlength: "16" } }, validator: [ "required" ]
                },
                {
                    title: "Active", field: "active", headerSort: true, editable: reqEditable,
                    editor: "list", editorParams: { values: ["Y", "N"], }, validator: "required"
                },
                { title: "Sort Order", field: "sortorder", headerSort: true, visible: false },
                {
                    title: "Delete", field: "uses", formatter: deleteicon, hozAlign: "center", headerSort: false,
                    cellClick: function (e, cell) {
                        deleterow(e, cell.getRow());
                    }
                },
                { field: "to_delete", visible: false, }
            ],
        });

        this.#categorytable.on("dataChanged", function (data) {
            _this.category_dataChanged(data);
        });
        this.#categorytable.on("rowMoved", function (row) {
            _this.category_rowMoved(row)
        });
        this.#categorytable.on("cellEdited", cellChanged);

    }

    draw_curage(data, textStatus, jhXHR) {
        var _this = this;

        this.#curage_dirty = false;

        if (this.#curagetable != null) {
            this.#curagetable.off("dataChanged");
            this.#curagetable.off("rowMoved")
            this.#curagetable.off("cellEdited");
            this.#curagetable.destroy();
        }

        this.#curagetable = null;
        // current agelist table
        this.#curagetable = new Tabulator('#curage-div', {
            maxHeight: "300px",
            history: true,
            movableRows: true,
            data: data['current_agelist'],
            layout: "fitDataTable",
            columns: [
                { rowHandle: true, formatter: "handle", frozen: true, width: 30, minWidth: 30, maxWidth: 30, headerSort: false },
                { field: "agekey", visible: false },
                { title: "ConID", field: "conid", visible: false },
                { title: "Age Type", field: "ageType", width: 140, headerSort: true, editor: "input", editorParams: { elementAttributes: { maxlength: "16" } }, validator: "required" },
                { title: "Label", field: "label", headerSort: false, width: 200, editor: "input", editorParams: { elementAttributes: { maxlength: "64" } }, validator: "required" },
                { title: "shortname", field: "shortname", headerSort: false, width: 140, editor: "input", editorParams: { elementAttributes: { maxlength: "16" } }, validator: "required" },
                { title: "Badge Flag", field: "badgeFlag", headerSort: true, width: 140, editor: "input", editorParams: { elementAttributes: { maxlength: "16" } }, },
                { title: "Sort Order", field: "sortorder", headerSort: true, visible: false },
                {
                    title: "Delete", field: "uses", formatter: deleteicon, hozAlign: "center", headerSort: false,
                    cellClick: function (e, cell) {
                        deleterow(e, cell.getRow());
                    }
                },
                { field: "to_delete", visible: false, }
            ],
        });

        this.#curagetable.on("dataChanged", function (data) {
            _this.curage_dataChanged(data);
        });
        this.#curagetable.on("rowMoved", function (row) {
            _this.curage_rowMoved(row)
        });
        this.#curagetable.on("cellEdited", cellChanged);
    }

    draw_nextage(data, textStatus, jhXHR) {
        var _this = this;

        this.#nextage_dirty = false;

        if (this.#nextagetable != null) {
            this.#nextagetable.off("dataChanged");
            this.#nextagetable.off("rowMoved")
            this.#nextagetable.off("cellEdited");
            this.#nextagetable.destroy();
        }

        this.#nextagetable = null;
        // next  agelist table
        this.#nextagetable = new Tabulator('#nextage-div', {
            maxHeight: "300px",
            history: true,
            movableRows: true,
            data: data['next_agelist'],
            layout: "fitDataTable",
            columns: [
                { rowHandle: true, formatter: "handle", frozen: true, width: 30, minWidth: 30, maxWidth: 30, headerSort: false },
                { field: "agekey", visible: false },
                { title: "ConID", field: "conid", visible: false },
                { title: "Age Type", field: "ageType", width: 140, headerSort: true, editor: "input", editorParams: { elementAttributes: { maxlength: "16" } } },
                { title: "Label", field: "label", headerSort: false, width: 200, editor: "input", editorParams: { elementAttributes: { maxlength: "64" } } },
                { title: "shortname", field: "shortname", headerSort: false, width: 140, editor: "input", editorParams: { elementAttributes: { maxlength: "16" } } },
                { title: "Sort Order", field: "sortorder", headerSort: true, visible: false },
                {
                    title: "Delete", field: "uses", formatter: deleteicon, hozAlign: "center", headerSort: false,
                    cellClick: function (e, cell) {
                        deleterow(e, cell.getRow());
                    }
                },
                { field: "to_delete", visible: false, }
            ],
        });

        this.#nextagetable.on("dataChanged", function (data) {
            _this.nextage_dataChanged(data);
        });
        this.#nextagetable.on("rowMoved", function (row) {
            _this.nextage_rowMoved(row)
        });
        this.#nextagetable.on("cellEdited", cellChanged);
    }

    open() {
        var _this = this;
        var script = "scripts/getMemberSetupData.php";
        $.ajax({
            url: script,
            method: 'POST',
            data: { type: 'all', },
            success: function (data, textStatus, jhXHR) {
                _this.draw(data, textStatus, jhXHR);
            },
            error: function (jqXHR, textStatus, errorThrown) {
                showError("ERROR in " + script + ": " + textStatus, jqXHR);
                return false;
            }
        });
    };

    close() {
        if (this.#curagetable != null) {
            this.#curagetable.off("dataChanged");
            this.#curagetable.off("rowMoved")
            this.#curagetable.off("cellEdited");
            this.#curagetable.destroy();
            this.#curagetable = null;
        }

        if (this.#categorytable != null) {
            this.#categorytable.off("dataChanged");
            this.#categorytable.off("rowMoved")
            this.#categorytable.off("cellEdited");
            this.#categorytable.destroy();
            this.#categorytable = null;
        }

        if (this.#memtypetable != null) {
            this.#memtypetable.off("dataChanged");
            this.#memtypetable.off("rowMoved")
            this.#memtypetable.off("cellEdited");
            this.#memtypetable.destroy();
            this.#memtypetable = null;
        }

        if (this.#nextagetable != null) {
            this.#nextagetable.off("dataChanged");
            this.#nextagetable.off("rowMoved")
            this.#nextagetable.off("cellEdited");
            this.#nextagetable.destroy();
            this.#nextagetable = null;
        }

               
        this.#memsetup_pane.innerHTML = '';
        this.#memtype_dirty = false;
        this.#category_dirty = false;
        this.#curage_dirty = false;
        this.#nextage_dirty = false;
    };

    undoTypes() {
        if (this.#memtypetable != null) {
            this.#memtypetable.undo();

            if (this.checkTypeUndoRedo() <= 0) {
                this.#memtype_dirty = false;
                this.#memtype_savebtn.innerHTML = "Save Changes";
                this.#memtype_savebtn.disabled = true;
            }
        }
    };

    redoTypes() {
        if (this.#memtypetable != null) {
            this.#memtypetable.redo();
            
            if (this.checkTypeUndoRedo() > 0) {
                this.#memtype_dirty = true;
                this.#memtype_savebtn.innerHTML = "Save Changes*";
                this.#memtype_savebtn.disabled = false;
            }
        }
    };

    addrowTypes() {
        var _this = this;
        this.#memtypetable.addRow({memType: 'new-row', active: 'Y', sortorder: 99, uses: 0}, false).then(function (row) {
            row.getTable().scrollToRow(row);
            _this.checkTypeUndoRedo();
        });
    }

    // set undo / redo status for mem type buttons
    checkTypeUndoRedo() {
        var undosize = this.#memtypetable.getHistoryUndoSize();
        this.#memtype_undobtn.disabled = undosize <= 0;
        this.#memtype_redobtn.disabled = this.#memtypetable.getHistoryRedoSize() <= 0;
        return undosize;
    }
    saveTypesComplete(data, textStatus, jhXHR) {
        var _this = this;

        if ('error' in data && data['error'] != '') {
            showError(data['error']);
            this.#memtype_savebtn.innerHTML = "Save Changes*";
            this.#memtype_savebtn.disabled = false;
            return false;
        } else {
            showError(data['success']);    
        }
        this.#memtype_savebtn.innerHTML = "Save Changes";
        var script = "scripts/getMemberSetupData.php";
        $.ajax({
            url: script,
            method: 'POST',
            data: { type: 'memType', },
            success: function (data, textStatus, jhXHR) {
                _this.draw_memtype(data, textStatus, jhXHR);
            },
            error: function (jqXHR, textStatus, errorThrown) {
                showError("ERROR in " + script + ": " + textStatus, jqXHR);
                return false;
            }
        });
    }

    saveTypes() {
        var _this = this;

        if (this.#memtypetable != null) {
            var invalids = this.#memtypetable.validate();
            if (invalids !== true) {
                console.log(invalids);
                alert("MemType Table does not pass validation, please check for empty cells or cells in red");
                return false;
            }
            this.#memtype_savebtn.innerHTML = "Saving...";
            this.#memtype_savebtn.disabled = true;

            var script = "scripts/updateMemberSetupData.php";

            var postdata = {
                ajax_request_action: 'memtype',
                tabledata: JSON.stringify(this.#memtypetable.getData()),
                tablename: "memTypes",
                indexcol: "memtypekey"
            };
            //console.log(postdata);
            $.ajax({
                url: script,
                method: 'POST',
                data: postdata,
                success: function (data, textStatus, jhXHR) {
                    _this.saveTypesComplete(data, textStatus, jhXHR);
                },
                error: function (jqXHR, textStatus, errorThrown) {
                    showError("ERROR in " + script + ": " + textStatus, jqXHR);
                    return false;
                }
            });
        }
    };

    undoCat() {
        if (this.#categorytable != null) {
            this.#categorytable.undo();

            if (this.checkCatUndoRedo() <= 0) {
                this.#category_dirty = false;
                this.#category_savebtn.innerHTML = "Save Changes";
                this.#category_savebtn.disabled = true;
            }
        }
    };

    redoCat() {
        if (this.#categorytable != null) {
            this.#categorytable.redo();
            
            if (this.checkCatUndoRedo() > 0) {
                this.#category_dirty = true;
                this.#category_savebtn.innerHTML = "Save Changes*";
                this.#category_savebtn.disabled = false;
            }
        }
    };

    addrowCat() {
        var _this = this;

        this.#categorytable.addRow({memCategory: 'new-row', onlyOne: 'Y', standAlone: 'N', variablePrice: 'N', badgeLabel: 'X', active: 'Y', sortorder: 99, uses: 0}, false).then(function (row) {
            row.getTable().scrollToRow(row);
            _this.checkCatUndoRedo();
        });
    }
    
    // set undo / redo status for category buttons
    checkCatUndoRedo() {
        var undosize = this.#categorytable.getHistoryUndoSize();
        this.#category_undobtn.disabled = undosize <= 0;
        this.#category_redobtn.disabled = this.#categorytable.getHistoryRedoSize() <= 0;
        return undosize;
    }
    saveCatComplete(data, textStatus, jhXHR) {
        var _this = this;

        if ('error' in data && data['error'] != '') {
            showError(data['error']);
            this.#category_savebtn.innerHTML = "Save Changes*";
            this.#category_savebtn.disabled = false;
            return false;
        } else {
            showError(data['success']);
        }
        this.#category_savebtn.innerHTML = "Save Changes";
        var script = "scripts/getMemberSetupData.php";
        $.ajax({
            url: script,
            method: 'POST',
            data: { type: 'memCat', },
            success: function (data, textStatus, jhXHR) {
                _this.draw_memcat(data, textStatus, jhXHR);
            },
            error: function (jqXHR, textStatus, errorThrown) {
                showError("ERROR in " + script + ": " + textStatus, jqXHR);
                return false;
            }
        });
    }

    saveCat() {
        var _this = this;

        if (this.#categorytable != null) {
            var invalids = this.#categorytable.validate();
            if (invalids !== true) {
                console.log(invalids);
                alert("Category Table does not pass validation, please check for empty cells or cells in red");
                return false;
            }
            this.#category_savebtn.innerHTML = "Saving...";
            this.#category_savebtn.disabled = true;

            var script = "scripts/updateMemberSetupData.php";

            var postdata = {
                ajax_request_action: 'category',
                tabledata: JSON.stringify(this.#categorytable.getData()),
                tablename: "memCategories",
                indexcol: "memcatkey"
            };
            //console.log(postdata);
            $.ajax({
                url: script,
                method: 'POST',
                data: postdata,
                success: function (data, textStatus, jhXHR) {
                    _this.saveCatComplete(data, textStatus, jhXHR);
                },
                error: function (jqXHR, textStatus, errorThrown) {
                    showError("ERROR in " + script + ": " + textStatus, jqXHR);
                    return false;
                }
            });
        }
    };

    undoCurAge() {
        if (this.#curagetable != null) {
            this.#curagetable.undo();
            
            if (this.checkCurageUndoRedo() <= 0) {
                this.#curage_dirty = false;
                this.#curage_savebtn.innerHTML = "Save Changes";
                this.#curage_savebtn.disabled = true;
            }
        }
    };

    redoCurAge() {
        if (this.#curagetable != null) {
            this.#curagetable.redo();
            
            if (this.checkCurageUndoRedo() > 0) {
                this.#curage_undobtn.disabled = false;
                this.#curage_dirty = true;
                this.#curage_savebtn.innerHTML = "Save Changes*";
                this.#curage_savebtn.disabled = false;
            }
        }
    };

    addrowCurAge() {
        var _this = this;

        this.#curagetable.addRow({conid: this.#current_conid, ageType: 'new-row', label: 'new-label', shortname: 'new-shortname', sortorder: 99, uses: 0}, false).then(function (row) {
            row.getTable().scrollToRow(row);
            _this.checkCurageUndoRedo();
        });
    }

    // set undo / redo status for curent con ageList buttons
    checkCurageUndoRedo() {
        var undosize = this.#curagetable.getHistoryUndoSize();
        this.#curage_undobtn.disabled = undosize <= 0;
        this.#curage_redobtn.disabled = this.#curagetable.getHistoryRedoSize() <= 0;
        return undosize;
    }

    saveCurAgeComplete(data, textStatus, jhXHR) {
        var _this = this;

        if ('error' in data && data['error'] != '') {
            showError(data['error']);
            this.#curage_savebtn.innerHTML = "Save Changes*";
            this.#curage_savebtn.disabled = false;
            return false;
        } else {
            showError(data['success']);
        }
        this.#curage_savebtn.innerHTML = "Save Changes";
        var script = "scripts/getMemberSetupData.php";
        $.ajax({
            url: script,
            method: 'POST',
            data: { type: 'curage', },
            success: function (data, textStatus, jhXHR) {
                _this.draw_curage(data, textStatus, jhXHR);
            },
            error: function (jqXHR, textStatus, errorThrown) {
                showError("ERROR in " + script + ": " + textStatus, jqXHR);
                return false;
            }
        });
    }

    saveCurAge() {
        var _this = this;

        if (this.#curagetable != null) {
            var invalids = this.#curagetable.validate();
            if (invalids !== true) {
                console.log(invalids);
                alert("Age Table does not pass validation, please check for empty cells or cells in red");
                return false;
            }
            this.#curage_savebtn.innerHTML = "Saving...";
            this.#curage_savebtn.disabled = true;

            var script = "scripts/updateMemberSetupData.php";

            var postdata = {
                ajax_request_action: 'curage',
                year: this.#current_conid,
                tabledata: JSON.stringify(this.#curagetable.getData()),
                tablename: "ageList",
                indexcol: "agekey"
            };
            //console.log(postdata);
            $.ajax({
                url: script,
                method: 'POST',
                data: postdata,
                success: function (data, textStatus, jhXHR) {
                    _this.saveCurAgeComplete(data, textStatus, jhXHR);
                },
                error: function (jqXHR, textStatus, errorThrown) {
                    showError("ERROR in " + script + ": " + textStatus, jqXHR);
                    return false;
                }
            });
        }
    };

    undoNextAge() {
        if (this.#nextagetable != null) {
            this.#nextagetable.undo();

            if (this.checkNextageUndoRedo() <= 0) {
                this.#nextage_dirty = false;
                this.#nextage_savebtn.innerHTML = "Save Changes";
                this.#nextage_savebtn.disabled = true;
            }
        }
    };

    redoNextAge() {
        if (this.#nextagetable != null) {
            this.#nextagetable.redo();

            if (this.checkNextageUndoRedo() > 0) {
                this.#nextage_dirty = true;
                this.#nextage_savebtn.innerHTML = "Save Changes*";
                this.#nextage_savebtn.disabled = false;
            }
        }
    };

    addrowNextAge()  {
        var _this = this;

        this.#nextagetable.addRow({conid: this.#next_conid, ageType: 'new-row', label: 'new-label', shortname: 'new-shortname', sortorder: 99, uses: 0}, false).then(function (row) {
            row.getTable().scrollToRow(row);
            _this.checkNextageUndoRedo();
        });
    }

    // set undo / redo status for next con ageList buttons
    checkNextageUndoRedo() {
        var undosize = this.#nextagetable.getHistoryUndoSize();
        this.#nextage_undobtn.disabled = undosize <= 0;
        this.#nextage_redobtn.disabled = this.#nextagetable.getHistoryRedoSize() <= 0;
        return undosize;
    }
    
    saveNextAgeComplete(data, textStatus, jhXHR) {
        var _this = this;

        if ('error' in data && data['error'] != '') {
            showError(data['error']);
            this.#nextage_savebtn.innerHTML = "Save Changes*";
            this.#nextage_savebtn.disabled = false;
            return false;
        } else {
            showError(data['success']);
        }
        this.#nextage_savebtn.innerHTML = "Save Changes";
        var script = "scripts/getMemberSetupData.php";
        $.ajax({
            url: script,
            method: 'POST',
            data: { type: 'nextage', },
            success: function (data, textStatus, jhXHR) {
                _this.draw_nextage(data, textStatus, jhXHR);
            },
            error: function (jqXHR, textStatus, errorThrown) {
                showError("ERROR in " + script + ": " + textStatus, jqXHR);
                return false;
            }
        });
    }

    saveNextAge() {
        var _this = this;

        if (this.#nextagetable != null) {
            var invalids = this.#nextagetable.validate();
            if (invalids !== true) {
                console.log(invalids);
                alert("MemType Table does not pass validation, please check for empty cells or cells in red");
                return false;
            }
            this.#nextage_savebtn.innerHTML = "Saving...";
            this.#nextage_savebtn.disabled = true;

            var script = "scripts/updateMemberSetupData.php";

            var postdata = {
                ajax_request_action: 'nextage',
                year: this.#current_conid,
                tabledata: JSON.stringify(this.#nextagetable.getData()),
                tablename: "ageList",
                indexcol: "agekey"
            };
            //console.log(postdata);
            $.ajax({
                url: script,
                method: 'POST',
                data: postdata,
                success: function (data, textStatus, jhXHR) {
                    _this.saveNextAgeComplete(data, textStatus, jhXHR);
                },
                error: function (jqXHR, textStatus, errorThrown) {
                    showError("ERROR in " + script + ": " + textStatus, jqXHR);
                    return false;
                }
            });
        }
    };
};

function reqEditable(cell) {
    if (cell.getData().required == 'N')
        return true;

    cell.getElement().style.backgroundColor ="#E8FFE8";
    return false;
}