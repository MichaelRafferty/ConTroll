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
        if (this.#memtypetable.getHistoryUndoSize() > 0) {
            this.#memtype_undobtn.disabled = false;
        }
    };

    memtype_rowMoved(row) {
        this.#memtype_savebtn.innerHTML = "Save Changes*";
        this.#memtype_savebtn.disabled = false;
        this.#memtype_dirty = true;
        if (this.#memtypetable.getHistoryUndoSize() > 0) {
            this.#memtype_undobtn.disabled = false;
        }
    }

    category_rowMoved(row) {
        this.#category_savebtn.innerHTML = "Save Changes*";
        this.#category_savebtn.disabled = false;
        this.#category_dirty = true;
        if (this.#memtypetable.getHistoryUndoSize() > 0) {
            this.#category_undobtn.disabled = false;
        }
    }

    category_dataChanged(data) {
        //data - the updated table data
        if (!this.#category_dirty) {
            this.#category_savebtn.innerHTML = "Save Changes*";
            this.#category_savebtn.disabled = false;
            this.#category_dirty = true;
        }
        if (this.#categorytable.getHistoryUndoSize() > 0) {
            this.#category_undobtn.disabled = false;
        }
    };

    curage_dataChanged(data) {
        //data - the updated table data
        if (!this.#curage_dirty) {
            this.#curage_savebtn.innerHTML = "Save Changes*";
            this.#curage_savebtn.disabled = false;
            this.#curage_dirty = true;
        }
        if (this.#curagetable.getHistoryUndoSize() > 0) {
            this.#curage_undobtn.disabled = false;
        }
    };

    curage_rowMoved(row) {
        this.#curage_savebtn.innerHTML = "Save Changes*";
        this.#curage_savebtn.disabled = false;
        this.#curage_dirty = true;
        if (this.#curagetable.getHistoryUndoSize() > 0) {
            this.#curage_undobtn.disabled = false;
        }
    }

    nextage_dataChanged(data) {
        //data - the updated table data
        if (!this.#nextage_dirty) {
            this.#nextage_savebtn.innerHTML = "Save Changes*";
            this.#nextage_savebtn.disabled = false;
            this.#nextage_dirty = true;
        }
        if (this.#nextagetable.getHistoryUndoSize() > 0) {
            this.#nextage_undobtn.disabled = false;
        }
    };

    nextage_rowMoved(row) {
        this.#nextage_savebtn.innerHTML = "Save Changes*";
        this.#nextage_savebtn.disabled = false;
        this.#nextage_dirty = true;
        if (this.#nextagetable.getHistoryUndoSize() > 0) {
            this.#nextage_undobtn.disabled = false;
        }
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
                { title: "Type", field: "memType", headerSort: true, width: 150, editor: "input", editorParams: { elementAttributes: { maxlength: "16" } } },
                { title: "Active", field: "active", headerSort: true, editor: "list", editorParams: { values: ["Y", "N"], } },
                { title: "Sort Order", field: "sortorder", headerSort: true, visible: false },
                {
                    title: "Delete", field: "uses", formatter: deleteicon, hozAlign: "center", headerSort: false,
                    cellClick: function (e, cell) {
                        deleterow(e, cell.getRow());
                    }
                },
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
                { title: "Category", field: "memCategory", width: 150, headerSort: true, editor: "input", editorParams: { elementAttributes: { maxlength: "16" } } },
                { title: "Active", field: "active", headerSort: true, editor: "list", editorParams: { values: ["Y", "N"], } },
                { title: "Sort Order", field: "sortorder", headerSort: true, visible: false },
                {
                    title: "Delete", field: "uses", formatter: deleteicon, hozAlign: "center", headerSort: false,
                    cellClick: function (e, cell) {
                        deleterow(e, cell.getRow());
                    }
                },
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
                { title: "ConID", field: "conid", visible: false },
                { title: "Age Type", field: "ageType", headerSort: true, editor: "input" },
                { title: "Label", field: "label", headerSort: false, editor: "input", editorParams: { elementAttributes: { maxlength: "64" } } },
                { title: "shortname", field: "shortname", headerSort: false, editor: "input", editorParams: { elementAttributes: { maxlength: "16" } } },
                { title: "Sort Order", field: "sortorder", headerSort: true, visible: false },
                {
                    title: "Delete", field: "uses", formatter: deleteicon, hozAlign: "center", headerSort: false,
                    cellClick: function (e, cell) {
                        deleterow(e, cell.getRow());
                    }
                },
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
                { title: "ConID", field: "conid", visible: false },
                { title: "Age Type", field: "ageType", headerSort: true, editor: "input" },
                { title: "Label", field: "label", headerSort: false, editor: "input", editorParams: { elementAttributes: { maxlength: "64" } } },
                { title: "shortname", field: "shortname", headerSort: false, editor: "input", editorParams: { elementAttributes: { maxlength: "16" } } },
                { title: "Sort Order", field: "sortorder", headerSort: true, visible: false },
                {
                    title: "Delete", field: "uses", formatter: deleteicon, hozAlign: "center", headerSort: false,
                    cellClick: function (e, cell) {
                        deleterow(e, cell.getRow());
                    }
                },
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
        var script = "scripts/getMemberSetupData.php";
        $.ajax({
            url: script,
            method: 'GET',
            data: 'type=all',
            success: function (data, textStatus, jhXHR) {
                mem.draw(data, textStatus, jhXHR);
            },
            error: function (jqXHR, textStatus, errorThrown) {
                showError("ERROR in " + script + ": " + textStatus, jqXHR);
                return false;
            }
        });
    };

    close() {
        this.#memtypetable = null;
        this.#categorytable = null;
        this.#memsetup_pane.innerHTML = '';
        this.#memtype_dirty = false;
        this.#category_dirty = false;
    };

    undoTypes() {
        if (this.#memtypetable != null) {
            this.#memtypetable.undo();

            var undoCount = this.#memtypetable.getHistoryUndoSize();
            if (undoCount <= 0) {
                this.#memtype_undobtn.disabled = true;
                this.#memtype_dirty = false;
                this.#memtype_savebtn.innerHTML = "Save Changes";
                this.#memtype_savebtn.disabled = true;
            }
            var redoCount = this.#memtypetable.getHistoryRedoSize();
            if (redoCount > 0) {
                this.#memtype_redobtn.disabled = false;
            }
        }
    };

    redoTypes() {
        if (this.#memtypetable != null) {
            this.#memtypetable.redo();

            var undoCount = this.#memtypetable.getHistoryUndoSize();
            if (undoCount > 0) {
                this.#memtype_undobtn.disabled = false;
                this.#memtype_dirty = true;
                this.#memtype_savebtn.innerHTML = "Save Changes*";
                this.#memtype_savebtn.disabled = false;
            }
            var redoCount = this.#memtypetable.getHistoryRedoSize();
            if (redoCount <= 0) {
                this.#memtype_redobtn.disabled = true;
            }
        }
    };

    addrowTypes() {
        this.#memtypetable.addRow({ memType: 'new-row', active: 'Y', sortorder: 99, uses: 0 }, false);
    };

    saveTypesComplete(data, textStatus, jhXHR) {
        if (data['error'] && data['error' != '']) {
            showError(data['error']);
        } else {
            showError(data['success']);
        }
        this.#memtype_savebtn.innerHTML = "Save Changes";
        var script = "scripts/getMemberSetupData.php";
        $.ajax({
            url: script,
            method: 'GET',
            data: 'type=memType',
            success: function (data, textStatus, jhXHR) {
                mem.draw_memtype(data, textStatus, jhXHR);
            },
            error: function (jqXHR, textStatus, errorThrown) {
                showError("ERROR in " + script + ": " + textStatus, jqXHR);
                return false;
            }
        });
    }

    saveTypes() {
        if (this.#memtypetable != null) {
            this.#memtype_savebtn.innerHTML = "Saving...";
            this.#memtype_savebtn.disabled = true;

            var script = "scripts/updateMemberSetupData.php";

            var postdata = {
                ajax_request_action: 'memtype',
                tabledata: this.#memtypetable.getData(),
                tablename: "memTypes",
                indexcol: "memType"
            };
            //console.log(postdata);
            $.ajax({
                url: script,
                method: 'POST',
                data: postdata,
                success: function (data, textStatus, jhXHR) {
                    mem.saveTypesComplete(data, textStatus, jhXHR);
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

            var undoCount = this.#categorytable.getHistoryUndoSize();
            if (undoCount <= 0) {
                this.#category_undobtn.disabled = true;
                this.#category_dirty = false;
                this.#category_savebtn.innerHTML = "Save Changes";
                this.#category_savebtn.disabled = true;
            }
            var redoCount = this.#categorytable.getHistoryRedoSize();
            if (redoCount > 0) {
                this.#category_redobtn.disabled = false;
            }
        }
    };

    redoCat() {
        if (this.#categorytable != null) {
            this.#categorytable.redo();

            var undoCount = this.#categorytable.getHistoryUndoSize();
            if (undoCount > 0) {
                this.#category_undobtn.disabled = false;
                this.#category_dirty = true;
                this.#category_savebtn.innerHTML = "Save Changes*";
                this.#category_savebtn.disabled = false;
            }
            var redoCount = this.#categorytable.getHistoryRedoSize();
            if (redoCount <= 0) {
                this.#category_redobtn.disabled = true;
            }
        }
    };

    addrowCat() {
        this.#categorytable.addRow({ memCategory: 'new-row', active: 'Y', sortorder: 99, uses: 0 }, false);
    };

    saveCatComplete(data, textStatus, jhXHR) {
        if (data['error'] && data['error' != '']) {
            showError(data['error']);
        } else {
            showError(data['success']);
        }
        this.#category_savebtn.innerHTML = "Save Changes";
        var script = "scripts/getMemberSetupData.php";
        $.ajax({
            url: script,
            method: 'GET',
            data: 'type=memCat',
            success: function (data, textStatus, jhXHR) {
                mem.draw_memcat(data, textStatus, jhXHR);
            },
            error: function (jqXHR, textStatus, errorThrown) {
                showError("ERROR in " + script + ": " + textStatus, jqXHR);
                return false;
            }
        });
    }

    saveCat() {
        if (this.#categorytable != null) {
            this.#category_savebtn.innerHTML = "Saving...";
            this.#category_savebtn.disabled = true;

            var script = "scripts/updateMemberSetupData.php";

            var postdata = {
                ajax_request_action: 'category',
                tabledata: this.#categorytable.getData(),
                tablename: "memcategories",
                indexcol: "memCategory"
            };
            //console.log(postdata);
            $.ajax({
                url: script,
                method: 'POST',
                data: postdata,
                success: function (data, textStatus, jhXHR) {
                    mem.saveCatComplete(data, textStatus, jhXHR);
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

            var undoCount = this.#curagetable.getHistoryUndoSize();
            if (undoCount <= 0) {
                this.#curage_undobtn.disabled = true;
                this.#curage_dirty = false;
                this.#curage_savebtn.innerHTML = "Save Changes";
                this.#curage_savebtn.disabled = true;
            }
            var redoCount = this.#curagetable.getHistoryRedoSize();
            if (redoCount > 0) {
                this.#curage_redobtn.disabled = false;
            }
        }
    };

    redoCurAge() {
        if (this.#curagetable != null) {
            this.#curagetable.redo();

            var undoCount = this.#curagetable.getHistoryUndoSize();
            if (undoCount > 0) {
                this.#curage_undobtn.disabled = false;
                this.#curage_dirty = true;
                this.#curage_savebtn.innerHTML = "Save Changes*";
                this.#curage_savebtn.disabled = false;
            }
            var redoCount = this.#curagetable.getHistoryRedoSize();
            if (redoCount <= 0) {
                this.#curage_redobtn.disabled = true;
            }
        }
    };

    addrowCurAge() {
        this.#curagetable.addRow({ conid: this.#current_conid, ageType: 'new-row', label: 'new-label', shortname: 'new-shortname', sortorder: 99, uses: 0 }, false);
    };

    saveCurAgeComplete(data, textStatus, jhXHR) {
        if (data['error'] && data['error' != '']) {
            showError(data['error']);
        } else {
            showError(data['success']);
        }
        this.#curage_savebtn.innerHTML = "Save Changes";
        var script = "scripts/getMemberSetupData.php";
        $.ajax({
            url: script,
            method: 'GET',
            data: 'type=curage',
            success: function (data, textStatus, jhXHR) {
                mem.draw_curage(data, textStatus, jhXHR);
            },
            error: function (jqXHR, textStatus, errorThrown) {
                showError("ERROR in " + script + ": " + textStatus, jqXHR);
                return false;
            }
        });
    }

    saveCurAge() {
        if (this.#curagetable != null) {
            this.#curage_savebtn.innerHTML = "Saving...";
            this.#curage_savebtn.disabled = true;

            var script = "scripts/updateMemberSetupData.php";

            var postdata = {
                ajax_request_action: 'curage',
                year: this.#current_conid,
                tabledata: this.#curagetable.getData(),
                tablename: "ageList",
                indexcol: "curage"
            };
            //console.log(postdata);
            $.ajax({
                url: script,
                method: 'POST',
                data: postdata,
                success: function (data, textStatus, jhXHR) {
                    mem.saveCurAgeComplete(data, textStatus, jhXHR);
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

            var undoCount = this.#nextagetable.getHistoryUndoSize();
            if (undoCount <= 0) {
                this.#nextage_undobtn.disabled = true;
                this.#nextage_dirty = false;
                this.#nextage_savebtn.innerHTML = "Save Changes";
                this.#nextage_savebtn.disabled = true;
            }
            var redoCount = this.#nextagetable.getHistoryRedoSize();
            if (redoCount > 0) {
                this.#nextage_redobtn.disabled = false;
            }
        }
    };

    redoNextAge() {
        if (this.#nextagetable != null) {
            this.#nextagetable.redo();

            var undoCount = this.#nextagetable.getHistoryUndoSize();
            if (undoCount > 0) {
                this.#nextage_undobtn.disabled = false;
                this.#nextage_dirty = true;
                this.#nextage_savebtn.innerHTML = "Save Changes*";
                this.#nextage_savebtn.disabled = false;
            }
            var redoCount = this.#nextagetable.getHistoryRedoSize();
            if (redoCount <= 0) {
                this.#nextage_redobtn.disabled = true;
            }
        }
    };

    addrowNextAge() {
        this.#nextagetable.addRow({ conid: this.#next_conid, ageType: 'new-row', label: 'new-label', shortname: 'new-shortname', sortorder: 99, uses: 0 }, false);
    };

    saveNextAgeComplete(data, textStatus, jhXHR) {
        if (data['error'] && data['error' != '']) {
            showError(data['error']);
        } else {
            showError(data['success']);
        }
        this.#nextage_savebtn.innerHTML = "Save Changes";
        var script = "scripts/getMemberSetupData.php";
        $.ajax({
            url: script,
            method: 'GET',
            data: 'type=nextage',
            success: function (data, textStatus, jhXHR) {
                mem.draw_nextage(data, textStatus, jhXHR);
            },
            error: function (jqXHR, textStatus, errorThrown) {
                showError("ERROR in " + script + ": " + textStatus, jqXHR);
                return false;
            }
        });
    }

    saveNextAge() {
        if (this.#nextagetable != null) {
            this.#nextage_savebtn.innerHTML = "Saving...";
            this.#nextage_savebtn.disabled = true;

            var script = "scripts/updateMemberSetupData.php";

            var postdata = {
                ajax_request_action: 'nextage',
                year: this.#current_conid,
                tabledata: this.#nextagetable.getData(),
                tablename: "ageList",
                indexcol: "nextage"
            };
            //console.log(postdata);
            $.ajax({
                url: script,
                method: 'POST',
                data: postdata,
                success: function (data, textStatus, jhXHR) {
                    mem.saveNextAgeComplete(data, textStatus, jhXHR);
                },
                error: function (jqXHR, textStatus, errorThrown) {
                    showError("ERROR in " + script + ": " + textStatus, jqXHR);
                    return false;
                }
            });
        }
    };
};

function deleteicon(cell, formattParams, onRendered) {
    var value = cell.getValue();
    if (value == 0)
        return "&#x1F5D1;";
    return value;
}

function deleterow(e, row) {
    var count = row.getCell("uses").getValue();
    if (count == 0)
        row.delete();
}