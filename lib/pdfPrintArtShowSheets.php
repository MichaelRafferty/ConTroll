<?php
// pdfPrintArtShowSheets.php - routines for creating the art show bid sheets, price tags and control sheets
require_once (__DIR__ . '/../Composer/vendor/autoload.php');
require_once ("pdfFunctions.php");

function pdfPrintShopPriceSheets($regionYearId, $region, $response) {
    $con = get_conf('con');
    if (array_key_exists('currency', $con)) {
        $currency = $con['currency'];
    } else {
        $currency = 'USD';
    }
// local constants for the sheets
    $margin = 0.25;
    $numcols = 3;
    $numrows = 4;
    $vsize = 1.6;
    $indent = 0.1;
    $blockheight = 0.35;
    $labelOffset = 0.06;
    $dataOffset = 0.22;

    $itemSQL = <<<EOS
SELECT e.exhibitorName, exRY.exhibitorNumber, aI.title, aI.item_key, aI.sale_price, aI.original_qty, aI.material, e.id, eR.name, 
       aI.id AS itemId, e.artistName
FROM exhibitorRegionYears exRY
JOIN exhibitorYears exY ON exY.id = exRY.exhibitorYearId
JOIN exhibitors e ON e.id = exY.exhibitorId
JOIN artItems aI ON aI.exhibitorRegionYearId = exRY.id 
JOIN exhibitsRegionYears eRY ON exRY.exhibitsRegionYearId = eRY.id
JOIN exhibitsRegions eR ON eRY.exhibitsRegion = eR.id
WHERE exRY.exhibitorYearId=? AND exRY.exhibitsRegionYearId = ? AND aI.type = 'print'
ORDER BY aI.item_key;
EOS;

    $itemR = dbSafeQuery($itemSQL, 'ii', array($regionYearId, $region));
    if ($itemR === false) {
        $response['error'] = "Error retrieving art items for print show, please seek assistance";
        echo "Error retrieving art items for print show, please seek assistance\n";
        return $response;
    }
    if ($itemR->num_rows == 0) {
        $response['num_rows'] = $itemR->num_rows;
        $response['status'] = 'No art found requiring price tags';
        echo "No art found requiring price tags\n";
        $itemR->free();
        return $response;
    }

    // compute the sheet title (ConShortName, Print Shop Copy Tag)
    $con = get_con();
    $conname = $con['label'];

    $vendor = get_conf('vendor');
    $title = $vendor['artistPriceTag'];
    if ($title == null || $title == '') {
        $title = "Unconfigured Print Shop Price Tag";
    }

    $useBarCode = false;
    if (array_key_exists('artistPriceTagBarcode', $vendor)) {
        $value = strtolower($vendor['artistPriceTagBarcode']);
        if ($value == '1' || $value == 'yes') {
            $useBarCode = true;
            $vsize += 0.25;
        }
    }

    // load data array
    $artItems = [];
    $numTags = 0;
    while ($artItem = $itemR->fetch_assoc()) {
        $artItems[] = $artItem;
        $numTags += $artItem['original_qty'];
    }
    $itemR->free();

    if ($artItems[0]['artistName'] == null || $artItems[0]['artistName'] == '') {
        $artistName = $artItems[0]['exhibitorName'];
    } else {
        $artistName = $artItems[0]['artistName'];
    }

    $pages = ceil($numTags / ($numrows * $numcols));

    $curLocale = locale_get_default();
    $dolfmt = new NumberFormatter($curLocale == 'en_US_POSIX' ? 'en-us' : $curLocale, NumberFormatter::CURRENCY);

    $pdf = new \Erkens\Fpdf\Barcode('L', 'in', 'Letter');
    initPDF($pdf, 0.008, 'Arial', '', 11);

    // computes from those offsets
    $hsize = ($pdf->GetPageWidth() - 2 * $margin) / $numcols;
    $firstrow = $margin + 0.3;

    pushFont('Arial', '', 14);
    $titlewidth = $pdf->getStringWidth($title);
    $titleoffset = ($hsize - (2 * $indent) - $titlewidth) / 2;
    popFont();

    // timestamp for printing when generated
    $createDate = date('Y/m/d h:i:s A');
    $fileDate = date('Y-m-d-H-i-s');

    $row = $numrows;
    $col = $numcols;
    $page = 0;
    foreach ($artItems as $print) {
        for ($copy = 1; $copy <= $print['original_qty']; $copy++) {
            // set up for next item
            $col++;
            if ($col >= $numcols) {
                $row++;
                $col = 0;
            }
            if ($row >= $numrows) {
                $row = 0;
                $pdf->AddPage();
                $page++;
                pushFont('Arial', 'B', 11);
                printXY($margin, $margin,"Price Tags for $conname's " . $print['name'] . "; Artist: " . $artistName);
                $fileLabel = str_replace(' ', '_', $conname . "_" . $print['name'] . "_" . $artistName);
                $y = $pdf->GetPageHeight() - ($margin);
                printXY($margin, $y,"Generated: $createDate");
                $pageStr = "Page $page of $pages";
                rightPrintXY(0,  $y, $pdf->GetPageWidth() - $margin,  $pageStr);
                popFont();
            }

            $v = $firstrow + $row * $vsize;
            $h = $margin + $col * $hsize;
            // draw outer box
            $pdf->Rect($h, $v, $hsize, $vsize);
            $h += $indent;
            $v += $indent;

            // now title header
            $isize = $hsize - 2 * $indent;
            $pdf->Rect($h, $v, $isize, $blockheight);
            pushFont('Arial', '', 14);
            centerPrintXY($h, $v + 0.16, $isize - 0.1, $title);
            popFont();

            // draw artist block
            $v += $blockheight;
            $pdf->Rect($h, $v, $isize * 0.8, $blockheight);
            $pdf->Rect($h + $isize * 0.8, $v, $isize * 0.2, $blockheight);
            pushFont('Arial', '', 5);
            printXY($h + 0.025, $v + $labelOffset,'ARTIST');
            printXY($h + 0.025 + $isize * 0.8, $v + $labelOffset,'ARTIST#');
            popFont();
            fitprintXY($h + 0.1, $v + $dataOffset, (0.8 * $isize), $artistName);
            printXY($h + 0.1 + (0.8 * $isize), $v + $dataOffset, $print['exhibitorNumber']);

            // draw print title block
            $v += $blockheight;
            $pdf->Rect($h, $v, $isize * 0.8, $blockheight);
            $pdf->Rect($h + $isize * 0.8, $v, $isize * 0.2, $blockheight);
            pushFont('Arial', '', 5);
            printXY($h + 0.025, $v + $labelOffset,'TITLE');
            printXY($h + 0.025 + $isize * 0.8, $v + $labelOffset,'PIECE#');
            popFont();

            fitprintXY($h + 0.1, $v + $dataOffset, ($isize * 0.8) - 0.2, $print['title']);
            printXY($h + 0.1 + (0.8 * $isize), $v + $dataOffset, $print['item_key']);

            // draw buyer/count/price line
            $v += $blockheight;
            $pdf->Rect($h, $v, $isize * 0.5, $blockheight);
            $pdf->Rect($h + $isize * 0.5, $v, $isize * 0.25, $blockheight);
            $pdf->Rect($h + $isize * 0.75, $v, $isize * 0.25, $blockheight);
            pushFont('Arial', '', 5);
            printXY($h + 0.025, $v + $labelOffset, 'BUYER (ART SHOW USE ONLY)');
            printXY($h + 0.025 + $isize * 0.5, $v + $labelOffset,'UNIT');
            printXY($h + 0.025 + $isize * 0.625, $v + $labelOffset, 'OF');
            printXY($h + 0.025 + $isize * 0.75, $v + $labelOffset, 'PRICE');
            popFont();

            printXY($h + 0.1 + (0.5 * $isize), $v + $dataOffset, $copy);
            printXY($h + 0.1 + (0.6 * $isize), $v + $dataOffset, $print['original_qty']);
            $priceFmt = $dolfmt->formatCurrency((float)$print['sale_price'], $currency);
            $pricewidth = $pdf->getStringWidth($priceFmt);
            printXY($h + (0.97 * $isize) - $pricewidth, $v + $dataOffset, $priceFmt);

            if ($useBarCode) {
                $v += $blockheight;
                $barcodeData = sprintf("%7.7d,%3.3d", $print['itemId'], $copy);
                $pdf->code128($h + $indent, $v + $labelOffset, $barcodeData, ($isize - (2 * $indent)) / 1.5, $blockheight - (2 * $labelOffset));
            }
        }
    }

    header('Content-Type: application/pdf');
    $fileLabel = preg_replace('/[^A-Za-z0-9_]/', '', $fileLabel);
    $filename = $fileLabel . '_' . $fileDate . '.pdf';
    header('Content-Disposition: inline; filename="' . $filename . '"');
    $output = $pdf->Output();
    print($output);
    $response['success'] = true;
    $response['message'] = "$numTags output on $pages pages";
    return $response;
}

function pdfPrintBidSheets($regionYearId, $region, $response) {
    $con = get_conf('con');
    if (array_key_exists('currency', $con)) {
        $currency = $con['currency'];
    } else {
        $currency = 'USD';
    }
    // get parameters for sizing
    $con = get_con();
    $conname = $con['label'];

    $vendor = get_conf('vendor');
    $title = null;
    if (array_key_exists('artistBidSheet', $vendor))
        $title = $vendor['artistBidSheet'];
   
    if ($title == null || $title == '') {
        $title = 'Unconfigured Art Show Bid Sheets';
    }

    $useBarCode = false;
    if (array_key_exists('artistPriceTagBarcode', $vendor)) {
        $value = strtolower($vendor['artistPriceTagBarcode']);
        if ($value == '1' || $value == 'yes') {
            $useBarCode = true;
        }
    }
    
    $bidlines = null;
    if (array_key_exists('artistBidSheetLines', $vendor))
        $bidlines = $vendor['artistBidSheetLines'];
    if ($bidlines == null || $bidlines == '' || !is_numeric($bidlines)) {
        $bidlines = 4;
    }

    $numberedLines = null;
    if (array_key_exists('artistBidSheetNumbers', $vendor))
        $numberedLines = $vendor['artistBidSheetNumbers'];
    if ($numberedLines == null || $numberedLines == '' || !is_numeric($numberedLines)) {
        $numberedLines = 3;
    }

    $bidSep = 0;
    if (array_key_exists('artistBidSheetBidSep', $vendor))
        $bidSep = $vendor['artistBidSheetBidSep'];

    if ($bidSep == 'yes' || $bidSep == 'true')
        $bidSep = 1;
    else if ($bidSep == 'no' || $bidSep == 'false')
        $bidSep = 0;
    else if ($bidSep == null || $bidSep == '' || !is_numeric($bidSep))
        $bidSep = 0;

    $totalLines = $bidlines + ($useBarCode ? 1 : 0);

// local constants for the sheets
    $margin = 0.25;
    $indent = 0.1;
    $blockheight = 0.33;
    $priceheight = 0.25;
    $headerheight = 0.15;
    $labelOffset = 0.06;
    $dataOffset = 0.20;
    $priceoffset = 0.14;

    if ($totalLines <= 4) {
        $orient = 'L';
        $numcols = 3;
        $numrows = 2;
        $vsize = 3.8 - ((4 - $totalLines) * $blockheight);
    } else if ($totalLines <= 8) {
        $orient = 'P';
        $numcols = 2;
        $numrows = 2;
        $vsize = 5.1 - ((8 - $totalLines) * $blockheight);;
    } else {
        $orient = 'P';
        $numcols = 2;
        $numrows = 1;
        $vsize = 10.1 - ((23 - $totalLines) * $blockheight);;
    }

    $itemSQL = <<<EOS
SELECT e.exhibitorName, exRY.exhibitorNumber, e.artistName,
       aI.title, aI.item_key, aI.min_price, aI.sale_price, aI.original_qty, aI.material, aI.type,
       aI.id AS itemId, e.id, eR.name, e.artistName
FROM exhibitorRegionYears exRY
JOIN exhibitorYears exY ON exY.id = exRY.exhibitorYearId
JOIN exhibitors e ON e.id = exY.exhibitorId
JOIN artItems aI ON aI.exhibitorRegionYearId = exRY.id 
JOIN exhibitsRegionYears eRY ON exRY.exhibitsRegionYearId = eRY.id
JOIN exhibitsRegions eR ON eRY.exhibitsRegion = eR.id
WHERE exRY.exhibitorYearId=? AND exRY.exhibitsRegionYearId = ? AND aI.type in ('art','nfs')
ORDER BY aI.item_key;
EOS;

    $itemR = dbSafeQuery($itemSQL, 'ii', array($regionYearId, $region));
    if ($itemR === false) {
        $response['error'] = 'Error retrieving art items for bid sheets, please seek assistance';
        echo "Error retrieving art items for bid sheets, please seek assistance\n";
        return $response;
    }
    if ($itemR->num_rows == 0) {
        $response['num_rows'] = $itemR->num_rows;
        $response['status'] = 'No art found requiring bid sheets';
        echo "No art found requiring bid sheets\n";
        $itemR->free();
        return $response;
    }

// load data array
    $artItems = [];
    while ($artItem = $itemR->fetch_assoc()) {
        $artItems[] = $artItem;
    }
    $numSheets = $itemR->num_rows;
    $itemR->free();

    if ($artItems[0]['artistName'] == null || $artItems[0]['artistName'] == '') {
        $artistName = $artItems[0]['exhibitorName'];
    } else {
        $artistName = $artItems[0]['artistName'];
    }

    $pages = ceil($numSheets / ($numrows * $numcols));

    $curLocale = locale_get_default();
    $dolfmt = new NumberFormatter($curLocale == 'en_US_POSIX' ? 'en-us' : $curLocale, NumberFormatter::CURRENCY);

    $pdf = new \Erkens\Fpdf\Barcode($orient, 'in', 'Letter');
    initPDF($pdf, 0.008, 'Arial', '', 11);

// computes from those offsets
    $hsize = ($pdf->GetPageWidth() - 2 * $margin) / $numcols;
    $firstrow = $margin + 0.15;

// timestamp for printing when generated
    $createDate = date('Y/m/d h:i:s A');
    $fileDate = date('Y-m-d-H-i-s');

    $row = $numrows;
    $col = $numcols;
    $page = 0;
    foreach ($artItems as $art) {
        // set up for next item
        $col++;
        if ($col >= $numcols) {
            $row++;
            $col = 0;
        }
        if ($row >= $numrows) {
            $row = 0;
            $pdf->AddPage();
            $page++;
            pushFont('Arial', 'B', 11);
            printXY($margin, $margin, "Bid Sheets for $conname's " . $art['name'] . '; Artist: ' . $artistName);
            $fileLabel = str_replace(' ', '_',$conname . '_' . $art['name'] . '_' . $artistName . '.pdf');
            $y = $pdf->GetPageHeight() - ($margin);
            printXY($margin, $y, "Generated: $createDate");
            $pageStr = "Page $page of $pages";
            rightPrintXY(0,  $y, $pdf->GetPageWidth() - $margin,  $pageStr);
            popFont();
        }

        $v = $firstrow + $row * $vsize;
        $h = $margin + $col * $hsize;
        // draw outer box
        $pdf->Rect($h, $v, $hsize, $vsize);
        $h += $indent;
        $v += $indent;

        // now title header
        $isize = $hsize - 2 * $indent;
        $pdf->Rect($h, $v, $isize, $blockheight);
        pushFont('Arial', '', 14);
        centerPrintXY($h, $v + 0.16, $isize - 0.1, $title);
        popFont();

        // draw artist block
        $v += $blockheight;
        $pdf->Rect($h, $v, $isize * 0.8, $blockheight);
        $pdf->Rect($h + $isize * 0.8, $v, $isize * 0.2, $blockheight);
        pushFont('Arial', '', 5);
        printXY($h + 0.025, $v + $labelOffset,'ARTIST');
        printXY($h + 0.025 + $isize * 0.8, $v + $labelOffset,'ARTIST#');
        popFont();
        fitprintXY($h + 0.1, $v + $dataOffset, (0.8 * $isize),  $artistName);
        printXY($h + 0.1 + (0.8 * $isize), $v + $dataOffset, $art['exhibitorNumber']);

        // draw print title block
        $v += $blockheight;
        $pdf->Rect($h, $v, $isize, $blockheight);
        pushFont('Arial', '', 5);
        printXY($h + 0.025, $v + $labelOffset, 'TITLE');
        popFont();
        fitprintXY($h + 0.1, $v + $dataOffset,$isize-0.2, $art['title']);

        // draw Medium/Piece#
        $v += $blockheight;
        $pdf->Rect($h, $v, $isize * 0.8, $blockheight);
        $pdf->Rect($h + $isize * 0.8, $v, $isize * 0.2, $blockheight);
        pushFont('Arial', '', 5);
        printXY($h + 0.025, $v + $labelOffset, 'MEDIUM');
        printXY($h + 0.025 + $isize * 0.8, $v + $labelOffset, 'PIECE#');
        popFont();
        fitprintXY($h + 0.1, $v + $dataOffset, ($isize * 0.8) -0.2, $art['material']);
        printXY($h + 0.1 + (0.8 * $isize), $v + $dataOffset, $art['item_key']);

        if ($art['type'] == 'nfs') {
            pushLineWidth(0.024);
            $v += $blockheight;
            // NFS just output a large NFS line
            $isize = $hsize - 2 * $indent;
            $pdf->Rect($h, $v, $isize, $blockheight * 1.6);
            pushFont('Arial', '', 32);
            centerPrintXY($h, $v + 0.30, $isize - 0.1, 'NOT FOR SALE');
            popFont();
            popLineWidth();
            $v += $blockheight * 0.6;
        } else {
            // draw Minimum Bid Amount
            pushLineWidth(0.016);
            $v += $blockheight;
            $pdf->Rect($h, $v, $isize * 0.6, $priceheight);
            $pdf->Rect($h + $isize * 0.6, $v, $isize * 0.4, $priceheight);
            $label = 'Minimum bid amount';
            $length = $pdf->getStringWidth($label);
            printXY($h + ($isize * 0.6) - (0.1 + $length), $v + $priceoffset, $label);

            if ($art['type'] == 'nfs') {
                $priceFmt = 'N/A';
            } else {
                $priceFmt = $dolfmt->formatCurrency((float)$art['min_price'], $currency);
            }
            $pricewidth = $pdf->getStringWidth($priceFmt);
            printXY($h + (0.97 * $isize) - $pricewidth, $v + $priceoffset, $priceFmt);

            // draw Quick Sale Amount
            $v += $priceheight;
            $pdf->Rect($h, $v, $isize * 0.6, $priceheight);
            $pdf->Rect($h + $isize * 0.6, $v, $isize * 0.4, $priceheight);
            $label = 'Quicksale price';
            $length = $pdf->getStringWidth($label);
            printXY($h + ($isize * 0.6) - (0.1 + $length), $v + $priceoffset, $label);

            $price = $art['sale_price'];
            if ($price > 0 && $art['type'] != 'nfs') {
                $priceFmt = $dolfmt->formatCurrency((float)$art['sale_price'], $currency);
            } else {
                $priceFmt = "N/A";
            }
            $pricewidth = $pdf->getStringWidth($priceFmt);
            printXY($h + (0.97 * $isize) - $pricewidth, $v + $priceoffset, $priceFmt);
            popLineWidth();

            // now bid header
            $v += $priceheight;
            $headerStart = $v;
            $pdf->Rect($h, $v, $isize * 0.6, $headerheight);
            $pdf->Rect($h + $isize * 0.6, $v, $isize * 0.2, $headerheight);
            $pdf->Rect($h + $isize * 0.8, $v, $isize * 0.2, $headerheight);
            pushFont('Arial', '', 8);
            printXY($h + 0.025, $v + $labelOffset + 0.01, "Bidder Name (Please Print)");
            printXY($h + 0.025 + $isize * 0.6, $v + $labelOffset + 0.01, 'Badge #');
            printXY($h + 0.025 + $isize * 0.8, $v + $labelOffset + 0.01, 'Bid ($)');
            popFont();

            $v += $headerheight;
            pushFont('Arial', '', 6);
            for ($lineno = 1; $lineno <= $numberedLines; $lineno++) {
                $pdf->Rect($h, $v, $isize, $blockheight);
                if ($bidSep) {
                    $pdf->Rect($h + $isize * 0.6, $v, $isize * 0.2, $blockheight);
                }
                printXY($h, $v + $labelOffset, "$lineno)");
                $v += $blockheight;
            }
            popFont();
            for (; $lineno <= $bidlines; $lineno++) {
                $pdf->Rect($h, $v, $isize, $blockheight);
                if ($bidSep) {
                    $pdf->Rect($h + $isize * 0.6, $v, $isize * 0.2, $blockheight);
                }
                $v += $blockheight;
            }

            // artshow use only block
            pushFont('Arial', 'B', 11);
            $pdf->Rect($h, $v, $isize, $blockheight);
            printXY($h, $v + $dataOffset, "ART SHOW USE ONLY");
            popFont();

            pushFont('Arial', '', 9);
            $pdf->Rect($h + 1.75, $v + $labelOffset + 0.05, 0.15, 0.15);
            printXY($h + 1.95, $v + $dataOffset, "AUC");
            $pdf->Rect($h + 2.45, $v + $labelOffset + 0.05, 0.15, 0.15);
            printXY($h + 2.65, $v + $dataOffset, 'SOLD');
            $pdf->Rect($h + 3.25, $v + $labelOffset + 0.05, 0.15, 0.15);
            printXY($h + 3.45, $v + $dataOffset, 'QS');
        }

        if ($useBarCode) {
            $v += $blockheight;
            $barcodeData = sprintf('%7.7d,%3.3d', $art['itemId'], 1);
            $pdf->code128($h + $indent, $v + $labelOffset, $barcodeData, ($isize - (2 * $indent)) / 1.5, $blockheight - (2 * $labelOffset));
        }

        $headerEnd = $v + $blockheight;
        pushLineWidth(0.024);
        $pdf->Rect($h, $headerStart, $isize, $headerEnd - $headerStart);
        popLineWidth();
    }

    header('Content-Type: application/pdf');
    $fileLabel = preg_replace('/[^A-Za-z0-9_]/', '', $fileLabel);
    $filename = $fileLabel . '_' . $fileDate . '.pdf';
    header('Content-Disposition: inline; filename="' . $filename . '"');
    $output = $pdf->Output();
    print($output);
    $response['success'] = true;
    $response['message'] = "$pages pages output";
    return $response;
}

// pdfArtistControlSheet.php - creates the control sheet as a web page for printing

function pdfArtistControlSheet($regionYearId, $region, $response, $printContactInfo = false) {
    $con = get_conf('con');
    if (array_key_exists('currency', $con)) {
        $currency = $con['currency'];
    } else {
        $currency = 'USD';
    }
    // local constants for the control sheet
    $margin = 0.25;
    $indent = 0.1;
    $pt = 1/72;
    $lineHeight = 11 * $pt;
    $boxHeight = 13 * $pt;
    $dataOffset = 0.12;

    $artistQ = <<<EOS
SELECT e.*, exY.conid,exY.mailin,exY.contactName,exY.contactPhone, exY.contactEmail, exRY.agentPerid, exRY.agentRequest, exRY.exhibitorNumber, eR.name,
       p.first_name, p.last_name, p.middle_name, p.suffix, p.phone, p.email_addr, e.exhibitorEmail, e.artistName, e.exhibitorPhone
FROM exhibitorRegionYears exRY
JOIN exhibitorYears exY ON exY.id = exRY.exhibitorYearId
JOIN exhibitors e ON e.id = exY.exhibitorId
JOIN exhibitsRegionYears eRY ON exRY.exhibitsRegionYearId = eRY.id
JOIN exhibitsRegions eR ON eRY.exhibitsRegion = eR.id
LEFT OUTER JOIN perinfo p ON p.id = exRY.agentPerid
WHERE exRY.exhibitorYearId=? AND exRY.exhibitsRegionYearId = ?;
EOS;

    $artistR = dbSafeQuery($artistQ, 'ii', array($regionYearId, $region));
    if ($artistR === false || $artistR->num_rows == 0) {
        $response['error'] = 'Error retrieving Artist information for control sheet, please seek assistance';
        echo "Error retrieving Artist information for control sheet, please seek assistance\n";
        return $response;
    }

    $artist = $artistR->fetch_assoc();
    $artistR->free();

    $con = get_con();
    $conname = $con['label'];

    // determine which parts of the second block we need
    if ($artist['artistName'] == null || $artist['artistName'] == '' ) {
        $artistName = $artist['exhibitorName'];
    } else {
        $artistName = $artist['artistName'];
    }
    $showContact = $artist['contactName'] != $artistName || $artist['contactPhone'] != $artist['exhibitorPhone'] ||
        $artist['exhibitorEmail'] != $artist['contactEmail'];
    $showShip = $artist['mailin'] == 'Y';

    $title = "$conname Art Control Sheet for " . $artistName;

    $pdf = new Fpdf\Fpdf('L', 'in', 'Letter');
    initPDF($pdf, 0.008, 'Arial', '', 11);

    // computes from those offsets
    $hsize = ($pdf->GetPageWidth() - 2 * $margin);
    $firstrow = $margin + 0.4;

    // timestamp for printing when generated
    $createDate = date('Y/m/d h:i:s A');
    $fileDate = date('Y-m-d-H-i-s');

    $curLocale = locale_get_default();
    $dolfmt = new NumberFormatter($curLocale == 'en_US_POSIX' ? 'en-us' : $curLocale, NumberFormatter::CURRENCY);

    $page = 0;

    // print header on first page
    $pdf->AddPage();
    $page++;
    pushFont('Arial', 'B', 11);
    printXY($margin, $margin, "Control Sheets for $conname's " . $artist['name'] . '; Artist: ' . $artistName);
    $fileLabel = str_replace(' ', '-', $conname . '_' . $artist['name'] . '_' . $artistName);
    $y = $pdf->GetPageHeight() - ($margin);
    printXY($margin, $y, "Generated: $createDate");
    $pageStr = "Page $page";
    rightPrintXY(0,  $y, $pdf->GetPageWidth() - $margin,  $pageStr);
    popFont();

    // Title Line
    pushFont('Arial', 'B', 14);
    $v = $firstrow;
    $h = $margin;
    printXY($h, $v, $title);
    popFont();
    $v += 0.4;

    // Section Line - Artist & Agent Info
    pushFont('Arial', 'B', 13);
    printXY($h, $v, "Artist & Agent Information");
    popFont();
    $v += 0.3;

    // Artist Number:
    pushFont('Arial', 'B', 12);
    printXY($h, $v, "Artist Number: ". $artist['exhibitorNumber']);
    popFont();
    $v += 0.15;

    $col1 = $h + $pt;
    $col2 = $h + 3.0 + 3 * $pt;
    $col3 = $h + 6.0 + 3 * $pt;
    $col1w = 3.0 - 4 * $pt;
    $col2w = $col1w;
    $col3w = $col1w;
    $col23w = 6.0 - 4 * $pt;
    $leading = 3 * $pt;
    $minRowHeight = 12 * $pt + $leading;
    $mprintXYoffset = (11 / 144) + $leading;  // strange centerline type of mprintXY.
    // artist name row
    //  artist name
    printXY($col1, $v + $dataOffset, "Artist:");
    $maxY = $minRowHeight * $pt;
    $y = fitprintXY($h + 0.5, $v + $dataOffset, $col1w - 0.5, $artistName);
    if ($y > $maxY) $maxY = $y;
    $rowHeight = $leading + $maxY + 0.05 - $v;

    //  email
    if ($artist['email_addr'] == null || $artist['email_addr'] == '')
        $emailAddr = $artist['exhibitorEmail'];
    else
        $emailAddr = $artist['email_addr'];
    printXY($col2, $v + $dataOffset, 'Email: ' . $emailAddr);

    // Phone
    printXY($col3, $v + $dataOffset, 'Phone: ' . $artist['exhibitorPhone']);
    $pdf->Rect($h, $v, 3.0,$rowHeight);
    $pdf->Rect($h + 3.0, $v, 3.0,$rowHeight);
    $pdf->Rect($h + 6.0, $v, 3.0,$rowHeight);
    $v += $rowHeight;

    // Address lines
    $addr = $artist['addr'];
    if (array_key_exists('addr2', $artist) && isset($artist['addr2']) && $artist['addr2'] != '') {
        $addr .= PHP_EOL . $artist['addr2'];
    }
    printXY($col1, $v + $dataOffset, 'Address:');
    $maxY = $minRowHeight;
    $y = mprintXY($col2, $v + $mprintXYoffset, $col23w, $addr);
    if ($y > $maxY) $maxY = $y;
    $rowHeight = $leading + $maxY - $v;
    $pdf->Rect($h, $v, 3.0,$rowHeight);
    $pdf->Rect($h + 3.0, $v, 6.0,$rowHeight);
    $v += $rowHeight;

    $addr = $artist['city'] . ', ' . $artist['state'] . ' ' . $artist['zip'];
    printXY($col1, $v + $dataOffset, 'City/State/Zip:');
    $maxY = $minRowHeight;
    $y = mprintXY($col2, $v + $mprintXYoffset, $col23w, $addr);
    if ($y > $maxY) $maxY = $y;
    $rowHeight = $leading + $maxY - $v;
    $pdf->Rect($h, $v, 3.0,$rowHeight);
    $pdf->Rect($h + 3.0, $v, 6.0,$rowHeight);
    $v += $rowHeight;

    printXY($col1, $v + $dataOffset, 'Country:');
    printXY($col2, $v + $dataOffset, $artist['country']);
    $rowHeight = $minRowHeight;
    $pdf->Rect($h, $v, 3.0,$rowHeight);
    $pdf->Rect($h + 3.0, $v, 6.0,$rowHeight);
    $v += $rowHeight;

    // agent line
    if (array_key_exists('agentPerid', $artist) && $artist['agentPerid'] > 0) {
        $aname = TRIM(TRIM(TRIM($artist['first_name'] . ' ' . $artist['middle_name']) . ' ' . $artist['last_name']) . ' ' . $artist['suffix']);
        $aperid = $artist['agentPerid'];
        $aemail = $artist['email_addr'];
        $aphone = $artist['phone'];
        $agent = "$aperid: $aname,  $aemail,  $aphone";
    } else {
        $agent = "(Artist)";
    }
    printXY($col1, $v + $dataOffset, 'Agent:');
    $maxY = $minRowHeight;
    $y = mprintXY($col2, $v + $mprintXYoffset, $col23w, $agent);
    if ($y > $maxY) $maxY = $y;
    $rowHeight = $leading + $maxY - $v;
    $pdf->Rect($h, $v, 3.0,$rowHeight);
    $pdf->Rect($h + 3.0, $v, 6.0,$rowHeight);
    $v += $rowHeight;

    if ($showContact || $showShip) {
        if ($showContact && $showShip)
            $title = 'Primary Contact/Shipping Information';
        else if ($showContact)
            $title = 'Primary Contact';
        else
            $title = 'Shipping Information';

        // Section Line - Primary Contact
        $v += 0.25;
        pushFont('Arial', 'B', 13);
        printXY($h, $v, $title);
        popFont();
        $v += 0.3;

        $col2 = $h + 2.0 + 3 * $pt;
        $col2w = 6.0 - 4 * $pt;

        if ($showContact) {
            // Primary contact info
            printXY($col1, $v + $dataOffset, 'Primary Contact:');
            $maxY = $minRowHeight;
            $y = mprintXY($col2, $v + $mprintXYoffset, $col2w, $artist['contactName']);
            if ($y > $maxY) $maxY = $y;
            $rowHeight = $leading + $maxY - $v;
            $pdf->Rect($h, $v, 2.0, $rowHeight);
            $pdf->Rect($h + 2.0, $v, 4.0, $rowHeight);
            $v += $rowHeight;

            printXY($col1, $v + $dataOffset, 'Phone:');
            $maxY = $minRowHeight;
            $y = mprintXY($col2, $v + $mprintXYoffset, $col2w, $artist['contactPhone']);
            if ($y > $maxY) $maxY = $y;
            $rowHeight = $leading + $maxY - $v;
            $pdf->Rect($h, $v, 2.0, $rowHeight);
            $pdf->Rect($h + 2.0, $v, 4.0, $rowHeight);
            $v += $rowHeight;

            printXY($col1, $v + $dataOffset, 'Email:');
            $maxY = $minRowHeight;
            $y = mprintXY($col2, $v + $mprintXYoffset, $col2w, $artist['contactEmail']);
            if ($y > $maxY) $maxY = $y;
            $rowHeight = $leading + $maxY - $v;
            $pdf->Rect($h, $v, 2.0, $rowHeight);
            $pdf->Rect($h + 2.0, $v, 4.0, $rowHeight);
            $v += $rowHeight;
        }

        if ($showShip) {
            // ship to lines

            printXY($col1, $v + $dataOffset, 'Shipping Info:');
            $maxY = $minRowHeight;
            $y = mprintXY($col2, $v + $mprintXYoffset, $col2w, "Ship to:");
            if ($y > $maxY) $maxY = $y;
            $rowHeight = $leading + $maxY - $v;
            $pdf->Rect($h, $v, 2.0, $rowHeight);
            $pdf->Rect($h + 2.0, $v, 4.0, $rowHeight);
            $v += $rowHeight;

            printXY($col1, $v + $dataOffset, 'Company:');
            $maxY = $minRowHeight;
            $y = mprintXY($col2, $v + $mprintXYoffset, $col2w, $artist['shipCompany']);
            if ($y > $maxY) $maxY = $y;
            $rowHeight = $leading + $maxY - $v;
            $pdf->Rect($h, $v, 2.0, $rowHeight);
            $pdf->Rect($h + 2.0, $v, 4.0, $rowHeight);
            $v += $rowHeight;

            $addr = $artist['shipAddr'];
            if (array_key_exists('shipAddr2', $artist) && isset($artist['shipAddr2']) && $artist['shipAddr2'] != '') {
                $addr .= PHP_EOL . $artist['shipAddr2'];
            }
            printXY($col1, $v + $dataOffset, 'Address:');
            $maxY = $minRowHeight;
            $y = mprintXY($col2, $v + $mprintXYoffset, $col2w, $addr);
            if ($y > $maxY) $maxY = $y;
            $rowHeight = $leading + $maxY - $v;
            $pdf->Rect($h, $v, 2.0, $rowHeight);
            $pdf->Rect($h + 2.0, $v, 4.0, $rowHeight);
            $v += $rowHeight;

            $addr = $artist['shipCity'] . ', ' . $artist['shipState'] . ' ' . $artist['shipZip'];
            printXY($col1, $v + $dataOffset, 'City/State/Zip:');
            $maxY = $minRowHeight;
            $y = mprintXY($col2, $v + $mprintXYoffset, $col2w, $addr);
            if ($y > $maxY) $maxY = $y;
            $rowHeight = $leading + $maxY - $v;
            $pdf->Rect($h, $v, 2.0, $rowHeight);
            $pdf->Rect($h + 2.0, $v, 4.0, $rowHeight);
            $v += $rowHeight;

            printXY($col1, $v + $dataOffset, 'Country:');
            $maxY = $minRowHeight;
            $y = mprintXY($col2, $v + $mprintXYoffset, $col2w, $artist['shipCountry']);
            if ($y > $maxY) $maxY = $y;
            $rowHeight = $leading + $maxY - $v;
            $pdf->Rect($h, $v, 2.0, $rowHeight);
            $pdf->Rect($h + 2.0, $v, 4.0, $rowHeight);
            $v += $rowHeight;
        }
    }

    // Section Line - Artwork
    $v += 0.25;
    pushFont('Arial', 'B', 13);
    printXY($h, $v, 'Artwork');
    popFont();
    $v += 0.3;

    // now get art info
    $itemSQL = <<<EOS
SELECT e.exhibitorName, exRY.exhibitorNumber, e.artistName,
       aI.title, aI.item_key, aI.min_price, aI.sale_price, aI.original_qty, aI.quantity, aI.material, aI.type,
       aI.status, aI.bidder, aI.final_price, e.id, eR.name, e.artistName,
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
    if ($itemR === false) {
        $response['error'] = 'Error retrieving art items for control sheet, please seek assistance';
        echo "Error retrieving art items for control sheet, please seek assistance\n";
        return $response;
    }
    if ($itemR->num_rows == 0) {
        printXY($h, $v, "No art found for this artist");
        $response['num_rows'] = $itemR->num_rows;
        $response['status'] = 'No art found requiring bid sheets';
    } else {

        // load data array
        $artItems = [];
        while ($artItem = $itemR->fetch_assoc()) {
            $artItems[] = $artItem;
        }
        $itemR->free();

        pushFont('Arial', '', 10);
        $numwidth = $pdf->GetStringWidth("123");
        $cPN = $margin + $pt;
        $wPN = $numwidth;
        $cT = $cPN + $wPN + 3 * $pt;
        $wT = 2.0;
        $cType = $cT + $wT + 3 * $pt;
        $wType = 0.25;
        $cM = $cType + $wType + 3 * $pt;
        $wM = 1.2;
        $cMin = $cM + $wM + 3 * $pt;
        $wMin = 0.7;
        $cSale = $cMin + $wMin + 3 * $pt;
        $wSale = 0.7;
        $cOrig = $cSale + $wSale + 3 * $pt;
        $wOrig = $numwidth;
        $cQty = $cOrig + $wOrig + 3 * $pt;
        $wQty = $numwidth;
        $cLoc = $cQty + $wQty + 3 * $pt;
        $wLoc = 0.5;
        $cStatus = $cLoc + $wLoc + 3 * $pt;
        $wStatus = 0.5;
        $cFinal = $cStatus + $wStatus + 3 * $pt;
        $wFinal = 0.7;
        $cWin = $cFinal + $wFinal + 3 * $pt;
        $wWin = 1.3;
        $cWEmail = $cWin + $wWin + 3 * $pt;
        $wWEmail = 1.4;
        $maxV = 8.5 - ($margin + 4 * $minRowHeight);
        $boxOffset = (11 / 144);

        $titleNeeded = true;
        foreach ($artItems as $artItem) {
            if ($v > $maxV) {
                $titleNeeded = true;
                $pdf->AddPage();
                $page++;
                pushFont('Arial', 'B', 11);
                printXY($margin, $margin, "Control Sheets for $conname's " . $artist['name'] . '; Artist: ' . $artistName);
                $y = $pdf->GetPageHeight() - ($margin);
                printXY($margin, $y, "Generated: $createDate");
                $pageStr = "Page $page";
                rightPrintXY(0,  $y, $pdf->GetPageWidth() - $margin,  $pageStr);
                popFont();
                $v = $firstrow;;
            }

            if ($titleNeeded) {
                // print title
                $titleNeeded = false;
                rightPrintXY($cPN, $v, $wPN, '#');
                printXY($cT, $v,'Title');
                pushFont('Arial', '', 7);
                printXY($cType - $pt, $v, 'Type');
                popFont();
                printXY($cM, $v, 'Material');
                pushFont('Arial', '', 8);
                mprintXY($cMin, $v, $wMin, 'Min bid or Ins Value');
                mprintXY($cSale, $v, $wSale, 'Quick Sale or Print Price');
                popFont();
                pushFont('Arial', '', 6);
                mprintXY($cOrig - $pt, $v, $wOrig + $pt, 'Orig Qty');
                mprintXY($cQty - $pt, $v, $wQty + $pt, 'Cur. Qty');
                popFont();
                pushFont('Arial', '', 8);
                printXY($cLoc, $v, 'Location');
                popFont();
                printXY($cStatus, $v, 'Status');
                mprintXY($cFinal, $v, $wFinal, 'Winning Bid');
                printXY($cWin, $v, 'Bidder');
                if ($printContactInfo === true) {
                    printXY($cWEmail, $v, 'Bidder Email');
                } else {
                    printXY($cWEmail, $v, 'Bidder Id');
                }

                $bv = $v - ($boxOffset + $pt);
                $boxHeight = 2 * $minRowHeight;
                $pdf->Rect($margin, $bv, $wPN + $pt * 3, $boxHeight);
                $pdf->Rect($cT - $pt, $bv, $wT + $pt * 3, $boxHeight);
                $pdf->Rect($cType - $pt, $bv, $wType + $pt * 3, $boxHeight);
                $pdf->Rect($cM - $pt, $bv, $wM + $pt * 3, $boxHeight);
                $pdf->Rect($cMin - $pt, $bv, $wMin + $pt * 3, $boxHeight);
                $pdf->Rect($cSale - $pt, $bv, $wSale + $pt * 3, $boxHeight);
                $pdf->Rect($cOrig - $pt, $bv, $wOrig + $pt * 3, $boxHeight);
                $pdf->Rect($cQty - $pt, $bv, $wQty + $pt * 3, $boxHeight);
                $pdf->Rect($cLoc - $pt, $bv, $wLoc + $pt * 3, $boxHeight);
                $pdf->Rect($cStatus - $pt, $bv, $wStatus + $pt * 3, $boxHeight);
                $pdf->Rect($cFinal - $pt, $bv, $wFinal + $pt * 3, $boxHeight);
                $pdf->Rect($cWin - $pt, $bv, $wWin + $pt * 3, $boxHeight);
                $pdf->Rect($cWEmail - $pt, $bv, $wWEmail + $pt * 3, $boxHeight);

                $v += 2 * $minRowHeight;
            }

            $winnerName = TRIM(TRIM(TRIM($artItem['first_name'] . ' ' . $artItem['middle_name']) . ' ' . $artItem['last_name']) . ' ' . $artItem['suffix']);
            $winnerPerid = $artItem['bidder'];
            $winnerEmail = $artItem['email_addr'];

            // art row
            $maxY = $minRowHeight;
            rightPrintXY($cPN, $v, $wPN, $artItem['item_key']);
            $y = mprintXY($cT, $v, $wT, $artItem['title']);
            if ($y > $maxY) $maxY = $y;
            pushFont('Arial', '', 7);
            printXY($cType, $v, $artItem['type']);
            popFont();
            $y = mprintXY($cM, $v, $wM, $artItem['material']);
            if ($y > $maxY) $maxY = $y;
            if ($artItem['min_price'] && $artItem['type'] != 'print') {
                if ($artItem['min_price'] > 9999.99) {
                    pushFont('Arial', '', $artItem['min_price'] > 999999.99 ? 7.5 : 9);
                }
                rightPrintXY($cMin, $v, $wMin, $dolfmt->formatCurrency((float)$artItem['min_price'], $currency));
                if ($artItem['min_price'] > 9999.99) {
                    popFont();
                }
            }
            if ($artItem['sale_price'] && $artItem['type'] != 'nfs') {
                if ($artItem['sale_price'] > 9999.99) {
                    pushFont('Arial', '', $artItem['sale_price'] > 999999.99 ? 7.5 : 9);
                }
                rightPrintXY($cSale, $v, $wSale, $dolfmt->formatCurrency((float)$artItem['sale_price'], $currency));
                if ($artItem['sale_price'] > 9999.99) {
                    popFont();
                }
            }
            if ($artItem['original_qty'] > 0)
                rightPrintXY($cOrig, $v, $wOrig, $artItem['original_qty']);
            if ($artItem['quantity'] > 0)
                rightPrintXY($cQty, $v, $wQty, $artItem['quantity']);
            pushFont('Arial', '', 7);
            $y = fitprintXY($cStatus - $pt, $v, $wStatus + $pt, $artItem['status']);
            if ($y > $maxY) $maxY = $y;
            popFont();
            if ($artItem['final_price']) {
                if ($artItem['final_price'] > 9999.99) {
                    pushFont('Arial', '', $artItem['final_price'] > 999999.99 ? 7.5 : 9);
                }
                rightPrintXY($cFinal, $v, $wFinal, $dolfmt->formatCurrency((float)$artItem['final_price'], $currency));
                if ($artItem['final_price'] > 9999.99) {
                    popFont();
                }
            }
            $y = mprintXY($cWin, $v, $wWin, $winnerName);
            if ($y > $maxY) $maxY = $y;
            if ($printContactInfo === true) {
                $y = mprintXY($cWEmail, $v, $wWEmail, $winnerEmail);
            } else {
                $y = mprintXY($cWEmail, $v, $wWEmail, $winnerPerid);
            }
            if ($y > $maxY) $maxY = $y;

            // now draw the borders

            $bv = $v - ($boxOffset + $pt);
            $boxHeight = 2 * $pt + $boxOffset + $maxY - $v;
            $pdf->Rect($margin, $bv, $wPN + $pt * 3, $boxHeight);
            $pdf->Rect($cT - $pt, $bv, $wT + $pt * 3, $boxHeight);
            $pdf->Rect($cType - $pt, $bv, $wType + $pt * 3, $boxHeight);
            $pdf->Rect($cM - $pt, $bv, $wM + $pt * 3, $boxHeight);
            $pdf->Rect($cMin - $pt, $bv, $wMin + $pt * 3, $boxHeight);
            $pdf->Rect($cSale - $pt, $bv, $wSale + $pt * 3, $boxHeight);
            $pdf->Rect($cOrig - $pt, $bv, $wOrig + $pt * 3, $boxHeight);
            $pdf->Rect($cQty - $pt, $bv, $wQty + $pt * 3, $boxHeight);
            $pdf->Rect($cLoc - $pt, $bv, $wLoc + $pt * 3, $boxHeight);
            $pdf->Rect($cStatus - $pt, $bv, $wStatus + $pt * 3, $boxHeight);
            $pdf->Rect($cFinal - $pt, $bv, $wFinal + $pt * 3, $boxHeight);
            $pdf->Rect($cWin - $pt, $bv, $wWin + $pt * 3, $boxHeight);
            $pdf->Rect($cWEmail - $pt, $bv, $wWEmail + $pt * 3, $boxHeight);

            $v = $maxY + 0.1;
        }

        $v += $minRowHeight;
        pushFont('Arial', 'B', 12);
        centerPrintXY(0, $v, $pdf->getPageWidth(), "* * * * * End of Artwork * * * * *");
    }

    header('Content-Type: application/pdf');
    $fileLabel = preg_replace('/[^A-Za-z0-9_]/', '', $fileLabel);
    $filename = $fileLabel . '_' . $fileDate . '.pdf';
    header('Content-Disposition: inline; filename="' . $filename . '"');
    $output = $pdf->Output();
    print($output);
    $response['success'] = true;
    $response['message'] = "$page pages output";
    return $response;
}
