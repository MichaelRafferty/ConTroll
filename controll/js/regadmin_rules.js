//import { TabulatorFull as Tabulator } from 'tabulator-tables';
// rules class - all edit membership rules functions
class rulesSetup {
    #messageDiv = null;
    #rulesPane = null;
    #rulesTable = null;

    // globals before open
    constructor() {
        this.#messageDiv = document.getElementById('test');
        this.#rulesPane = document.getElementById('rules-pane');

    };


    // called on open of the custom text window
    open() {
        var html = `<h4><strong>Edit Membership Rules:</strong></h4>
<div class="container-fluid">
    <div class="row">
        <div class="col-sm-2 p-2">not yet</div>
    </div>
</div>
`;
        this.#rulesPane.innerHTML = html;
    }

    // on close of the pane, clean up the items
    close() {
        if (this.#rulesTable) {
            this.#rulesTable.destroy();
            this.#rulesTable = null;
        }

        this.#rulesPane.innerHTML = '';
    };
}