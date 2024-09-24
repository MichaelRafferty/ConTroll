// globals for the people tabs

// tab classes
unmatchedPeople = null;
findPerson = null;
addPerson = null;
add_tab = null;
find_tab = null;
unmatched_tab = null;
// debug meaning
//  1 = console.logs
//  2 = show hidden table fields
//  4 = show hidden div

// initialization at DOM complete
window.onload = function initpage() {
    add_tab = document.getElementById("add-tab");
    find_tab = document.getElementById("findedit-tab");
    unmatched_tab = document.getElementById("unmatched-tab");
    settab('unmatched-pane');
}

function settab(tabname) {
    // close all of them
    if (unmatchedPeople != null)
        unmatchedPeople.close();
    if (findPerson != null)
        findPerson.close();
    if (addPerson != null)
        addPerson.close();

    // now open the relevant one, and create the class if needed
    switch (tabname) {
        case 'unmatched-pane':
            if (unmatchedPeople == null)
                unmatchedPeople = new Unmatched(config['debug']);
            unmatchedPeople.open();
            break;
        case 'findedit-pane':
            if (findPerson == null)
                findPerson = new Find(config['debug']);
            findPerson.open();
            break;
        case 'add-pane':
            if (addPerson == null)
                addPerson = new Add(config['debug']);
            addPerson.open();
            break;
    }
}

// switch from add to edit with a person
function peopleEditPerson(index, row) {
    console.log("Switch to " + index);
    console.log(row);
    bootstrap.Tab.getOrCreateInstance(find_tab).show();
    if (findPerson == null)
        findPerson = new Find(config['debug']);
    findPerson.open(null, index, row);
}