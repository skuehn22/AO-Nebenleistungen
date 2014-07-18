<?php
/**
 * Darstellung der Preise.
 * Komma entsprechend der Länderkennung
 *
 *
 *
 * <code>
 *  Codebeispiel
 * </code>
 *
 * @author Stephan Krauß
 */
class nook_ToolPreise{


    /**
     * Formatiert die Komma der Preise entsprechend der
     * Länderkennung
     *
     * @static
     * @param $__gesamtpreis
     * @return string
     */
    static public function berechneGesamtpreisAllerArtikelImWarenkorb($__gesamtpreis){
        $translate = new Zend_Session_Namespace('translate');
        $translateItems = $translate->getIterator();

        if($translateItems['language'] == 'de')
            $gesamtpreis = number_format($__gesamtpreis, 2, ',', '');
        else
            $gesamtpreis = number_format($__gesamtpreis, 2, '.', '');

        return $gesamtpreis;
    }

    /**
     * Ermittelt die Mehrwertsteuer entsprechend
     * der Kennung
     *
     * @static
     * @param $__mehrwertSteuerCode
     * @return float
     */
    static public function findMehrwertsteuerFaktor($__mehrwertSteuerCode){

		if($__mehrwertSteuerCode == 'A')
			$vat = 1.19;
		elseif($__mehrwertSteuerCode == 'B')
			$vat = 1.07;
        elseif($__mehrwertSteuerCode == 'C')
            $vat = 1.0;
        else
            $vat = 1.0;
		
		return $vat;
	}

    /**
     * Wandelt das Komma in einem Preis
     * zu einem Punkt um.
     *
     * @static
     * @param $__preis
     * @return mixed
     */
    static public function wandleKommaZuPunkt($__preis){
        $preisMitPunkt = str_replace(',','.',$__preis);

        return $preisMitPunkt;
    }

    /**
     * Wandelt den Punkt in einer Preisangabe
     * zu einem Komma um
     *
     * @static
     * @param $__preis
     * @return mixed
     */
    public static  function wandlePunktZuKomma($__preis){
        $preisMitKomma = str_replace('.',',',$__preis);

        return $preisMitKomma;
    }

    /**
     *
     *
     * @static
     * @param array $__preise
     * @param $__variablenName
     * @return void
     */
    static function wandlePunktZuKommaArray(array $__preise, $__variablenName){

        for($i = 0; $i < count($__preise); $i++){
            $__preise[$i][$__variablenName] = str_replace('.',',',$__preise[$i][$__variablenName]);
        }

        return $__preise;
    }

    /**
     * Berechnung Mehrwertsteuer
     * Berechnung Nettopreis
     *
     * @static
     * @param $__bruttoPreis
     * @param $__mehrwertSteuer
     * @return array
     */
    static function bestimmeNettopreis($__bruttoPreis, $__mehrwertSteuer){
        $preise = array();

        $preise['netto'] = $__bruttoPreis / ($__mehrwertSteuer / 100 + 1);
        $preise['brutto'] = $__bruttoPreis;
        $preise['mwst'] = $preise['netto'] * ($__mehrwertSteuer / 100);

        return $preise;
    }

    /**
     * Berechnung Mehrwertsteuer
     * Berechnung Bruttopreis
     *
     * @static
     * @param $__nettoPreis
     * @param $__mehrwertSteuer
     * @return array
     */
    static function bestimmeBruttopreis($__nettoPreis, $__mehrwertSteuer){
        $preise = array();

        $preise['brutto'] = $__nettoPreis * ($__mehrwertSteuer / 100 + 1);
        $preise['mwst'] = $__nettoPreis * ($__mehrwertSteuer / 100);
        $preise['netto'] = $__nettoPreis;

        return $preise;
    }

    /**
     * Berechnet die Mehrwertsteuer mittels Bruttopreis und Steuersatz
     *
     * + Steuersatz = 0.19
     * + Steuersatz = 0.07
     *
     * @param $brutto
     * @param $steuersatz
     * @return mixed
     */
    public static function berechneNetto($brutto, $steuersatz)
    {
        if($steuersatz == 0)
            return $brutto;

        $steuersatz = $steuersatz * 100;
        $netto = $brutto / ($steuersatz + 100) * 100;

        return $netto;
    }

}