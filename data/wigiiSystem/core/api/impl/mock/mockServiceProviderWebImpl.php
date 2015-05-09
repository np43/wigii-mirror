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

/*
 * Created on 24 ao�t 09
 * by LWR
 */


class mockServiceProviderWebImpl extends ServiceProviderWebImpl
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
		$instance = new mockServiceProviderWebImpl();
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
	 * default as ElementServiceImpl
	 */
	protected function createElementServiceInstance()
	{
		return new mockElementServiceImpl();
	}

	/**
	 * default as GroupAdminServiceImpl
	 */
	protected function createGroupAdminServiceInstance()
	{
		return new mockGroupAdminServiceImpl();
	}

	/**
	 * default as UserAdminServiceImpl
	 */
//	protected function createUserAdminServiceInstance()
//	{
//		return new mockUserAdminServiceImpl();
//	}

//	protected function createExceptionSinkInstance()
//	{
//		$expsW = new ExceptionSinkWebImpl();
//		$expsW->setSystemConsoleEnabled(true);
//		$exps = new testWigiiExecutorExceptionSink($expsW,
//			 	parent::createExceptionSinkInstance());
//		return $exps;
//	}
}


