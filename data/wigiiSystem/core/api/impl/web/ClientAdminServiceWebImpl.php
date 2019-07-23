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

/**
 * wigii ClientAdminService implementation which integrates with wigii web site
 * Created by CWE on 10 janvier 10
 */
class ClientAdminServiceWebImpl extends ClientAdminServiceImpl
{
	private $_debugLogger;
	private $_executionSink;

	private function debugLogger()
	{
		if(!isset($this->_debugLogger))
		{
			$this->_debugLogger = DebugLogger::getInstance("ClientAdminServiceWebImpl");
		}
		return $this->_debugLogger;
	}
	private function executionSink()
	{
		if(!isset($this->_executionSink))
		{
			$this->_executionSink = ExecutionSink::getInstance("ClientAdminServiceWebImpl");
		}
		return $this->_executionSink;
	}
	public function __construct()
	{
		$this->debugLogger()->write("creating instance");
	}

	// service implementation

	/**
	 * This implementation returns WIGII CLIENT
	 */
	public function getDefaultClient()
	{
		$this->executionSink()->publishStartOperation("getDefaultClient");
		try
		{
			$returnValue = $this->getCachedClient(CLIENT_NAME);
			if(!isset($returnValue))
			{
				$returnValue = Client::createInstance();
				$returnValue->setClientName(CLIENT_NAME);
				$this->cacheClient($returnValue);
			}
		}
		catch(Exception $e)
		{
			$this->executionSink()->publishEndOperationOnError("getDefaultClient", $e);
			throw new ClientAdminServiceException('',ClientAdminServiceException::WRAPPING, $e);
		}
		$this->executionSink()->publishEndOperation("getDefaultClient");
		return $returnValue;
	}
	
	// Wigii monitoring functions
	
	/**
	 * Gathers Execution context information and returns an object or a string
	 * @param WigiiBPLParameter $options a set of options conditionning the set of information gathered regarding the current execution context :
	 * - outputFormat: one of 'string','stdClass'. Defaults to string.
	 * @return string|stdClass an object (or its serialized version) containing the following entries :
	 * - date : event date in YYYY-mm-dd hh:mm:ss:u format
	 * - request: sub-url
	 * - wigiiNamespace: current namespace
	 * - module: current module
	 * - action: current action
	 * - realUsername : real user name
	 * - username : role name
	 * - principalNamespace : principal current namespace
	 * - clientIP: remote client IP address
	 * - referer: remote client calling url
	 * - version: Wigii system version label
	 */
	public function gatherExecutionContextInfo($options=null) {
		if(!isset($options)) $options=wigiiBPLParam();
		if(!$options->getValue('outputFormat')) $options->setValue('outputFormat', 'string');
		$returnValue=parent::gatherExecutionContextInfo(wigiiBPLParam('outputFormat','stdClass'));
		// gathers execution context info
		$exec = ServiceProvider::getExecutionService();
		$returnValue->{'clientIP'} = $_SERVER["REMOTE_ADDR"].':'.$_SERVER["REMOTE_PORT"];
		$returnValue->{'referer'} = $_SERVER["HTTP_REFERER"];
		$returnValue->{'request'} = $exec->getCrtRequest();
		$returnValue->{'wigiiNamespace'} = $exec->getCrtWigiiNamespace()->getWigiiNamespaceUrl();
		$returnValue->{'module'} = $exec->getCrtModule()->getModuleUrl();
		$returnValue->{'action'} = $exec->getCrtAction();
		$returnValue->{'version'} = VERSION_LABEL;
		// serializes output
		if($options->getValue('outputFormat')=='string') {
			$s="EXECUTION CONTEXT:\n\n";
			foreach($returnValue as $k=>$v) {
				$s.=$k.': '.$v."\n";
			}
			$returnValue=$s;
		}
		return $returnValue;
	}	
	
	// Wigii signals
		
	public function signalFatalError($exception) {
		if($this->isMonitoringEnabled('monitorFatalError')) {
			try {
				$this->getFatalErrorsLogger()->info($this->gatherExecutionContextInfo()."\n".
													$this->gatherExceptionInfo($exception));
			}
			catch(Exception $e) {/* silent to avoid loops */}
		}
	}
	private $fatalErrorsLogger;
	/**
	 * Opens a specialized Apache Logger to dump fatal errors signals.	 
	 * @return Logger level INFO 
	 */
	protected function getFatalErrorsLogger() {
		if(!isset($this->fatalErrorsLogger)) {
			$this->fatalErrorsLogger = Logger::getLogger('ClientAdminServiceImpl_FatalErrors');			
			$appender=$this->fatalErrorsLogger->getAppender('ClientErrors');
			if(!isset($appender)) {
				$appender=$this->createFatalErrorsAppender();
				$this->fatalErrorsLogger->addAppender($appender);
			}
			$this->fatalErrorsLogger->setAdditivity(false);
			$this->fatalErrorsLogger->setLevel(LoggerLevel::getLevelInfo());			
		}
		return $this->fatalErrorsLogger;
	}
	/**
	 * Opens a rolling file in LOG_PATH/errors/Client_errors.log
	 * @return LoggerAppender
	 */
	protected function createFatalErrorsAppender() {
		$returnValue=new LoggerAppenderRollingFile('ClientErrors');
		// configures rolling file
		$returnValue->setFile(dirname($_SERVER["SCRIPT_FILENAME"]).'/'.LOG_PATH.'errors/'.CLIENT_NAME.'_errors.log');
		$returnValue->setMaxBackupIndex(5);
		$returnValue->setMaxFileSize('2MB');
		// configures layout
		$layout=new LoggerLayoutPattern();
		$layout->setConversionPattern('-----------------------------------------------------%newline%newline%message%newline%newline-----------------------------------------------------%newline');
		$layout->activateOptions();
		$returnValue->setLayout($layout);
		$returnValue->activateOptions();
		return $returnValue;
	}
}


