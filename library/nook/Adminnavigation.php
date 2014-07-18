<?php

/**
 * Steuert die Zuordnung der Bausteine im Administrationsbereich zu den angemeldeten Rollen
 *
 * @author Stephan Krauss
 * @date 25.02.14
 * @package tools
 */
class nook_Adminnavigation{

    // Bereiche der Bausteine
	protected $condition_bereich_allgemein = 0;
    protected $condition_bereich_programme = 1;
    protected $condition_bereich_uebernachtung = 6;


    // Rollen
    protected $rolle_anbieter = 5;
    protected $rolle_offlinebucher = 9;
    protected $rolle_administrator = 10;

    // vorhandene Bausteine im Adminsystem
	private $vorhandeneBausteine;

    // Auth - Werte des Benutzer
    private $bereichAnbieter;
    private $rolleId;


	public function __construct(){
        $auth = new Zend_Session_Namespace('Auth');
        $this->bereichAnbieter = $auth->anbieter; // Anbieter - Bereich
        $this->rolleId = $auth->role_id; // Rolle des Benutzer

        // Rolle mindestens Redakteur
        if($this->rolleId < $this->rolle_anbieter){
            try{
                throw new nook_Exception('Rolle hat unzureichende Rechte');
            }
            catch(Exception $e){
                $e->kundenId = nook_ToolKundendaten::findKundenId();
                nook_ExceptionRegistration::buildAndRegisterErrorInfos($e,2);
                Zend_Session::destroy();
                exit;
            }
        }

        // bestimmen der Bausteine des Admin - Bereich
        $validBlocks = $this->bestimmenBausteineDerRolle();

        // mindestens ein Baustein muss für die Rolle freigeschaltet sein
        if(count($validBlocks) == 0){
            try{
                throw new nook_Exception('Keine Programme für diese Rolle vorhanden');
            }
            catch(Exception $e){
                $e->kundenId = nook_ToolKundendaten::findKundenId();
                nook_ExceptionRegistration::buildAndRegisterErrorInfos($e,2);
                Zend_Session::destroy();
                exit;
            }

        }

        // vorhandene Bausteine
        $this->vorhandeneBausteine = $validBlocks;
	}

    /**
     * Ermitteln der Bausteine des Adminsystem die ab Rolle = 5 verwendet werden können
     *
     * @return array
     */
    protected function bestimmenBausteineDerRolle()
    {
        $cols = array(
            'module',
            'module',
            'name',
            'bereich',
            'role_id',
            'controller'
        );

        $tabelleBlocks = new Application_Model_DbTable_blocks();
        $select = $tabelleBlocks->select();
        $select
            ->from($tabelleBlocks, $cols)
            ->where("module = 'admin'")
            ->where("role_id <= ".$this->rolleId);

        // Bereich Programme und Übernachtung
        if( ($this->bereichAnbieter == 1) or ($this->bereichAnbieter == 6) )
            $select->where("bereich = ".$this->bereichAnbieter);

        $select->order('bereich')->order('name');

        $query = $select->__toString();

        $rows = $tabelleBlocks->fetchAll($select)->toArray();

        return $rows;
    }

    /**
     * Menue der Programme
     *
     * @return string
     */
    public function getProgramme(){
		$vorhandeneBausteine = $this->vorhandeneBausteine;
		$zugelasseneBausteine = array();
		
		$j = 0;
        // darf Benutzer auf Programm zugreifen
        if( ($this->rolleId >= $this->rolle_anbieter) ){

            // darf der Benutzer auf den Bereich zugreifen
            for($i=0; $i < count($vorhandeneBausteine); $i++){

                // normaler Anbieter
                if( ($vorhandeneBausteine[$i]['bereich'] == $this->bereichAnbieter) and ($vorhandeneBausteine[$i]['bereich'] == $this->condition_bereich_programme) ){
                    $zugelasseneBausteine[$j] = $vorhandeneBausteine[$i];
                    $j++;
                }
                // Administrator und Offlinebucher
                elseif( ($this->rolleId >= $this->rolle_offlinebucher)  and ($vorhandeneBausteine[$i]['bereich'] == $this->condition_bereich_programme)){
                    $zugelasseneBausteine[$j] = $vorhandeneBausteine[$i];
                    $j++;
                }
		    }
        }
		
		$menu = $this->buildMenu($zugelasseneBausteine);
		return $menu;
	}

    /**
     * Menue Übernachtung
     *
     * @return string
     */
    public function getHotel(){
        $vorhandeneBausteine = $this->vorhandeneBausteine;
        $zugelasseneBausteine = array();

        $j = 0;
        // darf Benutzer auf Programm zugreifen
        if( ($this->rolleId >= $this->rolle_anbieter) ){

            // darf der Benutzer auf den Bereich zugreifen
            for($i=0; $i < count($vorhandeneBausteine); $i++){

                // normaler Anbieter
                if( ($vorhandeneBausteine[$i]['bereich'] == $this->bereichAnbieter) and ($vorhandeneBausteine[$i]['bereich'] == $this->condition_bereich_uebernachtung) ){
                    $zugelasseneBausteine[$j] = $vorhandeneBausteine[$i];
                    $j++;
                }
                // Administrator und Offlinebucher
                elseif( ($this->rolleId >= $this->rolle_offlinebucher)  and ($vorhandeneBausteine[$i]['bereich'] == $this->condition_bereich_uebernachtung)){
                    $zugelasseneBausteine[$j] = $vorhandeneBausteine[$i];
                    $j++;
                }
            }
        }

        $menu = $this->buildMenu($zugelasseneBausteine);
        return $menu;
    }

    /**
     * Menue der Administration
     *
     * @return string
     */
    public function getAdmin(){
		$blocks = $this->vorhandeneBausteine;
		$validBlocks = array();

        // wenn kein Admin
        if($this->rolleId < $this->rolle_administrator)
            return;

		$j = 0;
		for($i=0; $i < count($blocks); $i++){
			if( ($blocks[$i]['role_id'] >= $this->rolle_offlinebucher) and ($blocks[$i]['bereich'] == 0)){
				$validBlocks[$j] = $blocks[$i];
				$j++;	
			}
		}

		$menu = $this->buildMenu($validBlocks);

		return $menu;
	}

    /**
     * Erstellt das JSON - Format der Menue
     *
     * @param $bausteine
     * @return string
     */
    private function buildMenu($bausteine){
		$menu = "";
		
		for($i=0; $i<count($bausteine); $i++){
			$label = $bausteine[$i]['name'];
			$menu .= "{";
			$menu .= " text: '".$label."', href: '"."/admin/".$bausteine[$i]['controller']."'";
			$menu .= "},";
		}

		$menu = substr($menu, 0, -1);
		
		return $menu;
	}

}