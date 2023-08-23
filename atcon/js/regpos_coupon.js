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

        return this.#curCoupon['id'];
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

        if (this.#curCoupon['minTransaction'] == null)
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
        this.#mtypes = data['mtypes'];
        this.#couponError = false;
        clear_message();
        pay_shown();
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


    // AgeDiscount - compute discount on badges of this agev group
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
            var group = mbrtype['memGroup'];
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
