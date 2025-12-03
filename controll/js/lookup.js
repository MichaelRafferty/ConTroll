// lookup registrations

// locations
findPattern = null;

// tabulator
lookupTable = null;
lookupdata = [];

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
        name_search: searchPattern,
        action: 'lookup',
        find_type: 'lookup',
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
            if (data.success !== undefined) {
                show_message(data.success, 'success');
            }
            drawResults(data);
        },
        error: showAjaxError,
    });
}

function drawResults(data) {
    let perdata = data.perinfo;
    let membership = null;
    lookupdata = [];
    for (let i = 0; i < perdata.length; i++) {
        let peritem = perdata[i];
        let numMems = peritem.memberships.length
        if (numMems == 0)
            continue;
        for (let j = 0; j < numMems; j++) {
            membership = peritem.memberships[j];
            let row = [];
            row.tid = (membership.tid2 != null && membership.tid2 > 0) ? membership.tid2 : membership.tid;
            row.perid = peritem.perid;
            row.fullName = peritem.fullName;
            row.badgename = peritem.badgename;
            row.badge_name = peritem.badge_name;
            row.badgeNameL2 = peritem.badgeNameL2;
            row.email_addr = peritem.email_addr;
            row.managerId = peritem.managedBy;
            row.managerName = peritem.mgrFullName;
            row.label = (isPrimary(membership.conid, membership.memType, membership.memCategory, membership.price) ?
                (membership.printcount + ':') : '') + membership.label;
            row.price = membership.price;
            row.paid = membership.paid;
            row.status = membership.status;
            row.create_date = membership.create_date;
            row.change_date = membership.change_date;
            row.paidDate = membership.complete_date;
            row.first_name = peritem.first_name;
            row.middle_name = peritem.middle_name;
            row.last_name = peritem.last_name;
            row.suffix = peritem.suffix;
            lookupdata.push(row);
        }
    }
    if (lookupTable) {
        lookupTable.replaceData(lookupdata);
        return;
    }

    lookupTable = new Tabulator('#lookupTable', {
        data: lookupdata,
        layout: "fitDataTable",
        index: "perid",
        columns: [
            { title: "TID", field: "tid", hozAlign: "right",  headerSort: true, headerFilter: true },
            { title: "PID", field: "perid", width: 110, hozAlign: "right", headerSort: true, headerFilter: true, },
            { title: "Person", field: "fullName", headerSort: true, headerFilter: true, headerFilterFunc: fullNameHeaderFilter, },
            { title: "Badge Name", field: "badgename", headerSort: true, headerFilter: true, formatter: 'html', },
            { title: "Email", field: "email_addr", headerSort: true, headerFilter: true },
            { title: "Mgr PID", field: "managerId", width: 110, hozAlign: "right", headerSort: true, headerFilter: true },
            { title: "Mgr Name", field: "managerName", headerSort: true, headerFilter: true },
            { title: "Membership Type", field: "label", headerSort: true, headerFilter: true, },
            { title: "Price", field: "price", hozAlign: "right", headerSort: true, headerFilter: true, headerFilterFunc:numberHeaderFilter, },
            { title: "Paid", field: "paid", hozAlign: "right", headerSort: true, headerFilter: true, headerFilterFunc:numberHeaderFilter, },
            { title: "Status", field: "status", headerSort: true, headerFilter: true, },
            { title: "Date Created", field: "create_date", headerSort: true, headerFilter: true },
            { title: "Date Changed", field: "change_date", headerSort: true, headerFilter: true, visible: false},
            { title: "Paid Date", field: "paidDate", headerSort: true, headerFilter: true },
            {field: 'first_name', visible: false,},
            {field: 'middle_name', visible: false,},
            {field: 'last_name', visible: false,},
        ],
        initialSort: [
            {column: "tid", dir: "desc" },
            {column: "change_date", dir: "desc" },
        ],
    });
}
