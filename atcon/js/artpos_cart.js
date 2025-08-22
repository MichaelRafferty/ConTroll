// Cart Class - all functions and data related to the cart portion of the right side of the screen
// Cart includes: art and paymentd
class artpos_cart {
// cart dom items
    #startover_button = null;
    #add_button = null;
    #pay_button = null;
    #next_button = null;
    #release_button = null;
    #cart_div = null;

// cart states
    #in_pay = false;
    #freeze_cart = false;
    #changeRow = null;

// cart internals
    #total_price = 0;
    #total_paid = 0;
    #total_pmt = 0;
    #total_art = 0;
    #total_tax = 0;
    #unpaid_rows = 0;
    #cart_art = [];
    #cart_art_map = new map();
    #cart_pmt = [];

// initialization
    constructor() {
// lookup all DOM elements
// ask to load mapping tables
        this.#cart_div = document.getElementById("cart");
        this.#startover_button = document.getElementById("startover_btn");
        this.#next_button = document.getElementById("next_btn");
        this.#release_button = document.getElementById("release_btn");
        this.#add_button = document.getElementById("add_btn");
        this.#pay_button = document.getElementById("pay_btn");
    }

    // simple get/set/hide/show methods
    setInPay() {
        this.#in_pay = true;
    }

    clearInPay() {
        this.#in_pay = false;
    }

    freeze() {
        this.#freeze_cart = true;
    }

    unfreeze() {
        this.#freeze_cart = false;
    }

    isFrozen() {
        return this.#freeze_cart == true;
    }

    hidePay() {
        this.#pay_button.hidden = true;
    }

    showPay() {
        this.#pay_button.hidden = false;
    }

    hideRelease() {
        this.#release_button.hidden = true;
    }

    showRelease() {
        this.#release_button.hidden = false;
    }

    hideNext() {
        this.#next_button.hidden = true;
    }

    showNext() {
        this.#next_button.hidden = false;
    }

    hideStartOver() {
        this.#startover_button.hidden = true;
    }

    showStartOver() {
        this.#startover_button.hidden = false;
    }

    // get overall cart values
    // number of people in the cart
    getCartLength() {
        return this.#cart_art.length;
    }

    // number of payment records in the cart
    getPmtLength() {
        return this.#cart_pmt.length;
    }

    // get total price
    getTotalPrice() {
        return Number(this.#total_price);
    }

    // get total amount paid
    getTotalPaid() {
        return Number(this.#total_paid);
    }

    // get total pmts in cart
    getTotalPmt() {
        return Number(this.#total_pmt);
    }

    // check if a person is in cart already
    notinCart(artId) {
        return this.#cart_art_map.isSet(artId) === false;
    }

    getItemKey(index) {
        return this.#cart_art[index]['item_key'];
    }

    getArtId(index) {
        return this.#cart_art[index]['id'];
    }

    getTitle(index) {
        return this.#cart_art[index]['title'];
    }

    getMaterial(index) {
        return this.#cart_art[index]['material'];
    }

    getArtType(index) {
        return this.#cart_art[index]['Type'];
    }

    getStatus(index) {
        return this.#cart_art[index]['Status'];
    }

    getFinalPrice(index) {
        return this.#cart_art[index]['final_price'];
    }

    getExhibitorNumber(index) {
        return this.#cart_art[index]['exhibitorNumber'];
    }


    // make a copy of private structures for use in ajax calls back to the PHP.   The master copies are only accessible within the class.
    getCartArt() {
        return make_copy(this.#cart_art);
    }


    getCartMap() {
        return this.#cart_art_map.getMap();
    }

    getCartPmt() {
        return make_copy(this.#cart_pmt);
    }


// if no art or payments have been added to the database, this will reset for the next customer

    startOver(mode = 0) {
        // if cart has unpaid items in it, delete the artSales items
        var numUnpaid = 0;
        for (var i = 0; i < this.#cart_art.length; i++) {
            if (this.#cart_art[i].paid == undefined || Number(this.#cart_art[i].paid) == 0)
                numUnpaid++;
        }
        if (numUnpaid > 0) {
            // delete the unpaid's from ArtSales
            $.ajax({
                method: "POST",
                url: "scripts/artpos_removeArtSalesRecord.php",
                data: {
                    artSalesId: -1,
                    perid: currentPerson.id,
                    action: 'deleteUnpaid',
                },
                success: function (data, textstatus, jqxhr) {
                    if (data.error !== undefined) {
                        show_message(data.error, 'error');
                        return;
                    }
                    if (data.message !== undefined) {
                        show_message(data.message, 'success');
                        return;
                    }
                    if (data.warn !== undefined) {
                        show_message(data.warn, 'warn');
                        return;
                    }
                },
                error: function (jqXHR, textstatus, errorThrown) {
                    $("button[name='findArtBtn']").attr("disabled", false);
                    showAjaxError(jqXHR, textstatus, errorThrown);
                }
            });

        }
        // empty cart
        this.#cart_art = [];
        this.#cart_pmt = [];
        this.#freeze_cart = false;

        this.hideNext();
        this.hidePay();
        this.#in_pay = false;
        this.drawCart();
        this.showStartOver();
    }

    // add art record to cart
    add(artItem) {
        var pindex = this.#cart_art.length;
        this.#cart_art.push(make_copy(artItem));
        this.#cart_art[pindex]['index'] = pindex;
        this.drawCart();
    }

// remove an art item from the cart
    remove(artId) {
        var index = this.#cart_art_map.get(artId);

        var art = this.#cart_art[index];
        var artSalesId = null;
        if (art.hasOwnProperty('artSalesId'))
            artSalesId = art.artSalesId;
        if (artSalesId != null) {
            // remove the element from the system, as it was removed from the cart, if the amount paid is 0
            var paid = Number(art.paid);
            if (paid == 0) {
                $.ajax({
                    method: "POST",
                    url: "scripts/artpos_removeArtSalesRecord.php",
                    data: {
                        artSalesId: artSalesId,
                        perid: currentPerson.id,
                        action: 'deleteUnpaid',
                    },
                    success: function (data, textstatus, jqxhr) {
                        if (data.error !== undefined) {
                            show_message(data.error, 'error');
                            return;
                        }
                        if (data.message !== undefined) {
                            show_message(data.message, 'success');
                            return;
                        }
                        if (data.warn !== undefined) {
                            show_message(data.warn, 'warn');
                            return;
                        }
                    },
                    error: function (jqXHR, textstatus, errorThrown) {
                        $("button[name='findArtBtn']").attr("disabled", false);
                        showAjaxError(jqXHR, textstatus, errorThrown);
                    }
                });
            }
        }

        this.#cart_art.splice(index, 1);
        // splices loses me the index number for the cross-reference, so the cart needs renumbering
        this.drawCart();
    }

// update payment data in  cart
    updatePmt(data) {
        if (data['prow']) {
            this.#cart_pmt.push(data['prow']);
        }
        if (data['crow']) {
            this.#cart_pmt.push(data['crow']);
        }

        data['cart_art'].forEach((artitem) => {
            var index = this.#cart_art_map.get(artitem['id']);
            this.#cart_art[index] = artitem;
        });
    }

// add payment record to cart
    addPmt(pmt) {
        this.#cart_pmt.push(pmt);
    }

    // update Row quantity - and reprice item
    updateRowQuantity(rowid) {
        var newqty = document.getElementById('purQuantity_' + rowid).value;
        //console.log(newqty);
        //console.log(this.#cart_art_map);
        var row = this.#cart_art[this.#cart_art_map.get(rowid)];
        if (newqty > row['quantity'] && newqty != row['purQuantity']) {
            if (!confirm("Only " + row['quantity'] + " are available, are you sure?")) {
                document.getElementById('purQuantity_' + rowid).value = row['purQuantity'];
                return;
            }
        }
        row['purQuantity'] = newqty;
        this.drawCart();
    }
// cart_renumber:
// rebuild the indices in the cart_art table
// for shortcut reasons indices are used to allow usage of the filter functions built into javascript
// this rebuilds the index and art id cross-reference maps.  It needs to be called whenever the number of items in cart is changed.
    #cart_renumber() {
        var index;
        this.#cart_art_map = new map();
        for (index = 0; index < this.#cart_art.length; index++) {
            this.#cart_art[index]['index'] = index;
            this.#cart_art_map.set(this.#cart_art[index]['id'], index);
        }
    }

    // remove from cart - delete the item from the cart and redraw it
    remove_from_cart(id) {
        alert('remove(' + id + ') called');
    }

// format all of the memberships for one record in the cart
    #drawCartRow(rownum) {
        var row = this.#cart_art[rownum];
        var artLabel = (row['exhibitorNumber'] + '-' + row['item_key'])
        var rowlabel;


        var col1 = '';
        var btncolor = null;

        // first row - member name, remove button
        var rowhtml = '<div class="row">';
        rowhtml += '<div class="col-sm-8 text-bg-success">Art Item: ' + artLabel + ' (' + row['type'] + ')</div>';
        if (!this.#freeze_cart && row['paid'] == 0) {
            rowhtml += `
        <div class="col-sm-2 p-0 text-center"><button type="button" class="btn btn-sm btn-secondary pt-0 pb-0 ps-1 pe-1" onclick="cart.remove(` + row['id'] + `)">Remove</button></div>
`;
        }
        rowhtml += '</div>'; // end of exhibitor Number/ItemKey row

        // Artist
        rowhtml += '<div class="row"><div class="col-sm-2">Artist:' + '</div><div class="col-sm-10">' + row['exhibitorName'] + '</div></div>';
        // Title
        rowhtml += '<div class="row"><div class="col-sm-2">Title: ' + '</div><div class="col-sm-10">' + row['title'] + '</div></div>';
        // Material
        rowhtml += '<div class="row"><div class="col-sm-2">Material: ' + '</div><div class="col-sm-10">' + row['material'] + '</div></div>';
        if (row['type'] == 'art') {
            // Status of art item
            rowhtml += '<div class="row"><div class="col-sm-2">Status: ' + '</div><div class="col-sm-10">' + row['status'] + '</div></div>';
        }
        if (this.#freeze_cart) {
            rowhtml += '<div class="row"><div class="col-sm-2">Quantity: ' + '</div><div class="col-sm-10">' + row['purQuantity'] + '</div></div>';
        } else if (row['type'] == 'print') {
            rowhtml += '<div class="row"><div class="col-sm-2">Quantity: ' + '</div><div class="col-sm-10"><input class="no-spinners" type="number" min="1"' +
                ' max="' + row['quantity'] + '"' +
                ' name="purQuantity_' + row['id'] + '" id="purQuantity_' + row['id'] + '" value="' + row['purQuantity'] +
                '" onchange="cart.updateRowQuantity(' + row['id'] +');"/>' +
                ' of ' + row['quantity'] + ' remaining</div></div>';
        }
        // price
        var priceType = 'Final';
        if (row['type'] == 'print') {
            priceType = 'Sale';
            row['display_price'] = row['sale_price'] * row['purQuantity'];
        } else if (row['type'] == 'art' && (row['final_price'] == null || row['final_price'] == 0)) {
            priceType = 'Quick Sale';
            row['display_price'] = row['sale_price'];
        } else {
            row['display_price'] = row['final_price'];
        }
        row['priceType'] = priceType;
        rowhtml += '<div class="row"><div class="col-sm-8 p-0 text-end">' + priceType + ' Price:</div>' +
            '<div class="col-sm-2 text-end">$' + Number(row['display_price']).toFixed(2) + '</div>' +
            '<div class="col-sm-2 text-end">$' + Number(row['paid']).toFixed(2) + '</div></div>';

        this.#total_price += Number(row['display_price']);
        this.#total_paid += Number(row['paid']);
        if (row['display_price'] > row['paid'])
            this.#unpaid_rows++;
        return rowhtml;
    }

// draw a payment row in the cart
    #drawCartPmtRow(prow) {
        //   index: cart_pmt.length, amt: pay_amt, pretax: art amount, tax, ccauth: ccauth, checkno: checkno, desc: eldesc.value, type: ptype,

        var pmt = this.#cart_pmt[prow];
        var code = '';
        var desc = pmt['desc'] ? pmt['desc'] :'';
        if (pmt['type'] == 'check') {
            if ((!pmt['checkno']) || pmt['checkno'] == '') {
                code = desc.substring(desc.indexOf(':') + 1, desc.indexOf(';'));
                if (code == undefined || code == null) {
                    code = '';
                }
                desc = desc.substring(desc.indexOf(';') + 1);
            } else {
                code = pmt['checkno'];
            }
        } else if (pmt['type'] == 'credit') {
            code = pmt['ccauth'];
        }
        var ttype = pmt['type'];
        var html = '';
        if (pmt['time']) {
            html = '<div class="row mt-1"><div class="col-sm-12 p-0">' + pmt['time'] + '</div></div>';
        }
        if (desc == '') {
            html += `<div class="row">
    <div class="col-sm-4 p-0">` + ttype + `</div>
    <div class="col-sm-2 p-0">` + code + `</div>
    <div class="col-sm-2 text-end">` + Number(pmt['pretax']).toFixed(2) + `</div>
    <div class="col-sm-2 text-end">` + Number(pmt['tax']).toFixed(2) + `</div>
    <div class="col-sm-2 text-end">` + Number(pmt['amt']).toFixed(2) + `</div>
</div>
`;
        } else {
            html += `<div class="row">
    <div class="col-sm-2 p-0">` + ttype + `</div>
    <div class="col-sm-2 p-0">` + desc + `</div>
    <div class="col-sm-2 p-0">` + code + `</div>
    <div class="col-sm-2 text-end">` + Number(pmt['pretax']).toFixed(2) + `</div>
    <div class="col-sm-2 text-end">` + Number(pmt['tax']).toFixed(2) + `</div>
    <div class="col-sm-2 text-end">` + Number(pmt['amt']).toFixed(2) + `</div>
</div>
`;
        }
        return html;
    }

// draw/update by redrawing the entire cart
    drawCart() {
        this.#cart_renumber(); // to keep indexing intact, renumber the index and pindex each time
        this.#total_price = 0;
        this.#total_paid = 0;
        var name = '';
        var num_rows = 0;
        if (currentPerson) {
            name = ' For ' + (currentPerson.first_name + ' ' + currentPerson.last_name).trim();
        }
        var html = `
<div class="container-fluid">
<div class="row">
    <div class="col-sm-8 text-bg-primary">Artwork` + name + `</div>
    <div class="col-sm-2 text-bg-primary text-end">Price</div>
    <div class="col-sm-2 text-bg-primary text-end">Paid</div>
</div>
`;
        this.#unpaid_rows = 0;
        for (var rownum in this.#cart_art) {
            num_rows++;
            html += this.#drawCartRow(rownum);
        }
        this.#total_price = Number(this.#total_price.toFixed(2));
        this.#total_paid = Number(this.#total_paid.toFixed(2));
        html += `<div class="row">
    <div class="col-sm-8 p-0 text-end">Total:</div>
    <div class="col-sm-2 text-end">$` + Number(this.#total_price).toFixed(2) + `</div>
    <div class="col-sm-2 text-end">$` + Number(this.#total_paid).toFixed(2) + `</div>
</div>
`;

        if (this.#cart_pmt.length > 0) {
            html += `
<div class="row mt-3">
    <div class="col-sm-4 text-bg-primary">Payment</div>
    <div class="col-sm-2 text-bg-primary">Code</div>
    <div class="col-sm-2 text-bg-primary text-end">Art</div>
    <div class="col-sm-2 text-bg-primary text-end">Tax</div>
    <div class="col-sm-2 text-bg-primary text-end">Amount</div>
</div>
`;
            this.#total_pmt = 0;
            this.#total_art = 0;
            this.#total_tax = 0;
            for (var prow in this.#cart_pmt) {
                html += this.#drawCartPmtRow(prow);
                this.#total_pmt += Number(this.#cart_pmt[prow]['amt']);
                this.#total_art += Number(this.#cart_pmt[prow]['pretax']);
                this.#total_tax += Number(this.#cart_pmt[prow]['tax']);
            }
            html += `<div class="row">
    <div class="col-sm-6 p-0"></div>
    <div class="col-sm-6 p-0">
        <hr style="height:4px; color: black;background-color:black;border-width:0;"/>
    </div>
</div>
<div class="row">
    <div class="col-sm-6 p-0 text-end">Payment Total:</div>`;
            this.#total_pmt = Number(this.#total_pmt.toFixed(2));
            html += `
    <div class="col-sm-2 text-end">$` + Number(this.#total_art).toFixed(2) + `</div>
    <div class="col-sm-2 text-end">$` + Number(this.#total_tax).toFixed(2) + `</div>
    <div class="col-sm-2 text-end">$` + Number(this.#total_pmt).toFixed(2) + `</div>
</div>
`;
        }

        if (num_rows > 0) {
            this.#pay_button.hidden = this.#in_pay;
        }
        html += '</div>'; // ending the container fluid
        //console.log(html);
        this.#cart_div.innerHTML = html;
        //this.#startover_button.hidden = num_rows == 0;
        if (this.#unpaid_rows == 0) {
            this.#pay_button.hidden = true;
        }
        if (this.#freeze_cart) {
            this.#pay_button.hidden = true;
            this.hideStartOver();
        }
    }

    updateFromDB(data) {
        var newrow;

        // update the fields created by the database transactions
        var updated_art = data['updated_art'];
        for (rownum in updated_art) {
            newrow = updated_art[rownum];
            var keys = Object.keys(newrow);
            for (var keynum in keys) {
                var key = keys[keynum];
                this.#cart_art[newrow['rowpos']][key] = newrow[key];
            }
            this.#cart_art[newrow['rowpos']]['dirty'] = false;
        }


// delete all rows from cart marked for delete
        var delrows = [];
        var splicerow = null;
        for (var rownum in this.#cart_art) {
            if (this.#cart_art[rownum]['todelete'] == 1) {
                delrows.push(rownum);
            }
        }
        delrows = delrows.reverse();
        for (splicerow in delrows)
            this.#cart_art.splice(delrows[splicerow], 1);

// redraw the cart with the new id's and maps, which will compute the unpaid_rows.
        cart.drawCart();
        return this.#unpaid_rows;
    }
}
