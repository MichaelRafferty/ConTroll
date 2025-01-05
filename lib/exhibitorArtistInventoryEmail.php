<?php
// emailArtistInventoryReq - send customized email for the artist for their information about the art show now that they have paid
//      uses:
//          artistsite: URL to artist site
//          custom text valies: : exhibitor/index/email/onsiteInvHTML, onsiteInvText, mailinInvHTML, mailinInvText
//      macros to be replaced in those files:
//        <<EXHIBITOR_NAME>>: name from the exhibitor record (artist full name)
//        <<CONTACT_NAME>>: name from the exhibitor years record (contact full name)
//        <<ARTIST_NUMBER>>: number assigned to this artist
//        <<REGION_NAME>>: Name of the region (Art Show) where they bought space
//        <<CON_NAME>>: Name of the con from the config file
//        <<ARTIST_PORTAL>>: URL to artist portal from the config file
//        <<OWNER_NAME>>: NAME OF THE REGION OWNERS
//        <<OWNER_EMAIL>>: Email address for the owner of this region

function emailArtistInventoryReq($regionYearId, $type): bool|array {
    $con = get_conf('con');
    $vendor = get_conf("vendor");

    // load the custom text fields
    loadCustomText('exitibitor', 'index',null, true);

    $artistOnSiteInventoryText = returnCustomText('email/onsiteInvText');
    $artistOnSiteInventoryHTML = returnCustomText('email/onsiteInvHTML');
    $artistMailInInventoryText = returnCustomText('email/mailinInvText');
    $artistMailInInventoryHTML = returnCustomText('email/mailinInvHTML');

    if ($artistOnSiteInventoryText == '' && $artistOnSiteInventoryHTML == '' && $artistMailInInventoryText == '' && $artistMailInInventoryHTML == '') {
        return false; // no email templates available to send
    }

    // get information about this space/artist
    $artQuery = <<<EOS
SELECT e.artistName, e.exhibitorName, e.exhibitorEmail, exY.contactName, exY.contactEmail,
       exY.mailin, exRY.exhibitorNumber, eR.name, eRY.ownerName, eRY.ownerEmail, eT.portalType, eT.usesInventory
FROM exhibitorRegionYears exRY
JOIN exhibitorYears exY ON exRY.exhibitorYearId = exY.id
JOIN exhibitors e ON exY.exhibitorid = e.id
JOIN exhibitsRegionYears eRY ON exRY.exhibitsRegionYearId = eRY.id
JOIN exhibitsRegions eR ON eRY.exhibitsRegion = eR.id
JOIN exhibitsRegionTypes eT ON eT.regionType = eR.regionType
WHERE exRY.id = ?
EOS;

    $artR = dbSafeQuery($artQuery, 'i', array($regionYearId));
    if ($artR === false || $artR->num_rows < 1) {
        error_log("Exhibitor Region Year ID $regionYearId not found");
        return false; // no artist to send to, this is really an error
    }
    $artL = $artR->fetch_assoc();
    $artR->free();

    if ($artL['usesInventory'] != 'Y')
        return false; // this space doesn't use inventory don't send the artist inventory form, this is not an error.

    // load the files
    $txtmsg = NULL;
    $htmlmsg = NULL;
    if ($artL['mailin'] == 'Y') {
        $txtmsg = $artistMailInInventoryText == '' ? null : $artistMailInInventoryText;
        $htmlmsg = $artistMailInInventoryHTML == '' ? null : $artistMailInInventoryHTML;
    } else {
        $txtmsg = $artistOnSiteInventoryText == '' ? null : $artistOnSiteInventoryText;
        $htmlmsg = $artistOnSiteInventoryHTML == '' ? null : $artistOnSiteInventoryHTML;
    }

    $emails = artistEamilReplaceTokens($txtmsg, $htmlmsg, $artL);

    $subject = ($type == 'Reminder' ? "Reminder to P" : "P") . "lease enter your art items for " . $con['conname'] . ' ' . $artL['name'];

    $return_arr = send_email($artL['ownerEmail'], $artL['exhibitorEmail'], $artL['contactEmail'], $subject, $emails[0], $emails[1]);

    if (array_key_exists('error_code', $return_arr)) {
        $error_code = $return_arr['error_code'];
    } else {
        $error_code = null;
    }

    if (array_key_exists('email_error', $return_arr)) {
        $error_msg = $return_arr['email_error'];
    } else {
        $error_msg = null;
    }
    return array($error_code, $error_msg);
}

// artistEmailReplaceTokens: replace tokens in message
function artistEamilReplaceTokens($messageTxt, $messageHtml, $valArray): array {
    $con = get_conf('con');
    $vendor = get_conf('vendor');
    $tokens = [
        'EXHIBITOR_NAME' => $valArray['exhibitorName'],
        'ARTIST_NAME' => $valArray['artistName'],
        'CONTACT_NAME' => $valArray['contactName'],
        'ARTIST_NUMBER' => $valArray['exhibitorNumber'],
        'REGION_NAME' => $valArray['name'],
        'CON_NAME' => $con['conname'],
        'ARTIST_PORTAL' => $vendor['artistsite'],
        'OWNER_NAME' => $valArray['ownerName'],
        'OWNER_EMAIL' => $valArray['ownerEmail'],
    ];

    foreach ($tokens AS $key => $val) {
        if ($messageTxt != null) {
            $messageTxt = strip_tags(str_replace("<<$key>>", $val, $messageTxt));
        }
        if ($messageHtml != null) {
            $messageHtml = str_replace("<<$key>>", $val, $messageHtml);
        }
    }
    return array($messageTxt, $messageHtml);
}
