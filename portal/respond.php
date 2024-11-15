<?php
// Registration  Portal - respond.php - respond to as non login email token request
require_once("lib/base.php");

$con = get_conf('con');
$conid = $con['id'];
$portal_conf = get_conf('portal');
$debug = get_conf('debug');
$ini = get_conf('reg');
$condata = get_con();

index_page_init($condata['label'] . ' Membership Portal - Respond to Request Link');

?>
<body id="membershipPortalBody">
<div class="container-fluid">
    <div class="row">
        <div class="col-sm-12 p-0">
            <?php
            if (array_key_exists('logoimage', $ini) && $ini['logoimage'] != '') {
                if (array_key_exists('logoalt', $ini)) {
                    $altstring = $ini['logoalt'];
                } else {
                    $altstring = 'Logo';
                } ?>
                <img class="img-fluid" src="images/<?php echo $ini['logoimage']; ?>" alt="<?php echo $altstring; ?>"/>
                <?php
            }
            if (array_key_exists('logotext', $ini) && $ini['logotext'] != '') {
                echo $ini['logotext'];
            }
            ?>
        </div>
    </div>
    <div class="row">
        <div class="col-sm-12 mt-2">
            <h1>Membership Portal</h1>
        </div>
    </div>
<?php
// decipher the request
if (!(array_key_exists('action', $_GET) && array_key_exists('vid', $_GET))) {
    echo "<h3>Invalid Respond Link - Get Assistance</h3>\n";
    exit();
}

echo "\t<div class='row'>\n";

$action = $_GET['action'];
$vid = $_GET['vid'];
$result_message = '';

switch ($action) {
    case 'attach':
        $match = decryptAttach($_GET['vid'], $true);
        if (!validateLink($match, $action, 7 * 24 * 3600)) {
            break; // link is no longer valid, disregard, validate link puts out its own diagnostic.
        }

        // valid - process the attach request
        $acctType = $match['acctType'];
        $acctId = $match['acctId'];
        $loginId = $match['loginId'];
        $loginType = $match['loginType'];
        $managerEmail = $match['managerEmail'];

        // first update the types if they have been matched in the interim
        $cQ = <<<EOS
SELECT perid
FROM newperson
WHERE id = ?;
EOS;
        if ($acctType == 'n') {
            $cR = dbSafeQuery($cQ, 'i', array($acctId));
            if ($cR == false || $cR->num_rows != 1) {
                echo "<div class='col-sm-auto'>Can no longer find that person.</div>\n";
                break;
            }
            $newId = $cR->fetch_row()[0];
            $cR->free();
            if ($newId != null) {
                $acctId = $newId;
                $acctType = 'p';
            }
        }
        if ($loginType == 'n') {
            $cR = dbSafeQuery($cQ, 'i', array($loginId));
            if ($cR == false || $cR->num_rows != 1) {
                echo "<div class='col-sm-auto'>Can no longer find manager.</div>\n";
                break;
            }
            $newId = $cR->fetch_row()[0];
            $cR->free();
            if ($newId != null) {
                $loginId = $newId;
                $loginType = 'p';
            }
        }

        // ok, loginId and type are now updated
        $table = $acctType == 'p' ? 'perinfo' : 'newperson';
        $pfield = $loginType == 'p' ? 'managedBy' : 'managedByNew';
        $uQ = <<<EOS
UPDATE $table
SET $pfield = ?, managedReason = 'Client Response'
WHERE id = ?;
EOS;
        $num_upd = dbSafeCmd($uQ, 'ii', array($loginId, $acctId));
        if ($num_upd === false) {
            echo "<div class='col-sm-auto'>Unable to assign manager.</div>\n";
            break;
        }

        echo "<div class='col-sm-auto'>Your request has been processed and your account is now managed by $managerEmail.</div>\n";
        break;

    case 'identity':
        $match = decryptAttach($_GET['vid'], true);
        if (!validateLink($match, $action, 7 * 24 * 3600)) {
            break; // link is no longer valid, disregard, validate link puts out its own diagnostic.
        }

        // valid - process the attach request
        $provider = $match['provider'];
        $loginId = $match['loginId'];
        $email = $match['email'];

        // check if it's already there
        // first check to see if this identity exists
        $cQ = <<<EOS
SELECT perid, provider, email_addr
FROM perinfoIdentities
WHERE provider=? AND email_addr = ?
EOS;

        $cR = dbSafeQuery($cQ, 'ss', array($provider, $email));
        if ($cR == false) {
            echo "<div class='col-sm-auto bg-danger text-white'>Error checking identity list, get help</div>\n";
            break;
        }

        if ($cR->num_rows != 0) {
            echo "<div class='col-sm-auto bg-danger text-white'>This entity already exists</div>\n";
            break;
        }

// we just need the presence / absence, we never need to retrieve the data
        $cR->free();

// we have a match, see if this email address is someone elses or is already one of our email addresses and just add it for the new provider is one of our email addresses
        $cQ = <<<EOS
SELECT COUNT(*) AS emails
FROM perinfoIdentities
WHERE email_addr = ? AND perid != ?;
EOS;
        $cR = dbSafeQuery($cQ, 'si', array($provider, $loginId));
        if ($cR == false || $cR->num_rows == 0) {
            echo "<div class='col-sm-auto bg-danger text-white'>Error checking if this email belongs to someone else</div>\n";
            break;
        }

        $counts = $cR->fetch_row()[0];
        $cR->free();
        if ($counts > 0) {
            echo "<div class='col-sm-auto bg-danger text-white'>Someone else already has this email address in their identity list, " .
                "if this email is yours contact the registation admin for assistance.</div>\n";
            break;
        }

        // ok, insert it in the table.
        $iQ = <<<EOS
INSERT into perinfoIdentities(perid, provider, email_addr)
VALUES (?, ?, ?);
EOS;
        $iKey = dbSafeInsert($iQ, 'iss', array($loginId, $provider, $email));
        if ($iKey === false) {
            echo "<div class='col-sm-auto bg-danger text-white'>Unable to add identity, get assistance</div>\n";
            break;
        }


        echo "<div class='col-sm-auto'>Your request has been processed and the identity $provider:$email has been added.<br/>\n" .
            "You will see this in your list the next time you log into the portal.</div>\n";
        break;

    default:
        echo "<h3>Invalid Respond Link - Get Assistance</h3>\n";
}
?>
    </div>
    <div class='container-fluid'>
        <div class='row'>
            <div class='col-sm-12 m-0 p-0'>
                <div id='result_message' class='mt-4 p-2'><?php echo $result_message; ?></div>
            </div>
        </div>
        <div class='row mt-2'>
            <?php drawBug(12); ?>
        </div>
    </div>
</body>
<?php

// validate the link, and mark it used
function validateLink($match, $action, $expiration) {
    $linkid = $match['lid'];
    // check if the link has been used
    $linkQ = <<<EOS
SELECT id, email, useCnt, TIMESTAMPDIFF(SECOND, useTS, NOW()) AS diff
FROM portalTokenLinks
WHERE id = ? AND action = ?
ORDER BY createdTS DESC;
EOS;
    $linkR = dbSafeQuery($linkQ, 'ss', array($linkid, $action));
    if ($linkR == false || $linkR->num_rows != 1) {
        echo "<div class='col-sm-auto bg-danger text-white'>The link is invalid, please request a new link</div>\n";
        return false;
    }
    $linkL = $linkR->fetch_assoc();
    if ($linkL['email'] != $match['email']) {
        echo "<div class='col-sm-auto bg-danger text-white'>The link is invalid, please request a new link</div>";
        return false;
    }

    $timediff = time() - $match['ts'];
    if ($timediff > $expiration) {
        echo "<div class='col-sm-auto bg-danger text-white'>The link has expired, please request a new link</div>";
        exit();
    }

    $useMax = $linkL['diff'] > 30 ? 0 : 2; // use 2 to allow for double click
    if ($linkL['useCnt'] > $useMax) {
        echo "<div class='col-sm-auto bg-danger text-white'>The link has already been used, please request a new link</div>";
        return false;
    }

    // mark link as used
    $updQ = <<<EOS
UPDATE portalTokenLinks
SET useCnt = useCnt + 1, useIP = ?, useTS = now()
WHERE id = ?;
EOS;
    $updcnt = dbSafeCmd($updQ, 'si', array($_SERVER['REMOTE_ADDR'], $linkid));
    if ($updcnt != 1) {
        web_error_log("Error updating link $linkid as used");
    }
    return true;
}
