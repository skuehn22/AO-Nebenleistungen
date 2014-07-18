<?php
require_once 'Zend/View/Abstract.php';

class raintpl_View extends Zend_View_Abstract {

  private $_raintpl;

  public function __construct($data) {
    parent::__construct($data);

    require_once "../library/raintpl/rain.tpl.class.php";

    $this->_raintpl = new raintpl();
    raintpl::$tpl_dir = $data['tpl_dir'];
    raintpl::$cache_dir = $data['cache_dir'];
  }

  public function getEngine() {
    return $this->_raintpl;
  }

  public function __set($key, $val) {
    $this->_raintpl->assign($key, $val);
  }

  public function __get($key) {
      return isset( $this->_raintpl->$var[$key] ) ? $this->_raintpl->$var[$key] : null;
  }

  public function __isset($key) {
    return $this->_raintpl->get_template_vars($key) != null;
  }

  public function __unset($key) {
    $this->_raintpl->clear_assign($key);
  }

  public function assign($spec, $value=null) {
    if (is_array($spec)) {
      $this->_raintpl->assign($spec);
      return;
    }
    $this->_raintpl->assign($spec, $value);
  }

  public function clearVars() {
    $this->_raintpl->$var=array();
  }

  public function render($name,$return_string=false) {
      if( $ext = strrchr($name, '.') )
          $name = substr($name, 0, -strlen($ext)); 
    return $this->_raintpl->draw($name,$return_string);
  }

  public function _run() {

  }

}
