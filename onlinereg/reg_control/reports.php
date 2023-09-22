<?php
require_once "lib/base.php";
//initialize google session
$need_login = google_init("page");

$page = "reports";
if(!$need_login or !checkAuth($need_login['sub'], $page)) {
    bounce_page("index.php");
}

page_init($page,
    /* css */ array('css/base.css'
                   ),
    /* js  */ array('js/d3.js',
                    'js/base.js'
                   ),
              $need_login);


$con = get_conf("con");
$control = get_conf("control");
$conid=$con['id'];

?>
<div id='main'>
  <a href='reports/artSales.php'>Artshow amounts sold</a><br/>
  <a href='reports/artists.php'>Artists since <?PHP echo $con['minComp']; ?></a><br/>
  <a href="reports/artInventory.php">Art Inventory</a><br/>
  <a href='reports/newMembers.php'>New Members</a><br/>
  <a href='reports/duplicates.php'>Duplicate Memberships</a><br/>
  <a href='reports/badgeTypes.php'>Badge Types</a><br/>
  <a href='reports/clubHistory.php'><?PHP echo $control['clubname']; ?> History</a><br/>
  <form action='reports/badgeHistory.php' method='GET'>
    Badge Hisory For:
    <input type='number' name='perid'/>
    <input type='submit' value='Get'/>
  </form>
  <form action='reports/hotel_reg.php' method='GET'>
    Registration Report For <?PHP echo $con['conname']; ?>
    <input type='number' name='conid'/>
    <input type='submit' value='Get'/>
  </form>
  <form action='reports/participants.php' method='GET'>
    Participant list for <?PHP echo $con['conname']; ?>
    <input type='number' name='conid'/>
    <input type='submit' value='Get'/>
  </form>
  <form action='reports/artCheckout.php' method='GET'>
    <select name='artid'>
        <?php
            $artistQ = "select S.id, art_key, concat_ws(' ', P.first_name, P.last_name) as name from artshow as S JOIN artist as A on A.id = S.artid JOIN perinfo as P on P.id=A.artist where conid=$conid order by art_key;";
            $artistL = dbQuery($artistQ);
            while($artist = fetch_safe_assoc($artistL)) {
                printf("<option value = '%s'>%s (%s)</option>",
                    $artist['id'], $artist['name'], $artist['art_key']);
            }
        ?>
    </select>
    <input type='submit' value='Artshow Checkout'/>
  </form>
    <form action='reports/badgeHistory.php' method='GET'>
        <input type='number' name='perid' size=6/>
        <input type='submit' value='Get Badge History'/>
    </form>
</div>
