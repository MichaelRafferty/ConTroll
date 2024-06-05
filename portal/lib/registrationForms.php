<?php
// draw_login - draw the login options form
function draw_login($config_vars, $result_message = '') {
    ?>

 <!-- signin form (at body level) -->
    <div id='signin'>
        <div class='container-fluid form-floating'>
            <div class='row mb-2'>
                <div class='col-sm-auto'>
                    <h4>Please log in to continue to the Portal.</h4>
                </div>
            </div>
            <div class="row mb-2">
                <div class='col-sm-auto'>
                    <button class="btn btn-sm btn-primary" onclick="loginWithToken();">Login with Authentication Link via Email</button>
                </div>
            </div>
            <div id='token_email_div' hidden>
                <div class='row mt-1'>
                    <div class='col-sm-1'>
                        <label for='token_email'>*Email: </label>
                    </div>
                    <div class='col-sm-auto'>
                        <input class='form-control-sm' type='email' name='token_email' id='token_email' size='40' onchange='tokenEmailChanged();' required/>
                    </div>
                </div>
                <div class='row mt-2 mb-2'>
                    <div class='col-sm-1'></div>
                    <div class='col-sm-auto'>
                        <button type='button' class='btn btn-primary btn-sm' id='sendLinkBtn' onclick='sendLink();' disabled>Send Link</button>
                    </div>
                </div>
            </div>
            <div class='row mb-2'>
                <div class='col-sm-auto'>
                    <button class='btn btn-sm btn-primary' onclick='loginWithGoogle();'>Login with Google</button>
                </div>
            </div>
            <?php
            // bypass for testing on Development PC
    if (stripos(__DIR__, '/Users/syd/') !== false && $_SERVER['SERVER_ADDR'] == '127.0.0.1') {
                ?>
            <div class="row mt-3><div class="col-sm-12"><hr></div></div>
            <div class='row mt-2'>
                <div class='col-sm-auto'>
                    <label for='dev_email'>*Dev Email/Perid/Newperid: </label>
                </div>
                <div class='col-sm-auto'>
                    <input class='form-control-sm' type='email' name='dev_email' id='dev_email' size='40' required/>
                </div>
                <div class='col-sm-auto'>
                    <button type="button" class='btn btn-sm btn-primary' onclick='loginWithEmail();'>Login to Development</button>
                </div>
            </div>
            <div class='row mb-2'><div class="col-sm-12" id="matchList"></div></div>
            <?php
    } ?>
        </div>
    </div>
    <div class='container-fluid'>
        <div class='row'>
            <div class='col-sm-12 m-0 p-0'>
                <div id='result_message' class='mt-4 p-2'><?php echo $result_message; ?></div>
            </div>
        </div>
    </div>
    </body>
    <script type='text/javascript'>
        var config = <?php echo json_encode($config_vars); ?>;
    </script>
</html>
<?php
}
