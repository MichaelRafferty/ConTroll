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
require_once '../lib/policies.php';
require_once '../lib/profile.php';
require_once '../lib/interests.php';
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
                    'js/people_add.js',
                    'js/people_find.js',
              ),
                    $need_login);

$con_conf = get_conf('con');
$controll = get_conf('controll');
$conid = $con_conf['id'];
$debug = get_conf('debug');
$usps = get_conf('usps');
$policies = getPolicies();
$interests = getInterests();

if (array_key_exists('controll_people', $debug))
    $debug_people=$debug['controll_people'];
else
    $debug_people = 0;

$useUSPS = false;
if (($usps != null) && array_key_exists('secret', $usps) && ($usps['secret'] != ''))
    $useUSPS = true;

// first the passed in parameters and the the modals
$config_vars['debug'] = $debug_people;
$config_vars['conid'] = $conid;
$config_vars['useUSPS'] = $useUSPS;
$config_vars['policies'] = $policies;
$config_vars['inrerests'] = $interests;
?>
<script type='text/javascript'>
    var config = <?php echo json_encode($config_vars); ?>;
    var policies = <?php echo json_encode($policies, JSON_FORCE_OBJECT | JSON_HEX_QUOT); ?>
</script>
<?php 
    //bs_tinymceModal();
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
                    <div class='row mt-1'>
                        <div class='col-sm-auto text-bg-secondary'>
                            Additional Query:
                        </div>
                        <div class='col-sm-auto text-bg-secondary'>
                            <input type='text' size='80' id='matchAdditionalQuery' name='matchAdditionalQuery' , placeholder='Name/PID/email'/>
                        </div>
                        <div class='col-sm-auto text-bg-secondary'>
                            <button class='btn btn-sm btn-light pt-0 pb-0 mt-0 mb-0'
                                    type='button' onclick="unmatchedPeople.additionalQuery()">Search</button>
                        </div>
                    </div>
                    <div class='row mb-2'>
                        <div class='col-sm-12' id='additionalTable'></div>
                    </div>
                </div>
                <div class='container-fluid' id="editMatch">
                    <div class="row mt-4">
                        <div class="col-sm-12">
                            <h1 class='h3'>
                                <b>Editing: <span id='editMatchTitle'>A and B</span></b>
                            </h1>
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
                        <div class='col-sm-3 border border-dark pe-0'>
                            <div class='container-fluid'>
                                <div class='row justify-content-between'>
                                    <div class='col-sm-auto ms-0 me-0 ps-0 pe-0' id='matchID'></div>
                                    <div class='col-sm-auto ms-0 me-0 ps-0 pe-0'>
                                        <button class='btn btn-sm btn-light pt-0 pb-0 mt-0 mb-0 justify-content-end'
                                                type='button' onclick="unmatchedPeople.copy('matchAll')">
                                            ALL&gt;&gt;
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-5 border border-dark"></div>
                        <div class='col-sm-3 border border-dark ps-0'>
                            <div class='container-fluid'>
                                <div class='row'>
                                    <div class='col-sm-auto ms-0 me-0 ps-0 pe-0'>
                                        <button class='btn btn-sm btn-light pt-0 pb-0 mt-0 mb-0 me-2 justify-content-end'
                                                type='button' onclick="unmatchedPeople.copy('newAll')">
                                            &lt;&lt;ALL
                                        </button>
                                    </div>
                                    <div class='col-sm-auto ms-0 me-0 ps-0 pe-0' id='newID'></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class='row'>
                        <div class='col-sm-1 border border-dark ps-1 pe-1'>Full Name</div>
                        <div class='col-sm-3 border border-dark pe-0'>
                            <div class="container-fluid">
                                <div class="row justify-content-between">
                                    <div class="col-sm-auto ms-0 me-0 ps-0 pe-0" id="matchName"></div>
                                    <div class="col-sm-auto ms-0 me-0 ps-0 pe-0">
                                        <button class='btn btn-sm btn-light pt-0 pb-0 mt-0 mb-0 justify-content-end'
                                                type='button' onclick="unmatchedPeople.copy('matchName')">
                                            &gt;&gt;
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class='col-sm-5 border border-dark ps-1 pe-1'>
                            <input type='text' id='firstName' name='firstName' maxlength='32' size='23' placeholder='First Name'/>
                            <input type='text' id='middleName' name='middleName' maxlength='32' size='11' placeholder='Middle'/>
                            <input type='text' id='lastName' name='lastName' maxlength='32' size='23' placeholder='Last Name'/>
                            <input type='text' id='suffix' name='suffix' maxlength='4' size='5' placeholder='Sfx'/>
                        </div>
                        <div class='col-sm-3 border border-dark ps-0'>
                            <div class='container-fluid'>
                                <div class='row'>
                                    <div class='col-sm-auto ms-0 me-0 ps-0 pe-0'>
                                        <button class='btn btn-sm btn-light pt-0 pb-0 mt-0 mb-0 me-2 justify-content-end'
                                                type='button' onclick="unmatchedPeople.copy('newName')">
                                            &lt;&lt;
                                        </button>
                                    </div>
                                    <div class='col-sm-auto ms-0 me-0 ps-0 pe-0' id='newName'></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class='row'>
                        <div class='col-sm-1 border border-dark ps-1 pe-1'>Legal Name</div>
                        <div class='col-sm-3 border border-dark pe-0'>
                            <div class='container-fluid'>
                                <div class='row justify-content-between'>
                                    <div class='col-sm-auto ms-0 me-0 ps-0 pe-0' id='matchLegal'></div>
                                    <div class='col-sm-auto ms-0 me-0 ps-0 pe-0'>
                                        <button class='btn btn-sm btn-light pt-0 pb-0 mt-0 mb-0 justify-content-end'
                                                type='button' onclick="unmatchedPeople.copy('matchLegal')">
                                            &gt;&gt;
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class='col-sm-5 border border-dark ps-1 pe-1'>
                            <input type="text" id="legalName" name="legalName" maxlength="128" size="68" placeholder="Legal Name"/>
                        </div>
                        <div class='col-sm-3 border border-dark ps-0'>
                            <div class='container-fluid'>
                                <div class='row'>
                                    <div class='col-sm-auto ms-0 me-0 ps-0 pe-0'>
                                        <button class='btn btn-sm btn-light pt-0 pb-0 mt-0 mb-0 me-2 justify-content-end'
                                                type='button' onclick="unmatchedPeople.copy('newLegal')">
                                            &lt;&lt;
                                        </button>
                                    </div>
                                    <div class='col-sm-auto ms-0 me-0 ps-0 pe-0' id='newLegal'></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class='row'>
                        <div class='col-sm-1 border border-dark ps-1 pe-1'>Pronouns</div>
                        <div class='col-sm-3 border border-dark pe-0'>
                            <div class='container-fluid'>
                                <div class='row justify-content-between'>
                                    <div class='col-sm-auto ms-0 me-0 ps-0 pe-0' id='matchPronouns'></div>
                                    <div class='col-sm-auto ms-0 me-0 ps-0 pe-0'>
                                        <button class='btn btn-sm btn-light pt-0 pb-0 mt-0 mb-0 justify-content-end'
                                                type='button' onclick="unmatchedPeople.copy('matchPronouns')">
                                            &gt;&gt;
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class='col-sm-5 border border-dark ps-1 pe-1'>
                            <input type='text' id='pronouns' name='pronouns' maxlength='64' size='64' placeholder='Pronouns'/>
                        </div>
                        <div class='col-sm-3 border border-dark ps-0'>
                            <div class='container-fluid'>
                                <div class='row'>
                                    <div class='col-sm-auto ms-0 me-0 ps-0 pe-0'>
                                        <button class='btn btn-sm btn-light pt-0 pb-0 mt-0 mb-0 me-2 justify-content-end'
                                                type='button' onclick="unmatchedPeople.copy('newPronouns')">
                                            &lt;&lt;
                                        </button>
                                    </div>
                                    <div class='col-sm-auto ms-0 me-0 ps-0 pe-0' id='newPronouns'></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class='row'>
                        <div class='col-sm-1 border border-dark ps-1 pe-1'>Badge Name</div>
                        <div class='col-sm-3 border border-dark pe-0'>
                            <div class='container-fluid'>
                                <div class='row justify-content-between'>
                                    <div class='col-sm-auto ms-0 me-0 ps-0 pe-0' id='matchBadge'></div>
                                    <div class='col-sm-auto ms-0 me-0 ps-0 pe-0'>
                                        <button class='btn btn-sm btn-light pt-0 pb-0 mt-0 mb-0 justify-content-end'
                                                type='button' onclick="unmatchedPeople.copy('matchBadge')">
                                            &gt;&gt;
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class='col-sm-5 border border-dark ps-1 pe-1'>
                            <input type='text' id='badgeName' name='badgeName' maxlength='32' size='32' placeholder='Defaults to First Last'/>
                        </div>
                        <div class='col-sm-3 border border-dark ps-0'>
                            <div class='container-fluid'>
                                <div class='row'>
                                    <div class='col-sm-auto ms-0 me-0 ps-0 pe-0'>
                                        <button class='btn btn-sm btn-light pt-0 pb-0 mt-0 mb-0 me-2 justify-content-end'
                                                type='button' onclick="unmatchedPeople.copy('newBadge')">
                                            &lt;&lt;
                                        </button>
                                    </div>
                                    <div class='col-sm-auto ms-0 me-0 ps-0 pe-0' id='newBadge'></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class='row'>
                        <div class='col-sm-1 border border-dark ps-1 pe-1'>Address</div>
                        <div class='col-sm-3 border border-dark pe-0'>
                            <div class='container-fluid'>
                                <div class='row justify-content-between'>
                                    <div class='col-sm-auto ms-0 me-0 ps-0 pe-0' id='matchAddress'></div>
                                    <div class='col-sm-auto ms-0 me-0 ps-0 pe-0'>
                                        <button class='btn btn-sm btn-light pt-0 pb-0 mt-0 mb-0 justify-content-end'
                                                type='button' onclick="unmatchedPeople.copy('matchAddress')">
                                            &gt;&gt;
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class='col-sm-5 border border-dark ps-1 pe-1'>
                            <input type='text' id='address' name='address' maxlength='64' size='64' placeholder='Address'/>
                            <input type='text' id='addr2' name='addr2' maxlength='64' size='64' placeholder='Address Line 2 or Company'/>
                            <input type='text' id='city' name='city' maxlength='32' size='32' placeholder='City'/>
                            <input type='text' id='state' name='state' maxlength='16' size='16' placeholder='State'/>
                            <input type='text' id='zip' name='zip' maxlength='10' size='10' placeholder='Postal Code'/>

                            <label for='country' class='form-label-sm'>
                                <span class='text-dark' style='font-size: 10pt;'>Country</span>
                            </label><br/>
                            <select name='country' id='country'>
                                <?php
                                    $fh = fopen(__DIR__ . '/../lib/countryCodes.csv', 'r');
                                    while (($data = fgetcsv($fh, 1000, ',', '"')) != false) {
                                        echo '<option value="' . escape_quotes($data[1]) . '">' . $data[0] . '</option>';
                                    }
                                    fclose($fh);
                                ?>
                            </select>
                        </div>
                        <div class='col-sm-3 border border-dark ps-0'>
                            <div class='container-fluid'>
                                <div class='row'>
                                    <div class='col-sm-auto ms-0 me-0 ps-0 pe-0'>
                                        <button class='btn btn-sm btn-light pt-0 pb-0 mt-0 mb-0 me-2 justify-content-end'
                                                type='button' onclick="unmatchedPeople.copy('newAddress')">
                                            &lt;&lt;
                                        </button>
                                    </div>
                                    <div class='col-sm-auto ms-0 me-0 ps-0 pe-0' id='newAddress'></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class='row'>
                        <div class='col-sm-1 border border-dark ps-1 pe-1'>Email Addr</div>
                        <div class='col-sm-3 border border-dark pe-0'>
                            <div class='container-fluid'>
                                <div class='row justify-content-between'>
                                    <div class='col-sm-auto ms-0 me-0 ps-0 pe-0' id='matchEmail'></div>
                                    <div class='col-sm-auto ms-0 me-0 ps-0 pe-0'>
                                        <button class='btn btn-sm btn-light pt-0 pb-0 mt-0 mb-0 justify-content-end'
                                                type='button' onclick="unmatchedPeople.copy('matchEmail')">
                                            &gt;&gt;
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class='col-sm-5 border border-dark ps-1 pe-1'>
                            <input type='text' id='emailAddr' name='emailAddr' maxlength='254' size='68' placeholder='Email Address'/>
                        </div>
                        <div class='col-sm-3 border border-dark ps-0'>
                            <div class='container-fluid'>
                                <div class='row'>
                                    <div class='col-sm-auto ms-0 me-0 ps-0 pe-0'>
                                        <button class='btn btn-sm btn-light pt-0 pb-0 mt-0 mb-0 me-2 justify-content-end'
                                                type='button' onclick="unmatchedPeople.copy('newEmail')">
                                            &lt;&lt;
                                        </button>
                                    </div>
                                    <div class='col-sm-auto ms-0 me-0 ps-0 pe-0' id='newEmail'></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class='row'>
                        <div class='col-sm-1 border border-dark ps-1 pe-1'>Phone</div>
                        <div class='col-sm-3 border border-dark pe-0'>
                            <div class='container-fluid'>
                                <div class='row justify-content-between'>
                                    <div class='col-sm-auto ms-0 me-0 ps-0 pe-0' id='matchPhone'></div>
                                    <div class='col-sm-auto ms-0 me-0 ps-0 pe-0'>
                                        <button class='btn btn-sm btn-light pt-0 pb-0 mt-0 mb-0 justify-content-end'
                                                type='button' onclick="unmatchedPeople.copy('matchPhone')">
                                            &gt;&gt;
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class='col-sm-5 border border-dark ps-1 pe-1'>
                            <input type='text' id='phone' name='phone' maxlength='15' size='15' placeholder='Phone'/>
                        </div>
                        <div class='col-sm-3 border border-dark ps-0'>
                            <div class='container-fluid'>
                                <div class='row'>
                                    <div class='col-sm-auto ms-0 me-0 ps-0 pe-0'>
                                        <button class='btn btn-sm btn-light pt-0 pb-0 mt-0 mb-0 me-2 justify-content-end'
                                                type='button' onclick="unmatchedPeople.copy('newPhone')">
                                            &lt;&lt;
                                        </button>
                                    </div>
                                    <div class='col-sm-auto ms-0 me-0 ps-0 pe-0' id='newPhone'></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class='row'>
                        <div class='col-sm-1 border border-dark ps-1 pe-1'>Policies</div>
                        <div class='col-sm-3 border border-dark pe-0'>
                            <div class='container-fluid'>
                                <div class='row justify-content-between'>
                                    <div class='col-sm-auto ms-0 me-0 ps-0 pe-0' id='matchPolicies'></div>
                                    <div class='col-sm-auto ms-0 me-0 ps-0 pe-0'>
                                        <button class='btn btn-sm btn-light pt-0 pb-0 mt-0 mb-0 justify-content-end'
                                                type='button' onclick="unmatchedPeople.copy('matchPolicies')">
                                            &gt;&gt;
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class='col-sm-5 border border-dark ps-1 pe-1' id="policiesDiv">
                            <?php
                                drawPoliciesCell($policies);
                            ?>
                        </div>
                        <div class='col-sm-3 border border-dark ps-0'>
                            <div class='container-fluid'>
                                <div class='row'>
                                    <div class='col-sm-auto ms-0 me-0 ps-0 pe-0'>
                                        <button class='btn btn-sm btn-light pt-0 pb-0 mt-0 mb-0 me-2 justify-content-end'
                                                type='button' onclick="unmatchedPeople.copy('newPolicies')">
                                            &lt;&lt;
                                        </button>
                                    </div>
                                    <div class='col-sm-auto ms-0 me-0 ps-0 pe-0' id='newPolicies'></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class='row'>
                        <div class='col-sm-1 border border-dark ps-1 pe-1'>Flags</div>
                        <div class='col-sm-3 border border-dark pe-0'>
                            <div class='container-fluid'>
                                <div class='row justify-content-between'>
                                    <div class='col-sm-auto ms-0 me-0 ps-0 pe-0' id='matchFlags'></div>
                                    <div class='col-sm-auto ms-0 me-0 ps-0 pe-0'>
                                        <button class='btn btn-sm btn-light pt-0 pb-0 mt-0 mb-0 justify-content-end'
                                                type='button' onclick="unmatchedPeople.copy('matchFlags')">
                                            &gt;&gt;
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class='col-sm-5 border border-dark ps-1 pe-1'>
                            <label for="active">Active: </label>
                            <select name="active" id="active">
                                <option value="Y">Y</option>
                                <option value="N">N</option>
                            </select>
                            <label for='banned'>Banned: </label>
                            <select name='banned' id='banned'>
                                <option value='Y'>Y</option>
                                <option value='N'>N</option>
                            </select>
                        </div>
                        <div class='col-sm-3 border border-dark ps-0'>
                            <div class='container-fluid'>
                                <div class='row'>
                                    <div class='col-sm-auto ms-0 me-0 ps-0 pe-0'>
                                        <button class='btn btn-sm btn-light pt-0 pb-0 mt-0 mb-0 me-2 justify-content-end'
                                                type='button' onclick="unmatchedPeople.copy('newFlags')">
                                            &lt;&lt;
                                        </button>
                                    </div>
                                    <div class='col-sm-auto ms-0 me-0 ps-0 pe-0' id='newFlags'></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class='row'>
                        <div class='col-sm-1 border border-dark ps-1 pe-1'>Manager</div>
                        <div class='col-sm-3 border border-dark pe-0'>
                            <div class='container-fluid'>
                                <div class='row justify-content-between'>
                                    <div class='col-sm-auto ms-0 me-0 ps-0 pe-0' id='matchManager'></div>
                                    <div class='col-sm-auto ms-0 me-0 ps-0 pe-0'>
                                        <button class='btn btn-sm btn-light pt-0 pb-0 mt-0 mb-0 justify-content-end'
                                                type='button' onclick="unmatchedPeople.copy('matchManager')">
                                            &gt;&gt;
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class='col-sm-5 border border-dark ps-1 pe-1' id='managerDiv'></div>
                        <div class='col-sm-3 border border-dark ps-0'>
                            <div class='container-fluid'>
                                <div class='row'>
                                    <div class='col-sm-auto ms-0 me-0 ps-0 pe-0'>
                                        <button class='btn btn-sm btn-light pt-0 pb-0 mt-0 mb-0 me-2 justify-content-end'
                                                type='button' onclick="unmatchedPeople.copy('newManager')">
                                            &lt;&lt;
                                        </button>
                                    </div>
                                    <div class='col-sm-auto ms-0 me-0 ps-0 pe-0' id='newManager'></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class='modal-footer'>
                <button class='btn btn-sm btn-secondary' type='button' data-bs-dismiss='modal'>Cancel</button>
                <button class='btn btn-sm btn-primary' type='button' id='updateExisting' onClick='unmatchedPeople.saveMatch("e")' disabled>Update Existing
                    Person</button>
                <button class='btn btn-sm btn-primary' type='button' id='createNew' onClick='unmatchedPeople.saveMatch("n")' disabled>Create New
                    Person</button>
                <button class='btn btn-sm btn-warning' type='button' id='deleteNew' onClick='unmatchedPeople.deletePerson()' disabled>Delete New
                    Person</button>
            </div>
            <div id='result_message_candidate' class='mt-4 p-2'></div>
        </div>
    </div>
</div>
<div id='edit-person' class='modal modal-xl fade' tabindex='-1' aria-labelledby='Edit Person'
     aria-hidden='true' style='--bs-modal-width: 98%;'>
    <div class='modal-dialog'>
        <div class='modal-content'>
            <div class='modal-header bg-primary text-bg-primary'>
                <div class='modal-title'>
                    <strong id='editTitle'>Editing <span id='editPersonName'>Name</span></strong>
                </div>
                <button type='button' class='btn-close' data-bs-dismiss='modal' aria-label='Close'></button>
            </div>
            <div class='modal-body' style='padding: 4px; background-color: lightcyan;'>
                <div class='container-fluid'>
                    <div class="row mt-2">
                        <div class="col-sm-12"><h2 class="size=h3">Profile/Policies</h2></div>
                    </div>
<?php
drawEditPersonBlock($conid, $useUSPS, $policies, 'find', true, true, '', array(), 200, true, 'f_');
drawInterestList($interests, true);
?>
                    <div class='row mt-3' id="managerHdr">
                        <div class='col-sm-auto'><h2 class='size=h3'>Manager (Disassociate manager and save before adding people managed by this person)
                            </h2></div>
                    </div>
                    <div class="row mt-2" id="managerRow">
                        <div class="col-sm-auto"><button class="btn btn-sm btn-warning" type="button" onclick="findPerson.disassociate();
">Disassociate</button></div>
                        <div class="col-sm-auto"><input type="number" class='no-spinners' inputmode='numeric' id="f_managerId" name="f_managerId"></div>
                        <div class='col-sm-auto'>
                            <button class='btn btn-sm btn-secondary' type='button' onclick='findPerson.findManager();'>Find New Manager</button>
                        </div>
                        <div class="col-sm-auto" id="f_managerName"></div>
                    </div>
                    <div class="row mt-2" id="managerLookupFind" hidden>
                        <div class="col-sm-auto"><label for="newManagerLookup">Lookup New Manager:</label></div>
                        <div class="col-sm-auto">
                            <input type="text" name='newManagerLookup' id='newManagerLookup' size="80"
                                  placeholder="Name/Portion of (Name, Address, Email, Badgename, Legal Name)">
                        </div>
                        <div class='col-sm-auto'>
                            <button class='btn btn-sm btn-primary' type='button' onclick='findPerson.lookupManager();'>Lookup</button>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-sm-12" id="managerTableDiv"></div>
                    </div>
                    <div class='row mt-3' id="managesHdr">
                        <div class='col-sm-auto'><h2 class='size=h3'>Manages (Unmange all and save before making this person managed by another person)
                            </h2></div>
                    </div>
                    <div class="row mt-1" id="managesRow"></div>
                    <div class='row mt-2' id='addManages'>
                        <div class='col-sm-auto'>
                            <button class='btn btn-sm btn-secondary' type='button' onclick='findPerson.addManages();'>Add to Managed List</button>
                        </div>
                        <div class='col-sm-auto'><input type='number' class='no-spinners' inputmode='numeric' id='f_managesId' name='f_managesId'></div>
                        <div class='col-sm-auto' id="managesName"></div>
                        <div class='col-sm-auto'>
                            <button class='btn btn-sm btn-secondary' type='button' onclick='findPerson.findManages();'>Find Person to Manage</button>
                        </div>
                    </div>
                    <div class='row mt-2' id='managesLookupFind' hidden>
                        <div class='col-sm-auto'><label for='newManagesLookup'>Lookup New Person to Manage:</label></div>
                        <div class='col-sm-auto'>
                            <input type='text' name='newManagesLookup' id='newManagesLookup' size='80'
                                   placeholder='Name/Portion of (Name, Address, Email, Badgename, Legal Name)'>
                        </div>
                        <div class='col-sm-auto'>
                            <button class='btn btn-sm btn-primary' type='button' onclick='findPerson.lookupManages();'>Lookup</button>
                        </div>
                    </div>
                    <div class='row'>
                        <div class='col-sm-12' id='managesTableDiv'></div>
                    </div>
                    <div class='row mt-3'>
                        <div class='col-sm-auto'><h2 class='size=h3'>Status and Notes</h2></div>
                    </div>
                    <div class='row'>
                        <div class='col-sm-1'>Status:</div>
                        <div class='col-sm-auto'>Active: <select id="f_active" name="active">
                                <option value="Y">Y</option>
                                <option value="N">N</option>
                            </select>
                        </div>
                        <div class='col-sm-auto'>Banned: <select id='f_banned' name='banned'>
                                <option value='N'>N</option>
                                <option value='Y'>Y</option>
                            </select>
                        </div>
                    </div>
                    <div class="row mt-1">
                        <div class="col-sm-1">Open Notes:</div>
                        <div class="col-sm-10">
                            <textarea id="f_open_notes" name="open_notes" cols="120" rows='10' wrap='soft'
                                placeholder='notes visible to registration clerks only'>
                            </textarea>
                        </div>
                    </div>
                    <div class='row mt-1'>
                        <div class='col-sm-1'>Admin Notes:</div>
                        <div class='col-sm-10'>
                            <textarea id='f_admin_notes' name='admon_notes' cols="120" rows="10" wrap="soft"
                                      placeholder='notes visible to registration admins only'>
                            </textarea>
                        </div>
                    </div>
                </div>
            </div>
            <div class='modal-footer'>
                <button class='btn btn-sm btn-secondary' type='button' data-bs-dismiss='modal'>Cancel</button>
                <button class='btn btn-sm btn-primary' type='button' id='updateExisting' onClick='findPerson.saveEdit()'>Update Existing Person</button>
            </div>
            <div id='find_edit_message' class='mt-4 p-2'></div>
        </div>
    </div>
</div>
<div id='person-history' class='modal modal-xl fade' tabindex='-1' aria-labelledby='Person History' aria-hidden='true' style='--bs-modal-width: 96%;'>
    <div class='modal-dialog'>
        <div class='modal-content'>
            <div class='modal-header bg-primary text-bg-primary'>
                <div class='modal-title'>
                    <strong id='historyTitle'>Person History</strong>
                </div>
                <button type='button' class='btn-close' data-bs-dismiss='modal' aria-label='Close'></button>
            </div>
            <div class='modal-body' style='padding: 4px; background-color: lightcyan;'>
                <div class='container-fluid' id='personHistory-div'></div>
            </div>
            <div class='modal-footer'>
                <button class='btn btn-sm btn-secondary' data-bs-dismiss='modal'>Close</button>
            </div>
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
            <div class='row mt-2'>
                <div class="col-sm-1">Search for:</div>
                <div class="col-sm">
                    <input type='text' id='find_pattern' name='find_pattern' maxlength='80' size='80'
                           placeholder='Name/Portion of (Name, Address, Email, Badgename, Legal Name) or Person ID'/>
                </div>
            </div>
            <div class="row mt-2">
                <div class="col-sm-12 p-0 m-0" id="findTable"></div>
            </div>
            <div class='row mt-2'>
                <div class='col-sm-1'></div>
                <div class="col-sm-11">
                    <button class='btn btn-sm btn-primary' type='button'  id='findPersonBTN' onclick='findPerson.find();'>Find Person to Edit</button>
                    <button class='btn btn-sm btn-secondary' type='button' id='findAddPersonBTN' onclick='findPerson.addPerson();' disabled>Not found, Add New
                        Person</button>
                </div>
            </div>
        </div>
    </div>
    <div class='tab-pane fade' id='add-pane' role='tabpanel' aria-labelledby='add-tab' tabindex='0'>
        <div class='container-fluid'>
            <div class='row mt-2'>
                <div class='col-sm-12' id='addH1Div'><H1 class='h3'><b>Add Person</b></H1></div>
            </div>
<?php
    drawEditPersonBlock($con_conf, true, $policies, 'addPerson', false, true, '', null, 100, true, 'a_');
?>
        <div class="row mt-2">
            <div class="col-sm-auto">
                <button class="btn btn-sm btn-primary" type='button' onclick="addPerson.checkExists();">Check If Already Exists</button>
                <button class="btn btn-sm btn-secondary" type='button' onclick="addPerson.clearForm();">Clear Add Person Form</button>
                <button class="btn btn-sm btn-secondary" type='button' id="addPersonBTN" onclick="addPerson.addPerson();" disabled>Add New Person</button>
            </div>
        </div>
        <div class='row mt-2'>
            <div class='col-sm-12' id='matchTable'></div>
        </div>
    </div>
</div>
<div id='result_message' class='mt-4 p-2'></div>

<pre id='test'>
</pre>

<?php
page_foot($page);
?>
