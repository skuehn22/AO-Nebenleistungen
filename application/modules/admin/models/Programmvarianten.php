<?php
class Admin_Model_Programmvarianten extends nook_Model_model{

    // Subklassen
    private $_dependency = array();
	
	// private $_error = 490;

    public function setDependency($__name, $__depObject){
        $this->_dependency[$__name] = $__depObject;

        return $this;
    }

    public function setFormData($__formData){
        $this->_dependency['form']
            ->checkData($__formData)
            ->savePreisvariante();

        return $this;
    }

    public function getPreisvarianten($__start, $__limit){
        $preisvarianten = $this->_dependency['grid']
            ->setStartLimit($__start, $__limit)
            ->getPreisvarianten();

        return $preisvarianten;
    }

    public function getAnzahlDatensaetze(){
        $anzahl = $this->_dependency['grid']->getAnzahlDatensaetze();

        return $anzahl;
    }

    public function getFormData($__loadId){
        $datenPreisVariante = $this->_dependency['form']
            ->setId($__loadId)
            ->getData();

        return $datenPreisVariante;
    }

    public function updatePreisvariante($__datenProgrammVariante){
        $id = $__datenProgrammVariante['id'];
        unset($__datenProgrammVariante['id']);

        $this->_dependency['form']
            ->setId($id)
            ->checkData($__datenProgrammVariante)
            ->updatePreisvariante();

        return;
    }

    public function loeschenPreisvariante($__deleteId){
        $this->_dependency['grid']
            ->setId($__deleteId)
            ->deletePreisvariante();

        return;
    }

}