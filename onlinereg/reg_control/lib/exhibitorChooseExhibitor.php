<?php
    // exhibitorChooseExhibitor
    // functions relating to choosing an exhibitor to work on for the mail in and other options

    // draw_exhibitorChooseModal(
function draw_exhibitorChooseModal() {
    $exhibitor_conf = get_conf('vendor');
    ?>
    <!-- request -->
    <div id='exhibitor_choose' class='modal modal-xl fade' tabindex='-1' aria-labelledby='Choose Exhibitor for Mail-In Functions' aria-hidden='true'
         style='--bs-modal-width: 96%;'>
        <div class='modal-dialog'>
            <div class='modal-content'>
                <div class='modal-header bg-primary text-bg-primary'>
                    <div class='modal-title' id="exhibitor_choose_title">
                        <strong>Choose Exhibitor</strong>
                    </div>
                    <button type='button' class='btn-close' data-bs-dismiss='modal' aria-label='Close'></button>
                </div>
                <div class='modal-body' style='padding: 4px; background-color: lightcyan;'>
                    <div class='container-fluid'>
                        <div class="row">
                            <div class="col-sm-12" id="exhibitorHtml"></div>
                        </div>
                        <div class="row">
                            <div class="col-sm-12" id="ce_message_div"></div>
                        </div>
                    </div>
                </div>
                <div class='modal-footer'>
                    <button class='btn btn-sm btn-secondary' data-bs-dismiss='modal'>Cancel</button>
                    <button class='btn btn-sm btn-primary' id='exhibitor_choose_btn' onClick="chooseExhibitor()">Choose which Exhibitor to process</button>
                </div>
            </div>
        </div>
    </div>
    <?php
}
?>
