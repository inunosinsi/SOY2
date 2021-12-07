<?php

/**
 * @package SOY2.SOY2HTML
 */
class HTMLImage extends SOY2HTML{
    var $src;
    const SOY_TYPE = SOY2HTML::SKIP_BODY;
    function setSrc($path){
    	$this->src = $path;
    }
    function setImagePath($path){
    	$this->setSrc($path);
    }
    function execute(){
    	$this->setAttribute("src", (string)$this->src);
    }
    function getObject(){
    	return $this->src;
    }
    function setAlt($alt){
    	$this->setAttribute("alt", (string)$alt);
    }
}
