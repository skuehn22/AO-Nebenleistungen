<?php
/**
 * Der Redakteur, Administrator kann den Anzeigemodus der Übersetzungsansicht in den Views der Bausteine im Front - Bereich ändern
 *
 * @author Stephan.Krauss
 * @date 18.17.2013
 * @file TranslatesingleController.php
 * @package admin
 * @subpackage controller
 */
class Admin_TranslatesingleController extends Zend_Controller_Action implements nook_ToolCrudController
{
    // Flags

    protected $realParams = array();
    protected $pimple = null;

    public function init(){
        $request = $this->getRequest();
        $this->realParams = $request->getParams();

        $this->pimple = new Pimple_Pimple();
    }

    public function indexAction(){}

    /**
     * Wechselt den Anzeigemodus der Übersetzungsansicht in den Views
     */
    public function editAction(){
        try {
            $this->_helper->viewRenderer->setNoRender();
            $this->_helper->layout->disableLayout();

            $toolUebersetzungsModus = new nook_ToolUebersetzungsModus();
            $toolUebersetzungsModus->switchUebersetzungsmodus();

            echo "{success: true}";
        }
        catch (Exception $e) {
            $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams);
            Zend_Session::destroy();
            $this->_redirect(Zend_Registry::get('static')->home->system->out . "/error/" . $e->getMessage());
                }
    }

    public function deleteAction(){}
}

