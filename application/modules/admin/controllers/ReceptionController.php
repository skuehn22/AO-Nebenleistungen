<?php
/**
 * Der Benutzer kann die Zusatzinformationen der Rezeption handeln.
 *
 * @author Stephan Krauss
 * @date 25.02.14
 * @package admin
 * @subpackage controller
 */
class Admin_ReceptionController extends Zend_Controller_Action
{
    private $realParams = array();

    public function init(){
        $request = $this->getRequest();
        $this->realParams = $request->getParams();
    }

    /**
     * Schreibt die Zusatzangaben eines Programmes für den Rezeptionisten in 'tbl_reception'
     */
    public function updateAction(){
        $params = $this->realParams;

        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        try{
            $adminModelReception = new Admin_Model_Reception();
            $adminModelReception
                ->setProgrammId($params['programmId'])
                ->setZusatzinformationReception($params['informationRezeption'])
                ->steuerungReception();

            echo "{success: true}";
        }
        catch(Exception $e){
            $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams);
            echo "{success: false, message: 'Serverfehler !<br>Bitte Logdatei kontrollieren'}";
        }
    }



    /**
     * Holt die Zusatzangaben eines Programmes für den Rezeptionisten aus 'tbl_reception'
     */
    public function readAction(){
        $params = $this->realParams;
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        try{
            $adminModelReception = new Admin_Model_Reception();
            $receptionZusatzinformation = $adminModelReception
                ->setProgrammId($params['programmdetailsId'])
                ->steuerungReception()
                ->getZusatzinformationReception();

                $readFiles = nook_upload::getInstance();
                $files = $readFiles->readFiles($params['programmdetailsId']);

            $response = array(
                'informationRezeption' => $receptionZusatzinformation,
                'zusatzdokumente' => $files
            );


            echo "{success: true, data: ".json_encode($response)."}";
        }
        catch(Exception $e){
            $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams);
            echo "{success: false, message: 'Serverfehler !<br>Bitte Logdatei kontrollieren'}";
        }
    }

    public function createAction(){}
    public function writeAction(){}
    public function deleteAction(){}
}

