<?php

/**
 * @package SOY2.SOY2Action
 */
class SOY2ActionBase{
	private $_classPath;
	protected function getClassPath(){
		if(is_null($this->_soy2_classPath)){
			$reflection = new ReflectionClass(get_class($this));
			$classFilePath = $reflection->getFileName();
			$this->_soy2_classPath = str_replace("\\", "/", $classFilePath);
		}
		return $this->_classPath;
	}
}
