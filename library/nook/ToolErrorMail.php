<?php
/**
 * 06.08.12 09:22
 * Fehlerbereich:
 * Versendet ein Mail im Falle einer Exception
 *
 * @author Stephan Krauß
 */

class nook_ToolErrorMail {

    private $_mailAdresseHandy = null;
    private $_mailAdresseTicket = null;

    private $_from = null;
    private $_mailSenden = null;

    // Konditionen
    private $_condition_mail_senden = 2; // Mail wird versandt

    /**
     * erkennt ob ein Mail versandt werden soll
     * speichert Mailadresse
     * übernimmt from:
     *
     */
    public function __construct(){

        $static = Zend_Registry::get('static')->toArray();

        $this->_mailAdresseHandy = $static['debugModus']['mailAdresseHandy'];
        $this->_mailAdresseTicket = $static['debugModus']['mailAdresseTicket'];
        $this->_from = 'info@stephankrauss.de';
        $this->_mailSenden = $static['debugModus']['mail'];
    }

    /**
     * senden Fehler Mail
     *
     * @param $__errorMessage
     */
    public function sendeMail(array $error){

        if($this->_condition_mail_senden != $this->_mailSenden)
            return;

        // versand Fehlermail
        $this->_sendeMail($error);

        return $this;
    }

    /**
     * sendet Mail
     *
     * @return nook_ToolErrorMail
     */
    private function _sendeMail(array $error){

        $bodyText = "'Ein Fehler ist aufgetreten! Fehlernummer: ".$error['idFehlermeldung']."\n\n";

        foreach($error as $key => $value){
            $bodyText .= ucfirst($key).": ".$value."\n";
        }

        $path_parts = pathinfo($error['file']);
        $subjekt = 'Fehler: '.$error['idFehlermeldung'].', '.$path_parts['filename'].', Linie: '.$error['line'];

        $mail = new Zend_Mail('utf8');

        $mail
            ->addTo($this->_mailAdresseHandy, 'mobile Benachrichtigung')
            ->addTo($this->_mailAdresseTicket, 'Ticket Flow')
            ->setBodyText($bodyText)
            ->setSubject($subjekt)
            ->setFrom('suppenterrine@gmail.com')
            ->send();

        return $this;
    }


} // end class
