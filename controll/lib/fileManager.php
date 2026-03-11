<?php
    // fileManager.php
    // functions relating to displaying, uploading, or deleting files

    // draw_fileManager() - draw the base file manager area
    function draw_fileManager($authToken) {
        $admin = $authToken->checkAuth('admin');
        $reg_staff = $authToken->checkAuth('reg_staff');
        $regAdmin = $authToken->checkAuth('reg_admin');
        $exhibitor = $authToken->checkAuth('exhibitor');
        $finance = $authToken->checkAuth('finance');
        if ($admin) {
            echo <<<EOS
                <div class='row mb-4'>
                    <div class='col-sm-auto'><h3>Controll Back End Images</h3></div>
                    <div class="col-sm-auto">
                        <button class="btn btn-sm btn-secondary" id="controllShow" onclick="fileManager.toggleShowHide('controll');">Hide</button>
                    </div>
                </div>
                <div class='row' id="controllImagePreview"></div>
EOS;
        }
        if ($admin || $finance) {
            echo <<<EOS
                <div class='row mb-4'>
                    <div class='col-sm-auto'><h3>Report Data Files</h3></div>
                    <div class="col-sm-auto">
                        <button class="btn btn-sm btn-secondary" id="reportShow" onclick="fileManager.toggleShowHide('report');">Hide</button>
                    </div>
                </div>
                <div class='row' id="reportDataFiles"></div>
EOS;
        }
        if ($admin || $reg_staff) {
            echo <<<EOS
                <div class='row mb-4'>
                    <div class='col-sm-auto'><h3>Online Reg Images</h3></div>
                    <div class="col-sm-auto">
                        <button class="btn btn-sm btn-secondary" id="onlineShow" onclick="fileManager.toggleShowHide('online');">Hide</button>
                    </div>
                </div>
                <div class='row' id="onlineRegImagePreview"></div>
EOS;
            echo <<<EOS
                <div class='row mb-4'>
                    <div class='col-sm-auto'><h3>Portal Reg Images</h3></div>
                    <div class="col-sm-auto">
                        <button class="btn btn-sm btn-secondary" id="portalShow" onclick="fileManager.toggleShowHide('portal');">Hide</button>
                    </div>
                </div>
                <div class='row' id="portalImagePreview"></div>
EOS;
        }
        if ($admin || $exhibitor) {
            echo <<<EOS
                <div class='row mb-4'>
                    <div class='col-sm-auto'><h3>Exhibitor Images</h3></div>
                    <div class="col-sm-auto">
                        <button class="btn btn-sm btn-secondary" id="exhibitorShow" onclick="fileManager.toggleShowHide('exhibitor');">Hide</button>
                    </div>
                </div>
                <div class='row' id="exhibitorImagePreview"></div>
EOS;
        }
    }
