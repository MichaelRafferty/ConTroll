
<?php
// draw_login - draw the login/signup form
function draw_login($config_vars, $result_message = '') {
    $portalName = $config_vars['portalName'];
    $tabIndex = 10;
    ?>

 <!-- signin form (at body level) -->
    <script type='text/javascript'>
        var config = <?php echo json_encode($config_vars); ?>;
    </script>
    <div id='signin'>
        <?php outputCustomText('login/top' . $portalName); ?>
        <div class='container-fluid form-floating'>
            <div class='row mb-2'>
                <div class='col-sm-auto'>
                    <h1 class="h4">Please log in to continue to the <?php echo $portalName; ?> Portal.</h1>
                </div>
            </div>
            <form id='exhibitorSignin' method='POST'>
                <div class='row mt-1'>
                    <div class='col-sm-1'>
                        <label for='si_email'><span class='text-danger'>&bigstar;</span>Email: </label>
                    </div>
                    <div class='col-sm-auto'>
                        <input class='form-control-sm' type='email' name='si_email' id='si_email' size='40'
                               tabindex="<?php echo $tabIndex; $tabIndex += 2;?>" required/>
                    </div>
                </div>
                <div class='row mt-1'>
                    <div class='col-sm-1'>
                        <label for='si_password'><span class='text-danger'>&bigstar;</span>Password: </label>
                    </div>
                    <div class='col-sm-10'>
                        <?php echo eyepwField('si_password', 'si_password', 40, '', $tabIndex);
                            $tabIndex += 2;
                        ?>
                    </div>
                </div>
                <div class='row mt-2'>
                    <div class='col-sm-1'></div>
                    <div class='col-sm-auto'>
                        <input type='submit' class='btn btn-primary' value='Existing Account Sign-in'
                            tabindex="<?php echo $tabIndex; $tabIndex += 2;?>" />
                    </div>
                </div>
            </form>
            <?php  if (getConfValue('vendor', 'passkeyRpLevel') != 'd' && array_key_exists('HTTPS', $_SERVER) &&
                (isset($_SERVER['HTTPS']) ||  $_SERVER['HTTPS'] == 'on')) { ?>
            <div class='row mt-1'>
                <div class='col-sm-1'></div>
                <div class='col-sm-2' style="text-align: center">OR</div>
            </div>
            <div class='row mt-1'>
                <div class='col-sm-1'></div>
                <div class='col-sm-auto'>
                    <button class='btn btn-sm btn-primary' id="loginPasskeyBtn" onclick='loginWithPasskey();'>
                        <img src="lib/passkey.png">Login with Passkey
                    </button>
                </div>
            </div>
            <?php } ?>
        </div>
    </div>
    <div id='resetpw'>
        <div class='container-fluid'>
            <div class='row mt-4'>
                <div class='col-sm-auto'>
                    <button class='btn btn-secondary' onclick='resetPassword()' tabindex="<?php echo $tabIndex; $tabIndex += 2;?>">
                        Reset Forgotten Password
                    </button>
                </div>
            </div>
        </div>
    </div>
    <div class='container-fluid'>
        <div class='row mt-4'>
            <div class='col-sm-auto'>
                <button type="button" class="btn btn-sm btn-secondary" onclick="exhibitorProfile.profileModalOpen('register');"
                    tabindex="<?php echo $tabIndex; $tabIndex += 2;?>">
                    Sign Up for a New Account
                </button>
            </div>
        </div>
    </div>
    <?php outputCustomText('login/bottom' . $portalName); ?>
    <div class='container-fluid'>
        <div class='row'>
            <div class='col-sm-12 m-0 p-0'>
                <div id='result_message' class='mt-4 p-2'><?php echo $result_message; ?></div>
            </div>
        </div>
    </div>
    </body>
</html>
<?php
}

// draw_RegistratioModal - the modal for reg_control create and edit profile
function draw_registrationModal($portalType, $portalName, $con, $countryOptions, $tabStart=10 ) {
    $con = get_conf('con');
    $vendor_conf = get_conf('vendor');
    $tabIndex = $tabStart;
    ?>
    <!-- Registgration/Edit Registration Modal Popup -->
    <div id='profile' class="modal modal-xl fade" tabindex="-1" aria-labelledby="New Vendor" aria-hidden="true" style='--bs-modal-width: 80%;'>
        <div class="modal-dialog">
            <div class="modal-content">
                <div class='modal-header bg-primary text-bg-primary'>
                    <div class="modal-title">
                        <strong id="modalTitle">Unset Title for Profile Editing</strong>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" style="padding: 4px; background-color: lightcyan;">
                    <div class="container-fluid form-floating" style="background-color: lightcyan;">
                        <form id="exhibitorProfileForm" name="exhibitorProfileForm" action="javascript:void(0);" class="form-floating">
                            <input type="hidden" id='profileMode' name='profileMode' value="admin"/>
                            <input type="hidden" id='profileType' name='profileType' value="<?php echo $portalType; ?>"/>
                            <?php
                            if ($portalType == 'admin') {
                                echo  "<input type = 'hidden' id = 'exhibitorId' name = 'exhibitorId'/>\n";
                                echo  "<input type = 'hidden' id = 'exhibitorYearId' name = 'exhibitorYearId'/>\n";
                            }
                            ?>
                            <div class="row" <?php echo $portalType == 'admin' ? ' hidden' : ''; ?>>
                                <div class="col-sm-12" id="profileIntro">
                                    <p>This form creates an account on the <?php echo $con['conname'] . " $portalName" ?>
                                        Portal.</p>
                                </div>
                            </div>
                            <?php if ($portalType != 'admin') { ?>
                            <div class="row">
                                <div class="col-sm-12">
                                    <?php outputCustomText('profile/top'); outputCustomText('profile/top' . $portalName); ?>
                                </div>
                            </div>
                            <?php } ?>
                            <div class="row" id="creatingAccountMsg">
                                <div class="col-sm-12">Creating an account does not guarantee space.</div>
                            </div>
                            <!-- Business Info -->
                            <div class='row mt-2'>
                                <div class='col-sm-2'></div>
                                <div class='col-sm-auto p-0 ms-0 me-0'><h1 class="h4">Business Information</h1></div>
                            </div>
                             <?php outputCustomText('profile/bus' . $portalName); if ($portalType == 'artist' || $portalType == 'admin') { ?>
                                <div class="row mt-1">
                                    <div class='col-sm-2'>
                                        <label for='artistName'><span class='text-danger'>&bigstar;</span>Artist Name: </label>
                                    </div>
                                    <div class='col-sm-auto p-0 ms-0 me-0'>
                                        <input class='form-control-sm' type='text' name='artistName' id='artistName' maxlength='128' size='50'
                                               required placeholder='Artist Name' tabindex="<?php echo $tabIndex; $tabIndex += 2;?>"/>
                                    </div>
                                </div>
                            <?php } ?>
                            <div class='row mt-1'>
                                <div class="col-sm-2">
                                    <label for="exhibitorName"><span class='text-danger'>&bigstar;</span>Business Name: </label>
                                </div>
                                <div class="col-sm-auto p-0 ms-0 me-0">
                                    <input class="form-control-sm" type='text' name='exhibitorName' id="exhibitorName"
                                       maxlength="64" size="50" tabindex="<?php echo $tabIndex; $tabIndex += 2;?>" required
                                       placeholder="<?php echo $portalType == 'artist' ? 'Company or Artist Name' : 'Vendor, Dealer or Store name';?>"/><br/>
                                    <span class="small">This is the name that we will register your space under.</span>
                                </div>
                            </div>
                            <div class='row mt-1'>
                                <div class='col-sm-2'>
                                    <label for='exhibitorEmail'><span class='text-danger'>&bigstar;</span>Business Email: </label>
                                </div>
                                <div class='col-sm-auto p-0 ms-0 me-0'>
                                    <input class='form-control-sm' type='email' name='exhibitorEmail' id='exhibitorEmail' maxlength='254' size='50' required
                                        placeholder='email address for the business and overall login to the portal' tabindex="<?php echo $tabIndex; $tabIndex += 2;?>"
                                    />
                                </div>
                            </div>
                            <div class='row mt-1'>
                                <div class='col-sm-2'>
                                    <label for='exhibitorPhone'><span class='text-danger'>&bigstar;</span>Business Phone: </label>
                                </div>
                                <div class='col-sm-auto p-0 ms-0 me-0'>
                                    <input class='form-control-sm' type='text' name='exhibitorPhone' id='exhibitorPhone' maxlength='32' size='24' required
                                        placeholder='phone number for the business' tabindex="<?php echo $tabIndex; $tabIndex += 2;?>"
                                    />
                                </div>
                            </div>
                            <?php if ($portalType == 'vendor' && array_key_exists('taxidlabel', $vendor_conf) && $vendor_conf['taxidlabel'] != '') { ?>
                            <div class='row mt-1'>
                                <div class='col-sm-2'>
                                    <label for='exhibitorTaxid'><?php echo $vendor_conf['taxidlabel']; ?>:</label>
                                </div>
                                <div class="col-sm-10 p-0">
                                    <input class='form-control-sm' type='text' id="salesTaxId" name='salesTaxId'
                                           size=32 maxlength="32" tabindex="<?php echo $tabIndex; $tabIndex += 2;?>"
                                    />
                                </div>
                            </div>
                            <?php } ?>
                            <div class='row mt-1' id='passwordLine1'>
                                <div class='col-sm-2'>
                                    <label for='pw1'><span class='text-danger'>&bigstar;</span>Password: </label>
                                </div>
                                <div class='col-sm-10 p-0 ms-0 me-0'>
                                    <?php echo eyepwField('pw1', 'password', 40,'minimum of 8 characters', $tabIndex);
                                        $tabIndex += 2;
                                    ?>
                                </div>
                            </div>
                            <div class='row mt-1' id='passwordLine2'>
                                <div class='col-sm-2'>
                                    <label for='pw2'><span class='text-danger'>&bigstar;</span>Confirm Password: </label>
                                </div>
                                <div class='col-sm-10 p-0 ms-0 me-0'>
                                    <?php echo eyepwField('pw2', 'password2', 40,'retype password', $tabIndex);
                                        $tabIndex += 2;
                                    ?>
                                </div>
                            </div>
                            <div class='row mt-1'>
                                <div class='col-sm-2'>
                                    <label for='website'>Website: </label>
                                </div>
                                <div class='col-sm-auto p-0 ms-0 me-0'>
                                    <input class='form-control-sm' id='website' type='text' size='64' name='website'
                                           tabindex="<?php echo $tabIndex; $tabIndex += 2;?>"
                                           placeholder='Please enter your web, Etsy or social media site, or other appropriate URL.'
                                    />
                                </div>
                            </div>
                             <?php outputCustomText('profile/web' . $portalName); ?>
                            <div class='row mt-1'>
                                <div class='col-sm-2'>
                                    <label for='description'><span class='text-danger'>&bigstar;</span>Description: </label>
                                </div>
                                <div class='col-sm-auto p-0 ms-0 me-0'>
                                    <textarea class='form-control-sm' id='description' name='description' rows=5 cols=64 required
                                              placeholder="Tell us enough about yourself/company so that we can make a decision on including you."
                                              tabindex="<?php echo $tabIndex; $tabIndex += 2;?>">
                                    </textarea>
                                </div>
                            </div>
                            <?php if ($portalType == 'admin') { ?>
                            <div class='row mt-1'>
                                <div class='col-sm-2'>
                                    <label for='exhNotes'>Exhibitor Notes:</label>
                                </div>
                                <div class='col-sm-9 p-0 ms-0 me-0'>
                                    <textarea class='form-control-sm' id='exhNotes' name='exhNotes' rows=5 cols=100
                                              placeholder='Administrators Notes for this Exhibitor'
                                              tabindex="<?php echo $tabIndex;
                                                  $tabIndex += 2; ?>">
                                    </textarea>
                                </div>
                            </div>
                            <?php } ?>
                            <div class='row mt-1'>
                                <div class='col-sm-2'>
                                    <label for='publicity'><span class='text-danger'>&bigstar;</span>Publicity: </label>
                                </div>
                                <div class='col-sm-9 p-0 ms-0 me-0'>
                                    <select name='publicity' id='publicity' tabindex="<?php echo $tabIndex; $tabIndex += 2;?>"
                                            style='min-width:100% !important;'>
                                        <option value='1' selected>Yes, You may use my information to publicize my attendence
                                            at <?php echo $con['conname']; ?></option>
                                        <option value='0'>No, You may not use my information to publicize my attendence
                                            at <?php echo $con['conname']; ?></option>
                                    </select>
                                </div>
                            </div>
                            <?php if ($portalType == 'artist' || $portalType == 'admin') { /* TODO change this to 'mail-in allowed' */ ?>
                                <div class='row mt-1'>
                                    <div class='col-sm-2'>
                                        <label for='mailin'><span class='text-danger'>&bigstar;</span>Are you requesting a mail-in space: </label>
                                    </div>
                                    <div class='col-sm-9 p-0 ms-0 me-0'>
                                        <select name='mailin' id='mailin' tabindex="<?php echo $tabIndex; $tabIndex += 2;?>"
                                                style='min-width:100% !important;'>
                                            <option value='N'>No, (Not Mail In), On-site or Using Agent to transport and hang/collect art</option>
                                            <option value='Y'>Yes, (Mail In), if shipping art, it will be returned to the Shipping Address</option>
                                        </select>
                                    </div>
                                </div>
                            <?php } else { ?>
                            <input type="hidden" name="mailin" id="mailinN" value="N">
                            <?php } ?>
                            <!-- Vendor/Artist Address -->
                            <div class='row mt-2'>
                                <div class='col-sm-2'></div>
                                <div class='col-sm-auto p-0 ms-0 me-0'><h1 class="h4"><?php echo $portalName; ?> Address</h1></div>
                            </div>
                            <?php outputCustomText('profile/add' . $portalName); ?>
                            <div class="row mt-1">
                                <div class="col-sm-2">
                                    <label for="addr"><span class='text-danger'>&bigstar;</span>Address </label>
                                </div>
                                <div class="col-sm-auto p-0 ms-0 me-0">
                                    <input class="form-control-sm" id='addr' type='text' size="64" name='addr'
                                           required placeholder="Street Address"
                                           tabindex="<?php echo $tabIndex; $tabIndex += 2;?>"
                                    />
                                </div>
                            </div>
                            <div class="row mt-1">
                                <div class="col-sm-2"></div>
                                <div class="col-sm-auto p-0 ms-0 me-0">
                                    <input class="form-control-sm" id='addr2' type='text' size="64" name='addr2'
                                           placeholder="second line of address if needed"
                                           tabindex="<?php echo $tabIndex; $tabIndex += 2;?>"
                                    />
                                </div>
                            </div>
                            <div class="row mt-1">
                                <div class="col-sm-2">
                                    <label for="city"><span class='text-danger'>&bigstar;</span>City: </label>
                                </div>
                                <div class="col-sm-auto p-0 ms-0 me-0">
                                    <input class="form-control-sm" id='city' type='text' size="32" maxlength="32" name='city' required
                                           tabindex="<?php echo $tabIndex; $tabIndex += 2;?>"
                                    />
                                </div>
                                <div class="col-sm-auto ms-0 me-0 p-0 ps-2">
                                    <label for="state"><span class='text-danger'>&bigstar;</span>State: </label>
                                </div>
                                <div class="col-sm-auto p-0 ms-0 me-0 ps-1">
                                    <input class="form-control-sm" id='state' type='text' size="10" maxlength="16" name='state' required
                                           tabindex="<?php echo $tabIndex; $tabIndex += 2;?>"
                                    />
                                </div>
                                <div class="col-sm-auto ms-0 me-0 p-0 ps-2">
                                    <label for="zip"><span class='text-danger'>&bigstar;</span>Zip: </label>
                                </div>
                                <div class="col-sm-auto p-0 ms-0 me-0 ps-1 pb-2">
                                    <input class="form-control-sm" id='zip' type='text' size="11" maxlength="11" name='zip' required
                                           placeholder="Postal Code" tabindex="<?php echo $tabIndex; $tabIndex += 2;?>"
                                    />
                                </div>
                            </div>
                            <div class='row mt-1'>
                                <div class='col-sm-2'>
                                    <label for='country'> Country </label>
                                </div>
                                <div class='col-sm-auto p-0 ms-0 me-0 ps-1 pb-2'>
                                    <select id='country' name='country' tabindex="<?php echo $tabIndex; $tabIndex += 2;?>">
                                        <?php echo $countryOptions; ?>
                                    </select>
                                </div>
                            </div>
                            <!-- Contact Info -->
                            <div class='row mt-2'>
                                <div class='col-sm-2'></div>
                                <div class='col-sm-auto p-0 ms-0 me-0'><h1 class="h4">Primary Contact</h1></div>
                            </div>
                            <?php outputCustomText('profile/contact'); ?>
                            <div class='row mt-1'>
                                <div class='col-sm-2'>
                                    <label for='contactName'><span class='text-danger'>&bigstar;</span>Contact Name: </label>
                                </div>
                                <div class='col-sm-auto p-0 ms-0 me-0'>
                                    <input class='form-control-sm' type='text' name='contactName' id='contactName'
                                           maxlength='64' size='50' required placeholder='primary contact name'
                                           tabindex="<?php echo $tabIndex; $tabIndex += 2;?>"
                                    />
                                </div>
                            </div>
                            <div class='row mt-1'>
                                <div class='col-sm-2'>
                                    <label for='contactEmail'><span class='text-danger'>&bigstar;</span>Email/Login: </label>
                                </div>
                                <div class='col-sm-auto p-0 ms-0 me-0'>
                                    <input class='form-control-sm' type='email' name='contactEmail' id='contactEmail' maxlength='254' size='50'
                                           required placeholder='email address for Contact and alternate login to the portal'
                                           tabindex="<?php echo $tabIndex; $tabIndex += 2;?>"
                                    />
                                </div>
                            </div>
                            <div class='row mt-1'>
                                <div class='col-sm-2'>
                                    <label for='contactPhone'><span class='text-danger'>&bigstar;</span>Contact Phone: </label>
                                </div>
                                <div class='col-sm-auto p-0 ms-0 me-0'>
                                    <input class='form-control-sm' type='text' name='contactPhone' id='contactPhone' maxlength='32' size='24' required
                                           placeholder="contact's phone number" tabindex="<?php echo $tabIndex; $tabIndex += 2;?>"
                                    />
                                </div>
                            </div>
                            <?php if ($portalType == 'admin') { ?>
                                <div class='row mt-1'>
                                    <div class='col-sm-2'>
                                        <label for='contactNotes'>Contactor Notes:<br/>or<br/>Notes for this year</label>
                                    </div>
                                    <div class='col-sm-9 p-0 ms-0 me-0'>
                                    <textarea class='form-control-sm' id='contactNotes' name='contactNotes' rows=5 cols=100
                                              placeholder='This Years Notes for this Contact/Exhibitor'
                                              tabindex="<?php echo $tabIndex;
                                                  $tabIndex += 2; ?>">
                                    </textarea>
                                    </div>
                                </div>
                            <?php } ?>
                            <div class='row mt-1' id='cpasswordLine1'>
                                <div class='col-sm-2'>
                                    <label for='cpw1'><span class='text-danger'>&bigstar;</span>Contact Password: </label>
                                </div>
                                <div class='col-sm-10 p-0 ms-0 me-0'>
                                    <?php echo eyepwField('cpw1', 'cpassword', 40,'minimum of 8 characters', $tabIndex);
                                        $tabIndex += 2;
                                    ?>
                                </div>
                            </div>
                            <div class='row mt-1' id='cpasswordLine2'>
                                <div class='col-sm-2'>
                                    <label for='cpw2'><span class='text-danger'>&bigstar;</span>Confirm Password: </label>
                                </div>
                                <div class='col-sm-10 p-0 ms-0 me-0'>
                                    <?php echo eyepwField('cpw2', 'cpassword2', 40,'retype the contact password', $tabIndex);
                                        $tabIndex += 2;
                                    ?>
                                </div>
                            </div>
                            <!-- Shipping Address (artist only) -->
                            <?php if ($portalType == 'artist' || $portalType == 'admin') { ?>
                            <div class='row mt-4'>
                                <div class='col-sm-2'></div>
                                <div class='col-sm-auto p-0 ms-0 me-0'><h1 class="h4">Shipping Address</h1></div>
                                <div class='col-sm-auto p-0 ms-4 me-0'>
                                    <button class='btn btn-sm btn-primary' type="button" tabindex="<?php echo $tabIndex; $tabIndex += 2;?>"
                                            onclick='exhibitorProfile.copyAddressToShipTo()'>
                                        Copy <?php echo $portalName; ?> Address to Shipping Address
                                    </button>
                                </div>
                            </div>
                            <?php outputCustomText('profile/shipping'); ?>
                            <div class='row mt-1'>
                                <div class='col-sm-2'>
                                    <label for='shipCompany'><span class='text-danger'>&bigstar;</span>Company </label>
                                </div>
                                <div class='col-sm-auto p-0 ms-0 me-0'>
                                    <input class='form-control-sm' id='shipCompany' type='text' size='64' name='shipCompany' required
                                           placeholder='Company Name' tabindex="<?php echo $tabIndex; $tabIndex += 2;?>"
                                    />
                                </div>
                            </div>
                            <div class='row mt-1'>
                                <div class='col-sm-2'>
                                    <label for='shipAddr'><span class='text-danger'>&bigstar;</span>Address </label>
                                </div>
                                <div class='col-sm-auto p-0 ms-0 me-0'>
                                    <input class='form-control-sm' id='shipAddr' type='text' size='64' name='shipAddr' required
                                           placeholder='Street Address' tabindex="<?php echo $tabIndex; $tabIndex += 2;?>"
                                    />
                                </div>
                            </div>
                            <div class='row mt-1'>
                                <div class='col-sm-2'></div>
                                <div class='col-sm-auto p-0 ms-0 me-0'>
                                    <input class='form-control-sm' id='shipAddr2' type='text' size='64' name='shipAddr2'
                                           placeholder='2nd line of address if needed' tabindex="<?php echo $tabIndex; $tabIndex += 2;?>"
                                    />
                                </div>
                            </div>
                            <div class='row mt-1'>
                                <div class='col-sm-2'>
                                    <label for='shipCity'><span class='text-danger'>&bigstar;</span>City: </label>
                                </div>
                                <div class='col-sm-auto p-0 ms-0 me-0'>
                                    <input class='form-control-sm' id='shipCity' type='text' size='32' maxlength='32' name='shipCity'
                                           required tabindex="<?php echo $tabIndex; $tabIndex += 2;?>"
                                    />
                                </div>
                                <div class='col-sm-auto ms-0 me-0 p-0 ps-2'>
                                    <label for='shipState'><span class='text-danger'>&bigstar;</span>State: </label>
                                </div>
                                <div class='col-sm-auto p-0 ms-0 me-0 ps-1'>
                                    <input class='form-control-sm' id='shipState' type='text' size='10' maxlength='16' name='shipState'
                                           required tabindex="<?php echo $tabIndex; $tabIndex += 2;?>"
                                    />
                                </div>
                                <div class='col-sm-auto ms-0 me-0 p-0 ps-2'>
                                    <label for='shipZip'><span class='text-danger'>&bigstar;</span>Zip: </label>
                                </div>
                                <div class='col-sm-auto p-0 ms-0 me-0 ps-1 pb-2'>
                                    <input class='form-control-sm' id='shipZip' type='text' size='11' maxlength='11' name='shipZip' required
                                           placeholder='Postal Code' tabindex="<?php echo $tabIndex; $tabIndex += 2;?>"
                                    />
                                </div>
                            </div>
                            <div class='row mt-1'>
                                <div class='col-sm-2'>
                                    <label for='shipCountry'> Country </label>
                                </div>
                                <div class='col-sm-auto p-0 ms-0 me-0 ps-1 pb-2'>
                                    <select id='shipCountry' name='shipCountry' tabindex="<?php echo $tabIndex; $tabIndex += 2;?>">
                                        <?php echo $countryOptions; ?>
                                    </select>
                                </div>
                            </div>
                            <?php } ?>
                        </form>
                    </div>
                    <div id='au_result_message' class='mt-4 p-2'></div>
                </div>
                <div class="modal-footer">
                    <button class='btn btn-sm btn-secondary' data-bs-dismiss='modal' tabindex="<?php echo $tabIndex; $tabIndex += 2;?>">
                        Cancel
                    </button>
                    <button class='btn btn-sm btn-primary' id='profileSubmitBtn' tabindex="<?php echo $tabIndex; $tabIndex += 2;?>"
                            onClick="exhibitorProfile.submitProfile('<?php echo $portalType; ?>')">Admin</button>
                </div>
            </div>
        </div>
    </div>
    <?php
    }

// draw_RegistratioModal - the modal for exhibitor signup in the vendor subsystem
function draw_signupModal($portalType, $portalName, $con, $countryOptions, $tabStart = 10) {
    $con = get_conf('con');
    $vendor_conf = get_conf('vendor');
    $tabIndex = $tabStart;
    ?>
    <!-- Registgration/Edit Registration Modal Popup -->
    <div id='profile' class='modal modal-xl fade' tabindex='-1' aria-labelledby='New Vendor' aria-hidden='true' style='--bs-modal-width: 80%;'>
        <div class="modal-dialog">
            <div class="modal-content">
                <div class='modal-header bg-primary text-bg-primary'>
                    <div class="modal-title">
                        <strong id='modalTitle'>Unset Title for Profile Editing</strong>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" style="padding: 4px; background-color: lightcyan;">
                    <?php if ($portalType != 'admin') outputCustomText('signup/top'); ?>
                    <div class="container-fluid form-floating" style="background-color: lightcyan;">
                        <form id='exhibitorProfileForm' name='exhibitorProfileForm' action='javascript:void(0);' class='form-floating'>
                            <input type="hidden" id='profileMode' name='profileMode' value="admin"/>
                            <input type="hidden" id='profileType' name='profileType' value="<?php echo $portalType; ?>"/>
                            <?php
                            if ($portalType == 'admin') {
                                echo "<input type = 'hidden' id = 'exhibitorId' name = 'exhibitorId'/>\n";
                                echo "<input type = 'hidden' id = 'exhibitorYearId' name = 'exhibitorYearId'/>\n";
                            }
                            ?>
                            <div class="row" <?php echo $portalType == 'admin' ? ' hidden' : ''; ?>>
                                <div class="col-sm-12" id="profileIntro">
                                    <p>This form creates an account on the <?php echo $con['conname'] . " $portalName" ?>
                                        Portal.</p>
                                </div>
                            </div>
                            <div class="row" id="creatingAccountMsg">
                                <div class="col-sm-12">Creating an account does not guarantee space.</div>
                            </div>
                            <!-- Page 1 - Are you a mail in Artist - display only if artist -->
                            <div id="page1">
                                <?php if ($portalType != 'admin') { ?>
                                    <div class="row mt-1">
                                        <div class="col-sm-12">
                                            <?php outputCustomText('signup/pg1'. $portalName);?>
                                        </div>
                                    </div>
                                <?php } ?>
                                <?php if ($portalType == 'artist') {
                                    // cannot do check of mailinallowed flag, as we don't know which space they are going to reserve
                                ?>
                                <div class='row mt-3'>
                                    <div class='col-sm-2'>
                                        <label for='artistName'><span class='text-danger'>&bigstar;</span>Artist Name: </label>
                                    </div>
                                    <div class='col-sm-auto p-0 ms-0 me-0'>
                                        <input class='form-control-sm' type='text' name='artistName' id='artistName' maxlength='128' size='50'
                                               required placeholder='Artist Name' tabindex="<?php echo $tabIndex; $tabIndex += 2;?>"/>
                                    </div>
                                </div>
                                <div class='row mt-3'>
                                    <div class='col-sm-2'></div>
                                    <div class='col-sm-auto p-0 ms-0 me-0'><h1 class="h4">Are You a Mail In Artist</h1></div>
                                </div>
                                <div class='row mt-1'>
                                    <div class='col-sm-2'>
                                        <label for='mailin'><span class='text-danger'>&bigstar;</span>Are you requesting a mail-in space: </label>
                                    </div>
                                    <div class='col-sm-9 p-0 ms-0 me-0'>
                                        <select name='mailin' id='mailin' tabindex="<?php echo $tabIndex; $tabIndex += 2;?>"
                                                style="min-width:100% !important;">
                                            <option value="">--Choose Yes or No--</option>
                                            <option value="N">No, (Not Mail In), On-site or Using Agent to transport and hang/collect art</option>
                                            <option value="Y">Yes, (Mail In), if shipping art, it will be returned to the Shipping Address</option>
                                        </select>
                                    </div>
                                </div>
                                <?php } else { ?>
                                <input type="hidden" name="mailin" id="mailinN" value="N">
                                <?php } ?>
                                <div class='row mt-1'>
                                    <div class='col-sm-2'>
                                        <label for='publicity'><span class='text-danger'>&bigstar;</span>Publicity: </label>
                                    </div>
                                    <div class='col-sm-9 p-0 ms-0 me-0'>
                                        <select name='publicity' id='publicity' tabindex="<?php echo $tabIndex; $tabIndex += 2;?>"
                                                style="min-width:100% !important;">
                                            <option value='1' selected>Yes, You may use my information to publicize my attendence at <?php echo $con['conname']; ?></option>
                                            <option value='0'>No, You may not use my information to publicize my attendence at <?php echo $con['conname']; ?></option>
                                        </select>
                                    </div>
                                </div>
                                <div class="row mt-4">
                                    <div class='col-sm-2'></div>
                                    <div class="col-sm-auto p-0 ms-0 me-0"><strong>Signup page 1 of 4, please complete all 4 pages, register button is on page 4.</strong></div>
                                </div>
                            </div>
                            <div id="page2" hidden>
                                <?php if ($portalType != 'admin') { ?>
                                    <div class="row mt-1">
                                        <div class="col-sm-12">
                                            <?php outputCustomText('signup/pg2'. $portalName);?>
                                        </div>
                                    </div>
                                <?php } ?>
                            <!-- Business Info -->
                                <div class='row mt-2'>
                                    <div class='col-sm-2'></div>
                                    <div class='col-sm-auto p-0 ms-0 me-0'><h1 class="h4">Business Information</h1></div>
                                    <?php if ($portalType == 'artist') { ?>
                                        <div class='col-sm-auto p-0 ms-4 me-0'>
                                            <button class='btn btn-sm btn-primary' type='button' id="copyArtistName"
                                                    tabindex="<?php echo $tabIndex; $tabIndex += 2;?>"
                                                    onclick='exhibitorProfile.copyArtistNametoBusinessName()'>
                                                Copy <?php echo $portalName; ?> Name to Business Name
                                            </button>
                                        </div>
                                    <?php } ?>
                                </div>
                                <div class="row mt-1">
                                    <div class="col-sm-2">
                                        <label for="exhibitorName"><span class='text-danger'>&bigstar;</span>Name: </label>
                                    </div>
                                    <div class="col-sm-auto p-0 ms-0 me-0">
                                        <input class="form-control-sm" type='text' name='exhibitorName' id="exhibitorName" maxlength="64" size="50"
                                               tabindex="<?php echo $tabIndex; $tabIndex += 2;?>" required
                                               placeholder="<?php echo $portalType == 'artist' ? 'Company or Artist Name' : 'Vendor, Dealer or Store name'; ?>"/><br/>
                                        <span class="small">This is the name that we will register your space under.</span>
                                    </div>
                                </div>
                                <div class='row mt-1'>
                                    <div class='col-sm-2'>
                                        <label for='exhibitorEmail'><span class='text-danger'>&bigstar;</span>Business Email: </label>
                                    </div>
                                    <div class='col-sm-auto p-0 ms-0 me-0'>
                                        <input class='form-control-sm' type='email' name='exhibitorEmail' id='exhibitorEmail' maxlength='254' size='50'
                                               tabindex="<?php echo $tabIndex; $tabIndex += 2;?>" required
                                               placeholder='email address for the business and overall login to the portal'
                                        />
                                    </div>
                                </div>
                                <div class='row mt-1'>
                                    <div class='col-sm-2'>
                                        <label for='exhibitorPhone'><span class='text-danger'>&bigstar;</span>Business Phone: </label>
                                    </div>
                                    <div class='col-sm-auto p-0 ms-0 me-0'>
                                        <input class='form-control-sm' type='text' name='exhibitorPhone' id='exhibitorPhone' maxlength='32' size='24'
                                               tabindex="<?php echo $tabIndex; $tabIndex += 2;?>" required
                                               placeholder='phone number for the business'
                                        />
                                    </div>
                                </div>
                                <?php if ($portalType == 'vendor' && array_key_exists('taxidlabel', $vendor_conf) && $vendor_conf['taxidlabel'] != '') { ?>
                                    <div class='row mt-1'>
                                        <div class='col-sm-2'>
                                            <label for='exhibitorTaxid'><?php echo $vendor_conf['taxidlabel']; ?>:</label>
                                        </div>
                                        <div class="col-sm-10 p-0">
                                            <input class='form-control-sm' type='text' id="salesTaxId" name='salesTaxId'
                                                   size=32 maxlength="32" tabindex="<?php echo $tabIndex; $tabIndex += 2;?>"
                                            />
                                        </div>
                                    </div>
                                <?php } ?>
                                <div class='row mt-1' id='passwordLine1'>
                                    <div class='col-sm-2'>
                                        <label for='pw1'><span class='text-danger'>&bigstar;</span>Password: </label>
                                    </div>
                                    <div class='col-sm-10 p-0 ms-0 me-0'>
                                        <?php echo eyepwField('pw1', 'password', 40,'minimum of 8 characters', $tabIndex);
                                            $tabIndex += 2;
                                        ?>
                                    </div>
                                </div>
                                <div class='row mt-1' id='passwordLine2'>
                                    <div class='col-sm-2'>
                                        <label for='pw2'><span class='text-danger'>&bigstar;</span>Confirm Password: </label>
                                    </div>
                                    <div class='col-sm-10 p-0 ms-0 me-0'>
                                        <?php echo eyepwField('pw2', 'password2', 40,'retype the exhibitor password', $tabIndex);
                                            $tabIndex += 2;
                                        ?>
                                    </div>
                                </div>
                                <div class='row mt-1'>
                                    <div class='col-sm-2'>
                                        <label for='website'>Website: </label>
                                    </div>
                                    <div class='col-sm-auto p-0 ms-0 me-0'>
                                        <input class='form-control-sm' id='website' type='text' size='64' name='website'
                                               placeholder='Please enter your web, Etsy or social media site, or other appropriate URL.'
                                               tabindex="<?php echo $tabIndex; $tabIndex += 2;?>"
                                        />
                                    </div>
                                </div>
                                <div class='row mt-1'>
                                    <div class='col-sm-2'>
                                        <label for='description'><span class='text-danger'>&bigstar;</span>Description: </label>
                                    </div>
                                    <div class='col-sm-auto p-0 ms-0 me-0'>
                                        <textarea class='form-control-sm' id='description' name='description' rows=5 cols=64 required
                                                  tabindex="<?php echo $tabIndex; $tabIndex += 2;?>"></textarea>
                                    </div>
                                </div>
                                <?php if ($portalType == 'admin') { ?>
                                    <div class='row mt-1'>
                                        <div class='col-sm-2'>
                                            <label for='exhNotes'>Exhibitor Notes:</label>
                                        </div>
                                        <div class='col-sm-9 p-0 ms-0 me-0'>
                                    <textarea class='form-control-sm' id='exhNotes' name='exhNotes' rows=5 cols=100
                                              placeholder='Administrators Notes for this Exhibitor'
                                              tabindex="<?php echo $tabIndex;
                                                  $tabIndex += 2; ?>">
                                    </textarea>
                                        </div>
                                    </div>
                                <?php } ?>
                                <div class='row mt-4'>
                                    <div class='col-sm-2'></div>
                                    <div class='col-sm-auto p-0 ms-0 me-0'><strong>Signup page 2 of 4, please complete all 4 pages, register button is on page
                                            4.</strong></div>
                                </div>
                            </div>
                            <div id='page3' hidden>
                                <?php if ($portalType != 'admin') { ?>
                                    <div class="row mt-1">
                                        <div class="col-sm-12">
                                            <?php outputCustomText('signup/pg3'. $portalName);?>
                                        </div>
                                    </div>
                                <?php } ?>
                                <!-- Contact Info -->
                                <div class='row mt-2'>
                                    <div class='col-sm-2'></div>
                                    <div class='col-sm-auto p-0 ms-0 me-0'><h1 class="h4">Primary Contact</h1></div>
                                    <div class='col-sm-auto p-0 ms-4 me-0'>
                                        <button class='btn btn-sm btn-primary' type='button' id="copyToContact"
                                                tabindex="<?php echo $tabIndex; $tabIndex += 2;?>"
                                                onclick='exhibitorProfile.copyBusToContactName()'>
                                            Copy <?php echo $portalName; ?> Name/Info to Contact Name/Info
                                        </button>
                                    </div>
                                </div>
                                <div class='row mt-1'>
                                    <div class='col-sm-2'>
                                        <label for='contactName'><span class='text-danger'>&bigstar;</span>Contact Name: </label>
                                    </div>
                                    <div class='col-sm-auto p-0 ms-0 me-0'>
                                        <input class='form-control-sm' type='text' name='contactName' id='contactName' maxlength='64' size='50'
                                               tabindex="<?php echo $tabIndex; $tabIndex += 2;?>" required
                                               placeholder='primary contact name'
                                        />
                                    </div>
                                </div>
                                <div class='row mt-1'>
                                    <div class='col-sm-2'>
                                        <label for='contactEmail'><span class='text-danger'>&bigstar;</span>Email/Login: </label>
                                    </div>
                                    <div class='col-sm-auto p-0 ms-0 me-0'>
                                        <input class='form-control-sm' type='email' name='contactEmail' id='contactEmail' maxlength='254' size='50' required
                                               placeholder='email address for Contact and alternate login to the portal'
                                               tabindex="<?php echo $tabIndex; $tabIndex += 2;?>"
                                        />
                                    </div>
                                </div>
                                <div class='row mt-1'>
                                    <div class='col-sm-2'>
                                        <label for='contactPhone'><span class='text-danger'>&bigstar;</span>Contact Phone: </label>
                                    </div>
                                    <div class='col-sm-auto p-0 ms-0 me-0'>
                                        <input class='form-control-sm' type='text' name='contactPhone' id='contactPhone' maxlength='32' size='24' required
                                               placeholder="contact's phone number" tabindex="<?php echo $tabIndex; $tabIndex += 2;?>"
                                        '/>
                                    </div>
                                </div>
                                <?php if ($portalType == 'admin') { ?>
                                    <div class='row mt-1'>
                                        <div class='col-sm-2'>
                                            <label for='contactNotes'>Contactor Notes:<br/>or<br/>Notes for this year</label>
                                        </div>
                                        <div class='col-sm-9 p-0 ms-0 me-0'>
                                    <textarea class='form-control-sm' id='contactNotes' name='contactNotes' rows=5 cols=100
                                              placeholder='This Years Notes for this Contact/Exhibitor'
                                              tabindex="<?php echo $tabIndex;
                                                  $tabIndex += 2; ?>">
                                    </textarea>
                                        </div>
                                    </div>
                                <?php } ?>
                                <div class='row mt-1' id='cpasswordLine1'>
                                    <div class='col-sm-2'>
                                        <label for='cpw1'><span class='text-danger'>&bigstar;</span>Contact Password: </label>
                                    </div>
                                    <div class='col-sm-10 p-0 ms-0 me-0'>
                                        <?php echo eyepwField('cpw1', 'cpassword', 40,'minimum of 8 characters', $tabIndex);
                                            $tabIndex += 2;
                                        ?>
                                    </div>
                                </div>
                                <div class='row mt-1' id='cpasswordLine2'>
                                    <div class='col-sm-2'>
                                        <label for='cpw2'><span class='text-danger'>&bigstar;</span>Confirm Password: </label>
                                    </div>
                                    <div class='col-sm-10 p-0 ms-0 me-0'>
                                        <?php echo eyepwField('cpw2', 'cpassword2', 40,'retype the contact password', $tabIndex);
                                            $tabIndex += 2;
                                        ?>
                                    </div>
                                </div>
                                <div class='row mt-4'>
                                    <div class='col-sm-2'></div>
                                    <div class='col-sm-auto p-0 ms-0 me-0'><strong>Signup page 3 of 4, please complete all 4 pages, register button is on page
                                            4.</strong></div>
                                </div>
                            </div>
                            <div id="page4" hidden>
                                <?php if ($portalType != 'admin') { ?>
                                    <div class="row mt-1">
                                        <div class="col-sm-12">
                                            <?php outputCustomText('signup/pg4'. $portalName);?>
                                        </div>
                                    </div>
                                <?php } ?>
                                <!-- Vendor/Artist Address -->
                                <div class='row mt-2'>
                                    <div class='col-sm-2'></div>
                                    <div class='col-sm-auto p-0 ms-0 me-0'><h1 class="h4"><?php echo $portalName; ?> Address</h1></div>
                                </div>
                                <div class="row mt-1">
                                    <div class="col-sm-2">
                                        <label for="addr"><span class='text-danger'>&bigstar;</span>Address </label>
                                    </div>
                                    <div class="col-sm-auto p-0 ms-0 me-0">
                                        <input class="form-control-sm" id='addr' type='text' size="64" name='addr' required
                                               placeholder="Street Address"
                                               tabindex="<?php echo $tabIndex; $tabIndex += 2;?>"
                                        />
                                    </div>
                                </div>
                                <div class="row mt-1">
                                    <div class="col-sm-2"></div>
                                    <div class="col-sm-auto p-0 ms-0 me-0">
                                        <input class="form-control-sm" id='addr2' type='text' size="64" name='addr2'
                                               placeholder="second line of address if needed"
                                               tabindex="<?php echo $tabIndex; $tabIndex += 2;?>"
                                        />
                                    </div>
                                </div>
                                <div class="row mt-1">
                                    <div class="col-sm-2">
                                        <label for="city"><span class='text-danger'>&bigstar;</span>City: </label>
                                    </div>
                                    <div class="col-sm-auto p-0 ms-0 me-0">
                                        <input class="form-control-sm" id='city' type='text' size="32" maxlength="32" name='city' required
                                               tabindex="<?php echo $tabIndex; $tabIndex += 2;?>"
                                        />
                                    </div>
                                    <div class="col-sm-auto ms-0 me-0 p-0 ps-2">
                                        <label for="state"><span class='text-danger'>&bigstar;</span>State: </label>
                                    </div>
                                    <div class="col-sm-auto p-0 ms-0 me-0 ps-1">
                                        <input class="form-control-sm" id='state' type='text' size="10" maxlength="16" name='state' required
                                               tabindex="<?php echo $tabIndex; $tabIndex += 2;?>"
                                        />
                                    </div>
                                    <div class="col-sm-auto ms-0 me-0 p-0 ps-2">
                                        <label for="zip"><span class='text-danger'>&bigstar;</span>Zip: </label>
                                    </div>
                                    <div class="col-sm-auto p-0 ms-0 me-0 ps-1 pb-2">
                                        <input class="form-control-sm" id='zip' type='text' size="11" maxlength="11" name='zip' required
                                               placeholder="Postal Code"
                                               tabindex="<?php echo $tabIndex; $tabIndex += 2;?>"
                                        />
                                    </div>
                                </div>
                                <div class='row mt-1'>
                                    <div class='col-sm-2'>
                                        <label for='country'> Country </label>
                                    </div>
                                    <div class='col-sm-auto p-0 ms-0 me-0 ps-1 pb-2'>
                                        <select id='country' name='country'
                                                tabindex="<?php echo $tabIndex; $tabIndex += 2;?>">
                                            <?php echo $countryOptions; ?>
                                        </select>
                                    </div>
                                </div>

                                <!-- Shipping Address (artist only) -->
                                <?php if ($portalType == 'artist' || $portalType == 'admin') { ?>
                                    <div class='row mt-4'>
                                        <div class='col-sm-2'></div>
                                        <div class='col-sm-auto p-0 ms-0 me-0'><h1 class="h4">Shipping Address</h1></div>
                                        <div class='col-sm-auto p-0 ms-4 me-0'>
                                            <button class='btn btn-sm btn-primary' type="button" id="copyAddress"
                                                    tabindex="<?php echo $tabIndex; $tabIndex += 2;?>"
                                                    onclick='exhibitorProfile.copyAddressToShipTo()'>
                                                Copy <?php echo $portalName; ?> Address to Shipping Address
                                            </button>
                                        </div>
                                    </div>
                                    <div class='row mt-1'>
                                        <div class='col-sm-2'>
                                            <label for='shipCompany'><span class='text-danger'>&bigstar;</span>Company </label>
                                        </div>
                                        <div class='col-sm-auto p-0 ms-0 me-0'>
                                            <input class='form-control-sm' id='shipCompany' type='text' size='64' name='shipCompany' required
                                                   placeholder='Company Name' tabindex="<?php echo $tabIndex; $tabIndex += 2;?>"
                                            />
                                        </div>
                                    </div>
                                    <div class='row mt-1'>
                                        <div class='col-sm-2'>
                                            <label for='shipAddr'><span class='text-danger'>&bigstar;</span>Address </label>
                                        </div>
                                        <div class='col-sm-auto p-0 ms-0 me-0'>
                                            <input class='form-control-sm' id='shipAddr' type='text' size='64' name='shipAddr' required
                                                   placeholder='Street Address' tabindex="<?php echo $tabIndex; $tabIndex += 2;?>"
                                            />
                                        </div>
                                    </div>
                                    <div class='row mt-1'>
                                        <div class='col-sm-2'></div>
                                        <div class='col-sm-auto p-0 ms-0 me-0'>
                                            <input class='form-control-sm' id='shipAddr2' type='text' size='64' name='shipAddr2'
                                                   placeholder='2nd line of address if needed'
                                                   tabindex="<?php echo $tabIndex; $tabIndex += 2;?>"
                                            />
                                        </div>
                                    </div>
                                    <div class='row mt-1'>
                                        <div class='col-sm-2'>
                                            <label for='shipCity'><span class='text-danger'>&bigstar;</span>City: </label>
                                        </div>
                                        <div class='col-sm-auto p-0 ms-0 me-0'>
                                            <input class='form-control-sm' id='shipCity' type='text' size='32' maxlength='32' name='shipCity'
                                                   required tabindex="<?php echo $tabIndex; $tabIndex += 2;?>"
                                            />
                                        </div>
                                        <div class='col-sm-auto ms-0 me-0 p-0 ps-2'>
                                            <label for='shipState'><span class='text-danger'>&bigstar;</span>State: </label>
                                        </div>
                                        <div class='col-sm-auto p-0 ms-0 me-0 ps-1'>
                                            <input class='form-control-sm' id='shipState' type='text' size='10' maxlength='16' name='shipState'
                                                   required tabindex="<?php echo $tabIndex; $tabIndex += 2;?>"
                                            />
                                        </div>
                                        <div class='col-sm-auto ms-0 me-0 p-0 ps-2'>
                                            <label for='shipZip'><span class='text-danger'>&bigstar;</span>Zip: </label>
                                        </div>
                                        <div class='col-sm-auto p-0 ms-0 me-0 ps-1 pb-2'>
                                            <input class='form-control-sm' id='shipZip' type='text' size='11' maxlength='11' name='shipZip' required
                                                   placeholder='Postal Code' tabindex="<?php echo $tabIndex; $tabIndex += 2;?>"
                                            />
                                        </div>
                                    </div>
                                    <div class='row mt-1'>
                                        <div class='col-sm-2'>
                                            <label for='shipCountry'> Country </label>
                                        </div>
                                        <div class='col-sm-auto p-0 ms-0 me-0 ps-1 pb-2'>
                                            <select id='shipCountry' name='shipCountry' tabindex="<?php echo $tabIndex; $tabIndex += 2;?>">
                                                <?php echo $countryOptions; ?>
                                            </select>
                                        </div>
                                    </div>
                                <?php } ?>
                                <div class='row mt-4'>
                                    <div class='col-sm-2'></div>
                                    <div class='col-sm-auto p-0 ms-0 me-0'><strong>Signup page 4 of 4, please complete all 4 pages, register button is on this page.</strong></div>
                                </div>
                            </div>
                        </form>
                        <?php if ($portalType != 'admin') { ?>
                            <div class="row mt-1">
                                <div class="col-sm-12">
                                    <?php outputCustomText('signup/bottom');?>
                                </div>
                            </div>
                        <?php } ?>
                    </div>
                    <div id='au_result_message' class='mt-4 p-2'></div>
                </div>
                <div class="modal-footer">
                    <?php $tabIndex = $tabStart + 900; ?>
                    <button class='btn btn-sm btn-secondary' data-bs-dismiss='modal' tabindex="<?php echo $tabIndex; $tabIndex += 2;?>">Cancel</button>
                    <button class='btn btn-sm btn-primary' tabindex="<?php echo $tabIndex; $tabIndex += 2;?>" id='previousPageBtn'
                            onclick="exhibitorProfile.prevPage();" disabled>Previous Page</button>
                    <button class='btn btn-sm btn-primary' tabindex="<?php echo $tabIndex; $tabIndex += 2;?>"
                            id='nextPageBtn' onclick="exhibitorProfile.nextPage();">Next Page</button>
                    <button class='btn btn-sm btn-primary' id='profileSubmitBtn' onClick="exhibitorProfile.submitProfile('<?php echo $portalType; ?>')"
                            tabindex="<?php echo $tabIndex; $tabIndex += 2;?>" disabled>Admin</button>
                </div>
            </div>
        </div>
    </div>
    <?php
}
