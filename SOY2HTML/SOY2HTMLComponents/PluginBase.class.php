<?php

/**
 * @package SOY2.SOY2HTML
 */
class PluginBase extends SOY2HTML{
	const SOY_TYPE = SOY2HTML::HTML_BODY;
	protected $soyValue = "";
	protected $parent = null;
	function setSoyValue($value){
		$this->soyValue = $value;
	}
	function setParent($page){
		$this->parent = $page;
	}
	function execute(){
    	if($this->functionExists("executePlugin")){
    		$this->__call("executePlugin",array($this->soyValue));
    	}else{
    		$this->executePlugin($this->soyValue);
    	}
    }
    function getObject(){
    }
    function getPlugin($param){
    	$plugin = SOY2HTMLPlugin::getPlugin($param);
    	if(is_null($plugin))return $plugin;
    	if(is_object($plugin)){
    		return $plugin;
    	}
    	return new $plugin();
    }
    function executePlugin($soyValue){
    }
    function getVisbleScript(){
    	return array("","");
    }
}
/**
 * @package SOY2.SOY2HTML
 */
class SOY2HTMLPlugin{
	private static function &getPlugins(){
		static $_static;
		if(is_null($_static)){
			$_static = array();
		}
		return $_static;
	}
	public static function addPlugin($key,$value){
		$plugins = &SOY2HTMLPlugin::getPlugins();
		$plugins[$key] = $value;
	}
	public static function getPlugin($key){
		$plugins = SOY2HTMLPlugin::getPlugins();
		return (isset($plugins[$key])) ? $plugins[$key] : null;
	}
	public static function removePlugin($key){
		$plugins = &SOY2HTMLPlugin::getPlugins();
		@$plugins[$key] = null;
	}
	public static function length(){
		return count(SOY2HTMLPlugin::getPlugins());
	}
}
/**
 * @package SOY2.SOY2HTML
 */
class PagePlugin extends PluginBase{
	var $isOverWrite = false;
	function executePlugin($soyValue){
    	$innerHTML = array();
    	$innerHTML[] = '<?php if(!isset($'.$this->parent->getPageParam().'["page_'.md5($soyValue).'"])){ ?>';
    	$innerHTML[] = '<?php $'.$this->parent->getPageParam().'["page_'.md5($soyValue).'"] = PagePlugin::loadWebPage("'.$this->parent->getId().'","'.$this->parent->getClassPath().'","'.$soyValue.'",__FILE__); ?>';
    	$innerHTML[] = '<?php } ?>';
    	$innerHTML[] = '<?php echo $'.$this->parent->getPageParam().'["page_'.md5($soyValue).'"]; ?>';
    	$this->setInnerHTML(implode("\n",$innerHTML));
    }
    function getStartTag(){
    	if($this->getAttribute("isOverWrite")){
    		$this->isOverWrite = (boolean)$this->getAttribute("isOverWrite");
    		$this->clearAttribute("isOverWrite");
    	}
    	if($this->isOverWrite){
    		return "";
    	}
    	return parent::getStartTag();
    }
    function getEndTag(){
    	if($this->isOverWrite){
    		return "";
    	}
    	return parent::getEndTag();
    }
    public static function loadWebPage($parentId,$parentClassPath,$className,$parentFilePath){
    	$id = "page_".md5($className.$parentClassPath);
    	$class = SOY2HTMLFactory::pageExists($className);
    	$filePath = str_replace("\\","/",realpath(SOY2HTMLConfig::PageDir().str_replace(".","/",$className).".class.php"));
		$parentPageParam = "";
    	$cachFilePath = SOY2HTMLConfig::CacheDir().
			SOY2HTMLConfig::getOption("cache_prefix") .
			"cache_" . $class .'_'. $id .'_'. $parentPageParam
			."_". md5($filePath)
			."_".SOY2HTMLConfig::Language()
			.".html.php";
		if(file_exists($cachFilePath) && filemtime($cachFilePath) < filemtime($parentFilePath)){
			unlink($cachFilePath);
		}
    	$webPage = SOY2HTMLFactory::createInstance($className);
    	$webPage->setId($id);
    	$webPage->setPageParam($id);
    	$webPage->setParentId($parentId);
    	$webPage->setParentPageParam($parentPageParam);
    	$webPage->execute();
    	$value = $webPage->getObject();
    	return $value;
    }
}
/**
 * @package SOY2.SOY2HTML
 */
class LinkPlugin extends PluginBase{
	function executePlugin($soyValue){
		if(strpos($soyValue,"/") !== false){
			$this->_attribute["href"] = SOY2PageController::createRelativeLink($soyValue);
		}else{
			$this->_attribute["href"] = SOY2PageController::createLink($soyValue);
		}
	}
}
/**
 * @package SOY2.SOY2HTML
 */
class SrcPlugin extends PluginBase{
	function executePlugin($soyValue){
		if(strpos($soyValue,"/") !== false){
			$this->_attribute["src"] = SOY2PageController::createRelativeLink($soyValue);
		}else{
			$this->_attribute["src"] = SOY2PageController::createLink($soyValue);
		}
	}
}
/**
 * @package SOY2.SOY2HTML
 */
class ActionPlugin extends PluginBase{
	function executePlugin($soyValue){
		if(strpos($soyValue,"/") !== false){
			$this->_attribute["action"] = SOY2PageController::createRelativeLink($soyValue);
		}else{
			$this->_attribute["action"] = SOY2PageController::createLink($soyValue);
		}
	}
}
/**
 * @package SOY2.SOY2HTML
 */
class DisplayPlugin extends PluginBase{
	var $soyValue;
	function executePlugin($soyValue){
		$this->soyValue = $soyValue;
	}
	function getStartTag(){
		return '<?php if(DisplayPlugin::toggle("'.$this->soyValue.'")){ ?>'. parent::getStartTag();
	}
	function getEndTag(){
		return  parent::getEndTag() . '<?php } ?>';
	}
	public static function visible($soyValue){
		DisplayPlugin::toggle($soyValue,1);
	}
	public static function toggle($soyValue,$flag = null){
		static $_flags;
		if(!$_flags){
			$_flags = array();
		}
		if(!is_null($flag)){
			$_flags[$soyValue] = $flag;
		}
		return (isset($_flags[$soyValue])) ? $_flags[$soyValue] : true;
	}
	public static function hide($soyValue){
		DisplayPlugin::toggle($soyValue,0);
	}
}
/**
 * @package SOY2.SOY2HTML
 */
class PanelPlugin extends PluginBase{
	var $soyValue;
	var $flag = true;
	function executePlugin($soyValue){
		$panels = &PanelPlugin::getPanels();
		$this->soyValue = $soyValue;
		if(in_array($soyValue,$panels)){
			$this->flag = true;
			$this->setInnerHTML("");
		}else{
			$panels[] = $soyValue;
			$this->flag = false;
		}
	}
	public static function &getPanels(){
		static $_panels;
		if(is_null($_panels)){
			$_panels = array();
		}
		return $_panels;
	}
	function getStartTag(){
		$html = array();
		if($this->flag){
			$html[] = '<?php echo $_panel_plugin_'.$this->soyValue.'; ?>';
		}else{
			$html[] = '<?php ob_start(); ?>';
		}
		return parent::getStartTag() . implode("\n",$html);
	}
	function getEndTag(){
		$html = array();
		if($this->flag){
		}else{
			$html[] = '<?php $_panel_plugin_'.$this->soyValue.' = ob_get_contents(); ?>';
			$html[] = '<?php ob_end_clean(); ?>';
			$html[] = '<?php echo $_panel_plugin_'.$this->soyValue.'; ?>';
		}
		return implode("\n",$html) . parent::getEndTag();
	}
}
/**
 * @package SOY2.SOY2HTML
 */
class IgnorePlugin extends PluginBase{
	function getStartTag(){
		return '<?php /* ?>';
	}
	function getEndTag(){
		return  '<?php */ ?>';
	}
}
/**
 * @package SOY2HTML
 */
class SOY2HTML_ControllPlugin extends PluginBase{
	function getStartTag(){
		$condition = $this->getAttribute("condition");
		$this->clearAttribute("condition");
		return '<?php $condition = ControllPlugin::checkCondition("'.$this->soyValue.'","'.htmlspecialchars($condition,ENT_QUOTES).'");' .
				'if($condition){ ?>' . parent::getStartTag();
	}
	public static function checkCondition($type,$key){
		switch($type){
			case "if":
			default:
				$res = false;
				if(strlen($key)>0){
					eval('$res = ('.$key.');');
				}
				return $res;
				break;
		}
		return false;
	}
	function getEndTag(){
		return  parent::getEndTag() . "<?php } ?>";
	}
}
