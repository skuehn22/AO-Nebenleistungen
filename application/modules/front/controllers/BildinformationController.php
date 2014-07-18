<?php
/**
 * Liefert Zusatzinformationen zu einem anzuzeigenden Bild
 *
 * + Cpyright
 * + Bildbeschreibung
 *
 * @author Stephan.Krauss
 * @date 06.58.2014
 * @file BildinformationController.php
 * @package front
 * @subpackage controller
 */
class Front_BildinformationController extends  Zend_Controller_Action{

    private $realParams = null;
    private $requestUrl = null;

    public function init(){
        $request = $this->getRequest();
        $this->realParams = $request->getParams();

        $this->requestUrl = $this->view->url();
    }

    /**
     * Gibt die Bildbeschreibung eines Bildes zurück
     */
    public function bildbeschreibungAction(){
        $params = $this->realParams;

        try{
            $this->_helper->viewRenderer->setNoRender();
            $this->_helper->layout->disableLayout();

        }
        catch (Exception $e){
            $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams, $this->requestUrl);
            $this->_redirect(Zend_Registry::get('static')->home->system->out."/error/".$e);
        }
    }

    /**
     * Gibt die Copyright Information eines Bildes zurück
     */
    public function copyrightAction(){
        $params = $this->realParams;

        try{
            $this->_helper->viewRenderer->setNoRender();
            $this->_helper->layout->disableLayout();

        }
        catch (Exception $e){
            $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams, $this->requestUrl);
            $this->_redirect(Zend_Registry::get('static')->home->system->out."/error/".$e);
        }
    }

    /**
     * Gibt Bildbeschreibung und Copyright eines Bildes zurück
     */
    public function bildbeschreibungCopyrightAction(){
        $params = $this->realParams;

        try{
            $this->_helper->viewRenderer->setNoRender();
            $this->_helper->layout->disableLayout();

            $tabelleBilder = new Application_Model_DbTable_bilder();

            $frontModelBildinformation = new Front_Model_Bildinformation();
            $frontModelBildinformation
                ->setBildId($params['bildId'])
                ->setBildTyp($params['bildTypId'])
                ->setTabelleBilder($tabelleBilder)
                ->steuerungErmittlungBildinformation();

            $bildinformation = array(
                'bildname' => $frontModelBildinformation->getBildname(),
                'copyright' => $frontModelBildinformation->getCopyright()
            );

            echo json_encode($bildinformation);
        }
        catch (Exception $e){
            $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams, $this->requestUrl);
            $this->_redirect(Zend_Registry::get('static')->home->system->out."/error/".$e);
        }
    }
}