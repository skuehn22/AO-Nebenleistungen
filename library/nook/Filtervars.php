<?php

class nook_FilterVars {

    private $_parameter = array();
    private $_filterAnweisungen = array();
    private $_errors = array();
    public $countErrors = 0;
    private $_filterTyp;
    private $_variablenName;
    private $_options = array();

    public function setData(Array $parameter, Array $filterAnweisungen){
        $this->_parameter = $parameter;
        $this->_filterAnweisungen = $filterAnweisungen;

        return $this;
    }

    public function getKontrollergebnis(){
        $errors = $this->_kontrolleEingabewerte();

        return;
    }

    public function getErrors(){
        return $this->_errors;
    }

    private function _kontrolleEingabewerte(){

        foreach($this->_parameter as $variablenname => $variableninhalt){

            if(array_key_exists($variablenname, $this->_filterAnweisungen)){

                $this->_filterTyp = $this->_filterAnweisungen[$variablenname][0];
                $this->_variablenName = $variablenname;

                if(!empty($this->_filterAnweisungen[$variablenname]['options']) and is_array($this->_filterAnweisungen[$variablenname]['options']))
                    $this->_options['options'] = $this->_filterAnweisungen[$variablenname]['options'];

                $this->_setFilter();
            }
        }

        return $this;
    }

    private function _setFilter(){

        if($this->_filterTyp == 'int')
            $this->_validateInt();
        elseif($this->_filterTyp == 'validate_email')
            $this->_validateEmail();
        elseif($this->_filterTyp == 'float')
            $this->_validateFloat();
        elseif($this->_filterTyp == 'boolean')
            $this->_validateBoolean();
        elseif($this->_filterTyp == 'validate_url')
            $this->_validateUrl();
        elseif($this->_filterTyp == 'validate_ip')
            $this->_validateIp();
        elseif($this->_filterTyp == 'validate_regexp')
            $this->_validateRegexp();

        return;
    }

    private function _validateInt(){
        if(!filter_var($this->_parameter[$this->_variablenName], FILTER_VALIDATE_INT, $this->_options))
            $this->_setError('Das ist keine Ganzzahl');

        return;
    }

    private function _validateEmail(){
        if(!filter_var($this->_parameter[$this->_variablenName], FILTER_VALIDATE_EMAIL))
            $this->_setError('Das ist keine Mailadresse');

        return;
    }

     private function _validateFloat(){
        if(!filter_var($this->_parameter[$this->_variablenName], FILTER_VALIDATE_FLOAT, $this->_options))
            $this->_setError('Das ist keine Kommazahl');

        return;
    }

    private function _validateBoolean(){

        $inhalt = $this->_parameter[$this->_variablenName];
        if(!is_bool($inhalt))
            $this->_setError('Das ist kein Boolean');

        return;
    }

    private function _validateUrl(){
        if(!filter_var($this->_parameter[$this->_variablenName], FILTER_VALIDATE_URL))
            $this->_setError('Das ist keine URL');

        return;
    }

    private function _validateIp(){
        if(!filter_var($this->_parameter[$this->_variablenName], FILTER_VALIDATE_IP))
            $this->_setError('Das ist keine IP');

        return;
    }

    private function _validateRegexp(){
        if(!filter_var($this->_parameter[$this->_variablenName], FILTER_VALIDATE_REGEXP, $this->_options))
            $this->_setError('Das ist keine RegExp');

        return;
    }




    private function _setError($fehlermeldung){
        $fehlermeldung = $this->_translate($fehlermeldung);

        $this->_errors[] = array(
            'id' => $this->_variablenName,
            'msg' => $fehlermeldung
        );

        $this->countErrors++;

        return;
    }

    private function _translate($fehlermeldung){

        return $fehlermeldung;
    }

    public function __call($method, $args){
        echo 'Methode: '.$method;
        var_dump($args);

        throw new Exception('Filtermethode nicht vorhanden');
    }

} // end class

?>