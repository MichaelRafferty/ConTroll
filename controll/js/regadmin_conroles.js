//import { TabulatorFull as Tabulator } from 'tabulator-tables';
// conroles class - all edit convention roles functions
conRolesDescriptionMCEInit = false;
class ConRolesSetup {
    #messageDiv = null;
    #conRolesPane = null;
    #conRolesTable = null;

    #conRoles = null;
    #conRolesDirty = false;
    #conRolesSaveBtn = null;
    #conRolesUndoBtn = null;
    #conRolesRedoBtn = null;
    #conRolesAddRowBtn = null;
    #conRolesPagination = false;
    #dirty = false;

    // edit items
    #editConroleModal = null;
    #editConroleTitle = null;
    #editBlock = null
    #editSaveBtn = null;
    #editConroleName = null;
    #editConroleNameDiv = null;
    #conroleDescription = null;
    #cName = null;
    #cMemLabel = null;

    #debug = 0;
    #debugVisible = false;

    // globals before open
    constructor() {
        this.#debug = debug;
        if (this.#debug & 2) {
            this.#debugVisible = true;
        }

        this.#messageDiv = document.getElementById('test');
        this.#conRolesPane = document.getElementById('conroles-pane');

        let id = document.getElementById('editConrolesModal');
        if (id) {
            this.#editConroleModal = new bootstrap.Modal(id, {focus: true, backdrop: 'static'});
            this.#editConroleTitle = document.getElementById('editConrolesTitle');
            this.#editBlock = document.getElementById('editBlockDiv');
            this.#editSaveBtn = document.getElementById('editConroleSaveBtn');
            this.#editConroleNameDiv = document.getElementById('editConroleName');
            this.#conroleDescription = document.getElementById('conroleDescription');
            this.#cName = document.getElementById('cName');
            this.#cMemLabel = document.getElementById('cMemlabel');
            if (conRolesDescriptionMCEInit) {
                tinyMCE.get("conroleDescription").focus();
                tinyMCE.get("conroleDescription").load();
            } else {
                // start the tinyMCE editors
                tinyMCE.init({
                    selector: 'textarea#conroleDescription',
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
                    link_default_target: '_blank',
                    content_style: 'body {font - family:Helvetica,Arial,sans-serif; font-size:14px }',
                    placeholder: 'Edit the conroles prompt...',
                    auto_focus: 'editFieldArea',
                });
                // Prevent Bootstrap dialog from blocking focusin
                document.addEventListener('focusin', (e) => {
                    if (e.target.closest(".tox-tinymce, .tox-tinymce-aux, .moxman-window, .tam-assetmanager-root") !== null) {
                        e.stopImmediatePropagation();
                    }
                });
                conRolesDescriptionMCEInit = true;
            }
        }
    };

    // called on open of the conroles window
    open() {
        let html = `
    <div class="container-fluid">
        <div class="row">
            <div class="col-sm-12">
                <h4><strong>Convention Roles Setup Tables:</strong></h4>
            </div>
        </div>
        <div class="row">
            <div class="col-sm-12 p-0 m-0" id="conrolesTableDiv"></div>
        </div>
        <div class="row mt-2">
            <div class="col-sm-auto" id="types-buttons">
                <button id="conroles-undo" type="button" class="btn btn-secondary btn-sm" onclick="conroles.undo(); return false;" disabled>Undo</button>
                <button id="conroles-redo" type="button" class="btn btn-secondary btn-sm" onclick="conroles.redo(); return false;" disabled>Redo</button>
                <button id="conroles-addrow" type="button" class="btn btn-secondary btn-sm" onclick="conroles.addrow(); return false;">Add New</button>
                <button id="conroles-save" type="button" class="btn btn-primary btn-sm"  onclick="conroles.save(); return false;" disabled>Save Changes</button>
                <button id="conroles-csv" type="button" class="btn btn-info btn-sm"  onclick="conroles.download('csv'); return false;">Download CSV</button>
                <button id="conroles-xlsx" type="button" class="btn btn-info btn-sm"  onclick="conroles.download('xlsx'); return false;">Download Excel</button>
            </div>
        </div>
    </div>`;
        this.#conRolesPane.innerHTML = html;
        this.#conRoles = null;
        let _this = this;
        let script = "scripts/regadmin_getConfigTables.php";
        let postdata = {
            ajax_request_action: 'conroles',
            tablename: "conroles",
            indexcol: "conRole"
        };
        clear_message();
        $.ajax({
            url: script,
            method: 'POST',
            data: postdata,
            success: function (data, textStatus, jhXHR) {
                checkRefresh(data);
                _this.draw(data, textStatus, jhXHR);
            },
            error: function (jqXHR, textStatus, errorThrown) {
                showError("ERROR in " + script + ": " + textStatus, jqXHR);
                return false;
            }
        });
    }

    // draw the conroles edit screen
    draw(data, textStatus, jhXHR) {
        let _this = this;

        if (this.#conRolesTable != null) {
            this.#conRolesTable.off("dataChanged");
            this.#conRolesTable.off("rowMoved")
            this.#conRolesTable.off("cellEdited");
            this.#conRolesTable.destroy();
            this.#conRolesTable = null;
        }
        if (!data['conroles']) {
            show_message("Error loading conroles", 'error');
            return;
        }
        this.#conRoles = data['conroles'];
        this.#conRolesDirty = false;
        this.#conRolesPagination = this.#conRoles.length > 25,
        this.#conRolesTable = new Tabulator('#conrolesTableDiv', {
            history: true,
            movableRows: true,
            data: this.#conRoles,
            layout: "fitDataTable",
            index: "conRole",
            pagination: this.#conRolesPagination,
            paginationAddRow:"table",
            paginationSize: 10,
            paginationSizeSelector: [10, 25, 50, 100, 250, true], //enable page size select element with these options
            columns: [
                {rowHandle: true, formatter: "handle", frozen: true, width: 30, minWidth: 30, maxWidth: 30, headerSort: false, },
                {title: "Edit", formatter: this.editbutton, formatterParams: {table: 'conroles' }, hozAlign:"left", headerSort: false },
                {title: "Con Role", field: "conRole", width: 200, headerSort: true},
                {title: "Description", field: "description", headerSort: false, width: 600, headerFilter: true, validator: "required", formatter: this.toHTML, },
                {title: "Label", field: "memLabel", headerSort: false, width: 200, headerFilter: true, },
                {
                    title: "Active", field: "active", headerWordWrap: true, headerSort: true,
                    editor: "list", editorParams: { values: ["Y", "N"], }, width: 70, validator: "required"
                },
                {title: "Sort Order", field: "sortOrder", visible: this.#debugVisible, headerFilter: false, headerWordWrap: true, width: 80,},
                {title: "Orig Key", field: "conrolesKey", visible: this.#debugVisible, headerFilter: false, headerWordWrap: true, width: 200,},
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
        this.#conRolesTable.on("dataChanged", function (data) {
            _this.dataChanged();
        });
        this.#conRolesTable.on("rowMoved", function (row) {
            _this.rowMoved(row)
        });
        this.#conRolesTable.on("cellEdited", cellChanged);

        this.#conRolesUndoBtn = document.getElementById('conroles-undo');
        this.#conRolesRedoBtn = document.getElementById('conroles-redo');
        this.#conRolesAddRowBtn = document.getElementById('conroles-addrow');
        this.#conRolesSaveBtn = document.getElementById('conroles-save');
    }

    // table related functions
    // display edit button for a long field
    editbutton(cell, formatterParams, onRendered) {
        let conroleName = cell.getRow().getIndex()
        if (conroleName != '') {
            return '<button class="btn btn-secondary" style = "--bs-btn-padding-y: .0rem; --bs-btn-padding-x: .3rem; --bs-btn-font-size: .75rem;",' +
                ' onclick="conroles.editConrole(\'conroles\',\'' + conroleName + '\');">Edit Con Role</button>';
        }
        return "Save First";
    }

    toHTML(cell,  formatterParams, onRendered) {
        let item = cell.getValue();
        cell.getElement().style.whiteSpace = "normal";
        return item;
    }

    toDateFromDays(cell,  formatterParams, onRendered) {
        let days = cell.getValue();
        if (days === null || days === undefined)
            days = 0;
        if (days == 0)
            return '';

        let endDate = new Date(config.conStartDate);
        endDate.setTime(endDate.getTime() + Number(days) * 1000 * 24 * 3600);
        return endDate.toISOString().split('T')[0];
    }

    // add row to  table and scroll to that new row
    addrow() {
        let _this = this;
        this.#conRolesTable.clearFilter(true);
        this.#conRolesTable.addRow({conRole: 'new-row', description: '', memLabel: '',
            active: 'Y', sortOrder: 99, uses: 0}, false).then(function (row) {
            if (_this.#conRolesPagination) {
                _this.#conRolesTable.setPage("last"); // adding new to last page always
                row.getTable().setPageToRow(row).then(function () {
                    setCellChanged(row.getCell("conRole"));
                    setCellChanged(row.getCell("description"));
                    setCellChanged(row.getCell("memLabel"));
                    setCellChanged(row.getCell("active"));
                });
            } else {
                setCellChanged(row.getCell("conRole"));
                setCellChanged(row.getCell("description"));
                setCellChanged(row.getCell("memLabel"));
                setCellChanged(row.getCell("active"));
            }
            _this.checkUndoRedo();
            });
    }

    dataChanged() {
        //data - the updated table data
        if (!this.#dirty) {
            this.#conRolesSaveBtn.innerHTML = "Save Changes*";
            this.#conRolesSaveBtn.disabled = false;
            this.#dirty = true;
        }
        this.checkUndoRedo();
    };

    rowMoved(row) {
        this.#conRolesSaveBtn.innerHTML = "Save Changes*";
        this.#conRolesSaveBtn.disabled = false;
        this.#dirty = true;
        this.checkUndoRedo();
    }

    undo() {
        if (this.#conRolesTable != null) {
            this.#conRolesTable.undo();

            if (this.checkUndoRedo() <= 0) {
                this.#dirty = false;
                this.#conRolesSaveBtn.innerHTML = "Save Changes";
                this.#conRolesSaveBtn.disabled = true;
            }
        }
    };

    redo() {
        if (this.#conRolesTable != null) {
            this.#conRolesTable.redo();

            if (this.checkUndoRedo() > 0) {
                this.#dirty = true;
                this.#conRolesSaveBtn.innerHTML = "Save Changes*";
                this.#conRolesSaveBtn.disabled = false;
            }
        }
    };

    // set undo / redo status for buttons
    checkUndoRedo() {
        let undosize = this.#conRolesTable.getHistoryUndoSize();
        this.#conRolesUndoBtn.disabled = undosize <= 0;
        this.#conRolesRedoBtn.disabled = this.#conRolesTable.getHistoryRedoSize() <= 0;
        return undosize;
    }

    toggleConrole(id) {
        let checked = document.getElementById('i_' + id).checked;
        let notesId = document.getElementById("i_p_" + id);
        if (notesId)
            notesId.hidden = !checked;
    }

    // open the previewEdit modal and populate it with the stuff for this entry and it's save back
    editConrole(table, conroleName) {
        //console.log(table);
        //console.log(conroleName);
        this.#editConroleName = conroleName;
        let conroleRow = this.#conRolesTable.getRow(conroleName).getData();
        editPreviewClass = 'conroles';
        let conroleDescription = '';
        if (conroleRow.hasOwnProperty('description')) {
            if (conroleRow.description != null && conroleRow.description != undefined)
                conroleDescription = conroleRow.description;
        }

        // build the modal contents
        this.#editConroleTitle.innerHTML = "Edit the " + conroleName + " convention role";
        this.#editConroleNameDiv.innerHTML = conroleName;
        this.#conroleDescription.value = conroleDescription;
        tinyMCE.activeEditor.setContent(conroleDescription);
        this.#cName.value = conroleRow.conRole;
        this.#cMemLabel.value = conroleRow.memLabel;
        this.#editConroleModal.show();
    }

    // on close of the pane, clean up the items
    close() {
            // on close of the pane, clean up the items
            if (this.#conRolesTable != null) {
                this.#conRolesTable.off("dataChanged");
                this.#conRolesTable.off("rowMoved")
                this.#conRolesTable.off("cellEdited");
                this.#conRolesTable.destroy();
                this.#conRolesTable = null;
            }
            
        this.#conRolesPane.innerHTML = '';
    };

    // process the save button on the edit modal
    editConroleSave() {
        let description = tinyMCE.activeEditor.getContent();

        // these will be encoded in <p> tags already, so strip the leading and trailing ones.
        if (description.startsWith('<p>')) {
            description = description.substring(3);
        }
        if (description.endsWith('</p>')) {
            description = description.substring(0, description.length - 4);
        }

        let row = this.#conRolesTable.getRow(this.#editConroleName);
        row.getCell("description").setValue(description);
        row.getCell("conRole").setValue(this.#cName.value);
        row.getCell("memLabel").setValue(this.#cMemLabel.value);
        this.#editConroleModal.hide();
        this.dataChanged();
    }

    // save - save the conroles entries back to the database
    save() {
        let _this = this;

        if (this.#conRolesTable != null) {
            let invalids = this.#conRolesTable.validate();
            if (!invalids === true) {
                console.log(invalids);
                show_message("Conroles Table does not pass validation, please check for empty cells or cells in red", 'error');
                return false;
            }

            this.#conRolesSaveBtn.innerHTML = "Saving...";
            this.#conRolesSaveBtn.disabled = true;

            let script = "scripts/regadmin_updateConfigTables.php";

            let postdata = {
                ajax_request_action: 'conroles',
                tabledata: JSON.stringify(this.#conRolesTable.getData()),
                tablename: "conroles",
                indexcol: "conrole"
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
                        _this.#conRolesSaveBtn.disabled = false;
                        _this.#conRolesSaveBtn.innerHTML = "Save Changes*";
                        return false;
                    }
                    checkRefresh(data);
                    conroles.close();
                    conroles.open();
                    show_message(data['success'], 'success');
                },
                error: function (jqXHR, textStatus, errorThrown) {
                    showError("ERROR in " + script + ": " + textStatus, jqXHR);
                    return false;
                }
            });
        }
    }


    // save off the data file
    download(format) {
        if (this.#conRolesTable == null)
            return;

        let filename = 'conroles';
        let tabledata = JSON.stringify(this.#conRolesTable.getData("active"));
        let fieldList = [
            'conrole',
            'description',
            'notesPrompt',
            'endDate',
            'notifyList',
            'csv',
            'active',
            'createDate',
            'updateDate',
            'sortOrder'
        ];
        downloadFilePost(format, filename, tabledata, null, fieldList);
    }
}
