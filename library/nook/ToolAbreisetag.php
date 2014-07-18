<?php
/**
 * Errechnet den Abreisetag
 *
 * Ermittelt den Abreisetag an Hand des Anreisetages
 * und der Anzahl der Übernachtungen.
 * 
 * @author Stephan Krauss
 * @date 25.02.14
 * @package tools
 */
class nook_ToolAbreisetag
{
    // Fehler
    private $_error_anfangswerte_unvollstaendig = 1580;

    protected $_anreiseDatum = null;
    protected $_anzahlUebernachtungen = null;
    protected $_abreiseDatum = ' ';

    /**
     * @param $anreiseDatum
     * @return nook_ToolAbreisetag
     */
    public function setAnreiseDatum ($anreiseDatum)
    {
        $this->_anreiseDatum = $anreiseDatum;

        return $this;
    }

    /**
     * @param $anzahlUebernachtungen
     * @return nook_ToolAbreisetag
     */
    public function setAnzahlUebernachtungen ($anzahlUebernachtungen)
    {
        $anzahlUebernachtungen = (int) $anzahlUebernachtungen;
        $this->_anzahlUebernachtungen = $anzahlUebernachtungen;

        return $this;
    }

    /**
     * @return string
     */
    public function getAbreiseDatum ()
    {
        return $this->_abreiseDatum;
    }

    /**
     * Berechnung des Abreisetages
     *
     * @return $this
     * @throws nook_Exception
     */
    public function berechneAbreisetag()
    {
        try{
            if(empty($this->_anreiseDatum))
                throw new nook_Exception($this->_error_anfangswerte_unvollstaendig);

            if(empty($this->_anzahlUebernachtungen)) {
                throw new nook_Exception($this->_error_anfangswerte_unvollstaendig);
            }
        }
        catch(Exception $e){
            nook_ExceptionRegistration::registerException($e, 3);
        }

        $this->_berechneAbreisetag();

        return $this;
    }

    /**
     * Ermittelt das Abreisedatum
     */
    private function _berechneAbreisetag()
    {
        $this->_transformDatum();
        $this->_abreiseDatum = nook_ToolDatum::berechneEndeZeitraum($this->_anreiseDatum, $this->_anzahlUebernachtungen);

        return;
    }

    private function _transformDatum(){

        if(strstr($this->_anreiseDatum, '.'))
            $this->_anreiseDatum = nook_ToolDatum::wandleDatumDeutschInEnglisch($this->_anreiseDatum);

        return;
    }




} // end class
