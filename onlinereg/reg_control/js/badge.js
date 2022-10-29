$(document).ready(function() {
  showBlock('#badgeList');
  getList();
  $('#newPersonForm').bind("reset", function (e) {
    $('#newID').val('');
    $('#oldID').val('');
    $('#updatePerson').attr('disabled', 'disabled');
    $('#checkConflict').removeAttr('disabled');
    $('#oname').empty();
    $('#oemail').empty();
    $('#oaddr').empty();
    $('#ophone').empty();
    $('#obadge').empty();
    $('#newPersonForm :required').map(function (e) { $(this).removeClass('need');});
    return true;
  });
});

function findPerson(form) {
    var getData = $(form).serialize();
    $.ajax({
        url: 'scripts/findPerson.php',
        method: "GET",
        data: getData,
        success: function (data, textStatus, jqXhr) {
            if(data['error'] != undefined) { console.log(data['error']); }
            displaySearchResults(data, addPerson)
        }
    });
}

function searchConflictPerson(data, textStatus, jsXHR) {
    var getData = 'id=' + data['id'];
    var formurl = "scripts/getNewPerson.php";
   $.ajax({
     method: "GET",
     data: getData,
     url: formurl,
     success:  function (data, textStatus, jqXhr) {
            if(data['error'] != undefined) { console.log(data['error']); }
            $('#newID').val(data['new']['id']);
            displaySearchResults(data, loadOldPerson)

            var resDiv = $("#searchResultHolder");

            var newPersonButton = $(document.createElement("button"));
            newPersonButton.append("New Person");
            newPersonButton.attr("type", "button");
            resDiv.append(newPersonButton);
        
            newPersonButton.click(function () {
                var formData = "newID=" + data['new']['id'];
                var script = "scripts/addPersonFromConflict.php";
                if(confirm("Please only use this if the person you are looking for isn't in the list above this.  If only minor changes are needed then click on their name in the list above this and you will be able to update their record.  Click 'Cancel' if the name is in the list above the \"New Person\" button.  otherwise click 'OK'")) {
                  $.ajax({
                    data: formData,
                    method: "POST",
                    url: script,
                    success: updatePersonCatch,
                    error: function (jqXHR, textStatus, errorThrown) {
                        showError("ERROR in " + script + ": " + textStatus, jqXHR);
                        return false;
                    }
                  });
                  return false;
                } else { return false; } 
            });
        },
     error: function (jqXHR, textStatus, errorThrown) {
      showError("ERROR in " + formurl + ": " + textStatus, jqXHR));
      return false;
     }
   });
}

function loadOldPerson(obj) {
  var oldAddr = obj['address'] + "<br/>";
  if(obj['addr_2'] != '') { oldAddr+= obj['addr_2'] + "<br/>"; }
  oldAddr += obj['locale'];
  $('#oname').empty().append(obj['full_name']);
  $('#ophone').empty().append(obj['phone']);
  $('#oemail').empty().append(obj['email_addr']);
  $('#oaddr').empty().append(oldAddr);
  $('#obadge').empty().append(obj['badge_name']);
  $('#oldID').val(obj['id']);

  $('#checkConflict').attr('disabled', 'disabled');
  $('#updatePerson').removeAttr('disabled');
}


function addPerson(userid) {
  var formUrl = 'scripts/listBadge.php';
  var formData = 'perid='+userid.id;

  $.ajax({
    url: formUrl,
    data: formData,
    method: "GET",
    success: function (data, textStatus, jqXHR) {
        //$('#test').empty().append(JSON.stringify(data, null, 2));
      getList();
    }
  });
}


function getList() {
  var formUrl = 'scripts/getBadgeList.php';
  $.ajax({
    url: formUrl,
    method: "GET",
    success: function (data, textStatus, jqXHR) {
        //$('#test').empty().append(JSON.stringify(data, null, 2));
      showBadgeList(data['badges']);
    }
  });
}

function showBadgeList(data) {
  $('#badges').empty();
  var badgeList = d3.select("#badges").selectAll("tr").data(data)
    .enter().insert("tr", ":first-child").html(function(d) { return showPerson(d); });
}

function showPerson(data) {
  var formid = "badge"+data['id'];
  var ret = "<form id='"+formid+"' action='javascript:void(0)' perid='"
            + data['perid'] + "'>";
    var cont = false;

    ret += "<input form='" + formid + "' type='hidden' name='regid' value='" + data['regid'] + "'/>";
    ret += "<input form='" + formid + "' type='hidden' name='id' value='" + data['id'] + "'/>";

    ret += "<td class='small'>";
    ret += data['name'] + "<br/>";
    //badgeType
    if(data['regid']!=null && data['regid']!='') {
      cont=true;
      ret += data['label'] + " ("+data['regid']+")";
    } else {
      ret+= badgeSelect(formid);
    }
    ret += "</td>";

    ret += "<td class='small'>";
    if (data['badge_name']=='') { ret += "&lt;default&gt;<br/>"; }
    else { ret += data['badge_name'] + "<br/>"; }

    ret += "</td>";
    ret += "<td class='small'>";
    if (cont) { 
        ret += "&nbsp;";
    } else {
        ret += "<input form='" + formid + "' type='submit' value='update badge' onClick='updateReg(\"#" + formid + "\"); return false;'/>";  
    }
    ret += '</td>';

    ret += "<td class='small'>";
    ret+= "<input form='"+formid+"' type='submit' value='Edit Person' onClick='editPerson(\""+data['perid']+"\",\""+formid+"\"); return false;'/>";
    ret += '</td>';
    ret += "</form>";

    return ret;
}

function editPerson(perid,formid) {
    $.ajax({
        url: 'scripts/editPerson.php',
        method: 'GET',
        data: "id="+perid,
        success: function (data, textStatus, jqXhr) {
            if(data['error'] != undefined) { console.log(data['error']); }
            showEditPerson(data,formid);
        }
    });
}

function showEditPerson(perinfo,formid) {
    $('#edit_fname').val(perinfo['first_name']);
    $('#edit_lname').val(perinfo['last_name']);
    $('#edit_mname').val(perinfo['middle_name']);
    $('#edit_suffix').val(perinfo['suffix']);
    $('#edit_badge').val(perinfo['badge_name'])
    $('#edit_addr').val(perinfo['address']);
    $('#edit_addr2').val(perinfo['addr_2']);
    $('#edit_city').val(perinfo['city']);
    $('#edit_state').val(perinfo['state']);
    $('#edit_zip').val(perinfo['zip']);
    $('#edit_country').val(perinfo['country']);
    $('#edit_email').val(perinfo['email_addr']);
    $('#edit_phone').val(perinfo['phone']);
    $('#edit_id').val(perinfo['id']);
    $('#editPersonFormIdNum').empty().append(perinfo['id']);
    track("#editForm")
    $('#editDialog').dialog('open');
}

function updateReg(form) {

  var formData = $(form).serialize();
  var formUrl = "scripts/freeBadge.php"

  $.ajax({
    url: formUrl,
    data: formData,
    method: "POST",
    success: function (data, textStatus, jqXHR) {
      getList();
      return false;
    },
    error: function (jqXHR, textStatus, errorThrown) {
      showError(JSON.stringify(jqXHR, null, 2));
      return false;
    }
  });

}

function updatePersonCatch (data, textStatus, jqXHR) {
    $('#newPersonForm').trigger('reset');
    alert('Person Created or Updated. If you do not see the person in your list below please search for them.');
    $('#newPerson').hide();
    addPerson(data);
    //showError('trace:', data);
}

function getEdited(data, textStatus, jqXHR) {
   getList();
}
