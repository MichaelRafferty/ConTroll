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

var registrationtable = null;
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
var recepitEmailAddress = null;
var find_result_table = null;
var testdiv = null;
var conid = 0;
var reglistDiv = null;

// changes items
var changeMemberships = [];
var changeList = [];
var denyRevoke = ['rolled-over', 'cancelled','refunded', 'transfered'];
var denyTransfer = ['rolled-over', 'cancelled','refunded', 'transfered'];
var allowRolloverCategories = ['standard','freebie','upgrade','yearahead'];
var currentIndex = null;
var currentRow = null;
var transferSearchDiv = null;
var changeRowdata = null;
var changeBodyDiv = null;
var transferFromNameDiv = null;
var transferFromRegistrationDiv = null
var transferNameSearchField = null;
var rolloverDiv = null;
var rolloverSelect = null;
var changeDirection = null;
var editRegDiv = null;
var editRegLabel = null;
var editMemSelect = null;
var editOrigMemlabel = null;
var editNewPrice = null;
var editOrigPrice = null;
var editNewRegPrice = null;
var editNewPaid = null;
var editOrigPaid = null;
var editNewCoupon = null;
var editOrigCoupon = null;
var editNewCouponDiscount = null;
var editOrigCouponDiscount = null;
var editStatusSelect = null;
var editOrigStatus = null;
var editSaveOverride = null;

// history items
var historyModal = null;
var historyTitle = null;
var historyDiv = null;
var historyRow = null;

// notes class
var notes = null;

// initialization at DOM complete
window.onload = function initpage() {
    id = document.getElementById('changeModal');
    if (id != null) {
        changeModal = new bootstrap.Modal(id, { focus: true, backdrop: 'static' });
        changeBodyDiv = document.getElementById("change-body-div");
        transferSearchDiv = document.getElementById("transfer-search-div");
        transferFromNameDiv = document.getElementById("transfer_from");
        transferFromRegistrationDiv = document.getElementById('transfer_registration');
        transferNameSearchField = document.getElementById('transfer_name_search');
        rolloverDiv = document.getElementById('rollover-div');
        rolloverSelect = document.getElementById('rollover_select');
        editRegDiv = document.getElementById("editReg-div");
        editRegLabel = document.getElementById("edit_registration_label");
        editMemSelect = document.getElementById("edit_memSelect");
        editOrigMemlabel = document.getElementById("edit_origMemLabel");
        editNewPrice = document.getElementById("edit_newPrice");
        editOrigPrice = document.getElementById("edit_origPrice");
        editNewRegPrice = document.getElementById("edit_newRegPrice");
        editNewPaid = document.getElementById("edit_newPaid");
        editOrigPaid = document.getElementById("edit_origPaid");
        editNewCoupon = document.getElementById("edit_newCoupon");
        editOrigCoupon = document.getElementById("edit_origCoupon");
        editNewCouponDiscount = document.getElementById("edit_newCouponDiscount");
        editOrigCouponDiscount = document.getElementById("edit_origCouponDiscount");
        editStatusSelect = document.getElementById("edit_statusSelect");
        editOrigStatus = document.getElementById("edit_origStatus");
        editSaveOverride = document.getElementById("edit_saveOverride");
    }

    id = document.getElementById('receipt');
    if (id != null) {
        receiptModal = new bootstrap.Modal(id, { focus: true, backdrop: 'static' });
        $('#receipt').on('hide.bs.modal', function () {
            recepitEmailAddress = null;
        });
    }

    notes = new Notes('', config.userid);

    id = document.getElementById('history');
    if (id != null) {
        historyModal = new bootstrap.Modal(id, { focus: true, backdrop: 'static' });
        historyTitle = document.getElementById('historyTitle');
        historyDiv = document.getElementById('history-div');
    }

    testdiv = document.getElementById('test');
    reglistDiv = document.getElementById('reglist-csv-div');

    $('#registration-table').html('<button class="btn btn-primary mb-4 ms-4" onclick="getData();">Load Registration List</button>');
}

// filters for RegistrationList
// click functions to toggle a single row in a filter
function catclicked(e, cell) {
    var filtercell = cell.getRow().getCell("memCategory");
    var value = filtercell.getValue();
    if (filtercell.getElement().style.backgroundColor) {
        registrationtable.removeFilter("category", "in", catfilter);
        catfilter = catfilter.filter(arrayItem => arrayItem !== value);
        if (catfilter.length > 0) {
            registrationtable.addFilter("category", "in", catfilter);
        }
        filtercell.getElement().style.backgroundColor = "";
    } else {
        if (catfilter.length > 0) {
            registrationtable.removeFilter("category", "in", catfilter);
        }
        catfilter.push(value);
        registrationtable.addFilter("category", "in", catfilter);
        filtercell.getElement().style.backgroundColor = "#C0FFC0";
    }
}

function typeclicked(e, cell) {
    var filtercell = cell.getRow().getCell("memType");
    var value = filtercell.getValue();
    if (filtercell.getElement().style.backgroundColor) {
        registrationtable.removeFilter("type", "in", typefilter);
        typefilter = typefilter.filter(arrayItem => arrayItem !== value);
        if (typefilter.length > 0) {
            registrationtable.addFilter("type", "in", typefilter);
        }
        filtercell.getElement().style.backgroundColor = "";
    } else {
        if (typefilter.length > 0) {
            registrationtable.removeFilter("type", "in", typefilter);
        }
        typefilter.push(value);
        registrationtable.addFilter("type", "in", typefilter);
        filtercell.getElement().style.backgroundColor = "#C0FFC0";
    }
}

function ageclicked(e, cell) {
    var filtercell = cell.getRow().getCell("memAge");
    var value = filtercell.getValue();
    if (filtercell.getElement().style.backgroundColor) {
        registrationtable.removeFilter("age", "in", agefilter);
        agefilter = agefilter.filter(arrayItem => arrayItem !== value);
        if (agefilter.length > 0) {
            registrationtable.addFilter("age", "in", agefilter);
        }
        filtercell.getElement().style.backgroundColor = "";
    } else {
        if (agefilter.length > 0) {
            registrationtable.removeFilter("age", "in", agefilter);
        }
        agefilter.push(value);
        registrationtable.addFilter("age", "in", agefilter);
        filtercell.getElement().style.backgroundColor = "#C0FFC0";
    }
}

function priceclicked(e, cell) {
    var filtercell = cell.getRow().getCell("price");
    var value = filtercell.getValue();
    if (filtercell.getElement().style.backgroundColor) {
        registrationtable.removeFilter("price", "in", pricefilter);
        pricefilter = pricefilter.filter(arrayItem => arrayItem !== value);
        if (pricefilter.length > 0) {
            registrationtable.addFilter("price", "in", pricefilter);
        }
        filtercell.getElement().style.backgroundColor = "";
    } else {
        if (pricefilter.length > 0) {
            registrationtable.removeFilter("price", "in", pricefilter);
        }
        pricefilter.push(value);
        registrationtable.addFilter("price", "in", pricefilter);
        filtercell.getElement().style.backgroundColor = "#C0FFC0";
    }
}

function labelclicked(e, cell) {
    var filtercell = cell.getRow().getCell("label");
    var value = filtercell.getValue();
    if (filtercell.getElement().style.backgroundColor) {
        registrationtable.removeFilter("label", "in", labelfilter);
        labelfilter = labelfilter.filter(arrayItem => arrayItem !== value);
        if (labelfilter.length > 0) {
            registrationtable.addFilter("label", "in", labelfilter);
        }
        filtercell.getElement().style.backgroundColor = "";
    } else {
        if (labelfilter.length > 0) {
            registrationtable.removeFilter("label", "in", labelfilter);
        }
        labelfilter.push(value);
        registrationtable.addFilter("label", "in", labelfilter);
        filtercell.getElement().style.backgroundColor = "#C0FFC0";
    }
}

function couponclicked(e, cell) {
    var filtercell = cell.getRow().getCell("name");
    value = filtercell.getValue();
    if (filtercell.getElement().style.backgroundColor) {
        registrationtable.removeFilter("name", "in", couponfilter);
        couponfilter = couponfilter.filter(arrayItem => arrayItem !== value);
        if (couponfilter.length > 0) {
            registrationtable.addFilter("name", "in", couponfilter);
        }
        filtercell.getElement().style.backgroundColor = "";
    } else {
        if (couponfilter.length > 0) {
            registrationtable.removeFilter("name", "in", couponfilter);
        }
        couponfilter.push(value);
        registrationtable.addFilter("name", "in", couponfilter);
        filtercell.getElement().style.backgroundColor = "#C0FFC0";
    }
}

function statusclicked(e, cell) {
    var filtercell = cell.getRow().getCell("name");
    value = filtercell.getValue();
    if (filtercell.getElement().style.backgroundColor) {
        registrationtable.removeFilter("status", "in", statusfilter);
        statusfilter = statusfilter.filter(arrayItem => arrayItem !== value);
        if (statusfilter.length > 0) {
            registrationtable.addFilter("status", "in", statusfilter);
        }
        filtercell.getElement().style.backgroundColor = "";
    } else {
        if (statusfilter.length > 0) {
            registrationtable.removeFilter("status", "in", statusfilter);
        }
        statusfilter.push(value);
        registrationtable.addFilter("status", "in", statusfilter);
        filtercell.getElement().style.backgroundColor = "#C0FFC0";
    }
}

// clear all filter items from the clicked filter section
function clearfilter() {
    if (typefilter.length > 0) {
        registrationtable.removeFilter("type", "in", typefilter);
        typefilter = [];
        var rows = type.getRows();
        for (var row of rows) {
            row.getCell("memType").getElement().style.backgroundColor = "";
        }
    }
    if (catfilter.length > 0) {
        registrationtable.removeFilter("category", "in", catfilter);
        catfilter = [];
        var rows = category.getRows();
        for (var row of rows) {
            row.getCell("memCategory").getElement().style.backgroundColor = "";
        }
    }
    if (agefilter.length > 0) {
        registrationtable.removeFilter("age", "in", agefilter);
        agefilter = [];
        var rows = age.getRows();
        for (var row of rows) {
            row.getCell("memAge").getElement().style.backgroundColor = "";
        }
    }
    if (pricefilter.length > 0) {
        registrationtable.removeFilter("price", "in", pricefilter);
        pricefilter = [];
        var rows = price.getRows();
        for (var row of rows) {
            row.getCell("price").getElement().style.backgroundColor = "";
        }
    }
    if (labelfilter.length > 0) {
        registrationtable.removeFilter("label", "in", labelfilter);
        labelfilter = [];
        var rows = label.getRows();
        for (var row of rows) {
            row.getCell("label").getElement().style.backgroundColor = "";
        }
    }
    if (couponfilter.length > 0) {
        registrationtable.removeFilter("name", "in", couponfilter);
        couponfilter = [];
        var rows = coupon.getRows();
        for (var row of rows) {
            row.getCell("name").getElement().style.backgroundColor = "";
        }
    }
    if (statusfilter.length > 0) {
        registrationtable.removeFilter("name", "in", statusfilter);
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
    var hcount = data['hcount'];
    var complete_trans = data['complete_trans'];
    var index = cell.getRow().getIndex();

    var btns = "";
    if (perid > 0) {
        btns += '<button class="btn btn-secondary me-1" style = "--bs-btn-padding-y: .0rem; --bs-btn-padding-x: .3rem; --bs-btn-font-size: .75rem;",' +
            ' onclick="changeReg(' + index + ')">Chgs</button>';
    }

    // receipt button
    if (paid > 0)
        btns += '<button class="btn btn-primary me-1" style = "--bs-btn-padding-y: .0rem; --bs-btn-padding-x: .3rem; --bs-btn-font-size: .75rem;",' +
            ' onclick="receipt(' + index + ')">Rcpt</button>';

    // history button
    if (hcount > 0)
        btns += '<button class="btn btn-primary me-1" style = "--bs-btn-padding-y: .0rem; --bs-btn-padding-x: .3rem; --bs-btn-font-size: .75rem;",' +
            ' onclick="history(' + index + ')">Hist</button>';

    // notes button
    if (ncount != null && ncount > 0)
        btns += '<button class="btn btn-primary" style = "--bs-btn-padding-y: .0rem; --bs-btn-padding-x: .3rem; --bs-btn-font-size: .75rem;",' +
            ' onclick="showRegNotes(' + index + ', true)">Notes</button>';

return btns;
}

//// Receipt Start
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
// receipt - display a receipt for the transaction for this registration
function receipt(index) {
    var row = registrationtable.getRow(index);
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

/// History Start
// display history: use the modal to show the history for this reg id
function history(index) {
    historyRow = registrationtable.getRow(index).getData();
    var regid = historyRow.badgeId;
    $.ajax({
        method: "POST",
        url: "scripts/regadmin_getRegHistory.php",
        data: { regid: regid },
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
            displayHistory(data);
            if (data['success'] !== undefined)
                show_message(data.success, 'success');
        },
        error: function (jqXHR, textStatus, errorThrown) {
            showError("ERROR in getReceipt: " + textStatus, jqXHR);
        }
    });
}

function displayHistory(data) {
    var  title = "Registration Change History for " + historyRow.create_trans + ':' + historyRow.badgeId;
    historyTitle.innerHTML = title
    title += "<br/>Person:  " + historyRow.fullName + ' (' + historyRow.perid + "), Email: " + historyRow.email_addr +
        "<br/>Membership: " + historyRow.label;
    // build the history display
    var html = '<div class="row"><div class="col-sm-12"><h1 class="h3">' + title + '</h1></div></div>';
    // format the heading line
    html += "<div class='row'>\n" +
        "<div class='col-sm-2'>Change Date</div>\n" +
        "<div class='col-sm-1'>memId</div>\n" +
        "<div class='col-sm-1'>Price</div>\n" +
        "<div class='col-sm-1'>CpnDsc</div>\n" +
        "<div class='col-sm-1'>Paid</div>\n" +
        "<div class='col-sm-1'>Complete</div>\n" +
        "<div class='col-sm-1'>Update By</div>\n" +
        "<div class='col-sm-1'>Coupon</div>\n" +
        "<div class='col-sm-1'>Plan Id</div>\n" +
        "<div class='col-sm-1'>Status</div>\n" +
        "</div>\n";
    // format the current line
    var current = data['history'][0];
    var color = '';
    var prior = data['history'][0];
    for (var i = 0; i < data['history'].length; i++) {
        var current = data['history'][i];
        html += "<div class='row'>\n";

        // change date
        html += "<div class='col-sm-2'>" + current.change_date + "</div>\n";
        // memId
        color = prior.memId != current.memId ? ' style="background-color: #ffcdcd;"' : '';
        html += "<div class='col-sm-1'" + color + ">" + current.memId + "</div>\n";
        // price
        color = prior.price != current.price ? ' style="background-color: #ffcdcd;"' : '';
        html += "<div class='col-sm-1'" + color + ">" + current.price + "</div>\n";
        // couponDiscount
        color = prior.couponDiscount != current.couponDiscount ? ' style="background-color: #ffcdcd;"' : '';
        html += "<div class='col-sm-1'" + color + ">" + current.couponDiscount + "</div>\n";
        // paid
        color = prior.paid != current.paid ? ' style="background-color: #ffcdcd;"' : '';
        html += "<div class='col-sm-1'" + color + ">" + current.paid + "</div>\n";
        // complete_trans
        color = prior.complete_trans != current.complete_trans ? ' style="background-color: #ffcdcd;"' : '';
        html += "<div class='col-sm-1'" + color + ">" + current.complete_trans + "</div>\n";
        // updatedBy
        color = prior.updatedBy != current.updatedBy ? ' style="background-color: #ffcdcd;"' : '';
        html += "<div class='col-sm-1'" + color + ">" + current.updatedBy + "</div>\n";
        // coupon
        color = prior.coupon != current.coupon ? ' style="background-color: #ffcdcd;"' : '';
        html += "<div class='col-sm-1'" + color + ">" + current.coupon + "</div>\n";
        // planId
        color = prior.planId != current.planId ? ' style="background-color: #ffcdcd;"' : '';
        html += "<div class='col-sm-1'" + color + ">" + current.planId + "</div>\n";
        // status
        color = prior.status != current.status ? ' style="background-color: #ffcdcd;"' : '';
        html += "<div class='col-sm-1'" + color + ">" + current.status + "</div>\n";

        html += "</div>\n";
        prior = current;
    }
    historyDiv.innerHTML = html;
    historyModal.show();
}

//// notes start
// notes - display the notes for this registration - ajax call to fetch the notes
function showRegNotes(rid, readOnly) {
    if (rid == null || rid == '') {
        show_message("No registration id for this row, seek assistance", "error");
        return;
    }
    notes.getDisplayRegNotes(rid, readOnly);
}

// change - display all reg records for a perid based on this row and offer changes, also used to refresh the modal popup after changes
function changeReg(index, clear = true) {
    if (clear)
        clear_message('changeMessageDiv');

    currentIndex = index;
    var row = registrationtable.getRow(index);
    changeRowdata = row.getData();
    var perid = changeRowdata['perid'];

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
        <div class="col-sm-12"><b>Registrations for ` + rowdata.fullName + ' (' + rowdata.email_addr + '), id: ' + rowdata.perid + `</b></div>
    </div>
      <div class="row">
        <div class="col-sm-12">
            <button class="btn btn-sm btn-light" onclick="changeSelectAll(1);">Select All</button>
            <button class="btn btn-sm btn-light" onclick="changeSelectAll(0);">Clear All</button>
        </div>      
    </div>
    <div class="row">
        <div class="col-sm-1" style="text-align: right;">Selected</div>
        <div class="col-sm-1" style="text-align: right;">Reg ID</div>
        <div class="col-sm-1" style="text-align: right;">Trans ID</div>
        <div class="col-sm-1" style="text-align: right;">Mem ID</div>
        <div class="col-sm-2">Label</div>
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
        <div class="col-sm-2 text-primary">` + rowdata.label + `</div>
        <div class="col-sm-1 text-primary" style="text-align: right;">` + rowdata.price + `</div>
        <div class="col-sm-1 text-primary" style="text-align: right;">` + rowdata.paid + `</div>
        <div class="col-sm-1 text-primary" style="text-align: right;">` + rowdata.couponDiscount + `</div>
        <div class="col-sm-1 text-primary">` + rowdata.status + `</div>
        <div class="col-sm-2">
            <button class="btn btn-sm btn-secondary" onclick="changeEdit(` + rowdata.badgeId + `)";>Edit</button>
            <button class="btn btn-sm btn-secondary" onclick="showRegNotes(` + rowdata.badgeId + `, false)";>Add Note</button>
        </div>
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
        <div class="col-sm-2">` + membership.label + `</div>
        <div class="col-sm-1" style="text-align: right;">` + membership.price + `</div>
        <div class="col-sm-1" style="text-align: right;">` + membership.paid + `</div>
        <div class="col-sm-1" style="text-align: right;">` + membership.couponDiscount + `</div>
        <div class="col-sm-1">` + membership.status + `</div>
        <div class="col-sm-2">
            <button class="btn btn-sm btn-secondary" onclick="changeEdit(` + membership.id + `)";>Edit</button>
            <button class="btn btn-sm btn-secondary" onclick="addNote(` + membership.id + `)";>Add Note</button>
        </div>
    </div>
`;
    }
    html += `
    <div class="row mt-2 mb-2">
        <div class="col-sm-12" style="text-align: center;">
            <button class="btn btn-sm btn-primary" onclick="changeRevoke(0);">Revoke Selected</button>
            <button class="btn btn-sm btn-warning me-4" onclick="changeRevoke(1);">Restore Selected</button>
            <button class="btn btn-sm btn-primary me-4" onclick="changeTransfer();">Transfer Selected</button>
`;
    if (config['oneoff'] == 0) {
        html += '<button class="btn btn-sm btn-primary me-4" onclick="changeRollover();">Rollover Selected</button>\n';
    }
    if (config['finance'] == 1) {
        html += '<button class="btn btn-sm btn-primary" onclick="changeRefund();">Refund Selected</button>\n';
    }
    html += `
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

//// Revoke Start
// process the revoke/restore requests, validate the selections and if allowed call the AJAX call to process the request
function changeRevoke(direction) {
    // hide transfer block
    clear_message();
    clear_message('changeMessageDiv');
    transferSearchDiv.hidden = true;
    rolloverDiv.hidden = true;
    editRegDiv.hidden = true;
    changeDirection = direction;
    // check which ones need to be ignored
    var message = '';
    changeList = [];
    for (var i = 0; i < changeMemberships.length; i++) {
        var changeItem = changeMemberships[i];
        var checked = document.getElementById('m-' + changeItem.id).checked;
        if (!checked)
            continue;

        if (direction == 0 && denyRevoke.indexOf(changeItem.status) != -1)  {
            message += "Cannot change " + changeItem.id + " as status " + changeItem.status + " cannot be revoked<br/>";
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
        source: config['source'],
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
            if (data['warn'] !== undefined) {
                show_message(data['warn'], 'warn', 'changeMessageDiv');
                return;
            }
            changeModal.hide();
            getData();
            if (data['success'] !== undefined) {
                show_message(data['success'], 'success', 'changeMessageDiv');
            }
            if (data['message'])
                show_message(data['message'], 'success', 'changeMessageDiv');
        },
        error: function (jqXHR, textStatus, errorThrown) {
            showError("ERROR in cancelReg: " + textStatus, jqXHR);
        }
    });
}
//// Revoke End

//// Transfer Start
// process the transfer/return requests, validate the selections and if allowed show the part of the modal to request to whom to transfer
function changeTransfer() {
    clear_message();
    clear_message('changeMessageDiv');
    rolloverDiv.hidden = true;
    editRegDiv.hidden = true;

    // check which ones need to be ignored
    var message = '';
    changeList = [];
    var registrationList = '';
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
        registrationList += changeItem.id + ':' + changeItem.label + '  ';
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

    transferFromNameDiv.innerHTML = changeRowdata.perid + ': ' + changeRowdata.fullName;
    transferFromRegistrationDiv.innerHTML =  registrationList;
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
                {column: "fullName", dir: "asc"},
            ],
            columns: [
                {title: "Transfer", width: 90, headerFilter: false, headerSort: false, formatter: addTransferIcon, formatterParams: {t: "result"},},
                {title: "ID", field: "perid", width: 120, hozAlign: "right", headerHozAlign: "right" },
                {field: "index", visible: false,},
                {field: "regcnt", visible: false,},
                {title: "Name", field: "fullName", width: 200, headerFilter: true, headerWordWrap: true, tooltip: build_record_hover,},
                {field: "last_name", visible: false,},
                {field: "first_name", visible: false,},
                {field: "middle_name", visible: false,},
                {field: "suffix", visible: false,},
                {title: "Badge Name", field: "badge_name", width: 200, headerFilter: true, headerWordWrap: true, tooltip: true,},
                {title: "Zip", field: "postal_code", headerFilter: true, headerWordWrap: true, tooltip: true, maxWidth: 100, width: 100},
                {title: "Email Address", field: "email_addr", width: 200, headerFilter: true, headerWordWrap: true, tooltip: true,},
                {title: "Current Registrations", field: "regs", headerFilter: true, headerWordWrap: true, tooltip: true,},
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
        source: config['source'],
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
            //console.log(data);
            if (data.error) {
                show_message(data.error, 'error', 'changeMessageDiv');
            } else if (data.warning) {
                changeModal.hide();
                show_message(data.warning, 'warn', 'changeMessageDiv');
            } else {
                transferSearchDiv.hidden = true;
                transferNameSearchField.value = '';
                transferFromNameDiv.innerHTML = '';
                transferFromRegistrationDiv.innerHTML = '';
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
    editRegDiv.hidden = true;
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
    var optionList = "    <option value=''>Do Not Create New Registration for this row</option>\n" +
        "    <option value='auto'>Auto: Auto Select New Registration for this row</option>\n";
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
        <div class="col-sm-3" style="text-align:right"><input type="checkbox" value="Y" id="c-` + i + '"/>' +
            '<label for="c-' + item + `">&nbsp;Override Already Printed Check</label>
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
        source: config['source'],
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
            rolloverSelect.innerHTML = '';
            rolloverDiv.hidden = true;
            changeModal.hide();
            getData();
            if (data.message)
                show_message(data.message, 'success');
        },
        error: function (jqXHR, textStatus, errorThrown) {
            showError("ERROR in rolloverReg: " + textStatus, jqXHR);
        }
    });
}
//// Rollover End

//// Refund start
// changeRefund - validate / start the refund process
function changeRefund() {
    show_message("Not Yet", 'warn', 'changeMessageDiv');
}
//// Refund End

//// Edit Start
// changeEdit - edit a single registration record within limits
function changeEdit(badgeId) {
    var ageList = {all: 1};

    transferSearchDiv.hidden = true;
    rolloverDiv.hidden = true;
    editRegDiv.hidden = false;

    currentIndex = badgeId;
    currentRow = registrationtable.getRow(badgeId).getData();
    var curMemId = currentRow.memId;
    ageList[memLabelsIdx[curMemId].memAge] = 1;


    // build the select list, first the ones for this mem Age and All
    var memOptionList = '<select id="newMemId" onchange="changeEditRegChange();">' + "\n";
    for (var i = 0; i < memLabels.length; i++) {
        var memItem = memLabels[i];
        if (ageList[memItem.memAge]) {
            memOptionList += '   <option value="' + memItem.id + '"' + (currentRow.memId == memItem.id ? ' selected' : '') + '>' +
            memItem.id + ':' + memItem.memAge + '-' + memItem.memType + '-' + memItem.memCategory + ' $' + memItem.price + ' ' +
            memItem.shortname + "</option>\n";
        }
    }
    // now the rest of them
    for (var i = 0; i < memLabels.length; i++) {
        var memItem = memLabels[i];
        if (!ageList[memItem.memAge]) {
            memOptionList += '   <option value="' + memItem.id + '"' + (currentRow.memId == memItem.id ? ' selected' : '') +'>' +
            memItem.id + ':' + memItem.memAge + '-' + memItem.memType + '-' + memItem.memCategory + ' $' + memItem.price + ' ' +
            memItem.shortname + "</option>\n";
        }
    }
    memOptionList += "</select>\n";

    var statuses = ['unpaid','plan','paid','cancelled','refunded','transfered','upgraded','rolled-over'];
    var statusSelect = "<select id='newStatus'>\n";
    for (var i = 0; i < statuses.length; i++) {
        statusSelect += '<option value="' + statuses[i] + '"' + (currentRow.status == statuses[i] ? ' selected' : '') +
            ">" + statuses[i] + "</option>\n";
    }
    statusSelect += "</select>\n";

    // now fill in the current data
    editRegLabel.innerHTML = currentRow.badgeId + ': ' + currentRow.label;
    editMemSelect.innerHTML = memOptionList;
    editOrigMemlabel.innerHTML = currentRow.label;
    editNewPrice.value = currentRow.price == null ? '' : currentRow.price;
    editOrigPrice.innerHTML = currentRow.price == null ? '' : currentRow.price;
    editNewRegPrice.innerHTML = '';
    if (editNewPaid)
        editNewPaid.value = currentRow.paid == null ? '' : currentRow.paid;
    if (editOrigPaid)
        editOrigPaid.innerHTML = currentRow.paid == null ? '' : currentRow.paid;
    editNewCoupon.value = currentRow.coupon == null ? '' : currentRow.coupon;
    editOrigCoupon.innerHTML = currentRow.coupon == null ? '' : currentRow.coupon;
    editNewCouponDiscount.value = currentRow.couponDiscount == null ? '' : currentRow.couponDiscount;
    editOrigCouponDiscount.innerHTML = currentRow.couponDiscount == null ? '' : currentRow.couponDiscount;
    if (editStatusSelect)
        editStatusSelect.innerHTML = statusSelect;
    if (editOrigStatus)
        editOrigStatus.innerHTML = currentRow.status;
}

// changeEditRegChange - populate change fields on reg item change
function changeEditRegChange() {
    var regItemId = document.getElementById("newMemId").value;
    if (regItemId != '') {
        var newMemItem = memLabelsIdx[regItemId];
        editNewRegPrice.innerHTML = newMemItem.price;
    }
}

// changeEditSave - validate the changes and save them
function changeEditSave(override) {
    var newMemId = document.getElementById("newMemId").value;
    var newPrice = editNewPrice.value;
    var newPaid = null;
    var newCoupon = editNewCoupon.value;
    var newDiscount = editNewCouponDiscount.value;
    if (newDiscount == '')
        newDiscount = 0.00;
    var newStatusSelect = document.getElementById("newStatus");
    var newStatus = null;
    if (newStatusSelect)
        newStatus = document.getElementById('newStatus').value;

    if (editNewPaid)
        newPaid = editNewPaid.value;

    if (newMemId == '')
        newMemId = currentRow.memId;

    editSaveOverride.hidden = true;
    if (override > 0) {
        clear_message('changeMessageDiv');
    } else {
        // now some simple validations
        var warnings = '';
        var numWarnings = 0;
        if (config['finance'] == 1) {
            var balanceDue = Number(newPrice) - (Number(newPaid) + Number(newDiscount));
            if (newPrice != (Number(newPaid) + Number(newDiscount))) {
                warnings += 'Price of ' + newPrice + ' does not equal the sum of Paid + Coupon Discount of ' +
                    (Number(newPaid) + Number(newDiscount)) + '<br/>';
                numWarnings++;
            }
        }

        if (newPrice != Number(memLabelsIdx[newMemId].price)) {
            warnings += 'Price of ' + newPrice + ' does not equal the registration item price of ' + memLabelsIdx[newMemId].price + '<br/>';
            numWarnings++;
        }

        if (Number(newDiscount) > 0 && newCoupon == '') {
            warnings += 'Coupon Discount of ' + Number(newDiscoint) + 'is > 0, yet the coupon is blank<br/>';
            numWarnings++;
        }

        if (config['finance'] == 1) {
            if (balanceDue > 0 && (newStatus == 'paid' || newStatus == 'upgraded')) {
                warnings += 'There is a balance Due of ' + balanceDue + " and the record is paid/uograded, it needs to be 'unpaid'." +
                    " If you continue it will be set to unpaid.<br/>";
                numWarnings++;
            }

            if (balanceDue < 0) {
                warnings += 'There is a refund of ' + Number(-balanceDue) + ' due to your changes<br/>';
                numWarnings++;
            }
        }

        if (numWarnings > 0) {
            editSaveOverride.hidden = false;
            show_message("Please confirm you wish to save anyway due to these warnings:<br/>&nbsp;<br/>" + warnings,
                'warn', 'changeMessageDiv');
            return;
        }
    }
    // ok, a clean validition or an overide, do the save
    var data = {
        action: 'edit',
        old: currentRow,
        id: currentRow.badgeId,
        new: {
            memId: newMemId,
            price: newPrice,
            paid: newPaid,
            coupon: newCoupon,
            couponDiscount: newDiscount,
            status: newStatus,
        },
        source: config['source'],
    };
    var script = 'scripts/regadmin_editReg.php';
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
            changeEditClose();
            getData();
            if (data.message)
                show_message(data.message, 'success');
        },
        error: function (jqXHR, textStatus, errorThrown) {
            showError("ERROR in rolloverReg: " + textStatus, jqXHR);
        }
    });
    return;
}
// changeEditClose - clean up the form and close it, used for discard and after Save
function changeEditClose() {
    editRegLabel.innerHTML = '';
    editMemSelect.innerHTML = '';
    editOrigMemlabel.innerHTML = '';
    editNewPrice.value = '';
    editOrigPrice.innerHTML = '';
    editNewRegPrice.innerHTML = '';
    if (editNewPaid)
        editNewPaid.value = '';
    if (editOrigPaid)
        editOrigPaid.innerHTML = '';
    editNewCoupon.value = '';
    editOrigCoupon.innerHTML = '';
    editNewCouponDiscount.value = '';
    editOrigCouponDiscount.innerHTML = '';
    if (editStatusSelect)
        editStatusSelect.innerHTML = '';
    if (editOrigStatus)
        editOrigStatus.innerHTML = '';
    currentRow = null;
    currentItem = null;

    editRegDiv.hidden = true;
    changeModal.hide();
}
//// Edit End
// draws the registration List table of registrations found
function draw_registrations(data) {
    if (registrationtable !== null) {
        registrationtable.destroy();
        registrationtable = null;
    }
    registrationtable = new Tabulator('#registration-table', {
        data: data['badges'],
        layout: "fitDataTable",
        index: "badgeId",
        pagination: true,
        paginationSize: 25,
        paginationSizeSelector: [10, 25, 50, 100, 250, true], //enable page size select element with these options
        columns: [
            { title: "Action", formatter: actionbuttons, hozAlign:"left", headerSort: false },
            { title: "TID", field: "display_trans", hozAlign: "right",  headerSort: true, headerFilter: true },
            { title: "PID", field: "perid", width: 110, hozAlign: "right", headerSort: true, headerFilter: true, },
            { title: "Manager", field: "manager", width: 110, hozAlign: "right", headerSort: true, headerFilter: true, },
            { title: "Full Name", field: "fullName", headerSort: true, headerFilter: true, headerFilterFunc: fullNameHeaderFilter, },
            { title: "Badge Name", field: "badge_name", headerSort: true, headerFilter: true },
            { title: "Email", field: "email_addr", headerSort: true, headerFilter: true },
            { title: "Membership Type", field: "label", width: 300, headerSort: true, headerFilter: true, },
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
            { field: "hcount", visible: false,},
            {field: 'first_name', visible: false,},
            {field: 'middle_name', visible: false,},
            {field: 'last_name', visible: false,},
        ],
        initialSort: [
            {column: "display_trans", dir: "desc" },
            {column: "change_date", dir: "desc" },
        ],
    });
    reglistDiv.hidden = false;
}

// save off the csv file
function reglistCSV() {
    if (registrationtable == null)
        return;

    var filename = 'registrations';
    var tabledata = JSON.stringify(registrationtable.getData("active"));
    var excludeList = ['hcount','ncount'];
    downloadCSVPost(filename, tabledata, excludeList);
}

// called from data load - draws the filter stats block and the registrations block
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
    draw_registrations(data);
}

// ajax call to retrieve the starting set of data for the filters and the registration list
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
    if (tabname != 'registrationlist-pane') {
        reglistDiv.hidden = true;
        if (registrationtable) {
            registrationtable.destroy();
            registrationtable = null;
        }
    }

    // now open the relevant one, and create the class if needed
    switch (tabname) {
        case 'registrationlist-pane':
            getData();
            break;
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
        case 'memconfig-pane':
            if (mem == null)
                mem = new memsetup();
            mem.open();
            break;
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

// reg note items
function addNote(regId) {
    showRegNotes(regId, false);
}