//import { TabulatorFull as Tabulator } from 'tabulator-tables';

var activeConSetup = 'none';
var editListMasterRow = null;
var memListModalDirty = false;
var tinyMCEInit = false;

class consetup {
    #debug = 0;
    #debugVisible = false;
    #active = false;
    #bundlesEnabled = false;
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
    #catListData = null;
    #typeListData = null;
    #ageListData = null;
    #catListSelect = null;
    #typeListSelect = null;
    #ageListSelect = null;
    #memListModal = null;
    #memListMasterRow = null;
    #editData = null;
    #paginationDiv = null;
    // edit bundle items
    #memListBundleContains = null;
    #memListBundleTable = null;
    #memListPrice = null;
    #selValues = null;
    #editMemListBundleDiv = null;
    #nonBundleList = [];
    #containsField = null;
    #rowStartDate = '';
    #rowEndDate = '';

    constructor(setup_type) {
        this.#debug = Number(config.debug);
        this.#conid = Number(config.conid);
        this.#bundlesEnabled = config.bundleMemberships == 'Y';
        if (this.#debug & 2) {
            this.#debugVisible = true;
        }
        config.debug = this.#debug;
        config.conid = this.#conid;
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
        activeConSetup = this.#setup_type;
        let id = document.getElementById('editMemListModal');
        if (id) {
            this.#memListModal = new bootstrap.Modal(id, {focus: true, backdrop: 'static'});
        }
        this.#memListBundleContains = document.getElementById('editMemListBundleContains');
        this.#memListPrice = document.getElementById('editMemListPrice');
        this.#editMemListBundleDiv = document.getElementById('editMemListBundleDiv');
        if (this.#editMemListBundleDiv)
            this.#editMemListBundleDiv.hidden = true;
        $('div[name="TScontains"]').hide();
    };

    // set undo / redo status for conlist (convention data)
    checkConlistUndoRedo() {
        let undosize = this.#contable.getHistoryUndoSize();
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
        let undosize = this.#memtable.getHistoryUndoSize();
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

    defaultNewRowValues(row, except) {
        this.#editData.push({id: 'new' + row });
        document.getElementById('EMLTS' + row + '_ID').innerHTML = this.#editData[row].id;
        this.#editData[row].conid = this.#conid;
        this.#editData[row].sort_order = this.#editData[row - 1].sort_order + 1;
        document.getElementById('EMLTS' + row + '_Sort').value = this.#editData[row].sort_order;
        this.#editData[row].memCategory = document.getElementById('memListCategorySelect').value;
        this.#editData[row].memAge = document.getElementById('memListAgeSelect').value;
        this.#editData[row].memType = document.getElementById('memListTypeSelect').value;
        this.#editData[row].shortname = document.getElementById('editMemListLabel').value;
        this.#editData[row].notes = document.getElementById('editMemListNotes').value;
        this.#editData[row].uses = 0;
    }

    setEditDataPrice(row, value) {
        if (this.#editData.length <= row)
            this.defaultNewRowValues(row, 'price');
        this.#editData[row].price = value;
    }

    setEditDataStartDate(row, value) {
        if (this.#editData.length <= row)
            this.defaultNewRowValues(row, 'startdate');
        this.#editData[row].startdate = value;
    }

    setEditDataEndDate(row, value) {
        if (this.#editData.length <= row)
            this.defaultNewRowValues(row, 'enddate');
        this.#editData[row].enddate = value;
    }

    draw(year, data, textStatus, jhXHR) {
        let _this = this;
        //console.log('in draw');
        //console.log(data);


        let html = '<h5><strong>' + this.#setup_title + ` Convention Data:</strong></h5>
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
<div class='row mt-2 mb-3' id='reglist-csv-div'>
    <div class="col-sm-auto p-1 ps-3 pe-3 tabulator-paginator paginationBGColor" id="` + this.#setup_type + `"PaginationDiv"></div>
    <div class='col-sm-auto p-1 ms-4' id="memlist-buttons">  
        <button id="` + this.#setup_type + `memlist-undo" type="button" class="btn btn-secondary btn-sm" onclick="` + this.#setup_type + `.undoMemList(); return false;" disabled>Undo</button>
        <button id="` + this.#setup_type + `memlist-redo" type="button" class="btn btn-secondary btn-sm" onclick="` + this.#setup_type + `.redoMemList(); return false;" disabled>Redo</button>
        <button id="` + this.#setup_type + `memlist-addrow" type="button" class="btn btn-secondary btn-sm" onclick="` + this.#setup_type + `.addrowMemList(); return false;">Add New</button>
        <button id="` + this.#setup_type + `memlist-save" type="button" class="btn btn-primary btn-sm"  onclick="` + this.#setup_type + `.saveMemList(); return false;" disabled>Save Changes</button>
        <button id="` + this.#setup_type + `memlist-csv" type="button" class="btn btn-info btn-sm"  onclick="` + this.#setup_type + `.downloadMemList('csv'); return false;">Download CSV</button>
        <button id="` + this.#setup_type + `memlist-xlsx" type="button" class="btn btn-info btn-sm"  onclick="` + this.#setup_type + `.downloadMemList('xlsx'); return false;">Download Excel</button>
    </div>
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

        this.draw_conlist(year, data, textStatus, jhXHR);
        this.draw_memlist(year, data, textStatus, jhXHR);
    };

    draw_conlist(year, data, textStatus, jhXHR) {
        let _this = this;
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
                    {title: "ID", field: "id", width: 50, headerSort: false},
                    {
                        title: "Name",
                        field: "name",
                        headerSort: false,
                        width: 100,
                        editor: "input",
                        editorParams: {elementAttributes: {maxlength: "10"}},
                        validator: "required"
                    },
                    {
                        title: "Label",
                        field: "label",
                        headerSort: false,
                        width: 350,
                        editor: "input",
                        editorParams: {elementAttributes: {maxlength: "40"}},
                        validator: "required"
                    },
                    {title: "Start Date", field: "startdate", width: 100, headerSort: false, editor: "date", validator: "required"},
                    {title: "End Date", field: "enddate", width: 100, headerSort: false, editor: "date", validator: "required"},
                    {field: "to_delete", visible: false,}
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

    draw_memlist(year, data, textStatus, jhXHR) {
        let _this = this;

        // save off the select list data
        this.#catListData = data['memCats'];
        this.#typeListData = data['memTypes'];
        if (data['ageTypes'] && Array.isArray(data['ageTypes']))
            this.#ageListData = data['ageTypes'];
        else
            this.#ageListData = [];

        // build the select lists
        this.#catListSelect = "<select name='memListCategorySelect' id='memListCategorySelect' onchange='memListModalDirty = true;'>";
        for (let index = 0; index < this.#catListData.length; index++) {
            let cat = this.#catListData[index];
            this.#catListSelect += "\n<option value='" + cat + "'>" + cat + "</option>";
        }
        this.#ageListSelect += "\n</select>";
        this.#ageListSelect = "<select name='memListAgeSelect' id='memListAgeSelect' onchange='memListModalDirty = true;'>";
        for (let index = 0; index < this.#ageListData.length; index++) {
            let age = this.#ageListData[index];
            this.#ageListSelect += "\n<option value='" + age + "'>" + age + "</option>";
        }
        this.#typeListSelect += "\n</select>";
        this.#typeListSelect = "<select name='memListTypeSelect' id='memListTypeSelect' onchange='memListModalDirty = true;'>";
        for (let index = 0; index < this.#typeListData.length; index++) {
            let type = this.#typeListData[index];
            this.#typeListSelect += "\n<option value='" + type + "'>" + type + "</option>";
        }
        this.#typeListSelect += "\n</select>";
        document.getElementById('editMemListCategory').innerHTML = this.#catListSelect;
        document.getElementById('editMemListAge').innerHTML = this.#ageListSelect;
        document.getElementById('editMemListType').innerHTML = this.#typeListSelect;

        let memListData = new Array();

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
            memListData = [];
            data['memlist'] = [];
        } else {
            memListData = data['memlist'];
        }
        this.#paginationDiv = document.getElementById( this.#setup_type + 'PaginationDiv');
        this.#paginationDiv.innerHTML = '';
        this.#paginationDiv.hidden = data['memlist'].length <= 25;

        this.#memtable = new Tabulator('#' + this.#setup_type + '-memlist', {
            history: true,
            movableRows: true,
            data: data['memlist'],
            layout: "fitDataTable",
            pagination: data['memlist'].length > 25,
            paginationAddRow: "table",
            paginationSize: 25,
            paginationSizeSelector: [10, 25, 50, 100, 250, true], //enable page size select element with these options
            paginationElement: this.#paginationDiv,
            initialSort:[
                {column:"sort_order", dir:"asc"}, //sort by this first
            ],
            columns: [
                {rowHandle: true, formatter: "handle", frozen: true, width: 30, minWidth: 30, maxWidth: 30, headerSort: false},
                {
                    title: "Del", field: "uses", formatter: deleteicon, hozAlign: "center", headerSort: false,
                    cellClick: function (e, cell) {
                        deleterow(e, cell.getRow());
                    }
                },
                {title: "Edit", formatter: this.editbutton, formatterParams: {year: year}, hozAlign: "left", headerSort: false},
                {title: "Sort", field: "sort_order", headerSort: true,sorter:"number"},
                {
                    title: "ID", field: "id", width: 70, headerSort: true, headerHozAlign: "right", hozAlign: "right",
                    headerFilter: "input", headerFilterFunc: numberHeaderFilter,
                },
                {field: "memlistkey", visible: false,},
                {title: "Con ID", field: "conid", width: 70, headerWordWrap: true, headerFilter: true, headerHozAlign: "right", hozAlign: "right",},
                {
                    title: "Category", field: "memCategory",
                    editor: "list", editorParams: {values: data['memCats'],},
                    headerFilter: true, headerFilterParams: {values: data['memCats']}
                },
                {
                    title: "Type", field: "memType",
                    editor: "list",  editorParams: {values: data['memTypes'],},
                    headerFilter: true,
                    headerFilterParams: {values: data['memTypes'],}
                },
                {
                    title: "Age", field: "memAge",
                    editor: "list", editorParams: {values: data['ageTypes'],},
                    headerFilter: true, headerFilterParams: {values: data['ageTypes'],},
                },
                {
                    title: "Label", field: "shortname", width: 200,
                    tooltip: function (e, cell, onRendered) {
                        return cell.getRow().getCell("label").getValue();
                    },
                    editor: "input", editorParams: {elementAttributes: {maxlength: "64"}},
                    formatter: "textarea",
                    headerFilter: true
                },
                {title: "Label", field: "label", visible: false},
                {
                    title: "Price", field: "price", hozAlign: "right", editor: "input", validator: ["required", this.#priceregexp],
                    formatter: "money",  formatterParams: { decimal: '.', thousand: ',', negative: true, precision: 2},
                    headerFilter: "input", headerFilterFunc: numberHeaderFilter,
                },
                {title: "Start Date", field: "startdate", width: 170, editor: "datetime", validator: "required", headerFilter: "input"},
                {title: "End Date", field: "enddate", width: 170, editor: "datetime", validator: "required", headerFilter: "input"},
                {
                    title: "At", field: "atcon", editor: "list", editorParams: {values: ["Y", "N"],},
                    headerFilter: true, headerFilterParams: {values: ["Y", "N"],}
                },
                {
                    title: "On", field: "online", editor: "list", editorParams: {values: ["Y", "N"],},
                    headerFilter: true, headerFilterParams: {values: ["Y", "N"],}
                },
                {
                    title: "Notes", field: "notes", width: 200,
                    editor: "input", editorParams: {elementAttributes: {maxlength: "1024"}},
                    headerFilter: true,
                    formatter: "textarea",
                },
                {
                    title: "Cart Desc", field: "cartDesc", width: 300,
                    headerFilter: true,
                    formatter: "html",
                },
                {
                    title: "GL Num", field: "glNum", width: 120, headerWordWrap: true,
                    editor: "input", editorParams: {elementAttributes: {maxlength: "16"}},
                    headerFilter: true
                },
                {
                    title: "GL Label", field: "glLabel", width: 200, headerWordWrap: true,
                    editor: "input", editorParams: {elementAttributes: {maxlength: "64"}},
                    headerFilter: true,
                    formatter: "textarea",
                },
                {field: "to_delete", visible: false,},
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

    // display edit button for a long field
    editbutton(cell, formatterParams, onRendered) {
        let index = cell.getRow().getIndex()
        let year = formatterParams.year;
        if (isNaN(index))
            index = "'" + index + "'";
        return '<button class="btn btn-secondary" style = "--bs-btn-padding-y: .0rem; --bs-btn-padding-x: .3rem; --bs-btn-font-size: .75rem;",' +
            ' onclick="' + year + '.editSeries(' + index + ');">Edit</button>';
    }

    editSeries(index) {
        let row = this.#memtable.getRow(index);
        let rowData = row.getData();
        let listData = this.#memtable.getData();
        this.#editData = [];

        // build an array of all of the rows in this series
        for (let index = 0; index < listData.length; index++) {
            let matchRow = listData[index];

            if (matchRow.conid != rowData.conid || matchRow.memCategory != rowData.memCategory || matchRow.memType != rowData.memType ||
                matchRow.memAge != rowData.memAge || matchRow.shortname != rowData.shortname)
                continue; // not one of the series

            if (matchRow.id == rowData.id) {
                editListMasterRow = this.#editData.length;
                }
            this.#editData.push(matchRow);
        }

        // populate the form with the master row (the one with the edit select
        let seriesName = rowData.conid + '/' + rowData.memCategory + '/' + rowData.memType + '/' + rowData.memAge + '/' + rowData.shortname;
        document.getElementById('editMemListTitle').innerHTML = 'Edit Memlist Series - ' + rowData.id + ': ' + seriesName;
        document.getElementById('editMemListName').innerHTML = seriesName;
        document.getElementById('editMemListID').innerHTML = rowData.id;
        document.getElementById('editMemListConID').innerHTML = rowData.conid;
        this.#memListMasterRow= rowData.id;
        memListModalDirty = false;
        this.#memListModal.show();

        let bundle = false;
        let label = rowData.shortname;
        let notes = rowData.notes;
        let bundleList = '';
        if (this.#memListBundleContains && label != undefined) {
            let bundlePrefix = label.substring(0, 8);
            bundle = bundlePrefix == 'Bundle: ';
        }

        if (bundle) {
            // prep the fields for the bundle
            label = label.substring(8);
            let sep = notes.indexOf('/');
            if (sep > 0) {
                bundleList = notes.substring(0, sep);
                notes = notes.substring(sep + 1);
            }
        }

        document.getElementById('memListCategorySelect').value = rowData.memCategory;
        document.getElementById('memListAgeSelect').value = rowData.memAge;
        document.getElementById('memListTypeSelect').value = rowData.memType;
        document.getElementById('editMemListAtcon').value = rowData.atcon;
        document.getElementById('editMemListOnline').value = rowData.online;
        document.getElementById('editMemListLabel').value = label;
        document.getElementById('editMemListPrice').value = rowData.price;
        document.getElementById('editMemListStart').value = rowData.startdate;
        document.getElementById('editMemListEnd').value = rowData.enddate;

        document.getElementById('editMemListNotes').value = notes;
        let cartDesc = rowData.cartDesc == null ? '' : rowData.cartDesc;
        document.getElementById('editMemListCartDesc').innerHTML = cartDesc.trim();
        document.getElementById('editMemListGLNum').value = rowData.glNum;
        document.getElementById('editMemListGLLabel').value = rowData.glLabel;

        if (this.#memListBundleContains) {
            this.#memListBundleContains.value = bundleList;
            document.getElementById('editMemListBundle').value = bundle ? 'Y' : 'N';
            if (bundle)
                $('div[name="TScontains"]').show();
            else
                $('div[name="TScontains"]').hide();
        }

        this.reSortTimeSeries(false);

        // set up to edit the cart description
        if (tinyMCEInit) {
            // update the text block
            tinyMCE.get("editMemListCartDesc").focus();
            tinyMCE.get("editMemListCartDesc").load();
        } else {
            tinyMCE.init({
                selector: 'textarea#editMemListCartDesc',
                height: 400,
                min_height: 400,
                menubar: false,
                license_key: 'gpl',
                plugins: 'advlist lists image link charmap fullscreen help nonbreaking preview searchreplace',
                toolbar: [
                    'help undo redo searchreplace copy cut paste pastetext | fontsizeinput styles h1 h2 h3 h4 h5 h6 | ' +
                    'bold italic underline strikethrough removeformat | ' +
                    'visualchars nonbreaking charmap hr | ' +
                    'preview fullscreen ',
                    'alignleft aligncenter alignright alignnone | outdent indent | numlist bullist checklist | forecolor backcolor | link image'
                ],
                link_target_list: [
                    {title: 'None', value: ''},
                    {title: 'Same page', value: '_self'},
                    {title: 'New page', value: '_blank'},
                ],
                link_default_target: '_blank',
                content_style: 'body {font - family:Helvetica,Arial,sans-serif; font-size:14px }',
                placeholder: 'Edit the cart item description here...',
                auto_focus: 'editMemListCartDesc'
            });
            tinyMCEInit = true;
        }
    }

    open() {
        let script = "scripts/regadmin_getCondata.php";
        $.ajax({
            url: script,
            method: 'GET',
            data: 'year=' + this.#setup_type + '&type=all',
            success: function (data, textStatus, jhXHR) {
                checkRefresh(data);
                if (data['year'] == 'current') {
                    current.draw('current', data, textStatus, jhXHR);
                } else {
                    next.draw('next', data, textStatus, jhXHR);
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
        let _this = this;

        this.#memtable.clearFilter(true);
        this.#memtable.addRow({
            id: -99999,
            conid: this.#conid,
            shortname: 'new-row',
            price: 0,
            atcon: 'N',
            online: 'N',
            sortorder: 0,
            uses: 0,
            label: '',
            notes: '',
            glNum: '',
            glLabel: '',
        }, false).then(function (row) {
            row.getTable().setPageToRow(row).then(function () {
                row.getCell("id").getElement().style.backgroundColor = "#fff3cd";
                row.getCell("conid").getElement().style.backgroundColor = "#fff3cd";
                row.getCell("shortname").getElement().style.backgroundColor = "#fff3cd";
                row.getCell("price").getElement().style.backgroundColor = "#fff3cd";
                row.getCell("atcon").getElement().style.backgroundColor = "#fff3cd";
                row.getCell("online").getElement().style.backgroundColor = "#fff3cd";
                _this.checkMemlistUndoRedo();
            });
        });
    };

    memlist_rowMoved(row) {
        // first change the sort order entry for this row, to be one more than the one before, or if first, one less than the one after
        let sortValue = undefined;
        let sortCell = undefined;
        let copyRow = row.getPrevRow();
        if (copyRow !== false) {
            sortCell = copyRow.getCell('sort_order');
            sortValue = sortCell.getValue() + 1;
        } else {
            copyRow = row.getNextRow();
            if (copyRow !== false) {
                sortCell = copyRow.getCell('sort_order');
                sortValue = sortCell.getValue() - 1;
            }
        }
        if (sortValue !== undefined) {
            sortCell = row.getCell('sort_order');
            sortCell.setValue(sortValue);
        }
        this.#memlist_savebtn.innerHTML = "Save Changes*";
        this.#memlist_savebtn.disabled = false;
        this.#memlist_dirty = true;
        this.checkMemlistUndoRedo();
    }

    saveConlistComplete(data, textStatus, jhXHR) {
        this.#conlist_savebtn.innerHTML = "Save Changes";

        clear_message();
        let script = "scripts/regadmin_getCondata.php";
        $.ajax({
            url: script,
            method: 'GET',
            data: 'year=' + this.#setup_type + '&type=conlist',
            success: function (data, textStatus, jhXHR) {
                if (data['error']) {
                    show_message(data['error'], 'error');
                    return false;
                }
                checkRefresh(data);
                if (data['year'] == 'current') {
                    current.draw_conlist(data['year'], data, textStatus, jhXHR);
                } else {
                    next.draw_conlist(data['year'], data, textStatus, jhXHR);
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
            let invalids = this.#contable.validate();
            if (!invalids === true) {
                console.log(invalids);
                show_message("Conlist Table does not pass validation, please check for empty cells or cells in red", 'error');
                return false;
            }

            this.#conlist_savebtn.innerHTML = "Saving...";
            this.#conlist_savebtn.disabled = true;

            let script = "scripts/regadmin_updateCondata.php";

            let postdata = {
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
                    checkRefresh(data);
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

        let script = "scripts/regadmin_getCondata.php";
        $.ajax({
            url: script,
            method: 'GET',
            data: 'year=' + this.#setup_type + '&type=memlist',
            success: function (data, textStatus, jhXHR) {
                checkRefresh(data);
                if (data['year'] == 'current') {
                    current.draw_memlist(data['year'], data, textStatus, jhXHR);
                } else {
                    next.draw_memlist(data['year'], data, textStatus, jhXHR);
                }
            },
            error: function (jqXHR, textStatus, errorThrown) {
                showError("ERROR in " + script + ": " + textStatus, jqXHR);
                return false;
            }
        });
    }

    saveMemList() {
        let message = '';
        let valid = true;
        if (this.#memtable != null) {
            let invalids = this.#memtable.validate();
            if (invalids !== true) {
                message += "MemList Table does not pass validation, please check for empty cells or cells in red</br>";
                valid = false;
            }

            let tabledata = this.#memtable.getData();
            // validate any bundles
            for (let row of tabledata) {
                let label =  row.shortname;
                let notes = row.notes;
                if (label.substring(0, 8) == 'Bundle: ') {
                    let indexMark = notes.indexOf('/');
                    if (indexMark < 0) {
                        valid = false;
                        message += "Bundle " + row.id +
                            " is missing the bundle list on the front of the notes line, use the Edit button to correct the bundle.<br/>";
                        continue;
                    }
                    let containsList = notes.substring(0,indexMark).split(',');
                    for (let bundleItem of containsList) {
                        let bundleRow = this.#memtable.getRow(bundleItem);
                        if (bundleRow === false) {
                            valid = false;
                            message += 'For bundle ID ' + row.id + ', bundle item ' + bundleItem +
                                ' does not exist in the memList, use the Edit button to correct the bundle.<br/>';
                        }
                    }
                }
            }

            if (!valid) {
                show_message(message, 'error');
                return;
            }

            let script = "scripts/regadmin_updateCondata.php";
            let keys = Object.keys(tabledata);
            let yearaheadWarning = '';
            for (let i = 0; i < keys.length; i++) {
                let row = tabledata[keys[i]];
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

            let postdata = {
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
                    checkRefresh(data);
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

    downloadMemList(format) {
        if (this.#memtable == null)
            return;

        let filename = this.#conid + '_memlist';
        let tabledata = JSON.stringify(this.#memtable.getData("active"));
        let fieldList = [
            'id',
            'conid',
            {key: 'memCategory', label: 'Category'},
            {key: 'memType', label: 'Type'},
            {key: 'memAge', label: 'Age'},
            'shortname',
            'label',
            'price',
            'startdate',
            'enddate',
            'atcon',
            'online',
            'notes',
            'glNum',
            'glLabel',
            'sort_order',
        ];
        downloadFilePost(format, filename, tabledata, null, fieldList);
    }

    // items for editing the bundle contains items
    // closeBundleSel - close the tablle

    closeBundleSel() {
        if (this.#memListBundleTable) {
            this.#memListBundleTable.destroy();
            this.#memListBundleTable = null;
        }
        this.#containsField = null;
        this.#editMemListBundleDiv.hidden = true;
    }

    // editBundleContains - select the mem id's for this bundle
    editBundleContains(field, startField, endField) {
        this.closeBundleSel();
        this.#containsField = field;
        // fill the non bundle list data from current data
        let curMemList = this.#memtable.getRows();
        this.#nonBundleList = [];
        // convert the ISO datetimes to database format, for string compare
        let date = new Date(document.getElementById(startField).value);
        this.#rowStartDate = date.getFullYear()
            + '-' + ("00" + (date.getMonth() + 1)).slice(-2)
            + "-" + ("00" + date.getDate()).slice(-2)
            +  " " + ("00" + date.getHours()).slice(-2)
            + ":" + ("00" + date.getMinutes()).slice(-2)
            + ":" + ("00" + date.getSeconds()).slice(-2);
        date = new Date(document.getElementById(endField).value);
        this.#rowEndDate =  date.getFullYear()
            + '-' + ("00" + (date.getMonth() + 1)).slice(-2)
            + "-" + ("00" + date.getDate()).slice(-2)
            +  " " + ("00" + date.getHours()).slice(-2)
            + ":" + ("00" + date.getMinutes()).slice(-2)
            + ":" + ("00" + date.getSeconds()).slice(-2);
        for (let row of curMemList) {
            let rowdata = row.getData();
            // if it's a bundle, and the periods overlap (mem start <= bund end and mem end > bundle start
            if (rowdata.label.substring(0, 8) != 'Bundle: ' && rowdata.startdate < this.#rowEndDate && rowdata.enddate > this.#rowStartDate)
                this.#nonBundleList.push(rowdata);
        }

        this.#selValues = document.getElementById(field).value;
        if (this.#selValues == null)
            this.#selValues = ''
        else
            this.#selValues = ',' + this.#selValues + ',';

        this.#editMemListBundleDiv.hidden = false;

        this.#memListBundleTable = new Tabulator('#editMemlistBundleTable', {
            data: this.#nonBundleList,
            layout: "fitDataTable",
            index: "id",
            pagination: this.#nonBundleList.length > 25,
            paginationAddRow:"table",
            paginationSize: 99999,
            paginationSizeSelector: [10, 25, 50, 100, 250, true], //enable page size select element with these options
            columns: [
                {title: "ID", field: "id", width: 70, headerHozAlign:"right", hozAlign: "right",
                    headerFilter: "input", headerFilterFunc:numberHeaderFilter,
                },
                {title: "ConId", field: "conid", width: 70, headerWordWrap: true, headerHozAlign:"right", hozAlign: "right",  headerFilter: true, },
                {title: "Cat", field: "memCategory", width: 90, headerFilter: 'list', headerFilterParams: { values: this.#catListData }, },
                {title: "Type", field: "memType", width: 90, headerFilter: 'list', headerFilterParams: { values: this.#typeListData },  },
                {title: "Age", field: "memAge", width: 90, headerFilter: 'list', headerFilterParams: { values: this.#ageListData },  },
                {title: "Label", field: "label", width: 250, headerFilter: true, },
                {title: "Price", field: "price", width: 80, headerFilter: true, headerHozAlign:"right", hozAlign: "right", },
                {title: "Notes", field: "notes", width: 200, headerFilter: true,  formatter: "textarea", },
                {title: "Start Date", field: "startDate", width: 200, visible: this.#debugVisible, headerFilter: true,  },
                {title: "End Date", field: "endDate", width: 200, visible: this.#debugVisible, headerFilter: true, },
            ],
        });
        this.#memListBundleTable.on("cellClick", clickedSelection);
        setTimeout(setInitialSel, 100);
    }

    // table functions
    // setInitialSel - set the initial selected items based on the current values
    setInitialSel() {
        let rows = this.#memListBundleTable.getRows();
        for (let row of rows) {
            let index = row.getCell('id').getValue().toString();
            if (this.#selValues.includes(',' + index + ',')) {
                row.getCell('id').getElement().style.backgroundColor = "#C0FFC0";
            }
        }
        if (this.#nonBundleList.length > 25)
            this.#memListBundleTable.setPageSize(25);
    }

    // toggle the selection color of the clicked cell
    clickedSelection(e, cell) {
        let filtercell = cell.getRow().getCell('id');
        let value = filtercell.getValue();
        if (filtercell.getElement().style.backgroundColor) {
            filtercell.getElement().style.backgroundColor = "";
        } else {
            filtercell.getElement().style.backgroundColor = "#C0FFC0";
        }
    }

    // set all/clear all sections in table based on direction
    setBundleSel(direction) {
        let rows = this.#memListBundleTable.getRows();
        for (let row of rows) {
            if (row.getPosition() === false)
                continue;

            row.getCell('id').getElement().style.backgroundColor = direction ? "#C0FFC0" : "";
        }
    }

    // retrieve the selected rows and set the field values
    applyBundleSel() {
        // store all the fields back into the field
        let warning = '';
        let val = '';
        let price = 0;
        let rows = this.#memListBundleTable.getRows();
        for (let row of rows) {
            if (row.getCell('id').getElement().style.backgroundColor != '') {
                let rowData = row.getData();
                if (rowData.startdate > this.#rowStartDate) {
                    warning += '<br/>Bundle start date (' + this.#rowStartDate + ') starts before the bundle element ' + rowData.id + "'s start date (" +
                        rowData.startdate + ')';
                }
                if (rowData.enddate < this.#rowEndDate) {
                    warning += '<br/>Bundle end date (' + this.#rowEndDate + ') ends after the bundle element ' + rowData.id + "'s end date (" +
                        rowData.enddate + ')';
                }
                val += ',' + rowData.id;
                price += Number(rowData.price);
            }
        }

        let containsField =  document.getElementById(this.#containsField);
        if (warning.length > 0) {
            show_message("Warning: Bundle Date Mismatch Issues" + warning, 'warn', 'result_message_editMemList');
            containsField.style.backgroundColor = 'var(--bs-warning)';
        } else {
            containsField.style.backgroundColor = '';
        }
        if (val != '')
            val = val.substring(1);

        price = price.toFixed(2);

        containsField.value = val;
        if (this.#containsField == 'editMemListBundleContains') {
            this.#memListPrice.value = price;
            let idField = 'EMLTS' + editListMasterRow + '_Price';
            document.getElementById(idField).value = price;
            idField = 'EMLTS' + editListMasterRow + '_contains';
            document.getElementById(idField).value = val;

        } else {
            let idField = this.#containsField.replace('_contains', '_Price');
            document.getElementById(idField).value = price;
            idField = this.#containsField.replace('_contains', '_ID');
            let id = document.getElementById(idField).innerHTML;
            if (id == this.#memListMasterRow) {
                this.#memListBundleContains.value = val;
                this.#memListPrice.value = price;
            }
        }
        this.closeBundleSel();
        this.bundleContentsChanged();
    }

    // bundleChanged - the bundle flag changes from yes/no
    bundleChanged() {
        let bundle = document.getElementById('editMemListBundle').value;
        if (bundle == 'Y')
            $('div[name="TScontains"]').show();
        else
            $('div[name="TScontains"]').hide();
    }

    // placeholder for updating the contents of the entire bundle set for all time series
    bundleContentsChanged() {
        console.log("bundleChanged");
        memListModalDirty = true;
    }

    // placeholder for updating the contents of the entire bundle set for all time series
    tsBundleContentsChanged(id) {
        if (id == editListMasterRow)
            this.#memListBundleContains.value = document.getElementById('EMLTS' + id + '_contains').value;
        memListModalDirty = true;
    }


    // copy the fixed fields from the upper Edit block to the lower time series rows
    copyMemListChanges() {
        let cartDesc = tinyMCE.get('editMemListCartDesc').getContent();
        for (let index = 0; index < 10; index++) {
            if (document.getElementById('EMLTS' + index + '_Price').value != '' ||
                document.getElementById('EMLTS' + index + '_Start').value != '' ||
                document.getElementById('EMLTS' + index + '_End').value != '') {
                // has price, copy the data rows
                if (index >= this.#editData.length) {
                    this.#editData.push({id: 'new' + index, conid: this.#conid });
                    document.getElementById('EMLTS' + index + '_ID').innerHTML = this.#editData[index].id;
                    this.#editData[index].sort_order = this.#editData[index - 1].sort_order + 1;
                    document.getElementById('EMLTS' + index + '_Sort').value = this.#editData[index].sort_order;
                    this.#editData[index].startdate = document.getElementById('EMLTS' + index + '_Start').value;
                    this.#editData[index].enddate = document.getElementById('EMLTS' + index + '_End').value;
                    this.#editData[index].price = document.getElementById('EMLTS' + index + '_Price').value;
                }
                this.#editData[index].memCategory = document.getElementById('memListCategorySelect').value;
                this.#editData[index].memAge = document.getElementById('memListAgeSelect').value;
                this.#editData[index].memType = document.getElementById('memListTypeSelect').value;
                this.#editData[index].shortname = document.getElementById('editMemListLabel').value;
                this.#editData[index].notes = document.getElementById('editMemListNotes').value;
                this.#editData[index].cartDesc = cartDesc;
                this.#editData[index].atcon = document.getElementById('editMemListAtcon').value;
                this.#editData[index].online = document.getElementById('editMemListOnline').value;
                this.#editData[index].glNum = document.getElementById('editMemListGLNum').value;
                this.#editData[index].glLabel = document.getElementById('editMemListGLLabel').value;
                document.getElementById('EMLTS' + index + '_glNum').value = this.#editData[index].glNum;
                document.getElementById('EMLTS' + index + '_glLabel').value = this.#editData[index].glLabel;
                document.getElementById('EMLTS' + index + '_Atcon').value = this.#editData[index].atcon;
                document.getElementById('EMLTS' + index + '_Online').value = this.#editData[index].online;
            }
        }
        show_message("Fields copied", 'success', 'result_message_editMemList');
        //console.log(this.#editData);
    }

    // copy the cart description field from the upper Edit block to the lower time series rows
    copyCartDesc() {
        let cartDesc = tinyMCE.get('editMemListCartDesc').getContent();
        for (let index = 0; index < 10; index++) {
            if (document.getElementById('EMLTS' + index + '_Price').value != '' ||
                document.getElementById('EMLTS' + index + '_Start').value != '' ||
                document.getElementById('EMLTS' + index + '_End').value != '') {
                // has price, copy the description
                this.#editData[index].cartDesc = cartDesc;
            }
        }
        show_message("Cart Description copied", 'success', 'result_message_editMemList');
        //console.log(this.#editData);
    }

    // sequence the end dates for the time series
    resetEndDates() {
        for (let index = 0; index < this.#editData.length - 1; index++) {
            this.#editData[index].enddate = this.#editData[index + 1].startdate;
            document.getElementById('EMLTS' + index + '_End').value = this.#editData[index].enddate;
        }
        show_message("End Dates reset", 'success', 'result_message_editMemList');
    }

    // save the time series data back to the edit data array
    saveTimeSeries() {
        let index = 0;
        let bundle =  false;
        if (this.#bundlesEnabled)
            bundle = document.getElementById('editMemListBundle').value == 'Y';
        let notes = document.getElementById('editMemListNotes').value;
        let mark = notes.indexOf('/');
        if (mark > 0)
            notes = notes.substring(mark + 1);
        let orignotes = notes;
        let shortname = document.getElementById('editMemListLabel').value;
        if (bundle) {
            if (shortname.substring(0, 8) != 'Bundle: ')
                shortname = 'Bundle: ' + shortname;
        }
        for (let row = 0; row < 10; row++) {
            if (document.getElementById('EMLTS' + row + '_Price').value != '' ||
                document.getElementById('EMLTS' + row + '_Start').value != '' ||
                document.getElementById('EMLTS' + row + '_End').value != '') {
                // has non empty price or dates, copy the data rows
                if (index >= this.#editData.length) {
                    this.defaultNewRowValues(row, '');
                    this.#editData[index].shortname = shortname;
                    this.#editData[index].glNum = document.getElementById('editMemListGLNum').value;
                    this.#editData[index].glLabel = document.getElementById('editMemListGLLabel').value;
                }
                if (bundle) {
                    let contains = document.getElementById('EMLTS' + row + '_contains').value;
                    if (contains == undefined || contains == '') {
                        contains = this.computeBundleList(document.getElementById('editMemListBundleContains').value,
                            document.getElementById('EMLTS' + row + '_Start').value,
                            document.getElementById('EMLTS' + row + '_End').value);
                        document.getElementById('EMLTS' + row + '_contains').value = contains[0];
                        document.getElementById('EMLTS' + row + '_Price').value = contains[1];
                    }
                    notes = document.getElementById('EMLTS' + row + '_contains').value + '/' + orignotes;
                } else {
                    if (document.getElementById('EMLTS' + row + '_Price').value == '')
                        document.getElementById(editMemListPrice).value;
                    notes = orignotes;
                }
                this.#editData[index].sort_order = document.getElementById('EMLTS' + row + '_Sort').value;
                this.#editData[index].price = document.getElementById('EMLTS' + row + '_Price').value;
                this.#editData[index].startdate = toDBdate(document.getElementById('EMLTS' + row + '_Start').value);
                this.#editData[index].enddate = toDBdate(document.getElementById('EMLTS' + row + '_End').value);
                this.#editData[index].shortname = shortname;
                this.#editData[index].notes = notes;
                this.#editData[index].atcon = document.getElementById('EMLTS' + row + '_Atcon').value;
                this.#editData[index].online = document.getElementById('EMLTS' + row + '_Online').value;
                this.#editData[index].glNum = document.getElementById('EMLTS' + row + '_glNum').value;
                this.#editData[index].glLabel = document.getElementById('EMLTS' + row + '_glLabel').value;
                index++;
            }
        }
        // now copy the main row data into editData
        index = editListMasterRow;
        this.#editData[index].memCategory = document.getElementById('memListCategorySelect').value;
        this.#editData[index].memAge = document.getElementById('memListAgeSelect').value;
        this.#editData[index].memType = document.getElementById('memListTypeSelect').value;
        this.#editData[index].shortname = document.getElementById('editMemListLabel').value;
        this.#editData[index].notes = document.getElementById('editMemListNotes').value;
        this.#editData[index].cartDesc = tinyMCE.get('editMemListCartDesc').getContent();
        this.#editData[index].glNum = document.getElementById('editMemListGLNum').value;
        this.#editData[index].glLabel = document.getElementById('editMemListGLLabel').value;
    }

    reSortTimeSeries(saveFirst = false) {
        if (saveFirst)
            this.saveTimeSeries();

        // now sort the rows into date order and display them
        this.#editData.sort(function (a, b) {
            if (a.startdate < b.startdate)
                return -1;

            if (a.enddate < b.enddate)
                return -1;

            if (a.startdate > b.startdate)
                return 1;

            if (a.enddate > b.enddate)
                return 1;

            if (a.price < b.price)
                return -1;

            if (a.price > b.price)
                return 1;

            return a.sort_order - b.sort_order;
        });

        let bundle = false;
        let bundleList = '';
        if (this.#memListBundleContains) {
            if (this.#bundlesEnabled) {
                bundle = document.getElementById('editMemListBundle').value == 'Y';
                bundleList = this.#memListBundleContains.value;
            }
        }

        // fill in the bottom rows from the edit array
        for (let index = 0; index < this.#editData.length; index++) {
            let row = this.#editData[index];

            let label = row.label;
            let notes = row.notes;
            let rowBundleList = '';
            let price = row.price;

            if (bundle) {
                // prep the fields for the bundle
                let sep = notes.indexOf('/');
                if (sep > 0) {
                    rowBundleList = notes.substring(0, sep);
                    notes = notes.substring(sep + 1);
                } else {
                    let contains = this.computeBundleList(bundleList, row.startdate, row.enddate);
                    rowBundleList = contains[0];
                    price = contains[1];

                }
                document.getElementById('EMLTS' + index + '_contains').value = rowBundleList;
                document.getElementById('EMLTS' + index + '_Price').value = price;
            }

            if (this.#memListMasterRow == row.id) {
                editListMasterRow = index;
            }

            document.getElementById('EMLTS' + index + '_ID').innerHTML = row.id;
            document.getElementById('EMLTS' + index + '_Sort').value = row.sort_order;
            document.getElementById('EMLTS' + index + '_Price').value = row.price;
            document.getElementById('EMLTS' + index + '_Start').value = row.startdate;
            document.getElementById('EMLTS' + index + '_End').value = row.enddate;
            document.getElementById('EMLTS' + index + '_Atcon').value = row.atcon;
            document.getElementById('EMLTS' + index + '_Online').value = row.online;
            document.getElementById('EMLTS' + index + '_glNum').value = row.glNum;
            document.getElementById('EMLTS' + index + '_glLabel').value = row.glLabel;
        }

        // clear the remaining bottom rows
        for (let index = this.#editData.length; index < 10; index++) {
            document.getElementById('EMLTS' + index + '_ID').innerHTML = '';
            document.getElementById('EMLTS' + index + '_Sort').value = '';
            document.getElementById('EMLTS' + index + '_Price').value = '';
            document.getElementById('EMLTS' + index + '_Start').value = '';
            document.getElementById('EMLTS' + index + '_End').value = '';
            document.getElementById('EMLTS' + index + '_Atcon').value = 'N';
            document.getElementById('EMLTS' + index + '_Online').value = 'N';
            document.getElementById('EMLTS' + index + '_glNum').value = '';
            document.getElementById('EMLTS' + index + '_glLabel').value = '';
            if (bundle) {
                document.getElementById('EMLTS' + index + '_contains').value = '';
            }
        }

        clear_message('result_message_editMemList');
    }

    // compute a new bundle list for this row from the start and end dates
    computeBundleList(bundleList, startdate, enddate) {
        let newBundleList = '';
        let oldList = bundleList.split(',');
        let memTableData = null;
        let price = 0;

        if (startdate.indexOf('T') >= 0)
            startdate = toDBdate(startdate);
        if (enddate.indexOf('T') >= 0)
            enddate = toDBdate(enddate);
        for (let id of oldList) {
            let memRow = this.#memtable.getRow(id).getData();
            if (memRow.startdate <= startdate && memRow.enddate >= enddate && memRow.shortname.substring(0, 8) != 'Bundle: ') {
                newBundleList += ',' + id;
                price += Number(memRow.price);
            } else {
                // now look for new matches as if the dates changed
                if (memTableData == null)
                    memTableData = this.#memtable.getData();
                let foundRow = null;
                for (let row of memTableData) {
                    // check for a new match in category, type, age, label, as well as date range
                    if (row.memCategory != memRow.memCategory)
                        continue;
                    if (row.memType != memRow.memType)
                        continue;
                    if (row.memAge != memRow.memAge)
                        continue;
                    if (row.startdate > startdate || row.enddate < enddate)
                        continue;

                    if (row.shortname.substring(0, 8) == 'Bundle: ')
                        continue;

                    foundRow = row;
                    break;
                }
                if (foundRow != null) {
                    newBundleList += ',' + foundRow.id;
                    price += Number(foundRow.price);
                }
            }
        }

        if (newBundleList != '')
            newBundleList = newBundleList.substring(1);

        return [newBundleList, price];
    }

    // save the modal data back to the table and close the modal
    editMemListSave() {
        if (this.#memListBundleContains) {
            // check if bundle is Yes
            let valid = true;
            let message = '';
            let bundle = false
            if (this.#bundlesEnabled)
                bundle = document.getElementById('editMemListBundle').value == 'Y';
            if (bundle) {
                // validate the bundle contents for each row in the table
                for (let i = 0; i < 10; i++) {
                    let contains = document.getElementById('EMLTS' + i + '_contains').value;
                    if (contains != '') {
                        let containsList = contains.split(',');
                        for (let c = 0; c < containsList.length; c++) {
                            // validate that this element is a memlistid in the table
                            let id = containsList[c];
                            let row = this.#memtable.getRow(id);
                            if (row === false) {
                                valid = false;
                                let memId = document.getElementById('EMLTS' + i + '_ID').innerHTML;
                                message += 'For bundle ID ' + memId + ', bundle item ' + id + ' does not exist in the memList.<br/>';
                            }
                        }
                    }
                }
                if (!valid) {
                    show_message(message, 'error', 'result_message_editMemList');
                    return;
                }
                // rebuild the bundle values: note, label
                let notes = document.getElementById('editMemListNotes');
                let note = document.getElementById('editMemListBundleContains').value + '/' + notes.value;
                notes.value = note;
                let labelEl = document.getElementById('editMemListLabel');
                let label = 'Bundle: ' + labelEl.value;
                labelEl.value = label;

                // now copy that to the time series
            }
        }
        this.saveTimeSeries(); // write the data back to the this.#editData array

        // copy the edit data back to the main array
        this.#memtable.updateOrAddData(this.#editData);
        for (let index = 0; index < this.#editData.length; index++) {
            let id = this.#editData[index].id;
            this.#memtable.getRow(id).getElement().style.backgroundColor = "#fff3cd";
        }
        this.#memtable.setSort([{column:"sort_order", dir: "asc"}]);
        this.#memListModal.hide();

        // mark that we need to save the screen
        this.#memlist_savebtn.innerHTML = "Save Changes*";
        this.#memlist_savebtn.disabled = false;
        this.#memlist_dirty = true;
        this.checkMemlistUndoRedo()
    }

    // cancel check dirty flag
    editMemListCancel() {
        if (memListModalDirty) {
            if (!confirm('You have unsaved changes you need to save back to the underlying page with the "Save Changes" button.' +
                '\n\nDo you wish to discard those changes?')) {
                return;
            }

            memListModalDirty = false;
        }
        this.#memListModal.hide();
    }
};

// static functions to call appropriate class
function editMemListSave() {
    if (activeConSetup == 'next')
        return next.editMemListSave();

    return current.editMemListSave();
}

function copyMemListChanges() {
    if (activeConSetup == 'next')
        return next.copyMemListChanges();

    return current.copyMemListChanges();
}

function copyCartDesc() {
    if (activeConSetup == 'next')
        return next.copyCartDesc();

    return current.copyCartDesc();
}


function resetEndDates() {
    if (activeConSetup == 'next')
        return next.resetEndDates();

    return current.resetEndDates();
}

function reSortTimeSeries() {
    if (activeConSetup == 'next')
        return next.reSortTimeSeries(true);

    return current.reSortTimeSeries(true);
}

function editMemListCancel() {
    if (activeConSetup == 'next')
        return next.editMemListCancel();

    return current.editMemListCancel();
}

// top section request to edit bundle contains field with select list
function editBundleContains(field, startField, endField) {
    if (activeConSetup == 'next')
        next.editBundleContains(field, startField, endField);
    else
        current.editBundleContains(field, startField, endField);
}

function setInitialSel() {
    if (activeConSetup == 'next')
        next.setInitialSel();
    else
        current.setInitialSel();
}

function clickedSelection(e, cell) {
    if (activeConSetup == 'next')
        next.clickedSelection(e, cell);
    else
        current.clickedSelection(e, cell);
}

// top section bundle button actions
function closeBundleSel() {
    if (activeConSetup == 'next')
        next.closeBundleSel();
    else
        current.closeBundleSel();
}

function setBundleSel(direction) {
    if (activeConSetup == 'next')
        next.setBundleSel(direction);
    else
        current.setBundleSel(direction);
}

function bundleChanged() {
    if (activeConSetup == 'next')
        next.setBundleSelbundleChanged();
    else
        current.bundleChanged();
}

function bundleContentsChanged() {
    if (activeConSetup == 'next')
        next.bundleContentsChanged();
    else
        current.bundleContentsChanged();
}

function tsBundleContentsChanged(id) {
    if (activeConSetup == 'next')
        next.tsBundleContentsChanged(id);
    else
        current.tsBundleContentsChanged(id);
}

function applyBundleSel() {
    if (activeConSetup == 'next')
        next.applyBundleSel();
    else
        current.applyBundleSel();
}

// top section edited price, set bottom screen
function priceChange(masterRow) {
    document.getElementById('EMLTS' + masterRow + '_Price').value = document.getElementById('editMemListPrice').value;
    memListModalDirty = true;
}

// top section edited startdate, set bottom screen
function startdateChange(masterRow) {
    document.getElementById('EMLTS' + masterRow + '_Start').value = toDBdate(document.getElementById('editMemListStart').value);
    memListModalDirty = true;
}

// top section edited enddate, set bottom screen
function enddateChange(masterRow) {
    document.getElementById('EMLTS' + masterRow + '_End').value = toDBdate(document.getElementById('editMemListEnd').value);
    memListModalDirty = true;
}

// top section edited atcon, set bottom screen
function atconChange(masterRow) {
    document.getElementById('EMLTS' + masterRow + '_Atcon').value = document.getElementById('editMemListAtcon').value;
    memListModalDirty = true;
}

// top section edited online, set bottom screen
function onlineChange(masterRow) {
    document.getElementById('EMLTS' + masterRow + '_Online').value = document.getElementById('editMemListOnline').value;
    memListModalDirty = true;
}

// top section edited glNum, set bottom screen
function glNumChange(masterRow) {
    document.getElementById('EMLTS' + masterRow + '_glNum').value = document.getElementById('editMemListGLNum').value;
    memListModalDirty = true;
}

// top section edited glLabel, set bottom screen
function glLabelChange(masterRow) {
    document.getElementById('EMLTS' + masterRow + '_glLabel').value = document.getElementById('editMemListGLLabel').value;
    memListModalDirty = true;
}

// bottom section edited price, set top screen
function tsPriceChange(row) {
    if (row == editListMasterRow) {
        document.getElementById('editMemListPrice').value = document.getElementById('EMLTS' + row + '_Price').value;
    }
    // so align works need to update the date field in the table
    memListModalDirty = true;
    if (activeConSetup == 'next')
        next.setEditDataPrice(row, document.getElementById('EMLTS' + row + '_Price').value);
    else
        current.setEditDataPrice(row, document.getElementById('EMLTS' + row + '_Price').value);
    memListModalDirty = true;
}

// bottom section edited startdate, set top screen
function tsStartChange(row) {
    if (row == editListMasterRow) {
        document.getElementById('editMemListStart').value = document.getElementById('EMLTS' + row + '_Start').value;
    }
    // so align works need to update the date field in the table
    memListModalDirty = true;
    if (activeConSetup == 'next')
        next.setEditDataStartDate(row, document.getElementById('EMLTS' + row + '_Start').value);
    else
        current.setEditDataStartDate(row, document.getElementById('EMLTS' + row + '_Start').value);
}

// bottom section edited enddate, set top screen
function tsEndChange(row) {
    if (row == editListMasterRow) {
        document.getElementById('editMemListEnd').value = document.getElementById('EMLTS' + row + '_End').value;
    }
    memListModalDirty = true;
    if (activeConSetup == 'next')
        next.setEditDataEndDate(row, document.getElementById('EMLTS' + row + '_End').value);
    else
        current.setEditDataEndDate(row, document.getElementById('EMLTS' + row + '_End').value);
}

// bottom section edited atcon, set top screen
function tsAtconChange(row) {
    if (row == editListMasterRow) {
        document.getElementById('editMemListAtcon').value = document.getElementById('EMLTS' + row + '_Atcon').value;
        memListModalDirty = true;
    }
}

// bottom section edited online, set top screen
function tsOnlineChange(row) {
    if (row == editListMasterRow) {
        document.getElementById('editMemListOnline').value = document.getElementById('EMLTS' + row + '_Online').value;
        memListModalDirty = true;
    }
}

// bottom section edited glnum, set top screen
function tsGlNumChange(row) {
    if (row == editListMasterRow) {
        document.getElementById('editMemListGLNum').value = document.getElementById('EMLTS' + row + '_glNum').value;
        memListModalDirty = true;
    }
}

// bottom section edited gllabel, set top screen
function tsGlLabelChange(row) {
    if (row == editListMasterRow) {
        document.getElementById('editMemListGLLabel').value = document.getElementById('EMLTS' + row + '_glLabel').value;
        memListModalDirty = true;
    }
}
