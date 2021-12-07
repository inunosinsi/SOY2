<?php

/**
 * ページャーコンポーネント
 */
class HTMLPager extends SOYBodyComponentBase{
	private $link;
	private $page = 1;
	private $start = 0;
	private $end = 0;
	private $total = 0;
	private $query = "";
	private $pagerCount = 10;
	private $limit = 0;
    function execute(){
    	if($this->_soy2_parent){
			$this->_soy2_parent->addLabel("count_start", array(
				"text" => $this->getStart()
			));
			$this->_soy2_parent->addLabel("count_end", array(
				"text" => $this->getEnd()
			));
			$this->_soy2_parent->addLabel("count_max", array(
				"text" => $this->getTotal()
			));
    	}
		$next = $this->getNextParam();
		$this->addLink("next_link", $next);
		$this->addLink("next_link_wrap", array("visible" => $next["visible"]));
		$prev = $this->getPrevParam();
		$this->addLink("prev_link", $prev);
		$this->addModel("prev_link_wrap", array("visible" => $prev["visible"]));
		$this->createAdd("pager_list","SOY2HTMLPager_List",$this->getPagerParam());
		$this->addForm("pager_jump", array(
			"method" => "get",
			"action" => $this->getLink()
		));
		$this->addSelect("pager_select", array(
			"name" => "page",
			"options" => $this->getSelectArray(),
			"selected" => $this->getPage(),
			"onchange" => "location.href=this.parentNode.action+this.options[this.selectedIndex].value"
		));
    	parent::execute();
    }
    function getNextParam(){
		$link = ($this->total > $this->end) ? $this->link . ($this->page + 1) : $this->link . $this->page;
		if(strlen($this->getQuery()))$link .= "?" . $this->getQuery();
		return array(
    		"link" => $link,
    		"class" => ($this->total <= $this->end) ? "pager_disable" : "",
    		"visible" => ($this->total > $this->end)
    	);
	}
	function getPrevParam(){
		$link = ($this->page > 1) ? $this->link . ($this->page - 1) : $this->link . ($this->page);
		if(strlen($this->getQuery()))$link .= "?" . $this->getQuery();
		return array(
    		"link" => $link,
    		"class" => ($this->page <= 1) ? "pager_disable" : "",
    		"visible" => ($this->page > 1)
    	);
	}
	function getPagerParam(){
    	if($this->pagerCount < 0){
    		$pagers = range(
    			1,$this->getLastPageNum()
    		);
    	}else{
    		if($this->getLastPageNum() <= $this->pagerCount){
	    		$pagers = range(1, $this->getLastPageNum());
    		}else{
	    		$pagers = range(
		    		max(1,                 min($this->page - floor($this->pagerCount/2), $this->getLastPageNum() - $this->pagerCount)),
		    		max($this->pagerCount, min($this->page + ceil($this->pagerCount/2) -1, $this->getLastPageNum()))
		    	);
    		}
    	}
		return array(
    		"url" => $this->link,
    		"current" => $this->page,
    		"list" => $pagers,
    		"visible" => ($this->getLastPageNum() > 1)
    	);
	}
	function getLastPageNum(){
		return ceil($this->total / $this->limit);
	}
	function getSelectArray(){
    	$pagers = range(
    		1,
    		(int)($this->total / $this->limit) + 1
    	);
		$array = array();
		foreach($pagers as $page){
			$array[ $page ] = $page;
		}
		return $array;
	}
    function getLink() {
    	return $this->link;
    }
    function setLink($link) {
    	$this->link = $link;
    }
    function getPage() {
    	return $this->page;
    }
    function setPage($page) {
    	$this->page = $page;
    }
    function getStart() {
    	return min($this->start,$this->total);
    }
    function setStart($start) {
    	$this->start = $start;
    }
    function getEnd() {
    	if(!$this->end){
    		$this->end = min($this->total,$this->start + $this->limit - 1);
    	}
    	return $this->end;
    }
    function setEnd($end) {
    	$this->end = $end;
    }
    function getTotal() {
    	return $this->total;
    }
    function setTotal($total) {
    	$this->total = $total;
    }
    function getQuery() {
    	return $this->query;
    }
    function setQuery($query) {
    	$this->query = $query;
    }
    function getPagerCount() {
    	return $this->pagerCount;
    }
    function setPagerCount($count) {
    	$this->pagerCount = $count;
    }
    function getLimit() {
    	return $this->limit;
    }
    function setLimit($limit) {
    	$this->limit = $limit;
    }
}
class SOY2HTMLPager_List extends HTMLList{
	private $url;
	private $current;
	protected function populateItem($bean){
		if(is_array($bean)){
			list($link,$text) = $bean;
		}else{
			$link = $bean;
			$text= $bean;
		}
		$url = $this->url . $link;
		$this->addLink("page_link", array(
			"text" => $text,
			"link" => ($this->current != $link)?$url : ""
		));
		$this->addLink("page_link_only", array(
			"link" => $url
		));
		$this->addLabel("page_text", array(
			"text" => $text
		));
		$this->addModel("current_page", array(
			"visible" => ($this->current == $link)
		));
		$this->addModel("other_page", array(
			"visible" => ($this->current != $link)
		));
	}
	function getUrl() {
		return $this->url;
	}
	function setUrl($url) {
		$this->url = $url;
	}
	function getCurrent() {
		return $this->current;
	}
	function setCurrent($cuttent) {
		$this->current = $cuttent;
	}
}
