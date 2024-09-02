<?php
global $db_ini;

// people - administrative control of
//      matching newperid's to existing perid's or creating a new perid if no patch
//      managing associations (managed by)
//      creating new perinfo records (perid's)
//      editing existing perinfo records including:
//          viewing/setting nodes (open and restrictred)
//          setting the ban flag
//
// broken into tabs:
//      Match Newperson to Perinfo (showing number of unmatched)
//      Find/Edit existing Perinfo Records (People)
//      Add New Person

require_once 'lib/base.php';
//initialize google session
$need_login = google_init('page');

$page = 'people';
if(!$need_login or !checkAuth($need_login['sub'], $page)) {
    bounce_page('index.php');
}

$cdn = getTabulatorIncludes();
page_init($page,
    /* css */ array($cdn['tabcss'],
                    // $cdn['tabbs5'],
                    'css/base.css',
                    ),
    /* js  */ array(//$cdn['luxon'],
                    $cdn['tabjs'],
//                    'js/tinymce/tinymce.min.js',
                    'js/people.js',
                    'js/people_unmatched.js',
              ),
                    $need_login);

$con_conf = get_conf('con');
$controll = get_conf('controll');
$conid = $con_conf['id'];
$debug = get_conf('debug');

if (array_key_exists('controll_people', $debug))
    $debug_people=$debug['controll_people'];
else
    $debug_people = 0;

// first the passed in parameters and the the modals
?>
<div id='parameters' <?php if (!($debug_people & 4)) echo 'hidden'; ?>>
    <div id="debug"><?php echo $debug_people; ?></div>
    <div id="conid"><?php echo $conid; ?></div>
</div>
<?php 
    bs_tinymceModal();
?>
<!-- Match Candidates Modal -->
<div id='match-candidates' class='modal modal-xl fade' tabindex='-1' aria-labelledby='Display Candidates for Match'
     aria-hidden='true' style='--bs-modal-width: 80%;'>
    <div class='modal-dialog'>
        <div class='modal-content'>
            <div class='modal-header bg-primary text-bg-primary'>
                <div class='modal-title'>
                    <strong id='candidatesTitle'>Potential matches for <span id="candidatesTitleName">Name</span></strong>
                </div>
                <button type='button' class='btn-close' data-bs-dismiss='modal' aria-label='Close'></button>
            </div>
            <div class='modal-body' style='padding: 4px; background-color: lightcyan;'>
                <div class='container-fluid'>
                    <div class='row mt-2'>
                        <div class='col-sm-12' id='candidatedH1Div'><H1 class='h3'>
                                <b>Potential Matches for: <span id='candidatesName'>Name</span></b>
                            </H1></div>
                        </div>
                    <div class='row mt-3'>
                        <div class='col-sm-12 text-bg-secondary'>
                            Edit Match Form Goes Here
                        </div>
                    </div>
                    <div class='row'>
                        <div class='col-sm-12' id='candidateTable'></div>
                    </div>
                </div>
            </div>
            <div class='modal-footer'>
                <button class='btn btn-sm btn-secondary' data-bs-dismiss='modal'>Cancel</button>
                <button class='btn btn-sm btn-primary' id='updateExisting' onClick='unmatchedPeople.matchExisting()'>Update Existing Person</button>
                <button class='btn btn-sm btn-primary' id='createNew' onClick='unmatchedPeople.createNew()'>Update Existing Person</button>
            </div>
            <div id='result_message_candidate' class='mt-4 p-2'></div>
        </div>
    </div>
</div>
<!-- Merge modals are an example and a placeholder for what me might need  -->
<div id='merge-lookup' class='modal modal-xl fade' tabindex='-1' aria-labelledby='Look up Merge Person' aria-hidden='true' style='--bs-modal-width: 80%;'>
    <div class='modal-dialog'>
        <div class='modal-content'>
            <div class='modal-header bg-primary text-bg-primary'>
                <div class='modal-title'>
                    <strong id='mergeTitle'>Lookup Person for Merge</strong>
                </div>
                <button type='button' class='btn-close' data-bs-dismiss='modal' aria-label='Close'></button>
            </div>
            <div class='modal-body' style='padding: 4px; background-color: lightcyan;'>
                <div class='container-fluid'>
                    <form id='merge-search' action='javascript:void(0)'>
                        <div class='row p-1'>
                            <div class='col-sm-3 p-0'>
                                <label for='merge_name_search' id='mergeName'>Merge Name:</label>
                            </div>
                            <div class='col-sm-9 p-0'>
                                <input class='form-control-sm' type='text' name='namesearch' id='merge_name_search' size='64'
                                       placeholder='Name/Portion of Name, Person (Registration) ID'/>
                            </div>
                            <div class='row mt-3'>
                                <div class='col-sm-12 text-bg-secondary'>
                                    Search Results
                                </div>
                            </div>
                            <div class='row'>
                                <div class='col-sm-12' id='merge_search_results'>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            <div class='modal-footer'>
                <button class='btn btn-sm btn-secondary' data-bs-dismiss='modal'>Cancel</button>
                <button class='btn btn-sm btn-primary' id='mergeSearch' onClick='merge_find()'>Find Person</button>
            </div>
            <div id='result_message_merge' class='mt-4 p-2'></div>
        </div>
    </div>
</div>
<ul class='nav nav-tabs mb-3' id='people-tab' role='tablist'>
    <li class='nav-item' role='presentation'>
        <button class='nav-link active' id='unmatched-tab' data-bs-toggle='pill' data-bs-target='#unmatched-pane' type='button'
                role='tab' aria-controls='nav-unmatched' aria-selected='true' onclick="settab('unmatched-pane');">Unmatched New People
        </button>
    </li>
    <li class='nav-item' role='presentation'>
        <button class='nav-link' id='findedit-tab' data-bs-toggle='pill' data-bs-target='#findedit-pane' type='button' role='tab'
                aria-controls='nav-findedit' aria-selected='false' onclick="settab('findedit-pane');">Find / Edit Person
        </button>
    </li>
    <li class='nav-item' role='presentation'>
        <button class='nav-link' id='add-tab' data-bs-toggle='pill' data-bs-target='#add-pane' type='button' role='tab'
                aria-controls='nav-add' aria-selected='false' onclick="settab('add-pane');">Add New Person
        </button>
    </li>
</ul>
<div class='tab-content ms-2' id='peole-content'>
    <div class='tab-pane fade show active' id='unmatched-pane' role='tabpanel' aria-labelledby='unmatched-tab' tabindex='0'>
        <div class='container-fluid'>
            <div class="row mt-2">
                <div class="col-sm-12" id="unmatchedH1Div"><H1 class="h3"><b>Unmatched New People: <span id="unmatchedCount">0</span></b></H1></div>
            </div>
            <div class="row mt-2">
                <div class="col-sm-12" id="unmatchedTable" name="unmatchedTable"></div>
            </div>
        </div>
    </div>
    <div class='tab-pane fade' id='findedit-pane' role='tabpanel' aria-labelledby='findedit-tab' tabindex='0'>
        <div class='container-fluid'>
            <div class='row mt-2'>
                <div class='col-sm-12' id='findeditH1Div'><H1 class='h3'><b>Find / Edit People</b></H1></div>
            </div>
        </div>
    </div>
    <div class='tab-pane fade' id='add-pane' role='tabpanel' aria-labelledby='add-tab' tabindex='0'>
        <div class='container-fluid'>
            <div class='row mt-2'>
                <div class='col-sm-12' id='addH1Div'><H1 class='h3'><b>Add Person</b></H1></div>
            </div>
        </div>
    </div>
    <div id='result_message' class='mt-4 p-2'></div>
</div>

<pre id='test'>
</pre>

<?php
page_foot($page);
?>
