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
  var formUrl = 'scripts/getClubList.php';
  $.ajax({
    url: formUrl,
    method: "GET",
    success: function (data, textStatus, jqXHR) {
      checkRefresh(data);
      showBadgeList(data['club']);
    }
  });
}
function showBadgeList(data) {
  $('#clubNames').empty();
  var badgeList = d3.select("#clubNames").selectAll("tr").data(data)
    .enter().insert("tr", ":first-child").html(function(d) { return showPerson(d); });
}

function showPerson(data) {
  var formid = "badge"+data['id'];
  var ret = "<form id='"+formid+"' action='javascript:void(0)'>";
  ret += "<input type='hidden' form='"+formid+"' name='perid' value='"+data['perid']+"'/>";
  var cont = false;
    ret+= "<td>"+data['name']+"</td>";
    ret+= "<td>";
    ret+= clubTypeSelect(formid, data['type']);
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

function clubTypeSelect(form, cur) {
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
            checkRefresh(data);
            displaySearchResults(data, addPerson)
        }
    });
}
function addPerson(userid) {
  var formUrl = 'scripts/listClub.php';
  var formData = 'perid='+userid.id;

  $.ajax({
    url: formUrl,
    data: formData,
    method: "POST",
    success: function (data, textStatus, jqXHR) {
      checkRefresh(data);
      getList();
    }
  });
}

function updateReg(form) {
  var formData = $(form).serialize();
  var formUrl = "scripts/listClub.php"

  $.ajax({
    url: formUrl,
    data: formData,
    method: "POST",
    success: function (data, textStatus, jqXHR) {
      checkRefresh(data);
      getList();
      return false;
    },
    error: function (jqXHR, textStatus, errorThrown) {
      showError("ERROR in " + script + ": " + textStatus, jqXHR)
      return false;
    }
  });

}

// Obsolete functions, delete once Club is rewritten
//
function hideBlock(block) {
  $(block + "Form").hide();
  $(block + "ShowLink").show();
  $(block + "HideLink").hide();
}

function showBlock(block) {
  $(block + "Form").show();
  $(block + "ShowLink").hide();
  $(block + "HideLink").show();
}

function addShowHide(block, id) {
  var show = $(document.createElement("a"));
  var hide = $(document.createElement("a"));
  show.addClass('showlink');
  hide.addClass('hidelink');
  show.attr('id', id + "ShowLink");
  hide.attr('id', id + "HideLink");
  show.attr('href', "javascript:void(0)");
  hide.attr('href', "javascript:void(0)");
  show.click(function () { showBlock("#" + id); });
  hide.click(function () { hideBlock("#" + id); });
  show.append("(show)");
  hide.append("(hide)");
  block.append(" ").append(show).append(" ").append(hide);
  container = $(document.createElement("form"));
  container.attr('id', id + "Form");
  container.attr('name', id);
  block.append(container);
  show.click()
  return container;
}


function displaySearchResults(data, callback) {
  var resDiv = $("#searchResultHolder");
  resDiv.empty();
  if (data["error"]) {
    showError(data["error"]);
    return false;
  }
  if (data["count"]) {
    $("#resultCount").empty().html("(" + data["count"] + ")");
  } else {
    $("#resultCount").empty().html("(0)");
  }

  for (var resultSet in data["results"]) {
    if (data["results"][resultSet].length == 0) { continue; }
    var setTitle = $(document.createElement("span"));
    setTitle.addClass('blocktitle');
    setTitle.append(resultSet);
    resDiv.append(setTitle)
    var resContainer = addShowHide(resDiv, resultSet);
    for (result in data["results"][resultSet]) {
      var user = data["results"][resultSet][result];
      var userDiv = $(document.createElement("div"));

      userDiv.attr('userid', user.id);
      userDiv.data('obj', data["results"][resultSet][result]);
      userDiv.addClass('button').addClass('searchResult').addClass('half');
      flags = $(document.createElement("div"));
      flags.addClass('right').addClass('half').addClass('notice');
      userDiv.append(flags);
      if (user.label) { userDiv.append(user.label + "<br/>" + "<hr/>"); }
      if (user.full_name) { userDiv.append(user.full_name + "<br/>"); }
      else { userDiv.append("***NO NAME***<br/>"); }
      if (user.legalName) { userDiv.append(user.legalName + "<br/>"); }
      if (user.badge_name) { userDiv.append(user.badge_name + "<br/>"); }
      userDiv.append($(document.createElement("hr")));
      if (user.address) { userDiv.append(user.address + "<br/>"); }
      else { userDiv.append("***NO STREET ADDR***<br/>"); }
      if (user.addr_2) { userDiv.append(user.addr_2 + "<br/>"); }
      if (user.locale) { userDiv.append(user.locale + "<br/>"); }
      else { userDiv.append("***NO CITY/STATE/ZIP***<br/>"); }
      userDiv.append($(document.createElement("hr")));
      if (user.email_addr) { userDiv.append(user.email_addr + "<br/>"); }
      if (user.phone) { userDiv.append(user.phone + "<br/>"); }
      if (user.banned == 'Y') {
        flags.append('banned<br/>');
        userDiv.addClass('banned');
      }
      else if (user.label) {
        userDiv.addClass('hasMembership');
      }
      else if (user.active == 'N') {
        flags.append('inactive<br/>');
        userDiv.addClass('inactive');
      }
      resContainer.append(userDiv);
      userDiv.click(function () { callback($(this).data('obj')); });
    }
  }
}

function submitForm(formObj, formUrl, succFunc, errFunc) {
  var postData = $(formObj).serialize();
  if (succFunc == null) {
    succFunc = function (data, textStatus, jsXhr) {
      $('#test').empty().append(JSON.stringify(data, null, 2));
    }
  };

  $.ajax({
    url: formUrl,
    type: "POST",
    data: postData,
    success: succFunc,
    error: function (JqXHR, textStatus, errorThrown) {
      $('#test').empty().append(JSON.stringify(JqXHR));
    }
  });
}

var tracker = new Array();
function track(formName) {
  tracker[formName] = new Object;
  $(formName + " :input").each(function () {
    tracker[formName][$(this).attr('name')] = false;
    $(this).on("change", function () {
      tracker[formName][$(this).attr('name')] = true;
    });
  });
}


function submitUpdateForm(formObj, formUrl, succFunc, errFunc) {
  var postData = "id=" + $(formObj + " :input[name=id]").val();
  for (var key in tracker[formObj]) {
    if (tracker[formObj][key]) {
      if ($(formObj + " :input[name=" + key + "]").attr('type') == 'radio') {
        postData += "&" + key + "=" + $(formObj + " :input[name=" + key + "]:checked").val();
      } else if ($(formObj + " :input[name=" + key + "]").attr('type') == 'checkbox') {
        postData += "&" + key + "=" + $(formObj + " :input[name=" + key + "])").attr('checked');
      } else {
        postData += "&" + key + "=" + $(formObj + " :input[name=" + key + "]").val();
      }
    }
  }
  if (succFunc == null) {
    succFunc = function (data, textStatus, jqXHR) {
      $('#test').empty().append(JSON.stringify(data));
    }
  };
  $.ajax({
    url: formUrl,
    type: "POST",
    data: postData,
    success: succFunc,
    error: function (JqXHR, textStatus, errorThrown) {
      $('#test').empty().append(JSON.stringify(JqXHR));
    }
  });
}

function testValid(formObj) {
  var errors = 0;

  $(formObj + " :required").map(function () {
    if (!$(this).val()) {
      $(this).addClass('need');
      errors++;
    } else {
      $(this).removeClass('need');
    }
  });

  return (errors == 0);
}

// end obsolete functions
