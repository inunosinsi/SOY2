<?php

/**
 * realpath 末尾が必ず「/」で返値
 */
function soy2_realpath($dir){
	$path = realpath($dir);
	if(!$path)return $path;
	$path = str_replace("\\","/",$path);
	if(is_dir($path) && $path[strlen($path)-1] != "/")$path .= "/";
	return $path;
}
/**
 * URLの末尾をスラッシュで終わらせるか
 */
function soy2_realurl($url){
	//末尾が拡張子の場合はそのまま
	$arg = substr($url, strrpos($url, "/") + 1);
	if(!strlen($arg) || $arg == "_notfound") return $url;
	if(preg_match('/\.html$|\.htm$|\.xml$|\.css$|\.js$|\.json$|\.php$/i', $arg)) return $url;
	return $url . "/";
}
