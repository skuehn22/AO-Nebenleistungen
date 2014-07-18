<?php
/**
 * Verwaltet die Übersetzung Translate der Template. Tool zur Organisation der Übersetzungen
 *
 * @author Stephan.Krauss
 * @date 26.05.2013
 * @file Translate.php
 * @package admin
 * @subpackage model
 */
class Admin_Model_Translate extends nook_Model_model{

    // Tabellen / Views / Datenbanken
	private $_db;
    private $_tabelleTranslate = null;

    // Errors
	public $error_test = 400;

    // Konditionen

    // Flags

    protected $_sucheBaustein = null;
    protected $_sucheBegriff = null;
    protected $translateId = null;
    protected $_deleteId = array();


	public function __construct(){
		$this->_db = Zend_Registry::get('front');
        /** @var _tabelleTranslate Application_Model_DbTable_translate */
        $this->_tabelleTranslate = new Application_Model_DbTable_translate();

		return;
	}

    /**
     * Ermittelt die Anzahl der Übersetzungen
     *
     * @return mixed
     */
    public function getCountTranslate(){

        $sql = "select count(id) as anzahl from tbl_translate";

        $sql = $this->_filterBegriffe($sql);

        // filtern Begriffe
        $anzahl = $this->_db->fetchOne($sql);

        return $anzahl;
    }

    /**
     * Liefert die Übersetzungen für Anzeifetabelle.
     *
     * @param $__start
     * @param $__limit
     * @return mixed
     */
    public function getTranslate($__start, $__limit){
        $start = 0;
        $limit = 20;

        if(!empty($__start)){
            $start = $__start;
            $limit = $__limit;
        }

        $sql = "
            SELECT
            `id`
            , `platzhalter`
            , `de`
            , `eng`
            , `module`
            , `controller`
            FROM
            `tbl_translate`";

        // filtern Begriffe
        $sql = $this->_filterBegriffe($sql);

        $sql .= " ORDER BY `module`, `controller` LIMIT ".$start.", ".$limit;

        $uebersetzungen = $this->_db->fetchAll($sql);

        return $uebersetzungen;
    }

    /**
     * Schränkt die Suche nach den Übersetzungen Translate ein
     *
     * @param $sql
     * @return string
     */
    private function _filterBegriffe($sql){

        if( (!empty($this->_sucheBaustein)) or (!empty($this->_sucheBegriff)) or (!empty($this->translateId))){
            $sql .= " where ";

            if(!empty($this->_sucheBaustein))
                $sql .= " controller = '".$this->_sucheBaustein."' and";

            if(!empty($this->_sucheBegriff))
                $sql .= " platzhalter like '%".$this->_sucheBegriff."%' and";

            if(!empty($this->translateId))
                $sql .= " id = ".$this->translateId." and";

            $sql = substr($sql, 0, -4);
        }

        return $sql;
    }


    /**
     * @param $__bausteinName
     * @return Admin_Model_Translate
     */
    public function setSucheBaustein($__bausteinName)
    {
        $this->_sucheBaustein = $__bausteinName;

        return $this;
    }

    /**
     * @param $__begriff
     * @return Admin_Model_Translate
     */
    public function setSucheBegriff($__begriff)
    {
        $this->_sucheBegriff = $__begriff;

        return $this;
    }

    /**
     * @param $translateId
     * @return Admin_Model_Translate
     */
    public function setTranslateId($translateId)
    {
        $this->translateId = $translateId;

        return $this;
    }

    /**
     * Trägt englische Übersetzung in Tabelle 'tbl_translate' ein.
     *
     * @param $__params
     * @return mixed
     */
    public function setUebersetzung($__params)
    {
        $update = array();
        $update['eng'] = $__params['eng'];
        $update['de'] = $__params['de'];

        $where = array();
        $where[] = "id = '".$__params['id']."'";

        $kontrolle = $this->_db->update('tbl_translate', $update, $where);

        return $kontrolle;
    }

    /**
     * @param $__deleteIdString
     * @return Admin_Model_Translate
     */
    public function setDeleteId($__deleteIdString){
        $this->_buildDeleteArray($__deleteIdString);

        return $this;
    }

    /**
     * Ermittelt die zu löschenden Id's der Translate
     *
     * @param $__deleteIdString
     */
    private function _buildDeleteArray($__deleteIdString){

        $__deleteIdString = trim($__deleteIdString);
        $deleteArray = explode(',',$__deleteIdString);

        for($i=0; $i < count($deleteArray); $i++){
            if(empty($deleteArray[$i]))
                continue;

            $this->_deleteId[] = $deleteArray[$i];
        }

        return;
    }

    /**
     * Löscht die vorgemerkten Übersetzungen
     *
     * @return Admin_Model_Translate
     */
    public function loescheTranslate(){

        if(count($this->_deleteId) > 0){
            for($i=0; $i < count($this->_deleteId); $i++){
                $delete = "id = ".$this->_deleteId[$i];
                $this->_tabelleTranslate->delete($delete);
            }

        }

        return $this;
    }
}