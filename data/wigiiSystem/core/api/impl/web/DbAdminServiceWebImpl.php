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

/* wigii DbAdminService implementation which integrates with wigii web site
 * Created by CWE on 25 juin 09
 */
class DbAdminServiceWebImpl extends DbAdminServiceImpl
{
	private $_debugLogger;
	private $_executionSink;

	private function debugLogger()
	{
		if(!isset($this->_debugLogger))
		{
			$this->_debugLogger = DebugLogger::getInstance("DbAdminServiceWebImpl");
		}
		return $this->_debugLogger;
	}
	private function executionSink()
	{
		if(!isset($this->_executionSink))
		{
			$this->_executionSink = ExecutionSink::getInstance("DbAdminServiceWebImpl");
		}
		return $this->_executionSink;
	}
	public function __construct()
	{
		$this->debugLogger()->write("creating instance");
	}

	/**
	 * creates a DbConnectionSettings instance for the given client
	 * returns a DbConnectionSettings instance
	 */
	protected function createDbConnectionSettingsInstance($principal, $client)
	{
		if($client == null) throw new DbAdminServiceException("try to create a DB Connection settings an empty client.", DbAdminServiceException::INVALID_ARGUMENT);
		//in this implementation the DB connection settings are linked with
		//the CLIENT_NAME const, because this const define the const we use
		if($client->getClientName() != CLIENT_NAME){
			throw new DbAdminServiceException("try to create a DB Connection settings for the client:".$client->getClientName()." and it is different than the constant client:".CLIENT_NAME, DbAdminServiceException::INVALID_ARGUMENT);
		}
		$returnValue = DbConnectionSettings::createInstance();
		$returnValue->setHostName(DB_HOST);
		$returnValue->setUsername(DB_USER);
		$returnValue->setPassword(DB_PWD);
		$returnValue->setDbName(DB_NAME);

		return $returnValue;
	}

	/**
	 * Returns lock expiration in microsecondes
	 * equals to session timeout
	 */
	private $sessionLifeTime;
	public function getLockExpirationMicrosec()
	{
		if(!isset($this->sessionLifeTime)){
			//maximum 2h of lock
			$this->sessionLifeTime = min(ini_get("session.gc_maxlifetime")*1000000, 120*60*1000000);
			//eput($this->sessionLifeTime);
		}
		return $this->sessionLifeTime;
	}
}
