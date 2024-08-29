//import { TabulatorFull as Tabulator } from 'tabulator-tables';
// rules class - all edit membership rules functions

var ageList = null;
var memTypes = null;
var memCategories = null;
var memList = null;
var memRules = null;

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
    #ruleSteps = null;
    #memTypes = null;
    #memCategories = null;
    #memAges = null;
    #memList = null;
    #ruleStepsDirty = null;
    #ruleStepsSaveBtn = null;
    #ruleStepsUndoBtn = null;
    #ruleStepsRedoBtn = null;
    #ruleStepsAddRowBtn = null;

    // editing a rule
    #editRuleModal = null;
    #editRuleTitle = null;
    #editRuleBlock = null
    #editRuleSel = null
    #editRuleSaveBtn = null;
    #editRuleName = null;
    #editRuleSelLabel = null;
    #editRuleNameDiv = null;
    #ruleDescription = null;
    #rules = null;
    #rulesIdx = null;
    #rName = null;
    #rOptionName = null;
    #rTypeList = null;
    #rCatList = null;
    #rAgeList = null;
    #rMemList = null;
    #ruleStepDiv = null;
    #ruleSimulator = null;

    // editing a ruleItem (step)
    #editRuleStepModal = null;
    #editRuleStepTitle = null;
    #editRuleStepBlock = null
    #editRuleStepSel = null
    #editRuleStepSaveBtn = null;
    #editRuleStepItem = null;
    #editRuleStepSelLabel = null;
    #editRuleStepNameDiv = null;
    #sName = null;
    #sStep = null;
    #sRuleType = null;
    #sApplyTo = null;
    #sTypeList = null;
    #sCatList = null;
    #sAgeList = null;
    #sMemList = null;
    #ruleItems = null;
    #ruleItemsIdx = null;

    #editRuleSelTable = null;
    #selIndex = null;
    #selField = null;
    #selValues = null;
    #filterTypes = [];
    #filterAges = []
    #filterCats = [];


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
            this.#editRuleBlock = document.getElementById('editRuleBlockDiv');
            this.#editRuleSel = document.getElementById('editRuleSelDiv');
            this.#editRuleSelLabel = document.getElementById('editRuleSelLabel');
            this.#editRuleSaveBtn = document.getElementById('editRuleSaveBtn');
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

        var id = document.getElementById('editRuleStepModal');
        if (id) {
            this.#editRuleStepModal = new bootstrap.Modal(id, {focus: true, backdrop: 'static'});
            this.#editRuleStepTitle = document.getElementById('editRuleStepTitle');
            this.#editRuleStepBlock = document.getElementById('editRuleStepBlockDiv');
            this.#editRuleStepSel = document.getElementById('editRuleStepSelDiv');
            this.#editRuleStepSelLabel = document.getElementById('editRuleStepSelLabel');
            this.#editRuleStepSaveBtn = document.getElementById('editRuleStepSaveBtn');
            this.#editRuleStepNameDiv = document.getElementById('editRuleStepName');
            this.#ruleDescription = document.getElementById('ruleDescription');
            this.#sName = document.getElementById('sName');
            this.#sStep = document.getElementById('sStep');
            this.#sRuleType = document.getElementById('sRuleType');
            this.#sApplyTo = document.getElementById('sApplyTo');
            this.#sTypeList = document.getElementById('sTypeList');
            this.#sCatList = document.getElementById('sCatList');
            this.#sAgeList = document.getElementById('sAgeList');
            this.#sMemList = document.getElementById('sMemList');
            this.#ruleStepDiv = document.getElementById('ruleStepDiv');
            this.#ruleStepsSaveBtn = document.getElementById('editRuleSaveBtn');
            this.#ruleStepsUndoBtn = document.getElementById('steps-undo');
            this.#ruleStepsRedoBtn = document.getElementById('steps-redo');
            this.#ruleStepsAddRowBtn = document.getElementById('steps-addrow');
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
        this.closeSelTable('r');
        this.closeSelTable('s');
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
        memTypes = data['memTypes'];
        memCategories = data['memCategories'];
        ageList = data['ageList'];
        memList = data['memList'];

        // create index of rules
        this.#rulesIdx = {};
        for (var i = 0; i < this.#rules.length; i++) {
            var row = this.#rules[i];
            this.#rulesIdx[row.name] = i;
        }
        // create index of ruleItems
        this.#ruleItemsIdx = {};
        for (var i = 0; i < this.#ruleItems.length; i++) {
            var row = this.#ruleItems[i];
            this.#ruleItemsIdx[row.rownum] = i;
        }

        this.#filterAges = [];
        this.#filterCats = [];
        this.#filterTypes = [];

        // load the filter arrays
        for (var row of memTypes) {
            this.#filterTypes.push(row['memType']);
        }
        for (var row of ageList) {
            this.#filterAges.push(row['ageType']);
        }
        for (var row of memCategories) {
            this.#filterCats.push(row['memCategory']);
        }

        this.#rulesDirty = false;
        this.#rulesTable = new Tabulator('#rulesTableDiv', {
            history: true,
            data: this.#rules,
            layout: "fitDataTable",
            index: "origName",
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
                {title: "Orig Key", field: "origName", visible: this.#debugVisible, headerFilter: false, headerWordWrap: true, width: 200,},
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
            rulesDataChanged();
        });
        this.#rulesTable.on("cellEdited", cellChanged);

        this.#rulesUndoBtn = document.getElementById('rules-undo');
        this.#rulesRedoBtn = document.getElementById('rules-redo');
        this.#rulesAddRowBtn = document.getElementById('rules-addrow');
        this.#rulesSaveBtn = document.getElementById('rules-save');
        this.closeSelTable('r');

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
        // populate the modal
        console.log("type = '" + type + "', item = '" + item + "'");
        var row = this.#ruleStepsTable.getRow(item);
        this.#editRuleStepItem = item;
        this.#sName.value = row.getCell('name').getValue();
        this.#sStep.value = row.getCell('step').getValue();
        this.#sRuleType.value = row.getCell('ruleType').getValue();
        this.#sApplyTo.value = row.getCell('applyTo').getValue();
        this.#sTypeList.innerHTML = row.getCell('typeList').getValue();
        this.#sCatList.innerHTML = row.getCell('catList').getValue();
        this.#sAgeList.innerHTML = row.getCell('ageList').getValue();
        this.#sMemList.innerHTML = row.getCell('memList').getValue();
        this.#editRuleModal.hide();
        this.#editRuleStepModal.show();
        $('#editRuleStepSelButtons').hide();
        this.#editRuleStepSelLabel.innerHTML = '';
        this.#selIndex = null;
    }

    editRuleStepSave(dosave) {
        // save the results back to the underlying table
        if (dosave) {
            // store all the fields back into the table row
            var row = this.#ruleStepsTable.getRow(this.#editRuleStepItem);

            var newValue = this.#sName.value;
            if (row.getCell("name").getValue() != newValue) {
                row.getCell("name").setValue(newValue);
            }
            newValue = this.#sStep.value;
            if (row.getCell("step").getValue() != newValue) {
                row.getCell("step").setValue(newValue);
            }
            newValue = this.#sRuleType.value;
            if (newValue == '') {
                show_message('You must select a rule type', 'error', 'result_message_editRuleStep');
                return;
            }
            if (row.getCell("ruleType").getValue() != newValue) {
                row.getCell("ruleType").setValue(newValue);
            }
            newValue = this.#sApplyTo.value;
            if (newValue == '') {
                show_message('You must select an Apply To', 'error', 'result_message_editRuleStep');
                return;
            }
            if (row.getCell("applyTo").getValue() != newValue) {
                row.getCell("applyTo").setValue(newValue);
            }
            newValue = this.#sAgeList.innerHTML;
            if (newValue == '')
                newValue = null;
            if (row.getCell("ageList").getValue() != newValue) {
                row.getCell("ageList").setValue(newValue);
            }
            newValue = this.#sTypeList.innerHTML;
            if (newValue == '')
                newValue = null;
            if (row.getCell("typeList").getValue() != newValue) {
                row.getCell("typeList").setValue(newValue);
            }
            newValue = this.#sCatList.innerHTML;
            if (newValue == '')
                newValue = null;
            if (row.getCell("catList").getValue() != newValue) {
                row.getCell("catList").setValue(newValue);
            }
            newValue = this.#sMemList.innerHTML;
            if (newValue == '')
                newValue = null;
            if (row.getCell("memList").getValue() != newValue) {
                row.getCell("memList").setValue(newValue);
            }

        }
        this.#editRuleStepModal.hide();
        this.#editRuleModal.show();
    }

    closeSelTable(level) {
        if (this.#editRuleSelTable) {
            this.#editRuleSelTable.destroy();
            this.#editRuleSelTable = null;
        }
        switch (level) {
            case 'r':
                $('#editRuleSelButtons').hide();
                this.#editRuleSelLabel.innerHTML = '';
                break;
            case 's':
                $('#editRuleStepSelButtons').hide();
                this.#editRuleStepSelLabel.innerHTML = '';
                break;
        }
        this.#selIndex = null;
    }

    // editTypes - select the types list for this rule
    editTypes(level) {
        this.closeSelTable(level);
        var tableField = null;
        switch (level) {
            case 'r':
                this.#selValues = ',' + this.#rTypeList.innerHTML + ',';
                this.#editRuleSelLabel.innerHTML = "<b>Select which Types apply to this rule:</b>"
                tableField = '#editRuleSelTable';
                this.#selField = this.#rTypeList;
                $('#editRuleSelButtons').show();
                break;
            case 's':
                this.#selValues = ',' + this.#sTypeList.innerHTML + ',';
                this.#editRuleStepSelLabel.innerHTML = "<b>Select which Types apply to this step:</b>"
                tableField = '#editRuleStepSelTable';
                this.#selField = this.#sTypeList;
                $('#editRuleStepSelButtons').show();
                break;
        }

        this.#editRuleSelTable = new Tabulator(tableField, {
            data: memTypes,
            layout: "fitDataTable",
            index: "memType",
            columns: [
                {title: "Type", field: "memType", width: 200, },
                {title: "Notes", field: "notes", width: 750, headerFilter: true, },
            ],
        });
        this.#editRuleSelTable.on("cellClick", rules.clickedSelection)
        this.#selIndex = 'memType';
        setTimeout(rulesSetInitialSel, 100);
    }

    // editCategories - select the category list for this rule
    editCategories(level) {
        this.closeSelTable(level);
        var tableField = null;
        switch (level) {
            case 'r':
                this.#selValues = ',' + this.#rCatList.innerHTML + ',';
                this.#editRuleSelLabel.innerHTML = "<b>Select which Categories apply to this rule:</b>"
                tableField = '#editRuleSelTable';
                this.#selField = this.#rCatList;
                $('#editRuleSelButtons').show();
                break;
            case 's':
                this.#selValues = ',' + this.#sCatList.innerHTML + ',';
                this.#editRuleStepSelLabel.innerHTML = "<b>Select which Categories apply to this step:</b>"
                tableField = '#editRuleStepSelTable';
                this.#selField = this.#sCatList;
                $('#editRuleStepSelButtons').show();
                break;
        }

        this.#editRuleSelTable = new Tabulator(tableField, {
            data: memCategories,
            layout: "fitDataTable",
            index: "memCategory",
            columns: [
                {title: "Category", field: "memCategory", width: 200, },
                {title: "Notes", field: "notes", width: 750, headerFilter: true, },
            ],
        });
        this.#editRuleSelTable.on("cellClick", rules.clickedSelection)
        this.#selIndex = 'memCategory';
        setTimeout(rulesSetInitialSel, 100);
    }

    // editAges - select the age list for this rule
    editAges(level) {
        this.closeSelTable(level);
        var tableField = null;
        switch (level) {
            case 'r':
                this.#selValues = ',' + this.#rAgeList.innerHTML + ',';
                this.#editRuleSelLabel.innerHTML = "<b>Select which Ages apply to this rule:</b>"
                tableField = '#editRuleSelTable';
                this.#selField = this.#rAgeList;
                $('#editRuleSelButtons').show();
                break;
            case 's':
                this.#selValues = ',' + this.#sAgeList.innerHTML + ',';
                this.#editRuleStepSelLabel.innerHTML = "<b>Select which Ages apply to this step:</b>"
                tableField = '#editRuleStepSelTable';
                this.#selField = this.#sAgeList;
                $('#editRuleStepSelButtons').show();
                break;
        }

        this.#editRuleSelTable = new Tabulator(tableField, {
            data: ageList,
            layout: "fitDataTable",
            index: "ageType",
            columns: [
                {title: "Age", field: "ageType", width: 200, },
                {title: "Short Name", field: "shortname", width: 200, },
                {title: "Label", field: "label", width: 450, headerFilter: true, },
            ],
        });
        this.#editRuleSelTable.on("cellClick", rules.clickedSelection)
        this.#selIndex = 'ageType';
        setTimeout(rulesSetInitialSel, 100);
    }

    // editMemList - select the mem id list for this rule
    editMemList(level) {
        this.closeSelTable(level);
        var tableField = null;
        switch (level) {
            case 'r':
                this.#selValues = ',' + this.#rMemList.innerHTML + ',';
                this.#editRuleSelLabel.innerHTML = "<b>Select which memId's apply to this rule:</b>"
                tableField = '#editRuleSelTable';
                this.#selField = this.#rMemList;
                $('#editRuleSelButtons').show();
                break;
            case 's':
                this.#selValues = ',' + this.#sMemList.innerHTML + ',';
                this.#editRuleStepSelLabel.innerHTML = "<b>Select which memId's apply to this step:</b>"
                tableField = '#editRuleStepSelTable';
                this.#selField = this.#sMemList;
                $('#editRuleStepSelButtons').show();
                break;
        }
        this.#editRuleSelTable = new Tabulator(tableField, {
            data: memList,
            layout: "fitDataTable",
            index: "id",
            pagination: true,
            paginationAddRow:"table",
            paginationSize: 25,
            paginationSizeSelector: [10, 25, 50, 100, 250, true], //enable page size select element with these options
            columns: [
                {title: "ID", field: "id", width: 80, headerHozAlign:"right", hozAlign: "right", },
                {title: "ConId", field: "conid", width: 80, headerWordWrap: true, headerHozAlign:"right", hozAlign: "right",  headerFilter: true, },
                {title: "Cat", field: "memCategory", width: 90, headerFilter: 'list', headerFilterParams: { values: this.#filterCats }, },
                {title: "Type", field: "memType", width: 90, headerFilter: 'list', headerFilterParams: { values: this.#filterTypes },  },
                {title: "Age", field: "memAge", width: 90, headerFilter: 'list', headerFilterParams: { values: this.#filterAges },  },
                {title: "Label", field: "label", width: 250, headerFilter: true, },
                {title: "Price", field: "price", width: 80, headerFilter: true, headerHozAlign:"right", hozAlign: "right", },
                {title: "Notes", field: "notes", width: 200, headerFilter: true, },
                {title: "Start Date", field: "startDate", width: 200, visible:false, },
                {title: "End Date", field: "endDate", width: 200, visible:false, },
            ],
        });
        this.#editRuleSelTable.on("cellClick", rules.clickedSelection)
        this.#selIndex = 'id';
        setTimeout(rulesSetInitialSel, 100);
    }

    getselIndex() {
        return this.#selIndex;
    }

    // table functions
    // setInitialSel - set the initial selected items based on the current values
    setInitialSel() {
        var rows = this.#editRuleSelTable.getRows();
        for (var row of rows) {
            var name = row.getCell(rules.getselIndex()).getValue();
            if (this.#selValues.includes(name)) {
                row.getCell(rules.getselIndex()).getElement().style.backgroundColor = "#C0FFC0";
            }
        }
    }

    // toggle the selection color of the clicked cell
    clickedSelection(e, cell) {
        var filtercell = cell.getRow().getCell(rules.getselIndex());
        var value = filtercell.getValue();
        if (filtercell.getElement().style.backgroundColor) {
            filtercell.getElement().style.backgroundColor = "";
        } else {
            filtercell.getElement().style.backgroundColor = "#C0FFC0";
        }
    }

    // set all/clear all sections in table based on direction
    setRuleSel(level, direction) {
        var rows = this.#editRuleSelTable.getRows();
        for (var row of rows) {
            row.getCell(rules.getselIndex()).getElement().style.backgroundColor = direction ? "#C0FFC0" : "";
        }
    }

    // retrieve the selected rows and set the field values
    applyRuleSel(level) {
        var filter = '';
        var rows = null;
        rows = this.#editRuleSelTable.getRows();
        for (var row of rows) {
            if (row.getCell(rules.getselIndex()).getElement().style.backgroundColor != '') {
                filter += ',' + row.getCell(rules.getselIndex()).getValue();
            }
        }
        if (filter != '')
            filter = filter.substring(1);
        //console.log(filter);
        this.#selField.innerHTML = filter;
        this.closeSelTable(level);
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
            if (ruleName == this.#ruleItems[i].origName) {
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
        this.#rTypeList.innerHTML = ruleRow.typeList == '' ? "<i>None</i>" : ruleRow.typeList;
        this.#rCatList.innerHTML = ruleRow.catList =='' ? "<i>None</i>" : ruleRow.catList;
        this.#rAgeList.innerHTML = ruleRow.ageList == '' ? "<i>None</i>" : ruleRow.ageList;
        this.#rMemList.innerHTML = ruleRow.memList == '' ? "<i>None</i>" : ruleRow.memList;

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
                {title: "Apply To", field: "applyTo", width: 100, headerWordWrap: true, validator: "required", },
                {title: "typeList", field: "typeList", width: 300, },
                {title: "catList", field: "catList", width: 300, },
                {title: "ageList", field: "ageList", width: 300, },
                {title: "memList", field: "memList", width: 300, },
                {title: "Edit", formatter: this.editbutton, formatterParams: {table: 'ruleItems', label: 'Edit Step' }, hozAlign:"left", headerSort: false },
                {title: "Orig Name", field: "origName", visible: this.#debugVisible, headerFilter: false, headerWordWrap: true, width: 200,},
                {title: "Orig Step", field: "origStep", visible: this.#debugVisible, headerFilter: false, headerWordWrap: true, width: 200,},
                {
                    title: "Delete", field: "uses", formatter: deleteicon, hozAlign: "center", headerSort: false,
                    cellClick: function (e, cell) {
                        deleterow(e, cell.getRow());
                    }
                },
                {title: "To Del", field: "to_delete", visible: this.#debugVisible,}
            ],
        });
        this.#ruleStepsTable.on("dataChanged", function (data) {
            rulesStepsDataChanged();
        });
        this.#ruleStepsTable.on("cellEdited", cellChanged);

        this.#rulesUndoBtn = document.getElementById('rules-undo');
        this.#rulesRedoBtn = document.getElementById('rules-redo');
        this.#rulesAddRowBtn = document.getElementById('rules-addrow');
        this.#rulesSaveBtn = document.getElementById('rules-save');
        this.#rulesSaveBtn.innerHTML = "Save Changes";
        this.#rulesSaveBtn.disabled = true;
        this.#rulesDirty = false;
        this.#editRuleModal.show();
    }

    stepsDataChanged() {
        //data - the updated table data
        if (!this.#ruleStepsDirty) {
            this.#ruleStepsSaveBtn.innerHTML = "Save Changes*";
            this.#ruleStepsSaveBtn.disabled = false;
            this.#ruleStepsDirty = true;
        }
        this.checkUndoRedo();
        this.drawPreviewPane();
    };

    // process the save button on the edit modal
    editRuleSave() {
        var description = tinyMCE.activeEditor.getContent();

        // these will be encoded in <p> tags already, so strip the leading and trailing ones.
        if (description.startsWith('<p>')) {
            description = description.substring(3);
        }
        if (description.endsWith('</p>')) {
            description = description.substring(0, description.length - 4);
        }

        // store all the fields back into the table row
        var row = this.#rulesTable.getRow(this.#editRuleName);
        if (row.getCell("description").getValue() != description) {
            row.getCell("description").setValue(description);
        }

        var newValue = this.#rName.value;
        if (row.getCell("name").getValue() != newValue) {
            row.getCell("name").setValue(newValue);
        }
        newValue = this.#rOptionName.value;
        if (row.getCell("optionName").getValue() != newValue) {
            row.getCell("optionName").setValue(newValue);
        }
        newValue = this.#rAgeList.innerHTML;
        if (newValue == '')
            newValue = null;
        if (row.getCell("ageList").getValue() != newValue) {
            row.getCell("ageList").setValue(newValue);
        }
        newValue = this.#rTypeList.innerHTML;
        if (newValue == '')
            newValue = null;
        if (row.getCell("typeList").getValue() != newValue) {
            row.getCell("typeList").setValue(newValue);
        }
        newValue = this.#rCatList.innerHTML;
        if (newValue == '')
            newValue = null;
        if (row.getCell("catList").getValue() != newValue) {
            row.getCell("catList").setValue(newValue);
        }
        newValue = this.#rMemList.innerHTML;
        if (newValue == '')
            newValue = null;
        if (row.getCell("memList").getValue() != newValue) {
            row.getCell("memList").setValue(newValue);
        }

        if (this.#ruleStepsTable != null) {
            // save the rule steps table stuff back to the ruleItmes array
            var data = this.#ruleStepsTable.getData();
            if (data.length > 0) {
                var keys = Object.keys(data[0]);
                for (var i = 0; i < data.length; i++) {
                    var row = data[i];
                    var idx = this.#ruleItemsIdx[row.rownum];
                    for (var j = 0; j < keys.length; j++) {
                        var key = keys[j];
                        this.#ruleItems[idx][key] = row[key];
                    }
                }
            }
            this.#ruleStepsTable.off("dataChanged");
            this.#ruleStepsTable.off("cellEdited");
            this.#ruleStepsTable.destroy();
            this.#ruleStepsTable = null;
        }
        this.#rulesDirty = true;
        this.#rulesSaveBtn.innerHTML = "Save Changes";
        this.#rulesSaveBtn.disabled = false;
        this.checkUndoRedo();
        this.#editRuleModal.hide();
    }

    // save the rules and rule items back to the database
    save() {
        var _this = this;
        var data = {
            rules: JSON.stringify(this.#rulesTable.getData()),
            items: JSON.stringify(this.#ruleItems),
            action: 'save',
        }
        var script = 'scripts/regadmin_updateRules.php';
        clear_message();
        $.ajax({
            url: script,
            method: 'POST',
            data: data,
            success: function (data, textStatus, jhXHR) {
                _this.saveSuccess(data);
            },
            error: function (jqXHR, textStatus, errorThrown) {
                showError("ERROR in " + script + ": " + textStatus, jqXHR);
                return false;
            }
        });
    }

    // save complete - reset buttons, refresh data
    saveSuccess(data) {
        if (data['error']) {
            show_message(data['error'], 'error');
            return;
        }
        if (data['warn']) {
            show_message(data['warn'], 'warn');
            return;
        }
        this.open();
        show_message(data['success'], 'success');
    }
    
    // on close of the pane, clean up the items
    close() {
        if (this.#rulesTable) {
            this.#rulesTable.destroy();
            this.#rulesTable = null;
        }
        if (this.#ruleStepsTable != null) {
            this.#ruleStepsTable.off("dataChanged");
            this.#ruleStepsTable.off("cellEdited");
            this.#ruleStepsTable.destroy();
            this.#ruleStepsTable = null;
        }

        this.#rulesPane.innerHTML = '';
    };
}

function rulesDrawPreviewPane() {
    rules.drawPreviewPane();
}

function rulesSetInitialSel() {
    rules.setInitialSel();
}

function rulesDataChanged() {
    rules.dataChanged();
}

function rulesStepsDataChanged() {
    rules.stepsDataChanged();
}