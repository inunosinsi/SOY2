<?php

/**
 * @package SOY2.SOY2Logger
 */
class SOY2Logger{
	const LEVEL_DEBUG =		0x1F;	//011111
	const LEVEL_INFO =		0x1E;	//011110
	const LEVEL_WARN = 		0x1C;	//011100
	const LEVEL_ERROR =		0x18;	//011000
	const LEVEL_FATAL =		0x10;	//010000
	const DEBUG		=	0x01;		//000001
	const INFO		=	0x02;		//000010
	const WARN		=	0x04;		//000100
	const ERROR		=	0x08;		//001000
	const FATAL		=	0x10;		//010000
	private $loggers = array();
	private $level = SOY2Logger::LEVEL_ERROR;	//ディフォルトはエラー以上
	private $startTime;
	private $stack;
	/**
	 * ロガーを追加
	 */
	public static function addLogger($id,$class, $options = null, $level = SOY2Logger::LEVEL_DEBUG){
		$logger = self::getLogger();
		if(is_object($class)){
			$obj = $class;
		}else{
			if(!class_exists($class)){
				$class = "SOY2Logger_" . $class;
			}
			if(!class_exists($class))return;
			$obj = new $class($options);
		}
		if($obj instanceof SOY2Logger_Base){
			$logger->loggers[$id] = array(
				"level" => $level,
				"logger" => $obj
			);
		}else{
			$logger->debug(get_class($obj) . " is not logger.");
		}
	}
	/**
	 * ディフォルトの出力レベルを設定する
	 */
	public static function setLevel($level){
		$logger = self::getLogger();
		$logger->level = $level;
	}
	/**
	 * ロガーオブジェクトを取得
	 */
	public static function getLogger(){
		static $_inst;
		if(!$_inst){
			$_inst = new SOY2Logger();
			$_inst->startTime = microtime(true);
			$logger = new SOY2Logger_Base();
			$_inst->loggers["SOY2Logger"] = array(
				"level" => SOY2Logger::DEBUG,
				"logger" => $logger
			);
		}
		return $_inst;
	}
	/**
	 * debugを出力する
	 */
	function debug($str){
		$this->log(SOY2Logger::DEBUG,"DEBUG",$str);
	}
	/**
	 * infoを出力する
	 */
	function info($str){
		$this->log(SOY2Logger::INFO,"INFO",$str);
	}
	/**
	 * warnを出力する
	 */
	function warn($str){
		$this->log(SOY2Logger::WARN,"WARN",$str);
	}
	/**
	 * errorを出力する
	 */
	function error($str){
		$this->log(SOY2Logger::ERROR,"ERROR",$str);
	}
	/**
	 * fatalを出力
	 */
	function fatal($str){
		$this->log(SOY2Logger::FATAL,"FATAL",$str);
	}
	/**
	 * ログを出力する
	 */
	function log($level,$levelText,$str){
		if(!($this->level & $level)){
			return;
		}
		$this->stack = $this->getStack();
		foreach($this->loggers as $id => $array){
			$loggerLevel = $array["level"];
			$logger = $array["logger"];
			if($loggerLevel & $this->level && $loggerLevel & $level){
				$log = $this->format($levelText,$str,$id,$logger);
				$logger->log($log);
			}
		}
	}
	/**
	 *
	 * フォーマット毎との置き換え
	 *
	 * 使えるフォーマット一覧
	 *
	 * %L	レベル
	 * %c	logger名
	 * %C	ログイベントが発生したクラスのクラス名
	 * %d	ログイベントが発生した時刻。%d{HH:mm:ss,SSS}のような指定が可能
	 * %F	ログイベントが発生したファイル名
	 * %l	ログ出力を行った行数
	 * %m	ログイベントで設定されたメッセージ
	 * %M	ログ出力を行ったメソッド名
	 * %n	プラットフォーム依存の改行
	 * %r	アプリケーションが開始してからログ出力されるまでの時間（単位：ミリ秒）
	 */
	function format($level,$str,$id,$logger){
		$format = $logger->format();
		$stack = $this->stack;
		$format = str_replace("%L",$level,$format);
		$format = str_replace("%c",$id,$format);
		$format = str_replace("%C",@$stack["Class"],$format);
		if(preg_match('/%d(\{(.+)\})?/',$format,$tmp)){
			if(!isset($tmp[2]) OR !$tmp[2])$tmp[2] = "Y:m:d H:i:s";
			$format = str_replace($tmp[0],date($tmp[2]),$format);
		}
		$format = str_replace("%F",$stack["FileName"],$format);
		$format = str_replace("%l",$stack["Line"],$format);
		$format = str_replace("%M",$stack["Method"],$format);
		$format = str_replace("%r",(microtime(true)-$this->startTime),$format);
		$format = str_replace("%n","\n",$format);
		$format = str_replace("%m",$str,$format);
		return $format;
	}
	/**
	 * logに出力する情報を取得する
	 */
	function getStack(){
		$array = debug_backtrace();
		$next = -1;
		foreach($array as $key => $stack){
			if( $stack["file"] == __FILE__
			 && $stack["function"] == "log"
			 && $stack["class"] == __CLASS__
			){
				$next = count($array) - $key - 1;
				continue;
			}
			if($next > 0){
				$next--;
				if($next != 0){
					continue;
				}
			}
			if($next == 0){
				return array(
					"FileName" => $stack["file"],
					"Class" => @$stack["class"],
					"Line" => $stack["line"],
					"Method" => $stack["function"]
				);
			}
		}
	}
}
/**
 * @package SOY2.SOY2Logger
 */
interface SOY2LoggerInterface{
	function log($str);
	function format();
}
/**
 *
 * ロガーのベース
 *
 * @package SOY2.SOY2Logger
 */
class SOY2Logger_Base implements SOY2LoggerInterface{
	/**
	 * ログを出力
	 */
	function log($str){
		echo $str . "\n";
	}
	/**
	 * フォーマット文字列を返す
	 */
	function format(){
		return "[%c][%L](%d) %m - %C#%M(%F:%l)";
	}
}
/**
 *
 * 汎用的なロガー
 *
 * @package SOY2.SOY2Logger
 */
class SOY2Logger_SimpleLogger extends SOY2Logger_Base{
	private $format;
	function __construct($options){
		if(isset($options["format"])){
			$this->format = $options["format"];
		}
	}
	function format(){
		return ($this->format) ? $this->format : parent::format();
	}
}
/**
 * 汎用的なファイル出力のためのロガー
 */
class SOY2Logger_FileLogger extends SOY2Logger_SimpleLogger{
	private $filePath;
	function __construct($options = array()){
		$this->setFilePath(@$options["path"]);
		parent::__construct($options);
	}
	/**
	 * ログを出力
	 */
	function log($str){
		$filepath = $this->getFilePath();
		if(strlen($filepath)>0)
			file_put_contents($filepath,$str . "\n", FILE_APPEND | LOCK_EX);
	}
	/**
	 * ファイルパスを取得
	 */
	function getFilePath() {
		return $this->filePath;
	}
	/**
	 * ファイルパスを設定
	 */
	function setFilePath($filePath) {
		$this->filePath = $filePath;
	}
}
class SOY2Logger_SOY2DebugLogger extends SOY2Logger_SimpleLogger{
	function __construct($option = array()){
		parent::__construct($option);
	}
	function log($str){
		SOY2Debug::trace($str);
	}
}
/**
 * 汎用的なファイル出力のためのロガー
 */
class SOY2Logger_RotationFileLogger extends SOY2Logger_FileLogger{
	private $maxLineCount = 400;
	private $maxFileCount = 10;
	private $suffix = "";
	function __construct($options){
		if(isset($options["line"]))$this->setMaxLineCount((int)$options["line"]);
		if(isset($options["count"]))$this->setMaxFileCount((int)$options["count"]);
		if(isset($options["suffix"]))$this->setSuffix((string)$options["suffix"]);
		parent::__construct($options);
	}
	/**
	 * ログを行います
	 */
	function log($str){
		$filepath = $this->getFilePath();
		$fdata = @file_get_contents($filepath);
		if(count(explode("\n",$fdata)) > $this->getMaxLineCount()){
			$this->rotation($filepath,$fdata);
		}
		parent::log($str);
	}
	/**
	 * ファイルのローテーションを実行する
	 */
	function rotation($filepath,$contents){
		$fp = fopen($filepath,"w");	//ここで0バイトになる
		flock($fp,LOCK_EX);
		$dirname = dirname($filepath)."/";
		$files = scandir($dirname);
		$logs = array();
		foreach($files as $file){
			if($file[0] == ".") continue;
			if(strpos($file,basename($filepath).$this->getSuffix().".") === 0){
				$logs[] = $file;
			}
		}
		$next_count = count($logs)+1;
		if($next_count < $this->getMaxFileCount()){
			$nextFilePath = basename($filepath).$this->getSuffix().".".$next_count;
			$logs[] = $nextFilePath;
		}
		$logs = array_reverse($logs);
		$logCnt = count($logs) - 1;
		for($i=0;$i<$logCnt;++$i){
			@unlink($dirname.$logs[$i]);
			rename($dirname.$logs[($i+1)],$dirname.$logs[$i]);
		}
		@file_put_contents($filepath.$this->getSuffix().".1",$contents);
		flock($fp,LOCK_UN);
		fclose($fp);
	}
	function getMaxLineCount() {
		return $this->maxLineCount;
	}
	function setMaxLineCount($maxLineCount) {
		$this->maxLineCount = $maxLineCount;
	}
	function getMaxFileCount() {
		return $this->maxFileCount;
	}
	function setMaxFileCount($maxFileCount) {
		$this->maxFileCount = $maxFileCount;
	}
	function getSuffix() {
		return $this->suffix;
	}
	function setSuffix($suffix) {
		$this->suffix = $suffix;
	}
}
class SOY2Logger_DateFileLogger extends SOY2Logger_FileLogger{
	/**
	 * ファイルパスを取得
	 */
	function getFilePath() {
		$filepath = parent::getFilePath();
		return $filepath . "_" . date("Ymd");
	}
}
class SOY2Logger_DateRotationFileLogger extends SOY2Logger_RotationFileLogger{
	/**
	 * ファイルパスを取得
	 */
	function getFilePath() {
		$filepath = parent::getFilePath();
		return $filepath . "_" . date("Ymd");
	}
}
