<?php

function soy2_cancel_magic_quotes_gpc(){
	if(get_magic_quotes_gpc()){
		$_POST = soy2_stripslashes($_POST);
		$_GET = soy2_stripslashes($_GET);
		$_COOKIE = soy2_stripslashes($_COOKIE);
		$_REQUEST = soy2_stripslashes($_REQUEST);
	}
}
function soy2_stripslashes($value){
	return is_array($value) ? array_map('soy2_stripslashes', $value) : stripslashes($value);
}
