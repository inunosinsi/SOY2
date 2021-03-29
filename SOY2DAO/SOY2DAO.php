<?php

/**
 * @package SOY2.SOY2DAO
 * SOY2DAO全般の設定をするSingletonクラス
 *
 * @author Miyazawa
 */
class SOY2DAOConfig{
	var $type;
	var $dsn;
	var $user = '';
	var $pass = '';
	var $daoDir = "dao/";
	var $entityDir = "entity/";
	var $daoCacheDir;	//Daoのキャッシュはディフォルトは行わない
	var $event = array();
	/*
	 * PDOのDSN prefixから末尾の:を取り除いた値
	 */
	const DB_TYPE_MYSQL = "mysql";
	const DB_TYPE_SQLITE = "sqlite";
	const DB_TYPE_POSTGRES = "pgsql";
	/*
	 * オプション
	 * limit_query … limit句を使うかどうか(boolean)
	 * keep_statement … statementのキャッシュを強制にする
	 * connection_failure … throw or abort
	 * cache_prefix … キャッシュファイルの先頭に付加する文字列
	 * use_pconnect … 持続的接続を使うかどうか(boolean) PDO::ATTR_PERSISTENT => true
	 */
	var $options = array();
	/*
	 * テーブル名マッピング
	 */
	var $tableMappings = array();
	/**
	 * Constructor
	 */
	private function __construct(){}
	/**
	 * @return SOY2DAOConfig
	 */
	private static function &getInstance(){
		static $_static;
		if(!$_static)$_static = new SOY2DAOConfig();
		return $_static;
	}
	public static function Dsn($dsn = null){
		$config =& self::getInstance();
		$res = $config->dsn;
		if($dsn){
			$config->dsn = $dsn;
			$config->type = substr($dsn,0,strpos($dsn,":"));
		}
		return $res;
	}
	public static function user($user = null){
		$config =& self::getInstance();
		$res = $config->user;
		if($user){
			$config->user = $user;
		}
		return $res;
	}
	public static function pass($pass = null){
		$config =& self::getInstance();
		$res = $config->pass;
		if($pass){
			$config->pass = $pass;
		}
		return $res;
	}
	public static function type(){
		$config =& self::getInstance();
		return $config->type;
	}
	public static function DaoDir($dir = null){
		$config = self::getInstance();
		$res = $config->daoDir;
		if($dir){
			if(substr($dir,strlen($dir)-1) != '/'){
				throw new SOY2DAOException("[SOY2DAO] DaoDir must end by '/'.");
			}
			$config->daoDir = str_replace("\\", "/", $dir);
		}
		return $res;
	}
	public static function EntityDir($dir = null){
		$config = self::getInstance();
		$res = $config->entityDir;
		if($dir){
			if(substr($dir,strlen($dir)-1) != '/'){
				throw new SOY2DAOException("[SOY2DAO] EntityDir must end by '/'.");
			}
			$config->entityDir = str_replace("\\", "/", $dir);
		}
		return $res;
	}
	public static function DaoCacheDir($dir = null){
		$config = self::getInstance();
		$res = $config->daoCacheDir;
		if($dir){
			if(substr($dir,strlen($dir)-1) != '/'){
				throw new SOY2DAOException("[SOY2DAO] EntityDir must end by '/'.");
			}
			$config->daoCacheDir = str_replace("\\", "/", $dir);
		}
		return $res;
	}
	public static function setOption($key, $value = null){
		$config = self::getInstance();
		if($value)$config->options[$key] = $value;
		return (isset($config->options[$key]) ) ? $config->options[$key] : null;
	}
	public static function getOption($key){
		return self::setOption($key);
	}
	public static function setTableMapping($key, $value = null){
		$config = self::getInstance();
		if($value)$config->tableMappings[$key] = $value;
		return (isset($config->tableMappings[$key]) ) ? $config->tableMappings[$key] : $key;
	}
	public static function getTableMapping($key){
		return self::setTableMapping($key);
	}
	/*
	 *
	 * QueryEvent
	 *
	 * SQL発行時にイベント発生
	 *
	 */
	public static function setQueryEvent($function){
		$config = self::getInstance();
		if(!isset($config->event["query"]))$config->event["query"] = array();
		$config->event["query"][] = $function;
	}
	public static function setUpdateQueryEvent($function){
		$config = self::getInstance();
		if(!isset($config->event["updateQuery"]))$config->event["updateQuery"] = array();
		$config->event["updateQuery"][] = $function;
	}
	public static function getQueryEvent(){
		$config = self::getInstance();
		if(!isset($config->event["query"]))$config->event["query"] = array();
		return $config->event["query"];
	}
	public static function getUpdateQueryEvent(){
		$config = self::getInstance();
		if(!isset($config->event["updateQuery"]))$config->event["updateQuery"] = array();
		return $config->event["updateQuery"];
	}
}
/**
 * SOY2DAO
 * DAOImplやDAOの基底となるクラス
 *
 * @package SOY2.SOY2DAO
 * @author Miyazawa
 */
class SOY2DAO{
	protected $_method;	//memcache method name
	protected $_entity;	//memcache entity info
	protected $_query;
	protected $_binds;
	protected $_offset;
	protected $_limit;
	protected $_rowcount;
	protected $_tempQuery = null;
	protected $_statementCache = array();
	protected $_keepStatement;
	protected $_order;
	protected $_responseTime;
	protected $_dsn = null;
	protected $_dbUser = null;
	protected $_dbPass = null;
	/**
	 * 呼び出されたMethodに対応するQueryを返す
	 *
	 * @return QueryのString
	 */
	function getQuery(){
		if(!isset($this->_query[$this->_method])){
			$query = $this->buildQuery($this->_method);
			return $query;
		}
		return $this->_query[$this->_method];
	}
	/**
	 * 呼び出したMethodに対応するQueryを設定する
	 *
	 * @param $sql Query文
	 */
	function setQuery($sql){
		$this->_query[$this->_method] = $sql;
	}
	/**
	 * バインド変数の設定配列を取得する
	 *
	 * @return bind変数配列
	 */
	function getBinds(){
		return $this->_binds;
	}
	/**
	 * SQL文とパラメータ一覧よりバインド配列を返す
	 *
	 * @exception SOY2DAOException バインド変数に設定してある名前がパラメータに無かった
	 *
	 * @param $sql SQL文
	 * @param $binds Methodのパラメータの連想配列
	 *
	 * @return bind配列
	 */
	function buildBinds($sql,$binds){
		if($sql instanceof SOY2DAO_Query){
			$sql = $sql->getQuery();
		}
		$sql = preg_replace("/'[^']*'/","",$sql);
		$regex = ":([a-zA-Z0-9_]*)";
		$tmp = array();
		$result = preg_match_all("/$regex/",$sql,$tmp);
		if(!$result){
			return array();
		}
		$bindArray = array();
		$mapping = $tmp[1];
		foreach($binds as $key => $bind){
			if(is_object($bind)){
				foreach($mapping as $name){
					$method = "get".ucwords($name);
					if(method_exists($bind,$method) && !isset($bindArray[":".$name])){
						$bindArray[":".$name] = $bind->$method();
					}
				}
				unset($mapping[array_search($key,$mapping)]);
			}else{
				if(in_array($key,$mapping) && !isset($bindArray[":".$key])){
					$bindArray[":".$key] = $bind;
				}
			}
		}
		foreach($mapping as $key => $map){
			if(strlen($map) && !array_key_exists(":".$map,$bindArray)){
				throw new SOY2DAOException("バインドするべき変数".$map."が足りません");
			}
		}
		$this->_binds = $bindArray;
		return $bindArray;
	}
	/**
	 * Method名からSQL文を取得する
	 *
	 * @param Method名
	 * @param 永続化しない属性(省略可能)
	 * @param カラム名(省略可能)
	 * @return SQL文
	 */
	function &buildQuery($method,$noPersistents = array(),$columns = array(),$queryType = null){
		if(!isset($this->_query[$method])){
			$this->_query[$method] =
				SOY2DAO_QueryBuilder::buildQuery($method,$this->getEntityInfo(),$noPersistents,$columns,$queryType);
			$this->_query[$method]->replaceTableNames();
		}
		return  $this->_query[$method];
	}
	/**
	 * PDOより帰ってきたArray配列をEntityクラスオブジェクトに変換する
	 *
	 * @param PDOより帰ってきた配列
	 * @return Entityオブジェクト
	 */
	function getObject($row){
		$entityInfo = $this->getEntityInfo();
		$objName = $entityInfo->name;
		$obj = new $objName();
		foreach($row as $key => $value){
			$column = $entityInfo->getColumnByName($key,false);
			if(!$column)continue;
			$propName = $column->prop;
			$method = "set".ucwords($propName);
			$obj->$method($value);
		}
		return $obj;
	}
	/**
	 * EntityInfoクラスオブジェクトを返します
	 *
	 * @return EntityInfoクラスオブジェクト
	 */
	function getEntityInfo(){
		if(!is_object($this->_entity)){
			$this->_entity = unserialize($this->_entity);
		}
		return $this->_entity;
	}
	/**
	 * PDOを取得する
	 *
	 * @return PDOオブジェクト
	 */
	function &getDataSource(){
		return SOY2DAO::_getDataSource($this->getDsn(),$this->getDbUser(),$this->getDbPass());
	}
	function releaseDataSource(){
		SOY2DAO::_releaseDataSource();
	}
	function clearStatementCache(){
		$this->_statementCache = array();
	}
	public static function &_getDataSource($dsn = null,$user = null, $pass = null){
		static $pdo;
		if(is_null($pdo)){
			$pdo = array();
		}
		$dsn = (is_null($dsn)) ? SOY2DAOConfig::Dsn() : $dsn;
		if(!isset($pdo[$dsn])){
			$user = (is_null($user)) ? SOY2DAOConfig::user() : $user;
			$pass = (is_null($pass)) ? SOY2DAOConfig::pass() : $pass;
			$pdoOptions = array(
				PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
			);
			if(SOY2DAOConfig::getOption("use_pconnect")){
				$pdoOptions[PDO::ATTR_PERSISTENT] = true;
			}
			try{
				$pdo[$dsn] = new PDO($dsn,$user,$pass,$pdoOptions);
			} catch (Exception $e) {
				$event = SOY2DAOConfig::getOption("connection_failure");
				if($event == "throw"){
					throw new SOY2DAOException("Can not get DataSource ({$dsn})", $e);
				}else{
					die("Can not get DataSource ({$dsn}).");
				}
			}
			if(SOY2DAOConfig::type() == SOY2DAOConfig::DB_TYPE_MYSQL){
				if(version_compare(PHP_VERSION, "5.3.6") >= 0 && strpos(SOY2DAOConfig::Dsn(),"charset=") !== false){
				}else{
					try{
						$pdo[$dsn]->exec("set names 'utf8'");
					}catch(Exception $e){
					}
				}
			}
		}
		return $pdo[$dsn];
	}
	public static function _releaseDataSource(){
		$pdo = &self::_getDataSource();
		$pdo = null;
	}
	/**
	 * find
	 */
    public static function find($className,$arguments = array()){
		if(!is_array($arguments))$arguments = array("id" => $arguments);
		SOY2DAOFactory::importEntity($className);
		$daoName = $className . "DAO";
		$dao = SOY2DAOFactory::create($daoName);
		if(empty($arguments) && method_exists($dao,"get")){
			return $dao->get();
		}
    	foreach($arguments as $key => $value){
    		if(method_exists($dao,"getBy" . ucwords($key))){
    			$method = "getBy" . ucwords($key);
    			return $dao->$method($value);
    		}
    	}
    	throw new Exception("not supported");
    }
	/**
	 * SQL文をQueryする
	 *
	 * @exception SOY2DAOException 結果が無いとき
	 *
	 * @param SQL文
	 * @param バインド配列
	 *
	 * @return 結果配列
	 */
	function executeQuery($query,$binds = array(),$keepStatement = false){
		if($query instanceof SOY2DAO_Query){
			if(strlen($this->getOrder())){
				$query->setOrder($this->getOrder());
			}
			$query->replaceTableNames();
			$sql = $query->getQuery();
		}else{
			$sql = $query;
		}
		if(!is_null($this->_keepStatement)){
			$keepStatement = $this->_keepStatement;
		}
		$isUseLimitQuery = false;
		if(SOY2DAOConfig::getOption("limit_query") === true && !is_null($this->_limit)){
			if(!is_null($this->_offset)){
				$sql .= " limit " . (int)$this->_offset . "," . (int)$this->_limit;
			}else{
				$sql .= " limit 0," . (int)$this->_limit;
			}
			$isUseLimitQuery = true;
		}
		if(SOY2DAOConfig::getOption("keep_statement") !== null){
			$keepStatement = (boolean)SOY2DAOConfig::getOption("keep_statement");
		}
		$pdo = $this->getDataSource();
		try{
			$events = SOY2DAOConfig::getQueryEvent();
			foreach($events as $event){
				call_user_func($event,$sql,$binds);
			}
			if($keepStatement){
				if(isset($this->_statementCache[md5($sql)])){
					$stmt = $this->_statementCache[md5($sql)];
				}else{
					$stmt = $pdo->prepare($sql);
					$this->_statementCache[md5($sql)] = $stmt;
				}
			}else{
				$stmt = $pdo->prepare($sql);
			}
			if(!$stmt){
				$e = new SOY2DAOException("The database server cannot successfully prepare the statement. SQL: ".$sql);
				$e->setQuery($sql . "");
				throw $e;
			}
			foreach($binds as $key => $bind){
				$type = PDO::PARAM_STR;
				switch(true){
					case is_null($bind) :
						$type = PDO::PARAM_NULL;
						break;
					case is_int($bind) :
						$type = PDO::PARAM_INT;
						break;
					case is_bool($bind) :
					case is_float($bind) :
					case is_numeric($bind) :
					case is_string($bind) :
					default:
						$type = PDO::PARAM_STR;
						break;
				}
				$stmt->bindParam($key, $binds[$key], $type);
			}
			$start = microtime(true);
			$result = $stmt->execute();
			$this->_responseTime = microtime(true) - $start;
		}catch(Exception $e){
			$e = new SOY2DAOException("Invalid query.",$e);
			$e->setQuery($sql . "");
			throw $e;
		}
		if(!$result){
			$e = new SOY2DAOException("[Failed] Statement->execute. ",$e);
			$e->setQuery($sql . "");
			throw $e;
		}
		$resultArray = array();
		$counter = 0;
		if($isUseLimitQuery){
			while($row = $stmt->fetch(PDO::FETCH_ASSOC)){
				$row = $this->unquoteColumnName($query, $sql, $row);
				$resultArray[] = $row;
				$counter++;
			}
		}else{
			if(!is_null($this->_offset)){
				for($i=0; $i<$this->_offset; ++$i){
					if($stmt->fetch() == false)break;
					$counter++;
				}
			}
			while($row = $stmt->fetch(PDO::FETCH_ASSOC)){
				if(is_null($this->_limit) || $counter < ($this->_offset + $this->_limit)){
					$row = $this->unquoteColumnName($query, $sql, $row);
					$resultArray[] = $row;
				}
				$counter++;
			}
		}
		$this->_rowcount = $counter;
		return $resultArray;
	}
	/**
	 * カラム名についた引用符を外す
	 * SQL文にgroup byがあるかどうかに関わらず常に引用符を外すことにした。
	 * PDOのバグなのか、group byを使うとfetchの返り値でカラム名が引用符ごと返って来てしまう。
	 * as "～"を指定したカラムはちゃんと引用符無しで返ってくる。
	 * viewをselectした場合もカラム名が引用符ごと返ってくる。
	 */
	private function unquoteColumnName($query, $sql, $row){
		if($query instanceof SOY2DAO_Query){
			$_row = array();
			foreach($row as $key => $value){
				$_row[$query->unquote($key)] = $value;
			}
			$row = $_row;
		}
		return $row;
	}
	/**
	 * Update系のQueryを実行する
	 *
	 * @param SQL文
	 * @param バインド変数
	 *
	 * @return 結果
	 */
	function executeUpdateQuery($sql,$binds = array(),$keepStatement = false){
		if($sql instanceof SOY2DAO_Query){
			if(strlen($this->getOrder())){
				$sql->setOrder($this->getOrder());
			}
			$sql->replaceTableNames();
			$this->_tempQuery = $sql;	//Queryを保存
			$sql = $sql->getQuery();
		}
		if(SOY2DAOConfig::getOption("keep_statement") !== null){
			$keepStatement = (boolean)SOY2DAOConfig::getOption("keep_statement");
		}
		if(!is_null($this->_keepStatement)){
			$keepStatement = $this->_keepStatement;
		}
		$pdo = $this->getDataSource();
		if($sql instanceof SOY2DAO_Query){
			$sql = $sql->__toString();
		}
		try{
			$events = SOY2DAOConfig::getUpdateQueryEvent();
			foreach($events as $event){
				call_user_func($event,$sql,$binds);
			}
			if($keepStatement){
				if(isset($this->_statementCache[md5($sql)])){
					$stmt = $this->_statementCache[md5($sql)];
				}else{
					$stmt = $pdo->prepare($sql);
					$this->_statementCache[md5($sql)] = $stmt;
				}
			}else{
				$stmt = $pdo->prepare($sql);
			}
			if($stmt === false){
				throw new SOY2DAOException("The database server cannot successfully prepare the statement. SQL: ".$sql);
			}
			foreach($binds as $key => $bind){
				$type = PDO::PARAM_STR;
				switch(true){
					case is_null($bind) :
						$type = PDO::PARAM_NULL;
						break;
					case is_int($bind) :
						$type = PDO::PARAM_INT;
						break;
					case is_bool($bind) :
					case is_float($bind) :
					case is_numeric($bind) :
					case is_string($bind) :
					default:
						$type = PDO::PARAM_STR;
						break;
				}
				$stmt->bindParam($key, $binds[$key], $type);
			}
			$start = microtime(true);
			$result = $stmt->execute();
			$this->_responseTime = microtime(true) - $start;
		}catch(Exception $e){
			$e = new SOY2DAOException("Invalid query.",$e);
			$e->setQuery($sql . "");
			throw $e;
		}
		return $result;
	}
	function setMethod($method){
		$this->_method = $method;
		$this->_binds = array();
	}
	/**
	 * 最後に挿入したIDを取得
	 */
	function lastInsertId(){
		$pdo = $this->getDataSource();
		if(SOY2DAOConfig::type() != SOY2DAOConfig::DB_TYPE_POSTGRES){
			return $pdo->lastInsertId();
		}else{
			if($this->_tempQuery && $this->_tempQuery instanceof SOY2DAO_Query){
				$sequence = $this->_tempQuery->sequence;
				$stmt = $pdo->query("select currval('$sequence') as current_seq_id");
				if(!$stmt)return null;
				while($row = $stmt->fetch(PDO::FETCH_ASSOC)){
					return $row["current_seq_id"];
				}
			}
			return null;
		}
	}
	/**
	 * オフセット指定
	 */
	function setOffset($offset){
		$this->_offset = $offset;
	}
	/**
	 * 件数指定
	 */
	function setLimit($limit){
		$this->_limit = $limit;
	}
	/**
	 * 検索結果の件数取得
	 */
	function getRowCount(){
		return $this->_rowcount;
	}
	/**
	 * トランザクション開始
	 */
	function begin(){
		$this->getDataSource()->beginTransaction();
	}
	/**
	 * ロールバック
	 */
	function rollback(){
		$this->getDataSource()->rollBack();
	}
	/**
	 * コミット
	 */
	function commit(){
		$this->getDataSource()->commit();
	}
	/**
	 * setter keep statement
	 */
	function setKeepStatement($flag){
		$this->_keepStatement = (boolean)$flag;
	}
	/**
	 * テーブル名を取得する
	 */
	function getTableName($key){
		return SOY2DAOConfig::getTableMapping($key);
	}
	/**
	 * setter order
	 */
	function setOrder($order){
		$this->_order = $order;
	}
	/**
	 * getter order
	 */
	function getOrder(){
		return $this->_order;
	}
	/**
	 * setter dsn
	 */
	function setDsn($dsn){
		$this->_dsn = $dsn;
	}
	/**
	 * getter dsn
	 */
	function getDsn(){
		return $this->_dsn;
	}
	function getDbUser() {
		return $this->_dbUser;
	}
	function setDbUser($dbUser) {
		$this->_dbUser = $dbUser;
	}
	function getDbPass() {
		return $this->_dbPass;
	}
	function setDbPass($dbPass) {
		$this->_dbPass = $dbPass;
	}
	/**
	 * 応答時間を取得
	 */
	function getResponseTime(){
		return $this->_responseTime;
	}
}
/**
 * SOY2DAOから吐き出すException
 */
class SOY2DAOException extends Exception{
	private $pdoException;
	private $query;
	function __construct($msg, Exception $e = null){
		$this->pdoException = $e;
		parent::__construct($msg);
	}
	function getPDOExceptionMessage(){
		if(!$this->pdoException)return "";
		$message = $this->pdoException->getMessage();
		if($this->pdoException instanceof PDOException && !empty($this->pdoException->errorInfo)){
			$message .= "; ".implode(", ", $this->pdoException->errorInfo);
		}
		return $message;
	}
	function getPdoException() {
		return $this->pdoException;
	}
	function setPdoException($pdoException) {
		$this->pdoException = $pdoException;
	}
	function getQuery() {
		return $this->query;
	}
	function setQuery($query) {
		$this->query = $query;
	}
}
