// Main portal javascript, also requires base.js

var portal = null;
var coupon = null;
var profile = null;

// initial setup
window.onload = function () {
    if (config.loadPlans) {
        paymentPlans = new PaymentPlans();
    }
    portal = new Portal();
    coupon = new Coupon();
    if (config.initCoupon && config.initCoupon != '') {
        coupon.addCouponCode(config.initCoupon, config.initCouponSerial);
    }
    if (config.refresh == 'passkey')
        portal.loginWithPasskey();
}

class Portal {
    // this page name for window.location to avoid refresh errors
    #portalPage = 'portal.php';
    // edit person modal
    #editPersonModal = null;
    #editPersonModalElement = null;
    #editPersonTitle = null;
    #editPersonSubmitBtn = null;
    #editPersonOverrideBtn = null;
    #epHeaderDiv = null;
    #epPersonIdField = null;
    #epPersonTypeField = null;
    #needAge = false;
    #editPersonEmail = null;

    // change email modal
    #changeEmailModal = null;
    #changeEmailModalElement = null;
    #changeEmailTitle = null;
    #changeEmailSubmitBtn = null;
    #changeEmailNewEmailAddr = null;
    #changeEmailH1 = null;

    // person fields
    #currentPerson = null;
    #currentPersonType = null;
    #fullName = null;
    #personSerializeStart = null;

    // interests fields
    #editInterestsModal = null;
    #editInterestsModalElement = null;
    #editInterestsTitle = null;
    #eiHeaderDiv = null
    #eiPersonIdField = null
    #eiPersonTypeField = null;
    #interests = null;
    #interestsSerializeStart = null;

    // order/payment fields
    #payBalanceBTN = null;
    #paymentDueModal = null;
    #paymentDueTitle = null;
    #paymentDueBody = null;
    #makePaymentModal = null;
    #makePaymentTitle = null;
    #makePaymentBody = null;
    #paymentPlan = null;
    #existingPlan = null;
    #planRecast = false;
    #totalAmountDue = null;
    #preCouponAmountDue = 0;
    #couponDiscount = 0;
    #paymentAmount = null;
    #planPayment = 0;
    #partialPayAmt = 0;
    #fullPayAmt = 0;
    #orderData = null;
    #taxes = [];
    #disableButtonNames = null;
    #selectedItems = false;
    #payDueSubmitButton = null;

    // receipt fields
    #receiptModal = null;
    #receiptDiv = null;
    #receiptTables = null;
    #receiptText = null;
    #receiptEmailBtn = null;
    #receiptTitle = null;
    #receiptEmailAddress = null;

    // show-hide fields
    #purchasedShowAll = null;
    #purchasedShowUnpaid = null;
    #purchasedHideAll = null;

    // coupon fields:
    #subTotalColDiv = null;
    #couponDiscountDiv = null;

    // policy Items
    #oldPolicies = null;

    // locale/currency
    #currencyFmt = null;
    #locale = null;

    // pay selected fields
    #payAllList = null;
    #paySelectedList = null;
    #selectIds = null;
    #selectIdKeys = null;
    #selectMems = null;
    #orderMemberships = null;
    #planAllorPartial = null;

    constructor() {
        let id;

        this.#locale = config.locale;
        this.#currencyFmt = new Intl.NumberFormat(this.#locale, {
            style: 'currency',
            currency: config.currency,
        });

        id = document.getElementById("editPersonModal");
        if (id) {
            profile = new Profile('', 'portal');
            this.#editPersonModalElement = id;
            this.#editPersonModal = new bootstrap.Modal(id, {focus: true, backdrop: 'static'});
            this.#editPersonTitle = document.getElementById('editPersonTitle');
            this.#editPersonSubmitBtn = document.getElementById('editPersonSubmitBtn');
            this.#editPersonOverrideBtn = document.getElementById('editPersonOverrideBtn');
            this.#epHeaderDiv = document.getElementById("epHeader");
            this.#epPersonIdField = document.getElementById("epPersonId");
            this.#epPersonTypeField = document.getElementById("epPersonType");
        }

        id = document.getElementById("changeEmailModal");
        if (id) {
            this.#changeEmailModalElement = id;
            this.#changeEmailModal = new bootstrap.Modal(id, {focus: true, backdrop: 'static'});
            this.#changeEmailTitle = document.getElementById('changeEmailTitle');
            this.#changeEmailSubmitBtn = document.getElementById('changeEmailSubmitBtn');
            this.#changeEmailNewEmailAddr = document.getElementById('changeEmailNewEmailAddr');
            this.#changeEmailH1 = document.getElementById('changeEmailH1');

            if (this.#changeEmailNewEmailAddr != null) {
                this.#changeEmailNewEmailAddr.addEventListener('keyup', (e) => {
                    if (e.code === 'Enter') {
                        portal.changeEmailChanged(2);
                    } else {
                        portal.changeEmailChanged(1);
                    }
                });
                this.#changeEmailNewEmailAddr.addEventListener('mouseout', (e) => {
                    portal.changeEmailChanged(0);
                });
            }
        }

        id = document.getElementById("editInterestModal");
        if (id) {
            this.#editInterestsModalElement = id;
            this.#editInterestsModal = new bootstrap.Modal(id, {focus: true, backdrop: 'static'});
            this.#editInterestsTitle = document.getElementById('editInterestsTitle');
            this.#eiHeaderDiv = document.getElementById('eiHeader');
            this.#eiPersonIdField = document.getElementById('eiPersonId');
            this.#eiPersonTypeField = document.getElementById("eiPersonType");
        }

        this.#payBalanceBTN = document.getElementById('payBalanceBTN');
        if (this.#payBalanceBTN != null && paymentPlanList != null) {
            if (paymentPlans.plansEligible(membershipsPurchased)) {
                this.#payBalanceBTN.innerHTML = "Show payment plan options";
            }
        }

        id = document.getElementById("paymentDueModal");
        if (id) {
            this.#paymentDueModal = new bootstrap.Modal(id, {focus: true, backdrop: 'static'});
            this.#paymentDueBody = document.getElementById("paymentDueBody");
            this.#paymentDueTitle = document.getElementById("paymentDueTitle");
            this.#payDueSubmitButton = document.getElementById("payDueSubmitButton");
        }

        id = document.getElementById("makePaymentModal");
        if (id) {
            this.#makePaymentModal = new bootstrap.Modal(id, {focus: true, backdrop: 'static'});
            this.#makePaymentBody = document.getElementById("makePaymentBody");
            this.#makePaymentTitle = document.getElementById("makePaymentTitle");
        }

        id = document.getElementById("portalReceipt");
        if (id) {
            this.#receiptModal = new bootstrap.Modal(id, {focus: true, backdrop: 'static'});
            this.#receiptDiv = document.getElementById('portalReceipt-div');
            this.#receiptTables = document.getElementById('portalReceipt-tables');
            this.#receiptText = document.getElementById('portalReceipt-text');
            this.#receiptEmailBtn = document.getElementById('portalEmailReceipt');
            this.#receiptTitle = document.getElementById('portalReceiptTitle');
        }


        this.#subTotalColDiv = document.getElementById('subTotalColDiv');
        this.#couponDiscountDiv = document.getElementById('couponDiscountDiv');
        let _this = this;
        var modalCalled = false;

        // enable all tooltips
        const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
        const tooltipList = [...tooltipTriggerList].map(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl));

        // do any people need to have their profiles edited to handle missing ages or age verification
        $('.need-age').each(function (i, obj) {
            if (modalCalled)
                return;

            let dataset = obj.dataset;
            let id = dataset.id;
            let type = dataset.type;
            let pid = type + id.toString();
            if ((!alreadyChecked.hasOwnProperty(pid)) || alreadyChecked[pid] == 0) {
                _this.editPerson(id, type, true, true);
                show_message('Age needs to be verified', "error", 'epMessageDiv');
                modalCalled = true;
            }
        });

        // do any people need to have their policies updated for missing policies
        if (!modalCalled) {
            $('.need-policies').each(function (i, obj) {
                if (modalCalled)
                    return;
                let dataset = obj.dataset;
                let id = dataset.id;
                let type = dataset.type;
                let pid = type + id.toString();
                if ((!alreadyChecked.hasOwnProperty(pid)) || alreadyChecked[pid] == 0) {
                    _this.editPerson(id, type, true);
                    show_message('Required Policies are not accepted', "warn", 'epMessageDiv');
                    modalCalled = true;
                }
            });
        }

        if (config.needInterests == 1) {
            if (modalCalled)
                return;
            _this.editInterests(config.id, config.idType);
            modalCalled = true;
        }

        if (config.hasOwnProperty('paymentFocus')) {
            if (config.paymentFocus != '') {
                this.setFocus(config.paymentFocus);
                config.paymentFocus = '';
            }
        }

        if (hid)
            this.settab(hid);
    }

    // set  / get functions
    setOrderData(data) {
        if (data != '') {
            this.#orderData = data;
            this.#totalAmountDue = data.rtn.totalAmt;
        }
    }

    // disassociate: remove the managed by link for this logged in person
    disassociate() {
        let data = {
            'managedBy': 'disassociate',
            loginId: config.id,
            loginType: config.idType,
        }
        let script = 'scripts/processDisassociate.php';
        $.ajax({
            method: 'POST',
            url: script,
            data: data,
            success: function (data, textStatus, jqXhr) {
                checkResolveUpdates(data);
                if (data.status == 'error') {
                    show_message(data.message, 'error');
                } else if (data.status == 'warn') {
                    show_message(data.message, 'warn');
                } else {
                    if (config.debug & 1)
                        console.log(data);
                    let divElement = document.getElementById('managedByDiv');
                    if (divElement)
                        divElement.style.display = 'none';
                    show_message("You have been disassociated from that manager.");
                }
            },
            error: function (jqXHR, textStatus, errorThrown) {
                showAjaxError(jqXHR, textStatus, errorThrown);
                return false;
            },
        });
    }

    // editPerson - edit a person you manage (or your self)
    editPerson(id, type, needIgnore = false, needAge = false) {
        if (this.#editPersonModal == null) {
            show_message('Edit Person is not available at this time', 'warn');
            return;
        }

        this.#needAge = needAge;

        clear_message('epMessageDiv');
        // clear the prior data
        profile.clearNext();

        this.#currentPerson = id;
        this.#currentPersonType = type;
        let data = {
            loginId: config.id,
            loginType: config.idType,
            getId: id,
            getType: type,
            memberships: 'Y',
            updateIgnore: needIgnore ? 1 : 0,
        }
        let script = 'scripts/getPersonInfo.php';
        $.ajax({
            method: 'POST',
            url: script,
            data: data,
            success: function (data, textStatus, jqXhr) {
                checkResolveUpdates(data);
                if (data.status == 'error') {
                    show_message(data.message, 'error');
                } else if (data.status == 'warn') {
                    show_message(data.message, 'warn');
                } else {
                    if (config.debug & 1)
                        console.log(data);
                    portal.editPersonGetSuccess(data);
                }
            },
            error: function (jqXHR, textStatus, errorThrown) {
                showAjaxError(jqXHR, textStatus, errorThrown);
                return false;
            },
        });
    }

    // got the person, update the modal contents
    editPersonGetSuccess(data) {
        // ok, it's legal to edit this person, now populate the fields
        let person = data.person;
        let post = data.post;
        let memberships = data.memberships;
        if (data.policies)
            this.#oldPolicies = data.policies;

        this.#fullName = person.fullName;
        this.#editPersonTitle.innerHTML = '<strong>Editing: ' + this.#fullName + '</strong>' + "&nbsp;&nbsp;&nbsp;(" + person.id + ")";
        if (profile.hasUSPSDiv() && person.country == 'USA') {
            this.#editPersonSubmitBtn.innerHTML = 'Validate Address and Update ' + this.#fullName;
        } else {
            this.#editPersonSubmitBtn.innerHTML = 'Update ' + this.#fullName;
        }

        // now fill in the fields
        let email = person.email_addr != '' ? person.email_addr : '<i>No Email Address Provided</i>';
        this.#epHeaderDiv.innerHTML = '<strong>Editing: ' + this.#fullName + ' (' + email + ')</strong>';
        this.#epPersonIdField.value = post.getId;
        this.#epPersonTypeField.value = post.getType;
        profile.setAll(person.first_name, person.middle_name, person.last_name, person.suffix, person.legalName, person.pronouns,
            person.address, person.addr_2, person.city, person.state, person.zip, person.country, person.phone,
            person.badge_name, person.badgeNameL2, person.currentAgeType, person.numPrimary);
        this.#editPersonEmail = profile.setEmailFixed(email);

        this.#personSerializeStart = $("#editPerson").serialize();

        // set age from memberships, find if any of them are primary
        let currentAge = '';
        for (let i = 0; i < memberships.length; i++) {
            let m = memberships[i];
            if (m.memAge != 'all' && isPrimary(m.conid, m.memType, m.memCategory, m.price)) {
                currentAge = m.memAge;
                let ageItem = ageListIdx[currentAge];
                if (ageItem.conid == config.conid && ageItem.ageType == m.memAge) {
                    profile.setAgeText('<b>' + ageItem.shortname + ' [' + ageItem.label + ']</b>');
                }
            }
        }
        if (currentAge != '')
            profile.setAge(currentAge);
        else if (person.currentAgeType != null &&
            (ageListIdx[person.currentAgeType].verify == 'N' || person.currentAgeConId == config.conid) &&
            !this.#needAge) {
            profile.setAge(person.currentAgeType);
            profile.hideAgeText(true);
            profile.hideAgeDiv(true);
        } else {
            profile.setAge('');
            profile.hideAgeText(true);
            profile.hideAgeDiv(true);
        }

        profile.setPolicies(this.#oldPolicies);

        this.#editPersonModal.show();
        profile.setFocus('fname');
    }

    // called on the close buttons for the modal, confirm close with changes pending
    checkEditPersonClose() {
        let beforeClose = $("#editPerson").serialize();
        if (beforeClose != this.#personSerializeStart) {
            if (!confirm("There are unsaved changes to the Edit Person Form.\nClick OK to close the form and discard the changes."))
                return false;
        }
        this.#editPersonModal.hide();
    }

    // editPerson - edit a person you manage (or your self)
    changeEmail(personJson) {
        if (this.#changeEmailModal == null) {
            show_message('Change Email is not available at this time', 'warn');
            return;
        }

        // clear old stuff
        clear_message('ceMessageDiv');
        this.#changeEmailNewEmailAddr.value = '';

        let personData = null;
        try {
            personData = JSON.parse(personJson);
        } catch (error) {
            console.log(error);
            show_message('Change Email passed invalid arguments, get assistqnce', 'error');
            return;
        }

        this.#currentPerson = personData.id;
        this.#currentPersonType = personData.type;

        // change modal fields
        this.#changeEmailH1.innerHTML = '<strong>Change Email Address for ' + personData.fullName + ' (' + personData.email_addr + ')</strong>';

        this.#changeEmailSubmitBtn.disabled = true;
        this.#changeEmailModal.show();
        let focusField = this.#changeEmailNewEmailAddr;
        setTimeout(() => {
            focusField.focus({focusVisible: true});
        }, 600);
    }

    // process auto enable of submit button
    changeEmailChanged(autoCall) {
        if (!this.#changeEmailNewEmailAddr) {
            this.#changeEmailSubmitBtn.disabled = true;
            return;
        }
        let email = this.#changeEmailNewEmailAddr.value;
        if (email == null || email == "") {
            this.#changeEmailSubmitBtn.disabled = true;
            return;
        }

        let valid = validateAddress(email);
        this.#changeEmailSubmitBtn.disabled = !valid;
        if (autoCall == 1)
            return;

        if (!valid) {
            show_message("Please enter a valid email address", 'warn', 'ceMessageDiv');
            return;
        }
        if (autoCall == 2)
            this.checkNewEmail(0);
    }

    // checkNewEmail - make sure the email address is valid, and the check if it's allowed for changing
    checkNewEmail() {
        // validate the email address
        let email = this.#changeEmailNewEmailAddr.value;
        if (!validateAddress(email)) {
            show_message("Please enter a valid email address", 'warn');
            this.#changeEmailSubmitBtn.disabled = true;
            return;
        }

        // ok valid email address, check if it's a legal one for us to use
        let data = {
            loginId: config.id,
            loginType: config.idType,
            email: email, // new email address
            currentPersonId: this.#currentPerson,
            currentPersonType: this.#currentPersonType,
            action: 'validate'
        };
        let script = 'scripts/changeEmail.php';
        $.ajax({
            url: script,
            data: data,
            method: 'POST',
            success: function (data, textStatus, jqXhr) {
                checkResolveUpdates(data);
                if (data.status == 'error') {
                    show_message(data.message, 'error', 'ceMessageDiv');
                    return false;
                }
                if (data.status == 'warn') {
                    show_message(data.message, 'warn', 'ceMessageDiv');
                    return false;
                }
                if (data.message) {
                    portal.changeEmailSuccess(data);
                }
                return true;
            },
            error: function (jqXHR, textStatus, errorThrown) {
                showAjaxError(jqXHR, textStatus, errorThrown, 'epMessageDiv');
                return false;
            },
        });
    }

    // change email success - clean up from changing the email address
    changeEmailSuccess(data) {
        if (data.message)
            show_message(data.message, 'success');

        this.#changeEmailModal.hide();
        clear_message('ceMessageDiv');
        this.#changeEmailNewEmailAddr.value = '';
    }

    // countryChange - if USPS and USA, then change button
    countryChange() {
        if (!profile.hasUSPSDiv())
            return;

        if (profile.country() == 'USA') {
            this.#editPersonSubmitBtn.innerHTML = 'Validate Address and Update ' + this.#fullName;
        } else {
            this.#editPersonSubmitBtn.innerHTML = 'Update ' + this.#fullName;
        }
    }

    // now submit the updates to the person

    editPersonSubmit(override) {
        clear_message();
        let person = URLparamsToArray($('#editPerson').serialize());
        let rtn = profile.validate(person, 'epMessageDiv', addPerson, redoAddress, '', false, override);
        if (rtn === false)
            return false;

        if (rtn === 'override' && !override) {
            this.#editPersonOverrideBtn.hidden = false;
            return false;
        }

        this.updatePerson(profile.getFormData());
        return true;
    }

    // update the account
    updatePerson(person) {
        let data = {
            loginId: config.id,
            loginType: config.idType,
            person: person,
            email: this.#editPersonEmail,
            currentPerson: this.#currentPerson,
            currentPersonType: this.#currentPersonType,
            oldPolicies: JSON.stringify(this.#oldPolicies),
            newPolicies: JSON.stringify(URLparamsToArray($('#editPolicies').serialize())),
        }
        if (config.debug & 1)
            console.log(data);

        let script = 'scripts/updatePersonInfo.php';
        $.ajax({
            method: 'POST',
            url: script,
            data: data,
            success: function (data, textStatus, jqXhr) {
                checkResolveUpdates(data);
                portal.updatePersonSuccess(data);
            },
            error: function (jqXHR, textStatus, errorThrown) {
                showAjaxError(jqXHR, textStatus, errorThrown, 'epMessageDiv');
                return false;
            },
        });
    }

    updatePersonSuccess(data) {
        if (data.status == 'error') {
            show_message(data.message, 'error', 'epMessageDiv');
        } else {
            if (config.debug & 1)
                console.log(data);
            show_message(data.message);
            this.#editPersonModal.hide();
            if (data.rows_upd > 0) {
                window.location = this.#portalPage + "?tab=" + hid + '&messageFwd=' + encodeURI(data.message);
            }
        }
    }

    addMembership(id, type) {
        let addForm = '<form id="addMembership" action="cart.php" method="POST">\
            <input type="hidden" name="cartId" value="' + id + '">\
            <input type="hidden" name="cartType" value="' + type + '">\
            <input type="hidden" name="action" value="buy">\
            </form>';
        $('body').append(addForm);
        $('#addMembership').submit();
        $('#addMembership').remove();
    }

    // interests - edit interests for a person

    // editInterests - open modal after getting data
    editInterests(id, type) {
        if (this.#editInterestsModal == null) {
            show_message('Edit Interests is not available at this time', 'warn');
            return;
        }

        this.#currentPerson = id;
        this.#currentPersonType = type;
        let data = {
            loginId: config.id,
            loginType: config.idType,
            getId: id,
            getType: type,
            memberships: 'N',
            interests: 'Y'
        }
        let script = 'scripts/getPersonInfo.php';
        $.ajax({
            method: 'POST',
            url: script,
            data: data,
            success: function (data, textStatus, jqXhr) {
                checkResolveUpdates(data);
                if (data.status == 'error') {
                    show_message(data.message, 'error');
                } else if (data.status == 'warn') {
                    show_message(data.message, 'warn');
                } else {
                    if (config.debug & 1)
                        console.log(data);
                    portal.editInterestsGetSuccess(data);
                }
            },
            error: function (jqXHR, textStatus, errorThrown) {
                showAjaxError(jqXHR, textStatus, errorThrown);
                return false;
            },
        });
    }

    // got the person, update the modal contents
    editInterestsGetSuccess(data) {
        // ok, it's legal to edit this person, now populate the fields
        let person = data.person;
        let post = data.post;
        this.#interests = data.interests;

        this.#fullName = person.fullName;
        this.#editInterestsTitle.innerHTML = '<strong>Editing Interests for: ' + this.#fullName + '</strong>';

        // now fill in the fields
        this.#eiHeaderDiv.innerHTML = 'Editing Interests for: ' + this.#fullName;
        this.#eiPersonIdField.value = post.getId;
        this.#eiPersonTypeField.value = post.getType;

        for (let row in this.#interests) {
            let interest = this.#interests[row];
            let id = document.getElementById('i_' + interest.interest);
            if (id) id.checked = interest.interested == 'Y';
        }

        this.#interestsSerializeStart = $("#editInterests").serialize();
        this.#editInterestsModal.show();

    }

    // called on the close buttons for the modal, confirm close with changes pending
    checkEditInterestsClose() {
        let beforeClose = $("#editInterests").serialize();
        if (beforeClose != this.#interestsSerializeStart) {
            if (!confirm("There are unsaved changes to the Edit Interests Form.\nClick OK to close the form and discard the changes."))
                return false;
        }
        this.#editInterestsModal.hide();
    }

    // editInterestsSubmit - save back the interests
    editInterestSubmit() {
        clear_message();
        let data = {
            loginId: config.id,
            loginType: config.idType,
            existingInterests: JSON.stringify(this.#interests),
            newInterests: JSON.stringify(URLparamsToArray($('#editInterests').serialize())),
            currentPerson: this.#currentPerson,
            currentPersonType: this.#currentPersonType,
        }
        if (config.debug & 1)
            console.log(data);

        let script = 'scripts/updateInterests.php';
        $.ajax({
            method: 'POST',
            url: script,
            data: data,
            success: function (data, textStatus, jqXhr) {
                checkResolveUpdates(data);
                portal.updateInterestsSuccess(data);
            },
            error: function (jqXHR, textStatus, errorThrown) {
                showAjaxError(jqXHR, textStatus, errorThrown, 'eiMessageDiv');
                return false;
            },
        });

    }

    updateInterestsSuccess(data) {
        if (data.status == 'error') {
            show_message(data.message, 'error', 'eiMessageDiv');
        } else {
            if (config.debug & 1)
                console.log(data);
            show_message(data.message);
            this.#editInterestsModal.hide();
            // ok, if we got here because of needInterests, go to add/update for the cart
            if (config.needInterests == 1) {
                if (config.numPrimary == 0) {
                    if (confirm("Add memberships to your account now?"))
                        this.addMembership(config.id, config.idType);
                }
            } else if (data.rows_upd > 0) {
                window.location = this.#portalPage + "?tab=" + hid + '&messageFwd=' + encodeURI(data.message);
            }
        }
    }

    // payment functions
    // Payment flow:
    //  1. determine what to pay (choosePay)
    //      a. Select what to pay
    //          allow them to choose what to pay from all due, and build that order or pay all and directly make full order
    //      b. Pay using payment Plan (once the items are determined, if a payment plan qualifies, allow them to pay with a plan)
    //          allow them to choose and build payment plan
    //          build order with that payment plan
    //      c. Payment on Plan
    //          directly make plan payment order
    //
    //  2. get order information back including
    //      a. orded id
    //      b. pretax total
    //      c. tax
    //      d. total with tax
    //
    //  3. show credit card payment screen
    //      get nonce
    //
    //  4. pay card
    //      use pre-built order

    // choosePay - choose which items to pay:
    //      show unpaid items and check boxes of ones that can be paid
    choosePay(totalDue) {
        clear_message();
        clear_message('payDueMessageDiv');
        clear_message('makePayMessageDiv');
        this.#planAllorPartial = null;
        let html = `
        <div class="row mt-3">
            <div class="col-sm-1" style="text-align: right"><b>Pay</b></div>
            <div class="col-sm-2"><b>Added By</b></div>
            <div class="col-sm-2"><b>For</b></div>
            <div class="col-sm-3"><b>Membership</b></div>
            <div class="col-sm-1" style="text-align: right"><b>Price</b></div>
            <div class="col-sm-1" style="text-align: right"><b>Already Paid</b></div>
            <div class="col-sm-1" style="text-align: right"><b>Balance Due</b></div>        
        </div>`;

        // build a list of memberships to pay with check boxes
        this.#partialPayAmt = 0;
        this.#fullPayAmt = Number(totalDue);
        this.#payAllList = [];
        this.#paySelectedList = [];
        this.#selectIds = {};
        this.#selectMems = {};
        let unpaids = 0;
        for (let i = 0; i < membershipsPurchased.length; i++) {
            let mem = membershipsPurchased[i];
            if (mem.status != 'unpaid')
                continue;

            unpaids++;
            mem.payThis = 1;
            this.#payAllList.push(make_copy(mem));
            mem.payThis = 0;
            this.#selectMems['other-' + mem.regid] = mem;
            let price = this.#currencyFmt.format(Number(mem.actPrice).toFixed(2));
            let paid = this.#currencyFmt.format(Number(Number(mem.actPaid) + Number(mem.actCouponDiscount)).toFixed(2))
            let bal = Number(Number(mem.actPrice) - (Number(mem.actPaid) + Number(mem.actCouponDiscount))).toFixed(2);
            html += `
        <div class="row">
            <div class="col-sm-1" style="text-align: right">
                <input type="checkbox" id="other-` + mem.regid + '" name="other-' + mem.regid +
                    '" onChange="portal.choosePayToggle(' + mem.regid + ',' + bal + `, false);">
            </div>
            <div class="col-sm-2" onclick="portal.choosePayToggle(` + mem.regid + ',' + bal + ', true);">' + mem.purchaserName + `</div>
            <div class="col-sm-2" onclick="portal.choosePayToggle(` + mem.regid + ',' + bal + ', true);">' + mem.fullName + `</div>
            <div class="col-sm-3">
                <label for="other-` + mem.regid + '">' + mem.label + `</label>
            </div>
            <div class="col-sm-1" onclick="portal.choosePayToggle(` + mem.regid + ',' + bal + ', true);" style="text-align: right">' + price + `</div>
            <div class="col-sm-1" onclick="portal.choosePayToggle(` + mem.regid + ',' + bal + ', true);" style="text-align: right">' + paid + `</div>
            <div class="col-sm-1" onclick="portal.choosePayToggle(` + mem.regid + ',' + bal + ', true);" style="text-align: right">' + this.#currencyFmt.format(bal) + `</div>
        </div>
`;
        }
        if (unpaids > 1) {
            html += `
    <div class="row mt-3 mb-2">
        <div class="col-sm-2" style="text-align: right"><button class="btn btn-sm btn-primary pt-0 pb-0" id="partialPayBTN"
            onClick="portal.makeOrder(null, 2);" disabled>
            Pay Selected
        </button></div>
        <div class="col-sm-auto">
            <b>The total amount due for selected memberships totaling
                <span id="partialPayDue2">` + this.#currencyFmt.format(Number(this.#partialPayAmt).toFixed(2)) + `</span></b>
        </div>
    </div>
    <div class="row mt-1 mb-2" id="paySelectedPlanRow" hidden>
        <div class="col-sm-2" style="text-align: right"><button class="btn btn-sm btn-primary pt-0 pb-0" id="partialPayBTNPlan"
            onClick="portal.buildPlan(2);">
            Make Plan for Selected
        </button></div>
        <div class="col-sm-auto">
            <b>Create a payment plan for selected memberships totaling
                <span id="partialPayDue1">` + this.#currencyFmt.format(Number(this.#partialPayAmt).toFixed(2)) + `</span></b>
        </div>
    </div>
`
        }
        html += `
    <div class="row mt-1 mb-2">
        <div class="col-sm-2" style="text-align: right"><button class="btn btn-sm btn-primary pt-0 pb-0"
            onClick="portal.makeOrder(null);">Pay All</button></div>
        <div class="col-sm-auto">
            <b>The total amount due for all memberships is ` +
            this.#currencyFmt.format(Number(totalDue).toFixed(2)) + `</b>
        </div>
    </div>
    <div class="row mt-1 mb-3" id="payAllPlanRow">
        <div class="col-sm-2" style="text-align: right"><button class="btn btn-sm btn-primary pt-0 pb-0"
            onClick="portal.buildPlan(1);">Make Plan for All</button></div>
        <div class="col-sm-auto">
            <b>Create a payment plan for the total amount due for all memberships of ` +
            this.#currencyFmt.format(Number(totalDue).toFixed(2)) + `</b>
        </div>
    </div>
`;
        this.#paymentDueBody.innerHTML = html;
        this.#paymentDueModal.show();
        this.#selectIdKeys = Object.keys(this.#selectIds);
        this.#selectIds = {};
        for (let idkey of Object.keys(this.#selectMems)) {
            this.#selectIds[idkey] = {};
            this.#selectIds[idkey].id = idkey.replace('other-', '');
            this.#selectIds[idkey].dom = document.getElementById(idkey);
            this.#selectIds[idkey].mem = this.#selectMems[idkey];
        }
        let plansEligible = paymentPlans.plansEligible(this.#payAllList);
        document.getElementById('payAllPlanRow').hidden = !plansEligible;
    }

    choosePayToggle(id, bal, doToggle = false) {
        let element = document.getElementById('other-' + id);
        let checked = element.checked;
        if (doToggle) {
            checked = !checked;
            element.checked = checked;
        }
        if (checked) {
            this.#partialPayAmt += Number(bal);
        } else {
            this.#partialPayAmt -= Number(bal);
        }
        let balId = document.getElementById('partialPayDue1');
        if (balId) balId.innerHTML = Number(this.#partialPayAmt).toFixed(2);
        balId = document.getElementById('partialPayDue2');
        if (balId) balId.innerHTML = Number(this.#partialPayAmt).toFixed(2);

        let btn = document.getElementById('partialPayBTN');
        if (btn) {
            btn.disabled = this.#partialPayAmt == 0;
            document.getElementById('partialPayBTNPlan').disabled = this.#partialPayAmt == 0;
        }
        this.#paySelectedList = [];
        for (let idkey of Object.keys(this.#selectMems)) {
            let tag = this.#selectIds[idkey];
            if (tag.dom.checked)
                this.#paySelectedList.push(this.#selectIds[idkey].mem);
        }
        let plansEligible = paymentPlans.plansEligible(this.#paySelectedList);
        document.getElementById('paySelectedPlanRow').hidden = !plansEligible;
    }

    // Build Plan - select and start the build plan process
    buildPlan(type) {
        clear_message();
        clear_message('payDueMessageDiv');
        clear_message('makePayMessageDiv');
        let html = '';
        if (type == 1) {
            paymentPlans.plansEligible(this.#payAllList);
            this.#totalAmountDue = this.#fullPayAmt;
            this.#planAllorPartial = 'all';
        } else {
            paymentPlans.plansEligible(this.#paySelectedList);
            this.#totalAmountDue = this.#partialPayAmt;
            this.#planAllorPartial = 'partial';
        }

        let plans = paymentPlans.isMatchingPlans();
        if (this.#totalAmountDue < 0) {
            this.#totalAmountDue = 0;
        }
        this.#paymentAmount = this.#totalAmountDue;
        this.#planPayment = 0;
        this.#disableButtonNames = 'payBalanceBTNs';

        if (!plans) {
            show_message("No eligible plans found, use Pay All or Pay Selected", 'payDueMessageDiv', 'error');
            return;
        }

        let buttonName = '';
        let amountDueName = '';
        let makeOrderOther = 0;
        if (type == 2) {
            buttonName = 'Pay Selected Items Amount Due'
            amountDueName = 'selected items is ';
            makeOrderOther = 2;
        } else {
            buttonName = 'Pay Total Cart Items Amount Due'
            amountDueName = 'cart is ';
        }
        this.#payDueSubmitButton.innerHTML = buttonName;
        html += `
    <div class="row mt-3">
        <div class="col-sm-auto"><button class="btn btn-sm btn-primary pt-0 pb-0" onClick='portal.makeOrder(null , ` +
            makeOrderOther + `);'>` +
                buttonName + `</button></div>
        <div class="col-sm-auto">
            <b>Your total amount due for the ` + amountDueName + Number(this.#totalAmountDue).toFixed(2) + `</b>
        </div>
    </div>
`;

        html += `
    <div class="row mt-2">
        <div class="col-sm-12">
            You can pay this balance in full without creating a payment plan by using the "` + buttonName +
            `" button above or at the bottom of this popup.
        </div>
    </div>
     <div class="row mt-2">
        <div class="col-sm-12">
            You can pay by with one of the following payment plans using the "Select As Shown" button to use the plan with the default values,<br/>
            or "Customize" button (if available for that plan) to select your own down payment, number of payments and days between payments.
        </div>
    </div>
`;
        html += paymentPlans.getMatchingPlansHTML('portal');

        this.#paymentDueBody.innerHTML = html;
    }

    closePaymentDueModal() {
        this.#paymentDueModal.hide();
    }

    openPaymentDueModal() {
        this.#paymentDueModal.show();
    }

    // makeOrder - make call to create an order in the system and return the order Id, the amount due, the tax due and the total amount due
    makeOrder(plan, other = 0) {
        clear_message('payDueMessageDiv');
        this.#orderMemberships = [];

        if (other < 0)
            other = this.#planAllorPartial == 'all' ? 1 : 2;

        // disable the button that called us
        let enableButtonNames = null;
        if (this.#disableButtonNames) {
            enableButtonNames = this.#disableButtonNames;
        }
        $('[name="' + this.#disableButtonNames + '"]').prop('disabled', true);

        if (other == 1 || (other == 0 && this.#planAllorPartial == 'all')) {
            this.#paymentAmount = this.#fullPayAmt;
            this.#orderMemberships = this.#payAllList;
            this.#selectedItems = false;
        } else if (other == 2 || (other == 0 && this.#planAllorPartial == 'partial')) {
            this.#paymentAmount = this.#partialPayAmt;
            this.#orderMemberships = this.#paySelectedList;
            this.#selectedItems = true;
        } else {
            this.#paymentAmount = this.#fullPayAmt;
            this.#orderMemberships = this.#payAllList;
            this.#selectedItems = false;
        }

        if (plan == null) {
            this.#paymentPlan = null;
        } else {
            this.#paymentPlan = plan;
            this.#paymentAmount = plan.currentPayment;
            this.#totalAmountDue = plan.currentPayment;
        }
        let cancelOrderId = null;
        if (this.#orderData && this.#orderData.rtn && this.#orderData.rtn.orderId)
            cancelOrderId = this.#orderData.rtn.orderId;

        let newplan = false;
        if (this.#paymentPlan != null)
            if (this.#paymentPlan.new)
                newplan = true;

        // check if any of the memberships are in plan and if so, set plan recast to recompute the plan
        if (plan == null || newplan == true) {
            for (let mem of this.#orderMemberships) {
                if (mem.hasOwnProperty('planId') && mem.planId && mem.planId > 0) {
                    this.#planRecast = true;
                    break;
                }
            }
        }
        // transaction comes from session, person paying come from session, we will compute what was paid
        let data = {
            loginId: config.id,
            loginType: config.idType,
            action: 'portalOrder',
            plan: (this.#paymentPlan != null || this.#existingPlan != null) ? 1 : 0,
            existingPlan: this.#existingPlan,
            planRec: this.#paymentPlan,
            newplan: newplan ? 1 : 0,
            planPayment: this.#planPayment,
            otherMemberships: JSON.stringify(this.#orderMemberships),
            amount: this.#paymentAmount,
            couponDiscount: this.#couponDiscount,
            preCouponAmountDue: this.#preCouponAmountDue,
            couponCode: coupon.getCouponCode(),
            couponSerial: coupon.getCouponSerial(),
            cancelOrderId: cancelOrderId,
        };
        $.ajax({
            url: "scripts/portalOrder.php",
            data: data,
            method: 'POST',
            success: function (data, textStatus, jqXhr) {
                if (data.status == 'error') {
                    portal.openPaymentDueModal();
                    if (enableButtonNames)
                        $('[name="' + enableButtonNames + '"]').prop('disabled', false);
                    show_message(data.message, 'error', 'payDueMessageDiv');
                    return false;
                }
                checkResolveUpdates(data);
                if (data != '') {
                    portal.setOrderData(data);
                    portal.makePayment(plan);
                } else {
                    show_message("Error creating order, seek assistance", 'error');
                }
                if (enableButtonNames)
                    $('[name="' + enableButtonNames + '"]').prop('disabled', false);
                return true;
            },
            error: function (jqXHR, textStatus, errorThrown) {
                if (enableButtonNames)
                    $('[name="' + enableButtonNames + '"]').prop('disabled', false);
                showAjaxError(jqXHR, textStatus, errorThrown, 'eiMessageDiv');
                return false;
            },
        });
    }

    // make payment
    makePayment(plan) {
        let html = '';
        let done = false;
        if (plan == null && this.#paymentPlan != null) {
            this.#paymentPlan = null;
            this.makeOrder(null);
            return;
        } else if (this.#orderData && this.#orderData.post && this.#orderData.post.planPayment && this.#orderData.post.planPayment == 1) {
            html = `
        <div class="row mt-4 mb-4">
            <div class="col-sm-auto"><b>You are making a payment on ` + this.#orderData.post.existingPlan.name +
                ' payment plan of ' + Number(this.#orderData.amount).toFixed(2) + `</b></div>
         </div>        
`;
            done = true;
        } else {
            this.#paymentPlan = plan;
        }

        this.#paymentAmount = Number(this.#orderData.rtn.totalAmt);
        if (this.#orderData.rtn.taxAmt > 0) {
            let preTaxWording = this.#selectedItems ? 'for the selected items' : 'for the cart';
            html += `
            <div class="row mt-4">
                <div class="col-sm-4"><b>The Pre-Tax Amount Due ` + preTaxWording + ` is:</b></div>
                <div class="col-sm-1" style="text-align: right;"><b>` + this.#currencyFmt.format(Number(this.#orderData.rtn.preTaxAmt).toFixed(2)) + `</b></div>
            </div>`;
            this.#taxes = this.#orderData.rtn.taxes;
            if (Object.keys(config.taxRates).length > 0) {
                for (let tax in config.taxRates) {
                    let rate = config.taxRates[tax];
                    let amt = this.#taxes[tax];
                    if (amt != null) {
                        html += `
    <div class="row mt-1">
        <div class="col-sm-4">` + rate.label + `:</div>
        <div class="col-sm-1" style="text-align: right;">` + this.#currencyFmt.format(Number(amt).toFixed(2)) + `</div>
    </div>`;
                    }
                }
            }
            if (this.#orderData.rtn.taxAmt > 0) {
                html += `
    <div class="row mt-1 mb-3">
        <div class="col-sm-4">Total Sales Tax:</div>
        <div class="col-sm-1" style="text-align: right;" id="pay-tax-amt">` +
                    this.#currencyFmt.format(Number(this.#orderData.rtn.taxAmt).toFixed(2)) + `</div>
    </div>`;
            }
        }

        if (plan == null) {
            let totalWording = this.#selectedItems ? 'total amount of the selected items' : 'total amount for the cart';
            html += `
        <div class="row mt-2 mb-4">
            <div class="col-sm-auto"><strong>You are paying the ` + totalWording + `, so the payment amount is ` +
                this.#currencyFmt.format(Number(this.#paymentAmount).toFixed(2)) + `</strong></div>
         </div>
`;
        } else if (!done) {
            html = `
        <div class="row mt-2 mb-4">
            <div class="col-sm-auto"><b>The Current Amount Due to create the payment plan ` + plan.plan.name + ' is ' + Number(plan.currentPayment).toFixed(2) + `</b></div>
         </div>
`;
        }
        this.#makePaymentBody.innerHTML = html;
        this.#paymentDueModal.hide();
        this.#makePaymentModal.show();
    }

    // makePlanPayment - make a payment on a plan
    makePlanPayment(payorPlan, planName, paymentAmt, recast) {
        this.#existingPlan = payorPlan;
        this.#paymentAmount = paymentAmt;
        payorPlan.currentPayment = paymentAmt;
        if (recast)
            this.#planRecast = recast;
        this.#planPayment = 1;
        this.makeOrder(payorPlan, 0);
    }

    // makePurchase - make the membership/plan purchase.
    makePurchase(token, label = '') {
        if (token == 'test_ccnum') {  // this is the test form
            token = document.getElementById(token).value;
        }

        // our form
        let id = document.getElementById("purchase");
        if (id)
            id.disabled = true;
        // squares form
        let ids = document.getElementById("card-button");
        if (ids)
            ids.disabled = true;

        let newplan = false;
        if (this.#paymentPlan != null)
            if (this.#paymentPlan.new)
                newplan = true;

        let totalAmountDue = this.#paymentAmount;
        let taxAmount = 0
        let preTaxAmount = totalAmountDue;
        if (this.#existingPlan == null && this.#orderData && this.#orderData.rtn) {
            preTaxAmount = this.#orderData.rtn.preTaxAmt;
            taxAmount = this.#orderData.rtn.taxAmt;
        }

        let orderId = '';
        if (this.#orderData && this.#orderData.rtn && this.#orderData.rtn.orderId) {
            orderId = this.#orderData.rtn.orderId;
        }

        let badges = [];
        if (this.#orderData && this.#orderData.rtn && this.#orderData.rtn.results && this.#orderData.rtn.results.badges)
            badges = this.#orderData.rtn.results.badges;

        // transaction comes from session, person paying come from session, we will compute what was paid
        let data = {
            loginId: config.id,
            loginType: config.idType,
            action: 'portalPayment',
            plan: (this.#paymentPlan != null || this.#existingPlan != null) ? 1 : 0,
            existingPlan: this.#existingPlan,
            planRec: this.#paymentPlan,
            newplan: newplan ? 1 : 0,
            planPayment: this.#planPayment,
            otherMemberships: JSON.stringify(this.#orderMemberships),
            nonce: token,
            amount: this.#paymentAmount,
            totalAmountDue: this.#paymentAmount,
            preTaxAmount: preTaxAmount,
            taxAmount: taxAmount,
            couponDiscount: this.#couponDiscount,
            preCouponAmountDue: this.#preCouponAmountDue,
            couponCode: coupon.getCouponCode(),
            couponSerial: coupon.getCouponSerial(),
            planRecast: this.#planRecast ? 1 : 0,
            orderId: orderId,
            badges: JSON.stringify(badges),
        };
        $.ajax({
            url: "scripts/portalPayment.php",
            data: data,
            method: 'POST',
            success: function (data, textStatus, jqXhr) {
                checkResolveUpdates(data);
                portal.makePurchaseSuccess(data);
                return true;
            },
            error: function (jqXHR, textStatus, errorThrown) {
                if (id)
                    id.disabled = false;
                // squares form
                if (ids)
                    ids.disabled = false;
                showAjaxError(jqXHR, textStatus, errorThrown, 'eiMessageDiv');
                return false;
            },
        });
    }

    makePurchaseSuccess(data) {
        console.log(data);
        if (data.status == 'error') {
            // our form
            let id = document.getElementById("purchase");
            if (id)
                id.disabled = false;
            // squares form
            id = document.getElementById("card-button");
            if (id)
                id.disabled = false;
            if (data.error) {
                show_message(data.error, 'error', 'makePayMessageDiv');
                return;
            }
            if (data.message) {
                show_message(data.message, 'error', 'makePayMessageDiv');
                return;
            }
            if (data.data) {
                show_message(data.data, 'error', 'makePayMessageDiv');
                return;
            }
        }

        // clear any order in progress
        this.#orderData = null;
        this.#fullPayAmt = 0;

        if (data.message)
            window.location = this.#portalPage + "?tab=" + hid + '&messageFwd=' + encodeURI(data.message);
        else {
            let message = 'Payment succeeded, ' + data.rows_upd + ' memberships and other items updated';
            window.location = this.#portalPage + "?tab=" + hid + '&messageFwd=' + encodeURI(message);
        }
    }

    // fetch a receipt by transaction number
    transReceipt(transId) {
        this.#receiptEmailAddress = null;
        clear_message();
        let script = 'scripts/getReceipt.php';
        let data = {
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
                portal.showReceipt(data);
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
        let receipt = data.receipt;
        this.#receiptDiv.innerHTML = receipt.receipt_html;
        this.#receiptTables.innerHTML = receipt.receipt_tables;
        this.#receiptText.innerHTML = receipt.receipt;
        this.#receiptEmailAddress = receipt.payor_email;
        this.#receiptEmailBtn.innerHTML = "Email Receipt to " + receipt.payor_name + ' at ' + this.#receiptEmailAddress;
        this.#receiptTitle.innerHTML = "Registration Receipt for " + receipt.payor_name;
        this.#receiptModal.show();
    }

    emailReceipt(addrchoice) {
        let success = '';
        if (this.#receiptEmailAddress == null)
            return;

        if (success == '')
            success = this.#receiptEmailBtn.innerHTML.replace("Email Receipt to", "Receipt sent to");

        let data = {
            loginId: config.id,
            loginType: config.idType,
            email: this.#receiptEmailAddress,
            okmsg: success,
            text: this.#receiptText.innerHTML,
            html: this.#receiptTables.innerHTML,
            subject: this.#receiptTitle.innerHTML,
            success: success,
        };
        let _this = this;
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

        let color = false;
        $("div[name^='t-']").each(function () {
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

        let color = false;
        $("div[name^='t-']").each(function () {
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

    // coupon related items
    couponDiscountUpdate(couponAmounts) {
        this.#preCouponAmountDue = Number(couponAmounts.totalDue);
        this.#subTotalColDiv.innerHTML = currencyFmt.format(Number(couponAmounts.totalDue).toFixed(2));
        this.#couponDiscount = Number(couponAmounts.discount);
        this.#couponDiscountDiv.innerHTML = currencyFmt.format(Number(couponAmounts.discount).toFixed(2));
        this.#totalAmountDue = Number(couponAmounts.totalDue - couponAmounts.discount);
        $('span[name="totalDueAmountSpan"]').html('$&nbsp;' + this.#totalAmountDue.toFixed(2));

        if (this.#payBalanceBTN != null && paymentPlanList != null) {
            if (paymentPlans.plansEligible(membershipsPurchased)) {
                this.#payBalanceBTN.innerHTML = "Show payment plan options";
            } else {
                this.#payBalanceBTN.innerHTML = "Pay Balance";
            }
        }
    }

    // setFocus - jump to specific areas on the page
    setFocus(area) {
        switch (area) {
            case 'paymentDiv':
                $(window).scrollTop($('#paymentSectionDiv').offset().top);
                break;
        }
    }

    vote() {
        let rights = {NomNom: 1};
        this.getJWT(rights, config.nomnomURL);
    }

    virtual() {
        let rights = {Virtual: 1};
        this.getJWT(rights, config.virtualURL);
    }

    // voting, virtual, etc. - get jwt strings
    getJWT(rights, url) {
        let data = {
            loginId: config.id,
            loginType: config.idType,
            rights: rights,
        }
        clear_message();
        let script = 'scripts/getJWT.php';
        $.ajax({
            method: 'POST',
            url: script,
            data: data,
            success: function (data, textStatus, jqXhr) {
                if (data.status == 'error') {
                    show_message(data.message, 'error');
                } else if (data.status == 'warn') {
                    show_message(data.message, 'warn');
                } else {
                    // we have a response
                    if (config.debug > 0) {
                        console.log(data.rights);
                        console.log(data.payload);
                        console.log(data.jwt);
                        console.log(url + '?r=' + data.jwt);
                    }
                    openWindowWithFallback(url + '?r=' + data.jwt);
                }
            },
            error: function (jqXHR, textStatus, errorThrown) {
                showAjaxError(jqXHR, textStatus, errorThrown);
                return false;
            },
        });
    }

    // site selection
    siteSelect(url) {
        openWindowWithFallback(url);
    }

    // passkey refresh
    // login with passkey - ask for a confirm and return either retry or go to portal
    loginWithPasskey() {
        passkeyRequest('scripts/passkeyActions.php', 'portal.php', 'portal');
    }

    // set portal page tab
    settab(tabname) {
        if (hid == 0)
            return;

        // console.log("switching to " + tabname);
        for (let i = 0; i < tabs.length; i++) {
            // console.log("remove active from " + tabs[i] + '-tab');
            let el = document.getElementById(tabs[i] + '-tab');
            let left = 1;
            let right = 1;
            if (i == 0) left = 2;
            if (i == tabs.length - 1) right = 2;
            el.style = "border-bottom: 4px solid #0000FF; border-right: " + right + "px solid #808080;" +
                " border-left: " + left + "px solid #808080;" +
                " background-color: #E8E8E8; border-radius: 0px;";

            el = document.getElementById(tabs[i] + '-pane');
            el.classList.remove("active", "show");
        }

        document.getElementById(tabname + '-tab').style =
            "border-width: 4px 4px; border-color: var(--bs-primary); border-radius: 20px 20px 0px 0px; border-bottom: 0px;";
        document.getElementById(tabname + '-pane').classList.add("active", "show");
        hid = tabname;
    }
}


function makePurchase(token, label) {
    portal.makePurchase(token, label);
}

function addPerson(data) {
    portal.updatePerson(data);
}

function redoAddress() {
    portal.editPersonSubmit();
}
