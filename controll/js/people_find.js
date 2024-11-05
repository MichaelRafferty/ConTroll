//import { TabulatorFull as Tabulator } from 'tabulator-tables';
// policy class - all edit membership policy functions
class Find {
    #messageDiv = null;
    #findTable = null;
    #editTitle = null;
    #editPersonName = null;
    #findPattern = null;
    #addPersonBtn = null;
    #directEdit = null;

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
    #addManages = null;
    #managesId = null;
    #managerLookupRows = null
    #managerLookupFind = null;
    #newManagerLookup = null;
    #managerLookupTable = null;
    #managesLookupFind = null;
    #newManagesLookup = null;
    #managesLookupTable = null;
    #managesLookupRows = null
    #managesName = null;

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
            this.#managerLookupFind = document.getElementById('managerLookupFind');
            this.#newManagerLookup = document.getElementById('newManagerLookup');
            this.#newManagerLookup.addEventListener('keyup', managerLookupListener);
            this.#managesLookupFind = document.getElementById('managesLookupFind');
            this.#newManagesLookup = document.getElementById('newManagesLookup');
            this.#newManagesLookup.addEventListener('keyup', managesLookupListener);
            this.#addManages = document.getElementById('addManages');
            this.#managesId = document.getElementById('f_managesId');
            this.#managesName = document.getElementById('managesName');
        }
    }

    // called on open of the add window
    open(msg = null, id = null, row = null) {
        this.clearForm();
        if (this.#findTable != null) {
            this.#findTable.destroy();
            this.#findTable = null;
        }
        if (this.#managerLookupTable != null) {
            this.#managerLookupTable.destroy();
            this.#managerLookupTable = null;
        }
        if (this.#managesLookupTable != null) {
            this.#managesLookupTable.destroy();
            this.#managesLookupTable = null;
        }
        if (id != null) {
            clear_message();
            clearError();
            this.close();
            this.#managerHdr.hidden = true;
            this.#managerRow.hidden = true;
            this.#managesHdr.hidden = true;
            this.#managesRow.hidden = true;
            this.#managerLookupFind.hidden = true;
            this.#managesLookupFind.hidden = true;
            this.#addManages.hidden = true;
            var rows = [];
            rows.push(row);
            var data = {};
            data['matches'] = rows;
            this.#directEdit = id;
            this.findSuccess(data);
            setTimeout(add_editPerson, 100);
            return;
        }
        this.#findPattern.focus();
    }

    // delayed edit
    directEdit() {
        this.editPerson(this.#directEdit);
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
        this.close();
        this.#managerHdr.hidden = true;
        this.#managerRow.hidden = true;
        this.#managesHdr.hidden = true;
        this.#managesRow.hidden = true;
        this.#managerLookupFind.hidden = true;
        this.#managesLookupFind.hidden = true;
        this.#addManages.hidden = true;
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
                {title: "ID", field: "id", width: 80, headerHozAlign:"right", hozAlign: "right", headerSort: true},
                {title: "Mgr Id", field: "managerId", headerHozAlign:"right", hozAlign: "right", headerWordWrap: true, width: 80,headerSort: false },
                {title: "Managed By", field: "manager", headerWordWrap: true, width: 150, headerSort: true, headerFilter: true, },
                {title: "Full Name", field: "fullName", width: 200, headerSort: true, headerFilter: true, },
                {title: "Badge Name", field: "badge_name", width: 200, headerSort: true, headerFilter: true, },
                {title: "Full Address", field: "fullAddr", width: 400, headerSort: true, headerFilter: true, },
                {title: "Ctry", field: "country", width: 60, headerSort: false, headerFilter: false, },
                {title: "Email", field: "email_addr", width: 250, headerSort: true, headerFilter: true, },
                {title: "Phone", field: "phone", width: 150, headerSort: true, headerFilter: true, },
                {title: "Memberships", field: "memberships", minWidth: 300, headerSort: true, headerFilter: true, },
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

        this.#addPersonBtn.disabled = false;
    }

    // addperson button - go to add person
    addPerson() {
        this.close();
        peopleAddPerson();
    }

    // select edit: edit this person
    editButton(cell, formatterParams, onRendered) {
        var row = cell.getRow();
        var index = row.getIndex()

        return '<button class="btn btn-primary" type="button" ' +
            'style = "--bs-btn-padding-y: .0rem; --bs-btn-padding-x: .3rem; --bs-btn-font-size: .75rem;",' +
            ' onclick="findPerson.editPerson(' + index + ');">Edit</button>';
    }

    // select manager: select this row as manager
    selectButton(cell, formatterParams, onRendered) {
        var row = cell.getRow();
        var index = row.getIndex()
        var managerId = row.getData().managerId;
        if (managerId != null && managerId != '') {
            return '(managed)';
        }
        return '<button class="btn btn-primary" type="button" ' +
            'style = "--bs-btn-padding-y: .0rem; --bs-btn-padding-x: .3rem; --bs-btn-font-size: .75rem;",' +
            ' onclick="findPerson.selectManager(' + index + ');">Select</button>';
    }

    // select manager: select this row as manager
    managerButton(cell, formatterParams, onRendered) {
       return findPerson.managerBTN(cell);
    }

    managerBTN(cell){
        var row = cell.getRow();
        var index = row.getIndex()
        var managerId = row.getData().managerId;
        if (managerId == null || managerId == '') {
            return '<button class="btn btn-primary" type="button" ' +
                'style = "--bs-btn-padding-y: .0rem; --bs-btn-padding-x: .3rem; --bs-btn-font-size: .75rem;",' +
                ' onclick="findPerson.assignManager(' + index + ');">Manage</button>';
        }

        if (managerId == this.#editRow.id) {
            return '(Managed)';
        }
        return "(Mby " + managerId + ")";
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
        clear_message('find_edit_message');
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
        if (data['error']) {
            show_message(data['error'], 'error');
            return;
        }
        if (data['warn']) {
            show_message(data['warn'], 'warn');
            return;
        }

        this.#memberPolicies = data['policies'];
        this.#memberInterests = data['interests'];
        this.#managed = data['managed'];
        // populate the form
        this.#editPersonName.innerHTML = this.#editRow.fullName + ' (' + this.#editRow.id + ')';
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
            var response = policy.response;
            if (response === null || response === undefined) {
                response = policy.defaultValue;
            }
            document.getElementById('p_f_' + policy.policy).checked = response == 'Y';
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
            this.#managesLookupFind.hidden = true;
            this.#addManages.hidden = false;
            this.#managerId.value = '';
            this.#managerName.innerHTML = '';
            // now the manages section
            var html = '';
            for (i = 0; i < this.#managed.length; i++) {
                var mper = this.#managed[i];
                html += '<div class="col-sm-1">' +
                    '<button class="btn btn-sm btn-warning" type="button" id="u_' + mper.type + mper.id + '" ' +
                    'onclick="findPerson.unmanage(' + "'" + mper.type + mper.id + "'" + ')">Unmanage</button>' +
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
            this.#managesLookupFind.hidden = true;
            this.#addManages.hidden = true;
            this.#managerId.value = this.#editRow.managerId;
            this.#managerName.innerHTML = this.#editRow.manager;
        }

        if (data['success']) {
            show_message(data['success'], 'success', 'find_edit_message');
        }
        this.#editModal.show();
    }

    // clear the manager
    disassociate() {
        this.#managerId.value = null;
        this.#managerName.innerHTML = '';
    }

    // clear the managee
    unmanage(link) {
        console.log(link);
        var script = 'scripts/people_unmanage.php';
        var postdata = {
            action: 'unmanage',
            who: link,
        }
        var _this = this;
        $.ajax({
            url: script,
            method: 'POST',
            data: postdata,
            success: function (data, textStatus, jhXHR) {
                _this.unmanageSuccess(data);
            },
            error: function (jqXHR, textStatus, errorThrown) {
                showError("ERROR in " + script + ": " + textStatus, jqXHR);
                show_message("ERROR in " + script + ": " + jqXHR.responseText, 'error', 'find_edit_message');
                return false;
            }
        });
    }

    // unmanageSuccess - after delete of managed person fix up screen
    unmanageSuccess(data) {
        if (data['error']) {
            show_message(data['error'], 'error', 'find_edit_message');
            return;
        }
        if (data['warn']) {
            show_message(data['warn'], 'warn', 'find_edit_message');
            return;
        }

        // change the button to be inactive and it's name to be unmanaged
        var button = document.getElementById('u_' + data['who']);
        if (button) {
            button.innerHTML = 'cleared';
            button.disabled = true;
            button.classList.remove("btn-warning");
        }

        if (data['success']) {
            show_message(data['success'], 'success', 'find_edit_message');
            return;
        }
    }

    // findManager - show the search block to look for a new manager, you don't know the id directly
    findManager() {
        clear_message('find_edit_message');
        this.#newManagerLookup.value = '';
        this.#managerLookupFind.hidden = false;
        this.#newManagerLookup.focus();
    }

    // lookup manager - preform the search and show the table
    lookupManager() {
        var searchStr = this.#newManagerLookup.value;
        if (searchStr == '') {
            show_message("Enter a search string in the Lookup New Manager text box", 'error', 'find_edit_message')
        }
        var postdata = {
            type: 'manager',
            pattern: searchStr,
        }
        var script = 'scripts/people_findPerson.php';
        clear_message('find_edit_message');
        clear_message();
        clearError();
        var _this = this;
        $.ajax({
            url: script,
            method: 'POST',
            data: postdata,
            success: function (data, textStatus, jhXHR) {
                _this.lookupManagerSuccess(data);
            },
            error: function (jqXHR, textStatus, errorThrown) {
                showError("ERROR in " + script + ": " + textStatus, jqXHR);
                show_message("ERROR in " + script + ": " + jqXHR.responseText, 'error', 'find_edit_message');
                return false;
            }
        });
    }

    // lookup manager Success - draw the table
    lookupManagerSuccess(data) {
        if (data['error']) {
            show_message(data['error'], 'error', 'find_edit_message');
            return;
        }
        if (data['warn']) {
            show_message(data['warn'], 'warn', 'find_edit_message');
            return;
        }

        this.#managerLookupRows = data['matches'];
        this.#managerLookupTable = new Tabulator('#managerTableDiv', {
            data: this.#managerLookupRows,
            layout: "fitDataTable",
            index: "id",
            pagination: true,
            paginationAddRow:"table",
            paginationSize: 10,
            paginationSizeSelector: [10, 25, 50, 100, 250, true], //enable page size select element with these options
            columns: [
                {title: "Select", formatter: this.selectButton, headerSort: false },
                {title: "ID", field: "id", width: 80, headerHozAlign:"right", hozAlign: "right", headerSort: true},
                {title: "Mgr Id", field: "managerId", headerHozAlign:"right", hozAlign: "right", headerWordWrap: true, width: 80,headerSort: false },
                {title: "Managed By", field: "manager", headerWordWrap: true, width: 150, headerSort: true, headerFilter: true, },
                {title: "Full Name", field: "fullName", width: 200, headerSort: true, headerFilter: true, },
                {title: "Badge Name", field: "badge_name", width: 200, headerSort: true, headerFilter: true, },
                {title: "Full Address", field: "fullAddr", width: 400, headerSort: true, headerFilter: true, },
                {title: "Ctry", field: "country", width: 60, headerSort: false, headerFilter: false, },
                {title: "Email", field: "email_addr", width: 250, headerSort: true, headerFilter: true, },
                {title: "Phone", field: "phone", width: 150, headerSort: true, headerFilter: true, },
                {title: "Memberships", field: "memberships", minWidth: 300, headerSort: true, headerFilter: true, },
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
        if (data['success']) {
            show_message(data['success'], 'success', 'find_edit_message');
        }
    }
    // change the manager
    selectManager(index) {
        this.#managerId.value = index;
        this.#managerName.innerHTML = this.#managerLookupTable.getRow(index).getData().fullName;
        clear_message('find_edit_message');
        this.#newManagerLookup.value = '';
        this.#managerLookupFind.hidden = true;
        if (this.#managerLookupTable != null) {
            this.#managerLookupTable.destroy();
            this.#managerLookupTable = null;
        }
    }

    // findManages - show the search block to look for a new managee, you don't know the id directly
    findManages() {
        clear_message('find_edit_message');
        this.#newManagesLookup.value = '';
        this.#managesLookupFind.hidden = false;
        this.#newManagesLookup.focus();
    }

    // lookup managea - preform the search and show the table
    lookupManages() {
        var searchStr = this.#newManagesLookup.value;
        if (searchStr == '') {
            show_message("Enter a search string in the Find Person to Manage text box", 'error', 'find_edit_message')
        }
        var postdata = {
            type: 'managed',
            pattern: searchStr,
        }
        var script = 'scripts/people_findPerson.php';
        clear_message('find_edit_message');
        clear_message();
        clearError();
        var _this = this;
        $.ajax({
            url: script,
            method: 'POST',
            data: postdata,
            success: function (data, textStatus, jhXHR) {
                _this.lookupManagesSuccess(data);
            },
            error: function (jqXHR, textStatus, errorThrown) {
                showError("ERROR in " + script + ": " + textStatus, jqXHR);
                show_message("ERROR in " + script + ": " + jqXHR.responseText, 'error', 'find_edit_message');
                return false;
            }
        });
    }

    // lookup manages Success - draw the table
    lookupManagesSuccess(data) {
        if (data['error']) {
            show_message(data['error'], 'error', 'find_edit_message');
            return;
        }
        if (data['warn']) {
            show_message(data['warn'], 'warn', 'find_edit_message');
            return;
        }

        this.#managesLookupRows = data['matches'];
        this.#managesLookupTable = new Tabulator('#managesTableDiv', {
            data: this.#managesLookupRows,
            layout: "fitDataTable",
            index: "id",
            pagination: true,
            paginationAddRow:"table",
            paginationSize: 10,
            paginationSizeSelector: [10, 25, 50, 100, 250, true], //enable page size select element with these options
            columns: [
                {title: "Manage", formatter: findPerson.managerButton, headerSort: false },
                {title: "ID", field: "id", width: 80, headerHozAlign:"right", hozAlign: "right", headerSort: true},
                {title: "Mgr Id", field: "managerId", headerHozAlign:"right", hozAlign: "right", headerWordWrap: true, width: 80,headerSort: false },
                {title: "Managed By", field: "manager", headerWordWrap: true, width: 150, headerSort: true, headerFilter: true, },
                {title: "Full Name", field: "fullName", width: 200, headerSort: true, headerFilter: true, },
                {title: "Badge Name", field: "badge_name", width: 200, headerSort: true, headerFilter: true, },
                {title: "Full Address", field: "fullAddr", width: 400, headerSort: true, headerFilter: true, },
                {title: "Ctry", field: "country", width: 60, headerSort: false, headerFilter: false, },
                {title: "Email", field: "email_addr", width: 250, headerSort: true, headerFilter: true, },
                {title: "Phone", field: "phone", width: 150, headerSort: true, headerFilter: true, },
                {title: "Memberships", field: "memberships", minWidth: 300, headerSort: true, headerFilter: true, },
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
        if (data['success']) {
            show_message(data['success'], 'success', 'find_edit_message');
        }
    }
    // add the managee
    assignManager(index) {
        this.#managesId.value = index;
        this.#managesName.innerHTML = this.#managesLookupTable.getRow(index).getData().fullName;
        clear_message('find_edit_message');
        this.#newManagesLookup.value = '';
        this.#managesLookupFind.hidden = true;
        if (this.#managesLookupTable != null) {
            this.#managesLookupTable.destroy();
            this.#managesLookupTable = null;
        }

        // now add the row to the database
        addManages(index);
    }

    // addManages - update the database and redraw the screen with a new manages entry
    addManages(index = null) {
        if (index == null) {
            index = this.#managesId.value;
        }

        if (index == null || index == '') {
            show_message("Enter the perid to manage, or use the Find Person to Manage button", 'error', 'find_edit_message')
            return;
        }

        var script = 'scripts/people_manage.php';
        var postdata = {
            action: 'manage',
            who: index,
            manager: this.#editRow.id,
        }
        var _this = this;
        $.ajax({
            url: script,
            method: 'POST',
            data: postdata,
            success: function (data, textStatus, jhXHR) {
                _this.addManageeSuccess(data);
            },
            error: function (jqXHR, textStatus, errorThrown) {
                showError("ERROR in " + script + ": " + textStatus, jqXHR);
                show_message("ERROR in " + script + ": " + jqXHR.responseText, 'error', 'find_edit_message');
                return false;
            }
        });
    }

    // unmanageSuccess - after delete of managed person fix up screen
    addManageeSuccess(data) {
        if (data['error']) {
            show_message(data['error'], 'error', 'find_edit_message');
            return;
        }
        if (data['warn']) {
            show_message(data['warn'], 'warn', 'find_edit_message');
            return;
        }

        // reload the page with the new person on the list, it's too hard to add it in JS right now
        this.close(false);
        this.editPerson(this.#editRow.id);
    }

    // saveEdit - save the edited data back to the database
    saveEdit() {
        // validate the data, simple checks
        var email1 = this.#emailAddr.value;
        var email2 = this.#emailAddr2.value;
        if (email1 != email2) {
            show_message("Email addresses do not match", 'error', 'find_edit_message');
            return
        }
        if (validateAddress(email1) == false) {
            show_message("Invalid Email Address", 'error', 'find_edit_message');
            return;
        }

        if (this.#managerId.value == this.#editRow.id) {
            show_message("The person is set to manage themselves, this is not allowed, if they are not managed, clear the field",
                'error', 'find_edit_message');
            return;
        }

        // first we need the perid we are editing, this.#editRow has the row
        var script = 'scripts/people_updateEdit.php';

        // we need to pass each section, the profile, the policies, the interests, the manager, the manages, the active/banned, and the notes
        // first the edit fields on the form directly
        var postdata = {
            action: 'saveedit',
            perid: this.#editRow.id,
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
            emailAddr2: this.#emailAddr2.value,
            phone: this.#phone.value,
            managerId: this.#managerId.value,
            managerName: this.#managerName.innerHTML,
            active: this.#active.value,
            banned: this.#banned.value,
            openNotes: this.#openNotes.value,
            adminNotes: this.#adminNotes.value,
            oldPolicies: JSON.stringify(this.#memberPolicies),
            existingInterests: JSON.stringify(this.#memberInterests),
        };
        // now the policies
        var keys = Object.keys(this.#memberPolicies);
        var i;
        var newPolicies = {};
        for (i = 0; i < keys.length; i++) {
            var policy = this.#memberPolicies[keys[i]];
            if (document.getElementById('p_f_' + policy.policy).checked) {
                newPolicies['p_' + policy.policy] = 'Y';
            }
        }
        postdata['newPolicies'] = JSON.stringify(newPolicies);

        // now the interests
        var newInterests = {};
        var keys = Object.keys(this.#memberInterests);
        for (var i = 0; i < keys.length; i++) {
            var interest = this.#memberInterests[keys[i]];
            if (document.getElementById('i_' + interest.interest).checked) {
                newInterests[interest.interest] = 'Y';
            }
        }
        postdata['newInterests'] = JSON.stringify(newInterests);

        // manager
        postdata['managerId'] = document.getElementById('f_managerId').value;
        // manages
        postdata['managed'] = this.#managed;

        var _this = this;
        $.ajax({
            url: script,
            method: 'POST',
            data: postdata,
            success: function (data, textStatus, jhXHR) {
                _this.saveEditSuccess(data);
            },
            error: function (jqXHR, textStatus, errorThrown) {
                showError("ERROR in " + script + ": " + textStatus, jqXHR);
                show_message("ERROR in " + script + ": " + jqXHR.responseText, 'error', 'find_edit_message');
                return false;
            }
        });
    }

    saveEditSuccess(data) {
        if (data['error']) {
            show_message(data['error'], 'error', 'find_edit_message');
            return;
        }
        if (data['warn']) {
            show_message(data['warn'], 'warn', 'find_edit_message');
            return;
        }

        // update the underlying row in the table
        this.#editRow.first_name =  this.#firstName.value.trim();
        this.#editRow.middle_name = this.#middleName.value.trim();
        this.#editRow.last_name = this.#lastName.value.trim();
        this.#editRow.suffix = this.#suffix.value.trim();
        this.#editRow.legalName = this.#legalName.value.trim();
        this.#editRow.pronouns = this.#pronouns.value.trim();
        this.#editRow.badge_name = this.#badgeName.value.trim();
        this.#editRow.address = this.#address.value.trim();
        this.#editRow.addr2 = this.#addr2.value.trim();
        this.#editRow.city = this.#city.value.trim();
        this.#editRow.state = this.#state.value.trim();
        this.#editRow.zip = this.#zip.value.trim();
        this.#editRow.country = this.#country.value.trim();
        this.#editRow.emailAddr = this.#emailAddr.value.trim();
        this.#editRow.email_Addr = this.#emailAddr2.value.trim();
        this.#editRow.phone = this.#phone.value.trim();
        this.#editRow.managerId = this.#managerId.value.trim();
        this.#editRow.manager = this.#managerName.innerHTML.trim();
        this.#editRow.active = this.#active.value.trim();
        this.#editRow.banned = this.#banned.value.trim();
        this.#editRow.open_notes = this.#openNotes.value.trim();
        this.#editRow.admin_notes = this.#adminNotes.value.trim();
        var full = (this.#editRow.first_name + ' ' + this.#editRow.middle_name + ' ' + this.#editRow.last_name + ' ' + this.#editRow.suffix).trim();
        this.#editRow.fullName = full.replace(/  */, ' ');
        full = (this.#editRow.address + ' ' + this.#editRow.addr2 + ' ' + this.#editRow.city + ' ' +
            this.#editRow.state + ' ' + this.#editRow.zip + ' ' + this.#editRow.country).trim();
        this.#editRow.fullAddr = full.replace(/  */, ' ');

        var index = this.#editRow.id;
        this.#findTable.getRow(index).update(this.#editRow);

        this.clearForm();
        this.#editModal.hide();
        if (data['success']) {
            show_message(data['success'], 'success');
        }
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
        this.#managesName.innerHTML = '';
        this.#managesId.value = '';
        this.#addPersonBtn.disabled = true;
        clear_message('find_edit_message');
        clear_message();
        clearError();
    }

    // on close of the pane, clean up the items
    close(clearFind = true) {
        this.clearForm();
        if (this.#findTable != null && clearFind) {
            this.#findTable.destroy();
            this.#findTable = null;
        }
        if (this.#managerLookupTable != null) {
            this.#managerLookupTable.destroy();
            this.#managerLookupTable = null;
        }
        if (this.#managesLookupTable != null) {
            this.#managesLookupTable.destroy();
            this.#managesLookupTable = null;
        }
        this.#findPattern.removeEventListener('keyup', findKeyUpListener);
        if (this.#newManagerLookup != null)
            this.#newManagerLookup.removeEventListener('keyup', managerLookupListener);
        if (this.#newManagesLookup != null)
            this.#newManagesLookup.removeEventListener('keyup', managesLookupListener);
    };
}

function findKeyUpListener(e) {
    if (e.code === 'Enter')
        findPerson.find();
}

function managerLookupListener(e) {
    if (e.code === 'Enter')
        findPerson.lookupManager();
}

function managesLookupListener(e) {
    if (e.code === 'Enter')
        findPerson.lookupManages();
}

function add_editPerson() {
    findPerson.directEdit();
}