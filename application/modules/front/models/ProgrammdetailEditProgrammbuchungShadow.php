<?php
/**
 * Action Programmdetail_EditBestandsbuchung
 *
 * + Ausführliche Beschreibung der Klasse
 * + Ausführliche Beschreibung der Klasse
 * + Ausführliche Beschreibung der Klasse
 *
 * @author Stephan.Krauss
 * @date 28.06.13
 * @file ProgrammdetailEditBestandsbuchungShadow.php
 * @package front
 * @subpackage shadow
 */

class Front_Model_ProgrammdetailEditProgrammbuchungShadow
{

    /**
     * Ermittelt die Preisvariante
     *
     * + Preisvariante ID wird übergeben
     *
     * @param $preisvarianteId
     * @return array
     */
    public function ermittelnPreisvariante($preisvarianteId)
    {
        $model = new Front_Model_WarenkorbEditProgramm();
        $preisvariante = $model
            ->kontrolleIdTabelleProgrammbuchung($preisvarianteId)
            ->findeWerteProgrammbuchung()
            ->getPreisvariante();

        return $preisvariante;
    }

    /**
     * Holt den Original Buchungsdatensatz eines Programmes
     *
     * @param array $preisvariante
     * @return array
     */
    public function ermittelnOriginalProgrammbuchungsdaten(array $preisvariante)
    {
        /** @var  $toolProgrammbuchungDatensatz nook_ToolProgrammbuchungDatensatz */
        $toolProgrammbuchungDatensatz = nook_ToolProgrammbuchungDatensatz::instance();
        $originalBuchungsDatensatzProgramm = $toolProgrammbuchungDatensatz
            ->setProgrammbuchungId($preisvariante[ 'programmbuchungId' ])
            ->steuerungErmittlungBuchungsdatensatzProgramme()
            ->getProgrammbuchungDatensatz();

        return $originalBuchungsDatensatzProgramm;
    }

    /**
     * Ermittelt den Preis einer Preisvariante
     *
     * @param $programmpreisvarianteId
     * @return nook_ToolPreisvariante
     */
    public function ermittelnPreisDerpreisvariante($programmpreisvarianteId)
    {
        $toolPreisDerPreisvariante = nook_ToolPreisvariante::getInstance();
        $preisDerPreisvariante = $toolPreisDerPreisvariante
            ->setPreisVarianteId($programmpreisvarianteId)
            ->steuerungErmittelnPreisDerPreisvariante()
            ->getPreisVariantePreis();

        return $preisDerPreisvariante;
    }

    /**
     * Mappen der Daten einer Bestandsbuchung
     *
     * @param $preisvariante
     * @param $preisDerPreisvariante
     * @param $originalBuchungsdatensatzProgramm
     * @return array
     */
    public function mappenBestandsbuchung($preisvariante, $preisDerPreisvariante, $originalBuchungsdatensatzProgramm)
    {
        $datenBestandsbuchung = array();

        $datenBestandsbuchung[ 'datum' ] = $preisvariante[ 'datum' ];
        $datenBestandsbuchung[ 'anzahl' ] = $originalBuchungsdatensatzProgramm[ 'anzahl' ];
        $datenBestandsbuchung[ 'zeit' ] = $preisvariante[ 'zeit' ];
        $datenBestandsbuchung[ 'preis' ] = $preisDerPreisvariante;
        $datenBestandsbuchung[ 'preisvariante' ] = $originalBuchungsdatensatzProgramm[ 'tbl_programme_preisvarianten_id' ];
        $datenBestandsbuchung[ 'namepreisvariante' ] = $preisvariante['preisvariante'];

        $datenBestandsbuchung['zeitmanagerSelect'] = $preisvariante['zeitmanagerSelect'];
        $datenBestandsbuchung['zeitmanagerStunde'] = (int) $preisvariante['zeitmanagerStunde'];
        $datenBestandsbuchung['zeitmanagerMinute'] = (int) $preisvariante['zeitmanagerMinute'];

        return $datenBestandsbuchung;
    }

    /**
     * Bereitet Zeiten für den Zeitmanager auf.
     *
     * Darstellung der Zeit für die Varianten des Preismanager
     *
     * + Zeit für Select Box
     * + Zeit für Stundeneingabe
     * + Zeit für Minuteneingabe
     *
     * @param array $gebuchtesProgramm
     * @return array
     */
    public function zeitmanager(array $preisvariante)
    {
        $selectZeit = nook_ToolZeiten::kappenZeit($preisvariante['zeit'], 2);
        $preisvariante['zeitmanagerSelect'] = $selectZeit;

        $zeitangabe = nook_ToolZeiten::teileZeit($preisvariante['zeit']);
        $preisvariante['zeitmanagerStunde'] = $zeitangabe['stunde'];
        $preisvariante['zeitmanagerMinute'] = $zeitangabe['minute'];

        return $preisvariante;
    }

}
