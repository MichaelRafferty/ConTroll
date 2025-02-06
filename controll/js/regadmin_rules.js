//import { TabulatorFull as Tabulator } from 'tabulator-tables';
// rules class - all edit membership rules functions

var ageList = null;
var ageListIdx = null;
var memTypes = null;
var memTypesArr = null;
var memCategories = null;
var memCatArr = null;
var memList = null;
var memListFull = null;
var memListIdx = null;
var memRules = null;
var memRulesIdx = null;
var config = [];
var rulesMCEinit = false;

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
    #ruleAddRowNum = -1;

    // memRulesItems locals
    #ruleSteps = null;
    #ruleStepsDirty = null;
    #ruleStepsSaveBtn = null;
    #ruleStepsUndoBtn = null;
    #ruleStepsRedoBtn = null;
    #ruleStepsAddRowBtn = null;
    #ruleStepAddStepNum = -1;
    #ruleStepMaxStep = 1;

    // editing a rule
    #editRuleModal = null;
    #editRuleTitle = null;
    #editRuleBlock = null
    #editRuleSel = null
    #editRuleRow = null
    #editRuleSaveBtn = null;
    #editRuleName = null;
    #editRuleSelLabel = null;
    #editRuleNameDiv1 = null;
    #editRuleNameDiv2 = null;
    #editRuleNameDiv3 = null;
    #ruleDescription = null;
    #memRules = null;
    #rulesIdx = null;
    #rName = null;
    #rOptionName = null;
    #rTypeList = null;
    #rCatList = null;
    #rAgeList = null;
    #rMemList = null;
    #ruleStepDiv = null;
    #editStepRow = null;

    // simulator
    #ruleSimulator = null;
    #simulatorMemAvailable = null;
    #simulatorMemTable = null;

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
    #ruleStepsIdx = null;
    #memControlledTable = null;
    #memUsedTable = null;
    #memStepUsedTable = null;

    #editRuleSelTable = null;
    #selIndex = null;
    #selField = null;
    #selItem = null;
    #selValues = null;
    #filterTypes = [];
    #filterAges = []
    #filterCats = [];

    // selection items
    #selectionModal = null;
    #selectionTitle = null;
    #selectionBlock = null;

    // preview items
    #currentAge = null;
    #memberships = null

    #debug = 0;
    #conid = 0;
    #debugVisible = false;

    // globals before open
    constructor() {
        this.#debug =  Number(config['debug']);
        this.#conid =  Number(config['conid']);
        if (this.#debug & 2) {
            this.#debugVisible = true;
        }
        config['debug'] = this.#debug;
        config['conid'] = this.#conid;

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
            this.#editRuleNameDiv1 = document.getElementById('editRuleName1');
            this.#editRuleNameDiv2 = document.getElementById('editRuleName2');
            this.#editRuleNameDiv3 = document.getElementById('editRuleName3');
            this.#ruleDescription = document.getElementById('ruleDescription');
            this.#rName = document.getElementById('rName');
            this.#rOptionName = document.getElementById('rOptionName');
            this.#rTypeList = document.getElementById('rTypeList');
            this.#rCatList = document.getElementById('rCatList');
            this.#rAgeList = document.getElementById('rAgeList');
            this.#rMemList = document.getElementById('rMemList');
            this.#ruleStepDiv = document.getElementById('ruleStepDiv');

            if (rulesMCEinit) {
                tinyMCE.get("ruleDescription").focus();
                tinyMCE.get("ruleDescription").load();
            } else {
                // start the tinyMCE editors
                tinyMCE.init({
                    selector: 'textarea#ruleDescription',
                    id: "prompt",
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
                // Prevent Bootstrap dialog from blocking focusin
                document.addEventListener('focusin', (e) => {
                    if (e.target.closest(".tox-tinymce, .tox-tinymce-aux, .moxman-window, .tam-assetmanager-root") !== null) {
                        e.stopImmediatePropagation();
                    }
                });
                rulesMCEinit = true;
            }
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
            <div class="col-sm-6">
                <h4><strong>Edit Membership Rules:</strong></h4>              
            </div>
            <div class="col-sm-6 text-end">
                <strong><a href="md/Membership-Rules.md" target="_new">Display Rules Documentation (requires Markdown Reader Extension)</a></strong>
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
            <div class="col-sm-12">
                <h4>
                    <strong>Rules Simulator:</strong>&nbsp;&nbsp;&nbsp;
                    Similated Current Date:
                    <input type="text" maxlength="20" size="20" name="simDate" id="simDate" value="" onchange="rules.updateDate();"/>
                    <select name="simLimit" id="simLimit" onchange="rules.updateDate();">
                        <option value="All" style="background-color: #D0D0D0;" "selected>All Membership Items</option>
                        <option value="Both" style="background-color: #D0FFD0;">Online=Y or Atcon=Y</option>
                        <option value="Online"style="background-color: #FFD0FF;">Online=Y</option>
                        <option value="Atcon"style="background-color: #D0D0FF;">Atcon=Y</option>
                        <option value="None">Online=N and Atcon=N</option>
                    </select>
                </h4>
            </div>
        </div>
    </div>
    <div class="container-fluid" id="ruleSimulatorDiv"></div>
    <div class="container-fluid">
        <div class="row mt-4">
            <div class="col-sm-auto">
                <h4>Memberships Available for Simulation</h4>
            </div>
        </div>
        <div class="row mt-2">
            <div class="col-sm-12" id="simulatorMemAvailable"></div>
        </div>
    </div>
    <div class="container-fluid">
        <div class="row">
            <div class="col-sm-12" id="rulesMessageDiv"></div>
        </div>
    </div>
`;
        this.#rulesPane.innerHTML = html;
        memRules = null;
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
        this.#simulatorMemAvailable = document.getElementById('simulatorMemAvailable');
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
        if (!data['memRules']) {
            show_message("Error loading rules", 'error');
            return;
        }
        memRules = data['memRules'];
        memTypes = data['memTypes'];
        memCategories = data['memCategories'];
        ageList = data['ageList'];
        ageListIdx = data['ageListIdx'];
        memList = data['memListFull'];
        memListFull = data['memListFull'];
        memListIdx = data['memListFullIdx'];

        // make arrays from objects
        memTypesArr = [];
        for (var memType in  memTypes) {
            memTypesArr.push(memTypes[memType]);
        }
        memCatArr = [];
        for (var memCat in  memCategories) {
            memCatArr.push(memCategories[memCat]);
        }

        // create index of rules
        this.#rulesIdx = {};
        this.#memRules = [];
        var keys = Object.keys(memRules);
        for (var i = 0; i < keys.length; i++) {
            this.#memRules.push(memRules[keys[i]]);
            this.#rulesIdx[memRules[keys[i]].name] = i;
        }

        this.#filterAges = [];
        this.#filterCats = [];
        this.#filterTypes = [];

        // load the filter arrays
        this.#filterTypes = Object.keys(memTypes);
        this.#filterAges = Object.keys(ageList);
        this.#filterCats = Object.keys(memCategories);

        this.#rulesDirty = false;
        this.#rulesTable = new Tabulator('#rulesTableDiv', {
            history: true,
            data: this.#memRules,
            layout: "fitDataTable",
            index: "origName",
            pagination: true,
            paginationAddRow:"table",
            paginationSize: 10,
            paginationSizeSelector: [10, 25, 50, 100, 250, true], //enable page size select element with these options
            columns: [
                {title: "Edit", formatter: this.editbutton, formatterParams: {table: 'rules', label: 'Edit Rule' }, hozAlign:"left", headerSort: false },
                {title: "Name", field: "name", width: 200, headerSort: true, headerFilter: true, validator: "required", },
                {title: "Option Name", field: "optionName", headerWordWrap: true, width: 200, headerSort: true, headerFilter: true, validator: "required", },
                {title: "Description", field: "description", headerSort: false, width: 600, headerFilter: true, validator: "required", formatter: this.toHTML, },
                {title: "typeList", field: "typeList", headerSort: false, headerFilter: true, width: 180, formatter: "textarea", },
                {title: "catList", field: "catList", headerSort: false, headerFilter: true, width: 180, formatter: "textarea", },
                {title: "ageList", field: "ageList", headerSort: false, headerFilter: true, width: 180, formatter: "textarea", },
                {title: "memList", field: "memList", headerSort: false, headerFilter: true, width: 180, formatter: "textarea", },
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

    buildAgeButtons() {
        var html = '';
        for (var row in ageList) {
            var age = ageList[row];
            if (age.ageType == 'all')
                continue;
            html += '<div class="col-sm-auto"><button id="ageBtn-' + age.ageType + '" class="btn btn-sm btn-secondary" ' +
              'onclick="rules.ageSelect(' + "'" + age.ageType + "'" + ')">' + age.label + ' (' + age.shortname + ')' +
                '</button></div>\n';
        }
        return html;
    }

    buildMembershipButtons() {
        // now loop over age list and build each button
        var html = '';
        var rules = new MembershipRules(this.#conid, this.#currentAge, this.#memberships, this.#memberships);

        for (var row in memList) {
            var mem = memList[row];
            // apply implitict rules and membershipRules against memList entry
            if (!rules.testMembership(mem))
                continue;

            // apply age filter from age select
            if (mem.memAge == 'all' || mem.memAge == this.#currentAge) {
                var memLabel = mem.label;
                if ((!memCategories.hasOwnProperty(mem.memCategory)) || memCategories[mem.memCategory].variablePrice != 'Y') {
                    memLabel += ' (' + mem.price + ')';
                }
                html += '<div class="col-sm-auto mt-1 mb-1"><button id="memBtn-' + mem.id + '" class="btn btn-sm btn-primary"' +
                    ' onclick="rules.membershipAdd(' + "'" + mem.id + "'" + ')">' + '[' + mem.memId + '] ' +
                    (mem.conid != this.#conid ? mem.conid + ' ' : '') + memLabel + '</button></div>' + "\n";
            }
        }
        document.getElementById('membershipButtonsDiv').innerHTML = html;
    }

    ageSelect(ageType) {
        this.#currentAge = ageType;
        for (var age of ageList) {
            var id = document.getElementById('ageBtn-' + age.ageType);
            if (id) {
                if (ageType == age.ageType) {
                    var current = id.classList.contains('btn-success');
                    if (current) {
                        this.#currentAge = null;  // turn it back off
                    }
                }
                id.classList.remove('btn-secondary');
                id.classList.remove('btn-success');
                id.classList.add(age.ageType == this.#currentAge ? 'btn-success' : 'btn-secondary');
            }
        }

        this.updatePreviewPane();
    }

    updateDate(updatePane = true) {
        clear_message();
        var dateStr = document.getElementById('simDate').value.trim();
        var memFilter = document.getElementById('simLimit').value;
        //console.log("dateStr = '" + dateStr + "', memFilter='" + memFilter + "'");
        if (dateStr == '' && memFilter == 'All') {
            memList = memListFull;
        } else {
            var simDate = 0;
            if (dateStr != '') {
                simDate = Date.parse(dateStr);
                if (isNaN(simDate)) {
                    show_message("Unable to parse " + dateStr + " as a date.", 'warn');
                    return;
                }
            }

            memList = [];
            for (var i = 0; i < memListFull.length; i++) {
                var row = memListFull[i];
                // check for proper limit

                if (memFilter != 'All') {
                    var skip = false;
                    switch (memFilter) {
                        case 'Both':
                            if (row.atcon == 'N' && row.online == 'N')
                                skip = true;
                            break;
                        case 'Online':
                            if (row.online == 'N')
                                skip = true;
                            break;
                        case 'Atcon':
                            if (row.atcon == 'N')
                                skip = true;
                            break;
                        case 'None':
                            if (row.atcon == 'Y' || row.online == 'Y')
                                skip = true;
                            break;
                    }
                    if (skip)
                        continue;
                }
                if (simDate != 0) {
                    //console.log(dateStr + ', ' + row.startdate + ', ' + row.enddate);
                    var startDate = Date.parse(row.startdate);
                    var endDate = Date.parse(row.enddate);
                    //console.log(simDate + ' ' + startDate + ' ' + endDate);
                    if (simDate < startDate || simDate >= endDate)
                        continue;
                }
                memList.push(row);
            }
        }
        
        // figure out which rules control and use this memList entry
        for (var i = 0; i < memList.length; i++) {
            row = memList[i];
            var controlled = '';
            var usedby = '';
            for (var r = 0; r < this.#memRules.length; r++) {
                var rule = this.#memRules[r];
                var ruleset = rule.ruleset;
                // check each type to see if this rule fits it
                if (this.#checkItem(row, rule)) {
                    if (controlled != '')
                        controlled += ',';
                    controlled += rule.name;
                }

                var steps = Object.keys(ruleset);
                for (var s = 0; s < steps.length; s++) {
                    var step = ruleset[steps[s]];
                    if (this.#checkItem(row, step)) {
                        if (usedby != '')
                            usedby += ',';
                        usedby += step.name + '.' + step.step;
                    }
                }
            }
            row.controlled = controlled;
            row.usedby = usedby;
        }

        // update or show the mems available in this simulation
        if (this.#simulatorMemTable) {
            this.#simulatorMemTable.replaceData(memList);
        } else {
            this.#simulatorMemTable = new Tabulator('#simulatorMemAvailable', {
                data: memList,
                layout: "fitDataTable",
                index: "memId",
                columns: [
                    {title: "Id", field: "memId", headerSort: true, headerHozAlign: "right", hozAlign: "right",},
                    {title: "Conid", field: "conid", headerSort: true, headerHozAlign: "right", hozAlign: "right",},
                    {title: "Label", field: "label", headerFilter: true, width: 300, formatter: "textarea",},
                    {title: "Price", field: "price", headerHozAlign: "right", hozAlign: "right",},
                    {title: "Notes", field: "notes", headerFilter: true, width: 300, formatter: "textarea",},
                    {title: "Controlled By", field: "controlled", headerFilter: true, width: 400, formatter: "textarea", },
                    {title: "Used By", field: "usedby", headerFilter: true, width:400, formatter: "textarea", },
                ],
            });
        }

        if (updatePane)
            this.updatePreviewPane();
    }

    #checkItem(row, item) {
        var match = true;
        if (match && item.ageList != null && item.ageList != '') {
            if (!item.hasOwnProperty('ageListArray')) {
                item.ageListArray = item.ageList.split(',');
            }
            if (!item.ageListArray.includes(row.memAge))
                match = false;
        }
        if (match && item.catList != null && item.catList != '') {
            if (!item.hasOwnProperty('catListArray')) {
                item.catListArray = item.catList.split(',');
            }
            if (!item.catListArray.includes(row.memCategory))
                match = false;
        }
        if (match && item.typeList != null && item.typeList != '') {
            if (!item.hasOwnProperty('typeListArray')) {
                item.typeListArray = item.typeList.split(',');
            }
            if (!item.typeListArray.includes(row.memType))
                match = false;
        }
        if (match && item.memList != null && item.memList != '') {
            if (!item.hasOwnProperty('memListArray')) {
                item.memListArray = item.memList.split(',');
            }
            if (!item.memListArray.includes(row.memId.toString()))
                match = false;
        }
        return match;
    }

    // build the mem controlled table for the edit modals
    #buildMemControlled(div, ruleRow = null) {
        var rule;
        if (ruleRow) {
            rule = ruleRow;
        } else {
            rule = this.#rulesTable.getRow(this.#editRuleName).getData();
        }
        //console.log(rule);
        var memControlled = [];
        for (var i = 0; i < memListFull.length; i++) {
            var row = memListFull[i];
            // check each type to see if this rule fits it
            if (this.#checkItem(row, rule)) {
                memControlled.push(row);
            }
        }
        if (this.#memControlledTable) {
            this.#memControlledTable.replaceData(memControlled);
        } else {
            this.memConTrolledTable = new Tabulator(div, {
                data: memControlled,
                layout: "fitDataTable",
                columns: [
                    { title: "ID", field: "id", headerSort: true, headerHozAlign: "right", hozAlign: "right", },
                    { title: "Con ID", field: "conid", headerFilter: true, headerHozAlign:"right", hozAlign: "right", },
                    { title: "Category", field: "memCategory", headerFilter: true, },
                    { title: "Type", field: "memType", headerFilter: true, },
                    { title: "Age", field: "memAge", headerFilter: true, },
                    { title: "Label", field: "label", minWidth: 400, headerFilter: true, },
                    { title: "Price", field: "price", hozAlign: "right",  },
                    { title: "Start Date", field: "startdate", width: 170, headerFilter: true },
                    { title: "End Date", field: "enddate", width: 170, headerFilter: true},
                    { title: "Atcon", field: "atcon", headerFilter: true, headerFilterParams: { values: ["Y", "N"], } },
                    { title: "Online", field: "online", headerFilter: true, headerFilterParams: { values: ["Y", "N"], } },
                    { title: "Notes", field: "notes", minWidth: 400, headerFilter: true },
                ],
            });
        }
    }

    // build the mem used table for the edit modals
    #buildMemUsed(div, where, step = null) {
        var ruleset;
        if (step == null) {
            var rule = this.#rulesTable.getRow(this.#editRuleName).getData();
            ruleset = rule.ruleset;
        } else {
            ruleset = {};
            ruleset[step.step] = step;
        }

        var memUsed = [];
        for (var i = 0; i < memListFull.length; i++) {
            var row = memListFull[i];
            var steps = [];
            if (ruleset)
                steps = Object.keys(ruleset);
            var used = false;
            for (var s = 0; s < steps.length; s++) {
                if (this.#checkItem(row, ruleset[steps[s]]))
                    used = true;
            }
            if (used) {
                memUsed.push(row);
            }
        }
        if (table) {
            if (where == 'r') {
                this.#memUsedTable.replaceData(memUsed);
            } else if (where == 's')
                this.#memStepUsedTable.replaceData(memUsed);

        } else {
           var table = new Tabulator(div, {
                data: memUsed,
                layout: "fitDataTable",
                columns: [
                    { title: "ID", field: "id", headerSort: true, headerHozAlign: "right", hozAlign: "right", },
                    { title: "Con ID", field: "conid", headerFilter: true, headerHozAlign:"right", hozAlign: "right", },
                    { title: "Category", field: "memCategory", headerFilter: true, },
                    { title: "Type", field: "memType", headerFilter: true, },
                    { title: "Age", field: "memAge", headerFilter: true, },
                    { title: "Label", field: "label", minWidth: 400, headerFilter: true, },
                    { title: "Price", field: "price", hozAlign: "right",  },
                    { title: "Start Date", field: "startdate", width: 170, headerFilter: true },
                    { title: "End Date", field: "enddate", width: 170, headerFilter: true},
                    { title: "Atcon", field: "atcon", headerFilter: true, headerFilterParams: { values: ["Y", "N"], } },
                    { title: "Online", field: "online", headerFilter: true, headerFilterParams: { values: ["Y", "N"], } },
                    { title: "Notes", field: "notes", minWidth: 400, headerFilter: true, formatter: "textarea", },
                ],
            });
        }
        if (where == 'r') {
            this.#memUsedTable = table;
        } else if (where == 's')
            this.#memStepUsedTable = table;
    }

    membershipAdd(memId) {
        var memrow = this.findMembership(memId);
        if (memrow == null)
            return;

        var now = new Date();
        var newMembership = {};
        newMembership.id = -1;
        newMembership.create_date = now.getFullYear() + '-' + ('0' + (now.getMonth() + 1)).slice(-2) + '-' + ('0' + now.getDate()).slice(-2) + ' ' +
            ('0' + now.getHours()).slice(-2) + ':' + ('0' + now.getMinutes()).slice(-2) + ':' + ('0' + now.getSeconds()).slice(-2);
        newMembership.memId = memId;
        newMembership.conid = memrow.conid;
        newMembership.status = 'in-cart';
        newMembership.price = memrow.price;
        newMembership.paid = 0;
        newMembership.couponDiscount = 0;
        newMembership.label = memrow.label;
        newMembership.memCategory = memrow.memCategory;
        newMembership.memType = memrow.memType;
        newMembership.memAge = memrow.memAge;
        if (!this.#memberships)
            this.#memberships = [];
        this.#memberships.push(newMembership);
        this.updatePreviewPane();
    }

    // findMembership - find matching memRow in memList
    findMembership(id) {
        if (!memList)
            return null; // no list to search

        for (var row in memList) {
            var memrow = memList[row];
            if (id != memrow.id)
                continue;
            return memrow;  // return matching entry
        }
        return null; // not found
    }

    drawMembershipList() {
        var html = '';
        if (this.#memberships != null) {
            for (var i = 0; i < this.#memberships.length; i++) {
                html += '<div class="col-auto mt-1 mb-1"><button class="btn btn-sm btn-info text-white" type="button">' +
                    '[' + this.#memberships[i].memId + '] ' +
                    this.#memberships[i].conid + ' ' + this.#memberships[i].label + ' (' + this.#memberships[i].price + ')' +
                    '</button></div>\n';
            }
        }
        document.getElementById('membershipListDiv').innerHTML = html;
    }

    resetMembership() {
        this.#memberships = [];
        this.updatePreviewPane();
    }

    drawPreviewPane() {
        this.#ruleSimulator = document.getElementById('ruleSimulatorDiv');
        var html = '<div class="container-fluid"><div class="row mt-2 mb-2">\n';

        // first the age buttons
        html += this.buildAgeButtons();

        html += '</div>\n' +
            '<div class="row mt-2" id="membershipButtonsDiv"></div>\n</div>\n' +
            '<div class="row mt-4"><div class="col-sm-auto"><h4>Membership in Simulation: ' +
                '<button class="btn btn-sm btn-warning" onclick="rules.resetMembership();">Reset Memberships</button>' +
            '</h4></div></div>\n' +
            '<div class="row mt-2" id="membershipListDiv"></div>\n';
        this.#ruleSimulator.innerHTML = html;
        setTimeout(rulesUpdatePreviewPane, 100);
    }

    updatePreviewPane() {
        this.buildMembershipButtons();
        this.drawMembershipList();
        if (this.#simulatorMemTable == null)
            this.updateDate(false);
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
                case 'ruleSteps':
                    return '<button class="btn btn-secondary" style = "--bs-btn-padding-y: .0rem; --bs-btn-padding-x: .3rem; --bs-btn-font-size: .75rem;",' +
                    ' onclick="rules.editStep(\'ruleItems\',\'' + ruleName + '\');">Edit Step</button>';
            }
        }
        return "Save First";
    }

    // edit step - display a modal to edit a step
    editStep(type, itemId) {
        // populate the modal
        //console.log("type = '" + type + "', item = '" + itemId + "'");
        var row = this.#ruleStepsTable.getRow(itemId).getData();
        this.#editStepRow = make_copy(row);
        var item = '';
        this.#editRuleStepItem = itemId;
        item = row.name;
        this.#sName.value = item;
        this.#editRuleStepNameDiv.innerHTML = item;
        this.#sStep.value = row.step;
        this.#sRuleType.value = row.ruleType;
        this.#sApplyTo.value = row.applyTo;
        item = row.typeList;
        if (item == '' || item == undefined || item == null)
            item = "<i>None</i>";
        this.#sTypeList.innerHTML = item;

        item = row.catList;
        if (item == '' || item == undefined || item == null)
            item = "<i>None</i>";
        this.#sCatList.innerHTML = item;

        item = row.ageList;
        if (item == '' || item == undefined || item == null)
            item = "<i>None</i>";
        this.#sAgeList.innerHTML = item;

        item = row.memList;
        if (item == '' || item == undefined || item == null)
            item = "<i>None</i>";
        this.#sMemList.innerHTML = item;

        this.#editRuleModal.hide();
        this.#editRuleStepModal.show();
        $('#editRuleStepSelButtons').hide();
        this.#editRuleStepSelLabel.innerHTML = '';
        this.#selIndex = null;
        this.#buildMemUsed('#editStepUsedDiv', 's', this.#editStepRow);
    }

    addrowSteps() {
        var _this = this;
        this.#ruleStepAddStepNum--;
        this.#ruleStepsTable.addRow({
            name: this.#rName.value, uses: 0, origStep: this.#ruleStepAddStepNum, step: this.#ruleStepMaxStep, origName: this.#editRuleName
            }, false).then(function (row) {
            _this.#rulesTable.setPage("last"); // adding new to last page always
            row.getTable().scrollToRow(row);
            _this.checkStepsUndoRedo();
        });
        this.#ruleStepMaxStep++;
    }

    editRuleStepSave(dosave) {
        // save the results back to the underlying table
        if (dosave) {
            if (this.#debug > 0) console.log('editRuleStepSave:' + this.#editRuleStepItem);
            // store all the fields back into the table row
            var row = this.#ruleStepsTable.getRow(this.#editRuleStepItem);
            var rowdata = row.getData();
            if (this.#debug > 0) console.log(row.getData());

            var newValue = this.#sName.value;
            if (rowdata.name != newValue) {
                row.getCell("name").setValue(newValue);
            }
            newValue = this.#sStep.value;
            if (rowdata.step != newValue) {
                row.getCell("step").setValue(newValue);
            }
            newValue = this.#sRuleType.value;
            if (newValue == '') {
                show_message('You must select a rule type', 'error', 'result_message_editRuleStep');
                return;
            }
            if (rowdata.ruleType != newValue) {
                row.getCell("ruleType").setValue(newValue);
            }
            newValue = this.#sApplyTo.value;
            if (newValue == '') {
                show_message('You must select an Apply To', 'error', 'result_message_editRuleStep');
                return;
            }
            if (rowdata.applyTo != newValue) {
                row.getCell("applyTo").setValue(newValue);
            }
            newValue = this.#sAgeList.innerHTML;
            if (newValue == '' || newValue == undefined || newValue == '<i>None</i>')
                newValue = null;
            if (rowdata.ageList != newValue) {
                row.getCell("ageList").setValue(newValue);
            }
            newValue = this.#sTypeList.innerHTML;
            if (newValue == '' || newValue == undefined || newValue == '<i>None</i>')
                newValue = null;
            if (rowdata.typeList != newValue) {
                row.getCell("typeList").setValue(newValue);
            }
            newValue = this.#sCatList.innerHTML;
            if (newValue == '' || newValue == undefined || newValue == '<i>None</i>')
                newValue = null;
            if (rowdata.catList != newValue) {
                row.getCell("catList").setValue(newValue);
            }
            newValue = this.#sMemList.innerHTML;
            if (newValue == '' || newValue == undefined || newValue == '<i>None</i>')
                newValue = null;
            if (rowdata.memList != newValue) {
                row.getCell("memList").setValue(newValue);
            }

        }
        this.#editRuleStepModal.hide();
        this.#editRuleModal.show();
        this.#buildMemControlled('#editRuleControlledDiv');
        this.#buildMemUsed('#editRuleUsedDiv', 'r');
    }

    undoSteps() {
        if (this.#ruleStepsTable != null) {
            this.#ruleStepsTable.undo();

            if (this.checkStepsUndoRedo() <= 0) {
                this.#ruleStepsDirty = false;
                this.#editRuleSaveBtn.innerHTML = "Save Changes";
                this.#editRuleSaveBtn.disabled = true;
            }
        }
    };

    redoSteps() {
        if (this.#ruleStepsTable != null) {
            this.#ruleStepsTable.redo();

            if (this.checkStepsUndoRedo() > 0) {
                this.#ruleStepsDirty = true;
                this.#editRuleSaveBtn.innerHTML = "Save Changes*";
                this.#editRuleSaveBtn.disabled = false;
            }
        }
    };

    // set undo / redo status for buttons
    checkStepsUndoRedo() {
        var undosize = this.#ruleStepsTable.getHistoryUndoSize();
        this.#ruleStepsUndoBtn.disabled = undosize <= 0;
        this.#ruleStepsRedoBtn.disabled = this.#ruleStepsTable.getHistoryRedoSize() <= 0;
        return undosize;
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
        this.#selItem = 'typeList';
        switch (level) {
            case 'r':
                this.#selValues = this.#rTypeList.innerHTML.split(',');
                this.#editRuleSelLabel.innerHTML = "<b>Select which Types apply to this rule:</b>"
                tableField = '#editRuleSelTable';
                this.#selField = this.#rTypeList;
                $('#editRuleSelButtons').show();
                break;
            case 's':
                this.#selValues = this.#sTypeList.innerHTML.split(',');
                this.#editRuleStepSelLabel.innerHTML = "<b>Select which Types apply to this step:</b>"
                tableField = '#editRuleStepSelTable';
                this.#selField = this.#sTypeList;
                $('#editRuleStepSelButtons').show();
                break;
        }

        this.#editRuleSelTable = new Tabulator(tableField, {
            data: memTypesArr,
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
        this.#selItem = 'catList';
        switch (level) {
            case 'r':
                this.#selValues = this.#rCatList.innerHTML.split(',');
                this.#editRuleSelLabel.innerHTML = "<b>Select which Categories apply to this rule:</b>"
                tableField = '#editRuleSelTable';
                this.#selField = this.#rCatList;
                $('#editRuleSelButtons').show();
                break;
            case 's':
                this.#selValues = this.#sCatList.innerHTML.split(',');
                this.#editRuleStepSelLabel.innerHTML = "<b>Select which Categories apply to this step:</b>"
                tableField = '#editRuleStepSelTable';
                this.#selField = this.#sCatList;
                $('#editRuleStepSelButtons').show();
                break;
        }

        this.#editRuleSelTable = new Tabulator(tableField, {
            data: memCatArr,
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
        this.#selItem = 'ageList';
        switch (level) {
            case 'r':
                this.#selValues = this.#rAgeList.innerHTML.split(',');
                this.#editRuleSelLabel.innerHTML = "<b>Select which Ages apply to this rule:</b>"
                tableField = '#editRuleSelTable';
                this.#selField = this.#rAgeList;
                $('#editRuleSelButtons').show();
                break;
            case 's':
                this.#selValues = this.#sAgeList.innerHTML.split(',');
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
        this.#selItem = 'memList';
        switch (level) {
            case 'r':
                this.#selValues = this.#rMemList.innerHTML.split(',');
                this.#editRuleSelLabel.innerHTML = "<b>Select which memId's apply to this rule:</b>"
                tableField = '#editRuleSelTable';
                this.#selField = this.#rMemList;
                $('#editRuleSelButtons').show();
                break;
            case 's':
                this.#selValues = this.#sMemList.innerHTML.split(',');
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
            paginationSize: 9999,
            paginationSizeSelector: [10, 25, 50, 100, 250, true], //enable page size select element with these options
            columns: [
                {title: "ID", field: "id", width: 80, headerHozAlign:"right", hozAlign: "right", },
                {title: "ConId", field: "conid", width: 80, headerWordWrap: true, headerHozAlign:"right", hozAlign: "right",  headerFilter: true, },
                {title: "Cat", field: "memCategory", width: 90, headerFilter: 'list', headerFilterParams: { values: this.#filterCats }, },
                {title: "Type", field: "memType", width: 90, headerFilter: 'list', headerFilterParams: { values: this.#filterTypes },  },
                {title: "Age", field: "memAge", width: 90, headerFilter: 'list', headerFilterParams: { values: this.#filterAges },  },
                {title: "Label", field: "label", width: 250, headerFilter: true, },
                {title: "Price", field: "price", width: 80, headerFilter: true, headerHozAlign:"right", hozAlign: "right", },
                {title: "Notes", field: "notes", width: 200, headerFilter: true,  formatter: "textarea", },
                {title: "Start Date", field: "startDate", width: 200, visible: this.#debugVisible, },
                {title: "End Date", field: "endDate", width: 200, visible: this.#debugVisible, },
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
            var name = row.getCell(this.#selIndex).getValue().toString();
            if (this.#selValues.includes(name)) {
                row.getCell(this.#selIndex).getElement().style.backgroundColor = "#C0FFC0";
            }
        }
        if (this.#selIndex == 'id')
            this.#editRuleSelTable.setPageSize(25);
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
        if (this.#debug > 0) console.log('enter applyRuleSel(' + level + ')');
        // store all the fields back into the table row
        if (level == 's' && this.#editRuleStepItem) {
            var row = this.#ruleStepsTable.getRow(this.#editRuleStepItem);
            var rowdata = row.getData();
            if (this.#debug > 0) console.log(row.getData());
        }

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
        if (this.#debug > 0) console.log(filter);
        this.#selField.innerHTML = filter;
        this.closeSelTable(level);
        if (level == 'r') {
            this.#editRuleRow[this.#selItem] = filter;
            this.#editRuleRow[this.#selItem + 'Array'] = filter.split(',');
            this.#buildMemControlled('#editRuleControlledDiv', this.#editRuleRow);
            this.#buildMemUsed('#editRuleUsedDiv', level);
        } else {
            this.#editStepRow[this.#selItem] = filter;
            this.#editStepRow[this.#selItem + 'Array'] = filter.split(',');
            this.#buildMemUsed('#editStepUsedDiv', level, this.#editStepRow);
        }

        if (level == 's' && this.#debug > 0) {
            console.log('exit applyRuleSel(' + level + ')');
            // final values in the step
            row = this.#ruleStepsTable.getRow(this.#editRuleStepItem);
            rowdata = row.getData();
            console.log(row.getData());
        }
    }

    // add row to  table and scroll to that new row
    addrow() {
        var _this = this;
        this.#ruleAddRowNum--;
        this.#rulesTable.addRow({name: 'new-row', uses: 0, origName: this.#ruleAddRowNum}, false).then(function (row) {
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
        this.#editRuleRow = ruleRow;
        editPreviewClass = 'rules';
        var ruleOrigName = ruleRow.origName;
        var ruleDisplayName = ruleRow.name;
        var ruleDescription = ruleRow.description == null ? '' : ruleRow.description;

        var ruleSteps = {};
        if (memRules[ruleOrigName]) {
            if (memRules[ruleOrigName].ruleset) {
                ruleSteps = memRules[ruleOrigName].ruleset;
            }
        }

        var keys = Object.keys(ruleSteps);
        this.#ruleSteps = [];
        this.#ruleStepsIdx = {};
        // renumber the rules
        this.#ruleStepMaxStep = 1;
        for (var i = 0; i < keys.length; i++) {
            this.#ruleSteps.push(ruleSteps[keys[i]]);
            if (ruleSteps[keys[i]].step < 990) {
                ruleSteps[keys[i]].step = this.#ruleStepMaxStep;
                this.#ruleStepMaxStep++;
            }
            this.#ruleStepsIdx[ruleSteps[keys[i]].rownum] = i;
        }

        // build the modal contents
        this.#editRuleTitle.innerHTML = "Edit the " + ruleDisplayName + " rule";
        this.#editRuleNameDiv1.innerHTML = ruleDisplayName;
        this.#editRuleNameDiv2.innerHTML = ruleDisplayName;
        this.#editRuleNameDiv3.innerHTML = ruleDisplayName;
        this.#ruleDescription.innerHTML = ruleDescription;
        this.#rName.value = ruleRow.name
        this.#rOptionName.value = (ruleRow.optionName == undefined || ruleRow.optionName == null)  ? '' : ruleRow.optionName;
        this.#rTypeList.innerHTML = (ruleRow.typeList == '' || ruleRow.typeList == undefined || ruleRow.typeList == null) ? "<i>None</i>" : ruleRow.typeList;
        this.#rCatList.innerHTML = (ruleRow.catList == '' || ruleRow.catList == undefined || ruleRow.catList == null) ? "<i>None</i>" : ruleRow.catList;
        this.#rAgeList.innerHTML = (ruleRow.ageList == '' || ruleRow.ageList == undefined || ruleRow.ageList == null) ? "<i>None</i>" : ruleRow.ageList;
        this.#rMemList.innerHTML = (ruleRow.memList == '' || ruleRow.memList == undefined || ruleRow.memList == null) ? "<i>None</i>" : ruleRow.memList;

        tinyMCE.get("ruleDescription").focus();
        tinyMCE.get("ruleDescription").load();
        this.#ruleStepsTable = new Tabulator('#ruleStepDiv', {
            history: true,
            data: this.#ruleSteps,
            layout: "fitDataTable",
            index: "origStep",
            columns: [
                {title: "Edit", formatter: this.editbutton, formatterParams: {table: 'ruleSteps', label: 'Edit Step' }, hozAlign:"left", headerSort: false },
                {title: "Name", field: "name", width: 200, validator: "required", },
                {title: "Step", field: "step", width: 70, headerHozAlign:"right", hozAlign: "right", headerSort: false, validator: "required", },
                {title: "Rule Type", field: "ruleType", headerWordWrap: true, width: 150, headerSort: false, validator: "required", },
                {title: "Apply To", field: "applyTo", width: 100, headerWordWrap: true, validator: "required", },
                {title: "typeList", field: "typeList", width: 250, formatter: "textarea", },
                {title: "catList", field: "catList", width: 250, formatter: "textarea", },
                {title: "ageList", field: "ageList", width: 250, formatter: "textarea",},
                {title: "memList", field: "memList", width: 250, formatter: "textarea", },
                {title: "Orig Name", field: "origName", visible: this.#debugVisible, headerFilter: false, headerWordWrap: true, width: 200,},
                {title: "Orig Step", field: "origStep", visible: this.#debugVisible, headerFilter: false, headerWordWrap: true, width: 70,},
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

        // now build the memids controlled and used by this row
        this.#buildMemControlled('#editRuleControlledDiv');
        this.#buildMemUsed('#editRuleUsedDiv', 'r');

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
        this.checkStepsUndoRedo();
    };

    // process the save button on the edit modal
    editRuleSave() {
        if (!memRules[this.#editRuleName]) // if new, add it.
            memRules[this.#editRuleName] = {};
        var description = tinyMCE.activeEditor.getContent();

        // these will be encoded in <p> tags already, so strip the leading and trailing ones.
        if (description.startsWith('<p>')) {
            description = description.substring(3);
        }
        if (description.endsWith('</p>')) {
            description = description.substring(0, description.length - 4);
        }

        // store all the fields back into the table row, and into the main table
        var row = this.#rulesTable.getRow(this.#editRuleName);
        var rowdata = row.getData();
        if (rowdata.description != description) {
            row.getCell("description").setValue(description);
            memRules[this.#editRuleName].description = description;
        }

        var newValue = this.#rName.value;
        if (rowdata.name != newValue) {
            row.getCell("name").setValue(newValue);
            memRules[this.#editRuleName].name = newValue;
        }
        newValue = this.#rOptionName.value;
        if (newValue == undefined || newValue == null || newValue == "undefined" || newValue == "null")
            newValue = null;
        if (rowdata.optionName != newValue) {
            row.getCell("optionName").setValue(newValue);
            memRules[this.#editRuleName].optionName = newValue;
        }
        newValue = this.#rAgeList.innerHTML;
        if (newValue == '' || newValue == undefined || newValue == '<i>None</i>')
            newValue = null;
        if (rowdata.ageList != newValue) {
            row.getCell("ageList").setValue(newValue);
            memRules[this.#editRuleName].ageList = newValue;
            memRules[this.#editRuleName].ageListArray = newValue.split(',');
        }
        newValue = this.#rTypeList.innerHTML;
        if (newValue == '' || newValue == undefined || newValue == '<i>None</i>')
            newValue = null;
        if (rowdata.typeList != newValue) {
            row.getCell("typeList").setValue(newValue);
            memRules[this.#editRuleName].typeList = newValue;
            memRules[this.#editRuleName].typeListArray = newValue.split(',');
        }
        newValue = this.#rCatList.innerHTML;
        if (newValue == '' || newValue == undefined || newValue == '<i>None</i>')
            newValue = null;
        if (rowdata.catList != newValue) {
            row.getCell("catList").setValue(newValue);
            memRules[this.#editRuleName].catList = newValue;
            memRules[this.#editRuleName].catListArray = newValue.split(',');
        }
        newValue = this.#rMemList.innerHTML;
        if (newValue == '' || newValue == undefined || newValue == '<i>None</i>')
            newValue = null;
        if (rowdata.memList != newValue) {
            row.getCell("memList").setValue(newValue);
            memRules[this.#editRuleName].memList = newValue;
            memRules[this.#editRuleName].memListArray = newValue.split(',');
        }

        if (this.#ruleStepsTable != null) {
            // save the rule steps table stuff back to the main rule array
            var data = this.#ruleStepsTable.getData();

            if (data.length > 0) {
                var keys = Object.keys(data[0]);
                if (memRules.length == 0) {
                    memRules = {};
                }
                // figure out which step it belongs to by the name
                if (!memRules.hasOwnProperty(this.#editRuleName)) {
                    memRules[this.#editRuleName] = {};
                }
                if (!memRules[this.#editRuleName].hasOwnProperty('ruleset')) {
                    memRules[this.#editRuleName].ruleset = {};
                }

                for (var i = 0; i < data.length; i++) {
                    var row = data[i];
                    if (!memRules[this.#editRuleName].ruleset[row.origStep])
                        memRules[this.#editRuleName].ruleset[row.origStep] = {};
                    for (var j = 0; j < keys.length; j++) {
                        var key = keys[j];
                        memRules[this.#editRuleName].ruleset[row.origStep][key] = row[key];
                    }
                }
            }
            this.#ruleStepsTable.off("dataChanged");
            this.#ruleStepsTable.off("cellEdited");
            this.#ruleStepsTable.destroy();
            this.#ruleStepsTable = null;
        }

        if (!this.#rulesDirty) {
            this.#rulesSaveBtn.innerHTML = "Save Changes*";
            this.#rulesSaveBtn.disabled = false;
            this.#rulesDirty = true;
        }
        this.dataChanged();

        this.checkUndoRedo();
        this.updateDate(false);
        this.#editRuleModal.hide();
    }

    // save the rules and rule items back to the database
    save() {
        var _this = this;
        // save the rules table data back to the master rule set
        var data = this.#rulesTable.getData();
        var keys = Object.keys(data[0]);
        if (memRules.length == 0) {
            memRules = {};
        }
        for (var i = 0; i < data.length; i++) {
            var row = data[i];
            var origName = row.origName;
            if (!memRules.hasOwnProperty(origName)) {
                memRules[origName] = row;
            }
            for (var j = 0; j < keys.length; j++) {
                var key = keys[j];
                if (key != 'ruleset')
                    memRules[origName][key] = row[key];
            }
        }

        data = {
            rules: JSON.stringify(memRules),
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
        if (this.#simulatorMemTable) {
            this.#simulatorMemTable.destroy()
            this.#simulatorMemTable = null;
        }

        this.#rulesPane.innerHTML = '';
    };
}

function rulesDrawPreviewPane() {
    rules.drawPreviewPane();
}

function rulesUpdatePreviewPane() {
    rules.updatePreviewPane();
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