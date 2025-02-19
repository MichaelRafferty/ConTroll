// globals for the regadmin tabs

// debug meaning
//  1 = console.logs
//  2 = show hidden table fields
//  4 = show hidden div
debug = 0;

var reportContentDiv = null;
var reportTable = null;

// initialization at DOM complete
window.onload = function initpage() {
    reportContentDiv = document.getElementById('report-content-div');
}


// save off the csv file
function reportCSV() {
    if (registrationtable == null)
        return;

    var filename = 'registrations';
    var tabledata = JSON.stringify(registrationtable.getData("active"));
    var excludeList = ['hcount','ncount'];
    downloadCSVPost(filename, tabledata, excludeList);
}

function settab(tabname) {
    // now open the relevant one, and create the class if needed
    console.log(tabname);
}

function getRpt(reportName, prefix, fileName) {
    console.log(reportName, prefix,  fileName);
    // now open the relevant one, and create the class if needed
    var script = 'scripts/loadReport.php'
    var postdata = {
        group: fileName,
        prefix: prefix,
        report: reportName,
        action: 'fetch',
    };
    clear_message();
    clearError();
    reportContentDiv.innerHTML = '';
    $.ajax({
        url: script,
        method: 'POST',
        data: postdata,
        success: function (data, textStatus, jhXHR) {
            drawReport(data);
        },
        error: function (jqXHR, textStatus, errorThrown) {
            showError("ERROR in " + script + ": " + textStatus, jqXHR);
            show_message("ERROR in " + script + ": " + jqXHR.responseText, 'error');
            return false;
        }
    });
}

function drawReport(data) {
    console.log("back");
    console.log(data);
    if (data.error) {
       show_message(data.error, 'error');
       if (data['sql'])
           console.log(data['sql']);
       return;
    }

    if (reportTable) {
        reportTable.destroy();
        reportTable = null;
    }

    // build result area
    var html = `
    <div class="row">
        <div class="col-sm-12">
            <h1>` + data.report.name + `</h1>
        </div>
    </div>
    <div class="row">
        <div class="col-sm-12">
            <strong>` + data.report.description + `</strong>
        </div>
    </div>
    <div class="row">
        <div class="col-sm-12" id="reportTable" name="reportTable">
       </div>
    </div>
    `;
    reportContentDiv.innerHTML = html;

    // build tabulator specs
    params = {
        data: data.data,
        layout: "fitDataTable",
        pagination: true,
        paginationSize: 25,
        paginationSizeSelector: [10, 25, 50, 100, 250, true], //enable page size select element with these options
    };

    // build the column list
    var fields = data.fields;
    var columns = []
    for (var i = 0; i < fields.length; i++) {
        var field = fields[i];
        var column = {};
        if (field.hasOwnProperty('title')) {
            column.title = field.title;
            column.headerWordWrap = true;
        }
        if (field.hasOwnProperty('name'))
            column.field = field.name;
        if (field.hasOwnProperty('sort'))
            column.headerSort = true;
        if (field.hasOwnProperty('width'))
            column.width = true;
        if (field.hasOwnProperty('minWidth'))
            column.minWidth = true;
        if (field.hasOwnProperty('align')) {
            column.hozAlign = field.align;
            column.headerHozAlign = field.align;
        }
        if (field.hasOwnProperty('visible'))
            column.visible = field.visible;
        if (field.hasOwnProperty('filter')) {
            switch (field.filter) {
                case 'textarea':
                case 'true':
                    column.headerFilter = field.filter;
                    break;
                case 'number':
                    column.headerFilter = true;
                    column.headerFilterFunc = numberHeaderFilter;
                    break;
                case 'fullname':
                    column.headerFilter = true;
                    column.headerFilterFunc = fullNameHeaderFilter;
                    break;
                default:
                    column.headerFilter = true;
            }
        }
        console.log(column);
        columns.push(column);
    }
    params.columns = columns;

    if (data.hasOwnProperty('index')) {
        params.index = data.index;
    }

    // open table
    reportTable =  new Tabulator('#reportTable', params);

    if (data.success) {
        show_message(data.success, 'success');
    }
}