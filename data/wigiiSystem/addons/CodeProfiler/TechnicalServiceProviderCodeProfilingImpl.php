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

/* TechnicalServiceProvider implementation for performance monitoring
 * Created by CWE on 8.2.2013
 */
class TechnicalServiceProviderCodeProfilingImpl extends TechnicalServiceProviderWebImpl
{	
	/**
	 * Creates new instance and registers itself in API.
	 */
	public static function start($profilerDbConnectionSettings, $enableExecutionSink=false, $enableDebugLogger=false, $enableGlobalStatistic=true, $enableElementStatistic = true, $enableFileStatistic= true)
	{
		$instance = new TechnicalServiceProviderCodeProfilingImpl();		
		parent::registerSingleInstance($instance);
		$instance->setEnableExecutionSink($enableExecutionSink);
		$instance->setEnableDebugLogger($enableDebugLogger);
		$instance->setEnableGlobalStatistic($enableGlobalStatistic);
		$instance->setEnableElementStatistic($enableElementStatistic);
		$instance->setEnableFileStatistic($enableFileStatistic);	
		$instance->setCodeProfilerDbConnectionSettings($profilerDbConnectionSettings);
		self::useAdditionalExecutionSinks('ExecutionSinkCodeProfilingImpl');			
	}

	private $codeProfilerDbConnectionSettings;
	public function setCodeProfilerDbConnectionSettings($profilerDbConnectionSettings)
	{		
		$this->codeProfilerDbConnectionSettings = $profilerDbConnectionSettings;		
	}
	public static function getCodeProfilerDbConnectionSettings()
	{		
		return self::getInstance()->codeProfilerDbConnectionSettings;
	}
		
	protected function createMySqlFacadeInstance()
	{
		$mySqlF = new MySqlFacadeCodeProfilingImpl();
		//$mySqlF->setConnectionWaitTimeout(600);
		return $mySqlF;
	}
	
	private $codeProfiler;

	public static function getCodeProfiler()
	{
		return self::getInstance()->getCodeProfilerInstance();
	}

	/**
	 * default singleton
	 */
	protected function getCodeProfilerInstance()
	{
		if(!isset($this->codeProfiler))
		{
			$this->codeProfiler = $this->createCodeProfilerInstance();
		}
		return $this->codeProfiler;
	}

	/**
	 * default as CodeProfilerImpl
	 */
	protected function createCodeProfilerInstance()
	{
		$returnValue = new CodeProfilerImpl();
		$returnValue->setExecutionStackFilter(ExecutionStackFilter::createInstance());
		return $returnValue;
	}
}



