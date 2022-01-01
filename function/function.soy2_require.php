<?php

/**
 * include file(replace require(*))
 * @param path
 * @param when true do not return boolean value
 * @return boolean
 */
function soy2_require(string $file, bool $isThrowException=false){
	$res = (boolean)@include_once($file);
	if($isThrowException && !$res)throw new Exception("File Not Found:" . $file);
	return $res;
}
