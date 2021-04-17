<?php

/**
 * @package SOY2.SOY2HTML
 */
class SOYBodyComponentBase extends SOY2HTML{
	protected $_components = array();
	protected $_tmpList = array();
	protected $_childSoy2Prefix  = "soy";
	const SOY_TYPE = SOY2HTML::SOY_BODY;
    function add($id,$obj){
    	$obj->setId($id);
    	$obj->setParentObject($this);
    	$obj->init();
    	$this->_components[$id] = $obj;
    }
    /**
	 * コンポーネントクラスを指定してadd
	 *
	 * @param $id SoyId
	 * @param $className クラス名
	 * @param $array = array()　setter injection
	 * @see HTMLPage.add
	 */
	function createAdd($id,$className,$array = array()){
		if(!isset($array["soy2prefix"]) && $this->_childSoy2Prefix) {
			if(!is_array($array)) $array = array();
			$array["soy2prefix"] = $this->_childSoy2Prefix;
		}
		$this->add($id,SOY2HTMLFactory::createInstance($className,$array));
	}
	function getStartTag(){
    	return '<?php $'.$this->getId().' = $'.$this->getPageParam().'["'.$this->getId().'"]; ?>'.parent::getStartTag();
    }
	function getObject(){
    	return $this->_tmpList;
    }
	function execute(){
		$innerHTML = $this->getInnerHTML();
		$tmpList = array()
		foreach($this->_components as $key => $obj){
			if($obj instanceof HTMLPage){
				$obj->setParentPageParam($this->getId());
    		}
			$obj->setParentId($this->getId());
			$obj->setPageParam($this->getId());
			$obj->setContent($innerHTML);
			$obj->execute();
			$this->set($key,$obj,$tmpList);
			if($innerHTML){
				$innerHTML = $this->getContent($obj,$innerHTML);
			}
		}
		$this->_tmpList = $tmpList;
		$this->setInnerHTML($innerHTML);
	}
	function setChildSoy2Prefix($prefix){
		$this->_childSoy2Prefix = $prefix;
	}
	function isMerge(){
		return true;
	}

	/** PHP7.4対応 __call()の廃止 **/
	function addForm($id, $array=array()){self::createAdd($id, "HTMLForm", $array);}
	function addUploadForm($id, $array=array()){self::createAdd($id, "HTMLUploadForm", $array);}
	function addModel($id, $array=array()){self::createAdd($id, "HTMLModel", $array);}
	function addLabel($id, $array=array()){self::createAdd($id, "HTMLLabel", $array);}
	function addImage($id, $array=array()){self::createAdd($id, "HTMLImage", $array);}
	function addLink($id, $array=array()){self::createAdd($id, "HTMLLink", $array);}
	function addActionLink($id, $array=array()){self::createAdd($id, "HTMLActionLink", $array);}
	function addInput($id, $array=array()){
		self::createAdd($id, "HTMLInput", $array);
		self::addText($id, $array);
	}
	function addTextArea($id, $array=array()){
		self::createAdd($id, "HTMLTextArea", $array);
		self::addText($id, $array);
	}
	function addCheckBox($id, $array=array()){
		self::createAdd($id, "HTMLCheckBox", $array);
		self::addText($id, $array);
	}
	function addSelect($id, $array=array()){
		self::createAdd($id, "HTMLSelect", $array);
		self::addText($id, $array);
	}
	function addHidden($id, $array=array()){self::createAdd($id, "HTMLHidden", $array);}
	function addScript($id, $array=array()){self::createAdd($id, "HTMLScript", $array);}
	function addCSS($id, $array=array()){self::createAdd($id, "HTMLCSS", $array);}
	function addCSSLink($id, $array=array()){self::createAdd($id, "HTMLCSSLink", $array);}
	function addText($id, $array=array()){
		$new = array();
		if(isset($array["soy2prefix"]) && strlen($array["soy2prefix"])) $new["soy2prefix"] = $array["soy2prefix"];
		$new["text"] = (isset($array["value"])) ? $array["value"] : null;
		if(!strlen($new["text"]) && isset($array["text"]) && strlen($array["text"])) $new["text"] = $array["text"]; //addTextAreaの場合
		self::createAdd($id. "_text", "HTMLLabel", $new);
	}
	function addList($id, $array=array()){self::createAdd($id, "HTMLList", $array);}
}
/**
 * @package SOY2.SOY2HTML
 */
class SOY2HTMLElement extends SOY2HTML{
	const TEXT_ELEMENT = "_text_element_";
	var $_elements = array();
	var $_innerHTML;
	var $_tag;
	public static function &createElement($tag){
		return SOY2HTMLFactory::createInstance("SOY2HTMLElement",array(
			"elementTag" => $tag
		));
	}
	public static function &createTextElement($text){
		$ele =  SOY2HTMLFactory::createInstance("SOY2HTMLElement",array(
			"elementTag" => SOY2HTMLElement::TEXT_ELEMENT
		));
		$ele->_innerHTML = htmlspecialchars($text, ENT_QUOTES, SOY2HTML::ENCODING);
		return $ele;
	}
	public static function &createHtmlElement($html){
		$ele =  SOY2HTMLFactory::createInstance("SOY2HTMLElement",array(
			"elementTag" => SOY2HTMLElement::TEXT_ELEMENT
		));
		$ele->_innerHTML = $html;
		return $ele;
	}
	function setElementTag($tag){
		$this->_tag = $tag;
	}
	function getStartTag(){
		return "";
	}
	function getEndTag(){
		return "";
	}
	function setAttribute($key,$value,$flag = true){
		$this->_attribute[$key] = $value;
	}
	function getObject(){
		return $this->toHTML();
	}
	function toHTML(){
		$this->tag = $this->_tag;
		if($this->tag == SOY2HTMLElement::TEXT_ELEMENT){
			return $this->_innerHTML;
		}
		$html = SOY2HTML::getStartTag();
		$innerHTML = "";
		foreach($this->_elements as $ele){
			$innerHTML .= $ele->toHTML();
		}
		if(strlen($innerHTML)){
			$html .= $innerHTML;
			$html .= SOY2HTML::getEndTag();
		}else{
			$html = preg_replace('/>$/','/>',$html);
		}
		return $html;
	}
	function appendChild(SOY2HTMLElement &$ele){
		$this->_elements[] = $ele;
	}
}
/**
 * @package SOY2.SOY2HTML
 */
class SOY2HTMLStyle{
	var $_styles = array();
	function __construct($style = ""){
		$styles = explode(";",$style);
		foreach($styles as $str){
			if(!strstr($str,":"))continue;
			$array = explode(":",$str,2);
			$this->_styles[$array[0]] = $array[1];
		}
	}
	function __toString(){
		$style = '';
		foreach($this->_styles as $key => $value){
			if(!strlen($key) OR !strlen($value))continue;
			$style .= "$key:$value;";
		}
		return $style;
	}
	function __set($key, $value){
		//$key = preg_replace_callback('/[A-Z]/',create_function('$word','return \'-\'.strtolower($word[0]);'),$key);
		$key = preg_replace_callback('/[A-Z]/', function($word) use ($key) { return '-'.strtolower($word[0]); }, $key);
		$this->_styles[$key] = $value;
	}
	function __get($key){
		//$key = preg_replace_callback('/[A-Z]/',create_function('$word','return \'-\'.strtolower($word[0]);'),$key);
		$key = preg_replace_callback('/[A-Z]/', function($word) use ($key) { return '-'.strtolower($word[0]); }, $key);
		return $this->_styles[$key];
	}
}
/**
 * @package SOY2.SOY2HTML
 * 何もしないコンポーネント
 */
class HTMLModel extends SOY2HTML{
	const SOY_TYPE = SOY2HTML::HTML_BODY;
	function execute(){}
	function getObject(){
		return "";
	}
}
