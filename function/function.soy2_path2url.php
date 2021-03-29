<?php

/**
 * PathをURLに変換
 */
function soy2_path2url($path){
	$path = soy2_realpath($path);
	$root = soy2_realpath($_SERVER["DOCUMENT_ROOT"]);
	$url = str_replace($root,"/",$path);
	return $url;
}
