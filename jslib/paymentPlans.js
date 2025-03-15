// common Payment Plana - routines for creating, managing and paying payment pland

var paymentPlans = null;

class PaymentPlans {
    // payment plan rules
    #paymentPlan = null;
    #matchingPlans = {};

    // account holder payment plan items
    #payorPlan = null;
    #payorPlanPayments = null
    #payPlanModal = null;
    #payPlanTitle = null;
    #payPlanBody = null;
    #payPlanSubmit = null;
    #planPaymentAmount = null;
    #planPaymentMinPayment = null;
    #planPaymentBalanceDue = null;
    #planPaymentPayorPlanId = null;
    #planPaymentPayorPlanName = null;

    // customize plan items
    #customizePlanModal = null;
    #customizePlanTitle = null;
    #customizePlanBody = null;
    #customizePlanSubmit = null;
    #computedPlan = null;
    #computedOrig = null;

    constructor() {
        this.#matchingPlans = {};
        var id = document.getElementById('customizePlanModal');
        if (id) {
            this.#customizePlanModal = new bootstrap.Modal(id, {focus: true, backdrop: 'static'});
            this.#customizePlanTitle = document.getElementById('customizePlanTitle');
            this.#customizePlanBody = document.getElementById('customizePlanBody');
            this.#customizePlanSubmit = document.getElementById('customizePlanSubmit');
        }
        var id = document.getElementById('payPlanModal');
        if (id) {
            this.#payPlanModal = new bootstrap.Modal(id, {focus: true, backdrop: 'static'});
            this.#payPlanTitle = document.getElementById('payPlanTitle');
            this.#payPlanBody = document.getElementById('payPlanBody');
            this.#payPlanSubmit = document.getElementById('payPlanSubmit');
        }
    }

    // plansEligible: for which plans is a current cart eligible
    plansEligible(purchased = null, space = null) {
        var nonPlanAmt;
        var planAmt;
        var notInPlanItems;
        // any plans in the system?
        var keys = Object.keys(paymentPlanList);
        if (keys.length == 0)
            return false;

        var matched = 0;
        // how much is owed by the right type:

        for (var prow in keys) {
            var plan = paymentPlanList[keys[prow]];

            // compute the plan and the not plan amount for this plan
            planAmt = 0;
            nonPlanAmt = 0;
            notInPlanItems = '';

            if (purchased != null && purchased.length > 0) {
                for (var mrow in membershipsPurchased) {
                    var mem = membershipsPurchased[mrow];
                    if (mem.status != 'unpaid') // can't add anything without a balance due to a plan, and plan is already covered in a different plan
                        continue;

                    var eligible = false;
                    if (plan.catList != null) {
                        if (plan.catListArray.indexOf(mem.memCategory.toString()) != -1)
                            eligible = true;
                    }

                    if (plan.memList != null) {
                        if (plan.memListArray.indexOf(mem.id.toString()) == -1)
                            eligible = true;
                    }

                    if (plan.excludeList != null) {
                        if (plan.excludeListArray.indexOf(mem.id.toString()) != -1)
                            eligible = false;
                    }

                    // ignore coupon discount, it's handled later in the calc
                    if (eligible) {
                        planAmt += Number(mem.price) - Number(mem.paid);
                    } else {
                        nonPlanAmt += Number(mem.price) - Number(mem.paid);
                        notInPlanItems += '<br/>' + mem.fullname + ', ' + mem.label + ', ' +
                            (Number(mem.price) - (Number(mem.paid) + Number(mem.couponDiscount))).toFixed(2);
                    }
                }
            }

            if (space != null) {
                console.log("Not yet for space payments in plan");
            }

            if (typeof coupon != 'undefined' && coupon != null) {
                var cartDiscount = 0;
                if (typeof portal !== 'undefined' && portal != null) {
                    var portalDiscount = coupon.portalComputeCouponDiscount();
                    cartDiscount = portalDiscount.discount;
                }
                planAmt -= cartDiscount;
            }

            planAmt = Math.round(planAmt * 100.0) / 100.0;
            nonPlanAmt = Math.round(nonPlanAmt * 100.0) / 100.0;

            // now handle the rules for this plan once we have all the amounts
            var downPayment = downPayment = Math.round(Number(plan.downPercent) * planAmt) / 100.0;
            if (Number(plan.downAmt) > downPayment)
                downPayment = Number(plan.downAmt);
            // can they reduce the plan down payment by the amount due today for non plan amounts
            if (plan.downIncludeNonPlan == 'Y') {
                downPayment -= nonPlanAmt;
                if (downPayment < 0)
                    downPayment = 0;
            }

            if ((downPayment + Number(plan.minPayment)) >= planAmt) // not eligible for plan
                continue;

            var dueToday = nonPlanAmt + downPayment;
            var balanceDue = planAmt - downPayment;

            // compute maximum number of payments allowd
            // first get the number of weeks between now and the pay by date
            var pbDate = new Date(plan.payByDate);
            var today = new Date();
            var diff = Math.floor((pbDate.getTime() - today.getTime()) / (1000 * 3600 * 24)); // milliseconds to days and no fractional days

            var numPayments = Math.floor(diff / 7);  // max one per week
            if (numPayments <= 0)
                continue;   // has to be time for at least one payment beyond down payment

            // limit to the max allowed for this plan.
            if (numPayments > Number(plan.numPaymentMax))
                numPayments = Number(plan.numPaymentMax);
            var maxPayments = numPayments; // at this point this is time to payoff limited
            // limit to min payment amount, but if allowed allow the last payment to be less
            var computedNumPayments = Math.floor(balanceDue / Number(plan.minPayment));
            if (computedNumPayments * Number(plan.minPayment) != balanceDue && plan.lastPaymentPartial == 'Y') {
                // allow one more partial payment
                computedNumPayments++;
            }

            if (computedNumPayments > maxPayments)
                computedNumPayments = maxPayments; // limit it to fitting in time frame if the payment size allows too many payments
            else
                maxPayments = computedNumPayments; // limit max to number of payments by amount limit

            numPayments = computedNumPayments; // set the payments to the amount divisor limited to max
            // has to be at least one payment
            if (maxPayments < 1)
                continue;

            // days between payments, range: 7-30
            var daysBetween = Math.floor(diff / numPayments);
            if (daysBetween > 30)
                daysBetween = 30;
            else if (daysBetween < 7)
                daysBetween = 7;

            var paymentAmt = Math.ceil(100 * balanceDue / numPayments) / 100;
            var finalPaymentAmt = paymentAmt;
            if (paymentAmt < Number(plan.minPayment)) {
                paymentAmt = Number(plan.minPayment);
                finalPaymentAmt = balanceDue - (numPayments - 1) * paymentAmt;
            }

            this.#matchingPlans[plan.id] = {
                id: plan.id, plan: plan,
                planAmt: planAmt, nonPlanAmt: nonPlanAmt, downPayment: downPayment, dueToday: dueToday, maxPayments: maxPayments, daysBetween: daysBetween,
                balanceDue: balanceDue, paymentAmt: paymentAmt, finalPaymentAmt: finalPaymentAmt, numPayments: numPayments,
                notInPlanItems: notInPlanItems != '' ?  'Not in Plan:' + notInPlanItems : '',
            }
            matched++;
        }
        if (config.debug & 4) console.log(this.#matchingPlans);
        return matched > 0;
    }

    // getMatchingPlans - return the array of plans as a copy so it cannot modify the original ones due to javascript pass object by reference
    getMatchingPlans() {
        if (this.#matchingPlans != null)
            return make_copy(this.#matchingPlans);

        return null;
    }

    // isMatchingPlans - return true/false if there are any matching payment plans available for this 'cart'
    isMatchingPlans() {
        var keys = Object.keys(this.#matchingPlans);
        return keys.length > 0;
    }

    // getMatchingPlansHTML - return the HTML for the modal popup for choose plan
    getMatchingPlansHTML(from) {
        var keys = Object.keys(this.#matchingPlans);
        if (keys.length == 0)
            return '';

        var html = '<div class="row mt-2"><div class="col-sm-12"><b>Payment Plans Available:</b></div>' + `
    <div class="row">
        <div class="col-sm-2"></div>
        <div class="col-sm-1" style='text-align: right;'><b>Non Plan Amount</b></div>
        <div class="col-sm-1" style='text-align: right;'><b>Plan Amount</b></div>
        <div class="col-sm-1" style='text-align: right;'><b>Minimum Down Payment</b></div>
        <div class="col-sm-1" style='text-align: right;'><b>Due Today</b></div>
        <div class="col-sm-1" style='text-align: right;'><b>Balance Due</b></div>
        <div class="col-sm-1" style='text-align: right;'><b>Maximum Number Payments</b></div>
        <div class="col-sm-1" style='text-align: right;'><b>Max Days Between Payments</b></div>
        <div class="col-sm-1" style='text-align: right;'><b>Minimum Payment Amount</b></div>
        <div class="col-sm-1" style='text-align: right;'><b>Final Payment Amount</b></div>
        <div class="col-sm-1"><b>Must Pay In Full By</b></div>
    </div>
`;

        for (var row in keys) {
            var match = this.#matchingPlans[keys[row]];
            var plan = match.plan;

            html += `
    <div class="row">
        <div class="col-sm-2">
`;
            if (plan.modify == 'Y') {
                html += '<button class="btn btn-sm btn-secondary pt-0 pb-0" onclick="paymentPlans.customizePlan(' +
                    keys[row] + ",'" + from + "'" + ');">' +
                    'Customize Payment Plan:<br/>' + plan.name + '</button>';
            } else {
                html += '<button class="btn btn-sm btn-secondary pt-0 pb-0" onclick="paymentPlans.selectPlan(' +
                    keys[row] + ",'" + from + "'" + ');">' +
                    'Select Payment Plan:<br/>' + plan.name + '</button>';
            }
            html += `
        </div>
        <div class="col-sm-1" style='text-align: right;'>` + match.nonPlanAmt.toFixed(2) + `</div>
        <div class="col-sm-1" style='text-align: right;'>` + match.planAmt.toFixed(2) + `</div>
        <div class="col-sm-1" style='text-align: right;'>` + match.downPayment.toFixed(2) + `</div>
        <div class="col-sm-1" style='text-align: right;'>` + match.dueToday.toFixed(2) + `</div>
        <div class="col-sm-1" style='text-align: right;'>` + match.balanceDue.toFixed(2) + `</div>
        <div class="col-sm-1" style='text-align: right;'>` + match.maxPayments + `</div>
        <div class="col-sm-1" style='text-align: right;'>` + match.daysBetween + `</div>
        <div class="col-sm-1" style='text-align: right;'>` + match.paymentAmt.toFixed(2) + `</div>
        <div class="col-sm-1" style='text-align: right;'>` + match.finalPaymentAmt.toFixed(2) + `</div>
        <div class="col-sm-1">` + plan.payByDate + `</div>
    </div>
    <div class="row` + (match.notInPlanItems != '' ? '' : ' mb-3') + `">
        <div class="col-sm-1"></div>
        <div class="col-sm-10">` + plan.description + `</div>
    </div>
`;
            if (match.notInPlanItems != '') {
                html += '<div class="row mb-4"><div class="col-sm-2"></div><div class="col-sm-10">' + match.notInPlanItems + '</div></div>';
            }
        }
        return html;
    }

    // select this plan - build data structures and create the plan
    selectPlan(planId, from) {
        clear_message();
        clear_message('customizePlanMessageDiv');

        var match = make_copy(this.#matchingPlans[planId]);
        var plan = match.plan;
        if (config.debug & 2) {
            console.log('planId: ' + planId + ', from: ' + from);
            console.log("this.#matchingPlans");
            console.log(this.#matchingPlans);
            console.log("match");
            console.log(match);
        }


        match.totalAmountDue = (match.nonPlanAmt + match.planAmt).toFixed(2) + ``;
        match.currentPayment = (match.nonPlanAmt + match.downPayment).toFixed(2) + ``;
        match.daysBetween = match.daysBetween;
        match.paymentAmt = match.paymentAmt.toFixed(2) + ``;
        match.finalPaymentAmt = match.finalPaymentAmt.toFixed(2) + ``;
        match.balanceDue = match.balanceDue.toFixed(2) + ``;
        match.downPayment = match.downPayment.toFixed(2) + ``;
        match.new = true;
        portal.makePayment(match);
    }

    // customize plans items - fill in the modal and display it
    customizePlan(planId, from) {
        clear_message();
        clear_message('customizePlanMessageDiv');

        this.#computedPlan = make_copy(this.#matchingPlans[planId]);
        this.#computedOrig = make_copy(this.#matchingPlans[planId]);
        var match = this.#computedPlan;
        var plan = match.plan;
        if (config.debug & 2) {
            console.log('planId: ' + planId + ', from: ' + from);
            console.log("this.#matchingPlans");
            console.log(this.#matchingPlans);
            console.log("match");
            console.log(match);
        }

        if (this.#customizePlanModal == null) {
            switch (from) {
                case 'portal':
                    show_message("Plans not available right now", 'warn', 'payDueMessageDiv');
                    break;
            }
            return;
        }

        var html = '';

        // buld contents of page
        html += `
        <div class="row">
            <div class="col-sm-auto"><h3>Customize the ` + plan.name + ` payment plan </h3></div>
        </div>
        <div class="row">
            <div class="col-sm-1"></div>
            <div class="col-sm-10">` + plan.description + `</div>
        </div>
`;
        if (match.notInPlanItems != '') {
            html += '<div class="row mb-2"><div class="col-sm-2"></div><div class="col-sm-10">' + match.notInPlanItems + '</div></div>';
        }
        html += `
    <div class="row">
        <div class="col-sm-1" style='text-align: right;'><b>Non Plan Amount</b></div>
        <div class="col-sm-1" style='text-align: right;'><b>Plan Amount</b></div>
        <div class="col-sm-1" style='text-align: right;'><b>Down Payment</b></div>
        <div class="col-sm-1" style='text-align: right;'><b>Due Today</b></div>
        <div class="col-sm-1" style='text-align: right;'><b>Balance Due</b></div>
        <div class="col-sm-1" style='text-align: right;'><b>Number Payments</b></div>
        <div class="col-sm-1" style='text-align: right;'><b>Days Between</b></div>
        <div class="col-sm-1" style='text-align: right;'><b>Payment Amount</b></div>
        <div class="col-sm-1" style='text-align: right;'><b>Final Payment Amount</b></div>
        <div class="col-sm-1"><b>Must Pay In Full By</b></div>
    </div>
    <form id="customizePlanForm" class='form-floating' action='javascript:void(0);'>
    <div class="row">
        <div class="col-sm-1" style='text-align: right;'>` + match.nonPlanAmt.toFixed(2) + `</div>
        <div class="col-sm-1" style='text-align: right;'>` + match.planAmt.toFixed(2) + `</div>
        <div class="col-sm-1" style='text-align: right;'>
            <input type="number" class='no-spinners' inputmode="numeric" id="downPayment" name="downPayment" style="width: 8em;" placeholder="down payment" ` +
            'min="' + match.downPayment.toFixed(2) + '" max="' + match.planAmt.toFixed(2) + '" value="' + match.downPayment.toFixed(2) +
            `" onchange="paymentPlans.recompute('p');"/>
        </div>
        <div class="col-sm-1" style='text-align: right;' id="dueToday">` + match.dueToday.toFixed(2) + `</div>
        <div class="col-sm-1" style='text-align: right;' id="balanceDue">` + match.balanceDue.toFixed(2) + `</div>
        <div class="col-sm-1" style='text-align: right;'>`;
        if (match.maxPayments > 1) {
            html += `<input type="number" class='no-spinners' inputmode="numeric" id="numPayments" name="numPayments" style="width: 3em;" placeholder="max pmts" ` +
                'min="1" max="' + match.maxPayments + '" value="' + match.numPayments + `" onchange="paymentPlans.recompute('n');"/>`;
        } else {
            html += match.maxPayments;
        }
        html += `</div>
        <div class="col-sm-1" style='text-align: right;'>
            <input type="number" class='no-spinners' inputmode="numeric" id="daysBetween" name="daysBetween" style="width: 3em;" placeholder="days" ` +
            'min="7" max="' + match.daysBetween + '" value="' + match.daysBetween + `" onchange="paymentPlans.recompute('d');"/>
        </div>
        <div class="col-sm-1" style='text-align: right;' id="paymentAmt">` + match.paymentAmt.toFixed(2) + `</div>
        <div class="col-sm-1" style='text-align: right;' id="finalPaymentAmt">` + match.finalPaymentAmt.toFixed(2) + `</div>
        <div class="col-sm-1">` + plan.payByDate + `</div>
    </div>
    <div class="row">
        <div class="col-sm-2"></div>
        <div class="col-sm-1" style='text-align: right;'>Min: ` + this.#computedOrig.downPayment.toFixed(2) + `</div>
        <div class="col-sm-2"></div>
        <div class="col-sm-1" style='text-align: right;'>`;
        if (match.maxPayments > 1) {
            html += 'Max: ' + this.#computedOrig.maxPayments;
        }
        html += `</div>
        <div class="col-sm-1" style='text-align: right;'>7-30</div>
    </div>
    </form>
</div>
`;

        switch (from) {
            case 'portal':
                portal.closePaymentDueModal();
                break;
        }

        this.#computedPlan.totalAmountDue = (match.nonPlanAmt + match.planAmt).toFixed(2) + ``;
        this.#computedPlan.currentPayment = (match.nonPlanAmt + match.downPayment).toFixed(2) + ``;
        this.#computedPlan.numPayments = match.maxPayments;
        this.#computedPlan.daysBetween = match.daysBetween;
        this.#computedPlan.paymentAmt = match.paymentAmt.toFixed(2) + ``;
        this.#computedPlan.balanceDue = match.balanceDue.toFixed(2) + ``;
        this.#computedPlan.downPayment = match.downPayment.toFixed(2) + ``;

        this.#customizePlanBody.innerHTML = html;
        this.#customizePlanSubmit.innerHTML = 'Create Plan and pay amount due today of ' + this.#computedPlan.currentPayment;
        this.#customizePlanModal.show();
    }

    // recompute - onchange from customize payment plan modal popup - when one field changes, recompute what else changes on the screem and update the class variables
    recompute(field) {
        clear_message('customizePlanMessageDiv');

        var downPaymentField = document.getElementById("downPayment");
        var balanceDueField = document.getElementById("balanceDue");
        var dueTodayField = document.getElementById("dueToday");
        var numPaymentsField = document.getElementById("numPayments");
        var daysBetweenField = document.getElementById("daysBetween");
        var paymentAmtField = document.getElementById("paymentAmt");
        var finalPaymentField = document.getElementById("finalPaymentAmt");
        var plan = this.#computedOrig.plan;
        var paymentAmt = this.#computedPlan.paymentAmt;
        var finalPaymentAmt = this.#computedPlan.finalPaymentAmt;
        var balanceDue = this.#computedPlan.balanceDue;
        var maxPayments = this.#computedPlan.maxPayments;

        var down = downPaymentField.value;
        var numPayments = numPaymentsField.value;
        var days = daysBetweenField.value;
        var messageHTML = '';

        if (config.debug & 4) {
            console.log('field: ' + field + ', days: ' + days + ', numPayments: ' + numPayments + ', days: ' + days);
        }

        var pbDate = new Date(this.#computedOrig.plan.payByDate);
        var today = new Date();
        var diff = Math.floor((pbDate.getTime() - today.getTime()) / (1000 * 3600 * 24)); // milliseconds to days and no fractional days

        // now recompute based on what they changed, trying to old that value to what is possible
        switch (field) {
            case 'p': // down payment
                // validate range
                if (down < this.#computedOrig.downPayment) {
                    down = this.#computedOrig.downPayment;
                    messageHTML += "Adjusted down payment to not be lower than " + Number(this.#computedOrig.downPayment).toFixed(2) + "<br/>";
                }
                if (down > (this.#computedOrig.planAmt - plan.minPayment)) { // force at least one payment of the minimum payment amount
                    down = this.#computedOrig.planAmt - plan.minPayment;
                    messageHTML += "Down payment adjusted to allow for one payment greater than the plan minimum payment of " + Number(plan.minPayment).toFixed(2) + "<br/>";
                }
                // recompute balance due
                balanceDue = this.#computedPlan.planAmt - down;
                // recompute number of payments max
                var computedNumPayments = Math.floor(balanceDue / plan.minPayment);
                if (computedNumPayments * Number(plan.minPayment) != balanceDue && plan.lastPaymentPartial == 'Y') {
                    // allow one more partial payment
                    computedNumPayments++;
                }
                // if this is less payments than already set for this days between, lower the number of payments
                if (computedNumPayments < numPayments)
                    numPayments = computedNumPayments;
                // with new down payment, compute payment amount
                paymentAmt = Math.ceil(100 * balanceDue / numPayments) / 100;
                if (paymentAmt < plan.minPayment) {
                    paymentAmt = plan.minPayment;
                    numPayments = Math.floor(balanceDue / paymentAmt);
                    if (numPayments * Number(paymentAmt) != balanceDue && plan.lastPaymentPartial == 'Y') {
                        // allow one more partial payment
                        numPayments++;
                    }
                }
                finalPaymentAmt = balanceDue - (numPayments - 1) * paymentAmt;
                break;
            case 'n': // number of payments
                // range check
                if (numPayments < 1) {
                    numPayments = 1;
                    messageHTML += "Number of payments must be at least one<br/>";

                }
                if (numPayments > this.#computedOrig.maxPayments) {
                    numPayments = this.#computedOrig.maxPayments;
                    messageHTML += "Number of payments adjusted to not exceed plan maximum of " + this.#computedOrig.maxPayments + "<br/>";
                }

                // new payment amount
                paymentAmt = Math.ceil(100 * balanceDue / numPayments) / 100;

                // new days between payments
                if ((numPayments * days) > diff)
                    days = Math.floor(diff / numPayments);
                if (days > 30)
                    days = 30;
                if (days < 7)
                    days = 7;

                if (paymentAmt < plan.minPayment)
                    paymentAmt = plqn.minPayment;
                finalPaymentAmt = balanceDue - (numPayments - 1) * paymentAmt;
                break;
            case 'd': // days between
                // validate range
                if (days > 30) {
                    messageHTML += "Adjusted days between to plan maximum<br/>";
                    days = 30;
                }
                if (days < 7) {
                    days = 7;
                    messageHTML += "The shortest interval between payments is one week<br/>";
                }
                // compute number of payments to make this work, limiting to max in range from passed in maxPayments
                var newNumPayments = Math.ceil(diff / days);
                if (newNumPayments < numPayments) {
                    numPayments = newNumPayments;
                    messageHTML += "The days between payments exceeds the pay by date, adjusting the numbrer of payments</br>";
                }
                if (numPayments > this.#computedOrig.maxPayments) {
                    numPayments = this.#computedOrig.maxPayments;
                }
                // given the number of payments, compute the payment amount
                paymentAmt = Math.ceil(100 * balanceDue / numPayments) / 100;
                if (paymentAmt < plan.minPayment) {
                    // too many payments
                    paymentAmt = plqn.minPayment;
                    numPayments = Math.floor(balanceDue / paymentAmt);
                    if (numPayments * Number(paymentAmt) != balanceDue && plan.lastPaymentPartial == 'Y') {
                        // allow one more partial payment
                        numPayments++;
                    }
                }
                finalPaymentAmt = balanceDue - (numPayments - 1) * paymentAmt;
                break;
        }

        // update the plan
        this.#computedPlan.balanceDue = Number(balanceDue).toFixed(2);
        this.#computedPlan.downPayment = Number(down).toFixed(2);
        this.#computedPlan.numPayments = numPayments;
        this.#computedPlan.daysBetween = days;

        // update the screen
        downPaymentField.value = Number(down).toFixed(2);
        balanceDueField.innerHTML = Number(balanceDue).toFixed(2);
        dueTodayField.innerHTML = Number(dueToday).toFixed(2);
        numPaymentsField.value = numPayments;
        daysBetweenField.value = days;
        paymentAmtField.innerHTML = Number(paymentAmt).toFixed(2);
        finalPaymentField.innerHTML = Number(finalPaymentAmt).toFixed(2);

        this.#computedPlan.currentPayment = Number(this.#computedOrig.nonPlanAmt) + Number(down);
        dueTodayField.innerHTML = this.#computedPlan.currentPayment.toFixed(2);
        this.#customizePlanSubmit.innerHTML = 'Create Plan and pay amount due today of ' + this.#computedPlan.currentPayment.toFixed(2);
        if (messageHTML != '')
            show_message(messageHTML, 'warn', 'customizePlanMessageDiv');
    }

    // makePlan - create the plan and bring up the payment screen to pay for it
    makePlan(calledFrom) {
        if (this.#computedPlan == null) {
            if (config.debug) console.log("makeplan(" + calledFrom + ") called, with no computed plan???");
            return; // no plan to pay, why are we here?
        }
        this.#customizePlanModal.hide();
        var plan = make_copy(this.#computedPlan);
        plan.new = true;
        portal.makePayment(plan);
    }

    // payPlan - make a payment against a plan
    payPlan(payorPlanId) {
        if (config.debug) console.log("trying to pay plan " + payorPlanId);
        var payorPlan = payorPlans[payorPlanId];
        var payments = payorPlan['payments'];
        var numPmts = 0;
        var plan = paymentPlanList[payorPlan.planId];
        if (config.debug) {
            console.log("payorPlan");
            console.log(payorPlan);
            if (payments) {
                console.log("payments");
                console.log(payments);
            } else
                console.log('no payments');
        }

        var paymentAmt = Number(payorPlan.minPayment);
        var balanceDue = Number(payorPlan.balanceDue);
        // compute if we're past due
        if (payments) {
            numPmts = Object.keys(payments).length;
        }
        var createDate = new Date(payorPlan.createDate);
        var createTS = createDate.getTime();
        var now = Date.now();
        var daysDiff = Math.ceil((now - createTS) / (24 *3600 * 1000));
        var daysBetween = payorPlan.daysBetween;
        var nextDue = ((numPmts + 1) * daysBetween) - (1 + daysDiff);
        var numDue = 1;
        if (config.debug & 2) {
            console.log('daysDiff = ' + daysDiff);
            console.log('numPmts=' + numPmts);
            console.log('daysBetween=' + daysBetween);
            console.log('nextDue = ' + nextDue);
            console.log('numDue = ' + numDue);
        }

        if (nextDue < 0)  {
            var pastDue = Math.ceil(-nextDue / daysBetween);
            numDue += pastDue;
            if (config.debug & 2) console.log("pastDue = " + pastDue + ", numDue = " + numDue);
        }
        paymentAmt = numDue * paymentAmt;
        if (paymentAmt > balanceDue)
            paymentAmt = balanceDue

        this.#planPaymentAmount = paymentAmt;
        this.#planPaymentMinPayment = Number(payorPlan.minPayment) > balanceDue ? balanceDue : Number(payorPlan.minPayment);
        this.#planPaymentBalanceDue = balanceDue;
        this.#planPaymentPayorPlanId = payorPlanId;
        this.#planPaymentPayorPlanName = plan.name;

        var html = `
    <div class="row mt-3">
        <div class="col-sm-auto"><h3>Make a payment against the ` + plan.name + ` payment plan </h3></div>
    </div>
    <div class="row">
        <div class="col-sm-2" style='text-align: right;'>Balance Due:</div>
        <div class="col-sm-1 ms-2" style='text-align: right;'>` + balanceDue.toFixed(2) + `</div>
    </div>
    <div class="row">
        <div class="col-sm-2" style='text-align: right;'>Payment Amount:</div>
        <div class="col-sm-1 ms-2" style='text-align: right;'>
            <input type="number" class='no-spinners' inputmode="numeric" id="newPaymentAmt" name="newPaymentAmt" style="width: 8em;" placeholder="amount" ` +
            'min="' + paymentAmt.toFixed(2) + '" max="' + balanceDue.toFixed(2) + '" value="' + paymentAmt.toFixed(2) +
            `" onchange="paymentPlans.updatePaymentAmt();"/>
    </div>
`;

        this.#payPlanBody.innerHTML = html;
        this.#payPlanSubmit.innerHTML = 'Make Plan Payment of ' + paymentAmt.toFixed(2);
        this.#payPlanModal.show();
    }


    // update the amount to pay and submit button
    updatePaymentAmt() {
        var id = document.getElementById("newPaymentAmt");
        var paymentAmt = Number(id.value);
        var retVal = true;

        clear_message('payPlanMessageDiv');

        if (paymentAmt < this.#planPaymentMinPayment) {
            show_message("Payment less than minimum allowed, set to " + this.#planPaymentMinPayment.toFixed(2), 'warn', 'payPlanMessageDiv');
            paymentAmt = this.#planPaymentMinPayment.toFixed(2);
            retVal = false;
        } else if (paymentAmt > this.#planPaymentBalanceDue) {
            show_message("Payment greater than balance due, set to " + this.#planPaymentBalanceDue.toFixed(2), 'warn', 'payPlanMessageDiv');
            paymentAmt = this.#planPaymentBalanceDue.toFixed(2);
            retVal = false;
        }

        this.#planPaymentAmount = Number(paymentAmt).toFixed(2);
        id.value = this.#planPaymentAmount;
        this.#payPlanSubmit.innerHTML = 'Make Plan Payment of ' + paymentAmt;
        document.getElementById('paymentPlans')
        return retVal;
    }

    // makePlanPayment - make the plan payment
    makePlanPayment(from) {
        if (this.updatePaymentAmt() == false)
            return;

        this.#payPlanModal.hide();
        switch (from) {
            case 'portal':
                var existingPlan = make_copy(payorPlans[this.#planPaymentPayorPlanId]);
                portal.makePlanPayment(existingPlan, this.#planPaymentPayorPlanName, this.#planPaymentAmount);
                break;
            default:
                console.log('make plan payment: invalid from of ' + from);
                break;
        }
    }
}
