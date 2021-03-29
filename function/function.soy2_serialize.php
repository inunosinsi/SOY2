<?php

/**
 * serializeしたあとにaddslashesを行う
 *
 * @param $var 配列やインスタンスなど
 */
function soy2_serialize($var){
	return addslashes(serialize($var));
}
/**
 * stripslashesしてからunserializeを行う
 *
 * @param $string soy2_serializeの出力する文字列
 */
function soy2_unserialize($string){
	return (is_string($string)) ? unserialize(stripslashes($string)) : null;
}
