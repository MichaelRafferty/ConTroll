<?php
// pdfPrintArtShowSheets.php - routines for creating the print shop price tag sheets
require_once (__DIR__ . '/../Composer/vendor/autoload.php');
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
WHERE exRY.exhibitorYearId=? AND exRY.exhibitsRegionYearId = ? AND aI.type = 'print';
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
    while ($artItem = $itemR->fetch_assoc()) {
        $artItems[] = $artItem;
    }
    $numTags = 0;

    $pdf = new Fpdf('L', 'in', 'Letter');
    $pdf->SetAutoPageBreak(false);

    // computes from those offsets
    $hsize = ($pdf->GetPageWidth() - 2 * $margin) / $numcols;
    $firstrow = $margin + 0.5;

    $pdf->SetFont('Arial', '', 14);
    $titlewidth = $pdf->getStringWidth($title);
    $titleoffset = ($hsize - (2 * $indent) - $titlewidth) / 2;

    $curLocale = locale_get_default();
    $dolfmt = new NumberFormatter($curLocale == 'en_US_POSIX' ? 'en-us' : $curLocale, NumberFormatter::CURRENCY);

    // timestamp for printing when generated
    $createDate = date('Y/m/d h:i:s A');
    $fileDate = date('Y-m-d-H-i-s');

    $row = $numrows;
    $col = $numcols;
    $pages = 0;
    foreach ($artItems as $print) {
        for ($copy = 1; $copy <= $print['original_qty']; $copy++) {
            $numTags++;
            // set up for next item
            $col++;
            if ($col >= $numcols) {
                $row++;
                $col = 0;
            }
            if ($row >= $numrows) {
                $row = 0;
                $pdf->AddPage();
                $pages++;
                $pdf->SetFont('Arial', 'B', 11);
                $pdf->setXY($margin, $margin);
                $pdf->Cell(0, 0, "Price Tags for $conname's " . $print['name'] . "; Artist: " . $print['exhibitorName']);
                $fileLabel = $conname . "_" . $print['name'] . "_" . $print['exhibitorName'];
                $page = "Page $pages";
                $pagewidth = $pdf->getStringWidth($page);
                $pdf->setXY($pdf->GetPageWidth() - ($margin + $pagewidth), $margin);
                $pdf->Cell(0, 0, $page);
                $y = $pdf->GetPageHeight() - ($margin);
                $pdf->setXY($margin, $y);
                $pdf->Cell(0, 0, "Generated: $createDate");
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
            $pdf->SetFont('Arial', '', 14);
            $pdf->SetXY($h + $titleoffset, $v + 0.16);
            $pdf->Cell($titlewidth, 0, $title);

            // draw artist block
            $v += $blockheight;
            $pdf->Rect($h, $v, $isize * 0.8, $blockheight);
            $pdf->Rect($h + $isize * 0.8, $v, $isize * 0.2, $blockheight);
            $pdf->SetFont('Arial', '', 5);
            $pdf->SetXY($h + 0.025, $v + $labeloffset);
            $pdf->Cell(0, 0, 'ARTIST');
            $pdf->SetXY($h + 0.025 + $isize * 0.8, $v + $labeloffset);
            $pdf->Cell(0, 0, 'ARTIST#');

            $pdf->SetFont('Arial', '', 11);
            $len = $pdf->GetStringWidth($print['exhibitorName']);
            if ($len > (($isize * 0.8) - 0.2)) {
                $pdf->SetFont('Arial', '', 8);
                $pdf->SetXY($h + 0.1, $v + $dataoffset/2);
                $pdf->MultiCell(($isize * 0.8) -0.2, 0.1, $print['exhibitorName']);
                $pdf->SetFont('Arial', '', 11);
            } else {
                $pdf->SetXY($h + 0.1, $v + $dataoffset);
                $pdf->Cell(0, 0, $print['exhibitorName']);
            }
            $pdf->SetXY($h + 0.1 + (0.8 * $isize), $v + $dataoffset);
            $pdf->Cell(0, 0, $print['exhibitorNumber']);

            // draw print title block
            $v += $blockheight;
            $pdf->Rect($h, $v, $isize * 0.8, $blockheight);
            $pdf->Rect($h + $isize * 0.8, $v, $isize * 0.2, $blockheight);
            $pdf->SetFont('Arial', '', 5);
            $pdf->SetXY($h + 0.025, $v + $labeloffset);
            $pdf->Cell(0, 0, 'TITLE');
            $pdf->SetXY($h + 0.025 + $isize * 0.8, $v + $labeloffset);
            $pdf->Cell(0, 0, 'PIECE#');

            $pdf->SetFont('Arial', '', 11);
            $len = $pdf->GetStringWidth($print['title']);
            if ($len > (($isize * 0.8) - 0.2)) {
                $pdf->SetFont('Arial', '', 8);
                $pdf->SetXY($h + 0.1, $v + $dataoffset/2);
                $pdf->MultiCell(($isize * 0.8) -0.2, 0.1, $print['title']);
                $pdf->SetFont('Arial', '', 11);
            } else {
                $pdf->SetXY($h + 0.1, $v + $dataoffset);
                $pdf->Cell(0, 0, $print['title']);
            }
            $pdf->SetXY($h + 0.1 + (0.8 * $isize), $v + $dataoffset);
            $pdf->Cell(0, 0, $print['item_key']);

            // draw buyer/count/price line
            $v += $blockheight;
            $pdf->Rect($h, $v, $isize * 0.5, $blockheight);
            $pdf->Rect($h + $isize * 0.5, $v, $isize * 0.25, $blockheight);
            $pdf->Rect($h + $isize * 0.75, $v, $isize * 0.25, $blockheight);
            $pdf->SetFont('Arial', '', 5);
            $pdf->SetXY($h + 0.025, $v + $labeloffset);
            $pdf->Cell(0, 0, 'BUYER (ART SHOW USE ONLY)');
            $pdf->SetXY($h + 0.025 + $isize * 0.5, $v + $labeloffset);
            $pdf->Cell(0, 0, 'UNIT');
            $pdf->SetXY($h + 0.025 + $isize * 0.625, $v + $labeloffset);
            $pdf->Cell(0, 0, 'OF');
            $pdf->SetXY($h + 0.025 + $isize * 0.75, $v + $labeloffset);
            $pdf->Cell(0, 0, 'PRICE');

            $pdf->SetFont('Arial', '', 11);
            $pdf->SetXY($h + 0.1 + (0.5 * $isize), $v + $dataoffset);
            $pdf->Cell(0, 0, $copy);
            $pdf->SetXY($h + 0.1 + (0.6 * $isize), $v + $dataoffset);
            $pdf->Cell(0, 0, $print['original_qty']);
            $priceFmt = $dolfmt->formatCurrency((float)$print['sale_price'], 'USD');
            $pricewidth = $pdf->getStringWidth($priceFmt);
            $pdf->SetXY($h + (0.97 * $isize) - $pricewidth, $v + $dataoffset);
            $pdf->Cell(0, 0, $priceFmt);
        }
    }

    $y = $pdf->GetPageHeight() - $margin;

    $last = "Last Page";
    $pagewidth = $pdf->getStringWidth($last);
    $pdf->setXY($pdf->GetPageWidth() - ($margin + $pagewidth), $y);
    $pdf->Cell(0, 0, $last);

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
    $title = $vendor['artistBidSheet'];
    $bidlines = $vendor['artistBidSheetLines'];
    $numberedLines = $vendor['artistBidSheetNumbers'];

// local constants for the sheets
    $margin = 0.25;
    if ($bidlines <= 4) {
        $orient = 'L';
        $numcols = 3;
        $numrows = 2;
        $vsize = 3.8;
    } else if ($bidlines <= 8) {
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
WHERE exRY.exhibitorYearId=? AND exRY.exhibitsRegionYearId = ? AND aI.type in ('art','nfs');
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

    $title = $vendor['artistBidSheet'];
    if ($title == null || $title == '') {
        $title = 'Unconfigured Art Show Bid Sheets';
    }

// load data array
    $artItems = [];
    while ($artItem = $itemR->fetch_assoc()) {
        $artItems[] = $artItem;
    }
    $numSheets = $itemR->num_rows;

    $pdf = new Fpdf($orient, 'in', 'Letter');
    $pdf->SetAutoPageBreak(false);
    $pdf->SetLineWidth(0.008);

// computes from those offsets
    $hsize = ($pdf->GetPageWidth() - 2 * $margin) / $numcols;
    $firstrow = $margin + 0.2;

    $pdf->SetFont('Arial', '', 14);
    $titlewidth = $pdf->getStringWidth($title);
    $titleoffset = ($hsize - (2 * $indent) - $titlewidth) / 2;

    $curLocale = locale_get_default();
    $dolfmt = new NumberFormatter($curLocale == 'en_US_POSIX' ? 'en-us' : $curLocale, NumberFormatter::CURRENCY);

// timestamp for printing when generated
    $createDate = date('Y/m/d h:i:s A');
    $fileDate = date('Y-m-d-H-i-s');

    $row = $numrows;
    $col = $numcols;
    $pages = 0;
    foreach ($artItems as $art) {
        for ($copy = 1; $copy <= $art['original_qty']; $copy++) {
            // set up for next item
            $col++;
            if ($col >= $numcols) {
                $row++;
                $col = 0;
            }
            if ($row >= $numrows) {
                $row = 0;
                $pdf->AddPage();
                $pages++;
                $pdf->SetFont('Arial', 'B', 11);
                $pdf->setXY($margin, $margin);
                $pdf->Cell(0, 0, "Bid Sheets for $conname's " . $art['name'] . '; Artist: ' . $art['exhibitorName']);
                $fileLabel = $conname . '_' . $art['name'] . '_' . $art['exhibitorName'];
                $page = "Page $pages";
                $pagewidth = $pdf->getStringWidth($page);
                $pdf->setXY($pdf->GetPageWidth() - ($margin + $pagewidth), $margin);
                $pdf->Cell(0, 0, $page);
                $y = $pdf->GetPageHeight() - ($margin);
                $pdf->setXY($margin, $y);
                $pdf->Cell(0, 0, "Generated: $createDate");
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
            $pdf->SetFont('Arial', '', 14);
            $pdf->SetXY($h + $titleoffset, $v + 0.16);
            $pdf->Cell($titlewidth, 0, $title);

            // draw artist block
            $v += $blockheight;
            $pdf->Rect($h, $v, $isize * 0.8, $blockheight);
            $pdf->Rect($h + $isize * 0.8, $v, $isize * 0.2, $blockheight);
            $pdf->SetFont('Arial', '', 5);
            $pdf->SetXY($h + 0.025, $v + $labeloffset);
            $pdf->Cell(0, 0, 'ARTIST');
            $pdf->SetXY($h + 0.025 + $isize * 0.8, $v + $labeloffset);
            $pdf->Cell(0, 0, 'ARTIST#');

            $pdf->SetFont('Arial', '', 11);
            $len = $pdf->GetStringWidth($art['exhibitorName']);
            if ($len > (($isize * 0.8) - 0.2)) {
                $pdf->SetFont('Arial', '', 8);
                $pdf->SetXY($h + 0.1, $v + $dataoffset/2);
                $pdf->MultiCell(($isize * 0.8) -0.2, 0.1, $art['exhibitorName']);
                $pdf->SetFont('Arial', '', 11);
            } else {
                $pdf->SetXY($h + 0.1, $v + $dataoffset);
                $pdf->Cell(0, 0, $art['exhibitorName']);
            }
            $pdf->SetXY($h + 0.1 + (0.8 * $isize), $v + $dataoffset);
            $pdf->Cell(0, 0, $art['exhibitorNumber']);

            // draw print title block
            $v += $blockheight;
            $pdf->Rect($h, $v, $isize, $blockheight);
            $pdf->SetFont('Arial', '', 5);
            $pdf->SetXY($h + 0.025, $v + $labeloffset);
            $pdf->Cell(0, 0, 'TITLE');

            $pdf->SetFont('Arial', '', 11);

            $len = $pdf->GetStringWidth($art['title']);
            if ($len > ($isize - 0.2)) {
                $pdf->SetFont('Arial', '', 8);
                $pdf->SetXY($h + 0.1, $v + $dataoffset/2);
                $pdf->MultiCell($isize-0.2, 0.1, $art['title']);
                $pdf->SetFont('Arial', '', 11);
            } else {
                $pdf->SetXY($h + 0.1, $v + $dataoffset);
                $pdf->Cell(0, 0, $art['title']);
            }

            // draw Medium/Piece#
            $v += $blockheight;
            $pdf->Rect($h, $v, $isize * 0.8, $blockheight);
            $pdf->Rect($h + $isize * 0.8, $v, $isize * 0.2, $blockheight);
            $pdf->SetFont('Arial', '', 5);
            $pdf->SetXY($h + 0.025, $v + $labeloffset);
            $pdf->Cell(0, 0, 'MEDIUM');
            $pdf->SetXY($h + 0.025 + $isize * 0.8, $v + $labeloffset);
            $pdf->Cell(0, 0, 'PIECE#');

            $pdf->SetFont('Arial', '', 11);
            $len = $pdf->GetStringWidth($art['material']);
            if ($len > (($isize * 0.8) - 0.2)) {
                $pdf->SetFont('Arial', '', 8);
                $pdf->SetXY($h + 0.1, $v + $dataoffset/2);
                $pdf->MultiCell(($isize * 0.8) -0.2, 0.1, $art['material']);
                $pdf->SetFont('Arial', '', 11);
            } else {
                $pdf->SetXY($h + 0.1, $v + $dataoffset);
                $pdf->Cell(0, 0, $art['material']);
            }
            $pdf->SetXY($h + 0.1 + (0.8 * $isize), $v + $dataoffset);
            $pdf->Cell(1, 0, $art['item_key']);

            // draw Minimum Bid Amount
            $pdf->SetLineWidth(0.016);
            $v += $blockheight;
            $pdf->Rect($h, $v, $isize * 0.7, $priceheight);
            $pdf->Rect($h + $isize * 0.7, $v, $isize * 0.3, $priceheight);
            $pdf->SetFont('Arial', '', 11);
            $label = 'Minimum bid amount';
            $length = $pdf->getStringWidth($label);
            $pdf->SetXY($h + ($isize * 0.7) - (0.1 + $length), $v + $priceoffset);
            $pdf->Cell(0, 0, $label );

            if ($art['type'] == 'nfs') {
                $priceFmt = 'N/A';
            } else {
                $priceFmt = $dolfmt->formatCurrency((float)$art['min_price'], 'USD');
            }
            $pricewidth = $pdf->getStringWidth($priceFmt);
            $pdf->SetXY($h + (0.97 * $isize) - $pricewidth, $v + $priceoffset);
            $pdf->Cell(0, 0, $priceFmt);

            // draw Quick Sale Amount
            $v += $priceheight;
            $pdf->Rect($h, $v, $isize * 0.7, $priceheight);
            $pdf->Rect($h + $isize * 0.7, $v, $isize * 0.3, $priceheight);
            $pdf->SetFont('Arial', '', 11);
            $label = 'Quicksale price';
            $length = $pdf->getStringWidth($label);
            $pdf->SetXY($h + ($isize * 0.7) - (0.1 + $length), $v + $priceoffset);
            $pdf->Cell(0, 0, $label );

            $price = $art['sale_price'];
            if ($price > 0 && $art['type'] != 'nfs') {
                $priceFmt = $dolfmt->formatCurrency((float)$art['sale_price'], 'USD');
            } else {
                $priceFmt = "N/A";
            }
            $pricewidth = $pdf->getStringWidth($priceFmt);
            $pdf->SetXY($h + (0.97 * $isize) - $pricewidth, $v + $priceoffset);
            $pdf->Cell(0, 0, $priceFmt);

            $pdf->SetLineWidth(0.008);
            // now bid header
            $v += $priceheight;
            $headerStart = $v;
            $pdf->Rect($h, $v, $isize * 0.6, $headerheight);
            $pdf->Rect($h + $isize * 0.6, $v, $isize * 0.2, $headerheight);
            $pdf->Rect($h + $isize * 0.8, $v, $isize * 0.2, $headerheight);
            $pdf->SetFont('Arial', '', 8);
            $pdf->SetXY($h + 0.025, $v + $labeloffset + 0.01);
            $pdf->Cell(0, 0, "Bidder Name");
            $pdf->SetXY($h + 0.025 + $isize * 0.6, $v + $labeloffset + 0.01);
            $pdf->Cell(0, 0, 'Badge #');
            $pdf->SetXY($h + 0.025 + $isize * 0.8, $v + $labeloffset + 0.01);
            $pdf->Cell(0, 0, 'Bid ($)');
        }

        $v += $headerheight;
        $pdf->SetFont('Arial', '', 6);
        for ($lineno = 1; $lineno <= $numberedLines; $lineno++) {
            $pdf->Rect($h, $v, $isize, $blockheight);
            $pdf->SetXY($h, $v + $labeloffset);
                $pdf->Cell(0, 0, "$lineno)");

            $v += $blockheight;
        }
        for (; $lineno <= $bidlines; $lineno++) {
            $pdf->Rect($h, $v, $isize, $blockheight);
            $v += $blockheight;
        }

        // artshow use only block
        $pdf->SetFont('Arial', 'B', 7);
        $pdf->Rect($h, $v, $isize, $blockheight);
        $pdf->SetXY($h, $v + $labeloffset);
        $pdf->MultiCell(0, 0.12, "ART SHOW\nUSE ONLY");
        //$pdf->SetXY($h, $v + $labeloffset + 0.19);
        //$pdf->Cell(0, 0, 'USE ONLY:');

        $pdf->SetFont('Arial', '', 12);
        $pdf->Rect($h + 0.7, $v + $labeloffset + 0.05, 0.15, 0.15);
        $pdf->SetXY($h + 0.85, $v + $dataoffset);
        $pdf->Cell(0, 0, "AUC");

        $pdf->Rect($h + 1.37, $v + $labeloffset + 0.05, 0.15, 0.15);
        $pdf->SetXY($h + 1.52, $v + $dataoffset);
        $pdf->Cell(0, 0, 'SOLD');

        $pdf->Rect($h + 2.15, $v + $labeloffset + 0.05, 0.15, 0.15);
        $pdf->SetXY($h + 2.3, $v + $dataoffset);
        $pdf->Cell(0, 0, 'QS');

        $pdf->Rect($h + 2.7, $v + $labeloffset + 0.05, 0.15, 0.15, $art['type'] == 'nfs' ? 'DF' : 'D');
        $pdf->SetXY($h + 2.85, $v + $dataoffset);
        $pdf->Cell(0, 0, 'NFS');

        $headerEnd = $v + $blockheight;
        $pdf->SetLineWidth(0.024);
        $pdf->Rect($h, $headerStart, $isize, $headerEnd - $headerStart);
        $pdf->SetLineWidth(0.008);

    }

    $y = $pdf->GetPageHeight() - $margin;

    $last = 'Last Page';
    $pagewidth = $pdf->getStringWidth($last);
    $pdf->setXY($pdf->GetPageWidth() - ($margin + $pagewidth), $y);
    $pdf->Cell(0, 0, $last);

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
