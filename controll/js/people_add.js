class Add {
    #matchTable = null;
    #addPersonBtn = null;
    #addPersonOverrideBTN = null;

    #debug = 0;
    #debugVisible = false;

    // add fields matches
    #policiesDiv = null;
    #managerDiv = null;
    #active = null;
    #banned = null;
    #uspsDiv = null;

    #matched = null;

    #prefix = 'a_'; // add's prefix for fields in edit

    // globals before open
    constructor(debug) {
        this.#debug = debug;
        if (this.#debug & 2) {
            this.#debugVisible = true;
        }
        this.#addPersonBtn = document.getElementById('addPersonBTN');
        this.#addPersonOverrideBTN = document.getElementById('addPersonOverrideBTN');
    }

    // called on open of the add window
    open(msg = null) {
        this.clearForm();
        profile.hideAgeDiv(true);
        profile.hideAgeText(true);
    }

    // check if a close match for this person exists and display a table of matches.
    checkExists() {
        clear_message();
        clearError();
        var postdata = {
            type: 'check',
            firstName: profile.fname(),
            middleName: profile.mname(),
            lastName: profile.lname(),
            suffix: profile.suffix(),
            legalName: profile.legalName(),
            pronouns: profile.pronouns(),
            badgeName: profile.badgename(),
            badgeNameL2: profile.badgenameL2(),
            address: profile.addr(),
            addr2: profile.addr2(),
            city: profile.city(),
            state: profile.state(),
            zip: profile.zip(),
            country: profile.country(),
            emailAddr: profile.email(),
            email_addr: profile.email(),
            phone: profile.phone(),
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
                pagination: this.#matched.length > 25,
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
                    {title: "Badge Name", field: "badgename", width: 200, headerSort: true, headerFilter: true, formatter: 'html', },
                    {title: "Full Address", field: "fullAddr", width: 300, headerSort: true, headerFilter: true, formatter: "textarea", },
                    {title: "Email", field: "email_addr", width: 250, headerSort: true, headerFilter: true,},
                    {title: "Phone", field: "phone", width: 150, headerSort: true, headerFilter: true,},
                    {title: "Current Age", field: "currentAgeType", width: 100, headerSort: true, headerFilter: true, headerWordWrap: true, },
                    {title: "Date Created", field: "creation_date", width: 180, headerSort: true, headerFilter: true,},
                    {field: 'first_name', visible: false,},
                    {field: 'middle_name', visible: false,},
                    {field: 'last_name', visible: false,},
                    {field: 'suffix', visible: false,},
                    {field: 'legalName', visible: false,},
                    {field: 'badge_name', visible: false,},
                    {field: 'badgeNameL2', visible: false,},
                    {field: 'pronouns', visible: false,},
                    {field: 'address', visible: false,},
                    {field: 'addr_2', visible: false,},
                    {field: 'city', visible: false,},
                    {field: 'state', visible: false,},
                    {field: 'zip', visible: false,},
                    {field: 'country', visible: false,},
                    {field: 'active', visible: false,},
                    {field: 'banned', visible: false,},
                    {title: "Admin Notes", headerWordWrap: true, field: 'admin_notes', visible: false, },
                    {title: "Open Notes", headerWordWrap: true,field: 'open_notes', visible: false, },
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
        this.#addPersonOverrideBTN.disabled = true;
        this.close();
        peopleEditPerson(index, row);
    }

    // addPerson - they decided it's a new person, add them
    addPerson() {
        if (this.#matchTable != null) {
            this.#matchTable.destroy();
            this.#matchTable = null;
        }
        clear_message();
        clearError();

        let person = URLparamsToArray($('#a_editPerson').serialize());
        if (!profile.validate(person, null, addNewPerson2, addNewPerson)) {
            this.#addPersonOverrideBTN.disabled = false;
            return;
        }

        this.addPerson2();
        return;
    }

    addPerson2() {
        var postdata = {
            type: 'add',
            firstName: profile.fname(),
            middleName: profile.mname(),
            lastName: profile.lname(),
            suffix: profile.suffix(),
            legalName: profile.legalName(),
            pronouns: profile.pronouns(),
            badgeName: profile.badgename(),
            badgeNameL2: profile.badgenameL2(),
            address: profile.addr(),
            addr2: profile.addr2(),
            city: profile.city(),
            state: profile.state(),
            zip: profile.zip(),
            country: profile.country(),
            emailAddr: profile.email(),
            email_addr: profile.email(),
            phone: profile.phone(),
            currentAgeType: profile.age() == '' ? null : profile.age(),
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
       profile.clearNext();

        if (this.#matchTable != null) {
            this.#matchTable.destroy();
            this.#matchTable = null;
        }
        this.#addPersonBtn.disabled = true;
        this.#addPersonOverrideBTN.disabled = true;
    }

    // empty the form, and other parts for starting over
    clearForm() {
        profile.clearForm();
        if (this.#matchTable != null) {
            this.#matchTable.destroy();
            this.#matchTable = null;
        }
        clear_message();
        clearError();
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

function addNewPerson() {
    addPerson.addPerson();
}

function addNewPerson2() {
    addPerson.addPerson2();
}
