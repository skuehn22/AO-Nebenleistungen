<?php
class nook_Model_model{
	public $_groupsDatabase;
    protected $_eingabedaten = array();
    protected $_error_data_not_valid = 10000;
    protected $_error_data_variable_not_exist = 10001;

    protected $_objekte = array();

    /***************************************************/

    public function __construct(){
		$this->_groupsDatabase = Zend_Registry::get('front');

		return;
	}

	public function __destruct(){


		return;
	}

    public function add(ClassOperationInterface $singleObjekt) {
        $klassenName = get_class($singleObjekt);
        $this->objekte[$klassenName] = $singleObjekt;
    }

    public function execute($__klassenName, $__methodenName, $__params = false) {
        if(is_array($__params))
            $this->objekte[$__klassenName]->_eingabedaten = $__params;

        return $this->objekte[$__klassenName]->execute($__methodenName);
    }


    /****************************************************/


    /**
    * $__klassenNamen = array();
    * $__klassenNamen[] = 'libraryClass1';
    * $__klassenNamen[] = 'libraryClass2';
    */
    public function mergeObjects(array $__klassenNamen){
        foreach($__klassenNamen as $einzelklasse){
            if(!array_key_exists($einzelklasse, $this->_objekte)){
                $klassenPfad = "nook/".$einzelklasse.".php";
                $klasse = "nook_".$einzelklasse;

               include_once($klassenPfad);

                $objekt = new $klasse();
                $objekt->_db_hotels = $this->_db_hotel;
                $objekt->_db_groups = $this->_db_groups;
                $objekt->_eingabedaten = $this->_eingabedaten;

                $this->_objekte[$einzelklasse] = $objekt;
            }
        }
    }


     protected function _buildObjects($__classes){
        foreach($__classes as $key){
            if(!array_key_exists($key, $this->_objects)){
                include_once('nook/'.$key.'.php');
                $this->_objects[$key] = new $key();
            }
        }

        return;
    }

    public function setPropertyId(Array $__data){
        $this->_eingabedaten = $__data;

        return $this;
    }

    /**
    * $__mapping = array();
    * $__mapping[0]['ist'] = 'alter Name';
    * $__mapping[0]['soll'] = 'neuer Name';
    */
    public function mappingData(Array $__mapping){
        for($i=0; $i<count($__mapping); $i++){
            if(!array_key_exists($__mapping[$i]['ist'], $this->_eingabedaten))
                throw new nook_Exception($this->_error_data_variable_not_exist);

            $value = $this->_eingabedaten[$__mapping[$i]['ist']];
            unset($this->_eingabedaten[$__mapping[$i]['ist']]);
            $this->_eingabedaten[$__mapping[$i]['soll']] = $value;
        }

        return;
    }


	
	protected function _removeNull(Array $__data){
		$data = array();
		
		foreach ($__data as $key => $value){
			if(empty($value))
				$value = '';
				
			$data[$key] = $value;
		}
		
		return $data;
	}
	
}