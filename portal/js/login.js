// Main login javascript, also requires base.js

var login = null;

// initial setup
window.onload = function () {
    login = new Login();
}

class Login {
    // login fields
    #matchTable = null;

    // edit person items
    #editPersonModal = null;
    #editPersonTitle = null;
    #editPersonSubmitBtn = null;
    #epHeaderDiv = null;
    #epPersonIdField = null;
    #epPersonTypeField = null;
    #fnameField = null;
    #mnameField = null;
    #lnameField = null;
    #suffixField = null;
    #legalnameField = null;
    #pronounsField = null;
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
            this.#fnameField = document.getElementById("fname");
            this.#mnameField = document.getElementById("mname");
            this.#lnameField = document.getElementById("lname");
            this.#suffixField = document.getElementById("suffix");
            this.#legalnameField = document.getElementById("legalname");
            this.#pronounsField = document.getElementById("pronouns");
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

            $('#email1').attr('readonly', true);
            $('#email2').attr('readonly', true);
        }

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
                if (data['status'] == 'error') {
                    show_message(data['message'], 'error');
                    return;
                } else {
                    if (config['debug'] & 1)
                        console.log(data);
                    if (data['count'] == 1) {
                        location.href = config.uri;
                        return;
                    }
                    show_message("returned " + data['count'] + " matching records.");
                    if (_this.#matchTable != null) {
                        _this.#matchTable.destroy();
                        _this.#matchTable = null;
                    }
                    _this.#matchTable = new Tabulator('#matchList', {
                        maxHeight: "600px",
                        data: data['matches'],
                        layout: "fitColumns",
                        responsiveLayout: true,
                        pagination: true,
                        paginationSize: 10,
                        paginationSizeSelector: [10, 25, 50, 100, true], // enable page size select with these options
                        columns: [
                            // phone, badge_name, legalName, pronouns (not shown), address, addr_2, city, state, zip, country, creation_date, update_date,
                            // active,
                            // banned,
                            {title: 'T', field: 'tablename', headerWordWrap: true, headerFilter: true, width: 50,},
                            {title: 'ID', field: 'id', hozAlign: "right", width: 65, headerWordWrap: true, headerFilter: false,},
                            {title: 'Name', field: 'fullname', headerWordWrap: true, headerFilter: true, tooltip: true},
                            {title: 'Phone', field: 'phone', headerWordWrap: true, headerFilter: true, tooltip: true},
                            {title: 'Address', field: 'address', headerWordWrap: true, headerFilter: true, tooltip: true},
                            {title: 'City', field: 'city', headerWordWrap: true, headerFilter: true, tooltip: true,},
                            {title: 'State', field: 'state', headerWordWrap: true, headerFilter: true, tooltip: true,},
                            {title: 'Zip', field: 'zip', headerWordWrap: true, headerFilter: true, tooltip: true,},
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
                if (data['status'] == 'error') {
                    show_message(data['message'], 'error');
                } else {
                    if (config['debug'] & 1)
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
        this.#countryField.setAttribute("onchange", 'login.countryChange()');
        this.#epHeaderDiv.innerHTML = "Personal Information for " + email;
        this.#email1Field.value = email;
        this.#email2Field.value = email;
        this.#email = email;
        this.#validationType = validationType;

        if (this.#uspsDiv) {
            this.#editPersonSubmitBtn.innerHTML = 'Validate Address and Create Portal Account for ' + email;
        } else {
            this.#editPersonSubmitBtn.innerHTML = "Create Portal Account for " + email;
        }

        this.#editPersonModal.show();

    }

    // copied from portal.js (consider how to make one copy)
    // countryChange - if USPS and USA, then change button
    countryChange() {
        if (this.#uspsDiv == null)
            return;

        clear_message();
        var country = this.#countryField.value;
        if (this.#uspsDiv && country == 'USA') {
            this.#editPersonSubmitBtn.innerHTML = 'Validate Address and Create Portal Account for ' + this.#email;
        } else {
            this.#editPersonSubmitBtn.innerHTML = "Create Portal Account for " + this.#email;
        }
    }

// validate the edit person form for saving
    validate(person, validateUSPS = 0) {
        //process(formRef) {
        clear_message();
        clear_message('epMessageDiv');
        var valid = true;
        var required = config['required'];
        var message = "Please correct the items highlighted in red and validate again.<br/>" +
            "Note: If any of the address fields are used and the country is United States, " +
            "then the Address, City, State, and Zip fields must all be entered.";

        // validation
        // emails must not be blank and must match
        if (person['email1'] == '' || person['email2'] == '' || person['email1'] != person['email2']) {
            this.#email1Field.value = person['email1'];
            $('#email1').addClass('need');
            $('#email2').addClass('need');
            $('#email1').attr('readonly', false);
            $('#email2').attr('readonly', false);
            valid = false;
        } else if (!validateAddress(person['email1'])) {
            $('#email1').addClass('need');
            $('#email2').addClass('need');
            $('#email1').attr('readonly', false);
            $('#email2').attr('readonly', false);
            valid = false;
        } else {
            $('#email1').removeClass('need');
            $('#email2').removeClass('need');
        }

        if (required != '') {
            // first name is required
            if (person['fname'] == '') {
                valid = false;
                $('#fname').addClass('need');
            } else {
                $('#fname').removeClass('need');
            }
        }

        if (required == 'all') {
            // last name is required
            if (person['lname'] == '') {
                valid = false;
                $('#lname').addClass('need');
            } else {
                $('#lname').removeClass('need');
            }
        }

        if (required == 'addr' || required == 'all' || person['city'] != '' || person['state'] != '' || person['zip'] != '') {
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
            show_message(message, "error", 'epMessageDiv');
            return false;
        }

        // Check USPS for standardized address
        if (this.#uspsDiv != null && person['city'] != '' && validateUSPS == 0) {
            this.#personSave = person;
            this.#uspsAddress = null;
            var script = "scripts/uspsCheck.php";
            var data = person;
            data['source'] = 'login';
            $.ajax({
                url: script,
                data: data,
                method: 'POST',
                success: function (data, textStatus, jqXhr) {
                    if (data['status'] == 'error') {
                        show_message(data['message'], 'error', 'epMessageDiv');
                        return false;
                    }
                    login.showValidatedAddress(data);
                    return true;
                },
                error: function (jqXHR, textStatus, errorThrown) {
                    showAjaxError(jqXHR, textStatus, errorThrown, 'epMessageDiv');
                    return false;
                },
            });
            return false;
        }
        this.editPersonSubmit(2);
    }

    showValidatedAddress(data) {
        clear_message();
        var html = '';
        if (data['error']) {
            var errormsg = data['error'];
            if (errormsg.substring(0, 5) == '400: ') {
                errormsg = errormsg.substring(5);
            }
            html = "<h4>USPS Returned an error<br/>validating the address</h4>" +
                "<pre>" + errormsg + "</pre>\n";
        } else {
            this.#uspsAddress = data['address'];
            if (this.#uspsAddress['address2'] == undefined)
                this.#uspsAddress['address2'] = '';

            html = "<h4>USPS Returned: " + this.#uspsAddress['valid'] + "</h4>";
                // ok, we got a valid uspsAddress, if it doesn't match, show the block
            var person = this.#personSave;
            if (person['addr'] == this.#uspsAddress['address'] && person['addr2'] == this.#uspsAddress['address2'] &&
                person['city'] == this.#uspsAddress['city'] && person['state'] == this.#uspsAddress['state'] &&
                person['zip'] == this.#uspsAddress['zip']) {
                login.useMyAddress();
                return;
            }

            html += "<pre>" + this.#uspsAddress['address'] + "\n";
            if (this.#uspsAddress['address2'])
                html += this.#uspsAddress['address2'] + "\n";
            html += this.#uspsAddress['city'] + ', ' + this.#uspsAddress['state'] + ' ' + this.#uspsAddress['zip'] + "</pre>\n";

            if (this.#uspsAddress['valid'] == 'Valid')
                html += '<button class="btn btn-sm btn-primary m-1 mb-2" onclick="login.useUSPS();">Update using USPS Validated Address</button>'
        }
        html += '<button class="btn btn-sm btn-secondary m-1 mb-2 " onclick="login.useMyAddress();">Update using Address as Entered</button><br/>' +
            '<button class="btn btn-sm btn-secondary m-1 mt-2" onclick="login.redoAddress();">I fixed the address, validate it again.</button>';

        this.#uspsDiv.innerHTML = html;
        this.#uspsDiv.scrollIntoView({behavior: 'instant', block: 'center'});
    }

    // usps address post functions
    useUSPS() {
        var person = this.#personSave;
        person['addr'] = this.#uspsAddress['address'];
        if (this.#uspsAddress['address2'])
            person['addr2'] = this.#uspsAddress['address2'];
        else
            person['addr2'] = '';
        person['city'] = this.#uspsAddress['city'];
        person['state'] = this.#uspsAddress['state'];
        person['zip'] = this.#uspsAddress['zip'];

        this.#addrField.value = person['addr'];
        this.#addr2Field.value = person['addr2'];
        this.#cityField.value = person['city'];
        this.#stateField.value = person['state'];
        this.#zipField.value = person['zip'];
        this.#uspsDiv.innerHTML = '';
        this.editPersonSubmit(1);
    }

    useMyAddress() {
        this.#uspsDiv.innerHTML = '';
        this.editPersonSubmit(1);
    }

    redoAddress() {
        this.#uspsDiv.innerHTML = '';
        this.editPersonSubmit(0);
    }

    // now submit the updates to the person
    editPersonSubmit(validateUSPS = 0) {
        clear_message();
        var person = URLparamsToArray($('#editPerson').serialize());
        if (validateUSPS != 2) {
            if (!this.validate(person, validateUSPS))
                return;
        }

        var data = {
            person: person,
            currentPerson: -12345,
            currentPersonType: 'n',
            source: 'login',
            novalidate: this.#validationType,
            valEmail: this.#email,
        }
        if (config['debug'] & 1)
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
        if (data['status'] == 'error') {
            show_message(data['message'], 'error', 'epMessageDiv');
        } else {
            if (config['debug'] & 1)
                console.log(data);
            show_message(data['message']);
            this.#editPersonModal.hide();
            if (data['newPersonId'] > 0) {
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
