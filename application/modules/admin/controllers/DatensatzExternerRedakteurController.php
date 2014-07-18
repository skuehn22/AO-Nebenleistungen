<?php
/**
 * Userstory der Klasse
 *
 * + Beschreibung 1
 * + Beschreibung 2
 * + Beschreibung 3
 *
 * @author Stephan Krauss
 * @date 26.05.2014
 * @package admin
 * @subpackage controller
 */
class Admin_DatensatzExternerRedakteurController extends Zend_Controller_Action implements nook_ToolCrudController
{

    private $_condition_insert_zusatzinformation = 1;

    private $_realParams = array();

    public function init(){
        $request = $this->getRequest();
        $this->realParams = $request->getParams();
    }

    /**
     * Lädt das Formular und
     * trägt den Kommentar zum Programm ein.
     *
     */
    public function viewAction(){
        $request = $this->getRequest();
        $params = $request->getParams();

        try {
            $this->_helper->viewRenderer->setNoRender();
            $this->_helper->layout->disableLayout();

            if(empty($params['programmdetails_id']))
                throw new nook_Exception('Programm ID fehlt');

            $model = new Admin_Model_DatensatzExternerRedakteur();
            $zusatzinformation = $model
                ->setProgrammId($params['programmdetails_id'])
                ->getZusatzinformationEinesProgrammes();

            echo "{success: true, data: " . json_encode($zusatzinformation) . "}";
        }
       catch (Exception $e) {
            $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams);
            echo "{success: false, message: 'Serverfehler !<br>Bitte Logdatei kontrollieren'}";
        }
    }


    /**
     * Eintragen und Update der Formularwerte
     */
    public function editAction(){
        $request = $this->getRequest();
        $params = $request->getParams();

        try {
            $this->_helper->viewRenderer->setNoRender();
            $this->_helper->layout->disableLayout();

            if(empty($params['programmdetails_id']))
                throw new nook_Exception('Programm ID fehlt');

            $model = new Admin_Model_DatensatzExternerRedakteur();
            $params = $model->checkSubmit($params);
            $model->setProgrammId($params['programmdetails_id']);

            // insert Zusatzinformation
            if($params['status'] == $this->_condition_insert_zusatzinformation)
                $model->insertZusatzinformation($params);
            // update Zusatzinformation
            else
                $model->updateZusatzinformation($params);

            echo "{success: true}";
        }
       catch (Exception $e) {
            $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams);
            echo "{success: false, message: 'Serverfehler !<br>Bitte Logdatei kontrollieren'}";
        }
    }

    public function deleteAction(){}

    public function indexAction(){}

}

