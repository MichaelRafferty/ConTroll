<?php

// draw the vendor request modal
function draw_exhibitorRequestModal($portalType = '')
{
    $exhibitor_conf = get_conf('vendor');
    switch ($portalType) {
        case 'artist':
            $portalName = 'Artist';
            break;
        case 'exhibitor':
            $portalName = 'Exhibitor';
            break;
        case 'fan':
            $portalName = 'Fan';
            break;
        default:
            $portalName = 'Vendor';
            break;
    }
    ?>
    <!-- request -->
    <div id='exhibitor_req' class='modal modal-xl fade' tabindex='-1' aria-labelledby='Request Exhibitor Space' aria-hidden='true'
         style='--bs-modal-width: 96%;'>
        <div class='modal-dialog'>
            <div class='modal-content'>
                <div class='modal-header bg-primary text-bg-primary'>
                    <div class='modal-title' id="exhibitor_req_title">
                        <strong><?php echo $portalName;?> Space Request</strong>
                    </div>
                    <button type='button' class='btn-close' data-bs-dismiss='modal' aria-label='Close'></button>
                </div>
                <div class='modal-body' style='padding: 4px; background-color: lightcyan;'>
                    <div class='container-fluid'>
                        <form id='exhibitor_req_form' action='javascript:void(0)'>
                            <?php if ($portalType == '') { ?>
                            <div class='row p-0 bg-warning'>
                                <div class='col-sm-12 p-2'>
                                    Please make sure your profile contains a good description of what you will be vending and a link for our staff to see what
                                    you sell if at all possible.
                                </div>
                            </div>
                            <?php } outputCustomText('request/top'); outputCustomText('request/top' . $portalName); ?>
                            <div class="container-fluid p-0 m-0" id="spaceHtml"></div>
                            <div class='row p-1 pt-4 pb-3'>
                                <div class='col-sm-12'>
                                    You will be able to identify people for the included memberships (if any) and purchase up to the allowed number of
                                    discounted memberships later, if your request is approved.
                                </div>
                            </div>
                            <?php
                            if (array_key_exists('req_disclaimer', $exhibitor_conf) && $exhibitor_conf['req_disclaimer'] != '') {
                                $discfile = '../config/' . $exhibitor_conf['req_disclaimer'];
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
                            }
                            if ($portalType == '') {
                            ?>
                            <div class='row p-0 bg-warning'>
                                <div class='col-sm-auto p-2'>Completing this application does not guarantee space.</div>
                            </div>
                            <?php } ?>
                        </form>
                        <?php outputCustomText('request/bottom'); outputCustomText('request/bottom' . $portalName); ?>
                        <div class="row">
                            <div class="col-sm-12" id="sr_message_div"></div>
                        </div>
                    </div>
                </div>
                <div class='modal-footer'>
                    <button class='btn btn-sm btn-secondary' data-bs-dismiss='modal'>Cancel</button>
                    <button class='btn btn-sm btn-primary' id='exhibitor_req_btn' onClick="spaceReq(0, 0)">Request <?php echo $portalName;?> Space</button>
                </div>
            </div>
        </div>
    </div>
    <?php
}

// exhibitor_showRequest -> show the current request and the change/cancel button
function exhibitor_showRequest($regionId, $regionName, $regionSpaces, $exhibitorSpaceList) {
    $curLocale = locale_get_default();
    $dolfmt = new NumberFormatter($curLocale == 'en_US_POSIX' ? 'en-us' : $curLocale, NumberFormatter::CURRENCY);

    $con = get_conf('con');
    if (array_key_exists('currency', $con)) {
        $currency = $con['currency'];
    } else {
        $currency = 'USD';
    }
    echo "Request pending authorization for:<br/>\n";
    foreach ($exhibitorSpaceList as $key => $spaceItem) {
        // limit to spaces for this region
        $spaceId = $spaceItem['spaceId'];
        if (array_key_exists($spaceId, $regionSpaces)) {
            if ($spaceItem['item_requested'] != null) {
                $date = $spaceItem['time_requested'];
                $date = date_create($date);
                $date = date_format($date, 'F j, Y') . ' at ' . date_format($date, 'g:i A');
                echo $spaceItem['requested_description'] . " in " . $spaceItem['regionName'] . " for " .
                    $dolfmt->formatCurrency($spaceItem['requested_price'], $currency) . " at $date<br/>\n";
            }
        }
    }
    echo "<button class='btn btn-primary' onclick='exhibitorRequest.openReq($regionId, 1);'>Change/Cancel $regionName Space</button>";

}
