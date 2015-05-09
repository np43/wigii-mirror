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

/* wigii ModuleAdminService implementation which integrates with wigii web site
 * Created by CWE on Sept 21. 09
 */
class ModuleAdminServiceWebImpl extends ModuleAdminServiceImpl
{
	private $_debugLogger;
	private $_executionSink;

	private function debugLogger()
	{
		if(!isset($this->_debugLogger))
		{
			$this->_debugLogger = DebugLogger::getInstance("ModuleAdminServiceWebImpl");
		}
		return $this->_debugLogger;
	}
	private function executionSink()
	{
		if(!isset($this->_executionSink))
		{
			$this->_executionSink = ExecutionSink::getInstance("ModuleAdminServiceWebImpl");
		}
		return $this->_executionSink;
	}
	public function __construct()
	{
		$this->debugLogger()->write("creating instance");
	}

	//service dependancy
	private $configS;
	public function setConfigService($configService)
	{
		$this->configS = $configService;
	}
	protected function getConfigService()
	{
		// autowired
		if(!isset($this->configS))
		{
			$this->configS = ServiceProvider::getConfigService();
		}
		return $this->configS;
	}

	private $authenticationS;
	public function setAuthenticationService($authenticationS)
	{
		$this->authenticationS = $authenticationS;
	}
	protected function getAuthenticationService()
	{
		// autowired
		if(!isset($this->authenticationS))
		{
			$this->authenticationS = ServiceProvider::getAuthenticationService();
		}
		return $this->authenticationS;
	}

	// service implementation

	public function getDefaultPropagation($module)
	{
		if(is_null($module)) throw new ModuleAdminServiceException('module can not be null', ModuleAdminServiceException::INVALID_ARGUMENT);
		$propagation = $this->getConfigService()->m($this->getAuthenticationService()->getMainPrincipal(), null)->parameters->MODULE_defaultPropagation->{$module->getModuleName()}["propagation"];
		if(!isset($propagation)) $propagation = 1;
//		$this->debugLogger()->write("default propagation for module ".$module->getModuleName()." is ".$propagation);
		return $propagation;

//		global $MODULE_defaultPropagation;
//		$propagation = $MODULE_defaultPropagation[$module->getModuleName()];
//		if(!isset($propagation)) throw new ModuleAdminServiceException('module '.$module->getModuleName().' has no default propagation configured', ModuleAdminServiceException::INVALID_ARGUMENT);
//		else return $propagation;
	}
}



