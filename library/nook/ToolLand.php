<?php

/**
 * Konvertiert Landesnamen in  Landes ID nach
 * 'tbl_country'
 *
 * Konvertiert Landes ID in Landesname nach
 * 'tbl_country'
 *
 * @author Stephan.Krauss
 * @date 17.01.13
 * @file ToolLand.php
 */

class nook_ToolLand{

    // Konditionen
    private $_laender = array(
        '52' => 'Deutschland',
        '232' => 'Tschechien',
        '198' => 'Schweiz',
        '174' => 'Österreich'
    );

    // Fehler
    private $_error_kein_laendername = 1160;
    private $_error_keine_laender_id = 1161;

    /**
     * Ermittelt die ID eines Landes
     *
     * @param $__landName
     * @return bool|int|string
     * @throws nook_Exception
     */
    public function convertLaenderNameNachLaenderId($__landName){
        $ermittelterLandName = false;

        $landName = (int) $__landName;

        // Land Name schon vorhanden
        if($landName == 0)
            return $__landName;

        if(empty($landName))
            throw new nook_Exception($this->_error_kein_laendername);

        foreach($this->_laender as $key => $value){
            if($key == $landName){
                $ermittelterLandName = $value;

                break;
            }
        }

        return $ermittelterLandName;
    }

    /**
     * Ermittelt aus einer Länder ID
     * den Landesnamen
     *
     * @param $__landId
     * @return bool
     * @throws nook_Exception
     */
    public function convertLaenderIdNachLandName($__landId){

        $landName = false;
        $__landId = (int) $__landId;

        if(empty($__landId) or !is_int($__landId) )
            throw new nook_Exception($this->_error_keine_laender_id);

        foreach($this->_laender as $key => $value){

            if($__landId == $key){
                $landName = $value;

                break;
            }

        }

        return $landName;
    }


}