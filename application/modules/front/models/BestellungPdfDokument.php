<?php
/**
 * 16.11.12 12:16
 * Generiert das Bestätigungs - Pdf an den Kunden
 *
 *
 * @author Stephan Krauß
 * @package HerdenOnlineBooking
 */

class Front_Model_BestellungPdfDokument extends nook_ToolModel implements arrayaccess{

    // Meta Daten Pdf Dokument
    protected $_metaDataPdfDokument = array(
        "Creator" => "Herden Online Booking",
        "Producer" => "Herden Online Booking"
    );

    // Font der Texte im Pdf
    protected $_fontTexte = array(
        "ueberschrift" => array(
            "font" => Zend_Pdf_Font::FONT_HELVETICA,
            "groesse" => 10
        ),
        "text" => array(
            "font" => Zend_Pdf_Font::FONT_HELVETICA,
            "groesse" => 8
        ),
        "minitext" => array(
            "font" => Zend_Pdf_Font::FONT_HELVETICA,
            "groesse" => 6
        )
    );

    protected $_hochWert = 842; // Höhe einer A4 Seite in Punkte

    public $newPdfDokument =null;
    protected $_aktuellePdfDokumentenTyp = null;
    protected $_rechnungsNummer = null;

    protected $_statischeFirmenTexte = array(); // Kopfzeile , Fusszeile ...

    // $_condition_

    // Fehlerbereich
    private $_errror_dokumenttyp_unbekannt = 990;
    private $_error_pdf_konnte_nicht_angelegt_werden = 991;

    // Tabelle / Views

    public function __construct(){

    }

    /**
     * Übernimmt die Daten eines Fremd - Model
     *
     * @param $__modelName
     * @param $__model
     */
    public function setModelDataKundenUndBuchungsdaten($__fremdModel, $__modelName = false){

        $this->_importModelData($__fremdModel, $__modelName);

        return $this;
    }

    /**
     * Erstellt ein Pdf entsprechend
     * dem aktuellen Pdf Typ
     *
     * @return Front_Model_BestellungPdf
     */
    public function buildPdf(){

        $this
            ->_buildNewPdfDokument() // leeres Pdf Dokument
            ->_addErsteSeite(); // erste Seite mit Logo, Anschrift und Fusszeile

        return $this;
    }




    /**
     * Erstellt ein leeres Dokument
     * Fügt Metadaten hinzu
     *
     * @return Front_Model_BestellungPdf
     */
    private function _buildNewPdfDokument(){

        /** @var newPdfDokument Zend_Pdf */
        $this->newPdfDokument = new Zend_Pdf(); // neues Pdf Dokument

        $date = new Zend_Date(); // aktuelle Datum

        // flexible Metadaten Pdf Dokument
        $this->_metaDataPdfDokument["Title"] = ucwords($this->_aktuellePdfDokumentenTyp);
        $this->_metaDataPdfDokument["Subject"] = ucwords($this->_aktuellePdfDokumentenTyp);
        $this->_metaDataPdfDokument["CreationDate"] = $date->toString('dd.MM.YYYY HH:mm:ss');

        foreach($this->_metaDataPdfDokument as $key => $value){
            $this->newPdfDokument->properties[$key] = $value;
        }

        return $this;
    }

    /**
     * Erstellt die erste Seite des Pdf - Dokument mit Logo, Anschrift
     * und Fusszeile
     *
     * @return Front_Model_BestellungPdf
     */
    private function _addErsteSeite(){

        // Start des Seitenaufbau
        $this->_hochWert = 750;
        $ersteSeitePdf = new Zend_Pdf_Page(Zend_Pdf_Page::SIZE_A4);

        /** Kopfzeile **/
        // Font der Kopfzeile
        $ersteSeitePdf->setFont(Zend_Pdf_Font::fontWithName($this->_fontTexte['text']['font']), $this->_fontTexte['text']['groesse']);

        // Linienbreite
        $ersteSeitePdf->setLineWidth(0.25);

        // Briefkopf
        $ersteSeitePdf->drawText($this->_modelData['_statischeFirmenTexte']['brief_kopf'], 20, $this->_hochWert, 'UTF-8');
        $this->_hochWert -= 5;

        // Linie
        $ersteSeitePdf->drawLine(20,$this->_hochWert,255,$this->_hochWert);
        $this->_hochWert -= 10;

        // Logo
        $logo = Zend_Pdf_Image::imageWithPath("img/logo/HOB_Logo.png");
        $ersteSeitePdf->drawImage($logo,300,$this->_hochWert,500,$this->_hochWert + 71);

        /*** Fusszeile ***/
        $this->_hochWert = 150;
        $ersteSeitePdf->setFont(Zend_Pdf_Font::fontWithName($this->_fontTexte['text']['font']), $this->_fontTexte['text']['groesse']);

        // Font der Fuss Zeile
        $ersteSeitePdf->setFont(Zend_Pdf_Font::fontWithName($this->_fontTexte['minitext']['font']), $this->_fontTexte['minitext']['groesse']);

        // Linie
        $ersteSeitePdf->drawLine(68,$this->_hochWert,526,$this->_hochWert);

        // Fusszeile
        $textFussZeile = explode("\n", $this->_modelData['_statischeFirmenTexte']['brief_fusszeile']);

        foreach($textFussZeile as $row){
            $this->_hochWert -= 8;

            $ersteSeitePdf->drawText($row, 68, $this->_hochWert, 'UTF-8');
        }

        // erste Seite im Dokument speichern
        $this->newPdfDokument->pages[] = $ersteSeitePdf;

        return $this;
    }

    /**
     * Erstellt das Pdf Grunddokument
     * Kontrolle der Pdf Generierung
     *
     */
    public function buildDokumentPdf($__testMarke = false){

        $pdfPfad = realpath(dirname(__FILE__).'/../../../../pdf/');

        // Dateiname
        if(empty($__testMarke))
            $pdfDatei = $pdfPfad."/".$this->_modelData['_datenRechnungen']['rechnungsnummer'].".pdf";
        else
            $pdfDatei = $pdfPfad."/".$this->_modelData['_datenRechnungen']['rechnungsnummer'].$__testMarke.".pdf";

        // speichern Pdf Dokument
        $this->newPdfDokument->save($pdfDatei);

        return;
    }

    /**
     * Gibt das Pdf Grunddokument zurück
     *
     * @return mixed
     */
    public function getPdfDokument(){
        $newPdfGrundDokument = $this->newPdfDokument;

        return $newPdfGrundDokument;
    }

} // end class
