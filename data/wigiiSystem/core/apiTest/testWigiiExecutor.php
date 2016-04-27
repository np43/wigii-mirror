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

/*
 * Test infrastructure for WigiiExecutor and integrating TestRunner
 * Created by CWE on 30 juil. 09
 */


class testWigiiExecutorDebugLogger extends DebugLogger
{
	private $webImplDebugLogger;
	private $testRunnerDebugLogger;

	public function __construct($typeName, $webImplDebugLogger, $testRunnerDebugLogger)
	{
		parent::__construct($typeName);
		$this->setTestRunnerDebugLogger($testRunnerDebugLogger);
		$this->setWebImplDebugLogger($webImplDebugLogger);
	}

	// getter/setter

	protected function getWebImplDebugLogger()
	{
		return $this->webImplDebugLogger;
	}
	protected function setWebImplDebugLogger($webImplDebugLogger)
	{
		$this->webImplDebugLogger = $webImplDebugLogger;
	}
	protected function getTestRunnerDebugLogger()
	{
		return $this->testRunnerDebugLogger;
	}
	protected function setTestRunnerDebugLogger($testRunnerDebugLogger)
	{
		$this->testRunnerDebugLogger = $testRunnerDebugLogger;
	}

	// overrides service contract

	protected function writeMessage($message)
	{
		//$this->getWebImplDebugLogger()->writeMessage($message);
		$this->getTestRunnerDebugLogger()->writeMessage($message);
	}
}
class testWigiiExecutorExecutionSink extends ExecutionSink
{
	private $webImplExecutionSink;
	private $testRunnerExecutionSink;

	public function __construct($typeName, $webImplExecutionSink, $testRunnerExecutionSink)
	{
		parent::__construct($typeName);
		$this->setTestRunnerExecutionSink($testRunnerExecutionSink);
		$this->setWebImplExecutionSink($webImplExecutionSink);
	}

	// getter/setter

	protected function getWebImplExecutionSink()
	{
		return $this->webImplExecutionSink;
	}
	protected function setWebImplExecutionSink($webImplExecutionSink)
	{
		$this->webImplExecutionSink = $webImplExecutionSink;
	}
	protected function getTestRunnerExecutionSink()
	{
		return $this->testRunnerExecutionSink;
	}
	protected function setTestRunnerExecutionSink($testRunnerExecutionSink)
	{
		$this->testRunnerExecutionSink = $testRunnerExecutionSink;
	}

	// overrides service contract

	protected function writeMessage($message)
	{
		//$this->getWebImplExecutionSink()->writeMessage($message);
		$this->getTestRunnerExecutionSink()->writeMessage($message);
	}
}
class testWigiiExecutorExceptionSink extends ExceptionSink
{
	private $webImplExceptionSink;
	private $testRunnerExceptionSink;

	private $_debugLogger;

	private function debugLogger()
	{
		if(!isset($this->_debugLogger))
		{
			$this->_debugLogger = DebugLogger::getInstance("testWigiiExecutorExceptionSink");
		}
		return $this->_debugLogger;
	}

	public function __construct($webImplExceptionSink, $testRunnerExceptionSink)
	{
		$this->debugLogger()->write("create instance");
		$this->setTestRunnerExceptionSink($testRunnerExceptionSink);
		$this->setWebImplExceptionSink($webImplExceptionSink);
	}

	// getter/setter

	protected function getWebImplExceptionSink()
	{
		return $this->webImplExceptionSink;
	}
	protected function setWebImplExceptionSink($webImplExceptionSink)
	{
		$this->webImplExceptionSink = $webImplExceptionSink;
	}
	protected function getTestRunnerExceptionSink()
	{
		return $this->testRunnerExceptionSink;
	}
	protected function setTestRunnerExceptionSink($testRunnerExceptionSink)
	{
		$this->testRunnerExceptionSink = $testRunnerExceptionSink;
	}

	// overrides service contract

	protected function doPublish($exception)
	{
		//$this->debugLogger()->logBeginOperation("doPublish");
		//$this->getWebImplExceptionSink()->doPublish($exception);
		$this->getTestRunnerExceptionSink()->doPublish($exception);
		//$this->debugLogger()->logEndOperation("doPublish");
	}
}
class testWigiiExecutorTechnicalServiceProvider extends TestRunnerTechnicalServiceProvider
{
	public static function start()
	{
		$instance = new testWigiiExecutorTechnicalServiceProvider();
		parent::registerSingleInstance($instance);
	}

	protected function createExceptionSinkInstance()
	{
		$expsW = new ExceptionSinkWebImpl();
		$expsW->setSystemConsoleEnabled(true);
		$exps = new testWigiiExecutorExceptionSink($expsW,
			 	parent::createExceptionSinkInstance());
		return $exps;
	}
	protected function createExecutionSinkInstance($typeName)
	{
		$exs = new testWigiiExecutorExecutionSink($typeName,
				new ExecutionSinkWebImpl($typeName),
			 	parent::createExecutionSinkInstance($typeName));
		return $exs;
	}
	protected function createDebugLoggerInstance($typeName)
	{
		$dbl = new testWigiiExecutorDebugLogger($typeName,
				new DebugLoggerWebImpl($typeName),
			 	parent::createDebugLoggerInstance($typeName));
		return $dbl;
	}
}
class testWigiiExecutorServiceProvider extends ServiceProviderWebImpl
{
	private $_debugLogger;

	private function debugLogger()
	{
		if(!isset($this->_debugLogger))
		{
			$this->_debugLogger = DebugLogger::getInstance("testWigiiExecutorServiceProvider");
		}
		return $this->_debugLogger;
	}

	public static function start()
	{
		$instance = new testWigiiExecutorServiceProvider();
		parent::registerSingleInstance($instance);

		$instance->setSessionCacheExecEnabled(true);
		$instance->setSessionCacheBuildEnabled(true);
	}
	
	protected function createConfigServiceInstance()
	{
		$returnValue = parent::createConfigServiceInstance();
		$this->debugLogger()->write('sets configuration root folder to '.CORE_PATH.'apiTest/configs');
		$returnValue->setConfigFolderPath(CORE_PATH."apiTest/configs");
		return $returnValue;
	}
	
	protected function createAuthenticationServiceInstance($principalList = null)
	{
		$authS = new AuthenticationServiceWebImpl(CLIENT_NAME, $principalList);
		$authS->setSessionCacheEnabled($this->isSessionCacheExecEnabled());
		return $authS;
	}
	
	protected function createElementServiceInstance()
	{
		$els = new ElementServiceWebImpl();
		return $els;
	}
	
	protected function createModuleAdminServiceInstance()
	{
		$s = new ModuleAdminServiceWebImpl();
		return $s;
	}
}


/**
 * testWigiiExecutor
 */
class testWigiiExecutor extends WigiiExecutor
{
	public static function start()
	{
		$instance = new testWigiiExecutor();
		parent::registerSingleInstance($instance);
		$instance->doStart();
		return $instance;
	}

	protected function startTechnicalServiceProvider()
	{
		testWigiiExecutorTechnicalServiceProvider::start();
	}
	protected function startServiceProvider()
	{
		testWigiiExecutorServiceProvider::start();
	}
	protected function executeAction($exec)
	{
		// does nothing.
	}
}