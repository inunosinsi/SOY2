<?php

/*
 * PHPのバージョンによってsetcookieのオプションの値を変える
 */
function soy2_setcookie($key, $value=null, $opts=array()){
	$version = phpversion();
	$majorVersion = (int)substr($version, 0, strpos($version, "."));
	$minorVersion = (int)substr($version, strpos($version, ".") + 1);

	if(!isset($opts["expires"]) || !is_numeric($opts["expires"])) $opts["expires"] = time()-1;
	if(!isset($opts["secure"]) || !is_bool($opts["secure"])) $opts["secure"] = false;

	//php 7.3以降 samesiteの指定が出来る
	if($majorVersion >= 8 || ($majorVersion >= 7 && $minorVersion >= 3)){
		$opts["samesite"] = ($opts["secure"]) ? "Lax" : "None";
		setcookie($key, $value, $opts);
	}else{
		$path = (isset($opts["path"])) ? $opts["path"] : "";
		$domain = (isset($opts["domain"])) ? $opts["domain"] : "";
		$httponly = (isset($opts["httponly"]) && is_bool($opts["httponly"])) ? $opts["httponly"] : false;
		setcookie($key, $value , $opts["expires"], $path, $domain, $opts["secure"], $httponly);
	}
}
