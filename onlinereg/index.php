<?php
// Online Reg - index.php - Main page for online con registration
require_once("lib/base.php");
require_once('../lib/global.php');
require_once("../lib/cc__load_methods.php");
require_once("../lib/coupon.php");

$cc = get_conf('cc');
$con = get_conf('con');
$ini = get_conf('reg');
$usps = get_conf('usps');
load_cc_procs();

$condata = get_con();
$urlCouponCode = '';
$urlSerialNum = '';
$serialHidden = 'hidden';

$useUSPS = false;
if (($usps != null) && array_key_exists('secret', $usps) && ($usps['secret'] != ''))
    $useUSPS = true;

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
$membershiptypes = array();
$priceQ = <<<EOS
SELECT id, memGroup, label, shortname, sort_order, price, memAge, memCategory
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
$js = "var mtypes = " . json_encode($membershiptypes);
$startdate = new DateTime($condata['startdate']);
$enddate = new DateTime($condata['enddate']);
$daterange = $startdate->format("F j-") . $enddate->format("j, Y");
$agebydate = $startdate->format("F j, Y");
$altstring = $con['org'] . '. ' . $condata['label'] . ' . ' . $daterange;
$onsitesale = $startdate->format("l, F j");

// overall header HTML and main body
  ol_page_init($condata['label'] . ' Online Registration', $js);
?>
<body class="regPaybody">
    <div class="container-fluid">
        <?php if (array_key_exists('logoimage', $ini) && $ini['logoimage'] != '') {
                  if (array_key_exists('logoalt', $ini)) {
                      $altstring=$ini['logoalt'];
                  }
         ?>
        <img class="img-fluid" src="images/<?php echo $ini['logoimage']; ?>" alt="<?php echo escape_quotes($altstring); ?>"/>
        <?php }
               if(array_key_exists('logotext', $ini) && $ini['logotext'] != '') { ?>
        <div style='display:inline-block' class='display-1'><?php echo $ini['logotext']; ?></div>
        <?php } ?>
    </div>
    <h1> Welcome to the <?php echo $condata['label']; ?> Online Registration Page</h1>
<?php
  if($ini['test']==1) {
    ?>
    <h2 class='text-danger'><strong>This Page is for test purposes only</strong></h2>
    <?php
  }
  if($ini['open']==1 and $ini['close']==0 and $ini['suspended']==0) {
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
                            <div class="row" style="width:100%;">
                                <div class="col-sm-12">
                                    <p class="text-body">Note: Please provide your legal name that will match a valid form of ID. Your legal name will not be publicly visible.  If you don't provide one, it will default to your First, Middle, Last Names and Suffix.</p>
                                    <p class="text-body">Items marked with <span class="text-danger">&bigstar;</span> are required fields.</p>
                                </div>
                            </div>
                            <?php if ($useUSPS) echo '<div class="row"><div class="col-sm-8"><div class="container-fluid">' . PHP_EOL; ?>
                                <div class="row">
                                    <div class="col-sm-auto ms-0 me-2 p-0">
                                        <label for="fname" class="form-label-sm"><span class="text-dark" style="font-size: 10pt;"><span class='text-danger'>&bigstar;</span>First Name</span></label><br/>
                                        <input class="form-control-sm" type="text" name="fname" id='fname' size="22" maxlength="32" tabindex="2"/>
                                    </div>
                                    <div class="col-sm-auto ms-0 me-2 p-0">
                                        <label for="mname" class="form-label-sm"><span class="text-dark" style="font-size: 10pt;">Middle Name</span></label><br/>
                                        <input class="form-control-sm" type="text" name="mname" id='mname' size="8" maxlength="32" tabindex="4"/>
                                    </div>
                                    <div class="col-sm-auto ms-0 me-2 p-0">
                                        <label for="lname" class="form-label-sm"><span class="text-dark" style="font-size: 10pt;"><span class='text-danger'>&bigstar;</span>Last Name</span></label><br/>
                                        <input class="form-control-sm" type="text" name="lname" id='lname' size="22" maxlength="32" tabindex="6"/>
                                    </div>
                                    <div class="col-sm-auto ms-0 me-0 p-0">
                                        <label for="suffix" class="form-label-sm"><span class="text-dark" style="font-size: 10pt;">Suffix</span></label><br/>
                                        <input class="form-control-sm" type="text" name="suffix" id='suffix' size="4" maxlength="4" tabindex="8"/>
                                    </div>
                                </div>
                                <div class='row'>
                                    <div class='col-sm-12 ms-0 me-0 p-0'>
                                        <label for='legalname' class='form-label-sm'><span class='text-dark' style='font-size: 10pt;'>Legal Name: for checking against your ID. It will only be visible to Registration Staff.</label><br/>
                                        <input class='form-control-sm' type='text' name='legalname' id='legalname' size=64 maxlength='64' placeholder='Defaults to First Name Middle Name Last Name, Suffix' tabindex='10'/>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-sm-12 ms-0 me-0 p-0">
                                        <label for="addr" class="form-label-sm"><span class="text-dark" style="font-size: 10pt;"><span class='text-danger'>&bigstar;</span>Address</span></label><br/>
                                        <input class="form-control-sm" type="text" name='addr' id='addr' size=64 maxlength="64" tabindex='12'/>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-sm-12 ms-0 me-0 p-0">
                                        <label for="addr2" class="form-label-sm"><span class="text-dark" style="font-size: 10pt;">Company/2nd Address line</span></label><br/>
                                        <input class="form-control-sm" type="text" name='addr2' id='addr2' size=64 maxlength="64" tabindex='14'/>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-sm-auto ms-0 me-2 p-0">
                                        <label for="city" class="form-label-sm"><span class="text-dark" style="font-size: 10pt;"><span class='text-danger'>&bigstar;</span>City</span></label><br/>
                                        <input class="form-control-sm" type="text" name="city" id='city' size="22" maxlength="32" tabindex="16"/>
                                    </div>
                                    <div class="col-sm-auto ms-0 me-2 p-0">
                                        <label for="state" class="form-label-sm"><span class="text-dark" style="font-size: 10pt;"><span class='text-danger'>&bigstar;</span>State: US/CAN 2 letter abv.</span></label><br/>
                                        <input class="form-control-sm" type="text" name="state" id='state' size="16" maxlength="16" tabindex="18"/>
                                    </div>
                                    <div class="col-sm-auto ms-0 me-2 p-0">
                                        <label for="zip" class="form-label-sm"><span class="text-dark" style="font-size: 10pt;"><span class='text-danger'>&bigstar;</span>Zip</span></label><br/>
                                        <input class="form-control-sm" type="text" name="zip" id='zip' size="5" maxlength="10" tabindex="20"/>
                                    </div>
                                    <div class="col-sm-auto ms-0 me-0 p-0">
                                        <label for="country" class="form-label-sm"><span class="text-dark" style="font-size: 10pt;">Country</span></label><br/>
                                        <select name='country' tabindex='22' id='country' onchange="countryChange();">
                                    <?php
                      $fh = fopen(__DIR__ . '/../lib/countryCodes.csv', 'r');
                      while(($data = fgetcsv($fh, 1000, ',', '"'))!=false) {
                          echo '<option value="' . escape_quotes($data[1]) . '">' .$data[0]. '</option>';
                      }
                      fclose($fh);
                                    ?>
                                        </select>
                                    </div>
                                </div>
                                <?php if ($useUSPS) echo '</div></div><div class="col-sm-4" id="uspsblock"></div></div>' . PHP_EOL; ?>
                            <div class="row">
                                <div class="col-sm-12">
                                    <hr/>
                                </div>
                            </div>
                            <div class="row">
                                <div col="col-sm-12">
                                    <p class="text-body">Contact Information
                                     (<a href="<?php echo escape_quotes($con['privacypolicy']);?>" target='_blank'><?php echo $con['privacytext'];?></a>).</p>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-sm-auto ms-0 me-2 p-0">
                                    <label for="email1" class="form-label-sm"><span class="text-dark" style="font-size: 10pt;"><span class='text-danger'>&bigstar;</span>Email</span></label><br/>
                                    <input class="form-control-sm" type="email" name="email1" id='email1' size="35" maxlength="254" tabindex="24"/>
                                </div>
                                <div class="col-sm-auto ms-0 me-0 p-0">
                                    <label for="email2" class="form-label-sm"><span class="text-dark" style="font-size: 10pt;"><span class='text-danger'>&bigstar;</span>Confirm Email</span></label><br/>
                                    <input class="form-control-sm" type="email" name="email2" id='email2' size="35" maxlength="254" tabindex="26"/>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-sm-6 ms-0 me-0 p-0">
                                    <label for="phone" class="form-label-sm"><span class="text-dark" style="font-size: 10pt;">Phone</span></label><br/>
                                    <input class="form-control-sm" type="text" name="phone" id='phone' size="20" maxlength="15" tabindex="28"/>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-sm-12">
                                    <hr/>
                                </div>
                            </div>
                             <div class="row">
                                <div class="col-sm-12">
                                    <p class="text-body">
                                Select membership type from the drop-down menu below.
                                Eligibility for Child and Young Adult rates are based on age on <?php echo $agebydate; ?>
                                (the first day of the convention).</p>
                                </div>
                             </div>
                            <div class="row">
                                <div class="col-sm-auto ms-0 me-2 p-0">
                                    <label for="badgename" class="form-label-sm"><span class="text-dark" style="font-size: 10pt;">Badge Name (optional)</span></label><br/>
                                    <input class="form-control-sm" type="text" name="badgename" id='badgename' size="35" maxlength="32"  placeholder='defaults to first and last name' tabindex="30"/>
                                </div>
                                <div class="col-sm-auto ms-0 me-0 p-0">
                                    <label for="memType" class="form-label-sm"><span class="text-dark" style="font-size: 10pt;"><span class='text-danger'>&bigstar;</span>Membership Type</span></label><br/>
                                    <select id='memType' name='memType' style="width:500px;" tabindex='32' title='Age is as of <?php echo substr($condata['startdate'], 0, 10); ?> (the first day of the convention)'>
                                        <?php foreach ($membershiptypes as $memType) { ?>
                                            <option value='<?php echo $memType['id'];?>'><?php echo $memType['label']; ?> ($<?php echo $memType['price'];?>)</option>
                                        <?php    } ?>
                                    </select>
                                </div>
                            </div>
                            <div class='row'>
                                <div class='col-sm-12'>
                                    <hr/>
                                </div>
                            </div>
                            <div class='row'>
                                <div class='col-sm-12'>
                                    <p class='text-body'>
                                        <a href="<?php echo escape_quotes($con['policy']); ?>" target='_blank'>Click here for
                                            the <?php echo $con['policytext']; ?></a>.
                                    </p>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-sm-12">
                                    <p class="text-body"><?php echo $con['conname']; ?> is entirely run by volunteers.
                                    If you're interested in helping us run the convention please email
                                    <a href="mailto:<?php echo escape_quotes($con['volunteers']); ?>"><?php echo $con['volunteers']; ?></a>.
                                    </p>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-sm-12">
                                    <p class="text-body">
                                        <label>
                                            <input type='checkbox' checked name='contact' id='contact' value='Y'/>
                                            <?php echo $con['remindertext']; ?>
                                        </label>
                                        <span class='small'><a href='javascript:void(0)' onClick='$("#contactTip").toggle()'>(more info)</a></span>
                                        <div id='contactTip' class='padded highlight' style='display:none'>
                                            <p class="text-body">
                                                We will not sell your contact information or use it for any purpose other than contacting you about this
                                                <?php echo $con['conname']; ?> or future <?php echo $con['conname']; ?>s.
                                                <span class='small'><a href='javascript:void(0)' onClick='$("#contactTip").toggle()'>(close)</a></span>
                                            </p>
                                        </div>
                                    </p>
                                </div>
                            </div>
                             <div class="row">
                                <div class="col-sm-12">
                                    <p class="text-body">
                                        <label>
                                            <input type='checkbox' checked name='share' id='share' value='Y'/>
                                            May we include you in our <a target='_blank' href='checkReg.php'>Check Registration page</a>?
                                            This will allow you to check the status of your registration at your convenience.
                                            If you choose to opt out, you can only check the status of your registration status by contacting
                                            one of our volunteer staff at <?php echo $con['regemail']; ?> (please allow several days for a reply).
                                        </label>
                                    </p>
                                </div>
                             </div>
                            <div class="row">
                                <div class="col-sm-12">
                                    <hr/>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-sm-12">
                                    <button type="button" id="addToCartBtn" class="btn btn-sm btn-primary me-1" onclick="process('#newBadgeForm');">Add Membership To Cart</button>
                                    <button type="button" class="btn btn-sm btn-primary ms-1 me-1" onclick='newBadgeModalClose();'>Review and Pay</button>
                                    <button type="reset" class="btn btn-sm btn-secondary ms-1">Reset</button>
                                </div>
                            </div>
                        </form>
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
      if($ini['test']==1) {
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
<?php } else if($ini['cancelled']==1) { ?>
<p class='text-primary'>
<?php echo $condata['label']; ?> has been canceled.  If you had previously purchased a membership you should have received an email with instructions. Please go to our <a href='cancelation.php'>Membership Cancelation Page</a> to tell us how you'd like your membership handled.
</p>
<?php } else if($ini['suspended']==1) { ?>
<p class="text-primary">
<?php echo $con['conname']; ?> has temporarily suspended online registration <?php echo $ini['suspendreason']; ?>
</p>
<?php } else if($ini['close']==1) { ?>
<p class="text-primary">Preregistration for <?php echo $condata['label']; ?> is now closed.
Memberships will be available for purchase starting <?php echo $onsitesale; ?> by <?php echo $ini['onsiteopen'] . ' ' . $con['pickupareatext']; ?>
<a href="<?php echo escape_quotes($con['hotelwebsite']); ?>"> <?php echo $con['hotelname']; ?></a>.
Daily rates are posted on <a href="<?php echo escape_quotes($con['dailywebsite']); ?>">The <?php echo $con['conname']; ?> website</a></p>
<p class="text-body"><?php echo $con['addlpickuptext']; ?></p>

<p class="text-body">We look forward to seeing you at <?php echo $con['conname']; ?>.</p>
<?php } else { ?>
<p class="text-primary">Online registration for <?php echo $condata['id']; ?> is not yet open. We aim to have online registration open 6 months before the convention.

We will post a notice when online registration opens on the
<a href="<?php echo escape_quotes($ini['registrationpage']); ?>">The <?php echo $con['conname']; ?> Registration Page</a>.  Mail-in forms are also available on that page.</p>

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
