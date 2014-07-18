<?php

class Admin_Model_DatensatzZeitenMapperTreffpunkt{

    protected $_dbTable;

     public function __construct($options = null){

        if(is_array($options)){
            $this->setOptions($options);
        }

        return;
    }

    public function setOptions(array $options){

           $methods = get_class_methods($this);

           foreach ($options as $key => $value) {
               $method = 'set' . ucfirst($key);

               if (in_array($method, $methods)) {
                   $this->$method($value);
               }
           }

           return $this;
       }



    public function setDbTable($dbTable){
        if (is_string($dbTable)) {
            $dbTable = new $dbTable();
        }

        if (!$dbTable instanceof Zend_Db_Table_Abstract) {
            throw new Exception('Ungültiges Table Data Gateway angegeben');
        }

        $this->_dbTable = $dbTable;

        return $this;
    }

    public function save(Admin_Model_Index $model){

        // Mapping der Daten des Model 'Admin_Model_Index'

        if (null === ($model->data['id'])) {
            $this->_dbTable->insert($model->data);
        }
        else {
            $id = $model->data['id'];
            unset($model->data['id']);
            $this->_dbTable->update($model->data, array('id = ?' => $id));
        }

        return;
    }

    public function find($id, Admin_Model_Index $model){
        $entries   = array();
        $result = $this->_dbTable->find($id);

        if (0 == count($result)) {
            return;
        }

        $resultSet = $result->current();
        $entries = $resultSet->toArray();

        $i=0;
        $model->data[$i] = $entries;

        return;
    }

    public function fetchAll(Admin_Model_Index $model){
        $entries   = array();
        $resultSet = $this->_dbTable->fetchAll();

        $i=0;
        foreach ($resultSet as $row) {
            $entries[$i]['id'] = $row->id;
            $entries[$i]['firma'] = $row->firma;

            $i++;
        }

        $model->data = $entries;

        return;
    }

}

