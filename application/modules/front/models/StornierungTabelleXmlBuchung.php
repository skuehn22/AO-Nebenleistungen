<?php 
/**
* Korrigiert die Stornierung in der Tabelle 'tbl_xml_buchung'
*
* + Steuert das löschen der Buchung in 'tbl_xml_buchung'
* + löscht die Buchung in 'tbl_xml_buchung'
*
* @date 01.10.2013
* @file StornierungTabelleXmlBuchung.php
* @package front
* @subpackage model
*/
class Front_Model_StornierungTabelleXmlBuchung extends Front_Model_Stornierung implements Front_Model_StornierungWarenkorbInterface
{
    // Fehler
    private $error_anfangswerte_fehlen = 2250;
    private $error_loeschen_fehlgeschlagen = 2251;

    // Konditionen
    private $condition_aktueller_warenkorb = 0;

    // Flags
    protected $flagStatusWork = true;


    // Informationen

    protected $flagBestandsbuchung = false;
    protected $pimple = null;
    protected $artikelWarenkorb = array();

    /**
     * Steuert das löschen der Buchung in 'tbl_xml_buchung'
     *
     * @return Front_Model_StornierungTabelleXmlBuchung
     * @throws nook_Exception
     */
    public function work()
    {
        if(empty($this->buchungsnummer))
            throw new nook_Exception($this->error_anfangswerte_fehlen);

        if(is_null($this->zaehler))
            throw new nook_Exception($this->error_anfangswerte_fehlen);

        if(!is_array($this->artikelWarenkorb) or count($this->artikelWarenkorb) == 0){
            nook_ExceptionInformationRegistration::registerError('keine Artikel im Warenkorb');

            return $this;
        }

        foreach($this->artikelWarenkorb as $key => $artikel){
            if($artikel['status'] > 1)
                $this->loeschenXmlBuchung($artikel);
        }


        return $this;
    }

    /**
     * löscht die Buchung in 'tbl_xml_buchung'
     *
     * @param $artikel
     * @throws nook_Exception
     */
    private function loeschenXmlBuchung($artikel)
    {
        $kontrolle = 0;

        $deleteWhere = array(
            "buchungsnummer_id = ".$this->buchungsnummer,
            "zaehler = ".$this->condition_aktueller_warenkorb,
            "buchungstabelle_id = ".$artikel['id'],
            "bereich = ".$artikel['bereich']
        );

        /** @var $tabelleXmlBuchung Application_Model_DbTable_xmlBuchung */
        $tabelleXmlBuchung = $this->pimple['tabelleXmlBuchung'];

        $kontrolle = $tabelleXmlBuchung->delete($deleteWhere);

        return $kontrolle;
    }
}
