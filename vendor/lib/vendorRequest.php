<?php

// vendor_showRequest -> show the current request and the change/cancel button
function vendor_showRequest($regionId, $regionName, $regionSpaces, $exhibitorSpaceList) {
    $dolfmt = new NumberFormatter('', NumberFormatter::CURRENCY);

    echo "Request pending authorization for:<br/>\n";
    foreach ($exhibitorSpaceList as $key => $spaceItem) {
        // limit to spaces for this region
        $spaceId = $spaceItem['spaceId'];
        if (array_key_exists($spaceId, $regionSpaces)) {
            $date = $spaceItem['time_requested'];
            $date = date_create($date);
            $date = date_format($date, 'F j, Y') . ' at ' . date_format($date, 'g:i A');
            echo $spaceItem['requested_description'] . " in " . $spaceItem['regionName'] . " for " . $dolfmt->formatCurrency($spaceItem['requested_price'], 'USD') .
                " at $date<br/>\n";
        }
    }
    echo "<button class='btn btn-primary' onclick='openReq($regionId, 1);'>Change/Cancel $regionName Space</button>";

}
