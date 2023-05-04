<?php 
// Vendor - index.php - Main page for vendor registration
require_once("lib/base.php");
$ini = redirect_https();
require_once("../lib/cc__load_methods.php");

$cc = get_conf('cc');
$con = get_conf('con');
$conid = $con['id'];
$vendor = get_conf('vendor');
$reg = get_conf('reg');
load_cc_procs();

$condata = get_con();

session_start();

$in_session = false;
$forcePassword = false;
$regserver = $reg['server'];

$reg_link = "<a href='$regserver'>Convention Registration</a>";

$priceR = dbSafeQuery('SELECT type, price_full FROM vendor_reg WHERE conid=?;', "i", array($condata['id']));
$price_list = array();
while($price = fetch_safe_assoc($priceR)) {
    $price_list[$price['type']]=$price['price_full'];
}

vendor_page_init($condata['label'] . ' Vendor Registration')
?>

<body id="vendorPortalBody">
    <div class="container-fluid">
        <div class="row">
            <div class="col-sm-12 p-0">
                <?php if (array_key_exists('logoimage', $ini) && $ini['logoimage'] != '') { 
                        if (array_key_exists('logoalt', $ini)) {
                            $altstring=$ini['logoalt'];
                        } else {
                            $altstring = 'Logo';
                        }
                ?>
                <img class="img-fluid" src="images/<?php echo $ini['logoimage']; ?>" alt="<?php echo $altstring ;?>"/>
                <?php }
                    if (array_key_exists('logotext', $ini) && $ini['logotext'] != '') {
                        echo $ini['logotext']; 
                    } ?>
            </div>
        </div>
        <div class="row">
             <div class="col-sm-12 mt-2">
                <h1>Vendor Portal</h1>
                <p>Welcome to the <?php echo $con['label']?> Verndor Portal.</p>
            </div>
        </div>
        <div class"row">
            <div class="col-sm-12">
                 <p>From here you can create and manage accounts for <?php echo $vendor['artventortext']; ?>.</p>
            </div>    
        </div>
<?php
if($vendor['test']==1) {
?>
        <div class"row">
            <div class="col-sm-12">
                <h2 class='warn'>This Page is for test purposes only</h2>
            </div>
        </div>
    </div>
<?php
}

if(isset($_SESSION['id'])) {
//echo "In Session";
    if(isset($_REQUEST['logout'])) {
        session_destroy();
        unset($_SESSION['id']);
    } else {
        $vendor = $_SESSION['id'];
        $artist = $_SESSION['artist'];
        if($vendor == $artist) { $vendor= $_SESSION['vendor']; $_SESSION['id']=$vendor; }
        $in_session = true;
    }
} else if (isset($_POST['email']) and isset($_POST['password'])) {
    //handle login
    $login = strtolower(sql_safe($_POST['email']));
    $loginQ = "SELECT id, password, need_new FROM vendors WHERE email='$login';";
    $loginR = dbQuery($loginQ);
    while ($result = fetch_safe_assoc($loginR)) {
        if(password_verify($_POST['password'], $result['password'])) {
            $_SESSION['id'] = $result['id'];
            $_SESSION['artist'] = 0;
            $_SESSION['dealer'] = 0;

            $vendor = $_SESSION['id'];
            $artist = $_SESSION['artist'];
            $in_session = true;

            if($result['need_new']) { $forcePassword = true; }
        } else {
        ?>
        <h2 class='warn'>Unable to Verify Password</h2>
        <?php
        }
    }
}

if (!$in_session) { ?>
<!-- Registgration Modal Popup -->
<div id='registration' class="modal modal-xl fade" tabindex="-1" aria-labelledby="New Vendor" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header" style="background-color: lightcyan;">
                <div class="modal-title">
                    <strong>New Vendor Registration</strong>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" style="padding: 4px; background-color: lightcyan;">
                <div class="container-fluid form-floating" style="background-color: lightcyan;">
                    <form id="registrationForm" name="registrionForm" action="javascript:void(0);" class="form-floating">
                    <div class="row">
                        <div class="col-sm-12">
                            <p>This form creates an account on the <?php echo $con['conname']; ?> vendor site. <?php echo $vendor['addlaccounttext']?></p>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-sm-12">
                            <p> Please provide us with information we can use to evaluate if you qualify and how you would fit in the selection of <?php
                                echo $vendor['artventortext'] ?> at <?php echo $con['conname']; ?>.<br/>Creating an account does not guarantee space.</p>
                        </div>
                    </div>
                        <div class="row mt-1">
                            <div class="col-sm-2">
                                <label for="name" title='This is the name that we will register your space under.'> *Name: </label>
                            </div>
                            <div class="col-sm-auto p-0 ms-0 me-0">
                                <input class="form-control-sm" type='text' name='name' id="name" maxlength="64" size="50" tabindex="1" required/><br/>
                                <span class='small'>Dealer, Artist, or Store name</span>
                            </div>
                        </div>
                        <div class="row mt-1">
                            <div class="col-sm-2">
                                <label for="email" title='Your email address is used for contact and login purposes.'> *Email/Login: </label>
                            </div>
                            <div class="col-sm-auto p-0 ms-0 me-0">
                                <input class="form-control-sm" type='email' name='email' id="email" maxlength="64" size="50" required/><br/>
                                <span class='small'>For Contact and Login</span>
                            </div>
                        </div>
                        <div class="row mt-1">
                            <div class="col-sm-2">
                                <label for="pw1"> *Password: </label>
                            </div>
                            <div class="col-sm-auto p-0 ms-0 me-0">
                                <input class="form-control-sm" id='pw1' type='password' name='password' required/>
                            </div>
                        </div>
                        <div class="row mt-1">
                            <div class="col-sm-2">
                                <label for="pw2"> *Confirm Password: </label>
                            </div>
                            <div class="col-sm-auto p-0 ms-0 me-0">
                                <input class="form-control-sm" id='pw2' type='password' name='password2' required/>
                            </div>
                        </div>
                        <div class="row mt-1">
                            <div class="col-sm-2">
                                <label for="website" title='Please enter your web site, Etsy site, social media site, or other appropriate URL.'>Website: </label>
                            </div>
                            <div class="col-sm-auto p-0 ms-0 me-0">
                                <input class="form-control-sm" id='website' type='text' size="64" name='website'/>
                            </div>
                        </div>
                        <div class="row mt-1">
                            <div class="col-sm-2">
                                <label for="description">*Description: </label>
                            </div>
                            <div class="col-sm-auto p-0 ms-0 me-0">
                                <textarea class="form-control-sm" id="description" name='description' rows=5 cols=64 required></textarea>
                            </div>
                        </div>
                        <div class="row mt-1">
                            <div class="col-sm-2 p-0 ms-0 me-0 pe-2 text-end">
                                <input class="form-control-sm" type='checkbox' name='publicity'/>
                            </div>
                            <div class="col-sm-auto p-0 ms-0 me-0">                        
                                <label>Check if we may use your information to publicize your attendence at <?php echo $con['conname']; ?>, if you're coming?</label>
                            </div>
                        </div>
                        <div class="row mt-1">
                            <div class="col-sm-2">
                                <label for="addr" title='Street Address'> *Address </label>
                            </div>
                            <div class="col-sm-auto p-0 ms-0 me-0">
                                <input class="form-control-sm" id='addr' type='text' size="64" name='addr' required/>
                            </div>
                        </div>
                        <div class="row mt-1">
                            <div class="col-sm-2">
                                <label for="addr2" title='Company Name'> Company </label>
                            </div>
                            <div class="col-sm-auto p-0 ms-0 me-0">
                                <input class="form-control-sm" id='addr2' type='text' size="64" name='addr2'/>
                            </div>
                        </div>
                        <div class="row mt-1">
                            <div class="col-sm-2">
                                <label for="city"> *City: </label>
                            </div>
                            <div class="col-sm-auto p-0 ms-0 me-0">
                                <input class="form-control-sm" id='city' type='text' size="32" name='city' required/>
                            </div>
                            <div class="col-sm-auto ms-0 me-0 p-0 ps-2">
                                <label for="state"> *State: </label>
                            </div>
                            <div class="col-sm-auto p-0 ms-0 me-0 ps-1">
                                <input class="form-control-sm" id='state' type='text' size="2" maxlength="2" name='state' required/>
                            </div>
                            <div class="col-sm-auto ms-0 me-0 p-0 ps-2">
                                <label for="zip"> *Zip: </label>
                            </div>
                            <div class="col-sm-auto p-0 ms-0 me-0 ps-1">
                                <input class="form-control-sm" id='zip' type='text' size="11" maxlength="11" name='zip' required/>
                            </div>
                        </div>
                        <div class="row mt-1">
                            <div class="col-sm-2"></div>
                            <div class="col-sm-auto p-0 ms-0 me-0">
                                <input type='submit' onclick='register();'/>
                            </div>
                        </div>
                    </form>
                        <br/><span class='warn small'>Completing this application does not guarantee space.</span>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- signin form (at body level) -->
<div id='signin'>
    <div class="container-fluid form-floating">
        <div class="row mb-2">
            <div class="col-sm-auto">
                <h4>Please log in to continue to the Portal.</h4>
            </div>
        </div>
        <form id='vendor-signin' method='POST'>
            <div class="row mt-1">
                <div class="col-sm-1">
                    <label for="email">*Email/Login: </label>
                </div>
                <div class="col-sm-auto">
                    <input class="form-control-sm" type='email' name='email' size='40'/>
                </div>
            </div>
            <div class="row mt-1">
                <div class="col-sm-1">
                    <label for="password">*Password: </label>
                </div>
                <div class="col-sm-auto">
                    <input class="form-control-sm" type='password' name='password' size="40"/>
                </div>
            </div>   
            <div class="row mt-2">
                <div class="col-sm-1"></div>
                <div class="col-sm-auto">
                    <input type='submit' class="btn btn-small btn-primary" value='signin'/> or <a href='javascript:void(0)' onclick="registrationModalOpen();">Sign Up</a>
                </div>
            </div>
        </form>
    </div>
</div>
<div id='resetpw'>
    <div class="container-fluid">
        <div class="row mt-4">
            <div class="col-sm-auto">
                <button class="btn btn-small btn-primary" onclick='resetPassword()'>Reset Password</button>
            </div>
        </div>
    </div>
</div>
<?php
    return;
}
// this section is for 'in-session' management
$vendorQ = "SELECT name, email, website, description, request_dealer, request_artistalley, request_fanac need_new FROM vendors WHERE id=?;";
$info = fetch_safe_assoc(dbSafeQuery($vendorQ, 'i', array($vendor)));

if($info['need_new']) {
?>
<p>You need to change your password.</p>
    <form id='changepw' action='javascript:void(0)'>
        <label>Old Password: <input type='password' id='oldPw' name='oldPassword'></input></label><br/>
        <label>New Password: <input type='password' id='pw2' name='password'></input></label><br/>
        <label>Re-enter Password: <input type='password' name='password2'></input></label><br/>
        <input type='submit' onClick='forceChangePassword()' value='Change'></input>
    </form>
<?php
} else {
    // modals for each section
?>
    <div id='updateProfile' class='modal modal-xl fade' tabindex='-1' aria-labelledby='Update Vendor Profile' aria-hidden='true'>
        <div class='modal-dialog'>
            <div class='modal-content'>
                <div class='modal-header' style='background-color: lightcyan;'>
                    <div class='modal-title'>
                        <strong>Update Vendor Profile</strong>
                    </div>
                    <button type='button' class='btn-close' data-bs-dismiss='modal' aria-label='Close'></button>
                </div>
                <div class='modal-body' style='padding: 4px; background-color: lightcyan;'>
                    <form id='vendor_update' action='javascript:void(0)'>
                        <label>Website: <input type='text' name='website' value='<?php echo $info['website'];?>'></input></label>
                        <label>Description: <textarea name='description' rows=5 cols=40><?php echo $info['description'];?></textarea></label><br/>
                        <input type='submit' onClick='updateProfile()' value='Update'></input>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <div id='changePassword' class='modal modal-xl fade' tabindex='-1' aria-labelledby='Change Vendor Account Password' aria-hidden='true'>
        <div class='modal-dialog'>
            <div class='modal-content'>
                <div class='modal-header' style='background-color: lightcyan;'>
                    <div class='modal-title'>
                        <strong>Change Vendor Account Password</strong>
                    </div>
                    <button type='button' class='btn-close' data-bs-dismiss='modal' aria-label='Close'></button>
                </div>
                <div class='modal-body' style='padding: 4px; background-color: lightcyan;'>
                    <form id='changepw' action='javascript:void(0)'>
                        <label>Old Password: <input type='password' id='oldPw' name='oldPassword'></input></label><br/>
                        <label>New Password: <input type='password' id='pw2' name='password'></input></label><br/>
                        <label>Re-enter Password: <input type='password' name='password2'></input></label><br/>
                        <input type='submit' onClick='changePassword()' value='Change'></input>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <div id='dealer_req' class='modal modal-xl fade' tabindex='-1' aria-labelledby='Request Dealer Space' aria-hidden='true'>
        <div class='modal-dialog'>
            <div class='modal-content'>
                <div class='modal-header' style='background-color: lightcyan;'>
                    <div class='modal-title'>
                        <strong>Request Dealer Space</strong>
                    </div>
                    <button type='button' class='btn-close' data-bs-dismiss='modal' aria-label='Close'></button>
                </div>
                <div class='modal-body' style='padding: 4px; background-color: lightcyan;'>
                    <p class='warn'>Please make sure your profile contains a good description of what you will be vending and a link for our staff to see what
                        you sell if at all possible.</p>
                    <form id='dealer_req_form' action='javascript:void(0)'>
                        <label>How many 6x6 spaces are you requesting? <select name='dealer_6'>
                                <option>0</option>
                                <option>1</option>
                                <option>2</option>
                            </select><br/> $<?php echo $price_list['dealer_6']; ?> per space.
                            <br/>
                            <label>How many 10x10 spaces are you requesting? <select name='dealer_10'>
                                    <option>0</option>
                                    <option>1</option>
                                    <option>2</option>
                                </select><br/> $<?php echo $price_list['dealer_10']; ?> per space.
                                <br/>
                                <br/>
                                You will be able to identify people for the free memberships<br/> and purchase discounted memberships later, if your request
                                <br/>is approved.<br/>
                                <input type='submit' onclick='dealer_req();'></input><input type='reset'></input>
                    </form>
                    <br/><span class='warn small'>Completing this application does not guarantee space.</span>
                </div>
            </div>
        </div>
    </div>
    <div id='alley_req' class='modal modal-xl fade' tabindex='-1' aria-labelledby='Request Artist Alley Space' aria-hidden='true'>
        <div class='modal-dialog'>
            <div class='modal-content'>
                <div class='modal-header' style='background-color: lightcyan;'>
                    <div class='modal-title'>
                        <strong>Request Artist Alley Space</strong>
                    </div>
                    <button type='button' class='btn-close' data-bs-dismiss='modal' aria-label='Close'></button>
                </div>
                <div class='modal-body' style='padding: 4px; background-color: lightcyan;'>
                    <p class='warn'>Please make sure your profile contains a good description of your art and a link for our staff to see it if at all
                        possible.</p>
                    <form id='alley_req_form' action='javascript:void(0)'>
                        <label>How many tables are you requesting? <select name='alley_tables'>
                                <option>1</option>
                                <option>2</option>
                            </select><br/> $<?php echo $price_list['alley']; ?> per table.
                            <br/>
                            <br/>
                            You will have an option to purchase discounted memberships<br/> later, if your request is approved.<br/>
                            <input type='submit' onclick='alley_req();'></input><input type='reset'></input>
                    </form>
                    <br/><span class='warn small'>Completing this application does not guarantee space.</span>
                </div>
            </div>
        </div>
    </div>
    <div id='virtual_req' class='modal modal-xl fade' tabindex='-1' aria-labelledby='Request Virtual Vendor Space' aria-hidden='true'>
        <div class='modal-dialog'>
            <div class='modal-content'>
                <div class='modal-header' style='background-color: lightcyan;'>
                    <div class='modal-title'>
                        <strong>Request Virtual; Vendor Space</strong>
                    </div>
                    <button type='button' class='btn-close' data-bs-dismiss='modal' aria-label='Close'></button>
                </div>
                <div class='modal-body' style='padding: 4px; background-color: lightcyan;'>

                    <p class='warn'>Please make sure your profile contains a good description of what you're selling and a link for our staff to see it if at
                        all possible.</p>
                    <form id='virtual_req_form' action='javascript:void(0)'>
                        Joining the virtual vendor space costs $<?php echo $price_list['virtual']; ?>.<br/>
                        <br/>
                        <br/>
                        <input type='submit' onclick='virtual_req();'></input><input type='reset'></input>
                    </form>
                    <br/><span class='warn small'>Completing this application does not guarantee space.</span>
                </div>
            </div>
        </div>
    </div>
    <div id='alley_invoice' class='modal modal-xl fade' tabindex='-1' aria-labelledby='Artist Alley Invoice' aria-hidden='true'>
        <div class='modal-dialog'>
            <div class='modal-content'>
                <div class='modal-header' style='background-color: lightcyan;'>
                    <div class='modal-title'>
                        <strong>Artist Alley Invoice</strong>
                    </div>
                    <button type='button' class='btn-close' data-bs-dismiss='modal' aria-label='Close'></button>
                </div>
                <div class='modal-body' style='padding: 4px; background-color: lightcyan;'>
                    <?php echo $info['name']; ?> you are approved for <span id='alley_count'></span> <span id='alley_size'></span> Artist Alley Tables at $<span
                        id='alley_price'></span>. You may purchase 1 discounted membership per table at $<span id='alley_mem_price'></span>, anyone working the
                    table must have a mebership to the convention.

                    <hr/>
                    <form id='alley_invoice_form' action='javascript:void(0);'>
                        <span class='blocktitle'>Vendor Information</span><br/>
                        Please fill out this section with information on the vendor or store.
                        <input type='hidden' name='vendor' id='alley_id' value='<?php echo $vendor; ?>'/> <br/>
                        Name: <input type='text' name='name' id='alley_name' value='<?php echo $info['name']; ?>'/>
                        Email: <input type='text' name='email' id='alley_email' value='<?php echo $info['email']; ?>'/>
                        Address: <input type='text' name='address'/><br/>
                        City: <input type='text' name='city'/> State: <input type='text' name='state' size=3/> Zip: <input type='text' name='zip' size=6/><br/>

                        <br/>
                        Maryland Retail Tax ID: <input type='text' name='taxid'></input><br/>
                        (If you have one. If you do not, Balticon will get you a temporary ID for this event.)<br/>
                        <br/>
                        Subtotal for Spaces $<span id='alley_invoice_cost'></span><br/>
                        Membership costs will be calculated below.
                        <input type='hidden' id='alley_table_cost' name='table_sub'/>
                        <input type='hidden' id='alley_item_count' name='table_count'/>
                        Special Requests (electricity, same location as last year, etc. We will try, but cannot guarantee, to honor your request):<br/>
                        <textarea name='requests'></textarea>
                        <hr/>
                        As an Arist Alley artist you have the option to be included in our marketing materials.
                        <label><input type='checkbox' name='alley_bsfan'></input>List me in the BSFan Program Book</label><br/>
                        <label><input type='checkbox' name='alley_website'></input>List me on the Website</label><br/>
                        <label><input type='checkbox' name='alley_some'></input>List me on social media</label><br/>
                        <label><input type='checkbox' name='alley_prog'></input>I want to participate in Programing</label><br/>
                        <label><input type='checkbox' name='alley_demo'></input>I am interested in presenting a lecture or workshop</label><br/>
                        <hr/>
                        Discount Memberships: <span id='alley_membership_count'></span><br/>
                        <label><input type='checkbox' name='alley_mem1_have' onchange='$("#alley_mem1").toggle(); updateMemCount("mem1", this);'></input>I
                            expect to recieve a membership from another source, if this changes I will contact the artist alley coordinator. I understand
                            everyone staffing the table needs a membership.</label>
                        <div id='alley_mem1'>
                            Name:
                            <input type='text' name='alley_mem1_fname' size=15/>
                            <input type='text' name='alley_mem1_mname' size=10/>
                            <input type='text' name='alley_mem1_lname' size=15/>
                            <br/>
                            Badge Name: <input type='text' name='alley_mem1_bname'/><br/>
                            Address: <input type='text' name='alley_mem1_address'/><br/>
                            Company: <input type='text' name='alley_mem1_addr2'/><br/>
                            City: <input type='text' name='alley_mem1_city'/> State: <input type='text' name='alley_mem1_state' size=3/> Zip: <input type='text'
                                                                                                                                                     name='alley_mem1_zip'
                                                                                                                                                     size=6/><br/>
                        </div>
                        <br/>
                        <label id='alley_mem2_need'><input type='checkbox' name='alley_mem2_have'
                                                           onchange='$("#alley_mem2").toggle(); updateMemCount("mem2", this);'></input>I do not need a second
                            membership or expect to recieve a membership from another source, if this changes I will contact the artist alley coordinator. I
                            understand everyone staffing the table needs a membership.</label>
                        <div id='alley_mem2'>
                            Name:
                            <input type='text' name='alley_mem2_fname' size=15/>
                            <input type='text' name='alley_mem2_mname' size=10/>
                            <input type='text' name='alley_mem2_lname' size=15/>
                            <br/>
                            Badge Name: <input type='text' name='alley_mem2_bname'/><br/>
                            Address: <input type='text' name='alley_mem2_address'/><br/>
                            Company: <input type='text' name='alley_mem2_addr2'/><br/>
                            City: <input type='text' name='alley_mem2_city'/> State: <input type='text' name='alley_mem2_state' size=3/> Zip: <input type='text'
                                                                                                                                                     name='alley_mem2_zip'
                                                                                                                                                     size=6/><br/>
                        </div>
                        <hr/>
                        Subtotal for Memberships $<span id='alley_member_cost'></span><br/>
                        Total: $<span id='alley_total_cost'></span><br/>
                        <input type='hidden' id='alley_mem_cost' name='mem_cost'/>
                        <input type='hidden' id='alley_mem_count' name='mem_cnt'/>
                        <input type='hidden' id='alley_member_total' name='mem_total'/>
                        <input type='hidden' id='alley_total' name='total'/>
                        Payment Information:
                        <?php
                        if ($ini['test'] == 1) {
                            ?>
                            <h2 class='warn'>This won't charge your credit card, or do anything else.</h2>
                            <?php
                        }
                        ?>

                        First Name: <input type='text' name='fname' required='required'/><br/>
                        Last Name: <input type='text' name='lname' required='required'/><br/>
                        Street <input type='text' name='street' required='required'/>
                        City: <input type='text' name='city'/> State: <input type='text' name='state' size=3/> Zip: <input type='text' name='zip' size=6/><br/>
                        Country: <select class='ccdata' required='required' name='country' size=1>
                            <?php
                            $fh = fopen(__DIR__ . '/../lib/countryCodes.csv', 'r');
                            while (($data = fgetcsv($fh, 1000, ',', '"')) != false) {
                                echo "<option value='" . $data[1] . "'>" . $data[0] . "</option>";
                            }
                            fclose($fh);
                            ?>
                        </select>
                        <br/>
                        </select>
                        <br/>
                        We Accept<br/>
                        <img src='cards_accepted_64.png' alt="Visa, Mastercard, American Express, and Discover"/><br/>
                        <hr/>
                        Please wait for the email, and don't click the submit more than once.
                        <?php draw_cc_html($cc, "--", 1); ?>
                        <input type='reset'></input>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <div id='dealer_invoice' class='modal modal-xl fade' tabindex='-1' aria-labelledby='Dealer Invoice' aria-hidden='true'>
        <div class='modal-dialog'>
            <div class='modal-content'>
                <div class='modal-header' style='background-color: lightcyan;'>
                    <div class='modal-title'>
                        <strong>Dealer Invoice</strong>
                    </div>
                    <button type='button' class='btn-close' data-bs-dismiss='modal' aria-label='Close'></button>
                </div>
                <div class='modal-body' style='padding: 4px; background-color: lightcyan;'>
                    <?php echo $info['name']; ?> you are approved for <span id='dealer_count'></span> <span id='dealer_size'></span> spaces at $<span
                        id='dealer_price'></span>. Each space comes with one membership. <br/>
                    You may request up to two additional memberships at $55.
                    <hr/>
                    <form id='dealer_invoice_form' action='javascript:void(0);'>
                        <span class='blocktitle'>Vendor Information</span><br/>
                        Please fill out this section with information on the vendor or store.
                        <input type='hidden' name='vendor' id='dealer_id' value='<?php echo $vendor; ?>'/> <br/>
                        Name: <input type='text' name='name' id='dealer_name' value='<?php echo $info['name']; ?>'/>
                        Email: <input type='text' name='email' id='dealer_email' value='<?php echo $info['email']; ?>'/>
                        Address: <input type='text' name='address'/><br/>
                        City: <input type='text' name='city'/> State: <input type='text' name='state' size=3/> Zip: <input type='text' name='zip' size=6/><br/>

                        <br/>
                        Maryland Retail Tax ID: <input type='text' name='taxid'></input><br/>
                        (If you have one. If you do not, Balticon will get you a temporary ID for this event.)<br/>
                        <br/>
                        Cost for Spaces $<span id='dealer_space_cost'></span><br/>
                        <input type='hidden' id='dealer_space_sub' name='table_sub'/>
                        <input type='hidden' id='dealer_cost' name='total'/>
                        <input type='hidden' id='dealer_type' name='type'/>
                        <input type='hidden' id='dealer_item_count' name='count'/>
                        Special Requests:<br/>
                        <textarea name='requests'></textarea>
                        <hr/>
                        Included Memberships: <span id='dealer_mem_count'></span>
                        <input type='hidden' id='dealer_free_mem' name='free_mem_count'/>
                        <div id='dealer_mem1'>
                            Name:
                            <input type='text' name='dealer_mem1_fname'/ size=15>
                            <input type='text' name='dealer_mem1_mname'/ size=10>
                            <input type='text' name='dealer_mem1_lname'/ size=15>
                            <br/>
                            Badge Name: <input type='text' name='dealer_mem1_bname'/><br/>
                            Address: <input type='text' name='dealer_mem1_address'/><br/>
                            Company: <input type='text' name='dealer_mem1_addr2'/><br/>
                            City: <input type='text' name='dealer_mem1_city'/> State: <input type='text' name='dealer_mem1_state' size=3/> Zip: <input
                                type='text' name='dealer_mem1_zip' size=6/><br/>
                        </div>
                        <br/>
                        <div id='dealer_mem2'>
                            Name:
                            <input type='text' name='dealer_mem2_fname'/ size=15>
                            <input type='text' name='dealer_mem2_mname'/ size=10>
                            <input type='text' name='dealer_mem2_lname'/ size=15>
                            <br/>
                            Badge Name: <input type='text' name='dealer_mem2_bname'/><br/>
                            Address: <input type='text' name='dealer_mem2_address'/><br/>
                            Company: <input type='text' name='dealer_mem2_addr2'/><br/>
                            City: <input type='text' name='dealer_mem2_city'/> State: <input type='text' name='dealer_mem2_state' size=3/> Zip: <input
                                type='text' name='dealer_mem2_zip' size=6/><br/>
                        </div>
                        Select number of additional memberships at $<span 'dealer_mem_price'>55</span>:
                        <select id='dealer_num_paid' name='dealer_num_paid' onchange='updateDealerPaid()'>
                            <option value='0'>0</option>
                            <option value='1'>1</option>
                            <option value='2'>2</option>
                        </select>
                        <div id='dealer_paid1'>
                            Name:
                            <input type='text' name='dealer_paid1_fname'/ size=15>
                            <input type='text' name='dealer_paid1_mname'/ size=10>
                            <input type='text' name='dealer_paid1_lname'/ size=15>
                            <br/>
                            Badge Name: <input type='text' name='dealer_paid1_bname'/><br/>
                            Address: <input type='text' name='dealer_paid1_address'/><br/>
                            Company: <input type='text' name='dealer_paid1_addr2'/><br/>
                            City: <input type='text' name='dealer_paid1_city'/> State: <input type='text' name='dealer_paid1_state' size=3/> Zip: <input
                                type='text' name='dealer_paid1_zip' size=6/><br/>
                        </div>
                        <br/>
                        <div id='dealer_paid2'>
                            Name:
                            <input type='text' name='dealer_paid2_fname'/ size=15>
                            <input type='text' name='dealer_paid2_mname'/ size=10>
                            <input type='text' name='dealer_paid2_lname'/ size=15>
                            <br/>
                            Badge Name: <input type='text' name='dealer_paid2_bname'/><br/>
                            Address: <input type='text' name='dealer_paid2_address'/><br/>
                            Company: <input type='text' name='dealer_paid2_addr2'/><br/>
                            City: <input type='text' name='dealer_paid2_city'/> State: <input type='text' name='dealer_paid2_state' size=3/> Zip: <input
                                type='text' name='dealer_paid2_zip' size=6/><br/>
                        </div>
                        Cost for Memberships: $<span id='dealer_mem_cost'>0</span>
                        <input type='hidden' id='dealer_paid_mem_count' name='mem_cnt'/>
                        <hr/>
                        Total: <span id='dealer_invoice_cost'></span>
                        Payment Information:
                        <?php
                        if ($ini['test'] == 1) {
                            ?>
                            <h2 class='warn'>This won't charge your credit card, or do anything else.</h2>
                            <?php
                        }
                        ?>
                        <br/>
                        We Accept<br/>
                        <img src='cards_accepted_64.png' alt="Visa, Mastercard, American Express, and Discover"/><br/>
                        <hr/>
                        Please wait for the email, and don't click the submit more than once.
                        <?php draw_cc_html($cc, "--", 2); ?>
                        <input type='reset'></input>

                    </form>
                </div>
            </div>
        </div>
    </div>
    <!-- end of modals, start of main body -->
<div>
<h3>Welcome to the Portal Page for <?php echo $info['name'];?></h3>
<button onclick='$("#updateProfile").dialog("open");'>View/Change Profile</button>
<button onclick='$("#changePassword").dialog("open");'>Change Password</button>

<p>
<?php echo $con['label'];?> has multiple types of spaces for vendors.  If you select a type for which you aren't qualified we will alert groups managing other spaces.
</p>
<?php /*
<p label='fanac'
Do we want to include parties?
</p>
*/ ?>
<?php /*
<h3>Virtual Vendor</h3>
<p label='virtual'>
<?php echo $con['label'];?> will host a virtual vendors space. Participating in that space costs $<?php echo $price_list['virtual'];?> and vendors who participate will get <em>Nora/Kelly: Please provide a description of what they get</em>.
</p>
<?php
$aaQ = "SELECT requested, authorized, purchased, price, paid, transid FROM vendor_show WHERE type='virtual' and vendor = $vendor and conid=$conid;";
$aaR = dbQuery($aaQ);
$virtual_info = NULL;
if($aaR->num_rows >= 1) { 
    $virtual_info = fetch_safe_assoc($aaR);
    if($virtual_info['authorized'] > $virtual_info['purchased']) {
        ?><button onclick="openInvoice('virtual', <?php echo ($virtual_info['authorized'] - $virtual_info['purchased']);?>, <?php echo $price_list['virtual'];?>)">Pay Virtual Vendor Space Invoice</button> <?php
    } else if($virtual_info['requested'] > $virtual_info['authorized']){
        ?><div>Request Pending Authorization.</div><?php
    } else {
        ?><div>Registered for <?php echo $virtual_info['purchased'];?></div><?php
    }
} else {
    ?><button onclick='virtual_req();'>Request Virtual Vendor</button><?php
}
?>
<hr/>
*/ ?>
<div>
<h3>Artist Alley</h3>
<p label='artist'>
<?php echo $con['label'];?> hosts an Artist Alley to enable artists to directly interact with Balticon members.  These tables cost $<?php echo $price_list['alley']; ?>, and are the 5th floor Atrium, a central space open to all convention members for the duration of the convention and are not secured at night.  We expect artists to usually have someone at their table from 10am through 6pm, although short absences are acceptable for panel participation, etc.  We encourage artists to engage in their craft at their table to attract potential customers.
A membership is required to run the table, one discounted membership is available per table.
</p>
<?php
$aaQ = "SELECT requested, authorized, purchased, price, paid, transid FROM vendor_show WHERE type='alley' and vendor = $vendor and conid=$conid;";
$aaR = dbQuery($aaQ);
$alley_info = NULL;
if($aaR->num_rows >= 1) { 
    $alley_info = fetch_safe_assoc($aaR);
    if($alley_info['authorized'] > $alley_info['purchased']) {
        ?><button onclick="openInvoice('alley', <?php echo ($alley_info['authorized'] - $alley_info['purchased']);?>, <?php echo $price_list['alley'];?>)">Pay Artist Alley Invoice</button> <?php
    } else if($alley_info['requested'] > $alley_info['authorized']){
        ?><div>Request Pending Authorization.</div><?php
    } else {
        ?><div>Registered for <?php echo $alley_info['purchased'];?></div><?php
    }
} else {
    ?><button onclick='$("#alley_req").dialog("open");'>Request Artist Alley</button><?php
}
?>
<hr/>
<h3>Dealers Room</h3>
<p label='dealer'>
The primary space for vendors at <?php echo $con['label'];?> are the Dealers Rooms, two adjacent rooms on the 5th floor.  Space in these rooms are predominately sold as 1 or 2 6x6 spaces for $<?php echo $price_list['dealer_6']; ?> each coordinate with the head of the dealers room if you want more than 2 spaces. Each space comes with a table/chair (if desired) and a membership, additional memberships will be available at a discounted rate.  Dealers spaces are expected to be attended while the rooms are open Friday 2pm - 7pm, Saturday 10am - 7pm, Sunday 10am - 7pm, and Monday 10am - 2pm.
</p>
<?php
$drQ = "SELECT type, requested, authorized, purchased, price, paid, transid FROM vendor_show WHERE type in ('dealer_6', 'dealer_10') and vendor = $vendor and conid=$conid;";
$drR = dbQuery($drQ);
$dealer_info = NULL;
if($drR->num_rows >= 1) { 
    while($dealer_hold = fetch_safe_assoc($drR)) {
        if($dealer_hold['type'] == 'dealer_10' && $dealer_hold['authorized'] != "0") { $dealer_info = $dealer_hold; }
        else if($dealer_hold['type'] == 'dealer_6' && $dealer_info == null) { $dealer_info = $dealer_hold; }
    }

    if($dealer_info['authorized'] > $dealer_info['purchased']) {
        ?><button onclick="openInvoice('dealer', <?php echo ($dealer_info['authorized'] - $dealer_info['purchased']);?>, <?php echo $price_list[$dealer_info['type']];?>, '<?php echo $dealer_info['type'];?>')">Pay Dealers Room Invoice</button> <?php
    } else if($dealer_info['requested'] > $dealer_info['authorized']){
        ?><div>Request Pending Authorization.</div><?php
    } else {
        ?><div>Registered for <?php echo $dealer_info['purchased'];?></div><?php
    }
} else {
    ?><button onclick='$("#dealer_req").dialog("open");'>Request Dealers Space</button><?php
}
?>
</div>
</div>
<a href='?logout'>Logout</a>
<?php } ?>
</body>
</html>
