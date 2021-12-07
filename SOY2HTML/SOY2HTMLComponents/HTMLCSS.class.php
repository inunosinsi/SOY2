<?php
/**
 * @package SOY2.SOY2HTML
 */
class HTMLCSS extends SOY2HTML{
	var $tag = "style";
	const SOY_TYPE = SOY2HTML::HTML_BODY;
	var $text = "";
	function execute(){
		$this->setAttribute("type","text/css");
		parent::execute();
	}
	function setStyle($text){
		$this->text = $text;
	}
	function getObject(){
		return $this->text;//htmlspecialchars((string)$this->text,ENT_QUOTES,SOY2HTML::ENCODING)
	}
}
