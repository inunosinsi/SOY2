<?php

/**
 * @package SOY2.SOY2Logic
 */
interface SOY2LogicInterface{
	public static function getInstance($className,$args);
}
/**
 * @package SOY2.SOY2Logic
 */
abstract class SOY2LogicBase implements SOY2LogicInterface{
	public static function getInstance($className,$args){
		$obj = new $className();
		foreach($args as $key => $value){
			$method = "set".ucwords($key);
			if(method_exists($obj,$method)){
				$obj->$method($args[$key]);
			}
		}
		return $obj;
	}
}
/**
 * @package SOY2.SOY2Logic
 */
class SOY2Logic{
	public static function createInstance($classPath,$array = array()){
		if(!class_exists($classPath)){
			if(SOY2::import($classPath) == false){
				throw new Exception("Failed to include ".$classPath);
			}
		}
		if(preg_match('/\.?([a-zA-Z0-9_]+$)/',$classPath,$tmp)){
			$classPath = $tmp[1];
		}
		$refClass = new ReflectionClass($classPath);
		$interfaces = $refClass->getInterfaces();
		$flag = false;
		if(array_key_exists("SOY2LogicInterface",$interfaces)){
			$flag = true;
		}else{
			foreach($interfaces as $key => $interface){
				if($interface->getName() == "SOY2LogicInterface"){
					$flag = true;
					break;
				}
			}
		}
		if(!$flag){
			throw new Exception("[SOY2Logic]$classPath"." must be subclass of SOY2LogicBase.");
		}
		$method = $refClass->getMethod("getInstance");
		return $method->invoke(NULL,$classPath,$array);
	}
}
/**
 * @package SOY2.SOY2Logic
 */
class SOY2LogicContainer {
	private $logics = array();
	private function __construct(){
	}
	public static function get($name,$array = array()){
		static $instance;
		if(!$instance){
			$instance = new SOY2LogicContainer;
		}
		return $instance->_get($name,$array);
	}
    private function _get($name,$array = array()){
    	if(isset($this->logics[$name])){
    		$obj = $this->logics[$name];
    	}else{
    		$obj = SOY2Logic::createInstance($name,$array);
    		$this->logics[$name] = $obj;
    	}
    	foreach($array as $key => $value){
			$method = "set".ucwords($key);
			if(method_exists($obj,$method)){
				$obj->$method($array[$key]);
			}
		}
		return $obj;
    }
}
