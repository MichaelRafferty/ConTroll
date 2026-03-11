// file manager javascript

var fileManager = null;

// initial setup
window.onload = function () {
    fileManager = new FileManager();
}

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
            load_type: 'all'
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
                    show_message(data.error, 'warn');
                    return;
                }
                if (data.success)
                    show_message(data.success, 'success');
                fileManager.drawFileManager(data);
            },
            error: function (jqXHR, textStatus, errorThrown) {
                showError("ERROR in getMenu: " + textStatus, jqXHR);
            },
        });
    }

    // draw the initial screen
    drawFileManager(data) {
        console.log(data);

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
        let html = '<div class="row">\n';
        let files = Object.keys(data);
        for (let file of files) {
            let element = data[file];
            html +='<div class="col-sm-auto mb-4">\n';
            if (preview) {
                html += '<img src="' + element.path + '" style="max-height:200px;max-width:200px;height:auto;width:auto;"><br/>\n';
            }
            html += file + '(' + element.size + ')<br/>c: ' + element.created +
                '<br/>m: ' + element.modified + '</div>\n';
        }
        html += '</div>\n';
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
}
