<?php
  require_once("../../lib/db_functions.php");

function refundEmail_HTML($test, $email, $tid) {
  $con = get_con();
  $ini = get_conf('reg');
  $conid=$con['id'];

  $conlabel = $con['label'];

  $url = $ini['reg']['server'] . "/OnlineReg/cancelation.php";
  $url2 = $ini['reg']['server'] . "/OnlineReg";
  if($test) {
      $url = $ini['reg']['testserver'] . "/OnlineReg/cancelation.php";
      $url2 = $ini['reg']['testserver'] . "/OnlineReg";
    }

  $transQ = "SELECT T.paid, M.label, M.memAge"
        . ", P.first_name, P.last_name, P.badge_name"
        . ", R.paid"
    . " FROM transaction as T"
        . " JOIN reg as R on R.create_trans=T.id"
        . " JOIN perinfo as P on P.id=R.perid"
        . " JOIN memList as M on M.id=R.memId"
        . " JOIN payments as Y on Y.transid=T.id"
    . " WHERE T.id = " . sql_safe($tid)
    . " AND M.memCategory in ('standard', 'yearahead') AND M.conid=$conid" . ";";
  $transR = dbQuery($transQ);

  $names = "<ul>\n";
  while($trans = fetch_safe_assoc($transR)) {
    $names .= "\t<li>" . $trans['first_name'] . " " . $trans['last_name'];
    if($trans['badge_name'] != '') {$names .= " (" .$trans['badge_name'] . ")";}
    $names .= "</li>\n";
  }
  $names .= "</ul>\n";

$text = <<<EOT
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"><html><head><META http-equiv="Content-Type" content="text/html; charset=utf-8"></head><body>

<p>As we announced on Wednesday, March 18, we had to make the unfortunate decision to cancel $conlabel. Your email address is associated with a membership to $conlabel.</p>

<p>Our records show this email address is associated with Transaction #<strong>$tid</strong>, which has the following memberships:<p>
<strong>$names</strong>

<p>If this is an error, please contact us at <a href='mailto:refunds@balticon.org'>refunds@balticon.org</a>. Otherwise, please visit <a href='$url?email=$email&tid=$tid'>our Membeship Cancelation page</a> to tell us how you want your membership(s) processed.</p>

<p>We are offering three options for what to do with your membership: donation, rollover, or refund.</p>

<DL>
<DT><strong>Donation</strong></DT>
<DD>Balticon is run by the Baltimore Science Fiction Society (BSFS) which depends on memberships from Balticon for nearly all its yearly budget. Please consider donating the cost of your membership(s) to the BSFS. As a 501(c)3 non-profit, all donations to BSFS are tax-deductible (please contact your tax professionals for full details).</DD>

<DT><strong>Rollover</strong></DT>
<DD>A rollover will convert your existing $conlabel membership(s) to a Balticon 55 membership, at no additional cost to you.</DD>

<DT><strong>Refund</strong></DT>
<DD>For online purchases, we can refund your purchase to your original method of payment. For at con or mail in purchases, we can mail you a check to refund your membership(s). If the refund needs to be done another way, please contact registration@balticon.org so that we can discuss options. We will process the refund as soon as possible.</DD>
</DL>

<p>You can let us know your preference by visiting <a href='$url?email=$email&tid=$tid'>our Membership Cancelation Page</a> at <a href='$url'>$url</a> and entering your email address and transaction number (<strong>$tid</strong>). You can also reach the page by following links from <a href='$url2'>Balticon Online Registration</a> or the <a href='https://www.balticon.org/wp54/registration'>Registration page</a> on the <a href='https://www.balticon.org/'>Balticon Website</a>. If we do not hear from you by <strong>June 15th</strong>, we will process your membership as a Rollover, and you will be pre-registered for Balticon 55. Any difference between the amount paid and pre-registration rate for Balticon 55 will be considered a (very appreciated) tax-deductible donation to BSFS.</p>

<p>Memberships will be processed as staff is available. We hope to have all rollovers, refunds, and donations processed by the end of the summer. Please remember that Balticon is a 100% volunteer-run organization, and we thank you in advance for your patience.</p>

EOT;

return $text;
}

function refundEmail_TEXT($test, $email, $tid) {
  $con = get_con();
  $ini = get_conf('reg');
  $conid=$con['id'];

  $conlabel = $con['label'];

  $url = $ini['reg']['server'] . "/cancelation.php";
  if($test) { $url = $ini['reg']['testserver'] . "/cancelation.php"; }

  $transQ = "SELECT T.paid, M.label, M.memAge"
        . ", P.first_name, P.last_name, P.badge_name"
        . ", R.paid"
    . " FROM transaction as T"
        . " JOIN reg as R on R.create_trans=T.id"
        . " JOIN perinfo as P on P.id=R.perid"
        . " JOIN memList as M on M.id=R.memId"
        . " JOIN payments as Y on Y.transid=T.id"
    . " WHERE T.id = " . sql_safe($tid)
    . " AND M.memCategory in ('standard', 'yearahead') AND M.conid=$conid" . ";";
  $transR = dbQuery($transQ);

  $names = "";
  while($trans = fetch_safe_assoc($transR)) {
    $names .= $trans['first_name'] . " " . $trans['last_name'] . " (" .$trans['badge_name'] . ")\n";
  }

$text = <<<EOT
As we announced on Wednesday, March 18, we had to make the unfortunate decision to cancel $conlabel. Your email address is associated with a membership to $conlabel.

Our records show this email address is associated with Transaction #$tid, which has the following memberships:
$names

If this is an error, please contact us at refunds@balticon.org. Otherwise, please visit $url?email=$email&tid=$tid to tell us how you want your membership(s) processed.

We are offering three options for what to do with your membership: donation, rollover, or refund.

Donation
Balticon is run by the Baltimore Science Fiction Society (BSFS), which depends on memberships from Balticon for nearly all its yearly budget. Please consider donating the cost of your membership(s) to BSFS. As a 501(c)3 non-profit, all donations to BSFS are tax-deductible (please contact your tax professionals for full details).

Rollover
A rollover will convert your existing $conlabel membership(s) to a Balticon 55 membership, at no additional cost to you.

Refund
For online purchases, we can refund the value to your original method of payment. For at-con or mail-in purchases, we can mail a check to refund your membership(s). If the refund needs to be done another way, please contact registration@balticon.org so that we can discuss options. We will process your refund as soon as possible.

You can let us know your preference by visiting $url?email=$email&tid=$tid. You can also find this page by going to $url and entering your email address and transaction number ($tid). If we do not hear from you by June 15th, we will process your membership as a rollover, and you will be pre-registered for Balticon 55. Any difference between the amount paid and pre-registration rate for Balticon 55 will be considered a very appreciated tax-deductible donation to BSFS.

Memberships will be processed as staff is available. We hope to have all rollovers, refunds, and donations processed by the end of the summer. Please remember that Balticon is a 100% volunteer-run organization, and we thank you in advance for your patience.

EOT;

return $text;
}


function preConEmail_last_HTML($test) {

  $con = get_con();
  $ini = get_conf('reg');

$conlabel = $con['label'];

$html = <<<EOT
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"><html><head><META http-equiv="Content-Type" content="text/html; charset=utf-8"></head><body><p>Hello!</p>

<p>
	$conlabel is almost upon us! You are receiving this email because your email address is associated with a valid registration to attend this year’s convention. To check the status of your badge or the rest of your family, you can always visit: <a href="https://reg.balticon.org/onlineReg/checkreg.php" target="_blank">https://reg.balticon.org/<wbr>onlineReg/checkreg.php</a>
</p>
<p>
	This year, we are again at the <a href="https://www.balticon.org/wp53/hotel/renaissance-harborplace-hotel/" target="_blank">Renaissance Baltimore Harborplace Hotel</a>, at 202 East Pratt Street, Baltimore, MD 21202. Badges can be picked up or purchased at Balticon Registration, which is on the 5th floor in the Maryland Ballroom Foyer next to the Watertable Restaurant. Since parking in a city can be expensive, we recommend using mass transit if at all possible. You can find information at <a href="https://www.balticon.org/wp53/hotel/mass-transit/" target="_blank">https://www.balticon.org/wp53/<wbr>hotel/mass-transit/</a>. If you are arriving by car, we strongly recommend pre-purchasing your parking. Details are available on our website at <a href="https://www.balticon.org/wp53/hotel/parking/" target="_blank">https://www.balticon.org/wp53/<wbr>hotel/parking/</a>.
</p>

<p>
	Our Programming team has put together a great schedule for us this year, and you can take a look at it at <a href="https://schedule.balticon.org/" target="_blank">https://schedule.balticon.org/</a> . Information about other activities, as well as our Guests of Honor, can be found on our website at <a href="https://balticon.org/" target="_blank">https://balticon.org/</a>
</p>

<p>
	The Baltimore Science Fiction Society (BSFS) is dedicated to providing a comfortable and harassment-free environment for everyone at Balticon and other BSFS-sponsored events. For specific information, including our full Anti-Harassment Policy, see <a href="http://bsfs.org/policy.htm" target="_blank">http://bsfs.org/policy.htm</a>.
</p>
<p>
	If you have any further questions, please feel free to contact us at <a href="mailto:feedback@balticon.org" target="_blank">feedback@balticon.org</a>, or visit our website for information on how to contact individual departments.
</p>

<p>See you at the convention!</p>
<br>

<p>
	<strong>Where do I get my badge?</strong><br>
	When you arrive at the hotel, take the elevators up to the 5th floor. Follow the signs to the Maryland Ballroom Foyer, where you’ll see a banner for Balticon Registration. This is NOT the same area as Hotel Registration. Get in line for Check-In, and have your ID ready.<br>
	Registration hours are:<br>
	<table>
	<tr>
		<td>Friday</td>
		<td>1pm – 10pm</td>
	</tr>
	<tr>
		<td>Saturday</td>
		<td>8:45am – 7pm</td>
	</tr>
	<tr>
		<td>Sunday</td>
		<td>8:45am – 5pm</td>
	</tr>
	<tr>
		<td>Monday</td>
		<td>10am – 1:30pm</td>
	</tr>
	</table>
</p>

<p>
	<strong>Do I have to show ID?</strong><br>
	If you pre-purchased a badge, we would like to make sure that no one else picks up your badge. We are very reasonable about what we consider valid ID; we just need to know the badge is going to the right person. However, if you really don’t want to use your ID, you are always welcome to pay cash at the door!
</p>
<p>
	<strong>Can I pick up my spouse/girlfriend/child’s membership as well as my own?</strong> <br>
	Maybe! You can pick up someone else’s membership IF one of the following is true:<br>
	<ul>
		<li>The two memberships share a last name.</li>
		<li>The two memberships share an address.</li>
		<li>The two memberships were purchased on the same transaction.</li>
		<li>You have ID for both memberships.</li>
	</ul>
	If you are picking up a membership for someone else, we will also request that you leave us with a cell phone number where we can reach you at con, in case they come looking for their badge at Registration before they find you.
</p>

<p>
	<strong>I changed my mind about my badge name! I just came up with the coolest thing, but I put something on my form that I hate now!</strong><br>
	That’s okay! When you come to pick up your badge, just let us know you’d like to change your badge name before we print it out, and we can make the change at the door.
</p>

<p>
	<strong>We don’t need no stinkin’ badges!</strong><br>
	Our badges don’t stink, and yes, you do need one. All Balticon members are required to be have a badge at all times in Balticon spaces.  The badge should be worn so as to be clearly visible and must be presented to any Balticon volunteer checking badges on behalf of the convention.  We encourage members to wear their badges above the waist because a higher number of badges are lost when worn at hip level. If you do lose your badge, you can check at Registration or the Lost &amp; Found at Ops to see if it has been turned in.</p>
<p>Most conventions require you to purchase a new membership if your original badge is lost and has not been found, and that is an option. Such purchases are not refundable if the badge is later located. Alternatively, if the registration lead can confirm that someone has purchased or otherwise been granted a currently valid membership to Balticon, they may, at their discretion, issue a replacement badge.  A donation of at least $20 to BSFS Books For Kids is requested for that service.
</p>
<p>
	<strong>What about Participants, Dealers, and Artists?</strong> <br>
	You should be receiving or have already received a separate email from your respective departments soon with further information. Your badge will still be at Registration for you!
</p>

<hr>
<p style="text-align:center">
	You are receiving this email because your email address is associated with a valid membership for Balticon 53. You will receive a post-con survey, and then no further emails from us, unless we need to contact you individually. If you wish to opt out of the survey email, please email us at <a href="mailto:registration@balticon.org" target="_blank">registration@balticon.org</a>.
</p>
<p style="text-align:center;font-size:75%">
	Baltimore Science Fiction Society, Inc.<br>
	PO Box 686<br>
	Baltimore, MD 21203-0686<br>
	Phone: (410) JOE-BSFS (563-2737)<br>
</p></body></html>
EOT;

if($test) {
    $html= "THIS IS A TEST\n\n" . $html;
}

return $html;
}

function preConEmail_last_TEXT($test) {
  $con = get_con();
  $ini = get_conf('reg');

$conlabel = $con['label'];

$text = <<<EOT
Hello!

$conlabel is almost upon us! You are receiving this email because your email address is associated with a valid registration to attend this year’s convention. To check the status of your badge or the rest of your family, you can always visit: https://reg.balticon.org/onlineReg/checkreg.php

This year, we are again at the Renaissance Baltimore Harborplace Hotel, at 202 East Pratt Street, Baltimore, MD 21202. Badges can be picked up or purchased at Balticon Registration, which is on the 5th floor in the Maryland Ballroom Foyer next to the Watertable Restaurant. Since parking in a city can be expensive, we recommend using mass transit if at all possible. You can find information at https://www.balticon.org/wp53/hotel/mass-transit/. If you are arriving by car, we strongly recommend pre-purchasing your parking. Details are available on our website at https://www.balticon.org/wp53/hotel/parking/.

Our Programming team has put together a great schedule for us this year, and you can take a look at it at https://schedule.balticon.org/ . Information about other activities, as well as our Guests of Honor, can be found on our website at https://balticon.org/

The Baltimore Science Fiction Society (BSFS) is dedicated to providing a comfortable and harassment-free environment for everyone at Balticon and other BSFS-sponsored events. For specific information, including our full Anti-Harassment Policy, see http://bsfs.org/policy.htm.

If you have any further questions, please feel free to contact us at feedback@balticon.org, or visit our website for information on how to contact individual departments.

See you at the convention!


Where do I get my badge?
When you arrive at the hotel, take the elevators up to the 5th floor. Follow the signs to the Maryland Ballroom Foyer, where you’ll see a banner for Balticon Registration. This is NOT the same area as Hotel Registration. Get in line for Check-In, and have your ID ready.
Registration hours are:
Friday 1pm – 10pm
Saturday 8:45am – 7pm
Sunday 8:45am – 5pm
Monday 10am – 1:30pm

Do I have to show ID?
If you pre-purchased a badge, we would like to make sure that no one else picks up your badge. We are very reasonable about what we consider valid ID; we just need to know the badge is going to the right person. However, if you really don’t want to use your ID, you are always welcome to pay cash at the door!

Can I pick up my spouse/girlfriend/child’s membership as well as my own?
Maybe! You can pick up someone else’s membership IF one of the following is true:
The two memberships share a last name.
The two memberships share an address.
The two memberships were purchased on the same transaction.
You have ID for both memberships.
If you are picking up a membership for someone else, we will also request that you leave us with a cell phone number where we can reach you at con, in case they come looking for their badge at Registration before they find you.

I changed my mind about my badge name! I just came up with the coolest thing, but I put something on my form that I hate now!
That’s okay! When you come to pick up your badge, just let us know you’d like to change your badge name before we print it out, and we can make the change at the door.

We don’t need no stinkin’ badges!
Our badges don’t stink, and yes, you do need one. All Balticon members are required to be have a badge at all times in Balticon spaces.  The badge should be worn so as to be clearly visible and must be presented to any Balticon volunteer checking badges on behalf of the convention.  We encourage members to wear their badges above the waist because a higher number of badges are lost when worn at hip level. If you do lose your badge, you can check at Registration or the Lost & Found at Ops to see if it has been turned in.

Most conventions require you to purchase a new membership if your original badge is lost and has not been found, and that is an option. Such purchases are not refundable if the badge is later located. Alternatively, if the registration lead can confirm that someone has purchased or otherwise been granted a currently valid membership to Balticon, they may, at their discretion, issue a replacement badge.  A donation of at least $20 to BSFS Books For Kids is requested for that service.

What about Participants, Dealers, and Artists?
You should be receiving or have already received a separate email from your respective departments soon with further information. Your badge will still be at Registration for you!
EOT;

if($test) {
    $text = "THIS IS A TEST\n\n" . $text;
}

return $text;
}
