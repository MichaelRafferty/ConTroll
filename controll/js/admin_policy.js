//import { TabulatorFull as Tabulator } from 'tabulator-tables';
// policy class - all edit membership policy functions
class policySetup {
    #messageDiv = null;
    #policyPane = null;
    #policyTable = null;

    // globals before open
    constructor() {
        this.#messageDiv = document.getElementById('test');
        this.#policyPane = document.getElementById('policy-pane');

    };


    // called on open of the policy window
    open() {
        var html = `<h4><strong>Edit Policy:</strong></h4>
<div class="container-fluid">
    <div class="row">
        <div class="col-sm-2 p-2">not yet</div>
    </div>
</div>
`;
        this.#policyPane.innerHTML = html;
    }

    // on close of the pane, clean up the items
    close() {
        if (this.#policyTable) {
            this.#policyTable.destroy();
            this.#policyTable = null;
        }

        this.#policyPane.innerHTML = '';
    };
}