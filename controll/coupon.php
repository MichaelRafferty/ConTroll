<?php
global $db_ini;

require_once "lib/base.php";
//initialize google session
$need_login = google_init("page");

$page = "finance";
if(!$need_login or !checkAuth($need_login['sub'], $page)) {
    bounce_page("index.php");
}

$cdn = getTabulatorIncludes();
page_init("Coupon",
    /* css */ array($cdn['tabcss'], $cdn['tabbs5'],
                    'css/base.css',
                    ),
    /* js  */ array(//$cdn['luxon'],
                    $cdn['tabjs'],
                    'js/coupon.js'),
                    $need_login);

$con = get_con();
$conid = $con['id'];
// load membership types for limit to membership type
$limitSQL = <<<EOS
SELECT id, label, price, enddate
FROM memLabel
WHERE conid = ? AND price > 0 AND memCategory not in ('freebie', 'rollover', 'cancel', 'addon', 'add-on')
ORDER BY label, enddate, price
EOS;
$limitR = dbSafeQuery($limitSQL, 'i', array($conid));
$optionList = "<option value=''>Not Limited</option>\n";
while ($row = $limitR->fetch_assoc()) {
    $optionList .= '<option value="' . $row['id'] . '">' . $row['label'] . ' (' . $row['price'] . ': ' . $row['enddate'] . ")</option>\n";
}

// first the modal for editing/adding a coupon
?>
<div id='edit_coupon' class='modal modal-xl fade' tabindex='-1' aria-labelledby='Add/Edit Coupon' aria-hidden='true' style='--bs-modal-width: 80%;'>
    <div class='modal-dialog'>
        <div class='modal-content'>
            <div class='modal-header bg-primary text-bg-primary'>
                <div class='modal-title' id='edit-coupon-title'>
                    <strong>Edit Coupon</strong>
                </div>
                <button type='button' class='btn-close' data-bs-dismiss='modal' aria-label='Close'></button>
            </div>
            <div class='modal-body' style='padding: 4px; background-color: lightcyan;'>
                <div class='container-fluid form-floating'>
                    <div class='row mb-1'>
                        <div class='col-sm-12' id='edit_coupon_preform'></div>
                    </div>
                    <form id='coupon_form' class='form-floating' action='javascript:void(0);'>
                        <input type='hidden' name='couponId' id='form_couponId'/>
                        <div class='row mb-1'>
                            <div class='col-sm-4'>
                                <label for='form_code'>Code*:</label>
                            </div>
                            <div class='col-sm-auto p-0'>
                                <input class='form-control-sm' type='text' name='code' id='form_code' size='16' maxlength="16" required/>
                            </div>
                        </div>
                        <div class='row mb-1'>
                            <div class='col-sm-4'>
                                <label for='form_name'>Descriptive Name*:</label>
                            </div>
                            <div class='col-sm-auto p-0'>
                                <input class='form-control-sm' type='text' name='name' id='form_name' size='32' maxlength="32" required/>
                            </div>
                        </div>
                        <div class='row mb-1'>
                            <div class='col-sm-4'>
                                <label for='form_couponType'>One Use Coupon:*</label>
                            </div>
                            <div class='col-sm-auto p-0'>
                                <select name='oneUse' id='form_oneUse'>
                                    <option value="0">No</option>
                                    <option value="1">Yes</option>
                                </select>
                            </div>
                        </div>
                        <div class='row mb-1'>
                            <div class='col-sm-4'>
                                <label for='form_startDate'>Optional Starting Date:</label>
                            </div>
                            <div class='col-sm-auto p-0'>
                                <input class='form-control-sm' type='datetime-local' name='startDate' id='form_startDate'/>
                            </div>
                        </div>
                        <div class='row mb-1'>
                            <div class='col-sm-4'>
                                <label for='form_endDate'>Optional Ending Date:</label>
                            </div>
                            <div class='col-sm-auto p-0'>
                                <input class='form-control-sm' type='datetime-local' name='endDate' id='form_endDate'/>
                            </div>
                        </div>
                        <div class='row mb-1'>
                            <div class='col-sm-4'>
                                <label for='form_couponType'>Coupon Type:*</label>
                            </div>
                            <div class='col-sm-auto p-0'>
                                <select name="couponType" id="form_couponType">
                                    <option value="$off">Fixed dollars off Cart</option>
                                    <option value="%off">Fixed percentage off Primary Memberships in Cart</option>
                                    <option value="$mem">Fixed dollars off Primary Memberships</option>
                                    <option value="%mem">Fixed percentage off Primary Memberships</option>
                                    <option value="price">Set specific membership type to a fixed price</option>
                                </select>
                            </div>
                        </div>
                        <div class='row mb-1'>
                            <div class='col-sm-4'>
                                <label for='form_discount'>Discount ($ or %)*:</label>
                            </div>
                            <div class='col-sm-auto p-0'>
                                <input class='form-control-sm' name='discount' id='form_discount' type='number' required/>
                            </div>
                        </div>
                        <div class='row mb-1'>
                            <div class='col-sm-4'>
                                <label for='form_memId'>Limit to membership type: </label>
                            </div>
                            <div class='col-sm-8 p-0'>
                                <select class='form-control-sm' id='form_memId' name='memId'>
                                    <?php echo $optionList; ?>
                                </select>
                            </div>
                        </div>
                        <div class='row mb-1'>
                            <div class='col-sm-4'>
                                <label for='form_minMemberships'>Minimum Primary Memberships:</label>
                            </div>
                            <div class="col-sm-auto p-0">
                                <input class='form-control-sm' type='number' name='minMemberships' id='form_minMemberships' />
                            </div>
                        </div>
                        <div class='row mb-1'>
                            <div class='col-sm-4'>
                                <label for='form_maxMemberships'>Apply to at most (Max) Memberships:</label>
                            </div>
                            <div class='col-sm-auto p-0'>
                                <input class='form-control-sm' type='number' name='maxMemberships' id='form_maxMemberships' />
                            </div>
                        </div>
                        <div class='row mb-1'>
                            <div class='col-sm-4'>
                                <label for='form_limitMemberships'>Limit number of this Primary Membership:</label>
                            </div>
                            <div class='col-sm-auto p-0'>
                                <input class='form-control-sm' type='number' name='limitMemberships' id='form_limitMemberships' />
                            </div>
                        </div>
                        <div class='row mb-1'>
                            <div class='col-sm-4'>
                                <label for='form_minTransaction'>Require minimum cart of $:</label>
                            </div>
                            <div class='col-sm-auto p-0'>
                                <input class='form-control-sm' type='number' name='minTransaction' id='form_minTransaction' />
                            </div>
                        </div>
                        <div class='row mb-1'>
                            <div class='col-sm-4'>
                                <label for='form_maxTransaction'>Discount maximum cart of $:</label>
                            </div>
                            <div class='col-sm-auto p-0'>
                                <input class='form-control-sm' type='number' name='maxTransaction' id='form_maxTransaction' />
                            </div>
                        </div>
                        <div class='row mb-1'>
                            <div class='col-sm-4'>
                                <label for='form_maxRedemption'>Maximum number of redemptions:</label>
                            </div>
                            <div class='col-sm-auto p-0'>
                                <input class='form-control-sm' type='number' name='maxRedemption' id='form_maxRedemption' />
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-sm-4"></div>
                            <div class="col-sm-auto p-0">
                                <button id="form_submit" type="button" class="btn btn-primary btn-sm" onclick="coupons.UpdateCoupon();">Update Coupon</button>
                                <button id="form_delete" type="button" class="btn btn-warning btn-sm" onclick="coupons.DeleteCoupon();">Delete Unused Coupon</button>
                                <button id="form_cancel" type="button" class="btn btn-secondary btn-sm" onClick="coupons.HideEditModal();">Cancel</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="container-fluid">
    <div class="row">
        <div class="col-sm-auto p-0 m-0 me-4">
            <h4>Coupons:</h4>
        </div>
        <div class="col-sm-auto p-0 m-0 me-4">
            <button id='coupon-addrow' type='button' class='btn btn-secondary btn-sm' onclick='coupons.AddNew();'>Add New</button>
        </div>
        <div class="col-sm-auto p-0 m-0 ms-4">
            Click on "ID", "#Used" or "#Keys" cells to display additional details.
        </div>
    </div>
    <div class="row">
        <div class="col-sm-auto p-0 m-0" id="couponTable"></div>
    </div>
    <div class='row mt-2'>
        <div class='col-sm-auto p-0 m-0' id="detailTable"></div>
    </div>
    <div id='result_message' class='mt-4 p-2'></div>
</div>
<pre id='test'>
</pre>
<?php

page_foot($page);
?>
