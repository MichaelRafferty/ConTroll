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
    #newPasskeyBtn = null;

    constructor() {
        this.#loginWithPasskeyBtn = document.getElementById('loginPasskeyBtn');
    }

// login functions
    // login with passkey - ask for a confirm and return either retry or go to portal
    loginWithPasskey(email = null) {
        if (this.#loginWithPasskeyBtn)
            this.#loginWithPasskeyBtn.disabled = true;

        passkeyRequest('scripts/passkeyActions.php', 'index.php', 'controll', this.#loginWithPasskeyBtn, email);
    }

    // loginWithToken: show email for token
    loginWithGoogle(id = '') {
        let loc = '?oauth2=google';
        if (id != '')
            loc += '&id=' + id;
        window.location = loc;
    }
    // passkeys
    // newPasskey - request generate passkey on device and store same in database
    newPasskey() {
        if (!this.#newPasskeyBtn)
            this.#newPasskeyBtn = document.getElementById('newPasskey');

        if (this.#newPasskeyBtn)
            this.#newPasskeyBtn.disabled = true;

        createPasskeyRegistration('scripts/passkeyActions.php', config.name.trim(), config.email, 'controll');
        if (this.#newPasskeyBtn)
            this.#newPasskeyBtn.disabled = false;
        return;
    }

    // delete passkey - clicked the delete button
    deletePasskey(id) {
        deletePasskeyEntry('scripts/passkeyActions.php', id, config.email, 'controll');
    }
}
