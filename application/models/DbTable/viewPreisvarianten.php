<?php
/**
 * 20.09.2012
 * View zur Darstellung der Preisvarianten eines Produktes
 * Es wird entsprechend der Anzeigesprache
 * verschiedene Views verwendet.
 *
 * @author Stephan Krauß
 */
 
class Application_Model_DbTable_viewPreisvarianten extends Zend_Db_Table_Abstract{

    protected $_primary = 'id';
    private $_kennzifferSprache = null;

    private $_condition_deutsche_sprache = 1;
    private $_condition_englische_sprache = 2;

    /**
     * Setz die View entsprechend der Kennziffer
     * der Anzeigesprache
     *
     */
    protected function _setupTableName(){

        // automatisches ermitteln der Anzeigesprache
        $kennzifferSprache = nook_ToolSprache::ermittelnKennzifferSprache();
        $this->_kennzifferSprache = $kennzifferSprache;

        if($kennzifferSprache == $this->_condition_deutsche_sprache)
            $this->_name = 'view_preisvarianten_de';
        if($kennzifferSprache == $this->_condition_englische_sprache)
            $this->_name = 'view_preisvarianten_en';

        parent::_setupTableName();
    }

} // end class
