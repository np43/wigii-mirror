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
 * An ExecutionSink which integrates Apache Log4Php (http://logging.apache.org/log4php)
 * Created by CWE on 12 juillet 2013
 */
class ApacheExecutionSink extends ExecutionSink
{
	private $apacheLogger;
	
	// Object lifecycle
	
	public function __construct($typeName)
	{
		parent::__construct($typeName);
		$this->apacheLogger = Logger::getLogger($typeName);		
	}
	
	// Configuration
	
	public function isEnabled()
	{
		return $this->apacheLogger->isInfoEnabled();
	}
	public function setEnabled($enabled)
	{
		// enabled if logLevel <= info
		if($enabled && !$this->apacheLogger->isInfoEnabled()) {
			$this->apacheLogger->setLevel(LoggerLevel::getLevelInfo());
		}
		// disabled if logLevel > info
		elseif(!$enabled && $this->apacheLogger->isInfoEnabled()) {
			$this->apacheLogger->setLevel(LoggerLevel::getLevelError());
		}
	}
	
	// Implementation
	
	protected function writeMessage($message)
	{
		$this->apacheLogger->info($message);
	}
}




