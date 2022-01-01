<?php

/**
 * @package SOY2.SOY2DAO
 */
class SOY2DAOContainer{
	private $daos = array();
	private function __construct(){
	}
	public static function get(string $name, array $arguments=array()){
		static $instance;
		if(is_null($instance)) $instance = new SOY2DAOContainer;
		return $instance->_get($name,$arguments);
	}
    public static function _get(string $name, array $arguments=array()){
		if(isset($this->daos[$name])){
			$dao  = $this->daos[$name];
		}else{
			$dao = SOY2DAOFactory::create($name,$arguments);
			$this->daos[$name] = $dao;
		}
		foreach($arguments as $key => $value){
			if(method_exists($dao,"set".ucwords($key))){
				$func = "set".ucwords($key);
				$dao->$func($value);
				continue;
			}
		}
		return $dao;
    }
}
