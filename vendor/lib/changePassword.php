<?php

// drawChangePassword - make it common code to draw change password prompts
function drawChangePassword($title, $width, $drawbutton) {
    global $config_vars;

    $html = '';
    if ($title != '') {
        $html = <<<EOH
    <div class='row'>
        <div class='col-sm-12'>$title</div>
    </div>
EOH;
        }
    $html .= <<<EOH
    <div class='container-fluid'>
        <form id='changepw' action='javascript:void(0)'>
        <div class='row'>
            <div class='col-sm-$width'>
                <label for='oldPw'>Old or Temp Password:</label>
            </div>
            <div class='col-sm-8'>
                <input type='password' id='oldPw' name='oldPassword' size="24" autocomplete="off" required/>
            </div>
        </div>
        <div class='row'>
            <div class='col-sm-$width'>
                <label for='newPw'>New Password:</label>
            </div>
            <div class='col-sm-8'>
                <input type='password' id='newPw' name='password' size="24" autocomplete="off" required placeholder="minimum of 8 characters"/>
            </div>
        </div>
        <div class='row'>
            <div class='col-sm-$width'>
                <label for='newPw2'>Re-enter New Password:</label>
            </div>
            <div class='col-sm-8'>
                <input type='password' id='newPw2' name='password2' size="24" autocomplete="off" required placeholder="re-enter the password"/>
            </div>
        </div>
EOH;
    if ($drawbutton) {
        $cv = json_encode($config_vars);
        $html .= <<<EOH
        <div class='row mt-2'>
            <div class='col-sm-$width'></div>
            <div class='col-sm-8'>
                <button class='btn btn-sm btn-primary' onClick='changePassword()'>Change Password</button>
            </div>
        </div>
        </form>
        <div class="row">
            <div class="col-sm-12 m-0 p-0">
                <div id='result_message' class='mt-4 p-2'></div>
            </div>
        </div>
    </div>
    </body>
    <script type='text/javascript'>
        var config = $cv;
    </script>
</html>
EOH;
    } else {
        $html .= <<<EOH
        </form>
    </div>
EOH;
    }
    echo $html;
    //vendor_page_footer();
}

// draw the password modal
function draw_passwordModal() {
    // modals for each section
    ?>
    <!-- Change Password -->
    <div id='changePassword' class='modal modal-xl fade' tabindex='-1' aria-labelledby='Change Vendor Account Password' aria-hidden='true'>
        <div class='modal-dialog'>
            <div class='modal-content'>
                <div class='modal-header bg-primary text-bg-primary'>
                    <div class='modal-title'>
                        <strong id="changePasswordTitle">Change Vendor Account Password</strong>
                    </div>
                    <button type='button' class='btn-close' data-bs-dismiss='modal' aria-label='Close'></button>
                </div>
                <div class='modal-body' style='padding: 4px; background-color: lightcyan;'>
                    <?php drawChangePassword('', 4, false);
                    ?>
                </div>
                <div class='modal-footer'>
                    <button class='btn btn-sm btn-secondary' data-bs-dismiss='modal'>Cancel</button>
                    <button class='btn btn-sm btn-primary' onClick='changePassword()'>Change Password</button>
                </div>
            </div>
        </div>
    </div>
    <?php
}
