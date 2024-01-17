<?php

class SOY2Mail_IMAPLogic extends SOY2Mail implements SOY2Mail_ReceiverInterface{
	private $con;
	private $host;
	private $port;
	private $flag;
	private $folder;
	private $user;
	private $pass;
	function __construct(array $options) {
		if(!function_exists("imap_open")){//extension_loaded("imap")
			throw new SOY2MailException("The extension 'imap' is necessary.");
		}
		if(!isset($options["imap.host"])){
			throw new SOY2MailException("[imap.host] is necessary.");
		}
		if(!isset($options["imap.port"])){
			throw new SOY2MailException("[imap.port] is necessary.");
		}
		if(!isset($options["imap.user"])){
			throw new SOY2MailException("[imap.user] is necessary.");
		}
		if(!isset($options["imap.pass"])){
			throw new SOY2MailException("[imap.pass] is necessary.");
		}
		$this->host = $options["imap.host"];
		$this->port = $options["imap.port"];
		if(isset($options["imap.flag"]))$this->flag = $options["imap.flag"];
		if(isset($options["imap.folder"]))$this->folder = $options["imap.folder"];
		$this->user = $options["imap.user"];
		$this->pass = $options["imap.pass"];
	}
	function __destruct(){
		if($this->con) $this->close();
	}
	function open(){
		$host = $this->host;
		$host .= ":" . $this->port;
		if($this->flag)$host .= "/" . $this->flag;
		$this->con = imap_open("{" . $host . "}" . $this->folder, $this->user, $this->pass);
		if($this->con === false){
			throw new SOY2MailException("imap_open(): login failed");
		}
	}
	function close(){
		imap_close($this->con);
		$this->con = null;
	}
	function receive(){
		if(!$this->con)$this->open();
		$unseen = imap_search($this->con, "UNSEEN");
		if($unseen == false){
			return false;
		}
		$mail = new SOY2Mail();
		$i = array_shift($unseen);
		$head = imap_headerinfo($this->con, $i);
		$title = mb_decode_mimeheader(@$head->subject);
		$rawHeader = imap_fetchheader($this->con, $i);
		$mail->setRawData($rawHeader.imap_body($this->con, $i));
		$Structure = imap_fetchstructure($this->con, $i);	//メール構造読み込み
		$mimeType = $this->getMimeType($Structure->type,$Structure->subtype);	//メールタイプ読み込み
		if(strpos($mimeType,"multipart") !== false && count($Structure->parts)>1){
			$numberOfParts = count($Structure->parts);	//添付ファイルの数数え
			for($j=1; $j<$numberOfParts; $j++){
				$part = $Structure->parts[$j];
				if($part->ifdparameters){
					$filename = $this->getParameterValue($part->dparameters,"filename");
				}
				if(!$filename && $part->ifparameters){
					$filename = $this->getParameterValue($part->parameters,"name");
				}
				if($filename){
					$attachmentName = $filename;	//添付ファイル名読み込み
					$attachmentName = mb_encode_mimeheader($attachmentName);//日本語名だったらエンコード
				}else{
					$attachmentName = "file-".$i."-".$j;
				}
				$attachmentFile = imap_fetchbody ($this->con,$i,$j+1);	//添付部分取り出し
				$attachmentFile = imap_base64 ($attachmentFile);		//デコード
				$mail->addAttachment($attachmentName, $this->getMimeType($part->type,$part->subtype), $attachmentFile);
			}
			$body = imap_fetchbody($this->con, $i, 1);
			if($encoding = $this->getParameterValue($Structure->parts[0]->parameters,"charset")){
				$mail->setEncoding($encoding);
			}else{
				$encoding = null;
			}
		}else{
			if($encoding = $this->getParameterValue($Structure->parameters,"charset")){
				$mail->setEncoding($encoding);
			}else{
				$encoding = null;
			}
			$body = imap_body($this->con, $i);
		}
		imap_setflag_full($this->con, $i, "\\Seen");
		$from = $head->from[0];
		$mail->setFrom($from->mailbox . "@" . $from->host, @$from->personal);
		$to = $head->to[0];
		$mail->addRecipient($to->mailbox . "@" . $to->host, @$to->personal);
		$mail->setSubject($title);
		$mail->setEncodedText($body);
		if($encoding){
			$mail->setText(mb_convert_encoding($body,"UTF-8",$encoding));
		}else{
			$mail->setText(mb_convert_encoding($body,"UTF-8","JIS,SJIS,EUC-JP,UTF-8,ASCII"));
		}
		$mail->setHeaders((array)$head);
		return $mail;
	}
	/**
	 * imap_fetchstructureの返り値のオブジェクトのtypeとsubtypeからMIME-Typeをテキストで返す
	 */
	function getMimeType(string $type, string $subType){
		$mimeType = "";
		switch($type){
			case 0:
				$mimeType = "text";
				break;
			case 1:
				$mimeType = "multipart";
				break;
			case 2:
				$mimeType = "message";
				break;
			case 3:
				$mimeType = "application";
				break;
			case 4:
				$mimeType = "audio";
				break;
			case 5:
				$mimeType = "image";
				break;
			case 6:
				$mimeType = "video";
				break;
			case 7:
				$mimeType = "other";
				break;
		}
		if(strlen($subType)){
			$mimeType .= "/".strtolower($subType);
		}
		return $mimeType;
	}
	/**
	 * imap_fetchstructureの返り値のオブジェクトのparametersから欲しいattributeの値を返す
	 */
	function getParameterValue(array $parameters, string $attribute){
		$attribute = strtolower($attribute);
		foreach($parameters as $param){
			if(strtolower($param->attribute) == $attribute){
				return $param->value;
			}
		}
		return false;
	}
	function getCon() {
		return $this->con;
	}
	function setCon($con) {
		$this->con = $con;
	}
	function getHost() {
		return $this->host;
	}
	function setHost($host) {
		$this->host = $host;
	}
	function getPort() {
		return $this->port;
	}
	function setPort($port) {
		$this->port = $port;
	}
	function getFlag() {
		return $this->flag;
	}
	function setFlag($flag) {
		$this->flag = $flag;
	}
	function getUser() {
		return $this->user;
	}
	function setUser($user) {
		$this->user = $user;
	}
	function getPass() {
		return $this->pass;
	}
	function setPass($pass) {
		$this->pass = $pass;
	}
}