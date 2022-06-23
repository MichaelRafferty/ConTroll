function clearPermissions(userid) {
    var formdata = $("#" + userid).serialize();
    $('#test').append(formdata);
    $.ajax({
        url: 'scripts/permUpdate.php',
        method: 'POST',
        data: formdata+"&action=clear",
        success: function (data, textStatus, jhXHR) {
            $('#test').append(JSON.stringify(data, null, 2));
            location.reload();
        }
    });
}

function updatePermissions(userid) {
    var formdata = $("#" + userid).serialize();
    $('#test').append(formdata);
    $.ajax({
        url: 'scripts/permUpdate.php',
        method: 'POST',
        data: formdata+"&action=update",
        success: function (data, textStatus, jhXHR) {
            $('#test').append(JSON.stringify(data, null, 2));
            location.reload();
        }
    });
}


function createAccount() {
    var formdata = $("#createUserForm").serialize();
    $('#test').append(formdata);
    $.ajax({
        url: 'scripts/permUpdate.php',
        method: 'POST',
        data: formdata+"&action=create",
        success: function (data, textStatus, jhXHR) {
            $('#test').append(JSON.stringify(data, null, 2));
            location.reload();
        }
    });
}
