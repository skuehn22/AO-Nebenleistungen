<?php
class nook_Exception extends Exception {	
	
	public $edited = false;
    protected $query = '';

    public function getQuery()
    {
        return $this->query;
    }

    public function setQuery($query)
    {
        $this->query = $query;
    }
	
}