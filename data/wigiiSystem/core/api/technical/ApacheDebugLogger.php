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
 * DebugLogger which integrates Apache Log4Php (http://logging.apache.org/log4php)
 * Created by CWE on 12 juillet 2013
 */
class ApacheDebugLogger extends DebugLogger
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
		return $this->apacheLogger->isDebugEnabled();
	}
	public function setEnabled($enabled)
	{
		// enabled if logLevel <= debug
		if($enabled && !$this->apacheLogger->isDebugEnabled()) {
			$this->apacheLogger->setLevel(LoggerLevel::getLevelDebug());
		}
		// disabled if logLevel > debug
		elseif(!$enabled && $this->apacheLogger->isDebugEnabled()) {
			$this->apacheLogger->setLevel(LoggerLevel::getLevelInfo());
		}
	}
	
	// Implementation
	
	/**
	 * Does the actual job of writing a debug message
	 */
	protected function writeMessage($message)
	{
		$this->apacheLogger->debug($message);
	}
}

