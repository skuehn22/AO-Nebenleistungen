<?php
/**
 * Klasse zur Bearbeitung der Programmvarianten eines Programmes
 *
 *
 *
 * <code>
 *  Codebeispiel
 * </code>
 *
 * @author Stephan Krauß
 */

class Front_Model_ProgrammdetailProgrammvarianten
{

    private $_tabellePreisvarianten = null;
    private $_viewPreisvariantenSprachen = null;

    private $_programId = null;
    private $_anzeigeSprache = null;
    private $_selectBox = null;
    private $_preiseProgrammVarianten = null;
    private $_preisErsteProgrammvariante = null;
    private $_anzahlPreisvarianten = null;

    private $_condition_moegliche_anzahl_programmvarianten = 15;

    private $_error_daten_nicht_integer = 830;
    private $_error_wert_nicht_vorhanden = 831;

    private $bestandsbuchungPreisvarianteId = null;

    public function __construct ()
    {

        /** @var $_tabellePreisvarianten Application_Model_DbTable_preise */
        $this->_tabellePreisvarianten = new Application_Model_DbTable_preise(array( 'db' => 'front' ));
        /** @var _viewPreisvariantenSprachen Application_Model_DbTable_viewPreisvarianten */
        $this->_viewPreisvariantenSprachen = new Application_Model_DbTable_viewPreisvarianten();

        // Anzeigesprache
        $this->_anzeigeSprache = Zend_Registry::get('language');
    }

    /**
     * Übernimmt die ID der Preisvariante eines bereits gebuchten Programmes
     *
     * @param $bestandsbuchungPreisvarianteId
     * @return Front_Model_ProgrammdetailProgrammvarianten
     */
    public function setBestandsbuchungProgrammId($bestandsbuchungPreisvarianteId)
    {
        $this->bestandsbuchungPreisvarianteId = $bestandsbuchungPreisvarianteId;

        return $this;
    }



    /************ Anzeige der vorhandenen Programmvarianten ****************/

    /**
     * Ermittelt die vorhandenen Preisvarianten eines Programmes
     *
     * @param $programmId
     * @return
     */
    public function getPreisvariantenEinesProgrammes ($programmId)
    {
        $this->_programId = $programmId;

        // ermitteln Preisvarianten eines Programmes
        $programmvarianten = $this->ermittelnPreisvariantenProgramm($programmId, $this->bestandsbuchungPreisvarianteId);

        // erstellen select Box Formular
        $this->buildSelectBoxProgrammvarianten($programmvarianten);

        return $this;
    }

    /**
     * Ermittelt die Preisvarianten eines Programmes
     *
     * + alle Preisvarianten eines neuen Programmes
     * + 1 Preisvariante eines bereits gebuchten Programmes
     *
     * @param $programmId
     * @return array
     */
    private function ermittelnPreisvariantenProgramm($programmId, $bestandsbuchungPreisvarianteId = false)
    {
        $cols = array(
            'id',
            'verkaufspreis',
            'preisvariante_' . $this->_anzeigeSprache . ' as preisvariante',
            'confirm_1'
        );

        $select = $this->_viewPreisvariantenSprachen->select();
        $select
            ->from($this->_viewPreisvariantenSprachen,$cols)
            ->where('programmdetailId = ' . $programmId)
            ->order('preisvariante_' . $this->_anzeigeSprache);

        if(!empty($bestandsbuchungPreisvarianteId))
            $select->where("id = ".$bestandsbuchungPreisvarianteId);

        $query = $select->__toString();

        $programmvarianten = $this->_tabellePreisvarianten->fetchAll($select)->toArray();

        return $programmvarianten;
    }

    /**
     * Baut die Select - Box der Auswahl
     * der Programmvarianten
     *
     * @return void
     */
    private function buildSelectBoxProgrammvarianten ($__programmvarianten)
    {

        for($i = 0; $i < count($__programmvarianten); $i++) {



                $this->_inputBox[$i] = "<span id='opt_".$i."' value='" . $__programmvarianten[ $i ][ 'id' ] . "'>" . $__programmvarianten[ $i ][ 'preisvariante' ] . "</span>";


                $this->_selectBox .= "<option  value='" . $__programmvarianten[ $i ][ 'id' ] . "'>" . $__programmvarianten[ $i ][ 'preisvariante' ] . "</option>\n";

                if($i == 0) {
                    $this->_preisErsteProgrammvariante = $__programmvarianten[ $i ][ 'verkaufspreis' ];
                }

                $this->_preiseProgrammVarianten .= "'" . $__programmvarianten[ $i ][ 'verkaufspreis' ] . "',";
            $this->_confirmBox[$i] = $__programmvarianten[ $i ][ 'confirm_1' ];

        }

        $this->_preiseProgrammVarianten = substr($this->_preiseProgrammVarianten, 0, -1);


        return;
    }

    /**
     * gibt die Werte der Bestelltabelle zurück
     *
     * @return array
     */
    public function getBestellTabelle ()
    {
        $programmvarianten = array();

        for($i = 0; $i < count($this->_inputBox); $i++) {
            if ($this->_inputBox[$i]!= ""){
                $programmvarianten[$i]['options'] = $this->_selectBox;
                $programmvarianten[$i]['inputs'] = $this->_inputBox[$i];
                $programmvarianten[$i]['confirm'] = $this->_confirmBox[$i];
            }
        }

        $this->_anzahlPreisvarianten = count($programmvarianten);

        return $programmvarianten;
    }

    /**
     * Gibt die Anzahl der Preisvarianten eines Programmes zurück
     *
     * @return null
     */
    public function getAnzahlPreisvarianten ()
    {

        return $this->_anzahlPreisvarianten;
    }

    /**
     * Gibt den Preis der ersten
     * Programmvariante zurück
     *
     * @return bool|null
     */
    public function getStartpreis ()
    {
        if($this->_preisErsteProgrammvariante == null) {
            return false;
        }

        return $this->_preisErsteProgrammvariante;
    }

    /**
     * Gibt die Preise der Programmvarianten für ein Javascript Array zurück
     *
     * @return bool|Array
     */
    public function getPreiseDerProgrammvarianten ()
    {
        if($this->_preiseProgrammVarianten === false or count($this->_preiseProgrammVarianten) == 0)
            return false;

        return $this->_preiseProgrammVarianten;
    }

    /********** speichern der Programmvarianten **************/

    /**
     * Kontrolliert die ankommenden Programmvarianten.
     * Abbruch wenn keine Integer - Werte
     * Abbruch wenn Werte nicht vorhanden
     * Aufbereitung der bestellten Programmvarianten
     *
     * @throws nook_Exception
     * @param $__buchungsDaten
     * @return array
     */
    public function kontrolleAnkommendeProgrammVarianten (array $__buchungsDaten)
    {

        // Datum
        if(!array_key_exists('datum', $__buchungsDaten)) {
            throw new nook_Exception($this->_error_wert_nicht_vorhanden);
        }

        $programmdatum = $__buchungsDaten[ 'datum' ];
        unset($__buchungsDaten[ 'datum' ]);

        // Programm ID
        if(!array_key_exists('ProgrammId', $__buchungsDaten)) {
            throw new nook_Exception($this->_error_wert_nicht_vorhanden);
        }

        $programmId = (int) $__buchungsDaten[ 'ProgrammId' ];
        if(!filter_var($programmId, FILTER_VALIDATE_INT)) {
            throw new nook_Exception($this->_error_daten_nicht_integer);
        }

        unset($__buchungsDaten[ 'ProgrammId' ]);

        // Zeit
        if(isset($__buchungsDaten[ 'zeitmanagerStunde' ]) and isset($__buchungsDaten[ 'zeitmanagerMinute' ])) {
            $programmzeit = $__buchungsDaten[ 'zeitmanagerStunde' ] . ":" . $__buchungsDaten[ 'zeitmanagerMinute' ];

            unset($__buchungsDaten[ 'zeitmanagerStunde' ]);
            unset($__buchungsDaten[ 'zeitmanagerMinute' ]);
        }

        if(isset($__buchungsDaten[ 'zeitmanager' ])) {
            $programmzeit = $__buchungsDaten[ 'zeitmanager' ];

            unset($__buchungsDaten[ 'zeitmanager' ]);
        }

        $j = 0;
        $programmVarianten = array();
        for($i = 0; $i < $this->_condition_moegliche_anzahl_programmvarianten; $i++) {

            // Anzahl
            $__buchungsDaten[ $i ] = (int) $__buchungsDaten[ $i ];
            if($__buchungsDaten[ $i ] == 0) {
                continue;
            }

            // Programmvariante
            $__buchungsDaten[ 'programmvariante_' . $i ] = (int) $__buchungsDaten[ 'programmvariante_' . $i ];
            if($__buchungsDaten[ 'programmvariante_' . $i ] == 0) {
                continue;
            }

            if(!filter_var($__buchungsDaten[ $i ], FILTER_VALIDATE_INT)) {
                throw new nook_Exception($this->_error_daten_nicht_integer);
            }

            $__buchungsDaten[ 'programmvariante_' . $i ] = (int) $__buchungsDaten[ 'programmvariante_' . $i ];

            $programmVarianten[ $j ][ 'programmVariante' ] = $__buchungsDaten[ 'programmvariante_' . $i ]; // Programmvariante
            $programmVarianten[ $j ][ 'programmAnzahl' ] = $__buchungsDaten[ $i ]; // Anzahl
            $programmVarianten[ $j ][ 'programmDatum' ] = $programmdatum; // Datum
            $programmVarianten[ $j ][ 'programmZeit' ] = $programmzeit; // Zeit

            $j++;
        }

        return $programmVarianten;
    }

}