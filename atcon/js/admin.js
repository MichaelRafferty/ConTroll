$(document).ready(function() {
    $('#chpw').hide();
    $.ajax({
        method: "POST",
        url: "scripts/authUsers.php",
        success: function(data, textstatus, jqxhr) {
            addUsers(data['users']);
        }
    });
});

function addUsers(userlist) {
    for(u in userlist) {
        var row = $(document.createElement('tr'))
        row.append($(document.createElement('td'))
                .append(userlist[u]['id']));
        row.append($(document.createElement('td'))
                .append(userlist[u]['name']));
        row.append($(document.createElement('td'))
                .append($(document.createElement('input'))
                    .attr('type', 'checkbox')
                    .attr('checked', userlist[u]['data_entry'])
                    .attr('id', 'user' + u + 'data_entry')));
        row.append($(document.createElement('td'))
                .append($(document.createElement('input'))
                    .attr('type', 'checkbox')
                    .attr('checked', userlist[u]['cashier'])
                    .attr('id', 'user' + u + 'cashier')));
        row.append($(document.createElement('td'))
                .append($(document.createElement('input'))
                    .attr('type', 'checkbox')
                    .attr('checked', userlist[u]['artinventory'])
                    .attr('id', 'user' + u + 'artinventory')));
        row.append($(document.createElement('td'))
                .append($(document.createElement('input'))
                    .attr('type', 'checkbox')
                    .attr('checked', userlist[u]['artsales'])
                    .attr('id', 'user' + u + 'artsales')));
        row.append($(document.createElement('td'))
                .append($(document.createElement('input'))
                    .attr('type', 'checkbox')
                    .attr('checked', userlist[u]['manager'])
                    .attr('id', 'user' + u + 'manager')));
        row.append($(document.createElement('td'))
            .append($(document.createElement('button'))
                .attr('user', u)
                .click(function () { updateUser($(this).attr('user')); })
                .append('Update'))
            .append($(document.createElement('button'))
                .attr('user', u)
                .click(function () { passwdUser($(this).attr('user')); })
                .append('Reset Password')))

    $('#users').append(row);
    }
}

function updateUser(user) {
    $.ajax({
        method: "POST",
        url: "scripts/updateAuths.php",
        data: { 
            updateUser: user,
            data_entry: $('#user'+user+'data_entry').is(':checked'),
            register: $('#user'+user+'cashier').is(':checked'),
            ertinventory: $('#user'+user+'artinventory').is(':checked'),
            artsales: $('#user'+user+'artsales').is(':checked'),
            manager: $('#user'+user+'manager').is(':checked')
        },
        success: function (data, textstatus, jqxhr) { 
            alert(data['message']); 
            $('#users').empty();
            $.ajax({
                method: "POST",
                url: "scripts/authUsers.php",
                success: function(data, textstatus, jqxhr) {
                    addUsers(data['users']);
                }
            });
        }
    });
}

function passwdUser(user) {
    $.ajax({
        method : "POST",
        url : "scripts/passwdReset.php",
        data : {newpw: prompt("New Password"), resetUser: user},
        success: function (data, textstatus, jqxhr) { alert(data['message']); }
    });
}

function addUser() {
    var postdata = $('#addUserForm').serialize();
    $.ajax({
        data: postdata,
        method: "POST",
        url: "scripts/addAtconUser.php",
        success: function (data, textstatus, jqxhr) { 
            alert(data['message']); 
            $('#users').empty();
            $.ajax({
                method: "POST",
                url: "scripts/authUsers.php",
                success: function(data, textstatus, jqxhr) {
                    addUsers(data['users']);
                    $('#addUser').dialog('close');
                }
            });
        }
    });
}
