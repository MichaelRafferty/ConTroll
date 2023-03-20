function printTestLabel() {
    newbadge = document.getElementById('newBadge');
    dayfield = document.getElementById('day');
    typefield = document.getElementById('type');

    if (typefield.value == 'oneday' && dayfield.value == '') {
        show_message('one day badges need a day selected', 'warn');
        return;
    }

    clear_message();
    if (newbadge.checkValidity()) {
        var badge = $('#newBadge').serialize();
        var params = URLparamsToArray(badge);

        $('#test').empty().append(badge);
        var postData = {
            ajax_request_action: 'printBadge',
            badge: badge,
            params: params,
        };
        $.ajax({
            method: "POST",
            url: "scripts/printformTasks.php",
            data: postData,
            success: function (data, textstatus, jqxhr) {
                if (data['message'] !== undefined) {
                    show_message(data['message'], 'success');
                }
                if (data['error'] !== undefined) {
                    var msg = data['error'];
                    if (data['error_message'] !== undefined) {
                        msg += "<br/>" + $data['error_message'];
                    }
                    show_message(msg, 'error');
                }
            },
            error: showAjaxError,
        });
    }
}
