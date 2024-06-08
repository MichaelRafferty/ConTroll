// addUpdate javascript, also requires base.js

var membership = null;

// initial setup
window.onload = function () {
    membership = new Membership();
}

class Membership {
    // current person info
    #epHeader = null;
    #memberships = null;
    #personInfo = null;
    #epHeaderDiv = null;
    #fnameField = null;
    #mnameField = null;
    #lnameField = null;
    #suffixField = null;
    #legalnameField = null;
    #addrField = null;
    #addr2Field = null;
    #cityField = null;
    #stateField = null;
    #zipField = null;
    #countryField = null;
    #uspsblock = null;
    #email1Field = null;
    #email2Field = null;
    #phoneField = null;
    #badgenameField = null;
    #contactField = null;
    #shareField = null;
    #uspsDiv= null;

    // this person info
    #addUpdateId = null;
    #addUpdateType = null;
    #uspsAddress = null;

    // age items
    #ageButtonsDiv = null;
    #currentAge = null;
    #memberAge = null;
    #memberAgeLabel = null;
    #memberAgeStatus = null;
    #memberAgeError = false;

    // flow items
    #auHeader = null
    #ageBracketDiv = null;
    #verifyPersonDiv = null;
    #getNewMembershipDiv = null;
    #cartDiv = null;
    #currentStep = 1;
    #step3submitDiv = null;

    constructor() {
        this.#memberships = [];

        this.#auHeader = document.getElementById("auHeader");
        // set up div elements
        this.#ageButtonsDiv = document.getElementById("ageButtons");
        this.#ageBracketDiv = document.getElementById("ageBracketDiv");
        this.#verifyPersonDiv = document.getElementById("verifyPersonDiv");
        this.#getNewMembershipDiv = document.getElementById("getNewMembershipDiv");
        this.#cartDiv = document.getElementById("cartDiv");
        this.#step3submitDiv = document.getElementById("step3submit");

        this.#ageBracketDiv.hidden = false;
        this.#verifyPersonDiv.hidden = true;
        this.#getNewMembershipDiv.hidden = true;
        this.#cartDiv.hidden = false;

        this.#epHeader = document.getElementById("epHeader");
        this.#fnameField = document.getElementById("fname");
        this.#mnameField = document.getElementById("mname");
        this.#lnameField = document.getElementById("lname");
        this.#suffixField = document.getElementById("suffix");
        this.#legalnameField = document.getElementById("legalname");
        this.#addrField = document.getElementById("addr");
        this.#addr2Field = document.getElementById("addr2");
        this.#cityField = document.getElementById("city");
        this.#stateField = document.getElementById("state");
        this.#zipField = document.getElementById("zip");
        this.#countryField = document.getElementById("country");
        this.#uspsblock = document.getElementById("uspsblock");
        this.#email1Field = document.getElementById("email1");
        this.#email2Field = document.getElementById("email2");
        this.#phoneField = document.getElementById("phone");
        this.#badgenameField = document.getElementById("badgename");
        this.#contactField = document.getElementById("contact");
        this.#shareField = document.getElementById("share");
        this.#uspsDiv = document.getElementById("uspsblock");

        if (config['action'] != 'new') {
            this.#addUpdateType = config['upgradeType'];
            this.#addUpdateId = config['upgradeId'];
            this.getPersonInfo(this.#addUpdateId, this.#addUpdateType, true);
        } else {
            this.buildAgeButtons();
        }

    }

// membership add/update functions
    // getPersonInfo
    getPersonInfo(id, type, ageButtons) {
        if (id == null) {
            return;
        }

        var data = {
            getId: id,
            getType: type,
            memberships: 'Y',
            ageButtons: ageButtons,
        }
        var script = 'scripts/getPersonInfo.php';
        $.ajax({
            method: 'POST',
            url: script,
            data: data,
            success: function (data, textStatus, jqXhr) {
                if (data['status'] == 'error') {
                    show_message(data['message'], 'error');
                } else if (data['status'] == 'warn') {
                    show_message(data['message'], 'warn');
                } else {
                    if (config['debug'] & 1)
                        console.log(data);
                    membership.getPersonInfoSuccess(data, ageButtons);
                }
            },
            error: function (jqXHR, textStatus, errorThrown) {
                showAjaxError(jqXHR, textStatus, errorThrown);
                return false;
            },
        });
    }

    // got the person, update the modal contents
    getPersonInfoSuccess(data, ageButtons) {
        // ok, it's legal to edit this person, now populate the fields
        this.#personInfo = data['person'];

        // now fill in the fields
        this.#fnameField.value = this.#personInfo['first_name'];
        this.#mnameField.value = this.#personInfo['middle_name'];
        this.#lnameField.value = this.#personInfo['last_name'];
        this.#suffixField.value = this.#personInfo['suffix'];
        this.#legalnameField.value = this.#personInfo['legalName'];
        this.#addrField.value = this.#personInfo['address'];
        this.#addr2Field.value = this.#personInfo['addr_2'];
        this.#cityField.value = this.#personInfo['city'];
        this.#stateField.value = this.#personInfo['state'];
        this.#zipField.value = this.#personInfo['zip'];
        this.#countryField.value = this.#personInfo['country'];
        this.#uspsblock.innerHTML = '';
        this.#email1Field.value = this.#personInfo['email_addr'];
        this.#email2Field.value = this.#personInfo['email_addr'];
        this.#phoneField.value = this.#personInfo['phone'];
        this.#badgenameField.value = this.#personInfo['badge_name'];
        this.#shareField.checked = (this.#personInfo['share_reg_ok'] == null || this.#personInfo['share_reg_ok'] == 'Y');
        this.#contactField.checked = (this.#personInfo['contact_ok'] == null || this.#personInfo['contact_ok'] == 'Y');
        this.#memberships = data['memberships'];
        this.#auHeader.innerHTML = 'Adding/Updating memberships for ' + this.#personInfo.fullname;
        this.#epHeader.innerHTML = 'Verifying personal information for ' + this.#personInfo.fullname;

        if (ageButtons)
            this.buildAgeButtons();
    }

    // age functions
    buildAgeButtons() {
        // first check if there is a current age;
        for (var row in this.#memberships) {
            var mbr = this.#memberships[row];
            if (mbr.memAge != 'all') {
                this.#memberAge = mbr.memAge;
                this.#memberAgeLabel = mbr.status;
                for (row in ageList) {
                    var age = ageList[row];
                    if (age.ageType == this.#memberAge) {
                        this.#memberAgeLabel = age.label;
                        break;
                    }
                }
                break;
            }
        }

        var color = this.#memberAge != null ? 'btn-warning' : (this.#currentAge != null ? 'btn-secondary' : 'btn-primary');
        // now loop over age list and build each button
        var html = '';
        for (row in ageList) {
            var age = ageList[row];
            if (age.ageType == 'all')
                continue;
            html += '<div class="col-sm-auto"><button id="ageBtn-' + age.ageType + '" class="btn btn-sm ' +
                ((this.#currentAge == age.ageType || this.#memberAge == age.ageType) ? 'btn-primary' : color) + '" onclick="membership.ageSelect(' + "'" + age.ageType + "'" + ')">' + age.label + '</button></div>' + "\n";
        }
        this.#ageButtonsDiv.innerHTML = html;
    }

    // goto step: handle going directly to a step:
    gotoStep(step) {
        this.#ageBracketDiv.hidden = step != 1;
        this.#verifyPersonDiv.hidden = step != 2;
        this.#getNewMembershipDiv.hidden = step != 3;

    }
    // ageSelect - redo all the age buttons on selecting one of them, then move on to the next page
    ageSelect(ageType) {
        if (this.#memberAge != null && ageType != this.#memberAge) {
            this.#memberAgeError = true;
            show_message("You already have a membership of the age type " + this.#memberAgeLabel + '.<br/>' +
                (this.#memberAgeStatus == 'cart' ? "You will need to remove this incorrect membership from your cart or change the age selected to continue." :
                    "If this new age is the correct age, please contact registration at " + config['regadminemail'] + " to assist you in correcting the prior membership.<br/>" +
                    "You will not be able to purchase additional memberships for this person until this is corrected. " +
                    "Otherwise, change the age selected to continue."), "warn");
            return;
        }

        this.#currentAge = ageType;
        var color = this.#memberAge != null ? 'btn-warning' : (this.#currentAge != null ? 'btn-secondary' : 'btn-primary');
        for (var row in ageList) {
            var age = ageList[row];
            if (age.ageType == 'all')
                continue;
            var btn = document.getElementById('ageBtn-' + age.ageType);
            btn.classList.remove('btn-primary');
            btn.classList.remove('btn-secondary');
            btn.classList.remove('btn-warning');
            btn.classList.add((this.#currentAge == age.ageType || this.#memberAge == age.ageType) ? 'btn-primary' : color);
        }

        this.#currentStep = 2;
        this.#ageBracketDiv.hidden = true;
        this.#verifyPersonDiv.hidden = false;
    }

    // verifyAddress - verify with USPS if defined or go to step 3
    verifyAddress() {
        var valid = true;
        var person = URLparamsToArray($('#addUpgradeForm').serialize());
        var keys = Object.keys(person);
        for (var row in keys) {
            var key = keys[row];
            this.#personInfo[key] = person[key];
        }

        // validation
        // emails must not be blank and must match
        if (person['email1'] == '' || person['email2'] == '' || person['email1'] != person['email2']) {
            this.#email1Field.value = person['email1'];
            $('#email1').addClass('need');
            $('#email2').addClass('need');
            valid = false;
        } else if (!validateAddress(person['email1'])) {
            $('#email1').addClass('need');
            $('#email2').addClass('need');
            valid = false;
        } else {
            $('#email1').removeClass('need');
            $('#email2').removeClass('need');
        }

        // first name is required
        if (person['fname'] == '') {
            valid = false;
            $('#fname').addClass('need');
        } else {
            $('#fname').removeClass('need');
        }

        // last name is required
        if (person['lname'] == '') {
            valid = false;
            $('#lname').addClass('need');
        } else {
            $('#lname').removeClass('need');

        }

        // address 1 is required, address 2 is optional
        if (person['addr'] == '') {
            valid = false;
            $('#addr').addClass('need');
        } else {
            $('#addr').removeClass('need');
        }

        // city/state/zip required
        if (person['city'] == '') {
            valid = false;
            $('#city').addClass('need');
        } else {
            $('#city').removeClass('need');
        }

        if (person['state'] == '') {
            valid = false;
            $('#state').addClass('need');
        } else {
            if (person['country'] == 'USA') {
                if (person['state'].length != 2) {
                    valid = false;
                    $('#state').addClass('need');
                } else {
                    $('#state').removeClass('need');
                }
            } else {
                $('#state').removeClass('need');
            }
        }

        if (person['zip'] == '') {
            valid = false;
            $('#zip').addClass('need');
        } else {
            $('#zip').removeClass('need');
        }

        // don't continue to process if any are missing
        if (!valid)
            return false;

        // Check USPS for standardized address
        if (this.#uspsDiv != null && (person['country'] == 'USA')) {
            var script = "scripts/uspsCheck.php";
            $.ajax({
                url: script,
                data: this.#personInfo,
                method: 'POST',
                success: function (data, textStatus, jqXhr) {
                    membership.showValidatedAddress(data);
                    return true;
                },
                error: function (jqXHR, textStatus, errorThrown) {
                    showAjaxError(jqXHR, textStatus, errorThrown, 'epMessageDiv');
                    return false;
                },
            })
            return false;
        }

        this.gotoStep(3);
        return true;
    }

    showValidatedAddress(data) {
        var html = '';
        if (data['error']) {
            html = "<h4>USPS Returned an error validating the address</h4>" +
                "<pre>" + data['error'] + "</pre>\n";
        } else {
            this.#uspsAddress = data['address'];
            if (this.#uspsAddress['address2'] == undefined)
                this.#uspsAddress['address2'] = '';

            html = "<h4>USPS Returned: " + this.#uspsAddress['valid'] + "</h4>";
            if (data['status'] == 'error') {
                html += "<p>USPS this.#uspsAddress Validation Failed: " + data['error'] + "</p>";
            } else {
                // ok, we got a valid uspsAddress, if it doesn't match, show the block
                if (this.#personInfo['addr'] == this.#uspsAddress['address'] && this.#personInfo['addr2'] == this.#uspsAddress['address2'] &&
                    this.#personInfo['city'] == this.#uspsAddress['city'] && this.#personInfo['state'] == this.#uspsAddress['state'] &&
                    this.#personInfo['zip'] == this.#uspsAddress['zip']) {
                    membership.useMyAddress();
                    return;
                }

                html += "<pre>" + this.#uspsAddress['address'] + "\n";
                if (this.#uspsAddress['address2'])
                    html += this.#uspsAddress['address2'] + "\n";
                html += this.#uspsAddress['city'] + ', ' + this.#uspsAddress['state'] + ' ' + this.#uspsAddress['zip'] + "</pre>\n";
            }
            if (this.#uspsAddress['valid'] == 'Valid')
                html += '<button class="btn btn-sm btn-primary m-1 mb-2" onclick="membership.useUSPS();">Update using USPS Validated Address</button>'
        }
        html += '<button class="btn btn-sm btn-secondary m-1 mb-2 " onclick="membership.useMyAddress();">Update using Address as Entered</button><br/>' +
            '<button class="btn btn-sm btn-secondary m-1 mt-2" onclick="membership.redoAddress();">I fixed the address, validate it again.</button>';

        this.#uspsDiv.innerHTML = html;
        this.#uspsDiv.scrollIntoView({behavior: 'instant', block: 'center'});
    }

    // usps address post functions
    useUSPS() {
        this.#personInfo['addr'] = this.#uspsAddress['address'];
        if (this.#uspsAddress['address2'])
            this.#personInfo['addr2'] = this.#uspsAddress['address2'];
        else
            this.#personInfo['addr2'] = '';
        this.#personInfo['city'] = this.#uspsAddress['city'];
        this.#personInfo['state'] = this.#uspsAddress['state'];
        this.#personInfo['zip'] = this.#uspsAddress['zip'];

        this.#addrField.value = this.#personInfo['addr'];
        this.#addr2Field.value = this.#personInfo['addr2'];
        this.#cityField.value = this.#personInfo['city'];
        this.#stateField.value = this.#personInfo['state'];
        this.#zipField.value = this.#personInfo['zip'];
        this.#uspsDiv.innerHTML = '';
        this.gotoStep(3);
    }

    useMyAddress() {
        this.#uspsDiv.innerHTML = '';
        this.gotoStep(3);
    }

    redoAddress() {
        this.#uspsDiv.innerHTML = '';
        this.editPersonSubmit(false);
    }
}
