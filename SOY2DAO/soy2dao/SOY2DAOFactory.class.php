<?php

/**
 * SOY2DAOFactory
 * クラス名からDAOImplを生成する
 *
 * @package SOY2.SOY2DAO
 * @author Miyazawa
 */
class SOY2DAOFactory{
	/**
	 * クラス名を元にオブジェクトをつくりそのインスタンスを返す
	 *
	 * @param $className DAOImplを生成したいDAOクラス名
	 * @return DAOImplクラスオブジェクト
	 */
	public static function create(string $className, array $arguments=array()){
		$className = SOY2DAOFactory::importDAO($className);
		$obj = SOY2DAOFactoryImpl::build($className);
		foreach($arguments as $key => $value){
			if(method_exists($obj,"set".ucwords($key))){
				$func = "set".ucwords($key);
				$obj->$func($value);
				continue;
			}
		}
		return $obj;
	}
	const ANNOTATION_ENTITY = "entity";
	const ANNOTATION_QUERY = "query";
	const ANNOTATION_SQL = "sql";
	const ANNOTATION_RETURN = "return";
	const ANNOTATION_ORDER = "order";
	const ANNOTATION_COLUMNS = "columns";
	const ANNOTATION_GROUP = "group";
	const ANNOTATION_HAVING = "having";
	const ANNOTATION_DISTINCT = "distinct";
	const ANNOTATION_TRIGGER = "trigger";
	const ANNOTATION_FINAL = "final";
	const ANNOTATION_QUERY_TYPE = "query_type";
	const ANNOTATION_TABLE = "table";
	const ANNOTATION_ID = "id";
	const ANNOTATION_NO_PERSISTENT = "no_persistent";	//DBに関係ない属性地はこれを設定
	const ANNOTATION_READ_ONLY = "read_only";			//検索系でのみ使用可能な属性
	const ANNOTATION_COLUMN = "column";
	const ANNOTATION_COLUMN_ALIAS = "alias";
	const ANNOTATION_COLUMN_TYPE = "type";
	const ANNOTATION_INDEX = "index";
	/**
	 * コメントからアノテーションを取得する
	 *
	 * @param $key Annotationのキー
	 * @param $str Annotationの入ったコメント文
	 *
	 * @return $keyに対応するAnnotationが存在すればその値、なければfalse
	 */
	public static function getAnnotation(string $key, string $str){
		$regex = '@'.$key.'\s+(.+)';
		$tmp = array();
		if(!preg_match("/$regex/",$str,$tmp)){
			$regex = '@'.$key;
			if(preg_match("/$regex/",$str)){
				return true;
			}else{
				return false;
			}
		}
		return trim($tmp[1]);
	}
	/**
	 * DAOクラスを読み込む
	 *
	 * @param $className クラス名（パッケージ含む）
	 */
	public static function importDAO(string $className){
		if(!class_exists($className)){
			$path = $className;
			$tmp = array();
			if(preg_match('/\.?([a-zA-Z0-9_]+$)/',$className,$tmp)){
				$className = $tmp[1];
			}
			if(!class_exists($className)){
				$fullPath = SOY2DAOConfig::DaoDir(). str_replace(".","/",$path).".class.php";
				include($fullPath);
			}
		}
		return $className;
	}
	/**
	 * Entityクラスを読み込む
	 *
	 * @param $className クラス名（パッケージ含む）
	 */
	public static function importEntity(string $className){
		if(!class_exists($className)){
			$path = $className;
			$tmp = array();
			if(preg_match('/\.?([a-zA-Z0-9_]+$)/',$className,$tmp)){
				$className = $tmp[1];
			}
			if(class_exists($className)){
				return $className;
			}
			$fullPath = SOY2DAOConfig::EntityDir(). str_replace(".","/",$path).".class.php";
			require_once($fullPath);
		}
		return $className;
	}
}
/**
 * SOY2DAOFactoryImpl
 * DAOImplを生成する
 *
 * @package SOY2.SOY2DAO
 * @author Miyazawa
 */
class SOY2DAOFactoryImpl extends SOY2DAOFactory {
	/**
	 * クラス名を元にオブジェクトをつくりそのインスタンスを返す
	 *
	 * @param $className DAOクラス名
	 * @return DAOImplクラスオブジェクト
	 */
	public static function build(string $className){
		$implClassName = self::getImplClassName($className);
		if(class_exists($implClassName)){
			return new $implClassName();
		}
		$cacheFilePath = self::getDaoCacheFilePath($className);
		$reflection = new ReflectionClass($className);
		if(file_exists($cacheFilePath)
			&& filemtime($cacheFilePath) > filemtime(__FILE__)
			&& filemtime($cacheFilePath) > filemtime($reflection->getFileName())
		){
			include_once($cacheFilePath);
		}
		if(class_exists($implClassName)){
			return new $implClassName();
		}
		$daoComment = $reflection->getDocComment();
		$entityClass = self::getEntityClassName($className,$daoComment);
		$entityClass = SOY2DAOFactory::importEntity($entityClass);
		$entityInfo = self::buildEntityInfomation($entityClass);
		if(!$reflection->isSubclassOf(new ReflectionClass("SOY2DAO"))){
			return $reflection->newInstance();
		}
		$methods = $reflection->getMethods();
		foreach($methods as $method){
			if($method->getDeclaringClass()->getName() != $className)continue;
			$methodStrings[] = self::buildMethod($method,$entityInfo);
		}
		$str  = "class ".$reflection->getName()."Impl extends ".$reflection->getName()."{";
		$str.="\n";
		$str .= 'var $_entity = "'.str_replace('"','\"',serialize($entityInfo)).'";';
		$str.="\n";
		$str .= implode("\n",$methodStrings);
		$str .= "}";
		/*
		$classss = explode("\n",$str);
		foreach($classss as $key => $value){
			echo "$key:\t$value<br>";
		}
		*/
		if(SOY2DAOConfig::DaoCacheDir()){
			$fp = fopen($cacheFilePath,"w");
			$entityReflection = new ReflectionClass($entityClass);
			$import = "<?php if(!class_exists('$entityClass')){ \n"
					 ."include_once(\"".str_replace("\\","/",$entityReflection->getFileName())."\"); \n"
					 ."} \n?>";
			$updateCheck = '<?php $updateDate'." = max(filemtime(\"".str_replace("\\","/",$reflection->getFileName())."\"),filemtime(\"".str_replace("\\","/",$entityReflection->getFileName())."\"));";
			$updateCheck .= 'if($updateDate  < filemtime(__FILE__)){ ?>';
			fwrite($fp,$import);
			fwrite($fp,$updateCheck);
			fwrite($fp,"<?php\n".$str."?>");
			fwrite($fp,"<?php\n } \n?>");
			fclose($fp);
		}
		eval($str);
		$name = $reflection->getName()."Impl";
		return new $name();
	}
	/**
	 * メソッドのReflectionを元に実際の内容を作る
	 *
	 * @param $method ReflectionMethod
	 * @return Methodの内容がかかれたString
	 */
	public static function buildMethod($method,$entityInfo){
		$table = self::getAnnotation(SOY2DAOFactory::ANNOTATION_TABLE,$method->getDocComment());
		$return = self::getAnnotation(SOY2DAOFactory::ANNOTATION_RETURN,$method->getDocComment());
		$queryAnnotation = self::getAnnotation(SOY2DAOFactory::ANNOTATION_QUERY,$method->getDocComment());
		$sqlAnnotation = self::getAnnotation(SOY2DAOFactory::ANNOTATION_SQL,$method->getDocComment());
		$noPersistent = self::getAnnotation(SOY2DAOFactory::ANNOTATION_NO_PERSISTENT,$method->getDocComment());
		$order = self::getAnnotation(SOY2DAOFactory::ANNOTATION_ORDER,$method->getDocComment());
		$column = self::getAnnotation(SOY2DAOFactory::ANNOTATION_COLUMNS,$method->getDocComment());
		$columns = (strlen($column)) ? explode(",",$column) : array();
		$group = self::getAnnotation(SOY2DAOFactory::ANNOTATION_GROUP,$method->getDocComment());
		$having = self::getAnnotation(SOY2DAOFactory::ANNOTATION_HAVING,$method->getDocComment());
		$index = self::getAnnotation(SOY2DAOFactory::ANNOTATION_INDEX,$method->getDocComment());
		$distinct = self::getAnnotation(SOY2DAOFactory::ANNOTATION_DISTINCT,$method->getDocComment());
		$trigger = self::getAnnotation(SOY2DAOFactory::ANNOTATION_TRIGGER,$method->getDocComment());
		$final = self::getAnnotation(SOY2DAOFactory::ANNOTATION_FINAL,$method->getDocComment());
		$queryType = self::getAnnotation(SOY2DAOFactory::ANNOTATION_QUERY_TYPE,$method->getDocComment());
		if($final || $method->isFinal() || $method->isPrivate()){
			return;
		}
		$replacePropertyNameFunction = function($key) use ($entityInfo){
			return $entityInfo->getColumn($key[1])->getName();
		};
		$queryAnnotation = preg_replace_callback('/#+([a-zA-Z0-9_]*)#+/',$replacePropertyNameFunction,$queryAnnotation);
		$group = preg_replace_callback('/#+([a-zA-Z0-9_]*)#+/',$replacePropertyNameFunction,$group);
		$having = preg_replace_callback('/#+([a-zA-Z0-9_]*)#+/',$replacePropertyNameFunction,$having);
		$order = preg_replace_callback('/#+([a-zA-Z0-9_]*)#+/',$replacePropertyNameFunction,$order);
		$noPersistent = preg_replace_callback('/#+([a-zA-Z0-9_]*)#+/',$replacePropertyNameFunction,$noPersistent);
		$noPersistents = (strlen($noPersistent)) ? explode(",",$noPersistent) : array();
		$indexColumn = preg_replace_callback('/#+([a-zA-Z0-9_]*)#+/',$replacePropertyNameFunction,$index);
		$columns = preg_replace_callback('/#+([a-zA-Z0-9_]*)#+/',$replacePropertyNameFunction,$columns);
		$parameters = $method->getParameters();
		$params = array();
		foreach($parameters as $param){
			$str = "";
			if(method_exists($param, "getType")){
				$class = (!is_null($param->getType()) && !$param->getType()->isBuiltin()) ? new ReflectionClass($param->getType()->getName()) : null;
			}else{
				$class = (method_exists($param, "getClass")) ? $param->getClass() : "";	//ReflectionParameter::getClass() is deprecated in PHP8
			}
			if($class){
				$str .= $class->getName()." ";
			}
			if($param->isPassedByReference()){
				$str .= "&";
			}
			$str .= '$'.$param->getName();
			if($param->isDefaultValueAvailable()){
				$defValue = $param->getDefaultValue();
				if(is_null($defValue) || is_array($defValue)){
					$defValue = 'null';
				}else if(!is_numeric($defValue) && is_string($defValue)){
					$defValue = '"'.$defValue.'"';
				}
				$str .= " = " . $defValue;
			}
			$params[] = $str;
		}
		$methodString = array();
		$methodString[] =  "function ".$method->getName()."(".implode(",",$params)."){";
		$methodString[] = '$this->setMethod("'.$method->getName().'");';
		if($sqlAnnotation){
			$methodString[] = '$this->setQuery("'.$sqlAnnotation.'");';
		}
		$methodString[] = '$query = $this->buildQuery($this->_method,'.
				'unserialize(\''.serialize($noPersistents).'\'),'.
				'unserialize(\''.serialize($columns).'\'),' .
				'"'.$queryType.'");';
		if($table)$methodString[] = '$query->table = "'.$table.'";';
		if($queryAnnotation)$methodString[] = '$query->where = "'.$queryAnnotation.'";';
		if($order)$methodString[] = '$query->order = "'.$order.'";';
		if($group)$methodString[] = '$query->group = "'.$group.'";';
		if($having)$methodString[] = '$query->having = "'.$having.'";';
		if($distinct)$methodString[] = '$query->distinct = true;';
		$props = array();
		foreach($method->getParameters() as $key => $refParam){
			$props[] = '"'.$refParam->getName().'" => $'.$refParam->getName();
		}
		$methodString[] = 'if($query instanceof SOY2DAO_Query){ $query->parseExpression(array('.implode(',',$props).')); }';
		$methodString[] = '$this->buildBinds($query,array('.implode(',',$props).'));';
		if($method->isAbstract()){
			$methodString[] = '$query = $this->getQuery();';
			$methodString[] = '$binds = $this->getBinds();';
			if($trigger){
				$triggers = explode(",",$trigger);
				foreach($triggers as $key => $trigger){
					$methodString[] = 'if(method_exists($this,"'.$trigger.'")){';
					if(!strpos("::",$trigger)){
						$methodString[] = 'list($query,$binds) = $this->' . $trigger . '($query,$binds);';
					}
					$methodString[] = '}else{';
					$methodString[] = 'list($query,$binds) = ' . $trigger . '($query,$binds);';
					$methodString[] = '}';
				}
			}
			$returnType = $return;
			if(preg_match('/^column_(.*)$/i',$return,$tmp)){
				$returnType = 'column';
				$returnColumnName = $tmp[1];
			}
			if(preg_match('/^columns_(.*)$/i',$return,$tmp)){
				$returnType = 'columns';
				$returnColumnName = $tmp[1];
			}
			if($returnType == "object"
			|| $returnType == "column"
			|| $returnType == "row"
			){
				$methodString[] = '$oldLimit = $this->_limit;';
				$methodString[] = '$this->setLimit(1);';
				$methodString[] = '$oldOffset = $this->_offset;';
				$methodString[] = '$this->setOffset(0);';
			}
			if(preg_match("/^insert|^create/",strtolower($method->getName())) || $queryType == "insert"){
				$methodString[] = '$result = $this->executeUpdateQuery($query,$binds);';
			}else if(preg_match("/^delete|^remove/",strtolower($method->getName())) || $queryType == "delete"){
				$methodString[] = '$result = $this->executeUpdateQuery($query,$binds);';
			}else if(preg_match("/^update|^save|^write|^reset|^change/",strtolower($method->getName())) || $queryType == "update"){
				$methodString[] = '$result = $this->executeUpdateQuery($query,$binds);';
			}else{
				$methodString[] = '$result = $this->executeQuery($query,$binds);';
			}
			switch($returnType){
				case "id":
					$methodString[] = 'return $this->lastInsertId();';
					break;
				case "object":
					$methodString[] = '$this->setLimit($oldLimit);';
					$methodString[] = '$this->setOffset($oldOffset);';
					$methodString[] = 'if(count($result)<1)throw new SOY2DAOException("[SOY2DAO]Failed to return Object.");';
					$methodString[] = '$obj = $this->getObject($result[0]);';
					$methodString[] = 'return $obj;';
					break;
				case "row":
					$methodString[] = '$this->setLimit($oldLimit);';
					$methodString[] = '$this->setOffset($oldOffset);';
					$methodString[] = 'if(count($result)<1)throw new SOY2DAOException("[SOY2DAO]Failed to return row.");';
					$methodString[] = 'return $result[0];';
					break;
				case "column":
					$methodString[] = '$this->setLimit($oldLimit);';
					$methodString[] = '$this->setOffset($oldOffset);';
					$methodString[] = 'if(count($result)<1)throw new SOY2DAOException("[SOY2DAO]Failed to return column.");';
					$methodString[] = '$row = $result[0];';
					$methodString[] = 'return $row["'.$returnColumnName.'"];';
					break;
				case "array":
					$methodString[] = '$array=array();';
					if($index){
						$methodString[] = 'if(is_array($result)){';
						$methodString[] = 'foreach($result as $row){';
						$methodString[] = '$array[$row["'.$indexColumn.'"]] = $row;';
						$methodString[] = '}';
						$methodString[] = '}';
					}else{
						$methodString[] = '$array = $result;';
					}
					$methodString[] = 'return $array;';
					break;
				case "columns":
					$methodString[] = '$array=array();';
					if($index){
						$methodString[] = 'if(is_array($result)){';
						$methodString[] = 	'foreach($result as $row){';
						$methodString[] = 	'$array[$row["'.$indexColumn.'"]] = $row["'.$returnColumnName.'"];';
						$methodString[] = 	'}';
						$methodString[] = '}';
					}else{
						$methodString[] = 'if(is_array($result)){';
						$methodString[] = 	'foreach($result as $row){';
						$methodString[] = 	'$array[] = $row["'.$returnColumnName.'"];';
						$methodString[] = 	'}';
						$methodString[] = '}';
					}
					break;
				case "list":
				default:
					$methodString[] = '$array = array();';
					$methodString[] = 'if(is_array($result)){';
					$methodString[] = 'foreach($result as $row){';
					if($index){
						$func = "get".ucfirst($index);
						$methodString[] = '$obj = $this->getObject($row);';
						$methodString[] = '$array[$obj->'.$func.'()] = $obj;';
					}else{
						$methodString[] = '$array[] = $this->getObject($row);';
					}
					$methodString[] = '}';
					$methodString[] = '}';
					$methodString[] = 'return $array;';
					break;
			}
		}else{
			$parameters = $method->getParameters();
			$params = array();
			foreach($parameters as $parameter){
				$params[] = '$'.$parameter->getName();
			}
			$methodString[] = "return parent::".$method->getName()."(".implode(",",$params).");";
		}
		$methodString[] = '}';
		return implode("\n",$methodString);
	}
	/**
	 * DAOImplのクラス名を返す
	 */
	private static function getImplClassName(string $className){
		return $className."Impl";
	}
	/**
	 * DAOImplのキャッシュファイル名を返す
	 */
	private static function getDaoCacheFilePath(string $className, string $extension=".class.php"){
		$reflection = new ReflectionClass($className);
		return SOY2DAOConfig::DaoCacheDir()
		       .SOY2DAOConfig::getOption("cache_prefix")."dao_cache_".self::getImplClassName($className)
		       ."_".md5($reflection->getFileName())
		       .".class.php";
	}
	/**
	 * DAOクラスに関連付けられたEntityClass名を返す
	 *
	 * @param $className DAOクラス名
	 * @param $daoComment DAOクラスのコメント
	 *
	 * @return Entityクラス名
	 */
	public static function getEntityClassName(string $className, string $daoComment){
		$result = self::getAnnotation(SOY2DAOFactory::ANNOTATION_ENTITY,$daoComment);
		if($result !== false){
			$entity = $result;
		}else{
			$entity = preg_replace('/dao$/i',"",$className);
		}
		return $entity;
	}
	/**
	 * Entityクラス名からEntityInfoクラスオブジェクトを作る
	 *
	 * @param $entity EntityClass名
	 * @return EntityInfoのクラスオブジェクト
	 */
	public static function buildEntityInfomation($entity){
		$reflection = new ReflectionClass($entity);
		$comment = $reflection->getDocComment();
		$entityInfo = new SOY2DAO_Entity();
		$entityInfo->name = $entity;
		$table = self::getAnnotation(self::ANNOTATION_TABLE,$comment);
		$entityInfo->table = (strlen($table)>0) ? $table : $entity;
		$id = self::getAnnotation(self::ANNOTATION_ID,$comment);
		$entityInfo->id = $id;
		$properties = $reflection->getProperties();
		$parent = $reflection->getParentClass();
		while($parent){
			$properties = array_merge($properties,$parent->getProperties());
			$parent = $parent->getParentClass();
		}
		foreach($properties as $property){
			$propertyComment = $property->getDocComment();
			$propName = $property->getName();
			if($propName[0] == "_")continue;
			$noPersistent = self::getAnnotation(self::ANNOTATION_NO_PERSISTENT,$propertyComment);
			if($noPersistent)continue;
			$column = new SOY2DAO_EntityColumn();
			$column->prop = $property->getName();
			$columnAnnotation = self::getAnnotation(self::ANNOTATION_COLUMN,$propertyComment);
			$alias = self::getAnnotation(self::ANNOTATION_COLUMN_ALIAS,$propertyComment);
			if($columnAnnotation === false){
				$column->name = $property->getName();
			}else{
				$column->name = $columnAnnotation;
			}
			if($alias !== false){
				$column->alias = $alias;
			}
			$type = self::getAnnotation(self::ANNOTATION_COLUMN_TYPE,$propertyComment);
			if($type){
				$column->type = $type;
			}
			$id = self::getAnnotation(self::ANNOTATION_ID,$propertyComment);
			$tmp = array();
			switch(true){
				case ($id === false):
					break;
				case preg_match("/^sequence=(.*)/",$id,$tmp):
					$column->sequence = $tmp[1];
				case preg_match("/^identity/",$id):
				default:
					$column->isPrimary = true;
					break;
			}
			$readOnly = (boolean)self::getAnnotation(self::ANNOTATION_READ_ONLY,$propertyComment);
			$column->readOnly = $readOnly;
			$entityInfo->columns[strtolower($column->prop)] = $column;
		}
		$entityInfo->buildReverseColumns();
		return $entityInfo;
	}
}
