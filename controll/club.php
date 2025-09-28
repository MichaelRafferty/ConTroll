<?php
require_once "lib/base.php";
//initialize google session
$need_login = google_init("page");

$page = "club";
if(!$need_login or !checkAuth($need_login['sub'], $page)) {
    bounce_page("index.php");
}
if (array_key_exists('user_id', $_SESSION)) {
    $user_id = $_SESSION['user_id'];
} else {
    bounce_page('index.php');
    return;
}

$con = get_conf("con");
$conid = $con['id'];
$club_conf = get_conf('club');

$scriptName = $_SERVER['SCRIPT_NAME'];
if (array_key_exists('tab', $_REQUEST)) {
    $initialTab = $_REQUEST['tab'];
} else {
    $initialTab = 'List';
}

$cdn = getTabulatorIncludes();
page_init($page,
    /* css */ array($cdn['tabcss'], $cdn['tabbs5'], 'css/base.css', 'css/oldbase.css', 'css/club.css'),
    /* js  */ array($cdn['tabjs'],
                    'js/club.js'
                   ),
              $need_login);

$config_vars['debug'] = getConfValue('debug', 'controll_club', 0);
$config_vars['conid'] = $conid;
if (array_key_exists('msg', $_REQUEST)) {
    $config_vars['msg'] = $_REQUEST['msg'];
}

?>
    <script type='text/javascript'>
        var config = <?php echo json_encode($config_vars); ?>;
    </script>
    <div id='user-lookup' class='modal modal-xl fade' tabindex='-1' aria-labelledby='Lookup Person to Add as Member' aria-hidden='true' style='--bs-modal-width: 80%;'>
        <div class='modal-dialog'>
            <div class='modal-content'>
                <div class='modal-header bg-primary text-bg-primary'>
                    <div class='modal-title'>
                        <strong id='addTitle'>Lookup Person to Add as User</strong>
                    </div>
                    <button type='button' class='btn-close' data-bs-dismiss='modal' aria-label='Close'></button>
                </div>
                <div class='modal-body' style='padding: 4px; background-color: lightcyan;'>
                    <div class='container-fluid'>
                        <form id='add-search' action='javascript:void(0)'>
                            <div class='row p-1'>
                                <div class='col-sm-3 p-0'>
                                    <label for='add_name_search' id='addName'>Name:</label>
                                </div>
                                <div class='col-sm-9 p-0'>
                                    <input class='form-control-sm' type='text' name='namesearch' id='add_name_search' size='64'
                                           placeholder='Name/Portion of Name, Person (Badge) ID'/>
                                </div>
                                <div class='row mt-3'>
                                    <div class='col-sm-12 text-bg-secondary'>
                                        Search Results
                                    </div>
                                </div>
                                <div class='row'>
                                    <div class='col-sm-12' id='add_search_results'>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
                <div class='modal-footer'>
                    <button class='btn btn-sm btn-secondary' data-bs-dismiss='modal'>Cancel</button>
                    <button class='btn btn-sm btn-primary' id='addSearch' onClick='add_find()'>Find Person</button>
                </div>
                <div id='result_message_user' class='mt-4 p-2'></div>
            </div>
        </div>
    </div>
    <div class="container-fluid" id='main'>
        <ul class='nav nav-tabs mb-3' id='club-tab' role='tablist'>
            <li class='nav-item' role='presentation'>
                <button class='nav-link active' id='list-tab' data-bs-toggle='pill' data-bs-target='#list-pane' type='button' role='tab'
                        aria-controls='nav-overview' aria-selected="true" onclick="settab('list-pane');">List
                </button>
            </li>
            <li class='nav-item' role='presentation'>
                <button class='nav-link' id='configuration-tab' data-bs-toggle='pill' data-bs-target='#configuration-pane' type='button' role='tab'
                        aria-controls='nav-configuration' aria-selected='false' onclick="settab('configuration-pane');">Club Configuration
                </button>
            </li>

        </ul>
        <div class="tab-content ms-2" id="club-content">
            <div class='tab-pane fade show active' id='list-pane'>
                <div class='container-fluid'>
                    <div class='row'>
                        <div class='col-sm-12'>
                            <h3 style='text-align: center;'><strong>Club Member List</strong></h3>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-sm-auto" id="clubListTableDiv"></div>
                    </div>
                    <div class='row mt-2 mb-3 '>
                        <div class='col-sm-auto'>
                            <button id='clubList-undo' type='button' class='btn btn-secondary btn-sm' onclick='undoClubList(); return false;' disabled>Undo</button>
                            <button id='clubList-redo' type='button' class='btn btn-secondary btn-sm' onclick='redoClubList(); return false;' disabled>Redo</button>
                            <button id='clubList-save' type='button' class='btn btn-primary btn-sm' onclick='saveClubList(); return false;' disabled>Save Changes</button>
                        </div>
                    </div>
                </div>
            </div>
            <div class='tab-pane fade' id='configuration-pane' role='tabpanel' aria-labelledby='configuration-tab' tabindex='0'>
                <div class='container-fluid'>
                    <div class='row'>
                        <div class='col-sm-12'>
                            <h3 style='text-align: center;'><strong>Club Types</strong></h3>
                        </div>
                    </div>
                   <div class="row">
                        <div class="col-sm-auto" id="clubTypesTableDiv"></div>
                    </div>
                    <div class='row mt-2 mb-3 '>
                        <div class='col-sm-auto'>
                            <button id='clubTypes-undo' type='button' class='btn btn-secondary btn-sm' onclick='undoClubTypes(); return false;' disabled>Undo</button>
                            <button id='clubTypes-redo' type='button' class='btn btn-secondary btn-sm' onclick='redoClubTypes(); return false;' disabled>Redo</button>
                            <button id='clubTypes-save' type='button' class='btn btn-primary btn-sm' onclick='saveClubTypes(); return false;' disabled>Save Changes</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
<pre id='test'></pre>
<?php
page_foot($page);
