// Main login javascript, also requires base.js

var login = null;
var profile = null;

// initial setup
window.onload = function () {
    login = new Login();
}

class Login {
    // login fields

    #loginWithPasskeyBtn = null;

    constructor() {
        this.#loginWithPasskeyBtn = document.getElementById('loginPasskeyBtn');
    }

// login functions
    // login with passkey - ask for a confirm and return either retry or go to portal
    loginWithPasskey() {
        if (this.#loginWithPasskeyBtn)
            this.#loginWithPasskeyBtn.disabled = true;

        passkeyRequest('scripts/passkeyActions.php', 'index.php', 'controll', this.#loginWithPasskeyBtn);
    }

    // loginWithToken: show email for token
    loginWithGoogle(id = '') {
        let loc = '?oauth2=google';
        if (id != '')
            loc += '&id=' + id;
        window.location = loc;
    }
}
