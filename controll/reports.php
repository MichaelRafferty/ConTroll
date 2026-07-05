<?php
require_once "lib/base.php";
require_once 'lib/sessionAuth.php';

$page = 'reports';
$authToken = new authToken('web');
if (!$authToken->isLoggedIn() || !$authToken->checkAuth($page)) {
    bounce_page('index.php');
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
              $authToken);


$con = get_conf("con");
$controll = get_conf("controll");
$conid=$con['id'];

$config_vars = array();
$config_vars['pageName'] = 'reports';
$config_vars['debug'] = getConfValue('debug', 'controll_reports', 0);
$config_vars['conid'] = $conid;
$config_vars['tokenStatus'] = $authToken->checkToken();

// loop ver the groups directory and local groups directory finding groups to make into tabs
$reports = [];
if ($groupDir = opendir(__DIR__ . '/reports/groups')) {
    while (false !== ($file = readdir($groupDir))) {
        if (str_ends_with($file, '.grp')) {
            $report = parse_ini_file(__DIR__ . "/reports/groups/$file" , true);
            if ($authToken->checkAuth($report['group']['auth'])) {
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
            if ($authToken->checkAuth($report['group']['auth'])) {
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
?>
</ul>

<div class='tab-content ms-2' id='reports-content'>
<?php
$active = ' active';
$reportPrompts = [];
$reportTopNotes = [];
$reportBottomNotes = [];
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
            $type = $rpt['type'];
            $template = $rpt['template'];
            $keys = array_keys($rpt);
            $prompts = [];
            $topNotes = [];
            $bottomNotes = [];
            sort($keys);
            for ($i = 0; $i < count($keys); $i++) {
                switch (substr($keys[$i], 0, 1)) {
                    case 'P':
                        $prompt = explode('/~/', $rpt[$keys[$i]]);
                        if (count($prompt) > 4) {
                            $default = $prompt[4];
                            if (preg_match('/^#.+#$/', $default)) {
                                $prompt[4] = replaceConfigTokens($default);
                            } else {
                                switch (strtolower($default)) {
                                    case 'today':
                                        $prompt[4] = date_format(date_create(), 'Y-m-d');
                                        break;
                                    case 'yesterday':
                                        $date = date_create();
                                        $date = date_add($date, date_interval_create_from_date_string('-1 day'));
                                        $prompt[4] = date_format($date, 'Y-m-d');
                                        break;
                                    case 'thismonth':
                                        $prompt[4] = date_format(date_create(), 'Y-m-01');
                                        break;
                                    case 'lastmonth':
                                        $date = date_create();
                                        $date = date_add($date, date_interval_create_from_date_string('-1 month'));
                                        $prompt[4] = date_format($date, 'Y-m-01');
                                        break;
                                    case 'now':
                                        $prompt[4] = date_format(date_create(), 'Y-m-d H:i:s');
                                        break;
                                }
                            }
                        }
                        $prompts[] = $prompt;
                        break;
                    case 'T':
                        $topNotes[] = $rpt[$keys[$i]];
                        break;
                    case 'B':
                        $bottomNotes[] = $rpt[$keys[$i]];
                        break;
                }


            }
            $tab = str_replace(' ', '-', $name);
            if (count($topNotes) > 0)
                $reportTopNotes[$key] = $topNotes;
            if (count($bottomNotes) > 0)
                $reportBottomNotes[$key] = $bottomNotes;
            if (count($prompts) > 0) {
                $reportPrompts[$key] = $prompts;
                $onclick = "showPrompts('$key', '$prefix', '$fileName', '$type', '$template');";
            } else {
                $onclick = "noPrompts('$key', '$prefix', '$fileName', '$type', '$template');";
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
    echo " <script type='text/javascript'>\n";
    echo "    var reportTopNotes = " . json_encode($reportTopNotes) . ";\n";
    echo "    var reportBottomNotes = " . json_encode($reportBottomNotes) . ";\n";
    if (count($reportPrompts) > 0) {
        echo "    var reportPrompts = " . json_encode($reportPrompts) . ";\n";
    }
    echo " </script>\n";
    ?>
    <div class='tab-content ms-2' id='gen-report-content'>
        <div class='container-fluid' id='report-prompt-div'></div>
        <div class='container-fluid' id='report-content-div'></div>
    </div>
    <?php
}
?>
    <div id='result_message' class='mt-4 p-2'></div>
    <pre id='test'></pre>
</div>
<?php
page_foot($page);
