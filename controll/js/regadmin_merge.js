//import { TabulatorFull as Tabulator } from 'tabulator-tables';
// merge class - all merge functions
class mergesetup {
    #message_div = null;
    #merge_pane = null;
    #remainPid = null;
    #mergePid = null;
    #find_candidate_btn = null;
    #check_merge_btn = null;
    #mergeCandidatesDiv = null;
    #mergeCandidatesTable = null;
    #find_modal = null;
    #mergeTitle = null;
    #mergeLookupName = null;
    #findType = null;
    #find_result_table = null;
    #find_pattern_field = null;

    // merge check modal
    #mergeCheckModal = null;
    #remainPerson = null;
    #mergePerson = null;
    #editMatchTitle = null;

    // matched person display fields
    #mergeId = null;
    #mergeName = null;
    #mergeLegal = null;
    #mergePronouns = null;
    #mergeBadge = null;
    #mergeAddress = null;
    #mergeEmail = null;
    #mergeAge = null;
    #mergePhone = null;
    #mergePolicies = null;
    #mergeFlags = null;
    #mergeManager = null;
    #mergePersonPolicies = null;
    // candidate (new) person display fields
    #remainId = null;
    #remainName = null;
    #remainLegal = null;
    #remainPronouns = null;
    #remainBadge = null;
    #remainAddress = null;
    #remainEmail = null;
    #remainAge = null;
    #remainPhone = null;
    #remainPolicies = null;
    #remainFlags = null;
    #remainManager = null;
    #remainPersonPolicies = null;
    // editing fields
    #firstName = null;
    #middleName = null;
    #lastName = null;
    #suffix = null;
    #legalName = null;
    #pronouns = null;
    #badgeName = null;
    #badgeNameL2 = null;
    #address = null;
    #addr2 = null;
    #city = null;
    #state = null;
    #zip = null;
    #country = null;
    #age = null;
    #emailAddr = null;
    #phone = null;
    #policiesDiv = null;
    #managerDiv = null;
    #active = null;
    #banned = null;
    

    // globals before open
    constructor() {
        this.#message_div = document.getElementById('test');
        this.#merge_pane = document.getElementById('merge-pane');
        // set listen to CR on find name field
        this.#find_pattern_field = document.getElementById("merge_name_search");
        this.#find_pattern_field.addEventListener('keyup', (e)=> { if (e.code === 'Enter') merge_find('search'); });
        // merge check modal
        let id = document.getElementById('merge-edit');
        if (id) {
            this.#mergeCheckModal = new bootstrap.Modal(id, { focus: true, backdrop: 'static' });
            this.#editMatchTitle = document.getElementById('mergeMatchTitle');
            // matched person display fields
            this.#mergeId = document.getElementById('matchID');
            this.#mergeName = document.getElementById('matchName');
            this.#mergeLegal = document.getElementById('matchLegal');
            this.#mergePronouns = document.getElementById('matchPronouns');
            this.#mergeBadge = document.getElementById('matchBadge');
            this.#mergeAddress = document.getElementById('matchAddress');
            this.#mergeEmail = document.getElementById('matchEmail');
            this.#mergeAge = document.getElementById('matchAge');
            this.#mergePhone = document.getElementById('matchPhone');
            this.#mergePolicies = document.getElementById('matchPolicies');
            this.#mergeFlags = document.getElementById('matchFlags');
            this.#mergeManager = document.getElementById('matchManager');
            // candidate (new) person display fields
            this.#remainId = document.getElementById('newID');
            this.#remainName = document.getElementById('newName');
            this.#remainLegal = document.getElementById('newLegal');
            this.#remainPronouns = document.getElementById('newPronouns');
            this.#remainBadge = document.getElementById('newBadge');
            this.#remainAddress = document.getElementById('newAddress');
            this.#remainEmail = document.getElementById('newEmail');
            this.#remainAge = document.getElementById('newAge');
            this.#remainPhone = document.getElementById('newPhone');
            this.#remainPolicies = document.getElementById('newPolicies');
            this.#remainFlags = document.getElementById('newFlags');
            this.#remainManager = document.getElementById('newManager');
            // editing fields
            this.#firstName = document.getElementById('firstName');
            this.#middleName = document.getElementById('middleName');
            this.#lastName = document.getElementById('lastName');
            this.#suffix = document.getElementById('suffix');
            this.#legalName = document.getElementById('legalName');
            this.#pronouns = document.getElementById('pronouns');
            this.#badgeName = document.getElementById('badgeName');
            this.#badgeNameL2 = document.getElementById('badgeNameL2');
            this.#address = document.getElementById('address');
            this.#addr2 = document.getElementById('addr2');
            this.#city = document.getElementById('city');
            this.#state = document.getElementById('state');
            this.#zip = document.getElementById('zip');
            this.#country = document.getElementById('country');
            this.#emailAddr = document.getElementById('emailAddr');
            this.#age = document.getElementById('age');
            this.#phone = document.getElementById('phone');
            this.#policiesDiv = document.getElementById('policiesDiv');
            this.#managerDiv = document.getElementById('managerDiv');
            this.#active = document.getElementById('active');
            this.#banned = document.getElementById('banned');
        }
    };

    // return the merge perid field
    getMergePid() {
        if (this.#mergePid)
            return this.#mergePid.value;
        return null;
    }

    // return the remain pid field
    getRemainPid() {
        if (this.#remainPid)
            return this.#remainPid.value;
        return null;
    }

    // called on open of the merge window
    open() {
        let html = `<h4><strong>Merge People:</strong></h4>
<div class="container-fluid">
    <div class="row">
        <div class="col-sm-2 p-2"><label for="remainPid">Perid to remain:</label></div>
        <div class="col-sm-2 p-2"><input  class='form-control-sm' type="number" id="remainPid" name="remainPid" placeholder='perid to remain' onchange="merge.btnctl()"></div>
        <div class="col-sm-4 p-2"><button id="remain_find_merge" type="button" class="btn btn-primary btn-sm" onclick="merge.findPerson('remain'); return false;">Find 'To Remain' Person</button></div>
    </div>
    <div class="row">
        <div class="col-sm-2 p-2"><label for="mergePid">Perid to merge in remain:</label></div>
        <div class="col-sm-2 p-2"><input  class='form-control-sm' type="number" id="mergePid" name="mergePid" placeholder='perid to merge' onchange="merge.btnctl()"></div>
        <div class="col-sm-4 p-2"><button id="merge_find_merge" type="button" class="btn btn-primary btn-sm" onclick="merge.findPerson('merge'); return false;">Find 'To Merge' Person</button></div>
    </div>
    <div class="row mt-2">
        <div class="col-sm-auto p-2">
            <button id="find-candidate" type="button" class="btn btn-primary btn-sm" onclick="merge.findCandidates(); return false;" disabled>Find Candidates</button>
            <button id="check-merge" type="button" class="btn btn-primary btn-sm" onclick="merge.checkMerge(); return false;" disabled>Check Merge</button>
        </div>
    </div>
    <div class="row mt-4">
        <div class="col-sm-12 p-2" id="mergeCandidates"></div>
    </div>
</div>
`;
        this.#merge_pane.innerHTML = html;
        this.#remainPid = document.getElementById('remainPid');
        this.#mergePid = document.getElementById('mergePid');
        this.#find_candidate_btn = document.getElementById('find-candidate');
        this.#check_merge_btn = document.getElementById('check-merge');
        this.#mergeCandidatesDiv = document.getElementById('mergeCandidates');
        if (this.#find_modal == null) { // only need to call on first open.
            let id = document.getElementById('merge-lookup');
            if (id != null) {
                this.#find_modal = new bootstrap.Modal(id, {focus: true, backdrop: 'static'});
                this.#mergeTitle = document.getElementById('mergeTitle');
                this.#mergeLookupName = document.getElementById('mergeLookupName');
                id.addEventListener('shown.bs.modal', () => {
                    this.#find_pattern_field.focus()
                })
            }
        }
    }

    // on close of the pane, clean up the items
    close() {
        if (this.#mergeCandidatesTable) {
            this.#mergeCandidatesTable.destroy();
            this.#mergeCandidatesTable = null;
        }
        if (this.#find_result_table) {
            this.#find_result_table.destroy();
            this.#find_result_table = null;
        }
        this.#merge_pane.innerHTML = '';
        this.#findType = null;
    };

    // manage which buttons need to be active
    //  find candidates: if there is a remain PID
    //  check_merge if both are filled in
    btnctl() {
        let remainPID = this.getRemainPid();
        this.#find_candidate_btn.disabled =  !(remainPID > 0);

        let mergePID = this.getMergePid();
        this.#check_merge_btn.disabled = ! (remainPID > 0 && mergePID > 0);
    }

    // find the candidates to merge into this Remain PID
    findCandidates(data = null) {
        let clear_error = true;
        if (data) {
            if (data.error) {
                show_message(data.error, 'error');
                return;
            } else if (data.success) {
                show_message(data.success + ': ' + data.status, 'success');
                clear_error = false;
            }
        }
        let remainPID = this.#remainPid.value;
        if (remainPID == null || remainPID == '' || remainPID <= 0)
            return; // no values to find

        this.#mergePid.value = "";
        this.btnctl();
        if (this.#mergeCandidatesTable) {
            this.#mergeCandidatesTable.destroy();
            this.#mergeCandidatesTable = null;
        }
        if (clear_error) {
            clearError();
            clear_message();
        }
        let script = "scripts/mergeFindCandidates.php";
        let postdata = {
            remain: this.#remainPid.value,
            matchCount: 5,
        }
        $.ajax({
            url: script,
            method: 'POST',
            data: postdata,
            success: function (data, textStatus, jhXHR) {
                checkRefresh(data);
                merge.drawCandidates(data, textStatus, jhXHR);
            },
            error: function (jqXHR, textStatus, errorThrown) {
                showError("ERROR in " + script + ": " + textStatus, jqXHR);
                return false;
            }
        });
    }

    // for tabulator, add buttons to have a candidate become remain or merge perid
    addActionButtons(cell, formatterParams, onRendered) {
        let btns = "";
        let data = cell.getData();
        let index = cell.getRow().getIndex();
        let thisItem = data.id;

        btns += '<button class="btn btn-primary" style = "--bs-btn-padding-y: .0rem; --bs-btn-padding-x: .3rem; --bs-btn-font-size: .75rem;" onclick="merge.makeRemain(' + thisItem + ')">Make Remain</button>';
        btns += '<button class="btn btn-secondary" style = "--bs-btn-padding-y: .0rem; --bs-btn-padding-x: .3rem; --bs-btn-font-size: .75rem;" onclick="merge.makeMerge(' + thisItem + ')">Make Merge</button>';

        return btns;
    }

    // draw the candidates tabulator box
    drawCandidates(data, textStatus, jhXHR) {
        console.log(data);

        if (data.error) {
            showError(data.error, textStatus, jhXHR);
            return;
        }
        let candidatekey = Object.keys(data.values)[0];
        let candidates = data.values[candidatekey];

        this.#mergeCandidatesTable = new Tabulator('#mergeCandidates', {
            maxHeight: "600px",
            movableRows: false,
            history: false,
            data: candidates,
            layout: "fitDataTable",
            columns: [
                { title: "Actions", headerFilter: false, headerSort: false, formatter: merge.addActionButtons,},
                { title: "ID", field: "id", headerSort: true, },
                { title: "Last Name", field: "last_name", headerSort: true, },
                { title: "First Name", field: "first_name", headerSort: true, },
                { title: "Middle Name", field: "Middle_name", headerSort: true, },
                { title: "Suffix", field: "Suffix", headerSort: true, },
                { title: "Badge Name", field: "badgename", headerSort: true, formatter: 'html', },
                { title: "Email Address", field: "email_addr", headerSort: true, },
                { title: "Address", field: "address", headerSort: true, },
                { title: "Address 2", field: "addr_2", headerSort: true, },
                { title: "City", field: "city", headerSort: true, },
                { title: "State/Prov", field: "state", headerSort: true, },
                { title: "Zip/PC", field: "zip", headerSort: true, },
                { title: "Country", field: "Country", headerSort: true, },

            ]
        });
    }

    // update mergePID with selected value (on make Merge button click)
    makeMerge(pid) {
        this.#mergePid.value = pid;
        this.btnctl();
        clear_message();
    }

    // update remainPID with selected value (on make Remain button click)
    makeRemain(pid) {
        this.#remainPid.value = pid;
        this.btnctl();
        clear_message();
    }

    // retrieve the values to display to confirm the merge before executing it
    checkMerge() {
        let mergePid = this.getMergePid();
        let remainPid = this.getRemainPid();

        if (!(mergePid > 0 && remainPid > 0))
            return;

        if (this.#mergeCandidatesTable) {
            this.#mergeCandidatesTable.destroy();
            this.#mergeCandidatesTable = null;
        }

        clearError();
        clear_message();
        let script = "scripts/mergeCheckCandidates.php";
        let data = {
            merge: mergePid,
            remain: remainPid,
        }
        $.ajax({
            url: script,
            method: 'POST',
            data: data,
            success: function (data, textStatus, jhXHR) {
                checkRefresh(data);
                merge.drawCheck(data, textStatus, jhXHR);
            },
            error: function (jqXHR, textStatus, errorThrown) {
                showError("ERROR in " + script + ": " + textStatus, jqXHR);
                return false;
            }
        });
    }

    // display results of check merge ajax call
    drawCheck(data, textStatus, jhXHR) {
        //console.log(data);

        if (data.error) {
            show_message(data.error, 'error');
            return;
        }

        this.#mergePerson = data.values.merge;
        this.#remainPerson = data.values.remain;
        this.clearEditBlock();
        // fill merge modal
        this.#editMatchTitle.innerHTML = '<b>Merging</b> ' + this.#mergePerson.fullName + ' <b>into</b> ' + this.#remainPerson.fullName;
        this.#mergeId.innerHTML = id;
        this.#mergeName.innerHTML = this.#mergePerson.fullName;
        this.#mergeLegal.innerHTML = this.#mergePerson.legalName;
        this.#mergePronouns.innerHTML = this.#mergePerson.pronouns;
        this.#mergeBadge.innerHTML = this.#mergePerson.badgeNameDef;
        this.#mergeAddress.innerHTML = this.#mergePerson.fullAddr;
        this.#mergeEmail.innerHTML = this.#mergePerson.email_addr;
        this.#mergeAge.innerHTML = this.#mergePerson.currentAgeType;
        this.#mergePhone.innerHTML = this.#mergePerson.phone;
        this.#mergeFlags.innerHTML = 'Active: ' + this.#mergePerson.active + ', Banned: ' + this.#mergePerson.banned;
        if (this.#mergePerson.managedBy) {
            this.#mergeManager.innerHTML = this.#mergePerson.manager + ' (' + this.#mergePerson.managedBy + ')';
        } else {
            this.#mergeManager.innerHTML = '<i>Not Managed</i>';
        }
        let html = '';
        let mpol = this.#mergePerson['policies'];
        for (policy in mpol) {
            html += policy + ': ' + mpol[policy] + "<br/>";
        }
        this.#mergePolicies.innerHTML = html;

        // now populate the match candidate fields
        this.#remainId.innerHTML = this.#remainPerson.id;
        this.#remainName.innerHTML = this.#remainPerson.fullName;
        this.#remainLegal.innerHTML = this.#remainPerson.legalName;
        this.#remainPronouns.innerHTML = this.#remainPerson.pronouns;
        this.#remainBadge.innerHTML = this.#remainPerson.badgeNameDef;
        this.#remainAddress.innerHTML = this.#remainPerson.fullAddr;
        this.#remainEmail.innerHTML = this.#remainPerson.email_addr;
        this.#remainAge.innerHTML = this.#remainPerson.currentAgeType;
        this.#remainPhone.innerHTML = this.#remainPerson.phone;
        this.#remainFlags.innerHTML = 'Active: ' + this.#remainPerson.active + ', Banned: ' + this.#remainPerson.banned;
        if (this.#remainPerson.managedBy) {
            this.#remainManager.innerHTML = this.#remainPerson.manager + ' (' + this.#remainPerson.managedBy + ')';
        } else {
            this.#remainManager.innerHTML = '<i>Not Managed</i>';
        }
        html = '';
        mpol = this.#remainPerson['policies'];
        for (policy in mpol) {
            html += policy + ': ' + mpol[policy] + "<br/>";
        }
        this.#remainPolicies.innerHTML = html;

        // now populate the New/Edited Values fields
        this.#firstName.value = this.#remainPerson.first_name;
        this.#middleName.value = this.#remainPerson.middle_name;
        this.#lastName.value = this.#remainPerson.last_name;
        this.#suffix.value = this.#remainPerson.suffix;
        this.#legalName.value = this.#remainPerson.legalName;
        this.#pronouns.value = this.#remainPerson.pronouns;
        this.#badgeName.value = this.#remainPerson.badge_name;
        this.#badgeNameL2.value = this.#remainPerson.badgeNameL2;
        this.#address.value = this.#remainPerson.address;
        this.#addr2.value = this.#remainPerson.addr_2;
        this.#city.value = this.#remainPerson.city;
        this.#state.value = this.#remainPerson.state;
        this.#zip.value = this.#remainPerson.zip;
        this.#country.value = this.#remainPerson.country;
        this.#emailAddr.value = this.#remainPerson.email_addr;
        this.#age.value = this.#remainPerson.currentAgeType;
        this.#phone.value = this.#remainPerson.phone;
        this.#active.value = this.#remainPerson.active == 'N' ? 'N' : 'Y';  // default to Y
        this.#banned.value = this.#remainPerson.banned == 'Y' ? 'Y' : 'N';  // default to N
        let p = this.#remainPerson['policies'];
        for (let pol in policies) {
            let polName = policies[pol].policy;
            let pname = 'p_' + polName;
            if (p[polName])
                document.getElementById(pname).checked  = p[polName] == 'Y';
            else
                document.getElementById(pname).checked = policies[pol].defaultValue == 'Y';
        }
        // now build the manager div
        this.#managerDiv.innerHTML = this.drawManager('remain');

        // now set the colors of what's different
        let diffcolor = 'yellow';

        this.#mergeName.style.backgroundColor = this.#remainPerson.fullName != this.#mergePerson.fullName ? diffcolor : '';
        this.#mergeLegal.style.backgroundColor = this.#remainPerson.legalName != this.#mergePerson.legalName ? diffcolor : '';
        this.#mergePronouns.style.backgroundColor = this.#remainPerson.pronouns != this.#mergePerson.pronouns ? diffcolor : '';
        this.#mergeBadge.style.backgroundColor = this.#remainPerson.badgeName != this.#mergePerson.badgeName ? diffcolor : '';
        this.#mergeAddress.style.backgroundColor = this.#remainPerson.fullAddr != this.#mergePerson.fullAddr ? diffcolor : '';
        this.#mergeEmail.style.backgroundColor = this.#remainPerson.email_addr != this.#mergePerson.email_addr ? diffcolor : '';
        this.#mergeAge.style.backgroundColor = this.#remainPerson.currentAgeType != this.#mergePerson.currentAgeType ? diffcolor : '';
        this.#mergePhone.style.backgroundColor = this.#remainPerson.phone != this.#mergePerson.phone ? diffcolor : '';
        this.#mergePolicies.style.backgroundColor = this.#remainPerson.policies != this.#mergePerson.policies ? diffcolor : '';
        this.#mergeFlags.style.backgroundColor = this.#remainPerson.flags != this.#mergePerson.flags ? diffcolor : '';
        this.#mergeManager.style.backgroundColor = this.#remainPerson.manager != this.#mergePerson.manager ? diffcolor : '';

        //this.#updateExisting.disabled = disableUpdateExisting;
        //this.#createNew.disabled = false;

        this.#mergeCheckModal.show();
    }

    // draw the manager central editblock from the match and new person
    drawManager(direction) {
        let manager = '<i>Not Manged</i>';
        if (direction == 'remain' && this.#remainPerson.managedBy)
            manager = this.#remainPerson.manager + ' (' + this.#remainPerson.manager + ')';
        if (direction == 'merge')
            manager = this.#mergePerson.manager + ' (' + this.#mergePerson.manager + ')';

        let nManagerId = this.#mergePerson.managedBy;
        let pManagerId = this.#remainPerson.managedBy;

        // now build the manager div
        let html = "Manager: <span id='manager' name='manager'>" + manager + "<br/>\n" +
            "<select name='managerId' id='managerId'>\n";

        if (nManagerId == null && pManagerId == null) {
            html += "<option value='' selected>No Manger Assigned</option>\n";
        } else {
            if (nManagerId == pManagerId)
                html += "<option value='$pManagerId' selected>Accept Manager (Matches)</option>\n";
            if (pManagerId || direction == 'remain')
                html += "<option value='$pManagerId'" + (nManagerId != pManagerId && direction == 'remain' ? ' selected' : '') + ">" +
                    "Use Remain Person Manager</option>\n";
            if (nManagerId || direction == 'merge')
                html += "<option value='$pManagerId'" + (nManagerId != pManagerId && direction == 'merge' ? ' selected' : '') + ">" +
                    "Use Merge Person Manager</option>\n";
            if (nManagerId != null || pManagerId != null)
                html += "<option value=''>Remove Manager</option>\n";
        }
        html += "</select>\n";
        return html;
    }

    // reset the edit block for the next merge
    clearEditBlock() {
        this.#mergeId.innerHTML = '';
        this.#mergeName.innerHTML = '';
        this.#mergeLegal.innerHTML = '';
        this.#mergePronouns.innerHTML = '';
        this.#mergeBadge.innerHTML = '';
        this.#mergeAddress.innerHTML = '';
        this.#mergeEmail.innerHTML = '';
        this.#mergeAge.innerHTML = '';
        this.#mergePhone.innerHTML = '';
        this.#mergePolicies.innerHTML = '';
        this.#mergeFlags.innerHTML = '';
        this.#mergeManager.innerHTML = '';
        // clear the colors as well
        this.#mergeName.style.backgroundColor = '';
        this.#mergeLegal.style.backgroundColor = '';
        this.#mergePronouns.style.backgroundColor = '';
        this.#mergeBadge.style.backgroundColor = '';
        this.#mergeAddress.style.backgroundColor = '';
        this.#mergeEmail.style.backgroundColor = '';
        this.#mergeAge.style.backgroundColor = '';
        this.#mergePhone.style.backgroundColor = '';
        this.#mergePolicies.style.backgroundColor = '';
        this.#mergeFlags.style.backgroundColor = '';
        this.#mergeManager.style.backgroundColor = '';
       
        this.#remainId.innerHTML = '';
        this.#remainName.innerHTML = '';
        this.#remainLegal.innerHTML = '';
        this.#remainPronouns.innerHTML = '';
        this.#remainBadge.innerHTML = '';
        this.#remainAddress.innerHTML = '';
        this.#remainEmail.innerHTML = '';
        this.#remainAge.value = '';
        this.#remainPhone.innerHTML = '';
        this.#remainPolicies.innerHTML = '';
        this.#remainFlags.innerHTML = '';
        this.#remainManager.innerHTML = '';
        // clear the colors as well
        this.#remainName.style.backgroundColor = '';
        this.#remainLegal.style.backgroundColor = '';
        this.#remainPronouns.style.backgroundColor = '';
        this.#remainBadge.style.backgroundColor = '';
        this.#remainAddress.style.backgroundColor = '';
        this.#remainEmail.style.backgroundColor = '';
        this.#remainAge.style.backgroundColor = '';
        this.#remainPhone.style.backgroundColor = '';
        this.#remainPolicies.style.backgroundColor = '';
        this.#remainFlags.style.backgroundColor = '';
        this.#remainManager.style.backgroundColor = ''
    }

    // copy a value from the match or new to the edit section
    copy(source) {
        let policy = ''
        let mpol = null;
        clear_message('result_message_candidate');

        if (source.startsWith('match')) {
            if (this.#mergePerson == null) {
                show_message("No matched person to copy from", 'error', 'result_message_candidate');
                return;
            }
        }

        let p = null;
        switch (source) {
            case 'matchName':
                this.#firstName.value = this.#mergePerson.first_name;
                this.#middleName.value = this.#mergePerson.middle_name;
                this.#lastName.value = this.#mergePerson.last_name;
                this.#suffix.value = this.#mergePerson.suffix;
                break;

            case 'newName':
                this.#firstName.value = this.#remainPerson.first_name;
                this.#middleName.value = this.#remainPerson.middle_name;
                this.#lastName.value = this.#remainPerson.last_name;
                this.#suffix.value = this.#remainPerson.suffix;
                break;

            case 'matchLegal':
                this.#legalName.value = this.#mergePerson.legalName;
                break;

            case 'newLegal':
                this.#legalName.value = this.#remainPerson.legalName;
                break;

            case 'matchPronouns':
                this.#pronouns.value = this.#mergePerson.pronouns;
                break;

            case 'newPronouns':
                this.#pronouns.value = this.#remainPerson.pronouns;
                break;

            case 'matchBadge':
                this.#badgeName.value = this.#mergePerson.badge_name;
                this.#badgeNameL2.value = this.#mergePerson.badgeNameL2;
                break;

            case 'newBadge':
                this.#badgeName.value = this.#remainPerson.badge_name;
                this.#badgeNameL2.value = this.#remainPerson.badgeNameL2;
                break;

            case 'matchAddress':
                this.#address.value = this.#mergePerson.address;
                this.#addr2.value = this.#mergePerson.addr_2;
                this.#city.value = this.#mergePerson.city;
                this.#state.value = this.#mergePerson.state;
                this.#zip.value = this.#mergePerson.zip;
                this.#country.value = this.#mergePerson.country;
                break;

            case 'newAddress':
                this.#address.value = this.#remainPerson.address;
                this.#addr2.value = this.#remainPerson.addr_2;
                this.#city.value = this.#remainPerson.city;
                this.#state.value = this.#remainPerson.state;
                this.#zip.value = this.#remainPerson.zip;
                this.#country.value = this.#remainPerson.country;
                break;

            case 'matchEmail':
                this.#emailAddr.value = this.#mergePerson.email_addr;
                break;

            case 'newEmail':
                this.#emailAddr.value = this.#remainPerson.email_addr;
                break;

            case 'matchAge':
                this.#age.value = this.#mergePerson.currentAgeType;
                break;

            case 'newAge':
                this.#age.value = this.#remainPerson.currentAgeType;
                break;

            case 'matchPhone':
                this.#phone.value = this.#mergePerson.phone;
                break;

            case 'newPhone':
                this.#phone.value = this.#remainPerson.phone;
                break;

            case 'newPolicies':
                p = this.#remainPerson['policies'];
                for (let pol in policies) {
                    let polName = policies[pol].policy;
                    let pname = 'p_' + polName;
                    if (p[polName])
                        document.getElementById(pname).checked  = p[polName] == 'Y';
                    else
                        document.getElementById(pname).checked = policies[pol].defaultValue == 'Y';
                }
                break;

            case 'matchPolicies':
                p = this.#mergePerson['policies'];
                for (let pol in policies) {
                    let polName = policies[pol].policy;
                    let pname = 'p_' + polName;
                    if (p[polName])
                        document.getElementById(pname).checked  = p[polName] == 'Y';
                    else
                        document.getElementById(pname).checked = policies[pol].defaultValue == 'Y';
                }
                break;

            case 'newPolicies':
                p = this.#remainPerson['policies'];
                for (let pol in policies) {
                    let polName = policies[pol].policy;
                    let pname = 'p_' + polName;
                    if (p[polName])
                        document.getElementById(pname).checked  = p[polName] == 'Y';
                    else
                        document.getElementById(pname).checked = policies[pol].defaultValue == 'Y';
                }
                break;

            case 'newManager':
                this.#managerDiv.innerHTML = this.drawManager('remain');
                break;

            case 'matchManager':
                this.#managerDiv.innerHTML = this.drawManager('merge');
                break;

            case 'newFlags':
                this.#active.value = this.#remainPerson.active;
                this.#banned.value = this.#remainPerson.banned;
                break;

            case 'matchFlags':
                this.#active.value = this.#mergePerson.active;
                this.#banned.value = this.#mergePerson.banned;
                break;

            case 'matchAll':
                this.#firstName.value = this.#mergePerson.first_name;
                this.#middleName.value = this.#mergePerson.middle_name;
                this.#lastName.value = this.#mergePerson.last_name;
                this.#suffix.value = this.#mergePerson.suffix;
                this.#legalName.value = this.#mergePerson.legalName;
                this.#pronouns.value = this.#mergePerson.pronouns;
                this.#badgeName.value = this.#mergePerson.badge_name;
                this.#badgeNameL2.value = this.#mergePerson.badgeNameL2;
                this.#address.value = this.#mergePerson.address;
                this.#addr2.value = this.#mergePerson.addr_2;
                this.#city.value = this.#mergePerson.city;
                this.#state.value = this.#mergePerson.state;
                this.#zip.value = this.#mergePerson.zip;
                this.#country.value = this.#mergePerson.country;
                this.#emailAddr.value = this.#mergePerson.email_addr;
                this.#age.value = this.#mergePerson.currentAgeType;
                this.#phone.value = this.#mergePerson.phone;
                p = this.#mergePerson['policies'];
                for (let pol in policies) {
                    let polName = policies[pol].policy;
                    let pname = 'p_' + polName;
                    if (p[polName])
                        document.getElementById(pname).checked  = p[polName] == 'Y';
                    else
                        document.getElementById(pname).checked = policies[pol].defaultValue == 'Y';
                }
                this.#managerDiv.innerHTML = this.drawManager('merge');
                this.#active.value = this.#mergePerson.active;
                this.#banned.value = this.#mergePerson.banned;
                break;

            case 'newAll':
                this.#firstName.value = this.#remainPerson.first_name;
                this.#middleName.value = this.#remainPerson.middle_name;
                this.#lastName.value = this.#remainPerson.last_name;
                this.#suffix.value = this.#remainPerson.suffix;
                this.#legalName.value = this.#remainPerson.legalName;
                this.#pronouns.value = this.#remainPerson.pronouns;
                this.#badgeName.value = this.#remainPerson.badge_name;
                this.#badgeNameL2.value = this.#remainPerson.badgeNameL2;
                this.#address.value = this.#remainPerson.address;
                this.#addr2.value = this.#remainPerson.addr_2;
                this.#city.value = this.#remainPerson.city;
                this.#state.value = this.#remainPerson.state;
                this.#zip.value = this.#remainPerson.zip;
                this.#country.value = this.#remainPerson.country;
                this.#emailAddr.value = this.#remainPerson.email_addr;
                this.#age.value = this.#remainPerson.currentAgeType;
                this.#phone.value = this.#remainPerson.phone;
                p = this.#remainPerson['policies'];
                for (let pol in policies) {
                    let polName = policies[pol].policy;
                    let pname = 'p_' + polName;
                    if (p[polName])
                        document.getElementById(pname).checked  = p[polName] == 'Y';
                    else
                        document.getElementById(pname).checked = policies[pol].defaultValue == 'Y';
                }
                this.#managerDiv.innerHTML = this.drawManager('remain');
                this.#active.value = this.#remainPerson.active;
                this.#banned.value = this.#remainPerson.banned;
                break;


            default:
                show_message("Invalid source " + source, 'warn', 'result_message_candidate');

        }
    }

    // execute the merge
    performMerge() {
        if (!(this.#mergePerson.id > 0 && this.#remainPerson.id > 0))
            return;


        let values = {
            first_name: this.#firstName.value,
            middle_name: this.#middleName.value,
            last_name: this.#lastName.value,
            suffix: this.#suffix.value,
            legalName: this.#legalName.value,
            pronouns: this.#pronouns.value,
            badge_name: this.#badgeName.value,
            badgeNameL2: this.#badgeNameL2.value,
            address: this.#address.value,
            addr_2: this.#addr2.value,
            city: this.#city.value,
            state: this.#state.value,
            zip: this.#zip.value,
            country: this.#country.value,
            email_addr: this.#emailAddr.value,
            currentAgeType: this.#age.value,
            phone: this.#phone.value,
            active: this.#active.value,
            banned: this.#banned.value,
        };
        for (let pol in policies) {
            let pname = 'p_' + policies[pol].policy;
            values[pname] = document.getElementById(pname).checked ? 'Y' : 'N';
        }
        clearError();
        clear_message();
        this.#mergeCheckModal.hide();
        let script = "scripts/mergeExecuteMerge.php";
        let data = {
            merge: this.#mergePerson.id,
            remain: this.#remainPerson.id,
            values: values,
        }

        $.ajax({
            url: script,
            method: 'POST',
            data: data,
            success: function (data, textStatus, jhXHR) {
                checkRefresh(data);
                merge.findCandidates(data);
            },
            error: function (jqXHR, textStatus, errorThrown) {
                showError("ERROR in " + script + ": " + textStatus, jqXHR);
                return false;
            }
        });
    }

    // set the modal up and display the modal to find a person by name/...
    findPerson(findType) {
        this.#find_modal.show();
        this.#findType = findType;
        if (this.#findType == 'remain') {
            this.#mergeTitle.innerHTML = 'Lookup Person to remain after Merge';
            this.#mergeLookupName.innerHTML = 'Remaining Person Name:';
        } else {
            this.#mergeTitle.innerHTML = 'Lookup Person to Merge';
            this.#mergeLookupName.innerHTML = 'Person Name to Merge:';
        }
    }

    // get the list of people for the match
    merge_find() {
        if (this.#findType == null | this.#findType == '')
            return;

        clear_message('result_message_merge');
        let name_search = document.getElementById('merge_name_search').value.toLowerCase().trim();
        if (name_search == null || name_search == '')  {
            show_message("No search criteria specified", "warn", 'result_message_merge');
            return;
        }

        // search for matching names
        $("button[name='mergeSearch']").attr("disabled", true);
        test.innerHTML = '';
        clear_message('result_message_merge');
        if (this.#find_result_table) {
            this.#find_result_table.destroy();
            this.#find_result_table = null;
        }

        clearError();
        clear_message();
        $.ajax({
            method: "POST",
            url: "scripts/mergeFindRecord.php",
            data: { name_search: name_search, },
            success: function (data, textstatus, jqxhr) {
                $("button[name='mergeSearch']").attr("disabled", false);
                if (data.error !== undefined) {
                    show_message(data.error, 'error', 'result_message_merge');
                    return;
                }
                checkRefresh(data);
                if (data.message !== undefined) {
                    show_message(data.message, 'success', 'result_message_merge');
                }
                if (data.warn !== undefined) {
                    show_message(data.warn, 'warn', 'result_message_merge');
                }
                merge_found(data);
            },
            error: function (jqXHR, textStatus, errorThrown) {
                $("button[name='mergeSearch']").attr("disabled", false);
                showError("ERROR in mergeFindRecord: " + textStatus, jqXHR);
            }
        });
    }

    // merge_found - display a list of potential merge recipients
    merge_found(data) {
        let perinfo = data.perinfo;
        let name_search = data.name_search;
        if (perinfo.length > 0) {
            this.#find_result_table = new Tabulator('#merge_search_results', {
                maxHeight: "600px",
                data: perinfo,
                layout: "fitColumns",
                initialSort: [
                    {column: "fullName", dir: "asc"},
                ],
                columns: [
                    {width: 70, headerFilter: false, headerSort: false, formatter: addMergeIcon, formatterParams: {t: "result"},},
                    {title: "perid", field: "perid",width: 100, hozAlign: 'right' },
                    {field: "index", visible: false,},
                    {field: "regcnt", visible: false,},
                    {title: "Name", field: "fullName", width: 200, headerFilter: true, headerWordWrap: true, tooltip: build_record_hover,},
                    {field: "last_name", visible: false,},
                    {field: "first_name", visible: false,},
                    {field: "middle_name", visible: false,},
                    {field: "suffix", visible: false,},
                    {title: "Badge Name", field: "badgename", width: 200, headerFilter: true, headerWordWrap: true, tooltip: true, formatter: 'html', },
                    {title: "Zip", field: "postal_code", headerFilter: true, headerWordWrap: true, tooltip: true, maxWidth: 100, width: 100},
                    {title: "Email Address", field: "email_addr", width: 200, headerFilter: true, headerWordWrap: true, tooltip: true,},
                    {title: "Current Badges", field: "regs", headerFilter: true, headerWordWrap: true, tooltip: true,},
                    {field: "index", visible: false,},
                ],
            });
        }
    }
    
    selectPerson(perid) {
        if (this.#findType == 'merge') {
            this.#mergePid.value = perid;
        }
        if (this.#findType == 'remain') {
            this.#remainPid.value = perid;
        }

        if (this.#find_result_table) {
            this.#find_result_table.destroy();
            this.#find_result_table = null;
        }
        this.#findType = null;
        document.getElementById('merge_name_search').value = '';
        clear_message('result_message_merge');
        this.#find_modal.hide();
        this.btnctl();
        return;
    }
};

// pass to the class functions
function merge_find() {
    merge.merge_find();
}

function merge_found(data) {
    merge.merge_found(data);
}

function selectPerson(perid) {
    merge.selectPerson(perid);
}

// show the full perinfo record as a hover in the table
function build_record_hover(e, cell, onRendered) {
    let data = cell.getData();
    //console.log(data);
    let hover_text = 'Person id: ' + data.perid + '<br/>' +
        (data.first_name + ' ' + data.middle_name + ' ' + data.last_name).trim() + '<br/>' +
        data.address_1 + '<br/>';
    if (data.address_2 != '') {
        hover_text += data.address_2 + '<br/>';
    }
    hover_text += data.city + ', ' + data.state + ' ' + data.postal_code + '<br/>';
    if (data.country != '' && data.country != 'USA') {
        hover_text += data.country + '<br/>';
    }
    hover_text += 'Badge Name: ' + badgeNameDefault(data.badge_name, data.badgeNameL2, data.first_name, data.last_name) + '<br/>' +
        'Email: ' + data.email_addr + '<br/>' + 'Phone: ' + data.phone + '<br/>' +
        'Active:' + data.active + ' Contact?:' + data.contact_ok + ' Share?:' + data.share_reg_ok + '<br/>';

    return hover_text;
}

// tabulator formatter for the merge column for the find results, displays the "Select" to mark the membership merge
function addMergeIcon(cell, formatterParams, onRendered) { //plain text value
    var tid;
    var html = '';
    var banned = cell.getRow().getData().banned == 'Y';
    var regcnt = cell.getRow().getData().regcnt;
    var color = 'btn-success';
    var perid = cell.getRow().getData().perid;

    return '<button type="button" class="btn btn-sm ' + color + ' pt-0 pb-0" style="--bs-btn-font-size: 75%;" onclick="selectPerson(' + perid + ')">Select</button>';
}
