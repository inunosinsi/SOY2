<?php

/**
 * @package SOY2.SOY2HTML
 */
class HTMLLabel extends SOY2HTML{
	const SOY_TYPE = SOY2HTML::HTML_BODY;
	var $text;
	private $width;
	private $isFolding;
	private $foldingTag = "<br />";
	private $isHtml = false;
	private $suffix = "...";
	function setText($text){
		$this->text = (string)$text;
	}
	function getText(){
		return (string)$this->text;
	}
	function setHtml($html){
		$this->text = (is_string($html)) ? (string)$html : "";
		$this->isHtml = true;
	}
	function getObject(){
		$text = $this->getText();
		if($this->isHtml){
			return $text;
		}else{
			if(is_numeric($this->width) && $this->width > 0){
				if($this->isFolding != true){
					$width = max(0, $this->width - mb_strwidth($this->suffix));
					$short_text = mb_strimwidth($text,0,$width);
		    		if(mb_strwidth($short_text) < mb_strwidth($text)){
				    	$short_text .= $this->suffix;
		    		}
		    		if(mb_strwidth($short_text) < mb_strwidth($text)){
		    			$text = $short_text;
		    		}
					return htmlspecialchars($text,ENT_QUOTES,SOY2HTML::ENCODING);
				}else{
					$folded = "";
					while(strlen($text)>0){
						$tmp = mb_strimwidth($text, 0, $this->width);
						$text = mb_substr($text, mb_strlen($tmp));
						$folded .= htmlspecialchars($tmp,ENT_QUOTES,SOY2HTML::ENCODING);
						if(strlen($text) >0) $folded .= $this->foldingTag;
					}
					return $folded;
				}
			}else{
				return htmlspecialchars($text,ENT_QUOTES,SOY2HTML::ENCODING);
			}
		}
	}
	function setWidth($width){
		$this->width = $width;
	}
	function setIsFolding($flag){
		$this->isFolding = (boolean)$flag;
	}
	function setFoldingTag($tag){
		$this->foldingTag = $tag;
	}
	public function getSuffix() {
		return $this->suffix;
	}
	public function setSuffix($suffix) {
		$this->suffix = $suffix;
	}
}
