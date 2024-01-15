
<?php

// draw the vendor request modal
function draw_vendorReqModal() {
    $vendor_conf = get_conf('vendor');
    ?>
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
                            <?php
                            if (array_key_exists('req_disclaimer',$vendor_conf) && $vendor_conf['req_disclaimer'] != '') {
                                ?>                          <div class='row p-1 pt-4 pb-3'>
                                    <div class='col-sm-12'>
                                        <?php echo $vendor_conf['req_disclaimer'] . "\n"; ?>
                                    </div>
                                </div>
                                <?php
                            }
                            ?>
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
    <?php
}
