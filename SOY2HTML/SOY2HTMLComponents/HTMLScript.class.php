<?php

/**
 * @package SOY2.SOY2HTML
 */
class HTMLScript extends SOY2HTML{
    var $tag = "script";
    const SOY_TYPE = SOY2HTML::HTML_BODY;
    var $script = "";
    var $type = "text/javascript";
    function setScript($script){
    	$this->script = $script;
    }
    function setSrc($src){
    	$this->setAttribute("src",$src);
    }
    function execute(){
    	$this->setAttribute("type",$this->type);
    	parent::execute();
    }
    function setType($type){
    	$this->type = $type;
    }
    function getObject(){
    	if(strlen($this->script)){
    		return "<!--\n".$this->script."\n-->";//htmlspecialchars((string)$this->script,ENT_QUOTES,SOY2HTML::ENCODING)
    	}else{
    		return $this->script;
    	}
    }
}
