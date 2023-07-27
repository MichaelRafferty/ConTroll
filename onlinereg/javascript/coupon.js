// Coupon Class - all functions and data related to processing / displaying a coupon on the reg screen
// Coupons includes: selection, display, calculation, cart support

class Coupon {
// coupon items
    #couponCode = null;
    #couponMsgDiv = null;
    #addCouponBTN = null;
    #couponBTN = null;
    #couponNameDiv = null;
    #couponDetailDiv = null;
    #couponHeader = null;
    #removeCouponBTN = null;
    #addCoupon = null;

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
        this.#couponMsgDiv = document.getElementById("couponMsgDiv");
        this.#couponNameDiv = document.getElementById("couponNameDiv");
        this.#couponDetailDiv = document.getElementById("couponDetailDiv");
        this.#addCouponBTN = document.getElementById("addCouponBTN");
        this.#couponBTN = document.getElementById("couponBTN");
        this.#couponHeader = document.getElementById("couponHeader");
        this.#removeCouponBTN = document.getElementById("removeCouponBTN");
        var coupon_modal = document.getElementById('addCoupon');
        if (coupon_modal != null) {
            this.#addCoupon = new bootstrap.Modal(coupon_modal, { focus: true, backdrop: 'static' });
        }
    }

    ModalOpen() {
        "use strict";
        if (this.#addCoupon != null) {
            this.#addCoupon.show();
            if (!this.#couponCode) {
                return;
            }
            this.#couponCode.addEventListener('keyup', (e)=> { if (e.code === 'Enter') addCouponCode(); });
            this.#addCouponBTN.disabled = false;
            this.#removeCouponBTN.hidden = !this.#couponActive;
            this.clear_modal_message();
        }
    }

    ModalClose() {
        "use strict";
        if (this.#addCoupon != null) {
            this.#addCoupon.hide();
        }
    }

    AddCouponCode() {
        "use strict";
        if (!this.#couponCode)  // field not found
            return;

        var couponCodeStr = this.#couponCode.value;

        if (couponCodeStr == '') {
            this.show_modal_message('Please enter a coupon code', 'warn');
            return;
        }

        this.#addCouponBTN.disabled = true;
        this.clear_modal_message();
        // validate the coupon code
        $.ajax({
            url: "scripts/getCouponDetails.php",
            data: { code: couponCodeStr },
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
        this.#curCoupon = data['coupon'];
        this.ModalClose();
        // recompute the coupon effects on the membership types
        this.UpdateMtypes();
        // now apply the coupon to the screen
        // set coupon code
        this.#couponNameDiv.innerHTML = "<span style='color: red;'>" + this.#curCoupon['code'] + "</span>";
        this.#couponDetailDiv.innerHTML = this.couponDetails()
        this.#couponBTN.innerHTML = "Change Coupon";
        this.#couponHeader.innerHTML = "Change/Remove Coupon for Order";
        this.#addCouponBTN.innerHTML = "Change Coupon";
        this.#couponActive = true;
        repriceCart();
    }

    RemoveCouponCode() {
        "use strict";
        this.#couponCode.value = '';
        this.#couponNameDiv.innerHTML = "";
        this.#couponDetailDiv.innerHTML = "";
        this.#couponBTN.innerHTML = "Add Coupon";
        this.#couponHeader.innerHTML = "Add Coupon to Order";
        this.#addCouponBTN.innerHTML = "Add Coupon";
        this.ModalClose();
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
        if (this.#curCoupon['minMemberships']) {
            if (this.#curCoupon['maxMemberships'] && this.#curCoupon['minMemberships'] > 1) {
                html += ', #Memberships: ' + this.#curCoupon['minMemberships'] + '-' + this.#curCoupon['maxMemberships'] ;
            } else if (this.#curCoupon['minMemberships'] > 1) {
                html += ', Min Memberships: ' + this.#curCoupon['minMemberships'];
            }
        } else if (this.#curCoupon['maxMemberships']) {
            html += ' Max Memberships: ' + this.#curCoupon['maxMemberships'];
        }

        if (this.#curCoupon['minTransaction']) {
            if (this.#curCoupon['maxTransaction']) {
                html += ', Cart Value: $' + this.#curCoupon['minTransaction'] + '-' + this.#curCoupon['maxTransaction'] ;
            } else {
                html += ', Min Cart: $' + this.#curCoupon['minTransaction'];
            }
        } else if (this.#curCoupon['maxTransaction']) {
            html += ', Max Cart: $' + this.#curCoupon['maxTransaction'];
        }
        
        if (this.#curCoupon['memId']) {
            html += ', Only valid on ' + this.#curCoupon['label'] + ' memberships';
        }

        if (html.length < 2)
            return '';

        return 'Coupon Details: ' + html.substring(2);
    }


    // AgeDiscount - compute discount on badges of this agev group
    // returns text string to add to display for discount in price
    UpdateMtypes() {
        for (var row in mtypes) {
            var mbrtype = mtypes[row];
            if (this.#curCoupon == null || mbrtype['price'] == 0 || (mbrtype['memCategory'] != 'standard' && mbrtype['memCategory'] != 'virtual'))
                mbrtype['discount'] = Number(0).toFixed(2);
            else if (this.#curCoupon['couponType'] == '$off' || this.#curCoupon['couponType'] == '%off')
                mbrtype['discount'] = Number(0).toFixed(2);
            else if (this.#curCoupon['memId'] == null || this.#curCoupon['memId'] == mbrtype['id']) { // ok, we have a coupon type that applies to this row
                var discount = Number(0).toFixed(2);
                if (this.#curCoupon['couponType'] == 'price') {
                    discount = Number(mbrtype['price']).toFixed(2) - Number(this.#curCoupon['discount']).toFixed(2);
                } else if (this.#curCoupon['couponType'] == '$mem') {
                    discount = Number(this.#curCoupon['discount']).toFixed(2);
                } else if (this.#curCoupon['couponType'] == '%mem') {
                    discount = (Number(mbrtype['price']) * Number(this.#curCoupon['discount']) / 100.0).toFixed(2);
                }
                if (Number(discount).toFixed(2) > Number(mbrtype['price']).toFixed(2)) {
                    discount = Number(mbrtype['price']).toFixed(2);
                }
                mbrtype['discount'] = discount;
            } else
                mbrtype['discount'] = discount;
        }
        return;
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
