//import { TabulatorFull as Tabulator } from 'tabulator-tables';
// policy class - all edit membership policy functions
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

    // edit & Preview items
    #editPreviewModal = null;
    #editPreviewTitle = null;
    #editBlock = null
    #previewBlock = null;
    #editPreviewSaveBtn = null;
    #editPolicyName = null;

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
                </div>
            </div>
        </div>`;
        this.#policyPane.innerHTML = html;
        this.#policies = null;
        var _this = this;
        var script = "scripts/getPolicyConfig.php";
        $.ajax({
            url: script,
            method: 'POST',
            data: { type: 'all', },
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
        }
        if (!data['policies']) {
            show_message("Error loading policies", 'error');
            return;
        }
        this.#policies = data['policies'];
        this.#policyDirty = false;
        this.#policyTable = new Tabulator('#policyTableDiv', {
            maxHeight: "800px",
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
                {title: "Policy", field: "policy", width: 200, headerSort: true},
                {title: "Prompt", field: "prompt", headerSort: false, width: 600, headerFilter: true, validator: "required", },
                {title: "Description", field: "description", headerSort: false, headerFilter: true, width: 600, validator: "required", },
                {
                    title: "Req", field: "required", headerSort: true,
                    editor: "list", editorParams: { values: ["Y", "N"], }, width: 70, validator: "required"
                },
                {
                    title: "Default Value", field: "defaultValue", headerWordWrap: true, headerSort: true,
                    editor: "list", editorParams: { values: ["Y", "N"], }, width: 70, validator: "required"
                },
                {title: "Edit", formatter: this.editbutton, formatterParams: {table: 'policies' }, hozAlign:"left", headerSort: false },
                {title: "Sort Order", field: "sortorder", visible: this.#debugVisible, headerFilter: false, headerWordWrap: true, width: 80,},
                {title: "Orig Key", field: "policyKey", visible: this.#debugVisible, headerFilter: false, headerWordWrap: true, width: 200,},
                {
                    title: "Delete", field: "uses", formatter: deleteicon, hozAlign: "center", headerSort: false,
                    cellClick: function (e, cell) {
                        deleterow(e, cell.getRow());
                    }
                },
                {title: "To Del", field: "to_delete", visible: this.#debugVisible,}
            ],
        });
        this.#policyTable.on("dataChanged", function (data) {
            _this.dataChanged(data);
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

    // add row to  table and scroll to that new row
    addrow() {
        var _this = this;
        this.#policyTable.addRow({policy: 'new-row', prompt: '', desccription: '', required: 'N',
            defaultValue: 'Y', sortorder: 99, uses: 0}, false).then(function (row) {
            _this.#policyTable.setPage("last"); // adding new to last page always
            row.getTable().scrollToRow(row);
            _this.checkUndoRedo();
        });
    }

    // set undo / redo status for buttons
    checkUndoRedo() {
        var undosize = this.#policyTable.getHistoryUndoSize();
        this.#policyUndoBtn.disabled = undosize <= 0;
        this.#policyRedoBtn.disabled = this.#policyTable.getHistoryRedoSize() <= 0;
        return undosize;
    }

    // open the previewEdit modal and populate it with the stuff for this entry and it's save back
    editPreview(table, policyName) {
        console.log(table);
        console.log(policyName);
        this.#editPolicyName = policyName;
        var policyRow = this.#policyTable.getRow(policyName).getData();
        editPreviewClass = 'policy';
        var policyName = policyRow.policy;
        var policyPrompt = policyRow.prompt;
        var policyDescription = policyRow.description;
        var polictRequired = policyRow.required;

        // build the modal contents
        this.#editPreviewTitle.innerHTML = "Edit/Preview the " + policyName + " policy";
        var html = `
        <div class="row mt-4">
            <div class="col-sm-12"><h4>Edit the ` + policyName + ` policy</h4></div>
        </div>
        <div class="row mt-2">
            <div class="col-sm-12"><b>Policy Prompt:</b></div>
        </div>
        <div class="row mt-1">
            <div class="col-sm-12">
                <textarea id="policyPrompt" name="policyPrompt">` + policyPrompt + `</textarea>
            </div>
        </div>
        <div class="row mt-4">
            <div class="col-sm-12"><b>Policy Description:</b></div>
        </div>
        <div class="row mt-1">
            <div class="col-sm-12">
                <textarea id="policyDescription" name="policyDescription">\` + policyDescription + \`</textarea>
            </div>
        </div>
        `;
        this.#editBlock.innerHTML = html;

        html = `
        <div class="row mt-4">
            <div class="col-sm-12"><h4>Preview the ` + policyRow.policy +
            ` policy <button class="btn btn-primary" onclick="updatePreview()">Update Preview</button></h4></div>
        </div>
        <div class='row'>
            <div class='col-sm-12'>
                <p class='text-body'>
                    <label>
                        <input type='checkbox' name='p_` + policyName + " id='p_" + policyName + `' value='Y'/>
                        <span id="l_` + policyName + '">' +
                            (polictRequired == 'Y' ? "<span class='warn'>&bigstar;</span>" : '') +
                            policyPrompt + `</span>
                    </label>
`;
                    if (policyDescription != '') {
                        html += `
                        <span class="small"><a href='javascript:void(0)' onClick='$("#` + policyName + `Tip").toggle()'>
                            <img src="/images/infoicon.png"  alt="click this info icon for more information" style="max-height: 25px;"></a></span>
                <div id='` + policyName + `Tip' class='padded highlight' style='display:none'>
                    <p class='text-body'>` + policyDescription + `
                        <span class='small'><a href='javascript:void(0)' onClick='$("#` + policyName + `Tip").toggle()'>
                              <img src='/images/closeicon.png' alt='click this close icon to close the more information window' style='max-height: 25px;'>
                            </a></span>
                    </p>
                </div>
`;
                    }
                    html += `
                </p>
            </div>
        </div>         
`;
        this.#previewBlock.innerHTML = html;

        // start the tinyMCE editors
        tinyMCE.init({
            selector: 'textarea#policyPrompt',
            height: 400,
            min_height: 300,
            menubar: false,
            license_key: 'gpl',
            plugins: 'advlist lists image link charmap fullscreen help nonbreaking preview searchreplace',
            toolbar:  [
                'help undo redo searchreplace copy cut paste pastetext | fontsizeinput styles h1 h2 h3 h4 h5 h6 | ' +
                'bold italic underline strikethrough removeformat | '+
                'visualchars nonbreaking charmap hr | ' +
                'preview fullscreen ',
                'alignleft aligncenter alignright alignnone | outdent indent | numlist bullist checklist | forecolor backcolor | link image'
            ],
            content_style: 'body {font - family:Helvetica,Arial,sans-serif; font-size:14px }',
            placeholder: 'Edit the policy prompt...',
            auto_focus: 'editFieldArea',
            init_instance_callback: function (editor) {
                editor.setContent(policyPrompt);
            }
        });
        tinyMCE.init({
            selector: 'textarea#policyDescription',
            height: 400,
            min_height: 300,
            menubar: false,
            license_key: 'gpl',
            plugins: 'advlist lists image link charmap fullscreen help nonbreaking preview searchreplace',
            toolbar:  [
                'help undo redo searchreplace copy cut paste pastetext | fontsizeinput styles h1 h2 h3 h4 h5 h6 | ' +
                'bold italic underline strikethrough removeformat | '+
                'visualchars nonbreaking charmap hr | ' +
                'preview fullscreen ',
                'alignleft aligncenter alignright alignnone | outdent indent | numlist bullist checklist | forecolor backcolor | link image'
            ],
            content_style: 'body {font - family:Helvetica,Arial,sans-serif; font-size:14px }',
            placeholder: 'Edit the description here...',
            auto_focus: 'editFieldArea',
            init_instance_callback: function (editor) {
                editor.setContent(policyDescription);
            }
        });

        this.#editPreviewModal.show();
    }

    // on close of the pane, clean up the items
    close() {
         if (this.#policyTable != null) {
            this.#policyTable.off("dataChanged");
            this.#policyTable.off("rowMoved")
            this.#policyTable.off("cellEdited");
            this.#policyTable.destroy();
        }

        this.#policyPane.innerHTML = '';
    };
}