<?php 
require_once("lib/base.php");
$ini = redirect_https();

$cc = get_conf('cc');
switch ($cc['type']) {
      case 'convergepay':
          require_once("lib/convergepay.php");
          break;
      case 'square':
          require_once("lib/square.php");
          break;
      default:
          echo "No valid credit card processor defined\n";
          exit();
}
$condata = get_con();
$con = get_conf('con');

$prices = array();
$priceQ = "SELECT memAge, price from memList where conid='". $condata['id'] .
"' and memCategory='standard' and startdate < current_timestamp() and enddate >= current_timestamp() and memType='full';";
$priceQ = <<<EOS
SELECT memAge, price
FROM memList 
WHERE 
    conid=? 
    AND memCategory='standard' 
    AND startdate < current_timestamp() 
    AND enddate >= current_timestamp() 
    AND memType='full'
;
EOS;
$priceR = dbSafeQuery($priceQ, "i", array($condata['id']));
while($priceL = fetch_safe_assoc($priceR)) { 
    $prices[$priceL['memAge']] = $priceL['price'];
}

$startdate = new DateTime($condata['startdate']);
$enddate = new DateTime($condata['enddate']);
$daterange = $startdate->format("F j-") . $enddate->format("j, Y");
$agebydate = $startdate->format("F j, Y");
$altstring = $con['org'] . '. ' . $condata['label'] . ' . ' . $daterange;
$onsitesale = $startdate->format("l, F j");

// overall header HTML and main body
  ol_page_init($condata['label'] . ' Online Registration');
?>
 <body class="regPaybody">
    <div class="container-fluid">
        <img class="img-fluid" src="images/<?php echo $ini['logoimage']; ?>" alt="<?php echo $altstring ;?>"/>
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
     
    <div class="container" id='anotherBadge' title='Add another Badge?'>
        <p class="text-body">        Badge added for <span id='oldBadgeName'></span>.<br/>
        Add another Badge?</p>
    </div>


    <div id='newBadge' class="container-fluid" style="background:#f4f4ff;display:none;">
        <h3 class="text-primary">New Convention Badges</h3>
        <form id='newBadgeForm' action='javascript:void(0);'>
            <p class="text-body">Please provide your legal name that will match a form of identification you can present at registration and a current address.</p>
            <div class="row" style="width:100%;">
                <div class="col-4 ms-0 me-0 p-0">
                    <label for="fname" class="form-label-sm"><span class="text-dark" style="font-size: 10pt;"><span class='text-info'>*</span>First Name</span></label>
                </div>
                <div class="col-2 ms-0 me-0 p-0">
                    <label for="mname" class="form-label-sm"><span class="text-dark" style="font-size: 10pt;">Middle Name</span></label>
                </div>
                <div class="col-4 ms-0 me-0 p-0">
                    <label for="lname" class="form-label-sm"><span class="text-dark" style="font-size: 10pt;"><span class='text-info'>*</span>Last Name</span></label>
                </div>
                <div class="col-2 ms-0 me-0 p-0">
                    <label for="suffix" class="form-label-sm"><span class="text-dark" style="font-size: 10pt;">Suffix</span></label>
                </div>
            </div>
            <div class="row">
                <div class="col-4 ms-0 me-0 p-0">
                    <input class="form-control-sm" type="text" name="fname" id='fname' size="22" maxlength="32" tabindex="1"/>
                </div>
                <div class="col-2 ms-0 me-0 p-0">
                    <input class="form-control-sm" type="text" name="mname" id='mname' size="8" maxlength="32" tabindex="2"/>
                </div>
                <div class="col-4 ms-0 me-0 p-0">
                    <input class="form-control-sm" type="text" name="lname" id='lname' size="22" maxlength="32" tabindex="3"/>
                </div>
                <div class="col-2 ms-0 me-0 p-0">
                    <input class="form-control-sm" type="text" name="suffix" id='suffix' size="4" maxlength="4" tabindex="4"/>
                </div>
            </div>
            <div class="row">
                <div class="col-12 ms-0 me-0 p-0">
                    <label for="addr" class="form-label-sm"><span class="text-dark" style="font-size: 10pt;"><span class='text-info'>*</span>Address</span></label>
                </div>
            </div>
            <div class="row">
                <div class="col-12 ms-0 me-0 p-0">
                    <input class="form-control-sm" type="text" name='addr' id='addr' size=64 maxlength="64" tabindex='5'/>
                </div>
            </div>
            <div class="row">
                <div class="col-12 ms-0 me-0 p-0">
                    <label for="addr2" class="form-label-sm"><span class="text-dark" style="font-size: 10pt;">Company/2nd Address line</span></label>
                </div>
            </div>
            <div class="row">
                <div class="col-12 ms-0 me-0 p-0">
                    <input class="form-control-sm" type="text" name='addr2' id='addr2' size=64 maxlength="64" tabindex='6'/>
                </div>
            </div>
            <div class="row">
                <div class="col-4 ms-0 me-0 p-0">
                    <label for="city" class="form-label-sm"><span class="text-dark" style="font-size: 10pt;"><span class='text-info'>*</span>City</span></label>
                </div>
                <div class="col-2 ms-0 me-0 p-0">
                    <label for="state" class="form-label-sm"><span class="text-dark" style="font-size: 10pt;"><span class='text-info'>*</span>State</span></label>
                </div>
                <div class="col-2 ms-0 me-0 p-0">
                    <label for="zip" class="form-label-sm"><span class="text-dark" style="font-size: 10pt;"><span class='text-info'>*</span>Zip</span></label>
                </div>
                <div class="col-4 ms-0 me-0 p-0">
                    <label for="suffix" class="form-label-sm"><span class="text-dark" style="font-size: 10pt;">Country</span></label>
                </div>
            </div>
            <div class="row">
                <div class="col-4 ms-0 me-0 p-0">
                    <input class="form-control-sm" type="text" name="city" id='city' size="22" maxlength="32" tabindex="7"/>
                </div>
                <div class="col-2 ms-0 me-0 p-0">
                    <input class="form-control-sm" type="text" name="state" id='state' size="2" maxlength="2" tabindex="8"/>
                </div>
                <div class="col-2 ms-0 me-0 p-0">
                    <input class="form-control-sm" type="text" name="zip" id='lname' size="5" maxlength="10" tabindex="9"/>
                </div>
                <div class="col-4 ms-0 me-0 p-0">
                    <select name='country' tabindex='10'>
                        <option value="USA" default='true'>United States</option>
                        <option value="CAN">Canada</option>
                    <?php
    $fh = fopen("countryCodes.csv","r");
    while(($data = fgetcsv($fh, 1000, ',', '"'))!=false) {
        echo "<option value='".$data[1]."'>".$data[0]."</option>";
    }
    fclose($fh);
                    ?>
                    </select>
                </div>
            </div>
            <hr/>
            <p class="text-body">Please provide a way for us to contact you if there are questions about your registration.  We will never share your information without your consent.</p>
                <div class="row">
                    <div class="col-6 ms-0 me-0 p-0">
                        <label for="email1" class="form-label-sm"><span class="text-dark" style="font-size: 10pt;"><span class='text-info'>*</span>Email</span></label>
                    </div>
                    <div class="col-6 ms-0 me-0 p-0">
                        <label for="email2" class="form-label-sm"><span class="text-dark" style="font-size: 10pt;"><span class='text-info'>*</span>Confirm Email</span></label>
                    </div>
                </div>
                <div class="row">
                    <div class="col-6 ms-0 me-0 p-0">
                        <input class="form-control-sm" type="email" name="email1" id='email1' size="35" maxlength="64" tabindex="11"/>
                    </div>
                    <div class="col-6 ms-0 me-0 p-0">
                        <input class="form-control-sm" type="email" name="email2" id='email2' size="35" maxlength="64" tabindex="12"/>
                    </div>
                </div>
                <div class="row">
                    <div class="col-6 ms-0 me-0 p-0">
                        <label for="phone" class="form-label-sm"><span class="text-dark" style="font-size: 10pt;">Phone</span></label>
                    </div>
                </div>
                <div class="row">
                    <div class="col-6 ms-0 me-0 p-0">
                        <input class="form-control-sm" type="text" name="phone" id='phone' size="20" maxlength="15" tabindex="13"/>
                    </div>
                </div>
                <hr/>
                <p class="text-body"> Please provide information about your membership and badge.
                <br/>
                Select membership type from the drop-down menu below.
                Eligibiilty for Child and Young Adult rates are based on age on <?php echo $agebydate; ?>
                (the first day of the convention).</p>
                <div class="row">
                    <div class="col-6 ms-0 me-0 p-0">
                        <label for="badgename" class="form-label-sm"><span class="text-dark" style="font-size: 10pt;">Badge Name</span></label>
                    </div>
                    <div class="col-6 ms-0 me-0 p-0">
                        <label for="memType" class="form-label-sm"><span class="text-dark" style="font-size: 10pt;"><span class='text-info'>*</span>Membership Type</span></label>
                    </div>
                </div>
                <div class="row">
                    <div class="col-6 ms-0 me-0 p-0">
                        <input class="form-control-sm" type="text" name="badgename" id='badgename' size="35" maxlength="32"  placeholder='defaults to first and last name' tabindex="14"/>
                    </div>
                    <div class="col-6 ms-0 me-0 p-0"> 
                        <select id='memType' name='age' style="width:300px;" tabindex='15' title='Age as of <?php echo substr($condata['startdate'], 0, 10); ?> (the first day of the convention)'>
                            <?php if (array_key_exists('adult', $prices)) { ?><option value='adult'>Adult [21+ years old] ($<?php echo $prices['adult'];?>)</option> <?php }; ?>
                            <?php if (array_key_exists('military', $prices)) { ?><option value='military'>Active Duty Military ($<?php echo $prices['military'];?>)</option> <?php }; ?>
                            <?php if (array_key_exists('youth', $prices)) { ?><option value='youth'>Young Adult or Child [13 to 20 years old] ($<?php echo $prices['youth'];?>)</option><?php }; ?>
                            <?php if (array_key_exists('child', $prices)) { ?><option value='child'>Child [6 to 12 years old] ($<?php echo $prices['child'];?>)</option><?php }; ?>
                            <?php if (array_key_exists('kit', $prices)) { ?><option value='kit'>Kid in Tow [under 6 years old] ($<?php echo $prices['kit'];?>)</option><?php }; ?>
                        </select>
                    </div>
                </div> 
                <p class="text-body"><?php echo $con['conname']; ?> is entirely run by volunteers. 
                If you're interested in helping us run the convention please email 
                <a href='mailto:<?php echo $con['volunteers']; ?>'><?php echo $con['volunteers']; ?></a>.
                </p>
                <hr/>
                <p class="text-body">
                    <a href="<?php echo $con['policy'];?>" target="_blank">Click here for the <?php echo $con['policytext']; ?></a>.   
                </p>
                <input type='submit' onclick='process($("#newBadgeForm"));' value='Add Badge To Cart'/>
                <input type='submit' onclick='$("#newBadge").dialog("close");' value='Cancel'/>
                <input type='reset'/>
                <hr/>
                <p class="text-body">
                    <label>
                        <input type='checkbox' checked name='contact' id='contact' value='Y'/> 
                        May we include you in our annual reminder postcards and future marketing or survey emails?
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
                <p class="text-body">
                    <label>
                        <input type='checkbox' checked name='share' id='share' value='Y'/>
                        May we include you in our <a target='_blank' href='checkReg.php'>online search of members</a>? 
                        To support members checking their registration, we allow a search for a name through our list of members.
                        This provides city/state of residence as well as a partial name. 
                        If you choose to opt out, you can only check the status of your registration status manually by contacting
                        one of our volunteer staff at <?php echo $con['regemail']; ?> (please allow several days for a reply).
                    </label>
                </p>
                <hr/>
                <input type='submit' onclick='process($("#newBadgeForm"));' value='Add Badge To Cart'/>
                <input type='submit' onclick='$("#newBadge").dialog("close");' value='Review and Pay'/>
                <input type='reset'/>
            </form>
        </div>
     <div class="container-fluid">
         <div class="row">
             <div class="col-6 p-2 border border-2 border-primary">
                 <h3 class="text-primary">Summary</h3>
                 <hr style="height:4px; color:#0d6efd;background-color:#0d6efd;border-width:0;"/>
                 <?php if (array_key_exists('adult', $prices)) { ?>Adult Badges <span id='adults'>0</span> x $<?php echo $prices['adult']; ?><br/><?php }; ?>
                 <?php if (array_key_exists('military', $prices)) { ?>Military Badges <span id='militarys'>0</span> x $<?php echo $prices['military']; ?><br/><?php }; ?>
                 <?php if (array_key_exists('youth', $prices)) { ?>YA Badges <span id='youths'>0</span> x $<?php echo $prices['youth']; ?><br/><?php }; ?>
                 <?php if (array_key_exists('child', $prices)) { ?>Child Badges <span id='childs'>0</span> x $<?php echo $prices['child']; ?><br/><?php }; ?>
                 <?php if (array_key_exists('kit', $prices)) { ?>Kids in Tow <span id='kits'>0</span> x $<?php echo $prices['kit']; ?><br/><?php }; ?>
                 <hr style="height:4px; color:#0d6efd;background-color:#0d6efd;border-width:0;"/>
                 Total Cost: $<span id='total'>0</span><br/>
                 <button onclick='$("#newBadge").dialog("open");'>Add Badges</button>
                 <hr style="height:4px; color:#0d6efd;background-color:#0d6efd;border-width:0;"/>
                 <label>Choose who's paying for the order:<br/>
                 <select id='personList' onchange='updateAddr()'>
                 </select></label>
                 <button onclick='toggleAddr()'>Edit</button>
                 <form action='javascript:void(0)' id='purchaseForm'>
                     <div class="container-fluid">
                         <div class="row">
                             <div class="col-2 ms-0 me-0 p-0">
                                 <label for="cc_fname">
                                     Name:
                                 </label>
                             </div>
                             <div class="col-10 ms-0 me-0 p-0">
                                 <input type='text' name='fname' class='ccdata' id='cc_fname' required='required' placeholder='First Name'/>
                                 <input type='text' name='lname' id='cc_lname' required='required' class='ccdata' placeholder='Last Name'/>
                             </div>
                         </div>
                         <div class="row">
                             <div class="col-2 ms-0 me-0 p-0">
                                 <label for="cc_street">
                                     Street:
                                 </label>
                             </div>
                             <div class="col-10 ms-0 me-0 p-0">
                                 <input type='text' size=40 class='ccdata' id='cc_street' required='required' name='street'/>
                             </div>
                         </div>
                         <div class="row">
                             <div class="col-2 ms-0 me-0 p-0">
                                 <label for="cc_city">City:</label>
                             </div>
                             <div class="col-10 ms-0 me-0 p-0">
                                 <input type='text' id='cc_city' required='required' size="20" class='ccdata' name='city'/>
                                 <label for="cc_state">State:</label>
                                 <input type='text' id='cc_state' size=2 required='required' class='ccdata' name='state/'>
                                 <label for="cc_zip">Zip:</label>
                                 <input type='text' id='cc_zip' required='required' size=10 class='ccdata' name='zip/'>
                             </div>
                         </div>
                         <div class="row">
                             <div class="col-2 ms-0 me-0 p-0">
                                 <label for="cc_country">Country:</label>
                             </div>
                             <div class="col-10 ms-0 me-0 p-0">
                                  <select id='cc_country' class='ccdata' required='required' name='country' size=1>
                                      <?php                                      
                                      $fh = fopen("countryCodes.csv","r");
                                      while(($data = fgetcsv($fh, 1000, ',', '"'))!=false) {
                                          echo "<option value='".$data[1]."'>".$data[0]."</option>";
                                      }
                                      fclose($fh);
                                      ?>
                                  </select>
                             </div>
                         </div>
                         <div class="row">
                             <div class="col-2 ms-0 me-0 p-0">
                                 <label for="cc_email">Email:</label>
                             </div>
                             <div class="col-10 ms-0 me-0 p-0">
                                  <input type='email' id='cc_email' class='ccdata' name='cc_email'/>
                             </div>
                         </div>
                         <hr style="height:4px; color:#0d6efd;background-color:#0d6efd;border-width:0;"/>
                         <div class="row">
                             <div class="col-12 ms-0 me-0 p-0">
                                   <?php draw_cc_html($cc); ?>
                             </div>
                         </div>
                     </div>
                 </form>
                 <hr style="height:4px; color:#0d6efd;background-color:#0d6efd;border-width:0;"/>
                 <p class="text-body">We Accept</p>
                 <img src='cards_accepted_64.png' alt="Visa, Mastercard, American Express, and Discover"/><br/>
<?php
      if($ini['test']==1) {
?>
                 <h2 class='text-danger'><strong>This won't charge your credit card.<br/>It also won't get you badges.</strong></h2>
    <?php
      }
    ?>
                 <p class="text-body"><?php echo $con['conname']; ?> memberships are not refundable, except in case of emergency. 
                 For details and questions about transfers and rollovers to future conventions, please see
                 <a href='<?php echo $con['regpolicy']; ?>'>The Registration Policies Page.</a></p>
             </div>
             <div class="col-6 p-2 border border-2 border-primary">
                 <h3 class="text-primary">Badges</h3>
                 <hr style="height:4px; color:#0d6efd;background-color:#0d6efd;border-width:0;"/>
                <div class="container-fluid" id='badge_list'>
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
<?php echo $con['conname']; ?> has temporarily suspended online registration <?php echo $ini['suspendreason']; ?> on our ability to host <?php echo $con['conname']; ?> this year.  Please see the <a href='<?php echo $ini['suspendmessage']; ?>'>Message from our Chair</a> for details.
</p>
<?php } else if($ini['close']==1) { ?>
<p class="text-primary">Preregistration for <?php echo $condata['label']; ?> is now closed.  
Badges will be available for purchase starting <?php echo $onsitesale; ?> by <?php echo $ini['onsiteopen'] . ' ' . $con['pickupareatext']; ?> 
<a href="<?php echo $con['hotelwebsite']; ?>"> <?php echo $con['hotelname']; ?></a>.
Daily rates are posted on <a href="<?php echo $con['dailywebsite']; ?>">The <?php echo $con['conname']; ?> website</a></p>
<p class="text-body"><?php echo $con['addlpickuptext']; ?></p>

<p class="text-body">We look forward to seeing you at <?php echo $con['conname']; ?>.</p>
<?php } else { ?>
<p class="text-primary">Online registration for <?php echo $condata['id']; ?> is not yet open. We aim to have online registration open 6 months before the convention.

We will post a notice when online registration opens on the
<a href="<?php echo $ini['registrationpage']; ?>">The <?php echo $con['conname']; ?> Registration Page</a>.  Mail-in forms are also available on that page.</p>

<?php } ?>
<p class="text-body"><a href="<?php echo $con['policy'];?>" target="_blank">Click here for the <?php echo $con['policytext']; ?></a>.<br/>
For more information about <?php echo $con['conname']; ?> please email <a href="mailto:<?php echo $con['infoemail']; ?>"><?php echo $con['infoemail']; ?></a>.<br/>
For questions about <?php echo $con['conname']; ?> Registration, email <a href="mailto:<?php echo $con['regemail']; ?>"><?php echo $con['regemail']; ?></a>.</p>

<script>
  <?php
  foreach($prices as $grp => $price) {
    echo "setPrice('$grp',$price);";
  }
  ?>

    $(function ()
    {
        var $width = document.documentElement.clientWidth;
        if ($width > 800) { $width = 800; }
        $('#newBadge').dialog({
            title: 'New Membership',
            classes: { "ui-dialog-titlebar": 'newBadge' },
            autoOpen: true,
            width: $width,
            height: 'auto',
            modal: true
        });
        $('#anotherBadge').dialog({
            resizeable: false,
            height: 'auto',
            width: 500,
            modal: true,
            autoOpen: false,
            buttons: {
                "Add Another": function () { $(this).dialog("close"); },
                "Review and Pay": function () {
                    $('#newBadge').dialog("close"); $(this).dialog("close");
                }
            }
        });
    });
</script>

</body>
</html>
