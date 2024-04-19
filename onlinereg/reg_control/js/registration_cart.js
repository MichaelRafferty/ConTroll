// Cart Class - all functions and data related to the cart portion of the right side of the screen
// Cart includes: People, Memberships and Payments.
class reg_cart {
// cart dom items
    #void_button = null;
    #startover_button = null;
    #review_button = null;
    #next_button = null;
    #nochanges_button = null;
    #cart_div = null;

// cart states
    #in_review = false;
    #freeze_cart = false;
    #changeRow = null;

// cart internals
    #total_price = 0;
    #total_paid = 0;
    #total_pmt = 0;
    #unpaid_rows = 0;
    #membership_rows = 0;
    #needmembership_rows = 0;
    #cart_membership = [];
    #cart_perinfo = [];
    #cart_perinfo_map = new map();
    #cart_pmt = [];
    #days = ['sun','mon','tue','wed','thu','fri','sat'];

// cart html items
    #membership_select = null;
    #membership_selectlist = null;
    #upgrade_select = null;
    #yearahead_select = null;
    #yearahead_selectlist = [];
    #addon_select = null;

// initialization
    constructor() {
// lookup all DOM elements
// ask to load mapping tables
        this.#cart_div = document.getElementById("cart");
        this.#void_button = document.getElementById("void_btn");
        this.#startover_button = document.getElementById("startover_btn");
        this.#review_button = document.getElementById("review_btn");
        this.#next_button = document.getElementById("next_btn");
        this.#nochanges_button = document.getElementById("cart_no_changes_btn");
    }

// load mapping tables from database to javascript array
// also retrieve session data about printers

// the cart maintains its own copy of membership_select, as well as upgrade, year ahead and add-on.
// This is intended to only be built once on first page load.  It also draws the empty cart.
    set_initialData(membership_select, membership_selectlist) {
        // membership select is used by multiple classes, so calculate it once in the parent and pass it in.
        this.#membership_select = membership_select;
        this.#membership_selectlist = membership_selectlist;
        // cart is only place to use upgrade_select, so build it.
        this.#upgrade_select = {};

        var row = null;
        // upgrade_select
        filt_excat = null;
        filt_cat = new Array('upgrade')
        filt_shortname_regexp = null;
        filt_conid = [Number(conid)];
        var match = memList.filter(mem_filter);
        var nonday = 0;
        for (row in match) {
            var label = match[row]['label'];
            var day = label.replace(/.*upgrade +(...).*/i, '$1').toLowerCase();
            if (day.length > 3)
                day = (match[row]['label']).toLowerCase().substring(0, 3);
            if (!this.#days.includes(day)) {
                day = 'a' + String(nonday).padStart(2, '0');
                nonday++;
            }
            if (!this.#upgrade_select[day])
                this.#upgrade_select[day] = ''
            this.#upgrade_select[day] += '<option value="' + match[row]['id'] + '">' + match[row]['label'] + ", $" + match[row]['price'] + ' (' + match[row]['enddate'] + ')' + "</option>\n";
        }

        // cart is only place to use yearahead_select, so build it.
        filt_cat = new Array('yearahead')
        filt_shortname_regexp = null;
        filt_conid = [Number(conid) + 1];
        match = memList.filter(mem_filter);
        this.#yearahead_select = '';
        this.#yearahead_selectlist = [];
        for (row in match) {
            var option = '<option value="' + match[row]['id'] + '">' + match[row]['label'] + ", $" + match[row]['price'] +
                ' (' + match[row]['enddate'] + ':' + match[row]['id'] + ')' + "</option>\n";
            this.#yearahead_select += option;
            this.#yearahead_selectlist.push({price: match[row]['price'], option: option});
        }

        // cart is only place to use addon_select, so build it
        filt_cat = ['addon', 'add-on'];
        filt_conid = [Number(conid)];
        filt_shortname_regexp = null;
        match = memList.filter(mem_filter);
        this.#addon_select = '';
        for (row in match) {
            this.#addon_select += '<option value="' + match[row]['id'] + '">' + match[row]['label'] + ", $" + match[row]['price'] +
                ' (' + match[row]['enddate'] + ':' + match[row]['id'] + ')' + "</option>\n";
        }

        this.drawCart();
    }

    // simple get/set/hide/show methods
    setInReview() {
        this.#in_review = true;
    }

    clearInReview() {
        this.#in_review = false;
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

    hideNoChanges() {
        this.#nochanges_button.hidden = true;
    }

    showNoChanges() {
        this.#nochanges_button.hidden = false;
    }

    hideVoid() {
        this.#void_button.hidden = true;
    }

    showVoid() {
        this.#void_button.hidden = false;
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
        return this.#cart_perinfo.length;
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
    notinCart(perid) {
        return this.#cart_perinfo_map.isSet(perid) === false;
    }

    // notes fields in the cart, get current values and set new values, marking dirty for saving records

    getFullName(index) {
        return this.#cart_perinfo[index]['fullname'];
    }

    getRegFullName(index) {
        return this.#cart_perinfo[this.#cart_membership[index]['pindex']]['fullname'];
    }

    getRegLabel(index) {
        return this.#cart_membership[index]['label'];
    }
    getRegNote(index) {
        return this.#cart_membership[index]['reg_notes'];
    }

    getNewRegNote(index) {
        return this.#cart_membership[index]['new_reg_note'];
    }
    setRegNote(index, note) {
        this.#cart_membership[index]['new_reg_note'] = note;
        var pindex = this.#cart_membership[index]['pindex'];
        this.#cart_perinfo[pindex]['dirty'] = true;
        this.drawCart();
    }

    getPerinfoNote(index) {
        return this.#cart_perinfo[index]['open_notes'];
    }

    setPersonNote(index, note) {
        this.#cart_perinfo[index]['open_notes'] = note;
        this.#cart_perinfo[index]['dirty'] = true;
        this.#cart_perinfo[index]['open_notes_pending'] = 1;
        this.drawCart();
    }

    // make a copy of private structures for use in ajax calls back to the PHP.   The master copies are only accessible within the class.
    getCartPerinfo() {
        return make_copy(this.#cart_perinfo);
    }

    getCartMembership() {
        return make_copy(this.#cart_membership);
    }

    getCartMembershipRef() {
        return this.#cart_membership;
    }

    getCartMap() {
        return this.#cart_perinfo_map.getMap();
    }

    getCartPmt() {
        return make_copy(this.#cart_pmt);
    }

    allowAddCouponToCart() {
        var anyUnpaid = false;
        for (var rownum in this.#cart_membership) {
            var mbrrow = this.#cart_membership[rownum];
            if (mbrrow['coupon'])
                return false;
            if ((!non_primary_categories.includes(mbrrow['memCategory'])) && mbrrow['conid'] == conid && mbrrow['price'] > 0 && mbrrow['paid'] != mbrrow['price'])
                anyUnpaid = true;
        }
        if (anyUnpaid == false)
            return false;

        return true;
    }

    getPriorDiscount() {
        var priordiscount = 0;
        for (var rownum in this.#cart_membership) {
            var mrow = this.#cart_membership[rownum];
            if (mrow['couponDiscount']) {
                priordiscount += Number(mrow['couponDiscount']);
            }
        }

        return priordiscount;
    }

// if no memberships or payments have been added to the database, this will reset for the next customer
// TODO: verify how to tell if it's allowed to be shown as enabled
    startOver() {
        // empty cart
        this.#cart_membership = [];
        this.#cart_perinfo = [];
        this.#cart_pmt = [];
        this.#freeze_cart = false;

        this.hideNext();
        this.hideVoid();
        this.#in_review = false;
        this.drawCart();
    }

    // add search result_perinfo/membership record to the cart
    add(p, mrows) {
        var pindex = this.#cart_perinfo.length;
        this.#cart_perinfo.push(make_copy(p));
        this.#cart_perinfo[pindex]['index'] = pindex;
        for (var mrownum in mrows) {
            var mindex = this.#cart_membership.length;
            this.#cart_membership.push(make_copy(mrows[mrownum]));
            this.#cart_membership[mindex]['pindex'] = pindex;
            if (this.#cart_membership[mindex]['couponDiscount'] === undefined)
                this.#cart_membership[mindex]['couponDiscount'] = 0.00;
            if (this.#cart_membership[mindex]['couponDiscount'] === undefined)
                this.#cart_membership[mindex]['coupon'] = null;
        }
        this.drawCart();
    }

// remove person and all of their memberships from the cart
    remove(perid) {
        if (!confirm_discard_add_edit(false))
            return;

        var index = this.#cart_perinfo_map.get(perid);

        if (!this.confirmDiscardCartEntry(index, false))
            return;

        var mrows = find_memberships_by_perid(this.#cart_membership, perid);
        // need to splice backwards so the indices don't change
        var delrows = [];
        var splicerow = null;
        for (var mrownum in mrows) {
            splicerow = mrows[mrownum]['index'];
            delrows.push(Number(splicerow));
        }
        delrows = delrows.reverse();
        for (splicerow in delrows)
            this.#cart_membership.splice(delrows[splicerow], 1);

        this.#cart_perinfo.splice(index, 1);
        // splices loses me the index number for the cross-reference, so the cart needs renumbering
        this.drawCart();
    }

    // get into the add/edit fields the requested cart entry
    getAddEditFields(perid) {
        var cartrow = this.#cart_perinfo[this.#cart_perinfo_map.get(perid)];

        // set perinfo values
        add_index_field.value = cartrow['index'];
        add_perid_field.value = cartrow['perid'];
        add_memIndex_field.value = '';
        add_first_field.value = cartrow['first_name'];
        add_middle_field.value = cartrow['middle_name'];
        add_last_field.value = cartrow['last_name'];
        add_suffix_field.value = cartrow['suffix'];
        add_legalName_field.value = cartrow['legalName'];
        add_addr1_field.value = cartrow['address_1'];
        add_addr2_field.value = cartrow['address_2'];
        add_city_field.value = cartrow['city'];
        add_state_field.value = cartrow['state'];
        add_postal_code_field.value = cartrow['postal_code'];
        add_country_field.value = cartrow['country'];
        add_email_field.value = cartrow['email_addr'];
        add_phone_field.value = cartrow['phone'];
        add_badgename_field.value = cartrow['badge_name'];
        add_contact_field.value = cartrow['contact_ok'];
        add_share_field.value = cartrow['share_reg_ok'];

        // membership items - see if there is a membership item in the member list for this row
        var mem_index = find_primary_membership_by_perid(this.#cart_membership, cartrow['perid']);

        if (mem_index == null) {
            // none found put in select
            add_mem_select.innerHTML = add_mt_dataentry;
            document.getElementById("ae_mem_sel").innerHTML = this.#membership_select;
        } else {
            add_memIndex_field.value = mem_index;
            if (Number(this.#cart_membership[mem_index]['price']) == Number(this.#cart_membership[mem_index]['paid'])) {
                // already paid, just display the label
                add_mem_select.innerHTML = this.#cart_membership[mem_index]['label'];
            } else {
                add_mem_select.innerHTML = add_mt_dataentry;
                var mtel = document.getElementById("ae_mem_sel");
                mtel.innerHTML = this.#membership_select;
                mtel.value = this.#cart_membership[mem_index]['memId'];
            }
        }
    }

    // update the cart entry from the add/edit field row
    updateEntry(edit_index, new_memindex, row, mrow) {
        var cart_row = this.#cart_perinfo[edit_index];

        cart_row['first_name'] = row['first_name'];
        cart_row['middle_name'] = row['middle_name'];
        cart_row['last_name'] = row['last_name'];
        cart_row['suffix'] = row['suffix'];
        cart_row['legalName'] = row['legalName'];
        cart_row['badge_name'] = row['badge_name'];
        cart_row['address_1'] = row['address_1'];
        cart_row['address_2'] = row['address_2'];
        cart_row['city'] = row['city'];
        cart_row['state'] = row['state'];
        cart_row['postal_code'] = row['postal_code'];
        cart_row['country'] = row['country'];
        cart_row['email_addr'] = row['email_addr'];
        cart_row['phone'] = row['phone'];
        cart_row['share_reg_ok'] = row['share_reg_ok'];
        cart_row['contact_ok'] = row['contact_ok'];
        cart_row['share_reg_ok'] = row['share_reg_ok'];
        cart_row['active'] = 'Y';
        cart_row['dirty'] = true;

        if (mrow != null) {
            var cart_mrow = [];
            if (new_memindex != '') {
                cart_mrow = this.#cart_membership[new_memindex];
            } else {
                var ind = this.#cart_membership.length;
                this.#cart_membership.push({index: ind, printcount: 0, tid: 0});
                cart_mrow = this.#cart_membership[ind];
            }
            for (var field in mrow) {
                cart_mrow[field] = mrow[field];
            }
            if (!('paid' in cart_mrow)) {
                cart_mrow['paid'] = 0;
                cart_mrow['priorPaid'] = 0;
            }
            if (!('couponDiscount' in cart_mrow)) {
                cart_mrow['couponDiscount'] = 0;
                cart_mrow['coupon'] = null;
            }
            if (!('tid' in cart_mrow)) {
                cart_mrow['tid'] = '';
            }
        }
    }

    // check to see if the cart is not saved, and confirm leaving without saving it
    confirmDiscardCartEntry(index, silent) {
        if (this.isFrozen()) {
            return true;
        }

        var dirty = false;
        if (index >= 0) {
            dirty = this.#cart_perinfo[index]['dirty'] === true;
        } else {
            for (var row in this.#cart_perinfo) {
                dirty ||= this.#cart_perinfo[row]['dirty'] === true;
            }
        }

        if (!dirty)
            return true;

        if (silent)
            return false;

        var msg = "Discard updated cart items?";
        if (index >= 0)
            msg = "Discard updated cart items for " + (this.#cart_perinfo[index]['first_name'] + ' ' + this.#cart_perinfo[index]['last_name']).trim();

        if (!confirm(msg)) {
            return false; // confirm answered no, return not safe to discard
        }

        return true;
    }

// remove single membership item from the cart (leaving other memberships and person information
    deleteMembership(index) {
        if (this.#cart_membership[index]['tid'] != '') {
            if (confirm("Confirm delete for " + this.#cart_membership[index]['label'])) {
                this.#cart_membership[index]['todelete'] = 1;
                this.#cart_perinfo[this.#cart_membership[index]['pindex']]['dirty'] = true;
            }
        } else {
            this.#cart_membership.splice(index, 1);
        }
        this.drawCart();
    }

    // add selected membership as a new item in the card under this perid.
    addMembership(rownum, membership) {
        var row = this.#cart_perinfo[rownum];

        this.#cart_membership.push({
            perid: row['perid'],
            price: membership['price'],
            couponDiscount: 0,
            paid: 0,
            tid: 0,
            index: this.#cart_membership.length,
            printcount: 0,
            conid: membership['conid'],
            memCategory: membership['memCategory'],
            memType: membership['memType'],
            memAge: membership['memAge'],
            shortname: membership['shortname'],
            pindex: row['index'],
            memId: membership['id'],
            label: membership['label'],
            regid: -1,
            coupon: null,
        });

        cart.drawCart();
    }

// change single membership item from the cart - only allow items of the same class with higher prices
    changeMembership(index) {
        this.#changeRow = index;
        var mrow = this.#cart_membership[index];
        var prow = this.#cart_perinfo[mrow['pindex']];

        var html = '<div id="ChangePrior">Current Membership ' + mrow['label'] + "</div>\n";
        html += '<div id="ChangeTo">Change to:<br/><select name="change_membership_id" id="change_membership_id">' + "\n";
        // build select list here
        var optionrows = this.#membership_selectlist;
        if (mrow['memCategory'] == 'yearahead' && mrow['conid'] != conid)
            optionrows = this.#yearahead_selectlist;
        var price = mrow['price'];
        for (var row in optionrows) {
            if (optionrows[row]['price'] >= price || Manager)
                html += optionrows[row]['option'];
        }

        html += "</select></div>\n";
        changeModal.show();
        document.getElementById("ChangeTitle").innerHTML = "Change Membership Type for " + (prow['first_name'] + ' ' + prow['last_name']).trim();
        document.getElementById("ChangeBody").innerHTML = html;
    }

// save_membership_change
// update saved cart row with new memId
    saveMembershipChange() {
        if (this.#changeRow == null)
            return;

        var mrow = this.#cart_membership[this.#changeRow];
        var newMemid = document.getElementById("change_membership_id").value;
        mrow['memId'] = newMemid;

        var mi_row = find_memLabel(newMemid);
        mrow['memCategory'] = mi_row['memCategory'];
        mrow['memType'] = mi_row['memType'];
        mrow['memAge'] = mi_row['memAge'];
        mrow['shortname'] = mi_row['shortname'];
        mrow['label'] = mi_row['label'];
        mrow['price'] = mi_row['price'];
        this.#cart_perinfo[mrow['pindex']]['dirty'] = true;

        this.#changeRow = null;
        changeModal.hide();
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
        if (data['cart_membership']) {
            this.#cart_membership = make_copy(data['cart_membership']);
        }
    }

// cart_renumber:
// rebuild the indices in the cart_perinfo and cart_membership tables
// for shortcut reasons indices are used to allow usage of the filter functions built into javascript
// this rebuilds the index and perinfo cross-reference maps.  It needs to be called whenever the number of items in cart is changed.
    #cart_renumber() {
        var index;
        this.#cart_perinfo_map = new map();
        for (index = 0; index < this.#cart_perinfo.length; index++) {
            this.#cart_perinfo[index]['index'] = index;
            this.#cart_perinfo_map.set(this.#cart_perinfo[index]['perid'], index);
        }

        for (index = 0; index < this.#cart_membership.length; index++) {
            this.#cart_membership[index]['index'] = index;
            this.#cart_membership[index]['pindex'] = this.#cart_perinfo_map.get(this.#cart_membership[index]['perid']);
        }
    }

    // Clear the coupon matching couponId from all rows in the cart
    clearCoupon(couponId) {
        // clear the discount from the membership rows
        for (var rownum in this.#membership_rows ) {
            var mrow = this.#membership_rows[rownum];
            if (mrow['coupon'] == couponId) {
                mrow['coupon'] = null;
                mrow['couponDiscount'] = 0;
            }
        }
        // remove the discount coupon from the payment
        var delrows = [];
        for (rownum in this.#cart_pmt) {
            var prow = this.#cart_pmt[rownum];
            if (prow['type'] == 'discount' && prow['desc'].substring(0, 7) == 'Coupon:') {
                delrows.push(rownum);
            }
        }
        // now delete the matching rows (in reverse order)
        delrows = delrows.reverse();
        for (rownum in delrows)
            this.#cart_pmt.splice(delrows[rownum], 1);
    }

// format all of the memberships for one record in the cart
    #drawCartRow(rownum) {
        var row = this.#cart_perinfo[rownum];
        var membername = ((row['first_name'] + ' ' + row['middle_name']).trim() + ' ' + row['last_name'] + ' ' + row['suffix']).trim();
        var mrow;
        var rowlabel;
        var membership_found = false;
        var mem_is_membership = false;
        var membership_html = '';
        var rollover_html = '';
        var upgrade_html = '';
        var yearahead_html = '';
        var addon_html = '';
        var yearahead_eligible = false;
        var upgrade_eligible = false;
        var day = null;
        var col1 = '';
        var perid = row['perid'];
        var btncolor = null;
        // now loop over the memberships, sorting them by groups
        var mrows = find_memberships_by_perid(this.#cart_membership, perid);
        for (var mrownum in mrows) {
            mrow = mrows[mrownum];
            if (mrow['todelete'] !== undefined)
                continue;

            var row_shown = true;
            var category = mrow['memCategory'];
            if (category == 'yearahead' && mrow['conid'] == conid)
                category = 'standard'; // last years yearahead is this year's standard
            var memType = mrow['memType'];
            mem_is_membership = false;
            // col1 choices
            //  X = delete element from cart
            var allow_delete = mrow['regid'] <= 0;
            var allow_delete_priv = mrow['paid'] == 0 && mrow['printcount'] == 0;
            var allow_change_priv = mrow['regid'] > 0 && mrow['paid'] >= 0 && mrow['printcount'] == 0 &&
                (category == 'standard' || category == 'yearahead') && memType == 'full';
            col1 = '';
            if ((allow_delete || allow_delete_priv) && !this.#freeze_cart) {
                col1 += '<button type = "button" class="btn btn-sm btn-secondary pt-0 pb-0 ps-1 pe-1 m-0" onclick = "delete_membership(' +
                    mrow['index'] + ')" >X</button >';
            }
            // C = change membership type
            if (allow_change_priv && !this.#freeze_cart) {
                col1 += '<button type = "button" class="btn btn-sm btn-warning pt-0 pb-0 ps-1 pe-1 m-0" onclick = "change_membership(' +
                    mrow['index'] + ')" >C</button >';
            }

            var label = mrow['label'];
            if (!this.#freeze_cart) {
                var notes_count = 0;
                if (mrow['reg_notes_count'] !== undefined && mrow['reg_notes_count'] !== null) {
                    notes_count = Number(mrow['reg_notes_count']);
                }
                btncolor = 'btn-info';
                if (mrow['new_reg_note'] !== undefined && mrow['new_reg_note'] !== '')
                    btncolor = 'btn-warning';
                var btntext = 'Add Note';
                if (notes_count > 0) {
                    btntext = 'Notes:' + notes_count.toString();
                }
                label += ' <button type = "button" class="btn btn-sm ' + btncolor + ' pt-0 pb-0 ps-1 pe-1 m-0" onclick = " +show_reg_note(' +
                    mrow['index'] + ', ' + notes_count + ')" style=" --bs-btn-font-size:75%;">' + btntext + '</button >';
            }

            if ((!non_primary_categories.includes(category)) && mrow['conid'] == conid) { // this is the current year membership
                if (upgradable_types.includes(mrow['memType'])) {
                    upgrade_eligible = true;
                    if (mrow['memType'] == 'oneday' || mrow['memType'] == 'one-day') {
                        day = (mrow['label']).toLowerCase().substring(0, 3);
                    }
                }
                mem_is_membership = mrow['memCategory'] != 'cancel';
                yearahead_eligible = true;
                if (mrow['memCategory'] == 'upgrade') {
                    upgrade_html += `
    <div class="row">
        <div class="col-sm-1 p-0">` + col1 + `</div>
        <div class="col-sm-7 p-0">` + label + `</div>
        <div class="col-sm-2 text-end">` + Number(mrow['price']).toFixed(2) + `</div>
        <div class="col-sm-2 text-end">` + Number(mrow['paid']).toFixed(2) + `</div>
    </div>
`;
                } else {
                    membership_html += `
    <div class="row">
        <div class="col-sm-1 p-0">` + col1 + `</div>
        <div class="col-sm-7 p-0">` + label + `</div>
        <div class="col-sm-2 text-end">` + Number(mrow['price']).toFixed(2) + `</div>
        <div class="col-sm-2 text-end">` + Number(mrow['paid']).toFixed(2) + `</div>
    </div>
`;
                }
            } else {
                switch (category) {
                    case 'upgrade':
                        mem_is_membership = true;
                        yearahead_eligible = true;
                        upgrade_eligible = false;
                        day = null;
                        upgrade_html += `
    <div class="row">
        <div class="col-sm-1 p-0">` + col1 + `</div>
        <div class="col-sm-7 p-0">` + label + `</div>
        <div class="col-sm-2 text-end">` + Number(mrow['price']).toFixed(2) + `</div>
        <div class="col-sm-2 text-end">` + Number(mrow['paid']).toFixed(2) + `</div>
    </div>
    `;
                        break;
                    case 'yearahead':
                        yearahead_html += `
    <div class="row">
        <div class="col-sm-1 p-0">` + col1 + `</div>
        <div class="col-sm-7 p-0">` + label + `</div>
        <div class="col-sm-2 text-end">` + Number(mrow['price']).toFixed(2) + `</div>
        <div class="col-sm-2 text-end">` + Number(mrow['paid']).toFixed(2) + `</div>
    </div>
    `;
                        break;
                    case 'rollover': // can't get here if current con id
                        yearahead_eligible = false;
                        yearahead_html += `
    <div class="row">
        <div class="col-sm-1 p-0">` + col1 + `</div>
        <div class="col-sm-7 p-0">` + label + `</div>
        <div class="col-sm-2 text-end">` + Number(mrow['price']).toFixed(2) + `</div>
        <div class="col-sm-2 text-end">` + Number(mrow['paid']).toFixed(2) + `</div>
    </div>
    `;
                        break;
                    case 'addon':
                    case 'add-on':
                        rowlabel = 'Addon:';
                        addon_html += `
    <div class="row">
        <div class="col-sm-1 p-0">` + col1 + `</div>
        <div class="col-sm-7 p-0">` + label + `</div>
        <div class="col-sm-2 text-end">` + Number(mrow['price']).toFixed(2) + `</div>
        <div class="col-sm-2 text-end">` + Number(mrow['paid']).toFixed(2) + `</div>
    </div>
    `;
                        break;

                    default:
                        row_shown = false;
                }
            }

            if (row_shown) {
                this.#total_price += Number(mrow['price']);
                this.#total_paid += Number(mrow['paid']);
                if (mrow['couponDiscount'])
                    this.#total_paid += Number(mrow['couponDiscount']);
                if (mem_is_membership)
                    membership_found = true;
                if ((Number(mrow['paid']) + Number(mrow['couponDiscount'])) != Number(mrow['price'])) {
                    this.#unpaid_rows++;
                }
            }
        }
        // first row - member name, remove button
        var rowhtml = '<div class="row">';
        if (membership_found) {
            rowhtml += '<div class="col-sm-8 text-bg-success">Member: '
        } else {
            rowhtml += '<div class="col-sm-8 text-bg-info">Non Member: '
        }
        rowhtml += membername + '</div>';
        if (!this.#freeze_cart) {
            rowhtml += `
        <div class="col-sm-2 p-0 text-center"><button type="button" class="btn btn-sm btn-secondary pt-0 pb-0 ps-1 pe-1" onclick="edit_from_cart(` + perid + `)">Edit</button></div>
        <div class="col-sm-2 p-0 text-center"><button type="button" class="btn btn-sm btn-secondary pt-0 pb-0 ps-1 pe-1" onclick="remove_from_cart(` + perid + `)">Remove</button></div>
`;
        }
        rowhtml += '</div>'; // end of member name row

        // second row - badge name
        rowhtml += `
    <div class="row">
        <div class="col-sm-3 p-0">Badge Name:</div>
        <div class="col-sm-5 p-0">` + badge_name_default(row['badge_name'], row['first_name'], row['last_name']) + `</div>
        <div class="col-sm-2 p-0 text-center">`;
        if (!this.#freeze_cart && row['open_notes'] != null && row['open_notes'].length > 0) {
            rowhtml += '<button type="button" class="btn btn-sm btn-info p-0" onclick="show_perinfo_notes(' + row['index'] + ', \'cart\')">View Notes</button>';
        }
        rowhtml += `</div>
        <div class="col-sm-2 p-0 text-center">`;
        if (Manager && !this.#freeze_cart) {
            btncolor = 'btn-secondary';
            if (row['open_notes_pending'] !== undefined && row['open_notes_pending'] === 1)
                btncolor = 'btn-warning';
            rowhtml += '<button type="button" class="btn btn-sm ' + btncolor + ' p-0" onclick="edit_perinfo_notes(' + row['index'] + ', \'cart\')">Edit Notes</button>';
        }
        rowhtml += `</div>
    </div>
`;  // end of second row - badge name

        if (rollover_html != '') {
            rowhtml += `<div class="row">
            <div class="col-sm-auto p-0">Rollover:</div>
</div>
` + rollover_html;
        }
        // reg items:
        //
        // membership rows

        if (rollover_html == '' || membership_html != '') {
            rowhtml += `<div class="row">
        <div class="col-sm-auto p-0">Memberships:</div>
</div>
`;
        }

        if (membership_html != '') {
            rowhtml += membership_html;
        }

        // if no base membership, create a pulldown row for it.
        // header row already output above before membership html was output
        if (!membership_found && !this.#freeze_cart) {
            rowhtml += `<div class="row">
        <div class="col-sm-1 p-0">&nbsp;</div>
        <div class="col-sm-9 p-0"><select id="cart-madd-` + rownum + `" name="cart-addid">
` + this.#membership_select + `
            </select>
        </div>
        <div class="col-sm-2 p-0 text-center"><button type="button" class="btn btn-sm btn-info pt-0 pb-0 ps-1 pe-1" onclick="add_membership_cart(` + rownum + ", 'cart-madd-" + rownum + `')">Add</button>
        </div>
    </div>`;
        }

        // add in remainder of cart:
        if (upgrade_html != '') {
            rowhtml += `<div class="row">
            <div class="col-sm-auto p-0">Upgrade:</div>
</div>
` + upgrade_html;
        } else if (upgrade_eligible && !this.#freeze_cart) {
            rowhtml += `<div class="row">
            <div class="col-sm-auto p-0">Upgrade:</div>
</div>
<div class="row">
        <div class="col-sm-1 p-0">&nbsp;</div>
        <div class="col-sm-9 p-0"><select id="cart-mupg-` + rownum + `" name="cart-addid">
`;
            // allow for mismatches to show the entire select, if matched, just use that one
            if (day !== null && this.#upgrade_select[day] !== undefined) {
                rowhtml += this.#upgrade_select[day];
            } else {
                for (var upgrow in this.#upgrade_select) {
                    rowhtml += this.#upgrade_select[upgrow];
                }
            }
            rowhtml += `
            </select>
        </div>
        <div class="col-sm-2 p-0 text-center"><button type="button" class="btn btn-sm btn-secondary pt-0 pb-0 ps-1 pe-1" onclick="add_membership_cart(` + rownum + ", 'cart-mupg-" + rownum + `')">Add</button></div >
</div>
`;
        }

        if (this.#yearahead_select != '') {
            if (yearahead_html != '') {
                rowhtml += `<div class="row">
            <div class="col-sm-auto p-0">Next Year:</div>
</div>
` + yearahead_html;
            } else if (yearahead_eligible && !this.#freeze_cart) {
                rowhtml += `<div class="row">
            <div class="col-sm-auto p-0">Next Year:</div>
</div>
<div class="row">
        <div class="col-sm-1 p-0">&nbsp;</div>
        <div class="col-sm-9 p-0"><select id="cart-mya-` + rownum + `" name="cart-addid">
` + this.#yearahead_select + `
            </select>
        </div>
        <div class="col-sm-2 p-0 text-center"><button type="button" class="btn btn-sm btn-secondary pt-0 pb-0 ps-1 pe-1" onclick="add_membership_cart(` + rownum + ", 'cart-mya-" + rownum + `')">Add</button></div >
</div>
`;
            }
        }

        if (this.#addon_select != '') {
            if (addon_html != '' || !this.#freeze_cart) {
                rowhtml += `<div class="row">
            <div class="col-sm-auto p-0">Add Ons:</div>
</div>
` + addon_html;
            }
            if (!this.#freeze_cart) {
                rowhtml += `
<div class="row">
        <div class="col-sm-1 p-0">&nbsp;</div>
        <div class="col-sm-9 p-0"><select id="cart-maddon-` + rownum + `" name="cart-addid">
` + this.#addon_select + `
            </select>
        </div>
        <div class="col-sm-2 p-0 text-center"><button type="button" class="btn btn-sm btn-secondary pt-0 pb-0 ps-1 pe-1" onclick="add_membership_cart(` + rownum + ", 'cart-maddon-" + rownum + `')">Add</button></div >
</div>
`;
            }
        }

        if (membership_found)
            this.#membership_rows++
        else
            this.#needmembership_rows++;

        return rowhtml;
    }

// draw a payment row in the cart
    #drawCartPmtRow(prow) {
        //   index: cart_pmt.length, amt: pay_amt, ccauth: ccauth, checkno: checkno, desc: eldesc.value, type: ptype,

        var pmt = this.#cart_pmt[prow];
        var code = '';
        if (pmt['type'] == 'check') {
            code = pmt['checkno'];
        } else if (pmt['type'] == 'credit') {
            code = pmt['ccauth'];
        }
        return `<div class="row">
    <div class="col-sm-2 p-0">` + pmt['type'] + `</div>
    <div class="col-sm-6 p-0">` + pmt['desc'] + `</div>
    <div class="col-sm-2 p-0">` + code + `</div>
    <div class="col-sm-2 text-end">` + Number(pmt['amt']).toFixed(2) + `</div>
</div>
`;
    }

// draw/update by redrawing the entire cart
    drawCart() {
        this.#cart_renumber(); // to keep indexing intact, renumber the index and pindex each time
        this.#total_price = 0;
        this.#total_paid = 0;
        var num_rows = 0;
        this.#membership_rows = 0;
        this.#needmembership_rows = 0;
        var html = `
<div class="container-fluid">
<div class="row">
    <div class="col-sm-8 text-bg-primary">Member</div>
    <div class="col-sm-2 text-bg-primary text-end">Price</div>
    <div class="col-sm-2 text-bg-primary text-end">Paid</div>
</div>
`;
        this.#unpaid_rows = 0;
        for (var rownum in this.#cart_perinfo) {
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
    <div class="col-sm-8 text-bg-primary">Payment</div>
    <div class="col-sm-2 text-bg-primary">Code</div>
    <div class="col-sm-2 text-bg-primary text-end">Amount</div>
</div>
`;
            this.#total_pmt = 0;
            for (var prow in this.#cart_pmt) {
                html += this.#drawCartPmtRow(prow);
                this.#total_pmt += Number(this.#cart_pmt[prow]['amt']);
            }
            html += `<div class="row">
    <div class="col-sm-8 p-0 text-end">Payment Total:</div>`;
            this.#total_pmt = Number(this.#total_pmt.toFixed(2));
            html += `
    <div class="col-sm-4 text-end">$` + Number(this.#total_pmt).toFixed(2) + `</div>
</div>
`;
        }
        if (this.#needmembership_rows > 0) {
            var person = this.#needmembership_rows > 1 ? " people" : " person";
            var need = this.#needmembership_rows > 1 ? "need memberships" : "needs a membership";
            html += `<div class="row mt-3">
    <div class="col-sm-12">Cannot proceed to "Review" because ` + this.#needmembership_rows + person + " still " + need + `.  Use "Edit" button to add memberships for them or "Remove" button to take them out of the cart.
    </div>
`;
        } else if (num_rows > 0) {
            this.#review_button.hidden = this.#in_review;
        }
        html += '</div>'; // ending the container fluid
        //console.log(html);
        this.#cart_div.innerHTML = html;
        this.#startover_button.hidden = num_rows == 0;
        if (this.#needmembership_rows > 0 || (this.#membership_rows == 0 && this.#unpaid_rows == 0)) {
            review_tab.disabled = true;
            this.#review_button.hidden = true;
        }
        if (this.#freeze_cart) {
            review_tab.disabled = true;
            this.#review_button.hidden = true;
            this.hideStartOver();
        }
        find_unpaid_button.hidden = num_rows > 0;
    }

    // create the HTML of the cart into the review data block
    buildReviewData() {
        review_missing_items = 0;
        var html = `
<div id='reviewBody' class="container-fluid form-floating">
  <form id='reviewForm' action='javascript: return false; ' class="form-floating">
`;
        var rownum = null;
        var row;
        var colors = new map();
        var fieldno;
        var mrow;
        var field;
        var tabindex = 0;
        for (rownum in this.#cart_perinfo) {
            tabindex += 100;
            row = this.#cart_perinfo[rownum];
            mrow = find_primary_membership_by_perid(this.#cart_membership, row['perid']);
            // look up missing fields
            colors = new map();
            for (fieldno in review_required_fields) {
                field = review_required_fields[fieldno];
                if (row[field] == null || row[field] == '') {
                    review_missing_items++;
                    colors.set(field, 'var(--bs-warning)');
                } else {
                    colors.set(field, '');
                }
            }
            for (fieldno in review_prompt_fields) {
                field = review_prompt_fields[fieldno];
                if (row[field] == null || row[field] == '') {
                    if (this.#cart_membership[mrow]['memAge'] == 'child' || this.#cart_membership[mrow]['memAge'] == 'kit') {
                        review_missing_items++;
                        colors.set(field, 'var(--bs-warning)');
                    } else {
                        colors.set(field, 'var(--bs-info)');
                    }
                } else {
                    colors.set(field, '');
                }
            }
            html += '<div class="row">';
            if (mrow == null) {
                html += '<div class="col-sm-12 text-bg-info">No Membership</div>';
            } else {
                html += '<div class="col-sm-12 text-bg-success">Membership: ' + this.#cart_membership[mrow]['label'] + '</div>';
            }

            html += `
    </div>
    <input type="hidden" id='c` + rownum + `-index' value="` + row['index'] + `"/>
    <div class="row mt-1">
        <div class="col-sm-auto ms-0 me-2 p-0">
            <input type="text" name="c` + rownum + `-first_name" id='c` + rownum + `-first_name' size="25" maxlength="32" placeholder="First Name" tabindex="` + String(tabindex + 2) +
                '" value="' + row['first_name'] + '" style="background-color:' + colors.get('first_name') + ';' +
                `"/>
        </div>
        <div class="col-sm-auto ms-0 me-2 p-0">
            <input type="text" name="c` + rownum + `-middle_name" id='c` + rownum + `-middle_name' size="6" maxlength="32" placeholder="Middle" tabindex="` + String(tabindex + 4) +
                '" value="' + row['middle_name'] + `"/>
        </div>
        <div class="col-sm-auto ms-0 me-2 p-0">
            <input type="text" name="c` + rownum + `-last_name" id='c` + rownum + `-last_name' size="25" maxlength="32" placeholder="Last Name" tabindex="` + String(tabindex + 6) +
                '" value="' + row['last_name'] + '" style="background-color:' + colors.get('last_name') + ';' + `"/>
        </div>
        <div class="col-sm-auto ms-0 me-0 p-0">
            <input type="text" name="c` + rownum + `-suffix" id='c` + rownum + `-suffix' size="6" maxlength="4" placeholder="Suffix" tabindex="` + String(tabindex + 8) +
                '" value="' + row['suffix'] + `"/>
        </div>
    </div>
    <div class="row">
        <div class="col-sm-auto ms-0 me-0 p-0">
            <input type="text" name='c` + rownum + `-legalName' id='c` + rownum + `-legalName' size=80 maxlength="128" placeholder="Legal Name: defaults to first middle last suffix" tabindex="` +
                String(tabindex + 10) +  '" value="' + row['legalName'] + `"/>
        </div>
    </div>
    <div class="row">
        <div class="col-sm-auto ms-0 me-0 p-0">
            <input type="text" name='c` + rownum + `-badge_name' id='c` + rownum + `-badge_name' size=64 maxlength="64" placeholder="Badgename: defaults to first and last name" tabindex="` +
                String(tabindex + 12) +'" value="' + row['badge_name'] + `"/>
        </div>
    </div>
    <div class="row">
        <div class="col-sm-auto ms-0 me-2 p-0">
            <input type="text" name='c` + rownum + `-email_addr' id='c` + rownum + `-email_addr' size=64 maxlength="254" placeholder="Email Address" tabindex="` +
                String(tabindex + 14) + '"  value="' + row['email_addr'] + '" style="background-color:' + colors.get('email_addr') + ';' + `"/>
        </div>
         <div class="col-sm-auto ms-0 me-0 p-0">
            <input type="text" name='c` + rownum + `-phone' id='c` + rownum + `-phone' size=15 maxlength="15" placeholder="Phone Number" tabindex="` +
            String(tabindex + 16) + '" value="' + row['phone'] + '" style="background-color:' + colors.get('phone') + ';' + `"/>
        </div>
    </div>
    <div class="row">
        <div class="col-sm-auto ms-0 me-0 p-0">
            <input type="text" name='c` + rownum + `-address_1' id='c` + rownum + `-address_1' size=64 maxlength="64" placeholder="Street Address" tabindex="` +
                String(tabindex + 18) + '"  value="' + row['address_1'] + '" style="background-color:' + colors.get('address_1') + ';' + `"/>
        </div>
    </div>
    <div class="row">
        <div class="col-sm-auto ms-0 me-0 p-0">
            <input type="text" name='c` + rownum + `-address_2' id='c` + rownum + `-address_2' size=64 maxlength="64" placeholder="2nd line of Address (if needed, such as company)" tabindex="` +
                String(tabindex + 20) + '" value="' + row['address_2'] + `"/>
        </div>
    </div>
    <div class="row">
        <div class="col-sm-auto ms-0 me-2 p-0">
            <input type="text" name="c` + rownum + `-city" id='c` + rownum + `-city' size="22" maxlength="32" placeholder="City" tabindex="` + String(tabindex + 22) +
                '" value="' + row['city'] + '" style="background-color:' + colors.get('city') + ';' + `"/>
        </div>
        <div class="col-sm-auto ms-0 me-2 p-0">
            <input type="text" name="c` + rownum + `-state" id='c` + rownum + `-state' size="10" maxlength="16" placeholder="State" tabindex="` + String(tabindex + 24) +
                '" value="' + row['state'] + '" style="background-color:' + colors.get('state') + ';' + `"/>
        </div>
        <div class="col-sm-auto ms-0 me-2 p-0">
            <input type="text" name="c` + rownum + `-postal_code" id='c` + rownum + `-postal_code' size="10" maxlength="10" placeholder="Postal Code" tabindex="` + String(tabindex + 26) +
            '" value="' + row['postal_code'] + '" style="background-color:' + colors.get('postal_code') + ';' + `"/>
        </div>
        <div class="col-sm-auto ms-0 me-0 p-0">
            <select name='c` + rownum + `-country' id='c` + rownum + `-country' tabindex="` + String(tabindex + 28) + `">
                ` + country_select + `
            </select>
        </div>
    </div>
    <div class="row mb-4">
        <div class="col-sm-auto ms-0 me-2 p-0">Share Reg?</div>
        <div class="col-sm-auto ms-0 me-2 p-0">
            <select name='c` + rownum + `-share_reg_ok' id='c` + rownum + `-share_reg_ok' tabindex="` + String(tabindex + 30) + `">
               <option value="Y" ` + (row['share_reg_ok'] == 'Y' ? 'selected' : '') + `>Y</option>
               <option value="N" ` + (row['share_reg_ok'] == 'N' ? 'selected' : '') + `>N</option>
            </select>
        </div>
        <div class="col-sm-auto ms-0 me-2 p-0">Contact OK?</div>
        <div class="col-sm-auto ms-0 me-2 p-0">
            <select name='c` + rownum + `-contact_ok' id='c` + rownum + `-contact_ok' tabindex="` + String(tabindex + 32) + `">
                <option value="Y" ` + (row['contact_ok'] == 'Y' ? 'selected' : '') + `>Y</option>
                <option value="N" ` + (row['contact_ok'] == 'N' ? 'selected' : '') + `>N</option>
            </select>
        </div>
    </div>
`;
        }
        html += `
    <div class="row mt-2">
        <div class="col-sm-1 m-0 p-0">&nbsp;</div>
        <div class="col-sm-auto m-0 p-0">
            <button class="btn btn-primary btn-sm" type="button" id="review-btn-update" onclick="review_update();">Update All</button>
            <button class="btn btn-primary btn-sm" type="button" id="review-btn-nochanges" onclick="review_nochanges();">No Changes</button>
        </div>
    </div>
    <div class="row">
        <div class="col-sm-12" id="review_status"></div>
    </div>
  </form>
</div>
`
        return html;
    }

// update the cart from the review block
    updateReviewData() {
        // loop over cart looking for changes in data table
        var rownum = null;
        var el;
        var field;
        var fieldno
        for (rownum in this.#cart_perinfo) {
            // update all the fields on the review page
            for (fieldno in review_editable_fields) {
                field = review_editable_fields[fieldno];
                el = document.getElementById('c' + rownum + '-' + field);
                if (el) {
                    if (this.#cart_perinfo[rownum][field] != el.value) {
                        // alert("updating  row " + rownum + ":" + rownum + ":" + field + " from '" + this.#cart_perinfo[rownum][field] + "' to '" + el.value + "'");
                        this.#cart_perinfo[rownum][field] = el.value;
                        this.#cart_perinfo[rownum]['dirty'] = false;
                    }
                }
            }
        }
    }

// update the card with fields provided by the update of the database.  And since the DB is now updated, clear the dirty flags.
    updateFromDB(data) {
        var newrow;
        var cartrow;

        // update the fields created by the database transactions
        var updated_perinfo = data['updated_perinfo'];
        for (rownum in updated_perinfo) {
            newrow = updated_perinfo[rownum];
            cartrow = this.#cart_perinfo[newrow['rownum']]
            cartrow['perid'] = newrow['perid'];
            cartrow['dirty'] = false;
        }
        var updated_membership = data['updated_membership'];
        for (rownum in updated_membership) {
            newrow = updated_membership[rownum];
            cartrow = this.#cart_membership[newrow['rownum']];
            //array('rownum' => $row, 'perid' => $cartrow['perid'], 'create_trans' => $master_perid, 'id' => $new_regid);
            cartrow['create_trans'] = newrow['create_trans'];
            cartrow['regid'] = newrow['id'];
            cartrow['perid'] = newrow['perid'];
            cartrow['dirty'] = false;
        }

// delete all rows from cart marked for delete
        var delrows = [];
        var splicerow = null;
        for (var rownum in this.#cart_membership) {
            if (this.#cart_membership[rownum]['todelete'] == 1) {
                delrows.push(rownum);
            }
        }
        delrows = delrows.reverse();
        for (splicerow in delrows)
            this.#cart_membership.splice(delrows[splicerow], 1);

// redraw the cart with the new id's and maps, which will compute the unpaid_rows.
        cart.drawCart();
        return this.#unpaid_rows;
    }

    // update selected element in the country pulldown from the review data screen to the cart
    setCountrySelect() {
        var rownum;
        var row;
        var selid;

        for (rownum in this.#cart_perinfo) {
            row = this.#cart_perinfo[rownum];
            selid = document.getElementById('c' + rownum + '-country');
            selid.value = row['country'];
        }
        cart.drawCart();
    }

// receiptHeader - retrieve receipt header info from cart[0]
    receiptHeader(user_id, pay_tid) {
        var d = new Date();
        var payee = (this.#cart_perinfo[0]['first_name'] + ' ' + this.#cart_perinfo[0]['last_name']).trim();
        return "\nReceipt for payment to " + conlabel + "\nat " + d.toLocaleString() + "\nBy: " + payee + ", Cashier: " + user_id + ", Transaction: " + pay_tid;
    }

// printList - html to display cart elements to print
    printList(new_print) {
        var rownum;
        var crow;
        var mrow;
        var print_html = '';

        for (rownum in this.#cart_perinfo) {
            crow = this.#cart_perinfo[rownum];
            mrow = find_primary_membership_by_perid(this.#cart_membership, crow['perid']);
            if (new_print) {
                printed_obj.set(crow['index'], 0);
            }
            print_html += `
    <div class="row">
        <div class="col-sm-2 ms-0 me-2 p-0">
            <button class="btn btn-primary btn-sm" type="button" id="pay-print-` + this.#cart_perinfo[rownum]['index'] + `" name="print_btn" onclick="print_badge(` + crow['index'] + `);">Print</button>
        </div>
        <div class="col-sm-auto ms-0 me-2 p-0">            
            <span class="text-bg-success"> Membership: ` + this.#cart_membership[mrow]['label'] + `</span> (Times Printed: ` +
                this.#cart_membership[mrow]['printcount'] + `)<br/>
              ` + crow['badge_name'] + '/' + (crow['first_name'] + ' ' + crow['last_name']).trim() + `
        </div>
     </div>`;
        }
        return print_html;
    }

// getBadge = return the cart portions of the parameters for a badge print, that will be added to by the calling routine
    getBadge(index) {

        var row = this.#cart_perinfo[index];
        var mrow = find_primary_membership_by_perid(this.#cart_membership, row['perid']);
        var printrow = this.#cart_membership[mrow];

        var params = {};
        params['type'] = printrow['memType'];
        params['badge_name'] = row['badge_name'];
        params['full_name'] = (row['first_name'] + ' ' + row['last_name']).trim();
        params['category'] = printrow['memCategory'];
        params['badge_id'] = row['perid'];
        params['day'] = dayFromLabel(printrow['label']);
        params['age'] = printrow['memAge'];
        return params;
    }

    // addToPrintCount: increment the print count for a badge
    addToPrintCount(index) {
        var row = this.#cart_perinfo[index];
        var mrow = find_primary_membership_by_perid(this.#cart_membership, row['perid']);
        this.#cart_membership[mrow]['printcount']++;
        var retval = [];
        retval[0] = this.#cart_membership[mrow]['regid'];
        retval[1] = this.#cart_membership[mrow]['printcount'];
        return (retval);
    }

    // getEmail: return the email address of an entry
    getEmail(index) {
        return this.#cart_perinfo[index]['email_addr'];
    }
}
