//import { TabulatorFull as Tabulator } from 'tabulator-tables';
// interests class - all edit interests functions
class interestsSetup {
    #messageDiv = null;
    #interestsPane = null;
    #interestsTable = null;

    // globals before open
    constructor() {
        this.#messageDiv = document.getElementById('test');
        this.#interestsPane = document.getElementById('interests-pane');
        // set listen to CR on find name field

    };


    // called on open of the custom text window
    open() {
        var html = `<h4><strong>Edit Interests:</strong></h4>
<div class="container-fluid">
    <div class="row">
        <div class="col-sm-2 p-2">not yet</div>
    </div>
</div>
`;
        this.#interestsPane.innerHTML = html;
    }

    // on close of the pane, clean up the items
    close() {
        if (this.#interestsTable) {
            this.#interestsTable.destroy();
            this.#interestsTable = null;
        }

        this.#interestsPane.innerHTML = '';
    };
}