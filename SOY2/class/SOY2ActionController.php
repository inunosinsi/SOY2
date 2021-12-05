<?php

/**
 * @package SOY2.controller
 *
 * mod_rewriteを使ったフロントコントローラー
 */
class SOY2ActionController implements SOY2_Controller{
	/**
	 * 準備
	 */
	public static function init(array $options=array()){}
	/**
	 * 実行
	 */
	public static function run(){}
	/**
	 * フロントコントローラー取得
	 */
	public static function getInstance(){}
	/**
	 * 他のURLへ移動
	 */
	public static function jump(string $url){}
	/**
	 * 現在のURLを再読込（queryは変更可能）
	 */
	public static function reload(string $query=""){}
		
	private $path;
	private $arguments = array();
}
