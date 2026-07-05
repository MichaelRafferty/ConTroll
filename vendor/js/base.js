// new functions for token items
function checkRefresh(data) {
    // empty function for common js files for controll and atcon
}

function setCellChanged(cell) {
    setFieldChanged(cell.getElement());
}

function setFieldChanged(field) {
    if (!field.classList.contains('unsavedChangeBGColor'))
        field.classList.add('unsavedChangeBGColor');
}

function clearCellFieldChanged(cell) {
    clearFieldChanged(cell.getElement());
}

function clearFieldChanged(field) {
    field.classList.remove('unsavedChangeBGColor');
}
