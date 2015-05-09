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

/* wigii ClientAdminService implementation which integrates with wigii web site
 * Created by CWE on 10 janvier 10
 */
class ClientAdminServiceWebImpl extends ClientAdminServiceImpl
{
	private $_debugLogger;
	private $_executionSink;

	private function debugLogger()
	{
		if(!isset($this->_debugLogger))
		{
			$this->_debugLogger = DebugLogger::getInstance("ClientAdminServiceWebImpl");
		}
		return $this->_debugLogger;
	}
	private function executionSink()
	{
		if(!isset($this->_executionSink))
		{
			$this->_executionSink = ExecutionSink::getInstance("ClientAdminServiceWebImpl");
		}
		return $this->_executionSink;
	}
	public function __construct()
	{
		$this->debugLogger()->write("creating instance");
	}

	// service implementation

	/**
	 * This implementation returns WIGII CLIENT
	 */
	public function getDefaultClient()
	{
		$this->executionSink()->publishStartOperation("getDefaultClient");
		try
		{
			$returnValue = $this->getCachedClient(CLIENT_NAME);
			if(!isset($returnValue))
			{
				$returnValue = Client::createInstance();
				$returnValue->setClientName(CLIENT_NAME);
				$this->cacheClient($returnValue);
			}
		}
		catch(Exception $e)
		{
			$this->executionSink()->publishEndOperationOnError("getDefaultClient", $e);
			throw new ClientAdminServiceException('',ClientAdminServiceException::WRAPPING, $e);
		}
		$this->executionSink()->publishEndOperation("getDefaultClient");
		return $returnValue;
	}
}


