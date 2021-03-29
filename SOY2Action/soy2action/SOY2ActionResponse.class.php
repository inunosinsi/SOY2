<?php

/**
 * @package SOY2.SOY2Action
 */
class SOY2ActionResponse{
	private $_header = array();
	public static function &getInstance(){
		static  $_static;
		if(!$_static){
			$_static = new SOY2ActionResponse();
		}
		return $_static;
	}
	/**
	 * @todo デストラクタでヘッダー送信はスマートじゃないかも。でも壊されない限り呼ばれないしな
	 */
	function __destruct(){
		foreach($this->_header as $key => $value){
			header($key.": ".$value);
		}
	}
	function addHeader($key,$value){
		if(!is_array(@$this->_header[$key])){
			$this->_header[$key] = array(@$this->header[$key]);
		}
		$this->_header[$key] = $value;
	}
	function sendRedirect($url){
		header("Location: ".$url);
	}
	function setHeader($key,$value){
		$this->_header[$key] = $value;
	}
}
