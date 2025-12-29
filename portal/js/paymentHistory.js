// pyment history javascript, also requires base.js

//var coupon = null;
var paymentHistory = null;
// initial setup
window.onload = function () {
    if (config.loadPlans) {
        paymentPlans = new PaymentPlans();
    }
    paymentHistory = new PaymentHistory();
    /*
    coupon = new Coupon();
    if (config.initCoupon && config.initCoupon != '') {
        coupon.addCouponCode(config.initCoupon, config.initCouponSerial);
    }
    */
    if (config.refresh == 'passkey')
        paymentHistory.loginWithPasskey();
}

class PaymentHistory {
    // this page name for window.location to avoid refresh errors
    #portalPage = 'portal.php';

    // show-hide fields
    #purchasedShowAll = null;
    #purchasedShowUnpaid = null;
    #purchasedHideAll = null;

    // coupon fields:
    #subTotalColDiv = null;
    #couponDiscountDiv = null;
    #payBalanceBTN = null;

    // receipt fields
    #receiptModal = null;
    #receiptDiv = null;
    #receiptTables = null;
    #receiptText = null;
    #receiptEmailBtn = null;
    #receiptTitle = null;
    #receiptEmailAddress = null;

    // locale/currency
    #currencyFmt = null;
    #locale = null;

    constructor() {
        let id;

        this.#locale = config.locale;
        this.#currencyFmt = new Intl.NumberFormat(this.#locale, {
            style: 'currency',
            currency: config.currency,
        });

        id = document.getElementById("portalReceipt");
        if (id) {
            this.#receiptModal = new bootstrap.Modal(id, {focus: true, backdrop: 'static'});
            this.#receiptDiv = document.getElementById('portalReceipt-div');
            this.#receiptTables = document.getElementById('portalReceipt-tables');
            this.#receiptText = document.getElementById('portalReceipt-text');
            this.#receiptEmailBtn = document.getElementById('portalEmailReceipt');
            this.#receiptTitle = document.getElementById('portalReceiptTitle');
        }

        this.#purchasedShowAll = document.getElementById('btn-showAll');
        this.#purchasedShowUnpaid = document.getElementById('btn-showUnpaid');
        this.#purchasedHideAll = document.getElementById('btn-hideAll');

        // default to All
        if (this.#purchasedShowAll) {
            if (this.#purchasedShowAll.disabled == true)
                this.showAll();
        } else if (this.purchasedShowUnpaid) {
            if (this.#purchasedShowUnpaid.disabled == true)
                this.showUnpaid();
            else
                this.hideAll();
        }

        var _this = this;

        // enable all tooltips
        const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
        const tooltipList = [...tooltipTriggerList].map(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl));
    }

    // fetch a receipt by transaction number
    transReceipt(transId) {
        this.#receiptEmailAddress = null;
        clear_message();
        var script = 'scripts/getReceipt.php';
        var data = {
            loginId: config.id,
            loginType: config.idType,
            action: 'portalReceipt',
            transId: transId,
        }
        $.ajax({
            url: script,
            data: data,
            method: 'POST',
            success: function (data, textStatus, jqXhr) {
                checkResolveUpdates(data);
                paymentHistory.showReceipt(data);
                return true;
            },
            error: function (jqXHR, textStatus, errorThrown) {
                showAjaxError(jqXHR, textStatus, errorThrown);
                return false;
            },
        });
    }

    showReceipt(data) {
        if (data.message) {
            show_message(data.message, 'error');
            return;
        }
        if (data.data) {
            show_message(data.data, 'error');
            return;
        }

        clear_message();
        var receipt = data.receipt;
        this.#receiptDiv.innerHTML = receipt.receipt_html;
        this.#receiptTables.innerHTML = receipt.receipt_tables;
        this.#receiptText.innerHTML = receipt.receipt;
        this.#receiptEmailAddress = receipt.payor_email;
        this.#receiptEmailBtn.innerHTML = "Email Receipt to " + receipt.payor_name + ' at ' + this.#receiptEmailAddress;
        this.#receiptTitle.innerHTML = "Registration Receipt for " + receipt.payor_name;
        this.#receiptModal.show();
    }

    emailReceipt(addrchoice) {
        var success='';
        if (this.#receiptEmailAddress == null)
            return;

        if (success == '')
            success = this.#receiptEmailBtn.innerHTML.replace("Email Receipt to", "Receipt sent to");

        var data = {
            loginId: config.id,
            loginType: config.idType,
            email: this.#receiptEmailAddress,
            okmsg: success,
            text: this.#receiptText.innerHTML,
            html: this.#receiptTables.innerHTML,
            subject: this.#receiptTitle.innerHTML,
            success: success,
        };
        var _this = this;
        $.ajax({
            method: "POST",
            url: "scripts/emailReceipt.php",
            data: data,
            success: function (data, textstatus, jqxhr) {
                checkResolveUpdates(data);
                if (data.status == 'error') {
                    show_message(data.message, 'error');
                    return;
                }
                if (data.status == 'success') {
                    show_message(data.message, 'success');
                }
                if (data.status == 'warn') {
                    show_message(data.message, 'warn');
                }
                _this.#receiptModal.hide();
            },
            error: function (jqXHR, textStatus, errorThrown) {
                showAjaxError(jqXHR, textStatus, errorThrown);
            }
        });
    }

    // show / hide the home page purchased section
    showAll() {
        $('div[name="t-paid"]').show();
        $('div[name="t-unpaid"]').show();
        $('div[name="t-plan"]').show();

        var color = false;
        $("div[name^='t-']").each(function() {
            if (color)
                $(this).addClass('bg-light')
            else
                $(this).removeClass('bg-light');
            color = !color;
        });

        if (this.#purchasedShowAll) {
            if (!this.#purchasedShowAll.classList.contains('text-white'))
                this.#purchasedShowAll.classList.add("text-white");
            if (this.#purchasedShowAll.classList.contains('btn-light')) {
                this.#purchasedShowAll.classList.remove("btn-light");
                this.#purchasedShowAll.classList.add("btn-info");
            }
            this.#purchasedShowAll.disabled = true;
        }
        if (this.#purchasedShowUnpaid) {
            if (this.#purchasedShowUnpaid.classList.contains('text-white'))
                this.#purchasedShowUnpaid.classList.remove("text-white");
            if (this.#purchasedShowUnpaid.classList.contains('btn-info')) {
                this.#purchasedShowUnpaid.classList.remove("btn-info");
                this.#purchasedShowUnpaid.classList.add("btn-light");
            }
            this.#purchasedShowUnpaid.disabled = false;
        }
        if (this.#purchasedHideAll) {
            if (this.#purchasedHideAll.classList.contains('text-white'))
                this.#purchasedHideAll.classList.remove("text-white");
            if (this.#purchasedHideAll.classList.contains('btn-info')) {
                this.#purchasedHideAll.classList.remove("btn-info");
                this.#purchasedHideAll.classList.add("btn-light");
            }
            this.#purchasedHideAll.disabled = false;
        }
    }

    showUnpaid() {
        $('div[name="t-paid"]').hide();
        $('div[name="t-unpaid"]').show();
        $('div[name="t-plan"]').show();

        var color = false;
        $("div[name^='t-']").each(function() {
            if ($(this).css("display") != "none") {
                if (color)
                    $(this).addClass('bg-light')
                else
                    $(this).removeClass('bg-light');
                color = !color;
            }
        });

        if (this.#purchasedShowAll) {
            if (this.#purchasedShowAll.classList.contains('text-white'))
                this.#purchasedShowAll.classList.remove("text-white");
            if (this.#purchasedShowAll.classList.contains('btn-info')) {
                this.#purchasedShowAll.classList.remove("btn-info");
                this.#purchasedShowAll.classList.add("btn-light");
            }
            this.#purchasedShowAll.disabled = false;
        }
        if (this.#purchasedShowUnpaid) {
            if (!this.#purchasedShowUnpaid.classList.contains('text-white'))
                this.#purchasedShowUnpaid.classList.add("text-white");
            if (this.#purchasedShowUnpaid.classList.contains('btn-light')) {
                this.#purchasedShowUnpaid.classList.remove("btn-light");
                this.#purchasedShowUnpaid.classList.add("btn-info");
            }
            this.#purchasedShowUnpaid.disabled = true;
        }
        if (this.#purchasedHideAll) {
            if (this.#purchasedHideAll.classList.contains('text-white'))
                this.#purchasedHideAll.classList.remove("text-white");
            if (this.#purchasedHideAll.classList.contains('btn-info')) {
                this.#purchasedHideAll.classList.remove("btn-info");
                this.#purchasedHideAll.classList.add("btn-light");
            }
            this.#purchasedHideAll.disabled = false;
        }
    }

    hideAll() {
        $('[name="t-paid"]').hide();
        $('[name="t-unpaid"]').hide();
        $('[name="t-plan"]').hide();

        if (this.#purchasedShowAll) {
            if (this.#purchasedShowAll.classList.contains('text-white'))
                this.#purchasedShowAll.classList.remove("text-white");
            if (this.#purchasedShowAll.classList.contains('btn-info')) {
                this.#purchasedShowAll.classList.remove("btn-info");
                this.#purchasedShowAll.classList.add("btn-light");
            }
            this.#purchasedShowAll.disabled = false;
        }
        if (this.#purchasedShowUnpaid) {
            if (this.#purchasedShowUnpaid.classList.contains('text-white'))
                this.#purchasedShowUnpaid.classList.remove("text-white");
            if (this.#purchasedShowUnpaid.classList.contains('btn-info')) {
                this.#purchasedShowUnpaid.classList.remove("btn-info");
                this.#purchasedShowUnpaid.classList.add("btn-light");
            }
            this.#purchasedShowUnpaid.disabled = false;
        }
        if (this.#purchasedHideAll) {
            if (!this.#purchasedHideAll.classList.contains('text-white'))
                this.#purchasedHideAll.classList.add("text-white");
            if (this.#purchasedHideAll.classList.contains('btn-light')) {
                this.#purchasedHideAll.classList.remove("btn-light");
                this.#purchasedHideAll.classList.add("btn-info");
            }
            this.#purchasedHideAll.disabled = true;
        }
    }

    gotoPayment() {
        window.location.href = "/portal.php?payment=1";
    }
    /*
    // coupon related items
    couponDiscountUpdate(couponAmounts) {
        this.#preCouponAmountDue = Number(couponAmounts.totalDue);
        this.#subTotalColDiv.innerHTML = currencyFmt.format(Number(couponAmounts.totalDue).toFixed(2));
        this.#couponDiscount = Number(couponAmounts.discount);
        this.#couponDiscountDiv.innerHTML = currencyFmt.format(Number(couponAmounts.discount).toFixed(2));
        this.#totalAmountDue = Number(couponAmounts.totalDue - couponAmounts.discount);
        $('span[name="totalDueAmountSpan"]').html('$&nbsp;' + this.#totalAmountDue.toFixed(2));
    }*/

    // passkey refresh
    // login with passkey - ask for a confirm and return either retry or go to portal
    loginWithPasskey() {
        passkeyRequest('scripts/passkeyActions.php', 'portal.php', 'portal');
    }
}
