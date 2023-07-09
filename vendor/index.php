<?php
// Vendor - index.php - Main page for vendor registration
require_once("lib/base.php");
require_once("../lib/cc__load_methods.php");

$cc = get_conf('cc');
$con = get_conf('con');
$conid = $con['id'];
$vendor_conf = get_conf('vendor');
$ini = get_conf('reg');
load_cc_procs();

$condata = get_con();

$in_session = false;
$forcePassword = false;
$regserver = $ini['server'];

$reg_link = "<a href='$regserver'>Convention Registration</a>";

vendor_page_init($condata['label'] . ' Vendor Registration')
?>

<body id="vendorPortalBody">
<div class="container-fluid">
    <div class="row">
        <div class="col-sm-12 p-0">
            <?php if (array_key_exists('logoimage', $ini) && $ini['logoimage'] != '') {
                if (array_key_exists('logoalt', $ini)) {
                    $altstring = $ini['logoalt'];
                } else {
                    $altstring = 'Logo';
                }
                ?>
                <img class="img-fluid" src="images/<?php echo $ini['logoimage']; ?>" alt="<?php echo $altstring; ?>"/>
            <?php }
            if (array_key_exists('logotext', $ini) && $ini['logotext'] != '') {
                echo $ini['logotext'];
            } ?>
        </div>
    </div>
    <div class="row">
        <div class="col-sm-12 mt-2">
            <h1>Vendor Portal</h1>
        </div>
    </div>
    <div class="row p-1">
        <div class="col-sm-auto">
            Welcome to the <?php echo $con['label'] ?> Verndor Portal.
        </div>
    </div>
    <div class=row">
        <div class="col-sm-12">
            From here you can create and manage your account for <?php echo $vendor_conf['artventortext']; ?>.
        </div>
    </div>
<?php
if ($vendor_conf['test'] == 1) {
    ?>
    <div class="row">
        <div class="col-sm-12">
            <h2 class='warn'>This Page is for test purposes only</h2>
        </div>
    </div>
    <?php
}

if (isset($_SESSION['id'])) {
//echo "In Session";
    if (isset($_REQUEST['logout'])) {
        session_destroy();
        unset($_SESSION['id']);
    } else {
        $vendor = $_SESSION['id'];
        $artist = $_SESSION['artist'];
        if ($vendor == $artist) {
            $vendor = $_SESSION['vendor'];
            $_SESSION['id'] = $vendor;
        }
        $in_session = true;
    }
} else if (isset($_POST['si_email']) and isset($_POST['si_password'])) {
    //handle login
    $login = strtolower(sql_safe($_POST['si_email']));
    $loginQ = "SELECT id, password, need_new FROM vendors WHERE email=?;";
    $loginR = dbSafeQuery($loginQ, 's', array($login));
    while ($result = fetch_safe_assoc($loginR)) {
        if (password_verify($_POST['si_password'], $result['password'])) {
            $_SESSION['id'] = $result['id'];
            $_SESSION['artist'] = 0;
            $_SESSION['dealer'] = 0;

            $vendor = $_SESSION['id'];
            $artist = $_SESSION['artist'];
            $in_session = true;

            if ($result['need_new']) {
                $forcePassword = true;
            }
        } else {
            ?>
            <h2 class='warn'>Unable to Verify Password</h2>
            <?php
        }
    }
}

if (!$in_session) { ?>
    <!-- Registgration Modal Popup -->
    <div id='registration' class="modal modal-xl fade" tabindex="-1" aria-labelledby="New Vendor" aria-hidden="true" style='--bs-modal-width: 80%;'>
        <div class="modal-dialog">
            <div class="modal-content">
                <div class='modal-header bg-primary text-bg-primary'>
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
                                    <p>This form creates an account on the <?php echo $con['conname']; ?> vendor
                                        site. <?php echo $vendor_conf['addlaccounttext'] ?></p>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-sm-12">
                                    <p> Please provide us with information we can use to evaluate if you qualify and how you would fit in the selection of <?php
                                        echo $vendor_conf['artventortext'] ?> at <?php echo $con['conname']; ?>.<br/>Creating an account does not guarantee space.
                                    </p>
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
                                    <label for="website"
                                           title='Please enter your web site, Etsy site, social media site, or other appropriate URL.'>Website: </label>
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
                                    <input class="form-control-sm" type='checkbox' id='publicity' name='publicity'/>
                                </div>
                                <div class="col-sm-auto p-0 ms-0 me-0">
                                    <label for='publicity'>Check if we may use your information to publicize your attendence at <?php echo $con['conname']; ?>, if you're
                                        coming?</label>
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
                                <div class="col-sm-auto p-0 ms-0 me-0 ps-1 pb-2">
                                    <input class="form-control-sm" id='zip' type='text' size="11" maxlength="11" name='zip' required/>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class='btn btn-sm btn-secondary' data-bs-dismiss='modal'>Cancel</button>
                    <button class='btn btn-sm btn-primary' onClick='register()'>Register</button>
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
                        <label for="si_email">*Email/Login: </label>
                    </div>
                    <div class="col-sm-auto">
                        <input class="form-control-sm" type='email' name='si_email' id='si_email' size='40' required/>
                    </div>
                </div>
                <div class="row mt-1">
                    <div class="col-sm-1">
                        <label for="si_password">*Password: </label>
                    </div>
                    <div class="col-sm-auto">
                        <input class="form-control-sm" type='password' id='si_password' name='si_password' size="40" required/>
                    </div>
                </div>
                <div class="row mt-2">
                    <div class="col-sm-1"></div>
                    <div class="col-sm-auto">
                        <input type='submit' class="btn btn-primary" value='signin'/> or
                            <a href='javascript:void(0)' onclick="registrationModalOpen();">Sign Up</a>
                    </div>
                </div>
            </form>
            <div id='result_message' class='mt-4 p-2'></div>
        </div>
    </div>
    <div id='resetpw'>
        <div class="container-fluid">
            <div class="row mt-4">
                <div class="col-sm-auto">
                    <button class="btn btn-primary" onclick='resetPassword()'>Reset Password</button>
                </div>
            </div>
        </div>
    </div>
    </body>
</html>
    <?php
    return;
}
// this section is for 'in-session' management
// build spaces array
$spaceQ = <<<EOS
SELECT v.id, v.shortname, v.name, v.description, v.unitsAvailable, v.memId, m.price AS memPrice
FROM vendorSpaces v
JOIN memList m ON (v.memId = m.id)
WHERE v.conid=?
ORDER BY shortname;
EOS;

$spaceR =  dbSafeQuery($spaceQ, 'i', array($condata['id']));
$space_list = array();
$spaces = array();
// output the data for the scripts to use

while ($space = fetch_safe_assoc($spaceR)) {
    $space_list[$space['id']] = $space;
    $spaces[$space['shortname']] = $space['id'];
}

// built price lists
foreach ($space_list AS $shortname => $space) {
    $priceQ = <<<EOS
SELECT id, spaceId, code, description, units, price, includedMemberships, additionalMemberships, requestable
FROM vendorSpacePrices
WHERE spaceId=?
ORDER BY units;
EOS;
    $priceR = dbSafeQuery($priceQ, 'i', array($space['id']));
    $price_list = array();
    while ($price = fetch_safe_assoc($priceR)) {
        $units = strval($price['units'] + 0);
        $price_list[$units] = $price;
    }
    $space_list[$space['id']]['prices'] = $price_list;
}

// get this vendor
$vendorQ = <<<EOS
SELECT name, email, website, description, addr, addr2, city, state, zip, publicity, need_new
FROM vendors
WHERE id=?;
EOS;

$info = fetch_safe_assoc(dbSafeQuery($vendorQ, 'i', array($vendor)));
if ($info['need_new']) {
    drawChangePassword('You need to change your password.', 2, true);
    return;
}

// load the country codes for the option pulldown
$fh = fopen(__DIR__ . '/../lib/countryCodes.csv', 'r');
$countryOptions = '';
while(($data = fgetcsv($fh, 1000, ',', '"'))!=false) {
    $countryOptions .=  "<option value='".$data[1]."'>".$data[0]."</option>\n";
}
fclose($fh);
?>
<script type='text/javascript'>
var vendor_spaces = <?php echo json_encode($space_list); ?>;
var vendor_info = <?php echo json_encode($info); ?>;
var country_options = <?php echo json_encode($countryOptions); ?>;
</script>
<?php

$vendorSQ = <<<EOS
SELECT *
FROM vw_VendorSpace
WHERE vendorId = ? and conid = ?;
EOS;

$vendorSR = dbSafeQuery($vendorSQ, 'ii', array($vendor, $condata['id']));
$vendor_spacelist = array();
while ($space = fetch_safe_assoc($vendorSR)) {
    $vendor_spacelist[$space['id']] = $space;
}

    // modals for each section
    ?>
    <div id='update_profile' class='modal modal-xl fade' tabindex='-1' aria-labelledby='Update Vendor Profile' aria-hidden='true'>
        <div class='modal-dialog'>
            <div class='modal-content'>
                <div class='modal-header bg-primary text-bg-primary'>
                    <div class='modal-title'>
                        <strong>Update Vendor Profile</strong>
                    </div>
                    <button type='button' class='btn-close' data-bs-dismiss='modal' aria-label='Close'></button>
                </div>
                <div class='modal-body' style='padding: 4px; background-color: lightcyan;'>
                    <div class="container-fluid">
                        <form id='vendor_update' action='javascript:void(0)'>
                            <div class='row p-1'>
                                <div class='col-sm-2 p-0'>
                                    <label for='name'>Name:</label>
                                </div>
                                <div class='col-sm-10 p-0'>
                                    <input class='form-control-sm' type='text' name='name' id='name' size='64' value='<?php echo $info['name']; ?>' required/>
                                </div>
                            </div>
                            <div class='row p-1'>
                                <div class='col-sm-2 p-0'>
                                    <label for='emai'>Email:</label>
                                </div>
                                <div class='col-sm-10 p-0'>
                                    <input class='form-control-sm' type='text' name='email' id='email' size='64' value='<?php echo $info['email']; ?>' required/>
                                </div>
                            </div>
                            <div class="row p-1">
                                <div class="col-sm-2 p-0">
                                    <label for="website">Website:</label>
                                </div>
                                <div class="col-sm-10 p-0">
                                    <input class='form-control-sm' type='text' name='website' id='website' value='<?php echo $info['website']; ?>' required/>
                                </div>
                            </div>
                            <div class='row p-1'>
                                <div class='col-sm-2 p-0'>
                                    <label for='description'>Description:</label>
                                </div>
                                <div class='col-sm-10 p-0'>
                                    <textarea class="form-control-sm" name='description' id='description' rows=5 cols=60><?php echo $info['description']; ?></textarea>
                                </div>
                            </div>
                            <div class='row mt-1'>
                                <div class='col-sm-2 p-0 ms-0 me-0 pe-2 text-end'>
                                    <input class='form-control-sm' type='checkbox' <?php echo $info['publicity'] != 0 ? 'checked' : ''; ?> name='publicity' id="publicity"/>
                                </div>
                                <div class='col-sm-auto p-0 ms-0 me-0'>
                                    <label>Check if we may use your information to publicize your attendence at <?php echo $con['conname']; ?></label>
                                </div>
                            </div>
                            <div class="row mt-1">
                                <div class="col-sm-2">
                                    <label for="addr" title='Street Address'>Address </label>
                                </div>
                                <div class="col-sm-auto p-0 ms-0 me-0">
                                    <input class="form-control-sm" id='addr' type='text' size="64" name='addr' value='<?php echo $info['addr']; ?>' required/>
                                </div>
                            </div>
                            <div class="row mt-1">
                                <div class="col-sm-2">
                                    <label for="addr2" title='Company Name'>Company/ Address line 2:</label>
                                </div>
                                <div class="col-sm-auto p-0 ms-0 me-0">
                                    <input class="form-control-sm" id='addr2' type='text' size="64" value='<?php echo $info['addr2']; ?>' name='addr2'/>
                                </div>
                            </div>
                            <div class="row mt-1">
                                <div class="col-sm-2">
                                    <label for="city">City: </label>
                                </div>
                                <div class="col-sm-auto p-0 ms-0 me-0">
                                    <input class="form-control-sm" id='city' type='text' size="32" value='<?php echo $info['city']; ?>' name=' city' required/>
                                </div>
                                <div class="col-sm-auto ms-0 me-0 p-0 ps-2">
                                    <label for="state"> State: </label>
                                </div>
                                <div class="col-sm-auto p-0 ms-0 me-0 ps-1">
                                    <input class="form-control-sm" id='state' type='text' size="2" maxlength="2" value='<?php echo $info['state']; ?>'
                                           name='state' required/>
                                </div>
                                <div class="col-sm-auto ms-0 me-0 p-0 ps-2">
                                    <label for="zip"> Zip: </label>
                                </div>
                                <div class="col-sm-auto p-0 ms-0 me-0 ps-1 pb-2">
                                    <input class="form-control-sm" id='zip' type='text' size="11" maxlength="11" value='<?php echo $info['zip']; ?>' name='zip'
                                           required/>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
                <div class='modal-footer'>
                    <button class='btn btn-sm btn-secondary' data-bs-dismiss='modal'>Cancel</button>
                    <button class='btn btn-sm btn-primary' onClick='updateProfile()'>Update</button>
                </div>
            </div>
        </div>
    </div>
    <div id='changePassword' class='modal modal-xl fade' tabindex='-1' aria-labelledby='Change Vendor Account Password' aria-hidden='true'>
        <div class='modal-dialog'>
            <div class='modal-content'>
                <div class='modal-header bg-primary text-bg-primary'>
                    <div class='modal-title'>
                        <strong>Change Vendor Account Password</strong>
                    </div>
                    <button type='button' class='btn-close' data-bs-dismiss='modal' aria-label='Close'></button>
                </div>
                <div class='modal-body' style='padding: 4px; background-color: lightcyan;'>
                    <?php drawChangePassword('', 3, false);
                    ?>
                </div>
                <div class='modal-footer'>
                    <button class='btn btn-sm btn-secondary' data-bs-dismiss='modal'>Cancel</button>
                    <button class='btn btn-sm btn-primary' onClick='changePassword()'>Change Password</button>
                </div>
            </div>
        </div>
    </div>
    <!-- request -->
    <div id='vendor_req' class='modal modal-xl fade' tabindex='-1' aria-labelledby='Request $spacetitle Space' aria-hidden='true'>
        <div class='modal-dialog'>
            <div class='modal-content'>
                <div class='modal-header bg-primary text-bg-primary'>
                    <div class='modal-title' id="vendor_req_title">
                        <strong>Vendor Space Request</strong>
                    </div>
                    <button type='button' class='btn-close' data-bs-dismiss='modal' aria-label='Close'></button>
                </div>
                <div class='modal-body' style='padding: 4px; background-color: lightcyan;'>
                    <div class='container-fluid'>
                        <form id='vendor_req_form' action='javascript:void(0)'>
                            <div class='row p-0 bg-warning'>
                                <div class='col-sm-12 p-2'>
                                    Please make sure your profile contains a good description of what you will be vending and a link for our staff to see what
                                    you sell if at all possible.
                                </div>
                            </div>
                            <div class='row p-1'>
                                <div class='col-sm-auto p-0 pe-2'>
                                    <label for='vendor_req_price_id'>How many spaces are you requesting?</label>
                                </div>
                                <div class='col-sm-auto p-0'>
                                    <select name='vendor_req_price_id' id='vendor_req_price_id'>
                                        <option value='-1'>No Space Requested</option>
                                    </select>
                                </div>
                            </div>
                            <div class='row p-1 pt-4 pb-3'>
                                <div class='col-sm-12'>
                                    You will be able to identify people for the included memberships (if any) and purchase up to the allowed number of discounted memberships later, if your request is
                                    approved.
                                </div>
                            </div>
                            <div class='row p-0 bg-warning'>
                                <div class='col-sm-auto p-2'>Completing this application does not guarantee space.</div>
                            </div>
                        </form>
                    </div>
                </div>
                <div class='modal-footer'>
                    <button class='btn btn-sm btn-secondary' data-bs-dismiss='modal'>Cancel</button>
                    <button class='btn btn-sm btn-primary' id='vendor_req_btn' onClick="spaceReq(0, 0)">Request Vendor Space</button>
                </div>
            </div>
        </div>
    </div>
    <!-- invoice -->
    <div id='vendor_invoice' class='modal modal-xl fade' tabindex='-1' aria-labelledby='Vendor Invoice' aria-hidden='true' style='--bs-modal-width: 80%;'>
        <div class='modal-dialog'>
            <div class='modal-content'>
                <div class='modal-header bg-primary text-bg-primary'>
                    <div class='modal-title' id="vendor_invoice_title">
                        <strong>Vendor Invoice</strong>
                    </div>
                    <button type='button' class='btn-close' data-bs-dismiss='modal' aria-label='Close'></button>
                </div>
                <div class='modal-body' style='padding: 4px; background-color: lightcyan;'>
                    <div class="container-fluid form-floating">
                    <div class="row">
                        <div class="col-sm-12" id="vendor_inv_approved_for"></div>
                    </div>
                    <div class='row'>
                        <div class='col-sm-12' id='vendor_inv_included'></div>
                    </div>
                    <hr/>
                    <form id='vendor_invoice_form' class="form-floating" action='javascript:void(0);'>
                        <input type='hidden' name='vendor' id='vendor_inv_id' value='<?php echo $vendor; ?>'/>
                        <input type='hidden' name='item_purchased' id='vendor_inv_item_id'/>
                        <div class="row">
                            <div class="col-sm-12">
                                <strong>Vendor Information</strong>
                                <p>Please fill out this section with information on the vendor or store.  Changes made to the Vendor Information part of this form will update your profile.</p>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-sm-2">
                                <label for="vendor_inv_name">Name:</label>
                            </div>
                            <div class="col-sm-10 p-0">
                                <input class="form-control-sm" type='text' name='name' id='vendor_inv_name' value='<?php echo $info['name'];  ?>' size="64" required/>
                            </div>
                        </div>
                        <div class='row'>
                            <div class='col-sm-2'>
                                <label for='vendor_inv_email'>Email:</label>
                            </div>
                            <div class='col-sm-10 p-0'>
                                <input class='form-control-sm' type='text' name='email' id='vendor_inv_email' value='<?php echo $info['email']; ?>' size="64" required/>
                            </div>
                        </div>
                        <div class='row'>
                            <div class='col-sm-2'>
                                <label for='vendor_inv_addr'>Address:</label>
                            </div>
                            <div class='col-sm-10 p-0'>
                                <input class='form-control-sm' type='text' name='addr' id='vendor_inv_addr' value='<?php echo $info['addr']; ?>' size='64' required/>
                            </div>
                        </div>
                        <div class='row'>
                            <div class='col-sm-2'>
                                <label for='vendor_inv_addr2'>Company/ Addr2:</label>
                            </div>
                            <div class='col-sm-10 p-0'>
                                <input class='form-control-sm' type='text' name='addr2' id='vendor_inv_addr2' value='<?php echo $info['addr2']; ?>' size='64'/>
                            </div>
                        </div>
                        <div class='row'>
                            <div class='col-sm-2'>
                                <label for='vendor_inv_city'>City: </label>
                            </div>
                            <div class='col-sm-auto p-0 me-0'>
                                <input class='form-control-sm' id='vendor_inv_city' type='text' size='32' value='<?php echo $info['city']; ?>' name=' city' required/>
                            </div>
                            <div class='col-sm-auto ms-0 me-0 p-0 ps-2'>
                                <label for='vendor_inv_state'> State: </label>
                            </div>
                            <div class='col-sm-auto p-0 ms-0 me-0 ps-1'>
                                <input class='form-control-sm' id='vendor_inv_state' type='text' size='2' maxlength='2' value='<?php echo $info['state']; ?>'
                                       name='state' required/>
                            </div>
                            <div class='col-sm-auto ms-0 me-0 p-0 ps-2'>
                                <label for='vendor_inv_zip'> Zip: </label>
                            </div>
                            <div class='col-sm-auto p-0 ms-0 me-0 ps-1 pb-2'>
                                <input class='form-control-sm' id='vendor_inv_zip' type='text' size='11' maxlength='11' value='<?php echo $info['zip']; ?>' name='zip'
                                       required/>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-sm-2">
                                <label for="vendor_inv_taxid"><?php echo $vendor_conf['taxidlabel']; ?>:</label>
                            </div>
                            <div class="col-sm-10 p-0">
                                <input class='form-control-sm' type='text' name='taxid'/>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-sm-12"><?php echo $vendor_conf['taxidextra']; ?></div>
                        </div>
                        <div class="row mt-4">
                            <div class="col-sm-12">
                                Cost for Spaces $<span id='dealer_space_cost'></span>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-sm-2">
                                <label for="vendor_inv_requests">Special Requests:</label>
                            </div>
                            <div class="col-sm-10 p-0">
                                 <textarea class='form-control-sm' name='requests' cols="64" rows="5"></textarea>
                            </div>
                        </div>
                        <hr/>
                        <div id="vendor_inv_included_mbr"></div>
                        <div id="vendor_inv_additional_mbr"></div>
                        <div class="row">
                            <div class="col-sm-2">
                                Cost for Memberships:
                            </div>
                            <div class="col-sm-10 p-0">
                                $<span id='vendor_inv_mbr_cost'>0</span>
                            </div>
                        </div>
                        <hr/>
                        <div class="row">
                            <div class="col-sm-auto">
                                Total: <span id='vendor_inv_cost'></span>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-sm-12">
                                Payment Information:
                            </div>
                        </div>
                         <div class='row'>
                             <div class='col-sm-2'>
                                 <label for='cc_fname'>
                                     Name:
                                 </label>
                             </div>
                             <div class='col-sm-auto pe-0'>
                                 <input type='text' name='cc_fname' class='ccdata' id='cc_fname' required='required' placeholder='First Name' size="32" maxlength="32"/>
                             </div>
                             <div class='col-sm-auto'>
                                 <input type='text' name='cc_lname' id='cc_lname' required='required' class='ccdata' placeholder='Last Name' size='32' maxlength='32'/>
                             </div>
                         </div>
                         <div class='row'>
                             <div class='col-sm-2'>
                                 <label for='cc_street'>
                                     Street:
                                 </label>
                             </div>
                             <div class='col-sm-auto'>
                                 <input type='text' id='cc_street' required='required' name='cc_street' size='64' maxlength='64'/>
                             </div>
                         </div>
                         <div class='row'>
                             <div class='col-sm-2'>
                                 <label for='cc_city'>City:</label>
                             </div>
                             <div class='col-sm-auto'>
                                 <input type='text' id='cc_city' required='required' size='35' name='cc_city' maxlength='64'/>
                             </div>
                             <div class='col-sm-auto ps-0 pe-0'>
                                 <label for='cc_state'>State:</label>
                             </div>
                             <div class='col-sm-auto'>
                                 <input type='text' id='cc_state' size=2 maxlength="2" required='required' name='cc_state/'>
                             </div>
                             <div class='col-sm-auto ps-0 pe-0'>
                                 <label for='cc_zip'>Zip:</label>
                             </div>
                             <div class='col-sm-auto'>
                                 <input type='text' id='cc_zip' required='required' size=10 maxlength="10" name='cc_zip/'>
                             </div>
                         </div>
                         <div class='row'>
                             <div class='col-sm-2'>
                                 <label for='cc_country'>Country:</label>
                             </div>
                             <div class='col-sm-auto'>
                                  <select id='cc_country' required='required' name='cc_country' size=1>
                                      <?php echo $countryOptions; ?>
                                  </select>
                             </div>
                         </div>
                         <div class="row">
                             <div class="col-sm-2">
                                 <label for="cc_email">Email:</label>
                             </div>
                             <div class="col-sm-auto">
                                  <input type='email' id='cc_email' name='cc_email' size="35" maxlength="64"/>
                             </div>
                         </div>
                         <div class='row'>
                            <div class='col-sm-12'>
                                <?php if ($ini['test'] == 1) {
                                    ?>
                                    <h2 class='warn'>This won't charge your credit card, or do anything else.</h2>
                                    <?php
                                }
                                ?>
                                <br/>
                                We Accept<br/>
                                <img src='cards_accepted_64.png' alt="Visa, Mastercard, American Express, and Discover"/>
                            </div>
                        </div>
                        <hr/>
                        <div class="row">
                            <div class="col-sm-auto">
                                Please wait for the email, and don't click the "Purchase" button more than once.
                            </div>
                        </div>
                        <div class='row'>
                            <div class='col-sm-12'>
                                <?php draw_cc_html($cc, '--', 2); ?>
                                <input type='reset'/>
                            </div>
                        </div>
                    </form>
                </div>
                </div>
            </div>
        </div>
    </div>
    <!-- now for the top of the form -->
     <div class='container-fluid'>
        <div class='row p-1'>
            <div class='col-sm-12 p-0'>
                <h3>Welcome to the Portal Page for <?php echo $info['name']; ?></h3>
            </div>
        </div>
        <div class="row p-1">
            <div class="col-sm-auto p-0">
                <button class="btn btn-secondary" onclick='update_profile.show();'>View/Change your profile</button>
                <button class='btn btn-secondary' onclick='change_password.show();'>Change your password</button>
                <button class="btn btn-secondary" onclick="window.location='?logout';">Logout</button>
            </div>
        </div>
        <div class="row p-1 pt-4">
            <div class="col-sm-12 p-0">
                <h3>Vendor Spaces</h3>
            </div>
        </div>
<?php   if (count($spaces) > 1)  { ?>
        <div class="row p-1">
            <div class="col-sm-12 p-0"><?php
                echo $con['label']; ?> has multiple types of spaces for vendors. If you select a type for which you aren't qualified we will alert groups
                managing other spaces.
            </div>
        </div>
<?php   }
    foreach ($spaces AS $spacename => $spaceid) {
        $space = $space_list[$spaceid];
        if (array_key_exists($space['shortname'] . '_details', $vendor_conf)) {
            $description = $vendor_conf[$space['shortname'] . '_details'];
        } else {
            $description = $space['description'];
        }
        if (array_key_exists($spaceid, $vendor_spacelist)) {
            $vendor_space = $vendor_spacelist[$spaceid];
            $item_requested = $vendor_space['item_requested'];
        } else {
            $vendor_space = null;
            $item_requested = null;
        }

        // now the fixed text
        ?>
        <div class="row pt-4 p-1">
            <div class="col-sm-auto p-0">
                <h3><?php echo $space['name'];?></h3>
            </div>
        </div>
        <div class="row p-1">
            <div class="col-sm-12 p-0">
                <?php echo $description;?>
            </div>
        </div>
        <div class="row p-1 mt-2" id="<?php echo $space['shortname']; ?>_div">
            <div class="col-sm-auto p-0"><?php
        if ($vendor_space !== null) {
            if ($vendor_space['item_purchased']) {
                echo "You are registered for " . $vendor_space['purchased_description'] . "\n";
            } else if ($vendor_space['item_approved']) {
                ?>
                <button class="btn btn-primary"
                        onclick="openInvoice(<?php echo "'" . $space['id'] . "', " . $vendor_space['approved_units']; ?>)">
                    Pay <?php echo $space['name']; ?> Invoice</button> <?php
            } else if ($vendor_space['item_requested']) {
                echo 'Request pending authorization for ' . $vendor_space['requested_description'] . ".\n";?>
            </div>
            <div class="col-sm-auto ms-4 p-0"> <button class='btn btn-primary' onclick='openReq(<?php echo $spaceid . ", " . $vendor_space['item_requested'];?>);'>Change/Cancel  <?php echo $space['name']; ?> Space</button><?php
            } else {
                 ?>
            <button class="btn btn-primary" onclick='openReq(<?php echo $spaceid; ?>, 0);'>Request <?php echo $space['name']; ?> Space</button><?php
            }
        } else {
            ?>
            <button class="btn btn-primary" onclick='openReq(<?php echo $spaceid; ?>, 0);'>Request <?php echo $space['name']; ?> Space</button><?php
        }
        ?>
            </div>
        </div>
        <?php } ?>
        <div id='result_message' class='mt-4 p-2'></div>
    </div>
</body>
</html>

<?php
// drawChangePassword - make it common code to draw change password prompts
function drawChangePassword($title, $width, $drawbutton) {
    $html = '';
    if ($title != '') {
        $html = <<<EOH
    <div class='row'>
        <div class='col-sm-12'>$title</div>
    </div>
EOH;
        }
    $html .= <<<EOH
    <div class='container-fluid'>
        <form id='changepw' action='javascript:void(0)'>
        <div class='row'>
            <div class='col-sm-$width'>
                <label for='oldPw'>Old Password:</label>
            </div>
            <div class='col-sm-8'>
                <input type='password' id='oldPw' name='oldPassword' required/>
            </div>
        </div>
        <div class='row'>
            <div class='col-sm-$width'>
                <label for='pw'>new Password:</label>
            </div>
            <div class='col-sm-8'>
                <input type='password' id='pw' name='password' required/>
            </div>
        </div>
        <div class='row'>
            <div class='col-sm-$width'>
                <label for='pw2'>Re-enter Password:</label>
            </div>
            <div class='col-sm-8'>
                <input type='password' id='pw2' name='password2' required/>
            </div>
        </div>
EOH;
    if ($drawbutton) {
        $html .= <<<EOH
        <div class='row mt-2'>
            <div class='col-sm-$width'></div>
            <div class='col-sm-8'>
                <button class='btn btn-sm btn-primary' onClick='changePassword()'>Change Password</button>
            </div>
        </div>
        </form>
    </div>
    </body>
</html>
EOH;
    } else {
        $html .= <<<EOH
        </form>
    </div>
EOH;
    }
    echo $html;
}
