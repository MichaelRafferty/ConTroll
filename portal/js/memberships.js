// addUpdate javascript, also requires base.js

var membership = null;

// initial setup
window.onload = function () {
    membership = new Membership();
}

class Membership {
    // current person info
    #epHeader = null;
    #personInfo = [];
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
    #lastVerified = null;

    // this person info
    #addUpdateId = null;
    #addUpdateType = null;
    #uspsAddress = null;

    // age items
    #ageButtonsDiv = null;
    #currentAge = null; // age selected but set in a membership item in the 'cart'
    #memberAge = null; // age in a membership item in the cart
    #memberAgeLabel = null;
    #memberAgeStatus = null;
    #memberAgeError = false;

    // membership items
    #memberships = null;
    #allMemberships = null;
    #membershipButtonsDiv = null;
    #newMembershipSave = null;
    #primaryColorMemberships = ['standard', 'wsfs', 'supplement','yearahead'];

    // cart items
    #cartDiv = null;
    #cartContentsDiv = null;
    #totalDue = 0;
    #countMemberships = 0;
    #unpaidMemberships = 0;
    #newIDKey = -1;
    #saveCartBtn = null;
    #cartChanges = 0;

    // flow items
    #auHeader = null
    #ageBracketDiv = null;
    #verifyPersonDiv = null;
    #getNewMembershipDiv = null;
    #currentStep = 1;
    #step4submitDiv = null;

    // variable price items
    #amountField = null;
    #vpModal = null;
    #vpBody = null;

    // Interests items
    #interestDiv = null;
    #oldInterests = null;
    #newInterests = null;

    constructor() {
        this.#memberships = [];
        this.#allMemberships = [];

        this.#auHeader = document.getElementById("auHeader");
        // set up div elements
        this.#ageButtonsDiv = document.getElementById("ageButtons");
        this.#membershipButtonsDiv = document.getElementById("membershipButtons");
        this.#ageBracketDiv = document.getElementById("ageBracketDiv");
        this.#verifyPersonDiv = document.getElementById("verifyPersonDiv");
        this.#getNewMembershipDiv = document.getElementById("getNewMembershipDiv");
        this.#cartDiv = document.getElementById("cartDiv");
        this.#cartContentsDiv = document.getElementById("cartContentsDiv");
        this.#step4submitDiv = document.getElementById("step4submit");
        this.#interestDiv = document.getElementById("verifyInterestDiv");

        this.#ageBracketDiv.hidden = false;
        this.#verifyPersonDiv.hidden = true;
        this.#getNewMembershipDiv.hidden = true;
        this.#interestDiv.hidden = true;
        this.#cartDiv.hidden = true;

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

        this.#saveCartBtn = document.getElementById("saveCartBtn");
        this.#vpBody = document.getElementById("variablePriceBody");
        var id = document.getElementById("variablePriceModal");
        if (id) {
            this.#vpModal = new bootstrap.Modal(id, {focus: true, backdrop: 'static'});
            id.addEventListener('hidden.bs.modal', amountModalHiddenHelper);
        }

        if (config['action'] != 'new') {
            this.#addUpdateType = config['upgradeType'];
            this.#addUpdateId = config['upgradeId'];
            this.getPersonInfo(this.#addUpdateId, this.#addUpdateType, true, false);
        } else {
            this.getPersonInfo(config.personId, config.personType, true, true);
        }
    }

// membership add/update functions
    // getPersonInfo
    getPersonInfo(id, type, ageButtons, newFlag) {
        if (id == null) {
            return;
        }

        var data = {
            getId: id,
            getType: type,
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
                if (data['status'] == 'error') {
                    show_message(data['message'], 'error');
                } else if (data['status'] == 'warn') {
                    show_message(data['message'], 'warn');
                } else {
                    if (config['debug'] & 1)
                        console.log(data);
                    membership.getPersonInfoSuccess(data, ageButtons, newFlag);
                }
            },
            error: function (jqXHR, textStatus, errorThrown) {
                showAjaxError(jqXHR, textStatus, errorThrown);
                return false;
            },
        });
    }

    // got the person, update the modal contents
    getPersonInfoSuccess(data, ageButtons, newFlag) {
        // ok, it's legal to edit this person, now populate the fields
        this.#personInfo = data['person'];
        if (data['memberships']) {
            this.#memberships = data['memberships'];
        }
        if (data['allMemberships']) {
            this.#allMemberships = data['allMemberships'];
        }

        if (data['interests']) {
            this.#oldInterests = data['interests'];
        }

        // now fill in the fields
        if (newFlag) {
            this.#fnameField.value = '';
            this.#mnameField.value = '';
            this.#suffixField.value = '';
            this.#legalnameField.value = '';
            this.#email1Field.value = '';
            this.#email2Field.value = '';
            this.#phoneField.value = '';
            this.#badgenameField.value = '';
            this.#personInfo['personType'] = 'n';
            this.#personInfo['id'] = '-1';
            this.#lastVerified = 0;
        } else {
            // person fields
            this.#fnameField.value = this.#personInfo['first_name'];
            this.#mnameField.value = this.#personInfo['middle_name'];
            this.#suffixField.value = this.#personInfo['suffix'];
            this.#legalnameField.value = this.#personInfo['legalName'];
            this.#email1Field.value = this.#personInfo['email_addr'];
            this.#email2Field.value = this.#personInfo['email_addr'];
            this.#phoneField.value = this.#personInfo['phone'];
            this.#badgenameField.value = this.#personInfo['badge_name'];
            this.#auHeader.innerHTML = 'Adding/Updating memberships for ' + this.#personInfo.fullname;
            this.#epHeader.innerHTML = 'Verifying personal information for ' + this.#personInfo.fullname;
            if (this.#personInfo['lastVerified'] != null) {
                var lvd = new Date(this.#personInfo['lastVerified']);
                this.#lastVerified = lvd.getTime();
            } else {
                this.#lastVerified = 0;
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
        this.#lnameField.value = this.#personInfo['last_name'];
        this.#addrField.value = this.#personInfo['address'];
        this.#addr2Field.value = this.#personInfo['addr_2'];
        this.#cityField.value = this.#personInfo['city'];
        this.#stateField.value = this.#personInfo['state'];
        this.#zipField.value = this.#personInfo['zip'];
        this.#countryField.value = this.#personInfo['country'];
        this.#uspsblock.innerHTML = '';

        this.#shareField.checked = (this.#personInfo['share_reg_ok'] == null || this.#personInfo['share_reg_ok'] == 'Y');
        this.#contactField.checked = (this.#personInfo['contact_ok'] == null || this.#personInfo['contact_ok'] == 'Y');
        if (data['memberships']) {
            this.#memberships = data['memberships'];
        }

        if (ageButtons)
            this.buildAgeButtons();

// temp for testing
// this.#currentAge = 'adult';
// this.gotoStep(3);
    }

    // age functions
    buildAgeButtons() {
        // first check if there is a current age;
        for (var row in this.#memberships) {
            var mbr = this.#memberships[row];
            if (mbr.memAge != 'all') {
                this.#memberAge = mbr.memAge;
                this.#memberAgeLabel = ageListIdx[this.#memberAge].label;
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

    buildMembershipButtons() {
        // now loop over age list and build each button
        var html = '';
        var rules = new MembershipRules(config['conid'], this.#memberAge != null ? this.#memberAge : this.#currentAge, this.#memberships, this.#allMemberships);

        for (var row in memList) {
            var mem = memList[row];
            // apply implitict rules and membershipRules against memList entry
            if (!rules.testMembership(mem))
                continue;

            // apply age filter from age select
            if (mem.memAge == 'all' || mem.memAge == this.#currentAge) {
                html += '<div class="col-sm-auto mt-1 mb-1"><button id="memBtn-' + mem.id + '" class="btn btn-sm btn-primary"' +
                    ' onclick="membership.membershipAdd(' + "'" + mem.id + "'" + ')">' +
                    (mem.conid != config['conid'] ? mem.conid + ' ' : '') + mem.label + '</button></div>' + "\n";
                }
        }
        this.#membershipButtonsDiv.innerHTML = html;
    }

    // goto step: handle going directly to a step:
    gotoStep(step, ignoreSkip = false) {
        var nowD = new Date();
        var now = nowD.getTime();
        var dif = (now - this.#lastVerified);

        clear_message();

        if (!ignoreSkip && step == 2 && (now - this.#lastVerified) < (7 * 24 * 60 * 60 * 1000)) {
            step = 4;
        }
        if (this.#oldInterests.length == 0 && step == 3)
            step = 4;
        this.#ageBracketDiv.hidden = step != 1;
        this.#verifyPersonDiv.hidden = step != 2;
        this.#interestDiv.hidden = step != 3;
        this.#getNewMembershipDiv.hidden = step != 4;
        this.#cartDiv.hidden = step != 4;
        clear_message();
        if (step == 4) {
            this.updateCart();
            this.buildMembershipButtons();
        }
        this.#currentStep = step;
    }

    // ageSelect - redo all the age buttons on selecting one of them, then move on to the next page
    ageSelect(ageType) {
        if (this.#memberAge != null && ageType != this.#memberAge) {
            this.#memberAgeError = true;
            show_message("You already have a membership of the age '" + this.#memberAgeLabel + "'.<br/>" +
                (this.#memberAgeStatus == 'cart' ? "You will need to remove this incorrect membership from your cart or change the age selected to continue." :
                    "If this new age is the correct age, please contact registration at " + config['regadminemail'] + " to assist you in correcting the prior membership.<br/>" +
                    "You may not be able to purchase appropriate additional memberships for this person until this is corrected.<br/>" +
                    "If this new age is not the correct age, select the proper age above to continue."), "warn");
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

        this.gotoStep(2, false);
    }

    // verifyAddress - verify with USPS if defined or go to step 3
    verifyAddress() {
        clear_message();
        var valid = true;
        var required = config['required'];
        var person = URLparamsToArray($('#addUpgradeForm').serialize());
        this.#personInfo.last_name = person.lname.trim();
        this.#personInfo.middle_name = person.mname.trim();
        this.#personInfo.first_name = person.fname.trim();
        this.#personInfo.suffix = person.suffix.trim();
        this.#personInfo.email_addr = person.email1.trim();
        this.#personInfo.phone = person.phone.trim();
        this.#personInfo.badge_name = person.badgename.trim();
        this.#personInfo.legalName = person.legalname.trim();
        this.#personInfo.address = person.addr.trim();
        this.#personInfo.addr_2 = person.addr2.trim();
        this.#personInfo.city = person.city.trim();
        this.#personInfo.state = person.state.trim();
        this.#personInfo.zip = person.zip.trim();
        this.#personInfo.country = person.country.trim();
        this.#personInfo.share_reg_ok = person.hasOwnProperty('share') ? 'Y' : 'N';
        this.#personInfo.contact_ok =  person.hasOwnProperty('contact') ? 'Y' : 'N';

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

        // don't continue to process if any are missing
        if (!valid) {
            show_message("Please correct the items highlighted in red and validate again.<br/>" +
                "Note: If any of the Address fields are used and the country is United States, " +
                "then the Address, City, State, and Zip fields must all be entered.",
            "error");
            return false;
        }

        this.#cartChanges++;
        // Check USPS for standardized address
        if (this.#uspsDiv != null && person['country'] == 'USA' && person['city'] != '') {
            var script = "scripts/uspsCheck.php";
            $.ajax({
                url: script,
                data: this.#personInfo,
                method: 'POST',
                success: function (data, textStatus, jqXhr) {
                    if (data['status'] == 'error') {
                        show_message(data['message'], 'error');
                        return false;
                    }
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
        clear_message();
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
            if ((this.#personInfo['addr'] == this.#uspsAddress['address'] || this.#personInfo['address'] == this.#uspsAddress['address']) &&
                (this.#personInfo['addr2'] == this.#uspsAddress['address2'] || this.#personInfo['addr_2'] == this.#uspsAddress['address2']) &&
                this.#personInfo['city'] == this.#uspsAddress['city'] && this.#personInfo['state'] == this.#uspsAddress['state'] &&
                this.#personInfo['zip'] == this.#uspsAddress['zip']) {
                this.useMyAddress();
                return;
            }

            html += "<pre>" + this.#uspsAddress['address'] + "\n";
            if (this.#uspsAddress['address2'])
                html += this.#uspsAddress['address2'] + "\n";
            html += this.#uspsAddress['city'] + ', ' + this.#uspsAddress['state'] + ' ' + this.#uspsAddress['zip'] + "</pre>\n";

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
        this.#cartChanges++;
        this.gotoStep(3);
    }

    useMyAddress() {
        this.#uspsDiv.innerHTML = '';
        this.gotoStep(3);
    }

    redoAddress() {
        this.#uspsDiv.innerHTML = '';
        this.#cartChanges++;
        this.verifyAddress();
    }

    // save Interests
    saveInterests() {
        var newValues = URLparamsToArray($('#editInterests').serialize());
        if (newValues.length != this.#newInterests.length) {
            this.#cartChanges++;
        } else {
            for (var row in newValues) {
                var oldValue = this.#newInterests[row];
                var newValue = newValues[row];
                if (oldValue != newValue) {
                    this.#cartChanges++;
                }
            }
        }
        this.#newInterests =  newValues;
        this.gotoStep(4);
    }

    // cart functions
    // updateCart - redraw the items in the cart
    updateCart() {
        this.#totalDue = 0;
        this.#countMemberships = 0;
        this.#unpaidMemberships = 0;
        var statusCol;
        var html = `
            <div class="row">
                <div class="col-sm-2"><b>Purchase Date</b></div>
                <div class="col-sm-1" style='text-align: right;'><b>Balance Due</b></div>
                <div class="col-sm-1" style='text-align: right;'><b>Price</b></div>
                <div class="col-sm-1"><b>Type</b></div>
                <div class="col-sm-1"><b>Category</b></div>
                <div class="col-sm-4"><b>Membership</b></div>
            </div>
`;
        var col1 = '';
        for (var row in this.#memberships) {
            var membershipRec = this.#memberships[row];
            if (membershipRec['status'] != 'in-cart' && membershipRec['status'] != 'unpaid')
                continue;

            this.#countMemberships++;
            var amount_due = Number(membershipRec.price) - (Number(membershipRec.paid) + Number(membershipRec.couponDiscount));
            if (membershipRec.status == 'unpaid')
                this.#totalDue += amount_due;

            if (membershipRec.status == 'unpaid')
                statusCol = Number(amount_due).toFixed(2)
            else
                statusCol = membershipRec.status;

            if (membershipRec.toDelete) {
                col1 = '<button class="btn btn-sm btn-secondary pt-0 pb-0" onclick="membership.membershipRestore(' + row + ')">Restore</button>';
            } else if (membershipRec.status == 'unpaid' && membershipRec.price > 0 && membershipRec.paid == 0) {
                col1 = '<button class="btn btn-sm btn-secondary pt-0 pb-0" onclick="membership.membershipDelete(' + row + ')">Delete</button>';
            } else if (membershipRec.status == 'in-cart') {
                col1 = '<button class="btn btn-sm btn-secondary pt-0 pb-0" onclick="membership.membershipRemove(' + row + ')">Remove</button>';
            } else {
                col1 = membershipRec.create_date;
            }
            html += `
    <div class="row">
        <div class="col-sm-2">` + col1 + `</div>
        <div class="col-sm-1" style='text-align: right;'>` + statusCol + `</div>
        <div class="col-sm-1" style='text-align: right;'>` + membershipRec.price + `</div>
        <div class="col-sm-1">` + membershipRec.memType + `</div>
        <div class="col-sm-1">` + membershipRec.memCategory + `</div>
        <div class="col-sm-4">` + (membershipRec.conid != config.conid ? membershipRec.conid + ' ' : '') + membershipRec.label + ' [' + ageListIdx[membershipRec.memAge].label + `]</div>
    </div>
`;
        }
        if (this.#totalDue > 0) {
            html += `
    <div class='row'>
        <div class='col-sm-12 ms-0 me-0 align-center'>
            <hr color="black" style='height:3px;width:95%;margin:auto;margin-top:10px;margin-bottom:2px;'/>
        </div>
        <div class='col-sm-12 ms-0 me-0 align-center'>
            <hr color="black" style='height:3px;width:95%;margin:auto;margin-top:2px;margin-bottom:20px;'/>
        </div>
    </div>
    <div class="row">
        <div class="col-sm-1"></div>
        <div class="col-sm-1"><b>Total Due:</b></div>
        <div class="col-sm-1" style='text-align: right;'><b>$` + Number(this.#totalDue).toFixed(2)+ `</b></div>
    </div>`
        }
        if (this.#countMemberships == 0) {
            html = "No memberships found";
        }
        this.#cartContentsDiv.innerHTML = html;

        if (this.#cartChanges > 0)
            this.#saveCartBtn.innerHTML = "Save the cart and return to the home page";
        else
            this.#saveCartBtn.innerHTML = "Return to the home page.";
    }

    // add to cart
    membershipAdd(id) {
        clear_message();
        var memrow = findMembership(id);
        if (memrow == null)
            return;

        var now = new Date();
        var newMembership = {};
        newMembership.id = this.#newIDKey;
        newMembership.create_date = now.getFullYear() + '-' + ('0' + (now.getMonth() + 1)).slice(-2) + '-' + ('0' + now.getDate()).slice(-2) + ' ' +
            ('0' + now.getHours()).slice(-2) + ':' + ('0' + now.getMinutes()).slice(-2) + ':' + ('0' + now.getSeconds()).slice(-2);
        newMembership.memId = id;
        newMembership.conid = memrow.conid;
        newMembership.status = 'in-cart';
        newMembership.price = memrow.price;
        newMembership.paid = 0;
        newMembership.couponDiscount = 0;
        newMembership.label = memrow.label;
        newMembership.memCategory = memrow.memCategory;
        newMembership.memType = memrow.memType;
        newMembership.memAge = memrow.memAge;
        var memCat = memCategories[memrow.memCategory];
        if (memCat.variablePrice == 'Y') {
            var mem = memListIdx[newMembership.memId];
            // update the modal with the item
            this.#vpBody.innerHTML = `
    <div class="row">
        <div class="col-sm-auto">
            <label for="vpPrice">How much for ` + mem.label + `?</label>
        </div>
        <div class="col-sm-auto">
            <input type="number" class='no-spinners' inputmode="numeric" id="vpPrice" name="vpPrice" size="20" placeholder="How Much?" min="` + mem.price + `"/>
        </div>
    </div>
`;
            this.#vpModal.show();
            this.#amountField = document.getElementById("vpPrice");
            this.#amountField.addEventListener('keyup', membership.amountEventListener);
            newMembership.minPrice = mem.price;
            this.#newMembershipSave = newMembership;
            this.#amountField.focus({focusVisible: true});
            return;
        }
        this.membershipAddFinal(newMembership);
    }

    amountEventListener(e) {
        if (e.code === 'Enter')
            membership.vpSubmit();
    }

    amountModalHidden(e) {
        clear_message('vpMessageDiv');
        this.#amountField.removeEventListener('keyup', membership.amountEventListener);
    }

    // vpsubmit - handle return from modal popup
    vpSubmit() {
        var priceField = document.getElementById('vpPrice');
        var price = Number(priceField.value).toFixed(2);
        var newMembership = this.#newMembershipSave;
        if (Number(price) < Number(newMembership.minPrice)) {
            show_message("Your " + newMembership.label + " cannot be less than " + newMembership.minPrice, 'warn', 'vpMessageDiv');
            return;
        }
        this.#newMembershipSave = null;
        newMembership.price = price;
        this.membershipAddFinal(newMembership);
        this,this.#vpModal.hide();
    }

    // finish membership add
    membershipAddFinal(newMembership) {
        if (!this.#memberships)
            this.#memberships = [];
        this.#memberships.push(newMembership);
        this.newIDKey--;
        this.#cartChanges++;
        this.updateCart();
        this.buildMembershipButtons();
    }

    // remove an unsaved membership row from the cart
    membershipRemove(row) {
        clear_message();
        if (this.#memberships == null) {
            show_message("No memberships found", "warn");
            return;
        }

        var mbr = this.#memberships[row];
        if (mbr.status != 'in-cart') {
            show_message("Cannot remove that membership, only in-cart memberships can be removed.", "warn");
            return
        }

        // check if anything else in the cart depends on this membership
        // trial the delete
        mbr.toDelete = true;
        var rules = new MembershipRules(config['conid'], this.#memberAge != null ? this.#memberAge : this.#currentAge, this.#memberships, this.#allMemberships);
        for (var nrow in this.#memberships) {
            if (row == nrow)    // skip checking ourselves
                continue;
            var nmbr = this.#memberships[nrow];
            if (nmbr.toDelete)
                continue;
            if (rules.testMembership(nmbr, true) == false) {
                mbr.toDelete = undefined;
                show_message("You cannot remove " + mbr.label + " because " + nmbr.label + " requires it.  You must delete/remove " + nmbr.label + " first.", 'warn');
                return;
            }
        }


        this.#memberships.splice(row, 1);
        this.#cartChanges--;
        this.updateCart();
        this.buildMembershipButtons();
    }

    // mark an unpaid membership row to be deleted on save
    membershipDelete(row) {
        clear_message();
        if (this.#memberships == null) {
            show_message("No memberships found", "warn");
            return;
        }

        var mbr = this.#memberships[row];
        if (mbr.status != 'unpaid') {
            show_message("Cannot remove that membership, only unpaid membershipd can be deleted.", "warn");
            return
        }

        if (mbr.price == 0) {
            show_message("Please contact Registration to delete free memberships.", "warn");
            return;
        }

        if (mbr.paid > 0) {
            show_message("Please contact Registration to resolve this partially paid membership.", "warn");
            return;
        }

        // check if anything else in the cart depends on this membership
        // trial the delete
        mbr.toDelete = true;
        var rules = new MembershipRules(config['conid'], this.#memberAge != null ? this.#memberAge : this.#currentAge, this.#memberships, this.#allMemberships);
        for (var nrow in this.#memberships) {
            if (row == nrow)    // skip checking ourselves
                continue;
            var nmbr = this.#memberships[nrow];
            if (nmbr.toDelete)
                continue;
            nmbr.toDelete = true;
            if (rules.testMembership(nmbr, true) == false) {
                mbr.toDelete = undefined;
                nmbr.toDelete = undefined;
                show_message("You cannot delete " + mbr.label + " because " + nmbr.label + " requires it.  You must delete/remove " + nmbr.label + " first.", 'warn');
            }
            nmbr.toDelete = undefined;
        }


        this.#cartChanges++;
        this.updateCart();
        this.buildMembershipButtons();
    }

    membershipRestore(row) {
        clear_message();
        if (this.#memberships == null) {
            show_message("No memberships found", "warn");
            return;
        }

        var mbr = this.#memberships[row];
        if (!mbr.toDelete) {
            show_message("Cannot restore this membership, it is not marked deleted.", "warn");
            return
        }

        var rules = new MembershipRules(config['conid'], this.#memberAge != null ? this.#memberAge : this.#currentAge, this.#memberships, this.#allMemberships);
        if (rules.testMembership(mbr, false) == false) {
            show_message("You cannot restore " + mbr.label + " because it requires some other deleted membership. Look at your memberships marked 'Restore'" +
                " and restore its prerequesite", "warn");
        } else {
            mbr.toDelete = undefined;
        }
        this.#cartChanges--;
        this.updateCart();
        this.buildMembershipButtons();
    }

    findInCart(memId) {
        if (!this.#memberships)
            return null; // no list to search

        for (var row in this.#memberships) {
            var cartrow = this.#memberships[row];
            if (memId != cartrow.memId)
                continue;
            return cartrow;  // return matching entry
        }
        return null; // not found
    }

    // save cart / return home button
    saveCart() {
        var _this = this;
        if (this.#cartChanges == 0) {
            // go back to the home page
            window.location = "portal.php?messageFwdmessageFwd=" + encodeURI("No Changes");
            return;
        }
        this.#saveCartBtn.disabled = true;

        var script = 'scripts/updateFromCart.php';
        var data = {
            action: 'updateCart',
            cart: JSON.stringify(this.#memberships),
            person: JSON.stringify(this.#personInfo),
            oldInterests: JSON.stringify(this.#oldInterests),
            newInterests: JSON.stringify(URLparamsToArray($('#editInterests').serialize())),
        }

        $.ajax({
            method: 'POST',
            url: script,
            data: data,
            success: function (data, textStatus, jqXhr) {
                if (data['status'] == 'error') {
                    show_message(data['message'], 'error');
                    _this.#saveCartBtn.disabled = false;
                } else if (data['status'] == 'warn') {
                    show_message(data['message'], 'warn');
                    _this.#saveCartBtn.disabled = false;
                } else {
                    if (config['debug'] & 1)
                        console.log(data);
                    membership.saveCartComplete(data);
                }
            },
            error: function (jqXHR, textStatus, errorThrown) {
                showAjaxError(jqXHR, textStatus, errorThrown);
                _this.#saveCartBtn.disabled = false;
                return false;
            },
        });
    }

    saveCartComplete(data) {
        // once saved, return home
        var location = "portal.php";
        if (data['message']) {
            window.location = location + '?messageFwd=' + encodeURI(data['message']);
        } else {
            window.location = location+ '?messageFwd=' + encodeURI("No Changes");
        }
        return;
    }
}

function amountModalHiddenHelper(event) {
    membership.amountModalHidden(event);
}
