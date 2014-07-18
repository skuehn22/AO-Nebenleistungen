<?php
/**
 * Updat eines bereits gebuchten Programmes
 *
 * @author Stephan.Krauss
 * @date 19.02.2014
 * @file ProgrammUpdate.php
 * @package front
 * @subpackage model
 */
class Front_Model_ProgrammUpdate
{
    protected $buchungsdaten = array();
    protected $tabelleProgrammbuchung = NULL;
    protected $idTabelleProgrammbuchung = NULL;
    protected $korrekturAnzahlProgramme = 0;
    protected $anzahlUpdateDatensaetze = 0;

    /**
     * @param $korrekturAnzahlProgramme
     *
     * @return Front_Model_ProgrammUpdate
     */
    public function setKorrekturAnzahlProgramme($korrekturAnzahlProgramme)
    {
        $this->korrekturAnzahlProgramme = $korrekturAnzahlProgramme;

        return $this;
    }

    /**
     * @param array $buchungsdaten
     *
     * @return Front_Model_ProgrammUpdate
     */
    public function setBuchungsdaten(array $buchungsdaten)
    {
        $this->buchungsdaten = $buchungsdaten;

        return $this;
    }

    /**
     * @return int
     */
    public function getAnzahlUpdateProgrammbuchung()
    {
        return $this->anzahlUpdateDatensaetze;
    }

    /**
     * Steuert die Ermittlung ob eine Programmbuhung in 'tbl_programmbuchung' bereits vorhanden ist
     *
     * @return Front_Model_ProgrammUpdate
     * @throws Exception
     */
    public function steuerungKontrolleProgrammBereitsgebucht()
    {
        try {
            if (count($this->buchungsdaten) == 0) {
                throw new nook_Exception('Buchungsdaten fehlen');
            }

            $tabelleProgrammbuchung = $this->getTabelleProgrammbuchung();
            $neueAnzahl = $this->buchungsdaten['anzahl'] + $this->korrekturAnzahlProgramme;

            if (!is_null($this->idTabelleProgrammbuchung)) {
                $this->anzahlUpdateDatensaetze = $this->korrekturBuchungMitIdTabelleProgrammbuchung(
                    $tabelleProgrammbuchung,
                    $this->idTabelleProgrammbuchung,
                    $neueAnzahl
                );
            }
            else {
                $where = $this->whereErstellen($this->buchungsdaten);
                $this->anzahlUpdateDatensaetze = $this->korrekturBuchungTabelleProgrammbuchung(
                    $tabelleProgrammbuchung,
                    $where,
                    $neueAnzahl
                );
            }

        }
        catch (Exception $e) {
            throw $e;
        }

        return $this;
    }

    /**
     * @return Application_Model_DbTable_programmbuchung
     */
    public function getTabelleProgrammbuchung()
    {
        if (is_null($this->tabelleProgrammbuchung)) {
            $this->tabelleProgrammbuchung = new Application_Model_DbTable_programmbuchung();
        }

        return $this->tabelleProgrammbuchung;
    }

    /**
     * @param Zend_Db_Table_Abstract $tabelleProgrammbuchung
     *
     * @return Front_Model_ProgrammUpdate
     */
    public function setTabelleProgrammbuchung(Zend_Db_Table_Abstract $tabelleProgrammbuchung)
    {
        $this->tabelleProgrammbuchung = $tabelleProgrammbuchung;

        return $this;
    }

    /**
     * Update der Anzahl gebuchter Programme mittels ID der 'tbl_programmbuchung'
     *
     * @param Zend_Db_Table_Abstract $tabelleProgrammbuchung
     * @param $idTabelleProgrammbuchung
     * @param $neueAnzahl
     *
     * @return int
     */
    protected function korrekturBuchungMitIdTabelleProgrammbuchung(
        Zend_Db_Table_Abstract $tabelleProgrammbuchung,
        $idTabelleProgrammbuchung,
        $neueAnzahl
    )
    {
        $update = array(
            'anzahl' => $neueAnzahl
        );

        $whereIdTabelleProgrammbuchung = "id = " . $idTabelleProgrammbuchung;

        $anzahlUpdateDatensaetze = $tabelleProgrammbuchung->update($update, $whereIdTabelleProgrammbuchung);

        return $anzahlUpdateDatensaetze;
    }

    /**
     * Erstellt aus den Buchungsdaten die where - Klauseln
     *
     * @param array $buchungsdaten
     *
     * @return array
     */
    protected function whereErstellen(array $buchungsdaten)
    {
        $where = array();

        foreach ($buchungsdaten as $key => $value) {

            if ($key == 'anzahl') {
                continue;
            }

            $where[] = $key . " = '" . $value . "'";
        }

        return $where;
    }

    /**
     * Update der Anzahl der gebuchten Programme mit where - Klausei
     *
     * @param Zend_Db_Table_Abstract $tabelleProgrammbuchung
     * @param $where
     * @param $neueAnzahl
     *
     * @return int
     */
    protected function korrekturBuchungTabelleProgrammbuchung(
        Zend_Db_Table_Abstract $tabelleProgrammbuchung,
        $where,
        $neueAnzahl
    )
    {
        $update = array(
            'anzahl' => $neueAnzahl
        );

        $anzahlUpdateDatensaetze = $tabelleProgrammbuchung->update($update, $where);

        return $anzahlUpdateDatensaetze;
    }

}
 