<?php

/**
 * SOY2Mail 標準サーバ設定クラス
 *
 * SOY2Mail#importを使うにはSOY2が必要です。
 */
class SOY2Mail_ServerConfig {
    const SERVER_TYPE_SMTP = 0;
	const SERVER_TYPE_SENDMAIL = 2;
	const RECEIVE_SERVER_TYPE_POP  = 0;
	const RECEIVE_SERVER_TYPE_IMAP = 1;
    private $sendServerType = SOY2Mail_ServerConfig::SERVER_TYPE_SENDMAIL;
    private $isUseSMTPAuth = true;
    private $isUsePopBeforeSMTP = false;
    private $sendServerAddress = "localhost";
    private $sendServerPort = 25;
    private $sendServerUser = "";
    private $sendServerPassword = "";
    private $isUseSSLSendServer = false;
    private $receiveServerType = SOY2Mail_ServerConfig::RECEIVE_SERVER_TYPE_POP;
    private $receiveServerAddress = "localhost";
    private $receiveServerPort = 110;
    private $receiveServerUser = "";
    private $receiveServerPassword = "";
    private $isUseSSLReceiveServer = false;
    private $fromMailAddress = "";
    private $fromMailAddressName = "";
    private $returnMailAddress = "";
    private $returnMailAddressName = "";
    private $encoding = "ISO-2022-JP";
    /**
     * 設定からSOY2Mailオブジェクトを生成する
     */
    function buildReceiveMail(){
    	switch($this->receiveServerType){
    		case self::RECEIVE_SERVER_TYPE_IMAP:
    			$flag = null;
    			if($this->getIsUseSSLReceiveServer())$flag = "ssl";
    			return SOY2Mail::create("imap",array(
    				"imap.host" => $this->getReceiveServerAddress(),
    				"imap.port" => $this->getReceiveServerPort(),
    				"imap.user" => $this->getReceiveServerUser(),
    				"imap.pass" => $this->getReceiveServerPassword(),
    				"imap.flag" => $flag
    			));
    			break;
    		case self::RECEIVE_SERVER_TYPE_POP:
    		default:
    			$host = $this->getReceiveServerAddress();
    			if($this->getIsUseSSLReceiveServer())$host =  "ssl://" . $host;
    			return SOY2Mail::create("pop",array(
    				"pop.host" => $host,
    				"pop.port" => $this->getReceiveServerPort(),
    				"pop.user" => $this->getReceiveServerUser(),
    				"pop.pass" => $this->getReceiveServerPassword()
    			));
    			break;
    	}
    }
    /**
     * 設定からSOY2Mailオブジェクトを生成する
     */
    function buildSendMail(){
    	$mail = null;
    	switch($this->sendServerType){
    		case self::SERVER_TYPE_SMTP:
    			$host = $this->getSendServerAddress();
    			if($this->getIsUseSSLSendServer())$host =  "ssl://" . $host;
    			$mail = SOY2Mail::create("smtp",array(
    				"smtp.host" => $host,
    				"smtp.port" => $this->getSendServerPort(),
    				"smtp.user" => $this->getSendServerUser(),
    				"smtp.pass" => $this->getSendServerPassword(),
    				"smtp.auth" => ($this->getIsUseSMTPAuth()) ? "PLAIN" : false
    			));
    			break;
    		case self::SERVER_TYPE_SENDMAIL:
    		default:
    			$mail = SOY2Mail::create("sendmail",array());
    			break;
    	}
    	if($mail){
    		$mail->setEncoding($this->getEncoding());
    		$mail->setSubjectEncoding($this->getEncoding());
    		$mail->setFrom($this->getFromMailAddress(),$this->getFromMailAddressName());
			if(strlen($this->getReturnMailAddress())>0){
				$replyTo = new SOY2Mail_MailAddress($this->getReturnMailAddress(), $this->getReturnMailAddressName(), $this->getEncoding());
				$mail->setHeader("Reply-To", $replyTo->getString());
			}
    	}
    	return $mail;
    }
    /**
     * export config
     */
    function export(){
    	return base64_encode(addslashes(serialize($this)));
    }
    /**
     * import config
     */
    function import(string $str){
    	$obj = unserialize(stripslashes($str));
    	if($obj && $obj instanceof SOY2Mail_ServerConfig){
    		SOY2::cast($this,$obj);
    	}else{
    		throw new SOY2MailException("Failed to import");
    	}
    }
    function getSendServerType() {
    	return $this->sendServerType;
    }
    function setSendServerType($sendServerType) {
    	$this->sendServerType = $sendServerType;
    }
    function getIsUseSMTPAuth() {
    	return $this->isUseSMTPAuth;
    }
    function setIsUseSMTPAuth($isUseSMTPAuth) {
    	$this->isUseSMTPAuth = $isUseSMTPAuth;
    }
    function getIsUsePopBeforeSMTP() {
    	return $this->isUsePopBeforeSMTP;
    }
    function setIsUsePopBeforeSMTP($isUsePopBeforeSMTP) {
    	$this->isUsePopBeforeSMTP = $isUsePopBeforeSMTP;
    }
    function getSendServerAddress() {
    	return $this->sendServerAddress;
    }
    function setSendServerAddress($sendServerAddress) {
    	$this->sendServerAddress = $sendServerAddress;
    }
    function getSendServerPort() {
    	return $this->sendServerPort;
    }
    function setSendServerPort($sendServerPort) {
    	$this->sendServerPort = $sendServerPort;
    }
    function getSendServerUser() {
    	return $this->sendServerUser;
    }
    function setSendServerUser($sendServerUser) {
    	$this->sendServerUser = $sendServerUser;
    }
    function getSendServerPassword() {
    	return $this->sendServerPassword;
    }
    function setSendServerPassword($sendServerPassword) {
    	$this->sendServerPassword = $sendServerPassword;
    }
    function getIsUseSSLSendServer() {
    	return $this->isUseSSLSendServer;
    }
    function setIsUseSSLSendServer($isUseSSLSendServer) {
    	$this->isUseSSLSendServer = $isUseSSLSendServer;
    }
    function getReceiveServerType() {
    	return $this->receiveServerType;
    }
    function setReceiveServerType($receiveServerType) {
    	$this->receiveServerType = $receiveServerType;
    }
    function getReceiveServerAddress() {
    	return $this->receiveServerAddress;
    }
    function setReceiveServerAddress($receiveServerAddress) {
    	$this->receiveServerAddress = $receiveServerAddress;
    }
    function getReceiveServerPort() {
    	return $this->receiveServerPort;
    }
    function setReceiveServerPort($receiveServerPort) {
    	$this->receiveServerPort = $receiveServerPort;
    }
    function getReceiveServerUser() {
    	return $this->receiveServerUser;
    }
    function setReceiveServerUser($receiveServerUser) {
    	$this->receiveServerUser = $receiveServerUser;
    }
    function getReceiveServerPassword() {
    	return $this->receiveServerPassword;
    }
    function setReceiveServerPassword($receiveServerPassword) {
    	$this->receiveServerPassword = $receiveServerPassword;
    }
    function getIsUseSSLReceiveServer() {
    	return $this->isUseSSLReceiveServer;
    }
    function setIsUseSSLReceiveServer($isUseSSLReceiveServer) {
    	$this->isUseSSLReceiveServer = $isUseSSLReceiveServer;
    }
    function getFromMailAddress() {
    	return $this->fromMailAddress;
    }
    function setFromMailAddress($fromMailAddress) {
    	$this->fromMailAddress = $fromMailAddress;
    }
    function getFromMailAddressName() {
    	return $this->fromMailAddressName;
    }
    function setFromMailAddressName($fromMailAddressName) {
    	$this->fromMailAddressName = $fromMailAddressName;
    }
    function getReturnMailAddress() {
    	return $this->returnMailAddress;
    }
    function setReturnMailAddress($returnMailAddress) {
    	$this->returnMailAddress = $returnMailAddress;
    }
    function getReturnMailAddressName() {
    	return $this->returnMailAddressName;
    }
    function setReturnMailAddressName($returnMailAddressName) {
    	$this->returnMailAddressName = $returnMailAddressName;
    }
    function getEncoding() {
    	return $this->encoding;
    }
    function setEncoding($encoding) {
    	$this->encoding = $encoding;
    }
}
