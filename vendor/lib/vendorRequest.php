<?php

// draw the vendor request modal
function draw_vendorReqModal()
{
    $vendor_conf = get_conf('vendor');
    ?>
    <!-- request -->
    <div id='vendor_req' class='modal modal-xl fade' tabindex='-1' aria-labelledby='Request $spacetitle Space' aria-hidden='true'
         style='--bs-modal-width: 96%;'>
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
                            <div id="spaceHtml"></div>
                            <div class='row p-1 pt-4 pb-3'>
                                <div class='col-sm-12'>
                                    You will be able to identify people for the included memberships (if any) and purchase up to the allowed number of
                                    discounted memberships later, if your request is
                                    approved.
                                </div>
                            </div>
                            <?php
                            if (array_key_exists('req_disclaimer', $vendor_conf) && $vendor_conf['req_disclaimer'] != '') {
                                $discfile = '../config/' . $vendor_conf['req_disclaimer'];
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
                            ?>
                            <div class='row p-0 bg-warning'>
                                <div class='col-sm-auto p-2'>Completing this application does not guarantee space.</div>
                            </div>
                        </form>
                        <div class="row" id="sr_message_div"></div>
                    </div>
                </div>
                <div class='modal-footer'>
                    <button class='btn btn-sm btn-secondary' data-bs-dismiss='modal'>Cancel</button>
                    <button class='btn btn-sm btn-primary' id='vendor_req_btn' onClick="spaceReq(0, 0)">Request Vendor Space</button>
                </div>
            </div>
        </div>
    </div>
    <?php
}

// vendor_showRequest -> show the current request and the change/cancel button
function vendor_showRequest($regionId, $regionName, $regionSpaces, $exhibitorSpaceList) {
    $dolfmt = new NumberFormatter('', NumberFormatter::CURRENCY);

    echo "Request pending authorization for:<br/>\n";
    foreach ($exhibitorSpaceList as $key => $spaceItem) {
        // limit to spaces for this region
        $spaceId = $spaceItem['spaceId'];
        if (array_key_exists($spaceId, $regionSpaces)) {
            $date = $spaceItem['time_requested'];
            $date = date_create($date);
            $date = date_format($date, 'F j, Y') . ' at ' . date_format($date, 'g:i A');
            echo $spaceItem['requested_description'] . " in " . $spaceItem['regionName'] . " for " . $dolfmt->formatCurrency($spaceItem['requested_price'], 'USD') .
                " at $date<br/>\n";
        }
    }
    echo "<button class='btn btn-primary' onclick='openReq($regionId, 1);'>Change/Cancel $regionName Space</button>";

}
