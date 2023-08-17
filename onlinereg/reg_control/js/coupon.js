//import { TabulatorFull as Tabulator } from 'tabulator-tables';
// Coupon Class - all ;unctions and data related to processing / displaying a coupon on the reg_control/coupon
// Coupons includes: selection, display, calculation, cart support

var coupons = null;

// wrappers
function usedClicked(e, cell) {
    "use strict";

    coupons.usedClicked(cell);
}

function keysClicked(e, cell) {
    "use strict";

    coupons.keysClicked(cell);
}

function rulesClicked(e, cell) {
    "use strict";

    coupons.rulesClicked(cell);
}

window.onload = function initpage() {
    "use strict";

    coupons = new Coupon();

    var script = "scripts/getCouponData.php";
    $.ajax({
        url: script,
        method: 'POST',
        data: 'type=all',
        success: function (data, textStatus, jhXHR) {
            if (data['status'] == 'error')
                show_message($data['error'], 'error');
            else {
                coupons.initData(data);
            }
        },
        error: function (jqXHR, textStatus, errorThrown) {
            showError("ERROR in " + script + ": " + textStatus, jqXHR);
            return false;
        }
    });
}

class Coupon {
// coupon items
    #couponTable = null;
    #couponData = null;
    #detailTable = null;

// coupon data
    #curCoupon = null;

// DOM Objects
    #detailsDIV = null;

// initialization
    constructor() {
        "use strict";
        // dom elements
        this.#detailsDIV = document.getElementById('detailTable');
    }

    initData(data) {
        "use strict";

        // build initial tabulator table
        var couponArray = data['coupons'];
        this.#couponData = new Array();
        for (var row of couponArray) {
            this.#couponData[row['id']] = row;
        }

        this.draw(couponArray);
    }

    // tabulator display and edit functions
    #addEditIcon(cell, formatterParams, onRendered) { //plain text value
        "use strict";

        var id = cell.getData().id;
        return '<button type="button" class="btn btn-sm btn-secondary pt-0 pb-0" style="--bs-btn-font-size: 75%;" onclick="coupons.EditCoupon(' + id + ')">Edit</button>';
    }

    usedClicked(cell) {
        "use strict";

        var used = cell.getValue();
        if (used != null && (used > 0 || used == 'FULL')) {
            // get the usage of this coupon
            this.#curCoupon = cell.getRow().getCell('id').getValue();
            this.#showUsed();
        } else {
            this.#clearUsed();
        }
    }

    keysClicked(cell) {
        "use strict";

        var keys = cell.getValue();
        if (keys != null && keys > 0) {
            // get the usage of this coupon
            this.#curCoupon = cell.getRow().getCell('id').getValue();
            this.#showKeys();
        } else {
            this.#clearUsed();
        }
    }

    rulesClicked(cell) {
        this.#clearUsed(false);
        this.#detailsDIV.innerHTML = this.#couponDetails(cell.getData());
    }
    draw(couponArray) {
        "use strict";

       this.#clearUsed(true);

        this.#couponTable = new Tabulator('#couponTable', {
            maxHeight: "500px",
            movableRows: false,
            history: false,
            pagination: true,
            paginationSize: 10,
            paginationSizeSelector: [10, 25, 50, 100, 250, true], //enable page size select element with these options
            data: couponArray,
            layout: "fitDataTable",
            columns: [
                {title: "Edit", formatter: this.#addEditIcon, hozAlign:"center", },
                {title: "ID", field: "id", headerSort: true, cellClick: rulesClicked, },
                {title: "Code", field: "code", headerSort: true, headerFilter: true,},
                {title: "Name", field: "name", headerSort: true, headerFilter: true,},
                {title: "OneUse", field: "oneUse", headerSort: true, formatter: "tickCross", headerFilter: "tickCross", headerFilterParams: {tristate: true},},
                {title: "Type", field: "couponType", headerSort: true, headerFilter: true,},
                {title: "Discount", field: "discount", headerSort: true, headerFilter: true,},
                {title: "Membership Type", field: "shortname", headerFilter: true, headerWordWrap: true,},
                {title: "Starts", field: "dispStart", headerSort: true, headerFilter: true,},
                {title: "Ends", field: "dispEnd", headerSort: true, headerFilter: true,},
                {title: "#Used", field: "full", headerSort: true, headerFilter: true, cellClick: usedClicked, },
                {title: "#Keys", field: "keycount", cellClick: keysClicked, },
                {field: "limitMemberships", visible: false, },
                {field: "maxMemberships", visible: false, },
                {field: "maxTransaction", visible: false, },
                {field: "memId", visible: false, },
                {field: "minMemberships", visible: false, },
                {field: "minTransaction", visible: false, },
                {field: "shortname", visible: false, },
            ]
        });
    }
    EditCoupon(id) {
        "use strict";

        console.log("asked to edit " + id);
        console.log(this.#couponData[id]);
    }

    // detail table items

    #clearUsed(all = false) {
        if (this.#detailTable != null) {
            this.#detailTable.destroy();
            this.#detailTable = null;
        }

        if (all)
            this.#curCoupon = null;

        this.#detailsDIV.innerHTML = '';
    }
    #showUsed() {
        "use strict";

        this.#clearUsed(false);

        if (this.#curCoupon == null)
            return;

        var script = "scripts/getCouponUsage.php";
        var data = { id: this.#curCoupon, };
        $.ajax({
            url: script,
            method: 'POST',
            data: data,
            success: function (data, textStatus, jhXHR) {
                if (data['status'] == 'error')
                    show_message($data['error'], 'error');
                else {
                    coupons.drawUsed(data);
                }
            },
            error: function (jqXHR, textStatus, errorThrown) {
                showError("ERROR in " + script + ": " + textStatus, jqXHR);
                return false;
            }
        });
    }

    #showKeys() {
        "use strict";

        this.#clearUsed(false);

        if (this.#curCoupon == null)
            return;

        var script = "scripts/getCouponKeys.php";
        var data = { id: this.#curCoupon, };
        $.ajax({
            url: script,
            method: 'POST',
            data: data,
            success: function (data, textStatus, jhXHR) {
                if (data['status'] == 'error')
                    show_message($data['error'], 'error');
                else {
                    coupons.drawKeys(data);
                }
            },
            error: function (jqXHR, textStatus, errorThrown) {
                showError("ERROR in " + script + ": " + textStatus, jqXHR);
                return false;
            }
        });
    }

    // ajax return of data, draw usage subtable
    drawUsed(data) {
        "use strict";

        var usageData = data['used'];
        if (usageData.length <= 0)
            return;

        var label = "Usage data for Coupon " + usageData[0]['CouponId'] + ": " + usageData[0]['code'] + "(" + usageData[0]['name'] + ")";
        this.#detailTable = new Tabulator('#detailTable', {
            maxHeight: "500px",
            movableRows: false,
            history: false,
            pagination: true,
            paginationSize: 10,
            paginationSizeSelector: [10, 25, 50, 100, 250, true], //enable page size select element with these options
            data: usageData,
            layout: "fitDataTable",
            columns: [
                {
                    title: label, columns: [
                        {title: "TransID", field: "transId", headerSort: true, headerFilter: true,},
                        {title: "Timestamp", field: "complete_date",  headerSort: true, },
                        {title: "Price", field: "price", headerSort: false, },
                        {title: "Discount", field: "couponDiscount", headerSort: false, },
                        {title: "Paid", field: "paid", headerSort: false, },
                        {title: "Perid", field: "perid", headerSort: true, headerFilter: true,},
                        {title: "Last Name", headerWordWrap: true, field: "last_name", headerSort: true, headerFilter: true,},
                        {title: "First Name", headerWordWrap: true, field: "first_name", headerSort: true, headerFilter: true,},
                        {title: "Badge Name", headerWordWrap: true, field: "badge_name", headerSort: true, headerFilter: true,},
                        {title: "GUID", field: "guid", headerSort: false, },
                    ],
                },
            ],
        });
    }

    // ajax return of data, draw usage subtable
    drawKeys(data) {
        "use strict";

        var keyData = data['keys'];
        if (keyData.length <= 0)
            return;

        var label = "Key data for Coupon " + keyData[0]['couponId'] + ": " + keyData[0]['code'] + "(" + keyData[0]['name'] + ")";
        this.#detailTable = new Tabulator('#detailTable', {
            maxHeight: "500px",
            movableRows: false,
            history: false,
            pagination: true,
            paginationSize: 10,
            paginationSizeSelector: [10, 25, 50, 100, 250, true], //enable page size select element with these options
            data: keyData,
            layout: "fitDataTable",
            columns: [
                {
                    title: label, columns: [
                        {title: "Key ID", field: "id", headerSort: true, headerFilter: false,},
                        {title: "Create Timestamp", headerWordWrap: true, field: "createTS",  headerSort: true, },
                        {title: "Create By", headerWordWrap: true, field: "createBy",  headerSort: true, headerFilter: true, },
                        {title: "Perid", field: "perid", headerSort: true, headerFilter: true,},
                        {title: "Last Name", headerWordWrap: true, field: "last_name", headerSort: true, headerFilter: true,},
                        {title: "First Name", headerWordWrap: true, field: "first_name", headerSort: true, headerFilter: true,},
                        {title: "Badge Name", headerWordWrap: true, field: "badge_name", headerSort: true, headerFilter: true,},
                        {title: "GUID", field: "guid", headerSort: false, },
                        {title: "Used Timestamp", headerWordWrap: true, field: "useTS",  headerSort: true, },
                        {title: "Used By", field: "usedBy", headerSort: true, headerFilter: true,},
                        {title: "Last Name", headerWordWrap: true, field: "u_last_name", headerSort: true, headerFilter: true,},
                        {title: "First Name", headerWordWrap: true, field: "u_first_name", headerSort: true, headerFilter: true,},
                        {title: "Badge Name", headerWordWrap: true, field: "u_badge_name", headerSort: true, headerFilter: true,},
                    ],
                },
            ],
        });
    }

    // couponDetails - a text line of the restrictions for this coupon
    // fields: minMemberships, maxMemberships, minTransaction, maxTransaction, maxRedemption, redeemedCount
    //
    #couponDetails(coupon) {
        var html = '';
        var label = 'non zero dollar';

        if (coupon['couponType'] == '$mem' || coupon['couponType'] == '%mem') {
            html += "<li>This coupon only applies to memberships, not add-ons</li>";
        }
        if (coupon['couponType'] == '$off' || coupon['couponType'] == '%off') {
            html += "<li>This coupon only applies to the cost of memberships in the cart, not add-ons</li>";
        }
        if (coupon['couponType'] == 'price') {
            label = coupon['shortname'];
            html += "<li>This coupon applies a special price of " + Number(coupon['discount']).toFixed(2) + " to " +
                label + " memberships in the cart.</li>";
        }
        if (coupon['minMemberships']) {
            if (coupon['minMemberships'] > 1)
                html += '<li>You must buy at least ' + coupon['minMemberships'] + " " + label + " memberships</li>\n";
        }
        if (coupon['maxMemberships']) {
            html += '<li>This coupon will only discount up to ' + coupon['maxMemberships'] + " " + label + " memberships</li>\n";
        }

        if (coupon['minTransaction']) {
            html += '<li>Your pre-discount cart value must be at least ' + coupon['minTransaction'] + "</li>\n";
        }
        if (coupon['maxTransaction']) {
            html += '<li>The discount will only apply to the first ' + coupon['maxTransaction'] + " of the cart</li>\n";
        }

        if (coupon['memId']) {
            html += '<li>Only valid on ';
            var plural = 's'
            if (coupon['limitMemberships']) {
                if (coupon['limitMemberships'] == 1) {
                    html += 'one ';
                    plural = '';
                } else {
                    html += coupon['limitMemberships'] + ' ';
                }
            } else
                html += ''
            html += coupon['shortname'] + ' membership' + plural + "</li>\n";
        }

        return "Coupon Details for coupon code '" + coupon['code'] + "': " + coupon['name'] + "\n<ul>\n" + html + "</ul>\n";
    }
/*
    // get functions
    getMinMemberships() {
        if (this.#curCoupon == null)
            return 0;

        if (this.#curCoupon['minMemberships'] == null)
            return 0;

        return this.#curCoupon['minMemberships'];
    }

    getMaxMemberships() {
        if (this.#curCoupon == null)
            return 999999999;

        if (this.#curCoupon['maxMembersiphs'] == null)
            return 999999999;

        return this.#curCoupon['maxMemberships'];
    }

    getLimitMemberships() {
        if (this.#curCoupon == null)
            return 999999999;

        if (this.#curCoupon['limitMemberships'] == null)
            return 999999999;

        return this.#curCoupon['limitMemberships'];
    }

    getMinCart() {
        if (this.#curCoupon == null)
            return 0;

        if (this.#curCoupon['minTransactions'] == null)
            return 0;

        return this.#curCoupon['minTransaction'];
    }

    getMaxCart() {
        if (this.#curCoupon == null)
            return 999999999;

        if (this.#curCoupon['maxTransaction'] == null)
            return 999999999;

        return this.#curCoupon['maxTransaction'];
    }

    getCouponCode() {
        if (this.#curCoupon == null)
            return null;

        return this.#curCoupon['code'];
    }
    getCouponSerial() {
        if (this.#curCoupon == null)
            return null;

        return this.#curCoupon['guid'];
    }
    getMemGroup() {
        if (this.#curCoupon == null)
            return null;

        if (this.#curCoupon['memId'] == null)
            return null;

        return this.#curCoupon['memGroup'];
    }

 */
}
