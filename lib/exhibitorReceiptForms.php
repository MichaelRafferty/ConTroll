<?php

// draw the vendor request modal
function draw_exhibitorReceiptModal($portalType = '')
{
    $exhibitor_conf = get_conf('vendor');
    ?>
    <!-- request -->
    <div id='exhibitor_receipt' class='modal modal-xl fade' tabindex='-1' aria-labelledby='Receipt For Space' aria-hidden='true'
         style='--bs-modal-width: 96%;'>
        <div class='modal-dialog'>
            <div class='modal-content'>
                <div class='modal-header bg-primary text-bg-primary'>
                    <div class='modal-title' id="exhibitor_receipt_title">
                        <strong>Exhibitor Space Receipt</strong>
                    </div>
                    <button type='button' class='btn-close' data-bs-dismiss='modal' aria-label='Close'></button>
                </div>
                <div class='modal-body' style='padding: 4px; background-color: lightcyan;'>
                    <div class='container-fluid'>
                        <div id="receiptHtml"></div>
                        <div class='row' id='receipt_message_div'></div>
                    </div>
                </div>
                <div class='modal-footer'>
                    <button class='btn btn-sm btn-secondary' data-bs-dismiss='modal'>Dismiss</button>
                    <div id="repeciotEmailBtns"></div>
                </div>
            </div>
        </div>
    </div>
    <?php
}
