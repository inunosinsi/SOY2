<?php

/**
 * @package SOY2.SOY2Action
 */
class SOY2ActionFormValidator_NumberValidator extends SOY2ActionFormValidator{
	var $max;
	var $min;
	function __construct($obj){
		$this->max = @$obj->max;
		$this->min = @$obj->min;
	}
	function validate(SOY2ActionForm &$form,$propName,$value,$require){
		if($require && is_null($value)){
			$form->setError($propName,new ActionFormError(get_class($form),$propName,get_class($this),"require",$this->getMessage("require")));
		}
		if(!$require && is_null($value)){
			return null;
		}
		if(!is_numeric($value)){
			$form->setError($propName,new ActionFormError(get_class($form),$propName,get_class($this),"type",$this->getMessage("type")));
		}
		if(isset($this->max) && (int)$this->max < (int)$value){
			$form->setError($propName,new ActionFormError(get_class($form),$propName,get_class($this),"max",$this->getMessage("max")));
		}
		if(isset($this->min) && (int)$this->min > (int)$value){
			$form->setError($propName,new ActionFormError(get_class($form),$propName,get_class($this),"min",$this->getMessage("min")));
		}
		return $value;
	}
}
/**
 * @package SOY2.SOY2Action
 */
class SOY2ActionFormValidator_StringValidator extends SOY2ActionFormValidator{
	var $max;
	var $min;
	var $regex;
	function __construct($obj){
		$this->max = @$obj->max;
		$this->min = @$obj->min;
		$this->regex = @$obj->regex;
	}
	function validate(SOY2ActionForm &$form,$propName,$value,$require){
		if($require && strlen($value) < 1){
			$form->setError($propName,new ActionFormError(get_class($form),$propName,get_class($this),"require",$this->getMessage("require")));
		}
		if(!$require && strlen($value) < 1){
			return null;
		}
		if(isset($this->max) && $this->max < strlen($value)){
			$form->setError($propName,new ActionFormError(get_class($form),$propName,get_class($this),"max",$this->getMessage("max")));
		}
		if(isset($this->min) && $this->min > strlen($value)){
			$form->setError($propName,new ActionFormError(get_class($form),$propName,get_class($this),"min",$this->getMessage("min")));
		}
		if(isset($this->regex) && !preg_match("/".$this->regex."/",$value)){
			$form->setError($propName,new ActionFormError(get_class($form),$propName,get_class($this),"regex",$this->getMessage("regex")));
		}
		return $value;
	}
}
