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

// membership add/update functions
    disassociate(idstr) {
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
}
