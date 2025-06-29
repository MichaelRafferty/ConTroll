// Class to standardize addition/edit of notes for reg and perinfo items
class Notes {
    #callingClass = '';

    // notes items
    #notes = null;
    #notesPerid = null;
    #notesIndex = null;
    #notesType = null;
    #notesPriorValue = null;

    #notesTitleDiv = null;
    #notesBodyDiv = null;
    #closeNotesBTN = null;
    #cancelNotesBTN = null;
    #notesReadOnly = false;

    // global items
    #conid = null;
    #conlabel = null;
    #user_id = 0;
    #manager = false;
    #baseManagerEnabled = false;

// initialization
    constructor(className, user_id, manager=false, baseManagerEnabled=false) {
        this.#callingClass = className;
        this.#user_id = user_id;
        this.#manager = manager;
        this.#baseManagerEnabled = baseManagerEnabled;

        // notes items
        var id = document.getElementById('Notes');
        if (id != null) {
            this.#notes = new bootstrap.Modal(id, {focus: true, backdrop: 'static'});
            this.#notesTitleDiv = document.getElementById('NotesTitle');
            this.#notesBodyDiv = document.getElementById('NotesBody');
            this.#closeNotesBTN = document.getElementById('close_note_button');
            this.#cancelNotesBTN = document.getElementById('cancel_note_button');
        }
    }


// display the note popup with the requested notes
    showPerinfoNotes(index, where) {
        console.log("showPerinfoNotes: not yet");
        /*
        var note = null;
        var fullName = null;
        this.#notesType = null;

        if (where == 'cart') {
            note = cart.getPerinfoNote(index);
            fullName = cart.getFullName(index);
            this.#notesType = 'PC';
        }
        if (where == 'result') {
            note = this.#result_perinfo[index].open_notes;
            fullName = this.#result_perinfo[index].fullName;
            this.#notesType = 'PR';
        }
        if (where == 'add') {
            note = this.#add_perinfo[index].open_notes
            fullName = this.#add_perinfo[index].fullName;
            this.#notesType = 'add';
        }

        if (this.#notesType == null)
            return;

        this.#notesIndex = index;

        this.#notes.show();
        document.getElementById('NotesTitle').innerHTML = "Notes for " + fullName;
        document.getElementById('NotesBody').innerHTML = note.replace(/\n/g, '<br/>');
        this.#closeNotesBTN.innerHTML = "Close";
        this.#closeNotesBTN.disabled = false;
         */
    }

// editPerinfoNotes: display in an editor the perinfo notes field
// only managers can edit the notes
    editPerinfoNotes(index, where) {
        console.log("editPerinfoNotes: not yet");
        /*
        var note = null;
        var fullName = null;

        if (!this.#manager || !baseManagerEnabled)
            return;

        this.#notesType = null;
        if (where == 'cart') {
            note = cart.getPerinfoNote(index);
            fullName = cart.getFullName(index);
            this.#notesType = 'PC';
        }
        if (where == 'result') {
            note = this.#result_perinfo[index].open_notes;
            fullName = this.#result_perinfo[index].fullName;
            this.#notesType = 'PR';
        }
        if (where == 'add') {
            note = this.#add_perinfo[index].open_notes
            fullName = this.#add_perinfo[index].fullName;
            this.#notesType = 'add';
        }
        if (this.#notesType == null)
            return;

        this.#notesIndex = index;
        this.#notesPriorValue = note;
        if (this.#notesPriorValue === null) {
            this.#notesPriorValue = '';
        }

        this.#notes.show();
        document.getElementById('NotesTitle').innerHTML = "Editing Notes for " + fullName;
        document.getElementById('NotesBody').innerHTML =
            '<textarea name="perinfoNote" class="form-control" id="perinfoNote" cols=60 wrap="soft" style="height:400px;">' +
            this.#notesPriorValue +
            "</textarea>";
        var notes_btn = document.getElementById('close_note_button');
        notes_btn.innerHTML = "Save and Close";
        notes_btn.disabled = false;
         */
    }

// fetch / display the reg notoes
    getDisplayRegNotes(rid, readOnly) {
        var _this = this;
        $.ajax({
            method: "POST",
            url: "scripts/getNotes.php",
            data: {
                rid: rid,
            },
            success: function (data, textstatus, jqxhr) {
                if (data['error'] !== undefined) {
                    show_message(data['error'], 'error');
                    return;
                }
                if (data['success'] !== undefined) {
                    show_message(data['success'], 'success');
                }
                if (data['warn'] !== undefined) {
                    show_message(data['warn'], 'warn');
                }
                _this.displayRegNotes(data, rid, readOnly);
                if (data['success'] !== undefined)
                    show_message(data.success, 'success');
            },
            error: function (jqXHR, textStatus, errorThrown) {
                showError("ERROR in getReceipt: " + textStatus, jqXHR);
            }
        });
    }

    displayRegNotes(data, rid, readOnly) {
        this.#notesType = 'CT';
        this.#notesIndex = rid;
        this.#notesPerid = data.perid;
        this.#cancelNotesBTN.hidden = readOnly;
        this.drawRegNote(data.label, '', data.fullName, data.notes, readOnly);
    }

    drawRegNote(label, newnote, fullName, existingNotes, readOnly) {
        this.#notesReadOnly = readOnly;
        var bodyHTML = `
        <div class="row mt-4">
            <div class="col-sm-1"><b>TID</b></div>
            <div class="col-sm-2"><b>Log Date</b></div>
            <div class="col-sm-1"><b>UserId</b></div>
            <div class="col-sm-8"><b>Note</b></div>
        </div>
`;
        for (var i = 0; i < existingNotes.length; i++) {
            var noteRec = existingNotes[i];
            bodyHTML += `
        <div class="row mt-2">
            <div class="col-sm-1">` + noteRec.tid + `</div>
            <div class="col-sm-2">` + noteRec.logdate + `</div>
            <div class="col-sm-1">` + noteRec.userid + `</div>
            <div class="col-sm-8">` + noteRec.notes + `</div>
        </div>
`;
        }

        if (!readOnly) {
            bodyHTML += '<br/>&nbsp;<br/>Enter/Update new note:<br/>' +
                '<input type="text" name="new_reg_note" id="new_reg_note" maxLength=64 size=60>' +
                "</div>\n";
        }
        bodyHTML += "</div>\n";
        this.#notesBodyDiv.innerHTML = bodyHTML;
        this.#notesTitleDiv.innerHTML = "<b>Registration Notes for " + fullName + '<br/>Membership: ' + label + "</b>\n";
        this.#notes.show();
        if (readOnly) {
            this.#closeNotesBTN.innerHTML = "Close";
        } else {
            if (newnote == undefined || newnote == null) {
                newnote = '';
            }
            document.getElementById('new_reg_note').value = newnote;
            this.#closeNotesBTN.innerHTML = "Save and Close";

        }
        this.#closeNotesBTN.disabled = false;
    }

// show the registration element note, anyone can add a new note, so it needs a save and close button
    showRegNote(perid, index, count) {
        var bodyHTML = '<div class="row mb-2">\n<div class="col-sm-12">\n';
        var note = cart.getRegNote(perid, index);
        var fullName = cart.getRegFullName(perid);
        ``
        var label = cart.getRegLabel(perid, index);
        var newregnote = cart.getNewRegNote(perid, index);

        this.#notesType = 'RC';
        this.#notesIndex = index;
        this.#notesPerid = perid;

        this.drawRegNote(label, newregnote, fullName, note, false);
    }

// saveNote
//  save and update the note based on type
    saveNote() {
        if (this.#notesReadOnly == false) {
            switch (this.#notesType) {
                case 'RC':
                    cart.setRegNote(this.#notesPerid, this.#notesIndex, document.getElementById("new_reg_note").value);
                    break;

                case 'PC':
                    if (this.#manager && this.#baseManagerEnabled) {
                        cart.setPersonNote(this.#notesIndex, document.getElementById("perinfoNote").value);
                    }
                    break;

                case 'PR':
                    if (this.#manager && this.#baseManagerEnabled) {
                        var new_note = document.getElementById("perinfoNote").value;
                        if (new_note != this.#notesPriorValue) {
                            console.log("need access to result_perinfo");
                            break;
                            /* not this this.#result_perinfo[this.#notesIndex].open_notes = new_note;
                            // search for matching names
                            var postData = {
                                ajax_request_action: 'updatePerinfoNote',
                                perid: this.#result_perinfo[this.#notesIndex].perid,
                                notes: this.#result_perinfo[this.#notesIndex].open_notes,
                                user_id: this.#user_id,
                            };
                            this.#closeNotesBTN.disabled = true;
                            var _this = this;
                            $.ajax({
                                method: "POST",
                                url: "scripts/pos_updatePerinfoNote.php",
                                data: postData,
                                success: function (data, textstatus, jqxhr) {
                                    if (data.error !== undefined) {
                                        show_message(data.error, 'error');
                                        _this.#closeNotesBTN.disabled = false;
                                        return;
                                    }
                                    if (data.message !== undefined) {
                                        show_message(data.message, 'success');
                                    }
                                    if (data.warn !== undefined) {
                                        show_message(data.warn, 'warn');
                                    }
                                },
                                error: function (jqXHR, textstatus, errorThrown) {
                                    _this.#closeNotesBTN.disabled = false;
                                    showAjaxError(jqXHR, textstatus, errorThrown);
                                }
                            });

                             */
                        }
                    }
                    break;

                case 'CT':
                    var newNote = document.getElementById('new_reg_note').value.trim();
                    clear_message();
                    clearError();
                    if (newNote != '') {
                        var _this = this;
                        var postData = {
                            ajax_request_action: 'addRegNote',
                            regid: this.#notesIndex,
                            note: newNote,
                            user_id: this.#user_id,
                            source: config['source'],
                        };
                        this.#closeNotesBTN.disabled = true;
                        $.ajax({
                            method: "POST",
                            url: "scripts/regadmin_addRegNote.php",
                            data: postData,
                            success: function (data, textstatus, jqxhr) {
                                if (data.error !== undefined) {
                                    show_message(data.error, 'error');
                                    _this.#closeNotesBTN.disabled = false;
                                    return;
                                }
                                if (data.success !== undefined) {
                                    show_message(data.success, 'success');
                                }
                                if (data.warn !== undefined) {
                                    show_message(data.warn, 'warn');
                                }
                            },
                            error: function (jqXHR, textstatus, errorThrown) {
                                _this.#closeNotesBTN.disabled = false;
                                showAjaxError(jqXHR, textstatus, errorThrown);
                            }
                        });
                    }
            }
        }
        this.#notesType = null;
        this.#notesPerid = null;
        this.#notesIndex = null;
        this.#notesPriorValue = null;
        this.#notes.hide();
    }
}