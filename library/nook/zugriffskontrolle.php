<?php

class nook_zugriffskontrolle{

    private $_condition_insert = 'insert';
    private $_condition_update = 'update';
    private $_condition_user_ist_anbieter = 5;

    private $_typZugriff;

    public function setTypZugriff($__typZugriff){
        $this->_typZugriff = $__typZugriff;

        return $this;
    }

    public function getKontrollVariablen($__params){
        $auth = new Zend_Session_Namespace('Auth');
        $userId = $auth->userId;

        if($__typZugriff == $this->_condition_update){
            $__params['geändertAm'] = date("Y-m-d H:i:s", time());
            $__params['geändertDurch'] = $userId;
        }
        elseif($__typZugriff == $this->_condition_insert){
            $__params['angelegtDurch'] = $userId;
        }
        
        return $__params;
    }

      // Kontrolle der Zugriffsrechte auf ein Hotel oder Programm
//    public function getZugriffsrecht($__id, $__typ = 'hotel'){
//        $kontrolle = false;
//
//        $auth = new Zend_Session_Namespace('Auth');
//        // wenn user nicht Anbieter
//        if($auth->role_id > $this->_condition_user_ist_anbieter){
//            return true;
//        }
//
//        // Kontrolle auf Hotel
//        if($__typ == 'hotel'){
//            $this->_kontrolleZugriffHotel($__id);
//
//            $kontrolle = true;
//        }
//
//        // Kontrolle auf Programm
//        if($__typ == 'program'){
//            $this->_kontrolleZugriffProgramm($__id);
//
//            $kontrolle = true;
//        }
//
//        return $kontrolle;
//
//    }

//    private function _kontrolleZugriffHotel($__idHotel){
//
//        return;
//    }
//
//    private function _kontrolleZugriffProgramm($__idProgramm){
//
//        return;
//    }
}
