//import { TabulatorFull as Tabulator } from 'tabulator-tables';

class consetup {
    #active = false;
    #contable = null;
    #memtable = null;
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

    draw(data, textStatus, jhXHR) {
        var _this = this;
        //console.log('in draw');
        //console.log(data);


        var html = '<h5><strong>' + this.#setup_title + ` Convention Data:</strong></h5>
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
    <button id="` + this.#setup_type + `memlist-csv" type="button" class="btn btn-info btn-sm"  onclick="` + this.#setup_type + `.downloadMemList(); return false;">Download CSV</button>
</div>
<div>&nbsp;</div>
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

        this.draw_conlist(data, textStatus, jhXHR);
        this.draw_memlist(data, textStatus, jhXHR);
    };

    draw_conlist(data, textStatus, jhXHR) {
        var _this = this;
        this.#conlist_dirty = false;

        if (this.#contable != null) {
            this.#contable.off("dataChanged");
            this.#contable.off("cellEdited");
            this.#contable.destroy();
        }

        this.#contable = null;

        if (data['conlist'] == null) {
            this.#conlist_div.innerHTML = 'Nothing defined yet.' +
                (this.#setup_type == 'next') ? ' After the current year is set up, ask you admin to run the "Build &lt;id&gt; Setup" ' +
                'from the home page before continuing the the next year setup.' : '';

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
            show_message("Nothing defined yet", 'warn')
            memListData = new Array();
        } else {
            memListData = data['memList'];
        }
        this.#memtable = new Tabulator('#' + this.#setup_type + '-memlist', {
            history: true,
            movableRows: true,
            data: data['memlist'],
            layout: "fitDataTable",
            pagination: true,
            paginationAddRow:"table",
            paginationSize: 25,
            paginationSizeSelector: [10, 25, 50, 100, 250, true], //enable page size select element with these options
            columns: [
                { rowHandle: true, formatter: "handle", frozen: true, width: 30, minWidth: 30, maxWidth: 30, headerSort: false },
                {
                    title: "Del", field: "uses", formatter: deleteicon, hozAlign: "center", headerSort: false,
                    cellClick: function (e, cell) {
                        deleterow(e, cell.getRow());
                    }
                },
                { title: "ID", field: "id", width: 70, headerSort: true, headerHozAlign:"right", hozAlign: "right", },
                { field: "memlistkey", visible: false, },
                { title: "Con ID", field: "conid", width: 70, headerWordWrap: true, headerFilter: true, headerHozAlign:"right", hozAlign: "right", },
                { title: "Sort", field: "sort_order", headerSort: false, visible: false },
                { title: "Category", field: "memCategory", editor: "list", editorParams: { values: data['memCats'], }, headerFilter: true, headerFilterParams: { values: data['memCats'] } },
                { title: "Type", field: "memType", editor: "list", editorParams: { values: data['memTypes'], }, headerFilter: true, headerFilterParams: { values: data['memTypes'], } },
                { title: "Age", field: "memAge", editor: "list", editorParams: { values: data['ageTypes'], }, headerFilter: true, headerFilterParams: { values: data['ageTypes'], }, },
                {
                    title: "Label", field: "shortname", minWidth: 300,
                    tooltip: function (e, cell, onRendered) { return cell.getRow().getCell("label").getValue(); },
                    editor: "input", editorParams: { elementAttributes: { maxlength: "64" } },
                    headerFilter: true
                },
                { title: "Label", field: "label", visible: false },
                {
                    title: "Price", field: "price", hozAlign: "right", editor: "input", validator: ["required", this.#priceregexp],
                    headerFilter: "input", headerFilterFunc:numberHeaderFilter,
                },
                { title: "Start Date", field: "startdate", width: 170, editor: "datetime", validator: "required", headerFilter: "input" },
                { title: "End Date", field: "enddate", width: 170, editor: "datetime", validator: "required", headerFilter: "input" },
                {
                    title: "At", field: "atcon", editor: "list", editorParams: { values: ["Y", "N"], },
                    headerFilter: true, headerFilterParams: { values: ["Y", "N"], }
                },
                {
                    title: "On", field: "online", editor: "list", editorParams: { values: ["Y", "N"], },
                    headerFilter: true, headerFilterParams: { values: ["Y", "N"], }
                },
                {
                    title: "Notes", field: "notes", minWidth: 300,
                    editor: "textarea", editorParams: { elementAttributes: { maxlength: "1024" } },
                    headerFilter: true, formatter: "textarea",
                },
                {
                    title: "GL Num", field: "glNum", minWidth: 120, headerWordWrap: true,
                    editor: "input", editorParams: { elementAttributes: { maxlength: "16" } },
                    headerFilter: true
                },
                {
                    title: "GL Label", field: "glLabel", minWidth: 200, headerWordWrap: true,
                    editor: "input", editorParams: { elementAttributes: { maxlength: "64" } },
                    headerFilter: true
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

    open() {
        var script = "scripts/regadmin_getCondata.php";
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
        if (this.#contable != null) {
            this.#contable.off("dataChanged");
            this.#contable.off("cellEdited");
            this.#contable.destroy();
            this.#contable = null;
        } 

        this.#conlist_pane.innerHTML = '';
        this.#conlist_dirty = false;
        this.#memlist_dirty = false;
    };

    undoConlist() {
        if (this.#contable != null) {
            this.#contable.undo();

            if (this.checkConlistUndoRedo() <= 0) {
                this.#conlist_dirty = false;
                this.#conlist_savebtn.innerHTML = "Save Changes";
                this.#conlist_savebtn.disabled = true;
            }
        }
    };

    redoConlist() {
        if (this.#contable != null) {
            this.#contable.redo();

            if (this.checkConlistUndoRedo() > 0) {
                this.#conlist_dirty = true;
                this.#conlist_savebtn.innerHTML = "Save Changes*";
                this.#conlist_savebtn.disabled = false;
            }
        }
    };

    undoMemList() {
        if (this.#memtable != null) {
            this.#memtable.undo();

            if (this.checkMemlistUndoRedo() <= 0) {
                this.#memlist_dirty = false;
                this.#memlist_savebtn.innerHTML = "Save Changes";
                this.#memlist_savebtn.disabled = true;
            }
        }
    };

    redoMemList() {
        if (this.#memtable != null) {
            this.#memtable.redo();

            if (this.checkMemlistUndoRedo() > 0) {
                this.#memlist_dirty = true;
                this.#memlist_savebtn.innerHTML = "Save Changes*";
                this.#memlist_savebtn.disabled = false;
            }
        }
    };

    addrowMemList() {
        var _this = this;

        this.#memtable.addRow({ id: -99999, conid: this.#conid, shortname: 'new-row', price:0, atcon: 'N', online:'N', sortorder: 199, uses: 0 }, false).then(function(row) {
            row.getTable().scrollToRow(row);
            _this.checkMemlistUndoRedo();
        });
    };

    memlist_rowMoved(row) {
        this.#memlist_savebtn.innerHTML = "Save Changes*";
        this.#memlist_savebtn.disabled = false;
        this.#memlist_dirty = true;
        this.checkMemlistUndoRedo();
    }

    saveConlistComplete(data, textStatus, jhXHR) {        
        this.#conlist_savebtn.innerHTML = "Save Changes";

        clear_message();
        var script = "scripts/regadmin_getCondata.php";
        $.ajax({
            url: script,
            method: 'GET',
            data: 'year=' + this.#setup_type + '&type=conlist',      
            success: function (data, textStatus, jhXHR) {
                if (data['error']) {
                    show_message(data['error'], 'error');
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
                show_message("Conlist Table does not pass validation, please check for empty cells or cells in red", 'error');
                return false;
            }

            this.#conlist_savebtn.innerHTML = "Saving...";
            this.#conlist_savebtn.disabled = true;

            var script = "scripts/regadmin_updateCondata.php";

            var postdata = {
                ajax_request_action: this.#setup_type,
                tabledata: JSON.stringify(this.#contable.getData()),
                tablename: "conlist",
                indexcol: "id"
            };
            clear_message();
            //console.log(postdata);
            $.ajax({
                url: script,
                method: 'POST',
                data: postdata,
                success: function (data, textStatus, jhXHR) {
                    if (data['error']) {
                        show_message(data['error'], 'error');
                        // reset save button
                        if (data['year'] == 'current') {
                            current.conlist_dataChanged(data);
                        } else {
                            next.conlist_dataChanged(data);
                        }
                        return false;
                    } else {
                        show_message(data['success'], 'success');
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
        if (data['error']) {
            this.#memlist_savebtn.innerHTML = "Save Changes*";
            this.#memlist_savebtn.disabled = false;
            return false;
        }
        this.#memlist_savebtn.innerHTML = "Save Changes";

        var script = "scripts/regadmin_getCondata.php";
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
                show_message("MemList Table does not pass validation, please check for empty cells or cells in red", 'error');
                return false;
            }

            var script = "scripts/regadmin_updateCondata.php";
            var tabledata = this.#memtable.getData();
            var keys = Object.keys(tabledata);
            var yearaheadWarning = '';
            for (var i = 0; i < keys.length; i++) {
                var row = tabledata[keys[i]];
                if (row.memCategory == 'yearahead') {
                    if (row.conid == this.#conid) {
                        yearaheadWarning += 'Fixing conid for ' + row.id + ' of ' + row.conid + ' for ' + row.memCategory +
                            ', Setting it to ' + (this.#conid + 1) + '<br/>';
                        this.#memtable.getRow(row.id).getCell('conid').setValue(this.#conid + 1);
                    }
                }
            }
            if (yearaheadWarning != '') {
                show_message(yearaheadWarning + " if this is correct, presse Save Changes again, " +
                    "otherwise delete the row and try adding it again not as a yearahead membership", 'warn');
                return;
            }

            this.#memlist_savebtn.innerHTML = "Saving...";
            this.#memlist_savebtn.disabled = true;

            var postdata = {
                ajax_request_action: this.#setup_type,
                tabledata: JSON.stringify(this.#memtable.getData()),
                tablename: "memlist",
                indexcol: "id"
            };
            clear_message();
            //console.log(postdata);
            $.ajax({
                url: script,
                method: 'POST',
                data: postdata,
                success: function (data, textStatus, jhXHR) {
                    if (data['error']) {
                        show_message(data['error'], 'error');
                        // reset save button
                        if (data['year'] == 'current') {
                            current.memlist_dataChanged(data);
                        } else {
                            next.memlist_dataChanged(data);
                        }
                        return false;
                    } else {
                        show_message(data['success'], 'success');
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

    downloadMemList() {
        if (this.#memtable == null)
            return;

        var filename = this.#conid + '_memlist';
        var tabledata = JSON.stringify(this.#memtable.getData("active"));
        var fieldList = [
            'id',
            'conid',
            { key: 'memCategory', label: 'Category' },
            { key: 'memType', label: 'Type' },
            { key: 'memAge', label: 'Age' },
            'shortname',
            'label',
            'price',
            'startdate',
            'enddate',
            'atcon',
            'online',
            'notes',
            'sort_order'
        ];
        downloadCSVPost(filename, tabledata, null, fieldList);
    }
};
