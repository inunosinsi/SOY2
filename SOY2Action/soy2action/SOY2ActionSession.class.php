<?php

/**
 * @package SOY2.SOY2Action
 */
class SOY2ActionSession {
    const session_user_key = "_SOY2_USER_";
    const session_flash_key = "_SOY2_FLASH_";
    /**
     * @return SOY2UserSession
     */
    public static function &getUserSession(){
    	@session_start();
    	if(!isset($_SESSION[self::session_user_key])){
    		$_SESSION[self::session_user_key] = new SOY2UserSession();
    	}
    	return $_SESSION[self::session_user_key];
    }
    /**
     * @return SOY2FlashSession
     */
    public static function &getFlashSession(){
    	@session_start();
    	static $_request;
    	if(is_null($_request)){
    		$_request = true;
    	}
    	if(!isset($_SESSION[self::session_flash_key])){
    		$_SESSION[self::session_flash_key] = new SOY2FlashSession();
    	}
    	if($_request == true){
    		$_SESSION[self::session_flash_key]->checkFlash();
    		$_request = false;
    	}
    	return $_SESSION[self::session_flash_key];
    }
    public static function regenerateSessionId(){
    	@session_start();
    	session_regenerate_id(true);
    }
}
/**
 * @package SOY2.SOY2Action
 */
class SOY2ActionSessionBase{
	private $_hash = array();
	function setAttribute($key,$value){
		$this->_hash[$key] = soy2_serialize($value);
		if(is_null($value)){
			unset($this->_hash[$key]);
		}
	}
	function getAttribute($key){
		return (isset($this->_hash[$key])) ? soy2_unserialize($this->_hash[$key]) : null;
	}
	function clearAttributes(){
		$this->_hash = array();
	}
	function getAttributeKeys(){
		return array_keys($this->_hash);
	}
}
/**
 * @package SOY2.SOY2Action
 */
class SOY2UserSession extends SOY2ActionSessionBase{
	private $isAuthenticated = array();
	private $credentials = array();
	function setAuthenticated($key,$flag = null){
		if(is_null($flag) && is_bool($key)){
			$flag = $key;
			$key = "default";
		}
		/**
		 * 以前のハッシュでない$isAuthenticatedがセッションに残っていた時のため
		 * 旧バージョンでのログイン中にファイルを入れ替えるとsetAuthenticatedをしても値が変わらなかった
		 */
		if(!is_array($this->isAuthenticated)){
			$this->isAuthenticated = array();
		}
		$this->isAuthenticated[$key] = $flag;
	}
	function getAuthenticated($key = null){
		if(is_null($key))$key = "default";
		return (isset($this->isAuthenticated[$key])) ? $this->isAuthenticated[$key] : false;
	}
	function addCredential(){
		$args = func_get_args();
		foreach($args as $key => $value){
			$this->credentials[$key] = $args[$key];
		}
	}
	function hasCredential($key){
		return (in_array($key,$this->credentials)) ? true : false;
	}
	function removeCredential($key){
		if(!isset($this->credentials[$key]))return;
		$this->credentials[$key] = null;
		unset($this->credentials[$key]);
	}
	function clearCredentials(){
		$this->credentials = array();
	}
}
/**
 * @package SOY2.SOY2Action
 */
class SOY2FlashSession extends SOY2ActionSessionBase{
	private $isFlash = 0;
	function checkFlash(){
		$this->isFlash++;
		if($this->isFlash >= 2){
			$this->clearAttributes();
			$this->resetFlashCounter();
		}
	}
	function resetFlashCounter(){
		$this->isFlash = 0;
	}
	/**
	 * reset all flash session
	 */
	function reset($array = null){
		$this->clearAttributes();
    	$this->resetFlashCounter();
    	if(is_array($array)){
	    	foreach($array as $key => $value){
	    		$this->setAttribute($key,$value);
	    	}
    	}
	}
}
