// Class Atcon Payments
// Resolve terminal payments without a final poll to complete or cancelled

class Payments {
    #refreshButton = null;
    #issueList = null;
    
    constructor() {
        // Search tabulator elements
        this.#refreshButton = document.getElementById('payments_refresh_btn');
    }

    loadPaymentIssues() {
        var self = this;

        this.#refreshButton.disabled = true;

        var postData = {
            ajax_request_action: 'issues',
        };
        $.ajax({
            method: "POST",
            url: "scripts/admin_getTerminalIssues.php",
            data: postData,
            success: function (data, textstatus, jqxhr) {
                if (data.error) {
                    show_message(data.error, 'error');
                    self.#refreshButton.disabled = false;
                }
                if (data.success) {
                    show_message(data.message, 'success');
                }
                payments.drawPaymentIssues(data);
            },
            error: showAjaxError,
        });
    }

    drawPaymentIssues(data) {
        if (this.#issueList !== null) {
            this.#issueList.replaceData(data.issues);
        }
        else {
            this.#issueList = new Tabulator('#paymentsTable', {
                data: data.issues,
                layout: "fitData",
                maxHeight: "800px",
                movableRows: false,
                history: true,
                index: 'name',
                columns: [
                    {title: "Actions", minWidth: 230, formatter: this.issueActions,},
                    {title: "Age<br/>(Mins)", field: "minutes", minWidth: 60, headerSort: true, headerFilter: true, headerWordWrap: true,
                        hozAlign: 'right', headerHozAlign: 'right'},
                    {title: "TID", field: "id", minWidth: 100, headerSort: true, headerFilter: true,  hozAlign: 'right', headerHozAlign: 'right'},
                    {title: "Status", field: "paymentStatus", minWidth: 100, headerSort: true, headerFilter: true, headerWordWrap: true,},
                    {title: "Checkout ID", field: "checkoutId", minWidth: 100, headerSort: true, headerFilter: true, headerWordWrap: true,},
                    {title: "Card Status", field: "cardStatus", headerSort: true, headerFilter: true, headerWordWrap: true,},
                    {title: "Order ID", field: "orderId", minWidth: 100, headerSort: true, headerFilter: true, headerWordWrap: true,},
                    {title: "Create Date", field: "create_date", headerSort: true, headerFilter: true, headerWordWrap: true,},
                    {title: "Complete Date", field: "complete_date", headerSort: true, headerFilter: true, headerWordWrap: true,},
                    {title: "Type", field: "type", headerSort: true, headerFilter: true, headerWordWrap: true, },
                    {title: "Due", field: "withtax",headerSort: false,  hozAlign: 'right', headerHozAlign: 'right', },
                    {title: "Paid", field: "paid",headerSort: false,  hozAlign: 'right', headerHozAlign: 'right', },
                    {title: "Perid", field: "perid",headerSort: false, hozAlign: 'right', headerHozAlign: 'right', },
                    {title: "Full Name", field: "fullName", headerSort: true, headerFilter: true, headerWordWrap: true, },
                    {title: "Payment ID", field: "paymentId",  headerSort: true, headerFilter: true, headerWordWrap: true,},
                    {title: "Card Payment ID", field: "cardPaymentId", headerSort: true, headerFilter: true, headerWordWrap: true,},
                ],
            });
        }
        this.#refreshButton.disabled = false;
    }

// tabulator formatter for the actions column, displays the poll button
// filters for ones that are too recent to poll as they might be active in POS
    issueActions(cell, formatterParams, onRendered) { //plain text value
        'use strict';

        var data = cell.getData();
        var btns = "";

        if (data.minutes > 15) {
            btns += '<button class="btn btn-primary me-1" style = "--bs-btn-padding-y: .0rem; --bs-btn-padding-x: .3rem; --bs-btn-font-size: .75rem;" ' +
                ' onclick="payments.poll(\'' + data.id + '\')">Poll</button>';
        } else {
            btns += 'Too New';
        }

        return btns;
    }

    // poll the payment to update it's status and finish its processing if 'complete'
    poll(transid) {
        // clear the fields
        console.log("Poll of " + transid + " requested");
    }
}
