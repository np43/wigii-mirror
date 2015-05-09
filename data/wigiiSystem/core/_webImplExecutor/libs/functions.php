<?php
/**
 *  This file is part of Wigii.
 *
 *  Wigii is free software: you can redistribute it and\/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  Wigii is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with Wigii.  If not, see <http:\//www.gnu.org/licenses/>.
 *
 *  @copyright  Copyright (c) 2012 Wigii 		 http://code.google.com/p/wigii/    http://www.wigii.ch
 *  @license    http://www.gnu.org/licenses/     GNU General Public License
 */

/*
 * Created on 24 juil. 09
 * by LWR
 */

/*
file class
this help to manage files
*/
class File{
	function list_dirs($dir,$prefix=""){
		$dir_dirs = File::scan_dir($dir);
		foreach($dir_dirs as $d){
			if(is_dir($dir . $d) && File::check_filter($d,"",$prefix) && $d != "." && $d != "..")
				$dirs[] = $d;
		}
		return $dirs;
	}

	function list_files($dir,$ext_filter="",$prefix=""){
		$dir_files = File::scan_dir($dir);
		foreach($dir_files as $file){
			if(is_file($dir . $file) && File::check_filter($file,$ext_filter,$prefix))
				$files[] = $file;
		}
		return $files;
	}

	function scan_dir($dir){
		$dh  = opendir($dir);
		$files = null;
		if($dh){ //if directory does not exist return null;
			while (false !== ($filename = readdir($dh))) {
				$files[] = $filename;
			}
		}

		return $files;
	}

	function check_filter($name,$ext_filter,$prefix){
		if(is_string($ext_filter) && !empty($ext_filter))
			$ext_filter = array($ext_filter);

		if(is_array($ext_filter)){
			foreach($ext_filter as $ext){
				$ext_length = (int) strlen($ext);
				$ext_check = substr($name,-$ext_length,$ext_length) == $ext || $ext_check;
			}
		}else
			$ext_check = true;

		if(!empty($prefix))
			$prefix_check = substr($name,0,strlen($prefix)) == $prefix;
		else
			$prefix_check = true;

		return $ext_check && $prefix_check;
	}
}

function rrmdir($dir, $noWarning=false) {
	if (is_dir($dir)) {
		$objects = scandir($dir);
		foreach ($objects as $object) {
			if ($object != "." && $object != "..") {
				if (filetype($dir."/".$object) == "dir"){
					rrmdir($dir."/".$object, $noWarning);
				} else {
					if($noWarning) @unlink($dir."/".$object);
					else unlink($dir."/".$object);
				}
			}
		}
		reset($objects);
		if($noWarning) @rmdir($dir);
		else rmdir($dir);
	}
}

/**
 *  retourne le type MIME à partir d'une extension
 *  Exemple : $ext = ".pdf" => type renvoyé : "application/pdf"
 */
function typeMime($ext) {
	// on récupère la liste des extensions de fichiers et leurs types Mime associés
	$mime = parse_ini_file(IMPL_PATH."libs/mime.ini");
	$extension = strtolower(substr($ext, 1));

	/* on affecte le type Mime si on a trouvé l'extension sinon le type par défaut (un flux d'octets).
	Attention : Internet Explorer et Opera ne supporte pas le type MIME standard */
	if (array_key_exists($extension, $mime))
		$type = $mime[$extension];
	else
		$type = 'application/octet-stream';

	return $type;
}
function mime2ext($mime) {
	// on récupère la liste des extensions de fichiers et leurs types Mime associés
	$mimes = parse_ini_file(IMPL_PATH."libs/mime.ini");
	$mime = strtolower($mime);

	/* on affecte le type Mime si on a trouvé l'extension sinon le type par défaut (un flux d'octets).
	Attention : Internet Explorer et Opera ne supporte pas le type MIME standard */
	$ext = array_search($mime, $mimes);
	if($ext){
		return ".".$ext;
	} else return null;
}

function formatFileSize($size) {
	if ($size < 1024 * 1024)
		$size = round(($size / 1024.0), 1)."ko";
	else
		if ($size < 1024 * 1024 * 1024)
			$size = round(($size / (1024.0 * 1024.0)), 1)."Mo";
		else
			if ($size < 1024 * 1024 * 1024 * 1024)
				$size = round(($size / (1024.0 * 1024.0 * 1024.0)), 1)."Go";
	return $size;
}

/**
 * @param addSlashes replace ' by \' only, this is useful when this function
 * is called for the infobull of groups
 */
function prepareTextForInfoBuble($tempValue, $addSlashes=false, $HTMLEntitiesDecode=true){
	if($HTMLEntitiesDecode){
		$tempValue = html_entity_decode($tempValue, ENT_COMPAT);
	}
	$tempValue =stripslashes($tempValue);
	$tempValue = str_replace(array("<", ">", '"', "\n", "\r"), array("&lt;", "&gt;", "&quot;", "", ""), $tempValue);
	if($addSlashes) $tempValue = str_replace("'", "\'", $tempValue);
	return $tempValue;
}

//control if the arry is empty, or have only empty values
function checkIsEmpty($var){
	if ($var == null) return true;
	if(is_array($var)){
		foreach($var as $i){
			if($i!= null) return false;
		}
		return true;
	}
	return false;
}

//return true if it is a mimeType of an image we can cut
function isImage($mimeType){
	switch ($mimeType) {
		case "image/gif" :
		case "image/jpg" :
		case "image/jpeg" :
		case "image/bmp" :
		case "image/png" :
			return true;
		default :
			return false;
	}
}

include_once(IMPL_PATH . "libs/bmp.php");

function cutImage($srcfile, $dstfile, $dstW=null, $dstH=null) {

	//this is a try using the phpThumb library without success. It seem that this library use behind the same imagecreate functions, therefore we don't have better chance to generates thumbnails for big images
//	global $PHPTHUMB_CONFIG;
////	fput($PHPTHUMB_CONFIG);
//	$phpThumb = new phpThumb();
//	$srcfile = '../../../../../'.$srcfile;
//	$dstfile = '../../../../../'.$dstfile;
//	$phpThumb->setSourceFilename($srcfile);
//	$phpThumb->RenderToFile($dstfile);
//	if($dstW) $phpThumb->setParameter('w', $dstW);
//	if($dstW) $phpThumb->setParameter('h', $dstH);
//	if($dstW) $phpThumb->setParameter('sia', $dstfile);
//	$message = "object is created";
//	if ($phpThumb->GenerateThumbnail()) {
//        $message= "Image rended successfully in $dstfile";
////        if ($phpThumb->RenderToFile($dstfile)) {
////            //image uploaded - you will probably need to put image info into a database at this point
////            $message="Image rended successfully.";
////        } else {
////            //unable to write file to final destination directory - check folder permissions
////            $message= "Error! Please try again (render).";
////        }
//    } else {
//        //unable to generate the image
//        $message= "Error! Please try again (generate, from: $srcfile to: $dstfile).";
//    }
//    fput($message);
//	return true;

	//fput("cutImage: ".$srcfile." -> ".$dstfile);
	// prends la taille de l image source
	$srcinfo = getimagesize($srcfile);
//	fput("1");
	$srcW = $srcinfo[0];
	$srcH = $srcinfo[1];

	//vérifie la taille de l'image. Memory limit à 48M
	//avec des essais, j'ai réussi à passé un jpeg de 3500x2625 = 9187500
	//avec 3600x2700 = 9720000 ça n'a pas marché
	//4225x2812 = 11880700 ok pour 64M on peu laisser la valeur pour 48M
//	fput($srcW * $srcH);
//	fput(ini_get("memory_limit"));
	if($srcW * $srcH > 9187500) return true;
//	fput("2");

	if($dstW==null) $dstW = $srcW/$srcH * $dstH;
	if($dstH==null) $dstH = $srcH/$srcW * $dstW;

	if($dstH > $srcH) $dstH = $srcH;
	if($dstW > $srcW) $dstW = $srcW;

//	eput($srcinfo);
//	return;

	// trouve le type de l'image
	switch ($srcinfo[2]) {
		case IMAGETYPE_GIF :
			$srctype = "gif";
			break;
		case IMAGETYPE_JPEG :
			$srctype = "jpg";
			break;
		case IMAGETYPE_PNG :
			$srctype = "png";
			break;
		case IMAGETYPE_BMP :
			$srctype = "bmp";
			break;
		default :
			return false;
	}

	// cree les resources images
	$dstimg = imagecreatetruecolor($dstW, $dstH);
	//fput("3");

	switch ($srctype) {
		case "jpg" :
			$srcimg = imagecreatefromjpeg($srcfile);
			break;
		case "gif" :
			//dans le cas d'un gif il faut gérer la couleur transparente
			//définie dans le fichier
			$srcimg = imagecreatefromgif($srcfile);
			$trans_colour = imagecolortransparent($srcimg);
			imagecolortransparent($dstimg, $trans_colour);
			imagefill($dstimg, 0, 0, $trans_colour);
			break;
		case "png" :
			//dans le cas d'un png il faut gérer la transparence
			//et pour utiliser le imagesavealpha, il faut désactiver l'alphablending
			imagealphablending($dstimg, false);
			imagesavealpha($dstimg, true);
			//fput("3.1");
			$trans_colour = imagecolortransparent($dstimg);
			//$trans_colour = imagecolorallocate($dstimg, $trans_colour);
			//fput("3.2");
			//fput($dstimg." ".$trans_colour);
			imagefill($dstimg, 0, 0, $trans_colour);
			//fput("3.3");
			$srcimg = imagecreatefrompng($srcfile);
			//fput("3.4");
			break;
		case "bmp" :
			$srcimg = imagecreatefrombmp($srcfile);
			break;
	}
	//fput("4");


	// image source plus etroite que image destination
	if (($dstW / $dstH) > ($srcW / $srcH)) {
		$factor = $srcW / $dstW;
		$tmpH = $dstH * $factor;
		$srcY = ($srcH - $tmpH) / 2;

		imagecopyresampled($dstimg, $srcimg, 0, 0, 0, $srcY, $dstW, $dstH, $srcW, $tmpH);
	} else // image source plus large que image destination?
		{
		$factor = $srcH / $dstH;
		$tmpW = $dstW * $factor;
		$srcX = ($srcW - $tmpW) / 2;

		imagecopyresampled($dstimg, $srcimg, 0, 0, $srcX, 0, $dstW, $dstH, $tmpW, $srcH);
	}
	//fput("5");


//	$trans_colour = imagecolortransparent($srcimg);
//	imagecolortransparent($dstimg, $trans_colour);
//	imagefill($dstimg, 0, 0, $trans_colour);
	//imagefill($dstimg, 0, 0, $trans_colour);

	switch ($srctype) {
		case "jpg" :
			imagejpeg($dstimg, $dstfile, 95);
			break;
		case "gif" :
			imagegif($dstimg, $dstfile);
			break;
		case "png" :
			imagepng($dstimg, $dstfile);
			break;
		case "bmp" :
			imagebmp($dstimg, $dstfile);
			break;
	}
	$old = umask(0000);
	chmod($dstfile, 0666);
	umask($old);

	//fput("6");

	return true;
}
