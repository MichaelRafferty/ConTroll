// Portal - Base.js - common javascript functions

// findMembership - find matching memRow in memList
function findMembership(id) {
    if (!memList)
        return null; // no list to search

    for (var row in memList) {
        var memrow = memList[row];
        if (id != memrow.id)
            continue;
        return memrow;  // return matching entry
    }
    return null; // not found
}

// make_copy(associative array)
// javascript passes by reference, can't slice an associative array, so you need to do a horrible JSON kludge
function make_copy(arr) {
    return JSON.parse(JSON.stringify(arr));  // horrible way to make an independent copy of an associative array
}

// check for the array resolveUpdates and update config if it exists
function checkResolveUpdates(data) {
    if (data['resolveUpdates']) {
        var resolveUpdates = data['resolveUpdates'];
        if (resolveUpdates['id'] && resolveUpdates['idType'] && config) {
            config['id'] = resolveUpdates['id'];
            config['idType'] = resolveUpdates['idType'];
        }
    }
}