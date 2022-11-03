//import { TabulatorFull as Tabulator } from 'tabulator-tables';

class consetup {
    #active = false;
    #contable = null;
    #memtable = null;
    #proposed = ' ';
    #condate = null;
    #conyear = null;
    #mindate = null;
    #maxdate = null;
    #dateformat = 'yyyy-MM-dd';
    #conlist_dirty = false;
    #conlist_savebtn = null;
    #conlist_undobtn = null;
    #conlist_redobtn = null;
    #conlist_div = null;
    #conlist_pane = null;
    #memlist_dirty = false;
    #memlist_savebtn = null;
    #memlist_undobtn = null;
    #memlist_div = null;
    #message_div = null;
    #setup_type = null;
    #setup_title = null;

    constructor(setup_type) {

        this.#message_div = document.getElementById('test');
        if (setup_type == 'current' || setup_type == 'c') {
            this.#conlist_pane = document.getElementById('consetup-pane');
            this.#setup_type = 'current';
            this.#setup_title = 'Current';
        }
        if (setup_type == 'next' || setup_type == '') {
            this.#conlist_pane = document.getElementById('nextconsetup-pane');
            this.#setup_type = 'next';
            this.#setup_title = 'Next';
        }
    };


    conlist_dataChanged(data) {
        //data - the updated table data
        if (!this.#conlist_dirty) {
            this.#conlist_savebtn.innerHTML = "Save Changes*";
            this.#conlist_savebtn.disabled = false;
            this.#conlist_dirty = true;
        }
        if (this.#contable.getHistoryUndoSize() > 0) {
            this.#conlist_undobtn.disabled = false;
        }
    };

    draw(data, textStatus, jhXHR) {
        var _this = this;
        //console.log('in draw');
        //console.log(data);
        this.#proposed = ' ';

        if (data['age-type'] == 'proposed')
            this.#proposed = ' Proposed ';

        var html = '<h5><strong>' + this.#proposed + ' ' + this.#setup_title + ` Convention Data:</strong></h5>
<div id="` + this.#setup_type + `-conlist"></div>
<div id="conlist-buttons">  
    <button id="` + this.#setup_type + `conlist-undo" type="button" class="btn btn-secondary btn-sm" onclick="` + this.#setup_type + `.undoConlist(); return false;" disabled>Undo</button>
    <button id="` + this.#setup_type + `conlist-redo" type="button" class="btn btn-secondary btn-sm" onclick="` + this.#setup_type + `.redoConlist(); return false;" disabled>Redo</button>
    <button id="` + this.#setup_type + `conlist-save" type="button" class="btn btn-primary btn-sm"  onclick="` + this.#setup_type + `.saveConlist(); return false;" disabled>Save Changes</button>
</div>
<div>&nbsp;</div>
<h5><strong>` + this.#setup_title + ` Membership Types:</strong></h5>
<div id="` + this.#setup_type + `-memlist"></div>
`;
        this.#conlist_pane.innerHTML = html;
        this.#message_div.innerHTML = '';
        this.#condate = new Date(data['startdate']);
        this.#conyear = this.#condate.getFullYear();
        this.#mindate = this.#conyear + "-01-01";
        this.#maxdate = this.#conyear + "-12-31";
        this.#conlist_savebtn = document.getElementById(this.#setup_type + 'conlist-save');
        this.#conlist_undobtn = document.getElementById(this.#setup_type + 'conlist-undo');
        this.#conlist_redobtn = document.getElementById(this.#setup_type + 'conlist-redo');
        this.#conlist_div = document.getElementById(this.#setup_type + '-conlist');
        this.#memlist_div = document.getElementById(this.#setup_type + '-memlist');

        if (this.#proposed != ' ') {
            this.#conlist_savebtn.innerHTML = "Save Changes*";
            this.#conlist_savebtn.disabled = false;
            this.#conlist_dirty = true;
        } else {
            this.#conlist_dirty = false;
        }

       this.#contable = null;

        if (data['conlist'] == null) {
            this.#conlist_div.innerHTML = 'Nothing defined yet';
        } else {
            this.#contable = new Tabulator('#' + this.#setup_type + '-conlist', {
                maxHeight: "400px",
                history: true,
                data: [data['conlist']],
                layout: "fitDataTable",
                columns: [
                    { title: "ID", field: "id", headerSort: false },
                    { title: "Name", field: "name", headerSort: false, editor: "input" },
                    { title: "Label", field: "label", headerSort: false, editor: "input" },
                    { title: "Start Date", field: "startdate", headerSort: false, editor: "date" },
                    { title: "End Date", field: "enddate", headerSort: false, editor: "date" }
                ],
            });
        }

        this.#contable.on("dataChanged", function (data) {
            _this.conlist_dataChanged(data);
        });
        this.#contable.on("cellEdited", cellChanged);

        this.#memtable = null;

        if (data['memlist'] == null) {
            this.#memlist_div.innerHTML = 'Nothing defined yet';
        } else {
            this.#memtable = new Tabulator('#' + this.#setup_type + '-memlist', {
                maxHeight: "600px",
                data: data['memlist'],
                layout: "fitDataTable",
                columns: [
                    { title: "ID", field: "id", visible: false },
                    { title: "Con ID", field: "conid" },
                    { title: "Sort", field: "sort_order", headerSort: false },
                    { title: "Category", field: "memCategory" },
                    { title: "Type", field: "memType" },
                    { title: "Age", field: "memAge" },
                    {
                        title: "Name", field: "shortname",
                        tooltip: function (e, cell, onRendered) { return cell.getRow().getCell("label").getValue(); }
                    },
                    { title: "Label", field: "label", visible: false },
                    { title: "Price", field: "price" },
                    { title: "Start Date", field: "startdate" },
                    { title: "End Date", field: "enddate" },
                    { title: "Atcon", field: "atcon" },
                    { title: "Online", field: "online" }
                ],

            });
        }
    };

    open() {
        var script = "scripts/getCondata.php";     
        $.ajax({
            url: script,
            method: 'GET',
            data: 'year=' + this.#setup_type,
            success: function (data, textStatus, jhXHR) {
                if (data['year'] == 'current') {
                    current.draw(data, textStatus, jhXHR);
                } else {
                    next.draw(data, textStatus, jhXHR);
                }
            },
            error: function (jqXHR, textStatus, errorThrown) {
                showError("ERROR in " + script + ": " + textStatus, jqXHR);
                return false;
            }
        });
    };

    close() {
        this.#contable = null;
        this.#memtable = null;
        this.#conlist_pane.innerHTML = '';
        this.#conlist_dirty = false;
        this.#memlist_dirty = false;
    };

    undoConlist() {
        if (this.#contable != null) {
            this.#contable.undo();

            var undoCount = this.#contable.getHistoryUndoSize();
            if (undoCount <= 0) {
                this.#conlist_undobtn.disabled = true;
                this.#conlist_dirty = false;
                if (this.#proposed == ' ') {
                    this.#conlist_savebtn.innerHTML = "Save Changes";
                    this.#conlist_savebtn.disabled = true;
                }
            }
            var redoCount = this.#contable.getHistoryRedoSize();
            if (redoCount > 0) {
                this.#conlist_redobtn.disabled = false;
            }
        }
    };

    redoConlist() {
        if (this.#contable != null) {
            this.#contable.redo();

            var undoCount = this.#contable.getHistoryUndoSize();
            if (undoCount > 0) {
                this.#conlist_undobtn.disabled = false;
                this.#conlist_dirty = true;
                this.#conlist_savebtn.innerHTML = "Save Changes*";
                this.#conlist_savebtn.disabled = false;
            }
            var redoCount = this.#contable.getHistoryRedoSize();
            if (redoCount <= 0) {
                this.#conlist_redobtn.disabled = true;
            }
        }
    };

    saveConlistComplete(data, textStatus, jhXHR) {
        if (data['error'] && data['error' != '']) {
            showError(data['error']);
        } else {
            showError(data['success']);
        }
        this.#conlist_savebtn.innerHTML = "Save Changes";
    }

    saveConlist() {
        if (this.#contable != null) {
            this.#conlist_savebtn.innerHTML = "Saving...";
            this.#conlist_savebtn.disabled = true;

            var script = "scripts/updateCondata.php";

            var postdata = {
                ajax_request_action: this.#setup_type,
                tabledata: this.#contable.getData(),
                tablename: "conlist",
                indexcol: "id"
            };
            //console.log(postdata);
            $.ajax({
                url: script,
                method: 'POST',
                data: postdata,
                success: function (data, textStatus, jhXHR) {
                    if (data['year'] == 'current') {
                        current.saveConlistComplete(data, textStatus, jhXHR);
                    } else {
                        next.saveConlistComplete(data, textStatus, jhXHR);
                    }                   
                },
                error: function (jqXHR, textStatus, errorThrown) {
                    showError("ERROR in " + script + ": " + textStatus, jqXHR);
                    return false;
                }
            });
        }
    };
};