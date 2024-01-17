<?php

class SOY2Mail_POPLogic extends SOY2Mail implements SOY2Mail_ReceiverInterface{
	private $con;
	private $host;
	private $port;
	private $flag;
	private $folder;
	private $user;
	private $pass;
	function __construct(array $options){
		if(!isset($options["pop.host"])){
			throw new SOY2MailException("[pop.host] is necessary.");
		}
		if(!isset($options["pop.port"])){
			throw new SOY2MailException("[pop.port] is necessary.");
		}
		if(!isset($options["pop.user"])){
			throw new SOY2MailException("[pop.user] is necessary.");
		}
		if(!isset($options["pop.pass"])){
			throw new SOY2MailException("[pop.pass] is necessary.");
		}
		$this->host = $options["pop.host"];
		$this->port = $options["pop.port"];
		if(isset($options["pop.flag"]))$this->flag = $options["pop.flag"];
		if(isset($options["pop.folder"]))$this->folder = $options["pop.folder"];
		$this->user = $options["pop.user"];
		$this->pass = $options["pop.pass"];
	}
	function __destruct(){
		if($this->con) $this->close();
	}
	function open(){
		$this->con = fsockopen($this->host, $this->port, $errono, $errnstr);
		if(!$this->con){
			$this->close();
			throw new SOY2MailException("failed to connect");
		}
		$buff = $this->popCommand("USER ".$this->user);
		if(!$buff)throw new SOY2MailException("Failed to connect pop server");
		$buff = $this->popCommand("PASS ".$this->pass);
		if(!$buff)throw new SOY2MailException("Failed to connect pop server");
	}
	function close(){
		if($this->con){
			$this->popCommand("QUIT");
			fclose($this->con);
			$this->con = null;
		}
	}
	function receive(){
		if(!$this->con)$this->open();
		$res = $this->popCommand("LIST");
		if(!$res)throw new SOY2MailException("failed to open Receive Server");
		$mailId = null;
		while(true){
			$buff = $this->getPopResponse();
			if($buff == ".")break;
			$array = explode(" ",$buff);
			if(!is_numeric($array[0])) continue;
			if(is_null($mailId)) $mailId = $array[0];
		}
		if(is_null($mailId)) return false;
		$res = $this->popCommand("RETR ".$mailId);
		$flag = false;
		$header = "";
		$body = "";
		$encoding = "JIS";
		$headers = array();
		$mail = new SOY2Mail();
		while(true){
			$buff = $this->getPopResponse();
			if($buff == ".")break;
			if(!$flag && strlen($buff)==0){
				$flag = true;
				continue;
			}
			if(strpos($buff,"..")===0){
				$buff = substr($buff,1);
			}
			if($flag){
				$body .= $buff . "\r\n";
			}else{
				$header .= $buff . "\r\n";
			}
		}
		$this->popCommand("DELE " . $mailId);
		$mail->setRawData($header."\r\n".$body);
		$headers = $this->parseHeaders($header);
		if(isset($headers["Content-Type"]) && preg_match("/boundary=\"?(.*?)\"?/",$headers["Content-Type"], $tmp)){
			$boundary = $tmp[1];
			$bodies = explode("--". $boundary, $body);
			$attachCount = count($bodies);
			for($i=0;$i<$attachCount;++$i){
				$tmpHeader = substr($bodies[$i], 0, strpos($bodies[$i], "\r\n\r\n"));
				$tmpBody = substr($bodies[$i], strpos($bodies[$i], "\r\n\r\n")+4);
				$tmpHeaders = $this->parseHeaders($tmpHeader);
				if(isset($tmpHeaders["Content-Disposition"]) && preg_match("/filename.*=(.*)/",$tmpHeaders["Content-Disposition"], $tmp)){
					$filename = preg_replace('/["\']/',"",$tmp[1]);
					$mail->addAttachment($filename, "", base64_decode($tmpBody));
					continue;
				}
				if(isset($tmpHeaders["Content-Type"]) && preg_match("/charset=(.*)/",$tmpHeaders["Content-Type"],$tmp)){
					$encoding = $tmp[1];
					$body = $tmpBody;
				}
			}
		}else{
			if(isset($headers["Content-Type"]) && preg_match("/charset=(.*)/",$headers["Content-Type"],$tmp)){
				$encoding = $tmp[1];
			}
		}
		if(isset($headers["From"])){
			$from = explode(",",$headers["From"]);
			$from = trim($from[0]);
			if(preg_match('/"?(.*?)"?\s*<?(.+@.+)>?/',$from,$tmp)){
				$label = mb_decode_mimeheader($tmp[1]);
				$address = $tmp[2];
				$mail->setFrom($address, $label);
			}
		}
		if(isset($headers["To"])){
			$toes = explode(",",$headers["To"]);
			foreach($toes as $to){
				$to = trim($to);
				if(preg_match('/"?(.*?)"?\s?<?(.+@.+)>?/',$to,$tmp)){
					$label = mb_decode_mimeheader($tmp[1]);
					$address = $tmp[2];
					$mail->addRecipient($address, $label);
				}
			}
		}
		if(isset($headers["Subject"])){
			$mail->setSubject(mb_decode_mimeheader(@$headers["Subject"]));
		}
		$mail->setHeaders($headers);
		$mail->setEncodedText($body);
		$mail->setText(mb_convert_encoding($body,"UTF-8",$encoding));
		$mail->setEncoding($encoding);
		return $mail;
	}
	function popCommand(string $string){
		fputs($this->con, $string."\r\n");
  		$buff = fgets($this->con);
		if(strpos($buff,"+OK") == 0){
			return $buff;
		}else{
			return false;
		}
	}
	function getPopResponse(){
		$buff = fgets($this->con);
		$buff = rtrim($buff, "\r\n");
		return $buff;
	}
	/**
	 * 受信メッセージのヘッダーを解析し配列にする
	 */
	function parseHeaders(string $header){
		$headers = array();
		$header = preg_replace("/\r\n[ \t]+/", ' ', $header);
		$raw_headers = explode("\r\n", $header);
		foreach($raw_headers as $value){
			$name  = substr($value, 0, $pos = strpos($value, ':'));
			$value = ltrim(substr($value, $pos + 1));
			if (isset($headers[$name]) AND is_array($headers[$name])) {
				$headers[$name][] = $value;
			} elseif (isset($headers[$name])) {
				$headers[$name] = array($headers[$name], $value);
			} else {
				$headers[$name] = $value;
			}
		}
		return $headers;
	}
}