<?php
class Front_Model_Zusatzartikel extends nook_Model_model{
	private $_db;
	
	public function __construct(){
		$this->_db = Zend_Registry::get('front');
		
		return;
	}
	
	public function getCountPrograms(){
		$sql = "select count(Fa_Id) as anzahl from tbl_programmbeschreibung";
		$anzahl = $this->_db->fetchOne($sql);
		
		return $anzahl;
	}
	
	public function getTableItems($start = false, $limit = false){
		$start = 0;
		$limit = 20;	
		
		if(array_key_exists('limit', $_POST)){
			$start = $_POST['start'];
			$limit = $_POST['limit'];
		}
	
		$sql = "select progname, Eintrittspreise, Fa_Id, Öffnungszeiten from tbl_programmbeschreibung limit ".$start.", ".$limit;
		$result = $this->_db->fetchAll($sql);
		
		$ausgabe = array();
		for($i=0; $i<count($result); $i++){
			$ausgabe[$i]['progname'] = $result[$i]["progname"];
			$ausgabe[$i]['eintrittspreise'] = $result[$i]["Eintrittspreise"];
			$ausgabe[$i]['FaId'] = $result[$i]["Fa_Id"];
			$ausgabe[$i]['oeffnungszeiten'] = $result[$i]["Öffnungszeiten"];
		}
	
		return $ausgabe;		
	}
	
}