<?php

//  match edit: handle l/r editing of a match for both people unmatched and merge
function matchEdit($type, $titleName, $leftName, $middleName, $rightName, $className, $countryOptions, $policiesCell, $ageList ) {
    $ageOptions = '';
    foreach ($ageList as $age) {
        $ageOptions .= '<option value="' . escape_quotes($age['ageType']) . '">' . $age['shortname'] . ' [' . $age['label'] . "]</option>\n";
    }

    $html = <<<EOS
                <div class='container-fluid' id="editMatch">
                    <div class="row mt-4">
                        <div class="col-sm-12">
                            <h1 class="h3" id="$titleName">
                                <b>Editing: A and B</b>
                            </h1>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-sm-1 border border-dark ps-1 pe-1">Field</div>
                        <div class="col-sm-3 border border-dark">$leftName</div>
                        <div class="col-sm-5 border border-dark">$middleName</div>
                        <div class="col-sm-3 border border-dark">$rightName</div>
                    </div>
                    <div class="row">
                        <div class="col-sm-1 border border-dark ps-1 pe-1">ID</div>
                        <div class='col-sm-3 border border-dark pe-0'>
                            <div class='container-fluid'>
                                <div class='row justify-content-between'>
                                    <div class='col-sm-auto ms-0 me-0 ps-0 pe-0' id='matchID'></div>
                                    <div class='col-sm-auto ms-0 me-0 ps-0 pe-0'>
                                        <button class='btn btn-sm btn-light pt-0 pb-0 mt-0 mb-0 justify-content-end'
                                                type='button' onclick="$className.copy('matchAll')">
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
                                                type='button' onclick="$className.copy('newAll')">
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
                                                type='button' onclick="$className.copy('matchName')">
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
                                                type='button' onclick="$className.copy('newName')">
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
                                                type='button' onclick="$className.copy('matchLegal')">
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
                                                type='button' onclick="$className.copy('newLegal')">
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
                                                type='button' onclick="$className.copy('matchPronouns')">
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
                                                type='button' onclick="$className.copy('newPronouns')">
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
                                                type='button' onclick="$className.copy('matchBadge')">
                                            &gt;&gt;
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class='col-sm-5 border border-dark ps-1 pe-1'>
                            <input type='text' id='badgeName' name='badgeName' maxlength='32' size='32' placeholder='Defaults to First Last'/><br/>
                            <input type='text' id='badgeNameL2' name='badgeNameL2' maxlength='32' size='32' placeholder='Badge Line 2'/>
                        </div>
                        <div class='col-sm-3 border border-dark ps-0'>
                            <div class='container-fluid'>
                                <div class='row'>
                                    <div class='col-sm-auto ms-0 me-0 ps-0 pe-0'>
                                        <button class='btn btn-sm btn-light pt-0 pb-0 mt-0 mb-0 me-2 justify-content-end'
                                                type='button' onclick="$className.copy('newBadge')">
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
                                                type='button' onclick="$className.copy('matchAddress')">
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
                            <input type='text' id='state' name='state' maxlength='16' size='16' placeholder='State/Prov'/>
                            <input type='text' id='zip' name='zip' maxlength='10' size='10' placeholder='Postal Code'/>

                            <label for='country' class='form-label-sm'>
                                <span class='text-dark' style='font-size: 10pt;'>Country</span>
                            </label><br/>
                            <select name='country' id='country'>                
                                  $countryOptions;
                            </select>
                        </div>
                        <div class='col-sm-3 border border-dark ps-0'>
                            <div class='container-fluid'>
                                <div class='row'>
                                    <div class='col-sm-auto ms-0 me-0 ps-0 pe-0'>
                                        <button class='btn btn-sm btn-light pt-0 pb-0 mt-0 mb-0 me-2 justify-content-end'
                                                type='button' onclick="$className.copy('newAddress')">
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
                                                type='button' onclick="$className.copy('matchEmail')">
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
                                                type='button' onclick="$className.copy('newEmail')">
                                            &lt;&lt;
                                        </button>
                                    </div>
                                    <div class='col-sm-auto ms-0 me-0 ps-0 pe-0' id='newEmail'></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class='row'>
                        <div class='col-sm-1 border border-dark ps-1 pe-1'>Current Age</div>
                        <div class='col-sm-3 border border-dark pe-0'>
                            <div class='container-fluid'>
                                <div class='row justify-content-between'>
                                    <div class='col-sm-auto ms-0 me-0 ps-0 pe-0' id='matchAge'></div>
                                    <div class='col-sm-auto ms-0 me-0 ps-0 pe-0'>
                                        <button class='btn btn-sm btn-light pt-0 pb-0 mt-0 mb-0 justify-content-end'
                                                type='button' onclick="$className.copy('matchAge')">
                                            &gt;&gt;
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class='col-sm-5 border border-dark ps-1 pe-1'>
                            <select id='age'>
                                <option value=''>--Select Age Bracket--</option>
                                $ageOptions
                        </div>
                        <div class='col-sm-3 border border-dark ps-0'>
                            <div class='container-fluid'>
                                <div class='row'>
                                    <div class='col-sm-auto ms-0 me-0 ps-0 pe-0'>
                                        <button class='btn btn-sm btn-light pt-0 pb-0 mt-0 mb-0 me-2 justify-content-end'
                                                type='button' onclick="$className.copy('newAge')">
                                            &lt;&lt;
                                        </button>
                                    </div>
                                    <div class='col-sm-auto ms-0 me-0 ps-0 pe-0' id='newAge'></div>
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
                                                type='button' onclick="$className.copy('matchPhone')">
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
                                                type='button' onclick="$className.copy('newPhone')">
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
                                                type='button' onclick="$className.copy('matchPolicies')">
                                            &gt;&gt;
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class='col-sm-5 border border-dark ps-1 pe-1' id="policiesDiv">
                            $policiesCell
                        </div>
                        <div class='col-sm-3 border border-dark ps-0'>
                            <div class='container-fluid'>
                                <div class='row'>
                                    <div class='col-sm-auto ms-0 me-0 ps-0 pe-0'>
                                        <button class='btn btn-sm btn-light pt-0 pb-0 mt-0 mb-0 me-2 justify-content-end'
                                                type='button' onclick="$className.copy('newPolicies')">
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
                                                type='button' onclick="$className.copy('matchFlags')">
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
                                                type='button' onclick="$className.copy('newFlags')">
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
                                                type='button' onclick="$className.copy('matchManager')">
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
                                                type='button' onclick="$className.copy('newManager')">
                                            &lt;&lt;
                                        </button>
                                    </div>
                                    <div class='col-sm-auto ms-0 me-0 ps-0 pe-0' id='newManager'></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
EOS;
    return $html;
}
