<?php

class Admin_zusatztexteFirmaController extends Zend_Controller_Action implements nook_ToolCrudController{

    private $_realParams = array();

    public function init()
    {
        $request = $this->getRequest();
        $this->realParams = $request->getParams();
    }

    /**
     * Ermittelt Zusatztete einer Firma
     */
    public function viewAction()
    {
        $request = $this->getRequest();
        $params = $request->getParams();

        try {
            $this->_helper->viewRenderer->setNoRender();
            $this->_helper->layout->disableLayout();

            $model = new Admin_Model_zusatztexteFirma();
            $zusatzTexteFirma = $model
                ->setFirmenId($params['id'])
                ->getZusatztexte();

            echo "{success: true, data: ".json_encode($zusatzTexteFirma)."}";

        }
       catch (Exception $e) {
            $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams);
            echo "{success: false, message: 'Serverfehler !<br>Bitte Logdatei kontrollieren'}";
        }
    }

    /**
     * speichert die neuen Zusatztexte
     * einer Firma
     */
    public function editAction()
    {
       $request = $this->getRequest();
       $params = $request->getParams();

       try {
           $this->_helper->viewRenderer->setNoRender();
           $this->_helper->layout->disableLayout();

           $model = new Admin_Model_zusatztexteFirma();
           $kontrolle = $model
               ->setFirmenId($params['companyId'])
               ->editZusatztexte($params);

           if($kontrolle)
               echo "{success: true}";
       }
      catch (Exception $e) {
           $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams);
           echo "{success: false, message: 'Serverfehler !<br>Bitte Logdatei kontrollieren'}";
       }
    }

    public function indexAction(){}

    public function deleteAction(){}

}

