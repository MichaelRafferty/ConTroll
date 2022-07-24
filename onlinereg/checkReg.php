<?php
require_once("lib/base.php");
$ini = redirect_https();
$condata = get_con();
$con = get_conf('con');

$lname = "";
$fname = "";
if(isset($_GET) && isset($_GET['lname']) && isset($_GET['fname'])) {
  $lname = $_GET['lname'];
  $fname = $_GET['fname'];
}

ol_page_init($condata['label'] . ' Registration Check');
?>
<body>
    <h1>
        <?php echo $condata['label']; ?> Registration Check
    </h1>
    <?php
if($lname == "" or $fname == "") {
    ?>
     <div id='newBadge' class="container-fluid">
         <form method="GET">
             <div class="row" style="width:100%;">
                 <div class="col-12 ms-0 me-0 p-0">
                     <p>Please provide the last name and at least the first initial of the first name.</p>
                 </div>
             </div>
             <div class="row" style="width:100%;">
                 <div class="col-1 ms-0 me-0 p-0">
                    <label for="fname" class="form-label-sm"><span class="text-dark"><span class='text-info'>*</span>First Name:</span></label>
                 </div>
                 <div class="col-2 ms-0 me-0 p-0">
                     <input class="form-control-sm" type="text" name="fname" id='fname' size="12" maxlength="32" tabindex="1"/>
                 </div>
                 <div class="col-1 ms-0 me-0 p-0">
                    <label for="lname" class="form-label-sm"><span class="text-dark"><span class='text-info'>*</span>Last Name:</span></label>
                 </div>
                 <div class="col-3 ms-0 me-0 p-0">
                     <input class="form-control-sm" type="text" name="lname" id='lname' size="22" maxlength="32" tabindex="2"/>
                 </div>
                 <div class="col-1 ms-0 me-0 p-0">
                     <input type='submit' value='Search' />
                 </div>
             </div>
         </form>
     </div>
    <?php
} else {
    $fname .= '%';
    $query = <<<EOS
SELECT P.last_name, SUBSTRING(P.first_name, 1, 1) AS fi, SUBSTRING(P.middle_name, 1, 1) AS mi, P.zip
FROM reg R
JOIN perinfo P ON (R.perid = P.id)
WHERE P.share_reg_ok='Y' AND R.conid = ? AND P.first_name like ? AND P.last_name = ? AND R.price=R.paid;
EOS;

    $perR = dbSafeQuery($query, 'iss', array($condata['id'],  $fname, $lname));

    $newp_query = <<<EOS
SELECT NP.last_name, SUBSTRING(NP.first_name, 1, 1) as fi, SUBSTRING(NP.middle_name, 1, 1) as mi, NP.zip
FROM reg R
JOIN newperson NP ON (R.newperid = NP.id)
WHERE R.perid is null AND NP.share_reg_ok='Y' AND R.conid =? AND NP.first_name like ? AND NP.last_name = ? AND R.price=R.paid;
EOS;

    $nperR = dbSafeQuery($newp_query, 'iss', array($condata['id'],  $fname, $lname));

    $results = array();
    while($perR && $row = fetch_safe_assoc($perR)) {
        array_push($results, $row);
    }

    while($perR && $row = fetch_safe_assoc($nperR)) {
        array_push($results, $row);
    }

    $numbadges = count($results);
    ?>
    <div id='newBadge' class="container-fluid">
        <div class="row" style="width:100%;">
            <div class="col-12 ms-0 me-0 p-0">
                <p>Your search for <?php echo $fname . " " . $lname; ?> found <?php echo$numbadges; ?> Badge<?php if ($numbadges != 1) { echo "s"; } ?>.</p>
            </div>
        </div>
        <div class="row" style="width:100%;">
             <div class="col-2 ms-0 me-0 p-0">
                 <strong>Last Name</strong>
             </div>
            <div class="col-1 ms-0 me-0 p-0">
                 <strong>FI</strong>
             </div>
             <div class="col-1 ms-0 me-0 p-0">
                 <strong>MI</strong>
             </div>
             <div class="col-1 ms-0 me-0 p-0">
                 <strong>Zip</strong>
             </div>
        </div> 
            <?php
  foreach($results as $row) {
            ?>
            <div class="row" style="width:100%;">
                 <div class="col-2 ms-0 me-0 p-0">
                      <?php echo $row['last_name'];?>
                 </div>
                <div class="col-1 ms-0 me-0 p-0">
                    <?php echo $row['fi'];?>
                </div>
                <div class="col-1 ms-0 me-0 p-0">
                    <?php echo $row['mi'];?>
                </div>
                <div class="col-1 ms-0 me-0 p-0">
                     <?php echo $row['zip'];?>
                </div>
            </div> 
            <?php
  }
            ?>
    </div>


    <?php } ?>
For hotel information and directions please see <a href="<?php echo $con['hotelwebsite']; ?>">
        the <?php echo $con['conname']; ?> hotel page
    </a>
    <br />
    <hr />

    <p class="text-body">
        <a href="<?php echo $con['policy'];?>" target="_blank">
            Click here for the <?php echo $con['policytext']; ?>
        </a>.<br />
        For more information about <?php echo $con['conname']; ?> please email <a href="mailto:<?php echo $con['infoemail']; ?>">
            <?php echo $con['infoemail']; ?>
        </a>.<br />
        For questions about <?php echo $con['conname']; ?> Registration, email <a href="mailto:<?php echo $con['regemail']; ?>">
            <?php echo $con['regemail']; ?>
        </a>.
    </p>
</body>
</html>
