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
 * Created on 24 November 2011 by LWR
 * Modified by CWE on 30.04.2019 to include DevEnvBanner on localhost
 */

/**
 * ExampleWigiiExecutor
 */
class ExampleWigiiExecutor extends WigiiExecutor
{
	//dependency injection

	private $_debugLogger;
	private function debugLogger()
	{
		if(!isset($this->_debugLogger))
		{
			$this->_debugLogger = DebugLogger::getInstance("ExampleWigiiExecutor");
		}
		return $this->_debugLogger;
	}

	private $_executionSink;
	private function executionSink()
	{
		if(!isset($this->_executionSink))
		{
			$this->_executionSink = ExecutionSink::getInstance("ExampleWigiiExecutor");
		}
		return $this->_executionSink;
	}

	public static function start()
	{
		$instance = new ExampleWigiiExecutor();
		parent::registerSingleInstance($instance);
		$instance->doStart();
		return $instance;
	}

	protected function startServiceProvider()
	{
		ServiceProviderExampleImpl::start(SESSION_CACHE_EXEC_ENABLED, SESSION_CACHE_BUILD_ENABLED, SYS_CONSOLE_ENABLED, $this);
		$this->executionSink()->log("ServiceProviderExampleImpl started");
	}
	
	
	protected function executeAction($exec)
	{
		$p = ServiceProvider::getAuthenticationService()->getMainPrincipal();
		//WARNING, if make new entry point here, important to do a return in place of break
		//to prevent the parent to be execute if something match in the switch
		switch($exec->getCrtAction()){
		case "newExempleEntryPoint":
			//code of the newExempleEntryPoint
			return;
		}
		parent::executeAction($exec);
	}
	
	protected function includeTemplateCompanyBanner($p, $exec){
		if($_SERVER['HTTP_HOST']=='localhost' && file_exists(CLIENT_WEB_PATH . "DevEnvBanner.php")) include(CLIENT_WEB_PATH . "DevEnvBanner.php");
	}
}




