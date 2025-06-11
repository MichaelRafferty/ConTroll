<?php
    // exhibitsConfiguration.php
    // functions relating to configuring exhibits space

    // draw_exhibitsConfigurationModals - used for editing rows of configuration tables
function draw_exhibitsConfigurationModals() {
    // regionYear
    ?>
    <div id='exhibitorRegionYearModal' class='modal modal-xl fade' tabindex='-1' aria-labelledby='Exhibits Configuration-Edit Region Year' aria-hidden='true'
         style='--bs-modal-width: 96%;'>
        <div class='modal-dialog'>
            <div class='modal-content'>
                <div class='modal-header bg-primary text-bg-primary'>
                    <div class='modal-title' id="exhibitsRegionYear_editTitle">
                        <strong>Edit Row</strong>
                    </div>
                    <button type='button' class='btn-close' data-bs-dismiss='modal' aria-label='Close'></button>
                </div>
                <div class='modal-body' style='padding: 4px; background-color: lightcyan;'>
                    <div class='container-fluid'>
                        <div class="row mt-4">
                            <div class="col-sm-12">
                                <h1 class="h4" id="eryH1Title">Edit the Region Year</h1>
                            </div>
                        </div>
                        <div class="row mt-2">
                            <div class="col-sm-2">&bigstar;Exhibits Region:</div>
                            <div class="col-sm-auto">
                                <select id="eyrExhibitsRegion" name="eyrExhibitsRegion">
                                    <option value="-1">Select a region</option>
                                </select>
                            </div>
                        </div>
                        <div class="row mt-2">
                            <div class='col-sm-2'>&bigstar;Room Status:</div>
                            <div class='col-sm-auto'>
                                <select id='eryRoomStatus' name='eryRoomStatus'>
                                    <option value='precon'>precon: Pre-Convention-Entered/Checked In Only</option>
                                    <option value='bid'>bids: Bids and QuickSale Allowed</option>
                                    <option value='checkout'>checkout: Bid Sold/Auction Sold and Checkout Allowed</option>
                                    <option value='closed'>closed: Only Checkout Allowed</option>
                                    <option value='all'>all: No Restrictions</option>
                                </select>
                            </div>
                        </div>
                        <div class="row mt-2">
                            <div class="col-sm-2">&bigstar;Owner Name:</div>
                            <div class='col-sm-auto'>
                                <input type="text" id="eyrOwnerName" name="eyrOwnerName" maxlength="64" size="64"/>
                            </div>
                        </div>
                        <div class="row mt-2">
                            <div class='col-sm-2'>&bigstar;Owner Email:</div>
                            <div class='col-sm-auto'>
                                <input type='text' id='eyrOwnerEmail' name='eyrOwnerEmail' maxlength='254' size="64"/>
                            </div>
                        </div>
                        <div class="row mt-2">
                            <div class='col-sm-2'>&bigstar;Included Membership Type:</div>
                            <div class='col-sm-auto'>
                                <select id='eryIncludedMemId' name="eryIncludedMemId">
                                    <option value='-1'>Select a type</option>
                                </select>
                            </div>
                        </div>
                        <div class="row mt-2">
                            <div class='col-sm-2'>&bigstar;Additional Membership Type:</div>
                            <div class='col-sm-auto'>
                                <select id='eryAdditionalMemId' name="eryAdditionalMemId">
                                    <option value='-1'>Select a type</option>
                                </select>
                            </div>
                        </div>
                        <div class="row mt-2">
                            <div class='col-sm-2'>Total Units Available:</div>
                            <div class='col-sm-auto'>
                                <input type='number' class='no-spinners' inputmode='numeric' id='eyrTotalUnits' name='eyrTotalUnits'/>
                            </div>
                        </div>
                        <div class="row mt-2">
                            <div class='col-sm-2'>At Con Base Exhibitor Number:</div>
                            <div class='col-sm-auto'>
                                <input type='number' class='no-spinners' inputmode='numeric' id='eyrAtConBase' name='eyrAtConBase' min="0"/>
                            </div>
                        </div>
                        <div class="row mt-2">
                            <div class='col-sm-2'>Default Space GL Num:</div>
                            <div class='col-sm-auto'>
                                <input type='text' id='eyrGLNum' name='eyrGLNum' maxlength='16' size='24'/>
                            </div>
                        </div>
                        <div class="row mt-2">
                            <div class='col-sm-2'>Default Space GL Label:</div>
                            <div class='col-sm-auto'>
                                <input type='text' id='eyrGLLabel' name='eyrGLLabel' maxlength='64' size='64'/>
                            </div>
                        </div>
                        <div class="row mt-2">
                            <div class='col-sm-2'>Mail In Fee:</div>
                            <div class='col-sm-auto'>
                                <input type='number' class='no-spinners' inputmode='numeric' id='eyrMailInFee' name='eyrMailInFee' min="0"/>
                            </div>
                        </div>
                        <div class="row mt-2">
                            <div class='col-sm-2'>Mail-In Base Exhibitor Number:</div>
                            <div class='col-sm-auto'>
                                <input type='number' class='no-spinners' inputmode='numeric' id='eyrMailInBase' name='eyrMailInBase' min="0"/>
                            </div>
                        </div>
                        <div class="row mt-2">
                            <div class='col-sm-2'>Mail-In Fee GL Num:</div>
                            <div class='col-sm-auto'>
                                <input type='text' id='eyrFeeGLNum' name='eyrFeeGLNum' maxlength='16' size='24'/>
                            </div>
                        </div>
                        <div class="row mt-2">
                            <div class='col-sm-2'>Mail-in Fee GL Label:</div>
                            <div class='col-sm-auto'>
                                <input type='text' id='eyrFeeGLLabel' name='eyrFeeGLLabel' maxlength='64' size='64'/>
                            </div>
                        </div>
                        <div class="row mt-2">
                            <div class="col-sm-12" id="ry_message_div"></div>
                        </div>
                    </div>
                </div>
                <div class='modal-footer'>
                    <button class='btn btn-sm btn-secondary' data-bs-dismiss='modal'>Cancel</button>
                    <button class='btn btn-sm btn-primary' id="saveRYedit" onclick="exhibits.saveRYEdit()">Save back to table</button>
                </div>
            </div>
        </div>
    </div>
    <?php
}
?>
