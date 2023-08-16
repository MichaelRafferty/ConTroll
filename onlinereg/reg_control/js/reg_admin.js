var badgetable = null;
var category = null;
var type = null;
var paid = null;
var label = null;
var age = null;
var coupon = null;
var typefilter = null;
var catfilter = null;
var agefilter = null;
var paidfilter = null;
var labelfilter = null;
var couponfilter = null;
var transfer_modal = null;
var find_result_table = null;
var find_pattern_field = null;
var testdiv = null;

$(document).ready(function () {
    id = document.getElementById('transfer_to');
    if (id != null) {
        transfer_modal = new bootstrap.Modal(id, { focus: true, backdrop: 'static' });
    }

    find_pattern_field = document.getElementById("transfer_name_search");
    find_pattern_field.addEventListener('keyup', (e)=> { if (e.code === 'Enter') transfer_find(); });
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
                    { field: "label" },
                    { field: "percent", formatter: "progress", width: 100, headerSort: false, },
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
}

function transferbutton(cell, formatterParams, onRendered) {
    if (cell.getRow().getCell("price").getValue() > 0 && cell.getRow().getCell("paid").getValue() > 0)
        return '<button class="btn btn-secondary" style = "--bs-btn-padding-y: .0rem; --bs-btn-padding-x: .3rem; --bs-btn-font-size: .75rem;">Transfer</button>';
    if (cell.getRow().getCell("price").getValue() == 0 && cell.getRow().getCell("paid").getValue() == 0)
        return '<button class="btn btn-warning" style = "--bs-btn-padding-y: .0rem; --bs-btn-padding-x: .3rem; --bs-btn-font-size: .75rem;">Transfer</button>';
    return "";
}

// transfer - get information to build modal popup to find person to transfer to
function transfer(edit, cell) {
    if (cell.getRow().getCell("price").getValue() > 0 && cell.getRow().getCell("paid").getValue() == 0)
        return;

    if (cell.getRow().getCell("price").getValue() == 0) {
        if (confirm("This is a free badge, really transfer it?\n(Is it an included vendor badge or similar situation?)") == false)
            returm;
    }

    var badgeid = cell.getRow().getCell("badgeId").getValue();
    var fullname = cell.getRow().getCell('p_name').getValue();
    var badgename = cell.getRow().getCell('p_badge').getValue();

    document.getElementById("transfer_name_search").value = '';
    if (find_result_table != null) {
        find_result_table.destroy();
        find_result_table = null;
    }
    document.getElementById('transfer_from').innerHTML = fullname + '(' + badgename + ')';
    document.getElementById('from_badgeid').value = badgeid;
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
    var name_search = document.getElementById('transfer_name_search').value.toLowerCase().trim();
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

// tabulator formatter for the transfer column for the find results, displays the "transfer" to transfer the membrership
// color based on number of reg records this person already has for this con
function addTransferIcon(cell, formatterParams, onRendered) { //plain text value
    var tid;
    var html = '';
    var banned = cell.getRow().getData().banned == 'Y';
    var regcnt = cell.getRow().getData().regcnt;
    var color = 'btn-success';
    var from = cell.getRow().getData().from;
    var to = cell.getRow().getData().perid;
    if (banned == undefined) {
        tid = Number(cell.getRow().getData().tid);
        html = '<button type="button" class="btn btn-sm btn-success p-0" style="--bs-btn-font-size: 75%;" onclick="add_unpaid(' + tid + ')">Pay</button > ';
        return html;
    }
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
                {title: "Name", field: "fullname", headerFilter: true, headerWordWrap: true, tooltip: build_record_hover,},
                {field: "last_name", visible: false,},
                {field: "first_name", visible: false,},
                {field: "middle_name", visible: false,},
                {field: "suffix", visible: false,},
                {title: "Badge Name", field: "badge_name", headerFilter: true, headerWordWrap: true, tooltip: true,},
                {title: "Zip", field: "postal_code", headerFilter: true, headerWordWrap: true, tooltip: true, maxWidth: 100, width: 100},
                {title: "Email Address", field: "email_addr", headerFilter: true, headerWordWrap: true, tooltip: true,},
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
        pagination: true,
        paginationSize: 10,
        paginationSizeSelector: [10, 25, 50, 100, 250, true], //enable page size select element with these options
        columns: [
            { title: "perid", field: "perid", visible: false },
            { title: "Person", field: "p_name", headerSort: true, headerFilter: true },
            { title: "Badge Name", field: "p_badge", headerSort: true, headerFilter: true },
            { title: "Membership Type", field: "label", headerSort: true, headerFilter: true, },
            { title: "Price", field: "price", hozAlign: "right", headerSort: true, headerFilter: true },
            { title: "Discount", field: "couponDiscount", hozAlign: "right", headerSort: true, headerFilter: true, },
            { title: "Paid", field: "paid", hozAlign: "right", headerSort: true, headerFilter: true, },
            { title: "Coupon", field: "name", headerSort: true, headerFilter: true, },
            { title: "Created", field: "create_date", headerSort: true, headerFilter: true },
            { title: "Changed", field: "change_date", headerSort: true, headerFilter: true },
            { field: "category", visible: false },
            { field: "age", visible: false },
            { field: "type", visible: false },
            { field: "badgeId", visible: false },
            { field: "perid", visible: false },
            { title: "Transfer", formatter: transferbutton, hozAlign:"center", cellClick: transfer, headerSort: false },
        ]
    });
}

function draw(data, textStatus, jqXHR) {
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
    var formData = { 'badge': from, 'perid': to };
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
                console.log("about to call getdata");
                getData();
                console.log("after call getdata");
                if (data.message)
                    show_message(data.message, 'success');

            }
        }
    });
}

function sendCancel() {
    var tid = prompt("Would you like to send a test email?\nIf so please enter the transaction you want to send the test for.");
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
    var email = prompt("Would you like to send a test " + type + " email?\nIf so please enter the address to send the test to.");
    var action = "none";

    if (email == null) {
        if (confirm("You are about to send a " + type + " email to a lot of people.  Are you sure?")) {
            action = 'full';
        } else { return false; }
    } else {
        action = 'test';
    }

    $.ajax({
        url: 'scripts/sendEmail.php',
        data: { 'action': action, 'email': email, 'type': type },
        method: "POST",
        success: function (data, textStatus, jqXHR) {
            if (data.error) {
                $('#test').empty().append(JSON.stringify(data));
                alert(data.error);
            } else {
                $('#test').empty().append(JSON.stringify(data));
            }
        }
    });
}
