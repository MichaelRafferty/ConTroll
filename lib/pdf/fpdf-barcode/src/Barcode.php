<?php

require_once ("Type/Code128.php");
require_once ("Type/Code39.php");
require_once ("Type/Ean13.php");
require_once ("Type/UpcA.php");

class Barcode extends tFPDF
{
    /** @var array */
    protected $barcodes;

    public function __construct($orientation='P', $unit='mm', $size='A4')
    {
        $this->barcodes = array();
        parent::__construct($orientation, $unit, $size);
    }

    /**
     * @param float $x
     * @param float $y
     * @param string $code
     * @param float $width
     * @param float $height
     */
    public function code128($x, $y, $code, $width, $height)
    {
        if (!isset($this->barcodes['code128'])) {
            $this->barcodes['code128'] = new Code128($this);
        }

        $this->barcodes['code128']->drawCode($x, $y, $code, $width, $height);
    }

    /**
     * @param float $x
     * @param float $y
     * @param string $code
     * @param float $width
     * @param float $height
     * @param bool $isWide
     * @param bool $extended
     * @param bool $needChecksum
     * @param bool $displayText
     */
    public function code39($x, $y, $code, $width=0.4, $height = 20.0, $isWide = false, $extended = true, $needChecksum = false, $displayText = false)
    {
        if (!isset($this->barcodes['code39'])) {
            $this->barcodes['code39'] = new Code39($this);
        }

        $this->barcodes['code39']->drawCode($x, $y, $code, $extended, $needChecksum, $width, $height, $isWide, $displayText);
    }

    /**
     * @param float $x
     * @param float $y
     * @param string $code
     * @param float $width
     * @param float $height
     */
    public function ean13($x, $y, $code, $width = .35, $height = 16.0)
    {
        if (!isset($this->barcodes['ean13'])) {
            $this->barcodes['ean13'] = new Ean13($this);
        }

        $this->barcodes['ean13']->drawCode($x, $y, $code, $height, $width);
    }

    /**
     * @param float $x
     * @param float $y
     * @param string $code
     * @param float $width
     * @param float $height
     */
    public function upcA($x, $y, $code, $width = .35, $height = 16.0)
    {
        if (!isset($this->barcodes['upca'])) {
            $this->barcodes['upca'] = new UpcA($this);
        }

        $this->barcodes['upca']->drawCode($x, $y, $code, $height, $width);
    }
}
