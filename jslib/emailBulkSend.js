// Bulk Email Sending functions
//  instance of the class must be a javascript variable named emailBulkSend
// Usage concept:
//  1. create the bulk email instance
//  2. Call the getList function passing in the script and data to pass to retrieve the email parameters
//  3. Get approval to send the list (in regular or test mode) to the list of people
//  4. Loop over the list calling the function for a particular batch size, until all emails are sent.
class EmailBulkSend {

    // Bulk Email DOM related privates
    #emailStatusDivId = null;
    #emailStatusDiv = null;

    // Bulk Email in progress privates
    #emailText = null;
    #emailHTML = null;
    #emailFrom = null;
    #emailTo = null;
    #emailCC = null;
    #emailSubject = null;
    #emailStatusHTML = '';
    #emailBatch = null;
    #emailTest = null;
    #emailType = null;

    // Operational privates
    #debug = 0;
    #startOrdinal = 0;
    #sendURL = null;
    #batchStartTime = null;

    // Email sending parameters
    #batchSize = 100;
    #macroSubstitution = false;

    constructor(statusDivName, sendURL, batchSize = 100, debug = 0) {
        this.#debug = debug;
        this.#sendURL = sendURL;
        this.#batchSize = batchSize;
        this.#emailStatusDivId = statusDivName;
        this.#emailStatusDiv = document.getElementById(statusDivName);
        this.#emailStatusHTML = '';
    }

    getEmailAndList(url, dataobj) {
        this.#emailStatusHTML += "Getting Email Contents and List\n";
        this.#emailStatusDiv.innerHTML = this.#emailStatusHTML;
        var _this = this;
        $.ajax({
            url: url,
            data: dataobj,
            method: 'POST',
            success: function (data, textstatus, jqxhr) {
                _this.showList(data);
            },
            error: showAjaxError
        })
    }

    showList(data) {
        if (this.#debug & 1)
            console.log(data);
        if (data['error'] !== undefined) {
            show_message(data['error'], 'error', this.#emailStatusDivId );
            return;
        }
        if (data['success'] !== undefined) {
            show_message(data['success'], 'success', this.#emailStatusDivId);
        }
        if (data['warn'] !== undefined) {
            show_message(data['warn'], 'warn', this.#emailStatusDivId);
        }

        this.#emailText = data['emailText'];
        this.#emailHTML = data['emailHTML'];
        this.#emailFrom = data['emailFrom'];
        this.#emailTo = data['emailTo'];
        this.#emailCC = data['emailCC'];
        this.#emailSubject = data['emailSubject'];
        this.#emailType = data['emailType'];
        this.#macroSubstitution = data['macroSubstitution'];
        if (data['emailTest']) {
            this.#emailTest = data['emailTest'];
        }

        // get first and last of list, plus it's size
        var params = "<pre>\n\nEmail Paramenters:\n" +
            "From: " + this.#emailFrom + "\n" +
            "To: " + this.#emailTo.length + " addresses, first: " + this.#emailTo[0]['email'] + ", last: " + this.#emailTo[this.#emailTo.length - 1]['email'] + "\n";
        if (this.#emailTest) {
            params += "Test To: " + this.#emailTest[0]['email'] + "\n";
        }
        if (this.#emailCC) {
            params += "CC: " + this.#emailCC.join(", ") + "\n";
        } else {
            params += "CC: [null]\n";
        }
            params += "Subject: " + this.#emailSubject + "\n" +
            "Macro Substitution: " + this.#macroSubstitution + "\n</pre>\n";

        if (this.#emailTest)
            params += "<button class='btn btn-primary btn-sm' onclick='emailBulkSend.sendBulkOk();'>Send Test email</button>&nbsp;";
        else
            params += "<button class='btn btn-primary btn-sm' onclick='emailBulkSend.sendBulkOk();'>Send " + this.#emailTo.length + " emails</button>&nbsp;";

        params += "<button class='btn btn-primary btn-sm' id='bulkSendCancelBtn' onclick='emailBulkSend.sendBulkNo();'>Cancel Send</button>\n";
        this.#emailStatusHTML += params;
        clear_message(this.#emailStatusDivId);
        this.#emailStatusDiv.innerHTML = this.#emailStatusHTML;
        document.getElementById('bulkSendCancelBtn').focus();
    }

    sendBulkNo() {
        clear_message(this.#emailStatusDivId);
        this.#emailStatusDiv.innerHTML = "Email Cancelled";
        emailBulkSend = null;
    }

    sendBulkOk() {
        if (this.#emailTest)
            this.#emailTo =  this.#emailTest;
        this.#emailStatusHTML = "Email Send Started for " + this.#emailTo.length + " emails\n<PRE>\n";
        clear_message(this.#emailStatusDivId);
        this.#emailStatusDiv.innerHTML = this.#emailStatusHTML + "</pre>\n";
        this.#startOrdinal = 0;
        if (this.#emailTo.length > 0)
            this.sendNextBatch();
        else
            this.#emailStatusDiv.innerHTML = "Nothing to send?";
    }

    sendNextBatch() {
        this.#emailBatch = this.#emailTo.slice(this.#startOrdinal, this.#startOrdinal + this.#batchSize);
        this.#batchStartTime = Date.now();
        var data = {
            emailText: this.#emailText,
            emailHTML: this.#emailHTML,
            emailFrom: this.#emailFrom,
            emailTo: this.#emailBatch,
            emailCC: this.#emailCC,
            emailSubject: this.#emailSubject,
            emailTest: this.#emailTest,
            emailType: this.#emailType,
            macroSubstitution: this.#macroSubstitution
        };
        var dataJSON = btoa(encodeURI(JSON.stringify(JSON.stringify(data))));
        var _this = this;
        $.ajax({
            url: this.#sendURL,
            data: { data: dataJSON },
            method: 'POST',
            success: function (data, textstatus, jqxhr) {
                _this.finishBatch(data);
            },
            error: showAjaxError
        })
    }

    finishBatch(data) {
        if (this.#debug & 1)
            console.log(data);
        if (data['error'] !== undefined) {
            show_message(data['error'], 'error', this.#emailStatusDivId );
            return;
        }
        if (data['success'] !== undefined) {
            show_message(data['success'], 'success', this.#emailStatusDivId);
        }
        if (data['warn'] !== undefined) {
            show_message(data['warn'], 'warn', this.#emailStatusDivId);
        }

        var elapsed = (Date.now() - this.#batchStartTime) / 1000;
        this.#emailStatusHTML += "Batch of " + this.#emailBatch.length + " sent in " + elapsed + " seconds\n";
        this.#emailStatusDiv.innerHTML = this.#emailStatusHTML + "</pre>\n";
        this.#emailTo = this.#emailTo.slice(this.#batchSize);
        if (this.#emailTo.length > 0) {
            this.sendNextBatch();
        } else {
            this.#emailStatusHTML += "\n\n</pre>\nEmail Send Complete";
            this.#emailStatusDiv.innerHTML = this.#emailStatusHTML;
        }
    }
}

emailBulkSend = null;
