$(document).ready(function() {
    showBlock('#artist');
    showBlock('#currentShow');
    getCurrentShow();
});
function findAgent(request) {
    var agentName = prompt("Enter the agent name to search for", request);
    if(agentName == null) { return false;}
    $.ajax({
        url: 'scripts/findPerson.php',
        method: "GET",
        data: {"full_name": agentName},
        success: function (data, textStatus, jqXhr) {
            if(data['error'] != undefined) { console.log(data['error']); }
            displaySearchResults(data, setAgent)
        }
    });
}

function setAgent(perid) {
   $.ajax({
     type: "POST",
     data: {'artid' : $("#artid").val(), 'agent' : perid.id},
     url: "scripts/setAgent.php",
     success: function(data, textStatus, jqXHR) {
       getArtist({id:$('#perid').val()});
       return false;
     }
   });
}

function findPerson(form) {
    var getData = $(form).serialize() + "&condition=artist";
    $.ajax({
        url: 'scripts/findPerson.php',
        method: "GET",
        data: getData,
        success: function (data, textStatus, jqXhr) {
            if(data['error'] != undefined) { console.log(data['error']); }
            displaySearchResults(data, getArtist)
        }
    });
}

function getArtist(perid) {
  $('#artistForm')[0].reset();
  var formUrl = "scripts/getArtist.php";
  $.ajax({
    type: "GET",
    data: "perid="+perid.id,
    url: formUrl,
    success: function(data, textStatus, jqXHR) {
      if(data['inShow']==false) {
        newArtist(data['person'], data['artist']);
      } else {
        loadPerson(data['person'], data['badge'], data['artist'], data['vendor'], data['agent']);
      }
      //showError('trace:', data);
      return false;
    },
    error: function(jqXHR, textStatus, errorThrown) {
      showError('ERROR in ' + formUrl + ": " + textStatus, jqXHR);
      return false;
    }
  });
}

function newArtist(per, artist) {
    $('#newArtistForm')[0].reset();
    $('#newartistId').val(artist['id']);
    $('#newperid').val(per['id']);
    $('#newArtistName').empty().text(per['first_name'] + " " + per['middle_name'] + ' ' + per['last_name']);
    $('#newArtistTrade').empty().text(artist['art_name']);
    $('#agent_name_in').val(artist['agent_name']);

    $('#newArtist').dialog("open");
}
function loadPerson(per, badge, artist, vendor, agent) {
    $('#pername').empty().append(per['first_name'] + ' ' + per['last_name']);
    if(per['badge']) { 
        $('#badge').empty().append(per['label']); 
    } else { $('#badge').empty().append("none"); }
    $('#artname').empty().append(vendor['name']);
    $('#perid').val(per['id']);
    $('#artid').val(artist['id']);
    $('#emails').empty().append(per['email_addr'] + "; " + artist['email'])
    if(artist['agent_request'] == '') { 
        $('#agent_row').hide(); 
    } else {
        $('#agent_row').show();
        $('#agent_request').empty().append(artist['agent_request']);
        if(agent == null) {
            findAgent(artist['agent_request']);
        } else {
            $("#agent_name").empty().append(agent['first_name'] + ' ' + agent['last_name']);
            $("#agent_id").empty().append(agent['id']);
            if(agent['badge']) { 
                $('#agent_badge').empty().append(agent['label']); 
            } else { $('#agent_badge').empty().append("none"); }
        }
    }

    $('#website').empty().append(vendor['website']);
    $('#description').empty().append(vendor['description']);

   $.ajax({
     type: "GET",
     data: "artid="+artist['id'],
     url: "scripts/artDetails.php",
     success: function(data, textStatus, jqXHR) {
       //showError('trace:', data);
       showArtshow(data);
       return false;
     },
     error: function(jqXHR, textStatus, errorThrown) {
       showError("ERROR in " + formUrl + ": " + textStatus, jqXHR);
       return false;
     }
   });

   $.ajax({
     type: "GET",
     data: "artid="+artist['id'],
     url: "scripts/artHistory.php",
     success: function(data, textStatus, jqXHR) {
       //showError('trace:', data);
       showHistory(data['history']);
       return false;
     },
     error: function(jqXHR, textStatus, errorThrown) {
       showError("ERROR in " + formUrl + ": " + textStatus, jqXHR);
       return false;
     }
  });
}

function showHistory(history) {
  $('#artistHistoryInfo').empty();
  var rows = d3.select('#artistHistoryInfo').selectAll('tr').data(history)
    .enter().append('tr')
    .html(function (d) { return "<td>"+d['conid']+"</td><td>"+d['total']+"</td>"; });
}

function showArtshow(data) {
  var inshow = data['inShow'];
  $('#inshow').empty().append(inshow);
  if(inshow == 'yes') {
    $('#key').val(data['details']['art_key']);
    $('#asp_count').val(data['details']['a_panels']);
    $('#ast_count').val(data['details']['a_tables']);
    $('#psp_count').val(data['details']['p_panels']);
    $('#pst_count').val(data['details']['p_tables']);
    $('#asp').val(data['details']['a_panel_list']);
    $('#ast').val(data['details']['a_table_list']);
    $('#psp').val(data['details']['p_panel_list']);
    $('#pst').val(data['details']['p_table_list']);
    $('#detailsId').val(data['details']['id']);
    $('#show_desc').empty().append(data['details']['description']);
    $('#itemcount').empty().append(data['itemcount']);
  } else { $('#detailsId').val(''); }
}

function getCurrentShow() {
  var formUrl = "scripts/currentArtShow.php";
  $.ajax({
    type: "GET",
    data: null,
    url: formUrl,
    success: function(data, textStatus, jqXHR) {
      $('#currentShowArtists').empty();
      // This code loops over each artist and generates a row in the
      // artist list for them.
      var rows = d3.select('#currentShowArtists').selectAll('tr')
        .data(data.artistList)
        .enter().append('tr')
        .html(function (d) {
          var ret = "<td>"+d['art_key']+"</td><td>"
            +"<a href='javascript:void(0);' onClick='getArtist({id:"
            + d['perid'] + "})'>"
            +d['name']+"</a></td><td>"
            +d['trade']+"</td><td>";
            if(d['old_agent'] != '') { ret += d['old_agent']; }
            else { ret += d['new_agent']; }
            ret += "</td><td>"
            +"<button onClick='showControl("+d['artid']+")'>Control</button>"
            +"</td><td>"
            +"<button onClick='showBid("+d['artid']+")'>Bid</button>"
            +"</td><td>"
            +"<button onClick='showPrint("+d['artid']+")'>Print</button>"
            +"</td><td>"
            +d['description']
            +"</td>";
          return ret;
        });
      //showError('trace:', data);
      return false;
    },
    error: function(qXHR, textStatus, errorThrown) {
      showError("ERROR in " + formUrl + ": " + textStatus);
      return false;
    }
  });
}

function addArtist() {
  formdata = $('#newArtistForm').serialize();
  formUrl = "scripts/setArtDetails.php";
  $.ajax({
    type: "POST",
    data: formdata,
    url: formUrl,
    success: function(data, textStatus, jqXHR) {
      //showError('trace:', data);
      $('#newArtist').dialog('close');
      location.reload();
      //getArtist({id : $('#newperid').val()});
      //getCurrentShow();
      return false;
    },
    error: function(jqXHR, textStatus, errorThrown) {
      showError("ERROR in " + formUrl + ": " + textStatus, jqXHR);
      return false;
    }
  });
}

function updateAssignment() {
  formdata = $('#artistForm').serialize();
  formUrl = "scripts/setArtDetails.php";
  $.ajax({
    type: "POST",
    data: formdata,
    url: formUrl,
    success: function(data, textStatus, jqXHR) {
      //showError('trace:', data);
      getArtist({id: $('#perid').val()});
      getCurrentShow();
      return false;
    },
    error: function(jqXHR, textStatus, errorThrown) {
      showError("ERROR in " + formUrl + ": " + textStatus, jqXHR);
      return false;
    }
  });
}

function sendEmail(id) {
  var formUrl = "scripts/artEmail.php";
  var formData = "id="+id;
  $.ajax({
    url: formUrl,
    data: formData,
    method: "GET",
    success: function(data, textStatus, jqXHR) {
      if(data['error']!=null) { showError(data['error']); }
      showAlert("To: " + data['to'] + "<br/>" + data['body']['Data']);
    },
    error: function(jqXHR, textStatus, errorThrown) {
      showError("ERROR in " + formUrl + ": " + textStatus, jqXHR);
      return false;
    }
  });
}

function showControl(id) {
    var formUrl = "scripts/artControlSheet.php";
    var formData = "id="+id;
    window.open('showControl.php?id='+id);
    return false;
    $.ajax({
        url: formUrl,
        data: formData,
        method: "GET",
        success: function(data, textStatus, jqXHR) {
            if(data['error']!=null) { showError(data['error']); }
            //showAlert("To: " + data['to'] + "<br/>" + data['body']['Data']);
            showError('trace:', data);
        },
        error: function(jqXHR, textStatus, errorThrown) {
            showError("ERROR in " + formUrl + ": " + textStatus, jqXHR);
            return false;
        }
    });

}

function showBid(id) {
    window.open('showBid.php?id='+id);
}

function showPrint(id) {
    window.open('showPrint.php?id='+id);
}
