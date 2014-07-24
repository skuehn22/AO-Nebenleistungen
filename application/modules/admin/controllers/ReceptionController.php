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

            $test = $_FILES['miniBild2']['name'];

            // Bild
            if (!empty($_FILES['miniBild']['name'])) {
                break;
                $kontrolle = $this->_uploadImage($params);
            } else {
                $kontrolle = true;
            }

            echo "{success: true}";
        }
        catch(Exception $e){
            $e = nook_ExceptionRegistration::registerException($e, 1, $this->realParams);
            echo "{success: false, message: 'Serverfehler !<br>Bitte Logdatei kontrollieren'}";
        }
    }

    private function _uploadImage($params)
    {
        try {
            if (!empty($_FILES['miniBild2'])) {
                $image = $_FILES['miniBild2'];
                $imageName = $params['programmId'];
                $imagePath = ABSOLUTE_PATH . "/images/program/midi/";

                $uploadImage = nook_upload::getInstance();
                $kontrolleImageTyp = $uploadImage->setImage($image)->setImagePath($imagePath)->setImageName(
                    $imageName
                )->checkImageTyp();
                if ($kontrolleImageTyp) {
                    $kontrolleMove = $uploadImage->moveImage();
                    if ($kontrolleMove) {
                        return true;
                    } else {
                        return false;
                    }
                } else {
                    return false;
                }
            }

            return true;
        } catch (Exception $e) {
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

            $response = array(
                'informationRezeption' => $receptionZusatzinformation
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

