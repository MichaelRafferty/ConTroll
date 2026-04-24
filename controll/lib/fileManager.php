<?php
    // fileManager.php
    // functions relating to displaying, uploading, or deleting files

    // draw_fileManagerModals
    function draw_fileManagerModals($authToken) {
        $admin = $authToken->checkAuth('admin');
        if ($admin) {
            echo <<<EOS
<div id='fm_RenameDelete' class='modal modal-xl fade' tabindex='-1' aria-labelledby='Rename or Delete File' 
    aria-hidden='true' style='--bs-modal-width: 80%;'>
    <div class='modal-dialog'>
        <div class='modal-content'>
            <div class='modal-header bg-primary text-bg-primary'>
                <div class='modal-title'>
                    <strong id='fm_rdTitle'>Rename/Delete Filename</strong>
                </div>
                <button type='button' class='btn-close' data-bs-dismiss='modal' aria-label='Close'></button>
            </div>
            <div class='modal-body' style='padding: 4px; background-color: lightcyan;'>
                <div class='container-fluid'>
                     <div class="row mt-2 mb-4">
                         <div class="col-sm-2"></div>
                         <div class="col-sm-auto"><h1 class="size-h3" id="fm_renameHeading"></h1></div>
                     </div>
                     <div class="row mt-1 mb-1">
                        <div class="col-sm-2 text-end">
                            <button id="fm_renameBtn" class="btn btn-small btn-primary" onclick="fileManager.rename();">Rename</button>
                        </div>
                        <div class="col-sm-auto align-self-center">New Name:</div>
                        <div class="col-sm-auto align-self-center">
                            <input type="text" size="64" maxlength="128" id="fm_newName" placeholder="New File Name (letters, numbers and _-. only">
                        </div>
                    </div>
                    <div class="row mt-1 mb-1">
                        <div class="col-sm-2 text-end">
                            <button id="fm_deleteBtn" class="btn btn-small btn-warning" onclick="fileManager.delete();">Delete File</button>
                        </div>
                        <div class="col-sm-auto text-end align-self-center">Delete the file, this action cannot be undone.</div>
                    </div>
                </div>
                <div id='result_message_fm_rd' class='mt-4 p-2'></div>
            </div>
            <div class='modal-footer'>
                <button class='btn btn-sm btn-secondary' data-bs-dismiss='modal'>Cancel</button>
            </div>
          
        </div>
    </div>
</div>
EOS;
        }
        echo <<<EOS
<div id='fm_upload' class='modal modal-xl fade' tabindex='-1' aria-labelledby='Upload File' aria-hidden='true' style='--bs-modal-width: 80%;'>
    <div class='modal-dialog'>
        <div class='modal-content'>
            <div class='modal-header bg-primary text-bg-primary'>
                <div class='modal-title'>
                    <strong id='fm_uploadTitle'>Upload File</strong>
                </div>
                <button type='button' class='btn-close' data-bs-dismiss='modal' aria-label='Close'></button>
            </div>
            <div class='modal-body' style='padding: 4px; background-color: lightcyan;'>
                <div class='container-fluid'>
                    <div class="row mt-2 mb-4">
                        <div class="col-sm-auto ms-4"><h1 class="size-h3" id="fm_uploadHeading">upload heading</h1></div>
                    </div>
                    <div class="row mt-1 mb-1">
                        <div class="col-sm-auto ms-4 card alert-secondary">
                            <input type="file" id="fm_chooseFileName" name="fm_chooseFileName"
                             accept="image/png, image/jpeg, image/jpg, application/pdf', text/csv, 
                             application/vnd.openxmlformats-officedocument.spreadsheetml.sheet, application/vnd.ms-excel"
                             style="display: none">
                            <p class="card-title">Upload Image: Drag/Drop file or 
                                <button type="button" class="btn btn-secondary btn-sm" id="fm_uploadChooseBtn">Choose File</button>
                            </p>
                            <div class="card-body" id="fmUploadArea" style="margin-right: auto; margin-left: auto; margin-top:0;">
                                <input type="hidden" name="defaultPhoto" id="default_photo" value="1">
                                <img class="upload-image" style="width: 600px; height: 600px; object-fit: scale-down; 
                                    margin-top:0; margin-right: auto; margin-left: auto;" id="fm_uploadedPhoto" src="lib/uploadArea.jpg">
                            </div>
                        </div>
                    </div>                         
                </div>
                <div class="row mt-1 mb-1">
                    <div class="col-sm-auto ms-4">
                        <button type="button" class="btn btn-primary btn-sm" id="fm_uploadFile" style="display: block;"
                            onclick="fileManager.startTransfer(); disabled">
                            Upload Image/File
                        </button>
                    </div>
                </div>
                <div id='result_message_fm_up' class='mt-4 p-2'></div>
            </div>
            <div class='modal-footer'>
                <button class='btn btn-sm btn-secondary' data-bs-dismiss='modal'>Cancel</button>
            </div>
        </div>
    </div>
</div>
EOS;
    }

    // draw_fileManager - draw the base file manager area
    function draw_fileManager($authToken) {
        $admin = $authToken->checkAuth('admin');
        $reg_staff = $authToken->checkAuth('reg_staff');
        $regAdmin = $authToken->checkAuth('reg_admin');
        $exhibitor = $authToken->checkAuth('exhibitor');
        $finance = $authToken->checkAuth('finance');
        if ($admin) {
            echo <<<EOS
                <div class='row mt-4 mb-2'>
                    <div class='col-sm-auto'><h3>Controll Back End Images</h3></div>
                    <div class="col-sm-auto">
                        <button class="btn btn-sm btn-secondary" id="controllShow" onclick="fileManager.toggleShowHide('controll');">Hide</button>
                    </div>
                    <div class="col-sm-auto">
                        <button class="btn btn-sm btn-secondary" id="controllUpload" onclick="fileManager.showUpload('controll');">Upload New Image</button>
                    </div>
                </div>
                <div class='row' id="controllImagePreview"></div>
EOS;
        }
        if ($admin || $finance) {
            echo <<<EOS
                <div class='row mt-4 mb-2'>
                    <div class='col-sm-auto'><h3>Report Data Files</h3></div>
                    <div class="col-sm-auto">
                        <button class="btn btn-sm btn-secondary" id="reportShow" onclick="fileManager.toggleShowHide('report');">Hide</button>
                    </div>
                    <div class="col-sm-auto">
                        <button class="btn btn-sm btn-secondary" id="reportUpload" onclick="fileManager.showUpload('report');">Upload New File</button>
                    </div>
                </div>
                <div class='row' id="reportDataFiles"></div>
EOS;
        }
        if ($admin || $reg_staff) {
            echo <<<EOS
                <div class='row mt-4 mb-2'>
                    <div class='col-sm-auto'><h3>Online Reg Images</h3></div>
                    <div class="col-sm-auto">
                        <button class="btn btn-sm btn-secondary" id="onlineShow" onclick="fileManager.toggleShowHide('online');">Hide</button>
                    </div>
                    <div class="col-sm-auto">
                        <button class="btn btn-sm btn-secondary" id="onlineUpload" onclick="fileManager.showUpload('online');">Upload New Image</button>
                    </div>
                </div>
                <div class='row' id="onlineRegImagePreview"></div>
EOS;
            echo <<<EOS
                <div class='row mt-4 mb-2'>
                    <div class='col-sm-auto'><h3>Portal Reg Images</h3></div>
                    <div class="col-sm-auto">
                        <button class="btn btn-sm btn-secondary" id="portalShow" onclick="fileManager.toggleShowHide('portal');">Hide</button>
                    </div>
                    <div class="col-sm-auto">
                        <button class="btn btn-sm btn-secondary" id="portalUpload" onclick="fileManager.showUpload('portal');">Upload New Image</button>
                    </div>
                </div>
                <div class='row' id="portalImagePreview"></div>
EOS;
        }
        if ($admin || $exhibitor) {
            echo <<<EOS
                <div class='row mt-4 mb-2'>
                    <div class='col-sm-auto'><h3>Exhibitor Images</h3></div>
                    <div class="col-sm-auto">
                        <button class="btn btn-sm btn-secondary" id="exhibitorShow" onclick="fileManager.toggleShowHide('exhibitor');">Hide</button>
                    </div>
                    <div class="col-sm-auto">
                        <button class="btn btn-sm btn-secondary" id="exhibitorUpload" onclick="fileManager.showUpload('exhibitor');">Upload New Image</button>
                    </div>
                </div>
                <div class='row' id="exhibitorImagePreview"></div>
EOS;
        }
    }
