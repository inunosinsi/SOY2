<?php

class SOY2Mail_SMTPLogic extends SOY2Mail implements SOY2Mail_SenderInterface{
	private $con;
	private $host;
	private $port;
	private $isSMTPAuth = false;
	private $isStartTLS = false;
	private $user;
	private $pass;
	private $debugHTML = false;
	private $debug = false;
	private $esmtpOptions = array();
	private $isSecure = false;
	function __construct(array $options){
		if(!isset($options["smtp.host"])){
			throw new SOY2MailException("[smtp.host] is necessary.");
		}
		if(!isset($options["smtp.port"])){
			throw new SOY2MailException("[smtp.port] is necessary.");
		}
		$this->host = $options["smtp.host"];
		$this->port = $options["smtp.port"];
		$this->isSMTPAuth = (isset($options["smtp.auth"])) ? $options["smtp.auth"] : false;
		$this->isStartTLS = (isset($options["smtp.starttls"])) ? $options["smtp.starttls"] : false;
		$this->user =  (isset($options["smtp.user"])) ? $options["smtp.user"] : null;
		$this->pass =  (isset($options["smtp.pass"])) ? $options["smtp.pass"] : null;
		if(isset($options["debug"]) && $options["debug"]){
			if(isset($_SERVER["REMOTE_ADDR"])){
				$this->debugHTML = true;
			}else{
				$this->debug = true;
			}
		}
	}
	function open(){
		$this->con = fsockopen($this->host, $this->port, $errono, $errnstr, 60);
		if(!$this->con){
			$this->close();
			throw new SOY2MailException("failed to connect");
		}
		stream_set_timeout($this->con, 1);
		$buff = $this->getSmtpResponse();
		if(substr($buff,0,3) != "220"){
			throw new SOY2MailException("failed to receive greeting message.");
		}
		$this->ehlo();
		if(stripos($this->host, 'ssl://') === 0 || stripos($this->host, 'tls://') === 0){
			$this->isSecure = true;
		}elseif(
			$this->isStartTLS &&//使う設定
			function_exists("openssl_open") &&//OpenSSLが利用可能 extension_loaded("openssl")
			function_exists("stream_socket_enable_crypto") &&//PHP 5.1.0以上
			isset($this->esmtpOptions['STARTTLS'])//STARTTLSが利用可能
		){
			if( $this->startTLS() ){
				$this->isSecure = true;
				$this->ehlo();
			}
		}
		if($this->isSMTPAuth && isset($this->esmtpOptions["AUTH"]) && is_array($this->esmtpOptions["AUTH"])){
			$authTypes = $this->esmtpOptions["AUTH"];
			/** CRAM-MD5を最優先にしてDIGEST-MD5の優先度を下げる **/
			if(in_array("CRAM-MD5",$authTypes)){
				$this->smtpCommand("AUTH CRAM-MD5");
				$buff = $this->getSmtpResponse();
				if(strlen($buff) < 5 || substr($buff,0,3) != "334") throw new SOY2MailException("smtp login failed");
				$challenge = base64_decode(substr(($buff),4));
				$response = SOY2Mail_SMTPAuth_CramMD5::getResponse($this->user,$this->pass,$challenge);
				$this->smtpCommand(base64_encode($response));
				while(true){
					$buff = $this->getSmtpResponse();
					if(substr($buff,0,3) == "235") break;
					if(substr($buff,0,3) == "501") throw new SOY2MailException("smtp login failed: wrong parameter");
					if(substr($buff,0,3) == "535") throw new SOY2MailException("smtp login failed: wrong id or password");
				}
			}else if(in_array("DIGEST-MD5",$authTypes)){
				$hostname = str_replace("ssl://", "", $this->host);
				$this->smtpCommand("AUTH DIGEST-MD5");
				$buff = $this->getSmtpResponse();
				if(strlen($buff) < 5 || substr($buff,0,3) != "334") throw new SOY2MailException("smtp login failed");
				$challenge = base64_decode(substr(trim($buff),4));
				$response = SOY2Mail_SMTPAuth_DigestMD5::getResponse($this->user,$this->pass,$challenge,$hostname);
				$this->smtpCommand(base64_encode($response));
				while(true){
					$buff = $this->getSmtpResponse();
					if(substr($buff,0,3) == "334") break;
					if(substr($buff,0,3) == "501") throw new SOY2MailException("smtp login failed: wrong parameter");
					if(substr($buff,0,3) == "535") throw new SOY2MailException("smtp login failed: wrong id or password");
				}
				$this->smtpCommand("");
				while(true){
					$buff = $this->getSmtpResponse();
					if(substr($buff,0,3) == "235") break;
					if(substr($buff,0,3) == "501") throw new SOY2MailException("smtp login failed: wrong parameter");
					if(substr($buff,0,3) == "535") throw new SOY2MailException("smtp login failed: wrong id or password");
				}
			}elseif(in_array("PLAIN",$authTypes)){
				$this->smtpCommand("AUTH PLAIN ".base64_encode(
					$this->user . "\0" .
					$this->user . "\0" .
					$this->pass ));
				while(true){
					$buff = $this->getSmtpResponse();
					if(substr($buff,0,3) == "235") break;
					if(substr($buff,0,3) == "501") throw new SOY2MailException("smtp login failed: wrong parameter");
					if(substr($buff,0,3) == "535") throw new SOY2MailException("smtp login failed: wrong id or password");
				}
			}elseif(in_array("LOGIN",$authTypes)){
				$this->smtpCommand("AUTH LOGIN");
				$buff = $this->getSmtpResponse();
				if(substr($buff,0,3) != "334") throw new SOY2MailException("smtp login failed");
				$this->smtpCommand(base64_encode($this->user));
				$buff = $this->getSmtpResponse();
				if(substr($buff,0,3) != "334") throw new SOY2MailException("smtp login failed");
				$this->smtpCommand(base64_encode($this->pass));
				while(true){
					$buff = $this->getSmtpResponse();
					if(substr($buff,0,3) == "235") break;
					if(substr($buff,0,3) == "501") throw new SOY2MailException("smtp login failed: wrong parameter");
					if(substr($buff,0,3) == "535") throw new SOY2MailException("smtp login failed: wrong id or password");
				}
			}else{
			}
		}
	}
	function ehlo(){
		$this->smtpCommand("EHLO ". php_uname("n"));// gethostname php_uname("n") $_SERVER["HOSTNAME"]
		$buff = $this->getSmtpResponse();//最初はドメイン
		while(strlen($buff) && substr($buff,0,4) != "250 "){
			$buff = $this->getSmtpResponse();
			if(preg_match("/^250[- ]([-A-Z0-9]+)(?:[= ](.*))?\$/i",trim($buff),$matches)){
				if(isset($matches[2])){
					if(strpos($matches[2]," ")!==false){
						$this->esmtpOptions[$matches[1]] = explode(" ",$matches[2]);
					}else{
						$this->esmtpOptions[$matches[1]] = $matches[2];
					}
				}else{
					$this->esmtpOptions[$matches[1]] = true;
				}
			}
		}
	}
	function startTLS(){
		$this->smtpCommand("STARTTLS");
		$buff = $this->getSmtpResponse();
		if( substr($buff,0,3) == "220" ){
			return stream_socket_enable_crypto($this->con, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
		}
	}
	function send(){
		$bccRecipients = $this->getBccRecipients();
		$recipients = $this->getRecipients();
		foreach($recipients as $recipient){
			$this->sendMail($recipient, $bccRecipients);
		}
	}
	function sendMail(SOY2Mail_MailAddress $sendTo, array $bccRecipients=array()){
		$sent = false;
		$try = $try_connect = 0;
		while(!$sent){
			$try++;
			while(!$this->con){
				$try_connect++;
				try{
					$this->open();
				}catch(Exception $e){
					$this->close();
					if($try_connect > 10){
						if($this->debug)echo "SMTP Failed to open SMTP connection.\n";
						throw $e;
					}
				}
				if($try_connect > 20){
					$this->close();
					throw new SOY2MailException("Too many failure to connect server.");
				}
			}
			try{
				$this->_sendMail($sendTo, $bccRecipients);
				$sent = true;
			}catch(Exception $e){
				$this->close();
				if($try > 2){
					throw $e;
				}
			}
			if($try > 5){
				$this->close();
				throw new SOY2MailException("Too many failure to send email.");
			}
		}
	}
	private function _sendMail(SOY2Mail_MailAddress $sendTo, array $bccRecipients=array()){
		$from = $this->getFrom();
		$title = $this->getEncodedSubject();
		$body = $this->getEncodedText();
		$body = str_replace(array("\r\n", "\r"), "\n", $body);  // CRLF, CR -> LF 正規表現で m オプションを使うためにLFにする
		$body = preg_replace('/^\\./m','..', $body);          // .～        -> ..～
		$body = str_replace("\n", "\r\n", $body);                // LF       -> CRLF
		$this->smtpCommand("MAIL FROM:<".$from->getAddress().">");
		while(true){
			$str = $this->getSmtpResponse();
			if(substr($str,0,3) == "250") break;
			if(!is_null($str) && strlen($str)<1)sleep(1);
			if(strlen($str) && substr($str,0,3)!="250")throw new SOY2MailException("Failed: MAIL FROM " . $str);
		}
		$this->isSendMailFrom = true;
		$this->smtpCommand("RCPT TO:<".$sendTo->getAddress().">");
		foreach($bccRecipients as $bccSendTo){
			$this->smtpCommand("RCPT TO:<".$bccSendTo->getAddress().">");
		}
		while(true){
			$str = $this->getSmtpResponse();
			if(strlen($str)<1)break;
			if(preg_match("/Ok/i",$str)) break;
			if(substr($str,0,3)!="250")throw new SOY2MailException("Failed: RCPT TO " . $str);
		}
		$this->smtpCommand("DATA");
		while(true){
			$str = $this->getSmtpResponse();
			if(strlen($str)<1)break;
			if(preg_match("/354/i",$str)) break;
			if(substr($str,0,3)!="250")throw new SOY2MailException("Failed: DATA " . $str);
		}
		$headers = $this->getHeaders();
		foreach($headers as $key => $value){
			if( "Content-Type" == $key ){ continue; }
			$this->data("$key: $value");
		}
		$this->data("MIME-Version: 1.0");
		$this->data("Subject: ".$title);
		$this->data("From: ".$from->getString());
		$this->data("To: ".$sendTo->getString());
		$attachments = $this->getAttachments();
		if(count($attachments)<1){
			if(isset($headers["Content-Type"])){
				$this->data("Content-Type: ".$headers["Content-Type"]);
			}else{
				$this->data("Content-Type: text/plain; charset=".$this->getEncoding()."");
			}
			$this->data("");
			$this->data($body);
		}else{
			$boundary = "----------" . md5(time());
			$this->data("Content-Type: multipart/mixed;  boundary=\"$boundary\"");
			$this->data("");
			$this->data("--".$boundary);
			if(isset($headers["Content-Type"])){
				$this->data("Content-Type: ".$headers["Content-Type"]);
			}else{
				$this->data("Content-Type: text/plain; charset=".$this->getEncoding()."");
			}
			$this->data("");
			$this->data($body);
			foreach($attachments as $filename => $attachment){
				if( !isset($attachment["contents"]) ){ continue; }
				$this->data("--".$boundary);
				if( !isset($attachment["mime-type"]) || strlen($attachment["mime-type"]) <1 ){
					$attachment["mime-type"] = "application/octet-stream";
				}
				$this->data("Content-Type: ".$attachment["mime-type"].";"."\r\n"." name=\"".mb_encode_mimeheader($filename)."\"");
				$this->data("Content-Disposition: inline;"."\r\n"." filename=\"".mb_encode_mimeheader($filename)."\"");
				$this->data("Content-Transfer-Encoding: base64");
				$this->data("");
				$this->data(wordwrap(base64_encode($attachment["contents"]),72, "\r\n", true));
			}
			$this->data("--". $boundary . "--");
		}
		$this->smtpCommand(".");
	}
	function close(){
		if($this->con && $this->smtpCommand("QUIT")){
			fclose($this->con);
		}
		$this->con = null;
	}
	function data(string $string){
		$this->smtpCommand($string);
	}
	function smtpCommand(string $string){
		if(!$this->con){
			throw new SOY2MailException('SMTP is null');
 			return;
 		}
 		if($this->debugHTML)echo "> ". htmlspecialchars($string) . "<br>";
		if($this->debug)echo "SMTP> ".$string."\n";
 		$result = fputs($this->con, $string."\r\n");
 		if($result == false){
			throw new SOY2MailException('Result is false.');
 		}
	}
	function getSmtpResponse(){
		$buff = fgets($this->con);
		while($buff === false || !strlen($buff)){
			$buff = fgets($this->con);
			$meta = stream_get_meta_data($this->con);
			if(feof($this->con) || $meta["timed_out"]){
				return null;
			}
		}
		if($this->debugHTML)echo "> ". htmlspecialchars($buff) . "<br>";
		if($this->debug)echo "SMTP< ".$buff;
		return $buff;
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
	function getIsSMTPAuth() {
		return $this->isSMTPAuth;
	}
	function setIsSMTPAuth($isSMTPAuth) {
		$this->isSMTPAuth = $isSMTPAuth;
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
	function getDebug() {
		return $this->debug;
	}
	function setDebug($debug) {
		$this->debug = $debug;
	}
}
class SOY2Mail_SMTPAuth_CramMD5{
	static function getResponse(string $user, string $pass, string $challengeStr){
		return $user." ".hash_hmac("md5",$challengeStr,$pass);
	}
}
class SOY2Mail_SMTPAuth_DigestMD5{
	static function getResponse(string $user, string $pass, string $challengeStr, string $hostname){
		$challenge = array();
		if(preg_match_all('/([-a-z]+)=(?:"([^"]*)"|([^=,]*))(?:,|$)/u',$challengeStr,$matches,PREG_SET_ORDER)){
			foreach($matches as $matche){
				$challenge[$matche[1]] = strlen($matche[2]) ? $matche[2] : $matche[3] ;
			}
		}
		if(!isset($challenge["algorithm"]) || !isset($challenge["nonce"])){
			throw new SOY2MailException("smtp login failed");
		}
		if(isset($challenge["qop"]) && strlen($challenge["qop"])){
			$qop = explode(",",$challenge["qop"]);
			if(!in_array("auth",$qop)){
				throw new SOY2MailException("smtp login failed");
			}
		}
		if(!isset($challenge["realm"])){
			$challenge["realm"] = "";
		}
		if(!isset($challenge["maxbuf"])){
			$challenge["maxbuf"] = "65536";
		}
		$response = array(
			"username" => $user,
			"realm" => $challenge["realm"],
			"nonce" => $challenge["nonce"],
			"cnonce" => preg_replace("/[^[:alnum:]]/", "", substr(base64_encode( md5(mt_rand(),true) ),0,21)),
			"nc" => "00000001",
			"qop" => "auth",
			"digest-uri" => 'smtp/'.$hostname,
			"response" => "",
			"maxbuf" => $challenge["maxbuf"],
		);
		$hashed = pack('H32', md5($user.":".$response["realm"].":".$pass));
		$a1 = $hashed.":".$response["nonce"].":".$response["cnonce"];
		$a2 = "AUTHENTICATE:".$response["digest-uri"];
		$response["response"] = md5(md5($a1).":".$response["nonce"].":".$response["nc"].":".$response["cnonce"].":".$response["qop"].":".md5($a2));
		$responseArr = array();
		foreach($response as $key => $value){
			$isContinue = false;
			switch($key){
				case "realm":
					if(!strlen($value)) $isContinue = false;
					break;
				case "username":
				case "nonce":
				case "cnonce":
				case "digest-uri":
					$value = '"'.$value.'"';
					break;
			}
			if(!$isContinue) continue;
			$responseArr[] = $key."=".$value;
		}
		$resonseStr = implode(",",$responseArr);
		return $resonseStr;
	}
}