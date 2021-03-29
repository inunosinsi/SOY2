<?php

/**
 * @package SOY2.SOY2Action
 */
class SOY2ActionForm{
	var $_errors = array();
	function getParamName(){}
	/**
	 * プロパティ名を指定してエラー表示
	 * @return boolean
	 */
	function isError($propName){
		return (isset($this->_errors[$propName]) && is_a($this->_errors[$propName],'ActionFormError')) ? true : false;
	}
	/**
	 * バリデートのエラーが存在するかどうか
	 * @return boolean
	 */
	function hasError(){
		return (count($this->_errors) > 0 ) ? true : false;
	}
	/**
	 * プロパティ名を指定してエラーメッセージを取得
	 * @return string エラーメッセージ
	 */
	function getErrorString($propName){
		$error = @$this->_errors[$propName];
		return ($error) ? $error->format() : null;
	}
	/**
	 * プロパティ名を指定してエラーオブジェクトを取得
	 * @return ActionFormError
	 */
	function getError($propName){
		$error = @$this->_errors[$propName];
		return $error;
	}
	/**
	 * エラーを設定
	 */
	function setError($propName,ActionFormError $error){
		$this->_errors[$propName] = $error;
	}
	/**
	 * エラーを全て取得
	 */
	function getErrors(){
		return $this->_errors;
	}
	/**
	 * @access public static
	 * フォームを作成
	 *
	 * @param フォーム名
	 * @param SOY2ActionRequest
	 */
	public static function createForm($formName,&$request){
		if(!class_exists($formName)){
			return new SOY2ActionForm();
		}
		$form = new $formName();
		$reflection = new ReflectionClass($formName);
		if($form->getParamName()){
			$param = $request->getParameter($form->getParamName());
		}else{
			$param = $request->getParameters();
		}
		if(!is_array($param)){
			return $form;
		}
		foreach($param as $key => $value){
			$param[strtolower($key)] = $value;
		}
		$reflectionProperties = $reflection->getProperties();
		foreach($reflectionProperties as $property){
			$funcName = "set".ucwords($property->getName());
			try{
				$method = $reflection->getMethod($funcName);
				if($method->isInternal()){
					continue;
				}
			}catch(Exception $e){
				continue;
			}
			$value = @$param[strtolower($property->getName())];
			$validator = SOY2ActionFormValidator::getValidator($param,$property,$method);
			if($validator){
				$value = $validator->validate($form,$property->getName(),$value,$validator->_isRequire);
			}
			$form->$funcName($value);
		}
		return $form;
	}
	function __toString(){
		$values = array();
		foreach($this as $key => $value){
			if($key == "_errors")continue;
			$values[$key] = $value;
		}
		return (string)http_build_query($values);
	}
}
/**
 * フォームバリデートのエラー保持クラス
 */
class ActionFormError{
	var $className;
	var $prop;
	var $validator;
	var $error;
	var $message;
	/**
	 * @param string $class ActionFormクラス名
	 * @param string $prop プロパティ名
	 * @param string $validator Validator名
	 * @param string $error エラー種別
	 */
	function __construct($class,$prop,$validator,$error,$message = null){
		$this->className = $class;
		$this->prop = $prop;
		$this->validator = $validator;
		$this->error = $error;
		$this->message = $message;
	}
	function getFormat(){
		return '$class->$propは$validatorの$error に違反しています';
	}
	function format(){
		if($this->message){
			return $this->message;
		}
		$format = $this->getFormat();
		$format = str_replace('$class',$this->className,$format);
		$format = str_replace('$prop',$this->prop,$format);
		$format = str_replace('$validator',$this->validator,$format);
		$format = str_replace('$error',$this->error,$format);
		return $format;
	}
	function __toString(){
		return $this->format();
	}
}
