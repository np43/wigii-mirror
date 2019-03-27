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
 * Initializes support of Wigii NCD language
 * Created by CWE on February 7th 2018
 */
//$GLOBALS["executionTime"][$GLOBALS["executionTimeNb"]++." "."start header.wncd.php"] = microtime(true);
$this->executionSink()->publishStartOperation("TEMPLATE header.wncd.php");
?>
<div id='wncdProgramOutput' style='display:none; top:0px; left:0px;'></div>
<!-- Wigii NCD initialization -->
<script type="text/javascript">
window.wigiiNcdEtp = {options:{
	/**
	 * Defines program output location
	 */
	programOutput: "#wncdProgramOutput",
	/**
	 * Callback when Wigii NCD ETP layer is ready
	 */
	ncdEtpReady: function(wigiiNcdEtp) {
		if(!wigiiNcdEtp.startupLog) wigiiNcdEtp.startupLog = {};
		wigiiNcdEtp.startupLog.ncdEtpReady=true;
		wigiiNcdEtp.startupLog.version = wigiiNcdEtp.version();
	},
	/**
	 * Callback when Wigii NCD ETP Fx layer is ready
	 */
	ncdEtpFxReady: function(wigiiNcdEtp) {
		if(!wigiiNcdEtp.startupLog) wigiiNcdEtp.startupLog = {};
		wigiiNcdEtp.startupLog.ncdEtpFxReady=true;
		wigiiNcdEtp.program.context.startupLog = wigiiNcdEtp.startupLog;
	}
}};
</script>
<!-- script src="https://www.wigii.org/system/libs/wigii-ncd-core.min.js"></script -->
<!-- script src="https://www.wigii.org/system/libs/wigii-ncd-core.js"></script-->
<script src="<?=SITE_ROOT_forFileUrl;?>assets/js/wigii-ncd-core.js"></script>
<?
//$GLOBALS["executionTime"][$GLOBALS["executionTimeNb"]++." "."end header.wncd.php"] = microtime(true);
$this->executionSink()->publishEndOperation("TEMPLATE header.wncd.php");