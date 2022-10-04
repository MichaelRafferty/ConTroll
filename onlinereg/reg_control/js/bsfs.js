$(document).ready(function() {
  showBlock('#badgeList');
  showBlock('#searchPerson');
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


function getList() {
  var formUrl = 'scripts/getBsfsList.php';
  $.ajax({
    url: formUrl,
    method: "GET",
    success: function (data, textStatus, jqXHR) {
      showBadgeList(data['bsfs']);
    }
  });
}
function showBadgeList(data) {
  $('#bsfsNames').empty();
  var badgeList = d3.select("#bsfsNames").selectAll("tr").data(data)
    .enter().insert("tr", ":first-child").html(function(d) { return showPerson(d); });
}

function showPerson(data) {
  var formid = "badge"+data['id'];
  var ret = "<form id='"+formid+"' action='javascript:void(0)'>";
  ret += "<input type='hidden' form='"+formid+"' name='perid' value='"+data['perid']+"'/>";
  var cont = false;
    ret+= "<td>"+data['name']+"</td>";
    ret+= "<td>";
    ret+= bsfsTypeSelect(formid, data['type']);
    ret+="</td>";
    ret+= "<td>"
    ret+= "<input form='"+formid+"' type='text' size=4 value='"+data['year']
        + "' name='year'/>";
    ret+="</td>";
    ret+= "<td>";
    ret+= "<input form='"+formid+"' type='submit' value='update' onClick='updateReg(\"#"+formid+"\"); return false;'/>";
  ret += '</td>'
  ret += "</form>";

  return ret;
}

function bsfsTypeSelect(form, cur) {
  var ret="<select form='"+form+"' name='type'>";
    ret+="<option value='none'";
    if(cur== '') { ret+= " selected='selected'"; }
    ret+=">None</option>";

    ret+="<option value='inactive'";
    if(cur== 'inactive') { ret+= " selected='selected'"; }
    ret+=">Inactive</option>";

    ret+="<option value='eternal'";
    if(cur== 'eternal') { ret+= " selected='selected'"; }
    ret+=">Eternal</option>";

    ret+="<option value='life'";
    if(cur== 'life') { ret+= " selected='selected'"; }
    ret+=">Life</option>";

    ret+="<option value='child'";
    if(cur== 'child') { ret+= " selected='selected'"; }
    ret+=">Child</option>";

    ret+="<option value='annual'";
    if(cur== 'annual') { ret+= " selected='selected'"; }
    ret+=">Annual</option>";

  ret+="</select>";
  return ret;
}

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
function addPerson(userid) {
  var formUrl = 'scripts/listBsfs.php';
  var formData = 'perid='+userid.id;

  $.ajax({
    url: formUrl,
    data: formData,
    method: "POST",
    success: function (data, textStatus, jqXHR) {
      getList();
    }
  });
}

function updateReg(form) {
  var formData = $(form).serialize();
  var formUrl = "scripts/listBsfs.php"

  $.ajax({
    url: formUrl,
    data: formData,
    method: "POST",
    success: function (data, textStatus, jqXHR) {

      getList();
      return false;
    },
    error: function (jqXHR, textStatus, errorThrown) {
      showError(JSON.stringify(jqXHR));
      return false;
    }
  });

}

