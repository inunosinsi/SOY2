<?php
/**
 * @param string
 * @return string
 */
function read_code_file(string $path){
	$code = trim(file_get_contents($path));
	$code = trim($code);
	$code = ltrim($code, "<?php");
	$code = trim($code);
	$code = rtrim($code, "?>");
	$code = trim($code);
	for(;;){
		if(is_bool(strpos($code, "\n\n"))) break;
		$code = trim(str_replace("\n\n", "\n", $code));
	}
	return "\n".$code;
}

/**
 * @param string, string
 */
function read_batch(string $dust, string $dir){
	if($dh = opendir($dir)){
		while(($f = readdir($dh)) !== false) {
			$res = strpos($f, ".");
			if(is_numeric($res) && $res === 0) continue;
			file_put_contents($dust, read_code_file($dir.$f), FILE_APPEND);
		}
	}
}

$mode = (isset($argv[1]) && is_numeric($argv[1])) ? (int)$argv[1] : 0;

// 共通
$list = array(
	"SOY2" => 1,
	"SOY2Action" => 0,
	"SOY2DAO" => 1,
	"SOY2Debug" => 0,
	"SOY2HTML" => 1,
	"SOY2Logger" => 0,
	"SOY2Logic" => 1,
	"SOY2Mail" => 1,
	"SOY2Plugin" => 1,
	"SOY2Session" => 1,
	"function" => 1
);

$dustDir = __DIR__."/build/";
if(!file_exists($dustDir)) mkdir($dustDir);

switch($mode){
	case 1:	// soycms
		$dust = $dustDir."soy2_build.min.php";
		$list["SOY2Action"] = 1;
		break;
	case 2:	// soyshop
		$dust = $dustDir."soy2_build.min.php";
		break;
	default:// full package
		$dust = $dustDir."soy2_build.php";
		$list["SOY2Debug"] = 1;
		$list["SOY2Action"] = 1;
		$list["SOY2Logger"] = 1;
		break;
}

if(file_exists($dust)) unlink($dust);

// build
file_put_contents($dust, "<?php\n/**\n * https://github.com/inunosinsi/SOY2\n */", FILE_APPEND);
foreach($list as $className => $on){
	if($on === 0) continue;
	$dir = __DIR__."/".$className."/";
	switch($className){
		case "SOY2":
			file_put_contents($dust, read_code_file($dir."SOY2.php"), FILE_APPEND);
			file_put_contents($dust, read_code_file($dir."SOY2_Controller.class.php"), FILE_APPEND);
			file_put_contents($dust, read_code_file($dir."class/SOY2ActionController.php"), FILE_APPEND);
			file_put_contents($dust, read_code_file($dir."class/SOY2PageController.php"), FILE_APPEND);
			break;
		case "SOY2Mail":
			file_put_contents($dust, read_code_file($dir."SOY2Mail.php"), FILE_APPEND);
			file_put_contents($dust, read_code_file($dir."SOY2Mail_ServerConfig.class.php"), FILE_APPEND);
			file_put_contents($dust, read_code_file($dir."SOY2Mail_POPLogic.class.php"), FILE_APPEND);
			file_put_contents($dust, read_code_file($dir."SOY2Mail_IMAPLogic.class.php"), FILE_APPEND);
			file_put_contents($dust, read_code_file($dir."SOY2Mail_SendMailLogic.class.php"), FILE_APPEND);
			file_put_contents($dust, read_code_file($dir."SOY2Mail_SMTPLogic.class.php"), FILE_APPEND);
			break;
		case "function":
			read_batch($dust, $dir);
			break;
		default:
			file_put_contents($dust, read_code_file($dir.$className.".php"), FILE_APPEND);
			if($dh = opendir($dir)){
				while(($f = readdir($dh)) !== false) {
					if(is_numeric(strpos($f, ".")) || !is_dir($dir.$f)) continue;
					read_batch($dust, $dir.$f."/");
				}
				closedir($dh);
			}
			break;
	}
}
