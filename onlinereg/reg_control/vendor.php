<?php
require_once "lib/base.php";
//initialize google session
$need_login = google_init("page");

$page = "vendor";
if(!$need_login or !checkAuth($need_login['sub'], $page)) {
    bounce_page("index.php");
}

page_init($page,
    /* css */ array('css/base.css'
                   ),
    /* js  */ array('/javascript/d3.js',
                    'js/base.js',
                    'js/vendor.js'
                   ),
              $need_login);

$con = get_con();
$conid = $con['id'];

$conf = get_conf('con');

?>
<div id='main'>
  <div id='currentNumbers' class='half'>
    <span class='blocktitle'>Artist Alley Registrations:</span>
    <?php
        $showQ = "SELECT type, sum(requested) as requested, sum(authorized) as authorized, sum(purchased) as purchased from vendor_show WHERE conid=$conid group by type;";
        $showR = dbQuery($showQ);
        while($showLine = fetch_safe_assoc($showR)) {
            switch($showLine['type']) {
                case 'alley': $alley_show = $showLine; break;
                case 'dealer_6': $dealer6_show = $showLine; break;
                case 'dealer_10': $dealer10_show = $showLine; break;
                case 'virtual': $virtual_show = $showLine; break;
            }
        }
    ?>
    New: <?php echo $alley_show['requested'] - $alley_show['authorized']; ?>
    Pending: <?php echo $alley_show['authorized'] - $alley_show['purchased']; ?>
    Purchased: <?php echo $alley_show['purchased']; ?>
    </br>
    <span class='blocktitle'>Dealers Room 6' Registrations:</span>
    New: <?php echo $dealer6_show['requested'] - $dealer6_show['authorized']; ?>
    Pending: <?php echo $dealer6_show['authorized'] - $dealer6_show['purchased']; ?>
    Purchased: <?php echo $dealer6_show['purchased']; ?>
    </br>
    <span class='blocktitle'>Dealers Room 10' Registrations:</span>
    New: <?php echo $dealer10_show['requested'] - $dealer10_show['authorized']; ?>
    Pending: <?php echo $dealer10_show['authorized'] - $dealer10_show['purchased']; ?>
    Purchased: <?php echo $dealer10_show['purchased']; ?>
    </br>
    <span class='blocktitle'>Virtual Vendor Registration:</span>
    New: <?php echo $virtual_show['requested']; ?> Purchased: <?php echo $virtual_show['purchased']; ?>
    <br/>
  </div>
  <div id='searchResults' class='half right'>
    <span class='blocktitle'>Search Results</span>
    <span id="resultCount"> </span>
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
      Name: <input type="text" name="full_name" id="findPersonFullName"></input>
      <input type="submit" value="Find" onClick='findPerson("#findPerson")'></input>
    </form>
  </div>
  <div id='vendorList'><span class='blocktitle'>Vendor List</span>
    <a class='showlink' id='vendorListShowLink' href='javascript:void(0)'
        onclick='showBlock("#vendorList")'>(show)</a>
    <a class='hidelink' id='vendorListHideLink' href='javascript:void(0)'
        onclick='hideBlock("#vendorList")'>(hide)</a>
    <table id='vendorListT'>
        <thead>
            <tr>
                <th>Vendor Name</th>
                <th>Vendor Website</th>
                <th>Vendor Email</th>
                <th>Dealer Info</th>
                <th>Alley Info</th>
                <th>Virtual</th>
                <th>View</th>
                <th>Password Reset</th>
            </tr>
        </thead>
                       
  <?php
    $vendorQ = "SELECT V.id, V.name, V.website, V.email"
            . ", request_dealer, request_artistalley, request_fanac, request_virtual"
            . ", SA.requested as A_req, SA.authorized as A_auth, SA.purchased as A_purch"
            . ", SD.requested as D_req, SD.authorized as D_auth, SD.purchased as D_purch"
            . ", SD10.requested as T_req, SD10.authorized as T_auth, SD10.purchased as T_purch"
            . ", SV.requested as V_req, SV.authorized as V_auth, SV.purchased as V_purch, SV.virtual_type as V_type"
            . " FROM vendors as V"
            . " LEFT JOIN vendor_show as SA on SA.vendor=V.id AND SA.type='alley' and SA.conid=$conid"
            . " LEFT JOIN vendor_show as SD on SD.vendor=V.id AND SD.type='dealer_6' and SD.conid=$conid"
            . " LEFT JOIN vendor_show as SD10 on SD10.vendor=V.id AND SD10.type='dealer_10' and SD10.conid=$conid"
            . " LEFT JOIN vendor_show as SV on SV.vendor=V.id AND SV.type='virtual' and SV.conid=$conid"
            . " WHERE request_dealer or request_artistalley or request_fanac or request_virtual"
            . ";";


    $vendorList = dbQuery($vendorQ);

    while($vendor = fetch_safe_assoc($vendorList)) {
        if($vendor['A_req']+$vendor['D_req']+$vendor['T_req']+$vendor['V_req']==0) continue;
  ?>
        <tr>
            <td><?php echo $vendor['name']; ?></td>
            <td><?php echo $vendor['website']; ?></td>
            <td><?php echo $vendor['email']; ?></td>
            <td><?php if($vendor['request_dealer']) {
                if($vendor['D_purch'] > 0) echo $vendor['D_purch'] . " 6'";
                else if ($vendor['D_auth'] > 0) echo $vendor['D_auth'] . " 6' authorized";
                else if ($vendor['T_purch'] > 0) echo $vendor['T_purch'] . " 10'";
                else if ($vendor['T_auth'] > 0) echo $vendor['T_auth'] . " 10' authorized";
                else if ($vendor['D_req'] > 0) echo "requested " . $vendor['D_req'];
                else { echo ""; }
                } else { echo "N/R"; }
            ?></td>
            <td><?php if($vendor['request_artistalley']) {
                if($vendor['A_purch'] > 0) echo $vendor['A_purch'];
                else if ($vendor['A_auth'] > 0) echo $vendor['A_auth'] . " authorized";
                else if ($vendor['A_req'] > 0 ) echo "requested " . $vendor['A_req'];
                else { echo ""; }
                } else { echo "N/R"; }
            ?></td>
            <td><?php if($vendor['request_virtual']) {
                if($vendor['V_purch'] > 0) echo $vendor['V_type'];
                else if ($vendor['V_auth'] > 0) echo $vendor['V_auth'] . " authorized";
                else { 
                    echo "requested " . $vendor['V_req'];
                }
                } else { echo "N/R"; }
            ?></td>
            <td><button onclick="authorize(<?php echo $vendor['id'];?>);">View</button></td>
            <td><button onclick="resetPw(<?php echo $vendor['id'];?>)">Reset PW</button></td>
        </tr>
  <?php } ?>
    </table>
  </div>
  <div id='vendorDetails'><span class="blocktitle">Vendor Details</span>
     <a class='showlink' id='artistShowLink' href='javascript:void(0)'
      onclick='showBlock("#vendorDetails")'>(show)</a>
    <a class='hidelink' id='artistHideLink' href='javascript:void(0)'
      onclick='hideBlock("#vendorDetails")'>(hide)</a>
    <form id='vendorUpdate' action='javascript:void(0)'>
        <input type='hidden' name='vendor' id='vendorId'/>
        Name: <input type='text' name='name' id='vendorName'/><br/>
        Website: <input type='text' name='website' id='vendorWebsite'/><br/>
        Description: <textarea name='description' id='vendorDesc'></textarea><br/>
        <table>
            <tr><th>Artist Alley</td><td>Requested</td><td>Authorized</td><td>Paid</td><td/></tr>
            <tr><td>Tables</td>
                <td><input type='number' name='alleyRequest' id='alleyRequest'/></td>
                <td><input type='number' name='alleyAuth' id='alleyAuth'/></td>
                <td><input type='number' name='alleyPurch' id='alleyPurch'/></td>
            </tr>
            <tr><th>Dealers</td><td>Requested</td><td>Authorized</td><td>Paid</td><td/></tr>
            <tr><td>6x6 spaces</td>
                <td><input type='number' name='dealerRequest' id='dealerRequest'/></td>
                <td><input type='number' name='dealerAuth' id='dealerAuth'/></td>
                <td><input type='number' name='dealerPurch' id='dealerPurch'/></td>
            </tr>
            <tr><td>10x10 spaces</td>
                <td><input type='number' name='d10Request' id='d10Request'/></td>
                <td><input type='number' name='d10Auth' id='d10Auth'/></td>
                <td><input type='number' name='d10Purch' id='d10Purch'/></td>
            </tr>
        </table>
    <button onclick='updateVendor();'>Update Vendor</button><br/>
    </form>
  </div>
</div>

</div>
<pre id='test'></pre>
<div id='alert' class='popup'>
    <div id='alertInner'>
    </div>
    <button class='center' onclick='$("#alert").hide();'>Close</button>
</div>
