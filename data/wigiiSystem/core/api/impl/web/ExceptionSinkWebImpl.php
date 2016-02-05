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

/**
 * ExceptionSink implementation which integrates with wigii web site
 * Created by CWE on 8 juin 09
 */
class ExceptionSinkWebImpl extends ExceptionSink
{
	private $systemConsoleEnabled;

	public function getSystemConsoleEnabled(){
		return $this->systemConsoleEnabled;
	}
	public function setSystemConsoleEnabled($enable){
		$this->systemConsoleEnabled = $enable;
	}

	// Dependency injection

	private $systemConsoleS;
	public function setSystemConsoleService($systemConsoleService){
		$this->systemConsoleS = $systemConsoleService;
	}
	protected function getSystemConsoleService(){
		// autowired
		if(!isset($this->systemConsoleS)){
			$this->systemConsoleS = ServiceProvider::getSystemConsoleService();
		}
		return $this->systemConsoleS;
	}

	private $executionService;
	public function setExecutionService($executionService){
		$this->executionService = $executionService;
	}
	protected function getExecutionService(){
		// autowired
		if(!isset($this->executionService)){
			$this->executionService = ServiceProvider::getExecutionService();
		}
		return $this->executionService;
	}
	
	protected function getMessageRecursive($exception){
		if($exception == null) return;
		$returnValue = $exception->getMessage();
		if($returnValue != null) {
			$returnValue = '->'.$exception->getCode().":".$returnValue." ";
		} else {
			$returnValue .= '->';
		}
		if(is_a($exception, "ServiceException")){
			$returnValue .= $this->getMessageRecursive($exception->getPreviousException());
		}
		return $returnValue;
	}
	protected function getTraceRecursive($exception){
		if($exception == null) return;
		$returnValue = $exception->getTraceAsString();
		if($returnValue != null) {
			$returnValue = "\n".'->'.$exception->getCode().": ".$exception->getMessage().":\n".$returnValue." ";
		} else {
			$returnValue .= "\n".'->'."\n";
		}
		if(is_a($exception, "ServiceException")){
			$returnValue .= $this->getTraceRecursive($exception->getPreviousException());
		}
		return $returnValue;
	}
	/**
	 * Does the actual publication work
	 */
	protected function doPublish($exception)
	{
		$errCode = $exception->getCode();
		$errMsg = "(".$this->getMessageRecursive($exception).")"; //." ".utf8_encode(str_replace("\n", "",$exception->getTraceAsString()));
		
		//Exception trace added to Log.txt
		//fput($exception->getCode()." : ".$errMsg."\n".$exception->getTraceAsString());
		
		
		if($this->getSystemConsoleEnabled()){
			//$moreDetail = $errCode.": ".$exception->getMessage()." in ".$exception->getFile()." at line ".$exception->getLine()."\n$errMsg\n--TRACE--:\n".$exception->getTraceAsString();
			//$wigiiPath = str_replace("www\\index.php", "", $_SERVER["SCRIPT_FILENAME"]);
			$moreDetail = "--TRACE--:".$this->getTraceRecursive($exception);
			$errMsg .= "\\n\\n".str_replace("<br>", "\\n", str_replace('\\', '/', str_replace("\n", "<br>", $moreDetail)));
		} else if(false) {
			fput($errMsg."\n\n--TRACE--:".$this->getTraceRecursive($exception));
		}
		
		$this->getExecutionService()->addJsCode("alert('A technical error has occured.\\n\\nSorry for the inconvenience.\\n\\nPlease contact your administrator with the following description:\\n".str_replace(array("'", "//", "\n"), array("\'", "\\/\\/", "\\n"), $errMsg)."');");
		
		if($this->getSystemConsoleEnabled()){
			$this->getSystemConsoleService()->storeMessage("Exception", "", $moreDetail);
		}
		
		// signals fatal error to monitoring system
		ServiceProvider::getClientAdminService()->signalFatalError($exception);
	}
}


