<?php

/**
 * SOY2DAO_EntityColumn
 *
 * @package SOY2.SOY2DAO
 * @author Miyazawa
 */
class SOY2DAO_EntityColumn{
	var $id;	//identity,またはsequence=シーケンス名
	var $name;	//カラム名?
	var $alias;	//変換後のカラム名
	var $prop;
	var $isPrimary;
	var $readOnly;
	var $sequence;
	function getId() {
		return $this->id;
	}
	function setId($id) {
		$this->id = $id;
	}
	function getName() {
		return $this->name;
	}
	function setName($name) {
		$this->name = $name;
	}
	function getAlias() {
		return $this->alias;
	}
	function setAlias($alias) {
		$this->alias = $alias;
	}
	function getProp() {
		return $this->prop;
	}
	function setProp($prop) {
		$this->prop = $prop;
	}
	function getIsPrimary() {
		return $this->isPrimary;
	}
	function setIsPrimary($isPrimary) {
		$this->isPrimary = $isPrimary;
	}
	function getSequence() {
		return $this->sequence;
	}
	function setSequence($sequence) {
		$this->sequence = $sequence;
	}
}
