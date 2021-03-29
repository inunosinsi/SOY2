<?php

/*
 * tokenを発行など
 */
function soy2_get_token(){
	if(!isset($_SESSION))@session_start();
	if(!isset($_SESSION["soy2_token"])){
		$_SESSION["soy2_token"] = soy2_generate_token();
	}
	return $_SESSION["soy2_token"];
}
function soy2_check_token(){
	if(!isset($_SESSION)) session_start();
	if(isset($_SESSION["soy2_token"]) AND isset($_REQUEST["soy2_token"])){
		if($_REQUEST["soy2_token"] === $_SESSION["soy2_token"]){
			$_SESSION["soy2_token"] = soy2_generate_token();
			return true;
		}
	}
	return false;
}
function soy2_generate_token(){
	return md5(mt_rand());
}
