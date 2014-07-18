<?php

class Admin_AvailablecategoriesController extends Zend_Controller_Action
{

    private $_realParams = array();

   public function init(){
       $request = $this->getRequest();
       $this->realParams = $request->getParams();
   }

    public function indexAction()
    {
        $request = $this->getRequest();
        $params = $request->getParams();

        try {
            $raintpl = raintpl_rainhelp::getRainTpl();

            $this->view->content = $raintpl->draw("Admin_Availablecategories_Index", true);
        }
       catch (Exception $e) {
            $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams);
            echo "{success: false, message: 'Serverfehler !<br>Bitte Logdatei kontrollieren'}";
        }
    }

    public function getcategoriesAction()
    {
        $request = $this->getRequest();
        $params = $request->getParams();

        try {
            $this->_helper->viewRenderer->setNoRender();
            $this->_helper->layout->disableLayout();

            $model = new Admin_Model_Availablecategories();
            $anzahl = $model->getCountCategories();

            $start = false;
            $limit = false;

            if (array_key_exists('limit', $params)) {
                $start = $params['start'];
                $limit = $params['limit'];
            }

            $ausgabe = $model->getCategories($start, $limit);

            echo "{success: true, data: " . json_encode($ausgabe) . ", anzahl: " . $anzahl . "}";

        }
       catch (Exception $e) {
            $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams);
            echo "{success: false, message: 'Serverfehler !<br>Bitte Logdatei kontrollieren'}";
        }
    }
}

