<?php

/**
 * SOY2DAO_Query
 *
 * @package SOY2.SOY2DAO
 */
class SOY2DAO_Query{
	var $prefix;
	var $table;
	var $sql;
	var $where;
	var $order;
	var $group;
	var $having;	//new!!
	var $distinct;
	var $sequence;
	var $binds = array();
	/*
	 * キーワードと区別するために識別子を囲む引用符
	 * http://dev.mysql.com/doc/refman/5.1/ja/identifiers.html
	 * http://www.postgresql.jp/document/pg825doc/html/sql-syntax-lexical.html
	 * http://www.sqlite.org/lang_keywords.html
	 */
	const IDENTIFIER_QUALIFIER_MYSQL = "`";
	const IDENTIFIER_QUALIFIER_SQLITE = '"';
	const IDENTIFIER_QUALIFIER_POSTGRES = '"';
	/**
	 * @return このオブジェクトが持つ情報を元にSQL文が返る
	 */
	function __toString(){
		switch($this->prefix){
			case "insert":
				$sql =  $this->prefix." into ".$this->quoteIdentifier($this->table)." ".$this->sql;
				if(strlen($this->where)){
					$sql .= " where ".$this->where;
				}
				break;
			case "select":
				$sql =  $this->prefix." ";
				if($this->distinct){
					$sql .= "distinct ";
				}
				$sql .= $this->sql." from ".$this->quoteIdentifier($this->table);
				if(strlen($this->where)){
					$sql .= " where ".$this->where;
				}
				if(strlen($this->group)){
					$sql .= " group by ".$this->group;
				}
				if(strlen($this->having)){
					$sql .= " having ".$this->having;
				}
				if(strlen($this->order)){
					$sql .= " order by ".$this->order;
				}
				break;
			case "update":
				$sql =  $this->prefix." ".$this->quoteIdentifier($this->table)." set ".$this->sql;
				if(strlen($this->where)){
					$sql .= " where ".$this->where;
				}
				break;
			case "delete":
				$sql =  $this->prefix." from ".$this->quoteIdentifier($this->table);
				if(strlen($this->where)){
					$sql .= " where ".$this->where;
				}
				break;
		}
		return $sql;
	}
	/**
	 * where句およびhaving句のPHP式の実行
	 * :を使うときは\でエスケープしておく必要がある
	 */
	function parseExpression($arguments){
		/*
		 * 引数の$argumentsはevalの中で使われている
		 */
		$phpExpression = '/<\?php\s(.*)?\?>/';
		if(preg_match($phpExpression,$this->where,$tmp)){
			$expression = $tmp[1];
			$expression = str_replace("\\:","@:@",$expression);
			$expression = preg_replace("/:([a-zA-Z0-9_]+)/",'$arguments[\'$1\']',$expression);
			$expression = str_replace("@:@",":",$expression);
			$replace = "";
			eval('$replace = '.$expression.";");
			if(!is_string($replace) AND !is_numeric($replace))throw new SOY2DAOException("PHP式の変換に失敗しました。(".$tmp[1].")");
			$this->where = preg_replace($phpExpression,$replace,$this->where);
		}
		if(preg_match($phpExpression,$this->having,$tmp)){
			$expression = $tmp[1];
			$expression = preg_replace("/:([a-zA-Z0-9_]*)/",'$arguments[\'$1\']',$expression);
			$replace = "";
			eval('$replace = '.$expression.";");
			if(!is_string($replace) AND !is_numeric($replace))throw new SOY2DAOException("PHP式の変換に失敗しました。(".$tmp[1].")");
			$this->having = preg_replace($phpExpression,$replace,$this->having);
		}
	}
	/**
	 * テーブル名を変換します。
	 * （使われていない模様）
	 */
	function replaceTableNames(){
		$this->table = preg_replace_callback('/([a-zA-Z_0-9]+)\?/',array($this,'replaceTableName'),$this->table);
		$this->sql = preg_replace_callback('/([a-zA-Z_0-9]+)\?/',array($this,'replaceTableName'),$this->sql);
		$this->where = preg_replace_callback('/([a-zA-Z_0-9]+)\?/',array($this,'replaceTableName'),$this->where);
		$this->having = preg_replace_callback('/([a-zA-Z_0-9]+)\?/',array($this,'replaceTableName'),$this->having);
	}
	function replaceTableName($key){
		return SOY2DAOConfig::getTableMapping($key[1]);
	}
	/**
	 * 識別子を引用符で囲みます
	 * MySQL: ` バッククォート
	 * SQLite, PostgreSQL: " ダブルクォート
	 */
	public function quoteIdentifier($identifier){
		if(strlen(preg_replace("/[a-zA-Z0-9_]+/","",$identifier))>0){
			/*
			 * @table table1 join table2 on (table1.id=table2.subid)
			 * や
			 * @column table1.id
			 * のような記述がされているものは囲まない
			 */
			return $identifier;
		}else{
			switch(SOY2DAOConfig::type()){
				case SOY2DAOConfig::DB_TYPE_MYSQL :
					return self::IDENTIFIER_QUALIFIER_MYSQL . $identifier . self::IDENTIFIER_QUALIFIER_MYSQL;
				case SOY2DAOConfig::DB_TYPE_SQLITE :
					return self::IDENTIFIER_QUALIFIER_SQLITE . $identifier . self::IDENTIFIER_QUALIFIER_SQLITE;
				case SOY2DAOConfig::DB_TYPE_POSTGRES :
					return self::IDENTIFIER_QUALIFIER_POSTGRES . $identifier . self::IDENTIFIER_QUALIFIER_POSTGRES;
				default:
					return $identifier;
			}
		}
	}
	/**
	 * 識別子の引用符を外す
	 */
	public function unquote($value){
		$quote = "";
		switch(SOY2DAOConfig::type()){
			case SOY2DAOConfig::DB_TYPE_MYSQL :
				$quote = self::IDENTIFIER_QUALIFIER_MYSQL;
				break;
			case SOY2DAOConfig::DB_TYPE_SQLITE :
				$quote = self::IDENTIFIER_QUALIFIER_SQLITE;
				break;
			case SOY2DAOConfig::DB_TYPE_POSTGRES :
				$quote = self::IDENTIFIER_QUALIFIER_POSTGRES;
				break;
		}
		if(strlen($quote)>0 && strlen($value)>1 && $value[0]===$quote && $value[strlen($value)-1]===$quote){
			$value = substr($value,1,strlen($value)-2);
		}
		return $value;
	}
	/**
	 * SQL文を生成し、返します。
	 * PHP 5.2.0以前では__toStringが呼ばれないのでこちらを使用してください。
	 *
	 * @return string SQL文
	 */
	function getQuery(){
		return $this->__toString();
	}
	function getPrefix() {
		return $this->prefix;
	}
	function setPrefix($prefix) {
		$this->prefix = $prefix;
	}
	function getTable() {
		return $this->table;
	}
	function setTable($table) {
		$this->table = $table;
	}
	function getSql() {
		return $this->sql;
	}
	function setSql($sql) {
		$this->sql = $sql;
	}
	function getWhere() {
		return $this->where;
	}
	function setWhere($where) {
		$this->where = $where;
	}
	function getOrder() {
		return $this->order;
	}
	function setOrder($order) {
		$this->order = $order;
	}
	function getGroup() {
		return $this->group;
	}
	function setGroup($group) {
		$this->group = $group;
	}
	function getHaving() {
		return $this->having;
	}
	function setHaving($having) {
		$this->having = $having;
	}
	function getDistinct() {
		return $this->distinct;
	}
	function setDistinct($distinct) {
		$this->distinct = $distinct;
	}
	function getSequence() {
		return $this->sequence;
	}
	function setSequence($sequence) {
		$this->sequence = $sequence;
	}
	function getBinds() {
		return $this->binds;
	}
	function setBinds($binds) {
		$this->binds = $binds;
	}
}
