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

/* wigii ModuleAdminService implementation
 * Created by CWE on 12 juin 09
 */
class ModuleAdminServiceImpl implements ModuleAdminService
{
	private $_debugLogger;
	private $_executionSink;
	private $authoS;
	private $mysqlF;
	private $dbAS;
	private $groupASImpl;

	/**
	 * module cache
	 * map moduleName -> Module
	 */
	private $moduleCache;

	private function debugLogger()
	{
		if(!isset($this->_debugLogger))
		{
			$this->_debugLogger = DebugLogger::getInstance("ModuleAdminServiceImpl");
		}
		return $this->_debugLogger;
	}
	private function executionSink()
	{
		if(!isset($this->_executionSink))
		{
			$this->_executionSink = ExecutionSink::getInstance("ModuleAdminServiceImpl");
		}
		return $this->_executionSink;
	}
	public function __construct()
	{
		$this->debugLogger()->write("creating instance");
	}

	// dependency injection

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
	public function setMySqlFacade($mysqlFacade)
	{
		$this->mysqlF = $mysqlFacade;
	}
	protected function getMySqlFacade()
	{
		// autowired
		if(!isset($this->mysqlF))
		{
			$this->mysqlF = TechnicalServiceProvider::getMySqlFacade();
		}
		return $this->mysqlF;
	}
	public function setDbAdminService($dbAdminService)
	{
		$this->dbAS = $dbAdminService;
	}
	protected function getDbAdminService()
	{
		// autowired
		if(!isset($this->dbAS))
		{
			$this->dbAS = ServiceProvider::getDbAdminService();
		}
		return $this->dbAS;
	}
	/**
	 * depends on GroupAdminServiceImpl or on a subclass
	 */
	public function setGroupAdminServiceImpl($groupAdminServiceImpl)
	{
		if(isset($groupAdminServiceImpl) && !($groupAdminServiceImpl instanceof GroupAdminServiceImpl))
		{
			throw new ModuleAdminServiceException("ModuleAdminServiceImpl depends on GroupAdminServiceImpl or a subclass", ModuleAdminServiceException::INVALID_ARGUMENT);
		}
		$this->groupASImpl = $groupAdminServiceImpl;
	}
	protected function getGroupAdminServiceImpl()
	{
		// autowired
		if(!isset($this->groupASImpl))
		{
			$this->setGroupAdminServiceImpl(ServiceProvider::getGroupAdminService());
		}
		return $this->groupASImpl;
	}



	// service implementation

	public function getModule($principal, $moduleName)
	{
		$this->executionSink()->publishStartOperation("getModule", $principal);
		try
		{
			$returnValue = $this->doGetModule($principal, $moduleName);
		}
		catch(Exception $e)
		{
			$this->executionSink()->publishEndOperationOnError("getModule", $e, $principal);
			throw new ModuleAdminServiceException('',ModuleAdminServiceException::WRAPPING, $e);
		}
		$this->executionSink()->publishEndOperation("getModule", $principal);
		return $returnValue;
	}

	public function getAdminModule($principal)
	{
		$this->executionSink()->publishStartOperation("getAdminModule", $principal);
		try
		{
			$returnValue = $this->doGetModule($principal, Module::ADMIN_MODULE);
		}
		catch(Exception $e)
		{
			$this->executionSink()->publishEndOperationOnError("getAdminModule", $e, $principal);
			throw new ModuleAdminServiceException('',ModuleAdminServiceException::WRAPPING, $e);
		}
		$this->executionSink()->publishEndOperation("getAdminModule", $principal);
		return $returnValue;
	}
	public function getHomeModule($principal)
	{
		$this->executionSink()->publishStartOperation("getHomeModule", $principal);
		try
		{
			$returnValue = $this->doGetModule($principal, Module::HOME_MODULE);
		}
		catch(Exception $e)
		{
			$this->executionSink()->publishEndOperationOnError("getHomeModule", $e, $principal);
			throw new ModuleAdminServiceException('',ModuleAdminServiceException::WRAPPING, $e);
		}
		$this->executionSink()->publishEndOperation("getHomeModule", $principal);
		return $returnValue;
	}

	public function getModuleFromSelectedGroups($principal, $groupLogExp)
	{
		$this->executionSink()->publishStartOperation("getModuleFromSelectedGroups", $principal);
		try
		{
			if(is_null($groupLogExp)) throw new ModuleAdminServiceException("groupLogExp cannot be null", ModuleAdminServiceException::INVALID_ARGUMENT);

			// checks authorization
			$this->assertPrincipalAuthorizedForGetModuleFromSelectedGroups($principal);

			// gets module
			$returnValue = null;
			$dbRow = $this->getMySqlFacade()->selectOne($principal,
					$this->getSqlForGetModuleFromSelectedGroups($groupLogExp),
					$this->getDbAdminService()->getDbConnectionSettings($principal));
			if(isset($dbRow))
			{
				$returnValue = $this->getModule($principal, $dbRow['module']);
			}
		}
		catch (ModuleAdminServiceException $maE){
			$this->executionSink()->publishEndOperationOnError("getModuleFromSelectedGroups", $maE, $principal);
			throw $maE;
		}
		catch (AuthorizationServiceException $asE){
			$this->executionSink()->publishEndOperationOnError("getModuleFromSelectedGroups", $asE, $principal);
			throw $asE;
		}
		catch(Exception $e)
		{
			$this->executionSink()->publishEndOperationOnError("getModuleFromSelectedGroups", $e, $principal);
			throw new ModuleAdminServiceException('',ModuleAdminServiceException::WRAPPING, $e);
		}
		$this->executionSink()->publishEndOperation("getModuleFromSelectedGroups", $principal);
		return $returnValue;
	}
	protected function assertPrincipalAuthorizedForGetModuleFromSelectedGroups($principal)
	{
		// checks general authorization
		return $this->getAuthorizationService()->assertPrincipalAuthorized($principal, "ModuleAdminService", "getModuleFromSelectedGroups");
	}
	protected function getSqlForGetModuleFromSelectedGroups($groupLogExp)
	{
		if(is_null($groupLogExp)) throw new ModuleAdminServiceException("groupSelection LogExp cannot be null", ModuleAdminServiceException::INVALID_ARGUMENT);

		$sqlB = $this->getGroupAdminServiceImpl()->getSqlWhereClauseBuilderForSelectGroups('G');
		// reduces logExp and clones it.
		$groupLogExp = $groupLogExp->reduceNegation(true);
		// where clause
		$whereClause = $sqlB->buildWhereClause($groupLogExp);
		$fs = FieldSelector::createInstance('module');
		$fsl = FieldSelectorListArrayImpl::createInstance();
		$fsl->addFieldSelectorInstance($fs);
		// group by clause
		$groupByClause = " group by G.".$sqlB->getSqlColumnNameForFieldSelector($fs);
		return $this->getGroupAdminServiceImpl()->getSqlForSelectGroups($whereClause, 0, 'G', $fsl, true).$groupByClause;
	}
	public function getModulesFromSelectedGroups($principal, $groupLogExp)
	{
		$this->executionSink()->publishStartOperation("getModulesFromSelectedGroups", $principal);
		try
		{
			if(is_null($groupLogExp)) throw new ModuleAdminServiceException("groupLogExp cannot be null", ModuleAdminServiceException::INVALID_ARGUMENT);

			// checks authorization
			$this->assertPrincipalAuthorizedForGetModuleFromSelectedGroups($principal);

			// gets module
			$returnValue = null;
			$dbRow = RowListArrayImpl::createInstance('module');
			$this->getMySqlFacade()->selectAll($principal,
					$this->getSqlForGetModuleFromSelectedGroups($groupLogExp),
					$this->getDbAdminService()->getDbConnectionSettings($principal),
					$dbRow);
			$returnValue = $this->formatModuleArray($principal, array_keys($dbRow->getListIterator()));
		}
		catch (ModuleAdminServiceException $maE){
			$this->executionSink()->publishEndOperationOnError("getModulesFromSelectedGroups", $maE, $principal);
			throw $maE;
		}
		catch (AuthorizationServiceException $asE){
			$this->executionSink()->publishEndOperationOnError("getModulesFromSelectedGroups", $asE, $principal);
			throw $asE;
		}
		catch(Exception $e)
		{
			$this->executionSink()->publishEndOperationOnError("getModulesFromSelectedGroups", $e, $principal);
			throw new ModuleAdminServiceException('',ModuleAdminServiceException::WRAPPING, $e);
		}
		$this->executionSink()->publishEndOperation("getModulesFromSelectedGroups", $principal);
		return $returnValue;
	}
	protected function assertPrincipalAuthorizedForGetModulesFromSelectedGroups($principal)
	{
		// checks general authorization
		return $this->getAuthorizationService()->assertPrincipalAuthorized($principal, "ModuleAdminService", "getModulesFromSelectedGroups");
	}

	public function getModuleFromSelectedElements($principal, $elementIds)
	{
		$this->executionSink()->publishStartOperation("getModuleFromSelectedElements", $principal);
		try
		{
			if(is_null($elementIds)) throw new ModuleAdminServiceException("elementIds cannot be null", ModuleAdminServiceException::INVALID_ARGUMENT);

			// checks authorization
			$this->assertPrincipalAuthorizedForGetModuleFromSelectedElements($principal);

			// gets module
			$returnValue = null;
			$dbRow = $this->getMySqlFacade()->selectOne($principal,
					$this->getSqlForGetModuleFromSelectedElements($elementIds),
					$this->getDbAdminService()->getDbConnectionSettings($principal));
			if(isset($dbRow))
			{
				$returnValue = $this->getModule($principal, $dbRow['module']);
			}
		}
		catch (ModuleAdminServiceException $maE){
			$this->executionSink()->publishEndOperationOnError("getModuleFromSelectedElements", $maE, $principal);
			throw $maE;
		}
		catch (AuthorizationServiceException $asE){
			$this->executionSink()->publishEndOperationOnError("getModuleFromSelectedElements", $asE, $principal);
			throw $asE;
		}
		catch(Exception $e)
		{
			$this->executionSink()->publishEndOperationOnError("getModuleFromSelectedElements", $e, $principal);
			throw new ModuleAdminServiceException('',ModuleAdminServiceException::WRAPPING, $e);
		}
		$this->executionSink()->publishEndOperation("getModuleFromSelectedElements", $principal);
		return $returnValue;
	}
	protected function assertPrincipalAuthorizedForGetModuleFromSelectedElements($principal)
	{
		// checks general authorization
		return $this->getAuthorizationService()->assertPrincipalAuthorized($principal, "ModuleAdminService", "getModuleFromSelectedElements");
	}
	protected function getSqlForGetModuleFromSelectedElements($elementIds)
	{
		if(is_null($elementIds)) throw new ModuleAdminServiceException("elementIds cannot be null", ModuleAdminServiceException::INVALID_ARGUMENT);

		$sqlB = $this->getMySqlFacade()->getSqlBuilder();
		$inIdElements = $sqlB->formatBinExp('E.id_element', 'IN', $elementIds, MySqlQueryBuilder::SQLTYPE_INT);
		return "select E.modulename as module from Elements as E where $inIdElements group by E.modulename";
	}

	public function formatModuleArray($principal, $moduleAccess){
		$this->executionSink()->publishStartOperation("formatModuleArray", $principal);
		try
		{
			if($moduleAccess == "" ||
				$moduleAccess == null ||
				$moduleAccess === "NULL" ||
				$moduleAccess === false){
				$moduleAccess = array();
			} elseif(is_string($moduleAccess)) {
				//remove any space or _ in the moduleAccess
				$moduleAccess = str_replace("_", "", str_replace(" ", "", $moduleAccess));
				$moduleAccess = explode(";", $moduleAccess);
				$moduleAccess = array_combine($moduleAccess, $moduleAccess);
				sort($moduleAccess);
			} elseif(!is_array($moduleAccess)){
				throw new ModuleAdminServiceException("Try to format a module access with an invalid argument.",ModuleAdminServiceException::INVALID_ARGUMENT);
			}

			//create for each key a Module object
			$returnValue = array();
			foreach($moduleAccess as $key=>$mod){
				if($mod==null) continue;
				if(is_numeric($key)){
					if(is_string($mod)){
						$moduleName = $mod;
					} else {
						$moduleName = $mod->getModuleName();
					}
				} else {
					$moduleName = $key;
				}
				$returnValue[$moduleName] = $this->doGetModule($principal, $moduleName);
			}
		}
		catch(Exception $e)
		{
			$this->executionSink()->publishEndOperationOnError("formatModuleArray", $e, $principal);
			throw new ModuleAdminServiceException('',ModuleAdminServiceException::WRAPPING, $e);
		}
		$this->executionSink()->publishEndOperation("formatModuleArray", $principal);
		return $returnValue;
	}
	public function moduleArrayToString($modules)
	{
		if(is_null($modules)) return null;
		if(!is_array($modules)) throw new ModuleAdminServiceException('module array is not an array', ModuleAdminException::INVALID_ARGUMENT);
		$keys = array_keys($modules);
		sort($keys);
		$returnValue = implode(';', $keys);
		unset($keys);
		return $returnValue;
	}

	public function getDefaultPropagation($module)
	{
		throw new ModuleAdminServiceException('only implemented in Web Impl', ModuleAdminServiceException::UNSUPPORTED_OPERATION);
	}

	/**
	 * creates a module instance with the given name
	 * extension point to use when wanting to create an instance based on a serialized object.
	 * returns a Module instance
	 */
	protected function createModuleInstance($principal, $moduleName)
	{
		$returnValue = Module::createInstance();
		$returnValue->setModuleName($moduleName);

		return $returnValue;
	}


	// Cache management

	private function getCachedModule($moduleName)
	{
		if(!isset($this->moduleCache)) return null;
		$returnValue = $this->moduleCache[$moduleName];
		if(!isset($returnValue))
		{
			$this->debugLogger()->write("module $moduleName not found in cache");
			return null;
		}
		return $returnValue;
	}

	private function cacheModule($module)
	{
		if(is_null($module)) return;
		if(!isset($this->moduleCache)) $this->moduleCache = array();
		$this->moduleCache[$module->getModuleName()] = $module;
		$this->debugLogger()->write('stores module');
	}

	// Utils

	private function doGetModule($principal, $moduleName)
	{
		//if($moduleName == Module::EMPTY_MODULE_URL) $moduleName = null;
		$returnValue = $this->getCachedModule($moduleName);
		if(!isset($returnValue))
		{
			$returnValue = $this->createModuleInstance($principal, $moduleName);
			$this->cacheModule($returnValue);
		}
		return $returnValue;
	}
}



