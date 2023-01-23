<?php 
  require_once("db_functions.php");
  require_once("artshow.php");
function vendorReset($passwd, $dest) {

$body = "The password to you Balticon Vendor Portal account has been reset.\nThe new password is:\n\t$passwd\n\nPlease login to the Balticon Artshow site at "
    . "https://" . $_SERVER['HTTP_HOST'] . "/$dest"
    . " to change your password.\n\nIf you continue to have problems please contact regadmin@bsfs.org.\n\nThank you for your interest in Balticon.\n";

return $body;
}
function request_vendor($vendorId) {
    $vendorQ = "SELECT name, website, description FROM vendors WHERE id=$vendorId";
    $vendor = fetch_safe_assoc(dbQuery($vendorQ));
    $body = $vendor['name'] . ",\n"
        . "Thank you for your interest in the Vending at Balticon.\nYou provided the description:\n" . $vendor['description'] . "\nAnd the website " . $vendor['website'] . "\n\nIf you have any questions please contact the Artist Alley or Dealers Room staff.\n\nThank you\n";
    //$body = "id: $vendorId access: $access info: " . json_encode($vendor) . "\n";

    return $body;
}
function request($access, $vendorId) {
    $vendorQ = "SELECT name, website, description FROM vendors WHERE id=$vendorId";
    $vendor = fetch_safe_assoc(dbQuery($vendorQ));
    $body = $vendor['name'] . ",\n"
        . "Thank you for your interest in the Balticon $access.\nYou provided the description:\n" . $vendor['description'] . "\nAnd the website " . $vendor['website'] . "\n\nIf you have any questions please contact the $access staff.\n\nThank you\n";
    //$body = "id: $vendorId access: $access info: " . json_encode($vendor) . "\n";

    return $body;
}

function artshowReceipt($artshowid) {
    global $con;

    $infoQ = "SELECT S.art_key, S.a_panels, S.p_panels, S.a_tables, S.attending"
        . ", S.agent_request, S.description"
        . ", concat_ws(' ', P.first_name, P.last_name) as name"
        . ", A.art_name" 
        . ", concat_ws(' ', G.first_name, G.last_name) as agent"
        . " FROM artshow as S"
            . " JOIN perinfo as P on P.id=S.perid"
            . " JOIN artist as A on A.id=S.artid"
            . " LEFT JOIN perinfo as G on G.id=A.agent_perid"
        . " WHERE S.id = $artshowid;";

    $info = fetch_safe_assoc(dbQuery($infoQ));

    $body = (($info['art_name'] == "")?$info['art_name']:$info['name']) . ",\n"
        . "Wecome to the " . $con['label'] . " Art Show.\n"
        . "Your Artist Number is " . $info['art_key'] . "\n"
        . "You have been registered for:\n "
        . (($info['a_panels'] > 0)?"\t" . calc_panels($info['a_panels']) . " Artshow Panels\n":"")
        . (($info['a_tables'] > 0)?"\t" . calc_tables($info['a_tables']) . " Artshow Tables\n":"")
        . (($info['p_panels'] > 0)?"\t" . calc_panels($info['p_panels']) . " Printshop Panels\n":"")
        . "\n";

    if($info['agent_request'] != '') {
        $body .= "You've identified " . $info['agent_request'] . " as your agent";
        if($info['agent_request'] != $info['agent']) {
            if($info['agent'] != '') {
                $body .= ", if this is a change from your prior agent of ". $info['agent'];
            } else {
                $body .= ", as this is a new agent";
            }
            $body .= ", it may take us a few days to update the database";
        }

        $body .= ".\n\n";
    }

    if($info['description'] != '') {
        $body .= "Thank you for providing the following information about your art:\n\t" . html_entity_decode($info['description'], ENT_QUOTES|ENT_HTML401) . "\n\n";
    }
    

    $body .= "
* * * * * * * FILLING OUT THE ONLINE CONTROL SHEET * * * * * * * *

By using the Online Control Sheet, you will be able to enter your art directly into the Art Show database.  You can edit your entries and remove them if needed, then print out your bid sheets and copy sheets.

"
. "Sign in to the Artist Portal at https://" . $_SERVER['HTTP_HOST'] . "/artshow."
. "   You will see your personal and Art Show information.  Select the Online Control Sheet and then follow the instructions to enter your art. 
 
Please email artshow@balticon.org if you have any questions about the forms.

";

    switch ($info['attending']) {
        case 'mailin':
            $body .= "
* * * * * * * * MAIL-IN DEADLINE * * * * * * * * 
Please have your art postmarked no later than Friday, May 18, to account for postal delays.  It will be physically impossible for us to pick up the art at the box location after Wednesday, May 23. Send your art to the address below: 

BALTICON ART SHOW
7820B Worman's Mill Rd
PMB 286
Frederick, MD 21701


* * * * * * * * RETURN SHIPPING * * * * * * * * 
Please include either a pre-paid shipping label, or fill out a Return Shipping form with payment and include that with your art.  Forms will be available on the Artist Portal.  Unsold art will be shipped out within five days of the end of the convention.
";
            break;
        case 'agent':
        case 'attending':
            $body .= "
* * * * * * * * FRIDAY ARTIST CHECK-IN * * * * * * * * 
Artist Check-in starts at noon on Friday.  Bring your art to the Art Show and check-in at the table with your name and artist number from your forms.  We will give you your space/location, and you can hang or set up your art.  We provide hanging hardware, including the extra-long hooks for the Print Shop.  When your art has been set up, come back to the table to have one of our staff finish the check-in process.


* * * * * * * * ARTIST GUEST OF HONOR * * * * * * * * 
This year our Artist Guest of Honor Galen Dara will be in the Art Show at 9 pm, after the Opening Ceremonies.  Come to the Art Show to meet her and her art.


* * * * * * * * ARTIST CHECK-OUT * * * * * * * * 
The Art Auction and sales start at 2pm on Sunday, and Artist Check-out runs from about 3-5pm.  Pack up your art, then bring all bid sheets to the desk to be checked out.  We will perform reconciliation on Monday, and generate checks to be sent out within two weeks after the convention.
";
            break;
        default:
            $body .= "Something Broke in our script, our appologies.\n\n";
    }

    $body .= "

Thank you,

- Nora Echeverria, Anna Scott
Balticon 52 Art Show

www.balticon.org
www.bsfs.org
";
    return $body;
}

function getEmailBody($transid) {
  $con = get_con();
  $ini = get_conf('reg');

$ownerQ = "select NP.first_name, NP.last_name, P.cc_txn_id as payid, T.complete_date from newperson as NP, transaction as T, payments as P where P.transid=T.id and NP.id=T.newperid and T.id='" . sql_safe($transid) . "';";
$owner = fetch_safe_assoc(dbQuery($ownerQ));

$body = $owner['first_name'] . " " . $owner['last_name'] .",\n";
$body .= "thank you for registering for ". $con['name'] . "\n\n"; 

if($ini['test']==1) {
  $body .= "This Page is for test purposes only\n";
}

$body .= "Your Transaction number is $transid and Receipt number is " . 
  $owner['payid'] . ".\n";

$body .= "In response to your request Badges have been created for:\n";

$badgeQ = "select NP.first_name, NP.last_name, M.label from newperson as NP, transaction as T, reg as R, memList as M where NP.id=R.newperid and M.id=R.memId and R.create_trans=T.id and T.id='". sql_safe($transid) . "';";
$badgeR = dbQuery($badgeQ);

while($badge = fetch_safe_assoc($badgeR)) {
  $body.= "* ". $badge['first_name'] . " " . $badge['last_name']
    . " (" . $badge['label'] . ")\n\n";
}

$body .= "You will receive a separate email with credit card receipt details.\n";

$body .= "Please contact regadmin@bsfs.org or registration@bsfs.org with any questions and we look forward to seeing you at " . $con['name'] . ".\n"; 

$body .= "
For hotel information and directions please see http://balticon.org/hotel.html

The Balticon/BSFS Harrasment Policy is at http://www.bsfs.org/policy.htm.
For more information about Balticon please email BalticonInfo@bsfs.org.
For questions about Balticon Registration, email registration@balticon.org.";

return $body;
}

