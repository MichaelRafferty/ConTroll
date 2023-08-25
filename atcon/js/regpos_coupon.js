// Coupon Class - all functions and data related to processing / displaying a coupon on the reg screen
// Coupons includes: selection, display, calculation, cart support

class Coupon {
// coupon items
    #couponError = false;

// state items
    #couponActive = false;

// coupon data
    #curCoupon = null;
    #mtypes = null;

// initialization
    constructor() {
        "use strict";
        this.#couponActive = false;
    };

    couponError() {
        return this.#couponError;
    }

    isCouponActive() {
        return this.#couponActive;
    }

    getCouponId() {
        if (this.#curCoupon == null)
            return null;

        return Number(this.#curCoupon['id']);
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

        return this.#curCoupon['memGroup'];
    }

    getNameString() {
        if (this.#curCoupon == null)
            return '';

        return this.#curCoupon['code'] + ' (' + this.#curCoupon['name'] + ')';
    }

    LoadCoupon(couponId) {
        "use strict";

        // get the coupon data
        var postData = {
            ajax_request_action: 'getCouponDetails',
            couponId: couponId,
        };
        $.ajax({
            url: "scripts/regpos_getCouponDetails.php",
            data: postData,
            method: 'POST',
            success: this.VC_ajax_success,
            error: this.VC_ajax_error
        });
    }

    VC_ajax_error(JqXHR, textStatus, errorThrown) {
        show_message(textStatus, 'error');
    }

    VC_ajax_success(data, textStatus, jqXHR) {
        coupon.vc_success(data)
    }

    vc_success(data) {
        "use strict";

        if (data['status'] == 'error') {
            show_message(data['error'], 'error');
            return;
        }
        if (data['status'] == 'echo')
            console.log(data);
        if (data['maxRedemption'] != null) {
            if (data['coupon']['maxRedemption'] <= data['coupon']['redeemedCount']) {
                show_message('Redemption count exceeded, coupon is no longer valid', 'error');
                return;
            }
        }

        this.#curCoupon = data['coupon'];
        this.#couponActive = true;
        this.#mtypes = data['mtypes'];
        this.#couponError = false;
        clear_message();
        this.UpdateMtypes();
        cart_total = (cart.getTotalPrice() - cart.getTotalPaid()).toFixed(2);
        coupon_discount = coupon.CartDiscount();
        var total_amount_due = (cart_total - coupon_discount).toFixed(2);

        // add coupon discount as payment row
        var prow = {
            index: cart.getPmtLength(), amt: coupon_discount, ccauth: null, checkno: null, desc: 'Coupon: ' + coupon.getCouponCode(), type: 'discount',
            coupon: coupon.getCouponId(),
        };
        pay('', prow);
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


    // UpdateMtypes (age type) - compute discount on badges of this age group
    // returns text string to add to display for discount in price
    UpdateMtypes() {
        for (var row in this.#mtypes) {
            var mbrtype = this.#mtypes[row];
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
        }
        return;
    }

    // determine if the coupon's minimum requirements have been met
    CouponMet() {
        if (!this.#couponActive)
            return false;

        var numMemberships = 0;
        var minMemberships = this.getMinMemberships();


        var memberships = cart.getCartMembership();
        var numMbrId = 0;
        var mbrId = this.#curCoupon['memId'];
        var mbrprice = 0;
        for (var mrownum in memberships) {
            var mrow = memberships[mrownum];
            if (mrow['memId'] == mbrId && (mrow['coupon'] == null || mrow['coupon'] == '')) {
                numMbrId++;
                numMemberships++;
                mbrprice += mrow['price'];
            } else {
                var mtype = this.#mtypes[mrow['memId']];
                if (mtype['primary'] && (mrow['coupon'] == null || mrow['coupon'] == '')) {
                    mbrprice += mrow['price'];
                    numMemberships++;
                }
            }
        }

        if (numMemberships < minMemberships) // check for min in cart
            return false;

        if (mbrprice < this.getMinCart()) // not enough purchased of primary memberships
            return false;

        return true;
    }
    CartDiscount() {
        if (!this.CouponMet())
            return 0;

        var discount = 0;
        var cart_total_price = cart.getTotalPrice();

        if (this.#curCoupon['couponType'] == '$off') {
            discount = this.#curCoupon['discount'] < cart_total_price ? this.#curCoupon['discount'] : cart_total_price;
            // mark reg as coupon as coupon applied
        } else if (this.#curCoupon['couponType'] == '%off') {
            var amountDiscountable = cart_total_price > this.getMaxCart() ? this.getMaxCart() : cart_total_price;
            discount = Number(Number(this.#curCoupon['discount']) * amountDiscountable / 100).toFixed(2);
        }
        var memberships = cart.getCartMembershipRef();
        for (var mrownum in memberships) {
            var mrow = memberships[mrownum];
            var mtype = this.#mtypes[mrow['memId']];
            if (mtype['primary'] && (mrow['coupon'] == null || mrow['coupon'] == '') && mrow['couponDiscount'] == 0) {
                var rowdiscount = Number(mtype['discount']);
                mrow['couponDiscount'] = rowdiscount;
                mrow['coupon'] = this.getCouponId();
                discount += rowdiscount;
            }
        }

        if (discount > cart_total_price) {
            return Number(cart_total_price).toFixed(2);
        }

        return Number(discount).toFixed(2);
    }
}
