//tabs
var find_tab = null;

//buttons
var startover_button;

//find item fields
var artist_field = null;
var item_field = null;
var find_result_table = null;
var id_div = null;
var cart_div = null;

//tables
var datatbl = new Array();
var locations = new Array();
var cart_items = new Array();
var cart = new Array();
var actionlist = new Array();

//counters
var need_location = 0;
var need_count = 0;

//mode
manager=true;

window.onload = function init_page() {
    //tabs
    find_tab = document.getElementById('find_tab');

    //find people
    id_div = document.getElementById("find_results");
    artist_field = document.getElementById('artist_num_lookup');
    item_field = document.getElementById('item_num_lookup');
    cart_div = document.getElementById('cart');

    //buttons
    startover_button = document.getElementById("startover_btn");

    start_over();
    }

function start_over() {
    init_table();
    init_locations();
    init_cart();

    actionlist=new Array();

    //disable tabs...

    //set tab to find_tab
    bootstrap.Tab.getOrCreateInstance(find_tab).show();
}

function init_cart() {
    need_location = 0;
    need_count = 0;
    cart = new Array();
    cart_items = new Array();
    draw_cart();
}

function init_table() {
    if (find_result_table != null) {
        find_result_table.destroy();
        find_result_table = null;
    }
    id_div.innerHTML = "";
    datatbl = new Array();
}

function init_locations() {
    var script = 'onServer/getLocations.php';
    $.ajax({
            method: "GET",
            url: script,
            success: function(data, textStatus, jqXhr) {
                locations = data;
                //$('#test').empty().append(JSON.stringify(data, null, 2));
                }
            });
}

function addInventoryIcon(cell, formatterParams, onRendered) {
    var html = '';
    var item_status = cell.getRow().getData().status;

    switch(item_status) {
        case 'Checked Out':
        case 'purchased/released':
            html += '<button type="button" class="btn btn-sm btn-danger pt-0 pb-0" onclick="add_to_cart(' + cell.getRow().getData().index + ',\'alert\')">N/A</button>';
            // no inventory action, gone
            break;
        case 'Sold Bid Sheet':
        case 'To Auction':
            // sales can sell
            if(mode == 'sales') {
                html += '<button type="button" class="btn btn-sm btn-primary pt-0 pb-0" onclick="add_to_cart(' + cell.getRow().getData().index + ',\'sell\')">Sell</button>';
            }
            break;
        case 'Quicksale/Sold':
            //sales or manager can release
            if(manager || (mode=='sales')) {
                html += '<button type="button" class="btn btn-sm btn-secondary pt-0 pb-0" onclick="add_to_cart(' + cell.getRow().getData().index + ',\'release\')">Release</button>';
            }
            break;
        case 'BID':
        case 'Checked In':
            //sales can sell and inventory can confirm or check out
            if(mode == 'sales') {
                html += '<button type="button" class="btn btn-sm btn-primary pt-0 pb-0" onclick="add_to_cart(' + cell.getRow().getData().index + ',\'sell\')">Sell</button>';
            }
        case 'NFS':
            // inventory or check out
            if(mode == 'inventory') {
                html += '<button type="button" class="btn btn-sm btn-primary pt-0 pb-0" onclick="add_to_cart(' + cell.getRow().getData().index + ',\'inventory\')">Inv</button>';
                html += '<button type="button" class="btn btn-sm btn-secondary pt-0 pb-0" onclick="add_to_cart(' + cell.getRow().getData().index + ',\'check out\')">Out</button>';
            }
            // manager can remove from show
            if(manager) {
                html += '<button type="button" class="btn btn-sm btn-warning pt-0 pb-0" onclick="add_to_cart(' + cell.getRow().getData().index + ',\'remove\')">Remove</button>';
            }
            break;
        case 'Removed from Show':
        case 'Not In Show':
        default:
            // must check in
            html += '<button type="button" class="btn btn-sm btn-primary pt-0 pb-0" onclick="add_to_cart(' + cell.getRow().getData().index + ',\'check in\')">In</button>';
    }
    if(html == '') { 
        html += '<button type="button" class="btn btn-sm btn-danger pt-0 pb-0" onclick="add_to_cart(' + cell.getRow().getData().index + ',\'alert\')">N/A</button>';
    }

    return html;
}

function build_record_hover(e, cell, onRendered) {
    data = cell.getData();
    hover_text = data['id'] + '<br/>' + data['name'].trim() + '<br/>' 
    hover_text += data['title'].trim() + '<br/>';
    hover_text += data['status'].trim() + ' @ ' + data['location'] + '<br/>';
    hover_text += 'updated: ' + data['time_updated'] + '<br/>';

    return hover_text
}

function build_table(tableData) {
    init_table();

    var html = ''

    if(tableData.length > 0) {
        for (var trow in tableData) {
            var row = tableData[trow];
            row.index = trow;
            datatbl.push(row);
        }
        find_result_table = new Tabulator('#find_results', {
            maxHeight: "600px",
            data: datatbl,
            layout: "fitColumns",
            columns: [
                { title: 'Key', field: 'id', hozAlign: "right", maxWidth: 50, headerWordWrap: true, headerFilter: true, tooltip: build_record_hover, },
                { title: 'Artist', field: 'name', headerWordWrap: true, headerFilter: true, tooltip: true},
                { title: 'Item', field: 'title', headerWordWrap: true, headerFilter: true, tooltip: true},
                { title: 'Status', field: 'status', headerWordWrap: true, headerFilter: true, tooltip: true},
                { title: 'Loc.', field: 'location', width: 50, headerWordWrap: true, headerFilter: true, tooltip: true},
                {field: 'index', visible: false,},
                { title: 'Qty.', field: 'qty', width: 50, headerSort: false, tooltip: true},
                { title: 'Cart', width: 101, hozAlign: "center", headerFilter: false, headerSort: false, formatter: addInventoryIcon, },
            ],
        });
    } else { 
        id_div.innerHTML = 'No matching items found';
    }
    return;

}

function find_item(action) {
    var artist = artist_field.value;
    var item = item_field.value;  

    var script = 'onServer/getItem.php';

    var itemList = $('#userDiv').data('items');
    if(itemList == undefined) {
        itemList = [];
    }
    if(itemList[artist+'_'+item] != undefined) {
        alert("Item already in list");
    } else {
        $.ajax({
            data: "artist="+artist+"&item="+item,
            method: "GET",
            url: script,
            success: function(data, textStatus, jqXhr) {
                if(data['noitem']!=undefined) {
                    alert("No matching Item Found");
                } else {
                    build_table(data['items']);
                    //$('#test').empty().append(JSON.stringify(data, null, 2));
                }
            }
        });
    }

}

function remove_from_cart(index) {
    cart.splice(index, 1);
    cart_perid.splice(index, 1);
    draw_cart();
}


function add_to_cart(index, action) {
    var item = datatbl[index];
    $('#test').empty().append(action + '\n' + JSON.stringify(item, null, 2));

    if (cart_items.includes(item['id']) == false) {
        cart_items.push(item['id']);
        cart.push(item);
    } else {
        alert('item is already in the cart');
        return;
    }

    switch(action) {
    case 'check in':
        //ready for checkin?
        //does item have location?
        if(item['location'] == "") {
            item['need_location'] = true;
            need_location++;
        }
        //have we confirmed count?
        if(item['type'] == 'print') {
            item['need_count'] = true;
            need_count++;
        }
        break;
    default:
        alert('not implemented');
    }
        
    draw_cart();
}

function draw_cart_row(rownum) {
    var item = cart[rownum];
    var html = `
<div class="row">
    <div class="col-sm-8">
`;
    var action_html = `
    </div>
    <div class="col-sm-4">
`;
    var trailing_html = '</div></div>';

    var location_select = '<select '
    if(item['need_location']) { location_select += 'class="bg-warning" '; }
    location_select += 'id="loc_' + item['id'] + '">';
    if(item['location'] == "") {
        location_select += '<option></option>';
    }
    for(loc in locations[item['art_key']]) {
        if((item['location'] != "") && (locations[item['art_key']][loc] == item['location'])) {
            location_select += '<option selected=selected>' + locations[item['art_key']][loc] + '</option>';
        } else { 
            location_select += '<option>' + locations[item['art_key']][loc] + '</option>';
        }
    }
    location_select += '</select>';
    switch(item['type']) {
        case 'nfs':
            if(mode == 'sales') {
                alert("Cannot Sell NFS");
            } else {
                html += item['id'] + '<br/>' 
                    + item['name'] + ': ' + item['title'] + '<br/>'
                    + 'Location: ' + location_select + '<br/>'
                    + 'NFS @ ' + item['status'] + '<br/>';
                action_html += '<br/><br/>';
                if(item['need_location']) {
                    action_html += `<button class="btn btn-primary btn-small p-0" type="button" id="` + item['id'] + `"_update_loc" onclick="update_loc(`+rownum+`);">Update Loc</button>`;
                } else {
                    action_html += `<button class="btn btn-info btn-small p-0" type="button" id="` + item['id'] + `"_update_loc" onclick="update_loc(`+rownum+`);">Update Loc</button>`;
                }
                action_html += '<br/>';
            }
            break;
        case 'art':
            html += item['id'] + '<br/>' 
                + item['name'] + ': ' + item['title'] + '<br/>'
                + 'Location: ' + location_select + '<br/>'
                + 'Art @ ' + item['status'] + '<br/>';
            action_html += '<br/><br/>';
            if(item['need_location']) {
                action_html += `<button class="btn btn-primary btn-small p-0" type="button" id="` + item['id'] + `"_update_loc" onclick="update_loc(`+rownum+`);">Update Loc</button>`;
            } else {
                action_html += `<button class="btn btn-info btn-small p-0" type="button" id="` + item['id'] + `"_update_loc" onclick="update_loc(`+rownum+`);">Update Loc</button>`;
            }
            action_html += '<br/>';
            break;
        case 'print':
            html += item['id'] + '<br/>' 
                + item['name'] + ': ' + item['title'] + '<br/>'
                + 'Location: ' + location_select + '<br/>';
            if(item['need_count']) {
                html += '<span class="bg-warning">' + item['quantity'] + '</span>'
                + ' @ ' + item['status'] + '<br/>';
            } else {
                html += item['quantity'] + ' @ ' + item['status'] + '<br/>';
            }
            action_html += '<br/><br/>';
            if(item['need_location']) {
                action_html += `<button class="btn btn-primary btn-small p-0" type="button" id="` + item['id'] + `"_update_loc" onclick="update_loc(`+rownum+`);">Update Loc</button>`;
            } else {
                action_html += `<button class="btn btn-info btn-small p-0" type="button" id="` + item['id'] + `"_update_loc" onclick="update_loc(`+rownum+`);">Update Loc</button>`;
            }
            action_html += '<br/>';
            if(item['need_count']) {
                action_html += `<button class="btn btn-primary btn-small p-0" type="button" id="` + item['id'] + `"_confirm_count" onclick="confirm_count(`+rownum+`);">Confirm Qty</button>`;
            }
            action_html += '<br/>';
            break;
        default:
            alert('Unknown Type');
    }

    return html + action_html + trailing_html;
}

function update_loc(row, loc) {
    var item = cart[row]['id'];
    if(loc == undefined) { loc = document.getElementById('loc_' + item).value; }
    console.log("Shift " + item + " to " + loc);
    //check if valid
    if(!locations[cart[row]['art_key']].includes(loc)) {
        alert("Invalid location");
    } else {
        actionlist.push(create_action('Set Location', item, loc));

        cart[row]['location']=loc;

        if(cart[row]['need_location']) {
            cart[row]['need_location']=false;
            need_location--;
        }
    }

    draw_cart();
}


function confirm_count(row) {
    cart[row]['need_count'] = false;
    need_count--;
    draw_cart();
}

function draw_cart() {
    num_rows = 0;
    var html = `
<div class="container-fluid">
<div class="row">
    <div class="col-sm-8 text-bg-primary">Items</div>
    <div class="col-sm-4 text-bg-primary">Actions</div>
</div>
`;

    for (rownum in cart) {
        num_rows++;
        html += draw_cart_row(rownum);
    }

    html += '</div>' //end container-fluid
    cart_div.innerHTML=html;

    //clear buttons
    startover_button.hidden = num_rows == 0;
}

function create_action(action, item, value) {
    return {
        action: action,
        item: item,
        value: value
    };
}
