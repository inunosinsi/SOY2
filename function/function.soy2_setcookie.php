<?php
/*
 * PHPのバージョンによってsetcookieのオプションの値を変える
 */
function soy2_setcookie($key, $value=null, $opts=array()){
	if(!count($opts)) $opts = session_get_cookie_params();	//optsが空の場合はセッションの設定を用いる
	if(is_null($value))	$opts["expires"] = time()-1;	//valueがnullの場合はクッキーを削除する
	if(isset($opts["lifetime"])) unset($opts["lifetime"]);	//lifetimeがある場合は削除

	if(!isset($opts["path"]) || !is_string($opts["path"]) || !strlen($opts["path"])) $opts["path"] = "/";
	if(!isset($opts["domain"]) || !is_string($opts["domain"]) || !strlen($opts["domain"])) $opts["domain"] = null;
	if(!isset($opts["expires"]) || !is_numeric($opts["expires"])) $opts["expires"] = 0;
	if(!isset($opts["httponly"]) || !is_bool($opts["httponly"])) $opts["httponly"] = true;
	if(!isset($opts["secure"]) || !is_bool($opts["secure"])) $opts["secure"] = (isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] == "on");

	$vArr = explode(".", phpversion());
	if(($vArr[0] >= 8 || ($vArr[0] >= 7 && $vArr[1] >= 3))){	//php 7.3以降 samesiteの指定が出来る
		if(!isset($opts["samesite"])) {	// SameSiteの値はセッションの設定から取得する
			$sessParams = session_get_cookie_params();
			$opts["samesite"] = (isset($sessParams["samesite"]) && strlen($sessParams["samesite"])) ? $sessParams["samesite"] : "Lax";
			unset($sessParams);
		}
		setcookie($key, $value, $opts);
	}else{
		setcookie($key, $value , $opts["expires"], $opts["path"], $opts["domain"], $opts["secure"], $opts["httponly"]);
	}
}
