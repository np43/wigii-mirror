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
 * MySql database technical facade with persistent connections
 * Created by CWE on 26 Feb 2013
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
			$returnValue = @mysql_pconnect($cnxSettings->getHost(), $cnxSettings->getUsername(), $cnxSettings->getPassword());
			if(!$returnValue) throw new MySqlFacadeException("Connection to database with: ".$cnxSettings->displayDebug()." failed.", MySqlFacadeException::INVALID_ARGUMENT);
			if(!mysql_select_db($cnxSettings->getDbName(), $returnValue)){
				throw new MySqlFacadeException("No database: ".$cnxSettings->getDbName()." on host: ".$cnxSettings->getHost(), MySqlFacadeException::INVALID_ARGUMENT);
			}
			//set the charset to UTF8
			mysql_query("SET NAMES utf8;", $returnValue);
			mysql_query("SET CHARACTER SET utf8;", $returnValue);
			mysql_query("SET SESSION wait_timeout = $this->connectionWaitTimeout", $returnValue);
			$this->cacheDbConnection($cnxSettings, $returnValue);
		}
		return $returnValue;
	}
}
