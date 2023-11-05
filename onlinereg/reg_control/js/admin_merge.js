//import { TabulatorFull as Tabulator } from 'tabulator-tables';

class mergesetup {
    #message_div = null;
    #merge_pane = null;
    #remainPid = null;
    #mergePid = null;
    #find_candidate_btn = null;
    #check_merge_btn = null;
    #mergeCandidatesDiv = null;
    #mergeCandidatesTable = null;
    #mergeCheck_div = null;

    constructor() {
        this.#message_div = document.getElementById('test');
        this.#merge_pane = document.getElementById('merge-pane');
    };

    getMergePid() {
        if (this.#mergePid)
            return this.#mergePid.value;
        return null;
    }

    getRemainPid() {
        if (this.#remainPid)
            return this.#remainPid.value;
        return null;
    }

    open() {

        var html = `<h4><strong>Merge People:</strong></h4>
<div class="container-fluid">
    <div class="row">
        <div class="col-sm-2 p-2"><label for="remainPid">Perid to remain:</label></div>
        <div class="col-sm-2 p-2"><input  class='form-control-sm' type="number" id="remainPid" name="remainPid" onchange="merge.btnctl()"></div>
    </div>
    <div class="row">
        <div class="col-sm-2 p-2"><label for="mergePid">Perid to merge in remain:</label></div>
        <div class="col-sm-2 p-2"><input  class='form-control-sm' type="number" id="mergePid" name="mergePid" onchange="merge.btnctl()"></div>
    </div>
    <div class="row mt-2">
        <div class="col-sm-auto p-2">
            <button id="find-candidate" type="button" class="btn btn-primary btn-sm" onclick="merge.findCandidates(); return false;" disabled>Find Candidates</button>
            <button id="check-merge" type="button" class="btn btn-primary btn-sm" onclick="merge.checkMerge(); return false;" disabled>Check Merge</button>
        </div>
    </div>
    <div class="row mt-4">
        <div class="col-sm-12 p-2" id="mergeCandidates"></div>
    </div>
    <div class="row mt-4">
        <div class="col-sm-12 p-2" id="mergeCheck-div"></div>
    </div>
</div>
`;
        this.#merge_pane.innerHTML = html;
        this.#remainPid = document.getElementById('remainPid');
        this.#mergePid = document.getElementById('mergePid');
        this.#find_candidate_btn = document.getElementById('find-candidate');
        this.#check_merge_btn = document.getElementById('check-merge');
        this.#mergeCandidatesDiv = document.getElementById('mergeCandidates');
        this.#mergeCheck_div = document.getElementById('mergeCheck-div');
    }

    close() {
        if (this.#mergeCandidatesTable) {
            this.#mergeCandidatesTable.destroy();
            this.#mergeCandidatesTable = null;
        }
        this.#merge_pane.innerHTML = '';
    };

    btnctl() {
        var remainPID = this.getRemainPid();
        this.#find_candidate_btn.disabled =  !(remainPID > 0);

        var mergePID = this.getMergePid();
        this.#check_merge_btn.disabled = ! (remainPID > 0 && mergePID > 0);
    }

    findCandidates() {
        this.#mergePid.value = "";
        this.btnctl();
        this.#mergeCheck_div.innerHTML = "";
        if (this.#mergeCandidatesTable) {
            this.#mergeCandidatesTable.destroy();
            this.#mergeCandidatesTable = null;
        }
        var script = "scripts/mergeFindCandidates.php";
        var data = {
            remain: this.#remainPid.value,
            matchCount: 5,
        }
        $.ajax({
            url: script,
            method: 'POST',
            data: data,
            success: function (data, textStatus, jhXHR) {
                merge.drawCandidates(data, textStatus, jhXHR);
            },
            error: function (jqXHR, textStatus, errorThrown) {
                showError("ERROR in " + script + ": " + textStatus, jqXHR);
                return false;
            }
        });
    }

    addActionButtons(cell, formatterParams, onRendered) {
        var btns = "";
        var data = cell.getData();
        var index = cell.getRow().getIndex();
        var thisItem = data['id'];

        btns += '<button class="btn btn-primary" style = "--bs-btn-padding-y: .0rem; --bs-btn-padding-x: .3rem; --bs-btn-font-size: .75rem;" onclick="merge.makeRemain(' + thisItem + ')">Make Remain</button>';
        btns += '<button class="btn btn-secondary" style = "--bs-btn-padding-y: .0rem; --bs-btn-padding-x: .3rem; --bs-btn-font-size: .75rem;" onclick="merge.makeMerge(' + thisItem + ')">Make Merge</button>';

        return btns;
    }
    drawCandidates(data, textStatus, jhXHR) {
        console.log(data);

        if (data['error']) {
            showError(data['error'], textStatus, jhXHR);
            return;
        }
        var candidatekey = Object.keys(data['values'])[0];
        var candidates = data['values'][candidatekey];

        this.#mergeCandidatesTable = new Tabulator('#mergeCandidates', {
            maxHeight: "600px",
            movableRows: false,
            history: false,
            data: candidates,
            layout: "fitDataTable",
            columns: [
                { title: "Actions", headerFilter: false, headerSort: false, formatter: merge.addActionButtons,},
                { title: "ID", field: "id", headerSort: true, },
                { title: "Last Name", field: "last_name", headerSort: true, },
                { title: "First Name", field: "first_name", headerSort: true, },
                { title: "Middle Name", field: "Middle_name", headerSort: true, },
                { title: "Suffix", field: "Suffix", headerSort: true, },
                { title: "Badge Name", field: "badge_name", headerSort: true, },
                { title: "Email Address", field: "email_addr", headerSort: true, },
                { title: "Address", field: "address", headerSort: true, },
                { title: "Address 2", field: "addr_2", headerSort: true, },
                { title: "City", field: "city", headerSort: true, },
                { title: "State", field: "state", headerSort: true, },
                { title: "Zip", field: "zip", headerSort: true, },
                { title: "Country", field: "Country", headerSort: true, },

            ]
        });
    }

    makeMerge(pid) {
        this.#mergePid.value = pid;
        this.btnctl();
    }

    makeRemain(pid) {
        this.#remainPid.value = pid;
        this.btnctl();
    }

    checkMerge() {
        var mergePid = this.getMergePid();
        var remainPid = this.getRemainPid();

        if (!(mergePid > 0 && remainPid > 0))
            return;

        this.#mergeCheck_div.innerHTML = "";
        var script = "scripts/mergeCheckCandidates.php";
        var data = {
            merge: mergePid,
            remain: remainPid,
        }
        $.ajax({
            url: script,
            method: 'POST',
            data: data,
            success: function (data, textStatus, jhXHR) {
                merge.drawCheck(data, textStatus, jhXHR);
            },
            error: function (jqXHR, textStatus, errorThrown) {
                showError("ERROR in " + script + ": " + textStatus, jqXHR);
                return false;
            }
        });
    }

    drawCheck(data, textStatus, jhXHR) {
        console.log(data);

        if (data['error']) {
            showError(data['error'], textStatus, jhXHR);
            return;
        }

        var values = data['values'];
        var mergeArr = values['merge'];
        var remainArr = values['remain'];

        var html = `
<strong>Verify your merge request:</strong><br/>&nbsp;<br/>
<i>Remaining Person</i><br/>
"` + remainArr.join('","') + `"<br/>
<i>Merged Person (will go away)</i><br/>
"` + mergeArr.join('","') + `"<br/>&nbsp;<br/>
<button id="perform-merge" type="button" class="btn btn-primary btn-sm" onclick="merge.performMerge(` + remainArr[0] + `, ` + mergeArr[0] + `); return false;">Perform Merge</button>
`;
        this.#mergeCheck_div.innerHTML = html;
    }

    performMerge(remainPID, mergePID) {
        if (!(remainPID > 0 && mergePID > 0))
            return;

        var script = "scripts/mergeExecuteMerge.php";
        var data = {
            merge: mergePID,
            remain: remainPID,
        }
        $.ajax({
            url: script,
            method: 'POST',
            data: data,
            success: function (data, textStatus, jhXHR) {
                merge.findCandidates();
            },
            error: function (jqXHR, textStatus, errorThrown) {
                showError("ERROR in " + script + ": " + textStatus, jqXHR);
                return false;
            }
        });
    }
};
