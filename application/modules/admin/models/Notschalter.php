<?php
/**
 * Methoden für den Notschalter der Programm und Hotelbuchungen
 *
 * + Kontrolliert den Servicecontainer
 * + Steuert die Ermittlung der Notschalter
 * + Ermittelt den Zustand der Notschalter des System
 * + Mappen Status der Notschalter
 * + Steuert das Update der Notschalter
 * + Update der Notschalter des System
 *
 * @date 22.10.2013
 * @file Notschalter.php
 * @package admin
 * @subpackage model
 */
class Admin_Model_Notschalter extends nook_Model_model
{
    // Fehler
    private $error_anfangswerte_fehlen = 1970;

    // Konditionen
    private $condition_notschalter_aktiv = 2;
    private $condition_notschalter_passiv = 1;

    // Flags

    /** @var $pimple Pimple_Pimple */
    protected $pimple = null;
    protected $notschalter = array();

    public function __construct()
    {

    }

    /**
     * @param Pimple_Pimple $pimple
     * @return Admin_Model_Notschalter
     */
    public function setPimple(Pimple_Pimple $pimple)
    {
        $this->pimple = $pimple;

        return $this;
    }

    /**
     * Kontrolliert den Servicecontainer
     *
     * + wenn notwendig Pimple erstellen
     * + Kontrolle / Erstellen Zugriff auf Tabellen
     *
     */
    private function kontrolleServicecontainer()
    {
        if(empty($this->pimple))
            $this->pimple = new Pimple_Pimple();

        if (!$this->pimple->offsetExists('tabelleSystemparameter')) {
            $this->pimple['tabelleSystemparameter'] = function () {
                return new Application_Model_DbTable_systemparameter();
            };
        }
    }

    /**
     * Steuert die Ermittlung der Notschalter
     *
     * @return Admin_Model_Notschalter
     */
    public function steuerungErmittlungNotschalter()
    {
        $this->kontrolleServicecontainer();
        $this->ermittelnNotschalterDesSystem();

        return $this;
    }

    /**
     * Ermittelt den Zustand der Notschalter des System
     *
     * + Schalter ist aktiv = true
     * + Schalter ist passiv = false
     */
    private function ermittelnNotschalterDesSystem()
    {
        /** @var $tabelleSystemparameter Application_Model_DbTable_systemparameter */
        $tabelleSystemparameter = $this->pimple['tabelleSystemparameter'];
        $systemparameter = $tabelleSystemparameter->fetchAll()->toArray();

        $notschalter = array();

        foreach ($systemparameter as $key => $value) {
            if ($value['parametername'] == 'programmbuchung') {
                if ($value['wert'] == 1) {
                    $notschalter['programmbuchung'] = false;
                } else {
                    $notschalter['programmbuchung'] = true;
                }
            }

            if ($value['parametername'] == 'hotelbuchung') {
                if ($value['wert'] == 1) {
                    $notschalter['hotelbuchung'] = false;
                } else {
                    $notschalter['hotelbuchung'] = true;
                }
            }
        }

        $this->notschalter = $notschalter;

        return;
    }

    /**
     * @return array
     */
    public function getNotschalter()
    {
        return $this->notschalter;
    }

    /**
     * Mappen Status der Notschalter
     *
     * @param array $notschalter
     * @return Admin_Model_Notschalter
     */
    public function setNotschalter(array $notschalter)
    {
        if (array_key_exists('programmbuchung', $notschalter)) {
            $notschalter['programmbuchung'] = $this->condition_notschalter_aktiv;
        } else {
            $notschalter['programmbuchung'] = $this->condition_notschalter_passiv;
        }

        if (array_key_exists('hotelbuchung', $notschalter)) {
            $notschalter['hotelbuchung'] = $this->condition_notschalter_aktiv;
        } else {
            $notschalter['hotelbuchung'] = $this->condition_notschalter_passiv;
        }

        $this->notschalter = $notschalter;

        return $this;
    }

    /**
     * Steuert das Update der Notschalter
     *
     * @return $this
     * @throws nook_Exception
     */
    public function steuerungUpdateNotschalter()
    {
        $this->kontrolleServicecontainer();

        if (empty($this->notschalter)) {
            throw new nook_Exception($this->error_anfangswerte_fehlen);
        }

        $this->updateNotschalter();

        return $this;
    }

    /**
     * Update der Notschalter des System
     */
    private function updateNotschalter()
    {
        /** @var $tabelleSystemparameter Application_Model_DbTable_systemparameter */
        $tabelleSystemparameter = $this->pimple['tabelleSystemparameter'];

        foreach ($this->notschalter as $key => $value) {
            $update = array(
                'wert' => $value
            );

            $tabelleSystemparameter->update($update, "parametername = '" . $key . "'");
        }

        return;
    }
}