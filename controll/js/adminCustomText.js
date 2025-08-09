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
    #textOnly = false;

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
        var script;
        var html = `
<div class="container-fluid">
    <div class="row">
       <div class="col-sm-6">
            <h4><strong>Edit Custom Text:</strong></h4>
       </div>
       <div class="col-sm-6 text-end">
            <strong><a href="markdown.php?mdf=md/CustomText.md" target="_new">Display Custom Text Documentation</a></strong>
        </div>
    </div>
    <div class="row">
        <div class="col-sm-12 p-0 m-0" id="customTextTableDiv"></div>
    </div>
    <div class="row mt-2">
        <div class="col-sm-auto" id="types-buttons">
            <button id="customText-undo" type="button" class="btn btn-secondary btn-sm" onclick="customText.undo(); return false;" disabled>Undo</button>
            <button id="customText-redo" type="button" class="btn btn-secondary btn-sm" onclick="customText.redo(); return false;" disabled>Redo</button>
            <button id="customText-save" type="button" class="btn btn-primary btn-sm"  onclick="customText.save(); return false;" disabled>Save Changes</button>
            <button id="customText-csv" type="button" class="btn btn-info btn-sm"  onclick="customText.download('csv'); return false;">Download CSV</button>
            <button id="customText-csv" type="button" class="btn btn-info btn-sm"  onclick="customText.download('xlsx'); return false;">Download Excel</button>
        </div>
    </div>
</div>
`;
        this.#customTextPane.innerHTML = html;
        this.#customText = null;
        var _this = this;
        switch (config['pageName']) {
            case 'exhibitor':
                script = "scripts/exhibitsGetCustomText.php";
                break;
            case 'regAdmin':
                script = "scripts/regadmin_getConfigTables.php";
                break;
            default:
                show_message("Invalid page call", 'error');
                return;
        }
        var postdata = {
            ajax_request_action: 'customText',
            tablename: "customText",
            indexcol: "none",
            page: config['page'],
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
            pagination: this.#customText.length > 25,
            paginationAddRow:"table",
            paginationSize: 10,
            paginationSizeSelector: [10, 25, 50, 100, 250, true], //enable page size select element with these options
            columns: [
                {title: "Edit", formatter: this.editbutton, formatterParams: {table: 'customText' }, hozAlign:"left", headerSort: false },
                {title: "#", field: "rownum", width: 50, visible: this.#debugVisible, },
                {title: "App", field: "appName", width: 100, headerSort: true, headerFilter: true, },
                {title: "Page", field: "appPage", width: 150, headerSort: true, headerFilter: true, },
                {title: "Section", field: "appSection", width:150, headerSort: true, headerFilter: true, },
                {title: "Item", field: "txtItem", width: 150, headerSort: true, headerFilter: true, },
                {title: "Description", field: "txtItemDescription", width: 300, headerSort: true, headerFilter: true, formatter: "textarea" },
                {title: "Custom Text", field: "contents", headerSort: false, width: 950, headerFilter: true, validator: "required", formatter: this.toHTML },
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
                ' onclick="customText.editCustomText(' + index + ');">Edit</button>';
    }

    toHTML(cell,  formatterParams, onRendered) {
        var text = cell.getValue();
        var item = cell.getData().txtItem;
        if (item == 'text')
            text = text.replaceAll("\n", '<br/>');
        return text;
    }

    editCustomText(index) {
        var row = this.#customTextTable.getRow(index).getData();

        var titleName = row.appName + '-' + row.appPage + '-' + row.appSection + '-' + row.txtItem;
        this.#textOnly = row.txtItem == 'text';
        var textItem = row.contents;
        showEdit('customText', 'customText', index, row.txtItemDescription, titleName, textItem, this.#textOnly);
    }

    editReturn(editTable, editfield, editIndex, editvalue) {
        var row = this.#customTextTable.getRow(editIndex);
        row.getCell('contents').setValue(editvalue);
        this.dataChanged();
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
        var script;
        var _this = this;

        if (this.#customTextTable != null) {
            this.#customTextSaveBtn.innerHTML = "Saving...";
            this.#customTextSaveBtn.disabled = true;

            switch (config['pageName']) {
                case 'exhibitor':
                    script = "scripts/exhibitsUpdateCustomText.php";
                    break;
                case 'regAdmin':
                    script = "scripts/regadmin_updateConfigTables.php";
                    break;
                default:
                    show_message("Invalid page call", 'error');
                    return;
            }

            // the btoa of encodeURI is to get past passing html code up to the servrer with commodo security checking in the way
            // it's stupid but it works, may have to resort to this for interests and policies in the future too.
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
                    _this.saveSuccess(data);
                },
                error: function (jqXHR, textStatus, errorThrown) {
                    showError("ERROR in " + script + ": " + textStatus, jqXHR);
                    return false;
                }
            });
        }
    }

    // success save - process the changes
    saveSuccess(data) {
        if (data['error']) {
            show_message(data['error'], 'error');
            // reset save button
            this.dataChanged();
            return false;
        }
        /* update routines do a reload, saving a round trip */
        this.#customTextTable.replaceData(data['customText']);
        this.#dirty = false;
        this.#customTextSaveBtn.innerHTML = "Save Changes";
        this.#customTextRedoBtn.disabled = true;
        show_message(data['success'], 'success');
    }

    // save off the table as a file
    download(format) {
        if (this.#customTextTable == null)
            return;

        var filename = 'customText';
        var tabledata = JSON.stringify(this.#customTextTable.getData("active"));
        var fieldList = [
            { key: 'appName', label: 'App' },
            { key: 'appPage', label: 'Page' },
            { key: 'appSection', label: 'Section' },
            { key: 'txtItem', label: 'Item' },
            { key: 'txtItemDescription', label: 'Description' },
            { key: 'contents', label: 'Custom_Text' },
        ];
        downloadFilePost(format,  filename, tabledata, null, fieldList);
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
