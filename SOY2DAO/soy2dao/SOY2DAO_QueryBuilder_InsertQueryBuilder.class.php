<?php

/**
 * SOY2DAO_InsertQueryBuilder
 * insert文のQueryオブジェクトを作る
 *
 * @package SOY2.SOY2DAO
 * @author Miyazawa
 */
class SOY2DAO_InsertQueryBuilder extends SOY2DAO_QueryBuilder{
	/**
	 * DAOのメソッド名と、EntityクラスのアノテーションなどからSOY2DAO_Queryオブジェクトを作る
	 *
	 * @param $methodName DAOクラスにあるメソッド名
	 * @param $entityInfo EntityClassのAnnotationなどの情報
	 *
	 * @return SOY2DAO_Query
	 */
	protected static function build(string $methodName,$entityInfo, array $noPersistents, array $columns){
		$query = new SOY2DAO_Query();
		$query->prefix = "insert";
		$query->table = $entityInfo->table;
		if(empty($columns)){
			$columns = $entityInfo->getColumns();
		}
		$columnString = array();
		foreach($columns as $key => $value){
			$column = $entityInfo->getColumnByName($value);
			if($column->isPrimary && !$column->sequence){
				continue;
			}
			$columnString[] = $query->quoteIdentifier($column->name);
		}
		$sql = "(".implode(",",$columnString).") ";
		$values = array();
		foreach($columns as $key => $value){
			$column = $entityInfo->getColumnByName($value);
			if($column->isPrimary && $column->sequence){
				$values[] = "nextval(".$query->quoteIdentifier($column->sequence).")";
				$query->sequence = $column->sequence;
				continue;
			}
			if($column->isPrimary){
				continue;
			}
			$values[] = ":".$column->prop;
		}
		$sql.= "values(".implode(",",$values).") ";
		$query->sql = $sql;
		return $query;
	}
}
