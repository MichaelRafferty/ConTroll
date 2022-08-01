<?php
require_once "lib/base.php";
//initialize google session
$need_login = google_init("page");

$page = "art_control";
if(!$need_login or !checkAuth($need_login['sub'], $page)) {
    bounce_page("index.php");
}

page_init($page,
    /* css */ array('css/base.css',
                    'css/table.css',
                    'css/art_control.css'
                   ),
    /* js  */ array('js/d3.js',
                    'js/base.js',
                    'js/table.js',
                    'js/art_control.js'
                   ),
              $need_login);

$con = get_con();
$conid = $con['id'];

$conf = get_conf('con');
?>
  <script>
  $(function () {
  $('#auction').dialog({
    title: "Process Item",
    autoOpen: false,
    width: 600,
    height: 400,
    modal: true,
  })
  $('#newItem').dialog({
    title: "New Item",
    autoOpen: false,
    width: 500,
    height: 300,
    modal: true,
  });
  });
  </script>
  <style>
    .ui-dialog { padding: .3em; }
  </style>
<div id='auction' class='dialog'>
  <form id='auctionArt' action='javascript:void(0);'>
    Perid: <input required='required' id='auctionPerid' name='perid' type='text' size=7></input>
    <button onClick='fetchPerson("#auctionPerid", "#auctionWinner")'>Check Person</button>

    <div id='auctionWinner' class='boxed'>
    </div>
    Artist: <input required='required' id='auctionArtist' type='text' name='art_key' size=4></input>
    Item: <input required='required' id='auctionItem' type='text' name='item_key' size=4></input>
    <button onClick='fetchArt("#auctionArtist", "#auctionItem", "#auctionArtItem")'>Check Art</button>
    <div id='auctionArtItem' class='boxed'>
    </div>
    Price: <input required='required' id='auctionPrice' name='price' type='text'></input>
    <br/>
    <input type='submit' onClick='testValid("#auctionArt") && purchase("#auctionArt")' value='Buy'></input>
    <label><input id='auctionArtDone' type='checkbox' name='toReg'>Go to Register</input></label>
  </form>
</div>
<div id='newItem' class='dialog'>
    <form id='newArtForm' action='javascript:void(0);'>
      Artist: <select id='newItemArtistList' name='artist'>
      </select><br/>
      Type: <select name='type'>
        <option>art</option>
        <option>nfs</option>
        <option>print</option>
      </select><br/>
      <span id='artPriceWarn'>Quick Sale price should be higher than Art price</span>
      <table class='inline'>
        <tr><td class='formlabel'>Title</td></tr>
        <tr><td class='formfield'><input id='newItemTitle' name='title' required='required' type='text'></input></td></tr>
      </table>
      <table class='inline'>
        <tr><td class='formlabel'>Min Bid (USD)<br/>Ins Amnt</td></tr>
        <tr><td class='formfield'><input name='price' type='text' size=5></input></td></tr>
      </table>
      <table class='inline'>
        <tr><td class='formlabel'>Quicksale<br/>Print Shop</td></tr>
        <tr><td class='formfield'><input name='qsale' type='text' size=5></input></td></tr>
      </table>
      <table class='inline'>
        <tr><td class='formlabel'>Quantity</td></tr>
        <tr><td class='formfield'><input name='qty' type='text' size=5></input></td></tr>
      </table>
      <br/>
      <input type='submit' value='Add' onClick="addItem('#newArt', false);"></input>
    </form>
    <div id='newItemLog'></div>
</div>

<button onClick='$("#newItem").dialog("open");'>New Item</button>
<label><input type='checkbox' id='lockUpdate' checked='checked'/>Lock Update</label>
<button onClick='updateChanged()'>Update Changed</button>
<button onClick='redraw("#grid")'>Reset</button>
<a href="reports/artInventory.php">Inventory Report</a>

<div id='main'>
    <span class='half' id='facets'>
    </span>
    <span class='half' id='table'>
        <div id='gridFilter'>
            <span id='gridSelectWrap' class='right'>
                <span id='gridSelect'></span>
                <button onclick='clearSelect("#grid")'>Clear</button>
                <button onclick='invSelect("#grid")'>Invert</button>
                <button onclick='addFilter("#grid")'>Filter</button>
            </span>
        </div>
        <div id='gridCtrl'>
            Item
            <input type='number' id='gridStart' min=0 step=1 value=0 />
            Of <span id='gridVis'></span> (<span id='gridMax'></span>)
            <button onClick='redraw("#grid")'>Go</button>
            <span class='right'>
                Page Size
                <select id='gridSize'>
                    <option>10</option>
                    <option selected='selected'>25</option>
                    <option>50</option>
                    <option>100</option>
                </select>
                <button onClick='firstPage("#grid")'>First</button>
                <button onClick='prevPage("#grid")'>Prev</button>
                <button onClick='nextPage("#grid")'>Next</button>
                <button onClick='lastPage("#grid")'>Last</button>
            </span>
        </div>
        <table id='grid'>
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Artist<br/>No.</th>
                    <th>Piece<br/>No.</th>
                    <th>Type</th>
                    <th>Title</th>
                    <th>Min Bid<br/>or Ins.</th>
                    <th>Q. Sale<br/>or Print</th>
                    <th>Orig<br/>Qty</th>
                    <th>Curr<br/>Qty</th>
                    <th>Status</th>
                    <th>Location</th>
                    <th>Sold To</th>
                    <th>Sale Price</th>
                    <th>Update</th>
                </tr>
            </thead>
            <tbody id='gridBody'>
            </tbody>
        </table>
    </span>
</div>
<pre id='test'>
</pre>
<?php
page_foot($page);
?>
