<?php
/**
 * Controller zur Verwaltung durchlaufender preisposten
 *
 * + Ausführliche Beschreibung der Klasse
 * + Ausführliche Beschreibung der Klasse
 * + Ausführliche Beschreibung der Klasse
 *
 * @author Stephan.Krauss
 * @date 23.14.2013
 * @file PreispostenController.php
 * @package admin
 * @subpackage controller
 */

class Admin_PreispostenController extends Zend_Controller_Action
{

    // Flags

    protected $pimple = null;
    protected $realParams = array();

    public function init(){
        $request = $this->getRequest();
        $this->realParams = $request->getParams();

        // DIC
        $this->pimple = $this->getInvokeArg('bootstrap')->getResource('Container');
    }

    /**
     * Übergibt die möglichen Varianten des durchlaufenden Preisposten
     */
    public function indexAction()
    {
        try {
            $this->_helper->viewRenderer->setNoRender();
            $this->_helper->layout->disableLayout();

            $variantenPreisposten = $this->ermittelnvariantenPreisposten();
            $anzahl = count($variantenPreisposten);

            echo "{succes: true, data: ".json_encode($variantenPreisposten).", totalProperty: ".$anzahl."}";
        }
       catch (Exception $e) {
            $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams);
            echo "{success: false, message: 'Serverfehler !<br>Bitte Logdatei kontrollieren'}";
        }
    }

    /**
     * Ermittelt die vorhandenen Preisposten
     *
     * @return array
     */
    private function ermittelnvariantenPreisposten()
    {
        $this->pimple['tabelleRechnungenDurchlaeufer'] = function($c){
            return new Application_Model_DbTable_rechnungenDurchlaeufer();
        };

        $modelPreispostenDurchlaeufer = new Admin_Model_PreispostenDurchlaeufer();
        $variantenPreisposten = $modelPreispostenDurchlaeufer
            ->setPimple($this->pimple)
            ->steuerungErmittlungDurchlaufendePreisposten()
            ->getDurchlaufendePreisposten();

        return $variantenPreisposten;
    }

}

