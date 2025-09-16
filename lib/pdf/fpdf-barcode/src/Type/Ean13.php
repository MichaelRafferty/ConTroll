<?php

/**
 * Class Ean13
 *
 * Based upon: http://www.fpdf.org/en/script/script5.php
 *
 * @package Fpdf\Type
 */
class Ean13
{
    /** @var FPDF */
    protected $fpdf;
    /** @var array */
    private $codes;
    /** @var array */
    private $parities;

    /**
     * Ean13 constructor.
     * @param FPDF $fpdf
     */
    public function __construct(tFPDF $fpdf)
    {
        $this->fpdf = $fpdf;
        $this->init();
    }

    /**
     * @param float $x
     * @param float $y
     * @param string $barcode
     * @param float $h
     * @param float $w
     */
    public function drawCode($x, $y, $barcode, $h = 16.0, $w = .35)
    {
        //Padding
        $barcode = str_pad($barcode, 12, '0', STR_PAD_LEFT);

        //Add or control the check digit
        if (strlen($barcode) == 12) {
            $barcode .= $this->getCheckDigit($barcode);
        } elseif (!$this->testCheckDigit($barcode)) {
            $this->fpdf->Error('Incorrect check digit');
        }
        //Convert digits to bars

        $code = '101';
        $p = $this->parities[$barcode[0]];
        for ($i = 1; $i <= 6; $i++) {
            $code .= $this->codes[$p[$i - 1]][$barcode[$i]];
        }
        $code .= '01010';
        for ($i = 7; $i <= 12; $i++) {
            $code .= $this->codes['C'][$barcode[$i]];
        }
        $code .= '101';
        //Draw bars
        for ($i = 0; $i < strlen($code); $i++) {
            if ($code[$i] == '1') {
                $this->fpdf->Rect($x + $i * $w, $y, $w, $h, 'F');
            }
        }
    }

    public function init()
    {
        $this->codes = array(
            'A' => array(
                '0' => '0001101',
                '1' => '0011001',
                '2' => '0010011',
                '3' => '0111101',
                '4' => '0100011',
                '5' => '0110001',
                '6' => '0101111',
                '7' => '0111011',
                '8' => '0110111',
                '9' => '0001011',
            ),
            'B' => array(
                '0' => '0100111',
                '1' => '0110011',
                '2' => '0011011',
                '3' => '0100001',
                '4' => '0011101',
                '5' => '0111001',
                '6' => '0000101',
                '7' => '0010001',
                '8' => '0001001',
                '9' => '0010111',
            ),
            'C' => array(
                '0' => '1110010',
                '1' => '1100110',
                '2' => '1101100',
                '3' => '1000010',
                '4' => '1011100',
                '5' => '1001110',
                '6' => '1010000',
                '7' => '1000100',
                '8' => '1001000',
                '9' => '1110100',
            ),
        );

        $this->parities = array(
            '0' => array('A', 'A', 'A', 'A', 'A', 'A'),
            '1' => array('A', 'A', 'B', 'A', 'B', 'B'),
            '2' => array('A', 'A', 'B', 'B', 'A', 'B'),
            '3' => array('A', 'A', 'B', 'B', 'B', 'A'),
            '4' => array('A', 'B', 'A', 'A', 'B', 'B'),
            '5' => array('A', 'B', 'B', 'A', 'A', 'B'),
            '6' => array('A', 'B', 'B', 'B', 'A', 'A'),
            '7' => array('A', 'B', 'A', 'B', 'A', 'B'),
            '8' => array('A', 'B', 'A', 'B', 'B', 'A'),
            '9' => array('A', 'B', 'B', 'A', 'B', 'A'),
        );
    }

    /**
     * @param string $barcode
     * @return int
     */
    private function getCheckDigit($barcode)
    {
        //Compute the check digit
        $sum = 0;
        for ($i = 1; $i <= 11; $i += 2) {
            $sum += 3 * $barcode[$i];
        }
        for ($i = 0; $i <= 10; $i += 2) {
            $sum += $barcode[$i];
        }
        $r = $sum % 10;
        if ($r > 0) {
            $r = 10 - $r;
        }

        return $r;
    }

    /**
     * @param string $barcode
     * @return bool
     */
    private function testCheckDigit($barcode)
    {
        //Test validity of check digit
        $sum = 0;
        for ($i = 1; $i <= 11; $i += 2) {
            $sum += 3 * $barcode[$i];
        }
        for ($i = 0; $i <= 10; $i += 2) {
            $sum += $barcode[$i];
        }

        return ($sum + $barcode[12]) % 10 == 0;
    }
}
