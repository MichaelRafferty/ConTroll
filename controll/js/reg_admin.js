// globals for the regadmin tabs
current = null;
next = null;
mem = null;
merge = null;
customText = null;
policy = null;
interests = null;
rules = null;
conid = null;
editPreviewClass = null;
memLabels = null;
memLabelsIdx = null;
memLabelsNext = null;
memLabelsNextIdx = null;
// debug meaning
//  1 = console.logs
//  2 = show hidden table fields
//  4 = show hidden div
debug = 0;

var badgetable = null;
var category = null;
var type = null;
var price = null;
var label = null;
var age = null;
var coupon = null;
var statusTable = null;
var typefilter = null;
var catfilter = null;
var agefilter = null;
var pricefilter = null;
var labelfilter = null;
var couponfilter = null;
var statusfilter = null;
var changeModal = null;
var receiptModal = null;
var notesModal = null;
var recepitEmailAddress = null;
var find_result_table = null;
var testdiv = null;
var conid = 0;

// changes items
var changeMemberships = [];
var changeList = [];
var denyCancel = ['rolled-over', 'cancelled','refunded', 'transfered'];
var denyTransfer = ['rolled-over', 'cancelled','refunded', 'transfered'];
var allowRolloverCategories = ['standard','freebie','upgrade','yearahead'];
var currentIndex = null;
var transferSearchDiv = null;
var changeRowdata = null;
var changeBodyDiv = null;
var transferFromNameDiv = null;
var transferFromBadgeDiv = null
var transferNameSearchField = null;
var rolloverDiv = null;
var rolloverSelect = null;
var changeDirection = null;

// initialization at DOM complete
window.onload = function initpage() {
    id = document.getElementById('changeModal');
    if (id != null) {
        changeModal = new bootstrap.Modal(id, { focus: true, backdrop: 'static' });
        changeBodyDiv = document.getElementById("change-body-div");
        transferSearchDiv = document.getElementById("transfer-search-div");
        transferFromNameDiv = document.getElementById("transfer_from");
        transferFromBadgeDiv = document.getElementById('transfer_badge');
        transferNameSearchField = document.getElementById('transfer_name_search');
        rolloverDiv = document.getElementById('rollover-div');
        rolloverSelect = document.getElementById('rollover_select');
    }

    id = document.getElementById('receipt');
    if (id != null) {
        receiptModal = new bootstrap.Modal(id, { focus: true, backdrop: 'static' });
        $('#receipt').on('hide.bs.modal', function () {
            recepitEmailAddress = null;
        });
    }

    id = document.getElementById('notes');
    if (id != null) {
        notesModal = new bootstrap.Modal(id, { focus: true, backdrop: 'static' });
    }
    testdiv = document.getElementById('test');
    getData();
}

// filters for BadgeList
// click functions to toggle a single row in a filter
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

function priceclicked(e, cell) {
    var filtercell = cell.getRow().getCell("price");
    var value = filtercell.getValue();
    if (filtercell.getElement().style.backgroundColor) {
        badgetable.removeFilter("price", "in", pricefilter);
        pricefilter = pricefilter.filter(arrayItem => arrayItem !== value);
        if (pricefilter.length > 0) {
            badgetable.addFilter("price", "in", pricefilter);
        }
        filtercell.getElement().style.backgroundColor = "";
    } else {
        if (pricefilter.length > 0) {
            badgetable.removeFilter("price", "in", pricefilter);
        }
        pricefilter.push(value);
        badgetable.addFilter("price", "in", pricefilter);
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

// clear all filter items from the clicked filter section
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
    if (pricefilter.length > 0) {
        badgetable.removeFilter("price", "in", pricefilter);
        pricefilter = [];
        var rows = price.getRows();
        for (var row of rows) {
            row.getCell("price").getElement().style.backgroundColor = "";
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

// draw all of the filter tables with the progress bar as statistics with counts
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

    if (price !== null) {
        price.off("cellClick");
        price.destroy();
        price = null;
    }
    price = new Tabulator('#price-table', {
        data: data['prices'],
        layout: "fitDataTable",
        columns: [
            {
                title: "price", columns: [
                    { field: "price", hozAlign: "right" },
                    { field: "percent", formatter: "progress", width: 100, headerSort: false, },
                    { field: "occurs", hozAlign: "right" },
                ]
            },
        ],
    });
    price.on("cellClick", priceclicked);
    pricefilter = [];

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
    var perid = data['perid'];
    var paid = data['paid'];
    var ncount = data['ncount'];
    var complete_trans = data['complete_trans'];
    var index = cell.getRow().getIndex();

    var btns = "";
    if (perid > 0) {
        btns += '<button class="btn btn-secondary" style = "--bs-btn-padding-y: .0rem; --bs-btn-padding-x: .3rem; --bs-btn-font-size: .75rem;",' +
            ' onclick="changeReg(' + index + ')">Changes</button>';
    }

    // receipt buttons
    if (paid > 0 && complete_trans > 0)
        btns += '<button class="btn btn-primary" style = "--bs-btn-padding-y: .0rem; --bs-btn-padding-x: .3rem; --bs-btn-font-size: .75rem;",' +
            ' onclick="receipt(' + index + ')">Receipt</button>';

    // notes button
    if (ncount != null && ncount > 0)
        btns += '<button class="btn btn-primary" style = "--bs-btn-padding-y: .0rem; --bs-btn-padding-x: .3rem; --bs-btn-font-size: .75rem;",' +
            ' onclick="notes(' + index + ')">Notes</button>';

return btns;
}

// display receipt: use the modal to show the receipt
function displayReceipt(data) {
    document.getElementById('receipt-div').innerHTML = data['receipt_html'];
    document.getElementById('receipt-tables').innerHTML = data['receipt_tables'];
    document.getElementById('receipt-text').innerHTML = data['receipt'];
    recepitEmailAddress = data['payor_email'];
    document.getElementById('emailReceipt').innerHTML = "Email Receipt to " + data['payor_name'] + ' at ' + recepitEmailAddress;
    document.getElementById('receiptTitle').innerHTML = "Registration Receipt for " + data['payor_name'];
    receiptModal.show();
}

function receipt_email(addrchoice) {
    var email = recepitEmailAddress;
    var success='';
    if (addrchoice == 'reg') {
        email = document.getElementById('regadminemail').innerHTML;
        success = 'Receipt sent to Regadmin at ' + email;
    }

    if (recepitEmailAddress == null)
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

// notes - display the notes for this badge - ajax call to fetch the notes
function notes(index) {
    var row = badgetable.getRow(index);
    var rid = row.getCell("badgeId").getValue();
    if (rid == null || rid == '') {
        show_message("No registration id for this row, seek assistance", "error");
        return;
    }
    $.ajax({
        method: "POST",
        url: "scripts/getNotes.php",
        data: {
            rid: rid,
            index: index,
        },
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
            displayNotes(data);
            if (data['success'] !== undefined)
                show_message(data.success, 'success');
        },
        error: function (jqXHR, textStatus, errorThrown) {
            showError("ERROR in getReceipt: " + textStatus, jqXHR);
        }
    });
}

// displayNotes - display all registration notes for this reg record - return from the AJAX call fetching the notes
function displayNotes(data) {

    var index = data['post']['index'];
    var row = badgetable.getRow(index);
    var fullname = row.getCell('p_name').getValue();
    var label = row.getCell('label').getValue();
    var badgeId = row.getCell('badgeId').getValue();
    document.getElementById('notesTitle').innerHTML = "Registration Notes for " + fullname + '<br/>Membership: ' + badgeId + ': ' + label;

    var notes = data['notes'];
    var html = `
        <div class="row mt-4">
            <div class="col-sm-1"><b>TID</b></div>
            <div class="col-sm-2"><b>Log Date</b></div>
            <div class="col-sm-1"><b>UserId</b></div>
            <div class="col-sm-8"><b>Note</b></div>
        </div>
`;
    for (var i = 0; i < notes.length; i++) {
        var note = notes[i];
        html += `
        <div class="row mt-2">
            <div class="col-sm-1">` + note.tid + `</div>
            <div class="col-sm-2">` + note.logdate + `</div>
            <div class="col-sm-1">` + note.userid + `</div>
            <div class="col-sm-8">` + note.notes + `</div>
        </div>
`;
    }
    document.getElementById('notesText').innerHTML = html;
    notesModal.show();
}

// change - display all reg records for a perid based on this row and offer changes, also used to refresh the modal popup after changes
function changeReg(index, clear = true) {
    if (clear)
        clear_message('changeMessageDiv');

    currentIndex = index;
    var row = badgetable.getRow(index);
    changeRowdata = row.getData();
    var perid =     changeRowdata['perid'];

    if (perid == null || perid == '' || perid <= 0)
        return;

    // get all the regs for this perid for this con to decide what changes to make
    var data = {
        perid: perid,
        action: 'regregs',
    };
    var script = 'scripts/regadmin_getRegs.php';
    $.ajax({
        method: "POST",
        url: script,
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
                return;
            }
            changeRegsData(data, changeRowdata);
        },
        error: function (jqXHR, textStatus, errorThrown) {
            showError("ERROR in rolloverBadge: " + textStatus, jqXHR);
        }
    });
}

// return from the ajax call to fetch the regs for this perid, display the list of registrations
function changeRegsData(data, rowdata) {
    var html = '';

    //console.log(data);
    //console.log(rowdata);

    // now have retrieved the memberships for this perid (person) and the rowdata is the actual row involved, display all the memberships with checkboxes
    // and options

    changeMemberships = data.memberships;

    html += `
    <div class="row mt-4 mb-2">
        <div class="col-sm-12"><b>Registrations for ` + rowdata.p_name + ' (' + rowdata.p_email + '), id: ' + rowdata.perid + `</b></div>
    </div>
      <div class="row">
        <div class="col-sm-12">
            <button class="btn btn-small btn-light" onclick="changeSelectAll(1);">Select All</button>
            <button class="btn btn-small btn-light" onclick="changeSelectAll(0);">Clear All</button>
        </div>      
    </div>
    <div class="row">
        <div class="col-sm-1" style="text-align: right;">Selected</div>
        <div class="col-sm-1" style="text-align: right;">Reg ID</div>
        <div class="col-sm-1" style="text-align: right;">Trans ID</div>
        <div class="col-sm-1" style="text-align: right;">Mem ID</div>
        <div class="col-sm-3">Label</div>
        <div class="col-sm-1" style="text-align: right;">Price</div>
        <div class="col-sm-1" style="text-align: right;">Paid</div>
        <div class="col-sm-1" style="text-align: right;">Disc.</div>
        <div class="col-sm-1">Status</div>
    </div>
    <div class="row">
        <div class="col-sm-1 text-primary" style="text-align: right;">
            <input type="checkbox" id="m-` + rowdata.badgeId + `" value="Y" checked>
        </div>
        <div class="col-sm-1 text-primary" style="text-align: right;">
            <label for="m-` + rowdata.badgeId + '">' + rowdata.badgeId + `</label></div>
        <div class="col-sm-1 text-primary" style="text-align: right;">` + rowdata.create_trans + `</div>
        <div class="col-sm-1 text-primary" style="text-align: right;">` + rowdata.memId + `</div>
        <div class="col-sm-3 text-primary">` + rowdata.label + `</div>
        <div class="col-sm-1 text-primary" style="text-align: right;">` + rowdata.price + `</div>
        <div class="col-sm-1 text-primary" style="text-align: right;">` + rowdata.paid + `</div>
        <div class="col-sm-1 text-primary" style="text-align: right;">` + rowdata.couponDiscount + `.</div>
        <div class="col-sm-1 text-primary">` + rowdata.status + `</div>
        <div class="col-sm-1"><button class="btn btn-sm btn-secondary" onclick="changeEdit(` + rowdata.badgeId + `)";>Edit</button></div>
    </div>
`;

    for (var i = 0; i < changeMemberships.length; i++) {
        var membership = changeMemberships[i];
        if (membership.id == rowdata.badgeId)
            continue;

        html += `
    <div class="row mt-1">
        <div class="col-sm-1" style="text-align: right;">
            <input type="checkbox" id="m-` + membership.id + `" value="Y">
        </div>
        <div class="col-sm-1" style="text-align: right;">
            <label for="m-` + membership.id + '">' + membership.id + `</label></div>
        <div class="col-sm-1" style="text-align: right;">` + membership.create_trans + `</div>
        <div class="col-sm-1" style="text-align: right;">` + membership.memId + `</div>
        <div class="col-sm-3">` + membership.label + `</div>
        <div class="col-sm-1" style="text-align: right;">` + membership.price + `</div>
        <div class="col-sm-1" style="text-align: right;">` + membership.paid + `</div>
        <div class="col-sm-1" style="text-align: right;">` + membership.couponDiscount + `.</div>
        <div class="col-sm-1">` + membership.status + `</div>
        <div class="col-sm-1"><button class="btn btn-sm btn-secondary" onclick="changeEdit(\` + membership.badgeId + \`)";>Edit</button></div>
    </div>
`;
    }
    html += `
    <div class="row mt-2 mb-2">
        <div class="col-sm-12" style="text-align: center;">
            <button class="btn btn-sm btn-primary" onclick="changeCancel(0);">Cancel Selected</button>
            <button class="btn btn-sm btn-warning me-4" onclick="changeCancel(1);">Restore Selected</button>
            <button class="btn btn-sm btn-primary me-4" onclick="changeTransfer();">Transfer Selected</button>
            <button class="btn btn-sm btn-primary me-4" onclick="changeRollover();">Rollover Selected</button>
            <button class="btn btn-sm btn-primary" onclick="changeRefund();">Refund Selected</button>
        </div>
    </div>
`;
    changeBodyDiv.innerHTML = html;
    changeModal.show();
}

// select All/Clear all - set/clear all of the check boxes in the reg selection list for this perid
function changeSelectAll(direction) {
    for (var i = 0; i < changeMemberships.length; i++) {
        var membership = changeMemberships[i];
        var id = document.getElementById('m-' + membership.id);
        if (id)
            id.checked = direction == 1;
    }
}

//// Cancel Start
// process the cancel/restore requests, validate the selections and if allowed call the AJAX call to process the request
function changeCancel(direction) {
    // hide transfer block
    clear_message();
    clear_message('changeMessageDiv');
    transferSearchDiv.hidden = true;
    rolloverDiv.hidden = true;
    changeDirection = direction;
    // check which ones need to be ignored
    var message = '';
    changeList = [];
    for (var i = 0; i < changeMemberships.length; i++) {
        var changeItem = changeMemberships[i];
        var checked = document.getElementById('m-' + changeItem.id).checked;
        if (!checked)
            continue;

        if (direction == 0 && denyCancel.indexOf(changeItem.status) != -1)  {
            message += "Cannot change " + changeItem.id + " as status " + changeItem.status + " cannot be cancelled<br/>";
            continue;
        }
        if (direction == 1 && changeItem.status != 'cancelled')  {
            message += "Cannot change " + changeItem.id + " as status " + changeItem.status + " is not cancelled<br/>";
            continue;
        }
        changeList.push(changeItem.id);
    }

    if (changeList.length == 0) {
        message += "Nothing to change";
        show_message(message, 'warn', 'changeMessageDiv');
        return;
    }

    if (message != '') {
        show_message(message, 'error', 'changeMessageDiv');
        return;
    }

    clear_message('changeMessageDiv');
    var data = {
        cancelList: changeList,
        direction: direction,
        action: 'cancel',
    }
    var script= 'scripts/regadmin_cancelReg.php';

    $.ajax({
        method: "POST",
        url: script,
        data: data,
        success: function (data, textstatus, jqxhr) {
            if (data['error'] !== undefined) {
                show_message(data['error'], 'error', 'changeMessageDiv');
                return;
            }
            if (data['success'] !== undefined) {
                show_message(data['success'], 'success', 'changeMessageDiv');
            }
            if (data['warn'] !== undefined) {
                show_message(data['warn'], 'warn', 'changeMessageDiv');
                return;
            }
            cancelRegsSuccess(data);
        },
        error: function (jqXHR, textStatus, errorThrown) {
            showError("ERROR in cancelReg: " + textStatus, jqXHR);
        }
    });
}

// ajax success function display the message and refresh the data
function cancelRegsSuccess(data) {
    if (data['message'])
        show_message(data['message'], 'success', 'changeMessageDiv');

    changeReg(currentIndex, false);
}
//// Cancel End

//// Transfer Start
// process the transfer/return requests, validate the selections and if allowed show the part of the modal to request to whom to transfer
function changeTransfer() {
    clear_message();
    clear_message('changeMessageDiv');
    rolloverDiv.hidden = true;

    // check which ones need to be ignored
    var message = '';
    changeList = [];
    var badgeList = '';
    for (var i = 0; i < changeMemberships.length; i++) {
        var changeItem = changeMemberships[i];
        var checked = document.getElementById('m-' + changeItem.id).checked;
        if (!checked)
            continue;

        if (denyTransfer.indexOf(changeItem.status) != -1)  {
            message += "Cannot transfer " + changeItem.id + " as status " + changeItem.status + " cannot be transfered<br/>";
            continue;
        }

        changeList.push(changeItem.id);
        badgeList += changeItem.id + ':' + changeItem.label + '  ';
    }

    if (changeList.length == 0) {
        message += "Nothing to change";
        show_message(message, 'warn', 'changeMessageDiv');
        return;
    }

    if (message != '') {
        show_message(message, 'error', 'changeMessageDiv');
        return;
    }

    transferFromNameDiv.innerHTML = changeRowdata['perid'] + ': ' + changeRowdata['p_name'];
    transferFromBadgeDiv.innerHTML =  badgeList;
    transferNameSearchField.value = '';

    transferNameSearchField.value = '';
    if (find_result_table != null) {
        find_result_table.destroy();
        find_result_table = null;
    }

    transferSearchDiv.hidden = false;
}

// changeTransferFind - search for matching names - call ajax routine to return matching names
function changeTransferFind() {
    if (find_result_table != null) {
        find_result_table.destroy();
        find_result_table = null;
    }

    clear_message('changeMessageDiv');
    var name_search = transferNameSearchField.value.toLowerCase().trim();
    if (name_search == null || name_search == '')  {
        show_message("No search criteria specified", "warn", 'changeMessageDiv');
        return;
    }

    // search for matching names
    $("button[name='transferSearch']").attr("disabled", true);
    clear_message('changeMessageDiv');

    $.ajax({
        method: "POST",
        url: "scripts/regadmin_transferFindRecord.php",
        data: { name_search: name_search, },
        success: function (data, textstatus, jqxhr) {
            $("button[name='transferSearch']").attr("disabled", false);
            if (data['error'] !== undefined) {
                show_message(data['error'], 'error', 'changeMessageDiv');
                return;
            }
            if (data['message'] !== undefined) {
                show_message(data['message'], 'success', 'changeMessageDiv');
            }
            if (data['warn'] !== undefined) {
                show_message(data['warn'], 'warn','changeMessageDiv');
            }
            changeTransferFound(data);
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
    return '<button type="button" class="btn btn-sm ' + color + ' pt-0 pb-0" style="--bs-btn-font-size: 75%;" onclick="transferReg(' + to + ',' + banned + ')">Transfer</button>';
}

// changeTransferFound - display a list of potential transfer recipients
function changeTransferFound(data) {
    var perinfo = data['perinfo'];
    var name_search = data['name_search'];
    if (perinfo.length > 0) {
        find_result_table = new Tabulator('#transfer_search_results', {
            maxHeight: "600px",
            data: perinfo,
            layout: "fitDataTable",
            initialSort: [
                {column: "fullname", dir: "asc"},
            ],
            columns: [
                {title: "ID", field: "perid", width: 120, hozAlign: "right", headerHozAlign: "right" },
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

// execute the transfer request from the search list button of to whom to transfer
function transferReg(to, banned) {
    if (banned == 'Y') {
        if (prompt("Transfer to banned user?") == false)
            return;
    }

    clear_message('changeMessageDiv');
    var script = 'scripts/regadmin_transferReg.php';
    var data = {
        action: 'transfer',
        from: changeRowdata.perid,
        to: to,
        transferList: changeList,
    }
    $.ajax({
        url: script,
        data: data,
        method: 'POST',
        error: function (jqXHR, textStatus, errorThrown) {
            showError("ERROR in transferReg: " + textStatus, jqXHR);
            return false;
        },
        success: function (data, textStatus, jqXHR) {
            console.log(data);
            if (data.error) {
                show_message(data.error, 'error', 'changeMessageDiv');
            } else if (data.warning) {
                changeModal.hide();
                show_message(data.warning, 'warn', 'changeMessageDiv');
            } else {
                transferSearchDiv.hidden = true;
                transferNameSearchField.value = '';
                transferFromNameDiv.innerHTML = '';
                transferFromBadgeDiv.innerHTML = '';
                if (find_result_table != null) {
                    find_result_table.destroy();
                    find_result_table = null;
                }
                changeModal.hide();
                getData();
                if (data.message)
                    show_message(data.message, 'success');
            }
        }
    });
}
//// Transfer end

//// Rollover Start
// process the rollover requests, validate the selections and if allowed call the AJAX call to process the request
function changeRollover() {
    // hide transfer block
    clear_message();
    clear_message('changeMessageDiv');
    transferSearchDiv.hidden = true;
    // check which ones need to be ignored
    var message = '';
    changeList = [];
    var ageList = {all: 1};
    var memCat = null;
    var memAge = null;

    for (var i = 0; i < changeMemberships.length; i++) {
        var changeItem = changeMemberships[i];
        var checked = document.getElementById('m-' + changeItem.id).checked;
        if (!checked)
            continue;

        // check statuses, only allow paid / upraded
        if (changeItem.status != 'paid' && status != 'upgraded') {
            message += "Cannot change " + changeItem.id + " as status " + changeItem.status + " cannot be rolled over, it must be paid.<br/>";
            continue;
        }

        // now check the category

        memCat = memLabelsIdx[changeItem.memId].memCategory;
        memAge = memLabelsIdx[changeItem.memId].memAge;

        if (allowRolloverCategories.indexOf(memCat) == -1) {
            message += "Cannot change " + changeItem.id + " as category " + memCat + " is not allowed to be rolled over<br/>";
            continue;
        }

        changeList.push(changeItem.id);
        if (ageList[memAge])
            ageList[memAge]++;
        else
            ageList[memAge] = 1;
    }

    if (changeList.length == 0) {
        message += "Nothing to change";
        show_message(message, 'warn', 'changeMessageDiv');
        return;
    }

    if (message != '') {
        show_message(message, 'error', 'changeMessageDiv');
        return;
    }

    clear_message('changeMessageDiv');

    // now get the memList entry for this rollover
    // first roll forward
    memListSelect = [];
    for (var i = 0; i < memLabelsNext.length; i++) {
        var memItem = memLabelsNext[i];
        if (ageList[memItem.memAge]) {
            if (allowRolloverCategories.indexOf(memItem.memCategory) >= 0) {
                memListSelect.push(memItem);
            }
        }
    }

    // build the select list
    var optionList = "    <option value=''>Do Not Create New Registration for this row</option>\n";
    for (var i = 0; i < memListSelect.length; i++) {
        optionList += '   <option value="' + memListSelect[i].id + '">' + memListSelect[i].id + ':' + memListSelect[i].memAge + '-' +
            memListSelect[i].memType + '-' + memListSelect[i].memCategory + ' $' + memListSelect[i].price + ' ' +
            memListSelect[i].shortname + "</option>\n";
    }
    optionList += "</select>\n";
    var html = ''
    for (var i = 0; i < changeList.length; i++) {
        var item = changeList[i];
        html += `
    <div class="row mt-2">
        <div class="col-sm-1" style="text-align:right">` + item + `</div>
        <div class="col-sm-2" style="text-align:right"><input type="checkbox" value="Y" id="c-` + i + '"/>' +
            '<label for="c-' + item + `">&nbsp;Override Print Check</label>
        </div>
        <div class="col-sm-8">
            <select id="rolloverMemId-` + i + '" name="rolloverMemId-"' + i + ">\n" +
            optionList + `
       </div>
    </div>
`;
    }
    rolloverSelect.innerHTML = html;
    rolloverDiv.hidden = false;
}

function changeRolloverExecute() {
    // check that at least one of the reg entries is not "do not create new"
    var newIds = {};
    var numIds = 0;
    for (var i = 0; i < changeList.length; i++) {
        var newId = document.getElementById('rolloverMemId-' + i).value;
        var override = document.getElementById('c-' + i).checked;
        newIds[changeList[i]] = { newid: newId, override: override ? 'Y' : 'N' };
        if (newId != '')
            numIds++;
    }

    if (numIds == 0) {
        show_message('At least one membership needs to have a "Do Not Create" selection', 'error', 'changeMessageDiv');
        return;
    }

    var data = {
        rolloverList: newIds,
        action: 'rollover',
    }
    var script= 'scripts/regadmin_rolloverReg.php';

    $.ajax({
        method: "POST",
        url: script,
        data: data,
        success: function (data, textstatus, jqxhr) {
            if (data['error'] !== undefined) {
                show_message(data['error'], 'error', 'changeMessageDiv');
                return;
            }
            if (data['success'] !== undefined) {
                show_message(data['success'], 'success', 'changeMessageDiv');
            }
            if (data['warn'] !== undefined) {
                show_message(data['warn'], 'warn', 'changeMessageDiv');
                return;
            }
            rolloverRegSuccess(data);
        },
        error: function (jqXHR, textStatus, errorThrown) {
            showError("ERROR in rolloverReg: " + textStatus, jqXHR);
        }
    });
}

// clean up after rollover reg
function rolloverRegSuccess(data) {
    rolloverSelect.innerHTML = '';
    rolloverDiv.hidden = true;
    changeModal.hide();
    getData();
    if (data.message)
        show_message(data.message, 'success');
}

//// Rollover End

//// Refund start
// changeRefund - validate / start the refund process
function changeRefund() {
    show_message("Not Yet", 'warn', 'changeMessageDiv');
}

// draws the badge List table of badges found
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
            { field: "ncount", visible: false,},
            { title: "Action", formatter: actionbuttons, hozAlign:"left", headerSort: false },
        ],
        initialSort: [
            {column: "display_trans", dir: "desc" },
            {column: "change_date", dir: "desc" },
        ],
    });
}

// called from data load - draws the filter stats block and the badges block
function draw(data, textStatus, jqXHR) {
    conid = Number(data['conid']);
    memLabels = data['memLabels'];
    memLabelsNext = data['memLabelsNext'];
    memLabelsIdx = {};
    memLabelsNextIdx = {};
    for (i = 0; i < memLabels.length; i++) {
        memLabelsIdx[memLabels[i].id] = memLabels[i];
    }
    for (i = 0; i < memLabelsNext.length; i++) {
        memLabelsNextIdx[memLabelsNext[i].id] = memLabelsNext[i];
    }
    draw_stats(data);
    draw_badges(data);
}

// ajax call to retrieve the starting set of data for the filters and the badge list
function getData() {
    $.ajax({
        url: "scripts/regadmin_getBadges.php",
        method: "GET",
        success: draw,
        error: function (jqXHR, textStatus, errorThrown) {
            showError("ERROR in getBadges: " + textStatus, jqXHR);
            return false;
        }
    })
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

function settab(tabname) {
    // close all of them
    if (current != null)
        current.close();
    if (mem != null)
        mem.close();
    if (next != null)
        next.close();
    if (merge != null)
        merge.close();
    if (customText != null)
        customText.close();
    if (policy != null)
        policy.close();
    if (interests != null)
        interests.close();
    if (rules != null)
        rules.close();

    // now open the relevant one, and create the class if needed
    switch (tabname) {
        case 'consetup-pane':
            if (current == null)
                current = new consetup('current');
            current.open();
            break;

        case 'nextconsetup-pane':
            if (next == null)
                next = new consetup('next');
            next.open();
            break;
        /* case 'memconfig-pane':
            if (mem == null)
                mem = new memsetup();
            mem.open();
            break; */
        case 'merge-pane':
            if (merge == null)
                merge = new mergesetup();
            merge.open();
            break;
        case 'customtext-pane':
            if (customText == null)
                customText = new customTextSetup();
            customText.open();
            break;
        case 'policy-pane':
            if (policy == null)
                policy = new policySetup(debug);
            policy.open();
            break;
        case 'interests-pane':
            if (interests == null)
                interests = new interestsSetup();
            interests.open();
            break;
        case 'rules-pane':
            if (rules == null)
                rules = new rulesSetup();
            rules.open();
            break;
    }
}
function cellChanged(cell) {
    dirty = true;
    cell.getElement().style.backgroundColor = "#fff3cd";
}

function deleteicon(cell, formattParams, onRendered) {
    var value = cell.getValue();
    if (value == 0)
        return "&#x1F5D1;";
    return value;
}

function deleterow(e, row) {
    var count = row.getCell("uses").getValue();
    if (count == 0) {
        row.getCell("to_delete").setValue(1);
        row.getCell("uses").setValue('<span style="color:red;"><b>Del</b></span>');
    }
}