// Point of Sale Cart Class - all functions and data related to the cart portion of the right side of the screen
// The cart manages: People, Memberships and Payment
class PosCart {
// cart dom items
    #voidButton = null;
    #startoverButton = null;
    #reviewButton = null;
    #nextButton = null;
    #nochangesButton = null;
    #cartDiv = null;

// cart states
    #inReview = false;
    #freezeCart = false;
    #changeRow = null;

// cart internals
    #totalPrice = 0;
    #totalPaid = 0;
    #totalPmt = 0;
    #unpaidRows = 0;
    #membershipRows = 0;
    #needMembershipRows = 0;
    #cartPerinfo = [];
    #cartPerinfoMap = new map();
    #cartPmt = [];

// Add Edit Memberships
    #addEditModal = null;
    #addEditBody = null;
    #addEditTitle = null;
    #addEditFullName = null;
    #ageButtonsDiv = null;
    #membershipButtonsDiv = null;
    #memberAge = null;
    #memberAgeLabel = null;
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
    #country_select = document.getElementById('country').innerHTML;

// Pay items
    #anyUnpaid = false;

// Constants
    #isMembershipTypes = [ 'full', 'virtual', 'oneday' ];
    #isDueStatuses = [ 'unpaid', 'plan', 'in-cart' ];

// initialization
    constructor() {
// lookup all DOM elements
// ask to load mapping tables
        this.#cartDiv = document.getElementById("cart");
        this.#voidButton = document.getElementById("void_btn");
        this.#startoverButton = document.getElementById("startover_btn");
        this.#reviewButton = document.getElementById("review_btn");
        this.#nextButton = document.getElementById("next_btn");
        this.#nochangesButton = document.getElementById("cart_no_changes_btn");

        // addEdit membership
        var id = document.getElementById('addEdit');
        if (id) {
            this.#addEditModal = new bootstrap.Modal(id, {focus: true, backdrop: 'static'});
            this.#addEditBody = document.getElementById('addEditBody');
            this.#addEditTitle = document.getElementById('addEditTitle');
            this.#addEditFullName = document.getElementById('addEditFullName');
            this.#ageButtonsDiv = document.getElementById('ageButtons');
            this.#membershipButtonsDiv = document.getElementById('membershipButtons');
            this.#cartContentsDiv = document.getElementById('cartContentsDiv');
        }
        var id = document.getElementById("variablePriceModal");
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

    hideVoid() {
        this.#voidButton.hidden = true;
    }

    showVoid() {
        this.#voidButton.hidden = false;
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

    // check if a person is in cart already
    notinCart(perid) {
        return this.#cartPerinfoMap.isSet(perid) === false;
    }

    // notes fields in the cart, get current values and set new values, marking dirty for saving records

    getFullName(index) {
        return this.#cartPerinfo[index].fullName;
    }

    getRegFullName(perid) {
        var index = this.#cartPerinfoMap.get(perid);
        return this.#cartPerinfo[index].fullName;
    }

    getRegLabel(perid, index) {
        var pindex = this.#cartPerinfoMap.get(perid);
        var perinfo = this.#cartPerinfo[pindex];
        var mem = perinfo.memberships[index];
        return mem.label;
    }

    getRegNote(perid, index) {
        var pindex = this.#cartPerinfoMap.get(perid);
        var perinfo = this.#cartPerinfo[pindex];
        var mem = perinfo.memberships[index];
        return mem.notes;
    }

    getNewRegNote(perid, index) {
        var pindex = this.#cartPerinfoMap.get(perid);
        var perinfo = this.#cartPerinfo[pindex];
        var mem = perinfo.memberships[index];
        return mem.new_reg_note;
    }

    setRegNote(perid, index, note) {
        var pindex = this.#cartPerinfoMap.get(perid);
        this.#cartPerinfo[pindex].memberships[index].new_reg_note = note;
        this.#cartPerinfo[pindex].dirty = true;
        this.drawCart();
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

    allowAddCouponToCart() {
        this.#anyUnpaid = false;
        var numCoupons = pos.everyMembership(this.#cartPerinfo, function(_this, mem) {
            if (mem.coupon)
                return 1;
            if ((!pos.nonPrimaryCategoriesIncludes(mem.memCategory)) && mem.conid == pos.getConid() && mem.status != 'paid')
                cart.setAnyUnpaid();
        });

        if (this.#anyUnpaid == false || numCoupons > 0)
            return false;

        return true;
    }

    getPriorDiscount() {
        var priordiscount = 0;
        console.log("getPriorDiscount: TODO");
        /*
        for (var rownum in this.#cart_membership) {
            var mrow = this.#cart_membership[rownum];
            if (mrow.couponDiscount) {
                priordiscount += Number(mrow.couponDiscount);
            }
        }
         */

        return priordiscount;
    }

// if no memberships or payments have been added to the database, this will reset for the next customer
// TODO: verify how to tell if it's allowed to be shown as enabled
    startOver() {
        // empty cart
        this.#cartPerinfo = [];
        this.#cartPmt = [];
        this.#freezeCart = false;

        this.hideNext();
        this.hideVoid();
        this.#inReview = false;
        this.drawCart();
    }

    // add search result_perinfo record to the cart
    add(p) {
           var pindex = this.#cartPerinfo.length;
        this.#cartPerinfo.push(make_copy(p));
        this.#cartPerinfo[pindex].index = pindex;
        this.#cartPerinfoMap.set(this.#cartPerinfo[pindex].perid, pindex);
        var mrows = p.memberships;
        for (var mrownum in mrows) {
            this.#cartPerinfo[pindex].memberships[mrownum].index = mrownum;
            this.#cartPerinfo[pindex].memberships[mrownum].pindex = pindex;
            if (mrows[mrownum].couponDiscount === undefined) {
                this.#cartPerinfo[pindex].memberships[mrownum].couponDiscount = 0.00;
                this.#cartPerinfo[pindex].memberships[mrownum].coupon = null;
            }
        }
        this.drawCart();
    }

// remove person and all of their memberships from the cart
    remove(perid) {
        if (!pos.confirmDiscardAddEdit(false))
            return;

        var index = this.#cartPerinfoMap.get(perid);
        if (!this.confirmDiscardCartEntry(index, false))
            return;

        this.#cartPerinfo.splice(index, 1);
        // splices loses me the index number for the cross-reference, so the cart needs renumbering
        this.drawCart();
    }

    // get into the add/edit fields the requested cart entry
    getAddEditFields(perid) {
        var cartrow = this.#cartPerinfo[this.#cartPerinfoMap.get(perid)];

        // set perinfo values
        pos.editFromCart(cartrow);
    }

    // update the cart entry from the add/edit field row
    updateEntry(edit_index, row, policies) {
        var cart_row = this.#cartPerinfo[edit_index];

        cart_row.first_name = row.first_name;
        cart_row.middle_name = row.middle_name;
        cart_row.last_name = row.last_name;
        cart_row.suffix = row.suffix;
        cart_row.legalName = row.legalName;
        cart_row.pronouns = row.pronouns;
        cart_row.badge_name = row.badge_name;
        cart_row.address_1 = row.address_1;
        cart_row.address_2 = row.address_2;
        cart_row.city = row.city;
        cart_row.state = row.state;
        cart_row.postal_code = row.postal_code;
        cart_row.country = row.country;
        cart_row.email_addr = row.email_addr;
        cart_row.phone = row.phone;
        cart_row.active = 'Y';

        for (var pol in policies) {
            var policyName = policies[pol].policy;

            if (!cart_row.policies.hasOwnProperty(policyName)) {
                cart_row.policies[policyName] = {};
                cart_row.policies[policyName].perid = cart_row.perid;
                cart_row.policies[policyName].pindex = cart_row.pindex;
                cart_row.policies[policyName].policy = policyName;
            }
            cart_row.policies[policyName].response = row.policies[policyName].response;
        }

        cart_row.dirty = true;

        // policies
        console.log("TODO: Policies");
    }

    // check to see if the cart is not saved, and confirm leaving without saving it
    confirmDiscardCartEntry(index, silent) {
        if (this.isFrozen()) {
            return true;
        }

        var dirty = false;
        if (index >= 0) {
            dirty = this.#cartPerinfo[index].dirty === true;
        } else {
            for (var row in this.#cartPerinfo) {
                dirty ||= this.#cartPerinfo[row].dirty === true;
            }
        }

        if (!dirty)
            return true;

        if (silent)
            return false;

        var msg = "Discard updated cart items?";
        if (index >= 0)
            msg = "Discard updated cart items for " + (this.#cartPerinfo[index].first_name + ' ' + this.#cartPerinfo[index].last_name).trim();

        if (!confirm(msg)) {
            return false; // confirm answered no, return not safe to discard
        }

        return true;
    }

// remove single membership item from the cart (leaving other memberships and person information
    deleteMembership(index) {
        console.log("deleteMembership: TODO");
        return;
        /*
        if (this.#cart_membership[index].tid != '') {
            if (confirm("Confirm delete for " + this.#cart_membership[index].label)) {
                this.#cart_membership[index].todelete = 1;
                this.#cartPerinfo[this.#cart_membership[index].pindex].dirty = true;
            }
        } else {
            this.#cart_membership.splice(index, 1);
        }
        this.drawCart();

         */
    }

// use the memRules engine to add/edit the memberships for this person
    addEditMemberships(index) {
        var cart_row = this.#cartPerinfo[index];
        if (this.#addEditModal) {
            this.#addEditFullName.innerHTML = cart_row.fullName;
            this.#memberships = [];
            this.#allMemberships = [];

            // build the current values of the memberships
            this.everyMembership(this.#cartPerinfo, function(_this, mem) {
                if (cart_row.perid == mem.perid ) {
                    _this.#memberships.push(mem);
                }
                _this.#allMemberships.push(mem);
            });
            this.buildAgeButtons();
            this.buildRegItemButtons();
            this.redrawRegItems(index);
            this.#currentPerid = cart_row.perid;
            this.#currentPerIdx = index;
            this.#cartChanges = 0;
            this.#addEditModal.show();
        }
        return;
    }

// Redraw Reg Items - redraw the items for this person
    redrawRegItems(index) {
        var totalDue = 0;
        var countMemberships = 0;
        var unpaidMemberships = 0;
        var html = `
            <div class="row">
                <div class="col-sm-2"><b>Actions</b></div>
                <div class="col-sm-1" style='text-align: right;'><b>Status</b></div>
                <div class="col-sm-1" style='text-align: right;'><b>Price</b></div>
                <div class="col-sm-1" style='text-align: right;'><b>Paid</b></div>
                <div class="col-sm-4"><b>Membership</b></div>
            </div>
`;
        var col1 = '';
        for (var row in this.#memberships) {
            var membershipRec = this.#memberships[row];
            countMemberships++;
            var amount_due = Number(membershipRec.price) - (Number(membershipRec.paid) + Number(membershipRec.couponDiscount));
            var label = (membershipRec.conid != config.conid ? membershipRec.conid + ' ' : '') + membershipRec.label +
                (membershipRec.memAge != 'all' ? ' . + ageListIdx[membershipRec.memAge].label + ' : '');
            if ((!membershipRec.toDelete) && membershipRec.status.includes(this.#isDueStatuses))
                totalDue += amount_due;

            var strike = false
            var btncolor = 'btn-secondary';
            col1 = membershipRec.create_date;
            if (membershipRec.toDelete) {
                strike = true;
                col1 = '<button class="btn btn-sm btn-secondary pt-0 pb-0" onclick="cart.regItemRestore(' +
                    row + ')">Restore</button>';
            } else if (membershipRec.status == 'in-cart') {
                col1 = '<button class="btn btn-sm btn-secondary pt-0 pb-0" onclick="cart.regItemRemove(' + row + ')">Remove</button>';
            } else if (membershipRec.status != 'plan' && (membershipRec.paid == 0 || pos.getManager())) {
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
        <div class="col-sm-1" style='text-align: right;'><b>$` + Number(totalDue).toFixed(2)+ `</b></div>
    </div>`
        }
        if (countMemberships == 0) {
            html = "Nothing in the Cart";
        }
        this.#cartContentsDiv.innerHTML = html;
    }

// age buttons
    buildAgeButtons() {
        // first check if there is a current age;
        for (var row in this.#memberships) {
            var mbr = this.#memberships[row];
            if (mbr.memAge != 'all') {
                this.#memberAge = mbr.memAge;
                this.#memberAgeLabel = ageListIdx[this.#memberAge].label;
                if (this.#currentAge == null)
                    this.#currentAge = this.#memberAge
                break;
            }
        }

        var color = this.#memberAge != null ? 'btn-warning' : (this.#currentAge != null ? 'btn-secondary' : 'btn-primary');
        // now loop over age list and build each button
        var html = '';
        for (row in ageList) {
            var age = ageList[row];
            if (age.ageType == 'all')
                continue;

            html += '<div class="col-sm-auto"><button id="ageBtn-' + age.ageType + '" class="btn btn-sm ' +
                ((this.#currentAge == age.ageType || this.#memberAge == age.ageType) ? 'btn-primary' : color) + '" onclick="cart.ageSelect(' + "'" + age.ageType + "'" + ')">' +
                age.label + ' (' + age.shortname + ')' +
                '</button></div>' + "\n";
        }
        this.#ageButtonsDiv.innerHTML = html;
    }

    // ageSelect - redo all the age buttons on selecting one of them, then move on to the next page
    ageSelect(ageType) {
        if (this.#memberAge != null && ageType != this.#memberAge) {
            show_message("You already have a membership of the age '" + this.#memberAgeLabel, "warn", 'aeMessageDiv');
            return;
        }

        this.#currentAge = ageType;
        var color = this.#memberAge != null ? 'btn-warning' : (this.#currentAge != null ? 'btn-secondary' : 'btn-primary');
        for (var row in ageList) {
            var age = ageList[row];
            if (age.ageType == 'all')
                continue;
            var btn = document.getElementById('ageBtn-' + age.ageType);
            btn.classList.remove('btn-primary');
            btn.classList.remove('btn-secondary');
            btn.classList.remove('btn-warning');
            btn.classList.add((this.#currentAge == age.ageType || this.#memberAge == age.ageType) ? 'btn-primary' : color);
        }
    }

    // membership buttons
    buildRegItemButtons() {
        // loop over memList and build each button
        var html = '';
        var rules = new MembershipRules(pos.getConid(), this.#memberAge != null ? this.#memberAge : this.#currentAge, this.#memberships, this.#allMemberships);

        for (var row in memList) {
            var mem = memList[row];
            // apply implitict rules and membershipRules against memList entry
            if (!rules.testMembership(mem))
                continue;

            // apply age filter from age select
            if (mem.memAge == 'all' || mem.memAge == this.#currentAge) {
                var memLabel = mem.label;
                if (memCategories[mem.memCategory].variablePrice != 'Y') {
                    memLabel += ' (' + mem.price + ')';
                }
                html += '<div class="col-sm-auto mt-1 mb-1"><button id="memBtn-' + mem.id + '" class="btn btn-sm btn-primary"' +
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

        var mbr = this.#memberships[row];
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
        var rules = new MembershipRules(config.conid, this.#memberAge != null ? this.#memberAge : this.#currentAge, this.#memberships, this.#allMemberships);
        for (var nrow in this.#memberships) {
            if (row == nrow)    // skip checking ourselves
                continue;
            var nmbr = this.#memberships[nrow];
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

        var mbr = this.#memberships[row];
        if (!mbr.toDelete) {
            show_message("Cannot restore this membership, it is not marked deleted.", "warn", 'aeMessageDiv');
            return
        }

        var rules = new MembershipRules(config.conid, this.#memberAge != null ? this.#memberAge : this.#currentAge, this.#memberships, this.#allMemberships);
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
        var memrow = findMembership(id);
        if (memrow == null)
            return;

        var now = new Date();
        var newMembership = {};
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
        newMembership.memType = memrow.memType;
        newMembership.memAge = memrow.memAge;
        var memCat = memCategories[memrow.memCategory];
        if (memCat.variablePrice == 'Y') {
            var mem = memListIdx[newMembership.memId];
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
            var amountField = this.#amountField;
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
        var priceField = document.getElementById('vpPrice');
        var price = Number(priceField.value).toFixed(2);
        var newMembership = this.#newMembershipSave;
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
        this.#memberships.push(newMembership);
        this.newIDKey--;
        this.#cartChanges++;
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

        var mbr = this.#memberships[row];
        if (mbr.status != 'in-cart') {
            show_message("Cannot remove that item, only in-cart items can be removed.", "warn", 'aeMessageDiv');
            return
        }

        // check if anything else in the cart depends on this membership
        // trial the delete
        mbr.toDelete = true;
        var rules = new MembershipRules(config.conid, this.#memberAge != null ? this.#memberAge : this.#currentAge, this.#memberships, this.#allMemberships);
        for (var nrow in this.#memberships) {
            if (row == nrow)    // skip checking ourselves
                continue;
            var nmbr = this.#memberships[nrow];
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
        this.redrawRegItems();
        this.buildRegItemButtons();
    }

// addEdit Assist functions
    checkAddEditClose() {
        // TODO: warn about unsaved changes
        console.log("checkAddEditClose: TODO");
        this.#addEditModal.hide();
    }

// update payment data in  cart
    updatePmt(data) {
        if (data.prow) {
            this.#cartPmt.push(data.prow);
        }
        if (data.crow) {
            this.#cartPmt.push(data.crow);
        }
        console.log("updatePmt: TODO");
        /*
        if (data.cart_membership) {
            this.#cart_membership = make_copy(data.cart_membership);
        }

         */
    }

// cart_renumber:
// rebuild the indices in the cartPerinfo and its membership tables
// for shortcut reasons indices are used to allow usage of the filter functions built into javascript
// this rebuilds the index and perinfo cross-reference maps.  It needs to be called whenever the number of items in cart is changed.
    #cart_renumber() {
        var index;
        this.#cartPerinfoMap = new map();
        for (index = 0; index < this.#cartPerinfo.length; index++) {
            this.#cartPerinfo[index].index = index;
            this.#cartPerinfoMap.set(this.#cartPerinfo[index].perid, index);
            for (var rownum in this.#cartPerinfo[index].memberships) {
                this.#cartPerinfo[index].memberships[rownum].index = rownum;
                this.#cartPerinfo[index].memberships[rownum].pindex = index;
            }
        }
    }

    // Clear the coupon matching couponId from all rows in the cart
    clearCoupon(couponId) {
        // clear the discount from the membership rows
        for (var rownum in this.#membershipRows ) {
            var mrow = this.#membershipRows[rownum];
            if (mrow.coupon == couponId) {
                mrow.coupon = null;
                mrow.couponDiscount = 0;
            }
        }
        // remove the discount coupon from the payment
        var delrows = [];
        for (rownum in this.#cartPmt) {
            var prow = this.#cartPmt[rownum];
            if (prow.type == 'discount' && prow.desc.substring(0, 7) == 'Coupon:') {
                delrows.push(rownum);
            }
        }
        // now delete the matching rows (in reverse order)
        delrows = delrows.reverse();
        for (rownum in delrows)
            this.#cartPmt.splice(delrows[rownum], 1);
    }

// format all of the memberships for one record in the cart
    #drawCartRow(rownum) {
        var row = this.#cartPerinfo[rownum];
        var mrow;
        var rowlabel;
        var membership_found = false;
        var membership_html = '';
        var col1 = '';
        var perid = row.perid;
        var btncolor = null;
        // now loop over the memberships in the order retrieved
        var pindex = this.#cartPerinfoMap.get(perid);
        var mrows = this.#cartPerinfo[[pindex]].memberships;
        for (var mrownum in mrows) {
            mrow = mrows[mrownum];
            if (mrow.todelete !== undefined)
                continue;

            var row_shown = true;
            var category = mrow.memCategory;
            if (category == 'yearahead' && mrow.conid == pos.getConid())
                category = 'standard'; // last years yearahead is this year's standard
            var memType = mrow.memType;

            // col1 - status
            switch (mrow.status) {
                case 'paid':
                    col1 = "Pd";
                    break;
                case 'unpaid':
                    col1 = "Upd";
                    break;
                case 'plan':
                    col1 = "Pln";
                    break;
                default:
                    col1 = mrow.status.substring(0, 3);
            }

            var label = mrow.label;
            if (!this.#freezeCart) {
                var notes_count = 0;
                if (mrow.reg_notes_count !== undefined && mrow.reg_notes_count !== null) {
                    notes_count = Number(mrow.reg_notes_count);
                }
                btncolor = 'btn-info';
                if (mrow.new_reg_note !== undefined && mrow.new_reg_note !== '')
                    btncolor = 'btn-warning';
                var btntext = 'Add Note';
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
        <div class="col-sm-2 text-end">` + Number(mrow.price).toFixed(2) + `</div>
        <div class="col-sm-2 text-end">` + Number(mrow.paid).toFixed(2) + `</div>
    </div>
`;
            this.#totalPrice += Number(mrow.price);
            this.#totalPaid += Number(mrow.paid);
            if (mrow.couponDiscount)
                this.#totalPaid += Number(mrow.couponDiscount);
            if (this.#isMembershipTypes.includes(memType))
                membership_found = true;
            if (mrow.status != 'paid') {
                this.#unpaidRows++;
            }
        }
        // first row - member name, remove button
        var rowhtml = '<div class="row mt-1">';
        if (membership_found) {
            rowhtml += '<div class="col-sm-8 text-bg-success">Member: (' + perid + ') ';
        } else {
            rowhtml += '<div class="col-sm-8 text-bg-info">Non Member: (' + perid + ') ';
        }
        rowhtml += row.fullName + '</div>';
        if (!this.#freezeCart) {
            rowhtml += `
        <div class="col-sm-2 text-center"><button type="button" class="btn btn-sm btn-secondary pt-0 pb-0 ps-1 pe-1" onclick="pos.editFromCart(` + perid + `)">Edit</button></div>
        <div class="col-sm-2 text-center"><button type="button" class="btn btn-sm btn-secondary pt-0 pb-0 ps-1 pe-1" onclick="pos.removeFromCart(` + perid + `)">Remove</button></div>
`;
        }
        rowhtml += '</div>'; // end of member name row

        // second row - badge name
        rowhtml += `
    <div class="row">
        <div class="col-sm-3">Badge Name:</div>
        <div class="col-sm-5">` + pos.badgeNameDefault(row.badge_name, row.first_name, row.last_name) + `</div>
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
`;  // end of second row - badge name
        // third row add/edit memberships
        rowhtml += `</div>
    <div class="row">
        <div class="col-sm-auto"><button type="button" class="btn btn-sm btn-primary" onclick="cart.addEditMemberships(` +
            row.index + `);">Add/Edit Memberships</button>
        </div>
    </div>
    `;

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

        var pmt = this.#cartPmt[prow];
        var code = '';
        if (pmt.type == 'check') {
            code = pmt.checkno;
        } else if (pmt.type == 'credit') {
            code = pmt.ccauth;
        }
        return `<div class="row">
    <div class="col-sm-2 p-0">` + pmt.type + `</div>
    <div class="col-sm-6 p-0">` + pmt.desc + `</div>
    <div class="col-sm-2 p-0">` + code + `</div>
    <div class="col-sm-2 text-end">` + Number(pmt.amt).toFixed(2) + `</div>
</div>
`;
    }

// draw/update by redrawing the entire cart
    drawCart() {
        this.#cart_renumber(); // to keep indexing intact, renumber the index and pindex each time
        this.#totalPrice = 0;
        this.#totalPaid = 0;
        var num_rows = 0;
        this.#membershipRows = 0;
        this.#needMembershipRows = 0;
        var html = `
<div class="container-fluid">
<div class="row">
    <div class="col-sm-8 text-bg-primary">Member</div>
    <div class="col-sm-2 text-bg-primary text-end">Price</div>
    <div class="col-sm-2 text-bg-primary text-end">Paid</div>
</div>
`;
        this.#unpaidRows = 0;
        for (var rownum in this.#cartPerinfo) {
            num_rows++;
            html += this.#drawCartRow(rownum);
        }
        this.#totalPrice = Number(this.#totalPrice.toFixed(2));
        this.#totalPaid = Number(this.#totalPaid.toFixed(2));
        html += `<div class="row">
    <div class="col-sm-8 text-end">Total:</div>
    <div class="col-sm-2 text-end">$` + Number(this.#totalPrice).toFixed(2) + `</div>
    <div class="col-sm-2 text-end">$` + Number(this.#totalPaid).toFixed(2) + `</div>
</div>
`;

        if (this.#cartPmt.length > 0) {
            html += `
<div class="row mt-3">
    <div class="col-sm-8 text-bg-primary">Payment</div>
    <div class="col-sm-2 text-bg-primary">Code</div>
    <div class="col-sm-2 text-bg-primary text-end">Amount</div>
</div>
`;
            this.#totalPmt = 0;
            for (var prow in this.#cartPmt) {
                html += this.#drawCartPmtRow(prow);
                this.#totalPmt += Number(this.#cartPmt[prow].amt);
            }
            html += `<div class="row">
    <div class="col-sm-8 p-0 text-end">Payment Total:</div>`;
            this.#totalPmt = Number(this.#totalPmt.toFixed(2));
            html += `
    <div class="col-sm-4 text-end">$` + Number(this.#totalPmt).toFixed(2) + `</div>
</div>
`;
        }
        if (this.#needMembershipRows > 0) {
            var person = this.#needMembershipRows > 1 ? " people" : " person";
            var need = this.#needMembershipRows > 1 ? "need memberships" : "needs a membership";
            html += `<div class="row mt-3">
    <div class="col-sm-12">Cannot proceed to "Review" because ` + this.#needMembershipRows + person + " still " + need +
                `.  Use "Edit" button to add memberships for them or "Remove" button to take them out of the cart.
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
            this.hideStartOver();
        }
        pos.setFindUnpaidHidden(num_rows > 0);
    }

    // create the HTML of the cart into the review data block
    buildReviewData() {
        pos.setMissingItems(0);
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
        var review_missing_items = 0;
        for (rownum in this.#cartPerinfo) {
            tabindex += 100;
            row = this.#cartPerinfo[rownum];
            mrow = pos.find_primary_membership(row.memberships);
            // look up missing fields
            colors = new map();
            for (fieldno in this.#review_required_fields) {
                field = this.#review_required_fields[fieldno];
                if (row[field] == null || row[field] == '') {
                    review_missing_items++;
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
            pos.setMissingItems(review_missing_items);
            html += '<div class="row">';
            if (mrow == null) {
                html += '<div class="col-sm-12 text-bg-info">No Membership</div>';
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
            <input type="text" name='c` + rownum + `-pronouns' id='c` + rownum + `-pronouns' size=80 maxlength="128" placeholder="Pronouns" tabindex="` +
                String(tabindex + 10) +  '" value="' + row.pronouns + `"/>
        </div>
    </div>
    <div class="row">
        <div class="col-sm-auto ms-0 me-2 p-0">
            <input type="text" name='c` + rownum + `-email_addr' id='c` + rownum + `-email_addr' size=64 maxlength="254" placeholder="Email Address" tabindex="` +
                String(tabindex + 14) + '"  value="' + row.email_addr + '" style="background-color:' + colors.get('email_addr') + ';' + `"/>
        </div>
         <div class="col-sm-auto ms-0 me-0 p-0">
            <input type="text" name='c` + rownum + `-phone' id='c` + rownum + `-phone' size=15 maxlength="15" placeholder="Phone Number" tabindex="` +
            String(tabindex + 16) + '" value="' + row.phone + '" style="background-color:' + colors.get('phone') + ';' + `"/>
        </div>
    </div>
    <div class="row">
        <div class="col-sm-auto ms-0 me-0 p-0">
            <input type="text" name='c` + rownum + `-address_1' id='c` + rownum + `-address_1' size=64 maxlength="64" placeholder="Street Address" tabindex="` +
                String(tabindex + 18) + '"  value="' + row.address_1 + '" style="background-color:' + colors.get('address_1') + ';' + `"/>
        </div>
    </div>
    <div class="row">
        <div class="col-sm-auto ms-0 me-0 p-0">
            <input type="text" name='c` + rownum + `-address_2' id='c` + rownum + `-address_2' size=64 maxlength="64" placeholder="2nd line of Address (if needed, such as company)" tabindex="` +
                String(tabindex + 20) + '" value="' + row.address_2 + `"/>
        </div>
    </div>
    <div class="row">
        <div class="col-sm-auto ms-0 me-2 p-0">
            <input type="text" name="c` + rownum + `-city" id='c` + rownum + `-city' size="22" maxlength="32" placeholder="City" tabindex="` + String(tabindex + 22) +
                '" value="' + row.city + '" style="background-color:' + colors.get('city') + ';' + `"/>
        </div>
        <div class="col-sm-auto ms-0 me-2 p-0">
            <input type="text" name="c` + rownum + `-state" id='c` + rownum + `-state' size="10" maxlength="16" placeholder="State" tabindex="` + String(tabindex + 24) +
                '" value="' + row.state + '" style="background-color:' + colors.get('state') + ';' + `"/>
        </div>
        <div class="col-sm-auto ms-0 me-2 p-0">
            <input type="text" name="c` + rownum + `-postal_code" id='c` + rownum + `-postal_code' size="10" maxlength="10" placeholder="Postal Code" tabindex="` + String(tabindex + 26) +
            '" value="' + row.postal_code + '" style="background-color:' + colors.get('postal_code') + ';' + `"/>
        </div>
        <div class="col-sm-auto ms-0 me-0 p-0">
            <select name='c` + rownum + `-country' id='c` + rownum + `-country' tabindex="` + String(tabindex + 28) + `">
                ` + this.#country_select + `
            </select>
        </div>
    </div>
    <div class="row mb-4">
`;

            // policies
            var policies = row.policies;
            for (var polrow in policies) {
                var policyName = policies[polrow].policy;
                var policyResp = policies[polrow].response;
                html += '<div class="col-sm-auto">' + policyName + ': ' +
                    '<input type="checkbox" name="c' + rownum + '-p_' + policyName + '" id="c' + rownum + '-p_' + policyName +
                    '" tabindex="' + String(tabindex + 26) +
                    '" value="Y"' + (policyResp == 'Y' ? ' checked' : ' ') + '/>\n</div>\n';
            }

        html += '\n</div>\n';
        }
    html += `<div class="row mt-2">
        <div class="col-sm-1 m-0 p-0">&nbsp;</div>
        <div class="col-sm-auto m-0 p-0">
            <button class="btn btn-primary btn-sm" type="button" id="review-btn-update" onclick="pos.reviewUpdate();">Update All</button>
            <button class="btn btn-primary btn-sm" type="button" id="review-btn-nochanges" onclick="pos.reviewNoChanges();">No Changes</button>
        </div>
    </div>
    <div class="row">
        <div class="col-sm-12" id="review_status"></div>
    </div>
  </form>
</div>
`;
        return html;
    }

// update the cart from the review block
    updateReviewData() {
        // loop over cart looking for changes in data table
        var rownum = null;
        var el;
        var field;
        var fieldno
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
        }
    }

// update the card with fields provided by the update of the database.  And since the DB is now updated, clear the dirty flags.
    updateFromDB(data) {
        var newrow;
        var cartrow;

        console.log("updateFromDB: TODO");
        /*
        // update the fields created by the database transactions
        var updated_perinfo = data.updated_perinfo;
        for (rownum in updated_perinfo) {
            newrow = updated_perinfo[rownum];
            cartrow = this.#cartPerinfo[newrow.rowpos]
            cartrow.perid = newrow.perid;
            cartrow.dirty = false;
        }
        var updated_membership = data.updated_membership;
        for (rownum in updated_membership) {
            newrow = updated_membership[rownum];
            cartrow = this.#cart_membership[newrow.rowpos];
            //array('rowpos' => $row, 'perid' => $cartrow.perid, 'create_trans' => $master_perid, 'id' => $new_regid);
            cartrow.create_trans = newrow.create_trans;
            cartrow.regid = newrow.id;
            cartrow.perid = newrow.perid;
            cartrow.dirty = false;
        }

// delete all rows from cart marked for delete
        var delrows = [];
        var splicerow = null;
        for (var rownum in this.#cart_membership) {
            if (this.#cart_membership[rownum].todelete == 1) {
                delrows.push(rownum);
            }
        }
        delrows = delrows.reverse();
        for (splicerow in delrows)
            this.#cart_membership.splice(delrows[splicerow], 1);

// redraw the cart with the new id's and maps, which will compute the unpaid_rows.
        cart.drawCart();
        return this.#unpaidRows;

         */
    }

    // update selected element in the country pulldown from the review data screen to the cart
    setCountrySelect() {
        var rownum;
        var row;
        var selid;

        for (rownum in this.#cartPerinfo) {
            row = this.#cartPerinfo[rownum];
            selid = document.getElementById('c' + rownum + '-country');
            selid.value = row.country;
        }
        cart.drawCart();
    }

// receiptHeader - retrieve receipt header info from cart[0]
    receiptHeader(user_id, pay_tid) {
        var d = new Date();
        var payee = (this.#cartPerinfo[0].first_name + ' ' + this.#cartPerinfo[0].last_name).trim();
        return "\nReceipt for payment to " + conlabel + "\nat " + d.toLocaleString() + "\nBy: " + payee + ", Cashier: " + user_id + ", Transaction: " + pay_tid;
    }

// printList - html to display cart elements to print
    printList(new_print) {
        var rownum;
        var crow;
        var mrow;
        var print_html = '';

        for (rownum in this.#cartPerinfo) {
            crow = this.#cartPerinfo[rownum];
            mrow = pos.find_primary_membership(crow.memberships);
            if (mrow == null)
                continue;   // skip anyone without a primary
            mrow = crow.memberships[mrow];
            if (new_print) {
                printed_obj.set(crow.index, 0);
            }
            print_html += `
    <div class="row">
        <div class="col-sm-2 ms-0 me-2 p-0">
            <button class="btn btn-primary btn-sm" type="button" id="pay-print-` + this.#cartPerinfo[rownum].index + `" name="print_btn" onclick="pos.print_badge(` + crow.index + `);">Print</button>
        </div>
        <div class="col-sm-auto ms-0 me-2 p-0">            
            <span class="text-bg-success"> Membership: ` + mrow.label + `</span> (Times Printed: ` +
                mrow.printcount + `)<br/>
              ` + crow.badge_name + '/' + (crow.first_name + ' ' + crow.last_name).trim() + `
        </div>
     </div>`;
        }
        return print_html;
    }

// getBadge = return the cart portions of the parameters for a badge print, that will be added to by the calling routine
    getBadge(index) {

        var row = this.#cartPerinfo[index];
        var printrow = pos.find_primary_membership(row.memberships);
        if (printrow == null)
            return null;

        printrow = row.memberships[printrow];

        var params = {};
        params.type = printrow.memType;
        params.badge_name = row.badge_name;
        params.full_name = (row.first_name + ' ' + row.last_name).trim();
        params.category = printrow.memCategory;
        params.badge_id = row.perid;
        params.day = dayFromLabel(printrow.label);
        params.age = printrow.memAge;
        return params;
    }

    // addToPrintCount: increment the print count for a badge
    addToPrintCount(index) {
        var row = this.#cartPerinfo[index];
        var mrow = pos.find_primary_membership(row.membrerships);
        if (mrow == null) {
            return array(null, 0);
        }

        this.#cartPerinfo[index].memberships[mrow].printcout++;
        var retval = [];
        retval[0] = mrow.regid;
        retval[1] = mrow.printcount;
        return (retval);
    }

    // getEmail: return the email address of an entry
    getEmail(index) {
        return this.#cartPerinfo[index].email_addr;
    }
}
