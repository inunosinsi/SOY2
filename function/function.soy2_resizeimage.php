<?php

/*
 * Created on 2010/04/28
 *
 * To change the template for this generated file go to
 * Window - Preferences - PHPeclipse - PHP - Code Templates
 */
/**
 * 縦横の最大の大きさ指定してリサイズ
 */
function soy2_resizeimage_maxsize($filepath,$savepath,$max){
	if(function_exists("getimagesize")){
		list($width, $height, $type, $attr) = getimagesize($filepath);
	}
	else if(class_exists("Imagick")){
		$thumb = new Imagick($filepath);
		$width = $thumb->getImageWidth();
		$height = $thumb->getImageHeight();
		$thumb = null;
	}
	else if(function_exists("NewMagickWand")){
		$thumb = NewMagickWand();
		MagickReadImage($thumb,$filepath);
		list($width,$height) = array(MagickGetImageWidth($thumb),MagickGetImageHeight($thumb));
		$thumb = null;
	}
	else{
		throw new Exception("soy2_resizeimage_maxsize is not avaiable.please install Imagick,NewMagickWand or GD");
	}
	if($width <= $max AND $height <= $height){
		return soy2_resizeimage($filepath,$savepath,$width,$height);
	}
	if($width > $height){
		$width = $max;
		$height = null;
	}else{
		$width = null;
		$height = $max;
	}
	return soy2_resizeimage($filepath,$savepath,$width,$height);
}
/**
 * 縦横の大きさ指定してリサイズ
 *
 * @param $filepath
 * @param $savepath
 * @param $width
 * @param $height
 */
function soy2_resizeimage($filepath,$savepath,$width = null,$height = null){
	if(class_exists("Imagick")){
		$thumb = new Imagick($filepath);
		$imageSize = array($thumb->getImageWidth(),$thumb->getImageHeight());
		if(is_null($width) && is_null($height)){
			$width = $imageSize[0];
			$height = $imageSize[1];
		}else if(is_null($width)){
			$width = $imageSize[0] * $height / $imageSize[1];
		}else if(is_null($height)){
			$height = $imageSize[1] * $width / $imageSize[0];
		}
		$thumb->thumbnailImage($width,$height);
		$thumb->writeImage($savepath);
		return true;
	}
	if(function_exists("NewMagickWand")){
		$thumb = NewMagickWand();
		MagickReadImage($thumb,$filepath);
		$imageSize = array(MagickGetImageWidth($thumb),MagickGetImageHeight($thumb));
		if(is_null($width) && is_null($height)){
			$width = $imageSize[0];
			$height = $imageSize[1];
		}else if(is_null($width)){
			$width = $imageSize[0] * $height / $imageSize[1];
		}else if(is_null($height)){
			$height = $imageSize[1] * $width / $imageSize[0];
		}
		if(!MagickResizeImage($thumb,$width,$height,MW_LanczosFilter,1)){
			trigger_error("Failed [MagickResizeImage] " . __FILE__ . ":" . __LINE__,E_USER_ERROR);
			return -1;
		}
		if(!MagickWriteImage($thumb,$savepath)){
			trigger_error("Failed [MagickWriteImage] " . __FILE__ . ":" . __LINE__,E_USER_ERROR);
			return -1;
		}
		return true;
	}
	return soy2_image_resizeimage_gd($filepath,$savepath,$width,$height);
}
function soy2_image_resizeimage_gd($filepath,$savepath,$width = null,$height = null){
	$info = pathinfo($filepath); //php version is 5.2.0 use pathinfo($filepath,PATHINFO_EXTENSION);
	if(!isset($info["extension"])) {
		trigger_error("Failed [Type is empty] " . __FILE__ . ":" . __LINE__,E_USER_ERROR);
		return -1;
	}
	$type = strtolower($info["extension"]);
	if($type == "jpg")$type = "jpeg";
	$from = "imagecreatefrom" . $type;
	if(!function_exists($from)){
		trigger_error("Failed [Invalid Type:".$type."] " . __FILE__ . ":" . __LINE__,E_USER_ERROR);
		return -1;
	}
	$srcImage = $from($filepath);
	$imageSize = getimagesize($filepath);
	if(is_null($width) && is_null($height)){
		$width = $imageSize[0];
		$height = $imageSize[1];
	}else if(is_null($width)){
		$width = $imageSize[0] * $height / $imageSize[1];
	}else if(is_null($height)){
		$height = $imageSize[1] * $width / $imageSize[0];
	}
	$dstImage = imagecreatetruecolor($width,$height);
	imagecopyresampled($dstImage,$srcImage, 0, 0, 0, 0,
  			$width, $height, $imageSize[0], $imageSize[1]);
  	$info = pathinfo($savepath); //php version is 5.2.0 use pathinfo($filepath,PATHINFO_EXTENSION);
	$type = strtolower($info["extension"]);;
	switch($type){
		case "jpg":
		case "jpeg":
			return imagejpeg($dstImage,$savepath,100);
			break;
		default:
			$to = "image" . $type;
			if(function_exists($to)){
				$to($dstImage,$savepath);
				return true;
			}
			trigger_error("Failed [Invalid Type:".$type."] " . __FILE__ . ":" . __LINE__,2);
			return -1;
			break;
	}
}
