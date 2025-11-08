<?php

class UpcA extends Ean13
{
    /**
     * @param float $x
     * @param float $y
     * @param string $barcode
     * @param float $h
     * @param float $w
     */
    public function drawCode($x, $y, $barcode, $h = 16.0, $w = .35)
    {
        parent::drawCode($x, $y, "0" . $barcode, $h, $w);
    }
}
