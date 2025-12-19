// addUpdate javascript, also requires base.js

var add = null;
var profile = null;

// initial setup
window.onload = function () {
    add = new Add();
}

class Add {
    // current person info
    #epHeader = null;
    #personInfo = [];

    // flow items
    #emailDiv = null;
    #verifyPersonDiv = null;
    #leaveBeforeChanges = false;
    #newEmail = null;
    #newEmailField = null;
    #debug = 0;
    #addNewPersonBtn = null;

    constructor() {
        if (config.debug)
            this.#debug = config.debug;

        profile = new Profile('', 'login');

        // set up div elements
        this.#addNewPersonBtn = document.getElementById("addNewPerson");
        this.#emailDiv = document.getElementById("emailDiv");
        this.#newEmailField = document.getElementById("newEmailAddr");
        this.#newEmailField.addEventListener('keyup', addNewEmailListener);
        this.#verifyPersonDiv = document.getElementById("verifyPersonDiv");

        this.#addNewPersonBtn.hidden = true;
        this.#verifyPersonDiv.hidden = true;

        this.#epHeader = document.getElementById("epHeader");
        this.getPersonInfo(config.id, config.idType);
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
            profile.setEmailFixed(newEmail);
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
        let script='scripts/checkExistance.php';
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
            profile.setEmailFixed(email);
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
                <div class="col-sm-auto"> Should we email them asking if you may manage their account?</div>
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

// membership add functions
    // getPersonInfo
    getPersonInfo(id, type) {
        if (id == null) {
            return;
        }

        var data = {
            loginId: config.id,
            loginType: config.idType,
            getId: id,
            getType: type,
            newFlag: 1,
            memberships: 'N',
            interests: 'N',
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
                    add.getPersonInfoSuccess(data);
                }
            },
            error: function (jqXHR, textStatus, errorThrown) {
                showAjaxError(jqXHR, textStatus, errorThrown);
                return false;
            },
        });
    }

    // got the person, update the modal contents
    getPersonInfoSuccess(data) {
        // ok, it's legal to edit this person, now populate the fields
        let person = data.person;
        profile.clearNext();

        // now pre-fill in the inherited fields fields
        profile.setLname(person.last_name);
        profile.setAddr(person.address);
        profile.setAddr2(person.addr_2);
        profile.setCity(person.city);
        profile.setState(person.state);
        profile.setZip(person.zip);
        profile.setCountry(person.country);
     }

    // goto profile: switch from email to profile
    gotoProfile() {
        clear_message();

        // stop listening for enter key for new email address
        this.#newEmailField.removeEventListener('keyup', addNewEmailListener);

        // switch visible sections
        this.#emailDiv.hidden = true;
        this.#verifyPersonDiv.hidden = false;
        this.#addNewPersonBtn.hidden = false;
        profile.setFocus('fname');
        window.addEventListener('beforeunload', event => {
            add.confirmExit(event);
        });
        this.#leaveBeforeChanges = true;
    }

    // addPersonSubmit - calidate and add the person;
    addPersonSubmit() {
        clear_message();
        let person = URLparamsToArray($('#addUpgradeForm').serialize());

        // validate the form
        if (!profile.validate(person, null, addPerson, redoAddress))
            return false;

        this.addPerson(profile.getFormData());
        return true;
        }

    // ok, add the person to the account
    addPerson(person){
        clear_message();
        let data = {
            //person: URLparamsToArray($('#addUpgradeForm').serialize()),
            person: person,
            newPolicies: JSON.stringify(URLparamsToArray($('#editPolicies').serialize())),
            newInterests: JSON.stringify(URLparamsToArray($('#editInterests').serialize())),
            currentPerson: config.id,
            currentPersonType: config.idType,
            source: 'add',
        }
        if (config.debug & 1)
            console.log(data);

        let script = 'scripts/createNewperson.php';
        $.ajax({
            method: 'POST',
            url: script,
            data: data,
            success: function (data, textStatus, jqXhr) {
                add.addNewpersonSuccess(data);
            },
            error: function (jqXHR, textStatus, errorThrown) {
                showAjaxError(jqXHR, textStatus, errorThrown);
                return false;
            },
        });
    }

    addNewpersonSuccess(data){
        if (data.status == 'error') {
            show_message(data.message, 'error');
        } else {
            if (config.debug & 1)
                console.log(data);
            show_message(data.message);
            if (data.newPersonId > 0) {
                this.#leaveBeforeChanges = false;
                let fullname = (profile.fname() + ' ' + profile.lname()).trim();
                let url = window.location.protocol + '//' + window.location.hostname + '/cart.php';
                if (confirm('Press OK to purchase memberships now for ' + fullname + '.  Otherwise you will be taken to the portal home page')) {
                    window.location.href=url;
                    return;
                }
                window.location = "/portal.php";
            }
        }
        return true;
    }

    // cancel add - cancel the add without doing an are you sure...
    cancelAdd() {
        this.#leaveBeforeChanges = false;
        window.location="portal.php";
    }

    // if they haven't used the save/return button, ask if they want to leave
    confirmExit(event) {
        if (this.#leaveBeforeChanges) {
            event.preventDefault(); // if the browser lets us set our own variable
            if (!confirm("You are leaving without saving any changes you have made.\n" +
                "If you wish to save your new person, use the 'Add New Person to Your Account button.\n" +
                "Do you wish to leave anyway discarding any potential changes?")) {
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

function addPerson(data) {
    add.addPerson(data);
}

function redoAddress() {
    add.addPersonSubmit();
}
