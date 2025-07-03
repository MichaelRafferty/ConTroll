/*
 * Copyright (c) 2025, Michael Rafferty
 * ConTroll™ is freely available for use under the GNU Affero General Public License, Version 3. See the ConTroll™ ReadMe file.
 */

/* common JS library for working with passkeys.
 * Modifed from the https://github.com/lbuchs/webauthn client under the MIT License
 */

/**
 * creates a new FIDO2 registration
 * @returns {undefined}
 */
async function createPasskeyRegistration(script, displayName, email, source) {
    // check browser support
    console.log(navigator.credentials);
    console.log(navigator.credentials.create);

    if (!window.fetch || !navigator.credentials || !navigator.credentials.create) {
        show_message('Your browser does not support passkeys.', 'error');
        return;
    }

    /*
// get create args from server
    var data = {
        displayName: displayName,
        email: email,
        source: source,
        action: 'create',
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
                createPasskeyJS(data);
            }
        },
        error: function (jqXHR, textStatus, errorThrown) {
            showAjaxError(jqXHR, textStatus, errorThrown);
            return false;
        },
    });
}
     */
    var rep = await window.fetch(script + '?action=create&source=portal&email=' + email, {method:'POST', cache:'no-cache'});
    const createArgs = await rep.json();
/*
async function createPasskeyJS(data) {
    var args = data.args;
    if (args.success === false) {
        show_message("Error: " + createArgs.msg, 'error');
        return;
    }

    console.log(args);
    createArgs = JSON.parse(args);

 */
    console.log(createArgs);

// replace binary base64 data with ArrayBuffer. another way to do this
// is the reviver function of JSON.parse()
    recursiveBase64StrToArrayBuffer(createArgs);
    console.log(createArgs);

// create credentials
    var cred = await navigator.credentials.create(createArgs);

// create object
    var authenticatorAttestationResponse = {
        transports: cred.response.getTransports ? cred.response.getTransports() : null,
        clientDataJSON: cred.response.clientDataJSON ? arrayBufferToBase64(cred.response.clientDataJSON) : null,
        attestationObject: cred.response.attestationObject ? arrayBufferToBase64(cred.response.attestationObject) : null
    };

// check auth on server side
    rep = await window.fetch(server + '?fn=processCreate' + getGetParams(), {
        method: 'POST',
        body: JSON.stringify(authenticatorAttestationResponse),
        cache: 'no-cache'
    });
    var authenticatorAttestationServerResponse = await rep.json();

// prompt server response
    if (!authenticatorAttestationServerResponse.success) {
        show_message('Error: ' + authenticatorAttestationServerResponse.msg, 'error');
        return;
    }

    // attestation successful
    // save key in server databse
    data.action = 'save';
    $.ajax({
        method: 'POST',
        url: data.script,
        data: data,
        success: function (data, textStatus, jqXhr) {
            if (data['status'] == 'error') {
                show_message(data['message'], 'error');
                return;
            } else if (data['status'] == 'warn') {
                show_message(data['message'], 'warn');
            } else {
                switch (data.source) {
                    case 'portal':
                        window.location = '?messageFwd=' + encodeURI(data['message']);
                        return;
                }
                show_message(data['message'], 'success');
                return;
            }
        },
        error: function (jqXHR, textStatus, errorThrown) {
            showAjaxError(jqXHR, textStatus, errorThrown);
            return false;
        },
    });
}


/**
* checks a FIDO2 registration
* @returns {undefined}
*/
async function checkRegistration(server) {
    try {
        if (!window.fetch || !navigator.credentials || !navigator.credentials.create) {
            throw new Error('Browser not supported.');
        }

    // get check args
        let rep = await window.fetch(server + '?fn=getGetArgs' + getGetParams(), {method:'GET',cache:'no-cache'});
        const getArgs = await rep.json();

        // error handling
        if (getArgs.success === false) {
            throw new Error(getArgs.msg);
        }

        // replace binary base64 data with ArrayBuffer. a other way to do this
        // is the reviver function of JSON.parse()
        recursiveBase64StrToArrayBuffer(getArgs);

        // check credentials with hardware
        const cred = await navigator.credentials.get(getArgs);

        // create object for transmission to server
        const authenticatorAttestationResponse = {
            id: cred.rawId ? arrayBufferToBase64(cred.rawId) : null,
            clientDataJSON: cred.response.clientDataJSON  ? arrayBufferToBase64(cred.response.clientDataJSON) : null,
            authenticatorData: cred.response.authenticatorData ? arrayBufferToBase64(cred.response.authenticatorData) : null,
            signature: cred.response.signature ? arrayBufferToBase64(cred.response.signature) : null,
            userHandle: cred.response.userHandle ? arrayBufferToBase64(cred.response.userHandle) : null
        };

        // send to server
        rep = await window.fetch(server + '?fn=processGet' + getGetParams(), {
            method:'POST',
            body: JSON.stringify(authenticatorAttestationResponse),
            cache:'no-cache'
        });
        const authenticatorAttestationServerResponse = await rep.json();

        // check server response
        if (authenticatorAttestationServerResponse.success) {
            reloadServerPreview();
            window.alert(authenticatorAttestationServerResponse.msg || 'login success');
        } else {
            throw new Error(authenticatorAttestationServerResponse.msg);
        }

    } catch (err) {
        reloadServerPreview();
        window.alert(err.message || 'unknown error occured');
    }
}

function queryFidoMetaDataService(server) {
    window.fetch(server + '?fn=queryFidoMetaDataService' + getGetParams(), {method:'GET',cache:'no-cache'}).then(function(response) {
        return response.json();
    }).then(function(json) {
        if (json.success) {
            window.alert(json.msg);
        } else {
            throw new Error(json.msg);
        }
    }).catch(function(err) {
        window.alert(err.message || 'unknown error occured');
    });
}

/**
* convert RFC 1342-like base64 strings to array buffer
* @param {mixed} obj
* @returns {undefined}
*/
function recursiveBase64StrToArrayBuffer(obj) {
    let prefix = '=?BINARY?B?';
    let suffix = '?=';
    if (typeof obj === 'object') {
        for (let key in obj) {
            if (typeof obj[key] === 'string') {
                let str = obj[key];
                if (str.substring(0, prefix.length) === prefix && str.substring(str.length - suffix.length) === suffix) {
                    str = str.substring(prefix.length, str.length - suffix.length);

                    let binary_string = window.atob(str);
                    let len = binary_string.length;
                    let bytes = new Uint8Array(len);
                    for (let i = 0; i < len; i++) {
                        bytes[i] = binary_string.charCodeAt(i);
                    }
                    obj[key] = bytes.buffer;
                }
            } else {
                recursiveBase64StrToArrayBuffer(obj[key]);
            }
        }
    }
}

/**
* Convert a ArrayBuffer to Base64
* @param {ArrayBuffer} buffer
* @returns {String}
*/
function arrayBufferToBase64(buffer) {
    let binary = '';
    let bytes = new Uint8Array(buffer);
    let len = bytes.byteLength;
    for (let i = 0; i < len; i++) {
        binary += String.fromCharCode(bytes[i]);
    }
    return window.btoa(binary);
}

/**
* Get URL parameter
* @returns {String}
*/
function getGetParams() {
    let url = '';

    url += '&apple=' + (document.getElementById('cert_apple').checked ? '1' : '0');
    url += '&yubico=' + (document.getElementById('cert_yubico').checked ? '1' : '0');
    url += '&solo=' + (document.getElementById('cert_solo').checked ? '1' : '0');
    url += '&hypersecu=' + (document.getElementById('cert_hypersecu').checked ? '1' : '0');
    url += '&google=' + (document.getElementById('cert_google').checked ? '1' : '0');
    url += '&microsoft=' + (document.getElementById('cert_microsoft').checked ? '1' : '0');
    url += '&mds=' + (document.getElementById('cert_mds').checked ? '1' : '0');

    url += '&requireResidentKey=' + (document.getElementById('requireResidentKey').checked ? '1' : '0');

    url += '&type_usb=' + (document.getElementById('type_usb').checked ? '1' : '0');
    url += '&type_nfc=' + (document.getElementById('type_nfc').checked ? '1' : '0');
    url += '&type_ble=' + (document.getElementById('type_ble').checked ? '1' : '0');
    url += '&type_int=' + (document.getElementById('type_int').checked ? '1' : '0');
    url += '&type_hybrid=' + (document.getElementById('type_hybrid').checked ? '1' : '0');

    url += '&fmt_android-key=' + (document.getElementById('fmt_android-key').checked ? '1' : '0');
    url += '&fmt_android-safetynet=' + (document.getElementById('fmt_android-safetynet').checked ? '1' : '0');
    url += '&fmt_apple=' + (document.getElementById('fmt_apple').checked ? '1' : '0');
    url += '&fmt_fido-u2f=' + (document.getElementById('fmt_fido-u2f').checked ? '1' : '0');
    url += '&fmt_none=' + (document.getElementById('fmt_none').checked ? '1' : '0');
    url += '&fmt_packed=' + (document.getElementById('fmt_packed').checked ? '1' : '0');
    url += '&fmt_tpm=' + (document.getElementById('fmt_tpm').checked ? '1' : '0');

    url += '&rpId=' + encodeURIComponent(document.getElementById('rpId').value);

    url += '&userId=' + encodeURIComponent(document.getElementById('userId').value);
    url += '&userName=' + encodeURIComponent(document.getElementById('userName').value);
    url += '&userDisplayName=' + encodeURIComponent(document.getElementById('userDisplayName').value);

    if (document.getElementById('userVerification_required').checked) {
        url += '&userVerification=required';

    } else if (document.getElementById('userVerification_preferred').checked) {
        url += '&userVerification=preferred';

    } else if (document.getElementById('userVerification_discouraged').checked) {
        url += '&userVerification=discouraged';
    }

    return url;
}

function setAttestation(attestation) {
    let inputEls = document.getElementsByTagName('input');
    for (const inputEl of inputEls) {
        if (inputEl.id && inputEl.id.match(/^(fmt|cert)\_/)) {
            inputEl.disabled = !attestation;
        }
        if (inputEl.id && inputEl.id.match(/^fmt\_/)) {
            inputEl.checked = attestation ? inputEl.id !== 'fmt_none' : inputEl.id === 'fmt_none';
        }
        if (inputEl.id && inputEl.id.match(/^cert\_/)) {
            inputEl.checked = attestation ? inputEl.id === 'cert_mds' : false;
        }
    }
}
