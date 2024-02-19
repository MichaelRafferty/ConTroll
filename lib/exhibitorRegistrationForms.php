
<?php
// draw_login - draw the login/signup form
function draw_login($config_vars) {
    ?>

 <!-- signin form (at body level) -->
    <div id='signin'>
        <div class='container-fluid form-floating'>
            <div class='row mb-2'>
                <div class='col-sm-auto'>
                    <h4>Please log in to continue to the Portal.</h4>
                </div>
            </div>
            <form id='vendor-signin' method='POST'>
                <div class='row mt-1'>
                    <div class='col-sm-1'>
                        <label for='si_email'>*Email/Login: </label>
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
                        <input type='submit' class='btn btn-primary' value='signin'/> or
                            <a href='javascript:void(0)' onclick="exhibitorProfile.profileModalOpen('register');">Sign Up</a>
                    </div>
                </div>
            </form>
        </div>
    </div>
    <div id='resetpw'>
        <div class='container-fluid'>
            <div class='row mt-4'>
                <div class='col-sm-auto'>
                    <button class='btn btn-primary' onclick='resetPassword()'>Reset Password</button>
                </div>
            </div>
        </div>
    </div>
    <div class='container-fluid'>
        <div class='row'>
            <div class='col-sm-12 m-0 p-0'>
                <div id='result_message' class='mt-4 p-2'></div>
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

// draw_RegistratioModal - the modal for signup and edit profile
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
                                    <p> Please provide us with information we can use to evaluate if you qualify and how you would fit in the selection of <?php
                                        echo $portalType; ?>s at <?php echo $con['conname'];
                                        $addlkey = $portalType == 'artist' ? 'artistSignupAddltext' : 'vendorSignupAddltext';
                                        if (array_key_exists($addlkey, $vendor_conf)) {
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
                            <div class="row mt-1">
                                <div class="col-sm-2">
                                    <label for="exhibitorName"> *Name: </label>
                                </div>
                                <div class="col-sm-auto p-0 ms-0 me-0">
                                    <input class="form-control-sm" type='text' name='exhibitorName' id="exhibitorName" maxlength="64" size="50" tabindex="1" required
                                        placeholder="<?php echo $portalType == 'artist' ? 'Company or Artist Name' : 'Vendor, Dealer or Store name';?>"/><br/>
                                        <span class="small">This is the name that we will register your space under.</span>
                                </div>
                            </div>
                            <div class='row mt-1'>
                                <div class='col-sm-2'>
                                    <label for='exhibitorEmail'> *Business Email: </label>
                                </div>
                                <div class='col-sm-auto p-0 ms-0 me-0'>
                                    <input class='form-control-sm' type='email' name='exhibitorEmail' id='exhibitorEmail' maxlength='64' size='50' required
                                        placeholder='email address for the business' tabindex="2"/>
                                </div>
                            </div>
                            <div class='row mt-1'>
                                <div class='col-sm-2'>
                                    <label for='exhibitorPhone'> *Business Phone: </label>
                                </div>
                                <div class='col-sm-auto p-0 ms-0 me-0'>
                                    <input class='form-control-sm' type='text' name='exhibitorPhone' id='exhibitorPhone' maxlength='32' size='24' required
                                        placeholder='phone number for the business' tabindex="3"/>
                                </div>
                            </div>
                            <div class='row mt-1'>
                                <div class='col-sm-2'>
                                    <label for='website'>Website: </label>
                                </div>
                                <div class='col-sm-auto p-0 ms-0 me-0'>
                                    <input class='form-control-sm' id='website' type='text' size='64' name='website'
                                        placeholder='Please enter your web, Etsy or social media site, or other appropriate URL.' tabindex="4"/>
                                </div>
                            </div>
                            <div class='row mt-1'>
                                <div class='col-sm-2'>
                                    <label for='description'>*Description: </label>
                                </div>
                                <div class='col-sm-auto p-0 ms-0 me-0'>
                                    <textarea class='form-control-sm' id='description' name='description' rows=5 cols=64 required tabindex="5"></textarea>
                                </div>
                            </div>
                            <div class='row mt-1'>
                                <div class='col-sm-2 p-0 ms-0 me-0 pe-2 text-end'>
                                    <input class='form-control-sm' type='checkbox' id='publicity' name='publicity' tabindex="6"/>
                                </div>
                                <div class='col-sm-auto p-0 ms-0 me-0'>
                                    <label for='publicity'>Check if we may use your information to publicize your attendence at <?php echo $con['conname']; ?>, if you're
                                        coming?</label>
                                </div>
                            </div>
                            <!-- Contact Info -->
                            <div class='row mt-2'>
                                <div class='col-sm-2'></div>
                                <div class='col-sm-auto p-0 ms-0 me-0'><h4>Primary Contact/Agent Information</h4></div>
                            </div>
                            <div class='row mt-1'>
                                <div class='col-sm-2'>
                                    <label for='mailin'> *Mail In Artist: </label>
                                </div>
                                <div class='col-sm-auto p-0 ms-0 me-2 align-middle'><input class='form-control-sm' type='radio' name='mailin' id='mailinN'  tabindex='7' value="N" /></div>
                                <div class='col-sm-auto p-0 ms-0 me-4'>On-site/Using Agent or Not an Artist (not Mail-in)</div>
                                <div class='col-sm-auto p-0 ms-0 me-2 align-middle'><input class='form-control-sm' type='radio' name='mailin' id='mailinY'  tabindex='7' value="Y"/></div>
                                <div class='col-sm-auto p-0 ms-0 me-0'>Shipping Art, return via Shipping Address (Mail In)</div>
                            </div>
                            <div class='row mt-1'>
                                <div class='col-sm-2'>
                                    <label for='contactName'> *Contact/Agent Name: </label>
                                </div>
                                <div class='col-sm-auto p-0 ms-0 me-0'>
                                    <input class='form-control-sm' type='text' name='contactName' id='contactName' maxlength='64' size='50' tabindex='10' required
                                        placeholder="primary contact name"/>
                                </div>
                            </div>
                            <div class="row mt-1">
                                <div class="col-sm-2">
                                    <label for="contactEmail"> *Email/Login: </label>
                                </div>
                                <div class="col-sm-auto p-0 ms-0 me-0">
                                    <input class='form-control-sm' type='email' name='contactEmail' id='contactEmail' maxlength='64' size='50' required
                                        placeholder="email address for Contact and Login to the portal" tabindex="12"/>
                                </div>
                            </div>
                            <div class='row mt-1'>
                                <div class='col-sm-2'>
                                    <label for='contactPhone'> *Contact Phone: </label>
                                </div>
                                <div class='col-sm-auto p-0 ms-0 me-0'>
                                    <input class='form-control-sm' type='text' name='contactPhone' id='contactPhone' maxlength='32' size='24' required
                                        placeholder="contact's phone number" tabindex="14"/>
                                </div>
                            </div>
                            <div class="row mt-1" id="passwordLine1">
                                <div class="col-sm-2">
                                    <label for="pw1"> *Password: </label>
                                </div>
                                <div class="col-sm-auto p-0 ms-0 me-0">
                                    <input class="form-control-sm" id='pw1' type='password' name='password' autocomplete="off" required tabindex="16"
                                    size="24" placeholder='minimum of 8 characters' />
                                </div>
                            </div>
                            <div class="row mt-1" id="passwordLine2">
                                <div class="col-sm-2">
                                    <label for="pw2"> *Confirm Password: </label>
                                </div>
                                <div class="col-sm-auto p-0 ms-0 me-0">
                                    <input class="form-control-sm" id='pw2' type='password' name='password2' autocomplete="off" required tabindex="18"
                                    size="24" placeholder='minimum of 8 characters'/>
                                </div>
                            </div>
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
                                    <input class="form-control-sm" id='addr' type='text' size="64" name='addr' required placeholder="Street Address" tabindex="20"/>
                                </div>
                            </div>
                            <div class="row mt-1">
                                <div class="col-sm-2"></div>
                                <div class="col-sm-auto p-0 ms-0 me-0">
                                    <input class="form-control-sm" id='addr2' type='text' size="64" name='addr2'
                                           placeholder="second line of address if neededsecond line of address if needed" tabindex="22"/>
                                </div>
                            </div>
                            <div class="row mt-1">
                                <div class="col-sm-2">
                                    <label for="city"> *City: </label>
                                </div>
                                <div class="col-sm-auto p-0 ms-0 me-0">
                                    <input class="form-control-sm" id='city' type='text' size="32" maxlength="32" name='city' required tabindex="24"/>
                                </div>
                                <div class="col-sm-auto ms-0 me-0 p-0 ps-2">
                                    <label for="state"> *State: </label>
                                </div>
                                <div class="col-sm-auto p-0 ms-0 me-0 ps-1">
                                    <input class="form-control-sm" id='state' type='text' size="10" maxlength="16" name='state' required tabindex="26"/>
                                </div>
                                <div class="col-sm-auto ms-0 me-0 p-0 ps-2">
                                    <label for="zip"> *Zip: </label>
                                </div>
                                <div class="col-sm-auto p-0 ms-0 me-0 ps-1 pb-2">
                                    <input class="form-control-sm" id='zip' type='text' size="11" maxlength="11" name='zip' required
                                           placeholder="Postal Code" tabindex="28"/>
                                </div>
                            </div>
                            <div class='row mt-1'>
                                <div class='col-sm-2'>
                                    <label for='country'> Country </label>
                                </div>
                                <div class='col-sm-auto p-0 ms-0 me-0 ps-1 pb-2'>
                                    <select id='country' name='country' tabindex='30'>
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
                                    <button class='btn btn-sm btn-primary' type="button" onclick='exhibitorProfile.copyAddressToShipTo()'>Copy <?php echo $portalName; ?> Address to Shipping Address</button>
                                </div>
                            </div>
                            <div class='row mt-1'>
                                <div class='col-sm-2'>
                                    <label for='shipCompany'> *Company </label>
                                </div>
                                <div class='col-sm-auto p-0 ms-0 me-0'>
                                    <input class='form-control-sm' id='shipCompany' type='text' size='64' name='shipCompany' required
                                           placeholder='Company Name' tabindex='32'/>
                                </div>
                            </div>
                            <div class='row mt-1'>
                                <div class='col-sm-2'>
                                    <label for='shipAddr'> *Address </label>
                                </div>
                                <div class='col-sm-auto p-0 ms-0 me-0'>
                                    <input class='form-control-sm' id='shipAddr' type='text' size='64' name='shipAddr' required
                                           placeholder='Street Address' tabindex="34"/>
                                </div>
                            </div>
                            <div class='row mt-1'>
                                <div class='col-sm-2'></div>
                                <div class='col-sm-auto p-0 ms-0 me-0'>
                                    <input class='form-control-sm' id='shipAddr2' type='text' size='64' name='shipAddr2'
                                           placeholder='2nd line of address if needed' tabindex="36"/>
                                </div>
                            </div>
                            <div class='row mt-1'>
                                <div class='col-sm-2'>
                                    <label for='shipCity'> *City: </label>
                                </div>
                                <div class='col-sm-auto p-0 ms-0 me-0'>
                                    <input class='form-control-sm' id='shipCity' type='text' size='32' maxlength='32' name='shipCity' required tabindex="38"/>
                                </div>
                                <div class='col-sm-auto ms-0 me-0 p-0 ps-2'>
                                    <label for='shipState'> *State: </label>
                                </div>
                                <div class='col-sm-auto p-0 ms-0 me-0 ps-1'>
                                    <input class='form-control-sm' id='shipState' type='text' size='10' maxlength='16' name='shipState' required tabindex="40"/>
                                </div>
                                <div class='col-sm-auto ms-0 me-0 p-0 ps-2'>
                                    <label for='shipZip'> *Zip: </label>
                                </div>
                                <div class='col-sm-auto p-0 ms-0 me-0 ps-1 pb-2'>
                                    <input class='form-control-sm' id='shipZip' type='text' size='11' maxlength='11' name='shipZip' required
                                           placeholder='Postal Code' tabindex="42"/>
                                </div>
                            </div>
                            <div class='row mt-1'>
                                <div class='col-sm-2'>
                                    <label for='shipCountry'> Country </label>
                                </div>
                                <div class='col-sm-auto p-0 ms-0 me-0 ps-1 pb-2'>
                                    <select id='shipCountry' name='shipCountry' tabindex='44'>
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
                    <button class='btn btn-sm btn-secondary' data-bs-dismiss='modal' tabindex="46">Cancel</button>
                    <button class='btn btn-sm btn-primary' id='profileSubmitBtn' onClick="exhibitorProfile.submitProfile('<?php echo $portalType; ?>')" tabindex="48">Admin</button>
                </div>
            </div>
        </div>
    </div>
    <?php
    }
