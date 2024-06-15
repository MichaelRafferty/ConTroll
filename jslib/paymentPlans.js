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

            this.#matchingPlans[plan.id] = {id: plan.id, plan: plan,
                planAmt: planAmt, nonPlanAmt: nonPlanAmt, downPayment: downPayment,
                minPayment: nonPlanAmt + downPayment, balanceDue: planAmt - downPayment,
            }
            matched++;
        }
        console.log(this.#matchingPlans);
        return matched > 0;
    }
}
