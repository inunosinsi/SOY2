<?php

/**
 * 保存・削除などを自動化するメソッドを自動で追加
 */
class SOY2DAO_EntityBase {
	/**
	 * permanent me
	 */
    final function save(){
    	$dao = $this->getDAO();
    	if($this->check()){
    		if(strlen($this->getId())>0){
	    		$dao->update($this);
	    	}else{
	    		$id = $dao->insert($this);
	    		$this->setId($id);
	    	}
	    	return $this->getId();
    	}else{
    		return null;
    	}
    }
    /**
     * delete me
     */
    final function delete(){
    	$this->getDAO()->delete($this->getId());
    }
    public static function deleteAll(){
    	eval('$obj = new static;');
    	$dao = $obj->getDAO();
    	$dao->deleteAll();
    }
	/**
     * get by id
     */
    final function get(int $id=0){
    	if($id > 0){
    		$res = $this->getDAO()->getById($id);
    	}else{
    		$res = $this->getDAO()->getById($this->getId());
    	}
    	return $res;
    }
    private $_dao;
    /**
     * build dao
     */
    final function getDAO(){
    	if(is_null($this->_dao)){
	    	$daoClass = get_class($this) . "DAO";
	    	if(!class_exists($daoClass)){
	    		$ref = new ReflectionClass($this);
	    		$filepath = dirname($ref->getFileName()) . "/" . $daoClass . ".class.php";
	    		if(file_exists($filepath))include_once($filepath);
	    	}
	    	$this->_dao = SOY2DAOFactory::create($daoClass);
    	}
    	return $this->_dao;
    }
	public final function begin(){
		$dao = $this->getDAO();
		$dao->begin();
	}
	public final function commit(){
		$dao = $this->getDAO();
		$dao->commit();
	}
	public final function rollback(){
		$dao = $this->getDAO();
		$dao->rollback();
	}
    function __wakeup(){
    	$this->_dao = null;
    }
}
