<?php

/**
 * Der Benutzer kann die Anzahl der Programme mit verschiedenen Paramtern ermitteln. Es wird ein Array der Seiten zurückgegeben. Programmsuche
 *
 * + Suchparameter:
 * + sprache ID
 * + City ID
 * + Suchbegriff
 * + Seitennummer
 * + Programmkategorie
 * + Seite vor
 * + Seite zurück
 *
 * @author Stephan
 * @date 10.03.14
 * @file ProgrammeSeitenSuchen.php
 * @project HOB
 * @package front | admin | plugin | tabelle | tool
 * @subpackage controller | model
 */

class Front_Model_ProgrammeSeitenSuchen
{
    protected $programmeProSeite = 0;
    protected $anzahlSichtbareSeiten = 0;
    protected $spracheId = null;
    protected $cityId = null;
    protected $suchbegriff = null;
    protected $programmkategorieId = 0;
    protected $startSeite = 1;

    protected $condition_module = 'front';
    protected $condition_controller = 'programmstart';
    protected $condition_action = 'index';

    /** @var $viewProgrammsucheDe Zend_Db_Table_Abstract */
    protected $viewProgrammsucheDe = null;
    /** @var $viewProgrammsucheEn Zend_Db_Table_Abstract */
    protected $viewProgrammsucheEn = null;
    /** @var $select Zend_Db_Table_Select */
    protected $select = null;

    protected $anzahlProgramme = 0;
    protected $seiten = array();


    /**
     * @param $startSeite
     * @return Front_Model_ProgrammeSeitenSuchen
     */
    public function setStartSeite($startSeite)
    {
        $startSeite = (int) $startSeite;
        $this->startSeite = $startSeite;

        return $this;
    }

    /**
     * @param $anzahlSichtbareSeiten
     * @return Front_Model_ProgrammeSeitenSuchen
     */
    public function setAnzahlSichtbareSeiten($anzahlSichtbareSeiten)
    {
        $anzahlSichtbareSeiten = (int) $anzahlSichtbareSeiten;
        $this->anzahlSichtbareSeiten = $anzahlSichtbareSeiten;

        return $this;
    }

    /**
     * @param $programmeProSeite
     * @return Front_Model_ProgrammeSeitenSuchen
     */
    public function setProgrammeProSeite($programmeProSeite)
    {
        $programmeProSeite = (int) $programmeProSeite;
        $this->programmeProSeite = $programmeProSeite;

        return $this;
    }

    /**
     * @param $cityId
     * @return Front_Model_ProgrammeSeitenSuchen
     */
    public function setCityId($cityId)
    {
        $cityId = (int) $cityId;
        $this->cityId = $cityId;

        return $this;
    }

    /**
     * @param $programmkategorie
     * @return Front_Model_ProgrammeSeitenSuchen
     */
    public function setProgrammkategorieId($programmkategorie)
    {
        $programmkategorie = (int) $programmkategorie;
        $this->programmkategorieId = $programmkategorie;

        return $this;
    }

    /**
     * @param $spracheId
     * @return Front_Model_ProgrammeSeitenSuchen
     */
    public function setSpracheId($spracheId)
    {
        $this->spracheId = $spracheId;

        return $this;
    }

    /**
     * @param $suchbegriff
     * @return Front_Model_ProgrammeSeitenSuchen
     */
    public function setSuchbegriff($suchbegriff)
    {
        $this->suchbegriff = $suchbegriff;

        return $this;
    }

    /**
     * @param Zend_Db_Table_Abstract $viewProgrammsucheDe
     * @return Front_Model_ProgrammeSeitenSuchen
     */
    public function setViewProgrammsucheDe(Zend_Db_Table_Abstract $viewProgrammsucheDe)
    {
        $this->viewProgrammsucheDe = $viewProgrammsucheDe;

        return $this;
    }

    /**
     * @return Application_Model_DbTable_viewProgrammsucheDe
     */
    public function getProgrammsucheDe()
    {
        if(is_null($this->viewProgrammsucheDe))
            $this->viewProgrammsucheDe = new Application_Model_DbTable_viewProgrammsucheDe();

        return $this->viewProgrammsucheDe;
    }

    /**
     * @param Zend_Db_Table_Abstract $viewProgrammsucheEn
     * @return Front_Model_ProgrammeSeitenSuchen
     */
    public function setViewProgrammsucheEn(Zend_Db_Table_Abstract $viewProgrammsucheEn)
    {
        $this->viewProgrammsucheEn = $viewProgrammsucheEn;

        return $this;
    }

    /**
     * @return Application_Model_DbTable_viewProgrammsucheEn
     */
    public function getProgrammsucheEn()
    {
        if(is_null($this->viewProgrammsucheEn))
            $this->viewProgrammsucheEn = new Application_Model_DbTable_viewProgrammsucheEn();

        return $this->viewProgrammsucheEn;
    }

    /**
     * Steuerung der Ermittlung der Seiten der Programme
     *
     * + Pflichtwerte zur suche der Seiten der Programme
     * + suche im Programm Namen
     *
     * @return Front_Model_ProgrammeSeitenSuchen
     * @throws Exception
     */
    public function steuerungErmittlungAnzahlProgramme()
    {
        try{
            // Pflichtwerte
            if(is_null($this->spracheId))
                throw new nook_Exception('Sprache ID fehlt');

            if(is_null($this->cityId))
                throw new nook_Exception('Stadt ID fehlt');

            if(empty($this->programmeProSeite))
                throw new nook_Exception('Anzahl Programme pro Seite fehlt');

            if(empty($this->anzahlSichtbareSeiten))
                throw new nook_Exception('Anzahl sichtbare Seiten fehlt');

            // View
            if($this->spracheId == 1)
                $viewProgrammsuche = $this->getProgrammsucheDe();
            else
                $viewProgrammsuche = $this->getProgrammsucheEn();

            // Select Objekt
            $select = $this->erstellenSelect($viewProgrammsuche);

            // Stadt ID
            $select = $this->selectCityId($select, $this->cityId);

            // suche im Programmnamen
            if(!is_null($this->suchbegriff))
                $select = $this->selectSuchbegriff($this->suchbegriff, $this->select);

            // suche nach der Programmkategorie
            if(!empty($this->programmkategorieId))
                $select = $this->selectProgrammkategorie($this->programmkategorieId, $select);
            else
                $select = $this->gruppierenNachProgrammId($select);

            // ermitteln Anzahl Seiten
            $anzahlErmittelterProgramme = $this->ermittelnAnzahlProgramme($viewProgrammsuche, $select);
            $this->anzahlProgramme = $anzahlErmittelterProgramme;

            // generieren Array der Seiten, wenn mehr als eine Seite
            if($anzahlErmittelterProgramme > $this->programmeProSeite){
                $seiten = $this->seitenAufrufePaginator($anzahlErmittelterProgramme, $this->programmeProSeite, $this->startSeite);
                $this->seiten = $seiten;
            }
            else
                $this->seiten = false;


            return $this;

        }
        catch(Exception $e)
        {
            throw $e;
        }
    }

    /**
     * Wenn keine Programmkategorie ID gegeben
     *
     * @param Zend_Db_Table_Select $select
     * @return Zend_Db_Table_Select
     */
    protected function gruppierenNachProgrammId(Zend_Db_Table_Select $select)
    {
        $select->group('id');
        $query = $select->__toString();

        return $select;
    }

    /**
     * Select mittels City ID
     *
     * @param Zend_Db_Table_Select $select
     * @param $cityId
     * @return Zend_Db_Table_Select
     */
    protected function selectCityId(Zend_Db_Table_Select $select, $cityId)
    {
        $whereCityId = "AO_City = ".$cityId;
        $select->where($whereCityId);

        $query = $select->__toString();

        return $select;
    }

    /**
     * baut das Array der Seiten.
     *
     * + vor - Button
     * + zurueck - Button
     * + Begrenzung auf vorgegebene Anzahl der Seiten
     *
     * @param $anzahlErmittelterProgramme
     * @param $programmeProSeite
     * @return array
     */
    protected function seitenAufrufePaginator($anzahlErmittelterProgramme, $programmeProSeite, $startSeite)
    {
        $paginator = array();

        // mögliche Anzahl der Seiten
        $anzahlSeiten = $anzahlErmittelterProgramme / $programmeProSeite;
        $anzahlMoeglicheSeiten = ceil($anzahlSeiten);


        // aktive Button links
        if($startSeite == 1)
            $paginator = $this->ersteSeite($paginator, $anzahlMoeglicheSeiten);
        else
            $paginator = $this->naechsteSeite($startSeite, $paginator, $anzahlMoeglicheSeiten);

        return $paginator;
    }

    /**
     * Ermittelt die Anzahl der Programme
     *
     * @param Zend_Db_Table_Abstract $viewProgrammsuche
     * @param Zend_Db_Table_Select $select
     * @param $condition_programme_pro_seite
     * @return mixed
     */
    protected function ermittelnAnzahlProgramme(Zend_Db_Table_Abstract $viewProgrammsuche,Zend_Db_Table_Select $select)
    {
        $query = $select->__toString();

        $rows = $viewProgrammsuche->fetchAll($select)->toArray();

        return count($rows);
    }

    /**
     * Sucht die Seiten in einer Stadt mittels Programmkategorie ID
     *
     * @param $programmkategorieId
     * @param Zend_Db_Table_Select $select
     * @return Zend_Db_Table_Select
     */
    protected function selectProgrammkategorie($programmkategorieId, Zend_Db_Table_Select $select)
    {
        $whereProgrammkategorieId = new Zend_Db_Expr("programmkategorie_id = ".$programmkategorieId);

        $select->where($whereProgrammkategorieId);
        $this->select = $select;
        $query = $select->__toString();

        return $select;
    }

    /**
     * Select Objekt um Suchbegriff erweitern
     *
     * @param $suchbegriff
     * @param Zend_Db_Table_Select $select
     * @return Zend_Db_Table_Select
     */
    protected function selectSuchbegriff($suchbegriff,Zend_Db_Table_Select $select)
    {
        $whereProgname = new Zend_Db_Expr("progname like '%".$suchbegriff."%'");
        $select->where($whereProgname);
        $query = $select->__toString();

        return $select;
    }

    /**
     * Erstellt das Select Objekt der View Programmsuche
     *
     * @param Zend_Db_Table_Abstract $viewProgrammsuche
     * @return Zend_Db_Table_Select
     */
    protected function erstellenSelect(Zend_Db_Table_Abstract $viewProgrammsuche)
    {
        $select = $viewProgrammsuche->select();
        $this->select = $select;

        return $select;
    }

    /**
     * @return int
     */
    public function getAnzahlProgramme()
    {
        return $this->anzahlProgramme;
    }

    /**
     * @return array
     */
    public function getSeiten()
    {
        return $this->seiten;
    }

    /**
     * Paginator erste Seite
     *
     * @param $paginator
     * @param $anzahlMoeglicheSeiten
     * @return array
     */
    protected function ersteSeite($paginator, $anzahlMoeglicheSeiten)
    {
        // zurueck Button
        $paginatorElemente = array(
            'seite' => 1,
            'module' => $this->condition_module,
            'controller' => $this->condition_controller,
            'action' => $this->condition_action,
            'cityId' => $this->cityId,
            'class' => 'passivStep',
            'anzeige' => '<'
        );

        $paginator[] = $paginatorElemente;

        // aktive Button
        $paginatorElemente = array(
            'seite' => 1,
            'module' => $this->condition_module,
            'controller' => $this->condition_controller,
            'action' => $this->condition_action,
            'cityId' => $this->cityId,
            'class' => 'aktiveStep',
            'anzeige' => 1
        );

        $paginator[] = $paginatorElemente;

        if ($anzahlMoeglicheSeiten >= 2) {
            $paginatorElemente = array(
                'seite' => 2,
                'module' => $this->condition_module,
                'controller' => $this->condition_controller,
                'action' => $this->condition_action,
                'cityId' => $this->cityId,
                'class' => 'passivStep',
                'anzeige' => 2
            );

            $paginator[] = $paginatorElemente;
        } else {
            $paginatorElemente = array(
                'seite' => 2,
                'module' => $this->condition_module,
                'controller' => $this->condition_controller,
                'action' => $this->condition_action,
                'cityId' => $this->cityId,
                'class' => 'passivStep',
                'anzeige' => ''
            );

            $paginator[] = $paginatorElemente;
        }

        if ($anzahlMoeglicheSeiten >= 3) {
            $paginatorElemente = array(
                'seite' => 3,
                'module' => $this->condition_module,
                'controller' => $this->condition_controller,
                'action' => $this->condition_action,
                'cityId' => $this->cityId,
                'class' => 'passivStep',
                'anzeige' => 3
            );

            $paginator[] = $paginatorElemente;
        } else {
            $paginatorElemente = array(
                'seite' => 3,
                'module' => $this->condition_module,
                'controller' => $this->condition_controller,
                'action' => $this->condition_action,
                'cityId' => $this->cityId,
                'class' => 'passivStep',
                'anzeige' => ''
            );

            $paginator[] = $paginatorElemente;
        }

        if ($anzahlMoeglicheSeiten >= 4) {
            $paginatorElemente = array(
                'seite' => 2,
                'module' => $this->condition_module,
                'controller' => $this->condition_controller,
                'action' => $this->condition_action,
                'cityId' => $this->cityId,
                'class' => 'passivStep',
                'anzeige' => '>'
            );

            $paginator[] = $paginatorElemente;
            return $paginator;
        } else {
            if($anzahlMoeglicheSeiten == 1){
                $paginatorElemente = array(
                    'seite' => 1,
                    'module' => $this->condition_module,
                    'controller' => $this->condition_controller,
                    'action' => $this->condition_action,
                    'cityId' => $this->cityId,
                    'class' => 'passivStep',
                    'anzeige' => '>'
                );

                $paginator[] = $paginatorElemente;
            }
            elseif($anzahlMoeglicheSeiten == 2){
                $paginatorElemente = array(
                    'seite' => 2,
                    'module' => $this->condition_module,
                    'controller' => $this->condition_controller,
                    'action' => $this->condition_action,
                    'cityId' => $this->cityId,
                    'class' => 'passivStep',
                    'anzeige' => '>'
                );

                $paginator[] = $paginatorElemente;
            }
            elseif($anzahlMoeglicheSeiten == 3){
                $paginatorElemente = array(
                    'seite' => 3,
                    'module' => $this->condition_module,
                    'controller' => $this->condition_controller,
                    'action' => $this->condition_action,
                    'cityId' => $this->cityId,
                    'class' => 'passivStep',
                    'anzeige' => '>'
                );

                $paginator[] = $paginatorElemente;
            }

        }

        return $paginator;
    }

    /**
     * aktiver Button in der Mitte
     *
     * @param $startSeite
     * @param $paginator
     * @param $anzahlMoeglicheSeiten
     * @return array
     */
    protected function naechsteSeite($startSeite, $paginator, $anzahlMoeglicheSeiten)
    {
        // zurueck Button
        $paginatorElemente = array(
            'seite' => $startSeite - 1,
            'module' => $this->condition_module,
            'controller' => $this->condition_controller,
            'action' => $this->condition_action,
            'cityId' => $this->cityId,
            'class' => 'passivStep',
            'anzeige' => '<'
        );

        // passiver Button
        $paginator[] = $paginatorElemente;

        $paginatorElemente = array(
            'seite' => $startSeite - 1,
            'module' => $this->condition_module,
            'controller' => $this->condition_controller,
            'action' => $this->condition_action,
            'cityId' => $this->cityId,
            'class' => 'passivStep',
            'anzeige' => $startSeite - 1
        );

        $paginator[] = $paginatorElemente;

        // aktive Button
        $paginatorElemente = array(
            'seite' => $startSeite,
            'module' => $this->condition_module,
            'controller' => $this->condition_controller,
            'action' => $this->condition_action,
            'cityId' => $this->cityId,
            'class' => 'aktiveStep',
            'anzeige' => $startSeite
        );

        $paginator[] = $paginatorElemente;

        // passiver oder Leer Button
        if( ($startSeite + 1) <= $anzahlMoeglicheSeiten){
            $paginatorElemente = array(
                'seite' => $startSeite + 1,
                'module' => $this->condition_module,
                'controller' => $this->condition_controller,
                'action' => $this->condition_action,
                'cityId' => $this->cityId,
                'class' => 'passivStep',
                'anzeige' => $startSeite + 1
            );

            $paginator[] = $paginatorElemente;
        }
        else{
            $paginatorElemente = array(
                'seite' => $startSeite + 1,
                'module' => $this->condition_module,
                'controller' => $this->condition_controller,
                'action' => $this->condition_action,
                'cityId' => $this->cityId,
                'class' => 'passivStep',
                'anzeige' => ''
            );

            $paginator[] = $paginatorElemente;
        }

        // weiter oder Leer Button
        if( ($startSeite + 1) >= $anzahlMoeglicheSeiten){
            $paginatorElemente = array(
                'seite' => $startSeite,
                'module' => $this->condition_module,
                'controller' => $this->condition_controller,
                'action' => $this->condition_action,
                'cityId' => $this->cityId,
                'class' => 'passivStep',
                'anzeige' => '>'
            );

            $paginator[] = $paginatorElemente;
        }
        else{
            $paginatorElemente = array(
                'seite' => $startSeite + 1,
                'module' => $this->condition_module,
                'controller' => $this->condition_controller,
                'action' => $this->condition_action,
                'cityId' => $this->cityId,
                'class' => 'passivStep',
                'anzeige' => '>'
            );

            $paginator[] = $paginatorElemente;
        }

        return $paginator;
    }
}

/*******************/
//include_once("../../../../autoload_cts.php");
//
//$myClass = new Front_Model_ProgrammeSeitenSuchen();
//
//$anzahlProgramme = $myClass
//    ->setProgrammeProSeite(10)
//    ->setCityId(1)
//    ->setSpracheId(1)
//    ->setAnzahlSichtbareSeiten(3)
//    // ->setSuchbegriff('seen')
//    ->steuerungErmittlungAnzahlProgramme()
//    ->getAnzahlProgramme();
//
//$seiten = $myClass->getSeiten();