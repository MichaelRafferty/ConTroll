/* Auction Item Registration related functions
 */
var artPagination = false;
var nfsPagination = false;
var printPagination = false;
var currencyFmt = null;

class AuctionItemRegistration {

// items related to artists, or other exhibitors registering items
    #item_registration = null;
    #item_registration_title = null
    #item_registration_btn = null;
    #closeAnyway = false;
    #locale = 'en-us';
    #currencyFmt = null;
    #skipComputeDups = false;

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
    #newNFSRow = null;
    #newNFS = null;

    // import modal
    #importModal = null;
    #itemImportBtn = null;
    #importTableDiv = null;
    #importTable = null;
    #debug = 0;
    #debugVisible = false;
    #mainImportBtn = null;
    #inventoryImportBtn = null;

// init
    constructor(debug=0) {
        this.#debug = debug;
        this.#locale = config.locale;
        this.#currencyFmt = new Intl.NumberFormat(this.#locale, {
            style: 'currency',
            currency: config.currency,
        });
        currencyFmt = this.#currencyFmt;
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
            this.#inventoryImportBtn = document.getElementById('inventoryImportPriorBtn');
        }
        if (this.#debug & 1) {
            this.#debugVisible = true;
        }
    };

    // open the appropriate of PDF sheet in a new window
    printSheets(type, region = null, conid = null) {
        if (region == null)
            region = this.#region;
        let script = "scripts/bidsheets.php?type=" + type + "&region=" + region;
        if (conid != null)
            script += '&conid=' + conid;
        window.open(script, "_blank")
    }

    // check if there are unsaved changes, and if so, promptuser to save, or close if second press
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

    // open the item registration modal, fetching current data each time
    open(region, art= null, print = null, nfs = null) {
        clear_message('ir_message_div');
        this.#region = region;
        // if the parent has an import items from prior year button, unhide the one here
        this.#mainImportBtn = document.getElementById('importPriorBtn');
        this.#inventoryImportBtn.hidden = this.#mainImportBtn == null;
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

    // successful get of items, draw the contents of each of the three sections
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
        this.drawNFSItemTable(data['items'], nfs);

        this.validateLoadLimit(false);
        this.#item_registration_title.innerHTML = '<strong>' + this.#regionName + ' Item Registration</strong>';
        this.#item_registration.show();
    };

    // close the item registration modal
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

    // a field, row, or entire table was reloaded, this is called by an ON function on the table.
    // set up the butons and compute the colors for the duplicates
    dataChangedArt(data=null) {
        if (this.#skipComputeDups)
            return; // don't call it recursively due to dups checks changes to dups column
        //data - the updated table data
        if (!this.#artItemsDirty) {
            this.#artSaveBtn.innerHTML = "Save Changes*";
            this.#artSaveBtn.disabled = false;
            this.#artItemsDirty = true;
        }
        if (data == null){
            this.#artSaveBtn.innerHTML = "Save Changes*";
            this.#artSaveBtn.disabled = false;
        }
        this.checkArtUndoRedo();
        //console.log("dataChangedArt calling computeDups(art)");
        this.computeDups('art');
    };

    // check all the rows in the table for title and material matching to color code the duplicates.
    // This is an alert to the user, not an actual error.
    computeDups(type) {
        this.#skipComputeDups = true;
        //console.log("in computeDups(" + type + ")");
        let data = null;
        let table = null;
        switch (type) {
            case 'art':
                data = this.#artItemTable.getData();
                table = this.#artItemTable;
                break;
            case 'print':
                data = this.#printItemTable.getData();
                table = this.#printItemTable;
                break;
            case 'nfs':
                data = this.#nfsItemTable.getData();
                table = this.#nfsItemTable;
                break;
            default:
                this.#skipComputeDups = false;
                return;
        }

        // no need to check for dups if there are no rows.
        // However, for one row, (two down to 1 in particular), let it check to clear the colors if the dup was deleted
        if (data.length < 1) {
            this.#skipComputeDups = false;
            return; // no dups to check for
        }

        for (let i = 0; i < data.length; i++) {
            let row = data[i];
            let dup = 0;
            for (let j = 0; j < data.length; j++) {
                if (i == j)
                    continue;   // don't check itself
                let comprow = data[j];
                if (comprow.title == row.title && comprow.material == row.material) {
                    dup = 1;
                    break;
                }
            }
            let duprow = table.getRow(row.item_key);
            let cell = duprow.getCell('dupItem');
            cell.setValue(dup);
        }
        //console.log("Setting timeout redraw(true)");
        setTimeout(function() {
            //console.log("redrawing " + type);
            //console.log(table);
            let cell = null;
            if (typeof table.getActiveCell === 'function') {
                cell = table.getActiveCell();
            }
            table.redraw(true);
            if (cell)
                cell.focus();
        }, 200);

        this.#skipComputeDups = false;
    }

    // recompute the table undo/redo button states
    checkArtUndoRedo() {
        let undosize = this.#artItemTable.getHistoryUndoSize();
        this.#artUndoBtn.disabled = undosize <= 0;
        this.#artRedoBtn.disabled = this.#artItemTable.getHistoryRedoSize() <= 0;
        return undosize;
    }
    
    // process a redo button
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
    
    // process an undo button
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

        return {item_key: 'New' + this.#addItemIndex.toString(), 'status': 'Entered', 'title' : '', 'material' : '',
            'quantity' : '', sale_price: '', min_price: '', dupItem: 0};
    }

    // add a new art row (add new button
    addrowArt(art = null) {
        if (this.validateMaxLimit('Art Auction')) {
            this.#skipComputeDups = true;
            let itemKey = 'new' + this.#addItemIndex.toString();
            this.#addItemIndex++;
            let newRow = {item_key: itemKey, status: 'Entered', dupItem: 0 };
            if (art != null) {
                newRow.title = art.title;
                newRow.material = art.material;
                newRow.min_price = art.min_price
                newRow.sale_price = art.sale_price;
            } else {
                newRow.title = '';
                newRow.material = '';
                newRow.min_price = '';
                newRow.sale_price = '';
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
            this.#skipComputeDups = false;
        }
    };

    // save the art table back to the database
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
    
    // reload the data and update the display and buttons
    saveArtComplete(data, textStatus, jhXHR) {
        if (data['error']) {
            show_message(data['error'], 'error', 'ir_message_div');
            this.#artSaveBtn.innerHTML = "Save Changes*";
            this.#artSaveBtn.disabled = false;
            if (data.hasOwnProperty('marks')) {
                this.unMarkRows(this.#artItemTable, data.marks);
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

    // clear marks for unsaved rows
    unMarkRows(table, marks) {
        for(let index = 0; index < marks.length; index++) {
            let mark = marks[index];
            let row = table.getRow(mark.item_key);
            if (row.classList.contains('unsavedWarnBGColor')) {
                row.classList.remove('unsavedWarnBGColor');
            }
        }
    }

    // a field, row, or entire table was reloaded, this is called by an ON function on the table.
    // set up the butons and compute the colors for the duplicates
    dataChangedPrint(data=null) {
        if (this.#skipComputeDups)
            return; // don't call it recursively due to dups checks changes to dups column

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
        //console.log("dataChangedPrint calling computeDups(print)");
        this.computeDups('print');
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
            this.#skipComputeDups = true;
            let itemKey = 'new' + this.#addItemIndex.toString();
            this.#addItemIndex++;
            let newRow = {item_key: itemKey, status: 'Entered', dupItem: 0 };
            if (print != null) {
                newRow.title = print.title;
                newRow.material = print.material;
                newRow.min_price = print.min_price
                newRow.sale_price = print.sale_price;
                newRow.original_qty = print.original_qty;
            } else {
                newRow.title = '';
                newRow.material = '';
                newRow.min_price = '';
                newRow.sale_price = '';
                newRow.original_qty = '';
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
            this.#skipComputeDups = false;
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
                    _this.dataChangedPrint();
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
                    this.unMarkRows(this.#printItemTable, data.marks);
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

    // a field, row, or entire table was reloaded, this is called by an ON function on the table.
    // set up the butons and compute the colors for the duplicates
    dataChangedNFS(data = null) {
        if (this.#skipComputeDups)
            return; // don't call it recursively due to dups checks changes to dups column
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
        this.checkNFSUndoRedo();
        //console.log("dataChangedNFS calling computeDups(nfs)");
        this.computeDups('nfs');
    };
    
    checkNFSUndoRedo() {
        let undosize = this.#nfsItemTable.getHistoryUndoSize();
        this.#nfsUndoBtn.disabled = undosize <= 0;
        this.#nfsRedoBtn.disabled = this.#nfsItemTable.getHistoryRedoSize() <= 0;
        return undosize;

    }
    
    redoNFS() {
        if (this.#nfsItemTable != null) {
            this.#nfsItemTable.redo();

            if (this.checkNFSUndoRedo() > 0) {
                this.#nfsItemsDirty = true;
                this.#nfsSaveBtn.innerHTML = "Save Changes*";
                this.#nfsSaveBtn.disabled = false;
            }
        }
    };
    
    undoNFS() {
        if (this.#nfsItemTable != null) {
            this.#nfsItemTable.undo();

            if (this.checkNFSUndoRedo() > 0) {
                this.#nfsItemsDirty = true;
                this.#nfsSaveBtn.innerHTML = "Save Changes*";
                this.#nfsSaveBtn.disabled = false;
            }
        }
    };

    addrowNFS(nfs = null) {
        if (this.validateMaxLimit('Display/Not For Sale')) {
            this.#skipComputeDups = true;
            let itemKey = 'new' + this.#addItemIndex.toString();
            this.#addItemIndex++;
            let newRow = {item_key: itemKey, status: 'Entered', dupItem: 0 };
            if (nfs != null) {
                newRow.title = nfs.title;
                newRow.material = nfs.material;
                newRow.min_price = nfs.min_price
                newRow.sale_price = nfs.sale_price;
            } else {
                newRow.title = '';
                newRow.material = '';
                newRow.min_price = '';
                newRow.sale_price = '';
            }
            this.#nfsItemTable.addRow(newRow, false).then(function (row) {
                auctionItemRegistration.checkNFSUndoRedo();
                if (nfsPagination) {
                    row.pageTo().then(function () {
                        setCellChanged(row);
                    });
                } else {
                    setCellChanged(row);
                }
            });
            this.#skipComputeDups = false;
        }
    };

    saveNFS() {
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
                    _this.saveNFSComplete(data, textStatus, jhXHR);
                },
                error: function (jqXHR, textStatus, errorThrown) {
                    show_message("ERROR in " + script + ": " + textStatus, 'error', 'ir_message_div');
                    _this.dataChangedNFS();
                    return false;
                }
            });
        }
    }

    saveNFSComplete(data, textStatus, jhXHR) {
        if('error' in data) {
            if (data['error']) {
                show_message(data['error'], 'error', 'ir_message_div');
                this.#nfsSaveBtn.innerHTML = "Save Changes*";
                this.#nfsSaveBtn.disabled = false;
                if (data.hasOwnProperty('marks')) {
                    this.unMarkRows(this.#nfsItemTable, data.marks);
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
        this.drawNFSItemTable(data['items']);
        this.validateLoadLimit(true,  'nfs', data['items']['nfs']);
    }

    // create the tabulator table for the art items
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
                { title: 'id', field: 'id', visible: false},
                { title: '#', field: 'item_key', width: 60, hozAlign: "right"},
                {
                    title: 'Title', field: 'title', minWidth: 600, editor: 'input', editable: artItemEditCheck,
                    editorParams: { elementAttributes: { maxlength: "64" }}, formatter: dupCheck,
                },
                {
                    title: "Material", field: "material", minWidth: 300, editor: 'input', editable: artItemEditCheck,
                    editorParams: {elementAttributes: {maxlength: "32"}}, formatter: dupCheck,
                },
                {
                    title: "Minimim Bid", field: "min_price", headerWordWrap: true, width: 100, hozAlign: "right",
                    editor: 'number', editable: artItemEditCheck, editorParams: {min: 1}, formatter: localeMoney,
                },
                {
                    title: "Quick Sale", field: "sale_price", headerWordWrap: true, width: 100, hozAlign: "right", visible: this.#allowQuickSale,
                    editor: 'number', editable: artItemEditCheck, editorParams: {min: 1}, formatter: localeMoney,
                },
                { title: "Status", field: "status", width: 200,},
                {
                    title: "Delete", field: "uses", formatter: deleteicon, hozAlign: "center", headerSort: false, width: 100,
                    cellClick: function (e, cell) {
                        deleterow(e, cell.getRow());
                    }
                },
                { title: "To Del", field: "to_delete", visible: this.#debugVisible},
                { title: "dupItem", field: "dupItem", visible: this.#debugVisible},
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
        this.#artItemTable.on("cellEdited", artSetCellChanged);

        // now if imported items are passed, add them to the section
        if (art != null && art.length > 0) {
            this.#artSaveBtn.innerHTML = 'Save Changes*';
            this.#artSaveBtn.disabled = false;
            this.#newArt = art;
            this.#artItemTable.on("tableBuilt", addArt);
        } else {
            this.#artSaveBtn.innerHTML = 'Save Changes';
            this.#artSaveBtn.disabled = true;
            //console.log("drawItemTable calling checkDupsArt");
            this.#artItemTable.on("tableBuilt", checkDupsArt);
        }
        document.getElementById('print_bidsheet').hidden = data.art.length == 0;
    }

    // add art from the import list to the art items table
    addArt() {
        this.#artItemTable.off("tableBuilt");
        if (this.#newArt == null) {
            //console.log("addArt calling checkDups('art'), new art is null");
            this.computeDups('art');
            return;
        }

        for (let i = 0; i < this.#newArt.length; i++) {
            let row = this.#newArt[i];
            this.addrowArt(row);
        }

        this.#newArt = null;
        //console.log("addArt calling checkDups('art'), new art is not null");
        this.computeDups('art');
    }

    // create the print items table in tabulator
    drawPrintItemTable(data, print = null) {
        let _this = this;
        printPagination = data.print.length > 25;
        let tableSpecs = {
            maxHeight: "400px",
            history: true,
            data: data.print,
            index: 'item_key',
            layout: 'fitColumns', // Note: fitDataTable caused it to not honor the window width and create scoll bar, unsure why
            pagination: printPagination,
            paginationAddRow:"table",
            paginationSize: 10,
            paginationSizeSelector: [5, 10, 25, 50, true], //enable page size select element with these options
            columns: [
                { title: 'id', field: 'id', visible: false},
                { title: '#', field: 'item_key', width: 60, hozAlign: "right"},
                {
                    title: 'Title', field: 'title', minWidth: 600, editor: 'input', editable:artItemEditCheck, 
                    editorParams: { elementAttributes: { maxlength: "64"} }, formatter: dupCheck,
                },
                { title: "Material", field: "material", minWidth: 300, editor: 'input', editable:artItemEditCheck,
                    editorParams: { elementAttributes: { maxlength: "32"} }, formatter: dupCheck,
                },
                {
                    title: "Quantity", field: "original_qty", headerWordWrap: true, width: 100, hozAlign: "right", 
                    editor: 'number', editable:artItemEditCheck, editorParams: {min: 1}
                },
                { title: "Sale Price", field: "sale_price", headerWordWrap: true, width: 100, hozAlign: "right",
                    editor: 'number', editable:artItemEditCheck, editorParams: {min: 1}, formatter: localeMoney,
                },
                { title: "Status", field: "status", width: 200, },
                { title: "Delete", field: "uses", formatter: deleteicon, hozAlign: "center", headerSort: false, width: 100,
                    cellClick: function (e, cell) { deleterow(e, cell.getRow());}},
                { title: "To Del", field: "to_delete", visible: this.#debugVisible},
                { title: "dupItem", field: "dupItem", visible: this.#debugVisible},
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
        this.#printItemTable.on("cellEdited", printSetCellChanged);
        // now if imported items are passed, add them to the section
        if (print != null && print.length > 0) {
            this.#printSaveBtn.innerHTML = 'Save Changes*';
            this.#printSaveBtn.disabled = false;
            this.#newPrint = print;
            this.#printItemTable.on("tableBuilt", addPrint);
        } else {
            this.#printSaveBtn.innerHTML = 'Save Changes';
            this.#printSaveBtn.disabled = true;
            //console.log("drawItemTable calling checkDupsPrint");
            this.#printItemTable.on("tableBuilt", checkDupsPrint);
        }
        document.getElementById('print_printshop').hidden = data.print.length == 0;
    }

    // add the items from import to the print table
    addPrint() {
        this.#printItemTable.off("tableBuilt");
        if (this.#newPrint == null) {
            //console.log("addPrint calling checkDups('print'), new print is null");
            this.computeDups('print');
            return;
        }

        for (let i = 0; i < this.#newPrint.length; i++) {
            let row = this.#newPrint[i];
            this.addrowPrint(row);
        }

        this.#newPrint = null;
        //console.log("addPrint calling checkDups('print'), new print is not null");
        this.computeDups('print');
    }

    // create the not for sale items table in tabulator
    drawNFSItemTable(data, nfs = null) {
        let _this = this;
        nfsPagination = data.nfs.length > 25;
        let tableSpecs = {
            maxHeight: "400px",
            history: true,
            data: data.nfs,
            index: 'item_key',
            layout: 'fitColumns', // Note: fitDataTable caused it to not honor the window width and create scoll bar, unsure why
            pagination: nfsPagination,
            paginationAddRow:"table",
            paginationSize: 10,
            paginationSizeSelector: [5, 10, 25, 50, true], //enable page size select element with these options
            columns: [
                { title: 'id', field: 'id', visible: false},
                { title: '#', field: 'item_key', width: 60, hozAlign: "right"},
                {
                    title: 'Title', field: 'title', minWidth: 600, editor: 'input',
                    editable:artItemEditCheck, editorParams: { elementAttributes: { maxlength: "64"} }, formatter: dupCheck,
                },
                {
                    title: "Material", field: "material", minWidth: 300, editor: 'input', 
                    editable:artItemEditCheck, editorParams: { elementAttributes: { maxlength: "32"} }, formatter: dupCheck,
                },
                { title: "Insurance Price", field: "sale_price", headerWordWrap: true, width: 100, hozAlign: "right",
                    editor: 'number', editable:artItemEditCheck, editorParams: {min: 1}, formatter: localeMoney,
                },
                { title: "Status", field: "status", width: 200, },
                { title: "Delete", field: "uses", formatter: deleteicon, hozAlign: "center", headerSort: false, width: 100,
                    cellClick: function (e, cell) { deleterow(e, cell.getRow());}},
                { title: "To Del", field: "to_delete", visible: this.#debugVisible},
                { title: "dupItem", field: "dupItem", visible: this.#debugVisible},
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
            _this.dataChangedNFS(data);
        });
        this.#nfsItemTable.on("cellEdited", nfsSetCellChanged);

        // now if imported items are passed, add them to the section
        if (nfs != null && nfs.length > 0) {
            this.#nfsSaveBtn.innerHTML = 'Save Changes*';
            this.#nfsSaveBtn.disabled = false;
            this.#newNFS = nfs;
            this.#nfsItemTable.on("tableBuilt", addNFS);
        } else {
            this.#nfsSaveBtn.innerHTML = 'Save Changes';
            this.#nfsSaveBtn.disabled = true;
            //console.log("drawItemTable calling checkDupsNFS");
            this.#nfsItemTable.on("tableBuilt", checkDupsNFS);
        }
    }

    // add the import items to the nfs table
    addNFS() {
        this.#nfsItemTable.off("tableBuilt");
        if (this.#newNFS == null) {
            //console.log("addNFS calling checkDups('nfs'), new nfs is null");
            this.computeDups('nfs');
            return;
        }

        for (let i = 0; i < this.#newNFS.length; i++) {
            let row = this.#newNFS[i];
            this.addrowNFS(row);
        }

        this.#newNFS = null;
        //console.log("addNFS calling checkDups('nfs'), new nfs is not null");
        this.computeDups('nfs');
    }

    // handle pressing the import button
    // get the data available for import from the database
    import(region) {
        clear_message('ir_message_div');
        let standalone = true;
        if (region == null) {
            region = this.#region;
            let standalone = false;
        } else {
            this.#region = region;
        }
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
                _this.drawImport(data, standalone);

            },
            error: function (jqXHR, textStatus, errorThrown) {
                show_message("ERROR in " + script + ": " + textStatus, 'error');
                return false;
            }
        });
    };

    // draw the import items modal
    drawImport(data, standalone) {
        if (standalone) {
            this.#item_registration.hide();
        }
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
                    { title: 'Item Num', field: 'itemNum', width: 100, visible: false },
                    {
                        title: 'Import', field: 'import', width: 80, headerSort: false,
                        formatter: "tickCross", cellClick: auctionItemRegistration.invertSelect,                    
                    },
                    { title: 'Type', field: 'type', width: 100, formatter: existsColor, },
                    {
                        title: 'Title', field: 'title', minWidth: 600, formatter: existsColor,
                        editor: 'input', editorParams: { elementAttributes: { maxlength: "64"} }
                    },
                    {
                        title: "Material", field: "material", minWidth: 300, formatter: existsColor,
                        editor: 'input', editorParams: { elementAttributes: { maxlength: "32"} }
                    },
                    {
                        title: "Minimim Bid<br/>(for art only)", field: "min_price", headerWordWrap: true, width: 100, hozAlign: "right",
                        editor: 'number', editorParams: {min: 1}, formatter: localeMoney,
                    },
                    {
                        title: "Quick Sale/<br/>Sale Price", field: "sale_price", headerWordWrap: true, width: 100, hozAlign: "right",
                        editor: 'number', editorParams: {min: 1}, formatter: localeMoney,
                    },
                    {
                        title: "Quantity", field: "quantity", headerWordWrap: true, width: 100, hozAlign: "right",
                        editor: 'number', editorParams: {min: 1}, },
                    { title: 'E', field: 'newExists', width: 50, visible:false, },
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

// tabulator format function for the delete item button
function deleteicon(cell, formatParams, onRendered) {
    let value = cell.getValue();
    let status = cell.getRow().getData('status');
    if (status == 'Entered' && (value == 0 || value == null))
        return "&#x1F5D1;";
    return value;
}

// process the row deletion (clicking on the trashcan).
function deleterow(e, row) {
    let count = row.getCell("uses").getValue();
    let status = row.getCell("status")
    if (count == null && status == 'Entered') {
        row.delete();
        return;
    }
    if (count == 0 && status == 'Entered') {
        row.getCell("to_delete").setValue(1);
        row.getCell("uses").setValue('<span style="color:red;"><b>Del</b></span>');
    }
}

// for import, if the item is already in the inventory, color it.
function existsColor(cell, formatParams, onRendered) {
    let row = cell.getRow().getData();
    let value = cell.getValue();
    let exists = row['newExists'];
    let element = cell.getElement();
    element.style.backgroundColor = exists ? '#FFE0E0' : '#FFFFFF';
    return value;
}

// use INTL currency format with symbols for money as Tabulator doesn't do the symbols by default
function localeMoney(cell, formatParams, onRendered) {
    let value = cell.getValue();
    if (value == '')
        return value;

    return currencyFmt.format(Number(value).toFixed(2));
}

// formatter to color duplicate cells
function dupCheck(cell, formatParams, onRendered, dataTable) {
    let row = cell.getRow()
    let dup = row.getData().dupItem
    if (row.getPosition() % 2) {
        cell.getElement().style.backgroundColor = (dup == 1) ? '#FFF0F0' : '#FFFFFF';
    } else {
        cell.getElement().style.backgroundColor = (dup == 1) ? '#FFE0E0' : '#F0F0F0';
    }
    return cell.getValue();
}

// allow editing only of cells with status Entered
function artItemEditCheck(cell) {
    let data = cell.getRow().getData();
    if (data.status == null)
        return true;
    if (data.status != 'Entered')
        return false;
    return true;
}

// process the import of art items only after the table is rendered.
function addArt() {
    setTimeout(function() {
        auctionItemRegistration.addArt();
        }, 500);
}

// process the import of print items only after the table is rendered.
function addPrint() {
    setTimeout(function() {
        auctionItemRegistration.addPrint();
    }, 500);
}

// process the import of NFS items only after the table is rendered.
function addNFS() {
    setTimeout(function() {
        auctionItemRegistration.addNFS();
    }, 500);
}

function checkDupsArt() {
    auctionItemRegistration.computeDups('art');
}

function checkDupsPrint() {
    auctionItemRegistration.computeDups('print');
}

function checkDupsNFS() {
    auctionItemRegistration.computeDups('nfs');
}

// processing the on art item for data cell changed, add doing dups only on title or material changed
function artSetCellChanged(cell) {
    let field = cell.getField();
    if (field == 'title' || field == 'material') {
        //console.log("title/material changed in art");
        auctionItemRegistration.computeDups('art');
    }
    return setCellChanged(cell);
}

// processing the on print item for data cell changed, add doing dups only on title or material changed
function printSetCellChanged(cell) {
    let field = cell.getField();
    if (field == 'title' || field == 'material') {
        //console.log("title/material changed in print");
        auctionItemRegistration.computeDups('print');
    }
    return setCellChanged(cell);
}

// processing the on nfs item for data cell changed, add doing dups only on title or material changed
function nfsSetCellChanged(cell) {
    let field = cell.getField();
    if (field == 'title' || field == 'material') {
        //console.log("title/material changed in nfs");
        auctionItemRegistration.computeDups('nfs');
    }
    return setCellChanged(cell);
}
