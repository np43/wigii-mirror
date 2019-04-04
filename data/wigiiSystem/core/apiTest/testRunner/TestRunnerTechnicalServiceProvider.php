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
 * wigii system test runner technical service provider
 * Created by CWE on 18 juin 09
 */
class TestRunnerTechnicalServiceProvider extends TechnicalServiceProvider
{
	public static function start()
	{
		if(!parent::isUp())
		{
			parent::registerSingleInstance(new TestRunnerTechnicalServiceProvider());
		}
	}

	/**
	 * Do not use directly this function, but rather call TestRunner::test method
	 */
	public static function getTestRunner()
	{
		return self::getInstance()->getTestRunnerInstance();
	}
	public static function pushRootPrincipalToTestRunner()
	{
		self::getTestRunner()->setRootPrincipal(self::getInstance()->getRootPrincipal());
	}

	private $testRunner;

	/**
	 * default singleton
	 */
	protected function getTestRunnerInstance()
	{
		if(!isset($this->testRunner))
		{
			$this->testRunner = $this->createTestRunnerInstance();
		}
		return $this->testRunner;
	}

	/**
	 * default as TestRunner
	 */
	protected function createTestRunnerInstance()
	{
		return new TestRunner();
	}

	protected function createDebugLoggerInstance($typeName)
	{
		return new TestRunnerDebugLogger($typeName);
	}
	protected function createExecutionSinkInstance($typeName)
	{
		return new TestRunnerExecutionSink($typeName);
	}
	protected function createExceptionSinkInstance()
	{
		return new TestRunnerExceptionSink();
	}
}



