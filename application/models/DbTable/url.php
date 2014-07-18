<?php

/**
 * Tabbel zur Speicherung der URL Alias des System HOB
 *
 * + speichern der vorhandenen URL des System
 * + umschreiben deutsches Alias
 * + umschreiben englisches Alias
 *
 * @author Stephan Krauss
 * @date 07.07.2014
 * @package tabelle
 */
class Application_Model_DbTable_url extends Zend_Db_Table_Abstract
{
    protected $_name = 'tbl_url';
    protected $_primary = 'id';

    protected $condition_url_nicht_vorhanden = 0;
    protected $condition_url_einmal_vorhanden = 1;
    protected $condition_url_mehrfach_vorhanden = 2;

    /**
     * @param $url
     * @return int
     */
    public function updateUrlAlias($url)
    {

        $cols = array(
            new Zend_Db_Expr("count(id) as anzahl")
        );

        $whereUrl = "url = '" . $url . "'";

        $select = $this->select();
        $select->from($this, $cols)->where($whereUrl);

        $query = $select->__toString();

        $rows = $this->fetchAll($select)->toArray();

        if ($rows[0]['anzahl'] == $this->condition_url_einmal_vorhanden)
            return $this->condition_url_einmal_vorhanden;
        elseif ($rows[0]['anzahl'] == $this->condition_url_nicht_vorhanden) {
            $this->insert(array('url' => $url));

            return $this->condition_url_nicht_vorhanden;
        } else
            return $this->condition_url_mehrfach_vorhanden;
    }

}
