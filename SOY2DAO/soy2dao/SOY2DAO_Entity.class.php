<?php

/**
 * SOY2DAO Entity Class
 *
 * @package SOY2.SOY2DAO
 * @see SOY2DAO_EntityColumn
 * @author Miyazawa
 */
class SOY2DAO_Entity{
	var $name;
	var $table;
	var $id;
	var $columns = array();
	var $reverseColumns = array();	//逆引きテーブル
	/**
	 * @return EntityClassのProperty名を連想配列のキーとし、値にカラム名が入ったArrayを返す
	 * @param readOnlyな属性も取得するかどうか
	 */
	function getColumns(bool $flag=false){
		$array = array();
		foreach($this->columns as $column){
			 if(!$flag && $column->readOnly)continue;
			 $array[strtolower($column->prop)] = $column->name;
		}
		return $array;
	}
	/**
	 * @param $key EntityClassのProperty名
	 * @return EntityClassのProperty名から対応するSOY2DAO_EntityColumnオブジェクトを返す。
	 */
	function getColumn($key){
		$key = strtolower($key);
		return (isset($this->columns[$key])) ? $this->columns[$key] : null;
	}
	/**
	 * カラム名からSOY2DAO_EntityColumnオブジェクトを取得
	 * @param $name カラム名
	 */
	function getColumnByName(string $name, bool $isThrow=true){
		$name = strtolower($name);
		if(!isset($this->reverseColumns[$name])){
			if($isThrow){
				trigger_error("[SOY2DAO]".$this->name." does not have $name.");
			}else{
				return null;
			}
		}
		return $this->getColumn(@$this->reverseColumns[$name]);
	}
	/**
	 * 逆引きテーブルを作成
	 */
	function buildReverseColumns(){
		foreach($this->columns as $key => $column){
			$name = ($column->getAlias()) ? $column->getAlias() : $column->getName();
			$name = strtolower($name);
			$this->reverseColumns[$name] = $key;
		}
	}
}
