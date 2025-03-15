function eventCreated(data, textStatus, jqXHR) {
    if(data['status']=='error') { alert(data['error']); $('#test').empty().append(data); return;}
    else {
        $('#test').empty().append(JSON.stringify(data,2,null));
        alert("Event " + data['title'] + " Created " + data['day'] + " at " + data['time'] + "\nTech At " + data['tech']);
        
    }
}

