<?php

/**
 * SOY2DAO_DeleteQueryBuilder
 * delete文のQueryオブジェクトを作る
 *
 * @package SOY2.SOY2DAO
 * @author Miyazawa
 */
class SOY2DAO_DeleteQueryBuilder extends SOY2DAO_QueryBuilder{
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
		$query->prefix = "delete";
		$query->table = $entityInfo->table;
		$columns = $entityInfo->getColumns();
		if(preg_match('/By([a-zA-Z0-9_]*)$/',$methodName,$tmp)){
			$param = $tmp[1];
			$column = $entityInfo->getColumn($param);
			if($column){
				$query->where = $query->quoteIdentifier($column->name)." = :{$column->prop}";
			}
		}else{
			foreach($columns as $key => $value){
				$column = $entityInfo->getColumn($key);
				if($column->isPrimary){
					$query->where = $query->quoteIdentifier($column->name)." = :{$column->prop}";
				}
			}
		}
		return $query;
	}
}
