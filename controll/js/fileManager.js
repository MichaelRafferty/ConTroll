// file manager javascript

class FileManager {
    // section fields
    #controllPreview = null;
    #reportPreview = null;
    #onlinePreview = null;
    #portalPreview = null;
    #exhibitorPreview = null;
    
    // section permissions
    #controll = null;
    #report = null;
    #online = null;
    #portal = null;
    #exhibitor = null;

    // overall permissions
    #admin = false;
    #reg_admin = false;
    #reg_staff = false;
    #reg_finance = false;
    #exhibitorRole = false;

    #controllButton = null;
    #reportButton = null;
    #onlineButton = null;
    #portalButton = null;
    #exhibitorButton = null;

    #controllShow = true;
    #reportShow = true;
    #onlineShow = true;
    #portalShow = true;
    #exhibitorShow = true;

    // rename delete fields
    #renameDeleteModal = null;
    #renameDeleteTitle = null
    #currentDirectory = '';
    #currentFile = '';
    #currentNameTxt = '';
    #renameHeading = null;
    #fm_renameBtn = null;
    #fm_deleteBtn = null;

    // upload file fields
    #uploadModal = null;
    #uploadTitle = null;
    #uploadHeading = null;
    #uploadChooseFileName = null;
    #uploadChooseBtn = null;
    #uploadBtn = null;
    #uploadZone = null;
    #uploadImage = null;
    #uploadBuffer = null;
    #uploadFileName = null;
    #defaultUploadTarget = 'lib/uploadArea.jpg';
    #uploadEventListenerSet = false;

    constructor() {
        this.#controllPreview = document.getElementById("controllImagePreview");
        this.#controllButton = document.getElementById("controllShow");
        this.#controll = this.#controllPreview != null;
        this.#reportPreview = document.getElementById("reportDataFiles");
        this.#reportButton = document.getElementById("reportShow");
        this.#report = this.#reportPreview != null;
        this.#onlinePreview = document.getElementById("onlineRegImagePreview");
        this.#onlineButton = document.getElementById("onlineShow");
        this.#online = this.#onlinePreview != null;
        this.#portalPreview = document.getElementById("portalImagePreview");
        this.#portalButton = document.getElementById("portalShow");
        this.#portal = this.#portalPreview != null;
        this.#exhibitorPreview = document.getElementById("exhibitorImagePreview");
        this.#exhibitorButton = document.getElementById("exhibitorShow");
        this.#exhibitor = this.#exhibitorPreview != null;

        var id = document.getElementById('fm_RenameDelete');
        if (id != null) {
            this.#renameDeleteModal = new bootstrap.Modal(id, {focus: true, backdrop: 'static'});
            this.#renameDeleteTitle = document.getElementById("fm_rdTitle");
            this.#currentNameTxt = document.getElementById("fm_newName");
            this.#renameHeading = document.getElementById("fm_renameHeading");
            this.#fm_renameBtn = document.getElementById("fm_renameBtn");
            this.#fm_deleteBtn = document.getElementById("fm_deleteBtn");
        }

        var id = document.getElementById('fm_upload');
        if (id != null) {
            this.#uploadModal = new bootstrap.Modal(id, {focus: true, backdrop: 'static'});
            this.#uploadTitle = document.getElementById("fm_uploadTitle");
            this.#uploadChooseBtn = document.getElementById("fm_uploadChooseBtn");
            this.#uploadChooseFileName = document.getElementById("fm_chooseFileName");
            this.#uploadZone = document.getElementById("fmUploadArea");
            this.#uploadImage = document.getElementById("fm_uploadedPhoto");
            this.#uploadBtn = document.getElementById("fm_uploadFile");
        }
    }

    // get / set functions
    setChosenFileName(name) {
        this.#uploadChooseFileName.value = name;
    }

    setDark() {
        this.#uploadZone.classList.remove('alert-secondary');
        this.#uploadZone.classList.add('alert-dark');
    }

    setSecondary() {
        this.#uploadZone.classList.remove('alert-dark');
        this.#uploadZone.classList.add('alert-secondary');
    }

    setUploadDisabled(state) {
        this.#uploadBtn.disabled = state;
    }

    setUploadPhotoSrc(name) {
        if (name == null)
            name = this.#defaultUploadTarget;
        this.#uploadImage.src = name;
        this.#uploadBtn.disabled = false;
        let html = "Upload " + this.#currentFile;
        if (this.#currentFile != this.#uploadFileName) {
            html += ' as ' + this.#uploadFileName;
        }
        this.#uploadBtn.innerHTML = html;
    }

    setUploadBuffer(contents) {
        this.#uploadBuffer = contents;
    }

    clickChooseFileName() {
        this.#uploadChooseFileName.click();
    }

    open() {
        if (this.#controll) {
            this.#controllPreview.innerHTML = '';
            this.#controllButton.innerHTML = 'Hide';
            this.#controllPreview.hidden = false;
        }
        if (this.#report) {
            this.#reportPreview.innerHTML = '';
            this.#reportButton.innerHTML = 'Hide';
            this.#reportPreview.hidden = false;
        }
        if (this.#online) {
            this.#onlinePreview.innerHTML = '';
            this.#onlineButton.innerHTML = 'Hide';
            this.#onlinePreview.hidden = false;
        }
        if (this.#portal) {
            this.#portalPreview.innerHTML = '';
            this.#portalButton.innerHTML = 'Hide';
            this.#portalPreview.hidden = false;
        }
        if (this.#exhibitor) {
            this.#exhibitorPreview.innerHTML = '';
            this.#exhibitorButton.innerHTML = 'Hide';
            this.#exhibitorPreview.hidden = false;
        }

        let script = 'scripts/filemgr_getLists.php';
        let postData = {
            load_type: 'all',
            action: 'load',
        }
        clearError();
        clear_message();
        $.ajax({
            url: script,
            method: 'POST',
            data: postData,
            success: function (data, textStatus, jhXHR) {
                if (data.error) {
                    show_message(data.error, 'error');
                    return;
                }
                checkRefresh(data);
                if (data.warn) {
                    show_message(data.warn, 'warn');
                    return;
                }
                if (data.success)
                    show_message(data.success, 'success');
                fileManager.drawFileManager(data);
            },
            error: function (jqXHR, textStatus, errorThrown) {
                showError("ERROR in open: " + textStatus, jqXHR);
            },
        });
    }

    // draw the initial screen
    drawFileManager(data) {
        console.log(data);

        // set permissions
        this.#admin = data.admin;
        this.#reg_admin = data.reg_admin;
        this.#reg_staff = data.reg_staff;
        this.#reg_finance = data.finance;
        this.#exhibitorRole = data.exhibitorRole;

        // controll images
        if (data.controll) {
            this.#controllPreview.innerHTML = this.buildFileList(data.controllFiles, true);
        }

        // report data files
        if (data.report) {
            this.#reportPreview.innerHTML = this.buildFileList(data.reportFiles, false);
        }

        // online reg images
        if (data.online) {
            this.#onlinePreview.innerHTML = this.buildFileList(data.onlineFiles, true);
        }

        // portal reg images
        if (data.portal) {
            this.#portalPreview.innerHTML = this.buildFileList(data.portalFiles, true);
        }

        // online reg images
        if (data.exhibitor) {
            this.#exhibitorPreview.innerHTML = this.buildFileList(data.exhibitorFiles, true);
        }
    }

    buildFileList(data, preview) {
        let html = '';
        let files = Object.keys(data);
        if (preview) {
            html = '<div class="row">\n';
            for (let file of files) {
                let element = data[file];
                html += '<div class="col-sm-auto mb-4 me-4 align-self-end">\n';
                if (preview) {
                    html += '<img src="' + element.path + '" style="max-height:200px;max-width:200px;height:auto;width:auto;"><br/>\n';
                }
                html += file + '(' + element.size + ')<br/>c: ' + element.created +
                    '<br/>m: ' + element.modified + '\n';
                if (this.#admin)
                    html += '<br/><button class="btn btn-small btn-secondary" onclick="fileManager.renameDelete(\'' + element.path + '\');">' +
                        'Rename/Delete</button>\n';
                html += '</div>\n';
            }
            html += '</div>\n';
        } else {
            if (files.length > 0) {
                html = '<div class="row">\n<div class="col-sm-2"></div>\n' +
                    '<div class="col-sm-3">File Name</div>\n' +
                    '<div class="col-sm-1 text-end">Size</div>\n' +
                    '<div class="col-sm-2">Creation Date/Time</div>\n' +
                    '<div class="col-sm-2">Last Modified</div>\n' +
                    '</div>\n';
            }
            for (let file of files) {
                let element = data[file];
                html += '<div class="row mb-1 me-1">\n<div class="col-sm-2">';
                if (this.#admin)
                    html += '<button class="btn btn-small btn-secondary" onclick="fileManager.renameDelete(\'' + element.path + '\');">' +
                        'Rename/Delete</button>';
                html += '</div>\n<div class="col-sm-3 align-self-center">' + file + '</div>\n' +
                    '<div class="col-sm-1 text-end align-self-center">' + element.size + '</div>\n' +
                    '<div class="col-sm-2 align-self-center">' + element.created + '</div>\n' +
                    '<div class="col-sm-2 align-self-center">' + element.modified + '</div>\n' +
                    '</div>\n';
            }
        }
        return html;
    }

    toggleShowHide(section) {
        switch (section) {
            case 'controll':
                if (this.#controllShow) {
                    this.#controllButton.innerHTML = 'Show';
                    this.#controllPreview.hidden = true;
                } else {
                    this.#controllButton.innerHTML = 'Show';
                    this.#controllPreview.hidden = false;
                }
                this.#controllShow = !this.#controllShow;
                break;
            case 'report':
                if (this.#reportShow) {
                    this.#reportButton.innerHTML = 'Show';
                    this.#reportPreview.hidden = true;
                } else {
                    this.#reportButton.innerHTML = 'Show';
                    this.#reportPreview.hidden = false;
                }
                this.#reportShow = !this.#reportShow;
                break;
            case 'online':
                if (this.#onlineShow) {
                    this.#onlineButton.innerHTML = 'Show';
                    this.#onlinePreview.hidden = true;
                } else {
                    this.#onlineButton.innerHTML = 'Show';
                    this.#onlinePreview.hidden = false;
                }
                this.#onlineShow = !this.#onlineShow;
                break;
            case 'portal':
                if (this.#portalShow) {
                    this.#portalButton.innerHTML = 'Show';
                    this.#portalPreview.hidden = true;
                } else {
                    this.#portalButton.innerHTML = 'Show';
                    this.#portalPreview.hidden = false;
                }
                this.#portalShow = !this.#portalShow;
                break;
            case 'exhibitor':
                if (this.#exhibitorShow) {
                    this.#exhibitorButton.innerHTML = 'Show';
                    this.#exhibitorPreview.hidden = true;
                } else {
                    this.#exhibitorButton.innerHTML = 'Show';
                    this.#exhibitorPreview.hidden = false;
                }
                this.#exhibitorShow = !this.#exhibitorShow;
                break;
        }
    }

    renameDelete(path) {
        let pathElements = path.split('/');
        this.#currentDirectory = pathElements[0];
        this.#currentFile = pathElements[1];
        this.#renameHeading.innerHTML = 'Rename/Delete <b>' + this.#currentFile + '</b> in the directory <b>' + this.#currentDirectory + '</b>';
        this.#renameDeleteTitle.innerHTML = 'Rename/Delete ' + this.#currentFile + ' in the directory ' + this.#currentDirectory;
        this.#currentNameTxt.value = '';
        this.disableRenameDeleteBtns(false)
        this.#renameDeleteModal.show();
    }

    rename() {
        this.disableRenameDeleteBtns(true);
        clear_message('result_message_fm_rd');

        if (this.#currentFile == '' || this.#currentDirectory == '') {
            return;
        }
        let newName = this.#currentNameTxt.value;
        if (newName == '') {
            show_message("No new name specified", 'error', 'result_message_fm_rd');
            this.disableRenameDeleteBtns(false);
            return;
        }

        // validate new name
        // no leading .
        if (newName.startsWith('.')) {
            show_message("New file name cannot start with a . (no hidden files allowed)", 'error', 'result_message_fm_rd');
            this.disableRenameDeleteBtns(false);
            return;
        }

        if (!/^[A-Za-z0-9\-\._]+$/.test(newName)) {
            show_message("Invalid characters in new file name, only A-Z, a-z, 0-9, ., -, and _ allowed.", 'error', 'result_message_fm_rd');
            this.disableRenameDeleteBtns(false);
            return;
        }

        if (newName.length > 127) {
            show_message("New file name cannot be longer than 127 characters, you entered " + newName.length, 'error', 'result_message_fm_rd');
            this.disableRenameDeleteBtns(false);
            return;
        }

        console.log("In Directory " + this.#currentDirectory + ' renaming ' + this.#currentFile + ' to ' + newName);
        let script = 'scripts/filemgr_getLists.php';
        let postData = {
            load_type: this.#currentDirectory,
            action: 'rename',
            origDir: this.#currentDirectory,
            origName: this.#currentFile,
            newName: newName,
        }
        clearError();
        clear_message();
        let modal = this.#renameDeleteModal;
        $.ajax({
            url: script,
            method: 'POST',
            data: postData,
            success: function (data, textStatus, jhXHR) {
                if (data.error) {
                    show_message(data.error, 'error', 'result_message_fm_rd');
                    fileManager.disableRenameDeleteBtns(false);
                    return;
                }
                checkRefresh(data);
                if (data.warn) {
                    show_message(data.warn, 'warn', 'result_message_fm_rd');
                    fileManager.disableRenameDeleteBtns(false);
                    return;
                }
                if (data.success) {
                    show_message(data.success, 'success');
                }
                modal.hide();
                fileManager.drawFileManager(data);
            },
            error: function (jqXHR, textStatus, errorThrown) {
                showError("ERROR in rename: " + textStatus, jqXHR);
                fileManager.disableRenameDeleteBtns(false);
            },
        });
    }

    delete() {
        this.disableRenameDeleteBtns(true);
        clear_message('result_message_fm_rd');
        console.log("In Directory " + this.#currentDirectory + ' deleting ' + this.#currentFile);
        let script = 'scripts/filemgr_getLists.php';
        let postData = {
            load_type: this.#currentDirectory,
            action: 'delete',
            origDir: this.#currentDirectory,
            origName: this.#currentFile,
        }
        clearError();
        clear_message();
        let modal = this.#renameDeleteModal;
        $.ajax({
            url: script,
            method: 'POST',
            data: postData,
            success: function (data, textStatus, jhXHR) {
                if (data.error) {
                    show_message(data.error, 'error', 'result_message_fm_rd');
                    fileManager.disableRenameDeleteBtns(false);
                    return;
                }
                checkRefresh(data);
                if (data.warn) {
                    show_message(data.warn, 'warn', 'result_message_fm_rd');
                    fileManager.disableRenameDeleteBtns(false);
                    return;
                }
                if (data.success) {
                    show_message(data.success, 'success');
                }
                modal.hide();
                fileManager.drawFileManager(data);
            },
            error: function (jqXHR, textStatus, errorThrown) {
                showError("ERROR in delete: " + textStatus, jqXHR);
                fileManager.disableRenameDeleteBtns(false);
            },
        });
    }

    disableRenameDeleteBtns(dir = false) {
        if (this.#fm_renameBtn)
            this.#fm_renameBtn.disabled = dir;
        if (this.#fm_deleteBtn)
            this.#fm_deleteBtn.disabled = dir;
    }

    showUpload(dir) {
        clear_message('result_message_fm_up');
        this.#currentDirectory = dir;
        this.#currentFile = '';
        this.#uploadFileName = '';
        this.#uploadBuffer = null;
        if (this.#uploadImage.src != this.#defaultUploadTarget)
            this.#uploadImage.src = this.#defaultUploadTarget;
        this.#uploadBtn.innerHTML = 'Upload Image/File';
        this.#uploadBtn.disabled = true;

        // choosefile item
        if (this.#uploadEventListenerSet == false) {
            this.#uploadChooseBtn.addEventListener("click", function (e) {
                fileManager.setChosenFileName(null);
                fileManager.clickChooseFileName();
            });
            this.#uploadChooseFileName.addEventListener("change", function (e) {
                fileManager.loaduploadimage(e.target.files[0]);
            });

            // if browser supports drag and drop of photos
            if (window.File && window.FileReader && window.FileList && window.Blob) {
                // hover
                this.#uploadImage.addEventListener("dragenter", function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    fileManager.setDark();
                });
                this.#uploadImage.addEventListener("dragleave", function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    fileManager.setSecondary();

                });
                // upload
                this.#uploadImage.addEventListener("dragover", function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                });
                this.#uploadImage.addEventListener("drop", function (e) {
                    clear_message('result_message_fm_up');
                    e.preventDefault();
                    e.stopPropagation();
                    fileManager.setSecondary();
                    if (e.dataTransfer.files.length == 1) {
                        let f = e.dataTransfer.files[0];
                        if (!f.type.match(/image\/(jpeg|png)/i)) {
                            show_message("Only jpg and png files allowed", 'error', 'result_message_fm_up');
                            fileManager.setUploadDisabled();
                        } else if (f.name.match(/\.(jpg|jpeg|png)$/i))
                            fileManager.loaduploadimage(f);
                        else {
                            show_message("Only jpg and png files allowed", 'error', 'result_message_fm_up');
                            fileManager.setUploadDisabled();
                        }
                    } else {
                        show_message("Drag only one image file", 'error', 'result_message_fm_up');
                    }

                });
            }
        }
        this.#uploadEventListenerSet = true;
        this.#uploadModal.show();
    }

    hideUpload() {
        this.#currentDirectory = '';
        this.#currentFile = '';
        this.#uploadFileName = '';
        this.#uploadBuffer = null;
        this.#uploadModal.hide();
    }

    loaduploadimage(file) {
        clear_message('result_message_fm_up');
        console.log(file);
        this.#currentFile = file.name;
        let name = file.name;
        name = name.replace(/ +/g, '_');
        name = name.replace(/[^A-Za-z0-9\-\._]+/g, '');
        this.#uploadFileName = name;
        let type = file.type;
        fileManager.setUploadDisabled(true);
        // is this file an image file name (ends in .png, .jpeg or .jpg), if so load it into the preview area
        if (type == 'image/png' || type == 'image/jpeg' || type == 'image/jpg') {
            let reader = new FileReader();
            reader.onload = (function (thefile) {
                return function (e) {
                    fileManager.setUploadPhotoSrc(e.target.result);
                }
            })(file);

            reader.readAsDataURL(file);
        } else if (type == 'application/pdf' || type == 'text/csv' ||
                type == 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' || type == 'application/vnd.ms-excel') {
            let reader = new FileReader();
            reader.onload = (function (thefile) {
                return function (e) {
                    fileManager.setUploadBuffer(e.target.result);
                    fileManager.setUploadPhotoSrc(null);
                }
            })(file);

            reader.readAsDataURL(file);
        } else {
            show_message("Only jpg and png images allowed or pdf/csv/xls/xlsx files allowed", 'error', 'result_message_fm_up');
        }
    }

    startTransfer() {
        clear_message('result_message_fm_up');
        console.log("Dir: " + this.#currentDirectory);
        console.log("Src: " + this.#currentFile);
        console.log("Dest: " + this.#uploadFileName);
        let buffer = this.#uploadImage.src;
        if (buffer == null  || buffer.endsWith(this.#defaultUploadTarget))
            buffer = this.#uploadBuffer;

        let script = 'scripts/filemgr_getLists.php';
        let postData = {
            action: 'upload',
            load_type: this.#currentDirectory,
            origDir: this.#currentDirectory,
            newName: this.#uploadFileName,
            contents: buffer,
        }

        $.ajax({
            url: script,
            method: 'POST',
            data: postData,
            success: function (data, textStatus, jhXHR) {
                if (data.error) {
                    show_message(data.error, 'error', 'result_message_fm_up');
                    return;
                }
                checkRefresh(data);
                if (data.warn) {
                    show_message(data.warn, 'warn', 'result_message_fm_up');
                    return;
                }
                if (data.success)
                    show_message(data.success, 'success');
                fileManager.hideUpload();
                fileManager.drawFileManager(data);
            },
            error: function (jqXHR, textStatus, errorThrown) {
                showError("ERROR in startTransfer: " + textStatus, jqXHR);
            },
        });

    }
}
