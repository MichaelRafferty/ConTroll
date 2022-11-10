$(document).ready(function () {
    getData();
});

var badgetable = null;
var category = null;
var type = null;
var paid = null;
var label = null;
var age = null;
var typefilter = null;
var catfilter = null;
var agefilter = null;
var paidfitler = null;
var labelfilter = null;

function catclicked(e, cell) {
    value = cell.getRow().getCell("memCategory").getValue();
    if (cell.getElement().style.backgroundColor) {
        badgetable.removeFilter("category", "in", catfilter);        
        catfilter = catfilter.filter(arrayItem => arrayItem !== value);      
        if (catfilter.length > 0) {
            badgetable.addFilter("category", "in", catfilter);
        }
        cell.getElement().style.backgroundColor = "";
    } else {
        if (catfilter.length > 0) {
            badgetable.removeFilter("category", "in", catfilter);            
        } 
        catfilter.push(value);
        badgetable.addFilter("category", "in", catfilter);
        cell.getElement().style.backgroundColor = "#C0FFC0";
    }
}

function typeclicked(e, cell) {
    value = cell.getRow().getCell("memType").getValue();
    if (cell.getElement().style.backgroundColor) {
        badgetable.removeFilter("type", "in", typefilter);        
        typefilter = typefilter.filter(arrayItem => arrayItem !== value);        
        if (typefilter.length > 0) {
            badgetable.addFilter("type", "in", typefilter);
        }
        cell.getElement().style.backgroundColor = "";
    } else {
        if (typefilter.length > 0) {
            badgetable.removeFilter("type", "in", typefilter);
        }
        typefilter.push(value);
        badgetable.addFilter("type", "in", typefilter);
        cell.getElement().style.backgroundColor = "#C0FFC0";
    }
}

function ageclicked(e, cell) {
    value = cell.getRow().getCell("memAge").getValue();
    if (cell.getElement().style.backgroundColor) {
        badgetable.removeFilter("age", "in", agefilter);
        agefilter = agefilter.filter(arrayItem => arrayItem !== value);
        if (agefilter.length > 0) {
            badgetable.addFilter("age", "in", agefilter);
        }
        cell.getElement().style.backgroundColor = "";
    } else {
        if (agefilter.length > 0) {
            badgetable.removeFilter("age", "in", agefilter);
        }
        agefilter.push(value);
        badgetable.addFilter("age", "in", agefilter);
        cell.getElement().style.backgroundColor = "#C0FFC0";
    }
}

function paidclicked(e, cell) {
    value = cell.getRow().getCell("paid").getValue();
    if (cell.getElement().style.backgroundColor) {
        badgetable.removeFilter("paid", "in", paidfilter);
        paidfilter = paidfilter.filter(arrayItem => arrayItem !== value);
        if (paidfilter.length > 0) {
            badgetable.addFilter("paid", "in", paidfilter);
        }
        cell.getElement().style.backgroundColor = "";
    } else {
        if (paid.length > 0) {
            badgetable.removeFilter("paid", "in", paidfilter);
        }
        paidfilter.push(value);
        badgetable.addFilter("paid", "in", paidfilter);
        cell.getElement().style.backgroundColor = "#C0FFC0";
    }   
}

function labelclicked(e, cell) {
    value = cell.getRow().getCell("label").getValue();
    if (cell.getElement().style.backgroundColor) {
        badgetable.removeFilter("label", "in", labelfilter);
        labelfilter = labelfilter.filter(arrayItem => arrayItem !== value);
        if (labelfilter.length > 0) {
            badgetable.addFilter("label", "in", labelfilter);
        }
        cell.getElement().style.backgroundColor = "";
    } else {
        if (labelfilter.length > 0) {
            badgetable.removeFilter("label", "in", labelfilter);
        }
        labelfilter.push(value);
        badgetable.addFilter("label", "in", labelfilter);
        cell.getElement().style.backgroundColor = "#C0FFC0";
    }
}

function draw_stats(data) {
    if (category !== null) {
        category.off("cellClick");
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
}

function draw_badges(data) {
    if (badgetable !== null) {
        badgetable = null;
    }
    badgetable = new Tabulator('#badge-table', {
        data: data['badges'],
        layout: "fitDataTable",
        height: "600px",
        pagination: true,
        columns: [
            { title: "perid", field: "perid", visible: false },
            { title: "Person", field: "p_name", headerSort: true, headerFilter: true },
            { title: "Badge Name", field: "p_badge", headerSort: true, headerFilter: true },
            { title: "Membership Type", field: "label", headerSort: true, headerFilter: true, },
            { title: "Price", field: "price", hozAlign: "right", headerSort: true, headerFilter: true },
            { title: "Paid", field: "paid", hozAlign: "right", headerSort: true, headerFilter: true, },
            { title: "Created", field: "create_date", headerSort: true, headerFilter: true },
            { title: "Changed", field: "change_date", headerSort: true, headerFilter: true },
            { field: "category", visible: false },
            { field: "age", visible: false },
            { field: "type", visible: false },
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
            showError("ERROR in " + script + ": " + textStatus, jqXHR);
            return false;
        }
    })
}


function transferBadge(badge) {
    var newId = prompt('Please enter the Perid of the person you are transferring TO');

    var formData = { 'badge': badge, 'perid': newId };
    $.ajax({
        url: 'scripts/transferBadge.php',
        data: formData,
        method: 'POST',
        success: function (data, textStatus, jqXHR) {
            if (data.error != '') {
                $('#test').empty().append(JSON.stringify(data));
                alert(data.error);
            } else {
                location.reload();
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
            if (data.error != '') {
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
            if (data.error != '') {
                $('#test').empty().append(JSON.stringify(data));
                alert(data.error);
            } else {
                $('#test').empty().append(JSON.stringify(data));
            }
        }
    });
}
