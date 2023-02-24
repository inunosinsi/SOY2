<?php

/**
 * SOY2DAO_SelectQueryBuilder
 * Select文のQueryオブジェクトを作る
 *
 * @package SOY2.SOY2DAO
 * @author Miyazawa
 */
class SOY2DAO_SelectQueryBuilder extends SOY2DAO_QueryBuilder{
	/**
	 * DAOのメソッド名と、EntityクラスのアノテーションなどからSOY2DAO_Queryオブジェクトを作る
	 *
	 * @param $methodName DAOクラスにあるメソッド名
	 * @param $entityInfo EntityClassのAnnotationなどの情報
	 *
	 * @return SOY2DAO_Query
	 */
	protected static function build(string $methodName, SOY2DAO_Entity $entityInfo, array $noPersistents, array $columns){
		$query = new SOY2DAO_Query();
		$query->prefix = "select";
		$query->table = $entityInfo->table;
		if(empty($columns)){
			$columns = $entityInfo->getColumns(true);
		}
		$columns = array_map(array($query,"quoteIdentifier"), $columns);
		$query->sql = implode(",",$columns);
		$tmp = array();
		if(preg_match('/By([a-zA-Z0-9_]*)$/',$methodName,$tmp)){
			$param = $tmp[1];
			$column = $entityInfo->getColumn($param);
			if(!is_null($column)){
				$query->where = $query->quoteIdentifier($column->name)." = :{$column->prop}";
			}
		}
		return $query;
	}
}
