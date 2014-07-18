<?php
/**
 * Darstellung von
 * Stamminformationen im Whiteboard
 *
 * + Darstellung der offenen Kommentare der Programme
 *
 *
 * @author Stephan Krauß
 */

class Admin_WhiteboardController extends Zend_Controller_Action
{
    private $_realParams = null;

    public function init()
    {
        $request = $this->getRequest();
        $this->realParams = $request->getParams();
    }

    /**
     * Darstellung des Templat
     * Anzeige der Rolle sowie persönlicher Daten
     */
    public function indexAction()
    {
        $request = $this->getRequest();
        $params = $request->getParams();

        try {
            $raintpl = raintpl_rainhelp::getRainTpl();

            $model = new Admin_Model_Whiteboard();

            // Rolle im System als Text
            $rolle = $model->getRolleImSystem();
            $raintpl->assign('rolle', $rolle);

            // Personendaten
            $personendaten = $model->getpersonenDaten();
            $raintpl->assign('personendaten', $personendaten);

            // Darstellung der Rolle
            $rolleId = $model->getRolleId();
            $raintpl->assign('rolleId', $rolleId);

            $this->view->content = $raintpl->draw("Admin_Whiteboard_Index", true);
        }
       catch (Exception $e) {
            $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams);
            echo "{success: false, message: 'Serverfehler !<br>Bitte Logdatei kontrollieren'}";
        }
    }

    /**
     * Stellt die offene Fragen / Kommentare
     * der Programme dar.
     */
    public function offenekommentareAction(){
        $request = $this->getRequest();
        $params = $request->getParams();

        try{
            $this->_helper->viewRenderer->setNoRender();
            $this->_helper->layout->disableLayout();

            $model = new Admin_Model_whiteboardFragen();
            $offeneKommentare = $model->getOffeneKommentare($params);

            echo "{success: true, data: ".json_encode($offeneKommentare['data']).", anzahl: ".$offeneKommentare['anzahl']."}";
        }
        catch(Exception $e){
            $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams);
            echo "{success: false, message: 'Serverfehler !<br>Bitte Logdatei kontrollieren'}";
        }
    }

    /**
     * Löscht einzelne
     * offene Fragen
     *
     */
    public function kommentarerledigtAction(){
        $request = $this->getRequest();
        $params = $request->getParams();

        try{
            $this->_helper->viewRenderer->setNoRender();
            $this->_helper->layout->disableLayout();

            $model = new Admin_Model_whiteboardFragen();
            $model->offeneKommentarStatusaenderung($params['programmId']);

            echo "{success: true}";
        }
        catch(Exception $e){
            $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams);
            echo "{success: false, message: 'Serverfehler !<br>Bitte Logdatei kontrollieren'}";
        }
    }
}

