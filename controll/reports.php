<?php
require_once "lib/base.php";
//initialize google session
$need_login = google_init("page");

$page = "reports";
if(!$need_login or !checkAuth($need_login['sub'], $page)) {
    bounce_page("index.php");
}

$cdn = getTabulatorIncludes();
page_init($page,
    /* css */ array('css/base.css',
                    $cdn['tabbs5'],
                   ),
    /* js  */ array($cdn['tabjs'],
                    $cdn['popjs'],
                    'js/reports.js',
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

// loop ver the groups directory and local groups directory finding groups to make into tabs
$reports = [];
if ($groupDir = opendir(__DIR__ . '/reports/groups')) {
    while (false !== ($file = readdir($groupDir))) {
        if (str_ends_with($file, '.grp')) {
            $report = parse_ini_file(__DIR__ . "/reports/groups/$file" , true);
            if (checkAuth($need_login['sub'], $report['group']['auth'])) {
                $report['group']['file'] = "groups/$file";
                $report['group']['prefix'] = "reports";
                $reports["groups/$file"] = $report;
            }
        }
    }
    closedir($groupDir);
}
if ($groupDir = opendir(__DIR__ . '/reports/local_groups')) {
    while (false !== ($file = readdir($groupDir))) {
        if (str_ends_with($file, '.grp')) {
            $report = parse_ini_file(__DIR__ . "/reports/local_groups/$file", true);
            if (checkAuth($need_login['sub'], $report['group']['auth'])) {
                $report['group']['file'] = "local_groups/$file";
                $report['group']['prefix'] = 'local_reports';
                $reports["local_groups/$file"] = $report;
            }
        }
    }
    closedir($groupDir);
}

if (array_key_exists('name', $_REQUEST)) {
    // we have a run a report directly, find the name in the group file
    $reportName = $_REQUEST['name'];
    $found = false;
    foreach ($reports AS $group => $list) {
        foreach ($list AS $name => $values){
            if ($name == 'group') {
                $config_vars['group'] = $values;
                continue;
            }
            $rptname = preg_replace('/^[0-9]+/', '', $name);
            if ($rptname == $reportName) {
                $found = true;
                break;
            }
        }
        if ($found)
            break;
    }

    if ($found) {
        $config_vars['reportName'] = $name;
        $config_vars['groupName'] = $group;
        $config_vars['values'] = $values;
        $prompt = 1;
        $prompts = [];
        while (array_key_exists('P' . $prompt, $_REQUEST)) {
            $prompts[] = $_REQUEST['P' . $prompt];
            $prompt++;
        }
        $config_vars['prompts'] = $prompts;
    }
}

?>
<script type='text/javascript'>
    var config = <?php echo json_encode($config_vars); ?>;
    var reports = <?php echo json_encode($reports); ?>;
</script>
<ul class='nav nav-tabs mb-3' id='reports-tab' role='tablist'>
<?php
// now make the tabs
$active = ' active';
if (count($reports) > 0) {
    $keys = array_keys($reports);
    sort($keys);
    for ($i = 0; $i < count($keys); $i++) {
        $key = $keys[$i];
        $report = $reports[$key];

        $hdr = $report['group'];
        $name = $hdr['name'];
        $desc = $hdr['description'];
        echo <<<EOS
    <li class='nav-item' role='presentation'>
        <button class='nav-link$active' id='$name-tab' data-bs-toggle='pill' data-bs-target='#$name-pane' type='button'
                role='tab' aria-controls='nav-$name' aria-selected='true' onclick="settab('$name-pane');">$desc
        </button>
    </li>   
EOS;
           $active = '';
    }
}
if (checkAuth($need_login['sub'], 'admin')){
?>
    <li class='nav-item' role='presentation'>
        <button class='nav-link' id='oldreports-tab' data-bs-toggle='pill' data-bs-target='#oldreports-pane' type='button'
                role='tab' aria-controls='nav-oldreports' aria-selected='true' onclick="settab('oldreports-pane');">Old Reports
        </button>
    </li>
<?php } ?>
</ul>

<div class='tab-content ms-2' id='reports-content'>
<?php
$active = ' active';
$reportPrompts = [];
if (count($reports) > 0) {
    foreach ($reports AS $rptkey => $report) {
        $grpname = $report['group']['name'];
        echo <<<EOS
    <div class='tab-content $active' id='$grpname-content' tabindex='0' hidden>
        
EOS;
        $groupRpts = array_keys($report);
        sort($groupRpts);
        echo <<<EOS
        <ul class="nav nav-pills mb-3" id="$name-content-tab" role="tablist">
EOS;
        $active2 = ' active';
        foreach ($groupRpts as $key) {
            if ($key == 'group')
                continue;   // skip the header

            $rpt = $report[$key];
            //	template=member_duplicates.rpt
            //name='Duplicate Memberships'
            //	description="Duplicate Memberships of those that only allow for 1"
            //	auth=registration
            //	type=rpt
            $name = $rpt['name'];
            $group = $report['group'];
            $fileName = $group['file'];
            $prefix = $group['prefix'];
            $keys = array_keys($rpt);
            $prompts = [];
            sort($keys);
            for ($i = 0; $i < count($keys); $i++) {
                if (!str_starts_with($keys[$i], 'P'))
                    continue;

                $prompts[] = explode('/~/', $rpt[$keys[$i]]);
            }
            $tab = str_replace(' ', '-', $name);
            if (count($prompts) > 0) {
                $reportPrompts[$key] = $prompts;
                $onclick = "showPrompts('$key', '$prefix', '$fileName');";
            } else {
                $onclick = "noPrompts('$key', '$prefix', '$fileName');";
            }
            $desc = $rpt['description'];
            echo <<<EOS
            <li class="nav-item" role="presentation $active">
                <span class="d-inline-block" tabindex="0" data-bs-toggle="tooltip" data-bs-placement="right" data-bs-title="$desc">
                    <button class="nav-link" id="$tab-tab" data-bs-toggle="pill" data-bs-target="#gen-report-content" type="button"
                        role="tab" aria-controls="$grpname-tab" aria-selected="false" onclick="$onclick" tabindex="-1">
                        $name
                    </button>
                </span>
            </li>
EOS;
        }
        $active2 = '';
        echo <<<EOS
        </ul>
    </div>
EOS;
        $active = '';
    }
    if (count($reportPrompts) > 0) {
?>
    <script type='text/javascript'>
        var reportPrompts = <?php echo json_encode($reportPrompts); ?>;
    </script>
<?php
    }
    ?>
    <div class='tab-content ms-2' id='gen-report-content'>
        <div class='container-fluid' id='report-prompt-div'></div>
        <div class='container-fluid' id='report-content-div'></div>
    </div>
    <?php
}
?>
    <div class='tab-pane fade show <?php echo $active; ?>' id='oldreports-pane' role='tabpanel' aria-labelledby='oldreports-tab' tabindex='0'>
        <div class='container-fluid'>
  <a href='reports/artSales.php'>Artshow amounts sold</a><br/>
  <a href='reports/artists.php'>Artists since <?PHP echo $con['minComp']; ?></a><br/>
  <a href="reports/artInventory.php">Art Inventory</a><br/>
  <a href='reports/newMembers.php'>New Members</a><br/>
  <a href='reports/clubHistory.php'><?PHP echo $controll['clubname']; ?> History</a><br/>
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
    if (true || false) {
        ?>
  <form action='reports/artCheckout.php' method='GET'>
    <select name='artid'>
        <?php
            $artistQ = <<<EOS
SELECT ery.exhibitorNumber, e.exhibitorName, ery.id
FROM `exhibitsRegionYears` xry 
JOIN exhibitorRegionYears ery on xry.id=ery.exhibitsRegionYearId 
JOIN exhibitorYears ey on ey.id=ery.exhibitorYearId 
JOIN exhibitors e on e.id=ey.exhibitorId 
JOIN exhibitsRegions xr on xr.id = xry.exhibitsRegion and xr.name like '%Art Show%'
WHERE xry.conid=? and ery.exhibitorNumber is not null
ORDER BY ery.exhibitorNumber;
EOS;
            $artistR = dbSafeQuery($artistQ, 'i', array($conid));
            while($artist = $artistR->fetch_assoc()) {
                printf("<option value = '%s'>%s (%s)</option>",
                    $artist['id'], $artist['exhibitorName'], $artist['exhibitorNumber']);
            }
        ?>
    </select>
    <input type='submit' value='Artshow Checkout'/>
  </form>
    <?php } ?>
        </div>
    </div>
    <div id='result_message' class='mt-4 p-2'></div>
    <pre id='test'></pre>
</div>
<?php
page_foot($page);
