<?php
require_once "lib/base.php";
//initialize google session
$need_login = google_init("page");

$page = "artshow";
if(!$need_login or !checkAuth($need_login['sub'], $page)) {
    bounce_page("index.php");
}

if(!isset($_GET) || !isset($_GET['id'])) {
?>
    <div>No Id Provided</div>
<?php
    exit();
}

$id= $_GET['id'];
$con = get_con();
$conid = $con['id'];
    
$conf = get_conf('con');

$artQ = <<<EOS
SELECT S.id, S.art_key, S.artid, V.name as art_name, concat_ws(' ', P.first_name, P.last_name) as name
FROM artshow S
JOIN artist A ON (A.id=S.artid)
JOIN perinfo P ON (P.id=A.artist)
JOIN vendors V ON (V.id=A.vendor)
WHERE conid=? AND artid=?;
EOS;

$artR = dbSafeQuery($artQ, 'ii', array($conid, $id));
$artist = fetch_safe_assoc($artR);


function getInfo($pin) {
  global $con;
  $artshowQ = "SELECT * FROM artshow WHERE id=? AND conid=?;";
  $artshowR = dbSafeQuery($artshowQ, 'ii', array($pin, $id));
  $artshowInfo = fetch_safe_assoc($artshowR);
  $perid = $artshowInfo['perid'];
  $artid = $artshowInfo['artid'];
  $perQ = "SELECT * from perinfo where id=$perid;";
  $artistQ = "SELECT * from artist where id=$artid;";
  $per = fetch_safe_assoc(dbQuery($perQ));
  $artist = fetch_safe_assoc(dbQuery($artistQ));
  if($artist['agent_perid'] != '') {
    $agentId = $artist['agent_perid'];
    $agentQ = "SELECT * from perinfo where id=$agentId;";
    $agent = fetch_safe_assoc(dbQuery($agentQ));
  } else { $agent = null; }


  return array(
    'artshow' => $artshowInfo,
    'per' => $per,
    'artist' => $artist,
    'agent' => $agent
  );
}

function getArtwork($id) {
    $artQ = <<<EOS
SELECT I.item_key, I.title, I.type, I.status, I.material, I.original_qty, I.quantity, I.min_price, I.sale_price
    I.final_price, concat_ws(' ', P.first_name, P.last_name) as name, P.email_addr
FROM artItems as I
LEFT JOIN perinfo P (ON P.id=I.bidder)
WHERE artshow=?;
EOS;
    #print($artQ);
    $artR = dbSafeQuery($artQ, 'i', array($id));
    $art = array();
    while($item = fetch_safe_assoc($artR)) {
        array_push($art, $item);
    }
    return $art;
}


?>
<!DOCTYPE html>
<html lang="en">
<head>
<title><?php echo $artist['art_name']; ?> -- Control Sheet</title>
    <link href='css/base.css' rel='stylesheet' type='text/css' />
    <link href='css/showControl.css' rel='stylesheet' type='text/css' />

  <script type='text/javascript' src='/javascript/jquery-min-3.60.js''></script>
  <script type='text/javascript' src='/javascript/d3.js'></script>
  <script type='text/javascript' src='js/base.js'></script>
</head>
<body>
<h2> Art Control Sheet for <?php echo $artist['art_name']; ?></h2>
<h4>Artist & Agent Information</h4>
<?php $info = getInfo($artist['id']); 
#var_dump($info); 
?>
  <p>Artist Number: <?php echo $info['artshow']['art_key']; ?><br/>
  <table class='noborder'>
    <tr>
      <td>Artist Name: <span id='artistName'> <?php
        echo $info['per']['first_name'] . " " .
            $info['per']['middle_name'] . " " .
            $info['per']['last_name'] . " " .
            $info['per']['suffix'];
      ?></span></td>
      <td>Email: <span id='artistEmail'><?php
        echo $info['per']['email_addr'];
      ?></span></td>
      <td>Phone: <span id='artistPhone'><?php
        echo $info['per']['phone'];
      ?></span></td>
    </tr>
    <tr>
      <td>Trade Name: <span id='tradeName'><?php
        echo $info['artist']['art_name'];
      ?></span></td>
      <td>Checks To: <span id='checksTo'><?php
        switch($info['artist']['checks_to']) {
           case 'other':
             echo $info['artist']['checks_pay_to'];
             break;
           default: echo $info['artist']['checks_to'];
        }
      ?></span></td>
      <td>Professional? <span id='artistPro'><?php
        echo $info['artist']['pro'];
      ?></span></td>
    </tr>
    <tr>
      <td>Company:<td colspan=2><span id='artistAddr2'><?php
        echo $info['per']['addr_2']
      ?></span></td>
    </tr>
    <tr>
      <td>Address:<td colspan=2><span id='artistAddr'><?php
        echo $info['per']['address']
      ?></span></td>
    </tr>
    <tr>
      <td>City, State:<td colspan=2><span id='artistCity'><?php
        echo $info['per']['city'];
      ?></span>, <span id='artistState'><?php
        echo $info['per']['state'];
      ?></span></td>
    </tr>
    <tr>
      <td>Zip, Country:<td colspan=2><span id='artistZip'><?php
        echo $info['per']['zip'];
      ?></span>, <span id='artistCountry'><?php
        echo $info['per']['country']
      ?></span></td>
    </tr>
<?php
  if($info['agent'] != null) {
  ?>
    <tr>
      <td>Agent Name: <span id='agentName'> <?php
        echo $info['agent']['first_name'] . " " .
            $info['agent']['middle_name'] . " " .
            $info['agent']['last_name'] . " " .
            $info['agent']['suffix'];
      ?></span></td>
      <td>Email: <span id='agentEmail'><?php
        echo $info['agent']['email_addr'];
      ?></span></td>
      <td>Phone: <span id='agentPhone'><?php
        echo $info['agent']['phone'];
      ?></span></td>
    </tr>
    <tr>
      <td>Company:<td colspan=2><span id='agentAddr2'><?php
        echo $info['agent']['addr_2']
      ?></span></td>
    </tr>
    <tr>
      <td>Address:<td colspan=2><span id='agentAddr'><?php
        echo $info['agent']['address']
      ?></span></td>
    </tr>
    <tr>
      <td>City, State:<td colspan=2><span id='agentCity'><?php
        echo $info['agent']['city'];
      ?></span>, <span id='agentState'><?php
        echo $info['agent']['state'];
      ?></span></td>
    </tr>
    <tr>
      <td>Zip, Country:<td colspan=2><span id='agentZip'><?php
        echo $info['agent']['zip'];
      ?></span>, <span id='agentCountry'><?php
        echo $info['agent']['country']
      ?></span></td>
    </tr>
  <?php
  } else {
  ?>
    <tr><td colspan=3>Agent Name: <span class='warn'>No Agent</span></td></tr>
  <?php
  }
?>
  </table>
  <h4>Contact/Shipping Information</h4>
Blank unless information differs from above.
  <table>
  <tr><td>Contact Info:</td></tr>
  <tbody>
  <td>Cell Phone:</td><td><?php echo $info['artist']['other_cell']; ?></td></tr>
  <td>Other Phone:</td><td><?php echo $info['artist']['other_phone']; ?></td></tr>
  <td>Email:</td><td><?php echo $info['artist']['other_email']; ?></td></tr>
  </tbody>
  <tr><td>Shipping Info:</td><td>
    Ship To: <?php $info['artist']['ship_to']; ?>
  </td></tr>
  <tbody id='shippinginfo'>
    <tr>
      <td>Company:</td><td><?php echo $info['artist']['other_addr2']?></td>
    </tr>
    <tr>
      <td>Address:</td><td><?php echo $info['artist']['other_addr1'] ?></td>
    </tr>
    <tr>
      <td>City, State:</td><td><?php echo $info['artist']['other_city']; ?>, <?php echo $info['artist']['other_state']; ?></td>
    </tr>
    <tr>
      <td>Zip, Country:</td><td><?php echo $info['artist']['other_zip']; ?>, <?php $info['artist']['other_country']; ?></td>
    </tr>
  </tbody>
  </table>

<h4>Artwork</h4>
  <table>
    <thead><tr><th>Piece<br/>No.</th><th>Title</th><th>type</th><th>Material</th>
      <th>Min bid or<br/>Ins. Value</th>
      <th>Quick Sale or <br/>Print Price</th>
      <th>Orig. Qty</th><th>Curr. Qty</th>
      <th>Location</th><th>Status</th><th>Winning<br/>Bid</th>
      <th>Bidder</th><th>Bidder Email</th></tr></thead >
    <tbody id='itemList' class='long'>
    <?php $artwork = getArtwork($artist['id']); 
    #var_dump($artwork);
    $total = 0;
    foreach ($artwork as $item) {
        switch($item['type']) {
            case 'art':
                if($item['status'] == 'sold') { $total += $item['final_price']; }
                break;
            case 'print':
                $sold = $item['original_qty'] - $item['quantity'];
                $total += ($sold * $item['sale_price']);
        }
        ?>
        <tr>
            <td><?php echo $item['item_key']; ?></td>
            <td><?php echo $item['title']; ?></td>
            <td><?php echo $item['type']; ?></td>
            <td><?php echo $item['material']; ?></td>
            <td><?php echo $item['min_price']; ?></td>
            <td><?php echo $item['sale_price']; ?></td>
            <td><?php echo $item['original_qty']; ?></td>
            <td><?php echo $item['quantity']; ?></td>
            <td><?php echo $item['location']; ?></td>
            <td><?php echo $item['status']; ?></td>
            <td><?php echo $item['final_price']; ?></td>
            <td><?php echo $item['name']; ?></td>
            <td><?php echo $item['email_addr']; ?></td>
        </tr>
        <?php
    }
    ?>
    </tbody>
  </table>
    Artshow Total Sold: $<?php echo $total; ?>
    <h3>Artshow Checkout is between 3:00 PM and 5:00 PM Sunday Evening</h3>
</body>
</html>
