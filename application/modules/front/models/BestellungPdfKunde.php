<?php
/**
 * Erstellt das Kunden Pdf
 *
 *
 */
class Front_Model_BestellungPdfKunde extends nook_ToolModel implements arrayaccess{

    protected $_hochWert = 700; // Hochwert Pdf Dokument
    protected $_rechtsWert = 20; // Abstand vom linken Rand

    protected $_newPdfDokumentKunde = null;
    protected $_seite = null;
    protected $_seitePdf = null;

    protected $_fontTexte = array();

    /** @var $_modelTexteBuchungsDatenSaetze Front_Model_BestellungTexteBuchungsdatensaetze */
    protected $_modelTexteBuchungsDatenSaetze = null;


    // Fehler
    // private $_error_kein_int = 1020;

    // Tabellen / Views

    // Konditionen
    private $_condition_deutsche_sprache = 1;

    public function __construct(){

    }

    public function erstellenDokument(){
        $this
            ->_setSchriften() // Schritgröße, Schriftform
            ->_adressdatenKunde() // fügt Adressdaten Kunde ein
            ->_setzenStartwerteSeite() // Hochwert, Seitennummer

            ->_einfuegenHotelbuchungen() // Hotelbuchungen
            ->_einfuegenZusatzprodukte(); // Zusatzprodukte der Hotels

        return $this;
    }

    /**
     * Übernimmt Model Texte
     * der Buchungsdatensätze
     *
     * @param $__modelTexteBuchungsDatenSaetze
     * @return Front_Model_BestellungPdfKunde
     */
    public function setTexteBuchungsdatensaetze($__modelTexteBuchungsDatenSaetze){
        $this->_modelTexteBuchungsDatenSaetze = $__modelTexteBuchungsDatenSaetze;

        return $this;
    }

    /**
     * Setzt die Startwerte
     * Seite = 0
     * Hochwert der ersten Seite
     *
     * @return Front_Model_BestellungPdfKunde
     */
    private function _setzenStartwerteSeite(){
        $this->_seite = 0;
        $this->_hochWert = 650;

        return $this;
    }

    /**
     * Übernimmt Schriftgrösse
     * Schriftart
     *
     * @return Front_Model_BestellungPdfKunde
     */
    private function _setSchriften(){
        $this->_fontTexte = $this->offsetGet("_fontTexte");

        return $this;
    }

    /**
     * Fügt Hotelbuchungen ein
     *
     */
    private function _einfuegenHotelbuchungen(){

        // keine Hotelbuchungen vorhanden
        if(count($this->_modelData['_datenHotelbuchungen']) == 0)
            return $this;

        /** @var $pdfGrunddokument Zend_Pdf */
        $pdfGrunddokument = $this->_newPdfDokumentKunde;

        /** @var $seitePdf Zend_Pdf_Page */
        $this->_seitePdf = $pdfGrunddokument->pages[$this->_seite];

        // Font
        $this->_seitePdf->setFont(Zend_Pdf_Font::fontWithName($this->_fontTexte['text']['font']), $this->_fontTexte['text']['groesse']);

        // Abschnitt Hotelbuchung
        $this->_hochWert -= 10;
        $this->_seitePdf->drawText($this->_modelData['_statischeFirmenTexte']['ueberschrift_uebernachtungen'].":", $this->_rechtsWert, $this->_hochWert, 'UTF-8');
        $this->_hochWert -= 10;

        for($i=0; $i<count($this->_modelData['_datenHotelbuchungen']); $i++){

            $textHotelbuchung = $this->_modelTexteBuchungsDatenSaetze->texteHotelbuchungen($this->_modelData['_datenHotelbuchungen'][$i]);

            foreach($textHotelbuchung as $key => $value){
                $this->_hochWert -= 10;

                // Leerzeile
                if($value == "##"){
                    $this->_hochWert -= 10;
                    continue;
                }

                // neue Seite im Dokument
                if($this->_hochWert <= 150){
                    $this->_neueSeitePdfDokument(); // neue Pdf Seite im Dokument
                }

                $this->_seitePdf->drawText($value, $this->_rechtsWert, $this->_hochWert, 'UTF-8');
            }
            $this->_hochWert -= 10;
        }

        return $this;
    }

    private function _einfuegenZusatzprodukte(){

       // keine Zusatzprodukte vorhanden
       if(count($this->_modelData['_datenProduktbuchungen']) == 0)
           return $this;

       /** @var $pdfGrunddokument Zend_Pdf */
       $pdfGrunddokument = $this->_newPdfDokumentKunde;

       /** @var $seitePdf Zend_Pdf_Page */
       $this->_seitePdf = $pdfGrunddokument->pages[$this->_seite];

       // Font
       $this->_seitePdf->setFont(Zend_Pdf_Font::fontWithName($this->_fontTexte['text']['font']), $this->_fontTexte['text']['groesse']);

       // Abschnitt Produkte
        $this->_hochWert -= 10;
       $this->_seitePdf->drawText($this->_modelData['_statischeFirmenTexte']['ueberschrift_zusatzprodukte'].":", $this->_rechtsWert, $this->_hochWert, 'UTF-8');
        $this->_hochWert -= 10;

       for($i=0; $i<count($this->_modelData['_datenProduktbuchungen']); $i++){

           $textProduktbuchung = $this->_modelTexteBuchungsDatenSaetze->texteProduktbuchungen($this->_modelData['_datenProduktbuchungen'][$i]);

           foreach($textProduktbuchung as $key => $value){
               $this->_hochWert -= 10;

               // Leerzeile
               if($value == "##"){
                   $this->_hochWert -= 10;
                   continue;
               }

               // neue Seite im Dokument
               if($this->_hochWert <= 150){
                   $this->_neueSeitePdfDokument(); // neue Pdf Seite im Dokument
               }

               $this->_seitePdf->drawText($value, $this->_rechtsWert, $this->_hochWert, 'UTF-8');
           }
           $this->_hochWert -= 10;
       }

       return $this;
   }



    /**
     * Übernimmt Kundendaten
     * und Buchungsdaten
     *
     * @param $__fremdModel
     * @return Front_Model_BestellungPdfKunde
     */
    public function setKundenUndBuchungsdaten($__fremdModel){
        $this->_importModelData($__fremdModel);

        return $this;
    }

    /**
     * Legt eine neue Seite im Pdf Dokument an
     *
     * @return mixed
     */
    private function _neueSeitePdfDokument(){

        $this->_hochWert = 800;
        $this->_seite ++;

        // legt neue Seite an
        $this->_seitePdf = $this->_newPdfDokumentKunde->newPage(Zend_Pdf_Page::SIZE_A4);
        $this->_seitePdf->setFont(Zend_Pdf_Font::fontWithName($this->_fontTexte['text']['font']), $this->_fontTexte['text']['groesse']);
        $this->_newPdfDokumentKunde->pages[] = $this->_seitePdf;

        return $this;
    }

    /**
     * Erstellt das Pdf Grunddokument
     * Kontrolle der Pdf Generierung
     *
     */
    public function buildPdfUebernachtungen(){

        // Dateiname
        $pdfFileName = realpath(dirname(__FILE__) . '/../../../../pdf/');
        $pdfFileName .= '/'.$this->_modelData['_datenRechnungen']['rechnungsnummer']."_kunde.pdf";

        // speichern Pdf Dokument
        $this->_newPdfDokumentKunde->save($pdfFileName);

        return;
    }

    /**
     * Übernimmt ein Clone des
     * Grund Pdf-Dokument
     *
     * @return Front_Model_BestellungPdfKunde
     */
    public function setPdfDokument($__model_pdfDokument){

        $newPdfDokumentKunde = clone $__model_pdfDokument->newPdfDokument;
        $this->_newPdfDokumentKunde = $newPdfDokumentKunde;

        return $this;
    }

    /**
    * Kundendaten werden in die
    * erste Seite des Pdf Dokumentes eingefügt.
    *
    * @return
    */
    private function _adressdatenKunde(){

        /** @var $pdfGrunddokument Zend_Pdf */
        $pdfGrunddokument = $this->_newPdfDokumentKunde;
        $__ersteSeitePdf = $pdfGrunddokument->pages[0];

        $__ersteSeitePdf->setFont(Zend_Pdf_Font::fontWithName($this->_fontTexte['text']['font']), $this->_fontTexte['text']['groesse']);

        $this->_hochWert = 720;

        $kundenDaten = $this->offsetGet('_kundenDaten');
        $__ersteSeitePdf->drawText($kundenDaten['title']." ".$kundenDaten['firstname']." ".$kundenDaten['lastname'], 20, $this->_hochWert, 'UTF-8');
        $this->_hochWert -= 10;

        $__ersteSeitePdf->drawText($kundenDaten['street']." ".$kundenDaten['housenumber'], 20, $this->_hochWert, 'UTF-8');
        $this->_hochWert -= 10;

        $__ersteSeitePdf->drawText($kundenDaten['zip']." ".$kundenDaten['city'], 20, $this->_hochWert, 'UTF-8');
        $this->_hochWert -= 20;

        $__ersteSeitePdf->setFont(Zend_Pdf_Font::fontWithName($this->_fontTexte['ueberschrift']['font']), $this->_fontTexte['ueberschrift']['groesse']);

        // Rechnungsnummer
        $rechnungsdaten = $this->offsetGet('_datenRechnungen');
        $__ersteSeitePdf->drawText($this->_modelData['_statischeFirmenTexte']['ueberschrift_rechnungsnummer'].":".$rechnungsdaten['rechnungsnummer'], 20, $this->_hochWert, 'UTF-8');
        $this->_hochWert -= 20;

        // Überschrift Buchungsbestätigung
        $__ersteSeitePdf->drawText($this->_modelData['_statischeFirmenTexte']['ueberschrift_buchungsbestaetigung'].":", 20, $this->_hochWert, 'UTF-8');
        $this->_hochWert -= 20;

        return $this;
    }


} // end class