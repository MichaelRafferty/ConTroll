<?php
require_once "lib/base.php";
//initialize google session
$need_login = google_init("page");

$page = "club";
if(!$need_login or !checkAuth($need_login['sub'], $page)) {
    bounce_page("index.php");
}

page_init($page,
    /* css */ array('css/base.css',
                    'css/club.css'
                   ),
    /* js  */ array('js/d3.js',
                    'js/people.js',
                    'js/club.js'
                   ),
              $need_login);

$con = get_conf("con");
$conid = $con['id'];

?>
<script>
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
      <input type='hidden' name='id'/>
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
            </tr>
            <tr>
                <td class='formfield'><input type="text" name="badge" size=20/></td>
            </tr>
        </tbody>
        <tbody id='editPersonFormAddress'>
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
                <td class='formfield'><input type="text" name="state" size=10 maxlength=16/></td>
                <td class='formfield'><input type="text" name="zip" size=5 maxlength=10/></td>
            </tr>
            <tr>
                <td class='formlabel'>Country</td>
            </tr>
            <tr>
                <td class='formfield'><input type="text" name="country" size="15" value="USA"/></td>
            </tr>
        </tbody>
        <tbody id="editPersonFormContact">
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
        <tfoot id="editPersonFormButtons">
            <tr>
                <td colspan=5>
                    <input type="submit" value="Update Person" onClick='submitUpdateForm("#editForm", "scripts/editPerson.php", getUpdated, null)'/>
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
  <input type='hidden' id='oldID' name='conflictOldIDfield' value=''/>
  <table>
    <thead>
      <tr>
        <th colspan=4>New Person</th>
        <th style="width: 10em;">Old Person</th>
        <th style="width: 5em;">change?</th>
      </tr>
    </thead>
    <tbody>
      <tr>
        <td class='formlabel'>First Name</td>
        <td class='formlabel'>Middle Name</td>
        <td class='formlabel'>Last Name</td>
        <td class='formlabel'>Suffix</td>
        <td class='separated formlabel'>Old Name</td>
        <td class='separated formlabel'>Use New Name</td>
      </tr>
      <tr>
        <td><input tabindex=1 type='text' name='fname' id='fname' required='required'/></td>
        <td><input tabindex=2 type='text' name='mname' id='mname'/></td>
        <td><input tabindex=3 type='text' name='lname' id='lname' required='required'/></td>
        <td><input tabindex=4 type='text' name='suffix' size=4 id='fname'/></td>
        <td class='separated' id='conflictFormOldName'></td>
        <td class='separated center'><input type='checkbox' name='conflictFormName' value='checked' checked='checked'></td>
      </tr>
      <tr>
        <td class='formlabel' colspan=4>Badge Name</td>
        <td class='formlabel separated'>Old Badge Name</td>
        <td class='formlabel separated'>Use New Badge</td>
      </tr>
      <tr>
        <td colspan=4><input tabindex=5 type='text' name='badge' id='obadge'/></td>
        <td class='separated' id='conflictFormOldBadge'></td>
        <td class='separated center'><input type='checkbox' name='conflictFormBadge' value='checked' checked='checked'></td>
      </tr>
      <tr>
        <td class='formlabel' colspan=4>Email</td>
        <td class='formlabel separated'>Old Email</td>
        <td class='formlabel separated'>Use New Email</td>
      </tr>
      <tr>
        <td colspan=4><input tabindex=6 type='text' name='email' id='email' required='required'/></td>
        <td class='separated' id='conflictFormOldEmail'></td>
        <td class='separated center'>
            <input type='checkbox' name='conflictFormEmail' value='checked' checked='checked'/>
        </td>
      </tr>
      <tr>
        <td class='formlabel' colspan=4>Phone #</td>
        <td class='formlabel separated'>Old Phone #</td>
        <td class='formlabel separated'>Use New Phone</td>
      </tr>
      <tr>
        <td colspan=4><input tabindex=7 type='text' name='phone' id='phone'/></td>
        <td class='separated' id='conflictFormOldPhone'></td>
        <td class='separated center'>
            <input type='checkbox' name='conflictFormPhone' value='checked' checked='checked'/>
        </td>
      </tr>
     <tr>
        <td colspan=4 class='formlabel'>Street Address</td>
        <td class='separated formlabel'>Old Address</td>
        <td class='separated formlabel'>Use New Address</td>
      </tr>
      <tr>
        <td colspan=4>
          <input tabindex=8 type='text' name='address' id='addr' size=60 required='required'/>
        </td>
        <td class='separated' id='conflictFormOldAddr'></td>
        <td class='separated center' rowspan=4>
            <input type='checkbox' name='conflictFormAddr' value='checked' checked='checked'/>
        </td>
      </tr>
      <tr>
        <td colspan=6 class='formlabel'>Company/2nd Line
      </tr>
      <tr>
        <td colspan=4>
          <input tabindex=9 type='text' name='addr2' id='addr2' size=60/>
        </td>
        <td class='separated' id='conflictFormOldAddr2'></td>
      </tr>
      <tr>
        <td class='formlabel'>City</td>
        <td class='formlabel'>State/Zip</td>
        <td class='formlabel' colspan=4>Country</td>
      </tr>
      <tr>
        <td>
          <input tabindex=10 type='text' name='city' id='city' required='required'/>
        </td>
        <td>
          <input tabindex=11 type='text' size=10 maxlength="16" name='state' id='state' required='required'/> /
          <input tabindex=12 type='text' name='zip' id='zip' size=5 maxlength="10" required='required'/>
        </td>
        <td colspan=2>
          <select tabindex=13 id='country' name='country' size=1 width=20>
            <?php
            $fh = fopen(__DIR__ . '/../lib/countryCodes.csv', 'r');
            while(($data = fgetcsv($fh, 1000, ',', '"'))!=false) {
              echo "<option value='".$data[1]."'>".$data[0]."</option>";
            }
            fclose($fh);
            ?>
          </select>
        </td>
        <td class='separated' id='conflictFormOldLocale'></td>
        <td/>
      </tr>
    </tbody>
    <tfoot>
      <tr>
        <td colspan=6>
          <button tabindex=14 type='submit' id='checkConflict'
            onClick='testValid("#newPersonForm") && checkForReg("#newPersonForm"); return false'
          >Check Person</button>
          <button type='submit' id='updatePerson'
            onClick='testValid("#newPersonForm") && submitForm("#newPersonForm", "scripts/oldEditPersonFromConflict.php", updatePersonCatch, null); return false'>Update</button>
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
    <a class='showlink' id='searchPersonShowLink' href='javascript:void(0)'
      onclick='showBlock("#searchPerson")'>(show)</a>
    <a class='hidelink' id='searchPersonHideLink' href='javascript:void(0)'
      onclick='hideBlock("#searchPerson")'>(hide)</a>
    <form class='inline' id="findPerson" method="GET" action="javascript:void(0)">
      Name: <input type="text" name="full_name" id="findPersonFullName"/>
      <input type="submit" value="Find" onClick='findPerson("#findPerson")'/>
    </form>
    <button id='newPersonShow' onClick='$("#newPerson").show()'>New Person</button>
  </div>
  <div id='clubList'><span class="blocktitle">Club List</span>
    <a class='showlink' id='clubListShowLink' href='javascript:void(0)'
      onclick='showBlock("#clubList")'>(show)</a>
    <a class='hidelink' id='clubListHideLink' href='javascript:void(0)'
      onclick='hideBlock("#clubList")'>(hide)</a>
    <table id='clubListForm'>
      <thead>
        <tr>
          <th>Name</th>
          <th>Member Type</th>
          <th>Member Year</th>
          <th>Update</th>
        </tr>
      </thead>
      <tbody id='clubNames' class='scroll'>
      </tbody>
    </table>
  </div>
</div>


<pre id='test'>
</pre>
<?php
page_foot($page);
?>
