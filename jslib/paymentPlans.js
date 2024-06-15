// common Payment Plana - routines for creating, managing and paying payment pland

var paymentPlans = null;

class PaymentPlans {
    // payment plan rules
    #paymentPlan = null;
    #matchingPlans = {};

    // account holder payment plan items
    #payorPlan = null;
    #payorPlanPayments = null

    constructor() {
        this.#matchingPlans = {};
    }

    // for which plans is a current car eligible
    plansEligible(purchased = null, space = null) {
        var nonPlanAmt;
        var planAmt;
        // any plans in the system?
        var keys = Object.keys(paymentPlanList);
        if (keys.length == 0)
            return false;

        var matched = 0;
        // how much is owed by the right type:

        for (var prow in keys)  {
            var plan = paymentPlanList[keys[prow]];

            // compute the plan and the not plan amount for this plan
            planAmt = 0;
            nonPlanAmt = 0;

            if (purchased != null && purchased.length > 0) {
                for (var mrow in membershipsPurchased) {
                    var mem = membershipsPurchased[mrow];
                    if (mem.status != 'unpaid') // can't add anything without a balance due to a plan, and plan is already covered in a different plan
                        continue;

                    var eligible = true;
                    if (plan.catList != null) {
                        if (plan.catListArray.indexOf(mem.memCategory.toString()) == -1)
                            eligible = false;
                    }
                    if (eligible && plan.memList != null) {
                        if (plan.memListArray.indexOf(mem.id.toString()) == -1)
                            eligible = false;
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

            this.#matchingPlans[plan.id] = {id: plan.id, plan: plan,
                planAmt: planAmt, nonPlanAmt: nonPlanAmt, downPayment: downPayment, maxPayments: numPayments, daysBetween: daysBetween,
                minPayment: nonPlanAmt + downPayment, balanceDue: balanceDue, paymentAmt: paymentAmt,
            }
            matched++;
        }
        //console.log(this.#matchingPlans);
        return matched > 0;
    }

    getMatchingPlans() {
        if (this.#matchingPlans != null)
            return make_copy(this.#matchingPlans);

        return null;
    }

    isMatchingPlans() {
        return this.#matchingPlans != null;
    }
    getMatchingPlansHTML() {
        if (this.#matchingPlans == null)
            return '';

        var html = '<div class="row mt-2"><div class="col-sm-12"><b>Payment Plans Available:</b></div>' + `
    <div class="row">
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
        <div class="col-sm-2"><button class="btn btn-sm btn-secondary pt-0 pb-0" onclick="portal.customizePlan(` + plan.id + ');">Create payment plan: ' + plan.name + `</button></div>
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
        <div class="col-sm-11">` + plan.description + `</div>
    </div>
`;
        }
        return html;
    }
}
