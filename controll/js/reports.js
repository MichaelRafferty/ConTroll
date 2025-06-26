// globals for the regadmin tabs

// debug meaning
//  1 = console.logs
//  2 = show hidden table fields
//  4 = show hidden div
debug = 0;

var reportContentTabs = null;
var reportContentDiv = null;
var reportTable = null;
var csvfile = null;
var reportTabs = [];
var reportContents = {};
var reportPromptDiv = null;
var reportFields = null;
var prompts = null;

// initialization at DOM complete
window.onload = function initpage() {
    reportContentTabs = document.getElementsByClassName('report-content');
    reportContentDiv = document.getElementById('report-content-div');
    reportPromptDiv = document.getElementById('report-prompt-div');
    var keys = Object.keys(reports);
    for (var i = 0; i < keys.length; i++) {
        var report = reports[keys[i]];
        reportTabs.push(report.group.name);
        reportContents[report.group.name] = document.getElementById(report.group.name + '-content');
    }

    // enable all tooltips
    const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
    const tooltipList = [...tooltipTriggerList].map(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl));

    if (config.hasOwnProperty('reportName')) {
        runReport(config['reportName']);
    }
}

function settab(tabname) {
    // now open the relevant one, and create the class if needed
    if (reportTable) {
        reportTable.destroy();
        reportTable = null;
    }

    for (var i = 0; i < reportTabs.length; i++) {
        var tab = reportTabs[i];
        var tabCompare = tab + '-pane';
        reportContents[tab].hidden = (tabCompare != tabname);
    }

    reportContentDiv.innerHTML = '';
    reportPromptDiv.innerHTML = '';
    clear_message();
    clearError();
}

function showPrompts(reportName, prefix, fileName) {
    if (!reportPrompts.hasOwnProperty(reportName)) {
        show_message("Report not configured properly, no prompts found, seek assistance", "error");
        return;
    }

    prompts = reportPrompts[reportName];
    reportFields = [];
    console.log(reportName);
    console.log(prompts);
    // build the input area with the prompts...
    var html = ''
    for (var i = 0; i < prompts.length; i++) {
        var prompt = prompts[i];
        if (prompt[0] == 'prompt') {
            html += '<div class="row">\n<div class="col-sm-auto"><label for="P-' + prompt[1] + '">' + prompt[2] + '</label></div>\n';
            html += '<div class="col-sm-auto"><input type="text" id="P-' + prompt[1] + '" name="P-' + prompt[1] + '"';
            if (prompt.length > 3) {
                html += ' placeholder="' + prompt[3] + '" ';
            }
            if (prompt.length > 4) {
                html += ' value="' + prompt[4] + '" ';
            }
            html += '></div>\n</div>\n';
            reportFields.push("P-" + prompt[1]);
        }
    }

    if (html == '') {
        return getRpt(reportName, prefix, fileName);
    }

    html += '<div class="row mt-2">\n<div class="col-sm-auto">\n' +
        '<button class="btn btn-sm btn-primary" type="button" onclick="getRpt(\'' + reportName + '\', \'' + prefix + '\', \'' + fileName + '\');">\n' +
        'Run Report\n</button>\n</div>\n</div>\n';
    reportPromptDiv.innerHTML = html;
}

function noPrompts(reportName, prefix, fileName) {
    reportFields = null;
    reportPromptDiv.innerHTML = '';
    getRpt(reportName, prefix, fileName);
}

function runReport(name) {
    console.log(name);
    console.log(config);
    console.log("setting tab to " + config.group.name);
    settab(config.group.name + '-pane');
    elTab = document.getElementById(config.group.name + '-tab');
    bootstrap.Tab.getOrCreateInstance(elTab).show();
    // highlight the report we need
    rptId = config.values.name.replace(' ', '-') + '-tab';
    console.log(rptId);
    elRpt = document.getElementById(rptId);
    elRpt.classList.add('active');
    // draw the prompts
    showPrompts(config.reportName, config.pageName, config.groupName);
    // initialize the prompts
    var index = 0;
    for (var i = 0; i < prompts.length; i++) {
        var prompt = prompts[i];
        if (prompt[0] == 'prompt') {
            field = "P-" + prompt[1];
            document.getElementById(field).value = config.prompts[index];
            index++;
        }
    }
    // last - run the report
    getRpt(config.reportName, config.pageName, config.groupName);
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

    if (reportFields && reportFields.length > 0) {
        var postVars = {};
        for (var i = 0; i < reportFields.length; i++) {
            var fieldName = reportFields[i].substr(2);
            postVars[fieldName] = document.getElementById(reportFields[i]).value;
        }
        postdata.postVars = postVars;
    }

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

    if (data.hasOwnProperty('csvfile')) {
        html += `
    <div class="row">
        <div class="col-sm-auto">
            <button type="button" class="btn btn-info btn-sm" onclick="downloadReport('xlsx'); return false;" disabled>Download Excel xlsx</button>
            <button type="button" class="btn btn-info btn-sm" onclick="downloadReport('csv'); return false;">Download CSV</button>
        </div>
    </div>
`;
    }
    reportContentDiv.innerHTML = html;

    // build tabulator specs
    params = {
        data: data.data,
        layout: "fitDataTable",
        pagination: true,
        paginationSize: 25,
        paginationSizeSelector: [10, 25, 50, 100, 250, true], //enable page size select element with these options
    };
    if (data.hasOwnProperty('groupby')) {
        params.groupBy = data['groupby'];
    }

    // set the calc position
    var calcPosition = 'bottom';
    if (data.hasOwnProperty('calcPosition'))
        calcPosition = data['calcPosition'];
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
            column.width = field.width;
        if (field.hasOwnProperty('minWidth'))
            column.minWidth = field.minWidth;
        if (field.hasOwnProperty('align')) {
            column.hozAlign = field.align;
            column.headerHozAlign = field.align;
        }
        if (field.hasOwnProperty('calc')) {
            column[calcPosition + 'Calc'] = field.calc;
            if (field.hasOwnProperty('precision')) {
                column[calcPosition + 'CalcParams'] = { precision: field.precision };
            }
        }
        if (field.hasOwnProperty('format')) {
            column.formatter = field.format;
        }
        if (field.hasOwnProperty('visible')) {
            if (field.visible == 'true' || field.visible === true)
                column.visible = true;
            else
                column.visible = false;
        }
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
        //console.log(column);
        columns.push(column);
    }
    params.columns = columns;

    if (data.hasOwnProperty('index')) {
        params.index = data.index;
    }

    // open table
    reportTable =  new Tabulator('#reportTable', params);

    if (data.success)
        show_message(data.success, 'success');

    if (data.csvfile)
        csvfile = data.csvfile;
}

function downloadReport(format) {
    var tabledata = JSON.stringify(reportTable.getData("active"));
    downloadFilePost(format, csvfile, tabledata);
}