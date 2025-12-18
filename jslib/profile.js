// Profile Class - all functions and data related to entry and validation of a profile

class Profile {
// fields
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
    #email2Field = null;
    #phoneField = null;
    #badgenameField = null;
    #badgenameL2Field = null;
    #ageField = null;
    #ageDiv = null;
    #ageText = null;
    #uspsDiv= null;
    #newPolicies = null;

// online reg - membership filtering
    #memIdField = null;

// USPS fields
    #formData = null;
    #formDataSave = null;
    #messageDiv = null;
    #addCallback = null;
    #redoCallback = null;
    #uspsAddress = null;

// initialization
    constructor(prefix = '') {
        
// lookup all DOM elements
        this.#fnameField = document.getElementById(prefix + "fname");
        this.#mnameField = document.getElementById(prefix + "mname");
        this.#lnameField = document.getElementById(prefix + "lname");
        this.#suffixField = document.getElementById(prefix + "suffix");
        this.#legalNameField = document.getElementById(prefix + "legalName");
        this.#pronounsField = document.getElementById(prefix + "pronouns");
        this.#addrField = document.getElementById(prefix + "addr");
        this.#addr2Field = document.getElementById(prefix + "addr2");
        this.#cityField = document.getElementById(prefix + "city");
        this.#stateField = document.getElementById(prefix + "state");
        this.#zipField = document.getElementById(prefix + "zip");
        this.#countryField = document.getElementById(prefix + "country");
        this.#email1Field = document.getElementById(prefix + "email1");
        this.#email2Field = document.getElementById(prefix + "email2");
        this.#phoneField = document.getElementById(prefix + "phone");
        this.#badgenameField = document.getElementById(prefix + "badge_name");
        this.#badgenameL2Field = document.getElementById(prefix + "badgeNameL2");
        this.#ageField = document.getElementById(prefix + "age");
        this.#ageText = document.getElementById(prefix + "agetext");
        this.#ageDiv = document.getElementById(prefix + "agediv");
        this.#uspsDiv = document.getElementById(prefix + "uspsblock");
        this.#memIdField = document.getElementById('memId');

        if (this.#memIdField) {
            this.#ageField.onchange=function() {
                profile.ageChanged();
            }
        }
    }

    // get functions
    fname() {
        return this.#fnameField.value;
    }

    mname() {
        return this.#mnameField.value;
    }

    lname() {
        return this.#lnameField.value;
    }

    suffix() {
        return this.#suffixField.value;
    }

    legalName() {
        return this.#legalNameField.value;
    }

    pronouns() {
        return this.#pronounsField.value;
    }

    addr() {
        return this.#addrField.value;
    }

    addr2() {
        return this.#addr2Field.value;
    }

    city() {
        return this.#cityField.value;
    }

    state() {
        return this.#stateField.value;
    }

    zip() {
        return this.#zipField.value;
    }

    country() {
        return this.#countryField.value;
    }

    email() {
        return this.#email1Field.value;
    }

    email2() {
        if (this.#email2Field)
            return this.#email2Field.value;
        return this.#email1Field.value;
    }

    phone() {
        return this.#phoneField.value;
    }

    badgename() {
        return this.#badgenameField.value;
    }

    badgenameL2() {
        return this.#badgenameL2Field.value;
    }

    age() {
        return this.#ageField.value;
    }

    hasUSPSDiv() {
        return this.#uspsDiv != null;
    }

    // set functions
    setall(person) {
        console.log("setall called");
    }

    setAgeText(text) {
        this.ageTextField.innerHTML = text;
    }

    hideAgeDiv(hide) {
        this.#ageDiv.hidden = hide;
    }

    hideAgeText(hide) {
        this.#ageText.hidden = hide;
    }

    hideAgeField(hide) {
        this.#ageField.hidden = hide;
    }

    validate(person, messageDiv, addCallback, redoCallback, message = '') {
        this.#messageDiv = messageDiv;
        this.#addCallback = addCallback;
        this.#redoCallback = redoCallback;
        let valid = message == '';
        let required = config.required;
        this.#uspsAddress = null;

        // trim trailing blanks
        let keys = Object.keys(person);
        for (let i = 0; i < keys.length; i++) {
            if (keys[i] != 'policyInterest')
                person[keys[i]] = person[keys[i]].trim();
        }

        this.#formData = person;
        this.#formDataSave = person;

        if (person.country == 'USA') {
            message += "<br/>Note: If any of the address fields Address, City, State/Prov or Zip/PC are used and the country is United States, " +
                "then the Address, City, State, and Zip fields must all be entered and the state field must be a valid USPS two character state code.";
        }
        // validation
        if (required != '') {
            // first name is required
            if (person.fname == '') {
                valid = false;
                this.#fnameField.classList.add('need');
            } else {
                this.#fnameField.classList.remove('need');
            }
        }

        if (this.#email1Field)
            this.#email1Field.classList.remove('need');
        if (this.#email2Field)
            this.#email2Field.classList.remove('need');
        if (this.#email1Field != null && this.email() != '/r') {
            if (this.email() != this.email2()) {
                message += "The two email addresses do not match<br/>";
                valid = false;
                this.#email1Field.classList.add('need');
                if (this.#email2Field)
                    this.#email2Field.classList.add('need');
            } else if (!validateAddress(this.email())) {
                message += "The email address is not a valid email address<br/>";
                this.#email1Field.classList.add('need');
                if (this.#email2Field)
                    this.#email2Field.classList.add('need');
            }
        }

        if (required == 'all') {
            // last name is required
            if (person.lname == '') {
                valid = false;
                this.#lnameField.classList.add('need');
            } else {
                this.#lnameField.classList.remove('need');
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
                this.#addrField.classList.add('need');
            } else {
                this.#addrField.classList.remove('need');
            }

            // city/state/zip required
            if (person.city == '') {
                valid = false;
                this.#cityField.classList.add('need');
            } else {
                this.#cityField.classList.remove('need');
            }

            if (person.state == '') {
                valid = false;
                this.#stateField.classList.add('need');
            } else {
                if (person.country == 'USA') {
                    if (person.state.trim().length != 2) {
                        valid = false;
                        this.#stateField.classList.add('need');
                    } else {
                        this.#stateField.classList.remove('need');
                    }
                } else {
                    this.#stateField.classList.remove('need');
                }
            }

            if (person.zip == '') {
                valid = false;
                this.#zipField.classList.add('need');
            } else {
                this.#zipField.classList.remove('need');
            }
        }

        // age is always required
        if (person.age === undefined || person.age == '') {
            valid = false;
            this.#ageField.classList.add('need');
        } else {
            this.#ageField.classList.remove('need');
        }

        // now verify required policies
        if (policies) {
            this.#newPolicies = URLparamsToArray($('#editPolicies').serialize());
            for (let row in policies) {
                let policy = policies[row];
                if (policy.required == 'Y') {
                    let field = '#l_' + policy.policy;
                    if (typeof this.#newPolicies['p_' + policy.policy] === 'undefined') {
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
            show_message("Please correct the items highlighted in red and validate again.<br/>" + message, 'error', messageDiv);
            return false;
        }

        // Check USPS for standardized address
        if (this.#uspsDiv != null && this.#countryField.value == 'USA' && this.#cityField.value != '' && this.#cityField.value != '/r' &&
            this.#stateField.value != '/r') {
            let _this = this;
            $.ajax({
                url: "scripts/uspsCheck.php",
                data: this.#formData,
                method: 'POST',
                success: function (data, textstatus, jqxhr) {
                    if (data.status == 'error') {
                        show_message(data.message, 'error', messageDiv);
                        return false;
                    }
                    profile.showValidatedAddress(data);
                },
                error: function (jqXHR, textstatus, errorThrown) {
                    show_message("ERROR! " + textStatus + ' ' + errorThrown + '<br/>Seek Assistance.', 'error', messageDiv);
                },
            });
            return false;
        }
        return true;
    }

    // usps functions
    showValidatedAddress(data) {
        let html = '';
        clear_message(this.#messageDiv);
        if (data.error) {
            let errormsg = data.error;
            if (errormsg.substring(0, 5) == '400: ') {
                errormsg = errormsg.substring(5);
            }
            html = "<h4>USPS Returned an error<br/>validating the address</h4>" +
                "<pre>" + errormsg + "</pre>\n";
        } else {
            this.#uspsAddress = data.address;
            html = "<h4>USPS Returned: " + this.#uspsAddress.valid + "</h4>";
            if (data.status == 'error') {
                html += "<p>USPS uspsAddress Validation Failed: " + data.error + "</p>";
            } else {
                // ok, we got a valid uspsAddress, show the block
                html += "<pre>" + this.#uspsAddress.address + "\n";
                if (this.#uspsAddress.address2)
                    html += this.#uspsAddress.address2 + "\n";
                html += this.#uspsAddress.city + ', ' + this.#uspsAddress.state + ' ' + this.#uspsAddress.zip + "</pre>\n";
            }
            if (this.#uspsAddress.valid == 'Valid')
                html += '<button class="btn btn-sm btn-primary m-1 mb-2" onclick="profile.useUSPS();">Add to cart using USPS Validated Address</button>'
        }
        html += '<button class="btn btn-sm btn-secondary m-1 mb-2 " onclick="profile.useMyAddress();">Add to cart using Address as Entered</button><br/>' +
            '<button class="btn btn-sm btn-secondary m-1 mt-2" onclick="profile.redoAddress();">I fixed the address, validate it again.</button>';

        this.#uspsDiv.innerHTML = html;
        this.#uspsDiv.scrollIntoView({behavior: 'instant', block: 'center'});
    }

    useUSPS() {
        this.#formData = this.#formDataSave;
        this.#formData.addr = this.#uspsAddress.address;
        if (this.#uspsAddress.address2)
            this.#formData.addr2 = this.#uspsAddress.address2;
        else
            this.#formData.addr2 = '';
        this.#formData.city = this.#uspsAddress.city;
        this.#formData.state = this.#uspsAddress.state;
        this.#formData.zip = this.#uspsAddress.zip;

        this.#addrField.value = this.#formData.addr;
        this.#addr2Field.value = this.#formData.addr2;
        this.#cityField.value = this.#formData.city;
        this.#stateField.value = this.#formData.state;
        this.#zipField.value = this.#formData.zip;
        this.#uspsDiv.innerHTML = '';
        this.#addCallback(this.#formData);
    }

    useMyAddress() {
        this.#uspsDiv.innerHTML = '';
        this.#addCallback(this.#formDataSave);
    }

    redoAddress() {
        this.#uspsDiv.innerHTML = '';
        this.#redoCallback("newBadgeForm");
    }

    // clearnext - clear the fields for another membership to be added
    clearNext() {
        this.#fnameField.value = '';
        this.#mnameField.value = '';
        this.#suffixField.value = '';
        this.#email1Field.value = '';
        this.#email2Field.value = '';
        this.#legalNameField.value = '';
        this.#pronounsField.value = '';
        this.#badgenameField.value = '';
        this.#badgenameL2Field.value = '';
        this.#ageField.value = '';

        // reset the policies and interests
        for (let row in policies) {
            let policy = policies[row];
            let field = '#p_' + policy.policy;
            $(field).prop('checked', policy.defaultValue == 'Y');
        }
        for (let row in interests) {
            let interest = interests[row];
            let field = '#i_' + interest.interest;
            $(field).prop('checked', false);
        }
    }

    // ageChanged - filter memList for age change
    ageChanged() {
        if (this.#memIdField == null)
            return;

        let age = this.#ageField.value;
        let first = true;
        for (let i = 0; i < membershipTypes.length; i++) {
            let mtype = membershipTypes[i];
            let display = (mtype.memAge == age || mtype.memAge == 'all');
            if (first && display) {
                first = false;
                this.#memIdField.value = mtype.id;
            }
            this.#memIdField.options[i].style.display = (mtype.memAge == age || mtype.memAge == 'all') ? '' : 'none';
        }
    }
}
