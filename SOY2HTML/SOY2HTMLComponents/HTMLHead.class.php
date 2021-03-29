<?php

/**
 * @package SOY2.SOY2HTML
 * HTMLHeadコンポーネント
 *
 * title - タイトルを設定
 * isEraseHead - テンプレートにあるヘッドを削除するかどうか
 */
class HTMLHead extends SOY2HTML{
	var $tag = "head";
	var $title;
	var $isEraseHead = false;
	const SOY_TYPE = SOY2HTML::HTML_BODY;
	const HEAD_SCRIPT = "_script_";
	const HEAD_LINK = "_link_";
	const HEAD_META = "_meta_";
	function setTitle($title){
		$this->title = $title;
	}
	function getTitle(){
		return htmlspecialchars((string)$this->title,ENT_QUOTES,SOY2HTML::ENCODING);
	}
	function setIsEraseHead($boolean){
		$this->isEraseHead = (boolean)$boolean;
	}
	function getIsEraseHead(){
		return $this->isEraseHead;
	}
	protected static function &getHeads(){
		static $_array;
		if(!$_array){
			$_array = array(
				self::HEAD_SCRIPT => array(),
				self::HEAD_LINK => array(),
				self::HEAD_META => array()
			);
		}
		return $_array;
	}
	public static function addMeta($key,$array){
		$heads = &HTMLHead::getHeads();
		$heads[self::HEAD_META][$key] = $array;
	}
	public static function clearMeta($key){
		$heads = &HTMLHead::getHeads();
		$heads[self::HEAD_META][$key] = null;
		unset($heads[self::HEAD_META][$key]);
	}
	public static function addLink($key,$array){
		$heads = &HTMLHead::getHeads();
		$heads[self::HEAD_LINK][$key] = $array;
	}
	public static function clearLink($key){
		$heads = &HTMLHead::getHeads();
		$heads[self::HEAD_LINK][$key] = null;
		unset($heads[self::HEAD_LINK][$key]);
	}
	public static function addScript($key,$array){
		$heads = &HTMLHead::getHeads();
		$heads[self::HEAD_SCRIPT][$key] = $array;
	}
	public static function clearScript($key){
		$heads = &HTMLHead::getHeads();
		$heads[self::HEAD_SCRIPT][$key] = null;
		unset($heads[self::HEAD_SCRIPT][$key]);
	}
	function execute(){
		if($this->getIsModified() != true){
			return;
		}
		if($this->isEraseHead){
			$this->setInnerHTML("");
		}
		$innerHTML = $this->getInnerHTML();
		$innerHTML .= '<?php echo $'.$this->getPageParam().'["'.$this->getId().'"]["metas"]; ?>';
		$innerHTML .= '<?php echo $'.$this->getPageParam().'["'.$this->getId().'"]["links"]; ?>';
		$innerHTML .= '<?php echo $'.$this->getPageParam().'["'.$this->getId().'"]["scripts"]; ?>';
		$innerHTML .= "\n";
		if(preg_match('/<\/title>/i',$innerHTML)){
			$innerHTML = preg_replace('/<\/title>/i','<?php echo $'.$this->getPageParam().'["'.$this->getId().'"]["title"]; ?></title>',$innerHTML);
		}else{
			$innerHTML .= '<title><?php echo $'.$this->getPageParam().'["'.$this->getId().'"]["title"]; ?></title>'."\n";
		}
		$this->setInnerHTML($innerHTML);
	}
	function getObject(){
		return array(
			"title"   => $this->getTitle(),
			"metas"   => HTMLHead::getMetaHTML(),
			"links" => HTMLHead::getLinkHTML(),
			"scripts"   => HTMLHead::getScriptHTML(),
		);
	}
	function getMetaHTML(){
		$array = HTMLHead::getHeads();
		$metaArray = array();
		$metas = $array[self::HEAD_META];
		foreach($metas as $akey => $avalue){
			$attributes = array();
			foreach($avalue as $key => $value){
				$attributes[$key] = $key.'="'.htmlspecialchars((string)$value,ENT_QUOTES,SOY2HTML::ENCODING).'"';
			}
			$metaArray[$akey] = '<meta '.implode(" ",$attributes).'/>';
		}
		return  ((!empty($metaArray)) ? "\n" : "") . implode("\n",$metaArray);
	}
    function getScriptHTML(){
    	$array = HTMLHead::getHeads();
		$scriptArray = array();
		$scripts = $array[self::HEAD_SCRIPT];
		foreach($scripts as $akey => $avalue){
			$attributes = array();
			$body = "";
			foreach($avalue as $key => $value){
				$key = strtolower($key);
				if($key == "script"){
					$body = "<!--\n".$value."\n-->";//scriptの中身はHTML4ではCDATA, XHTML1だとPCDATA
					continue;
				}
				if($key == "src"){
				}
				$attributes[$key] = $key.'="'.htmlspecialchars((string)$value,ENT_QUOTES,SOY2HTML::ENCODING).'"';
			}
			if(!array_key_exists("type", $attributes)){
				$attributes["type"] = 'type="text/JavaScript"';
			}
			if(!array_key_exists("charset", $attributes)){
				$attributes["charset"] = 'charset="utf-8"';
			}
			$scriptArray[$akey] = '<script '.implode(" ",$attributes).'>'.( (strlen($body) >0) ? "\n".$body."\n" : "" ).'</script>';
		}
		return ((!empty($scriptArray)) ? "\n" : "") . implode("\n",$scriptArray);
    }
    function getLinkHTML(){
    	$array = HTMLHead::getHeads();
		$linkArray = array();
		$links = $array[self::HEAD_LINK];
		foreach($links as $akey => $avalue){
			$attributes = array();
			foreach($avalue as $key => $value){
				$attributes[$key] = $key.'="'.htmlspecialchars((string)$value,ENT_QUOTES,SOY2HTML::ENCODING).'"';
			}
			$linkArray[$akey] = '<link '.implode(" ",$attributes).'/>';
		}
		return ((!empty($linkArray)) ? "\n" : "") . implode("\n",$linkArray);
    }
}
