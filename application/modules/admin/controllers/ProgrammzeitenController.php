<?php
/**
 * Der Administrator kann die Programmzeiten eines Programmes anlegen und löschen.
 *
 * @author Stephan.Krauss
 * @date 13.36.2013
 * @file ProgrammzeitenController.php
 * @package admin
 * @subpackage controller
 */
class Admin_ProgrammzeitenController extends Zend_Controller_Action
{
    // Fehler

    // Konditionen

    // Flags


    protected $realParams = array();
    protected $pimple = null;

    public function init()
    {
        $request = $this->getRequest();
        $this->realParams = $request->getParams();
    }

    public function erststartAction()
    {
        $params = $this->realParams;

        try {
            $this->_helper->viewRenderer->setNoRender();
            $this->_helper->layout->disableLayout();



            // echo "{success: true, data: " . json_encode($result) . ", anzahl: " . $anzahl . "}";
        }
        catch (Exception $e) {
            $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams);
            echo "{success: false, message: 'Serverfehler !<br>Bitte Logdatei kontrollieren'}";
        }

    }

}