// account settinga javascript, also requires base.js

var settings = null;

// initial setup
window.onload = function () {
    settings = new Settings();
}

class Settings {
    #people = null;
    #emails = null;
    #newPasskeyBtn = null;

    constructor() {
        this.#people = [];
        this.#emails = [];
        this.#newPasskeyBtn = document.getElementById('newPasskey');
        if (config.hasOwnProperty('passkey') && config.passkey == 'create')
            this.newPasskey();
    }

// associate / disassociate a person from this account, by the account holders requests
    // disassociate - remove control of a person from this account
    disassociate(idstr) {
        var type = idstr.substring(0,1);
        var id = Number(idstr.substring(1));
        var script = 'scripts/processDisassociate.php';
        var data = {
            loginId: config['id'],
            loginType: config['idType'],
            managedBy: 'client',
            idType: type,
            idNum: id,
        }
        $.ajax({
            method: 'POST',
            url: script,
            data: data,
            success: function (data, textStatus, jqXhr) {
                checkResolveUpdates(data);
                if (data['status'] == 'error') {
                    show_message(data['message'], 'error');
                } else if (data['status'] == 'warn') {
                    show_message(data['message'], 'warn');
                } else {
                    window.location = '?messageFwd=' + encodeURI(data['message']);
                }
            },
            error: function (jqXHR, textStatus, errorThrown) {
                showAjaxError(jqXHR, textStatus, errorThrown);
                return false;
            },
        });
    }

    // attach - request (or if emails match, directly attach) a person to this account
    attach() {
        var id = document.getElementById('acctId');
        if (id == null)
            return; // the form is not loaded

        var acctId = Number(id.value);
        var email = document.getElementById('emailAddr').value;

        if  (acctId == null || acctId <= 0) {
            show_message("Account Id is required", "error");
            return;
        }

        if (acctId == config['id']) {
            show_message("You cannot request to manage yourself", "error");
            return;
        }

        if  (email == null || email == '' || validateAddress(email) == false) {
            show_message("A valid email address is required", "error");
            return;
        }

        if (managed['p' + acctId.toString()] || managed['n' + acctId.toString()]) {
            show_message("You already manage " + acctId.toString(), "error");
            return;
        }

        document.getElementById('attachBtn').disabled = true;
        var script = 'scripts/requestAssociate.php';
        var data = {
            loginId: config['id'],
            loginType: config['idType'],
            acctId: acctId,
            email: email,
            action: 'request',
        }

        $.ajax({
            method: 'POST',
            url: script,
            data: data,
            success: function (data, textStatus, jqXhr) {
                checkResolveUpdates(data);
                if (data['status'] == 'error') {
                    show_message(data['message'], 'error');
                    document.getElementById('attachBtn').disabled = false;
                } else if (data['status'] == 'warn') {
                    show_message(data['message'], 'warn');
                    document.getElementById('attachBtn').disabled = false;
                } else {
                    window.location = '?messageFwd=' + encodeURI(data['message']);
                }
            },
            error: function (jqXHR, textStatus, errorThrown) {
                showAjaxError(jqXHR, textStatus, errorThrown);
                document.getElementById('attachBtn').disabled = false;
                return false;
            },
        });
    }

    // deleteIdentity/newIdentity add/remove identities
    deleteIdentity(idstr) {
        var keys = idstr.split('~');
        var script = 'scripts/processIdentityDelete.php';
        var data = {
            loginId: config['id'],
            loginType: config['idType'],
            action: 'delete',
            provider: keys[0],
            email: keys[1],
        }
        $.ajax({
            method: 'POST',
            url: script,
            data: data,
            success: function (data, textStatus, jqXhr) {
                checkResolveUpdates(data);
                if (data['status'] == 'error') {
                    show_message(data['message'], 'error');
                } else if (data['status'] == 'warn') {
                    show_message(data['message'], 'warn');
                } else {
                    window.location = '?messageFwd=' + encodeURI(data['message']);
                }
            },
            error: function (jqXHR, textStatus, errorThrown) {
                showAjaxError(jqXHR, textStatus, errorThrown);
                return false;
            },
        });
    }

    // newidentity - add new identity
    newIdentity() {
        var id = document.getElementById('provider');
        if (id == null)
            return; // the form is not loaded

        var provider = id.value;
        var email = document.getElementById('identityEmailAddr').value;

        if (provider == null || provider == '')
            provider = 'allow';

        if  (email == null || email == '' || validateAddress(email) == false) {
            show_message("A valid email address is required", "error");
            return;
        }

        for (var row in identities) {
            var identity = identities[row];
            if (identity.provide == provider && identity.email_addr) {
                show_message("This entry is already in your list", "error");
                return;
            }
        }

        document.getElementById('newIdentity').disabled = true;
        var script = 'scripts/addIdentity.php';
        var data = {
            loginId: config['id'],
            loginType: config['idType'],
            provider: provider,
            email: email,
            action: 'addIdentity',
        }

        $.ajax({
            method: 'POST',
            url: script,
            data: data,
            success: function (data, textStatus, jqXhr) {
                checkResolveUpdates(data);
                if (data['status'] == 'error') {
                    show_message(data['message'], 'error');
                    document.getElementById('newIdentity').disabled = false;
                } else if (data['status'] == 'warn') {
                    show_message(data['message'], 'warn');
                    document.getElementById('newIdentity').disabled = false;
                } else {
                    window.location = '?messageFwd=' + encodeURI(data['message']);
                }
            },
            error: function (jqXHR, textStatus, errorThrown) {
                showAjaxError(jqXHR, textStatus, errorThrown);
                document.getElementById('newIdentity').disabled = false;
                return false;
            },
        });
    }

    // passkeys
    // newPasskey - request generate passkey on device and store same in database
    newPasskey() {
        if (this.#newPasskeyBtn)
            this.#newPasskeyBtn.disabled = true;

        var displayName = document.getElementById('userDisplayName').value;

        if (displayName.length == 0) {
            if (config.badgeName.length > 0)
                displayName = config.badgeName
            else
                displayName = config.firstName + ' ' + config.lastName;
        }

        createPasskeyRegistration('scripts/passkeyActions.php', displayName.trim(), config.email, 'portal');
        if (this.#newPasskeyBtn)
            this.#newPasskeyBtn.disabled = false;
        return;
    }

    // delete passkey - clicked the delete button
    deletePasskey(id) {
        deletePasskeyEntry('scripts/passkeyActions.php', id, config.email, 'portal');
    }
}
