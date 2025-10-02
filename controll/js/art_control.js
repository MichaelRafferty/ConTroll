var region = null;
var regionTab = null;

var itemTable = null;
var itemSaveBtn = null;
var itemUndoBtn = null;
var itemRedoBtn = null;

var itemTable_dirty = false;
var artItemModal = null;
var createPaneModal = null;
var historyPaneModal = null;
var artItemHistoryTitle = null;
var historyRow = null;
var historyDiv = null;

var artists = null;

var priceregexp = 'regex:^([0-9]+([.][0-9]*)?|[.][0-9]+)$';

var testdiv = null;

$(document).ready(function() {
    testdiv = document.getElementById('test');
    artItemModal = artItemModalOnLoad(itemTable);

    setRegion('overview', null);

    var createPaneId = document.getElementById('artItemCreatePane');
    if(createPaneId != null) {
        createPaneModal = new bootstrap.Modal(createPaneId, {focus: true, backdrop: 'static'});
    }
    var historyPaneId = document.getElementById('artItemHistoryPane');
    if(historyPaneId != null) {
        historyPaneModal = new bootstrap.Modal(historyPaneId, {focus: true, backdrop: 'static'});
        artItemHistoryTitle = document.getElementById('artItemHistoryTitle');
        historyDiv = document.getElementById('artItemHistory-div');
    }
});

function setRegion(name, id) {
    region = id;

    if(regionTab!=null) {
        regionTab.classList.remove('active');
        regionTab.setAttribute('aria-selected', 'false');
    }

    regionElem = document.getElementById(name + '-tab');
    regionElem.classList.add('active')
    regionElem.setAttribute('aria-selected', 'true');
    regionTab=regionElem;

    if(region != null) {
        getData();
        document.getElementById('item-addnew').disabled=false;
    }
    else { 
        document.getElementById('artItems_table').innerHTML="<p>This is an Overview tab, please select one of the regions above to see the items in that region</p>";
        document.getElementById('item-addnew').disabled=true;
    }
}

function getData() {
    var script = "scripts/artcontrol_getArtItems.php";
    $.ajax({
        method: "GET",
        url: script,
        data: 'region=' + region,
        success: function (data, textStatus, jqXHR) {
            if('error' in data) {
                showError("ERROR in getArt: " + data.error);
            }
            artists=data.artists;
            var artistList = document.getElementById('artItemCreateExhibitor')

            for(artist in artists) {
                var opt = document.createElement('option')
                opt.value = artist;
                opt.innerHTML=artists[artist].exhibitorName+' ('+artists[artist].exhibitorNumber+')';
                artistList.appendChild(opt);
            }
            draw(data);
        },
        error: function (jqXHR, textStatus, errorThrown) {
            showError("ERROR in getArt: " + textStatus, jqXHR);
            return false;
        }
    });
}

function findDuplicates(data) {
    var extendedKey = {};
    var errorString = "";
    for (const index in data) {
        var item = data[index];
        var key = item.item_key;
        var exhNum = item.exhibitorNumber;
        var extKey = exhNum + '_' + key;
        if(extendedKey[extKey]) {
            extendedKey[extKey]++;
            errorString += exhNum + " has " + extendedKey[extKey] + " items with item # " + key;

        } else {
            extendedKey[extKey] = 1;
        }
    }
    return errorString;
}

function draw(data, textStatus, jqXHR) {
    //set buttons
    itemSaveBtn = document.getElementById("item-save");
    itemUndoBtn = document.getElementById("item-undo");
    itemRedoBtn = document.getElementById("item-redo");

    if(itemTable != null) {
        itemTable.off("dataChanged");
        itemTable.off("cellEdited");
        itemTable.destroy();
    }

    itemTable_dirty = false;
    itemUndoBtn.disabled = true;
    itemRedoBtn.disabled = true;
    itemSaveBtn.innerHTML = "Save Changes"
    itemSaveBtn.disabled = true;

    document.getElementById('artControlPaginationDiv').innerHTML = '';
    document.getElementById('artControlPaginationDiv').hidden = data.art.length <= 50;
    itemTable = new Tabulator('#artItems_table', {
        mxHeight: "800px",
        history: true,
        data: data.art,
        layout: 'fitDataTable',
        pagination: data.art.length > 50,
        paginationElement: document.getElementById('artControlPaginationDiv'),
        paginationSize: 50,
        paginationSizeSelector: [10, 25, 50, 100, true], // enable page size select with these options
        columns: [
            {title: 'Actions', headerFilter: false, headerSort: false, formatter: addEditButton, responsive:0},
            {title: 'id', field: 'id', visible: false},
            {title: 'exhibitorYearId', field: 'exhibitorYearId', visible: false},
            {title: 'locations', field: 'locations', visible: false},
            {title: 'Name', field: 'exhibitorName', headerSort: true, headerFilter: 'list', headerFilterParams: { values: data.artists.map(function(a) { return a.exhibitorName;})}, },
            {title: 'Artist #', field: 'exhibitorNumber', headerWordWrap: true, headerSort: true, width: 60,
                headerFilter: 'list', headerFilterParams: { values: data.artists.map(function(a) { return a.exhibitorNumber;}).sort()},
                hozAlign: "right",
            },
            {title: 'Item #', field: 'item_key', headerSort: true, headerFilter: true, headerWordWrap: true, width: 60, hozAlign: "right",},
            {title: 'Type', field: 'type', headerSort: true, headerFilter: 'list', headerFilterParams: { values: ['art', 'print', 'nfs']}, width: 75, },
            {title: 'Title', field: 'title', headerSort: true, headerFilter: true,},
            {title: 'Material', field: 'material', headerSort: true, headerFilter: true,},
            {title: 'Min Bid or Ins.', field: 'min_price', headerSort: true, headerFilter: true, headerFilterFunc:numberHeaderFilter,
                headerWordWrap: true, width: 100, formatter: "money", hozAlign: "right", },
            {title: 'Q. Sale or Print', field: 'sale_price', headerSort: true, headerFilter: true, headerFilterFunc:numberHeaderFilter,
                headerWordWrap: true, width: 100, formatter: "money", hozAlign: "right", },
            {title: 'Orig Qty', field: 'original_qty', headerSort: true, headerFilter: true, headerFilterFunc:numberHeaderFilter,
                headerWordWrap: true, width: 70, hozAlign: "right", },
            {title: 'Current Qty', field: 'quantity', headerSort: true, headerFilter: true, headerFilterFunc:numberHeaderFilter,
                headerWordWrap: true, width: 70, hozAlign: "right", },
            {title: 'Status', field: 'status', headerSort: true, headerFilter:'list', headerFilterParams: { values: statusList.getStatuses() } },
            {title: 'Location', field: 'location', headerSort: true, headerFilter: true, },
            {title: 'BidderNum', field: 'bidder', visible: false, },
            {title: 'Bidder', field: 'bidderText', headerSort: true, headerFilter:true, },
            {title: 'Final Price', field: 'final_price', headerSort: true, headerFilter: true, headerFilterFunc:numberHeaderFilter,
                headerWordWrap: true, width: 100, formatter: "money", hozAlign: "right", },
            {title: 'Notes', field: 'notes', formatter: "textarea", }
        ]
    });

    itemTable.on("dataChanged", itemTable_dataChanged);
    itemTable.on("cellEdited", cellChanged)

    itemTable_dirty = false;
    document.getElementById('artControl-csv-div').hidden = false;

    artItemModal.setItemTable(itemTable);
}

function addEditButton(cell, formatterParams, onRendered) {
    var html = '';
    var index = cell.getRow().getIndex();
    var row = cell.getRow().getData();
    var btnClass = 'btn btn-sm p-0 ms-1 me-1';
    var btnStyle = 'style="--bs-btn-font-size: 75%;"';

    html += '<button type="button" class="'+btnClass+' btn-primary" '+btnStyle+' ' +
        'onclick="artItemModal.fetchArtItem(' + index + ',editReturn)">Edit item</button>';

    if (row.historyCount > 0) {
        html += '<button type="button" class="'+btnClass+' btn-secondary" '+btnStyle+' ' +
            'onclick="fetchArtItemHistory(' + index + ')">History</button>';
    }

    return html;
}

function editReturn(editTable, editfield, editIndex, editvalue) {
    //itemTable

}

function itemTable_dataChanged(data) {
    if(!itemTable_dirty) {
        itemSaveBtn.innerHTML = "Save Changes*";
        itemSaveBtn.disabled = false;
        itemTable_dirty = true;
    }

    checkItemUndoRedo();
}

function cellChanged(cell) {
    dirty = true;
    cell.getElement().style.backgroundColor = "#fff3cd";
}

function undoItem () {
    if(itemTable != null) {
        itemTable.undo();

        if(checkItemUndoRedo() <= 0) {
            itemTable_dirty = false;
            itemSaveBtn.innerHTML = "Save Changes";
            itemSaveBtn.disabled = true;
        }
    }
}
function redoItem () {
    if(itemTable != null) {
        itemTable.redo();

        if(checkItemUndoRedo() > 0) {
            itemTable_dirty = true;
            itemSaveBtn.innerHTML = "Save Changes";
            itemSaveBtn.disabled = false;
        }
    }
}

function checkItemUndoRedo() {
    var undosize = itemTable.getHistoryUndoSize();
    itemUndoBtn.disabled = undosize <= 0;
    itemRedoBtn.disabled = itemTable.getHistoryRedoSize() <= 0;
    return undosize;
}

function addnewItem() {
    document.getElementById('artItemCreateNumber').value=0;
    createPaneModal.show();
}

function createNewItem() {
    var artist = document.getElementById('artItemCreateExhibitor').value;
    var itemNumber = document.getElementById('artItemCreateNumber').value;
    var type = document.getElementById('artItemCreateType').value;
    artItemModal.setValuesForNew(artists[artist], itemNumber, type);
    artItemModal.resetEditPane();
    artItemModal.openEditPane();
}

function saveItem() {
    if(itemTable != null) {
        var invalids = itemTable.validate();
        if (!invalids === true) {
            console.log(invalids);
            alert("Item table does not pass validation, please check for empty cells or cells in red");
            return false;
        }

        var duplicates = findDuplicates(itemTable.getData());
        if(duplicates != "") {
            alert(duplicates);
            return false;
        }
    }

    itemSaveBtn.innerHTML = "Saving...";
    itemSaveBtn.disabled = true;

    script = "scripts/artcontrol_updateArtItems.php"
    var postdata = {
        tabledata: JSON.stringify(itemTable.getData()),
        indexcol: "id",
        region: region
    }

    $.ajax({
        url: script,
        method: 'POST',
        data: postdata,
        success: function (data, textStatus, jhXHR) {
            if (data.error != undefined) {
                showError(data.error);
                itemSaveBtn.innerHTML = "Save Changes";
                itemSaveBtn.disabled = false;
            } else {
                //console.log(data);
                show_message(data.message, 'success');
                draw(data);
            }
        }
    });
}

// download buttons, save off the data file
function download(format) {
    if (itemTable == null)
        return;

    var filename = 'artitems';
    var tabledata = JSON.stringify(itemTable.getData("active"));
    var excludeList = [];
    downloadFilePost(format, filename, tabledata, excludeList);
}

// print control sheets
function pdfSheets(type, email) {
    var regionYearId = '';
    var itemData = itemTable.getData("active");
    var ids = [];

    if (itemData.length == 0) {
        show_message("No Art Items in filtered table.", 'error');
        return;
    }

    var regionYearId = itemData[0].exhibitsRegionYearId;
    for (var i = 0; i < itemData.length; i++) {
        if (!ids.includes(itemData[i].exhibitorYearId)) {
            ids.push(itemData[i].exhibitorYearId);
        }
    }
    var eyid = ids.join(',');

    var script = "scripts/exhibitorsBidSheets.php?type=" + type + "&region=" + regionYearId + "&eyid=" + eyid + "&email=" + email;
    window.open(script, "_blank")
}

/// History Start
// display history: use the modal to show the history for this art item id
function fetchArtItemHistory(index) {
    historyRow = itemTable.getRow(index).getData();
    $.ajax({
        method: "POST",
        url: "scripts/artcontrol_getArtItemHistory.php",
        data: { itemId: historyRow.id, action: 'fetchHistory', artistNumber: historyRow.exhibitorNumber },
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
            displayArtItemHistory(data);
            if (data['success'] !== undefined)
                show_message(data.success, 'success');
        },
        error: function (jqXHR, textStatus, errorThrown) {
            showError("ERROR in getReceipt: " + textStatus, jqXHR);
        }
    });
}

// historyId, id, item_key, title, type, status, location, quantity, original_qty, min_price, sale_price, final_price,
//              bidder, conid, artshow, time_updated, updatedBy, material, exhibitorRegionYearId, notes, historyDate
function displayArtItemHistory(data) {
    var  title = "Art Item Change History for " + historyRow.exhibitorNumber + '-' + historyRow.item_key;
    title += "<br/>Name:  " + historyRow.exhibitorName + ' - ' + historyRow.type + ": " + historyRow.title;
    artItemHistoryTitle.innerHTML = title;
    // build the history display
    var html = '<div class="row"><div class="col-sm-12"><h1 class="h3">' + title + '</h1></div></div>';
    // format the heading line
    html += `<div class='row'>
        <div class='col-sm-2'>Change Date</div>
        <div class='col-sm-4'>Title</div>
        <div class='col-sm-8'>Material</div>
        <div class='col-sm-2'>Status</div>
    </div>
    <div class='row'>
        <div class='col-sm-1'></div>
        <div class='col-sm-1'>Quantity</div>
        <div class='col-sm-1'>Location</div>
        <div class='col-sm-1'>Minimim</div>
        <div class='col-sm-1'>Sale</div>
        <div class='col-sm-1'>Final</div>
        <div class='col-sm-1'>Bidder</div>
        <div class='col-sm-1'>Upd By</div>
    </div>
    <div class='row'>
        <div class='col-sm-1'></div>
        <div class='col-sm-11'>Notes</div>
    </div>\n`;
    // format the current line
    var current = data['history'][0];
    var color = '';
    var prior = data['history'][0];
    for (var i = 0; i < data['history'].length; i++) {
        var current = data['history'][i];
        html += "<div class='row mt-2'>\n";

        // change date
        html += "<div class='col-sm-2'>" + current.historyDate + "</div>\n";
        // title
        color = prior.title != current.title ? ' style="background-color: #ffcdcd;"' : '';
        html += "<div class='col-sm-4'" + color + ">" + current.title + "</div>\n";
        // material
        color = prior.material != current.material ? ' style="background-color: #ffcdcd;"' : '';
        html += "<div class='col-sm-4'" + color + ">" + current.material + "</div>\n";
        // status
        color = prior.status != current.status ? ' style="background-color: #ffcdcd;"' : '';
        html += "<div class='col-sm-2'" + color + ">" + current.status + "</div>\n</div>\n";
        // quantity
        color = (prior.quantity != current.quantity || prior.original_qty != current.original_qty) ? ' style="background-color: #ffcdcd;"' : '';
        html += "<div class='row'>\n<div class='col-sm-1'>" +
            "</div><div class='col-sm-1'" + color + ">" + current.quantity + ' of ' + current.original_qty + "</div>\n";
        // location
        color = prior.location != current.location ? ' style="background-color: #ffcdcd;"' : '';
        html += "<div class='col-sm-1'" + color + ">" + current.location + "</div>\n";
        // minimum
        color = prior.min_price != current.min_price ? ' style="background-color: #ffcdcd;"' : '';
        html += "<div class='col-sm-1'" + color + ">" + current.min_price + "</div>\n";
        // sale
        color = prior.sale_price != current.sale_price ? ' style="background-color: #ffcdcd;"' : '';
        html += "<div class='col-sm-1'" + color + ">" + current.sale_price + "</div>\n";
        // final
        color = prior.final_price != current.final_price ? ' style="background-color: #ffcdcd;"' : '';
        html += "<div class='col-sm-1'" + color + ">" + current.final_price + "</div>\n";
        // bidder
        color = prior.bidder != current.bidder ? ' style="background-color: #ffcdcd;"' : '';
        html += "<div class='col-sm-1'" + color + ">" + current.bidder + "</div>\n";
        // updatedBy
        color = prior.updatedBy != current.updatedBy ? ' style="background-color: #ffcdcd;"' : '';
        html += "<div class='col-sm-1'" + color + ">" + current.updatedBy + "</div>\n</div>\n";
        // notes
        color = prior.notes != current.notes ? ' style="background-color: #ffcdcd;"' : '';
        html += "<div class='row'>\n<div class='col-sm-1'></div>\n<div class='col-sm-11'" + color + ">" + current.notes + "</div>\n";
        html += "</div>\n";
        prior = current;
    }


    historyDiv.innerHTML = html;
    historyPaneModal.show();
}

