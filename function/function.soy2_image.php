<?php

/*
 * soy2_image_info
 * @param String filepath
 * @return Array("width" => int, "height" => int)
 * 指定した画像の幅と高さを返す
 */
function soy2_image_info($filepath){
	if(!is_readable($filepath) || is_dir($filepath)){
		return false;
	}
	/*
	 * GD
	 * http://php.net/manual/en/book.image.php
	 */
	if(function_exists("getimagesize")){
		$imageSize = getimagesize($filepath);
		return array("width" => $imageSize[0], "height" => $imageSize[1]);
	}
	/*
	 * Image Magick
	 * http://php.net/manual/en/book.imagick.php
	 */
	if(class_exists("Imagick")){
		$thumb = new Imagick($filepath);
		return array("width" => $thumb->getImageWidth(), "height" => $thumb->getImageHeight());
	}
	/*
	 * Gmagick
	 * http://php.net/manual/en/book.gmagick.php
	 */
	if(class_exists("Gmagick")){
		$thumb = new Gmagick($filepath);
		return array("width" => $thumb->getimagewidth(), "height" => $thumb->getimageheight());
	}
	/*
	 * NewMagickWand
	 * http://www.magickwand.org/
	 */
	if(function_exists("NewMagickWand")){
		$thumb = NewMagickWand();
		MagickReadImage($thumb,$filepath);
		return array("width" => MagickGetImageWidth($thumb), "height" => MagickGetImageHeight($thumb));
	}
	return null;
}
