<?php
/**
 * Dem Offlinebucher wird das Formular der Suche in der Adressdatenbank dargestellt.
 * Er kann einen Benutzer zur Offlinebuchung auswählen.
 *
 * @author Stephan.Krauss
 * @date 16.10.2013
 * @file OfflinebuchungController.php
 * @package front
 * @subpackage controller
 */
class Front_OfflinebuchungController extends  Zend_Controller_Action{

    protected $requestUrl = null;
    protected $realParams = null;

    protected $raintpl = null;

    public function init(){
        $request = $this->getRequest();
        $this->realParams = $request->getParams();
        $this->requestUrl = $this->view->url();

        // Rain TPL
        $this->raintpl = raintpl_rainhelp::getRainTpl();

        // Kontrolle Zugriff
        $zugriff = array(
            'readAction' => 9,
            'suchen-benutzerAction' => 9,
            'uebernahme-benutzerAction' => 9
        );

        $toolZugriffController = new nook_ToolZugriffController();
        $toolZugriffController
            ->setZugriffAction($zugriff)
            ->setActionName($this->realParams['action'])
            ->steuerungKontrolleZugriffAction();
    }

    public function createAction(){}

    /**
     * stelt das Suchformular Benutzersuche dar
     */
    public function readAction(){
        $params = $this->realParams;
        $raintpl = $this->raintpl;

        try{
            $template = $raintpl->draw( "Front_Offlinebuchung_Read", true );
            $this->view->content = $template;
        }
        catch (Exception $e){
            $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams, $this->requestUrl);
            $this->_redirect(Zend_Registry::get('static')->home->system->out."/error/".$e);
        }
    }

    public function updateAction(){}

    public function deleteAction(){}

    /**
     * Sucht die Benutzer in der Tabelle 'tbl_adressen'
     */
    public function suchenBenutzerAction()
    {
        $params = $this->realParams;
        unset($params['module']);
        unset($params['controller']);
        unset($params['action']);
        unset($params['submitSuchen']);

        foreach($params as $key => $value){
            if(empty($value))
                unset($params[$key]);
        }

        try{
            if(count($params) > 0){
                $pimple = new Pimple_Pimple();
                $pimple['suchParameter'] = $params;

                $pimple['viewFirmen'] = function(){
                    return new Application_Model_DbTable_viewFirmen();
                };

                $pimple['tabelleBuchungsnummer'] = function(){
                    return new Application_Model_DbTable_buchungsnummer();
                };

                $frontModelOfflinebuchung = new Front_Model_Offlinebuchung();
                $benutzer = $frontModelOfflinebuchung
                    ->setPimple($pimple)
                    ->steuerungErmittlungBenutzer()
                    ->getBenutzer();

                if((is_array($benutzer)) and (count($benutzer) > 0)){
                    $this->raintpl->assign('benutzer', $benutzer);
                }

                $this->raintpl->assign('personalData', $params);
            }

            $this->readAction();
        }
        catch(Exception $e){
            $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams, $this->requestUrl);
            $this->_redirect(Zend_Registry::get('static')->home->system->out."/error/".$e);
        }

    }

    /**
     * Übernimmt den Benutzer als Offlinebucher
     */
    public function uebernahmeBenutzerAction()
    {
        $params = $this->realParams;

        try{
            $pimple = new Pimple_Pimple();

            $auth = new Zend_Session_Namespace('Auth');

            $pimple['offlineBucherId'] = $auth->userId;
            $pimple['kundenId'] = $params['id'];
            $pimple['tabelleBuchungsnummer'] = function(){
                return new Application_Model_DbTable_buchungsnummer();
            };
            $pimple['tabelleAdressenSuperuser'] = function(){
                return new Application_Model_DbTable_adressenSuperuser();
            };
            $pimple['tabelleAdressen'] = function(){
                return new Application_Model_DbTable_adressen();
            };

            $frontModelOfflinebucherUebernahmeBenutzer = new Front_Model_UebernahmeBenutzer();
            $frontModelOfflinebucherUebernahmeBenutzer
                ->setPimple($pimple)
                ->steuerungUmschreibenBenutzer();


            $this->_redirect('/front/login/');
        }
        catch(Exception $e){
            $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams, $this->requestUrl);
            $this->_redirect(Zend_Registry::get('static')->home->system->out."/error/".$e);
        }



    }
}
