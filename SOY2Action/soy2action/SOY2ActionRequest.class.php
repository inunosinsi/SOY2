<?php

/**
 * @package SOY2.SOY2Action
 */
class SOY2ActionRequest{
	private $_hash;
	private $_method;
	public static function &getInstance(){
		static  $_static;
		if(!$_static){
			$_static = new SOY2ActionRequest();
			$_static->_hash = array_merge($_POST,$_GET);
			$_static->_method = $_SERVER['REQUEST_METHOD'];
		}
		return $_static;
	}
	function getCookies(){
	}
	function getHeader($name){
	}
	function getMethod(){
		return $this->_method;
	}
	function setMethod($method){
		$this->_method = $method;
	}
	function getParameter($name){
		return (isset($this->_hash[$name])) ? $this->_hash[$name] : null;
	}
	function getParameterNames(){
		return array_keys($this->_hash);
	}
	function &getParameters(){
		return $this->_hash;
	}
	function setParameter($key,$value){
		$this->_hash[$key] = $value;
	}
	function addParameter($key,$value){
		if(!isset($this->_hash[$key])){
			$this->_hash[$key] = array($value);
			return;
		}
		if(is_array($this->_hash[$key])){
			$this->_hash[$key][] = $value;
		}else{
			$this->_hash[$key] = array($this->_hash[$key],$value);
		}
	}
}
