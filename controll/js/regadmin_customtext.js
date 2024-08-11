//import { TabulatorFull as Tabulator } from 'tabulator-tables';
// customText class - all edit Customtext functions
class customTextSetup {
    #messageDiv = null;
    #customTextPane = null;
    #customTextTable = null;

    // globals before open
    constructor() {
        this.#messageDiv = document.getElementById('test');
        this.#customTextPane = document.getElementById('customtext-pane');

    };


    // called on open of the custom text window
    open() {
        var html = `<h4><strong>Edit Custom Text:</strong></h4>
<div class="container-fluid">
    <div class="row">
        <div class="col-sm-2 p-2">not yet</div>
    </div>
</div>
`;
        this.#customTextPane.innerHTML = html;
    }

    // on close of the pane, clean up the items
    close() {
        if (this.#customTextTable) {
            this.#customTextTable.destroy();
            this.#customTextTable = null;
        }

        this.#customTextPane.innerHTML = '';
    };
}