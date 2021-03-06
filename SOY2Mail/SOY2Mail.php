<?php

class SOY2Mail {
	/**
	 *
	 */
    public static function create($type, $options = array()){
		$mail = null;
    	switch($type){
    		case "imap":
    			$mail = new SOY2Mail_IMAPLogic($options);
    			break;
    		case "pop":
    			$mail = new SOY2Mail_POPLogic($options);
    			break;
    		case "smtp":
    			$mail = new SOY2Mail_SMTPLogic($options);
    			break;
    		case "sendmail":
    			$mail = new SOY2Mail_SendMailLogic($options);
    			break;
    		default:
    			throw new SOY2MailException("[SOY2Mail]Invalid Logic type " . $type);
    			break;
    	}
    	return $mail;
    }
    private $subject;
    private $encodedSubject;
    private $text;
    private $encodedText;
    private $attachments = array();
    private $headers = array();
    private $from = array();
    private $recipients = array();
    private $bccRecipients = array();
    private $encoding = "UTF-8";
    private $subjectEncoding = "ISO-2022-JP";
    private $rawData = "";
    function getSubject() {
    	return $this->subject;
    }
    function setSubject($subject) {
    	$this->subject = $subject;
    	$this->encodedSubject = "";
    }
    function getEncodedSubject() {
    	if(strlen($this->encodedSubject)<1){
    		$this->encodedSubject = mb_encode_mimeheader($this->subject,
				$this->getSubjectEncodingForConvert(),"B","\r\n",strlen("Subject: "));
    	}
    	return $this->encodedSubject;
    }
    function setEncodedSubject($encodedSubject) {
    	$this->encodedSubject = $encodedSubject;
    }
    function getText() {
    	return $this->text;
    }
    function setText($text, $encoding = null) {
    	$this->text = $text;
    	if(!$this->encodedText){
    		if(!$encoding)$encoding = $this->getEncodingForConvert();
    		$this->encodedText = mb_convert_encoding($text, $encoding);
    	}
    }
    function getEncodedText() {
    	return $this->encodedText;
    }
    function setEncodedText($encodedText) {
    	$this->encodedText = $encodedText;
    }
    function getAttachments() {
    	return $this->attachments;
    }
    function setAttachments($attachments) {
    	$this->attachments = $attachments;
    }
    function getHeaders() {
    	return $this->headers;
    }
    function setHeaders($headers) {
    	$this->headers = $headers;
    }
    function getFrom() {
    	return $this->from;
    }
    function setFrom($from, $label = null, $encoding = null) {
		if(!$encoding)$encoding = $this->getEncoding();
    	$this->from = new SOY2Mail_MailAddress($from, $label, $encoding);
    }
    function getRecipients() {
    	return $this->recipients;
    }
    function setRecipients($recipients) {
    	$this->recipients = $recipients;
    }
    function getEncodedRecipients() {
    	return $this->encodedRecipients;
    }
    function setEncodedRecipients($encodedRecipients) {
    	$this->encodedRecipients = $encodedRecipients;
    }
    /**
     * ????????????????????????
     * ???????????????????????????????????????????????????subjectEncoding
     */
	function getEncoding() {
		return $this->encoding;
	}
	function setEncoding($encoding) {
		$this->encoding = $encoding;
	}
	function getBccRecipients() {
    	return $this->bccRecipients;
    }
    function setBccRecipients($bccRecipients) {
    	$this->bccRecipients = $bccRecipients;
    }
    function getRawData(){
    	return $this->rawData;
    }
    function setRawData($rawData){
    	$this->rawData = $rawData;
    }
    /**
     * ???????????????????????????
     */
    function clearSubject(){
    	$this->subject = null;
    	$this->encodedSubject = null;
    }
    /**
     * ???????????????????????????
     */
    function clearText(){
    	$this->text = null;
    	$this->encodedText = null;
    }
    /**
     * ????????????????????????
     */
    function addRecipient($address, $label = null, $encoding = null){
    	if(!$encoding)$encoding = $this->getEncoding();
    	$recipient = new SOY2Mail_MailAddress($address, $label, $encoding);
    	$this->recipients[$address] = $recipient;
    	return $this;
    }
    /**
     * ????????????????????????
     */
    function removeRecipient($address){
    	$this->recipients[$address] = null;
    	unset($this->recipients[$address]);
    }
    /**
     * ?????????????????????????????????
     */
    function clearRecipients(){
    	$this->recipients = array();
    }
    /**
     * BCC????????????????????????
     */
    function addBccRecipient($address, $label = null, $encoding = null){
    	if(!$encoding)$encoding = $this->getEncoding();
    	$recipient = new SOY2Mail_MailAddress($address, $label, $encoding);
    	$this->bccRecipients[$address] = $recipient;
    	return $this;
    }
    /**
     * BCC????????????????????????
     */
    function removeBccRecipient($address){
    	$this->bccRecipients[$address] = null;
    	unset($this->bccRecipients[$address]);
    }
    /**
     * BCC?????????????????????????????????
     */
    function clearBccRecipients(){
    	$this->bccRecipients = array();
    }
    /**
     * header???????????????
     */
    function setHeader($key, $value){
    	if(strlen($value)>0){
	    	$this->headers[$key] = $value;
    	}else{
    		if(array_key_exists($key, $this->headers)){
    			unset($this->headers[$key]);
    		}
    	}
    	return $this;
    }
    /**
     * ???????????????????????????
     */
    function getHeader($key){
    	return (isset($this->headers[$key])) ? $this->headers[$key] : "";
    }
    /**
     * ?????????????????????????????????
     */
    function clearHeaders(){
    	$this->headers = array();
    }
    /**
     * ?????????????????????????????????
     */
    function addAttachment($filename, $type, $contents){
    	$this->attachments[$filename] = array(
    		"filename" => $filename,
    		"mime-type" => $type,
    		"contents" => $contents
    	);
    }
    /**
     * ?????????????????????????????????
     */
    function removeAttachment($filename){
    	$this->attachments[$filename] = null;
    	unset($this->attachments[$filename]);
    }
    /**
     * ??????????????????????????????????????????
     */
    function clearAttachments(){
    	$this->attachments = array();
    }
	/**
	 * ????????????????????????
	 */
    function getSubjectEncoding() {
    	return $this->subjectEncoding;
    }
    function setSubjectEncoding($subjectEncoding) {
    	$this->subjectEncoding = $subjectEncoding;
    }
    /**
     * ?????????????????????????????????????????????????????????
     */
    function getEncodingForConvert(){
    	return self::getPracticalEncoding($this->getEncoding());
    }
    /**
     * ?????????????????????????????????????????????????????????
     */
    function getSubjectEncodingForConvert(){
    	return self::getPracticalEncoding($this->getSubjectEncoding());
    }
    /**
     * ????????????????????????????????????????????????????????????
     * ????????????????????????????????????????????????????????????????????????????????????????????????????????????????????????
     */
    public static function getPracticalEncoding($encoding){
    	switch(strtoupper($encoding)){
    		case "ISO-2022-JP":
    			/*
    			 * ?????????????????????????????????????????????
    			 * ISO-2022-JP: ASCII, JIS X 0201 ??????????????????, JIS X 0208
    			 * JIS: ISO-2022-JP????????????JIS X 0201???????????????, JIS X 0212
    			 * ISO-2022-JP-MS: JIS????????????NEC???????????????NEC??????IBM????????????????????????
    			 *   http://legacy-encoding.sourceforge.jp/wiki/
    			 */
    			if(version_compare(PHP_VERSION,"5.2.1") >= 0){
	    			return "ISO-2022-JP-MS";
    			}else{
	    			return "JIS";
    			}
    		default:
    			return $encoding;
    	}
    }
}
class SOY2Mail_MailAddress{
	private $address;
	private $label;
	private $encoding;
	function __construct($address, $label = "", $encoding = ""){
		$this->address = $address;
		$this->label = $label;
		$this->encoding = $encoding;
	}
	function getAddress() {
		if(strpos($this->address, '"') === false && ( strpos($this->address, "..") !== false || strpos($this->address, ".@") !== false )){
			list($local, $domain) = explode("@", $this->address);
			$quoted = '"'.$local.'"@'.$domain;
			return $quoted;
		}else{
			return $this->address;
		}
	}
	function setAddress($address) {
		$this->address = $address;
	}
	function getLabel() {
		return $this->label;
	}
	function setLabel($label) {
		$this->label = $label;
	}
	function getEncoding() {
		return $this->encoding;
	}
	function setEncoding($encoding) {
		$this->encoding = $encoding;
	}
    /**
     * ????????????????????????????????????????????????
     */
    function getEncodingForConvert(){
    	return SOY2Mail::getPracticalEncoding($this->getEncoding());
    }
    /**
     * ??????????????????????????????????????????
     * ????????????????????????????????????
     */
	function getString(){
		if(strlen($this->address)<1)return '';
		if(strlen($this->label)<1)return '<' . $this->address . '>';
		return mb_encode_mimeheader($this->label, $this->getEncodingForConvert()).' <'.$this->address.'>';
	}
	function __toString(){
		return $this->getString();
	}
	/**
	 * ??????????????????????????????????????????
	 * @param string $email
	 * @param boolean true?????????????????????????????????????????????
	 * @return boolean
	 *
	 * $lazy: true
	 * @????????????1?????????????????????????????????.?????????????????????????????????OK
	 * $lazy: false
	 * ??????????????????RFC?????????
	 * ????????????????????????????????????????????????.????????????????????????????????????????????????NG??????????????????docomo?????????RFC???????????????????????????????????????
	 */
	protected static function _validation($email, $lazy = false){
		if($lazy){
			$validEmail = "^.+\@[^.]+(?:\\.[^.]+)+\$";
		}else{
			$ascii  = '[a-zA-Z0-9!#$%&\'*+\-\/=?^_`{|}~.]';//'[\x01-\x7F]';
			$domain = '(?:[-a-z0-9]+\.)+[a-z]{2,10}';//'([-a-z0-9]+\.)*[a-z]+';
			$d3     = '\d{1,3}';
			$ip     = $d3.'\.'.$d3.'\.'.$d3.'\.'.$d3;
			$validEmail = "^$ascii+\@(?:$domain|\\[$ip\\])\$";
		}
		if(! preg_match('/'.$validEmail.'/i', $email) ) {
			return false;
		}
		return true;
	}
	/**
	 * ??????????????????????????????????????????????????????
	 * @param string $email
	 * @return boolean
	 */
	public static function simpleValidation($email){
		return self::_validation($email, true);
	}
	/**
	 * ????????????????????????????????????????????????????????????
	 * @param string $email
	 * @return boolean
	 */
	public static function validation($email){
		return self::_validation($email, false);
	}
}
interface SOY2Mail_SenderInterface{
	function open();
	function send();
	function close();
}
interface SOY2Mail_ReceiverInterface{
	function open();
	function receive();
	function close();
}
class SOY2MailException extends Exception{}
