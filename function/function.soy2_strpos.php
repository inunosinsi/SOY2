<?php
/**
 * 文字列中に、ある部分文字列が最初に現れる場所を探す ヒットしない場合は-1を返す
 */
function soy2_strpos(string $haystack, string $needle, int $offset = 0){
	$res = strpos($haystack, $needle, $offset);
	return (is_numeric($res)) ? $res : -1;
}

/**
 * 文字列中に、ある部分文字列が最後に現れる場所を探す ヒットしない場合は-1を返す
 */
function soy2_strrpos(string $haystack, string $needle, int $offset = 0){
	$res = strrpos($haystack, $needle, $offset);
	return (is_numeric($res)) ? $res : -1;
<<<<<<< HEAD
}
=======
}

function soy2_stripos(string $haystack, string $needle, int $offset = 0){
	$res = stripos($haystack, $needle, $offset);
	return (is_numeric($res)) ? $res : -1;
}

function soy2_strripos(string $haystack, string $needle, int $offset = 0){
	$res = strripos($haystack, $needle, $offset);
	return (is_numeric($res)) ? $res : -1;
}
>>>>>>> 4202aacc0d58046855c6de4f139599a8d53f5c83
