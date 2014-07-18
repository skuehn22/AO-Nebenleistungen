<?php

class library1 implements ClassOperationInterface
{
    public $_eingabedaten = array();

    public $_db_hotel;
    public $_db_groups;

    public function __construct(){
        $this->_db_groups = Zend_Registry::get('front');
        $this->_db_hotel = Zend_Registry::get('hotels');
    }

    public function execute($__methodenName)
    {
        return $this->$__methodenName();
    }

     public function setData(array $__params)
    {
        $this->_eingabedaten = $__params;
    }

    private function getTest()
    {
        return "Methode 'test'";
    }

    private function getTestParam(){
        return "Methode getTestParam: ".$this->_eingabedaten['wert'];
    }
}

?>
 
