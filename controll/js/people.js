// globals for the people tabs

// tab classes
var unmatchedPeople = null;
var findPerson = null;
var addPerson = null;
var add_tab = null;
var find_tab = null;
var unmatched_tab = null;
var profile = null;
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
            if (profile =! null)
                profile = null;
            profile = new Profile('f_', 'people');
            if (findPerson == null)
                findPerson = new Find(config['debug']);
            findPerson.open();
            break;
        case 'add-pane':
            if (profile =! null)
                profile = null;
            profile = new Profile('a_', 'people');
            if (addPerson == null)
                addPerson = new Add(config['debug']);
            addPerson.open();
            break;
    }
}

// switch from add to edit with a person
function peopleEditPerson(index, row) {
    //console.log("Switch to " + index);
    //console.log(row);
    bootstrap.Tab.getOrCreateInstance(find_tab).show();
    if (findPerson == null)
        findPerson = new Find(config['debug']);
    findPerson.open(null, index, row);
}

// switch from find/edit to add because the person wasn't foiund
function peopleAddPerson() {
    bootstrap.Tab.getOrCreateInstance(add_tab).show();
    if (addPerson == null)
        addPerson = new Add(config['debug']);
    if (profile =! null)
        profile = null;
    profile = new Profile('a_', 'people');
    addPerson.open();
}
