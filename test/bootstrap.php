<?php

date_default_timezone_set('Europe/Paris');
// Set up the classpath for testing wigii
define("CLIENT_NAME", "Example" );
define("CLIENT_WEB_PATH", CLIENT_NAME."/" );
define("CLIENT_WEB_URL", CLIENT_WEB_PATH );
define("TEMPORARYUNZIPFORVIEWINGFILE_path", "temporary_unzipForViewing/");
define("FILES_PATH", "files/");

define("LOG_PATH", "../log/"); //stores the log when sys console is enabled
define("TMP_PATH", "tmp/"); //stores the session files, this is defined in the .htaccess

define("wigiiSystem_PATH", "../data/wigiiSystem/");

//Cannot be a mounted docker dir as file permissions get crazy
define("TEMPORARYUPLOADEDFILE_path",  "/tmp/www-uploads");

define("CORE_PATH", wigiiSystem_PATH . "core/");
define("IMPL_PATH", CORE_PATH . "_webImplExecutor/");
define("TEMPLATE_PATH", IMPL_PATH . "templates/");
define("DATATYPE_PATH", CORE_PATH . "datatype/");
define("LANG_FOLDER", CORE_PATH . "langs/");
define("CLIENT_CONFIG_PATH", wigiiSystem_PATH . "configs/" . CLIENT_NAME . "/");

define("FIXTURES", "fixtures/");

/**
 * configuration part
 */
define("GLOBAL_STATISTIC_ENABLED", true);
define("ELEMENT_STATISTIC_ENABLED", false);
define("FILE_STATISTIC_ENABLED", false);
define("TEMPORARYUPLOADEDFILE_lifetime", 60 * 60 * 24); //number of second a file remains in the temp folder
define("TEMPORARYUNZIPFORVIEWINGFILE_lifetime", 60 * 60 * 12); //number of second a file remains in the temp folder for viewing

define("SITE_ROOT", "https://unittest/");
define("SITE_ROOT_forFileUrl", "https://unittest/");

define("SESSION_NAME", "unittest");

define("DEBUG_LOGGER_ENABLED", false);
define("EXECUTION_SINK_ENABLED", false);

define("ACCENT_list", "ÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖØÙÚÛÜÝÞßàáâãäåæçèéêëìíîïðñòóôõöøùúûýýþÿ");
define("Replace_ACCENT_list", "AAAAAAACEEEEIIIIDNOOOOOOUUUUYPBaaaaaaaceeeeiiiionoooooouuuyypy");

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

// mock out PHP where we need to alter the behaviour for unit tests
require_once ('testLibs/MockCoreFunctions.php');

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
            $filename = 'testLibs/'.$class_name.".php";
            $ok = file_exists($filename);
        }
        if (!$ok) {
            $filename = DATATYPE_PATH.$class_name.".php";
            $ok = file_exists($filename);
        }
        if (!$ok) {
            $filename = CLIENT_CONFIG_PATH.$class_name.".php";
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
        }
        // end wigii Addons
        ////////////////////

        if ($ok)
            include_once ($filename);
//        else {
//            //echo "wigii_autoload cannot find file for class:$class_name";
//            throw new ServiceException("wigii_autoload cannot find file for class:$class_name", ServiceException::UNSUPPORTED_OPERATION);
//        }
    }
}
// we should call start so that the init of wiggii is set/ Disable debug etc for test output
WigiiCoreExecutor::start();


