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
            this.#couponMsgDiv = document.getElementById("couponMsgDiv");
            this.#serialDiv = document.getElementById("serialDiv");
            this.#couponDetailDiv = document.getElementById("couponDetailDiv");
            this.#addCouponBTN = document.getElementById("addCouponBTN");
            this.#couponBTN = document.getElementById("couponBTN");
            this.#couponHeader = document.getElementById("couponHeader");
            this.#removeCouponBTN = document.getElementById("removeCouponBTN");
            //this.#subTotalDiv = document.getElementById("subTotalDiv");
            this.#couponDiv = document.getElementById("couponDiv");
            this.#couponDiscount = document.getElementById("couponDiscount");
            var coupon_modal = document.getElementById('addCoupon');
            if (coupon_modal != null) {
                this.#addCoupon = new bootstrap.Modal(coupon_modal, {focus: true, backdrop: 'static'});
            }

            this.#couponCode.addEventListener('keyup', (e) => {
                if (e.code === 'Enter') addCouponCode();
            });
            this.#couponSerial.addEventListener('keyup', (e) => {
                if (e.code === 'Enter') addCouponCode();
            });

            if (this.#couponCode.value.length > 0) {
                this.AddCouponCode();
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

        return this.#curCoupon['minMemberships'];
    }

    getMaxMemberships() {
        if (this.#curCoupon == null)
            return 999999999;

        if (this.#curCoupon['maxMembersiphs'] == null)
            return 999999999;

        return this.#curCoupon['maxMemberships'];
    }

    getLimitMemberships() {
        if (this.#curCoupon == null)
            return 999999999;

        if (this.#curCoupon['limitMemberships'] == null)
            return 999999999;

        return this.#curCoupon['limitMemberships'];
    }

    getMinCart() {
        if (this.#curCoupon == null)
            return 0;

        if (this.#curCoupon['minTransactions'] == null)
            return 0;

        return this.#curCoupon['minTransaction'];
    }

    getMaxCart() {
        if (this.#curCoupon == null)
            return 999999999;

        if (this.#curCoupon['maxTransaction'] == null)
            return 999999999;

        return this.#curCoupon['maxTransaction'];
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

        return this.#curCoupon['memGroup'];
    }

    // coupon modal area functions
    ModalOpen(cartsize) {
        "use strict";
        this.#lastCartSize = cartsize;
        if (this.#addCoupon != null) {
            if (!this.#couponCode) {
                return;
            }
            this.#addCoupon.show();
            this.#addCouponBTN.disabled = false;
            this.#removeCouponBTN.hidden = !this.#couponActive;
            this.clear_modal_message();
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

    AddCouponCode() {
        "use strict";
        if (!this.#couponCode)  // field not found
            return;

        var couponCodeStr = this.#couponCode.value;
        var couponSerialStr = this.#couponSerial.value;
        if (couponSerialStr == '')
            couponSerialStr = null;

        if (couponCodeStr == '') {
            this.show_modal_message('Please enter a coupon code', 'warn');
            return;
        }

        this.#addCouponBTN.disabled = true;
        this.clear_modal_message();
        // validate the coupon code
        $.ajax({
            url: "scripts/getCouponDetails.php",
            data: { code: couponCodeStr, serial: couponSerialStr },
            method: 'POST',
            success: this.VC_ajax_success,
            error: this.VC_ajax_error
        });
    }

    VC_ajax_error(JqXHR, textStatus, errorThrown) {
        coupon.vc_error(textStatus);
    }

    vc_error(textstatus) {
        "use strict";
        this.show_modal_message(textstatus, 'error');
        this.#addCouponBTN.disabled = false;
    }

    VC_ajax_success(data, textStatus, jqXHR) {
        coupon.vc_success(data)
    }
    vc_success(data) {
        "use strict";

        if (data['error'] !== undefined) {
            this.show_modal_message(data['error'], 'error');
            this.#addCouponBTN.disabled = false;
            return;
        }
        if (data['status'] == 'echo')
            console.log(data);
        if (data['maxRedemption'] != null) {
            if (data['coupon']['maxRedemption'] <= data['coupon']['redeemedCount']) {
                this.show_modal_message('Redemption count exceeded, coupon is no longer valid', 'error');
                this.#addCouponBTN.disabled = false;
                return;
            }
        }

        if (data['coupon']['oneUse'] == 1) {
            if (data['coupon']['guid'] == null) {
                this.ModalOpen(this.#lastCartSize);
                this.#serialDiv.hidden = false;
                if (this.#couponSerial.value == '')
                    this.show_modal_message("Please enter the one use serial number for this coupon", 'success');
                else
                    this.show_modal_message("Invalid serial number, please reenter", 'error');
                return;
            } else {
                if (data['coupon']['usedBy'] != null) {
                    this.ModalOpen(this.#lastCartSize);
                    this.#serialDiv.hidden = false;
                    console.log(this.#curCoupon);
                    this.show_modal_message("This one use coupon has already been redeemed", 'error');
                    return;
                }
            }
        } else {
            this.#serialDiv.hidden = true;
        }

        this.#curCoupon = data['coupon'];
        if (data['mtypes']) {
            mtypes = data['mtypes'];
            // rebuild select list
            var mlist = document.getElementById('memType');
            if (mlist) {
                var html = '';
                for (var row in mtypes) {
                    var mrow = mtypes[row];
                    html += '<option value="' + mrow['memGroup'] + '">' + mrow['label'] + "</option>\n";
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
        this.#couponDiv.hidden = false;
        //this.#subTotalDiv.hidden = false;
        this.#couponBTN.hidden = true;
        this.#couponHeader.innerHTML = "Change/Remove Coupon for Order";
        this.#addCouponBTN.innerHTML = "Change Coupon";
        this.#couponActive = true;
        // recompute the coupon effects on the membership types
        this.UpdateMtypes();
        repriceCart();
        this.#couponError = false;
    }

    RemoveCouponCode() {
        "use strict";
        this.#couponCode.value = '';
        this.#couponSerial.value = '';
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
        this.UpdateMtypes();

        repriceCart();
    }

    // couponDetails - a text line of the restrictions for this coupon
    // fields: minMemberships, maxMemberships, minTransaction, maxTransaction, maxRedemption, redeemedCount
    //
    couponDetails() {
        var html = '';
        var label = 'non zero dollar';

        if (this.#curCoupon['couponType'] == '$mem' || this.#curCoupon['couponType'] == '%mem') {
            html += "<li>This coupon only applies to memberships, not add-ons</li>";
        }
        if (this.#curCoupon['couponType'] == '$off' || this.#curCoupon['couponType'] == '%off') {
            html += "<li>This coupon only applies to the cost of memberships in the cart, not add-ons</li>";
        }
        if (this.#curCoupon['couponType'] == 'price') {
            label = this.#curCoupon['shortname'];
            html += "<li>This coupon applies a special price of " + Number(this.#curCoupon['discount']).toFixed(2) + " to " +
                label + " memberships in the cart.</li>";
        }
        if (this.#curCoupon['minMemberships']) {
            if (this.#curCoupon['minMemberships'] > 1)
                html += '<li>You must buy at least ' + this.#curCoupon['minMemberships'] + " " + label + " memberships</li>\n";
        }
        if (this.#curCoupon['maxMemberships']) {
            html += '<li>This coupon will only discount up to ' + this.#curCoupon['maxMemberships'] + " " + label + " memberships</li>\n";
        }

        if (this.#curCoupon['minTransaction']) {
            html += '<li>Your pre-discount cart value must be at least ' + this.#curCoupon['minTransaction'] + "</li>\n";
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


    // AgeDiscount - compute discount on badges of this agev group
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
            var group = mbrtype['memGroup'];
            shortnames[group] = mbrtype['shortname'];
        }
        return;
    }

    CartDiscount(total) {
        if (!this.#couponActive)
            return 0;

        var discount = 0;
        if (this.#curCoupon['couponType'] == '$off') {
            discount = this.#curCoupon['discount'];
        } else if (this.#curCoupon['couponType'] == '%off') {
            var discountable = total > this.#curCoupon['maxTransaction'] ? this.#curCoupon['maxTransaction'] : total;
            discount = Number(this.#curCoupon['discount']) * discountable;
        }

        if (discount > total) {
            return total;
        }

        return discount;
    }

// modal_message:
// apply colors to the message div and place the text in the div, first clearing any existing class colors
// type:
//  error: (white on red) bg-danger
//  warn: (black on yellow-orange) bg-warning
//  success: (white on green) bg-success
    show_modal_message(message, type) {
        "use strict";
        if (this.#couponMsgDiv === null ) {
            this.#couponMsgDiv = document.getElementById('result_message');
        }
        if (this.#couponMsgDiv.classList.contains('bg-danger')) {
            this.#couponMsgDiv.classList.remove('bg-danger');
        }
        if (this.#couponMsgDiv.classList.contains('bg-success')) {
            this.#couponMsgDiv.classList.remove('bg-success');
        }
        if (this.#couponMsgDiv.classList.contains('bg-warning')) {
            this.#couponMsgDiv.classList.remove('bg-warning');
        }
        if (this.#couponMsgDiv.classList.contains('text-white')) {
            this.#couponMsgDiv.classList.remove('text-white');
        }
        if (message === undefined || message === '') {
            this.#couponMsgDiv.innerHTML = '';
            return;
        }
        if (type === 'error') {
            this.#couponMsgDiv.classList.add('bg-danger');
            this.#couponMsgDiv.classList.add('text-white');
        }
        if (type === 'success') {
            this.#couponMsgDiv.classList.add('bg-success');
            this.#couponMsgDiv.classList.add('text-white');
        }
        if (type === 'warn') {
            this.#couponMsgDiv.classList.add('bg-warning');
        }
        this.#couponMsgDiv.innerHTML = message;
    }
    clear_modal_message() {
        this.show_modal_message('', '');
    }
}
