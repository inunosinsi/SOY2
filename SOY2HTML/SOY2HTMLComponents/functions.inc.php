<?php

function soy2html_layout_include(string $file){
	$layoutDir = SOY2HTMLConfig::LayoutDir();
	@include($layoutDir . $file);
}
function soy2html_layout_get(string $file){
	try{
		ob_start();
		soy2html_layout_include($file);
		$html = ob_get_contents();
		ob_end_clean();
		return $html;
	}catch(Exception $e){
		ob_end_flush();
		throw $e;
	}
}
