<?php

/**
 * @package SOY2.controller
 */
class SOY2PageController implements SOY2_Controller{
	var $defaultPath = "Index";
	var $requestPath = "";
	var $arguments = array();
	public static function init($controller = null){
		static $_controller;
		if(!$_controller){
			if($controller){
				$_controller = new $controller();
			}else{
				$_controller = new SOY2PageController();
			}
		}
		return $_controller;
	}
	final public static function run(){
		$controller = self::init();
		$controller->execute();
	}
	final public static function getRequestPath(){
		$controller = self::init();
		return $controller->requestPath;
	}
	public static function getArguments(){
		$controller = self::init();
		return $controller->arguments;
	}
	function execute(){
		$pathBuilder = $this->getPathBuilder();
		$path = $pathBuilder->getPath();
		$args = $pathBuilder->getArguments();
		if(!strlen($path) || substr($path,strlen($path)-1,1) == "."){
			$path .= $this->getDefaultPath();
		}
		$this->requestPath = $path;
		$this->arguments = $args;
		$classPathBuilder = $this->getClassPathBuilder();
		$classPath = $classPathBuilder->getClassPath($path);
		$classPath .= 'Page';
		if(!SOY2HTMLFactory::pageExists($classPath)){
			$path = $pathBuilder->getPath();
			$classPath = $classPathBuilder->getClassPath($path);
			if(!preg_match('/.+Page$/',$classPath)){
				$classPath .= '.IndexPage';
			}
		}
		if(!SOY2HTMLFactory::pageExists($classPath)){
			$this->onNotFound($path, $args, $classPath);
		}
		$webPage = &SOY2HTMLFactory::createInstance($classPath, array(
			"arguments" => $args
		));
		try{
			$webPage->display();
		}catch(Exception $e){
			$this->onError($e);
		}
	}
	function onError(Exception $e){
		throw $e;
	}
	/**
	 * ページが存在しない場合
	 * 引数はオーバーロード用
	 * @param $path
	 * @param $args
	 * @param $classPath
	 */
	function onNotFound($path = null, $args = null, $classPath = null){
		header("HTTP/1.1 404 Not Found");
		header("Content-Type: text/html; charset=utf-8");
		echo "<h1>404 Not Found</h1><hr>指定のパスへのアクセスは有効でありません。";
		exit;
	}
	function getDefaultPath(){
		$controller = self::init();
		return $controller->defaultPath;
	}
	function setDefaultPath($path){
		$controller = self::init();
		$controller->defaultPath = $path;
	}
	public static function jump($path){
		$url = self::createLink($path, true);
		header("Location: ".$url);
		exit;
	}
	public static function redirect($path, $permanent = false){
		if($permanent){
			header("HTTP/1.1 301 Moved Permanently");
		}
		$url = self::createRelativeLink($path, true);
		header("Location: ".$url);
		exit;
	}
	public static function reload(){
		$url = self::createLink(self::getRequestPath(), true) ."/". implode("/",self::getArguments());
		header("Location: ".$url);
		exit;
	}
	function &getPathBuilder(){
		static $builder;
		if(!$builder){
			$builder = new SOY2_PathInfoPathBuilder();
		}
		return $builder;
	}
	function &getClassPathBuilder(){
		static $builder;
		if(!$builder){
			$builder = new SOY2_DefaultClassPathBuilder();
		}
		return $builder;
	}
	public static function createLink($path, $isAbsoluteUrl = false){
		$controller = self::init();
		$pathBuilder = $controller->getPathBuilder();
		return $pathBuilder->createLinkFromPath($path, $isAbsoluteUrl);
	}
	public static function createRelativeLink($path, $isAbsoluteUrl = false){
		$controller = self::init();
		$pathBuilder = $controller->getPathBuilder();
		return $pathBuilder->createLinkFromRelativePath($path, $isAbsoluteUrl);
	}
}
/**
 * @package SOY2.controller
 * PathInfoから呼び出しパスを作成
 * 後半の数字を含む部分は引数として渡す
 *
 * DOCUMENT_ROOTを仮想的にSOY2_DOCUMENT_ROOTで上書き可能
 */
class SOY2_PathInfoPathBuilder implements SOY2_PathBuilder{
	var $path;
	var $arguments;
	function __construct(){
		$pathInfo = (isset($_SERVER['PATH_INFO'])) ? $_SERVER['PATH_INFO'] : "";
		if(preg_match('/^((\/[a-zA-Z]*)*)(\/-)?((\/[0-9a-zA-Z_\.]*)*)$/',$pathInfo,$tmp)){
			$path = preg_replace('/^\/|\/$/',"",$tmp[1]);
			$path = str_replace("/",".",$path);
			$arguments = preg_replace("/^\//","",$tmp[4]);
			$arguments = explode("/",$arguments);
			foreach($arguments as $key => $value){
				if(!strlen($value)){
					$arguments[$key] = null;
					unset($arguments[$key]);
				}
			}
			$this->path = $path;
			$this->arguments = $arguments;
		}
	}
	function getPath(){
		return $this->path;
	}
	function getArguments(){
		return $this->arguments;
	}
	/**
	 * パスからURLを生成する
	 * スクリプトのファイル名を含む（ただし$pathが空の時はスクリプト名を付けない）
	 */
	function createLinkFromPath($path, $isAbsoluteUrl = false){
		$scriptPath = self::getScriptPath();
		if(strlen($path)>0){
			$path = $scriptPath . "/" . str_replace(".","/",$path);
		}else{
			$path = strrev(strstr(strrev($scriptPath),"/"));
		}
		if($isAbsoluteUrl){
			return self::createAbsoluteURL($path);
		}else{
			return $path;
		}
	}
	/**
	 * 相対パスを解釈してURLを生成する
	 * @param String $path 相対パス
	 * @param Boolean $isAbsoluteUrl 返り値を絶対URL（http://example.com/path/to）で返すかルートからの絶対パス（/path/to）で返すか
	 */
	function createLinkFromRelativePath($path, $isAbsoluteUrl = false){
		if(preg_match("/^https?:/",$path)){
			return $path;
		}
		if(preg_match("/^\//",$path)){
		}else{
			$scriptPath = self::getScriptPath();
			$scriptDir = preg_replace("/".basename($scriptPath)."\$/", "", $scriptPath);
			$path = self::convertRelativePathToAbsolutePath($path, $scriptDir);
		}
		if($isAbsoluteUrl){
			return self::createAbsoluteURL($path);
		}else{
			return $path;
		}
	}
	/**
	 * フロントコントローラーのURLでの絶対パスを取得する
	 * （ファイルシステムのパスではない）
	 */
	protected static function getScriptPath(){
		static $script;
		if(!$script){
			/**
			 * @TODO ルート的にアクセスされた場合は、フロントコントローラーの設置場所をDocumentRootとみなす。
			 */
			$documentRoot = (defined("SOY2_DOCUMENT_ROOT")) ? SOY2_DOCUMENT_ROOT : ((isset($_SERVER["SOY2_DOCUMENT_ROOT"])) ? $_SERVER["SOY2_DOCUMENT_ROOT"] : $_SERVER["DOCUMENT_ROOT"]);
			$documentRoot = str_replace("\\","/",$documentRoot);
			if(strlen($documentRoot) >0 && $documentRoot[strlen($documentRoot)-1] != "/") $documentRoot .= "/";
			$script = str_replace($documentRoot,"/",str_replace("\\","/",$_SERVER["SCRIPT_FILENAME"]));
			$script = str_replace("\\","/",$_SERVER["SCRIPT_FILENAME"]);
			$script = str_replace($documentRoot, "/", $script);
		}
		return $script;
	}
	/**
	 * 絶対パスにドメインなどを付加して絶対URLに変換する
	 */
	protected static function createAbsoluteURL($path){
		static $scheme, $domain, $port;
		if(!$scheme){
			$scheme = (isset($_SERVER["HTTPS"]) || defined("SOY2_HTTPS") && SOY2_HTTPS) ? "https" : "http";
		}
		if(!$domain && isset($_SERVER["SERVER_NAME"])){
			$domain = $_SERVER["SERVER_NAME"];
		}
		if(!$port){
			if(!isset($_SERVER["SERVER_PORT"])) $_SERVER["SERVER_PORT"] = 80;
			if( $_SERVER["SERVER_PORT"] == "80" && !isset($_SERVER["HTTPS"]) || $_SERVER["SERVER_PORT"] == "443" && isset($_SERVER["HTTPS"]) ){
				$port = "";
			}elseif(strlen($_SERVER["SERVER_PORT"]) > 0){
				$port = ":".$_SERVER["SERVER_PORT"];
			}else{
				$port = "";
			}
		}
		return $scheme."://".$domain.$port.$path;
	}
	/**
	 * 指定したディレクトリからの相対パスを絶対パスに変換する
	 */
	protected static function convertRelativePathToAbsolutePath($relativePath, $base){
		$base = str_replace("\\","/",$base);
		$base = preg_replace("/\/+/","/",$base);
		$relativePath = str_replace("\\","/",$relativePath);
		$relativePath = preg_replace("/\/+/","/",$relativePath);
		$dirs = explode("/", $base);
		if($dirs[0] == "") array_shift($dirs);
		if(count($dirs) > 0 && $dirs[count($dirs)-1] == "") array_pop($dirs);
		$paths = explode("/",$relativePath);
		$pathStack = array();
		foreach($paths as $path){
			if($path == ".."){
				array_pop($dirs);
			}elseif($path == "."){
			}else{
				array_push($pathStack,$path);
			}
		}
		$absolutePath = implode("/",array_merge($dirs,$pathStack));
		$absolutePath = "/".$absolutePath;
		return $absolutePath;
	}
}
/**
 * @package SOY2.controller
 */
class SOY2_DefaultClassPathBuilder implements SOY2_ClassPathBuilder{
	function getClassPath($path){
		return $path;
	}
}
