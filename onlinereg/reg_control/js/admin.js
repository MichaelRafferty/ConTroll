//import { TabulatorFull as Tabulator } from 'tabulator-tables';

var current_active = false;
var users_active = false;
var next_active = false;
var current_condata = null;
var current_memlist = null;
var next_condata = null;
var next_memlist = null;
var next_conlistDirty = false;
var proposed = ' ';

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
            { title: "ID", field: "id", visible: false },
            { title: "Con", field: "conid" },
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
    proposed = ' ';

    if (data['conlist-type'] == 'proposed')
        proposed = ' Proposed ';

    var html = '<h5><strong>' + proposed + `Next Convention Data:</strong></h5>
<div id="next-conlist"></div>
<div id="conlist-buttons">  
    <button id="nextconlist-undo" type="button" class="btn btn-secondary btn-sm" onclick="undoNextConlist(); return false;" disabled>Undo</button>
    <button id="nextconlist-redo" type="button" class="btn btn-secondary btn-sm" onclick="redoNextConlist(); return false;" disabled>Redo</button>
    <button id="nextconlist-save" type="button" class="btn btn-primary btn-sm"  onclick="saveNextConlist(); return false;" disabled>Save Changes</button>
</div>
<div>&nbsp;</div>
<h5><strong>Next Membership Types:</strong></h5>
<div id="next-memlist"></div>
`;
    $('#nextconsetup-pane').empty().append(html);
    $('#test').empty();
    condate = new Date(data['startdate']);
    conyear = condate.getFullYear();
    mindate = conyear + "-01-01";
    maxdate = conyear + "-12-31";
    dateformat = 'yyyy-MM-dd';

    if (proposed != ' ') {
        var savebtn = document.getElementById("nextconlist-save");
        savebtn.innerHTML = "Save Changes*";
        savebtn.disabled = false;
    }        

    next_condata = null;

    if (data['conlist'] == null) {
        $('#next-conlist').empty().append("Nothing defined yet");
    } else {
        next_condata = new Tabulator('#next-conlist', {
            maxHeight: "400px",
            history: true,
            data: [data['conlist']],
            layout: "fitDataTable",
            columns: [
                { title: "ID", field: "id", headerSort: false },
                { title: "Name", field: "name", headerSort: false, editor: "input" },
                { title: "Label", field: "label", headerSort: false, editor: "input" },
                { title: "Start Date", field: "startdate", headerSort: false, editor: "date" },
                { title: "End Date", field: "enddate", headerSort: false, editor: "date" }
            ],
        });
    }

    next_condata.on("dataChanged", function (data) {
        //data - the updated table data
        next_conlistDirty = true;
        var savebtn = document.getElementById("nextconlist-save");
        savebtn.innerHTML = "Save Changes*";
        savebtn.disabled = false;
        if (this.getHistoryUndoSize() > 0) {
            document.getElementById("nextconlist-undo").disabled = false;
        }
    });

    next_memlist = null;

    if (data['memlist'] == null) {
        $('#next-memlist').empty().append("Nothing defined yet");
    } else {
        next_memlist = new Tabulator('#next-memlist', {
            maxHeight: "600px",
            data: data['memlist'],
            layout: "fitDataTable",
            columns: [
                { title: "ID", field: "id", visible: false },
                { title: "Con ID", field: "conid" },
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

function undoNextConlist() {
    if (next_condata != null) {
        next_condata.undo();

        var undoCount = next_condata.getHistoryUndoSize();
        if (undoCount <= 0) {
            document.getElementById("nextconlist-undo").disabled = true;
            next_conlistDirty = false;
            if (proposed == ' ') {
                var savebtn = document.getElementById("nextconlist-save");
                savebtn.innerHTML = "Save Changes";
                savebtn.disabled = true;
            }
        }
        var redoCount = next_condata.getHistoryRedoSize();
        if (redoCount > 0) {
            document.getElementById("nextconlist-redo").disabled = false;
        }
    }
};

function redoNextConlist() {
    if (next_condata != null) {
        next_condata.redo();

        var undoCount = next_condata.getHistoryUndoSize();
        if (undoCount > 0) {
            document.getElementById("nextconlist-undo").disabled = false;
            next_conlistDirty = true;
            var savebtn = document.getElementById("nextconlist-save");
            savebtn.innerHTML = "Save Changes*";
            savebtn.disabled = false;
        }
        var redoCount = next_condata.getHistoryRedoSize();
        if (redoCount <= 0) {
            document.getElementById("nextconlist-redo").disabled = true;
        }
    }
};

function saveNextConlistComplete(data, textStatus, jhXHR) {
    if (data['error'] && data['error' != '']) {
        showError(data['error']);
    } else {
        showError(data['success']);
    }
    var savebtn = document.getElementById("nextconlist-save");
    savebtn.innerHTML = "Save Changes";
}

function saveNextConlist() {
    if (next_condata != null) {
        var savebtn = document.getElementById("nextconlist-save");
        savebtn.innerHTML = "Saving...";
        savebtn.disabled = true;

        var script = "scripts/updateCondata.php";

        var postdata = {
            ajax_request_action: "update_nextcondata",
            tabledata: next_condata.getData(),
            tablename: "conlist",
            indexcol: "id"
        };
        //console.log(postdata);
        $.ajax({
            url: script,
            method: 'POST',
            data: postdata,
            success: saveNextConlistComplete,
            error: function (jqXHR, textStatus, errorThrown) {
                showError("ERROR in " + script + ": " + textStatus, jqXHR);
                return false;
            }
        });
    }
};


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
