<?php
require_once "lib/base.php";
//initialize google session
$need_login = google_init("page");

$page = "artshow";
if(!$need_login or !checkAuth($need_login['sub'], $page)) {
    bounce_page("index.php");
}

page_init($page,
    /* css */ array('css/base.css',
                   ),
    /* js  */ array('js/d3.js',
                    'js/base.js',
                    'js/artshow.js'
                   ),
              $need_login);
$con = get_con();
$conid = $con['id'];

$conf = get_conf('con');

?>
<div id='main'>
  <div id='currentNumbers' class='half'>
    <span class='blocktitle'>Artshow Registrations</span>
    <?php 
        $artshowQ = "SELECT * from artshow_reg where conid=$conid;";
        $as_status = fetch_safe_assoc(dbQuery($artshowQ));
        $artshowQ = "SELECT attending, count(id) as c from artshow where conid=$conid GROUP BY attending;";
        $countR = dbQuery($artshowQ);
        $artist_count = array('all' => 0, 'mailin' => 0);
        while($countItem = fetch_safe_assoc($countR)) {
            switch($countItem['attending']) {
                case 'mailin': 
                    $artist_count['mailin'] += $countItem['c'];
                case 'attending':
                case 'agent':
                default:
                    $artist_count['all'] += $countItem['c'];
            }
        }
    ?>
    <a href='reports/artshowRegReport.php'>Report</a><br/>
    Artshow Space Remaining:
    <?php echo ($as_status['max_art'] - $as_status['cur_art']); ?> Panels
    <?php echo ($as_status['max_table'] - $as_status['cur_table']); ?> Tables,
    Printshop:
    <?php echo ($as_status['max_print'] - $as_status['cur_print']); ?> Panels,
    Mailin: 
    <?php echo ($as_status['max_mailin'] - $as_status['cur_mailin']); ?> Panels
    <br/>
    # Artists: 
    <?php echo $artist_count['all']; ?>
    , # Mailin:
    <?php echo $artist_count['mailin']; ?>
  </div>
  <div id='searchResults' class='half right'>
    <span class='blocktitle'>Search Results</span>
    <span id="resultCount"> </span>
    <div id='searchResultHolder'>
    </div>
  </div>
<div class='half'>
  <div id="searchPerson"><span class="blocktitle">Search Person</span>
    <form class='inline' id="findPerson" method="GET" action="javascript:void(0)">
      Name: <input type="text" name="full_name" id="findPersonFullName"></input>
      <input type="submit" value="Find" onClick='findPerson("#findPerson")'></input>
    </form>
  </div>

  <div id="artist">
    <form id="artistForm" method="POST" action="javascript:void(0)">
      <input type='hidden' name='perid' id='perid'/>
      <input type='hidden' name='artid' id='artid'/>
      <input type='hidden' name='agent' id='agent'/>
      <input type='hidden' name='detailsId' id='detailsId'/>
      <table class='inline2'><tr><td class='formlabel'>Artist Name</td></tr><tr><td class='formfield' id='pername'></td></tr></table>
      <table class='inline2'><tr><td class='formlabel'>Badge Status</td></tr><tr><td class='formfield' id='badge'></td></tr></table>
      <table class='inline2'><tr><td class='formlabel'>Trade Name</td></tr><tr><td id='artname'></td></tr></table>
      <table class='inline2'><tr><td class='formlabel'>Emails</td></tr><td class='formfield' id='emails'></td></tr></table>
      <table class='inline2'><tr><td class='formlabel'>Website</td></tr><td class='formfield' id='website'></td></tr></table>
      <br/>
      <table class='inline'><tr><td class='formlabel'>Description</td></tr><td class='formfield' id='description'></td></tr></table>
      <br/>
    <span id='agent_row'>
      <table class='inline2'><tr><td class='formlabel'>Agent Request</td></tr><tr><td class='formfield' id='agent_request'></td></tr></table>
      <table class='inline2'><tr><td class='formlabel'>Agent Name</td></tr><tr><td class='formfield' id='agent_name'></td></tr></table>
      <table class='inline2'><tr><td class='formlabel'>Agent Badge Status</td></tr><tr><td class='formfield' id='agent_badge'></td></tr></table>
      <table class='inline2'><tr><td class='formlabel'>Agent Id</td></tr><tr><td class='formfield' id='agent_id'></td></tr></table>
    </span>
      <br/>
      <table class='inline'><tr><td class='formlabel'>In Show</td></tr><td class='formfield' id='inshow'></td></tr></table>
      <table class='inline'><tr><td class='formlabel'>Artist Num</td></tr><td class='formfield'><input type='text' id='key' name='key' size=5></input></td></tr></table>
      <table class='inline'><tr><td class='formlabel'># Items</td></tr><td class='formfield' id='itemcount'></td></tr></table>
      <br/>
      <table class='inline'><tr><td class='formlabel'>Description For Show</td></tr><td class='formfield' id='show_desc'></td></tr></table>
      <br/>
      <table><tr>
        <tbody>
        <th class='formfield'>Art </th><td class='formfield'>&#8531;Panels</td>
        <td class='formfield'> <input type='text' size=2 name='asp_count' id='asp_count'></input></td>
        <td class='formfield'>&#188;Tables</td>
        <td><input type='text' size=2 name='ast_count' id='ast_count'></input>
        </td>
        <th class='formfield'>Print </th><td class='formfield'>&#8531;Panels</td>
          <td class='formfield'><input type='text' size=2 name='psp_count' id='psp_count'></input></td>
        </td></tr>
        <tr><td class='formfield'>ids:</td>
          <td class='formfield' colspan=2><input type='text' size=15 name='asp' id='asp' placeholder='list of panels'></input></td>
          <td class='formfield' colspan=2><input type='text' size=15 name='ast' id='ast' placeholder='list of tables'></input>
        </td>
       <td class='formfield'>ids:</td>
         <td class='formfield' colspan=2'><input type='text' size=15 name='psp' id='psp' placeholder='list of print'></input></td></tr>
        </tbody>
     </table>
     <input type='submit' value='Update Assignments' onClick='updateAssignment()'></input>
     <br/>History
     <table id='artistHistoryForm'>
        <thead>
          <tr><th>Con</th><th>Ammount</th></tr>
        </thead>
        <tbody id='artistHistoryInfo'>
        </tbody>
      </table>
    </form>
  <hr/>
  <div id='currentShow'><span class='blocktitle'>Current Artshow</span>
    <a class='showlink' id='currentShowShowLink' href='javascript:void(0)'
      onclick='showBlock("#currentShow")'>(show)</a>
    <a class='hidelink' id='currentShowHideLink' href='javascript:void(0)'
      onclick='hideBlock("#currentShow")'>(hide)</a>
    <table id='currentShowForm'>
      <thead>
        <tr>
          <th>Artist<br/>No.</th><th>Artist Name</th><th>Trade Name</th><th>Agent</th>
          <th>Control Sheet</th><th>Bid Sheet</th><th>Print Sheet</th><th>Description</th>
        </tr>
      </thead>
      <tbody id='currentShowArtists'>
      </tbody>
    </table>
  </div>
  </div>
</div>
</div>
<pre id='test'></pre>
<div id='alert' class='popup'>
    <div id='alertInner'>
    </div>
    <button class='center' onclick='$("#alert").hide();'>Close</button>
</div>
<div id='newArtist'>
    <div id='newArtist'>
        <span id='newArtistName'></span></br>
        <span id='newArtistTrade'></span></br>
    </div>
    <form id='newArtistForm' action='javascript:void(0)'>
        <input type='hidden' name='artid' id='newartistId'></input>
        <input type='hidden' name='perid' id='newperid'></input>
        Artist Type: <select name='mailin'>
            <option default='default' value='attending'>Attending</option>
            <option value='agent'>Agent</option>
            <option value='mailin'>Mailin</option>
            <option value='special'>Special (e.g. GoH)</option>
        </select>
        <br/>
        <div id='agent_name'>Agent Name: <input type='text' id='agent_name_in' name='agent_request'></input></div>
        <table>
            <tr><td class='righttext'>Artshow Panel Thirds: </td>
                <td>
                    <input type='number' name='asp_count' min=0 max=99>
                    </input></label>
                </td>
            </tr>
            <tr><td class='righttext'>Printshop Panel Thirds: </td>
                <td>
                    <input type='number' name='psp_count' min=0 max=99>
                    </input></label>
                </td>
            </tr>
            <tr><td class='righttext'>Artshow Table Quarters: </td>
                <td>
                    <input type='number' name='ast_count' min=0 max=99>
                    </input></label>
                </td>
            </tr>
        </table>
        Description/Special Requriements:<br/><textarea rows=4 cols=40 id='desc' name='desc' placeholder='Enter art description or requirements for display'></textarea>
        <input type='submit' value='Add to Show' onClick='addArtist()'></input><input type='reset'></input>
    </form>
</div>
<script>
$(function() {
    $('#newArtist').dialog({
        title: "Add Artist to Show",
        autoOpen: false,
        width: 500,
        height: 450,
        modal: true
    });
});
</script>
