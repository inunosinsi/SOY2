<?php

/**
 * @package SOY2.SOY2HTML
 */
class HTMLCSSLink extends SOY2HTML{
	var $tag = "link";
	const SOY_TYPE = SOY2HTML::SKIP_BODY;
	var $link;
	function setLink($link){
		$this->link = $link;
	}
	function execute(){
		$this->setAttribute("href", (string)$this->link);
	}
	function getObject(){
		return $this->link;
	}
}
