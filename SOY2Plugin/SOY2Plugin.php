<?php

/**
 * @package SOY2.SOY2Plugin
 */
interface SOY2PluginDelegateAction{
	function run($extetensionId,$moduleId,SOY2PluginAction $action);
}
/**
 * @package SOY2.SOY2Plugin
 */
interface SOY2PluginAction{}
/**
 * @package SOY2.SOY2Plugin
 */
class SOY2Plugin{
	/**
	 * 拡張ポイントを登録する
	 *
	 * @param string $extensionId 拡張ポイントID
	 * @param string $delegateClassName クラス名
	 */
	public static function registerExtension($extensionId, $delegateClassName){
		$inst =self::getInstance();
		$inst->setDelegate($extensionId,$delegateClassName);
	}
	/**
	 * 拡張ポイントを実行する
	 *
	 * @param string $extensionId 拡張ポイントID
	 * @param string $arguments オプション
	 */
	public static function invoke($extensionId, $arguments = array()){
		$inst = self::getInstance();
		$delegate = $inst->getDelegate($extensionId);
		if(!$delegate)return;
		SOY2::cast($delegate,(object)$arguments);
		$extensions = $inst->getExtensions($extensionId);
		/*
		 * delegateに処理を委譲
		 */
		foreach($extensions as $extensionId => $extensionArray){
			foreach($extensionArray as $moduleId => $array){
				foreach($array as $extensionClassName){
					$class = $inst->getClass($extensionClassName);
					if(!$class)continue;
					if(!($class instanceof SOY2PluginAction))return;
					$delegate->run($extensionId,$moduleId,$class);
				}
			}
		}
		return $delegate;
	}
	/**
	 * 拡張ポイントを実行する
	 *
	 * @return string
	 * @param string $extensionId 拡張ポイントID
	 * @param string $arguments オプション
	 */
	public static function display($extensionId, $arguments = array()){
		ob_start();
		self::invoke($extensionId,$arguments);
		$html = ob_get_contents();
		ob_end_clean();
		return $html;
	}
	/**
	 * 拡張ポイントにモジュールを登録
	 *
	 * @param string $extensionId 拡張ポイント
	 * @param string $moduleId モジュールID
	 * @param string $className クラス名
	 */
	public static function extension($extensionId, $moduleId, $className){
		$inst =self::getInstance();
		$inst->addExtension($extensionId,$moduleId,$className);
	}
	/**
	 * Singleton
	 */
	public static function getInstance($className = null){
		static $_inst;
		if(is_null($_inst)){
			if(is_null($className))$className = "SOY2Plugin";
			$_inst = new $className();
		}
		return $_inst;
	}
	/*
	 * 以下内部使用メソッド、プロパティ
	 */
	private $delegates = array();
	private $extensions = array();
	private $objects = array();
	private $classes = array();
	function setDelegate($point, $delegate){
		$this->delegates[$point] = $delegate;
	}
	/**
	 * @return SOY2PluginDelegateAction
	 */
	function getDelegate($point){
		if(!isset($this->delegates[$point]))return false;
		$delegateClassName = $this->delegates[$point];
		if(!class_exists($delegateClassName))return false;
		$delegate = new $delegateClassName();
		if(!($delegate instanceof SOY2PluginDelegateAction))return false;
		return $delegate;
	}
	/**
	 * 拡張ポイントに追加
	 */
	function addExtension($extension,$moduleId,$extensionClass){
		if(!isset($this->extensions[$extension]))$this->extensions[$extension] = array();
		if(!isset($this->extensions[$extension][$moduleId]))$this->extensions[$extension][$moduleId] = array();
		 $this->extensions[$extension][$moduleId][] = $extensionClass;
	}
	/**
	 *
	 */
	function getClass($className){
		if(!class_exists($className)){
			return null;
		}
		if(!isset($this->classes[$className])){
			$obj = new $className();
			$this->classes[$className] = $obj;
		}
		return $this->classes[$className];
	}
	function getDelegates() {
		return $this->delegates;
	}
	function setDelegates($delegates) {
		$this->delegates = $delegates;
	}
	function getExtensions($extensionId = null) {
		if(!is_null($extensionId)){
			if(strpos($extensionId,".*") == strlen($extensionId)-2){
				$extensionId = substr($extensionId,0,strlen($extensionId)-1);
				$res = array();
				foreach($this->extensions as $key => $array){
					if(strpos($key,$extensionId) === 0){
						$res[$key] = $array;
					}
				}
				return $res;
			}else{
				return (isset($this->extensions[$extensionId])) ? array($extensionId => $this->extensions[$extensionId]) : array();
			}
		}
		return $this->extensions;
	}
	function setExtensions($extensions) {
		$this->extensions = $extensions;
	}
	function getObjects() {
		return $this->objects;
	}
	function setObjects($objects) {
		$this->objects = $objects;
	}
}
