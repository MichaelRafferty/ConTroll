<?php

//draw the item registration modal
function draw_itemRegistrationModal($portalType = '') {
    if($portalType != 'artist') {
        return;
    }
?>
    <div id='item_registration' class='modal modal-x1 fade' tabindex='-1' aria-labelledby='Register Items' aria-hidden='true' style='--bs-modal-width: 96%;'>
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
                        <div class='row' id='ir_message_div'></div>
                    </div>
                </div>
                <div class='modal-footer'>
                </div>
            </div>
        </div>
    </div>
<?php
}

?>
