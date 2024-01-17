<?php

class SOY2Mail_SendMailLogic extends SOY2Mail implements SOY2Mail_SenderInterface{
    function __construct(array $options) {}
    function open(){}
    function close(){}
    function send(){
    	$bccRecipients = $this->getBccRecipients();
    	$recipients = $this->getRecipients();
		foreach($recipients as $recipient){
			$this->sendMail($recipient, $bccRecipients);
		}
    }
    function sendMail(SOY2Mail_MailAddress $sendTo, array $bccRecipients=array()){
		$to = $sendTo->getString();
		$from = $this->getFrom();
		$title = $this->getEncodedSubject();
		$body = $this->getEncodedText();
		$headers = array();
		$_headers = $this->getHeaders();
		foreach($_headers as $key => $value){
			if( "Content-Type" == $key ){ continue; }
			$headers[] = "$key: $value";
		}
		$headers[] = "MIME-Version: 1.0" ;
		$headers[] = "From: " . $from->getString();
		$attachments = $this->getAttachments();
		if(count($attachments)<1){
			if(isset($_headers["Content-Type"])){
				$headers[] = "Content-Type: ".$_headers["Content-Type"];
			}else{
				$headers[] = "Content-Type: text/plain; charset=".$this->getEncoding();
			}
		}else{
			$boundary = "----------" . md5(time());
			$headers[] = "Content-Type: multipart/mixed;  boundary=\"$boundary\"";
			$_body = "--" . $boundary . "\r\n";
			if(isset($_headers["Content-Type"])){
				$_body .= "Content-Type: ".$_headers["Content-Type"] . "\r\n";
			}else{
				$_body .= "Content-Type: text/plain; charset=".$this->getEncoding()."" . "\r\n";
			}
			$body = $_body . "\r\n" . $body . "\r\n";
			foreach($attachments as $filename => $attachment){
				if( !isset($attachment["contents"]) ){ continue; }
				$body .= "--" . $boundary . "\r\n";
				if( !isset($attachment["mime-type"]) || strlen($attachment["mime-type"]) <1 ){
					$attachment["mime-type"] = "application/octet-stream";
				}
				$body .= "Content-Type: ".$attachment["mime-type"].";"."\r\n".
				         " name=\"".mb_encode_mimeheader($filename)."\"" . "\r\n";
				$body .= "Content-Disposition: inline;"."\r\n".
				         " filename=\"".mb_encode_mimeheader($filename)."\"" . "\r\n";
				$body .= "Content-Transfer-Encoding: base64" . "\r\n";
				$body .= "\r\n";
				$body .= wordwrap(base64_encode($attachment["contents"]),72, "\r\n", true) . "\r\n";
			}
			$body .= "--" . $boundary . "--";
		}
		/**
		 * RFC2821 4.5.2：SMTPクライアントは .から始まる行に.を付加し、サーバーは .から始まる行の.を除去する
		 * ただし、sendmailに渡す場合は「.」の処理はsendmailがやってくれる。
		 * Windows版mail()ではPHPがSMTP通信を行うが「.」の処理はPHP側がやってくれる。
		 */
		/**
		 * 改行コードはLF（Windows版mail()はSMTP通信を行うのでCRLF）を使う
		 *
		 * PHPマニュアルにはヘッダーの改行コードはCRLFとあるがこれは間違い。
		 * mail()の改行コードの扱いは問題がある。
		 * Manual: http://jp2.php.net/manual/ja/function.mail.php
		 * Bug report: http://bugs.php.net/15841
		 * http://www.webmasterworld.com/forum88/4368.htm
		 *
		 * RFC2822ではメールの改行コードはCRLFだが、
		 * *nix版PHPのmailはSMTP通信を行うのではなくsendmailコマンドを使うため
		 * ローカル環境の改行コードを使って値を渡すのが正しいとも言える。
		 * そのためmail()内部ではadditional_headerとTo:, Subject, 本文をLFで結合している。
		 * メール末尾もLF.LFとなっている。
		 *
		 * 改行コードをLFに統一してもmail()にはまだ問題がある。
		 * CRLF＋スペースorタブ以外の制御コードはいったんスペースに置換しているようで、
		 * ヘッダーのfoldingのためのLF+スペースがスペース＋スペースに置換されたまま戻らなくなってしまう。
		 * http://www.pubbs.net/php/200908/44353/
		 * RFC2822では一行は998文字までがMUST、78文字はSHOULDなのでRFC違反ではない。
		 *
		 * ただし、改行コードがLFのメールをsendmailに渡してもCRLFに変換してくれないことが
		 * 多いようなので、改行コードについてはRFC違反となる。
		 * が、CRLFとLFが混在するよりはましなので、LFで統一することにする。
		 */
		$title = str_replace(array("\r\n", "\r"), "\n", $title);
		$body = str_replace(array("\r\n", "\r"), "\n", $body);
		$to = str_replace(array("\r\n", "\r"), "\n", $to);
		$headersText = implode("\n",$headers);
		if($this->isWindows()){
			$title = str_replace("\n", "\r\n", $title);
			$body = str_replace("\n", "\r\n", $body);
			$to = str_replace("\n", "\r\n", $to);
			$headersText = implode("\r\n",$headers);
		}
		$sendmail_params  = "-f".$from->getAddress();
		mail($to, $title, $body, $headersText, $sendmail_params);
		if(count($bccRecipients) >0){
			$headers[] = "X-To: ".$sendTo->getString();
			if($this->isWindows()){
				$headersText = implode("\r\n",$headers);
			}else{
				$headersText = implode("\n",$headers);
			}
			foreach($bccRecipients as $bccSendTo){
				$to = $bccSendTo->getString();
				$to = str_replace(array("\r\n", "\r"), "\n", $to);
				if(isset($_SERVER["WINDIR"]) || isset($_SERVER["windir"])){
					$to = str_replace("\n", "\r\n", $to);
				}
				mail($to, $title, $body, $headersText, $sendmail_params);
			}
		}
    }
    /**
     * OSがWindowsかどうかを返す
     */
    private function isWindows(){
		if(isset($_SERVER["WINDIR"]) || isset($_SERVER["windir"])){
			return true;
		}elseif(isset($_SERVER["SystemRoot"]) && strpos(strtolower($_SERVER["SystemRoot"]),"windows") !== false){
			return true;
		}elseif(isset($_SERVER["SYSTEMROOT"]) && strpos(strtolower($_SERVER["SYSTEMROOT"]),"windows") !== false){
			return true;
		}else{
			return false;
		}
    }
}
