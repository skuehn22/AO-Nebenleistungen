<?php
 
class Front_Model_ProgrammdetailPreismanager extends nook_Model_model{

    private $_error_daten_stimmen_nicht = 560;
    private $_error_programm_id_nicht_vorhanden = 561;
    private $_error_personenanzahl_stimmt_nicht = 562;
    private $_error_pflichtvariable_nicht_vorhanden = 10001;

    private $_condition_kleiner_als = 1;
    private $_condition_gleich_als = 2;
    private $_condition_groesser_als =3;

    private $_condition_preis_ist_personenpreis = 1;

    private $_condition_preisvariante_personenzuschlag = 3;
    private $_condition_preisvariante_zuschlag_fuer_wochentag = 4;
    private $_condition_preisvariante_sprache = 5;
    private $_condition_preisvariante_dauer = 6;
    private $_condition_preisvariante_uhrzeit = 7;

    private $_suchdaten;
    private $_db_front;
    private $_programmId;
    private $_basisDatenProgramm = array();

    private $_gruppenDerPreisvariantenEinesProgrammes;

    private $_berechnetePreiszuschlaegeEinesProgrammes = array();

    private $_mehrwertSteuerUmrechnung = array(
        'A' => 1.19,
        'B' => 1.07,
        'C' => 1.0
    );

    public function __construct(){
        $this->_db_front = Zend_Registry::get('front');

        return;
    }

    public function mapData($__rawData){
        $daten = array();

        unset($__rawData['controller']);
        unset($__rawData['module']);
        unset($__rawData['action']);

        $daten = $__rawData;

        return $daten;
    }

    public function checkDatenVorhanden(array $__rawData, array $__pflichtFelder){

        foreach($__pflichtFelder as $value){
            if(!array_key_exists($value, $__rawData))
                throw new nook_Exception(10001);
        }

        return;
    }

    public function checkDatenInhalt($__rawData){
        $daten = array();
        $kontrolle = array();

        foreach($__rawData as $key => $value){

            if($key == 'controller' or $key == 'module' or $key == 'action')
                continue;

            if($key == 'datum' and $value == '00.00.0000')
                continue;
            elseif($key == 'datum'){
                $daten[$key] = $value;

                continue;
            }

            $daten[$key] = (int) $value;
        }

        $filter = array(
            'personenanzahl' => array(
                'filter' => FILTER_VALIDATE_INT
            ),
            'stunde' => array(
                'filter' => FILTER_VALIDATE_INT
            ),
            'treffpunkt' => array(
                'filter' => FILTER_VALIDATE_INT
            ),
            'sprache' => array(
                'filter' => FILTER_VALIDATE_INT
            ),
            'programmId' => array(
                'filter' => FILTER_VALIDATE_INT
            )
        );

        $kontrolle = filter_var_array($daten, $filter);

        foreach($kontrolle as $key => $value){
            if($value === false)
                throw new nook_Exception($this->_error_daten_stimmen_nicht);
        }

        return $daten;
    }

    

    public function setDaten($__daten){
        $this->_suchdaten = $__daten;

        return $this;
    }

    public function getNeuberechneterPreis(){

        $neuerPreis = $this
            ->_ermittelnDerBasisdatenDesProgrammes()
            ->_ermittelnPreiszuschlaegeDesProgrammes()
            ->_preisberechnungAnzahlPersonen()
            ->_preisberechnungDatum()
            ->_preisberechnungStunde()
            ->_preisberechnungDauer()
            ->_preisberechnungSprache()
//            ->_preisberechnungTreffpunkt()
            ->_neuberchneterPreis();

        return $neuerPreis;
    }

    private function _neuberchneterPreis(){
        // Verkaufspreis Netto und Informationen
        $neuerPreis = array(
            'preis' => $this->_basisDatenProgramm['vk'],
            'de' => array(),
            'eng' => array()
        );
        
        $basisPreis = $this->_basisDatenProgramm['vk']; // Netto Verkaufspreis

        if(count($this->_berechnetePreiszuschlaegeEinesProgrammes) > 0){
            foreach($this->_berechnetePreiszuschlaegeEinesProgrammes as $key => $preisZuschlag){
                if($preisZuschlag['preistyp'] == 2)
                    $neuerPreis['preis'] += $preisZuschlag['value'];

                if($preisZuschlag['preistyp'] == 3){
                    $zuschlag = $this->_basisDatenProgramm['vk'] * $preisZuschlag['value'];
                    $neuerPreis['preis'] += $zuschlag;
                }

                $neuerPreis['de'][] .= $preisZuschlag['deutsch'];
                $neuerPreis['eng'][] .= $preisZuschlag['englisch'];
            }
        }

        // Mehrwertsteuer
        $neuerPreis['preis'] *= $this->_basisDatenProgramm['mehrwertSteuerFaktor'];
        $neuerPreis['preis'] = number_format($neuerPreis['preis'],2);

        return $neuerPreis;
    }

    private function _ermittelnDerBasisdatenDesProgrammes(){
        if(empty($this->_suchdaten['programmId']))
            throw new nook_Exception($this->_error_programm_id_nicht_vorhanden);

        $this->_programmId = (int) $this->_suchdaten['programmId'];

        $sql = "
            SELECT
                `vk`
                , `mwst_satz`
                , `minPersons`
                , `maxPersons`
                , `gruppenpreis`
            FROM
                `tbl_programmdetails`
            WHERE (`Fa_ID` = ".$this->_programmId.")";

        $basisDaten = $this->_db_front->fetchRow($sql);
        $basisDaten['mehrwertSteuerFaktor'] = $this->_mehrwertSteuerUmrechnung[$basisDaten['mwst_satz']];
        unset($basisDaten['mwst_satz']);

        $this->_basisDatenProgramm = $basisDaten;

        return $this;
    }

    private function _ermittelnPreiszuschlaegeDesProgrammes(){

        $sql = "
            SELECT
                `tbl_programmvarianten`.`deutsch`
                , `tbl_programmvarianten`.`englisch`
                , `tbl_programmvarianten`.`preistyp`
                , `tbl_programmvarianten`.`anzahl`
                , `tbl_programmvarianten`.`ansatz`
                , `tbl_programmvarianten`.`bezug`
                , `tbl_programmvarianten`.`bezeichnung`
                , `tbl_details_programmvarianten`.`value`
                , `tbl_programmvarianten`.`variantengruppe`
            FROM
                `tbl_details_programmvarianten`
                INNER JOIN `tbl_programmvarianten`
                    ON (`tbl_details_programmvarianten`.`programmvariante_id` = `tbl_programmvarianten`.`id`)
            WHERE (`tbl_details_programmvarianten`.`Fa_Id` =".$this->_programmId.")
            ORDER BY `tbl_programmvarianten`.`variantengruppe` ASC, `tbl_programmvarianten`.`ansatz` ASC, `tbl_programmvarianten`.`anzahl` ASC";

        $rawPreisVarianten = $this->_db_front->fetchAll($sql);


        $preisvariantenNachPreisgruppen = array();
        foreach($rawPreisVarianten as $key => $einzelnePreisvariante){

            $variantengruppe = $einzelnePreisvariante['variantengruppe'];
            unset($einzelnePreisvariante['variantengruppe']);

            $preisvariantenNachPreisgruppen[$variantengruppe][] = $einzelnePreisvariante;
        }

        $this->_gruppenDerPreisvariantenEinesProgrammes = $preisvariantenNachPreisgruppen;

        return $this;
    }

    // Zuschlag für Übersetzer
    private function _preisberechnungSprache(){

        // gibt es eine Zuschlag für den Übersetzer
        if(!array_key_exists($this->_condition_preisvariante_sprache, $this->_gruppenDerPreisvariantenEinesProgrammes))
            return $this;

        // wurde eine Sprache gewählt
        if($this->_suchdaten['programmSprache'] == '0')
            return $this;

        // ermitteln der Bezugsgröße
        $sql = "select de from tbl_prog_sprache where id = ".$this->_suchdaten['programmSprache'];
        $nameDerProgrammSprache = $this->_db_front->fetchOne($sql);

        $this->_filternPreiszuschlaegeQualitativ($this->_condition_preisvariante_sprache, $nameDerProgrammSprache);

        return $this;
    }

    // Zuschlag für Dauer des Programmes
    private function _preisberechnungDauer(){

        // gibt es eine Preisvariante für die Dauer des Programmes
        if(!array_key_exists($this->_condition_preisvariante_dauer, $this->_gruppenDerPreisvariantenEinesProgrammes))
            return $this;

        // wenn keine Dauer angegeben
        if($this->_suchdaten['dauer'] == '0')
            return $this;

        $this->_filternPreiszuschlaegeQuantitativ($this->_condition_preisvariante_dauer, $this->_suchdaten['dauer']);

        return $this;
    }

    // Zuschlag für Stunde des Tages
    private function _preisberechnungStunde(){

        // gibt es eine Preisvariante für die Uhrzeit
        if(!array_key_exists($this->_condition_preisvariante_uhrzeit, $this->_gruppenDerPreisvariantenEinesProgrammes))
            return $this;

        // wenn keine Uhrzeit angegeben
        if($this->_suchdaten['stunde'] == '0')
            return $this;

        $this->_filternPreiszuschlaegeQuantitativ($this->_condition_preisvariante_uhrzeit, $this->_suchdaten['stunde']);

        return $this;
    }

    // Zuschlag für Anzahl personen Personen
    private function _preisberechnungAnzahlPersonen(){

        // gibt es eine Preisvariante für Personenanzahl
        if(!array_key_exists($this->_condition_preisvariante_personenzuschlag, $this->_gruppenDerPreisvariantenEinesProgrammes))
            return $this;

        // wenn keine Personenanzahl angegeben
        if(empty($this->_suchdaten['personenanzahl']))
            return $this;

        // wenn kein Personenpreis
        if($this->_basisDatenProgramm['gruppenpreis'] != $this->_condition_preis_ist_personenpreis)
            return $this;

        if(($this->_suchdaten['personenanzahl'] < $this->_basisDatenProgramm['minPersons']) or ($this->_suchdaten['personenanzahl'] > $this->_basisDatenProgramm['maxPersons']))
            throw new nook_Exception($this->_error_personenanzahl_stimmt_nicht);

        $this->_filternPreiszuschlaegeQuantitativ($this->_condition_preisvariante_personenzuschlag, $this->_suchdaten['personenanzahl']);

        return $this;
    }

    // Zuschlag für Wochentag
    private function _preisberechnungDatum(){

        // gibt es eine Preisvariante für den Wochentag
        if(!array_key_exists($this->_condition_preisvariante_zuschlag_fuer_wochentag, $this->_gruppenDerPreisvariantenEinesProgrammes))
            return $this;

        // wenn kein Datum angegeben
        if($this->_suchdaten['datum'] == '00.00.0000')
            return $this;

        $wochentag = $this->_ermittelnWochentag();

        $this->_filternPreiszuschlaegeQuantitativ($this->_condition_preisvariante_zuschlag_fuer_wochentag, $wochentag);

        return $this;
    }

    // quantitative Preiszuschläge
    private function _filternPreiszuschlaegeQuantitativ($__preisGruppenNummer, $__vergleichswert){

        for($i = 0; $i < count($this->_gruppenDerPreisvariantenEinesProgrammes[$__preisGruppenNummer]);$i++){

            // ????
            $preisvariante = $this->_gruppenDerPreisvariantenEinesProgrammes[$__preisGruppenNummer][$i];

            if(($preisvariante['ansatz'] == $this->_condition_kleiner_als) and ($__vergleichswert < $preisvariante['anzahl']))
                $this->_berechnetePreiszuschlaegeEinesProgrammes[$__preisGruppenNummer] = $preisvariante;

            if(($preisvariante['ansatz'] == $this->_condition_gleich_als) and ($__vergleichswert == $preisvariante['anzahl']))
                $this->_berechnetePreiszuschlaegeEinesProgrammes[$__preisGruppenNummer] = $preisvariante;

            if(($preisvariante['ansatz'] == $this->_condition_groesser_als) and ($__vergleichswert > $preisvariante['anzahl']))
                $this->_berechnetePreiszuschlaegeEinesProgrammes[$__preisGruppenNummer] = $preisvariante;
        }

        return;
    }

    // qualitative Preiszuschläge
    private function _filternPreiszuschlaegeQualitativ($__preisGruppenNummer, $__vergleichswert){
        for($i = 0; $i < count($this->_gruppenDerPreisvariantenEinesProgrammes[$__preisGruppenNummer]);$i++){

            $preisvariante = $this->_gruppenDerPreisvariantenEinesProgrammes[$__preisGruppenNummer][$i];

            if($preisvariante['bezug'] == $__vergleichswert)
                $this->_berechnetePreiszuschlaegeEinesProgrammes[$__preisGruppenNummer] = $preisvariante;
        }

        return;
    }

    private function _ermittelnWochentag(){

        if($this->_suchdaten['sprache'] == 'de'){
            $teileDatum = explode('.',$this->_suchdaten['datum']);
            $zeit = strtotime((int)$teileDatum[2]."-".(int)$teileDatum[1]."-".(int)$teileDatum[0]);
        }
        else{
            $teileDatum = explode('/',$this->_suchdaten['datum']);
            $zeit = strtotime((int)$teileDatum[2]."-".(int)$teileDatum[0]."-".(int)$teileDatum[1]);
        }

        $wochentagZaehler = date("w",$zeit);
        // Korrektur Sonntag
        if($wochentagZaehler == 0)
            $wochentagZaehler = 7;

        return $wochentagZaehler;
    }

}
