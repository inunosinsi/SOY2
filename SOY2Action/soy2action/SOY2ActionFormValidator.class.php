<?php

/**
 * @package SOY2.SOY2Action
 *
 * ヴァリデート
 *
 * 使い方
 * setterのコメントに
 * @validator ＜形式＞ ＜オプション＞
 *
 * オプションは省略化
 * ヴァリデーターによって設定出来るオプションはさまざま
 *
 */
abstract class SOY2ActionFormValidator{
	var $_isRequire;
	var $_message;
	/**
	 * @access public
	 * 対応するバリデータを取得。
	 * 無かったらnullを返す。
	 *
	 * @return SOY2ActionFormValidator
	 */
	public static function getValidator($param,ReflectionProperty &$property,ReflectionMethod &$reflectionMethod){
		$comment = $reflectionMethod->getDocComment();
		$comment = preg_replace('/^\s*\*|^\/\*\*|\/$|\n/m','',$comment);
		$tmp = array();
		if(!preg_match('/@validator\s+([^\s]*)(?:\s+(\{.*\}))?/m',$comment,$tmp))return null;
		$type = $tmp[1];
		$json = @$tmp[2];
		$class = "SOY2ActionFormValidator_".ucwords($type)."Validator";
		if(!class_exists($class)){
			throw new Exception("[SOY2ActionFormValidator]".$class." is not defined.");
		}
		$obj = json_decode($json);
		$validator = new $class($obj,$param);
		$validator->_isRequire = (isset($obj->require)) ? $obj->require : false;
		if(!empty($obj->message)){
			$validator->_message = (array)$obj->message;
		}
		return $validator;
	}
	function getMessage($error){
		return (isset($this->_message[$error])) ? $this->_message[$error] : null;
	}
	abstract function validate(SOY2ActionForm &$form,$propName,$value,$isRequire);
}
