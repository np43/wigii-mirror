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

/* ServiceProvider implementation which integrates with command line interpreter
 * Created by CWE on 19 march 2013
 */
class ServiceProviderCliImpl extends ServiceProvider
{
	private $_debugLogger;
	private function debugLogger()
	{
		if(!isset($this->_debugLogger))
		{
			$this->_debugLogger = DebugLogger::getInstance("ServiceProviderCliImpl");
		}
		return $this->_debugLogger;
	}

	/**
	 * Creates new instance and registers itself in API.
	 * receives the CliExecutor which starts the API CliImpl so that it can configure it.
	 */
	private $cliExecutor;
	public function setCliExecutor($var){
		$this->cliExecutor = $var;
	}
	protected function getCliExecutor(){
		return $this->cliExecutor;
	}
	
	public static function start($cliExecutor=null)
	{
		$instance = new ServiceProviderCliImpl();
		$instance->setCliExecutor($cliExecutor);
		parent::registerSingleInstance($instance);
		// informs CliExecutor of SystemPrincipals
		if(isset($cliExecutor))
		{
			$cliExecutor->addSystemPrincipal($instance->getSystemPrincipals());
		}
	}
	
	
	// Implementation
	
	protected function createDbAdminServiceInstance()
	{
		return new DbAdminServiceWebImpl();
	}
	
	protected function createClientAdminServiceInstance()
	{
		return new ClientAdminServiceWebImpl();
	}
	
	protected function createModuleAdminServiceInstance()
	{		
		return new ModuleAdminServiceWebImpl();
	}
	
	protected function createConfigServiceInstance()
	{
		$returnValue = new ConfigServiceWebImpl();
		// sets configuration root folder to wigii/configs
		$returnValue->setConfigFolderPath(wigiiSystem_PATH.'configs');
		$returnValue->setDatatypeFolderPath(DATATYPE_PATH);
		$returnValue->setSessionCacheEnabled(true);
		return $returnValue;
	}
}


