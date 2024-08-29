<?php
global $db_ini;

require_once "lib/base.php";
//initialize google session
$need_login = google_init("page");

$page = "reg_admin";
if(!$need_login or !checkAuth($need_login['sub'], $page)) {
    bounce_page("index.php");
}

$cdn = getTabulatorIncludes();
page_init($page,
    /* css */ array($cdn['tabcss'],
                    // $cdn['tabbs5'],
                    'css/base.css',
                    ),
    /* js  */ array(//$cdn['luxon'],
                    $cdn['tabjs'],
                    'js/tinymce/tinymce.min.js',
                    'js/reg_admin.js',
                    'js/regadmin_consetup.js',
                    'js/regadmin_memconfig.js',
                    'js/regadmin_merge.js',
                    'js/regadmin_customText.js',
                    'js/regadmin_policy.js',
                    'js/regadmin_interests.js',
                    'js/regadmin_rules.js',
                    'jslib/emailBulkSend.js'),
                    $need_login);

$con_conf = get_conf('con');
$controll = get_conf('controll');
if ($controll != null && array_key_exists('badgelistfilter', $controll)) {
    $badgeListFilter = $controll['badgelistfilter'];
    if ($badgeListFilter != "top" && $badgeListFilter != "bottom")
        $badgeListFilter = "top";
} else
    $badgeListFilter = "top";

$conid = $con_conf['id'];
$debug = get_conf('debug');

if (array_key_exists('controll_regadmin', $debug))
    $debug_admin=$debug['controll_regadmin'];
else
    $debug_regadmin = 0;

?>
<div id='parameters' <?php if (!($debug_regadmin & 4)) echo 'hidden'; ?>>
    <div id="debug"><?php echo $debug_regadmin; ?></div>
    <div id="conid"><?php echo $conid; ?></div>
</div>
<?php bs_tinymceModal(); ?>
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
<div id='editPreviewModal' class='modal modal-xl fade' tabindex='-1' aria-labelledby='Edit and Preview Policy Configuration' aria-hidden='true'
     style='--bs-modal-width: 96%;'>
    <div class='modal-dialog'>
        <div class='modal-content'>
            <div class='modal-header bg-primary text-bg-primary'>
                <div class='modal-title'>
                    <strong id='editPreviewTitle'>Edit Preview Title</strong>
                </div>
                <button type='button' class='btn-close' data-bs-dismiss='modal' aria-label='Close'></button>
            </div>
            <div class='modal-body' style='padding: 4px; background-color: lightcyan;'>
                <div class='container-fluid' id='editBlockDiv'>
                    <div class='row mt-4'>
                        <div class='col-sm-12'><h4>Edit the <span id="editPolicyName">policyName</span> policy</h4></div>
                    </div>
                    <div class='row mt-2'>
                        <div class='col-sm-12'><b>Policy Prompt:</b></div>
                    </div>
                    <div class='row mt-1'>
                        <div class='col-sm-12'>
                            <textarea rows='5' cols='120' id='policyPrompt' name='policyPrompt'>policyPrompt</textarea>
                        </div>
                    </div>
                    <div class='row mt-4'>
                        <div class='col-sm-12'><b>Policy Description:</b></div>
                    </div>
                    <div class='row mt-1'>
                        <div class='col-sm-12'>
                            <textarea rows='5' cols='120' id='policyDescription' name='policyDescription'></textarea>
                        </div>
                    </div>
                </div>
                <div class='container-fluid' id='previewBlockDiv'>
                    <div class='row mt-4'>
                        <div class='col-sm-12'>
                            <h4>Preview the <span id='previewPolicyName'>policyName</span> policy
                                <button class='btn btn-primary' onclick='policy.updatePreview()'>Update Preview</button>
                            </h4>
                        </div>
                    </div>
                    <div class='row'>
                        <div class='col-sm-12'>
                            <p class='text-body' id='previewBody'>
                                <label>
                                    <input type='checkbox' name='p_preview' id='p_preview' value='Y'/>
                                    <span class='warn' id='l_required'>&bigstar;</span><span id='l_preview'>Preview Prompt</span>
                                </label>
                                <span class='small' id='previewDescIcon'>
                                    <a href='javascript:void(0)' onClick='$("#previewTip").toggle()'>
                                        <img src='/images/infoicon.png'
                                            alt='click this info icon for more information' style='max-height: 25px;'>
                                    </a>
                                </span>
                                <div id='previewTip' class='padded highlight' style="display:none">
                                    <p class='text-body'><span id="previewDescriptionText">Preview Text</span>
                                        <span class='small'>
                                            <a href='javascript:void(0)' onClick='$("#previewTip").toggle()'>
                                                <img src='/images/closeicon.png'
                                                     alt='click this close icon to close the more information window' style='max-height: 25px;'>
                                            </a>
                                        </span>
                                    </p>
                                </div>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            <div class='modal-footer'>
                <button class='btn btn-sm btn-secondary' data-bs-dismiss='modal'>Cancel</button>
                <button class='btn btn-sm btn-primary' id='editPreviewSaveBtn' onClick='policy.editPreviewSave()'>Save Changes</button>
            </div>
            <div id='result_message_editPreview' class='mt-4 p-2'></div>
        </div>
    </div>
</div>
<div id='editInterestsModal' class='modal modal-xl fade' tabindex='-1' aria-labelledby='Edit and Preview Interest Configuration' aria-hidden='true'
     style='--bs-modal-width: 96%;'>
    <div class='modal-dialog'>
        <div class='modal-content'>
            <div class='modal-header bg-primary text-bg-primary'>
                <div class='modal-title'>
                    <strong id='editInterestsTitle'>Edit Interests Title</strong>
                </div>
                <button type='button' class='btn-close' data-bs-dismiss='modal' aria-label='Close'></button>
            </div>
            <div class='modal-body' style='padding: 4px; background-color: lightcyan;'>
                <div class='container-fluid' id='editInterestBlockDiv'>
                    <div class='row mt-4'>
                        <div class='col-sm-12'><h4>Edit the <span id='editInterestName'>interestName</span> interest</h4></div>
                    </div>
                    <div class='row'>
                        <div class='col-sm-1'>
                            <label for="iName">Name:</label>
                        </div>
                        <div class="col-sm-11">
                            <input type="text" id="iName" name="iName" size="20" maxlength="16" placeholder="name"/>
                        </div>
                    </div>
                    <div class='row'>
                        <div class='col-sm-1'>
                            <label for='iNotify'>Notify:</label>
                        </div>
                        <div class='col-sm-11'>
                            <textarea rows='5' cols='120' id='iNotify' name='iNotify' maxlength="500" wrap="soft"
                                      placeholder='comma separated list of email addresses, leave empty if CSV is Y'>
                            </textarea>
                        </div>
                    </div>
                    <div class='row mt-2'>
                        <div class='col-sm-12'><b>Interest Description:</b></div>
                    </div>
                    <div class='row mt-1'>
                        <div class='col-sm-12'>
                            <textarea rows='5' cols='120' id='interestDescription' name='interestDescription'>interestDescription</textarea>
                        </div>
                    </div>
                </div>
            </div>
            <div class='modal-footer'>
                <button class='btn btn-sm btn-secondary' data-bs-dismiss='modal'>Cancel</button>
                <button class='btn btn-sm btn-primary' id='editInterestSaveBtn' onClick='interests.editInterestSave()'>Save Changes</button>
            </div>
            <div id='result_message_editInterest' class='mt-4 p-2'></div>
        </div>
    </div>
</div>
<div id='editRuleModal' class='modal modal-xl fade' tabindex='-1' aria-labelledby='Edit and Test Rules Configuration'
     aria-hidden='true' style='--bs-modal-width: 96%;'>
    <div class='modal-dialog'>
        <div class='modal-content'>
            <div class='modal-header bg-primary text-bg-primary'>
                <div class='modal-title'>
                    <strong id='editRuleTitle'>Edit Rule Title</strong>
                </div>
                <button type='button' class='btn-close' data-bs-dismiss='modal' aria-label='Close'></button>
            </div>
            <div class='modal-body' style='padding: 4px; background-color: lightcyan;'>
                <div class='container-fluid' id='editRuleBlockDiv'>
                    <div class='row mt-4'>
                        <div class='col-sm-12'><h4>Edit the <span id='editRuleName'>Rule Name</span> Rule</h4></div>
                    </div>
                    <div class='row mt-1'>
                        <div class='col-sm-6'>
                            <div class='container-fluid' id='editRuleFieldDiv'>
                                <div class="row">
                                    <div class='col-sm-2'>
                                        <label for='rName'>Name:</label>
                                    </div>
                                    <div class='col-sm-10'>
                                        <input type='text' id='rName' name='rName' size='20' maxlength='16' placeholder='name'/>
                                    </div>
                                </div>
                                <div class='row mt-1'>
                                    <div class='col-sm-2'>
                                        <label for='rOptionName'>Option Name:</label>
                                    </div>
                                    <div class='col-sm-10'>
                                        <input type='text' id='rOptionName' name='rOptionName' size='64' maxlength='64' placeholder='option name'/>
                                    </div>
                                </div>
                                <div class='row mt-1'>
                                    <div class='col-sm-3'>
                                        <button class="btn btn-sm btn-primary" type="button" onclick="rules.editTypes('r');">Edit Mem Types</button>
                                    </div>
                                    <div class='col-sm-9' id="rTypeList"><i>None</i></div>
                                </div>
                                <div class='row mt-1'>
                                    <div class='col-sm-3'>
                                        <button class='btn btn-sm btn-primary' type='button' onclick="rules.editCategories('r');">Edit Mem Cats</button>
                                    </div>
                                    <div class='col-sm-9' id='rCatList'><i>None</i></div>
                                </div>
                                <div class='row mt-1'>
                                    <div class='col-sm-3'>
                                        <button class='btn btn-sm btn-primary' type='button' onclick="rules.editAges('r');">Edit Mem Ages</button>
                                    </div>
                                    <div class='col-sm-9' id='rAgeList'><i>None</i></div>
                                </div>
                                <div class='row mt-1'>
                                    <div class='col-sm-3'>
                                        <button class='btn btn-sm btn-primary' type='button' onclick="rules.editMemList('r');">Edit Mem Ids</button>
                                    </div>
                                    <div class='col-sm-9' id='rMemList'><i>None</i></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class='container-fluid' id='editRuleSelDiv'>
                                <div class='row mb-2'>
                                    <div class='col-sm-12' id="editRuleSelLabel"></div>
                                </div>
                                <div class='row'>
                                    <div class='col-sm-12 m-0 p-0' id='editRuleSelTable'></div>
                                </div>
                                <div class='row mt-1' id="editRuleSelButtons" name="editRuleSelButtons">
                                    <div class='col-sm-auto'>
                                        <button class="btn btn-secondary btn-sm" type="button" onclick="rules.closeSelTable('r');">Cancel Changes</button>
                                    </div>
                                    <div class='col-sm-auto'>
                                        <button class='btn btn-secondary btn-sm' type='button' onclick="rules.setRuleSel('r', false);">
                                            Clear All Items
                                        </button>
                                    </div>
                                    <div class='col-sm-auto'>
                                        <button class='btn btn-secondary btn-sm' type='button' onclick="rules.setRuleSel('r', true);">
                                            Select All Items
                                        </button>
                                    </div>
                                    <div class='col-sm-auto'>
                                        <button class='btn btn-primary btn-sm' type='button' onclick="rules.applyRuleSel('r');">Apply Selections</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class='row mt-2'>
                        <div class='col-sm-12'><b>Rule Description:</b></div>
                    </div>
                    <div class='row mt-1'>
                        <div class='col-sm-12'>
                            <textarea rows='5' cols='120' id='ruleDescription' name='ruleDescription'>ruleDescription</textarea>
                        </div>
                    </div>
                    <div class='row mt-1'>
                        <div class='col-sm-12'><b>Steps:</b></div>
                    </div>
                    <div class="row">
                        <div class="col-sm-12 m-0 p-0" id="ruleStepDiv"></div>
                    </div>
                    <div class='row mt-2'>
                        <div class='col-sm-auto' id='steps-buttons'>
                            <button id='steps-undo' type='button' class='btn btn-secondary btn-sm' onclick='rules.undoSteps(); return false;' disabled>Undo</button>
                            <button id='steps-redo' type='button' class='btn btn-secondary btn-sm' onclick='rules.redoSteps(); return false;' disabled>Redo</button>
                            <button id='steps-addrow' type='button' class='btn btn-secondary btn-sm' onclick='rules.addrowSteps(); return false;'>Add New</button>
                        </div>
                    </div>
                </div>
                <div id='result_message_editRule' class='mt-4 p-2'></div>
            </div>
            <div class='modal-footer'>
                <button class='btn btn-sm btn-secondary' data-bs-dismiss='modal'>Cancel</button>
                <button class='btn btn-sm btn-primary' id='editRuleSaveBtn' onClick='rules.editRuleSave()'>Save Changes</button>
            </div>
        </div>
    </div>
</div>
<div id='editRuleStepModal' class='modal modal-xl fade' tabindex='-1' aria-labelledby='Edit Rule Step Block' aria-hidden='true'
     style='--bs-modal-width: 96%;'>
    <div class='modal-dialog'>
        <div class='modal-content'>
            <div class='modal-header bg-primary text-bg-primary'>
                <div class='modal-title'>
                    <strong id='editRuleStepTitle'>Edit Rule Step Title</strong>
                </div>
                <button type='button' class='btn-close' data-bs-dismiss='modal' aria-label='Close'></button>
            </div>
            <div class='modal-body' style='padding: 4px; background-color: lightcyan;'>
                <div class='container-fluid' id='editRuleStepBlockDiv'>
                    <div class='row mt-4'>
                        <div class='col-sm-12'><h4>Edit the <span id='editRuleStepName'>Rule Step Name</span> Rule</h4></div>
                    </div>
                    <div class='row mt-1'>
                        <div class='col-sm-6'>
                            <div class='container-fluid' id='editRuleStepFieldDiv'>
                                <div class='row'>
                                    <div class='col-sm-2'>
                                        <label for='sName'>Step Name:</label>
                                    </div>
                                    <div class='col-sm-10'>
                                        <input type='text' id='sName' name='sName' size='20' maxlength='16' placeholder='step name'/>
                                    </div>
                                </div>
                                <div class='row mt-1'>
                                    <div class='col-sm-2'>
                                        <label for='sStep'>Step #:</label>
                                    </div>
                                    <div class='col-sm-10'>
                                        <input type='number' class='no-spinners' inputmode='numeric' id='sStep' name='sStem'
                                               size='10' min="1" max="999" placeholder='Step #'/>
                                    </div>
                                </div>
                                <div class='row mt-1'>
                                    <div class='col-sm-2'>
                                        <label for='sRuleType'>Rule Type:</label>
                                    </div>
                                    <div class='col-sm-10'>
                                        <select id='sRuleType' name='sRuleType'>
                                            <option value="">--Select Rule Type--</option>
                                            <option value="needAny">Need Any</option>
                                            <option value="needAll">Need All</option>
                                            <option value='notAny'>Not Any</option>
                                            <option value='notAll'>Not All</option>
                                            <option value='limitAge'>Limit Age</option>
                                            <option value='currentAge'>Current Age</option>
                                        </select>
                                    </div>
                                </div>
                                <div class='row mt-1'>
                                    <div class='col-sm-2'>
                                        <label for='sApplyTo'>Apply To:</label>
                                    </div>
                                    <div class='col-sm-10'>
                                        <select id='sApplyTo' name='sApplyTo'>
                                            <option value=''>--Select Apply To--</option>
                                            <option value='person'>Person</option>
                                            <option value='all'>All in Account</option>
                                        </select>
                                    </div>
                                </div>
                                <div class='row mt-1'>
                                    <div class='col-sm-3'>
                                        <button class='btn btn-sm btn-primary' type='button' onclick="rules.editTypes('s');">Edit Mem Types</button>
                                    </div>
                                    <div class='col-sm-9' id='sTypeList'><i>None</i></div>
                                </div>
                                <div class='row mt-1'>
                                    <div class='col-sm-3'>
                                        <button class='btn btn-sm btn-primary' type='button' onclick="rules.editCategories('s');">Edit Mem Cats</button>
                                    </div>
                                    <div class='col-sm-9' id='sCatList'><i>None</i></div>
                                </div>
                                <div class='row mt-1'>
                                    <div class='col-sm-3'>
                                        <button class='btn btn-sm btn-primary' type='button' onclick="rules.editAges('s');">Edit Mem Ages</button>
                                    </div>
                                    <div class='col-sm-9' id='sAgeList'><i>None</i></div>
                                </div>
                                <div class='row mt-1'>
                                    <div class='col-sm-3'>
                                        <button class='btn btn-sm btn-primary' type='button' onclick="rules.editMemList('s');">Edit Mem Ids</button>
                                    </div>
                                    <div class='col-sm-9' id='sMemList'><i>None</i></div>
                                </div>
                            </div>
                        </div>
                        <div class='col-sm-6'>
                            <div class='container-fluid' id='editRuleStepSelDiv'>
                                <div class='row mb-2'>
                                    <div class='col-sm-12' id='editRuleStepSelLabel'></div>
                                </div>
                                <div class='row'>
                                    <div class='col-sm-12 m-0 p-0' id='editRuleStepSelTable'></div>
                                </div>
                                <div class='row mt-1' id='editRuleStepSelButtons' name='editRuleStepSelButtons'>
                                    <div class='col-sm-auto'>
                                        <button class='btn btn-secondary btn-sm' type='button' onclick="rules.closeSelTable('s');">Cancel Changes</button>
                                    </div>
                                    <div class='col-sm-auto'>
                                        <button class='btn btn-secondary btn-sm' type='button' onclick="rules.setRuleSel('s', false);">
                                            Clear All Items
                                        </button>
                                    </div>
                                    <div class='col-sm-auto'>
                                        <button class='btn btn-secondary btn-sm' type='button' onclick="rules.setRuleSel('s', true);">
                                            Select All Items
                                        </button>
                                    </div>
                                    <div class='col-sm-auto'>
                                        <button class='btn btn-primary btn-sm' type='button' onclick="rules.applyRuleSel('s');">Apply Selections</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div id='result_message_editRuleStep' class='mt-4 p-2'></div>
            </div>
            <div class='modal-footer'>
                <button class='btn btn-sm btn-secondary' onClick='rules.editRuleStepSave(false);'>Cancel</button>
                <button class='btn btn-sm btn-primary' id='editRuleSaveBtn' onClick='rules.editRuleStepSave(true);'>Save Changes</button>
            </div>
        </div>
    </div>
</div>
<div id='changeModal' class='modal modal-xl fade' tabindex='-1' aria-labelledby='Change Registration' aria-hidden='true' style='--bs-modal-width: 96%;'>
<div class='modal-dialog'>
    <div class='modal-content'>
        <div class='modal-header bg-primary text-bg-primary'>
            <div class='modal-title'>
                <strong id='changeTitle'>Change Registration</strong>
            </div>
            <button type='button' class='btn-close' data-bs-dismiss='modal' aria-label='Close'></button>
        </div>
        <div class='modal-body' style='padding: 4px; background-color: lightcyan;'>
            <div class='container-fluid' id="change-body-div"></div>
            <div class='container-fluid' id="transfer-search-div" hidden>
                <div class='row p-1'>
                    <div class='col-sm-2 p-0'>
                        <label for='name'>Transfer Registration:</label>
                    </div>
                    <div class='col-sm-10 p-0' id='transfer_registration'></div>
                </div>
                <div class='row p-1'>
                    <div class='col-sm-2 p-0'>
                        <label for='name'>Transfer From:</label>
                    </div>
                    <div class='col-sm-10 p-0' id='transfer_from'></div>
                </div>
                <div class='row p-1'>
                    <div class='col-sm-2 p-0'>
                        <label for='search_name'>Transfer To:</label>
                    </div>
                    <div class='col-sm-10 p-0'>
                        <input class='form-control-sm' type='text' name='namesearch' id='transfer_name_search' size='64'
                               placeholder='Name/Portion of Name, Person (Registration) ID'/>
                    </div>
                </div>
                <div class='row mt-3'>
                    <div class='col-sm-2 p-0'></div>
                    <div class='col-sm-10 p-0'>
                        <button class='btn btn-sm btn-primary' id='transferSearch' onClick='changeTransferFind()'>Find Person</button>
                    </div>
                </div>
                <div class='row mt-3'>
                    <div class='col-sm-12 p-0' id="transfer_search_results"></div>
                </div>
            </div>
            <div class='container-fluid' id='rollover-div' hidden>
                <div class='row p-1'>
                    <div class='col-sm-2 p-0'>
                        <label for='name'>Rollover Registration:</label>
                    </div>
                    <div class='col-sm-10 p-0' id='rollover_registration'></div>
                </div>
                <div class='row p-1'>
                    <div class='col-sm-12 p-0'>
                        <label for='name'>Select Registration Type for the rollover</label>
                    </div>
                </div>
                <div class='row p-1'>
                    <div class='col-sm-12 p-0' id="rollover_select"></div>
                </div>
                <div class='row mt-3 mb-2'>
                    <div class='col-sm-1 p-0'></div>
                    <div class='col-sm-10 p-0'>
                        <button class='btn btn-sm btn-primary' id='rollover-execute' onClick='changeRolloverExecute()'>Execute Rollover</button>
                    </div>
                </div>
            </div>
            <div class='container-fluid' id='editReg-div' hidden>
                <div class='row p-1'>
                    <div class='col-sm-2 p-0'>
                        <label for='name'>Edit Membershop:</label>
                    </div>
                    <div class='col-sm-10 p-0' id='edit_registration_label'></div>
                </div>
                <div class='row mt-1'>
                    <div class='col-sm-1'>Reg Type:</div>
                    <div class='col-sm-auto'>New:</div>
                    <div class='col-sm-auto' id='edit_memSelect'></div>
                    <div class='col-sm-auto'>Original:</div>
                    <div class='col-sm-auto' id='edit_origMemLabel'></div>
                </div>
                <div class='row mt-1'>
                    <div class='col-sm-1'>Price:</div>
                    <div class='col-sm-auto'>New:</div>
                    <div class='col-sm-auto'>
                        <input type="number" placeholder="New Price" id="edit_newPrice"/>
                    </div>
                    <div class='col-sm-auto'>Original:</div>
                    <div class='col-sm-auto' id='edit_origPrice'></div>
                    <div class='col-sm-auto'>New Reg:</div>
                    <div class='col-sm-auto' id='edit_newRegPrice'></div>
                </div>
                <div class='row mt-1'>
                    <div class='col-sm-1'>Paid:</div>
                    <div class='col-sm-auto'>New:</div>
                    <div class='col-sm-auto'>
                        <input type='number' placeholder='New Paid' id='edit_newPaid'/>
                    </div>
                    <div class='col-sm-auto'>Original:</div>
                    <div class='col-sm-auto' id='edit_origPaid'></div>
                </div>
                <div class='row mt-1'>
                    <div class='col-sm-1'>Coupon:</div>
                    <div class='col-sm-auto'>New:</div>
                    <div class='col-sm-auto'>
                        <input type='number' placeholder='New Coupon' id='edit_newCoupon'/>
                    </div>
                    <div class='col-sm-auto'>Original:</div>
                    <div class='col-sm-auto' id='edit_origCoupon'></div>
                </div>
                <div class='row mt-1'>
                    <div class='col-sm-1'>Cpn Disc:</div>
                    <div class='col-sm-auto'>New:</div>
                    <div class='col-sm-auto'>
                        <input type='number' placeholder='New Coupon' id='edit_newCouponDiscount'/>
                    </div>
                    <div class='col-sm-auto'>Original:</div>
                    <div class='col-sm-auto' id='edit_origCouponDiscount'></div>
                </div>
                <div class='row mt-1'>
                    <div class='col-sm-1'>Status:</div>
                    <div class='col-sm-auto'>New:</div>
                    <div class='col-sm-auto' id='edit_statusSelect'></div>
                    <div class='col-sm-auto'>Original:</div>
                    <div class='col-sm-auto' id='edit_origStatus'></div>
                </div>
                <div class='row mt-3 mb-2'>
                    <div class='col-sm-1 p-0'></div>
                    <div class='col-sm-10 p-0'>
                        <button class='btn btn-sm btn-secondary' id='edit_discard' onClick='changeEditClose()'>Discard Changes</button>
                        <button class='btn btn-sm btn-primary' id='edit_save' onClick='changeEditSave(0)'>Save Changes</button>
                        <button class='btn btn-sm btn-warning' id='edit_saveOverride' onClick='changeEditSave(1)' hidden>
                            Save Changes Overriding Warnings
                        </button>
                    </div>
                </div>
            </div>
            <div class='container-fluid'>
                <div "class=row mt-2">
                    <div class="col-sm-12" id="changeMessageDiv"></div>
                </div>
            </div>
        </div>
        <div class='modal-footer'>
            <button class='btn btn-sm btn-secondary' data-bs-dismiss='modal'>Close</button>
        </div>
    </div>
</div>
</div>
<div id='receipt' class='modal modal-xl fade' tabindex='-1' aria-labelledby='Registration Receipt' aria-hidden='true' style='--bs-modal-width: 80%;'>
    <div class='modal-dialog'>
        <div class='modal-content'>
            <div class='modal-header bg-primary text-bg-primary'>
                <div class='modal-title'>
                    <strong id='receiptTitle'>Registration Receipt</strong>
                </div>
                <button type='button' class='btn-close' data-bs-dismiss='modal' aria-label='Close'></button>
            </div>
            <div class='modal-body' style='padding: 4px; background-color: lightcyan;'>
                <div id="receipt-div"></div>
                <div id="regadminemail" hidden="true"><?php echo $con_conf['regadminemail'];?></div>
                <div id="receipt-text" hidden="true"></div>
                <div id="receipt-tables" hidden="true"></div>
            </div>
            <div class='modal-footer'>
                <button class='btn btn-sm btn-secondary' data-bs-dismiss='modal'>Close</button>
                <button class='btn btn-sm btn-primary' id='emailReceipt' onClick='receipt_email("payor")'>Email Receipt</button>
                <button class='btn btn-sm btn-primary' id='emailReceiptReg' onClick='receipt_email("reg")'>Email Receipt to regadmin at <?php echo $con_conf['regadminemail'];?></button>
            </div>
        </div>
    </div>
</div>
<div id='notes' class='modal modal-xl fade' tabindex='-1' aria-labelledby='Registration Notes' aria-hidden='true' style='--bs-modal-width: 80%;'>
    <div class='modal-dialog'>
        <div class='modal-content'>
            <div class='modal-header bg-primary text-bg-primary'>
                <div class='modal-title'>
                    <strong id='notesTitle'>Registration Notes</strong>
                </div>
                <button type='button' class='btn-close' data-bs-dismiss='modal' aria-label='Close'></button>
            </div>
            <div class='modal-body' style='padding: 4px; background-color: lightcyan;'>
                <div class="container-fluid" id="notesText"></div>
            </div>
            <div class='modal-footer'>
                <button class='btn btn-sm btn-secondary' data-bs-dismiss='modal'>Close</button>
            </div>
        </div>
    </div>
</div>
<ul class='nav nav-tabs mb-3' id='regadmin-tab' role='tablist'>
    <li class='nav-item' role='presentation'>
        <button class='nav-link active' id='registrationlist-tab' data-bs-toggle='pill' data-bs-target='#registrationlist-pane' type='button'
                role='tab' aria-controls='nav-registrationlist' aria-selected='true' onclick="settab('registrationlist-pane');">Registration List
        </button>
    </li>
    <li class='nav-item' role='presentation'>
        <button class='nav-link' id='consetup-tab' data-bs-toggle='pill' data-bs-target='#consetup-pane' type='button' role='tab'
                aria-controls='nav-consetup' aria-selected='false' onclick="settab('consetup-pane');">Current Convention Setup
        </button>
    </li>
    <li class='nav-item' role='presentation'>
        <button class='nav-link' id='nextconsetup-tab' data-bs-toggle='pill' data-bs-target='#nextconsetup-pane' type='button' role='tab'
                aria-controls='nav-nextconsetup' aria-selected='false' onclick="settab('nextconsetup-pane');">Next Convention Setup
        </button>
    </li>
    <li class='nav-item' role='presentation'>
        <button class='nav-link' id='memconfig-tab' data-bs-toggle='pill' data-bs-target='#memconfig-pane' type='button' role='tab'
                aria-controls='nav-memconfigsetup' aria-selected='false' onclick="settab('memconfig-pane');">Membership Configuration
        </button>
    </li>
    <li class='nav-item' role='presentation'>
        <button class='nav-link' id='customtext-tab' data-bs-toggle='pill' data-bs-target='#customtext-pane' type='button' role='tab'
                aria-controls='nav-customtext' aria-selected='false' onclick="settab('customtext-pane');">Custom Text
        </button>
    </li>
    <li class='nav-item' role='presentation'>
        <button class='nav-link' id='policy-tab' data-bs-toggle='pill' data-bs-target='#policy-pane' type='button' role='tab'
                aria-controls='nav-policy' aria-selected='false' onclick="settab('policy-pane');">Policies
        </button>
    </li>
    <li class='nav-item' role='presentation'>
        <button class='nav-link' id='interests-tab' data-bs-toggle='pill' data-bs-target='#interests-pane' type='button' role='tab'
                aria-controls='nav-interests' aria-selected='false' onclick="settab('interests-pane');">Interests
        </button>
    </li>
    <li class='nav-item' role='presentation'>
        <button class='nav-link' id='rules-tab' data-bs-toggle='pill' data-bs-target='#rules-pane' type='button' role='tab'
                aria-controls='nav-rules' aria-selected='false' onclick="settab('rules-pane');">Membership Rules
        </button>
    </li>
    <li class='nav-item' role='presentation'>
        <button class='nav-link' id='merge-tab' data-bs-toggle='pill' data-bs-target='#merge-pane' type='button' role='tab'
                aria-controls='nav-merge' aria-selected='false' onclick="settab('merge-pane');">Merge People
        </button>
    </li>
</ul>
<div class='tab-content ms-2' id='regadmin-content'>
    <div class='tab-pane fade show active' id='registrationlist-pane' role='tabpanel' aria-labelledby='registrationlist-tab' tabindex='0'>
        <div class="container-fluid">
<?php
    if ($badgeListFilter == "top")
        drawFilters();
?>
        <div class="row">
            <div class="col-sm-auto p-0">
                <div id="registration-table"></div>
            </div>
        </div>
<?php
    if ($badgeListFilter == 'bottom')
        drawFilters();
?>
        <div class="row">
            <div class="col-sm-auto p-2">
                <button class="btn btn-primary btn-sm" onclick="window.location.href = 'reports/allEmails.php';" disabled>Download Email List</button>
            </div>
            <div class="col-sm-auto p-2">
                <button class="btn btn-primary btn-sm" onclick="window.location.href = 'reports/regReport.php';" disabled>Download Reg Report</button>
            </div>
            <div class="col-sm-auto p-2">
                <button class="btn btn-primary btn-sm" onclick="sendEmail('marketing')" disabled>Send Marketing Email</button>
            </div>
            <div class='col-sm-auto p-2'>
                <button class='btn btn-primary btn-sm' onclick="sendEmail('comeback')" disabled>Send Come Back Email</button>
            </div>
            <div class="col-sm-auto p-2">
                <button class="btn btn-primary btn-sm" onclick="sendEmail('reminder')" disabled>Send Attendance Reminder Email</button>
            </div>
            <?php if ($db_ini['con']['survey_url']) { ?>
            <div class="col-sm-auto p-2">
                <button class="btn btn-primary btn-sm" onclick="sendEmail('survey')" disabled>Send Survey Email</button>
            </div>
            <?php } ?>
            <?php if ($db_ini['reg']['cancelled']) { ?>
            <div class="col-sm-auto p-2">
                <button class="btn btn-primary btn-sm" onclick="sendCancel()" disabled>Send Cancelation Instructions</button>
            </div>
            <div class="col-sm-auto p-2">
                <button class="btn btn-primary btn-sm" onclick="window.location.href = 'reports/cancel.php';" disabled>Download Cancellation Report</button>
            </div>
            <div class="col-sm-auto p-2">
                <button class="btn btn-primary btn-sm" onclick="window.location.href = 'reports/processRefunds.php';">Download Process Refunds Report</button>
            </div>
            <?php } ?>
        </div>
    </div>
    </div></div>
    <div class='tab-pane fade' id='consetup-pane' role='tabpanel' aria-labelledby='consetup-tab' tabindex='0'></div>
    <div class='tab-pane fade' id='nextconsetup-pane' role='tabpanel' aria-labelledby='nextconsetup-tab' tabindex='0'></div>
    <div class='tab-pane fade' id='memconfig-pane' role='tabpanel' aria-labelledby='memconfig-tab' tabindex='0'></div>
    <div class='tab-pane fade' id='merge-pane' role='tabpanel' aria-labelledby='merge-tab' tabindex='0'></div>
    <div class='tab-pane fade' id='customtext-pane' role='tabpanel' aria-labelledby='customtext-tab' tabindex='0'></div>
    <div class='tab-pane fade' id='policy-pane' role='tabpanel' aria-labelledby='policy-tab' tabindex='0'></div>
    <div class='tab-pane fade' id='interests-pane' role='tabpanel' aria-labelledby='interests-tab' tabindex='0'></div>
    <div class='tab-pane fade' id='rules-pane' role='tabpanel' aria-labelledby='rules-tab' tabindex='0'></div>
    <div id='result_message' class='mt-4 p-2'></div>
</div>
<pre id='test'>
</pre>
<?php

page_foot($page);

function drawFilters() {
?>
<div class="container-fluid">
    <div class="row mb-2">
        <div class="col-sm-auto me-1 p-0">Click on a row to toggle filtering by that value</div>
        <div class="col-sm-auto me-1 p-0">
            <button class="btn btn-primary btn-sm" onclick="clearfilter();">Clear All Filters</button>
        </div>
    </div>
    <div class="row">
        <div class="col-sm-auto me-1 p-0">
            <div id="category-table"></div>
        </div>
        <div class="col-sm-auto me-1  p-0">
            <div id="type-table"></div>
        </div>
        <div class="col-sm-auto me-1  p-0">
            <div id="age-table"></div>
        </div>
        <div class="col-sm-auto me-1  p-0">
            <div id="price-table"></div>
        </div>
        <div class="col-sm-auto me-1 p-0">
            <div id="label-table"></div>
        </div>
        <div class='col-sm-auto me-1 p-0'>
            <div id='coupon-table'></div>
        </div>
        <div class='col-sm-auto me-1 p-0'>
            <div id='status-table'></div>
        </div>
    </div>
    <?php
}
?>
