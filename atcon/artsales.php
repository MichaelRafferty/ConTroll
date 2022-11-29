<?php
require_once "lib/base.php";
//initialize google session
$page = "art_sales";

if (!isset($_SESSION['user'])) {
    header("Location: /index.php");
    exit(0);
}

page_init($page, 'artshow',
     /* css */ array('css/atcon.css'),
    /* js  */ array('js/artsales.js')
    );

$con = get_conf("con");
$conid=$con['id'];
$method='artshow';

?>
<script>
$(function() {
    $('#finalDialog').dialog({
        autoOpen: false,
        width: 300,
        height: 600,
        modal: true,
        title: "Confirm Transaction"
    });
});
$(function() {
    $("#initialDialog").dialog({
        autoOpen: true,
        width: 400,
        height: 350,
        modal: true,
        title: "New Customer"
    });
});

<?php
if(isset($_GET['id'])) {
    $id= $_GET['id'];
?>
    $(document).ready(function() {
        $('#fetchUserId').val(<?php echo $id;?>)
        $('#fetchUserSubmit').click();
    });
<?php
}
?>
</script>
<div id='finalDialog'>
    <div id='transactionInfo'>
        <table>
            <tr><th class='righttext'>Subtotal: </th><td>$
                <span id='receiptSubtotal'></span></td></tr>
            <tr><th class='righttext'>Tax: </th><td>$
                <span id='receiptTax'></span></td></tr>
            <tr><th class='righttext'>Total: </th><td>$
                <span id='receiptTotal'></span></td></tr>

            <tr><th class='righttext'>Paid: </th><td>$
                <span id='receiptPaid'></span></td></tr>
            <tr><th class='righttext'>Change</th><td>$
                <span id='receiptChange'></span></td></tr>
        </table>
    </div>
    <div>
        <span class='blocktitle'>Purchased Art</span>
    </div>
    <div id='finalContainer'>
    </div>
</div>
<div id='initialDialog'>
    <form class='inline' id='fetchUser' method='GET' action='javascript:void(0)'>
      Member Id # <input type='text' id='fetchUserId' name='perid' size=10 
                         maxlength=10 placeholder='Badge #'/>
      <input type='submit' class='bigButton' id='fetchUserSubmit' value='Get User'
        onclick='getForm("#fetchUser", "scripts/artMember.php", setUser, null)'/>
    </form><br/>
    <hr/>
    <button id='anonMember' class='bigButton'
        onclick='fetchAnon();'>Non-member Purchase
    </button>
    <hr/>
    <?php passwdForm(); ?>
</div>
<?php paymentDialogs(); ?>
<div id='main'>
  <div id='userDiv'><span class='blocktitle'>
    Customer : <span id='userName'></span> <span id='userBadge'></span> 
    # <span id='userPerid'></span>
  </span></div>
  <form class='inline' id='getItem' action='javascript:void(0)'>
    Artist #: <input type='number' id='artArtist' name='artist' 
                   placeholder='Artist #'/>
    Item #: <input type='number' id='artItem' name='Item' 
                 placeholder='Item #'/>
    <input type='submit' value='Get Item' onclick='getItem()'/>
  </form>
  <table id='cart' class='outerborder'>
    <thead><tr>
        <th>Title</th>
        <th>Artist</th>
        <th>Type</th>
        <th>Price Ea.</th>
        <th>Quantity</th>
        <th>Total/Bid</th>
        <th>Leaving<br/>Show</th>
        <th>Delete</th>
    </tr></thead>
    <tbody id='cartList'>
        <tr>
        </tr>
    </tbody>
    <tfoot id='transValues' class='outerborder'>
        <tr>
            <th colspan=5 class='righttext'>Total</th>
            <td>$<span id='transTotal'>0.00</span></td>
        </tr>
        <tr>
            <th colspan=5 class='righttext'>Paid</th>
            <td>$<span id='transPaid'>0.00</span></td>
        </tr>
        <tr>
            <th colspan=5 class='righttext'>Remainder/Change</th>
            <td>$<span id='transChange'>0.00</span></td>
        </tr>
    </tfoot>
    <tfoot id='cartValues' class='outerborder'>
        <tr>
            <th colspan=5 class='righttext'>SubTotal</th>
            <td>$<span id='cartSubtotal'>0</span>.00</td>
        </tr>
        <tr>
            <th colspan=5 class='righttext'>6% Tax</th>
            <td>$<span id='cartTax'>0.00</span></td>
        </tr>
        <tr>
            <th colspan=5 class='righttext'>Total</th>
            <td>$<span id='cartTotal'>0.00</span></td>
        </tr>
    </tfoot>
    <tfoot id='paymentMethods'><tr><td colspan=7>
        <button class='payment bigButton' disabled='disabled' onClick='takePayment("cash")'>Cash</button>
        <button class='payment bigButton' disabled='disabled' onClick='takePayment("check")'>Check</button>
        <button class='payment bigButton' disabled='disabled' onClick='takePayment("credit")'>Credit Card</button>
        <?php /*    <button class='payment' disabled='disabled' onClick='takePayment("discount")'>Discount</button> */ ?>
    </td></tr></tfoot>
  </table>
  <button id='completeArtSale' class='bigButton' 
          onclick='completeArtSale()'>Complete Transaction
  </button>
  <button id='resetArtSale' class='bigButton' 
          onclick='window.location.href=window.location.pathname'>Reset Transaction
  </button>
  <button id='logout' class='bigButton' 
          onclick='window.location.href=window.location.pathname+"?action=logout"'>
          Logout
  </button>
</div>
<pre id='test'></pre>