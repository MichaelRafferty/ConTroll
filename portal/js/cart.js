// addUpdate javascript, also requires base.js

var membership = null;

// initial setup
window.onload = function () {
    cart = new Cart();
}

class Cart {
    // current person info
    #personInfo = [];

    // age items
    #currentAge = null; // age of the person from perinfo/newperson

    // membership items
    #memberships = null;
    #allMemberships = null;
    #membershipButtonsDiv = null;
    #newMembershipSave = null;
    #primaryColorMemberships = ['standard', 'wsfs', 'supplement','yearahead'];

    // cart items
    #cartDiv = null;
    #cartContentsDiv = null;
    #totalDue = 0;
    #countMemberships = 0;
    #unpaidMemberships = 0;
    #newIDKey = -1;
    #saveCartBtn = null;
    #cartChanges = 0;

    // flow items
    #auHeader = null;
    #getNewMembershipDiv = null;
    #leaveBeforeChanges = true;
    #debug = 0;

    // variable price items
    #amountField = null;
    #vpModal = null;
    #vpBody = null;

    constructor() {
        if (config.debug)
            this.#debug = config.debug;
        this.#currentAge = person.currentAgeType;
        this.#memberships = person.memberships;
        this.#allMemberships = person.allMemberships;

        this.#auHeader = document.getElementById("auHeader");
        // set up div elements
        this.#membershipButtonsDiv = document.getElementById("membershipButtons");
        this.#getNewMembershipDiv = document.getElementById("getNewMembershipDiv");
        this.#cartDiv = document.getElementById("cartDiv");
        this.#cartContentsDiv = document.getElementById("cartContentsDiv");

        this.#saveCartBtn = document.getElementById("saveCartBtn");
        var id = document.getElementById("variablePriceModal");
        if (id) {
            this.#vpModal = new bootstrap.Modal(id, {focus: true, backdrop: 'static'});
            id.addEventListener('hidden.bs.modal', amountModalHiddenHelper);
            this.#vpBody = document.getElementById("variablePriceBody");
        }
        this.updateCart();
        this.buildMembershipButtons();
    }

    buildMembershipButtons() {
        // now loop over memList and build each button
        var html = '';
        var rules = new MembershipRules(config.conid, this.#currentAge, this.#memberships, this.#allMemberships);

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
                html += '<div class="col-sm-2 mt-1 mb-1"><button id="memBtn-' + mem.id + '" class="btn btn-sm btn-primary h-100 w-100"' +
                    ' onclick="cart.membershipAdd(' + "'" + mem.id + "'" + ')">' +
                    (mem.conid != config.conid ? mem.conid + ' ' : '') + memLabel + '</button></div>' + "\n";
                }
        }
        this.#membershipButtonsDiv.innerHTML = html;
    }

    // goto step: handle going directly to a step:
    gotoStep(step, ignoreSkip = false) {
        clear_message();

    }

    // cart functions
    // updateCart - redraw the items in the cart
    updateCart() {
        this.#totalDue = 0;
        this.#countMemberships = 0;
        this.#unpaidMemberships = 0;
        var html = `
            <div class="row">
                <div class="col-sm-2"><b>Remove/Delete</b></div>
                <div class="col-sm-1" style='text-align: right;'><b>Status</b></div>
                <div class="col-sm-1" style='text-align: right;'><b>Price</b></div>
                <div class="col-sm-4"><b>Membership</b></div>
            </div>
`;
        var col1 = '';
        var now = new Date();
        for (var row in this.#memberships) {
            var membershipRec = this.#memberships[row];
            if (membershipRec.status != 'in-cart' && membershipRec.status != 'unpaid')
                continue;

            this.#countMemberships++;
            var amount_due = Number(membershipRec.price) - (Number(membershipRec.paid) + Number(membershipRec.couponDiscount));
            var label = (membershipRec.conid != config.conid ? membershipRec.conid + ' ' : '') + membershipRec.label +
                (membershipRec.memAge != 'all' ? (' ' + ageListIdx[membershipRec.memAge].label) : '');
            var expired = false;
            if ((membershipRec.status == 'unpaid' || membershipRec.status == 'in-cart') && !membershipRec.toDelete)
                this.#totalDue += amount_due;
            if (membershipRec.status == 'unpaid' && membershipRec.paid == 0) {
                var sd = new Date(membershipRec.startdate);
                var ed = new Date(membershipRec.enddate);
                if (membershipRec.online == 'N' || sd.getTime() > now.getTime() || ed.getTime() < now.getTime()) {
                    expired = true;
                    label = "<span class='text-danger'><b>Expired: </b>" + label + "</span>";
                }
            }

            var strike = false
            var btncolor = expired ? 'btn-danger' : 'btn-secondary';
            col1 = membershipRec.create_date;
            if (membershipRec.toDelete) {
                strike = true;
                if (!expired) {
                    col1 = '<button class="btn btn-sm btn-secondary pt-0 pb-0" onclick="cart.membershipRestore(' +
                        row + ')">Restore</button>';
                }
            } else if (membershipRec.status == 'unpaid' && membershipRec.price > 0 && membershipRec.paid == 0) {
                col1 = '<button class="btn btn-sm ' + btncolor + ' pt-0 pb-0" onclick="cart.membershipDelete(' + row + ')">Delete</button>';
            } else if (membershipRec.status == 'in-cart') {
                col1 = '<button class="btn btn-sm btn-secondary pt-0 pb-0" onclick="cart.membershipRemove(' + row + ')">Remove</button>';
            }
            html += `
    <div class="row">
        <div class="col-sm-2">` + col1 + `</div>
        <div class="col-sm-1" style='text-align: right;'>` + (strike ? '<s>' : '') + membershipRec.status + (strike ? '</s>' : '') + `</div>
        <div class="col-sm-1" style='text-align: right;'>` + (strike ? '<s>' : '') + membershipRec.price + (strike ? '</s>' : '') + `</div>
        <div class="col-sm-8">` + (strike ? '<s>' : '') + label + (strike ? '</s>' : '') + `
        </div>
    </div>
`;
        }
        if (this.#totalDue > 0) {
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
        <div class="col-sm-1" style='text-align: right;'><b>$` + Number(this.#totalDue).toFixed(2)+ `</b></div>
    </div>`
        }
        if (this.#countMemberships == 0) {
            html = "Nothing in the Cart";
        }
        this.#cartContentsDiv.innerHTML = html;
        this.#saveCartBtn.innerHTML = "Save Cart and Return to the Home Page to Pay or Add A New Member"
    }

    // add to cart
    membershipAdd(id) {
        clear_message();
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

    amountEventListener(e) {
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
        this.updateCart();
        this.buildMembershipButtons();
    }

    // remove an unsaved membership row from the cart
    membershipRemove(row) {
        clear_message();
        if (this.#memberships == null) {
            show_message("No memberships found", "warn");
            return;
        }

        var mbr = this.#memberships[row];
        if (mbr.status != 'in-cart') {
            show_message("Cannot remove that membership, only in-cart memberships can be removed.", "warn");
            return
        }

        // check if anything else in the cart depends on this membership
        // trial the delete
        mbr.toDelete = true;
        var rules = new MembershipRules(config.conid, this.#currentAge, this.#memberships, this.#allMemberships);
        for (var nrow in this.#memberships) {
            if (row == nrow)    // skip checking ourselves
                continue;
            var nmbr = this.#memberships[nrow];
            if (nmbr.toDelete)
                continue;
            if (rules.testMembership(nmbr, true) == false) {
                mbr.toDelete = undefined;
                show_message("You cannot remove " + mbr.label + " because " + nmbr.label + " requires it.  You must delete/remove " + nmbr.label + " first.", 'warn');
                return;
            }
        }

        this.#memberships.splice(row, 1);
        this.#cartChanges--;
        this.updateCart();
        this.buildMembershipButtons();
    }

    // mark an unpaid membership row to be deleted on save
    membershipDelete(row) {
        clear_message();
        if (this.#memberships == null) {
            show_message("No memberships found", "warn");
            return;
        }

        var mbr = this.#memberships[row];
        if (mbr.status != 'unpaid') {
            show_message("Cannot remove that membership, only unpaid membershipd can be deleted.", "warn");
            return
        }

        if (mbr.price == 0) {
            show_message("Please contact registration at " + config.regadminemail + "  to delete free memberships.", "warn");
            return;
        }

        if (mbr.paid > 0) {
            show_message("Please contact registration at " + config.regadminemail + " to resolve this partially paid membership.", "warn");
            return;
        }

        // check if anything else in the cart depends on this membership
        // trial the delete
        mbr.toDelete = true;
        var rules = new MembershipRules(config.conid, this.#currentAge, this.#memberships, this.#allMemberships);
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
                show_message("You cannot delete " + mbr.label + " because " + nmbr.label + " requires it.  You must delete/remove " + nmbr.label + " first.", 'warn');
            }
            nmbr.toDelete = undefined;
        }

        this.#cartChanges++;
        this.updateCart();
        this.buildMembershipButtons();
    }

    membershipRestore(row) {
        clear_message();
        if (this.#memberships == null) {
            show_message("No memberships found", "warn");
            return;
        }

        var mbr = this.#memberships[row];
        if (!mbr.toDelete) {
            show_message("Cannot restore this membership, it is not marked deleted.", "warn");
            return
        }

        var rules = new MembershipRules(config.conid, this.#currentAge, this.#memberships, this.#allMemberships);
        if (rules.testMembership(mbr, false) == false) {
            show_message("You cannot restore " + mbr.label + " because it requires some other deleted membership. Look at your memberships marked 'Restore'" +
                " and restore its prerequesite", "warn");
        } else {
            mbr.toDelete = undefined;
        }
        this.#cartChanges--;
        this.updateCart();
        this.buildMembershipButtons();
    }

    findInCart(memId) {
        if (!this.#memberships)
            return null; // no list to search

        for (var row in this.#memberships) {
            var cartrow = this.#memberships[row];
            if (memId != cartrow.memId)
                continue;
            return cartrow;  // return matching entry
        }
        return null; // not found
    }

    // save cart / return home button
    saveCart() {
        var _this = this;
        if (this.#cartChanges == 0) {
            // go back to the home page
            this.#leaveBeforeChanges = false;
            window.location = "portal.php?messageFwdmessageFwd=" + encodeURI("No Changes");
            return;
        }
        this.#saveCartBtn.disabled = true;

        var script = 'scripts/updateFromCart.php';
        var data = {
            action: 'updateCart',
            loginId: config.id,
            loginType: config.idType,
            cart: JSON.stringify(this.#memberships),
            person: JSON.stringify(person),
        }

        $.ajax({
            method: 'POST',
            url: script,
            data: data,
            success: function (data, textStatus, jqXhr) {
                checkResolveUpdates(data);
                if (data.status == 'error') {
                    show_message(data.message, 'error');
                    _this.#saveCartBtn.disabled = false;
                } else if (data.status == 'warn') {
                    show_message(data.message, 'warn');
                    _this.#saveCartBtn.disabled = false;
                } else {
                    if (config.debug & 1)
                        console.log(data);
                    cart.saveCartComplete(data);
                }
            },
            error: function (jqXHR, textStatus, errorThrown) {
                showAjaxError(jqXHR, textStatus, errorThrown);
                _this.#saveCartBtn.disabled = false;
                return false;
            }
        });
    }

    saveCartComplete(data) {
        // once saved, return home
        this.#leaveBeforeChanges = false;
        var location = "portal.php";
        if (data.message) {
            window.location = location + '?messageFwd=' + encodeURI(data.message);
        } else {
            window.location = location+ '?messageFwd=' + encodeURI("No Changes");
        }
        return;
    }

    // if they haven't used the save/return button, ask if they want to leave
    confirmExit(event) {
        if (this.#leaveBeforeChanges) {
            var buttonName = this.#saveCartBtn.innerHTML;
            event.preventDefault(); // if the browser lets us set our own variable
            if (!confirm("You are leaving without saving any changes you have made to your cart.\n" +
                "Do you wish to leave anyway discarding any potential changes?")) {
                return false;
            }
        }

        return true;
    }

    discardCart() {
        let data = [];
        this.saveCartComplete(data);
    }
}

function amountModalHiddenHelper(event) {
    cart.amountModalHidden(event);
}
