// lookup registrations

// locations
findPattern = null;

// tabulator
lookupTable = null;

// debug meaning
//  1 = console.logs
//  2 = show hidden table fields
//  4 = show hidden div

// initialization at DOM complete
window.onload = function initpage() {
    findPattern = document.getElementById('find_pattern');
    findPattern.focus();
    findPattern.addEventListener('keyup', (e)=> { if (e.code === 'Enter') findRegs(); });
}

function findRegs() {
    var searchPattern = findPattern.value;
    clear_message();
    if (searchPattern.toString().length == 0) {
        show_message("The search pattern is empty, please enter a person id, transaction id, or at least 3 characters of the name/address/email/badgename", 'error');
        return;
    }

    if (isNaN(searchPattern)) {
        if (searchPattern.toString().length < 3) {
            show_message("The search pattern is empty, please enter a person id, transaction id, or at least 3 characters of the name/address/email/badgename", 'error');
            return;
        }
    } else {
        searchPattern = Number(searchPattern);
        if (searchPattern < 1) {
            show_message("Person id's and Transaction id's are positive numbers greater than 0.", 'error');
            return;
        }
    }

    // now call the script to return the memberships for this search
    var postData = {
        pattern: searchPattern,
        action: 'lookup'
    }
    $.ajax({
        method: "POST",
        url: "scripts/lookup_search.php",
        data: postData,
        success: function (data, textstatus, jqxhr) {
            if (data.error !== undefined) {
                show_message(data.error, 'error');
                return;
            }
            if (data.message !== undefined) {
                show_message(data.message, 'success');
            }
            drawResults(data);
        },
        error: showAjaxError,
    });
}

function drawResults(data) {
    if (lookupTable) {
        lookupTable.replaceData(data.matches);
        return;
    }

    lookupTable = new Tabulator('#lookupTable', {
        data: data.matches,
        layout: "fitDataTable",
        index: "perid",
        columns: [
            { title: "TID", field: "tid", hozAlign: "right",  headerSort: true, headerFilter: true },
            { title: "PID", field: "perid", width: 110, hozAlign: "right", headerSort: true, headerFilter: true, },
            { title: "Person", field: "fullName", headerSort: true, headerFilter: true },
            { title: "Badge Name", field: "badge_name", headerSort: true, headerFilter: true },
            { title: "Email", field: "email_addr", headerSort: true, headerFilter: true },
            { title: "Membership Type", field: "label", width: 300, headerSort: true, headerFilter: true, },
            { title: "Price", field: "price", hozAlign: "right", headerSort: true, headerFilter: true, headerFilterFunc:numberHeaderFilter, },
            { title: "Paid", field: "paid", hozAlign: "right", headerSort: true, headerFilter: true, headerFilterFunc:numberHeaderFilter, },
            { title: "Status", field: "status", headerSort: true, headerFilter: true, },
            { title: "Date Created", field: "create_date", headerSort: true, headerFilter: true },
            { title: "Date Changed", field: "change_date", headerSort: true, headerFilter: true },
            { title: "Paid Date", field: "paidDate", headerSort: true, headerFilter: true },
        ],
        initialSort: [
            {column: "tid", dir: "desc" },
            {column: "change_date", dir: "desc" },
        ],
    });
}
