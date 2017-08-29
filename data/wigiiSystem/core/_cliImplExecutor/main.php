<?php
/*
//$GLOBALS["executionTime"]=array();
$GLOBALS["executionTimeNb"] = 0;
//$GLOBALS["executionTime"]["startIndex"." ".$GLOBALS["executionTimeNb"]++] = microtime(true);
*/

$DEBUG_EXECUTION_ENABLED= true;

// Looks for client name: option -c clientName
// ( argv[0] is the name of the script )
define ("MAIN_USAGE", "Usage [-noTrace] -c ClientName [-u UserName -p Password | -uRootPrincipal] commandName subArguments");
for($i = 0; $i < $argc; $i++) {
    if($argv[$i] == '-c') {
        define ("CLIENT_NAME", $argv[$i+1] );
    }
    elseif($argv[$i] == '-noTrace') {
        $DEBUG_EXECUTION_ENABLED = false;
    }
}
define("DEBUG_EXECUTION_ENABLED", $DEBUG_EXECUTION_ENABLED);

if(!defined("CLIENT_NAME")) {
	echo "Undefined client name. ".MAIN_USAGE;
	return false;
} 

define("wigiiSystem_PATH", "../../");
define("CORE_PATH", wigiiSystem_PATH . "core/");
define("IMPL_PATH", CORE_PATH . "_webImplExecutor/");
define("TEMPLATE_PATH", IMPL_PATH . "templates/");
define("DATATYPE_PATH", CORE_PATH . "datatype/");
define("LANG_FOLDER", CORE_PATH . "langs/");
define("CLIENT_CONFIG_PATH", wigiiSystem_PATH . "configs/" . CLIENT_NAME . "/");
define("ADDONS_PATH", wigiiSystem_PATH . "addons/");
// Medair (CWE) 25.08.2017 added access to :
// temporary uploaded files zone (to enable batch file upload)
define("TEMPORARYUPLOADEDFILE_path",  wigiiSystem_PATH."tempUploadedFiles/");
// and client files to enable data manipulation
define("FILES_PATH", wigiiSystem_PATH."../../users/" . CLIENT_NAME . "/data/uploadedFiles/");

//load the paths
include (IMPL_PATH . "autoload.php");

/**
 * Command Line excutor start the API
 * client implementation START
 */
//start the CLIENT implementation
include_once (CLIENT_CONFIG_PATH . "start_cli.php");
//add the CLIENT configuration
if(file_exists(CLIENT_CONFIG_PATH . "config_cli.php")) {
	include_once (CLIENT_CONFIG_PATH . "config_cli.php");
}
else {
	include_once (CLIENT_CONFIG_PATH . "config.php");	
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