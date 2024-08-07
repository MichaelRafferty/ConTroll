var badgetable = null;
var category = null;
var type = null;
var paid = null;
var label = null;
var age = null;
var coupon = null;
var statusTable = null;
var typefilter = null;
var catfilter = null;
var agefilter = null;
var paidfilter = null;
var labelfilter = null;
var couponfilter = null;
var statusfilter = null;
var transfer_modal = null;
var receipt_modal = null;
var receipt_email_address = null;
var find_result_table = null;
var find_pattern_field = null;
var testdiv = null;
var conid = 0;

$(document).ready(function () {
    id = document.getElementById('transfer_to');
    if (id != null) {
        transfer_modal = new bootstrap.Modal(id, { focus: true, backdrop: 'static' });
        find_pattern_field = document.getElementById('transfer_name_search')
        find_pattern_field.addEventListener('keyup', (e)=> { if (e.code === 'Enter') transfer_find(); });
        id.addEventListener('shown.bs.modal', () => {
            find_pattern_field.focus()
        })
    }

    id = document.getElementById('receipt');
    if (id != null) {
        receipt_modal = new bootstrap.Modal(id, { focus: true, backdrop: 'static' });
        $('#receipt').on('hide.bs.modal', function () {
            receipt_email_address = null;
        });
    }

    testdiv = document.getElementById('test');
    getData();
});

function catclicked(e, cell) {
    var filtercell = cell.getRow().getCell("memCategory");
    var value = filtercell.getValue();
    if (filtercell.getElement().style.backgroundColor) {
        badgetable.removeFilter("category", "in", catfilter);
        catfilter = catfilter.filter(arrayItem => arrayItem !== value);
        if (catfilter.length > 0) {
            badgetable.addFilter("category", "in", catfilter);
        }
        filtercell.getElement().style.backgroundColor = "";
    } else {
        if (catfilter.length > 0) {
            badgetable.removeFilter("category", "in", catfilter);
        }
        catfilter.push(value);
        badgetable.addFilter("category", "in", catfilter);
        filtercell.getElement().style.backgroundColor = "#C0FFC0";
    }
}

function typeclicked(e, cell) {
    var filtercell = cell.getRow().getCell("memType");
    var value = filtercell.getValue();
    if (filtercell.getElement().style.backgroundColor) {
        badgetable.removeFilter("type", "in", typefilter);
        typefilter = typefilter.filter(arrayItem => arrayItem !== value);
        if (typefilter.length > 0) {
            badgetable.addFilter("type", "in", typefilter);
        }
        filtercell.getElement().style.backgroundColor = "";
    } else {
        if (typefilter.length > 0) {
            badgetable.removeFilter("type", "in", typefilter);
        }
        typefilter.push(value);
        badgetable.addFilter("type", "in", typefilter);
        filtercell.getElement().style.backgroundColor = "#C0FFC0";
    }
}

function ageclicked(e, cell) {
    var filtercell = cell.getRow().getCell("memAge");
    var value = filtercell.getValue();
    if (filtercell.getElement().style.backgroundColor) {
        badgetable.removeFilter("age", "in", agefilter);
        agefilter = agefilter.filter(arrayItem => arrayItem !== value);
        if (agefilter.length > 0) {
            badgetable.addFilter("age", "in", agefilter);
        }
        filtercell.getElement().style.backgroundColor = "";
    } else {
        if (agefilter.length > 0) {
            badgetable.removeFilter("age", "in", agefilter);
        }
        agefilter.push(value);
        badgetable.addFilter("age", "in", agefilter);
        filtercell.getElement().style.backgroundColor = "#C0FFC0";
    }
}

function paidclicked(e, cell) {
    var filtercell = cell.getRow().getCell("paid");
    var value = filtercell.getValue();
    if (filtercell.getElement().style.backgroundColor) {
        badgetable.removeFilter("paid", "in", paidfilter);
        paidfilter = paidfilter.filter(arrayItem => arrayItem !== value);
        if (paidfilter.length > 0) {
            badgetable.addFilter("paid", "in", paidfilter);
        }
        filtercell.getElement().style.backgroundColor = "";
    } else {
        if (paidfilter.length > 0) {
            badgetable.removeFilter("paid", "in", paidfilter);
        }
        paidfilter.push(value);
        badgetable.addFilter("paid", "in", paidfilter);
        filtercell.getElement().style.backgroundColor = "#C0FFC0";
    }
}

function labelclicked(e, cell) {
    var filtercell = cell.getRow().getCell("label");
    var value = filtercell.getValue();
    if (filtercell.getElement().style.backgroundColor) {
        badgetable.removeFilter("label", "in", labelfilter);
        labelfilter = labelfilter.filter(arrayItem => arrayItem !== value);
        if (labelfilter.length > 0) {
            badgetable.addFilter("label", "in", labelfilter);
        }
        filtercell.getElement().style.backgroundColor = "";
    } else {
        if (labelfilter.length > 0) {
            badgetable.removeFilter("label", "in", labelfilter);
        }
        labelfilter.push(value);
        badgetable.addFilter("label", "in", labelfilter);
        filtercell.getElement().style.backgroundColor = "#C0FFC0";
    }
}

function couponclicked(e, cell) {
    var filtercell = cell.getRow().getCell("name");
    value = filtercell.getValue();
    if (filtercell.getElement().style.backgroundColor) {
        badgetable.removeFilter("name", "in", couponfilter);
        couponfilter = couponfilter.filter(arrayItem => arrayItem !== value);
        if (couponfilter.length > 0) {
            badgetable.addFilter("name", "in", couponfilter);
        }
        filtercell.getElement().style.backgroundColor = "";
    } else {
        if (couponfilter.length > 0) {
            badgetable.removeFilter("name", "in", couponfilter);
        }
        couponfilter.push(value);
        badgetable.addFilter("name", "in", couponfilter);
        filtercell.getElement().style.backgroundColor = "#C0FFC0";
    }
}

function statusclicked(e, cell) {
    var filtercell = cell.getRow().getCell("name");
    value = filtercell.getValue();
    if (filtercell.getElement().style.backgroundColor) {
        badgetable.removeFilter("status", "in", statusfilter);
        statusfilter = statusfilter.filter(arrayItem => arrayItem !== value);
        if (statusfilter.length > 0) {
            badgetable.addFilter("status", "in", statusfilter);
        }
        filtercell.getElement().style.backgroundColor = "";
    } else {
        if (statusfilter.length > 0) {
            badgetable.removeFilter("status", "in", statusfilter);
        }
        statusfilter.push(value);
        badgetable.addFilter("status", "in", statusfilter);
        filtercell.getElement().style.backgroundColor = "#C0FFC0";
    }
}

function clearfilter() {
    if (typefilter.length > 0) {
        badgetable.removeFilter("type", "in", typefilter);
        typefilter = [];
        var rows = type.getRows();
        for (var row of rows) {
            row.getCell("memType").getElement().style.backgroundColor = "";
        }
    }
    if (catfilter.length > 0) {
        badgetable.removeFilter("category", "in", catfilter);
        catfilter = [];
        var rows = category.getRows();
        for (var row of rows) {
            row.getCell("memCategory").getElement().style.backgroundColor = "";
        }
    }
    if (agefilter.length > 0) {
        badgetable.removeFilter("age", "in", agefilter);
        agefilter = [];
        var rows = age.getRows();
        for (var row of rows) {
            row.getCell("memAge").getElement().style.backgroundColor = "";
        }
    }
    if (paidfilter.length > 0) {
        badgetable.removeFilter("paid", "in", paidfilter);
        paidfilter = [];
        var rows = paid.getRows();
        for (var row of rows) {
            row.getCell("paid").getElement().style.backgroundColor = "";
        }
    }
    if (labelfilter.length > 0) {
        badgetable.removeFilter("label", "in", labelfilter);
        labelfilter = [];
        var rows = label.getRows();
        for (var row of rows) {
            row.getCell("label").getElement().style.backgroundColor = "";
        }
    }
    if (couponfilter.length > 0) {
        badgetable.removeFilter("name", "in", couponfilter);
        couponfilter = [];
        var rows = coupon.getRows();
        for (var row of rows) {
            row.getCell("name").getElement().style.backgroundColor = "";
        }
    }
    if (statusfilter.length > 0) {
        badgetable.removeFilter("name", "in", statusfilter);
        statusfilter = [];
        var rows = statusTable.getRows();
        for (var row of rows) {
            row.getCell("name").getElement().style.backgroundColor = "";
        }
    }
}

function draw_stats(data) {
    if (category !== null) {
        category.off("cellClick");
        category.destroy();
        category = null;
    }
    category = new Tabulator('#category-table', {
        data: data['categories'],
        layout: "fitDataTable",
        columns: [
            {
                title: "Category", columns: [
                    { field: "memCategory" },
                    { field: "percent", formatter: "progress", width: 100, headerSort: false, },
                    { field: "occurs", hozAlign: "right" },
                ]
            },
        ],
    });
    category.on("cellClick", catclicked)
    catfilter = [];
    if (type !== null) {
        type.off("cellClick");
        type.destroy();
        type = null;
    }
    type = new Tabulator('#type-table', {
        data: data['types'],
        layout: "fitDataTable",
        columns: [
            {
                title: "Type", columns: [
                    { field: "memType" },
                    { field: "percent", formatter: "progress", width: 100, headerSort: false, },
                    { field: "occurs", hozAlign: "right" },
                ]
            },
        ],
    });
    type.on("cellClick", typeclicked);
    typefilter = [];
    if (age !== null) {
        age.off("cellClick");
        age.destroy();
        age = null;
    }
    age = new Tabulator('#age-table', {
        data: data['ages'],
        layout: "fitDataTable",
        columns: [
            {
                title: "Age", columns: [
                    { field: "memAge", hozAlign: "right" },
                    { field: "percent", formatter: "progress", width: 100, headerSort: false, },
                    { field: "occurs", hozAlign: "right" },
                ]
            },
        ],
    });
    age.on("cellClick", ageclicked);
    agefilter = [];

    if (paid !== null) {
        paid.off("cellClick");
        paid.destroy();
        paid = null;
    }
    paid = new Tabulator('#paid-table', {
        data: data['paids'],
        layout: "fitDataTable",
        columns: [
            {
                title: "Paid", columns: [
                    { field: "paid", hozAlign: "right" },
                    { field: "percent", formatter: "progress", width: 100, headerSort: false, },
                    { field: "occurs", hozAlign: "right" },
                ]
            },
        ],
    });
    paid.on("cellClick", paidclicked);
    paidfilter = [];

    if (label !== null) {
        label.off("cellClick");
        label.destroy();
        label = null;
    }
    label = new Tabulator('#label-table', {
        data: data['labels'],
        layout: "fitDataTable",
        columns: [
            {
                title: "Label", columns: [
                    { field: "label", maxWidth: 400, },
                    { field: "percent", formatter: "progress", width: 100, maxWidth: 100, headerSort: false, },
                    { field: "occurs", hozAlign: "right" },
                ]
            },
        ],
    });
    label.on("cellClick",  labelclicked);
    labelfilter = [];

    if (coupon !== null) {
        coupon.off("cellClick");
        coupon.destroy();
        coupon = null;
    }
    coupon = new Tabulator('#coupon-table', {
        data: data['coupons'],
        layout: "fitDataTable",
        columns: [
            {
                title: "Coupon", columns: [
                    { field: "name" },
                    { field: "percent", formatter: "progress", width: 100, headerSort: false, },
                    { field: "occurs", hozAlign: "right" },
                ]
            },
        ],
    });
    coupon.on("cellClick",  couponclicked);
    couponfilter = [];

    if (statusTable !== null) {
        statusTable.off("cellClick");
        statusTable.destroy();
        statusTable = null;
    }
    statusTable = new Tabulator('#status-table', {
        data: data['statuses'],
        layout: "fitDataTable",
        columns: [
            {
                title: "status", columns: [
                    { field: "name" },
                    { field: "percent", formatter: "progress", width: 100, headerSort: false, },
                    { field: "occurs", hozAlign: "right" },
                ]
            },
        ],
    });
    statusTable.on("cellClick",  statusclicked);
    statusfilter = [];
}

// display actions as buttons in a cell for this membership
function actionbuttons(cell, formatterParams, onRendered) {
    var data = cell.getData();
    var category = data['category'];
    var status = data['status'];
    if (category == 'cancel')  // no actions can be taken on a cancelled membership
        return "";

    var btns = "";
    var index = cell.getRow().getIndex();
    var price = data['price'];
    var paid = data['paid'];
    var complete_trans = data['complete_trans'];

    if (category != 'dealers') { // dealers can't roll over, and transfer is handled on-site only in atcon re-assigning the name.
        if (status == 'paid') {
            // transfer buttons
            if ((category == 'addon' || category == 'add-on') && paid > 0) {
                btns += '<button class="btn btn-warning" style = "--bs-btn-padding-y: .0rem; --bs-btn-padding-x: .3rem; --bs-btn-font-size: .75rem;" onclick="transfer(' + index + ')">Transfer</button>';
            } else if (price > 0 && paid > 0)
                btns += '<button class="btn btn-secondary" style = "--bs-btn-padding-y: .0rem; --bs-btn-padding-x: .3rem; --bs-btn-font-size: .75rem;", onclick="transfer(' + index + ')">Transfer</button>';
            else if (price == 0 && paid == 0)
                btns += '<button class="btn btn-warning" style = "--bs-btn-padding-y: .0rem; --bs-btn-padding-x: .3rem; --bs-btn-font-size: .75rem;", onclick="transfer(' + index + ')">Transfer</button>';

            // rollover buttons
            if (price > 0 && paid > 0)
                btns += '<button class="btn btn-secondary" style = "--bs-btn-padding-y: .0rem; --bs-btn-padding-x: .3rem; --bs-btn-font-size: .75rem;", onclick="rollover(' + index + ')">Rollover</button>';
            else if (price == 0 && paid == 0)
                btns += '<button class="btn btn-warning" style = "--bs-btn-padding-y: .0rem; --bs-btn-padding-x: .3rem; --bs-btn-font-size: .75rem;", onclick="rollover(' + index + ')">Rollover</button>';
        }
    }

    // receipt buttons
    if (paid > 0 && complete_trans > 0)
        btns += '<button class="btn btn-primary" style = "--bs-btn-padding-y: .0rem; --bs-btn-padding-x: .3rem; --bs-btn-font-size: .75rem;", onclick="receipt(' + index + ')">Receipt</button>';
    return btns;
}

// display receipt: use the modal to show the receipt
function displayReceipt(data) {
    document.getElementById('receipt-div').innerHTML = data['receipt_html'];
    document.getElementById('receipt-tables').innerHTML = data['receipt_tables'];
    document.getElementById('receipt-text').innerHTML = data['receipt'];
    receipt_email_address = data['payor_email'];
    document.getElementById('emailReceipt').innerHTML = "Email Receipt to " + data['payor_name'] + ' at ' + receipt_email_address;
    document.getElementById('receiptTitle').innerHTML = "Registration Receipt for " + data['payor_name'];
    receipt_modal.show();
}

function receipt_email(addrchoice) {
    var email = receipt_email_address;
    var success='';
    if (addrchoice == 'reg') {
        email = document.getElementById('regadminemail').innerHTML;
        success = 'Receipt sent to Regadmin at ' + email;
    }

    if (receipt_email_address == null)
        return;

    if (success == '')
        success = document.getElementById('emailReceipt').innerHTML.replace("Email Receipt to", "Receipt sent to");

    var data = {
        email: email,
        okmsg: success,
        text: document.getElementById('receipt-text').innerHTML,
        html: document.getElementById('receipt-tables').innerHTML,
        subject: document.getElementById('receiptTitle').innerHTML,
        success: success,
    };
    $.ajax({
        method: "POST",
        url: "scripts/emailReceipt.php",
        data: data,
        success: function (data, textstatus, jqxhr) {
            if (data['error'] !== undefined) {
                show_message(data['error'], 'error');
                return;
            }
            if (data['success'] !== undefined) {
                show_message(data['success'], 'success');
            }
            if (data['warn'] !== undefined) {
                show_message(data['warn'], 'warn');
            }
        },
        error: function (jqXHR, textStatus, errorThrown) {
            showError("ERROR in emailReceipt: " + textStatus, jqXHR);
        }
    });
}
// receipt - display a receipt for the transaction for this badge
function receipt(index) {
    var row = badgetable.getRow(index);
    var transid = row.getCell("complete_trans").getValue();
    if (transid == null || transid == '') {
        transid = row.getCell("create_trans").getValue();
    }
    $.ajax({
        method: "POST",
        url: "scripts/getReceipt.php",
        data: { transid },
        success: function (data, textstatus, jqxhr) {
            if (data['error'] !== undefined) {
                show_message(data['error'], 'error');
                return;
            }
            if (data['success'] !== undefined) {
                show_message(data['success'], 'success');
            }
            if (data['warn'] !== undefined) {
                show_message(data['warn'], 'warn');
            }
            displayReceipt(data);
            if (data['success'] !== undefined)
                show_message(data.success, 'success');
        },
        error: function (jqXHR, textStatus, errorThrown) {
            showError("ERROR in getReceipt: " + textStatus, jqXHR);
        }
    });

}

// rollover - cancel his years badge and create it as a rollover in next yeers con
function rollover(index) {
    var row = badgetable.getRow(index);
    var data = row.getData();
    var perid = data['perid'];
    var confirm_msg = "Confirm rollover for " + data['p_name'].trim() + "'s badge of type " + data['label'] + " to " + (conid + 1) + '?';
    if (confirm(confirm_msg)) {
        $.ajax({
            method: "POST",
            url: "scripts/rolloverBadge.php",
            data: {  badge: index, toconid: conid + 1, perid: perid, },
            success: function (data, textstatus, jqxhr) {
                if (data['error'] !== undefined) {
                    show_message(data['error'], 'error');
                    return;
                }
                if (data['success'] !== undefined) {
                    show_message(data['success'], 'success');
                }
                if (data['warn'] !== undefined) {
                    show_message(data['warn'], 'warn');
                }
                getData();
                if (data['success'] !== undefined)
                    show_message(data.success, 'success');
            },
            error: function (jqXHR, textStatus, errorThrown) {
                showError("ERROR in rolloverBadge: " + textStatus, jqXHR);
            }
        });
    }
}

// transfer - get information to build modal popup to find person to transfer to
function transfer(index) {
    var row = badgetable.getRow(index);
    var data = row.getData();

    if (data['price'] > 0 && data['paid'] == 0)
        return;

    if (data['status'] != 'paid')
        return;

    if (data['price'] == 0) {
        if (confirm("This is a free badge, really transfer it?\n(Is it an included exhibitor badge or similar situation?)") == false)
            returm;
    }

    var badgeid = data['badgeId'];
    var fullname = data['p_name'];
    var badgename = data['p_badge'];
    var badgelabel = data['label'];
    var perid = data['perid'];

    find_pattern_field.value = '';
    if (find_result_table != null) {
        find_result_table.destroy();
        find_result_table = null;
    }
    if (badgename != null && badgename != '')
        badgename = ' (' + badgename + ')';
    document.getElementById('transfer_from').innerHTML = fullname + badgename;
    document.getElementById('transfer_badge').innerHTML = badgelabel;
    document.getElementById('from_badgeid').value = badgeid;
    document.getElementById('from_perid').value = perid;
    document.getElementById('transfer_search_results').innerHTML = '';
    test.innerHTML = '';
    transfer_modal.show();

}

// transfer_find - search for matching names
function transfer_find() {
    if (find_result_table != null) {
        find_result_table.destroy();
        find_result_table = null;
    }

    clear_message();
    var name_search = find_pattern_field.value.toLowerCase().trim();
    if (name_search == null || name_search == '')  {
        show_message("No search criteria specified", "warn");
        return;
    }

    // search for matching names
    $("button[name='transferSearch']").attr("disabled", true);
    test.innerHTML = '';
    clear_message();

    $.ajax({
        method: "POST",
        url: "scripts/transferFindRecord.php",
        data: { name_search: name_search, },
        success: function (data, textstatus, jqxhr) {
            $("button[name='transferSearch']").attr("disabled", false);
            if (data['error'] !== undefined) {
                show_message(data['error'], 'error');
                return;
            }
            if (data['message'] !== undefined) {
                show_message(data['message'], 'success');
            }
            if (data['warn'] !== undefined) {
                show_message(data['warn'], 'warn');
            }
            transfer_found(data);
        },
        error: function (jqXHR, textStatus, errorThrown) {
            $("button[name='transferSearch']").attr("disabled", false);
            showError("ERROR in transferFindRecord: " + textStatus, jqXHR);
        }
    });
}

// badge_name_default: build a default badge name if its empty
function badge_name_default(badge_name, first_name, last_name) {
    if (badge_name === undefined | badge_name === null || badge_name === '') {
        var default_name = (first_name + ' ' + last_name).trim();
        return '<i>' + default_name.replace(/ +/, ' ') + '</i>';
    }
    return badge_name;
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

// tabulator formatter for the transfer column for the find results, displays the "transfer" to transfer the membership
// color based on number of reg records this person already has for this con
function addTransferIcon(cell, formatterParams, onRendered) { //plain text value
    var tid;
    var html = '';
    var banned = cell.getData().banned == 'Y';
    var regcnt = cell.getData().regcnt;
    var color = 'btn-success';
    var from = cell.getRow().getData().from;
    var to = cell.getRow().getData().perid;

    if (banned) {
        color = 'btn-danger';
    } else if (regcnt > 0) {
        color = 'btn-warning';
    }
    return '<button type="button" class="btn btn-sm ' + color + ' pt-0 pb-0" style="--bs-btn-font-size: 75%;" onclick="transferBadge(' + to + ',' + banned + ')">Transfer</button>';
}

// transfer_found - display a list of potential transfer recipients
function transfer_found(data) {
    var perinfo = data['perinfo'];
    var name_search = data['name_search'];
    if (perinfo.length > 0) {
        find_result_table = new Tabulator('#transfer_search_results', {
            maxHeight: "600px",
            data: perinfo,
            layout: "fitColumns",
            initialSort: [
                {column: "fullname", dir: "asc"},
            ],
            columns: [
                {field: "perid", visible: false,},
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
                {title: "Transfer", width: 90, headerFilter: false, headerSort: false, formatter: addTransferIcon, formatterParams: {t: "result"},},
                {field: "index", visible: false,},
            ],
        });
    }
}

function draw_badges(data) {
    if (badgetable !== null) {
        badgetable.destroy();
        badgetable = null;
    }
    badgetable = new Tabulator('#badge-table', {
        data: data['badges'],
        layout: "fitDataTable",
        index: "badgeId",
        pagination: true,
        paginationSize: 10,
        paginationSizeSelector: [10, 25, 50, 100, 250, true], //enable page size select element with these options
        columns: [
            { title: "TID", field: "display_trans", hozAlign: "right",  headerSort: true, headerFilter: true },
            { title: "PID", field: "perid", hozAlign: "right", headerSort: true, headerFilter: true, },
            { title: "Person", field: "p_name", headerSort: true, headerFilter: true },
            { title: "Badge Name", field: "p_badge", headerSort: true, headerFilter: true },
            { title: "Email", field: "p_email", headerSort: true, headerFilter: true },
            { title: "Membership Type", field: "label", headerSort: true, headerFilter: true, },
            { title: "memId", field: "memId", hozAlign: "right", headerSort: true, headerFilter: true, },
            { title: "Price", field: "price", hozAlign: "right", headerSort: true, headerFilter: true, headerFilterFunc:numberHeaderFilter, },
            { title: "Disc", field: "couponDiscount", hozAlign: "right", headerSort: true, headerFilter: true, headerFilterFunc:numberHeaderFilter, },
            { title: "Paid", field: "paid", hozAlign: "right", headerSort: true, headerFilter: true, headerFilterFunc:numberHeaderFilter, },
            { title: "Coupon", field: "name", headerSort: true, headerFilter: true, },
            { title: "Status", field: "status", headerSort: true, headerFilter: true, },
            { title: "Created", field: "create_date", headerSort: true, headerFilter: true },
            { title: "Changed", field: "change_date", headerSort: true, headerFilter: true },
            { field: "category", visible: false },
            { field: "age", visible: false },
            { field: "type", visible: false },
            { field: "badgeId", visible: false },
            { field: "perid", visible: false },
            { field: "create_trans", visible: false },
            { field: "complete_trans", visible: false },
            { title: "Action", formatter: actionbuttons, hozAlign:"left", headerSort: false },
        ],
        initialSort: [
            {column: "display_trans", dir: "desc" },
            {column: "change_date", dir: "desc" },
        ],
    });
}

function draw(data, textStatus, jqXHR) {
    conid = Number(data['conid']);
    draw_stats(data);
    draw_badges(data);
}

function getData() {
    $.ajax({
        url: "scripts/getBadges.php",
        method: "GET",
        success: draw,
        error: function (jqXHR, textStatus, errorThrown) {
            showError("ERROR in getBadges: " + textStatus, jqXHR);
            return false;
        }
    })
}


function transferBadge(to, banned) {
    if (banned == 'Y') {
        if (prompt("Transfer to banned user?") == false)
            return;
    }

    var from = document.getElementById('from_badgeid').value;
    var from_perid = document.getElementById('from_perid').value;
    var formData = { 'badge': from, 'perid': to, 'from_perid' : from_perid};
    $.ajax({
        url: 'scripts/transferBadge.php',
        data: formData,
        method: 'POST',
        error: function (jqXHR, textStatus, errorThrown) {
            showError("ERROR in transferBadge: " + textStatus, jqXHR);
            return false;
        },
        success: function (data, textStatus, jqXHR) {
            console.log(data);
            if (data.error) {
                show_message(data.error, 'error');
            } else if (data.warning) {
                transfer_modal.hide();
                show_message(data.warning, 'warn');
            } else {
                transfer_modal.hide();
                getData();
                if (data.message)
                    show_message(data.message, 'success');

            }
        }
    });
}

function sendCancel() {
    var tid = prompt("Would you like to send a test email?\nIf so please enter the transaction you want to send the test for.\n");
    var action = "none";

    if (tid == null) {
        if (confirm("You are about to send email to a lot of people.  Are you sure?")) {
            action = 'full';
        } else { return false; }
    } else {
        action = 'test';
    }

    $.ajax({
        url: 'scripts/sendCancelEmail.php',
        data: { 'action': action, 'tid': tid },
        method: "POST",
        success: function (data, textStatus, jqXHR) {
            if (data.erro) {
                $('#test').empty().append(JSON.stringify(data));
                alert(data.error);
            } else {
                $('#test').empty().append(JSON.stringify(data));
            }
        }
    });
}


function sendEmail(type) {
    emailBulkSend = new EmailBulkSend('result_message', 'scripts/sendBatch.php');

    var email = prompt("Would you like to send a test " + type + " email?\nIf so please enter the address to send the test to in the box below and click ok.\n" +
        "If you don't provide a test address, you will be sending emails to a lot of people.\nYou will be give a chance to review the number of emails to be sent before they are sent out.\n" +
        "Clicking cancel will cancel the sending of these emails.\n");
    var action = "none";

    if (email == null)
        return false;

    if (email == '') {
        action = 'full';
    } else {
        action = 'test';
    }

    var data = { 'action': action, 'email': email, 'type': type };
    emailBulkSend.getEmailAndList('scripts/sendEmail.php', data );
}
