var region = null;
var regionTab = null;

var itemTable = null;
var itemSaveBtn = null;
var itemUndoBtn = null;
var itemRedoBtn = null;

var itemTable_dirty = false;
var artItemModal = null;
var createPaneModal = null;

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
    var script = "scripts/getArtItems.php";
    $.ajax({
        method: "GET",
        url: script,
        data: 'region=' + region,
        success: function (data, textStatus, jqXHR) {
            if('error' in data) {
                showError("ERROR in getArt: " + data['error']);
            }
            artists=data.artists;
            var artistList = document.getElementById('artItemCreateExhibitor')

            for(artist in artists) {
                var opt = document.createElement('option')
                opt.value = artist;
                opt.innerHTML=artists[artist].exhibitorName+' ('+artists[artist].exhibitorNumber+')';
                artistList.appendChild(opt);
            }
            draw(data, textStatus, jqXHR);
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
        var key = item['item_key'];
        var exhNum = item['exhibitorNumber'];
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


    itemTable = new Tabulator('#artItems_table', {
        mxHeight: "800px",
        history: true,
        data: data['art'],
        layout: 'fitDataTable',
        pagination: true,
        paginationSize: 50,
        paginationSizeSelector: [10, 25, 50, 100, true], // enable page size select with these options
        columns: [
            {title: 'Actions', hozAlign: "center", headerFilter: false, headerSort: false, formatter: addEditButton, responsive:0},
            {title: 'id', field: 'id', visible: false},
            {title: 'locations', field: 'locations', visible: false},
            {title: 'Name', field: 'exhibitorName', headerSort: true, headerFilter: 'list', headerFilterParams: { values: data['artists'].map(function(a) { return a.exhibitorName;})}, },
            {title: 'Artist #', field: 'exhibitorNumber', headerWordWrap: true, headerSort: true, width: 60,
                headerFilter: 'list', headerFilterParams: { values: data['artists'].map(function(a) { return a.exhibitorNumber;}).sort()},
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

    artItemModal.setItemTable(itemTable);
}

function addEditButton(cell, formatterParams, onRendered) {
    var html = '';
    var index = cell.getRow().getIndex();
    var item_status = cell.getRow().getData().status;
    var btnClass = 'btn btn-sm p-0';
    var btnStyle = 'style="--bs-btn-font-size: 75%;"';

    html += '<button type="button" class="'+btnClass+' btn-primary" '+btnStyle+' onclick="artItemModal.fetchArtItem(' + index + ',editReturn)">Edit item</button>'

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

    script = "scripts/updateArtItems.php"
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
            if (data['error'] != undefined) {
                showError(data['error']);
                itemSaveBtn.innerHTML = "Save Changes";
                itemSaveBtn.disabled = false;
            } else {
                //console.log(data);
                show_message(data['message'], 'success');
                getData();
            }
        }
    });
}
