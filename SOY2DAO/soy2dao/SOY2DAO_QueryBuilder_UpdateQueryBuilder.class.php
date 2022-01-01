<?php

/**
 * SOY2DAO_UpdateQueryBuiler
 * Update文のQueryオブジェクトを作る
 *
 * @package SOY2.SOY2DAO
 * @author Miyazawa
 */
class SOY2DAO_UpdateQueryBuilder extends SOY2DAO_QueryBuilder{
	/**
	 * DAOのメソッド名と、EntityクラスのアノテーションなどからSOY2DAO_Queryオブジェクトを作る
	 *
	 * @param $methodName DAOクラスにあるメソッド名
	 * @param $entityInfo EntityClassのAnnotationなどの情報
	 *
	 * @return SOY2DAO_Query
	 */
	protected static function build(string $methodName, $entityInfo, array $noPersistents, array $columns){
		$query = new SOY2DAO_Query();
		$query->prefix = "update";
		$query->table = $entityInfo->table;
		if(empty($columns)){
			$columns = $entityInfo->getColumns();
		}
		$sql = array();
		foreach($columns as $key => $value){
			$column = $entityInfo->getColumnByName($value);
			if(in_array($column->prop,$noPersistents)){
				continue;
			}
			if(in_array($column->name,$noPersistents)){
				continue;
			}
			if($column->isPrimary){
				$query->where = $query->quoteIdentifier($column->name)." = :{$column->prop}";
			}else{
				$sql[] = $query->quoteIdentifier($column->name)." = :{$column->prop}";
			}
		}
		$query->sql = implode(",",$sql);
		return $query;
	}
}
