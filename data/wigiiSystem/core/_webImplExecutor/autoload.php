<?php
/**
 *  This file is part of Wigii.
 *  Wigii is developed to inspire humanity. To Humankind we offer Gracefulness, Righteousness and Goodness.
 *  
 *  Wigii is free software: you can redistribute it and/or modify it 
 *  under the terms of the GNU General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, 
 *  or (at your option) any later version.
 *  
 *  Wigii is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; 
 *  without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  
 *  See the GNU General Public License for more details.
 *
 *  A copy of the GNU General Public License is available in the Readme folder of the source code.  
 *  If not, see <http://www.gnu.org/licenses/>.
 *
 *  @copyright  Copyright (c) 2016  Wigii.org
 *  @author     <http://www.wigii.org/system>      Wigii.org 
 *  @link       <http://www.wigii-system.net>      <https://github.com/wigii/wigii>   Source Code
 *  @license    <http://www.gnu.org/licenses/>     GNU General Public License
 */

/**
 * Created by LWR, the 22 july 09
 */

if(!defined("CORE_PATH")) define("CORE_PATH", wigiiSystem_PATH . "core/");
if(!defined("IMPL_PATH")) define("IMPL_PATH", CORE_PATH . "_webImplExecutor/");
if(!defined("TEMPLATE_PATH")) define("TEMPLATE_PATH", IMPL_PATH . "templates/");
if(!defined("DATATYPE_PATH")) define("DATATYPE_PATH", CORE_PATH . "datatype/");
if(!defined("LANG_FOLDER")) define("LANG_FOLDER", CORE_PATH . "langs/");
if(!defined("CLIENT_CONFIG_PATH")) define("CLIENT_CONFIG_PATH", wigiiSystem_PATH . "configs/" . CLIENT_NAME . "/");

define("ACCENT_list", "ÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖØÙÚÛÜÝÞßàáâãäåæçèéêëìíîïðñòóôõöøùúûüýýþÿ");
define("Replace_ACCENT_list", "AAAAAAACEEEEIIIIDNOOOOOOUUUUYPBaaaaaaaceeeeiiiionoooooouuuuyypy");

//first include each functions libs
include(CORE_PATH."api/libs/functions.php");
include(IMPL_PATH."libs/functions.php");

// include libs used by webimpl
$includePath = ini_get('include_path');
if(strpos($includePath, ";")!==false){
	//window environment
	set_include_path(ini_get('include_path').';'.IMPL_PATH."libs/");
} else {
	//unix environment
	set_include_path(ini_get('include_path').':'.IMPL_PATH."libs/");
}

// include PEAR packages
/* not active yet.
$includePath = ini_get('include_path');
if(strpos($includePath, ";")!==false){
	//window environment
	set_include_path(ini_get('include_path').';'.CORE_PATH."api/libs/pear/php/");
} else {
	//unix environment
	set_include_path(ini_get('include_path').':'.CORE_PATH."api/libs/pear/php/");
}
*/

// include external vendors repository, maintained through Composer (http://getcomposer.org/)
require_once(CORE_PATH."api/libs/vendor/autoload.php");

// include HTMLPurify library to prevent any XSS injection in html areas
require_once(CORE_PATH."_webImplExecutor/libs/htmlpurifier-4.5.0-lite/library/HTMLPurifier.auto.php");

// Include Twig Library for the templating and configure the loader
require_once(CORE_PATH.'_webImplExecutor/libs/Twig/Autoloader.php');
Twig_Autoloader::register();

// registers Wigii classes autoload function
spl_autoload_register('wigii_autoload');

/**
* public
* this function defines where to look to find classes
* return: void
*/
function wigii_autoload($class_name) {
	if (!class_exists($class_name)) {
		$ok = false;
		if (!$ok) {
			$filename = DATATYPE_PATH.$class_name.".php";
			$ok = file_exists($filename);
		}
		if (!$ok) {
			$filename = IMPL_PATH."templates/$class_name.php";
			$ok = file_exists($filename);
		}
		if (!$ok) {
			$filename = IMPL_PATH."libs/$class_name.php";
			$ok = file_exists($filename);
		}
		if (!$ok) {
			$filename = IMPL_PATH."libs/securimage/$class_name.php";
			$ok = file_exists($filename);
		}
		if (!$ok) {
			$filename = IMPL_PATH."libs/form/$class_name.php";
			$ok = file_exists($filename);
		}
		//////////////
		// wigii API
		if (!$ok) {
			$filename = CORE_PATH."model/$class_name.php";
			$ok = file_exists($filename);
		}
		if (!$ok) {
			$filename = CORE_PATH."model/eventModel/$class_name.php";
			$ok = file_exists($filename);
		}
		if (!$ok) {
			$filename = CORE_PATH."api/$class_name.php";
			$ok = file_exists($filename);
		}
		if (!$ok) {
			$filename = CORE_PATH."api/libs/$class_name.php";
			$ok = file_exists($filename);
		}
		if (!$ok) {
			$filename = CORE_PATH."api/exceptions/$class_name.php";
			$ok = file_exists($filename);
		}
		if (!$ok) {
			$filename = CORE_PATH."api/technical/$class_name.php";
			$ok = file_exists($filename);
		}
		if (!$ok) {
			$filename = CORE_PATH."api/impl/$class_name.php";
			$ok = file_exists($filename);
		}
		if (!$ok) {
			$filename = CORE_PATH."api/impl/web/$class_name.php";
			$ok = file_exists($filename);
		}
		if (!$ok) {
			$filename = CORE_PATH."api/impl/restricted/$class_name.php";
			$ok = file_exists($filename);
		}
		if (!$ok) {
			$filename = CORE_PATH."api/impl/mock/$class_name.php";
			$ok = file_exists($filename);
		}
		if (!$ok) {
			$filename = CORE_PATH."api/impl/".CLIENT_NAME."/$class_name.php";
			$ok = file_exists($filename);
		}
		if (!$ok) {
			$filename = CORE_PATH."_cliImplExecutor/$class_name.php";
			$ok = file_exists($filename);
		}
		if (!$ok) {
			$filename = CORE_PATH."api/impl/cli/$class_name.php";
			$ok = file_exists($filename);
		}
		if (!$ok) {
			$filename = CORE_PATH."wpl/$class_name.php";
			$ok = file_exists($filename);
		}
		if (!$ok) {
			$filename = CORE_PATH."wfl/$class_name.php";
			$ok = file_exists($filename);
		}
		if (!$ok) {
			$filename = CORE_PATH."apiTest/$class_name.php";
			$ok = file_exists($filename);
		}
		if (!$ok) {
			$filename = CORE_PATH."apiTest/testRunner/$class_name.php";
			$ok = file_exists($filename);
		}
		// end wigii API
		////////////////

		//PHPExcel Lib
		if (!$ok && strpos($class_name, 'PHPExcel') === 0) {
			$filename =	IMPL_PATH."libs/".
								str_replace('_',DIRECTORY_SEPARATOR,$class_name).
								'.php';
			$ok = !((file_exists($filename) === false) || (is_readable($filename) === false));
		}

		//Services_JSON
		if (!$ok && strpos($class_name, 'Services_JSON') === 0) {
			$filename =	CORE_PATH."api/libs/services_json.php";
			$ok = !((file_exists($filename) === false) || (is_readable($filename) === false));
		}

		////////////////////
		// wigii Addons
		// default path is: define("ADDONS_PATH", wigiiSystem_PATH . "addons/") in index.php
		//
		if(!defined("ADDONS_PATH")){
			if(is_dir(wigiiSystem_PATH . "addons/")){
				define("ADDONS_PATH", wigiiSystem_PATH . "addons/");
			}
		}
		if(defined("ADDONS_PATH")){
			// addon: CodeProfiler
			if (!$ok) {
				$filename = ADDONS_PATH."CodeProfiler/$class_name.php";
				$ok = file_exists($filename);
			}
			// addon: Reporting
			if (!$ok) {
				$filename = ADDONS_PATH."Reporting/$class_name.php";
				$ok = file_exists($filename);
			}
			// addon: SFTP
			if (!$ok) {
				$filename = ADDONS_PATH."SFTP/$class_name.php";
				$ok = file_exists($filename);
			}
			// addon: CampaignMonitor
			if (!$ok) {
				$filename = ADDONS_PATH."CampaignMonitor/$class_name.php";
				$ok = file_exists($filename);
			}
			// addon: Cresus
			if (!$ok) {
				$filename = ADDONS_PATH."Cresus/$class_name.php";
				$ok = file_exists($filename);
			}
			// addon: Excel
			if (!$ok) {
				$filename = ADDONS_PATH."Excel/$class_name.php";
				$ok = file_exists($filename);
			}
			// addon: Geometry
			if (!$ok) {
				$filename = ADDONS_PATH."Geometry/$class_name.php";
				$ok = file_exists($filename);
			}
			// addon: Medidata
			if (!$ok) {
				$filename = ADDONS_PATH."Medidata/$class_name.php";
				$ok = file_exists($filename);
			}
		}
		// end wigii Addons
		////////////////////

		if ($ok)
			include_once ($filename);
		else {
			//echo "wigii_autoload cannot find file for class:$class_name";
			throw new ServiceException("wigii_autoload cannot find file for class:$class_name", ServiceException::UNSUPPORTED_OPERATION);
		}
	}
}


