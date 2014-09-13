<?php
/**
 * Wrapper für Zend_Pdf
 * + Übergeben des Papierlayout und der Papiergröße
 * + Übergeben des Papierlayout und der Papiergröße
 * + Erstellt ein leeres Pdf Dokument
 * + Erstellt die Font und Farben des Dokumentes
 * + Erstellt und definiert die Farben
 * + setzen der Meta Angaben des Pdf Dokumentes
 * + Setzen der Abstände an den Rändern des Dokumentes
 * + Erstellt eine neue Seite
 * + Steuerung von Schriftgroesse , Schriftform und Schriftstärke
 * + Setzen Font für momentane Seite des Dokumentes
 * + Setzen der Füllfarbe
 * + Setzen der Linienfarbe
 * + Setzen der Linienstärke
 * + Zeichnen eines Rechteck
 * + Ausgabe oder Rendern des Pdf Dokumentes
 * + Ermitteln Registrierungsnummer mir Session
 * + Just in case you don't want this class to automatically add page breaks...
 * + Rotieren eines Textes
 * + Einfache Methode zum schreiben eines Textblockes mittels eines Array
 * + Schreiben eines Textblockes
 * + Schreibt den Text unter Beachtung des Spaltenmodels des Pdf
 * + Ermitteln der Textlänge
 * + Bewegt den Cursor nach links und auf die nächste Zeile
 * + Fügt einen Link hinzu
 * + Zeichnet eine Linie
 * + Zeichnet eine Grafik
 * + zeichnet eine grafik
 * + Umrechnung von Points in mm
 * + Rechnet mm in Points
 * + Gibt die X - Koordinate zurück
 * + Setzen der X - Koordinate / Tiefwert
 * + Gibt Y - Koordinate zurück
 * + Setzen der Y - Koordinate
 *
 * @date 02.26.2013
 * @file WrapPdf.php
 * @package front
 * @subpackage model
 */
class Front_Model_WrapperPdf
{
    // Fehler
    private $error = 1830;

    protected $zpdf;
    protected $pages = array();
    protected $tocEntries = array();
    protected $currentPage = -1;
    protected $cx; // This represents the current Y-coordinate of the cursor in millimetres
    protected $cy; // This represents the current Y-coordinate of the cursor in millimetres
    protected $colours = array();
    protected $layout;
    protected $paperSize;
    protected $paperWidth;
    protected $paperHeight;
    protected $autoPageBreak = true;

    protected $fontRegular;
    protected $fontItalic;
    protected $fontBold;
    protected $fontBoldItalic;
    protected $fontLight;
    protected $fontLightItalic;

    protected $ueberschrift;
    protected $text;
    protected $minitext;

    protected $pfad = null;

    // The following attributes keep track of the current settings for the page.
    protected $currentFont = null;
    protected $currentFontSize = 0;
    protected $currentFontSpacing = 0; // This might be called "line spacing" or "leading" (the latter being the more
    // technically correct term in the publishing world), however both of those terms
    // measure the distance between lines relative to the font size. We, however, are
    // measuring the spacing in terms of mm between the top of one printed line and
    // the top of the next printed line, hence the creation of a different label.

    protected $currentLineWidth = 0;
    protected $currentLineColour = '';
    protected $currentFillColour = '';
    protected $currentMarginLeft = 20;
    protected $currentMarginRight = 20;
    protected $currentMarginTop = 20;
    protected $currentMarginBottom = 50;

    protected $logoImage = "vorlagen/logo.png";

    // Cache images so that if they are loaded more than once, we re-use them
    protected $imageCache = array();

    /**
     * Übergeben des Papierlayout und der Papiergröße
     *
     * @param string $layout
     * @param string $paperSize
     */
    public function __construct($layout = 'P', $paperSize = 'A4')
    {
        $this->layout = $layout; // 'P' for Portrait, 'L' for Landscape
        $this->paperSize = $paperSize; // 'A4' or 'Letter'

        if ($paperSize == 'Letter') {
            if ($layout == 'P') {
                $this->paperWidth = 216; // millimetres
                $this->paperHeight = 279; // millimetres
                $this->paperSizeDetail = Zend_Pdf_Page::SIZE_LETTER;
            } else {
                $this->paperWidth = 279; // millimetres
                $this->paperHeight = 216; // millimetres
                $this->paperSizeDetail = Zend_Pdf_Page::SIZE_LETTER_LANDSCAPE;
            }
        } else {
            if ($layout == 'P') {
                $this->paperWidth = 210; // millimetres
                $this->paperHeight = 297; // millimetres
                $this->paperSizeDetail = Zend_Pdf_Page::SIZE_A4;
            } else {
                $this->paperWidth = 297; // millimetres
                $this->paperHeight = 210; // millimetres
                $this->paperSizeDetail = Zend_Pdf_Page::SIZE_A4_LANDSCAPE;
            }
        }
    }

    /**
     * Erstellt ein leeres Pdf Dokument
     *
     * @return Front_Model_WrapperPdf
     */
    public function createEmptyPdf()
    {
        $this->zpdf = new Zend_Pdf();

        return $this;
    }

    /**
     * @param $path
     * @return Front_Model_WrapperPdf
     */
    public function createPdfFromOriginal()
    {
        $this->currentPage = 0;

        $this->zpdf = Zend_Pdf::load($this->pfad . "/HOB_Briefpapier.pdf");

        $font = Zend_Pdf_Font::fontWithName(Zend_Pdf_Font::FONT_HELVETICA);

        $this->cx = $this->currentMarginLeft;
        $this->cy = $this->currentMarginTop;
        $this->currentFont = $font;
        $this->currentFontSize = 10;

        $this->zpdf->pages[$this->currentPage]->setFont($this->currentFont, $this->currentFontSize);

        return $this;
    }

    /**
     * @param $pfad
     * @return Front_Model_WrapperPdf
     */
    public function setPfad($pfad)
    {
        $this->pfad = $pfad;

        return $this;
    }

    /**
     * Erstellt die Font und Farben des Dokumentes
     * + setzen der Schrift Fonts
     * + wenn keine Vorgabe, dann Standard
     * + wenn Vorgabe, dann Übernahme der Schriftart
     *
     * @return Front_Model_WrapPdf
     */
    public function createFont($fontName = false)
    {
        // Standard
        if (empty($fontName)) {
            $this->fontRegular = Zend_Pdf_Font::fontWithPath('font/JUICE_Regular.ttf');
            $this->fontItalic = Zend_Pdf_Font::fontWithPath('font/JUICE_Italic.ttf');
            $this->fontBold = Zend_Pdf_Font::fontWithPath('font/JUICE_Bold.ttf');
            $this->fontBoldItalic = Zend_Pdf_Font::fontWithPath('font/JUICE_Bold_Italic.ttf');
            $this->fontLight = Zend_Pdf_Font::fontWithPath('font/JUICE_Light.ttf');
            $this->fontLightItalic = Zend_Pdf_Font::fontWithPath('font/JUICE_Light_Italic.ttf');
        } // Vorgabe Font
        elseif ($fontName == 'Helvetica') {
            $this->fontRegular = Zend_Pdf_Font::fontWithName(Zend_Pdf_Font::FONT_HELVETICA);
            $this->fontItalic = Zend_Pdf_Font::fontWithName(Zend_Pdf_Font::FONT_HELVETICA_ITALIC);
            $this->fontBold = Zend_Pdf_Font::fontWithName(Zend_Pdf_Font::FONT_HELVETICA_BOLD);
            $this->fontBoldItalic = Zend_Pdf_Font::fontWithName(Zend_Pdf_Font::FONT_HELVETICA_BOLD_ITALIC);
            $this->fontLight = Zend_Pdf_Font::fontWithName(Zend_Pdf_Font::FONT_HELVETICA);
            $this->fontLightItalic = Zend_Pdf_Font::fontWithName(Zend_Pdf_Font::FONT_HELVETICA_ITALIC);
        }

        return $this;
    }

    /**
     * Erstellt und definiert die Farben
     * + Set up colours - and here's a BIG tip
     * + Zend_Pdf_Color_Rgb() expects values from 0 to 1, NOT 0 to 255 !!!
     *
     * @return Front_Model_WrapPdf
     */
    public function createColors()
    {
        $this->colours['black'] = new Zend_Pdf_Color_Rgb(0, 0, 0);
        $this->colours['white'] = new Zend_Pdf_Color_Rgb(1, 1, 1);
        $this->colours['grey'] = new Zend_Pdf_Color_Rgb(0.3, 0.3, 0.3);
        $this->colours['blue'] = new Zend_Pdf_Color_Rgb(0, 0.5, 0.8);
        $this->colours['green'] = new Zend_Pdf_Color_Rgb(0.6, 0.8, 0.7);

        return $this;
    }

    /**
     * setzen der Meta Angaben des Pdf Dokumentes
     *
     * @param $title
     * @param string $subject
     * @param string $author
     * @param string $producer
     * @param string $creator
     * @param string $keywords
     * @return Front_Model_WrapPdf
     */
    public function setProperties($title, $subject = '', $author = '', $producer = '', $creator = '', $keywords = '')
    {
        // See the metadata section (10.2) in the following file for the precise definition of each of these fields:
        // http://partners.adobe.com/public/developer/en/pdf/PDFReference16.pdf#page=794
        $this->zpdf->properties['Title'] = $title;
        $this->zpdf->properties['Subject'] = $subject;
        $this->zpdf->properties['Author'] = $author; // The name of the person who created the document.

        $this->zpdf->properties['Producer'] = $producer; // If  the document was converted to PDF from another format,
        // the name of the application that converted it to PDF. This
        // should theoretically be left blank most of the time, as the
        // PDF is being created from scratch, not converted from some
        // other document.

        $this->zpdf->properties['Creator'] = $creator; // If the document was converted to PDF from another format, the
        // name of the application that created the original document
        // from which it was converted. This should theoretically be
        // left blank most of the time, as the PDF is being created from
        // scratch, not converted from some other format.

        $this->zpdf->properties['Keywords'] = $keywords;

        $now = 'D:' . date("YmdHis", time()) . 'Z';
        $this->zpdf->properties['CreationDate'] = $now;
        $this->zpdf->properties['ModDate'] = $now;
        $this->zpdf->properties['Trapped'] = 'False'; // See this page for information on trapping: http://bit.ly/aKLDYZ

        return $this;
    }

    /**
     * Setzen der Abstände an den Rändern des Dokumentes
     *
     * @param $left
     * @param int $right
     * @param int $top
     * @param int $bottom
     * @return Front_Model_WrapPdf
     */
    public function setMargins($left, $right = 0, $top = 0, $bottom = 0)
    {
        $this->currentMarginLeft = $left;

        if ($right) {
            $this->currentMarginRight = $right;
        }

        if ($top) {
            $this->currentMarginTop = $top;
        }

        if ($bottom) {
            $this->currentMarginBottom = $bottom;
        }

        return $this;
    }

    /**
     * Erstellt eine neue Seite
     *
     * @param string $tocEntry
     * @return Front_Model_WrapPdf
     */
    public function addPage($tocEntry = '')
    {
        $this->currentPage++;
        $this->zpdf->pages[$this->currentPage] = new Zend_Pdf_Page($this->paperSizeDetail);

        if ($tocEntry) {
            $this->tocEntries[] = array( $tocEntry, $this->currentPage );
        }

        $this->cx = $this->currentMarginLeft;
        $this->cy = $this->currentMarginTop;

        // These things need to be reset each time we create a new page.
        $this->currentFont = null;
        $this->currentFontSize = 0;
        $this->currentLineWidth = 0;
        $this->currentLineColour = '';
        $this->currentFillColour = '';

        $this->cy = 70;

        $imagePath = $this->pfad . "/" . $this->logoImage;
        $image = Zend_Pdf_Image::imageWithPath($imagePath);
        $this->zpdf->pages[$this->currentPage]->drawImage($image, 255, 694, 552, 802);

        return $this;
    }

    /**
     * Steuerung von Schriftgroesse , Schriftform und Schriftstärke
     *
     * @param $style
     * @return $this
     */
    public function setStyle($style)
    {
        switch ($style) {
            case 1:
                $this->setFont($this->fontBold, 16, 9, 'blue');
                break;
            case 2:
                $this->setFont($this->fontRegular, 9, 6, 'grey');
                break;
            case 3:
                $this->setFont($this->fontBold, 9, 6, 'grey');
                break;
            case 4:
                $this->setFont($this->fontItalic, 9, 6, 'grey');
                break;
            default:
                $this->setFont($this->fontRegular, 9, 6, 'black');
        }

        return $this;
    }

    /**
     * Setzen Font für momentane Seite des Dokumentes
     * + Font
     * + Größe
     * + Abstand
     * + Farbe
     *
     * @param $font
     * @param $size
     * @param $spacing
     * @param $colour
     */
    public function setFont($font, $size, $spacing, $colour)
    {
        if ($font != $this->currentFont || $size != $this->currentFontSize) {
            $this->zpdf->pages[$this->currentPage]->setFont($font, $size);
            $this->currentFont = $font;
            $this->currentFontSize = $size;
        }

        $this->setFillColour($colour);
        $this->currentFontSpacing = $spacing;
    }

    /**
     * Setzen der Füllfarbe
     *
     * @param $colour
     */
    protected function setFillColour($colour)
    {
        if (!isset($this->colours[$colour])) {
            echo 'Unknown colour requested for fill: ', $colour, "\n";
            exit;
        }

        if ($colour != $this->currentFillColour) {
            $this->zpdf->pages[$this->currentPage]->setFillColor($this->colours[$colour]);
            $this->currentFillColour = $colour;
        }
    }

    /**
     * Setzen der Linienfarbe
     *
     * @param $colour
     */
    protected function setLineColour($colour)
    {
        if (!isset($this->colours[$colour])) {
            echo 'Unknown colour requested for line: ', $colour, "\n";
            exit;
        }

        if ($colour != $this->currentLineColour) {
            $this->zpdf->pages[$this->currentPage]->setLineColor($this->colours[$colour]);
            $this->currentLineColour = $colour;
        }
    }

    /**
     * Setzen der Linienstärke
     *
     * @param $width
     * @return Front_Model_WrapPdf
     */
    public function setLineWidth($width)
    {
        if ($width != $this->currentLineWidth) {
            $this->zpdf->pages[$this->currentPage]->setLineWidth($width);
        }

        return $this;
    }

    /**
     * Zeichnen eines Rechteck
     *
     * @param $x1
     * @param $y1
     * @param $x2
     * @param $y2
     * @param string $outline
     * @param string $fill
     * @return Front_Model_WrapPdf
     */
    public function drawRectangle($x1, $y1, $x2, $y2, $outline = 'black', $fill = 'white')
    {
        $x1 = $this->mmToPoints($x1);
        $x2 = $this->mmToPoints($x2);
        $y1 = $this->mmToPoints($this->paperHeight - $y1);
        $y2 = $this->mmToPoints($this->paperHeight - $y2);

        $this->setLineColour($outline);
        $this->setFillColour($fill);

        $this->zpdf->pages[$this->currentPage]->drawRectangle($x1, $y1, $x2, $y2);

        return $this;
    }

    /**
     * Ausgabe oder Rendern des Pdf Dokumentes
     *
     * @param string $prefix
     */
    public function output($prefix = '')
    {
        $entries = count($this->tocEntries);

        if ($entries) {
            $this->zpdf->outlines[0] = Zend_Pdf_Outline::create('Table of Contents', null);

            for ($c = 0; $c < $entries; $c++) {
                $pageNo = $this->tocEntries[$c][1];
                $destination{$c} = Zend_Pdf_Destination_Fit::create($this->zpdf->pages[$pageNo]);
                $this->zpdf->setNamedDestination('page_' . $pageNo, $destination{$c});
                $this->zpdf->outlines[0]->childOutlines[] = Zend_Pdf_Outline::create(
                    $this->tocEntries[$c][0],
                    $this->zpdf->getNamedDestination('page_' . $pageNo)
                );
            }
        }

        if ($prefix) {
            $registrierungsnummer = $this->ermittelnRegistrierungsnummer();

            $ablagePfad = $this->pfad . "/" . $prefix . "_" . $registrierungsnummer . "_" . $this->zaehler . ".pdf";
            $pdfDateiName = $prefix . "_" . $registrierungsnummer . "_" . $this->zaehler . ".pdf";

            $this->zpdf->save($ablagePfad);

            return $pdfDateiName;
        } else {
            $this->zpdf->render();
        }
    }

    /**
     * Ermitteln Registrierungsnummer mir Session
     *
     * @return int
     */
    private function ermittelnRegistrierungsnummer()
    {
        $toolRegistrierungsnummer = new nook_ToolRegistrierungsnummer();
        $registrierungsnummer = $toolRegistrierungsnummer->steuerungErmittelnRegistrierungsnummerMitSession(
        )->getRegistrierungsnummer();

        return $registrierungsnummer;
    }

    /**
     * Just in case you don't want this class to automatically add page breaks...
     *
     * @param bool $auto
     * @return Front_Model_WrapPdf
     */
    public function setAutoPageBreak($auto = true)
    {
        $this->autoPageBreak = $auto;

        return $this;
    }

    /**
     * Rotieren eines Textes
     * One thing that can make writing rotated text complicated is the fact that when you rotate the page, the entire
     * co-ordinate system rotates around the point that you nominate when calling the rotate() method. The trick, then,
     * is to rotate the page around the actual co-ordinates where you would like the text to start. By definition, that
     * point won't move during the rotation process, as that point is at the centre. So then you can send those
     * co-ordinates to the drawText() method and be sure that your text will actually appear at that point. This saves
     * some otherwise very tricky maths!
     *
     * @param $text
     * @param $sx
     * @param $sy
     * @param $angle
     * @return Front_Model_WrapPdf
     */
    public function writeRotatedText($text, $sx, $sy, $angle)
    {
        $px = $this->mmToPoints($sx);
        $py = $this->mmToPoints($this->paperHeight - $sy);

        $this->zpdf->pages[$this->currentPage]->rotate($px, $py, deg2rad($angle))
            ->drawText($text, $px, $py)
            ->rotate($px, $py, deg2rad(-$angle));

        return $this;
    }

    /**
     * Einfache Methode zum schreiben eines Textblockes mittels eines Array
     * Simple method to write a block of text, wrapping lines according to the current margin settings.
     * Please note that this method starts by splitting up the incoming text into individual lines and then calling the
     * writeText() method to do the actual rendering. The only real thing this adds over calling writeText() directly
     * is that leading spaces on ALL lines will be preserved.
     *
     * @param string $text The line of text to be written.
     */
    public function writeLines($text)
    {
        $lines = explode("\x0a", $text);

        foreach ($lines as $line) {
            $this->writeText($line);
            $this->ln();
        }
    }

    /**
     * Schreiben eines Textblockes
     * Simple method to write a block of text, wrapping lines according to the current margin settings. Note that it
     * does NOT render any carriage returns unless it needs to wrap. Hence, you will need to call the ln() method to
     * add one or more carriage returns after the paragraph has been written.
     * This method was derived from part of the class that 'storeman' contributed to the Zend Framework docs. See the
     * bottom of this page for details: http://framework.zend.com/manual/en/zend.pdf.pages.html
     *
     * @param $text
     * @return Front_Model_WrapPdf
     */
    public function writeText($text)
    {
        $text = 'abcdefghij';
        $text = trim($text);

        $lineText = '';

        $m = strlen($text);

        for ($i = 0; $i < $m; $i++) {
            $lineText .= ' ';
        }

        preg_match_all('/([^\s]*\s*)/i', $text, $matches);
        $words = $matches[1];

        $lineWidth = $this->getStringWidth($lineText);
        $width = $this->paperWidth - $this->currentMarginRight - $this->cx;

        foreach ($words as $word) {
            // If this method has been called by writeLines() then there won't be any carriage returns.
            // However, if the method is being called directly then there may well
            // be some stray carriage returns in there, which we
            // will strip out.
            $word = str_replace("\x0a", ' ', $word);

            $wordWidth = $this->getStringWidth($word);

            if ($lineWidth + $wordWidth < $width) {
                $lineText .= $word;
                $lineWidth += $wordWidth;
            } else {
                // At this point we simply need to render the line and add a carriage return
                $this->zpdf->pages[$this->currentPage]->drawText(
                    $lineText,
                    $this->mmToPoints($this->cx),
                    $this->mmToPoints($this->paperHeight - $this->cy)
                );
                $this->ln();
                $width = $this->paperWidth - $this->currentMarginRight - $this->cx;

                // And now we prime our strings ready for the next iteration through the loop
                $lineText = $word;
                $lineWidth = $wordWidth;
            }
        }

        // At this point we're finishing off the rendering of a line that does NOT need a carriage return
        $this->zpdf->pages[$this->currentPage]->drawText(
            $lineText,
            $this->mmToPoints($this->cx),
            $this->mmToPoints($this->paperHeight - $this->cy)
        );

        $this->cx += $this->getStringWidth($lineText);

        return $this;
    }

    /**
     * Schreibt den Text unter Beachtung des Spaltenmodels des Pdf
     * + At this point we simply need to render the line and add a carriage return
     * + legt den linken Abstand des Textes fest.
     * + wenn kein Abstand vorgegeben, dann 20 mm
     * + legt den Abstand der neuen Zeile fest
     * + legt fest ob im Bedarfsfall eine neue Seite angelegt wird
     *
     * @param $text
     * @param int $zeilenAbstand
     * @param int $spaltenLinie
     * @return $this
     */
    public function schreibeTextzeile($text, $zeilenAbstand = 0, $spaltenLinie = 0, $ln = true, $flagNeueSeite = true)
    {
        // Zeilenabstand
        if (empty($text)) {
            if ($zeilenAbstand == 0) {
                $this->ln(4, $flagNeueSeite);
            }
            else{

                $this->ln($zeilenAbstand, $flagNeueSeite);
            }

            return;
        }

        // Linie der Spalte
        if ($spaltenLinie == 0) {
            $this->cx = 20;
        }
        else {
            $this->cx = $this->tabelleMillimeter[$spaltenLinie];
        }

        if ($zeilenAbstand != 0) {
            $this->currentFontSpacing = $zeilenAbstand;
        }

        // schreiben des Textes
        if (!is_array($text)) {
            $this->zpdf->pages[$this->currentPage]->drawText(
                $text,
                $this->mmToPoints($this->cx),
                $this->mmToPoints($this->paperHeight - $this->cy),
                'UTF-8'
            );
        } else {
            $this->zpdf->pages[$this->currentPage]->drawText(
                $text,
                $this->mmToPoints($this->cx),
                $this->mmToPoints($this->paperHeight - $this->cy),
                'UTF-8'
            );
        }

        if ($ln) {
            $this->ln($zeilenAbstand, $flagNeueSeite);
        }

        return $this;
    }

    private function splitWords($text, $spaltenLinie)
    {
        $words = explode(" ", $text);

        if (empty($words) == 0) {
            return $text;
        }

        return $text;
    }

    /**
     * Ermitteln der Textlänge
     * This is basically a copy of Zend_Barcode_Renderer_Pdf->widthForStringUsingFontSize(), the main differences being
     * that (A) it calculates the width based on the current font settings and (B) it returns the width in mm, not points.
     *
     * @param $string
     * @return float
     */
    protected function getStringWidth($string)
    {
        if (empty($string)) {
            return 0;
        }

        $drawingString = iconv('', 'UTF-16BE', $string);
        $characters = array();

        for ($i = 0; $i < strlen($drawingString); $i++) {
            $characters[] = (ord($drawingString[$i++]) << 8) | ord($drawingString[$i]);
        }

        $font = $this->currentFont;
        $glyphs = $font->glyphNumbersForCharacters($characters);
        $widths = $font->widthsForGlyphs($glyphs);
        $stringWidth = (array_sum($widths) / $font->getUnitsPerEm()) * $this->currentFontSize;

        return $this->pointsToMm($stringWidth);
    }

    /**
     * Bewegt den Cursor nach links und auf die nächste Zeile
     * Move the cursor to the left margin and move down the standard amount or some arbitrary amount
     * + neuer Zeilenumbruch
     * + flagNeueSeite = true , bei Bedarf wird eine neue Seite generiert
     *
     * @param int $h
     * @return Front_Model_WrapPdf
     */
    public function ln($h = 0, $flagNeueSeite = true)
    {
        $this->cx = $this->currentMarginLeft;

        if ($h) {
            $this->cy += $h;
        } else {
            $this->cy += $this->currentFontSpacing;
        }

        if ($flagNeueSeite) {
            if ($this->autoPageBreak && $this->paperHeight - $this->cy < $this->currentMarginBottom) {
                $f = $this->currentFont;
                $s = $this->currentFontSize;
                $p = $this->currentFontSpacing;
                $c = $this->currentFillColour;
                $this->addPage();
                $this->setFont($f, $s, $p, $c);
            }
        }

        return $this;
    }

    /**
     * Fügt einen Link hinzu
     *
     * @param $x1
     * @param $y1
     * @param $x2
     * @param $y2
     * @param $url
     * @return Front_Model_WrapPdf
     */
    public function addLink($x1, $y1, $x2, $y2, $url)
    {
        $x1 = $this->mmToPoints($x1);
        $x2 = $this->mmToPoints($x2);
        $y1 = $this->mmToPoints($this->paperHeight - $y1);
        $y2 = $this->mmToPoints($this->paperHeight - $y2);

        $target = Zend_Pdf_Action_URI :: create($url);
        $annotation = Zend_Pdf_Annotation_Link :: create($x1, $y1, $x2, $y2, $target);
        $this->zpdf->pages[$this->currentPage]->attachAnnotation($annotation);

        return $this;
    }

    /**
     * Zeichnet eine Linie
     *
     * @param $lines
     * @return Front_Model_WrapPdf
     */
    public function drawLines($lines)
    {
        $this->setLineWidth(0.25);
        $this->setLineColour('grey');
        $x1 = $this->currentMarginLeft;
        $x2 = $this->mmToPoints($this->paperWidth - $this->currentMarginRight);

        for ($c = 0; $c < $lines && ($this->cy < ($this->paperHeight - $this->currentMarginBottom - 5)); $c++, $this->cy += 7) {
            $yl = $this->mmToPoints($this->paperHeight - $this->cy);
            $this->zpdf->pages[$this->currentPage]->drawLine($x1, $yl, $x2, $yl);
        }

        return $this;
    }

    /**
     * Zeichnet eine Grafik
     *
     * @param $filename
     * @param int $width
     * @return Front_Model_WrapPdf
     */
    public function drawGraphic($filename, $width = 0)
    {
        if ($width == 0) {
            // This mode renders an image the entire width of the main text area
            $this->cy += $this->image(
                    $filename,
                    $this->currentMarginLeft,
                    $this->cy,
                    $this->paperWidth - $this->currentMarginLeft - $this->currentMarginRight
                ) + 2;
        } else {
            $this->cy += $this->image($filename, $this->currentMarginLeft, $this->cy, $width) + 2;
        }

        return $this;
    }

    /**
     * zeichnet eine grafik
     * This importance of this method is that it retains the aspect ratio when rendering the image.
     *
     * @param $filename
     * @param $x_mm
     * @param $y_mm
     * @param int $w_mm
     * @return float
     */
    protected function image($filename, $x_mm, $y_mm, $w_mm = 0)
    {
        $size = getimagesize($filename);
        $width = $size[0];
        $height = $size[1];

        if ($w_mm == 0) {
            $w_mm = $this->pointsToMm($width);
        }

        $h_mm = $height / $width * $w_mm;

        $x1 = $this->mmToPoints($x_mm);
        $x2 = $this->mmToPoints($x_mm + $w_mm);
        $y1 = $this->mmToPoints($this->paperHeight - $y_mm - $h_mm);
        $y2 = $this->mmToPoints($this->paperHeight - $y_mm);

        if (!isset($this->imageCache[$filename])) {
            $this->imageCache[$filename] = Zend_Pdf_Image::imageWithPath($filename);
        }

        $this->zpdf->pages[$this->currentPage]->drawImage($this->imageCache[$filename], $x1, $y1, $x2, $y2);

        return $h_mm;
    }

    /**
     * Umrechnung von Points in mm
     * Convert from points to inches (there are 72 points to an inch) then from inches to mm (there are 25.4 mm per inch)
     *
     * @param $points
     * @return float
     */
    protected function pointsToMm($points)
    {
        return $points / 72 * 25.4;
    }

    /**
     * Rechnet mm in Points
     * Convert from mm to inches (there are 25.4mm to an inch) then from inches to points (there are 72 points per inch)
     *
     * @param $mm
     * @return float
     */
    public function mmToPoints($mm)
    {
        return $mm / 25.4 * 72;
    }

    /**
     * Gibt die X - Koordinate zurück
     * Some convenience methods - just in case you want to move the cursor to some arbitrary position on the page
     *
     * @return mixed
     */
    public function getX()
    {
        return $this->cx;
    }

    /**
     * Setzen der X - Koordinate / Tiefwert
     *
     * @param $x
     * @return Front_Model_WrapPdf
     */
    public function setX($x)
    {
        $this->cx = $x;

        return $this;
    }

    /**
     * Gibt Y - Koordinate zurück
     *
     * @return mixed
     */
    public function getY()
    {
        return $this->cy;
    }

    /**
     * Setzen der Y - Koordinate
     *
     * @param $y
     * @return Front_Model_WrapPdf
     */
    public function setY($y)
    {
        $this->cy = $y;

        return $this;
    }
}

