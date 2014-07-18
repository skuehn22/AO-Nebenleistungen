<?php
/**
* Öffnungszeiten eines Programmes
*
* + Übernimmt den Servicecontainer
* + Überprüft den Inhalt des Servicecontainer
* + Steuerung der Ermittlung der Öffnungszeiten an den Wochentagen eines Programmes
* + Ermitteln der Bezeichnung der Wochentage
* + Ermittelt die Öffnungszeiten eines Programmes
* + Darstellen der ganztägigen Öffnungszeit
* + Korrigiert den Wert für Mitternacht von 23:59 auf 24:00 Uhr
* + Gibt die Öffnungszeiten eines Programmes zurück
* + Ermittelt den Anzeigetyp der Öffnungszeiten
* + Steuert die Ermittlung Öffnungszeiten
* + Gibt die Geschaeftstage des Programmes in einer Woche als String zurück
* + Steuerung der Kontrolle ob eine Zeit entsprechend der Öffnungszeiten eines Programmes zulässig ist
* + Ermittelt ob die Zeit eines programmes mit den Öffnungszeiten vereinbar ist
* + Gibt die Information zurück wie eine zeit mit den Öffnungszeiten vereinbar ist
*
* @date 20.08.13
* @file OeffnungszeitenProgramm.php
* @package front
* @subpackage model
*/
class Front_Model_OeffnungszeitenProgramm
{
    // Tabellen / Views

    private $tabelleProgrammdtails = null;

    // Konditionen
    private $geschaeftstag = 2;
    private $condition_anzeige_in_listenform = 2;
    private $condition_eingabe_durch_benutzer = 1;

    // Fehler
    private $error_anfangswerte_fehlen = 1950;

    // Flags
    protected $flag_kontrolle_oeffnungszeit = false;

    protected $pimple = null;
    protected $programmId = null;
    protected $varianteOeffnungszeitProgramm = null;
    protected $oeffnungszeitenProgramm = array();
    protected $oeffnungszeitenProgrammWochentage = null;
    protected $anzeigespracheId = null;
    protected $datum = null;
    protected $zeit = null;
    protected $typAnzeigeOeffnungszeiten = null;

    /**
     * Übernimmt den Servicecontainer
     *
     * @param $pimple
     */
    public function __construct($pimple = false)
    {
        if (!empty($pimple)) {
            if ($pimple instanceof Pimple_Pimple) {
                $this->pimple = $pimple;
            } else {
                throw new nook_Exception($this->error_anfangswerte_fehlen);
            }
        } else {
            $this->pimple = new Pimple_Pimple();
        }

        $this->checkPimple();
    }

    /**
     * Überprüft den Inhalt des Servicecontainer
     */
    private function checkPimple()
    {
        if (!$this->pimple->offsetExists('tabelleProgrammdtails')) {
            $this->pimple['tabelleProgrammdtails'] = function () {
                return new Application_Model_DbTable_programmedetails();
            };
        }

        if (!$this->pimple->offsetExists('tabelleProgrammdtailsZeiten')) {
            $this->pimple['tabelleProgrammdtailsZeiten'] = function () {
                return new Application_Model_DbTable_programmedetailsZeiten();
            };
        }

        if (!$this->pimple->offsetExists('tabelleProgrammdtailsOeffnungszeiten')) {
            $this->pimple['tabelleProgrammdtailsOeffnungszeiten'] = function () {
                return new Application_Model_DbTable_programmedetailsOeffnungszeiten();
            };
        }

        if (!$this->pimple->offsetExists('nookToolWochentageNamen')) {
            $this->pimple['nookToolWochentageNamen'] = function () {
                return new nook_ToolWochentageNamen();
            };
        }

        return;
    }

    /**
     * @param $programmId
     * @return Front_Model_OeffnungszeitenProgramm
     */
    public function setProgrammId($programmId)
    {
        $programmId = (int) $programmId;
        $this->programmId = $programmId;

        return $this;
    }

    /**
     * @param $anzeigespracheId
     * @return Front_Model_OeffnungszeitenProgramm
     */
    public function setAnzeigespracheId($anzeigespracheId)
    {
        $anzeigespracheId = (int) $anzeigespracheId;
        $this->anzeigespracheId = $anzeigespracheId;

        return $this;
    }

    /**
     * @param $datum
     * @return Front_Model_OeffnungszeitenProgramm
     */
    public function setDatum($datum)
    {
        $this->datum = $datum;

        return $this;
    }

    /**
     * @param $zeit
     * @return Front_Model_OeffnungszeitenProgramm
     */
    public function setZeit($zeit)
    {
        $this->zeit = $zeit;

        return $this;
    }

    /**
     * Steuerung der Ermittlung der Öffnungszeiten an den Wochentagen eines Programmes
     * + es wird der Name des Wochentages angezeigt
     * + Standard ist deutsche Sprache
     * + Standard ist die Kurzform des Wochentages
     *
     * @param int $anzeigespracheId
     * @return Front_Model_OeffnungszeitenProgramm
     * @throws nook_Exception
     */
    public function steuerungErmittlungOeffnungszeitenProgramm($anzeigespracheId = 1)
    {
        if (empty($this->anzeigespracheId)) {
            $this->anzeigespracheId = $anzeigespracheId;
        }

        if (empty($this->programmId)) {
            throw new nook_Exception($this->error_anfangswerte_fehlen);
        }

        $oeffnungszeiten = $this->ermittlungOeffnungszeitenProgramm();
        $oeffnungszeiten = $this->ermittleBezeichnungWochentag($oeffnungszeiten);

        $this->oeffnungszeitenProgramm = $oeffnungszeiten;

        return $this;
    }

    /**
     * Ermitteln der Bezeichnung der Wochentage
     *
     * @param $oeffnungszeiten
     * @return mixed
     */
    private function ermittleBezeichnungWochentag($oeffnungszeiten)
    {
        /** @var $toolWochentageNamen nook_ToolWochentageNamen */
        $toolWochentageNamen = $this->pimple['nookToolWochentageNamen'];
        $namenDerWochentage = $toolWochentageNamen
            ->setAnzeigeNamensTyp(1)
            ->setAnzeigespracheId($this->anzeigespracheId)
            ->steuerungErmittlungNamenDerWochentage()
            ->getNamenDerWochentage();

        foreach ($oeffnungszeiten as $key => $value) {
            $oeffnungszeiten[$key]['name'] = $namenDerWochentage[$value['wochentag']];
        }


        return $oeffnungszeiten;
    }

    /**
     * Ermittelt die Öffnungszeiten eines Programmes
     *
     * + Öffnungszeiten werden nach der Ziffer des Wochentages sortiert
     * + Öffnungszeiten mit einem Startdatum 'von' = '00:00:00' bis '00:00:00' werden entsprechend der Eingabe der Zeit berücksichtigt
     */
    private function ermittlungOeffnungszeitenProgramm()
    {
        $this->ermittlungTypAnzeigeOeffnungszeit();

        $cols = array(
            new Zend_Db_Expr("SUBSTR(von,1,5) as von"),
            new Zend_Db_Expr("SUBSTR(bis,1,5) as bis"),
            'wochentag'
        );

        $whereProgrammId = "programmdetails_id = " . $this->programmId;

        /** @var  $tabelleProgrammdtailsOeffnungszeiten Application_Model_DbTable_programmedetailsOeffnungszeiten */
        $tabelleProgrammdtailsOeffnungszeiten = $this->pimple['tabelleProgrammdtailsOeffnungszeiten'];
        $select = $tabelleProgrammdtailsOeffnungszeiten->select();
        $select
            ->from($tabelleProgrammdtailsOeffnungszeiten, $cols)
            ->where($whereProgrammId)
            ->order('wochentag');

        if($this->typAnzeigeOeffnungszeiten == $this->condition_eingabe_durch_benutzer)
            $select->where("bis <> '00:00:00'");

        $rows = $tabelleProgrammdtailsOeffnungszeiten->fetchAll($select)->toArray();

        return $rows;
    }

    /**
     * Darstellen der ganztägigen Öffnungszeit
     *
     * + Öffnungszeit ganztägig: von 00:00 bis 23:59 Uhr
     * + ganztägige Öffnungszeit: von 00:00 bis 24:00 Uhr
     *
     * @return Front_Model_OeffnungszeitenProgramm
     */
    public function darstellenGanztaegigeOeffnung()
    {
        $oeffnungszeitenProgramm = array();
        $i = 0;
        foreach($this->oeffnungszeitenProgramm as $key => $value){
            // ganztägige Öffnungszeit
            if($value['von'] == '00:00' and $value['bis'] == '23:59'){
                continue;
            }
            // ganztägige Öffnungszeit
            elseif($value['von'] == '00:00' and $value['bis'] == '24:00'){
                continue;
            }
            // normale Öffnungszeit
            else{
                $oeffnungszeitenProgramm[$i] = $value;

                $i++;
            }
        }

        // Übergabe Öffnungszeiten
        if(count($oeffnungszeitenProgramm) > 0)
            $this->oeffnungszeitenProgramm = $oeffnungszeitenProgramm;
        else
            $this->oeffnungszeitenProgramm = false;

        return $this;
    }

    /**
     * Korrigiert den Wert für Mitternacht von 23:59 auf 24:00 Uhr
     *
     * @return $this
     */
    public function korrekturMitternacht()
    {
        for($i=0; $i < count($this->oeffnungszeitenProgramm); $i++){
            if($this->oeffnungszeitenProgramm[$i]['bis'] == '23:59')
                $this->oeffnungszeitenProgramm[$i]['bis'] = '24:00';
        }

        return $this;
    }

    /**
     * Gibt die Öffnungszeiten eines Programmes zurück
     *
     * @return array
     */
    public function getOeffnungszeiten()
    {
        return $this->oeffnungszeitenProgramm;
    }

    /**
     * Ermittelt den Anzeigetyp der Öffnungszeiten
     *
     */
    private function ermittlungTypAnzeigeOeffnungszeit()
    {
        $cols = array(
            new Zend_Db_Expr("count(id) as anzahl")
        );

        $whereProgrammId = "programmdetails_id = ".$this->programmId;
        $whereStatus = "status = ".$this->condition_anzeige_in_listenform;

        /** @var $tabelleProgrammdetailsZeiten Application_Model_DbTable_programmedetailsZeiten */
        $tabelleProgrammdetailsZeiten = $this->pimple['tabelleProgrammdtailsZeiten'];
        $select = $tabelleProgrammdetailsZeiten->select();
        $select
            ->from($tabelleProgrammdetailsZeiten, $cols)
            ->where($whereProgrammId)
            ->where($whereStatus);

        $rows = $tabelleProgrammdetailsZeiten->fetchAll($select)->toArray();

        if($rows[0]['anzahl'] > 0)
            $this->typAnzeigeOeffnungszeiten = $this->condition_anzeige_in_listenform;
        else
            $this->typAnzeigeOeffnungszeiten = $this->condition_eingabe_durch_benutzer;

        return $this->typAnzeigeOeffnungszeiten;
    }

    /**
     * Steuert die Ermittlung Öffnungszeiten
     *
     * @return $this
     */
    public function steuerungErmittlungGeschaeftstageProgramm()
    {
        $this->ermittlungTypAnzeigeOeffnungszeit();

        $this->steuerungErmittlungOeffnungszeitenProgramm();
        $this->ermittlungGeschaeftstageProgramm();

        return $this;
    }

    private function ermittlungGeschaeftstageProgramm()
    {
        $oeffnungszeitenProgrammWochentage = '';
        if (!empty($this->oeffnungszeitenProgramm)) {
            foreach ($this->oeffnungszeitenProgramm as $key => $value) {
                $oeffnungszeitenProgrammWochentage .= "'" . $value['wochentag'] . "',";
            }

            $this->oeffnungszeitenProgrammWochentage = substr($oeffnungszeitenProgrammWochentage, 0, -1);
        }

        return;
    }

    /**
     * Gibt die Geschaeftstage des Programmes in einer Woche als String zurück
     *
     * @return string
     */
    public function getGeschaeftstage()
    {
        return $this->oeffnungszeitenProgrammWochentage;
    }

    /**
     * Steuerung der Kontrolle ob eine Zeit entsprechend der Öffnungszeiten eines Programmes zulässig ist
     *
     * @return $this
     * @throws nook_Exception
     */
    public function steuerungUeberpruefungOeffnungszeit()
    {
        if (empty($this->programmId) or empty($this->datum) or empty($this->zeit)) {
            throw new nook_Exception($this->error_anfangswerte_fehlen);
        }

        $this->ueberpruefungOeffnungszeit();

        return $this;
    }

    /**
     * Ermittelt ob die Zeit eines programmes mit den Öffnungszeiten vereinbar ist
     * + Ermittlung Wochentag als Ziffer
     * + Abfrage ob eine Zeit bezüglich der Öffnungszeit eines Wochentages zulässig ist
     * + Wochentag -> Sonntag = 0, Korrektur auf 7

     */
    private function ueberpruefungOeffnungszeit()
    {
        $this->flag_kontrolle_oeffnungszeit = false;

        $zeit = strtotime($this->datum);
        $wochentag = date("w", $zeit);

        // Korrektur für Soontag
        if($wochentag == 0)
            $wochentag = 7;

        $whereWochentag = "wochentag = " . $wochentag;
        $whereProgrammId = "programmdetails_id = " . $this->programmId;
        $whereFrom = "von <= '" . $this->zeit . "'";
        $whereBis = "bis >= '" . $this->zeit . "'";

        $cols = array(
            new Zend_Db_Expr("count(id) as anzahl")
        );

        /** @var $tabelleProgrammdtailsOeffnungszeiten Application_Model_DbTable_programmedetailsOeffnungszeiten */
        $tabelleProgrammdtailsOeffnungszeiten = $this->pimple['tabelleProgrammdtailsOeffnungszeiten'];
        $select = $tabelleProgrammdtailsOeffnungszeiten->select();
        $select
            ->from($tabelleProgrammdtailsOeffnungszeiten, $cols)
            ->where($whereProgrammId)
            ->where($whereWochentag)
            ->where($whereFrom)
            ->where($whereBis);

        $rows = $tabelleProgrammdtailsOeffnungszeiten->fetchAll($select)->toArray();

        if ($rows[0]['anzahl'] == 1) {
            $this->flag_kontrolle_oeffnungszeit = true;
        }

        return;
    }

    /**
     * Gibt die Information zurück wie eine zeit mit den Öffnungszeiten vereinbar ist
     * + true = liegt innerhalb der Öffnungszeit
     * + false = liegt außerhalb der Öffnungszeit
     *
     * @return bool
     */
    public function getKontrolleOeffnungszeit()
    {
        return $this->flag_kontrolle_oeffnungszeit;
    }

}
