// Exhibitor Receipt display/email
//  instance of the class must be a javascript variable names exhibitorReceipt
class ExhibitorReceipt {

// items related to requesting space (not approvals)
    #exhibitorRequest = null;
    #exhibitorReveiptBtn = null;
    #receiptContent = null;
    #regionYearId = null;

// init
    constructor() {
        var id = document.getElementById('exhibitor_receipt');
        if (id != null) {
            this.#exhibitorRequest = new bootstrap.Modal(id, {focus: true, backdrop: 'static'});
            this.#exhibitorReveiptBtn = document.getElementById('exhibitor_receipt_btn');
            this.#receiptContent = document.getElementById('receiptHtml');
        }
    }

// showReceipt - open the receipt modal and fetch it's contents

    showReceipt(regionYearId) {
        var spaceHtml = '';
        var regionName = '';

        this.#regionYearId = regionYearId;

        //console.log("open receipt modal for id =" + regionYearId);
        var region = exhibits_spaces[regionYearId];

        if (!region)
            return;

        var regionList = region_list[regionYearId];
        if (config['debug'] & 1) {
            console.log("regionList");
            console.log(regionList);
            console.log("Region Spaces");
            console.log(region);
        }

        regionName = regionList.name;
        clear_message('receipt_message_div');
        clear_message();
        var dataobj = {
            regionYearId: regionYearId,
            'type': config['portalType'],
            'name': config['portalName'],
        };
        var url = 'scripts/exhibitorReceipt.php';
        var _this = this;
        $.ajax({
            url: url,
            data: dataobj,
            method: 'POST',
            success: function (data, textstatus, jqxhr) {
                if (config['debug'] & 1)
                    console.log(data);
                if (data['error'] !== undefined) {
                    show_message(data['error'], 'error', 'receipt_message_div');
                    return;
                }
                if (data['success'] !== undefined) {
                    show_message(data['success'], 'success', receipt_message_div);
                }
                if (data['warn'] !== undefined) {
                    show_message(data['warn'], 'warn', 'receipt_message_div');
                }
                _this.drawReceipt(data);
            },
            error: showAjaxError
        })
    }

    // drawReceipt - draw the receipt on the screen
    drawReceipt(data) {
        var html = data['receipt_html'];
        this.#receiptContent.innerHTML = html;

        this.#exhibitorRequest.show();
    }
}

exhibitorReceipt = null;
// init
function exhibitorReceiptOnLoad() {
    exhibitorReceipt = new ExhibitorReceipt();
}
