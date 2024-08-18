//import { TabulatorFull as Tabulator } from 'tabulator-tables';
// customText class - all edit Customtext functions
class customTextSetup {
    #messageDiv = null;
    #customTextPane = null;
    #customTextTable = null;
    #customText = null;
    #customTextDirty = false;
    #customTextSaveBtn = null;
    #customTextUndoBtn = null;
    #customTextRedoBtn = null;

    #dirty = false;
    #debug = 0;
    #debugVisible = false;

    // globals before open
    constructor() {
        this.#messageDiv = document.getElementById('test');
        this.#customTextPane = document.getElementById('customtext-pane');

    };


    // called on open of the custom text window
    open() {
        var html = `<h4><strong>Edit Custom Text:</strong></h4>
<div class="container-fluid">
    <div class="row">
    <div class="row">
        <div class="col-sm-12 p-0 m-0" id="customTextTableDiv"></div>
    </div>
    <div class="row mt-2">
        <div class="col-sm-auto" id="types-buttons">
            <button id="customText-undo" type="button" class="btn btn-secondary btn-sm" onclick="customText.undo(); return false;" disabled>Undo</button>
            <button id="customText-redo" type="button" class="btn btn-secondary btn-sm" onclick="customText.redo(); return false;" disabled>Redo</button>
            <button id="customText-save" type="button" class="btn btn-primary btn-sm"  onclick="customText.save(); return false;" disabled>Save Changes</button>
        </div>
    </div>
</div>
`;
        this.#customTextPane.innerHTML = html;
        this.#customText = null;
        var _this = this;
        var script = "scripts/regadmin_getConfigTables.php";
        var postdata = {
            ajax_request_action: 'customText',
            tablename: "customText",
            indexcol: "none"
        };
        clear_message();
        this.#dirty = false;
        $.ajax({
            url: script,
            method: 'POST',
            data: postdata,
            success: function (data, textStatus, jhXHR) {
                _this.draw(data, textStatus, jhXHR);
            },
            error: function (jqXHR, textStatus, errorThrown) {
                showError("ERROR in " + script + ": " + textStatus, jqXHR);
                return false;
            }
        });
    }

    // draw the customText edit screen
    draw(data, textStatus, jhXHR) {
        var _this = this;

        if (this.#customTextTable != null) {
            this.#customTextTable.off("dataChanged");
            this.#customTextTable.off("cellEdited");
            this.#customTextTable.destroy();
            this.#customTextTable = null;
        }
        if (!data['customText']) {
            show_message("Error loading custom text", 'error');
            return;
        }
        this.#customText = data['customText'];
        this.#customTextDirty = false;
        this.#customTextTable = new Tabulator('#customTextTableDiv', {
            history: true,
            data: this.#customText,
            layout: "fitDataTable",
            index: 'rownum',
            pagination: true,
            paginationAddRow:"table",
            paginationSize: 10,
            paginationSizeSelector: [10, 25, 50, 100, 250, true], //enable page size select element with these options
            columns: [
                {title: "#", field: "rownum", width: 50, visible: this.#debugVisible, },
                {title: "Application", field: "appName", width: 150, headerSort: true, headerFilter: true, },
                {title: "Page", field: "appPage", width: 150, headerSort: true, headerFilter: true, },
                {title: "Section", field: "appSection", width:150, headerSort: true, headerFilter: true, },
                {title: "Item", field: "txtItem", width: 150, headerSort: true, headerFilter: true, },
                {title: "Custom Text", field: "contents", headerSort: false, width: 1300, headerFilter: true, validator: "required", },
                {title: "Edit", formatter: this.editbutton, formatterParams: {table: 'customText' }, hozAlign:"left", headerSort: false },
            ],
        });
        this.#customTextTable.on("dataChanged", function (data) {
            _this.dataChanged();
        });
        this.#customTextTable.on("cellEdited", cellChanged);
        this.#customTextUndoBtn = document.getElementById('customText-undo');
        this.#customTextRedoBtn = document.getElementById('customText-redo');
        this.#customTextSaveBtn = document.getElementById('customText-save');
    }

    // table related functions
    // display edit button for a long field
    editbutton(cell, formatterParams, onRendered) {
        var index = cell.getRow().getIndex()
        return '<button class="btn btn-secondary" style = "--bs-btn-padding-y: .0rem; --bs-btn-padding-x: .3rem; --bs-btn-font-size: .75rem;",' +
                ' onclick="customText.editCustomText(' + index + ');">Edit Custom Text</button>';
    }

    editCustomText(index) {
        var row = this.#customTextTable.getRow(index).getData();

        var titleName = row.appName + '-' + row.appPage + '-' + row.appSection + '-' + row.txtItem;
        var textItem = row.contents;
        showEdit('customText', 'customText', index, '', titleName, textItem);
    }

    editReturn(editTable, editfield, editIndex, editvalue) {
        var row = this.#customTextTable.getRow(editIndex);
        row.getCell('contents').setValue(editvalue);
    }

    dataChanged() {
        //data - the updated table data
        if (!this.#dirty) {
            this.#customTextSaveBtn.innerHTML = "Save Changes*";
            this.#customTextSaveBtn.disabled = false;
            this.#dirty = true;
        }
        this.checkUndoRedo();
    };
    
    undo() {
        if (this.#customTextTable != null) {
            this.#customTextTable.undo();

            if (this.checkUndoRedo() <= 0) {
                this.#dirty = false;
                this.#customTextSaveBtn.innerHTML = "Save Changes";
                this.#customTextSaveBtn.disabled = true;
            }
        }
    };

    redo() {
        if (this.#customTextTable != null) {
            this.#customTextTable.redo();

            if (this.checkUndoRedo() > 0) {
                this.#dirty = true;
                this.#customTextSaveBtn.innerHTML = "Save Changes*";
                this.#customTextSaveBtn.disabled = false;
            }
        }
    };

    // set undo / redo status for buttons
    checkUndoRedo() {
        var undosize = this.#customTextTable.getHistoryUndoSize();
        this.#customTextUndoBtn.disabled = undosize <= 0;
        this.#customTextRedoBtn.disabled = this.#customTextTable.getHistoryRedoSize() <= 0;
        return undosize;
    }


    // save - save the customText entries back to the database
    save() {
        var _this = this;

        if (this.#customTextTable != null) {
            this.#customTextSaveBtn.innerHTML = "Saving...";
            this.#customTextSaveBtn.disabled = true;

            var script = "scripts/regadmin_updateConfigTables.php";

            var postdata = {
                ajax_request_action: 'customText',
                tabledata: btoa(encodeURI(JSON.stringify(this.#customTextTable.getData()))),
                tablename: "customText",
                indexcol: "customText"
            };
            clear_message();
            this.#dirty = false;
            //console.log(postdata);
            $.ajax({
                url: script,
                method: 'POST',
                data: postdata,
                success: function (data, textStatus, jhXHR) {
                    if (data['error']) {
                        show_message(data['error'], 'error');
                        // reset save button
                        _this.dataChanged();
                        return false;
                    }
                    customText.close();
                    customText.open();
                    show_message(data['success'], 'success');
                },
                error: function (jqXHR, textStatus, errorThrown) {
                    showError("ERROR in " + script + ": " + textStatus, jqXHR);
                    return false;
                }
            });
        }
    }

    // on close of the pane, clean up the items
    close() {
        if (this.#customTextTable) {
            this.#customTextTable.destroy();
            this.#customTextTable = null;
        }

        this.#customTextPane.innerHTML = '';
    };
}