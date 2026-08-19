<?php

/**
 * serializeしたあとにaddslashesを行う（HMAC署名付き）
 *
 * @param mixed $var 配列やインスタンスなど
 * @return string
 */
function soy2_serialize($var){
	$serialized = serialize($var);

	// HMAC（署名）を生成して付与
	$secretKey = defined('SOY2_SECRET_KEY') ? SOY2_SECRET_KEY : 'soy2';
	$hmac = hash_hmac('sha256', $serialized, $secretKey);
	return addslashes($hmac . ':' . $serialized);
}

/**
 * stripslashesしてからunserializeを行う（旧形式の互換性対応付き）
 *
 * @param string $string soy2_serializeの出力する文字列
 * @return mixed
 */
function soy2_unserialize(string $string){
	// allowed_classes の判定処理（静的保持）
	static $allowed;
	if(is_null($allowed)){
		/**
		 * @usage 同一ディレクトリ内にallowedClasses.phpを作成
		 * シリアライズ化したオブジェクトのデータを復元する時に復元を許可するオブジェクト名を配列で指定
		 * <?php
		 * $allowed = array();
		 */
		$allowed = array();
		if(file_exists(__DIR__."/allowedClasses.php")) include(__DIR__."/allowedClasses.php");
		if(!is_array($allowed) || !count($allowed)) $allowed = false;
	}
	$rawString = stripslashes($string);

	//署名(HMAC)
    $parts = explode(':', $rawString, 2);
    $secretKey = defined('SOY2_SECRET_KEY') ? SOY2_SECRET_KEY : 'soy2';

	// 区切り文字 ':' が含まれ、かつ先頭が64文字のハッシュ値（sha256）っぽいかチェック
	$hasHmac = (count($parts) === 2 && strlen($parts[0]) === 64 && ctype_xdigit($parts[0]));

	if($hasHmac){	// --- 【新形式（HMACあり）の処理】 ---
		list($hmac, $serialized) = $parts;
		$calculatedHmac = hash_hmac('sha256', $serialized, $secretKey);

 		// 改ざんチェック（不正なら即拒否）
		if (!hash_equals($calculatedHmac, $hmac)) return null;
		return unserialize($serialized, array("allowed_classes" => $allowed));
    }else{
        // --- 【旧形式（HMACなし）の処理】 ---
        return unserialize($rawString, array("allowed_classes" => $allowed));
    }
}
