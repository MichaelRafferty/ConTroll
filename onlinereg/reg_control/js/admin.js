//import { TabulatorFull as Tabulator } from 'tabulator-tables';

var current_active = false;
var users_active = false;
var next_active = false;
var current_condata = null;
var current_memlist = null;
var next_condata = null;
var next_memlist = null;

function clearPermissions(userid) {
    var formdata = $("#" + userid).serialize();
    $('#test').append(formdata);
    $.ajax({
        url: 'scripts/permUpdate.php',
        method: 'POST',
        data: formdata+"&action=clear",
        success: function (data, textStatus, jhXHR) {
            $('#test').append(JSON.stringify(data, null, 2));
            location.reload();
        }
    });
}

function updatePermissions(userid) {
    var formdata = $("#" + userid).serialize();
    $('#test').append(formdata);
    $.ajax({
        url: 'scripts/permUpdate.php',
        method: 'POST',
        data: formdata+"&action=update",
        success: function (data, textStatus, jhXHR) {
            $('#test').append(JSON.stringify(data, null, 2));
            location.reload();
        }
    });
}


function createAccount() {
    var formdata = $("#createUserForm").serialize();
    $('#test').append(formdata);
    $.ajax({
        url: 'scripts/permUpdate.php',
        method: 'POST',
        data: formdata+"&action=create",
        success: function (data, textStatus, jhXHR) {
            $('#test').append(JSON.stringify(data, null, 2));
            location.reload();
        }
    });
}

function draw_current(data, textStatus, jhXHR) {
    //console.log('in draw current');
    //console.log(data);

    var html = `<h5><strong>Current Convention Data:</strong></h5>
<div id="current-conlist"></div>
<h5><strong>Current Membership Types:</strong></h5>
<div id="current-memlist"></div>
`;
    $('#consetup-pane').empty().append(html);
    $('#test').empty();

    current_condata = null;

    current_condata = new Tabulator('#current-conlist', {
        maxHeight: "400px",
        data: [ data['conlist'] ],
        layout: "fitDataTable",        
        columns: [
            { title: "ID", field: "id", headerSort: false },
            { title: "Name", field: "name", headerSort: false },
            { title: "Label", field: "label", headerSort: false },
            { title: "Start Date", field: "startdate", headerSort: false },
            { title: "End Date", field: "enddate", headerSort: false }
        ],
    });

    current_memlist = null;
    current_memlist = new Tabulator('#current-memlist', {
        maxHeight: "600px",
        data: data['memlist'],
        layout: "fitDataTable",
        columns: [
            { title: "ID", field: "id" },
            { title: "Sort", field: "sort_order", headerSort: false },
            { title: "Category", field: "memCategory" },
            { title: "Type", field: "memType" },
            { title: "Age", field: "memAge" },
            {
                title: "Name", field: "shortname",
                tooltip: function (e, cell, onRendered) { return cell.getRow().getCell("label").getValue(); }
            },
            { title: "Label", field: "label", visible: false },
            { title: "Price", field: "price" },
            { title: "Start Date", field: "startdate" },
            { title: "End Date", field: "enddate" },
            { title: "Atcon", field: "atcon" },
            { title: "Online", field: "online" }
        ],
        
    });
}

function open_current() {
    var script = "scripts/getCondata.php";
    $.ajax({
        url: script,
        method: 'GET',
        data: 'year=current',
        success: draw_current,
        error: function (jqXHR, textStatus, errorThrown) {
            showError("ERROR in " + script + ": " + textStatus, jqXHR);
            return false;
        }
    });
}

function draw_next(data, textStatus, jhXHR) {
    //console.log('in draw current');
    //console.log(data);

    var html = `<h5><strong>Next Convention Data:</strong></h5>
<div id="next-conlist"></div>
<h5><strong>Next Membership Types:</strong></h5>
<div id="next-memlist"></div>
`;
    $('#nextconsetup-pane').empty().append(html);
    $('#test').empty();

    next_condata = null;

    if (data['conlist'] == null) {
        $('#next-conlist').empty().append("Nothing defined yet");
    } else {
        next_condata = new Tabulator('#next-conlist', {
            maxHeight: "400px",
            data: [data['conlist']],
            layout: "fitDataTable",
            columns: [
                { title: "ID", field: "id", headerSort: false },
                { title: "Name", field: "name", headerSort: false },
                { title: "Label", field: "label", headerSort: false },
                { title: "Start Date", field: "startdate", headerSort: false },
                { title: "End Date", field: "enddate", headerSort: false }
            ],
        });
    }

    next_memlist = null;

    if (data['memlist'] == null) {
        $('#next-memlist').empty().append("Nothing defined yet");
    } else {
        next_memlist = new Tabulator('#next-memlist', {
            maxHeight: "600px",
            data: data['memlist'],
            layout: "fitDataTable",
            columns: [
                { title: "ID", field: "id" },
                { title: "Sort", field: "sort_order", headerSort: false },
                { title: "Category", field: "memCategory" },
                { title: "Type", field: "memType" },
                { title: "Age", field: "memAge" },
                {
                    title: "Name", field: "shortname",
                    tooltip: function (e, cell, onRendered) { return cell.getRow().getCell("label").getValue(); }
                },
                { title: "Label", field: "label", visible: false },
                { title: "Price", field: "price" },
                { title: "Start Date", field: "startdate" },
                { title: "End Date", field: "enddate" },
                { title: "Atcon", field: "atcon" },
                { title: "Online", field: "online" }
            ],

        });
    }
}

function open_next() {
    var script = "scripts/getCondata.php";
    $.ajax({
        url: script,
        method: 'GET',
        data: 'year=next',
        success: draw_next,
        error: function (jqXHR, textStatus, errorThrown) {
            showError("ERROR in " + script + ": " + textStatus, jqXHR);
            return false;
        }
    });
}

function close_current() {
    current_condata = null;
    current_memlist = null;
    $('#consetup-pane').empty();
}

function close_next() {
    next_condata = null;
    next_memlist = null;
    $('#nextconsetup-pane').empty();
}

function settab(tabname) {
    switch (tabname) {
        case 'users-pane':
            close_current();
            close_next();
            break;

        case 'consetup-pane':
            close_next();
            open_current();
            break;

        case 'nextconsetup-pane':
            close_current();            
            open_next();
            break;
    }
}
