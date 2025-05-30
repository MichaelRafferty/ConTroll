// Point of Sale Class - all functions and data common to the point of sale interfaces
class Pos {

    // use: is this atcon (a) or registration (r)
    #use = null;

    #add_edit_dirty_check = false;
    #add_edit_initial_state = "";
    #add_edit_current_state = "";
    #add_edit_prior_tab = null;

    // review items
    #review_div = null;
    #reviewMissingItens = 0;
    #reviewMissingPolicies = 0;
    #review_dirty = false;
    #review_editable_fields = [
        'first_name', 'middle_name', 'last_name', 'suffix',
        'legalName',
        'pronouns',
        'badge_name',
        'email_addr',
        'address_1',
        'address_2',
        'city', 'state', 'postal_code', 'phone'
    ];

    // pay items
    #pay_div = null;
    #pay_button_pay = null;
    #pay_button_ercpt = null;
    #pay_button_rcpt = null;
    #pay_tid = null;
    #pay_tid_amt = 0;
    #discount_mode = 'none';
    #num_coupons = 0;
    #couponList = null;
    #couponSelect = null;
    #couponDiscount = 0;
    #managerDiscount = 0;
    #drow = null;
    #cc_html = '';
    #purchase_label = 'purchase';
    #pay_currentOrderId = null;
    #preTaxAmt = null;
    #taxAmt = null;
    #taxLabel = '';
    #totalPaid = null;
    #payOverride = 0;
    #payPoll = 0;
    #payCurrentRequest = null;
    #payForcePayShown = false;

    // Data Items
    #unpaid_table = [];
    #result_perinfo = [];
    #add_perinfo = [];
    #new_perid = -1;
    #memList = null;
    #memListMap = null;
    #catList = null;
    #ageList = null;
    #typeList = null;
    #policies = null;
    #memRules = null;
    #changeModal = null;
    #cashChangeModal = null;

    // notes items
    #notes = null;
    #notesPerid = null;
    #notesIndex = null;
    #notesType = null;
    #notesPriorValue = null;

    // global items
    #conid = null;
    #conlabel = null;
    #user_id = 0;
    #manager = false;
    #upgradable_types = ['one-day', 'oneday', 'virtual'];
    #multiOneDay = 0;

    // filter criteria
    #filt_excat = null; // array of exclude category
    #filt_cat = null;  // array of categories to include
    #filt_age = null;  // array of ages to include
    #filt_type = null; // array of types to include
    #filt_conid = null; // array of conid's to include
    #filt_shortname_regexp = null; // regexp item;
    #startdate = null; // from load init data, start date of convention
    #enddate = null;  // from load init data, ending date of convention (inclusive)

    // receipt items
    #emailAddreesRecipients = [];
    #last_email_row = '';
    #receeiptEmailAddresses_div = null;
    #lastReceiptType = '';

    // print items
    #newPrint = false;
    #printedObj = null;
    #printDiv = null;
    #badgePrinterAvailable = false;
    #receiptPrinterAvailable = false;
    #ccTerminalAvailable = false;
    #badgeList = null;
    #printActive = false;

    // tab fields
    #find_tab = null;
    #add_tab = null;
    #review_tab = null;
    #pay_tab = null;
    #print_tab = null;
    #current_tab = null;

// find people fields
    #id_div = null;
    #pattern_field = null;
    #find_result_table = null;
    #number_search = null;
    #memLabel = null;
    #find_unpaid_button = null;
    #find_perid = null;
    #name_search = '';

// add/edit person fields
    #add_index_field = null;
    #add_perid_field = null;
    #add_memIndex_field = null;
    #add_first_field = null;
    #add_middle_field = null;
    #add_last_field = null;
    #add_suffix_field = null;
    #add_legalName_field = null;
    #add_pronouns_field = null;
    #add_addr1_field = null;
    #add_addr2_field = null;
    #add_city_field = null;
    #add_state_field = null;
    #add_postal_code_field = null;
    #add_country_field = null;
    #add_email1_field = null;
    #add_email2_field = null;
    #add_phone_field = null;
    #add_badgename_field = null;
    #add_header = null;
    #addnew_button = null;
    #addoverride_button = null;
    #clearadd_button = null;
    #add_results_table = null;
    #add_results_div = null;
    #add_mode = true;
    #addOverride = 0;
    #uspsDiv= null;

    // for matching/every functions
    #checkPerid = null;

// initialization
    constructor(use) {
        this.#use = use;

        if (config.hasOwnProperty('multiOneDay'))
            this.#multiOneDay = config.multiOneDay;

        // set up the constants for objects on the screen

        this.#find_tab = document.getElementById("find-tab");
        this.#current_tab = this.#find_tab;
        this.#add_tab = document.getElementById("add-tab");
        this.#add_edit_prior_tab = this.#add_tab;
        this.#review_tab = document.getElementById("review-tab");
        this.#pay_tab = document.getElementById("pay-tab");
        this.#print_tab = document.getElementById("print-tab");

        // find people
        this.#pattern_field = document.getElementById("find_pattern");
        this.#pattern_field.addEventListener('keyup', (e) => {
            if (e.code === 'Enter') this.findRecord('search');
        });
        this.#pattern_field.focus();
        this.#id_div = document.getElementById("find_results");
        this.#find_unpaid_button = document.getElementById("find_unpaid_btn");

        // add/edit people
        this.#add_index_field = document.getElementById("perinfo-index");
        this.#add_perid_field = document.getElementById("perinfo-perid");
        this.#add_memIndex_field = document.getElementById("membership-index");
        this.#add_first_field = document.getElementById("fname");
        this.#add_middle_field = document.getElementById("mname");
        this.#add_last_field = document.getElementById("lname");
        this.#add_legalName_field = document.getElementById("legalName");
        this.#add_pronouns_field = document.getElementById("pronouns");
        this.#add_suffix_field = document.getElementById("suffix");
        this.#add_addr1_field = document.getElementById("addr");
        this.#add_addr2_field = document.getElementById("addr2");
        this.#add_city_field = document.getElementById("city");
        this.#add_state_field = document.getElementById("state");
        this.#add_postal_code_field = document.getElementById("zip");
        this.#add_country_field = document.getElementById("country");
        this.#add_email1_field = document.getElementById("email1");
        this.#add_email2_field = document.getElementById("email2");
        this.#add_phone_field = document.getElementById("phone");
        this.#add_badgename_field = document.getElementById("badgename");
        this.#add_header = document.getElementById("add_header");
        this.#addnew_button = document.getElementById("addnew-btn");
        this.#addoverride_button = document.getElementById("addoverride-btn");
        this.#clearadd_button = document.getElementById("clearadd-btn");
        this.#add_results_div = document.getElementById("add_results");
        this.#add_edit_initial_state = $("#add-edit-form").serialize();
        this.#uspsDiv = document.getElementById("uspsblock");

        // review items
        this.#review_div = document.getElementById('review-div');

        // pay items
        this.#pay_div = document.getElementById('pay-div');
        this.#pay_div.innerHTML = "No Payment Required, Proceed to Next Customer";

        // print items
        this.#printDiv = document.getElementById("print-div");

        // add events
        this.#find_tab.addEventListener('shown.bs.tab', find_shown)
        this.#add_tab.addEventListener('shown.bs.tab', add_shown)
        this.#review_tab.addEventListener('shown.bs.tab', review_shown)
        this.#pay_tab.addEventListener('shown.bs.tab', pay_shown)
        if (this.#printDiv)
            this.#print_tab.addEventListener('shown.bs.tab', print_shown)

        // notes items
        this.#notes = new bootstrap.Modal(document.getElementById('Notes'), {focus: true, backdrop: 'static'});

        // cash payment requires change
        this.#cashChangeModal = new bootstrap.Modal(document.getElementById('CashChange'), {focus: true, backdrop: 'static'});

        cart = new PosCart();
        coupon = new Coupon();

        bootstrap.Tab.getOrCreateInstance(this.#find_tab).show();

        // check of payPoll (terminal in use), on unsaved changes before leave
        window.addEventListener('beforeunload', event => {
            pos.confirmExit(event);
        })
        window.addEventListener('onunload', event => {
            console.log('pos unload');
        })

        // load the initial data and the proceed to set up the rest of the system
        var postData = {
            ajax_request_action: 'loadInitialData',
            nopay: config.cashier == 0,
        };
        var _this = this;
        $.ajax({
            method: "POST",
            url: "scripts/pos_loadInitialData.php",
            data: postData,
            success: function (data, textstatus, jqxhr) {
                if (data.error !== undefined) {
                    show_message(data.error, 'error');
                    return;
                }
                if (data.message !== undefined) {
                    show_message(data.message, 'success');
                }
                _this.loadInitialData(data);
            },
            error: showAjaxError,
        });
    }

    // set/get private field functions
    setReviewTabDisable(state) {
        this.#review_tab.disabled = state;
    }

    setFindUnpaidHidden(state) {
        if (this.#find_unpaid_button)
            this.#find_unpaid_button.hidden = state;
    }

    setMissingItems(num) {
        this.#reviewMissingItens = num;
    }

    setMissingPolicies(num) {
        this.#reviewMissingPolicies = num;
    }

    setCouponDiscount(num) {
        this.#couponDiscount = num;
    }

    getConid() {
        return this.#conid;
    }

    setPrinterData(data) {
        this.#badgePrinterAvailable = false;
        if (data.hasOwnProperty('badgePrinter'))
            this.#badgePrinterAvailable = data.badgePrinter === true;
        this.#receiptPrinterAvailable = false;
        if (data.hasOwnProperty('receiptPrinter')) {
            this.#receiptPrinterAvailable = data.receiptPrinter === true;
            if (this.#printActive)
                this.printShown();
        }
        if (data.hasOwnProperty('terminal'))
            this.#ccTerminalAvailable = data.terminal === true;
    }

    getConlabel() {
        return this.#conlabel;
    }

    getManager() {
        return this.#manager == 1  && baseManagerEnabled;
    }

    isMultiOneDay() {
        return this.#multiOneDay == 1;
    }

    getReviewEditableFields() {
        return this.#review_editable_fields;
    }

    upgradableTypesIncludes(type) {
        return this.#upgradable_types.includes(type);
    }

    getNumCoupons() {
        return this.#num_coupons;
    }

    isReviewDirty() {
        return this.#review_dirty;
    }

    hideCashChangeModal() {
        this.#cashChangeModal.hide();
    }

    /* obsolete? */
    editFromCartRow(cartrow) {
        this.#add_index_field.value = cartrow.index;
        this.#add_perid_field.value = cartrow.perid;
        this.#add_memIndex_field.value = '';
        this.#add_first_field.value = cartrow.first_name;
        this.#add_middle_field.value = cartrow.middle_name;
        this.#add_last_field.value = cartrow.last_name;
        this.#add_suffix_field.value = cartrow.suffix;
        this.#add_legalName_field.value = cartrow.legalName;
        this.#add_pronouns_field.value = cartrow.pronouns;
        this.#add_addr1_field.value = cartrow.address_1;
        this.#add_addr2_field.value = cartrow.address_2;
        this.#add_city_field.value = cartrow.city;
        this.#add_state_field.value = cartrow.state;
        this.#add_postal_code_field.value = cartrow.postal_code;
        this.#add_country_field.value = cartrow.country;
        this.#add_email1_field.value = cartrow.email_addr;
        this.#add_email2_field.value = cartrow.email_addr;
        this.#add_phone_field.value = cartrow.phone;
        this.#add_badgename_field.value = cartrow.badge_name;
        // policies
        var policies = cartrow.policies;
        for (var row in policies) {
            var policyName = policies[row].policy;
            var policyResp = policies[row].response;
            var policybox = document.getElementById('p_' + policyName);
            if (policybox)
                document.getElementById('p_' + policyName).checked = policyResp == 'Y';
        }
    }

    // loop over people/memberships calling a function on each membership:
    //      function is called with (memrow) which as an associative array of the row
    //      returns the sum of whatever fcn returns
    everyMembership(perinfo, fcn) {
        var rtn = 0;
        for (var pmrowindex in perinfo) {
            var memberships = perinfo[pmrowindex].memberships;
            if (memberships) {
                for (var rowindex in memberships) {
                    rtn += fcn(this, memberships[rowindex]);
                }
            }
        }
        return rtn;
    }

    // load mapping tables from database to javascript array
    loadInitialData(data) {
        // map the memIds and labels for the pre-coded memberships.  Doing it now because it depends on what the database sends.
        // tables
        this.#conlabel =  data.label;
        this.#conid = data.conid;
        this.#user_id = data.user_id
        this.#manager = data.Manager;
        this.#startdate = data.startdate;
        this.#enddate = data.enddate;
        this.#memList = data.memLabels;
        this.#catList = data.memCategories;
        this.#ageList = data.ageList;
        this.#typeList = data.memTypes;
        this.#cc_html = data.cc_html;
        this.#policies = data.policies;
        this.#memRules = data.memRules;
        this.#discount_mode = data.discount;
        this.#badgePrinterAvailable = false;
        if (data.hasOwnProperty('badgePrinter'))
            this.#badgePrinterAvailable = data.badgePrinter === true;
        this.#receiptPrinterAvailable = false;
        if (data.hasOwnProperty('receiptPrinter'))
            this.#receiptPrinterAvailable = data.receiptPrinter === true;
        this.#ccTerminalAvailable = false;
        if (data.hasOwnProperty('terminal'))
            this.#ccTerminalAvailable = data.terminal === true;


        if (this.#manager == false)
            baseManagerEnabled = false;

        // create the globals (vars) for membershipRules.js
        memTypes= data.gmemTypes;
        ageList = data.gageList;
        ageListIdx = data.gageListIdx;
        memCategories = data.gmemCategories;
        memList = data.gmemList;
        memListIdx = data.gmemListIdx;
        memRules = data.gmemRules;

        if (this.#discount_mode === undefined || this.#discount_mode === null || this.#discount_mode == '')
            this.#discount_mode = 'none';

        // build memListMap from memList
        this.#memListMap = new map();
        var row = null;
        var index = 0;
        while (index < this.#memList.length) {
            this.#memListMap.set(this.#memList[index].id, index);
            index++;
        }

        // set up coupon items
        this.#num_coupons = data.num_coupons;
        this.#couponList = data.couponList;
        // build coupon select
        if (this.#num_coupons <= 0) {
            this.#couponSelect = '';
        } else {
            this.#couponSelect = '<select name="couponSelect" id="pay_couponSelect">' + "\n<option value=''>No Coupon</option>\n";
            for (var row in this.#couponList) {
                var item = this.#couponList[row];
                this.#couponSelect += "<option value='" + item.id + "'>" + item.code + ' (' + item.name + ")</option>\n";
            }
            this.#couponSelect += "</select>\n";
        }

        // draw empty cart
        cart.drawCart();

        // set up initial values
        this.#result_perinfo = [];

        // set starting stages of left and right windows
        this.clearAdd(1);

        if (config.hasOwnProperty('autoloadTID')) {
            // this is a passed in autoload of a pid, do a find and pass it to the cart
            if (config.autoloadTID) {
                this.#pattern_field.value = config.autoloadTID;
                this.findRecord('search');
            }
        }
    }

    // find the primary membership for a perid given it's array of memberships
    // with memberships sorted by purchase date, it's last
    find_primary_membership(regitems) {
        var mem_index = null;
        for (var item in regitems) {
            var mi_row = regitems[item];
            if (mi_row.conid != this.#conid)
                continue;

            if (!isPrimary(mi_row.conid, mi_row.memType, mi_row.memCategory, mi_row.price))
                continue;

            mem_index = item;
        }
        return mem_index;
    }

    // badgeNameDefault: build a default badge name if its empty
    badgeNameDefault(badge_name, first_name, last_name) {
        if (badge_name === undefined | badge_name === null || badge_name === '') {
            var default_name = (first_name + ' ' + last_name).trim();
            return '<i>' + default_name.replace(/ +/, ' ') + '</i>';
        }
        return badge_name;
    }

    // show the full perinfo record as a hover in the table
    buildRecordHover(e, cell, onRendered) {
        var data = cell.getData();
        //console.log(data);
        var hover_text = 'Person id: ' + data.perid + '<br/>' +
            'Full Name: ' + data.fullName + '<br/>' +
            'Pronouns: ' + data.pronouns + '<br/>' +
            'Legal Name: ' + data.legalName + '<br/>' +
            data.address_1 + '<br/>';
        if (data.address_2 != '') {
            hover_text += data.address_2 + '<br/>';
        }
        hover_text += data.city + ', ' + data.state + ' ' + data.postal_code + '<br/>';
        if (data.country != '' && data.country != 'USA') {
            hover_text += data.country + '<br/>';
        }
        hover_text += 'Badge Name: ' + this.badgeNameDefault(data.badge_name, data.first_name, data.last_name) + '<br/>' +
            'Email: ' + data.email_addr + '<br/>' + 'Phone: ' + data.phone + '<br/>';
        if (data.managedBy) {
            hover_text += 'Managed by: (' + data.managedBy + ') ' + data.mgrFullName + '</br>';
        } else if (data.cntManages > 0) {
            hover_text += 'Manages: ' + data.cntManages + '<br/>';
        }
        hover_text += 'Active:' + data.active;

        // append the policies to the active flag line
        var policies = data.policies;
        for (var row in policies) {
            var policyName = policies[row].policy;
            var policyResp = policies[row].response;
            hover_text += ', ' + policyName + ': ' + policyResp;
        }

        hover_text += '<br/>' +
            'Membership: ' + data.reg_label + '<br/>';

        return hover_text;
    }

    // if no memberships or payments have been added to the database, this will reset for the next customer
    startOver(reset_all) {
        if (!this.confirmDiscardAddEdit(false))
            return;

        if (!cart.confirmDiscardCartEntry(-1, false))
            return;

        if (this.#payPoll == 1) {
            if (!confirm("You are leaving without polling the terminal for payment completion.\n" +
                'Please use the "Payment Complete" button to check if the payment is complete,\n' +
                'or tthe "Cancel Payment" buttons to cancel the payment request and release the terminal.\n' +
                "Do you wish to leave anyway without releasing the terminal?")) {
                return;
            }
            // cancel terminal request
            var postData = {
                ajax_request_action: 'cancelPayRequest',
                requestId: this.#payCurrentRequest,
                user_id: this.#user_id,
            };
            var _this = this;
            clear_message();
            $.ajax({
                method: "POST",
                url: "scripts/pos_cancelPayment.php",
                data: postData,
                success: function (data, textstatus, jqxhr) {
                    if (typeof data == 'string') {
                        show_message(data, 'error');
                        return;
                    }

                    if (data.error !== undefined) {
                        show_message(data.error, 'error');
                        return;
                    }

                    if (data.status == 'error') {
                        show_message(data.data, 'error');
                        return;
                    }

                    if (data.warn !== undefined) {
                        show_message(data.warn, 'warn');
                    }

                    if (data.message !== undefined) {
                        show_message(data.message, 'success');
                    }
                },
                error: function (jqXHR, textstatus, errorThrown) {
                    document.getElementById('pollRow').hidden = false;
                    _this.#pay_button_pay.disabled = true;
                    showAjaxError(jqXHR, textstatus, errorThrown);
                },
            });
            this.#payPoll = 0;
        }

        if (reset_all > 0)
            clear_message();

        // reset admin mode if enabled
        if (!inConTroll && baseManagerEnabled) {
            base_toggleManager();
        }
        // clear the coupon
        coupon = null;
        coupon = new Coupon();
        this.#managerDiscount = 0;
        this.#couponDiscount = 0;
        this.#drow = null;
        // empty cart
        cart.startOver();
        if (this.#pay_currentOrderId && this.#pay_currentOrderId != '') {
            var postData = {
                ajax_request_action: 'cancelOrder',
                orderId: this.#pay_currentOrderId,
                user_id: this.#user_id,
            };
            $.ajax({
                method: "POST",
                url: "scripts/pos_cancelOrder.php",
                data: postData,
                success: function (data, textstatus, jqxhr) {
                    if (data.error !== undefined) {
                        show_message(data.error, 'error');
                        return;
                    }
                    if (data.warn !== undefined) {
                        show_message(data.warn, 'warn');
                        return;
                    }
                    if (data.message !== undefined) {
                        show_message(data.message, 'success');
                    }
                },
                error: function (jqXHR, textstatus, errorThrown) {
                    $("button[name='find_btn']").attr("disabled", false);
                    showAjaxError(jqXHR, textstatus, errorThrown);
                }
            });
            this.#pay_currentOrderId = null;
        }
        if (this.#find_unpaid_button)
            this.#find_unpaid_button.hidden = false;
        // empty search strings and results
        this.#pattern_field.value = "";
        if (this.#find_result_table != null) {
            this.#find_result_table.destroy();
            this.#find_result_table = null;
        }
        this.#id_div.innerHTML = "";
        this.#unpaid_table = null;

        // reset data to call up
        this.#result_perinfo = [];
        this.#emailAddreesRecipients = [];
        this.#last_email_row = '';

        // reset tabs to initial values
        this.#find_tab.disabled = false;
        this.#add_tab.disabled = false;
        this.#review_tab.disabled = true;
        this.#pay_tab.disabled = true;
        if (this.#print_tab)
            this.#print_tab.disabled = true;
        cart.hideNext();
        this.#pay_button_pay = null;
        this.#pay_button_ercpt = null;
        this.#pay_button_rcpt = null;
        this.#receeiptEmailAddresses_div = null;
        this.#pay_tid = null;
        this.#pay_tid_amt = 0;
        this.#pay_currentOrderId = null;
        // clear the pay tab
        this.#pay_div.innerHTML = "No Payment Required, Proceed to Next Customer";

        this.clearAdd(reset_all);
        // set tab to find-tab
        bootstrap.Tab.getOrCreateInstance(this.#find_tab).show();
        this.#pattern_field.focus();
        this.#review_dirty = false;
    }


    // add search person/transaction from result_perinfo record to the cart
    addToCart(index, table) {
        var rt = null;
        var perid;

        if (table == 'result') {
            rt = this.#result_perinfo;
        } else if (table == 'add') {
            rt = this.#add_perinfo;
        }

        if (index >= 0) {
            if (rt[index].banned == 'Y') {
                alert("Please ask " + (rt.first_name + ' ' + rt[index].last_name).trim() + " to talk to the Registration Administrator, " +
                    "you cannot add them at this time.")
                return;
            }
            perid = rt[index].perid;
            if (cart.notinCart(perid)) {
                cart.add(rt[index]);
            }
        } else {
            var row;
            index = -index;
            this.everyMembership(this.#result_perinfo, function(_this, mem) {
                var prow = mem.pindex;
                if (index == _this.#result_perinfo[prow].perid || index == _this.#result_perinfo[prow].managedBy || index ==  mem.tid || index == mem.tid2) {
                    if (_this.#result_perinfo[prow].banned == 'Y') {
                        alert("Please ask " + (_this.#result_perinfo[prow].first_name + ' ' + _this.#result_perinfo[prow].last_name).trim() +
                            " to talk to the Registration Administrator, you cannot add them at this time.")
                        return 0;
                    }
                    perid = _this.#result_perinfo[prow].perid;
                    if (cart.notinCart(perid)) {
                        cart.add(_this.#result_perinfo[prow], index == _this.#result_perinfo[prow].perid);
                    }
                }
            });
        }

        if (table == 'result') {
            if (this.#find_result_table !== null) {
                this.#find_result_table.replaceData(this.#result_perinfo);
            } else {
                this.drawAsRecords();
            }
        }
        clear_message();
    }

    // remove person and all of their memberships from the cart
    removeFromCart(perid) {
        cart.remove(perid);

        if (this.#find_result_table !== null) {
            this.#find_result_table.replaceData(this.#result_perinfo);
        } else {
            this.drawAsRecords();
        }
        clear_message();
    }

    // common confirm add/edit screen dirty, if the tab isn't shown switch to it if dirty
    confirmDiscardAddEdit(silent) {
        if (!this.#add_edit_dirty_check || cart.isFrozen()) // don't check if not dirty, or if the cart is frozen, return ok to discard
            return true;

        this.#add_edit_current_state = $("#add-edit-form").serialize();
        if (this.#add_edit_initial_state == this.#add_edit_current_state)
            return true; // no changes found

        if (silent)
            return false;

        // show the add/edit screen if it's hidden
        bootstrap.Tab.getOrCreateInstance(this.#add_tab).show();

        return confirm("Discard current data in add/edit screen?");
    }

// event handler for beforeunload event, prevents leaving with unsaved data
    checkAllUnsaved(e) {
        // data editing checks
        if (!this.confirmDiscardAddEdit(true)) {
            e.preventDefault();
            e.returnValue = "You have unsaved member changes, leave anyway";
            return;
        }

        if (!cart.confirmDiscardCartEntry(-1, true)) {
            e.preventDefault();
            e.returnValue = "You have unsaved cart changes, leave anyway";
            return;
        }

        delete e.returnValue;
    }

    // populate the add/edit screen from a cart item, and switch to add/edit
    editFromCart(perid) {
        if (!this.confirmDiscardAddEdit(false))
            return;

        this.clearAdd(1);
        cart.getAddEditFields(perid);

        // set page values
        add_header.innerHTML = `
<div class="col-sm-12 text-bg-primary mb-2">
        <div class="text-bg-primary m-2">
            Edit Person and Membership
        </div>
    </div>`;
        this.#addnew_button.innerHTML = "Update to Cart";
        this.#clearadd_button.innerHTML = "Discard Update";
        this.#addoverride_button.hidden = true;
        this.#add_mode = false;
        this.#add_edit_dirty_check = true;
        this.#add_edit_initial_state = $("#add-edit-form").serialize();
        this.#add_edit_current_state = "";
        this.#add_edit_prior_tab = this.#current_tab;
        bootstrap.Tab.getOrCreateInstance(this.#add_tab).show();
    }

    // Clear the add/edit screen back to completely empty (startup)
    clearAdd(reset_all) {
        // reset to empty all of the add/edit fields
        this.#add_index_field.value = "";
        this.#add_perid_field.value = "";
        this.#add_first_field.value = "";
        this.#add_middle_field.value = "";
        this.#add_last_field.value = "";
        this.#add_legalName_field.value = "";
        this.#add_pronouns_field.value = "";
        this.#add_suffix_field.value = "";
        this.#add_addr1_field.value = "";
        this.#add_addr2_field.value = "";
        this.#add_city_field.value = "";
        this.#add_state_field.value = "";
        this.#add_postal_code_field.value = "";
        this.#add_country_field.value = "";
        this.#add_email1_field.value = "";
        this.#add_email2_field.value = "";
        this.#add_phone_field.value = "";
        this.#add_badgename_field.value = "";
        this.#add_country_field.value = 'USA';
        // clear the policies
        for (var pol in this.#policies) {
            var policyName = this.#policies[pol].policy;
            var policybox = document.getElementById('p_' + policyName);
            if (policybox)
                policybox.checked =  this.#policies[pol].defaultValue == 'Y';
        }

        this.#add_header.innerHTML = `
<div class="col-sm-12 text-bg-primary mb-2">
        <div class="text-bg-primary m-2">
            Add New Person and Membership
        </div>
    </div>`;
        this.#add_first_field.style.backgroundColor = '';
        this.#add_last_field.style.backgroundColor = '';
        this.#add_addr1_field.style.backgroundColor = '';
        this.#add_city_field.style.backgroundColor = '';
        this.#add_state_field.style.backgroundColor = '';
        this.#add_postal_code_field.style.backgroundColor = '';
        this.#add_email1_field.style.backgroundColor = '';
        this.#add_email2_field.style.backgroundColor = '';
        if (this.#add_results_table != null) {
            this.#add_results_table.destroy();
            this.#add_results_table = null;
            this.#add_results_div.innerHTML = "";
        }
        this.#add_mode = true;
        this.#add_edit_dirty_check = true;
        this.#add_edit_initial_state = $("#add-edit-form").serialize();
        this.#add_edit_current_state = "";
        this.#addoverride_button.hidden = true;
        this.#addnew_button.innerHTML = "Add to Cart";
        if (reset_all > 0)
            clear_message();
        if (this.#clearadd_button.innerHTML.trim() != 'Clear Add Person Form') {
            this.#clearadd_button.innerHTML = 'Clear Add Person Form';
            // change back to the prior tab
            bootstrap.Tab.getOrCreateInstance(this.#add_edit_prior_tab).show();
            this.#add_edit_prior_tab = this.#add_tab;
        }
    }

    // add record from the add/edit screen to the cart.  If it's already in the cart, update the cart record.
    add_new(override = 0) {
        var edit_index = this.#add_index_field.value.trim();
        var edit_perid = this.#add_perid_field.value.trim();
        var new_memindex = this.#add_memIndex_field.value.trim();
        var new_first = this.#add_first_field.value.trim();
        var new_middle = this.#add_middle_field.value.trim();
        var new_last = this.#add_last_field.value.trim();
        var new_suffix = this.#add_suffix_field.value.trim();
        var new_legalName = this.#add_legalName_field.value.trim();
        var new_pronouns = this.#add_legalName_field.value.trim();
        var new_addr1 = this.#add_addr1_field.value.trim();
        var new_addr2 = this.#add_addr2_field.value.trim();
        var new_city = this.#add_city_field.value.trim();
        var new_state = this.#add_state_field.value.trim();
        var new_postal_code = this.#add_postal_code_field.value.trim();
        var new_country = this.#add_country_field.value.trim();
        var new_email = this.#add_email1_field.value.trim();
        var new_phone = this.#add_phone_field.value.trim();
        var new_badgename = this.#add_badgename_field.value.trim();
        var new_fullname = (new_first + ' ' + new_middle + ' ' + new_last + ' ' + new_suffix).replace('  ', ' ').trim();

        this.#addOverride = override;

        if (this.#add_mode == false && edit_index != '') { // update perinfo/meminfo and cart_perinfo and cart_memberships
            var row = {};
            row.policies = {};
            row.first_name = new_first;
            row.middle_name = new_middle;
            row.last_name = new_last;
            row.suffix = new_suffix;
            row.legalName = new_legalName;
            row.pronouns = new_pronouns;
            row.badge_name = new_badgename;
            row.address_1 = new_addr1;
            row.address_2 = new_addr2;
            row.city = new_city;
            row.state = new_state;
            row.postal_code = new_postal_code;
            row.country = new_country;
            row.email_addr = new_email;
            row.phone = new_phone;
            row.fullName = new_fullname;
            row.active = 'Y';
            row.dirty = true;

            for (var pol in this.#policies) {
                var policyName = this.#policies[pol].policy;

                var policybox = document.getElementById('p_' + policyName);
                if (policybox) {
                    var response = policybox.checked;
                    row.policies[policyName] = {};
                    row.policies[policyName].response = response ? 'Y' : 'N';
                    row.policies[policyName].policy = policyName;
                }
            }
            
            cart.updateEntry(edit_index, row, this.#policies);
            this.#review_dirty = true;

            // clear the fields that should not be preserved between adds.  Allowing a second person to be added using most of the same data as default.
            this.#add_first_field.value = "";
            this.#add_middle_field.value = "";
            this.#add_suffix_field.value = "";
            this.#add_legalName_field.value = "";
            this.#add_pronouns_field.value = "";
            this.#add_email1_field.value = "";
            this.#add_email2_field.value = "";
            this.#add_phone_field.value = "";
            this.#add_badgename_field.value = "";
            this.#add_index_field.value = "";
            this.#add_perid_field.value = "";
            this.#add_memIndex_field.value = "";
            // clear the policies
            for (var pol in this.#policies) {
                var policyName = this.#policies[pol].policy;
                var policybox = document.getElementById('p_' + policyName);
                if (policybox)
                    policybox.checked =  this.#policies[pol].policy.defaultValue == 'Y';
            }
            this.#add_header.innerHTML = `
<div class="col-sm-12 text-bg-primary mb-2">
        <div class="text-bg-primary m-2">
            Add New Person and Membership
        </div>
    </div>`;
            this.#add_first_field.style.backgroundColor = '';
            this.#add_last_field.style.backgroundColor = '';
            this.#add_addr1_field.style.backgroundColor = '';
            this.#add_city_field.style.backgroundColor = '';
            this.#add_state_field.style.backgroundColor = '';
            this.#add_postal_code_field.style.backgroundColor = '';
            this.#add_email1_field.style.backgroundColor = '';
            this.#add_email2_field.style.backgroundColor = '';
            if (this.#add_results_table != null) {
                this.#add_results_table.destroy();
                this.#add_results_table = null;
                this.#add_results_div.innerHTML = "";
            }
            this.#addnew_button.innerHTML = "Add to Cart";
            this.#clearadd_button.innerHTML = 'Clear Add Person Form';
            this.#add_edit_dirty_check = true;
            this.#add_edit_initial_state = $("#add-edit-form").serialize();
            this.#add_edit_current_state = "";
            cart.drawCart();
            bootstrap.Tab.getOrCreateInstance(this.#add_edit_prior_tab).show();
            this.#add_edit_prior_tab = this.#add_tab;
            return;
        }

        // we've searched this first/last name already and are displaying the table, so just go add the manually entered person
        if (this.#add_results_table != null) {
            this.#add_results_table.destroy();
            this.#add_results_table = null;
            pos.addNewToCart(override);
            return;
        }

        clear_message();
        var name_search = (new_first + ' ' + new_last).toLowerCase().trim();
        if (name_search == null || name_search == '') {
            show_message("First name or Last Name must be specified", "warn");
            return;
        }

        // look for matching records for this person being added to check for duplicates
        var postData = {
            ajax_request_action: 'findRecord',
            find_type: 'addnew',
            name_search: name_search,
        };
        $("button[name='find_btn']").attr("disabled", true);
        var _this = this;
        $.ajax({
            method: "POST",
            url: "scripts/pos_findRecord.php",
            data: postData,
            success: function (data, textstatus, jqxhr) {
                if (data.error !== undefined) {
                    show_message(data.error, 'error');
                    $("button[name='find_btn']").attr("disabled", false);
                    return;
                }
                if (data.message !== undefined) {
                    show_message(data.message, 'success');
                }
                if (data.warn !== undefined) {
                    show_message(data.warn, 'warn');
                }
                _this.addFound(data);
                $("button[name='find_btn']").attr("disabled", false);
            },
            error: function (jqXHR, textstatus, errorThrown) {
                $("button[name='find_btn']").attr("disabled", false);
                showAjaxError(jqXHR, textstatus, errorThrown);
            }
        });
    }

// addFound: all the tasks post search for matching records for adding a record to the cart
    addFound(data) {
        var rowindex;
        // see if they already exist (if add to cart)
        this.#add_perinfo = data.perinfo;

        if (this.#add_perinfo.length > 0) {
            // find primary membership for each add_perinfo record
            for (rowindex in this.#add_perinfo) {
                var row = this.#add_perinfo[rowindex];
                var primmem = this.find_primary_membership(row.memberships);
                if (primmem != null) {
                    row.reg_label = row.memberships[primmem].label;
                    var tid = row.memberships[primmem].tid;
                    if (tid != '') {
                        this.#checkPerid = row.perid;
                        var other = !row.memberships.every(this.notPerid, this);

                        if (other) {
                            row.tid = tid;
                        }
                    }
                } else {
                    row.reg_label = 'No Primary Membership';
                    row.reg_tid = '';
                }
            }
            // table
            var _this = this;
            this.#add_results_table = new Tabulator('#add_results', {
                maxHeight: "600px",
                data: this.#add_perinfo,
                layout: "fitColumns",
                initialSort: [
                    {column: "fullName", dir: "asc"},
                    {column: "badge_name", dir: "asc"},
                ],
                columns: [
                    {field: "perid", visible: false,},
                    {title: "Name", field: "fullName", headerFilter: true, headerFilterFunc: fullNameHeaderFilter, headerWordWrap: true,
                        tooltip: posbuildRecordHover, formatter: "textarea", },
                    {field: "last_name", visible: false,},
                    {field: "first_name", visible: false,},
                    {field: "middle_name", visible: false,},
                    {field: "suffix", visible: false,},
                    {field: "legalName", visible: false,},
                    {title: "Badge Name", field: "badge_name", headerFilter: true, headerWordWrap: true, tooltip: true,},
                    {title: "Zip", field: "postal_code", headerFilter: true, headerWordWrap: true, tooltip: true, maxWidth: 70, width: 70},
                    {title: "Email Address", field: "email_addr", headerFilter: true, headerWordWrap: true, tooltip: true,},
                    {title: "Reg", field: "reg_label", headerFilter: true, headerWordWrap: true, tooltip: true, maxWidth: 120, width: 120,},
                    {title: "Nt", width: 45, headerSort: false, headerFilter: false, formatter: posPerNotesIcons, formatterParams: {t: "add"},},
                    {title: "Cart", width: 100, headerFilter: false, headerSort: false, formatter: _this.addCartIcon, formatterParams: {t: "add"},},
                    {field: "index", visible: false,},
                    {field: "open_notes", visible: false,},
                ],
            });
            this.#addnew_button.innerHTML = "Add New";
            this.#add_edit_initial_state = $("#add-edit-form").serialize();
            $("button[name='find_btn']").attr("disabled", false);
            return;
        }
        this.addNewToCart(this.#addOverride);
    }

// addNewToCart - not in system or operator said they are really new, add them to the cart
    addNewToCart(override = 0) {
        var new_first = this.#add_first_field.value.trim();
        var new_middle = this.#add_middle_field.value.trim();
        var new_last = this.#add_last_field.value.trim();
        var new_suffix = this.#add_suffix_field.value.trim();
        var new_legalName = this.#add_legalName_field.value.trim();
        var new_pronouns = this.#add_pronouns_field.value.trim();
        var new_addr1 = this.#add_addr1_field.value.trim();
        var new_addr2 = this.#add_addr2_field.value.trim();
        var new_city = this.#add_city_field.value.trim();
        var new_state = this.#add_state_field.value.trim();
        var new_postal_code = this.#add_postal_code_field.value.trim();
        var new_country = this.#add_country_field.value.trim();
        var new_email = this.#add_email1_field.value.trim();
        var new_phone = this.#add_phone_field.value.trim();
        var new_badgename = this.#add_badgename_field.value.trim();
        var new_fullname = (new_first + ' ' + new_middle + ' ' + new_last + ' ' + new_suffix).replace('  ', ' ').trim();

        this.#addOverride = override;

        if (new_legalName == '') {
            new_legalName = ((new_first + ' ' + new_middle).trim() + ' ' + new_last + ' ' + new_suffix).replace('  ', ' ').trim();
        }

        clear_message();
        // look for missing data
        // look for missing fields
        var missing_fields = 0;
        if (override == 0) {
            var required = config.required;

            if (required != '') {
                if (new_first == '') {
                    missing_fields++;
                    this.#add_first_field.style.backgroundColor = 'var(--bs-warning)';
                } else {
                    this.#add_first_field.style.backgroundColor = '';
                }
            }
            if (required == 'all') {
                if (new_last == '') {
                    missing_fields++;
                    this.#add_last_field.style.backgroundColor = 'var(--bs-warning)';
                } else {
                    this.#add_last_field.style.backgroundColor = '';
                }
            }

            if (required == 'all') {
                if (new_addr1 == '') {
                    missing_fields++;
                    this.#add_addr1_field.style.backgroundColor = 'var(--bs-warning)';
                } else {
                    this.#add_addr1_field.style.backgroundColor = '';
                }
            }

            if (required == 'addr' || required == 'all' ||
                (new_country == 'USA' && this.#uspsDiv != null &&
                    (new_addr1 != '' || new_city != '' || new_state != '' || new_postal_code != '')
                )
            ) {
                if (new_city == '') {
                    missing_fields++;
                    this.#add_city_field.style.backgroundColor = 'var(--bs-warning)';
                } else {
                    this.#add_city_field.style.backgroundColor = '';
                }

                if (new_state == '') {
                    missing_fields++;
                    this.#add_state_field.style.backgroundColor = 'var(--bs-warning)';
                } else {
                    this.#add_state_field.style.backgroundColor = '';
                }

                if (new_postal_code == '') {
                    missing_fields++;
                    this.#add_postal_code_field.style.backgroundColor = 'var(--bs-warning)';
                } else {
                    this.#add_postal_code_field.style.backgroundColor = '';
                }
            }

            if (new_email == '') {
                missing_fields++;
                this.#add_email1_field.style.backgroundColor = 'var(--bs-warning)';
                this.#add_email2_field.style.backgroundColor = 'var(--bs-warning)';
            } else {
                this.#add_email1_field.style.backgroundColor = '';
                this.#add_email2_field.style.backgroundColor = '';
            }
        } else {
            this.#add_first_field.style.backgroundColor = '';
            this.#add_last_field.style.backgroundColor = '';
            this.#add_addr1_field.style.backgroundColor = '';
            this.#add_city_field.style.backgroundColor = '';
            this.#add_state_field.style.backgroundColor = '';
            this.#add_postal_code_field.style.backgroundColor = '';
            this.#add_email1_field.style.backgroundColor = '';
            this.#add_email2_field.style.backgroundColor = '';
            this.#add_header.innerHTML = `
    <div class="col-sm-12 text-bg-primary mb-2">
        <div class="text-bg-primary m-2">
            Add New Person and Membership
        </div>
    </div>`;
        }

        if (missing_fields > 0) {
            if (this.#add_results_table != null) {
                this.#add_results_table.destroy();
                this.#add_results_table = null;
                this.#add_results_div.includes = "";
                this.#addnew_button.innerHTML = "Add to Cart";
            }
            this.#add_header.innerHTML = `
<div class="col-sm-12 text-bg-warning mb-2">
        <div class="text-bg-warning m-2">
            Add New Person and Membership (* = Required Data)
        </div>
    </div>`;
            this.#addoverride_button.hidden = false;
            return;
        }

        this.#addoverride_button.hidden = true;

        //  build the policy array
        var rowPolicies = {};
        for (var pol in this.#policies) {
            var policyName = this.#policies[pol].policy;
            var policybox = document.getElementById('p_' + policyName);
            if (policybox) {
                rowPolicies[policyName] = {};
                rowPolicies[policyName].policy = policyName;
                rowPolicies[policyName].perid = this.#new_perid;
                rowPolicies[policyName].response = policybox.checked ? 'Y' : 'N';
            }
        }

        var row = {
            perid: this.#new_perid, first_name: new_first, middle_name: new_middle, last_name: new_last, suffix: new_suffix,
            legalName: new_legalName, pronouns: new_pronouns, badge_name: new_badgename, fullName: new_fullname,
            address_1: new_addr1, address_2: new_addr2, city: new_city, state: new_state, postal_code: new_postal_code,
            open_notes: '',
            country: new_country, email_addr: new_email, phone: new_phone, active: 'Y', banned: 'N', policies: rowPolicies
        };
        this.#new_perid--;

        this.#add_first_field.value = "";
        this.#add_middle_field.value = "";
        this.#add_email1_field.value = "";
        this.#add_email2_field.value = "";
        this.#add_phone_field.value = "";
        this.#add_badgename_field.value = "";
        cart.add(row);

        if (this.#add_results_table != null) {
            this.#add_results_table.destroy();
            this.#add_results_table = null;
            this.#add_results_div.innerHTML = "";
            this.#addnew_button.innerHTML = "Add to Cart";
        }
        this.#add_header.innerHTML = `
<div class="col-sm-12 text-bg-primary mb-2">
        <div class="text-bg-primary m-2">
            Add New Person and Membership
        </div>
    </div>`;
        this.#add_edit_dirty_check = false;
        this.#add_edit_initial_state = $("#add-edit-form").serialize();
        this.#add_edit_current_state = "";
    }

// drawRecord: findRecord found rows from search.  Display them in the non table format used by transaction and perid search, or a single row match for string.
    drawRecord(row, first) {
        var data = this.#result_perinfo[row];
        var mem = data.memberships;
        var prim = this.find_primary_membership(mem);
        var label = "No Primary Membership";
        if (prim != null) {
            label = mem[prim].label;
        }
        var html = `
<div class="container-fluid">
    <div class="row mt-2">
        <div class="col-sm-3 pt-1 pb-1">`;
        if (first) {
            html += `<button class="btn btn-primary btn-sm" id="add_btn_all" onclick="pos.addToCart(-` + this.#number_search + `, 'result');">Add All Cart</button>`;
        }
        html += `</div>
        <div class="col-sm-5 pt-1 pb-1">`;
        if (cart.notinCart(data.perid)) {
            if (data.banned == 'Y') {
                html += `
            <button class="btn btn-danger btn-sm" id="add_btn_1" onclick="pos.addToCart(` + row + `, 'result');">B</button>`;
            } else {
                html += `
            <button class="btn btn-success btn-sm" id="add_btn_1" onclick="pos.addToCart(` + row + `, 'result');">Add to Cart</button>`;
            }
        } else {
            html += `
            <i>In Cart</i>`
        }
        html += `</div>
        <div class="col-sm-2">`;
        if (data.open_notes != null && data.open_notes.length > 0) {
            html += '<button type="button" class="btn btn-sm btn-info p-0" onclick="pos.showPerinfoNotes(' + data.index + ', \'result\')">View' +
                ' Notes</button>';
        }
        html += `</div>
        <div class="col-sm-2">`;
        if (baseManagerEnabled && this.#manager) {
            html += '<button type="button" class="btn btn-sm btn-secondary p-0" onClick="pos.editPerinfoNotes(0, \'result\')">Edit Notes</button>';
        }

        html += `
            </div>
        </div>
        <div class="row">
            <div class="col-sm-3">Person ID:</div>
            <div class="col-sm-9">` + data.perid + `</div>
        </div>
        <div class="row">
            <div class="col-sm-3">Badge Name:</div>
            <div class="col-sm-9">` + this.badgeNameDefault(data.badge_name, data.first_name, data.last_name) + `</div>
        </div>
        <div class="row">
            <div class="col-sm-3">Name:</div>
            <div class="col-sm-9">` + data.fullName + `</div>
        </div>  
         <div class="row">
            <div class="col-sm-3">Pronouns:</div>
            <div class="col-sm-9">` + data.pronouns + `</div>
        </div>  
         <div class="row">
            <div class="col-sm-3">Legal Name:</div>
            <div class="col-sm-9">` + data.legalName + `</div>
        </div>
        <div class="row">
            <div class="col-sm-3">Address:</div>
            <div class="col-sm-9">` + data.address_1 + `</div>
        </div>
`;
        if (data.address_2 != '') {
            html += `
    <div class="row">
        <div class="col-sm-3"></div>
        <div class="col-sm-9">` + data.address_2 + `</div>
    </div>
`;
        }
        html += `
    <div class="row">
       <div class="col-sm-3"></div>
       <div class="col-sm-9">` + data.city + ', ' + data.state + ' ' + data.postal_code + `</div>
    </div>
`;
        if (data.country != '' && data.country != 'USA') {
            html += `
    <div class="row">
       <div class="col-sm-3"></div>
       <div class="col-sm-9">` + data.country + `</div>
    </div>
`;
        }
        html += `
    <div class="row">
       <div class="col-sm-3">Email Address:</div>
       <div class="col-sm-9">` + data.email_addr + `</div>
    </div>
    <div class="row">
       <div class="col-sm-3">Phone:</div>
       <div class="col-sm-9">` + data.phone + `</div>
    </div>
    <div class="row">
       <div class="col-sm-3">Policies:</div>
       <div class="col-sm-auto">Active: ` + data.active + "</div>\n";
        var policies = data.policies;
        for (var row in policies) {
            var policyName = policies[row].policy;
            var policyResp = policies[row].response;
            html += '<div class="col-sm-auto">' + policyName + ': ' + policyResp + "</div>\n";
        }

        html += `
    </div>
    <div class="row">
       <div class="col-sm-3">Membership Type:</div>
       <div class="col-sm-9">` + label + `</div>
    </div>
</div>
`;
        return html;
    }

    // tabulator perinfo formatters:

    // tabulator formatter for the add cart column, displays the "add" record and "trans" to add the transaction to the card as appropriate
    // filters for ones already in the cart, and statuses that should not be allowed to be added to the cart
    addCartIcon(cell, formatterParams, onRendered) { //plain text value
        var tid;
        var html = '';
        var data = cell.getRow().getData();
        if (data.banned == undefined) {
            tid = Number(data.tid);
            html = '<button type="button" class="btn btn-sm btn-success p-0" style="--bs-btn-font-size: 75%;" ' +
                'onclick="pos.addUnpaid(' + tid + ')">Pay</button > ';
            return html;
        }
        if (data.banned == 'Y') {
            return '<button type="button" class="btn btn-sm btn-danger pt-0 pb-0" style="--bs-btn-font-size: 75%;" onclick="pos.addToCart(' +
                data.index + ', \'' + formatterParams.t + '\')">B</button>';
        } else if (cart.notinCart(data.perid)) {
            html = '<button type="button" class="btn btn-sm btn-success p-0" style="--bs-btn-font-size: 75%;" onclick="pos.addToCart(' +
                data.index + ', \'' + formatterParams.t + '\')">Add</button>';
            if (config.useportal == 1) {
                var mgr = data.cntManages;
                if (mgr > 0) {
                    html += '&nbsp;<button type="button" class="btn btn-sm btn-success p-0" style="--bs-btn-font-size: 75%;" ' +
                        'onclick="pos.addToCart(' + (-data.perid) + ', \'' + formatterParams.t + '\')">Mgr</button>';
                }
            } else {
                tid = data.tid;
                if (tid != '' && tid != undefined && tid != null) {
                    html += '&nbsp;<button type="button" class="btn btn-sm btn-success p-0" style="--bs-btn-font-size: 75%;" ' +
                        'onclick="pos.addToCart(' + (-tid) + ', \'' + formatterParams.t + '\')">Tran</button>';
                }
            }
            return html;
        }
        return '<span style="font-size: 75%;">In Cart';
    }

    // tabulator formatter for the notes, displays the "O" (open)  and "E" (edit) note for this person
    perNotesIcons(cell, formatterParams, onRendered) { //plain text value
        var index = cell.getRow().getData().index;
        var open_notes = cell.getRow().getData().open_notes;
        var html = "";
        if (open_notes != null && open_notes.length > 0 && !(baseManagerEnabled && this.#manager)) {
            html += '<button type="button" class="btn btn-sm btn-info p-0" style="--bs-btn-font-size: 75%;"  onclick="pos.showPerinfoNotes(' + index + ', \'' + formatterParams.t + '\')">O</button>';
        }
        if (baseManagerEnabled && this.#manager) {
            var btnclass = "btn-secondary";
            if (open_notes != null && open_notes.length > 0)
                btnclass = "btn-info";
            html += ' <button type="button" class="btn btn-sm ' + btnclass + ' p-0" style="--bs-btn-font-size: 75%;" onclick="pos.editPerinfoNotes(' + index + ', \'' + formatterParams.t + '\')">E</button>';
        }
        if (html == "")
            html = "&nbsp;"; // blank draws nothing
        return html;
    }

// display the note popup with the requested notes
    showPerinfoNotes(index, where) {
        var note = null;
        var fullName = null;
        this.#notesType = null;

        if (where == 'cart') {
            note = cart.getPerinfoNote(index);
            fullName = cart.getFullName(index);
            this.#notesType = 'PC';
        }
        if (where == 'result') {
            note = this.#result_perinfo[index].open_notes;
            fullName = this.#result_perinfo[index].fullName;
            this.#notesType = 'PR';
        }
        if (where == 'add') {
            note = this.#add_perinfo[index].open_notes
            fullName = this.#add_perinfo[index].fullName;
            this.#notesType = 'add';
        }

        if (this.#notesType == null)
            return;

        this.#notesIndex = index;

        this.#notes.show();
        document.getElementById('NotesTitle').innerHTML = "Notes for " + fullName;
        document.getElementById('NotesBody').innerHTML = note.replace(/\n/g, '<br/>');
        var notes_btn = document.getElementById('close_note_button');
        notes_btn.innerHTML = "Close";
        notes_btn.disabled = false;
    }

// editPerinfoNotes: display in an editor the perinfo notes field
// only managers can edit the notes
    editPerinfoNotes(index, where) {
        var note = null;
        var fullName = null;

        if (!this.#manager  || !baseManagerEnabled)
            return;

        this.#notesType = null;
        if (where == 'cart') {
            note = cart.getPerinfoNote(index);
            fullName = cart.getFullName(index);
            this.#notesType = 'PC';
        }
        if (where == 'result') {
            note = this.#result_perinfo[index].open_notes;
            fullName = this.#result_perinfo[index].fullName;
            this.#notesType = 'PR';
        }
        if (where == 'add') {
            note = this.#add_perinfo[index].open_notes
            fullName = this.#add_perinfo[index].fullName;
            this.#notesType = 'add';
        }
        if (this.#notesType == null)
            return;

        this.#notesIndex = index;
        this.#notesPriorValue = note;
        if (this.#notesPriorValue === null) {
            this.#notesPriorValue = '';
        }

        this.#notes.show();
        document.getElementById('NotesTitle').innerHTML = "Editing Notes for " + fullName;
        document.getElementById('NotesBody').innerHTML =
            '<textarea name="perinfoNote" class="form-control" id="perinfoNote" cols=60 wrap="soft" style="height:400px;">' +
            this.#notesPriorValue +
            "</textarea>";
        var notes_btn = document.getElementById('close_note_button');
        notes_btn.innerHTML = "Save and Close";
        notes_btn.disabled = false;
    }

// show the registration element note, anyone can add a new note, so it needs a save and close button
    showRegNote(perid, index, count) {
        var bodyHTML = '<div class="row mb-2">\n<div class="col-sm-12">\n';
        var note = cart.getRegNote(perid, index);
        var fullName = cart.getRegFullName(perid);``
        var label = cart.getRegLabel(perid, index);
        var newregnote = cart.getNewRegNote(perid, index);

        this.#notesType = 'RC';
        this.#notesIndex = index;
        this.#notesPerid = perid;

        if (count > 0) {
            bodyHTML = note.replace(/\n/g, '<br/>');
        }
        bodyHTML += '<br/>&nbsp;<br/>Enter/Update new note:<br/><input type="text" name="new_reg_note" id="new_reg_note" maxLength=64 size=60>' +
            "</div>\n</div\n";

        this.#notes.show();
        document.getElementById('NotesTitle').innerHTML = "Registration Notes for " + fullName + '<br/>Membership: ' + label;
        document.getElementById('NotesBody').innerHTML = bodyHTML;
        if (newregnote !== undefined) {
            document.getElementById('new_reg_note').value = newregnote;
        }
        var notes_btn = document.getElementById('close_note_button');
        notes_btn.innerHTML = "Save and Close";
        notes_btn.disabled = false;
    }

// saveNote
//  save and update the note based on type
    saveNote() {
        if (document.getElementById('close_note_button').innerHTML.trim() == "Save and Close") {
            if (this.#notesType == 'RC') {
                cart.setRegNote(this.#notesPerid, this.#notesIndex, document.getElementById("new_reg_note").value);
            }
            if (this.#notesType == 'PC' && this.#manager && baseManagerEnabled) {
                cart.setPersonNote(this.#notesIndex, document.getElementById("perinfoNote").value);
            }
            if (this.#notesType == 'PR' && this.#manager && baseManagerEnabled) {
                var new_note = document.getElementById("perinfoNote").value;
                if (new_note != this.#notesPriorValue) {
                    this.#result_perinfo[this.#notesIndex].open_notes = new_note;
                    // search for matching names
                    var postData = {
                        ajax_request_action: 'updatePerinfoNote',
                        perid: this.#result_perinfo[this.#notesIndex].perid,
                        notes: this.#result_perinfo[this.#notesIndex].open_notes,
                        user_id: this.#user_id,
                    };
                    document.getElementById('close_note_button').disabled = true;
                    $.ajax({
                        method: "POST",
                        url: "scripts/pos_updatePerinfoNote.php",
                        data: postData,
                        success: function (data, textstatus, jqxhr) {
                            if (data.error !== undefined) {
                                show_message(data.error, 'error');
                                document.getElementById('close_note_button').disabled = falser;
                                return;
                            }
                            if (data.message !== undefined) {
                                show_message(data.message, 'success');
                            }
                            if (data.warn !== undefined) {
                                show_message(data.warn, 'warn');
                            }
                        },
                        error: function (jqXHR, textstatus, errorThrown) {
                            document.getElementById('close_note_button').disabled = false;
                            showAjaxError(jqXHR, textstatus, errorThrown);
                        }
                    });
                }
            }
        }
        this.#notesType = null;
        this.#notesPerid = null;
        this.#notesIndex = null;
        this.#notesPriorValue = null;
        this.#notes.hide();
    }

    // select the row (tid) from the unpaid list and add it to the cart, switch to the payment tab (used by find unpaid)
    // marks it as a tid (not perid) add by inverting it.  (addToCart will deal with the inversion)
addUnpaid(tid) {
        pos.addToCart(-Number(tid), 'result');
        // force a new transaction for the payment as the cashier is not the same as the check-in in this case.
        pos.addedPayableTransToCart();
    }

// search the online database for a set of records matching the criteria
// find_type: empty: search for memberships
//              unpaid: return all unpaid
//  possible meanings of find_pattern
//      numeric: search for tid or perid matches
//      alphanumeric: search for names in name, badge_name, email_address fields
//
    findRecord(find_type) {
        if (this.#find_result_table != null) {
            this.#find_result_table.destroy();
            this.#find_result_table = null;
        }
        this.#id_div.innerHTML = "";
        clear_message();
        this.#name_search = this.#pattern_field.value.toLowerCase().trim();
        if ((this.#name_search == null || this.#name_search == '') && find_type == 'search') {
            show_message("No search criteria specified", "warn");
            return;
        }

        // search for matching names
        var postData = {
            ajax_request_action: 'findRecord',
            find_type: find_type,
            name_search: this.#name_search,
        };
        $("button[name='find_btn']").attr("disabled", true);
        var _this = this;
        $.ajax({
            method: "POST",
            url: "scripts/pos_findRecord.php",
            data: postData,
            success: function (data, textstatus, jqxhr) {
                if (data.error !== undefined) {
                    show_message(data.error, 'error');
                    return;
                }
                if (data.message !== undefined) {
                    show_message(data.message, 'success');
                }
                if (data.warn !== undefined) {
                    show_message(data.warn, 'warn');
                }
                _this.foundRecord(data);
                $("button[name='find_btn']").attr("disabled", false);
            },
            error: function (jqXHR, textstatus, errorThrown) {
                $("button[name='find_btn']").attr("disabled", false);
                showAjaxError(jqXHR, textstatus, errorThrown);
            }
        });
    }

// successful return from 2 AJAX call - processes found records
// unpaid: one record: put it in the cart and go to pay screen
//      multiple records: show table of records with pay icons
// normal:
//      single row: display record
//      multiple rows: display table of records with add/trans buttons
    foundRecord(data) {
        var row;
        var index;
        var tid;
        var mperid;
        var mem;
        var find_type = data.find_type;
        this.#result_perinfo = data.perinfo;
        this.#name_search = data.name_search;

        // unpaid search: Only used by Cashier
        // zero found: status message
        // 1 found: add it to cart and go to pay
        // 2 or more found: display a table of transactions
        if (find_type == 'unpaid') {
            if (this.#result_perinfo.length == 0) { // no unpaid records
                this.#id_div.innerHTML = 'No unpaid records found';
                return;
            }
            var trantbl = [];
            // loop over unpaid memberships and finding distinct transactions (should this move to a second SQL query?)
            this.everyMembership(this.#result_perinfo, function(_this, mem) {
                tid = mem.tid;
                if (!trantbl.includes(tid)) {
                    trantbl.push(tid);
                    return 1;
                }
                return 0;
            });
            if (trantbl.length == 1) { // only 1 row, add it to the cart and go to pay tab
                tid = trantbl[0];
                for (row in this.#result_perinfo) {
                    if (result_membership[row].tid == tid) {
                        index = result_membership[row].pindex;
                        pos.addToCart(index, 'result');
                    }
                }
                pos.addedPayableTransToCart(); // build the master transaction and attach records
                return;
            }

            // build the data table for tabulator
            this.#unpaid_table  = [];
            // multiple entries unpaid, display table to choose which one
            for (var trow in trantbl) {
                tid = trantbl[trow];
                var price = 0;
                var paid = 0;
                var names = '';
                var num_mem = 0;
                var prowindex = 0;
                var prow = null;
                mperid = -1;
                this.everyMembership(this.#result_perinfo, function(_this, mem) {
                    if (mem.tid == tid) {
                        prowindex = mem.pindex;
                        prow = _this.#result_perinfo[prowindex];
                        num_mem++;
                        price += Number(mem.price);
                        paid += Number(mem.paid);
                        // show each name only once
                        if (mperid != mem.perid) {
                            if (names != '') {
                                names += '; ';
                            }
                            names += prow.fullName+ '(' + prow.perid + ')';
                            mperid = mem.perid;
                        }
                    }
                });

                row = {tid: tid, names: names, num_mem: num_mem, price: price, paid: paid, index: trow};
                this.#unpaid_table.push(row);
            }
            // and instantiate the table into the find_results DOM object (div)
            var _this = this;
            this.#find_result_table = new Tabulator('#find_results', {
                maxHeight: "600px",
                data: this.#unpaid_table,
                layout: "fitColumns",
                initialSort: [
                    {column: "names", dir: "asc"},
                ],
                columns: [
                    {title: "Cart", width: 60, formatter: _this.addCartIcon, formatterParams: {t: "unpaid"}, headerSort: false,},
                    {title: "TID", field: "tid", headerFilter: true, headerWordWrap: true, width: 70, maxWidth: 70, hozAlign: 'right',},
                    {title: "Names", field: "names", headerFilter: true, headerSort: true, headerWordWrap: true, tooltip: true,},
                    {title: "#M", field: "num_mem", minWidth: 50, maxWidth: 50, headerSort: false, hozAlign: 'right',},
                    {title: "Price", field: "price", maxWidth: 80, minWidth: 80, headerSort: false, hozAlign: 'right',},
                    {title: "Paid", field: "paid", maxWidth: 80, minWidth: 80, headerSort: false, hozAlign: 'right',},
                    {field: "index", visible: false,},
                ],
            });
            return;
        }
        // sum print and attach counts
        var print_count = 0;
        var attach_count = 0;
        var memCount = 0;
        var regtids = [];
        var rowindex;
        var memberships;

        memCount = this.everyMembership(this.#result_perinfo, function(_this, mem) {
            print_count += Number(mem.printcount);
            attach_count += Number(mem.attachcount);
            return 1;
        });

        // not unpaid search... mark the type of the primary membership in the person row for the table
        // find primary membership for each result_perinfo record
        for (rowindex in this.#result_perinfo) {
            row = this.#result_perinfo[rowindex];
            mem = row.memberships;
            var primmem = this.find_primary_membership(mem);
            if (primmem != null) {
                row.reg_label = mem[primmem].label;
                tid = mem[primmem].tid;
                if (tid != '') {
                    var other = false;
                    this.#checkPerid = row.perid;

                    other = !this.#result_perinfo.every(this.notPerid, this);
                    if (other) {
                        row.tid = tid;
                    }
                }
            } else {
                row.reg_label = 'No Primary Membership';
                row.reg_tid = '';
            }
        }

        // string search, returning more than one row show tabulator table
        if (isNaN(this.#name_search) && this.#result_perinfo.length > 1) {
            // table
            var _this = this;
            this.#find_result_table = new Tabulator('#find_results', {
                maxHeight: "600px",
                data: this.#result_perinfo,
                layout: "fitColumns",
                initialSort: [
                    {column: "fullName", dir: "asc"},
                ],
                columns: [
                    {title: "Cart", width: 100, headerFilter: false, headerSort: false, formatter: _this.addCartIcon, formatterParams: {t: "result"},},
                    {title: "Per ID", field: "perid", headerWordWrap: true, width: 80, visible: false, hozAlign: 'right',},
                    {field: "index", visible: false,},
                    {title: "Full Name", field: "fullName", headerFilter: true, headerFilterFunc: fullNameHeaderFilter, headerWordWrap: true,
                        tooltip: posbuildRecordHover, formatter: "textarea", },
                    {field: "last_name", visible: false,},
                    {field: "first_name", visible: false,},
                    {field: "middle_name", visible: false,},
                    {field: "suffix", visible: false,},
                    {field: "legalName", visible: false,},
                    {field: "pronouns", visible: false,},
                    {title: "Badge Name", field: "badge_name", headerFilter: true, headerWordWrap: true, tooltip: true,},
                    {title: "Zip", field: "postal_code", headerFilter: true, headerWordWrap: true, tooltip: true, maxWidth: 70, width: 70},
                    {title: "Email Address", field: "email_addr", headerFilter: true, headerWordWrap: true, tooltip: true,},
                    {title: "Reg", field: "reg_label", headerFilter: true, headerWordWrap: true, tooltip: true, maxWidth: 120, width: 120,},
                    {title: "Nt", width: 45, headerSort: false, headerFilter: false, formatter: posPerNotesIcons, formatterParams: {t: "result"},},

                    {field: "index", visible: false,},
                ],
            });
        } else if (this.#result_perinfo.length > 0) {  // one row string, or all perinfo/tid searches, display in record format
            if ((!isNaN(this.#name_search)) && regtids.length == 1 && (attach_count > 0 || print_count > 0)) {
                // only 1 transaction returned and it was search by number, and it's been attached for payment before
                // add it to the cart and go to payment
                for (row in result_membership) {
                    if ((result_membership[row].tid == tid) || (result_membership[row].rstid == this.#name_search)) {
                        index = result_membership[row].pindex;
                        pos.addToCart(index, 'result');
                    }
                }
                pos.addedPayableTransToCart();
                return;
            }
            this.#number_search = Number(this.#name_search);
            this.drawAsRecords();
            if (config.autoloadTID) {
                this.addToCart(-this.#name_search, 'result');
                config.autoloadTID = undefined;
            }
            return;
        }
        // no rows show the diagnostic
        this.#id_div.innerHTML = `"container-fluid">
    <div class="row mt-3">
        <div class="col-sm-4">No matching records found</div>
        <div class="col-sm-auto"><button class="btn btn-primary btn-sm" type="button" id="notFoundAddNew" onclick="pos.notFoundAddNew();">Add New Person</button>
        </div>
    </div>
</div>
`;
        this.#id_div.innerHTML = 'No matching records found'
    }

// draw perinfo as full record, not tabular data
    drawAsRecords() {
        var html = '';
        var first = false;
        var row;
        if (this.#result_perinfo.length > 1) {
            first = true;
        }
        for (row in this.#result_perinfo) {
            html += this.drawRecord(row, first);
            first = false;
        }
        html += '</div>';
        this.#id_div.innerHTML = html;
    }

// when searching, if clicking on the add new button, switch to the add/edit tab
    notFoundAddNew() {
        this.#id_div.innerHTML = '';
        this.#pattern_field.value = '';

        bootstrap.Tab.getOrCreateInstance(this.#add_tab).show();
    }

// switch to the review tab when the review button is clicked
    startReview() {
        if (!this.confirmDiscardAddEdit(false))
            return;
        cart.hideNoChanges();
        cart.freeze();
        cart.drawCart();

        // set tab to review-tab
        bootstrap.Tab.getOrCreateInstance(this.#review_tab).show();
        this.#review_tab.disabled = false;
    }

// create the review data screen from the cart
    reviewUpdate() {
        cart.updateReviewData();
        this.reviewShown();
        if (this.#reviewMissingPolicies > 0 && this.#print_tab)
            this.#print_tab.disabled =true;

        if (this.#reviewMissingItens > 0) {
            setTimeout(reviewNoChanges, 100);
        } else {
            this.reviewNoChanges();
        }
    }

    addedPayableTransToCart() {
        // clear any search remains
        if (this.#add_results_table != null) {
            this.#add_results_table.destroy();
            this.#add_results_table = null;
        }
        if (this.#find_result_table != null) {
            this.#find_result_table.destroy();
            this.#find_result_table = null;
        }
        this.#id_div.innerHTML = '';
        cart.showNoChanges();
    }


// no changes button pressed:
// if everything is put up next customer
    reviewNoChanges() {
        // first check to see if any required fields still exist
        if (this.#reviewMissingItens > 0) {
            if (!confirm("Proceed ignoring check for " + this.#reviewMissingItens.toString() + " missing data items (shown in yellow)?")) {
                return false; // confirm answered no, return not safe to discard
            }
        }

        if (this.#reviewMissingPolicies > 0 && this.#print_tab)
            this.#print_tab.disabled = true;

        cart.hideNoChanges();
        // submit the current card data to update the database, retrieve all TID's/PERID's/REGID's of inserted data
        var postData = {
            ajax_request_action: 'updateCartElements',
            cart_perinfo: JSON.stringify(cart.getCartPerinfo()),
            user_id: this.#user_id,
            source: config['source'],
        };
        var _this = this;
        clear_message();
        $.ajax({
            method: "POST",
            url: "scripts/pos_updateCartElements.php",
            data: postData,
            success: function (data, textstatus, jqxhr) {
                if (data.error !== undefined) {
                    show_message(data.error, 'error');
                    return;
                }
                if (data.message !== undefined) {
                    show_message(data.message, 'success');
                }
                if (data.warn !== undefined) {
                    show_message(data.warn, 'warn');
                }
                _this.reviewedUpdateCart(data);
            },
            error: showAjaxError,
        });
    }

// reviewedUpdateCart:
//  all the data from the cart has been updated in the database, now apply the id's and proceed to the next step
    reviewedUpdateCart(data) {
        this.#pay_tid = data.master_tid;
        this.#pay_tid_amt = 0;
        // update cart elements
        var unpaidRows = cart.updateFromDB(data);
        if (data.success)
            show_message(data.success, 'success');
        else
            clear_message();

        // set tab to review-tab
        if (unpaidRows == 0) {
            if (this.#print_tab) {
                if (this.#reviewMissingPolicies == 0) {
                    this.gotoPrint();
                    return;
                } else {
                    this.#print_tab.disabled = true;
                    cart.showNext();
                    cart.hideStartOver();
                    cart.freeze();
                    show_message("Printing is disabled with missing required policies", "warn");
                    return;
                }
            }
            cart.showNext();
            cart.hideStartOver();
            cart.freeze();
            var el = document.getElementById('review-btn-update');
            if (el)
                el.hidden = true;
            el = document.getElementById('review-btn-nochanges');
            if (el)
                el.hidden = true;
            show_message('Memberships added, cart saved, nothing to pay, press the "Next Customer" button to continue', 'success');
            return;
        }

        if (config.cashier == 1) {
            this.gotoPay();
            return;
        } else {
            cart.showNext();
            cart.hideStartOver();
            cart.freeze();
            var el = document.getElementById('review-btn-update');
            if (el)
                el.hidden = true;
            el = document.getElementById('review-btn-nochanges');
            if (el)
                el.hidden = true;
            el = document.getElementById('review_status');
            if (el) {
                var html = "<strong>Completed: Send customer to cashier with id of " + this.#pay_tid + '</strong>';
                if (config.hasOwnProperty('cashierAllowed') && config.cashierAllowed == 1)
                    html += "<br/>or click here to call up this transaction as a cashier: " +
                        '<btn class="btn btn-secondary btn-sm" type="button" id="switch-casher-btn" ' +
                        'onclick="window.location=' + "'?mode=cashier&tid=" + this.#pay_tid + "'" + '">' +
                        'Take Payment Here</button>';
                el.innerHTML = html;
            }
        }
    }

// transition to payment processing
    gotoPay() {
        // build the order
        var postData = {
            ajax_request_action: 'buildOrder',
            cart_perinfo: JSON.stringify(cart.getCartPerinfo()),
            pay_tid: this.#pay_tid,
        };
        if (this.#pay_currentOrderId) {
            postData.cancelOrder = this.#pay_currentOrderId;
            this.#pay_currentOrderId = null;
        }
        if (coupon.isCouponActive()) {
            postData.couponCode = coupon.getCouponCode();
            postData.couponDiscount = this.#couponDiscount;
        }
        if (this.#managerDiscount > 0) {
            postData.discountAmt = this.#managerDiscount;
            postData.drow = this.#drow;
        }

        var _this = this;
        clear_message();
        $.ajax({
            method: "POST",
            url: "scripts/pos_buildOrder.php",
            data: postData,
            success: function (data, textstatus, jqxhr) {
                var stop = true;
                if (typeof data == 'string') {
                    show_message(data, 'error');
                } else if (data.error !== undefined) {
                    show_message(data.error, 'error');
                } else if (data.message !== undefined) {
                    show_message(data.message, 'success');
                    stop = false;
                } else if (data.warn !== undefined) {
                    show_message(data.warn, 'warn');
                    stop = false;
                } else if (data.status == 'error') {
                    show_message(data.data, 'error');
                } else
                    stop = false;
                if (!stop)
                    _this.buildOrderSuccess(data);
            },
            error: function (jqXHR, textstatus, errorThrown) {
                showAjaxError(jqXHR, textstatus, errorThrown);
            },
        });
    }

    buildOrderSuccess(data) {
        this.#pay_currentOrderId = data.rtn.orderId;
        this.#preTaxAmt = data.rtn.preTaxAmt;
        this.#taxAmt = data.rtn.taxAmt;
        this.#taxLabel = data.rtn.taxLabel;
        this.#totalPaid = data.rtn.totalPaid;
        show_message("Order #" + this.#pay_currentOrderId + " created.");
        bootstrap.Tab.getOrCreateInstance(this.#pay_tab).show();
        if (this.#payForcePayShown) {
            this.#payForcePayShown = false;
            this.payShown();
        }
        if (data.hasOwnProperty('badges')) {
            // badges in return, update cart rows
            for (var i = 0; i < data.badges.length; i++) {
                var badge = data.badges[i];
                cart.setCouponDisount(badge.perid, badge.regid, badge.paid, badge.coupon, badge.couponDiscount)
            }
        }
        cart.drawCart();
    }

// gotoPrint switch to the print tab
    gotoPrint() {
        this.#printedObj = null;
        this.#pay_currentOrderId = null; // clear order id if we leave payment tab
        bootstrap.Tab.getOrCreateInstance(this.#print_tab).show();
    }


// setPayType: shows/hides the appropriate fields for that payment type
    setPayType(ptype) {
        var elcheckno = document.getElementById('pay-check-div');
        var elccauth = document.getElementById('pay-ccauth-div');
        var elonline = document.getElementById('pay-online-div');
        var elcash = document.getElementById('pay-cash-div');
        var eldisc = document.getElementById('pay-disc-div');
        var eldiscount = document.getElementById('pt-discount');
        var couponRow = document.getElementById('couponRow');

        elcheckno.hidden = ptype != 'check';
        elccauth.hidden = ptype != 'credit';
        elonline.hidden = ptype != 'online';
        elcash.hidden = ptype != 'cash';
        eldisc.hidden = ptype != 'discount';

        if (couponRow && eldiscount)
            couponRow.hidden = document.getElementById('pt-discount').checked;

        this.#pay_button_pay.innerHTML = 'Confirm Pay';
        this.#pay_button_pay.disabled = ptype == 'online';


        if (ptype != 'check') {
            document.getElementById('pay-checkno').value = null;
        }
        if (ptype != 'credit') {
            document.getElementById('pay-ccauth').value = null;
        }
        if (ptype != 'cash') {
            document.getElementById('pay-tendered').value = null;
        }
        if (ptype != 'discount') {
            document.getElementById('pay-discount').value = null;
        }
    }

// overridePay - pay returned the terminal was unavailable, operator said to override it
    overridePay(){
        this.#payOverride = 1;
        this.pay('');
    }

// payPoll - poll to see if the payment is complete
    payPoll(action) {
        document.getElementById('pollRow').hidden = true;
        if (action == 1) { // asked to poll for is it complete
            this.#payPoll = 1;
            this.pay('');
            return;
        }
        // cancel terminal request
        var postData = {
            ajax_request_action: 'cancelPayRequest',
            requestId: this.#payCurrentRequest,
            user_id: this.#user_id,
        };
        var _this = this;
        clear_message();
        $.ajax({
            method: "POST",
            url: "scripts/pos_cancelPayment.php",
            data: postData,
            success: function (data, textstatus, jqxhr) {
                _this.cancelSuccess(data);
            },
            error: function (jqXHR, textstatus, errorThrown) {
                document.getElementById('pollRow').hidden = false;
                _this.#pay_button_pay.disabled = true;
                showAjaxError(jqXHR, textstatus, errorThrown);
            },
        });
    }

    cancelSuccess(data) {
        this.#pay_button_pay.disabled = false;

        // things that stop us cold....
        if (typeof data == 'string') {
            show_message(data, 'error');
            document.getElementById('pollRow').hidden = false;
            return;
        }

        if (data.error !== undefined) {
            show_message(data.error, 'error');
            document.getElementById('pollRow').hidden = false;
            return;
        }

        if (data.status == 'error') {
            show_message(data.data, 'error');
            document.getElementById('pollRow').hidden = false;
            return;
        }

        if (data.warn !== undefined) {
            show_message(data.warn, 'warn');
            // warn means we could not get the terminal, ask if we want to override it
            if (data.status != 'OFFLINE') {
                document.getElementById('overrideRow').hidden = false;
                return;
            }
        }

        this.#payPoll = 0;
        this.#payCurrentRequest = null;
        // and things that continue
        if (data.message !== undefined) {
            show_message(data.message, 'success');
        }

        document.getElementById('pollRow').hidden = true;
        this.#pay_button_pay.disabled = false;
        this.payShown();
    }

// Process a payment against the transaction
    pay(nomodal, prow = null, nonce = null) {
        var checked = false;
        var ccauth = null;
        var checkno = null;
        var desc = null;
        var ptype = null;
        var total_amount_due = Number(this.#preTaxAmt) + Number(this.#taxAmt) - (Number(this.#couponDiscount) + Number(this.#managerDiscount));
        var pt_cash = document.getElementById('pt-cash').checked;
        var pt_check = document.getElementById('pt-check').checked;
        var pt_online = document.getElementById('pt-online');
        var pt_credit = document.getElementById('pt-credit');
        var pt_terminal = document.getElementById('pt-terminal');
        var pt_discount = document.getElementById('pt-discount');

        document.getElementById('overrideRow').hidden = true;

        if (this.#pay_currentOrderId == null) {
            show_message("No order in progress, you have reached an error condition, start over or seek assistance", "error");
            return;
        }

        if (pt_online)
            pt_online = pt_online.checked;
        else
            pt_online = false;

        if (pt_credit)
            pt_credit = pt_credit.checked;
        else
            pt_credit = false;

        if (pt_terminal)
            pt_terminal = pt_terminal.checked;
        else
            pt_terminal = false;

        if (pt_discount)
            pt_discount = pt_discount.checked;
        else
            pt_discount = false;

        if (nomodal != '') {
            this.#cashChangeModal.hide();
        }

        tendered_amt = 0;
        discount_amt = 0;

        if (prow == null) {
            // validate the payment entry: It must be >0 and <= amount due
            //      a payment type must be specified
            //      for check: the check number is required
            //      for credit card: the auth code is required
            //      for discount: description is required, it's optional otherwise

            if (pt_cash) {
                var eltenderedamt = document.getElementById('pay-tendered');
                var tendered_amt = Number(eltenderedamt.value);
                if (tendered_amt < total_amount_due) {
                    eltenderedamt.style.backgroundColor = 'var(--bs-warning)';
                    return;
                }
                if (tendered_amt > total_amount_due) {
                    if (nomodal == '') {
                        this.#cashChangeModal.show();
                        document.getElementById("CashChangeBody").innerHTML = "<div class='row mt-2'>\n<div class='col-sm-12'>" +
                            "Customer owes $" + total_amount_due.toFixed(2) + ", and tendered $" + tendered_amt.toFixed(2) +
                            "</div>\n</div>\n<div class='row mt-2 mb-2'>\n<div class='col-sm-12'>" +
                            "Confirm change give to customer of $" + (tendered_amt - total_amount_due).toFixed(2) +
                            "</div>\n</div>\n";
                        return;
                    }
                } else {
                    eltenderedamt.style.backgroundColor = '';
                }
            }

            var elptdiv = document.getElementById('pt-div');
            elptdiv.style.backgroundColor = '';

            var eldesc = document.getElementById('pay-desc');
            if (pt_discount) {
                ptype = 'discount';
                desc = eldesc.value;
                if (desc == null || desc == '') {
                    eldesc.style.backgroundColor = 'var(--bs-warning)';
                    return;
                } else {
                    eldesc.style.backgroundColor = '';
                }
                checked = true;
            } else {
                eldesc.style.backgroundColor = '';
            }

            if (pt_terminal) {
                ptype = 'terminal';
                checked = true;
            }

            if (pt_check) {
                ptype = 'check';
                var elcheckno = document.getElementById('pay-checkno');
                checkno = elcheckno.value;
                if (checkno == null || checkno == '') {
                    elcheckno.style.backgroundColor = 'var(--bs-warning)';
                    return;
                } else {
                    elcheckno.style.backgroundColor = '';
                }
                checked = true;
            }

            if (pt_credit) {
                ptype = 'credit';
                var elccauth = document.getElementById('pay-ccauth');
                ccauth = elccauth.value;
                if (ccauth == null || ccauth == '') {
                    elccauth.style.backgroundColor = 'var(--bs-warning)';
                    return;
                } else {
                    elccauth.style.backgroundColor = '';
                }
                checked = true;
            }

            if (pt_online) {
                ptype = 'online';
                if (nonce == null) {
                    alert("Credit Card Processing Error: Unable to obtain nonce token");
                    $('#' + this.#purchase_label).removeAttr("disabled");
                    return;
                }
                checked = true;
            }

            if (pt_cash) {
                ptype = 'cash';
                checked = true;
            }

            if (!checked) {
                elptdiv.style.backgroundColor = 'var(--bs-warning)';
                if (pt_online)
                    $('#' + this.#purchase_label).removeAttr("disabled");
                return;
            }

            if (pt_discount) {
                var eltenderedamt = document.getElementById('pay-discount');
                var discount_amt = Number(eltenderedamt.value);
                if (discount_amt <=  0 || discount_amt > total_amount_due) {
                    eltenderedamt.style.backgroundColor = 'var(--bs-warning)';
                    return;
                }
                this.#drow = {
                    index: cart.getPmtLength() + 1, amt: discount_amt, ccauth: ccauth, checkno: checkno, desc: eldesc.value, type: 'discount',
                };
                cart.addPmt(this.#drow, true);
                this.#managerDiscount = discount_amt;
                document.getElementById('pt-discount').checked = false;
                document.getElementById('pay-discount').value = '';
                eldesc.value = '';
                this.setPayType('none');
                this.#payForcePayShown = true;
                pos.gotoPay();
                return;
            }

            if (tendered_amt > 0) {
                var crow = null;
                var change = 0;
                if (tendered_amt > total_amount_due) {
                    change = tendered_amt - total_amount_due;
                    crow = {
                        index: cart.getPmtLength() + 1, amt: -change, ccauth: ccauth, checkno: checkno, desc: eldesc.value, type: 'change',
                    };
                }
            }

            var payorPerid = 2; // atcon perid
            var email = document.getElementById("pay-email").value;
            var phone = document.getElementById("pay-phone").value;
            var payor = Number(document.getElementById('pay-emailsel').value);
            var country = '';
            var couponCode = coupon.getCouponCode();
            var cprow = cart.getCouponPmt();

            if (payor >= 0 && email == cart.getEmail(payor)) {
                payorPerid = cart.getPerid(payor);
                country = cart.getCountry(payor);
            }

            prow = {
                index: cart.getPmtLength(), amt: total_amount_due, ccauth: ccauth, checkno: checkno, desc: eldesc.value,
                type: ptype, nonce: nonce, coupon: couponCode,
                payor: {
                    email: email,
                    phone: phone,
                    perid: payorPerid,
                    country: country,
                },
            };
        }
        // process payment
        var postData = {
            ajax_request_action: 'processPayment',
            orderId: this.#pay_currentOrderId,
            cart_perinfo: JSON.stringify(cart.getCartPerinfo()),
            new_payment: prow,
            coupon: coupon.getCouponId(),
            couponPayment: cprow,
            change: crow,
            nonce: nonce,
            user_id: this.#user_id,
            pay_tid: this.#pay_tid,
            pay_tid_amt: this.#pay_tid_amt,
            preTaxAmt: this.#preTaxAmt,
            taxAmt: this.#taxAmt,
            totalAmtDue: total_amount_due,
            couponDiscount: this.#couponDiscount,
            override: this.#payOverride,
            poll: this.#payPoll,
            drow: this.#drow,
            discountAmt: this.#managerDiscount,
        };
        this.#payOverride = 0;
        this.#pay_button_pay.disabled = true;
        var _this = this;
        clear_message();
        $.ajax({
            method: "POST",
            url: "scripts/pos_processPayment.php",
            data: postData,
            success: function (data, textstatus, jqxhr) {
                _this.paySuccess(data);
            },
            error: function (jqXHR, textstatus, errorThrown) {
                _this.#pay_button_pay.disabled = false;
                $('#' + _this.#purchase_label).removeAttr("disabled");
                showAjaxError(jqXHR, textstatus, errorThrown);
            },
        });
    }

    paySuccess(data) {
        // reset the disabled items
        $('#' + this.#purchase_label).removeAttr("disabled");


        // things that stop us cold....
        if (typeof data == 'string') {
            show_message(data, 'error');
            if (data.hasOwnProperty("cancelled")) {
                this.#payPoll = 0;
                this.#payCurrentRequest = null;
                this.#pay_button_pay.disabled = false;
            } else if (this.#payPoll == 1)
                document.getElementById('pollRow').hidden = false;
            return;
        }

        if (data.error !== undefined) {
            show_message(data.error, 'error');
            if (data.error.includes("cancelled")) {
                this.#payPoll = 0;
                this.#payCurrentRequest = null;
                this.#pay_button_pay.disabled = false;
            }  else if (this.#payPoll == 1)
                document.getElementById('pollRow').hidden = false;
            return;
        }

        if (data.status == 'error') {
            show_message(data.data, 'error');
            if (data.hasOwnProperty('error') && data.error.hasOwnProperty("cancelled")) {
                this.#payPoll = 0;
                this.#payCurrentRequest = null;
                this.#pay_button_pay.disabled = false;
            } else if (this.#payPoll == 1)
                document.getElementById('pollRow').hidden = false;
            return;
        }

        if (data.warn !== undefined) {
            show_message(data.warn, 'warn');
            // warn means we could not get the terminal, ask if we want to override it
            if (data.status != 'OFFLINE') {
                document.getElementById('overrideRow').hidden = false;
                return;
            }
        }

        this.#payPoll = 0;
        this.#pay_button_pay.disabled = false;
        // and things that continue
        if (data.message !== undefined) {
            show_message(data.message, 'success');
        }
        if (data.hasOwnProperty('poll')) {
            if (data.poll == 1) {
                if (data.id) {
                    this.#payCurrentRequest = data.id;
                }
                document.getElementById('pollRow').hidden = false;
                this.#pay_button_pay.disabled = true;
                this.#payPoll = 1;
                return;
            }
        }
        cart.updatePmt(data);
        this.#payCurrentRequest = null;
        this.#pay_tid_amt += Number(data.pay_amt);
        this.payShown();
    }

// Create a receipt and email it
    emailReceipt(receipt_type) {
        this.#lastReceiptType = receipt_type;
        // header text
        var header_text = cart.receiptHeader(this.#user_id, this.#pay_tid);
        // optional footer text
        var footer_text = '';
        // server side will print the receipt
        var postData = {
            user_id: this.#user_id,
            ajax_request_action: 'printReceipt',
            header: header_text,
            prows: JSON.stringify(cart.getCartPerinfo()),
            pmtrows: JSON.stringify(cart.getCartPmt()),
            footer: footer_text,
            receipt_type: receipt_type,
            email_addrs: this.#emailAddreesRecipients,
        };
        if (this.#receiptPrinterAvailable || receipt_type == 'email') {
            if (receipt_type == 'email')
                this.#pay_button_ercpt.disabled = true;
            else
                this.#pay_button_rcpt.disabled = true;
        }

        var _this = this;
        clear_message();
        $.ajax({
            method: "POST",
            url: "scripts/pos_emailReceipt.php",
            data: postData,
            success: function (data, textstatus, jqxhr) {
                if (typeof data == "string") {
                    show_message(data, 'error');
                } else if (data.error !== undefined) {
                    show_message(data.error, 'error');
                } else if (data.message !== undefined) {
                    show_message(data.message, 'success');
                } else if (data.warn !== undefined) {
                    show_message(data.warn, 'warn');
                }
                if (_this.#lastReceiptType == 'email')
                    _this.#pay_button_ercpt.disabled = false;
                else
                    _this.#pay_button_rcpt.disabled = false;
            },
            error: function (jqXHR, textstatus, errorThrown) {
                if (_this.#lastReceiptType == 'email')
                    _this.#pay_button_ercpt.disabled = false;
                else
                    _this.#pay_button_rcpt.disabled = false;
                showAjaxError(jqXHR, textstatus, errorThrown);
            }
        });
    }

// Send one or all of the badges to the printer
    printBadge(cindex, mindex) {
        var rownum = 0;
        var cartlen = cart.getCartLength();

        var params = [];
        if (cindex >= 0) {
            params.push(cart.getBadge(cindex, mindex));
        } else {
            for (rownum in this.#badgeList) {
                params.push(cart.getBadge(this.#badgeList[rownum][0], this.#badgeList[rownum][1]));
            }
        }
        var postData = {
            ajax_request_action: 'printBadge',
            params: JSON.stringify(params),
        };
        $("button[name='print_btn']").attr("disabled", true);
        var _this = this;
        clear_message();
        $.ajax({
            method: "POST",
            url: "scripts/pos_printBadge.php",
            data: postData,
            success: function (data, textstatus, jqxhr) {
                if (data.constructor.name !== 'Object' ) {
                    show_message(data, 'error');
                    $("button[name='print_btn']").attr("disabled", false);
                    return;
                }
                if (data.error !== undefined) {
                    show_message(data.error, 'error');
                    $("button[name='print_btn']").attr("disabled", false);
                    return;
                }
                _this.printComplete(data);
            },
            error: function (jqXHR, textstatus, errorThrown) {
                $("button[name='print_btn']").attr("disabled", false);
                showAjaxError(jqXHR, textstatus, errorThrown);
            },
        });
    }

    printComplete(data) {
        var badges = data.badges;
        var regs = [];
        var index;
        for (index in badges) {
            var regId = badges[index]['regId'];
            var printCount = badges[index]['printCount'];
            if (this.#printedObj.get(regId) == 0) {
                this.#printedObj.set(regId, 1);
                regs.push({ regid: regId, printcount: printCount + 1});
            }
        }
        if (regs.length > 0) {
            var postData = {
                ajax_request_action: 'updatePrintcount',
                regs: regs,
                user_id: this.#user_id,
                tid: this.#pay_tid,
                source: config['source'],
            };
            clear_message();
            $.ajax({
                method: "POST",
                url: "scripts/pos_updatePrintCount.php",
                data: postData,
                success: function (data, textstatus, jqxhr) {
                    if (data.error !== undefined) {
                        show_message(data.error, 'error');
                        return;
                    }
                },
                error: showAjaxError,
            });
        }
        $("button[name='print_btn']").attr("disabled", false);
        this.printShown();
        show_message(data.message, 'success');
    }
// tab shown events - state mapping for which tab is shown
    findShown() {
        this.#printActive = false;
        this.#pay_currentOrderId = null; // leaving pay clears order id
        cart.clearInReview();
        cart.unfreeze();
        this.#current_tab = this.#find_tab;
        cart.drawCart();
    }

    addShown() {
        this.#printActive = false;
        this.#pay_currentOrderId = null; // leaving pay clears order id
        cart.clearInReview();
        cart.unfreeze();ƒvoi
        cart.drawCart();
    }

    reviewShown() {
        this.#printActive = false;

        // draw review section
        this.#current_tab = this.#review_tab;
        this.#review_div.innerHTML = cart.buildReviewData();
        cart.setInReview();
        cart.freeze();
        cart.setCountrySelect();
    }

    toggleRecipientEmail(row) {
        var emailCheckbox = document.getElementById('emailAddr_' + row.toString());
        var email_address = cart.getEmail(row);
        if (emailCheckbox.checked) {
            if (!this.#emailAddreesRecipients.includes(email_address)) {
                this.#emailAddreesRecipients.push(email_address);
            }
        } else {
            if (this.#emailAddreesRecipients.includes(email_address)) {
                for (var index = 0; index < this.#emailAddreesRecipients.length; index++) {
                    if (this.#emailAddreesRecipients[index] == email_address)
                        this.#emailAddreesRecipients.splice(index, 1);
                }
            }
        }
        this.#pay_button_ercpt.disabled = this.#emailAddreesRecipients.length == 0;
    }

    checkboxCheck() {
        var emailCheckbox = document.getElementById('emailAddr_' + this.#last_email_row.toString());
        if (emailCheckbox) {
            emailCheckbox.checked = true;
        }
        this.#pay_button_ercpt.hidden = false;
        this.#pay_button_ercpt.disabled = false;
    }

// applyCoupon - apply and compute the discount for a coupon, also show the rules for the coupon if applied
//  a = apply coupon from select
//  r = remove coupon
//  in any case need to re-show the pay tab with the details
    applyCoupon(cmd) {
        if (cmd == 'r') {
            var curCoupon = coupon.getCouponId();
            cart.clearCoupon(curCoupon);
            coupon = null;
            coupon = new Coupon();
            this.#payForcePayShown = true;
            this.gotoPay();
            return;
        }
        if (cmd == 'a') {
            var couponId = document.getElementById("pay_couponSelect").value;
            coupon = null;
            coupon = new Coupon();
            if (couponId == '') {
                show_message("Coupon cleared, no coupon applied", 'success');
                return;
            }
            this.#couponDiscount = Number(coupon.CartDiscount()).toFixed(2);
            this.#payForcePayShown = true;
            coupon.loadCoupon(couponId);
        }
        return;
    }

    selectPayor() {
        var rownum = document.getElementById("pay-emailsel").value;
        if (rownum < 0) {
            document.getElementById("pay-email").value = '';
            document.getElementById("pay-phone").value = '';
        } else {
            document.getElementById("pay-email").value = cart.getEmail(rownum);
            document.getElementById("pay-phone").value = cart.getPhone(rownum);
        }
    }

    payShown() {
        cart.clearInReview();
        cart.freeze();
        this.#current_tab = this.#pay_tab;
        cart.drawCart();

        var total_amount_due = this.#taxAmt + cart.getTotalPrice() - (cart.getTotalPaid() + Number(this.#couponDiscount) + Number(this.#managerDiscount));
        if (total_amount_due < 0.01) { // allow for rounding error, no need to round here
            this.#pay_currentOrderId = null;
            // nothing more to pay
            if (this.#print_tab)
                this.#print_tab.disabled = false;
            cart.showNext();
            if (this.#pay_button_pay != null) {
                var rownum;
                this.#pay_button_pay.hidden = true;
                this.#pay_button_rcpt.hidden = false;
                document.getElementById('payFormDiv').innerHTML = '';
                // hide the rest of the payment items
                var email_html = '';
                var email_count = 0;
                this.#last_email_row = -1;
                var cartlen = cart.getCartLength();
                rownum = 0;
                while (rownum < cartlen) {
                    var email_addr = cart.getEmail(rownum);
                    if (validateAddress(email_addr)) {
                        email_html += '<div class="row"><div class="col-sm-1 text-end pe-2"><input type="checkbox" id="emailAddr_' + rownum.toString() +
                            '" name="receiptEmailAddrList" onclick="pos.toggleRecipientEmail(' + rownum.toString() + ')"/></div><div class="col-sm-8">' +
                            '<label for="emailAddr_' + rownum.toString() + '">' + email_addr + '</label></div></div>';
                        email_count++;
                        this.#last_email_row = rownum;
                    }
                    rownum++;
                }
                if (email_html.length > 2) {
                    this.#pay_button_ercpt.hidden = false;
                    this.#pay_button_ercpt.disabled = false;
                    this.#receeiptEmailAddresses_div.innerHTML = '<div class="row mt-2"><div class="col-sm-9 p-0">Email receipt to:</div></div>' +
                        email_html;
                    if (email_count == 1) {
                        this.#emailAddreesRecipients.push(cart.getEmail(this.#last_email_row));
                        setTimeout(checkboxCheck, 100);
                    }
                }
            } else {
                if (this.#print_tab) {
                    if (this.#reviewMissingPolicies == 0) {
                        this.gotoPrint();
                        return;
                    } else {
                        cart.showNext();
                        cart.hideStartOver();
                        cart.freeze();
                        this.#print_tab.disabled = true;
                        show_message("Printing is disabled with missing required policies", "warn");
                        return;
                    }
                }
            }
        } else {
            if (this.#pay_button_pay != null) {
                this.#pay_button_pay.hidden = false;
                this.#pay_button_ercpt.hidden = true;
                this.#pay_button_ercpt.disabled = true;
            }

            // draw the pay screen
            var pay_html = `
<div id='payBody' class="container-fluid">
 <div id="payFormDiv" class="container-fluid form-floating">
  <form id='payForm' action='javascript: return false; ' class="form-floating">
    <div class="row pb-2">
        <div class="col-sm-auto ms-0 me-2 p-0">New Payment Transaction ID: ` + this.#pay_tid + `</div>
    </div>
    `;
            if (this.#num_coupons > 0 && this.#drow == null) {
                if (cart.allowAddCouponToCart()) {
                    // cannot apply a coupon if one was already in the cart (and of course, there need to be valid coupons right now)
                    if (!coupon.isCouponActive()) { // no coupon applied yet or a discount is applied
                        pay_html += `
    <div class="row mt-3" id="couponRow">
        <div class="col-sm-2 ms-0 me-2 p-0">Coupon:</div>
        <div class="col-sm-auto ms-0 me-2 p-0">
` + this.#couponSelect + `
        </div>
        <div class="col-sm-auto ms-0 me-0 p-0">
            <button class="btn btn-secondary btn-sm" type="button" id="pay-btn-coupon" onclick="pos.applyCoupon('a');">Apply Coupon</button>
        </div>  
    </div>
`;
                    } else {
                        // now display the amount due
                        pay_html += `
    <div class="row mt-1">
        <div class="col-sm-2 ms-0 me-2 p-0">Coupon:</div>
        <div class="col-sm-auto ms-0 me-2 p-0">` + coupon.getNameString() + `</div>
         <div class="col-sm-auto ms-0 me-0 p-0">
            <button class="btn btn-secondary btn-sm" type="button" id="pay-btn-coupon" onclick="pos.applyCoupon('r');">Remove Coupon</button>
        </div>  
    </div>
    <div class="row mt-1">
        <div class="col-sm-1 ms-0 me-0">&nbsp;</div>
        <div class="col-sm-11 ms-0 me-0 p-0">` + coupon.couponDetails() + `</div>
    </div>
`;
                    }
                }
            }
            // add prior discounts to screen if any

            pay_html += `
    <div class="row mt-1">
        <div class="col-sm-2 ms-0 me-2 p-0">Order Total:</div>
        <div class="col-sm-auto m-0 p-0 ms-0 me-2 p-0" id="pay-pre-tax-amt">$` + Number(this.#preTaxAmt).toFixed(2) + `</div>
    </div>
`;
            if (this.#managerDiscount > 0) {
                pay_html += `
    <div class="row mt-2">
        <div class="col-sm-2 ms-0 me-2 p-0">Discount:</div>
        <div class="col-sm-auto m-0 p-0 ms-0 me-2 p-0" id="pay-prior-disc">$` + Number(this.#managerDiscount).toFixed(2) + `</div>
    </div>
`;
            }
            if (this.#couponDiscount > 0) {
                var pretax = Number(this.#preTaxAmt) - Number(this.#couponDiscount);
                pay_html += `
    <div class="row mt-1">
        <div class="col-sm-2 ms-0 me-2 p-0">Coupon:</div>
        <div class="col-sm-auto m-0 p-0 ms-0 me-2 p-0" id="pay-coupon-disc">$` + (-Number(this.#couponDiscount)).toFixed(2) + `</div>
    </div>
       <div class="row mt-1">
        <div class="col-sm-2 ms-0 me-2 p-0">Pre Tax:</div>
        <div class="col-sm-auto m-0 p-0 ms-0 me-2 p-0" id="pay-post-coupon">$` + Number(pretax).toFixed(2) + `</div>
    </div>
`;
            }
            pay_html += `
    <div class="row mt-1">
        <div class="col-sm-2 ms-0 me-2 p-0">` + this.#taxLabel + `:</div>
        <div class="col-sm-auto m-0 p-0 ms-0 me-2 p-0" id="pay-tax-amt">$` + Number(this.#taxAmt).toFixed(2) + `</div>
    </div>
    <div class="row mt-1">
        <div class="col-sm-2 ms-0 me-2 p-0">Amount Due:</div>
        <div class="col-sm-auto m-0 p-0 ms-0 me-2 p-0" id="pay-amt-due">$` + Number(total_amount_due).toFixed(2) + `</div>
    </div>
    <div class="row">
        <div class="col-sm-2 m-0 mt-2 me-2 mb-2 p-0">Payment Type:</div>
        <div class="col-sm-auto m-0 mt-2 p-0 ms-0 me-2 mb-2 p-0" id="pt-div">
`;
            if (this.#ccTerminalAvailable) {
                pay_html += `
            <input type="radio" id="pt-terminal" name="payment_type" value="terminal" onchange='pos.setPayType("terminal");'/>
            <label for="pt-terminal">Credit Card Terminal&nbsp;&nbsp;&nbsp;</label>
`;
            } else if (!config.hasOwnProperty('creditonline') || config.creditonline == 1) {
                pay_html += `
            <input type="radio" id="pt-online" name="payment_type" value="credit" onchange='pos.setPayType("online");'/>
            <label for="pt-online">Online Credit Card&nbsp;&nbsp;&nbsp;</label>
`;
            }
            if (!config.hasOwnProperty('creditoffline') || config.creditoffline == 1) {
                pay_html += `
            <input type="radio" id="pt-credit" name="payment_type" value="credit" onchange='pos.setPayType("credit");'/>
            <label for="pt-credit">Offline Credit Card&nbsp;&nbsp;&nbsp;</label>
`;
            }

            pay_html += `
            <input type="radio" id="pt-check" name="payment_type" value="check" onchange='pos.setPayType("check");'/>
            <label for="pt-check">Check&nbsp;&nbsp;&nbsp;</label>
            <input type="radio" id="pt-cash" name="payment_type" value="cash" onchange='pos.setPayType("cash");'/>
            <label for="pt-cash">Cash&nbsp;&nbsp;&nbsp;</label>
`;
            if (this.#discount_mode != "none" && !coupon.isCouponActive() && this.#drow == null) {
                if (this.#discount_mode == 'any' || ((this.#discount_mode == 'manager' || this.#discount_mode == 'active') &&
                    this.#manager && baseManagerEnabled)) {
                    pay_html += `
            <input type="radio" id="pt-discount" name="payment_type" value="discount" onchange='pos.setPayType("discount");'/>
            <label for="pt-discount">Discount</label>
`;
                }
            }
            pay_html += `
        </div>
    </div>
    <div class="row mb-2" id="pay-check-div" hidden>
        <div class="col-sm-2 ms-0 me-2 p-0">Check Number:</div>
        <div class="col-sm-auto m-0 p-0 ms-0 me-2 p-0"><input type="text" size="8" maxlength="10" name="pay-checkno" id="pay-checkno"/></div>
    </div>
    <div class="row mb-2" id="pay-cash-div" hidden>
        <div class="col-sm-2 ms-0 me-2 p-0">Amt Tendered:</div>
        <div class="col-sm-auto m-0 p-0 ms-0 me-2 p-0"><input type="number" class="no-spinners" id="pay-tendered" name="paid-tendered" size="6"/></div>
    </div>
       <div class="row mb-2" id="pay-disc-div" hidden>
        <div class="col-sm-2 ms-0 me-2 p-0">Disc Amount</div>
        <div class="col-sm-auto m-0 p-0 ms-0 me-2 p-0"><input type="number" class="no-spinners" id="pay-discount" name="paid-discount" size="6"/></div>
    </div>
    <div class="row mb-2" id="pay-ccauth-div" hidden>
        <div class="col-sm-2 ms-0 me-2 p-0">CC Auth Code:</div>
        <div class="col-sm-auto m-0 p-0 ms-0 me-2 p-0"><input type="text" size="15" maxlength="16" name="pay-ccauth" id="pay-ccauth"/></div>
    </div>    
    <div class="row mb-2" id="pay-online-div" hidden>
        <div class="col-sm-12 ms-0 me-0 p-0">` + this.#cc_html + `</div>  
    </div>    
    <div class="row mb-2">
        <div class="col-sm-2 ms-0 me-2 p-0">Description:</div>
        <div class="col-sm-auto m-0 p-0 ms-0 me-2 p-0"><input type="text" size="60" maxlength="48" name="pay-desc" id="pay-desc"/></div>
    </div>
     <div class="row mb-2">
        <div class="col-sm-2 ms-0 me-2 p-0">Select Payor:</div>
        <div class="col-sm-auto m-0 p-0 ms-0 me-2 p-0">
            <select name="pay-emailsel" id="pay-emailsel" onchange="pos.selectPayor();">
                <option value=-1>Other: Enter payor below</option>
`;
            cartlen = cart.getCartLength();
            rownum = 0;
            while (rownum < cartlen) {
                var email_addr = cart.getEmail(rownum);
                if (validateAddress(email_addr)) {
                    pay_html += "<option value='" + rownum.toString() + "'" + (rownum == 0 ? ' selected' : '') + '>' + cart.getFullName(rownum) + "</option>\n";
                }
                rownum++;
            }
            pay_html += `
            </select>         
        </div>
    </div>
    <div class="row mb-2">
        <div class="col-sm-2 ms-0 me-2 p-0">Payor Email:</div>
        <div class="col-sm-auto m-0 p-0 ms-0 me-2 p-0"><input type="text" size="60" maxlength="254" name="pay-email" id="pay-email" value="` +
            cart.getEmail(0) + `" placeholder="Enter payor's email address" /></div>
    </div>
    <div class="row">
        <div class="col-sm-2 ms-0 me-2 p-0">Payor Phone:</div>
        <div class="col-sm-auto m-0 p-0 ms-0 me-2 p-0"><input type="text" size="30" maxlength="24" name="pay-phone" id="pay-phone" value="` +
                cart.getPhone(0) + `" placeholder="Enter payor's phone number"/></div>
    </div>
    <div class="row mt-3">
        <div class="col-sm-2 ms-0 me-2 p-0">&nbsp;</div>
        <div class="col-sm-auto ms-0 me-2 p-0">
            <button class="btn btn-primary btn-sm" type="button" id="pay-btn-pay" onclick="pos.pay('');">Confirm Pay</button>
        </div>
    </div>
    <div class="row mt-3" id="overrideRow" hidden>
        <div class="col-sm-auto ms-0 me-2 p-0">
            <button class="btn btn-warning btn-sm" type="button" id="pay-btn-override" onclick="pos.overridePay();">Override</button>
        </div>
        <div class="col-sm-10 ms-0 me-2 p-0" id="override_msg">
            <p>The terminal is marked as not available, override the status to take control and use it anyway?</p>
            <p>This will cancel any payment in process on the terminal.</p>
        </div>
    </div>
     <div class="row mt-3" id="pollRow" hidden>
        <div class="col-sm-auto ms-0 me-2 p-0">
            <button class="btn btn-primary btn-sm" type="button" id="pay-poll-complete" onclick="pos.payPoll(1);">Payment Complete</button>
        </div>
        <div class="col-sm-auto ms-0 me-2 p-0">
            <button class="btn btn-primary btn-sm" type="button" id="pay-poll-cancel" onclick="pos.payPoll(0);">Cancel Payment</button>
        </div>
    </div>
  </form>
</div>
    <div id="receeiptEmailAddresses" class="container-fluid"></div>
    <div class="row mt-3">
        <div class="col-sm-auto ms-0 me-2 p-0">
            <button class="btn btn-primary btn-sm" type="button" id="pay-btn-ercpt" onclick="pos.emailReceipt('email');" hidden disabled>Email Receipt</button>
        </div>
        <div class="col-sm-auto ms-0 me-2 p-0">
            <button class="btn btn-primary btn-sm" type="button" id="pay-btn-rcpt" onclick="pos.emailReceipt('print');" hidden disabled>Print Receipt</button>
        </div>
    </div>
    <div class="row mt-4">
        <div class="col-sm-12 p-0" id="pay_status"></div>
    </div>
</div>
`;

            this.#pay_div.innerHTML = pay_html;
            this.#pay_button_pay = document.getElementById('pay-btn-pay');
            this.#pay_button_ercpt = document.getElementById('pay-btn-ercpt');
            this.#pay_button_rcpt = document.getElementById('pay-btn-rcpt');
            this.#pay_button_rcpt.disabled = !this.#receiptPrinterAvailable;
            this.#receeiptEmailAddresses_div = document.getElementById('receeiptEmailAddresses');
            if (this.#receeiptEmailAddresses_div)
                this.#receeiptEmailAddresses_div.innerHTML = '';
        }
        cart.showStartOver();
    }

// process online credit card payment
    makePurchase(token, label) {
        if (label != '') {
            this.#purchase_label = label;
        }
        if (token == 'test_ccnum') {  // this is the test form
            token = document.getElementById(token).value;
        }

        $('#' + this.#purchase_label).attr("disabled", "disabled");
        this.pay('', null, token);
    }

// printint
    printShown() {
        cart.clearInReview();
        this.#find_tab.disabled = true;
        this.#add_tab.disabled = true;
        this.#review_tab.disabled = true;
        cart.hideStartOver();
        cart.showNext();
        cart.freeze();
        this.#current_tab = this.#print_tab;
        this.newPrint = false;
        this.#printActive = true;

        if (this.#printedObj == null) {
            this.#newPrint = true;
            this.#printedObj = new map();
        }
        cart.drawCart();

        // draw the print screen
        var print_html = `<div id='printBody' class="container-fluid form-floating">
`;
        if (this.#badgePrinterAvailable === false) {
            print_html += 'No printer selected, unable to print badges.  </div>';
            this.#printDiv.innerHTML = print_html;
            return;
        }
        this.#badgeList = [];
        print_html += cart.printList(this.#newPrint, this.#printedObj);
        print_html += `
    <div class="row mt-4">
        <div class="col-sm-2 ms-0 me-2 p-0">&nbsp;</div>
        <div class="col-sm-auto ms-0 me-2 p-0">
            <button class="btn btn-primary btn-sm" type="button" id="pay-print-all" name="print_btn" onclick="pos.printBadge(-1, -1);">Print All</button>
        </div>
    </div>
    <div class="row mt-4">
        <div class="col-sm-12 m-0 mt-4 p-0" id="pt-status"></div>
    </div>
</div>`;

        this.#printDiv.innerHTML = print_html;
    }

// addToBadgeList - add to badge Print List array
    addToBadgeList(cindex, mindex) {
        this.#badgeList.push([cindex, mindex]);
    }

// dayFromLabel(label)
// return the full day name from a memList/memLabel label.
    dayFromLabel(label) {
        var pattern_fa = /^mon\s.*$/i;
        var pattern_ff = /^monday.*$/i;
        var pattern_ma = /.*\s+mon\s.*$/i;
        var pattern_mf = /.*\s+monday.*$/i;
        if (pattern_fa.test(label) || pattern_ff.test(label) || pattern_ma.test(label) || pattern_mf.test(label))
            return "Monday;"

        pattern_fa = /^tue\s.*$/i;
        pattern_ff = /^tueday.*$/i;
        pattern_ma = /.*\s+tue\s.*$/i;
        pattern_mf = /.*\s+tueday.*$/i;
        if (pattern_fa.test(label) || pattern_ff.test(label) || pattern_ma.test(label) || pattern_mf.test(label))
            return "Tuesday;"


        pattern_fa = /^wed\s.*$/i;
        pattern_ff = /^wednesday.*$/i;
        pattern_ma = /.*\s+wed\s.*$/i;
        pattern_mf = /.*\s+wednesday.*$/i;
        if (pattern_fa.test(label) || pattern_ff.test(label) || pattern_ma.test(label) || pattern_mf.test(label))
            return "Wednesday;"

        pattern_fa = /^thu\s.*$/i;
        var pattern_faa = /^thur\s.*$/i;
        pattern_ff = /^thursday.*$/i;
        pattern_ma = /.*\s+thu\s.*$/i;
        var pattern_maa = /.*\s+thur\s.*$/i;
        pattern_mf = /.*\s+thursday.*$/i;
        if (pattern_fa.test(label) || pattern_faa.test(label) || pattern_ff.test(label) ||
            pattern_ma.test(label) || pattern_maa.test(label) || pattern_mf.test(label))
            return "Thursday;"

        pattern_fa = /^fri\s.*$/i;
        pattern_ff = /^friday.*$/i;
        pattern_ma = /.*\s+fri\s.*$/i;
        pattern_mf = /.*\s+friday.*$/i;
        if (pattern_fa.test(label) || pattern_ff.test(label) || pattern_ma.test(label) || pattern_mf.test(label))
            return "Friday;"

        pattern_fa = /^sat\s.*$/i;
        pattern_ff = /^saturday.*$/i;
        pattern_ma = /.*\s+sat\s.*$/i;
        pattern_mf = /.*\s+saturday.*$/i;
        if (pattern_fa.test(label) || pattern_ff.test(label) || pattern_ma.test(label) || pattern_mf.test(label))
            return "Saturday;"

        pattern_fa = /^sun\s.*$/i;
        pattern_ff = /^sunday.*$/i;
        pattern_ma = /.*\s+sun\s.*$/i;
        pattern_mf = /.*\s+sunday.*$/i;
        if (pattern_fa.test(label) || pattern_ff.test(label) || pattern_ma.test(label) || pattern_mf.test(label))
            return "Sunday;"

        return "";
    }

// every functions
    notPerid(current, index, perinfo) {
        var memberships = current.memberships;

        if (memberships) {

            for (var i = 0; i < memberships.length; i++) {
                if (memberships[i].perid != this.#checkPerid)
                    return false;
            }
        }

        return true;
    }

    // combined exit change check
    confirmExit(event) {
        // if they have a terminal action in process, as if they want to leave install of 'poll' for it's status
        if (this.#payPoll == 1) {
            event.preventDefault(); // if the browser lets us set our own variable
            if (!confirm("You are leaving without polling the terminal for payment completion.\n" +
                'Please use the "Payment Complete" button to check if the payment is complete,\n' +
                'or the "Cancel Payment" buttons to cancel the payment request and release the terminal.\n' +
                "Do you wish to leave anyway and release the terminal?")) {
                return false;
            }
            delete e.returnValue;
            var currentOrder = this.#pay_currentOrderId;
            var user_id = this.#user_id;
            this.#pay_currentOrderId = null;
            // cancel terminal request
            var postData = {
                ajax_request_action: 'cancelPayRequest',
                requestId: this.#payCurrentRequest,
                user_id: this.#user_id,
            };
            var _this = this;
            clear_message();
            $.ajax({
                method: "POST",
                url: "scripts/pos_cancelPayment.php",
                data: postData,
                success: function (data, textstatus, jqxhr) {
                    if (typeof data == 'string') {
                        show_message(data, 'error');
                        return;
                    }

                    if (data.error !== undefined) {
                        show_message(data.error, 'error');
                        return;
                    }

                    if (data.status == 'error') {
                        show_message(data.data, 'error');
                        return;
                    }

                    if (data.warn !== undefined) {
                        show_message(data.warn, 'warn');
                    }

                    if (data.message !== undefined) {
                        show_message(data.message, 'success');
                    }
                    if (currentOrder && currentOrder != '') {
                        var postData = {
                            ajax_request_action: 'cancelOrder',
                            orderId: currentOrder,
                            user_id: user_id,
                        };
                        $.ajax({
                            method: "POST",
                            url: "scripts/pos_cancelOrder.php",
                            data: postData,
                            success: function (data, textstatus, jqxhr) {
                                if (data.error !== undefined) {
                                    show_message(data.error, 'error');
                                    return;
                                }
                                if (data.warn !== undefined) {
                                    show_message(data.warn, 'warn');
                                    return;
                                }
                                if (data.message !== undefined) {
                                    show_message(data.message, 'success');
                                }
                            },
                            error: function (jqXHR, textstatus, errorThrown) {
                                $("button[name='find_btn']").attr("disabled", false);
                                showAjaxError(jqXHR, textstatus, errorThrown);
                            }
                        });
                    }
                },
                error: function (jqXHR, textstatus, errorThrown) {
                    document.getElementById('pollRow').hidden = false;
                    _this.#pay_button_pay.disabled = true;
                    showAjaxError(jqXHR, textstatus, errorThrown);
                },
            });
            this.#payPoll = 0;
            return true;
        }
        if (this.#pay_currentOrderId && this.#pay_currentOrderId != '') {
            event.preventDefault(); // if the browser lets us set our own variable
            if (!confirm("You are leaving with paying for an outstanding order.\n" +
                "Do you wish to leave anyway and cancel the order?")) {
                return false;
            }
            delete e.returnValue;
            var postData = {
                ajax_request_action: 'cancelOrder',
                orderId: this.#pay_currentOrderId,
                user_id: this.#user_id,
            };
            $.ajax({
                method: "POST",
                url: "scripts/pos_cancelOrder.php",
                data: postData,
                success: function (data, textstatus, jqxhr) {
                    if (data.error !== undefined) {
                        show_message(data.error, 'error');
                        return;
                    }
                    if (data.warn !== undefined) {
                        show_message(data.warn, 'warn');
                        return;
                    }
                    if (data.message !== undefined) {
                        show_message(data.message, 'success');
                    }
                },
                error: function (jqXHR, textstatus, errorThrown) {
                    $("button[name='find_btn']").attr("disabled", false);
                    showAjaxError(jqXHR, textstatus, errorThrown);
                }
            });
            this.#pay_currentOrderId = null;
        }

        if (!this.confirmDiscardAddEdit(true)) {
            e.preventDefault();
            e.returnValue = "You have unsaved member changes, leave anyway";
            return;
        }

        if (!cart.confirmDiscardCartEntry(-1, true)) {
            e.preventDefault();
            e.returnValue = "You have unsaved cart changes, leave anyway";
            return;
        }

        delete e.returnValue;
        return true;
    }
}

// functions for tabulator icons
function posPerNotesIcons(cell, formatterParams, onRendered) {
    return pos.perNotesIcons(cell, formatterParams, onRendered);
}

function posbuildRecordHover(e, cell, onRendered) {
    return pos.buildRecordHover(e, cell, onRendered);
}

function checkboxCheck() {
    return pos.checkboxCheck();
}

function reviewNoChanges() {
    return pos.reviewNoChanges();
}