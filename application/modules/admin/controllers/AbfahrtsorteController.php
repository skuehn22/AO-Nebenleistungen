<?php

class Admin_AbfahrtsorteController extends Zend_Controller_Action
{
    private $_realParams = array();

    public function init(){
        $request = $this->getRequest();
        $this->realParams = $request->getParams();

       $this->session = new Zend_Session_Namespace('warenkorb');

       $request = $this->getRequest();
       $this->realParams = $request->getParams();
    }


    private function _getPimple(array $classes){
        // Klassen für Model
//        $classes = array(
//            'stuff' => array(
//                'path' => 'Own_Stuff',
//                'options' => array(
//                    'wert1' => 'wert1',
//                    'wert2' => 'wert2'
//                )
//            ),
//            'mapperFirma' => array(
//                'path' => 'Admin_Model_IndexMapperFirma',
//                'options' => array(
//                    'DbTable' => 'Admin_Model_DbTable_Firma'
//                )
//            )
//        );

//        $classes = array();
//        $pimple = new Pimple_Pimple();
          $pimple = new Pimple_Test();

//        foreach($classes as $className => $classElements){
//            if(isset($classElements['options']))
//                $object = new $classes[$className]['path']($classes[$className]['options']);
//            else
//                $object = new $classes[$className]['path']();
//
//            $pimple->offsetSet($className, $object);
//        }

        // return $pimple;

        return;
    }

    public function indexAction(){
        $request = $this->getRequest();
        $params = $request->getParams();

        try {
            $raintpl = raintpl_rainhelp::getRainTpl();

            $raintpl->assign('rolle', 10);
            $raintpl->assign('showCloseButton','true');


            $this->view->content = $raintpl->draw("Admin_Abfahrtsorte_Index", true);
        }
       catch (Exception $e) {
            $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams);
            echo "{success: false, message: 'Serverfehler !<br>Bitte Logdatei kontrollieren'}";
       }
    }

    public function vorhandenertreffpunktAction(){
        $request = $this->getRequest();
        $params = $request->getParams();

        try{
            $this->_helper->viewRenderer->setNoRender();
            $this->_helper->layout->disableLayout();

            $model = new Admin_Model_Abfahrtsorte();
            $params = $model->map($params);


            $model->checkVorhandenerTreffpunkt($params);

            // Klassen für Model
            $classes = array();
            $pimple = $this->_getPimple($classes);
            $model->pimple = $pimple;

            $treffpunktDaten = $model->getVorhandeneDaten($params['programmId']);

            echo "{success: true, data: ".json_encode($treffpunktDaten)."}";
        }
        catch (Exception $e) {
            $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams);
            echo "{success: false, message: 'Serverfehler !<br>Bitte Logdatei kontrollieren'}";
       }
    }

    public function settreffpunktAction(){
        $request = $this->getRequest();
        $params = $request->getParams();

        try {
            $this->_helper->viewRenderer->setNoRender();
            $this->_helper->layout->disableLayout();

            $model = new Admin_Model_Abfahrtsorte();
            $params = $model->map($params);
            $model->checkInsertTreffpunkt($params);

            // Klassen für Model
//            $classes = array();
//            $pimple = $this->_getPimple($classes);
//            $model->pimple = $pimple;

            $model->setTreffpunkte($params);

            echo "{success: true, msg: 'stimmt so'}";

        }
        catch(Exception $e){
             $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams);
             echo "{success: false, message: 'Serverfehler !<br>Bitte Logdatei kontrollieren'}";
        }
    }

    public function gettreffpunktstartAction(){
        $request = $this->getRequest();
        $params = $request->getParams();

        try {
            $this->_helper->viewRenderer->setNoRender();
            $this->_helper->layout->disableLayout();

            $model = new Admin_Model_Abfahrtsorte();
            $params = $model->map($params);
            $model->checkQueryStore($params['query']);


            // Klassen für Model
            $classes = array();

            $pimple = $this->_getPimple($classes);
            $model->pimple = $pimple;

            if(!array_key_exists('limit', $params)){
                $params['start'] = 0;
                $params['limit'] = 10;
            }

            $treffpunkte = $model->getTreffpunkte( $params['query'], $params['start'], $params['limit']);

            echo "{success: true, data: ".json_encode($treffpunkte['data']).", anzahl: ".$treffpunkte['anzahl']."}";
        }
        catch(Exception $e){
             $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams);
             echo "{success: false, message: 'Serverfehler !<br>Bitte Logdatei kontrollieren'}";
        }
    }

     public function getprogrammzeitenAction(){
        $request = $this->getRequest();
        $params = $request->getParams();

        try {
            $this->_helper->viewRenderer->setNoRender();
            $this->_helper->layout->disableLayout();

            $model = new Admin_Model_Abfahrtsorte();
            $params = $model->map($params);

            // Klassen für Model
            $classes = array();
            $pimple = $this->_getPimple($classes);
            $model->pimple = $pimple;

            $zeiten = $model->getProgrammZeiten($params['programmId']);

            echo "{success: true, data: ".json_encode($zeiten)."}";
        }
        catch(Exception $e){
             $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams);
             echo "{success: false, message: 'Serverfehler !<br>Bitte Logdatei kontrollieren'}";
        }
    }

     public function deletezeitenAction(){
        $request = $this->getRequest();
        $params = $request->getParams();

        try {
            $this->_helper->viewRenderer->setNoRender();
            $this->_helper->layout->disableLayout();

            $model = new Admin_Model_Abfahrtsorte();
            $params = $model->map($params);

            // Klassen für Model
            $classes = array();
            $pimple = $this->_getPimple($classes);
            $model->pimple = $pimple;

            $zeiten = $model->removeProgrammZeit($params['id']);

            // echo "{success: true, data: ".json_encode($zeiten)."}";
        }
        catch(Exception $e){
             $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams);
             echo "{success: false, message: 'Serverfehler !<br>Bitte Logdatei kontrollieren'}";
        }
    }

    public function removeprogrammzeitenAction(){
        $request = $this->getRequest();
        $params = $request->getParams();

        try {
            $this->_helper->viewRenderer->setNoRender();
            $this->_helper->layout->disableLayout();

            $model = new Admin_Model_Abfahrtsorte();
            $params = $model->map($params);

            // Klassen für Model
            $classes = array();
            $pimple = $this->_getPimple($classes);
            $model->pimple = $pimple;
            $treffpunkte = $model->removeProgrammZeit($params['id']);

            // echo "{success: true, data: ".json_encode($treffpunkte)."}";
        }
        catch(Exception $e){
             $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams);
             echo "{success: false, message: 'Serverfehler !<br>Bitte Logdatei kontrollieren'}";
        }
    }

     public function setneuezeitAction(){
        $request = $this->getRequest();
        $params = $request->getParams();

        try {
            $this->_helper->viewRenderer->setNoRender();
            $this->_helper->layout->disableLayout();

            $model = new Admin_Model_Abfahrtsorte();
            $params = $model->map($params);

            // checken der Zeiten
            $model->checkInsertZeiten($params);

            // Klassen für Model
            $classes = array();
            $pimple = $this->_getPimple($classes);
            $model->pimple = $pimple;

            // eintragen Abfahrts / Ankunftszeit
            $model->setProgrammZeit($params);


            echo "{success: true}";
        }
        catch(Exception $e){
             $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams);
             echo "{success: false, message: 'Serverfehler !<br>Bitte Logdatei kontrollieren'}";
        }
    }
}

