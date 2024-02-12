// Exhibitor Profile related functions
//  instance of the class must be a javascript variable names exhibitorProfile
class ExhibitorProfile {

    // Profile DOM related privates
    #profileModal = null;
    #profileMode = "unknown";
    #profileUseType = "unknown";
    #profileIntroDiv = null;
    #profileSubmitBtn = null;
    #profileModalTitle = null;
    #passwordLine1 = null;
    #passwordLine2 = null;
    #creatingAccountMsgDiv = null;
    // globals
    #debugFlag = 0;

    static #fieldList = ["exhibitorName", "exhibitorEmail", "exhibitorPhone", "description", "publicity",
        "contactName", "contactEmail", "contactPhone", "pw1", "pw2",
        "addr", "city", "state", "zip", "country", "shipCompany", "shipAddr", "shipCity", "shipState", "shipZip", "shipCountry", "mailin"];

    static #copyFromFieldList = ['exhibitorName', 'addr', 'addr2', 'city', 'state', 'zip', 'country'];
    static #copyToFieldList = ['shipCompany', 'shipAddr', 'shipAddr2', 'shipCity', 'shipState', 'shipZip', 'shipCountry'];

    // constructor function - intializes dom objects and inital privates
    constructor(debug = 0) {
        var id = document.getElementById('profile');
        if (id != null) {
            this.#profileModal = new bootstrap.Modal(id, {focus: true, backdrop: 'static'});
            if (this.#profileModal != null) {
                this.#profileIntroDiv = document.getElementById("profileIntro");
                this.#passwordLine1 = document.getElementById("passwordLine1");
                this.#passwordLine2 = document.getElementById("passwordLine2");
                this.#profileMode = document.getElementById('profileMode');
                this.#profileSubmitBtn = document.getElementById('profileSubmitBtn');
                this.#profileModalTitle = document.getElementById('modalTitle');
                this.#creatingAccountMsgDiv = document.getElementById('creatingAccountMsg');
            }
        }
        if (debug)
            this.#debugFlag = debug;
    }

    //  copy the address fields to the ship to address fields
    copyAddressToShipTo() {
        for (var fieldNum in ExhibitorProfile.#copyFromFieldList) {
            document.getElementById(ExhibitorProfile.#copyToFieldList[fieldNum]).value = document.getElementById(ExhibitorProfile.#copyFromFieldList[fieldNum]).value;
        }
    }

    // submit the profile or both register and update, which type is in profileMode, set by the modal open
    submitProfile(dataType) {
        // replace validator with direct validation as it doesn't work well with bootstrap
        var valid = true;
        var m2 = ''; // add on to the message field if the description field needs editing
        var field2 = null; // cross field checks (e.g. pw1 and pw2)

        for (var fieldNum in ExhibitorProfile.#fieldList) {
            var fieldName = ExhibitorProfile.#fieldList[fieldNum];
            var field = document.getElementById(fieldName);
            switch (fieldName) {
                case 'exhibitorEmail':
                case 'contactEmail':
                    if (validateAddress(field.value)) {
                        field.style.backgroundColor = '';
                    } else {
                        field.style.backgroundColor = 'var(--bs-warning)';
                        valid = false;
                    }
                    break;
                case 'pw1':
                    if (this.#profileUseType != 'register')
                        break;
                    field2 = document.getElementById("pw2");
                    if (field.value == field2.value && field.value.length >= 8) {
                        field.style.backgroundColor = '';
                    } else {
                        field.style.backgroundColor = 'var(--bs-warning)';
                        valid = false;
                    }
                    break;
                case 'pw2':
                    if (this.#profileUseType != 'register')
                        break;
                    field2 = document.getElementById("pw1");
                    if (field.value == field2.value && field.value.length >= 8) {
                        field.style.backgroundColor = '';
                    } else {
                        field.style.backgroundColor = 'var(--bs-warning)';
                        valid = false;
                    }
                    break;
                case 'description':
                    var value = tinyMCE.activeEditor.getContent();
                    if (value == null) {
                        value = false;
                        m2 = " and the description field which also is required.";
                    } else if (value.trim() == '') {
                        value = false;
                        m2 = " and the description field which also is required.";
                    }
                    break;

                case 'mailin':
                    break;

                default:
                    if (this.#debugFlag & 16) {
                        console.log(ExhibitorProfile.#fieldList[fieldNum].substring(0, 4));
                        console.log(dataType);
                    }
                    if (dataType != 'artist' && ExhibitorProfile.#fieldList[fieldNum].substring(0, 4) == 'ship') {
                        if (this.#debugFlag & 16)
                            console.log("skipping " + ExhibitorProfile.#fieldList[fieldNum]);
                        break;
                    }
                    if (field.value.length > 1) {
                        field.style.backgroundColor = '';
                    } else {
                        field.style.backgroundColor = 'var(--bs-warning)';
                        valid = false;
                    }
            }
        }

        if (!valid) {
            show_message("Fill in required missing fields highlighted in this color" + m2, "warn", 'au_result_message');
            return null;
        }
        clear_message('au_result_message');
        tinyMCE.triggerSave();

        //
        $.ajax({
            url: 'scripts/vendorAddUpdate.php',
            data: $('#exhibitorProfileForm').serialize(),
            method: 'POST',
            success: function (data, textstatus, jqXHR) {
                exhibitorProfile.submitProfileSuccess(data);
            },
            error: showAjaxError
        });
    }

    // submitSuccess - success return from ajax
    submitProfileSuccess(data) {
        if (data['status'] == 'error') {
            show_message(data['message'], 'error', 'au_result_message');
        } else {
            this.profileModalClose();
            if (this.#profileUseType == 'register')
                show_message("Thank you for registering for an account with the " + config['label'] + ' ' + config['portalName'] + " portal.  Please log in using your contact email address and password." + "<br/" + data['message]']);
            else
                show_message(data['message'], 'success')
            if (data['info']) {
                if (config['debug'] & 7) {
                    console.log("before update of vendor_info");
                    console.log(vendor_info);
                }
                vendor_info = data['info'];
                if (config['debug'] & 7) {
                    console.log("after update of vendor_info");
                    console.log(vendor_info);
                }
                if (config['debug'] & 1)
                    console.log(data);
            }
        }
    }

    // profileModalOpen - set up and show the edit profile modal
    profileModalOpen(useType) {
        if (this.#profileModal != null) {
            // set items as registration use of the modal
            switch (useType) {
                case 'register':
                    this.#profileIntroDiv.innerHTML = '<p>This form creates an account on the ' + config['label'] + ' ' + config['portalName'] + ' Portal.</p>';
                    this.#profileSubmitBtn.innerHTML = 'Register ' + config['portalName'];
                    this.#profileModalTitle.innerHTML = "New " + config['portalName'] + ' Registration;'
                    this.#creatingAccountMsgDiv.hidden = false;
                    document.getElementById('publicity').checked = 1;
                    break;
                case 'review':
                    this.#profileIntroDiv.innerHTML = '<p>Please review and update your account with any changes this year.</p>';
                    this.#profileSubmitBtn.innerHTML = 'Reviewed/Updated ' + config['portalName'] + ' Profile';
                    this.#profileModalTitle.innerHTML = "Review " + config['portalName'] + ' Profile';
                    break;
                case 'update':
                    this.#profileIntroDiv.innerHTML = '<p>This form updates your account on the ' + config['label'] + ' ' + config['portalName'] + ' Portal.</p>';
                    this.#profileSubmitBtn.innerHTML = 'Update ' + config['portalName'] + ' Profile';
                    this.#profileModalTitle.innerHTML = "Update " + config['portalName'] + ' Profile';
                    break;
                default: // show something, but the code needs updating if we get here
                    console.log('Unexpected useType: ' + useType);
                    this.#profileIntroDiv.innerHTML = '<p>This form' + useType + ' your account on the ' + config['label'] + ' ' + config['portalName'] + ' Portal.</p>';
                    this.#profileSubmitBtn.innerHTML = UseType + config['portalName'] + ' Profile';
                    this.#profileModalTitle.innerHTML = UseType + config['portalName'] + ' Profile';
            }

            this.#creatingAccountMsgDiv.hidden = true;
            var keys = Object.keys(vendor_info);
            for (var keyindex in keys) {
                var key = keys[keyindex];
                if (key == 'eNeedNew' || key == 'cNeedNew' || key == 'eConfirm' || key == 'cConfirm')
                    continue;

                var value = vendor_info[key];
                if (this.#debugFlag & 16)
                    console.log(key + ' = "' + value + '"');
                if (key == 'mailin') {
                    if (value == 'N')
                        key = 'mailinN';
                    if (value == 'Y')
                        key = 'mailinY';
                }
                var id = document.getElementById(key);
                if (id) {
                    if (key == 'publicity')
                        id.checked = value == 1;
                    else if (key == 'mailinY' || key == 'mailinN')
                        id.checked = true;
                    else
                        id.value = value;
                } else if (this.#debugFlag & 16)
                    console.log("field not found " + key);
            }
        }
        this.#profileMode.value = useType;
        this.#profileUseType = useType;
        this.#passwordLine1.hidden = useType != 'register';
        this.#passwordLine2.hidden = useType != 'register';
        this.#profileModal.show();
        tinyMCE.init({
            selector: 'textarea#description',
            height: 400,
            min_height: 400,
            menubar: false,
            plugins: 'advlist lists image link charmap fullscreen help nonbreaking preview searchreplace',
            toolbar: [
                'help undo redo searchreplace copy cut paste pastetext | fontsizeinput styles h1 h2 h3 h4 h5 h6 | ' +
                'bold italic underline strikethrough removeformat | ' +
                'visualchars nonbreaking charmap hr | ' +
                'preview fullscreen ',
                'alignleft aligncenter alignright alignnone | outdent indent | numlist bullist checklist | forecolor backcolor | link image'
            ],
            content_style: 'body {font - family:Helvetica,Arial,sans-serif; font-size:14px }',
            placeholder: 'Edit the description here...',
            auto_focus: 'reg-description'
        });
    }

    // profileModalClose - close the modal edit profile dialog
    profileModalClose() {
        if (this.#profileModal != null) {
            this.#profileModal.hide();
        }
    }
}
