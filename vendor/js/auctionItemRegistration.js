/* Auction Item Registration related functions
 */
var artPagination = false;
var nfsPagination = false;
var printPagination = false;

class AuctionItemRegistration {

// items related to artists, or other exhibitors registering items
    #item_registration = null;
    #item_registration_title = null
    #item_registration_btn = null;
    #closeAnyway = false;

    #region = 0;
    #numItems = null;
    #maxItems = null;
    #allowQuickSale = true;
    #ownerName = '';
    #ownerEmail = '';
    #regionName = '';
    #addItemIndex = 1;

    // auction section
    #artItemTable = null;
    #artItemsDirty = false;
    #artSaveBtn = null;
    #artUndoBtn = null;
    #artRedoBtn = null;
    #artAddBtn = null;
    #newArt = null;

    // print section
    #printItemTable = null;
    #printItemsDirty = false;
    #printSaveBtn = null;
    #printUndoBtn = null;
    #printRedoBtn = null;
    #printAddBtn = null;
    #newPrintRow = null;
    #newPrint = null;

    // not for sale section
    #nfsItemTable = null;
    #nfsItemsDirty = false;
    #nfsSaveBtn = null;
    #nfsUndoBtn = null;
    #nfsRedoBtn = null;
    #nfsAddBtn = null;
    #newNfsRow = null;
    #newNfs = null;

    // import modal
    #importModal = null;
    #itemImportBtn = null;
    #importTableDiv = null;
    #importTable = null;
    #debug = 0;
    #debugVisible = false;

// init
    constructor(debug=0) {
        this.#debug = debug;
        let id = document.getElementById('item_import');
        if (id != null) {
            this.#importModal = new bootstrap.Modal(id, {focus: true, backdrop: 'static'});
            this.#itemImportBtn = document.getElementById('import_items_btn');
            this.#importTableDiv = document.getElementById('importTable');
        }
        id = document.getElementById('item_registration');
        if (id != null) {
            this.#item_registration = new bootstrap.Modal(id, {focus: true, backdrop: 'static'});
            this.#item_registration_btn = document.getElementById('item_registration_btn');
            this.#item_registration_title = document.getElementById('item_registration_title');
        }
        if (this.#debug & 1) {
            this.#debugVisible = true;
        }
    };

    printSheets(type, region = null, conid = null) {
        if (region == null)
            region = this.#region;
        let script = "scripts/bidsheets.php?type=" + type + "&region=" + region;
        if (conid != null)
            script += '&conid=' + conid;
        window.open(script, "_blank")
    }

    closeModal() {
        if ((!this.#closeAnyway) && (this.#artItemsDirty || this.#printItemsDirty || this.#nfsItemsDirty)) {
            show_message("You have unsaved changes, save them first, press close again to close without saving them.", 'warn', 'ir_message_div');
            this.#closeAnyway = true;
            return;
        }
        clear_message('ir_message_div');
        this.#closeAnyway = false;
        this.#item_registration.hide();
    }

    open(region, art= null, print = null, nfs = null) {
        clear_message('ir_message_div');
        this.#region = region;
        let _this = this;
        let script = "scripts/getItems.php"
        clear_message();
        $.ajax({
            url: script,
            method: 'POST',
            data: {gettype: 'all', region: region},
            success: function (data, textSatus, jhXHR) {
                if (data['error']) {
                    show_message(data['error'], 'error');
                    return false;
                }
                _this.draw(data, art, print, nfs);

            },
            error: function (jqXHR, textStatus, errorThrown) {
                show_message("ERROR in " + script + ": " + textStatus, 'error');
                return false;
            }
        });
    };

    draw(data, art = null, print = null, nfs = null) {
        this.#maxItems = data.inv.maxInventory;
        this.#ownerName = data.inv.ownerName;
        this.#ownerEmail = data.inv.ownerEmail;
        this.#regionName = data.inv.name;
        this.#allowQuickSale = data.inv.allowQuickSale == 'Y';
        this.#numItems = data.itemCount;
        if (this.#maxItems == 0)
            this.#maxItems = 999999;

        this.#artSaveBtn = document.getElementById('art-save');
        this.#artUndoBtn = document.getElementById('art-undo');
        this.#artRedoBtn = document.getElementById('art-redo');
        this.#artAddBtn = document.getElementById('art-addrow');

        this.#printSaveBtn = document.getElementById('print-save');
        this.#printUndoBtn = document.getElementById('print-undo')
        this.#printRedoBtn = document.getElementById('print-redo');
        this.#printAddBtn = document.getElementById('print-addrow');

        this.#nfsSaveBtn = document.getElementById('nfs-save');
        this.#nfsUndoBtn = document.getElementById('nfs-undo');
        this.#nfsRedoBtn = document.getElementById('nfs-redo');
        this.#nfsAddBtn = document.getElementById('nfs-addrow');
        this.drawArtItemTable(data['items'], art);
        this.drawPrintItemTable(data['items'], print);
        this.drawNfsItemTable(data['items'], nfs);

        this.validateLoadLimit(false);
        this.#item_registration_title.innerHTML = '<strong>' + this.#regionName + ' Item Registration</strong>';
        this.#item_registration.show();
    };

    close() {
        this.#region = 0;
        if(this.#artItemTable) {
            this.#artItemTable.off('dataChanged');
            this.#artItemTable.off('cellEdited');
            this.#artItemTable.destroy();
            this.#artItemTable = null;
        }
        if(this.#printItemTable) {
            this.#printItemTable.off('dataChanged');
            this.#printItemTable.off('cellEdited');
            this.#printItemTable.destroy();
            this.#printItemTable = null;
        }
        if(this.#nfsItemTable) {
            this.#nfsItemTable.off('dataChanged');
            this.#nfsItemTable.off('cellEdited');
            this.#nfsItemTable.destroy();
            this.#nfsItemTable = null;
        }

        this.#item_registration.hide();addoverride-btn
    };

    dataChangedArt(data=null) {
        //data - the updated table data
        if (!this.#artItemsDirty) {
            this.#artSaveBtn.innerHTML = "Save Changes*";
            this.#artSaveBtn.disabled = false;
            this.#artItemsDirty = true;
        }
        if(data == null){
            this.#artSaveBtn.innerHTML = "Save Changes*";
            this.#artSaveBtn.disabled = false;
        }
        this.checkArtUndoRedo();
    };
    checkArtUndoRedo() {
        let undosize = this.#artItemTable.getHistoryUndoSize();
        this.#artUndoBtn.disabled = undosize <= 0;
        this.#artRedoBtn.disabled = this.#artItemTable.getHistoryRedoSize() <= 0;
        return undosize;

    }
    redoArt() {
        if (this.#artItemTable != null) {
            this.#artItemTable.redo();

            if (this.checkArtUndoRedo() > 0) {
                this.#artItemsDirty = true;
                this.#artSaveBtn.innerHTML = "Save Changes*";
                this.#artSaveBtn.disabled = false;
            }
        }
    };
    undoArt() {
        if (this.#artItemTable != null) {
            this.#artItemTable.undo();

            if (this.checkArtUndoRedo() > 0) {
                this.#artItemsDirty = true;
                this.#artSaveBtn.innerHTML = "Save Changes*";
                this.#artSaveBtn.disabled = false;
            }
        }
    };

    // validate we are not over the limit
    validateMaxLimit(artType) {
        if (this.#numItems >= this.#maxItems) {
            show_message('You already have ' + this.#numItems + ' items in your total inventory, out of a limit of ' + this.#maxItems +
                ' for the ' + this.#regionName +
                '<br/>You must delete one or more items from your inventory and save the changes before you can add any more.', 'error', 'ir_message_div');

            this.#artAddBtn.disabled = true;
            this.#printAddBtn.disabled = true;
            this.#nfsAddBtn.disabled = true;
            return false;
        }

        this.#numItems++;
        return true;
    }

    validateLoadLimit(recomputeItemCount, section='', data = null) {
        if (recomputeItemCount) {
            this.#numItems = 0;
            if (section == 'art')
                this.#numItems += data.length;
            else if (this.#artItemTable)
                this.#numItems += this.#artItemTable.getData().length;
            else if (data.hasOwnProperty('items') && data.items.hasOwnProperty('art'))
                this.#numItems += data.items.art.length;

            if (section == 'print')
                this.#numItems += data.length;
            else if (this.#printItemTable)
                this.#numItems += this.#printItemTable.getData().length;
            else if (data.hasOwnProperty('items') && data.items.hasOwnProperty('print'))
                this.#numItems += data.items.print.length;

            if (section == 'nfs')
                this.#numItems += data.length;
            else if (this.#nfsItemTable)
                this.#numItems += this.#nfsItemTable.getData().length;
            else if (data.hasOwnProperty('items') && data.items.hasOwnProperty('nfs'))
                this.#numItems += data.items.nfs.length;

        }
        if (this.#numItems >= this.#maxItems) {
            let limitWord = this.#numItems == this.#maxItems ? 'at' : 'beyond';
            show_message("Warning: You are " + limitWord + " the limit of " + this.#maxItems + " inventory items for " + this.#regionName +
                ",<br/>You will not be allowed to add more until you delete some and save your changes to get below the limit.<br/><br/>" +
                "If you have any questions about the limit, please reach out to " + this.#ownerName + " at " + this.#ownerEmail,
                'warn', 'ir_message_div');

            this.#artAddBtn.disabled = true;
            this.#printAddBtn.disabled = true;
            this.#nfsAddBtn.disabled = true;
            return;
        }

        this.#artAddBtn.disabled = false;
        this.#printAddBtn.disabled = false;
        this.#nfsAddBtn.disabled = false;
    }

    // deal with tab at end to add a row
    tabNewRow()  {
        // recompute and warn if over the limit
        this.validateLoadLimit(true);
        this.#addItemIndex++;
        if (this.#numItems >= this.#maxItems) { // note: >= because the new row hasn't been added yet.
           return {id: -9999, item_key: 'Over' + this.#addItemIndex.toString(), title: 'Over limit, this item will be deleted on save'};
        }

        return {item_key: 'New' + this.#addItemIndex.toString(), 'status': 'Entered'};
    }

    addrowArt(art = null) {
        if (this.validateMaxLimit('Art Auction')) {
            let itemKey = 'new' + this.#addItemIndex.toString();
            this.#addItemIndex++;
            let newRow = {item_key: itemKey, status: 'Entered' };
            if (art != null) {
                newRow.title = art.title;
                newRow.material = art.material;
                newRow.min_price = art.min_price
                newRow.sale_price = art.sale_price;
            }
            this.#artItemTable.addRow(newRow, false).then(function (row) {
                auctionItemRegistration.checkArtUndoRedo();
                if (artPagination) {
                    row.pageTo().then(function () {
                        setCellChanged(row);
                    });
                } else {
                    setCellChanged(row);
                }
            });
        }
    };

    saveArt() {
        let type = 'art';
        if(this.#artItemTable != null) {
            let _this = this;

            let invalids; // TODO validation
            this.#artSaveBtn.innerHTML = "Saving...";
            this.#artSaveBtn.disabled = true;

            let script = "scripts/updateGetItems.php";

            clear_message();
            clear_message('ir_message_div');
            let postdata = {
                region: this.#region,
                itemType: type,
                tabledata: JSON.stringify(this.#artItemTable.getData())
            };

            //console.log(postdata);
            $.ajax({
                url: script,
                method: 'POST',
                data: postdata,
                success: function (data, textStatus, jhXHR) {
                    _this.saveArtComplete(data, textStatus, jhXHR);
                },
                error: function (jqXHR, textStatus, errorThrown) {
                    show_message("ERROR in " + script + ": " + textStatus, 'error', 'ir_message_div');
                    _this.dataChangedArt();
                    return false;
                }
            });
        }
    }
    saveArtComplete(data, textStatus, jhXHR) {
        if (data['error']) {
            show_message(data['error'], 'error', 'ir_message_div');
            this.#artSaveBtn.innerHTML = "Save Changes*";
            this.#artSaveBtn.disabled = false;
            if (data.hasOwnProperty('marks')) {
                this.markRows(this.#artItemTable, data.marks);
            }
            return false;
        }
        if(data['message']) {
            show_message(data['message'], 'success', 'ir_message_div');
        }
        if(data['warn']) {
            show_message(data['warn'], 'warn', 'ir_message_div');
        }

        this.drawArtItemTable(data['items']);
        this.validateLoadLimit(true, 'art', data['items']['art']);
    }

    markRows(table, marks) {
        for(let index = 0; index < marks.length; index++) {
            let mark = marks[index];
            let row = table.getRow(mark.item_key);
            if (row.classList.contains('unsavedWarnBGColor')) {
                row.classList.add('unsavedWarnBGColor');
            }
        }
    }

//TODO change Item Number

    dataChangedPrint(data=null) {
        //data - the updated table data
        if (!this.#printItemsDirty) {
            this.#printSaveBtn.innerHTML = "Save Changes*";
            this.#printSaveBtn.disabled = false;
            this.#printItemsDirty = true;
        }
        if(data == null){
            this.#printSaveBtn.innerHTML = "Save Changes*";
            this.#printSaveBtn.disabled = false;
        }
        this.checkPrintUndoRedo();
    };
    checkPrintUndoRedo() {
        let undosize = this.#printItemTable.getHistoryUndoSize();
        this.#printUndoBtn.disabled = undosize <= 0;
        this.#printRedoBtn.disabled = this.#printItemTable.getHistoryRedoSize() <= 0;
        return undosize;

    }
    redoPrint() {
        if (this.#printItemTable != null) {
            this.#printItemTable.redo();

            if (this.checkPrintUndoRedo() > 0) {
                this.#printItemsDirty = true;
                this.#printSaveBtn.innerHTML = "Save Changes*";
                this.#printSaveBtn.disabled = false;
            }
        }
    };
    undoPrint() {
        if (this.#printItemTable != null) {
            this.#printItemTable.undo();

            if (this.checkPrintUndoRedo() > 0) {
                this.#printItemsDirty = true;
                this.#printSaveBtn.innerHTML = "Save Changes*";
                this.#printSaveBtn.disabled = false;
            }
        }
    };
    addrowPrint(print = null) {
        if (this.validateMaxLimit('Print Shop')) {
            let itemKey = 'new' + this.#addItemIndex.toString();
            this.#addItemIndex++;
            let newRow = {item_key: itemKey, status: 'Entered' };
            if (print != null) {
                newRow.title = print.title;
                newRow.material = print.material;
                newRow.min_price = print.min_price
                newRow.sale_price = print.sale_price;
                newRow.original_qty = print.original_qty;
            }
            this.#printItemTable.addRow(newRow, false).then(function (row) {
                auctionItemRegistration.checkPrintUndoRedo();
                if (printPagination) {
                    row.pageTo().then(function () {
                        setCellChanged(row);
                    });
                } else {
                    setCellChanged(row);
                }
            });
        }
    };

    savePrint() {
        let type = 'print';
        if(this.#artItemTable != null) {
            let _this = this;

            let invalids; // TODO validation
            this.#printSaveBtn.innerHTML = "Saving...";
            this.#printSaveBtn.disabled = true;

            let script = "scripts/updateGetItems.php";

            clear_message();
            clear_message('ir_message_div');
            let postdata = {
                region: this.#region,
                itemType: type,
                tabledata: JSON.stringify(this.#printItemTable.getData())
            };

            //console.log(postdata);
            $.ajax({
                url: script,
                method: 'POST',
                data: postdata,
                success: function (data, textStatus, jhXHR) {
                    _this.savePrintComplete(data, textStatus, jhXHR);
                },
                error: function (jqXHR, textStatus, errorThrown) {
                    show_message("ERROR in " + script + ": " + textStatus, 'error', 'ir_message_div');
                    _this.dataChangedArt();
                    return false;
                }
            });
        }
    }
    savePrintComplete(data, textStatus, jhXHR) {
        if('error' in data) {
            if (data['error']) {
                show_message(data['error'], 'error', 'ir_message_div');
                this.#printSaveBtn.innerHTML = "Save Changes*";
                this.#printSaveBtn.disabled = false;
                if (data.hasOwnProperty('marks')) {
                    this.markRows(this.#printItemTable, data.marks);
                }
                return false;
            }
            if (data['message']) {
                show_message(data['message'], 'error', 'ir_message_div');
            }
            this.#printSaveBtn.innerHTML = "Save Changes*";
            this.#printSaveBtn.disabled = false;
            return false;
        }
        if(data['message'] !== undefined) {
            show_message(data['message'], 'success', 'ir_message_div');
        }
        if(data['warn'] !== undefined) {
            show_message(data['warn'], 'warn', 'ir_message_div');
        }

        //console.log(data);
        this.drawPrintItemTable(data['items']);
        this.validateLoadLimit(true, 'print', data['items']['print']);
    }

    dataChangedNfs(data = null) {
        //data - the updated table data
        if (!this.#nfsItemsDirty) {
            this.#nfsSaveBtn.innerHTML = "Save Changes*";
            this.#nfsSaveBtn.disabled = false;
            this.#nfsItemsDirty = true;
        }
        if(data == null){
            this.#nfsSaveBtn.innerHTML = "Save Changes*";
            this.#nfsSaveBtn.disabled = false;
        }
        this.checkNfsUndoRedo();
    };
    checkNfsUndoRedo() {
        let undosize = this.#nfsItemTable.getHistoryUndoSize();
        this.#nfsUndoBtn.disabled = undosize <= 0;
        this.#nfsRedoBtn.disabled = this.#nfsItemTable.getHistoryRedoSize() <= 0;
        return undosize;

    }
    redoNfs() {
        if (this.#nfsItemTable != null) {
            this.#nfsItemTable.redo();

            if (this.checkNfsUndoRedo() > 0) {
                this.#nfsItemsDirty = true;
                this.#nfsSaveBtn.innerHTML = "Save Changes*";
                this.#nfsSaveBtn.disabled = false;
            }
        }
    };
    undoNfs() {
        if (this.#nfsItemTable != null) {
            this.#nfsItemTable.undo();

            if (this.checkNfsUndoRedo() > 0) {
                this.#nfsItemsDirty = true;
                this.#nfsSaveBtn.innerHTML = "Save Changes*";
                this.#nfsSaveBtn.disabled = false;
            }
        }
    };

    addrowNfs(nfs = null) {
        if (this.validateMaxLimit('Display/Not For Sale')) {
            let itemKey = 'new' + this.#addItemIndex.toString();
            this.#addItemIndex++;
            let newRow = {item_key: itemKey, status: 'Entered' };
            if (nfs != null) {
                newRow.title = nfs.title;
                newRow.material = nfs.material;
                newRow.min_price = nfs.min_price
                newRow.sale_price = nfs.sale_price;
            }
            this.#nfsItemTable.addRow(newRow, false).then(function (row) {
                auctionItemRegistration.checkNfsUndoRedo();
                if (nfsPagination) {
                    row.pageTo().then(function () {
                        setCellChanged(row);
                    });
                } else {
                    setCellChanged(row);
                }
            });
        }
    };

    saveNfs() {
        let type = 'nfs';
        if(this.#artItemTable != null) {
            let _this = this;

            let invalids; // TODO validation
            this.#nfsSaveBtn.innerHTML = "Saving...";
            this.#nfsSaveBtn.disabled = true;

            let script = "scripts/updateGetItems.php";

            clear_message();
            clear_message('ir_message_div');
            let postdata = {
                region: this.#region,
                itemType: type,
                tabledata: JSON.stringify(this.#nfsItemTable.getData())
            };

            //console.log(postdata);
            $.ajax({
                url: script,
                method: 'POST',
                data: postdata,
                success: function (data, textStatus, jhXHR) {
                    _this.saveNfsComplete(data, textStatus, jhXHR);
                },
                error: function (jqXHR, textStatus, errorThrown) {
                    show_message("ERROR in " + script + ": " + textStatus, 'error', 'ir_message_div');
                    _this.dataChangedNfs();
                    return false;
                }
            });
        }
    }
    saveNfsComplete(data, textStatus, jhXHR) {
        if('error' in data) {
            if (data['error']) {
                show_message(data['error'], 'error', 'ir_message_div');
                this.#nfsSaveBtn.innerHTML = "Save Changes*";
                this.#nfsSaveBtn.disabled = false;
                if (data.hasOwnProperty('marks')) {
                    this.markRows(this.#nfsItemTable, data.marks);
                }
                return false;
            }
            if (data['message']) {
                show_message(data['message'], 'error', 'ir_message_div');
            }
            this.#nfsSaveBtn.innerHTML = "Save Changes*";
            this.#nfsSaveBtn.disabled = false;
            return false;
        }
        if(data['message'] !== undefined) {
            show_message(data['message'], 'success', 'ir_message_div');
        }
        if(data['warn'] !== undefined) {
            show_message(data['warn'], 'warn', 'ir_message_div');
        }

        //console.log(data);
        this.drawNfsItemTable(data['items']);
        this.validateLoadLimit(true,  'nfs', data['items']['nfs']);
    }

    drawArtItemTable(data, art = null) {
        let _this = this;
        artPagination = data.art.length > 25;
        let tableSpecs = {
            maxHeight: "400px",
            history: true,
            data: data.art,
            layout: 'fitColumns', // Note: fitDataTable caused it to not honor the window width and create scoll bar, unsure why
            pagination: artPagination,
            index: 'item_key',
            paginationAddRow: "table",
            paginationSize: 10,
            paginationSizeSelector: [5, 10, 25, 50, true], //enable page size select element with these options
            columns: [
                {title: 'id', field: 'id', visible: false},
                {title: '#', field: 'item_key', width: 60, hozAlign: "right"},
                {
                    title: 'Title', field: 'title', minWidth: 600, editor: 'input', editable: artItemEditCheck, editorParams: {
                        elementAttributes: {
                            maxlength:
                                "64"
                        }
                    }
                },
                {
                    title: "Material",
                    field: "material",
                    minWidth: 300,
                    editor: 'input',
                    editable: artItemEditCheck,
                    editorParams: {elementAttributes: {maxlength: "32"}}
                },
                {
                    title: "Minimim Bid", field: "min_price", headerWordWrap: true, width: 100, hozAlign: "right",
                    editor: 'number', editable: artItemEditCheck, editorParams: {min: 1}, formatter: "money",
                    formatterParams: {decimal: '.', thousand: ',', symbol: '$', negativeSign: true},
                },
                {
                    title: "Quick Sale", field: "sale_price", headerWordWrap: true, width: 100, hozAlign: "right", visible: this.#allowQuickSale,
                    editor: 'number', editable: artItemEditCheck, editorParams: {min: 1}, formatter: "money",
                    formatterParams: {decimal: '.', thousand: ',', symbol: '$', negativeSign: true},
                },
                {title: "Status", field: "status", width: 200,},
                {
                    title: "Delete", field: "uses", formatter: deleteicon, hozAlign: "center", headerSort: false, width: 100,
                    cellClick: function (e, cell) {
                        deleterow(e, cell.getRow());
                    }
                },
                {title: "To Del", field: "to_delete", visible: this.#debugVisible},
            ]
        };
        this.validateLoadLimit(true, 'art', data.art);
        if (this.#numItems < this.#maxItems) {
            tableSpecs['tabEndNewRow'] = function (row) {
                return auctionItemRegistration.tabNewRow();
            };
        }
        this.#artItemTable = new Tabulator('#artItemTable', tableSpecs);
        this.#artItemsDirty = false;
        this.#artItemTable.on("dataChanged", function (data) {
            _this.dataChangedArt(data);
        });
        this.#artItemTable.on("cellEdited", setCellChanged);

        // now if imported items are passed, add them to the section
        if (art != null && art.length > 0) {
            this.#artSaveBtn.innerHTML = 'Save Changes*';
            this.#artSaveBtn.disabled = false;
            this.#newArt = art;
            this.#artItemTable.on("tableBuilt", addArt);
        } else {
            this.#artSaveBtn.innerHTML = 'Save Changes';
            this.#artSaveBtn.disabled = true;
        }

        document.getElementById('print_bidsheet').hidden = data.art.length == 0;
    }

    addArt() {
        this.#artItemTable.off("tableBuilt");
        if (this.#newArt == null)
            return;

        for (let i = 0; i < this.#newArt.length; i++) {
            let row = this.#newArt[i];
            this.addrowArt(row);
        }

        this.#newArt = null;
    }

    drawPrintItemTable(data, print = null) {
        let _this = this;
        printPagination = data.print.length > 25;
        let tableSpecs = {
            maxHeight: "400px",
            history: true,
            data: data.print,
            layout: 'fitColumns', // Note: fitDataTable caused it to not honor the window width and create scoll bar, unsure why
            pagination: printPagination,
            paginationAddRow:"table",
            paginationSize: 10,
            paginationSizeSelector: [5, 10, 25, 50, true], //enable page size select element with these options
            columns: [
                {title: 'id', field: 'id', visible: false},
                {title: '#', field: 'item_key', width: 60, hozAlign: "right"},
                {title: 'Title', field: 'title', minWidth: 600, editor: 'input', editable:artItemEditCheck, editorParams: { elementAttributes: { maxlength: "64"} } },
                {title: "Material", field: "material", minWidth: 300, editor: 'input', editable:artItemEditCheck, editorParams: { elementAttributes: { maxlength: "32"} } },
                {title: "Quantity", field: "original_qty", headerWordWrap: true, width: 100, hozAlign: "right", editor: 'number', editable:artItemEditCheck, editorParams: {min: 1} },
                {title: "Sale Price", field: "sale_price", headerWordWrap: true, width: 100, hozAlign: "right",
                    editor: 'number', editable:artItemEditCheck, editorParams: {min: 1}, formatter: "money",
                    formatterParams: {decimal: '.', thousand: ',', symbol: '$', negativeSign: true}, },
                {title: "Status", field: "status", width: 200, },
                {title: "Delete", field: "uses", formatter: deleteicon, hozAlign: "center", headerSort: false, width: 100,
                    cellClick: function (e, cell) { deleterow(e, cell.getRow());}},
                {title: "To Del", field: "to_delete", visible: this.#debugVisible},
            ]
        };
        this.validateLoadLimit(true, 'print', data.art);
        if (this.#numItems < this.#maxItems) {
            tableSpecs['tabEndNewRow'] = function (row) {
                return auctionItemRegistration.tabNewRow();
            };
        }
        this.#printItemTable = new Tabulator('#printItemTable', tableSpecs);
        this.#printItemsDirty = false;
        this.#printItemTable.on("dataChanged", function (data) {
            _this.dataChangedPrint(data);
        });
        this.#printItemTable.on("cellEdited", setCellChanged);
        // now if imported items are passed, add them to the section
        if (print != null && print.length > 0) {
            this.#printSaveBtn.innerHTML = 'Save Changes*';
            this.#printSaveBtn.disabled = false;
            this.#newPrint = print;
            this.#printItemTable.on("tableBuilt", addPrint);
        } else {
            this.#printSaveBtn.innerHTML = 'Save Changes';
            this.#printSaveBtn.disabled = true;
        }
        document.getElementById('print_printshop').hidden = data.print.length == 0;
    }

    addPrint() {
        this.#printItemTable.off("tableBuilt");
        if (this.#newPrint == null)
            return;

        for (let i = 0; i < this.#newPrint.length; i++) {
            let row = this.#newPrint[i];
            this.addrowPrint(row);
        }

        this.#newPrint = null;
    }

    drawNfsItemTable(data, nfs = null) {
        let _this = this;
        nfsPagination = data.nfs.length > 25;
        let tableSpecs = {
            maxHeight: "400px",
            history: true,
            data: data.nfs,
            layout: 'fitColumns', // Note: fitDataTable caused it to not honor the window width and create scoll bar, unsure why
            pagination: nfsPagination,
            paginationAddRow:"table",
            paginationSize: 10,
            paginationSizeSelector: [5, 10, 25, 50, true], //enable page size select element with these options
            columns: [
                {title: 'id', field: 'id', visible: false},
                {title: '#', field: 'item_key', width: 60, hozAlign: "right"},
                {title: 'Title', field: 'title', minWidth: 600, editor: 'input', editable:artItemEditCheck, editorParams: { elementAttributes: { maxlength: "64"} } },
                {title: "Material", field: "material", minWidth: 300, editor: 'input', editable:artItemEditCheck, editorParams: { elementAttributes: { maxlength: "32"} } },
                {title: "Insurance Price", field: "sale_price", headerWordWrap: true, width: 100, hozAlign: "right",
                    editor: 'number', editable:artItemEditCheck, editorParams: {min: 1}, formatter: "money",
                    formatterParams: {decimal: '.', thousand: ',', symbol: '$', negativeSign: true}, },
                {title: "Status", field: "status", width: 200, },
                {title: "Delete", field: "uses", formatter: deleteicon, hozAlign: "center", headerSort: false, width: 100,
                    cellClick: function (e, cell) { deleterow(e, cell.getRow());}},
                {title: "To Del", field: "to_delete", visible: this.#debugVisible},
            ]
        };
        this.validateLoadLimit(true, 'nfs', data.nfs);
        if (this.#numItems < this.#maxItems) {
            tableSpecs['tabEndNewRow'] = function (row) {
                return auctionItemRegistration.tabNewRow();
            };
        }
        this.#nfsItemTable = new Tabulator('#nfsItemTable', tableSpecs);
        this.#nfsItemsDirty = false;
        this.#nfsItemTable.on("dataChanged", function (data) {
            _this.dataChangedNfs(data);
        });
        this.#nfsItemTable.on("cellEdited", setCellChanged);

        // now if imported items are passed, add them to the section
        if (nfs != null && nfs.length > 0) {
            this.#nfsSaveBtn.innerHTML = 'Save Changes*';
            this.#nfsSaveBtn.disabled = false;
            this.#newNfs = nfs;
            this.#nfsItemTable.on("tableBuilt", addNfs);
        } else {
            this.#nfsSaveBtn.innerHTML = 'Save Changes';
            this.#nfsSaveBtn.disabled = true;
        }
    }

    addNfs() {
        this.#nfsItemTable.off("tableBuilt");
        if (this.#newNfs == null)
            return;

        for (let i = 0; i < this.#newNfs.length; i++) {
            let row = this.#newNfs[i];
            this.addrowNfs(row);
        }

        this.#newNfs = null;
    }

    import(region) {
        clear_message('ir_message_div');
        this.#region = region;
        let _this = this;
        let script = "scripts/getItems.php"
        clear_message();
        $.ajax({
            url: script,
            method: 'POST',
            data: {gettype: 'import', region: region},
            success: function (data, textSatus, jhXHR) {
                if (data['error']) {
                    show_message(data['error'], 'error');
                    return false;
                }
                _this.drawImport(data);

            },
            error: function (jqXHR, textStatus, errorThrown) {
                show_message("ERROR in " + script + ": " + textStatus, 'error');
                return false;
            }
        });
    };

    // draw the import items modal
    drawImport(data) {
        clear_message();
        clear_message('ii_message_div');
        if (this.#importTable) {
            this.#importTable.replaceData(data.items);
        } else {
            this.#importTable = new Tabulator('#importTable', {
                maxHeight: "800px",
                data: data.items,
                index: 'itemNum',
                layout: 'fitColumns', // Note: fitDataTable caused it to not honor the window width and create scoll bar, unsure why
                pagination: data.items.length > 25,
                paginationSize: 25,
                paginationSizeSelector: [10, 25, 50, true], //enable page size select element with these options
                columns: [
                    {title: 'Item Num', field: 'itemNum', width: 100, visible: false },
                    {title: 'Import', field: 'import', width: 80, headerSort: false,
                        formatter: "tickCross", cellClick: auctionItemRegistration.invertSelect, },
                    {title: 'Type', field: 'type', width: 100 },
                    {title: 'Title', field: 'title', minWidth: 600, editor: 'input', editorParams: { elementAttributes: { maxlength: "64"} } },
                    {title: "Material", field: "material", minWidth: 300, editor: 'input', editorParams: { elementAttributes: { maxlength: "32"} } },
                    {title: "Minimim Bid<br/>(for art only)", field: "min_price", headerWordWrap: true, width: 100, hozAlign: "right",
                        editor: 'number', editorParams: {min: 1}, formatter: "money",
                        //formatterParams: {decimal: '.', thousand: ',', symbol: '$', negativeSign: true},
                    },
                    {title: "Quick Sale/<br/>Sale Price", field: "sale_price", headerWordWrap: true, width: 100, hozAlign: "right",
                        editor: 'number', editorParams: {min: 1}, formatter: "money",
                        //formatterParams: {decimal: '.', thousand: ',', symbol: '$', negativeSign: true},
                    },
                    {title: "Quantity", field: "quantity", headerWordWrap: true, width: 100, hozAlign: "right",
                        editor: 'number', editorParams: {min: 1}, },
                ],
            });
            this.#importTable.on("cellEdited", setCellChanged);
        }
        this.#importModal.show();
    }

    // import the ones with ticks into a data array for draw
    importSelected() {
        let art = {};
        art.print = [];
        art.art = [];
        art.nfs = [];
        let rows = this.#importTable.getData();
        let itemKey = 1;
        for (let i = 0; i < rows.length; i++) {
            let row = rows[i];
            if (row.import === false || row.import == 0)
                continue;

            let newRow = {};
            newRow.id = -(i+1);
            newRow.item_key = itemKey++;
            newRow.type = row.type;
            newRow.title = row.title;
            newRow.material = row.material;
            newRow.original_qty = row.quantity;
            newRow.quantity = row.quantity;
            newRow.min_price = row.min_price;
            newRow.sale_price = row.sale_price;
            newRow.status = 'Entered';
            newRow.uses = 0;

            art[row.type].push(newRow);
        }
        this.#importModal.hide();
        this.open(this.#region, art.art, art.print, art.nfs);
    }

    // change tick to cross and back for import column.
    invertSelect(e,cell) {
        'use strict';

        let value = cell.getValue();
        if (value === undefined) {
            value = false;
        }
        if (value === 0 || Number(value) === 0)
            value = false;
        else if (value === "1" || Number(value) > 0)
            value = true;

        cell.setValue(!value, true);
    }

    // close the import modal screen
    closeImportModal() {
        if (this.#importTable) {
            this.#importTable.off("cellEdited");
            this.#importTable.destroy();
            this.#importTable = null;
        }
        this.#importModal.hide();
    }
}

auctionItemRegistration = null;
// init
function auctionItemRegistrationOnLoad(region) {
    auctionItemRegistration = new AuctionItemRegistration(config['debug']);
}

function deleteicon(cell, formattParams, onRendered) {
    let value = cell.getValue();
    if (value == 0 || value == null)
        return "&#x1F5D1;";
    return value;
}

function deleterow(e, row) {
    let count = row.getCell("uses").getValue();
    if (count == null) {
        row.delete();
        return;
    }
    if (count == 0) {
        row.getCell("to_delete").setValue(1);
        row.getCell("uses").setValue('<span style="color:red;"><b>Del</b></span>');
    }
}

function artItemEditCheck(cell) {
    let data = cell.getRow().getData();
    if (data.status == null)
        return true;
    if (data.status != 'Entered')
        return false;
    return true;
}

function addArt() {
    setTimeout(function() {
        auctionItemRegistration.addArt();
        }, 500);
}

function addPrint() {
    setTimeout(function() {
        auctionItemRegistration.addPrint();
    }, 500);
}

function addNfs() {
    setTimeout(function() {
        auctionItemRegistration.addNfs();
    }, 500);
}
