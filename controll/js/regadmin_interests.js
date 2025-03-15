//import { TabulatorFull as Tabulator } from 'tabulator-tables';
// interests class - all edit interests functions
interestsDescriptionMCEInit = false;
class interestsSetup {
    #messageDiv = null;
    #interestsPane = null;
    #interestsTable = null;

    #interests = null;
    #interestsDirty = false;
    #interestsSaveBtn = null;
    #interestsUndoBtn = null;
    #interestsRedoBtn = null;
    #interestsAddRowBtn = null;
    #dirty = false;

    // edit & Preview items
    #editInterestModal = null;
    #editInterestTitle = null;
    #editBlock = null
    #editSaveBtn = null;
    #editInterestName = null;
    #editInterestNameDiv = null;
    #interestDescription = null;
    #iName = null;
    #iNotify = null;

    #debug = 0;
    #debugVisible = false;

    // globals before open
    constructor() {
        this.#debug = debug;
        if (this.#debug & 2) {
            this.#debugVisible = true;
        }

        this.#messageDiv = document.getElementById('test');
        this.#interestsPane = document.getElementById('interests-pane');

        var id = document.getElementById('editInterestsModal');
        if (id) {
            this.#editInterestModal = new bootstrap.Modal(id, {focus: true, backdrop: 'static'});
            this.#editInterestTitle = document.getElementById('editInterestsTitle');
            this.#editBlock = document.getElementById('editBlockDiv');
            this.#editSaveBtn = document.getElementById('editInterestSaveBtn');
            this.#editInterestNameDiv = document.getElementById('editInterestName');
            this.#interestDescription = document.getElementById('interestDescription');
            this.#iName = document.getElementById('iName');
            this.#iNotify = document.getElementById('iNotify');
            if (interestsDescriptionMCEInit) {
                tinyMCE.get("interestDescription").focus();
                tinyMCE.get("interestDescription").load();
            } else {
                // start the tinyMCE editors
                tinyMCE.init({
                    selector: 'textarea#interestDescription',
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
                    placeholder: 'Edit the interests prompt...',
                    auto_focus: 'editFieldArea',
                });
                // Prevent Bootstrap dialog from blocking focusin
                document.addEventListener('focusin', (e) => {
                    if (e.target.closest(".tox-tinymce, .tox-tinymce-aux, .moxman-window, .tam-assetmanager-root") !== null) {
                        e.stopImmediatePropagation();
                    }
                });
                interestsDescriptionMCEInit = true;
            }
        }
    };

    // called on open of the interests window
    open() {
        var html = `
    <div class="container-fluid">
        <div class="row">
            <div class="col-sm-12">
                <h4><strong>Interest Setup Tables:</strong></h4>
            </div>
        </div>
        <div class="row">
            <div class="col-sm-12 p-0 m-0" id="interestsTableDiv"></div>
        </div>
        <div class="row mt-2">
            <div class="col-sm-auto" id="types-buttons">
                <button id="interests-undo" type="button" class="btn btn-secondary btn-sm" onclick="interests.undo(); return false;" disabled>Undo</button>
                <button id="interests-redo" type="button" class="btn btn-secondary btn-sm" onclick="interests.redo(); return false;" disabled>Redo</button>
                <button id="interests-addrow" type="button" class="btn btn-secondary btn-sm" onclick="interests.addrow(); return false;">Add New</button>
                <button id="interests-save" type="button" class="btn btn-primary btn-sm"  onclick="interests.save(); return false;" disabled>Save Changes</button>
                <button id="interests-csv" type="button" class="btn btn-info btn-sm"  onclick="interests.csv(); return false;">Download CSV</button>

            </div>
        </div>
        <div class="row mt-4">
            <div class="col-sm-12"><h4><strong>Interests Preview:</strong></h4></div>
        </div>
        <div class="container-fluid" id="interestPreviewDiv"></div>
    </div>`;
        this.#interestsPane.innerHTML = html;
        this.#interests = null;
        var _this = this;
        var script = "scripts/regadmin_getConfigTables.php";
        var postdata = {
            ajax_request_action: 'interests',
            tablename: "interests",
            indexcol: "interest"
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

    // draw the interests edit screen
    draw(data, textStatus, jhXHR) {
        var _this = this;

        if (this.#interestsTable != null) {
            this.#interestsTable.off("dataChanged");
            this.#interestsTable.off("rowMoved")
            this.#interestsTable.off("cellEdited");
            this.#interestsTable.destroy();
            this.#interestsTable = null;
        }
        if (!data['interests']) {
            show_message("Error loading interests", 'error');
            return;
        }
        this.#interests = data['interests'];
        this.#interestsDirty = false;
        this.#interestsTable = new Tabulator('#interestsTableDiv', {
            history: true,
            movableRows: true,
            data: this.#interests,
            layout: "fitDataTable",
            index: "interest",
            pagination: true,
            paginationAddRow:"table",
            paginationSize: 10,
            paginationSizeSelector: [10, 25, 50, 100, 250, true], //enable page size select element with these options
            columns: [
                {rowHandle: true, formatter: "handle", frozen: true, width: 30, minWidth: 30, maxWidth: 30, headerSort: false, },
                {title: "Edit", formatter: this.editbutton, formatterParams: {table: 'interests' }, hozAlign:"left", headerSort: false },
                {title: "Interest", field: "interest", width: 200, headerSort: true},
                {title: "Description", field: "description", headerSort: false, width: 600, headerFilter: true, validator: "required", formatter: this.toHTML, },
                {title: "Notify List", field: "notifyList", headerSort: false, headerFilter: true, width: 600, validator: "required", formatter: "textarea", },
                {
                    title: "CSV", field: "csv", headerWordWrap: true, headerSort: true,
                    editor: "list", editorParams: { values: ["Y", "N"], }, width: 70, validator: "required"
                },
                {
                    title: "Active", field: "active", headerWordWrap: true, headerSort: true,
                    editor: "list", editorParams: { values: ["Y", "N"], }, width: 70, validator: "required"
                },
                {title: "Sort Order", field: "sortOrder", visible: this.#debugVisible, headerFilter: false, headerWordWrap: true, width: 80,},
                {title: "Orig Key", field: "interestsKey", visible: this.#debugVisible, headerFilter: false, headerWordWrap: true, width: 200,},
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
        this.#interestsTable.on("dataChanged", function (data) {
            _this.dataChanged();
        });
        this.#interestsTable.on("rowMoved", function (row) {
            _this.rowMoved(row)
        });
        this.#interestsTable.on("cellEdited", cellChanged);

        this.#interestsUndoBtn = document.getElementById('interests-undo');
        this.#interestsRedoBtn = document.getElementById('interests-redo');
        this.#interestsAddRowBtn = document.getElementById('interests-addrow');
        this.#interestsSaveBtn = document.getElementById('interests-save');

        setTimeout(InterestsdrawPreviewPane, 100);
    }

    // table related functions
    // display edit button for a long field
    editbutton(cell, formatterParams, onRendered) {
        var interestName = cell.getRow().getIndex()
        if (interestName != '') {
            return '<button class="btn btn-secondary" style = "--bs-btn-padding-y: .0rem; --bs-btn-padding-x: .3rem; --bs-btn-font-size: .75rem;",' +
                ' onclick="interests.editInterest(\'interests\',\'' + interestName + '\');">Edit Interest</button>';
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
        this.#interestsTable.addRow({interest: 'new-row', notifyList: '', description: '', csv: 'N',
            active: 'Y', sortOrder: 99, uses: 0}, false).then(function (row) {
            _this.#interestsTable.setPage("last"); // adding new to last page always
            row.getTable().scrollToRow(row);
            _this.checkUndoRedo();
        });
    }

    dataChanged() {
        //data - the updated table data
        if (!this.#dirty) {
            this.#interestsSaveBtn.innerHTML = "Save Changes*";
            this.#interestsSaveBtn.disabled = false;
            this.#dirty = true;
        }
        this.checkUndoRedo();
        this.drawPreviewPane();
    };

    rowMoved(row) {
        this.#interestsSaveBtn.innerHTML = "Save Changes*";
        this.#interestsSaveBtn.disabled = false;
        this.#dirty = true;
        this.checkUndoRedo();
    }

    undo() {
        if (this.#interestsTable != null) {
            this.#interestsTable.undo();

            if (this.checkUndoRedo() <= 0) {
                this.#dirty = false;
                this.#interestsSaveBtn.innerHTML = "Save Changes";
                this.#interestsSaveBtn.disabled = true;
            }
        }
    };

    redo() {
        if (this.#interestsTable != null) {
            this.#interestsTable.redo();

            if (this.checkUndoRedo() > 0) {
                this.#dirty = true;
                this.#interestsSaveBtn.innerHTML = "Save Changes*";
                this.#interestsSaveBtn.disabled = false;
            }
        }
    };

    // set undo / redo status for buttons
    checkUndoRedo() {
        var undosize = this.#interestsTable.getHistoryUndoSize();
        this.#interestsUndoBtn.disabled = undosize <= 0;
        this.#interestsRedoBtn.disabled = this.#interestsTable.getHistoryRedoSize() <= 0;
        return undosize;
    }

    // drawPreviewPane - given the row data draw the preview pane
    drawPreviewPane() {
        var data = this.#interestsTable.getData();
        var html = `
        <div class='row'>
            <div class='col-sm-auto'>
                <h3>Additional Interests or Needs</h3>
           </div>
        </div>
        <div class='row mb-2'>
            <div class='col-sm-auto'>
                This form lets us know if you want to be contacted about specific things. We ask these questions to help us give you the experience you are after.
            </div>
        </div>
`;

        for (var i=0; i < data.length; i++) {
            var interest = data[i];
            var desc = /*replaceVariables(*/interest['description']; //);
            html += `
        <div class='row mt-1'>
            <div class='col-sm-auto'>
                <input type='checkbox' id="i_` + i + `">
            </div>
            <div class='col-sm-auto'>
                <label for='i_` + i + "'>" + desc + `</label>
            </div>
        </div>
`;
        }
        document.getElementById('interestPreviewDiv').innerHTML = html;
    }

    // open the previewEdit modal and populate it with the stuff for this entry and it's save back
    editInterest(table, interestName) {
        //console.log(table);
        //console.log(interestName);
        this.#editInterestName = interestName;
        var interestRow = this.#interestsTable.getRow(interestName).getData();
        editPreviewClass = 'interests';
        var interestName = interestRow.interest;
        var interestDescription = '';
        if (interestRow.hasOwnProperty('description')) {
            if (interestRow.description != null && interestRow.description != undefined)
                interestDescription = interestRow.description;
        }

        // build the modal contents
        this.#editInterestTitle.innerHTML = "Edit the " + interestName + " interest";
        this.#editInterestNameDiv.innerHTML = interestName;
        this.#interestDescription.innerHTML = interestDescription;
        tinyMCE.activeEditor.setContent(interestDescription);
        this.#iName.value = interestRow.interest;
        this.#iNotify.value = interestRow.notifyList;
        this.#editInterestModal.show();
    }

    // on close of the pane, clean up the items
    close() {
            // on close of the pane, clean up the items
            if (this.#interestsTable != null) {
                this.#interestsTable.off("dataChanged");
                this.#interestsTable.off("rowMoved")
                this.#interestsTable.off("cellEdited");
                this.#interestsTable.destroy();
                this.#interestsTable = null;
            }
            
        this.#interestsPane.innerHTML = '';
    };

    // process the save button on the edit modal
    editInterestSave() {
        var description = tinyMCE.activeEditor.getContent();

        // these will be encoded in <p> tags already, so strip the leading and trailing ones.
        if (description.startsWith('<p>')) {
            description = description.substring(3);
        }
        if (description.endsWith('</p>')) {
            description = description.substring(0, description.length - 4);
        }

        var row = this.#interestsTable.getRow(this.#editInterestName);
        row.getCell("description").setValue(description);
        row.getCell("interest").setValue(this.#iName.value);
        row.getCell("notifyList").setValue(this.#iNotify.value);
        this.#editInterestModal.hide();
        this.dataChanged();
    }

    // save - save the interests entries back to the database
    save() {
        var _this = this;

        if (this.#interestsTable != null) {
            var invalids = this.#interestsTable.validate();
            if (!invalids === true) {
                console.log(invalids);
                show_message("Interests Table does not pass validation, please check for empty cells or cells in red", 'error');
                return false;
            }

            this.#interestsSaveBtn.innerHTML = "Saving...";
            this.#interestsSaveBtn.disabled = true;

            var script = "scripts/regadmin_updateConfigTables.php";

            var postdata = {
                ajax_request_action: 'interests',
                tabledata: JSON.stringify(this.#interestsTable.getData()),
                tablename: "interests",
                indexcol: "interest"
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
                        _this.#interestsSaveBtn.disabled = false;
                        _this.#interestsSaveBtn.innerHTML = "Save Changes*";
                        return false;
                    }
                    interests.close();
                    interests.open();
                    show_message(data['success'], 'success');
                },
                error: function (jqXHR, textStatus, errorThrown) {
                    showError("ERROR in " + script + ": " + textStatus, jqXHR);
                    return false;
                }
            });
        }
    }


    // save off the csv file
    csv() {
        if (this.#interestsTable == null)
            return;

        var filename = 'interests';
        var tabledata = JSON.stringify(this.#interestsTable.getData("active"));
        var fieldList = [
            'interest',
            'description',
            'notifyList',
            'csv',
            'active',
            'createDate',
            'updateDate',
            'sortOrder'
        ];
        downloadCSVPost(filename, tabledata, null, fieldList);
    }
}

function InterestsdrawPreviewPane() {
    interests.drawPreviewPane();
}