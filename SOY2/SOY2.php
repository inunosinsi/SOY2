<?php

class SOY2{
	private $_rootDir = "webapp/";
	/**
	 * アプリケーションのディレクトリを設定（取得）
	 */
	public static function RootDir($dir = null){
		static $_static;
		if(!$_static)$_static = new SOY2();
		if($dir){
			if(substr($dir,strlen($dir)-1) != '/'){
				throw new Exception("[SOY2]RootDir must end by '/'.");
			}
			$_static->_rootDir = str_replace("\\", "/", $dir);
		}
		return $_static->_rootDir;
	}
	/**
	 * クラスのインポート
	 *
	 * @return クラス名
	 */
	public static function import($path,$extension =".class.php"){
		if(class_exists($path)){
			return $path;
		}
		$tmp = array();
		preg_match('/\.([a-zA-Z0-9_]+$)/',$path,$tmp);
		if(count($tmp)){
			$className = $tmp[1];
		}else{
			$className = $path;
		}
		$dir = self::RootDir();
		$path = str_replace(".","/",$path);
		if(is_readable($dir.$path.$extension) && include_once($dir.$path.$extension)){
			return $className;
		}else{
			return false;
		}
	}
	/**
	 * 指定ディレクトリにあるクラスを全てインポート
	 */
	public static function imports($dir, $rootDir = null){
		if(!$rootDir)$rootDir = SOY2::RootDir();
		$path = str_replace(".","/",$dir);
		$dirPath = $rootDir.str_replace("*","",$path);
		$files = scandir($dirPath);
		foreach($files as $file){
			if(preg_match('/.php$/',$file) && is_readable($dirPath.$file)){
				include_once($dirPath.$file);
			}
		}
	}
	/**
	 * オブジェクトのキャストを行う
	 *
	 * @uses SOY2::cast("クラス名",$obj);
	 * @uses SOY2::cast($obj2,$obj);
	 *
	 * キャスト先のオブジェクトはsetter必須
	 * キャスト元のオブジェクトはgetterがあればそちらを、無ければプロパティを直接。
	 * ただしプロパティがpublicでない場合はコピーしない（警告なし）
	 */
	public static function cast($className,$obj){
		if(!is_object($className)){
			if($className != "array" && $className != "object"){
				$result = self::import($className);
				if($result == false){
					throw new Exception("[SOY2]Could not find class:".$className);
				}
				$className = $result;
			}
		}
		$tmpObject = new stdClass;
		if($obj instanceof stdClass){
			$tmpObject = $obj;
		}else if(is_array($obj)){
			$tmpObject = (object)$obj;
		}else if(is_null($obj)){
			$tmpObject = new stdClass;
		}else{
			$refClass = new ReflectionClass($obj);
			$properties = $refClass->getProperties();
			foreach($properties as $property){
				$name = $property->getName();
				if($refClass->hasMethod("get".ucwords($name))){
					$method = "get".ucwords($name);
					$value = $obj->$method();
					if(is_string($value) && !strlen($value))$value = null;
					$tmpObject->$name = $value;
				}else{
					if(!$property->isPublic())continue;
					$value = $obj->$name;
					if(is_string($value) && !strlen($value))$value = null;
					$tmpObject->$name = $value;
				}
			}
		}
		if(is_object($className)){
			$newObj = $className;
		}else if($className == "array"){
			return (array)$tmpObject;
		}else if($className == "object"){
			return $tmpObject;
		}else{
			$newObj = new $className();
		}
		foreach($tmpObject as $prop => $property){
			if($newObj instanceof stdClass){
				$newObj->$prop = $property;
				continue;
			}
			$methodName = "set".ucwords($prop);
			if(!method_exists($newObj,$methodName))continue;
			$newObj->$methodName($property);
		}
		return $newObj;
	}
	/**
	 * SOY2フレームワークの全ての設定を1メソッドで。
	 *
	 * @example SOY2::config(
	 * 	array(
	 * 		"RootDir" => "webapp/",
	 * 		"ActionDir"  => "webapp/actions/",
	 * 		"PageDir"	=> "webapp/pages/",
	 * 		"CacheDir"	=> "page_cache/",
	 * 		"DaoDir"	=> "webapp/dao/",
	 * 		"EntityDir"	=> "webapp/entity/",
	 * 		"Dsn"		=> "localhost...",
	 * 		"user"		=> "",
	 * 		"pass"		=> ""
	 * ));
	 */
	public static function config($array){
		if(isset($array['RootDir'])){
			SOY2::RootDir($array['RootDir']);
		}
		if(isset($array['ActionDir'])){
			SOY2ActionConfig::ActionDir($array['ActionDir']);
		}
		if(isset($array['PageDir'])){
			SOY2HTMLConfig::PageDir($array['PageDir']);
		}
		if(isset($array['CacheDir'])){
			SOY2HTMLConfig::CacheDir($array['CacheDir']);
		}
		if(isset($array['DaoDir'])){
			SOY2DAOConfig::DaoDir($array['DaoDir']);
		}
		if(isset($array['EntityDir'])){
			SOY2DAOConfig::EntityDir($array['EntityDir']);
		}
		if(isset($array['Dsn'])){
			SOY2DAOConfig::Dsn($array['Dsn']);
		}
		if(isset($array['pass'])){
			SOY2DAOConfig::user($array['user']);
		}
		if(isset($array['pass'])){
			SOY2DAOConfig::pass($array['pass']);
		}
	}
}
