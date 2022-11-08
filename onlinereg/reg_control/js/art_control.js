$(document).ready(function() {
  load_list();
  $('#artPriceWarn').hide();
  $('#gridSelectWrap').hide();
});

$(window).resize(function() {
    $("#main").width($('#table').width() + $('#facets').width() + 15);
});

function key(d) {
  return d['art_key']+'_'+d['item_key'];
}

function input(name, d) {
  var id = name;
  var size = 5;
  if(name == 'title') { size=30; }
  var ret = "<input size="+size+" name='" + id + "' type='text' "
      + "value='" + d[name] + "' onChange='doChange(\"item" + key(d) + "\")'/>";

  return ret;
}

function check(name, d, val) {
  if(d[name]==val) { return " selected='selected'"; }
  else { return ""; }
}

function doChange(id) {
  $('#'+id).addClass('changed');
}

function load_list() {
  var listURL = "scripts/artItems.php";
  $.ajax({
    method: "GET",
    url: listURL,
    success: function(data, textStatus, jqXHR) {
      //showError('trace:', data);
      console.log(data['art'].length);
      $('#grid').data('data', data['art']);
      $('#grid').data('rowFunc', buildRow);

      var facetList = ["artist", "art_key", "status"];
      $('#grid').data('filters', facetList);
      defineFacets('#grid', '#facets');

      //redraw('#grid');
      for(var i=0; i< data['artists'].length; i++) {
        if(data['artists'][i]['art_key'] != '') {
            $('#newItemArtistList')
                .append($(document.createElement('option'))
                .attr('value', data['artists'][i]['art_key'])
                .append(data['artists'][i]['name']));
        }
      }
      redraw('#grid');



      $("#main").width($('#table').width() + $('#facets').width() + 15);
    }
  });
}

function buildRow(art) {
    var row = $(document.createElement('tr'))
    row.attr('id', "item" + key(art));
    row.data('item', art);
    var cell = $(document.createElement('td'));
    cell.append(art['artist']);
    row.append(cell)
    cell = $(document.createElement('td'));
    cell.append(art['art_key']);
    row.append(cell)
    cell = $(document.createElement('td'));
    cell.append(art['item_key']);
    row.append(cell)
    cell = $(document.createElement('td'));
    cell.append(art['type']);
    row.append(cell)
    cell = $(document.createElement('td'));
    cell.html(input('title', art));
    row.append(cell)
    cell = $(document.createElement('td'));
    cell.html(input('min_price', art));
    row.append(cell)
    cell = $(document.createElement('td'));
    cell.html(input('sale_price', art));
    row.append(cell)
    cell = $(document.createElement('td'));
    if(art['status']=='Not In Show') {
        cell.html(input('original_qty', art));
    } else {
        cell.html(input('original_qty', art));
        cell.find('input').attr("type", "hidden");
        cell.append(art['original_qty']);
    }
    row.append(cell)
    cell = $(document.createElement('td'));
    cell.html(input('quantity', art));
    row.append(cell)
    cell = $(document.createElement('td'));
    cell.html(status_select(art));
    row.append(cell)
    cell = $(document.createElement('td'));
    cell.html(location_select(art));
    row.append(cell)
    cell = $(document.createElement('td'));
    cell.append(art['bidder']);
    row.append(cell)
    cell = $(document.createElement('td'));
    cell.append(art['final_price']);
    row.append(cell)
    cell = $(document.createElement('td'));
    cell.html(buttons(art));
    row.append(cell)

    return row;
}

function status_select(d) {
  var ret = "<select name='status' onChange='doChange(\"item"+key(d)+"\")'>"
    + "<option" + check('status', d, 'Not In Show') + ">Not In Show</option>"
    + "<option" + check('status', d, 'Checked In') + ">Checked In</option>"
    + "<option" + check('status', d, 'NFS') + " value='NFS'>NFS/Checked In</option>"
    + "<option" + check('status', d, 'BID') + ">Bid</option>"
    + "<option" + check('status', d, 'To Auction') + ">To Auction</option>"
    + "<option" + check('status', d, 'Sold Bid Sheet') + " value='Sold Bid Sheet'>Sold to bid sheet</option>"
    + "<option" + check('status', d, 'Quicksale/Sold') + ">Quicksale/Sold</option>"
    + "<option" + check('status', d, 'purchased/released') + " value='purchased/released'>Purchased/Released</option>"
    + "<option" + check('status', d, 'Removed from Show') + ">Removed From Show</option>"
    + "<option" + check('status', d, 'Checked Out') + ">Checked Out</option>"
    + "</select>";

  return ret;
}
function buttons(d) {
  var ret = "<td>";
    ret += "<button class='right' onClick='removeItem(\""+key(d)+"\")'>Delete</button>";
    ret += "<button class='right' onClick='update(\""+key(d)+"\")'>Update</button>";
    ret += "<button class='right' onclick='doAuction(\""+key(d)+"\")'>Enter Purchaser</button>";
    ret += "</td>";

  return ret;
}

function location_select(d) {
  var ret = "<select name='location' onChange='doChange(\"item"+key(d)+"\")'>"
    + "<option" + check('location', d, '') + "></option>";
  var list = d['loc_list'].split(",");
  for(var i=0; i< list.length; i++) {
    ret += "<option" + check('location', d, list[i]) + ">"+list[i]+"</option>";
  }
  ret += "</select>";

  return ret;
}

function updateChanged() {
  d3.selectAll(".changed").each(function () {
    update(key($(this).data('item')));
  });
}

function update(item) {
  if($('#lockUpdate')[0].checked) { lockUpdate(); return false; }
  var args = $('#item'+item+" :input").serialize() + "&key="+item;
  $.ajax({
    url: "scripts/updateItem.php",
    method: "POST",
    data: args,
    success: function(data, textStatus, jqXHR) { 
        //showError('trace:', data);
        $('#grid').data('data', data['art']);
        redraw('#grid'); 
        }
  });
}

function removeItem(item) {
  if($('#lockUpdate')[0].checked) { 
    lockUpdate();
    redraw("#grid"); 
    return false; 
  }
  var args = $('#item'+item+" :input").serialize() + "&key="+item + "&action=delete";
  $.ajax({
    url: "scripts/updateItem.php",
    method: "POST",
    data: args,
    success: function(data, textStatus, jqXHR) { 
        $('#grid').data('data', data['art']);
        redraw("#grid"); 
    }
  });
}

function addItem(form, close) {
  var type = $(form+"Form input[name='type']").val()
  var formdata = $(form+"Form").serialize()
  var script = "scripts/addItem.php";

  if(type=='art') {
    var min_bid = $(form+"Form :input[name='price']").val()
    var qsale =  $(form+"Form :input[name='qsale']").val()
    if(qsale != '' && parseInt(qsale) <= parseInt(min_bid)) {
      $('#artPriceWarn').show();
      return false;
    } else {
      $('#artPriceWarn').hide();
    }
  }

$.ajax({
    url: script,
    method: "POST",
    data: formdata,
    success: function (data, textStatus, jqXHR) {
      //showError('trace: ', data);
      if(data['error'] != null) { alert(data['error']); }
      else {
        $('#main').data('art', data['art']);
        $('#grid').data('data', data['art']);
        redraw('#grid');
      }
    }
  });

  if(close) { $('#newItem').dialog('close'); $("#newItemLog").empty(); }
  else { $('#newItemLog').append($('#newItemTitle').val() + " Added<br/>"); }
}

function doAuction(item) {
  var keys = item.split("_");
  $('#auctionArtItem').empty();
  $('#auctionWinner').empty();
  $('#auctionPerid').val('');
  $('#auctionPrice').val('');
  $('#auctionArtist').val(keys[0]);
  $('#auctionItem').val(keys[1]);
  fetchArt('#auctionArtist', '#auctionItem', "#auctionArtItem");
  $('#auction').dialog("open");
}

function fetchPerson(person, destination) {
  var args="id="+$('#auctionPerid').val();
  $.ajax({
    url: "scripts/getPerson.php",
    method: "GET",
    data: args,
    success: function(data, textStatus, jqXHR) {
      $(destination).empty();
      $(destination).data('person', data['results']);
      $(destination).append(data['results']['full_name'])
        .append($(document.createElement('br')))
        .append(data['results']['badge_name']);
    }
  })
}

function fetchArt(artist, item, destination) {
  $.ajax({
    url: "scripts/getArt.php",
    method: "GET",
    data: "art_key="+$(artist).val()+"&item_key="+$(item).val(),
    success: function(data, textStatus, jqXHR) {
      if(data['error']) {
        alert(data['error']);
        return;
      }
      var artItem=data['result'];
      $(destination).empty();
      $(destination).data('art', artItem);
      $(destination).append(artItem['title'])
        .append($(document.createElement('br')));
      $(destination).append(artItem['name'])
        .append($(document.createElement('br')));
      var table = $(document.createElement('table')).attr('width','100%');
      var row = $(document.createElement('tr'));
      row.append($(document.createElement('th')).attr('width','60%').append('Title'));
      if(artItem['type']=='art') {
        row.append($(document.createElement('th')).append('Min'));
      }
      row.append($(document.createElement('th')).append('Sale'));
      if(artItem['type']=='print') {
        $("#auctionPrice").val(data['sale_price']);
        row.append($(document.createElement('th')).append('Qty'));
      }
      table.append(row);
      row = $(document.createElement('tr'));
      row.append($(document.createElement('td')).append(artItem['title']));
      if(artItem['type']=='art') {
        row.append($(document.createElement('th')).append(artItem['min_price']));
      }
      row.append($(document.createElement('th')).append(artItem['sale_price']));
      if(artItem['type']=='print') {
        row.append($(document.createElement('th')).append(artItem['quantity']));
      }
      table.append(row);
      $(destination).append(table);
    }
  });
}

function purchase(form) {
  var args = $(form).serialize();
  var item = $(form + 'Item').data('art');
  if(+$('#auctionPrice').val() < +item['min_price']) {
    alert("Price must be above minimum bid"); return false;
  }
  $.ajax({
    url: "scripts/artSale.php",
    method: "POST",
    data: args,
    success: function(data, textStatus, jqXHR) {
      if('error' in data && data['error']!='') { 
        showError(data['error']); 
      }
      //$('#main').data('art', data['art']);
      //$('#grid').data('data', data['art']);
      redraw("#grid");
      alert("Artist " + data['post']['art_key'] + " Item " + data['post']['item_key'] + " Sold To " + data['post']['perid'] + " For $" + data['post']['price'] + "\nTable will update on Page Update or Reload.");
    }
  });
}

function lockUpdate() {
    alert("Lock Update Checked .. if you intend to make changes please unlock");
}
