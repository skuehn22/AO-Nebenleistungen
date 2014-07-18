<?php
/**
 * Berechnung der Textbreite für Pdf
 *
 * Ermittelt die Breite des Textes in Punkte
 * und berechnet den neuen Rechtswert für rechtsbündige
 * Spalten
 *
 * @author Stephan.Krauss
 * @date 02.04.13
 * @file ToolPdf.php
 * @package tools
 */


class nook_ToolPdf{

    protected $_font = null;
    protected $_fontSize = null;
    protected $_text = null;
    protected $_rechterAbstand = null;

    protected $_stringWidth = 0;
    protected $_hPadding = 0; // horizontales padding der Tabellen Zelle;

    // Fehler
    private $_error_abstand_falsch = 1370;
    private $_error_text_zu_gross = 1371;
    private $_error_werte_fehlen = 1372;

    /**
     * @param $__font
     * @return nook_ToolPdf
     */
    public function setFont($__font)
    {
        $this->_font = $__font;

        return $this;
    }

    /**
     * @param $__fontSize
     * @return nook_ToolPdf
     */
    public function setFontSize($__fontSize)
    {
        $this->_fontSize = $__fontSize;

        return $this;
    }

    /**
     * @param $__hPadding
     * @return nook_ToolPdf
     */
    public function setHPadding($__hPadding)
    {
        $this->_hPadding = $__hPadding;

        return $this;
    }

    /**
     * @param $__rechterAbstand
     * @return nook_ToolPdf
     */
    public function setRechteBegrenzung($__rechterAbstand)
    {
        $this->_rechterAbstand = $__rechterAbstand;

        return $this;
    }

    /**
     * @param $__text
     * @return nook_ToolPdf
     */
    public function setText($__text)
    {
        $this->_text = $__text;

        return $this;
    }

    /**
     * Übernimmt und kontrolliert die Ausgangswerte.
     * Gibt den neu berechneten Rechtswert zurück.
     *
     * @return mixed
     * @throws nook_Exception
     */
    public function berchneRechtswert(){

        if(strlen($this->_text) < 2 )
            throw new nook_Exception($this->_error_werte_fehlen);

        if(empty($this->_font))
            throw new nook_Exception($this->_error_werte_fehlen);

        if(empty($this->_fontSize))
            throw new nook_Exception($this->_error_werte_fehlen);

        if(empty($this->_rechterAbstand))
            throw new nook_Exception($this->_error_werte_fehlen);

        $this->_getTextBreite();
        $berechneterRechtswert = $this->_neuerRechtswert();

        return $berechneterRechtswert;
    }

    /**
    * Gibt die Breite eines Textes in 'points' zurück.
    *
    */
    private function _getTextBreite(){

        $text = $this->_text;
        $text = trim($text);
        $drawingString = iconv( '', 'UTF-16BE', $text );
        $characters = array();

        for ($i = 0; $i < strlen($drawingString); $i++) {
            $characters[] = (ord($drawingString[$i++]) << 8 ) | ord($drawingString[$i]);
        }

        $glyphs = $this->_font->glyphNumbersForCharacters($characters);
        $widths = $this->_font->widthsForGlyphs($glyphs);
        $stringWidth = (array_sum($widths) / $this->_font->getUnitsPerEm()) * $this->_fontSize;

        $this->_stringWidth = $stringWidth;


        return;
    }

    /**
    * Ermittelt den neuen Rechtswert im Pdf Dokument
    *
    * @param $__textbreite
    * @param $__linkerAbstand
    * @param $__rechterAbstand
    * @return mixed
    * @throws Exception
    */
    private function _neuerRechtswert(){

        $berechneterRechtswert = $this->_rechterAbstand - $this->_stringWidth - $this->_hPadding;

        return $berechneterRechtswert;
    }

    /**
     * rechnet Points in mm um
     *
     * @param $points
     * @return float
     */
    public function pointsToMm( $points )
    {
        return $points / 72 * 25.4;
    }

    /**
     * rechnet mm in Points um
     *
     * @param $mm
     * @return float
     */
    public function mmToPoints( $mm )
    {
        return $mm / 25.4 * 72;
    }

} // end class