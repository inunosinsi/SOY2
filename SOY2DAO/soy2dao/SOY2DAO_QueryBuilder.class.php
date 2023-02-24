<?php

/**
 * SOY2DAO_QueryBuilder
 *
 * @package SOY2.SOY2DAO
 * @author Miyazawa
 */
class SOY2DAO_QueryBuilder{
	/**
	 * DAOのメソッド名と、EntityクラスのアノテーションなどからSOY2DAO_Queryオブジェクトを作る
	 * メソッド名からおのおの作るQuery文のbuilderへ処理を渡す
	 *
	 * @param $methodName DAOクラスにあるメソッド名
	 * @param $entityInfo EntityClassのAnnotationなどの情報
	 * @param $noPersistents 無視するカラム
	 * @param $columns 使うカラム
	 * @param $queryType タイプ
	 * @return SOY2DAO_Query
	 */
	public static function buildQuery(string $methodName, SOY2DAO_Entity $entityInfo, array $noPersistents=array(), array $columns=array(), string $queryType=""){
		if(preg_match("/^insert|^create/",$methodName) || $queryType == "insert"){
			return SOY2DAO_InsertQueryBuilder::build($methodName,$entityInfo,$noPersistents,$columns);
		}
		if(preg_match("/^delete|^remove/",$methodName) || $queryType == "delete"){
			return SOY2DAO_DeleteQueryBuilder::build($methodName,$entityInfo,$noPersistents,$columns);
		}
		if(preg_match("/^update|^save|^write|^reset|^change/",$methodName) || $queryType == "update"){
			return SOY2DAO_UpdateQueryBuilder::build($methodName,$entityInfo,$noPersistents,$columns);
		}
		return SOY2DAO_SelectQueryBuilder::build($methodName,$entityInfo,$noPersistents,$columns);
	}
	/**
	 * @return SOY2DAO_Query
	 */
	protected static function build(string $methodName, SOY2DAO_Entity $entityInfo, array $noPersistents, array $columns){
		return new SOY2DAO_Query();
	}
}
