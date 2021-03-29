<?php

/**
 * @package SOY2.controller
 */
interface SOY2_Controller{
	public static function run();
}
/**
 * @package SOY2.controller
 */
interface SOY2_ClassPathBuilder{
	function getClassPath($path);
}
/**
 * @package SOY2.controller
 */
interface SOY2_PathBuilder{
	function getPath();
	function getArguments();
}
