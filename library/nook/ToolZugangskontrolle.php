<?php
/**
 * Sichten und eintragen der vorhandenen
 * Action der Controller
 *
 * Reflection Class zum eintragen
 * der Resourcen der Controller - Action.
 * Schreibt Errors der Model.
 * Schreibt Beschreibung der Model.
 *
 * @date 01.02.13 08:41
 * @author Stephan Krauß
 */

class nook_ToolZugangskontrolle {

     // Error
    private $_error_zu_viele_datensaetze = 1320;

    // Tabellen / Views
    private $_tabelleZugangskontrolle = null;

    // Konditionen
    private $_condition_anzahl_datensaetze_zugangskontrolle = 1;
    private $_condition_kein_datensatz_zugangskontrolle_vorhanden = 0;

    // Flags


    protected  $arrModules = array();
    protected  $arrControllers = array();
    protected  $arrActions = array();
    protected  $arrIgnore = array('.','..','.svn','DbTable');
    protected  $arrModels = array();

    public function __construct(){

        /** @var _tabelleZugangskontrolle Application_Model_DbTable_zugangskontrolle */
        $this->_tabelleZugangskontrolle = new Application_Model_DbTable_zugangskontrolle();
    }


    public function buildModulesArray() {

        $path = APPLICATION_PATH . '\modules';
        $dstApplicationModules = opendir($path);

        while ( ($dstFile = readdir($dstApplicationModules) ) !== false ) {
            if( ! in_array($dstFile, $this->arrIgnore) ) {
                if( is_dir(APPLICATION_PATH . '/modules/' . $dstFile) ){
                    $this->arrModules[] = $dstFile;
                }
            }
        }

        closedir($dstApplicationModules);

        return $this;
    }




    public function buildControllerArrays() {
        if( count($this->arrModules) > 0 ) {
            foreach( $this->arrModules as $strModuleName ) {

                $datControllerFolder = opendir(APPLICATION_PATH . '/modules/' . $strModuleName . '/controllers' );

                while ( ($dstFile = readdir($datControllerFolder) ) !== false ) {
                    if( ! in_array($dstFile, $this->arrIgnore)) {
                        if( preg_match( '/Controller/', $dstFile) ){
                            $this->arrControllers[$strModuleName][] = strtolower( substr( $dstFile,0,-14 ) );
                        }
                    }
                }
                closedir($datControllerFolder);
            }
        }

        return $this;
    }

    public function buildActionArrays() {

        if( count($this->arrControllers) > 0 ) {
            foreach( $this->arrControllers as $strModule => $arrController ) {
                foreach( $arrController as $strController ) {
                    $strClassName = ucfirst( $strModule ).'_'.ucfirst( $strController . 'Controller' );

                    if($strClassName == 'Admin_ZugangskontrolleController')
                        continue;

                    Zend_Loader::loadFile(APPLICATION_PATH . '/modules/'.$strModule.'/controllers/'.ucfirst( $strController ).'Controller.php');

                    $objReflection = new Zend_Reflection_Class( $strClassName );
                    $arrMethods = $objReflection->getMethods();

                    foreach( $arrMethods as $objMethods ) {
                        if( preg_match( '/Action/', $objMethods->name ) ) {
                            $this->arrActions[$strModule][$strController][] = substr($objMethods->name,0,-6 );
                        }
                    }
                }
            }
        }

        return $this;
    }

    public function datensatzZugangskontrolle(){

        foreach($this->arrActions as $moduleName => $arrController){
            foreach($arrController as $controllerName => $arrActions){
                for($i=0; $i< count($arrActions); $i++){
                   $insert = array(
                       'module' => $moduleName,
                       'controller' => $controllerName,
                       'action' => $arrActions[$i]
                   );

                   $this->_eintragenDatensatz($insert);
                }
            }
        }

        return $this;
    }

    private function _eintragenDatensatz(array $__datensatz){

        $select = $this->_tabelleZugangskontrolle->select();
        $select
            ->where("module = '".$__datensatz['module']."'")
            ->where("controller = '".$__datensatz['controller']."'")
            ->where("action = '".$__datensatz['action']."'");

        $rows = $this->_tabelleZugangskontrolle->fetchAll($select)->toArray();

        if(count($rows) > $this->_condition_anzahl_datensaetze_zugangskontrolle)
            throw new nook_Exception($this->_error_zu_viele_datensaetze);
        elseif(count($rows) == $this->_condition_kein_datensatz_zugangskontrolle_vorhanden){
            $this->_tabelleZugangskontrolle->insert($__datensatz);
        }

        return $this;
    }



} // end class