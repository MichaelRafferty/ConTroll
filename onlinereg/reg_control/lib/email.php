<?php

function refundEmail_HTML($test, $email, $tid) {
  $ini = get_conf('reg');
  $con = get_conf('con');
  $conid=$con['id'];

  $conlabel = $con['label'];
  $canceldate = $ini['cancel_date'];
  $refundemail = $con['refundemail'];
  $conname = $con['conname'];
  $orgname = $con['org'];
  $orgabv = $con['orgabv'];
  $regemail = $con['regadminemail'];

  $url = $ini['server'] . "/cancelation.php";
  $url2 = $ini['server'];
  $regpage = $con['regpage'] ;
  $homepage = $con['website'];

  $transQ = <<<EOS
SELECT T.paid, M.label, M.memAge, P.first_name, P.last_name, P.badge_name, R.paid
FROM transaction as T
JOIN reg R ON (R.create_trans=T.id)
JOIN perinfo P ON (P.id=R.perid)
JOIN memLabel M ON (M.id=R.memId)
JOIN payments Y ON (Y.transid=T.id)
WHERE T.id = ? AND M.memCategory in ('standard', 'yearahead') AND M.conid=?;
EOS;

  $transR = dbSafeQuery($transQ, 'ii', array($tid, $conid));

  $names = "<ul>\n";
  while($trans = fetch_safe_assoc($transR)) {
    $names .= "\t<li>" . $trans['first_name'] . " " . $trans['last_name'];
    if($trans['badge_name'] != '') {$names .= " (" .$trans['badge_name'] . ")";}
    $names .= "</li>\n";
  }
  $names .= "</ul>\n";

$text = <<<EOT
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"><html><head><META http-equiv="Content-Type" content="text/html; charset=utf-8"></head><body>

<p>As we announced on $canceldate, we had to make the unfortunate decision to cancel $conlabel. Your email address is associated with a membership to $conlabel.</p>

<p>Our records show this email address is associated with Transaction #<strong>$tid</strong>, which has the following memberships:<p>
<strong>$names</strong>

<p>If this is an error, please contact us at <a href="$refundemail">$refundemail</a>. Otherwise, please visit <a href='$url?email=$email&tid=$tid'>our Membeship Cancelation page</a> to tell us how you want your membership(s) processed.</p>

<p>We are offering three options for what to do with your membership: donation, rollover, or refund.</p>

<DL>
<DT><strong>Donation</strong></DT>
<DD>$conname is run by the $orgname ($orgabv) which depends on memberships from $conname for nearly all its yearly budget. Please consider donating the cost of your membership(s) to the $orgabv. As a 501(c)3 non-profit, all donations to $orgabv are tax-deductible (please contact your tax professionals for full details).</DD>

<DT><strong>Rollover</strong></DT>
<DD>A rollover will convert your existing $conlabel membership(s) to a membership for the next $conname, at no additional cost to you.</DD>

<DT><strong>Refund</strong></DT>
<DD>For online purchases, we can refund your purchase to your original method of payment. For at con or mail in purchases, we can mail you a check to refund your membership(s). If the refund needs to be done another way, please contact $regemail so that we can discuss options. We will process the refund as soon as possible.</DD>
</DL>

<p>You can let us know your preference by visiting <a href='$url?email=$email&tid=$tid'>our Membership Cancelation Page</a> at <a href='$url'>$url</a> and entering your email address and transaction number (<strong>$tid</strong>). You can also reach the page by following links from <a href='$url2'>$conname Online Registration</a> or the <a href="$regpage">Registration page</a> on the <a href="$homepage>$conname Website</a>. If we do not hear from you by <strong>within two weeks</strong>, we will process your membership as a Rollover, and you will be pre-registered for the next $conname. Any difference between the amount paid and pre-registration rate for the next $conname will be considered a (very appreciated) tax-deductible donation to $orgabv.</p>

<p>Memberships will be processed as staff is available. We hope to have all rollovers, refunds, and donations processed within 120 days. Please remember that $conname is a 100% volunteer-run organization, and we thank you in advance for your patience.</p>

EOT;

return $text;
}

function refundEmail_TEXT($test, $email, $tid) {
    $ini = get_conf('reg');
    $con = get_conf('con');
    $conid=$con['id'];

    $conlabel = $con['label'];
    $canceldate = $ini['cancel_date'];
    $refundemail = $con['refundemail'];
    $conname = $con['conname'];
    $orgname = $con['org'];
    $orgabv = $con['orgabv'];
    $regemail = $con['regadminemail'];

    $url = $ini['server'] . "/cancelation.php";

  $transQ = <<<EOS
SELECT T.paid, M.label, M.memAge, P.first_name, P.last_name, P.badge_name, R.paid
FROM transaction T
JOIN reg R ON (R.create_trans=T.id)
JOIN perinfo P ON (P.id=R.perid)
JOIN memLabel M ON (M.id=R.memId)
JOIN payments Y ON (Y.transid=T.id)
WHERE T.id = ? AND M.memCategory in ('standard', 'yearahead') AND M.conid=?;
EOS;

  $transR = dbSafeQuery($transQ, 'ii', array($tid, $conid));

  $names = "";
  while($trans = fetch_safe_assoc($transR)) {
    $names .= $trans['first_name'] . " " . $trans['last_name'] . " (" .$trans['badge_name'] . ")\n";
  }

$text = <<<EOT
As we announced on $canceldate, we had to make the unfortunate decision to cancel $conlabel. Your email address is associated with a membership to $conlabel.

Our records show this email address is associated with Transaction #$tid, which has the following memberships:
$names

If this is an error, please contact us at $refundemail. Otherwise, please visit $url?email=$email&tid=$tid to tell us how you want your membership(s) processed.

We are offering three options for what to do with your membership: donation, rollover, or refund.

Donation
$conname is run by the  $orgname ($orgabv) which depends on memberships from $conname  for nearly all its yearly budget. Please consider donating the cost of your membership(s) to $orgabv. As a 501(c)3 non-profit, all donations to $orgabv are tax-deductible (please contact your tax professionals for full details).

Rollover
A rollover will convert your existing $conlabel membership(s) to a membership for the next $conname, at no additional cost to you.

Refund
For online purchases, we can refund the value to your original method of payment. For at-con or mail-in purchases, we can mail a check to refund your membership(s). If the refund needs to be done another way, please contact $regemail so that we can discuss options. We will process your refund as soon as possible.

You can let us know your preference by visiting $url?email=$email&tid=$tid. You can also find this page by going to $url and entering your email address and transaction number ($tid). If we do not hear from you within two weeks, we will process your membership as a rollover, and you will be pre-registered for the next $conname. Any difference between the amount paid and pre-registration rate for the next $conname will be considered a very appreciated tax-deductible donation to $orgabv.

Memberships will be processed as staff is available. We hope to have all rollovers, refunds, and donations processed within 120 days. Please remember that $conname is a 100% volunteer-run organization, and we thank you in advance for your patience.

EOT;

return $text;
}


function preConEmail_last_HTML($test) {

  $ini = get_conf('reg');
  $con = get_conf('con');

  $conlabel = $con['label'];
  $conname = $con['conname'];
  $orgname = $con['org'];
  $orgabv = $con['orgabv'];
  $url = rtrim($ini['server'], '/');
  $hotelpage = $con['hotelwebsite'];
  $hotelname = $con['hotelname'];
  $hoteladdr = $con['hoteladdr'];
  $pickupareatext = $con['pickupareatext'];
  $addlpickuptext = $con['addlpickuptext'];
  $schedulepage = $con['schedulepage'];
  $homepage = $con['website'];
  $policypage = $con['policy'];
  $feedbackemail = $con['feedbackemail'];

  $html = <<<EOT
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"><html><head><META http-equiv="Content-Type" content="text/html; charset=utf-8"></head><body><p>Hello!</p>

<p>
	$conlabel is almost upon us! You are receiving this email because your email address is associated with a valid registration to attend this year’s convention. To check the status of your, or the rest of your family's, registration you can always visit: <a href="$url/checkReg.php" target="_blank">$url/checkReg.php</a>
</p>
<p>
	This year we are at the same hotel, which is now the <a href="$hotelpage" target="_blank">$hotelname</a>, at $hoteladdr. Badges can be picked up or purchased at $conname Registration, which is $pickupareatext. $addlpickuptext
</p>

<p>
	Our programming team has put together a great schedule for us this year, and you can take a look at it at <a href="$schedulepage" target="_blank">$schedulepage/</a> . Information about other activities, as well as our Guests of Honor, can be found on our website at <a href="$homepage" target="_blank">$homepage</a>.
</p>

<p>
	The $orgname ($orgabv) is dedicated to providing a comfortable and harassment-free environment for everyone at $conname and other $orgabv-sponsored events. For specific information, including our full Anti-Harassment Policy, see <a href="$policypage" target="_blank">$policypage</a>.
</p>
<p>
	If you have any further questions, please feel free to contact us at <a href="mailto:$feedbackemail" target="_blank">$feedbackemail</a>, or visit our website for information on how to contact individual departments.
</p>

<p>See you at the convention!</p>
<br>
EOT;
  $addlemailhtml= __DIR__ . "/../../../config/ConSpecificReminderEmailAddlHTML.txt";
  if (is_readable($addlemailhtml)) {
      $html .= file_get_contents($addlemailhtml);
  }
  if($test) {
      $html= "THIS IS A TEST\n\n" . $html;
  }
  return $html;
}

function preConEmail_last_TEXT($test) {
    $ini = get_conf('reg');
    $con = get_conf('con');

    $conlabel = $con['label'];
    $conname = $con['conname'];
    $orgname = $con['org'];
    $orgabv = $con['orgabv'];
    $url = rtrim($ini['server'], '/');
    $hotelname = $con['hotelname'];
    $hoteladdr = $con['hoteladdr'];
    $pickupareatext = $con['pickupareatext'];
    $addlpickuptext = $con['addlpickuptext'];
    $schedulepage = $con['schedulepage'];
    $homepage = $con['website'];
    $policypage = $con['policy'];
    $feedbackemail = $con['feedbackemail'];

  $text = <<<EOT
Hello!

$conlabel is almost upon us! You are receiving this email because your email address is associated with a valid registration to attend this year’s convention. To check the status of your, or the rest of your family's, registration you can always visit: $url/checkreg.php

This year we are at the same hotel, which is now the $hotelname</a>, at $hoteladdr. Badges can be picked up or purchased at $conname Registration, which is $pickupareatext. $addlpickuptext

Our programming team has put together a great schedule for us this year, and you can take a look at it at $schedulepage. Information about other activities, as well as our Guests of Honor, can be found on our website at $homepage.

The $orgname ($orgabv) is dedicated to providing a comfortable and harassment-free environment for everyone at $conname and other $orgabv-sponsored events. For specific information, including our full Anti-Harassment Policy, see $policypage.

If you have any further questions, please feel free to contact us at $feedbackemail, or visit our website for information on how to contact individual departments.

See you at the convention!
EOT;

  $addlemailtxt= __DIR__ . "/../../../config/ConSpecificReminderEmailAddlText.txt";
  if (is_readable($addlemailtxt)) {
      $text .= file_get_contents($addlemailtxt);
  }
  if($test) {
      $text = "THIS IS A TEST\n\n" . $text;
  }

  return $text;
}

function MarketingEmail_HTML($test) {

    $ini = get_conf('reg');
    $con = get_conf('con');
    $reg = get_conf('reg');

    $conlabel = $con['label'];
    $conname = $con['conname'];
    $orgname = $con['org'];
    $orgabv = $con['orgabv'];
    $url = rtrim($ini['server'], '/');
    $hotelpage = $con['hotelwebsite'];
    $hotelname = $con['hotelname'];
    $hoteladdr = $con['hoteladdr'];
    $pickupareatext = $con['pickupareatext'];
    $addlpickuptext = $con['addlpickuptext'];
    $schedulepage = $con['schedulepage'];
    $homepage = $con['website'];
    $policypage = $con['policy'];
    $feedbackemail = $con['feedbackemail'];
    $regsite = $reg['server'];

    $html = <<<EOT
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"><html><head><META http-equiv="Content-Type" content="text/html; charset=utf-8"></head><body><p>Hello!</p>

<p>
	$conlabel is almost upon us! You are receiving this email because your email address is associated with a valid registration to attend last year’s convention, but we don't have you registered for this year's convention. You can always register on-site, but you can save money by purchasing your membership in advance at <a href=$regsite" target-"_blank">$regsite</a>. To check the status of your, or the rest of your family's, registration  you can always visit: <a href="$url/checkReg.php" target="_blank">$url/checkReg.php</a>
</p>
<p>
	This year, we are again at the <a href="$hotelpage" target="_blank">$hotelname</a>, at $hoteladdr.  Please register for rooms as soon as possible as the block will be closing soon.
</p>

<p>
	Our programming team is putting together a great schedule for us this year, and you will soon be able to take a look at it at <a href="$schedulepage" target="_blank">$schedulepage</a> . Information about other activities, as well as our Guests of Honor, can be found on our website at <a href="$homepage" target="_blank">$homepage</a>.
</p>

<p>
	The $orgname ($orgabv) is dedicated to providing a comfortable and harassment-free environment for everyone at $conname and other $orgabv-sponsored events. For specific information, including our full Anti-Harassment Policy, see <a href="$policypage" target="_blank">$policypage</a>.
</p>
<p>
	If you have any further questions, please feel free to contact us at <a href="mailto:$feedbackemail" target="_blank">$feedbackemail</a>, or visit our website for information on how to contact individual departments.
</p>

<p>We hope to see you at the convention!</p>
<br>
EOT;
    $addlemailhtml= __DIR__ . "/../../../config/ConSpecificMarketingEmailAddlHTML.txt";
    if (is_readable($addlemailhtml)) {
        $html .= file_get_contents($addlemailhtml);
    }
    if($test) {
        $html= "THIS IS A TEST\n\n" . $html;
    }
    return $html;
}

function MarketingEmail_TEXT($test) {
    $ini = get_conf('reg');
    $con = get_conf('con');
    $reg = get_conf('reg');

    $conlabel = $con['label'];
    $conname = $con['conname'];
    $orgname = $con['org'];
    $orgabv = $con['orgabv'];
    $url = rtrim($ini['server'], '/');
    $hotelname = $con['hotelname'];
    $hoteladdr = $con['hoteladdr'];
    $pickupareatext = $con['pickupareatext'];
    $addlpickuptext = $con['addlpickuptext'];
    $schedulepage = $con['schedulepage'];
    $homepage = $con['website'];
    $policypage = $con['policy'];
    $feedbackemail = $con['feedbackemail'];
    $regsite = $reg['server'];

    $text = <<<EOT
Hello!

$conlabel is almost upon us! You are receiving this email because your email address is associated with a valid registration to attend last year’s convention, but we don't have you registered for this year's convention. You can always register on-site, but you can save money by purchasing your membership in advance at $regsite. To check the status of your, or the rest of your family's, memberships, you can always visit: $url/checkReg.php

This year, we are at the same hotel which is now the $hotelname, at $hoteladdr.  Please rsgister for rooms as soon as possible as the block will be closing soon.

Our programming team is putting together a great schedule for us this year, and you will be able to soon take a look at it at $schedulepage. Information about other activities, as well as our Guests of Honor, can be found on our website at $homepage.

The $orgname ($orgabv) is dedicated to providing a comfortable and harassment-free environment for everyone at $conname and other $orgabv-sponsored events. For specific information, including our full Anti-Harassment Policy, see $policypage.

If you have any further questions, please feel free to contact us at $feedbackemail, or visit our website for information on how to contact individual departments.

We hope to see you at the convention!

EOT;

    $addlemailtxt= __DIR__ . "/../../../config/ConSpecificMarketingEmailAddlText.txt";
    if (is_readable($addlemailtxt)) {
        $text .= file_get_contents($addlemailtxt);
    }
    if($test) {
        $text = "THIS IS A TEST\n\n" . $text;
    }

    return $text;
}
