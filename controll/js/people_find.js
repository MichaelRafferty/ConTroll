//import { TabulatorFull as Tabulator } from 'tabulator-tables';
// policy class - all edit membership policy functions
class Find {
    #messageDiv = null;
    #findTable = null;
    #editTitle = null;
    #editPersonName = null;
    #findPattern = null;
    #addPersonBtn = null;

    #debug = 0;
    #debugVisible = false;

    // find fields
    #editModal = null;

    // edit person fields
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
    #managerName = null;
    #managerId = null;
    #policiesDiv = null;
    #active = null;
    #banned = null;
    #openNotes = null;
    #adminNotes = null
    #memberPolicies = null;
    #memberInterests = null;
    #managed = null;
    #managesHdr = null;
    #managesRow = null;
    #managerHdr = null;
    #managerRow = null;

    #matched = null;
    #editRow = null;

    // globals before open
    constructor(debug) {
        this.#debug = debug;
        if (this.#debug & 2) {
            this.#debugVisible = true;
        }
        this.#editPersonName = document.getElementById('editPersonName');
        this.#findPattern = document.getElementById('find_pattern');
        this.#findPattern.addEventListener('keyup', findKeyUpListener);
        this.#messageDiv = document.getElementById('find_edit_message');

        this.#addPersonBtn = document.getElementById('findAddPersonBTN');
        var id  = document.getElementById('edit-person');
        if (id) {
            this.#editModal = new bootstrap.Modal(id, {focus: true, backdrop: 'static'});
            this.#editTitle = document.getElementById('editTitle');
            this.#firstName = document.getElementById('f_fname');
            this.#middleName = document.getElementById('f_mname');
            this.#lastName = document.getElementById('f_lname');
            this.#suffix = document.getElementById('f_suffix');
            this.#legalName = document.getElementById('f_legalname');
            this.#pronouns = document.getElementById('f_pronouns');
            this.#badgeName = document.getElementById('f_badgename');
            this.#address = document.getElementById('f_addr');
            this.#addr2 = document.getElementById('f_addr2');
            this.#city = document.getElementById('f_city');
            this.#state = document.getElementById('f_state');
            this.#zip = document.getElementById('f_zip');
            this.#country = document.getElementById('f_country');
            this.#emailAddr = document.getElementById('f_email1');
            this.#emailAddr2 = document.getElementById('f_email2');
            this.#phone = document.getElementById('f_phone');
            this.#managerId = document.getElementById('f_managerId');
            this.#managerName = document.getElementById('f_managerName');
            this.#active = document.getElementById('f_active');
            this.#banned = document.getElementById('f_banned');
            this.#openNotes = document.getElementById('f_open_notes');
            this.#adminNotes = document.getElementById('f_admin_notes');
            this.#managesHdr = document.getElementById('managesHdr');
            this.#managesRow = document.getElementById('managesRow');
            this.#managerHdr = document.getElementById('managerHdr');
            this.#managerRow = document.getElementById('managerRow');
        }
    }

    // called on open of the add window
    open(msg = null) {
        this.clearForm();
        if (this.#findTable != null) {
            this.#findTable.destroy();
            this.#findTable = null;
        }
        this.#findPattern.focus();
    }

    // find matching records
    find() {
        var postdata = {
            type: 'find',
            pattern: this.#findPattern.value,
        };
        var script = 'scripts/people_findPerson.php';
        var _this = this;
        clear_message();
        clearError();
        $.ajax({
            url: script,
            method: 'POST',
            data: postdata,
            success: function (data, textStatus, jhXHR) {
                _this.findSuccess(data);
            },
            error: function (jqXHR, textStatus, errorThrown) {
                showError("ERROR in " + script + ": " + textStatus, jqXHR);
                show_message("ERROR in " + script + ": " + jqXHR.responseText, 'error');
                return false;
            }
        });
    }

    // see if there are any matches, if so draw the table, else just enable add new person, if country is USA, add validate USPS to this step
    findSuccess(data) {
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
                {title: "Edit", formatter: this.editButton, headerSort: false },
                {title: "ID", field: "id", width: 120, headerHozAlign:"right", hozAlign: "right", headerSort: true},
                {title: "Mgr Id", field: "managerId", headerHozAlign:"right", hozAlign: "right", headerWordWrap: true, width: 120,headerSort: false },
                {title: "Managed By", field: "manager", headerWordWrap: true, width: 150, headerSort: true, headerFilter: true, },
                {title: "Full Name", field: "fullName", width: 200, headerSort: true, headerFilter: true, },
                {title: "Badge Name", field: "fullName", width: 200, headerSort: true, headerFilter: true, },
                {title: "Full Address", field: "fullAddr", width: 400, headerSort: true, headerFilter: true, },
                {title: "Ctry", field: "country", width: 60, headerSort: false, headerFilter: false, },
                {title: "Email", field: "email_addr", width: 250, headerSort: true, headerFilter: true, },
                {title: "Phone", field: "phone", width: 150, headerSort: true, headerFilter: true, },
                {title: "Memberships", field: "memberships", width: 300, headerSort: true, headerFilter: true, },
                {field: "creation_date", visible: false },
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
                {field: 'admin_notes', visible: false,},
                {field: 'open_notes', visible: false,},
            ],
        });
    }

    // select edit: edit this person
    editButton(cell, formatterParams, onRendered) {
        var row = cell.getRow();
        var index = row.getIndex()

        return '<button class="btn btn-primary" style = "--bs-btn-padding-y: .0rem; --bs-btn-padding-x: .3rem; --bs-btn-font-size: .75rem;",' +
            ' onclick="findPerson.editPerson(' + index + ');">Edit</button>';
    }

    // editPerson - call up this person to edit
    editPerson(index) {
        this.#editRow = this.#findTable.getRow(index).getData();

        var postdata = {
            type: 'details',
            perid: index,
        };
        var script = 'scripts/people_findGetDetails.php';
        var _this = this;
        clear_message();
        clearError();
        $.ajax({
            url: script,
            method: 'POST',
            data: postdata,
            success: function (data, textStatus, jhXHR) {
                _this.findDetailsSuccess(data);
            },
            error: function (jqXHR, textStatus, errorThrown) {
                showError("ERROR in " + script + ": " + textStatus, jqXHR);
                show_message("ERROR in " + script + ": " + jqXHR.responseText, 'error');
                return false;
            }
        });
    }

    findDetailsSuccess(data) {
        var i;  // index

        this.#memberPolicies = data['policies'];
        this.#memberInterests = data['interests'];
        this.#managed = data['managed'];
        // populate the form
        this.#editPersonName.innerHTML = this.#editRow.fullName;
        this.#firstName.value = this.#editRow.first_name;
        this.#middleName.value = this.#editRow.middle_name;
        this.#lastName.value = this.#editRow.last_name;
        this.#suffix.value = this.#editRow.suffix;
        this.#legalName.value = this.#editRow.legalname;
        this.#pronouns.value = this.#editRow.pronouns;
        this.#badgeName.value = this.#editRow.badge_name;
        this.#address.value = this.#editRow.address;
        this.#addr2.value = this.#editRow.addr_2;
        this.#city.value = this.#editRow.city;
        this.#state.value = this.#editRow.state;
        this.#zip.value = this.#editRow.zip;
        this.#country.value = this.#editRow.country;
        this.#emailAddr.value = this.#editRow.email_addr;
        this.#emailAddr2.value = this.#editRow.email_addr;
        this.#phone.value = this.#editRow.phone;
        this.#managerId.value = this.#editRow.managerId;
        this.#managerName.innerHTML = this.#editRow.manager;
        this.#active.value = this.#editRow.active;
        this.#banned.value = this.#editRow.banned;
        this.#openNotes.innerHTML = this.#editRow.open_notes;
        this.#adminNotes.innerHTML = this.#editRow.admin_notes;

        // loop over the policies
        var keys = Object.keys(this.#memberPolicies);
        for (i = 0; i < keys.length; i++) {
            var policy = this.#memberPolicies[keys[i]];
            document.getElementById('p_f_' + policy.policy).checked = policy.response == 'Y';
        }

        // now the interests
        keys = Object.keys(this.#memberInterests);
        for (i = 0; i < keys.length; i++) {
            var interest = this.#memberInterests[keys[i]];
            document.getElementById('i_' + interest.interest).checked = interest.interested == 'Y';
        }

        if (this.#managed.length > 0) {
            this.#managerHdr.hidden = true;
            this.#managerRow.hidden = true;
            this.#managesHdr.hidden = false;
            this.#managesRow.hidden = false;
            this.#managerId.value = '';
            this.#managerName.innerHTML = '';
            // now the manages section
            var html = '';
            for (i = 0; i < this.#managed.length; i++) {
                var mper = this.#managed[i];
                html += '<div class="col-sm-1">' +
                    '<button class="btn btn-sm btn-warning" onclick="findPerson.unmanage(' + "'" + mper.type + mper.id + "'" + ')">Unmanage</button>' +
                    '</div>\n' +
                    '<div class="col-sm-1">' + mper.type + mper.id + '</div>\n' +
                    '<div class="col-sm-10">' + mper.fullName + '</div>\n';
            }
            this.#managesRow.innerHTML = html;
        } else {
            this.#managerHdr.hidden = false;
            this.#managerRow.hidden = false;
            this.#managesHdr.hidden = true;
            this.#managesRow.hidden = true;
            this.#managerId.value = this.#editRow.managerId;
            this.#managerName.innerHTML = this.#editRow.manager;
        }
        this.#editModal.show();
    }

    // clear the manager
    disassociate() {
        this.#managerId.value = null;
        this.#managerName.innerHTML = '';
    }

    // change the manager
    changeManager() {
        console.log("change manager called");
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
    }

    // on close of the pane, clean up the items
    close() {
        this.clearForm();
        if (this.#findTable != null) {
            this.#findTable.destroy();
            this.#findTable = null;
        }
        this.#findPattern.removeEventListener('keyup', findKeyUpListener);
    };
}

function findKeyUpListener(e) {
    if (e.code === 'Enter')
        findPerson.find();
}