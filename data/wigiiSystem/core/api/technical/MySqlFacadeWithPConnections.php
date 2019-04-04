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
 * MySql database technical facade with persistent connections
 * Created by CWE on 26 Feb 2013
 * Modified by Medair (ACA) on August 10th 2016 to use mysqli driver instead of deprecated mysql. 
 */
class MySqlFacadeWithPConnections extends MySqlFacade
{
	private $_debugLogger;
	private $_executionSink;
	private $connectionWaitTimeout;	

	private function debugLogger()
	{
		if(!isset($this->_debugLogger))
		{
			$this->_debugLogger = DebugLogger::getInstance("MySqlFacadeWithPConnections");
		}
		return $this->_debugLogger;
	}
	private function executionSink()
	{
		if(!isset($this->_executionSink))
		{
			$this->_executionSink = ExecutionSink::getInstance("MySqlFacadeWithPConnections");
		}
		return $this->_executionSink;
	}

	
	/**
	 * Sets connection wait timeout
	 */
	public function setConnectionWaitTimeout($connectionWaitTimeout) {
		$this->connectionWaitTimeout = $connectionWaitTimeout;
	}
	/**
	 * Returns connection wait timeout
	 */
	protected function getConnectionWaitTimeout() {
		if(!isset($this->connectionWaitTimeout)) $this->connectionWaitTimeout = 600;
		return $this->connectionWaitTimeout;
	}
	
	/**
	 * Connects to mysql server
	 */
	protected function connect($cnxSettings)
	{
		$returnValue = $this->getCachedDbConnection($cnxSettings);
		if(!isset($returnValue))
		{			
			$returnValue = @mysqli_connect('p:'.$cnxSettings->getHost(), $cnxSettings->getUsername(), $cnxSettings->getPassword());
			if(!$returnValue) throw new MySqlFacadeException("Connection to database with: ".$cnxSettings->displayDebug()." failed.", MySqlFacadeException::INVALID_ARGUMENT);
			$this->debugLogger()->write("Connected using persitent connections");
			if(!mysqli_select_db($returnValue, $cnxSettings->getDbName())){
				throw new MySqlFacadeException("No database: ".$cnxSettings->getDbName()." on host: ".$cnxSettings->getHost(), MySqlFacadeException::INVALID_ARGUMENT);
			}
			//set the charset of the DB, default utf8mb4 (full UTF8)
			if (!defined("DB_CHARSET")) define ("DB_CHARSET", "utf8mb4");
			mysqli_query($returnValue, "SET NAMES ".DB_CHARSET.";");
			mysqli_query($returnValue, "SET CHARACTER SET ".DB_CHARSET.";");
			mysqli_query($returnValue, "SET SESSION wait_timeout = $this->connectionWaitTimeout");
			$this->cacheDbConnection($cnxSettings, $returnValue);
		}
		return $returnValue;
	}
}
