//import { TabulatorFull as Tabulator } from 'tabulator-tables';
// merge class - all merge functions
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
    #find_modal = null;
    #mergeTitle = null;
    #mergeName = null;
    #findType = null;
    #find_result_table = null;
    #find_pattern_field = null;


    // globals before open
    constructor() {
        this.#message_div = document.getElementById('test');
        this.#merge_pane = document.getElementById('merge-pane');
        // set listen to CR on find name field
        this.#find_pattern_field = document.getElementById("merge_name_search");
        this.#find_pattern_field.addEventListener('keyup', (e)=> { if (e.code === 'Enter') merge_find('search'); });
    };

    // return the merge perid field
    getMergePid() {
        if (this.#mergePid)
            return this.#mergePid.value;
        return null;
    }

    // return the remain pid field
    getRemainPid() {
        if (this.#remainPid)
            return this.#remainPid.value;
        return null;
    }

    // called on open of the merge window
    open() {
        var html = `<h4><strong>Merge People:</strong></h4>
<div class="container-fluid">
    <div class="row">
        <div class="col-sm-2 p-2"><label for="remainPid">Perid to remain:</label></div>
        <div class="col-sm-2 p-2"><input  class='form-control-sm' type="number" id="remainPid" name="remainPid" placeholder='perid to remain' onchange="merge.btnctl()"></div>
        <div class="col-sm-4 p-2"><button id="remain_find_merge" type="button" class="btn btn-primary btn-sm" onclick="merge.findPerson('remain'); return false;">Find to Remain Person</button></div>
    </div>
    <div class="row">
        <div class="col-sm-2 p-2"><label for="mergePid">Perid to merge in remain:</label></div>
        <div class="col-sm-2 p-2"><input  class='form-control-sm' type="number" id="mergePid" name="mergePid" placeholder='perid to merge' onchange="merge.btnctl()"></div>
        <div class="col-sm-4 p-2"><button id="merge_find_merge" type="button" class="btn btn-primary btn-sm" onclick="merge.findPerson('merge'); return false;">Find to Merge Person</button></div>
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
        if (this.#find_modal == null) { // only need to call on first open.
            var id = document.getElementById('merge-lookup');
            if (id != null) {
                this.#find_modal = new bootstrap.Modal(id, {focus: true, backdrop: 'static'});
                this.#mergeTitle = document.getElementById('mergeTitle');
                this.#mergeName = document.getElementById('mergeName');
                id.addEventListener('shown.bs.modal', () => {
                    this.#find_pattern_field.focus()
                })
            }
        }
    }

    // on close of the pane, clean up the items
    close() {
        if (this.#mergeCandidatesTable) {
            this.#mergeCandidatesTable.destroy();
            this.#mergeCandidatesTable = null;
        }
        if (this.#find_result_table) {
            this.#find_result_table.destroy();
            this.#find_result_table = null;
        }
        this.#merge_pane.innerHTML = '';
        this.#findType = null;
    };

    // manage which buttons need to be active
    //  find candidates: if there is a remain PID
    //  check_merge if both are filled in
    btnctl() {
        var remainPID = this.getRemainPid();
        this.#find_candidate_btn.disabled =  !(remainPID > 0);

        var mergePID = this.getMergePid();
        this.#check_merge_btn.disabled = ! (remainPID > 0 && mergePID > 0);
    }

    // find the candidates to merge into this Remain PID
    findCandidates(data = null) {
        var clear_error = true;
        if (data) {
            if (data['error']) {
                show_message($data['error'], 'error');
                clear_error = false;
            } else if (data['success']) {
                show_message(data['success'] + ': ' + data['status'], 'success');
                clear_error = false;
            }
        }
        var remainPID = this.#remainPid.value;
        if (remainPID == null || remainPID == '' || remainPID <= 0)
            return; // no values to find

        this.#mergePid.value = "";
        this.btnctl();
        this.#mergeCheck_div.innerHTML = "";
        if (this.#mergeCandidatesTable) {
            this.#mergeCandidatesTable.destroy();
            this.#mergeCandidatesTable = null;
        }
        if (clear_error)
            clearError();
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

    // for tabulator, add buttons to have a candidate become remain or merge perid
    addActionButtons(cell, formatterParams, onRendered) {
        var btns = "";
        var data = cell.getData();
        var index = cell.getRow().getIndex();
        var thisItem = data['id'];

        btns += '<button class="btn btn-primary" style = "--bs-btn-padding-y: .0rem; --bs-btn-padding-x: .3rem; --bs-btn-font-size: .75rem;" onclick="merge.makeRemain(' + thisItem + ')">Make Remain</button>';
        btns += '<button class="btn btn-secondary" style = "--bs-btn-padding-y: .0rem; --bs-btn-padding-x: .3rem; --bs-btn-font-size: .75rem;" onclick="merge.makeMerge(' + thisItem + ')">Make Merge</button>';

        return btns;
    }

    // draw the candidates tabulator box
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

    // update mergePID with selected value (on make Merge button click)
    makeMerge(pid) {
        this.#mergePid.value = pid;
        this.btnctl();
        clear_message();
    }

    // update remainPID with selected value (on make Remain button click)
    makeRemain(pid) {
        this.#remainPid.value = pid;
        this.btnctl();
        clear_message();
    }

    // retrieve the values to display to confirm the merge before executing it
    checkMerge() {
        var mergePid = this.getMergePid();
        var remainPid = this.getRemainPid();

        if (!(mergePid > 0 && remainPid > 0))
            return;

        clearError();
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

    // display results of check merge ajax call
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

    // execute the merge
    performMerge(remainPID, mergePID) {
        if (!(remainPID > 0 && mergePID > 0))
            return;

        clearError();
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
                merge.findCandidates(data);
            },
            error: function (jqXHR, textStatus, errorThrown) {
                showError("ERROR in " + script + ": " + textStatus, jqXHR);
                return false;
            }
        });
    }

    // set the modal up and display the modal to find a person by name/...
    findPerson(findType) {
        this.#find_modal.show();
        this.#findType = findType;
        if (this.#findType == 'remain') {
            this.#mergeTitle.innerHTML = 'Lookup Person to remain after Merge';
            this.#mergeName.innerHTML = 'Remaining Person Name:';
        } else {
            this.#mergeTitle.innerHTML = 'Lookup Person to Merge';
            this.#mergeName.innerHTML = 'Person Name to Merge:';
        }
    }

    // get the list of people for the match
    merge_find() {
        if (this.#findType == null | this.#findType == '')
            return;

        clear_message('result_message_merge');
        clear_message();
        var name_search = document.getElementById('merge_name_search').value.toLowerCase().trim();
        if (name_search == null || name_search == '')  {
            show_message("No search criteria specified", "warn", 'result_message_merge');
            return;
        }

        // search for matching names
        $("button[name='mergeSearch']").attr("disabled", true);
        test.innerHTML = '';
        clear_message('result_message_merge');
        if (this.#find_result_table) {
            this.#find_result_table.destroy();
            this.#find_result_table = null;
        }

        clearError();
        $.ajax({
            method: "POST",
            url: "scripts/mergeFindRecord.php",
            data: { name_search: name_search, },
            success: function (data, textstatus, jqxhr) {
                $("button[name='mergeSearch']").attr("disabled", false);
                if (data['error'] !== undefined) {
                    show_message(data['error'], 'error', 'result_message_merge');
                    return;
                }
                if (data['message'] !== undefined) {
                    show_message(data['message'], 'success', 'result_message_merge');
                }
                if (data['warn'] !== undefined) {
                    show_message(data['warn'], 'warn', 'result_message_merge');
                }
                merge_found(data);
            },
            error: function (jqXHR, textStatus, errorThrown) {
                $("button[name='mergeSearch']").attr("disabled", false);
                showError("ERROR in mergeFindRecord: " + textStatus, jqXHR);
            }
        });
    }

    // merge_found - display a list of potential merge recipients
    merge_found(data) {
        var perinfo = data['perinfo'];
        var name_search = data['name_search'];
        if (perinfo.length > 0) {
            this.#find_result_table = new Tabulator('#merge_search_results', {
                maxHeight: "600px",
                data: perinfo,
                layout: "fitColumns",
                initialSort: [
                    {column: "fullname", dir: "asc"},
                ],
                columns: [
                    {width: 70, headerFilter: false, headerSort: false, formatter: addMergeIcon, formatterParams: {t: "result"},},
                    {title: "perid", field: "perid",width: 100, hozAlign: 'right' },
                    {field: "index", visible: false,},
                    {field: "regcnt", visible: false,},
                    {title: "Name", field: "fullname", width: 200, headerFilter: true, headerWordWrap: true, tooltip: build_record_hover,},
                    {field: "last_name", visible: false,},
                    {field: "first_name", visible: false,},
                    {field: "middle_name", visible: false,},
                    {field: "suffix", visible: false,},
                    {title: "Badge Name", field: "badge_name", width: 200, headerFilter: true, headerWordWrap: true, tooltip: true,},
                    {title: "Zip", field: "postal_code", headerFilter: true, headerWordWrap: true, tooltip: true, maxWidth: 100, width: 100},
                    {title: "Email Address", field: "email_addr", width: 200, headerFilter: true, headerWordWrap: true, tooltip: true,},
                    {title: "Current Badges", field: "regs", headerFilter: true, headerWordWrap: true, tooltip: true,},
                    {field: "index", visible: false,},
                ],
            });
        }
    }
    
    selectPerson(perid) {
        if (this.#findType == 'merge') {
            this.#mergePid.value = perid;
        }
        if (this.#findType == 'remain') {
            this.#remainPid.value = perid;
        }

        if (this.#find_result_table) {
            this.#find_result_table.destroy();
            this.#find_result_table = null;
        }
        this.#findType = null;
        document.getElementById('merge_name_search').value = '';
        clear_message('result_message_merge');
        this.#find_modal.hide();
        this.btnctl();
        return;
    }
};

// pass to the class functions
function merge_find() {
    merge.merge_find();
}

function merge_found(data) {
    merge.merge_found(data);
}

function selectPerson(perid) {
    merge.selectPerson(perid);
}

// show the full perinfo record as a hover in the table
function build_record_hover(e, cell, onRendered) {
    var data = cell.getData();
    //console.log(data);
    var hover_text = 'Person id: ' + data['perid'] + '<br/>' +
        (data['first_name'] + ' ' + data['middle_name'] + ' ' + data['last_name']).trim() + '<br/>' +
        data['address_1'] + '<br/>';
    if (data['address_2'] != '') {
        hover_text += data['address_2'] + '<br/>';
    }
    hover_text += data['city'] + ', ' + data['state'] + ' ' + data['postal_code'] + '<br/>';
    if (data['country'] != '' && data['country'] != 'USA') {
        hover_text += data['country'] + '<br/>';
    }
    hover_text += 'Badge Name: ' + badge_name_default(data['badge_name'], data['first_name'], data['last_name']) + '<br/>' +
        'Email: ' + data['email_addr'] + '<br/>' + 'Phone: ' + data['phone'] + '<br/>' +
        'Active:' + data['active'] + ' Contact?:' + data['contact_ok'] + ' Share?:' + data['share_reg_ok'] + '<br/>';

    return hover_text;
}

// badge_name_default: build a default badge name if its empty
function badge_name_default(badge_name, first_name, last_name) {
    if (badge_name === undefined | badge_name === null || badge_name === '') {
        var default_name = (first_name + ' ' + last_name).trim();
        return '<i>' + default_name.replace(/ +/, ' ') + '</i>';
    }
    return badge_name;
}

// tabulator formatter for the merge column for the find results, displays the "Select" to mark the membrership merge
function addMergeIcon(cell, formatterParams, onRendered) { //plain text value
    var tid;
    var html = '';
    var banned = cell.getRow().getData().banned == 'Y';
    var regcnt = cell.getRow().getData().regcnt;
    var color = 'btn-success';
    var perid = cell.getRow().getData().perid;

    return '<button type="button" class="btn btn-sm ' + color + ' pt-0 pb-0" style="--bs-btn-font-size: 75%;" onclick="selectPerson(' + perid + ')">Select</button>';
}
