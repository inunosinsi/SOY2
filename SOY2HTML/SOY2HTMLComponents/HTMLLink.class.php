<?php

/**
 * @package SOY2.SOY2HTML
 */
class HTMLLink extends HTMLLabel{
	var $tag = "a";
	const SOY_TYPE = SOY2HTML::HTML_BODY;
	var $link;
	var $target;
		function getStartTag(){
		return '<?php if(strlen($'.$this->getPageParam().'["'.$this->getId().'_attribute"]["href"])>0){ ?>' .
			parent::getStartTag() .
			'<?php } ?>';
	}
	function getEndTag(){
		return '<?php if(strlen($'.$this->getPageParam().'["'.$this->getId().'_attribute"]["href"])>0){ ?>' .
			parent::getEndTag() .
			'<?php } ?>';
	}
	function setLink($link){
		$this->link = $link;
	}
	function setTarget($target){
		$this->target = $target;
	}
	function execute(){
		if(!is_null($this->text)){
			parent::execute();
		}
		$suffix = $this->getAttribute($this->_soy2_prefix . ":suffix");
		if($suffix) $this->link .= $suffix;

		$this->setAttribute("href",(string)$this->link);
		if(is_string($this->target) && strlen($this->target)){
			$this->setAttribute("target",$this->target);
		}elseif(isset($this->target)){
			$this->clearAttribute("target");
		}
	}
	function getObject(){
		if(!is_null($this->text)){
			return parent::getObject();
		}
		return $this->link;
	}
}
/**
 * @package SOY2.SOY2HTML
 *
 * @see function soy2_get_token
 */
class HTMLActionLink extends HTMLLink{
	function execute(){
		if(!is_null($this->text)){
			HTMLLabel::execute();
		}
		$link = $this->link;
		if(!is_string($link)) $link = "";
		if(is_bool(strpos($link,"?"))){
			$link .= "?";
		}else{
			$link .= "&";
		}
		$link .= "soy2_token=" . soy2_get_token();
		$this->setAttribute("href",$link);
	}
}
