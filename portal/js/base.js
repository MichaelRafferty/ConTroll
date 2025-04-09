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