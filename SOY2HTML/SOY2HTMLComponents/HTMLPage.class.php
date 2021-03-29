<?php

/**
 * 各ページの設定をするクラスの基底となるクラス
 *
 * @package SOY2.SOY2HTML
 * @author Miyazawa
 */
class HTMLPage extends SOYBodyComponentBase{
	protected $_soy2_content;
	protected $_soy2_page;
	private $_soy2_body_element;
	private $_soy2_head_element;

	/**
	 * キャッシュファイルの生成に失敗しているか判定する為の文字数
	 * キャッシュファイルの生成に失敗すると白紙ページになってしまい、一定期間キャッシュが残ってしまう
	 */
	const CACHE_CONTENTS_LENGTH_MIN = 81;

	function __construct(){
		$this->prepare();
	}
	/**
	 * コンポーネントとして動作時
	 * 派生元のSOY2HTMLのsetIdメソッドのオーバーライド
	 *
	 * @see SOY2HTML.setId
	 */
	function setId($id){
		SOY2HTML::setId($id);
		$this->setPageParam($id);
	}
	/*
	function setParentPageParam($param){
		SOY2HTML::setParentPageParam($param);
	}
	*/
	/**
	 * コンポーネントとして動作時
	 * 派生元のSOY2HTMLのsetContentメソッドのオーバーライド
	 *
	 * @see SOY2HTML.setConetnt
	 */
	function setContent($content){
		parent::setContent($content);
		$this->setInnerHTML('<?php echo $'.$this->getParentPageParam().'["'.$this->getId().'"]; ?>');
	}
	/**
	 * コンストラクタより呼ばれ、Initializeを行う
	 */
	function prepare(){
		$this->init();
		$this->_soy2_page = array();
		$content = $this->getTemplate();
		if($content !== false && strlen($content)){
			/*
			 * PHPを許可しないときは<?と?>をエスケープする
			 * ただしXML宣言は残す
			 */
			if(defined("SOY2HTML_ALLOW_PHP_SCRIPT") && SOY2HTML_ALLOW_PHP_SCRIPT == false){
				$content = preg_replace('/\A<\?xml([^\?]*)\?>/sm','@@XML_START@@$1@@XML_END@@',$content);
				$content = str_replace(array('<?', '?>'), array('&lt;?', '?&gt;'), $content);
				$content = str_replace(array('@@XML_START@@', '@@XML_END@@'), array('<?xml', '?>'), $content);
			}
			/*
			 * PHPの短縮タグ（<?）が有効なときはxml宣言をechoするようにする
			 */
			if(ini_get("short_open_tag")){
				$content = preg_replace('/\A<\?xml/','<?php echo "<?xml"; ?>',$content);
			}
		}
		$this->_soy2_content = $content;
		if($this->_soy2_content === false && is_readable($this->getCacheFilePath(".inc.php"))){
			ob_start();
			include($this->getCacheFilePath(".inc.php"));
			$tmp = ob_get_contents();
			ob_end_clean();
			$this->_soy2_permanent_attributes = @unserialize($tmp);
		}
	}
	function getBodyElement(){
		if(is_null($this->_soy2_body_element))$this->_soy2_body_element = new HTMLPage_ChildElement("body");
		return $this->_soy2_body_element;
	}
	function getHeadElement(){
		if(is_null($this->_soy2_head_element))$this->_soy2_head_element = new HTMLPage_HeadElement("head");
		return $this->_soy2_head_element;
	}
	/**
	 * SOY2HTMLオブジェクトのインスタンスを作成
	 *
	 * @return SOY2HTML
	 * @param SoyId
	 * @param クラス名
	 * @param 初期値
	 */
	function create($id,$className,$array = array()){
		if(is_object($className)){
			$obj = $className;
			$obj->setId($id);
		}else{
			$obj = SOY2HTMLFactory::createInstance($className,$array);
			$obj->setId($id);
			$obj->setParentId($this->getId());
		}
		$obj->setParentObject($this);
		$obj->init();
		if($this->_soy2_content != false){
			$obj->setContent($this->_soy2_content);
		}else{
			$obj->setIsModified(false);
			if(isset($this->_soy2_permanent_attributes[$id])){
				foreach($this->_soy2_permanent_attributes[$id] as $key => $value){
					$obj->_soy2_attribute[$key] = $value;
				}
			}
		}
		if($obj instanceof HTMLPage){
			$obj->setParentPageParam($this->getPageParam());
			$obj->setPageParam($this->getPageParam());
		}else{
			$obj->setPageParam($this->getPageParam());
		}
		return $obj;
	}
	/**
	 * IDにたいしてオブジェクトを関連付け登録します
	 *
	 * @param $id ID名
	 * @param $obj SOY2HTMLより派生したクラスオブジェクト
	 *
	 * ex:
	 * 	<p soy:id="blog_title">タイトル</p>
	 *
	 * に対して
	 *
	 * 	$this->add("blog_title",SOY2HTMLFactory::createInstance("HTMLLabel",array(
	 * 		"text" => BLOG_TITLE,
	 * 		"tag" => "p"
	 * 	)));
	 *
	 */
	function add($id,$obj){
		if(!$obj instanceof SOY2HTML){
			return;
		}
		if($obj->getId() !== $id){
			$obj = $this->create($id,$obj);
		}
		$obj->execute();
		$this->set($id,$obj);
		if($this->_soy2_content != false){
			$this->_soy2_content = $this->getContent($obj,$this->_soy2_content);
			$this->_soy2_permanent_attributes[$id] = $obj->getPermanentAttribute();//
		}
	}
	/**
	 * コンポーネントクラスを指定してadd
	 *
	 * createしてからaddしてます。
	 *
	 * @param $id SoyId
	 * @param $className クラス名
	 * @param $array = array()　setter injection
	 * @see HTMLPage.add
	 */
	function createAdd($id,$className,$array = array()){
		$this->add($id,$this->create($id,$className,$array));
	}
	/**
	 * プラグインの実行
	 */
	function parsePlugin(){
		$plugin = new PluginBase();
		while(true && SOY2HTMLPlugin::length()){
			list($tag,$line,$innerHTML,$outerHTML,$value,$suffix,$skipendtag) = $plugin->parse("[a-zA-Z0-9]*","[a-zA-Z0-9\.\/\-_\?\&\=#]*",$this->_soy2_content);
			if(!strlen($tag))break;
			$tmpPlugin = $plugin->getPlugin($suffix);
			$plugin->_attribute = array();
			if(is_null($tmpPlugin)){
				$tmpTag = $plugin->getTag();
				$plugin->setTag($tag);
				$plugin->parseAttributes($line);
				$plugin->setInnerHTML($innerHTML);
				$plugin->setOuterHTML($outerHTML);
				$plugin->setSkipEndTag($skipendtag);
				$this->_soy2_content = $this->getContent($plugin,$this->_soy2_content);
				$this->_soy2_content = str_replace(":".$suffix,"",$this->_soy2_content);
				$plugin->setTag($tmpTag);
				continue;
			}
			$tmpPlugin->_attribute = array();
			$tmpPlugin->setTag($tag);
			$tmpPlugin->parseAttributes($line);
			$tmpPlugin->setInnerHTML($innerHTML);
			$tmpPlugin->setOuterHTML($outerHTML);
			$tmpPlugin->setParent($this);
			$tmpPlugin->setSkipEndTag($skipendtag);
			$tmpPlugin->setSoyValue($value);
			$tmpPlugin->execute();
			$this->_soy2_content = $this->getContent($tmpPlugin,$this->_soy2_content);
		}
		$plugin = null;
	}
	/**
	 * キャッシュを生成し、画面上にContentを表示します
	 */
	function display(){
		if($this->_soy2_body_element)$this->_soy2_content = $this->_soy2_body_element->convert($this->_soy2_content,$this->getPageParam());
		if($this->_soy2_head_element)$this->_soy2_content = $this->_soy2_head_element->convert($this->_soy2_content,$this->getPageParam());
		$page = &$this->_soy2_page;
		if($this->_soy2_body_element)$page = $this->_soy2_body_element->execute($page);
		if($this->_soy2_head_element)$page = $this->_soy2_head_element->execute($page);
		$this->parsePlugin();
		$this->parseMessageProperty();
		$filePath = $this->getCacheFilePath();
		$this->createCacheFile();
		$this->createPermanentAttributesCache();
		if(file_exists($filePath)){	//キャッシュファイルを作成できなかった場合
			$page = &HTMLPage::getPage();
			if($this->getId()){
				$page[$this->getId()] = $this->_soy2_page;
			}else{
				$page = $this->_soy2_page;
			}
			ob_start();
			include($filePath);
			$html = ob_get_contents();
			ob_end_clean();
		}else{
			$html = "";
		}

		$layoutDir = SOY2HTMLConfig::LayoutDir();
		$layout = $this->getLayout();
		if($layoutDir && is_file($layoutDir . $layout)){
			include($layoutDir . $layout);
		}else{
			echo $html;
		}
		self::popPageStack();
	}
	/**
	 * 対応する部分の書き換え
	 * @see SOY2HTML.execute
	 */
	function execute(){
		$this->_soy2_innerHTML = '<?php echo @$'.$this->getParentPageParam().'["'.$this->getId().'"]; ?>';
	}
	/**
	 * 現在のContentを取得します
	 *
	 * @return 現在のContent
	 */
	function getObject(){
		ob_start();
		$this->display();
		$content = ob_get_contents();
		ob_end_clean();
		return $content;
	}
	/**
	 * テンプレートファイルの読み込み
	 *
	 * 上書きすることで独自のテンプレートを使用可能
	 *
	 * @return 読み込まれたTemplete（Content)
	 */
	function getTemplate(){
		if($this->isModified() != true){
			return false;
		}
		$file = $this->getTemplateFilePath();
		if(!file_exists($file)){
			return "";
		}
		return file_get_contents($file);
	}
	/**
	 * テンプレートファイルパスの読み込み
	 * これを上書きすることで任意のテンプレートを読み込ませることが出来る
	 * @return テンプレートファイルのパス
	 */
	function getTemplateFilePath(){
		$dir = dirname($this->getClassPath());
		if(strlen($dir)>0 && $dir[strlen($dir)-1] != "/")$dir .= "/";	//end by "/"
		$templateDir = SOY2HTMLConfig::TemplateDir();
		if($templateDir){
			$pageDir = SOY2HTMLConfig::PageDir();
			$dir = str_replace($pageDir,$templateDir,$dir);
		}
		$lang = SOY2HTMLConfig::Language();
		if(strlen($lang) > 0){
			$lang_html = $dir . get_class($this) . "_" . $lang . ".html";
			if(file_exists($lang_html)){
				return $lang_html;
			}
		}
		//隠しモード：同名のHTMLファイルのファイル名の頭に_(アンダースコア)を付与すると優先的に読み込む
		$hidden_mode_html = $dir . "_" . get_class($this) . ".html";
		if(file_exists($hidden_mode_html)){
			return $hidden_mode_html;
		}

		return $dir . get_class($this) . ".html";
	}
	/**
	 * キャッシュファイルのパス
	 *
	 * @return キャッシュファイルのパス
	 */
	function getCacheFilePath($extension = ".html.php"){
		return
			SOY2HTMLConfig::CacheDir()
			.SOY2HTMLConfig::getOption("cache_prefix") .
			"cache_" . get_class($this) .'_'. $this->getId() .'_'. $this->getParentPageParam()
			."_". md5($this->getClassPath().$this->getTemplateFilePath())
			."_".SOY2HTMLConfig::Language()
			.$extension;
	}
	/**
	 * キャッシュファイルにファイルの書き込み
	 *
	 */
	function createCacheFile(){
		$filePath = $this->getCacheFilePath();
		if(!strlen($filePath)){
			return;
		}
		if(!$this->isModified()){
			return;
		}
		$fp = @fopen($filePath,"w");
		if(!$fp){
			throw new SOY2HTMLException("[SOY2HTML]Can not create cache file.");
		}
		fwrite($fp,'<?php /* created ' . date("Y-m-d h:i:s") .' */ ?>');
		fwrite($fp,"\r\n");
		if(strlen($this->getId())){
			fwrite($fp,'<?php $'.$this->getPageParam().' = HTMLPage::getPage("'.$this->getId().'"); ?>');
		}else{
			fwrite($fp,'<?php $'.$this->getPageParam().' = HTMLPage::getPage(); ?>');
		}
		fwrite($fp,"\r\n");
		fwrite($fp,$this->_soy2_content);
		fclose($fp);
	}
	/**
	 * 永続化の属性値を作成
	 */
	function createPermanentAttributesCache(){
		$filePath = $this->getCacheFilePath(".inc.php");
		if($this->isModified() != true && file_exists($filePath)){
			return;
		}
		$fp = @fopen($filePath,"w");
		fwrite($fp,"<?php ");
		fwrite($fp,'echo \''.serialize($this->_soy2_permanent_attributes).'\';');
		fwrite($fp,"?>");
		fclose($fp);
	}
	/**
	 * キャッシュを作成するかどうか
	 * @return true:作成すべき false:しなくてもよい
	 */
	function isModified(){
		$filePath = $this->getCacheFilePath();
		//キャッシュの出力に失敗した場合は強制的にキャッシュの生成 キャッシュの生成に失敗した時、キャッシュファイルの文字数が81になるので、81以下の場合は失敗と見なす
		if(file_exists($filePath)){
			$len = 0;
			$fp = fopen($filePath, "r");
			if($fp){
				while ($line = fgets($fp)) {
					$len += strlen(trim($line));
					if($len > self::CACHE_CONTENTS_LENGTH_MIN) break;
  				}
			}
			fclose($fp);
			if($len <= self::CACHE_CONTENTS_LENGTH_MIN) return true;
		}
		$templateFilePath = $this->getTemplateFilePath();
		$reflection = new ReflectionClass(get_class($this));
		$classFilePath = $reflection->getFileName();
		if(defined("SOY2HTML_CACHE_FORCE") && SOY2HTML_CACHE_FORCE == true){
			return true;
		}
		if(!file_exists($templateFilePath)){
			return false;
		}
		if(
			file_exists($filePath)
			&& filemtime(__FILE__) <= filemtime($filePath)
			&& filemtime($templateFilePath) <= filemtime($filePath)
			&& filemtime($classFilePath) <= filemtime($filePath)
		){
			return false;
		}
		return true;
	}
	/**
	 * ページを取得する
	 *
	 * @return ページ
	 */
	public static function &getPage($id = null){
		static $page;
		if(is_null($page)){
			$page = array();
		}
		$tmpPage = &$page;
		$pageStack = self::$_soy2_page_stack;
		foreach($pageStack as $stack){
			if(!isset($tmpPage[$stack]))$tmpPage[$stack] = array();
			$tmpPage = &$tmpPage[$stack];
		}
		if($id){
			if(!isset($tmpPage[$id]))$tmpPage[$id] = array();
			return $tmpPage[$id];
		}
		return $tmpPage;
	}
	/*
	 * 以下、ページの入れ子構造を実現するためのスタック
	 */
	private static $_soy2_page_stack = array();
	private static function pushPageStack($id){
		if(!$id)return;
		self::$_soy2_page_stack[] = $id;
	}
	private static function popPageStack(){
		array_pop(self::$_soy2_page_stack);
	}
	/**
	 * ページに値をセットする
	 * @see SOY2HTML.set
	 */
	function set($id,SOY2HTML &$obj,&$page = null){
		$page = &$this->_soy2_page;
		parent::set($id,$obj,$page);
	}
	/**
	 * タイトルを書き換える
	 */
	function setTitle($title){
		$this->getHeadElement()->setTitle($title);
	}
	/**
	 * @override
	 * MessagePropertyを置き換える
	 */
	function parseMessageProperty(){
		if($this->getIsModified()){
			foreach($this->_message_properties as $key => $message){
				$tmpKey = "@@".$key.";";
				$this->_soy2_content = str_replace($tmpKey,$message,$this->_soy2_content);
			}
		}
	}
	/**
	 * レイアウトを取得
	 * HTMLPageのディフォルトはnull(レイアウトを使わない)
	 */
	function getLayout(){
		return null;
	}
}
/**
 * HTMLTemplatePageではテンプレートHTMLをパラメータとして渡す
 * テンプレートファイルがないのでその点でキャッシュ周りの処理が変わる
 *
 * 例
 * $htmlObj->create("some_soy_id","HTMLTemplatePage", array(
 * 	"arguments" => array("some_soy_id","<h1>test</h1><p soy:id=\"test\">test</p>")
 * ));
 */
class HTMLTemplatePage extends HTMLPage{
	var $_id;
	var $_html;
	private $hash = "";
	function __construct($args){
		$this->_id = $args[0];
		$this->_html = $args[1];
		$this->hash = md5($this->_html);
		parent::__construct();
	}
	function getTemplate(){
		return $this->_html;
	}
	function getId(){
		return $this->_id;
	}
	function getParentId(){
		return $this->getId();
	}
	function getPageParam(){
		return $this->_id;
	}
	function getCacheFilePath($extension = ".html.php"){
		return SOY2HTMLConfig::CacheDir()
			.SOY2HTMLConfig::getOption("cache_prefix") .
			"cache_" . 'template' .'_'. $this->getId() .'_'. $this->getParentPageParam()
			."_". $this->hash
			."_".SOY2HTMLConfig::Language()
			.$extension;
	}
	function isModified(){
		if(defined("SOY2HTML_CACHE_FORCE") && SOY2HTML_CACHE_FORCE == true){
			return true;
		}
		$filePath = $this->getCacheFilePath();
		if("HTMLTemplatePage" == ($class = get_class($this))){
			$classFilePath = __FILE__;
		}else{
			$reflection = new ReflectionClass(get_class($this));
			$classFilePath = $reflection->getFileName();
		}
		if(
			file_exists($filePath)
			&& filemtime(__FILE__) <= filemtime($filePath)
			&& filemtime($classFilePath) <= filemtime($filePath)
		){
			return false;
		}
		return true;
	}
}
/**
 * 子エレメント
 * 追記したりとか
 */
class HTMLPage_ChildElement{
	protected $tag;
	private $insert = array();
	private $append = array();
	function __construct($tag){
		$this->tag = $tag;
	}
	function insertHTML($html){
		$this->insert[] = $html;
	}
	function appendHTML($html){
		$this->append[] = $html;
	}
	function execute($array){
		$array["page_" . $this->tag . "_insert"] = implode("\n",$this->insert);
		$array["page_" . $this->tag . "_append"] = implode("\n",$this->append);
		return $array;
	}
	function convert($html,$pageParam){
		if($html != false){
			if(preg_match('/(<'.$this->tag.'\s?[^>]*>)/i',$html,$tmp1,PREG_OFFSET_CAPTURE)){
			 	$start = $tmp1[1][0];
			 	$out = $tmp1[1][0] . "\n" . '<?php echo $'.$pageParam.'["page_'.$this->tag.'_insert"]; ?>';
				$html = str_replace($start,$out,$html);
			}
			if(preg_match('/(<\/'.$this->tag.'\s?[^>]*>)/i',$html,$tmp1,PREG_OFFSET_CAPTURE)){
				$start = $tmp1[1][0];
			 	$out = '<?php echo $'.$pageParam.'["page_'.$this->tag.'_append"]; ?>' ."\n" . $tmp1[1][0];
				$html = str_replace($start,$out,$html);
			}
		}
		return $html;
	}
}
class HTMLPage_HeadElement extends HTMLPage_ChildElement{
	private $title;
	private $metas = array();
	function __construct($tag = null){
		if($tag == null)$tag = "head";
		parent::__construct($tag);
	}
	function setTitle($title){
		$this->title = $title;
	}
	function getTitle(){
		return $this->title;
	}
	function _getMeta($name){
		if(!isset($this->metas[$name])){
			$this->metas[$name] = array("insert"=>"","content"=>false,"append"=>"");	//
		}
		return $this->metas[$name];
	}
	/**
	 * 元のmetaに書かれているものは残して後ろに追加
	 */
	function appendMeta($name,$content){
		$array = $this->_getMeta($name);
		$array["append"] .= $content;
		$this->metas[$name] = $array;
	}
	/**
	 * 元のmetaに書かれているものをは残して前に追加
	 */
	function insertMeta($name,$content){
		$array = $this->_getMeta($name);
		$array["insert"] .= $content;
		$this->metas[$name] = $array;
	}
	/**
	 * 元のmetaに書かれているものを消去
	 */
	function setMeta($name,$content){
		$array = $this->_getMeta($name);
		$array["content"] = $content;
		$this->metas[$name] = $array;
	}
	/**
	 * 設定を一回空にする(setMeta falseでもいいかもしれない)
	 */
	function clearMeta($name){
		if(isset($this->metas[$name]))unset($this->metas[$name]);
	}
	function execute($array){
		$array["page_" . $this->tag . "_title"] = $this->getTitle();
		$array["page_" . $this->tag . "_meta"] = $this->metas;
		$array = parent::execute($array);
		return $array;
	}
	function convert($html,$pageParam){
		if($html != false){
			if( preg_match('/(<title\s?[^>]*>)/i',$html,$tmp1,PREG_OFFSET_CAPTURE)
			 && preg_match('/(<\/title\s?[^>]*>)/i',$html,$tmp2,PREG_OFFSET_CAPTURE)
			){
				$start = $tmp1[1][1];
				$end = $tmp2[1][1] + strlen($tmp2[1][0]);
				$out = $tmp1[1][0] . '<?php echo htmlspecialchars($'.$pageParam.'["page_'.$this->tag.'_title"],ENT_QUOTES); ?>' . $tmp2[1][0];
				$in = substr($html,$start,$end - $start);
				$html= str_replace($in,$out,$html);
			}
			preg_match_all('/(<meta([^>]*)\/?>)/i',$html,$meta,PREG_OFFSET_CAPTURE);
			$added = array();
			foreach($meta[1] as $key => $array){
				if(preg_match('/<?php/',$meta[2][$key][0])){
					continue;
				}
				if(preg_match('/name\s*=\s*"([^"]+)"/i',$meta[2][$key][0],$tmp)){
					$name = $tmp[1];
					$content = "";
					if(preg_match('/content\s*=\s*"([^"]+)"/i',$meta[2][$key][0],$tmp2)){
						$content = $tmp2[1];
					}
					$replace = '<?php ' .
					           '$content = "'.strtr($content,array("'" => "\\'", "\\" => "\\\\")).'"; ' .
					           'if(isset($'.$pageParam.'["page_'.$this->tag.'_meta"]["'.$name.'"])){ ' .
					           '  $array = $'.$pageParam.'["page_'.$this->tag.'_meta"]["'.$name.'"]; ' .
					           '  if($array["content"] == false){ $content = htmlspecialchars($array["insert"],ENT_QUOTES,SOY2HTML::ENCODING) . $content . htmlspecialchars($array["append"],ENT_QUOTES,SOY2HTML::ENCODING); }else{ $content = htmlspecialchars($array["content"],ENT_QUOTES,SOY2HTML::ENCODING); }' .
					           '}' .
					           'echo \'<meta name="'.htmlspecialchars($name,ENT_QUOTES,SOY2HTML::ENCODING).'" content="\'.$content.\'" />\' . "\n"; ?>';
					$html = str_replace($array[0],$replace,$html);
					$added[] = $name;
				}
			}
			$head = "";
			foreach($this->metas as $key => $array){
				if(in_array($key,$added))continue;
				$head = '<?php if(isset($'.$pageParam.'["page_'.$this->tag.'_meta"]["'.$key.'"])){ ' .
						'	echo \'<meta name="'.htmlspecialchars($key,ENT_QUOTES).'" content="\'.htmlspecialchars(' .
								'$'.$pageParam.'["page_'.$this->tag.'_meta"]["'.$key.'"]["insert"] . ' .
								'$'.$pageParam.'["page_'.$this->tag.'_meta"]["'.$key.'"]["content"] . ' .
								'$'.$pageParam.'["page_'.$this->tag.'_meta"]["'.$key.'"]["append"],ENT_QUOTES' .
							').\'" />\' . "\n";'.
						'} ?>';
			}
			if(strlen($head) >0 && stripos($html,'</head>')!==false){
	    		$html = preg_replace('/<\/head>/i',$head.'</head>',$html);
			}
		}
		$html = parent::convert($html,$pageParam);
		return $html;
	}
}
