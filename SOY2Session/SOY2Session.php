<?php

class SOY2Session{
	const _KEY_ = "_soy2_session_";
	private static $_deleted_class = null;
	/**
	 * get session
	 */
	public static final function get($sessionClass){
		return self::getSession($sessionClass)->getObject();
	}
	public static function getSession($sessionClass){
		$className = SOY2::import($sessionClass);
		if(!isset($_SESSION)){
			session_start();
		}
		if(!isset($_SESSION[self::_KEY_]))$_SESSION[self::_KEY_] = array();
		if(!isset($_SESSION[self::_KEY_][$className])){
			$obj = new SOY2SessionValue($sessionClass);
			$_SESSION[self::_KEY_][$className] = $obj;
		}else{
			$obj = $_SESSION[self::_KEY_][$className];
		}
		return $_SESSION[self::_KEY_][$className];
	}
	public static function destroyAll(){
		if(!isset($_SESSION)){
			session_start();
		}
		unset($_SESSION[self::_KEY_]);
	}
	public static function destroySession($sessionClass = null){
		if(!$sessionClass){
			return self::$_deleted_class;
		}
		$className = preg_replace('/.*\.(.*)/','$1',$sessionClass);
		if(!isset($_SESSION)){
			self::$_deleted_class = $className;
			session_start();
		}
		$_SESSION[self::_KEY_][$className] = null;
		unset($_SESSION[self::_KEY_][$className]);
	}
	/**
	 * init
	 * call only first time
	 */
	function init(){
	}
	/**
	 * 復元時に毎回呼ばれる
	 */
	function wakeup(){
	}
	/**
	 * delete from session
	 */
	function destroy(){
		unset($_SESSION[self::_KEY_][get_class($this)]);
	}
	/**
	 * reset all parameter to null
	 */
	function clear(){
		$_SESSION[self::_KEY_][get_class($this)]->reset();
	}
}
class SOY2SessionValue{
	private $create;
	private $update;
	private $className;
	private $classObject;
	private $classValue;
	function __construct($className){
		$class = SOY2::import($className);
		$this->className = $className;
		$this->classObject = new $class;
		$this->create = time();
		if(!$this->classObject instanceof SOY2Session){
			trigger_error($className . " is not subclass of SOY2Session");
		}
		$this->classObject->init();
	}
	function getClassName() {
		return $this->className;
	}
	function setClassName($className) {
		$this->className = $className;
	}
	function getClassValue() {
		return $this->classValue;
	}
	function setClassValue($classValue) {
		$this->classValue = $classValue;
	}
	function getObject(){
		return $this->classObject;
	}
	function __sleep(){
		if($this->classObject){
			$this->classValue = SOY2::cast("object",$this->classObject);
		}
		$this->update = time();
		return array("className","classValue","create","update");
	}
	function __wakeup(){
		try{
			$this->classObject = SOY2::cast($this->className,$this->classValue);
			if(SOY2Session::destroySession() != get_class($this->classObject)){
				$this->classObject->wakeup();
			}
		}catch(Exception $e){
		}
	}
	function reset(){
		$obj = SOY2::cast("array",$this->classObject);
		foreach($obj as $key => $value){
			$obj[$key] = null;
		}
		$this->classObject = SOY2::cast($this->className,(object)$obj);
	}
}
