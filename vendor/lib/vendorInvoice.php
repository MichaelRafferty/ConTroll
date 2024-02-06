<?php
// draw the invoice screen for buying space in the vendor/artist portal
function draw_vendorInvoiceModal($vendor, $info, $countryOptions, $ini, $cc) {
    $vendor_conf = get_conf('vendor');
    ?>
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
                    <form id='vendor_invoice_form' class='form-floating' action='javascript:void(0);'>
                        <div class="row mt-2">
                            <div class="col-sm-12" id="vendor_inv_approved_for"></div>
                        </div>
                        <div class='row mt-4'>
                            <div class='col-sm-12' id='vendor_inv_included'></div>
                        </div>
                        <hr/>
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
                                <input class="form-control-sm" type='text' name='name' id='vendor_inv_name' value="<?php echo escape_quotes($info['exhibitorName']);  ?>" size="64" required/>
                            </div>
                        </div>
                        <div class='row'>
                            <div class='col-sm-2'>
                                <label for='vendor_inv_email'>Email:</label>
                            </div>
                            <div class='col-sm-10 p-0'>
                                <input class='form-control-sm' type='text' name='email' id='vendor_inv_email' value="<?php echo escape_quotes($info['exhibitorEmail']); ?>" size="64" required/>
                            </div>
                        </div>
                        <div class='row'>
                            <div class='col-sm-2'>
                                <label for='vendor_inv_addr'>Address:</label>
                            </div>
                            <div class='col-sm-10 p-0'>
                                <input class='form-control-sm' type='text' name='addr' id='vendor_inv_addr' value="<?php echo escape_quotes($info['addr']); ?>" size='64' required/>
                            </div>
                        </div>
                        <div class='row'>
                            <div class='col-sm-2'>
                                <label for='vendor_inv_addr2'>Company/ Addr2:</label>
                            </div>
                            <div class='col-sm-10 p-0'>
                                <input class='form-control-sm' type='text' name='addr2' id='vendor_inv_addr2' value="<?php echo escape_quotes($info['addr2']); ?>" size='64'/>
                            </div>
                        </div>
                        <div class='row'>
                            <div class='col-sm-2'>
                                <label for='vendor_inv_city'>City: </label>
                            </div>
                            <div class='col-sm-auto p-0 me-0'>
                                <input class='form-control-sm' id='vendor_inv_city' type='text' size='32' value="<?php echo escape_quotes($info['city']); ?>" name=' city' required/>
                            </div>
                            <div class='col-sm-auto ms-0 me-0 p-0 ps-2'>
                                <label for='vendor_inv_state'> State: </label>
                            </div>
                            <div class='col-sm-auto p-0 ms-0 me-0 ps-1'>
                                <input class='form-control-sm' id='vendor_inv_state' type='text' size='2' maxlength='2' value="<?php echo escape_quotes($info['state']); ?>"
                                       name='state' required/>
                            </div>
                            <div class='col-sm-auto ms-0 me-0 p-0 ps-2'>
                                <label for='vendor_inv_zip'> Zip: </label>
                            </div>
                            <div class='col-sm-auto p-0 ms-0 me-0 ps-1 pb-2'>
                                <input class='form-control-sm' id='vendor_inv_zip' type='text' size='11' maxlength='11' value="<?php echo escape_quotes($info['zip']); ?>" name='zip'
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
                        <div class="row mt-4 mb-4">
                            <div class="col-sm-2"></div>
                            <div class="col-sm-10" id="dealer_space_cost"></div>
                        </div>
                        <div class="row">
                            <div class="col-sm-2">
                                <label for="vendor_inv_requests">Special Requests:</label>
                            </div>
                            <div class="col-sm-10 p-0">
                                 <textarea class='form-control-sm' id='vendor_inv_requests' name='requests' cols="64" rows="5"></textarea>
                            </div>
                        </div>
                        <hr/>
                        <div id="vendor_inv_included_mbr"></div>
                        <div id="vendor_inv_additional_mbr"></div>
                        <div class="row">
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
                                 <input type='text' name='cc_fname' id='cc_fname' required='required' placeholder='First Name' size="32" maxlength="32" />
                             </div>
                             <div class='col-sm-auto'>
                                 <input type='text' name='cc_lname' id='cc_lname' required='required'  placeholder='Last Name' size='32' maxlength='32'/>
                             </div>
                         </div>
                         <div class='row'>
                             <div class='col-sm-2'>
                                 <label for='cc_street'>
                                     Street:
                                 </label>
                             </div>
                             <div class='col-sm-auto'>
                                 <input type='text' id='cc_street' required='required' name='cc_addr' size='64' maxlength='64' value="<?php echo escape_quotes($info['addr']); ?>"/>
                             </div>
                         </div>
                         <div class='row'>
                             <div class='col-sm-2'>
                                 <label for='cc_city'>City:</label>
                             </div>
                             <div class='col-sm-auto'>
                                 <input type='text' id='cc_city' required='required' size='35' name='cc_city' maxlength='64' value="<?php echo escape_quotes($info['city']); ?>"/>
                             </div>
                             <div class='col-sm-auto ps-0 pe-0'>
                                 <label for='cc_state'>State:</label>
                             </div>
                             <div class='col-sm-auto'>
                                 <input type='text' id='cc_state' size=2 maxlength="2" required='required' name='cc_state' value="<?php echo escape_quotes($info['state']); ?>"/>
                             </div>
                             <div class='col-sm-auto ps-0 pe-0'>
                                 <label for='cc_zip'>Zip:</label>
                             </div>
                             <div class='col-sm-auto'>
                                 <input type='text' id='cc_zip' required='required' size=10 maxlength="10" name='cc_zip' value="<?php echo escape_quotes($info['zip']); ?>"/>
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
                                  <input type='email' id='cc_email' name='cc_email' size="35" maxlength="64" value="<?php echo escape_quotes($info['contactEmail']); ?>"/>
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
                        <?php
if (array_key_exists('pay_disclaimer',$vendor_conf) && $vendor_conf['pay_disclaimer'] != '') {
?>                          <div class='row p-1 pt-4 pb-3'>
                                <div class='col-sm-12'><?php
                            if (array_key_exists('pay_disclaimer', $vendor_conf) && $vendor_conf['pay_disclaimer'] != '') {
                                $discfile = '../config/' . $vendor_conf['pay_disclaimer'];
                                if (is_readable($discfile)) {
                                    $disclaimer = file_get_contents($discfile);
                                    ?>
                                    <div class='row p-1 pt=0 pb-3'>
                                        <div class='col-sm-12'>
                                            <?php echo $disclaimer . "\n"; ?>
                                        </div>
                                    </div>
                                    <?php
                                }
                            } ?>
                                </div>
                            </div>
<?php
}
?>
                        <div class="row">
                            <div class="col-sm-auto">
                                Please wait for the email, and don't click the "Purchase" button more than once.
                            </div>
                        </div>
                        <div class='row'>
                            <div class='col-sm-12'>
                                <?php echo draw_cc_html($cc, '--', 2); ?>
                                <input type='reset'/>
                            </div>
                        </div>
                    </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    </div>
<?php
}

// vendor_showRequest -> show the current request and the change/cancel button
function vendor_showInvoice($regionId, $regionName, $regionSpaces, $exhibitorSpaceList)
{
    $dolfmt = new NumberFormatter('', NumberFormatter::CURRENCY);

    $totalPrice = 0;
    echo "You have been approved for:<br/>\n";
    foreach ($exhibitorSpaceList as $key => $spaceItem) {
        // limit to spaces for this region
        $spaceId = $spaceItem['spaceId'];
        if (array_key_exists($spaceId, $regionSpaces)) {
            $date = $spaceItem['time_approved'];
            $date = date_create($date);
            $date = date_format($date, 'F j, Y') . ' at ' . date_format($date, 'g:i A');
            echo $spaceItem['approved_description'] . ' in ' . $spaceItem['regionName'] . ' for ' . $dolfmt->formatCurrency($spaceItem['approved_price'], 'USD') .
                " at $date<br/>\n";
            $totalPrice += $spaceItem['approved_price'];
        }
    }
    echo "__________________________________________________________<br/>\nTotal price for $regionName spaces " . $dolfmt->formatCurrency($totalPrice, 'USD') . "<br/>\n";
    echo "<button class='btn btn-primary' onclick='openInvoice($regionId);'>Pay $regionName Invoice</button>";

}
