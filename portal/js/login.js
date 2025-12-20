// Main login javascript, also requires base.js

var login = null;
var profile = null;

// initial setup
window.onload = function () {
    login = new Login();
    if (config.refresh == 'passkey')
        login.loginWithPasskey();
}

class Login {
    // login fields
    #matchTable = null;
    #loginWithPasskeyBtn = null;

    // edit person items
    #editPersonModal = null;
    #editPersonTitle = null;
    #editPersonSubmitBtn = null;
    #epHeaderDiv = null;
    #epPersonIdField = null;
    #epPersonTypeField = null;
    #sendLinkBtn = null;
    #tokenEmailDiv = null
    #tokenEmail = null;
    #devEmail = null;
    #newPolicies = null;

    #email = null;
    #validationType = null;
    #personSave = null;
    #uspsAddress = null;

    constructor() {
        this.#matchTable = null;
        var id;
        id = document.getElementById("editPersonModal");
        if (id) {
            this.#editPersonModal = new bootstrap.Modal(id, {focus: true, backdrop: 'static'});
            this.#editPersonTitle = document.getElementById('editPersonTitle');
            this.#editPersonSubmitBtn = document.getElementById('editPersonSubmitBtn');
            this.#epHeaderDiv = document.getElementById("epHeader");
            this.#epPersonIdField = document.getElementById("epPersonId");
            this.#epPersonTypeField = document.getElementById("epPersonType");
            profile = new Profile('', 'login');
        }

        this.#loginWithPasskeyBtn = document.getElementById("loginPasskeyBtn");
        this.#sendLinkBtn = document.getElementById("sendLinkBtn");
        this.#devEmail = document.getElementById("dev_email");
        this.#tokenEmail = document.getElementById("token_email");
        this.#tokenEmailDiv = document.getElementById('token_email_div');

        if (this.#tokenEmail) {
            this.#tokenEmail.addEventListener('keyup', (e)=> {
                    if (e.code === 'Enter') {
                        login.tokenEmailChanged(2);
                    } else {
                        login.tokenEmailChanged(1);
                    }
                });
            this.#tokenEmail.addEventListener('mouseout', (e)=> {  login.tokenEmailChanged(0); });
        }
        if (this.#devEmail) {
            this.#devEmail.addEventListener('keyup', (e)=> { if (e.code === 'Enter') login.loginWithEmail(); });
        }
    }

// login functions
// loginWithEmail: dev only
    loginWithEmail(id = null) {
        if (!this.#devEmail) {
            return;
        }
        var dev_email = this.#devEmail.value;
        if (dev_email == null || dev_email == "") {
            show_message('Please enter a valid email address', 'warn');
            return
        }
        var data = {
            'email': dev_email,
            'type': 'dev',
            'id': id,
        }
        var script = 'scripts/processLoginRequest.php';
        var _this = this;
        $.ajax({
            method: 'POST',
            url: script,
            data: data,
            success: function (data, textStatus, jqXhr) {
                if (data.status == 'error') {
                    show_message(data.message, 'error');
                    return;
                } else {
                    if (config.debug & 1)
                        console.log(data);
                    if (data.count == 1) {
                        location.href = config.uri;
                        return;
                    }
                    show_message("returned " + data.count + " matching records.");
                    if (_this.#matchTable != null) {
                        _this.#matchTable.destroy();
                        _this.#matchTable = null;
                    }
                    _this.#matchTable = new Tabulator('#matchList', {
                        maxHeight: "600px",
                        data: data.matches,
                        layout: "fitColumns",
                        responsiveLayout: true,
                        pagination: data.matches.length > 25,
                        paginationSize: 10,
                        paginationSizeSelector: [10, 25, 50, 100, true], // enable page size select with these options
                        columns: [
                            // phone, badge_name, badgeNameL2, legalName, pronouns (not shown), address, addr_2, city, state, zip, country, creation_date,
                            // update_date,
                            // active,
                            // banned,
                            {title: 'T', field: 'tablename', headerWordWrap: true, headerFilter: true, width: 50,},
                            {title: 'ID', field: 'id', hozAlign: "right", width: 65, headerWordWrap: true, headerFilter: false,},
                            {title: 'Name', field: 'fullName', headerWordWrap: true, headerFilter: true, tooltip: true},
                            {title: 'Phone', field: 'phone', headerWordWrap: true, headerFilter: true, tooltip: true},
                            {title: 'Address', field: 'address', headerWordWrap: true, headerFilter: true, tooltip: true},
                            {title: 'City', field: 'city', headerWordWrap: true, headerFilter: true, tooltip: true,},
                            {title: 'State/Prov', field: 'state', headerWordWrap: true, headerFilter: true, tooltip: true,},
                            {title: 'Zip/Postal Code', field: 'zip', headerWordWrap: true, headerFilter: true, tooltip: true,},
                            {title: 'Created', field: 'creation_date', headerWordWrap: true, headerFilter: false, tooltip: true, headerSort: true,},
                            {title: 'Act', field: 'active', headerWordWrap: true, headerFilter: true, tooltip: false, width: 50},
                            {title: 'Ban', field: 'banned', headerWordWrap: true, headerFilter: true, tooltip: false, width: 50},
                            {title: 'Actions', width: 100, hozAlign: "center", headerFilter: false, headerSort: false, formatter: _this.loginSelectIcon,},
                        ],
                    });
                }
            },
            error: function (jqXHR, textStatus, errorThrown) {
                showAjaxError(jqXHR, textStatus, errorThrown);
                return false;
            },
        });
    }

    // loginSelectIcon: deal with matches in dev list
    loginSelectIcon(cell, formatterParams, onRendered) {
        var id = cell.getRow().getData().id;
        return "<button type='button' class='btn btn-sm btn-primary pt-0 pb-0' onclick='login.loginWithEmail(" + id + ");'>Login</button>";
    }

    // login with passkey - ask for a confirm and return either retry or go to portal
    loginWithPasskey() {
        if (this.#loginWithPasskeyBtn)
            this.#loginWithPasskeyBtn.disabled = true;

       passkeyRequest('scripts/passkeyActions.php', 'portal.php', 'portal', this.#loginWithPasskeyBtn);
    }

    // loginWithToken: show email for token
    loginWithToken() {
        if (!this.#tokenEmailDiv) {
            return;
        }
        this.#tokenEmailDiv.hidden = false;
        this.#tokenEmail.focus();
    }

    // loginWithToken: show email for token
    loginWithGoogle() {
        window.location = '?oauth2=google';
    }

    tokenEmailChanged(autoCall) {
        clear_message();
        if (!this.#tokenEmail) {
            this.#sendLinkBtn.disabled = true;
            return;
        }
        var email = this.#tokenEmail.value;
        if (email == null || email == "") {
            this.#sendLinkBtn.disabled = true;
            return;
        }

        var valid = validateAddress(email);
        this.#sendLinkBtn.disabled = !valid;
        if (autoCall == 1)
            return;

        if (!valid) {
            show_message("Please enter a valid email address", 'warn');
            return;
        }
        if (autoCall == 2)
            this.sendLink();
    }

    // sendLink: send the login linkl
    sendLink() {
        var token_email = this.#tokenEmail.value;
        if (!validateAddress(token_email)) {
            show_message('Please enter a valid email address', 'warn');
            return
        }
        this.#sendLinkBtn.disabled = true;
        var data = {
            'email': token_email,
            'type': 'token',
        }
        var script = 'scripts/processLoginRequest.php';
        $.ajax({
            method: 'POST',
            url: script,
            data: data,
            success: function (data, textStatus, jqXhr) {
                if (data.status == 'error') {
                    show_message(data.message, 'error');
                } else {
                    if (config.debug & 1)
                        console.log(data);
                    show_message("Link sent to " + token_email + ", check your email and click on the link to login.");
                    login.clearTokenEmail();
                }
            },
            error: function (jqXHR, textStatus, errorThrown) {
                showAjaxError(jqXHR, textStatus, errorThrown);
                return false;
            },
        });
    }

    // clear token email for success of send link
    clearTokenEmail() {
        this.#tokenEmail.value = '';
    }

    // create account, use the edit person modal to enter a new account
    createAccount(email, validationType) {
        this.#editPersonTitle.innerHTML = "Create New Portal Account";
        this.#editPersonSubmitBtn.setAttribute("onclick", 'login.editPersonSubmit()');
        //this.#countryField.setAttribute("onchange", 'login.countryChange()');
        this.#epHeaderDiv.innerHTML = "Personal Information for " + email;
        this.#email = profile.setEmailFixed(email);
        this.#validationType = validationType;
        // now clear the input fields
        profile.clearNext();
        profile.hideAgeText();
        profile.hideAgeDiv();

        if (profile.hasUSPSDiv) {
            this.#editPersonSubmitBtn.innerHTML = 'Validate Address and Create Portal Account for ' + email;
        } else {
            this.#editPersonSubmitBtn.innerHTML = "Create Portal Account for " + email;
        }

        this.#editPersonModal.show();
        profile.setFocus('fname');
    }

    // copied from portal.js (consider how to make one copy)
    // countryChange - if USPS and USA, then change button
    countryChange() {
        if (!profile.hasUSPSDiv())
            return;

        clear_message('epMessageDiv');
        if (profile.country() == 'USA') {
            this.#editPersonSubmitBtn.innerHTML = 'Validate Address and Create Portal Account for ' + this.#email;
        } else {
            this.#editPersonSubmitBtn.innerHTML = "Create Portal Account for " + this.#email;
        }
    }

    // now submit the updates to the person
    editPersonSubmit() {
        clear_message('epMessageDiv');
        let person = URLparamsToArray($('#editPerson').serialize());

        // validate the form
        if (!profile.validate(person, 'epMessageDiv', addPerson, redoAddress, ''))
            return false;

        this.addPerson(profile.getFormData());
        return true;
    }

    // add the account
    addPerson(person) {
        let data = {
            person: person,
            newPolicies: JSON.stringify(URLparamsToArray($('#editPolicies').serialize())),
            newInterests: JSON.stringify(URLparamsToArray($('#editInterests').serialize())),
            currentPerson: -12345,
            currentPersonType: 'n',
            source: 'login',
            validation: this.#validationType,
            valEmail: this.#email,
        }
        if (config.debug & 1)
            console.log(data);

        var script = 'scripts/createNewperson.php';
        $.ajax({
            method: 'POST',
            url: script,
            data: data,
            success: function (data, textStatus, jqXhr) {
                login.createNewpersonSuccess(data);

            },
            error: function (jqXHR, textStatus, errorThrown) {
                showAjaxError(jqXHR, textStatus, errorThrown, 'epMessageDiv');
                return false;
            },
        });
    }

   createNewpersonSuccess(data){
        if (data.status == 'error') {
            show_message(data.message, 'error', 'epMessageDiv');
        } else {
            if (config.debug & 1)
                console.log(data);
            show_message(data.message);
            this.#editPersonModal.hide();
            if (data.newPersonId > 0) {
                let fullname = (profile.fname() + ' ' + profile.lname()).trim();
                let url = window.location.protocol + '//' + window.location.hostname + '/cart.php';
                if (confirm('Press OK to purchase memberships now for ' + fullname + '.  Otherwise you will be taken to the portal home page')) {
                    window.location.href=url;
                    return;
                }
                window.location.reload();
            }
        }
    }

    newpersonClose() {
        show_message("The new account was not created.<br/>" +
            'Your data is still there if you click the "Create New Account" button again.',
            'warn')
        this.#editPersonModal.hide();
    }
}

function addPerson(data) {
    login.addPerson(data);
}

function redoAddress() {
    login.editPersonSubmit();
}
