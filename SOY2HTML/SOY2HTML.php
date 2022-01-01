<?php

/**
 * SOY2HTMLの基底クラス
 * @package SOY2.SOY2HTML
 * @author Miyazawa
 */
class SOY2HTMLBase{
	private $_soy2_classPath;
	protected $_soy2_functions = array();
	protected function getClassPath(){
		if(is_null($this->_soy2_classPath)){
			$reflection = new ReflectionClass(get_class($this));
			$classFilePath = $reflection->getFileName();
			$this->_soy2_classPath = str_replace("\\", "/", $classFilePath);
		}
		return $this->_soy2_classPath;
	}
	/**
	 * パラメータに与えられた関数を実行し、結果を返す
	 *
	 * @param $name 関数名
	 * @param $args パラメータ
	 *
	 * @return 実行された関数の結果
	 *
	 */
	function __call(string $name, array $args){
		if(!$this->functionExists($name) && $name != "HTMLPage" && $name != "WebPage"){
			throw new SOY2HTMLException("Method not found: ".$name);
		}
		$func = $this->_soy2_functions[$name];
		$code = $func['code'];
		$argments = $func['args'];
		$variant = "";
		if(is_array($argments)){
			$argsCnt = count($argments);
			for($i = 0; $i < $argsCnt; ++$i){
				$variant .= $argments[$i].' = $args['.$i.'];';
			}
		}
		return eval($variant.$code.";");
	}

	/**
	 * 関数を追加登録します
	 *
	 * @param $name 関数名
	 * @param $args パラメータ
	 * @param $code 実行内容
	 */
	function addFunction(string $name, array $args, string $code){
		$this->_soy2_functions[$name]['args'] = $args;
		$this->_soy2_functions[$name]['code'] = $code;
	}
	function functionExists($name){
		return array_key_exists($name,$this->_soy2_functions);
	}
}
/**
 * 各コンポーネントの基底となるクラス
 *
 * @see SOY2HTMLBase
 * @package SOY2.SOY2HTML
 * @author Miyazawa
 */
abstract class SOY2HTML extends SOY2HTMLBase{
	const HTML_BODY = '_HTML_BODY_';
	const SKIP_BODY = '_SKIP_BODY_';//空要素：処理としては開始タグのみを出力する
	const SOY_BODY  = '_SOY_BODY_';
	const SOY_TYPE = SOY2HTML::HTML_BODY;
	const ENCODING = 'UTF-8';
	protected $tag = "[a-zA-Z_:][a-zA-Z0-9_:.\-]*|!--";//XML対応で_:なども追加
	protected $_soy2_id;
	protected $_soy2_parentId = null;
	protected $_soy2_parent = null;
	protected $_soy2_prefix = "soy";
	protected $_soy2_pageParam = "page";
	protected $_soy2_parentPageParam = "page";
	protected $_soy2_isModified = true;//更新しているかどうかのフラグ
	protected $_soy2_outerHTML;
	protected $_soy2_innerHTML;
	/*
	 * array(属性名 => 属性値, ...)
	 * createInstanceの第2引数で設定した値が入る
	 */
	public $_soy2_attribute = array();
	/*
	 * array(属性名 => 「属性名」に関する情報, ...)
	 * createInstanceの第2引数で設定した値の場合はその属性が真偽値かどうかが入る
	 * テンプレートのHTMLに元から書かれている値もここに入る
	 */
	public $_attribute      = array();
	protected $_soy2_style;//styleは特別扱い
	protected $_soy2_visible = true;
	protected $_skip_end_tag = false;
	protected $_message_properties = array();
	protected $_soy2_permanent_attributes = array();
	abstract function getObject();
	/**
	 * 準備
	 */
	function init(){
	}
	/**
	 * タグに対応する部分を書き換える
	 */
	function execute(){
		if($this->getComponentType() == SOY2HTML::SKIP_BODY){
			return;
		}
		$this->_soy2_innerHTML ='<?php echo $'.$this->_soy2_pageParam.'["'.$this->_soy2_id.'"]; ?>';
	}
	/**
	 * 前置詞を取得
	 */
	function getSoy2Prefix(){
		return $this->_soy2_prefix;
	}
	/**
	 * 前置詞を設定
	 */
	function setSoy2Prefix($prefix){
		$this->_soy2_prefix = $prefix;
	}
	/**
	 * soy:id を登録する
	 */
	function setId($id){
		$this->_soy2_id = $id;
	}
	/**
	 * getter soy:id
	 */
	function getId(){
		return $this->_soy2_id;
	}
	/**
	 * setter parentId
	 */
	function setParentId($id){
		$this->_soy2_parentId = $id;
	}
	/**
	 * getter parentId
	 */
	function getParentId(){
		return $this->_soy2_parentId;
	}
	/**
	 * setter parent
	 */
	function setParentObject($obj){
		$this->_soy2_parent = $obj;
	}
	/**
	 * getter parent
	 */
	function getParentObject(){
		return $this->_soy2_parent;
	}
	/**
	 * setter pageParam
	 */
	function setPageParam($param){
		$this->_soy2_pageParam = $param;
	}
	/**
	 * getter pageParam
	 */
	function getPageParam(){
		return $this->_soy2_pageParam;
	}
	/**
	 * setter ParentPageParam
	 */
	function setParentPageParam($param){
		$this->_soy2_parentPageParam = $param;
	}
	/**
	 * getter ParentPageParam
	 */
	function getParentPageParam(){
		return $this->_soy2_parentPageParam;
	}
	/**
	 * soy:idの存在するタグを登録します
	 * 例:<p soy:id="title"/>ならばp
	 */
	function setTag($tag){
		$this->tag = $tag;
	}
	/**
	 * getter tag
	 */
	function getTag(){
		return $this->tag;
	}
	/**
	 * getter soy_type
	 */
	function getComponentType(){
		$func = function(){
			//PHP5.3でも一応動作する　調査用
			if(is_null($this) || is_string($this)) return "_HTML_BODY_";
			$className = get_class($this);
			return $className::SOY_TYPE;
		};
		return $func();
	}
	/**
	 * setter soy_visible
	 */
	function setVisible($value){
		$this->_soy2_visible = (boolean)$value;
	}
	/**
	 * getter soy_visible
	 */
	function getVisible(){
		return $this->_soy2_visible;
	}
	/**
	 * setter isModified
	 */
	function setIsModified($value){
		$this->_soy2_isModified = $value;
	}
	/**
	 * getter isModified
	 */
	function getIsModified(){
		return $this->_soy2_isModified;
	}
	/**
	 * setter innerHTML
	 */
	function setInnerHTML($innerHTML){
		$this->_soy2_innerHTML = $innerHTML;
	}
	/**
	 * getter innerHTML
	 */
	function getInnerHTML(){
		return $this->_soy2_innerHTML;
	}
	/**
	 * setter outerHTML
	 */
	function setOuterHTML($outerHTML){
		$this->_soy2_outerHTML = $outerHTML;
	}
	/**
	 * getter outerHTML
	 */
	function getOuterHTML(){
		return $this->_soy2_outerHTML;
	}
	function setSkipEndTag($boolean){
		$this->_skip_end_tag = $boolean;
	}
	function getIsSkipEndTag(){
		return $this->_skip_end_tag;
	}
	/**
	 * HTMLをParseして必要なContentを取得する
	 *
	 * @param $content HTMLソースコード
	 */
	function setContent($content){
		list($tag,$line,$innerHTML,$outerHTML,$value,$suffix,$skipendtag) = $this->parse("id",$this->_soy2_id, (string)$content);
		$this->tag = $tag;
		$this->parseAttributes($line);
		$this->_soy2_innerHTML = $innerHTML;
		$this->_soy2_outerHTML = $outerHTML;
		$this->setSkipEndTag($skipendtag);
	}
	/**
	 * @return array(tag,line,innerhtml,outerhtml,value,suffix,skipendtag)
	 */
	function parse(string $suffix, string $value, string $content){
		$result = array(
			"tag" => "",
			"line" => "",
			"innerHTML" => "",
			"outerHTML" => "",
			"value" => "",
			"suffix" => "",
			"skipendtag" => false
		);
		if($content instanceof HTMLList_DummyObject) $content = "";
		switch ($this->getComponentType()) {
			case SOY2HTML::HTML_BODY:
				$regex = '/<(('.$this->tag.')[^<>]*\s'.$this->_soy2_prefix.':('.$suffix.')=\"('.$value.')\"\s?[^>]*)>/i';
				$tmp = array();
				if(is_string($content) && preg_match($regex,$content,$tmp,PREG_OFFSET_CAPTURE)){
					$start = $tmp[0][1];
					$end = 0;
					$tmpValue = $tmp[4][0];
					$endTag = $tmp[2][0];
					$endPrefix = $this->_soy2_prefix;
					if($endTag != "!--"){
						$endTag = '\/'. $endTag;
					}else{
						$endPrefix = '\/' . $endPrefix;
					}
					if(strpos($tmpValue,"\\") !== false)$tmpValue = str_replace("\\","\\\\",$tmpValue);
					if(strpos($tmpValue,"/") !== false)$tmpValue = str_replace("/","\\/",$tmpValue);
					if(strpos($tmpValue,"*") !== false)$tmpValue = str_replace("*","\\*",$tmpValue);
					if(strpos($tmpValue,"+") !== false)$tmpValue = str_replace("+","\\+",$tmpValue);
					if(strpos($tmpValue,"?") !== false)$tmpValue = str_replace("?","\\?",$tmpValue);
					if(strpos($tmpValue,".") !== false)$tmpValue = str_replace(".","\\.",$tmpValue);
					if(strpos($tmpValue,"-") !== false)$tmpValue = str_replace("-","\\-",$tmpValue);
					$endRegex = '/(<('.$endTag.')[^<>]*\s'.$endPrefix.':'.$suffix.'=\"'.$tmpValue.'\"\s?[^>]*>)/';
					$endRegex_short = strlen($tmpValue) ? '/(<!--[^<>]*\s\/'.$tmpValue.'\s[^>]*-->)/' : "" ;//短縮形：<!-- /entry -->のように書ける
					$line = $tmp[1][0];
					$tag = $tmp[2][0];
					$suffix = $tmp[3][0];
					$value = $tmp[4][0];
					$result["line"] = $line;
					$result["tag"] = $tag;
					$result["suffix"] = $suffix;
					$result["value"] = $value;
					$innerHTML = "";
					$outerHTML = "";
					$line = trim($line);
					if(preg_match('/\/(--)?$/',$line) OR in_array(strtolower($tag),SOY2HTML::getEmptyTagList())){
						$outerHTML = $tmp[0][0];
						$result["skipendtag"] = true;
					}else if(preg_match($endRegex,$content,$tmp2,PREG_OFFSET_CAPTURE)
						|| strlen($endRegex_short) && preg_match($endRegex_short,$content,$tmp2,PREG_OFFSET_CAPTURE,$tmp[1][1])
					){
						$startOffset = $tmp[1][1];
						$endOffset = $tmp2[1][1] + strlen($tmp2[1][0]);
						$outerHTML = substr($content,$startOffset-1, $endOffset - $startOffset + 1);
						$innerHTML = substr($content,$startOffset+strlen($tmp[1][0])+1,$tmp2[1][1]-($startOffset + strlen($tmp[1][0]))-1);
					}else{
						$i = $start + strlen($tmp[0][0]);
						while($i<strlen($content)){
							$buff = $content[$i];
							if($buff === "<" && $content[$i+1] === "/"){
								$buff = substr($content,$i,strlen("</".$tag));
								$end = $i + strlen("</".$tag);
								/*
								 * 同じタグが内部にある場合は
								 * 動作がおかしくなることはありますけど、現状はこれで良いかと。
								 */
								if($buff === "</".$tag){
									while($end<strlen($content)){
										$buff2 = $content[$end];
										$buff .= $buff2;
										$end++;
										if($buff2 == ">"){
											break;
										}
									}
									break;
								}else{
									$buff = $content[$i];
								}
							}
							$innerHTML .= $buff;
							$i++;
						}
						$outerHTML = substr($content,$start,$end - $start);
					}
					$result["innerHTML"] = $innerHTML;
					$result["outerHTML"] = $outerHTML;
				}
				break;
			case SOY2HTML::SKIP_BODY:
				$regex = '/(<(('.$this->tag.')[^<>]*\s'.$this->_soy2_prefix.':('.$suffix.')=\"('.$value.')\"\s?[^>]*\/?)>)/i';
				$tmp = array();
				if(is_string($content) && preg_match($regex,$content,$tmp)){
					$result["outerHTML"] = $tmp[1];
					$result["line"] = $tmp[2];
					$result["tag"] = $tmp[3];
					$result["suffix"] = $tmp[4];
					$result["value"] = $tmp[5];
					$result["skipendtag"] = true;
				}
				break;
			case SOY2HTML::SOY_BODY:
				$startRegex = '/(<(('.$this->tag.')[^<>]*\s'.$this->_soy2_prefix.':('.$suffix.')=\"('.$value.')\"\s?[^>]*)>)/';
				$startRegex_comment = '/(<((!--)[^<>]*\s'.$this->_soy2_prefix.':('.$suffix.')=\"('.$value.')\"\s?[^>]*)>)/';
				$tmp1 = array();
				$tmp2 = array();
				if(preg_match($startRegex_comment,$content,$tmp1,PREG_OFFSET_CAPTURE)){
					$endRegex_comment = '/(<(!--)[^<>]*\s?\/'.$this->_soy2_prefix.':'.$suffix.'=\"'.$value.'\"\s?[^>]*>)/';
					$endRegex_comment_short = '/(<(!--)[^<>]*\s?\/'.$value.'\s?[^>]*>)/';
					if(preg_match($endRegex_comment,$content,$tmp2,PREG_OFFSET_CAPTURE)
						|| preg_match($endRegex_comment_short,$content,$tmp2,PREG_OFFSET_CAPTURE,$tmp1[1][1])
					){
						$startOffset = $tmp1[1][1];
						$endOffset = $tmp2[1][1] + strlen($tmp2[1][0]);
						$result["line"] = $tmp1[2][0];
						$result["tag"] = $tmp1[3][0];
						$result["suffix"] = $tmp1[4][0];
						$result["value"] = $tmp1[5][0];
						$result["outerHTML"] = substr($content,$startOffset, $endOffset - $startOffset);
						$result["innerHTML"] = substr($content,$startOffset + strlen($tmp1[1][0]),$tmp2[1][1] - ($startOffset + strlen($tmp1[1][0])));
					}
				}else if(preg_match($startRegex,$content,$tmp1,PREG_OFFSET_CAPTURE)){
					$tag = $tmp1[3][0];
					$endRegex = '/(<\/('.$tag.')[^<>]*\s'.$this->_soy2_prefix.':'.$suffix.'=\"'.$value.'\"\s?[^>]*>)/';
					$endRegex_short = '/(<\/('.$tag.')>)/';
					if(preg_match($endRegex,$content,$tmp2,PREG_OFFSET_CAPTURE)
						 || preg_match($endRegex_short,$content,$tmp2,PREG_OFFSET_CAPTURE,$tmp1[1][1])){
						$startOffset = $tmp1[1][1];
						$endOffset = $tmp2[1][1] + strlen($tmp2[1][0]);
						$result["line"] = $tmp1[2][0];
						$result["tag"] = $tmp1[3][0];
						$result["suffix"] = $tmp1[4][0];
						$result["value"] = $tmp1[5][0];
						$result["outerHTML"] = substr($content,$startOffset, $endOffset - $startOffset);
						$result["innerHTML"] = substr($content,$startOffset + strlen($tmp1[1][0]),$tmp2[1][1] - ($startOffset + strlen($tmp1[1][0])));
					}
				}
				break;
			default:
				break;
		}
		return array($result["tag"],$result["line"],$result["innerHTML"],$result["outerHTML"],$result["value"],$result["suffix"],$result["skipendtag"]);
	}
	/**
	 * 属性の設定
	 * タグ中のsoy:id以外の属性を格納する
	 *
	 * @param $line
	 */
	function parseAttributes(string $line){
		$regex ='/([a-zA-Z_:][a-zA-Z0-9_:.\-]*)\s*=\s*"([^"]*)"/';
		$tmp = array();
		if(preg_match_all($regex,$line,$tmp)){
			$keys = $tmp[1];
			$values = $tmp[2];
			foreach($keys as $i => $key){
				$key = strtolower($key);
				$value = html_entity_decode($values[$i], ENT_QUOTES, SOY2HTML::ENCODING);
				if(preg_match('/'.$this->_soy2_prefix.':/',$key)){
					$this->_soy2_attribute[$key] = $value;
					$this->setPermanentAttribute($key,$value);
					continue;
				}
				if($key == "style"){
					$this->_attribute[$key] = new SOY2HTMLStyle($value);
					continue;
				}
				$this->_attribute[$key] = $value;
			}
		}
	}
	/**
	 * soy:idを置換した形のcontentを返す
	 *
	 * @param $tag SOY2HTMLオブジェクト
	 * @param $content HTMLテンプレートソース
	 *
	 * @return 置換された形のcontent
	 */
	function getContent(SOY2HTML $tag, string $content){
		$in = $tag->_soy2_outerHTML;
		$tag->parseMessageProperty();
		$out = "";
		switch ($tag->getComponentType()) {
			case SOY2HTML::SKIP_BODY:
				$out = $tag->getStartTag();
				break;
			case SOY2HTML::HTML_BODY:
			case SOY2HTML::SOY_BODY:
				$innerHTML = $tag->_soy2_innerHTML;
				if(strlen($innerHTML)){
					$tag->setSkipEndTag(false);
				}
				$out = $tag->getStartTag().$innerHTML.$tag->getEndTag();
				break;
		}
		list($start,$end) = $tag->getVisbleScript();
		$in = str_replace($in,$start.$out.$end,$content);
		$tmpTag = "[a-zA-Z_:][a-zA-Z0-9_:.\-]*|!--";//XML対応で_:なども追加
		$tag->tag = $tmpTag;
		while(true){
			list($tagName,$line,$innerHTML,$outerHTML,$value,$suffix,$skipendtag) = $tag->parse("id",$tag->_soy2_id.'\*',$in);
			if(strlen($tagName)<1){
				return $in;
			}
			$tag->_attribute = array();
			$tag->_soy2_attribute = array();
			$tag->setTag($tagName);
			$tag->parseAttributes($line);
			$tag->setInnerHTML($innerHTML);
			$tag->setOuterHTML($outerHTML);
			$tag->setSkipEndTag($skipendtag);
			$tag->execute();
			$this->set($tag->getId(),$tag);
			$in = $this->getContent($tag,$in);
			$tag->setTag($tmpTag);
		}
	}
	/**
	 * 開始タグ(<p>とか<a href="・・・">とか)を取得する
	 *
	 * @return 開始タグ
	 */
	function getStartTag(){
		if($this->tag == "!--")return '';
		$attributes = array();
		foreach($this->_attribute as $key => $value){
			if(is_object($value)){
				$value = $value->__toString();
			}
			if(!preg_match("/$key=[\"']/i",$value)){
				$value = ' '.$key."=\"".htmlspecialchars((string)$value, ENT_QUOTES, SOY2HTML::ENCODING)."\"";
			}
			$attributes[] = $value;
		}
		$attribute = implode("",$attributes);
		$out = '<'.$this->tag;
		$out .= $attribute;
		if(SOY2HTMLConfig::getOption("output_html")){
		}else{
			if($this->getComponentType() == SOY2HTML::SKIP_BODY OR $this->getIsSkipEndTag()){
				$out .= ' /';
			}
		}
		$out .= '>';
		return $out;
	}
	/**
	 * 終了タグ(</p>とか</a>とか)を取得する
	 *
	 * @return 終了タグ
	 */
	function getEndTag(){
		if($this->getIsSkipEndTag())return '';
		if($this->tag == "!--")return '';
		return '</'.$this->tag.'>';
	}
	/**
	 * 表示非表示を書き換えるタグを取得
	 *
	 * @return array(開始タグ,終了タグ)
	 */
	function getVisbleScript(){
		return array(
			'<?php if(!isset($'.$this->getPageParam().'["'.$this->getId().'_visible"]) || $'.$this->getPageParam().'["'.$this->getId().'_visible"]){ ?>',
			'<?php } ?>'."\n"
		);
	}
	/**
	 * 属性を取得する
	 *
	 * @param $key 属性名
	 *
	 * @return 属性の値
	 */
	function getAttribute(string $key){
		$key = strtolower($key);
		return (isset($this->_attribute[$key]) && $this->_attribute[$key] !== true) ? $this->_attribute[$key] :
				 (isset($this->_soy2_attribute[$key]) ? $this->_soy2_attribute[$key] : null);
	}
	function getAttributes(){
		return $this->_soy2_attribute;
	}
	/**
	 * 属性を設定する
	 *
	 * @param $key 属性名
	 * @param $value 属性の値
	 * @param $flag 属性が常に存在するかどうか（disabled, readonlyなどはfalse）
	 *
	 */
	function setAttribute(string $key, string $value="", bool $flag=true){
		$key = strtolower($key);
		$this->_attribute[$key] = $flag;
		$this->_soy2_attribute[$key] = $value;
	}
	/**
	 * 属性値を保存する
	 */
	function setPermanentAttribute(string $key,string $value){
		if(!$this->getIsModified())return;
		$this->_soy2_permanent_attributes[$key] = $value;
	}
	/**
	 * 保存した属性値を取得する
	 */
	function getPermanentAttribute(string $key=""){
		if(!strlen($key)) return $this->_soy2_permanent_attributes;
		return (isset($this->_soy2_permanent_attributes[$key])) ? $this->_soy2_permanent_attributes[$key] : null;
	}
	/**
	 * 属性を消去する
	 */
	function clearAttribute(string $key){
		$key = strtolower($key);
		$this->_attribute[$key] = null;
		$this->_soy2_attribute[$key] = null;
		unset($this->_attribute[$key]);
		unset($this->_soy2_attribute[$key]);
	}
	/**
	 * スタイルオブジェクトを取得
	 */
	function &getStyle(){
		if(!isset($this->_soy2_attribute['style'])){
			$this->_soy2_attribute['style'] = new SOY2HTMLStyle();
		}
		return $this->_soy2_attribute['style'];
	}
	function setStyle($style){
		if(!$style instanceof SOY2HTMLStyle){
			$style = new SOY2HTMLStyle($style);
		}
		$this->setAttribute("style",$style);
	}
	function addMessageProperty($key,$message){
		$this->_message_properties[$key] = $message;
	}
	/**
	 * MessagePropertyを置き換える
	 */
	function parseMessageProperty(){
		if($this->getIsModified()){
			foreach($this->_message_properties as $key => $message){
				$tmpKey = "@@".$key.";";
				$this->_soy2_innerHTML = str_replace($tmpKey,$message,$this->_soy2_innerHTML);
			}
		}
	}
	/**
	 * 永続化する場合マージするかどうか
	 */
	function isMerge(){
		return false;
	}
	/**
	 * 永続化処理
	 *
	 * @param $id ページのID
	 * @param $obj タグ
	 */
	function set($id,SOY2HTML &$obj,&$page = null){
		if(is_null($page)){
			$page = &WebPage::getPage($this->getParentId());
		}
		$value = $obj->getObject();
		if(isset($page[$id]) && is_array($value) && $obj->isMerge()){
			$page[$id] = array_merge($page[$id],$value);
		}else{
			$page[$id] = $value;
		}
		$attribute = $obj->_soy2_attribute;
		foreach($attribute as $key => $value){
			if(!isset($obj->_attribute[$key]))continue;
			if(is_object($value))$value = $value->__toString();
			$value = (string)$value;
			if(strlen($value)){
				$page[$obj->getId()."_attribute"][$key] = htmlspecialchars($value,ENT_QUOTES,SOY2HTML::ENCODING);
			}else{
				$page[$obj->getId()."_attribute"][$key] = "";
			}
			/*
			 * _soy2_attributeの値で_attributeの値を上書きする
			 */
			if($obj->_attribute[$key] === false){
				$obj->_attribute[$key] = '<?php if($'.$obj->getPageParam().'["'.$obj->getId().'_attribute"]["'.$key.'"]){ ?>' .
				                         ' '.$key.'="<?php echo $'.$obj->getPageParam().'["'.$obj->getId().'_attribute"]["'.$key.'"]; ?>"' .
				                         '<?php } ?>';
			}else{
				$obj->_attribute[$key] = '<?php if(strlen($'.$obj->getPageParam().'["'.$obj->getId().'_attribute"]["'.$key.'"])){ ?>' .
				                         ' '.$key.'="<?php echo $'.$obj->getPageParam().'["'.$obj->getId().'_attribute"]["'.$key.'"]; ?>"' .
				                         '<?php } ?>';
			}
		}
		$page[$obj->getId()."_visible"] = $obj->getVisible();
	}
	/**
	 * 閉じなくても良いHTMLのリスト
	 * アルファベット順で。
	 */
	public static function getEmptyTagList(){
		return array(
			"area",
			"base",
			"basefont",
			"bgsound",
			"br",
			"embed",
			"hr",
			"img",
			"input",
			"link",
			"meta",
			"param"
		);
	}
	/**
	 * HTMLのタグを除去して実体参照をテキストに戻す
	 */
	public static function ToText(string $html, string $encoding=SOY2HTML::ENCODING){
		/*
		 * html_entity_decodeは文字コードの指定が重要
		 * http://jp2.php.net/manual/ja/function.html-entity-decode.php#function.html-entity-decode.notes
		 */
		//preタグ内にある&lt;と&gt;は予め除いておく
		$html = str_replace(array("&lt;", "&gt;"), "", $html);
		return html_entity_decode(strip_tags($html), ENT_QUOTES, $encoding);
	}
}
/**
 * SOY2HTMLConfig
 * SOY2HTMLに関わる設定を行うSingletonクラス
 *
 * @package SOY2.SOY2HTML
 * @author Miyazawa
 */
class SOY2HTMLConfig{
	private function __construct(){}
	private $cacheDir = "cache/";
	private $pageDir = "pages/";
	private $templateDir = null;
	private $lang = "";	//言語。ディフォルトは空
	private $layoutDir = "layout/";
	/**
	 * cache_prefix … キャッシュファイルの先頭に付加する文字列
	 * output_html … true/false. HTML形式で出力する。デフォルトはXHTML形式（空要素のタグが/>）。
	 */
	private $options = array();
	private static function &getInstance(){
		static $_static;
		if(!$_static){
			$_static = new SOY2HTMLConfig();
		}
		return $_static;
	}
	public static function CacheDir(string $dir=""){
		$config = self::getInstance();
		if(strlen($dir)){
			if(substr($dir,strlen($dir)-1) != '/'){
				throw new SOY2HTMLException("[SOY2HTML]CacheDir must end by '/'.");
			}
			$config->cacheDir = str_replace("\\", "/", $dir);
		}
		return $config->cacheDir;
	}
	public static function PageDir(string $dir=""){
		$config = self::getInstance();
		if(strlen($dir)){
			if(substr($dir,strlen($dir)-1) != '/'){
				throw new SOY2HTMLException("[SOY2HTML]PageDir must end by '/'.");
			}
			$config->pageDir = str_replace("\\", "/", $dir);
		}
		return $config->pageDir;
	}
	public static function TemplateDir(string $dir=""){
		$config = self::getInstance();
		if(strlen($dir)){
			if(substr($dir,strlen($dir)-1) != '/'){
				throw new SOY2HTMLException("[SOY2HTML]TemplateDir must end by '/'.");
			}
			$config->templateDir = str_replace("\\", "/", $dir);
		}
		return $config->templateDir;
	}
	public static function LayoutDir(string $dir=""){
		$config = self::getInstance();
		if(strlen($dir)){
			if(substr($dir,strlen($dir)-1) != '/'){
				throw new SOY2HTMLException("[SOY2HTML]Layout Dir must end with '/'.");
			}
			$config->layoutDir = str_replace("\\", "/", $dir);
		}
		return $config->layoutDir;
	}
	/**
	 * SOY2HTMLの言語を設定する
	 */
	public static function Language(string $lang=""){
		$config = self::getInstance();
		if(strlen($lang)){
			$config->lang = $lang;
		}
		return $config->lang;
	}
	/**
	 * オプション設定
	 */
	public static function setOption(string $key, $value=null){
		$config = self::getInstance();
		if($value)$config->options[$key] = $value;
		return (isset($config->options[$key]) ) ? $config->options[$key] : null;
	}
	/**
	 * オプション取得
	 */
	public static function getOption(string $key){
		return self::setOption($key);
	}
}
/**
 * SOY2HTMLクラスから派生するオブジェクトを生成するFactoryクラス
 *
 * @package SOY2.SOY2HTML
 * @author Miyazawa
 */
class SOY2HTMLFactory extends SOY2HTMLBase{
	/**
	 * 指定したクラス名と属性値から対応するクラスのインスタンスを生成し、返す
	 *
	 * @param $className クラスの名前
	 * @param $attributes 属性の配列
	 *
	 * @return クラスのインスタンス
	 */
	public static function &createInstance(string $className, array $attributes=array()){
		if(!class_exists($className)){
			try{
				self::importWebPage($className);
			}catch(SOY2HTMLException $e){
				throw new SOY2HTMLException("[SOY2HTML]Class ".$className. " is undefined.");
			}
		}
		$tmp = array();
		preg_match('/\.([a-zA-Z0-9_]+$)/',$className,$tmp);
		if(count($tmp)){
			$className = $tmp[1];
		}
		if(isset($attributes['arguments'])){
			$class = new $className($attributes['arguments']);
			$attributes['arguments'] = null;
			unset($attributes['arguments']);
		}else{
			$class = new $className();
		}
		if(is_array($attributes)){
			foreach($attributes as $key => $value){
				if($key == "id"){
					$class->setAttribute($key,(string)$value);
					continue;
				}
				if(strpos($key,"attr:") !== false){
					$key = substr($key,5);
					$class->setAttribute($key,(string)$value);
					continue;
				}
				if(method_exists($class,"set".ucwords($key))  || $class->functionExists("set".ucwords($key))){
					$func = "set".ucwords($key);
					$class->$func($value);
					continue;
				}
				if(stristr($key,':function')){
					$key = trim($key);
					$funcName = str_replace(strstr($key,":function"),"",$key);
					$argsRegex = '/:function\s*\((.*)\)$/';
					$tmp = array();
					if(preg_match($argsRegex,$key,$tmp)){
						$args = explode(",",$tmp[1]);
					}else{
						continue;
					}
					$code = $value;
					$class->addFunction($funcName,$args,$code);
					continue;
				}
				$class->setAttribute($key, (string)$value);
			}
		}

		return $class;
	}
	/**
	 * クラス名から対応するWebPageオブジェクトのファイルをインポートする１
	 *
	 * @param $className クラス名
	 * @exception SOY2HTMLException ファイルが存在しないとき
	 */
	public static function importWebPage(string $className){
		if(self::pageExists($className) == false){
			throw new SOY2HTMLException();
		}
		$pageDir = SOY2HTMLConfig::PageDir();
		$path = str_replace(".","/",$className);
		$extension = ".class.php";
		include_once($pageDir.$path.$extension);
	}
	public static function pageExists(string $className){
		$pageDir = SOY2HTMLConfig::PageDir();
		$path = str_replace(".","/",$className);
		$extension = ".class.php";
		if(defined("SOY2HTML_AUTO_GENERATE") && SOY2HTML_AUTO_GENERATE == true && !file_exists($pageDir.$path.$extension) && file_exists($pageDir.$path.".html")){
			self::generateWebPage($className,$pageDir.$path);
		}
		if(!file_exists($pageDir.$path.$extension)){
			return false;
		}
		$tmp = array();
		preg_match('/\.([a-zA-Z0-9_]+$)/',$className,$tmp);
		if(count($tmp)){
			$className = $tmp[1];
		}
		return $className;
	}
	private static function generateWebPage(string $className, string $path){
		$templatePath = $path . ".html";
		$fullPath = $path . ".class.php";
		$dirpath = dirname($fullPath);
		while(file_exists($dirpath) == false){
			if(!mkdir($dirpath))return;
			$dirpath = dirname($dirpath);
		}
		$docComment = array();
		$docComment[] = "/**";
		$docComment[] = " * @class $className";
		$docComment[] = " * @date ".date("c");
		$docComment[] = " * @author SOY2HTMLFactory";
		$docComment[] = " */ ";
		$tmp = array();
		preg_match('/\.([a-zA-Z0-9_]+$)/',$className,$tmp);
		if(count($tmp)){
			$tmpClassName = $tmp[1];
		}else{
			$tmpClassName = $className;
		}
		$class = array();
		$class[] = "class ".$tmpClassName." extends WebPage{";
		$class[] = "	";
		$class[] = '	function '.$tmpClassName.'(){';
		$class[] = "		parent::__construct();";
		$soyIds = array();
		$tmpSoyIds = array();
		$templates = file($templatePath);
		$regex = '/<([^>^\s]*)[^>]*(\/)?soy:id=\"([a-zA-Z][a-zA-Z0-9_]+)\"\s?[^>]*>/i';
		foreach($templates as $str){
			if(!preg_match($regex,$str,$tmp))continue;
			$tag = $tmp[1];
			$isEnded = (boolean)(strlen($tmp[2]) OR $tag[0] == "/");
			$soyId = $tmp[3];
			if($isEnded && isset($tmpSoyIds[$soyId])){
				$childSoyIds = array();
				$tmpKeys = array_keys($tmpSoyIds);
				$tmpKeys = array_reverse($tmpKeys);
				foreach($tmpKeys as $value){
					if($value == $soyId){
						$tmpSoyIds[$soyId]["child"] = array_reverse($childSoyIds);
						$soyIds += array_reverse($tmpSoyIds);
						$tmpSoyIds = array();
						break;
					}
					$childSoyIds[$value] = $tmpSoyIds[$value];
					unset($tmpSoyIds[$value]);
				}
				continue;
			}
			$tmpSoyIds[$soyId] = array(
				"tag" => $tag,
				"child" => array()
			);
		}
		list($result,$classes) = self::generateCreateAdd($soyIds);
		$class[] = implode("\n\t\t",$result);
		$class[] = "	}";
		$class[] = "}";
		$class[] = "";
		$class[] = implode("\n",$classes);
		file_put_contents($fullPath,"<?php \n".implode("\n",$docComment) ."\n". implode("\n",$class)."\n?>");
	}
	private static function generateCreateAdd(array $soyIds, string $className="HTMLLabel"){
		$keys = array_keys($soyIds);
		$script = array();
		$classes = array();
		foreach($keys as $key){
			$className = "HTMLLabel";
			$createKey = array("text");
			$script[] = '';
			if($soyIds[$key]["tag"] == "input"){
				$className = "HTMLInput";
				$createKey = array(
					"name" => $key,
					"value" => ""
				);
			}
			if($soyIds[$key]["tag"] == "select"){
				$className = "HTMLSelect";
				$createKey = array(
					"name" => $key,
					"options" => array(),
					"selected" => ""
				);
			}
			if($soyIds[$key]["tag"] == "textarea"){
				$className = "HTMLTextArea";
				$createKey = array(
					"name" => $key,
					"value" => ""
				);
			}
			if(preg_match('/_link$/',$key)){
				$className = "HTMLLink";
				$createKey = array("link");
			}
			if(preg_match('/_form$/',$key)){
				$className = "HTMLForm";
				list($tmpScript,$tmpClass) = self::generateCreateAdd($soyIds[$key]["child"]);
				$script[] = '$this->createAdd("'.$key.'","'.$className.'");';
				$script = array_merge($script,$tmpScript);
				$classes = array_merge($classes,$tmpClass);
				continue;
			}
			if(preg_match('/_list$/',$key)){
				$className = str_replace("_list","List",ucwords($key));
				list($tmpScript,$tmpClass) = self::generateCreateAdd($soyIds[$key]["child"]);
				$script[] = '$this->createAdd("'.$key.'","'.$className.'",array(';
				$script[] = "\t".'"list" => array()';
				$script[] = '));';
				$classes[] = '';
				$classes[] = '/**';
				$classes[] = ' * @class '.$className;
				$classes[] = ' * @generated by SOY2HTML';
				$classes[] = ' */';
				$classes[] = 'class '.$className.' extends HTMLList{';
				$classes[] = "\t".'protected function populateItem($entity){';
				$classes[] = "\t\t".implode("\n\t\t",$tmpScript);
				$classes[] = "\t".'}';
				$classes[] = '}';
				$classes = array_merge($classes,$tmpClass);
				continue;
			}
			list($tmpScript,$tmpClass) = self::generateCreateAdd($soyIds[$key]["child"]);
			$script[] = '$this->createAdd("'.$key.'","'.$className.'",array(';
			foreach($createKey as $tmpCreateKey => $defaultValue){
				if(is_numeric($tmpCreateKey)){
					$tmpCreateKey = $defaultValue;
					$defaultValue = "";
				}
				if(is_string($defaultValue)){
					$defaultValue = '"'.$defaultValue.'"';
				}
				if(is_array($defaultValue)){
					$defaultValue = 'array()';
				}
				if(!strlen($defaultValue))$defaultValue = '""';
				$script[] = "\t".'"'.$tmpCreateKey.'" => '.$defaultValue.',';
			}
			$script[] = '));';
			$script = array_merge($script,$tmpScript);
			$classes = array_merge($classes,$tmpClass);
		}
		return array($script,$classes);
	}
}
/**
 * SOY2HTMLが出力するSOY2HTMLException
 */
class SOY2HTMLException extends Exception{}
