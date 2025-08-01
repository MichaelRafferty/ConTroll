//tabs
var find_tab = null;

//buttons
var startover_button;
var inventory_update_button;
var location_change_button;

//find item fields
var artist_field = null;
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
var locations_changed = 0;

window.onload = function init_page() {
    //tabs
    find_tab = document.getElementById('find_tab');

    //find people
    id_div = document.getElementById("find_results");
    artist_field = document.getElementById('artist_num_lookup');
    cart_div = document.getElementById('cart');

    //buttons
    startover_button = document.getElementById("startover_btn");
    inventory_update_button = document.getElementById("inventory_btn");
    location_change_button = document.getElementById("location_change_btn");

    start_over();
    }

function start_over() {
    actionlist=new Array();

    init_table();
    init_locations();
    init_cart();

    // clear the error message block
    clear_message();

    //disable tabs...

    //set tab to find_tab
    if (find_tab)
        bootstrap.Tab.getOrCreateInstance(find_tab).show();
}

function init_cart() {
    need_location = 0;
    need_count = 0;
    locations_changed = 0;
    cart = new Array();
    cart_items = new Array();
    draw_cart();
}

function init_table() {
    if (find_result_table != null) {
        find_result_table.destroy();
        find_result_table = null;
    }
    if (id_div != null) {
        id_div.innerHTML = "";
    }
    datatbl = new Array();
}

function inventory() {
    var script = 'scripts/artInventory_inventory.php';
    $.ajax({
            method: "POST",
            url: script,
            data: "actions=" + JSON.stringify(actionlist),
            success: function(data, textStatus, jqXhr) {
                $('#test').empty().append(JSON.stringify(data['log'], null, 2));
                
                start_over();
                find_item('refresh');
            },
            error: showAjaxError,
        });
}

function init_locations() {
    var script = 'scripts/artInventory_getLocations.php';
    var data = "region=" + region
    $.ajax({
            method: "GET",
            url: script,
            data: data,
            success: function(data, textStatus, jqXhr) {
                locations = data['locations'];
                $('#test').empty();
            },
            error: showAjaxError,
        });
}

function addInventoryIcon(cell, formatterParams, onRendered) {
    var html = '';
    var item_status = cell.getRow().getData().status;
    var btnClass = 'btn btn-sm p-0';
    var btnStyle = 'style="--bs-btn-font-size: 75%;"';


    switch(item_status) {
        case 'Not In Show':
        case 'Checked Out':
        case 'Purchased/Released':
            html += '<button type="button" class="btn btn-sm btn-danger pt-0 pb-0"' + btnStyle + '">N/A</button>';
            // no inventory action, gone
            break;
        case 'Sold Bid Sheet':
        case 'Sold At Auction':
            //no inventory action
            break;
        case 'Quicksale/Sold':
            //inventory
            if(mode == 'artinventory') {
                html += '<button type="button" class="'+btnClass+' btn-primary" '+btnStyle+' onclick="add_to_cart(' + cell.getRow().getData().index + ',\'Inventory\')">Inv</button> ';
            }
            //manager can release
            if(manager) {
                html += '<button type="button" class="'+btnClass+' btn-secondary" '+btnStyle+' onclick="add_to_cart(' + cell.getRow().getData().index + ',\'Release\')">Release</button> ';
            }
            break;
        case 'To Auction':
        case 'BID':
            //inventory only
            if(mode == 'artinventory') {
                html += '<button type="button" class="'+btnClass+' btn-primary" '+btnStyle+' onclick="add_to_cart(' + cell.getRow().getData().index + ',\'Inventory\')">Inv</button> ';
            }
            break;
        case 'Checked In':
        case 'NFS':
            // inventory or check out
            if(mode == 'artinventory') {
                html += '<button type="button" class="'+btnClass+' btn-primary" '+btnStyle+' onclick="add_to_cart(' + cell.getRow().getData().index + ',\'Inventory\')">Inv</button> ';
                html += '<button type="button" class="'+btnClass+' btn-secondary" '+btnStyle+' onclick="add_to_cart(' + cell.getRow().getData().index + ',\'Check Out\')">Out</button> ';
            }
            // manager can remove from show
            if(manager) {
                html += '<button type="button" class="'+btnClass+' btn-warning" '+btnStyle+' onclick="add_to_cart(' + cell.getRow().getData().index + ',\'remove\')">Remove</button> ';
            }
            break;
        case 'Removed from Show':
        case 'Entered':
        default:
            // must check in
            html += '<button type="button" class="'+btnClass+' btn-primary" '+btnStyle+' onclick="add_to_cart(' + cell.getRow().getData().index + ',\'Check In\')">In</button> ';
    }
    return html;
}

function build_record_hover(e, cell, onRendered) {
    data = cell.getData();
    hover_text = data['id'] + '<br/>' + data['name'].trim() + '<br/>' 
    hover_text += data['title'].trim() + '<br/>';
    hover_text += data['status'].trim() + ' @ ' + data['location'] + '<br/>';
    if((data['status'] == 'BID') || (data['status'] == 'To Auction')) { 
        hover_text += 'by ' + data['bidder'] + ' @ $' + data['final_price'] + '<br/>';
    }
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
            row.prev_loc = row['location'];
            row.prev_bid = row['final_price'];
            row.prev_bidder = row['bidder'];
            row.prev_status = row['status'];
            datatbl.push(row);
        }
        find_result_table = new Tabulator('#find_results', {
            maxHeight: "700px",
            data: datatbl,
            layout: "fitColumns",
            responsiveLayout:true,
            pagination: datatbl.length > 25,
            paginationSize: 10,
            paginationSizeSelector: [10, 25, 50, 100, 250, true], //enable page size select element with these options
            columns: [
                { title: 'Key', field: 'id', hozAlign: "right", width:75, headerWordWrap: true, headerFilter: true, tooltip: build_record_hover, responsive: 0},
                { title: 'Artist', field: 'name', headerWordWrap: true, headerFilter: true, tooltip: true },
                { title: 'Item', field: 'title', headerWordWrap: true, headerFilter: true, tooltip: true},
                { title: 'Status', field: 'status', headerWordWrap: true, headerFilter: true, tooltip: true},
                { title: 'Updated', field: 'time_updated', headerWordWrap: true, headerFilter: true, tooltip: true, responsive: 2, width: 120, },
                { title: 'Loc.', field: 'location', width: 80, headerWordWrap: true, headerFilter: true, tooltip: true},
                {field: 'index', visible: false,},
                { title: 'Qty.', field: 'qty', width: 100, headerSort: false, tooltip: true},
                { title: 'Actions', width: 150, hozAlign: "center", headerFilter: false, headerSort: false, formatter: addInventoryIcon, responsive:0},
            ],
        });
    } else { 
        id_div.innerHTML = 'No matching items found';
    }
    return;

}

function find_item(action) {
    var artist = artist_field.value;

    var script = 'scripts/artInventory_getItem.php';

    $.ajax({
        data: "artist="+artist,
        method: "GET",
        url: script,
        success: function(data, textStatus, jqXhr) {
            if(data['noitem']!=undefined) {
                alert("No matching Item Found");
            } else {
                build_table(data['items']);
                $('#test').empty();
            }
        }
    });
}

function remove_from_cart(index) {
    var key = cart_items[index]
    var item = cart[index];

    if(item['need_count']) { need_count--; }
    if(item['need_location']) { need_location--; }
    for (action in actionlist) {
        while((actionlist.length>0) && actionlist[action]['item'] == key) {
            switch(actionlist[action]['action']) {
            case 'Set Location':
                cart[index]['location']=cart[index]['prev_loc'];
                break;
            case 'Set Bid':
                cart[index]['final_price']=cart[index]['prev_bid'];
                break;
            case 'Set Bidder':
                cart[index]['bidder']=cart[index]['prev_bidder'];
                break;
            case 'Check In':
            case 'Sell To Bidsheet':
            case 'Send To Auction':
                cart[index]['status']=cart[index]['prev_status'];
                break;
            }
            actionlist.splice(action, 1);
        }
    }

    cart.splice(index, 1);
    cart_items.splice(index, 1);
    draw_cart();
}

function add_to_cart(index, action) {
    var item = datatbl[index];
    actionlist.push(create_action(action, item.id, null));
    if (config.debug > 0)
        $('#test').empty().append(action + '\n' + JSON.stringify(item, null, 2));
    else
        $('#test').empty();
    clear_message();

    switch(action) {
    case 'Check In':
    case 'Inventory':
    case 'Check Out':
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
    case 'Release':
        break;
    default:
        show_message("Not Implimented", 'warn');
        return; 
    }
        
    if (cart_items.includes(item['id']) == false) {
        cart_items.push(item['id']);
        cart.push(item);
    } else {
        alert('item is already in the cart');
        return;
    }

    item['action']=action;
    draw_cart();
}

function changed_loc() { 
    locations_changed++; 
    location_change_button.hidden = (locations_changed == 0);
}

function draw_cart_row(rownum) {
    var item = cart[rownum];
    var btnClass = 'btn btn-sm p-0';
    var btnStyle = 'style="--bs-btn-font-size: 75%;"';

    var html = `
<div class="row">
    <div class="col-sm-8">
        <div class="text-bg-info">
`;
    var action_html = `
    </div>
    <div class="col-sm-4">
        <button type="button" class="` + btnClass +` btn-secondary" `+btnStyle+` onclick="remove_from_cart(`+rownum+`)">Remove</button><br/>
`;
    var trailing_html = '</div></div>';

    var location_select = '<select onchange="changed_loc();"'
    if(item['need_location']) { location_select += 'class="bg-warning" '; }
    location_select += 'id="loc_' + item['id'] + '">';
    if(item['location'] == "") {
        location_select += '<option></option>';
    }
    for(loc in locations[item['exhibitorNumber']]) {
        if((item['location'] != "") && (locations[item['exhibitorNumber']][loc] == item['location'])) {
            location_select += '<option selected=selected>' + locations[item['exhibitorNumber']][loc] + '</option>';
        } else { 
            location_select += '<option>' + locations[item['exhibitorNumber']][loc] + '</option>';
        }
    }
    location_select += '</select>';
    var show_location = true;
    if((item['action'] == 'Check Out') || (item['action']=='Remove')){
        show_location = false;
        need_location = false;
    }
    switch(item['type']) {
        case 'nfs':
                html += item['id'] + '<span class="right">'+item['action']+'</span></div>'
                    + item['exhibitorName'] + ': ' + item['title'] + '<br/>'
                    + ((show_location)?'Location: ' + location_select + '<br/>':'')
                    + 'Display Only @ ' + item['status'] + '<br/>';
                action_html += '<br/>';
                if(show_location) {
                  if(item['need_location']) {
                    action_html += `<button class="`+btnClass+` btn-primary" `+btnStyle+` type="button" id="` + item['id'] + `"_update_loc" onclick="update_loc(`+rownum+`);">Update Loc</button>`;
                  } else {
                    action_html += `<button class="`+btnClass+` btn-info" `+btnStyle+` type="button" id="` + item['id'] + `"_update_loc" onclick="update_loc(`+rownum+`);">Update Loc</button>`;
                  }
                }
                action_html += '<br/>';
            break;
        case 'art':
            html += item['id'] + '<span class="right">'+item['action']+'</span></div>'
                + item['exhibitorName'] + ': ' + item['title'] + '<br/>'
                + ((show_location)?'Location: ' + location_select + '<br/>':'')
                + 'Art Item @ ' + item['status'] + '<br/>';
            action_html += '<br/>';
            if(show_location) {
              if(item['need_location']) {
                action_html += `<button class="`+btnClass+` btn-primary" `+btnStyle+` type="button" id="` + item['id'] + `"_update_loc" onclick="update_loc(`+rownum+`);">Update Loc</button>`;
              } else {
                action_html += `<button class="`+btnClass+` btn-info" `+btnStyle+` type="button" id="` + item['id'] + `"_update_loc" onclick="update_loc(`+rownum+`);">Update Loc</button>`;
              }
            }
            action_html += '<br/>';
            if(item['status'] == 'BID') {
                action_html += `<button class="`+btnClass+` btn-success" `+btnStyle+` type="button" id="` + item['id'] + `"_to_bidsheet" onclick="update_bid(`+rownum+`,false,true);">To Bid Sheet</button><br/>`;
            } else {
                action_html += '<br/>'; 
            }

            var min_price=item['min_price'];
            if(item['final_price'] != null) {
                min_price = item['final_price'];
            }
            if(item['status'] == 'Quicksale/Sold') {
                html += `Purchased by ` + item['bidder'] + ` @ $` + item['final_price'];
            } else if((item['status'] == 'To Auction')) {
                html += 'Bid ';
                html += `<input type='number' min=0 id='bidder_` + item['id']
                    + `' value="` + item['bidder'] + `" style="width: 7em"></input> @ $`
                    + `<input type='number' min=`+min_price+` id='bid_` + item['id']
                    + `' value="` + item['final_price'] + `" style="width: 7em"></input><br/>`;
                action_html += `<button class="`+btnClass+` btn-success" `+btnStyle+` type="button" id="` + item['id'] + `"_at_auction" onclick="update_bid(`+rownum+`,false,true);">Sold At Auction</button><br/>`;
            } else {
                html += 'Bid ';
                html += `<input type='number' min=0 id='bidder_` + item['id']
                    + `' value="` + item['bidder'] + `" style="width: 7em"></input> @ $`
                    + `<input type='number' min=`+min_price+` id='bid_` + item['id']
                    + `' value="` + item['final_price'] + `" style="width: 7em"></input><br/>`
                action_html += `<button class="`+btnClass+` btn-primary" `+btnStyle+` type="button" id="` + item['id'] + `"_update_bid" onclick="update_bid(`+rownum+`);">Bid</button>`;
                action_html += `<button class="`+btnClass+` btn-secondary" `+btnStyle+` type="button" id="` + item['id'] + `"_to_auction" onclick="update_bid(`+rownum+`,true);">To Auction</button>`;
            }
            action_html += "<br/>"
            break;
        case 'print':
            html += item['id'] + '<span class="right">'+item['action']+'</span></div>'
                + item['exhibitorName'] + ': ' + item['title'] + '<br/>'
                + ((show_location)?'Location: ' + location_select + '<br/>':'')
                + 'Print Shop @ ' + item['status'] + '<br/>';
            if(item['need_count']) {
                html += '<span class="bg-warning">' + item['quantity'] + '</span>'
                + ' @ ' + item['status'] + '<br/>';
            } else {
                html += item['quantity'] + ' @ ' + item['status'] + '<br/>';
            }
            action_html += '<br/>';
            if(show_location) {
              if(item['need_location']) {
                action_html += `<button class="`+btnClass+` btn-primary" `+btnStyle+` type="button" id="` + item['id'] + `"_update_loc" onclick="update_loc(`+rownum+`);">Update Loc</button>`;
                } else {
                action_html += `<button class="`+btnClass+` btn-info" `+btnStyle+` type="button" id="` + item['id'] + `"_update_loc" onclick="update_loc(`+rownum+`);">Update Loc</button>`;
              }
            }
            action_html += '<br/>';
            if(item['need_count']) {
                action_html += `<button class="`+btnClass+` btn-primary" `+btnStyle+` type="button" id="` + item['id'] + `"_confirm_count" onclick="confirm_count(`+rownum+`);">Confirm Qty</button>`;
            }
            action_html += '<br/>';
            break;
        default:
            alert('Unknown Type');
    }

    return html + action_html + trailing_html;
}

function change_locs() {
    for (row in cart) {
        var item = cart[row]['id'];
        var new_loc = document.getElementById('loc_' + item).value;
        if(new_loc != cart[row]['location']) {
            update_loc(row, new_loc, false);
        }
    }

    draw_cart();
}

function update_bid(row, to_auction=false, close=false) {
    var item = cart[row]['id'];
    var bidder = document.getElementById('bidder_' + item).value; 
    var price = document.getElementById('bid_' + item).value; 
    //check if valid
    if(cart[row]['type'] != "art") {
        alert("Item not in auction");
    } else {
        actionlist.push(create_action('Set Bidder', item, bidder));
        actionlist.push(create_action('Set Bid', item, price));

        if (cart[row]['final_price'] != null) {
            if (Number(cart[row]['final_price']) > price) {
                if (!confirm("Price is less than last bid, are you sure?")) {
                    document.getElementById('bid_' + item).value = cart[row]['final_price'];
                    return;
                }
            }
        }

        if (cart[row]['min_price'] != null) {
            if (Number(cart[row]['min_price']) > price) {
                if (!confirm("Price is less than minimum price of " + cart[row]['min_price'] + ", are you sure?")) {
                    document.getElementById('bid_' + item).value = cart[row]['final_price'];
                    document.getElementById('bidder_' + item).value = cart[row]['bidder'];
                    return;
                }
            }
        }
        cart[row]['bidder']=bidder;
        cart[row]['final_price']=price;

        if(to_auction) {
            actionlist.push(create_action('Send To Auction', item));
            cart[row]['status']='To Auction';
        }
        if(close) {
            if(cart[row]['status'] == 'To Auction') {
                actionlist.push(create_action('Sell At Auction', item));
                cart[row]['status']='Sold At Auction';
            } else {
                actionlist.push(create_action('Sell To Bidsheet', item));
                cart[row]['status']='Sold To Bidsheet';
            }
        }
    }

    draw_cart();
}

function update_loc(row, loc, redraw=true) {
    var item = cart[row]['id'];
    if(loc == undefined) { loc = document.getElementById('loc_' + item).value; }
    console.log("Shift " + item + " to " + loc);
    //check if valid
    if(!locations[cart[row]['exhibitorNumber']].includes(loc)) {
        alert("Invalid location");
    } else {
        actionlist.push(create_action('Set Location', item, loc));

        cart[row]['location']=loc;

        if(cart[row]['need_location']) {
            cart[row]['need_location']=false;
            need_location--;
        }
    }

    if(redraw) { draw_cart(); }
}


function confirm_count(row) {
    cart[row]['need_count'] = false;
    need_count--;
    draw_cart();
}

function toggle_visibility(id) {
    var element = document.getElementById(id);
    var element_show = document.getElementById(id + "_show");
    var element_hide = document.getElementById(id + "_hide");

    if(element.style.display == "none") {
        element.style.display = "block";
        element_hide.style.display = "inline";
        element_show.style.display = "none";
    } else {
        element.style.display = "none";
        element_hide.style.display = "none";
        element_show.style.display = "inline";
    }

}

function draw_notes() {
    var html = `<div onclick="toggle_visibility('artInventory_pending')">` + actionlist.length + ` Pending Actions
    <span class='btn btn-secondary btn-sm p-0' id="artInventory_pending_show">show</span><span class='btn btn-secondary btn-sm p-0' id="artInventory_pending_hide" style="display: none">hide</span>
    <div id="artInventory_pending" class="text-info" style="display: none"><ul>`;

    for (action in actionlist) {
        html += "<li>" + actionlist[action]['action'] + " " + actionlist[action]['item'] 
        switch(actionlist[action]['action']){
            case "Set Location": 
            case "Set Bidder":
            case "Set Bid":
                html += " to " + actionlist[action]['value']
                break;
            case "Check In":
            case "To Auction":
            default:
                break;
        }
        html += '</li>';

    }
        
    html += `</ul></div>`;
    if(need_count > 0) {
        html += "<div>Please Confirm current quantity for " + need_count + " items.</div>";
    }
    if(need_location > 0) {
        html += "<div>Please set locations for " + need_location + " items.</div>";
    }

    return html;
}

function draw_cart() {
    locations_changed = 0;
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

    if(actionlist.length > 0) {
        html += `
<div class="row">
    <div class="col-sm-12 text-bg-secondary">Notes</div>
</div>
`;

        html += draw_notes();
    }

    html += '</div>' //end container-fluid
    cart_div.innerHTML=html;

    //clear buttons
    startover_button.hidden = num_rows == 0;
    inventory_update_button.hidden = !((num_rows > 0) & (need_count == 0) & (need_location == 0));
    location_change_button.hidden = (locations_changed == 0);
}

function create_action(action, item, value) {
    return {
        action: action,
        item: item,
        value: value
    };
}
