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
                   ),
              $need_login);


$con = get_conf("con");
$controll = get_conf("controll");
$conid=$con['id'];

$debug = get_conf('debug');

if (array_key_exists('controll_reports', $debug))
    $debug_reports=$debug['controll_reports'];
else
    $debug_reports = 0;

$config_vars = array();
$config_vars['pageName'] = 'reports';
$config_vars['debug'] = $debug_reports;
$config_vars['conid'] = $conid;
?>
<script type='text/javascript'>
    var config = <?php echo json_encode($config_vars); ?>;
</script>
    <ul class='nav nav-tabs mb-3' id='reports-tab' role='tablist'>
        <li class='nav-item' role='presentation'>
            <button class='nav-link active' id='oldreports-tab' data-bs-toggle='pill' data-bs-target='#oldreports-pane' type='button'
                    role='tab' aria-controls='nav-oldreports' aria-selected='true' onclick="settab('oldreports-pane');">Old Reports
            </button>
        </li>
    </ul>
</ul>
<div class='tab-content ms-2' id='reports-content'>
    <div class='tab-pane fade show active' id='oldreports-pane' role='tabpanel' aria-labelledby='oldreports-tab' tabindex='0'>
        <div class='container-fluid'>
  <a href='reports/artSales.php'>Artshow amounts sold</a><br/>
  <a href='reports/artists.php'>Artists since <?PHP echo $con['minComp']; ?></a><br/>
  <a href="reports/artInventory.php">Art Inventory</a><br/>
  <a href='reports/newMembers.php'>New Members</a><br/>
  <a href='reports/duplicates.php'>Duplicate Memberships</a><br/>
  <a href='reports/badgeTypes.php'>Badge Types</a><br/>
  <a href='reports/clubHistory.php'><?PHP echo $controll['clubname']; ?> History</a><br/>
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
    <?php // this stuff below is obsolete and needs to be rewritten for mondern art show
    if (false) {
        ?>
  <form action='reports/artCheckout.php' method='GET'>
    <select name='artid'>
        <?php
            $artistQ = <<<EOS
SELECT S.id, art_key, TRIM(CONCAT_WS(' ', P.first_name, P.last_name)) AS name
FROM artshow AS S
JOIN artist AS A ON A.id = S.artid
JOIN perinfo AS P ON P.id=A.artist
WHERE conid=?
ORDER by art_key;
EOS;
            $artistR = dbSafeQuery($artistQ, 'i', array($conid));
            while($artist = $artistR->fetch_assoc()) {
                printf("<option value = '%s'>%s (%s)</option>",
                    $artist['id'], $artist['name'], $artist['art_key']);
            }
        ?>
    </select>
    <input type='submit' value='Artshow Checkout'/>
  </form>
    <?php } ?>
    <form action='reports/badgeHistory.php' method='GET'>
        <input type='number' name='perid' size=6/>
        <input type='submit' value='Get Badge History'/>
    </form>
        </div>
    </div>
</div>
