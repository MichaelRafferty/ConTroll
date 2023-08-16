<?php
require_once "lib/base.php";
//initialize google session
$need_login = google_init("page");

$page = "registration";
if(!$need_login or !checkAuth($need_login['sub'], $page)) {
    bounce_page("index.php");
}

page_init($page,
    /* css */ array('css/base.css',
                    'css/registration.css'
                   ),
    /* js  */ array('js/d3.js',
                    'js/base.js',
                    'js/people.js',
                    'js/registration.js'
                   ),
              $need_login);

$con = get_conf("con");
$conid=$con['id'];

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
<?php
if(isset($_GET['id'])) {
    $id= $_GET['id'];
?>
    $(document).ready(function() {
        $('#fetchTransactionId').val(<?php echo $id;?>)
        $('#fetchTransactionSubmit').click();
    });
<?php
}
?>
</script>
<div id='editDialog'>
    <form id='editForm' action='javascript:void(0)'>
      <input type='hidden' name='id'/>
      <input type='hidden' name='prefix'/>
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
                    <input type="submit" value="Update Person" onClick='submitUpdateForm("#editForm", "scripts/editPerson.php", getEdited, null)'/>
                    <input type="reset"/>
                </td>
            </tr>
        </tfoot>
      </table>
    </form>
</div>
<div id='newPerson' class='popup' style="width: 75%; float: left;" >
  <form id='newPersonForm' action='javascript:void(0)'>
  <input type='hidden' id='conflictNewIDfield' name='newID' value=''/>
  <input type='hidden' id='conflictOldIDfield' name='oldID' value=''/>    
  <input type='hidden' name='honorcheckboxes' value="1" />
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
        <td><input tabindex=4 type='text' name='suffix' size=4 id='suffix'/></td>
        <td class='separated' id='conflictFormDbName'></td>
        <td class='separated center'><input type='checkbox' name='conflictFormName' value='checked' checked='checked'></td>
      </tr>
      <tr>
        <td class='formlabel' colspan=4>Badge Name</td>
        <td class='formlabel separated'>Old Badge Name</td>
        <td class='formlabel separated'>Use New Badge</td>
      </tr>
      <tr>
        <td colspan=4><input tabindex=5 type='text' name='badge_name' id='badge_name'/></td>
        <td class='separated' id='conflictFormDbBadge'></td>
        <td class='separated center'><input type='checkbox' name='conflictFormBadge' value='checked' checked='checked'></td>
      </tr>
      <tr>
        <td class='formlabel' colspan=4>Email</td>
        <td class='formlabel separated'>Old Email</td>
        <td class='formlabel separated'>Use New Email</td>
      </tr>
      <tr>
        <td colspan=4><input tabindex=6 type='text' name='email' id='email' required='required'/></td>
        <td class='separated' id='conflictFormDbEmail'></td>
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
        <td class='separated' id='conflictFormDbPhone'></td>
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
        <td class='separated' id='conflictFormDbAddr'></td>
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
        <td class='separated' id='conflictFormDbAddr2'></td>
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
          <input tabindex=11 type='text' size=2 name='state' id='state' required='required'/> /
          <input tabindex=12 type='text' name='zip' id='zip' size=5 required='required'/>
        </td>
        <td colspan=2>
          <select tabindex=13 id='country' name='country' size=1 style='width: 240px;'>
            <?php
            $fh = fopen(__DIR__ . '/../../lib/countryCodes.csv', 'r');
            while(($data = fgetcsv($fh, 1000, ',', '"'))!=false) {
              echo "<option value='".$data[1]."'>".$data[0]."</option>";
            }
            fclose($fh);
            ?>
          </select>
        </td>
        <td class='separated' id='conflictFormDbLocale'></td>
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
            onClick='testValid("#newPersonForm") && submitForm("#newPersonForm", "scripts/editPersonFromConflict.php", updatePersonCatch, null); return false'>Update</button>
          <button type='reset' id='newPersonClose' onClick='$("#newPerson").hide(); return true;'>Close</button>
      </tr>
    </tfoot>
    </table>
  </form>
</div>
<?php paymentDialogs(); ?>
<div id='main'>
    <div id='searchResults' class='half right'>
        <span class='blocktitle'>Search Results</span>
        <span id="resultCount"></span>
        <div id='searchResultHolder'>
        </div>
    </div>
  <div id='transaction' class='half'><span class='blocktitle'>Transaction</span>
    <a class='showlink' id='transactionShowLink' href='javascript:void(0)'
      onclick='showBlock("#transaction")'>(show)</a>
    <a class='hidelink' id='transactionHideLink' href='javascript:void(0)'
      onclick='hideBlock("#transaction")'>(hide)</a>
    <form class='inline' id='fetchTransaction' method="GET" action="javascript:void(0)">
      <input type="text" id='fetchTransactionId' name="id" size=10 maxlength=10 placeholder='Key #'/>
      <input type="submit" id='fetchTransactionSubmit' value="Get" onclick='getForm("#fetchTransaction", "scripts/getTransaction.php", setTransaction, null)'/>
    </form>
    <form class='inline' id='createTransaction' method="GET" action="javascript:void(0)">
      <input type='text' name='full_name' placeholder='Name'/>
      <input id='findForCreate' type='submit' value='Find' onClick='findToCreate("#createTransaction")'/>
    </form>
    <button id='newPersonTransaction' onClick='$("#newPerson").data("callback", createTransactionNewPerson); $("#newPerson").show();'>New Person</button>
    <form class='inline' id='peridTransaction' method='POST' action='javascript:void(0)'>
      <input type="text" id='fetchPerId' name="perid" size=10 maxlength=10 placeholder='Per Id'/>
      <input type="submit" id='peridSubmit' value="Get Perid" onclick='submitForm("#peridTransaction", "scripts/reg_start.php", setTransaction, null)'/>
    </form>
    <form id='transactionForm' method="POST" action="javascript:void(0)">
    <input type='hidden' id='transactionFormOwnerId'/>
      <table id='transactionFormTable'>
        <thead id='transactionFormId'>
          <tr>
            <td class='formlabel' colspan=2>Create: <span id="transactionFormIdCreate"></span></td>
            <td class='formlabel' colspan=2>Complete: <span id="transactionFormIdComplete"></span></td>
            <td/>
            <td class='formlabel'>Transaction# <span id="transactionFormIdNum"></span></td>

          </tr>
          <tr>
            <td id='transactionFormOwnerName' colspan=2></td>
            <td></td>
            <td id='transactionFormOwnerEmail' colspan=2></td>
            <td></td>
          </tr>
          <tr>
            <td id='transactionFormOwnerAddr' colspan=4></td>
            <td id='transactionFormOwnerManage' colspan=2>
              <button id='transactionFormOwnerEdit' onClick='editPerson("transactionFormOwner"); return false;'>Edit Person</button><br/>
              <button id='transactionFormOwnerBadgeRollover' onclick='processRollover($("#transactionFormOwnerBadgeId").val(), "rollover"); return false;' disabled>Rollover</button>
              <button id='transactionFormOwnerBadgeVolunteer' onclick='processRollover($("#transactionFormOwnerBadgeId").val(), "volunteer"); return false;' disabled>Volunteer RO</button>
            </td>
          </tr>
          <tr>
            <td class='formlabel' colspan=2>Badge Name</td>
            <td class='formlabel center'>paid/price</td>
            <td class='formlabel'>Badge Type</td>
            <td class='formlabel'></td>
            <td class='formlabel'>Cost</td>
          </tr>
          <tr id='transactionFormOwnerBadge'>
           <?php
           $badgeage_q = <<<EOS
SELECT CONCAT_WS('-', M.id, M.memCategory, M.memType, M.memAge) as type, M.label, M.price
FROM memLabel M
WHERE M.conid=? ORDER BY sort_order, memType, memAge ASC;
EOS;
           $badge_res=dbSafeQuery($badgeage_q, 'i', array($conid));
           $badges=array();
           while($row = fetch_safe_assoc($badge_res)) {
              $badges[count($badges)] = $row;
           }
            ?>
            <td id='transactionFormOwnerBadgeName' colspan=2> </td>
            <td id='transactionFormOwnerBadgePaidPrice' class='center'>
              <span id='transactionFormOwnerBadgePaid'></span>/
              <span id='transactionFormOwnerBadgePrice'></span>
            </td>
            <input type='hidden' id='transactionFormOwnerBadgeId' name='transactionFormOwnerBadgeId'/>
            <td>
              <select name='transactionFormOwnerBadgeType' id='transactionFormOwnerBadgeType'>
                <option default=true value='none'>None</option>
                <?php foreach($badges as $badge) {
                  echo "<option value=".$badge['type'].">".$badge['label']." ($".$badge['price'].")</option>";
                } ?>
              </select>
            </td>
            <td id='transactionFormOwnerBadgeButtons'>
              <button id='transactionFormOwnerBadgeSubmit' onclick='updateBadge("transactionForm", "Owner", "scripts/createBadge.php")'>Create</button>
            </td>
            <td id='transactionFormOwnerBadgeCost' class='rightText'></td>
          </tr>
          <tr>
            <td colspan=6><ul id='transactionFormOwnerBadgeAction'></ul> </td>
          </tr>
          <tr>
            <td colspan=6 id='transactionFormOwnerBadgeActionButtons'>
              <button class='right badgeAction' id='transactionFormOwnerBadgeNote' onClick='addBadgeNote("notes", $("#transactionFormOwnerBadgeId").val(), "transactionFormOwnerBadge"); return false;'>Add Note</button>
<?php /*
              <button class='right badgeAction' id='transactionFormOwnerBadgeVolunteer' onclick='addBadgeAddon("volunteer", $("#transactionFormOwnerBadgeId").val(), "transactionFormOwnerBadge", ""); return false;'>Volunteer</button>
              <button class='right badgeAction' id='transactionFormOwnerBadgeReprint' onClick='addBadgeNote("reprint", $("#transactionFormOwnerBadgeId").val(), "transactionFormOwnerBadge"); return false;'>Reprint</button>
              <button class='right badgeAction' id='transactionFormOwnerBadgeReturn' onClick='addBadgeNote("return", $("#transactionFormOwnerBadgeId").val(), "transactionFormOwnerBadge"); return false;'>Return</button>
              <button id='transactionFormOwnerBadgeUpgrade' class='badgeAction' onClick='addBadgeAddon("upgrade", $("#transactionFormOwnerBadgeId").val(), "transactionFormOwnerBadge", ""); return false;'>Upgrade <span id='ownerUpgradePrice'></span></button>
              <button id='transactionFormOwnerBadgeYearAhead' class='badgeAction' onClick='addBadgeAddon("yearahead", $("#transactionFormOwnerBadgeId").val(), "transactionFormOwnerBadge", ""); return false;'>YearAhead</button>
*/ ?>
            </td>
          </tr>
        </thead>

        <tbody id="transactionFormAdd">
        <tr>
          <td colspan=3>
            <input type='text' id='addFullName' name='transactionFormAddFullName' placeholder='Name'/>
            <input type='submit' id='addFullNameSubmit' value='Find' onClick='findToAppend()'/>
          </td>
          <td>
          <button id='newPersonShow' onClick='$("#newPerson").data("callback", appendNewPerson); $("#newPerson").show();'>New Person</button>
          </td>
          <td colspan=2>Current Cost: $<span id='transactionFormCurrentCost'></span></td>
        </tr>
        </tbody>
        <tbody id="transactionFormPayments" class='noborder'>
        <tr>
          <th class='formlabel'>Type</th>
          <th class='formlabel' colspan=3>Description/Transaction Id</th>
          <th class='formlabel'>Amount</th>
          <th class='formlabel'></th>
        </tr>
        </tbody>
        <tfoot id="transactionFormButtons">
        <tr>
          <td>
            <button class='payment' disabled='disabled' onClick='takePayment("cash")'>Cash</button><br/>
            <button class='payment' disabled='disabled' onClick='takePayment("check")'>Check</button><br/>
            <button class='payment' disabled='disabled' onClick='takePayment("credit")'>Credit Card</button>
            <button class='payment' disabled='disabled' onClick='takePayment("discount")'>Discount</button>
          </td>
          <td colspan=4 class='rightText'>
            Price: $<span id='transactionFormTotalPrice'></span> -
            Paid: $<span id='transactionFormTotalPaid'></span> =
            Remaining: $<span id='transactionFormTotal'></span>
          </td>
        </tr>
        <tr>
          <td colspan=6>
            <span class='right'>
              <input type=reset value='New Transaction' onClick='location.reload(); return false;'/>
            </span>
            <input type='submit' id='transactionFormSubmit' value='Complete Transaction' onClick='completeTransaction("transactionForm")' disabled=true class='disable'/>
          </td>
        </tr>
        </tfoot>
      </table>
    </form>
  </div>

</div>
<pre id='test'></pre>
<div id='alert' class='popup'>
    <div id='alertInner'>
    </div>
    <button class='center' onclick='$("#alert").hide();'>Close</button>
</div>
