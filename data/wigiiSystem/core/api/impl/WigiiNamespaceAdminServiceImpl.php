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
 * Wigii WigiiNamespaceAdminService implementation
 * Created by CWE on 12 juin 09
 * Modified by CWE on 23 mars 14 to add Wigii Setup functions
 */
class WigiiNamespaceAdminServiceImpl implements WigiiNamespaceAdminService
{
	private $_debugLogger;
	private $_executionSink;
	private $clientAS;
	private $authoS;

	/**
	 * wigiiNamespace cache
	 * map clientName -> map wigiiNamespaceName -> WigiiNamespace
	 */
	private $wigiiNamespaceCache;


	private function debugLogger()
	{
		if(!isset($this->_debugLogger))
		{
			$this->_debugLogger = DebugLogger::getInstance("WigiiNamespaceAdminServiceImpl");
		}
		return $this->_debugLogger;
	}
	private function executionSink()
	{
		if(!isset($this->_executionSink))
		{
			$this->_executionSink = ExecutionSink::getInstance("WigiiNamespaceAdminServiceImpl");
		}
		return $this->_executionSink;
	}
	public function __construct()
	{
		$this->debugLogger()->write("creating instance");
	}


	// dependency injection

	public function setClientAdminService($clientAdminService)
	{
		$this->clientAS = $clientAdminService;
	}
	protected function getClientAdminService()
	{
		// autowired
		if(!isset($this->clientAS))
		{
			$this->clientAS = ServiceProvider::getClientAdminService();
		}
		return $this->clientAS;
	}

	public function setAuthorizationService($authorizationService)
	{
		$this->authoS = $authorizationService;
	}
	protected function getAuthorizationService()
	{
		// autowired
		if(!isset($this->authoS))
		{
			$this->authoS = ServiceProvider::getAuthorizationService();
		}
		return $this->authoS;
	}

	// service implementation

	public function getWigiiNamespace($principal, $wigiiNamespaceName)
	{
		$this->executionSink()->publishStartOperation("getWigiiNamespace", $principal);
		try
		{
			if(is_null($principal)) throw new WigiiNamespaceAdminServiceException('principal can not be null', WigiiNamespaceAdminServiceException::INVALID_ARGUMENT);
			$returnValue = $this->doGetWigiiNamespaceForClient($principal, $wigiiNamespaceName, $principal->getWigiiNamespace()->getClient());
		}
		catch(WigiiNamespaceAdminServiceException $nae)
		{
			$this->executionSink()->publishEndOperationOnError("getWigiiNamespace", $nae, $principal);
			throw $nae;
		}
		catch(Exception $e)
		{
			$this->executionSink()->publishEndOperationOnError("getWigiiNamespace", $e, $principal);
			throw new WigiiNamespaceAdminServiceException('',WigiiNamespaceAdminServiceException::WRAPPING, $e);
		}
		$this->executionSink()->publishEndOperation("getWigiiNamespace", $principal);
		return $returnValue;
	}
	public function getWigiiNamespaceForClient($principal, $wigiiNamespaceName, $client)
	{
		$this->executionSink()->publishStartOperation("getWigiiNamespaceForClient", $principal);
		try
		{
			// checks authorizations
			$this->assertPrincipalAuthorizedForGetWigiiNamespaceForClient($principal, $wigiiNamespaceName, $client);
			// ok to fetch wigiiNamespace for client
			$returnValue = $this->doGetWigiiNamespaceForClient($principal, $wigiiNamespaceName, $client);
		}
		catch(WigiiNamespaceAdminServiceException $nae)
		{
			$this->executionSink()->publishEndOperationOnError("getWigiiNamespaceForClient", $nae, $principal);
			throw $nae;
		}
		catch(Exception $e)
		{
			$this->executionSink()->publishEndOperationOnError("getWigiiNamespaceForClient", $e, $principal);
			throw new WigiiNamespaceAdminServiceException('',WigiiNamespaceAdminServiceException::WRAPPING, $e);
		}
		$this->executionSink()->publishEndOperation("getWigiiNamespaceForClient", $principal);
		return $returnValue;
	}
	/**
	 * authorized if principal's client is equal to client
	 * or if principal is authentication service
	 */
	protected function assertPrincipalAuthorizedForGetWigiiNamespaceForClient($principal, $wigiiNamespaceName, $client)
	{
		if(is_null($principal)) throw new WigiiNamespaceAdminServiceException('principal can not be null', WigiiNamespaceAdminServiceException::INVALID_ARGUMENT);
		if(is_null($client)) throw new WigiiNamespaceAdminServiceException('client can not be null', WigiiNamespaceAdminServiceException::INVALID_ARGUMENT);
		if($principal->getWigiiNamespace()->getClient() !== $client)
		{
			if(!$this->getAuthorizationService()->isPrincipalAuthenticationService($principal))
			{
				$this->getAuthorizationService()->fail($principal, 'has not access to client '.$client->getClientName());
			}
		}
	}

	public function getEmptyWigiiNamespaceForClient($principal, $client)
	{
		$this->executionSink()->publishStartOperation("getEmptyWigiiNamespaceForClient", $principal);
		try
		{
			$returnValue = $this->doGetWigiiNamespaceForClient($principal, $this->getEmptyWigiiNamespaceName(), $client);
		}
		catch(WigiiNamespaceAdminServiceException $nae)
		{
			$this->executionSink()->publishEndOperationOnError("getWigiiNamespaceForClient", $nae, $principal);
			throw $nae;
		}
		catch(Exception $e)
		{
			$this->executionSink()->publishEndOperationOnError("getEmptyWigiiNamespaceForClient", $e, $principal);
			throw new WigiiNamespaceAdminServiceException('',WigiiNamespaceAdminServiceException::WRAPPING, $e);
		}
		$this->executionSink()->publishEndOperation("getEmptyWigiiNamespaceForClient", $principal);
		return $returnValue;
	}

	public function getEmptyWigiiNamespaceForEmptyClient()
	{
		$this->executionSink()->publishStartOperation("getEmptyWigiiNamespaceForEmptyClient");
		try
		{
			$returnValue = $this->getCachedWigiiNamespace($this->getClientAdminService()->getEmptyClient(), $this->getEmptyWigiiNamespaceName());
			if(!isset($returnValue))
			{
				$returnValue = $this->createEmptyWigiiNamespaceInstanceForEmptyClient();
				$this->cacheWigiiNamespace($returnValue);
			}
			return $returnValue;
		}
		catch(Exception $e)
		{
			$this->executionSink()->publishEndOperationOnError("getEmptyWigiiNamespaceForEmptyClient", $e);
			throw new WigiiNamespaceAdminServiceException('',WigiiNamespaceAdminServiceException::WRAPPING, $e);
		}
		$this->executionSink()->publishEndOperation("getEmptyWigiiNamespaceForEmptyClient");
		return $returnValue;
	}

	public function getEmptyWigiiNamespaceForDefaultClient()
	{
		$this->executionSink()->publishStartOperation("getEmptyWigiiNamespaceForDefaultClient");
		try
		{
			$returnValue = $this->getCachedWigiiNamespace($this->getClientAdminService()->getDefaultClient(), $this->getEmptyWigiiNamespaceName());
			if(!isset($returnValue))
			{
				$returnValue = $this->createEmptyWigiiNamespaceInstanceForDefaultClient();
				$this->cacheWigiiNamespace($returnValue);
			}
			return $returnValue;
		}
		catch(Exception $e)
		{
			$this->executionSink()->publishEndOperationOnError("getEmptyWigiiNamespaceForDefaultClient", $e);
			throw new WigiiNamespaceAdminServiceException('',WigiiNamespaceAdminServiceException::WRAPPING, $e);
		}
		$this->executionSink()->publishEndOperation("getEmptyWigiiNamespaceForDefaultClient");
		return $returnValue;
	}

	/**
	 * This implementation returns the 'Setup' wigii namespace
	 */
	public function getSetupWigiiNamespace($principal) {
		return $this->getWigiiNamespace($principal, 'Setup');
	}

	/**
	 * creates a wigiiNamespace instance with the given name and attached to the specified client
	 * extension point to use when wanting to create an instance based on a serialized object.
	 * returns a WigiiNamespace instance
	 */
	protected function createWigiiNamespaceInstanceForClient($principal, $wigiiNamespaceName, $client)
	{
		$returnValue = WigiiNamespace::createInstance($client);
		$returnValue->setWigiiNamespaceName($wigiiNamespaceName);
		return $returnValue;
	}
	protected function createEmptyWigiiNamespaceInstanceForEmptyClient()
	{
		$returnValue = WigiiNamespace::createInstance($this->getClientAdminService()->getEmptyClient());
		$returnValue->setWigiiNamespaceName($this->getEmptyWigiiNamespaceName());
		return $returnValue;
	}
	protected function createEmptyWigiiNamespaceInstanceForDefaultClient()
	{
		$returnValue = WigiiNamespace::createInstance($this->getClientAdminService()->getDefaultClient());
		$returnValue->setWigiiNamespaceName($this->getEmptyWigiiNamespaceName());
		return $returnValue;
	}

	/**
	 * Returns the name to use for the empty wigiiNamespace.
	 * This implementation returns ''
	 */
	protected function getEmptyWigiiNamespaceName()
	{
		return WigiiNamespace::EMPTY_NAMESPACE_NAME;
	}

	// Cache management

	private function getCachedWigiiNamespace($client, $wigiiNamespaceName)
	{
		if(!isset($this->wigiiNamespaceCache)) return null;
		$clientWigiiNamespaces = $this->wigiiNamespaceCache[$client->getClientName()];
		if(!isset($clientWigiiNamespaces)) return null;
		$returnValue = $clientWigiiNamespaces[$wigiiNamespaceName];
		if(!isset($returnValue))
		{
			$this->debugLogger()->write("$wigiiNamespaceName not found in cache");
			return null;
		}
		return $returnValue;
	}

	private function cacheWigiiNamespace($wigiiNamespace)
	{
		if(is_null($wigiiNamespace)) return;
		if(!isset($this->wigiiNamespaceCache)) $this->wigiiNamespaceCache = array();
		$clientName = $wigiiNamespace->getClient()->getClientName();
		$clientWigiiNamespaces = $this->wigiiNamespaceCache[$clientName];
		$clientWigiiNamespaces[$wigiiNamespace->getWigiiNamespaceName()] = $wigiiNamespace;
		$this->wigiiNamespaceCache[$clientName] = $clientWigiiNamespaces;
		$this->debugLogger()->write('stores wigiiNamespace');
	}

	// Utils

	private function doGetWigiiNamespaceForClient($principal, $wigiiNamespaceName, $client)
	{
		//if($wigiiNamespaceName == WigiiNamespace::EMPTY_NAMESPACE_URL) $wigiiNamespaceName = null;
		$returnValue = $this->getCachedWigiiNamespace($client, $wigiiNamespaceName);
		if(!isset($returnValue))
		{
			$returnValue = $this->createWigiiNamespaceInstanceForClient($principal, $wigiiNamespaceName, $client);
			$this->cacheWigiiNamespace($returnValue);
		}
		return $returnValue;
	}
}


