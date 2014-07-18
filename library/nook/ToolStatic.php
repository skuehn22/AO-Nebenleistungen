<?php
class nook_ToolStatic{

    /**
     * Holt die statischen Werte aus der Datei
     * application/configs/static.ini
     *
     * @static
     * @return $werte Array
     */
    public static function getStaticWerte(){

        $static = Zend_Registry::get('static');
        $werte = $static->toArray();

        return $werte;
    }

    /**
     * Holt den 'salt' Wert aus /application/configs/static.ini. Salzen eines Passwortes
     *
     * @static
     * @return
     */
    public static function getSalt(){
        $staticWerte = self::getStaticWerte();
        $salt = $staticWerte['geheim']['salt'];

        return $salt;
    }

    /**
     * kontrolliert das Passwort in der Tabelle 'tbl_adressen'
     * mit dem eingegebenen Passwort
     *
     * @static
     * @param $__passwort
     * @param $__kundenId
     * @return bool
     */
    public static function checkPasswort($__passwort, $__kundenId){
        $check = false;

        $table = new Application_Model_DbTable_adressen(array('db' => 'front'));
        $select = $table
                ->select()
                ->from(array('kunde' => 'tbl_adressen'),array('password'))
                ->where('id = '.$__kundenId);
        
        $rows = $table->fetchRow($select);
        $gespeichertePasswort = $rows->toArray();

        // Passwortveränderung
        $salt = self::getSalt();
        $gerechnetePasswort = md5($__passwort.$salt);

        // Kontrolle Passwort
        if($gerechnetePasswort === $gespeichertePasswort['password'])
            $check = true;

        return $check;
    }

    /**
     * berechnet den md5 eines Passwortes. Salzen eines Passwortes
     * verwendet den 'salt' Wert
     *
     * @static
     * @param $__passwort
     * @return
     */
    public static function berechnePasswort($__passwort){

        $salt = self::getSalt();
        $saltPasswort = md5($__passwort.$salt);

        return $saltPasswort;
    }



}