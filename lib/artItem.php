<?php
function drawEditPane($tabIndex=100)
{
    /* div
    TODO: Tab Index
    */
    ?>
    <!-- artItem modal -->
    <div id='artItemEditPane' class='modal modal-xl fade' tabindex='-1' aria-labelledby='Art Item Editor'
         aria-hidden='true' style='--bs-modal-width: 50%;'>
        <div class='modal-dialog'>
            <div class='modal-content'>
                <div class='modal-header bg-primary text-bg-primary'>
                    <div class='modal-title' id="artItemEditor_title"><strong>Art Item Editor</strong></div>
                    <button type='button' class='btn-close' data-bs-dismiss='modal' aria-label='Close'></button>
                </div>
                <div class='modal-body' style='padding: 4px; background-color: lightcyan;'>
                    <div class='container-fluid form-floating'>
                        <form id="artItemEditor" method="POST">
                            <div class="row mb-2">
                                <div class="col-sm-auto">
                                    Exhibitor:
                                    <span id="artItemExhibitor"></span>
                                    (<span id="artItemArtistNumber"></span>)
                                </div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-sm-auto">
                                    Show:
                                    <span id="artItemShow"></span>
                                </div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-sm-auto">
                                    Item Number:
                                    <span id="artItemItemNumber"></span> <!--TODO change to input tabIndex+1-->
                                </div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-sm-auto">
                                    Type:
                                    <span id="artItemType"></span> <!--TODO change to select tabindex+2 -->
                                </div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-sm-auto">
                                    <label for="artItemTitle">Title:</label>
                                    <input tabindex="<?php echo $tabIndex+5; ?>" type="text" id="artItemTitle" name="title" size="64" maxlength="64"/>
                                </div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-sm-auto">
                                    <label for="artItemMaterial">Material:</label>
                                    <input tabindex="<?php echo $tabIndex+6; ?>" type="text" id="artItemMaterial" name="material" size='32' maxlength='32'/>
                                </div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-sm-auto">
                                    <label for="artItemStatus">Status:</label>
                                    <select tabindex="<?php echo $tabIndex+7; ?>" id="artItemStatus" name="status"> <!--populate from artItemStatuses-->
                                    </select>
                                </div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-sm-auto">
                                    <label for="artItemLocation">Location:</label>
                                    <select tabindex="<?php echo $tabIndex+8; ?>" id="artItemLocation" name="location">
                                        <!--populate from this artist's locations-->
                                    </select>
                                </div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-sm-auto">
                                    <label for="artItemQuantity">Quantity:</label>
                                    <input tabindex="<?php echo $tabIndex+9; ?>" type="number" id="artItemQuantity" name="quantity"/>
                                    <!--max = original quantity-->
                                    <label for="artItemOrigQty">of (original):</label>
                                    <input tabindex="<?php echo $tabIndex+10; ?>" type="number" id="artItemOrigQty" name="orig_qty"/>
                                </div>
                            </div>
                            <div class="row mb-2" id="minPriceRow">
                                <div class="col-sm-auto">
                                    <label for="artItemMinPrice">Minimum Bid:</label>
                                    <input tabindex="<?php echo $tabIndex+11; ?>" type="number" id="artItemMinPrice" name="min"/>
                                </div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-sm-auto">
                                    <label for="artItemSalePrice" id="artItemSalePriceName">Quicksale/Sale Price/Ins
                                        Price:</label>
                                    <input tabindex="<?php echo $tabIndex+12; ?>" type="number" id="artItemSalePrice" name="sale"/>
                                </div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-sm-auto">
                                    <label for="artItemBidder">Bidder PerId:</label>
                                    <input tabindex="<?php echo $tabIndex+13; ?>" type="number" id="artItemBidder" name="bidder"/>
                                    Name:
                                    <span id="artItemBidderName"></span>
                                </div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-sm-auto">
                                    <label for="artItemFinalPrice">Final Price:</label>
                                    <input tabindex="<?php echo $tabIndex+14; ?>" type="number" id="artItemFinalPrice" name="final"/>
                                    <!--only valid for some statuses -->
                                </div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-sm-auto">
                                    <label for="artItemNotes">Notes:</label>
                                    <textarea cols='70' rows='10' wrap='soft'
                                            tabindex="<?php echo $tabIndex+15; ?>" id="artItemNotes" name="notes"></textarea>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div id='ai_result_message' class='mt-4 p-2'></div>
                </div>
                <div class="modal-footer">
                    <button class='btn btn-sm btn-secondary' data-bs-dismiss='modal'>Cancel</button>
                    <button class='btn btn-sm btn-secondary' onClick="artItemModal.resetEditPane()">Reset</button>
                    <button class='btn btn-sm btn-primary' id='profileSubmitBtn' onClick="artItemModal.updateArtItem()">
                        Update Art Item
                    </button>
                </div>
            </div>
        </div>
    </div>
    <?php
}
