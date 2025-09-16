//import { TabulatorFull as Tabulator } from 'tabulator-tables';
// policy class - all edit membership policy functions
policyMCEInit = false;
class policySetup {
    #messageDiv = null;
    #policyPane = null;
    #policyTable = null;
    #policies = null;
    #policyDirty = false;
    #policySaveBtn = null;
    #policyUndoBtn = null;
    #policyRedoBtn = null;
    #policyAddRowBtn = null;
    #dirty = false;

    // edit & Preview items
    #editPreviewModal = null;
    #editPreviewTitle = null;
    #editBlock = null
    #previewBlock = null;
    #editPreviewSaveBtn = null;
    #editPolicyName = null;
    #editPolicyNameDiv = null;
    #policyPrompt = null;
    #policyDescription = null;
    #previewDescIcon = null;
    #previewPolicyName = null;
    #previewDescriptionText = null;
    #p_preview = null;
    #l_preview = null;
    #l_required = null;

    #debug = 0;
    #debugVisible = false;

    // globals before open
    constructor(debug) {
        this.#debug = debug;
        if (this.#debug & 2) {
            this.#debugVisible = true;
        }
        this.#messageDiv = document.getElementById('test');
        this.#policyPane = document.getElementById('policy-pane');
        var id = document.getElementById('editPreviewModal');
        if (id) {
            this.#editPreviewModal = new bootstrap.Modal(id, {focus: true, backdrop: 'static'});
            this.#editPreviewTitle = document.getElementById('editPreviewTitle');
            this.#editBlock = document.getElementById('editBlockDiv');
            this.#previewBlock = document.getElementById('previewBlockDiv');
            this.#editPreviewSaveBtn = document.getElementById('editPreviewSaveBtn');
            this.#previewPolicyName = document.getElementById('previewPolicyName');
            this.#editPolicyNameDiv = document.getElementById('editPolicyName');
            this.#policyPrompt = document.getElementById('policyPrompt');
            this.#policyDescription = document.getElementById('policyDescription');
            this.#p_preview = document.getElementById('p_preview');
            this.#l_preview = document.getElementById('l_preview');
            this.#l_required = document.getElementById('l_required');
            this.#previewDescIcon = document.getElementById('previewDescIcon');
            this.#previewDescriptionText = document.getElementById('previewDescriptionText');
            if (policyMCEInit) {
                tinyMCE.get("policyDescription").focus();
                tinyMCE.get("policyDescription").load();
                tinyMCE.get("policyPrompt").focus();
                tinyMCE.get("policyPrompt").load();
            } else {
                // start the tinyMCE editors
                tinyMCE.init({
                    selector: 'textarea#policyPrompt',
                    id: "prompt",
                    height: 400,
                    min_height: 300,
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
                    content_style: 'body {font - family:Helvetica,Arial,sans-serif; font-size:14px }',
                    placeholder: 'Edit the policy prompt...',
                    auto_focus: 'editFieldArea',
                });
                tinyMCE.init({
                    selector: 'textarea#policyDescription',
                    id: "desc",
                    height: 400,
                    min_height: 300,
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
                    content_style: 'body {font - family:Helvetica,Arial,sans-serif; font-size:14px }',
                    placeholder: 'Edit the description here...',
                    auto_focus: 'editFieldArea',
                });
                // Prevent Bootstrap dialog from blocking focusin
                document.addEventListener('focusin', (e) => {
                    if (e.target.closest(".tox-tinymce, .tox-tinymce-aux, .moxman-window, .tam-assetmanager-root") !== null) {
                        e.stopImmediatePropagation();
                    }
                });
                policyMCEInit = true;
            }
        }
    };

    // called on open of the policy window
    open() {
        var html = `
        <div class="container-fluid">
            <div class="row">
                <div class="col-sm-12">
                    <h4><strong>Policy Setup Tables:</strong></h4>
                </div>
            </div>
            <div class="row">
                <div class="col-sm-12 p-0 m-0" id="policyTableDiv"></div>
            </div>
            <div class="row mt-2">
                <div class="col-sm-auto" id="types-buttons">
                    <button id="policy-undo" type="button" class="btn btn-secondary btn-sm" onclick="policy.undo(); return false;" disabled>Undo</button>
                    <button id="policy-redo" type="button" class="btn btn-secondary btn-sm" onclick="policy.redo(); return false;" disabled>Redo</button>
                    <button id="policy-addrow" type="button" class="btn btn-secondary btn-sm" onclick="policy.addrow(); return false;">Add New</button>
                    <button id="policy-save" type="button" class="btn btn-primary btn-sm"  onclick="policy.save(); return false;" disabled>Save Changes</button>
                    <button id="policy-csv" type="button" class="btn btn-info btn-sm"  onclick="policy.download('csv'); return false;">Download CSV</button>
                    <button id="policy-xlsx" type="button" class="btn btn-info btn-sm"  onclick="policy.download('xlsx'); return false;">Download Excel</button>
                </div>
            </div>
        </div>`;
        this.#policyPane.innerHTML = html;
        this.#policies = null;
        this.#dirty = false;
        var _this = this;
        var script = "scripts/regadmin_getConfigTables.php";
        var postdata = {
            ajax_request_action: 'policy',
            tablename: "policy",
            indexcol: "policy"
        };
        clear_message();
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

    // draw the policy edit screen
    draw(data, textStatus, jhXHR) {
        var _this = this;

        if (this.#policyTable != null) {
            this.#policyTable.off("dataChanged");
            this.#policyTable.off("rowMoved")
            this.#policyTable.off("cellEdited");
            this.#policyTable.destroy();
            this.#policyTable = null;
        }
        if (!data['policies']) {
            show_message("Error loading policies", 'error');
            return;
        }
        this.#policies = data['policies'];
        this.#policyDirty = false;
        this.#policyTable = new Tabulator('#policyTableDiv', {
            history: true,
            movableRows: true,
            data: this.#policies,
            layout: "fitDataTable",
            index: "policy",
            pagination: true,
            paginationAddRow:"table",
            paginationSize: 10,
            paginationSizeSelector: [10, 25, 50, 100, 250, true], //enable page size select element with these options
            columns: [
                {rowHandle: true, formatter: "handle", frozen: true, width: 30, minWidth: 30, maxWidth: 30, headerSort: false, },
                {title: "Policy", field: "policy", editor: "input", width: 200, headerSort: true, validator: "required", },
                {title: "Edit", formatter: this.editbutton, formatterParams: {table: 'policies' }, hozAlign:"left", headerSort: false },
                {title: "Prompt", field: "prompt", headerSort: false, width: 600, headerFilter: true, validator: "required", formatter: this.toHTML, },
                {title: "Description", field: "description", headerSort: false, headerFilter: true, width: 600, validator: "required", formatter: this.toHTML },
                {
                    title: "Req", field: "required", headerSort: true,
                    editor: "list", editorParams: { values: ["Y", "N"], }, width: 70, validator: "required"
                },
                {
                    title: "Default Value", field: "defaultValue", headerWordWrap: true, headerSort: true,
                    editor: "list", editorParams: { values: ["Y", "N"], }, width: 70, validator: "required"
                },
                {
                    title: "Active", field: "active", headerWordWrap: true, headerSort: true,
                    editor: "list", editorParams: { values: ["Y", "N"], }, width: 70, validator: "required"
                },
                {title: "Sort Order", field: "sortOrder", visible: this.#debugVisible, headerFilter: false, headerWordWrap: true, width: 80,},
                {title: "Orig Key", field: "policyKey", visible: this.#debugVisible, headerFilter: false, headerWordWrap: true, width: 200,},
                {
                    title: "Delete", field: "uses", formatter: deleteicon, hozAlign: "center", headerSort: false,
                    cellClick: function (e, cell) {
                        deleterow(e, cell.getRow());
                        _this.checkUndoRedo();
                    }
                },
                {title: "To Del", field: "to_delete", visible: this.#debugVisible,}
            ],
        });
        this.#policyTable.on("dataChanged", function (data) {
            _this.dataChanged();
        });
        this.#policyTable.on("rowMoved", function (row) {
            _this.rowMoved(row)
        });
        this.#policyTable.on("cellEdited", cellChanged);

        this.#policyUndoBtn = document.getElementById('policy-undo');
        this.#policyRedoBtn = document.getElementById('policy-redo');
        this.#policyAddRowBtn = document.getElementById('policy-addrow');
        this.#policySaveBtn = document.getElementById('policy-save');
    }

    // table related functions
    // display edit button for a long field
    editbutton(cell, formatterParams, onRendered) {
        var policyName = cell.getRow().getIndex()
        if (policyName != '') {
            return '<button class="btn btn-secondary" style = "--bs-btn-padding-y: .0rem; --bs-btn-padding-x: .3rem; --bs-btn-font-size: .75rem;",' +
                ' onclick="policy.editPreview(\'policy\',\'' + policyName + '\');">Edit Policy</button>';
        }
        return "Save First";
    }

    toHTML(cell,  formatterParams, onRendered) {
        var item = cell.getValue();
        return item;
    }

    // add row to  table and scroll to that new row
    addrow() {
        var _this = this;
        this.#policyTable.clearFilter(true);
        this.#policyTable.addRow({policy: 'new-row', prompt: '', description: '', required: 'N', active: 'Y',
            defaultValue: 'Y', sortOrder: 99, uses: 0}, false).then(function (row) {
            row.getTable().setPage('last').then(function() {
                row.getCell("policy").getElement().style.backgroundColor = "#fff3cd";
                row.getCell("prompt").getElement().style.backgroundColor = "#fff3cd";
                row.getCell("description").getElement().style.backgroundColor = "#fff3cd";
                row.getCell("required").getElement().style.backgroundColor = "#fff3cd";
                row.getCell("active").getElement().style.backgroundColor = "#fff3cd";
                row.getCell("defaultValue").getElement().style.backgroundColor = "#fff3cd";
                _this.checkUndoRedo();
            });
        });
    }

    dataChanged() {
        //data - the updated table data
        if (!this.#dirty) {
            this.#policySaveBtn.innerHTML = "Save Changes*";
            this.#policySaveBtn.disabled = false;
            this.#dirty = true;
        }
        this.checkUndoRedo();
    };

    rowMoved(row) {
        this.#policySaveBtn.innerHTML = "Save Changes*";
        this.#policySaveBtn.disabled = false;
        this.#dirty = true;
        this.checkUndoRedo();
    }

    undo() {
        if (this.#policyTable != null) {
            this.#policyTable.undo();

            if (this.checkUndoRedo() <= 0) {
                this.#dirty = false;
                this.#policySaveBtn.innerHTML = "Save Changes";
                this.#policySaveBtn.disabled = true;
            }
        }
    };

    redo() {
        if (this.#policyTable != null) {
            this.#policyTable.redo();

            if (this.checkUndoRedo() > 0) {
                this.#dirty = true;
                this.#policySaveBtn.innerHTML = "Save Changes*";
                this.#policySaveBtn.disabled = false;
            }
        }
    };

    // set undo / redo status for buttons
    checkUndoRedo() {
        var undosize = this.#policyTable.getHistoryUndoSize();
        this.#policyUndoBtn.disabled = undosize <= 0;
        this.#policyRedoBtn.disabled = this.#policyTable.getHistoryRedoSize() <= 0;
        return undosize;
    }

    // process the save button on the preview pane
    editPreviewSave() {
        var policyPrompt = tinyMCE.get('policyPrompt').getContent();
        var policyDesc = tinyMCE.get('policyDescription').getContent();

        // these will be encoded in <p> tags already, so strip the leading and trailing ones.
        if (policyPrompt.startsWith('<p>')) {
            policyPrompt = policyPrompt.substring(3);
        }
        if (policyPrompt.endsWith('</p>')) {
            policyPrompt = policyPrompt.substring(0, policyPrompt.length - 4);
        }
        if (policyDesc.startsWith('<p>')) {
            policyDesc = policyDesc.substring(3);
        }
        if (policyDesc.endsWith('</p>')) {
            policyDesc = policyDesc.substring(0, policyDesc.length - 4);
        }

        var policyRow = this.#policyTable.getRow(this.#editPolicyName);
        policyRow.getCell("prompt").setValue(policyPrompt);
        policyRow.getCell("description").setValue(policyDesc);
        this.#editPreviewModal.hide();
        this.dataChanged();
    }

    // save - save the policy entries back to the database
    save() {
        var _this = this;

        if (this.#policyTable != null) {
            var invalids = this.#policyTable.validate();
            if (!invalids === true) {
                console.log(invalids);
                show_message("Policy Table does not pass validation, please check for empty cells or cells in red", 'error');
                return false;
            }

            this.#policySaveBtn.innerHTML = "Saving...";
            this.#policySaveBtn.disabled = true;

            var script = "scripts/regadmin_updateConfigTables.php";

            var postdata = {
                ajax_request_action: 'policy',
                tabledata: JSON.stringify(this.#policyTable.getData()),
                tablename: "policy",
                indexcol: "policy"
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
                        _this.#policySaveBtn.innerHTML = "Save Changes*";
                        _this.#policySaveBtn.disabled = false;
                        return false;
                    }
                    policy.close();
                    policy.open();
                    show_message(data['success'], 'success');
                },
                error: function (jqXHR, textStatus, errorThrown) {
                    showError("ERROR in " + script + ": " + textStatus, jqXHR);
                    return false;
                }
            });
        }
    }

    // open the previewEdit modal and populate it with the stuff for this entry and it's save back
    editPreview(table, policyName) {
        //console.log(table);
        //console.log(policyName);
        this.#editPolicyName = policyName;
        var policyRow = this.#policyTable.getRow(policyName).getData();
        editPreviewClass = 'policy';
        var policyName = policyRow.policy;
        var policyPrompt = policyRow.prompt;
        var policyDescription = policyRow.description;
        var polictRequired = policyRow.required;

        // build the modal contents
        this.#editPreviewTitle.innerHTML = "Edit/Preview the " + policyName + " policy";
        this.#previewPolicyName.innerHTML = policyName;
        this.#editPolicyNameDiv.innerHTML = policyName;
        this.#policyPrompt.innerHTML = policyPrompt;
        this.#policyDescription.innerHTML = policyDescription;
        this.#p_preview.checked = false;
        this.#l_preview.innerHTML = policyPrompt;
        this.#l_required.hidden = polictRequired != 'Y';
        this.#previewDescIcon.hidden = policyDescription == '';
        this.#previewDescriptionText.innerHTML = policyDescription;
        $("#previewTip").hide();

        tinyMCE.get("policyDescription").focus();
        tinyMCE.get("policyDescription").load();
        tinyMCE.get("policyPrompt").focus();
        tinyMCE.get("policyPrompt").load();

        this.#editPreviewModal.show();
    }

    updatePreview() {
        var policyPrompt = tinyMCE.get('policyPrompt').getContent();
        var policyDesc = tinyMCE.get('policyDescription').getContent();

        var policyRow = this.#policyTable.getRow(this.#editPolicyName).getData();
        var polictRequired = policyRow.required;

        // these are already in paragraph tags, strip off the leading and trailing ones in the string
        if (policyPrompt.startsWith('<p>')) {
            policyPrompt = policyPrompt.substring(3);
        }
        if (policyPrompt.endsWith('</p>')) {
            policyPrompt = policyPrompt.substring(0, policyPrompt.length - 4);
        }
        if (policyDesc.startsWith('<p>')) {
            policyDesc = policyDesc.substring(3);
        }
        if (policyDesc.endsWith('</p>')) {
            policyDesc = policyDesc.substring(0, policyDesc.length - 4);
        }

        policyPrompt = policyPrompt.trim();
        policyDesc = policyDesc.trim();
        this.#p_preview.checked = false;
        this.#l_preview.innerHTML = policyPrompt;
        this.#previewDescIcon.hidden = policyDesc == '';
        this.#previewDescriptionText.innerHTML = policyDesc;
        $("#previewTip").hide();
    }

    // save off the table as a file
    download(format) {
        if (this.#policyTable == null)
            return;

        var filename = 'policies';
        var tabledata = JSON.stringify(this.#policyTable.getData("active"));
        var fieldList = [
            'policy',
            'prompt',
            'description',
            'required',
            'defaultValue',
            'active',
            'createDate',
            'updateDate',
            'sortOrder'
        ];
        downloadFilePost(format,  filename, tabledata, null, fieldList);
    }

    // on close of the pane, clean up the items
    close() {
         if (this.#policyTable != null) {
            this.#policyTable.off("dataChanged");
            this.#policyTable.off("rowMoved")
            this.#policyTable.off("cellEdited");
            this.#policyTable.destroy();
            this.#policyTable = null;
        }

        this.#policyPane.innerHTML = '';
    };
}