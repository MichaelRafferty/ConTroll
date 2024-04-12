<?php
// pdfPrintArtShowSheets.php - routines for creating the art show bid sheets, price tags and control sheets
require_once (__DIR__ . '/../Composer/vendor/autoload.php');
require_once ("pdfFunctions.php");
use Fpdf\Fpdf as Fpdf;
function pdfPrintShopPriceSheets($regionYearId, $region, $response) {
// local constants for the sheets
    $margin = 0.25;
    $numcols = 3;
    $numrows = 4;
    $vsize = 1.6;
    $indent = 0.1;
    $blockheight = 0.35;
    $labeloffset = 0.06;
    $dataoffset = 0.22;

    $itemSQL = <<<EOS
SELECT e.exhibitorName, exRY.exhibitorNumber, aI.title, aI.item_key, aI.sale_price, aI.original_qty, aI.material, e.id, eR.name
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
    if ($itemR == false) {
        $response['error'] = "Error retrieving art items for print show, please seek assistance";
        echo "Error retrieving art items for print show, please seek assistance\n";
        return $response;
    }
    if ($itemR->num_rows == 0) {
        $response['num_rows'] = $itemR->num_rows;
        $response['status'] = 'No art found requiring price tags';
        echo "No art found requiring price tags\n";
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

    // load data array
    $artItems = [];
    $numTags = 0;
    while ($artItem = $itemR->fetch_assoc()) {
        $artItems[] = $artItem;
        $numTags += $artItem['original_qty'];
    }
    $pages = ceil($numTags / ($numrows * $numcols));

    $curLocale = locale_get_default();
    $dolfmt = new NumberFormatter($curLocale == 'en_US_POSIX' ? 'en-us' : $curLocale, NumberFormatter::CURRENCY);

    $pdf = new Fpdf('L', 'in', 'Letter');
    initPDF($pdf, 0.008, 'Arial', '', 11);

    // computes from those offsets
    $hsize = ($pdf->GetPageWidth() - 2 * $margin) / $numcols;
    $firstrow = $margin + 0.5;

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
                printXY($margin, $margin,"Price Tags for $conname's " . $print['name'] . "; Artist: " . $print['exhibitorName']);
                $fileLabel = $conname . "_" . $print['name'] . "_" . $print['exhibitorName'];
                $y = $pdf->GetPageHeight() - ($margin);
                printXY($margin, $y,"Generated: $createDate");
                $pageStr = "Page $page of $pages";
                $pagewidth = $pdf->getStringWidth($pageStr);
                printXY($pdf->GetPageWidth() - ($margin + $pagewidth), $y, $pageStr);
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
            fitprintXY($h+ $titleoffset, $v + 0.16, $titlewidth, $title);
            popFont();

            // draw artist block
            $v += $blockheight;
            $pdf->Rect($h, $v, $isize * 0.8, $blockheight);
            $pdf->Rect($h + $isize * 0.8, $v, $isize * 0.2, $blockheight);
            pushFont('Arial', '', 5);
            printXY($h + 0.025, $v + $labeloffset,'ARTIST');
            printXY($h + 0.025 + $isize * 0.8, $v + $labeloffset,'ARTIST#');
            popFont();
            fitprintXY($h + 0.1, $v + $dataoffset, (0.8 * $isize),  $print['exhibitorName']);
            printXY($h + 0.1 + (0.8 * $isize), $v + $dataoffset, $print['exhibitorNumber']);

            // draw print title block
            $v += $blockheight;
            $pdf->Rect($h, $v, $isize * 0.8, $blockheight);
            $pdf->Rect($h + $isize * 0.8, $v, $isize * 0.2, $blockheight);
            pushFont('Arial', '', 5);
            printXY($h + 0.025, $v + $labeloffset,'TITLE');
            printXY($h + 0.025 + $isize * 0.8, $v + $labeloffset,'PIECE#');
            popFont();

            fitprintXY($h + 0.1, $v + $dataoffset, ($isize * 0.8) - 0.2, $print['title']);
            printXY($h + 0.1 + (0.8 * $isize), $v + $dataoffset, $print['item_key']);

            // draw buyer/count/price line
            $v += $blockheight;
            $pdf->Rect($h, $v, $isize * 0.5, $blockheight);
            $pdf->Rect($h + $isize * 0.5, $v, $isize * 0.25, $blockheight);
            $pdf->Rect($h + $isize * 0.75, $v, $isize * 0.25, $blockheight);
            pushFont('Arial', '', 5);
            printXY($h + 0.025, $v + $labeloffset, 'BUYER (ART SHOW USE ONLY)');
            printXY($h + 0.025 + $isize * 0.5, $v + $labeloffset,'UNIT');
            printXY($h + 0.025 + $isize * 0.625, $v + $labeloffset, 'OF');
            printXY($h + 0.025 + $isize * 0.75, $v + $labeloffset, 'PRICE');
            popFont();

            printXY($h + 0.1 + (0.5 * $isize), $v + $dataoffset, $copy);
            printXY($h + 0.1 + (0.6 * $isize), $v + $dataoffset, $print['original_qty']);
            $priceFmt = $dolfmt->formatCurrency((float)$print['sale_price'], 'USD');
            $pricewidth = $pdf->getStringWidth($priceFmt);
            printXY($h + (0.97 * $isize) - $pricewidth, $v + $dataoffset, $priceFmt);
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

// local constants for the sheets
    $margin = 0.25;
    if ($bidlines <= 4) {
        $orient = 'L';
        $numcols = 3;
        $numrows = 2;
        $vsize = 3.8;
    } else if ($bidlines < 8) {
        $orient = 'P';
        $numcols = 2;
        $numrows = 2;
        $vsize = 5.0;
    } else {
        $orient = 'P';
        $numcols = 2;
        $numrows = 1;
        $vsize = 10.0;
    }

    $indent = 0.1;
    $blockheight = 0.33;
    $priceheight = 0.25;
    $headerheight = 0.15;
    $labeloffset = 0.06;
    $dataoffset = 0.20;
    $priceoffset = 0.14;

    $itemSQL = <<<EOS
SELECT e.exhibitorName, exRY.exhibitorNumber, aI.title, aI.item_key, aI.min_price, aI.sale_price, aI.original_qty, aI.material, aI.type, e.id, eR.name
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
    if ($itemR == false) {
        $response['error'] = 'Error retrieving art items for bid sheets, please seek assistance';
        echo "Error retrieving art items for bid sheets, please seek assistance\n";
        return $response;
    }
    if ($itemR->num_rows == 0) {
        $response['num_rows'] = $itemR->num_rows;
        $response['status'] = 'No art found requiring bid sheets';
        echo "No art found requiring bid sheets\n";
        return $response;
    }

// load data array
    $artItems = [];
    while ($artItem = $itemR->fetch_assoc()) {
        $artItems[] = $artItem;
    }
    $numSheets = $itemR->num_rows;
    $pages = ceil($numSheets / ($numrows * $numcols));

    $curLocale = locale_get_default();
    $dolfmt = new NumberFormatter($curLocale == 'en_US_POSIX' ? 'en-us' : $curLocale, NumberFormatter::CURRENCY);

    $pdf = new Fpdf($orient, 'in', 'Letter');
    initPDF($pdf, 0.008, 'Arial', '', 11);

// computes from those offsets
    $hsize = ($pdf->GetPageWidth() - 2 * $margin) / $numcols;
    $firstrow = $margin + 0.2;

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
            printXY($margin, $margin, "Bid Sheets for $conname's " . $art['name'] . '; Artist: ' . $art['exhibitorName']);
            $fileLabel = $conname . '_' . $art['name'] . '_' . $art['exhibitorName'];
            $y = $pdf->GetPageHeight() - ($margin);
            printXY($margin, $y, "Generated: $createDate");
            $pageStr = "Page $page of $pages";
            $pagewidth = $pdf->getStringWidth($pageStr);
            printXY($pdf->GetPageWidth() - ($margin + $pagewidth), $y, $pageStr);
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
        fitprintXY($h+ $titleoffset, $v + 0.16, $titlewidth, $title);
        popFont();

        // draw artist block
        $v += $blockheight;
        $pdf->Rect($h, $v, $isize * 0.8, $blockheight);
        $pdf->Rect($h + $isize * 0.8, $v, $isize * 0.2, $blockheight);
        pushFont('Arial', '', 5);
        printXY($h + 0.025, $v + $labeloffset,'ARTIST');
        printXY($h + 0.025 + $isize * 0.8, $v + $labeloffset,'ARTIST#');
        popFont();
        fitprintXY($h + 0.1, $v + $dataoffset, (0.8 * $isize),  $art['exhibitorName']);
        printXY($h + 0.1 + (0.8 * $isize), $v + $dataoffset, $art['exhibitorNumber']);

        // draw print title block
        $v += $blockheight;
        $pdf->Rect($h, $v, $isize, $blockheight);
        pushFont('Arial', '', 5);
        printXY($h + 0.025, $v + $labeloffset, 'TITLE');
        popFont();
        fitprintXY($h + 0.1, $v + $dataoffset,$isize-0.2, $art['title']);

        // draw Medium/Piece#
        $v += $blockheight;
        $pdf->Rect($h, $v, $isize * 0.8, $blockheight);
        $pdf->Rect($h + $isize * 0.8, $v, $isize * 0.2, $blockheight);
        pushFont('Arial', '', 5);
        printXY($h + 0.025, $v + $labeloffset, 'MEDIUM');
        printXY($h + 0.025 + $isize * 0.8, $v + $labeloffset, 'PIECE#');
        popFont();
        fitprintXY($h + 0.1, $v + $dataoffset, ($isize * 0.8) -0.2, $art['material']);
        printXY($h + 0.1 + (0.8 * $isize), $v + $dataoffset, $art['item_key']);

        // draw Minimum Bid Amount
        pushLineWidth(0.016);
        $v += $blockheight;
        $pdf->Rect($h, $v, $isize * 0.7, $priceheight);
        $pdf->Rect($h + $isize * 0.7, $v, $isize * 0.3, $priceheight);
        $label = 'Minimum bid amount';
        $length = $pdf->getStringWidth($label);
        printXY($h + ($isize * 0.7) - (0.1 + $length), $v + $priceoffset, $label );

        if ($art['type'] == 'nfs') {
            $priceFmt = 'N/A';
        } else {
            $priceFmt = $dolfmt->formatCurrency((float)$art['min_price'], 'USD');
        }
        $pricewidth = $pdf->getStringWidth($priceFmt);
        printXY($h + (0.97 * $isize) - $pricewidth, $v + $priceoffset, $priceFmt);

        // draw Quick Sale Amount
        $v += $priceheight;
        $pdf->Rect($h, $v, $isize * 0.7, $priceheight);
        $pdf->Rect($h + $isize * 0.7, $v, $isize * 0.3, $priceheight);
        $label = 'Quicksale price';
        $length = $pdf->getStringWidth($label);
        printXY($h + ($isize * 0.7) - (0.1 + $length), $v + $priceoffset, $label );

        $price = $art['sale_price'];
        if ($price > 0 && $art['type'] != 'nfs') {
            $priceFmt = $dolfmt->formatCurrency((float)$art['sale_price'], 'USD');
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
        printXY($h + 0.025, $v + $labeloffset + 0.01, "Bidder Name (Please Print)");
        printXY($h + 0.025 + $isize * 0.6, $v + $labeloffset + 0.01, 'Badge #');
        printXY($h + 0.025 + $isize * 0.8, $v + $labeloffset + 0.01, 'Bid ($)');
        popFont();

        $v += $headerheight;
        pushFont('Arial', '', 6);
        for ($lineno = 1; $lineno <= $numberedLines; $lineno++) {
            $pdf->Rect($h, $v, $isize, $blockheight);
            if ($bidSep) {
                $pdf->Rect($h + $isize * 0.6, $v, $isize * 0.2, $blockheight);
            }
            printXY($h, $v + $labeloffset, "$lineno)");
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
        pushFont('Arial', 'B', 7);
        $pdf->Rect($h, $v, $isize, $blockheight);
        $pdf->SetXY($h, $v + $labeloffset);
        $pdf->MultiCell(0, 0.12, "ART SHOW\nUSE ONLY");
        popFont();

        pushFont('Arial', '', 12);
        $pdf->Rect($h + 0.7, $v + $labeloffset + 0.05, 0.15, 0.15);
        printXY($h + 0.85, $v + $dataoffset, "AUC");
        $pdf->Rect($h + 1.37, $v + $labeloffset + 0.05, 0.15, 0.15);
        printXY($h + 1.52, $v + $dataoffset, 'SOLD');
        $pdf->Rect($h + 2.15, $v + $labeloffset + 0.05, 0.15, 0.15);
        printXY($h + 2.3, $v + $dataoffset, 'QS');
        $pdf->Rect($h + 2.7, $v + $labeloffset + 0.05, 0.15, 0.15, $art['type'] == 'nfs' ? 'DF' : 'D');
        printXY($h + 2.85, $v + $dataoffset, 'NFS');

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
    $response['message'] = "$numSheets  output on $pages pages";
    return $response;
}
?>
