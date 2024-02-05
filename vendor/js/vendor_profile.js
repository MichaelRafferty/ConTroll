// Vendor Prpfile related functions
var profileModal = null;
var profileMode = "unknown";
var profileUseType = "unknown";
var switchPortalbtn = null;
var profileIntroDiv = null;
var profileSubmitBtn = null;
var profileModalTitle = null;

const fieldlist = ["exhibitorName", "exhibitorEmail", "exhibitorPhone", "description", "publicity",
    "contactName", "contactEmail", "contactPhone", "pw1", "pw2",
    "addr", "city", "state", "zip", "country", "shipCompany", "shipAddr", "shipCity", "shipState", "shipZip", "shipCountry", "mailin"];
const copyFromFieldList = [ 'exhibitorName', 'addr', 'addr2', 'city', 'state', 'zip', 'country'];
const copyToFieldList = ['shipCompany', 'shipAddr', 'shipAddr2', 'shipCity', 'shipState', 'shipZip', 'shipCountry'];

// setup function
function vendorProfileOnLoad() {
    var id = document.getElementById('profile');
    if (id != null) {
        profileModal = new bootstrap.Modal(id, {focus: true, backdrop: 'static'});
    }
}


//  copy the address fields to the ship to address fields
function copyAddressToShipTo() {
    for (var fieldnum in copyFromFieldList) {
        document.getElementById(copyToFieldList[fieldnum]).value = document.getElementById(copyFromFieldList[fieldnum]).value;
    }
}

// submit the profile or both register and update, which type is in profileMode, set by the modal open
function submitProfile(dataType) {
    // replace validator with direct validation as it doesn't work well with bootstrap
    var valid = true;
    var m2= '';

    for (var fieldnum in fieldlist) {
        var field = document.getElementById(fieldlist[fieldnum]);
        switch (fieldlist[fieldnum]) {
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
                if (profileUseType != 'register')
                    break;
                var field2 = document.getElementById("pw2");
                if (field.value == field2.value && field.value.length >= 8) {
                    field.style.backgroundColor = '';
                } else {
                    field.style.backgroundColor = 'var(--bs-warning)';
                    valid = false;
                }
                break;
            case 'pw2':
                if (profileUseType != 'register')
                    break;
                var field2 = document.getElementById("pw1");
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
                console.log(fieldlist[fieldnum].substring(0, 4) );
                console.log(dataType);
                if (dataType != 'artist' && fieldlist[fieldnum].substring(0, 4) == 'ship') {
                    if (config['debug'] & 16)
                        console.log("skipping " + fieldlist[fieldnum]);
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
        success: function(data, textstatus, jqXHR) {
            if(data['status'] == 'error') {
                show_message(data['message'], 'error', 'au_result_message');
            } else {
                profileModalClose();
                if (profileUseType == 'register')
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
        },
        error: showAjaxError
    });
}


function profileModalOpen(useType) {
    if (profileModal != null) {
        // set items as registration use of the modal
        if (profileIntroDiv == null) {
            profileIntroDiv = document.getElementById("profileIntro");
            passwordLine1 = document.getElementById("passwordLine1");
            passwordLine2 = document.getElementById("passwordLine2");
            profileMode = document.getElementById('profileMode');
            profileSubmitBtn = document.getElementById('profileSubmitBtn');
            profileModalTitle = document.getElementById('modalTitle');
            creatingAccountMsgDiv = document.getElementById('creatingAccountMsg');
        }
        if (useType == 'register') {
            profileIntroDiv.innerHTML = '<p>This form creates an account on the ' + config['label'] + ' ' + config['portalName'] + ' Portal.</p>';
            profileSubmitBtn.innerHTML = 'Register ' + config['portalName'];
            profileModalTitle.innerHTML = "New " + config['portalName'] + ' Registration;'
            creatingAccountMsgDiv.hidden = false;
            document.getElementById('publicity').checked = 1;
        } else { // update/Review
            if (useType == 'review') {
                profileIntroDiv.innerHTML = '<p>Please review and update your account with any changes this year.</p>';
                profileSubmitBtn.innerHTML = 'Reviewed/Updated ' + config['portalName'] + ' Profile';
            } else {
                profileIntroDiv.innerHTML = '<p>This form updates your account on the ' + config['label'] + ' ' + config['portalName'] + ' Portal.</p>';
                profileSubmitBtn.innerHTML = 'Update ' + config['portalName'] + ' Profile';
            }

            profileModalTitle.innerHTML = "Update " + config['portalName'] + ' Profile';
            creatingAccountMsgDiv.hidden = true;
            var keys = Object.keys(vendor_info);
            for (var keyindex in keys) {
                var key = keys[keyindex];
                if (key == 'eNeedNew' || key == 'cNeedNew' || key == 'eConfirm' || key == 'cConfirm')
                    continue;

                var value=vendor_info[key];
                if (config['debug'] & 16)
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
                } else  if (config['debug'] & 16)
                    console.log("field not found " + key);
            }
        }
        profileMode.value = useType;
        profileUseType = useType;
        passwordLine1.hidden = useType != 'register';
        passwordLine2.hidden = useType != 'register';
        profileModal.show();
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
}

function profileModalClose() {
    if (profileModal != null) {
        profileModal.hide();
    }
}
