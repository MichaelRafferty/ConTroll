//import { TabulatorFull as Tabulator } from 'Tabulator';
//import Jquery from 'Jquery';
//import JqueryUI from 'Jquery UI';

// main screen
var message_div = null;

conid = null
// debug meaning
//  1 = console.logs
//  2 = show hidden table fields
//  4 = show hidden div
debug = 0;

var add_modal = null;
var add_result_table = null;
var add_pattern_field = null;
var addTitle = null;
var addName = null;

window.onload = function initpage() {
  debug = config.debug;
  conid = config.debug;
  var id = document.getElementById('user-lookup');
  if (id != null) {
    add_modal = new bootstrap.Modal(id, {focus: true, backdrop: 'static'});
    add_pattern_field = document.getElementById("add_name_search");
    add_pattern_field.addEventListener('keyup', (e) => {
      if (e.code === 'Enter') add_find();
    });
    id.addEventListener('shown.bs.modal', () => {
      add_pattern_field.focus()
    });
    addTitle = document.getElementById('addTitle');
    addName = document.getElementById('addName');
  }
  if (config.hasOwnProperty('msg')) {
    show_message(config.msg, 'success');
  }
}

//window.onbeforeunload = function() { return null; } // to clear unintended departures if needed

function addFindPerson() {
  addType = 'newuser';
  add_modal.show();
  addTitle.innerHTML = 'Lookup Person to Add as User';
}
function add_find() {
  clear_message('result_message_user');
  var name_search = document.getElementById('add_name_search').value.toLowerCase().trim();
  if (name_search == null || name_search == '')  {
    show_message("No search criteria specified", "warn", 'result_message_user');
    return;
  }

  // search for matching names
  $("button[name='addSearch']").attr("disabled", true);
  test.innerHTML = '';
  clear_message('result_message_user');
  if (add_result_table) {
    add_result_table.destroy();
    add_result_table = null;
  }

  clearError();
  clear_message();
  $.ajax({
    method: "POST",
    url: "scripts/mergeFindRecord.php",
    data: { name_search: name_search, },
    success: function (data, textstatus, jqxhr) {
      $("button[name='mergeSearch']").attr("disabled", false);
      if (data.error !== undefined) {
        show_message(data.error, 'error', 'result_message_user');
        return;
      }
      if (data.message !== undefined) {
        show_message(data.message, 'success', 'result_message_user');
      }
      if (data.warn !== undefined) {
        show_message(data.warn, 'warn', 'result_message_user');
      }
      add_found(data);
    },
    error: function (jqXHR, textStatus, errorThrown) {
      $("button[name='addSearch']").attr("disabled", false);
      showError("ERROR in addFindRecord: " + textStatus, jqXHR);
    }
  });
}

function settab(tabname) {
  // close all of the other tabs
  clear_message();

  // now open the relevant one, and create the class if needed
  switch (tabname) {
    case 'list-pane':
      getClubList();
      break;
    case 'keys-pane':
      console.log(tabname);
    case 'configuration-pane':
      getClubConfig();
      break;
  }
}

function getClubConfig() {
  var url = 'scripts/club_getClubConfig.php';
  clearError();
  clear_message();
  $.ajax({
    url: url,
    method: 'GET',
    success: function (data, textStatus, jhXHR) {
      openClubConfig(data);
    },
    error: function (jqXHR, textStatus, errorThrown) {
      showError("ERROR in getClubConfig: " + textStatus, jqXHR);
    },
  })
}

function openClubConfig(data) {
  if (data.error) {
    show_message(data.error, 'error');
    return;
  }
  if (data.warn) {
    show_message(data.warn, 'warn');
    return;
  }

  if(data.clubTypes) {
    configTable = new Tabulator('#clubTypesTableDiv', {
      moveableRows: true,
      history: true,
      data: data.clubTypes,
      layout: "fitDataTable",
      columns: [
        { rowHandle: true, formatter: "handle", frozen: true, width: 30, minWidth: 30, maxWidth: 30, headerSort: false },
        { field: "id", visible: debug > 0 },
        { title: "Type", field: "clubMemType" },
        { title: "Description", field: "description" },
        { title: "Expires?", field: "expires", editor:"list", editorParams:{values: ["No", "Years"]}},
        { title: "Number of Years", field: "nYears", editor:"number"},
        { title: "Badgename Flag", field: "flag" },
        { field: "sortOrder", visible: debug > 0 },
      ]
    })
  }
}

function getList() {
  var formUrl = 'scripts/getClubList.php';
  $.ajax({
    url: formUrl,
    method: "GET",
    success: function (data, textStatus, jqXHR) {
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

      getList();
      return false;
    },
    error: function (jqXHR, textStatus, errorThrown) {
      showError("ERROR in " + script + ": " + textStatus, jqXHR)
      return false;
    }
  });

}
