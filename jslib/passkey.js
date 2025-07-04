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
    if (!window.fetch || !navigator.credentials || !navigator.credentials.create) {
        show_message('Your browser does not support passkeys.', 'error');
        return;
    }

    var params = "displayName=" + encodeURIComponent(displayName) +
        "&email=" + encodeURIComponent(email) +
        "&source=" + encodeURIComponent(source) +
        "&action=create";

    var rep = await fetch(script + '?' + params, {
        method: 'GET',
        cache: 'no-cache'
    });

    const createArgs = await rep.json();

// replace binary base64 data with ArrayBuffer. another way to do this
// is the reviver function of JSON.parse()
    recursiveBase64StrToArrayBuffer(createArgs);

// create credentials
    var cred = await navigator.credentials.create(createArgs);

// create object
    var authenticatorAttestationResponse = {
        transports: cred.response.getTransports ? cred.response.getTransports() : null,
        clientDataJSON: cred.response.clientDataJSON ? arrayBufferToBase64(cred.response.clientDataJSON) : null,
        attestationObject: cred.response.attestationObject ? arrayBufferToBase64(cred.response.attestationObject) : null
    };


    // check asstetation and store in server if successful
    // save key in server database
    data = {
        action: 'save',
        displayName: displayName,
        email: email,
        source: source,
        att: JSON.stringify(authenticatorAttestationResponse),
    };

    $.ajax({
        method: 'POST',
        url: script,
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

// deletePasskeyEntry - delete from the database the passkey with this id and userName
function deletePasskeyEntry(script, id, userName, source) {
    data = {
        action: 'delete',
        email: userName,
        id: id,
        source: source,
    };

    $.ajax({
        method: 'POST',
        url: script,
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

async function passkeyRequest(script, successPage, source, enable) {
    if (!window.fetch || !navigator.credentials || !navigator.credentials.create) {
        show_message('Your browser does not support passkeys.', 'error');
        return;
    }

    var params = "displayName=NA" +
        "&email=NA" +
        "&source=" + encodeURIComponent(source) +
        "&action=request";

    var rep = await fetch(script + '?' + params, {
        method: 'GET',
        cache: 'no-cache'
    });

    const requestArgs = await rep.json();

// replace binary base64 data with ArrayBuffer. another way to do this
// is the reviver function of JSON.parse()
    recursiveBase64StrToArrayBuffer(requestArgs);

// get credentials
    var cred = await navigator.credentials.get(requestArgs);

// create object
    // create object for transmission to server
    var authenticatorAttestationResponse = {
        id: cred.rawId ? arrayBufferToBase64(cred.rawId) : null,
        clientDataJSON: cred.response.clientDataJSON  ? arrayBufferToBase64(cred.response.clientDataJSON) : null,
        authenticatorData: cred.response.authenticatorData ? arrayBufferToBase64(cred.response.authenticatorData) : null,
        signature: cred.response.signature ? arrayBufferToBase64(cred.response.signature) : null,
        userHandle: cred.response.userHandle ? arrayBufferToBase64(cred.response.userHandle) : null
    };

    // check asstetation and store in server if successful
    // save key in server database
    data = {
        action: 'check',
        displayName: displayName,
        email: email,
        source: source,
        att: JSON.stringify(authenticatorAttestationResponse),
    };

    $.ajax({
        method: 'POST',
        url: script,
        data: data,
        success: function (data, textStatus, jqXhr) {
            if (enable)
                enable.disabled = false;

            if (data['status'] == 'error') {
                show_message(data['message'], 'error');
                return;
            } else if (data['status'] == 'warn') {
                show_message(data['message'], 'warn');
            } else {
                switch (source) {
                    case 'portal':
                        window.location = successPage + '?messageFwd=' + encodeURI(data['message']);
                        return;
                }
                show_message(data['message'], 'success');
                return;
            }
        },
        error: function (jqXHR, textStatus, errorThrown) {
            if (enable)
                enable.disabled = false;

            showAjaxError(jqXHR, textStatus, errorThrown);
            return false;
        },
    });
}
/*
    // get check args
        let rep = await window.fetch(script + '?fn=getGetArgs' + getGetParams(), {method:'GET',cache:'no-cache'});
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
*/

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