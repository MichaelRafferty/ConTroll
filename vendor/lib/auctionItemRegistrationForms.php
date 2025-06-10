<?php

//draw the item registration modal
function draw_itemRegistrationModal($portalType = '', $showsheets=false, $showcontrol=false) {
    if($portalType != 'artist') {
        return;
    }

    $vendor = get_conf('vendor');
    $auctionTitle = null;
    $salesTitle = null;
    $nfsTitle = null;

    if (array_key_exists('artistItemAuctionTitle', $vendor))
        $auctionTitle = $vendor['artistItemAuctionTitle'];

    if ($auctionTitle == null || $auctionTitle == '')
        $auctionTitle = 'Art Auction Items';

    if (array_key_exists('artistItemSalesTitle', $vendor))
        $salesTitle = $vendor['artistItemSalesTitle'];

    if ($salesTitle == null || $salesTitle == '')
        $salesTitle = 'Art Sales / Print Shop Items';

    if (array_key_exists('artistItemNFSTitle', $vendor))
        $nfsTitle = $vendor['artistItemNFSTitle'];

    if ($nfsTitle == null || $nfsTitle == '')
        $nfsTitle = 'Display Only / Not For Sale Items';

?>
    <div id='item_registration' class='modal modal-xl fade' tabindex='-1' aria-labelledby='Register Items' aria-hidden='true' style='--bs-modal-width: 96%;'>
        <div class='modal-dialog'>
            <div class='modal-content'>
                <div class='modal-header bg-primary text-bg-primary'>
                    <div class='modal-title' id='item_registration_title'>
                        <strong>Item Registration</strong>
                    </div>
                    <button type='button' class='btn-close' data-bs-dismiss='modal' aria-label='Close'></button>
                </div>
                <div class='modal-body' stype='padding: 4px; background-color: lightcyan;'>
                    <div class='container-fluid'>
                        <?php outputCustomText('items/top');?>
                        <div class='row'> <?php /* art items */ ?>
                            <div class='col-sm-auto'>
                                <h4> Registration for <?php echo $auctionTitle; ?></h4>
                                <div id='artItemTable'>placeholder</div>
                            </div>
                        </div>
                        <div class='row'>
                            <div class='col-sm-auto m-0 p-0' id='art-buttons'>
                                <button id="art-undo" type="button" class="btn btn-secondary btn-sm" onclick="auctionItemRegistration.undoArt(); return false;" disabled>Undo</button>
                                <button id="art-redo" type="button" class="btn btn-secondary btn-sm" onclick="auctionItemRegistration.redoArt(); return false;" disabled>Redo</button>
                                <button id="art-addrow" type="button" class="btn btn-secondary btn-sm" onclick="auctionItemRegistration.addrowArt(); return false;">Add New</button>
                                <button id="art-save" type="button" class="btn btn-primary btn-sm"  onclick="auctionItemRegistration.saveArt(); return false;" disabled>Save Changes</button>
                            </div>
                        </div>
                        <div class='row'> <?php /* print items */ ?>
                            <div class='col-sm-auto'>
                                <h4>Registration for <?php echo $salesTitle; ?></h4>
                                <div id='printItemTable'>placeholder</div>
                            </div>
                        </div>
                        <div class='row'>
                            <div class='col-sm-auto m-0 p-0' id='print-buttons'>
                                <button id="print-undo" type="button" class="btn btn-secondary btn-sm" onclick="auctionItemRegistration.undoPrint(); return false;" disabled>Undo</button>
                                <button id="print-redo" type="button" class="btn btn-secondary btn-sm" onclick="auctionItemRegistration.redoPrint(); return false;" disabled>Redo</button>
                                <button id="print-addrow" type="button" class="btn btn-secondary btn-sm" onclick="auctionItemRegistration.addrowPrint(); return false;">Add New</button>
                                <button id="print-save" type="button" class="btn btn-primary btn-sm"  onclick="auctionItemRegistration.savePrint(); return false;" disabled>Save Changes</button>
                            </div>
                        </div>
                        <div class='row'> <?php /* nfs items */ ?>
                            <div class='col-sm-auto'>
                                <h4>Registration for <?php echo $nfsTitle; ?></h4>
                                <div id='nfsItemTable'>placeholder</div>
                            </div>
                        </div>
                        <div class='row'>
                            <div class='col-sm-auto m-0 p-0' id='nfs-buttons'>
                                <button id="nfs-undo" type="button" class="btn btn-secondary btn-sm" onclick="auctionItemRegistration.undoNfs(); return false;" disabled>Undo</button>
                                <button id="nfs-redo" type="button" class="btn btn-secondary btn-sm" onclick="auctionItemRegistration.redoNfs(); return false;" disabled>Redo</button>
                                <button id="nfs-addrow" type="button" class="btn btn-secondary btn-sm" onclick="auctionItemRegistration.addrowNfs(); return false;">Add New</button>
                                <button id="nfs-save" type="button" class="btn btn-primary btn-sm"  onclick="auctionItemRegistration.saveNfs(); return false;" disabled>Save Changes</button>
                            </div>
                        </div>
                        <?php outputCustomText('items/bottom');?>
                        <hr/>
                        <div class='row'>
                            <div class='col-sm-auto'>
                            <h4>Buttons to print out bidsheets and control sheets.</h4>
                            </div>
                        </div>
                        <div class='row'>
                            <div class='col-sm-auto' id='print_buttons'>
                                <button id='print_bidsheet' type='button' class='btn btn-primary btn-sm' onclick="auctionItemRegistration.printSheets('bidsheets'); return false;">Print Bidsheets</button>
                                <button id='print_printshop' type='button' class='btn btn-primary btn-sm' onclick="auctionItemRegistration.printSheets('printshop'); return false;">Print Sales Tags</button>
                                <button id='print_controlsheet' type='button' class='btn btn-primary btn-sm' onclick="auctionItemRegistration.printSheets('control'); return false;">Print Control Sheet</button>
                            </div>
                        </div>
                        <div class='row mt-2' id='ir_message_div'></div>
                    </div>
                </div>
                <div class='modal-footer'>
                </div>
            </div>
        </div>
    </div>
<?php
}


function itemRegistrationOpenBtn($region) {
    echo "<button class='btn btn-primary m-1' onclick='auctionItemRegistration.open($region);'>Open Item Registration</button>";
}


?>
