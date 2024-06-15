// Main portal javascript, also requires base.js

var portal = null;

// initial setup
window.onload = function () {
    if (config.loadPlans) {
        paymentPlans = new PaymentPlans();
    }
    portal = new Portal();
}

class Portal {
    // edit person modal
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

    // person fields
    #currentPerson = null;
    #currentPersonType = null;
    #fullname = null;
    #personSave = null;
    #uspsAddress = null;

    // interests fields
    #editInterestsModal = null;
    #editInterestsTitle = null;
    #eiHeaderDiv = null
    #eiPersonIdField = null
    #eiPersonTypeField = null;
    #interests = null;

    // payment fields
    #payBalanceBTN = null;

    constructor() {
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
        }
        id = document.getElementById("editInterestModal");
        if (id) {
            this.#editInterestsModal = new bootstrap.Modal(id, {focus: true, backdrop: 'static'});
            this.#editInterestsTitle = document.getElementById('editInterestsTitle');
            this.#eiHeaderDiv = document.getElementById('eiHeader');
            this.#eiPersonIdField = document.getElementById('eiPersonId');
            this.#eiPersonTypeField = document.getElementById("eiPersonType");
        }

        this.#payBalanceBTN = document.getElementById('payBalanceBTN');
        if (this.#payBalanceBTN != null && paymentPlanList != null) {
            if (paymentPlans.plansEligible(membershipsPurchased)) {
                this.#payBalanceBTN.innerHTML = "Pay Balance (or start a payment plan)";
            }

        }
    }

    // disassociate: remove the managed by link for this logged in person
    disassociate() {
        var data = {
            'managedBy': 'disassociate',
        }
        var script = 'scripts/processDisassociate.php';
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

        this.#currentPerson = id;
        this.#currentPersonType = type;
        var data = {
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
                if (data['status'] == 'error') {
                    show_message(data['message'], 'error');
                } else if (data['status'] == 'warn') {
                    show_message(data['message'], 'warn');
                } else {
                    if (config['debug'] & 1)
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
        var person = data['person'];
        var post = data['post'];

        var fullname = person['fullname'] + ' (';
        if (post['getType'] == 'n') {
            fullname += 'Temporary ';
        }
        fullname += 'ID: ' + person['id'] + ')</strong>';
        this.#fullname = fullname;

        this.#editPersonTitle.innerHTML = '<strong>Editing: ' + fullname + '</strong>';
        if (this.#uspsDiv && person['country'] == 'USA') {
            this.#editPersonSubmitBtn.innerHTML = 'Validate Address and Update ' + fullname;
        } else {
            this.#editPersonSubmitBtn.innerHTML = 'Update ' + fullname;
        }

        // now fill in the fields
        this.#epHeaderDiv.innerHTML = '<strong>Editing: ' + fullname + '</strong>';
        this.#epPersonIdField.value = post['getId'];
        this.#epPersonTypeField.value = post['getType'];
        this.#fnameField.value = person['first_name'];
        this.#mnameField.value = person['middle_name'];
        this.#lnameField.value = person['last_name'];
        this.#suffixField.value = person['suffix'];
        this.#legalnameField.value = person['legalName'];
        this.#addrField.value = person['address'];
        this.#addr2Field.value = person['addr_2'];
        this.#cityField.value = person['city'];
        this.#stateField.value = person['state'];
        this.#zipField.value = person['zip'];
        this.#countryField.value = person['country'];
        this.#uspsblock.innerHTML = '';
        this.#email1Field.value = person['email_addr'];
        this.#email2Field.value = person['email_addr'];
        this.#phoneField.value = person['phone'];
        this.#badgenameField.value = person['badge_name'];
        this.#shareField.checked = (person['share_reg_ok'] == null || person['share_reg_ok'] == 'Y');
        this.#contactField.checked = (person['contact_ok'] == null || person['contact_ok'] == 'Y');

        this.#editPersonModal.show();
    }

    // countryChange - if USPS and USA, then change button
    countryChange() {
        if (this.#uspsDiv == null)
            return;

        var country = this.#countryField.value;
        if (this.#uspsDiv && country == 'USA') {
            this.#editPersonSubmitBtn.innerHTML = 'Validate Address and Update ' + this.#fullname;
        } else {
            this.#editPersonSubmitBtn.innerHTML = 'Update ' + this.#fullname;
        }
    }

// validate the edit person form for saving
    validate(person) {
        //process(formRef) {
        var valid = true;

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
        if (!valid) {
            show_message("Please correct the items highlighted in red and validate again", "error");
            return false;
        }

        // Check USPS for standardized address
        if (this.#uspsDiv != null && (person['country'] == 'USA')) {
            this.#personSave = person;
            this.#uspsAddress = null;
            var script = "scripts/uspsCheck.php";
            $.ajax({
                url: script,
                data: person,
                method: 'POST',
                success: function (data, textStatus, jqXhr) {
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
                var person = this.#personSave;
                if (person['addr'] == this.#uspsAddress['address'] && person['addr2'] == this.#uspsAddress['address2'] &&
                    person['city'] == this.#uspsAddress['city'] && person['state'] == this.#uspsAddress['state'] &&
                    person['zip'] == this.#uspsAddress['zip']) {
                    portal.useMyAddress();
                    return;
                }

                html += "<pre>" + this.#uspsAddress['address'] + "\n";
                if (this.#uspsAddress['address2'])
                    html += this.#uspsAddress['address2'] + "\n";
                html += this.#uspsAddress['city'] + ', ' + this.#uspsAddress['state'] + ' ' + this.#uspsAddress['zip'] + "</pre>\n";
            }
            if (this.#uspsAddress['valid'] == 'Valid')
                html += '<button class="btn btn-sm btn-primary m-1 mb-2" onclick="portal.useUSPS();">Update using USPS Validated Address</button>'
        }
        html += '<button class="btn btn-sm btn-secondary m-1 mb-2 " onclick="portal.useMyAddress();">Update using Address as Entered</button><br/>' +
            '<button class="btn btn-sm btn-secondary m-1 mt-2" onclick="portal.redoAddress();">I fixed the address, validate it again.</button>';

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
        this.editPersonSubmit(true);
    }

    useMyAddress() {
        this.#uspsDiv.innerHTML = '';
        this.editPersonSubmit(true);
    }

    redoAddress() {
        this.#uspsDiv.innerHTML = '';
        this.editPersonSubmit(false);
    }

    // now submit the updates to the person
    editPersonSubmit(novalidate = false) {
        clear_message();
        var person = URLparamsToArray($('#editPerson').serialize());
        if (!novalidate)
            if (!this.validate(person))
                return;
        
        var data = {
            person: person,
            currentPerson: this.#currentPerson,
            currentPersonType: this.#currentPersonType,
        }
        if (config['debug'] & 1)
            console.log(data);

        var script = 'scripts/updatePersonInfo.php';
        $.ajax({
            method: 'POST',
            url: script,
            data: data,
            success: function (data, textStatus, jqXhr) {
                portal.updatePersonSuccess(data);

            },
            error: function (jqXHR, textStatus, errorThrown) {
                showAjaxError(jqXHR, textStatus, errorThrown, 'epMessageDiv');
                return false;
            },
        });
    }

    updatePersonSuccess(data){
        if (data['status'] == 'error') {
            show_message(data['message'], 'error', 'epMessageDiv');
        } else {
            if (config['debug'] & 1)
                console.log(data);
            show_message(data['message']);
            this.#editPersonModal.hide();
            if (data['rows_upd'] > 0) {
                window.location.search = '?messageFwd=' + encodeURI(data['message']);
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
                if (data['status'] == 'error') {
                    show_message(data['message'], 'error');
                } else if (data['status'] == 'warn') {
                    show_message(data['message'], 'warn');
                } else {
                    if (config['debug'] & 1)
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
        var person = data['person'];
        var post = data['post'];
        this.#interests = data['interests'];

        var fullname = person['fullname'] + ' (';
        if (post['getType'] == 'n') {
            fullname += 'Temporary ';
        }
        fullname += 'ID: ' + person['id'] + ')</strong>';
        this.#fullname = fullname;

        this.#editInterestsTitle.innerHTML = '<strong>Editing Interests for: ' + fullname + '</strong>';

        // now fill in the fields
        this.#eiHeaderDiv.innerHTML = 'Editing Interests for: ' + fullname;
        this.#eiPersonIdField.value = post['getId'];
        this.#eiPersonTypeField.value = post['getType'];

        for (var row in this.#interests) {
            var interest = this.#interests[row];
            var id = document.getElementById('i_' + interest.interest);
            id.checked = interest.interested == 'Y';
        }

        this.#editInterestsModal.show();
    }

    // editInterestsSubmit - save back the interests
    editInterestSubmit() {
        clear_message();
        var data = {
            existingInterests: JSON.stringify(this.#interests),
            newInterests: JSON.stringify(URLparamsToArray($('#editInterests').serialize())),
            currentPerson: this.#currentPerson,
            currentPersonType: this.#currentPersonType,
        }
        if (config['debug'] & 1)
            console.log(data);

        var script = 'scripts/updateInterests.php';
        $.ajax({
            method: 'POST',
            url: script,
            data: data,
            success: function (data, textStatus, jqXhr) {
                portal.updateInterestsSuccess(data);

            },
            error: function (jqXHR, textStatus, errorThrown) {
                showAjaxError(jqXHR, textStatus, errorThrown, 'eiMessageDiv');
                return false;
            },
        });

    }

    updateInterestsSuccess(data){
        if (data['status'] == 'error') {
            show_message(data['message'], 'error', 'eiMessageDiv');
        } else {
            if (config['debug'] & 1)
                console.log(data);
            show_message(data['message']);
            this.#editInterestsModal.hide();
            if (data['rows_upd'] > 0) {
                window.location.search = '?messageFwd=' + encodeURI(data['message']);
            }
        }
    }
}
