<?php

/**
 * @package SOY2.SOY2HTML
 */
class WebPage extends HTMLPage{
	const SOY_TYPE = SOY2HTML::HTML_BODY;
	function __construct(){
		$this->init();
		$this->prepare();
	}
	/**
	 * prepareMethodにおいて
	 * Post時はこちらが呼ばれます。
	 *
	 */
	function doPost(){}
	/**
	 * doPostがよばれるように拡張
	 */
	function prepare(){
		if($_SERVER['REQUEST_METHOD'] == 'POST'){
			$this->doPost();
		}
		parent::prepare();
	}
	function getLayout(){
		return "default.php";
	}
}
