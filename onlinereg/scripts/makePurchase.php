<?php
require_once(__DIR__ . "/../../lib/db_functions.php");
require_once(__DIR__ . "/../../lib/ajax_functions.php");
require_once "../lib/log.php";
require_once("../../lib/cc__load_methods.php");
require_once("../../lib/email__load_methods.php");
require_once "../lib/email.php";

if(!isset($_POST) || !isset($_POST['badgeList'])) {
    ajaxSuccess(array('status'=>'error', 'error'=>"Error: No Badges")); exit();
}

db_connect();
$ccauth = get_conf('cc');
load_cc_procs();
load_email_procs();

$condata = get_con();
$log = get_conf('log');
$con = get_conf('con');
logInit($log['reg']);

$prices = array();
$memId = array();
$priceQ = <<<EOQ
SELECT m.id, m.memAge, m.label, m.shortname, m.price
FROM memLabel m
WHERE
    m.conid=?
    AND m.online = 'Y'
    AND startdate < current_timestamp()
    AND enddate >= current_timestamp()
;
EOQ;
$counts = array();
$priceR = dbSafeQuery($priceQ, 'i', array($condata['id']));
while($priceL = fetch_safe_assoc($priceR)) {
  $prices[$priceL['memAge']] = $priceL['price'];
  $memId[$priceL['memAge']] = $priceL['id'];
  $counts[$priceL['memAge']] = 0;
}

$badges = json_decode($_POST['badgeList'], true);
$people = array();

$total = 0;
$count = 0;

$newid_list = "";

// check that we got valid total from the post before anything is inserted into the database
foreach ($badges as $badge) {
    if(!isset($badge) || !isset($badge['age'])) { continue; }
    if (array_key_exists($badge['age'], $counts)) {
        $total += $prices[$badge['age']];
    }
}

$total=round($total, 2);

if($_POST['total'] != $total) {
    error_log("bad total: post=" . $_POST['total'] . ", calc=" . $total);
    ajaxSuccess(array('status'=>'error', 'data'=>'Unable to process, bad total sent to Server'));
    exit();
}

foreach ($badges as $badge) {
  if(!isset($badge) || !isset($badge['age'])) { continue; }
  if (array_key_exists($badge['age'], $counts)) {
      $counts[$badge['age']]++;

      $people[$count] = array(
        'info'=>$badge,
        'price'=>$prices[$badge['age']],
        'memId'=>$memId[$badge['age']]
        );

      if($badge['age'] != 'adult' && $badge['age'] != 'military' && $badge['age'] != 'youth' && $badge['age'] != 'child' && $badge['age'] != 'kit') {
          $badge['age']='all';
      }

      if($badge['share'] == "") { $badge['share'] = 'Y'; }

// see if there is an exact match

// now resolve exact matches
      $exactMsql = <<<EOF
SELECT id
FROM perinfo p
WHERE
	REGEXP_REPLACE(TRIM(LOWER(IFNULL(?,''))), "  *", " ") =
		REGEXP_REPLACE(TRIM(LOWER(IFNULL(p.first_name, ''))), "  *", " ")
	AND REGEXP_REPLACE(TRIM(LOWER(IFNULL(?,''))), "  *", " ") =
		REGEXP_REPLACE(TRIM(LOWER(IFNULL(p.middle_name, ''))), "  *", " ")
	AND REGEXP_REPLACE(TRIM(LOWER(IFNULL(?,''))), "  *", " ") =
		REGEXP_REPLACE(TRIM(LOWER(IFNULL(p.last_name, ''))), "  *", " ")
	AND REGEXP_REPLACE(TRIM(LOWER(IFNULL(?,''))), "  *", " ") =
		REGEXP_REPLACE(TRIM(LOWER(IFNULL(p.suffix, ''))), "  *", " ")
	AND REGEXP_REPLACE(TRIM(LOWER(IFNULL(?,''))), "  *", " ") =
		REGEXP_REPLACE(TRIM(LOWER(IFNULL(p.email_addr, ''))), "  *", " ")
	AND REGEXP_REPLACE(TRIM(LOWER(IFNULL(?,''))), "  *", " ") =
		REGEXP_REPLACE(TRIM(LOWER(IFNULL(p.phone, ''))), "  *", " ")
	AND REGEXP_REPLACE(TRIM(LOWER(IFNULL(?,''))), "  *", " ") =
		REGEXP_REPLACE(TRIM(LOWER(IFNULL(p.badge_name, ''))), "  *", " ")
	AND REGEXP_REPLACE(TRIM(LOWER(IFNULL(?,''))), "  *", " ") =
		REGEXP_REPLACE(TRIM(LOWER(IFNULL(p.address, ''))), "  *", " ")
	AND REGEXP_REPLACE(TRIM(LOWER(IFNULL(?,''))), "  *", " ") =
		REGEXP_REPLACE(TRIM(LOWER(IFNULL(p.addr_2, ''))), "  *", " ")
	AND REGEXP_REPLACE(TRIM(LOWER(IFNULL(?,''))), "  *", " ") =
		REGEXP_REPLACE(TRIM(LOWER(IFNULL(p.city, ''))), "  *", " ")
	AND REGEXP_REPLACE(TRIM(LOWER(IFNULL(?,''))), "  *", " ") =
		REGEXP_REPLACE(TRIM(LOWER(IFNULL(p.state, ''))), "  *", " ")
	AND REGEXP_REPLACE(TRIM(LOWER(IFNULL(?,''))), "  *", " ") =
		REGEXP_REPLACE(TRIM(LOWER(IFNULL(p.zip, ''))), "  *", " ")
	AND REGEXP_REPLACE(TRIM(LOWER(IFNULL(?,''))), "  *", " ") =
		REGEXP_REPLACE(TRIM(LOWER(IFNULL(p.country, ''))), "  *", " ");
EOF;
      $value_arr = array(
        trim($badge['fname']),
        trim($badge['mname']),
        trim($badge['lname']),
        trim($badge['suffix']),
        trim($badge['email1']),
        trim($badge['phone']),
        trim($badge['badgename']),
        trim($badge['addr']),
        trim($badge['addr2']),
        trim($badge['city']),
        trim($badge['state']),
        trim($badge['zip']),
        $badge['country']
        );
      $res = dbSafeQuery($exactMsql, 'sssssssssssss', $value_arr);
      if ($res !== false) {
          if ($res->num_rows > 0) {
              $match = fetch_safe_assoc($res);
              $id = $match['id'];
          } else {
              $id = null;
          }
      } else {
          $id = null;
      }
      $value_arr = array(
        trim($badge['lname']),
        trim($badge['mname']),
        trim($badge['fname']),
        trim($badge['suffix']),
        trim($badge['email1']),
        trim($badge['phone']),
        trim($badge['badgename']),
        trim($badge['addr']),
        trim($badge['addr2']),
        trim($badge['city']),
        trim($badge['state']),
        trim($badge['zip']),
        $badge['country'],
        $badge['contact'] === null ? 'Y' :  $badge['contact'],
        $badge['share'] === null ? 'Y' :  $badge['share'],
        $id
        );

      $insertQ = <<<EOS
INSERT INTO newperson(last_name, middle_name, first_name, suffix, email_addr, phone,
    badge_name, address, addr_2, city, state, zip, country, contact_ok, share_reg_ok, perid)
    VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?);
EOS;

      $newid = dbSafeInsert($insertQ, 'sssssssssssssssi', $value_arr);
      $people[$count]['newid']=$newid;

      $newid_list .= "id='$newid' OR ";

      $count++;
  } else {
      ajaxSuccess(array('status'=>'error', 'badges'=>$badges, 'error'=>"Error: invalid badge age category"));
      exit();
  }
}

$transQ = <<<EOS
INSERT INTO transaction(newperid, perid, price, type, conid)
    VALUES(?, ?, ?, ?, ?);
EOS;

$transid= dbSafeInsert($transQ, "iidsi", array($people[0]['newid'], $id, $total, 'website', $condata['id']));

$newid_list .= "transid='$transid'";

$person_update = "UPDATE newperson SET transid='$transid' WHERE $newid_list;";
// This dbQuery is all internal veriables, (id's returned by the database functions) so the Safe version is not needed.
dbQuery($person_update);

$badgeQ = <<<EOS
INSERT INTO reg(conid, newperid, perid, create_trans, price, memID)
VALUES(?, ?, ?, ?, ?, ?);
EOS;
$badge_types = "iiiidi";

foreach($people as $person) {
    $badge_data = array(
      $condata['id'],
      $person['newid'],
      $id,
      $transid,
      $person['price'],
      $person['memId'],
      );

  $badgeId=dbSafeInsert($badgeQ, $badge_types, $badge_data);
}

$all_badgeQ = <<<EOS
SELECT R.id AS badge,
    NP.first_name AS fname, NP.middle_name AS mname, NP.last_name AS lname, NP.suffix AS suffix,
    NP.email_addr AS email,
    NP.address AS street, NP.city AS city, NP.state AS state, NP.zip AS zip, NP.country AS country,
    NP.id as id, R.price AS price, M.memAge AS age, NP.badge_name AS badgename
FROM newperson NP
JOIN reg R ON (R.newperid=NP.id)
JOIN memList M ON (M.id = R.memID)
WHERE NP.transid=?;
EOS;

$all_badgeR = dbSafeQuery($all_badgeQ, "i", array($transid));

$badgeResults = array();
while ($row = fetch_safe_assoc($all_badgeR)) {
  $badgeResults[count($badgeResults)] = $row;
}



$results = array(
  'transid' => $transid,
  'counts' => $counts,
  'price' => $total,
  'badges' => $badgeResults,
  'total' => $total,
  'nonce' => $_POST['nonce']
  );

//log requested badges
logWrite(array('con'=>$condata['name'], 'trans'=>$transid, 'results'=>$results, 'request'=>$badges));

$rtn = cc_charge_purchase($results, $ccauth);
if ($rtn === null) {
    ajaxSuccess(array('status'=>'error', 'data'=>'Credit card not approved'));
    exit();
}

//$tnx_record = $rtn['tnx'];

$num_fields = sizeof($rtn['txnfields']);
$val = array();
for ($i = 0; $i < $num_fields; $i++) {
    $val[$i] = '?';
}
$txnQ = "INSERT INTO payments(time," . implode(',', $rtn['txnfields']) . ') VALUES(current_time(),' . implode(',', $val) . ');';
$txnT = implode('', $rtn['tnxtypes']);
$txnid = dbSafeInsert($txnQ, $txnT, $rtn['tnxdata']);
$approved_amt =  $rtn['amount'];

$txnUpdate = "UPDATE transaction SET ";
if($approved_amt == $total) {
    $txnUpdate .= "complete_date=current_timestamp(), ";
}

$txnUpdate .= "paid=? WHERE id=?;";
$txnU = dbSafeCmd($txnUpdate, "di", array($approved_amt, $transid) );

$regQ = "UPDATE reg SET paid=price WHERE create_trans=?;";
dbSafeCmd($regQ, "i", array($transid));


$return_arr = send_email($con['regadminemail'], trim($_POST['cc_email']), /* cc */ null, $condata['label']. " Online Registration Receipt",  getEmailBody($transid), /* htmlbody */ null);

ajaxSuccess(array(
  "status"=>$return_arr['success'],
  "url"=>$rtn['url'],
  "data"=> $return_arr['email_error'],
  "trans"=>$transid,
  //"email"=>$email_msg,
  "email_error"=>$return_arr['error_code']
));
?>