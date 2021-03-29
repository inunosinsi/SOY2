<?php

/**
 * soy2_scanfiles
 * 特定のディレクトリの下にあるファイルを全て列挙
 */
function soy2_scanfiles($dir,$depth = -1){
	$res = array();
	$dir = soy2_realpath($dir);
	if($depth == 0)return $res;
	$files = soy2_scandir($dir);
	foreach($files as $file){
		if(is_dir($dir . $file)){
			$res = array_merge($res,soy2_scanfiles($dir . $file,($depth-1)));
		}else{
			$res[] = $dir . $file;
		}
	}
	return $res;
}
