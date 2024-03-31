/* Auction Item Registration related functions
 */
class AuctionItemRegistration {
    
// items related to artists, or other exhibitors registering items
    #item_registration = null;
    #item_registration_btn = null;

    #region = 0;

    #artItemTable = null;
    #artItemsDirty = false;
    #artSaveBtn = null;
    #artUndoBtn = null;
    #artRedoBtn = null;

    #printItemTable = null;
    #printItemsDirty = false;
    #printSaveBtn = null;
    #printUndoBtn = null;
    #printRedoBtn = null;

    #nfsItemTable = null;
    #nfsItemsDirty = false;
    #nfsSaveBtn = null;
    #nfsUndoBtn = null;
    #nfsRedoBtn = null;

    #debug = 0;
    #debugVisible = false;

// init
    constructor(debug=0) {
        this.#debug = debug;
        var id = document.getElementById('item_registration');
        if (id != null) {
            this.#item_registration = new bootstrap.Modal(id, {focus: true, backdrop: 'static'});
            this.#item_registration_btn = document.getElementById('item_registration_btn');
        }
        if (this.#debug & 1) {
            this.#debugVisible = true;
        }
    };


    printSheets(type) {
        var _this = this;
        var script = 'scripts/bidsheets.php';

        $.ajax({
            url: script,
            method: 'GET',
            data: {type: type, region: this.#region},
            xhrFields: {
                responseType: 'blob' // to avoid binary data being mangled on charset conversion
            },
            success: function (data, textSatus, xhr) {
                if(xhr.getResponseHeader('Content-Type') == 'application/pdf') {
                    var disposition = xhr.getResponseHeader('Content-Disposition');
                    var filename = "";
                    if (disposition && disposition.indexOf('attachment') !== -1) {
                        var filenameRegex = /filename[^;=\n]*=((['"]).*?\2|[^;\n]*)/;
                        var matches = filenameRegex.exec(disposition);
                        if (matches != null && matches[1]) filename = matches[1].replace(/['"]/g, '');
                    }
                    var URL = window.URL || window.webkitURL;
                    var downloadUrl = URL.createObjectURL(data);

                    if (filename) {
                        // use HTML5 a[download] attribute to specify filename
                        var a = document.createElement("a");
                        // safari doesn't support this yet
                        if (typeof a.download === 'undefined') {
                            window.location.href = downloadUrl;
                        } else {
                            a.href = downloadUrl;
                            a.download = filename;
                            document.body.appendChild(a);
                            a.click();
                        }
                    } else {
                        window.location.href = downloadUrl;
                    }

                    setTimeout(function () { URL.revokeObjectURL(downloadUrl); }, 100);
                } else {
                    if (data['error']) {
                        show_message(data['error'], 'error', 'ir_message_div');
                        return false;
                    }
                    if (data['num_rows'] == 0) {
                        show_message(data['status'], 'warning', 'ir_message_div');
                    }
                    console.log(data);
                }
            },
            error: function (jqXHR, textStatus, errorThrown) {
                show_message("ERROR in " + script + ": " + textStatus, 'error', 'ir_message_div');
                return false;
            }
        });
    }

    open(region) {
        clear_message('ir_message_div');
        this.#region = region;
        var _this = this;
        var script = "scripts/getItems.php"
        $.ajax({
            url: script,
            method: 'POST',
            data: {gettype: 'all', region: region},
            success: function (data, textSatus, jhXHR) {
                if (data['error']) {
                    show_message(data['error'], 'error', 'ir_message_div');
                    return false;
                }
                console.log(data);
                _this.draw(data);

            },
            error: function (jqXHR, textStatus, errorThrown) {
                show_message("ERROR in " + script + ": " + textStatus, 'error', 'ir_message_div');
                return false;
            }
        });
    };

    draw(data) {
        this.#artSaveBtn = document.getElementById('art-save');
        this.#artUndoBtn = document.getElementById('art-undo');
        this.#artRedoBtn = document.getElementById('art-redo');
        this.drawArtItemTable(data['items']);

        this.#printSaveBtn = document.getElementById('print-save');
        this.#printUndoBtn = document.getElementById('print-undo');
        this.#printRedoBtn = document.getElementById('print-redo');
        this.drawPrintItemTable(data['items']);

        this.#nfsSaveBtn = document.getElementById('nfs-save');
        this.#nfsUndoBtn = document.getElementById('nfs-undo');
        this.#nfsRedoBtn = document.getElementById('nfs-redo');
        this.drawNfsItemTable(data['items']);

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
        this.#item_registration.hide(); 
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
        var undosize = this.#artItemTable.getHistoryUndoSize();
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
    addrowArt() {
        var _this = this;
        this.#artItemTable.addRow({item_key: 'new'}, false).then(function (row) {
            row.getTable().scrollToRow(row);
            _this.checkArtUndoRedo();
        });
    };
    saveArt() {
        var type = 'art';
        if(this.#artItemTable != null) {
            var _this = this;

            var invalids; // TODO validation
            this.#artSaveBtn.innerHTML = "Saving...";
            this.#artSaveBtn.disabled = true;

            var script = "scripts/updateGetItems.php";

            clear_message();
            var postdata = {
                region: this.#region,
                itemType: type,
                tabledata: JSON.stringify(this.#artItemTable.getData())
            };

            console.log(postdata);
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
        if('error' in data) {
            if (data['error']) {
                show_message(data['error'], 'error', 'ir_message_div');
                this.#artSaveBtn.innerHTML = "Save Changes*";
                this.#artSaveBtn.disabled = false;
                return false;
            }
            if (data['message']) {
                show_message(data['message'], 'error', 'ir_message_div');
            }
            this.#artSaveBtn.innerHTML = "Save Changes*";
            this.#artSaveBtn.disabled = false;
            return false;
        }
        if(data['message'] !== undefined) {
            show_message(data['message'], 'success', 'ir_message_div');
        }   
        if(data['warn'] !== undefined) {
            show_message(data['warn'], 'warn', 'ir_message_div');
        }   

        console.log(data);
        this.drawArtItemTable(data['items']);
    }
//TODO Delete Art Items
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
        var undosize = this.#printItemTable.getHistoryUndoSize();
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
    addrowPrint() {
        var _this = this;
        this.#printItemTable.addRow({item_key: 'new'}, false).then(function (row) {
            row.getTable().scrollToRow(row);
            _this.checkPrintUndoRedo();
        });
    };
    savePrint() {
        var type = 'print';
        if(this.#artItemTable != null) {
            var _this = this;

            var invalids; // TODO validation
            this.#printSaveBtn.innerHTML = "Saving...";
            this.#printSaveBtn.disabled = true;

            var script = "scripts/updateGetItems.php";

            clear_message();
            var postdata = {
                region: this.#region,
                itemType: type,
                tabledata: JSON.stringify(this.#printItemTable.getData())
            };

            console.log(postdata);
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

        console.log(data);
        this.drawPrintItemTable(data['items']);
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
        var undosize = this.#nfsItemTable.getHistoryUndoSize();
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
    addrowNfs() {
        var _this = this;
        this.#nfsItemTable.addRow({item_key: 'new'}, false).then(function (row) {
            row.getTable().scrollToRow(row);
            _this.checkNfsUndoRedo();
        });
    };
    saveNfs() {
        var type = 'nfs';
        if(this.#artItemTable != null) {
            var _this = this;

            var invalids; // TODO validation
            this.#nfsSaveBtn.innerHTML = "Saving...";
            this.#nfsSaveBtn.disabled = true;

            var script = "scripts/updateGetItems.php";

            clear_message();
            var postdata = {
                region: this.#region,
                itemType: type,
                tabledata: JSON.stringify(this.#nfsItemTable.getData())
            };

            console.log(postdata);
            $.ajax({
                url: script,
                method: 'POST',
                data: postdata,
                success: function (data, textStatus, jhXHR) {
                    _this.saveNfsComplete(data, textStatus, jhXHR);
                },
                error: function (jqXHR, textStatus, errorThrown) {
                    show_message("ERROR in " + script + ": " + textStatus, 'error', 'ir_message_div');
                    _this.dataChangedArt();
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

        console.log(data);
        this.drawNfsItemTable(data['items']);
    }

    drawArtItemTable(data) {
        var _this = this;
        this.#artItemTable = new Tabulator('#artItemTable', {
            maxHeight: "400px",
            history: true,
            data: data['art'],
            layout: 'fitDataTable',
            pagination: true,
            paginationSize: 10,
            paginationSizeSelector: [5, 10, 25, 50, true], //enable page size select element with these options
            columns: [
                {title: 'id', field: 'id', visible: false},
                {title: '#', field: 'item_key', width: 50, hozAlign: "right"},
                {title: 'Title', field: 'title', width: 600, editor: 'input', editorParams: { elementAttributes: { maxlength: "64"} } },
                {title: "Material", field: "material", width: 300, editor: 'input', editorParams: { elementAttributes: { maxlength: "32"} } },
                {title: "Minimim Bid", field: "min_price", headerWordWrap: true, width: 100, hozAlign: "right",
                    editor: 'number', editorParams: {min: 1}, formatter: "money",
                    formatterParams: {decimal: '.', thousand: ',', symbol: '$', negativeSign: true}, },
                {title: "Quick Sale", field: "sale_price", headerWordWrap: true, width: 100, hozAlign: "right",
                    editor: 'number', editorParams: {min: 1}, formatter: "money",
                    formatterParams: {decimal: '.', thousand: ',', symbol: '$', negativeSign: true}, },
                {title: "Delete", field: "uses", formatter: deleteicon, hozAlign: "center", headerSort: false, cellClick: function (e, cell) { deleterow(e, cell.getRow());}},
                {title: "To Del", field: "to_delete", visible: this.#debugVisible},
                {title: "Status", field: "status", visible: this.#debugVisible},
            ]
        });
        this.#artItemsDirty = false;
        this.#artItemTable.on("dataChanged", function (data) {
            _this.dataChangedArt(data);
        });
        this.#artItemTable.on("cellEdited", cellChanged);

        this.#artSaveBtn.innerHTML='Save Changes';
        this.#artSaveBtn.disbled=true;
    }

    drawPrintItemTable(data) {
        var _this = this;
        this.#printItemTable = new Tabulator('#printItemTable', {
            maxHeight: "400px",
            history: true,
            data: data['print'],
            layout: 'fitDataTable',
            pagination: true,
            paginationSize: 10,
            paginationSizeSelector: [5, 10, 25, 50, true], //enable page size select element with these options
            columns: [
                {title: 'id', field: 'id', visible: false},
                {title: '#', field: 'item_key', width: 50, hozAlign: "right"},
                {title: 'Title', field: 'title', width: 600, editor: 'input', editorParams: { elementAttributes: { maxlength: "64"} } },
                {title: "Material", field: "material", width: 300, editor: 'input', editorParams: { elementAttributes: { maxlength: "32"} } },
                {title: "Quantity", field: "original_qty", headerWordWrap: true, width: 100, hozAlign: "right", editor: 'number', editorParams: {min: 1} },
                {title: "Quick Sale", field: "sale_price", headerWordWrap: true, width: 100, hozAlign: "right",
                    editor: 'number', editorParams: {min: 1}, formatter: "money",
                    formatterParams: {decimal: '.', thousand: ',', symbol: '$', negativeSign: true}, },
                {title: "Delete", field: "uses", formatter: deleteicon, hozAlign: "center", headerSort: false, cellClick: function (e, cell) { deleterow(e, cell.getRow());}},
                {title: "To Del", field: "to_delete", visible: this.#debugVisible},
                {title: "Status", field: "status", visible: this.#debugVisible},
            ]
        });
        this.#printItemsDirty = false;
        this.#printItemTable.on("dataChanged", function (data) {
            _this.dataChangedPrint(data);
        });
        this.#printItemTable.on("cellEdited", cellChanged);

        this.#printSaveBtn.innerHTML='Save Changes';
        this.#printSaveBtn.disbled=true;
    }

    drawNfsItemTable(data) {
        var _this = this;
        this.#nfsItemTable = new Tabulator('#nfsItemTable', {
            maxHeight: "400px",
            history: true,
            data: data['nfs'],
            layout: 'fitDataTable',
            pagination: true,
            paginationSize: 10,
            paginationSizeSelector: [5, 10, 25, 50, true], //enable page size select element with these options
            columns: [
                {title: 'id', field: 'id', visible: false},
                {title: '#', field: 'item_key', width: 50, hozAlign: "right"},
                {title: 'Title', field: 'title', width: 600, editor: 'input', editorParams: { elementAttributes: { maxlength: "64"} } },
                {title: "Material", field: "material", width: 300, editor: 'input', editorParams: { elementAttributes: { maxlength: "32"} } },
                {title: "Insurance Price", field: "sale_price", headerWordWrap: true, width: 100, hozAlign: "right",
                    editor: 'number', editorParams: {min: 1}, formatter: "money",
                    formatterParams: {decimal: '.', thousand: ',', symbol: '$', negativeSign: true}, },
                {title: "Delete", field: "uses", formatter: deleteicon, hozAlign: "center", headerSort: false, cellClick: function (e, cell) { deleterow(e, cell.getRow());}},
                {title: "To Del", field: "to_delete", visible: this.#debugVisible},
                {title: "Status", field: "status", visible: this.#debugVisible},
            ]
        });
        this.#nfsItemsDirty = false;
        this.#nfsItemTable.on("dataChanged", function (data) {
            _this.dataChangedNfs(data);
        });
        this.#nfsItemTable.on("cellEdited", cellChanged);

        this.#nfsSaveBtn.innerHTML='Save Changes';
        this.#nfsSaveBtn.disbled=true;
    }
    
}

auctionItemRegistration = null;
// init
function auctionItemRegistrationOnLoad(region) {
    auctionItemRegistration = new AuctionItemRegistration(config['debug']);
}

function cellChanged(cell) {
//    dirty = true;
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
