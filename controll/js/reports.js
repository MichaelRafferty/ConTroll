// globals for the regadmin tabs

// debug meaning
//  1 = console.logs
//  2 = show hidden table fields
//  4 = show hidden div
debug = 0;

var reportContentDiv = null;


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

    if (data.success) {
        show_message(data.success, 'success');
    }
}