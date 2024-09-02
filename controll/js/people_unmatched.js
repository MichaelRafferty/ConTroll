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
                {title: "Manages", field: "manages", width: 90, headerHozAlign:"right", hozAlign: "right", headerSort: true},
                {title: "Manager", field: "manager", width: 150, headerSort: true, headerFilter: true, },
                {title: "Last Name", field: "last_name", width: 150, headerSort: true, headerFilter: true, },
                {title: "First Name", field: "first_name", width: 150, headerSort: true, headerFilter: true, },
            ],
        });
    }

    // table related functions
    // display edit button for a long field
    matchButton(cell, formatterParams, onRendered) {
        var index = cell.getRow().getIndex()
        return '<button class="btn btn-primary" style = "--bs-btn-padding-y: .0rem; --bs-btn-padding-x: .3rem; --bs-btn-font-size: .75rem;",' +
            ' onclick="unmatchedPeople.matchPerson(' + index + ');">Match</button>';
    }

    matchPerson(id) {
        console.log(id);
    }

    // on close of the pane, clean up the items
    close() {
         if (this.#unmatchedTable != null) {
            this.#unmatchedTable.destroy();
            this.#unmatchedTable = null;
        }
    };
}