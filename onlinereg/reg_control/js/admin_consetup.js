//import { TabulatorFull as Tabulator } from 'tabulator-tables';

class consetup {
    #active = false;
    #contable = null;
    #memtable = null;
    #breaktable = null;
    #proposed = ' ';
    #condate = null;
    #conyear = null;
    #conid = null;
    #mindate = null;
    #maxdate = null;
    #dateformat = 'yyyy-MM-dd';
    #priceregexp = 'regex:^([0-9]+([.][0-9]*)?|[.][0-9]+)';
    #conlist_dirty = false;
    #conlist_savebtn = null;
    #conlist_undobtn = null;
    #conlist_redobtn = null;
    #conlist_div = null;
    #conlist_pane = null;
    #memlist_dirty = false;
    #memlist_savebtn = null;
    #memlist_undobtn = null;
    #memlist_redobtn = null;
    #memlist_addrowbtn = null;
    #memlist_div = null;
    #breaksetup_div = null
    #breaklist_div = null;
    #breaklist_dirty = false;
    #breaklist_savebtn = null;
    #breaklist_undobtn = null;
    #breaklist_redobtn = null;
    #message_div = null;
    #setup_type = null;
    #setup_title = null;

    constructor(setup_type) {

        this.#message_div = document.getElementById('test');
        if (setup_type == 'current' || setup_type == 'c') {
            this.#conlist_pane = document.getElementById('consetup-pane');
            this.#setup_type = 'current';
            this.#setup_title = 'Current';
        }
        if (setup_type == 'next' || setup_type == '') {
            this.#conlist_pane = document.getElementById('nextconsetup-pane');
            this.#setup_type = 'next';
            this.#setup_title = 'Next';
        }
    };

    // set undo / redo status for conlist (convention data)
    checkConlistUndoRedo() {
        var undosize = this.#contable.getHistoryUndoSize();
        this.#conlist_undobtn.disabled = undosize <= 0;
        this.#conlist_redobtn.disabled = this.#contable.getHistoryRedoSize() <= 0;
        return undosize;
    }

    conlist_dataChanged(data) {
        //data - the updated table data
        if (!this.#conlist_dirty) {
            this.#conlist_savebtn.innerHTML = "Save Changes*";
            this.#conlist_savebtn.disabled = false;
            this.#conlist_dirty = true;
        }
        this.checkConlistUndoRedo();
    };

    // set undo / redo status for memlist (membership type data)
    checkMemlistUndoRedo() {
        var undosize = this.#memtable.getHistoryUndoSize();
        this.#memlist_undobtn.disabled = undosize <= 0;
        this.#memlist_redobtn.disabled = this.#memtable.getHistoryRedoSize() <= 0;
        return undosize;
    }

    memlist_dataChanged(data) {
        //data - the updated table data
        if (!this.#memlist_dirty) {
            this.#memlist_savebtn.innerHTML = "Save Changes*";
            this.#memlist_savebtn.disabled = false;
            this.#memlist_dirty = true;
        }
        this.checkMemlistUndoRedo();
    };

    // set undo / redo status for break list (setup next year convention data)
    checkBreaklistUndoRedo() {
        var undosize = this.#breaktable.getHistoryUndoSize();
        this.#breaklist_undobtn.disabled = undosize <= 0;
        this.#breaklist_redobtn.disabled = this.#breaktable.getHistoryRedoSize() <= 0;
        return undosize;
    }

    breaklist_dataChanged(data) {
        //data - the updated table data
        if (!this.#breaklist_dirty) {
            this.#breaklist_dirty = true;
        }
        this.#breaklist_savebtn.innerHTML = "Build " + this.#setup_title + " Membership Types";
        this.#breaklist_savebtn.disabled = false;
        this.checkBreaklistUndoRedo();
    };

    draw(data, textStatus, jhXHR) {
        var _this = this;
        //console.log('in draw');
        //console.log(data);
        this.#proposed = ' ';

        if (data['conlist-type'] == 'proposed')
            this.#proposed = ' Proposed ';

        var html = '<h5><strong>' + this.#proposed + ' ' + this.#setup_title + ` Convention Data:</strong></h5>
<div id="` + this.#setup_type + `-conlist"></div>
<div id="conlist-buttons">  
    <button id="` + this.#setup_type + `conlist-undo" type="button" class="btn btn-secondary btn-sm" onclick="` + this.#setup_type + `.undoConlist(); return false;" disabled>Undo</button>
    <button id="` + this.#setup_type + `conlist-redo" type="button" class="btn btn-secondary btn-sm" onclick="` + this.#setup_type + `.redoConlist(); return false;" disabled>Redo</button>
    <button id="` + this.#setup_type + `conlist-save" type="button" class="btn btn-primary btn-sm"  onclick="` + this.#setup_type + `.saveConlist(); return false;" disabled>Save Changes</button>
</div>
<div>&nbsp;</div>
<h5><strong>` + this.#setup_title + ` Membership Types:</strong></h5>
<p><strong>NOTE:</strong> All date ranges are '>=' Start Date and '<' End Date, so the End Date of one period should be the start date of the next.</p>
<div id="` + this.#setup_type + `-memlist"></div>
<div id="memlist-buttons">  
    <button id="` + this.#setup_type + `memlist-undo" type="button" class="btn btn-secondary btn-sm" onclick="` + this.#setup_type + `.undoMemList(); return false;" disabled>Undo</button>
    <button id="` + this.#setup_type + `memlist-redo" type="button" class="btn btn-secondary btn-sm" onclick="` + this.#setup_type + `.redoMemList(); return false;" disabled>Redo</button>
    <button id="` + this.#setup_type + `memlist-addrow" type="button" class="btn btn-secondary btn-sm" onclick="` + this.#setup_type + `.addrowMemList(); return false;">Add New</button>
    <button id="` + this.#setup_type + `memlist-save" type="button" class="btn btn-primary btn-sm"  onclick="` + this.#setup_type + `.saveMemList(); return false;" disabled>Save Changes</button>
</div>
<div id="` + this.#setup_type + `-breakpointsetup" hidden>
<div>&nbsp;</div>
<h5><strong>` + this.#setup_title + ` Membership Breakpoints:</strong></h5>
<div id="` + this.#setup_type + `-breaklist"></div>
<div id="breaklist-buttons">
    <button id="` + this.#setup_type + `breaklist-undo" type="button" class="btn btn-secondary btn-sm" onclick="` + this.#setup_type + `.undoBreakList(); return false;" disabled>Undo</button>
    <button id="` + this.#setup_type + `breaklist-redo" type="button" class="btn btn-secondary btn-sm" onclick="` + this.#setup_type + `.redoBreakList(); return false;" disabled>Redo</button>
    <button id="` + this.#setup_type + `breaklist-save" type="button" class="btn btn-primary btn-sm"  onclick="` + this.#setup_type + `.saveBreakList(); return false;">Build ` + this.#setup_title + ` Membership Types</button>
</div>
</div>
`;
        this.#conlist_pane.innerHTML = html;
        this.#message_div.innerHTML = '';
        this.#conid = data['conid'];
        this.#condate = new Date(data['startdate']);
        this.#conyear = this.#condate.getFullYear();
        this.#mindate = this.#conyear + "-01-01";
        this.#maxdate = this.#conyear + "-12-31";
        this.#conlist_savebtn = document.getElementById(this.#setup_type + 'conlist-save');
        this.#conlist_undobtn = document.getElementById(this.#setup_type + 'conlist-undo');
        this.#conlist_redobtn = document.getElementById(this.#setup_type + 'conlist-redo');
        this.#conlist_div = document.getElementById(this.#setup_type + '-conlist');
        this.#memlist_div = document.getElementById(this.#setup_type + '-memlist');
        this.#memlist_savebtn = document.getElementById(this.#setup_type + 'memlist-save');
        this.#memlist_undobtn = document.getElementById(this.#setup_type + 'memlist-undo');
        this.#memlist_redobtn = document.getElementById(this.#setup_type + 'memlist-redo')
        this.#memlist_addrowbtn = document.getElementById(this.#setup_type + 'memlist-addrow')
        this.#breaksetup_div = document.getElementById(this.#setup_type + '-breakpointsetup');
        this.#breaklist_div = document.getElementById(this.#setup_type + '-breaklist');
        this.#breaklist_savebtn = document.getElementById(this.#setup_type + 'breaklist-save');
        this.#breaklist_undobtn = document.getElementById(this.#setup_type + 'breaklist-undo');
        this.#breaklist_redobtn = document.getElementById(this.#setup_type + 'breaklist-redo')


        this.draw_conlist(data, textStatus, jhXHR);
        this.draw_memlist(data, textStatus, jhXHR);
        if (data['breaklist'] && data['breaklist'] !== null) {
            this.draw_breaklist(data, textStatus, jhXHR);
        }
    };

    draw_conlist(data, textStatus, jhXHR) {
        var _this = this;

        if (this.#proposed != ' ') {
            this.#conlist_savebtn.innerHTML = "Save Changes*";
            this.#conlist_savebtn.disabled = false;
            this.#conlist_dirty = true;
        } else {
            this.#conlist_dirty = false;
        }

        if (this.#contable != null) {
            this.#contable.off("dataChanged");
            this.#contable.off("cellEdited");
            this.#contable.destroy();
        }

        this.#contable = null;

        if (data['conlist'] == null) {
            this.#conlist_div.innerHTML = 'Nothing defined yet';
        } else {
            this.#contable = new Tabulator('#' + this.#setup_type + '-conlist', {
                maxHeight: "400px",
                history: true,
                data: [data['conlist']],
                layout: "fitDataTable",
                columns: [
                    { title: "ID", field: "id", width: 50, headerSort: false },
                    { title: "Name", field: "name", headerSort: false, width: 100, editor: "input", editorParams: { elementAttributes: { maxlength: "10" } }, validator: "required" },
                    { title: "Label", field: "label", headerSort: false, width: 350, editor: "input", editorParams: { elementAttributes: { maxlength: "40" } }, validator: "required" },
                    { title: "Start Date", field: "startdate", width: 100, headerSort: false, editor: "date", validator: "required" },
                    { title: "End Date", field: "enddate", width: 100, headerSort: false, editor: "date", validator: "required" },
                    { field: "to_delete", visible: false, }
                ],
            });
        }

        if (this.#contable) {
            this.#contable.on("dataChanged", function (data) {
                _this.conlist_dataChanged(data);
            });
            this.#contable.on("cellEdited", cellChanged);
        }
    };

    draw_memlist(data, textStatus, jhXHR) {
        var _this = this;
        var memListData = new Array();

        if (this.#memtable != null) {
            this.#memtable.off("dataChanged");
            this.#memtable.off("rowMoved")
            this.#memtable.off("cellEdited");
            this.#memtable.destroy();
        }

        this.#memlist_dirty = false;

        this.#memtable = null;
        if (data['memlist'] == null) {
            showError("Nothing defined yet")
            memListData = new Array();
        } else {
            memListData = data['memList'];
        }
        this.#memtable = new Tabulator('#' + this.#setup_type + '-memlist', {
            maxHeight: "600px",
            history: true,
            movableRows: true,
            data: data['memlist'],
            layout: "fitDataTable",
            columns: [
                { rowHandle: true, formatter: "handle", frozen: true, width: 30, minWidth: 30, maxWidth: 30, headerSort: false },
                { title: "ID", field: "id", headerSort: true },
                { title: "Con ID", field: "conid", headerFilter: true },
                { title: "Sort", field: "sort_order", headerSort: false, visible: false },
                { title: "Category", field: "memCategory", editor: "list", editorParams: { values: data['memCats'], }, headerFilter: true, headerFilterParams: { values: data['memCats'] } },
                { title: "Type", field: "memType", editor: "list", editorParams: { values: data['memTypes'], }, headerFilter: true, headerFilterParams: { values: data['memTypes'], } },
                { title: "Age", field: "memAge", editor: "list", editorParams: { values: data['ageTypes'], }, headerFilter: true, headerFilterParams: { values: data['ageTypes'], }, },
                {
                    title: "Label", field: "shortname",
                    tooltip: function (e, cell, onRendered) { return cell.getRow().getCell("label").getValue(); },
                    editor: "input", editorParams: { elementAttributes: { maxlength: "64" } },
                    headerFilter: true
                },
                { title: "Label", field: "label", visible: false },
                {
                    title: "Price", field: "price", hozAlign: "right", editor: "input", validator: ["required", this.#priceregexp],
                    headerFilter: "input", headerFilterFunc:numberHeaderFilter,
                },
                { title: "Start Date", field: "startdate", width: 150, editor: "datetime", validator: "required", headerFilter: "input" },
                { title: "End Date", field: "enddate", width: 150, editor: "datetime", validator: "required", headerFilter: "input" },
                {
                    title: "Atcon", field: "atcon", editor: "list", editorParams: { values: ["Y", "N"], },
                    headerFilter: true, headerFilterParams: { values: ["Y", "N"], }
                },
                {
                    title: "Online", field: "online", editor: "list", editorParams: { values: ["Y", "N"], },
                    headerFilter: true, headerFilterParams: { values: ["Y", "N"], } },
                {
                    title: "Delete", field: "uses", formatter: deleteicon, hozAlign: "center", headerSort: false,
                    cellClick: function (e, cell) {
                        deleterow(e, cell.getRow());
                    }
                },
                { field: "to_delete", visible: false, },
            ],

        });

        this.#memtable.on("dataChanged", function (data) {
            _this.memlist_dataChanged(data);
        });
        this.#memtable.on("rowMoved", function (row) {
            _this.memlist_rowMoved(row)
        });
        this.#memtable.on("cellEdited", cellChanged);
    };

    draw_breaklist(data, textStatus, jhXHR) {
        var _this = this;

        if (this.#breaktable != null) {
            this.#breaktable.off("dataChanged");

            this.#breaktable.off("cellEdited");
            this.#breaktable.destroy();
        }

        this.#breaklist_dirty = true;

        this.#breaktable = null;

        this.#breaksetup_div.removeAttribute("hidden");

        this.#breaktable = new Tabulator('#' + this.#setup_type + '-breaklist', {
            history: true,
            data: data['breaklist'],
            layout: "fitDataTable",
            columns: [
                { // last con group
                    title: "Last Con's Breakpoint", columns: [
                        { title: "ConID", field: "oldconid" },
                        { title: "Start", field: "oldstart" , width: 100},
                        { title: "End", field: "oldend", width: 100 }
                    ],
                },
                { // new group
                    title: "New Con Breakpoint", columns: [
                        { title: "ConID", field: "newconid" },
                        { title: "Start", field: "newstart", width: 100, editor: "date", validator: "required" },
                        { title: "End", field: "newend", width: 100, editor: "date", validator: "required" },
                    ],
                },
                { field: "to_delete", visible: false, }
            ]
        });

        if (this.#breaktable) {
            this.#breaktable.on("dataChanged", function (data) {
                _this.breaklist_dataChanged(data);
            });

            this.#breaktable.on("cellEdited", cellChanged);
        }
    };

    open() {
        var script = "scripts/getCondata.php";     
        $.ajax({
            url: script,
            method: 'GET',
            data: 'year=' + this.#setup_type + '&type=all',
            success: function (data, textStatus, jhXHR) {
                if (data['year'] == 'current') {
                    current.draw(data, textStatus, jhXHR);
                } else {
                    next.draw(data, textStatus, jhXHR);
                }
            },
            error: function (jqXHR, textStatus, errorThrown) {
                showError("ERROR in " + script + ": " + textStatus, jqXHR);
                return false;
            }
        });
    };

    close() {
        if (this.#memtable != null) {
            this.#memtable.off("dataChanged");
            this.#memtable.off("rowMoved")
            this.#memtable.off("cellEdited");
            this.#memtable.destroy();
            this.#memtable = null;
        }
        if (this.#breaktable != null) {
            this.#breaktable.off("dataChanged");
            this.#breaktable.off("cellEdited");
            this.#breaktable.destroy();
            this.#breaktable = null;
        }
        if (this.#contable != null) {
            this.#contable.off("dataChanged");
            this.#contable.off("cellEdited");
            this.#contable.destroy();
            this.#contable = null;
        } 

        this.#conlist_pane.innerHTML = '';
        this.#conlist_dirty = false;
        this.#memlist_dirty = false;
        this.#breaklist_dirty = false;
    };

    undoConlist() {
        if (this.#contable != null) {
            this.#contable.undo();

            var undoCount = this.#contable.getHistoryUndoSize();
            if (undoCount <= 0) {
                this.#conlist_undobtn.disabled = true;
                this.#conlist_dirty = false;
                if (this.#proposed == ' ') {
                    this.#conlist_savebtn.innerHTML = "Save Changes";
                    this.#conlist_savebtn.disabled = true;
                }
            }
            var redoCount = this.#contable.getHistoryRedoSize();
            if (redoCount > 0) {
                this.#conlist_redobtn.disabled = false;
            }
        }
    };

    redoConlist() {
        if (this.#contable != null) {
            this.#contable.redo();

            var undoCount = this.#contable.getHistoryUndoSize();
            if (undoCount > 0) {
                this.#conlist_undobtn.disabled = false;
                this.#conlist_dirty = true;
                this.#conlist_savebtn.innerHTML = "Save Changes*";
                this.#conlist_savebtn.disabled = false;
            }
            var redoCount = this.#contable.getHistoryRedoSize();
            if (redoCount <= 0) {
                this.#conlist_redobtn.disabled = true;
            }
        }
    };

    undoMemList() {
        if (this.#memtable != null) {
            this.#memtable.undo();

            var undoCount = this.#memtable.getHistoryUndoSize();
            if (undoCount <= 0) {
                this.#memlist_undobtn.disabled = true;
                this.#memlist_dirty = false;
                if (this.#proposed == ' ') {
                    this.#memlist_savebtn.innerHTML = "Save Changes";
                    this.#memlist_savebtn.disabled = true;
                }
            }
            var redoCount = this.#memtable.getHistoryRedoSize();
            if (redoCount > 0) {
                this.#memlist_redobtn.disabled = false;
            }
        }
    };

    redoMemList() {
        if (this.#memtable != null) {
            this.#memtable.redo();

            var undoCount = this.#memtable.getHistoryUndoSize();
            if (undoCount > 0) {
                this.#memlist_undobtn.disabled = false;
                this.#memlist_dirty = true;
                this.#memlist_savebtn.innerHTML = "Save Changes*";
                this.#memlist_savebtn.disabled = false;
            }
            var redoCount = this.#memtable.getHistoryRedoSize();
            if (redoCount <= 0) {
                this.#memlist_redobtn.disabled = true;
            }
        }
    };

    undoBreakList() {
        if (this.#breaktable != null) {
            this.#breaktable.undo();

            var undoCount = this.#breaktable.getHistoryUndoSize();
            if (undoCount <= 0) {
                this.#breaklist_undobtn.disabled = true;
            }
            var redoCount = this.#breaktable.getHistoryRedoSize();
            if (redoCount > 0) {
                this.#breaklist_redobtn.disabled = false;
            }
        }
    };

    redoBreakList() {
        if (this.#breaktable != null) {
            this.#breaktable.redo();

            var undoCount = this.#breaktable.getHistoryUndoSize();
            if (undoCount > 0) {
                this.#breaklist_undobtn.disabled = false;       
            }
            var redoCount = this.#breaktable.getHistoryRedoSize();
            if (redoCount <= 0) {
                this.#breaklist_redobtn.disabled = true;
            }
        }
    };

    addrowMemList() {
        this.#memtable.addRow({ id: -99999, conid: this.#conid, shortname: 'new-row', price:0, atcon: 'N', online:'N', sortorder: 199, uses: 0 }, false).then(function(row) {
            row.getTable().scrollToRow(row);
        });
        this.checkMemlistUndoRedo();
    };

    memlist_rowMoved(row) {
        this.#memlist_savebtn.innerHTML = "Save Changes*";
        this.#memlist_savebtn.disabled = false;
        this.#memlist_dirty = true;
        if (this.#memtable.getHistoryUndoSize() > 0) {
            this.#memlist_undobtn.disabled = false;
        }
        if (this.#memtable.getHistoryRedoSize() > 0) {
            this.#memlist_redobtn.disabled = false;
        }
    }

    saveConlistComplete(data, textStatus, jhXHR) {        
        this.#conlist_savebtn.innerHTML = "Save Changes";

        var script = "scripts/getCondata.php";
        $.ajax({
            url: script,
            method: 'GET',
            data: 'year=' + this.#setup_type + '&type=conlist',      
            success: function (data, textStatus, jhXHR) {
                if (data['error']) {
                    showError(data['error']);
                    return false;
                }
                if (data['year'] == 'current') {
                    current.draw_conlist(data, textStatus, jhXHR);
                } else {
                    next.draw_conlist(data, textStatus, jhXHR);
                }
            },
            error: function (jqXHR, textStatus, errorThrown) {
                showError("ERROR in " + script + ": " + textStatus, jqXHR);
                return false;
            }
        });
    }

    saveConlist() {
        if (this.#contable != null) {
            var invalids = this.#contable.validate();
            if (!invalids === true) {
                console.log(invalids);
                alert("Conlist Table does not pass validation, please check for empty cells or cells in red");
                return false;
            }

            this.#conlist_savebtn.innerHTML = "Saving...";
            this.#conlist_savebtn.disabled = true;

            var script = "scripts/updateCondata.php";

            var postdata = {
                ajax_request_action: this.#setup_type,
                tabledata: JSON.stringify(this.#contable.getData()),
                tablename: "conlist",
                indexcol: "id"
            };
            //console.log(postdata);
            $.ajax({
                url: script,
                method: 'POST',
                data: postdata,
                success: function (data, textStatus, jhXHR) {
                    if (data['error'] != undefined) {
                        showError(data['error']);
                        // reset save button
                        if (data['year'] == 'current') {
                            current.conlist_dataChanged(data);
                        } else {
                            next.conlist_dataChanged(data);
                        }
                        return false;
                    } else {
                        showError(data['success']);
                    }
                    if (data['year'] == 'current') {
                        current.saveConlistComplete(data, textStatus, jhXHR);
                    } else {
                        next.saveConlistComplete(data, textStatus, jhXHR);
                    }                   
                },
                error: function (jqXHR, textStatus, errorThrown) {
                    showError("ERROR in " + script + ": " + textStatus, jqXHR);
                    return false;
                }
            });
        }
    };

    saveMemListComplete(data, textStatus, jhXHR) {
        if (data['error'] != undefined) {
            showError(data['error']);
            this.#memlist_savebtn.innerHTML = "Save Changes*";
            this.#memlist_savebtn.disabled = false;
            return false;
        } else {
            showError(data['success']);
        }
        this.#memlist_savebtn.innerHTML = "Save Changes";

        var script = "scripts/getCondata.php";
        $.ajax({
            url: script,
            method: 'GET',
            data: 'year=' + this.#setup_type + '&type=memlist',
            success: function (data, textStatus, jhXHR) {
                if (data['year'] == 'current') {
                    current.draw_memlist(data, textStatus, jhXHR);
                } else {
                    next.draw_memlist(data, textStatus, jhXHR);
                }
            },
            error: function (jqXHR, textStatus, errorThrown) {
                showError("ERROR in " + script + ": " + textStatus, jqXHR);
                return false;
            }
        });
    }

    saveMemList() {
        if (this.#memtable != null) {
            var invalids = this.#memtable.validate();
            if (invalids !== true) {
                console.log(invalids);
                alert("MemList Table does not pass validation, please check for empty cells or cells in red");
                return false;
            }

            this.#memlist_savebtn.innerHTML = "Saving...";
            this.#memlist_savebtn.disabled = true;

            var script = "scripts/updateCondata.php";

            var postdata = {
                ajax_request_action: this.#setup_type,
                tabledata: JSON.stringify(this.#memtable.getData()),
                tablename: "memlist",
                indexcol: "id"
            };
            //console.log(postdata);
            $.ajax({
                url: script,
                method: 'POST',
                data: postdata,
                success: function (data, textStatus, jhXHR) {
                    if (data['error'] != undefined) {
                        showError(data['error']);
                        // reset save button
                        if (data['year'] == 'current') {
                            current.memlist_dataChanged(data);
                        } else {
                            next.memlist_dataChanged(data);
                        }
                        return false;
                    } else {
                        showError(data['success']);
                    }

                    if (data['year'] == 'current') {                        
                        current.saveMemListComplete(data, textStatus, jhXHR);
                    } else {                        
                        next.saveMemListComplete(data, textStatus, jhXHR);
                    }
                },
                error: function (jqXHR, textStatus, errorThrown) {
                    showError("ERROR in " + script + ": " + textStatus, jqXHR);
                    return false;
                }
            });
        }
    };

    saveBreakListComplete(data, textStatus, jhXHR) {
        var success;
        if (data['error'] != undefined) {
            showError(data['error']);
            return false;
        } else {
            success = data['success'];
            showError(success);
        }

        var script = "scripts/getCondata.php";
        $.ajax({
            url: script,
            method: 'GET',
            data: 'year=' + this.#setup_type + '&type=memlist',
            success: function (data, textStatus, jhXHR) {                
                if (data['year'] == 'current') {
                    current.close();
                    current.draw(data, textStatus, jhXHR);
                } else {
                    next.close();
                    next.draw(data, textStatus, jhXHR);
                }
                showError(success);
            },
            error: function (jqXHR, textStatus, errorThrown) {
                showError("ERROR in " + script + ": " + textStatus, jqXHR);
                return false;
            }
        });
    }

    saveBreakList() {
        if (this.#breaktable != null) {
            var invalids = this.#breaktable.validate();
            if (invalids !== true) {
                console.log(invalids);
                alert("Breakpoint Table does not pass validation, please check for empty cells or cells in red");
                return false;
            }

            this.#breaklist_savebtn.innerHTML = "Creating new membership type list...";
            this.#breaklist_savebtn.disabled = true;

            var script = "scripts/updateCondata.php";

            var postdata = {
                ajax_request_action: this.#setup_type,
                tabledata: JSON.stringify(this.#breaktable.getData()),
                tablename: "breaklist",
                indexcol: "old"
            };
            //console.log(postdata);
            $.ajax({
                url: script,
                method: 'POST',
                data: postdata,
                success: function (data, textStatus, jhXHR) {
                    if (data['error'] != undefined) {
                        showError(data['error'])
                        // reset save button
                        if (data['year'] == 'current') {
                            current.breaklist_dataChanged(data);
                        } else {
                            next.breaklist_dataChanged(data);
                        }   
                        return false;
                    } else {
                        showError(data['success']);                        
                    }
                    if (data['year'] == 'current') {
                        current.saveBreakListComplete(data, textStatus, jhXHR);
                    } else {
                        next.saveBreakListComplete(data, textStatus, jhXHR);
                    }
                },
                error: function (jqXHR, textStatus, errorThrown) {
                    showError("ERROR in " + script + ": " + textStatus, jqXHR);
                    return false;
                }
            });
        }
    };
};
