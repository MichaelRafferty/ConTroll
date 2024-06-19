// membership history javascript, also requires base.js

function updateSelection() {
    var startOption = document.getElementById('fromId');
    var endOption = document.getElementById('toId');
    var from = startOption.value;
    var to = endOption.value;

    window.location.search = '?start=' + from + '&end=' + to;
}
