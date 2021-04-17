<?php

/*
 * PHPのバージョンによってsetcookieのオプションの値を変える
 */
 function soy2_setcookie($key, $value=null, $opts=array()){
 	if(!isset($opts["expires"]) || !is_numeric($opts["expires"])) $opts["expires"] = time()-1;
 	if(!isset($opts["secure"]) || !is_bool($opts["secure"])) $opts["secure"] = false;

 	$vArr = explode(".", phpversion());
 	if(($vArr[0] >= 8 || ($vArr[0] >= 7 && $vArr[1] >= 3))){	//php 7.3以降 samesiteの指定が出来る
 		if(!isset($opts["samesite"])) $opts["samesite"] = ($opts["secure"]) ? "Lax" : "None";
 		setcookie($key, $value, $opts);
 	}else{
 		$path = (isset($opts["path"])) ? $opts["path"] : "";
 		$domain = (isset($opts["domain"])) ? $opts["domain"] : "";
 		$httponly = (isset($opts["httponly"]) && is_bool($opts["httponly"])) ? $opts["httponly"] : false;
 		setcookie($key, $value , $opts["expires"], $path, $domain, $opts["secure"], $httponly);
 	}
 }
