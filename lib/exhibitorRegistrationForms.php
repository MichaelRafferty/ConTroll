
<?php
// draw_login - draw the login/signup form
function draw_login($config_vars, $result_message = '') {
    ?>

 <!-- signin form (at body level) -->
    <div id='signin'>
        <div class='container-fluid form-floating'>
            <div class='row mb-2'>
                <div class='col-sm-auto'>
                    <h4>Please log in to continue to the Portal.</h4>
                </div>
            </div>
            <form id='exhibitorSignin' method='POST'>
                <div class='row mt-1'>
                    <div class='col-sm-1'>
                        <label for='si_email'>*Email: </label>
                    </div>
                    <div class='col-sm-auto'>
                        <input class='form-control-sm' type='email' name='si_email' id='si_email' size='40' required/>
                    </div>
                </div>
                <div class='row mt-1'>
                    <div class='col-sm-1'>
                        <label for='si_password'>*Password: </label>
                    </div>
                    <div class='col-sm-auto'>
                        <input class='form-control-sm' type='password' id='si_password' name='si_password' size='40' autocomplete='off' required/>
                    </div>
                </div>
                <div class='row mt-2'>
                    <div class='col-sm-1'></div>
                    <div class='col-sm-auto'>
                        <input type='submit' class='btn btn-primary' value='Existing Account Sign-in'/>
                    </div>
                </div>
            </form>
        </div>
    </div>
    <div id='resetpw'>
        <div class='container-fluid'>
            <div class='row mt-4'>
                <div class='col-sm-auto'>
                    <button class='btn btn-secondary' onclick='resetPassword()'>Reset Forgotten Password</button>
                </div>
            </div>
        </div>
    </div>
    <div class='container-fluid'>
        <div class='row mt-4'>
            <div class='col-sm-auto'>
                <button type="button" class="btn btn-small btn-secondary" onclick="exhibitorProfile.profileModalOpen('register');">Sign Up for a New Account</a>
            </div>
            </div>
        </div>
    </div>
    <div class='container-fluid'>
        <div class='row'>
            <div class='col-sm-12 m-0 p-0'>
                <div id='result_message' class='mt-4 p-2'><?php echo $result_message; ?></div>
            </div>
        </div>
    </div>
    </body>
    <script type='text/javascript'>
        var config = <?php echo json_encode($config_vars); ?>;
    </script>
</html>
<?php
}

// draw_RegistratioModal - the modal for reg_control create and edit profile
function draw_registrationModal($portalType, $portalName, $con, $countryOptions) {
    $vendor_conf = get_conf('vendor');
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
                                    <p> Please provide us with information we can use to manage <?php
                                        echo $portalType; ?>s at <?php echo $con['conname'];
                                        $addlkey = $portalType == 'artist' ? 'artistSignupAddltext' : 'vendorSignupAddltext';
                                        if (array_key_exists($addlkey, $vendor_conf) && ($vendor_conf[$addlkey] != "")) {
                                            echo '<br/>' . file_get_contents('../config/'. $vendor_conf[$addlkey]);
                                        } ?>
                                    </p>
                                </div>
                            </div>
                            <?php } ?>
                            <div class="row" id="creatingAccountMsg">
                                <div class="col-sm-12">Creating an account does not guarantee space.</div>
                            </div>
                            <!-- Business Info -->
                            <div class='row mt-2'>
                                <div class='col-sm-2'></div>
                                <div class='col-sm-auto p-0 ms-0 me-0'><h4>Business Information</h4></div>
                            </div>
                             <?php if ($portalType == 'artist' || $portalType == 'admin') { ?>
                                <div class="row mt-1">
                                    <div class='col-sm-2'>
                                        <label for='artistName'> *Artist Name: </label>
                                    </div>
                                    <div class='col-sm-auto p-0 ms-0 me-0'>
                                        <input class='form-control-sm' type='text' name='artistName' id='artistName' maxlength='128' size='50'
                                               required placeholder='Artist Name' tabindex='1010'/>
                                    </div>
                                </div>
                            <?php } ?>
                            <div class='row mt-1'>
                                <div class="col-sm-2">
                                    <label for="exhibitorName"> *Business Name: </label>
                                </div>
                                <div class="col-sm-auto p-0 ms-0 me-0">
                                    <input class="form-control-sm" type='text' name='exhibitorName' id="exhibitorName" maxlength="64" size="50" tabindex="2" required
                                        placeholder="<?php echo $portalType == 'artist' ? 'Company or Artist Name' : 'Vendor, Dealer or Store name';?>"/><br/>
                                        <span class="small">This is the name that we will register your space under.</span>
                                </div>
                            </div>
                            <div class='row mt-1'>
                                <div class='col-sm-2'>
                                    <label for='exhibitorEmail'> *Business Email: </label>
                                </div>
                                <div class='col-sm-auto p-0 ms-0 me-0'>
                                    <input class='form-control-sm' type='email' name='exhibitorEmail' id='exhibitorEmail' maxlength='254' size='50' required
                                        placeholder='email address for the business and overall login to the portal' tabindex="10"/>
                                </div>
                            </div>
                            <div class='row mt-1'>
                                <div class='col-sm-2'>
                                    <label for='exhibitorPhone'> *Business Phone: </label>
                                </div>
                                <div class='col-sm-auto p-0 ms-0 me-0'>
                                    <input class='form-control-sm' type='text' name='exhibitorPhone' id='exhibitorPhone' maxlength='32' size='24' required
                                        placeholder='phone number for the business' tabindex="20"/>
                                </div>
                            </div>
                            <div class='row mt-1' id='passwordLine1'>
                                <div class='col-sm-2'>
                                    <label for='pw1'> *Password: </label>
                                </div>
                                <div class='col-sm-auto p-0 ms-0 me-0'>
                                    <input class='form-control-sm' id='pw1' type='password' name='password' autocomplete='off' required tabindex='30'
                                           size='24' placeholder='minimum of 8 characters'/>
                                </div>
                            </div>
                            <div class='row mt-1' id='passwordLine2'>
                                <div class='col-sm-2'>
                                    <label for='pw2'> *Confirm Password: </label>
                                </div>
                                <div class='col-sm-auto p-0 ms-0 me-0'>
                                    <input class='form-control-sm' id='pw2' type='password' name='cpassword2' autocomplete='off' required tabindex='40'
                                       size='24' placeholder='minimum of 8 characters'/>
                                </div>
                            </div>
                            <div class='row mt-1'>
                                <div class='col-sm-2'>
                                    <label for='website'>Website: </label>
                                </div>
                                <div class='col-sm-auto p-0 ms-0 me-0'>
                                    <input class='form-control-sm' id='website' type='text' size='64' name='website'
                                        placeholder='Please enter your web, Etsy or social media site, or other appropriate URL.' tabindex="50"/>
                                </div>
                            </div>
                            <div class='row mt-1'>
                                <div class='col-sm-2'>
                                    <label for='description'>*Description: </label>
                                </div>
                                <div class='col-sm-auto p-0 ms-0 me-0'>
                                    <textarea class='form-control-sm' id='description' name='description' rows=5 cols=64 required tabindex="60"></textarea>
                                </div>
                            </div>
                            <div class='row mt-1'>
                                <div class='col-sm-2'>
                                    <label for='publicity'> *Publicity: </label>
                                </div>
                                <div class='col-sm-9 p-0 ms-0 me-0'>
                                    <select name='publicity' id='publicity' tabindex='1020' style='min-width:100% !important;'>
                                        <option value='1' selected>Yes, You may use my information to publicize my attendence
                                            at <?php echo $con['conname']; ?></option>
                                        <option value='0'>No, You may not use my information to publicize my attendence
                                            at <?php echo $con['conname']; ?></option>
                                    </select>
                                </div>
                            </div>
                            <?php if ($portalType == 'artist') { /* TODO change this to 'mail-in allowed' */ ?>
                                <div class='row mt-1'>
                                    <div class='col-sm-2'>
                                        <label for='mailin'> *Are you requesting a mail-in space: </label>
                                    </div>
                                    <div class='col-sm-9 p-0 ms-0 me-0'>
                                        <select name='mailin' id='mailin' tabindex='1020' style='min-width:100% !important;'>
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
                                <div class='col-sm-auto p-0 ms-0 me-0'><h4><?php echo $portalName; ?> Address</h4></div>
                            </div>
                            <div class="row mt-1">
                                <div class="col-sm-2">
                                    <label for="addr"> *Address </label>
                                </div>
                                <div class="col-sm-auto p-0 ms-0 me-0">
                                    <input class="form-control-sm" id='addr' type='text' size="64" name='addr' required placeholder="Street Address" tabindex="100"/>
                                </div>
                            </div>
                            <div class="row mt-1">
                                <div class="col-sm-2"></div>
                                <div class="col-sm-auto p-0 ms-0 me-0">
                                    <input class="form-control-sm" id='addr2' type='text' size="64" name='addr2'
                                           placeholder="second line of address if needed" tabindex="110"/>
                                </div>
                            </div>
                            <div class="row mt-1">
                                <div class="col-sm-2">
                                    <label for="city"> *City: </label>
                                </div>
                                <div class="col-sm-auto p-0 ms-0 me-0">
                                    <input class="form-control-sm" id='city' type='text' size="32" maxlength="32" name='city' required tabindex="120"/>
                                </div>
                                <div class="col-sm-auto ms-0 me-0 p-0 ps-2">
                                    <label for="state"> *State: </label>
                                </div>
                                <div class="col-sm-auto p-0 ms-0 me-0 ps-1">
                                    <input class="form-control-sm" id='state' type='text' size="10" maxlength="16" name='state' required tabindex="130"/>
                                </div>
                                <div class="col-sm-auto ms-0 me-0 p-0 ps-2">
                                    <label for="zip"> *Zip: </label>
                                </div>
                                <div class="col-sm-auto p-0 ms-0 me-0 ps-1 pb-2">
                                    <input class="form-control-sm" id='zip' type='text' size="11" maxlength="11" name='zip' required
                                           placeholder="Postal Code" tabindex="140"/>
                                </div>
                            </div>
                            <div class='row mt-1'>
                                <div class='col-sm-2'>
                                    <label for='country'> Country </label>
                                </div>
                                <div class='col-sm-auto p-0 ms-0 me-0 ps-1 pb-2'>
                                    <select id='country' name='country' tabindex='150'>
                                        <?php echo $countryOptions; ?>
                                    </select>
                                </div>
                            </div>
                            <!-- Contact Info -->
                            <div class='row mt-2'>
                                <div class='col-sm-2'></div>
                                <div class='col-sm-auto p-0 ms-0 me-0'><h4>Primary Contact</h4></div>
                            </div>
                            <div class='row mt-1'>
                                <div class='col-sm-2'>
                                    <label for='contactName'> *Contact Name: </label>
                                </div>
                                <div class='col-sm-auto p-0 ms-0 me-0'>
                                    <input class='form-control-sm' type='text' name='contactName' id='contactName' maxlength='64' size='50' tabindex='160'
                                           required
                                           placeholder='primary contact name'/>
                                </div>
                            </div>
                            <div class='row mt-1'>
                                <div class='col-sm-2'>
                                    <label for='contactEmail'> *Email/Login: </label>
                                </div>
                                <div class='col-sm-auto p-0 ms-0 me-0'>
                                    <input class='form-control-sm' type='email' name='contactEmail' id='contactEmail' maxlength='254' size='50' required
                                           placeholder='email address for Contact and alternate login to the portal' tabindex='170'/>
                                </div>
                            </div>
                            <div class='row mt-1'>
                                <div class='col-sm-2'>
                                    <label for='contactPhone'> *Contact Phone: </label>
                                </div>
                                <div class='col-sm-auto p-0 ms-0 me-0'>
                                    <input class='form-control-sm' type='text' name='contactPhone' id='contactPhone' maxlength='32' size='24' required
                                           placeholder="contact's phone number" tabindex='180'/>
                                </div>
                            </div>
                            <div class='row mt-1' id='cpasswordLine1'>
                                <div class='col-sm-2'>
                                    <label for='cpw1'> *Contact Password: </label>
                                </div>
                                <div class='col-sm-auto p-0 ms-0 me-0'>
                                    <input class='form-control-sm' id='cpw1' type='password' name='cpassword' autocomplete='off' required tabindex='190'
                                           size='24' placeholder='minimum of 8 characters'/>
                                </div>
                            </div>
                            <div class='row mt-1' id='cpasswordLine2'>
                                <div class='col-sm-2'>
                                    <label for='cpw2'> *Confirm Password: </label>
                                </div>
                                <div class='col-sm-auto p-0 ms-0 me-0'>
                                    <input class='form-control-sm' id='cpw2' type='password' name='cpassword2' autocomplete='off' required tabindex='200'
                                           size='24' placeholder='minimum of 8 characters'/>
                                </div>
                            </div>
                            <!-- Shipping Address (artist only) -->
                            <?php if ($portalType == 'artist' || $portalType == 'admin') { ?>
                            <div class='row mt-4'>
                                <div class='col-sm-2'></div>
                                <div class='col-sm-auto p-0 ms-0 me-0'><h4>Shipping Address</h4></div>
                                <div class='col-sm-auto p-0 ms-4 me-0'>
                                    <button class='btn btn-sm btn-primary' type="button" onclick='exhibitorProfile.copyAddressToShipTo()'>Copy <?php echo $portalName; ?> Address to Shipping Address</button>
                                </div>
                            </div>
                            <div class='row mt-1'>
                                <div class='col-sm-2'>
                                    <label for='shipCompany'> *Company </label>
                                </div>
                                <div class='col-sm-auto p-0 ms-0 me-0'>
                                    <input class='form-control-sm' id='shipCompany' type='text' size='64' name='shipCompany' required
                                           placeholder='Company Name' tabindex='210'/>
                                </div>
                            </div>
                            <div class='row mt-1'>
                                <div class='col-sm-2'>
                                    <label for='shipAddr'> *Address </label>
                                </div>
                                <div class='col-sm-auto p-0 ms-0 me-0'>
                                    <input class='form-control-sm' id='shipAddr' type='text' size='64' name='shipAddr' required
                                           placeholder='Street Address' tabindex="220"/>
                                </div>
                            </div>
                            <div class='row mt-1'>
                                <div class='col-sm-2'></div>
                                <div class='col-sm-auto p-0 ms-0 me-0'>
                                    <input class='form-control-sm' id='shipAddr2' type='text' size='64' name='shipAddr2'
                                           placeholder='2nd line of address if needed' tabindex="230"/>
                                </div>
                            </div>
                            <div class='row mt-1'>
                                <div class='col-sm-2'>
                                    <label for='shipCity'> *City: </label>
                                </div>
                                <div class='col-sm-auto p-0 ms-0 me-0'>
                                    <input class='form-control-sm' id='shipCity' type='text' size='32' maxlength='32' name='shipCity' required tabindex="240"/>
                                </div>
                                <div class='col-sm-auto ms-0 me-0 p-0 ps-2'>
                                    <label for='shipState'> *State: </label>
                                </div>
                                <div class='col-sm-auto p-0 ms-0 me-0 ps-1'>
                                    <input class='form-control-sm' id='shipState' type='text' size='10' maxlength='16' name='shipState' required tabindex="250"/>
                                </div>
                                <div class='col-sm-auto ms-0 me-0 p-0 ps-2'>
                                    <label for='shipZip'> *Zip: </label>
                                </div>
                                <div class='col-sm-auto p-0 ms-0 me-0 ps-1 pb-2'>
                                    <input class='form-control-sm' id='shipZip' type='text' size='11' maxlength='11' name='shipZip' required
                                           placeholder='Postal Code' tabindex="260"/>
                                </div>
                            </div>
                            <div class='row mt-1'>
                                <div class='col-sm-2'>
                                    <label for='shipCountry'> Country </label>
                                </div>
                                <div class='col-sm-auto p-0 ms-0 me-0 ps-1 pb-2'>
                                    <select id='shipCountry' name='shipCountry' tabindex='270'>
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
                    <button class='btn btn-sm btn-secondary' data-bs-dismiss='modal' tabindex="280">Cancel</button>
                    <button class='btn btn-sm btn-primary' id='profileSubmitBtn' onClick="exhibitorProfile.submitProfile('<?php echo $portalType; ?>')" tabindex="290">Admin</button>
                </div>
            </div>
        </div>
    </div>
    <?php
    }

// draw_RegistratioModal - the modal for exhibitor signup
function draw_signupModal($portalType, $portalName, $con, $countryOptions) {
    $vendor_conf = get_conf('vendor');
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
                            <?php if ($portalType != 'admin') { ?>
                                <div class="row">
                                    <div class="col-sm-12">
                                        <p> Please provide us with information we can use to manage <?php
                                            echo $portalType; ?>s at <?php echo $con['conname'];
                                            $addlkey = $portalType == 'artist' ? 'artistSignupAddltext' : 'vendorSignupAddltext';
                                            if (array_key_exists($addlkey, $vendor_conf) && ($vendor_conf[$addlkey] != '')) {
                                                echo '<br/>' . file_get_contents('../config/' . $vendor_conf[$addlkey]);
                                            } ?>
                                        </p>
                                    </div>
                                </div>
                            <?php } ?>
                            <div class="row" id="creatingAccountMsg">
                                <div class="col-sm-12">Creating an account does not guarantee space.</div>
                            </div>
                            <!-- Page 1 - Are you a mail in Artist - display only if artist -->
                            <div id="page1">
                                <?php if ($portalType == 'artist') { /* TODO change this to 'mail-in allowed' */ ?>
                                <div class='row mt-3'>
                                    <div class='col-sm-2'>
                                        <label for='artistName'> *Artist Name: </label>
                                    </div>
                                    <div class='col-sm-auto p-0 ms-0 me-0'>
                                        <input class='form-control-sm' type='text' name='artistName' id='artistName' maxlength='128' size='50'
                                               required placeholder='Artist Name' tabindex='1010'/>
                                    </div>
                                </div>
                                <div class='row mt-3'>
                                    <div class='col-sm-2'></div>
                                    <div class='col-sm-auto p-0 ms-0 me-0'><h4>Are You a Mail In Artist</h4></div>
                                </div>
                                <div class='row mt-1'>
                                    <div class='col-sm-2'>
                                        <label for='mailin'> *Are you requesting a mail-in space: </label>
                                    </div>
                                    <div class='col-sm-9 p-0 ms-0 me-0'>
                                        <select name='mailin' id='mailin' tabindex='1020' style="min-width:100% !important;">
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
                                        <label for='publicity'> *Publicity: </label>
                                    </div>
                                    <div class='col-sm-9 p-0 ms-0 me-0'>
                                        <select name='publicity' id='publicity' tabindex='1020' style="min-width:100% !important;">
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
                            <!-- Business Info -->
                                <div class='row mt-2'>
                                    <div class='col-sm-2'></div>
                                    <div class='col-sm-auto p-0 ms-0 me-0'><h4>Business Information</h4></div>
                                </div>
                                <div class="row mt-1">
                                    <div class="col-sm-2">
                                        <label for="exhibitorName"> *Name: </label>
                                    </div>
                                    <div class="col-sm-auto p-0 ms-0 me-0">
                                        <input class="form-control-sm" type='text' name='exhibitorName' id="exhibitorName" maxlength="64" size="50" tabindex="2010"
                                               required
                                               placeholder="<?php echo $portalType == 'artist' ? 'Company or Artist Name' : 'Vendor, Dealer or Store name'; ?>"/><br/>
                                        <span class="small">This is the name that we will register your space under.</span>
                                    </div>
                                    <?php if ($portalType == 'artist') { ?>
                                        <div class='col-sm-auto p-0 ms-4 me-0'>
                                            <button class='btn btn-sm btn-primary' type='button' onclick='exhibitorProfile.copyArtistNametoBusinessName()'>
                                                Copy <?php echo $portalName; ?> Name to Business Name
                                            </button>
                                        </div>
                                    <?php } ?>
                                </div>
                                <div class='row mt-1'>
                                    <div class='col-sm-2'>
                                        <label for='exhibitorEmail'> *Business Email: </label>
                                    </div>
                                    <div class='col-sm-auto p-0 ms-0 me-0'>
                                        <input class='form-control-sm' type='email' name='exhibitorEmail' id='exhibitorEmail' maxlength='254' size='50' required
                                               placeholder='email address for the business and overall login to the portal' tabindex="2020"/>
                                    </div>
                                </div>
                                <div class='row mt-1'>
                                    <div class='col-sm-2'>
                                        <label for='exhibitorPhone'> *Business Phone: </label>
                                    </div>
                                    <div class='col-sm-auto p-0 ms-0 me-0'>
                                        <input class='form-control-sm' type='text' name='exhibitorPhone' id='exhibitorPhone' maxlength='32' size='24' required
                                               placeholder='phone number for the business' tabindex="2030"/>
                                    </div>
                                </div>
                                <div class='row mt-1' id='passwordLine1'>
                                    <div class='col-sm-2'>
                                        <label for='pw1'> *Password: </label>
                                    </div>
                                    <div class='col-sm-auto p-0 ms-0 me-0'>
                                        <input class='form-control-sm' id='pw1' type='password' name='password' autocomplete='off' required tabindex='2040'
                                               size='24' placeholder='minimum of 8 characters'/>
                                    </div>
                                </div>
                                <div class='row mt-1' id='passwordLine2'>
                                    <div class='col-sm-2'>
                                        <label for='pw2'> *Confirm Password: </label>
                                    </div>
                                    <div class='col-sm-auto p-0 ms-0 me-0'>
                                        <input class='form-control-sm' id='pw2' type='password' name='cpassword2' autocomplete='off' required tabindex='2050'
                                               size='24' placeholder='minimum of 8 characters'/>
                                    </div>
                                </div>
                                <div class='row mt-1'>
                                    <div class='col-sm-2'>
                                        <label for='website'>Website: </label>
                                    </div>
                                    <div class='col-sm-auto p-0 ms-0 me-0'>
                                        <input class='form-control-sm' id='website' type='text' size='64' name='website'
                                               placeholder='Please enter your web, Etsy or social media site, or other appropriate URL.' tabindex="2060"/>
                                    </div>
                                </div>
                                <div class='row mt-1'>
                                    <div class='col-sm-2'>
                                        <label for='description'>*Description: </label>
                                    </div>
                                    <div class='col-sm-auto p-0 ms-0 me-0'>
                                        <textarea class='form-control-sm' id='description' name='description' rows=5 cols=64 required tabindex="2070"></textarea>
                                    </div>
                                </div>
                                <div class='row mt-4'>
                                    <div class='col-sm-2'></div>
                                    <div class='col-sm-auto p-0 ms-0 me-0'><strong>Signup page 2 of 4, please complete all 4 pages, register button is on page
                                            4.</strong></div>
                                </div>
                            </div>
                            <div id='page3' hidden>
                                <!-- Contact Info -->
                                <div class='row mt-2'>
                                    <div class='col-sm-2'></div>
                                    <div class='col-sm-auto p-0 ms-0 me-0'><h4>Primary Contact</h4></div>
                                    <div class='col-sm-auto p-0 ms-4 me-0'>
                                        <button class='btn btn-sm btn-primary' type='button' onclick='exhibitorProfile.copyBusToContactName()'>
                                            Copy <?php echo $portalName; ?> Name/Info to Contact Name/Info
                                        </button>
                                    </div>
                                </div>
                                <div class='row mt-1'>
                                    <div class='col-sm-2'>
                                        <label for='contactName'> *Contact Name: </label>
                                    </div>
                                    <div class='col-sm-auto p-0 ms-0 me-0'>
                                        <input class='form-control-sm' type='text' name='contactName' id='contactName' maxlength='64' size='50' tabindex='3010'
                                               required
                                               placeholder='primary contact name'/>
                                    </div>
                                </div>
                                <div class='row mt-1'>
                                    <div class='col-sm-2'>
                                        <label for='contactEmail'> *Email/Login: </label>
                                    </div>
                                    <div class='col-sm-auto p-0 ms-0 me-0'>
                                        <input class='form-control-sm' type='email' name='contactEmail' id='contactEmail' maxlength='254' size='50' required
                                               placeholder='email address for Contact and alternate login to the portal' tabindex='3020'/>
                                    </div>
                                </div>
                                <div class='row mt-1'>
                                    <div class='col-sm-2'>
                                        <label for='contactPhone'> *Contact Phone: </label>
                                    </div>
                                    <div class='col-sm-auto p-0 ms-0 me-0'>
                                        <input class='form-control-sm' type='text' name='contactPhone' id='contactPhone' maxlength='32' size='24' required
                                               placeholder="contact's phone number" tabindex='3030'/>
                                    </div>
                                </div>
                                <div class='row mt-1' id='cpasswordLine1'>
                                    <div class='col-sm-2'>
                                        <label for='cpw1'> *Contact Password: </label>
                                    </div>
                                    <div class='col-sm-auto p-0 ms-0 me-0'>
                                        <input class='form-control-sm' id='cpw1' type='password' name='cpassword' autocomplete='off' required tabindex='3040'
                                               size='24' placeholder='minimum of 8 characters'/>
                                    </div>
                                </div>
                                <div class='row mt-1' id='cpasswordLine2'>
                                    <div class='col-sm-2'>
                                        <label for='cpw2'> *Confirm Password: </label>
                                    </div>
                                    <div class='col-sm-auto p-0 ms-0 me-0'>
                                        <input class='form-control-sm' id='cpw2' type='password' name='cpassword2' autocomplete='off' required tabindex='3050'
                                               size='24' placeholder='minimum of 8 characters'/>
                                    </div>
                                </div>
                                <div class='row mt-4'>
                                    <div class='col-sm-2'></div>
                                    <div class='col-sm-auto p-0 ms-0 me-0'><strong>Signup page 3 of 4, please complete all 4 pages, register button is on page
                                            4.</strong></div>
                                </div>
                            </div>
                            <div id="page4" hidden>
                                <!-- Vendor/Artist Address -->
                                <div class='row mt-2'>
                                    <div class='col-sm-2'></div>
                                    <div class='col-sm-auto p-0 ms-0 me-0'><h4><?php echo $portalName; ?> Address</h4></div>
                                </div>
                                <div class="row mt-1">
                                    <div class="col-sm-2">
                                        <label for="addr"> *Address </label>
                                    </div>
                                    <div class="col-sm-auto p-0 ms-0 me-0">
                                        <input class="form-control-sm" id='addr' type='text' size="64" name='addr' required placeholder="Street Address"
                                               tabindex="4010"/>
                                    </div>
                                </div>
                                <div class="row mt-1">
                                    <div class="col-sm-2"></div>
                                    <div class="col-sm-auto p-0 ms-0 me-0">
                                        <input class="form-control-sm" id='addr2' type='text' size="64" name='addr2'
                                               placeholder="second line of address if needed" tabindex="4020"/>
                                    </div>
                                </div>
                                <div class="row mt-1">
                                    <div class="col-sm-2">
                                        <label for="city"> *City: </label>
                                    </div>
                                    <div class="col-sm-auto p-0 ms-0 me-0">
                                        <input class="form-control-sm" id='city' type='text' size="32" maxlength="32" name='city' required tabindex="4030"/>
                                    </div>
                                    <div class="col-sm-auto ms-0 me-0 p-0 ps-2">
                                        <label for="state"> *State: </label>
                                    </div>
                                    <div class="col-sm-auto p-0 ms-0 me-0 ps-1">
                                        <input class="form-control-sm" id='state' type='text' size="10" maxlength="16" name='state' required tabindex="4040"/>
                                    </div>
                                    <div class="col-sm-auto ms-0 me-0 p-0 ps-2">
                                        <label for="zip"> *Zip: </label>
                                    </div>
                                    <div class="col-sm-auto p-0 ms-0 me-0 ps-1 pb-2">
                                        <input class="form-control-sm" id='zip' type='text' size="11" maxlength="11" name='zip' required
                                               placeholder="Postal Code" tabindex="4050"/>
                                    </div>
                                </div>
                                <div class='row mt-1'>
                                    <div class='col-sm-2'>
                                        <label for='country'> Country </label>
                                    </div>
                                    <div class='col-sm-auto p-0 ms-0 me-0 ps-1 pb-2'>
                                        <select id='country' name='country' tabindex='4060'>
                                            <?php echo $countryOptions; ?>
                                        </select>
                                    </div>
                                </div>

                                <!-- Shipping Address (artist only) -->
                                <?php if ($portalType == 'artist' || $portalType == 'admin') { ?>
                                    <div class='row mt-4'>
                                        <div class='col-sm-2'></div>
                                        <div class='col-sm-auto p-0 ms-0 me-0'><h4>Shipping Address</h4></div>
                                        <div class='col-sm-auto p-0 ms-4 me-0'>
                                            <button class='btn btn-sm btn-primary' type="button" onclick='exhibitorProfile.copyAddressToShipTo()'>
                                                Copy <?php echo $portalName; ?> Address to Shipping Address
                                            </button>
                                        </div>
                                    </div>
                                    <div class='row mt-1'>
                                        <div class='col-sm-2'>
                                            <label for='shipCompany'> *Company </label>
                                        </div>
                                        <div class='col-sm-auto p-0 ms-0 me-0'>
                                            <input class='form-control-sm' id='shipCompany' type='text' size='64' name='shipCompany' required
                                                   placeholder='Company Name' tabindex='4070'/>
                                        </div>
                                    </div>
                                    <div class='row mt-1'>
                                        <div class='col-sm-2'>
                                            <label for='shipAddr'> *Address </label>
                                        </div>
                                        <div class='col-sm-auto p-0 ms-0 me-0'>
                                            <input class='form-control-sm' id='shipAddr' type='text' size='64' name='shipAddr' required
                                                   placeholder='Street Address' tabindex="4080"/>
                                        </div>
                                    </div>
                                    <div class='row mt-1'>
                                        <div class='col-sm-2'></div>
                                        <div class='col-sm-auto p-0 ms-0 me-0'>
                                            <input class='form-control-sm' id='shipAddr2' type='text' size='64' name='shipAddr2'
                                                   placeholder='2nd line of address if needed' tabindex="4090"/>
                                        </div>
                                    </div>
                                    <div class='row mt-1'>
                                        <div class='col-sm-2'>
                                            <label for='shipCity'> *City: </label>
                                        </div>
                                        <div class='col-sm-auto p-0 ms-0 me-0'>
                                            <input class='form-control-sm' id='shipCity' type='text' size='32' maxlength='32' name='shipCity' required
                                                   tabindex="4100"/>
                                        </div>
                                        <div class='col-sm-auto ms-0 me-0 p-0 ps-2'>
                                            <label for='shipState'> *State: </label>
                                        </div>
                                        <div class='col-sm-auto p-0 ms-0 me-0 ps-1'>
                                            <input class='form-control-sm' id='shipState' type='text' size='10' maxlength='16' name='shipState' required
                                                   tabindex="4110"/>
                                        </div>
                                        <div class='col-sm-auto ms-0 me-0 p-0 ps-2'>
                                            <label for='shipZip'> *Zip: </label>
                                        </div>
                                        <div class='col-sm-auto p-0 ms-0 me-0 ps-1 pb-2'>
                                            <input class='form-control-sm' id='shipZip' type='text' size='11' maxlength='11' name='shipZip' required
                                                   placeholder='Postal Code' tabindex="4220"/>
                                        </div>
                                    </div>
                                    <div class='row mt-1'>
                                        <div class='col-sm-2'>
                                            <label for='shipCountry'> Country </label>
                                        </div>
                                        <div class='col-sm-auto p-0 ms-0 me-0 ps-1 pb-2'>
                                            <select id='shipCountry' name='shipCountry' tabindex='4230'>
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
                    </div>
                    <div id='au_result_message' class='mt-4 p-2'></div>
                </div>
                <div class="modal-footer">
                    <button class='btn btn-sm btn-secondary' data-bs-dismiss='modal' tabindex="5010">Cancel</button>
                    <button class='btn btn-sm btn-primary' tabindex="5020" id='previousPageBtn' onclick="exhibitorProfile.prevPage();" disabled>Previous Page</button>
                    <button class='btn btn-sm btn-primary' tabindex="5030" id='nextPageBtn' onclick="exhibitorProfile.nextPage();">Next Page</button>
                    <button class='btn btn-sm btn-primary' id='profileSubmitBtn' onClick="exhibitorProfile.submitProfile('<?php echo $portalType; ?>')"
                            tabindex="5050" disabled>Admin
                    </button>
                </div>
            </div>
        </div>
    </div>
    <?php
}
