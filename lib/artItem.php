<?php
function drawEditPane()
{
    /* div
    TODO: Tab Index
    */
    ?>
    <!-- artItem modal -->
    <div id='artItemEditPane' class='modal modal-xl fade' tabindex='-1' aria-labelledby='Art Item Editor'
         aria-hidden='true' style='--bs-modal-width: 90%;'>
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
                                <div class="col-sm-2">
                                    Exhibitor:
                                </div>
                                <div class="col-sm-auto" id="artItemExhibitor"></div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-sm-2">
                                    Show:
                                </div>
                                <div class="col-sm-auto" id="artItemShow"></div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-sm-2">
                                    Artist Number:
                                </div>
                                <div class="col-sm-2" id="artItemArtistNumber"></div>
                                <div class="col-sm-2">
                                    Item Number:
                                </div>
                                <div class="col-sm-2" id="artItemItemNumber"></div>
                                <div class="col-sm-2">
                                    Type:
                                </div>
                                <div class="col-sm-2" id="artItemType"></div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-sm-2">
                                    <label for="artItemTitle">Title:</label>
                                </div>
                                <div class="col-sm-auto">
                                    <input type="text" id="artItemTitle" name="title"/>
                                </div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-sm-2">
                                    <label for="artItemMaterial">Material:</label>
                                </div>
                                <div class="col-sm-auto">
                                    <input type="text" id="artItemMaterial" name="material"/>
                                </div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-sm-2">
                                    <label for="artItemStatus">Status:</label>
                                </div>
                                <div class="col-sm-4">
                                    <select id="artItemStatus" name="status"> <!--populate from artItemStatuses-->
                                    </select>
                                </div>
                                <div class="col-sm-2">
                                    <label for="artItemLocation">Location:</label>
                                </div>
                                <div class="col-sm-4">
                                    <select id="artItemLocation" name="location">
                                        <!--populate from this artist's locations-->
                                    </select>
                                </div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-sm-2">
                                    <label for="artItemQuantity">Quantity:</label>
                                </div>
                                <div class="col-sm-4">
                                    <input type="number" id="artItemQuantity" name="quantity"/>
                                    <!--max = original quantity-->
                                </div>
                                <div class="col-sm-2">
                                    <label for="artItemOrigQty">of (original):</label>
                                </div>
                                <div class="col-sm-4">
                                    <input type="number" id="artItemOrigQty" name="orig_qty"/>
                                </div>
                            </div>
                            <div class="row mb-2" id="minPriceRow">
                                <div class="col-sm-2">
                                    <label for="artItemMinPrice">Minimum Bid:</label>
                                </div>
                                <div class="col-sm-auto">
                                    <input type="number" id="artItemMinPrice" name="min"/>
                                </div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-sm-2">
                                    <label for="artItemSalePrice" id="artItemSalePriceName">Quicksale/Sale Price/Ins
                                        Price:</label>
                                </div>
                                <div class="col-sm-auto">
                                    <input type="number" id="artItemSalePrice" name="sale"/>
                                </div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-sm-2">
                                    <label for="artItemBidder">Bidder PerId:</label>
                                </div>
                                <div class="col-sm-4">
                                    <input type="number" id="artItemBidder" name="bidder"/>
                                </div>
                                <div class="col-sm-2">
                                    Name:
                                </div>
                                <div class="col-sm-4" id="artItemBidderName">
                                </div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-sm-2">
                                    <label for="artItemFinalPrice">Final Price:</label>
                                </div>
                                <div class="col-sm-4">
                                    <input type="number" id="artItemFinalPrice" name="final"/>
                                    <!--only valid for some statuses -->
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
                        Submit
                    </button>
                </div>
            </div>
        </div>
    </div>
    <?php
}


?>