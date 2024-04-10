<?php
// webArtistControlSheet.php - creates the control sheet as a web page for printing

function webArtistControlSheet($regionYearId, $region, $response) {
    $artistQ = <<<EOS
SELECT e.*, exY.conid,exY.mailin,exY.contactName,exY.contactPhone, exY.contactEmail, exRY.agentPerid, exRY.agentRequest, exRY.exhibitorNumber,
       p.first_name, p.last_name, p.middle_name, p.suffix, p.phone, p.email_addr
FROM exhibitorRegionYears exRY
JOIN exhibitorYears exY ON exY.id = exRY.exhibitorYearId
JOIN exhibitors e ON e.id = exY.exhibitorId
LEFT OUTER JOIN perinfo p ON p.id = exRY.agentPerid
WHERE exRY.exhibitorYearId=? AND exRY.exhibitsRegionYearId = ?;
EOS;

    $artistR = dbSafeQuery($artistQ, 'ii', array($regionYearId, $region));
    if ($artistR == false || $artistR->num_rows == 0) {
        $response['error'] = 'Error retrieving Artist information for control sheet, please seek assistance';
        echo "Error retrieving Artist information for control sheet, please seek assistance\n";
        return $response;
    }

    $artist = $artistR->fetch_assoc();
    $artistR->free();

    $con = get_con();
    $conname = $con['label'];

    $title = "$conname Art Control Sheet for " . $artist['exhibitorName'];
    control_sheet_page_init($title);

    ?>
<body id='controlSheetBody'>
<div class='container-fluid'>
    <div class='row'>
        <div class='col-sm-12'><h2>
            <?php echo $title . PHP_EOL; ?>
            </h2></div>
    </div>
    <div class="row">
        <div class="col-sm-12"><h4>Artist &amp; Agent Information</h4></div>
    </div>
    <div class="row">
        <div class='col-sm-12'><b>Artist Number: <?php echo $artist['exhibitorNumber']; ?></b></div>
    </div>
    <div class="row"><div class="col-sm-12"><div class="container-fluid">
    <div class="row">
        <div class="col-sm-4 border border=1 border-black">Artist Name: <?php echo $artist['exhibitorName'];?></div>
        <div class="col-sm-4 border border=1 border-black">Email: <?php echo $artist['exhibitorEmail'];?></div>
        <div class="col-sm-4 border border=1 border-black">Phone: <?php echo $artist['exhibitorPhone'];?></div>
    </div>
    <div class='row'>
        <div class='col-sm-4 border border=1 border-black'>Address:</div>
        <div class="col-sm-8 border border=1 border-black"><?php echo $artist['addr']; ?></div>
    </div>
    <?php if (array_key_exists('addr2', $artist) && isset($artist['addr2']) && $artist['addr2'] != '') { ?>
    <div class='row'>
        <div class='col-sm-4 border border=1 border-black'></div>
        <div class='col-sm-8 border border=1 border-black'><?php echo $artist['addr2']; ?></div>
    </div>
    <?php } ?>
    <div class='row'>
        <div class='col-sm-4 border border=1 border-black'>City/State/Zip:</div>
        <div class='col-sm-8 border border=1 border-black'><?php echo $artist['city'] . ", " . $artist['state'] . " " . $artist['zip']; ?></div>
    </div>
    <div class='row'>
        <div class='col-sm-4 border border=1 border-black'>Country:</div>
        <div class='col-sm-8 border border=1 border-black'><?php echo $artist['country']; ?></div>
    </div>
    <div class='row'>
        <div class='col-sm-12 border border=1 border-black'>Agent: <?php
            if (array_key_exists('agentPerid', $artist) && $artist['agentPerid'] > 0) {
                $aname = TRIM(TRIM(TRIM($artist['first_name'] . ' ' . $artist['middle_name']) . ' ' . $artist['last_name']) . ' ' . $artist['suffix']);
                $aperid = $artist['agentPerid'];
                $aemail = $artist['email_addr'];
                $aphone = $artist['phone'];
                echo "$aperid: $aname,  $aemail,  $aphone";
            }
            ?></div>
    </div>
    </div></div></div>
    <div class="row mt-3">
        <div class="col-sm-12"><h4>Alternate Contact/Shipping Information:</h4></div>
    </div>
    <div class='row'><div class='col-sm-12'><div class='container-fluid'>
    <div class="row">
        <div class="col-sm-2 border border=1 border-black">Alternate Contact:</div>
        <div class="col-sm-10 border border=1 border-black"><?php echo $artist['contactName']; ?></div>
    </div>
    <div class='row'>
        <div class='col-sm-2 border border=1 border-black'>Phone:</div>
        <div class='col-sm-10 border border=1 border-black'><?php echo $artist['contactPhone']; ?></div>
    </div>
    <div class='row'>
        <div class='col-sm-2 border border=1 border-black'>Email:</div>
        <div class='col-sm-10 border border=1 border-black'><?php echo $artist['contactEmail']; ?></div>
    </div>
    <div class='row'>
        <div class='col-sm-2 border border=1 border-black'>Shipping Info:</div>
        <div class='col-sm-10 border border=1 border-black'>Ship to:</div>
    </div>
    <div class='row'>
        <div class='col-sm-2 border border=1 border-black'>Company:</div>
        <div class='col-sm-10 border border=1 border-black'><?php echo $artist['shipCompany']; ?></div>
    </div>
    <div class='row'>
        <div class='col-sm-2 border border=1 border-black'>Address:</div>
        <div class='col-sm-10 border border=1 border-black'><?php echo $artist['shipAddr']; ?></div>
    </div>
    <?php if (array_key_exists('shipAddr2', $artist) && isset($artist['shipAddr2']) && $artist['shipAddr2'] != '') { ?>
        <div class='row'>
            <div class='col-sm-2 border border=1 border-black'></div>
            <div class='col-sm-10 border border=1 border-black'><?php echo $artist['shipAddr2']; ?></div>
        </div>
    <?php } ?>
    <div class='row'>
        <div class='col-sm-2 border border=1 border-black'>City/State/Zip:</div>
        <div class='col-sm-10 border border=1 border-black'><?php echo $artist['shipCity'] . ', ' . $artist['shipState'] . ' ' . $artist['shipZip']; ?></div>
    </div>
    <div class='row'>
        <div class='col-sm-2 border border=1 border-black'>Country:</div>
        <div class='col-sm-10 border border=1 border-black'><?php echo $artist['shipCountry']; ?></div>
    </div>
    </div></div></div>

    <?php

    // now get art info
    $itemSQL = <<<EOS
SELECT e.exhibitorName, exRY.exhibitorNumber, aI.title, aI.item_key, aI.min_price, aI.sale_price, aI.original_qty, aI.quantity, aI.material, aI.type, aI.status,
       aI.bidder, aI.final_price, e.id, eR.name,
       p.first_name, p.last_name, p.middle_name, p.suffix, p.phone, p.email_addr
FROM exhibitorRegionYears exRY
JOIN exhibitorYears exY ON exY.id = exRY.exhibitorYearId
JOIN exhibitors e ON e.id = exY.exhibitorId
JOIN artItems aI ON aI.exhibitorRegionYearId = exRY.id 
JOIN exhibitsRegionYears eRY ON exRY.exhibitsRegionYearId = eRY.id
JOIN exhibitsRegions eR ON eRY.exhibitsRegion = eR.id
LEFT OUTER JOIN perinfo p ON aI.bidder = p.id
WHERE exRY.exhibitorYearId=? AND exRY.exhibitsRegionYearId = ?
ORDER BY aI.item_key
EOS;

    $itemR = dbSafeQuery($itemSQL, 'ii', array($regionYearId, $region));
    if ($itemR == false) {
        $response['error'] = 'Error retrieving art items for control sheet, please seek assistance';
        echo "Error retrieving art items for control sheet, please seek assistance\n";
        return $response;
    }
    if ($itemR->num_rows == 0) {
        ?>
<div class='row mt-3'>
    <div class='col-sm-12'><h4>No art found for this artist</h4></div>
</div>
</body>
<?php
        $response['num_rows'] = $itemR->num_rows;
        $response['status'] = 'No art found requiring bid sheets';
        return $response;
    }

// load data array
    $artItems = [];
    while ($artItem = $itemR->fetch_assoc()) {
        $artItems[] = $artItem;
    }

    $curLocale = locale_get_default();
    $dolfmt = new NumberFormatter($curLocale == 'en_US_POSIX' ? 'en-us' : $curLocale, NumberFormatter::CURRENCY);

    ?>
    <div class='row mt-3'>
        <div class='col-sm-12'><h4>Artwork</h4></div>
    </div>
    <div class='row'><div class='col-sm-12'><div class='container-fluid'>

    <?php
    $titleNeeded = 0;
    foreach ($artItems as $artItem) {
        $titleNeeded--;
        if ($titleNeeded <= 0) {
            ?>
            <div class='row'>
                <div class='col-sm-3 p-0 m-0'>
                    <div class='container-fluid'>
                        <div class='row'>
                            <div class='col-sm-2 m-0 border border=1 border-black'>Piece No.</div>
                            <div class='col-sm-10 m-0 border border=1 border-black'>Title</div>
                        </div>
                    </div>
                </div>
                <div class="col-sm-2 p-0 m-0">
                    <div class="container-fluid">
                        <div class="row">
                            <div class="col-sm-3 m-0 border border=1 border-black">Type</div>
                            <div class="col-sm-9 m-0 border border=1 border-black">Material<br/>&nbsp;</div>
                        </div>
                    </div>
                </div>
                <div class='col-sm-4 p-0 m-0'>
                    <div class='container-fluid'>
                        <div class='row'>
                            <div class='col-sm-3 m-0 border border=1 border-black'>Min bid or Ins Value</div>
                            <div class='col-sm-3 m-0 border border=1 border-black'>Quick Sale or Print Price</div>
                            <div class='col-sm-1 m-0 ps-1 pe-1 border border=1 border-black'>Orig Qty</div>
                            <div class='col-sm-1 m-0 ps-1 pe-1 border border=1 border-black'>Cur. Qty</div>
                            <div class='col-sm-2 m-0 border border=1 border-black'>Location</div>
                            <div class='col-sm-2 m-0 border border=1 border-black'>Status</div>
                        </div>
                    </div>
                </div>
                <div class='col-sm-3 p-0 m-0'>
                    <div class='container-fluid'>
                        <div class='row'>
                            <div class='col-sm-3 m-0 ps-0 pe-0 border border=1 border-black'>Winning Bid</div>
                            <div class='col-sm-4 m-0 border border=1 border-black'>Bidder<br/>&nbsp;</div>
                            <div class='col-sm-5 border border=1 border-black'>Bidder Email</div>
                        </div>
                    </div>
                </div>
            </div>
            <?php
            $titleNeeded = 10;
        }
        $winnerName = TRIM(TRIM(TRIM($artItem['first_name'] . ' ' . $artItem['middle_name']) . ' ' . $artItem['last_name']) . ' ' . $artItem['suffix']);
        $winnerPerid = $artItem['bidder'];
        $winnerEmail = $artItem['email_addr'];
        ?>
            <div class='row'>
                <div class='col-sm-3 p-0 m-0'>
                    <div class='container-fluid'>
                        <div class='row'>
                            <div class='col-sm-2 m-0 border border=1 border-black text-end'><?php echo $artItem['item_key']; ?><br/>&nbsp;</div>
                            <div class='col-sm-10 m-0 border border=1 border-black'><?php echo $artItem['title']; ?></div>
                        </div>
                    </div>
                </div>
                <div class='col-sm-2 p-0 m-0'>
                    <div class='container-fluid'>
                        <div class='row'>
                            <div class='col-sm-3 m-0 border border=1 border-black'><?php echo $artItem['type']; ?><br/>&nbsp;</div>
                            <div class='col-sm-9 m-0 border border=1 border-black'><?php echo $artItem['material']; ?></div>
                        </div>
                    </div>
                </div>
                <div class='col-sm-4 p-0 m-0'>
                    <div class='container-fluid'>
                        <div class='row'>
                            <div class='col-sm-3 m-0 border border=1 border-black text-end'><?php echo $artItem['min_price'] ? $dolfmt->formatCurrency((float)$artItem['min_price'], 'USD') : '&nbsp;'; ?></div>
                            <div class='col-sm-3 m-0 border border=1 border-black text-end'><?php echo $artItem['sale_price'] ? $dolfmt->formatCurrency((float)$artItem['sale_price'], 'USD') : '&nbsp;'; ?></div>
                            <div class='col-sm-1 border border=1 border-black text-end'><?php echo $artItem['original_qty']; ?></div>
                            <div class='col-sm-1 border border=1 border-black text-end'><?php echo $artItem['quantity']; ?></div>
                            <div class='col-sm-2 border border=2 border-black'>&nbsp;<br/>&nbsp;</div>
                            <div class='col-sm-2 border border=2 border-black'><?php echo $artItem['status']; ?></div>
                        </div>
                    </div>
                </div>
                <div class='col-sm-3 p-0 m-0'>
                    <div class='container-fluid'>
                        <div class='row'>
                            <div class='col-sm-3 m-0 ps-1 border border=1 border-black'><?php
                                echo ($artItem['final_price'] ? $dolfmt->formatCurrency((float)$artItem['final_price'], 'USD') : '&nbsp;') . '<br/>&nbsp;';
                                ?></div>
                            <div class='col-sm-4 m-0 border border=1 border-black'><?php echo $winnerName; ?></div>
                            <div class='col-sm-5 border border=1 border-black'><?php echo $winnerEmail; ?></div>
                        </div>
                    </div>
                </div>
            </div>
        <?php
    }
    ?>
    </div></div></div>
</body>
<?php
    return $response;
}

function control_sheet_page_init($title) {
    echo <<<EOF
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>$title</title>
    <link href='/vendor/css/style.css' rel='stylesheet' type='text/css' />
    <link href='/csslib/jquery-ui-1.13.1.css' rel='stylesheet' type='text/css' /> 
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css' rel='stylesheet' integrity='sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH' crossorigin='anonymous'>
    
    <script src='https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js' integrity='sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz' crossorigin='anonymous'></script>
    <script type='text/javascript' src='/jslib/jquery-3.7.1.min.js'></script>
    <script type='text/javascript' src='/jslib/jquery-ui.min-1.13.1.js'></script>
</head>
EOF;
}
?>
