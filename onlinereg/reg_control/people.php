<?php
require_once "lib/base.php";
//initialize google session
$need_login = google_init("page");

$page = "people";
if(!$need_login or !checkAuth($need_login['sub'], $page)) {
    bounce_page("index.php");
}

// Auto assign perid for newid for exact match

// first update contact ok and ok to share
$updateQ = <<<EOF
UPDATE perinfo p
JOIN newperson n ON (
	REGEXP_REPLACE(TRIM(LOWER(IFNULL(n.first_name,''))), '  *', ' ') =
		REGEXP_REPLACE(TRIM(LOWER(IFNULL(p.first_name, ''))), '  *', ' ')
	AND REGEXP_REPLACE(TRIM(LOWER(IFNULL(n.middle_name,''))), '  *', ' ') =
		REGEXP_REPLACE(TRIM(LOWER(IFNULL(p.middle_name, ''))), '  *', ' ')
	AND REGEXP_REPLACE(TRIM(LOWER(IFNULL(n.last_name,''))), '  *', ' ') =
		REGEXP_REPLACE(TRIM(LOWER(IFNULL(p.last_name, ''))), '  *', ' ')
	AND REGEXP_REPLACE(TRIM(LOWER(IFNULL(n.suffix,''))), '  *', ' ') =
		REGEXP_REPLACE(TRIM(LOWER(IFNULL(p.suffix, ''))), '  *', ' ')
	AND REGEXP_REPLACE(TRIM(LOWER(IFNULL(n.email_addr,''))), '  *', ' ') =
		REGEXP_REPLACE(TRIM(LOWER(IFNULL(p.email_addr, ''))), '  *', ' ')
	AND REGEXP_REPLACE(TRIM(LOWER(IFNULL(n.phone,''))), '  *', ' ') =
		REGEXP_REPLACE(TRIM(LOWER(IFNULL(p.phone, ''))), '  *', ' ')
	AND REGEXP_REPLACE(TRIM(LOWER(IFNULL(n.badge_name,''))), '  *', ' ') =
		REGEXP_REPLACE(TRIM(LOWER(IFNULL(p.badge_name, ''))), '  *', ' ')
	AND REGEXP_REPLACE(TRIM(LOWER(IFNULL(n.address,''))), '  *', ' ') =
		REGEXP_REPLACE(TRIM(LOWER(IFNULL(p.address, ''))), '  *', ' ')
	AND REGEXP_REPLACE(TRIM(LOWER(IFNULL(n.addr_2,''))), '  *', ' ') =
		REGEXP_REPLACE(TRIM(LOWER(IFNULL(p.addr_2, ''))), '  *', ' ')
	AND REGEXP_REPLACE(TRIM(LOWER(IFNULL(n.city,''))), '  *', ' ') =
		REGEXP_REPLACE(TRIM(LOWER(IFNULL(p.city, ''))), '  *', ' ')
	AND REGEXP_REPLACE(TRIM(LOWER(IFNULL(n.state,''))), '  *', ' ') =
		REGEXP_REPLACE(TRIM(LOWER(IFNULL(p.state, ''))), '  *', ' ')
	AND REGEXP_REPLACE(TRIM(LOWER(IFNULL(n.zip,''))), '  *', ' ') =
		REGEXP_REPLACE(TRIM(LOWER(IFNULL(p.zip, ''))), '  *', ' ')
	AND REGEXP_REPLACE(TRIM(LOWER(IFNULL(n.country,''))), '  *', ' ') =
		REGEXP_REPLACE(TRIM(LOWER(IFNULL(p.country, ''))), '  *', ' ')
)
SET p.contact_ok = IFNULL(n.contact_ok, 'Y'), p.share_reg_ok = IFNULL(n.share_reg_ok,'Y')
WHERE n.perid IS NULL and p.id is not null;
EOF;

dbquery($updateQ);

// now resolve exact matches

$updateQ = <<<EOF
UPDATE newperson n
JOIN perinfo p ON (
	REGEXP_REPLACE(TRIM(LOWER(IFNULL(n.first_name,''))), '  *', ' ') =
		REGEXP_REPLACE(TRIM(LOWER(IFNULL(p.first_name, ''))), '  *', ' ')
	AND REGEXP_REPLACE(TRIM(LOWER(IFNULL(n.middle_name,''))), '  *', ' ') =
		REGEXP_REPLACE(TRIM(LOWER(IFNULL(p.middle_name, ''))), '  *', ' ')
	AND REGEXP_REPLACE(TRIM(LOWER(IFNULL(n.last_name,''))), '  *', ' ') =
		REGEXP_REPLACE(TRIM(LOWER(IFNULL(p.last_name, ''))), '  *', ' ')
	AND REGEXP_REPLACE(TRIM(LOWER(IFNULL(n.suffix,''))), '  *', ' ') =
		REGEXP_REPLACE(TRIM(LOWER(IFNULL(p.suffix, ''))), '  *', ' ')
	AND REGEXP_REPLACE(TRIM(LOWER(IFNULL(n.email_addr,''))), '  *', ' ') =
		REGEXP_REPLACE(TRIM(LOWER(IFNULL(p.email_addr, ''))), '  *', ' ')
	AND REGEXP_REPLACE(TRIM(LOWER(IFNULL(n.phone,''))), '  *', ' ') =
		REGEXP_REPLACE(TRIM(LOWER(IFNULL(p.phone, ''))), '  *', ' ')
	AND REGEXP_REPLACE(TRIM(LOWER(IFNULL(n.badge_name,''))), '  *', ' ') =
		REGEXP_REPLACE(TRIM(LOWER(IFNULL(p.badge_name, ''))), '  *', ' ')
	AND REGEXP_REPLACE(TRIM(LOWER(IFNULL(n.address,''))), '  *', ' ') =
		REGEXP_REPLACE(TRIM(LOWER(IFNULL(p.address, ''))), '  *', ' ')
	AND REGEXP_REPLACE(TRIM(LOWER(IFNULL(n.addr_2,''))), '  *', ' ') =
		REGEXP_REPLACE(TRIM(LOWER(IFNULL(p.addr_2, ''))), '  *', ' ')
	AND REGEXP_REPLACE(TRIM(LOWER(IFNULL(n.city,''))), '  *', ' ') =
		REGEXP_REPLACE(TRIM(LOWER(IFNULL(p.city, ''))), '  *', ' ')
	AND REGEXP_REPLACE(TRIM(LOWER(IFNULL(n.state,''))), '  *', ' ') =
		REGEXP_REPLACE(TRIM(LOWER(IFNULL(p.state, ''))), '  *', ' ')
	AND REGEXP_REPLACE(TRIM(LOWER(IFNULL(n.zip,''))), '  *', ' ') =
		REGEXP_REPLACE(TRIM(LOWER(IFNULL(p.zip, ''))), '  *', ' ')
	AND REGEXP_REPLACE(TRIM(LOWER(IFNULL(n.country,''))), '  *', ' ') =
		REGEXP_REPLACE(TRIM(LOWER(IFNULL(p.country, ''))), '  *', ' ')
)
SET n.perid = p.id
WHERE n.perid IS NULL and p.id is not null AND p.active = 'Y';
EOF;

dbquery($updateQ);

page_init($page,
    /* css */ array('css/base.css'
                   ),
    /* js  */ array('js/d3.js',
                    'js/base.js',
                    'js/people.js'
                   ),
              $need_login);
?>
<div id='main'>
    <div class='half right'>
        <div id='searchresults'>
            <span class='blocktitle'>Search Results</span>
            <span id="resultCount">
            </span>
            <div id='searchResultHolder'>
            </div>
        </div>
    </div>

    <div class='half'>
        <div id="conflictView"><span class="blocktitle">Unmatched New People</span>
            (<span id='conflictCount'><?php echo countConflicts($need_login['sub']); ?></span>)
            <a class='showlink' id='conflictViewShowLink'
                href='javascript:void(0)' onclick='showBlock("#conflictView")'>
                (show)
            </a>
            <a class='hidelink' id='conflictViewHideLink'
                href='javascript:void(0)' onclick='hideBlock("#conflictView")'>
                (hide)
            </a>
            <form class='inline' id="fetchNewPerson" method="GET"
                action="javascript:void(0)">
                <input type="text" name="id" placeholder='newPerId'
                    id="newPersonNumber" size=10 maxlength=10/>
                <input type="submit" value="Get"
                    onClick='fetchNewPerson("#fetchNewPerson")'/>
            </form>
            (f for first, l for last or the newperson id)
            <form id='conflictViewForm' method="POST"
                action='javascript:void(0)'>
                <input type='hidden' name='oldID' id='conflictOldIDfield'/>
                <input type='hidden' name='newID' id='conflictNewIDfield'/>
                <table id='conflictViewTable'>
                    <thead>
                        <tr><th>Field</th>
                            <th colspan=2>Existing DB Value</th>
                            <th>New Value</th>
                            <th colspan=2>New User Input</th>
                        </tr>
                        <tr><th>ID</th>
                            <th id="conflictFormOldID" colspan=2></th>
                            <th></th>
                            <th id="conflictFormNewID" colspan=2></th>
                        </tr>
                    </thead>
                    <tbody id='conflictFormName'>
                        <tr><th>Name</th>
                            <td id='conflictFormDbName'>dbName</td>
                            <td class='right'><button id='useDbName' onclick="setField('Name', 'Db');">&gt;</button></td>
                            <td>
                                <input id='conflictFormNewFName' type='text' name='first_name'/>
                                <input id='conflictFormNewMName' size=5 type='text' name='middle_name'/>
                                <input id='conflictFormNewLName' type='text' name='last_name'/>
                                <input id='conflictFormNewSuffix' size=4 type='text' name='suffix'/>
                            </td>
                            <td><button id='useNewName' onclick="setField('Name', 'User');">&lt;</button></td>
                            <td id='conflictFormUserName'>User Input</td>
                        </tr>
                        <tr><th>Badge</th>
                            <td id='conflictFormDbBadge'>dbBadge</td>
                            <td class='right'><button id='useDbBadge' onclick="setField('Badge', 'Db');">&gt;</button></td>
                            <td>
                                <input id='conflictFormNewBadge' type='text' name='badge_name'/>
                            </td>
                            <td><button id='useNewBadge' onclick="setField('Badge', 'User');">&lt;</button></td>
                            <td id='conflictFormUserBadge'>User Input</td>
                        </tr>
                    </tbody>
                    <tbody id='conflictFormAddress'>
                        <tr>
                            <th rowspan=4 class='center'>Addr</th>
                            <td id='conflictFormDbAddr'>dbAddr</td>
                            <td class='right'></td>
                            <td>
                                <input id='conflictFormNewAddr' size=40 type='text' name='address'/>
                            </td>
                            <td></td>
                            <td id='conflictFormUserAddr'>User Input</td>
                        </tr>
                        <tr>
                            <td id='conflictFormDbAddr2'>dbAddr2</td>
                            <td class='right'><button id='useDbAddr2' onclick="setField('Addr', 'Db');">&gt;</button></td>
                            <td>
                                <input id='conflictFormNewAddr2' size=40 type='text' name='addr_2'/>
                            </td>
                            <td><button id='useNewAddr2' onclick="setField('Addr', 'User');">&lt;</button></td>
                            <td id='conflictFormUserAddr2'>User Input</td>
                        </tr>
                        <tr>
                            <td id='conflictFormDbLocale'>dbLocale</td>
                            <td class='right'></td>
                            <td>
                                <input id='conflictFormNewCity' type='text' name='city'/>
                                <input id='conflictFormNewState' type='text' name='state' size=2/>
                                <input id='conflictFormNewZip' type='text' name='zip' size=5/>
                                <input id='conflictFormNewCountry' type='text' name='country' size=10/>
                            </td>
                            <td></td>
                            <td id='conflictFormUserLocale'>User Input</td>
                        </tr>
                    </tbody>
                    <tbody id='conflictFormContact'>
                        <tr>
                            <th>Email</th>
                            <td id='conflictFormDbEmail'>dbEmail</td>
                            <td class='right'><button id='useDbEmail' onclick="setField('Email', 'Db');">&gt;</button></td>
                            <td>
                                <input id='conflictFormNewEmail' type='text' name='email_addr'/>
                            </td>
                            <td><button id='useNewEmail' onclick="setField('Email', 'User');">&lt;</button></td>
                            <td id='conflictFormUserEmail'>User Input</td>
                        </tr>
                        <tr>
                            <th>Phone</th>
                            <td id='conflictFormDbPhone'>dbPhone</td>
                            <td class='right'><button id='useDbPhone' onclick="setField('Phone', 'Db');">&gt;</button></td>
                            <td>
                                <input id='conflictFormNewPhone' type='text' name='phone'/>
                            </td>
                            <td><button id='useNewPhone' onclick="setField('Phone', 'User');">&lt;</button></td>
                            <td id='conflictFormUserPhone'>User Input</td>
                        </tr>
                        <tr>
                            <th>Flags</th>
                            <td id='conflictFormDbFlags'>dbFlags</td>
                            <td class='right'><button id='useDbFlags' onclick="setField('Flags', 'Db');">&gt;</button></td>
                             <td>
                                <span class="formlabel">&nbsp;&nbsp;Share Reg?</span>
                                <input type="radio" name="conflictFormNewShareReg" value="Y">Y</input>
                                <input type="radio" name="conflictFormNewShareReg" value="N">N</input>
                                <span class="formlabel">Contact?</span>
                                <input type="radio" name="conflictFormNewContactOK" value="Y">Y</input>
                                <input type="radio" name="conflictFormNewContactOK" value="N">N</input>
                            </td>
                            <td><button id='useNewFlags' onclick="setField('Flags', 'User');">&lt;</button></td>
                            <td id='conflictFormUserFlags'>User Input</td>
                        </tr>
                        <tr><td></td>
                        <td colspan=2>
                            <button class='right' onclick="setField('all', 'Db');">
                                Use Existing Values &gt;
                            </button>
                        </td>
                        <td>
                        </td>
                        <td colspan=2>
                            <button onclick="setField('all', 'User');">
                                &lt; Use New Values
                            </button>
                        </td>
                        </tr>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan=6 class='righttext'>
                                <button onclick='conflictGetPerid()'>Find PerID</button><input type='submit' id='conflictUpdate' value='Update Person' onclick='updateConflict("existing")'/> <input type='submit' id='conflictNew' value='New Person (no match)' onclick='updateConflict("new")'/>
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </form>
        </div> <!-- end of conflictView -->
        <hr/>
        <div id="searchPerson"><span class="blocktitle">Search Person</span>
            <a class='showlink' id='searchPersonShowLink' href='javascript:void(0)'
                onclick='showBlock("#searchPerson")'>(show)</a>
            <a class='hidelink' id='searchPersonHideLink' href='javascript:void(0)'
                onclick='hideBlock("#searchPerson")'>(hide)</a>
            <form class='inline' id="findPerson" method="GET" action="javascript:void(0)">
                Name: <input type="text" name="full_name" id="findPersonFullName"/>
                <input type="submit" value="Find" onClick='findPerson("#findPerson")'/>
            </form>
        </div>
        <hr/>
        <div id="editPerson"><span class="blocktitle">Edit Person</span>
            <a class='showlink' id='editPersonShowLink' href='javascript:void(0)'
                onclick='showBlock("#editPerson")'>(show)</a>
            <a class='hidelink' id='editPersonHideLink' href='javascript:void(0)'
                onclick='hideBlock("#editPerson")'>(hide)</a>
            <form class='inline' id="fetchPerson" method="GET" action="javascript:void(0)">
                <input type="text" name="id" placeholder='PerId' id="editPersonNumber" size=10 maxlength=10/>
                <input type="submit" value="Get" onClick='fetchPerson("#fetchPerson")'/>
            </form>
            <form id="editPersonForm" name="editPerson" method="POST" action="javascript:void(0)">
                <input type="hidden" name="id"/>
                <table class='formalign'>
                    <thead id='editPersonFormId'>
                        <tr>
                            <td class='formlabel'>Create: <span id="editPersonFormIdCreate"></span></td>
                            <td class='formlabel'>Change: <span id="editPersonFormIdUpdate"></span></td>
                            <td/>
                            <td class='formlabel'>PerID# <span id="editPersonFormIdNum"></span></td>
                        </tr>
                    </thead>
                    <tbody id='editPersonFormName'>
                        <tr>
                            <td class='formlabel'>First Name</td>
                            <td class='formlabel'>Middle Name</td>
                            <td class='formlabel' colspan=2>Last Name</td>
                            <td class='formlabel'>Suffix</td>
                        </tr>
                        <tr>
                            <td class='formfield'><input type="text" name="fname" size=20/></td>
                            <td class='formfield'><input type="text" name="mname" size=20/></td>
                            <td class='formfield' colspan=2><input type="text" name="lname" size=20/></td>
                            <td class='formfield'><input type="text" name="suffix" size=4 maxlength=4/></td>
                        </tr>
                        <tr>
                            <td class='formlabel'>Badge Name</td>
                            <td colspan=4></td>
                        </tr>
                        <tr>
                            <td class='formfield'><input type="text" name="badge" size=20/></td>
                            <td colspan=4></td>
                        </tr>
                    </tbody>
                    <tbody id='editPersonFormAddress'>
                        <tr>
                            <td class='formlabel' colspan=5>Street Address</td>
                        </tr>
                        <tr>
                            <td class='formfield' colspan=5><input type="text" name="address" size=60/>
                        </tr>
                        <tr>
                            <td class='formlabel' colspan=5>Company/Address Line 2</td>
                        </tr>
                        <tr>
                            <td class='formfield' colspan=5><input type="text" name="addr2" size=60/></td>
                        </tr>
                        <tr>
                            <td class='formlabel' colspan=2>City/Locality</td>
                            <td class='formlabel'>State</td>
                            <td class='formlabel'>Zip</td>
                            <td></td>
                        </tr>
                        <tr>
                            <td class='formfield' colspan=2><input type="text" name="city" size=40/></td>
                            <td class='formfield'><input type="text" name="state" size=2 maxlength=2/></td>
                            <td class='formfield'><input type="text" name="zip" size=5 maxlength=10/></td>
                            <td></td>
                        </tr>
                        <tr>
                            <td class='formlabel'>Country</td>
                            <td colspan=4></td>
                        </tr>
                        <tr>
                            <td class='formfield'><input type="text" name="country" size="15" value="USA"/></td>
                            <td colspan=4></td>
                        </tr>
                    </tbody>
                    <tbody id="editPersonFormContact">
                        <tr>
                            <td class='formlabel' colspan=2>Email Addr</td>
                            <td class='formlabel'>Phone</td>
                            <td colspan=2></td>
                        </tr>
                        <tr>
                            <td class='formfield' colspan=2><input type="text" name="email" size=30/></td>
                            <td class='formfield' colspan=2><input type="text" name="phone" size=10/></td>
                            <td></td>
                        </tr>
                    </tbody>
                    <tbody id="editPersonFormChecks">
                        <tr>
                            <td class='formfield'><span class="formlabel">Share Reg?</span>
                                <input type="radio" name="share_reg" value="Y">Y</input>
                                <input type="radio" name="share_reg" value="N">N</input>
                            </td>
                            <td class='formfield' colspan=2><span class="formlabel">Contact?</span>
                                <input type="radio" name="contact_ok" value="Y">Y</input>
                                <input type="radio" name="contact_ok" value="N">N</input>
                            </td>
                            <td colspan=2></td>
                        </tr>
                        <tr>
                            <td class='small'>Last Reg <span id="editPersonFormLastReg"></span></td>
                            <td class='small'>Last Pickup <span id="editPersonFormLastPickup"></span></td>
                            <td colspan=3></td>
                        </tr>
                    </tbody>
                    <tbody id="editPersonFormStatus">
                        <tr>
                            <td class='formfield'><span class="formlabel">Active?</span>
                                <input type="radio" name="active" value="Y">Y</input>
                                <input type="radio" name="active" value="N">N</input>
                            </td>
                            <td class='formfield'><span class="formlabel">Banned?</span>
                                <input type="radio" name="banned" value="Y">Y</input>
                                <input type="radio" name="banned" value="N">N</input>
                            </td>
                            <td colspan=3></td>
                        </tr>
                    </tbody>
                    <tbody id="editPersonFormNotes">
                        <tr>
                            <td class='formlabel' colspan=2>Open Notes</td>
                            <td class='formlabel' colspan=3>Private Notes</td>
                        </tr>
                        <tr>
                            <td class='formfield' colspan=2><textarea rows=5 cols=30 name="open_notes"></textarea></td>
                            <td class='formfield' colspan=3><textarea rows=5 cols=20 name="admin_notes"></textarea></td>
                        </tr>
                    </tbody>
                    <tfoot id="editPersonFormButtons">
                        <tr>
                            <td colspan=5><input type="submit" value="Update Person" onClick='submitUpdateForm("#editPersonForm", "scripts/editPerson.php", getUpdated, null)'/>
                                <input type="reset"/></td>
                        </tr>
                    </tfoot>
                </table>
            </form>
        </div> <!-- end editPerson -->
        <hr/>
        <div id="addPerson"><span class="blocktitle"4>Add Person</span>
            <a class='showlink' id='addPersonShowLink' href='javascript:void(0)'
                onclick='showBlock("#addPerson")'>(show)</a>
            <a class='hidelink' id='addPersonHideLink' href='javascript:void(0)'
                onclick='hideBlock("#addPerson")'>(hide)</a>
            <br/>
            <form id="addPersonForm" name="addPerson" method="POST" action="javascript:void(0)">
                <input type="hidden" name="share_reg" value="Y"/>
                <input type="hidden" name="contact_ok" value="Y"/>
                <input type="hidden" name="active" value="Y"/>
                <input type="hidden" name="banned" value="N"/>               
                <table class='formalign'>
                    <tbody id='addPersonFormName'>
                        <tr>
                            <td class='formlabel'>First Name</td>
                            <td class='formlabel'>Middle Name</td>
                            <td class='formlabel' colspan=2>Last Name</td>
                            <td class='formlabel'>Suffix</td>
                        </tr>
                        <tr>
                            <td class='formfield'><input type="text" name="fname" size=20/></td>
                            <td class='formfield'><input type="text" name="mname" size=20/></td>
                            <td class='formfield' colspan=2><input type="text" name="lname" size=20/></td>
                            <td class='formfield'><input type="text" name="suffix" size=4 maxlength=4/></td>
                        </tr>
                        <tr>
                            <td class='formlabel'>Badge Name</td>
                        </tr>
                        <tr>
                            <td class='formfield'><input type="text" name="badge" size=20/></td>
                        </tr>
                    </tbody>
                    <tbody id='addPersonFormAddress'>
                        <tr>
                            <td class='formlabel' colspan=5>Street Address</td>
                        </tr>
                        <tr>
                            <td class='formfield' colspan=4><input type="text" name="address" size=60/>
                        </tr>
                        <tr>
                            <td class='formlabel' colspan=4>Company/Address Line 2</td>
                        </tr>
                        <tr>
                            <td class='formfield' colspan=4><input type="text" name="addr2" size=60/></td>
                        </tr>
                        <tr>
                            <td class='formlabel' colspan=2>City/Locality</td>
                            <td class='formlabel'>State</td>
                            <td class='formlabel'>Zip</td>
                        </tr>
                        <tr>
                            <td class='formfield' colspan=2><input type="text" name="city" size=40/></td>
                            <td class='formfield'><input type="text" name="state" size=2 maxlength=2/></td>
                            <td class='formfield'><input type="text" name="zip" size=5 maxlength=10/></td>
                        </tr>
                        <tr>
                            <td class='formlabel'>Country</td>
                        </tr>
                        <tr>
                            <td class='formfield'><input type="text" name="country" size="15" value="USA"/></td>
                        </tr>
                    </tbody>
                    <tbody id="addPersonFormContact">
                        <tr>
                            <td class='formlabel' colspan=2>Email Addr</td>
                            <td class='formlabel'>Phone</td>
                            <td></td>
                        </tr>
                        <tr>
                            <td class='formfield' colspan=2><input type="text" name="email" size=30/></td>
                            <td class='formfield' colspan=2><input type="text" name="phone" size=10/></td>
                            <td></td>
                        </tr>
                    </tbody>                     
                    <tfoot id="addPersonFormButtons">
                        <tr>
                            <td colspan=5>
                                <input type="submit" value="Check Conflicts Person" onClick='checkPerson("#addPersonForm")'/>
                                <input type="reset"/>
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </form>
        </div> <!-- end Add Person -->

    </div>
</div>

<pre id='test'>
</pre>

<?php
page_foot($page);
?>
