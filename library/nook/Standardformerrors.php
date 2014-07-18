<?php
class nook_Standardformerrors{

    public function __construct($fieldErrors){
        $errorMessages = array();

        $i=0;
        foreach($fieldErrors as $field => $error){

            $this->errorMessages[$i]['id'] = $field;

            $errorMessagesGerman = $this->_buildgermanErrorMessages();

            if(array_key_exists($error[0], $errorMessagesGerman)){
                $this->errorMessages[$i]['msg'] = $errorMessagesGerman[$error[0]];
            }
            else
                $this->errorMessages[$i]['msg'] = $error[0];

            $i++;
        }

        return;
    }

    private function _buildgermanErrorMessages(){

        $errorMessagesGerman = array(
            'emailAddressInvalidFormat' => 'fehlerhafte Mailadresse',
            'notAlpha' => 'nur Buchstaben'
        );

        return $errorMessagesGerman;
    }

    public function getErrorMessages(){
        return $this->errorMessages;
    }
}