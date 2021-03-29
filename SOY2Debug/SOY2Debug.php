<?php

/**
 * @package SOY2.SOY2Debug
 */
class SOY2Debug {
	/**
	 * デバッグWindowに文字を出力
	 */
	public static function trace(){
		$args = func_get_args();
		$socket = @fsockopen(self::host(),self::port(), $errno, $errstr,1);
		if(!$socket){
			return;
		}
		foreach($args as $var){
			fwrite($socket,var_export($var,true));
		}
		fclose($socket);
	}
	/**
	 * SOY2Debugのポートを設定。ディフォルトは9999
	 */
	public static function port($port = null){
		static $_port;
		if(is_null($_port)){
			$_port = 9999;
		}
		if($port){
			$_port = (int)$port;
		}
		return $_port;
	}
	/**
	 * SOY2Debugのホストを設定。ディフォルトはlocalhost
	 */
	public static function host($host = null){
		static $_host;
		if(is_null($_host)){
			$_host = "127.0.0.1";
		}
		if($host){
			$_host = $host;
		}
		return $_host;
	}
}
