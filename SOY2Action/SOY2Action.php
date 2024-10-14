<?php

/**
 * @package SOY2.SOY2Action
 */
class SOY2Action extends SOY2ActionBase{
	const SUCCESS = "_success_";
	const FAILED = "_failed_";

	private $_result;
	
	/**
	 * Action実行前準備
	 */
	protected function prepareImpl(SOY2ActionRequest	&$request,
							   SOY2ActionResponse	$response){
		return (method_exists($this,"prepare")) ? $this->prepare($request,$response) : null;
	}
	/**
	 * Action実行
	 */
	protected function executeImpl(SOY2ActionRequest	&$request,
							   SOY2ActionForm		&$form,
							   SOY2ActionResponse	&$response){
		return (method_exists($this,"execute")) ? $this->execute($request,$form,$response) : null;
	}
	/**
	 * Action実行。
	 * Get時に実行される。
	 */
	protected function doGetImpl(SOY2ActionRequest	&$request,
							   SOY2ActionForm		&$form,
							   SOY2ActionResponse	&$response){
		return (method_exists($this,"doGet")) ? $this->doGet($request,$form,$response) : null;
	}
	/**
	 * Action実行
	 * Post時に実行される。
	 */
	protected function doPostImpl(SOY2ActionRequest	&$request,
							   SOY2ActionForm		&$form,
							   SOY2ActionResponse	&$response){
		return (method_exists($this,"doPost")) ? $this->doPost($request,$form,$response) : null;
	}
	/**
	 * Action実行後処理
	 */
	protected function clearanceImpl(SOY2ActionResponse	&$response){
		if(method_exists($this,"clearance"))$this->clearance($response);
		$response = null;
	}
	/**
	 * Action実行処理
	 * @final
	 */
	final function run(){
		$request =& SOY2ActionRequest::getInstance();
		$response =& SOY2ActionResponse::getInstance();
		$this->_result = new SOY2ActionResult();
		$this->prepareImpl($request,$response);
		$formName = $this->getActionFormName();
		$form = SOY2ActionForm::createForm($formName,$request);
		if($request->getMethod() == 'POST'){
			$result = $this->doPostImpl($request,$form,$response);
			if($result)$this->_result->setResult($result);
		}else if($request->getMethod() == 'GET'){
			$result = $this->doGetImpl($request,$form,$response);
			if($result)$this->_result->setResult($result);
		}
		$result = $this->executeImpl($request,$form,$response);
		if($result)$this->_result->setResult($result);
		$this->clearanceImpl($response);
		return $this->_result;
	}
	/**
	 * メッセージをリザルトオブジェクトに設定
	 *
	 * @param キー
	 * @param 値
	 */
	function setMessage($key,$value){
		$this->_result->setMessage($key,$value);
	}
	/**
	 * リザルトオブジェクトからメッセージを取得
	 *
	 * @param　キー（省略可。省略時は全て）
	 */
	function getMessage($key = null){
		return $this->_result->getMessage($key);
	}
	/**
	 * エラーメッセージをリザルトオブジェクトに設定
	 *
	 * @param キー
	 * @param 値
	 */
	function setErrorMessage($key,$value){
		$this->_result->setErrorMessage($key,$value);
	}
	/**
	 * リザルトオブジェクトからエラーメッセージを取得
	 *
	 * @param キー (省略可。省略時は全て)
	 */
	function getErrorMessage($key = null){
		return $this->_result->getErrorMessage($key);
	}
	/**
	 * リザルトオブジェクトに属性を設定
	 *
	 * @param キー
	 * @param 値
	 */
	function setAttribute($key,$obj){
		$this->_result->setAttribute($key,$obj);
	}
	/**
	 * リザルトオブジェクトから属性を取得
	 *
	 * @param キー
	 */
	function getAttribute($key){
		return $this->_result->getAttribute($key);
	}
	/**
	 * ActionFormのクラス名を取得
	 * ディフォルトはクラス名+Form
	 *
	 * オーバーライドすることでActionFormのクラスを変更することが可能
	 */
	function getActionFormName(){
		return get_class($this). "Form";
	}
	/**
	 * ユーザーセッションを呼び出す
	 *
	 * @return SOY2UserSession
	 */
	function getUserSession(){
		return SOY2ActionSession::getUserSession();
	}
	/**
	 * Flashセッションを呼び出す
	 *
	 * @return SOY2FlashSession
	 */
	function getFlashSession(){
		return SOY2ActionSession::getFlashSession();
	}
}
/**
 * @package SOY2.SOY2Action
 */
class SOY2ActionResult{
	private $_result;
	private $_message;
	private $_errorMessage;
	private $_attributes;
	function setResult($result){
		switch($result){
			case SOY2Action::SUCCESS:
			case SOY2Action::FAILED:
				$this->_result = $result;
				break;
			default:
				throw new Exception("SOY2Action must return SOY2Action::SUCCESS or SOY2Action::FAILED.");
		}
	}
	function __toString(){
		return $this->_result;
	}
	function setMessage($key,$value){
		$this->_message[$key] = $value;
	}
	function getMessage($key = null){
		if(is_null($key)){
			return $this->_message;
		}
		return (isset($this->_message[$key])) ? $this->_message[$key] : null;
	}
	function setErrorMessage($key,$value){
		$this->_errorMessage[$key] = $value;
	}
	function getErrorMessage($key = null){
		if(is_null($key)){
			return $this->_errorMessage;
		}
		return (isset($this->_errorMessage[$key])) ? $this->_errorMessage[$key] : null;
	}
	function setAttribute($key,$obj){
		$this->_attributes[$key] = $obj;
	}
	function getAttribute($key){
		if(is_null($key)){
			return $this->_attributes;
		}
		return (isset($this->_attributes[$key])) ? $this->_attributes[$key] : null;
	}
	/**
	 * booleanを返します。
	 *
	 * PHP 5.2.0 以前では__toStringが使えないのでこちらを使用してください。
	 *
	 * @return boolean 成功か、失敗か
	 */
	function success(){
		return ($this->_result == SOY2Action::SUCCESS) ? true : false;
	}
}
/**
 * @package SOY2.SOY2Action
 */
class SOY2ActionConfig{
	private function __construct(){}
	private $actionPath = "actions/";
	private static function &getInstance(){
		static $_static;
		if(!$_static){
			$_static = new SOY2ActionConfig();
		}
		return $_static;
	}
	public static function ActionDir($dir = null){
		$config = self::getInstance();
		if($dir){
			if(substr($dir,strlen($dir)-1) != '/'){
				throw new Exception("[SOY2Action]ActionDir must end by '/'.");
			}
			$config->actionPath = str_replace("\\", "/", $dir);
		}
		return $config->actionPath;
	}
}
/**
 * @package SOY2.SOY2Action
 * SOY2ActionFactory
 * SOY2Actionオブジェクトを作成します。
 */
class SOY2ActionFactory extends SOY2ActionBase{
	static function &createInstance($path,$attributes = array()){
		$obj = null;
		if(class_exists($path)){
			$obj = new $path();
		}else{
			$tmp = array();
			if(preg_match('/\.?([a-zA-Z0-9]+$)/',$path,$tmp)){
				$className = $tmp[1];
			}
			if(!class_exists($className)){
				$fullPath = SOY2ActionConfig::ActionDir(). str_replace(".","/",$path).".class.php";
				if(defined("SOY2ACTION_AUTO_GENERATE") && SOY2ACTION_AUTO_GENERATE == true && !file_exists($fullPath)){
					SOY2ActionFactory::generateAction($className,$fullPath,$attributes);
				}
				include($fullPath);
			}
			$obj = new $className();
		}
		foreach($attributes as $key => $value){
			if(method_exists($obj,"set".ucwords($key))){
				$func = "set".ucwords($key);
				$obj->$func($value);
				continue;
			}
		}
		return $obj;
	}
	private static function generateAction($className,$fullPath,$attributes){
		$dirpath = dirname($fullPath);
		while(realpath($dirpath) == false){
			if(!mkdir($dirpath))return;
			$dirpath = dirname($dirpath);
		}
		$docComment = array();
		$docComment[] = "/**";
		$docComment[] = " * @class $className";
		$docComment[] = " * @date ".date("c");
		$docComment[] = " * @author SOY2ActionFactory";
		$docComment[] = " */ ";
		$class = array();
		$class[] = "class ".$className." extends SOY2Action{";
		if(!empty($attributes)){
			$class[] = "	";
			$setter = array();
			foreach($attributes as $key => $value){
			$class[] = '	private $'.$key.';';
			$setter[] = '	';
			$setter[] = '	function set'.ucwords($key).'($'.$key.'){';
			$setter[] = '		$this->'.$key.' = $'.$key.';';
			$setter[] = '	}';
			$setter[] = '	';
			}
			$class[] = implode("\n",$setter);
		}
		$class[] = "	";
		$class[] = "	/**";
		$class[] = "	 * Actionの実行を行います。";
		$class[] = "	 */";
		$class[] = '	protected function execute(SOY2ActionRequest &$request,SOY2ActionForm &$form, SOY2ActionResponse &$response){';
		$class[] = "		";
		$class[] = "		//フォームにエラーが発生していた場合";
		$class[] = '		if($form->hasError()){';
		$class[] = '			foreach($form->getErrors() as $key => $value){';
		$class[] = '				$this->setErrorMessage($key,$form->getErrorString($key));';
		$class[] = '			}';
		$class[] = '			return SOY2Action::FAILED;';
		$class[] = '		}';
		$class[] = "		";
		$class[] = "		";
		$class[] = "		return SOY2Action::SUCCESS;";
		$class[] = "	}";
		$class[] = "}";
		if(!empty($_POST)){
			$class[] = "";
			$class[] = "class ".$className."Form extends SOY2ActionForm{";
			$setter = array();
			foreach($_POST as $key => $value){
			$class[] = '	var $'.$key.';';
			$setter[] = '	';
			$setter[] = '	/**';
			$setter[] = '	 * @validator string {}';
			$setter[] = '	 */';
			$setter[] = '	function set'.ucwords($key).'($'.$key.'){';
			$setter[] = '		$this->'.$key.' = $'.$key.';';
			$setter[] = '	}';
			$setter[] = '	';
			}
			$class[] = implode("\n",$setter);
			$class[] = "}";
		}
		file_put_contents($fullPath,"<?php \n".implode("\n",$docComment) ."\n". implode("\n",$class)."\n?>");
	}
}
