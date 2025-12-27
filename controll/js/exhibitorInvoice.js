// Items related to building and paying the exhibitor invoice
class ExhibitorInvoice {
    #exhibitorInvoiceModal = null;
    #totalSpacePrice = 0;
    #regionYearId = null;
    #exhibitorId = null;
    #exhibitorYearId = null;
    #membershipCostdiv = null;
    #mailin = null;
    #additionalCost = [];
    #elcheckno = null;
    #elCcauth = null;
    #econfirm = null;
    #payCheckno = null;
    #payCcauth = null;
    #payButton = null;
    #payAmt = null;
    #totalInvCost = null;
    #totalMembershipCost = null;
    #payDescription = null;
    #totalAmountDue = 0;
    #paymentTypeDiv = null;
    #paymentDiv = null;
    #includedMemberships = 0;
    #additionalMemberships = 0;
    #currentPrefix = null;
    #currentType = null;
    #currentOrdinal = null;
    #formValid = false;
    #validateMessage = '';
    #payRow = null;
    #currentOrderId = null;
    #invalidFields = '';
    #portalType = null;

// constructor function - intializes dom objects and inital privates
    constructor() {
        var id = document.getElementById('vendor_invoice');
        if (id != null) {
            this.#exhibitorInvoiceModal = new bootstrap.Modal(id, {focus: true, backdrop: 'static'});
        }
        this.#membershipCostdiv = document.getElementById("membershipCost");
        this.#elcheckno = document.getElementById('pay-check-div');
        this.#elCcauth = document.getElementById('pay-ccauth-div');
        this.#econfirm = document.getElementById('');
        this.#payCheckno = document.getElementById('pay-checkno');
        this.#payCcauth = document.getElementById('pay-ccauth');
        this.#payDescription = document.getElementById('pay-desc');
        this.#payButton = document.getElementById('pay-btn-pay');
        this.#payAmt = document.getElementById('pay-amt');
        this.#totalInvCost = document.getElementById('vendor_inv_cost');
        this.#totalMembershipCost = document.getElementById('vendor_inv_mbr_cost');
        this.#paymentTypeDiv = document.getElementById('pt-div');
        this.#paymentDiv = document.getElementById('paymentDiv');

        this.#totalAmountDue = 0;
    }

// update showing the pay button and the payment fields
    #updatePaymentDiv() {
        if (this.#totalAmountDue == 0) {
            this.#payButton.disabled = false;
            this.#paymentDiv.hidden = true;
        } else {
            this.#payButton.disabled = !(document.getElementById('pt-cash').checked ||
                document.getElementById('pt-check').checked || document.getElementById('pt-credit').checked)
            this.#paymentDiv.hidden = false;
            this.#payRow = null;
        }
    }

// openInvoice: display the vendor invoice (and registration items)
    openInvoice(exhibitorInfo, regionYearId) {
        var spacePriceName = '';

        this.#regionYearId = regionYearId;
        this.#exhibitorId = exhibitorInfo.exhibitorId;
        this.#exhibitorYearId = exhibitorInfo.exhibitorYearId;

        if (config.debug & 1) {
            console.log("regionYearId: " + regionYearId);
        }
        var region = exhibits_spaces[regionYearId];
        var regionList = region_list[regionYearId];
        var portalName = 'ConTroll';
        var attendeeName = 'Exhibitor';
        var attendeeNameLC = 'Exhibitors';
        this.#portalType = regionList.portalType
        var exhibitorName = exhibitor_info.exhibitorName;
        switch (this.#portalType) {
            case 'artist':
                portalName = 'Artist';
                attendeeName = 'Artist';
                attendeeNameLC = 'artist';
                exhibitorName = exhibitor_info.artistName;
                if (exhibitorName == null || exhibitorName == '') {
                    exhibitorName = exhibitor_info.exhibitorName;
                }
                break;
            case 'vendor':
                portalName = 'Vendor';
                attendeeName = 'Vendor';
                attendeeNameLC = 'vendor';
                break;
        }
        this.#mailin = exhibitor_info.mailin;
        if (config.debug & 1) {
            console.log("regionList");
            console.log(regionList);
            console.log("Region Spaces");
            console.log(region);
        }

        // fill in the variable items
        document.getElementById("vendor_invoice_title").innerHTML = "<strong>Pay " + regionList.name + ' Invoice for ' + exhibitorName + '</strong>';

        // refresh the items spaces purchased area
        var ret = drawExhitorTopBlocks('You', exhibitor_spacelist, region, regionList, this.#regionYearId,
            'vendor_inv_approved_for', 'vendor_inv_included', 'vendor_inv_included_mbr',
            false);
        this.#includedMemberships = ret[0];
        this.#additionalMemberships = ret[1];
        spacePriceName = ret[2];
        this.#totalSpacePrice = ret[3];

        this.#totalAmountDue = Number(this.#totalSpacePrice);
        this.#totalInvCost.innerHTML = Number(this.#totalSpacePrice).toFixed(2);
        document.getElementById('vendorSpacePrice').value = this.#totalSpacePrice;
        document.getElementById('vendor_inv_region_id').value = regionYearId;

        this.#membershipCostdiv.hidden = (this.#includedMemberships == 0 && this.#additionalMemberships == 0);

        this.#exhibitorInvoiceModal.show();
        this.#updatePaymentDiv();
    }

// update invoice for the Cost of Memberships and total Cost when an additional member is started
    updateCost(regionYearId, item) {
        var regionList = region_list[regionYearId];
        var fname = document.getElementById('a_' + item + '_fname).value;
        this.#totalAmountDue = 0;
        this.#additionalCost[item] = fname == '' ? 0 : Number(regionList.additionalMemPrice);
        for (var num in this.#additionalCost) {
            this.#totalAmountDue += this.#additionalCost[num];
        }
        if (config.debug & 1)
            console.log('Pre this.#totalSpacePrice: ' + String(this.#totalAmountDue));
        this.#totalMembershipCost.innerHTML = Number(this.#totalAmountDue).toFixed(2);
        this.#totalAmountDue += Number(this.#totalSpacePrice);
        if (config.debug & 1)
            console.log('After adding this.#totalSpacePrice: ' + String(this.#totalAmountDue));
        this.#totalInvCost.innerHTML = Number(this.#totalAmountDue).toFixed(2);
        this.#updatePaymentDiv();
    }

// setPayType: shows/hides the appropriate fields for that payment type
    setPayType(ptype) {
        this.#elcheckno.hidden = ptype != 'check';
        this.#elCcauth.hidden = ptype != 'credit';
        this.#payButton.disabled = ptype == 'online';

        if (ptype != 'check') {
            this.#payCheckno.value = null;
        }
        if (ptype != 'credit') {
            this.#payCcauth.value = null;
        }
    }

// Process a payment against the transaction
    pay() {
        var checked = false;
        var ccauth = null;
        var checkno = null;
        var desc = null;
        var ptype = null;
        var pt_cash = document.getElementById('pt-cash').checked;
        var pt_check = document.getElementById('pt-check').checked;
        var pt_credit = document.getElementById('pt-credit').checked;
        this.#formValid = true;
        this.#validateMessage = '';

        clear_message('inv_result_message');

        if (this.#payRow == null && this.#totalAmountDue > 0) {
            // validate the payment entry: It must be >0 and <= amount due
            //      a payment type must be specified
            //      for check: the check number is required
            //      for credit card: the auth code is required
            //      for discount: description is required, it's optional otherwise
            var pay_amt = Number(this.#payAmt.value);
            if (pay_amt > 0 && pay_amt > this.#totalAmountDue) {
                this.#payAmt.style.backgroundColor = 'var(--bs-warning)';
                this.#invalidFields += "Amount Paid, ";
                this.#formValid = false;
            }
            if (this.#formValid && pay_amt <= 0 && this.#totalAmountDue > 0) {
                this.#payAmt.style.backgroundColor = 'var(--bs-warning)';
                this.#invalidFields += "Amount Paid, ";
                this.#formValid = false;
            }

            this.#payAmt.style.backgroundColor = '';
            this.#paymentTypeDiv.style.backgroundColor = '';

            if (pt_check) {
                ptype = 'check';
                checked = true;
                checkno = this.#payCheckno.value;
                if (checkno == null || checkno == '') {
                    this.#invalidFields += 'Check Number, ';
                    this.#payCheckno.style.backgroundColor = 'var(--bs-warning)';
                    this.#validateMessage += '<br/>For payment type check, the check number field is required.';
                    this.#formValid = false;
                } else {
                    this.#payCheckno.style.backgroundColor = '';
                }
            }

            if (pt_credit) {
                ptype = 'credit';
                checked = true;
                ccauth = this.#payCcauth.value;
                if (ccauth == null || ccauth == '') {
                    this.#invalidFields += 'CC Auth Code, '
                    this.#payCcauth.style.backgroundColor = 'var(--bs-warning)';
                    this.#validateMessage += '<br/>For payment type credit, the autherization code field is required.';
                    this.#formValid = false;
                } else {
                    this.#payCcauth.style.backgroundColor = '';
                }
            }

            if (pt_cash) {
                ptype = 'cash';
                checked = true;
            }

            if (!checked) {
                this.#paymentTypeDiv.style.backgroundColor = 'var(--bs-warning)';
                this.#invalidFields += "Payment Type, ";
                this.#validateMessage += '<br/>You must select a payment type.';
                this.#formValid = false;
            }

            if (this.#formValid) {
                if (pay_amt > 0) {
                    this.#payRow = {
                        index: 2, amt: pay_amt, ccauth: ccauth, checkno: checkno, desc: this.#payDescription.value, type: ptype, nonce: 'offline',
                    };
                }
            }
        }

        this.#currentOrdinal = 0;
        this.#currentType = 'i';
        this.#payValidate();
    }
        
    // now validate the membership fields
    payValidate() {
        if (this.#currentType == 'i') {
            while (this.#currentOrdinal < this.#includedMemberships) {
                this.#currentPrefix = 'i_' + this.#currentOrdinal + '_';
                if (document.getElementById(this.#currentPrefix + 'fname').value != '' ||
                    document.getElementById(this.#currentPrefix + 'lname').value != '') {
                    let message = inclProfiles[this.#currentOrdinal].validate(null, 'inv_result_message', payValidate, payValidate, '', true);
                    if (message != '') {
                        this.#formValid = false;
                        this.#validateMessage += message;
                    }
                }
                this.#currentOrdinal++;
            }
            this.#currentType = 'a'
            this.#currentOrdinal = 0;
        }

        while (this.#currentOrdinal < this.#additionalMemberships) {
            this.#currentPrefix = 'a_' + this.#currentOrdinal + '_';
            if (document.getElementById(this.#currentPrefix + 'fname').value != '' ||
                document.getElementById(this.#currentPrefix + 'lname').value != '') {
                let message = addlProfiles[this.#currentOrdinal].validate(null, 'inv_result_message', payValidate, payValidate, '', true);
                if (message != '') {
                    this.#formValid = false;
                    this.#validateMessage += message;
                }
            }
            this.#currentOrdinal++;
        }


        if (!this.#formValid) {
            show_message('Please correct the items marked in yellow to process the payment.' + this.#invalidFields +
                '<br/>For fields in the membership area that are required and not available, use /r to indicate not available.',
                'warn', 'inv_result_message')
            return;
        }

        this.#processPay();
    }

    // process payment
    processPay() {
        this.#payButton.disabled = true;
        var formArr = $('#vendor_invoice_form').serializeArray();
        var formData = {};
        for (var index = 0; index <formArr.length; index++)
            formData[formArr[index].name] = formArr[index].value;
        formData.nonce= 'admin';
        formData.amtDue= this.#totalAmountDue;
        formData.prow = this.#payRow;
        formData.portalType = this.#portalType;
        formData.exhibitorId = this.#exhibitorId;
        formData.exhibitorYearId = this.#exhibitorYearId;

        if (this.#currentOrderId) {
            formData.cancelOrderId = this.#currentOrderId;
            this.#currentOrderId = null;
        }
        clear_message('inv_result_message');
        $.ajax({
            url: 'scripts/exhibitorsSpacePayment.php',
            method: 'POST',
            data: formData,
            success: function(data, textStatus, jqXhr) {
                exhibitorInvoice.paySuccess(data);
            },
            error: function (jqXHR, textStatus, errorThrown) {
                showAjaxError(jqXHR, textStatus, errorThrown, 'inv_result_message');
                exhibitorInvoice.enablePayButton();
                return false;
            }
        });
    }

    // enablePayButton - for AJAX, re-enable the pay button
    enablePayButton() {
        this.#payButton.disabled = false;
    }

    // pay succeedd - deal with it
    paySuccess(data) {
        if (config.debug & 1)
            console.log(data);
        if (data.currentOrderId)
            this.#currentOrderId = data.currentOrderId;
        if (data.error) {
            show_message(data.error, 'error', 'inv_result_message');
            this.#payButton.disabled = false;
        } else if (data.status == 'error') {
            show_message(data.data, 'error', 'inv_result_message');
            this.#payButton.disabled = false;
        } else if (data.status == 'success') {
            this.#exhibitorInvoiceModal.hide();
            show_message(data.message + "Payment for space recorded.");
            if (data.exhibitor_spacelist) {
                exhibitor_spacelist = data.exhibitor_spacelist;
            }
            this.#currentOrderId = null; // successful payment clears the current order
            exhibitors.open(fulltabname, data.message);
        } else {
            show_message('There was an unexpected error, please email ' + config.vemail + ' to let us know.  Thank you.', 'error', 'inv_result_message');
            this.#payButton.disabled = false;
        }
    }

// Create a receipt and email it
    email_receipt(receipt_type) {
        // header text
        var header_text = cart.receiptHeader(user_id, pay_tid);
        // optional footer text
        var footer_text = '';
        // server side will print the receipt
        var postData = {
            ajax_request_action: 'printReceipt',
            header: header_text,
            prows: cart.getCartPerinfo(),
            mrows: cart.getCartMembership(),
            pmtrows: cart.getCartPmt(),
            footer: footer_text,
            receipt_type: receipt_type,
            email_addrs: emailAddreesRecipients,
        };
        pay_button_ercpt.disabled = true;
        $.ajax({
            method: "POST",
            url: "scripts/reg_emailReceipt.php",
            data: postData,
            success: function (data, textstatus, jqxhr) {
                clear_message();
                if (typeof data == "string") {
                    show_message(data,  'error');
                } else if (data.error !== undefined) {
                    show_message(data.error, 'error');
                } else if (data.message !== undefined) {
                    show_message(data.message, 'success');
                } else if (data.warn !== undefined) {
                    show_message(data.warn, 'success');
                }
                pay_button_ercpt.disabled = false;
            },
            error: function (jqXHR, textstatus, errorThrown) {
                pay_button_ercpt.disabled = false;
                showAjaxError(jqXHR, textstatus, errorThrown);
            }
        });
    }
}

exhibitorInvoice = null;
// init
function exhibitorInvoiceOnLoad() {
    exhibitorInvoice = new ExhibitorInvoice();
}

function payValidate() {
    this.exhibitorInvoice.payValidate();
}
