//import { TabulatorFull as Tabulator } from 'tabulator-tables';
// exhibits class - all exhibits space config functions
class exhibitssetup {
    // exhibits region types
    #regionType = null;
    #regionType_arr = [];

    #regionTypeTable = null;
    #regionTypedirty = false;
    #regionTypesavebtn = null;
    #regionTypeundobtn = null;
    #regionTyperedobtn = null;

    // exhibits regions
    #regions = null
    #regionListArr = {};
    #regionsTable = null;
    #regiondirty = false;
    #regionsavebtn = null;
    #regionundobtn = null;
    #regionredobtn = null;

    // exhibits region years
    #regionYears = null;
    #regionYearsListArr = {};
    #regionYearsTable = null;
    #regionYeardirty = false;
    #regionYearsavebtn = null;
    #regionYearundobtn = null;
    #regionYearredobtn = null;

    // exhibits spaces (sections of a region)
    #spaces = null;
    #spacesListArr = {};
    #spacesTable = null;
    #spacedirty = false;
    #spacesavebtn = null;
    #spaceundobtn = null;
    #spaceredobtn = null;

    // exhibits space prices
    #spacePrices = null;
    #spacePricesTable = null;
    #spacePricedirty = false;
    #spacePricesavebtn = null;
    #spacePriceundobtn = null;
    #spacePriceredobtn = null;

    // global items
    #memList = null;
    #memListArr = {};
    #message_div = null;
    #result_message_div = null;
    #exhibits_pane = null;
    #conid = null;
    #debug = 0;
    #debugVisible = false;
    #priceregexp = 'regex:^([0-9]+([.][0-9]*)?|[.][0-9]+)';

    // globals before open
    // none

    constructor(conid, debug) {
        this.#debug = debug;
        this.#conid = conid;
        this.#message_div = document.getElementById('test');
        this.#exhibits_pane = document.getElementById('configuration-content');
        this.#result_message_div = document.getElementById('result_message');
        if (this.#debug & 1) {
            console.log("Debug = " + debug);
            console.log("conid = " + conid);
        }
        if (this.#debug & 2) {
            this.#debugVisible = true;
        }
    };

    // called on open of the exhibits window
    open() {
        var html = `
<div class="container-fluid">
    <div class="row">
        <div class="col-sm-12"><h3 style="text-align: center;"><strong>Exhibits Setup</strong></h3></div>
    </div>
    <ul class="nav nav-pills nav-fill  mb-3" id="exhibitsAdmin-tab" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="regionTypes-tab" data-bs-toggle="pill" data-bs-target="#regionTypes-pane" type="button" role="tab" 
                    aria-controls="nav-exhibitsTabs" aria-selected="true" onclick="exhibits.settab('regionTypes-pane');">Region Types</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="regions-tab" data-bs-toggle="pill" data-bs-target="#regions-pane" type="button" role="tab"
                    aria-controls="nav-regions" aria-selected="false" onclick="exhibits.settab('regions-pane');">Regions
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="regionYears-tab" data-bs-toggle="pill" data-bs-target="#regionYears-pane" type="button" role="tab"
                    aria-controls="nav-regionYears" aria-selected="false" onclick="exhibits.settab('regionYears-pane');">Regions for this Year
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="regionSpaces-tab" data-bs-toggle="pill" data-bs-target="#regionSpaces-pane" type="button" role="tab"
                    aria-controls="nav-memconfigsetup" aria-selected="false" onclick="exhibits.settab('regionSpaces-pane');">Spaces within the Region
            </button>
        </li>
        <li class='nav-item' role='presentation'>
            <button class='nav-link' id='exhibitsPrices-tab' data-bs-toggle='pill' data-bs-target='#exhibitsPrices-pane' type='button' role='tab'
                    aria-controls='nav-exhibitssetup' aria-selected='false' onclick="exhibits.settab('exhibitsPrices-pane');">Space Pricing Options
            </button>
        </li>
    </ul>
    <div class="tab-content" id="exhibitsAdmin-content">
        <div class="tab-pane fade show active" id="regionTypes-pane" role="tabpanel" aria-labelledby="regionTypes-tab" tabindex="0">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-sm-auto m-0 p-0" id="regionType-div"></div>
                </div>
                <div class="row mt-2">
                    <div class="col-sm-auto" id="types-buttons">
                        <button id="types-undo" type="button" class="btn btn-secondary btn-sm" onclick="exhibits.undoTypes(); return false;" disabled>Undo</button>
                        <button id="types-redo" type="button" class="btn btn-secondary btn-sm" onclick="exhibits.redoTypes(); return false;" disabled>Redo</button>
                        <button id="types-addrow" type="button" class="btn btn-secondary btn-sm" onclick="exhibits.addrowTypes(); return false;">Add New</button>
                        <button id="types-save" type="button" class="btn btn-primary btn-sm"  onclick="exhibits.saveTypes(); return false;" disabled>Save Changes</button>
                    </div>
                </div>
            </div>
        </div>   
        <div class="tab-pane fade show" id="regions-pane" role="tabpanel" aria-labelledby="regions-tab" tabindex="0">
            <div class="container-fluid">
                 <div class="row">
                    <div class="col-sm-auto m-0 p-0" id="regions-div"></div>
                </div>
                <div class="row mt-2">
                    <div class="col-sm-auto" id="regions-buttons">                
                        <button id="regions-undo" type="button" class="btn btn-secondary btn-sm" onclick="exhibits.undoRegions(); return false;" disabled>Undo</button>
                        <button id="regions-redo" type="button" class="btn btn-secondary btn-sm" onclick="exhibits.redoRegions(); return false;" disabled>Redo</button>
                        <button id="regions-addrow" type="button" class="btn btn-secondary btn-sm" onclick="exhibits.addrowRegions(); return false;">Add New</button>
                        <button id="regions-save" type="button" class="btn btn-primary btn-sm"  onclick="exhibits.saveRegions(); return false;" disabled>Save Changes</button>
                    </div>
                </div>
            </div>
        </div>
        <div class="tab-pane fade show" id="regionYears-pane" role="tabpanel" aria-labelledby="regionYears-tab" tabindex="0">
            <div class="container-fluid">
                 <div class="row">
                    <div class="col-sm-auto m-0 p-0" id="regionYears-div"></div>
                </div>
                <div class="row mt-2">
                    <div class="col-sm-auto" id="years-buttons">
                        <button id="years-undo" type="button" class="btn btn-secondary btn-sm" onclick="exhibits.undoYears(); return false;" disabled>Undo</button>
                        <button id="years-redo" type="button" class="btn btn-secondary btn-sm" onclick="exhibits.redoYears(); return false;" disabled>Redo</button>
                        <button id="years-addrow" type="button" class="btn btn-secondary btn-sm" onclick="exhibits.addrowYears(); return false;">Add New</button>
                        <button id="years-save" type="button" class="btn btn-primary btn-sm"  onclick="exhibits.saveYears(); return false;" disabled>Save Changes</button>
                    </div>
                </div>
            </div>
        </div>
        <div class="tab-pane fade show" id="regionSpaces-pane" role="tabpanel" aria-labelledby="regionSpaces-tab" tabindex="0">
            <div class="container-fluid">
                 <div class="row">
                    <div class="col-sm-auto m-0 p-0" id="spaces-div"></div>
                </div>
                <div class="row mt-2">
                    <div class="col-sm-auto" id="spaces-buttons">
                        <button id="spaces-undo" type="button" class="btn btn-secondary btn-sm" onclick="exhibits.undoSpaces(); return false;" disabled>Undo</button>
                        <button id="spaces-redo" type="button" class="btn btn-secondary btn-sm" onclick="exhibits.redoSpaces(); return false;" disabled>Redo</button>
                        <button id="spaces-addrow" type="button" class="btn btn-secondary btn-sm" onclick="exhibits.addrowSpaces(); return false;">Add New</button>
                        <button id="spaces-save" type="button" class="btn btn-primary btn-sm"  onclick="exhibits.saveSpaces(); return false;" disabled>Save Changes</button>
                    </div>
                </div>                        
            </div>
        </div>
        <div class="tab-pane fade show" id="exhibitsPrices-pane" role="tabpanel" aria-labelledby="exhibitsPrices-tab" tabindex="0">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-sm-auto m-0 p-0" id="spacePrices-div"></div>
                </div>
                <div class="row mt-2">
                    <div class="col-sm-auto" id="spacePrices-buttons">
                        <button id="spacePrices-undo" type="button" class="btn btn-secondary btn-sm" onclick="exhibits.undoSpacePrices(); return false;" disabled>Undo</button>
                        <button id="spacePrices-redo" type="button" class="btn btn-secondary btn-sm" onclick="exhibits.redoSpacePrices(); return false;" disabled>Redo</button>
                        <button id="spacePrices-addrow" type="button" class="btn btn-secondary btn-sm" onclick="exhibits.addrowSpacePrices(); return false;">Add New</button>
                        <button id="spacePrices-save" type="button" class="btn btn-primary btn-sm"  onclick="exhibits.saveSpacePrices(); return false;" disabled>Save Changes</button>
                    </div>
                </div>        
            </div>
        </div>
    </div>
</div>
`;
        this.#exhibits_pane.innerHTML = html;

        // set up regionTypes
        this.#regionTypesavebtn = document.getElementById('types-save');
        this.#regionTypeundobtn = document.getElementById('types-undo');
        this.#regionTyperedobtn = document.getElementById('types-redo');

        // set up regions
        this.#regionsavebtn = document.getElementById('regions-save');
        this.#regionundobtn = document.getElementById('regions-undo');
        this.#regionredobtn = document.getElementById('regions-redo');

        // set up Years
        this.#regionYearsavebtn = document.getElementById('years-save');
        this.#regionYearundobtn = document.getElementById('years-undo');
        this.#regionYearredobtn = document.getElementById('years-redo');

        // set up Spaces
        this.#spacesavebtn = document.getElementById('spaces-save');
        this.#spaceundobtn = document.getElementById('spaces-undo');
        this.#spaceredobtn = document.getElementById('spaces-redo');

        // set up spacePrices
        this.#spacePricesavebtn = document.getElementById('spacePrices-save');
        this.#spacePriceundobtn = document.getElementById('spacePrices-undo');
        this.#spacePriceredobtn = document.getElementById('spacePrices-redo');

        // get initial data
        clear_message();
        clearError();
        var _this = this;
        var script = "scripts/exhibitsUpdateGetData.php";
        $.ajax({
            url: script,
            method: 'POST',
            data: {gettype: 'all'},
            success: function (data, textStatus, jhXHR) {
                if (data['error']) {
                    showError(data['error']);
                    return false;
                }
                _this.draw(data);
                _this.settab('regionTypes-pane');
            },
            error: function (jqXHR, textStatus, errorThrown) {
                showError("ERROR in " + script + ": " + textStatus, jqXHR);
                return false;
            }
        });
    }

    // on close of the pane, clean up the items
    close() {
        if (this.#regionTypeTable) {
            this.#regionTypeTable.off("dataChanged");
            this.#regionTypeTable.off("rowMoved")
            this.#regionTypeTable.off("cellEdited");
            this.#regionTypeTable.destroy();
            this.#regionTypeTable = null;
        }
        if (this.#regionsTable) {
            this.#regionsTable.off("dataChanged");
            this.#regionsTable.off("rowMoved")
            this.#regionsTable.off("cellEdited");
            this.#regionsTable.destroy();
            this.#regionsTable = null;
        }
        if (this.#regionYearsTable) {
            this.#regionYearsTable.off("dataChanged");
            this.#regionYearsTable.off("rowMoved")
            this.#regionYearsTable.off("cellEdited");
            this.#regionYearsTable.destroy();
            this.#regionYearsTable = null;
        }
        if (this.#spacesTable) {
            this.#spacesTable.off("dataChanged");
            this.#spacesTable.off("rowMoved")
            this.#spacesTable.off("cellEdited");
            this.#spacesTable.destroy();
            this.#spacesTable = null;
        }
        if (this.#spacePricesTable) {
            this.#spacePricesTable.off("dataChanged");
            this.#spacePricesTable.off("rowMoved")
            this.#spacePricesTable.off("cellEdited");
            this.#spacePricesTable.destroy();
            this.#spacePricesTable = null;
        }
        this.#exhibits_pane.innerHTML = '';
    };


    // common code for changing tabs
    settab(tabname) {
        clearError();
        clear_message();
    }

    // editDesc - use tinymce to edit a description
    editDesc(table, index, field, title) {
        var row;
        switch (table) {
            case 'Regions':
                row = this.#regionsTable.getRow(index);
                break;
            case 'exhibitsSpaces':
                row = this.#spacesTable.getRow(index);
                break;
            default:
                return;
        }
        var textitem = row.getCell(field).getValue();
        var titlename = row.getCell(title).getValue();
        showEdit('exhibits', table, index, field, titlename, textitem);
    }

    editReturn(editTable, editField,  editIndex, editValue) {
        // create update argument
        // have to build array because you can't pass a value before a :, it takes it as a string
        // remove leading and trailing <p> from editValue for the description

        const startRE = /^<p>/i;
        const endRE = /<\/p>$/i;
        var neweditValue = editValue.replace(startRE,'');
        neweditValue = neweditValue.replace(endRE,'');
        if (neweditValue.match(/.*<p>.*/) == null) {
            editValue = neweditValue;;
        }

        var updArr = {};
        updArr[editField] = editValue;
        switch (editTable) {
            case 'Regions':
                this.#regionsTable.getRow(editIndex).update(updArr);
                break;
            case 'exhibitsSpaces':
                this.#spacesTable.getRow(editIndex).update(updArr);
                break;
            default:
                return;
        }

    }
    // display edit button for a long field
    editbutton(cell, formatterParams, onRendered) {
        var index = cell.getRow().getIndex();
        if (index > 0) {
            return '<button class="btn btn-secondary" style = "--bs-btn-padding-y: .0rem; --bs-btn-padding-x: .3rem; --bs-btn-font-size: .75rem;", onclick="exhibits.editDesc(\'' +
                formatterParams['table'] + '\',' + index + ',\'' +  formatterParams['fieldName'] + '\', \'' + formatterParams['name'] + '\');">Edit Desc</button>';
        }
        return "Save First";
    }

    // draw - draw screen for editing
    draw(data) {
        var drew_regions = false;
        var drew_regionYears = false;
        var drew_spaces = false;
        var drew_spacePrices = false;

        if (data['memList']) {
            this.#memList = data['memList'];
            this.#memListArr = {};
            this.#memList.forEach(m => {
                this.#memListArr[m.id] = m['label'] + ':' + m['price'].toString();
            });

            if (this.#debug & 1) {
                console.log("memListArr:");
                console.log(this.#memListArr);
            }
        }

        if (data['exhibitsRegionTypes']) {
            this.drawRegionTypes(data);
            this.drawRegions(data);
            drew_regions = true;
        }

        if ((!drew_regions) && data['exhibitsRegions']) {
            this.drawRegions(data);
            this.drawRegionYears(data);
            drew_regionYears = true;
        }

        if ((!drew_regionYears) && data['exhibitsRegionYears']) {
            this.drawRegionYears(data);
            this.drawSpaces(data);
            this.drawSpacePrices(data);
            drew_spaces = true;
            drew_spacePrices = true;
        }

        if ((!drew_spaces) && data['exhibitsSpaces']) {
            this.drawSpaces(data);
            this.drawSpacePrices(data);
            drew_spacePrices = true;
        }

        if ((!drew_spacePrices) && data['exhibitsSpacePrices']) {
            this.drawSpacePrices(data);
        }
    }

    // draw regionTypes table
    drawRegionTypes(data) {
        var _this = this;

        if (this.#regionTypeTable != null) {
            this.#regionTypeTable.off("dataChanged");
            this.#regionTypeTable.off("rowMoved")
            this.#regionTypeTable.off("cellEdited");
            this.#regionTypeTable.destroy();
        }

        if (data['exhibitsRegionTypes']) {
            this.#regionType = data['exhibitsRegionTypes'];
            this.#regionType_arr = [];
            this.#regionType.forEach(regionType => {
                if (regionType.active == 'Y')
                    this.#regionType_arr.push(regionType.regionType);
            })

            if (this.#debug & 1) {
                console.log("regionType_arr:");
                console.log(this.#regionType_arr);
            }
        }

        this.#regionTypedirty = false;
        this.#regionTypeTable = new Tabulator('#regionType-div', {
            maxHeight: "800px",
            history: true,
            movableRows: true,
            data: this.#regionType,
            layout: "fitDataTable",
            pagination: true,
            paginationAddRow:"table",
            paginationSize: 10,
            paginationSizeSelector: [10, 25, 50, 100, 250, true], //enable page size select element with these options
            columns: [
                {rowHandle: true, formatter: "handle", frozen: true, width: 40, headerSort: false},
                { title: "Region Type", field: "regionType", width: 200, headerSort: true, headerWordWrap: true, editor: "input", editorParams: {maxlength: "16"} },
                { title: "Portal Type", field: "portalType", width: 100, headerSort: true, headerWordWrap: true, editor: "list", editorParams: {
                    values: ['vendor', 'artist']}, validator: "required" },
                { title: "Request Approval Required", field: "requestApprovalRequired", headerSort: true, width: 120, headerWordWrap: true, editor: "list", editorParams: {
                    values: ['None', 'Once', 'Annual']}, validator: "required" },
                { title: "Purchase Approval Required", field: "purchaseApprovalRequired", headerSort: true, width: 120, headerWordWrap: true, editor: "list", editorParams: {
                    values: ['Y', 'N'] }, validator: "required" },
                { title: "Purchase Area Totals", field: "purchaseAreaTotals", headerSort: true, width: 140, headerWordWrap: true, editor: "list", editorParams: {
                    values: ['unique', 'combined'] }, validator: "required" },
                { title: "Inperson Max Units", field: "inPersonMaxUnits", headerSort: true, width: 100, headerWordWrap: true, editor: "input" },
                { title: "Mail-in Allowed", field: "mailinAllowed", headerSort: true, width: 100, headerWordWrap: true, editor: "list", editorParams: {
                    values: ['Y', 'N'] }, validator: "required" },
                { title: "Mail-in Max Units", field: "mailinMaxUnits", headerSort: true, width: 100, headerWordWrap: true, editor: "input" },
                { title: "Need W9", field: "needW9", headerSort: false, width: 80, headerWordWrap: true, editor: "list", editorParams: { values: ['Y', 'N'] }, validator: "required" },
                { title: "Uses Inventory", field: "usesInventory", headerSort: false, width: 80, headerWordWrap: true, editor: "list", editorParams: { values: ['Y', 'N'] }, validator: "required" },
                { title: "Active", field: "active", headerSort: true, width: 120, editor: "list", editorParams: { values: ['Y', 'N'] }, validator: "required" },
                { title: "Sort Order", field: "sortorder", visible: this.#debugVisible, headerFilter: false, headerWordWrap: true, width: 80,},
                { title: "Orig Key", field: "regionTypeKey", visible: this.#debugVisible, headerFilter: false, headerWordWrap: true, width: 200,},
                { title: "Delete", field: "uses", formatter: deleteicon, hozAlign: "center", headerSort: false, cellClick: function (e, cell) {
                    deleterow(e, cell.getRow());
                } },
                { title: "To Del", field: "to_delete", visible: this.#debugVisible }
            ],
        });
        this.#regionTypeTable.on("dataChanged", function (data) {
            _this.dataChangedTypes(data);
        });
        this.#regionTypeTable.on("rowMoved", function (row) {
            _this.rowMovedTypes(row)
        });
        this.#regionTypeTable.on("cellEdited", cellChanged);
    }

    // draw regions table
    drawRegions(data) {
        var _this = this;

        if (this.#regionsTable != null) {
            this.#regionsTable.off("dataChanged");
            this.#regionsTable.off("rowMoved")
            this.#regionsTable.off("cellEdited");
            this.#regionsTable.destroy();
        }

        if (data['exhibitsRegions']) {
            this.#regions = data['exhibitsRegions'];

            this.#regionListArr = {};
            this.#regions.forEach(s => {
                this.#regionListArr[s.id] = s.shortname;
            });

            if (this.#debug & 1) {
                console.log("regionListArr:");
                console.log(this.#regionListArr);
            }
        }

        this.#regiondirty = false;
        this.#regionsTable = new Tabulator('#regions-div', {
            maxHeight: "800px",
            history: true,
            movableRows: true,
            data: this.#regions,
            layout: "fitDataTable",
            pagination: true,
            paginationAddRow:"table",
            paginationSize: 10,
            paginationSizeSelector: [10, 25, 50, 100, 250, true], //enable page size select element with these options
            columns: [
                {rowHandle: true, formatter: "handle", frozen: true, width: 40, headerSort: false},
                {title: "ID", field: "id", width: 50, hozAlign: "right", headerSort: false},
                {
                    title: "Type", field: "regionType", headerSort: true, width: 200, headerFilter: true, headerFilterParams: {values: this.#regionType_arr},
                    editor: "list", editorParams: {values: this.#regionType_arr}, validator: "required"
                },
                {
                    title: "Short Name", field: "shortname", headerSort: true, headerFilter: true, width: 200,
                    editor: "input", editorParams: {elementAttributes: {maxlength: "32"}}, validator: "required"
                },
                {
                    title: "Name", field: "name", width: 350, headerSort: true, headerFilter: true,
                    editor: "input", editorParams: {elementAttributes: {maxlength: "128"}}, validator: "required"
                },
                {title: "Description", field: "description", headerFilter: true, width: 500, headerSort: false,},
                {title: "Edit", formatter: this.editbutton, formatterParams: {table: 'Regions', fieldName: 'description', name: 'name' }, hozAlign:"left", headerSort: false },
                {title: "Sort Order", field: "sortorder", visible: this.#debugVisible, headerFilter: false, headerWordWrap: true, width: 90,},
                {title: "Orig Key", field: "regionKey", visible: this.#debugVisible, headerFilter: false, headerWordWrap: true, width: 200,},
                {
                    title: "Delete", field: "uses", formatter: deleteicon, hozAlign: "center", headerSort: false,
                    cellClick: function (e, cell) {
                        deleterow(e, cell.getRow());
                    }
                },
                {title: "To Del", field: "to_delete", visible: this.#debugVisible,}
            ],
        });
        this.#regionsTable.on("dataChanged", function (data) {
            _this.dataChangedRegions(data);
        });
        this.#regionsTable.on("rowMoved", function (row) {
            _this.rowMovedRegions(row)
        });
        this.#regionsTable.on("cellEdited", cellChanged);
    }

    // draw regionYears table
    drawRegionYears(data) {
        var _this = this;

        if (this.#regionYearsTable != null) {
            this.#regionYearsTable.off("dataChanged");
            this.#regionYearsTable.off("rowMoved")
            this.#regionYearsTable.off("cellEdited");
            this.#regionYearsTable.destroy();
        }
        
        if (data['exhibitsRegionYears']) {
            this.#regionYears = data['exhibitsRegionYears'];

            this.#regionYearsListArr = {};
            this.#regionYears.forEach(s => {
                this.#regionYearsListArr[s.id] = s.shortname;
            });

            if (this.#debug & 1) {
                console.log("regionYearsListArr:");
                console.log(this.#regionYearsListArr);
            }
        }

        this.#regionYeardirty = false;
        this.#regionYearsTable = new Tabulator('#regionYears-div', {
            maxHeight: "800px",
            history: true,
            movableRows: true,
            data: this.#regionYears,
            layout: "fitDataTable",
            pagination: true,
            paginationAddRow:"table",
            paginationSize: 10,
            paginationSizeSelector: [10, 25, 50, 100, 250, true], //enable page size select element with these options
            columns: [
                {rowHandle: true, formatter: "handle", frozen: true, width: 40, headerSort: false},
                {title: "ID", field: "id", width: 50, hozAlign: "right", headerSort: false,},
                {title: "Conid", field: "conid", width: 80, hozAlign: "right", headerSort: false,},
                {
                    title: "Exhibits Region", field: "exhibitsRegion", headerSort: true, width: 150, headerWordWrap: true, headerFilter: true, headerFilterParams: {values: this.#regionListArr},
                    editor: "list", editorParams: {values: this.#regionListArr}, validator: "required",
                    formatter: "lookup", formatterParams: this.#regionListArr,
                },
                {
                    title: "Owner Name", field: "ownerName", headerSort: true, headerFilter: true, width: 300,
                    editor: "input", editorParams: {elementAttributes: {maxlength: "64"}}, validator: "required"
                },
                {
                    title: "Owner Email", field: "ownerEmail", width: 300, headerSort: true, headerFilter: true,
                    editor: "input", editorParams: {elementAttributes: {maxlength: "64"}}, validator: "required"
                },
                { title: 'Included', field: "includedMemId", width: 200, headerSort: false,
                    editor: "list", formatter:"lookup", formatterParams: this.#memListArr, editorParams: { values: this.#memListArr }
                },
                { title: 'Additional', field: "additionalMemId", width: 200, headerSort: false,
                    editor: "list", formatter:"lookup", formatterParams: this.#memListArr, editorParams: { values: this.#memListArr  }
                },
                {title: 'Total Units Avail', field: "totalUnitsAvailable", width: 80, hozAlign: "right", headerWordWrap: true, headerSort: false, editor: "input", editorParams: {maxlength: "10"}},
                {title: 'At-Con Id Base', field: "atconIdBase", width: 80, hozAlign: "right", headerWordWrap: true, headerSort: false, editor: "number",},
                {
                    title: 'Mail-In Fee', field: "mailinFee", width: 100, hozAlign: "right", headerWordWrap: true, headerSort: false,
                    formatter: "money", formatterParams: {decimal: '.', thousand: ',', symbol: '$', negativeSign: true},
                    editor: "input", validator: ["required", this.#priceregexp],},
                {title: 'Mail-In Id Base', field: "mailinIdBase", width: 80, hozAlign: "right", headerWordWrap: true, headerSort: false, editor: "number",},
                {title: "Sort Order", field: "sortorder", visible: this.#debugVisible, headerFilter: false, headerWordWrap: true, hozAlign: "right", width: 90,},
                {title: "Orig Key", field: "regionYearKey", visible: this.#debugVisible, headerFilter: false, headerWordWrap: true, width: 200,},
                {
                    title: "Delete", field: "uses", formatter: deleteicon, hozAlign: "center", headerSort: false,
                    cellClick: function (e, cell) {
                        deleterow(e, cell.getRow());
                    }
                },
                {title: "To Del", field: "to_delete", visible: this.#debugVisible,}
            ],
        });
        this.#regionYearsTable.on("dataChanged", function (data) {
            _this.dataChangedYears(data);
        });
        this.#regionYearsTable.on("rowMoved", function (row) {
            _this.rowMovedYears(row)
        });
        this.#regionYearsTable.on("cellEdited", cellChanged);
    }

    // draw spaces table
    drawSpaces(data) {
        var _this = this;

        if (this.#spacesTable != null) {
            this.#spacesTable.off("dataChanged");
            this.#spacesTable.off("rowMoved")
            this.#spacesTable.off("cellEdited");
            this.#spacesTable.destroy();
        }

        if (data['exhibitsSpaces']) {
            this.#spaces = data['exhibitsSpaces'];

            this.#spacesListArr = {};
            this.#spaces.forEach(s => {
                this.#spacesListArr[s.id] = s.shortname;
            });

            if (this.#debug & 1) {
                console.log("spacesListArr:");
                console.log(this.#spacesListArr);
            }
        }

        this.#spacedirty = false;
        this.#spacesTable = new Tabulator('#spaces-div', {
            maxHeight: "800px",
            history: true,
            movableRows: true,
            data: this.#spaces,
            layout: "fitDataTable",
            pagination: true,
            paginationAddRow:"table",
            paginationSize: 10,
            paginationSizeSelector: [10, 25, 50, 100, 250, true], //enable page size select element with these options
            columns: [
                {rowHandle: true, formatter: "handle", frozen: true, width: 40, headerSort: false},
                {title: "ID", field: "id", width: 50, hozAlign: "right", headerSort: false},
                {
                    title: "Region",
                    field: "exhibitsRegionYear",
                    headerSort: true,
                    headerWordWrap: true,
                    width: 100,
                    headerFilter: true,
                    headerFilterParams: {values: this.#regionYearsListArr},
                    editor: "list",
                    editorParams: {values: this.#regionYearsListArr},
                    validator: "required",
                    formatter: "lookup", formatterParams: this.#regionYearsListArr,
                },
                {
                    title: "Short Name", field: "shortname", headerSort: true, headerFilter: true, width: 200,
                    editor: "input", editorParams: {elementAttributes: {maxlength: "32"}}, validator: "required"
                },
                {
                    title: "Name", field: "name", width: 400, headerSort: true, headerFilter: true,
                    editor: "input", editorParams: {elementAttributes: {maxlength: "128"}}, validator: "required"
                },
                {title: "Description", field: "description", headerFilter: true, width: 550, headerSort: false,},
                {title: "Edit", formatter: this.editbutton, formatterParams: {table: 'exhibitsSpaces', fieldName: 'description', name: 'name' }, hozAlign:"left", headerSort: false },
                {title: 'Units', field: "unitsAvailable", width: 100, hozAlign: "right", headerSort: false, editor: "number", editorParams: {min:0, max:9999999}},
                {title: "Sort Order", field: "sortorder", visible: this.#debugVisible, headerFilter: false, headerWordWrap: true, width: 80,},
                {title: "Orig Key", field: "spaceKey", visible: this.#debugVisible, headerFilter: false, headerWordWrap: true, width: 200,},
                {
                    title: "Delete", field: "uses", formatter: deleteicon, hozAlign: "center", headerSort: false,
                    cellClick: function (e, cell) {
                        deleterow(e, cell.getRow());
                    }
                },
                {title: "To Del", field: "to_delete", visible: this.#debugVisible,}
            ],
        });
        this.#spacesTable.on("dataChanged", function (data) {
            _this.dataChangedSpaces(data);
        });
        this.#spacesTable.on("rowMoved", function (row) {
            _this.rowMovedSpaces(row)
        });
        this.#spacesTable.on("cellEdited", cellChanged);
    }
    
    // draw spacePrices table
    drawSpacePrices(data) {
        var _this = this;

        if (this.#spacePricesTable != null) {
            this.#spacePricesTable.off("dataChanged");
            this.#spacePricesTable.off("rowMoved")
            this.#spacePricesTable.off("cellEdited");
            this.#spacePricesTable.destroy();
        }

        if (data['exhibitsSpacePrices'])
            this.#spacePrices = data['exhibitsSpacePrices'];

        this.#spacePricedirty = false;
        this.#spacePricesTable = new Tabulator('#spacePrices-div', {
            maxHeight: "800px",
            history: true,
            movableRows: true,
            data: this.#spacePrices,
            layout: "fitDataTable",
            pagination: true,
            paginationAddRow:"table",
            paginationSize: 10,
            paginationSizeSelector: [10, 25, 50, 100, 250, true], //enable page size select element with these options
            columns: [
                {rowHandle: true, formatter: "handle", frozen: true, width: 40, headerSort: false},
                {title: "ID", field: "id", width: 50, hozAlign: "right", headerSort: false},
                {
                    title: "Region", field: "regionId", width: 200, headerSort: true, headerFilter: 'list', headerFilterParams: {values: this.#regionListArr},
                    formatter: "lookup", formatterParams: this.#regionListArr,
                },
                {
                    title: "Exhibits Space", field: "spaceId", width: 200, headerSort: true, headerWordWrap: true,
                    headerFilter: true, headerFilterParams: {values: this.#spacesListArr},
                    editor: "list", formatter: "lookup", formatterParams: this.#spacesListArr, editorParams: {values: this.#spacesListArr}
                },
                {
                    title: "Code", field: "code", headerSort: true, headerFilter: true, width: 150,
                    editor: "input", editorParams: {elementAttributes: {maxlength: "32"}}, validator: "required"
                },
                {title: "Description", field: "description", editor: "input", editorParams: {elementAttributes: {maxlength: "64"}}, headerFilter: true, width: 450, headerSort: false,},
                {
                    title: 'Units', field: "units", headerHozAlign:"right", width: 100, hozAlign: "right", headerSort: false, editor: "input", editorParams: {maxlength: "10"},
                    headerFilter: true, headerFilterFunc: numberHeaderFilter,
                },
                {
                    title: 'Price', field: "price", headerHozAlign:"right", width: 120, hozAlign: "right", headerSort: false,
                    editor: "input", editorParams: {maxlength: "10"},
                    formatter: "money", formatterParams: {decimal: '.', thousand: ',', symbol: '$', negativeSign: true},
                    headerFilter: true, headerFilterFunc: numberHeaderFilter,
                },
                {
                    title: "Incl Mem", headerWordWrap: true, field: "includedMemberships", width: 80, hozAlign: "right", headerSort: false,
                    editor: "number", editorParams: {min: 0, max: 99, maxlength: 2},
                },
                {
                    title: "Addl Mem", headerWordWrap: true, field: "additionalMemberships", width: 80, hozAlign: "right", headerSort: false,
                    editor: "number", editorParams: {min: 0, max: 99, maxlength: 2},
                },
                {
                    title: "Req", field: "requestable", width: 80, hozAlign: "right", headerSort: false,
                    editor: "tickCross", formatter: "tickCross",
                },
                {title: "Sort Order", field: "sortorder", visible: this.#debugVisible, headerFilter: false, headerWordWrap: true, width: 80,},
                {title: "Orig Key", field: "priceKey", visible: this.#debugVisible, headerFilter: false, headerWordWrap: true, width: 200,},
                {
                    title: "Delete", field: "uses", formatter: deleteicon, hozAlign: "center", headerSort: false,
                    cellClick: function (e, cell) { deleterow(e, cell.getRow()); },
                },
                {title: "To Del", field: "to_delete", visible: this.#debugVisible,}
            ],
        });
        this.#spacePricesTable.on("dataChanged", function (data) {
            _this.dataChangedSpacePrices(data);
        });
        this.#spacePricesTable.on("rowMoved", function (row) {
            _this.rowMovedSpacePrices(row)
        });
        this.#spacePricesTable.on("cellEdited", cellChanged);
    }

    //// Processing functions for regionTypes

    dataChangedTypes(data) {
        //data - the updated table data
        if (!this.#regionTypedirty) {
            this.#regionTypesavebtn.innerHTML = "Save Changes*";
            this.#regionTypesavebtn.disabled = false;
            this.#regionTypedirty = true;
        }
        this.checkTypesUndoRedo();
    };

    rowMovedTypes(row) {
        this.#regionTypesavebtn.innerHTML = "Save Changes*";
        this.#regionTypesavebtn.disabled = false;
        this.#regionTypedirty = true;
        this.checkTypesUndoRedo();
    }

    // unbutton for regionTypes
    undoTypes() {
        if (this.#regionTypeTable != null) {
            this.#regionTypeTable.undo();

            if (this.checkTypesUndoRedo() <= 0) {
                this.#regionTypedirty = false;
                this.#regionTypesavebtn.innerHTML = "Save Changes";
                this.#regionTypesavebtn.disabled = true;
            }
        }
    };

    // rebutton for regionTypes
    redoTypes() {
        if (this.#regionTypeTable != null) {
            this.#regionTypeTable.redo();

            if (this.checkTypesUndoRedo() > 0) {
                this.#regionTypedirty = true;
                this.#regionTypesavebtn.innerHTML = "Save Changes*";
                this.#regionTypesavebtn.disabled = false;
            }
        }
    };

    // add row to types table and scroll to that new row
    addrowTypes() {
        var _this = this;
        this.#regionTypeTable.addRow({regionType: 'new-row', portalType: 'vendor', purchaseApprovalRequired: 'Y',  inPersonMaxUnits: 0, mailinAllowed: 'N', mailinMaxUnits: 0,
            active: 'Y', sortorder: 99, uses: 0}, false).then(function (row) {
            _this.#regionTypeTable.setPage("last"); // adding new to last page always
            row.getTable().scrollToRow(row);
            _this.checkTypesUndoRedo();
        });
    }

    // set undo / redo status for exhibits type buttons
    checkTypesUndoRedo() {
        var undosize = this.#regionTypeTable.getHistoryUndoSize();
        this.#regionTypeundobtn.disabled = undosize <= 0;
        this.#regionTyperedobtn.disabled = this.#regionTypeTable.getHistoryRedoSize() <= 0;
        return undosize;
    }

    saveTypesComplete(data, textStatus, jhXHR) {
        var _this = this;

        if ('error' in data) {
            if (data['error'] != '' && this.#debug)
                showError(data['error']);
            if (data['message']) {
                show_message(data['message'], 'error');
            }
            this.#regionTypesavebtn.innerHTML = "Save Changes*";
            this.#regionTypesavebtn.disabled = false;
            return false;
        }
        if (data['message'] !== undefined) {
            show_message(data['message'], 'success');
        }
        if (data['warn'] !== undefined) {
            show_message(data['warn'], 'warn');
        }
        this.#regionTypesavebtn.innerHTML = "Save Changes";
        this.#regionTypesavebtn.disabled = true;
        this.draw(data);
    }

    saveTypes() {
        if (this.#regionsTable != null) {
            var _this = this;

            var invalids = this.#regionTypeTable.validate();
            if (invalids !== true) {
                console.log(invalids);
                show_message("Region Type Table does not pass validation, please check for empty cells or cells outlined in red", 'error');
                return false;
            }
            this.#regionTypesavebtn.innerHTML = "Saving...";
            this.#regionTypesavebtn.disabled = true;

            var script = "scripts/exhibitsUpdateGetData.php";

            clear_message();
            clearError();
            var postdata = {
                tabledata: JSON.stringify(this.#regionTypeTable.getData()),
                tablename: "regionTypes",
                gettype: "types,regions",
                indexcol: "regionTypeKey"
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
    }

    //// Processing functions for regions
    dataChangedRegions(data) {
        //data - the updated table data
        if (!this.#regiondirty) {
            this.#regionsavebtn.innerHTML = "Save Changes*";
            this.#regionsavebtn.disabled = false;
            this.#regiondirty = true;
        }
        this.checkRegionsUndoRedo();
    };

    rowMovedRegions(row) {
        this.#regionsavebtn.innerHTML = "Save Changes*";
        this.#regionsavebtn.disabled = false;
        this.#regionTypedirty = true;
        this.checkRegionsUndoRedo();
    }

    // unbutton for region
    undoRegions() {
        if (this.#regionsTable != null) {
            this.#regionsTable.undo();

            if (this.checkRegionsUndoRedo() <= 0) {
                this.#regionTypedirty = false;
                this.#regionsavebtn.innerHTML = "Save Changes";
                this.#regionsavebtn.disabled = true;
            }
        }
    };

    // rebutton for region
    redoRegions() {
        if (this.#regionsTable != null) {
            this.#regionsTable.redo();

            if (this.checkRegionsUndoRedo() > 0) {
                this.#regionTypedirty = true;
                this.#regionsavebtn.innerHTML = "Save Changes*";
                this.#regionsavebtn.disabled = false;
            }
        }
    };

    // add row to Regions table and scroll to that new row
    addrowRegions() {
        var _this = this;
        this.#regionsTable.addRow({ sortorder: 99, uses: 0}, false).then(function (row) {
            _this.#regionsTable.setPage("last"); // adding new to last page always
            row.getTable().scrollToRow(row);
            _this.checkRegionsUndoRedo();
        });
    }

    // set undo / redo status for exhibits type buttons
    checkRegionsUndoRedo() {
        var undosize = this.#regionsTable.getHistoryUndoSize();
        this.#regionundobtn.disabled = undosize <= 0;
        this.#regionredobtn.disabled = this.#regionsTable.getHistoryRedoSize() <= 0;
        return undosize;
    }

    saveRegionsComplete(data, textStatus, jhXHR) {
        var _this = this;

        if ('error' in data) {
            if (this.#debug)
                showError(data['error']);
            if (data['message']) {
                show_message(data['message'], 'error');
            }
            this.#regionsavebtn.innerHTML = "Save Changes*";
            this.#regionsavebtn.disabled = false;
            return false;
        }
        if (data['message'] !== undefined) {
            show_message(data['message'], 'success');
        }
        if (data['warn'] !== undefined) {
            show_message(data['warn'], 'warn');
        }
        this.#regionsavebtn.innerHTML = "Save Changes";
        this.#regionsavebtn.disabled = true;
        this.draw(data);
    }

    saveRegions() {
        if (this.#regionsTable != null) {
            var _this = this;

            var invalids = this.#regionsTable.validate();
            if (invalids !== true) {
                console.log(invalids);
                show_message("Regions Table does not pass validation, please check for empty cells or cells outlined in red", 'error');
                return false;
            }
            this.#regionsavebtn.innerHTML = "Saving...";
            this.#regionsavebtn.disabled = true;

            var script = "scripts/exhibitsUpdateGetData.php";

            clear_message();
            clearError();
            var postdata = {
                tabledata: JSON.stringify(this.#regionsTable.getData()),
                tablename: "regions",
                gettype: "regions,years",
                indexcol: "regionKey"
            };
            //console.log(postdata);
            $.ajax({
                url: script,
                method: 'POST',
                data: postdata,
                success: function (data, textStatus, jhXHR) {
                    _this.saveRegionsComplete(data, textStatus, jhXHR);
                },
                error: function (jqXHR, textStatus, errorThrown) {
                    showError("ERROR in " + script + ": " + textStatus, jqXHR);
                    return false;
                }
            });
        }
    }

    //// Processing functions for regionYears

    dataChangedYears(data) {
        //data - the updated table data
        if (!this.#regionYeardirty) {
            this.#regionYearsavebtn.innerHTML = "Save Changes*";
            this.#regionYearsavebtn.disabled = false;
            this.#regionYeardirty = true;
        }
        this.checkYearsUndoRedo();
    };

    rowMovedYears(row) {
        this.#regionYearsavebtn.innerHTML = "Save Changes*";
        this.#regionYearsavebtn.disabled = false;
        this.#regionYeardirty = true;
        this.checkYearsUndoRedo();
    }

    // unbutton for regionYears
    undoYears() {
        if (this.#regionYearsTable != null) {
            this.#regionYearsTable.undo();

            if (this.checkYearsUndoRedo() <= 0) {
                this.#regionYeardirty = false;
                this.#regionYearsavebtn.innerHTML = "Save Changes";
                this.#regionYearsavebtn.disabled = true;
            }
        }
    };

    // rebutton for regionYears
    redoYears() {
        if (this.#regionYearsTable != null) {
            this.#regionYearsTable.redo();

            if (this.checkYearsUndoRedo() > 0) {
                this.#regionYeardirty = true;
                this.#regionYearsavebtn.innerHTML = "Save Changes*";
                this.#regionYearsavebtn.disabled = false;
            }
        }
    };

    // add row to Years table and scroll to that new row
    addrowYears() {
        var _this = this;
        this.#regionYearsTable.addRow({ownerName: 'new-row', sortorder: 99, uses: 0}, false).then(function (row) {
            _this.#regionYearsTable.setPage("last"); // adding new to last page always
            row.getTable().scrollToRow(row);
            _this.checkYearsUndoRedo();
        });
    }

    // set undo / redo status for exhibits type buttons
    checkYearsUndoRedo() {
        var undosize = this.#regionYearsTable.getHistoryUndoSize();
        this.#regionYearundobtn.disabled = undosize <= 0;
        this.#regionYearredobtn.disabled = this.#regionYearsTable.getHistoryRedoSize() <= 0;
        return undosize;
    }

    saveYearsComplete(data, textStatus, jhXHR) {
        var _this = this;

        if ('error' in data) {
            if (data['error'] != '' && this.#debug)
                showError(data['error']);
            if (data['message']) {
                show_message(data['message'], 'error');
            }
            this.#regionYearsavebtn.innerHTML = "Save Changes*";
            this.#regionYearsavebtn.disabled = false;
            return false;
        }
        if (data['message'] !== undefined) {
            show_message(data['message'], 'success');
        }
        if (data['warn'] !== undefined) {
            show_message(data['warn'], 'warn');
        }
        this.#regionYearsavebtn.innerHTML = "Save Changes";
        this.#regionYearsavebtn.disabled = true;
        this.draw(data);
    }

    saveYears() {
        if (this.#regionsTable != null) {
            var _this = this;

            var invalids = this.#regionYearsTable.validate();
            if (invalids !== true) {
                console.log(invalids);
                show_message("Region Years Table does not pass validation, please check for empty cells or cells outlined in red", 'error');
                return false;
            }
            this.#regionYearsavebtn.innerHTML = "Saving...";
            this.#regionYearsavebtn.disabled = true;

            var script = "scripts/exhibitsUpdateGetData.php";

            clear_message();
            clearError();
            var postdata = {
                tabledata: JSON.stringify(this.#regionYearsTable.getData()),
                tablename: "regionYears",
                gettype: "years,spaces",
                indexcol: "regionYearKey"
            };
            //console.log(postdata);
            $.ajax({
                url: script,
                method: 'POST',
                data: postdata,
                success: function (data, textStatus, jhXHR) {
                    _this.saveYearsComplete(data, textStatus, jhXHR);
                },
                error: function (jqXHR, textStatus, errorThrown) {
                    showError("ERROR in " + script + ": " + textStatus, jqXHR);
                    return false;
                }
            });
        }
    }

    //// Processing functions for spaces

    dataChangedSpaces(data) {
        //data - the updated table data
        if (!this.#spacedirty) {
            this.#spacesavebtn.innerHTML = "Save Changes*";
            this.#spacesavebtn.disabled = false;
            this.#spacedirty = true;
        }
        this.checkSpacesUndoRedo();
    };

    rowMovedSpaces(row) {
        this.#spacesavebtn.innerHTML = "Save Changes*";
        this.#spacesavebtn.disabled = false;
        this.#spacedirty = true;
        this.checkSpacesUndoRedo();
    }

    // unbutton for regionSpaces
    undoSpaces() {
        if (this.#spacesTable != null) {
            this.#spacesTable.undo();

            if (this.checkSpacesUndoRedo() <= 0) {
                this.#spacedirty = false;
                this.#spacesavebtn.innerHTML = "Save Changes";
                this.#spacesavebtn.disabled = true;
            }
        }
    };

    // rebutton for regionSpaces
    redoSpaces() {
        if (this.#spacesTable != null) {
            this.#spacesTable.redo();

            if (this.checkSpacesUndoRedo() > 0) {
                this.#spacedirty = true;
                this.#spacesavebtn.innerHTML = "Save Changes*";
                this.#spacesavebtn.disabled = false;
            }
        }
    };

    // add row to Spaces table and scroll to that new row
    addrowSpaces() {
        var _this = this;
        this.#spacesTable.addRow({shortname: 'new-row', sortorder: 99, uses: 0}, false).then(function (row) {
            _this.#spacesTable.setPage("last"); // adding new to last page always
            row.getTable().scrollToRow(row);
            _this.checkSpacesUndoRedo();
        });
    }

    // set undo / redo status for exhibits type buttons
    checkSpacesUndoRedo() {
        var undosize = this.#spacesTable.getHistoryUndoSize();
        this.#spaceundobtn.disabled = undosize <= 0;
        this.#spaceredobtn.disabled = this.#spacesTable.getHistoryRedoSize() <= 0;
        return undosize;
    }

    saveSpacesComplete(data, textStatus, jhXHR) {
        var _this = this;

        if ('error' in data) {
            if (data['error'] != '' && this.#debug)
                showError(data['error']);
            if (data['message']) {
                show_message(data['message'], 'error');
            }
            this.#spacesavebtn.innerHTML = "Save Changes*";
            this.#spacesavebtn.disabled = false;
            return false;
        }
        if (data['message'] !== undefined) {
            show_message(data['message'], 'success');
        }
        if (data['warn'] !== undefined) {
            show_message(data['warn'], 'warn');
        }
        this.#spacesavebtn.innerHTML = "Save Changes";
        this.#spacesavebtn.disabled = true;
        this.draw(data);
    }

    saveSpaces() {
        if (this.#spacesTable != null) {
            var _this = this;

            var invalids = this.#spacesTable.validate();
            if (invalids !== true) {
                console.log(invalids);
                show_message("Spaces Table does not pass validation, please check for empty cells or cells outlined in red", 'error');
                return false;
            }
            this.#spacesavebtn.innerHTML = "Saving...";
            this.#spacesavebtn.disabled = true;

            var script = "scripts/exhibitsUpdateGetData.php";

            clear_message();
            clearError();
            var postdata = {
                tabledata: JSON.stringify(this.#spacesTable.getData()),
                tablename: "exhibitsSpaces",
                gettype: "spaces,prices",
                indexcol: "spaceKey"
            };
            //console.log(postdata);
            $.ajax({
                url: script,
                method: 'POST',
                data: postdata,
                success: function (data, textStatus, jhXHR) {
                    _this.saveSpacesComplete(data, textStatus, jhXHR);
                },
                error: function (jqXHR, textStatus, errorThrown) {
                    showError("ERROR in " + script + ": " + textStatus, jqXHR);
                    return false;
                }
            });
        }
    }

    //// Processing functions for spacePricedss

    dataChangedSpacePrices(data) {
        //data - the updated table data
        if (!this.#spacePricedirty) {
            this.#spacePricesavebtn.innerHTML = "Save Changes*";
            this.#spacePricesavebtn.disabled = false;
            this.#spacePricedirty = true;
        }
        this.checkSpacePricesUndoRedo();
    };

    rowMovedSpacePrices(row) {
        this.#spacePricesavebtn.innerHTML = "Save Changes*";
        this.#spacePricesavebtn.disabled = false;
        this.#spacePricedirty = true;
        this.checkSpacePricesUndoRedo();
    }

    // unbutton for spaces
    undoSpacePrices() {
        if (this.#spacePricesTable != null) {
            this.#spacePricesTable.undo();

            if (this.checkSpacePricesUndoRedo() <= 0) {
                this.#spacePricedirty = false;
                this.#spacePricesavebtn.innerHTML = "Save Changes";
                this.#spacePricesavebtn.disabled = true;
            }
        }
    };

    // rebutton for spaces
    redoSpacePrices() {
        if (this.#spacePricesTable != null) {
            this.#spacePricesTable.redo();

            if (this.checkSpacePricesUndoRedo() > 0) {
                this.#spacePricedirty = true;
                this.#spacePricesavebtn.innerHTML = "Save Changes*";
                this.#spacePricesavebtn.disabled = false;
            }
        }
    };

    // add row to Spaces table and scroll to that new row
    addrowSpacePrices() {
        var _this = this;
        this.#spacePricesTable.addRow({code: 'new-row', sortorder: 99, uses: 0}, false).then(function (row) {
            _this.#spacePricesTable.setPage("last"); // adding new to last page always
            row.getTable().scrollToRow(row);
            _this.checkSpacePricesUndoRedo();
        });
    }

    // set undo / redo status for spaces buttons
    checkSpacePricesUndoRedo() {
        var undosize = this.#spacePricesTable.getHistoryUndoSize();
        this.#spacePriceundobtn.disabled = undosize <= 0;
        this.#spacePriceredobtn.disabled = this.#spacePricesTable.getHistoryRedoSize() <= 0;
        return undosize;
    }

    saveSpacePricessComplete(data, textStatus, jhXHR) {
        var _this = this;

        if ('error' in data) {
            if (data['error'] != '' && this.#debug)
                showError(data['error']);
            if (data['message']) {
                show_message(data['message'], 'error');
            }
            this.#spacePricesavebtn.innerHTML = "Save Changes*";
            this.#spacePricesavebtn.disabled = false;
            return false;
        }
        if (data['message'] !== undefined) {
            show_message(data['message'], 'success');
        }
        if (data['warn'] !== undefined) {
            show_message(data['warn'], 'warn');
        }
        this.#spacePricesavebtn.innerHTML = "Save Changes";
        this.#spacePricesavebtn.disabled = true;
        this.draw(data);
    }

    saveSpacePrices() {
        if (this.#spacePricesTable != null) {
            var _this = this;

            var invalids = this.#spacePricesTable.validate();
            if (invalids !== true) {
                console.log(invalids);
                show_message("Space Prices Table does not pass validation, please check for empty cells or cells outlined in red", 'error');
                return false;
            }
            this.#spacePricesavebtn.innerHTML = "Saving...";
            this.#spacePricesavebtn.disabled = true;

            var script = "scripts/exhibitsUpdateGetData.php";

            clear_message();
            clearError();
            var postdata = {
                tabledata: JSON.stringify(this.#spacePricesTable.getData()),
                tablename: "exhibitsSpacePrices",
                gettype: "prices",
                indexcol: "priceKey"
            };
            //console.log(postdata);
            $.ajax({
                url: script,
                method: 'POST',
                data: postdata,
                success: function (data, textStatus, jhXHR) {
                    _this.saveSpacesComplete(data, textStatus, jhXHR);
                },
                error: function (jqXHR, textStatus, errorThrown) {
                    showError("ERROR in " + script + ": " + textStatus, jqXHR);
                    return false;
                }
            });
        }
    }
};

var dirty = false;
function cellChanged(cell) {
    dirty = true;
    cell.getElement().style.backgroundColor = "#fff3cd";
}

function deleteicon(cell, formattParams, onRendered) {
    var value = cell.getValue();
    if (value == 0)
        return "&#x1F5D1;";
    return value;
}

function deleterow(e, row) {
    var count = row.getCell("uses").getValue();
    if (count == 0) {
        row.getCell("to_delete").setValue(1);
        row.getCell("uses").setValue('<span style="color:red;"><b>Del</b></span>');
    }
}
