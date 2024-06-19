// addUpdate javascript, also requires base.js

var settings = null;

// initial setup
window.onload = function () {
    settings = new Settings();
}

class Settings {
    #people = null;
    #emails = null;

    constructor() {
        this.#people = [];
        this.#emails = [];
    }

// associate / disassociate a person from this account, by the account holders requests
    // disassociate - remove control of a person from this account
    disassociate(idstr) {
        document.getElementById('attachBtn').disabled = true;
        var type = idstr.substring(0,1);
        var id = Number(idstr.substring(1));
        var script = 'scripts/processDisassociate.php';
        var data = {
            managedBy: 'client',
            idType: type,
            idNum: id,
        }
        $.ajax({
            method: 'POST',
            url: script,
            data: data,
            success: function (data, textStatus, jqXhr) {
                if (data['status'] == 'error') {
                    show_message(data['message'], 'error');
                } else if (data['status'] == 'warn') {
                    show_message(data['message'], 'warn');
                } else {
                    window.location.search = '?messageFwd=' + encodeURI(data['message']);
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

        if (acctId == config['personId']) {
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
            acctId: acctId,
            email: email,
            action: 'request',
        }

        $.ajax({
            method: 'POST',
            url: script,
            data: data,
            success: function (data, textStatus, jqXhr) {
                if (data['status'] == 'error') {
                    show_message(data['message'], 'error');
                    document.getElementById('attachBtn').disabled = false;
                } else if (data['status'] == 'warn') {
                    show_message(data['message'], 'warn');
                    document.getElementById('attachBtn').disabled = false;
                } else {
                    window.location.search = '?messageFwd=' + encodeURI(data['message']);
                }
            },
            error: function (jqXHR, textStatus, errorThrown) {
                showAjaxError(jqXHR, textStatus, errorThrown);
                document.getElementById('attachBtn').disabled = false;
                return false;
            },
        });
    }
}
