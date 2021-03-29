<?php

function soy2_check_referer(){
	$referer = parse_url($_SERVER['HTTP_REFERER']);
	$port = (($referer["port"]) && ($referer["port"] != 80 || $referer["port"] != 443)) ? ":" . $referer["port"] : "";
	if($referer['host'] . $port !== $_SERVER['HTTP_HOST']) return false;

	$_path = $referer["path"];

	//pathinfoがある時は削除する
	$queryString = $_SERVER['QUERY_STRING'];
	if(is_numeric(strpos($queryString, "pathinfo"))){
		$array = explode("&", $queryString);
		$params = array();
		foreach($array as $value){
			$v = explode("=", $value);
			if($v[0] == "pathinfo") continue;
			$params[$v[0]] = $v[1];
		}

		$queryString = "";
		if(count($params)){
			foreach($params as $key => $v){
				if(strlen($queryString)) $queryString .= "&";
				$queryString .= $key . "=" . $v;
			}
		}
	}

	if(isset($queryString) && strlen($queryString)) $_path .= "?" . $queryString;
	return ($_path == $_SERVER['REQUEST_URI']);
}
