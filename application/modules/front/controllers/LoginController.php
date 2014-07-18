<?php
/**
* Startseite der Homepage, allgemeine Werbeangebote, Anmeldung der Benutzer, Navigation
*
* + Erststart des System
*
* @date 04.56.2013
* @file LoginController.php
* @package front
* @subpackage controller
*/
class Front_LoginController extends Zend_Controller_Action{

    private $_realParams = null;
    private $requestUrl = null;
    protected $raintpl = null;

    public function init(){
        $frontModelServiceAustria = new Front_Model_ServiceTemplate();
        $service = $frontModelServiceAustria->getServiceTemplate();
        $raintpl = raintpl_rainhelp::getRainTpl();
        $raintpl->assign('service', $service);
        $this->raintpl = $raintpl;

        $request = $this->getRequest();
        $this->realParams = $request->getParams();
        $this->requestUrl = $this->view->url();
    }

    /**
     * Erststart des System
     *
     * + Kontrolle auf Mail und Passwort
     * + Fehlerdarstellung
     * + verändern der Session wenn Logout
     * + normale Darstellung
     *
     * @return void
     */
    public function indexAction(){
    	$request = $this->getRequest();
		$params = $request->getParams();
        $raintpl = $this->raintpl;

        try{
            $portalbereich = new Zend_Session_Namespace('portalbereich');
            $portalbereich->bereich = 'programme';

            // Error Handling
            if(array_key_exists('error', $params)){
                $raintpl->assign('errorMessage', $params['error']);
                Zend_Session::regenerateId();

                $modelLogout = new Front_Model_Logout();
                $modelLogout->abmelden();
                $raintpl->assign('flagSlogan', false);
            }
            // Login
            elseif(array_key_exists('login', $params)){
                $userName = trim($params['username']);
                $password = trim($params['password']);

                if(empty($userName) or empty($password)){
                    $modelLogout = new Front_Model_Logout();
                    $modelLogout->abmelden();
                    $raintpl->assign('flagSlogan', true);
                }
            }
            // Logout, verändern der Session
            elseif(array_key_exists('logout', $params)){
                $modelLogout = new Front_Model_Logout();
                $modelLogout->abmelden();
                $raintpl->assign('flagSlogan', true);
            }
            // normale Darstellung
            else{
                $modelLogin = new Front_Model_Login();
                $zusatzinformationenStaedte = $modelLogin->getZusatzinformationStaedte();

                $raintpl->assign('zusatzinformationenStaedte', $zusatzinformationenStaedte);

                // Bereich Slogan
                $raintpl->assign('flagSlogan',true);
            }

        	$this->view->content = $raintpl->draw( "Front_Login_Index", true );
        }
        catch (Exception $e){
            $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams, $this->requestUrl);
            $this->_redirect(Zend_Registry::get('static')->home->system->out."/error/".$e);
        }
    }

    public function viewAction(){}


    public function saveHotelsearchAction()
    {
        $request = $this->getRequest();
        $params = $request->getParams();

        try{
            // Tage Differenz
            $toolDatumDifferenzTage = new nook_ToolDatumDifferenzTage();
            $tageDifferenz = $toolDatumDifferenzTage
                ->setStartDatum($params['from'])
                ->setEndDatum($params['to'])
                ->steuerungErmittlungDifferenzTage()
                ->getTageDifferenz();

            // speichern Suchwerte Hotel in Namespace 'hotelsuche'
            $sessionNamespaceHotelsuche = new Zend_Session_Namespace('hotelsuche');
            $sessionNamespaceHotelsuche->city = $params['city'];
            $sessionNamespaceHotelsuche->from = $params['from'];
            $sessionNamespaceHotelsuche->days = $tageDifferenz;
            $sessionNamespaceHotelsuche->adult = $params['adult'];


            // Startparameter
            $startParams = array(
                'city' => $params['city'],
                'days' => $tageDifferenz,
                'from' => $params['from'],
                'adult' => $params['adult']
            );

            // umlenken zu anderen Controller
            $this->_forward('index','hotellist',null, $startParams);

            // $this->_redirect('/front/hotellist/index/');
        }
        catch(Exception $e){
            $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams, $this->requestUrl);
            $this->_redirect(Zend_Registry::get('static')->home->system->out."/error/".$e);
        }
    }
}
