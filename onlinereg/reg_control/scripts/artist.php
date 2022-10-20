<?php
require_once "lib/base.php";
//initialize google session
$need_login = google_init("page");

$page = "artist";
if(!$need_login or !checkAuth($need_login['sub'], $page)) {
    bounce_page("index.php");
}

page_init($page,
    /* css */ array('css/base.css'
                   ),
    /* js  */ array('js/d3.js',
                    'js/base.js',
                    'js/artist.js'
                   ),
              $need_login);

$con = get_con();
$conid = $con['id'];


$conf = get_conf('con');

?>
<div id='main'>
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
      Name: <input type="text" name="full_name" id="findPersonFullName"/>
      <input type="submit" value="Find" onClick='findPerson("#findPerson")'/>
    </form>
  </div>
  <div id='request'><span class='blocktitle'>Artist Requests</span>
    <a class='showlink' id='requestShowLink' href='javascript:void(0)'
      onclick='showBlock("#request")'>(show)</a>
    <a class='hidelink' id='requestHideLink' href='javascript:void(0)'
      onclick='hideBlock("#request")'>(hide)</a>
    <div id='requestForm'>
        <?php
            $requestQ = "SELECT V.id, V.name, V.website, V.description"
                . ", V.email, V.addr, V.addr2, V.city, V.state, V.zip"
                . " FROM vendors as V LEFT JOIN artist as A on A.vendor=V.id WHERE V.request_artshow=true and A.id is null;";
            $requestR = dbQuery($requestQ);
            ?><table>
            <tr><th style='width: 10%'>Name</th><th style='width: 40%'>Description</th><th style='width:40%'>Info</th></tr>
            <?php
            while($request = fetch_safe_assoc($requestR)) {
                ?>
                <tr> 
                    <td><?php echo $request['name'];?><br/><br/>
                        <button onclick='approveArtist(<?php echo $request['id'];?>)'>Approve</button>
                    </td>
                    <td><p><?php echo $request['description'];?></p>
                        <a target="_blank" href="https://<?php echo $request['website'];?>">Artist Website</a>
                    </td>
                    <td>
                        <?php echo $request['email'];?><br/>
                        <?php echo $request['addr'];?><br/>
                        <?php echo $request['addr2'];?><br/>
                        <?php echo $request['city'];?>,
                        <?php echo $request['state'];?>,
                        <?php echo $request['zip'];?>
                    </td>
                </tr>
                <?php
            }
            ?></table><?php
        ?>
    </div>
  </div>
  <div id="artist"><span class="blocktitle">Artist Info</span>
    <a class='showlink' id='artistShowLink' href='javascript:void(0)'
      onclick='showBlock("#artist")'>(show)</a>
    <a class='hidelink' id='artistHideLink' href='javascript:void(0)'
      onclick='hideBlock("#artist")'>(hide)</a>
    <form id="artistForm" method="POST" action="javascript:void(0)">
      <input type='hidden' name='perid' id='perid'/>
      <input type='hidden' name='artid' id='artid'/>
      <input type='hidden' name='agentid' id='agentid'/>
      <input type='hidden' name='vendor' id='vendorid'/>
      <table class='formalign'>
        <thead id="artistFormId"> <tr>
            <td class='formlabel'>Artist ID# <span id="artistFormArtId"></span></td>
            <td class='formlabel'>Vendor ID# <span id="artistFormVendorId"></span></td>
            <td class='formlabel'>PerID# <span id="artistFormPerId"></span></td>
          <td></td>
        </tr></thead>
        <tbody id='artistInfo'>
          <tr>
            <td class='formlabel'>First Name</td>
            <td class='formlabel'>Middle Name</td>
            <td class='formlabel'>Last Name</td>
            <td class='formlabel'>Suffix</td>
          </tr>
          <tr>
            <td class='formfield'><input type='text' disabled='disabled' id='fname'/></td>
            <td class='formfield'><input type='text' disabled='disabled' id='mname'/></td>
            <td class='formfield'><input type='text' disabled='disabled' id='lname'/></td>
            <td class='formfield'><input type='text' disabled='disabled' id='suffix' size=4/></td>
          </tr>
          <tr>
            <td class='formlabel' colspan=2>Email Addr</td>
            <td class='formlabel'>Phone</td>
          </tr>
          <tr>
            <td class='formfield' colspan=2><input type='text' disabled id='email' size=40/></td>
            <td class='formfield'><input type='text' disabled id='phone'/></td>
          </tr>
        </tbody>
        <tbody>
          <tr><td colspan=5>Vendor Info
            <a class='showlink' id='vendorInfoShowLink' href='javascript:void(0)' onclick='showBlock("#vendorInfo");'>(show)</a>
            <a class='hidelink' id='vendorInfoHideLink' href='javascript:void(0)' onclick='hideBlock("#vendorInfo");'>(hide)</a>
          <span id='vendorProvisional'></span>
          </td></tr>
          <tr><td>Vendor Name</td><td colspan=4>
            <input name='vendor_name' id='vendor_name' type='text' size=40/>
          </td></tr>
          <tr><td>Website</td><td colspan=4>
            <input name='vendor_site' id='vendor_site' type='text' size=40/>
          </td></tr>
          <tr><td>Description</td><td colspan=4>
            <textarea name='vendor_desc' id='vendor_desc' rows=4 cols=40>
            </textarea>
          </td></tr>
          <tr><td colspan=5><button onclick='resetPw($("#vendorid").val());'>Reset Password</button>
        </tbody>
        <tbody>
          <tr><td colspan=5>Shipping Info
            <a class='showlink' id='shippingInfoShowLink' href='javascript:void(0)' onclick='showBlock("#shippingInfo");'>(show)</a>
            <a class='hidelink' id='shippingInfoHideLink' href='javascript:void(0)' onclick='hideBlock("#shippingInfo");'>(hide)</a>
          </td></tr>
        </tbody>
        <tbody id='shippingInfoForm'>
          <tr>
            <td class='formlabel' colspan=5>Address</td>
          </tr>
          <tr>
            <td class='formfield' colspan=5><input type='text' name='ship_addr' id='ship_addr' size=60/></td>
          </tr>
          <tr>
            <td class='formlabel' colspan=5>Company</td>
          </tr>
          <tr>
            <td class='formfield' colspan=5><input type='text' name='ship_addr2' id='ship_addr2' size=60/></td>
          </tr>
          <tr>
            <td class='formlabel'>City</td>
            <td class='formlabel'>State</td>
            <td class='formlabel'>Zip</td>
            <td class='formlabel'>Country</td>
          </tr>
          <tr>
            <td class='formfield'><input type='text' name='ship_city' id='ship_city'/></td>
            <td class='formfield'><input type='text' name='ship_state' id='ship_state' size=4/></td>
            <td class='formfield'><input type='text' name='ship_zip' id='ship_zip' size=10/></td>
            <td class='formfield'><select id='ship_country' name='ship_country' size=1 width=20>
              <?php
              $fh = fopen("lib/countryCodes.csv","r");
              while(($data = fgetcsv($fh, 1000, ',', '"'))!=false) {
                echo "<option value='".$data[1]."'>".$data[0]."</option>";
              }
              fclose($fh);
              ?>
            </select></td>
          </tr>
        </tbody>
        <tfoot>
        <tr>
          <td colspan=5>
            <input type='submit' value='Create/Update artist' onClick="updateArtist()"/>
            <input type='reset' onClick="getArtist($('#perid').val());"/>
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
