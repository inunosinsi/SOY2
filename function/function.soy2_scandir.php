<?php

/**
 * 「.」から始まるディレクトリを取り除いたscandir
 *
 * @param $dir ディレクトリ
 */
function soy2_scandir($dir){
	$res = array();
	$files = scandir($dir);
	foreach($files as $row){
		if($row[0] == ".")continue;
		$res[] = $row;
	}
	return $res;
}
