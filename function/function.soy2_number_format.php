<?php

/*
 * number_formatの第一引数が数字ではなかった場合
 */
function soy2_number_format($int){
	if(!is_numeric($int)) return 0;
	return number_format($int);
}
