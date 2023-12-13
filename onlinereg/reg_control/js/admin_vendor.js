//import { TabulatorFull as Tabulator } from 'tabulator-tables';
// vendor class - all vendor space config functions
class vendorsetup {
    #message_div = null;
    #vendor_pane = null;
    #vendorSpacesDiv = null;
    #vendorSpacePricesDiv = null;
    #vendorSpacesTable = null;
    #vendorSpacePricesTable = null;
    #memList = null;
    #vendorSpaces = null;
    #vendorSpacePrices = null;

    // globals before open
    constructor() {
        this.#message_div = document.getElementById('test');
        this.#vendor_pane = document.getElementById('vendor-pane');
    };

    // called on open of the vendor window
    open() {
        var html = `<h4><strong>Configure Vendor Spaces:</strong></h4>
<div class="container-fluid">
    <div class="row">
        <div class="col-sm-auto m-2 p-0" id="vendorSpaces-div"></div>
    </div>
    <div class="row mt-4">
        <div class="col-sm-auto m-2 p-0" id="vendorSpacePrices-div"></div>
    </div>
</div>
`;
        this.#vendor_pane.innerHTML = html;
        this.#vendorSpacePricesDiv = document.getElementById('vendorSpacePrices-div');

        // get initial data
        var script = "scripts/vendorGetData.php";
        $.ajax({
            url: script,
            method: 'POST',
            data: { gettype: 'all'} ,
            success: function (data, textStatus, jhXHR) {
                if (data['error']) {
                    showError(data['error']);
                    return false;
                }
                vendor.draw(data);
            },
            error: function (jqXHR, textStatus, errorThrown) {
                showError("ERROR in " + script + ": " + textStatus, jqXHR);
                return false;
            }
        });
    }

    // on close of the pane, clean up the items
    close() {
        if (this.#vendorSpacesTable) {
            this.#vendorSpacesTable.destroy();
            this.#vendorSpacesTable = null;
        }
        if (this.#vendorSpacePricesTable) {
            this.#vendorSpacePricesTable.destroy();
            this.#vendorSpacePricesTable = null;
        }
        this.#vendor_pane.innerHTML = '';
    };

    // draw - draw screen for editing
    draw(data) {
        this.#vendorSpaces = data['vendorSpaces'];
        this.#vendorSpacePrices = data['vendorSpacePrices'];
        this.#memList = data['memList'];
        var memListArr = {};
        this.#memList.forEach(m => { memListArr[m.id] = m['label'] + ':' + m['price'].toString(); });
        //console.log(memListArr);
        var spaceListArr = {};
        this.#vendorSpaces.forEach(s => { spaceListArr[s.id] = s.shortname; });
        console.log(spaceListArr);

        // draw vendor table
        this.#vendorSpacesTable = new Tabulator('#vendorSpaces-div', {
            maxHeight: "400px",
            history: true,
            data: this.#vendorSpaces,
            layout: "fitDataTable",
            columns: [
                { title: "ID", field: "id", width: 50, hozAlign:"right", headerSort: false },
                { title: "Type", field: "spaceType", headerSort: true, width: 100, editor: "list", editorParams: {
                    values: [ 'artshow', 'dealers', 'fan', 'virtual'] }, validator: "required" },
                { title: "Short Name", field: "shortname", headerSort: true, headerFilter: true, width: 150,
                    editor: "input", editorParams: { elementAttributes: { maxlength: "32" } }, validator: "required" },
                { title: "Name", field: "name", width: 250, headerSort: true, headerFilter: true,
                    editor: "input", editorParams: { elementAttributes: { maxlength: "128" } }, validator: "required" },
                { title: "Description", field: "description", headerFilter: true, width: 450, headerSort: false, },
                { title: 'Units', field: "unitsAvailable", width: 60, hozAlign:"right", headerSort: false, editor: "input", editorParams: { maxlength: "10"}},
                { title: 'Included', field: "includedMemId", width: 150, headerSort: false,
                    editor: "list", formatter:"lookup", formatterParams: memListArr, editorParams: { values: memListArr }
                },
                { title: 'Additional', field: "additionalMemId", width: 150, headerSort: false,
                    editor: "list", formatter:"lookup", formatterParams: memListArr, editorParams: { values: memListArr  }
                }
            ],
        });

        // for now draw vendorSpacePrices table for formatting and data check
        this.#vendorSpacePricesTable = new Tabulator('#vendorSpacePrices-div', {
            maxHeight: "400px",
            history: true,
            data: this.#vendorSpacePrices,
            layout: "fitDataTable",
            columns: [
                { title: "ID", field: "id", width: 50, hozAlign:"right", headerSort: false },
                { title: "Vendor Space", field: "spaceId", width: 150, headerSort: true, headerFilter: true, headerFilterParams: { values: spaceListArr },
                    editor: "list", formatter: "lookup", formatterParams: spaceListArr, editorParams: {values: spaceListArr}
                },
                { title: "Code", field: "code", headerSort: true, headerFilter: true, width: 150,
                    editor: "input", editorParams: { elementAttributes: { maxlength: "32" } }, validator: "required" },
                { title: "Description", field: "description", headerSort: false, headerFilter: true, width: 600,
                    editor: "input", editorParams: { elementAttributes: { maxlength: "64" } }, validator: "required" },
                { title: 'Units', field: "units", width: 60, hozAlign:"right", headerSort: false, editor: "input", editorParams: { maxlength: "10"}},
                { title: 'Price', field: "price", width: 60, hozAlign:"right", headerSort: false,
                    editor: "input", editorParams: { maxlength: "10"},
                    formatter:"money", formatterParams: {decimal: '.', thousand: ',', symbol:'$', negativeSign:true}},
                { title: "Incl Mem", headerWordWrap: true, field: "includedMemberships", width: 50, hozAlign:"right", headerSort: false,
                    editor: "number", editorParams: {min: 0, max: 99, maxlength:2},
                },
                { title: "Addl Mem", headerWordWrap: true, field: "additionalMemberships", width: 50, hozAlign:"right", headerSort: false,
                    editor: "number", editorParams: {min: 0, max: 99, maxlength:2},
                },
                { title: "Req", field: "requestable", width: 50, hozAlign:"right", headerSort: false,
                    editor: "tickCross", formatter: "tickCross",
                }
            ],
        });
    }
};
