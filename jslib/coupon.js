// Coupon Class - all functions and data related to processing / displaying a coupon on the reg screen
// Coupons includes: selection, display, calculation, cart support

class Coupon {
// coupon items
    #couponCode = null;
    #couponSerial = null;
    #couponMsgDiv = null;
    #serialDiv = null;
    #addCouponBTN = null;
    #couponBTN = null;
    #couponDetailDiv = null;
    #couponHeader = null;
    #removeCouponBTN = null;
    #addCoupon = null;
    #couponError = false;
    //#subTotalDiv = null;
    #couponDiv = null
    #couponDiscount = null;
    #lastCartSize = 0;

    // portal coupon items
    #couponBlock = null;
    #couponLink = null;

// state items
    #couponActive = false;

// coupon data
    #curCoupon = null;

// initialization
    constructor() {
        "use strict";
// lookup all DOM elements
// ask to load mapping table
        this.#couponCode = document.getElementById("couponCode");
        if (this.#couponCode) {
            this.#couponSerial = document.getElementById("couponSerial");
            this.#couponLink = document.getElementById("couponLink");
            this.#couponMsgDiv = "couponMsgDiv";
            this.#serialDiv = document.getElementById("serialDiv");
            this.#couponDetailDiv = document.getElementById("couponDetailDiv");
            this.#couponHeader = document.getElementById("couponHeader");
            this.#removeCouponBTN = document.getElementById("removeCouponBTN");
            //this.#subTotalDiv = document.getElementById("subTotalDiv");
            this.#couponDiv = document.getElementById("couponDiv");
            this.#couponDiscount = document.getElementById("couponDiscount");
            this.#couponBlock = document.getElementById('couponBlock');

            // two different forms use this library...
            this.#couponBTN = document.getElementById("couponBTN");
            if (this.#couponBTN == null)
                this.#couponBTN = document.getElementById("addCouponButton");
            this.#addCouponBTN = document.getElementById("addCouponBTN");
            if (this.#addCouponBTN == null)
                this.#addCouponBTN = document.getElementById("acSubmitButton");

            var id = document.getElementById('addCoupon');
            if (id == null) {
                id = document.getElementById("couponApplyModal");
            }
            if (id != null)
                this.#addCoupon = new bootstrap.Modal(id, {focus: true, backdrop: 'static'});
            }

        if (this.#couponSerial) {
            this.#couponSerial.addEventListener('keyup', (e) => {
                if (e.code === 'Enter') coupon.addCouponCode();
            });
        }

        if (this.#couponLink) {
            this.#couponLink.addEventListener('keyup', (e) => {
                if (e.code === 'Enter') coupon.addCouponCode();
            });
        }

        if (this.#couponCode) {
            this.#couponCode.addEventListener('keyup', (e) => {
                if (e.code === 'Enter') coupon.addCouponCode();
            });
            if (this.#couponCode.value.length > 0) {
                this.addCouponCode();
                this.#couponError = !this.#couponActive;
            }
        }
    }

    // get functions

    couponError() {
        return this.#couponError;
    }

    isCouponActive() {
        return this.#couponActive;
    }
    getMinMemberships() {
        if (this.#curCoupon == null)
            return 0;

        if (this.#curCoupon['minMemberships'] == null)
            return 0;

        return Number(this.#curCoupon['minMemberships']);
    }

    getMaxMemberships() {
        if (this.#curCoupon == null)
            return 999999999;

        if (this.#curCoupon['maxMembersiphs'] == null)
            return 999999999;

        return Number(this.#curCoupon['maxMemberships']);
    }

    getLimitMemberships() {
        if (this.#curCoupon == null)
            return 999999999;

        if (this.#curCoupon['limitMemberships'] == null)
            return 999999999;

        return Number(this.#curCoupon['limitMemberships']);
    }

    getMinCart() {
        if (this.#curCoupon == null)
            return 0;

        if (this.#curCoupon['minTransaction'] == null)
            return 0;

        return Number(this.#curCoupon['minTransaction']);
    }

    getMaxCart() {
        if (this.#curCoupon == null)
            return 999999999;

        if (this.#curCoupon['maxTransaction'] == null)
            return 999999999;

        return Number(this.#curCoupon['maxTransaction']);
    }

    getCouponCode() {
        if (this.#curCoupon == null)
            return null;

        return this.#curCoupon['code'];
    }

    getCouponSerial() {
        if (this.#curCoupon == null)
            return null;

        return this.#curCoupon['guid'];
    }
    getMemGroup() {
        if (this.#curCoupon == null)
            return null;

        if (this.#curCoupon['memId'] == null)
            return null;

        return this.#curCoupon['memId'];
    }

    // coupon modal area functions
    ModalOpen(cartsize) {
        this.#lastCartSize = cartsize;
        if (this.#addCoupon != null) {
            if (!this.#couponCode) {
                return;
            }
            this.#addCoupon.show();
            this.#addCouponBTN.disabled = false;
            if (this.#removeCouponBTN)
                this.#removeCouponBTN.hidden = !this.#couponActive;

            clear_message(this.#couponMsgDiv);
        }
    }

    // showNewBadge == true if the cart is empty and the add badges modal should be shown.
    ModalClose(showNewBadge = false) {
        if (this.#addCoupon != null) {
            this.#addCoupon.hide();
        }
        if (showNewBadge)
            newBadge.show();
    }

    addCouponCode() {
        "use strict";

        var couponCodeStr, couponSerialStr, couponLinkStr;

        if (!this.#couponCode)  // field not found
            return;

        couponCodeStr = this.#couponCode.value;
        if (this.#couponSerial) {
            couponSerialStr = this.#couponSerial.value;
        } else {
            couponSerialStr = null;
        }

        if (this.#couponLink) {
            couponLinkStr = this.#couponLink.value;
            var parts = couponLinkStr.split('?offer=');
            if (parts.length > 1) {
                couponLinkStr = parts[1];
            } else {
                couponLinkStr = parts[0];
            }
            try {
                couponLinkStr = atob(couponLinkStr);
            } catch (e) {
                show_message('Coupon Link Code invalid', 'warn', this.#couponMsgDiv);
                return;
            }
            if (couponLinkStr) {
                var parts = couponLinkStr.split('~!~');
                couponCodeStr = parts[0];
                couponSerialStr = parts[1];
            }
        }

        if (couponSerialStr == '')
            couponSerialStr = null;

        if (couponCodeStr == '') {
            show_message('Please enter a coupon code', 'warn', this.#couponMsgDiv);
            return;
        }

        this.#addCouponBTN.disabled = true;
        clear_message(this.#couponMsgDiv);
        // validate the coupon code
        $.ajax({
            url: "scripts/getCouponDetails.php",
            data: { code: couponCodeStr, serial: couponSerialStr },
            method: 'POST',
            success: this.VC_ajax_success,
            error: coupon.VC_ajax_error
        });
    }

    VC_ajax_error(JqXHR, textStatus, errorThrown) {
        coupon.vc_error(JqXHR, textStatus, errorThrown);
    }

    vc_error(JqXHR, textStatus, errorThrown) {
        "use strict";
        showAjaxError(JqXHR, textStatus, errorThrown, this.#couponMsgDiv);
        this.#addCouponBTN.disabled = false;
    }

    VC_ajax_success(data, textStatus, jqXHR) {
        coupon.vc_success(data)
    }
    vc_success(data) {
        "use strict";

        if (data['status'] == 'error') {
            show_message(data['error'], 'error', this.#couponMsgDiv);
            this.#addCouponBTN.disabled = false;
            return;
        }
        if (data['status'] == 'echo')
            console.log(data);
        if (data['maxRedemption'] != null) {
            if (data['coupon']['maxRedemption'] <= data['coupon']['redeemedCount']) {
                show_message('Redemption count exceeded, coupon is no longer valid', 'error', this.#couponMsgDiv);
                this.#addCouponBTN.disabled = false;
                return;
            }
        }

        if (data['coupon']['oneUse'] == 1) {
            if (data['coupon']['guid'] == null) {
                this.ModalOpen(this.#lastCartSize);
                this.#serialDiv.hidden = false;
                if (this.#couponSerial.value == '')
                    show_message("Please enter the one use serial number for this coupon", 'success', this.#couponMsgDiv);
                else
                    show_message("Invalid serial number, please reenter", 'error', this.#couponMsgDiv);
                return;
            } else {
                if (data['coupon']['usedBy'] != null) {
                    this.ModalOpen(this.#lastCartSize);
                    this.#serialDiv.hidden = false;
                    console.log(this.#curCoupon);
                    show_message("This one use coupon has already been redeemed", 'error', this.#couponMsgDiv);
                    return;
                }
            }
        } else {
            this.#serialDiv.hidden = true;
        }

        this.#curCoupon = data['coupon'];
        if (data['mtypes']) {
            var mtypes = data['mtypes'];
            // rebuild select list
            var mlist = document.getElementById('memType');
            if (mlist) {
                var html = '';
                for (var row in mtypes) {
                    var mrow = mtypes[row];
                    html += '<option value="' + mrow['id'] + '">' + mrow['label'] + "</option>\n";
                }
                mlist.innerHTML = html;
            }
        }
        this.ModalClose(this.#lastCartSize == 0);
        //console.log("coupon data:");
        //console.log(this.#curCoupon);
        // now apply the coupon to the screen
        // set coupon code
        this.#couponDetailDiv.innerHTML = this.couponDetails();
        //this.#subTotalDiv.hidden = false;
        this.#couponBTN.hidden = true;
        this.#couponDiv.hidden = false;
        this.#couponHeader.innerHTML = "Change/Remove Coupon for Order";
        this.#addCouponBTN.innerHTML = "Change Coupon";
        this.#couponActive = true;
        // recompute the coupon effects on the membership types
        if (typeof mtypes !== 'undefined') {
            // online reg
            this.UpdateMtypes();
            repriceCart();
        } else {
            // portal
            show_message("Need to reprice cart", 'warn');
        }
        this.#couponError = false;
    }

    RemoveCouponCode() {
        "use strict";
        if (this.#couponCode)
            this.#couponCode.value = '';
        if (this.#couponSerial)
            this.#couponSerial.value = '';
        if (this.#couponLink.value)
            this.#couponLink.value = '';
        if (this.#serialDiv)
            this.#serialDiv.hidden = true;
        this.#couponDetailDiv.innerHTML = "";
        this.#couponDiv.hidden = true;
        //this.#subTotalDiv.hidden = true;
        this.#couponBTN.hidden = false;
        this.#couponHeader.innerHTML = "Add Coupon to Order";
        this.#addCouponBTN.innerHTML = "Add Coupon";
        this.ModalClose(this.#lastCartSize == 0);
        this.#curCoupon = null;
        this.#couponActive = false;
        if (typeof mtypes !== 'undefined') {
            this.UpdateMtypes();
            repriceCart();
        } else {
            show_message("Need to reprice cart", 'warn');
        }
    }

    // couponDetails - a text line of the restrictions for this coupon
    // fields: minMemberships, maxMemberships, minTransaction, maxTransaction, maxRedemption, redeemedCount
    //
    couponDetails() {
        var html = '';
        var label = 'non zero dollar';

        if (this.#curCoupon['couponType'] == '$mem' || this.#curCoupon['couponType'] == '%mem') {
            html += "<li>This coupon only applies to memberships, not add-ons</li>";
            if (this.#curCoupon['couponType'] == '$mem') {
                html += "<li>This coupon provides a $" + Number(this.#curCoupon['discount']).toFixed(2) + " discount on primary memberships.</li>";
            } else {
                html += "<li>This coupon provides a " + String(this.#curCoupon['discount']) + "% discount on primary memberships.</li>";
            }
        }
        if (this.#curCoupon['couponType'] == '$off' || this.#curCoupon['couponType'] == '%off') {
            html += "<li>This coupon only applies to the cost of memberships in the cart, not add-ons</li>";
            if (this.#curCoupon['couponType'] == '$off') {
                html += "<li>This coupon provides a $" + Number(this.#curCoupon['discount']).toFixed(2) + " discount off the total of primary memberships in the cart.</li>";
            } else {
                html += "<li>This coupon provides a " + String(this.#curCoupon['discount']) + "% discount off the total of primary memberships in the cart.</li>";
            }
        }
        if (this.#curCoupon['couponType'] == 'price') {
            label = this.#curCoupon['shortname'];
            html += "<li>This coupon applies a special price of " + Number(this.#curCoupon['discount']).toFixed(2) + " to " +
                label + " memberships in the cart.</li>";
        }
        if (this.getMinMemberships() > 1) {
            html += '<li>You must buy at least ' + this.getMinMemberships() + " " + label + " memberships</li>\n";
        }
        if (this.#curCoupon['maxMemberships']) {
            html += '<li>This coupon will only discount up to ' + this.#curCoupon['maxMemberships'] + " " + label + " memberships</li>\n";
        }

        if (this.getMinCart()) {
            html += '<li>Your pre-discount cart value must be at least ' + this.getMinCart() + "</li>\n";
        }
        if (this.#curCoupon['maxTransaction']) {
            html += '<li>The discount will only apply to the first ' + this.#curCoupon['maxTransaction'] + " of the cart</li>\n";
        }
        
        if (this.#curCoupon['memId']) {
            html += '<li>Only valid on ';
            var plural = 's'
            if (this.#curCoupon['limitMemberships']) {
                if (this.#curCoupon['limitMemberships'] == 1) {
                    html += 'one ';
                    plural = '';
                } else {
                    html += this.#curCoupon['limitMemberships'] + ' ';
                }
            } else
                html += ''
            html += this.#curCoupon['shortname'] + ' membership' + plural + "</li>\n";
        }

        return "Coupon Details for coupon code '" + this.#curCoupon['code'] + "': " + this.#curCoupon['name'] + "\n<ul>\n" + html + "</ul>\n";
    }


    // UpdateMtypes (age types) - compute discount on badges of this age group
    // returns text string to add to display for discount in price
    UpdateMtypes() {
        for (var row in mtypes) {
            var mbrtype = mtypes[row];
            var primary = true; // if coupon is active, does this 'num' count toward min / max memberships
            var discount = 0;

            // first compute primary membership
            if (this.#couponActive) {
                if (this.#curCoupon['memId'] == mbrtype['id']) {  // ok this is a forced primary
                    primary = true; // need a statement here, as combining the if's gets difficult
                } else if (mbrtype['price'] == 0 || (mbrtype['memCategory'] != 'standard' && mbrtype['memCategory'] != 'virtual')) {
                    primary = false;
                }
            }

            // now compute the discount
            if (!this.#couponActive) {
                discount = 0; // no discount if no coupon, price is 0 or its not a primary membership
            } else if (this.#curCoupon['couponType'] == '$off' || this.#curCoupon['couponType'] == '%off') {
                discount = 0; // cart type memberships don't discount rows
            } else if (this.#curCoupon['memId'] == null || this.#curCoupon['memId'] == mbrtype['id']) { // ok, we have a coupon type that applies to this row
                if (this.#curCoupon['couponType'] == 'price') {
                    // set price for a specific membership type, set the discount to the difference between the real price and the 'coupon price'
                    discount = Number(mbrtype['price']) - Number(this.#curCoupon['discount']);
                } else if (this.#curCoupon['couponType'] == '$mem') {
                    // flat $ discount on the primary membership
                    discount = Number(this.#curCoupon['discount']);
                } else if (this.#curCoupon['couponType'] == '%mem') {
                    // % off primaary membership set price.
                    discount = (Number(mbrtype['price']) * Number(this.#curCoupon['discount']) / 100.0);
                }
                // if the discount is > than the price limit it to the price.
                if (Number(discount) > Number(mbrtype['price'])) {
                    discount = Number(mbrtype['price']);
                }
            }
            mbrtype['primary'] = primary;
            mbrtype['discount'] = Number(discount).toFixed(2);
            mbrtype['discountable'] = discount > 0;
            var group = mbrtype['id'];
            shortnames[group] = mbrtype['shortname'];
        }
        return;
    }

    CartDiscount(total) {
        if (!this.#couponActive)
            return 0;

        if (total < this.getMinCart()) {
            return 0;
        }

        var discount = 0;
        if (this.#curCoupon['couponType'] == '$off') {
            discount = this.#curCoupon['discount'];
        } else if (this.#curCoupon['couponType'] == '%off') {
            var amountDiscountable = total > this.getMaxCart() ? this.getMaxCart() : total;
            discount = Number(Number(this.#curCoupon['discount']) * amountDiscountable / 100).toFixed(2);
        }

        if (discount > total) {
            return total;
        }

        return discount;
    }
}

/*


    // check if the coupon code or link is valid
    applyCouponSubmit() {
        var code = this.#couponCodeField.value;
        var link = this.#couponlinkField.value;
        var script = 'scripts/couponGetDetails.php';
        var data = {
            action: 'lookup',
            code: 'code',
            link: 'link',
        };
        $.ajax({
            method: 'POST',
            url: script,
            data: data,
            success: function (data, textStatus, jqXhr) {
                if (data['status'] == 'error') {
                    show_message(data['message'], 'error', 'acMessageDiv');
                } else if (data['status'] == 'warn') {
                    show_message(data['message'], 'warn', 'acMessageDiv'');
                } else {
                    portal.applyCouponSubmitSuccess(data);
                }
            },
            error: function (jqXHR, textStatus, errorThrown) {
                showAjaxError(jqXHR, textStatus, errorThrown);
                return false;
            },
        });
    }

    // continue with apply coupon, got the details, not draw the block explaining the coupon
    applyCouponSubmitSuccess(data) {
        var html = `
        <div class="row">
            <div class="col-sm-12">Coupon Details for coupon code <b>` + data['code'] + '</b>: ' + data['name'] + `</div>
`       </div>
        <div class="row">
            <div class="col-sm-12"
        </div>

`;
        this.#couponBlock.innerHTML = html;
        this.#couponApplyModal.hide();
    }
 */