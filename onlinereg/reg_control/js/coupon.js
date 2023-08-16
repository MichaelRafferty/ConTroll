//import { TabulatorFull as Tabulator } from 'tabulator-tables';
// Coupon Class - all ;unctions and data related to processing / displaying a coupon on the reg_control/coupon
// Coupons includes: selection, display, calculation, cart support

var coupons = null;
var cur_coupon = null;

window.onload = function initpage() {
    var script = "scripts/getCouponData.php";
    $.ajax({
        url: script,
        method: 'POST',
        data: 'type=all',
        success: function (data, textStatus, jhXHR) {
            if (data['status'] == 'error')
                show_message($data['error'], 'error');
            else {
                coupons = new Coupon(data);
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

// coupon data
    #curCoupon = null;

// initialization
    constructor(data) {
        "use strict";
        // dom elements

        // build initial tabulator table
        this.draw(data);
    }

    // tabulator display and edit functions
    #addEditIcon(cell, formatterParams, onRendered) { //plain text value
        var id = cell.getData().id;
        return '<button type="button" class="btn btn-sm btn-secondary pt-0 pb-0" style="--bs-btn-font-size: 75%;" onclick="coupons.EditCoupon(' + id + ')">Edit</button>';
    }
    draw(data) {
        if (this.#couponTable) {
            this.#couponTable.destroy();
            this.#couponTable = null;
        }

        this.#couponTable = new Tabulator('#couponTable', {
            maxHeight: "800px",
            movableRows: false,
            history: true,
            data: data['coupons'],
            layout: "fitDataTable",
            columns: [
                {title: "Edit", formatter: this.#addEditIcon, hozAlign:"center", },
                {title: "ID", field: "id", headerSort: true,},
                {title: "Code", field: "code", headerSort: true, headerFilter: true,},
                {title: "Name", field: "name", headerSort: true, headerFilter: true,},
                {title: "OneUse", field: "oneUse", headerSort: true, formatter: "tickCross", headerFilter: "tickCross", headerFilterParams: {tristate: true},},
                {title: "Type", field: "couponType", headerSort: true, headerFilter: true,},
                {title: "Discount", field: "discount", headerSort: true, headerFilter: true,},
                {title: "Membership Type", field: "shortname", headerFilter: true, headerWordWrap: true,},
                {title: "Starts", field: "dispStart", headerSort: true, headerFilter: true,},
                {title: "Ends", field: "dispEnd", headerSort: true, headerFilter: true,},
                {title: "#Used", field: "full", headerSort: true, headerFilter: true,},
                {title: "#Keys", field: "keycount",},

            ]
        });
    }
    EditCoupon(id) {
        console.log("asked to edit " + id);
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
