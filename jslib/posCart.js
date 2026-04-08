// Point of Sale Cart Class - all functions and data related to the cart portion of the right side of the screen
// The cart manages: People, Memberships and Payment
// ConTroll Registration System
// Author: Syd Weinstein
class PosCart {
// cart dom items
    #startoverButton = null;
    #reviewButton = null;
    #nextButton = null;
    #nochangesButton = null;
    #cartDiv = null;

// cart states
    #inReview = false;
    #freezeCart = false;

// cart internals
    #totalPrice = 0;
    #totalPaid = 0;
    #totalPmt = 0;
    #totalCouponUnpaid = 0;
    #unpaidRows = 0;
    #membershipRows = 0;
    #needMembershipRows = 0;
    #cartPerinfo = [];
    #cartPerinfoMap = new map();
    #cartPmt = [];
    #cartIgnorePmtRound = false;

// Add Edit Memberships
    #addEditModal = null;
    #addEditBody = null;
    #addEditTitle = null;
    #addEditFullName = null;
    #addEditPerid = null;
    #membershipButtonsDiv = null;
    #memberAge = null;
    #currentAge = null;
    #currentPerid = null;
    #currentPerIdx = null;
    #memberships = [];
    #allMemberships = [];
    #cartContentsDiv = null;
    #cartChanges = 0;
    #newIDKey = -1;
    #newMembershipSave = null;
    #amountField = null;
    #vpModal = null;
    #vpBody = null;

// Review items
    #review_required_all = ['first_name', 'last_name', 'email_addr', 'address_1', 'city', 'state', 'postal_code'];
    #review_required_addr = ['first_name', 'email_addr'];
    #review_required_first = ['first_name', 'email_addr', 'address_1', 'city', 'state', 'postal_code'];
    #review_required_fields = this.#review_required_all;
    #review_prompt_fields = [ 'phone' ];
    #age_select = document.getElementById('age').innerHTML;
    #country_select = document.getElementById('country').innerHTML;

// Pay items
    #anyUnpaid = false;
    #priorPayments = null;

// Constants
    #isDueStatuses = [ 'unpaid', 'plan', 'in-cart' ];

// currency
    #locale = 'en-us';
    #currencyFmt = null;

// initialization
    constructor() {
        let id;

        this.#locale = config.locale;
        this.#currencyFmt = new Intl.NumberFormat(this.#locale, {
            style: 'currency',
            currency: config.currency,
        });
// lookup all DOM elements
// ask to load mapping tables
        this.#cartDiv = document.getElementById("cart");
        this.#startoverButton = document.getElementById("startover_btn");
        this.#reviewButton = document.getElementById("review_btn");
        this.#nextButton = document.getElementById("next_btn");
        this.#nochangesButton = document.getElementById("cart_no_changes_btn");

        // addEdit membership
        id = document.getElementById('addEdit');
        if (id) {
            this.#addEditModal = new bootstrap.Modal(id, {focus: true, backdrop: 'static'});
            this.#addEditBody = document.getElementById('addEditBody');
            this.#addEditTitle = document.getElementById('addEditTitle');
            this.#addEditFullName = document.getElementById('addEditFullName');
            this.#membershipButtonsDiv = document.getElementById('membershipButtons');
            this.#cartContentsDiv = document.getElementById('cartContentsDiv');
        }
        id = document.getElementById("variablePriceModal");
        if (id) {
            this.#vpModal = new bootstrap.Modal(id, {focus: true, backdrop: 'static'});
            id.addEventListener('hidden.bs.modal', amountModalHiddenHelper);
            this.#vpBody = document.getElementById("variablePriceBody");
        }

        switch (config.required) {
            case 'first':
                this.#review_required_fields = this.#review_required_first;
                break;
            case 'addr':
                this.#review_required_fields = this.#review_required_addr;
                break;
            default:
                this.#review_required_fields = this.#review_required_all;
                break;
        }
    }

    // simple get/set/hide/show methods
    setInReview() {
        this.#inReview = true;
    }

    clearInReview() {
        this.#inReview = false;
    }

    getAnyUnpaid() {
        return this.#anyUnpaid;
    }

    setAnyUnpaid() {
        this.#anyUnpaid = true;
    }

    freeze() {
        this.#freezeCart = true;
    }

    unfreeze() {
        this.#freezeCart = false;
    }

    isFrozen() {
        return this.#freezeCart == true;
    }

    hideNoChanges() {
        this.#nochangesButton.hidden = true;
    }

    showNoChanges() {
        this.#nochangesButton.hidden = false;
    }

    hideNext() {
        this.#nextButton.hidden = true;
    }

    showNext() {
        this.#nextButton.hidden = false;
    }

    hideStartOver() {
        this.#startoverButton.hidden = true;
    }

    showStartOver() {
        this.#startoverButton.hidden = false;
    }

    // get overall cart values
    // number of people in the cart
    getCartLength() {
        return this.#cartPerinfo.length;
    }

    // number of payment records in the cart
    getPmtLength() {
        return this.#cartPmt.length;
    }

    getPmt() {
        return make_copy(this.#cartPmt);
    }

    getCouponPmt() {
        for (let rownum in this.#cartPmt) {
            let prow = this.#cartPmt[rownum];
            if (prow.type == 'coupon')
                return make_copy(prow);
        }
        return null;
    }

    // get total price
    getTotalPrice() {
        return Number(this.#totalPrice);
    }

    // get total amount paid
    getTotalPaid() {
        return Number(this.#totalPaid);
    }

    // get total pmts in cart
    getTotalPmt() {
        return Number(this.#totalPmt);
    }

    // get the total coupon discounts on memberships in the cart
    getTotalCouponDiscountUnpaid() {
        return Number(this.#totalCouponUnpaid);
    }

    // check if a person is in cart already
    notinCart(perid) {
        return this.#cartPerinfoMap.isSet(perid) === false;
    }

    // notes fields in the cart, get current values and set new values, marking dirty for saving records

    getPerid(index) {
        return this.#cartPerinfo[index].perid;
    }

    getFullName(index) {
        return this.#cartPerinfo[index].fullName;
    }

    getEmail(index) {
        return this.#cartPerinfo[index].email_addr;
    }

    getPhone(index) {
        return this.#cartPerinfo[index].phone;
    }

    getCountry(index) {
        return this.#cartPerinfo[index].country;
    }

    getRegFullName(perid) {
        let index = this.#cartPerinfoMap.get(perid);
        return this.#cartPerinfo[index].fullName;
    }

    getRegLabel(perid, index) {
        let pindex = this.#cartPerinfoMap.get(perid);
        let perinfo = this.#cartPerinfo[pindex];
        let mem = perinfo.memberships[index];
        return mem.label;
    }

    getRegNote(perid, index) {
        let pindex = this.#cartPerinfoMap.get(perid);
        let perinfo = this.#cartPerinfo[pindex];
        let mem = perinfo.memberships[index];
        return mem.reg_notes;
    }

    getNewRegNote(perid, index) {
        let pindex = this.#cartPerinfoMap.get(perid);
        let perinfo = this.#cartPerinfo[pindex];
        let mem = perinfo.memberships[index];
        return mem.new_reg_note;
    }

    // managerSelect options - return select array of potential managers in cart
    getManagerSelect() {
        let optionList = "";
        for (let rownum in this.#cartPerinfo) {
            let prow = this.#cartPerinfo[rownum];
            if (prow.hasOwnProperty('managedBy') == false || prow.managedBy === null || prow.managedBy === 0) {
                // this is a potential manager
                optionList += "<option value=" + prow.perid + ">" + prow.fullName + ' (' + prow.perid + ")</option>\n";
            }
        }
        if (optionList.length > 0) {
            optionList = "<option value=''>Unmanaged</option>\n" + optionList;
        }
        return optionList;
    }

    setRegNote(perid, index, note) {
        let pindex = this.#cartPerinfoMap.get(perid);
        this.#cartPerinfo[pindex].memberships[index].new_reg_note = note;
        this.#cartPerinfo[pindex].dirty = true;
        this.drawCart();
    }

    setCouponDisount(perid, regid, paid, couponId, discount) {
        let pindex = this.#cartPerinfoMap.get(perid);
        let mem =  this.#cartPerinfo[pindex].memberships;
        for (let i = 0; i < mem.length; i++) {
            if (mem[i].regid == regid) {
                this.#cartPerinfo[pindex].memberships[i].paid = paid;
                this.#cartPerinfo[pindex].memberships[i].couponDiscount = discount;
                this.#cartPerinfo[pindex].memberships[i].coupon = couponId;
                break;
            }
        }
    }

    getPerinfoNote(index) {
        return this.#cartPerinfo[index].open_notes;
    }

    setPersonNote(index, note) {
        this.#cartPerinfo[index].open_notes = note;
        this.#cartPerinfo[index].dirty = true;
        this.#cartPerinfo[index].open_notes_pending = 1;
        this.drawCart();
    }

    // make a copy of private structures for use in ajax calls back to the PHP.   The master copies are only accessible within the class.
    getCartPerinfo() {
        return make_copy(this.#cartPerinfo);
    }

    updatePerinfo(pindex, rindex, mem) {
        this.#cartPerinfo[pindex].memberships[rindex] = make_copy(mem);
    }

    getCartMap() {
        return this.#cartPerinfoMap.getMap();
    }

    getCartPmt() {
        return make_copy(this.#cartPmt);
    }

    // return the age based on memberships in the cart row
    #getAge(p) {
        if (p.memberships) {
            let memberships = p.memberships;
            for (let i = 0; i < memberships.length; i++) {
                let mbr = memberships[i];
                if (mbr.memAge == 'all')
                    continue;
                return mbr.memAge;
            }
        }
        return '';
    }

    allowAddCouponToCart() {
        this.#anyUnpaid = false;
        if (coupon.isCouponActive())
            return true;
        let numCoupons = pos.everyMembership(this.#cartPerinfo, function(_this, mem) {
            if (isPrimary(mem.conid, mem.memType, mem.memCategory, mem.price, 'coupon') && mem.status != 'paid')
                cart.setAnyUnpaid();
            if (mem.coupon)
                return true;
            return false;
        });

        if (this.#anyUnpaid == false || numCoupons > 0)
            return false;

        return true;
    }

    pushMembership(mem) {
        this.#memberships.push(make_copy(mem));
    }

    pushAllMembership(mem) {
        this.#allMemberships.push(make_copy(mem));
    }

// if no memberships or payments have been added to the database, this will reset for the next customer
// TODO: verify how to tell if it's allowed to be shown as enabled
    startOver() {
        // empty cart
        this.#cartPerinfo = [];
        this.#cartPmt = [];
        this.#freezeCart = false;
        this.#priorPayments = null;

        this.hideNext();
        this.#inReview = false;
        this.drawCart();
    }

    // add search result_perinfo record to the cart
    add(p, first=false) {
        let i;
        let pindex = this.#cartPerinfo.length;
        p.memberAgeType = this.#getAge(p);
        // force reverify if not this year and age is of type verify
        if (p.currentAgeType == null || p.currentAgeType == undefined)
            p.currentAgeType = '';
        if (p.currentAgeConId != config.conid && p.currentAgeType != '') {
            let ageItem = ageListIdx[p.currentAgeType];
            if (ageItem.verify == 'Y') {
                p.currentAgeType = '';
                pos.setReviewDirty();
            }
        }
        // if no age, default to membership age type if any
        if (p.currentAgeType == '' && p.memberAgeType != '') {
            p.currentAgeType = p.memberAgeType;
            p.currentAgeConId = config.conid;
            pos.setReviewDirty();
        }
        if (first) {
            this.#cartPerinfo.unshift(make_copy(p));
            // need to renumber the existing cart
            for (pindex = 1; i < this.#cartPerinfo.length; pindex++) {
                this.#cartPerinfo[pindex].index = i;
                this.#cartPerinfoMap.set(this.#cartPerinfo[pindex].perid, pindex);
                let mrows = this.#cartPerinfo[pindex].memberships;
                for (let mrownum in mrows) {
                    this.#cartPerinfo[pindex].memberships[mrownum].index = mrownum;
                    this.#cartPerinfo[pindex].memberships[mrownum].pindex = pindex;
                }
            }
            pindex = 0;
        }
        else {
            // see if this person is the manager of anyone in the cart
            let added = false;
            for (i = 0; i < this.#cartPerinfo.length; i++) {
                if (this.#cartPerinfo[i].managedBy == p.perid) {
                    this.#cartPerinfo.unshift(make_copy(p));
                    added = true;
                    break;
                }
            }
            if (!added)
                this.#cartPerinfo.push(make_copy(p));
        }
        this.#cartPerinfo[pindex].index = pindex;
        this.#cartPerinfoMap.set(this.#cartPerinfo[pindex].perid, pindex);
        if (p.memberships) {
            let mrows = p.memberships;
            this.#cartPerinfo[pindex].memberships = make_copy(mrows);
            for (let mrownum = 0; mrownum < mrows.length; mrownum++) {
                this.#cartPerinfo[pindex].memberships[mrownum].index = mrownum;
                this.#cartPerinfo[pindex].memberships[mrownum].pindex = pindex;
                if (mrows[mrownum].couponDiscount === undefined) {
                    this.#cartPerinfo[pindex].memberships[mrownum].couponDiscount = 0.00;
                    this.#cartPerinfo[pindex].memberships[mrownum].coupon = null;
                }
            }
        } else {
            this.#cartPerinfo[pindex].memberships = [];
        }
        // default any missing policies
        for (let policynum in policies) {
            let p = policies[policynum];
            if (!this.#cartPerinfo[pindex].hasOwnProperty('policies')) {
                this.#cartPerinfo[pindex].policies = {};
            }
            if (this.#cartPerinfo[pindex].policies.hasOwnProperty(p.policy)) {
                continue;
            }
            // add the missing policy
            this.#cartPerinfo[pindex].policies[p.policy] = {
                perid: this.#cartPerinfo[pindex].perid,
                pindex: pindex,
                policy: p.policy,
                response: p.defaultValue
            };
        }
        this.drawCart();
    }

// remove person and all of their memberships from the cart
    remove(perid) {
        if (!pos.confirmDiscardAddEdit(false))
            return;

        let index = this.#cartPerinfoMap.get(perid);
        if (!this.confirmDiscardCartEntry(index, false))
            return;

        this.#cartPerinfo.splice(index, 1);
        // splices loses me the index number for the cross-reference, so the cart needs renumbering
        this.drawCart();
    }

    // get into the add/edit fields the requested cart entry
    getAddEditFields(perid) {
        let cartrow = this.#cartPerinfo[this.#cartPerinfoMap.get(perid)];

        // set perinfo values
        pos.editFromCartRow(cartrow);
    }

    // update the cart entry from the add/edit field row
    updateEntry(edit_index, row) {
        let cart_row = this.#cartPerinfo[edit_index];

        cart_row.first_name = row.first_name;
        cart_row.middle_name = row.middle_name;
        cart_row.last_name = row.last_name;
        cart_row.suffix = row.suffix;
        cart_row.legalName = row.legalName;
        cart_row.pronouns = row.pronouns;
        cart_row.badge_name = row.badge_name;
        cart_row.badgeNameL2 = row.badgeNameL2;
        cart_row.address_1 = row.address_1;
        cart_row.address_2 = row.address_2;
        cart_row.city = row.city;
        cart_row.state = row.state;
        cart_row.postal_code = row.postal_code;
        cart_row.country = row.country;
        cart_row.email_addr = row.email_addr;
        cart_row.phone = row.phone;
        cart_row.fullName = row.fullName;
        cart_row.currentAgeType = row.currentAgeType;
        cart_row.currentAgeConId = row.currentAgeConId;
        cart_row.active = 'Y';
        if (row.hasOwnProperty('managedBy')) {
            let managedBy = row.managedBy;
            if (managedBy == '' || managedBy == null)
               delete cart_row.managedBy;
            else
                cart_row.managedBy = managedBy;
        } else {
            delete cart_row.managedBy;
        }

        // policies - first check if the row has any, then update the row with the policies
        if (!cart_row.hasOwnProperty('policies')) {
            cart_row.policies = {};
        }
        for (let pol in policies) {
            let policyName = policies[pol].policy;

            if (!cart_row.policies.hasOwnProperty(policyName)) {
                cart_row.policies[policyName] = {};
                cart_row.policies[policyName].perid = cart_row.perid;
                cart_row.policies[policyName].pindex = cart_row.pindex;
                cart_row.policies[policyName].policy = policyName;
            }
            cart_row.policies[policyName].response = row.policies[policyName].response;
        }

        cart_row.dirty = true;
    }

    // check to see if the cart is not saved, and confirm leaving without saving it
    confirmDiscardCartEntry(index, silent) {
        if (this.isFrozen()) {
            return true;
        }

        let dirty = false;
        if (index >= 0) {
            dirty = this.#cartPerinfo[index].dirty === true;
        } else {
            for (let row in this.#cartPerinfo) {
                dirty ||= this.#cartPerinfo[row].dirty === true;
            }
        }

        if (!dirty)
            return true;

        if (silent)
            return false;

        let msg = "Discard updated cart items?";
        if (index >= 0)
            msg = "Discard updated cart items for " + (this.#cartPerinfo[index].first_name + ' ' + this.#cartPerinfo[index].last_name).trim();

        if (!confirm(msg)) {
            return false; // confirm answered no, return not safe to discard
        }

        return true;
    }

// use the memRules engine to add/edit the memberships for this person
    addEditMemberships(index) {
        let cart_row = this.#cartPerinfo[index];

        // set the current age type
        this.#memberAge = null;
        if (cart_row.memberAgeType && cart_row.memberAgeType != '' && cart_row.memberAgeType != 'all') {
            this.#memberAge = cart_row.memberAgeType;
            this.#currentAge = cart_row.memberAgeType;
        } else if (cart_row.currentAgeConId == config.conid)
            this.#currentAge = cart_row.currentAgeType;
        else if (cart_row.currentAgeType && cart_row.currentAgeType != '') {
            let ageItem = ageListIdx[cart_row.currentAgeType];
            if (ageItem.verify == 'Y') {
                this.#currentAge = null;
            } else {
                this.#currentAge = cart_row.currentAgeType;
                this.#cartPerinfo[index].currentAgeConId = config.conid;
            }
        } else
            this.#currentAge = null;

        this.#addEditPerid = cart_row.perid;
        let managedByLogin = false;
        let loginPrimary = false;
        if (this.#addEditModal) {
            this.#addEditFullName.innerHTML = cart_row.fullName;
            this.#memberships = [];
            this.#allMemberships = [];
            let matchId = this.#cartPerinfo[index].perid;
            let matchMan = this.#cartPerinfo[index].managedBy;

            if (matchMan != null) {
                for (let index = 0; index < this.#cartPerinfo.length; index++) {
                    let entry = this.#cartPerinfo[index];
                    if (entry.perid != matchId && entry.perid == matchMan) {
                        managedByLogin = true;
                        for (let mindex = 0; mindex < entry.memberships.length; mindex++) {
                            let mbrship = entry.memberships[mindex];
                            if (isPrimary(mbrship.conid, mbrship.memType, mbrship.memCategory, mbrship.memPrice))
                                loginPrimary = true;
                        }
                    }
                }
            }
            config.loginPrimary = loginPrimary;
            config.managedByLogin = managedByLogin;

            // build the current values of the memberships
            pos.everyMembership(this.#cartPerinfo, function(_this, mem, perinfo) {
                mem.currentAgeConId = perinfo.currentAgeConId;
                mem.currentAgeType = perinfo.currentAgeType;
                if (cart_row.perid == mem.perid ) {
                    cart.pushMembership(mem);
                }
                cart.pushAllMembership(mem);
            });
            this.buildRegItemButtons();
            this.redrawRegItems(index);
            this.#currentPerid = cart_row.perid;
            this.#currentPerIdx = index;
            this.#cartChanges = 0;
            clear_message('aeMessageDiv');
            this.#addEditModal.show();
        }
        return;
    }

// saveMembershipChange: save the changes to the perid's memberships back to the cart perinfo record
    saveMembershipChange() {
        this.#cartPerinfo[this.#currentPerIdx].memberships = make_copy(this.#memberships);

        if ((!this.#cartPerinfo[this.#currentPerIdx].currentAgeType) || this.#cartPerinfo[this.#currentPerIdx].currentAgeType == '') {
            this.#cartPerinfo[this.#currentPerIdx].currentAgeType = this.#memberAge;
        }
        if (this.#memberships.length > 0 && this.#cartPerinfo[this.#currentPerIdx].currentAgeType != '')
            this.#cartPerinfo[this.#currentPerIdx].currentAgeConId = config.conid;
        this.#cartPerinfo[this.#currentPerIdx].memberAgeType = this.#memberAge;
        this.#memberships = [];
        this.#allMemberships = [];
        this.#currentPerIdx = null;
        this.#currentPerid = null;
        this.#addEditModal.hide();
        this.drawCart();
    }

// Redraw Reg Items - redraw the items for this person
    redrawRegItems(index) {
        let totalDue = 0;
        let countMemberships = 0;
        let unpaidMemberships = 0;
        let html = `
            <div class="row">
                <div class="col-sm-2"><b>Actions</b></div>
                <div class="col-sm-1" style='text-align: right;'><b>Status</b></div>
                <div class="col-sm-1" style='text-align: right;'><b>Price</b></div>
                <div class="col-sm-1" style='text-align: right;'><b>Paid</b></div>
                <div class="col-sm-4"><b>Membership</b></div>
                <div class="col-sm-4"><b>Membership</b></div>
            </div>
`;
        let col1 = '';
        for (let row in this.#memberships) {
            let membershipRec = this.#memberships[row];
            countMemberships++;
            let amount_due = Number(membershipRec.price) - (Number(membershipRec.paid) + Number(membershipRec.couponDiscount));
            let label = (membershipRec.conid != config.conid ? membershipRec.conid + ' ' : '') + membershipRec.label +
                (membershipRec.memAge != 'all' ? (' ' + ageListIdx[membershipRec.memAge].label) : '');
            if ((!membershipRec.toDelete) && membershipRec.status.includes(this.#isDueStatuses))
                totalDue += amount_due;

            let strike = false
            let btncolor = 'btn-secondary';
            col1 = membershipRec.create_date;
            if (membershipRec.toDelete) {
                strike = true;
                col1 = '<button class="btn btn-sm btn-secondary pt-0 pb-0" onclick="cart.regItemRestore(' +
                    row + ')">Restore</button>';
            } else if (membershipRec.status == 'in-cart') {
                col1 = '<button class="btn btn-sm btn-secondary pt-0 pb-0" onclick="cart.regItemRemove(' + row + ')">Remove</button>';
            } else if (membershipRec.status != 'plan' && membershipRec.status != 'paid' && (membershipRec.paid == 0 || pos.getManager())) {
                col1 = '<button class="btn btn-sm ' + btncolor + ' pt-0 pb-0" onclick="cart.regItemDelete(' + row + ')">Delete</button>';
            }
            html += `
    <div class="row">
        <div class="col-sm-2">` + col1 + `</div>
        <div class="col-sm-1" style='text-align: right;'>` + (strike ? '<s>' : '') + membershipRec.status + (strike ? '</s>' : '') + `</div>
        <div class="col-sm-1" style='text-align: right;'>` + (strike ? '<s>' : '') + membershipRec.price + (strike ? '</s>' : '') + `</div>
        <div class="col-sm-1" style='text-align: right;'>` + (strike ? '<s>' : '') + membershipRec.paid + (strike ? '</s>' : '') + `</div>
        <div class="col-sm-7">` + (strike ? '<s>' : '') + label + (strike ? '</s>' : '') + `
        </div>
    </div>
`;
        }
        if (totalDue > 0) {
            html += `
    <div class='row'>
        <div class='col-sm-12 ms-0 me-0 align-center'>
            <hr color="black" style='height:3px;width:95%;margin:auto;margin-top:10px;margin-bottom:2px;'/>
        </div>
        <div class='col-sm-12 ms-0 me-0 align-center'>
            <hr color="black" style='height:3px;width:95%;margin:auto;margin-top:2px;margin-bottom:20px;'/>
        </div>
    </div>
    <div class="row">
        <div class="col-sm-2"></div>
        <div class="col-sm-1" style='text-align: right;'><b>Total Due:</b></div>
        <div class="col-sm-1" style='text-align: right;'><b>` +
                this.#currencyFmt.format(Number(totalDue).toFixed(2)) + `</b></div>
    </div>`
        }
        if (countMemberships == 0) {
            html = "Nothing in the Cart";
        }
        this.#cartContentsDiv.innerHTML = html;
    }

    // membership buttons
    buildRegItemButtons() {
        // loop over memList and build each button
        let html = '';
        let rules = new MembershipRules(pos.getConid(), this.#memberAge != null ? this.#memberAge : this.#currentAge, this.#memberships, this.#allMemberships);
        let atcon = false;
        if (config.hasOwnProperty('posType')) {
            atcon = config.posType == 'a';
        }

        let noAgeFilter = this.#currentAge == null;
        for (let row in memList) {
            let mem = memList[row];

            // if atcon, skip items without atcon = 'Y'
            if (atcon && mem.atcon != 'Y')
                continue;

            // skip auto create mem items
            if (mem.hasOwnProperty('notes') && mem.notes && mem.notes == 'Auto created by rollover')
                continue;
            // apply implitict rules and membershipRules against memList entry
            if (!rules.testMembership(mem))
                continue;

            // apply age filter from age select
            if (noAgeFilter || mem.memAge == 'all' || mem.memAge == this.#currentAge) {
                let memLabel = mem.label;
                if (memCategories[mem.memCategory].variablePrice != 'Y') {
                    memLabel += ' (' + mem.price + ')';
                }
                html += '<div class="col-sm-2 mt-1 mb-1 ms-0 me-0"><button id="memBtn-' + mem.id + '" class="btn btn-sm btn-primary w-100 h-100"' +
                    ' onclick="cart.regItemAdd(' + "'" + mem.id + "'" + ')">' +
                    (mem.conid != pos.getConid() ? mem.conid + ' ' : '') + memLabel + '</button></div>' + "\n";
            }
        }
        this.#membershipButtonsDiv.innerHTML = html;
    }

    // mark an unpaid membership row to be deleted on save
    regItemDelete(row) {
        clear_message('aeMessageDiv');
        if (this.#memberships == null || this.#memberships.length == 0) {
            show_message("No registration items found", "warn", 'aeMessageDiv');
            return;
        }

        let mbr = this.#memberships[row];
        if (mbr.status != 'unpaid' && !pos.getManager()) {
            show_message("Cannot remove that registration item, only unpaid items can be deleted.", "warn", 'aeMessageDiv');
            return
        }

        if (mbr.price == 0 && !pos.getManager()) {
            show_message("Please contact registration at " + config.regadminemail + "  to delete free items.", "warn", 'aeMessageDiv');
            return;
        }

        if (mbr.paid > 0 && !pos.getManager()) {
            show_message("Please contact registration at " + config.regadminemail + " to resolve this partially paid item.", "warn", 'aeMessageDiv');
            return;
        }

        // check if anything else in the cart depends on this membership
        // trial the delete
        mbr.toDelete = true;
        let rules = new MembershipRules(config.conid, this.#memberAge != null ? this.#memberAge : this.#currentAge, this.#memberships, this.#allMemberships);
        for (let nrow in this.#memberships) {
            if (row == nrow)    // skip checking ourselves
                continue;
            let nmbr = this.#memberships[nrow];
            if (nmbr.toDelete)
                continue;
            nmbr.toDelete = true;
            if (rules.testMembership(nmbr, true) == false) {
                mbr.toDelete = undefined;
                nmbr.toDelete = undefined;
                show_message("You cannot delete " + mbr.label + " because " + nmbr.label + " requires it.  You must delete/remove " + nmbr.label + " first.",
                    'warn', 'aeMessageDiv');
            }
            nmbr.toDelete = undefined;
        }


        this.#cartChanges++;
        pos.setReviewDirty();
        this.redrawRegItems(this.#currentPerIdx);
        this.buildRegItemButtons();
    }

    // restore a 'deleted' membership item
    regItemRestore(row) {
        clear_message('aeMessageDiv');
        if (this.#memberships == null) {
            show_message("No memberships found", "warn", 'aeMessageDiv');
            return;
        }

        let mbr = this.#memberships[row];
        if (!mbr.toDelete) {
            show_message("Cannot restore this membership, it is not marked deleted.", "warn", 'aeMessageDiv');
            return
        }

        let rules = new MembershipRules(config.conid, this.#memberAge != null ? this.#memberAge : this.#currentAge, this.#memberships, this.#allMemberships);
        if (rules.testMembership(mbr, false) == false) {
            show_message("You cannot restore " + mbr.label + " because it requires some other deleted membership. Look at your memberships marked 'Restore'" +
                " and restore its prerequesite", "warn", 'aeMessageDiv');
        } else {
            mbr.toDelete = undefined;
        }
        this.#cartChanges--;
        this.redrawRegItems();
        this.buildRegItemButtons();
    }

    // add to cart
    regItemAdd(id) {
        clear_message('aeMessageDiv');
        let memrow = findMembership(id);
        if (memrow == null)
            return;

        let now = new Date();
        let newMembership = {};
        newMembership.id = this.#newIDKey;
        newMembership.create_date = now.getFullYear() + '-' + ('0' + (now.getMonth() + 1)).slice(-2) + '-' + ('0' + now.getDate()).slice(-2) + ' ' +
            ('0' + now.getHours()).slice(-2) + ':' + ('0' + now.getMinutes()).slice(-2) + ':' + ('0' + now.getSeconds()).slice(-2);
        newMembership.memId = id;
        newMembership.conid = memrow.conid;
        newMembership.status = 'in-cart';
        newMembership.price = memrow.price;
        newMembership.paid = 0;
        newMembership.couponDiscount = 0;
        newMembership.label = memrow.label;
        newMembership.memCategory = memrow.memCategory;
        newMembership.glNum = memrow.glNum;
        newMembership.taxable = memrow.taxable;
        newMembership.memType = memrow.memType;
        newMembership.memAge = memrow.memAge;
        newMembership.perid =  this.#addEditPerid;
        let memCat = memCategories[memrow.memCategory];
        if (memCat.variablePrice == 'Y') {
            let mem = memListIdx[newMembership.memId];
            // update the modal with the item
            this.#vpBody.innerHTML = `
    <div class="row">
        <div class="col-sm-auto">
            <label for="vpPrice">How much for ` + mem.label + `?</label>
        </div>
        <div class="col-sm-auto">
            <input type="number" class='no-spinners' inputmode="numeric" id="vpPrice" name="vpPrice" size="20" placeholder="How Much?" min="` + mem.price + `"/>
        </div>
    </div>
`;
            this.#vpModal.show();
            this.#amountField = document.getElementById("vpPrice");
            this.#amountField.addEventListener('keyup', cart.amountEventListener);
            newMembership.minPrice = mem.price;
            this.#newMembershipSave = newMembership;
            let amountField = this.#amountField;
            setTimeout(() => { amountField.focus({focusVisible: true}); }, 600);
            return;
        }
        this.membershipAddFinal(newMembership);
    }

    aountEventListener(e) {
        if (e.code === 'Enter')
            cart.vpSubmit();
    }

    amountModalHidden(e) {
        clear_message('vpMessageDiv');
        this.#amountField.removeEventListener('keyup', cart.amountEventListener);
    }

    // vpsubmit - handle return from modal popup
    vpSubmit() {
        let priceField = document.getElementById('vpPrice');
        let price = Number(priceField.value).toFixed(2);
        let newMembership = this.#newMembershipSave;
        if (Number(price) < Number(newMembership.minPrice)) {
            show_message("Your " + newMembership.label + " cannot be less than " + newMembership.minPrice, 'warn', 'vpMessageDiv');
            return;
        }
        this.#newMembershipSave = null;
        newMembership.price = price;
        this.membershipAddFinal(newMembership);
        this,this.#vpModal.hide();
    }

    // finish membership add
    membershipAddFinal(newMembership) {
        if (!this.#memberships)
            this.#memberships = [];
        this.#memberships.push(make_copy(newMembership));
        this.newIDKey--;
        if (this.#memberAge == null && newMembership.memAge != 'all')
            this.#memberAge = newMembership.memAge;
        this.#cartChanges++;
        pos.setReviewDirty();
        this.redrawRegItems();
        this.buildRegItemButtons();
    }

    // remove an unsaved reg item row from the cart
    regItemRemove(row) {
        clear_message('aeMessageDiv');
        if (this.#memberships == null) {
            show_message("No registration items found", "warn", 'aeMessageDiv');
            return;
        }

        let mbr = this.#memberships[row];
        if (mbr.status != 'in-cart') {
            show_message("Cannot remove that item, only in-cart items can be removed.", "warn", 'aeMessageDiv');
            return
        }

        // check if anything else in the cart depends on this membership
        // trial the delete
        mbr.toDelete = true;
        let rules = new MembershipRules(config.conid, this.#memberAge != null ? this.#memberAge : this.#currentAge, this.#memberships, this.#allMemberships);
        for (let nrow in this.#memberships) {
            if (row == nrow)    // skip checking ourselves
                continue;
            let nmbr = this.#memberships[nrow];
            if (nmbr.toDelete)
                continue;
            if (rules.testMembership(nmbr, true) == false) {
                mbr.toDelete = undefined;
                show_message("You cannot remove " + mbr.label + " because " + nmbr.label + " requires it.  You must delete/remove " + nmbr.label + " first.", 'warn', 'aeMessageDiv');
                return;
            }
        }

        this.#memberships.splice(row, 1);
        this.#cartChanges--;
        // recompute memberAge and currentAge
        this.#memberAge = null;
        for (let i = 0; i < this.#memberships.length; i++) {
            let row = this.#memberships[i];
            if (row.memAge != 'all') {
                this.#memberAge = row.memAge;
                break;
            }
        }
        this.redrawRegItems();
        this.buildRegItemButtons();
    }

// addEdit Assist functions
    checkAddEditClose() {
        if (this.#cartChanges != 0) {
            if (!confirm("You have made unsaved changes to the memberships.  Do you wish to discard them?"))
                return;
        }
        this.#memberships = [];
        this.#allMemberships = [];
        this.#currentPerIdx = null;
        this.#currentPerid = null;
        this.#addEditModal.hide();
    }
// add non database payment to the cart
    addPmt(pmtrow, setIgnore=false) {
        this.#cartPmt.push(pmtrow);
        this.#cartIgnorePmtRound = setIgnore;
    }
// update payment data in  cart
    updatePmt(data) {
        this.#cartIgnorePmtRound = false;
        if (data.prow) {
            if (data.prow.preTaxAmt > 0 || data.prow.amt > 0)
                this.#cartPmt.push(data.prow);
        }
        if (data.crow) {
            if (data.crow.preTaxAmt > 0 || data.crow.amt > 0)
                this.#cartPmt.push(data.crow);
        }
        this.updateFromDB(data);
    }

// cartRenumber:
// rebuild the indices in the cartPerinfo and its membership tables
// for shortcut reasons indices are used to allow usage of the filter functions built into javascript
// this rebuilds the index and perinfo cross-reference maps.  It needs to be called whenever the number of items in cart is changed.
    cartRenumber() {
        let index;
        this.#cartPerinfoMap = new map();
        for (index = 0; index < this.#cartPerinfo.length; index++) {
            this.#cartPerinfo[index].index = index;
            this.#cartPerinfoMap.set(this.#cartPerinfo[index].perid, index);
            for (let rownum in this.#cartPerinfo[index].memberships) {
                this.#cartPerinfo[index].memberships[rownum].index = rownum;
                this.#cartPerinfo[index].memberships[rownum].pindex = index;
            }
        }
    }

    // Clear the coupon matching couponId from all rows in the cart
    clearCoupon(couponId) {
        // clear the discount from the membership rows from the cart element
        for (let cartRow in this.#cartPerinfo) {
            for (let rownum in this.#cartPerinfo[cartRow].memberships ) {
                let mrow = this.#cartPerinfo[cartRow].memberships[rownum];
                if (mrow.coupon == couponId && (mrow.status == 'unpaid' || mrow.status == 'plan')) {
                    this.#cartPerinfo[cartRow].memberships[rownum].coupon = null;
                    this.#cartPerinfo[cartRow].memberships[rownum].couponDiscount = 0;
                }
            }
        }
        // remove the discount coupon from the payment
        let delrows = [];
        for (let rownum in this.#cartPmt) {
            let prow = this.#cartPmt[rownum];
            if (prow.type == 'coupon') {
                delrows.push(rownum);
            }
        }
        // now delete the matching rows (in reverse order)
        delrows = delrows.reverse();
        for (let rownum in delrows)
            this.#cartPmt.splice(delrows[rownum], 1);
    }

// format all of the memberships for one record in the cart
    #drawCartRow(rownum) {
        let row = this.#cartPerinfo[rownum];
        let mrow;
        let rowlabel;
        let membership_found = false;
        let membership_html = '';
        let col1 = '';
        let perid = row.perid;
        let btncolor = null;
        // now loop over the memberships in the order retrieved
        let pindex = this.#cartPerinfoMap.get(perid);
        let mrows = this.#cartPerinfo[pindex].memberships;
        for (let mrownum in mrows) {
            mrow = mrows[mrownum];
            if (mrow.toDelete !== undefined)
                continue;

            let row_shown = true;
            let category = mrow.memCategory;
            if (category == 'yearahead' && mrow.conid == pos.getConid())
                category = 'standard'; // last years yearahead is this year's standard
            let memType = mrow.memType;

            // col1 - status
            switch (mrow.status) {
                case 'paid':
                    col1 = "Pd";
                    break;
                case 'unpaid':
                case 'in-cart':
                    col1 = "Upd";
                    break;
                case 'plan':
                    col1 = "Pln";
                    break;
                default:
                    col1 = mrow.status.substring(0, 3);
            }

            let label = mrow.label;
            if (!this.#freezeCart) {
                let notes_count = 0;
                if (mrow.reg_notes_count !== undefined && mrow.reg_notes_count !== null) {
                    notes_count = Number(mrow.reg_notes_count);
                }
                btncolor = 'btn-info';
                if (mrow.new_reg_note !== undefined && mrow.new_reg_note !== '')
                    btncolor = 'btn-warning';
                let btntext = 'Add Note';
                if (notes_count > 0) {
                    btntext = 'Notes:' + notes_count.toString();
                }
                label += ' <button type = "button" class="btn btn-sm ' + btncolor + ' pt-0 pb-0 ps-1 pe-1 m-0" ' +
                    'onclick = "pos.showRegNote(' + perid +', ' + mrownum + ', ' + notes_count + ')" ' +
                    'style=" --bs-btn-font-size:75%;">' + btntext + '</button >';
            }

            membership_html += `
    <div class="row">
        <div class="col-sm-1 pe-0">` + col1 + `</div>
        <div class="col-sm-7 ps-1">` + label + `</div>
        <div class="col-sm-2 text-end">` + this.#currencyFmt.format(Number(mrow.price).toFixed(2)) + `</div>
        <div class="col-sm-2 text-end">` + this.#currencyFmt.format((Number(mrow.paid) + Number(mrow.couponDiscount)).toFixed(2)) + `</div>
    </div>
`;
            this.#totalPrice += Number(mrow.price);
            this.#totalPaid += Number(mrow.paid);
            if (mrow.couponDiscount > 0) {
                this.#totalPaid += Number(mrow.couponDiscount);
                if (mrow.status == 'unpaid')
                    this.#totalCouponUnpaid += Number(mrow.couponDiscount);
            }
            membership_found = true;
            if (mrow.status != 'paid') {
                this.#unpaidRows++;
            }
        }
        // first row - member name, remove button
        let rowhtml = '<div class="row mt-1">';
        if (membership_found) {
            rowhtml += '<div class="col-sm-8 text-bg-success">Member: (' + perid + ') ';
        } else {
            rowhtml += '<div class="col-sm-8 text-bg-info">Non Member: (' + perid + ') ';
        }
        rowhtml += row.fullName + '</div>';
        let editColor = (row.currentAgeType != '' && row.memberAgeType != '' && row.currentAgeType != row.memberAgeType) ?
            'btn-warning' : 'btn-secondary';
        if (!this.#freezeCart) {
            rowhtml += `
        <div class="col-sm-2 text-center"><button type="button" class="btn btn-sm ` + editColor +
                ` pt-0 pb-0 ps-1 pe-1" onclick="pos.editFromCart(` + perid + `)">Edit</button></div>
        <div class="col-sm-2 text-center"><button type="button" class="btn btn-sm btn-secondary pt-0 pb-0 ps-1 pe-1" onclick="pos.removeFromCart(` + perid + `)">Remove</button></div>
`;
        }
        rowhtml += '</div>'; // end of member name row

        // second row - badge name
        rowhtml += `
    <div class="row">
        <div class="col-sm-3">Badge Name:</div>
        <div class="col-sm-5">` + badgeNameDefault(row.badge_name, row.badgeNameL2, row.first_name, row.last_name) + `</div>
        <div class="col-sm-2 text-center">`;
        if (!this.#freezeCart && row.open_notes != null && row.open_notes.length > 0) {
            rowhtml += '<button type="button" class="btn btn-sm btn-info p-0" onclick="pos.showPerinfoNotes(' + row.index + ', \'cart\')">View' +
                ' Notes</button>';
        }
        rowhtml += `</div>
        <div class="col-sm-2 text-center">`;
        if (pos.getManager() && !this.#freezeCart) {
            btncolor = 'btn-secondary';
            if (row.open_notes_pending !== undefined && row.open_notes_pending === 1)
                btncolor = 'btn-warning';
            rowhtml += '<button type="button" class="btn btn-sm ' + btncolor + ' p-0" onclick="pos.editPerinfoNotes(' + row.index + ', \'cart\')">Edit' +
                ' Notes</button>';
        }
        rowhtml += `</div>
</div>
`;  // end of second row - badge name
        // third row add/edit memberships
        if (!this.#freezeCart) {
            rowhtml += `
    <div class="row">
        <div class="col-sm-auto"><button type="button" class="btn btn-sm btn-primary" onclick="cart.addEditMemberships(` +
                row.index + ');" ' + (editColor == 'btn-warning' ? 'disabled' : '') + `>Add/Edit Memberships</button>
        </div>
    </div>
    `;
        }

        // now the membership rows
        if (membership_html != '') {
            rowhtml += membership_html;
        }

        if (membership_found)
            this.#membershipRows++
        else
            this.#needMembershipRows++;

        return rowhtml;
    }

// draw a payment row in the cart
    #drawCartPmtRow(prow) {
        //   index: cart_pmt.length, amt: pay_amt, ccauth: ccauth, checkno: checkno, desc: eldesc.value, type: ptype,

        let pmt = this.#cartPmt[prow];
        let code = '';
        if (pmt.type == 'check') {
            code = pmt.checkno;
        } else if (pmt.type == 'credit') {
            code = pmt.ccauth;
        }
        return `<div class="row">
    <div class="col-sm-2 p-0">` + pmt.type + `</div>
    <div class="col-sm-6 p-0">` + pmt.desc + `</div>
    <div class="col-sm-2 p-0">` + code + `</div>
    <div class="col-sm-2 text-end">` + this.#currencyFmt.format(Number(pmt.preTaxAmt).toFixed(2)) + `</div>
</div>
`;
    }

// draw/update by redrawing the entire cart
    drawCart() {
        this.cartRenumber(); // to keep indexing intact, renumber the index and pindex each time
        this.#totalPrice = 0;
        this.#totalPaid = 0;
        this.#totalCouponUnpaid = 0;
        let num_rows = 0;
        this.#membershipRows = 0;
        this.#needMembershipRows = 0;
        let html = `
<div class="container-fluid">
<div class="row">
    <div class="col-sm-8 text-bg-primary">Member</div>
    <div class="col-sm-2 text-bg-primary text-end">Price</div>
    <div class="col-sm-2 text-bg-primary text-end">Paid</div>
</div>
`;
        this.#unpaidRows = 0;
        for (let rownum in this.#cartPerinfo) {
            num_rows++;
            html += this.#drawCartRow(rownum);
        }
        this.#totalPrice = Number(this.#totalPrice.toFixed(2));
        this.#totalPaid = Number(this.#totalPaid.toFixed(2));
        html += `<div class="row">
    <div class="col-sm-10 text-end">————</div>
    <div class="col-sm-2  text-end">————</div>
</div>
<div class="row">
    <div class="col-sm-8 text-end">Total:</div>
    <div class="col-sm-2 text-end">` + this.#currencyFmt.format(Number(this.#totalPrice).toFixed(2)) + `</div>
    <div class="col-sm-2 text-end">` + this.#currencyFmt.format(Number(this.#totalPaid).toFixed(2)) + `</div>
</div>
`;

        if (this.#priorPayments == null && this.#cartPmt.length == 0 && this.#totalPaid > 0) {
            // add in the pre paid amount as a prior payment
            let prow = {
                amt: this.#totalPaid,
                preTaxAmt: this.#totalPaid,
                type: 'prior',
                desc: 'payments not in this session',
                code: ''
            };
            this.#cartPmt.push(prow);
        }
        // loop over the cartPmt row and recompute the prior paid row added to the cart has a prior payment.
        let totalPayments = 0;
        let priorIndex = 0;
        for (let i = 0; i < this.#cartPmt.length; i++) {
            totalPayments += Number(this.#cartPmt[i].preTaxAmt);
            if (this.#cartPmt[i].type == 'prior')
                priorIndex = i;
        }

        if (this.#totalPaid != totalPayments && !this.#cartIgnorePmtRound) {
            // adjust the prior prow
            this.#cartPmt[priorIndex].preTaxAmt = Number(this.#cartPmt[priorIndex].preTaxAmt) + Number(this.#totalPaid) - Number(totalPayments);
        }
        if (this.#cartPmt.length > 0) {
            html += `
<div class="row mt-3">
    <div class="col-sm-8 text-bg-primary">Payment</div>
    <div class="col-sm-2 text-bg-primary">Code</div>
    <div class="col-sm-2 text-bg-primary text-end">Amount</div>
</div>
`;
            this.#totalPmt = 0;
            for (let prow in this.#cartPmt) {
                html += this.#drawCartPmtRow(prow);
                this.#totalPmt += Number(this.#cartPmt[prow].preTaxAmt);
            }
            html += `<div class="row">
    <div class="col-sm-8 p-0 text-end">Payment Total:</div>`;
            this.#totalPmt = Number(this.#totalPmt.toFixed(2));
            html += `
    <div class="col-sm-4 text-end">` + this.#currencyFmt.format(Number(this.#totalPmt).toFixed(2)) + `</div>
</div>
`;
        }
        if (this.#needMembershipRows > 0) {
            let person = this.#needMembershipRows > 1 ? " people" : " person";
            let need = this.#needMembershipRows > 1 ? "need memberships" : "needs a membership";
            html += `<div class="row mt-3">
    <div class="col-sm-12">Cannot proceed to "Review" because ` + this.#needMembershipRows + person + " still " + need +
                `.  Use "Add/Edit" button to add memberships for them or "Remove" button to take them out of the cart.
    </div>
`;
        } else if (num_rows > 0) {
            this.#reviewButton.hidden = this.#inReview;
        }
        html += '</div> <!-- end container fluid -->'; // ending the container fluid
        //console.log(html);
        this.#cartDiv.innerHTML = html;
        this.#startoverButton.hidden = num_rows == 0;
        if (this.#needMembershipRows > 0 || (this.#membershipRows == 0 && this.#unpaidRows == 0)) {
            pos.setReviewTabDisable(true);
            this.#reviewButton.hidden = true;
        }
        if (this.#freezeCart) {
            pos.setReviewTabDisable(true);
            this.#reviewButton.hidden = true;
        }
        pos.setFindUnpaidHidden(num_rows > 0);
    }

    // create the HTML of the cart into the review data block
    buildReviewData() {
        pos.setMissingItems(0);
        pos.setMissingPolicies(0);
        let html = `
<div id='reviewBody' class="container-fluid form-floating">
  <form id='reviewForm' action='javascript: return false; ' class="form-floating">
`;
        let rownum = null;
        let row;
        let colors = new map();
        let fieldno;
        let mrow;
        let field;
        let tabindex = 0;
        let reviewMissingItems = 0;
        let missingRequiredPolicies = 0;
        for (rownum in this.#cartPerinfo) {
            tabindex += 100;
            row = this.#cartPerinfo[rownum];
            mrow = pos.find_primary_membership(row.memberships);
            // look up missing fields
            colors = new map();
            for (fieldno in this.#review_required_fields) {
                field = this.#review_required_fields[fieldno];
                if (row[field] == null || row[field] == '') {
                    reviewMissingItems++;
                    colors.set(field, 'var(--bs-warning)');
                } else {
                    colors.set(field, '');
                }
            }
            for (fieldno in this.review_prompt_fields) {
                field = this.#review_prompt_fields[fieldno];
                if (row[field] == null || row[field] == '') {
                    colors.set(field, 'var(--bs-info)');
                } else {
                    colors.set(field, '');
                }
            }
            html += '<div class="row">';
            if (mrow == null) {
                html += '<div class="col-sm-12 text-bg-info">No Primary Membership</div>';
            } else {
                html += '<div class="col-sm-12 text-bg-success">Membership: ' + row.memberships[mrow].label + '</div>';
            }

            html += `
    </div>
    <input type="hidden" id='c` + rownum + `-index' value="` + row.index + `"/>
    <div class="row mt-1">
        <div class="col-sm-auto ms-0 me-2 p-0">
            <input type="text" name="c` + rownum + `-first_name" id='c` + rownum + `-first_name' size="25" maxlength="32" placeholder="First Name" tabindex="` + String(tabindex + 2) +
                '" value="' + row.first_name + '" style="background-color:' + colors.get('first_name') + ';' +
                `"/>
        </div>
        <div class="col-sm-auto ms-0 me-2 p-0">
            <input type="text" name="c` + rownum + `-middle_name" id='c` + rownum + `-middle_name' size="6" maxlength="32" placeholder="Middle" tabindex="` + String(tabindex + 4) +
                '" value="' + row.middle_name + `"/>
        </div>
        <div class="col-sm-auto ms-0 me-2 p-0">
            <input type="text" name="c` + rownum + `-last_name" id='c` + rownum + `-last_name' size="25" maxlength="32" placeholder="Last Name" tabindex="` + String(tabindex + 6) +
                '" value="' + row.last_name + '" style="background-color:' + colors.get('last_name') + ';' + `"/>
        </div>
        <div class="col-sm-auto ms-0 me-0 p-0">
            <input type="text" name="c` + rownum + `-suffix" id='c` + rownum + `-suffix' size="6" maxlength="4" placeholder="Suffix" tabindex="` + String(tabindex + 8) +
                '" value="' + row.suffix + `"/>
        </div>
    </div>
    <div class="row">
        <div class="col-sm-auto ms-0 me-0 p-0">
            <input type="text" name='c` + rownum + `-legalName' id='c` + rownum + `-legalName' size=80 maxlength="128" placeholder="Legal Name: defaults to first middle last suffix" tabindex="` +
                String(tabindex + 10) +  '" value="' + row.legalName + `"/>
        </div>
    </div>
    <div class="row">
        <div class="col-sm-auto ms-0 me-0 p-0">
            <input type="text" name='c` + rownum + `-badge_name' id='c` + rownum + `-badge_name' size=64 maxlength="64" placeholder="Badgename: defaults to first and last name" tabindex="` +
                String(tabindex + 12) +'" value="' + row.badge_name + `"/>
        </div>
    </div>
    <div class="row">
        <div class="col-sm-auto ms-0 me-0 p-0">
            <input type="text" name='c` + rownum + `-badgeNameL2' id='c` + rownum + `-badgeNameL2' size=32 maxlength="32" placeholder="Badgename Line 2" tabindex="` +
                String(tabindex + 14) +'" value="' + row.badgeNameL2 + `"/>
        </div>
        <div class="col-sm-auto ms-0 me-0 p-0 ps-2">
`;
            if (row.memberAgeType && row.memberAgeType != '' && row.memberAgeType != 'all') {
                let ageItem = ageListIdx[row.memberAgeType];
                html += 'Member Age: ' + ageItem.shortname + ' [' + ageItem.label + ']';
            } else {
                html += `<select name='c` + rownum + `-age' id='c` + rownum + `-age' tabIndex="` + String(tabindex + 15) + `">
                    ` + this.#age_select + `
                </select>
                `;
            }
            html += `
        </div>
    </div>
     <div class="row">
        <div class="col-sm-auto ms-0 me-0 p-0">
            <input type="text" name='c` + rownum + `-pronouns' id='c` + rownum + `-pronouns' size=80 maxlength="128" placeholder="Pronouns" tabindex="` +
                String(tabindex + 16) +  '" value="' + row.pronouns + `"/>
        </div>
    </div>
    <div class="row">
        <div class="col-sm-auto ms-0 me-2 p-0">
            <input type="text" name='c` + rownum + `-email_addr' id='c` + rownum + `-email_addr' size=64 maxlength="254" placeholder="Email Address" tabindex="` +
                String(tabindex + 18) + '"  value="' + row.email_addr + '" style="background-color:' + colors.get('email_addr') + ';' + `"/>
        </div>
         <div class="col-sm-auto ms-0 me-0 p-0">
            <input type="text" name='c` + rownum + `-phone' id='c` + rownum + `-phone' size=15 maxlength="15" placeholder="Phone Number" tabindex="` +
            String(tabindex + 20) + '" value="' + row.phone + '" style="background-color:' + colors.get('phone') + ';' + `"/>
        </div>
    </div>
    <div class="row">
        <div class="col-sm-auto ms-0 me-0 p-0">
            <input type="text" name='c` + rownum + `-address_1' id='c` + rownum + `-address_1' size=64 maxlength="64" placeholder="Street Address" tabindex="` +
                String(tabindex + 22) + '"  value="' + row.address_1 + '" style="background-color:' + colors.get('address_1') + ';' + `"/>
        </div>
    </div>
    <div class="row">
        <div class="col-sm-auto ms-0 me-0 p-0">
            <input type="text" name='c` + rownum + `-address_2' id='c` + rownum + `-address_2' size=64 maxlength="64" placeholder="2nd line of Address (if needed, such as company)" tabindex="` +
                String(tabindex + 24) + '" value="' + row.address_2 + `"/>
        </div>
    </div>
    <div class="row">
        <div class="col-sm-auto ms-0 me-2 p-0">
            <input type="text" name="c` + rownum + `-city" id='c` + rownum + `-city' size="22" maxlength="32" placeholder="City" tabindex="` + String(tabindex + 26) +
                '" value="' + row.city + '" style="background-color:' + colors.get('city') + ';' + `"/>
        </div>
        <div class="col-sm-auto ms-0 me-2 p-0">
            <input type="text" name="c` + rownum + `-state" id='c` + rownum + `-state' size="10" maxlength="16" placeholder="State/Prov" tabindex="` + String(tabindex + 28) +
                '" value="' + row.state + '" style="background-color:' + colors.get('state') + ';' + `"/>
        </div>
        <div class="col-sm-auto ms-0 me-2 p-0">
            <input type="text" name="c` + rownum + `-postal_code" id='c` + rownum + `-postal_code' size="10" maxlength="10" placeholder="Postal Code" tabindex="` + String(tabindex +30) +
            '" value="' + row.postal_code + '" style="background-color:' + colors.get('postal_code') + ';' + `"/>
        </div>
        <div class="col-sm-auto ms-0 me-0 p-0">
            <select name='c` + rownum + `-country' id='c` + rownum + `-country' tabindex="` + String(tabindex + 32) + `">
                ` + this.#country_select + `
            </select>
        </div>
    </div>
    <div class="row mb-4">
`;

            // policies
            let i = 0;
            for (let polrow in row.policies) {
                let policyName = row.policies[polrow].policy;
                if (policyIndex[policyName] == undefined) // skip over inactive policies
                    continue;

                let policyResp = row.policies[polrow].response;
                let color = '';
                if (config.mode != 'admin' && policies[policyIndex[policyName]].required == 'Y' && policyResp == 'N') {
                    missingRequiredPolicies++;
                    color = "var(--bs-danger-bg-subtle)"
                }
                i = i + 1;
                html += '<div class="col-sm-auto" style="background-color: ' + color + ';">' + policyName + ': ' +
                    '<input type="checkbox" name="c' + rownum + '-p_' + policyName + '" id="c' + rownum + '-p_' + policyName +
                    '" tabindex="' + String(tabindex + 36 + i) +
                    '" value="Y"' + (policyResp == 'Y' ? ' checked' : ' ') + '/>\n</div>\n';
            }

        html += '\n</div>\n';
        }

    html += `<div class="row mt-2">
        <div class="col-sm-1 m-0 p-0">&nbsp;</div>
        <div class="col-sm-auto m-0 p-0">
            <button class="btn btn-primary btn-sm" type="button" id="review-btn-update" onclick="pos.reviewUpdate();">Update All</button>
            <button class="btn btn-primary btn-sm" type="button" id="review-btn-nochanges" onclick="pos.reviewNoChanges();" ` +
                (pos.isReviewDirty() || missingRequiredPolicies > 0 ? ' disabled ' : '') + `>No Changes</button>
        </div>
    </div>
    <div class="row">
        <div class="col-sm-12" id="review_status"></div>
    </div>
  </form>
</div>
`;
        pos.setMissingItems(reviewMissingItems + missingRequiredPolicies);
        pos.setMissingPolicies(missingRequiredPolicies);
        return html;
    }

// update the cart from the review block
    updateReviewData() {
        // loop over cart looking for changes in data table
        let rownum = null;
        let el;
        let field;
        let fieldno;
        let review_editable_fields = pos.getReviewEditableFields();
        for (rownum in this.#cartPerinfo) {
            // update all the fields on the review page
            for (fieldno in review_editable_fields) {
                field = review_editable_fields[fieldno];
                el = document.getElementById('c' + rownum + '-' + field);
                if (el) {
                    if (this.#cartPerinfo[rownum][field] != el.value) {
                        // alert("updating  row " + rownum + ":" + rownum + ":" + field + " from '" + this.#cartPerinfo[rownum][field] + "' to '" + el.value + "'");
                        this.#cartPerinfo[rownum][field] = el.value;
                        this.#cartPerinfo[rownum].dirty = false;
                    }
                }
            }
            // update all the policy values
            let rowPolicies = this.#cartPerinfo[rownum].policies;
            for (let polrow in rowPolicies) {
                let policyName = rowPolicies[polrow].policy;
                let policyResp = rowPolicies[polrow].response;
                el = document.getElementById('c' + rownum + '-p_' + policyName);
                if (el) {
                    if (policyResp != (el.checked ? 'Y' : 'N')) {
                        this.#cartPerinfo[rownum].policies[polrow].response = el.checked ? 'Y' : 'N';
                        this.#cartPerinfo[rownum].dirty = false;
                    }
                }
            }
        }
    }

// update the card with fields provided by the update of the database.  And since the DB is now updated, clear the dirty flags.
    updateFromDB(data) {
        this.#cartPerinfo = data.updated_perinfo;
        // redraw the cart with the new id's and maps, which will compute the unpaid rows.
        cart.drawCart();
        return this.#unpaidRows;
    }

    // update selected element in the pulldowns in the review data screen from the cart
    setReviewedSelect() {
        let rownum;
        let row;
        let selid;

        for (rownum in this.#cartPerinfo) {
            row = this.#cartPerinfo[rownum];
            selid = document.getElementById('c' + rownum + '-country');
            selid.value = row.country;
            if (row.currentAgeType && row.currentAgeType != 'all') {
                selid = document.getElementById('c' + rownum + '-age');
                if (selid)
                    selid.value = row.currentAgeType;
            }
        }
        cart.drawCart();
    }

// receiptHeader - retrieve receipt header info from cart[0]
    receiptHeader(user_id, pay_tid) {
        let payee = (this.#cartPerinfo[0].first_name + ' ' + this.#cartPerinfo[0].last_name).trim();
        return "Receipt for payment to " + pos.getConlabel() + " By: " + payee + ", Cashier: " + user_id + ", Transaction: " + pay_tid;
    }

// printList - html to display cart elements to print
    printList(new_print, printed_obj) {
        let rownum;
        let crow;
        let mrow;
        let print_html = '';

        for (rownum in this.#cartPerinfo) {
            crow = this.#cartPerinfo[rownum];
            mrow = pos.find_primary_membership(crow.memberships);
            if (mrow == null)
                continue;   // skip anyone without a primary
            mrow = crow.memberships[mrow];
            // if one day, and multi, find all one days, else just select this one
            if (pos.isMultiOneDay() && mrow.memType == 'oneday') {
                // this row is a one day, find all the memberships that are type one day
                for (let row in crow.memberships) {
                    let mbrrow = crow.memberships[row];
                    print_html += `
    <div class="row">
        <div class="col-sm-2 ms-0 me-2 p-0">
            <button class="btn btn-primary btn-sm" type="button" id="pay-print-` + this.#cartPerinfo[rownum].index + `" name="print_btn" onclick="pos.printBadge(` +
                        crow.index + ',' + mbrrow.index + `);">Print</button>
        </div>
        <div class="col-sm-auto ms-0 me-2 p-0">            
            <span class="text-bg-success"> Membership: ` + mbrrow.label + `</span> (Times Printed: ` +
                                mbrrow.printcount + `)<br/>
              ` + badgeNameDefault(crow.badge_name, crow.badgeNameL2, crow.first_name, crow.last_name) + '/' + (crow.first_name + ' ' + crow.last_name).trim() + `
        </div>
     </div>`;
                    if (new_print) {
                        printed_obj.set(mbrrow.regid, 0);
                    }
                    pos.addToBadgeList(crow.index, mbrrow.index);
                }
            } else {
                print_html += `
    <div class="row">
        <div class="col-sm-2 ms-0 me-2 p-0">
            <button class="btn btn-primary btn-sm" type="button" id="pay-print-` + this.#cartPerinfo[rownum].index + `" name="print_btn" onclick="pos.printBadge(` +
                    crow.index + ',' + mrow.index + `);">Print</button>
        </div>
        <div class="col-sm-auto ms-0 me-2 p-0">            
            <span class="text-bg-success"> Membership: ` + mrow.label + `</span> (Times Printed: ` +
                    mrow.printcount + `)<br/>
              ` + badgeNameDefault(crow.badge_name, crow.badgeNameL2, crow.first_name, crow.last_name)  + '/' + (crow.first_name + ' ' + crow.last_name).trim() + `
        </div>
     </div>`;
                if (new_print) {
                    printed_obj.set(mrow.regid, 0);
                }
                pos.addToBadgeList(crow.index, mrow.index);
            }
        }
        return print_html;
    }

// getBadge = return the cart portions of the parameters for a badge print, that will be added to by the calling routine
    getBadge(cindex, mindex) {
        let row = this.#cartPerinfo[cindex];
        let printrow = row.memberships[mindex];

        let params = {};
        params.type = printrow.memType;
        params.badge_name = row.badge_name;
        params.badgeNameL2 = row.badgeNameL2;
        params.first_name = row.first_name;
        params.last_name = row.last_name;
        params.category = printrow.memCategory;
        params.badge_id = row.perid;
        params.day = dayFromLabel(printrow.label);
        if (printrow.memAge != 'all')
            params.age = printrow.memAge;
        else if (row.currentAgeType)
            params.age = row.currentAgeType;
        else
            params.age = 'all';
        params.regId = printrow.regid;
        params.printCount = printrow.printcount;
        return params;
    }
}
