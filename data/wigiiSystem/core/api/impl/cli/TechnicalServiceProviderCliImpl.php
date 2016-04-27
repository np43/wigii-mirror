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

/* TechnicalServiceProvider implementation which integrates with command line interpreter
 * Created by CWE on 8 juin 09
 */
class TechnicalServiceProviderCliImpl extends TechnicalServiceProvider
{
	/**
	 * Creates new instance and registers itself in API.
	 */
	public static function start()
	{
		$instance = new TechnicalServiceProviderCliImpl();
		parent::registerSingleInstance($instance);		
	}
	
	/**
	 * default as DebugLogger
	 */
	protected function createDebugLoggerInstance($typeName)
	{
		return new DebugLoggerCliImpl($typeName);
	}
	
	/**
	 * default as ExecutionSink
	 */
	protected function createExecutionSinkInstance($typeName)
	{
		return new ExecutionSinkCliImpl($typeName);
	}
	
	/**
	 * default as ExceptionSink
	 */
	protected function createExceptionSinkInstance()
	{
		return new ExceptionSinkCliImpl();
	}
}



