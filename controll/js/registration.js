// objects
var pos = null;
var cart = null;
var coupon = null;
var ageList = null;
var ageListIdx = null;
var memTypes = null;
var memCategories = null;
var memList = null;
var memListIdx = null;
var memRules = null;

// initialization
// lookup all DOM elements
// load mapping tables
window.onload = function initpage() {
    // set up the constants for objects on the screen

    pos = new Pos('r');
}

// search result_membership functions
// filter to return a single perid from result_membership.filter
function rm_perid_filter(cur, idx, arr) {
    return cur['perid'] == find_perid;
}

// bootstrap listeners
function find_shown() {
    pos.findShown();
}
function add_shown() {
    pos.addShown();
}
function review_shown() {
    pos.reviewShown();
}
function pay_shown() {
    pos.payShown();
}

function amountModalHiddenHelper(event) {
    cart.amountModalHidden(event);
}

// items for registration not in base
// findMembership - find matching memRow in memList
function findMembership(id) {
    if (!memList)
        return null; // no list to search

    for (var row in memList) {
        var memrow = memList[row];
        if (id != memrow.id)
            continue;
        return memrow;  // return matching entry
    }
    return null; // not found
}