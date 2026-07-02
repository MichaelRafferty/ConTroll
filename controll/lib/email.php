<?php

function refundEmail_HTML($test, $email, $tid) {;
    $con = get_conf('con');
    $conid = $con['id'];

    $conlabel = $con['label'];
    $canceldate = getConfValue('reg', 'cancel_date');
    $refundemail = $con['refundemail'];
    $conname = $con['conname'];
    $orgname = $con['org'];
    $orgabv = $con['orgabv'];
    $regemail = $con['regadminemail'];

    $url = getConfValue('reg', 'server') . "/cancelation.php";
    $url2 = getConfValue('reg', 'server');
    $regpage = getConfValue('con', 'regpage');
    $homepage = getConfValue('con', 'website');

    $transQ = <<<EOS
SELECT T.paid, M.label, M.memAge, P.first_name, P.last_name, P.badge_name, P.badgeNameL2, R.paid
FROM transaction AS T
JOIN reg R ON (R.create_trans=T.id)
JOIN perinfo P ON (P.id=R.perid)
JOIN memLabel M ON (M.id=R.memId)
JOIN payments Y ON (Y.transid=T.id)
WHERE T.id = ? AND M.memCategory IN ('standard', 'yearahead') AND M.conid=?;
EOS;

    $transR = dbSafeQuery($transQ, 'ii', array ($tid, $conid));

    $names = "<ul>\n";
    while ($trans = $transR->fetch_assoc()) {
        $names .= "\t<li>" . $trans['first_name'] . " " . $trans['last_name'];
        if ($trans['badge_name'] != '' || $trans['badgeNameL2'] != '') {
            $bn = badgeNameDefault($trans['badge_name'], $trans['badgeNameL2'], $trans['first_name'], $trans['last_name']);
            $bn = str_replace('<br/>', '/', $bn);
            $names .= " ($bn)";
        }
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
<DD>For online purchases, we can refund your purchase to your original method of payment. For at con or mail-in purchases, we can mail you a check to refund 
your membership(s). If the refund needs to be done another way, please contact $regemail so that we can discuss options. We will process the refund as soon as possible.</DD>
</DL>

<p>You can let us know your preference by visiting <a href='$url?email=$email&tid=$tid'>our Membership Cancelation Page</a> at <a href='$url'>$url</a> and entering your email address and transaction number (<strong>$tid</strong>). You can also reach the page by following links from <a href='$url2'>$conname Online Registration</a> or the <a href="$regpage">Registration page</a> on the <a href="$homepage>$conname Website</a>. If we do not hear from you by <strong>within two weeks</strong>, we will process your membership as a Rollover, and you will be pre-registered for the next $conname. Any difference between the amount paid and pre-registration rate for the next $conname will be considered a (very appreciated) tax-deductible donation to $orgabv.</p>

<p>Memberships will be processed as staff is available. We hope to have all rollovers, refunds, and donations processed within 120 days. Please remember that $conname is a 100% volunteer-run organization, and we thank you in advance for your patience.</p>

EOT;

    if ($test) {
        $text = "THIS IS A TEST\n\n" . $text;
    }

    return $text;
}

function refundEmail_TEXT($test, $email, $tid) {
    $con = get_conf('con');
    $conid = $con['id'];

    $conlabel = $con['label'];
    $canceldate = getConfValue('reg', 'cancel_date');
    $refundemail = $con['refundemail'];
    $conname = $con['conname'];
    $orgname = $con['org'];
    $orgabv = $con['orgabv'];
    $regemail = $con['regadminemail'];

    $url = getConfValue('reg', 'server') . '/cancelation.php';

    $transQ = <<<EOS
SELECT T.paid, M.label, M.memAge, P.first_name, P.last_name, P.badge_name, P.badgeNameL2, R.paid
FROM transaction T
JOIN reg R ON (R.create_trans=T.id)
JOIN perinfo P ON (P.id=R.perid)
JOIN memLabel M ON (M.id=R.memId)
JOIN payments Y ON (Y.transid=T.id)
WHERE T.id = ? AND M.memCategory IN ('standard', 'yearahead') AND M.conid=?;
EOS;

    $transR = dbSafeQuery($transQ, 'ii', array ($tid, $conid));

    $names = "";
    while ($trans = $transR->fetch_assoc()) {
        $bn = badgeNameDefault($trans['badge_name'], $trans['badgeNameL2'], $trans['first_name'], $trans['last_name']);
        $bn = str_replace('<br/>', '/', $bn);
        $bn = str_replace('<i>', '', $bn);
        $bn = str_replace('</i>', '', $bn);
        $names .= $trans['first_name'] . " " . $trans['last_name'] . " ($bn)\n";
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

    if ($test) {
        $html = "THIS IS A TEST\n\n" . $test;

        return $text;
    }
}
