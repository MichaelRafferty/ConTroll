<?php
// Online Reg - index.php - Main page for online con registration
require_once("lib/base.php");
require_once('../lib/global.php');
require_once("../lib/cc__load_methods.php");
require_once("../lib/profile.php");
require_once("../lib/policies.php");
require_once("../lib/interests.php");
require_once("../lib/coupon.php");

$cc = get_conf('cc');
$con = get_conf('con');
$reg_conf = get_conf('reg');
$usps = get_conf('usps');
load_cc_procs();

$condata = get_con();
$urlCouponCode = '';
$urlSerialNum = '';
$serialHidden = 'hidden';
$class = '';

$useUSPS = false;
if (($usps != null) && array_key_exists('secret', $usps) && ($usps['secret'] != ''))
    $useUSPS = true;

$config_vars = array();
$config_vars['label'] = $con['label'];
$config_vars['required'] = $reg_conf['required'];

$numCoupons = num_coupons();
if ($numCoupons == 0)
    $costCols = 4;
else {
    $costCols = 2;

    // only process offer on the command line if there is a valid set of coupons
    if (array_key_exists('offer', $_GET) && $_GET['offer']) {
        $offer_code = $_GET['offer'];
        $offer_code = base64_decode_url($offer_code);
        if ($offer_code) {
            $urlCouponCode = strtok($offer_code, '~!~');
            $urlSerialNum = strtok('~!~');
            if ($urlSerialNum) {
                $serialHidden = '';
            }
        }
    }
}
$policies = getPolicies();
$interests = getInterests();
$membershiptypes = array();
$priceQ = <<<EOS
SELECT id, label, shortname, sort_order, price, memAge, memCategory
FROM memLabel
WHERE
    conid=?
    AND online = 'Y'
    AND startdate <= current_timestamp()
    AND enddate > current_timestamp()
ORDER BY sort_order, price DESC;
EOS;
$priceR = dbSafeQuery($priceQ, "i", array($condata['id']));
while($priceL = $priceR->fetch_assoc()) {
    $membershiptypes[] = $priceL;
}
$js = "var mtypes = " . json_encode($membershiptypes) . PHP_EOL .
    "var numCoupons = " . $numCoupons . ";" . PHP_EOL .
    "var policies = " . json_encode($policies) . PHP_EOL .
    "var interests = " . json_encode($interests) . PHP_EOL .
    "var config = " . json_encode($config_vars) . PHP_EOL;
$startdate = new DateTime($condata['startdate']);
$enddate = new DateTime($condata['enddate']);
$daterange = $startdate->format("F j-") . $enddate->format("j, Y");
$ageByDate = $startdate->format("F j, Y");
$altstring = $con['org'] . '. ' . $condata['label'] . ' . ' . $daterange;
$onsitesale = $startdate->format("l, F j");

// overall header HTML and main body
  ol_page_init($condata['label'] . ' Online Registration', $js);
?>
<body class="regPaybody">
    <div class="container-fluid">
        <?php if (array_key_exists('logoimage', $reg_conf) && $reg_conf['logoimage'] != '') {
                  if (array_key_exists('logoalt', $reg_conf)) {
                      $altstring=$reg_conf['logoalt'];
                  }
         ?>
        <img class="img-fluid" src="images/<?php echo $reg_conf['logoimage']; ?>" alt="<?php echo escape_quotes($altstring); ?>"/>
        <?php }
               if(array_key_exists('logotext', $reg_conf) && $reg_conf['logotext'] != '') { ?>
        <div style='display:inline-block' class='display-1'><?php echo $reg_conf['logotext']; ?></div>
        <?php } ?>
    </div>
    <h1> Welcome to the <?php echo $condata['label']; ?> Online Registration Page</h1>
<?php
  if($reg_conf['test']==1) {
    ?>
    <h2 class='text-danger'><strong>This Page is for test purposes only</strong></h2>
    <?php
  }
  if($reg_conf['open']==1 and $reg_conf['close']==0 and $reg_conf['suspended']==0) {
    ?>

     <!--- aother membership modal popup -->
     <div class="modal" id="anotherBadge" tabindex="-2" aria-labelledby="Add Another Membership" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <div class="modal-title">
                        Add another Membership?
                    </div>
                </div>
                <div class="modal-body">
                     <p class="text-body">Membership added for <span id='oldBadgeName'></span>.<br/>Add Another Membership?</p>
                </div>
                <div class="modal-footer">
                    <button class='btn btn-sm btn-primary' type="button" onclick="togglePopup();">Add Another</button>
                    <button class='btn btn-sm btn-primary' type="button" onclick="anotherBadgeModalClose();">Review and Pay</button>
                </div>
            </div>
        </div>
    </div>
      <?php if ($numCoupons > 0) { ?>
      <!--- add coupon modal popup -->
      <div class='modal modal-lg' id='addCoupon' tabindex='-2' aria-labelledby='Add Coupon' aria-hidden='true'>
          <div class='modal-dialog'>
              <div class='modal-content'>
                  <div class='modal-header'>
                      <div class='modal-title' id="couponHeader">
                          Add Coupon to Order
                      </div>
                  </div>
                  <div class='modal-body'>
                      <div class="row mb-1">
                          <div class="col-sm-auto p-0 ms-4 me-2">
                              <label for="couponCode">Coupon Code:</label>
                          </div>
                          <div class="col-sm-auto p-0">
                              <input type="text" size="16" maxlength="16" id="couponCode" name="couponCode" value="<?php echo escape_quotes($urlCouponCode); ?>"/>
                          </div>
                      </div>
                      <div class='row mt-1 mb-1' id="serialDiv" <?php echo $serialHidden; ?>>
                          <div class='col-sm-auto p-0 ms-4 me-2'>
                              <label for='couponSerial'>Serial Number:</label>
                          </div>
                          <div class='col-sm-auto p-0'>
                              <input type='text' size='36' maxlength='36' id='couponSerial' name='couponSerial' value="<?php echo escape_quotes($urlSerialNum); ?>"/>
                          </div>
                      </div>
                      <div class="row">
                          <div class="col-sm-12" id="couponMsgDiv"></div>
                      </div>
                  </div>
                  <div class='modal-footer'>
                      <button class='btn btn-sm btn-warning' type='button' onclick='removeCouponCode();' id="removeCouponBTN" hidden>Remove Coupon</button>
                      <button class='btn btn-sm btn-primary' type='button' onclick='addCouponCode();' id="addCouponBTN">Add Coupon</button>
                      <button class='btn btn-sm btn-secondary' type='button' onclick='couponModalClose();'>Cancel</button>
                  </div>
              </div>
          </div>
      </div>
          <?php } ?>
    <!--- New Badge Modal Popup -->
    <div class="modal modal-xl fade" id="newBadge" tabindex="-1" aria-labelledby="New Membership" aria-hidden="true" style='--bs-modal-width: 90%;'>
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header" style="background-color: #b0c4de;">
                    <div class="modal-title">
                        <strong>New Membership</strong>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" style="padding: 4px;">
                     <div id='newBadgeBody' class="container-fluid form-floating" style="background:#f4f4ff;">
                        <h3 class="text-primary">New Convention Memberships</h3>
                        <form id='newBadgeForm' action='javascript:void(0);' class="form-floating">
<?php
    drawEditPersonBlock($con, $useUSPS, $policies, $class, /* modal */ true,
        /* editEmail */ true, $ageByDate, $membershiptypes, /* tabIndexStart  */ 100);
    if (count($interests) > 0) {
?>
        <div class='row'>
            <div class='col-sm-12'>
                <hr/>
            </div>
        </div>
<?php
        drawInterestList($interests);
    }
?>                            <div class="row mt-4">
                                <div class="col-sm-12">
                                    <button type="button" id="addToCartBtn" class="btn btn-sm btn-primary me-1"
                                            onclick="process('#newBadgeForm');" tabindex="980">Add Membership To Cart</button>
                                    <button type="button" class="btn btn-sm btn-primary ms-1 me-1"
                                            onclick='newBadgeModalClose();' tabindex="985">Review and Pay</button>
                                    <button type="reset" class="btn btn-sm btn-secondary ms-1"
                                        tabindex="990">Reset</button>
                                </div>
                            </div>
                        </form>
                         <div class="row">
                             <div class="col-sm-12" id="addMessageDiv"></div>
                         </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!--- Main body of the page -->

     <div class="container-fluid form-floating">
         <div class="row">
             <div class="col-sm-6 p-2 border border-2 border-primary">
                 <form action='javascript:void(0)' id='purchaseForm'>
                     <div class="container-fluid form-floating">
                         <div class="row">
                             <div class="col-sm-12">
                                   <h3 class="text-primary">Summary</h3>
                             </div>
                         </div>
                         <div class="row">
                             <div class="col-sm-12 ms-0 me-0 p-0">
                                 <hr style="height:4px; color:#0d6efd;background-color:#0d6efd;border-width:0;"/>
                             </div>
                         </div>
                         <div class="row">
                             <div class="col-sm-12" id="memSummaryDiv"></div>
                         </div>
                         <div class="row">
                             <div class="col-sm-12 ms-0 me-0 p-0">
                                 <hr style="height:4px; color:#0d6efd;background-color:#0d6efd;border-width:0;"/>
                             </div>
                         </div>
                         <div id='couponDiv' hidden>
                             <div class='row'>
                                 <div class='col-sm-12 ms-0 me-0 p-0' id='couponDetailDiv'></div>
                             </div>
                             <div class='row'>
                                 <div class='col-sm-12 ms-0 me-0 p-0'>
                                     <button class='btn btn-sm btn-secondary' onclick='couponModalOpen();' id='changeCouponBTN'>Change/Remove Coupon</button>
                                 </div>
                             </div>
                             <div class='row mt-4'>
                                 <div class='col-sm-4 ms-0 me-0 p-0'>
                                     Subtotal before coupon:
                                 </div>
                                 <div class="col-sm-auto ms-0 me-0 p-0" id='subTotalColDiv'></div>
                             </div>
                             <div class='row'>
                                 <div class='col-sm-4 ms-0 me-0 p-0'>
                                     Coupon Discount:
                                 </div>
                                 <div class='col-sm-auto ms-0 me-0 p-0' id='couponDiscountDiv'></div>
                             </div>
                             <div class='row'>
                                 <div class='col-sm-12 ms-0 me-0 p-0'>
                                     <hr style='height:4px; color:#0d6efd;background-color:#0d6efd;border-width:0;'/>
                                 </div>
                             </div>
                         </div>
                         <div class="row">
                             <div class="col-sm-4 ms-0 me-0 p-0">
                                 Total Cost:
                             </div>
                             <div class="col-sm-<?php echo $costCols; ?>" id="totalCostDiv"></div>
                             <?php if ($numCoupons > 0) { ?>
                             <div class='col-sm-auto ms-auto me-2 p-0' id="addCouponDiv">
                                 <button class="btn btn-sm btn-secondary" onclick='couponModalOpen();' id="couponBTN">Add Coupon</button>
                             </div>
                             <?php } ?>
                             <div class="col-sm-auto ms-0 me-2 p-0">
                                  <button class='btn btn-sm btn-primary' onclick='newBadgeModalOpen();'>Add Memberships</button>
                             </div>
                         </div>
                         <div class="row">
                             <div class="col-sm-12 ms-0 me-0 p-0">
                                 <hr style="height:4px; color:#0d6efd;background-color:#0d6efd;border-width:0;"/>
                             </div>
                         </div>
                         <div class="row">
                             <div class="col-sm-9 mb-4">
                                 <label>Choose who's paying for the order:</label><br/>
                                 <select id='personList' onchange='updateAddr()'></select>
                             </div>
                             <div class="col-sm-auto ms-auto me-2 p-0">
                                 <button class='btn btn-sm btn-secondary' onclick='toggleAddr()'>Edit Who<br/>is Paying</button>
                             </div>
                         </div>
                         <div class="row">
                             <div class="col-sm-2 ms-0 me-0 p-0">
                                 <label for="cc_fname">
                                     Name:
                                 </label>
                             </div>
                             <div class="col-sm-auto ms-0 me-1 p-0">
                                 <input type='text' name='fname' class='ccdata' id='cc_fname' required='required' placeholder='First Name'/>
                             </div>
                             <div class="col-sm-auto ms-0 me-0 p-0">
                                 <input type='text' name='lname' id='cc_lname' required='required' class='ccdata' placeholder='Last Name'/>
                             </div>
                         </div>
                         <div class="row">
                             <div class="col-sm-2 ms-0 me-0 p-0">
                                 <label for="cc_street">
                                     Street:
                                 </label>
                             </div>
                             <div class="col-sm-auto ms-0 me-0 p-0">
                                 <input type='text' size=40 class='ccdata' id='cc_street' required='required' name='street'/>
                             </div>
                         </div>
                         <div class="row">
                             <div class="col-sm-2 ms-0 me-0 p-0">
                                 <label for="cc_city">City:</label>
                             </div>
                             <div class="col-sm-auto ms-0 me-2 p-0">
                                 <input type='text' id='cc_city' required='required' size="20" class='ccdata' name='city'/>
                             </div>
                             <div class="col-sm-auto ms-0 me-1 p-0">
                                 <label for="cc_state">State:</label>
                             </div>
                             <div class="col-sm-auto ms-0 me-2 p-0">
                                 <input type='text' id='cc_state' size="10" maxlength="16" required='required' class='ccdata' name='state/'>
                             </div>
                             <div class="col-sm-auto ms-0 me-1 p-0">
                                 <label for="cc_zip">Zip:</label>
                             </div>
                             <div class="col-sm-auto ms-0 me-0 p-0">
                                 <input type='text' id='cc_zip' required='required' size=10 class='ccdata' name='zip/'>
                             </div>
                         </div>
                         <div class="row">
                             <div class="col-sm-2 ms-0 me-0 p-0">
                                 <label for="cc_country">Country:</label>
                             </div>
                             <div class="col-sm-auto ms-0 me-0 p-0">
                                  <select id='cc_country' class='ccdata' required='required' name='country' size=1>
                                      <?php
                                      $fh = fopen(__DIR__ . '/../lib/countryCodes.csv', 'r');
                                      while(($data = fgetcsv($fh, 1000, ',', '"'))!=false) {
                                          echo '<option value="' . escape_quotes($data[1]) . '">' . $data[0] . "</option>";
                                      }
                                      fclose($fh);
                                      ?>
                                  </select>
                             </div>
                         </div>
                         <div class="row">
                             <div class="col-sm-2 ms-0 me-0 p-0">
                                 <label for="cc_email">Email:</label>
                             </div>
                             <div class="col-sm-auto ms-0 me-0 p-0">
                                  <input type='email' id='cc_email' class='ccdata' name='cc_email'/>
                             </div>
                         </div>
                         <div class="row">
                             <div class="col-sm-12 ms-0 me-0 p-0">
                                 <hr style="height:4px; color:#0d6efd;background-color:#0d6efd;border-width:0;"/>
                             </div>
                         </div>
                         <div class="row" id="emptyCart">
                             <div class="col-sm-12 ms-0 me-0 p-0">
                                 Your cart does not contain any primary memberships.  Please add memberships to the cart before checking out.
                             </div>
                         </div>
                         <div class='row' id='noChargeCart' hidden>
                             <div class='col-sm-12 ms-0 me'-0 p-0'>
                                 No payment is required on your cart. Click "Purchase" to check out now or add more items to the cart using "Add Memberships".<br/>
                                 <button id='ncpurchase' class='btn btn-sm btn-primary' onclick="makePurchase('no-charge', 'purchase')">Purchase</button>&nbsp;
                                 <button class='btn btn-sm btn-primary' onclick='newBadgeModalOpen();'>Add Memberships</button>
                             </div>
                         </div>
                         <div class="row" id='chargeCart' hidden>
                             <div class="col-sm-12 ms-0 me-0 p-0">
                                   <?php echo draw_cc_html($cc); ?>
                             </div>
                         </div>
                         <div class="row">
                             <div class="col-sm-12 ms-0 me-0 p-0">
                                 <hr style="height:4px; color:#0d6efd;background-color:#0d6efd;border-width:0;"/>
                             </div>
                         </div>
                     </div>
                 </form>
                 <p class="text-body">We Accept</p>
                 <img src='cards_accepted_64.png' alt="Visa, Mastercard, American Express, and Discover"/><br/>
<?php
      if($reg_conf['test']==1) {
?>
                 <h2 class='text-danger'><strong>This won't charge your credit card.<br/>It also won't get you memberships.</strong></h2>
    <?php
      }
    ?>
                 <p class="text-body"><?php echo $con['conname']; ?> memberships are not refundable, except in case of emergency.
                 For details and questions about transfers and rollovers to future conventions, please see
                 <a href="<?php echo escape_quotes($con['regpolicy']); ?>">The Registration Policies Page.</a></p>
             </div>
             <div class="col-sm-6 p-2 border border-2 border-primary">
                 <div class="container-fluid">
                     <div class="row">
                         <div class="col-sm-12">
                              <h3 class="text-primary">Memberships</h3>
                         </div>
                     </div>
                     <div class="row">
                         <div class="col-sm-12 ms-0 me-0 p-0">
                             <hr style="height:4px; color:#0d6efd;background-color:#0d6efd;border-width:0;"/>
                         </div>
                     </div>
                     <div class="row">
                         <div class="col-sm-12" id="badge_list"></div>
                     </div>
                </div>
             </div>
         </div>
     </div>
<?php } else if($reg_conf['cancelled']==1) { ?>
<p class='text-primary'>
<?php echo $condata['label']; ?> has been canceled.  If you had previously purchased a membership you should have received an email with instructions. Please go to our <a href='cancelation.php'>Membership Cancelation Page</a> to tell us how you'd like your membership handled.
</p>
<?php } else if($reg_conf['suspended']==1) { ?>
<p class="text-primary">
<?php echo $con['conname']; ?> has temporarily suspended online registration <?php echo $reg_conf['suspendreason']; ?>
</p>
<?php } else if($reg_conf['close']==1) { ?>
<p class="text-primary">Preregistration for <?php echo $condata['label']; ?> is now closed.
Memberships will be available for purchase starting <?php echo $onsitesale; ?> by <?php echo $reg_conf['onsiteopen'] . ' ' . $con['pickupareatext']; ?>
<a href="<?php echo escape_quotes($con['hotelwebsite']); ?>"> <?php echo $con['hotelname']; ?></a>.
Daily rates are posted on <a href="<?php echo escape_quotes($con['dailywebsite']); ?>">The <?php echo $con['conname']; ?> website</a></p>
<p class="text-body"><?php echo $con['addlpickuptext']; ?></p>

<p class="text-body">We look forward to seeing you at <?php echo $con['conname']; ?>.</p>
<?php } else { ?>
<p class="text-primary">Online registration for <?php echo $condata['id']; ?> is not yet open. We aim to have online registration open 6 months before the convention.

We will post a notice when online registration opens on the
<a href="<?php echo escape_quotes($reg_conf['registrationpage']); ?>">The <?php echo $con['conname']; ?> Registration Page</a>.  Mail-in forms are also available on that page.</p>

<?php } ?>
    <div class='container-fluid'>
        <div class="row mt-2">
            <div class="col-sm-6">
                <p class='text-body'><a href="<?php echo escape_quotes($con['policy']); ?>" target='_blank'>Click here for
                        the <?php echo $con['policytext']; ?></a>.<br/>
                    For more information about <?php echo $con['conname']; ?> please email <a
                            href="mailto:<?php echo escape_quotes($con['infoemail']); ?>"><?php echo $con['infoemail']; ?></a>.<br/>
                    For questions about <?php echo $con['conname']; ?> Registration, email <a
                            href="mailto:<?php echo escape_quotes($con['regemail']); ?>"><?php echo $con['regemail']; ?></a>.</p>
            </div>
            <?php drawBug(6); ?>
        </div>
    </div>
</body>
</html>
