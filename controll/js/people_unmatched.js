//import { TabulatorFull as Tabulator } from 'tabulator-tables';
// policy class - all edit membership policy functions
class Unmatched {
    #messageDiv = null;
    #unmatchedPane = null;
    #unmatchedTable = null;
    #unmatchedCountSpan = null;
    #unmatchedCount = null;
    #unmatchedH1 = null;
    #unmatched = null

    #debug = 0;
    #debugVisible = false;

    // candidate modal
    #matchCandidatesModal = null;
    #candidatesTitleName = null;
    #candidates = null;
    #newperson = null;
    #newpersonTable = null;
    #candidatesName = null;
    #candidateTable = null;

    // globals before open
    constructor(debug) {
        this.#debug = debug;
        if (this.#debug & 2) {
            this.#debugVisible = true;
        }
        this.#messageDiv = document.getElementById('message');
        this.#unmatchedPane = document.getElementById('unmatched-pane');
        this.#unmatchedH1 = document.getElementById('unmatchedH1Div');
        this.#unmatchedCountSpan = document.getElementById('unmatchedCount');

        var id = document.getElementById('match-candidates');
        if (id) {
            this.#matchCandidatesModal = new bootstrap.Modal(id, {focus: true, backdrop: 'static'});
            this.#candidatesTitleName = document.getElementById('candidatesTitleName');
            this.#candidatesName = document.getElementById('candidatesName');
        }
    };

    // called on open of the policy window
    open() {
        var _this = this;
        var script = "scripts/people_getUnmatched.php";
        var postdata = {
            ajax_request_action: 'unmatched',
        };
        clear_message();
        $.ajax({
            url: script,
            method: 'POST',
            data: postdata,
            success: function (data, textStatus, jhXHR) {
                _this.draw(data);
            },
            error: function (jqXHR, textStatus, errorThrown) {
                showError("ERROR in " + script + ": " + textStatus, jqXHR);
                return false;
            }
        });
    }

    // draw the policy edit screen
    draw(data) {
        var _this = this;

        if (this.#unmatchedTable != null) {
            this.#unmatchedTable.destroy();
            this.#unmatchedTable = null;
        }
        if (!data['unmatched']) {
            show_message("Error loading unmatched people", 'error');
            return;
        }
        this.#unmatched = data['unmatched'];
        this.#unmatchedCount = data['numUnmatched'];
        this.#unmatchedCountSpan.innerHTML = this.#unmatchedCount;
        this.#unmatchedTable = new Tabulator('#unmatchedTable', {
            data: this.#unmatched,
            layout: "fitDataTable",
            index: "id",
            pagination: true,
            paginationAddRow:"table",
            paginationSize: 10,
            paginationSizeSelector: [10, 25, 50, 100, 250, true], //enable page size select element with these options
            columns: [
                {title: "Match", formatter: this.matchButton, headerSort: false },
                {title: "ID", field: "id", width: 80, headerHozAlign:"right", hozAlign: "right", headerSort: true},
                {title: "Manages", field: "manages", width: 90, headerHozAlign:"right", hozAlign: "right", headerSort: false},
                {title: "Mgr Type", field: "managerType", headerWordWrap: true, width: 50,headerSort: false },
                {title: "Manager", field: "manager", width: 150, headerSort: true, headerFilter: true, },
                {title: "Full Name", field: "fullName", width: 300, headerSort: true, headerFilter: true, },
                {title: "Email", field: "email_addr", width: 200, headerSort: true, headerFilter: true, },
                {title: "Date Created", field: "createtime", width: 180, headerSort: true, headerFilter: true, },
                {title: "Num Regs", field: "numRegs", width: 50, headerWordWrap: true, headerHozAlign:"right", hozAlign: "right", headerSort: false},
                {title: "Price", field: "price", width: 80, headerHozAlign:"right", hozAlign: "right", headerSort: false},
                {title: "Paid", field: "paid", width: 80, headerHozAlign:"right", hozAlign: "right", headerSort: false},

            ],
        });
    }

    // table related functions
    // display match button unmatched new people
    matchButton(cell, formatterParams, onRendered) {
        var row = cell.getRow();
        var index = row.getIndex()
        var managerType = row.getData().managerType;
        if (managerType != 'n') {
            return '<button class="btn btn-primary" style = "--bs-btn-padding-y: .0rem; --bs-btn-padding-x: .3rem; --bs-btn-font-size: .75rem;",' +
                ' onclick="unmatchedPeople.matchPerson(' + index + ');">Match</button>';
        }
        return "";
    }
    // display select button for candidate people
    selectButton(cell, formatterParams, onRendered) {
        var row = cell.getRow();
        var index = row.getIndex()
        var personType = formatterParams.table;

        return '<button class="btn btn-primary" style = "--bs-btn-padding-y: .0rem; --bs-btn-padding-x: .3rem; --bs-btn-font-size: .75rem;",' +
                ' onclick="unmatchedPeople.selectPerson(\'' + personType + '\', ' + index + ');">Select</button>';
    }

    // ok to match a person we need to do the following
    //  1. check if their manager is not matched and deny matching them
    //      (this step is already completed ny the show match algorithm, if there isn't a match button because the manager type is 'n')
    //  2. find all possible suggestions of people that might match this person
    //  3. offer to use of those or a new person
    //  4. select action
    //      a. if new, keep association, allow editing the profile and the saving as a new person
    //      b. if existing, allow to merge change data from the two records
    //      c. if mamnaged, offer to keep/break the management association,
    //          with the option to send an email and have the user respond they want to stay associated.
    //
    // matchPerson - get the candidates to match against a new person
    matchPerson(id) {
        var _this = this;
        var script = "scripts/people_getMatchCandidates.php";
        var postdata = {
            ajax_request_action: 'match',
            newperid: id,
        };
        clear_message();
        $.ajax({
            url: script,
            method: 'POST',
            data: postdata,
            success: function (data, textStatus, jhXHR) {
                _this.showCandidates(data);
            },
            error: function (jqXHR, textStatus, errorThrown) {
                showError("ERROR in " + script + ": " + textStatus, jqXHR);
                return false;
            }
        });
    }

    // showCandidates - open the modal to display the candidates for this match
    showCandidates(data) {
        console.log(data);
        if (data['error']) {
            show_message(data['error'], 'error');
            return;
        }
        this.#candidates = data['matches'];
        this.#newperson = data['newperson']
        var newpeople = [];
        newpeople.push(this.#newperson);
        this.#candidatesTitleName.innerHTML = this.#newperson.fullName;
        this.#candidatesName.innerHTML = this.#newperson.fullName;
        this.#newpersonTable = new Tabulator('#newpersonTable', {
            data: newpeople,
            layout: "fitDataTable",
            index: "id",
            columns: [
                {title: "Select", width: 100, formatter: this.selectButton, formatterParams: {table: 'n'}, headerSort: false },
                {title: "ID", field: "id", width: 80, headerHozAlign:"right", hozAlign: "right", headerSort: true},
                {title: "Full Name", field: "fullName", width: 300, headerSort: true, headerFilter: true, },
                {title: "Address", field: "fullAddr", width: 300, headerSort: true, headerFilter: true, },
                {title: "Badge Name", field: "badge_name", width: 150, headerFilter:true, headerSort: false},
                {title: "Manager", field: "manager", width: 150, headerSort: true, headerFilter: true, },
                {title: "Email", field: "email_addr", width: 200, headerSort: true, headerFilter: true, },
                {title: "Phone", field: "phone", width: 100, headerSort: true, headerFilter: true, },
                {title: "Date Created", field: "createtime", width: 180, headerSort: true, headerFilter: true, },
                {title: "Registrations", field: "regs", width: 300, },
            ],
        });
        this.#candidateTable = new Tabulator('#candidateTable', {
            data: this.#candidates,
            layout: "fitDataTable",
            index: "id",
            pagination: true,
            paginationAddRow:"table",
            paginationSize: 10,
            paginationSizeSelector: [10, 25, 50, 100, 250, true], //enable page size select element with these options
            columns: [
                {title: "Select", width: 100, formatter: this.selectButton, formatterParams: {table: 'p'}, headerSort: false },
                {title: "ID", field: "id", width: 80, headerHozAlign:"right", hozAlign: "right", headerSort: true},
                {title: "Full Name", field: "fullName", width: 300, headerSort: true, headerFilter: true, },
                {title: "Address", field: "fullAddr", width: 300, headerSort: true, headerFilter: true, },
                {title: "Badge Name", field: "badge_name", width: 150, headerFilter:true, headerSort: false},
                {title: "Manager", field: "manager", width: 150, headerSort: true, headerFilter: true, },
                {title: "Email", field: "email_addr", width: 200, headerSort: true, headerFilter: true, },
                {title: "Phone", field: "phone", width: 100, headerSort: true, headerFilter: true, },
                {title: "Date Created", field: "creation_date", width: 180, headerSort: true, headerFilter: true, },
                {title: "Registrations", field: "regs", width: 300, },
            ],
        });

        this.#matchCandidatesModal.show();
        show_message(data['success'], 'success', 'result_message_candidate');
    }

    // on close of the pane, clean up the items
    close() {
         if (this.#unmatchedTable != null) {
            this.#unmatchedTable.destroy();
            this.#unmatchedTable = null;
        }
    };
}