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

/* wigii service provider with restricted access
 * Access to a service is protected by authentication.
 * In this implementation, all services are accessible for a valid authenticated user.
 * Created by CWE on 14 juin 09
 */
class RestrictedServiceProvider
{
	// singleton implementation
	private static $singleton;

	private static function getInstance()
	{
		if(!isset(self::$singleton))
		{
			self::$singleton = new RestrictedServiceProvider();
		}
		return self::$singleton;
	}

	/**
	 * Registers a service provider subclass as current singleton instance
	 * throws AuthenticationServiceProviderException(UNAUTHORIZED) if principal is not valid.
	 * throws RestrictedServiceProviderException if an error occurs
	 */
	protected static function registerSingleInstance($serviceProvider)
	{
		self::$singleton = $serviceProvider;
	}

	// static service providing

	/**
	 * Returns an instance of wigii Authentication service
	 * throws RestrictedServiceProviderException if an error occurs
	 */
	public static function getAuthenticationService()
	{
		return RestrictedServiceProvider::getInstance()->getAuthenticationServiceInstance();
	}

	/**
	 * Returns an instance of wigii Config service
	 * principal: authenticated user performing the action
	 * throws AuthenticationServiceProviderException(UNAUTHORIZED) if principal is not valid.
	 * throws RestrictedServiceProviderException if an error occurs
	 */
	public static function getConfigService($principal)
	{
		return RestrictedServiceProvider::getInstance()->getConfigServiceInstance($principal);
	}

	/**
	 * Returns an instance of wigii Element service
	 * principal: authenticated user performing the action
	 * throws AuthenticationServiceProviderException(UNAUTHORIZED) if principal is not valid.
	 * throws RestrictedServiceProviderException if an error occurs
	 */
	public static function getElementService($principal)
	{
		return RestrictedServiceProvider::getInstance()->getElementServiceInstance($principal);
	}

	/**
	 * Returns an instance of wigii GroupAdmin service
	 * principal: authenticated user performing the action
	 * throws AuthenticationServiceProviderException(UNAUTHORIZED) if principal is not valid.
	 * throws RestrictedServiceProviderException if an error occurs
	 */
	public static function getGroupAdminService($principal)
	{
		return RestrictedServiceProvider::getInstance()->getGroupAdminServiceInstance($principal);
	}

	/**
	 * Returns an instance of wigii UserAdmin service
	 * principal: authenticated user performing the action
	 * throws AuthenticationServiceProviderException(UNAUTHORIZED) if principal is not valid.
	 * throws RestrictedServiceProviderException if an error occurs
	 */
	public static function getUserAdminService($principal)
	{
		return RestrictedServiceProvider::getInstance()->getUserAdminServiceInstance($principal);
	}

	/**
	 * Returns an instance of wigii WigiiNamespaceAdmin service
	 * principal: authenticated user performing the action
	 * throws AuthenticationServiceProviderException(UNAUTHORIZED) if principal is not valid.
	 * throws RestrictedServiceProviderException if an error occurs
	 */
	public static function getWigiiNamespaceAdminService($principal)
	{
		return RestrictedServiceProvider::getInstance()->getWigiiNamespaceAdminServiceInstance($principal);
	}

	/**
	 * Returns an instance of wigii ClientAdmin service
	 * principal: authenticated user performing the action
	 * throws AuthenticationServiceProviderException(UNAUTHORIZED) if principal is not valid.
	 * throws RestrictedServiceProviderException if an error occurs
	 */
	public static function getClientAdminService($principal)
	{
		return RestrictedServiceProvider::getInstance()->getClientAdminServiceInstance($principal);
	}

	/**
	 * Returns an instance of wigii ModuleAdmin service
	 * principal: authenticated user performing the action
	 * throws AuthenticationServiceProviderException(UNAUTHORIZED) if principal is not valid.
	 * throws RestrictedServiceProviderException if an error occurs
	 */
	public static function getModuleAdminService($principal)
	{
		return RestrictedServiceProvider::getInstance()->getModuleAdminServiceInstance($principal);
	}

	// service providing

	private $cachedAuthenticationService;

	/**
	 * default delegates to wigii service provider.
	 * The AuthenticationService is accessible for all users without authenticating.
	 * throws RestrictedServiceProviderException if an error occurs
	 */
	protected function getAuthenticationServiceInstance()
	{
		if(!isset($this->cachedAuthenticationService))
		{
			try
			{
				$this->cachedAuthenticationService = ServiceProvider::getAuthenticationService();
			}
			catch(Exception $e)
			{
				throw new RestrictedServiceProviderException('',RestrictedServiceProviderException::WRAPPING, $e);
			}
		}
		return $this->cachedAuthenticationService;
	}

	/**
	 * default delegates to wigii service provider after checking validity of principal
	 * throws AuthenticationServiceProviderException(UNAUTHORIZED) if principal is not valid.
	 * throws RestrictedServiceProviderException if an error occurs
	 */
	protected function getConfigServiceInstance($principal)
	{
		try
		{
			$this->getAuthenticationServiceInstance()->assertPrincipalValid($principal);
			return ServiceProvider::getConfigService();
		}
		catch(AuthenticationServiceException $ase)
		{
			if($ase->getCode() == AuthenticationServiceException::UNAUTHORIZED)
			{
				throw $ase;
			}
			else
			{
				throw new RestrictedServiceProviderException('',RestrictedServiceProviderException::WRAPPING, $ase);
			}
		}
		catch(Exception $e)
		{
			throw new RestrictedServiceProviderException('',RestrictedServiceProviderException::WRAPPING, $e);
		}
	}

	/**
	 * default delegates to wigii service provider after checking validity of principal
	 * throws AuthenticationServiceProviderException(UNAUTHORIZED) if principal is not valid.
	 * throws RestrictedServiceProviderException if an error occurs
	 */
	protected function getElementServiceInstance($principal)
	{
		try
		{
			$this->getAuthenticationServiceInstance()->assertPrincipalValid($principal);
			return ServiceProvider::getElementService();
		}
		catch(AuthenticationServiceException $ase)
		{
			if($ase->getCode() == AuthenticationServiceException::UNAUTHORIZED)
			{
				throw $ase;
			}
			else
			{
				throw new RestrictedServiceProviderException('',RestrictedServiceProviderException::WRAPPING, $ase);
			}
		}
		catch(Exception $e)
		{
			throw new RestrictedServiceProviderException('',RestrictedServiceProviderException::WRAPPING, $e);
		}
	}

	/**
	 * default delegates to wigii service provider after checking validity of principal
	 * throws AuthenticationServiceProviderException(UNAUTHORIZED) if principal is not valid.
	 * throws RestrictedServiceProviderException if an error occurs
	 */
	protected function getGroupAdminServiceInstance($principal)
	{
		try
		{
			$this->getAuthenticationServiceInstance()->assertPrincipalValid($principal);
			return ServiceProvider::getGroupAdminService();
		}
		catch(AuthenticationServiceException $ase)
		{
			if($ase->getCode() == AuthenticationServiceException::UNAUTHORIZED)
			{
				throw $ase;
			}
			else
			{
				throw new RestrictedServiceProviderException('',RestrictedServiceProviderException::WRAPPING, $ase);
			}
		}
		catch(Exception $e)
		{
			throw new RestrictedServiceProviderException('',RestrictedServiceProviderException::WRAPPING, $e);
		}
	}

	/**
	 * default delegates to wigii service provider after checking validity of principal
	 * throws AuthenticationServiceProviderException(UNAUTHORIZED) if principal is not valid.
	 * throws RestrictedServiceProviderException if an error occurs
	 */
	protected function getUserAdminServiceInstance($principal)
	{
		try
		{
			$this->getAuthenticationServiceInstance()->assertPrincipalValid($principal);
			return ServiceProvider::getUserAdminService();
		}
		catch(AuthenticationServiceException $ase)
		{
			if($ase->getCode() == AuthenticationServiceException::UNAUTHORIZED)
			{
				throw $ase;
			}
			else
			{
				throw new RestrictedServiceProviderException('',RestrictedServiceProviderException::WRAPPING, $ase);
			}
		}
		catch(Exception $e)
		{
			throw new RestrictedServiceProviderException('',RestrictedServiceProviderException::WRAPPING, $e);
		}
	}

	/**
	 * default delegates to wigii service provider after checking validity of principal
	 * throws AuthenticationServiceProviderException(UNAUTHORIZED) if principal is not valid.
	 * throws RestrictedServiceProviderException if an error occurs
	 */
	protected function getWigiiNamespaceAdminServiceInstance($principal)
	{
		try
		{
			$this->getAuthenticationServiceInstance()->assertPrincipalValid($principal);
			return ServiceProvider::getWigiiNamespaceAdminService();
		}
		catch(AuthenticationServiceException $ase)
		{
			if($ase->getCode() == AuthenticationServiceException::UNAUTHORIZED)
			{
				throw $ase;
			}
			else
			{
				throw new RestrictedServiceProviderException('',RestrictedServiceProviderException::WRAPPING, $ase);
			}
		}
		catch(Exception $e)
		{
			throw new RestrictedServiceProviderException('',RestrictedServiceProviderException::WRAPPING, $e);
		}
	}

	/**
	 * default delegates to wigii service provider after checking validity of principal
	 * throws AuthenticationServiceProviderException(UNAUTHORIZED) if principal is not valid.
	 * throws RestrictedServiceProviderException if an error occurs
	 */
	protected function getClientAdminServiceInstance($principal)
	{
		try
		{
			$this->getAuthenticationServiceInstance()->assertPrincipalValid($principal);
			return ServiceProvider::getClientAdminService();
		}
		catch(AuthenticationServiceException $ase)
		{
			if($ase->getCode() == AuthenticationServiceException::UNAUTHORIZED)
			{
				throw $ase;
			}
			else
			{
				throw new RestrictedServiceProviderException('',RestrictedServiceProviderException::WRAPPING, $ase);
			}
		}
		catch(Exception $e)
		{
			throw new RestrictedServiceProviderException('',RestrictedServiceProviderException::WRAPPING, $e);
		}
	}

	/**
	 * default delegates to wigii service provider after checking validity of principal
	 * throws AuthenticationServiceProviderException(UNAUTHORIZED) if principal is not valid.
	 * throws RestrictedServiceProviderException if an error occurs
	 */
	protected function getModuleAdminServiceInstance($principal)
	{
		try
		{
			$this->getAuthenticationServiceInstance()->assertPrincipalValid($principal);
			return ServiceProvider::getModuleAdminService();
		}
		catch(AuthenticationServiceException $ase)
		{
			if($ase->getCode() == AuthenticationServiceException::UNAUTHORIZED)
			{
				throw $ase;
			}
			else
			{
				throw new RestrictedServiceProviderException('',RestrictedServiceProviderException::WRAPPING, $ase);
			}
		}
		catch(Exception $e)
		{
			throw new RestrictedServiceProviderException('',RestrictedServiceProviderException::WRAPPING, $e);
		}
	}
}
?>