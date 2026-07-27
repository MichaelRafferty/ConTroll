<?php
// draw the invoice screen for buying space in the vendor/artist portal
function draw_exhibitorInvoiceModal($exhibitor, $info, $countryOptions, $testsite, $cc, $portalName, $portalType) {
    $taxLabel = getConfValue('con', 'taxLabel');
    $taxIdLabel = getConfValue('vendor', 'taxidlabel', 'Missing Sales Tax ID Label');
    if ($info == null) {
        $exhibitorName = '';
        $exhibitorEmail = '';
        $addr = '';
        $addr2 = '';
        $city = '';
        $state = '';
        $zip = '';
        $salesTaxId = '';
        $contactEmail = '';
    } else {
        $exhibitorName = escape_quotes($info['exhibitorName']);
        $exhibitorEmail = escape_quotes($info['exhibitorEmail']);
        $addr = escape_quotes($info['addr']);
        $addr2 = escape_quotes($info['addr2']);
        $city = escape_quotes($info['city']);
        $state = escape_quotes($info['state']);
        $zip = escape_quotes($info['zip']);
        $salesTaxId = escape_quotes($info['salesTaxId']);
        $contactEmail = escape_quotes($info['contactEmail']);
    }
    $tabindex = 50;
    $currency = getConfValue('con', 'currency', 'USD');
    $curLocale = locale_get_default();
    $dolfmt = new NumberFormatter($curLocale == 'en_US_POSIX' ? 'en-us' : $curLocale, NumberFormatter::CURRENCY);
    $cardsAccepted = getConfValue('global', 'cardsAcceptedImage', 'cards_accepted_64.png');
    $cardsAcceptedAlt = getConfValue('global', 'cardsAcceptedAltText', 'Visa, Mastercard, American Express, and Discover');
    ?>
    <!-- invoice -->
    <div id='vendor_invoice' class='modal modal-xl fade' tabindex='-1' aria-labelledby='Vendor Invoice' aria-hidden='true' style='--bs-modal-width: 90%;'>
        <div class='modal-dialog'>
            <div class='modal-content'>
                <div class='modal-header bg-primary text-bg-primary'>
                    <div class='modal-title' id="vendor_invoice_title">
                        <strong><?php echo $portalName; ?> Invoice</strong>
                    </div>
                    <button type='button' class='btn-close' data-bs-dismiss='modal' aria-label='Close'></button>
                </div>
                <div class='modal-body' style='padding: 4px; background-color: lightcyan;'>
                    <div class="container-fluid form-floating">
                        <?php outputCustomText('invoice/top'); outputCustomText('invoice/top' . $portalName); ?>
                        <form id='vendor_invoice_form' class='form-floating' action='javascript:void(0);'  onsubmit='return false;'>
                        <div class="row mt-2">
                            <div class="col-sm-12" id="vendor_inv_approved_for"></div>
                        </div>
                        <?php outputCustomText('invoice/afterPrice'); outputCustomText('invoice/afterPrice' . $portalName); ?>
                        <div class='row mt-4'>
                            <div class='col-sm-12' id='vendor_inv_included'></div>
                        </div>
                        <hr/>
                        <input type='hidden' name='vendor' id='vendor_inv_id' value='<?php echo $exhibitor; ?>'/>
                        <input type='hidden' name='regionYearId' id='vendor_inv_region_id'/>
                        <input type='hidden' name='portalName' id='vendorPortalName' value='<?php echo $portalName; ?>'/>
                        <input type='hidden' name='portalType' id='vendorPortalType' value='<?php echo $portalType; ?>'/>
                        <input type='hidden' name='spacePrice' id='vendorSpacePrice'/>
<?php
    if ($exhibitor != null) {
?>
                        <div class="row">
                            <div class="col-sm-12">
                                <strong><?php echo $portalName;?> Information</strong>
                                <?php outputCustomText('invoice/beforeProfile'); outputCustomText('invoice/beforeProfile' . $portalName); ?>
                                <p>Changes made to the <?php echo $portalName;?> Information part of this form will update your profile.</p>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-sm-2">
                                <label for="vendor_inv_name">Name:</label>
                            </div>
                            <div class="col-sm-10 p-0">
                                <input class="form-control-sm" type='text' name='name' id='vendor_inv_name' value="<?php echo $exhibitorName; ?>"
                                       size="64" required tabindex="<?php echo $tabindex; $tabindex += 2;?>"/>
                            </div>
                        </div>
                        <div class='row'>
                            <div class='col-sm-2'>
                                <label for='vendor_inv_email'>Email:</label>
                            </div>
                            <div class='col-sm-10 p-0'>
                                <input class='form-control-sm' type='text' name='email' id='vendor_inv_email' value="<?php echo $exhibitorEmail; ?>"
                                       size="64" required tabindex="<?php echo $tabindex; $tabindex += 2;?>"/>
                            </div>
                        </div>
                        <div class='row'>
                            <div class='col-sm-2'>
                                <label for='vendor_inv_addr'>Address:</label>
                            </div>
                            <div class='col-sm-10 p-0'>
                                <input class='form-control-sm' type='text' name='addr' id='vendor_inv_addr' value="<?php echo $addr; ?>"
                                       size='64' required tabindex="<?php echo $tabindex; $tabindex += 2;?>"/>
                            </div>
                        </div>
                        <div class='row'>
                            <div class='col-sm-2'>
                                <label for='vendor_inv_addr2'>Company/ Addr2:</label>
                            </div>
                            <div class='col-sm-10 p-0'>
                                <input class='form-control-sm' type='text' name='addr2' id='vendor_inv_addr2' value="<?php echo $addr2; ?>"
                                       size='64' tabindex="<?php echo $tabindex; $tabindex += 2;?>"/>
                            </div>
                        </div>
                        <div class='row'>
                            <div class='col-sm-2'>
                                <label for='vendor_inv_city'>City: </label>
                            </div>
                            <div class='col-sm-auto p-0 me-0'>
                                <input class='form-control-sm' type='text' name='city' id='vendor_inv_city' value="<?php echo $city; ?>"
                                       size='32' required tabindex="<?php echo $tabindex; $tabindex += 2;?>"/>
                            </div>
                            <div class='col-sm-auto ms-0 me-0 p-0 ps-2'>
                                <label for='vendor_inv_state'> State/Prov: </label>
                            </div>
                            <div class='col-sm-auto p-0 ms-0 me-0 ps-1'>
                                <input class='form-control-sm' type='text' name='state'  id='vendor_inv_state' value="<?php echo $state; ?>"
                                       size='10' maxlength='16' required tabindex="<?php echo $tabindex; $tabindex += 2;?>"/>
                            </div>
                            <div class='col-sm-auto ms-0 me-0 p-0 ps-2'>
                                <label for='vendor_inv_zip'> Zip/PC: </label>
                            </div>
                            <div class='col-sm-auto p-0 ms-0 me-0 ps-1 pb-2'>
                                <input class='form-control-sm' type='text' name='zip' id='vendor_inv_zip' value="<?php echo $zip; ?>"
                                       size='11' maxlength='11' required tabindex="<?php echo $tabindex; $tabindex += 2;?>"/>
                            </div>
                        </div>
                         <?php if ($portalType == 'vendor' && $taxLabel != '') { ?>
                        <div class="row">
                            <div class="col-sm-2">
                                <label for="salesTaxId"><?php echo $taxIdLabel; ?>:</label>
                            </div>
                            <div class="col-sm-10 p-0">
                                <input class='form-control-sm' type='text' name='salesTaxId' size=32 maxlength="32"
                                       value="<?php echo $salesTaxId;?>" tabindex="<?php echo $tabindex; $tabindex += 2;?>"/>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-sm-12"><?php outputCustomText('invoice/taxIdExtra'); ?></div>
                        </div>
                        <?php } ?>
                        <div class="row mt-4 mb-4">
                            <div class="col-sm-2"></div>
                            <div class="col-sm-10" id="dealer_space_cost"></div>
                        </div>
                        <div class="row">
                            <div class="col-sm-2">
                                <label for="vendor_inv_requests">Special Requests:</label>
                            </div>
                            <div class="col-sm-10 p-0">
                                 <textarea class='form-control-sm' id='vendor_inv_requests' name='requests' cols="64" rows="5"
                                           tabindex="<?php echo $tabindex; $tabindex += 2;?>"></textarea>
                            </div>
                        </div>
                        <hr/>
<?php
    }
    outputCustomText('invoice/beforeMem'); outputCustomText('invoice/beforeMem' . $portalName);
?>

                        <div id="vendor_inv_included_mbr"></div>
                        <div id="vendor_inv_additional_mbr"></div>
                        <div class="container-fluid" id="membershipCost">
                            <div class="row">
                                <div class="col-sm-auto">
                                    Total price for memberships:
                                </div>
                                <div class="col-sm-auto p-0">
                                    <span id='vendor_inv_mbr_cost'><?php echo $dolfmt->formatCurrency(0.00, $currency) ;?></span>
                                </div>
                            </div>
                            <hr/>
                            <div class='row'>
                                <div class='col-sm-auto'>
                                    Total Pre Tax Order:
                                </div>
                                <div class='col-sm-auto p-0' id='vendor_inv_cost'>
                                    <?php echo $dolfmt->formatCurrency(0.00, $currency) ;?>
                                </div>
                            </div>
                        </div>
                        </form>
                        <div class='row'>
                            <div class='col-sm-12' id='inv_result_message'></div>
                        </div>
                    </div>
                </div>
                <div class='modal-footer'>
                    <button type='button' class='btn btn-secondary' data-bs-dismiss='modal'>Cancel Order</button>
                    <button type='button' class='btn btn-primary' id='total_with_tax_btn' onclick="orderValidate()">
                        Compute Total With Tax and Pay
                    </button>
                </div>
            </div>
        </div>
    </div>
        <!-- payment -->
        <div id='vendor_payment' class='modal modal-xl fade' tabindex='-1' aria-labelledby='Vendor Payment' aria-hidden='true' style='--bs-modal-width: 90%;'>
            <div class='modal-dialog'>
                <div class='modal-content'>
                    <div class='modal-header bg-primary text-bg-primary'>
                        <div class='modal-title' id='vendor_payment_title'>
                            <strong><?php echo $portalName; ?> Invoice Payment</strong>
                        </div>
                        <button type='button' class='btn-close' onclick='orderCancel();' aria-label='Close'></button>
                    </div>
                    <div class='modal-body' style='padding: 4px; background-color: lightcyan;'>
                        <div class="container-fluid form-floating">
                            <div class='container-fluid' id='paymentForDiv'></div>
                        <div class='container-fluid' id='paymentDiv'>
<?php
                            $tabindex = 900;
                            if ($cc != null) { outputCustomText('beforeCharge');
?>
                             <form id='vendor_pay_form' class='form-floating' action='javascript:void(0);' onsubmit='return false;'>
                             <div class='row'>
                                <div class='col-sm-12'>
                                    <?php if ($testsite) {
                                        ?>
                                        <h2 class='warn'>This won't charge your credit card, or do anything else.</h2>
                                        <?php
                                    }
                                    ?>
                                    <br/>
                                    We Accept<br/>
                                    <img src='<?php echo $cardsAccepted; ?>' alt='<?php echo $cardsAcceptedAlt; ?>'/>
                                </div>
                            </div>
                            <hr/>
                            <?php outputCustomText('invoice/payDisclaimer'); outputCustomText('invoice/payDisclaimer' . $portalName); ?>
                            <div class="row">
                                <div class="col-sm-auto">
                                    Please wait for the email, and don't click the "Purchase" button more than once.
                                </div>
                            </div>
                            <div class='row'>
                                <div class='col-sm-12'>
                                    <?php echo draw_cc_html('--', 'all'); ?>
                                </div>
                            </div>
                            </form>
<?php
                                outputCustomText('invoice/bottom'); outputCustomText('invoice/bottom' . $portalName);
                            } else { // exhibitors module in ConTroll - cash/check/offline cc
?>
                            <div class='row mt-2'>
                                <div class='col-sm-2 ms-0 me-2 p-0'>Amount Paid:</div>
                                <div class='col-sm-auto m-0 p-0 ms-0 me-2 p-0'>
                                    <input type='number' class='no-spinners' id='pay-amt' name='paid-amt' size='6' tabindex="<?php echo $tabindex; $tabindex += 2;?>"/>
                                </div>
                            </div>
                            <div class='row'>
                                <div class='col-sm-2 m-0 mt-2 me-2 mb-2 p-0'>Payment Type:</div>
                                <div class='col-sm-auto m-0 mt-2 p-0 ms-0 me-2 mb-2 p-0' id='pt-div'>
                                    <input type='radio' id='pt-credit' name='payment_type' value='credit'
                                           onchange='exhibitorInvoice.setPayType("credit");'  tabindex="<?php echo $tabindex; $tabindex += 2;?>"/>
                                    <label for='pt-credit'>Credit Card</label>
                                    <input type='radio' id='pt-check' name='payment_type' value='check' onchange='exhibitorInvoice.setPayType("check");'
                                           tabindex="<?php echo $tabindex; $tabindex += 2;?>"/>
                                    <label for='pt-check'>Check</label>
                                    <input type='radio' id='pt-cash' name='payment_type' value='cash' onchange='exhibitorInvoice.setPayType("cash");'
                                           tabindex="<?php echo $tabindex; $tabindex += 2;?>"/>
                                    <label for='pt-cash'>Cash</label>
                                </div>
                            </div>
                            <div class='row mb-2' id='pay-check-div' hidden>
                                <div class='col-sm-2 ms-0 me-2 p-0'>Check Number:</div>
                                <div class='col-sm-auto m-0 p-0 ms-0 me-2 p-0'>
                                    <input type='text' size='8' maxlength='10' name='pay-checkno' id='pay-checkno' tabindex="<?php echo $tabindex; $tabindex += 2;?>"/>
                                </div>
                            </div>
                            <div class='row mb-2' id='pay-ccauth-div' hidden>
                                <div class='col-sm-2 ms-0 me-2 p-0'>CC Auth Code:</div>
                                <div class='col-sm-auto m-0 p-0 ms-0 me-2 p-0'>
                                    <input type='text' size='15' maxlength='16' name='pay-ccauth' id='pay-ccauth' tabindex="<?php echo $tabindex; $tabindex += 2;?>"/>
                                </div>
                            </div>
                            <div class='row'>
                                <div class='col-sm-2 ms-0 me-2 p-0'>Description:</div>
                                <div class='col-sm-auto m-0 p-0 ms-0 me-2 p-0'>
                                    <input type='text' size='60' maxlength='64' name='pay-desc' id='pay-desc' tabindex="<?php echo $tabindex; $tabindex += 2;?>"/>
                                </div>
                            </div>
                            <div class='row mt-3 pb-2'>
                                <div class='col-sm-2 ms-0 me-2 p-0'>&nbsp;</div>
                                <div class='col-sm-auto ms-0 me-2 p-0'>
                                    <button class='btn btn-primary btn-sm' type='button' id='card-button' disabled
                                            onclick="exhibitorInvoice.pay('nomodal');" tabindex="<?php echo $tabindex; $tabindex += 2;?>">Confirm Pay</button>
                                </div>
                                <div class='col-sm-auto ms-0 me-2 p-0'>
                                    <button class='btn btn-warning btn-sm' type='button' id='pay-override-pay' hidden disabled
                                            onclick='exhibitorInvoice.processPay();' tabindex="<?php echo $tabindex;
                                        $tabindex += 2; ?>">Overide Validation and Pay
                                    </button>
                                </div>
                                <div class='col-sm-auto ms-0 me-2 p-0'>
                                    <button class='btn btn-primary btn-sm' type='button' id='pay-btn-ercpt'
                                            onclick="exhibitorInvoice.email_receipt('email');" tabindex="<?php echo $tabindex; $tabindex += 2;?>"
                                            hidden disabled>Email Receipt</button>
                                </div>
                            </div>
<?php
                            }
?>
                            <div class='row'>
                                <div class='col-sm-12' id="pay_result_message"></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class='modal-footer'>
                    <button type='button' class='btn btn-secondary' id='pay_cancel_button' onclick='orderCancel()'>
                        Cancel Payment
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
<?php
}

// exhibitor_showInvoice -> show the current request and the change/cancel button
function exhibitor_showInvoice($regionYearId, $regionName, $regionSpaces, $exhibitorSpaceList, $region, $info) {
    $currency = getConfValue('con', 'currency', 'USD');
    $curLocale = locale_get_default();
    $dolfmt = new NumberFormatter($curLocale == 'en_US_POSIX' ? 'en-us' : $curLocale, NumberFormatter::CURRENCY);

    $totalPrice = 0;
    echo "You have been approved for:<br/>\n";
    foreach ($exhibitorSpaceList as $key => $spaceItem) {
        // limit to spaces for this region
        $spaceId = $spaceItem['spaceId'];
        if (array_key_exists($spaceId, $regionSpaces)) {
            if ($spaceItem['item_approved'] != null) {
                $date = $spaceItem['time_approved'];
                $date = date_create($date);
                $date = date_format($date, 'F j, Y') . ' at ' . date_format($date, 'g:i A');
                echo $spaceItem['approved_description'] . ' in ' . $spaceItem['regionName'] . ' for ' . $dolfmt->formatCurrency($spaceItem['approved_price'], $currency) .
                    " on $date<br/>\n";
                $totalPrice += $spaceItem['approved_price'];
            }
        }
    }
    if ($info['mailin'] == 'Y' && $region['mailinFee'] > 0) {
        echo "Mail-in Fee of " . $dolfmt->formatCurrency($region['mailinFee'], $currency) . "<br/>\n";
        $totalPrice += $region['mailinFee'];
    }
    echo "__________________________________________________________<br/>\nTotal price for $regionName spaces " . $dolfmt->formatCurrency($totalPrice, $currency) . "<br/>\n";
    echo "<button class='btn btn-primary' onclick='openInvoice($regionYearId);'>Pay $regionName Invoice</button>";

}


// draw the paid for status block
function vendor_receipt($regionYearId, $regionName, $regionSpaces, $exhibitorSpaceList) {
    $currency = getConfValue('con', 'currency', 'USD');
    $curLocale = locale_get_default();
    $dolfmt = new NumberFormatter($curLocale == 'en_US_POSIX' ? 'en-us' : $curLocale, NumberFormatter::CURRENCY);

    $totalPrice = 0;
    echo "You have purchased:<br/>\n";
    foreach ($exhibitorSpaceList as $key => $spaceItem) {
        // limit to spaces for this region
        $spaceId = $spaceItem['spaceId'];
        if (array_key_exists($spaceId, $regionSpaces)) {
            if ($spaceItem['item_purchased'] != null) {
                $date = $spaceItem['time_purchased'];
                $date = date_create($date);
                $date = date_format($date, 'F j, Y') . ' at ' . date_format($date, 'g:i A');
                echo $spaceItem['purchased_description'] . ' in ' . $spaceItem['regionName'] . ' for ' . $dolfmt->formatCurrency($spaceItem['purchased_price'], $currency) .
                    " on $date<br/>\n";
                $totalPrice += $spaceItem['purchased_price'];
            }
        }
    }
    echo "__________________________________________________________<br/>\nTotal price for $regionName spaces " . $dolfmt->formatCurrency($totalPrice, $currency) . "<br/>\n";
    if ($totalPrice >= 0.01) { // suppress receipt button for comp space
        echo "<button class='btn btn-primary m-1' onclick='exhibitorReceipt.showReceipt($regionYearId);'>Show receipt for $regionName space</button>";
    }
}
