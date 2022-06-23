$(document).ready(function () {
    getData();
    $('#gridSelectWrap').hide();
});


function getData() {
    $.ajax({
        url: "scripts/getBadges.php",
        method: "GET",
        success: function(data, textStatus, jqXhr) {
            console.log(data['badges'].length);
            //$('#test').empty().append(JSON.stringify(data, null, 2));
            $('#grid').data('data', data['badges']);
            $('#grid').data('rowFunc', buildRow);
            $('#grid').data('nameMatch', nameMatch);
            var facetList = ["category", "type", "age", "paid", "label"];
            $('#grid').data('filters', facetList);
            defineFacets('#grid', '#facets');
            redraw('#grid');
            $('#main').width($('#table').width() + $('#facets').width() + 15);
        }
    })
}

function buildRow(badge) {
    var row = $(document.createElement('tr'))
    var confirmed_person = false;
    row.attr('badge_id', badge.badgeId);

    var name = $(document.createElement('td'));
    if(badge.p_name != "") {
        name.append(badge.p_name)
            .append(document.createElement('br'))
            .append(badge.p_badge)
            .addClass('confirmed');
        row.attr('confirmed', 'confirmed');
        row.attr('perid', badge.perid);
        confirmed_person = true;
    } else {
        name.append(badge.np_name)
            .append(document.createElement('br'))
            .append(badge.np_badge)
            .addClass('unconfirmed');
        row.attr('confirmed', 'unconfirmed');
        row.attr('newperid', badge.np_id);
        confirmed_person = false;
    }
    row.append(name);

    var type = $(document.createElement('td'))
        .append(badge.label)
        .append(document.createElement('br'))
        .append(badge.memType);
    row.append(type)
        .attr('label', badge.label)
        .attr('category', badge.category)
        .attr('type', badge.type)
        .attr('age', badge.age);
    
    var payment = $(document.createElement('td'))
        .append(badge.paid)
        .append('/')
        .append(badge.price);
    row.append(payment);
    if(badge.price==0) { 
        row.attr('paid', 'comp'); 
        payment.attr('paid', 'comp'); 
    } 
    else if(badge.paid==0) { 
        row.attr('paid', 'unpaid'); 
        payment.attr('paid', 'unpaid'); 
    }
    else if(badge.paid<badge.price) { 
        row.attr('paid', 'partial'); 
        payment.attr('paid', 'partial'); 
    }
    else if(badge.paid==badge.price) { 
        row.attr('paid', 'paid'); 
        payment.attr('paid', 'paid'); 
    }
    else { 
        row.attr('paid', 'unknown'); 
        payment.attr('paid', 'unknown'); 
    }
    
    var dates = $(document.createElement('td'))
        .append(badge.create_date)
        .append(document.createElement('br'))
        .append(badge.change_date);
    row.append(dates);


    var buttons = $(document.createElement('td'))
        .append($(document.createElement('button'))
            .text('Transfer')
            .click(function () {transferBadge(badge.badgeId)})
        );
    row.append(buttons);

    return row;

}


function nameMatch(name, data) {
    var re = new RegExp(name);
    return (re.test(data.p_name) || re.test(data.p_badge));
}

function transferBadge(badge) {
    var newId = prompt('Please enter the Perid of the person you are transferring TO');

    var formData = {'badge' : badge, 'perid' : newId};
    $.ajax({
        url: 'scripts/transferBadge.php',
        data: formData,
        method: 'POST',
        success: function(data, textStatus, jqXHR) {
            if(data.error != '') {
                $('#test').empty().append(JSON.stringify(data));
                alert(data.error);
            } else {
                location.reload();
            }
        }
    });
}

function sendCancel() {
    var tid = prompt("Would you like to send a test email?\nIf so please enter the transaction you want to send the test for.");
    var action = "none";

    if(tid == null) {
        if(confirm("You are about to send email to a lot of people.  Are you sure?")) {
        action = 'full';
      } else { return false; }
    } else {
        action = 'test';
    }

    $.ajax({
        url: 'scripts/sendCancelEmail.php',
        data: { 'action' : action, 'tid' : tid},
        method: "POST",
        success: function(data, textStatus, jqXHR) {
            if(data.error != '') {
                $('#test').empty().append(JSON.stringify(data));
                alert(data.error);
            } else {
                $('#test').empty().append(JSON.stringify(data));
            }
        }
    });
}

function sendEmail() {
    var email = prompt("Would you like to send a test email?\nIf so please enter the address to send the test to.");
    var action = "none";

    if(email == null) {
        if(confirm("You are about to send email to a lot of people.  Are you sure?")) {
        action = 'full';
      } else { return false; }
    } else {
        action = 'test';
    }

    $.ajax({
        url: 'scripts/sendEmail.php',
        data: { 'action' : action, 'email' : email},
        method: "POST",
        success: function(data, textStatus, jqXHR) {
            if(data.error != '') {
                $('#test').empty().append(JSON.stringify(data));
                alert(data.error);
            } else {
                $('#test').empty().append(JSON.stringify(data));
            }
        }
    });
}
