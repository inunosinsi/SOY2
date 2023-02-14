<?php

/**
 * @package SOY2.SOY2HTML
 */
class HTMLForm extends SOYBodyComponentBase{
    var $tag = "form";
    var $action;
    var $_method = "post";
	private $disabled;
    function setTag($tag){
    	throw new SOY2HTMLException("[HTMLForm]タグの書き換えは不可です。");
    }
    function setMethod($method){
    	$this->_method = $method;
    }
    function setAction($action){
    	$this->action = $action;
    }
    function setTarget($target){
    	$this->setAttribute("target",$target);
    }
    function getStartTag(){
    	if(strtolower($this->_method) == "post"){
    		$token = '<input type="hidden" name="soy2_token" value="<?php echo soy2_get_token(); ?>" />';
    		return parent::getStartTag() . $token;
    	}
    	return parent::getStartTag();
    }
    function execute(){
		SOYBodyComponentBase::execute();
		if(is_string($this->action)){
			$this->setAttribute("action", $this->action);
		}else if(isset($_SERVER["REQUEST_URI"])){
			$this->setAttribute("action", $_SERVER["REQUEST_URI"]);
		}
		$this->setAttribute('method', (string)$this->_method);
		$disabled = ($this->disabled) ? "disabled" : "";
		$this->setAttribute("disabled",$disabled, false);
    }
    function setOnSubmit($value){
    	if(!preg_match("/^javascript:/i",$value)){
    		$value = "javascript:".$value;
    	}
    	$this->setAttribute("onsubmit", (string)$value);
    }
	function getDisabled() {
		return $this->disabled;
	}
	function setDisabled($disabled) {
		$this->disabled = $disabled;
	}
}
/**
 * @package SOY2.SOY2HTML
 */
class HTMLUploadForm extends HTMLForm{
	function execute(){
		parent::execute();
		$this->setAttribute("enctype","multipart/form-data");
	}
}
/**
 * @package SOY2.SOY2HTML
 */
abstract class HTMLFormElement extends SOY2HTML{
	var $name;
	private $disabled;
	private $readonly;
	private $required;
	private $placeholder;
	private $pattern;
	function execute(){
		parent::execute();
		$disabled = (is_string($this->disabled) || (is_bool($this->disabled)) && $this->disabled) ? "disabled" : "";
		$this->setAttribute("disabled", $disabled, false);
		$readonly = (is_string($this->readonly) || (is_bool($this->readonly) && $this->readonly)) ? "readonly" : "";
		$this->setAttribute("readonly", $readonly, false);
		$required = (is_string($this->required) || (is_bool($this->required) && $this->required)) ? "required" : "";
		$this->setAttribute("required", $required, false);
		$placeholder = (is_string($this->placeholder)) ? trim($this->placeholder) : "";
		$this->setAttribute("placeholder", $placeholder, false);
		$pattern = (is_string($this->pattern)) ? trim($this->pattern) : "";
		$this->setAttribute("pattern", $pattern, false);
	}
	function setName($value){
		$this->name = $value;
		$this->setAttribute("name", $value);
	}
	function getDisabled() {
		return $this->disabled;
	}
	function setDisabled($disabled) {
		$this->disabled = $disabled;
	}
	function getReadonly() {
		return $this->readonly;
	}
	function setReadonly($readonly) {
		$this->readonly = $readonly;
	}
	function getRequired(){
		return $this->required = $required;
	}
	function setRequired($required){
		$this->required = $required;
	}
	function getPlaceholder(){
		return $this->placeholder;
	}
	function setPlaceholder($placeholder){
		$this->placeholder = $placeholder;
	}
	function getPattern(){
		return $this->pattern;
	}
	function setPattern($pattern){
		$this->pattern = $pattern;
	}
}
/**
 * @package SOY2.SOY2HTML
 */
class HTMLInput extends HTMLFormElement{
	const SOY_TYPE = SOY2HTML::SKIP_BODY;
	var $tag = "input";
	var $value;
	var $type;
	function setValue($value){
		$this->value = $value;
		$this->setAttribute("value", (string)$this->value);
	}
	function execute(){
		parent::execute();
	}
	function getObject(){
		return $this->value;
	}
	function setType($value){
		$this->type = $value;
		$this->setAttribute("type",$this->type);
	}
	function getType(){
		return $this->type;
	}
}
/**
 * @package SOY2.SOY2HTML
 */
class HTMLHidden extends HTMLInput{
	function execute(){
		parent::execute();
		$this->setAttribute("type","hidden");
	}
}
/**
 * @package SOY2.SOY2HTML
 */
class HTMLTextArea extends HTMLFormElement{
	var $tag = "textarea";
	const SOY_TYPE = SOY2HTML::HTML_BODY;
	var $text;
	function setText($value){
		$this->text = $value;
	}
	function setValue($value){
		$this->text = $value;
	}
	function getText(){
		return (string) $this->text;
	}
	function getObject(){
		return "\n".htmlspecialchars($this->getText(),ENT_QUOTES,SOY2HTML::ENCODING);
	}
}
/**
 * HTMLSelect
 * @package SOY2.SOY2HTML
 * 使い方
 * <select soy:id="test_select"></select>
 *
 * $this->createAdd("test_select","HTMLSelect",array(
 * 		"selected" => $selectedvalue,
 * 		"options" => array(
 * 			"りんご","みかん","マンゴー"
 * 		),
 * 		"each" => array(
 * 			"onclick"=>"alert(this.value);"
 * 		),
 * 		"indexOrder" => $boolean,
 * 		"name" => $name
 * ));
 *
 * indexOrderがtrueの場合、またはoptionsに指定した配列が連想配列の場合は
 * <option value="0">りんご</option>
 * <option value="1">みかん</option>
 * <option value="2">マンゴー</option>
 * または
 * <option value="apple">りんご</option>
 * <option value="mandarin">みかん</option>
 * <option value="mango">マンゴー</option>
 * に展開されます。
 *
 * optionsに指定した配列が連想配列で無い場合（かつindexOrderがtrueでない場合）は
 * <option>りんご</option>
 * <option>みかん</option>
 * <option>マンゴー</option>
 * です。
 *
 * optionsを多重配列にすることで<optgroup>を指定できます。
 *
 * selectedを複数指定するときは配列にします。
 */
class HTMLSelect extends HTMLFormElement {
	var $tag = "select";
	const SOY_TYPE = SOY2HTML::HTML_BODY;
	var $options;
	var $selected;//複数指定するときは配列
	private $multiple = false;
	var $indexOrder = false;
	var $property;
	var $each = "";
	function setOptions($options){
		$this->options = $options;
	}
	function setSelected($selected){
		$this->selected = $selected;
	}
	function getMultiple() {
		return $this->multiple;
	}
	function setMultiple($multiple) {
		$this->multiple = $multiple;
	}
	function setIndexOrder(){
		$this->indexOrder = true;
	}
	function setProperty($name){
		$this->property = $name;
	}
	function setEach($each){
		if(is_array($each) && count($each)){
			$attr = array();
			foreach($each as $key => $value){
				$attr[] = htmlspecialchars((string)$key, ENT_QUOTES,SOY2HTML::ENCODING).'="'.htmlspecialchars((string)$value, ENT_QUOTES,SOY2HTML::ENCODING).'"';
			}
			$this->each = implode(" ",$attr);
		}
	}
	function execute(){
		$innerHTML  = $this->getInnerHTML();
		parent::execute();
		$this->setInnerHTML($innerHTML.$this->getInnerHTML());
		$multiple = ($this->multiple) ? "multiple" : "";
		$this->setAttribute("multiple",$multiple, false);
	}
	function getObject(){
		$first = (is_array($this->options) && count($this->options)) ? array_slice($this->options, 0, 1) : array();
		if(is_array(array_shift($first))){
			$twoDimensional = true;
			$isHash = false;
		}else{
			$twoDimensional = false;
			$isHash = (is_array($this->options) && array_keys($this->options) === range(0,count($this->options)-1)) ? false : true;
		}
		if($this->indexOrder){
			$isHash = true;
		}
		$buff = "";
		if($twoDimensional && is_array($this->options) && count($this->options)){
			foreach($this->options as $key => $value){
				if(is_array($value)){
					$key = (string)$key;
					$buff .= '<optgroup label="'.htmlspecialchars((string)$key, ENT_QUOTES,SOY2HTML::ENCODING).'">';
					$buff .= $this->buildOptions($value, $isHash);
					$buff .= '</optgroup>';
				}else{
					$buff .= $this->buildOption($key, $value, $isHash);
				}
			}
		}else{
			$buff .= $this->buildOptions($this->options, $isHash);
		}
		return $buff;
	}
	function buildOptions($options, $isHash){
		$buff = "";
		if(is_array($options) && count($options)){
			foreach($options as $key => $value){
				$buff .= $this->buildOption($key, $value, $isHash);
			}
		}
		return $buff;
	}
	function buildOption($key, $value, $isHash){
		$buff = "";
		$selected = '';
		$key = (string)$key;
		if(is_object($value) && $this->property){
			$propName = $this->property;
			$funcName = "get" . ucwords($propName);
			if(method_exists($value,$funcName)){
				$value = $value->$funcName();
			}else{
				$value = $value->$propName;
			}
		}
		if($isHash || !is_numeric($key)){
			$selected = ($this->selected($key)) ? 'selected="selected"' : '';
		}else{
			$selected = ($this->selected($value)) ? 'selected="selected"' : '';
		}
		$attributes = "";
		if(strlen($selected))   $attributes .= " ".$selected;
		if(strlen($this->each)) $attributes .= " ".$this->each;
		if($isHash || !is_numeric($key)){
			$attributes .= ' value="'.htmlspecialchars((string)$key,ENT_QUOTES,SOY2HTML::ENCODING).'"';
		}
		$buff .= "<option".$attributes.">".htmlspecialchars((string)$value,ENT_QUOTES,SOY2HTML::ENCODING)."</option>";
		return $buff;
	}
	/**
	 * 値がselectedであるかどうか
	 */
	function selected($value){
		if(is_array($this->selected)){
			return in_array($value,$this->selected);
		}else{
			return ($value == $this->selected);
		}
	}
	function setValue($value){
		$this->setSelected($value);
	}
}
/**
 * HTMLCheckBox
 *
 * 使い方１
 * <input type="checkbox" soy:id="soyid" />
 * $this->createAdd("soyid", "HTMLCheckbox", array(
 *  "label" => "LABEL",//<label for="thisid">LABEL</label>が自動的に生成される
 * 	"selected" => true, //or false //checked="checked"生成
 *  "isBoolean" => true, //<input type="hidden" value="0" />生成
 * ));
 *
 * 使い方２
 * <input type="checkbox" soy:id="soyid" id="checkboxid" /><label for="checkboxid">MY LABEL</label>
 * $this->createAdd("soyid", "HTMLCheckbox", array(
 * 	"elementId" => "checkboxid",
 * 	"selected" => true, //or false
 *  "isBoolean" => true,
 * ));
 */
class HTMLCheckBox extends HTMLInput {
	var $label;
	var $elementId;
	var $selected;
	var $type = "checkbox";
	var $isBoolean;
	function setLabel($label){
		$this->label = $label;
	}
	function setSelected($selected){
		$this->selected = $selected;
	}
	function setElementId($elementId){
		$this->elementId = $elementId;
	}
	function getStartTag(){
		$zero = "";
		$label = '<?php if(strlen($'.$this->getPageParam().'["'.$this->getId().'"])>0){ ?><label for="<?php echo $'.$this->getPageParam().'["'.$this->getId().'_attribute"]["id"]; ?>">'.
			'<?php echo $'.$this->getPageParam().'["'.$this->getId().'"]; ?></label><?php } ?>';
		if($this->isBoolean()){
			$zero = '<input type="hidden" name="<?php echo $'.$this->getPageParam().'["'.$this->getId().'_attribute"]["name"]; ?>" value="0" />';
		}
		return $zero . parent::getStartTag() . $label;
	}
	function execute(){
		parent::execute();
		if(!is_string($this->elementId)) $this->elementId = "label_" . md5((string)$this->value.(string)$this->name.(string)rand(0,1));
		$this->setAttribute("id",$this->elementId);
		$checked = ($this->selected) ? "checked" : "";
		$this->setAttribute("checked",$checked, false);
	}
	function getLabel(){
		return (string) $this->label;
	}
	function getObject(){
		return htmlspecialchars($this->getLabel(),ENT_QUOTES,SOY2HTML::ENCODING);
	}
	function setIsBoolean($flag){
		$this->isBoolean = $flag;
	}
	function isBoolean(){
		return (boolean)$this->isBoolean;
	}
}
