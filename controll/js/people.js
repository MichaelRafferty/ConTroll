// globals for the people tabs

// tab classes
unmatchedPeople = null;
findPerson = null;
addPerson = null;
// debug meaning
//  1 = console.logs
//  2 = show hidden table fields
//  4 = show hidden div
debug = 0;
conid = null;

// initialization at DOM complete
window.onload = function initpage() {
    var id = document.getElementById("debug");
    if (id)
        debug = id.innerHTML;
    id = document.getElementById("conid");
    if (id)
        conid = id.innerHTML;
    settab('unmatched-pane');
}


function settab(tabname) {
    // close all of them
    if (unmatchedPeople != null)
        unmatchedPeople.close();
    if (findPerson != null)
        findPerson.close();
    if (addPerson != null)
        ongamepaddisconnected.close();

    // now open the relevant one, and create the class if needed
    switch (tabname) {
        case 'unmatched-pane':
            if (unmatchedPeople == null)
                unmatchedPeople = new Unmatched(debug);
            unmatchedPeople.open();
            break;
        case 'findedit-pane':
            if (findPerson == null)
                findPerson = new Find(debug);
            findPerson.open();
            break;
        case 'add-pane':
            if (addPerson == null)
                addPerson = new Add(debug);
            addPerson.open();
            break;
    }
}