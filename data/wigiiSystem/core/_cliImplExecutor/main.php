<?php
/**
 *  This file is part of Wigii (R) software.
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
/*
//$GLOBALS["executionTime"]=array();
$GLOBALS["executionTimeNb"] = 0;
//$GLOBALS["executionTime"]["startIndex"." ".$GLOBALS["executionTimeNb"]++] = microtime(true);
*/

$DEBUG_EXECUTION_ENABLED= true;

// Looks for client name: option -c clientName
// ( argv[0] is the name of the script )
define ("MAIN_USAGE", "Usage [-shell | -noTrace] -c ClientName|NoClient [-u UserName -p Password | -uRootPrincipal] commandName subArguments");
for($i = 0; $i < $argc; $i++) {
    if($argv[$i] == '-c') {
        // CWE 14.04.2019: supports working with no client
        if($argv[$i+1]=='NoClient') {
            define("NO_CLIENT",true);
            define ("CLIENT_NAME", ""); // empty client has name empty.
        }
        else define ("CLIENT_NAME", $argv[$i+1]);
    }
    elseif($argv[$i] == '-noTrace') {
        $DEBUG_EXECUTION_ENABLED = false;
    }
    // CWE 14.04.2019: integrates with OS shell
    elseif($argv[$i] == '-shell') {
        $DEBUG_EXECUTION_ENABLED = false;
    }
}
define("DEBUG_EXECUTION_ENABLED", $DEBUG_EXECUTION_ENABLED);

if(!defined("CLIENT_NAME")) {
	echo "Undefined client name. ".MAIN_USAGE;
	exit(1004); // ServiceException::INVALID_ARGUMENT
} 

define("wigiiSystem_PATH", "../../");
define("CORE_PATH", wigiiSystem_PATH . "core/");
define("IMPL_PATH", CORE_PATH . "_webImplExecutor/");
define("CLI_PATH", CORE_PATH . "_cliImplExecutor/");
define("TEMPLATE_PATH", IMPL_PATH . "templates/");
define("DATATYPE_PATH", CORE_PATH . "datatype/");
define("LANG_FOLDER", CORE_PATH . "langs/");
if(!defined("NO_CLIENT")) define("CLIENT_CONFIG_PATH", wigiiSystem_PATH . "configs/" . CLIENT_NAME . "/");
if(!defined("NO_CLIENT")) define("CLIENT_DATA_PATH", wigiiSystem_PATH."../../users/" . CLIENT_NAME . "/");
define("ADDONS_PATH", wigiiSystem_PATH . "addons/");
// Medair (CWE) 25.08.2017 added access to :
// temporary uploaded files zone (to enable batch file upload)
define("TEMPORARYUPLOADEDFILE_path",  wigiiSystem_PATH."tempUploadedFiles/");
// and client files to enable data manipulation
if(!defined("NO_CLIENT")) define("FILES_PATH", wigiiSystem_PATH."../../users/" . CLIENT_NAME . "/data/uploadedFiles/");

//load the class paths
include (IMPL_PATH . "autoload.php");


// CWE 14.04.2019 if no client, then starts standard implementation
if(defined("NO_CLIENT")) {
    $cliExecutor = CliExecutor::start();
}
/**
 * Command Line executor starts the API
 * client implementation START
 */
else {
    //starts the CLIENT implementation
    include_once (CLIENT_CONFIG_PATH . "start_cli.php");
    //adds the CLIENT configuration
    if(file_exists(CLIENT_CONFIG_PATH . "config_cli.php")) {
    	include_once (CLIENT_CONFIG_PATH . "config_cli.php");
    }
    else {
    	include_once (CLIENT_CONFIG_PATH . "config.php");	
    }
}

/**
 * Cli Executor process command line and ends the execution
 */
$cliExecutor->processAndEnds($argc, $argv);


/*
//$GLOBALS["executionTime"]["end"] = microtime(true);
$firstTime = reset(//$GLOBALS["executionTime"]);
$lastTime = $firstTime;
$executionTimeLabel = array();
foreach(//$GLOBALS["executionTime"] as $label=>$time){
      $executionTimeLabel[] = $label." +".number_format(($time-$lastTime)*1000)."ms (".number_format(($time-$firstTime),3)."s)";
      $lastTime = $time;
}
fput($executionTimeLabel);
*/