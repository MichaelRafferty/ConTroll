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
                        if (plan.memListArray.indexOf(mem.id.toString()) != -1)
                            eligible = false;
                    }

                    if (eligible) {
                        planAmt += Number(mem.price) - (Number(mem.paid) + Number(mem.couponDiscount));
                    } else {
                        nonPlanAmt += Number(mem.price) - (Number(mem.paid) + Number(mem.couponDiscount));
                    }
                }
            }

            if (space != null) {
                console.log("Not yet for space payments in plan");
            }

            planAmt = Math.round(planAmt * 100.0) / 100.0;
            nonPlanAmt = Math.round(nonPlanAmt * 100.0) / 100.0;

            // now handle the rules for this plan once we have all the amounts
            var downPayment = Math.round(Number(plan.downPercent) * planAmt) / 100.0;
            if (Number(plan.downAmt) > downPayment)
                downPayment = Number(plan.downAmt);

            if ((downPayment + Number(plan.minPayment)) >= planAmt) // not eligible for plan
                continue;

            // compute maximum number of payments allowd
            // first get the number of weeks between now and the pay by date
            var pbDate = new Date(plan.payByDate);
            var today = new Date();
            var diff = Math.floor((pbDate.getTime() - today.getTime()) / (1000 * 3600 * 24)); // milliseconds to days and no fractional days

            var numPayments = Math.floor(diff / 7);  // max one per week
            if (numPayments <= 0)
                continue;   // has to be time for at least one payment beyond down payment

            // limit to the max allowed for this plan.
            if (numPayments > plan.numPaymentMax)
                numPayments = plan.numPaymentMax;

            // days beteeen payments, max 30 days
            var daysBetween = Math.floor(diff / numPayments);
            if (daysBetween > 30)
                daysBetween = 30;

            var balanceDue = planAmt - downPayment;
            var paymentAmt = Math.ceil(100 * balanceDue / numPayments) / 100;

            this.#matchingPlans[plan.id] = {
                id: plan.id, plan: plan,
                planAmt: planAmt, nonPlanAmt: nonPlanAmt, downPayment: downPayment, maxPayments: numPayments, daysBetween: daysBetween,
                minPayment: nonPlanAmt + downPayment, balanceDue: balanceDue, paymentAmt: paymentAmt,
            }
            matched++;
        }
        //console.log(this.#matchingPlans);
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
        return this.#matchingPlans != null;
    }

    // getMatchingPlansHTML - return the HTML for the modal popup for choose plan
    getMatchingPlansHTML(from) {
        if (this.#matchingPlans == null)
            return '';

        var html = '<div class="row mt-2"><div class="col-sm-12"><b>Payment Plans Available:</b></div>' + `
    <div class="row">
        <div class="col-sm-1"></div>
        <div class="col-sm-2"><b>Plan Name</b></div>
        <div class="col-sm-1" style='text-align: right;'><b>Non Plan Amount</b></div>
        <div class="col-sm-1" style='text-align: right;'><b>Plan Amount</b></div>
        <div class="col-sm-1" style='text-align: right;'><b>Down Payment</b></div>
        <div class="col-sm-1" style='text-align: right;'><b>Balance Due</b></div>
        <div class="col-sm-1" style='text-align: right;'><b>Maximim Number Payments</b></div>
        <div class="col-sm-1" style='text-align: right;'><b>Days Between Payments</b></div>
        <div class="col-sm-1" style='text-align: right;'><b>Minimum Payment Amount</b></div>
        <div class="col-sm-2"><b>Must Pay In Full By</b></div>
    </div>
`;
        var keys = Object.keys(this.#matchingPlans);
        for (var row in keys) {
            var match = this.#matchingPlans[keys[row]];
            var plan = match.plan;

            html += `
    <div class="row">
        <div class="col-sm-3">
            <button class="btn btn-sm btn-secondary pt-0 pb-0" onclick="paymentPlans.customizePlan(` + keys[row] + ",'" + from + "'" + ');">Customize payment plan: ' + plan.name + `</button>
        </div>
        <div class="col-sm-1" style='text-align: right;'>` + match.nonPlanAmt.toFixed(2) + `</div>
        <div class="col-sm-1" style='text-align: right;'>` + match.planAmt.toFixed(2) + `</div>
        <div class="col-sm-1" style='text-align: right;'>` + match.downPayment.toFixed(2) + `</div>
        <div class="col-sm-1" style='text-align: right;'>` + match.balanceDue.toFixed(2) + `</div>
        <div class="col-sm-1" style='text-align: right;'>` + match.maxPayments + `</div>
        <div class="col-sm-1" style='text-align: right;'>` + match.daysBetween + `</div>
        <div class="col-sm-1" style='text-align: right;'>` + match.paymentAmt.toFixed(2) + `</div>
        <div class="col-sm-2">` + plan.payByDate + `</div>
    </div>
    <div class="row mb-2">
        <div class="col-sm-1"></div>
        <div class="col-sm-10">` + plan.description + `</div>
    </div>
`;
        }
        return html;
    }

    // customize plans items - fill in the modal and display it
    customizePlan(planId, from) {
        clear_message();
        clear_message('customizePlanMessageDiv');

        console.log('planId: ' + planId + ', from: ' + from);
        console.log(this.#matchingPlans);
        this.#computedPlan = make_copy(this.#matchingPlans[planId]);
        this.#computedOrig = make_copy(this.#matchingPlans[planId]);
        var match = this.#computedPlan;
        var plan = match.plan;
        console.log(match);
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
    <div class="row">
        <div class="col-sm-1" style='text-align: right;'><b>Non Plan Amount</b></div>
        <div class="col-sm-1" style='text-align: right;'><b>Plan Amount</b></div>
        <div class="col-sm-1" style='text-align: right;'><b>Down Payment</b></div>
        <div class="col-sm-1" style='text-align: right;'><b>Balance Due</b></div>
        <div class="col-sm-1" style='text-align: right;'><b>Number Payments</b></div>
        <div class="col-sm-1" style='text-align: right;'><b>Days Between</b></div>
        <div class="col-sm-1" style='text-align: right;'><b>Payment Amount</b></div>
        <div class="col-sm-2"><b>Must Pay In Full By</b></div>
    </div>
    <form id="customizePlanForm" class='form-floating' action='javascript:void(0);'>
    <div class="row">
        <div class="col-sm-1" style='text-align: right;'>` + match.nonPlanAmt.toFixed(2) + `</div>
        <div class="col-sm-1" style='text-align: right;'>` + match.planAmt.toFixed(2) + `</div>
        <div class="col-sm-1" style='text-align: right;'>
            <input type="number" class='no-spinners' inputmode="numeric" id="downPayment" name="downPayment" style="width: 8em;" placeholder="down payment" ` +
            'min="' + match.downPayment.toFixed(2) + '" max="' + match.planAmt.toFixed(2) + '" value="' + match.downPayment.toFixed(2) +
            `" onchange="paymentPlans.recompute();"/>
        </div>
        <div class="col-sm-1" style='text-align: right;' id="balanceDue">` + match.balanceDue.toFixed(2) + `</div>
        <div class="col-sm-1" style='text-align: right;'>`;
        if (match.maxPayments > 1) {
            html += `<input type="number" class='no-spinners' inputmode="numeric" id="maxPayments" name="maxPayments" style="width: 3em;" placeholder="max pmts" ` +
                'min="1" max="' + match.maxPayments + '" value="' + match.maxPayments + `" onchange="paymentPlans.recompute();"/>`;
        } else {
            html += match.maxPayments;
        }
        html += `</div>
        <div class="col-sm-1" style='text-align: right;'>
            <input type="number" class='no-spinners' inputmode="numeric" id="daysBetween" name="daysBetween" style="width: 3em;" placeholder="days" ` +
            'min="7" max="' + match.daysBetween + '" value="' + match.daysBetween + `" onchange="paymentPlans.recompute();"/>
        </div>
        <div class="col-sm-1" style='text-align: right;' id="paymentAmt">` + match.paymentAmt.toFixed(2) + `</div>
        <div class="col-sm-2">` + plan.payByDate + `</div>
    </div>
    </form>
</div>
`;

        switch (from) {
            case 'portal':
                portal.closePaymentDueModal();
                break;
        }

        this.#computedPlan.totalAmountDue = match.nonPlanAmt + match.planAmt;
        this.#computedPlan.currentPayment = match.nonPlanAmt + match.downPayment;
        this.#computedPlan.numPayments = match.maxPayments;
        this.#computedPlan.daysBetween = match.daysBetween;
        this.#computedPlan.paymentAmt = match.paymentAmt;
        this.#computedPlan.balanceDue = match.balanceDue;
        this.#computedPlan.downPayment = match.downPayment;

        this.#customizePlanBody.innerHTML = html;
        this.#customizePlanSubmit.innerHTML = 'Create Plan and pay amount due today of ' + this.#computedPlan.currentPayment.toFixed(2);
        this.#customizePlanModal.show();
    }

    // recompute - onchange from customize payment plan modal popup - when one field changes, recompute what else changes on the screem and update the class variables
    recompute() {
        clear_message('customizePlanMessageDiv');

        var downPaymentField = document.getElementById("downPayment");
        var blanaceDueField = document.getElementById("balanceDue");
        var maxPayments = document.getElementById("maxPayments");
        var daysBetweenField = document.getElementById("daysBetween");
        var paymentAmtField = document.getElementById("paymentAmt");
        var plan = this.#computedOrig.plan;
        var paymentAmt = this.#computedPlan.paymentAmt;
        var balanceDue = this.#computedPlan.balanceDue;

        var down = downPaymentField.value;
        var numPayments = maxPayments.value;
        var days = daysBetweenField.value;

        console.log('days: ' + days + ', numPayments: ' + numPayments + ', days: ' + days);

        var pbDate = new Date(plan.payByDate);
        var today = new Date();
        var diff = Math.floor((pbDate.getTime() - today.getTime()) / (1000 * 3600 * 24)); // milliseconds to days and no fractional days

        if (days != this.#computedPlan.daysBetween) {
            console.log('days changed');
            // compute number of payments to make this work
            if (days > 30) {
                show_message("Adjusted days between to plan maximum", 'warn', 'customizePlanMessageDiv');
                days = 30;
            }
            if (days < 7) {
                days = 7;
                show_message("The shortest interval between payments is one week", 'warn', 'customizePlanMessageDiv');
            }
            numPayments = Math.ceil(diff / days);
            if (numPayments > this.#computedOrig.plan.numPaymentMax) {
                numPayments = this.#computedOrig.plan.numPaymentMax;
            }
            // given the number of payments, compute the payment amount
            paymentAmt = Math.ceil(100 * balanceDue / numPayments) / 100;
        } else if (down != this.#computedPlan.downPayment) {
            console.log('down changed');
            if (down < this.#computedOrig.downPayment) {
                down = this.#computedOrig.downPayment;
                show_message("Down payment cannot be lower than " + down, 'warn', 'customizePlanMessageDiv');
            }
            // recompute balance due
            balanceDue = this.#computedPlan.planAmt - down;
            // paymentAmt can't be less than $10, so make balance due numPayments * 10
            if (balanceDue < numPayments * 10) {
                down = this.#computedPlan.planAmt - (numPayments * 10);
                balanceDue = this.#computedPlan.planAmt - down;
                show_message("Adjusted down payment to keep minimum payment amount of 10", 'warn', 'customizePlanMessageDiv');
            }
            // with new down payment, compute payment amount
            paymentAmt = Math.ceil(100 * balanceDue / numPayments) / 100;
        } else if (numPayments != this.#computedPlan.numPayments) {
            console.log('numPayments changed');
            if (numPayments < 1) {
                numPayments = 1;
                show_message("The number of payments must be at least one", 'warn', 'customizePlanMessageDiv');
            }
            if (numPayments > this.#computedOrig.plan.numPaymentMax) {
                numPayments = this.#computedOrig.plan.numPaymentMax;
                show_message("Adjusted number of payments to plan maximum", 'warn', 'customizePlanMessageDiv');
            }
            if ((numPayments * days) > diff)
                days = Math.floor(diff / numPayments);
            if (days > 30)
                days = 30;
            paymentAmt = Math.ceil(100 * balanceDue / numPayments) / 100;
        }

        // update the plan
        this.#computedPlan.balanceDue = balanceDue;
        this.#computedPlan.downPayment = down;
        this.#computedPlan.numPayments = numPayments;
        this.#computedPlan.daysBetween = days;
        this.#computedPlan.paymentAmt = paymentAmt;

        // update the screen
        downPaymentField.value = down;
        blanaceDueField.innerHTML = balanceDue;
        maxPayments.value = numPayments;
        daysBetweenField.value = days;
        paymentAmtField.innerHTML = paymentAmt;

        this.#computedPlan.currentPayment = Number(this.#computedOrig.nonPlanAmt) + Number(down);
        this.#customizePlanSubmit.innerHTML = 'Create Plan and pay amount due today of ' + this.#computedPlan.currentPayment.toFixed(2);
    }

    // makePlan - create the plan and bring up the payment screen to pay for it
    makePlan(calledFrom) {
        if (this.#computedPlan == null) {
            console.log("makeplan(" + calledFrom + ") called, with no computed plan???");
            return; // no plan to pay, why are we here?
        }
        this.#customizePlanModal.hide();
        var plan = make_copy(this.#computedPlan);
        plan.new = true;
        portal.makePayment(plan);
    }

    // payPlan - make a payment against a plan
    payPlan(payorPlanId) {
        console.log("trying to pay plan " + payorPlanId);
        var payorPlan = payorPlans[payorPlanId];
        var payments = payorPlan['payments'];
        var plan = paymentPlanList[payorPlan.planId];
        console.log(payorPlan);
        if (payments)
            console.log(payments);
        else
            console.log('no payments');

        var paymentAmt = Number(payorPlan.minPayment);
        var balanceDue = Number(payorPlan.balanceDue);
        if (paymentAmt > balanceDue)
            paymentAmt = balanceDue

        this.#planPaymentAmount = paymentAmt;
        this.#planPaymentMinPayment = Number(payorPlan.minPayment);
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
            <input type="number" class='no-spinners' inputmode="numeric" id="newPaymentAmt" name="newPaymentAmt" style="width: 8em;" placeholder="amoutn" ` +
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

        clear_message('payPlanMessageDiv');

        if (paymentAmt < this.#planPaymentMinPayment) {
            show_message("Payment less than minimum allowed, set to " + this.#planPaymentMinPayment.toFixed(2), 'warn', 'payPlanMessageDiv');
            paymentAmt = this.#planPaymentMinPayment.toFixed(2);
        } else if (paymentAmt > this.#planPaymentBalanceDue) {
            show_message("Payment greater than balance due, set to " + this.#planPaymentBalanceDue.toFixed(2), 'warn', 'payPlanMessageDiv');
            paymentAmt = this.#planPaymentBalanceDue.toFixed(2);
        }

        id.value = Number(paymentAmt).toFixed(2);
        this.#payPlanSubmit.innerHTML = 'Make Plan Payment of ' + paymentAmt;
        document.getElementById('paymentPlans')
    }

    // makePlanPayment - make the plan payment
    makePlanPayment(from) {
        this.#payPlanModal.hide();
        switch (from) {
            case 'portal':
                var existingPlan = make_copy(payorPlans[this.#planPaymentPayorPlanId]);
                portal.makePlanPayment(existingPlan, this.#planPaymentPayorPlanName, this.#planPaymentAmount);
                break;
            default:
                console.log('make plan payment invalid from: ' + from);
                break;
        }
    }
}