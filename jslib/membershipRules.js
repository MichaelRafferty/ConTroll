// common membership Rules - test memList against implicit and explicit membership rules using the current 'cart' contents

var membershipRules = null;

// globals that must be loaded by the PHP code or by an ajax call outside of this routine
//     ageList
//     ageListIdx
//     var memTypes
//     var memCategories
//     var memList
//     var memListIdx
//     var memRules

class MembershipRules {
    // rule checking info
    #numCats = null;
    #numFull = null;
    #numFullYearAhead = null;
    #numOneDay = null;
    #conid = null;
    #age = null;
    #memberships = null;
    #allMemberships = null;
    
    // all rule check trackers
    #allTypes = null;
    #allCats = null;
    #allMems = null;
    #allAges = null;

    // mail rules statuses to include
    #includeStatus = ['in-cart', 'unpaid', 'paid', 'plan', 'upgraded'];

    constructor(conid, age, memberships, allMemberships) {
        // prepare for impicit rules
        this.#numCats = [];
        this.#numFull = 0;
        this.#numFullYearAhead = 0;
        this.#numOneDay = 0;
        this.#age = age;
        this.#conid = conid;
        this.#memberships = memberships;
        this.#allMemberships = allMemberships;
        for (var row in memberships) {
            var mbrRow = memberships[row];
            if (this.#includeStatus.indexOf(mbrRow.status) == -1) {
                continue;
            }

            if (mbrRow.memType == 'full' && mbrRow.toDelete != true) {
                if (mbrRow.conid == conid)
                    this.#numFull++;
                else
                    this.#numFullYearAhead++;
            }

            if (mbrRow.memType == 'oneday' && mbrRow.toDelete != true)
                this.#numOneDay++;
        }

        //console.log("setup for testing " + age);
    }

    findInCart(memId, mlist) {
        if (mlist == null)
            return null; // no list to search

        for (var row in mlist) {
            var cartrow = mlist[row];
            if (memId != cartrow.memId)
                continue;
            if (this.#includeStatus.indexOf(cartrow.status) == -1) {
                continue;
            }
            if (cartrow.toDelete == true)
                continue;

            return cartrow;  // return matching entry
        }
        return null; // not found
    }

    // test the memList entry against implicit and explicit rules
    testMembership(mem) {
        // first check if its in the right age, if age is null, all are accepted
        if (this.#age != null) {
            if (mem.memAge != 'all' && mem.memAge != this.#age)
                return false;   // skip this mem entry, its's not all or the current age bracket
        }

        // first the implicit rules:
        // 1. Only one 'full' is allowed
        if (mem.memType == 'full' && mem.memCategory != 'upgrade' && this.#numFull > 0 && mem.conid == this.#conid)
            return false; // only one full membership is allowed, unless it's an upgrade category one, let the fixed rules filter that issue
        if (mem.memType == 'full' && this.#numFullYearAhead > 0 && mem.conid != this.#conid)
            return false; // only one full membership for next year is allowed

        // 2. if full, no one-day, if one-day, no full
        // implicit rule no one day if there is a full for this year
        if (mem.memType == 'oneday' && this.#numFull > 0)
            return false; // no oneday if full membership found

        if (mem.memType == 'full' && this.#numOneDay > 0 && mem.memCategory != 'upgrade' && mem.conid == this.#conid)
            return false; // no full that is not an upgrade if there is a one day

        // 3. if virtual, no memType full
        if (mem.memType == 'virtual' && this.#numFull > 0)
            return false; // no virtual if memType full in cart

        // memCategory rule on duplicate- if onlyOne and it is in the cart, don't allow it again
        var memCat = memCategories[mem.memCategory];
        if (memCat.onlyOne == 'Y') {
            var item = this.findInCart(mem.memId, this.#memberships);
            if (item != null && item != mem) // for delete/remove, are we searching for ourselves, if so, it's allowed
                return false; // only one allowed and one of this memId is in the list already
        }

        // loop over the rulesets and see if they apply
        // to apply, each of the items (if present) typeList, catList, ageList and memList must match the memList item
        for (var key in memRules) {
            var rule = memRules[key];
            if (rule.typeList != null) {
                if (rule.typeListArray.indexOf(mem.memType.toString()) == -1)
                    continue;
            }
            if (rule.catList != null) {
                if (rule.catListArray.indexOf(mem.memCategory.toString()) == -1)
                    continue;
            }
            if (rule.ageList != null) {
                if (rule.ageListArray.indexOf(mem.memAge.toString()) == -1)
                    continue;
            }
            if (rule.memList != null) {
                if (rule.memListArray.indexOf(mem.memId.toString()) == -1)
                    continue;
            }

            // ok this rule applies to this memList entry, now apply it
            if (!this.testMembershipRule(rule, mem))
                return false;
        }
        // either none applied or all passed
        return true;
    }

    // test if the mem entry meets the rule (all steps must pass)
    testMembershipRule(rule,  mem) {
        var steps = rule.ruleset;
        //console.log("testing rule " + rule.name);
        for (var row in steps) {
            // must pass all steps
            var step = steps[row];
            //console.log('step ' + step.step + ', type=' + step.ruleType);

            if (!this.testMembershipRuleStep(step, mem))
                return false;
        }
        // all steps passed
        return true;
    }

    // test if a step passes
    testMembershipRuleStep(step, mem) {
        var checkMore= true;
        var stepPass = step.ruleType == 'notAny' || step.ruleType == 'notAll';
        var mlist = null;

        if (step.applyTo == 'all') {
            mlist = this.#allMemberships;
        } else {
            mlist = this.#memberships;
        }

        // check ageList against the person's age first
        if (step.ageList != null && this.#age != null && this.#age != '' && step.applyTo == 'person') {
            var match = step.ageListArray.indexOf(this.#age) != -1;
            if (step.ruleType == 'notAny' || step.ruleType == 'notAll') {
                if (match)
                    return false;
            } else {
                if (!match)
                    return false;
            }
        }

        if (step.ruleType == 'needAll' || step.ruleType == 'notAll') {
            // for the all rules we need an access to all types
            this.#allTypes = [];
            this.#allCats = [];
            this.#allAges = [];
            this.#allMems = [];
            if (step.ruleType == 'needAll' || step.ruleType == 'notall') {
                // set up the check matrix for 'All' rules
                if (step.typeList != null) {
                    for (var row in step.typeListArray) {
                        this.#allTypes[typeListArray[row]] = false;
                    }
                }

                if (step.catList != null) {
                    for (var row in step.catListArray) {
                        this.#allCats[catListArray[row]] = false;
                    }
                }

                if (step.ageList != null) {
                    for (var row in step.ageListArray) {
                        this.#allAges[ageListArray[row]] = false;
                    }
                }

                if (step.memList != null) {
                    for (var row in step.memListArray) {
                        this.#allMems[memListArray[row]] = false;
                    }
                }
            }
        }

        for (var mbrRow in mlist) {
            var mbr = mlist[mbrRow];
            if (mbr.toDelete == true)
                continue;

            switch (step.ruleType) {
                case 'needAny':
                    if (this.checkAny(step, mbr)) {
                        stepPass = true;
                        checkMore = false;
                    }
                    break;

                case 'needAll':
                case 'notAll':
                    this.checkAll(step,  mbr);
                    break;

                case 'notAny':
                    stepPass = true;
                    if (this.checkAny(step, mbr)) {
                        stepPass = false;
                        checkMore = false;
                    }
                    break;

                case 'limitAge':
                    var typeCheck = step.typeList == null;
                    var memCheck = step.memList == null;
                    var catCheck = step.catList == null;
                    var ageCheck = false;
                    // the entire membership list must have one item that matches all of the non null tests (however the age item must be non null
                    if (step.typeList != null) {
                        typeCheck = step.typeListArray.indexOf(mbr.memType.toString()) != -1;
                    }
                    if (step.catList != null) {
                        catCheck = step.catListArray.indexOf(mbr.memCategory.toString()) != -1;
                    }
                    if (step.memList != null) {
                        memCheck = step.memListArray.indexOf(mbr.memId.toString()) != -1;
                    }
                    if (step.ageList != null) {
                        ageCheck = step.ageListArray.indexOf(mbr.memAge.toString()) != -1;
                    }
                    stepPass = typeCheck && memCheck && ageCheck && ageCheck;
                    //console.log('mbr:');
                    //console.log(mbr);
                    //console.log('step:');
                    //console.log(step);
                    //console.log('typeCheck: ' + typeCheck + ', memCheck: ' + memCheck + ', catCheck: ' + catCheck + ', ageCheck: ' + ageCheck + ', stepPass: ' + stepPass);
                    if (stepPass)
                        checkMore = false;
            } // end of switch

        if (checkMore == false)
            return stepPass;
        } // end of membership list loop
        if (step.ruleType == 'needAll') {
            for (var row in this.#allTypes) {
                if (this.#allTypes[[row].toString()] == false)
                    return false;
            }
            for (var row in this.#allCats) {
                if (this.#allCats[[row].toString()] == false)
                    return false;
            }
            for (var row in this.#allMems) {
                if (this.#allMems[[row].toString()] == false)
                    return false;
            }
            for (var row in this.#allAges) {
                if (this.#allAges[[row].toString()] == false)
                    return false;
            }
            return true;
        } else if (step.ruleType == 'notall') {
            for (var row in this.#allTypes) {
                if (this.#allTypes[[row].toString()])
                    return false;
            }
            for (var row in this.#allCats) {
                if (this.#allCats[[row].toString()])
                    return false;
            }
            for (var row in this.#allMems) {
                if (this.#allMems[[row].toString()])
                    return false;
            }
            for (var row in this.#allAges) {
                if (this.#allAges[[row].toString()])
                    return false;
            }
            return true;
        } else {
            return stepPass;
        }
    } // end of functiontestMemberShipRuleStep

    // checkAny - check if a membership matches any of the requirements
    checkAny(step, mbr) {
        // any one of anything defined succeeds the rule test
        if (step.typeList != null) {
            if (step.typeListArray.indexOf(mbr.memType.toString()) != -1)
                return true;
        }
        if (step.catList != null) {
            if (step.catListArray.indexOf(mbr.memCategory.toString()) != -1)
                return true;
        }
        if (step.ageList != null) {
            if (step.ageListArray.indexOf(this.#age) != -1)
                return true;
            if (step.ageListArray.indexOf(mbr.memAge.toString()) != -1)
                return true;
        }
        if (step.memList != null) {
            if (step.memListArray.indexOf(mbr.memId.toString()) != -1)
                return true;
        }

        return false;
    }

    // checkAll - add a membership matches to the all tracking items
    checkAll(step, mbr) {
        // any one of anything defined succeeds the rule test
        if (step.typeList != null) {
            if (step.typeListArray.indexOf(mbr.memType.toString()) != -1)
                allTypes[mbr.memType.toString()] = true;
        }

        if (step.catList != null) {
            if (step.catListArray.indexOf(mbr.memCategory.toString()) != -1)
                this.#allCats[mbr.memCategory.toString()] = true;
        }

        if (step.ageList != null) {
            if (step.ageListArray.indexOf(mbr.memAge.toString()) != -1)
                this.#allAges[mbr.memAge.toString()] = true;
        }

        if (step.memList != null) {
            if (step.memListArray.indexOf(mbr.memId.toString()) != -1)
                this.#allMems[mbr.memId.toString()] = true;
        }
    }

} // end of Class
