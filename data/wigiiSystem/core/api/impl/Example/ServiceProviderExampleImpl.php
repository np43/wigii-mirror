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

/*
 * Created on 24 November 2011
 * by LWR
 */


class ServiceProviderExampleImpl extends ServiceProviderWebImpl
{
	private $sessionCacheExecEnabled;

	protected function isSessionCacheExecEnabled(){
		return $this->sessionCacheExecEnabled;
	}
	private $sessionCacheBuildEnabled;

	protected function isSessionCacheBuildEnabled(){
		return $this->sessionCacheBuildEnabled;
	}

	public static function start($sessionCacheExecEnabled=true, $sessionCacheBuildEnabled=true, $sysConsoleDisplayEnabled=false, $wigiiExecutor=null)
	{
		$instance = new ServiceProviderExampleImpl();
		$instance->setWigiiExecutor($wigiiExecutor);
		$instance->setSysConsoleDisplayEnabled($sysConsoleDisplayEnabled);
		$instance->sessionCacheExecEnabled = $sessionCacheExecEnabled;
		$instance->sessionCacheBuildEnabled = $sessionCacheBuildEnabled;
		parent::registerSingleInstance($instance);
		// informs WigiiExecutor of SystemPrincipals
		if(isset($wigiiExecutor))
		{
			$wigiiExecutor->addSystemPrincipal($instance->getSystemPrincipals());
		}
	}
	
	/**
	 * default as UserAdminServiceExampleImpl
	 */
	//protected function createUserAdminServiceInstance()
	//{
	//	return new UserAdminServiceExampleImpl();
	//}


}


