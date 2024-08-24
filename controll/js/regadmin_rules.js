//import { TabulatorFull as Tabulator } from 'tabulator-tables';
// rules class - all edit membership rules functions
class rulesSetup {
    #messageDiv = null;
    #rulesPane = null;
    #rulesTable = null;
    #ruleStepsTable = null;

    // memRules locals
    #rulesDirty = false;
    #rulesSaveBtn = null;
    #rulesUndoBtn = null;
    #rulesRedoBtn = null;
    #rulesAddRowBtn = null;

    // memRulesItems locals
    #rulesItemsDirty = false;
    #rulesItemsSaveBtn = null;
    #rulesItemsUndoBtn = null;
    #rulesItemsRedoBtn = null;
    #rulesItemsAddRowBtn = null;
    #ruleSteps = null;
    #memTypes = null;
    #memCategories = null;
    #memAges = null;
    #memList = null;

    // editing a rule items
    #editRuleModal = null;
    #editRuleTitle = null;
    #editBlock = null
    #editSaveBtn = null;
    #editRuleName = null;
    #editRuleNameDiv = null;
    #ruleDescription = null;
    #rules = null;
    #rName = null;
    #rOptionName = null;
    #rTypeList = null;
    #rCatList = null;
    #rAgeList = null;
    #rMemList = null;
    #ruleStepDiv = null;
    #ruleSimulator = null;

    #ruleItems = null;

    // selection items
    #selectionModal = null;
    #selectionTitle = null;
    #selectionBlock = null;


    #debug = 0;
    #debugVisible = false;

    // globals before open
    constructor() {
        this.#debug = debug;
        if (this.#debug & 2) {
            this.#debugVisible = true;
        }

        this.#messageDiv = document.getElementById('test');
        this.#rulesPane = document.getElementById('rules-pane');

        var id = document.getElementById('editRuleModal');
        if (id) {
            this.#editRuleModal = new bootstrap.Modal(id, {focus: true, backdrop: 'static'});
            this.#editRuleTitle = document.getElementById('editRuleTitle');
            this.#editBlock = document.getElementById('editRuleBlockDiv');
            this.#editSaveBtn = document.getElementById('editRuleSaveBtn');
            this.#editRuleNameDiv = document.getElementById('editRuleName');
            this.#ruleDescription = document.getElementById('ruleDescription');
            this.#rName = document.getElementById('rName');
            this.#rOptionName = document.getElementById('rOptionName');
            this.#rTypeList = document.getElementById('rTypeList');
            this.#rCatList = document.getElementById('rCatList');
            this.#rAgeList = document.getElementById('rAgeList');
            this.#rMemList = document.getElementById('rMemList');
            this.#ruleStepDiv = document.getElementById('ruleStepDiv');

            // start the tinyMCE editors
            tinyMCE.init({
                selector: 'textarea#ruleDescription',
                id: "prompt",
                mode: "exact",
                height: 250,
                min_height: 250,
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
                placeholder: 'Edit the rules description...',
                auto_focus: 'editFieldArea',
            });
        }

        id = document.getElementById('selectionModal');
        if (id) {
            this.#selectionModal = new bootstrap.Modal(id, {focus: true, backdrop: 'static'});
            this.#selectionTitle = document.getElementById('selectionTitle');
            this.#selectionBlock = document.getElementById('selectionBlockDiv');
        }
    };


    // called on open of the custom text window
    open() {
        var html = `
    <div class="container-fluid">
        <div class="row">
            <div class="col-sm-12">
                <h4><strong>Edit Membership Rules:</strong></h4>
            </div>
        </div>
        <div class="row">
            <div class="col-sm-12 p-0 m-0" id="rulesTableDiv"></div>
        </div>
        <div class="row mt-2">
            <div class="col-sm-auto" id="rules-buttons">
                <button id="rules-undo" type="button" class="btn btn-secondary btn-sm" onclick="rules.undo(); return false;" disabled>Undo</button>
                <button id="rules-redo" type="button" class="btn btn-secondary btn-sm" onclick="rules.redo(); return false;" disabled>Redo</button>
                <button id="rules-addrow" type="button" class="btn btn-secondary btn-sm" onclick="rules.addrow(); return false;">Add New</button>
                <button id="rules-save" type="button" class="btn btn-primary btn-sm"  onclick="rules.save(); return false;" disabled>Save Changes</button>
            </div>
        </div>
        <div class="row mt-4">
            <div class="col-sm-12"><h4><strong>Rules Sumulator:</strong></h4></div>
        </div>
    </div>
    <div class="container-fluid" id="ruleSimulatorDiv"></div>
    <div class="container-fluid">
        <div class="row">
            <div class="col-sm-12" id="rulesMessageDiv"></div>
        </div>
    </div>
`;
        this.#rulesPane.innerHTML = html;
        this.#rules = null;
        var _this = this;
        var script = "scripts/regadmin_getConfigTables.php";
        var postdata = {
            ajax_request_action: 'rules',
            tablename: "rules",
            indexcol: "rule"
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
        this.#rulesPane.innerHTML = html;
    }

    // draw the rules edit screen
    draw(data, textStatus, jhXHR) {
        var _this = this;

        if (this.#rulesTable != null) {
            this.#rulesTable.off("dataChanged");
            this.#rulesTable.off("cellEdited");
            this.#rulesTable.destroy();
            this.#rulesTable = null;
        }
        if (!data['rules']) {
            show_message("Error loading rules", 'error');
            return;
        }
        this.#rules = data['rules'];
        this.#ruleItems = data['ruleItems'];
        this.#memTypes = data['memTypes'];
        this.#memCategories = data['memCategories'];
        this.#memAges = data['memAges'];
        this.#memList = data['memList'];

        this.#rulesDirty = false;
        this.#rulesTable = new Tabulator('#rulesTableDiv', {
            history: true,
            data: this.#rules,
            layout: "fitDataTable",
            index: "name",
            pagination: true,
            paginationAddRow:"table",
            paginationSize: 10,
            paginationSizeSelector: [10, 25, 50, 100, 250, true], //enable page size select element with these options
            columns: [
                {title: "Name", field: "name", width: 200, headerSort: true, headerFilter: true, validator: "required", },
                {title: "Option Name", field: "optionName", headerWordWrap: true, width: 200, headerSort: true, headerFilter: true, validator: "required", },
                {title: "Description", field: "description", headerSort: false, width: 600, headerFilter: true, validator: "required", },
                {title: "typeList", field: "typeList", headerSort: false, headerFilter: true, width: 200, },
                {title: "catList", field: "catList", headerSort: false, headerFilter: true, width: 200, },
                {title: "ageList", field: "ageList", headerSort: false, headerFilter: true, width: 200, },
                {title: "memList", field: "memList", headerSort: false, headerFilter: true, width: 200, },
                {title: "Edit", formatter: this.editbutton, formatterParams: {table: 'rules', label: 'Edit Rule' }, hozAlign:"left", headerSort: false },
                {title: "Orig Key", field: "rulesKey", visible: this.#debugVisible, headerFilter: false, headerWordWrap: true, width: 200,},
                {
                    title: "Delete", field: "uses", formatter: deleteicon, hozAlign: "center", headerSort: false,
                    cellClick: function (e, cell) {
                        deleterow(e, cell.getRow());
                    }
                },
                {title: "To Del", field: "to_delete", visible: this.#debugVisible,}
            ],
        });
        this.#rulesTable.on("dataChanged", function (data) {
            _this.dataChanged();
        });
        this.#rulesTable.on("cellEdited", cellChanged);

        this.#rulesUndoBtn = document.getElementById('rules-undo');
        this.#rulesRedoBtn = document.getElementById('rules-redo');
        this.#rulesAddRowBtn = document.getElementById('rules-addrow');
        this.#rulesSaveBtn = document.getElementById('rules-save');


        setTimeout(rulesDrawPreviewPane, 100);
    }

    drawPreviewPane() {
        this.#ruleSimulator = document.getElementById('ruleSimulatorDiv');
        this.#ruleSimulator.innerHTML = '';
    }

    // table related functions
    // display edit button for a long field
    editbutton(cell, formatterParams, onRendered) {
        var ruleName = cell.getRow().getIndex()
        var table = formatterParams.table;
        if (ruleName != '') {
            switch(table) {
                case 'rules':
                return '<button class="btn btn-secondary" style = "--bs-btn-padding-y: .0rem; --bs-btn-padding-x: .3rem; --bs-btn-font-size: .75rem;",' +
                    ' onclick="rules.editRule(\'rules\',\'' + ruleName + '\');">Edit Rule</button>';
                case 'ruleItems':
                    return '<button class="btn btn-secondary" style = "--bs-btn-padding-y: .0rem; --bs-btn-padding-x: .3rem; --bs-btn-font-size: .75rem;",' +
                    ' onclick="rules.editStep(\'ruleItems\',\'' + ruleName + '\');">Edit Step</button>';
            }
        }
        return "Save First";
    }

    // edit step - display a modal to edit a step
    editStep(type, item) {
        show_message("NOT YET", 'warn', 'result_message_editRule');
    }

    // editTypes - select the types list for this rule
    editTypes(table) {
        switch (table) {
            case 'r':
                var values = ',' + this.#rTypeList.innerHTML + ',';
                var data = this.#memTypes;
                this.drawSelection('Rule xxx memTypes', data, values);
                break;
            case 'i':
                break;
        }
    }

    // drawSelection
    drawSelection(title, data, values) {
        this.#selectionTitle.innerHTML = title;



        this.#selectionModal.show();
    }

    // add row to  table and scroll to that new row
    addrow() {
        var _this = this;
        this.#rulesTable.addRow({rules: 'new-row', notifyList: '', desccription: '', csv: 'N',
            active: 'Y', sortorder: 99, uses: 0}, false).then(function (row) {
            _this.#rulesTable.setPage("last"); // adding new to last page always
            row.getTable().scrollToRow(row);
            _this.checkUndoRedo();
        });
    }

    dataChanged() {
        //data - the updated table data
        if (!this.#rulesDirty) {
            this.#rulesSaveBtn.innerHTML = "Save Changes*";
            this.#rulesSaveBtn.disabled = false;
            this.#rulesDirty = true;
        }
        this.checkUndoRedo();
        this.drawPreviewPane();
    };
    
    undo() {
        if (this.#rulesTable != null) {
            this.#rulesTable.undo();

            if (this.checkUndoRedo() <= 0) {
                this.#rulesDirty = false;
                this.#rulesSaveBtn.innerHTML = "Save Changes";
                this.#rulesSaveBtn.disabled = true;
            }
        }
    };

    redo() {
        if (this.#rulesTable != null) {
            this.#rulesTable.redo();

            if (this.checkUndoRedo() > 0) {
                this.#rulesDirty = true;
                this.#rulesSaveBtn.innerHTML = "Save Changes*";
                this.#rulesSaveBtn.disabled = false;
            }
        }
    };

    // set undo / redo status for buttons
    checkUndoRedo() {
        var undosize = this.#rulesTable.getHistoryUndoSize();
        this.#rulesUndoBtn.disabled = undosize <= 0;
        this.#rulesRedoBtn.disabled = this.#rulesTable.getHistoryRedoSize() <= 0;
        return undosize;
    }

    // open the previewEdit modal and populate it with the stuff for this entry and it's save back
    editRule(table, ruleName) {
        //console.log(table);
        //console.log(ruleName);
        this.#editRuleName = ruleName;
        var ruleRow = this.#rulesTable.getRow(ruleName).getData();
        editPreviewClass = 'rules';
        var ruleName = ruleRow.name;
        var ruleDescription = ruleRow.description;

        this.#ruleSteps = [];
        for (var i = 0; i < this.#ruleItems.length; i++) {
            if (ruleName == this.#ruleItems[i].name) {
                this.#ruleSteps.push(this.#ruleItems[i]);
            }
        }

        // build the modal contents
        this.#editRuleTitle.innerHTML = "Edit the " + ruleName + " rule";
        this.#editRuleNameDiv.innerHTML = ruleName;
        this.#editRuleNameDiv.innerHTML = ruleName;
        this.#ruleDescription.innerHTML = ruleDescription;
        this.#rName.value = ruleRow.name
        this.#rOptionName.value = ruleRow.optionName;
        this.#rTypeList.innerHTML = ruleRow.typeList = '' ? "<i>None</i>" : ruleRow.typeList;
        this.#rCatList.innerHTML = ruleRow.catList = '' ? "<i>None</i>" : ruleRow.catList;
        this.#rAgeList.innerHTML = ruleRow.ageList = '' ? "<i>None</i>" : ruleRow.ageList;
        this.#rMemList.innerHTML = ruleRow.memList = '' ? "<i>None</i>" : ruleRow.memList;

        tinyMCE.activeEditor.setContent(ruleDescription);
        this.#ruleStepsTable = new Tabulator('#ruleStepDiv', {
            history: true,
            data: this.#ruleSteps,
            layout: "fitDataTable",
            index: "rownum",
            columns: [
                {title: "rownum", field: "rownum", visible: this.#debugVisible,},
                {title: "Name", field: "name", width: 200, validator: "required", },
                {title: "Step", field: "step", width: 70, headerHozAlign:"right", hozAlign: "right", headerSort: false, validator: "required", },
                {title: "Rule Type", field: "ruleType", headerWordWrap: true, width: 100, headerSort: false, validator: "required", },
                {title: "Apply To", field: "applyTo", width: 100, validator: "required", },
                {title: "typeList", field: "typeList", width: 300, },
                {title: "catList", field: "catList", width: 300, },
                {title: "ageList", field: "ageList", width: 300, },
                {title: "memList", field: "memList", width: 300, },
                {title: "Edit", formatter: this.editbutton, formatterParams: {table: 'ruleItems', label: 'Edit Step' }, hozAlign:"left", headerSort: false },
                {title: "Orig Key", field: "rulesKey", visible: this.#debugVisible, headerFilter: false, headerWordWrap: true, width: 200,},
                {
                    title: "Delete", field: "uses", formatter: deleteicon, hozAlign: "center", headerSort: false,
                    cellClick: function (e, cell) {
                        deleterow(e, cell.getRow());
                    }
                },
                {title: "To Del", field: "to_delete", visible: this.#debugVisible,}
            ],
        });
        this.#rulesTable.on("dataChanged", function (data) {
            _this.dataChanged();
        });
        this.#rulesTable.on("cellEdited", cellChanged);

        this.#rulesUndoBtn = document.getElementById('rules-undo');
        this.#rulesRedoBtn = document.getElementById('rules-redo');
        this.#rulesAddRowBtn = document.getElementById('rules-addrow');
        this.#rulesSaveBtn = document.getElementById('rules-save');
        this.#editRuleModal.show();
    }

    // process the save button on the edit modal
    editRuleSave() {
        var description = tinyMCE.activeEditor.getContent();

        /*
        // these will be encoded in <p> tags already, so strip the leading and trailing ones.
        if (description.startsWith('<p>')) {
            description = description.substring(3);
        }
        if (description.endsWith('</p>')) {
            description = description.substring(0, description.length - 4);
        }
        */

        var row = this.#rulesTable.getRow(this.#editRuleName);
        row.getCell("description").setValue(description);
        /*row.getCell("interest").setValue(this.#iName.value);
        row.getCell("notifyList").setValue(this.#iNotify.value);*/
        if (this.#ruleStepsTable != null) {
            this.#ruleStepsTable.off("dataChanged");
            this.#ruleStepsTable.off("cellEdited");
            this.#ruleStepsTable.destroy();
            this.#ruleStepsTable = null;
        }
        this.#editRuleModal.hide();
    }
    
    // on close of the pane, clean up the items
    close() {
        if (this.#rulesTable) {
            this.#rulesTable.destroy();
            this.#rulesTable = null;
        }

        this.#rulesPane.innerHTML = '';
    };
}

function rulesDrawPreviewPane() {
    rules.drawPreviewPane();
}