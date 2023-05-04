<?php
global $db_ini;
require_once "lib/base.php";
//initialize google session
$need_login = google_init("page");

$page = "badge";

$con = get_con();
$conid = $con['id'];

$conf = get_conf('con');

if(!$need_login or !checkAuth($need_login['sub'], $page)) {
    bounce_page("index.php");
}

page_init($page,
    /* css */ array('css/base.css'
                   ),
    /* js  */ array('/javascript/d3.js',
                    'js/base.js',
                    'js/badge.js'
                   ),
              $need_login);

    // Get list of freebie badge types for pulldown
    $freeSQL = <<<EOS
SELECT M.id, M.label
FROM memList M
WHERE M.conid = ? and M.memCategory = 'freebie';
EOS;

    $freeR = dbSafeQuery($freeSQL, 'i', array($db_ini['con']['id']));
    $freeSelect = "<option disabled='disabled' selected='true'> -- select an option --</option>\\n";
    while($free = fetch_safe_assoc($freeR)) {
        $freeSelect .= "<option value='" . $free['id'] . "'>" . $free['label'] . "</option>\\n";
    }
?>
<script>
function badgeSelect(form) {
    var ret = "<select form='" + form + "' name='memId'>" + "<?php echo $freeSelect; ?>" + "\n</select>\n";
    return ret;
}

$(function() {
    $('#editDialog').dialog({
        autoOpen: false,
        width: 650,
        height: 450,
        modal: true,
        title: "Edit Person"
    });
});
</script>
<div id='editDialog'>
    <form id='editForm' action='javascript:void(0)'>
      <input type='hidden' id='edit_id' name='id'/>
      <input type='hidden' name='prefix'/>
      <table class='formalign'>
        <thead id='editPersonFormId'>
            <tr>
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
                <td class='formfield'><input type="text" id='edit_fname' name="fname" size=20/></td>
                <td class='formfield'><input type="text" id='edit_mname' name="mname" size=20/></td>
                <td class='formfield' colspan=2><input type="text" id='edit_lname' name="lname" size=20/></td>
                <td class='formfield'><input type="text" id='edit_suffix' name="suffix" size=4 maxlength=4/></td>
            </tr>
            <tr>
                <td class='formlabel'>Badge Name</td>
            </tr>
            <tr>
                <td class='formfield'><input type="text" id='edit_badge' name="badge" size=20/></td>
            </tr>
        </tbody>
        <tbody id='editPersonFormAddress'>
            <tr>
                <td class='formlabel' colspan=5>Street Address</td>
            </tr>
            <tr>
                <td class='formfield' colspan=4><input type="text" id='edit_addr' name="address" size=60/>
            </tr>
            <tr>
                <td class='formlabel' colspan=4>Company/Address Line 2</td>
            </tr>
            <tr>
                <td class='formfield' colspan=4><input type="text" id='edit_addr2' name="addr2" size=60/></td>
            </tr>
            <tr>
                <td class='formlabel' colspan=2>City/Locality</td>
                <td class='formlabel'>State</td>
                <td class='formlabel'>Zip</td>
            </tr>
            <tr>
                <td class='formfield' colspan=2><input type="text" id='edit_city' name="city" size=40/></td>
                <td class='formfield'><input type="text" name="state" id='edit_state' size=2 maxlength=2/></td>
                <td class='formfield'><input type="text" id='edit_zip' name="zip" size=5 maxlength=10/></td>
            </tr>
            <tr>
                <td class='formlabel'>Country</td>
            </tr>
            <tr>
                <td class='formfield'><input type="text" id='edit_country' name="country" size="15" value="USA"/></td>
            </tr>
        </tbody>
        <tbody id="editPersonFormContact">
            <tr>
                <td class='formlabel' colspan=2>Email Addr</td>
                <td class='formlabel'>Phone</td>
                <td></td>
            </tr>
            <tr>
                <td class='formfield' colspan=2><input type="text" id='edit_email' name="email" size=30/></td>
                <td class='formfield' colspan=2><input type="text" id='edit_phone' name="phone" size=10/></td>
                <td></td>
            </tr>
        </tbody>
        <tfoot id="editPersonFormButtons">
            <tr>
                <td colspan=5>
                    <input type="submit" value="Update Person" onClick='submitUpdateForm("#editForm", "scripts/editPerson.php", getEdited, null); $("#editDialog").dialog("close"); $("#editForm")[0].reset()'/>
                    <input type="reset"/>
                </td>
            </tr>
        </tfoot>
      </table>
    </form>
</div>
<div id='newPerson' class='popup'>
  <form id='newPersonForm' action='javascript:void(0)'>
  <input type='hidden' id='newID' name='newID' value=''/>
  <input type='hidden' id='oldID' name='oldID' value=''/>
  <table>
    <thead>
      <tr>
        <th colspan=4>New Person</th>
        <th>Old Person</th>
        <th>change?</th>
      </tr>
    </thead>
    <tbody>
      <tr>
        <td class='formlabel'>First Name</td>
        <td class='formlabel'>Middle Name</td>
        <td class='formlabel'>Last Name</td>
        <td class='formlabel'>Suffix</td>
        <td class='separated formlabel'>Old Name</td>
        <td class='separated formlabel'>Use Old Name</td>
      </tr>
      <tr>
        <td><input type='text' name='fname' id='fname' required='required'/></td>
        <td><input type='text' name='mname' id='mname'/></td>
        <td><input type='text' name='lname' id='lname' required='required'/></td>
        <td><input type='text' name='suffix' size=4 id='suffix'></td>
        <td class='separated' id='oname'></td>
        <td class='separated'>
          <label>Y<input type='radio' name='oname' value='Y'/></label>
          <label>N<input type='radio' checked='checked' name='oname' value='N'/></label>
        </td>
      </tr>
      <tr>
        <td class='formlabel' colspan=4>Badge Name</td>
        <td class='formlabel separated'>Old Badge Name</td>
        <td class='formlabel separated'>Use Old Badge</td>
      </tr>
      <tr>
        <td colspan=4><input type='text' name='badge' id='obadge'/></td>
        <td class='separated' id='obadge'></td>
        <td class='separated'>
          <label>Y<input type='radio' name='obadge' value='Y'/></label>
          <label>N<input type='radio' checked='checked' name='obadge' value='N'/></label>
        </td>
      </tr>
      <tr>
        <td class='formlabel' colspan=4>Email</td>
        <td class='formlabel separated'>Old Email</td>
        <td class='formlabel separated'>Use Old Email</td>
      </tr>
      <tr>
        <td colspan=4><input type='text' name='email' id='email' required='required'/></td>
        <td class='separated' id='oemail'></td>
        <td class='separated'>
          <label>Y<input type='radio' name='oemail' value='Y'/></label>
          <label>N<input type='radio' checked='checked' name='oemail' value='N'/></label>
        </td>
      </tr>
      <tr>
        <td class='formlabel' colspan=4>Phone #</td>
        <td class='formlabel separated'>Old Phone #</td>
        <td class='formlabel separated'>Use Old Phone</td>
      </tr>
      <tr>
        <td colspan=4><input type='text' name='phone' id='phone'/></td>
        <td class='separated' id='ophone'></td>
        <td class='separated'>
          <label>Y<input type='radio' name='ophone' value='Y'/></label>
          <label>N<input type='radio' checked='checked' name='ophone' value='N'/></label>
        </td>
      </tr>
     <tr>
        <td colspan=4 class='formlabel'>Street Address</td>
        <td class='separated formlabel'>Old Street</td>
        <td class='separated formlabel'>Use Old Address</td>
      </tr>
      <tr>
        <td colspan=4>
          <input type='text' name='address' id='addr' size=60 required='required'/>
        </td>
        <td class='separated' id='oaddr' rowspan=4></td>
        <td class='separated' rowspan=4>
          <label>Y<input type='radio' name='oaddr' value='Y'/></label>
          <label>N<input type='radio' checked='checked' name='oaddr' value='N'/></label>
        </td>
      </tr>
      <tr>
        <td colspan=6 class='formlabel'>Company/2nd Line
      </tr>
      <tr>
        <td colspan=4>
          <input type='text' name='addr2' id='addr2' size=60/>
        </td>
        <td class='separated' id='oaddr2'></td>
      </tr>
      <tr>
        <td class='formlabel'>City</td>
        <td class='formlabel'>State/Zip</td>
        <td class='formlabel' colspan=4>Country</td>
      </tr>
      <tr>
        <td>
          <input type='text' name='city' id='city' required='required'/>
        </td>
        <td>
          <input type='text' size=2 name='state' id='state' required='required'/> /
          <input type='text' name='zip' id='zip' size=5 required='required'/>
        </td>
        <td colspan=3>
          <select id='country' name='country' size=1 width=20>
            <?php
            $fh = fopen(__DIR__ . '/../../lib/countryCodes.csv', 'r');
            while(($data = fgetcsv($fh, 1000, ',', '"'))!=false) {
              echo "<option value='".$data[1]."'>".$data[0]."</option>";
            }
            fclose($fh);
            ?>
          </select>
        </td>
        <td class='separated' id='ocity'></td>
      </tr>
    </tbody>
    <tfoot>
      <tr>
        <td colspan=6>
          <button type='submit' id='checkConflict'
            onClick='testValid("#newPersonForm") && submitForm("#newPersonForm", "scripts/addPerson.php", searchConflictPerson, null); return false'
          >Check Person</button>
          <button type='submit' id='updatePerson'
            onClick='testValid("#newPersonForm") && submitForm("#newPersonForm", "scripts/editPersonFromBadgeList.php", updatePersonCatch, null); return false'
            disabled='disabled'>Update</button>
          <button type='reset' id='newPersonClose' onClick='$("#newPerson").hide(); return true;'>Close</button>
      </tr>
    </tfoot>
    </table>
  </form>
</div>

<div id='main'>
    <div id='searchResults' class='half right'>
        <span class='blocktitle'>Search Results</span>
        <span id="resultCount"></span>
        <div id='searchResultHolder'>
        </div>
    </div>
<div class='half'>
  <div id="searchPerson"><span class="blocktitle">Search Person</span>
    <form class='inline' id="findPerson" method="GET" action="javascript:void(0)">
      Name: <input type="text" name="full_name" id="findPersonFullName"/>
      <input type="submit" value="Find" onClick='findPerson("#findPerson")'/>
    </form>
    <button id='newPersonShow' onClick='$("#newPerson").show()'>New Person</button>
  </div>
  <div id='badgeList'><span class="blocktitle">Badge List</span>
    <a class='showlink' id='badgeListShowLink' href='javascript:void(0)'
      onclick='showBlock("#badgeList")'>(show)</a>
    <a class='hidelink' id='badgeListHideLink' href='javascript:void(0)'
      onclick='hideBlock("#badgeList")'>(hide)</a>
    <table id='badgeListForm'>
      <thead>
        <tr>
          <th>Name<br/>Badge Type</th>
          <th>Badge Name</th>
          <th>Update</th>
          <th>Edit</th>
        </tr>
      </thead>
      <tbody id='badges' class='scroll'>
      </tbody>
    </table>
  </div>
</div>
