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
    #memberAge = '';
    #uspsDiv= null;
    #email1Input = true;

// online reg - membership filtering
    #memIdField = null;
    #ageasofLabel = null;

// USPS fields
    #formData = null;
    #formDataSave = null;
    #messageDiv = null;
    #addCallback = null;
    #redoCallback = null;
    #uspsAddress = null;
    #source = '';
    #prefix = '';
    #alert = 'need';
    #alertName = 'red'
    #alertType = 'error'

// initialization
    constructor(prefix = '', source = '', alert= 'need') {
        this.#source = source;
        this.#prefix = prefix;
        this.#alert = alert;
        this.#alertName = alert == 'need' ? 'red' : 'yellow';
        this.#alertType = alert == 'need' ? 'error' : 'warn';

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
        this.#ageasofLabel = document.getElementById('ageasofLabel');
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
        if (this.#legalNameField)
            return this.#legalNameField.value;

        return '';
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
        if (this.#email1Input)
            return this.#email1Field.value;
        return this.#email1Field.innerHTML;
    }

    email2() {
        if (this.#email2Field)
            return this.#email2Field.value;
        return this.email();
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

    getFormData() {
        return this.#formData;
    }

    hasUSPSDiv() {
        return this.#uspsDiv != null;
    }

    // set functions - pre-populate the form
    setLname(last_name) {
        this.#lnameField.value = last_name;
    }

    setAddr(address) {
        this.#addrField.value = address;
    }
    setAddr2(addr_2) {
        this.#addr2Field.value = addr_2;
    }

    setCity(city) {
        this.#cityField.value = city;
    }
    setState(state) {
        this.#stateField.value = state;
    }
    setZip(zip) {
        this.#zipField.value = zip;
    }
    setCountry(country) {
        this.#countryField.value = country;
    }

    setEmail(email) {
        this.#email1Field.value = email;
        if (this.#email2Field)
            this.#email2Field.value = email;
    }

    setEmailFixed(email) {
        this.#email1Input = false;
        this.#email1Field.innerHTML = email;
        return email;
    }

    setAgeText(text, addon='') {
        this.#ageText.innerHTML = text + (addon != '' ? '<br/>' + addon : '');
        this.#ageText.hidden = false;
        this.#ageDiv.hidden = false;
        this.#ageField.hidden = true;
    }

    setAge(age) {
        this.#ageField.value = age;
    }

    setMemberAge(age) {
        this.#memberAge = age;
    }

    setAll(first_name, middle_name, last_name, suffix, legalName, pronouns, address, addr_2, city, state, zip, country, phone,
           badge_name, badgeNameL2, age) {
        this.#fnameField.value = first_name;
        this.#mnameField.value = middle_name;
        this.#lnameField.value = last_name;
        this.#suffixField.value = suffix;
        if (this.#legalNameField)
            this.#legalNameField.value = legalName;
        if (this.#pronounsField)
            this.#pronounsField.value = pronouns;
        this.#addrField.value = address;
        this.#addr2Field.value = addr_2;
        this.#cityField.value = city;
        this.#stateField.value = state;
        this.#zipField.value = zip;
        this.#countryField.value = country;
        this.#phoneField.value = phone;
        this.#badgenameField.value = badge_name;
        this.#badgenameL2Field.value = badgeNameL2;
        this.#ageField.value = age;
    }

    setPolicies(old) {
        // policies
        if (old) {
            for (let row in old) {
                let policy = old[row];
                let id = document.getElementById('p_' + this.#prefix + policy.policy);
                if (id) {
                    if (policy.response) {
                        id.checked = policy.response == 'Y';
                    } else {
                        id.checked = policy.defaultValue == 'Y';
                    }
                }
            }
        }
    }

    setFocus(field) {
        let focusField = this.#fnameField;
        switch (field) {
            case 'lname':
            case 'last_name':
                focusField = this.#lnameField;
                break;
        }
        setTimeout(() => { focusField.focus({focusVisible: true}); }, 600);
    }

    hideAgeDiv(hide = true) {
        this.#ageDiv.hidden = hide;
    }

    hideAgeText(hide = true) {
        this.#ageText.hidden = hide;
    }

    hideAgeField(hide = true) {
        this.#ageField.hidden = hide;
    }

    hideAgeAsOfLabel(hide = true) {
        this.#ageasofLabel.hidden = hide;
    }

    validate(person, messageDiv, addCallback, redoCallback, message = '', multiUse = false, override = false) {
        this.#messageDiv = messageDiv;
        this.#addCallback = addCallback;
        this.#redoCallback = redoCallback;
        let valid = message == '';
        let overrideAllowed = false;
        let required = config.required;
        this.#uspsAddress = null;

        if (this.#memIdField) {
            if (this.#memIdField.value != '') {
                let memId = this.#memIdField.value;
                for (let i = 0; i < membershipTypes.length; i++) {
                    let mtype = membershipTypes[i];
                    if (mtype.id == memId) {
                        this.#ageField.value = mtype.memAge;
                        break;
                    }
                }
            }
        }
        if (person == null) {
            person = {
                fname: this.fname(),
                mname: this.mname(),
                lname: this.lname(),
                suffix: this.suffix(),
                leganName: this.legalName(),
                addr: this.addr(),
                addr2: this.addr2(),
                city: this.city(),
                state: this.state(),
                zip: this.zip(),
                country: this.country(),
                email1: this.email(),
                phone: this.phone(),
                age: this.age(),
                badge_name: this.badgename(),
                badgeNameL2: this.badgenameL2(),
            }
        }

        // trim trailing blanks
        let keys = Object.keys(person);
        for (let i = 0; i < keys.length; i++) {
            if (keys[i] != 'policyInterest')
                person[keys[i]] = person[keys[i]].trim();
        }

        this.#formData = person;
        this.#formDataSave = person;

        // validation
        if (required != '') {
            // first name is required
            if (person.fname == '') {
                valid = false;
                this.#fnameField.classList.add(this.#alert);
            } else {
                this.#fnameField.classList.remove(this.#alert);
            }
        }

        if (this.#email1Field)
            this.#email1Field.classList.remove(this.#alert);
        if (this.#email2Field)
            this.#email2Field.classList.remove(this.#alert);
        if (this.#email1Field != null && this.email() != '/r') {
            if (this.email() != this.email2()) {
                message += "The two email addresses do not match<br/>";
                valid = false;
                this.#email1Field.classList.add(this.#alert);
                if (this.#email2Field)
                    this.#email2Field.classList.add(this.#alert);
            } else if (!validateAddress(this.email())) {
                message += "The email address is not a valid email address<br/>";
                this.#email1Field.classList.add(this.#alert);
                if (this.#email2Field)
                    this.#email2Field.classList.add(this.#alert);
            }
        }

        if (required == 'all') {
            // last name is required
            if (person.lname == '') {
                valid = false;
                this.#lnameField.classList.add(this.#alert);
            } else {
                this.#lnameField.classList.remove(this.#alert);
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
                this.#addrField.classList.add(this.#alert);
            } else {
                this.#addrField.classList.remove(this.#alert);
            }

            // city/state/zip required
            if (person.city == '') {
                valid = false;
                this.#cityField.classList.add(this.#alert);
            } else {
                this.#cityField.classList.remove(this.#alert);
            }

            if (person.state == '') {
                valid = false;
                this.#stateField.classList.add(this.#alert);
            } else {
                if (person.country == 'USA') {
                    if (person.state.trim().length != 2) {
                        valid = false;
                        this.#stateField.classList.add(this.#alert);
                    } else {
                        this.#stateField.classList.remove(this.#alert);
                    }
                } else {
                    this.#stateField.classList.remove(this.#alert);
                }
            }

            if (person.zip == '') {
                valid = false;
                this.#zipField.classList.add(this.#alert);
            } else {
                this.#zipField.classList.remove(this.#alert);
            }
        }

        // age is always required
        if (person.age === undefined || person.age == '') {
            valid = false;
            this.#ageField.classList.add(this.#alert);
        } else {
            if (this.#memberAge != '' && person.age != '' && person.age != this.#memberAge) {
                message += '<br/>Memerships have age ' + this.#memberAge + ' which does not match current age of ' + person.age + '.';
                this.#ageField.classList.add(this.#alert);
                valid = false;
            } else
                this.#ageField.classList.remove(this.#alert);
        }

        // now verify required policies
        if (policies) {
            for (let row in policies) {
                let policy = policies[row];
                if (policy.required == 'Y') {
                    let field = document.getElementById(this.#prefix + 'l_' + policy.policy);
                    if (!document.getElementById(this.#prefix + 'p_' + policy.policy).checked) {
                        if (this.#alertType == 'warn' || multiUse) {
                            message += '<br/>The required policy, ' + policy.policy + ', is not checked.';
                            valid = false;
                        } else {
                            message += '<br/>You cannot purchase memberships until you agree to the ' + policy.policy + ' policy.';
                            overrideAllowed = true;
                        }
                        field.classList.add('warncolor');
                    } else {
                        field.classList.remove(this.#alert);
                    }
                }
            }
        }

        // don't continue to process if any are missing
        if (!valid) {
            if (person.country == 'USA') {
                message += "<br/>Note: If any of the address fields Address, City, State/Prov or Zip/PC are used and the country is United States, " +
                    "then the Address, City, State, and Zip fields must all be entered and the state field must be a valid USPS two character state code.";
            }

            if (multiUse)
                return message;

            show_message("Please correct the items highlighted in " + this.#alertName + " and validate again.<br/>" + message,
                this.#alertType, messageDiv);

            return false;
        }

        if (overrideAllowed) {
            if (!override)
                return 'override';
        }

        // Check USPS for standardized address
        if (this.#uspsDiv != null && this.#countryField.value == 'USA' && this.#cityField.value != '' && this.#cityField.value != '/r' &&
            this.#stateField.value != '/r') {
            let data = this.#formData;
            data.source = 'login';
            data.prefix = this.#prefix;
            $.ajax({
                url: "scripts/uspsCheck.php",
                data: data,
                method: 'POST',
                success: function (data, textstatus, jqxhr) {
                    if (data.status == 'error') {
                        show_message(data.message, 'error', messageDiv);
                        return;
                    }
                    checkRefresh(data);
                    if (data.usps == null) {
                        profile.useMyAddress();
                        return;
                    }

                    profile.showValidatedAddress(data);
                },
                error: function (jqXHR, textStatus, errorThrown) {
                    show_message("ERROR! " + textStatus + ' ' + errorThrown + '<br/>Seek Assistance.', 'error', messageDiv);
                },
            });
            if (multiUse)
                return 'stop';
            return false;
        }
        if (multiUse)
            return '';
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
            let addr2 = ''
            if (this.#uspsAddress.addr2)
                addr2 = this.#uspsAddress.addr2.trim();

            if (this.#uspsAddress.address.trim() == this.#addrField.value.trim() &&
                addr2.trim() == this.#addr2Field.value.trim() &&
                this.#uspsAddress.city.trim() == this.#cityField.value.trim() &&
                this.#uspsAddress.state.trim() == this.#stateField.value.trim() &&
                this.#uspsAddress.zip.trim() == this.#zipField.value.trim()) {
                this.useMyAddress();
                return;
            }
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
        if (this.#email1Input)
            this.#email1Field.value = '';
        if (this.email2Field)
            this.#email2Field.value = '';
        if (this.#legalNameField)
            this.#legalNameField.value = '';
        this.#pronounsField.value = '';
        this.#badgenameField.value = '';
        this.#badgenameL2Field.value = '';
        this.#ageField.value = '';
        this.#memberAge = '';
        this.#ageText.hidden = true;
        this.#ageDiv.hidden = true;
        this.#ageField.hidden = false;

        this.#fnameField.classList.remove(this.#alert);
        this.#lnameField.classList.remove(this.#alert);
        this.#addrField.classList.remove(this.#alert);
        this.#cityField.classList.remove(this.#alert);
        this.#stateField.classList.remove(this.#alert);
        this.#zipField.classList.remove(this.#alert);
        this.#ageField.classList.remove(this.#alert);

        // reset the policies and interests
        if (typeof policies !== 'undefined') {
            for (let row in policies) {
                let policy = policies[row];
                let field = '#' + this.#prefix + 'p_' + policy.policy;
                $(field).prop('checked', policy.defaultValue == 'Y');
                field = '#' + this.#prefix + 'l_' + policy.policy;
                $(field).removeClass(this.#alert);
            }
        }
        if (typeof interests !== 'undefined') {
            for (let row in interests) {
                let interest = interests[row];
                let field = '#i_' + interest.interest;
                $(field).prop('checked', false);
            }
        }
    }

    // clear the entire profile form for a new load to edit
    clearForm() {
        this.clearNext();
        let defaultCountry = 'USA';
        if (config.hasOwnProperty('defaultCountry'))
            defaultCountry = config['defaultCountry'];

        this.#lnameField.value = '';
        this.#addrField.value = '';
        this.#addr2Field.value = '';
        this.#cityField.value = '';
        this.#stateField.value = '';
        this.#zipField.value = '';
        this.#countryField.value = defaultCountry;
        this.#phoneField.value = '';
        this.#lnameField.classList.remove(this.#alert);
        this.#addrField.classList.remove(this.#alert);
        this.#addr2Field.classList.remove(this.#alert);
        this.#cityField.classList.remove(this.#alert);
        this.#stateField.classList.remove(this.#alert);
        this.#zipField.classList.remove(this.#alert);
        this.#countryField.classList.remove(this.#alert);
        this.#phoneField.classList.remove(this.#alert);
    }
}
