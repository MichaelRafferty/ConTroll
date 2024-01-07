//import { TabulatorFull as Tabulator } from 'tabulator-tables';
// vendor class - all vendor space config functions
class vendorsetup {
    // vendor region types
    #regionType = null;
    #regionType_arr = [];

    #regionTypeTable = null;
    #regionTypedirty = false;
    #regionTypesavebtn = null;
    #regionTypeundobtn = null;
    #regionTyperedobtn = null;

    // vendor regions
    #regions = null
    #regionListArr = {};
    #regionsTable = null;
    #regiondirty = false;
    #regionsavebtn = null;
    #regionundobtn = null;
    #regionredobtn = null;

    // vendor region years
    #regionYears = null;
    #regionYearsTable = null;
    #regionYeardirty = false;
    #regionYearsavebtn = null;
    #regionYearundobtn = null;
    #regionYearredobtn = null;

    // vendor spaces (sections of a region)
    #spaces = null;
    #spacesTable = null;
    #spacedirty = false;
    #spacesavebtn = null;
    #spaceundobtn = null;
    #spaceredobtn = null;

    // vendor space prices
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
    #vendor_pane = null;
    #conid = null;
    #debug = 0;
    #debugVisible = "false";


    // globals before open
    // none

    constructor(conid, debug) {
        this.#debug = debug;
        this.#conid = conid;
        this.#message_div = document.getElementById('test');
        this.#vendor_pane = document.getElementById('vendor-pane');
        this.#result_message_div = document.getElementById('result_message');
        if (this.#debug & 1) {
            console.log("Debug = " + debug);
            console.log("conid = " + conid);
        }
        if (this.#debug & 2) {
            this.#debugVisible = "true";
        }
    };

    // called on open of the vendor window
    open() {
        var html = `
<div class="container-fluid">
    <div class="row">
        <div class="col-sm-12"><h3 style="text-align: center;"><strong>Vendor Setup</strong></h3></div>
    </div>
    <ul class="nav nav-pills nav-fill  mb-3" id="vendorAdmin-tab" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="regionTypes-tab" data-bs-toggle="pill" data-bs-target="#regionTypes-pane" type="button" role="tab" 
                    aria-controls="nav-vendorTabs" aria-selected="true" onclick="vendor.settab('regionTypes-pane');">Region Types</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="regions-tab" data-bs-toggle="pill" data-bs-target="#regions-pane" type="button" role="tab"
                    aria-controls="nav-regions" aria-selected="false" onclick="vendor.settab('regions-pane');">Regions
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="regionYears-tab" data-bs-toggle="pill" data-bs-target="#regionYears-pane" type="button" role="tab"
                    aria-controls="nav-regionYears" aria-selected="false" onclick="vendor.settab('regionYears-pane');">Regions for this Year
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="regionSpaces-tab" data-bs-toggle="pill" data-bs-target="#regionSpaces-pane" type="button" role="tab"
                    aria-controls="nav-memconfigsetup" aria-selected="false" onclick="vendor.settab('regionSpaces-pane');">Spaces within the Region
            </button>
        </li>
        <li class='nav-item' role='presentation'>
            <button class='nav-link' id='vendorPrices-tab' data-bs-toggle='pill' data-bs-target='#vendorPrices-pane' type='button' role='tab'
                    aria-controls='nav-vendorsetup' aria-selected='false' onclick="vendor.settab('vendorPrices-pane');">Space Pricing Options
            </button>
        </li>
    </ul>
    <div class="tab-content" id="vendorAdmin-content">
        <div class="tab-pane fade show active" id="regionTypes-pane" role="tabpanel" aria-labelledby="regionTypes-tab" tabindex="0">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-sm-auto m-0 p-0" id="regionType-div"></div>
                </div>
                <div class="row mt-2">
                    <div class="col-sm-auto" id="types-buttons">
                        <button id="types-undo" type="button" class="btn btn-secondary btn-sm" onclick="vendor.undoTypes(); return false;" disabled>Undo</button>
                        <button id="types-redo" type="button" class="btn btn-secondary btn-sm" onclick="vendor.redoTypes(); return false;" disabled>Redo</button>
                        <button id="types-addrow" type="button" class="btn btn-secondary btn-sm" onclick="vendor.addrowTypes(); return false;">Add New</button>
                        <button id="types-save" type="button" class="btn btn-primary btn-sm"  onclick="vendor.saveTypes(); return false;" disabled>Save Changes</button>
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
                        <button id="regions-undo" type="button" class="btn btn-secondary btn-sm" onclick="vendor.undoRegions(); return false;" disabled>Undo</button>
                        <button id="regions-redo" type="button" class="btn btn-secondary btn-sm" onclick="vendor.redoRegions(); return false;" disabled>Redo</button>
                        <button id="regions-addrow" type="button" class="btn btn-secondary btn-sm" onclick="vendor.addrowRegions(); return false;">Add New</button>
                        <button id="regions-save" type="button" class="btn btn-primary btn-sm"  onclick="vendor.saveRegions(); return false;" disabled>Save Changes</button>
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
                        <button id="years-undo" type="button" class="btn btn-secondary btn-sm" onclick="vendor.undoYears(); return false;" disabled>Undo</button>
                        <button id="years-redo" type="button" class="btn btn-secondary btn-sm" onclick="vendor.redoYears(); return false;" disabled>Redo</button>
                        <button id="years-addrow" type="button" class="btn btn-secondary btn-sm" onclick="vendor.addrowYears(); return false;">Add New</button>
                        <button id="years-save" type="button" class="btn btn-primary btn-sm"  onclick="vendor.saveYears(); return false;" disabled>Save Changes</button>
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
                        <button id="spaces-undo" type="button" class="btn btn-secondary btn-sm" onclick="vendor.undoSpaces(); return false;" disabled>Undo</button>
                        <button id="spaces-redo" type="button" class="btn btn-secondary btn-sm" onclick="vendor.redoSpaces(); return false;" disabled>Redo</button>
                        <button id="spaces-addrow" type="button" class="btn btn-secondary btn-sm" onclick="vendor.addrowSpaces(); return false;">Add New</button>
                        <button id="spaces-save" type="button" class="btn btn-primary btn-sm"  onclick="vendor.saveSpaces(); return false;" disabled>Save Changes</button>
                    </div>
                </div>                        
            </div>
        </div>
        <div class="tab-pane fade show" id="vendorPrices-pane" role="tabpanel" aria-labelledby="vendorPrices-tab" tabindex="0">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-sm-auto m-0 p-0" id="spacePrices-div"></div>
                </div>
                <div class="row mt-2">
                    <div class="col-sm-auto" id="spacePrices-buttons">
                        <button id="spacePrices-undo" type="button" class="btn btn-secondary btn-sm" onclick="vendor.undoSpacePrices(); return false;" disabled>Undo</button>
                        <button id="spacePrices-redo" type="button" class="btn btn-secondary btn-sm" onclick="vendor.redoSpacePrices(); return false;" disabled>Redo</button>
                        <button id="spacePrices-addrow" type="button" class="btn btn-secondary btn-sm" onclick="vendor.addrowSpacePrices(); return false;">Add New</button>
                        <button id="spacePrices-save" type="button" class="btn btn-primary btn-sm"  onclick="vendor.saveSpacePrices(); return false;" disabled>Save Changes</button>
                    </div>
                </div>        
            </div>
        </div>
    </div>
</div>
`;
        this.#vendor_pane.innerHTML = html;

        // set up regionTypes
        this.#regionTypesavebtn = document.getElementById('types-save');
        this.#regionTypeundobtn = document.getElementById('types-undo');
        this.#regionTyperedobtn = document.getElementById('types-redo');

        // set up regions
        this.#regionsavebtn = document.getElementById('regions-save');
        this.#regionundobtn = document.getElementById('regions-undo');
        this.#regionredobtn = document.getElementById('regions-redo');

        // get initial data
        clear_message();
        clearError();
        var _this = this;
        var script = "scripts/vendorUpdateGetData.php";
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
        this.#vendor_pane.innerHTML = '';
    };


    // common code for changing tabs
    settab(tabname) {
        clearError();
        clear_message();
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

        if (data['vendorRegionTypes']) {
            this.drawRegionTypes(data);
            this.drawRegions(data);
            drew_regions = true;
        }

        if ((!drew_regions) && data['vendorRegions']) {
            this.drawRegions(data);
            this.drawRegionYears(data);
            drew_regionYears = true;
        }

        if ((!drew_regionYears) && data['vendorRegionYears']) {
            this.drawRegionYears(data);
            this.drawSpaces(data);
            this.drawSpacePrices(data);
            drew_spaces = true;
            drew_spacePrices = true;
        }

        if ((!drew_spaces) && data['vendorSpaces']) {
            this.drawSpaces(data);
            this.drawSpacePrices(data);
            drew_spacePrices = true;
        }

        if ((!drew_spacePrices) && data['vendorSpacePrices']) {
            drawSpacePrices(data);
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

        if (data['vendorRegionTypes']) {
            this.#regionType = data['vendorRegionTypes'];
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
            columns: [
                {rowHandle: true, formatter: "handle", frozen: true, width: 30, minWidth: 30, maxWidth: 30, headerSort: false},
                {
                    title: "Region Type",
                    field: "regionType",
                    width: 200,
                    headerSort: true,
                    headerWordWrap: true,
                    editor: "input",
                    editorParams: {maxlength: "16"}
                },
                {
                    title: "Request Approval Required",
                    field: "requestApprovalRequired",
                    headerSort: true,
                    width: 140,
                    headerWordWrap: true,
                    editor: "list",
                    editorParams: {
                        values: ['None', 'Once', 'Annual']
                    },
                    validator: "required"
                },
                {
                    title: "Purchase Approval Required",
                    field: "purchaseApprovalRequired",
                    headerSort: true,
                    width: 140,
                    headerWordWrap: true,
                    editor: "list",
                    editorParams: {
                        values: ['Y', 'N']
                    },
                    validator: "required"
                },
                {
                    title: "Purchase Area Totals",
                    field: "purchaseAreaTotals",
                    headerSort: true,
                    width: 140,
                    headerWordWrap: true,
                    editor: "list",
                    editorParams: {
                        values: ['unique', 'combined']
                    },
                    validator: "required"
                },
                {
                    title: "Mail-in Allowed",
                    field: "mailinAllowed",
                    headerSort: true,
                    width: 140,
                    headerWordWrap: true,
                    editor: "list",
                    editorParams: {
                        values: ['Y', 'N']
                    },
                    validator: "required"
                },
                {
                    title: "Active", field: "active", headerSort: true, width: 80, editor: "list", editorParams: {
                        values: ['Y', 'N']
                    }, validator: "required"
                },
                {title: "Sort Order", field: "sortorder", visible: this.#debugVisible, headerFilter: false, headerWordWrap: true, width: 80,},
                {title: "Orig Key", field: "regionTypeKey", visible: this.#debugVisible, headerFilter: false, headerWordWrap: true, width: 200,},
                {
                    title: "Delete", field: "uses", formatter: deleteicon, hozAlign: "center", headerSort: false,
                    cellClick: function (e, cell) {
                        deleterow(e, cell.getRow());
                    }
                },
                {field: "to_delete", visible: false,}
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

        if (data['vendorRegions']) {
            this.#regions = data['vendorRegions'];

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
            data: this.#regions,
            layout: "fitDataTable",
            columns: [
                {title: "ID", field: "id", width: 50, hozAlign: "right", headerSort: false},
                {
                    title: "Type", field: "regionType", headerSort: true, width: 100, headerFilter: true, headerFilterParams: {values: this.#regionType_arr},
                    editor: "list", editorParams: {values: this.#regionType_arr}, validator: "required"
                },
                {
                    title: "Short Name", field: "shortname", headerSort: true, headerFilter: true, width: 150,
                    editor: "input", editorParams: {elementAttributes: {maxlength: "32"}}, validator: "required"
                },
                {
                    title: "Name", field: "name", width: 250, headerSort: true, headerFilter: true,
                    editor: "input", editorParams: {elementAttributes: {maxlength: "128"}}, validator: "required"
                },
                {title: "Description", field: "description", headerFilter: true, width: 450, headerSort: false,},
                {
                    title: 'Included', field: "includedMemId", width: 150, headerSort: false,
                    editor: "list", formatter: "lookup", formatterParams: this.#memListArr, editorParams: {values: this.#memListArr}
                },
                {
                    title: 'Additional', field: "additionalMemId", width: 150, headerSort: false,
                    editor: "list", formatter: "lookup", formatterParams: this.#memListArr, editorParams: {values: this.#memListArr}
                }
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
        
        if (data['vendorRegionsYears']) {
            this.#regionYears = data['vendorRegionYears'];
        }

        this.#regionYeardirty = false;
        this.#regionYearsTable = new Tabulator('#regionYears-div', {
            maxHeight: "800px",
            history: true,
            data: this.#regionYears,
            layout: "fitDataTable",
            columns: [
                {title: "ID", field: "id", width: 50, hozAlign: "right", headerSort: false,},
                {title: "Conid", field: "conid", width: 60, hozAlign: "right", headerSort: false,},
                {
                    title: "Vendor Space", field: "vendorSpace", headerSort: true, width: 100, headerFilter: true, headerFilterParams: {values: this.regionListArr},
                    editor: "list", editorParams: {values: this.#regionListArr}, validator: "required"
                },
                {
                    title: "Owner Name", field: "ownerName", headerSort: true, headerFilter: true, width: 250,
                    editor: "input", editorParams: {elementAttributes: {maxlength: "64"}}, validator: "required"
                },
                {
                    title: "Owner Email", field: "ownerEmail", width: 250, headerSort: true, headerFilter: true,
                    editor: "input", editorParams: {elementAttributes: {maxlength: "64"}}, validator: "required"
                },
            ],
        });
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

        if (data['vendorSpaces'])
            this.#spaces = data['vendorSpaces'];

        this.#spacedirty = false;
        this.#spacesTable = new Tabulator('#spaces-div', {
            maxHeight: "800px",
            history: true,
            data: this.#spaces,
            layout: "fitDataTable",
            columns: [
                {title: "ID", field: "id", width: 50, hozAlign: "right", headerSort: false},
                {
                    title: "Vendor Space",
                    field: "spaceType",
                    headerSort: true,
                    headerWordWrap: true,
                    width: 100,
                    headerFilter: true,
                    headerFilterParams: {values: this.#regionListArr},
                    editor: "list",
                    editorParams: {values: this.#regionListArr},
                    validator: "required"
                },
                {
                    title: "Short Name", field: "shortname", headerSort: true, headerFilter: true, width: 150,
                    editor: "input", editorParams: {elementAttributes: {maxlength: "32"}}, validator: "required"
                },
                {
                    title: "Name", field: "name", width: 250, headerSort: true, headerFilter: true,
                    editor: "input", editorParams: {elementAttributes: {maxlength: "128"}}, validator: "required"
                },
                {title: "Description", field: "description", headerFilter: true, width: 450, headerSort: false,},
                {title: 'Units', field: "unitsAvailable", width: 60, hozAlign: "right", headerSort: false, editor: "input", editorParams: {maxlength: "10"}},
            ],
        });
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

        if (data['vendorSpacePricess'])
            this.#spacePrices = data['vendorSpacePricess'];

        this.#spacePricedirty = false;
        this.#spacePricesTable = new Tabulator('#spacePrices-div', {
            maxHeight: "800px",
            history: true,
            data: this.#spacePrices,
            layout: "fitDataTable",
            columns: [
                {title: "ID", field: "id", width: 50, hozAlign: "right", headerSort: false},
                {
                    title: "Vendor Space", field: "spaceId", width: 150, headerSort: true, headerFilter: true, headerFilterParams: {values: this.#regionListArr},
                    editor: "list", formatter: "lookup", formatterParams: this.#regionListArr, editorParams: {values: this.#regionListArr}
                },
                {
                    title: "Code", field: "code", headerSort: true, headerFilter: true, width: 150,
                    editor: "input", editorParams: {elementAttributes: {maxlength: "32"}}, validator: "required"
                },
                {
                    title: "Description", field: "description", headerSort: false, headerFilter: true, width: 600,
                    editor: "input", editorParams: {elementAttributes: {maxlength: "64"}}, validator: "required"
                },
                {
                    title: 'Units', field: "units", width: 60, hozAlign: "right", headerSort: false, editor: "input", editorParams: {maxlength: "10"},
                    headerFilter: true, headerFilterFunc: numberHeaderFilter,
                },
                {
                    title: 'Price', field: "price", width: 60, hozAlign: "right", headerSort: false,
                    editor: "input", editorParams: {maxlength: "10"},
                    formatter: "money", formatterParams: {decimal: '.', thousand: ',', symbol: '$', negativeSign: true},
                    headerFilter: true, headerFilterFunc: numberHeaderFilter,
                },
                {
                    title: "Incl Mem", headerWordWrap: true, field: "includedMemberships", width: 50, hozAlign: "right", headerSort: false,
                    editor: "number", editorParams: {min: 0, max: 99, maxlength: 2},
                },
                {
                    title: "Addl Mem", headerWordWrap: true, field: "additionalMemberships", width: 50, hozAlign: "right", headerSort: false,
                    editor: "number", editorParams: {min: 0, max: 99, maxlength: 2},
                },
                {
                    title: "Req", field: "requestable", width: 50, hozAlign: "right", headerSort: false,
                    editor: "tickCross", formatter: "tickCross",
                }
            ],
        });
    }

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
        this.#regionTypeTable.addRow({spaceType: 'new-row', active: 'Y', sortorder: 99, uses: 0}, false).then(function (row) {
            row.getTable().scrollToRow(row);
            _this.checkTypesUndoRedo();
        });
    }

    // set undo / redo status for vendor type buttons
    checkTypesUndoRedo() {
        var undosize = this.#regionTypeTable.getHistoryUndoSize();
        this.#regionTypeundobtn.disabled = undosize <= 0;
        this.#regionTyperedobtn.disabled = this.#regionTypeTable.getHistoryRedoSize() <= 0;
        return undosize;
    }

    saveTypesComplete(data, textStatus, jhXHR) {
        var _this = this;

        if ('error' in data && data['error'] != '') {
            if (this.#debug)
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
        if (this.#regionTypeTable != null) {
            var _this = this;

            var invalids = this.#regionTypeTable.validate();
            if (invalids !== true) {
                console.log(invalids);
                alert("spaceType Table does not pass validation, please check for empty cells or cells in red");
                return false;
            }
            this.#regionTypesavebtn.innerHTML = "Saving...";
            this.#regionTypesavebtn.disabled = true;

            var script = "scripts/vendorUpdateGetData.php";

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
    };
};
