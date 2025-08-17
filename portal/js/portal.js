// Main portal javascript, also requires base.js

var portal = null;
var coupon = null;

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
}

class Portal {
    // this page name for window.location to avoid refresh errors
    #portalPage = 'portal.php';
    // edit person modal
    #editPersonModal = null;
    #editPersonModalElement = null;
    #editPersonTitle = null;
    #editPersonSubmitBtn = null;
    #epHeaderDiv = null;
    #epPersonIdField = null;
    #epPersonTypeField = null;
    #fnameField = null;
    #mnameField = null;
    #lnameField = null;
    #suffixField = null;
    #legalNameField = null;
    #pronounsField = null;
    #addrField = null;
    #addr2Field = null;
    #cityField = null;
    #stateField = null;
    #zipField = null;
    #countryField = null;
    #email1Field = null;
    #phoneField = null;
    #badgenameField = null;
    #uspsDiv= null;
    
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
    #personSave = null;
    #uspsAddress = null;
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
    #otherPayAmt = 0;
    #otherPay = 0;
    #orderData = null;
    #orderBadges = null;
    #disableButtonNames = null;

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
    #newPolicies = null;

    constructor() {
        var id;
        id = document.getElementById("editPersonModal");
        if (id) {
            this.#editPersonModalElement = id;
            this.#editPersonModal = new bootstrap.Modal(id, {focus: true, backdrop: 'static'});
            this.#editPersonTitle = document.getElementById('editPersonTitle');
            this.#editPersonSubmitBtn = document.getElementById('editPersonSubmitBtn');
            this.#epHeaderDiv = document.getElementById("epHeader");
            this.#epPersonIdField = document.getElementById("epPersonId");
            this.#epPersonTypeField = document.getElementById("epPersonType");
            this.#fnameField = document.getElementById("fname");
            this.#mnameField = document.getElementById("mname");
            this.#lnameField = document.getElementById("lname");
            this.#suffixField = document.getElementById("suffix");
            this.#legalNameField = document.getElementById("legalName");
            this.#pronounsField = document.getElementById("pronouns");
            this.#addrField = document.getElementById("addr");
            this.#addr2Field = document.getElementById("addr2");
            this.#cityField = document.getElementById("city");
            this.#stateField = document.getElementById("state");
            this.#zipField = document.getElementById("zip");
            this.#countryField = document.getElementById("country");
            this.#email1Field = document.getElementById("email1");
            this.#phoneField = document.getElementById("phone");
            this.#badgenameField = document.getElementById("badgename");
            this.#uspsDiv = document.getElementById("uspsblock");
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

        this.#purchasedShowAll = document.getElementById('btn-showAll');
        this.#purchasedShowUnpaid = document.getElementById('btn-showUnpaid');
        this.#purchasedHideAll = document.getElementById('btn-hideAll');

        /*  // Default to unpaid
        if (this.#purchasedShowUnpaid) {
            if (this.#purchasedShowUnpaid.disabled == true)
                this.showUnpaid();
        } else if (this.#purchasedShowAll) {
            if (this.#purchasedShowAll.disabled == true)
                this.showAll();
            else
                this.hideAll();
        } */

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

        this.#subTotalColDiv = document.getElementById('subTotalColDiv');
        this.#couponDiscountDiv = document.getElementById('couponDiscountDiv');
        var _this = this;
        var modalCalled = false;

        // enable all tooltips
        const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
        const tooltipList = [...tooltipTriggerList].map(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl));

        // do any people need to have their profiles edited to handle missing policies
        $('.need-policies').each(function(i, obj) {
            if (modalCalled)
                return;
            var dataset = obj.dataset;
            var id = dataset.id;
            var type = dataset.type;
            show_message('Required Policies are not accepted', "error", 'epMessageDiv');
            _this.editPerson(id, type);
            modalCalled = true;
        });

        if (config.needInterests == 1) {
            if (modalCalled)
                return;
            _this.editInterests(config.id, config.idType);
        }
    }

    // set  / get functions
    setOrderData(data) {
        this.#orderData = data;
        if (this.#otherPay > 0)
            this.#otherPay = 2;
        this.#totalAmountDue = data.rtn.totalAmt;
    }

    // disassociate: remove the managed by link for this logged in person
    disassociate() {
        var data = {
            'managedBy': 'disassociate',
            loginId: config.id,
            loginType: config.idType,
        }
        var script = 'scripts/processDisassociate.php';
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
                    var divElement = document.getElementById('managedByDiv');
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
    editPerson(id, type) {
        if (this.#editPersonModal == null) {
            show_message('Edit Person is not available at this time', 'warn');
            return;
        }

        clear_message('epMessageDiv');
        // clear the old validation colors, first the policies
        if (policies) {
            for (var row in policies) {
                var policy = policies[row];
                if (policy.required == 'Y') {
                    var field = '#l_' + policy.policy;
                    $(field).removeClass('need');
                }
            }
        }
        // now clear the input fields
        $('#fname').removeClass('need');
        $('#lname').removeClass('need');
        $('#addr').removeClass('need');
        $('#city').removeClass('need');
        $('#state').removeClass('need');
        $('#zip').removeClass('need');

        this.#currentPerson = id;
        this.#currentPersonType = type;
        var data = {
            loginId: config.id,
            loginType: config.idType,
            getId: id,
            getType: type,
            memberships: 'N'
        }
        var script = 'scripts/getPersonInfo.php';
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
        var person = data.person;
        var post = data.post;
        if (data.policies)
            this.#oldPolicies = data.policies;

        this.#fullName = person.fullName;
        this.#editPersonTitle.innerHTML = '<strong>Editing: ' + this.#fullName + '</strong>';
        if (this.#uspsDiv && person.country == 'USA') {
            this.#editPersonSubmitBtn.innerHTML = 'Validate Address and Update ' + this.#fullName;
        } else {
            this.#editPersonSubmitBtn.innerHTML = 'Update ' + this.#fullName;
        }

        // now fill in the fields
        this.#epHeaderDiv.innerHTML = '<strong>Editing: ' + this.#fullName + ' (' + person.email_addr + ')</strong>';
        this.#epPersonIdField.value = post.getId;
        this.#epPersonTypeField.value = post.getType;
        this.#fnameField.value = person.first_name;
        this.#mnameField.value = person.middle_name;
        this.#lnameField.value = person.last_name;
        this.#suffixField.value = person.suffix;
        this.#legalNameField.value = person.legalName;
        this.#pronounsField.value = person.pronouns;
        this.#addrField.value = person.address;
        this.#addr2Field.value = person.addr_2;
        this.#cityField.value = person.city;
        this.#stateField.value = person.state;
        this.#zipField.value = person.zip;
        this.#countryField.value = person.country;
        this.#email1Field.innerHTML = person.email_addr;
        this.#phoneField.value = person.phone;
        this.#badgenameField.value = person.badge_name;

        this.#personSerializeStart = $("#editPerson").serialize();

        // policies
        if (this.#oldPolicies) {
            for (var row in this.#oldPolicies) {
                var policy = this.#oldPolicies[row];
                var id = document.getElementById('p_' + policy.policy);
                if (id) {
                    if (policy.response) {
                        id.checked = policy.response == 'Y';
                    } else {
                        id.checked = policy.defaultValue == 'Y';
                    }
                }
            }
        }

        this.#editPersonModal.show();
        var focusField = this.#fnameField;
        setTimeout(() => { focusField.focus({focusVisible: true}); }, 600);
    }

    // called on the close buttons for the modal, confirm close with changes pending
    checkEditPersonClose() {
        var beforeClose = $("#editPerson").serialize();
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

        var personData = null;
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
        var focusField = this.#changeEmailNewEmailAddr;
        setTimeout(() => { focusField.focus({focusVisible: true}); }, 600);
    }

    // process auto enable of submit button
    changeEmailChanged(autoCall) {
        if (!this.#changeEmailNewEmailAddr) {
            this.#changeEmailSubmitBtn.disabled = true;
            return;
        }
        var email = this.#changeEmailNewEmailAddr.value;
        if (email == null || email == "") {
            this.#changeEmailSubmitBtn.disabled = true;
            return;
        }

        var valid = validateAddress(email);
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
        var email = this.#changeEmailNewEmailAddr.value;
        if (!validateAddress(email)) {
            show_message("Please enter a valid email address", 'warn');
            this.#changeEmailSubmitBtn.disabled = true;
            return;
        }

        // ok valid email address, check if it's a legal one for us to use
        var data = {
            loginId: config.id,
            loginType: config.idType,
            email: email, // new email address
            currentPersonId:  this.#currentPerson,
            currentPersonType: this.#currentPersonType,
            action: 'validate'
        };
        var script = 'scripts/changeEmail.php';
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
        if (this.#uspsDiv == null)
            return;

        var country = this.#countryField.value;
        if (this.#uspsDiv && country == 'USA') {
            this.#editPersonSubmitBtn.innerHTML = 'Validate Address and Update ' + this.#fullName;
        } else {
            this.#editPersonSubmitBtn.innerHTML = 'Update ' + this.#fullName;
        }
    }

// validate the edit person form for saving
    validate(person, validateUSPS) {
        //process(formRef) {
        clear_message('epMessageDiv');
        var valid = true;
        var required = config.required;
        var message = "Please correct the items highlighted in red and validate again.";

        // trim trailing blanks
        var keys = Object.keys(person);
        for (var i = 0; i < keys.length; i++) {
            person[keys[i]] = person[keys[i]].trim();
        }

        if (person.country == 'USA') {
            message += "<br/>Note: If any of the address fields Address, City, State or Zip are used and the country is United States, " +
                "then the Address, City, State, and Zip fields must all be entered and the state field must be a valid USPS two character state code.";
        }
        // validation
        if (required != '') {
            // first name is required
            if (person.fname == '') {
                valid = false;
                $('#fname').addClass('need');
            } else {
                $('#fname').removeClass('need');
            }
        }

        if (required == 'all') {
            // last name is required
            if (person.lname == '') {
                valid = false;
                $('#lname').addClass('need');
            } else {
                $('#lname').removeClass('need');
            }
        }

        if (required == 'addr' || required == 'all' ||
            (person.country == 'USA' && this.#uspsDiv != null &&
                (person.addr != '' || person.city != '' || person.state != '' || person.zip != '')
            )
        ) {
            // address 1 is required, address 2 is optional
            if (person.addr == '') {
                valid = false;
                $('#addr').addClass('need');
            } else {
                $('#addr').removeClass('need');
            }

            // city/state/zip required
            if (person.city == '') {
                valid = false;
                $('#city').addClass('need');
            } else {
                $('#city').removeClass('need');
            }

            if (person.state == '') {
                valid = false;
                $('#state').addClass('need');
            } else {
                if (person.country == 'USA') {
                    if (person.state.length != 2) {
                        valid = false;
                        $('#state').addClass('need');
                    } else {
                        $('#state').removeClass('need');
                    }
                } else {
                    $('#state').removeClass('need');
                }
            }

            if (person.zip == '') {
                valid = false;
                $('#zip').addClass('need');
            } else {
                $('#zip').removeClass('need');
            }
        }

        // now verify required policies
        if (policies) {
            this.#newPolicies = URLparamsToArray($('#editPolicies').serialize());
            //console.log("New Policies:");
            //console.log(this.#newPolicies);
            for (var row in policies) {
                var policy = policies[row];
                if (policy.required == 'Y') {
                    var field = '#l_' + policy.policy;
                    if (typeof this.#newPolicies['p_' + policy.policy] === 'undefined') {
                        //console.log("required policy " + policy.policy + ' is not checked');
                        message += '<br/>You cannot continue until you agree to the ' + policy.policy + ' policy.';
                        $(field).addClass('need');
                        valid = false;
                    } else {
                        $(field).removeClass('need');
                    }
                }
            }
        }

        // don't continue to process if any are missing
        if (!valid) {
            show_message(message, "error", 'epMessageDiv');
            return false;
        }

        // Check USPS for standardized address
        if (this.#uspsDiv != null && person.country == 'USA' && person.city != '' && person.state != '/r' && validateUSPS == 0) {
            this.#personSave = person;
            this.#uspsAddress = null;
            var script = "scripts/uspsCheck.php";
            $.ajax({
                url: script,
                data: person,
                method: 'POST',
                success: function (data, textStatus, jqXhr) {
                    checkResolveUpdates(data);
                    if (data.status == 'error') {
                        show_message(data.message, 'error', 'epMessageDiv');
                        return false;
                    }
                    portal.showValidatedAddress(data);
                    return true;
                },
                error: function (jqXHR, textStatus, errorThrown) {
                    showAjaxError(jqXHR, textStatus, errorThrown, 'epMessageDiv');
                    return false;
                },
            });
            return false;
        }

        // no usps, we're done, save the changes
        this.editPersonSubmit(2);
    }

    showValidatedAddress(data) {
        var html = '';
        clear_message('epMessageDiv');
        if (data.error) {
            var errormsg = data.error;
            if (errormsg.substring(0, 5) == '400: ') {
                errormsg = errormsg.substring(5);
            }
            html = "<h4>USPS Returned an error<br/>validating the address</h4>" +
                "<div class='bg-dangrer text-white'><pre>" + errormsg + "</pre></div>\n";
        } else {
            this.#uspsAddress = data.address;
            if (this.#uspsAddress.address2 == undefined)
                this.#uspsAddress.address2 = '';

            html = '';
            if (this.#uspsAddress.valid != 'Valid') {
                html += "<div class='p-2 bg-danger text-white'>";
            }
            html += "<h4>USPS Returned: " + this.#uspsAddress.valid + "</h4>";

            // ok, we got a valid uspsAddress, if it doesn't match, show the block
            var person = this.#personSave;
            if (person.addr == this.#uspsAddress.address && person.addr2 == this.#uspsAddress.address2 &&
                person.city == this.#uspsAddress.city && person.state == this.#uspsAddress.state &&
                person.zip == this.#uspsAddress.zip) {
                portal.useMyAddress();
                return;
            }

            html += "<pre>" + this.#uspsAddress.address + "\n";
            if (this.#uspsAddress.address2)
                html += this.#uspsAddress.address2 + "\n";
            html += this.#uspsAddress.city + ', ' + this.#uspsAddress.state + ' ' + this.#uspsAddress.zip + "</pre>\n";

            if (this.#uspsAddress.valid == 'Valid')
                html += '<button class="btn btn-sm btn-primary m-1 mb-2" onclick="portal.useUSPS();">Update using the USPS validated address</button>'
            else
                html += "<p>Please check/verify the address you entered on the left.</p></div>";
        }
        html += '<button class="btn btn-sm btn-secondary m-1 mb-2 " onclick="portal.useMyAddress();">Update using the address as entered</button><br/>' +
            '<button class="btn btn-sm btn-secondary m-1 mt-2" onclick="portal.redoAddress();">I fixed the address, validate it again</button>';

        if (this.#uspsDiv != null) {
            this.#uspsDiv.innerHTML = html;
            this.#uspsDiv.classList.add('border', 'border-4', 'border-dark', 'rounded');
            this.#uspsDiv.scrollIntoView({behavior: 'instant', block: 'center'});
        }
    }

    // usps address post functions
    useUSPS() {
        var person = this.#personSave;
        person.addr = this.#uspsAddress.address;
        if (this.#uspsAddress.address2)
            person.addr2 = this.#uspsAddress.address2;
        else
            person.addr2 = '';
        person.city = this.#uspsAddress.city;
        person.state = this.#uspsAddress.state;
        person.zip = this.#uspsAddress.zip;

        this.#addrField.value = person.addr;
        this.#addr2Field.value = person.addr2;
        this.#cityField.value = person.city;
        this.#stateField.value = person.state;
        this.#zipField.value = person.zip;
        if (this.#uspsDiv != null) {
            this.#uspsDiv.classList.remove('border', 'border-4', 'border-dark', 'rounded');
            this.#uspsDiv.innerHTML = '';
        }

        this.editPersonSubmit(1);
    }

    useMyAddress() {
        if (this.#uspsDiv != null) {
            this.#uspsDiv.innerHTML = '';
            this.#uspsDiv.classList.remove('border', 'border-4', 'border-dark', 'rounded');
        }
        this.editPersonSubmit(1);
    }

    redoAddress() {
        if (this.#uspsDiv != null) {
            this.#uspsDiv.innerHTML = '';
            this.#uspsDiv.classList.remove('border', 'border-4', 'border-dark', 'rounded');
        }
        this.editPersonSubmit(0);
    }

    // now submit the updates to the person
    // validateUSPS = 0 for do USPS validation, 1 = validate form, but not USPS, 2 = skip all validation
    editPersonSubmit(validateUSPS = 0) {
        clear_message();
        var person = URLparamsToArray($('#editPerson').serialize());
        if (validateUSPS != 2) {
            if (!this.validate(person, validateUSPS))
                return;
        }
        
        var data = {
            loginId: config.id,
            loginType: config.idType,
            person: person,
            currentPerson: this.#currentPerson,
            currentPersonType: this.#currentPersonType,
            oldPolicies: JSON.stringify(this.#oldPolicies),
            newPolicies: JSON.stringify(URLparamsToArray($('#editPolicies').serialize())),
        }
        if (config.debug & 1)
            console.log(data);

        var script = 'scripts/updatePersonInfo.php';
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

    updatePersonSuccess(data){
        if (data.status == 'error') {
            show_message(data.message, 'error', 'epMessageDiv');
        } else {
            if (config.debug & 1)
                console.log(data);
            show_message(data.message);
            this.#editPersonModal.hide();
            if (data.rows_upd > 0) {
                window.location = this.#portalPage + '?messageFwd=' + encodeURI(data.message);
            }
        }
    }

    addMembership(id, type) {
        var addForm = '<form id="AddUpgrade" action="addUpgrade.php" method="POST">\
            <input type="hidden" name="upgradeId" value="' + id + '">\
            <input type="hidden" name="upgradeType" value="' + type + '">\
            <input type="hidden" name="action" value="upgrade">\
            </form>';
        $('body').append(addForm);
        $('#AddUpgrade').submit();
        $('#AddUpgrade').remove();
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
        var data = {
            loginId: config.id,
            loginType: config.idType,
            getId: id,
            getType: type,
            memberships: 'N',
            interests: 'Y'
        }
        var script = 'scripts/getPersonInfo.php';
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
        var person = data.person;
        var post = data.post;
        this.#interests = data.interests;

        this.#fullName = person.fullName ;
        this.#editInterestsTitle.innerHTML = '<strong>Editing Interests for: ' + this.#fullName + '</strong>';

        // now fill in the fields
        this.#eiHeaderDiv.innerHTML = 'Editing Interests for: ' + this.#fullName;
        this.#eiPersonIdField.value = post.getId;
        this.#eiPersonTypeField.value = post.getType;

        for (var row in this.#interests) {
            var interest = this.#interests[row];
            var id = document.getElementById('i_' + interest.interest);
            id.checked = interest.interested == 'Y';
        }

        this.#interestsSerializeStart = $("#editInterests").serialize();
        this.#editInterestsModal.show();

    }

    // called on the close buttons for the modal, confirm close with changes pending
    checkEditInterestsClose() {
        var beforeClose = $("#editInterests").serialize();
        if (beforeClose != this.#interestsSerializeStart) {
            if (!confirm("There are unsaved changes to the Edit Interests Form.\nClick OK to close the form and discard the changes."))
                return false;
        }
        this.#editInterestsModal.hide();
    }
    // editInterestsSubmit - save back the interests
    editInterestSubmit() {
        clear_message();
        var data = {
            loginId: config.id,
            loginType: config.idType,
            existingInterests: JSON.stringify(this.#interests),
            newInterests: JSON.stringify(URLparamsToArray($('#editInterests').serialize())),
            currentPerson: this.#currentPerson,
            currentPersonType: this.#currentPersonType,
        }
        if (config.debug & 1)
            console.log(data);

        var script = 'scripts/updateInterests.php';
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

    updateInterestsSuccess(data){
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
                window.location = this.#portalPage + '?messageFwd=' + encodeURI(data.message);
            }
        }
    }

    // payment functions
    // Payment flow:
    //  1. determine what to pay
    //      a. payBalance
    //          directly make full order
    //      b. Pay using payment Plan
    //          allow them to choose and build payment plan
    //          build order with that payment plan
    //      c. Payment on Plan
    //          directly make plan payment order
    //      d. Pay Other
    //          allow them to choose what to pay
    //          build order
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

    payBalance(totalDue, skipPlan=false) {
        clear_message();
        clear_message('payDueMessageDiv');
        clear_message('makePayMessageDiv');
        this.#otherPay = 0;
        var html = '';
        var plans = paymentPlans.isMatchingPlans();

        if (this.#totalAmountDue == null) {
            this.#totalAmountDue = totalDue;
        } else if (this.#totalAmountDue + this.#couponDiscount != totalDue) {
            this.#totalAmountDue = totalDue - this.#couponDiscount;
        }
        if (this.#totalAmountDue < 0) {
            this.#totalAmountDue = 0;
        }
        this.#paymentAmount = this.#totalAmountDue;
        this.#planPayment = 0;

        this.#disableButtonNames = 'payBalanceBTNs';

        if (skipPlan || !plans) {
            this.makeOrder(null, 0);
            return;
        }

        html = `
    <div class="row mt-3">
        <div class="col-sm-auto"><button class="btn btn-sm btn-primary pt-0 pb-0" onClick='portal.makeOrder(null, 0);'>Pay Total Amount Due</button></div>
        <div class="col-sm-auto">
            <b>Your total amout due is ` + Number(this.#totalAmountDue).toFixed(2) + `</b>
        </div>
    </div>
`;
        if (plans) {
            html += `
    <div class="row mt-2">
        <div class="col-sm-12">
            You can pay this balance in full using the "Pay Total Amount Due" button above or<br/>
            create one of the following payment plans using the "Select" or "Customize" payment plan buttons below:
        </div>
    </div>
`;
            html += paymentPlans.getMatchingPlansHTML('portal');
        }
        
        this.#paymentDueBody.innerHTML = html;
        this.#paymentDueModal.show();
    }

    closePaymentDueModal() {
        this.#paymentDueModal.hide();
    }

    // payOther - show registrations and check boxes of ones that can be paid
    payOther(totalDue) {
        clear_message();
        clear_message('payDueMessageDiv');
        clear_message('makePayMessageDiv');
        var html = `
        <div class="row mt-3">
            <div class="col-sm-1" style="text-align: right">Pay</div>
            <div class="col-sm-5">Membership</div>
            <div class="col-sm-1" style="text-align: right">Price</div>
            <div class="col-sm-1" style="text-align: right">Already Paid</div>
            <div class="col-sm-1" style="text-align: right">Balance Due</div>        
        </div>`;

        // build a list of memberships to pay with check boxes
        this.#partialPayAmt = 0;
        this.#otherPayAmt = Number(totalDue);
        for (var i = 0; i < paidOtherMembership.length; i++) {
            var mem = paidOtherMembership[i];
            var price =  Number(mem.actPrice).toFixed(2);
            var paid =  Number(Number(mem.actPaid) + Number(mem.actCouponDiscount)).toFixed(2);
            var bal = Number(Number(mem.actPrice) - (Number(mem.actPaid) + Number(mem.actCouponDiscount))).toFixed(2);
            html += `
        <div class="row">
            <div class="col-sm-1" style="text-align: right"><input type="checkbox" id="other-` +
                mem.id + '" name="other-' + mem.id + '" onChange="portal.payOtherToggle(' + mem.id + ',' + bal + `);"></div>
            <div class="col-sm-5"><label for="other-` + mem.id + `">` + mem.label + `</label></div>
            <div class="col-sm-1" style="text-align: right">` + price + `</div>
            <div class="col-sm-1" style="text-align: right">` + paid + `</div>
            <div class="col-sm-1" style="text-align: right">` + bal + `</div>
        </div>
`;
        }
        html += `
    <div class="row mt-3">
        <div class="col-sm-2" style="text-align: right"><button class="btn btn-sm btn-primary pt-0 pb-0" id="partialPayBTN"
            onClick="portal.makeOrder(null, 2);" disabled>
            Pay Selected
        </button></div>
        <div class="col-sm-auto">
            <b>The total amout due for selected memberships purchased by others totaling
                <span id="partialPayDue">` + Number(this.#partialPayAmt).toFixed(2) + `</span></b>
        </div>
    </div>
    <div class="row mt-1 mb-3">
        <div class="col-sm-2" style="text-align: right"><button class="btn btn-sm btn-primary pt-0 pb-0"
            onClick="portal.makeOrder(null, 1);">Pay All</button></div>
        <div class="col-sm-auto">
            <b>The total amout due for all memberships purchased by others is ` + Number(totalDue).toFixed(2) + `</b>
        </div>
    </div>
`;
        this.#paymentDueBody.innerHTML = html;
        this.#otherPay = 1;
        this.#paymentDueModal.show();
    }

    payOtherToggle(id, bal) {
        var element = document.getElementById('other-' + id);
        if (element.checked) {
            this.#partialPayAmt += Number(bal);
        } else {
            this.#partialPayAmt -= Number(bal);
        }
        document.getElementById('partialPayDue').innerHTML = Number(this.#partialPayAmt).toFixed(2);
        document.getElementById('partialPayBTN').disabled = this.#partialPayAmt == 0;
    }

    // makeOrder - make call to create an order in the system and return the order Id, the amount due, the tax due and the total amount due
    makeOrder(plan, other = 0) {
        if (plan == null) {
            this.#paymentPlan = null;
            if (other == 1) {
                this.#paymentAmount = this.makeOtherOrder('full');
                this.#otherPay = 1;
            } else if (other == 2) {
                this.#paymentAmount = this.makeOtherOrder('part');
                this.#otherPay = 1;
            } else {
                this.#otherPay = 0;
                this.#paymentAmount = this.#totalAmountDue;
            }
        } else {
            this.#paymentPlan = plan;
            this.#paymentAmount = plan.currentPayment;
            this.#totalAmountDue = plan.currentPayment;
        }
        var cancelOrderId = null;
        if (this.#orderData && this.#orderData.rtn && this.#orderData.rtn.orderId)
            cancelOrderId = this.#orderData.rtn.orderId;

        var newplan = false;
        if (this.#paymentPlan != null)
            if (this.#paymentPlan.new)
                newplan = true;

        // disable the button that called us
        var enableButtonNames = null;
        if (this.#disableButtonNames) {
            enableButtonNames = this.#disableButtonNames;
        }
        $('[name="' + this.#disableButtonNames + '"]').prop('disabled', true);
        // transaction comes from session, person paying come from session, we will compute what was paid
        var data = {
            loginId: config.id,
            loginType: config.idType,
            action: 'portalOrder',
            plan:   (this.#paymentPlan != null || this.#existingPlan != null) ? 1 : 0,
            existingPlan: this.#existingPlan,
            planRec: this.#paymentPlan,
            newplan: newplan ? 1 : 0,
            planPayment: this.#planPayment,
            otherPay: this.#otherPay,
            otherMemberships: JSON.stringify(paidOtherMembership),
            amount: this.#paymentAmount,
            totalAmountDue: this.#otherPay == 1 ? this.#paymentAmount : this.#totalAmountDue,
            couponDiscount: this.#couponDiscount,
            preCouponAmountDue: this.#preCouponAmountDue,
            couponCode: coupon.getCouponCode(),
            couponSerial: coupon.getCouponSerial(),
            planRecast: this.#planRecast ? 1 : 0,
            cancelOrderId: cancelOrderId,
        };
        $.ajax({
            url: "scripts/portalOrder.php",
            data: data,
            method: 'POST',
            success: function (data, textStatus, jqXhr) {
                checkResolveUpdates(data);
                portal.setOrderData(data);
                portal.makePayment(plan);
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
        var html = '';
        var done = false;
        if (plan == null) {
            this.#paymentPlan = null;
            if (this.#otherPay == 1) {
                /* this is from the click "Pay total amount due" in the modal footer */
                this.makeOrder(null, 1);
                return;
            }
            if (this.#otherPay == 2) {
                html = `
        <div class="row mt-4">
            <div class="col-sm-auto"><b>You are paying for memberships purchased by others for you.</b>
        </div>
`;
            }
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
            html += `
            <div className="row mt-4">
                <div className="col-sm-auto"><b>The Pre-Tax Amount Due is ` + Number(this.#orderData.rtn.preTaxAmt).toFixed(2) + `</b></div>
            </div>
            <div className="row mt-2">
                <div className="col-sm-auto"><b>` + this.#orderData.rtn.taxLabel + ` is ` + Number(this.#orderData.rtn.taxAmt).toFixed(2) + `</b></div>
                
            </div>
`;
        }

        if (plan == null) {
            html += `
        <div class="row mt-2 mb-4">
            <div class="col-sm-auto"><strong>You are paying the total amount, so the payment amount is ` + Number(this.#paymentAmount).toFixed(2) + `</strong></div>
         </div>
`;
        } else if (!done) {
            html = `
        <div class="row mt-2 mb-4">
            <div class="col-sm-auto"><b>The Current Amount Due to create the payment plan ` + plan.plan.name + ' is ' + Number(plan.currentPayment).toFixed(2) + `</b></div>
         </div>
`;
        }
        this.#otherPay = 0;
        this.#makePaymentBody.innerHTML = html;
        this.#paymentDueModal.hide();
        this.#makePaymentModal.show();
    }

    // makePlanPayment - make a payment on a plan
    makePlanPayment(payorPlan, planName, paymentAmt, recast) {
        this.#existingPlan = payorPlan;
        this.#paymentAmount = paymentAmt;
        payorPlan.currentPayment = paymentAmt;
        this.#planRecast = recast;
        this.#planPayment = 1;
        this.#otherPay = 0;
        this.makeOrder(payorPlan, 0);
    }

    // makeOtherOrder - pay some or all of the 'paid by other items due', mark the records and return to makeOrder
    makeOtherOrder(type) {
        // mark which ones to pay
        var amount = 0;
        for (var i = 0; i < paidOtherMembership.length; i++) {
            if (type == 'full') {
                paidOtherMembership[i]['payThis'] = 1;
                amount +=  Number(paidOtherMembership[i].actPrice) - (Number(paidOtherMembership[i].actPaid) + Number(paidOtherMembership[i].actCouponDiscount));
            } else {
                var checked = document.getElementById('other-' + paidOtherMembership[i]['id']).checked;
                paidOtherMembership[i]['payThis'] = checked ? 1 : 0;
                if (checked)
                    amount +=  Number(paidOtherMembership[i].actPrice) - (Number(paidOtherMembership[i].actPaid) + Number(paidOtherMembership[i].actCouponDiscount));
            }
        }

        return amount;
    }

    // makePurchase - make the membership/plan purchase.
    makePurchase(token, label = '') {
        if (token == 'test_ccnum') {  // this is the test form
            token = document.getElementById(token).value;
        }

        // our form
        var id = document.getElementById("purchase");
        if (id)
            id.disabled = true;
        // squares form
        var ids = document.getElementById("card-button");
        if (ids)
            ids.disabled = true;

        var newplan = false;
        if (this.#paymentPlan != null)
            if (this.#paymentPlan.new)
                newplan = true;

        var totalAmountDue = this.#otherPay == 1 ? this.#paymentAmount : this.#totalAmountDue;
        var taxAmount = 0;
        var preTaxAmount = totalAmountDue;
        if (this.#existingPlan == null && this.#orderData && this.#orderData.rtn) {
            preTaxAmount = this.#orderData.rtn.preTaxAmt;
            taxAmount = this.#orderData.rtn.taxAmt;
        }

        var orderId = '';
        if (this.#orderData && this.#orderData.rtn && this.#orderData.rtn.orderId) {
            orderId = this.#orderData.rtn.orderId;
        }

        var badges = [];
        if (this.#orderData && this.#orderData.rtn && this.#orderData.rtn.results && this.#orderData.rtn.results.badges)
            badges = this.#orderData.rtn.results.badges;

        // transaction comes from session, person paying come from session, we will compute what was paid
        var data = {
            loginId: config.id,
            loginType: config.idType,
            action: 'portalPayment',
            plan:   (this.#paymentPlan != null || this.#existingPlan != null) ? 1 : 0,
            existingPlan: this.#existingPlan,
            planRec: this.#paymentPlan,
            newplan: newplan ? 1 : 0,
            planPayment: this.#planPayment,
            otherPay: this.#otherPay,
            otherMemberships: JSON.stringify(paidOtherMembership),
            nonce: token,
            amount: this.#paymentAmount,
            totalAmountDue: this.#otherPay == 1 ? this.#paymentAmount : this.#totalAmountDue,
            preTaxAmountDue: preTaxAmount,
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
            var id = document.getElementById("purchase");
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
        this.#otherPayAmt = 0;
        this.#otherPay = 0;

        if (data.message)
            window.location = this.#portalPage + '?messageFwd=' + encodeURI(data.message);
        else {
            var message = 'Payment succeeded, ' + data.rows_upd + ' memberships and other items updated';
            window.location = this.#portalPage + '?messageFwd=' + encodeURI(message);
        }
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

    // coupon related items
    couponDiscountUpdate(couponAmounts) {
        this.#preCouponAmountDue = Number(couponAmounts.totalDue);
        this.#subTotalColDiv.innerHTML = '$' + Number(couponAmounts.totalDue).toFixed(2);
        this.#couponDiscount = Number(couponAmounts.discount);
        this.#couponDiscountDiv.innerHTML = '$' + Number(couponAmounts.discount).toFixed(2);
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
    setFocus(area){
         switch (area) {
            case 'paymentDiv':
                $(window).scrollTop($('#paymentSectionDiv').offset().top);
                break;
        }
    }

    vote() {
        var rights = { NomNom: 1};
        this.getJWT(rights, config.nomnomURL);
    }

    virtual() {
        var rights = { Virtual: 1};
        this.getJWT(rights, config.virtualURL);
    }

    // voting, virtual, etc. - get jwt strings
    getJWT(rights, url) {
        var data = {
            loginId: config.id,
            loginType: config.idType,
            rights: rights,
        }
        clear_message();
        var script = 'scripts/getJWT.php';
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
}


function makePurchase(token, label) {
    portal.makePurchase(token, label);
}
