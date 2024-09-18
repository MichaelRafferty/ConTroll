//import { TabulatorFull as Tabulator } from 'tabulator-tables';
// policy class - all edit membership policy functions
class Find {
    #messageDiv = null;
    #findTitle = null;
    #findTable = null;
    #addPersonBtn = null;

    #debug = 0;
    #debugVisible = false;

    // find fields matches
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
        this.#messageDiv = document.getElementById('result_message_add');
        this.#findTitle = document.getElementById('findTitle');
        this.#addPersonBtn = document.getElementById('addPersonBTN');

        this.#firstName = document.getElementById('fname');
        this.#middleName = document.getElementById('mname');
        this.#lastName = document.getElementById('lname');
        this.#suffix = document.getElementById('suffix');
        this.#legalName = document.getElementById('legalname');
        this.#pronouns = document.getElementById('pronouns');
        this.#badgeName = document.getElementById('badgename');
        this.#address = document.getElementById('addr');
        this.#addr2 = document.getElementById('addr2');
        this.#city = document.getElementById('city');
        this.#state = document.getElementById('state');
        this.#zip = document.getElementById('zip');
        this.#country = document.getElementById('country');
        this.#emailAddr = document.getElementById('email1');
        this.#emailAddr2 = document.getElementById('email2');
        this.#phone = document.getElementById('phone');
    }

    // called on open of the add window
    open(msg = null) {
        this.clearForm();
    }

    // check if a close match for this person exists and display a table of matches.
    checkExists() {
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
        this.#findTable = new Tabulator('#findTable', {
            data: this.#matched,
            layout: "fitDataTable",
            index: "id",
            pagination: true,
            paginationAddRow:"table",
            paginationSize: 10,
            paginationSizeSelector: [10, 25, 50, 100, 250, true], //enable page size select element with these options
            columns: [
                {title: "Match", formatter: this.selectButton, headerSort: false },
                {title: "ID", field: "id", width: 120, headerHozAlign:"right", hozAlign: "right", headerSort: true},
                {title: "Mgr Id", field: "managerId", headerWordWrap: true, width: 120,headerSort: false },
                {title: "Manager", field: "manager", width: 200, headerSort: true, headerFilter: true, },
                {title: "Full Name", field: "fullName", width: 300, headerSort: true, headerFilter: true, },
                {title: "Badge Name", field: "fullName", width: 200, headerSort: true, headerFilter: true, },
                {title: "Full Address", field: "fullAddr", width: 400, headerSort: true, headerFilter: true, },
                {title: "Ctry", field: "country", width: 60, headerSort: false, headerFilter: false, },
                {title: "Email", field: "email_addr", width: 250, headerSort: true, headerFilter: true, },
                {title: "Phone", field: "phone", width: 150, headerSort: true, headerFilter: true, },
                {title: "Date Created", field: "creation_date", width: 180, headerSort: true, headerFilter: true, },
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
        peopleEditPerson(index, this.#findTable.getRow(index).getData());
    }

    // empty the form, and other parts for starting over
    clearForm() {
        this.#firstName.value = '';
        this.#middleName.value = '';
        this.#lastName.value = '';
        this.#suffix.value = '';
        this.#legalName.value = '';
        this.#pronouns.value = '';
        this.#badgeName = document.getElementById('badgename');
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
    }

    // on close of the pane, clean up the items
    close() {
         if (this.#findTable != null) {
            this.#findTable.destroy();
            this.#findTable = null;
        }
    };
}