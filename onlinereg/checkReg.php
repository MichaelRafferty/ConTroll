<?php
require_once("lib/base.php");
$ini = redirect_https();
$condata = get_con();
$con = get_conf('con');

$lname = "";
$fname = "";
if(isset($_GET) && isset($_GET['lname']) && isset($_GET['fname'])) {
  $lname = sql_safe($_GET['lname']);
  $fname = sql_safe($_GET['fname']);
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
Please provide the last name and at least the first initial of the first name.
    <form method="GET">
        First Name: <input required='required' type='text' name='fname' />
        Last Name: <input required='required' type='text' name='lname' />
        <input type='submit' value='Search' />
    </form>
    <?php
} else {
    $fname = sql_safe($fname);
    $lname = sql_safe($lname);
    $query = "SELECT P.last_name, SUBSTRING(P.first_name, 1, 1) as fi, SUBSTRING(P.middle_name, 1, 1) as mi, P.zip ".
         "FROM reg as R, perinfo as P " .
         "WHERE (R.perid = P.id AND P.share_reg_ok='Y' AND " .
             "R.conid = '" . $condata['id'] . "' AND ".
             "P.first_name like '$fname%' AND " .
             "P.last_name = '$lname' AND " .
             "R.price=R.paid);";

    $perR = dbQuery($query);

    $newp_query = "SELECT NP.last_name, SUBSTRING(NP.first_name, 1, 1) as fi, SUBSTRING(NP.middle_name, 1, 1) as mi, NP.zip ".
         "FROM reg as R, newperson as NP " .
         "WHERE (R.perid is null AND NP.share_reg_ok='Y' AND " .
             "R.newperid = NP.id AND R.conid = '" . $condata['id'] . "' AND ".
             "NP.first_name like '$fname%' AND " .
             "NP.last_name = '$lname' AND " .
             "R.price=R.paid);";

    $nperR = dbQuery($newp_query);

    $results = array();
    while($perR && $row = fetch_safe_assoc($perR)) {
        array_push($results, $row);
    }

    while($perR && $row = fetch_safe_assoc($nperR)) {
        array_push($results, $row);
    }

    ?>
Your search for <?php echo $fname . " " . $lname; ?> found <?php echo count($results); ?> Badges.<br />
    <table>
        <thead>
            <tr>
                <th>Last Name</th><th>FI</th><th>MI</th><th>Zip</th>
            </tr>
        </thead>
        <tbody>
            <?php
  foreach($results as $row) {
            ?>
            <tr>
                <td>
                    <?php echo $row['last_name'];?>
                </td>
                <td>
                    <?php echo $row['fi'];?>
                </td>
                <td>
                    <?php echo $row['mi'];?>
                </td>
                <td>
                    <?php echo $row['zip'];?>
                </td>
            </tr>
            <?php
  }
            ?>
        </tbody>
    </table>


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
