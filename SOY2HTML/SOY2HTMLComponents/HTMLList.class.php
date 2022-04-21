<?php

/**
 * @package SOY2.SOY2HTML
 */
class HTMLList extends SOYBodyComponentBase{
	var $list = array();
	var $_list = array();
	var $htmls = array();
	var $_includeParentTag = true;
	protected $_notMerge = false;
	function setList($list){
		if(!is_array($list)){
			$list = (array)$list;
		}
		$this->list = $list;
	}
	function getStartTag(){
		$this->_includeParentTag = $this->getAttribute("includeParentTag");
		$this->clearAttribute("includeParentTag");
		if($this->_includeParentTag){
		 	return SOY2HTML::getStartTag() . "\n".'<?php $'.$this->getId().'_counter = -1; foreach($'.$this->getPageParam().'["'.$this->getId().'"] as $key => $'.$this->getId().'){ $'.$this->getId().'_counter++; ?>';
		}else{
		 return '<?php $'.$this->getId().'_counter = -1;foreach($'.$this->getPageParam().'["'.$this->getId().'"] as $key => $'.$this->getId().'){ $'.$this->getId().'_counter++; ?>'
			 	. SOY2HTML::getStartTag();
		}
	}
	function getEndTag(){
		if($this->_includeParentTag){
		 	return '<?php } ?>' . "\n" .SOY2HTML::getEndTag();
		 }else{
			return SOY2HTML::getEndTag() . '<?php } ?>';
		}
	}
	function getObject(){
		return $this->_list;
	}
	function execute(){
		$innerHTML = $this->getInnerHTML();
		$old = error_reporting(E_ALL ^ E_NOTICE ^ E_WARNING);
		$this->populateItemImpl(new HTMLList_DummyObject(),null,-1,count($this->list));
		$this->addLabel("index", array("text" => ""));
		$this->createAdd("loop","HTMLList_LoopModel",array("counter" => -1));
		$this->addModel("at_first", array("visible" => false));
		$this->addModel("not_first", array("visible" => false));
		$this->addModel("at_last", array("visible" => false));
		$this->addModel("not_last", array("visible" => false));
		error_reporting($old);
		parent::execute();
		$counter = 0;
		$length = count($this->list);
		if($length > 0){
			foreach($this->list as $listKey => $listObj){
				$counter++;
				$tmpList = array();
				$res = $this->populateItemImpl($listObj,$listKey,$counter,$length);
				$this->addLabel("index", array("text" => $counter));
				$this->createAdd("loop","HTMLList_LoopModel",array("counter" => $counter));
				$this->addModel("at_first", array("visible" => $counter == 1));
				$this->addModel("not_first", array("visible" => $counter != 1));
				$this->addModel("at_last", array("visible" => $counter == $length));
				$this->addModel("not_last", array("visible" => $counter != $length));
				if($res === false)continue;
				foreach($this->_components as $key => $obj){
					$obj->setContent($innerHTML);
					$obj->execute();
					$this->set($key,$obj,$tmpList);
				}
				$this->_list[$listKey] = $tmpList;//WebPage::getPage($this->getParentId());
			}
		}
	}
	function isMerge(){
		return false;
	}
	function populateItemImpl($entity,$key,$counter,$length){
		if(method_exists($this,"populateItem")){
			return $this->populateItem($entity,$key,$counter,$length);
		}
		if($this->_soy2_functions["populateItem"]){
			return $this->__call("populateItem",array($entity,$key,$counter,$length));
		}
		return null;
	}
}
/**
 * @package SOY2.SOY2HTML
 */
class HTMLList_DummyObject extends ArrayObject{
	function __call($func,$args){
		return new HTMLList_DummyObject();
	}
	function __get($key){
		return new HTMLList_DummyObject();
	}
	function __toString(){
		return "";
	}
}
/**
 * @package SOY2.SOY2HTML
 */
class HTMLList_LoopModel extends HTMLModel{
	private $counter;
	function getStartTag(){
		$step = (int)$this->getAttribute("step");
		$func ='<?php $'.$this->getId().'_loop_visible = !(boolean)'.$step.';' .
				'if('.$step.')$'.$this->getId().'_loop_visible=(($'.$this->getPageParam().'_counter+1) % '.$step.' === 0); ' .
		 		'if($'.$this->getId().'_loop_visible){ ?>';
		$res = $func . parent::getStartTag();
		return $res;
	}
	function getEndTag(){
		return parent::getEndTag() . "<?php } ?>";
	}
	function setCounter($counter){
		$this->counter = $counter;
	}
	function getCounter(){
		return $this->counter;
	}
}
