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
     aria-hidden='true' style='--bs-modal-width: 98%;'>
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
                        <div class='col-sm-12' id='candidatedH1Div'>
                            <H1 class='h3'>
                                <b>Potential Matches for: <span id='candidatesName'>Name</span></b>
                            </H1>
                        </div>
                    </div>
                    <div class='row mt-3'>
                        <div class='col-sm-12 text-bg-secondary'>
                            Person being matched
                        </div>
                    </div>
                    <div class='row'>
                        <div class='col-sm-12' id='newpersonTable'></div>
                    </div>
                    <div class='row mt-1'>
                        <div class='col-sm-12 text-bg-secondary'>
                            Potential Matches
                        </div>
                    </div>
                    <div class='row mb-2'>
                        <div class='col-sm-12' id='candidateTable'></div>
                    </div>
                </div>
                <div class='container-fluid' id="editMatch" hidden1>
                    <div class="row mt-4">
                        <div class="col-sm-12">
                            <H1 class='h3'>
                                <b>Editing: <span id='editMatchTitle'>A and B</span></b>
                            </H1>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-sm-1 border border-dark ps-1 pe-1">Field</div>
                        <div class="col-sm-3 border border-dark">Matched Person</div>
                        <div class="col-sm-5 border border-dark">New/Edited Value</div>
                        <div class="col-sm-3 border border-dark">Match Candidate</div>
                    </div>
                    <div class="row">
                        <div class="col-sm-1 border border-dark ps-1 pe-1">ID</div>
                        <div class="col-sm-3 border border-dark" id="matchID"></div>
                        <div class="col-sm-5 border border-dark"></div>
                        <div class="col-sm-3 border border-dark" id="newID"></div>
                    </div>
                    <div class='row'>
                        <div class='col-sm-1 border border-dark ps-1 pe-1'>Full Name</div>
                        <div class='col-sm-3 border border-dark' id='matchName'></div>
                        <div class='col-sm-5 border border-dark ps-1 pe-1'>
                            <input type='text' id='firstName' name='firstName' maxlength='32' size='23' placeholder='First Name'/>
                            <input type='text' id='middleName' name='middleName' maxlength='32' size='11' placeholder='Middle'/>
                            <input type='text' id='lastName' name='lastName' maxlength='32' size='23' placeholder='Last Name'/>
                            <input type='text' id='suffix' name='suffix' maxlength='4' size='5' placeholder='Sfx'/>
                        </div>
                        <div class='col-sm-3 border border-dark' id='newName'></div>
                    </div>
                    <div class='row'>
                        <div class='col-sm-1 border border-dark ps-1 pe-1'>Legal Name</div>
                        <div class='col-sm-3 border border-dark' id='matchLegal'></div>
                        <div class='col-sm-5 border border-dark ps-1 pe-1'>
                            <input type="text" id="legalName" name="legalName" maxlength="128" size="68" placeholder="Legal Name"/>
                        </div>
                        <div class='col-sm-3 border border-dark' id='newLegal'></div>
                    </div>
                    <div class='row'>
                        <div class='col-sm-1 border border-dark ps-1 pe-1'>Pronouns</div>
                        <div class='col-sm-3 border border-dark' id='matchPronouns'></div>
                        <div class='col-sm-5 border border-dark ps-1 pe-1'>
                            <input type='text' id='pronouns' name='pronouns' maxlength='64' size='64' placeholder='Pronouns'/>
                        </div>
                        <div class='col-sm-3 border border-dark' id='newPronouns'></div>
                    </div>
                    <div class='row'>
                        <div class='col-sm-1 border border-dark ps-1 pe-1'>Badge Name</div>
                        <div class='col-sm-3 border border-dark' id='matchBadge'></div>
                        <div class='col-sm-5 border border-dark ps-1 pe-1'>
                            <input type='text' id='badgeName' name='badgeName' maxlength='32' size='32' placeholder='Defaults to First Last'/>
                        </div>
                        <div class='col-sm-3 border border-dark' id='newBadge'></div>
                    </div>
                    <div class='row'>
                        <div class='col-sm-1 border border-dark ps-1 pe-1'>Address</div>
                        <div class='col-sm-3 border border-dark' id='matchAddress'></div>
                        <div class='col-sm-5 border border-dark ps-1 pe-1'>
                            <input type='text' id='address' name='address' maxlength='64' size='64' placeholder='Address'/>
                            <input type='text' id='addr2' name='addr2' maxlength='64' size='64' placeholder='Address Line 2 or Company'/>
                            <input type='text' id='city' name='city' maxlength='32' size='32' placeholder='City'/>
                            <input type='text' id='state' name='state' maxlength='16' size='16' placeholder='State'/>
                            <input type='text' id='zip' name='zip' maxlength='10' size='10' placeholder='Postal Code'/>
                        </div>
                        <div class='col-sm-3 border border-dark' id='newAddress'></div>
                    </div>
                    <div class='row'>
                        <div class='col-sm-1 border border-dark ps-1 pe-1'>Email Addr</div>
                        <div class='col-sm-3 border border-dark' id='matchEmail'></div>
                        <div class='col-sm-5 border border-dark ps-1 pe-1'>
                            <input type='text' id='emailAddr' name='emailAddr' maxlength='254' size='68' placeholder='Email Address'/>
                        </div>
                        <div class='col-sm-3 border border-dark' id='newEmail'></div>
                    </div>
                    <div class='row'>
                        <div class='col-sm-1 border border-dark ps-1 pe-1'>Phone</div>
                        <div class='col-sm-3 border border-dark' id='matchPhone'></div>
                        <div class='col-sm-5 border border-dark ps-1 pe-1'>
                            <input type='text' id='phone' name='phone' maxlength='15' size='15' placeholder='Phone'/>
                        </div>
                        <div class='col-sm-3 border border-dark' id='newPhone'></div>
                    </div>
                    <div class='row'>
                        <div class='col-sm-1 border border-dark ps-1 pe-1'>Policies</div>
                        <div class='col-sm-3 border border-dark' id='matchPolicies'></div>
                        <div class='col-sm-5 border border-dark ps-1 pe-1' id="policiesDiv"></div>
                        <div class='col-sm-3 border border-dark' id='newPhone'></div>
                    </div>
                    <div class='row'>
                        <div class='col-sm-1 border border-dark ps-1 pe-1'>Flags</div>
                        <div class='col-sm-3 border border-dark' id='matchFlags'></div>
                        <div class='col-sm-5 border border-dark ps-1 pe-1'>
                            <label for="active">Active: </label>
                            <select name="active" id="active">
                                <option value="Y">Y</option>
                                <option value="N">N</option>
                            </select>
                            <label for='banned'>Banned: </label>
                            <select name='banned' id='active'>
                                <option value='Y'>Y</option>
                                <option value='N'>N</option>
                            </select>
                        </div>
                        <div class='col-sm-3 border border-dark' id='newFlags'></div>
                    </div>
                </div>
            </div>
            <div class='modal-footer'>
                <button class='btn btn-sm btn-secondary' data-bs-dismiss='modal'>Cancel</button>
                <button class='btn btn-sm btn-primary' id='updateExisting' onClick='unmatchedPeople.matchExisting()' disabled>Update Existing
                    Person</button>
                <button class='btn btn-sm btn-primary' id='createNew' onClick='unmatchedPeople.createNew()' disabled>Create New Person</button>
            </div>
            <div id='result_message_candidate' class='mt-4 p-2'></div>
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
