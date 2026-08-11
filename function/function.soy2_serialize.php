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
function soy2_unserialize(string $string){
	// シリアル化されたオブジェクトを復元する時に、指定のオブジェクトでない場合は復元を禁止する
	static $allowed;
	if(is_null($allowed)){
		/**
		 * @usage 同一ディレクトリ内にallowedClasses.phpを作成
		 * シリアライズ化したおオブジェクトのデータを復元する時に復元を許可するオブジェクト名を配列で指定
		 * <?php
		 * $allowed = array();
		 */
		$allowed = array();
		if(file_exists(__DIR__."/allowedClasses.php")) include(__DIR__."/allowedClasses.php");
		if(!is_array($allowed) || !count($allowed)) $allowed = false;
	}
	if(!strlen($string)) return array();
	
	return unserialize(stripslashes($string), array(
		array("allowed_classes" => $allowed)
	));
}
