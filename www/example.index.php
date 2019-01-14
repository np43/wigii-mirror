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


if(!defined("HTTPS_ON")) define("HTTPS_ON", false);
$alias = preg_replace('/\/[^\/]*$/', "", $_SERVER["SCRIPT_NAME"]); //this is used to define the SITE_ROOT after. --> important to keep

//CLIENT Selector depending on Host
switch($_SERVER['HTTP_HOST']){
	case "localhost":
	case "wigii.xxx.xx":
		define ("CLIENT_NAME", "Example" );
		break;
	default:
		echo "wigii system, no client selected";
		return;
		break;
}

/**
 * main paths
 */
define("CLIENT_WEB_PATH", CLIENT_NAME."/" );
define("CLIENT_WEB_URL", CLIENT_WEB_PATH );
define("TEMPORARYUNZIPFORVIEWINGFILE_path", "temporary_unzipForViewing/");
define("FILES_PATH", "../users/" . CLIENT_NAME . "/data/uploadedFiles/");

define("LOG_PATH", "../log/"); //stores the log when sys console is enabled
define("TMP_PATH", "../tmp/"); //stores the session files, this is defined in the .htaccess

define("wigiiSystem_PATH", "../data/wigiiSystem/");
define("wigiiSystem_WEBPATH", __DIR__);
define("TEMPORARYUPLOADEDFILE_path",  wigiiSystem_PATH."tempUploadedFiles/");
define("CORE_PATH", wigiiSystem_PATH . "core/");
define("IMPL_PATH", CORE_PATH . "_webImplExecutor/");
define("TEMPLATE_PATH", IMPL_PATH . "templates/");
define("DATATYPE_PATH", CORE_PATH . "datatype/");
define("LANG_FOLDER", CORE_PATH . "langs/");
define("CLIENT_CONFIG_PATH", wigiiSystem_PATH . "configs/" . CLIENT_NAME . "/");

/**
 * configuration part
 */
define("GLOBAL_STATISTIC_ENABLED", true);
define("ELEMENT_STATISTIC_ENABLED", false);
define("FILE_STATISTIC_ENABLED", false);
define("TEMPORARYUPLOADEDFILE_lifetime", 60 * 60 * 24); //number of second a file remains in the temp folder
define("TEMPORARYUNZIPFORVIEWINGFILE_lifetime", 60 * 60 * 12); //number of second a file remains in the temp folder for viewing


/**
 * !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
 * !! WARNING 
 * !! Everything from this part of the index file should not be edited
 * !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
 */

// Maintenance is ON ?
if(strpos($_SERVER['SCRIPT_FILENAME'], "indexModif.php") !== false){
	$MAINTENANCE_ON = true;
} else $MAINTENANCE_ON = false;

// SITE_ROOT & SITE_ROOT_forFileUrl constant
if (HTTPS_ON) {
	if (!defined("SITE_ROOT"))
		define("SITE_ROOT", "https://" . $_SERVER['HTTP_HOST'] . $alias . "/".($MAINTENANCE_ON ? "modif/" : ""));
	if (!defined("SITE_ROOT_forFileUrl"))
		define("SITE_ROOT_forFileUrl", "https://" . $_SERVER['HTTP_HOST'] . $alias . "/");

} else {
	if (!defined("SITE_ROOT"))
		define("SITE_ROOT", "http://" . $_SERVER['HTTP_HOST'] . $alias . "/".($MAINTENANCE_ON ? "modif/" : ""));
	if (!defined("SITE_ROOT_forFileUrl"))
		define("SITE_ROOT_forFileUrl", "http://" . $_SERVER['HTTP_HOST'] . $alias . "/");
}

// wigii version
include_once("version.php");


define("SESSION_NAME", "sessionId" . md5($_SERVER['HTTP_HOST'] . $alias));

//server configuration (the server might refuse to change settings)
ini_set("register.globals", "0");
ini_set("magic_quotes_gpc", false);

//load the paths
include (IMPL_PATH . "autoload.php");

/**
 * Session setup
 * the autoload function need to be defined before starting the session
 * otherwise the unserialize of object will not be correct
 */
ini_set("session.use_cookies", "1");
ini_set("session.use_only_cookies", "1");
session_name(SESSION_NAME);
session_cache_limiter("nocache");

define("SESSION_STARTED", session_start());
define("SESSION_ID", session_id());

// Reload on HTTPS if required
// if no url defined and no wigii_anchor, then reload to setup the anchor
if (($_GET["url"]==null && !$_COOKIE["wigii_anchor"]) || (HTTPS_ON and $_SERVER['HTTPS'] != "on")) {
	include_once (IMPL_PATH . "templates/reload.php");
}

/******************************
 ******************************
 * DEBUG and EXECUTION trace
 * ****************************
 * ****************************/

//the sys console is enabled if double cliking on the version label of the wigii system
//if debug logger or execution trace are enable, then logs will be showed otherwise the sys console will not be filled
if ($_SESSION["SYS_CONSOLE_ENABLED"])
	$SYS_CONSOLE_ENABLED = true;
else
	$SYS_CONSOLE_ENABLED = false;
	
$DEBUG_EXECUTION_ENABLED = $SYS_CONSOLE_ENABLED;
//$DEBUG_EXECUTION_ENABLED= true;
if($DEBUG_EXECUTION_ENABLED){
	if ($_SESSION["DEBUG_EXECUTION_LOG_FILEPATH"]){
		$DEBUG_EXECUTION_LOG_FILEPATH = $_SESSION["DEBUG_EXECUTION_LOG_FILEPATH"];
	} else {
		//at this will be append the principal name
		$DEBUG_EXECUTION_LOG_FILEPATH = dirname($_SERVER["SCRIPT_FILENAME"])."/".LOG_PATH.CLIENT_NAME."_".date("Y.m.d_H.i.s")."_".SESSION_ID;
		$_SESSION["DEBUG_EXECUTION_LOG_FILEPATH"] = $DEBUG_EXECUTION_LOG_FILEPATH;
	}
}

define("SYS_CONSOLE_ENABLED", $SYS_CONSOLE_ENABLED);
//$DEBUG_EXECUTION_ENABLED = true;
define("DEBUG_EXECUTION_ENABLED", $DEBUG_EXECUTION_ENABLED);

/******************************/
/******************************/

//two level of cache enabling
//EXEC is for everything we want to store in session which allows to work with wigii (login, language, etc)
//BUILD is for all the cache which prevents rebuilding elements as config, etc..
define("SESSION_CACHE_EXEC_ENABLED", true); //$_SERVER['HTTP_HOST'] != "localhost");
define("SESSION_CACHE_BUILD_ENABLED", true); //$_SERVER['HTTP_HOST'] != "localhost"); //$_SERVER['HTTP_HOST'] != "localhost");
define("DEBUG_LOGGER_ENABLED", DEBUG_EXECUTION_ENABLED);
define("EXECUTION_SINK_ENABLED", DEBUG_EXECUTION_ENABLED);

define("EMPTY_ANSWER_ID_URL", "NoAnswer");

/**
 * Wigii Executor start the API
 * client implementation START
 */
//start the CLIENT implementation
include_once (CLIENT_CONFIG_PATH . "start.php");

//add the CLIENT configuraiton
include_once (CLIENT_CONFIG_PATH . "config.php");

if(ServiceProvider::getTranslationService()->getLanguage()==null){
	ServiceProvider::getTranslationService()->setLanguage(DEFAULT_LANGUAGE);
}

/**
 * Wigii Executor process request and ends the execution
 */
$WigiiExecutor->processAndEnds();


/**
 * include the test_Runner index to make testing only
 * if not on updating!!
 */
if (TEST_ON && !ServiceProvider :: getExecutionService()->getIsUpdating()) {
	include (CORE_PATH . "apiTest/index_testRunner.php");
}

//if we want to publish in the console the message created in the tests
//ServiceProvider::getExecutionService()->end();

//this is used to publish JSCode added from the testZone, like for example to test the FormFacade
if (!ServiceProvider :: getExecutionService()->getIsUpdating()) {
	ServiceProvider :: getExecutionService()->flushJsCode();
}
