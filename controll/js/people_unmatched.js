//import { TabulatorFull as Tabulator } from 'tabulator-tables';
// policy class - all edit membership policy functions
class Unmatched {
    #messageDiv = null;
    #unmatchedPane = null;
    #unmatchedTable = null;
    #unmatchedCountSpan = null;
    #unmatchedCount = null;
    #unmatchedH1 = null;
    #unmatched = null

    #debug = 0;
    #debugVisible = false;

    // candidate modal
    #matchCandidatesModal = null;
    #candidatesTitleName = null;
    #candidates = null;
    #newperson = null;
    #newpersonTable = null;
    #candidatesName = null;
    #candidateTable = null;
    #editMatchTitle = null;
    #updateExisting = null;
    #createNew = null;
    #newpersonPolicies = null;
    #matchpeoplePolicies = null;
    
    // edit matches
    #matchPerson = null;
    // matched person display fields
    #matchId = null;
    #matchName = null;
    #matchLegal = null;
    #matchPronouns = null;
    #matchBadge = null;
    #matchAddress = null;
    #matchEmail = null;
    #matchPhone = null;
    #matchPolicies = null;
    #matchFlags = null;
    #matchManager = null;
    // candidate (new) person display fields
    #newId = null;
    #newName = null;
    #newLegal = null;
    #newPronouns = null;
    #newBadge = null;
    #newAddress = null;
    #newEmail = null;
    #newPhone = null;
    #newPolicies = null;
    #newFlags = null;
    #newManager = null;
    // editing fields
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
    #phone = null;
    #policiesDiv = null;
    #managerDiv = null;
    #active = null;
    #banned = null;

    // globals before open
    constructor(debug) {
        this.#debug = debug;
        if (this.#debug & 2) {
            this.#debugVisible = true;
        }
        this.#messageDiv = document.getElementById('message');
        this.#unmatchedPane = document.getElementById('unmatched-pane');
        this.#unmatchedH1 = document.getElementById('unmatchedH1Div');
        this.#unmatchedCountSpan = document.getElementById('unmatchedCount');

        var id = document.getElementById('match-candidates');
        if (id) {
            this.#matchCandidatesModal = new bootstrap.Modal(id, {focus: true, backdrop: 'static'});
            this.#candidatesTitleName = document.getElementById('candidatesTitleName');
            this.#candidatesName = document.getElementById('candidatesName');
            this.#editMatchTitle = document.getElementById('editMatchTitle');
            this.#updateExisting = document.getElementById('updateExisting');
            this.#createNew = document.getElementById('createNew');
            // matched person display fields
            this.#matchId = document.getElementById('matchID');
            this.#matchName = document.getElementById('matchName');
            this.#matchLegal = document.getElementById('matchLegal');
            this.#matchPronouns = document.getElementById('matchPronouns');
            this.#matchBadge = document.getElementById('matchBadge');
            this.#matchAddress = document.getElementById('matchAddress');
            this.#matchEmail = document.getElementById('matchEmail');
            this.#matchPhone = document.getElementById('matchPhone');
            this.#matchPolicies = document.getElementById('matchPolicies');
            this.#matchFlags = document.getElementById('matchFlags');
            this.#matchManager = document.getElementById('matchManager');
            // candidate (new) person display fields
            this.#newId = document.getElementById('newID');
            this.#newName = document.getElementById('newName');
            this.#newLegal = document.getElementById('newLegal');
            this.#newPronouns = document.getElementById('newPronouns');
            this.#newBadge = document.getElementById('newBadge');
            this.#newAddress = document.getElementById('newAddress');
            this.#newEmail = document.getElementById('newEmail');
            this.#newPhone = document.getElementById('newPhone');
            this.#newPolicies = document.getElementById('newPolicies');
            this.#newFlags = document.getElementById('newFlags');
            this.#newManager = document.getElementById('newManager');
            // editing fields
            this.#firstName = document.getElementById('firstName');
            this.#middleName = document.getElementById('middleName');
            this.#lastName = document.getElementById('lastName');
            this.#suffix = document.getElementById('suffix');
            this.#legalName = document.getElementById('legalName');
            this.#pronouns = document.getElementById('pronouns');
            this.#badgeName = document.getElementById('badgeName');
            this.#address = document.getElementById('address');
            this.#addr2 = document.getElementById('addr2');
            this.#city = document.getElementById('city');
            this.#state = document.getElementById('state');
            this.#zip = document.getElementById('zip');
            this.#country = document.getElementById('country');
            this.#emailAddr = document.getElementById('emailAddr');
            this.#phone = document.getElementById('phone');
            this.#policiesDiv = document.getElementById('policiesDiv');
            this.#managerDiv = document.getElementById('managerDiv');
            this.#active = document.getElementById('active');
            this.#banned = document.getElementById('banned');

        }
    };

    // called on open of the unmatched window
    open(msg = null) {
        var _this = this;
        var script = "scripts/people_getUnmatched.php";
        var postdata = {
            ajax_request_action: 'unmatched',
        };
        clear_message();
        clearError();
        $.ajax({
            url: script,
            method: 'POST',
            data: postdata,
            success: function (data, textStatus, jhXHR) {
                _this.draw(data, msg);
            },
            error: function (jqXHR, textStatus, errorThrown) {
                showError("ERROR in " + script + ": " + textStatus, jqXHR);
                return false;
            }
        });
    }

    // draw the policy edit screen
    draw(data, msg= null) {
        var _this = this;

        if (this.#unmatchedTable != null) {
            this.#unmatchedTable.destroy();
            this.#unmatchedTable = null;
        }
        if (!data['unmatched']) {
            show_message("Error loading unmatched people", 'error');
            return;
        }
        this.#unmatched = data['unmatched'];
        this.#unmatchedCount = data['numUnmatched'];
        this.#unmatchedCountSpan.innerHTML = this.#unmatchedCount;
        this.#unmatchedTable = new Tabulator('#unmatchedTable', {
            data: this.#unmatched,
            layout: "fitDataTable",
            index: "id",
            pagination: true,
            paginationAddRow:"table",
            paginationSize: 100,
            paginationSizeSelector: [10, 25, 50, 100, 250, true], //enable page size select element with these options
            columns: [
                {title: "Match", formatter: this.matchButton, headerSort: false },
                {title: "New ID", field: "id", headerWordWrap: true, width: 80, headerHozAlign:"right", hozAlign: "right", headerSort: true},
                {title: "Manages", field: "manages", width: 90, headerHozAlign:"right", hozAlign: "right", headerSort: false},
                {title: "Mgr Type", field: "managerType", headerWordWrap: true, width: 50,headerSort: false },
                {title: "Mgr Id", field: 'managerId', headerWordWrap: true, width: 120, headerHozAlign:"right", hozAlign: "right", },
                {title: "Managed By", field: "manager", headerWordWrap: true, width: 150, headerSort: true, headerFilter: true, },
                {title: "Full Name", field: "fullName", width: 300, headerSort: true, headerFilter: true, },
                {title: "Email", field: "email_addr", width: 200, headerSort: true, headerFilter: true, },
                {title: "Date Created", field: "createtime", width: 180, headerSort: true, headerFilter: true, },
                {title: "Num Regs", field: "numRegs", width: 50, headerWordWrap: true, headerHozAlign:"right", hozAlign: "right", headerSort: false},
                {title: "Registrations", field: "regs", minWidth: 400, headerWordWrap: true, headerSort: false},
                {field: "price",visible: false},
                {field: "paid", visible: false},
                {field: 'first_name', visible: false,},
                {field: 'middle_name', visible: false,},
                {field: 'last_name', visible: false,},
                {field: 'suffix', visible: false,},
                {field: 'legalName', visible: false,},
                {field: 'pronouns', visible: false,},
                {field: 'active', visible: false,},
                {field: 'banned', visible: false,},
            ],
        });

        if (msg)
            show_message(msg, 'success');
    }

    // table related functions
    // display match button unmatched new people
    matchButton(cell, formatterParams, onRendered) {
        var row = cell.getRow();
        var index = row.getIndex()
        var managerType = row.getData().managerType;
        if (managerType != 'n') {
            return '<button class="btn btn-primary" style = "--bs-btn-padding-y: .0rem; --bs-btn-padding-x: .3rem; --bs-btn-font-size: .75rem;",' +
                ' onclick="unmatchedPeople.matchPerson(' + index + ');">Match</button>';
        }
        var mgrId = row.getData().managerId
        return "Need " + mgrId;
    }
    // display select button for candidate people
    selectButton(cell, formatterParams, onRendered) {
        var row = cell.getRow();
        var index = row.getIndex()
        var personType = formatterParams.table;

        return '<button class="btn btn-primary" style = "--bs-btn-padding-y: .0rem; --bs-btn-padding-x: .3rem; --bs-btn-font-size: .75rem;",' +
                ' onclick="unmatchedPeople.selectPerson(\'' + personType + '\', ' + index + ');">Select</button>';
    }

    // ok to match a person we need to do the following
    //  1. check if their manager is not matched and deny matching them
    //      (this step is already completed ny the show match algorithm, if there isn't a match button because the manager type is 'n')
    //  2. find all possible suggestions of people that might match this person
    //  3. offer to use of those or a new person
    //  4. select action
    //      a. if new, keep association, allow editing the profile and the saving as a new person
    //      b. if existing, allow to merge change data from the two records
    //      c. if mamnaged, offer to keep/break the management association,
    //          with the option to send an email and have the user respond they want to stay associated.
    //
    // matchPerson - get the candidates to match against a new person
    matchPerson(id) {
        var _this = this;
        var script = "scripts/people_getMatchCandidates.php";
        var postdata = {
            ajax_request_action: 'match',
            newperid: id,
        };
        clear_message();
        clearError();
        $.ajax({
            url: script,
            method: 'POST',
            data: postdata,
            success: function (data, textStatus, jhXHR) {
                _this.showCandidates(data);
            },
            error: function (jqXHR, textStatus, errorThrown) {
                showError("ERROR in " + script + ": " + textStatus, jqXHR);
                show_message("ERROR in " + script + ": " + jqXHR.responseText, 'error', 'result_message_candidate');
                return false;
            }
        });
    }

    // showCandidates - open the modal to display the candidates for this match
    showCandidates(data) {
        console.log(data);
        if (data['error']) {
            show_message(data['error'], 'error');
            return;
        }
        this.#candidates = data['matches'];
        this.#matchpeoplePolicies = data['matchPolicies'];
        this.#newperson = data['newperson']
        this.#newpersonPolicies = data['npolicies'];
        var newpeople = [];
        newpeople.push(this.#newperson);
        this.#candidatesTitleName.innerHTML = this.#newperson.fullName;
        this.#candidatesName.innerHTML = this.#newperson.fullName;
        this.#newpersonTable = new Tabulator('#newpersonTable', {
            data: newpeople,
            layout: "fitDataTable",
            index: "id",
            columns: [
                {title: "Select", width: 100, formatter: this.selectButton, formatterParams: {table: 'n'}, headerSort: false },
                {title: "ID", field: "id", width: 80, headerHozAlign:"right", hozAlign: "right", headerSort: true},
                {title: "Full Name", field: "fullName", width: 300, headerSort: true, headerFilter: true, },
                {title: "Address", field: "fullAddr", width: 300, headerSort: true, headerFilter: true, },
                {title: "Badge Name", field: "badge_name", width: 150, headerFilter:true, headerSort: false},
                {title: "Manager By", field: "manager", headerWordWrap: true, width: 150, headerSort: true, headerFilter: true, },
                {title: "Email", field: "email_addr", width: 200, headerSort: true, headerFilter: true, },
                {title: "Phone", field: "phone", width: 100, headerSort: true, headerFilter: true, },
                {title: "Date Created", field: "createtime", width: 180, headerSort: true, headerFilter: true, },
                {title: "Registrations", field: "regs", width: 300, },
                {field: 'first_name', visible: false,},
                {field: 'middle_name', visible: false,},
                {field: 'last_name', visible: false,},
                {field: 'suffix', visible: false,},
                {field: 'legalName', visible: false,},
                {field: 'pronouns', visible: false,},
                {field: 'active', visible: false,},
                {field: 'banned', visible: false,},
                {field: 'managerId', visible: false,},
            ],
        });
        this.#candidateTable = new Tabulator('#candidateTable', {
            data: this.#candidates,
            layout: "fitDataTable",
            index: "id",
            pagination: true,
            paginationAddRow:"table",
            paginationSize: 10,
            paginationSizeSelector: [10, 25, 50, 100, 250, true], //enable page size select element with these options
            columns: [
                {title: "Select", width: 100, formatter: this.selectButton, formatterParams: {table: 'p'}, headerSort: false },
                {title: "ID", field: "id", width: 80, headerHozAlign:"right", hozAlign: "right", headerSort: true},
                {title: "Full Name", field: "fullName", width: 300, headerSort: true, headerFilter: true, },
                {title: "Address", field: "fullAddr", width: 300, headerSort: true, headerFilter: true, },
                {title: "Badge Name", field: "badge_name", width: 150, headerFilter:true, headerSort: false},
                {title: "Managed By", field: "manager", headerWordWrap: true, width: 150, headerSort: true, headerFilter: true, },
                {title: "Email", field: "email_addr", width: 200, headerSort: true, headerFilter: true, },
                {title: "Phone", field: "phone", width: 100, headerSort: true, headerFilter: true, },
                {title: "Date Created", field: "creation_date", width: 180, headerSort: true, headerFilter: true, },
                {title: "Registrations", field: "regs", width: 300, },
                {field: 'first_name', visible: false,},
                {field: 'middle_name', visible: false,},
                {field: 'last_name', visible: false,},
                {field: 'suffix', visible: false,},
                {field: 'legalName', visible: false,},
                {field: 'pronouns', visible: false,},
                {field: 'active', visible: false,},
                {field: 'banned', visible: false,},
                {field: 'managerId', visible: false,},
            ],
        });

        $('#editMatch').hide();
        this.#updateExisting.disabled = true;
        this.#createNew.disabled = true;
        this.#matchCandidatesModal.show();
        show_message(data['success'], 'success', 'result_message_candidate');
    }

    // selectPerson - move a person to the edit area and prepare to edit/save it
    selectPerson(type, id) {
        var html = '';
        var policy = '';
        var disableUpdateExisting = true;
        // they clicked select, if it's a new person, clear the matched person side of the page
        if (type == 'n') {
            this.clearEditBlock('c');
            this.#editMatchTitle.innerHTML = this.#newperson.fullName;
            this.#matchPerson = null;
        } else {
            // set the candidate section of the edit block to the values from the table
            disableUpdateExisting = false;
            this.#matchPerson = this.#candidateTable.getRow(id).getData();
            this.#editMatchTitle.innerHTML = this.#newperson.fullName + ' and ' + this.#matchPerson.fullName;
            this.#matchId.innerHTML = id;
            this.#matchName.innerHTML = this.#matchPerson.fullName;
            this.#matchLegal.innerHTML = this.#matchPerson.legalName;
            this.#matchPronouns.innerHTML = this.#matchPerson.pronouns;
            this.#matchBadge.innerHTML = this.#matchPerson.badge_name;
            this.#matchAddress.innerHTML = this.#matchPerson.fullAddr;
            this.#matchEmail.innerHTML = this.#matchPerson.email_addr;
            this.#matchPhone.innerHTML = this.#matchPerson.phone;
            this.#matchFlags.innerHTML = 'Active: ' + this.#matchPerson.active + ', Banned: ' + this.#matchPerson.banned;
            if (this.#matchPerson.managerId) {
                this.#matchManager.innerHTML = this.#matchPerson.manager + ' (' + this.#matchPerson.managerId + ')';
            } else {
                this.#matchManager.innerHTML = '<i>Not Managed</i>';
            }
            html = '';
            var mpol = this.#matchpeoplePolicies[id];
            for (policy in mpol) {
                html += policy + ': ' + mpol[policy] + "<br/>";
            }
            this.#matchPolicies.innerHTML = html;
        }

        // now populate the match candidate fields
        this.#newId.innerHTML = this.#newperson.id;
        this.#newName.innerHTML = this.#newperson.fullName;
        this.#newLegal.innerHTML = this.#newperson.legalName;
        this.#newPronouns.innerHTML = this.#newperson.pronouns;
        this.#newBadge.innerHTML = this.#newperson.badge_name;
        this.#newAddress.innerHTML = this.#newperson.fullAddr;
        this.#newEmail.innerHTML = this.#newperson.email_addr;
        this.#newPhone.innerHTML = this.#newperson.phone;
        this.#newFlags.innerHTML = 'Active: ' + this.#newperson.active + ', Banned: ' + this.#newperson.banned;
        if (this.#newperson.managerId) {
            this.#newManager.innerHTML = this.#newperson.manager + ' (' + this.#newperson.managerId + ')';
        } else {
            this.#newManager.innerHTML = '<i>Not Managed</i>';
        }
        html = '';
        for (policy in this.#newpersonPolicies) {
            html += policy + ': ' + this.#newpersonPolicies[policy] + "<br/>";
        }
        this.#newPolicies.innerHTML = html;

        // now populate the New/Edited Values fields
        this.#firstName.value = this.#newperson.first_name;
        this.#middleName.value = this.#newperson.middle_name;
        this.#lastName.value = this.#newperson.last_name;
        this.#suffix.value = this.#newperson.suffix;
        this.#legalName.value = this.#newperson.legalName;
        this.#pronouns.value = this.#newperson.pronouns;
        this.#badgeName.value = this.#newperson.badge_name;
        this.#address.value = this.#newperson.address;
        this.#addr2.value = this.#newperson.addr_2;
        this.#city.value = this.#newperson.city;
        this.#state.value = this.#newperson.state;
        this.#zip.value = this.#newperson.zip;
        this.#country.value = this.#newperson.country;
        this.#emailAddr.value = this.#newperson.email_addr;
        this.#phone.value = this.#newperson.phone;
        this.#active.value = this.#newperson.active == 'N' ? 'N' : 'Y';  // default to Y
        this.#banned.value = this.#newperson.banned == 'Y' ? 'Y' : 'N';  // default to N
        for (policy in this.#newpersonPolicies) {
            document.getElementById('p_' + policy).checked = this.#newpersonPolicies[policy] == 'Y';
        }
               // now build the manager div
        this.#managerDiv.innerHTML = this.drawManager(type, this.#newperson.manager, this.#newperson.managerId);

        // now set the colors of what's different
        var diffcolor = 'yellow';
        if (type != 'n') {
            this.#matchName.style.backgroundColor = this.#newperson.fullName != this.#matchPerson.fullName ? diffcolor : '';
            this.#matchLegal.style.backgroundColor = this.#newperson.legalName != this.#matchPerson.legalName ? diffcolor : '';
            this.#matchPronouns.style.backgroundColor = this.#newperson.pronouns != this.#matchPerson.pronouns ? diffcolor : '';
            this.#matchBadge.style.backgroundColor = this.#newperson.badge_name != this.#matchPerson.badge_name ? diffcolor : '';
            this.#matchAddress.style.backgroundColor = this.#newperson.fullAddr != this.#matchPerson.fullAddr ? diffcolor : '';
            this.#matchEmail.style.backgroundColor = this.#newperson.email_addr != this.#matchPerson.email_addr ? diffcolor : '';
            this.#matchPhone.style.backgroundColor = this.#newperson.phone != this.#matchPerson.phone ? diffcolor : '';
            this.#matchPolicies.style.backgroundColor = this.#newperson.policies != this.#matchPerson.policies ? diffcolor : '';
            this.#matchFlags.style.backgroundColor = this.#newperson.flags != this.#matchPerson.flags ? diffcolor : '';
            this.#matchManager.style.backgroundColor = this.#newperson.manager != this.#matchPerson.manager ? diffcolor : '';
        }

        this.#updateExisting.disabled = disableUpdateExisting;
        this.#createNew.disabled = false;
        $('#editMatch').show();
    }

    // update the database with the new match
    saveMatch(type) {
        // get all of the edited values, the existing id and the new id
        var postdata = {
            type: type,
            newperid: this.#newperson.id,
            perid: (type == 'e' && this.#matchPerson) ? this.#matchPerson.id : null,
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
            active: this.#active.value,
            banned: this.#banned.value,
            managerAction: document.getElementById('managerSelect').value,
            managerId: document.getElementById('managerId').value,
        };
        // now add the policies to the list
        for (var policy in this.#newpersonPolicies) {
            postdata['p_' + policy] = document.getElementById('p_' + policy).checked ? 'Y' : 'N';
        }

        var script = 'scripts/people_updateMatch.php'
        clear_message('result_message_candidate');
        clear_message();
        clearError();
        var _this = this;
        $.ajax({
            url: script,
            method: 'POST',
            data: postdata,
            success: function (data, textStatus, jhXHR) {
                _this.updateSuccess(data);
            },
            error: function (jqXHR, textStatus, errorThrown) {
                showError("ERROR in " + script + ": " + textStatus, jqXHR);
                show_message("ERROR in " + script + ": " + jqXHR.responseText, 'error', 'result_message_candidate');
                return false;
            }
        });
    }

    // successful update
    updateSuccess(data) {
        if (data['error']) {
            show_message(data['error'], 'error', 'result_message_candidate');
            return;
        }
        if (data['warn']) {
            show_message(data['warn'], 'warn', 'result_message_candidate');
            return;
        }

        this.#matchCandidatesModal.hide();
        this.clearEditBlock('a');
        this.open(data['success']);
    }

    // draw the manager central editblock from the match and new person
    drawManager(type, manager, managerId) {
        var manager = (managerId == undefined || managerId == null) ? '<i>Not Manged</i>' : (manager + ' (' + managerId + ')');

        var nManagerId = null;
        var pManagerId = null;
        if (this.#newperson) {
            if (this.#newperson.managerId) {
                if (this.#newperson.managerId != undefined && this.#newperson.managerId != null && this.#newperson.managerId != '')
                    nManagerId = this.#newperson.managerId;
            }
        }

        if (this.#matchPerson) {
            if (this.#matchPerson.managerId) {
                if (this.#matchPerson.managerId != undefined && this.#matchPerson.managerId != null && this.#matchPerson.managerId != '')
                    pManagerId = this.#matchPerson.managerId;
            }
        }
        // now build the manager div
        var html = "Manager: <span id='manager' name='manager'>" + manager + "<br/>\n" +
            "<select name='managerSelect' id='managerSelect'>\n";

        if (type == 'n') {
            html += "<option value='ACC' selected>New Person - No Change</option>\n" +
                "<option value='REM'>New Person - Remove Manager</option>\n";
        } else if (nManagerId == null && pManagerId == null) {
            html += "<option value='ACC' selected>No Manger Assigned</option>\n";
        } else {
            html += "<option value='ACC'" + (nManagerId == pManagerId ? ' selected' : '') + ">Accept Manager Shown</option>\n" +
                "<option value='REM'" + (nManagerId != pManagerId ? ' selected' : '') + ">Remove Manager</option>\n" +
                "<option value='EMAIL'>Send Email Manage Request</option>\n";
        }
        html += "</select>\n";
        if (managerId != null) {
            html += "<input type='hidden' name='managerId' id='managerId' value='" + managerId + "'/>\n";
        } else {
            html += "<input type='hidden' name='managerId' id='managerId' value=null />\n";
        }
        return html;
    }

    clearEditBlock(sections) {
        if (sections == 'c' || sections == 'a') {
            this.#matchId.innerHTML = '';
            this.#matchName.innerHTML = '';
            this.#matchLegal.innerHTML = '';
            this.#matchPronouns.innerHTML = '';
            this.#matchBadge.innerHTML = '';
            this.#matchAddress.innerHTML = '';
            this.#matchEmail.innerHTML = '';
            this.#matchPhone.innerHTML = '';
            this.#matchPolicies.innerHTML = '';
            this.#matchFlags.innerHTML = '';
            this.#matchManager.innerHTML = '';
            // clear the colors as well
            this.#matchName.style.backgroundColor = '';
            this.#matchLegal.style.backgroundColor = '';
            this.#matchPronouns.style.backgroundColor = '';
            this.#matchBadge.style.backgroundColor = '';
            this.#matchAddress.style.backgroundColor = '';
            this.#matchEmail.style.backgroundColor = '';
            this.#matchPhone.style.backgroundColor = '';
            this.#matchPolicies.style.backgroundColor = '';
            this.#matchFlags.style.backgroundColor = '';
            this.#matchManager.style.backgroundColor = '';
        }
        if (sections == 'n' || sections == 'a') {
            this.#newId.innerHTML = '';
            this.#newName.innerHTML = '';
            this.#newLegal.innerHTML = '';
            this.#newPronouns.innerHTML = '';
            this.#newBadge.innerHTML = '';
            this.#newAddress.innerHTML = '';
            this.#newEmail.innerHTML = '';
            this.#newPhone.innerHTML = '';
            this.#newPolicies.innerHTML = '';
            this.#newFlags.innerHTML = '';
            this.#newManager.innerHTML = '';
            // clear the colors as well
            this.#newName.style.backgroundColor = '';
            this.#newLegal.style.backgroundColor = '';
            this.#newPronouns.style.backgroundColor = '';
            this.#newBadge.style.backgroundColor = '';
            this.#newAddress.style.backgroundColor = '';
            this.#newEmail.style.backgroundColor = '';
            this.#newPhone.style.backgroundColor = '';
            this.#newPolicies.style.backgroundColor = '';
            this.#newFlags.style.backgroundColor = '';
            this.#newManager.style.backgroundColor = '';
        }
    }

    // copy a value from the match or new to the edit section
    copy(source) {
        var policy = ''

        switch (source) {
            case 'matchName':
                this.#firstName.value = this.#matchPerson.first_name;
                this.#middleName.value = this.#matchPerson.middle_name;
                this.#lastName.value = this.#matchPerson.last_name;
                this.#suffix.value = this.#matchPerson.suffix;
                break;

            case 'newName':
                this.#firstName.value = this.#newperson.first_name;
                this.#middleName.value = this.#newperson.middle_name;
                this.#lastName.value = this.#newperson.last_name;
                this.#suffix.value = this.#newperson.suffix;
                break;

            case 'matchLegal':
                this.#legalName.value = this.#matchPerson.legalName;
                break;

            case 'newLegal':
                this.#legalName.value = this.#newperson.legalName;
                break;

            case 'matchPronouns':
                this.#pronouns.value = this.#matchPerson.pronouns;
                break;

            case 'newPronouns':
                this.#pronouns.value = this.#newperson.pronouns;
                break;

            case 'matchBadge':
                this.#badgeName.value = this.#matchPerson.badge_name;
                break;

            case 'newBadge':
                this.#pronouns.value = this.#newperson.badge_name;
                break;

            case 'matchAddress':
                this.#address.value = this.#matchPerson.address;
                this.#addr2.value = this.#matchPerson.addr2;
                this.#city.value = this.#matchPerson.city;
                this.#state.value = this.#matchPerson.state;
                this.#zip.value = this.#matchPerson.zip;
                this.#country.value = this.#matchPerson.country;
                break;

            case 'newAddress':
                this.#address.value = this.#newperson.address;
                this.#addr2.value = this.#newperson.addr2;
                this.#city.value = this.#newperson.city;
                this.#state.value = this.#newperson.state;
                this.#zip.value = this.#newperson.zip;
                this.#country.value = this.#newperson.country;
                break;

            case 'matchEmail':
                this.#emailAddr.value = this.#matchPerson.email_addr;
                break;

            case 'newEmail':
                this.#emailAddr.value = this.#newperson.email_addr;
                break;

            case 'matchPhone':
                this.#phone.value = this.#matchPerson.phone;
                break;

            case 'newPhone':
                this.#phone.value = this.#newperson.phone;
                break;

            case 'newPolicies':
                for (policy in this.#newpersonPolicies) {
                    document.getElementById('p_' + policy).checked = this.#newpersonPolicies[policy] == 'Y';
                }
                break;

            case 'matchPolicies':
                var mpol = this.#matchpeoplePolicies[this.#matchPerson.id];
                for (policy in mpol) {
                    document.getElementById('p_' + policy).checked = mpol[policy] == 'Y';
                }
                break;

            case 'newManager':
                this.#managerDiv.innerHTML = this.drawManager('n', this.#newperson.manager, this.#newperson.managerId);
                break;

            case 'matchManager':
                this.#managerDiv.innerHTML = this.drawManager('p', this.#matchPerson.manager, this.#matchPerson.managerId);
                break;

            case 'newFlags':
                this.#active.value = this.#newperson.active;
                this.#banned.value = this.#newperson.banned;
                break;

            case 'matchFlags':
                this.#active.value = this.#matchPerson.active;
                this.#banned.value = this.#matchPerson.banned;
                break;

            default:
                show_message("Invalid source " + source, 'warn', 'result_message_candidate');

        }
    }
    // on close of the pane, clean up the items
    close() {
         if (this.#unmatchedTable != null) {
            this.#unmatchedTable.destroy();
            this.#unmatchedTable = null;
        }
    };
}