<?php
require_once "lib/base.php";

$page = "Register";

page_init($page, 'cashier',
    /* css */ array('css/registration.css','css/atcon.css'),
    /* js  */ array('js/atcon.js')
    );

$con = get_conf("con");
$conid=$con['id'];
$method='cashier';

if(!isset($_SESSION['user']) || 
  !check_atcon($_SESSION['user'], $_SESSION['passwd'], $method, $conid)) {
    if(isset($_POST['user']) && isset($_POST['passwd']) && isset($_POST['printer']) &&
      check_atcon($_POST['user'], $_POST['passwd'], $method, $conid)) {
        $_SESSION['user']=$_POST['user'];
        $_SESSION['passwd']=$_POST['passwd'];
        $_SESSION['printer']=$_POST['printer'];
	//$_SESSION['username'] = get_username($_SESSION['user']);
    } else {
        unset($_SESSION['user']);
        unset($_SESSION['passwd']);
    }
}

if(isset($_GET['action']) && $_GET['action']=='logout') {
    unset($_SESSION['user']);
    unset($_SESSION['passwd']);
    echo "<script>window.location.href=window.location.pathname</script>";
}

if(!isset($_SESSION['user'])) {
?>
<form method='POST'>
User Badge Id: <input type='text' name='user'/><br/>
Password: <input type='password' name='passwd'/><br/>
Badge Printer: <input type='number' name='printer'/><br/>
<input type='submit' value='Login'/>
</form>
<?php

} else {

?>
<script>
$(function() {
    $('#finalDialog').dialog({
        autoOpen: false,
        width: 300,
        height: 400,
        modal: true,
        title: "Confirm Transaction"
    });
});
$(function() {
    $("#initialDialog").dialog({
        autoOpen: true,
        width: 400,
        height: 400,
        modal: true,
        title: "New Transaction"
    });
});
$(function() {
    $('#editDialog').dialog({
        autoOpen: false,
        width: 800,
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
<div id='finalDialog'>
    <div>
        <span class='blocktitle'>Transaction #
            <span id='finalTransid'></span>
        </span>
    </div>
    <div id='finalContainer'>
      <div id='printableHead'>
        <div class='center' onclick='$("#printable").toggle()'>
            <button onclick='checkReceipt($("#finalTransid").text())'>Print Receipt</button><br />
            === PRINTABLE ===
        </div>
        <div id='printable'>
        </div>
            <button onclick='printBadge("#printable", "all")'>Print All</button>
            <button onclick='printBadge("#printable", "selected")'>Print Selected</button>
      </div>
      <div id='newBadgesHead'>
        <div class='center' onclick='$("#newBadges").toggle()'>
            === New Badges : Total $<span id='newBadgesTotal'></span> ===
        </div>
        <div id='newBadges'>
        </div>
      </div>
      <div id='oldBadgesHead'>
        <div class='center' onclick='$("#oldBadges").toggle()'>
            === Already Printed Badges ===
        </div>
        <div id='oldBadges'>
        </div>
        <button onclick='printBadge("#oldBadges", "selected")'>Reprint Selected</button>
      </div>
    </div>
    <div id='completeError'>
    </div>
    <div id='finalButtons'>
        <button onClick='window.location.href=window.location.pathname'>New Transaction</button>
    </div>
</div>
<div id='initialDialog'>
    <form class='inline' id='fetchTransaction' method="GET" action="javascript:void(0)">
      Transaction #: <input type="text" id='fetchTransactionId' name="id" size=10 maxlength=10 placeholder='Key #'/>
      <input type="submit" class='bigButton' id='fetchTransactionSubmit' value="Get Transaction" onclick='getForm("#fetchTransaction", "scripts/getTransaction.php", setTransaction, null)'/>
    </form><br/>
<form class='inline' action='javascript:void(0)' id='createTransaction' method='GET'>
    Search by Name: <input type='text' name='full_name' id='init_full' placeholder='Name'/><br/>
    <input type='submit' value='Search User' onClick='findToCreate("#createTransaction")'/>
</form>
    <button id='newPersonTransaction' onClick='$("#newPerson").data("callback", createTransactionNewPerson); $("#newPerson").show(); $("#initialDialog").dialog("close")'>New Person</button>
    <br/>
    <form class='inline' id='peridTransaction' method='POST' action='javascript:void(0)'>
      Lookup Badge: <input type="text" id='fetchPerId' name="perid" size=10 maxlength=10 placeholder='Per Id'/>
      <input type="submit" id='peridSubmit' value="Get Badge" onclick='submitForm("#peridTransaction", "scripts/reg_start.php", setTransaction, null)'/>
    </form>
    <br/>
<?php passwdForm(); ?>
</div>
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
<div id='newPerson' class='popup'>
  <form id='newPersonForm' action='javascript:void(0)'>
  <input type='hidden' id='newID' name='newID' value=''/>
  <input type='hidden' id='oldID' name='oldID' value=''/>
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
          <input tabindex=11 type='text' size=2 name='state' id='state' required='required'/> /
          <input tabindex=12 type='text' name='zip' id='zip' size=5 required='required'/>
        </td>
        <td colspan=2>
          <select tabindex=13 id='country' name='country' size=1 width=20>
            <option value='USA' default=true>United States</option>
            <option value='CAN'>Canada</option>
            <?php
            $fh = fopen("lib/countryCodes.csv","r");
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
            onClick='testValid("#newPersonForm") && submitForm("#newPersonForm", "scripts/editPersonFromConflict.php", updatePersonCatch, null); return false'>Update</button>
          <button type='reset' id='newPersonClose' onClick='$("#newPerson").hide(); return true;'>Close</button>
      </tr>
    </tfoot>
    </table>
  </form>
</div>
<?php paymentDialogs(); ?>
<div id='userInfo'>
User: <?php echo $_SESSION['user']; ?>  
Printer: <?php echo $_SESSION['printer']; ?>
</div>
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
    <form id='transactionForm' method="POST" action="javascript:void(0)">
    <input type='hidden' id='transactionFormOwnerId'/>
    <input type='hidden' id='transactionFormOwnerBadgeId' name='transactionFormOwnerBadgeId'/>
      <table id='transactionFormTable'>
        <thead id='transactionFormId'>
          <tr>
            <td class='formlabel' colspan=3>Create: <span id="transactionFormIdCreate"></span></td>
            <td class='formlabel' colspan=1>Complete: <span id="transactionFormIdComplete"></span></td>
            <td/>
            <td class='formlabel'>Transaction# <span id="transactionFormIdNum"></span></td>

          </tr>
          <tr id='transactionFormOwner'>
            <td id='transactionFormOwnerName' colspan=2></td>
            <td></td>
            <td id='transactionFormOwnerEmail' colspan=2></td>
            <td></td>
          </tr>
          <tr>
            <td id='transactionFormOwnerAddr' colspan=4></td>
            <td id='transactionFormOwnerManage' colspan=2>
              <button id='transactionFormOwnerEdit' onClick='editPerson("transactionFormOwner"); return false;'>Edit Person</button><br/>
              <button class='badgeAction' id='transactionFormOwnerBadgeRollover' onclick='addBadgeAddon("rollover", $("#transactionFormOwnerBadgeId").val(), "transactionFormOwner", "", true); return false;'>Rollover</button>
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
            $prices = json_decode(callHome("prices.php", "GET", ""), true);
            $badges=$prices['badges'];
            ?>
            <td id='transactionFormOwnerBadgeName' colspan=2> </td>
            <td id='transactionFormOwnerBadgePaidPrice' class='center'>
              <span id='transactionFormOwnerBadgePaid'></span>/
              <span id='transactionFormOwnerBadgePrice'></span>
            </td>
           
            <td>
              <select name='transactionFormOwnerBadgeType' id='transactionFormOwnerBadgeType'>
                <option default=true value='none'>None</option>
                <?php foreach($badges as $badge) {
                  echo "<option value=".$badge['type'].">".$badge['label']." ($".$badge['price'].")</option>";
                } ?>
              </select>
            </td>
            <td id='transactionFormOwnerBadgeButtons'>
              <button id='transactionFormOwnerBadgeSubmit' onclick='updateBadge("transactionForm", "Owner", "createBadge")'>Create</button>
            </td>
            <td id='transactionFormOwnerBadgeCost' class='rightText'></td>
          </tr>
          <tr>
            <td colspan=6><ul id='transactionFormOwnerBadgeAction'></ul> </td>
          </tr>
          <tr>
            <td colspan=6 id='transactionFormOwnerBadgeActionButtons'>
              <button class='right badgeAction' id='transactionFormOwnerBadgeNote' onClick='addBadgeNote("notes", $("#transactionFormOwnerBadgeId").val(), "transactionFormOwnerBadge"); return false;' disabled>Add Note</button>
              <button class='right badgeAction' id='transactionFormOwnerBadgeVolunteer' onclick='addBadgeAddon("volunteer", $("#transactionFormOwnerBadgeId").val(), "transactionFormOwner", "", true); return false;' disabled>Volunteer</button>            
              <button id='transactionFormOwnerBadgeYearAhead' class='badgeAction' onClick='addBadgeAddon("yearahead", $("#transactionFormOwnerBadgeId").val(), "transactionFormOwner", "", true); return false;' disabled>YearAhead</button>
            </td>
          </tr>
        </thead>

        <tbody id="transactionFormAdd">
        <tr>
          <td colspan=3>
            <input type='submit' class='bigButton' id='addFullNameSubmit' value='Find' onClick='findToAppend()'/>
          </td>
          <td>
          <button id='newPersonShow' class='bigButton' onClick='$("#newPerson").data("callback", appendNewPerson); $("#newPerson").show();'>New Person</button>
          </td>
          <td colspan=2>Current Cost: $<span id='transactionFormCurrentCost'></span></td>
        </tr>
        </tbody>
        <tbody id='actionTableBody'>
             <tr><th colspan=3>name</th><th>action</th><th>age</th><th>price</th></td></tr>
        </tbody>
        <tbody id="transactionFormPayments" class='noborder'>
        <tr>
          <th class='formlabel'>Type</td>
          <th class='formlabel' colspan=3>Description/Transaction Id</td>
          <th class='formlabel'>Amount</td>
          <th class='formlabel'></th>
        </tr>
        </tbody>
        <tfoot id="transactionFormButtons">
        <tr>
          <td colspan=3>
            <button class='payment bigButton' disabled='disabled' onClick='takePayment("cash")'>Cash</button>
            <button class='payment bigButton' disabled='disabled' onClick='takePayment("check")'>Check</button>
            <button class='payment bigButton' disabled='disabled' onClick='takePayment("credit")'>Credit Card</button>
<?php /*            <button class='payment' disabled='disabled' onClick='takePayment("discount")'>Discount</button> */ ?>
          </td>
          <td colspan=2 class='rightText'>
            Cost: $<span id='transactionFormTotalPrice'></span> -
            Paid: $<span id='transactionFormTotalPaid'></span> =
            Remaining: $<span id='transactionFormTotal'></span>
          </td>
        </tr>
        <tr>
          <td colspan=6>
            <span class='right'>
              <input type=reset class='bigButton' value='New Transaction' onClick='window.location.href=window.location.pathname'/>
              <input type=reset class='bigButton' value='Logout' onClick='window.location.href=window.location.pathname+"?action=logout"'/>
            </span>
            <input type='submit' class='bigButton disabled' id='transactionFormSubmit' value='Complete Transaction' onClick='completeTransaction("transactionForm")' disabled/>
          </td>
        </tr>
        </tfoot>
      </table>
    </form>
  </div>

</div>
<?php
}
?>
<pre id='test'></pre>
<div id='alert' class='popup'>
    <div id='alertInner'>
    </div>
    <button class='center' onclick='$("#alert").hide();'>Close</button>
</div>
