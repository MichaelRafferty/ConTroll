// addUpdate javascript, also requires base.js

var add = null;

// initial setup
window.onload = function () {
    add = new Add();
}

class Add {
    // current person info
    #epHeader = null;
    #personInfo = [];
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
    #badgeNameL2Field = null;
    #ageField = null;

    #uspsDiv= null;
    #lastVerified = null;

    // this person info
    #addUpdateId = null;
    #addUpdateType = null;
    #uspsAddress = null;

    // age items
    #ageDiv = null;
    #ageText = null;

    // flow items
    #auHeader = null
    #emailDiv = null;
    #verifyPersonDiv = null;
    #getNewMembershipDiv = null;
    #currentStep = 1;
    #leaveBeforeChanges = true;
    #newEmail = null;
    #newEmailField = null;
    #debug = 0;

    // Interests items
    #interestDiv = null;
    #oldInterests = null;
    #newInterests = null;

    // policy Items
    #oldPolicies = null;
    #newPolicies = null;

    constructor() {
        if (config.debug)
            this.#debug = config.debug;

        this.#auHeader = document.getElementById("auHeader");
        // set up div elements
        this.#emailDiv = document.getElementById("emailDiv");
        this.#newEmailField = document.getElementById("newEmailAddr");
        this.#newEmailField.addEventListener('keyup', addNewEmailListener);
        this.#verifyPersonDiv = document.getElementById("verifyPersonDiv");
        this.#interestDiv = document.getElementById("verifyInterestDiv");

        this.#verifyPersonDiv.hidden = true;

        this.#epHeader = document.getElementById("epHeader");
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
        this.#badgenameField = document.getElementById("badge_name");
        this.#badgeNameL2Field = document.getElementById("badgeNameL2");
        this.#ageField = document.getElementById("age");
        this.#ageText = document.getElementById("agetext");
        this.#ageDiv = document.getElementById("agediv");
        this.#uspsDiv = document.getElementById("uspsblock");

        if (config.action != 'new') {
            this.#addUpdateType = config.upgradeType;
            this.#addUpdateId = config.upgradeId;
            this.getPersonInfo(this.#addUpdateId, this.#addUpdateType, true, false, 1);
        } else {
            this.getPersonInfo(config.id, config.idType, true, true, 0);
        }
    }

// add new person functions
// check new email: check if this email exists
    checkNewEmail(skipMe) {
        var newEmail = this.#newEmailField.value;
        if (!validateAddress(newEmail)) {
            $('#newEmailAddr').addClass('need');
            show_message("Please enter a valid email address", 'error');
            return false;
        }

        if (skipMe == 0) {
            var lcEmail = newEmail.toLowerCase();
            if (lcEmail == config.personEmail.toLowerCase()) {
                document.getElementById('verifyMe').hidden = false;
                show_message("Please verify you want to use the same email address as your own", 'warn');
                return false;
            }
            if (emailsManaged && emailsManaged.length > 0) {
                var keys = Object.keys(emailsManaged);
                for (var index in keys) {
                    var emailAddr = keys[index];
                    if (emailAddr == lcEmail) {
                        document.getElementById('verifyMe').hidden = false;
                        show_message("Please verify you want to use the same email address as that for " + emailsManaged[emailAddr], 'warn');
                        return false;
                    }
                }
            }
        } else {
            document.getElementById('verifyMe').hidden = true;
        }
        clear_message();
        $('#newEmailAddr').removeClass('need');
        if (newEmail.toLowerCase() == config.personEmail.toLowerCase()) {
            this.#email1Field.innerHTML = newEmail;
            this.#newEmail = newEmail;
            this.gotoProfile();
            return;
        }
        var data = {
            loginId: config.id,
            loginType: config.idType,
            email: newEmail,
            action: 'exist',
        }
        var script='scripts/checkExistance.php';
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
                    add.checkNewEmailSuccess(data);
                }
            },
            error: function (jqXHR, textStatus, errorThrown) {
                showAjaxError(jqXHR, textStatus, errorThrown);
                return false;
            },
        });
    }

    // continue with the results of the email
    checkNewEmailSuccess(data) {
        var email = data.email;
        var managedByMe = data.managedByMe;
        var managedByOther = data.managedByOther;
        var countFound = data.countFound;
        var accountType = data.accountType;
        var accountId = data.accountId;

        if (countFound == 0 || (countFound == managedByMe)) {
            this.#email1Field.innerHTML = email;
            this.#newEmail = email;
            this.gotoProfile();
            return;
        } else if (managedByOther > 0) {
            show_message("This account is already managed by someone else.<br/>" +
                "If you feel you this is in error, please email registration at " + config.regadminemail + " for assistance.<br/>" +
                "Click the Home menu button to return to the portal.", 'error');
        } else if (countFound > (managedByMe + 1)) {
            show_message("More than one account has this email address, you cannot add this account.<br/>" +
                "If you feel you should be able to add these accounts, please email registration at " + config.regadminemail + " for assistance.<br/>" +
                "Click the Home menu button to return to the portal.", 'error');
            return;
        } else {
            var html = `
            <div class='row'>
                <div class'col-sm-12'>
                    <h2 class='size-h3 text-primary'>Ask to manage ` + email + `</h2>
                </div>
            </div>
            <div class="row">
                <div class="col-sm-12">
                    <p>This email address already has an account. For you to manage their account we need their permission.</p>
                    <p>We can email them a link for them to click on to allow you to manage their account.
                    When they click on the link we will add their account to yours. The link is valid for 24 hours.</p>
                    <p>You can check back later to see if they have clicked on the link.</p>
                </div>
            </div>
            <div class="row">
                <div class="col-sm-auto"> Should we send them an email asking if you may manage their account?</div>
                <div class="col-sm-auto">
                    <button class="btn btn-primary btn-sm" id="sendManageRequestBTN"
                        onclick="add.sendManageEmail('` + email + "'," + accountId + `);">Yes</button>
                </div>
                <div class="col-sm-auto">
                    <button class="btn btn-primary btn-sm"
                        onclick="window.location='portal.php?messageFwdmessageFwd=` + encodeURI("Add New Cancelled") + `'">No, return to the portal</button>
                </div>
            </div>`;
            this.#emailDiv.innerHTML = html;
        }
    }

    // sendManageEmail - send the email to 'associate for management' an account to this account
    sendManageEmail(email, acctId) {
        document.getElementById('sendManageRequestBTN').disabled = true;
        var script = 'scripts/requestAssociate.php';
        var data = {
            loginId: config.id,
            loginType: config.idType,
            acctId: acctId,
            email: email,
            action: 'request',
        }

        $.ajax({
            method: 'POST',
            url: script,
            data: data,
            success: function (data, textStatus, jqXhr) {
                checkResolveUpdates(data);
                if (data.status == 'error') {
                    show_message(data.message, 'error');
                    document.getElementById('sendManageRequestBTN').disabled = false;
                } else if (data.status == 'warn') {
                    show_message(data.message, 'warn');
                    document.getElementById('sendManageRequestBTN').disabled = false;
                } else {
                    window.location = 'portal.php?messageFwd=' + encodeURI(data.message);
                }
            },
            error: function (jqXHR, textStatus, errorThrown) {
                showAjaxError(jqXHR, textStatus, errorThrown);
                document.getElementById('attachBtn').disabled = false;
                return false;
            },
        });
    }

// membership add/update functions
    // getPersonInfo
    getPersonInfo(id, type, ageButtons, newFlag, nextStep) {
        if (id == null) {
            return;
        }

        var data = {
            loginId: config.id,
            loginType: config.idType,
            getId: id,
            getType: type,
            newFlag: newFlag ? 1 : 0,
            memberships: newFlag ? 'A' : 'B',
            ageButtons: ageButtons,
            interests: 'Y',
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
                    add.getPersonInfoSuccess(data, ageButtons, newFlag, nextStep);
                }
            },
            error: function (jqXHR, textStatus, errorThrown) {
                showAjaxError(jqXHR, textStatus, errorThrown);
                return false;
            },
        });
    }

    // got the person, update the modal contents
    getPersonInfoSuccess(data, ageButtons, newFlag, nextStep) {
        // ok, it's legal to edit this person, now populate the fields
        this.#personInfo = data.person;

        if (data.interests) {
            this.#oldInterests = data.interests;
        }
        if (data.policies) {
            this.#oldPolicies = data.policies;
    }

        // now fill in the fields
        if (newFlag) {
            this.#fnameField.value = '';
            this.#mnameField.value = '';
            this.#suffixField.value = '';
            this.#legalNameField.value = '';
            this.#pronounsField.value = '';
            this.#email1Field.innerHTML = '';
            this.#phoneField.value = '';
            this.#badgenameField.value = '';
            this.#personInfo.personType = 'n';
            this.#personInfo.id = '-1';
            this.#lastVerified = 0;
        } else {
            // person fields
            var email_addr = this.#personInfo.email_addr;
            if (this.#newEmail != null)
                email_addr = this.#newEmail;
            this.#fnameField.value = this.#personInfo.first_name;
            this.#mnameField.value = this.#personInfo.middle_name;
            this.#suffixField.value = this.#personInfo.suffix;
            this.#legalNameField.value = this.#personInfo.legalName;
            this.#pronounsField.value = this.#personInfo.pronouns;
            this.#email1Field.innerHTML = email_addr;
            this.#phoneField.value = this.#personInfo.phone;
            this.#badgenameField.value = this.#personInfo.badge_name;
            this.#badgeNameL2Field.value = this.#personInfo.badgeNameL2;
            this.#auHeader.innerHTML = 'Purchase/Upgrade memberships or other items for ' + this.#personInfo.fullName;
            this.#epHeader.innerHTML = 'Verifying personal information for ' + this.#personInfo.fullName + ' (' + email_addr + ')';
            if (this.#personInfo.lastVerified != null) {
                var lvd = new Date(this.#personInfo.lastVerified);
                this.#lastVerified = lvd.getTime();
            } else {
                this.#lastVerified = 0;
            }

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

            // interests
            if (this.#oldInterests) {
                for (var row in this.#oldInterests) {
                    var interests = this.#oldInterests[row];
                    var id = document.getElementById('i_' + interests.interest);
                    if (id) {
                        if (interests.interested) {
                            id.checked = interests.interested == 'Y';
                        } else {
                            id.checked = false;
                        }
                    }
                }
            }
        }
        this.#newInterests =  URLparamsToArray($('#editInterests').serialize());
        this.#lnameField.value = this.#personInfo.last_name;
        this.#addrField.value = this.#personInfo.address;
        this.#addr2Field.value = this.#personInfo.addr_2;
        this.#cityField.value = this.#personInfo.city;
        this.#stateField.value = this.#personInfo.state;
        this.#zipField.value = this.#personInfo.zip;
        this.#countryField.value = this.#personInfo.country;
        if (this.#uspsDiv != null) {
            this.#uspsDiv.innerHTML = '';
            this.#uspsDiv.classList.remove('border', 'border-4', 'border-dark', 'rounded');
        }
    }

    // goto profile: switch from email to profile
    gotoProfile() {
        var nowD = new Date();
        var now = nowD.getTime();
        var dif = (now - this.#lastVerified);

        clear_message();

        // stop listening for enter key for new email address
        this.#newEmailField.removeEventListener('keyup', addNewEmailListener);

        // switch visible sections
        this.#emailDiv.hidden = true;
        this.#verifyPersonDiv.hidden = false;
        clear_message();
        let focusField = this.#fnameField;
        setTimeout(() => { focusField.focus({focusVisible: true}); }, 600);
    }

    // verifyAddress - verify with USPS if defined or go to step 3
    // validateUSPS = 0 for do USPS validation, 1 = validate form, but not USPS, 2 = skip all validation
    verifyAddress(validateUSPS = 0) {
        clear_message();
        var valid = true;
        var required = config.required;
        var message = "Please correct the items highlighted in red and validate again.<br/>";
        var person = URLparamsToArray($('#addUpgradeForm').serialize());
        var keys = Object.keys(person);
        for (var i = 0; i < keys.length; i++) {
            person[keys[i]] = person[keys[i]].trim();
        }
        this.#personInfo = person;
        if (config.upgradeType) {
            this.#personInfo.personType = config.upgradeType;
            this.#personInfo.id = config.upgradeId;
        }

        // validation
        if (person.country == 'USA') {
            message += "<br/>Note: If any of the address fields Address, City, State/Prov or Zip/PC are used and the country is United States, " +
                "then the Address, City, State, and Zip fields must all be entered and the state field must be a valid USPS two character state code.";
        }

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
                    if (person.state.trim().length != 2) {
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
            console.log("New Policies:");
            console.log(this.#newPolicies);
            for (var row in policies) {
                var policy = policies[row];
                if (policy.required == 'Y') {
                    var field = '#l_' + policy.policy;
                    if (typeof this.#newPolicies['p_' + policy.policy] === 'undefined') {
                        console.log("required policy " + policy.policy + ' is not checked');
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
            show_message(message, "error");
            return false;
        }

        // Check USPS for standardized address
        if (this.#uspsDiv != null && person.country == 'USA' && person.city != '' && person.state != '/r' && validateUSPS == 0) {
            var script = "scripts/uspsCheck.php";
            $.ajax({
                url: script,
                data: person,
                method: 'POST',
                success: function (data, textStatus, jqXhr) {
                    if (data.status == 'error') {
                        show_message(data.message, 'error');
                        return false;
                    }
                    add.showValidatedAddress(data);
                    return true;
                },
                error: function (jqXHR, textStatus, errorThrown) {
                    showAjaxError(jqXHR, textStatus, errorThrown, 'epMessageDiv');
                    return false;
                },
            })
            return false;
        }

        return true;
    }

    showValidatedAddress(data) {
        var html = '';
        clear_message();
        if (data.error) {
            var errormsg = data.error;
            if (errormsg.substring(0, 5) == '400: ') {
                errormsg = errormsg.substring(5);
            }
            html = "<h4>USPS Returned an error<br/>validating the address</h4>" +
                "<div class='bg-danger text-white'><pre>" + errormsg + "</pre></div>\n";
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
            if ((this.#personInfo.addr == this.#uspsAddress.address || this.#personInfo.address == this.#uspsAddress.address) &&
                (this.#personInfo.addr2 == this.#uspsAddress.address2 || this.#personInfo.addr_2 == this.#uspsAddress.address2) &&
                this.#personInfo.city == this.#uspsAddress.city && this.#personInfo.state == this.#uspsAddress.state &&
                this.#personInfo.zip == this.#uspsAddress.zip) {
                this.useMyAddress();
                return;
            }

            html += "<pre>" + this.#uspsAddress.address + "\n";
            if (this.#uspsAddress.address2)
                html += this.#uspsAddress.address2 + "\n";
            html += this.#uspsAddress.city + ', ' + this.#uspsAddress.state + ' ' + this.#uspsAddress.zip + "</pre>\n";

            if (this.#uspsAddress.valid == 'Valid')
                html += '<button class="btn btn-sm btn-primary m-1 mb-2" onclick="add.useUSPS();">Update using the USPS validated address</button>'
            else
                html += "<p>Please check/verify the address you entered on the left.</p></div>";
        }
        html += '<button class="btn btn-sm btn-secondary m-1 mb-2 " onclick="add.useMyAddress();">Update using the address as entered</button><br/>' +
            '<button class="btn btn-sm btn-secondary m-1 mt-2" onclick="add.redoAddress();">I fixed the address, validate it again</button>';

        if (this.#uspsDiv != null) {
            this.#uspsDiv.innerHTML = html;
            this.#uspsDiv.classList.add('border', 'border-4', 'border-dark', 'rounded');
            this.#uspsDiv.scrollIntoView({behavior: 'instant', block: 'center'});
        }
    }

    // usps address post functions
    useUSPS() {
        this.#personInfo.addr = this.#uspsAddress.address;
        if (this.#uspsAddress.address2)
            this.#personInfo.addr2 = this.#uspsAddress.address2;
        else
            this.#personInfo.addr2 = '';
        this.#personInfo.city = this.#uspsAddress.city;
        this.#personInfo.state = this.#uspsAddress.state;
        this.#personInfo.zip = this.#uspsAddress.zip;

        this.#addrField.value = this.#personInfo.addr;
        this.#addr2Field.value = this.#personInfo.addr2;
        this.#cityField.value = this.#personInfo.city;
        this.#stateField.value = this.#personInfo.state;
        this.#zipField.value = this.#personInfo.zip;
        if (this.#uspsDiv != null) {
            this.#uspsDiv.innerHTML = '';
            this.#uspsDiv.classList.remove('border', 'border-4', 'border-dark', 'rounded');
        }
        this.verifyAddress(1);
    }

    useMyAddress() {
        if (this.#uspsDiv != null) {
            this.#uspsDiv.innerHTML = '';
            this.#uspsDiv.classList.remove('border', 'border-4', 'border-dark', 'rounded');
        }
        this.verifyAddress(1);
    }

    redoAddress() {
        if (this.#uspsDiv != null) {
            this.#uspsDiv.innerHTML = '';
            this.#uspsDiv.classList.remove('border', 'border-4', 'border-dark', 'rounded');
        }
        this.verifyAddress(0);
    }

    // if they haven't used the save/return button, ask if they want to leave
    confirmExit(event) {
        if (this.#leaveBeforeChanges) {
            var buttonName = 'missing'
            event.preventDefault(); // if the browser lets us set our own variable
            if (!confirm("You are leaving without saving any changes you have made.\nPlease go through all four steps and use the " +
                buttonName + " button.\nDo you wish to leave anyway discarding any potential changes?")) {
                return false;
            }
        }

        return true;
    }
}

function addNewEmailListener(event) {
    if (event.code === 'Enter')
        add.checkNewEmail(0);
}
