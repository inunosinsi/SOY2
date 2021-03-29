<?php

class HTMLTreeComponent_Child extends HTMLModel{
	private $func = "";
	function getStartTag(){
		$tag = parent::getStartTag();
		return
			'<?php foreach($'.$this->getParentId().'_child as $'.$this->getParentId() . "_" . $this->getId().'_child){ ?>' .
			$tag . "";
	}
	function getEndTag(){
		$tag = parent::getEndTag();
		return $tag . "<?php } /* end of loop of ".$this->getId()."*/ ?>" . "\n";;
	}
	function execute(){
		$this->_soy2_innerHTML = "<?php ".$this->func.'($'.$this->getParentId() . "_" . $this->getId() . '_child); ?>';
	}
	function getFunc() {
		return $this->func;
	}
	function setFunc($func) {
		$this->func = $func;
	}
}
class HTMLTreeComponent_ChildWrap extends HTMLModel{
	function getStartTag(){
		$tag = parent::getStartTag();
		return
			'<?php if(count($'.$this->getParentId().'_child) > 0){ ?>' .
			$tag . "\n";
	}
	function getEndTag(){
		$tag = parent::getEndTag();
		return $tag . "<?php } /* end of ".$this->getId()."*/ ?>" . "\n";;
	}
}
class HTMLTree extends SOYBodyComponentBase{
	public $tree;
	public $list;
	public $_funcName;
	private $_list = array();
    function getStartTag(){
		$tag = parent::getStartTag();
		$tag .= "<?php function " . $this->getFuncName() . '($'.$this->getId().'){ /* echo "<pre style=text-align:left;>";print_r($'.$this->getId().');echo "</pre>" */;' .
				'$'.$this->getId().'_child = $'.$this->getId().'["child"];' .
				'$'.$this->getId().' = $'.$this->getId().'["object"];' .
				"?>";
		return $tag;
	}
	function getEndTag(){
		$tag = "<?php } /* end of func ".$this->getFuncName()." */ ?>";
		$tag .= parent::getEndTag();
		$tag .= '<?php foreach($'.$this->getPageParam().'["'.$this->getId().'"] as $'.$this->getId().'_key => $'.$this->getId().'){' .
					$this->getFuncName() . '($'.$this->getId(). '); ' .
			    '} ?>';
		return $tag;
	}
	function getObject(){
		return $this->_list;
	}
	function getFuncName(){
		if(!$this->_funcName){
			$this->_funcName = "_soy2html_tree_component_" . $this->getId() . "_" . time();
		}
		return $this->_funcName;
	}
	function execute(){
		$innerHTML = $this->getInnerHTML();
		$this->populateItemImpl(new HTMLList_DummyObject,-1,-1);
		$this->createAdd("tree","HTMLTreeComponent_Child",array("func" => $this->getFuncName()));
		$this->createAdd("tree_child","HTMLTreeComponent_ChildWrap");
		parent::execute();
		$this->_list = $this->parseTree($this->tree);
	}
	function parseTree($tree,$depth = 0){
		$innerHTML = $this->getInnerHTML();
		$list = array();
		$counter = 0;
		foreach($tree as $treeKey => $treeArray){
			$isLast = false;
			$counter++;
			if(!is_array($treeArray)){
				$isLast = (count($tree) == $counter);
				$treeKey = $treeArray;
				$treeArray = array();
			}else{
				$isLast = (count($treeArray) < 1);
			}
			if(!isset($this->list[$treeKey]))continue;
			$tmpList = array();
			$listObj = $this->list[$treeKey];
			$new_depth = $depth + 1;
			$res = $this->populateItemImpl($listObj,$treeKey,$new_depth,$isLast);
			if($res === false)continue;
			foreach($this->_components as $key => $obj){
				$obj->setContent($innerHTML);
				$obj->execute();
				$this->set($key,$obj,$tmpList);
			}
			$child = (is_array($treeArray)) ? $this->parseTree($treeArray,$new_depth) : array();
			$list[$treeKey] = array(
				"object" => $tmpList,
				"child" => $child
			);
		}
		return $list;
	}
	function populateItemImpl($entity,$key,$depth,$isLast = false){
		if(method_exists($this,"populateItem")){
			return $this->populateItem($entity,$key,$depth,$isLast);
		}
		if($this->_soy2_functions["populateItem"]){
			return $this->__call("populateItem",array($entity,$key,$depth,$isLast));
		}
		return null;
	}
    function getList() {
    	return $this->list;
    }
    function setList($list) {
    	$this->list = $list;
    }
    function getTree() {
    	return $this->tree;
    }
    function setTree($tree) {
    	$this->tree = $tree;
    }
    function setTreeIds($ids){
    	$list = array();
    	$tmp = null;
    	foreach($ids as $id){
    		if(!is_null($tmp)){
    			$tmp[$id] = array();
    			$tmp = &$tmp[$id];
    		}else{
    			$list[$id] = array();
    			$tmp = &$list[$id];
    		}
    	}
    	$this->setTree($list);
    }
}
