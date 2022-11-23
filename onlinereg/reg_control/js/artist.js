$(document).ready(function() {
    showBlock('#artist');
    showBlock('#agentInfo');
    showBlock('#paymentInfo');
    showBlock('#searchPerson');
});

function findPerson(form) {
    var getData = $(form).serialize();
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

function findAgent(input) {
    var getData = "full_name="+ $(input).val();
    $.ajax({
        url: 'scripts/findPerson.php',
        method: "GET",
        data: getData,
        success: function (data, textStatus, jqXhr) {
            if(data['error'] != undefined) { console.log(data['error']); }
            displaySearchResults(data, setAgent)
        }
    });
    return false;
}

function getArtist(perid) {
  $('#artistForm')[0].reset();
  var formUrl = "scripts/getArtist.php";
  $.ajax({
    type: "GET",
    data: "perid="+perid.id,
    url: formUrl,
    success: function(data, textStatus, jqXHR) {
      console.log(data);
      loadPerson(data['person'], data['vendor'], data['artist'], data['agent']);
      //showError('trace:', data);

      return false;
    },
    error: function(jqXHR, textStatus, errorThrown) {
      showError("ERROR in " + formUrl + ": " + textStatus, jqXHR);
      return false;
    }
  });
}

function loadPerson(per, vendor, artist, agent) {
  $('#agent_name').val("");
  $('#artistForm').data('artist', per);
  $('#artistForm').data('vendor', vendor);
  $('#artistForm').data('agent', agent);
  $('#artistFormPerId').empty().append(per['id']);
  $('#fname').val(per['first_name']);
  $('#mname').val(per['middle_name']);
  $('#lname').val(per['last_name']);
  $('#suffix').val(per['suffix']);
  $('#email').val(per['email_addr']);
  $('#phone').val(per['phone']);
  $('#perid').val(per['id']);

  var provisional = false;

  if(artist != null) {
    $('#artistFormArtId').empty().append(artist['id']);
    $('#artid').val(artist['id']);
    $('#ship_addr').val(artist['ship_addr']);
    $('#ship_addr2').val(artist['ship_addr2']);
    $('#ship_city').val(artist['ship_city']);
    $('#ship_state').val(artist['ship_state']);
    $('#ship_zip').val(artist['ship_zip']);
    $('#ship_country').val(artist['ship_country']);
  } else {
    $('#artistFormArtId').empty();
    $('#artid').val();
    $('#ship_addr').val(per['address']);
    $('#ship_addr2').val(per['addr_2']);
    $('#ship_city').val(per['city']);
    $('#ship_state').val(per['state']);
    $('#ship_zip').val(per['zip']);
    $('#ship_country').val(per['country']);
  }

  if(agent != null) {
    $('#artistFormAgentId').empty().append(agent['id']);
    $('#agentid').val(agent['id']);
    $('#ag_fname').val(agent['first_name']);
    $('#ag_mname').val(agent['middle_name']);
    $('#ag_lname').val(agent['last_name']);
    $('#ag_suffix').val(agent['suffix']);
    $('#ag_email').val(agent['email_addr']);
    $('#ag_phone').val(agent['phone']);
  } else {
    $('#artistFormAgentId').empty();
    $('#agentid').val('');
  }

  if(vendor != null) {
    if((artist == null) || (artist['vendor'] == '')) {
        provisional=true;
    }

    if(provisional) {
        $('#vendorProvisional').empty().append('Provisional');
    } else {
        $('#vendorProvisional').empty();
    }

    $('#artistFormVendorId').empty().append(vendor['id']);
    $('#vendorid').val(vendor['id']);
    $('#vendor_name').val(vendor['name']);
    $('#vendor_site').val(vendor['site']);
    $('#vendor_description').val(vendor['description']);
  } else {
    $('#vendorProvisional').empty().append("No Vendor Account");
    $('#artistFormVendorId').empty();
    $('#vendorid').val('');
  }

  if(vendor == null) {
    alert("Please have the artist create a vendor account");
  }
  else if(artist == null) {
    if(confirm("New Artist?")) {
      updateArtist();
    } else {
      alert('clicking "Create/Update Artist" will create a new Artist record');
    }
  }
}

function updateArtist() {
  var formUrl= 'scripts/updateArtist.php';
  var postdata = $('#artistForm').serialize();
  $.ajax({
    url: formUrl,
    method: "POST",
    data: postdata,
    success: function (data, textStatus, jqXHR) {
      //showError('trace: ', data);
      getArtist({id: data['perid']});
      return false;
    },
    error: function(jqXHR, textStatus, errorThrown) {
      showError("ERROR in " + formUrl + ": " + textStatus, jqXHR);
      return false;
    }
  });
}

function setAgent(id) {
  var formUrl = "scripts/updateArtist.php";
  var postdata = "agent_only=true&agent_id="+id.id+
    "&artid="+$('#artid').val()+
    "&perid="+$('#perid').val();

  $.ajax({
    url: formUrl,
    method: "POST",
    data: postdata,
    success: function (data, textStatus, jqXHR) {
      getArtist({id: data['perid']});
      return false;
    },
    error: function(jqXHR, textStatus, errorThrown) {
      showError("ERROR in " + formUrl + ": " + textStatus, jqXHR);
      return false;
    }
  });
}

function changeChecks() {
  switch($('#checks_to').val()) {
    case 'other':
      $('#checks_other').removeAttr('disabled');
      break;
    case 'agent':
      var ag_name = $('#ag_fname').val() + " " + $('#ag_lname').val();
      $('#checks_other').val(ag_name);
      $('#checks_other').attr('disabled','disabled');
      break;
    case 'artist':
    default:
      var name = $('#fname').val() + " " + $('#lname').val();
      $('#checks_other').val(name);
      $('#checks_other').attr('disabled','disabled');
      break;
  }
}
function changeAddr() {
  switch($("#ship_to").val()) {
    case 'other':
      $('#other_addr2').removeAttr('disabled');
      $('#other_addr1').removeAttr('disabled');
      $('#other_city').removeAttr('disabled');
      $('#other_state').removeAttr('disabled');
      $('#other_zip').removeAttr('disabled');
      $('#other_country').removeAttr('disabled');
      $('#other_email').removeAttr('disabled');
      $('#other_phone').removeAttr('disabled');
      break;
    case 'agent':
      $('#other_addr2').attr('disabled', 'disabled');
      $('#other_addr1').attr('disabled', 'disabled');
      $('#other_city').attr('disabled', 'disabled');
      $('#other_state').attr('disabled', 'disabled');
      $('#other_zip').attr('disabled', 'disabled');
      $('#other_country').attr('disabled', 'disabled');
      $('#other_email').attr('disabled','disabled');
      $('#other_phone').attr('disabled','disabled');
      var agent = $('#artistForm').data('agent')

      $('#other_addr2').val(agent['addr_2']);
      $('#other_addr1').val(agent['address']);
      $('#other_city').val(agent['city']);
      $('#other_state').val(agent['state']);
      $('#other_zip').val(agent['zip']);
      $('#other_country').val(agent['country']);
        $('#other_email').val(agent['other_email']);
        $('#other_phone').val(agent['other_phone']);
      break;
    case 'artist':
    default:
      $('#other_addr2').attr('disabled', 'disabled');
      $('#other_addr1').attr('disabled', 'disabled');
      $('#other_city').attr('disabled', 'disabled');
      $('#other_state').attr('disabled', 'disabled');
      $('#other_zip').attr('disabled', 'disabled');
      $('#other_country').attr('disabled', 'disabled');
      $('#other_email').attr('disabled','disabled');
      $('#other_phone').attr('disabled','disabled');
      var artist = $('#artistForm').data('artist')

      $('#other_addr2').val(artist['addr_2']);
      $('#other_addr1').val(artist['address']);
      $('#other_city').val(artist['city']);
      $('#other_state').val(artist['state']);
      $('#other_zip').val(artist['zip']);
      $('#other_country').val(artist['country']);
      $('#other_email').val(artist['other_email']);
      $('#other_phone').val(artist['other_phone']);
      break;
  }
}

function resetPw(vendor) {
    $.ajax({
        url: 'scripts/setPassword.php',
        method: "GET",
        data: 'vendor=' + vendor,
        success: function (data, textStatus, jqXhr) {
            if(data['error'] != undefined) { console.log(data['error']); }
            alert(data['password']);
        }
    });
}

function approveArtist(id) {
    $.ajax({
        url: 'scripts/getRequest.php',
        method: "GET",
        data: {'id': id},
        success: function (data, textStatus, jqXhr) {
            if(data['error'] != undefined) { console.log(data['error']); }
            if(data['count'] > 0) { // there are already people for this
                displaySearchResults(data, getArtist)
            } else {
                alert("Please create the Person using that tab.");
            }
        }
    });
}

