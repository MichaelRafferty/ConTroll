var find_result_table = null;
var datatbl = new Array();
// result data
var result_data = [
    {
        perid: 1, first_name: "John", middle_name: "Q.", last_name: "Smith", badge_name: "John Smith",
        address_1: "123 Any St", address_2: '', city: 'Philadelphia', state: 'PA', postal_code: '19101-0000', country: 'USA',
        email_addr: 'john.q.public@gmail.com', phone: '215-555-2368',
        share_reg: 'Y', contact_ok: 'Y', active: 'Y', banned: 'N',
        reg_type: 'general', price: 75, paid: 75, tid: 11, index:0, 
    },
    {
        perid: 2, first_name: "Jane", middle_name: "Q.", last_name: "Smith", badge_name: "Jane Smith",
        address_1: "123 Any St", address_2: '', city: 'Philadelphia', state: 'PA', postal_code: '19101-0000', country: 'USA',
        email_addr: 'jane.q.public@gmail.com', phone: '215-555-2368',
        share_reg: 'Y', contact_ok: 'Y', active: 'Y', banned: 'N',
        reg_type: 'general', price: 75, paid: 75, tid: 11, index:1,
    },
    {
        perid: 3, first_name: "Amy", middle_name: "", last_name: "Jones", badge_name: "Lady Amy",
        address_1: "1023 Chestnut St", address_2: '', city: 'Philadelphia', state: 'PA', postal_code: '19103-0000', country: 'USA',
        email_addr: 'ladyamy@gmail.com', phone: '215-555-5432',
        share_reg: 'Y', contact_ok: 'Y', active: 'Y', banned: 'N',
        reg_type: 'student', price: 40, paid: 40, tid: 13, index:2,
    },
    {
        perid: 4, first_name: "John", middle_name: "", last_name: "Doe", badge_name: "Unknown Attendee",
        address_1: "Unknown Monument", address_2: '', city: 'Philadelphia', state: 'PA', postal_code: '19103-0000', country: 'USA',
        email_addr: 'lost@aol.com', phone: '',
        share_reg: 'Y', contact_ok: 'Y', active: 'Y', banned: 'N',
        reg_type: '', price: '', paid: '', tid: '', index: 3,
    },
    {
        perid: 5, first_name: "Bad", middle_name: "", last_name: "Mewber", badge_name: "Baddie",
        address_1: "Unknown Location", address_2: '', city: 'Philadelphia', state: 'PA', postal_code: '19103-0000', country: 'USA',
        email_addr: 'abuse@aol.com', phone: '',
        share_reg: 'N', contact_ok: 'N', active: 'Y', banned: 'Y',
        reg_type: '', price: '', paid: '', tid: '', index: 4,
    },
];

var cart = new Array();
var cart_div = null;

function build_record_hover(e, cell, onRendered) {
    data = cell.getData();
    //console.log(data);
    hover_text = data['first_name'] + ' ' + data['middle_name'] + ' ' + data['last_name'] + '<br/>' +
        data['address_1'] + '<br/>';
    if (data['address_2'] != '') {
        hover_text += data['address_2'] + '<br/>';
    }
    hover_text += data['city'] + ', ' + data['state'] + ' ' + data['postal_code'] + '<br/>';
    if (data['country'] != '' && data['country'] != 'USA') {
        hover_text += data['country'] + '<br/>';
    }
    hover_text += 'Badge Name: ' + data['badge_name'] + '<br/>' +
        'Email: ' + data['email_addr'] + '<br/>' + 'Phone: ' + data['phone'] + '<br/>' +
        'Active:' + data['active'] + ' Contact?:' + data['contact_ok'] + ' Share?:' + data['share_reg'] + ' Banned:' + data['banned'] + '<br/>' +
        'Membership: ' + data['reg_type'] + '<br/>';

    return hover_text;
}

function add_to_cart(index) {
    if (index >= 0) {
        cart.push(result_data[index]);
    } else {
        var row;
        index = -index;
        for (row in result_data) {
            if (result_data[row]['tid'] == index) {
                cart.push(result_data[row]);
            }
        }
    }
    draw_cart();
}

function remove_from_cart(index) {
    cart.splice(index, 1);
    draw_cart();
}

function draw_cart_row(rownum) {
    row = cart[rownum];
    var rowhtml = '<div class="row">';
    if (row['banned'] == 'Y') {
        rowhtml += '<div class="col-sm-8 text-bg-danger">';
    } else if (row['reg_type'] == '') {
        rowhtml += '<div class="col-sm-8 text-bg-info">'
    } else {
        rowhtml += '<div class="col-sm-8 text-bg-success">'
    }
    if (row['reg_type'] != '') {
        rowhtml += 'Membership: ' + row['reg_type'] + '</div>';
    } else {
        rowhtml += 'No Membership</div>';
    }
    rowhtml += `        
    <div class="col-sm-4"><button type="button" class="btn btn-small btn-secondary pt-0 pb-0" onclick=remove_from_cart(` + rownum + `)>Remove</button></div>
</div>
<div class="row">
    <div class="col-sm-8">` + row['badge_name'] + `</div>
    <div class="col-sm-2 text-end">` + row['price'] + `</div>
    <div class="col-sm-2 text-end">` + row['paid'] + `</div>
</div>
<div class="row">
    <div class="col-sm-8">` + row['first_name'] + ' ' + row['middle_name'] + ' ' + row['last_name'] + `</div>
</div>
`;
    return rowhtml;
}

function draw_cart() {

    if (cart_div == null) {
        cart_div = document.getElementById("cart");
    }

    var total_price = 0;
    var total_paid = 0;
    var row;
    var html = `
<div class="container-fluid">
<div class="row">
    <div class="col-sm-8 text-bg-primary">Badge</div>
    <div class="col-sm-2 text-bg-primary">Price</div>
    <div class="col-sm-2 text-bg-primary">Paid</div>
</row>
`;
    for (row in cart) {
        html += draw_cart_row(row);
        total_price += cart[row]['price'];
        total_paid += cart[row]['paid'];
    }
    html += `<div class="row">
    <div class="col-sm-8 text-end">Totals:</div>
    <div class="col-sm-2 text-end">` + total_price + `</div>
    <div class="col-sm-2 text-end">` + total_paid + `</div>
</div>
`;
    cart_div.innerHTML = html;
}

function draw_record(row) {
    var data = result_data[row];
    var html = `
<div class="container-fluid">
    <div class="row mt-2">
        <div class="col-sm-3"></div>
        <div class="col-sm-9"><button class="btn btn-success btn-small" id="add_btn_1" onclick="add_to_cart(` + row + `);">Add to Cart</button>
    </div>
    <div class="row">
        <div class="col-sm-3">` + 'Badge Name:' + `</div>
        <div class="col-sm-9">` + data['badge_name'] + `</div>
    </div>
    <div class="row">
        <div class="col-sm-3">Name:</div>
        <div class="col-sm-9">` +
        data['first_name'] + ' ' + data['middle_name'] + ' ' + data['last_name'] + `
        </div>
    </div>  
    <div class="row">
        <div class="col-sm-3">Address:</div>
        <div class="col-sm-9">` + data['address_1'] + `</div>
    </div>
`;
    if (data['address_2'] != '') {
        html += `
    <div class="row">
        <div class="col-sm-3"></div>
        <div class="col-sm-9">` + data['address_2'] + `</div>
    </div>
`;
    }
    html += `
    <div class="row">
       <div class="col-sm-3"></div>
       <div class="col-sm-9">` + data['city'] + ', ' + data['state'] + ' ' + data['postal_code'] + `</div>
    </div>
`;
    if (data['country'] != '' && data['country'] != 'USA') {
        html += `
    <div class="row">
       <div class="col-sm-3"></div>
       <div class="col-sm-9">` + data['country'] + `</div>
    </div>
`;
    }
    html += `
    <div class="row">
       <div class="col-sm-3">Email Address:</div>
       <div class="col-sm-9">` + data['email_addr'] + `</div>
    </div>
    <div class="row">
       <div class="col-sm-3">Phone::</div>
       <div class="col-sm-9">` + data['phone'] + `</div>
    </div>
    <div class="row">
       <div class="col-sm-3"></div>
       <div class="col-sm-auto">Active: ` + data['active'] + `</div>
       <div class="col-sm-auto">Contact OK: ` + data['contact_ok'] + `</div>
       <div class="col-sm-auto">Share Reg: ` + data['share_reg'] + `</div>
       <div class="col-sm-auto">Banned: ` + data['banned'] + `</div>
    </div>
    <div class="row">
       <div class="col-sm-3">Membership Type:</div>
       <div class="col-sm-9">` + data['reg_type'] + `</div>
    </div>
`;
    return html;
}

function addCartIcon(cell, formatterParams, onRendered) { //plain text value
    if (cell.getRow().getData().banned == 'Y') {
        return '<button type="button" class="btn btn-sm btn-danger p-0">B</button>';
    }
    return '<button type="button" class="btn btn-sm btn-success p-0">Add</button>';
};

function addCartClick(e, cell) {
    var index = cell.getRow().getData().index;
    add_to_cart(index);
}

function find_record() {
    if (find_result_table != null) {
        find_result_table.destroy();
        find_result_table = null;
    }
    var html = '';

    datatbl = new Array();

    id_div = document.getElementById("find_results");
    id_div.innerHTML = "";
    name_field = document.getElementById("find_name");
    name_search = name_field.value.toLowerCase();
 
    if (name_search != '') {
        // mockup of name search results
        for (rowindex in result_data) {
            var row = result_data[rowindex];
            var sourcestring = row['last_name'] + ' ' + row['first_name'] + ' ' + row['badge_name'] + ' ' + row['email_addr'];
            sourcestring = sourcestring.toLowerCase();
            if (sourcestring.includes(name_search)) {
                datatbl.push(row);
            }
         }

        if (datatbl.length > 0) {
            // table
            find_result_table = new Tabulator('#find_results', {
                maxHeight: "600px",
                data: datatbl,
                layout: "fitColumns",
                columns: [
                    { title: "ID", field: "perid", hozAlign: "right", tooltip: build_record_hover, width: 50, },
                    { title: "Last Name", field: "last_name", headerFilter: true, headerWordWrap: true, tooltip: true, },
                    { title: "First Name", field: "first_name", headerFilter: true, headerWordWrap: true, tooltip: true, },
                    { title: "Middle Name", field: "middle_name", headerFilter: false, headerWordWrap: true, tooltip: true, headerSort: false, maxWidth: 60, width: 60 },
                    { title: "Badge Name", field: "badge_name", headerFilter: true, headerWordWrap: true, tooltip: true, },
                    { title: "Email Address", field: "email_addr", headerFilter: true, headerWordWrap: true, tooltip: true, },
                    { title: "Reg", field: "reg_type", headerFilter: true, headerWordWrap: true, tooltip: true, maxWidth: 80, width: 80, },
                    {
                        title: "Cart", width: 45, hozAlign: "center", headerFilter: false, headerSort: false,
                        cellClick: addCartClick, formatter: addCartIcon,
                    },
                    { field: "index", visible: false, },
                ],
            });
            //id_div.innerHTML = "name search results";            
        } else {
            id_div.innerHTML = 'No matching records found'
        }
        return;
    }

    perid_field = document.getElementById("find_perid");
    perid_search = perid_field.value;
   
    if (perid_search > 0) {       
        if (perid_search > 0) {
            html = '';
            for (row in result_data) {
                if (result_data[row]['perid'] == perid_search) {
                    html += draw_record(row);
                }
            }
            if (html != '') {
                html += '</div>';
                id_div.innerHTML = html;
            } else {
                id_div.innerHTML = 'No matching records found'
            }
            return;
        }
    }

    transid_field = document.getElementById("find_transid");
    transid_search = transid_field.value
    if (transid_search > 0) {
        html = '';
        for (row in result_data) {
            if (result_data[row]['tid'] == transid_search) {
                html += draw_record(row);
            }
        }
        if (html != '') {
            html += `
    <div class="row mt-4">
        <div class="col-sm-3"></div>
        <div class="col-sm-9"><button class="btn btn-primary btn-small" id="add_btn_all" onclick="add_to_cart(-` + transid_search + `);">Add All Cart</button>
        </div>
    </div>
</div>`;
            id_div.innerHTML = html;
        } else {
            id_div.innerHTML = 'No matching records found'
        }

        return;
    }

    id_div.innerHTML = "No search criteria specified";
}