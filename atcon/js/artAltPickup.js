// alternate pickup auth class
var altPickupAuth
var locale = 'en-us';
var currencyFmt = null;

// initialization
// lookup all DOM elements
// load mapping tables
window.onload = function initpage() {
    // current items
    locale = config.locale;
    currencyFmt = new Intl.NumberFormat(locale, {
        style: 'currency',
        currency: config.currency,
    });

    // class
    altPickupAuth = new AltPickupAuth();
};

class AltPickupAuth {
    // local variables
    #authList = null;
    #authListTable = null;
    #debug = 0;

    constructor(debug = 0) {
        this.#debug = debug;
        this.loadInitialData();
    }

    // load the initial data for the startup display
    loadInitialData() {
        // load the initial data and the proceed to set up the rest of the system
        var postData = {
            ajax_request_action: 'loadInitialData',
        };
        $.ajax({
            method: "POST",
            url: "scripts/artAltPickup_updateGetData.php",
            data: postData,
            success: function (data, textstatus, jqxhr) {
                if (data.error !== undefined) {
                    show_message(data.error, 'error');
                    return;
                }
                if (data.message !== undefined) {
                    show_message(data.message, 'success');
                }
                altPickupAuth.draw(data);
            },
            error: showAjaxError,
        });
    }

    // draw or replace the auth list table
    draw(data) {
        if (this.#authListTable) {
            this.#authListTable.replaceData(data.authList);
        } else {
            this.#authListTable = new Tabulator('#pickupAuthTable', {
                data: data.authList,
                layout: "fitData",
                maxHeight: "800px",
                movableRows: false,
                history: true,
                pagination: data.authList.length > 25,
                paginationSize: 10,
                paginationSizeSelector: [10, 25, 50, true], //enable page size select element with these options
                paginationElement: document.getElementById('tabPaginationDiv'),
                columns: [
                    {title: "Conid", field: "conid", headerWordWrap: true, width: 70, headerSort: false, visible: this.#debug > 0, },
                    {title: "Bidder Perid ", field: "bidderPerid", headerWordWrap: true, hozAlign: 'right', headerHozAlign: 'right', 
                        headerSort: true, headerFilter: true, headerFilterFunc:numberHeaderFilter, width: 100, },
                    {title: "Bidder Full Name", field: "bidderFullName", headerSort: true, headerWordWrap: true, width: 300,
                        headerFilter: true, headerFilterFunc: fullNameHeaderFilter, },
                    {title: "Pickup Perid ", field: "pickupPerid", headerWordWrap: true, hozAlign: 'right', headerHozAlign: 'right',
                        headerSort: true, headerFilter: true, headerFilterFunc:numberHeaderFilter, width: 100, },
                    {title: "Pickup Full Name", field: "pickupFullName", headerSort: true, headerWordWrap: true,  width: 300, headerFilter: true, },
                    {title: "Date Created", field: "createDate", headerSort: true,  headerWordWrap: true, width: 190,
                        headerFilter: true, headerFilterFunc:dateStringHeaderFilter, },
                    {title: "Created By", field: "createdBy", headerWordWrap: true, hozAlign: 'right', headerHozAlign: 'right',
                        headerSort: true, headerFilter: true, headerFilterFunc:numberHeaderFilter, width: 100, },
                    {title: "Active", field: "active", headerSort: false, headerFilter: true, width: 80, hozAlign: 'center',},
                    {title: "Cancel Date", field: "deactivateDate", headerSort: true,  headerWordWrap: true, width: 190,
                        headerFilter: true, headerFilterFunc:dateStringHeaderFilter, },
                    {title: "Cancelled By", field: "deactivatedBy", headerWordWrap: true, hozAlign: 'right', headerHozAlign: 'right',
                        headerSort: true, headerFilter: true, headerFilterFunc:numberHeaderFilter, width: 100, },
                    {field: 'first_name', visible: false,},
                    {field: 'middle_name', visible: false,},
                    {field: 'last_name', visible: false,},
                ],
            });
        }
    }

    // bottom of page buttons
    addnew() {
        console.log("addnew called");
    }

    undo() {
        console.log("undo called");
    }

    redo() {
        console.log("redo called");
    }

    save() {
        console.log("save called");
    }

    download(format) {
        if (this.#authListTable == null)
            return;

        let filename = 'altPickupAuths';
        let tabledata = JSON.stringify(this.#authListTable.getData("active"));
        let excludeList = [];
        downloadFilePost(format, filename, tabledata, excludeList);
    }
};


