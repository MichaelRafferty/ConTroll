//import { TabulatorFull as Tabulator } from 'tabulator-tables';
// policy class - all edit membership policy functions
class Add {
    #matchTable = null;
    #addPersonBtn = null;

    #debug = 0;
    #debugVisible = false;

    // add fields matches
    #firstName = null;
    #middleName = null;
    #lastName = null;
    #suffix = null;
    #legalName = null;
    #pronouns = null;
    #badgeName = null;
    #address = null;
    #addr2 = null;
    #city = null;
    #state = null;
    #zip = null;
    #country = null;
    #emailAddr = null;
    #emailAddr2 = null;
    #phone = null;
    #policiesDiv = null;
    #managerDiv = null;
    #active = null;
    #banned = null;

    #matched = null;

    // globals before open
    constructor(debug) {
        this.#debug = debug;
        if (this.#debug & 2) {
            this.#debugVisible = true;
        }
        this.#addPersonBtn = document.getElementById('addPersonBTN');

        this.#firstName = document.getElementById('a_fname');
        this.#middleName = document.getElementById('a_mname');
        this.#lastName = document.getElementById('a_lname');
        this.#suffix = document.getElementById('a_suffix');
        this.#legalName = document.getElementById('a_legalname');
        this.#pronouns = document.getElementById('a_pronouns');
        this.#badgeName = document.getElementById('a_badgename');
        this.#address = document.getElementById('a_addr');
        this.#addr2 = document.getElementById('a_addr2');
        this.#city = document.getElementById('a_city');
        this.#state = document.getElementById('a_state');
        this.#zip = document.getElementById('a_zip');
        this.#country = document.getElementById('a_country');
        this.#emailAddr = document.getElementById('a_email1');
        this.#emailAddr2 = document.getElementById('a_email2');
        this.#phone = document.getElementById('a_phone');
    }

    // called on open of the add window
    open(msg = null) {
        this.clearForm();
    }

    // check if a close match for this person exists and display a table of matches.
    checkExists() {
        var email1 = this.#emailAddr.value;
        var email2 = this.#emailAddr2.value;
        if (email1 != email2) {
            show_message("Email addresses do not match", 'error');
            return;
        }

        clear_message();
        clearError();
        var postdata = {
            type: 'check',
            firstName: this.#firstName.value,
            middleName: this.#middleName.value,
            lastName: this.#lastName.value,
            suffix: this.#suffix.value,
            legalName: this.#legalName.value,
            pronouns: this.#pronouns.value,
            badgeName: this.#badgeName.value,
            address: this.#address.value,
            addr2: this.#addr2.value,
            city: this.#city.value,
            state: this.#state.value,
            zip: this.#zip.value,
            country: this.#country.value,
            emailAddr: this.#emailAddr.value,
            phone: this.#phone.value,
        };
        var script = 'scripts/people_checkExists.php';
        var _this = this;
        $.ajax({
            url: script,
            method: 'POST',
            data: postdata,
            success: function (data, textStatus, jhXHR) {
                _this.checkSuccess(data);
            },
            error: function (jqXHR, textStatus, errorThrown) {
                showError("ERROR in " + script + ": " + textStatus, jqXHR);
                show_message("ERROR in " + script + ": " + jqXHR.responseText, 'error');
                return false;
            }
        });
    }

    // see if there are any matches, if so draw the table, else just enable add new person, if country is USA, add validate USPS to this step
    checkSuccess(data) {
        if (data['error']) {
            show_message(data['error'], 'error');
            return;
        }
        if (data['warn']) {
            show_message(data['warn'], 'warn');
            return;
        }

        if (data['success']) {
            show_message(data['success'], 'success');
        }

        this.#matched = data['matches'];
        if (this.#matched.length > 0) {
            this.#matchTable = new Tabulator('#matchTable', {
                data: this.#matched,
                layout: "fitDataTable",
                index: "id",
                pagination: true,
                paginationAddRow: "table",
                paginationSize: 10,
                paginationSizeSelector: [10, 25, 50, 100, 250, true], //enable page size select element with these options
                columns: [
                    {title: "Match", formatter: this.selectButton, headerSort: false},
                    {title: "ID", field: "id", width: 80, headerHozAlign: "right", hozAlign: "right", headerSort: true},
                    {title: "Mgr Id", field: "managerId", width: 80, headerHozAlign: "right", hozAlign: "right", headerWordWrap: true, headerSort: false},
                    {title: "Manager", field: "manager", width: 150, headerSort: true, headerFilter: true,},
                    {title: "Full Name", field: "fullName", width: 250, headerSort: true, headerFilter: true, headerFilterFunc: fullNameHeaderFilter,
                        formatter: "textarea", },
                    {title: "Badge Name", field: "badgename", width: 200, headerSort: true, headerFilter: true,},
                    {title: "Full Address", field: "fullAddr", width: 300, headerSort: true, headerFilter: true, formatter: "textarea", },
                    {title: "Email", field: "email_addr", width: 250, headerSort: true, headerFilter: true,},
                    {title: "Phone", field: "phone", width: 150, headerSort: true, headerFilter: true,},
                    {title: "Date Created", field: "creation_date", width: 180, headerSort: true, headerFilter: true,},
                    {field: 'first_name', visible: false,},
                    {field: 'middle_name', visible: false,},
                    {field: 'last_name', visible: false,},
                    {field: 'suffix', visible: false,},
                    {field: 'legalName', visible: false,},
                    {field: 'pronouns', visible: false,},
                    {field: 'address', visible: false,},
                    {field: 'addr_2', visible: false,},
                    {field: 'city', visible: false,},
                    {field: 'state', visible: false,},
                    {field: 'zip', visible: false,},
                    {field: 'country', visible: false,},
                    {field: 'active', visible: false,},
                    {field: 'banned', visible: false,},
                ],
            });
        } else {
            if (this.#matchTable != null) {
                this.#matchTable.destroy();
                this.#matchTable = null;
            }
        }
        this.#addPersonBtn.disabled = false;
    }

    // select button: chose this person instead
    selectButton(cell, formatterParams, onRendered) {
        var row = cell.getRow();
        var index = row.getIndex()

        return '<button class="btn btn-primary" style = "--bs-btn-padding-y: .0rem; --bs-btn-padding-x: .3rem; --bs-btn-font-size: .75rem;",' +
            ' onclick="addPerson.selectPerson(' + index + ');">Select</button>';
    }

    // selectPerson - take this person and switch to the Find/Edit tab with this person
    selectPerson(index) {
        console.log(index);
        var row = this.#matchTable.getRow(index).getData();
        if (this.#matchTable != null) {
            this.#matchTable.destroy();
            this.#matchTable = null;
        }
        this.#addPersonBtn.disabled = true;
        this.close();
        peopleEditPerson(index, row);
    }

    // addPerson - they decided it's a new person, add them
    addPerson() {
        var email1 = this.#emailAddr.value;
        var email2 = this.#emailAddr2.value;
        if (email1 != email2) {
            show_message("Email addresses do not match", 'error');
            return;
        }

        var postdata = {
            type: 'add',
            firstName: this.#firstName.value,
            middleName: this.#middleName.value,
            lastName: this.#lastName.value,
            suffix: this.#suffix.value,
            legalName: this.#legalName.value,
            pronouns: this.#pronouns.value,
            badgeName: this.#badgeName.value,
            address: this.#address.value,
            addr2: this.#addr2.value,
            city: this.#city.value,
            state: this.#state.value,
            zip: this.#zip.value,
            country: this.#country.value,
            emailAddr: this.#emailAddr.value,
            phone: this.#phone.value,
            newPolicies: JSON.stringify(URLparamsToArray($('#a_editPolicies').serialize())),
        };
        var script = 'scripts/people_addNewPerson.php';
        var _this = this;
        $.ajax({
            url: script,
            method: 'POST',
            data: postdata,
            success: function (data, textStatus, jhXHR) {
                _this.addSuccess(data);
            },
            error: function (jqXHR, textStatus, errorThrown) {
                showError("ERROR in " + script + ": " + textStatus, jqXHR);
                show_message("ERROR in " + script + ": " + jqXHR.responseText, 'error');
                return false;
            }
        });
    }

    // add succeeded
    addSuccess(data) {
        if (data['error']) {
            show_message(data['error'], 'error');
            return;
        }
        if (data['warn']) {
            show_message(data['warn'], 'warn');
            return;
        }

        if (data['success']) {
            show_message(data['success'], 'success');
        }
        this.#firstName.value = '';
        this.#emailAddr.value = '';
        this.#emailAddr2.value = '';

        // clear the policy fields
        this.#resetPolicies();

        if (this.#matchTable != null) {
            this.#matchTable.destroy();
            this.#matchTable = null;
        }
        this.#addPersonBtn.disabled = true;
    }

    // empty the form, and other parts for starting over
    clearForm() {
        this.#firstName.value = '';
        this.#middleName.value = '';
        this.#lastName.value = '';
        this.#suffix.value = '';
        this.#legalName.value = '';
        this.#pronouns.value = '';
        this.#badgeName.value = '';
        this.#address.value = '';
        this.#addr2.value = '';
        this.#city.value = '';
        this.#state.value = '';
        this.#zip.value = '';
        this.#country.value = 'USA';
        this.#emailAddr.value = '';
        this.#emailAddr2.value = '';
        this.#phone.value = '';
        this.#addPersonBtn.disabled = true;
        this.#resetPolicies();
        if (this.#matchTable != null) {
            this.#matchTable.destroy();
            this.#matchTable = null;
        }
        clear_message();
        clearError();
    }

    // reset the policies to defaults
    #resetPolicies() {
        var index = 0;
        var keys = Object.keys(policies);
        for (index = 0; index < keys.length; index++) {
            var policy = policies[keys[index]];
            var policyField = 'p_a_' + policy.policy;
            document.getElementById(policyField).checked = policy.defaultValue == 'Y';
        }
    }

    // on close of the pane, clean up the items
    close() {
        this.clearForm();
    };

    // countryChange - normally used to enable / disable USPS,
    countryChange() {
        console.log("TODO: add country Change/USPS check");
    }
}