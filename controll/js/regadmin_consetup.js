//import { TabulatorFull as Tabulator } from 'tabulator-tables';

var activeConSetup = 'none';
var memListModalDirty = false;
var tinyMCEInit = false;
var currencyFmt = null;

class consetup {
    #debug = 0;
    #debugVisible = false;
    #active = false;
    #bundlesEnabled = false;
    #contable = null;
    #memtable = null;
    #datetable = null;
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
    #daterange_dirty = false;
    #daterange_savebtn = null;
    #daterange_undobtn = null;
    #daterange_redobtn = null;
    #daterange_addrowbtn = null;
    #daterange_div = null;
    #memlist_dirty = false;
    #memlist_savebtn = null;
    #memlist_undobtn = null;
    #memlist_redobtn = null;
    #memlist_addrowbtn = null;
    #memlist_div = null;
    #memList = null;
    #memListNewRowNumber = -99999;
    #message_div = null;
    #setup_type = null;
    #setup_title = null;
    #catListData = null;
    #typeListData = null;
    #ageListData = null;
    #yaAgeListData = null;
    #catListSelect = null;
    #typeListSelect = null;
    #ageListOptions = null;
    #ageListSelect = null;
    #yaAgeListOptions = null;
    #yaAgeListSelect = null;
    #ageListSame = true;
    #memListModal = null;
    #memListMasterRow = null;
    #editData = null;
    #editDataCurrentRow = null;
    #editDataCurrentMemId = null;
    #paginationDiv = null;
    // edit bundle items
    #editMemListBundleContainsRow = null;
    #memListBundleContains = null;
    #memListBundleTable = null;
    #memListPrice = null;
    #selValues = null;
    #editMemListBundleDiv = null;
    #nonBundleList = [];
    #rowStartDate = '';
    #rowEndDate = '';
    #locale = 'en-us';
    #currencyFmt = null;
    #memListPagination = false;
    #memTablePagination = false;
    // memListDates items
    #memListDates = null;
    #nicknameSelectOptions = null;
    #nickNameData = null;
    #nickNameDataIdx = null;
    #deleteIcon = String.fromCodePoint(parseInt("1F5D1", 16));
    #deletedIcon = '<span style="color:red;"><b>Del</b></span>';

    // constants
    #enumYN = ['Y', 'N'];
    #validateFields = [
        { field: 'memListCategorySelect', message: "Category is required" },
        { field: 'memListTypeSelect', message: "Type is required" },
        { field: 'memListAgeSelect', message: "Age is required" },
        { field: 'editMemListLabel', message: "Label is required, must be less than 64 characters, and cannot be new-row", len: 64, not: "new-row" },
    ];

    constructor(setup_type) {
        this.#debug = Number(config.debug);
        this.#locale = config.locale;
        this.#currencyFmt = new Intl.NumberFormat(this.#locale, {
            style: 'currency',
            currency: config.currency,
        });
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
        this.#editMemListBundleContainsRow = document.getElementById('editMemListBundleContainsRow');
        this.#memListPrice = document.getElementById('editMemListPrice');
        this.#editMemListBundleDiv = document.getElementById('editMemListBundleDiv');
        if (this.#editMemListBundleDiv)
            this.#editMemListBundleDiv.hidden = true;
        if (this.#editMemListBundleContainsRow)
            this.#editMemListBundleContainsRow.hidden = true;
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

    // set undo / redo status for date ranges)
    checkDateRangeUndoRedo() {
        let undosize = this.#datetable.getHistoryUndoSize();
        this.#daterange_undobtn.disabled = undosize <= 0;
        this.#daterange_redobtn.disabled = this.#datetable.getHistoryRedoSize() <= 0;
        return undosize;
    }

    daterange_dataChanged(data) {
        //data - the updated table data
        if (!this.#daterange_dirty) {
            this.#daterange_savebtn.innerHTML = "Save Changes*";
            this.#daterange_savebtn.disabled = false;
            this.#daterange_dirty = true;
        }
        this.checkDateRangeUndoRedo();
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
    }

    draw(year, data, textStatus, jhXHR) {
        let _this = this;
        //console.log('in draw');
        //console.log(data);

        // save off the select list data
        this.#catListData = data['memCats'];
        this.#typeListData = data['memTypes'];
        if (data['ageTypes'] && Array.isArray(data['ageTypes']))
            this.#ageListData = data['ageTypes'];
        else
            this.#ageListData = [];

        if (data['yaAgeTypes'] && Array.isArray(data['yaAgeTypes']))
            this.#yaAgeListData = data['yaAgeTypes'];
        else
            this.#yaAgeListData = [];

        // build options for comparison
        this.#ageListOptions = '';
        for (let index = 0; index < this.#ageListData.length; index++) {
            let age = this.#ageListData[index];
            this.#ageListOptions += "\n<option value='" + age + "'>" + age + "</option>";
        }
        this.#yaAgeListOptions = '';
        for (let index = 0; index < this.#yaAgeListData.length; index++) {
            let age = this.#yaAgeListData[index];
            this.#yaAgeListOptions += "\n<option value='" + age + "'>" + age + "</option>";
        }

        this.#ageListSame = this.#yaAgeListOptions == null || this.#ageListOptions == this.#yaAgeListOptions || this.#yaAgeListOptions == '';

        let html = `
<div class="container-fluid">
    <div class="row">
        <div class="col-sm-5"><h5><strong>` + this.#setup_title + ` Convention Data:</strong></h5></div>
        <div class="col-sm-1"></div>
        <div class="col-sm-6"><h5><strong>` + this.#setup_title + ` Convention Price Break Date Ranges:</strong></h5></div>
    </div>
    <div class="row">
        <div class="col-sm-5">
            <div class="container-fluid p-0 m-0">
                <div class="row">
                    <div class="col-sm-12 p-0 ms-3" id="` + this.#setup_type + `-conlist"></div>
                </div>
                <div class="row pt-2">
                    <div class="col-sm-12">
                        <div id="conlist-buttons">  
                            <button id="` + this.#setup_type + `conlist-undo" type="button" class="btn btn-secondary btn-sm" 
                                onclick="` + this.#setup_type + `.undoConlist(); return false;" disabled>Undo</button>
                            <button id="` + this.#setup_type + `conlist-redo" type="button" class="btn btn-secondary btn-sm"
                                onclick="` + this.#setup_type + `.redoConlist(); return false;" disabled>Redo</button>
                            <button id="` + this.#setup_type + `conlist-save" type="button" class="btn btn-primary btn-sm"
                                onclick="` + this.#setup_type + `.saveConlist(); return false;" disabled>Save Changes</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-1"></div>
         <div class="col-sm-5">
            <div class="container-fluid p-0 m-0">
                <div class="row">
                    <div class="col-sm-12 p-0 ms-3" id="` + this.#setup_type + `-daterange"></div>
                </div>
                <div class="row pt-2">
                    <div class="col-sm-12">
                        <div id="conlist-buttons">  
                            <button id="` + this.#setup_type + `daterange-undo" type="button" class="btn btn-secondary btn-sm" 
                                onclick="` + this.#setup_type + `.undoDateRange(); return false;" disabled>Undo</button>
                            <button id="` + this.#setup_type + `daterange-redo" type="button" class="btn btn-secondary btn-sm"
                                onclick="` + this.#setup_type + `.redoDateRange(); return false;" disabled>Redo</button>
                            <button id="` + this.#setup_type + `daterange-addrow" type="button" class="btn btn-secondary btn-sm"
                                onclick="` + this.#setup_type + `.addrowDateRange(); return false;">Add New</button>
                            <button id="` + this.#setup_type + `daterange-save" type="button" class="btn btn-primary btn-sm"
                                onclick="` + this.#setup_type + `.saveDateRange(); return false;" disabled>Save Changes</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-sm-6" id="` + this.#setup_type + `-daterange"></div>
    </div>

    <div class="row mt-3">
        <div class="col-sm-6">
            <h5><strong>` + this.#setup_title + ` Membership Types:</strong></h5>
        </div>
       <div class="col-sm-5 text-end">
           <strong><a href="markdown.php?mdf=md/DateFiltering.md" target="_new">How to filter on the Price Break column</a></strong>
       </div>
    </div>
`;
        if (!this.#ageListSame) {
            html += `
<h5 class="warncolor">Warning: Current and Next Convention Year Age Lists Differ</h5>
            `;
        }
        html += `
<p><strong>NOTE:</strong> All date ranges are '>=' Start Date and '<' End Date, so the End Date of one period should be the start date of the next.</p>
<div id="` + this.#setup_type + `-memlist"></div>
<div class='row mt-2 mb-3' id='reglist-csv-div'>
    <div class="col-sm-auto p-1 ps-3 pe-3 tabulator-paginator paginationBGColor" id="` + this.#setup_type + `PaginationDiv"></div>
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
        this.#daterange_savebtn = document.getElementById(this.#setup_type + 'daterange-save');
        this.#daterange_undobtn = document.getElementById(this.#setup_type + 'daterange-undo');
        this.#daterange_redobtn = document.getElementById(this.#setup_type + 'daterange-redo');
        this.#daterange_addrowbtn = document.getElementById(this.#setup_type + 'daterange-addrow')
        this.#daterange_div = document.getElementById(this.#setup_type + '-daterange');
        this.#memlist_div = document.getElementById(this.#setup_type + '-memlist');
        this.#memlist_savebtn = document.getElementById(this.#setup_type + 'memlist-save');
        this.#memlist_undobtn = document.getElementById(this.#setup_type + 'memlist-undo');
        this.#memlist_redobtn = document.getElementById(this.#setup_type + 'memlist-redo')
        this.#memlist_addrowbtn = document.getElementById(this.#setup_type + 'memlist-addrow')

        this.draw_conlist(year, data, textStatus, jhXHR);
        this.draw_daterange(year, data, textStatus, jhXHR);
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
            (this.#setup_type == 'next') ? ' After the current year is set up, ask your admin to run the "Build &lt;id&gt; Setup" ' +
                'from the home page before continuing to the next year setup.' : '';

        } else {
            this.#contable = new Tabulator('#' + this.#setup_type + '-conlist', {
                maxHeight: "400px",
                history: true,
                data: [data['conlist']],
                layout: "fitColumns",
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
                        widthGrow: 3,
                        editor: "input",
                        editorParams: {elementAttributes: {maxlength: "40"}},
                        validator: "required"
                    },
                    {title: "Start Date", field: "startdate", widthGrow: 1, headerSort: false, editor: "date", validator: "required"},
                    {title: "End Date", field: "enddate", widthGrow: 1, headerSort: false, editor: "date", validator: "required"},
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

    draw_daterange(year, data, textStatus, jhXHR) {
        let _this = this;
        this.#daterange_dirty = false;

        if (this.#datetable != null) {
            // just replace the data
            this.#datetable.replaceData(data['dateRanges']);
            this.#daterange_dirty = false;
        } else {
            this.#datetable = new Tabulator('#' + this.#setup_type + '-daterange', {
                maxHeight: "400px",
                history: true,
                movableRows: true,
                data: data['dateRanges'],
                layout: "fitColumns",
                columns: [
                    {rowHandle: true, formatter: "handle", frozen: true, width: 30, minWidth: 30, maxWidth: 30, headerSort: false},
                    {
                        title: "Del", field: "uses", formatter: deleteicon, hozAlign: "center", headerSort: false,
                        cellClick: function (e, cell) {
                            deleterow(e, cell.getRow());
                        }
                    },
                    {title: "ID", field: "id", width: 50, headerSort: false, visible: false},
                    {field: "dateRangeKey", visible: false,},
                    {
                        title: "Nick Name", field: "nickName", headerSort: true, headerWordWrap: true,
                        width: 250, editor: "input", editorParams: {elementAttributes: {maxlength: "16"}},
                    },
                    {
                        title: "Start Date", field: "startdate", widthGrow: 3, editor: "datetime", validator: "required",
                        headerFilter: "input", headerFilterFunc: dateStringHeaderFilter, headerFilterFuncParams: {field: 'startdate'},
                    },
                    {
                        title: "End Date", field: "enddate", widthGrow: 3, editor: "datetime", validator: "required",
                        headerFilter: "input", headerFilterFunc: dateStringHeaderFilter, headerFilterFuncParams: {field: 'enddate'},
                    },
                    {field: "to_delete", visible: false,}
                ],
            });

            if (this.#datetable) {
                this.#datetable.on("dataChanged", function (data) {
                    _this.daterange_dataChanged(data);
                });
                this.#datetable.on("cellEdited", cellChanged);
            }
        }
    };

    draw_memlist(year, data, textStatus, jhXHR) {
        let _this = this;

        if (data.hasOwnProperty('memListDates'))
            this.#memListDates = data['memListDates'];
        else
            this.#memListDates = [];

        if (this.#memtable == null) {
            // build the select lists
            this.#catListSelect = "<select name='memListCategorySelect' id='memListCategorySelect' onchange='memListModalMarkDirty(true);'>";
            for (let index = 0; index < this.#catListData.length; index++) {
                let cat = this.#catListData[index];
                this.#catListSelect += "\n<option value='" + cat + "'>" + cat + "</option>";
            }
            this.#ageListSelect = "<select name='memListAgeSelect' id='memListAgeSelect' onchange='memListModalMarkDirty(true);'>\n" +
                this.#ageListOptions + "\n</select>";
            this.#yaAgeListSelect = "<select name='memListAgeSelect' id='memListAgeSelect' onchange='memListModalMarkDirty(true);'>\n" +
                this.#yaAgeListOptions + "\n</select>";
            this.#typeListSelect = "<select name='memListTypeSelect' id='memListTypeSelect' onchange='memListModalMarkDirty(true);'>";
            for (let index = 0; index < this.#typeListData.length; index++) {
                let type = this.#typeListData[index];
                this.#typeListSelect += "\n<option value='" + type + "'>" + type + "</option>";
            }
            this.#typeListSelect += "\n</select>";
            document.getElementById('editMemListCategory').innerHTML = this.#catListSelect;
            document.getElementById('editMemListAge').innerHTML = this.#ageListSelect;
            document.getElementById('editMemListType').innerHTML = this.#typeListSelect;
        }

        if (data['memlist'] == null) {
            show_message("Nothing defined yet", 'warn')
            data['memlist'] = [];
        }
        if (this.#memtable != null) {
            // just replace the data
            this.#memtable.replaceData(data['memlist']);
            this.#memlist_dirty = false;
        } else {
            this.#paginationDiv = document.getElementById(this.#setup_type + 'PaginationDiv');
            this.#paginationDiv.innerHTML = '';
            this.#paginationDiv.hidden = data['memlist'].length <= 25;

            this.#memTablePagination = data['memlist'].length > 25;
            let columns = [
                {rowHandle: true, formatter: "handle", frozen: true, width: 30, minWidth: 30, maxWidth: 30, headerSort: false},
                {
                    title: "Del", field: "uses", formatter: deleteicon, hozAlign: "center", headerSort: false,
                    cellClick: function (e, cell) {
                        deleterow(e, cell.getRow());
                    }
                },
                {title: "Edit", formatter: this.editbutton, formatterParams: {year: year}, hozAlign: "left", headerSort: false},
                {
                    title: "Sort", field: "sort_order", width: 75, headerSort: true, headerHozAlign: "right", hozAlign: "right", sorter: "number",
                },
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
                    editor: "list", editorParams: {values: data['memTypes'],},
                    headerFilter: true,
                    headerFilterParams: {values: data['memTypes'],}
                },
                {
                    title: "Age", field: "memAge",
                    editor: (this.#ageListSame ? "list" : ageListEditor), editorParams: {values: data['ageTypes'],},
                    headerFilter: (this.#ageListSame ? true : "input"), headerFilterParams: {values: data['ageTypes'],},
                },
                {
                    title: "Label", field: "shortname", width: 180,
                    tooltip: function (e, cell, onRendered) {
                        return cell.getRow().getCell("label").getValue();
                    },
                    editor: "input", editorParams: {elementAttributes: {maxlength: "64"}},
                    formatter: "textarea", headerFilter: true
                },
                {title: "Label", field: "label", visible: false},
                {
                    title: "Price Breaks", field: "priceRanges", width: 250, formatter: "html",
                    headerFilter: "input", headerFilterFunc: priceListHeaderFilter, headerFilterFuncParams: {field: 'priceRanges'},

                },
                {
                    title: "At", field: "atcon", editor: "list", editorParams: {values: this.#enumYN,},
                    headerSort: false, headerFilter: true, headerFilterParams: {values: this.#enumYN,}
                },
                {
                    title: "On", field: "online", editor: "list", editorParams: {values: this.#enumYN,},
                    headerSort: false, headerFilter: true, headerFilterParams: {values: this.#enumYN,}
                },
                {
                    title: "Notes", field: "notes", width: 200,
                    editor: "input", editorParams: {elementAttributes: {maxlength: "1024"}},
                    headerFilter: true, formatter: "textarea",
                },
                {
                    title: "Cart Desc", field: "cartDesc", width: 250,
                    headerFilter: true, formatter: "html",
                },
                {
                    title: "Category Badge Label Override", field: "badgeLabel", width: 140, headerWordWrap: true,
                    editor: "input", editorParams: {elementAttributes: {maxlength: "16"}}, headerFilter: true,
                },
                {
                    title: "Report Grouping", field: "rptGrouping", width: 140, headerWordWrap: true,
                    editor: "input", editorParams: {elementAttributes: {maxlength: "128"}}, headerFilter: true,
                },
                {field: "to_delete", visible: false,}, {field: "catBadgeLabel", visible: false,}
            ];

            this.#memList = data['memlist'];
            this.#memtable = new Tabulator('#' + this.#setup_type + '-memlist', {
                history: true,
                movableRows: true,
                data: data['memlist'],
                layout: "fitDataTable",
                pagination: this.#memTablePagination,
                paginationAddRow: "table",
                paginationSize: 25,
                paginationSizeSelector: [10, 25, 50, 100, 250, true], //enable page size select element with these options
                paginationElement: this.#paginationDiv,
                initialSort: [
                    {column: "sort_order", dir: "asc"}, //sort by this first
                ],
                columns: columns,
            });

            this.#memtable.on("dataChanged", function (data) {
                _this.memlist_dataChanged(data);
            });
            this.#memtable.on("rowMoved", function (row) {
                _this.memlist_rowMoved(row)
            });
            this.#memtable.on("cellEdited", memListCellChanged);
            this.#memlist_dirty = false;
        }
    };

    memListCellChanged(cell) {
        setCellChanged(cell);
    }

    ageListEditor(cell, onRendered, success, cancel, editorParams){
        //cell - the cell component for the editable cell
        //onRendered - function to call when the editor has been rendered
        //success - function to call to pass the successfully updated value to Tabulator
        //cancel - function to call to abort the edit and return to a normal cell
        //editorParams - params object passed into the editorParams column definition property

        //console.log('cell');
        //console.log(cell);
        let row = cell.getRow();
        //console.log('row');
        //console.log(row);
        let data = row.getData();
        let conid = data.conid;


        //create and style editor
        var ageEditor = document.createElement("select");
        ageEditor.setAttribute("id", "ageEditorSelect");
        ageEditor.setAttribute("name", "ageEditorSelect");
        //let conid = cell.getRow().getData().conid;
        ageEditor.innerHTML = conid == this.#conid ? this.#ageListOptions : this.#yaAgeListOptions;
        //create and style input
        ageEditor.style.padding = "3px";
        ageEditor.style.width = "100%";
        ageEditor.style.boxSizing = "border-box";

        ageEditor.value = cell.getValue();

        //set focus on the select box when the ageEditor is selected
        onRendered(function(){
            ageEditor.focus();
            ageEditor.style.css = "100%";
        });

        //when the value has been set, trigger the cell to update
        function successFunc(){
            success(ageEditor.value);
        }

        ageEditor.addEventListener("change", successFunc);
        ageEditor.addEventListener("blur", successFunc);

        //return the ageEditor element
        return ageEditor;
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

    // build the edit price break series modal and display it
    editSeries(index) {
        clear_message('result_message_editMemList');
        let row = this.#memtable.getRow(index);
        this.#editDataCurrentRow = row.getData();
        this.#editDataCurrentMemId = this.#editDataCurrentRow.id;
        if (this.#memListDates.hasOwnProperty(this.#editDataCurrentMemId))
            this.#editData = this.#memListDates[this.#editDataCurrentMemId];
        else
            this.#editData = [];
        document.getElementById('createSeriesBtn').hidden = this.#editData.length > 0;

        // populate the form with the master row (the one with the edit select
        let seriesName = this.#editDataCurrentRow.conid + '/' + this.#editDataCurrentRow.memCategory + '/' +
            this.#editDataCurrentRow.memType + '/' + this.#editDataCurrentRow.memAge + '/' + this.#editDataCurrentRow.shortname;
        document.getElementById('editMemListTitle').innerHTML = 'Edit Memlist Series - ' + this.#editDataCurrentRow.id + ': ' + seriesName;
        document.getElementById('editMemListName').innerHTML = seriesName;
        document.getElementById('editMemListID').innerHTML = this.#editDataCurrentRow.id;
        document.getElementById('editMemListConID').innerHTML = this.#editDataCurrentRow.conid;
        document.getElementById('editMemListAge').innerHTML =
            this.#editDataCurrentRow.conid == this.#conid ? this.#ageListSelect : this.#yaAgeListSelect;
        this.#memListMasterRow= this.#editDataCurrentRow.id;
        this.memListModalMarkDirty(false, true);
        this.#memListModal.show();

        let bundle = false;
        let label = this.#editDataCurrentRow.shortname;
        let notes = this.#editDataCurrentRow.notes;
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
                if (notes.substring(0, 1) == '\n')
                    notes = notes.substring(1);
            }
        }

        document.getElementById('memListCategorySelect').value = this.#editDataCurrentRow.memCategory;
        document.getElementById('memListAgeSelect').value = this.#editDataCurrentRow.memAge;
        document.getElementById('memListTypeSelect').value = this.#editDataCurrentRow.memType;
        document.getElementById('editMemListAtcon').value = this.#editDataCurrentRow.atcon;
        document.getElementById('editMemListOnline').value = this.#editDataCurrentRow.online;
        document.getElementById('editMemListLabel').value = label;
        document.getElementById('editMemListNotes').value = notes;
        let cartDesc = this.#editDataCurrentRow.cartDesc == null ? '' : this.#editDataCurrentRow.cartDesc;
        document.getElementById('editMemListCartDesc').innerHTML = cartDesc.trim();
        document.getElementById('editMemListRptGrouping').value = this.#editDataCurrentRow.rptGrouping;
        document.getElementById('catBadgeLabel').innerHTML = this.#editDataCurrentRow.catBadgeLabel;
        document.getElementById('editMemListBadgeLabel').value = this.#editDataCurrentRow.badgeLabel;

        if (this.#memListBundleContains) {
            this.#memListBundleContains.value = bundleList;
            document.getElementById('editMemListBundle').value = bundle ? 'Y' : 'N';
            if (this.#editMemListBundleContainsRow) {
                this.#editMemListBundleContainsRow.hidden = !bundle;
            }
        }

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
                auto_focus: 'editMemListCartDesc',
                setup: function(ed) {
                    ed.on('change', function(e) {
                        memListModalMarkDirty(true);
                    });
                }
            });
            tinyMCEInit = true;
        }

        // build the select list for the time series (do it every time because they could have edited it before here
        this.#nicknameSelectOptions = '<option value="">Custom</option>\n';
        if (this.#datetable != null) {
            this.#nickNameData = this.#datetable.getData();
            this.#nickNameDataIdx = {};
            for (let index = 0; index < this.#nickNameData.length; index++) {
                let nickName = this.#nickNameData[index].nickName;
                this.#nickNameDataIdx[nickName] = this.#nickNameData[index];
                this.#nicknameSelectOptions += '<option value="' + nickName + '">' + nickName + '</option>\n';
            }
        }
        // now fill in the time series from the data
        for (let index = 0; index < this.#editData.length; index++) {
            let row = this.#editData[index];
            document.getElementById('EMLTS' + index + '_ID').innerHTML = row.id;
            let uses = row.uses;
            if (uses == 0) {
                uses = row.to_delete == 1 ? this.#deletedIcon : this.#deleteIcon;
            }
            document.getElementById('EMLTS' + index + '_USES').innerHTML = uses;
            document.getElementById('EMLTS' + index + '_NickNameSel').innerHTML = "<select id='EMLTS" + index + "_NickName' " +
                "onchange='nickNameChanged(" + index + ")'>\n" + this.#nicknameSelectOptions + "</select>\n";
            document.getElementById('EMLTS' + index + '_NickName').value = row.nickName == null ? '' : row.nickName;
            document.getElementById('EMLTS' + index + '_to_delete').value = row.to_delete;
            document.getElementById('EMLTS' + index + '_Price').value = row.price;
            document.getElementById('EMLTS' + index + '_Start').value = row.startdate;
            document.getElementById('EMLTS' + index + '_End').value = row.enddate;
            if (config.useGL == 1) {
                document.getElementById('EMLTS' + index + '_glNum').value = row.glNum == null ? '' : row.glNum;
            }
        }

        // clear the remaining bottom rows
        for (let index = this.#editData.length; index < 10; index++) {
            document.getElementById('EMLTS' + index + '_ID').innerHTML = '';
            document.getElementById('EMLTS' + index + '_USES').innerHTML = '';
            document.getElementById('EMLTS' + index + '_NickNameSel').innerHTML = "<select id='EMLTS" + index + "_NickName' " +
                "onchange='nickNameChanged(" + index + ")'>\n" + this.#nicknameSelectOptions + "</select>\n";
            document.getElementById('EMLTS' + index + '_NickName').value = '';
            document.getElementById('EMLTS' + index + '_to_delete').value = 0;
            document.getElementById('EMLTS' + index + '_Price').value = '';
            document.getElementById('EMLTS' + index + '_Start').value = '';
            document.getElementById('EMLTS' + index + '_End').value = '';
            if (config.useGL == 1) {
                document.getElementById('EMLTS' + index + '_glNum').value = '';
            }
        }

        if (bundle)
            this.recomputeBundlePrice();
    }

    // Create a price break series of starter rows if the list is empty
    createNewSeries() {
        if (this.#nickNameData != null) {
            for (let index = 0; index < this.#nickNameData.length; index++) {
                let row = this.#nickNameData[index];
                document.getElementById('EMLTS' + index + '_ID').innerText = 'new';
                document.getElementById('EMLTS' + index + '_USES').innerText = this.#deleteIcon;
                document.getElementById('EMLTS' + index + '_NickName').value = row.nickName;
                document.getElementById('EMLTS' + index + '_Start').value = row.startdate;
                document.getElementById('EMLTS' + index + '_End').value = row.enddate;
                document.getElementById('EMLTS' + index + '_to_delete').value = 0;
            }
        }

        document.getElementById('createSeriesBtn').hidden = true;
    }

    // repopulate the dates if nick name is changed
    nickNameChanged(index) {
        let newName = document.getElementById('EMLTS' + index + '_NickName').value;
        if (newName == '')
            return; // do nothing if the select list is custom, as this means leave it alone as a custom date range

        // get the date range
        let row = this.#nickNameDataIdx[newName];
        if (row != null) {
            document.getElementById('EMLTS' + index + '_Start').value = row.startdate;
            document.getElementById('EMLTS' + index + '_End').value = row.enddate;
        }
        this.memListModalMarkDirty(true);
    }

    // clear the nick name if the a date is changed
    dateChanged(index) {
        this.memListModalMarkDirty(true);
        // changing the date manually will clear the nickname
        document.getElementById('EMLTS' + index + '_NickName').value = '';
    }

    // set the modal changed and change the save button
    memListModalMarkDirty(dirty, disable = false) {
        memListModalDirty = dirty;
        let saveButton = document.getElementById('editMemListSaveBtn');
        if (dirty) {
            saveButton.innerHTML = '*Save Changes';
            saveButton.disabled = false;
        } else {
            saveButton.innerHTML = 'Save Changes';
            if (disable)
                saveButton.disabled = true;
        }
    }

    // mark the row deleted for saving
    deleteRowChanged(index) {
        let usesElement = document.getElementById('EMLTS' + index + '_USES');
        let todeleteElement = document.getElementById('EMLTS' + index + '_to_delete');
        let uses = usesElement.innerHTML;
        if (uses != this.#deleteIcon && uses != this.#deletedIcon)
            return; // not deletable, just return

        if (uses == this.#deleteIcon) {
            // mark it deleted
            usesElement.innerHTML = this.#deletedIcon;
            todeleteElement.value = 1;
        } else {
            usesElement.innerHTML = this.#deleteIcon;
            todeleteElement.value = 0;
        }
        this.memListModalMarkDirty(true);
    }

    open() {
        let script = "scripts/regadmin_getCondata.php";
        clear_message();
        clearError();
        $.ajax({
            url: script,
            method: 'POST',
            data: { year: this.#setup_type, type: 'all' },
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
        if (this.#datetable != null) {
            this.#datetable.off("dataChanged");
            this.#datetable.off("cellEdited");
            this.#datetable.destroy();
            this.#datetable = null;
        }
        if (this.#memListBundleTable != null) {
            this.#memListBundleTable.off("dataChanged");
            this.#memListBundleTable.off("cellEdited");
            this.#memListBundleTable.destroy();
            this.#memListBundleTable = null;
        }

        this.#conlist_pane.innerHTML = '';
        this.#conlist_dirty = false;
        this.#memlist_dirty = false;
        memListModalDirty = false;
    };

    // conlist functions
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

    // daterange functions
    undoDateRange() {
        if (this.#datetable != null) {
            this.#datetable.undo();

            if (this.checkDateRangeUndoRedo() <= 0) {
                this.#daterange_dirty = false;
                this.#daterange_savebtn.innerHTML = "Save Changes";
                this.#daterange_savebtn.disabled = true;
            }
        }
    };

    redoDateRange() {
        if (this.#datetable != null) {
            this.#datetable.redo();

            if (this.checkDateRangeUndoRedo() > 0) {
                this.#daterange_dirty = true;
                this.#daterange_savebtn.innerHTML = "Save Changes*";
                this.#daterange_savebtn.disabled = false;
            }
        }
    };

    addrowDateRange() {
        let _this = this;

        this.#datetable.clearFilter(true);
        this.#datetable.addRow({
            id: -99999,
            conid: this.#conid,
            nickName: '',
        }, false).then(function (row) {
            setCellChanged(row.getCell("nickName"));
            setCellChanged(row.getCell("startdate"));
            setCellChanged(row.getCell("enddate"));
            _this.checkDateRangeUndoRedo();

        });
    };

    daterange_rowMoved(row) {
        this.#daterange_savebtn.innerHTML = "Save Changes*";
        this.#daterange_savebtn.disabled = false;
        this.#daterange_dirty = true;
        this.checkDateRangeUndoRedo();
    }

    // memlist functions
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
            id: this.#memListNewRowNumber,
            conid: this.#conid,
            memCategory: '',
            memType: '',
            memAge: '',
            shortname: 'new-row',
            priceRanges: '',
            atcon: 'N',
            online: 'N',
            notes: '',
            cartDesc: '',
            badgeLabel: '',
            catBadgeLabel: '',
            rptGrouping: '',
            label: '',
            uses: 0,
            sortorder: 0,
        }, false).then(function (row) {
            if (_this.#memTablePagination) {
                row.getTable().setPageToRow(row).then(function () {
                    setCellChanged(row.getCell("id"));
                    setCellChanged(row.getCell("conid"));
                    setCellChanged(row.getCell("shortname"));
                    setCellChanged(row.getCell("priceRanges"));
                    setCellChanged(row.getCell("atcon"));
                    setCellChanged(row.getCell("online"));
                    _this.checkMemlistUndoRedo();
                });
            } else {
                setCellChanged(row.getCell("id"));
                setCellChanged(row.getCell("conid"));
                setCellChanged(row.getCell("shortname"));
                setCellChanged(row.getCell("priceRanges"));
                setCellChanged(row.getCell("atcon"));
                setCellChanged(row.getCell("online"));
                _this.checkMemlistUndoRedo();
            }
        });
        // now open the modal for this row... as it's useless until edited.
        this.editSeries(this.#memListNewRowNumber);
        this.memListModalMarkDirty(true);
        this.#memListNewRowNumber++;
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
        clearError();
        let script = "scripts/regadmin_getCondata.php";
        $.ajax({
            url: script,
            method: 'POST',
            data: { year: this.#setup_type, type: 'conlist' },
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
            clearError();
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

    saveDateRangeComplete(data, textStatus, jhXHR) {
        this.#daterange_savebtn.innerHTML = "Save Changes";

        clear_message();
        clearError();
        let script = "scripts/regadmin_getCondata.php";
        $.ajax({
            url: script,
            method: 'POST',
            data: { year: this.#setup_type, type: 'datelist' },
            success: function (data, textStatus, jhXHR) {
                if (data['error']) {
                    show_message(data['error'], 'error');
                    return false;
                }
                checkRefresh(data);
                if (data['year'] == 'current') {
                    current.draw_daterange(data['year'], data, textStatus, jhXHR);
                } else {
                    next.draw_daterange(data['year'], data, textStatus, jhXHR);
                }
            },
            error: function (jqXHR, textStatus, errorThrown) {
                showError("ERROR in " + script + ": " + textStatus, jqXHR);
                return false;
            }
        });
    }

    saveDateRange() {
        if (this.#datetable != null) {
            let invalids = this.#datetable.validate();
            if (!invalids === true) {
                console.log(invalids);
                show_message("Date Range Table does not pass validation, please check for empty cells or cells in red", 'error');
                return false;
            }

            this.#daterange_savebtn.innerHTML = "Saving...";
            this.#daterange_savebtn.disabled = true;

            let script = "scripts/regadmin_updateCondata.php";

            let postdata = {
                ajax_request_action: this.#setup_type,
                tabledata: JSON.stringify(this.#datetable.getData()),
                tablename: "daterange",
                indexcol: "id"
            };
            clear_message();
            clearError();
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
                            current.daterange_dataChanged(data);
                        } else {
                            next.daterange_dataChanged(data);
                        }
                        return false;
                    } else {
                        show_message(data['success'], 'success');
                    }
                    checkRefresh(data);
                    if (data['year'] == 'current') {
                        current.saveDateRangeComplete(data, textStatus, jhXHR);
                    } else {
                        next.saveDateRangeComplete(data, textStatus, jhXHR);
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

        let message = '';
        if (data['success'])
            message = data['success'];
        clear_message();
        clearError();
        let script = "scripts/regadmin_getCondata.php";
        $.ajax({
            url: script,
            method: 'POST',
            data: { year: this.#setup_type, type: 'memlist' },
            success: function (data, textStatus, jhXHR) {
                checkRefresh(data);
                if (data['year'] == 'current') {
                    current.draw_memlist(data['year'], data, textStatus, jhXHR);
                    if (message != '')
                        show_message(message, 'success');
                } else {
                    next.draw_memlist(data['year'], data, textStatus, jhXHR);
                    if (message != '')
                        show_message(message, 'success');
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
                if (label.length > 64) {
                    valid = false;
                    message += "Label (shortname) of row " + row.memId + " is too long at " + label.length + ", it cannot exceed 64 characters.<br/>";
                }
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
                memListDates: JSON.stringify(this.#memListDates),
                indexcol: "id"
            };
            clear_message();
            clearError();
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
            'cartDesc',
            'badgeLabel',
            'rptGrouping'
        ];
        if (config.useGL == 1) {
            fieldList.push('glNum');
        }
        fieldList.push('sort_order');
        downloadFilePost(format, filename, tabledata, null, fieldList);
    }

    // items for editing the bundle contains items
    // closeBundleSel - close the tablle

    closeBundleSel() {
        if (this.#memListBundleTable) {
            this.#memListBundleTable.destroy();
            this.#memListBundleTable = null;
        }
        this.#editMemListBundleDiv.hidden = true;
    }

    // editBundleContains - select the mem id's for this bundle
    editBundleContains(field) {
        this.closeBundleSel();
        // fill the non bundle list data from current data
        let curMemList = this.#memList;
        this.#nonBundleList = [];
        // convert the ISO datetimes to database format, for string compare
        if (this.#editData.length == 0) {
            if (!this.saveTimeSeries())
                return;
        }
        this.#rowStartDate = null;
        this.#rowEndDate = null;
        if (this.#editData.length > 0) {
            // find first range not deleted
            for (let i = 0; i < this.#editData.length; i++) {
                let row = this.#editData[i];
                if (row.to_delete == 0) {
                    this.#rowStartDate = row.startdate;
                    break;
                }
            }
            // find last non deleted range for end date
            for (let i = this.#editData.length - 1; i >= 0; i--) {
                let row = this.#editData[i];
                if (row.to_delete == 0) {
                    this.#rowEndDate = row.startdate;
                    break;
                }
            }
        }
        if (this.#rowStartDate == null) {
            this.#rowStartDate = '2100-01-01 00:00:00';
            this.#rowEndDate = '2001-01-01 00:00:00';
        }
        for (let row of curMemList) {
            let rowListData = this.#memListDates[row.id];
            if (rowListData == undefined)
                rowListData = [];
            let startok = false;
            let endok = false;
            // if it's a bundle, and the periods overlap (mem start <= bundle end and mem end > bundle start
            if (row.label.substring(0, 8) != 'Bundle: ') {
                for (let i = 0; i < rowListData.length; i++) {
                    let rowdate = rowListData[i];
                    if (rowdate.startdate <= this.#rowStartDate) {
                        startok = true;
                    }
                    if (rowdate.enddate >= this.#rowEndDate) {
                        endok = true;
                    }
                }
                if (startok && endok) {
                    this.#nonBundleList.push(row);
                }
            }
        }

        this.#selValues = document.getElementById(field).value;
        if (this.#selValues == null)
            this.#selValues = ''
        else
            this.#selValues = ',' + this.#selValues.replace(/ /g, '') + ',';

        this.#editMemListBundleDiv.hidden = false;

        this.#memListPagination = this.#nonBundleList.length > 25;
        this.#memListBundleTable = new Tabulator('#editMemlistBundleTable', {
            data: this.#nonBundleList,
            layout: "fitDataTable",
            index: "id",
            pagination: this.#memListPagination,
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
                addFieldClass(row.getCell('id').getElement(), 'selectedBGColor');
            }
        }
        if (this.#nonBundleList.length > 25)
            this.#memListBundleTable.setPageSize(25);
    }

    // toggle the selection color of the clicked cell
    clickedSelection(e, cell) {
        let filtercell = cell.getRow().getCell('id');
        toggleFieldClass(filtercell.getElement(), 'selectedBGColor');
    }

    // set all/clear all sections in table based on direction
    setBundleSel(direction) {
        let rows = this.#memListBundleTable.getRows();
        for (let row of rows) {
            if (row.getPosition() === false)
                continue;

            if (direction) {
                addFieldClass(row.getCell('id').getElement(), 'selectedBGColor');
            } else {
                row.getCell('id').getElement().classList.remove('selectedBGColor');
            }
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
            if (row.getCell('id').getElement().classList.contains('selectedBGColor')) {
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

        let containsField =  document.getElementById('editMemListBundleContains');
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
        this.closeBundleSel();
        if (!this.recomputeBundlePrice())
            return;
        this.memListModalMarkDirty(true);
    }

    // bundleChanged - the bundle flag changes from yes/no
    bundleChanged(id) {
        let bundle = document.getElementById('editMemListBundle').value;
        if (this.#editMemListBundleContainsRow) {
            this.#editMemListBundleContainsRow.hidden = bundle != 'Y';
        }
        this.memListModalMarkDirty(true);
    }

    // save the time series data back to the edit data array
    saveTimeSeries() {
        let bundle =  false;
        if (this.#bundlesEnabled)
            bundle = document.getElementById('editMemListBundle').value == 'Y';
        // notes = notes less bundle mark
        let notes = document.getElementById('editMemListNotes').value;
        let shortname = document.getElementById('editMemListLabel').value;
        if (bundle) {
            if (shortname.substring(0, 8) != 'Bundle: ')
                shortname = 'Bundle: ' + shortname.substring(0, 64-8);
                if (!this.recomputeBundlePrice())
                    return false;
        }
        // save back each row in the time series
        let rowListDates = [];
        for (let row = 0; row < 10; row++) {
            if (document.getElementById('EMLTS' + row + '_Price').value != '' ||
                document.getElementById('EMLTS' + row + '_Start').value != '' ||
                document.getElementById('EMLTS' + row + '_End').value != '' ||
                document.getElementById('EMLTS' + row + '_ID').innerHTML != ''
            ) {
                // build the row
                let datesRow = {};

                datesRow.id = document.getElementById('EMLTS' + row + '_ID').innerHTML;
                datesRow.idKey = datesRow.id;
                datesRow.memId = this.#editDataCurrentMemId;
                datesRow.price = document.getElementById('EMLTS' + row + '_Price').value;
                datesRow.startdate = toDBdate(document.getElementById('EMLTS' + row + '_Start').value);
                datesRow.enddate = toDBdate(document.getElementById('EMLTS' + row + '_End').value);
                datesRow.glNum = document.getElementById('EMLTS' + row + '_glNum').value;
                datesRow.nickName = document.getElementById('EMLTS' + row + '_NickName').value;
                let uses = document.getElementById('EMLTS' + row + '_USES').innerHTML;
                datesRow.to_delete = uses == this.#deletedIcon ? 1 : 0;
                if (uses == this.#deleteIcon || uses == this.#deletedIcon)
                    datesRow.uses = 0;
                else
                    datesRow.uses = uses;

                rowListDates.push(datesRow);
                }
            }

        this.#editData = rowListDates;
        this.#memListDates[this.#editDataCurrentMemId] = rowListDates;
        this.#editDataCurrentMemId = null;
        // now copy the main row data into editData
        this.#editDataCurrentRow.memCategory = document.getElementById('memListCategorySelect').value;
        this.#editDataCurrentRow.memAge = document.getElementById('memListAgeSelect').value;
        this.#editDataCurrentRow.memType = document.getElementById('memListTypeSelect').value;
        this.#editDataCurrentRow.shortname = document.getElementById('editMemListLabel').value;
        this.#editDataCurrentRow.rptGrouping = document.getElementById('editMemListRptGrouping').value;
        this.#editDataCurrentRow.cartDesc = tinyMCE.get('editMemListCartDesc').getContent();
        this.#editDataCurrentRow.atcon = document.getElementById('editMemListAtcon').value;
        this.#editDataCurrentRow.online = document.getElementById('editMemListOnline').value;
        if (bundle) {
            notes = document.getElementById('editMemListBundleContains').value + '/\n'  + notes;
        }
        this.#editDataCurrentRow.notes = notes;
        this.#editDataCurrentRow.badgeLabel = document.getElementById('editMemListBadgeLabel').value;
        // now rebuild the prices column
        let priceRanges = '';
        for (let i = 0; i < rowListDates.length; i++) {
            let row = rowListDates[i];
            if (priceRanges != '')
                priceRanges += '<br/>';
            if (row.to_delete == 1)
                priceRanges += '<s>'
            priceRanges += row.price + ': ' + row.startdate;
            if (row.to_delete == 1)
                priceRanges += '</s>';
        }
        this.#editDataCurrentRow.priceRanges = priceRanges;
        return true;
    }

    // compute price for each item in the time series for this bundle
    recomputeBundlePrice() {
        let numReprice = 0;
        clear_message('result_message_editMemList');
        // get the memIds in the bundle
        let bundleContents = this.#memListBundleContains.value.split(',');
        for (let i = 0; i < bundleContents.length; i++) {
            bundleContents[i] = bundleContents[i].trim();
            if (!this.#memListDates.hasOwnProperty(bundleContents[i])) {
                show_message("Error: Bundle item " + bundleContents[i] + " does not have the time series data", 'error', 'result_message_editMemList');
                return false;
            }
        }
        let message = '';
        // now loop over each time series item and compute the price
        for (let index = 0; index < 10; index++) {
            if (document.getElementById('EMLTS' + index + '_to_delete').value == 1) {
                document.getElementById('EMLTS' + index + '_Price').value = '';
                continue; // skip over items to delete
            }
            let startdate = document.getElementById('EMLTS' + index + '_Start').value.trim();
            let enddate = document.getElementById('EMLTS' + index + '_End').value.trim();
            if (startdate == '' || enddate == '') {
                // no date range set, clear the field
                document.getElementById('EMLTS' + index + '_Price').value = '';
                continue; // skip over items to delete
            }

            // make a comparable database string
            if (startdate.indexOf('T') >= 0)
                startdate = toDBdate(startdate);
            if (enddate.indexOf('T') >= 0)
                enddate = toDBdate(enddate);

            // loop of the items in the bundle list computing a price
            let newprice = 0;
            for (let i = 0; i < bundleContents.length; i++) {
                let item = this.#memListDates[bundleContents[i]];
                // find a matching date range in this bundle item
                let found = false;
                for (let m = 0; m < item.length; m++) {
                    // a match is if the item being matched is valie for this start and end dates
                    // so valid is if the item starts on or before this bundle item and ends on or after this bundle item
                    if (startdate >= item[m].startdate && enddate <= item[m].enddate) {
                        newprice += Number(item[m].price);
                        found = true;
                        break;
                    }
                }
                if (!found)
                    message += `No matching date range found for bundle item ${bundleContents[i]} for ${startdate} to ${enddate}<br/>`;
            }

            if (message == '') {
                newprice = newprice.toFixed(2);
                let element = document.getElementById('EMLTS' + index + '_Price');
                if (Number(element.value) != Number(newprice)) {
                    document.getElementById('EMLTS' + index + '_Price').value = newprice;
                    numReprice++;
                }
            }
        }
        if (message != '') {
            show_message(message, 'error', 'result_message_editMemList');
            return false;
        }
        if (numReprice > 0) {
            this.memListModalMarkDirty(true);
            show_message('Reprice of bundle items completed, ' + numReprice + ' item(s) repriced.', 'success', 'result_message_editMemList');
        }
        return true;
    }

    // save the modal data back to the table and close the modal
    editMemListSave() {
        if (this.#memListBundleContains) {
            // check if bundle is Yes
            let bundle = false
            if (this.#bundlesEnabled)
                bundle = document.getElementById('editMemListBundle').value == 'Y';
            if (bundle) {
                // rebuild the bundle values: note, label
                let notes = document.getElementById('editMemListNotes');
                notes = document.getElementById('editMemListBundleContains').value.replace(/ /g, '') + '/\n' + notes;
                let labelEl = document.getElementById('editMemListLabel');
                let label = 'Bundle: ' + labelEl.value;
                labelEl.value = label;
            }
        }

        // write the data back to the this.#editData array
        if (!this.saveTimeSeries())
            return;

        // now validate the remaining data, bundle has already been validated above
        let valid = true;
        let message = '';

        for (let fields of this.#validateFields) {
            let element = document.getElementById(fields.field);
            let value = element.value;
            let fieldValid = true;
            if (value == null || value == '') {
                fieldValid = false;
            }
            if (fields.hasOwnProperty("len")) {
                if (value.length > fields.len) {
                    fieldValid = false;
                }
            }
            if (fields.hasOwnProperty("not")) {
                if (value == fields.not) {
                    fieldValid = false;
                }
            }

            if (!fieldValid) {
                message += fields.message + '<br/>';
                valid = false;
                addFieldClass(element, 'need');
            } else {
                element.classList.remove('need');
            }
        }

        let len = 0;
        if (this.#memListDates.hasOwnProperty(this.#editDataCurrentRow.id))
            len = this.#memListDates[this.#editDataCurrentRow.id].length;

        if (len == undefined || len <= 0) {
            message += 'No Time Series Data rows</br>'
            valid = false;
        }

        if (!valid) {
            show_message('Error: Some fields are missing or incorrect<br>' + message, 'error',  'result_message_editMemList');
            return;
        }

        // copy the edit data back to the main array
        let updateData = [this.#editDataCurrentRow];
        this.#memtable.updateOrAddData(updateData);
        setCellChanged(this.#memtable.getRow(this.#editDataCurrentRow.id));
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

            this.memListModalMarkDirty(false);
        }
        this.#memListModal.hide();
    }
};

// static functions to call appropriate class
function editMemListSave() {
    //console.log("in editMemListSave");
    if (activeConSetup == 'next')
        return next.editMemListSave();

    return current.editMemListSave();
}

function editMemListCancel() {
    if (activeConSetup == 'next')
        return next.editMemListCancel();

    return current.editMemListCancel();
}

// top section request to edit bundle contains field with select list
function editBundleContains(field) {
    if (activeConSetup == 'next')
        next.editBundleContains(field);
    else
        current.editBundleContains(field);
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

function bundleChanged(id) {
    if (activeConSetup == 'next')
        next.setBundleSelbundleChanged(id);
    else
        current.bundleChanged(id);
}

function applyBundleSel() {
    if (activeConSetup == 'next')
        next.applyBundleSel();
    else
        current.applyBundleSel();
}

// agelist, call proper class
var ageListEditor = function(cell, onRendered, success, cancel, editorParams) {
    // console.log("in ageListEditor setup: cell");
    // console.log(cell);
    // console.log("editorParams:");
    // console.log(editorParams);
    // console.log("about to call class function");
    if (activeConSetup == 'next')
        return next.ageListEditor(cell, onRendered, success, cancel, editorParams);
    else
        return current.ageListEditor(cell, onRendered, success, cancel, editorParams);
};

function localeMoney(cell, formatParams, onRendered) {
    let value = cell.getValue();
    if (value == '')
        return value;

    return currencyFmt.format(Number(value).toFixed(2));
}

function memListCellChanged(cell) {
    if (activeConSetup == 'next')
        return next.memListCellChanged(cell);

    current.memListCellChanged(cell);
}

function nickNameChanged(index) {
    if (activeConSetup == 'next')
        return next.nickNameChanged(index);

    current.nickNameChanged(index);
}

function dateChanged(index) {
    if (activeConSetup == 'next')
        return next.dateChanged(index);

    current.dateChanged(index);
}

function deleteRowChanged(index) {
    if (activeConSetup == 'next')
        return next.deleteRowChanged(index);

    current.deleteRowChanged(index);
}

function memListModalMarkDirty(state) {
    if (activeConSetup == 'next')
        return next.memListModalMarkDirty(state);

    current.memListModalMarkDirty(state);
}

function createNewSeries() {
    if (activeConSetup == 'next')
        return next.createNewSeries();

    current.createNewSeries();
}

function recomputeBundlePrice() {
    if (activeConSetup == 'next')
        return next.recomputeBundlePrice();

    current.recomputeBundlePrice();
}

function enterPressedPrice(event) {
    if (event.key === 'Enter') {
        let field = event.target.id;
        let index = field.substring(5, 6);
        let newIndex = "EMLTS" + (Number(index) + 1) + "_Price";
        let newFocus = document.getElementById(newIndex);
        if (newFocus) {
            newFocus.focus()
        } else {
            console.log("No next field to focus on, but enter key pressed on " + event.target.id + " for " + newIndex)
        };
    }
}
