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

/**
 * wigii ElementService implementation
 * Created by CWE on 2 juin 09
 * Modified by CWE on 9 février 2014 to add support of sub elements
 * Modified by Medair (CWE,LMA) on 06.12.2016 to optimize SQL query for getAllElementsInGroups
 */
class ElementServiceImpl implements ElementService
{
	private $_debugLogger;
	private $_executionSink;
	private $configS; private $subElementConfigSupport;
	private $subElementConfigS;
	private $authoS;
	private $transS;
	private $dbAS;
	private $mysqlF;
	private $moduleAS;
	private $groupASImpl;
	private $tripod;
	private $elementPMapper;
	private $wigiiBagFiller;
	private $dbTableFiller;
	private $groupPListMapper;
	private $groupListMapper;
	private $elementQueryPlanner;
	private $sqlBuilderForGetAllElementsInGroup;
	private $sqlBuilderForGetAllElementsInGroups;
	private $sqlBuilderForGetPagedElementsInGroups;
	private $sqlBuilderForGetSelectedElementsInGroups;
	private $sqlBuilderForGetSelectedElements;
	private $sqlBuilderForSelectElementsInGroups;
	private $sqlBuilderForFillElement;
	private $sqlBuilderForInsertElement;
	private $sqlBuilderForUpdateElement;
	private $sqlBuilderForUpdateMultipleElement;
	private $sqlBuilderForGetSubElementsForField;
	private $elementServiceGroupPList;
	private $linkSelectorListMapper;

	private function debugLogger()
	{
		if(!isset($this->_debugLogger))
		{
			$this->_debugLogger = DebugLogger::getInstance("ElementServiceImpl");
		}
		return $this->_debugLogger;
	}
	private function executionSink()
	{
		if(!isset($this->_executionSink))
		{
			$this->_executionSink = ExecutionSink::getInstance("ElementServiceImpl");
		}
		return $this->_executionSink;
	}
	public function __construct()
	{
		$this->debugLogger()->write("creating instance");
	}

	// dependency injection

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

	public function setConfigService($configService)
	{
		$this->configS = $configService;
		unset($this->subElementConfigSupport);
	}
	protected function getConfigService()
	{
		// autowired
		if(!isset($this->configS))
		{
			$this->configS = ServiceProvider::getConfigService();
		}
		if(!isset($this->subElementConfigSupport)) {
			$this->subElementConfigSupport = array(
				'selectSubElementsConfig' => method_exists($this->configS, 'selectSubElementsConfig'),
				'selectSubElementConfig' => method_exists($this->configS, 'selectSubElementConfig'),
				'unselectSubElementConfig' => method_exists($this->configS, 'unselectSubElementConfig'),
				'getCurrentModule' => method_exists($this->configS, 'getCurrentModule'),
				'getCurrentLinkSelector' => method_exists($this->configS, 'getCurrentLinkSelector'),
				'getCurrentSubElementPathFromRoot' => method_exists($this->configS, 'getCurrentSubElementPathFromRoot')
			);
		}
		return $this->configS;
	}
	protected function getSubElementConfigService() {
		// autowired
		if(!isset($this->subElementConfigS))
		{
			$returnValue = $this->getConfigService();
			// validates support of sub elements
			if(!$this->subElementConfigSupport['selectSubElementsConfig']) throw new ElementServiceException("Injected ConfigService instance does not support method 'selectSubElementsConfig' which is needed in the scope of sub elements. Please inject another ConfigService instance.", ElementServiceException::CONFIGURATION_ERROR);
			if(!$this->subElementConfigSupport['getCurrentModule']) throw new ElementServiceException("Injected ConfigService instance does not support method 'getCurrentModule' which is needed in the scope of sub elements. Please inject another ConfigService instance.", ElementServiceException::CONFIGURATION_ERROR);
			if(!$this->subElementConfigSupport['getCurrentLinkSelector']) throw new ElementServiceException("Injected ConfigService instance does not support method 'getCurrentLinkSelector' which is needed in the scope of sub elements. Please inject another ConfigService instance.", ElementServiceException::CONFIGURATION_ERROR);
			if(!$this->subElementConfigSupport['getCurrentSubElementPathFromRoot']) throw new ElementServiceException("Injected ConfigService instance does not support method 'getCurrentSubElementPathFromRoot' which is needed in the scope of sub elements. Please inject another ConfigService instance.", ElementServiceException::CONFIGURATION_ERROR);
			$this->subElementConfigS = $returnValue;
		}
		return $this->subElementConfigS;
	}

	public function setElementQueryPlanner($elementQueryPlanner)
	{
		$this->elementQueryPlanner = $elementQueryPlanner;
	}
	protected function getElementQueryPlanner($sqlQueryType, $reservedNumberOfJoins=0, $fieldSelectorList=null, $fieldSelectorLogExp=null, $fieldSortingKeyList=null)
	{
		// autowired
		if(!isset($this->elementQueryPlanner))
		{
			$this->elementQueryPlanner = ElementQueryPlanner::createInstance($sqlQueryType, $reservedNumberOfJoins, $fieldSelectorList, $fieldSelectorLogExp, $fieldSortingKeyList);
		}
		else
		{
			if($this->elementQueryPlanner->isLockedForUse()) {
				return ElementQueryPlanner::createInstance($sqlQueryType, $reservedNumberOfJoins, $fieldSelectorList, $fieldSelectorLogExp, $fieldSortingKeyList);
			}
			else $this->elementQueryPlanner->reset($sqlQueryType, $reservedNumberOfJoins, $fieldSelectorList, $fieldSelectorLogExp, $fieldSortingKeyList);
		}
		return $this->elementQueryPlanner;
	}

	public function setElementPMapper($elementPMapper)
	{
		$this->elementPMapper = $elementPMapper;
	}
	protected function getElementPMapper($principal, $recordStructureFactory, $pRights = null, $elementEvaluator=null)
	{
		// autowired
		if(!isset($this->elementPMapper))
		{
			$this->elementPMapper = ElementPMapper::createInstance($principal, $recordStructureFactory, $pRights, $elementEvaluator);
		}
		else
		{
			if($this->elementPMapper->isLockedForUse()) {
				return ElementPMapper::createInstance($principal, $recordStructureFactory, $pRights, $elementEvaluator);
			}
			else $this->elementPMapper->reset($principal, $recordStructureFactory, $pRights, $elementEvaluator);
		}
		return $this->elementPMapper;
	}

	public function setWigiiBagFiller($wigiiBagFiller)
	{
		$this->wigiiBagFiller = $wigiiBagFiller;
	}
	protected function getWigiiBagFiller($principal, $wigiiBag, $pRights = null)
	{
		// autowired
		if(!isset($this->wigiiBagFiller))
		{
			$this->wigiiBagFiller =WigiiBagFiller::createInstance($principal, $wigiiBag, $pRights);
		}
		else
		{
			$this->wigiiBagFiller->reset($principal, $wigiiBag, $pRights);
		}
		return $this->wigiiBagFiller;
	}

	public function setDbTableFiller($dbTableFiller)
	{
		$this->dbTableFiller = $dbTableFiller;
	}
	protected function getDbTableFiller($principal, $dbTable, $createTable, $pRights = null)
	{
		// autowired
		if(!isset($this->dbTableFiller))
		{
			$this->dbTableFiller =DbTableFiller::createInstance($principal, $dbTable, $createTable, $pRights);
		}
		else
		{
			$this->dbTableFiller->reset($principal, $dbTable, $createTable, $pRights);
		}
		return $this->dbTableFiller;
	}

	protected function getTripod()
	{
		if(!isset($this->tripod))
		{
			$this->tripod = new ElementServiceTripod();
			$this->tripod->elementServiceImpl = $this;
		}
		return $this->tripod;
	}

	public function setTranslationService($translationService)
	{
		$this->transS = $translationService;
	}
	protected function getTranslationService()
	{
		// autowired
		if(!isset($this->transS))
		{
			$this->transS = ServiceProvider::getTranslationService();
		}
		return $this->transS;
	}

	public function setModuleAdminService($moduleAdminService)
	{
		$this->moduleAS = $moduleAdminService;
	}
	protected function getModuleAdminService()
	{
		// autowired
		if(!isset($this->moduleAS))
		{
			$this->moduleAS = ServiceProvider::getModuleAdminService();
		}
		return $this->moduleAS;
	}

	/**
	 * depends on GroupAdminServiceImpl or on a subclass
	 */
	public function setGroupAdminServiceImpl($groupAdminServiceImpl)
	{
		if(isset($groupAdminServiceImpl) && !($groupAdminServiceImpl instanceof GroupAdminServiceImpl))
		{
			throw new ElementServiceException("ElementServiceImpl depends on GroupAdminServiceImpl or a subclass", ElementServiceException::INVALID_ARGUMENT);
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

	public function setGroupPListMapper($groupPListMapper)
	{
		$this->groupPListMapper = $groupPListMapper;
	}
	protected function getGroupPListMapper($principal, $groupPList, $pRights = null)
	{
		// autowired
		if(!isset($this->groupPListMapper))
		{
			$this->groupPListMapper = GroupPListMapper::createInstance($this->getGroupAdminServiceImpl(), $principal, $groupPList, $pRights);
		}
		else
		{
			if($this->groupPListMapper->isLockedForUse()) {
				return GroupPListMapper::createInstance($this->getGroupAdminServiceImpl(), $principal, $groupPList, $pRights);
			}
			else $this->groupPListMapper->reset($principal, $groupPList, $pRights);
		}
		return $this->groupPListMapper;
	}
	public function setGroupListMapper($groupListMapper)
	{
		$this->groupListMapper = $groupListMapper;
	}
	protected function getGroupListMapper($principal, $groupList, $withDetail = true)
	{
		// autowired
		if(!isset($this->groupListMapper))
		{
			$this->groupListMapper = GroupListMapper::createInstance($this->getGroupAdminServiceImpl(), $principal, $groupList, $withDetail);
		}
		else
		{
			if($this->groupListMapper->isLockedForUse()) {
				return GroupListMapper::createInstance($this->getGroupAdminServiceImpl(), $principal, $groupList, $withDetail);
			}
			else $this->groupListMapper->reset($principal, $groupList, $withDetail);
		}
		return $this->groupListMapper;
	}

	protected function getElementEvaluatorForModule($principal, $module)
	{
		return ServiceProvider::getElementEvaluator($principal,
			(string)$this->getConfigService()->getParameter($principal, $module, "Element_evaluator"));
	}
	protected function getElementEvaluatorForGroup($principal, $group)
	{
		return ServiceProvider::getElementEvaluator($principal,
			(string)$this->getConfigService()->getGroupParameter($principal, $group, "Element_evaluator"));
	}
	protected function getElementEvaluatorForGroupList($principal, $groupList)
	{
		if(!is_array($groupList)) $groupList = $groupList->getListIterator();
		foreach($groupList as $group)
		{
			return $this->getElementEvaluatorForModule($principal, $group->getDbEntity()->getModule());
		}
	}

	public function setLinkSelectorListMapper($linkSelectorListMapper)
	{
		$this->linkSelectorListMapper = $linkSelectorListMapper;
	}
	protected function getLinkSelectorListMapper($linkSelectorList)
	{
		// autowired
		if(!isset($this->linkSelectorListMapper))
		{
			$this->linkSelectorListMapper = LinkSelectorListMapper::createInstance($linkSelectorList);
		}
		else
		{
			if($this->linkSelectorListMapper->isLockedForUse()) {
				return LinkSelectorListMapper::createInstance($linkSelectorList);
			}
			else $this->linkSelectorListMapper->reset($linkSelectorList);
		}
		return $this->linkSelectorListMapper;
	}

	// System principal management

	/**
	 * Adds a system principal or a list of system principals to the ElementService
	 */
	public function addSystemPrincipal($systemPrincipal)
	{
		if(is_null($systemPrincipal)) return;
		$this->getSystemPrincipals()->unionPrincipalList($systemPrincipal);
		$this->debugLogger()->write("received ".$systemPrincipal->count()." system principals.");
	}
	private $systemPrincipals;
	/**
	 * Returns the list of actual system principals owned by the ElementService
	 */
	protected function getSystemPrincipals()
	{
		//autowired
		if(!isset($this->systemPrincipals))
		{
			$this->systemPrincipals = PrincipalListArrayImpl::createInstance();
		}
		return $this->systemPrincipals;
	}
	/**
	 * Gets the root principal
	 */
	protected function getRootPrincipal()
	{
		$returnValue = ServiceProvider::getAuthorizationService()->findRootPrincipal($this->getSystemPrincipals());
		if(is_null($returnValue)) throw new AuthorizationServiceException("root principal has not been initialized by Service Provider", AuthorizationServiceException::FORBIDDEN);
		return $returnValue;
	}

	// service implementation


	public function getAllElementsInGroup($principal, $group, $elementPList, $includeChildrenGroups=true, $listFilter=null)
	{
		$this->executionSink()->publishStartOperation("getAllElementsInGroup", $principal);
		$elementPMapper = null;
		$eltQP = null;
		try
		{
			if(is_null($elementPList)) throw new ElementServiceException('elementPList can not be null', ElementServiceException::INVALID_ARGUMENT);

			// checks authorization
			$pRights = $this->assertPrincipalAuthorizedForGetAllElementsInGroup($principal, $group);
			$pRightsFromDb = !isset($pRights);

			$returnValue = null;
			if(is_null($group)) throw new ElementServiceException('group can not be null', ElementServiceException::INVALID_ARGUMENT);

			// initializes pagination, sorting, etc.
			$fieldSelectorList = null;
			$fieldSelectorLogExp = null;
			$fieldSortingKeyList = null;
			$configGroup = null;
			$configGroupList = null;
			$queryPaged = false;
			if(isset($listFilter))
			{
				$fieldSelectorList = $listFilter->getFieldSelectorList();
				$fieldSelectorLogExp = $listFilter->getFieldSelectorLogExp();
				$fieldSortingKeyList = $listFilter->getFieldSortingKeyList();
				$configGroupList = $listFilter->getConfigGroupList();
				if($listFilter->isPaged())
				{
					$queryPaged = true;
					$pageSize = $listFilter->getPageSize();
					$offset = ($listFilter->getDesiredPageNumber() - 1) * $pageSize;
				}
			}
			if($configGroupList == null) $configGroup = $group;
			else $configGroup = reset($configGroupList->getListIterator());
			// computes effective field list
			$eltQP = $this->getElementQueryPlanner(MySqlFacade::Q_SELECTALL, $this->getReservedSqlJoinsForGetAllElementsInGroup(),
							$fieldSelectorList, $fieldSelectorLogExp, $fieldSortingKeyList);
			if($eltQP->areFieldSelected())
			{
				$cS = $this->getConfigService();
				// if ConfigService supports method unselectSubElementConfig
				// then unselects any previous sub element config
				if($this->subElementConfigSupport['unselectSubElementConfig']) $cS->unselectSubElementConfig($principal);
				$cS->getGroupFields($principal, $configGroup->getDbEntity(), null, $eltQP);
			}

			// gets elements
			$elementPMapper = $this->getElementPMapper($principal, $elementPList, $pRights,
				$this->getElementEvaluatorForGroup($principal, $configGroup->getDbEntity()));
			$elementPMapper->setTripod($this->getTripod());
			$dbCS = $this->getDbAdminService()->getDbConnectionSettings($principal);
			$n = $eltQP->getNumberOfQueries();
			$strategy = $eltQP->getQueryStrategy();
			$mysqlF = $this->getMySqlFacade();
			for($i = 0; $i < $n; $i++)
			{
				if($queryPaged)
				{
					// Medair (CWE,LMA) 06.12.2016 - SQL optimization for paged query
					/*
					$nbRows = $mysqlF->selectPage($principal, $eltQP->getSql($i,
						$this->getSqlBuilderForGetAllElementsInGroup($principal, $strategy, $group, $pRightsFromDb, $includeChildrenGroups)),
						$dbCS, $offset, $pageSize,
						$elementPMapper);
					if($i === 0) {
						$listFilter->setTotalNumberOfObjects($nbRows);
						if($nbRows > 0) $elementPMapper->setTotalNumberOfElements($nbRows);
					}
					*/
					$sqlB = $this->getSqlBuilderForGetPagedElementsInGroups($principal, $strategy, $group, $pRightsFromDb, $includeChildrenGroups,$offset,$pageSize);
					// gets paged (SQL LIMIT is included into generated SQL, no need to call selectPage)
					$nbRows = $mysqlF->selectAll($principal, $eltQP->getSql($i,$sqlB),
							$dbCS,
							$elementPMapper);
					// fetches total number of rows
					if($i===0) {
						$totalNumberOfRows = $mysqlF->selectOneValue($principal, $sqlB->getSqlForCountElements(), $dbCS);
						$listFilter->setTotalNumberOfObjects($totalNumberOfRows);
						if($totalNumberOfRows > 0) $elementPMapper->setTotalNumberOfElements($totalNumberOfRows);
					}
				}
				else
				{
					$nbRows = $mysqlF->selectAll($principal, $eltQP->getSql($i,
						$this->getSqlBuilderForGetAllElementsInGroup($principal, $strategy, $group, $pRightsFromDb, $includeChildrenGroups)),
						$dbCS,
						$elementPMapper);
				}
				if(($i === 0) && ($nbRows === 0)) break;
			}
			$eltQP->freeMemory();
			$returnValue = $elementPMapper->count();
			if($returnValue > 0) $elementPMapper->flush($elementPList);
			else $elementPMapper->freeMemory();
		}
		catch(ElementServiceException $ese)
		{
			if(isset($elementPMapper)) $elementPMapper->freeMemory();
			if(isset($eltQP)) $eltQP->freeMemory();
			$this->executionSink()->publishEndOperationOnError("getAllElementsInGroup", $ese, $principal);
			throw $ese;
		}
		catch (AuthorizationServiceException $asE){
			if(isset($elementPMapper)) $elementPMapper->freeMemory();
			if(isset($eltQP)) $eltQP->freeMemory();
			$this->executionSink()->publishEndOperationOnError("getAllElementsInGroup", $asE, $principal);
			throw $asE;
		}
		catch(Exception $e)
		{
			if(isset($elementPMapper)) $elementPMapper->freeMemory();
			if(isset($eltQP)) $eltQP->freeMemory();
			$this->executionSink()->publishEndOperationOnError("getAllElementsInGroup", $e, $principal);
			throw new ElementServiceException('',ElementServiceException::WRAPPING, $e);
		}
		$this->executionSink()->publishEndOperation("getAllElementsInGroup", $principal);
		return $returnValue;
	}
	protected function assertPrincipalAuthorizedForGetAllElementsInGroup($principal, $group)
	{
		if(is_null($group)) throw new ElementServiceException('group can not be null', ElementServiceException::INVALID_ARGUMENT);
		$module = $group->getModule();
		if(is_null($module)) throw new ElementServiceException('group->module can not be null', ElementServiceException::INVALID_ARGUMENT);
		$autoS = $this->getAuthorizationService();
		// checks general authorization
		$pRights = $autoS->assertPrincipalAuthorized($principal, "ElementService", "getAllElementsInGroup");
		// adapts wigii namespace if needed
		if($principal->hasAdaptiveWigiiNamespace()) $principal->bindToWigiiNamespace($group->getWigiiNamespace());
		// checks module access
		if(is_null($pRights) && is_null($principal->getModuleAccess($module))) $autoS->fail($principal, 'has no access to module '.$module->getModuleName());
		return $pRights;
	}
	public function setSqlBuilderForGetAllElementsInGroup($queryStrategy, $sqlBuilderForGetAllElementsInGroup)
	{
		$this->sqlBuilderForGetAllElementsInGroup[$queryStrategy] = $sqlBuilderForGetAllElementsInGroup;
	}
	protected function getSqlBuilderForGetAllElementsInGroup($principal, $queryStrategy, $group, $pRightsFromDb, $includeChildrenGroups)
	{
		$sqlB = $this->doGetSqlBuilderForGetAllElementsInGroup($queryStrategy);
		$sqlB->setTripod($this->getTripod());
		$sqlB->reset($principal, $group, $pRightsFromDb, $includeChildrenGroups);
		return $sqlB;
	}
	protected function getReservedSqlJoinsForGetAllElementsInGroup()
	{
		return $this->doGetSqlBuilderForGetAllElementsInGroup(ElementQueryPlanner::QSTRATEGY_JOIN)->getNumberOfStaticJoins();
	}
	/**
	 * Returns a non initialized query builder for GetAllElementsInGroup according to strategy
	 */
	protected function doGetSqlBuilderForGetAllElementsInGroup($queryStrategy)
	{
		// autowired
		if(!isset($this->sqlBuilderForGetAllElementsInGroup)) $this->sqlBuilderForGetAllElementsInGroup = array();
		$sqlB = $this->sqlBuilderForGetAllElementsInGroup[$queryStrategy];
		if(!isset($sqlB))
		{
			switch($queryStrategy)
			{
				case ElementQueryPlanner::QSTRATEGY_JOIN:
					$sqlB = ElementSqlBuilderForGetAllElementsInGroups::createNonInitInstance();
					$this->sqlBuilderForGetAllElementsInGroup[$queryStrategy] = $sqlB;
					break;
				default: throw new ElementServiceException('unsupported query strategy', ElementServiceException::INVALID_ARGUMENT);
			}
		}
		return $sqlB;
	}
	/**
	 * exactly same authorizations and sql as getAllElementsInGroup
	 */
	public function fillWigiiBagWithAllElementsInGroup($principal, $group, $wigiiBag, $includeChildrenGroups=true, $listFilter=null)
	{
		$this->executionSink()->publishStartOperation("fillWigiiBagWithAllElementsInGroup", $principal);
		$eltQP = null;
		try
		{
			if(is_null($wigiiBag)) throw new ElementServiceException('wigiiBag can not be null', ElementServiceException::INVALID_ARGUMENT);

			// checks authorization
			$pRights = $this->assertPrincipalAuthorizedForGetAllElementsInGroup($principal, $group);
			$pRightsFromDb = !isset($pRights);

			$returnValue = null;
			if(is_null($group)) throw new ElementServiceException('group can not be null', ElementServiceException::INVALID_ARGUMENT);

			// initializes pagination, etc.
			$fieldSelectorList = null;
			$fieldSelectorLogExp = null;
			$fieldSortingKeyList = null;
			$configGroupList = null;
			$configGroup = null;
			$queryPaged = false;
			if(isset($listFilter))
			{
				$fieldSelectorList = $listFilter->getFieldSelectorList();
				$fieldSelectorLogExp = $listFilter->getFieldSelectorLogExp();
				$fieldSortingKeyList = $listFilter->getFieldSortingKeyList();
				$configGroupList = $listFilter->getConfigGroupList();
				if($listFilter->isPaged())
				{
					$queryPaged = true;
					$pageSize = $listFilter->getPageSize();
					$offset = ($listFilter->getDesiredPageNumber() - 1) * $pageSize;
				}
			}
			if($configGroupList == null) $configGroup = $group;
			else $configGroup = reset($configGroupList->getListIterator());

			// computes effective field list
			$eltQP = $this->getElementQueryPlanner(MySqlFacade::Q_SELECTALL, $this->getReservedSqlJoinsForGetAllElementsInGroup(),
							$fieldSelectorList, $fieldSelectorLogExp, $fieldSortingKeyList);
			$cS = $this->getConfigService();
			// if ConfigService supports method unselectSubElementConfig
			// then unselects any previous sub element config
			if($this->subElementConfigSupport['unselectSubElementConfig']) $cS->unselectSubElementConfig($principal);
			$cS->getGroupFields($principal, $configGroup->getDbEntity(), null, $eltQP);
			// ignores element detail
			$eltQP->ignoreElementDetail();

			// gets elements
			$wigiiBagFiller = $this->getWigiiBagFiller($principal, $wigiiBag, $pRights);
			$wigiiBagFiller->setTripod($this->getTripod());
			$dbCS = $this->getDbAdminService()->getDbConnectionSettings($principal);
			$n = $eltQP->getNumberOfQueries();
			$strategy = $eltQP->getQueryStrategy();
			$mysqlF = $this->getMySqlFacade();
			for($i = 0; $i < $n; $i++)
			{
				if($queryPaged)
				{
					// Medair (CWE,LMA) 06.12.2016 - SQL optimization for paged query
					/*
					 $nbRows = $mysqlF->selectPage($principal, $eltQP->getSql($i,
					 $this->getSqlBuilderForGetAllElementsInGroup($principal, $strategy, $group, $pRightsFromDb, $includeChildrenGroups)),
					 $dbCS, $offset, $pageSize,
					 $elementPMapper);
					 if($i === 0) {
					 $listFilter->setTotalNumberOfObjects($nbRows);
					 if($nbRows > 0) $elementPMapper->setTotalNumberOfElements($nbRows);
					 }
					 */
					$sqlB = $this->getSqlBuilderForGetPagedElementsInGroups($principal, $strategy, $group, $pRightsFromDb, $includeChildrenGroups,$offset,$pageSize);
					// gets paged (SQL LIMIT is included into generated SQL, no need to call selectPage)
					$nbRows = $mysqlF->selectAll($principal, $eltQP->getSql($i,$sqlB),
							$dbCS,
							$elementPMapper);
					// fetches total number of rows
					if($i===0) {
						$totalNumberOfRows = $mysqlF->selectOneValue($principal, $sqlB->getSqlForCountElements(), $dbCS);
						$listFilter->setTotalNumberOfObjects($totalNumberOfRows);
						if($totalNumberOfRows > 0) $elementPMapper->setTotalNumberOfElements($totalNumberOfRows);
					}					
				}
				else
				{
					$nbRows = $mysqlF->selectAll($principal, $eltQP->getSql($i,
						$this->getSqlBuilderForGetAllElementsInGroup($principal, $strategy, $group, $pRightsFromDb, $includeChildrenGroups)),
						$dbCS,
						$wigiiBagFiller);
				}
				if(($i === 0) && ($nbRows === 0)) break;
			}
			$eltQP->freeMemory();
			$returnValue = $wigiiBagFiller->count();
			$wigiiBagFiller->freeMemory();
		}
		catch(ElementServiceException $ese)
		{
			if(isset($eltQP)) $eltQP->freeMemory();
			$this->executionSink()->publishEndOperationOnError("fillWigiiBagWithAllElementsInGroup", $ese, $principal);
			throw $ese;
		}
		catch (AuthorizationServiceException $asE){
			if(isset($eltQP)) $eltQP->freeMemory();
			$this->executionSink()->publishEndOperationOnError("fillWigiiBagWithAllElementsInGroup", $asE, $principal);
			throw $asE;
		}
		catch(Exception $e)
		{
			if(isset($eltQP)) $eltQP->freeMemory();
			$this->executionSink()->publishEndOperationOnError("fillWigiiBagWithAllElementsInGroup", $e, $principal);
			throw new ElementServiceException('',ElementServiceException::WRAPPING, $e);
		}
		$this->executionSink()->publishEndOperation("fillWigiiBagWithAllElementsInGroup", $principal);
		return $returnValue;
	}

	public function getAllElementsInGroups($principal, $groupList, $elementPList, $includeChildrenGroups=true, $listFilter=null, $manualPaging_desiredPage=null, $manualPaging_pageSize=null, $manualPaging_keepRightsInOutOfPage=false)
	{
		$this->executionSink()->publishStartOperation("getAllElementsInGroups", $principal);
		$elementPMapper = null;
		$eltQP = null;
		try
		{
			if(is_null($elementPList)) throw new ElementServiceException('elementPList can not be null', ElementServiceException::INVALID_ARGUMENT);

			// checks authorization
			$pRights = $this->assertPrincipalAuthorizedForGetAllElementsInGroups($principal, $groupList);
			$pRightsFromDb = !isset($pRights);

			$returnValue = null;

			// initializes pagination, sorting, etc.
			$fieldSelectorList = null;
			$fieldSelectorLogExp = null;
			$fieldSortingKeyList = null;
			$configGroupList = null;
			$queryPaged = false;
			if(isset($listFilter))
			{
				$fieldSelectorList = $listFilter->getFieldSelectorList();
				$fieldSelectorLogExp = $listFilter->getFieldSelectorLogExp();
				$fieldSortingKeyList = $listFilter->getFieldSortingKeyList();
				$configGroupList = $listFilter->getConfigGroupList();
				if($listFilter->isPaged())
				{
					$queryPaged = true;
					$pageSize = $listFilter->getPageSize();
					$offset = ($listFilter->getDesiredPageNumber() - 1) * $pageSize;
				}
			}
			if($configGroupList == null){
				$configGroupList = $groupList;
			}

			// computes effective field list
			$eltQP = $this->getElementQueryPlanner(MySqlFacade::Q_SELECTALL, $this->getReservedSqlJoinsForGetAllElementsInGroups(),
							$fieldSelectorList, $fieldSelectorLogExp, $fieldSortingKeyList);
			if($eltQP->areFieldSelected())
			{
				$cS = $this->getConfigService();
				// if ConfigService supports method unselectSubElementConfig
				// then unselects any previous sub element config
				if($this->subElementConfigSupport['unselectSubElementConfig']) $cS->unselectSubElementConfig($principal);
				$cS->getGroupsFields($principal, $configGroupList, null, $eltQP);
			}

			// gets elements
			$elementPMapper = $this->getElementPMapper($principal, $elementPList, $pRights,
				$this->getElementEvaluatorForGroupList($principal, $configGroupList));
			$elementPMapper->setTripod($this->getTripod());

			if($manualPaging_pageSize) $elementPMapper->setManualPaging($manualPaging_desiredPage, $manualPaging_pageSize, $manualPaging_keepRightsInOutOfPage);

			$dbCS = $this->getDbAdminService()->getDbConnectionSettings($principal);
			$n = $eltQP->getNumberOfQueries();
			$strategy = $eltQP->getQueryStrategy();
			$mysqlF = $this->getMySqlFacade();
			for($i = 0; $i < $n; $i++)
			{				
				if($queryPaged)
				{
					// Medair (CWE,LMA) 06.12.2016 - SQL optimization for paged query
					/*
					$nbRows = $mysqlF->selectPage($principal, $eltQP->getSql($i,
						$this->getSqlBuilderForGetAllElementsInGroups($principal, $strategy, $groupList, $pRightsFromDb, $includeChildrenGroups)),
						$dbCS, $offset, $pageSize,
						$elementPMapper);
					if($i === 0) {
						$listFilter->setTotalNumberOfObjects($nbRows);
						if($nbRows > 0) $elementPMapper->setTotalNumberOfElements($nbRows);
					}
					*/					
					$sqlB = $this->getSqlBuilderForGetPagedElementsInGroups($principal, $strategy, $groupList, $pRightsFromDb, $includeChildrenGroups,$offset,$pageSize);
					// gets paged (SQL LIMIT is included into generated SQL, no need to call selectPage)
					$nbRows = $mysqlF->selectAll($principal, $eltQP->getSql($i,$sqlB),
						$dbCS,
						$elementPMapper);
					// fetches total number of rows
					if($i===0) {						
						$totalNumberOfRows = $mysqlF->selectOneValue($principal, $sqlB->getSqlForCountElements(), $dbCS);
						$listFilter->setTotalNumberOfObjects($totalNumberOfRows);
						if($totalNumberOfRows > 0) $elementPMapper->setTotalNumberOfElements($totalNumberOfRows);
					}					
				}
				else
				{
					$nbRows = $mysqlF->selectAll($principal, $eltQP->getSql($i,
						$this->getSqlBuilderForGetAllElementsInGroups($principal, $strategy, $groupList, $pRightsFromDb, $includeChildrenGroups)),
						$dbCS,
						$elementPMapper);
				}				
				if(($i === 0) && ($nbRows === 0)) break;
			}
			$eltQP->freeMemory();
			$returnValue = $elementPMapper->count();
			if($returnValue > 0) $elementPMapper->flush($elementPList);
			else $elementPMapper->freeMemory();
		}
		catch(ElementServiceException $ese)
		{
			if(isset($elementPMapper)) $elementPMapper->freeMemory();
			if(isset($eltQP)) $eltQP->freeMemory();
			$this->executionSink()->publishEndOperationOnError("getAllElementsInGroups", $ese, $principal);
			throw $ese;
		}
		catch (AuthorizationServiceException $asE){
			if(isset($elementPMapper)) $elementPMapper->freeMemory();
			if(isset($eltQP)) $eltQP->freeMemory();
			$this->executionSink()->publishEndOperationOnError("getAllElementsInGroups", $asE, $principal);
			throw $asE;
		}
		catch(Exception $e)
		{
			if(isset($elementPMapper)) $elementPMapper->freeMemory();
			if(isset($eltQP)) $eltQP->freeMemory();
			$this->executionSink()->publishEndOperationOnError("getAllElementsInGroups", $e, $principal);
			throw new ElementServiceException('',ElementServiceException::WRAPPING, $e);
		}
		$this->executionSink()->publishEndOperation("getAllElementsInGroups", $principal);
		return $returnValue;
	}
	protected function assertPrincipalAuthorizedForGetAllElementsInGroups($principal, $groupList)
	{
		if(is_null($groupList)) throw new ElementServiceException('groupList can not be null', ElementServiceException::INVALID_ARGUMENT);

		$autoS = $this->getAuthorizationService();
		// checks general authorization
		$pRights = $autoS->assertPrincipalAuthorized($principal, "ElementService", "getAllElementsInGroups");

		// checks module access
		$n = 0;
		if(!is_array($groupList))
		{
			$n = $groupList->count();
			$groupList = $groupList->getListIterator();
		}
		else $n = count($groupList);
		if($n == 0) throw new ElementServiceException('groupList can not be empty', ElementServiceException::INVALID_ARGUMENT);
		$initialized = false; $firstModule = null; $firstWigiiNamespace = null;
		$oneWigiiNamespace = true;
		foreach($groupList as $group)
		{
			$module = $group->getDbEntity()->getModule();
			$wigiiNamespace = $group->getDbEntity()->getWigiiNamespace();
			if(is_null($module)) throw new ElementServiceException('groupList->group->module can not be null', ElementServiceException::INVALID_ARGUMENT);
			if($initialized)
			{
				// checks module consistency and wigiiNamespace unicity
				if($module !== $firstModule) throw new ElementServiceException('groupList->group->module should always be the same instance equal to '.$firstModule->getModuleName(), ElementServiceException::INVALID_ARGUMENT);
				if($wigiiNamespace !== $firstWigiiNamespace) $oneWigiiNamespace = false;
			}
			else
			{
				// records first module and wigiiNamespace to check consistency
				$firstModule = $module;
				$firstWigiiNamespace = $wigiiNamespace;
				$initialized = true;
			}
		}
		// adapts wigii namespace if needed
		if($oneWigiiNamespace && $principal->hasAdaptiveWigiiNamespace()) $principal->bindToWigiiNamespace($firstWigiiNamespace);
		// checks module access
		if(is_null($pRights) && is_null($principal->getModuleAccess($firstModule))) $autoS->fail($principal, 'has no access to module '.$firstModule->getModuleName());
		return $pRights;
	}
	public function setSqlBuilderForGetAllElementsInGroups($queryStrategy, $sqlBuilderForGetAllElementsInGroups)
	{
		$this->sqlBuilderForGetAllElementsInGroups[$queryStrategy] = $sqlBuilderForGetAllElementsInGroups;
	}
	protected function getSqlBuilderForGetAllElementsInGroups($principal, $queryStrategy, $groupList, $pRightsFromDb, $includeChildrenGroups)
	{
		$sqlB = $this->doGetSqlBuilderForGetAllElementsInGroups($queryStrategy);
		$sqlB->setTripod($this->getTripod());
		$sqlB->reset($principal, $groupList, $pRightsFromDb, $includeChildrenGroups);
		return $sqlB;
	}
	protected function getReservedSqlJoinsForGetAllElementsInGroups()
	{
		return $this->doGetSqlBuilderForGetAllElementsInGroups(ElementQueryPlanner::QSTRATEGY_JOIN)->getNumberOfStaticJoins();
	}
	/**
	 * Returns a non initialized query builder for GetAllElementsInGroups according to strategy
	 */
	protected function doGetSqlBuilderForGetAllElementsInGroups($queryStrategy)
	{
		// autowired
		if(!isset($this->sqlBuilderForGetAllElementsInGroups)) $this->sqlBuilderForGetAllElementsInGroups = array();
		$sqlB = $this->sqlBuilderForGetAllElementsInGroups[$queryStrategy];
		if(!isset($sqlB))
		{
			switch($queryStrategy)
			{
				case ElementQueryPlanner::QSTRATEGY_JOIN:
					$sqlB = ElementSqlBuilderForGetAllElementsInGroups::createNonInitInstance();
					$this->sqlBuilderForGetAllElementsInGroups[$queryStrategy] = $sqlB;
					break;
				default: throw new ElementServiceException('unsupported query strategy', ElementServiceException::INVALID_ARGUMENT);
			}
		}
		return $sqlB;
	}
	public function setSqlBuilderForGetPagedElementsInGroups($sqlBuilderForGetPagedElementsInGroups) {
		$this->sqlBuilderForGetPagedElementsInGroups = $sqlBuilderForGetPagedElementsInGroups;
	}
	/**
	 * @return ElementSqlBuilderForGetPagedElementsInGroups returns an ElementSqlBuilder optimized for paged view using SQL LIMIT 
	 */
	protected function getSqlBuilderForGetPagedElementsInGroups($principal, $queryStrategy, $groupList, $pRightsFromDb, $includeChildrenGroups, $offset, $pageSize) {
		if($queryStrategy != ElementQueryPlanner::QSTRATEGY_JOIN) throw new ElementServiceException('unsupported query strategy', ElementServiceException::INVALID_ARGUMENT);
		if(!isset($this->sqlBuilderForGetPagedElementsInGroups)) {
			$this->sqlBuilderForGetPagedElementsInGroups = ElementSqlBuilderForGetPagedElementsInGroups::createNonInitInstance();
		}
		$this->sqlBuilderForGetPagedElementsInGroups->setTripod($this->getTripod());
		$this->sqlBuilderForGetPagedElementsInGroups->reset($principal, $groupList, $pRightsFromDb, $includeChildrenGroups);
		$this->sqlBuilderForGetPagedElementsInGroups->setPaging($offset, $pageSize);
		return $this->sqlBuilderForGetPagedElementsInGroups;
	}
	/**
	 * exactly same authorizations and sql as getAllElementsInGroups
	 */
	public function fillWigiiBagWithAllElementsInGroups($principal, $groupList, $wigiiBag, $includeChildrenGroups=true, $listFilter=null)
	{
		$this->executionSink()->publishStartOperation("fillWigiiBagWithAllElementsInGroups", $principal);
		$eltQP = null;
		try
		{
			if(is_null($wigiiBag)) throw new ElementServiceException('wigiiBag can not be null', ElementServiceException::INVALID_ARGUMENT);

			// checks authorization
			$pRights = $this->assertPrincipalAuthorizedForGetAllElementsInGroups($principal, $groupList);
			$pRightsFromDb = !isset($pRights);

			$returnValue = null;

			// initializes pagination, etc.
			$fieldSelectorList = null;
			$fieldSelectorLogExp = null;
			$fieldSortingKeyList = null;
			$configGroupList = null;
			$queryPaged = false;
			if(isset($listFilter))
			{
				$fieldSelectorList = $listFilter->getFieldSelectorList();
				$fieldSelectorLogExp = $listFilter->getFieldSelectorLogExp();
				$fieldSortingKeyList = $listFilter->getFieldSortingKeyList();
				$configGroupList = $listFilter->getConfigGroupList();
				if($listFilter->isPaged())
				{
					$queryPaged = true;
					$pageSize = $listFilter->getPageSize();
					$offset = ($listFilter->getDesiredPageNumber() - 1) * $pageSize;
				}
			}
			if($configGroupList == null) $configGroupList = $groupList;
			// computes effective field list
			$eltQP = $this->getElementQueryPlanner(MySqlFacade::Q_SELECTALL, $this->getReservedSqlJoinsForGetAllElementsInGroups(),
							$fieldSelectorList, $fieldSelectorLogExp, $fieldSortingKeyList);
			$cS = $this->getConfigService();
			// if ConfigService supports method unselectSubElementConfig
			// then unselects any previous sub element config
			if($this->subElementConfigSupport['unselectSubElementConfig']) $cS->unselectSubElementConfig($principal);
			$cS->getGroupsFields($principal, $configGroupList, null, $eltQP);
			// ignores element detail
			$eltQP->ignoreElementDetail();

			// gets elements
			$wigiiBagFiller = $this->getWigiiBagFiller($principal, $wigiiBag, $pRights);
			$wigiiBagFiller->setTripod($this->getTripod());
			$dbCS = $this->getDbAdminService()->getDbConnectionSettings($principal);
			$n = $eltQP->getNumberOfQueries();
			$strategy = $eltQP->getQueryStrategy();
			$mysqlF = $this->getMySqlFacade();
			for($i = 0; $i < $n; $i++)
			{
				if($queryPaged)
				{
					// Medair (CWE,LMA) 06.12.2016 - SQL optimization for paged query
					/*
					$nbRows = $mysqlF->selectPage($principal, $eltQP->getSql($i,
							$this->getSqlBuilderForGetAllElementsInGroups($principal, $strategy, $groupList, $pRightsFromDb, $includeChildrenGroups)),
							$dbCS, $offset, $pageSize,
							$wigiiBagFiller);
					if($i === 0) $listFilter->setTotalNumberOfObjects($nbRows);
					*/ 
					$sqlB = $this->getSqlBuilderForGetPagedElementsInGroups($principal, $strategy, $groupList, $pRightsFromDb, $includeChildrenGroups,$offset,$pageSize);
					// gets paged (SQL LIMIT is included into generated SQL, no need to call selectPage)
					$nbRows = $mysqlF->selectAll($principal, $eltQP->getSql($i,$sqlB),
							$dbCS,
							$elementPMapper);
					// fetches total number of rows
					if($i===0) {
						$totalNumberOfRows = $mysqlF->selectOneValue($principal, $sqlB->getSqlForCountElements(), $dbCS);
						$listFilter->setTotalNumberOfObjects($totalNumberOfRows);
						if($totalNumberOfRows > 0) $elementPMapper->setTotalNumberOfElements($totalNumberOfRows);
					}					
				}
				else
				{
					$nbRows = $mysqlF->selectAll($principal, $eltQP->getSql($i,
						$this->getSqlBuilderForGetAllElementsInGroups($principal, $strategy, $groupList, $pRightsFromDb, $includeChildrenGroups)),
						$dbCS,
						$wigiiBagFiller);
				}
				if(($i === 0) && ($nbRows === 0)) break;
			}
			$eltQP->freeMemory();
			$returnValue = $wigiiBagFiller->count();
			$wigiiBagFiller->freeMemory();
		}
		catch(ElementServiceException $ese)
		{
			if(isset($eltQP)) $eltQP->freeMemory();
			$this->executionSink()->publishEndOperationOnError("fillWigiiBagWithAllElementsInGroups", $ese, $principal);
			throw $ese;
		}
		catch (AuthorizationServiceException $asE){
			if(isset($eltQP)) $eltQP->freeMemory();
			$this->executionSink()->publishEndOperationOnError("fillWigiiBagWithAllElementsInGroups", $asE, $principal);
			throw $asE;
		}
		catch(Exception $e)
		{
			if(isset($eltQP)) $eltQP->freeMemory();
			$this->executionSink()->publishEndOperationOnError("fillWigiiBagWithAllElementsInGroups", $e, $principal);
			throw new ElementServiceException('',ElementServiceException::WRAPPING, $e);
		}
		$this->executionSink()->publishEndOperation("fillWigiiBagWithAllElementsInGroups", $principal);
		return $returnValue;
	}

	public function getSelectedElements($principal, $elementIds, $elementPList, $listFilter=null)
	{
		$this->executionSink()->publishStartOperation("getSelectedElements", $principal);
		$elementPMapper = null;
		$groupMapper = null;
		$eltQP = null;
		try
		{
			if(is_null($elementPList)) throw new ElementServiceException('elementPList can not be null', ElementServiceException::INVALID_ARGUMENT);
			if(is_null($elementIds)) throw new ElementServiceException('elementIds can not be null', ElementServiceException::INVALID_ARGUMENT);

			$mysqlF = $this->getMySqlFacade();
			$dbCS = $this->getDbAdminService()->getDbConnectionSettings($principal);

			// gets GroupList containing elements
			$groupList = GroupListAdvancedImpl::createInstance();
			$groupMapper = $this->getGroupListMapper($principal, $groupList, false);
			$mysqlF->selectAll($principal,
				$this->getSqlForGetAllGroupsWithoutDetailsContainingElements($principal, $elementIds),
				$dbCS,
				$groupMapper);
			$groupMapper->freeMemory();

			// checks authorization
			$pRights = $this->assertPrincipalAuthorizedForGetSelectedElements($principal, $elementIds, $groupList);

			$pRightsFromDb = !isset($pRights);


			// notifies calculated group list if possible
			if(isset($elementPList) && method_exists($elementPList, 'notifyCalculatedGroupList')) {
				$elementPList->notifyCalculatedGroupList($groupList);
			}

			$returnValue = null;

			// initializes pagination, sorting, etc.
			$fieldSelectorList = null;
			$fieldSelectorLogExp = null;
			$fieldSortingKeyList = null;
			$configGroupList = null;
			$queryPaged = false;
			if(isset($listFilter))
			{
				$fieldSelectorList = $listFilter->getFieldSelectorList();
				$fieldSelectorLogExp = $listFilter->getFieldSelectorLogExp();
				$fieldSortingKeyList = $listFilter->getFieldSortingKeyList();
				$configGroupList = $listFilter->getConfigGroupList();
				if($listFilter->isPaged())
				{
					$queryPaged = true;
					$pageSize = $listFilter->getPageSize();
					$offset = ($listFilter->getDesiredPageNumber() - 1) * $pageSize;
				}
			}
			if($configGroupList == null) $configGroupList = $groupList;

			// elements in list id
			$elementInIdListSqlB = $this->getSqlBuilderForSelectElements($elementIds);

			// computes effective field list
			$eltQP = $this->getElementQueryPlanner(MySqlFacade::Q_SELECTALL, $this->getReservedSqlJoinsForGetSelectedElements($elementInIdListSqlB, $pRightsFromDb),
							$fieldSelectorList, $fieldSelectorLogExp, $fieldSortingKeyList);
			if($eltQP->areFieldSelected())
			{
				$cS = $this->getConfigService();
				// if ConfigService supports method unselectSubElementConfig
				// then unselects any previous sub element config
				if($this->subElementConfigSupport['unselectSubElementConfig']) $cS->unselectSubElementConfig($principal);
				$cS->getGroupsFields($principal, $configGroupList, null, $eltQP);
			}

			// gets elements
			$elementPMapper = $this->getElementPMapper($principal, $elementPList, $pRights,
				$this->getElementEvaluatorForGroupList($principal, $configGroupList));
			$elementPMapper->setTripod($this->getTripod());
			$n = $eltQP->getNumberOfQueries();
			$strategy = $eltQP->getQueryStrategy();
			for($i = 0; $i < $n; $i++)
			{
				if($queryPaged)
				{
					$nbRows = $mysqlF->selectPage($principal, $eltQP->getSql($i,
						$this->getSqlBuilderForGetSelectedElements($strategy, $elementInIdListSqlB, $principal, $groupList, $pRightsFromDb)),
						$dbCS, $offset, $pageSize,
						$elementPMapper);
					if($i === 0) {
						$listFilter->setTotalNumberOfObjects($nbRows);
						if($nbRows > 0) $elementPMapper->setTotalNumberOfElements($nbRows);
					}
				}
				else
				{
					$nbRows = $mysqlF->selectAll($principal, $eltQP->getSql($i,
						$this->getSqlBuilderForGetSelectedElements($strategy, $elementInIdListSqlB, $principal, $groupList, $pRightsFromDb)),
						$dbCS,
						$elementPMapper);
				}
				if(($i === 0) && ($nbRows === 0)) break;
			}
			$eltQP->freeMemory();
			$returnValue = $elementPMapper->count();
			if($returnValue > 0) $elementPMapper->flush($elementPList);
			else $elementPMapper->freeMemory();
		}
		catch(ElementServiceException $ese)
		{
			if(isset($groupMapper)) $groupMapper->freeMemory();
			if(isset($elementPMapper)) $elementPMapper->freeMemory();
			if(isset($eltQP)) $eltQP->freeMemory();
			$this->executionSink()->publishEndOperationOnError("getSelectedElements", $ese, $principal);
			throw $ese;
		}
		catch (AuthorizationServiceException $asE){
			if(isset($groupMapper)) $groupMapper->freeMemory();
			if(isset($elementPMapper)) $elementPMapper->freeMemory();
			if(isset($eltQP)) $eltQP->freeMemory();
			$this->executionSink()->publishEndOperationOnError("getSelectedElements", $asE, $principal);
			throw $asE;
		}
		catch(Exception $e)
		{
			if(isset($groupMapper)) $groupMapper->freeMemory();
			if(isset($elementPMapper)) $elementPMapper->freeMemory();
			if(isset($eltQP)) $eltQP->freeMemory();
			$this->executionSink()->publishEndOperationOnError("getSelectedElements", $e, $principal);
			throw new ElementServiceException('',ElementServiceException::WRAPPING, $e);
		}
		$this->executionSink()->publishEndOperation("getSelectedElements", $principal);
		return $returnValue;
	}
	protected function assertPrincipalAuthorizedForGetSelectedElements($principal, $elementIds, $groupList)
	{
		$autoS = $this->getAuthorizationService();
		// checks general authorization
		$pRights = $autoS->assertPrincipalAuthorized($principal, "ElementService", "getSelectedElements");

		// checks module access
		$n = 0;
		if(!is_array($groupList))
		{
			$n = $groupList->count();
			$groupList = $groupList->getListIterator();
		}
		else $n = count($groupList);
		if($n == 0) throw new ElementServiceException('groupList can not be empty', ElementServiceException::INVALID_ARGUMENT);
		$initialized = false; $firstModule = null; $firstWigiiNamespace = null;
		$oneWigiiNamespace = true;
		foreach($groupList as $group)
		{
			$module = $group->getModule();
			$wigiiNamespace = $group->getWigiiNamespace();
			if(is_null($module)) throw new ElementServiceException('groupList->group->module can not be null', ElementServiceException::INVALID_ARGUMENT);
			if($initialized)
			{
				// checks module consistency and wigiiNamespace unicity
				if($module !== $firstModule) throw new ElementServiceException('groupList->group->module should always be the same instance equal to '.$firstModule->getModuleName(), ElementServiceException::INVALID_ARGUMENT);
				if($wigiiNamespace !== $firstWigiiNamespace) $oneWigiiNamespace = false;
			}
			else
			{
				// records first module to check consistency
				$firstModule = $module;
				$firstWigiiNamespace = $wigiiNamespace;
				$initialized = true;
			}
		}
		// adapts wigii namespace if needed
		if($oneWigiiNamespace && $principal->hasAdaptiveWigiiNamespace()) $principal->bindToWigiiNamespace($firstWigiiNamespace);
		// checks module access
		if(is_null($pRights) && is_null($principal->getModuleAccess($firstModule))) $autoS->fail($principal, 'has no access to module '.$firstModule->getModuleName());

		return $pRights;
	}
	public function setSqlBuilderForGetSelectedElements($queryStrategy, $sqlBuilderForGetSelectedElements)
	{
		$this->sqlBuilderForGetSelectedElementsInGroups[$queryStrategy] = $sqlBuilderForGetSelectedElements;
	}
	protected function getSqlBuilderForGetSelectedElements($queryStrategy, $elementInIdListSqlB, $principal, $groupList, $pRightsFromDb)
	{
		$sqlB = $this->doGetSqlBuilderForGetSelectedElements($queryStrategy);
		$sqlB->setTripod($this->getTripod());
		$sqlB->reset($elementInIdListSqlB, $principal, $groupList, $pRightsFromDb);
		return $sqlB;
	}
	/**
	 * Returns a non initialized query builder for GetSelectedElements according to strategy
	 */
	protected function doGetSqlBuilderForGetSelectedElements($queryStrategy)
	{
		// autowired
		if(!isset($this->sqlBuilderForGetSelectedElements)) $this->sqlBuilderForGetSelectedElements = array();
		$sqlB = $this->sqlBuilderForGetSelectedElements[$queryStrategy];
		if(!isset($sqlB))
		{
			switch($queryStrategy)
			{
				case ElementQueryPlanner::QSTRATEGY_JOIN:
					$sqlB = ElementSqlBuilderForGetSelectedElements::createNonInitInstance();
					$this->sqlBuilderForGetSelectedElements[$queryStrategy] = $sqlB;
					break;
				default: throw new ElementServiceException('unsupported query strategy', ElementServiceException::INVALID_ARGUMENT);
			}
		}
		return $sqlB;
	}
	protected function getReservedSqlJoinsForGetSelectedElements($elementInIdListSqlB, $pRightsFromDb)
	{
		return $this->doGetSqlBuilderForGetSelectedElements(ElementQueryPlanner::QSTRATEGY_JOIN)->getNumberOfStaticJoins($elementInIdListSqlB, $pRightsFromDb);
	}
	public function setSqlBuilderForSelectElements($sqlBuilderForSelectElements)
	{
		$this->sqlBuilderForSelectElements = $sqlBuilderForSelectElements;
	}
	protected function getSqlBuilderForSelectElements($elementIds)
	{
		// autowired
		if(!isset($this->sqlBuilderForSelectElements))
		{
			$this->sqlBuilderForSelectElements = ElementInIdListSqlBuilder::createInstance();
		}
		if(is_null($elementIds)) throw new ElementServiceException('elementIds can not be null', ElementServiceException::INVALID_ARGUMENT);
		$this->sqlBuilderForSelectElements->reset();
		$this->sqlBuilderForSelectElements->buildSqlForElementInIdList($elementIds);
		return $this->sqlBuilderForSelectElements;
	}

	public function getSelectedElementsInGroups($principal, $inGroupLogExp, $elementPList, $listFilter=null, $manualPaging_desiredPage=null, $manualPaging_pageSize=null, $manualPaging_keepRightsInOutOfPage=false)
	{
		$this->executionSink()->publishStartOperation("getSelectedElementsInGroups", $principal);
		$elementPMapper = null;
		$eltQP = null;
		try
		{
			if(is_null($elementPList)) throw new ElementServiceException('elementPList can not be null', ElementServiceException::INVALID_ARGUMENT);
			if(is_null($inGroupLogExp)) throw new ElementServiceException('inGroupLogExp can not be null', ElementServiceException::INVALID_ARGUMENT);

			// gets groupList
			$gAS = $this->getGroupAdminServiceImpl();
			$groupSelectionLogExp = $gAS->convertInGroupLogExp2GroupLogExp($inGroupLogExp);
			$groupList = GroupListArrayImpl::createInstance();
			$gAS->getSelectedGroupsWithoutDetail($principal, $groupSelectionLogExp, $groupList);

			// notifies calculated group list if possible
			if(isset($elementPList) && method_exists($elementPList, 'notifyCalculatedGroupList')) {
				$elementPList->notifyCalculatedGroupList($groupList);
			}

			// checks if ingrouplogexp is a single instance of LogExpInGroup
			// if yes, then redirects the call to getAllElementsInGroups with the grouplist
			if($inGroupLogExp instanceof LogExpInGroup) {
				$returnValue = $this->getAllElementsInGroups($principal, $groupList, $elementPList, $inGroupLogExp->includeChildrenGroups(), $listFilter, $manualPaging_desiredPage, $manualPaging_pageSize, $manualPaging_keepRightsInOutOfPage);
			}
			// else continues.
			else
			{
				// checks authorization
				$pRights = $this->assertPrincipalAuthorizedForGetSelectedElementsInGroups($principal, $groupList);
				$pRightsFromDb = !isset($pRights);

				$returnValue = null;

				// initializes pagination, sorting, etc.
				$fieldSelectorList = null;
				$fieldSelectorLogExp = null;
				$fieldSortingKeyList = null;
				$configGroupList = null;
				$queryPaged = false;
				if(isset($listFilter))
				{
					$fieldSelectorList = $listFilter->getFieldSelectorList();
					$fieldSelectorLogExp = $listFilter->getFieldSelectorLogExp();
					$fieldSortingKeyList = $listFilter->getFieldSortingKeyList();
					$configGroupList = $listFilter->getConfigGroupList();
					if($listFilter->isPaged())
					{
						$queryPaged = true;
						$pageSize = $listFilter->getPageSize();
						$offset = ($listFilter->getDesiredPageNumber() - 1) * $pageSize;
					}
				}
				if($configGroupList == null){
					$configGroupList = $groupList;
				}

				// element in group selection
				$elementInGroupSqlB = $this->getSqlBuilderForSelectElementsInGroups($inGroupLogExp);

				// computes effective field list
				$eltQP = $this->getElementQueryPlanner(MySqlFacade::Q_SELECTALL, $this->getReservedSqlJoinsForGetSelectedElementsInGroups($elementInGroupSqlB, $pRightsFromDb),
								$fieldSelectorList, $fieldSelectorLogExp, $fieldSortingKeyList);

				if($eltQP->areFieldSelected())
				{
					$cS = $this->getConfigService();
					// if ConfigService supports method unselectSubElementConfig
					// then unselects any previous sub element config
					if($this->subElementConfigSupport['unselectSubElementConfig']) $cS->unselectSubElementConfig($principal);
					$cS->getGroupsFields($principal, $configGroupList, null, $eltQP);
				}

				// gets elements
				$elementPMapper = $this->getElementPMapper($principal, $elementPList, $pRights,
					$this->getElementEvaluatorForGroupList($principal, $configGroupList));
				$elementPMapper->setTripod($this->getTripod());

				if($manualPaging_pageSize) $elementPMapper->setManualPaging($manualPaging_desiredPage, $manualPaging_pageSize, $manualPaging_keepRightsInOutOfPage);

				$dbCS = $this->getDbAdminService()->getDbConnectionSettings($principal);
				$n = $eltQP->getNumberOfQueries();
				$strategy = $eltQP->getQueryStrategy();
				$mysqlF = $this->getMySqlFacade();
				for($i = 0; $i < $n; $i++)
				{

					$sql = $eltQP->getSql($i,$this->getSqlBuilderForGetSelectedElementsInGroups($strategy, $elementInGroupSqlB, $principal, $groupList, $pRightsFromDb));

					if($queryPaged)
					{
						$nbRows = $mysqlF->selectPage($principal, $sql,
							$dbCS, $offset, $pageSize,
							$elementPMapper);
						if($i === 0) {
							$listFilter->setTotalNumberOfObjects($nbRows);
							if($nbRows > 0) $elementPMapper->setTotalNumberOfElements($nbRows);
						}
					}
					else
					{
						$nbRows = $mysqlF->selectAll($principal, $sql,
							$dbCS,
							$elementPMapper);
					}
	//				fput($nbRows);
					if(($i === 0) && ($nbRows === 0)) break;
				}
				$eltQP->freeMemory();
				$returnValue = $elementPMapper->count();
				if($returnValue > 0) $elementPMapper->flush($elementPList);
				else $elementPMapper->freeMemory();
			}
		}
		catch(ElementServiceException $ese)
		{
			if(isset($elementPMapper)) $elementPMapper->freeMemory();
			if(isset($eltQP)) $eltQP->freeMemory();
			$this->executionSink()->publishEndOperationOnError("getSelectedElementsInGroups", $ese, $principal);
			throw $ese;
		}
		catch (AuthorizationServiceException $asE){
			if(isset($elementPMapper)) $elementPMapper->freeMemory();
			if(isset($eltQP)) $eltQP->freeMemory();
			$this->executionSink()->publishEndOperationOnError("getSelectedElementsInGroups", $asE, $principal);
			throw $asE;
		}
		catch(Exception $e)
		{
			if(isset($elementPMapper)) $elementPMapper->freeMemory();
			if(isset($eltQP)) $eltQP->freeMemory();
			$this->executionSink()->publishEndOperationOnError("getSelectedElementsInGroups", $e, $principal);
			throw new ElementServiceException('',ElementServiceException::WRAPPING, $e);
		}
		$this->executionSink()->publishEndOperation("getSelectedElementsInGroups", $principal);
		return $returnValue;
	}
	protected function assertPrincipalAuthorizedForGetSelectedElementsInGroups($principal, $groupList)
	{
		$autoS = $this->getAuthorizationService();
		// checks general authorization
		$pRights = $autoS->assertPrincipalAuthorized($principal, "ElementService", "getSelectedElementsInGroups");

		// checks module access
		$n = 0;
		if(!is_array($groupList))
		{
			$n = $groupList->count();
			$groupList = $groupList->getListIterator();
		}
		else $n = count($groupList);
		if($n == 0) throw new ElementServiceException('groupList can not be empty', ElementServiceException::INVALID_ARGUMENT);
		$initialized = false; $firstModule = null; $firstWigiiNamespace = null;
		$oneWigiiNamespace = true;
		foreach($groupList as $group)
		{
			$module = $group->getModule();
			$wigiiNamespace = $group->getWigiiNamespace();
			if(is_null($module)) throw new ElementServiceException('groupList->group->module can not be null', ElementServiceException::INVALID_ARGUMENT);
			if($initialized)
			{
				// checks module consistency and wigiiNamespace unicity
				if($module !== $firstModule) throw new ElementServiceException('groupList->group->module should always be the same instance equal to '.$firstModule->getModuleName(), ElementServiceException::INVALID_ARGUMENT);
				if($wigiiNamespace !== $firstWigiiNamespace) $oneWigiiNamespace = false;
			}
			else
			{
				// records first module to check consistency
				$firstModule = $module;
				$firstWigiiNamespace = $wigiiNamespace;
				$initialized = true;
			}
		}
		// adapts wigii namespace if needed
		if($oneWigiiNamespace && $principal->hasAdaptiveWigiiNamespace()) $principal->bindToWigiiNamespace($firstWigiiNamespace);
		// checks module access
		if(is_null($pRights) && is_null($principal->getModuleAccess($firstModule))) $autoS->fail($principal, 'has no access to module '.$firstModule->getModuleName());
		return $pRights;
	}
	public function setSqlBuilderForGetSelectedElementsInGroups($queryStrategy, $sqlBuilderForGetSelectedElementsInGroups)
	{
		$this->sqlBuilderForGetSelectedElementsInGroups[$queryStrategy] = $sqlBuilderForGetSelectedElementsInGroups;
	}
	protected function getSqlBuilderForGetSelectedElementsInGroups($queryStrategy, $elementInGroupSqlB, $principal, $groupList, $pRightsFromDb)
	{
		$sqlB = $this->doGetSqlBuilderForGetSelectedElementsInGroups($queryStrategy);
		$sqlB->setTripod($this->getTripod());
		$sqlB->reset($elementInGroupSqlB, $principal, $groupList, $pRightsFromDb);
		return $sqlB;
	}
	/**
	 * Returns a non initialized query builder for GetSelectedElementsInGroups according to strategy
	 */
	protected function doGetSqlBuilderForGetSelectedElementsInGroups($queryStrategy)
	{
		// autowired
		if(!isset($this->sqlBuilderForGetSelectedElementsInGroups)) $this->sqlBuilderForGetSelectedElementsInGroups = array();
		$sqlB = $this->sqlBuilderForGetSelectedElementsInGroups[$queryStrategy];
		if(!isset($sqlB))
		{
			switch($queryStrategy)
			{
				case ElementQueryPlanner::QSTRATEGY_JOIN:
					$sqlB = ElementSqlBuilderForGetSelectedElementsInGroups::createNonInitInstance();
					$this->sqlBuilderForGetSelectedElementsInGroups[$queryStrategy] = $sqlB;
					break;
				default: throw new ElementServiceException('unsupported query strategy', ElementServiceException::INVALID_ARGUMENT);
			}
		}
		return $sqlB;
	}
	protected function getReservedSqlJoinsForGetSelectedElementsInGroups($elementInGroupSqlB, $pRightsFromDb)
	{
		return $this->doGetSqlBuilderForGetSelectedElementsInGroups(ElementQueryPlanner::QSTRATEGY_JOIN)->getNumberOfStaticJoins($elementInGroupSqlB, $pRightsFromDb);
	}
	public function setSqlBuilderForSelectElementsInGroups($sqlBuilderForSelectElementsInGroups)
	{
		$this->sqlBuilderForSelectElementsInGroups = $sqlBuilderForSelectElementsInGroups;
	}
	protected function getSqlBuilderForSelectElementsInGroups($inGroupLogExp)
	{
		// autowired
		if(!isset($this->sqlBuilderForSelectElementsInGroups))
		{
			$this->sqlBuilderForSelectElementsInGroups = ElementInGroupSqlBuilder::createInstance();
		}
		if(is_null($inGroupLogExp)) throw new ElementServiceException('inGroupLogExp can not be null', ElementServiceException::INVALID_ARGUMENT);
		$this->sqlBuilderForSelectElementsInGroups->reset();
		$this->sqlBuilderForSelectElementsInGroups->buildSqlForElementInGroup($inGroupLogExp);
		return $this->sqlBuilderForSelectElementsInGroups;
	}

	/**
	 * exactly same authorizations and sql as getSelectedElementsInGroups
	 */
	public function fillWigiiBagWithSelectedElementsInGroups($principal, $inGroupLogExp, $wigiiBag, $listFilter=null)
	{
		$this->executionSink()->publishStartOperation("fillWigiiBagWithSelectedElementsInGroups", $principal);
		$eltQP = null;
		try
		{
			if(is_null($wigiiBag)) throw new ElementServiceException('wigiiBag can not be null', ElementServiceException::INVALID_ARGUMENT);
			if(is_null($inGroupLogExp)) throw new ElementServiceException('inGroupLogExp can not be null', ElementServiceException::INVALID_ARGUMENT);

			// gets groupList
			$gAS = $this->getGroupAdminServiceImpl();
			$groupSelectionLogExp = $gAS->convertInGroupLogExp2GroupLogExp($inGroupLogExp);
			$groupList = GroupListArrayImpl::createInstance();
			$gAS->getSelectedGroupsWithoutDetail($principal, $groupSelectionLogExp, $groupList);

			// checks if ingrouplogexp is a single instance of LogExpInGroup
			// if yes, then redirects the call to getAllElementsInGroups with the grouplist
			if($inGroupLogExp instanceof LogExpInGroup) {
				$returnValue = $this->fillWigiiBagWithAllElementsInGroups($principal, $groupList, $wigiiBag, $inGroupLogExp->includeChildrenGroups(), $listFilter);
			}
			// else continues.
			else
			{
				// checks authorization
				$pRights = $this->assertPrincipalAuthorizedForGetSelectedElementsInGroups($principal, $groupList);
				$pRightsFromDb = !isset($pRights);

				$returnValue = null;

				// initializes pagination, etc.
				$fieldSelectorList = null;
				$fieldSelectorLogExp = null;
				$fieldSortingKeyList = null;
				$configGroupList = null;
				$queryPaged = false;
				if(isset($listFilter))
				{
					$fieldSelectorList = $listFilter->getFieldSelectorList();
					$fieldSelectorLogExp = $listFilter->getFieldSelectorLogExp();
					$fieldSortingKeyList = $listFilter->getFieldSortingKeyList();
					$configGroupList = $listFilter->getConfigGroupList();
					if($listFilter->isPaged())
					{
						$queryPaged = true;
						$pageSize = $listFilter->getPageSize();
						$offset = ($listFilter->getDesiredPageNumber() - 1) * $pageSize;
					}
				}
				if($configGroupList == null) $configGroupList = $groupList;

				// element in group selection
				$elementInGroupSqlB = $this->getSqlBuilderForSelectElementsInGroups($inGroupLogExp);

				// computes effective field list
				$eltQP = $this->getElementQueryPlanner(MySqlFacade::Q_SELECTALL, $this->getReservedSqlJoinsForGetSelectedElementsInGroups($elementInGroupSqlB, $pRightsFromDb),
								$fieldSelectorList, $fieldSelectorLogExp, $fieldSortingKeyList);
				$cS = $this->getConfigService();
				// if ConfigService supports method unselectSubElementConfig
				// then unselects any previous sub element config
				if($this->subElementConfigSupport['unselectSubElementConfig']) $cS->unselectSubElementConfig($principal);
				$cS->getGroupsFields($principal, $configGroupList, null, $eltQP);
	//			// ignores element detail
				$eltQP->ignoreElementDetail();

				// gets elements
				$wigiiBagFiller = $this->getWigiiBagFiller($principal, $wigiiBag, $pRights);
				$wigiiBagFiller->setTripod($this->getTripod());
				$dbCS = $this->getDbAdminService()->getDbConnectionSettings($principal);
				$n = $eltQP->getNumberOfQueries();
				$strategy = $eltQP->getQueryStrategy();
				$mysqlF = $this->getMySqlFacade();
				for($i = 0; $i < $n; $i++)
				{
					if($queryPaged)
					{
						$nbRows = $mysqlF->selectPage($principal, $eltQP->getSql($i,
							$this->getSqlBuilderForGetSelectedElementsInGroups($strategy, $elementInGroupSqlB, $principal, $groupList, $pRightsFromDb)),
							$dbCS, $offset, $pageSize,
							$wigiiBagFiller);
						if($i === 0) $listFilter->setTotalNumberOfObjects($nbRows);
					}
					else
					{
						$nbRows = $mysqlF->selectAll($principal, $eltQP->getSql($i,
							$this->getSqlBuilderForGetSelectedElementsInGroups($strategy, $elementInGroupSqlB, $principal, $groupList, $pRightsFromDb)),
							$dbCS,
							$wigiiBagFiller);
					}
					if(($i === 0) && ($nbRows === 0)) break;
				}
				$eltQP->freeMemory();
				$returnValue = $wigiiBagFiller->count();
				$wigiiBagFiller->freeMemory();
			}
		}
		catch(ElementServiceException $ese)
		{
			if(isset($eltQP)) $eltQP->freeMemory();
			$this->executionSink()->publishEndOperationOnError("fillWigiiBagWithSelectedElementsInGroups", $ese, $principal);
			throw $ese;
		}
		catch (AuthorizationServiceException $asE){
			if(isset($eltQP)) $eltQP->freeMemory();
			$this->executionSink()->publishEndOperationOnError("fillWigiiBagWithSelectedElementsInGroups", $asE, $principal);
			throw $asE;
		}
		catch(Exception $e)
		{
			if(isset($eltQP)) $eltQP->freeMemory();
			$this->executionSink()->publishEndOperationOnError("fillWigiiBagWithSelectedElementsInGroups", $e, $principal);
			throw new ElementServiceException('',ElementServiceException::WRAPPING, $e);
		}
		$this->executionSink()->publishEndOperation("fillWigiiBagWithSelectedElementsInGroups", $principal);
		return $returnValue;
	}

	public function createDbTableWithSelectedElementsInGroups($principal, $inGroupLogExp, $dbTable, $listFilter=null) {
		$this->executionSink()->publishStartOperation("createDbTableWithSelectedElementsInGroups", $principal);
		try
		{
			if(is_null($dbTable)) throw new ElementServiceException('dbTable can not be null', ElementServiceException::INVALID_ARGUMENT);
			$returnValue = $this->doFillDbTableWithSelectedElementsInGroups($principal, $inGroupLogExp,
				$this->getDbTableFiller($principal, $dbTable, true),
				$listFilter);
		}
		catch(ElementServiceException $ese)
		{
			$this->executionSink()->publishEndOperationOnError("createDbTableWithSelectedElementsInGroups", $ese, $principal);
			throw $ese;
		}
		catch (AuthorizationServiceException $asE){
			$this->executionSink()->publishEndOperationOnError("createDbTableWithSelectedElementsInGroups", $asE, $principal);
			throw $asE;
		}
		catch(Exception $e)
		{
			$this->executionSink()->publishEndOperationOnError("createDbTableWithSelectedElementsInGroups", $e, $principal);
			throw new ElementServiceException('',ElementServiceException::WRAPPING, $e);
		}
		$this->executionSink()->publishEndOperation("createDbTableWithSelectedElementsInGroups", $principal);
		return $returnValue;
	}
	public function fillDbTableWithSelectedElementsInGroups($principal, $inGroupLogExp, $dbTable, $listFilter=null) {
		$this->executionSink()->publishStartOperation("fillDbTableWithSelectedElementsInGroups", $principal);
		try
		{
			if(is_null($dbTable)) throw new ElementServiceException('dbTable can not be null', ElementServiceException::INVALID_ARGUMENT);
			$returnValue = $this->doFillDbTableWithSelectedElementsInGroups($principal, $inGroupLogExp,
				$this->getDbTableFiller($principal, $dbTable, false),
				$listFilter);
		}
		catch(ElementServiceException $ese)
		{
			$this->executionSink()->publishEndOperationOnError("fillDbTableWithSelectedElementsInGroups", $ese, $principal);
			throw $ese;
		}
		catch (AuthorizationServiceException $asE){
			$this->executionSink()->publishEndOperationOnError("fillDbTableWithSelectedElementsInGroups", $asE, $principal);
			throw $asE;
		}
		catch(Exception $e)
		{
			$this->executionSink()->publishEndOperationOnError("fillDbTableWithSelectedElementsInGroups", $e, $principal);
			throw new ElementServiceException('',ElementServiceException::WRAPPING, $e);
		}
		$this->executionSink()->publishEndOperation("fillDbTableWithSelectedElementsInGroups", $principal);
		return $returnValue;
	}
	/**
	 * Same authorizations as getSelectedElementsInGroups
	 */
	protected function doFillDbTableWithSelectedElementsInGroups($principal, $inGroupLogExp, $dbTableFiller, $listFilter=null) {
		$this->executionSink()->publishStartOperation("doFillDbTableWithSelectedElementsInGroups", $principal);
		$eltQP = null;
		try
		{
			if(is_null($inGroupLogExp)) throw new ElementServiceException('inGroupLogExp can not be null', ElementServiceException::INVALID_ARGUMENT);

			// gets groupList
			$gAS = $this->getGroupAdminServiceImpl();
			$groupSelectionLogExp = $gAS->convertInGroupLogExp2GroupLogExp($inGroupLogExp);
			$groupList = GroupListArrayImpl::createInstance();
			$gAS->getSelectedGroupsWithoutDetail($principal, $groupSelectionLogExp, $groupList);

			// checks if ingrouplogexp is a single instance of LogExpInGroup
			// if yes, then redirects the call to getAllElementsInGroups with the grouplist
			if($inGroupLogExp instanceof LogExpInGroup) {
				$returnValue = $this->doFillDbTableWithAllElementsInGroups($principal, $groupList, $dbTableFiller, $inGroupLogExp->includeChildrenGroups(), $listFilter);
			}
			// else continues.
			else
			{
				// checks authorization
				$pRights = $this->assertPrincipalAuthorizedForGetSelectedElementsInGroups($principal, $groupList);
				$pRightsFromDb = !isset($pRights);

				$returnValue = null;

				// initializes pagination, etc.
				$fieldSelectorList = null;
				$fieldSelectorLogExp = null;
				$fieldSortingKeyList = null;
				$configGroupList = null;
				$queryPaged = false;
				if(isset($listFilter))
				{
					$fieldSelectorList = $listFilter->getFieldSelectorList();
					$fieldSelectorLogExp = $listFilter->getFieldSelectorLogExp();
					$fieldSortingKeyList = $listFilter->getFieldSortingKeyList();
					$configGroupList = $listFilter->getConfigGroupList();
					if($listFilter->isPaged())
					{
						$queryPaged = true;
						$pageSize = $listFilter->getPageSize();
						$offset = ($listFilter->getDesiredPageNumber() - 1) * $pageSize;
					}
				}
				if($configGroupList == null) $configGroupList = $groupList;

				// element in group selection
				$elementInGroupSqlB = $this->getSqlBuilderForSelectElementsInGroups($inGroupLogExp);

				// computes effective field list
				$eltQP = $this->getElementQueryPlanner(MySqlFacade::Q_SELECTALL, $this->getReservedSqlJoinsForGetSelectedElementsInGroups($elementInGroupSqlB, $pRightsFromDb),
								$fieldSelectorList, $fieldSelectorLogExp, $fieldSortingKeyList);
				$eltQP->setRowSizeCutting(true); // activates row size cutting for create table.
				$cS = $this->getConfigService();
				// if ConfigService supports method unselectSubElementConfig
				// then unselects any previous sub element config
				if($this->subElementConfigSupport['unselectSubElementConfig']) $cS->unselectSubElementConfig($principal);
				$cS->getGroupsFields($principal, $configGroupList, null, $eltQP);
				// ignores element detail
				$eltQP->ignoreElementDetail();

				// gets elements
				$dbTableFiller->setTripod($this->getTripod());
				$dbCS = $this->getDbAdminService()->getDbConnectionSettings($principal);
				$n = $eltQP->getNumberOfQueries();
				$strategy = $eltQP->getQueryStrategy();
				$mysqlF = $this->getMySqlFacade();

				$finalDbTable = $dbTableFiller->getDbTable();
				if($n > 1) {

					$finalDbTableName = $finalDbTable->getTableName();
					$finalDbName = $finalDbTable->getDbName();
					$finalSqlCreateStatement = $finalDbTable->getTableCreationStatementDetail();
					for($i = 0; $i < $n; $i++)
					{
						// creates a temp table in final database with result of current query
						$sqlB = $this->getSqlBuilderForGetSelectedElementsInGroups($strategy, $elementInGroupSqlB, $principal, $groupList, $pRightsFromDb);
						$sqlB->setUserFriendlySqlColNames($finalDbTable->hasUserFriendlySqlColNames());
						if($finalDbTable->shouldHideDbRights()) $sqlB->hideDbRightsInSelectedCols();

						$sqlSelect = $eltQP->getSql($i, $sqlB);
						$tempDbTable = DbTable::createInstanceForSqlCreate($finalDbName, $finalDbTableName.$i, $finalSqlCreateStatement, false);
						if($queryPaged) {
							// create table, paged select
							$nbRows = $mysqlF->createTableFromSelectPage($principal, $tempDbTable, $sqlSelect,
											$dbCS, $offset, $pageSize);
						}
						else {
							// create table, select all
							$nbRows = $mysqlF->createTableFromSelectAll($principal, $tempDbTable, $sqlSelect, $dbCS);
						}
						// registers temp table
						$dbTableFiller->addTempTable($tempDbTable, $nbRows);
						if(($i == 0) && ($nbRows == 0)) break;
					}
					// changes db
// 					if($finalDbName != '') {
// 						$dbCS = clone $dbCS;
// 						$dbCS->setDbName($finalDbName);
// 					}
// 					$sqlSelect = $dbTableFiller->getSqlForSelectJoinedTempTables($pRightsFromDb);
				}
				elseif($n > 0) {
					$sqlB = $this->getSqlBuilderForGetSelectedElementsInGroups($strategy, $elementInGroupSqlB, $principal, $groupList, $pRightsFromDb);
					$sqlB->setUserFriendlySqlColNames($finalDbTable->hasUserFriendlySqlColNames());
					if($finalDbTable->shouldHideDbRights()) $sqlB->hideDbRightsInSelectedCols();

					$sqlSelect = $eltQP->getSql(0, $sqlB);
				}
				if($queryPaged && $n == 1)
				{
					if($dbTableFiller->shouldCreateTable()) {
						// create table, paged select
						$nbRows = $mysqlF->createTableFromSelectPage($principal, $finalDbTable, $sqlSelect,
									$dbCS, $offset, $pageSize);
					}
					else {
						// insert into table, paged select
						$nbRows = $mysqlF->insertIntoTableFromSelectPage($principal, $finalDbTable, $sqlSelect,
									$dbCS, $offset, $pageSize);
					}
					$listFilter->setTotalNumberOfObjects($nbRows);
				}
				//elseif($n > 0)
				elseif($n == 1)
				{
					if($dbTableFiller->shouldCreateTable()) {
						// create table, select all
						$nbRows = $mysqlF->createTableFromSelectAll($principal, $finalDbTable, $sqlSelect, $dbCS);
					}
					else {
						// insert into table, select all
						$nbRows = $mysqlF->insertIntoTableFromSelectAll($principal, $finalDbTable, $sqlSelect, $dbCS);
					}
					//if($n > 1) $nbRows = $dbTableFiller->getNbRows();
				}
				elseif($n > 1) $nbRows = $dbTableFiller->getNbRows();
				else $nbRows = 0;
				$returnValue = $nbRows;
				// cleanup of temp tables
				$this->cleanupTempTablesForDoFillDbTable($principal, $dbTableFiller, $mysqlF, $dbCS);
				$eltQP->freeMemory();
			}
		}
		catch(ElementServiceException $ese)
		{
			$this->cleanupTempTablesForDoFillDbTable($principal, $dbTableFiller, $mysqlF, $dbCS);
			if(isset($eltQP)) $eltQP->freeMemory();
			$this->executionSink()->publishEndOperationOnError("doFillDbTableWithSelectedElementsInGroups", $ese, $principal);
			throw $ese;
		}
		catch (AuthorizationServiceException $asE){
			$this->executionSink()->publishEndOperationOnError("doFillDbTableWithSelectedElementsInGroups", $asE, $principal);
			throw $asE;
		}
		catch(Exception $e)
		{
			$this->cleanupTempTablesForDoFillDbTable($principal, $dbTableFiller, $mysqlF, $dbCS);
			if(isset($eltQP)) $eltQP->freeMemory();
			$this->executionSink()->publishEndOperationOnError("doFillDbTableWithSelectedElementsInGroups", $e, $principal);
			throw new ElementServiceException('',ElementServiceException::WRAPPING, $e);
		}
		$this->executionSink()->publishEndOperation("doFillDbTableWithSelectedElementsInGroups", $principal);
		return $returnValue;
	}
	/**
	 * Same authorization as getAllElementsInGroups
	 */
	protected function doFillDbTableWithAllElementsInGroups($principal, $groupList, $dbTableFiller, $includeChildrenGroups=true, $listFilter=null) {
		$this->executionSink()->publishStartOperation("doFillDbTableWithAllElementsInGroups", $principal);
		$eltQP = null;
		try
		{
			// checks authorization
			$pRights = $this->assertPrincipalAuthorizedForGetAllElementsInGroups($principal, $groupList);
			$pRightsFromDb = !isset($pRights);

			// initializes pagination, etc.
			$fieldSelectorList = null;
			$fieldSelectorLogExp = null;
			$fieldSortingKeyList = null;
			$configGroupList = null;
			$queryPaged = false;
			if(isset($listFilter))
			{
				$fieldSelectorList = $listFilter->getFieldSelectorList();
				$fieldSelectorLogExp = $listFilter->getFieldSelectorLogExp();
				$fieldSortingKeyList = $listFilter->getFieldSortingKeyList();
				$configGroupList = $listFilter->getConfigGroupList();
				if($listFilter->isPaged())
				{
					$queryPaged = true;
					$pageSize = $listFilter->getPageSize();
					$offset = ($listFilter->getDesiredPageNumber() - 1) * $pageSize;
				}
			}
			if($configGroupList == null) $configGroupList = $groupList;

			// computes effective field list
			$eltQP = $this->getElementQueryPlanner(MySqlFacade::Q_SELECTALL, $this->getReservedSqlJoinsForGetAllElementsInGroups(),
							$fieldSelectorList, $fieldSelectorLogExp, $fieldSortingKeyList);
			$eltQP->setRowSizeCutting(true); // activates row size cutting for create table.
			$cS = $this->getConfigService();
			// if ConfigService supports method unselectSubElementConfig
			// then unselects any previous sub element config
			if($this->subElementConfigSupport['unselectSubElementConfig']) $cS->unselectSubElementConfig($principal);
			$cS->getGroupsFields($principal, $configGroupList, null, $eltQP);
			// ignores element detail
			$eltQP->ignoreElementDetail();

			// gets elements
			$dbTableFiller->setTripod($this->getTripod());
			$dbCS = $this->getDbAdminService()->getDbConnectionSettings($principal);
			$n = $eltQP->getNumberOfQueries();
			$strategy = $eltQP->getQueryStrategy();
			$mysqlF = $this->getMySqlFacade();

			$finalDbTable = $dbTableFiller->getDbTable();
			if($n > 1) {
				$finalDbTableName = $finalDbTable->getTableName();
				$finalDbName = $finalDbTable->getDbName();
				$finalSqlCreateStatement = $finalDbTable->getTableCreationStatementDetail();
				for($i = 0; $i < $n; $i++)
				{
					// creates a temp table in final database with result of current query
					$sqlB = $this->getSqlBuilderForGetAllElementsInGroups($principal, $strategy, $groupList, $pRightsFromDb, $includeChildrenGroups);
					$sqlB->setUserFriendlySqlColNames($finalDbTable->hasUserFriendlySqlColNames());
					if($finalDbTable->shouldHideDbRights()) $sqlB->hideDbRightsInSelectedCols();

					$sqlSelect = $eltQP->getSql($i, $sqlB);
					$tempDbTable = DbTable::createInstanceForSqlCreate($finalDbName, $finalDbTableName.$i, $finalSqlCreateStatement, false);
					//$this->debugLogger()->write("temp table ".$tempDbTable->getTableNameWithDbName()." has ".$dbTableFiller->fieldCounter." fields");
					if($queryPaged) {
						// create table, paged select
						$nbRows = $mysqlF->createTableFromSelectPage($principal, $tempDbTable, $sqlSelect,
										$dbCS, $offset, $pageSize);
					}
					else {
						// create table, select all
						$nbRows = $mysqlF->createTableFromSelectAll($principal, $tempDbTable, $sqlSelect, $dbCS);
					}
					// registers temp table
					$dbTableFiller->addTempTable($tempDbTable, $nbRows);
					if(($i == 0) && ($nbRows == 0)) break;
				}
				// changes db
// 				if($finalDbName != '') {
// 					$dbCS = clone $dbCS;
// 					$dbCS->setDbName($finalDbName);
// 				}
// 				$sqlSelect = $dbTableFiller->getSqlForSelectJoinedTempTables($pRightsFromDb);
			}
			elseif($n > 0) {
				$sqlB = $this->getSqlBuilderForGetAllElementsInGroups($principal, $strategy, $groupList, $pRightsFromDb, $includeChildrenGroups);
				$sqlB->setUserFriendlySqlColNames($finalDbTable->hasUserFriendlySqlColNames());
				if($finalDbTable->shouldHideDbRights()) $sqlB->hideDbRightsInSelectedCols();

				$sqlSelect = $eltQP->getSql(0, $sqlB);
			}
			if($queryPaged && $n == 1)
			{
				if($dbTableFiller->shouldCreateTable()) {
					// create table, paged select
					$nbRows = $mysqlF->createTableFromSelectPage($principal, $finalDbTable, $sqlSelect,
								$dbCS, $offset, $pageSize);
				}
				else {
					// insert into table, paged select
					$nbRows = $mysqlF->insertIntoTableFromSelectPage($principal, $finalDbTable, $sqlSelect,
								$dbCS, $offset, $pageSize);
				}
				$listFilter->setTotalNumberOfObjects($nbRows);
			}
			//elseif($n > 0)
			elseif($n == 1)
			{
				if($dbTableFiller->shouldCreateTable()) {
					// create table, select all
					$nbRows = $mysqlF->createTableFromSelectAll($principal, $finalDbTable, $sqlSelect, $dbCS);
				}
				else {
					// insert into table, select all
					$nbRows = $mysqlF->insertIntoTableFromSelectAll($principal, $finalDbTable, $sqlSelect, $dbCS);
				}
				//if($n > 1) $nbRows = $dbTableFiller->getNbRows();
			}
			elseif($n > 1) $nbRows = $dbTableFiller->getNbRows();
			else $nbRows = 0;
			// cleanup of temp tables
			$this->cleanupTempTablesForDoFillDbTable($principal, $dbTableFiller, $mysqlF, $dbCS);
			$eltQP->freeMemory();
		}
		catch(ElementServiceException $ese)
		{
			$this->cleanupTempTablesForDoFillDbTable($principal, $dbTableFiller, $mysqlF, $dbCS);
			if(isset($eltQP)) $eltQP->freeMemory();
			$this->executionSink()->publishEndOperationOnError("doFillDbTableWithAllElementsInGroups", $ese, $principal);
			throw $ese;
		}
		catch (AuthorizationServiceException $asE){
			$this->executionSink()->publishEndOperationOnError("doFillDbTableWithAllElementsInGroups", $asE, $principal);
			throw $asE;
		}
		catch(Exception $e)
		{
			$this->cleanupTempTablesForDoFillDbTable($principal, $dbTableFiller, $mysqlF, $dbCS);
			if(isset($eltQP)) $eltQP->freeMemory();
			$this->executionSink()->publishEndOperationOnError("doFillDbTableWithAllElementsInGroups", $e, $principal);
			throw new ElementServiceException('',ElementServiceException::WRAPPING, $e);
		}
		$this->executionSink()->publishEndOperation("doFillDbTableWithAllElementsInGroups", $principal);
		return $nbRows;
	}
	private function cleanupTempTablesForDoFillDbTable($principal, $dbTableFiller, $mysqlF, $cnxSettings) {
		$sqlDropTables = $dbTableFiller->getSqlForDropTempTables();
		if('' != $sqlDropTables) {
//			try {
//				//$mysqlF->execDDLCmd($principal, $sqlDropTables, $cnxSettings);
//			}
//			catch(Exception $e) {/* does nothing */}
		}
		$dbTableFiller->freeMemory();
	}
	
		
	public function countSelectedElementsDistributionInGroups($principal, $inGroupLogExp, $listFilter=null) {
		$this->executionSink()->publishStartOperation("countSelectedElementsDistributionInGroups", $principal);
		$eltQP = null;
		$returnValue=null;
		try
		{
			if(is_null($inGroupLogExp)) throw new ElementServiceException('inGroupLogExp can not be null', ElementServiceException::INVALID_ARGUMENT);
		
			// gets groupList
			$gAS = $this->getGroupAdminServiceImpl();
			$groupSelectionLogExp = $gAS->convertInGroupLogExp2GroupLogExp($inGroupLogExp);
			$groupList = GroupListArrayImpl::createInstance();
			$gAS->getSelectedGroupsWithoutDetail($principal, $groupSelectionLogExp, $groupList);
		
			// checks authorization
			$pRights = $this->assertPrincipalAuthorizedForGetSelectedElementsInGroups($principal, $groupList);
			$pRightsFromDb = !isset($pRights);
			
			// initializes pagination, sorting, etc.
			$fieldSelectorList = fsl(fs_e('id'));
			$fieldSelectorLogExp = null;
			$fieldSortingKeyList = null;
			$configGroupList = null;
			$queryPaged = false;
			if(isset($listFilter))
			{
				$fieldSelectorLogExp = $listFilter->getFieldSelectorLogExp();
				$configGroupList = $listFilter->getConfigGroupList();
			}
			if($configGroupList == null){
				$configGroupList = $groupList;
			}
			
			// checks if ingrouplogexp is a single instance of LogExpInGroup
			// if yes, then uses the calculated GroupList
			if($inGroupLogExp instanceof LogExpInGroup) {
				// ElementQueryPlanner configuration
				$reservedNumberOfJoins = $this->getReservedSqlJoinsForGetAllElementsInGroups();
				$sqlB = $this->getSqlBuilderForGetAllElementsInGroups($principal, ElementQueryPlanner::QSTRATEGY_JOIN, $groupList, $pRightsFromDb, $inGroupLogExp->includeChildrenGroups());
			}
			// else continues with the ingrouplogexp.
			else
			{
				// element in group selection
				$elementInGroupSqlB = $this->getSqlBuilderForSelectElementsInGroups($inGroupLogExp);
		
				// ElementQueryPlanner configuration
				$reservedNumberOfJoins = $this->getReservedSqlJoinsForGetSelectedElementsInGroups($elementInGroupSqlB, $pRightsFromDb);
				$sqlB = $this->getSqlBuilderForGetSelectedElementsInGroups($strategy, $elementInGroupSqlB, $principal, $groupList, $pRightsFromDb);
			}	
			// computes effective field list
			$eltQP = $this->getElementQueryPlanner(MySqlFacade::Q_SELECTALL, $reservedNumberOfJoins,
					$fieldSelectorList, $fieldSelectorLogExp, $fieldSortingKeyList);
	
			if($eltQP->areFieldSelected())
			{
				$cS = $this->getConfigService();
				// if ConfigService supports method unselectSubElementConfig
				// then unselects any previous sub element config
				if($this->subElementConfigSupport['unselectSubElementConfig']) $cS->unselectSubElementConfig($principal);
				$cS->getGroupsFields($principal, $configGroupList, null, $eltQP);
			}
	
			// gets elements
			$dbCS = $this->getDbAdminService()->getDbConnectionSettings($principal);
			$n = $eltQP->getNumberOfQueries();
			$strategy = $eltQP->getQueryStrategy();
			$mysqlF = $this->getMySqlFacade();
			if($n > 1 || $strategy != ElementQueryPlanner::QSTRATEGY_JOIN) {
				throw new ElementServiceException('unsupported query strategy for counting elements', ElementServiceException::UNSUPPORTED_OPERATION);
			}
	
			
			$returnValue = RowListForCountElementsInGroups::createInstance();
			
			$sql = "select tmp1.id_group, count(tmp1.id_element) as nbElements from Elements_Groups as tmp1
inner join (".$eltQP->getSql(0,$sqlB).") as tmp2 on tmp2.Eid = tmp1.id_element 
group by tmp1.id_group";
			
			$nbRows = $mysqlF->selectAll($principal,
					$sql,
					$dbCS,
					$returnValue);
			
			$returnValue = $returnValue->getCountData();
			
			$eltQP->freeMemory();
		}
		catch(ElementServiceException $ese)
		{
			if(isset($eltQP)) $eltQP->freeMemory();
			$this->executionSink()->publishEndOperationOnError("countSelectedElementsDistributionInGroups", $ese, $principal);
			throw $ese;
		}
		catch (AuthorizationServiceException $asE){
			if(isset($eltQP)) $eltQP->freeMemory();
			$this->executionSink()->publishEndOperationOnError("countSelectedElementsDistributionInGroups", $asE, $principal);
			throw $asE;
		}
		catch(Exception $e)
		{
			if(isset($eltQP)) $eltQP->freeMemory();
			$this->executionSink()->publishEndOperationOnError("countSelectedElementsDistributionInGroups", $e, $principal);
			throw new ElementServiceException('',ElementServiceException::WRAPPING, $e);
		}
		$this->executionSink()->publishEndOperation("countSelectedElementsDistributionInGroups", $principal);
		return $returnValue;
	}
	
	public function countSelectedElementsDistribution($principal, $elementIds) {
		$this->executionSink()->publishStartOperation("countSelectedElementsDistribution", $principal);
		try
		{			
			// checks authorization
			$pRights = $this->assertPrincipalAuthorizedForCountSelectedElementsDistribution($principal);
			
			$dbCS = $this->getDbAdminService()->getDbConnectionSettings($principal);
			$mysqlF = $this->getMySqlFacade();

			$returnValue = RowListForCountElementsInGroups::createInstance();

			$sql = "select tmp1.id_group, count(tmp1.id_element) as nbElements from Elements_Groups as tmp1
where tmp1.id_element IN (".implode(",", $elementIds).")
group by tmp1.id_group";
			$nbRows = $mysqlF->selectAll($principal,
				$sql,
				$dbCS,
				$returnValue);

			$returnValue = $returnValue->getCountData();
		}
		catch(ElementServiceException $ese)
		{
			$this->executionSink()->publishEndOperationOnError("countSelectedElementsDistribution", $ese, $principal);
			throw $ese;
		}
		catch (AuthorizationServiceException $asE){
			$this->executionSink()->publishEndOperationOnError("countSelectedElementsDistribution", $asE, $principal);
			throw $asE;
		}
		catch(Exception $e)
		{
			$this->executionSink()->publishEndOperationOnError("countSelectedElementsDistribution", $e, $principal);
			throw new ElementServiceException('',ElementServiceException::WRAPPING, $e);
		}
		$this->executionSink()->publishEndOperation("countSelectedElementsDistribution", $principal);
		return $returnValue;
	}
	protected function assertPrincipalAuthorizedForCountSelectedElementsDistribution($principal)
	{
		$autoS = $this->getAuthorizationService();
		// checks general authorization
		$pRights = $autoS->assertPrincipalAuthorized($principal, "ElementService", "countSelectedElementsDistribution");

		return $pRights;
	}

	public function countSelectedElementsInGroups($principal, $inGroupLogExp, $listFilter=null) {
		$this->executionSink()->publishStartOperation("countSelectedElementsInGroups", $principal);
		$eltQP = null;
		try
		{
			if(is_null($inGroupLogExp)) throw new ElementServiceException('inGroupLogExp can not be null', ElementServiceException::INVALID_ARGUMENT);

			// gets groupList
			$gAS = $this->getGroupAdminServiceImpl();
			$groupSelectionLogExp = $gAS->convertInGroupLogExp2GroupLogExp($inGroupLogExp);
			$groupList = GroupListArrayImpl::createInstance();
			$gAS->getSelectedGroupsWithoutDetail($principal, $groupSelectionLogExp, $groupList);

			// checks if ingrouplogexp is a single instance of LogExpInGroup
			// if yes, then redirects the call to getAllElementsInGroups with the grouplist
			if($inGroupLogExp instanceof LogExpInGroup) {
				$returnValue = $this->countAllElementsInGroups($principal, $groupList, $inGroupLogExp->includeChildrenGroups(), $listFilter);
			}
			// else continues.
			else
			{
				// checks authorization
				$pRights = $this->assertPrincipalAuthorizedForGetSelectedElementsInGroups($principal, $groupList);
				$pRightsFromDb = !isset($pRights);

				$returnValue = null;

				// initializes pagination, sorting, etc.
				$fieldSelectorList = fsl(fs_e('id'));
				$fieldSelectorLogExp = null;
				$fieldSortingKeyList = null;
				$configGroupList = null;
				$queryPaged = false;
				if(isset($listFilter))
				{
					//$fieldSelectorList = $listFilter->getFieldSelectorList();
					$fieldSelectorLogExp = $listFilter->getFieldSelectorLogExp();
					//$fieldSortingKeyList = $listFilter->getFieldSortingKeyList();
					$configGroupList = $listFilter->getConfigGroupList();
				}
				if($configGroupList == null){
					$configGroupList = $groupList;
				}

				// element in group selection
				$elementInGroupSqlB = $this->getSqlBuilderForSelectElementsInGroups($inGroupLogExp);

				// computes effective field list
				$eltQP = $this->getElementQueryPlanner(MySqlFacade::Q_SELECTALL, $this->getReservedSqlJoinsForGetSelectedElementsInGroups($elementInGroupSqlB, $pRightsFromDb),
								$fieldSelectorList, $fieldSelectorLogExp, $fieldSortingKeyList);

				if($eltQP->areFieldSelected())
				{
					$cS = $this->getConfigService();
					// if ConfigService supports method unselectSubElementConfig
					// then unselects any previous sub element config
					if($this->subElementConfigSupport['unselectSubElementConfig']) $cS->unselectSubElementConfig($principal);
					$cS->getGroupsFields($principal, $configGroupList, null, $eltQP);
				}

				// gets elements
				$dbCS = $this->getDbAdminService()->getDbConnectionSettings($principal);
				$n = $eltQP->getNumberOfQueries();
				$strategy = $eltQP->getQueryStrategy();
				$mysqlF = $this->getMySqlFacade();
				if($n > 1 || $strategy != ElementQueryPlanner::QSTRATEGY_JOIN) {
					throw new ElementServiceException('unsupported query strategy for counting elements', ElementServiceException::UNSUPPORTED_OPERATION);
				}

				if($n == 1) {
					$returnValue = $mysqlF->selectOneValue($principal,
							$this->getSqlForCountAllElementsInGroups($eltQP->getSql(0,
								$this->getSqlBuilderForGetSelectedElementsInGroups($strategy, $elementInGroupSqlB, $principal, $groupList, $pRightsFromDb))
							),
							$dbCS);
				}
				else $returnValue = 0;
				$eltQP->freeMemory();
			}
		}
		catch(ElementServiceException $ese)
		{
			if(isset($eltQP)) $eltQP->freeMemory();
			$this->executionSink()->publishEndOperationOnError("countSelectedElementsInGroups", $ese, $principal);
			throw $ese;
		}
		catch (AuthorizationServiceException $asE){
			if(isset($eltQP)) $eltQP->freeMemory();
			$this->executionSink()->publishEndOperationOnError("countSelectedElementsInGroups", $asE, $principal);
			throw $asE;
		}
		catch(Exception $e)
		{
			if(isset($eltQP)) $eltQP->freeMemory();
			$this->executionSink()->publishEndOperationOnError("countSelectedElementsInGroups", $e, $principal);
			throw new ElementServiceException('',ElementServiceException::WRAPPING, $e);
		}
		$this->executionSink()->publishEndOperation("countSelectedElementsInGroups", $principal);
		return $returnValue;
	}

	public function countAllElementsInGroups($principal, $groupList, $includeChildrenGroups=true, $listFilter=null) {
		$this->executionSink()->publishStartOperation("countAllElementsInGroups", $principal);
		$eltQP = null;
		try
		{
			// checks authorization
			$pRights = $this->assertPrincipalAuthorizedForGetAllElementsInGroups($principal, $groupList);
			$pRightsFromDb = !isset($pRights);

			$returnValue = null;

			// initializes pagination, sorting, etc.
			$fieldSelectorList = fsl(fs_e('id'));
			$fieldSelectorLogExp = null;
			$fieldSortingKeyList = null;
			$configGroupList = null;
			$queryPaged = false;
			if(isset($listFilter))
			{
				//$fieldSelectorList = $listFilter->getFieldSelectorList();
				$fieldSelectorLogExp = $listFilter->getFieldSelectorLogExp();
				//$fieldSortingKeyList = $listFilter->getFieldSortingKeyList();
				$configGroupList = $listFilter->getConfigGroupList();		
			}
			if($configGroupList == null){
				$configGroupList = $groupList;
			}

			// computes effective field list
			$eltQP = $this->getElementQueryPlanner(MySqlFacade::Q_SELECTALL, $this->getReservedSqlJoinsForGetAllElementsInGroups(),
							$fieldSelectorList, $fieldSelectorLogExp, $fieldSortingKeyList);
			if($eltQP->areFieldSelected())
			{
				$cS = $this->getConfigService();
				// if ConfigService supports method unselectSubElementConfig
				// then unselects any previous sub element config
				if($this->subElementConfigSupport['unselectSubElementConfig']) $cS->unselectSubElementConfig($principal);
				$cS->getGroupsFields($principal, $configGroupList, null, $eltQP);
			}

			// gets elements
			$dbCS = $this->getDbAdminService()->getDbConnectionSettings($principal);
			$n = $eltQP->getNumberOfQueries();
			$strategy = $eltQP->getQueryStrategy();
			$mysqlF = $this->getMySqlFacade();
			if($n > 1 || $strategy != ElementQueryPlanner::QSTRATEGY_JOIN) {
				throw new ElementServiceException('unsupported query strategy for counting elements', ElementServiceException::UNSUPPORTED_OPERATION);
			}

			if($n == 1) {
				$returnValue = $mysqlF->selectOneValue($principal,
					$this->getSqlForCountAllElementsInGroups($eltQP->getSql(0,
						$this->getSqlBuilderForGetAllElementsInGroups($principal, $strategy, $groupList, $pRightsFromDb, $includeChildrenGroups))
					),
					$dbCS);
			}
			else $returnValue = 0;
			$eltQP->freeMemory();
		}
		catch(ElementServiceException $ese)
		{
			if(isset($eltQP)) $eltQP->freeMemory();
			$this->executionSink()->publishEndOperationOnError("countAllElementsInGroups", $ese, $principal);
			throw $ese;
		}
		catch (AuthorizationServiceException $asE){
			if(isset($eltQP)) $eltQP->freeMemory();
			$this->executionSink()->publishEndOperationOnError("countAllElementsInGroups", $asE, $principal);
			throw $asE;
		}
		catch(Exception $e)
		{
			if(isset($eltQP)) $eltQP->freeMemory();
			$this->executionSink()->publishEndOperationOnError("countAllElementsInGroups", $e, $principal);
			throw new ElementServiceException('',ElementServiceException::WRAPPING, $e);
		}
		$this->executionSink()->publishEndOperation("countAllElementsInGroups", $principal);
		return $returnValue;
	}
	protected function getSqlForCountAllElementsInGroups($sqlForGetAllElementsInGroups) {
		return "select count(countE.Eid) from (".$sqlForGetAllElementsInGroups.") as countE";
	}

	public function fillElement($principal, $element, $fieldSelectorList=null)
	{
		$this->executionSink()->publishStartOperation("fillElement", $principal);
		$elementPMapper = null;
		$eltQP = null;
		try
		{
			if(is_null($element)) throw new ElementServiceException('element can not be null', ElementServiceException::INVALID_ARGUMENT);
			if(is_null($element->getFieldList())) throw new ElementServiceException('element->fieldList can not be null', ElementServiceException::INVALID_ARGUMENT);
			if(is_null($element->getWigiiBag())) throw new ElementServiceException('element->wigiiBag can not be null', ElementServiceException::INVALID_ARGUMENT);
			$elementId = $element->getId();
			if(!isset($elementId)) throw new ElementServiceException('element id can not be null', ElementServiceException::INVALID_ARGUMENT);

			// checks authorization
			$pRights = $this->assertPrincipalAuthorizedForFillElement($principal);
			$pRightsFromDb = !isset($pRights);

			$returnValue = null;
			$dbCS = $this->getDbAdminService()->getDbConnectionSettings($principal);
			$mysqlF = $this->getMySqlFacade();
			// retrieves element
			$dbRow = $mysqlF->selectOne($principal,
						$this->getSqlForGetElement($elementId),
						$dbCS);
			if(isset($dbRow))
			{
				$isSubElement = isset($dbRow['id_element_parent']);
				$module = $this->getModuleAdminService()->getModule($principal, $dbRow['module']);
				// checks module access (ignored for sub element)
				if(is_null($module)) throw new ElementServiceException('element can not have a null module in database', ElementServiceException::DATA_INTEGRITY_ERROR);
				if(is_null($pRights) && !$isSubElement && is_null($principal->getModuleAccess($module)))
				{
					$this->getAuthorizationService()->fail($principal, 'has not access to module '.$module->getModuleName());
				}

				// fills element
				$element->setModule($module);
				$element->fillFromArray($dbRow);
				//eput($dbRow);

				// if sub element, then checks read rights on root element and centers config service
				if($isSubElement) {
					$cS = $this->getSubElementConfigService();
					// centers the configuration on subelement if not done yet
					$ls = $cS->getCurrentLinkSelector();
					if(!isset($ls) ||
						$ls->getOwnerElementId() != $element->getElementParentId() ||
						$ls->getFieldName() != $element->getLinkName() ||
						$cS->getCurrentModule() !== $module) {
						$cS->selectSubElementConfig($principal, $elementId);
					}

					// fetches root element with principal rights
					$pathFromRoot = $cS->getCurrentSubElementPathFromRoot();
					$rootLs = $pathFromRoot->getFirstLinkSelector();
					$rootElementP = $this->getElementPWithoutFields($principal, $rootLs->getOwnerElementId());

					// checks authorization
					$pRightsForSubElement = $this->assertPrincipalAuthorizedForFillSubElement($principal, $rootElementP);
					$pRightsForSubElement->addPrincipalRights($pRights);
					$pRights = $pRightsForSubElement;
					$pRightsFromDb = false;
				}
				else {
					$cS = $this->getConfigService();
					// if ConfigService supports method unselectSubElementConfig
					// then unselects any previous sub element config
					if($this->subElementConfigSupport['unselectSubElementConfig']) $cS->unselectSubElementConfig($principal);
				}

				// computes effective field list
				$eltQP = $this->getElementQueryPlanner(MySqlFacade::Q_SELECTONE, $this->getReservedSqlJoinsForFillElement(), $fieldSelectorList);
				$eltQP->ignoreElementDetail();
				$cS->getFields($principal, $module, null, $eltQP);

				// gets element fields
				$elementPMapper = $this->getElementPMapper($principal, $element, $pRights,
					$this->getElementEvaluatorForModule($principal, $module));
				$elementPMapper->setTripod($this->getTripod());
				$n = $eltQP->getNumberOfQueries();
				$strategy = $eltQP->getQueryStrategy();
				for($i = 0; $i < $n; $i++)
				{
//					eput($eltQP->getSql($i,
//						$this->getSqlBuilderForFillElement($principal, $strategy, $element, ($pRightsFromDb && ($i === 0)))));
					$nbRows = $mysqlF->selectAll($principal, $eltQP->getSql($i,
						$this->getSqlBuilderForFillElement($principal, $strategy, $element, ($pRightsFromDb && ($i === 0)))),
						$dbCS,
						$elementPMapper);
					if(($i === 0) && ($nbRows === 0)) break;
				}
				$eltQP->freeMemory();
				$returnValue = $elementPMapper->getElementP();

				// recenters config service on subelement in case some func exp did change current config
				if($isSubElement) {
					// centers the configuration on subelement if not done yet
					$ls = $cS->getCurrentLinkSelector();
					if(!isset($ls) ||
						$ls->getOwnerElementId() != $element->getElementParentId() ||
						$ls->getFieldName() != $element->getLinkName() ||
						$cS->getCurrentModule() !== $module) {
						$cS->selectSubElementConfig($principal, $elementId);
					}
				}
			}
		}
		catch(ElementServiceException $ese)
		{
			if(isset($elementPMapper)) $elementPMapper->freeMemory();
			if(isset($eltQP)) $eltQP->freeMemory();
			$this->executionSink()->publishEndOperationOnError("fillElement", $ese, $principal);
			throw $ese;
		}
		catch (AuthorizationServiceException $asE){
			if(isset($elementPMapper)) $elementPMapper->freeMemory();
			if(isset($eltQP)) $eltQP->freeMemory();
			$this->executionSink()->publishEndOperationOnError("fillElement", $asE, $principal);
			throw $asE;
		}
		catch(Exception $e)
		{
			if(isset($elementPMapper)) $elementPMapper->freeMemory();
			if(isset($eltQP)) $eltQP->freeMemory();
			$this->executionSink()->publishEndOperationOnError("fillElement", $e, $principal);
			throw new ElementServiceException('',ElementServiceException::WRAPPING, $e);
		}
		$this->executionSink()->publishEndOperation("fillElement", $principal);
		return $returnValue;
	}
	protected function assertPrincipalAuthorizedForFillElement($principal)
	{
		// checks general authorization
		return $this->getAuthorizationService()->assertPrincipalAuthorized($principal, "ElementService", "fillElement");
		//no specific check is needed, because if the principal has no rights on the element --> no fields
	}
	protected function assertPrincipalAuthorizedForFillSubElement($principal, $rootElementP)
	{
		$pRightsOnRoot = $rootElementP->getRights();
		//check read rights on root element
		if($pRightsOnRoot == null){
			$autoS->fail($principal, 'has no right to get a sub element of element '.$rootElementP->getId());
		}
		return $pRightsOnRoot;
	}
	protected function getSqlForGetElement($elementId, $colPrefix='')
	{
		$sqlB = $this->getMySqlFacade()->getSqlBuilder();
		$id_element = $sqlB->formatBinExp('E.id_element', '=', $elementId, MySqlQueryBuilder::SQLTYPE_INT);
		return "select ".$this->getSqlColumnsForElement('E', $colPrefix)." from Elements as E where $id_element";
	}
	public function setSqlBuilderForFillElement($queryStrategy, $sqlBuilderForFillElement)
	{
		$this->sqlBuilderForFillElement[$queryStrategy] = $sqlBuilderForFillElement;
	}
	protected function getSqlBuilderForFillElement($principal, $queryStrategy, $element, $pRightsFromDb)
	{
		$sqlB = $this->doGetSqlBuilderForFillElement($queryStrategy);
		$sqlB->setTripod($this->getTripod());
		$sqlB->reset($principal, $element, $pRightsFromDb);
		return $sqlB;
	}
	protected function getReservedSqlJoinsForFillElement()
	{
		return $this->doGetSqlBuilderForFillElement(ElementQueryPlanner::QSTRATEGY_JOIN)->getNumberOfStaticJoins();
	}
	/**
	 * Returns a non initialized query builder for FillElement according to strategy
	 */
	protected function doGetSqlBuilderForFillElement($queryStrategy)
	{
		// autowired
		if(!isset($this->sqlBuilderForFillElement)) $this->sqlBuilderForFillElement = array();
		$sqlB = $this->sqlBuilderForFillElement[$queryStrategy];
		if(!isset($sqlB))
		{
			switch($queryStrategy)
			{
				case ElementQueryPlanner::QSTRATEGY_JOIN:
				case ElementQueryPlanner::QSTRATEGY_DATATYPE:
					$sqlB = ElementSqlBuilderForFillElement::createNonInitInstance();
					$this->sqlBuilderForFillElement[ElementQueryPlanner::QSTRATEGY_JOIN] = $sqlB;
					$this->sqlBuilderForFillElement[ElementQueryPlanner::QSTRATEGY_DATATYPE] = $sqlB;
					break;
				default: throw new ElementServiceException('unsupported query strategy', ElementServiceException::INVALID_ARGUMENT);
			}
		}
		return $sqlB;
	}

	public function getGroupsWithoutDetailContainingAllElements($principal, $elementPAdvancedList, $groupList, $listFilter=null)
	{
		$this->executionSink()->publishStartOperation("getGroupsWithoutDetailContainingAllElements", $principal);
		$groupMapper = null;
		try
		{
			// checks authorization
			$this->assertPrincipalAuthorizedForGetGroupsWithoutDetailContainingAllElements($principal, $elementPAdvancedList);

			// initiliazes pagination, sorting, etc.
			$fieldSelectorList = null;
			$groupLogExp = null;
			$groupSortingKeyList = null;
			$queryPaged = false;
			if(isset($listFilter))
			{
				$fieldSelectorList = $listFilter->getFieldSelectorList();
				$groupLogExp = $listFilter->getFieldSelectorLogExp();
				$groupSortingKeyList = $listFilter->getFieldSortingKeyList();
				if($listFilter->isPaged())
				{
					$queryPaged = true;
					$pageSize = $listFilter->getPageSize();
					$offset = ($listFilter->getDesiredPageNumber() - 1) * $pageSize;
				}
			}
			$groupMapper = $this->getGroupListMapper($principal, $groupList, false);
			if($queryPaged)
			{
				$listFilter->setTotalNumberOfObjects($this->getMySqlFacade()->selectPage($principal,
						$this->getSqlForGetGroupsWithoutDetailsContainingAllElements($principal, $elementPAdvancedList, $fieldSelectorList, $groupLogExp, $groupSortingKeyList),
						$this->getDbAdminService()->getDbConnectionSettings($principal), $offset, $pageSize,
						$groupMapper));
			}
			else
			{
				$this->getMySqlFacade()->selectAll($principal,
						$this->getSqlForGetGroupsWithoutDetailsContainingAllElements($principal, $elementPAdvancedList, $fieldSelectorList, $groupLogExp, $groupSortingKeyList),
						$this->getDbAdminService()->getDbConnectionSettings($principal),
						$groupMapper);
			}
			$returnValue = $groupMapper->count();
			$groupMapper->freeMemory();
		}
		catch (ElementServiceException $esE){
			if(isset($groupMapper)) $groupMapper->freeMemory();
			$this->executionSink()->publishEndOperationOnError("getGroupsWithoutDetailContainingAllElements", $esE, $principal);
			throw $esE;
		}
		catch (AuthorizationServiceException $asE){
			if(isset($groupMapper)) $groupMapper->freeMemory();
			$this->executionSink()->publishEndOperationOnError("getGroupsWithoutDetailContainingAllElements", $asE, $principal);
			throw $asE;
		}
		catch(Exception $e)
		{
			if(isset($groupMapper)) $groupMapper->freeMemory();
			$this->executionSink()->publishEndOperationOnError("getGroupsWithoutDetailContainingAllElements", $e, $principal);
			throw new ElementServiceException('',ElementServiceException::WRAPPING, $e);
		}
		$this->executionSink()->publishEndOperation("getGroupsWithoutDetailContainingAllElements", $principal);
		return $returnValue;
	}
	protected function assertPrincipalAuthorizedForGetGroupsWithoutDetailContainingAllElements($principal, $elementPAdvancedList)
	{
		if(is_null($elementPAdvancedList)) throw new ElementServiceException('elementPAdvancedList can not be null', ElementServiceException::INVALID_ARGUMENT);
		$module = reset($elementPAdvancedList->getListIterator())->getElement()->getModule();
		if(is_null($module)) throw new ElementServiceException('first element->module can not be null', ElementServiceException::INVALID_ARGUMENT);

		$autoS = $this->getAuthorizationService();
		// checks general authorization
		$autoS->assertPrincipalAuthorized($principal, "ElementService", "getGroupsWithoutDetailContainingAllElements");
		// checks module access if not root
		if(!$autoS->isRootPrincipal($principal) && !$autoS->isPublicPrincipal($principal))
		{
			if(is_null($principal->getModuleAccess($module))) $autoS->fail($principal, 'has no access to module '.$module->getModuleName());
		}
	}
	//fieldSelectorList is ignored, because we don't take the details in any case
	protected function getSqlForGetGroupsWithoutDetailsContainingAllElements($principal, $elementPAdvancedList, $fieldSelectorList=null, $groupLogExp=null, $groupSortingKeyList=null)
	{
		if(is_null($elementPAdvancedList)) throw new ElementServiceException("elementPAdvancedList cannot be null", ElementServiceException::INVALID_ARGUMENT);
		if(is_null($principal)) throw new ElementServiceException("principal cannot be null", ElementServiceException::INVALID_ARGUMENT);
		$elementIds = $elementPAdvancedList->getElementIds();
		if(is_null($elementIds)) throw new ElementServiceException("elementIds cannot be null", ElementServiceException::INVALID_ARGUMENT);


		$sqlB = $this->getMySqlFacade()->getSqlBuilder();
//		$result = ""; //lookup to groups that contains all elements and not only a part of it
//		$nbElements = 0;
//		foreach($elementIds as $elementId){
//			$nbElements++;
//			if($result != null) $result .= " AND ";
//			$result .= $sqlB->formatBinExp("EG.id_element", "=", $elementId, MySqlQueryBuilder::SQLTYPE_INT);
//		}
//		$elementIds = $result;

		$nbElements = count($elementIds);

		$gCols = $this->getGroupAdminServiceImpl()->getSqlColumnsForGroup('G', $this->getGroupAdminServiceImpl()->getFieldSelectorListForGroupWithoutDetail());

		$returnValue .= "select $gCols from Groups as G ";

		$subQuery = " select eg.id_group FROM Elements_Groups eg WHERE ".$sqlB->formatBinExp("eg.id_element", "IN", $elementIds, MySqlQueryBuilder::SQLTYPE_INT)." group by eg.id_group HAVING count(eg.id_element_group) = $nbElements ";

		// adds where clause
		if(!is_null($groupLogExp))
		{
			// reduces logExp and clones it.
			$groupLogExp = $groupLogExp->reduceNegation(true);
			$groupLogExp = $sqlB->buildWhereClause($groupLogExp);
			if(!is_null($groupLogExp) && $groupLogExp != '') $groupLogExp = " and ".$groupLogExp;
		}
		// order by clause
		if(!is_null($groupSortingKeyList))
		{
			$sqlB->convertFieldSortingKeyListToOrderByClause($groupSortingKeyList);
			$orderByClause = ' '.$sqlB->getOrderByClause();
		} else $orderByClause=' order by G.groupname';
		$returnValue .= " where G.id_group IN ($subQuery)".$groupLogExp.$orderByClause;

//		echo alert($returnValue);

		return $returnValue;
	}

	public function getAllGroupsWithoutDetailContainingElements($principal, $elementPAdvancedList, $groupList, $listFilter=null)
	{
		$this->executionSink()->publishStartOperation("getAllGroupsWithoutDetailContainingElements", $principal);
		$groupMapper = null;
		try
		{
			// checks authorization
			$this->assertPrincipalAuthorizedForGetAllGroupsWithoutDetailContainingElements($principal, $elementPAdvancedList);

			// initiliazes pagination, sorting, etc.
			$fieldSelectorList = null;
			$groupLogExp = null;
			$groupSortingKeyList = null;
			$queryPaged = false;
			if(isset($listFilter))
			{
				$fieldSelectorList = $listFilter->getFieldSelectorList();
				$groupLogExp = $listFilter->getFieldSelectorLogExp();
				$groupSortingKeyList = $listFilter->getFieldSortingKeyList();
				if($listFilter->isPaged())
				{
					$queryPaged = true;
					$pageSize = $listFilter->getPageSize();
					$offset = ($listFilter->getDesiredPageNumber() - 1) * $pageSize;
				}
			}
			$groupMapper = $this->getGroupListMapper($principal, $groupList, false);
			if($queryPaged)
			{
				$listFilter->setTotalNumberOfObjects($this->getMySqlFacade()->selectPage($principal,
						$this->getSqlForGetAllGroupsWithoutDetailsContainingElements($principal, $elementPAdvancedList, $fieldSelectorList, $groupLogExp, $groupSortingKeyList),
						$this->getDbAdminService()->getDbConnectionSettings($principal), $offset, $pageSize,
						$groupMapper));
			}
			else
			{
				$this->getMySqlFacade()->selectAll($principal,
						$this->getSqlForGetAllGroupsWithoutDetailsContainingElements($principal, $elementPAdvancedList, $fieldSelectorList, $groupLogExp, $groupSortingKeyList),
						$this->getDbAdminService()->getDbConnectionSettings($principal),
						$groupMapper);
			}
			$returnValue = $groupMapper->count();
			$groupMapper->freeMemory();
		}
		catch (ElementServiceException $esE){
			if(isset($groupMapper)) $groupMapper->freeMemory();
			$this->executionSink()->publishEndOperationOnError("getAllGroupsWithoutDetailContainingElements", $esE, $principal);
			throw $esE;
		}
		catch (AuthorizationServiceException $asE){
			if(isset($groupMapper)) $groupMapper->freeMemory();
			$this->executionSink()->publishEndOperationOnError("getAllGroupsWithoutDetailContainingElements", $asE, $principal);
			throw $asE;
		}
		catch(Exception $e)
		{
			if(isset($groupMapper)) $groupMapper->freeMemory();
			$this->executionSink()->publishEndOperationOnError("getAllGroupsWithoutDetailContainingElements", $e, $principal);
			throw new ElementServiceException('',ElementServiceException::WRAPPING, $e);
		}
		$this->executionSink()->publishEndOperation("getAllGroupsWithoutDetailContainingElements", $principal);
		return $returnValue;
	}
	protected function assertPrincipalAuthorizedForGetAllGroupsWithoutDetailContainingElements($principal, $elementPAdvancedList)
	{
		if(is_null($elementPAdvancedList)) throw new ElementServiceException('elementPAdvancedList can not be null', ElementServiceException::INVALID_ARGUMENT);
		$module = reset($elementPAdvancedList->getListIterator())->getElement()->getModule();
		if(is_null($module)) throw new ElementServiceException('first element->module can not be null', ElementServiceException::INVALID_ARGUMENT);

		$autoS = $this->getAuthorizationService();
		// checks general authorization
		$autoS->assertPrincipalAuthorized($principal, "ElementService", "getAllGroupsWithoutDetailContainingElements");
		// checks module access if not root
		if(!$autoS->isRootPrincipal($principal) && !$autoS->isPublicPrincipal($principal))
		{
			if(is_null($principal->getModuleAccess($module))) $autoS->fail($principal, 'has no access to module '.$module->getModuleName());
		}
	}
	//fieldSelectorList is ignored, because we don't take the details in any case
	protected function getSqlForGetAllGroupsWithoutDetailsContainingElements($principal, $elementPAdvancedList, $fieldSelectorList=null, $groupLogExp=null, $groupSortingKeyList=null)
	{
		if(empty($elementPAdvancedList)) throw new ElementServiceException("elementPAdvancedList cannot be null", ElementServiceException::INVALID_ARGUMENT);
		if(is_null($principal)) throw new ElementServiceException("principal cannot be null", ElementServiceException::INVALID_ARGUMENT);
		if(is_array($elementPAdvancedList)) $elementIds = $elementPAdvancedList;
		else $elementIds = $elementPAdvancedList->getElementIds();
		if(is_null($elementIds)) throw new ElementServiceException("elementIds cannot be null", ElementServiceException::INVALID_ARGUMENT);


		$sqlB = $this->getMySqlFacade()->getSqlBuilder();
		$elementIds = $sqlB->formatBinExp("EG.id_element", "IN", $elementIds, MySqlQueryBuilder::SQLTYPE_INT);

		$gCols = $this->getGroupAdminServiceImpl()->getSqlColumnsForGroup('G', $this->getGroupAdminServiceImpl()->getFieldSelectorListForGroupWithoutDetail());

		$returnValue .= "select $gCols from Groups as G inner join Elements_Groups EG on EG.id_group = G.id_group";

		// adds where clause
		if(!is_null($groupLogExp))
		{
			// reduces logExp and clones it.
			$groupLogExp = $groupLogExp->reduceNegation(true);
			$sqlB2 = $this->getGroupAdminServiceImpl()->getSqlWhereClauseBuilderForSelectGroups('G');
			$groupLogExp = $sqlB2->buildWhereClause($groupLogExp);
			if(!is_null($groupLogExp) && $groupLogExp != '') $groupLogExp = " and ".$groupLogExp;
		}
		// order by clause
		if(!is_null($groupSortingKeyList))
		{
			$sqlB->convertFieldSortingKeyListToOrderByClause($groupSortingKeyList);
			$orderByClause = ' '.$sqlB->getOrderByClause();
		} else $orderByClause=' order by G.groupname';
		$returnValue .= " where $elementIds".$groupLogExp." group by G.id_group ".$orderByClause;
		return $returnValue;
	}

	/**
	 * getGroupsPathContainingElement
	 * @return array[id_group]=array([id_group_parent]=>groupParentName,...,[id_group]=>[groupname]
	 * id_group is the group id of the group containing elements (can be multiple)
	 * if the wigiiNamespace of a group is different than the principal wigiiNamespace the groupname is prefixed with "wigiiNamespace : "
	 */
	private $cache_getGroupsPathContainingElement = null;
	public function getGroupsPathContainingElement($principal, $elementP){
		$this->executionSink()->publishStartOperation("getGroupsPathContainingElement", $principal);
		try
		{
			// checks authorization
			$pRights = $this->assertPrincipalAuthorizedForGetGroupsPathContainingElement($principal, $elementP);
			$pRightsFromDb = !isset($pRights);

			$key = $elementP->getId();
			if(!isset($this->cache_getGroupsPathContainingElement)) $this->cache_getGroupsPathContainingElement = array();
			if(!isset($this->cache_getGroupsPathContainingElement[$key])){
				$rowList = $this->getGroupAdminServiceImpl()->createRowListGroupPathImplInstance($principal->getWigiiNamespace()->getWigiiNamespaceName());
				$this->getMySqlFacade()->selectAll($principal,
					$this->getSqlForGetGroupsPathContainingElement($principal, $elementP),
					$this->getDbAdminService()->getDbConnectionSettings($principal),
					$rowList);
				$this->cache_getGroupsPathContainingElement[$key] = $rowList->getPaths();
			}

			$returnValue = $this->cache_getGroupsPathContainingElement[$key];

		}
		catch (ElementServiceException $esE){
			$this->executionSink()->publishEndOperationOnError("getGroupsPathContainingElement", $esE, $principal);
			throw $esE;
		}
		catch (AuthorizationServiceException $asE){
			$this->executionSink()->publishEndOperationOnError("getGroupsPathContainingElement", $asE, $principal);
			throw $asE;
		}
		catch(Exception $e)
		{
			$this->executionSink()->publishEndOperationOnError("getGroupsPathContainingElement", $e, $principal);
			throw new ElementServiceException('',ElementServiceException::WRAPPING, $e);
		}
		$this->executionSink()->publishEndOperation("getGroupsPathContainingElement", $principal);
		return $returnValue;
	}
	protected function assertPrincipalAuthorizedForGetGroupsPathContainingElement($principal, $elementP)
	{
		if(is_null($elementP)) throw new ElementServiceException('element can not be null', ElementServiceException::INVALID_ARGUMENT);
		$module = $elementP->getDbEntity()->getModule();
		if(is_null($module)) throw new ElementServiceException('element->module can not be null', ElementServiceException::INVALID_ARGUMENT);

		$autoS = $this->getAuthorizationService();
		// checks general authorization
		$pRights = $autoS->assertPrincipalAuthorized($principal, "ElementService", "getGroupsPathContainingElement");
		// checks module access
		if(is_null($pRights) && is_null($principal->getModuleAccess($module))) $autoS->fail($principal, 'has no access to module '.$module->getModuleName());
		return $pRights;
	}
	protected function getSqlForGetGroupsPathContainingElement($principal, $elementP)
	{
		if(is_null($elementP)) throw new ElementServiceException("element cannot be null", ElementServiceException::INVALID_ARGUMENT);
		if(is_null($principal)) throw new ElementServiceException("principal cannot be null", ElementServiceException::INVALID_ARGUMENT);
		$elementId = $elementP->getId();
		if(is_null($elementId)) throw new ElementServiceException("element->id cannot be null", ElementServiceException::INVALID_ARGUMENT);

		$sqlB = $this->getGroupAdminServiceImpl()->getSqlWhereClauseBuilderForSelectGroups('G');

		//the result are sorted DESC on isParent. --> that allows then the rowListGroupPathImpl to already have the parent
		//details when acting on the groups containing the Element
		$returnValue = "
select G.id_group as id, G.groupname as groupname, G.wigiiNamespace as wigiiNamespace, G.id_group_parent as id_group_parent, 0 as 'isParent' from Groups as G
inner join Elements_Groups EG on EG.id_group = G.id_group
where EG.id_element = ".$elementId."
union
select G2.id_group as id, G2.groupname as groupname, G2.wigiiNamespace as wigiiNamespace, G2.id_group_parent as id_group_parent, 1 as 'isParent' from Groups as G2
inner join Groups_Groups GG on GG.id_group_owner = G2.id_group
where GG.id_group IN (select EG2.id_group FROM Elements_Groups EG2
where EG2.id_element = ".$elementId.")
group by id
order by isParent DESC
";
		return $returnValue;
	}
	public function getAllGroupsContainingElement($principal, $element, $groupPList, $listFilter=null)
	{
		$this->executionSink()->publishStartOperation("getAllGroupsContainingElement", $principal);
		$groupPMapper = null;
		try
		{
			// checks authorization
			$pRights = $this->assertPrincipalAuthorizedForGetAllGroupsContainingElement($principal, $element);
			$pRightsFromDb = !isset($pRights);

			// initiliazes pagination, sorting, etc.
			$fieldSelectorList = null;
			$groupLogExp = null;
			$groupSortingKeyList = null;
			$queryPaged = false;
			if(isset($listFilter))
			{
				$fieldSelectorList = $listFilter->getFieldSelectorList();
				$groupLogExp = $listFilter->getFieldSelectorLogExp();
				$groupSortingKeyList = $listFilter->getFieldSortingKeyList();
				if($listFilter->isPaged())
				{
					$queryPaged = true;
					$pageSize = $listFilter->getPageSize();
					$offset = ($listFilter->getDesiredPageNumber() - 1) * $pageSize;
				}
			}

			$groupPMapper = $this->getGroupPListMapper($principal, $groupPList, $pRights);
			if($queryPaged)
			{
				$listFilter->setTotalNumberOfObjects($this->getMySqlFacade()->selectPage($principal,
						$this->getSqlForGetAllGroupsContainingElement($principal, $element, $pRightsFromDb, $fieldSelectorList, $groupLogExp, $groupSortingKeyList),
						$this->getDbAdminService()->getDbConnectionSettings($principal), $offset, $pageSize,
						$groupPMapper));
			}
			else
			{
				$this->getMySqlFacade()->selectAll($principal,
						$this->getSqlForGetAllGroupsContainingElement($principal, $element, $pRightsFromDb, $fieldSelectorList, $groupLogExp, $groupSortingKeyList),
						$this->getDbAdminService()->getDbConnectionSettings($principal),
						$groupPMapper);
			}
			$returnValue = $groupPMapper->count();
			$groupPMapper->freeMemory();
		}
		catch (ElementServiceException $esE){
			if(isset($groupPMapper)) $groupPMapper->freeMemory();
			$this->executionSink()->publishEndOperationOnError("getAllGroupsContainingElement", $esE, $principal);
			throw $esE;
		}
		catch (AuthorizationServiceException $asE){
			if(isset($groupPMapper)) $groupPMapper->freeMemory();
			$this->executionSink()->publishEndOperationOnError("getAllGroupsContainingElement", $asE, $principal);
			throw $asE;
		}
		catch(Exception $e)
		{
			if(isset($groupPMapper)) $groupPMapper->freeMemory();
			$this->executionSink()->publishEndOperationOnError("getAllGroupsContainingElement", $e, $principal);
			throw new ElementServiceException('',ElementServiceException::WRAPPING, $e);
		}
		$this->executionSink()->publishEndOperation("getAllGroupsContainingElement", $principal);
		return $returnValue;
	}
	protected function assertPrincipalAuthorizedForGetAllGroupsContainingElement($principal, $element)
	{
		if(is_null($element)) throw new ElementServiceException('element can not be null', ElementServiceException::INVALID_ARGUMENT);
		$module = $element->getModule();
		if(is_null($module)) throw new ElementServiceException('element->module can not be null', ElementServiceException::INVALID_ARGUMENT);

		$autoS = $this->getAuthorizationService();
		// checks general authorization
		$pRights = $autoS->assertPrincipalAuthorized($principal, "ElementService", "getAllGroupsContainingElement");
		// checks module access
		if(is_null($pRights) && is_null($principal->getModuleAccess($module))) $autoS->fail($principal, 'has no access to module '.$module->getModuleName());
		return $pRights;
	}
	protected function getSqlForGetAllGroupsContainingElement($principal, $element, $pRightsFromDb, $fieldSelectorList=null, $groupLogExp=null, $groupSortingKeyList=null)
	{
		if(is_null($element)) throw new ElementServiceException("element cannot be null", ElementServiceException::INVALID_ARGUMENT);
		if(is_null($principal)) throw new ElementServiceException("principal cannot be null", ElementServiceException::INVALID_ARGUMENT);
		$elementId = $element->getId();
		if(is_null($elementId)) throw new ElementServiceException("element->id cannot be null", ElementServiceException::INVALID_ARGUMENT);

		$sqlB = $this->getGroupAdminServiceImpl()->getSqlWhereClauseBuilderForSelectGroups('G');
		$elementId = $sqlB->formatValue($elementId, MySqlQueryBuilder::SQLTYPE_INT);

		$gCols = $this->getGroupAdminServiceImpl()->getSqlColumnsForGroup('G', $fieldSelectorList);
		if($pRightsFromDb)
		{
			$principalId = $principal->getUserId();
			if(is_null($principalId)) throw new ElementServiceException('principal attached user can not be null', ElementServiceException::INVALID_ARGUMENT);
			$principalId = $sqlB->formatValue($principalId, MySqlQueryBuilder::SQLTYPE_INT);
			if($gCols != '') $gCols .= ', ';
			$returnValue .= "select $gCols case when GP.id_group is null then 0 else 1 end as canRead, GP.canModify, GP.canWriteElement, GP.canShareElement";
		}
		else
		{
			if($gCols == '') $gCols .= 'G.id_group as id';
			$returnValue = "select $gCols , 1 as canRead"; //add canRead = 1 when PRights, because PRights give at least read rights. --> keep the details
		}
		$returnValue .= " from Groups as G inner join Elements_Groups EG on EG.id_group = G.id_group";
		if($pRightsFromDb)
		{
			$returnValue .= " left join (".
							$this->getSqlForSelectGroupsP($principal, $element->getModule(), $principalId,
								$this->getSqlForSelectElementGroups($elementId, 'EG1'),
								$this->getModuleAdminService()->getDefaultPropagation($element->getModule()), 'UGR1', 'GG1', 'GP1', 'G1')
							.") as GP on GP.id_group = G.id_group";
		}
		// adds where clause
		if(!is_null($groupLogExp))
		{
			// reduces logExp and clones it.
			$groupLogExp = $groupLogExp->reduceNegation(true);
			$groupLogExp = $sqlB->buildWhereClause($groupLogExp);
			if(!is_null($groupLogExp) && $groupLogExp != '') $groupLogExp = " and ".$groupLogExp;
		}
		// order by clause
		if(!is_null($groupSortingKeyList))
		{
			$sqlB->convertFieldSortingKeyListToOrderByClause($groupSortingKeyList);
			$orderByClause = ' '.$sqlB->getOrderByClause();
		} else $orderByClause=' order by G.groupname';
		$returnValue .= " where EG.id_element = $elementId".$groupLogExp.$orderByClause;
		return $returnValue;
	}

	public function getFieldFromGroupsContainingElement($principal, $fieldSelector, $element, $parentOrChildren, $valueList, $groupLogExp=null)
	{
		$this->executionSink()->publishStartOperation("getFieldFromGroupsContainingElement", $principal);
		try
		{
			// checks authorization
			$this->assertPrincipalAuthorizedForGetFieldFromGroupsContainingElement($principal);

			$gAS = $this->getGroupAdminServiceImpl();

			// gets all groups containing element
			$groupListFilter = ListFilter::createInstance();
			$groupListFilter->setFieldSelectorList($gAS->getFieldSelectorListForGroupWithoutDetail());
			if($groupLogExp) $groupListFilter->setFieldSelectorLogExp($groupLogExp);
			$groupList = $this->getElementServiceGroupPList();
			$this->getAllGroupsContainingElement($principal, $element, $groupList, $groupListFilter);

			//if no group found because of exclusions, then nothing to report on.
			if($groupList->isEmpty()){
				$this->executionSink()->log("getFieldFromGroupsContainingElement has found no group containing element which are not excluded.");
				return array();
			}

			// gets groups field
			$returnValue = $gAS->getGroupsField($principal, $fieldSelector, $groupList, $parentOrChildren, $valueList, $groupLogExp);
		}
		catch (ElementServiceException $esE){
			$this->executionSink()->publishEndOperationOnError("getFieldFromGroupsContainingElement", $esE, $principal);
			throw $esE;
		}
		catch (AuthorizationServiceException $asE){
			$this->executionSink()->publishEndOperationOnError("getFieldFromGroupsContainingElement", $asE, $principal);
			throw $asE;
		}
		catch(Exception $e)
		{
			$this->executionSink()->publishEndOperationOnError("getFieldFromGroupsContainingElement", $e, $principal);
			throw new ElementServiceException('',ElementServiceException::WRAPPING, $e);
		}
		$this->executionSink()->publishEndOperation("getFieldFromGroupsContainingElement", $principal);
		return $returnValue;
	}
	protected function assertPrincipalAuthorizedForGetFieldFromGroupsContainingElement($principal)
	{
		// checks general authorization
		$this->getAuthorizationService()->assertPrincipalAuthorized($principal, "ElementService", "getFieldFromGroupsContainingElement");
	}
	public function getFieldFromGroupsContainingElements($principal, $fieldSelector, $elementPAdvancedList, $parentOrChildren, $valueList, $groupLogExp=null)
	{
		$this->executionSink()->publishStartOperation("getFieldFromGroupsContainingElements", $principal);
		try
		{
			// checks authorization
			$this->assertPrincipalAuthorizedForGetFieldFromGroupsContainingElements($principal);

			$gAS = $this->getGroupAdminServiceImpl();

			// gets all groups containing element
			$groupListFilter = ListFilter::createInstance();
			$groupListFilter->setFieldSelectorList($gAS->getFieldSelectorListForGroupWithoutDetail());
			if($groupLogExp) $groupListFilter->setFieldSelectorLogExp($groupLogExp);
			$groupList = $this->getElementServiceGroupPList();
			$this->getAllGroupsWithoutDetailContainingElements($principal, $elementPAdvancedList, $groupList, $groupListFilter);

			//if no group found because of exclusions, then nothing to report on.
			if($groupList->isEmpty()){
				$this->executionSink()->log("getFieldFromGroupsContainingElements has found no group containing elements which are not excluded.");
				return array();
			}

			// gets groups field
			$returnValue = $gAS->getGroupsField($principal, $fieldSelector, $groupList, $parentOrChildren, $valueList, $groupLogExp);
		}
		catch (ElementServiceException $esE){
			$this->executionSink()->publishEndOperationOnError("getFieldFromGroupsContainingElements", $esE, $principal);
			throw $esE;
		}
		catch (AuthorizationServiceException $asE){
			$this->executionSink()->publishEndOperationOnError("getFieldFromGroupsContainingElements", $asE, $principal);
			throw $asE;
		}
		catch(Exception $e)
		{
			$this->executionSink()->publishEndOperationOnError("getFieldFromGroupsContainingElements", $e, $principal);
			throw new ElementServiceException('',ElementServiceException::WRAPPING, $e);
		}
		$this->executionSink()->publishEndOperation("getFieldFromGroupsContainingElements", $principal);
		return $returnValue;
	}
	protected function assertPrincipalAuthorizedForGetFieldFromGroupsContainingElements($principal)
	{
		// checks general authorization
		$this->getAuthorizationService()->assertPrincipalAuthorized($principal, "ElementService", "getFieldFromGroupsContainingElements");
	}

	/**
	 * Returns an Element based on the elementId without filling a wigiibag.
	 * return null if does not exist in database
	 */
	protected function getElementWithoutFields($principal, $elementId)
	{
		$dbCS = $this->getDbAdminService()->getDbConnectionSettings($principal);
		$mySqlF = $this->getMySqlFacade();

		// fetches element
		$dbRow = $mySqlF->selectOne($principal,
					$this->getSqlForGetElement($elementId),
					$dbCS);
		if(isset($dbRow)) return $this->createElementInstanceFromRow($principal, $dbRow);
		else return null;
	}
	/**
	 * Returns an ElementP based on the elementId without filling a wigiibag.
	 * returns null if does not exist in database
	 */
	protected function getElementPWithoutFields($principal, $elementId)
	{
		$dbCS = $this->getDbAdminService()->getDbConnectionSettings($principal);
		$mySqlF = $this->getMySqlFacade();

		// fetches element
		$element = $this->getElementWithoutFields($principal, $elementId);
		if(!isset($element)) return null;

		$elementP = ElementP::createInstance($element);
		if($this->getAuthorizationService()->isRootPrincipal($principal) ||
			$this->getAuthorizationService()->isPublicPrincipal($principal)){
			$elementP->setRightsFromDB($principal, array("canWriteElement"=>true, "canShareElement"=>true));
		} else {
			// fetches principal rights
			$dbRow = $mySqlF->selectOne($principal,
						$this->getSqlForGetElementPRights($principal, $element),
						$dbCS);

			// sets principal rights on existing element
			if(isset($dbRow))
			{
				$elementP->setRightsFromDB($principal, $dbRow);
			}
		}
		return $elementP;
	}

	public function toggleState_locked($principal, $elementId, $message=null){
		return $this->setElementState($principal, $elementId, "locked", null, $message);
	}
	public function toggleState_important1($principal, $elementId, $message=null){
		return $this->setElementState($principal, $elementId, "important1", null, $message);
	}
	public function toggleState_important2($principal, $elementId, $message=null){
		return $this->setElementState($principal, $elementId, "important2", null, $message);
	}
	public function toggleState_archived($principal, $elementId, $message=null){
		return $this->setElementState($principal, $elementId, "archived", null, $message);
	}
	public function toggleState_deprecated($principal, $elementId, $message=null){
		return $this->setElementState($principal, $elementId, "deprecated", null, $message);
	}
	public function toggleState_hidden($principal, $elementId, $message=null){
		return $this->setElementState($principal, $elementId, "hidden", null, $message);
	}
	public function toggleState_finalized($principal, $elementId, $message=null){
		return $this->setElementState($principal, $elementId, "finalized", null, $message);
	}
	public function toggleState_approved($principal, $elementId, $message=null){
		return $this->setElementState($principal, $elementId, "approved", null, $message);
	}
	public function toggleState_dismissed($principal, $elementId, $message=null){
		return $this->setElementState($principal, $elementId, "dismissed", null, $message);
	}
	public function toggleState_blocked($principal, $elementId, $message=null){
		return $this->setElementState($principal, $elementId, "blocked", null, $message);
	}
	public function setState_locked($principal, $elementId, $checked, $message=null){
		return $this->setElementState($principal, $elementId, "locked", $checked, $message);
	}
	public function setState_important1($principal, $elementId, $checked, $message=null){
		return $this->setElementState($principal, $elementId, "important1", $checked, $message);
	}
	public function setState_important2($principal, $elementId, $checked, $message=null){
		return $this->setElementState($principal, $elementId, "important2", $checked, $message);
	}
	public function setState_archived($principal, $elementId, $checked, $message=null){
		return $this->setElementState($principal, $elementId, "archived", $checked, $message);
	}
	public function setState_deprecated($principal, $elementId, $checked, $message=null){
		return $this->setElementState($principal, $elementId, "deprecated", $checked, $message);
	}
	public function setState_hidden($principal, $elementId, $checked, $message=null){
		return $this->setElementState($principal, $elementId, "hidden", $checked, $message);
	}
	public function setState_finalized($principal, $elementId, $checked, $message=null){
		return $this->setElementState($principal, $elementId, "finalized", $checked, $message);
	}
	public function setState_approved($principal, $elementId, $checked, $message=null){
		return $this->setElementState($principal, $elementId, "approved", $checked, $message);
	}
	public function setState_dismissed($principal, $elementId, $checked, $message=null){
		return $this->setElementState($principal, $elementId, "dismissed", $checked, $message);
	}
	public function setState_blocked($principal, $elementId, $checked, $message=null){
		return $this->setElementState($principal, $elementId, "blocked", $checked, $message);
	}
	public function setMultipleState_locked($principal, $elementIds, $checked, $message=null){
		return $this->setMultipleElementState($principal, $elementIds, "locked", $checked, $message);
	}
	public function setMultipleState_important1($principal, $elementIds, $checked, $message=null){
		return $this->setMultipleElementState($principal, $elementIds, "important1", $checked, $message);
	}
	public function setMultipleState_important2($principal, $elementIds, $checked, $message=null){
		return $this->setMultipleElementState($principal, $elementIds, "important2", $checked, $message);
	}
	public function setMultipleState_archived($principal, $elementIds, $checked, $message=null){
		return $this->setMultipleElementState($principal, $elementIds, "archived", $checked, $message);
	}
	public function setMultipleState_deprecated($principal, $elementIds, $checked, $message=null){
		return $this->setMultipleElementState($principal, $elementIds, "deprecated", $checked, $message);
	}
	public function setMultipleState_hidden($principal, $elementIds, $checked, $message=null){
		return $this->setMultipleElementState($principal, $elementIds, "hidden", $checked, $message);
	}
	public function setMultipleState_finalized($principal, $elementIds, $checked, $message=null){
		return $this->setMultipleElementState($principal, $elementIds, "finalized", $checked, $message);
	}
	public function setMultipleState_approved($principal, $elementIds, $checked, $message=null){
		return $this->setMultipleElementState($principal, $elementIds, "approved", $checked, $message);
	}
	public function setMultipleState_dismissed($principal, $elementIds, $checked, $message=null){
		return $this->setMultipleElementState($principal, $elementIds, "dismissed", $checked, $message);
	}
	public function setMultipleState_blocked($principal, $elementIds, $checked, $message=null){
		return $this->setMultipleElementState($principal, $elementIds, "blocked", $checked, $message);
	}
	public function updateSys_dateToNow($principal, $elementIds){
		$this->executionSink()->publishStartOperation("updateSys_dateToNow", $principal);
		try
		{
			//fetches elementP list
			$elementPList = ElementPAdvancedListArrayImpl::createInstance();
			$lf = ListFilter::createInstance();
			$fsl = FieldSelectorListArrayImpl::createInstance();
			$fsl->addFieldSelectorInstance(FieldSelector::createElementAttributeSelector("sys_date"));
			$lf->setFieldSelectorList($fsl);
			$this->getSelectedElements($principal, $elementIds, $elementPList, $lf);

			if(is_null($elementPList)) throw new ElementServiceException("elementIds contains unexisting elements (not exists in database)", ElementServiceException::INVALID_ARGUMENT);

			// checks authorization
			$this->assertPrincipalAuthorizedForUpdateSys_dateToNow($principal, $elementPList);

			if(!$elementPList->isEmpty()){
				// updates multiple element state
				$returnValue = 0;
				$dbCS = $this->getDbAdminService()->getDbConnectionSettings($principal);

				// acquires lock
				$shouldUnlock = $this->lock($principal, $elementPList);
				try
				{
					$returnValue = $this->getMySqlFacade()->update($principal,
						$this->getSqlForUpdateSys_dateToNow($principal, $elementIds),
						$dbCS);
				}
				// releases lock
				catch(Exception $ne) {if($shouldUnlock) $this->unLock($principal, $elementPList); throw $ne;}
				if($shouldUnlock) $this->unLock($principal, $elementPList);
			}
			$returnValue = $elementPList;
		}
		catch (ElementServiceException $ese){
			$this->executionSink()->publishEndOperationOnError("updateSys_dateToNow", $ese, $principal);
			throw $ese;
		}
		catch (AuthorizationServiceException $asE){
			$this->executionSink()->publishEndOperationOnError("updateSys_dateToNow", $asE, $principal);
			throw $asE;
		}
		catch(Exception $e)
		{
			$this->executionSink()->publishEndOperationOnError("setMultipleElementState", $e, $principal);
			throw new ElementServiceException('',ElementServiceException::WRAPPING, $e);
		}
		$this->executionSink()->publishEndOperation("setMultipleElementState", $principal);
		return $returnValue;
	}
	protected function assertPrincipalAuthorizedForUpdateSys_dateToNow($principal, $elementPList)
	{
		if(is_null($principal)) throw new ElementServiceException('principal can not be null', ElementServiceException::INVALID_ARGUMENT);
		if(is_null($elementPList)) throw new ElementServiceException('elementPList can not be null', ElementServiceException::INVALID_ARGUMENT);

		$autoS = $this->getAuthorizationService();
		// checks general authorization
		$autoS->assertPrincipalAuthorized($principal, "ElementService", "updateSys_dateToNow");
		// checks rights on Elements
		if(!$elementPList->isEmpty()){
			if(!$elementPList->allHaveWriteRights()){
				$autoS->fail($principal, 'has not write rights on each elements '.array_keys($elementPList->getListIterator()).' to update sys_date');
			}
		}
	}
	protected function getSqlForUpdateSys_dateToNow($principal, $elementIds){
		if(is_null($elementIds)) throw new ElementServiceException('elementIds can not be null', ElementServiceException::INVALID_ARGUMENT);

		$sqlB = $this->getMySqlFacade()->getSqlBuilder();
		$sqlB->setTableForUpdate('Elements');
		$sqlB->updateSysUser($principal);
		$sqlB->setWhereClauseMultipleId('id_element', $elementIds);

		return $sqlB->getSql();
	}

	//if checked is === null then toggle
	protected function setElementState($principal, $elementId, $state, $checked, $message=null)
	{
		$this->executionSink()->publishStartOperation("setElementState", $principal);
		try
		{
			// checks authorization
			$element = $this->assertPrincipalAuthorizedForSetElementState($principal, $elementId);

			// updates element state
			$returnValue = 0;
			if($checked === null){
				$checked = !$element->{"isState_".$state}();
			}

			// acquires lock
			$shouldUnlock = $this->lock($principal, $element);
			try
			{
				// ok, go on
				$returnValue = $this->getMySqlFacade()->update($principal,
					$this->getSqlForSetElementState($principal, $elementId, $state, $checked, $message),
					$this->getDbAdminService()->getDbConnectionSettings($principal));
				$returnValue = $message;
			}
			// releases lock
			catch(Exception $ne) {if($shouldUnlock) $this->unLock($principal, $element); throw $ne;}
			if($shouldUnlock) $this->unLock($principal, $element);
		}
		catch (ElementServiceException $ese){
			$this->executionSink()->publishEndOperationOnError("setElementState", $ese, $principal);
			throw $ese;
		}
		catch (AuthorizationServiceException $asE){
			$this->executionSink()->publishEndOperationOnError("setElementState", $asE, $principal);
			throw $asE;
		}
		catch(Exception $e)
		{
			$this->executionSink()->publishEndOperationOnError("setElementState", $e, $principal);
			throw new ElementServiceException('',ElementServiceException::WRAPPING, $e);
		}
		$this->executionSink()->publishEndOperation("setElementState", $principal);
		return $returnValue;
	}
	protected function assertPrincipalAuthorizedForSetElementState($principal, $elementId)
	{
		if(is_null($principal)) throw new ElementServiceException('principal can not be null', ElementServiceException::INVALID_ARGUMENT);
		if(is_null($elementId)) throw new ElementServiceException('elementId can not be null', ElementServiceException::INVALID_ARGUMENT);

		$autoS = $this->getAuthorizationService();
		// checks general authorization
		$pRights = $autoS->assertPrincipalAuthorized($principal, "ElementService", "setElementState");

		if(is_null($pRights)){
			// selects root element of sub-element if exists
			$dbRow = $this->getMySqlFacade()->selectFirst($principal,
					$this->getSqlForGetSubElementPathFromRoot($elementId),
					$this->getDbAdminService()->getDbConnectionSettings($principal));
			if(isset($dbRow)) $elementP = $this->getElementPWithoutFields($principal, $dbRow['id_element_owner']);
			else $elementP = $this->getElementPWithoutFields($principal, $elementId);
			if(isset($elementP) &&	($elementP->getRights() == null || !$elementP->getRights()->canWriteElement()) ){
				$autoS->fail($principal, 'has no right to set element state');
			}
			if(isset($dbRow)) $element = $this->getElementWithoutFields($principal, $elementId);
			elseif(isset($elementP)) $element = $elementP->getDbEntity();
			else $element = null;
		} else {
			$element = $this->getElementWithoutFields($principal, $elementId);
		}
		if(!isset($element)) throw new ElementServiceException("element id '$elementId' not found in database", ElementServiceException::INVALID_ARGUMENT);
		return $element;
	}
	protected function getSqlForSetElementState($principal, $elementId, $state, $checked, &$message){
		if(is_null($elementId)) throw new ElementServiceException('elementId can not be null', ElementServiceException::INVALID_ARGUMENT);
		if(is_null($state)) throw new ElementServiceException('state can not be null', ElementServiceException::INVALID_ARGUMENT);
		if($message!=null && !is_string($message) && !is_array($message)) throw new ElementServiceException('message can only be null or string or array', ElementServiceException::INVALID_ARGUMENT);

		$sqlB = $this->getMySqlFacade()->getSqlBuilder();
		$sqlB->setTableForUpdate('Elements');
		$sqlB->updateValue('state_'.$state, $checked, MySqlQueryBuilder::SQLTYPE_BOOLEAN);
		$realMessage = array();
		if($principal->getRealUser()){
			$realMessage["realUsername"] = $principal->getRealUser()->getUsername();
			$realMessage["realUserWigiiNamespace"] = $principal->getRealUser()->getWigiiNamespace()->getWigiiNamespaceName();
			$realMessage["realUserId"] = $principal->getRealUser()->getId();
		}
		if($principal->getAttachedUser()){
			$realMessage["username"] = $principal->getAttachedUser()->getUsername();
			$realMessage["userWigiiNamespace"] = $principal->getAttachedUser()->getWigiiNamespace()->getWigiiNamespaceName();
			$realMessage["userId"] = $principal->getAttachedUser()->getId();
		}
		$realMessage["timestamp"] = time();
		$realMessage["message"] = $message;
		$message = $realMessage;
		$sqlB->updateValue('state_'.$state.'Info', array2str($message), MySqlQueryBuilder::SQLTYPE_TEXT);
		$sqlB->updateSysUser($principal);

		$sqlB->setWhereClauseSingleId('id_element', $elementId);

		return $sqlB->getSql();
	}
	//if checked == null, then true for all except if all is true then false
	protected function setMultipleElementState($principal, $elementIds, $state, $checked, $message = null)
	{
		$this->executionSink()->publishStartOperation("setMultipleElementState", $principal);
		try
		{
			//fetches elementP list
			$elementPList = ElementPAdvancedListArrayImpl::createInstance("state_".$state);
			$lf = ListFilter::createInstance();
			$fsl = FieldSelectorListArrayImpl::createInstance();
			$fsl->addFieldSelectorInstance($elementPList->getSpecificAttributFieldSelector());
			$lf->setFieldSelectorList($fsl);
			$this->getSelectedElements($principal, $elementIds, $elementPList, $lf);
			if($checked === null && $elementPList->allHaveSpecificAttribut()){
				$checked = false;
			} else if($checked === null){
				$checked = true;
			}

			if(is_null($elementPList)) throw new ElementServiceException("elementIds contains unexisting elements (not exists in database)", ElementServiceException::INVALID_ARGUMENT);

			// checks authorization
			$this->assertPrincipalAuthorizedForSetMultipleElementState($principal, $elementPList);

			if(!$elementPList->isEmpty()){
				// updates multiple element state
				$returnValue = 0;
				$dbCS = $this->getDbAdminService()->getDbConnectionSettings($principal);

				// acquires lock
				$shouldUnlock = $this->lock($principal, $elementPList);
				try
				{
					$returnValue = $this->getMySqlFacade()->update($principal,
						$this->getSqlForSetMultipleElementState($principal, $elementIds, $state, $checked, $message),
						$dbCS);
				}
				// releases lock
				catch(Exception $ne) {if($shouldUnlock) $this->unLock($principal, $elementPList); throw $ne;}
				if($shouldUnlock) $this->unLock($principal, $elementPList);
			}
			$returnValue = $elementPList;
		}
		catch (ElementServiceException $ese){
			$this->executionSink()->publishEndOperationOnError("setMultipleElementState", $ese, $principal);
			throw $ese;
		}
		catch (AuthorizationServiceException $asE){
			$this->executionSink()->publishEndOperationOnError("setMultipleElementState", $asE, $principal);
			throw $asE;
		}
		catch(Exception $e)
		{
			$this->executionSink()->publishEndOperationOnError("setMultipleElementState", $e, $principal);
			throw new ElementServiceException('',ElementServiceException::WRAPPING, $e);
		}
		$this->executionSink()->publishEndOperation("setMultipleElementState", $principal);
		return $returnValue;
	}
	protected function assertPrincipalAuthorizedForSetMultipleElementState($principal, $elementPList)
	{
		if(is_null($principal)) throw new ElementServiceException('principal can not be null', ElementServiceException::INVALID_ARGUMENT);
		if(is_null($elementPList)) throw new ElementServiceException('elementPList can not be null', ElementServiceException::INVALID_ARGUMENT);

		$autoS = $this->getAuthorizationService();
		// checks general authorization
		$autoS->assertPrincipalAuthorized($principal, "ElementService", "setElementState");
		// checks rights on Elements
		if(!$elementPList->isEmpty()){
			if(!$elementPList->allHaveWriteRights()){
				$autoS->fail($principal, 'has not write rights on each elements '.array_keys($elementPList->getListIterator()).' to update multiple state');
			}
		}
	}
	protected function getSqlForSetMultipleElementState($principal, $elementIds, $state, $checked, $message){
		if(is_null($elementIds)) throw new ElementServiceException('elementIds can not be null', ElementServiceException::INVALID_ARGUMENT);
		if(is_null($state)) throw new ElementServiceException('state can not be null', ElementServiceException::INVALID_ARGUMENT);
		if($message!=null && !is_string($message) && !is_array($message)) throw new ElementServiceException('message can only be null or string or array', ElementServiceException::INVALID_ARGUMENT);

		$sqlB = $this->getMySqlFacade()->getSqlBuilder();
		$sqlB->setTableForUpdate('Elements');
		$sqlB->updateValue('state_'.$state, $checked, MySqlQueryBuilder::SQLTYPE_BOOLEAN);
		$sqlB->updateSysUser($principal);
		$realMessage = array();
		if($principal->getRealUser()){
			$realMessage["realUsername"] = $principal->getRealUser()->getUsername();
			$realMessage["realUserWigiiNamespace"] = $principal->getRealUser()->getWigiiNamespace()->getWigiiNamespaceName();
			$realMessage["realUserId"] = $principal->getRealUser()->getId();
		}
		$realMessage["username"] = $principal->getAttachedUser()->getUsername();
		$realMessage["userWigiiNamespace"] = $principal->getAttachedUser()->getWigiiNamespace()->getWigiiNamespaceName();
		$realMessage["userId"] = $principal->getAttachedUser()->getId();
		$realMessage["timestamp"] = time();
		$realMessage["message"] = $message;
		$sqlB->updateValue('state_'.$state.'Info', array2str($realMessage), MySqlQueryBuilder::SQLTYPE_TEXT);
		$sqlB->setWhereClauseMultipleId('id_element', $elementIds);

		return $sqlB->getSql();
	}

	public function removeMultipleElementSharing($rootPrincipal, $principal, $elementPAList, $groupIds, $preventRemovingLastSharingInWritableGroup=true){
		if($preventRemovingLastSharingInWritableGroup){
			return $this->shareMultipleElement($rootPrincipal, $principal, $elementPAList, $groupIds, "removeCheck");
		} else {
			return $this->shareMultipleElement($rootPrincipal, $principal, $elementPAList, $groupIds, "remove");
		}
	}
	public function addMultipleElementSharing($rootPrincipal, $principal, $elementPAList, $groupIds){
		return $this->shareMultipleElement($rootPrincipal, $principal, $elementPAList, $groupIds, "add");
	}
	public function setMultipleElementSharing($rootPrincipal, $principal, $elementPAList, $groupIds){
		return $this->shareMultipleElement($rootPrincipal, $principal, $elementPAList, $groupIds, "set");
	}
	protected function shareMultipleElement($rootPrincipal, $principal, $elementPAList, $groupIds, $action)
	{
		$this->executionSink()->publishStartOperation("shareMultipleElement", $principal);
		try
		{
			if(is_null($groupIds)) throw new ElementServiceException("groupIds cannot be null", ElementServiceException::INVALID_ARGUMENT);

			// checks authorization
			$this->assertPrincipalAuthorizedForShareMultipleElement($rootPrincipal, $principal, $elementPAList);

			// updates element shares
			if(!is_array($groupIds))
			{
				$groupIds = array($groupIds => $groupIds);
			}
			$groupAS = $this->getGroupAdminServiceImpl();
			$fsl = $groupAS->getFieldSelectorListForGroupWithoutDetail();
			$mySqlF = $this->getMySqlFacade();
			$dbCS = $this->getDbAdminService()->getDbConnectionSettings($principal);
			$groupPList = GroupListAdvancedImpl::createInstance(false);
			$listFilter = ListFilter::createInstance();
			$groupLogExp = LogExp::createInExp(FieldSelector::createInstance("id"), $groupIds);
			$listFilter->setFieldSelectorLogExp($groupLogExp);
			$listFilter->setFieldSelectorList($fsl);
			$groupAS->getSelectedGroups($principal, $listFilter, $groupPList);

			if($action === "add" || $action === "set"){
				//add sharing can only be done in writable groups
				$changes = $mySqlF->update($principal,
					$this->getSqlForShareMultipleElement($principal, $elementPAList, $groupPList->getWriteGroups()),
					$dbCS);
			}
			if($action === "set"){
				$module = reset($groupPList->getListIterator())->getDbEntity()->getModule();

				//fetch all groups Principal has access to
				$allGroupList = GroupListAdvancedImpl::createInstance(false);
				$listFilter->setFieldSelectorLogExp(null);
				$groupAS->getAllGroups($principal, $module, $allGroupList, $listFilter);

				//remove all writeGroups that we have not added
				$writeGroups = $allGroupList->getWriteGroups()->getGroupIds();
				$addedGroups = $groupPList->getGroupIds();
				$groupToUnshare = array_diff_key($writeGroups, $addedGroups);
				$changes = $mySqlF->delete($principal,
					$this->getSqlForDeleteMultipleElementSharing($principal, $elementPAList->getIds(), $groupToUnshare),
					$dbCS);

				//remove all sgroups for createdElements
				if($elementPAList->getSysCreationUserElementPList($principal->getRealUserId()) && $allGroupList->getSGroups()->getGroupIds()){
					$sGroups = $allGroupList->getSGroups()->getGroupIds();
					$addedGroups = $groupPList->getGroupIds();
					$groupToUnshare = array_diff_key($sGroups, $addedGroups);
					$changes = $mySqlF->delete($principal,
						$this->getSqlForDeleteMultipleElementSharing($principal, $elementPAList->getSysCreationUserElementPList($principal->getRealUserId())->getIds(), $groupToUnshare),
						$dbCS);
				}
			}
			if($action === "removeCheck"){
				// counts remaining shares on elements after deletion
				$module = reset($groupPList->getListIterator())->getDbEntity()->getModule();
				//fetch all groups Principal can write
				$allGroupList = GroupListAdvancedImpl::createInstance(false);
				$listFilter->setFieldSelectorLogExp(null);
				$groupAS->getAllGroups($principal, $module, $allGroupList, $listFilter);

				//check if those elements will still remain in a writable group

				if($elementPAList->getNoneSysCreationUserElementPList($principal->getRealUserId())->getIds() && $groupPList->getWriteGroups()->getGroupIds()){
					$orphans = $mySqlF->selectOne($principal,
						$this->getSqlForCountOrphansElementAfterUnshareMultiple($principal, $elementPAList->getNoneSysCreationUserElementPList($principal->getRealUserId())->getIds(), $groupPList->getWriteGroups()->getGroupIds(), $allGroupList->getWriteGroups()->getGroupIds(), 'result'),
						$dbCS);
					if(isset($orphans)) $orphans=$orphans['result'];
					if(!isset($orphans)) $orphans = 0;
				}

				if($elementPAList->getSysCreationUserElementPList($principal->getRealUserId()) && $groupPList->getSGroups()->getGroupIds()){
					//lookup to prevent to remove last sharing which would delete elements
					// counts remaining shares on this element after deletion
					$orphansCreatedEl = $mySqlF->selectOne($principal,
						$this->getSqlForCountOrphansElementAfterUnshareMultiple($principal, $elementPAList->getSysCreationUserElementPList($principal->getRealUserId())->getIds(), $groupPList->getSGroups()->getGroupIds(), $allGroupList->getSGroups()->getGroupIds(), 'result'),
						$dbCS);
					if(isset($orphansCreatedEl)) $orphansCreatedEl=$orphansCreatedEl['result'];
					if(!isset($orphansCreatedEl)) $orphansCreatedEl = 0;
					$orphans = $orphans + $orphansCreatedEl;
				}

				// if remaining shares > 0 then deletes those shares
				if($orphans==0) {
					$changes = 0;
					if($elementPAList->getNoneSysCreationUserElementPList($principal->getRealUserId())->getIds() && $groupPList->getWriteGroups()->getGroupIds()){
						$changes += $mySqlF->delete($principal,
							$this->getSqlForDeleteMultipleElementSharing($principal, $elementPAList->getNoneSysCreationUserElementPList($principal->getRealUserId())->getIds(), $groupPList->getWriteGroups()->getGroupIds()),
							$dbCS);
					}
					if($elementPAList->getSysCreationUserElementPList($principal->getRealUserId()) && $groupPList->getSGroups()->getGroupIds()){
						$changes += $mySqlF->delete($principal,
							$this->getSqlForDeleteMultipleElementSharing($principal, $elementPAList->getSysCreationUserElementPList($principal->getRealUserId())->getIds(), $groupPList->getSGroups()->getGroupIds()),
							$dbCS);
					}
				} else throw new ElementServiceException("Could not remove last element sharing in writable groups. Elements should at least belong to one writable group.", ElementServiceException::DATA_INTEGRITY_ERROR);
			}
			if($action === "remove"){

				//check that those elements will still remain in a group (even a none accessible group)
				if($elementPAList->getNoneSysCreationUserElementPList($principal->getRealUserId())->getIds() && $groupPList->getWriteGroups()->getGroupIds()){
					$orphans = $mySqlF->selectOne($principal,
						$this->getSqlForCountOrphansElementAfterUnshareMultiple($principal, $elementPAList->getNoneSysCreationUserElementPList($principal->getRealUserId())->getIds(), $groupPList->getWriteGroups()->getGroupIds(), null, 'result'),
						$dbCS);
					if(isset($orphans)) $orphans=$orphans['result'];
					if(!isset($orphans)) $orphans = 0;
				}

				if($elementPAList->getSysCreationUserElementPList($principal->getRealUserId()) && $groupPList->getSGroups()->getGroupIds()){
					//lookup to prevent to remove last sharing which would delete elements
					// counts remaining shares on this element after deletion
					$orphansCreatedEl = $mySqlF->selectOne($principal,
						$this->getSqlForCountOrphansElementAfterUnshareMultiple($principal, $elementPAList->getSysCreationUserElementPList($principal->getRealUserId())->getIds(), $groupPList->getSGroups()->getGroupIds(), null, 'result'),
						$dbCS);
					if(isset($orphansCreatedEl)) $orphansCreatedEl=$orphansCreatedEl['result'];
					if(!isset($orphansCreatedEl)) $orphansCreatedEl = 0;
					$orphans = $orphans + $orphansCreatedEl;
				}

				// if remaining shares > 0 then deletes those shares
				if($orphans==0) {
					$changes = 0;
					if($elementPAList->getNoneSysCreationUserElementPList($principal->getRealUserId())->getIds() && $groupPList->getWriteGroups()->getGroupIds()){
						$changes += $mySqlF->delete($principal,
							$this->getSqlForDeleteMultipleElementSharing($principal, $elementPAList->getNoneSysCreationUserElementPList($principal->getRealUserId())->getIds(), $groupPList->getWriteGroups()->getGroupIds()),
							$dbCS);
					}
					if($elementPAList->getSysCreationUserElementPList($principal->getRealUserId()) && $groupPList->getSGroups()->getGroupIds()){
						$changes += $mySqlF->delete($principal,
							$this->getSqlForDeleteMultipleElementSharing($principal, $elementPAList->getSysCreationUserElementPList($principal->getRealUserId())->getIds(), $groupPList->getSGroups()->getGroupIds()),
							$dbCS);
					}
				} else throw new ElementServiceException("Could not remove last element sharing. Elements should at least belong to one group.", ElementServiceException::DATA_INTEGRITY_ERROR);
			}

			$returnValue = $groupPList;
		}
		catch (ElementServiceException $ese){
			$this->executionSink()->publishEndOperationOnError("shareMultipleElement", $ese, $principal);
			throw $ese;
		}
		catch (AuthorizationServiceException $asE){
			$this->executionSink()->publishEndOperationOnError("shareMultipleElement", $asE, $principal);
			throw $asE;
		}
		catch(Exception $e)
		{
			$this->executionSink()->publishEndOperationOnError("shareMultipleElement", $e, $principal);
			throw new ElementServiceException('',ElementServiceException::WRAPPING, $e);
		}
		$this->executionSink()->publishEndOperation("shareMultipleElement", $principal);
		return $returnValue;
	}
	protected function assertPrincipalAuthorizedForShareMultipleElement($rootPrincipal, $principal, $elementPAList)
	{
		if(is_null($rootPrincipal)) throw new ElementServiceException('rootPrincipal can not be null', ElementServiceException::INVALID_ARGUMENT);
		if(is_null($principal)) throw new ElementServiceException('principal can not be null', ElementServiceException::INVALID_ARGUMENT);

		$autoS = $this->getAuthorizationService();
		// checks general authorization
		$autoS->assertPrincipalAuthorized($rootPrincipal, "ElementService", "shareMultipleElement");
		// checks rights on Elements
		if(!$elementPAList->allHaveWriteRights())
		{
			$autoS->fail($principal, 'has not write rights on each element in '.implode(", ", $elementPAList->getElementIds()));
		}
	}

	protected function getSqlForShareMultipleElement($principal, $elementPAList, $groupPList){
		if(is_null($elementPAList)) throw new ElementServiceException('elementPAList can not be null', ElementServiceException::INVALID_ARGUMENT);
		if(is_null($groupPList)) throw new ElementServiceException('groupPList can not be null', ElementServiceException::INVALID_ARGUMENT);

		$sqlB = $this->getMySqlFacade()->getSqlBuilder();
		$sqlB->setTableForUpdate('Elements_Groups', true);

		$first = true;
		foreach($elementPAList->getListIterator() as $element){
			foreach($groupPList->getListIterator() as $group){
				if($first){
					$first = false;
					$sqlB->updateValue('id_element', $element->getId(), MySqlQueryBuilder::SQLTYPE_INT);
					$sqlB->updateValue('id_group', $group->getId(), MySqlQueryBuilder::SQLTYPE_INT);
				} else {
					$sqlB->insertMoreRecord();
					$sqlB->insertMoreValue($element->getId(), MySqlQueryBuilder::SQLTYPE_INT);
					$sqlB->insertMoreValue($group->getId(), MySqlQueryBuilder::SQLTYPE_INT);
				}
			}
		}
		return $sqlB->getSql();

	}
	protected function getSqlForDeleteMultipleElementSharing($principal, $elementPAListIds, $groupIds){
		if(is_null($elementPAListIds)) throw new ElementServiceException('elementPAListIds can not be null', ElementServiceException::INVALID_ARGUMENT);
		if(is_null($groupIds)) throw new ElementServiceException('groupIds can not be null', ElementServiceException::INVALID_ARGUMENT);

		$sqlB = $this->getMySqlFacade()->getSqlBuilder();
		$sqlB->setTableForDelete('Elements_Groups');

		$elementIds = $sqlB->formatBinExp('Elements_Groups.id_element', 'IN', $elementPAListIds, MySqlQueryBuilder::SQLTYPE_INT);
		$groupIds = $sqlB->formatBinExp('Elements_Groups.id_group', 'IN', $groupIds, MySqlQueryBuilder::SQLTYPE_INT);
		$sqlB->setWhereClause("$elementIds and $groupIds");
		return $sqlB->getSql();

	}
	protected function getSqlForCountOrphansElementAfterUnshareMultiple($principal, $elementIds, $groupIdsToRemove, $checkThatTheyRemainInGroupsIds=null, $countColName='result')
	{
		$sqlB = $this->getMySqlFacade()->getSqlBuilder();
		$elementIds = $sqlB->formatBinExp('EG.id_element', 'IN', $elementIds, MySqlQueryBuilder::SQLTYPE_INT);
		$notInGroupIds = $sqlB->formatBinExp('EG2.id_group', 'NOTIN', $groupIdsToRemove, MySqlQueryBuilder::SQLTYPE_INT);
		if($checkThatTheyRemainInGroupsIds) $checkThatTheyRemainInGroupsIds = $sqlB->formatBinExp('EG2.id_group', 'IN', $checkThatTheyRemainInGroupsIds, MySqlQueryBuilder::SQLTYPE_INT);
		else $checkThatTheyRemainInGroupsIds = null;
		return "select count(EG.id_element) as $countColName from Elements_Groups as EG where $elementIds and EG.id_element NOT IN (select EG2.id_element FROM Elements_Groups as EG2 where $notInGroupIds".($checkThatTheyRemainInGroupsIds ? " and $checkThatTheyRemainInGroupsIds" :  "").")";
	}

	public function moveMultipleElementToModule($rootPrincipal, $principal, $elementPAList, $newModule, $groupIds){
		$this->executionSink()->publishStartOperation("moveMultipleElementToModule", $principal);
		try
		{
			// checks authorization
			$this->assertPrincipalAuthorizedForMoveMultipleElementToModule($rootPrincipal, $principal, $elementPAList, $newModule, $groupIds);

			$groupAS = $this->getGroupAdminServiceImpl();
			$fsl = $groupAS->getFieldSelectorListForGroupWithoutDetail();
			$mySqlF = $this->getMySqlFacade();
			$dbCS = $this->getDbAdminService()->getDbConnectionSettings($principal);

			// rights on Elements is trust because of the rootPrincipal (already checked)
			// destination group don't necessary have rights, has this is wanted is trust because it is defined in the config.of the rootPrincipal (already checked)

			//look for writable group in current module
			$allGroupList = GroupListAdvancedImpl::createInstance(false);
			$listFilter = ListFilter::createInstance();
			$listFilter->setFieldSelectorLogExp(null);
			$groupAS->getAllGroups($principal, reset($elementPAList->getListIterator())->getElement()->getModule(), $allGroupList, $listFilter);
			$writeGroupIds = $allGroupList->getWriteGroups()->getGroupIds();
			//check if elements have other links than writable groups
			$rowList = RowListArrayImpl::createInstance();
			$this->getMySqlFacade()->selectAll($principal,
					$this->getSqlForGetElementsInOtherGroups($elementPAList->getIds(), $writeGroupIds),
					$this->getDbAdminService()->getDbConnectionSettings($principal),
					$rowList);
			if(!$rowList->isEmpty()){
				$errorText = "elements ";
				foreach($rowList->getListIterator() as $row){
					$errorText .= "#".$row["id_element"]." ";
				}
				$errorText .= "have links in unwritable groups.";
				throw new ElementServiceException($errorText, ElementServiceException::ELEMENT_SHARED_IN_NONE_WRITABLE_GROUPS);
			}

			// acquires lock
			$shouldUnlock = $this->lock($principal, $elementPAList);
			try {
				// share multiple element to group
				$changes = $mySqlF->update($principal,
					$this->getSqlForMoveMultipleElementToGroups($elementPAList, $groupIds),
					$dbCS);

				// delete all other sharing
				$changes = $mySqlF->delete($principal,
						$this->getSqlForDeleteMultipleElementSharingNotInGroups($elementPAList, $groupIds),
						$dbCS);

				//update multiple element module
				$changes = $mySqlF->update($principal,
					$this->getSqlForUpdateMultipleElementToModule($principal, $elementPAList, $newModule),
					$dbCS);
			}
			// releases lock
			catch(Exception $ne) {if($shouldUnlock) $this->unLock($principal, $elementPAList); throw $ne;}
			if($shouldUnlock) $this->unLock($principal, $elementPAList);

			//return the groupList
			$returnValue = $elementPAList->count();
		}
		catch (ElementServiceException $ese){
			$this->executionSink()->publishEndOperationOnError("moveMultipleElementToModule", $ese, $principal);
			throw $ese;
		}
		catch (AuthorizationServiceException $asE){
			$this->executionSink()->publishEndOperationOnError("moveMultipleElementToModule", $asE, $principal);
			throw $asE;
		}
		catch(Exception $e)
		{
			$this->executionSink()->publishEndOperationOnError("moveMultipleElementToModule", $e, $principal);
			throw new ElementServiceException('',ElementServiceException::WRAPPING, $e);
		}
		$this->executionSink()->publishEndOperation("moveMultipleElementToModule", $principal);
		return $returnValue;
	}
	protected function assertPrincipalAuthorizedForMoveMultipleElementToModule($rootPrincipal, $principal, $elementPAList, $newModule, $groupIds)
	{
		if(is_null($rootPrincipal)) throw new ElementServiceException('Root principal can not be null', ElementServiceException::INVALID_ARGUMENT);
		if(is_null($principal)) throw new ElementServiceException('principal can not be null', ElementServiceException::INVALID_ARGUMENT);
		if(is_null($newModule)) throw new ElementServiceException("newModule cannot be null", ElementServiceException::INVALID_ARGUMENT);
		if(is_null($groupIds)) throw new ElementServiceException("groupIds cannot be null", ElementServiceException::INVALID_ARGUMENT);
		if(is_null($elementPAList)) throw new ElementServiceException("elementPAList cannot be null", ElementServiceException::INVALID_ARGUMENT);
		$autoS = $this->getAuthorizationService();
		// checks general authorization
		$autoS->assertPrincipalAuthorized($rootPrincipal, "ElementService", "moveMultipleElementToModule");
		// checks rights on Elements
		if(!$elementPAList->allHaveWriteRights())
		{
			$autoS->fail($principal, 'has not write rights on each element in '.implode(", ", $elementPAList->getElementIds()));
		}
	}
	protected function getSqlForMoveMultipleElementToGroups($dbEntityList, $groupIds){

		$sqlB = $this->getMySqlFacade()->getSqlBuilder();
		$sqlB->setTableForUpdate('Elements_Groups', true);

		$first = true;
		foreach($dbEntityList->getListIterator() as $dbEntity){
			foreach($groupIds as $idGroup){
				if($first){
					$first = false;
					$sqlB->updateValue('id_element', $dbEntity->getId(), MySqlQueryBuilder::SQLTYPE_INT);
					$sqlB->updateValue('id_group', $idGroup, MySqlQueryBuilder::SQLTYPE_INT);
				} else {
					$sqlB->insertMoreRecord();
					$sqlB->insertMoreValue($dbEntity->getId(), MySqlQueryBuilder::SQLTYPE_INT);
					$sqlB->insertMoreValue($idGroup, MySqlQueryBuilder::SQLTYPE_INT);
				}
			}
		}

		return $sqlB->getSql();

	}
	protected function getSqlForDeleteMultipleElementSharingNotInGroups($dbEntityList, $groupIds){

		$sqlB = $this->getMySqlFacade()->getSqlBuilder();
		$sqlB->setTableForDelete('Elements_Groups');

		$elementIds = $sqlB->formatBinExp('Elements_Groups.id_element', 'IN', $dbEntityList->getIds(), MySqlQueryBuilder::SQLTYPE_INT);
		$groupId = $sqlB->formatBinExp('Elements_Groups.id_group', 'NOTIN',$groupIds, MySqlQueryBuilder::SQLTYPE_INT);
		$sqlB->setWhereClause("$elementIds and $groupId");
		return $sqlB->getSql();

	}
	protected function getSqlForUpdateMultipleElementToModule($principal, $dbEntityList, $module){

		$sqlB = $this->getMySqlFacade()->getSqlBuilder();
		$sqlB->setTableForUpdate('Elements', true);

		$first = true;
		$time = time();
		$pId = $principal->getRealUserId();
		$pName = $principal->getRealUsername();
		foreach($dbEntityList->getListIterator() as $dbEntity){
			if($first){
				$first = false;
				$sqlB->updateValue('id_element', $dbEntity->getId(), MySqlQueryBuilder::SQLTYPE_INT);
				$sqlB->updateValue('sys_date', $time, MySqlQueryBuilder::SQLTYPE_INT);
				$sqlB->updateValue('sys_user', $pId, MySqlQueryBuilder::SQLTYPE_INT);
				$sqlB->updateValue('sys_username', $pName, MySqlQueryBuilder::SQLTYPE_VARCHAR);
				$sqlB->updateValue('modulename', $module->getModuleName(), MySqlQueryBuilder::SQLTYPE_VARCHAR);
			} else {
				$sqlB->insertMoreRecord();
				$sqlB->insertMoreValue($dbEntity->getId(), MySqlQueryBuilder::SQLTYPE_INT);
				$sqlB->insertMoreValue($time, MySqlQueryBuilder::SQLTYPE_INT);
				$sqlB->insertMoreValue($pId, MySqlQueryBuilder::SQLTYPE_INT);
				$sqlB->insertMoreValue($pName, MySqlQueryBuilder::SQLTYPE_VARCHAR);
				$sqlB->insertMoreValue($module->getModuleName(), MySqlQueryBuilder::SQLTYPE_VARCHAR);
			}
		}

		return $sqlB->getSql();
	}
	protected function getSqlForGetElementsInOtherGroups($elementIds, $shouldNotBeInGroupIds){
		return "SELECT id_element FROM Elements_Groups WHERE id_element IN (".implode(", ",$elementIds).") AND id_group NOT IN (".implode(", ", $shouldNotBeInGroupIds).")";
	}

	public function shareElement($principal, $elementId, $groupIds)
	{
		$this->executionSink()->publishStartOperation("shareElement", $principal);
		try
		{
			if(is_null($groupIds)) throw new ElementServiceException("groupIds cannot be null", ElementServiceException::INVALID_ARGUMENT);

			//fetches elementP
			$elementP = $this->getElementPWithoutFields($principal, $elementId);
			if(is_null($elementP)) throw new ElementServiceException("element $elementId does not exists in database", ElementServiceException::INVALID_ARGUMENT);
			$element = $elementP->getElement();

			// checks authorization
			$this->assertPrincipalAuthorizedForShareElement($principal, $elementP);

			// updates element shares
			if(!is_array($groupIds))
			{
				$groupIds = array($groupIds => $groupIds);
			}
			$groupAS = $this->getGroupAdminServiceImpl();
			$fsl = $groupAS->getFieldSelectorListForGroupWithoutDetail();
			$mySqlF = $this->getMySqlFacade();
			$dbCS = $this->getDbAdminService()->getDbConnectionSettings($principal);
			$returnValue = 0;
			foreach($groupIds as $groupId)
			{
				// fetches groupP
				$groupP = $groupAS->getGroup($principal, $groupId, $fsl);
				// if groupP exists in database ok to update sharing
				if(isset($groupP))
				{
					// checks if principal has rights to share element in this group
					if($this->checkPrincipalAuthorizedToShareElementInGroup($principal, $element, $groupP))
					{
						$changes = $mySqlF->update($principal,
							$this->getSqlForUpdateElementGroup($elementId, $groupId),
							$dbCS);
						if($changes > 0) $returnValue ++;
					}
					unset($groupP);
				}
			}
		}
		catch (ElementServiceException $ese){
			$this->executionSink()->publishEndOperationOnError("shareElement", $ese, $principal);
			throw $ese;
		}
		catch (AuthorizationServiceException $asE){
			$this->executionSink()->publishEndOperationOnError("shareElement", $asE, $principal);
			throw $asE;
		}
		catch(Exception $e)
		{
			$this->executionSink()->publishEndOperationOnError("shareElement", $e, $principal);
			throw new ElementServiceException('',ElementServiceException::WRAPPING, $e);
		}
		$this->executionSink()->publishEndOperation("shareElement", $principal);
		return $returnValue;
	}
	protected function assertPrincipalAuthorizedForShareElement($principal, $elementP)
	{
		if(is_null($principal)) throw new ElementServiceException('principal can not be null', ElementServiceException::INVALID_ARGUMENT);
		if(is_null($elementP)) throw new ElementServiceException('elementP can not be null', ElementServiceException::INVALID_ARGUMENT);
		$element = $elementP->getElement();
		$module = $element->getModule();
		if(is_null($module)) throw new ElementServiceException('element->module can not be null', ElementServiceException::INVALID_ARGUMENT);

		$autoS = $this->getAuthorizationService();
		// checks general authorization
		$autoS->assertPrincipalAuthorized($principal, "ElementService", "shareElement");
		// checks module access
		if(!$autoS->isRootPrincipal($principal) && is_null($principal->getModuleAccess($module))) $autoS->fail($principal, 'has no access to module '.$module->getModuleName());
		// checks rights on Element
		$pRights = $elementP->getRights();
		if(!isset($pRights) || !$pRights->canWriteElement())
		{
			$autoS->fail($principal, 'has no right to share element '.$element->getId());
		}
	}
	/**
	 * Returns true if authorized to share element in group else false
	 */
	protected function checkPrincipalAuthorizedToShareElementInGroup($principal, $element, $groupP)
	{
		if(is_null($principal)) throw new ElementServiceException('principal can not be null', ElementServiceException::INVALID_ARGUMENT);
		$moduleE = $element->getModule();
		if(is_null($moduleE)) throw new ElementServiceException('element->module can not be null', ElementServiceException::INVALID_ARGUMENT);
		if(is_null($groupP)) throw new ElementServiceException('groupP can not be null', ElementServiceException::INVALID_ARGUMENT);
		$group = $groupP->getGroup();
		$moduleG = $group->getModule();
		if(is_null($moduleG)) throw new ElementServiceException('group->module can not be null', ElementServiceException::INVALID_ARGUMENT);

		// checks that element module and group module matches
		if($moduleE !== $moduleG) throw new ElementServiceException('group module and element module are not equal', ElementServiceException::INVALID_ARGUMENT);

		// checks rights on Group
		$pRights = $groupP->getRights();
		if(!isset($pRights) || !($pRights->canWriteElement() || ($element->getSys_creationUser()==$principal->getRealUserId() && $pRights->canShareElement())))
		{
			return false;
		}
		return true;
	}

	public function unshareElement($principal, $elementId, $groupIds, $preventRemovingLastSharingInWritableGroup=true)
	{
		$this->executionSink()->publishStartOperation("unshareElement", $principal);
		try
		{
			if(is_null($groupIds)) throw new ElementServiceException("groupIds cannot be null", ElementServiceException::INVALID_ARGUMENT);

			//fetches elementP
			$elementP = $this->getElementPWithoutFields($principal, $elementId);
			if(is_null($elementP)) throw new ElementServiceException("element $elementId does not exists in database", ElementServiceException::INVALID_ARGUMENT);

			// checks authorization
			$this->assertPrincipalAuthorizedForUnshareElement($principal, $elementP);

			// updates element shares
			if(!is_array($groupIds))
			{
				$groupIds = array($groupIds => $groupIds);
			}
			$groupAS = $this->getGroupAdminServiceImpl();
			$fsl = $groupAS->getFieldSelectorListForGroupWithoutDetail();
			$mySqlF = $this->getMySqlFacade();
			$dbCS = $this->getDbAdminService()->getDbConnectionSettings($principal);
			$returnValue = 0;

			//retreive groupPList to unshare
			$groupPList = GroupListAdvancedImpl::createInstance(false);
			$listFilter = ListFilter::createInstance();
			$groupLogExp = LogExp::createInExp(FieldSelector::createInstance("id"), $groupIds);
			$listFilter->setFieldSelectorLogExp($groupLogExp);
			$listFilter->setFieldSelectorList($fsl);
			$groupAS->getSelectedGroups($principal, $listFilter, $groupPList);


			if($preventRemovingLastSharingInWritableGroup){
				// counts remaining shares on elements after deletion
				$module = $elementP->getDbEntity()->getModule();
				//fetch all groups Principal can write
				$allGroupList = GroupListAdvancedImpl::createInstance(false);
				$listFilter->setFieldSelectorLogExp(null);
				$groupAS->getAllGroups($principal, $module, $allGroupList, $listFilter);

				if($elementP->getDbEntity()->getSys_creationUser()!=$principal->getRealUserId()){
					$orphans = $mySqlF->selectOne($principal,
						$this->getSqlForCountOrphansElementAfterUnshareMultiple($principal, $elementP->getId(), $groupPList->getWriteGroups()->getGroupIds(), $allGroupList->getWriteGroups()->getGroupIds(), 'result'),
						$dbCS);
					if(isset($orphans)) $orphans=$orphans['result'];
					if(!isset($orphans)) $orphans = 0;
				}

				if($elementP->getDbEntity()->getSys_creationUser()==$principal->getRealUserId()){
					//lookup to prevent to remove last sharing which would delete elements
					// counts remaining shares on this element after deletion
					$orphansCreatedEl = $mySqlF->selectOne($principal,
						$this->getSqlForCountOrphansElementAfterUnshareMultiple($principal, $elementP->getId(), $groupPList->getSGroups()->getGroupIds(), $allGroupList->getSGroups()->getGroupIds(), 'result'),
						$dbCS);
					if(isset($orphansCreatedEl)) $orphansCreatedEl=$orphansCreatedEl['result'];
					if(!isset($orphansCreatedEl)) $orphansCreatedEl = 0;
					$orphans = $orphans + $orphansCreatedEl;
				}

				// if remaining shares > 0 then deletes those shares
				if($orphans==0) {
					$changes = 0;
					if($elementP->getDbEntity()->getSys_creationUser()!=$principal->getRealUserId()){
							$changes += $mySqlF->delete($principal,
							$this->getSqlForDeleteMultipleElementSharing($principal, $elementP->getId(), $groupPList->getWriteGroups()->getGroupIds()),
							$dbCS);
					}
					if($elementP->getDbEntity()->getSys_creationUser()==$principal->getRealUserId()){
						$changes += $mySqlF->delete($principal,
							$this->getSqlForDeleteMultipleElementSharing($principal, $elementP->getId(), $groupPList->getSGroups()->getGroupIds()),
							$dbCS);
					}
				} else throw new ElementServiceException("Could not remove last element sharing in writable groups. Element should at least belong to one writable group.", ElementServiceException::DATA_INTEGRITY_ERROR);
			} else {
				//check that those elements will still remain in a group (even a none accessible group)
				if($elementP->getDbEntity()->getSys_creationUser()!=$principal->getRealUserId()){
					$orphans = $mySqlF->selectOne($principal,
						$this->getSqlForCountOrphansElementAfterUnshareMultiple($principal, $elementP->getId(), $groupPList->getWriteGroups()->getGroupIds(), null, 'result'),
						$dbCS);
					if(isset($orphans)) $orphans=$orphans['result'];
					if(!isset($orphans)) $orphans = 0;
				}

				if($elementP->getDbEntity()->getSys_creationUser()==$principal->getRealUserId()){
					//lookup to prevent to remove last sharing which would delete elements
					// counts remaining shares on this element after deletion
					$orphansCreatedEl = $mySqlF->selectOne($principal,
						$this->getSqlForCountOrphansElementAfterUnshareMultiple($principal, $elementP->getId(), $groupPList->getSGroups()->getGroupIds(), null, 'result'),
						$dbCS);
					if(isset($orphansCreatedEl)) $orphansCreatedEl=$orphansCreatedEl['result'];
					if(!isset($orphansCreatedEl)) $orphansCreatedEl = 0;
					$orphans = $orphans + $orphansCreatedEl;
				}

				// if remaining shares > 0 then deletes those shares
				if($orphans==0) {
					$changes = 0;
					if($elementP->getDbEntity()->getSys_creationUser()!=$principal->getRealUserId()){
						$changes += $mySqlF->delete($principal,
							$this->getSqlForDeleteMultipleElementSharing($principal, $elementP->getId(), $groupPList->getWriteGroups()->getGroupIds()),
							$dbCS);
					}
					if($elementP->getDbEntity()->getSys_creationUser()==$principal->getRealUserId()){
						$changes += $mySqlF->delete($principal,
							$this->getSqlForDeleteMultipleElementSharing($principal, $elementP->getId(), $groupPList->getSGroups()->getGroupIds()),
							$dbCS);
					}
				} else throw new ElementServiceException("Could not remove last element sharing. Element should at least belong to one group.", ElementServiceException::DATA_INTEGRITY_ERROR);
			}

			$returnValue = $changes;

		}
		catch (ElementServiceException $ese){
			$this->executionSink()->publishEndOperationOnError("unshareElement", $ese, $principal);
			throw $ese;
		}
		catch (AuthorizationServiceException $asE){
			$this->executionSink()->publishEndOperationOnError("unshareElement", $asE, $principal);
			throw $asE;
		}
		catch(Exception $e)
		{
			$this->executionSink()->publishEndOperationOnError("unshareElement", $e, $principal);
			throw new ElementServiceException('',ElementServiceException::WRAPPING, $e);
		}
		$this->executionSink()->publishEndOperation("unshareElement", $principal);
		return $returnValue;
	}
	protected function assertPrincipalAuthorizedForUnshareElement($principal, $elementP)
	{
		if(is_null($principal)) throw new ElementServiceException('principal can not be null', ElementServiceException::INVALID_ARGUMENT);
		if(is_null($elementP)) throw new ElementServiceException('elementP can not be null', ElementServiceException::INVALID_ARGUMENT);
		$element = $elementP->getElement();
		$module = $element->getModule();
		if(is_null($module)) throw new ElementServiceException('element->module can not be null', ElementServiceException::INVALID_ARGUMENT);

		$autoS = $this->getAuthorizationService();
		// checks general authorization
		$autoS->assertPrincipalAuthorized($principal, "ElementService", "unshareElement");
		// checks module access
		if(!$autoS->isRootPrincipal($principal) && is_null($principal->getModuleAccess($module))) $autoS->fail($principal, 'has no access to module '.$module->getModuleName());
		// checks rights on Element
		$pRights = $elementP->getRights();
		if(!isset($pRights) || !$pRights->canWriteElement())
		{
			$autoS->fail($principal, 'has no right to unshare element '.$element->getId());
		}
	}
	/**
	 * Returns true if authorized to unshare element from group else false
	 */
	protected function checkPrincipalAuthorizedToUnshareElementFromGroup($principal, $element, $groupP)
	{
		if(is_null($groupP)) throw new ElementServiceException('groupP can not be null', ElementServiceException::INVALID_ARGUMENT);

		// checks rights on Group
		$pRights = $groupP->getRights();
		if(!isset($pRights) || !($pRights->canWriteElement() || ($element->getSys_creationUser()==$principal->getRealUserId() && $pRights->canShareElement())))
		{
			return false;
		}
		return true;
	}
	protected function getSqlForCountRemainingGroupsAfterUnshare($elementId, $groupId, $countColName='result')
	{
		$sqlB = $this->getMySqlFacade()->getSqlBuilder();
		$elementId = $sqlB->formatBinExp('EG.id_element', '=', $elementId, MySqlQueryBuilder::SQLTYPE_INT);
		$groupId = $sqlB->formatBinExp('EG.id_group', '!=', $groupId, MySqlQueryBuilder::SQLTYPE_INT);
		return "select count(id_group) as $countColName from Elements_Groups as EG where $elementId and $groupId";
	}

	public function insertElement($principal, $element, $groupId, $fieldSelectorList=null)
	{
		$this->executionSink()->publishStartOperation("insertElement", $principal);
		$eltQP = null;
		try
		{
			if(is_null($element)) return 0;
			if(!$element->isNew()) throw new ElementServiceException("can only insert new elements, use updateElement to update an existing element or setElementSharing to share an element with another group", ElementServiceException::INVALID_ARGUMENT);
			if(is_null($groupId)) throw new ElementServiceException("groupId cannot be null", ElementServiceException::INVALID_ARGUMENT);

			// reads group with principal rights
			$gAS = $this->getGroupAdminServiceImpl();
			$groupP = $gAS->getGroup($principal, $groupId, $gAS->getFieldSelectorListForGroupWithoutDetail());

			// checks authorization
			$this->assertPrincipalAuthorizedForInsertElement($principal, $groupP);

			// validates element
			$this->validateElementForInsert($principal, $element, $groupP);

			// computes effective field list
			$eltQP = $this->getElementQueryPlanner(MySqlFacade::Q_INSERTONE, 0, $fieldSelectorList);
			//SysInfo at field level requires to have element and principal
			$eltQP->setElement($element);
			$eltQP->setPrincipal($principal);
			$cS = $this->getConfigService();
			try {
				// if ConfigService supports method unselectSubElementConfig
				// then unselects any previous sub element config
				if($this->subElementConfigSupport['unselectSubElementConfig']) $cS->unselectSubElementConfig($principal);
				$cS->getGroupFields($principal, $groupP->getGroup(), null, $eltQP);
			}
			catch(ServiceException $se) {
				// extracts wrapped exception
				$seRoot = $se->getWigiiRootException();
				// if operation has been canceled, retries once.
				if($seRoot->getCode() == ServiceException::OPERATION_CANCELED) {
					$eltQP->retryAfterCancel(MySqlFacade::Q_INSERTONE, 0, $fieldSelectorList);
					$cS->getGroupFields($principal, $groupP->getGroup(), null, $eltQP);
				}
				else throw $se;
			}

			// inserts element
			$dbAS = $this->getDbAdminService();
			$dbCS = $dbAS->getDbConnectionSettings($principal);
			$mySqlF = $this->getMySqlFacade();
			$returnValue = 0;
			$beforeInsertId = $element->getId();
			$element->setId(
				$mySqlF->insertOne($principal,
					$this->getSqlForInsertElement($principal, $element, true),
					$dbCS)
			);
			try
			{
				//inserts element fields values
				$n = $eltQP->getNumberOfQueries();
				$strategy = $eltQP->getQueryStrategy();
				// strategy DATATYPE -> insert multiple
				if($strategy === ElementQueryPlanner::QSTRATEGY_DATATYPE) {
					for($i = 0; $i < $n; $i++)
					{
						$mySqlF->insertMultiple($principal, $eltQP->getSql($i,
							$this->getSqlBuilderForInsertElement($principal, $strategy, $element, $beforeInsertId)),
							$dbCS);
					}
				}
				// strategy FIELD -> insert one
				elseif($strategy === ElementQueryPlanner::QSTRATEGY_FIELD) {
					for($i = 0; $i < $n; $i++)
					{
						$mySqlF->insertOne($principal, $eltQP->getSql($i,
							$this->getSqlBuilderForInsertElement($principal, $strategy, $element, $beforeInsertId)),
							$dbCS);
					}
				}
				else throw new ElementServiceException('unsupported query strategy', ElementServiceException::UNSUPPORTED_OPERATION);
				$eltQP->freeMemory();

				// inserts Elements_Groups relation
				$mySqlF->insertOne($principal,
					$this->getSqlForInsertElementGroup($element->getId(), $groupId),
					$dbCS);
			}
			// if insertion error, then deletes first created element and throws exception
			catch(Exception $e)
			{
				// unlocks element
				$this->unLock($principal, $element);
				// deletes element
				try
				{
					$dbAS->deleteRows($principal,
						$this->getSqlTableNameForDeleteElement(),
						$this->getSqlWhereClauseForDeleteElement($principal, $element, GroupListAdvancedImpl::createInstance()->addGroupP($groupP)),
						$dbCS);
				}
				catch(Exception $e1){/* does nothing, it will be automatically deleted on next delete */}
				throw $e;
			}
			// unlocks element
			$this->unLock($principal, $element);
			$returnValue = 1;
		}
		catch (ElementServiceException $ese){
			if(isset($eltQP)) $eltQP->freeMemory();
			$this->executionSink()->publishEndOperationOnError("insertElement", $ese, $principal);
			throw $ese;
		}
		catch (AuthorizationServiceException $asE){
			if(isset($eltQP)) $eltQP->freeMemory();
			$this->executionSink()->publishEndOperationOnError("insertElement", $asE, $principal);
			throw $asE;
		}
		catch(Exception $e)
		{
			if(isset($eltQP)) $eltQP->freeMemory();
			$this->executionSink()->publishEndOperationOnError("insertElement", $e, $principal);
			throw new ElementServiceException('',ElementServiceException::WRAPPING, $e);
		}
		$this->executionSink()->publishEndOperation("insertElement", $principal);
		return $returnValue;
	}
	protected function assertPrincipalAuthorizedForInsertElement($principal, $groupP)
	{
		if(is_null($principal)) throw new ElementServiceException('principal can not be null', ElementServiceException::INVALID_ARGUMENT);

		$autoS = $this->getAuthorizationService();
		// checks general authorization
		$autoS->assertPrincipalAuthorized($principal, "ElementService", "insertElement");
		//check write rights on group
		if($groupP->getRights()==null || !$groupP->getRights()->canShareElement()){
			$autoS->fail($principal, 'has no right to insert element in group '.$groupP->getGroup()->getGroupName());
		}
	}
	protected function validateElementForInsert($principal, $element, $groupP)
	{
		/* nothing to do for now */
	}
	/**
	 * if lock then locks inserted element
	 */
	protected function getSqlForInsertElement($principal, $element, $lock)
	{
		$sqlB = $this->getMySqlFacade()->getSqlBuilder();
		$sqlB->setTableForInsert('Elements');

		if(is_null($element)) throw new ElementServiceException('element can not be null', ElementServiceException::INVALID_ARGUMENT);
		$module = $element->getModule(); if(is_object($module)) $module = $module->getModuleName();
		$sqlB->insertValue('modulename', $module, MySqlQueryBuilder::SQLTYPE_VARCHAR);
		$sqlB->insertValue('version', $element->getVersion(), MySqlQueryBuilder::SQLTYPE_VARCHAR);
		// subitem infos : id_element_parent, linkName, peerId are not persisted here.
		$sqlB->insertValue('state_important1', $element->isState_important1(), MySqlQueryBuilder::SQLTYPE_BOOLEAN);
		$sqlB->insertValue('state_important1Info', $element->getState_important1Info(), MySqlQueryBuilder::SQLTYPE_TEXT);
		$sqlB->insertValue('state_important2', $element->isState_important2(), MySqlQueryBuilder::SQLTYPE_BOOLEAN);
		$sqlB->insertValue('state_important2Info', $element->getState_important2Info(), MySqlQueryBuilder::SQLTYPE_TEXT);
		$sqlB->insertValue('state_hidden', $element->isState_hidden(), MySqlQueryBuilder::SQLTYPE_BOOLEAN);
		$sqlB->insertValue('state_hiddenInfo', $element->getState_hiddenInfo(), MySqlQueryBuilder::SQLTYPE_TEXT);
		$sqlB->insertValue('state_locked', $element->isState_locked(), MySqlQueryBuilder::SQLTYPE_BOOLEAN);
		$sqlB->insertValue('state_lockedInfo', $element->getState_lockedInfo(), MySqlQueryBuilder::SQLTYPE_TEXT);
		$sqlB->insertValue('state_archived', $element->isState_archived(), MySqlQueryBuilder::SQLTYPE_BOOLEAN);
		$sqlB->insertValue('state_archivedInfo', $element->getState_archivedInfo(), MySqlQueryBuilder::SQLTYPE_TEXT);
		$sqlB->insertValue('state_deprecated', $element->isState_deprecated(), MySqlQueryBuilder::SQLTYPE_BOOLEAN);
		$sqlB->insertValue('state_deprecatedInfo', $element->getState_deprecatedInfo(), MySqlQueryBuilder::SQLTYPE_TEXT);
		$sqlB->insertValue('state_finalized', $element->isState_finalized(), MySqlQueryBuilder::SQLTYPE_BOOLEAN);
		$sqlB->insertValue('state_finalizedInfo', $element->getState_finalizedInfo(), MySqlQueryBuilder::SQLTYPE_TEXT);
		$sqlB->insertValue('state_approved', $element->isState_approved(), MySqlQueryBuilder::SQLTYPE_BOOLEAN);
		$sqlB->insertValue('state_approvedInfo', $element->getState_approvedInfo(), MySqlQueryBuilder::SQLTYPE_TEXT);
		$sqlB->insertValue('state_dismissed', $element->isState_dismissed(), MySqlQueryBuilder::SQLTYPE_BOOLEAN);
		$sqlB->insertValue('state_dismissedInfo', $element->getState_dismissedInfo(), MySqlQueryBuilder::SQLTYPE_TEXT);
		$sqlB->insertValue('state_blocked', $element->isState_blocked(), MySqlQueryBuilder::SQLTYPE_BOOLEAN);
		$sqlB->insertValue('state_blockedInfo', $element->getState_blockedInfo(), MySqlQueryBuilder::SQLTYPE_TEXT);
		$sqlB->updateSysUser($principal);
		if($lock) $sqlB->lock($principal);
		return $sqlB->getSql();
	}
	protected function getSqlForInsertElementGroup($elementId, $groupId)
	{
		$sqlB = $this->getMySqlFacade()->getSqlBuilder();
		$sqlB->setTableForInsert('Elements_Groups');

		if(is_null($elementId)) throw new ElementServiceException('elementId can not be null', ElementServiceException::INVALID_ARGUMENT);
		if(is_null($groupId)) throw new ElementServiceException('groupId can not be null', ElementServiceException::INVALID_ARGUMENT);
		$sqlB->insertValue('id_element', $elementId, MySqlQueryBuilder::SQLTYPE_INT);
		$sqlB->insertValue('id_group', $groupId, MySqlQueryBuilder::SQLTYPE_INT);
		return $sqlB->getSql();
	}
	protected function getSqlForUpdateElementGroup($elementId, $groupId)
	{
		$sqlB = $this->getMySqlFacade()->getSqlBuilder();
		$sqlB->setTableForUpdate('Elements_Groups', true);

		if(is_null($elementId)) throw new ElementServiceException('elementId can not be null', ElementServiceException::INVALID_ARGUMENT);
		if(is_null($groupId)) throw new ElementServiceException('groupId can not be null', ElementServiceException::INVALID_ARGUMENT);
		$sqlB->updateValue('id_element', $elementId, MySqlQueryBuilder::SQLTYPE_INT);
		$sqlB->updateValue('id_group', $groupId, MySqlQueryBuilder::SQLTYPE_INT);
		return $sqlB->getSql();
	}
	protected function getSqlForDeleteElementGroup($elementId, $groupId)
	{
		$sqlB = $this->getMySqlFacade()->getSqlBuilder();
		$sqlB->setTableForDelete('Elements_Groups');

		if(is_null($elementId)) throw new ElementServiceException('elementId can not be null', ElementServiceException::INVALID_ARGUMENT);
		if(is_null($groupId)) throw new ElementServiceException('groupId can not be null', ElementServiceException::INVALID_ARGUMENT);
		$elementId = $sqlB->formatBinExp('Elements_Groups.id_element', '=', $elementId, MySqlQueryBuilder::SQLTYPE_INT);
		$groupId = $sqlB->formatBinExp('Elements_Groups.id_group', '=', $groupId, MySqlQueryBuilder::SQLTYPE_INT);
		$sqlB->setWhereClause("$elementId and $groupId");
		return $sqlB->getSql();
	}
	public function setSqlBuilderForInsertElement($queryStrategy, $sqlBuilderForInsertElement)
	{
		$this->sqlBuilderForInsertElement[$queryStrategy] = $sqlBuilderForInsertElement;
	}
	protected function getSqlBuilderForInsertElement($principal, $queryStrategy, $element, $beforeInsertId)
	{
		// autowired
		if(!isset($this->sqlBuilderForInsertElement)) $this->sqlBuilderForInsertElement = array();
		$sqlB = $this->sqlBuilderForInsertElement[$queryStrategy];
		if(!isset($sqlB))
		{
			switch($queryStrategy)
			{
				case ElementQueryPlanner::QSTRATEGY_DATATYPE:
					$sqlB = ElementSqlBuilder::createInstance();
					$this->sqlBuilderForInsertElement[ElementQueryPlanner::QSTRATEGY_DATATYPE] = $sqlB;
					break;
				case ElementQueryPlanner::QSTRATEGY_FIELD:
					$sqlB = ElementSqlBuilder::createInstance();
					$this->sqlBuilderForInsertElement[ElementQueryPlanner::QSTRATEGY_FIELD] = $sqlB;
					break;
				default: throw new ElementServiceException('unsupported query strategy', ElementServiceException::INVALID_ARGUMENT);
			}
		}
		else
		{
			$sqlB->reset();
		}
		$sqlB->setTripod($this->getTripod());
		$sqlB->setElement($element, $beforeInsertId);
		return $sqlB;
	}

	public function updateMultipleElement($rootPrincipal, $principal, $elementPAList, $fieldSelectorList)
	{
		$this->executionSink()->publishStartOperation("updateMultipleElement", $principal);
		$eltQP = null;
		try
		{
			// checks authorization
			$this->assertPrincipalAuthorizedForUpdateMultipleElement($rootPrincipal, $principal, $elementPAList);

			$dbCS = $this->getDbAdminService()->getDbConnectionSettings($principal);
			$mysqlF = $this->getMySqlFacade();

			//creation of technical Element Multiple
			$element = MultipleElement::createInstance($elementPAList);

			// no validation for multipleElements

			// computes effective field list
			$eltQP = $this->getElementQueryPlanner(MySqlFacade::Q_UPDATE, 0, $fieldSelectorList);
			$eltQP->setElement($element);
			$eltQP->setPrincipal($principal);
			$fields = array(); //this array is just to prevent adding to time the same field in the eltQP in the case of several subfields for the same field in the fieldselector
			foreach($fieldSelectorList->getListIterator() as $fs){
				if(!$fields[$fs->getFieldName()]){
					if(!$fs->isElementAttributeSelector()){
						$eltQP->addField($element->getFieldList()->getField($fs->getFieldName()));
					}
				}
				$fields[$fs->getFieldName()] = true;
			}

			// acquires lock
			$shouldUnlock = $this->lock($principal, $elementPAList);
			try
			{
				// updates element
				$returnValue = $mysqlF->update($principal,
						$this->getSqlForUpdateMultipleElement($principal, $element, $eltQP->getElementAttributeFieldSelectorList()),
						$dbCS);
				//update element fields values
				$n = $eltQP->getNumberOfQueries();
				$strategy = $eltQP->getQueryStrategy();
				for($i = 0; $i < $n; $i++)
				{
					$sql = $eltQP->getSql($i, $this->getSqlBuilderForUpdateMultipleElement($principal, $strategy, $element));
					$returnValue += $mysqlF->update($principal, $sql, $dbCS);
				}
				$eltQP->freeMemory();
				if($returnValue > 0) $returnValue = 1;
			}
			// releases lock
			catch(Exception $ne) {if($shouldUnlock) $this->unLock($principal, $elementPAList); throw $ne;}
			if($shouldUnlock) $this->unLock($principal, $elementPAList);
		}
		catch (ElementServiceException $ese){
			$this->executionSink()->publishEndOperationOnError("updateMultipleElement", $ese, $principal);
			throw $ese;
		}
		catch (AuthorizationServiceException $asE){
			$this->executionSink()->publishEndOperationOnError("updateMultipleElement", $asE, $principal);
			throw $asE;
		}
		catch(Exception $e)
		{
			$this->executionSink()->publishEndOperationOnError("updateMultipleElement", $e, $principal);
			throw new ElementServiceException('',ElementServiceException::WRAPPING, $e);
		}
		$this->executionSink()->publishEndOperation("updateMultipleElement", $principal);
		return $returnValue;
	}
	protected function assertPrincipalAuthorizedForUpdateMultipleElement($rootPrincipal, $principal, $elementPAList)
	{
		if(is_null($rootPrincipal)) throw new ElementServiceException('rootPrincipal can not be null', ElementServiceException::INVALID_ARGUMENT);
		if(is_null($principal)) throw new ElementServiceException('principal can not be null', ElementServiceException::INVALID_ARGUMENT);
		if(is_null($elementPAList) || $elementPAList->isEmpty()) throw new ElementServiceException('elementPAList can not be null or empty', ElementServiceException::INVALID_ARGUMENT);

		$autoS = $this->getAuthorizationService();
		// checks general authorization
		$autoS->assertPrincipalAuthorized($rootPrincipal, "ElementService", "updateMultipleElement");
		// checks rights on Elements
		if(!$elementPAList->allHaveWriteRights())
		{
			$autoS->fail($principal, 'has not write rights on each element in '.implode(", ", $elementPAList->getElementIds()));
		}
	}

	protected function getSqlForUpdateMultipleElement($principal, $element, $elementAttributeFieldSelectorList=null){
		if(is_null($element)) throw new ElementServiceException('element can not be null', ElementServiceException::INVALID_ARGUMENT);
		if(!is_a($element, "MultipleElement")) throw new ElementServiceException('element needs to be a MultipleElement', ElementServiceException::INVALID_ARGUMENT);

		$sqlB = $this->getMySqlFacade()->getSqlBuilder();
		$sqlB->setTableForUpdate('Elements', true);
		$first = true;
		$time = time();
		$pId = $principal->getRealUserId();
		$pName = $principal->getRealUsername();
		$elementList = $element->getElementList();
		foreach($elementList->getListIterator() as $listItem){
			$element = $listItem->getDbEntity();
			//update the Element with the static attributes
			$element->setSys_user($pId);
			$element->setSys_username($pName);
			$element->setSys_date($time);
			if($first){
				$first = false;
				// static attributes
				$sqlB->updateValue('id_element', $element->getId(), MySqlQueryBuilder::SQLTYPE_INT);
				$sqlB->updateValue('sys_date', $element->getSys_date(), MySqlQueryBuilder::SQLTYPE_INT);
				$sqlB->updateValue('sys_user', $element->getSys_user(), MySqlQueryBuilder::SQLTYPE_INT);
				$sqlB->updateValue('sys_username', $element->getSys_username(), MySqlQueryBuilder::SQLTYPE_VARCHAR);
				// other attributes
				if(!isset($elementAttributeFieldSelectorList) || $elementAttributeFieldSelectorList->containsFieldSelector("__element", "modulename"))
				$sqlB->updateValue('modulename', $element->getModule()->getModuleName(), MySqlQueryBuilder::SQLTYPE_VARCHAR);
				// subitem infos : id_element_parent, linkName, peerId are not persisted here.
				if(!isset($elementAttributeFieldSelectorList) || $elementAttributeFieldSelectorList->containsFieldSelector("__element", "version"))
				$sqlB->updateValue('version', $element->getVersion(), MySqlQueryBuilder::SQLTYPE_VARCHAR);
				if(!isset($elementAttributeFieldSelectorList) || $elementAttributeFieldSelectorList->containsFieldSelector("__element", "state_locked"))
				$sqlB->updateValue('state_locked', $element->isState_locked(), MySqlQueryBuilder::SQLTYPE_BOOLEAN);
				if(!isset($elementAttributeFieldSelectorList) || $elementAttributeFieldSelectorList->containsFieldSelector("__element", "state_lockedInfo"))
				$sqlB->updateValue('state_lockedInfo', $element->getState_lockedInfo(), MySqlQueryBuilder::SQLTYPE_TEXT);
				if(!isset($elementAttributeFieldSelectorList) || $elementAttributeFieldSelectorList->containsFieldSelector("__element", "state_important1"))
				$sqlB->updateValue('state_important1', $element->isState_important1(), MySqlQueryBuilder::SQLTYPE_BOOLEAN);
				if(!isset($elementAttributeFieldSelectorList) || $elementAttributeFieldSelectorList->containsFieldSelector("__element", "state_important1Info"))
				$sqlB->updateValue('state_important1Info', $element->getState_important1Info(), MySqlQueryBuilder::SQLTYPE_TEXT);
				if(!isset($elementAttributeFieldSelectorList) || $elementAttributeFieldSelectorList->containsFieldSelector("__element", "state_important2"))
				$sqlB->updateValue('state_important2', $element->isState_important2(), MySqlQueryBuilder::SQLTYPE_BOOLEAN);
				if(!isset($elementAttributeFieldSelectorList) || $elementAttributeFieldSelectorList->containsFieldSelector("__element", "state_important2Info"))
				$sqlB->updateValue('state_important2Info', $element->getState_important2Info(), MySqlQueryBuilder::SQLTYPE_TEXT);
				if(!isset($elementAttributeFieldSelectorList) || $elementAttributeFieldSelectorList->containsFieldSelector("__element", "state_hidden"))
				$sqlB->updateValue('state_hidden', $element->isState_hidden(), MySqlQueryBuilder::SQLTYPE_BOOLEAN);
				if(!isset($elementAttributeFieldSelectorList) || $elementAttributeFieldSelectorList->containsFieldSelector("__element", "state_hiddenInfo"))
				$sqlB->updateValue('state_hiddenInfo', $element->getState_hiddenInfo(), MySqlQueryBuilder::SQLTYPE_TEXT);
				if(!isset($elementAttributeFieldSelectorList) || $elementAttributeFieldSelectorList->containsFieldSelector("__element", "state_archived"))
				$sqlB->updateValue('state_archived', $element->isState_archived(), MySqlQueryBuilder::SQLTYPE_BOOLEAN);
				if(!isset($elementAttributeFieldSelectorList) || $elementAttributeFieldSelectorList->containsFieldSelector("__element", "state_archivedInfo"))
				$sqlB->updateValue('state_archivedInfo', $element->getState_archivedInfo(), MySqlQueryBuilder::SQLTYPE_TEXT);
				if(!isset($elementAttributeFieldSelectorList) || $elementAttributeFieldSelectorList->containsFieldSelector("__element", "state_deprecated"))
				$sqlB->updateValue('state_deprecated', $element->isState_deprecated(), MySqlQueryBuilder::SQLTYPE_BOOLEAN);
				if(!isset($elementAttributeFieldSelectorList) || $elementAttributeFieldSelectorList->containsFieldSelector("__element", "state_deprecatedInfo"))
				$sqlB->updateValue('state_deprecatedInfo', $element->getState_deprecatedInfo(), MySqlQueryBuilder::SQLTYPE_TEXT);
				if(!isset($elementAttributeFieldSelectorList) || $elementAttributeFieldSelectorList->containsFieldSelector("__element", "state_finalized"))
				$sqlB->updateValue('state_finalized', $element->isState_finalized(), MySqlQueryBuilder::SQLTYPE_BOOLEAN);
				if(!isset($elementAttributeFieldSelectorList) || $elementAttributeFieldSelectorList->containsFieldSelector("__element", "state_finalizedInfo"))
				$sqlB->updateValue('state_finalizedInfo', $element->getState_finalizedInfo(), MySqlQueryBuilder::SQLTYPE_TEXT);
				if(!isset($elementAttributeFieldSelectorList) || $elementAttributeFieldSelectorList->containsFieldSelector("__element", "state_approved"))
				$sqlB->updateValue('state_approved', $element->isState_approved(), MySqlQueryBuilder::SQLTYPE_BOOLEAN);
				if(!isset($elementAttributeFieldSelectorList) || $elementAttributeFieldSelectorList->containsFieldSelector("__element", "state_approvedInfo"))
				$sqlB->updateValue('state_approvedInfo', $element->getState_approvedInfo(), MySqlQueryBuilder::SQLTYPE_TEXT);
				if(!isset($elementAttributeFieldSelectorList) || $elementAttributeFieldSelectorList->containsFieldSelector("__element", "state_dismissed"))
				$sqlB->updateValue('state_dismissed', $element->isState_dismissed(), MySqlQueryBuilder::SQLTYPE_BOOLEAN);
				if(!isset($elementAttributeFieldSelectorList) || $elementAttributeFieldSelectorList->containsFieldSelector("__element", "state_dismissedInfo"))
				$sqlB->updateValue('state_dismissedInfo', $element->getState_dismissedInfo(), MySqlQueryBuilder::SQLTYPE_TEXT);
				if(!isset($elementAttributeFieldSelectorList) || $elementAttributeFieldSelectorList->containsFieldSelector("__element", "state_blocked"))
				$sqlB->updateValue('state_blocked', $element->isState_blocked(), MySqlQueryBuilder::SQLTYPE_BOOLEAN);
				if(!isset($elementAttributeFieldSelectorList) || $elementAttributeFieldSelectorList->containsFieldSelector("__element", "state_blockedInfo"))
				$sqlB->updateValue('state_blockedInfo', $element->getState_blockedInfo(), MySqlQueryBuilder::SQLTYPE_TEXT);
				if(!isset($elementAttributeFieldSelectorList) || $elementAttributeFieldSelectorList->containsFieldSelector("__element", "sys_creationDate"))
				$sqlB->updateValue('sys_creationDate', $element->getSys_creationDate(), MySqlQueryBuilder::SQLTYPE_INT);
				if(!isset($elementAttributeFieldSelectorList) || $elementAttributeFieldSelectorList->containsFieldSelector("__element", "sys_creationUser"))
				$sqlB->updateValue('sys_creationUser', $element->getSys_creationUser(), MySqlQueryBuilder::SQLTYPE_INT);
				if(!isset($elementAttributeFieldSelectorList) || $elementAttributeFieldSelectorList->containsFieldSelector("__element", "sys_creationUsername"))
				$sqlB->updateValue('sys_creationUsername', $element->getSys_creationUsername(), MySqlQueryBuilder::SQLTYPE_VARCHAR);
			} else {
				$sqlB->insertMoreRecord();
				// static attributes
				$sqlB->insertMoreValue($element->getId(), MySqlQueryBuilder::SQLTYPE_INT);
				$sqlB->insertMoreValue($element->getSys_date(), MySqlQueryBuilder::SQLTYPE_INT);
				$sqlB->insertMoreValue($element->getSys_user(), MySqlQueryBuilder::SQLTYPE_INT);
				$sqlB->insertMoreValue($element->getSys_username(), MySqlQueryBuilder::SQLTYPE_VARCHAR);
				// other attributes
				if(!isset($elementAttributeFieldSelectorList) || $elementAttributeFieldSelectorList->containsFieldSelector("__element", "modulename"))
				$sqlB->insertMoreValue($element->getModule()->getModuleName(), MySqlQueryBuilder::SQLTYPE_VARCHAR);
				if(!isset($elementAttributeFieldSelectorList) || $elementAttributeFieldSelectorList->containsFieldSelector("__element", "version"))
				$sqlB->insertMoreValue($element->getVersion(), MySqlQueryBuilder::SQLTYPE_VARCHAR);
				if(!isset($elementAttributeFieldSelectorList) || $elementAttributeFieldSelectorList->containsFieldSelector("__element", "state_locked"))
				$sqlB->insertMoreValue($element->isState_locked(), MySqlQueryBuilder::SQLTYPE_BOOLEAN);
				if(!isset($elementAttributeFieldSelectorList) || $elementAttributeFieldSelectorList->containsFieldSelector("__element", "state_lockedInfo"))
				$sqlB->insertMoreValue($element->getState_lockedInfo(), MySqlQueryBuilder::SQLTYPE_TEXT);
				if(!isset($elementAttributeFieldSelectorList) || $elementAttributeFieldSelectorList->containsFieldSelector("__element", "state_important1"))
				$sqlB->insertMoreValue($element->isState_important1(), MySqlQueryBuilder::SQLTYPE_BOOLEAN);
				if(!isset($elementAttributeFieldSelectorList) || $elementAttributeFieldSelectorList->containsFieldSelector("__element", "state_important1Info"))
				$sqlB->insertMoreValue($element->getState_important1Info(), MySqlQueryBuilder::SQLTYPE_TEXT);
				if(!isset($elementAttributeFieldSelectorList) || $elementAttributeFieldSelectorList->containsFieldSelector("__element", "state_important2"))
				$sqlB->insertMoreValue($element->isState_important2(), MySqlQueryBuilder::SQLTYPE_BOOLEAN);
				if(!isset($elementAttributeFieldSelectorList) || $elementAttributeFieldSelectorList->containsFieldSelector("__element", "state_important2Info"))
				$sqlB->insertMoreValue($element->getState_important2Info(), MySqlQueryBuilder::SQLTYPE_TEXT);
				if(!isset($elementAttributeFieldSelectorList) || $elementAttributeFieldSelectorList->containsFieldSelector("__element", "state_hidden"))
				$sqlB->insertMoreValue($element->isState_hidden(), MySqlQueryBuilder::SQLTYPE_BOOLEAN);
				if(!isset($elementAttributeFieldSelectorList) || $elementAttributeFieldSelectorList->containsFieldSelector("__element", "state_hiddenInfo"))
				$sqlB->insertMoreValue($element->getState_hiddenInfo(), MySqlQueryBuilder::SQLTYPE_TEXT);
				if(!isset($elementAttributeFieldSelectorList) || $elementAttributeFieldSelectorList->containsFieldSelector("__element", "state_archived"))
				$sqlB->insertMoreValue($element->isState_archived(), MySqlQueryBuilder::SQLTYPE_BOOLEAN);
				if(!isset($elementAttributeFieldSelectorList) || $elementAttributeFieldSelectorList->containsFieldSelector("__element", "state_archivedInfo"))
				$sqlB->insertMoreValue($element->getState_archivedInfo(), MySqlQueryBuilder::SQLTYPE_TEXT);
				if(!isset($elementAttributeFieldSelectorList) || $elementAttributeFieldSelectorList->containsFieldSelector("__element", "state_deprecated"))
				$sqlB->insertMoreValue($element->isState_deprecated(), MySqlQueryBuilder::SQLTYPE_BOOLEAN);
				if(!isset($elementAttributeFieldSelectorList) || $elementAttributeFieldSelectorList->containsFieldSelector("__element", "state_deprecatedInfo"))
				$sqlB->insertMoreValue($element->getState_deprecatedInfo(), MySqlQueryBuilder::SQLTYPE_TEXT);
				if(!isset($elementAttributeFieldSelectorList) || $elementAttributeFieldSelectorList->containsFieldSelector("__element", "state_finalized"))
				$sqlB->insertMoreValue($element->isState_finalized(), MySqlQueryBuilder::SQLTYPE_BOOLEAN);
				if(!isset($elementAttributeFieldSelectorList) || $elementAttributeFieldSelectorList->containsFieldSelector("__element", "state_finalizedInfo"))
				$sqlB->insertMoreValue($element->getState_finalizedInfo(), MySqlQueryBuilder::SQLTYPE_TEXT);
				if(!isset($elementAttributeFieldSelectorList) || $elementAttributeFieldSelectorList->containsFieldSelector("__element", "state_approved"))
				$sqlB->insertMoreValue($element->isState_approved(), MySqlQueryBuilder::SQLTYPE_BOOLEAN);
				if(!isset($elementAttributeFieldSelectorList) || $elementAttributeFieldSelectorList->containsFieldSelector("__element", "state_approvedInfo"))
				$sqlB->insertMoreValue($element->getState_approvedInfo(), MySqlQueryBuilder::SQLTYPE_TEXT);
				if(!isset($elementAttributeFieldSelectorList) || $elementAttributeFieldSelectorList->containsFieldSelector("__element", "state_dismissed"))
				$sqlB->insertMoreValue($element->isState_dismissed(), MySqlQueryBuilder::SQLTYPE_BOOLEAN);
				if(!isset($elementAttributeFieldSelectorList) || $elementAttributeFieldSelectorList->containsFieldSelector("__element", "state_dismissedInfo"))
				$sqlB->insertMoreValue($element->getState_dismissedInfo(), MySqlQueryBuilder::SQLTYPE_TEXT);
				if(!isset($elementAttributeFieldSelectorList) || $elementAttributeFieldSelectorList->containsFieldSelector("__element", "state_blocked"))
				$sqlB->insertMoreValue($element->isState_blocked(), MySqlQueryBuilder::SQLTYPE_BOOLEAN);
				if(!isset($elementAttributeFieldSelectorList) || $elementAttributeFieldSelectorList->containsFieldSelector("__element", "state_blockedInfo"))
				$sqlB->insertMoreValue($element->getState_blockedInfo(), MySqlQueryBuilder::SQLTYPE_TEXT);
				if(!isset($elementAttributeFieldSelectorList) || $elementAttributeFieldSelectorList->containsFieldSelector("__element", "sys_creationDate"))
				$sqlB->insertMoreValue($element->getSys_creationDate(), MySqlQueryBuilder::SQLTYPE_INT);
				if(!isset($elementAttributeFieldSelectorList) || $elementAttributeFieldSelectorList->containsFieldSelector("__element", "sys_creationUser"))
				$sqlB->insertMoreValue($element->getSys_creationUser(), MySqlQueryBuilder::SQLTYPE_INT);
				if(!isset($elementAttributeFieldSelectorList) || $elementAttributeFieldSelectorList->containsFieldSelector("__element", "sys_creationUsername"))
				$sqlB->insertMoreValue($element->getSys_creationUsername(), MySqlQueryBuilder::SQLTYPE_VARCHAR);
			}
		}
		return $sqlB->getSql();
	}

	public function insertMultipleElement($rootPrincipal, $principal, $groupP, $elementPAList, $fieldSelectorList)
	{
		$this->executionSink()->publishStartOperation("insertMultipleElement", $principal);
		$eltQP = null;
		try
		{
			// checks authorization
			$this->assertPrincipalAuthorizedForInsertMultipleElement($rootPrincipal, $principal, $groupP, $elementPAList);

			$dbCS = $this->getDbAdminService()->getDbConnectionSettings($principal);
			$mysqlF = $this->getMySqlFacade();

			//creation of technical Element Multiple
			$element = MultipleElement::createInstance($elementPAList);

			// no validation for multipleElements

			// computes effective field list
			$eltQP = $this->getElementQueryPlanner(MySqlFacade::Q_UPDATE, 0, $fieldSelectorList);
			$eltQP->setElement($element);
			$eltQP->setPrincipal($principal);
			foreach($element->getFieldList()->getListIterator() as $field){
				//add fields
				if($fieldSelectorList->containsField($field->getFieldName()))
				{
					$eltQP->addField($field);
				}
			}

			// acquires lock
			//$shouldUnlock = $this->lock($principal, $elementPAList);
			try
			{
				// insert element
				$execId = $principal->getExecutionId();
				$microtime = $principal->getMicroTime();
//				eput($this->getSqlForInsertMultipleElement($principal, $element, $execId, $microtime, $eltQP->getElementAttributeFieldSelectorList()));
				$lastId = $mysqlF->insertMultiple($principal,
						$this->getSqlForInsertMultipleElement($principal, $element, $execId, $microtime, $eltQP->getElementAttributeFieldSelectorList()),
						$dbCS);
				$n = $eltQP->getNumberOfQueries();

				//fetch the inserted ids based on the lock and the tags field
				$rowList = ElementService_ElementIdMapperForInsertMultiple::createInstance($elementPAList);
				$nbRecords = $this->getMySqlFacade()->selectAll($principal,
						$this->getSqlForGetInsertedElementIds($execId, $microtime),
						$this->getDbAdminService()->getDbConnectionSettings($principal),
						$rowList);

				//insert element fields values
				$strategy = $eltQP->getQueryStrategy();
				for($i = 0; $i < $n; $i++)
				{
					$sql = $eltQP->getSql($i, $this->getSqlBuilderForUpdateMultipleElement($principal, $strategy, $element, true));
//					fput($sql."\n\n");
					$returnValue += $mysqlF->update($principal, $sql, $dbCS);
				}
				$eltQP->freeMemory();

//				eput($elementPAList);
				// inserts Elements_Groups relation
				$this->getMySqlFacade()->insertMultiple($principal,
					$this->getSqlForInsertElementsGroup($rowList->getIds(), $groupP->getId()),
					$dbCS);

				if($returnValue > 0) $returnValue = 1;
			}
			// releases lock
			catch(Exception $ne) {
//				if($shouldUnlock) $this->unLock($principal, $elementPAList);
				throw $ne;
			}
			//the unlock needs the new ids
			$this->unLock($principal, $rowList);
		}
		catch (ElementServiceException $ese){
			if(isset($eltQP)) $eltQP->freeMemory();
			$this->executionSink()->publishEndOperationOnError("insertMultipleElement", $ese, $principal);
			throw $ese;
		}
		catch (AuthorizationServiceException $asE){
			if(isset($eltQP)) $eltQP->freeMemory();
			$this->executionSink()->publishEndOperationOnError("insertMultipleElement", $asE, $principal);
			throw $asE;
		}
		catch(Exception $e)
		{
			if(isset($eltQP)) $eltQP->freeMemory();
			$this->executionSink()->publishEndOperationOnError("insertMultipleElement", $e, $principal);
			throw new ElementServiceException('',ElementServiceException::WRAPPING, $e);
		}
		$this->executionSink()->publishEndOperation("insertMultipleElement", $principal);
		return $returnValue;
	}
	protected function getSqlForGetInsertedElementIds($execId, $microtime){
		$sqlB = $this->getMySqlFacade()->getSqlBuilder();
		$execId = $sqlB->formatBinExp("E.sys_lockId", "=", $execId, MySqlQueryBuilder::SQLTYPE_VARCHAR);
		$microtime = $sqlB->formatBinExp("E.sys_lockMicroTime", "=", $microtime, MySqlQueryBuilder::SQLTYPE_INT);
		return "select E.id_element, E.modulename, E.version, E.tags, E.sys_creationDate, E.sys_creationUser, E.sys_creationUsername, E.sys_date, E.sys_user, E.sys_username, E.sys_lockId, E.sys_lockMicroTime from Elements as E where $execId and $microtime order by E.tags";
	}
	protected function getSqlForInsertElementsGroup($elementIds, $groupId)
	{
		$sqlB = $this->getMySqlFacade()->getSqlBuilder();
		$sqlB->setTableForInsert('Elements_Groups');

		if(is_null($elementIds)) throw new ElementServiceException('elementIds can not be null', ElementServiceException::INVALID_ARGUMENT);
		if(is_null($groupId)) throw new ElementServiceException('groupId can not be null', ElementServiceException::INVALID_ARGUMENT);
		$first = true;
		foreach($elementIds as $elementId){
			if($first){
				$sqlB->insertValue('id_element', $elementId, MySqlQueryBuilder::SQLTYPE_INT);
				$sqlB->insertValue('id_group', $groupId, MySqlQueryBuilder::SQLTYPE_INT);
				$first = false;
			} else {
				$sqlB->insertMoreRecord();
				$sqlB->insertMoreValue($elementId, MySqlQueryBuilder::SQLTYPE_INT);
				$sqlB->insertMoreValue($groupId, MySqlQueryBuilder::SQLTYPE_INT);
			}
		}
		return $sqlB->getSql();
	}
	protected function assertPrincipalAuthorizedForInsertMultipleElement($rootPrincipal, $principal, $groupP, $elementPAList)
	{
		if(is_null($rootPrincipal)) throw new ElementServiceException('rootPrincipal can not be null', ElementServiceException::INVALID_ARGUMENT);
		if(is_null($principal)) throw new ElementServiceException('principal can not be null', ElementServiceException::INVALID_ARGUMENT);
		if(is_null($groupP)) throw new ElementServiceException('$groupP can not be null', ElementServiceException::INVALID_ARGUMENT);
		if(is_null($elementPAList) || $elementPAList->isEmpty()) throw new ElementServiceException('elementPAList can not be null or empty', ElementServiceException::INVALID_ARGUMENT);

		$autoS = $this->getAuthorizationService();
		// checks general authorization
		$autoS->assertPrincipalAuthorized($rootPrincipal, "ElementService", "insertMultipleElement");
		// checks rights on Group
		if($groupP->getRights()==null || !$groupP->getRights()->canShareElement())
		{
			$autoS->fail($principal, 'has not write rights in group '.$groupP->getId().' '.$gorupP->getDbEntity()->getGroupName());
		}
	}

	protected function getSqlForInsertMultipleElement($principal, $element, $execId, $microtime, $elementAttributeFieldSelectorList=null){
		if(is_null($element)) throw new ElementServiceException('element can not be null', ElementServiceException::INVALID_ARGUMENT);
		if(!is_a($element, "MultipleElement")) throw new ElementServiceException('element needs to be a MultipleElement', ElementServiceException::INVALID_ARGUMENT);

		$sqlB = $this->getMySqlFacade()->getSqlBuilder();
		$sqlB->setTableForInsert('Elements');
		$first = true;
		$time = time();
		$pId = $principal->getRealUserId();
		$pName = $principal->getRealUsername();
		$elementList = $element->getElementList();

		$counter = 1;
		foreach($elementList->getListIterator() as $listItem){
			$element = $listItem->getDbEntity();
			//update the Element with the static attributes
			$element->setSys_user($pId);
			$element->setSys_username($pName);
			$element->setSys_date($time);
			if($first){
				$first = false;
				// static attributes
				$sqlB->insertValue('sys_lockMicroTime', $microtime, MySqlQueryBuilder::SQLTYPE_VARCHAR);
				$sqlB->insertValue('sys_lockId', $execId, MySqlQueryBuilder::SQLTYPE_VARCHAR);
				$sqlB->insertValue('tags', $counter++, MySqlQueryBuilder::SQLTYPE_VARCHAR);
				$sqlB->insertValue('sys_creationDate', $element->getSys_creationDate(), MySqlQueryBuilder::SQLTYPE_INT);
				$sqlB->insertValue('sys_creationUser', $element->getSys_creationUser(), MySqlQueryBuilder::SQLTYPE_INT);
				$sqlB->insertValue('sys_creationUsername', $element->getSys_creationUsername(), MySqlQueryBuilder::SQLTYPE_VARCHAR);
				$sqlB->insertValue('sys_date', $element->getSys_date(), MySqlQueryBuilder::SQLTYPE_INT);
				$sqlB->insertValue('sys_user', $element->getSys_user(), MySqlQueryBuilder::SQLTYPE_INT);
				$sqlB->insertValue('sys_username', $element->getSys_username(), MySqlQueryBuilder::SQLTYPE_VARCHAR);
				$sqlB->insertValue('modulename', $element->getModule()->getModuleName(), MySqlQueryBuilder::SQLTYPE_VARCHAR);
				// subitem infos : id_element_parent, linkName, peerId are not persisted here.
				// other attributes
				if(!isset($elementAttributeFieldSelectorList) || $elementAttributeFieldSelectorList->containsFieldSelector("__element", "version"))
				$sqlB->insertValue('version', $element->getVersion(), MySqlQueryBuilder::SQLTYPE_VARCHAR);
				if(!isset($elementAttributeFieldSelectorList) || $elementAttributeFieldSelectorList->containsFieldSelector("__element", "state_locked"))
				$sqlB->insertValue('state_locked', $element->isState_locked(), MySqlQueryBuilder::SQLTYPE_BOOLEAN);
				if(!isset($elementAttributeFieldSelectorList) || $elementAttributeFieldSelectorList->containsFieldSelector("__element", "state_lockedInfo"))
				$sqlB->insertValue('state_lockedInfo', $element->getState_lockedInfo(), MySqlQueryBuilder::SQLTYPE_TEXT);
				if(!isset($elementAttributeFieldSelectorList) || $elementAttributeFieldSelectorList->containsFieldSelector("__element", "state_important1"))
				$sqlB->insertValue('state_important1', $element->isState_important1(), MySqlQueryBuilder::SQLTYPE_BOOLEAN);
				if(!isset($elementAttributeFieldSelectorList) || $elementAttributeFieldSelectorList->containsFieldSelector("__element", "state_important1Info"))
				$sqlB->insertValue('state_important1Info', $element->getState_important1Info(), MySqlQueryBuilder::SQLTYPE_TEXT);
				if(!isset($elementAttributeFieldSelectorList) || $elementAttributeFieldSelectorList->containsFieldSelector("__element", "state_important2"))
				$sqlB->insertValue('state_important2', $element->isState_important2(), MySqlQueryBuilder::SQLTYPE_BOOLEAN);
				if(!isset($elementAttributeFieldSelectorList) || $elementAttributeFieldSelectorList->containsFieldSelector("__element", "state_important2Info"))
				$sqlB->insertValue('state_important2Info', $element->getState_important2Info(), MySqlQueryBuilder::SQLTYPE_TEXT);
				if(!isset($elementAttributeFieldSelectorList) || $elementAttributeFieldSelectorList->containsFieldSelector("__element", "state_hidden"))
				$sqlB->insertValue('state_hidden', $element->isState_hidden(), MySqlQueryBuilder::SQLTYPE_BOOLEAN);
				if(!isset($elementAttributeFieldSelectorList) || $elementAttributeFieldSelectorList->containsFieldSelector("__element", "state_hiddenInfo"))
				$sqlB->insertValue('state_hiddenInfo', $element->getState_hiddenInfo(), MySqlQueryBuilder::SQLTYPE_TEXT);
				if(!isset($elementAttributeFieldSelectorList) || $elementAttributeFieldSelectorList->containsFieldSelector("__element", "state_archived"))
				$sqlB->insertValue('state_archived', $element->isState_archived(), MySqlQueryBuilder::SQLTYPE_BOOLEAN);
				if(!isset($elementAttributeFieldSelectorList) || $elementAttributeFieldSelectorList->containsFieldSelector("__element", "state_archivedInfo"))
				$sqlB->insertValue('state_archivedInfo', $element->getState_archivedInfo(), MySqlQueryBuilder::SQLTYPE_TEXT);
				if(!isset($elementAttributeFieldSelectorList) || $elementAttributeFieldSelectorList->containsFieldSelector("__element", "state_deprecated"))
				$sqlB->insertValue('state_deprecated', $element->isState_deprecated(), MySqlQueryBuilder::SQLTYPE_BOOLEAN);
				if(!isset($elementAttributeFieldSelectorList) || $elementAttributeFieldSelectorList->containsFieldSelector("__element", "state_deprecatedInfo"))
				$sqlB->insertValue('state_deprecatedInfo', $element->getState_deprecatedInfo(), MySqlQueryBuilder::SQLTYPE_TEXT);
				if(!isset($elementAttributeFieldSelectorList) || $elementAttributeFieldSelectorList->containsFieldSelector("__element", "state_finalized"))
				$sqlB->insertValue('state_finalized', $element->isState_finalized(), MySqlQueryBuilder::SQLTYPE_BOOLEAN);
				if(!isset($elementAttributeFieldSelectorList) || $elementAttributeFieldSelectorList->containsFieldSelector("__element", "state_finalizedInfo"))
				$sqlB->insertValue('state_finalizedInfo', $element->getState_finalizedInfo(), MySqlQueryBuilder::SQLTYPE_TEXT);
				if(!isset($elementAttributeFieldSelectorList) || $elementAttributeFieldSelectorList->containsFieldSelector("__element", "state_approved"))
				$sqlB->insertValue('state_approved', $element->isState_approved(), MySqlQueryBuilder::SQLTYPE_BOOLEAN);
				if(!isset($elementAttributeFieldSelectorList) || $elementAttributeFieldSelectorList->containsFieldSelector("__element", "state_approvedInfo"))
				$sqlB->insertValue('state_approvedInfo', $element->getState_approvedInfo(), MySqlQueryBuilder::SQLTYPE_TEXT);
				if(!isset($elementAttributeFieldSelectorList) || $elementAttributeFieldSelectorList->containsFieldSelector("__element", "state_dismissed"))
				$sqlB->insertValue('state_dismissed', $element->isState_dismissed(), MySqlQueryBuilder::SQLTYPE_BOOLEAN);
				if(!isset($elementAttributeFieldSelectorList) || $elementAttributeFieldSelectorList->containsFieldSelector("__element", "state_dismissedInfo"))
				$sqlB->insertValue('state_dismissedInfo', $element->getState_dismissedInfo(), MySqlQueryBuilder::SQLTYPE_TEXT);
				if(!isset($elementAttributeFieldSelectorList) || $elementAttributeFieldSelectorList->containsFieldSelector("__element", "state_blocked"))
				$sqlB->insertValue('state_blocked', $element->isState_blocked(), MySqlQueryBuilder::SQLTYPE_BOOLEAN);
				if(!isset($elementAttributeFieldSelectorList) || $elementAttributeFieldSelectorList->containsFieldSelector("__element", "state_blockedInfo"))
				$sqlB->insertValue('state_blockedInfo', $element->getState_blockedInfo(), MySqlQueryBuilder::SQLTYPE_TEXT);
			} else {
				$sqlB->insertMoreRecord();
				// static attributes
				$sqlB->insertMoreValue($microtime, MySqlQueryBuilder::SQLTYPE_INT);
				$sqlB->insertMoreValue($execId, MySqlQueryBuilder::SQLTYPE_VARCHAR);
				$sqlB->insertMoreValue($counter++, MySqlQueryBuilder::SQLTYPE_VARCHAR);
				$sqlB->insertMoreValue($element->getSys_creationDate(), MySqlQueryBuilder::SQLTYPE_INT);
				$sqlB->insertMoreValue($element->getSys_creationUser(), MySqlQueryBuilder::SQLTYPE_INT);
				$sqlB->insertMoreValue($element->getSys_creationUsername(), MySqlQueryBuilder::SQLTYPE_VARCHAR);
				$sqlB->insertMoreValue($element->getSys_date(), MySqlQueryBuilder::SQLTYPE_INT);
				$sqlB->insertMoreValue($element->getSys_user(), MySqlQueryBuilder::SQLTYPE_INT);
				$sqlB->insertMoreValue($element->getSys_username(), MySqlQueryBuilder::SQLTYPE_VARCHAR);
				$sqlB->insertMoreValue($element->getModule()->getModuleName(), MySqlQueryBuilder::SQLTYPE_VARCHAR);
				// other attributes
				if(!isset($elementAttributeFieldSelectorList) || $elementAttributeFieldSelectorList->containsFieldSelector("__element", "version"))
				$sqlB->insertMoreValue($element->getVersion(), MySqlQueryBuilder::SQLTYPE_VARCHAR);
				if(!isset($elementAttributeFieldSelectorList) || $elementAttributeFieldSelectorList->containsFieldSelector("__element", "state_locked"))
				$sqlB->insertMoreValue($element->isState_locked(), MySqlQueryBuilder::SQLTYPE_BOOLEAN);
				if(!isset($elementAttributeFieldSelectorList) || $elementAttributeFieldSelectorList->containsFieldSelector("__element", "state_lockedInfo"))
				$sqlB->insertMoreValue($element->getState_lockedInfo(), MySqlQueryBuilder::SQLTYPE_TEXT);
				if(!isset($elementAttributeFieldSelectorList) || $elementAttributeFieldSelectorList->containsFieldSelector("__element", "state_important1"))
				$sqlB->insertMoreValue($element->isState_important1(), MySqlQueryBuilder::SQLTYPE_BOOLEAN);
				if(!isset($elementAttributeFieldSelectorList) || $elementAttributeFieldSelectorList->containsFieldSelector("__element", "state_important1Info"))
				$sqlB->insertMoreValue($element->getState_important1Info(), MySqlQueryBuilder::SQLTYPE_TEXT);
				if(!isset($elementAttributeFieldSelectorList) || $elementAttributeFieldSelectorList->containsFieldSelector("__element", "state_important2"))
				$sqlB->insertMoreValue($element->isState_important2(), MySqlQueryBuilder::SQLTYPE_BOOLEAN);
				if(!isset($elementAttributeFieldSelectorList) || $elementAttributeFieldSelectorList->containsFieldSelector("__element", "state_important2Info"))
				$sqlB->insertMoreValue($element->getState_important2Info(), MySqlQueryBuilder::SQLTYPE_TEXT);
				if(!isset($elementAttributeFieldSelectorList) || $elementAttributeFieldSelectorList->containsFieldSelector("__element", "state_hidden"))
				$sqlB->insertMoreValue($element->isState_hidden(), MySqlQueryBuilder::SQLTYPE_BOOLEAN);
				if(!isset($elementAttributeFieldSelectorList) || $elementAttributeFieldSelectorList->containsFieldSelector("__element", "state_hiddenInfo"))
				$sqlB->insertMoreValue($element->getState_hiddenInfo(), MySqlQueryBuilder::SQLTYPE_TEXT);
				if(!isset($elementAttributeFieldSelectorList) || $elementAttributeFieldSelectorList->containsFieldSelector("__element", "state_archived"))
				$sqlB->insertMoreValue($element->isState_archived(), MySqlQueryBuilder::SQLTYPE_BOOLEAN);
				if(!isset($elementAttributeFieldSelectorList) || $elementAttributeFieldSelectorList->containsFieldSelector("__element", "state_archivedInfo"))
				$sqlB->insertMoreValue($element->getState_archivedInfo(), MySqlQueryBuilder::SQLTYPE_TEXT);
				if(!isset($elementAttributeFieldSelectorList) || $elementAttributeFieldSelectorList->containsFieldSelector("__element", "state_deprecated"))
				$sqlB->insertMoreValue($element->isState_deprecated(), MySqlQueryBuilder::SQLTYPE_BOOLEAN);
				if(!isset($elementAttributeFieldSelectorList) || $elementAttributeFieldSelectorList->containsFieldSelector("__element", "state_deprecatedInfo"))
				$sqlB->insertMoreValue($element->getState_deprecatedInfo(), MySqlQueryBuilder::SQLTYPE_TEXT);
				if(!isset($elementAttributeFieldSelectorList) || $elementAttributeFieldSelectorList->containsFieldSelector("__element", "state_approved"))
				$sqlB->insertMoreValue($element->isState_approved(), MySqlQueryBuilder::SQLTYPE_BOOLEAN);
				if(!isset($elementAttributeFieldSelectorList) || $elementAttributeFieldSelectorList->containsFieldSelector("__element", "state_approvedInfo"))
				$sqlB->insertMoreValue($element->getState_approvedInfo(), MySqlQueryBuilder::SQLTYPE_TEXT);
				if(!isset($elementAttributeFieldSelectorList) || $elementAttributeFieldSelectorList->containsFieldSelector("__element", "state_finalized"))
				$sqlB->insertMoreValue($element->isState_finalized(), MySqlQueryBuilder::SQLTYPE_BOOLEAN);
				if(!isset($elementAttributeFieldSelectorList) || $elementAttributeFieldSelectorList->containsFieldSelector("__element", "state_finalizedInfo"))
				$sqlB->insertMoreValue($element->getState_finalizedInfo(), MySqlQueryBuilder::SQLTYPE_TEXT);
				if(!isset($elementAttributeFieldSelectorList) || $elementAttributeFieldSelectorList->containsFieldSelector("__element", "state_dismissed"))
				$sqlB->insertMoreValue($element->isState_dismissed(), MySqlQueryBuilder::SQLTYPE_BOOLEAN);
				if(!isset($elementAttributeFieldSelectorList) || $elementAttributeFieldSelectorList->containsFieldSelector("__element", "state_dismissedInfo"))
				$sqlB->insertMoreValue($element->getState_dismissedInfo(), MySqlQueryBuilder::SQLTYPE_TEXT);
				if(!isset($elementAttributeFieldSelectorList) || $elementAttributeFieldSelectorList->containsFieldSelector("__element", "state_blocked"))
				$sqlB->insertMoreValue($element->isState_blocked(), MySqlQueryBuilder::SQLTYPE_BOOLEAN);
				if(!isset($elementAttributeFieldSelectorList) || $elementAttributeFieldSelectorList->containsFieldSelector("__element", "state_blockedInfo"))
				$sqlB->insertMoreValue($element->getState_blockedInfo(), MySqlQueryBuilder::SQLTYPE_TEXT);
			}
		}
		return $sqlB->getSql();
	}


	public function updateElement($principal, $element, $fieldSelectorList=null)
	{
		$this->executionSink()->publishStartOperation("updateElement", $principal);
		$eltQP = null;
		try
		{
			if(is_null($element)) return 0;
			if($element->isNew()) throw new ElementServiceException("can only update existing elements, use insertElement to insert a new element", ElementServiceException::INVALID_ARGUMENT);
			$elementId = $element->getId();
			$dbCS = $this->getDbAdminService()->getDbConnectionSettings($principal);
			$mysqlF = $this->getMySqlFacade();

			// selects root element of sub-element if exists
			$dbRow = $mysqlF->selectFirst($principal,
					$this->getSqlForGetSubElementPathFromRoot($elementId),
					$dbCS);

			// if sub element
			if(isset($dbRow)) {
				// gets root element of sub element
				$rootElementP = $this->getElementPWithoutFields($principal, $dbRow['id_element_owner']);
				// checks authorization
				$origElement = $this->assertPrincipalAuthorizedForUpdateSubElement($principal, $element, $rootElementP);

				$cS = $this->getSubElementConfigService();
				// centers the configuration on subelement if not done yet
				$ls = $cS->getCurrentLinkSelector();
				if(!isset($ls) ||
					$ls->getOwnerElementId() != $origElement->getElementParentId() ||
					$ls->getFieldName() != $origElement->getLinkName() ||
					$cS->getCurrentModule() !== $origElement->getModule()) {
					$cS->selectSubElementConfig($principal, $elementId);
				}
			}
			// else if root element
			else {
				// checks authorization
				// and reads existing element
				$origElement = $this->assertPrincipalAuthorizedForUpdateElement($principal, $element);

				$cS = $this->getConfigService();
				// if ConfigService supports method unselectSubElementConfig
				// then unselects any previous sub element config
				if($this->subElementConfigSupport['unselectSubElementConfig']) $cS->unselectSubElementConfig($principal);
			}

			// validates element
			$this->validateElementForUpdate($principal, $element, $origElement);

			// computes effective field list
			$eltQP = $this->getElementQueryPlanner(MySqlFacade::Q_UPDATE, 0, $fieldSelectorList);
			//SysInfo at field level requires to have element and principal
			$eltQP->setElement($element);
			$eltQP->setPrincipal($principal);
			$cS->getFields($principal, $origElement->getModule(), null, $eltQP);

			// acquires lock
			$shouldUnlock = $this->lock($principal, $element);
			try
			{
				// updates element
				$returnValue = $mysqlF->update($principal,
						$this->getSqlForUpdateElement($principal, $element, $origElement, $eltQP->getElementAttributeFieldSelectorList()),
						$dbCS);

				//update element fields values
				$n = $eltQP->getNumberOfQueries();
				$strategy = $eltQP->getQueryStrategy();
				for($i = 0; $i < $n; $i++)
				{
					$returnValue += $mysqlF->update($principal, $eltQP->getSql($i,
						$this->getSqlBuilderForUpdateElement($principal, $strategy, $element)),
						$dbCS);
				}
				$eltQP->freeMemory();
				if($returnValue > 0) $returnValue = 1;
			}
			// releases lock
			catch(Exception $ne) {if($shouldUnlock) $this->unLock($principal, $element); throw $ne;}
			if($shouldUnlock) $this->unLock($principal, $element);
		}
		catch (ElementServiceException $ese){
			if(isset($eltQP)) $eltQP->freeMemory();
			$this->executionSink()->publishEndOperationOnError("updateElement", $ese, $principal);
			throw $ese;
		}
		catch (AuthorizationServiceException $asE){
			if(isset($eltQP)) $eltQP->freeMemory();
			$this->executionSink()->publishEndOperationOnError("updateElement", $asE, $principal);
			throw $asE;
		}
		catch(Exception $e)
		{
			if(isset($eltQP)) $eltQP->freeMemory();
			$this->executionSink()->publishEndOperationOnError("updateElement", $e, $principal);
			throw new ElementServiceException('',ElementServiceException::WRAPPING, $e);
		}
		$this->executionSink()->publishEndOperation("updateElement", $principal);
		return $returnValue;
	}
	protected function assertPrincipalAuthorizedForUpdateElement($principal, $element)
	{
		if(is_null($principal)) throw new ElementServiceException('principal can not be null', ElementServiceException::INVALID_ARGUMENT);

		$autoS = $this->getAuthorizationService();
		// checks general authorization
		$pRights = $autoS->assertPrincipalAuthorized($principal, "ElementService", "updateElement");

		if(is_null($pRights)){
			$origElementP = $this->getElementPWithoutFields($principal, $element->getId());
			if(	$origElementP->getRights() == null || !$origElementP->getRights()->canWriteElement() ){
				$autoS->fail($principal, 'has no right to update element '.$origElementP->getId());
			}
			$origElement = $origElementP->getDbEntity();
		} else {
			$origElement = $this->getElementWithoutFields($principal, $element->getId());
		}
		return $origElement;
	}
	protected function assertPrincipalAuthorizedForUpdateSubElement($principal, $element, $rootElementP)
	{
		if(is_null($principal)) throw new ElementServiceException('principal can not be null', ElementServiceException::INVALID_ARGUMENT);

		$autoS = $this->getAuthorizationService();
		// checks general authorization
		$autoS->assertPrincipalAuthorized($principal, "ElementService", "updateElement");
		//check write rights on root element
		if($rootElementP->getRights()==null || !$rootElementP->getRights()->canWriteElement()){
			$autoS->fail($principal, 'has no right to update a sub element of element '.$rootElementP->getId());
		}
		return $this->getElementWithoutFields($principal, $element->getId());
	}
	protected function validateElementForUpdate($principal, $element, $origElement)
	{
		/* nothing to do for now */
	}
	public function setElementServiceGroupPList($elementServiceGroupPList)
	{
		$this->elementServiceGroupPList = $elementServiceGroupPList;
	}
	protected function getElementServiceGroupPList()
	{
		if(!isset($this->elementServiceGroupPList))
		{
			$this->elementServiceGroupPList = ElementServiceGroupPList::createInstance();
		}
		else $this->elementServiceGroupPList->reset();
		return $this->elementServiceGroupPList;
	}
	protected function getSqlForGetElementPRights($principal, $element)
	{
		$principalId = $principal->getUserId();
		if(is_null($principalId)) throw new ElementServiceException('principal attached user can not be null', ElementServiceException::INVALID_ARGUMENT);
		$sqlB = $this->getMySqlFacade()->getSqlBuilder();
		$principalId = $sqlB->formatValue($principalId, MySqlQueryBuilder::SQLTYPE_INT);
		$elementId = $sqlB->formatValue($element->getId(), MySqlQueryBuilder::SQLTYPE_INT);

		return $this->getSqlForSelectElementP($elementId, $this->getSqlForSelectGroupsP($principal, $element->getModule(), $principalId,
			$this->getSqlForSelectElementGroups($elementId, 'EG1'),
			$this->getModuleAdminService()->getDefaultPropagation($element->getModule()), 'UGR1', 'GG1', 'GP1', 'G1'),
			'GP2', 'EG2');
	}
	/**
	 * if lock then locks updated element
	 */
	protected function getSqlForUpdateElement($principal, $element, $origElement, $elementAttributeFieldSelectorList=null)
	{
		if(is_null($element)) throw new ElementServiceException('element can not be null', ElementServiceException::INVALID_ARGUMENT);
		if(is_null($origElement)) throw new ElementServiceException('original element can not be null', ElementServiceException::INVALID_ARGUMENT);

		$sqlB = $this->getMySqlFacade()->getSqlBuilder();
		$sqlB->setTableForUpdate('Elements');

		// dynamic attributes
		if(isset($elementAttributeFieldSelectorList))
		{
			foreach($elementAttributeFieldSelectorList->getListIterator() as $fs)
			{
				$fName = $fs->getSubFieldName();
				switch($fName)
				{
					case 'version': $sqlB->updateValueIfChanged('version', $element->getVersion(), $origElement->getVersion(), MySqlQueryBuilder::SQLTYPE_VARCHAR); break;
					case 'state_locked': $sqlB->updateValueIfChanged('state_locked', $element->isState_locked(), $origElement->isState_locked(), MySqlQueryBuilder::SQLTYPE_BOOLEAN); break;
					case 'state_lockedInfo': $sqlB->updateValueIfChanged('state_lockedInfo', $element->getState_lockedInfo(), $origElement->getState_lockedInfo(), MySqlQueryBuilder::SQLTYPE_TEXT); break;
					case 'state_important1': $sqlB->updateValueIfChanged('state_important1', $element->isState_important1(), $origElement->isState_important1(), MySqlQueryBuilder::SQLTYPE_BOOLEAN); break;
					case 'state_important1Info': $sqlB->updateValueIfChanged('state_important1Info', $element->getState_important1Info(), $origElement->getState_important1Info(), MySqlQueryBuilder::SQLTYPE_TEXT); break;
					case 'state_important2': $sqlB->updateValueIfChanged('state_important2', $element->isState_important2(), $origElement->isState_important2(), MySqlQueryBuilder::SQLTYPE_BOOLEAN); break;
					case 'state_important2Info': $sqlB->updateValueIfChanged('state_important2Info', $element->getState_important2Info(), $origElement->getState_important2Info(), MySqlQueryBuilder::SQLTYPE_TEXT); break;
					case 'state_hidden': $sqlB->updateValueIfChanged('state_hidden', $element->isState_hidden(), $origElement->isState_hidden(), MySqlQueryBuilder::SQLTYPE_BOOLEAN); break;
					case 'state_hiddenInfo': $sqlB->updateValueIfChanged('state_hiddenInfo', $element->getState_hiddenInfo(), $origElement->getState_hiddenInfo(), MySqlQueryBuilder::SQLTYPE_TEXT); break;
					case 'state_archived': $sqlB->updateValueIfChanged('state_archived', $element->isState_archived(), $origElement->isState_archived(), MySqlQueryBuilder::SQLTYPE_BOOLEAN); break;
					case 'state_archivedInfo': $sqlB->updateValueIfChanged('state_archivedInfo', $element->getState_archivedInfo(), $origElement->getState_archivedInfo(), MySqlQueryBuilder::SQLTYPE_TEXT); break;
					case 'state_deprecated': $sqlB->updateValueIfChanged('state_deprecated', $element->isState_deprecated(), $origElement->isState_deprecated(), MySqlQueryBuilder::SQLTYPE_BOOLEAN); break;
					case 'state_deprecatedInfo': $sqlB->updateValueIfChanged('state_deprecatedInfo', $element->getState_deprecatedInfo(), $origElement->getState_deprecatedInfo(), MySqlQueryBuilder::SQLTYPE_TEXT); break;
					case 'state_finalized': $sqlB->updateValueIfChanged('state_finalized', $element->isState_finalized(), $origElement->isState_finalized(), MySqlQueryBuilder::SQLTYPE_BOOLEAN); break;
					case 'state_finalizedInfo': $sqlB->updateValueIfChanged('state_finalizedInfo', $element->getState_finalizedInfo(), $origElement->getState_finalizedInfo(), MySqlQueryBuilder::SQLTYPE_TEXT); break;
					case 'state_approved': $sqlB->updateValueIfChanged('state_approved', $element->isState_approved(), $origElement->isState_approved(), MySqlQueryBuilder::SQLTYPE_BOOLEAN); break;
					case 'state_approvedInfo': $sqlB->updateValueIfChanged('state_approvedInfo', $element->getState_approvedInfo(), $origElement->getState_approvedInfo(), MySqlQueryBuilder::SQLTYPE_TEXT); break;
					case 'state_dismissed': $sqlB->updateValueIfChanged('state_dismissed', $element->isState_dismissed(), $origElement->isState_dismissed(), MySqlQueryBuilder::SQLTYPE_BOOLEAN); break;
					case 'state_dismissedInfo': $sqlB->updateValueIfChanged('state_dismissedInfo', $element->getState_dismissedInfo(), $origElement->getState_dismissedInfo(), MySqlQueryBuilder::SQLTYPE_TEXT); break;
					case 'state_blocked': $sqlB->updateValueIfChanged('state_blocked', $element->isState_blocked(), $origElement->isState_blocked(), MySqlQueryBuilder::SQLTYPE_BOOLEAN); break;
					case 'state_blockedInfo': $sqlB->updateValueIfChanged('state_blockedInfo', $element->getState_blockedInfo(), $origElement->getState_blockedInfo(), MySqlQueryBuilder::SQLTYPE_TEXT); break;
					// ignore static fields
					case 'sys_date':
					case 'sys_user':
					case 'sys_username':
					case 'sys_lockMicroTime':
					case 'sys_lockId':
					// subitem infos : id_element_parent, linkName, peerId are not persisted here.
					case 'id_element_parent':
					case 'linkName':
					case 'peerId':
						break;
					default: throw new ElementServiceException("element attribute $fName can not be updated or is invalid", ElementServiceException::INVALID_ARGUMENT);
				}
			}
		}
		else
		{
			$sqlB->updateValueIfChanged('version', $element->getVersion(), $origElement->getVersion(), MySqlQueryBuilder::SQLTYPE_VARCHAR);
			$sqlB->updateValueIfChanged('state_locked', $element->isState_locked(), $origElement->isState_locked(), MySqlQueryBuilder::SQLTYPE_BOOLEAN);
			$sqlB->updateValueIfChanged('state_lockedInfo', $element->getState_lockedInfo(), $origElement->getState_lockedInfo(), MySqlQueryBuilder::SQLTYPE_TEXT);
			$sqlB->updateValueIfChanged('state_important1', $element->isState_important1(), $origElement->isState_important1(), MySqlQueryBuilder::SQLTYPE_BOOLEAN);
			$sqlB->updateValueIfChanged('state_important1Info', $element->getState_important1Info(), $origElement->getState_important1Info(), MySqlQueryBuilder::SQLTYPE_TEXT);
			$sqlB->updateValueIfChanged('state_important2', $element->getState_important2(), $origElement->isState_important2(), MySqlQueryBuilder::SQLTYPE_BOOLEAN);
			$sqlB->updateValueIfChanged('state_important2Info', $element->getState_important2Info(), $origElement->getState_important2Info(), MySqlQueryBuilder::SQLTYPE_TEXT);
			$sqlB->updateValueIfChanged('state_hidden', $element->isState_hidden(), $origElement->isState_hidden(), MySqlQueryBuilder::SQLTYPE_BOOLEAN);
			$sqlB->updateValueIfChanged('state_hiddenInfo', $element->getState_hiddenInfo(), $origElement->getState_hiddenInfo(), MySqlQueryBuilder::SQLTYPE_TEXT);
			$sqlB->updateValueIfChanged('state_archived', $element->isState_archived(), $origElement->isState_archived(), MySqlQueryBuilder::SQLTYPE_BOOLEAN);
			$sqlB->updateValueIfChanged('state_archivedInfo', $element->getState_archivedInfo(), $origElement->getState_archivedInfo(), MySqlQueryBuilder::SQLTYPE_TEXT);
			$sqlB->updateValueIfChanged('state_deprecated', $element->isState_deprecated(), $origElement->isState_deprecated(), MySqlQueryBuilder::SQLTYPE_BOOLEAN);
			$sqlB->updateValueIfChanged('state_deprecatedInfo', $element->getState_deprecatedInfo(), $origElement->getState_deprecatedInfo(), MySqlQueryBuilder::SQLTYPE_TEXT);
			$sqlB->updateValueIfChanged('state_finalized', $element->isState_finalized(), $origElement->isState_finalized(), MySqlQueryBuilder::SQLTYPE_BOOLEAN);
			$sqlB->updateValueIfChanged('state_finalizedInfo', $element->getState_finalizedInfo(), $origElement->getState_finalizedInfo(), MySqlQueryBuilder::SQLTYPE_TEXT);
			$sqlB->updateValueIfChanged('state_approved', $element->isState_approved(), $origElement->isState_approved(), MySqlQueryBuilder::SQLTYPE_BOOLEAN);
			$sqlB->updateValueIfChanged('state_approvedInfo', $element->getState_approvedInfo(), $origElement->getState_approvedInfo(), MySqlQueryBuilder::SQLTYPE_TEXT);
			$sqlB->updateValueIfChanged('state_dismissed', $element->isState_dismissed(), $origElement->isState_dismissed(), MySqlQueryBuilder::SQLTYPE_BOOLEAN);
			$sqlB->updateValueIfChanged('state_dismissedInfo', $element->getState_dismissedInfo(), $origElement->getState_dismissedInfo(), MySqlQueryBuilder::SQLTYPE_TEXT);
			$sqlB->updateValueIfChanged('state_blocked', $element->isState_blocked(), $origElement->isState_blocked(), MySqlQueryBuilder::SQLTYPE_BOOLEAN);
			$sqlB->updateValueIfChanged('state_blockedInfo', $element->getState_blockedInfo(), $origElement->getState_blockedInfo(), MySqlQueryBuilder::SQLTYPE_TEXT);
			// subitem infos : id_element_parent, linkName, peerId are not persisted here.
		}

		// static attributes
		$sqlB->updateSysUser($principal);

		$sqlB->setWhereClauseSingleId('id_element', $element->getId());
		return $sqlB->getSql();
	}
	protected function getSqlBuilderForUpdateElement($principal, $queryStrategy, $element)
	{
		// autowired
		if(!isset($this->sqlBuilderForUpdateElement)) $this->sqlBuilderForUpdateElement = array();
		$sqlB = $this->sqlBuilderForUpdateElement[$queryStrategy];
		if(!isset($sqlB))
		{
			switch($queryStrategy)
			{
				case ElementQueryPlanner::QSTRATEGY_FIELD:
					$sqlB = ElementSqlBuilder::createInstance();
					$this->sqlBuilderForUpdateElement[ElementQueryPlanner::QSTRATEGY_FIELD] = $sqlB;
					break;
				default: throw new ElementServiceException('unsupported query strategy', ElementServiceException::INVALID_ARGUMENT);
			}
		}
		else
		{
			$sqlB->reset();
		}
		$sqlB->setTripod($this->getTripod());
		$sqlB->setElement($element);
		return $sqlB;
	}
	protected function getSqlBuilderForUpdateMultipleElement($principal, $queryStrategy, $element, $idsAreNew=false)
	{
		// autowired
		if(!isset($this->sqlBuilderForUpdateMultipleElement)) $this->sqlBuilderForUpdateMultipleElement = array();
		$sqlB = $this->sqlBuilderForUpdateMultipleElement[$queryStrategy];
		if(!isset($sqlB))
		{
			switch($queryStrategy)
			{
				case ElementQueryPlanner::QSTRATEGY_FIELD:
					$sqlB = ElementSqlBuilderForMultipleElement::createInstance($principal, $element, $idsAreNew);
					$this->sqlBuilderForUpdateElement[ElementQueryPlanner::QSTRATEGY_FIELD] = $sqlB;
					break;
				default: throw new ElementServiceException('unsupported query strategy', ElementServiceException::INVALID_ARGUMENT);
			}
		}
		else
		{
			$sqlB->reset();
		}
		$sqlB->setTripod($this->getTripod());
		$sqlB->setElement($element);
		return $sqlB;
	}

	public function deleteElement($principal, $elementId)
	{
		$this->executionSink()->publishStartOperation("deleteElement", $principal);
		try
		{
			if(is_null($elementId)) return 0;

			$dbAS = $this->getDbAdminService();
			$dbCS = $dbAS->getDbConnectionSettings($principal);
			$mySqlF = $this->getMySqlFacade();

			// selects root element of sub-element if exists
			$dbRow = $mySqlF->selectFirst($principal,
					$this->getSqlForGetSubElementPathFromRoot($elementId),
					$dbCS);

			// if sub element
			if(isset($dbRow)) {
				// gets root element of sub element
				$rootElementP = $this->getElementPWithoutFields($principal, $dbRow['id_element_owner']);
				// checks authorization
				$this->assertPrincipalAuthorizedForDeleteSubElement($principal, $rootElementP);

				// fills element instance with info for lock and delete of subitem
				$element = Element::createInstance(null);
				$element->setId($elementId);
				$element->setElementParentId($rootElementP->getId());

				// acquires lock
				$shouldUnlock = $this->lock($principal, $element);
				try
				{
					// updates Links value
					$step = 0;
					$mySqlF->insertOne($principal,
							$this->getSqlForUpateSubElementLinksValue($principal, $elementId, true),
							$dbCS);

					// deletes sub element
					$step++;
					if($dbAS->deleteRows($principal,
						$this->getSqlTableNameForDeleteSubElement(),
						$this->getSqlWhereClauseForDeleteSubElement($principal, $element),
						$dbCS) > 0) $returnValue = 1;
					else $returnValue = 0;
				}
				catch(Exception $ne) {
					// if links value has been modified, restores correct value
					if($step > 0) $mySqlF->insertOne($principal,
							$this->getSqlForUpateSubElementLinksValue($principal, $elementId, false),
							$dbCS);
					// releases lock
					if($shouldUnlock) $this->unLock($principal, $element);
					throw $ne;
				}
				if($shouldUnlock) $this->unLock($principal, $element);
			}
			// else root element
			else {

				// reads existing element
				$elementP = $this->getElementPWithoutFields($principal, $elementId);
				if(is_null($elementP)) return 0;

				// checks authorization
				$this->assertPrincipalAuthorizedForDeleteElement($principal, $elementP);
				$element = $elementP->getElement();

				//get all groups where we can write
				//the element will be only deleted in those groups
				//then if orphan he will be really deleted, other wise just unshared
				$groupListAdvancedImpl = GroupListAdvancedImpl::createInstance(false);
				$gAS = $this->getGroupAdminServiceImpl();
				$gAS->getAllGroups($principal, $element->getModule(), $groupListAdvancedImpl, ListFilter::createInstance()->setFieldSelectorList($gAS->getFieldSelectorListForGroupWithoutDetail()));

				// acquires lock
				$shouldUnlock = $this->lock($principal, $element);
				try
				{
					// deletes element
					if($dbAS->deleteRows($principal,
						$this->getSqlTableNameForDeleteElement(),
						$this->getSqlWhereClauseForDeleteElement($principal, $element, $groupListAdvancedImpl),
						$dbCS) > 0) $returnValue = 1;
					else $returnValue = 0;
				}
				// releases lock
				catch(Exception $ne) {if($shouldUnlock) $this->unLock($principal, $element); throw $ne;}
				if($shouldUnlock) $this->unLock($principal, $element);
			}
		}
		catch (ElementServiceException $esE){
			$this->executionSink()->publishEndOperationOnError("deleteElement", $esE, $principal);
			throw $esE;
		}
		catch (AuthorizationServiceException $asE){
			$this->executionSink()->publishEndOperationOnError("deleteElement", $asE, $principal);
			throw $asE;
		}
		catch(Exception $e)
		{
			$this->executionSink()->publishEndOperationOnError("deleteElement", $e, $principal);
			throw new ElementServiceException('',ElementServiceException::WRAPPING, $e);
		}
		$this->executionSink()->publishEndOperation("deleteElement", $principal);
		return $returnValue;
	}
	protected function assertPrincipalAuthorizedForDeleteElement($principal, $elementP)
	{
		if(is_null($principal)) throw new ElementServiceException('principal can not be null', ElementServiceException::INVALID_ARGUMENT);
		if(is_null($elementP)) throw new ElementServiceException('elementP can not be null', ElementServiceException::INVALID_ARGUMENT);
		$module = $elementP->getElement()->getModule();
		if(is_null($module)) throw new ElementServiceException('element->module can not be null', ElementServiceException::INVALID_ARGUMENT);

		$autoS = $this->getAuthorizationService();
		// checks general authorization
		$autoS->assertPrincipalAuthorized($principal, "ElementService", "deleteElement");
		// checks module access
		if(!$autoS->isRootPrincipal($principal) && is_null($principal->getModuleAccess($module))) $autoS->fail($principal, 'has no access to module '.$module->getModuleName());
		// check specific rights before deletion
		$pRights = $elementP->getRights();
		if(!isset($pRights) || !$pRights->canWriteElement())
		{
			$autoS->fail($principal, "has no rights to delete element ".$elementP->getElement()->getId());
		}
	}
	protected function getSqlTableNameForDeleteElement()
	{
		return 'Elements_Groups';
	}
	protected function getSqlWhereClauseForDeleteElement($principal, $element, $groupListAdvancedImpl)
	{
		if(is_null($element)) throw new ElementServiceException('element can not be null', ElementServiceException::INVALID_ARGUMENT);
		$sqlB = $this->getMySqlFacade()->getSqlBuilder();


		//if principal is creator of the element, then delete element_groups in SGroups
		if($element->getSys_creationUser() == $principal->getRealUserId()){
			$elementId = $sqlB->formatBinExp('Elements_Groups.id_element', '=', $element->getId(), MySqlQueryBuilder::SQLTYPE_INT);
			$groupIds = $sqlB->formatBinExp('Elements_Groups.id_group', 'IN', $groupListAdvancedImpl->getSGroups()->getGroupIds(), MySqlQueryBuilder::SQLTYPE_INT);
		//else delete element_groups in WriteGroups
		} else {
			$elementId = $sqlB->formatBinExp('Elements_Groups.id_element', '=', $element->getId(), MySqlQueryBuilder::SQLTYPE_INT);
			$groupIds = $sqlB->formatBinExp('Elements_Groups.id_group', 'IN', $groupListAdvancedImpl->getWriteGroups()->getGroupIds(), MySqlQueryBuilder::SQLTYPE_INT);
		}
		return $elementId." and ".$groupIds;
	}
	protected function assertPrincipalAuthorizedForDeleteSubElement($principal, $rootElementP)
	{
		if(is_null($principal)) throw new ElementServiceException('principal can not be null', ElementServiceException::INVALID_ARGUMENT);

		$autoS = $this->getAuthorizationService();
		// checks general authorization
		$autoS->assertPrincipalAuthorized($principal, "ElementService", "deleteElement");
		//check write rights on root element
		if($rootElementP->getRights()==null || !$rootElementP->getRights()->canWriteElement()){
			$autoS->fail($principal, 'has no right to delete a sub element of element '.$rootElementP->getId());
		}
	}
	protected function getSqlTableNameForDeleteSubElement()
	{
		return 'Elements';
	}
	protected function getSqlWhereClauseForDeleteSubElement($principal, $element)
	{
		if(is_null($element) || !$element->isSubElement()) throw new ElementServiceException('element must be a non null sub element', ElementServiceException::INVALID_ARGUMENT);
		$sqlB = $this->getMySqlFacade()->getSqlBuilder();
		return $sqlB->formatBinExp('Elements.id_element', '=', $element->getId(), MySqlQueryBuilder::SQLTYPE_INT);
	}

	public function deleteMultipleElements($rootPrincipal, $principal, $elementPAdvancedList)
	{
		$this->executionSink()->publishStartOperation("deleteMultipleElements", $principal);
		try
		{
			if(is_null($elementPAdvancedList) || $elementPAdvancedList->isEmpty()) return 0;

			// checks authorization
			$this->assertPrincipalAuthorizedForDeleteMultipleElements($rootPrincipal, $principal, $elementPAdvancedList);

			//get all groups where we can write
			//the element will be only deleted in those groups
			//then if orphan he will be really deleted, other wise just unshared
			$groupList = GroupListAdvancedImpl::createInstance(false);
			$gAS = $this->getGroupAdminServiceImpl();
			$firstElement = reset($elementPAdvancedList->getListIterator())->getDbEntity();
			$gAS->getAllGroups($principal, $firstElement->getModule(), $groupList, ListFilter::createInstance()->setFieldSelectorList($gAS->getFieldSelectorListForGroupWithoutDetail()));

			$dbAS = $this->getDbAdminService();
			$dbCS = $dbAS->getDbConnectionSettings($principal);

			// acquires lock
			$shouldUnlock = $this->lock($principal, $elementPAdvancedList);
			try
			{
				// deletes element
				if($dbAS->deleteRows($principal,
					$this->getSqlTableNameForDeleteElement(),
					$this->getSqlWhereClauseForDeleteMultipleElements($principal, $elementPAdvancedList, $groupList),
					$dbCS) > 0) $returnValue = 1;
				else $returnValue = 0;
			}
			// releases lock
			catch(Exception $ne) {if($shouldUnlock) $this->unLock($principal, $elementPAdvancedList); throw $ne;}
			if($shouldUnlock) $this->unLock($principal, $elementPAdvancedList);
		}
		catch (ElementServiceException $esE){
			$this->executionSink()->publishEndOperationOnError("deleteMultipleElements", $esE, $principal);
			throw $esE;
		}
		catch (AuthorizationServiceException $asE){
			$this->executionSink()->publishEndOperationOnError("deleteMultipleElements", $asE, $principal);
			throw $asE;
		}
		catch(Exception $e)
		{
			$this->executionSink()->publishEndOperationOnError("deleteMultipleElements", $e, $principal);
			throw new ElementServiceException('',ElementServiceException::WRAPPING, $e);
		}
		$this->executionSink()->publishEndOperation("deleteMultipleElements", $principal);
		return $returnValue;
	}
	protected function assertPrincipalAuthorizedForDeleteMultipleElements($rootPrincipal, $principal, $elementPAdvancedList)
	{
		if(is_null($rootPrincipal)) throw new ElementServiceException('rootPrincipal can not be null', ElementServiceException::INVALID_ARGUMENT);
		if(is_null($principal)) throw new ElementServiceException('principal can not be null', ElementServiceException::INVALID_ARGUMENT);
		if(is_null($elementPAdvancedList) || $elementPAdvancedList->isEmpty()) throw new ElementServiceException('elementPAdvancedList can not be null', ElementServiceException::INVALID_ARGUMENT);

		$autoS = $this->getAuthorizationService();
		// checks general authorization
		$autoS->assertPrincipalAuthorized($rootPrincipal, "ElementService", "deleteMultipleElements");
		// check specific rights before deletion
		if(!$elementPAdvancedList->allHaveWriteRights())
		{
			$autoS->fail($principal, "has no rights to delete all elements ".implode(", ", $elementPAdvancedList->getElementIds()));
		}
	}
	protected function getSqlWhereClauseForDeleteMultipleElements($principal, $elementPAdvancedList, $groupListAdvancedImpl)
	{
		if(is_null($elementPAdvancedList) || $elementPAdvancedList->isEmpty()) throw new ElementServiceException('elementPAdvancedList can not be null', ElementServiceException::INVALID_ARGUMENT);
		if(is_null($groupListAdvancedImpl) || $groupListAdvancedImpl->isEmpty()) throw new ElementServiceException('groupListAdvancedImpl can not be null', ElementServiceException::INVALID_ARGUMENT);

		$sqlB = $this->getMySqlFacade()->getSqlBuilder();

		$elementIds = $sqlB->formatBinExp('Elements_Groups.id_element', 'IN', $elementPAdvancedList->getElementIds(), MySqlQueryBuilder::SQLTYPE_INT);
		$writeGroupIds = $sqlB->formatBinExp('Elements_Groups.id_group', 'IN', $groupListAdvancedImpl->getWriteGroups()->getGroupIds(), MySqlQueryBuilder::SQLTYPE_INT);
		$ownedElementIds = ($elementPAdvancedList->getSysCreationUserElementPList($principal->getRealUserId()) ? $sqlB->formatBinExp('Elements_Groups.id_element', 'IN', $elementPAdvancedList->getSysCreationUserElementPList($principal->getRealUserId())->getIds(), MySqlQueryBuilder::SQLTYPE_INT) : null);
		if($ownedElementIds) $shareGroupIds = $sqlB->formatBinExp('Elements_Groups.id_group', 'IN', $groupListAdvancedImpl->getSGroups()->getGroupIds(), MySqlQueryBuilder::SQLTYPE_INT);

		return "($elementIds and $writeGroupIds)".($ownedElementIds ? " or ($ownedElementIds and $shareGroupIds)" : "");
	}

	public function searchElements($principal, $activityRecord, $elementListFilter, $elementSearchResultList)
	{
		throw new ElementServiceException("not implemented", ElementServiceException::NOT_IMPLEMENTED);
	}

	public function evaluateElementList($principal, $elementList, $elementEvaluator)
	{
		$this->executionSink()->publishStartOperation("evaluateElementList", $principal);
		try
		{
			if(is_null($elementList)) throw new ElementServiceException("elementList cannot be null", ElementServiceException::INVALID_ARGUMENT);
			if(is_null($elementEvaluator)) throw new ElementServiceException("elementEvalutaor cannot be null", ElementServiceException::INVALID_ARGUMENT);
			// evaluates the list
			$first = true;
			foreach($elementList->getListIterator() as $element)
			{
				// first extracts calculated fields
				if($first)
				{
					$first = false;
					$calculatedFieldList = FieldListArrayImpl::createInstance();
					foreach($element->getDbEntity()->getFieldList()->getListIterator() as $field)
					{
						if($field->isCalculated()) $calculatedFieldList->addField($field);
					}
				}
				// evaluates each calculated field
				foreach($calculatedFieldList->getListIterator() as $field)
				{
					$elementEvaluator->evaluateElement($principal, $element->getDbEntity(), $field, true);
				}
			}
			$elementEvaluator->freeMemory();
		}
		catch (ElementServiceException $esE){
			if(isset($elementEvaluator)) $elementEvaluator->freeMemory();
			$this->executionSink()->publishEndOperationOnError("evaluateElementList", $esE, $principal);
			throw $esE;
		}
		catch (AuthorizationServiceException $asE){
			if(isset($elementEvaluator)) $elementEvaluator->freeMemory();
			$this->executionSink()->publishEndOperationOnError("evaluateElementList", $asE, $principal);
			throw $asE;
		}
		catch(Exception $e)
		{
			if(isset($elementEvaluator)) $elementEvaluator->freeMemory();
			$this->executionSink()->publishEndOperationOnError("evaluateElementList", $e, $principal);
			throw new ElementServiceException('',ElementServiceException::WRAPPING, $e);
		}
		$this->executionSink()->publishEndOperation("evaluateElementList", $principal);
	}
	protected function assertPrincipalAuthorizedForEvaluateElementList($principal)
	{
		$autoS = $this->getAuthorizationService();
		// checks general authorization
		$autoS->assertPrincipalAuthorized($principal, "ElementService", "evaluateElementList");
	}

	public function evaluateSelectedElementsInGroups($principal, $inGroupLogExp)
	{
		$this->executionSink()->publishStartOperation("evaluateSelectedElementsInGroups", $principal);
		try
		{
			$listFilter = ListFilter::createInstance();
			$listFilter->setPageSize($this->getPageSizeForEvaluateSelectedElementsInGroups());
			$page = 1; $eltCount = 0;
			$listFilter->setDesiredPageNumber($page);
			$elementPAList = ElementPAdvancedListArrayImpl::createInstance(null, null, true);
			// fetches first page of elements in selected groups, evaluator is applied
			$eltCount += $this->getSelectedElementsInGroups($principal, $inGroupLogExp, $elementPAList, $listFilter);

			if($eltCount > 0)
			{
				// gets first element
				$firstElement = null;
				foreach($elementPAList->getListIterator() as $elementP)
				{
					$firstElement = $elementP;
					break;
				}
				// creates fieldSelectorList with calculated fields
				$fsl = FieldSelectorListArrayImpl::createInstance();
				foreach($firstElement->getDbEntity()->getFieldList()->getListIterator() as $field)
				{
					if($field->isCalculated()) $fsl->addFieldSelector($field->getFieldName());
				}
				// updates calculated fields in database
				$rootPrincipal = $this->getRootPrincipal();
				$this->updateMultipleElement($rootPrincipal, $principal, $elementPAList, $fsl);
				$page++;
				while($eltCount < $listFilter->getTotalNumberOfObjects())
				{
					$listFilter->setDesiredPageNumber($page);
					$elementPAList->reset(null, null, true);
					// fetches next page of elements in selected groups, evaluator is applied
					$eltCount += $this->getSelectedElementsInGroups($principal, $inGroupLogExp, $elementPAList, $listFilter);
					// updates next page of calculated fields in database
					$this->updateMultipleElement($rootPrincipal, $principal, $elementPAList, $fsl);
					$page++;
				}
			}
		}
		catch (ElementServiceException $esE){
			$this->executionSink()->publishEndOperationOnError("evaluateSelectedElementsInGroups", $esE, $principal);
			throw $esE;
		}
		catch (AuthorizationServiceException $asE){
			$this->executionSink()->publishEndOperationOnError("evaluateSelectedElementsInGroups", $asE, $principal);
			throw $asE;
		}
		catch(Exception $e)
		{
			$this->executionSink()->publishEndOperationOnError("evaluateSelectedElementsInGroups", $e, $principal);
			throw new ElementServiceException('',ElementServiceException::WRAPPING, $e);
		}
		$this->executionSink()->publishEndOperation("evaluateSelectedElementsInGroups", $principal);
	}
	protected function assertPrincipalAuthorizedForEvaluateSelectedElementsInGroups($principal)
	{
		$autoS = $this->getAuthorizationService();
		// checks general authorization
		$autoS->assertPrincipalAuthorized($principal, "ElementService", "evaluateSelectedElementsInGroups");
	}
	protected function getPageSizeForEvaluateSelectedElementsInGroups()
	{
		return 100;
	}

	protected function getWigiiBagIndicatorEvaluator($indicator){
		return WigiiBagIndicatorEvaluatorImpl::createInstance($indicator);
	}
	protected function getElementListIndicatorEvaluator($indicator){
		return ElementListIndicatorEvaluatorImpl::createInstance($indicator);
	}
	/**
	 * Evaluate the indicator in all elements in groups matching the listFilter
	 * the fieldSelectorList of the listFilter will be updated with the current indicator fieldSelector and subfields
	 */
	public function evaluateIndicatorOnElementsInGroups($principal, $indicator, $inGroupLogExp, $listFilter=null)
	{
		$this->executionSink()->publishStartOperation("evaluateIndicatorOnElementsInGroups", $principal);
		$returnValue = null;
		try
		{
			// checks authorization
			$this->assertPrincipalAuthorizedForEvaluateIndicatorOnElementsInGroups($principal);

			if($indicator->getFieldSelector()->isElementAttributeSelector()){
				//ElementListImplemantation
				$el = $this->getElementListIndicatorEvaluator($indicator);
				if($el->isIndicatorFunctionSupported($indicator)){
					//fill elementList
					$fsl = FieldSelectorListArrayWebImpl::createInstance(true, false);
					foreach($indicator->getSubFields() as $subFieldName){
						$fsl->addFieldSelector($indicator->getFieldSelector()->getFieldName(), $subFieldName);
					}
					//because of an unexpected exception when the fieldSelector contains no field
					//we define an empty one which cause to take everything and slows a lot the system
					$listFilter->setFieldSelectorList(null);
					$this->getSelectedElementsInGroups($principal, $inGroupLogExp, $el, $listFilter);
					//evaluate indicator
					$returnValue = $el->evaluateIndicator($principal, $indicator);
				} else {
					throw new ElementServiceException("indicator function ".$indicator->getFunction()." is not supported in ElementListIndicatorEvaluator implementation", ElementServiceException::UNSUPPORTED_OPERATION);
				}
			} else {
				//WigiiBagIndicatorEvaluator implementation
				//this could be changed to implement a mysql implementation if needed
				$wb = $this->getWigiiBagIndicatorEvaluator($indicator);
				if($wb->isIndicatorFunctionSupported($indicator)){
					//fill wigiiBab
					$fsl = FieldSelectorListArrayWebImpl::createInstance(true, false);
					foreach($indicator->getSubFields() as $subFieldName){
						$fsl->addFieldSelector($indicator->getFieldSelector()->getFieldName(), $subFieldName);
					}
					$listFilter->setFieldSelectorList($fsl);
					$this->fillWigiiBagWithSelectedElementsInGroups($principal, $inGroupLogExp, $wb, $listFilter);
					//evaluate indicator
					$returnValue = $wb->evaluateIndicator($principal, $indicator);
				} else {
					throw new ElementServiceException("indicator function ".$indicator->getFunction()." is not supported in WigiiBagIndicatorEvaluator implementation", ElementServiceException::UNSUPPORTED_OPERATION);
				}
			}
		}
		catch (ElementServiceException $esE){
			$this->executionSink()->publishEndOperationOnError("evaluateIndicatorOnElementsInGroups", $esE, $principal);
			throw $esE;
		}
		catch (AuthorizationServiceException $asE){
			$this->executionSink()->publishEndOperationOnError("evaluateIndicatorOnElementsInGroups", $asE, $principal);
			throw $asE;
		}
		catch(Exception $e)
		{
			$this->executionSink()->publishEndOperationOnError("evaluateIndicatorOnElementsInGroups", $e, $principal);
			throw new ElementServiceException('',ElementServiceException::WRAPPING, $e);
		}
		$this->executionSink()->publishEndOperation("evaluateIndicatorOnElementsInGroups", $principal);
		return $returnValue;
	}
	protected function assertPrincipalAuthorizedForEvaluateIndicatorOnElementsInGroups($principal)
	{
		$autoS = $this->getAuthorizationService();
		// checks general authorization
		$autoS->assertPrincipalAuthorized($principal, "ElementService", "evaluateIndicatorOnElementsInGroups");
	}


	// Sub elements

	public function getSubElementPathFromRoot($principal, $subElementId, $linkSelectorList)
	{
		$this->executionSink()->publishStartOperation("getSubElementPathFromRoot", $principal);
		$linkSelectorListMapper = null;
		try
		{
			if(is_null($linkSelectorList)) throw new ElementServiceException('linkSelectorList cannot be null', ElementServiceException::INVALID_ARGUMENT);

			// checks authorization
			$this->assertPrincipalAuthorizedForGetSubElementPathFromRoot($principal);

			// gets link selectors
			$linkSelectorListMapper = $this->getLinkSelectorListMapper($linkSelectorList);
			$this->getMySqlFacade()->selectAll($principal,
					$this->getSqlForGetSubElementPathFromRoot($subElementId),
					$this->getDbAdminService()->getDbConnectionSettings($principal),
					$linkSelectorListMapper);
			$returnValue = $linkSelectorListMapper->count();
			$linkSelectorListMapper->freeMemory();
		}
		catch (ElementServiceException $esE){
			if(isset($linkSelectorListMapper)) $linkSelectorListMapper->freeMemory();
			$this->executionSink()->publishEndOperationOnError("getSubElementPathFromRoot", $esE, $principal);
			throw $esE;
		}
		catch (AuthorizationServiceException $asE){
			if(isset($linkSelectorListMapper)) $linkSelectorListMapper->freeMemory();
			$this->executionSink()->publishEndOperationOnError("getSubElementPathFromRoot", $asE, $principal);
			throw $asE;
		}
		catch(Exception $e)
		{
			if(isset($linkSelectorListMapper)) $linkSelectorListMapper->freeMemory();
			$this->executionSink()->publishEndOperationOnError("getSubElementPathFromRoot", $e, $principal);
			throw new ElementServiceException('',ElementServiceException::WRAPPING, $e);
		}
		$this->executionSink()->publishEndOperation("getSubElementPathFromRoot", $principal);
		return $returnValue;
	}
	protected function assertPrincipalAuthorizedForGetSubElementPathFromRoot($principal)
	{
		// checks general authorization
		$this->getAuthorizationService()->assertPrincipalAuthorized($principal, "ElementService", "getSubElementPathFromRoot");
	}
	protected function getSqlForGetSubElementPathFromRoot($subElementId)
	{
		if(is_null($subElementId)) throw new ElementServiceException("subElementId cannot be null", ElementServiceException::INVALID_ARGUMENT);
		$sqlB = $this->getMySqlFacade()->getSqlBuilder();
		$subElementId = $sqlB->formatBinExp("EE.id_element", "=", $subElementId, MySqlQueryBuilder::SQLTYPE_INT);
		return "select EE.id_element_owner, E2.modulename, EE.linkName, E2.state_blocked from Elements_Elements EE
inner join Elements as E on E.id_element = EE.id_element and E.id_element_parent is not null
inner join Elements as E2 on E2.id_element = EE.id_element_owner
where $subElementId and EE.linkType = 'subitem' order by EE.id_elements_elements asc";
	}

	public function getSubElementsForField($principal, $masterElementId, $fieldName, $elementPList, $listFilter=null)
	{
		$this->executionSink()->publishStartOperation("getSubElementsForField", $principal);
		$elementPMapper = null;
		$eltQP = null;
		try
		{
			if(is_null($elementPList)) throw new ElementServiceException('elementPList can not be null', ElementServiceException::INVALID_ARGUMENT);
			if(is_null($masterElementId) || empty($fieldName)) throw new ElementServiceException("masterElementId or fieldName cannot be null", ElementServiceException::INVALID_ARGUMENT);

			$seCS = $this->getSubElementConfigService();
			// centers the configuration on subelement if not done yet
			$ls = $seCS->getCurrentLinkSelector();
			if(!isset($ls) ||
				$ls->getOwnerElementId() != $masterElementId ||
				$ls->getFieldName() != $fieldName) {
				$seCS->selectSubElementsConfig($principal, $masterElementId, $fieldName);
			}

			// fetches root element with principal rights
			$pathFromRoot = $seCS->getCurrentSubElementPathFromRoot();
			$rootLs = $pathFromRoot->getFirstLinkSelector();
			$rootElementP = $this->getElementPWithoutFields($principal, $rootLs->getOwnerElementId());

			// checks authorization
			$pRights = $this->assertPrincipalAuthorizedForGetSubElementsForField($principal, $rootElementP);

			$returnValue = null;

			// initializes pagination, sorting, etc.
			$fieldSelectorList = null;
			$fieldSelectorLogExp = null;
			$fieldSortingKeyList = null;
			$queryPaged = false;
			if(isset($listFilter))
			{
				$fieldSelectorList = $listFilter->getFieldSelectorList();
				$fieldSelectorLogExp = $listFilter->getFieldSelectorLogExp();
				$fieldSortingKeyList = $listFilter->getFieldSortingKeyList();
				if($listFilter->isPaged())
				{
					$queryPaged = true;
					$pageSize = $listFilter->getPageSize();
					$offset = ($listFilter->getDesiredPageNumber() - 1) * $pageSize;
				}
			}

			// computes effective field list
			$eltQP = $this->getElementQueryPlanner(MySqlFacade::Q_SELECTALL, $this->getReservedSqlJoinsForGetSubElementsForField(),
							$fieldSelectorList, $fieldSelectorLogExp, $fieldSortingKeyList);
			if($eltQP->areFieldSelected())
			{
				$seCS->getFields($principal, $seCS->getCurrentModule(), null, $eltQP);
			}

			// gets elements
			$elementPMapper = $this->getElementPMapper($principal, $elementPList, $pRights,
				$this->getElementEvaluatorForModule($principal, $seCS->getCurrentModule()));
			$elementPMapper->setTripod($this->getTripod());
			$dbCS = $this->getDbAdminService()->getDbConnectionSettings($principal);
			$n = $eltQP->getNumberOfQueries();
			$strategy = $eltQP->getQueryStrategy();
			$mysqlF = $this->getMySqlFacade();
			for($i = 0; $i < $n; $i++)
			{
				if($queryPaged)
				{
					$nbRows = $mysqlF->selectPage($principal, $eltQP->getSql($i,
						$this->getSqlBuilderForGetSubElementsForField($principal, $strategy, $masterElementId, $fieldName)),
						$dbCS, $offset, $pageSize,
						$elementPMapper);
					if($i === 0) {
						$listFilter->setTotalNumberOfObjects($nbRows);
						if($nbRows > 0) $elementPMapper->setTotalNumberOfElements($nbRows);
					}
				}
				else
				{
					$nbRows = $mysqlF->selectAll($principal, $eltQP->getSql($i,
						$this->getSqlBuilderForGetSubElementsForField($principal, $strategy, $masterElementId, $fieldName)),
						$dbCS,
						$elementPMapper);
				}
				if(($i === 0) && ($nbRows === 0)) break;
			}
			$eltQP->freeMemory();
			$returnValue = $elementPMapper->count();
			if($returnValue > 0) $elementPMapper->flush($elementPList);
			else $elementPMapper->freeMemory();
		}
		catch(ElementServiceException $ese)
		{
			if(isset($elementPMapper)) $elementPMapper->freeMemory();
			if(isset($eltQP)) $eltQP->freeMemory();
			$this->executionSink()->publishEndOperationOnError("getSubElementsForField", $ese, $principal);
			throw $ese;
		}
		catch (AuthorizationServiceException $asE){
			if(isset($elementPMapper)) $elementPMapper->freeMemory();
			if(isset($eltQP)) $eltQP->freeMemory();
			$this->executionSink()->publishEndOperationOnError("getSubElementsForField", $asE, $principal);
			throw $asE;
		}
		catch(Exception $e)
		{
			if(isset($elementPMapper)) $elementPMapper->freeMemory();
			if(isset($eltQP)) $eltQP->freeMemory();
			$this->executionSink()->publishEndOperationOnError("getSubElementsForField", $e, $principal);
			throw new ElementServiceException('',ElementServiceException::WRAPPING, $e);
		}
		$this->executionSink()->publishEndOperation("getSubElementsForField", $principal);
		return $returnValue;
	}
	protected function assertPrincipalAuthorizedForGetSubElementsForField($principal, $rootElementP)
	{
		// general authorization is given by the right to fetch root element.
		$pRightsOnRoot = $rootElementP->getRights();
		//check read rights on root element
		if($pRightsOnRoot == null){
			$this->getAuthorizationService()->fail($principal, 'has no right to get sub elements of element '.$rootElementP->getId());
		}
		return $pRightsOnRoot;
	}
	public function setSqlBuilderForGetSubElementsForField($queryStrategy, $sqlBuilderForGetSubElementsForField)
	{
		$this->sqlBuilderForGetSubElementsForField[$queryStrategy] = $sqlBuilderForGetSubElementsForField;
	}
	protected function getSqlBuilderForGetSubElementsForField($principal, $queryStrategy, $masterElementId, $fieldName)
	{
		$sqlB = $this->doGetSqlBuilderForGetSubElementsForField($queryStrategy);
		$sqlB->setTripod($this->getTripod());
		$sqlB->reset($principal, $masterElementId, $fieldName);
		return $sqlB;
	}
	protected function getReservedSqlJoinsForGetSubElementsForField()
	{
		return $this->doGetSqlBuilderForGetSubElementsForField(ElementQueryPlanner::QSTRATEGY_JOIN)->getNumberOfStaticJoins();
	}
	/**
	 * Returns a non initialized query builder for GetSubElementsForField according to strategy
	 */
	protected function doGetSqlBuilderForGetSubElementsForField($queryStrategy)
	{
		// autowired
		if(!isset($this->sqlBuilderForGetSubElementsForField)) $this->sqlBuilderForGetSubElementsForField = array();
		$sqlB = $this->sqlBuilderForGetSubElementsForField[$queryStrategy];
		if(!isset($sqlB))
		{
			switch($queryStrategy)
			{
				case ElementQueryPlanner::QSTRATEGY_JOIN:
					$sqlB = ElementSqlBuilderForGetSubElementsForField::createNonInitInstance();
					$this->sqlBuilderForGetSubElementsForField[$queryStrategy] = $sqlB;
					break;
				default: throw new ElementServiceException('unsupported query strategy', ElementServiceException::INVALID_ARGUMENT);
			}
		}
		return $sqlB;
	}


	public function getSelectedSubElements($principal, $inGroupLogExp, $subElementSelector, $elementPList, $listFilter=null) {
		ElementServiceException::throwNotImplemented();
	}

	public function insertSubElement($principal, $masterElementId, $fieldName, $element, $fieldSelectorList=null)
	{
		$this->executionSink()->publishStartOperation("insertSubElement", $principal);
		$eltQP = null;
		try
		{
			if(is_null($element)) return 0;
			if(!$element->isNew()) throw new ElementServiceException("can only insert new elements, use updateElement to update an existing element or setElementSharing to share an element with another group", ElementServiceException::INVALID_ARGUMENT);
			if(is_null($masterElementId) || empty($fieldName)) throw new ElementServiceException("masterElementId or fieldName cannot be null", ElementServiceException::INVALID_ARGUMENT);

			$seCS = $this->getSubElementConfigService();
			// centers the configuration on subelement if not done yet
			$ls = $seCS->getCurrentLinkSelector();
			if(!isset($ls) ||
				$ls->getOwnerElementId() != $masterElementId ||
				$ls->getFieldName() != $fieldName) {
				$seCS->selectSubElementsConfig($principal, $masterElementId, $fieldName);
			}

			// fetches root element with principal rights
			$pathFromRoot = $seCS->getCurrentSubElementPathFromRoot();
			$rootLs = $pathFromRoot->getFirstLinkSelector();
			$rootElementP = $this->getElementPWithoutFields($principal, $rootLs->getOwnerElementId());

			// checks authorization
			$this->assertPrincipalAuthorizedForInsertSubElement($principal, $rootElementP);

			// validates element
			$this->validateSubElementForInsert($principal, $element);

			// computes effective field list
			$eltQP = $this->getElementQueryPlanner(MySqlFacade::Q_INSERTONE, 0, $fieldSelectorList);
			//SysInfo at field level requires to have element and principal
			$eltQP->setElement($element);
			$eltQP->setPrincipal($principal);
			try {
				$seCS->getFields($principal, $seCS->getCurrentModule(), null, $eltQP);
			}
			catch(ServiceException $se) {
				// extracts wrapped exception
				$seRoot = $se->getWigiiRootException();
				// if operation has been canceled, retries once.
				if($seRoot->getCode() == ServiceException::OPERATION_CANCELED) {
					$eltQP->retryAfterCancel(MySqlFacade::Q_INSERTONE, 0, $fieldSelectorList);
					$seCS->getFields($principal, $seCS->getCurrentModule(), null, $eltQP);
				}
				else throw $se;
			}

			// inserts element
			$dbAS = $this->getDbAdminService();
			$dbCS = $dbAS->getDbConnectionSettings($principal);
			$mySqlF = $this->getMySqlFacade();
			$returnValue = 0;
			$beforeInsertId = $element->getId();
			$element->setId(
				$mySqlF->insertOne($principal,
					$this->getSqlForInsertSubElement($principal, $element, $masterElementId, $fieldName, true),
					$dbCS)
			);
			$element->setElementParentId($masterElementId);
			$element->setLinkName($fieldName);

			try
			{
				//inserts element fields values
				$n = $eltQP->getNumberOfQueries();
				$strategy = $eltQP->getQueryStrategy();
				// strategy DATATYPE -> insert multiple
				if($strategy === ElementQueryPlanner::QSTRATEGY_DATATYPE) {
					for($i = 0; $i < $n; $i++)
					{
						$mySqlF->insertMultiple($principal, $eltQP->getSql($i,
							$this->getSqlBuilderForInsertElement($principal, $strategy, $element, $beforeInsertId)),
							$dbCS);
					}
				}
				// strategy FIELD -> insert one
				elseif($strategy === ElementQueryPlanner::QSTRATEGY_FIELD) {
					for($i = 0; $i < $n; $i++)
					{
						$mySqlF->insertOne($principal, $eltQP->getSql($i,
							$this->getSqlBuilderForInsertElement($principal, $strategy, $element, $beforeInsertId)),
							$dbCS);
					}
				}
				else throw new ElementServiceException('unsupported query strategy', ElementServiceException::UNSUPPORTED_OPERATION);
				$eltQP->freeMemory();

				// inserts Elements_Elements relation for subitems
				$mySqlF->insertMultiple($principal,
					$this->getSqlForInsertElementElementAsSubitem($principal, $element->getId(), $pathFromRoot),
					$dbCS);

				// updates Links value
				$mySqlF->insertOne($principal,
					$this->getSqlForUpateSubElementLinksValue($principal, $element->getId()),
					$dbCS);
			}
			// if insertion error, then deletes first created element and throws exception
			catch(Exception $e)
			{
				// unlocks element
				$this->unLock($principal, $element);
				// deletes element
				try
				{
					$dbAS->deleteRows($principal,
						$this->getSqlTableNameForDeleteSubElement(),
						$this->getSqlWhereClauseForDeleteSubElement($principal, $element),
						$dbCS);
				}
				catch(Exception $e1){/* does nothing, it will be automatically deleted on next delete */}
				throw $e;
			}
			// unlocks element
			$this->unLock($principal, $element);
			$returnValue = 1;
		}
		catch (ElementServiceException $ese){
			if(isset($eltQP)) $eltQP->freeMemory();
			$this->executionSink()->publishEndOperationOnError("insertSubElement", $ese, $principal);
			throw $ese;
		}
		catch (AuthorizationServiceException $asE){
			if(isset($eltQP)) $eltQP->freeMemory();
			$this->executionSink()->publishEndOperationOnError("insertSubElement", $asE, $principal);
			throw $asE;
		}
		catch(Exception $e)
		{
			if(isset($eltQP)) $eltQP->freeMemory();
			$this->executionSink()->publishEndOperationOnError("insertSubElement", $e, $principal);
			throw new ElementServiceException('',ElementServiceException::WRAPPING, $e);
		}
		$this->executionSink()->publishEndOperation("insertSubElement", $principal);
		return $returnValue;
	}
	protected function assertPrincipalAuthorizedForInsertSubElement($principal, $rootElementP)
	{
		if(is_null($principal)) throw new ElementServiceException('principal can not be null', ElementServiceException::INVALID_ARGUMENT);

		$autoS = $this->getAuthorizationService();
		// checks general authorization
		$autoS->assertPrincipalAuthorized($principal, "ElementService", "insertElement");
		//check write rights on root element
		if($rootElementP->getRights()==null || !$rootElementP->getRights()->canWriteElement()){
			$autoS->fail($principal, 'has no right to insert a sub element to element '.$rootElementP->getId());
		}
	}
	protected function validateSubElementForInsert($principal, $element)
	{
		$m1 = $this->getSubElementConfigService()->getCurrentModule();
		$m2 = $element->getModule();
		// checks module consistency
		if(!(is_object($m2) && $m1 === $m2) || ($m1->getModuleName() == $m2)) throw new ElementServiceException();
	}
	/**
	 * if lock then locks inserted element
	 */
	protected function getSqlForInsertSubElement($principal, $element, $masterElementId, $linkName, $lock)
	{
		$sqlB = $this->getMySqlFacade()->getSqlBuilder();
		$sqlB->setTableForInsert('Elements');

		if(is_null($element)) throw new ElementServiceException('element can not be null', ElementServiceException::INVALID_ARGUMENT);
		$module = $element->getModule(); if(is_object($module)) $module = $module->getModuleName();
		$sqlB->insertValue('modulename', $module, MySqlQueryBuilder::SQLTYPE_VARCHAR);
		$sqlB->insertValue('id_element_parent', $masterElementId, MySqlQueryBuilder::SQLTYPE_INT);
		$sqlB->insertValue('linkName', $linkName, MySqlQueryBuilder::SQLTYPE_VARCHAR);
		$sqlB->insertValue('version', $element->getVersion(), MySqlQueryBuilder::SQLTYPE_VARCHAR);
		$sqlB->insertValue('state_important1', $element->isState_important1(), MySqlQueryBuilder::SQLTYPE_BOOLEAN);
		$sqlB->insertValue('state_important1Info', $element->getState_important1Info(), MySqlQueryBuilder::SQLTYPE_TEXT);
		$sqlB->insertValue('state_important2', $element->isState_important2(), MySqlQueryBuilder::SQLTYPE_BOOLEAN);
		$sqlB->insertValue('state_important2Info', $element->getState_important2Info(), MySqlQueryBuilder::SQLTYPE_TEXT);
		$sqlB->insertValue('state_hidden', $element->isState_hidden(), MySqlQueryBuilder::SQLTYPE_BOOLEAN);
		$sqlB->insertValue('state_hiddenInfo', $element->getState_hiddenInfo(), MySqlQueryBuilder::SQLTYPE_TEXT);
		$sqlB->insertValue('state_locked', $element->isState_locked(), MySqlQueryBuilder::SQLTYPE_BOOLEAN);
		$sqlB->insertValue('state_lockedInfo', $element->getState_lockedInfo(), MySqlQueryBuilder::SQLTYPE_TEXT);
		$sqlB->insertValue('state_archived', $element->isState_archived(), MySqlQueryBuilder::SQLTYPE_BOOLEAN);
		$sqlB->insertValue('state_archivedInfo', $element->getState_archivedInfo(), MySqlQueryBuilder::SQLTYPE_TEXT);
		$sqlB->insertValue('state_deprecated', $element->isState_deprecated(), MySqlQueryBuilder::SQLTYPE_BOOLEAN);
		$sqlB->insertValue('state_deprecatedInfo', $element->getState_deprecatedInfo(), MySqlQueryBuilder::SQLTYPE_TEXT);
		$sqlB->insertValue('state_finalized', $element->isState_finalized(), MySqlQueryBuilder::SQLTYPE_BOOLEAN);
		$sqlB->insertValue('state_finalizedInfo', $element->getState_finalizedInfo(), MySqlQueryBuilder::SQLTYPE_TEXT);
		$sqlB->insertValue('state_approved', $element->isState_approved(), MySqlQueryBuilder::SQLTYPE_BOOLEAN);
		$sqlB->insertValue('state_approvedInfo', $element->getState_approvedInfo(), MySqlQueryBuilder::SQLTYPE_TEXT);
		$sqlB->insertValue('state_dismissed', $element->isState_dismissed(), MySqlQueryBuilder::SQLTYPE_BOOLEAN);
		$sqlB->insertValue('state_dismissedInfo', $element->getState_dismissedInfo(), MySqlQueryBuilder::SQLTYPE_TEXT);
		$sqlB->insertValue('state_blocked', $element->isState_blocked(), MySqlQueryBuilder::SQLTYPE_BOOLEAN);
		$sqlB->insertValue('state_blockedInfo', $element->getState_blockedInfo(), MySqlQueryBuilder::SQLTYPE_TEXT);
		$sqlB->updateSysUser($principal);
		if($lock) $sqlB->lock($principal);
		return $sqlB->getSql();
	}
	/**
	 * @param LinkSelectorList $pathFromRoot sub element path from root element
	 */
	protected function getSqlForInsertElementElementAsSubitem($principal, $elementId, $pathFromRoot)
	{
		$sqlB = $this->getMySqlFacade()->getSqlBuilder();
		$sqlB->setTableForInsert('Elements_Elements');

		if(is_null($elementId)) throw new ElementServiceException('elementId can not be null', ElementServiceException::INVALID_ARGUMENT);
		if(is_null($pathFromRoot)) throw new ElementServiceException('pathFromRoot can not be null', ElementServiceException::INVALID_ARGUMENT);
		$timestamp = time();
		$userId = $principal->getRealUserId();
		$userName = $principal->getRealUsername();
		$first = true;
		foreach($pathFromRoot->getListIterator() as $ls) {
			if($first) {
				$sqlB->insertValue('id_element_owner', $ls->getOwnerElementId(), MySqlQueryBuilder::SQLTYPE_INT);
				$sqlB->insertValue('id_element', $elementId, MySqlQueryBuilder::SQLTYPE_INT);
				$sqlB->insertValue('linkName', $ls->getFieldName(), MySqlQueryBuilder::SQLTYPE_VARCHAR);
				$sqlB->insertValue('linkType', 'subitem', MySqlQueryBuilder::SQLTYPE_VARCHAR);
				$sqlB->insertValue('sys_creationDate', $timestamp, MySqlQueryBuilder::SQLTYPE_INT);
				$sqlB->insertValue('sys_creationUser', $userId, MySqlQueryBuilder::SQLTYPE_INT);
				$sqlB->insertValue('sys_creationUsername', $userName, MySqlQueryBuilder::SQLTYPE_VARCHAR);
				$first = false;
			}
			else {
				$sqlB->insertMoreRecord();
				$sqlB->insertMoreValue($ls->getOwnerElementId(), MySqlQueryBuilder::SQLTYPE_INT);
				$sqlB->insertMoreValue($elementId, MySqlQueryBuilder::SQLTYPE_INT);
				$sqlB->insertMoreValue($ls->getFieldName(), MySqlQueryBuilder::SQLTYPE_VARCHAR);
				$sqlB->insertMoreValue('subitem', MySqlQueryBuilder::SQLTYPE_VARCHAR);
				$sqlB->insertMoreValue($timestamp, MySqlQueryBuilder::SQLTYPE_INT);
				$sqlB->insertMoreValue($userId, MySqlQueryBuilder::SQLTYPE_INT);
				$sqlB->insertMoreValue($userName, MySqlQueryBuilder::SQLTYPE_VARCHAR);
			}
		}
		return $sqlB->getSql();
	}

	public function renameSubElementsLink($principal, $subElementIds, $newLinkName, $searchToken=null) {
		$this->executionSink()->publishStartOperation("renameSubElementsLink", $principal);
		try
		{
			if(empty($subElementIds)) $returnValue = 0;
			else {
				// checks authorization
				$this->assertPrincipalAuthorizedForRenameSubElementsLink($principal);

				// renames link
				$this->getMySqlFacade()->execQueryList($principal,
						$this->getSqlForRenameSubElementsLink($principal, $subElementIds, $newLinkName, $searchToken),
						$this->getDbAdminService()->getDbConnectionSettings($principal));
				$returnValue = 1;
			}
		}
		catch (ElementServiceException $ese){
			$this->executionSink()->publishEndOperationOnError("renameSubElementsLink", $ese, $principal);
			throw $ese;
		}
		catch (AuthorizationServiceException $asE){
			$this->executionSink()->publishEndOperationOnError("renameSubElementsLink", $asE, $principal);
			throw $asE;
		}
		catch(Exception $e)
		{
			$this->executionSink()->publishEndOperationOnError("renameSubElementsLink", $e, $principal);
			throw new ElementServiceException('',ElementServiceException::WRAPPING, $e);
		}
		$this->executionSink()->publishEndOperation("renameSubElementsLink", $principal);
		return $returnValue;
	}
	protected function assertPrincipalAuthorizedForRenameSubElementsLink($principal)
	{
		if(is_null($principal)) throw new ElementServiceException('principal can not be null', ElementServiceException::INVALID_ARGUMENT);

		$autoS = $this->getAuthorizationService();
		// checks general authorization
		$autoS->assertPrincipalAuthorized($principal, "ElementService", "renameSubElementsLink");
	}
	/**
	 * @return DbQueryList returns a DbQueryList instance
	 */
	protected function getSqlForRenameSubElementsLink($principal, $subElementIds, $newLinkName, $searchToken=null)
	{
		$sqlB = $this->getMySqlFacade()->getSqlBuilder();
		$timestamp = time();
		$userId = $principal->getRealUserId();
		$userName = $principal->getRealUsername();

		// temp table for elements_elements rows
		$tempTableName = 'temp_'.$timestamp;

		// new link name expression
		if(isset($searchToken)) {
			$newLinkName = 'replace(linkName, '
				.$sqlB->formatValue($searchToken, MySqlQueryBuilder::SQLTYPE_VARCHAR).', '
				.($newLinkName == '' ? "''": $sqlB->formatValue($newLinkName, MySqlQueryBuilder::SQLTYPE_VARCHAR)).')';
		}
		else $newLinkName = $sqlB->formatValue($newLinkName, MySqlQueryBuilder::SQLTYPE_VARCHAR);

		// sub elements id selector
		if(is_array($subElementIds)) $id_element = $sqlB->formatBinExp('id_element', 'IN', $subElementIds, MySqlQueryBuilder::SQLTYPE_INT);
		else $id_element = $sqlB->formatBinExp('id_element', '=', $subElementIds, MySqlQueryBuilder::SQLTYPE_INT);

		// update link values
		$updateOriginalLinkValue = $this->getSqlForUpateSubElementLinksValue($principal, $subElementIds, true);
		$updateNewLinkValue = $this->getSqlForUpateSubElementLinksValue($principal, $subElementIds, false);

		// sys user info
		$timestamp = $sqlB->formatBinExp('sys_date', '=', $timestamp, MySqlQueryBuilder::SQLTYPE_INT);
		$userId = $sqlB->formatBinExp('sys_user', '=', $userId, MySqlQueryBuilder::SQLTYPE_INT);
		$userName = $sqlB->formatBinExp('sys_username', '=', $userName, MySqlQueryBuilder::SQLTYPE_VARCHAR);

		// creates query list
		$returnValue = DbQueryListArrayImpl::createInstance();
		// statement 1 : create temp table
		$returnValue->addDbQuery(DbQuery::createInstance("CREATE TEMPORARY TABLE $tempTableName ENGINE=MEMORY
select EE1.id_elements_elements from Elements_Elements as EE1
inner join Elements as E1 on EE1.id_element_owner = E1.id_element_parent and EE1.id_element = E1.id_element
where EE1.linkType = 'subitem' and E1.$id_element
union
select EE2.id_elements_elements from Elements_Elements as EE2
inner join Elements as E2 on EE2.id_element_owner = E2.id_element_parent
inner join Elements_Elements as EE3 on EE3.linkType = 'subitem' and EE3.id_element_owner = E2.id_element
where EE2.linkType = 'subitem' and EE2.id_element = EE3.id_element and E2.$id_element", MySqlFacade::Q_CREATETABLE_FROMSELECT));
		// statement 2 : begin transaction
		$returnValue->addDbQuery(DbQuery::createInstance("START TRANSACTION", MySqlFacade::Q_DDLCMD));
		// statement 3 : update original link value
		$returnValue->addDbQuery(DbQuery::createInstance($updateOriginalLinkValue, MySqlFacade::Q_UPDATE));
		// statement 4 : update Elements link name
		$returnValue->addDbQuery(DbQuery::createInstance("update Elements set linkName = $newLinkName, $timestamp, $userId, $userName where $id_element", MySqlFacade::Q_UPDATE));
		// statement 5 : update Elements_Elements link name
		$returnValue->addDbQuery(DbQuery::createInstance("update Elements_Elements set linkName = $newLinkName where id_elements_elements in (select T.id_elements_elements from $tempTableName as T)", MySqlFacade::Q_UPDATE));
		// statement 6 : update new link value
		$returnValue->addDbQuery(DbQuery::createInstance($updateNewLinkValue, MySqlFacade::Q_UPDATE));
		// statement 7 : commit
		$returnValue->addDbQuery(DbQuery::createInstance("COMMIT", MySqlFacade::Q_DDLCMD));
		// statement 8 : drop temp table
		$returnValue->addDbQuery(DbQuery::createInstance("DROP TABLE $tempTableName", MySqlFacade::Q_DDLCMD));
		return $returnValue;
	}


	// Object builders

	private $fslForElementAttributForImport;
	public function getFieldSelectorListForElementAttributForImport()
	{
		// autowired
		if(!isset($this->fslForElementAttributForImport))
		{
			$this->fslForElementAttributForImport = FieldSelectorListArrayImpl::createInstance();
			$this->fillFieldSelectorListForElementStateAttribute($this->fslForElementAttributForImport);
			$this->fillFieldSelectorListForElementSysAttribute($this->fslForElementAttributForImport);
		}
		return $this->fslForElementAttributForImport;
	}
	private $fslForElementAttributForUpdate;
	public function getFieldSelectorListForElementAttributForUpdate()
	{
		// autowired
		if(!isset($this->fslForElementAttributForUpdate))
		{
			$this->fslForElementAttributForUpdate = FieldSelectorListArrayImpl::createInstance();
			$this->fillFieldSelectorListForElementStateAttribute($this->fslForElementAttributForUpdate);
			$this->fillFieldSelectorListForElementSysAttribute($this->fslForElementAttributForUpdate);
			$this->fslForElementAttributForUpdate->addFieldSelectorInstance(FieldSelector::createElementAttributeSelector('id'));
		}
		return $this->fslForElementAttributForUpdate;
	}
	public function fillFieldSelectorListForElementSignatureAttribute($fieldSelectorList)
	{
		$fieldSelectorList->addFieldSelectorInstance(FieldSelector::createElementAttributeSelector('id'));
		$fieldSelectorList->addFieldSelectorInstance(FieldSelector::createElementAttributeSelector('modulename'));
		return $fieldSelectorList;
	}

	protected function fillFieldSelectorListForElementBaseAttribute($fieldSelectorList)
	{
		$fieldSelectorList->addFieldSelectorInstance(FieldSelector::createElementAttributeSelector('version'));
		$fieldSelectorList->addFieldSelectorInstance(FieldSelector::createElementAttributeSelector('tags'));
		return $fieldSelectorList;
	}
	//without the lockId and lockMicroTime
	protected function fillFieldSelectorListForElementSysAttribute($fieldSelectorList)
	{
		$fieldSelectorList->addFieldSelectorInstance(FieldSelector::createElementAttributeSelector('sys_creationDate'));
		$fieldSelectorList->addFieldSelectorInstance(FieldSelector::createElementAttributeSelector('sys_creationUser'));
		$fieldSelectorList->addFieldSelectorInstance(FieldSelector::createElementAttributeSelector('sys_creationUsername'));
		$fieldSelectorList->addFieldSelectorInstance(FieldSelector::createElementAttributeSelector('sys_date'));
		$fieldSelectorList->addFieldSelectorInstance(FieldSelector::createElementAttributeSelector('sys_user'));
		$fieldSelectorList->addFieldSelectorInstance(FieldSelector::createElementAttributeSelector('sys_username'));
		return $fieldSelectorList;
	}
	protected function fillFieldSelectorListForElementStateAttribute($fieldSelectorList)
	{
		$fieldSelectorList->addFieldSelectorInstance(FieldSelector::createElementAttributeSelector('state_locked'));
		$fieldSelectorList->addFieldSelectorInstance(FieldSelector::createElementAttributeSelector('state_lockedInfo'));
		$fieldSelectorList->addFieldSelectorInstance(FieldSelector::createElementAttributeSelector('state_important2'));
		$fieldSelectorList->addFieldSelectorInstance(FieldSelector::createElementAttributeSelector('state_important2Info'));
		$fieldSelectorList->addFieldSelectorInstance(FieldSelector::createElementAttributeSelector('state_important1'));
		$fieldSelectorList->addFieldSelectorInstance(FieldSelector::createElementAttributeSelector('state_important1Info'));
		$fieldSelectorList->addFieldSelectorInstance(FieldSelector::createElementAttributeSelector('state_hidden'));
		$fieldSelectorList->addFieldSelectorInstance(FieldSelector::createElementAttributeSelector('state_hiddenInfo'));
		$fieldSelectorList->addFieldSelectorInstance(FieldSelector::createElementAttributeSelector('state_archived'));
		$fieldSelectorList->addFieldSelectorInstance(FieldSelector::createElementAttributeSelector('state_archivedInfo'));
		$fieldSelectorList->addFieldSelectorInstance(FieldSelector::createElementAttributeSelector('state_deprecated'));
		$fieldSelectorList->addFieldSelectorInstance(FieldSelector::createElementAttributeSelector('state_deprecatedInfo'));
		$fieldSelectorList->addFieldSelectorInstance(FieldSelector::createElementAttributeSelector('state_finalized'));
		$fieldSelectorList->addFieldSelectorInstance(FieldSelector::createElementAttributeSelector('state_finalizedInfo'));
		$fieldSelectorList->addFieldSelectorInstance(FieldSelector::createElementAttributeSelector('state_approved'));
		$fieldSelectorList->addFieldSelectorInstance(FieldSelector::createElementAttributeSelector('state_approvedInfo'));
		$fieldSelectorList->addFieldSelectorInstance(FieldSelector::createElementAttributeSelector('state_dismissed'));
		$fieldSelectorList->addFieldSelectorInstance(FieldSelector::createElementAttributeSelector('state_dismissedInfo'));
		$fieldSelectorList->addFieldSelectorInstance(FieldSelector::createElementAttributeSelector('state_blocked'));
		$fieldSelectorList->addFieldSelectorInstance(FieldSelector::createElementAttributeSelector('state_blockedInfo'));
		return $fieldSelectorList;
	}

	public function getSqlColumnsForElement($tableAlias, $colPrefix='')
	{
		if($tableAlias == '') throw new ElementServiceException('tableAlias must be filled', ElementServiceException::INVALID_ARGUMENT);
		if($colPrefix!=='')
		{
			return "$tableAlias.id_element as ".$colPrefix."id, ".
"$tableAlias.modulename as ".$colPrefix."module, ".
"$tableAlias.id_element_parent as ".$colPrefix."id_element_parent, ".
"$tableAlias.linkName as ".$colPrefix."linkName, ".
"$tableAlias.peerId as ".$colPrefix."peerId, ".
"$tableAlias.sys_creationDate as ".$colPrefix."sys_creationDate, ".
"$tableAlias.sys_creationUser as ".$colPrefix."sys_creationUser, ".
"$tableAlias.sys_creationUsername as ".$colPrefix."sys_creationUsername, ".
"$tableAlias.sys_date as ".$colPrefix."sys_date, ".
"$tableAlias.sys_user as ".$colPrefix."sys_user, ".
"$tableAlias.sys_username as ".$colPrefix."sys_username, ".
"$tableAlias.version as ".$colPrefix."version, ".
"$tableAlias.sys_lockMicroTime as ".$colPrefix."sys_lockMicroTime, ".
"$tableAlias.sys_lockId as ".$colPrefix."sys_lockId, ".
"$tableAlias.state_locked as ".$colPrefix."state_locked, ".
"$tableAlias.state_lockedInfo as ".$colPrefix."state_lockedInfo, ".
"$tableAlias.state_important1 as ".$colPrefix."state_important1, ".
"$tableAlias.state_important1Info as ".$colPrefix."state_important1Info, ".
"$tableAlias.state_important2 as ".$colPrefix."state_important2, ".
"$tableAlias.state_important2Info as ".$colPrefix."state_important2Info, ".
"$tableAlias.state_hidden as ".$colPrefix."state_hidden, ".
"$tableAlias.state_hiddenInfo as ".$colPrefix."state_hiddenInfo, ".
"$tableAlias.state_archived as ".$colPrefix."state_archived, ".
"$tableAlias.state_archivedInfo as ".$colPrefix."state_archivedInfo, ".
"$tableAlias.state_deprecated as ".$colPrefix."state_deprecated, ".
"$tableAlias.state_deprecatedInfo as ".$colPrefix."state_deprecatedInfo, ".
"$tableAlias.state_finalized as ".$colPrefix."state_finalized, ".
"$tableAlias.state_finalizedInfo as ".$colPrefix."state_finalizedInfo, ".
"$tableAlias.state_approved as ".$colPrefix."state_approved, ".
"$tableAlias.state_approvedInfo as ".$colPrefix."state_approvedInfo, ".
"$tableAlias.state_dismissed as ".$colPrefix."state_dismissed, ".
"$tableAlias.state_dismissedInfo as ".$colPrefix."state_dismissedInfo, ".
"$tableAlias.state_blocked as ".$colPrefix."state_blocked, ".
"$tableAlias.state_blockedInfo as ".$colPrefix."state_blockedInfo";
		}
		else
		{
			return "$tableAlias.id_element as id,
$tableAlias.modulename as module,
$tableAlias.id_element_parent,
$tableAlias.linkName,
$tableAlias.peerId,
$tableAlias.sys_creationDate,
$tableAlias.sys_creationUser,
$tableAlias.sys_creationUsername,
$tableAlias.sys_date,
$tableAlias.sys_user,
$tableAlias.sys_username,
$tableAlias.version,
$tableAlias.sys_lockMicroTime,
$tableAlias.sys_lockId,
$tableAlias.state_locked,
$tableAlias.state_lockedInfo,
$tableAlias.state_important1,
$tableAlias.state_important1Info,
$tableAlias.state_important2,
$tableAlias.state_important2Info,
$tableAlias.state_hidden,
$tableAlias.state_hiddenInfo,
$tableAlias.state_archived,
$tableAlias.state_archivedInfo,
$tableAlias.state_deprecated,
$tableAlias.state_deprecatedInfo,
$tableAlias.state_finalized,
$tableAlias.state_finalizedInfo,
$tableAlias.state_approved,
$tableAlias.state_approvedInfo,
$tableAlias.state_blocked,
$tableAlias.state_blockedInfo,
$tableAlias.state_dismissed,
$tableAlias.state_dismissedInfo
";
		}
	}

	/**
	 * @param dbRow an array straight from the DB
	 * @return instanciated Element object
	 */
	public function createElementInstanceFromRow($principal, $dbRow, $colPrefix='', $fieldList = null, $wigiiBag = null)
	{
		if(is_null($dbRow)) throw new ElementServiceException('dbRow cannot be null', ElementServiceException::INVALID_ARGUMENT);
		return Element::createInstance($this->getModuleAdminService()->getModule($principal, $dbRow[$colPrefix.'module']),$fieldList, $wigiiBag, $dbRow, $colPrefix);
	}

	// Iterates over a Field DataType subfields

	/**
	 * Visits a dataType subfields in the context of a field
	 * elementDataTypeSubfieldsVisitor: the ElementDataTypeSubFieldVisitor to use
	 * fieldSelectors: an array of field selectors, for this field and with subfields defined.
	 * selectedLanguages: an array of selected languages for multi language fields
	 * postcondition: ignores longblob or blobs subfield if not explicitely selected.
	 */
	public function visitDataTypeSubfields($field, $dataType, $elementDataTypeSubfieldVisitor,
										   $fieldSelectors=null, $selectedLanguages=null)
	{
		if(is_null($field)) throw new ElementServiceException('field can not be null', ElementServiceException::INVALID_ARGUMENT);
		if(is_null($dataType)) throw new ElementServiceException('dataType can not be null', ElementServiceException::INVALID_ARGUMENT);
		if(is_null($elementDataTypeSubfieldVisitor)) throw new ElementServiceException('elementDataTypeSubfieldsVisitor can not be null', ElementServiceException::INVALID_ARGUMENT);
		if(isset($fieldSelectors))
		{
			$x = $dataType->getXml();
			$fName = $field->getFieldName();
			foreach($fieldSelectors as $fieldSelector)
			{
				$subField = $fieldSelector->getSubFieldName();
				if(isset($subField) && ($fieldSelector->getFieldName() == $fName))
				{
					$dbFieldParams = $x->xpath($subField);
					if($dbFieldParams)
					{
						$dbFieldParams = $dbFieldParams[0];
						$dbFieldName = $dbFieldParams->getName();
						$this->visitDataTypeSubfield($field, $dataType, $dbFieldName, $dbFieldParams, true, $elementDataTypeSubfieldVisitor, $selectedLanguages);
					}
					else
					{
						throw new ElementServiceException("subField $subField is not defined in config of datatype ".$dataType->getDataTypeName(), ElementServiceException::NO_CORRESPONDANT_FIELD);
					}
				}
			}
		}
		else
		{
			foreach($dataType->getXml() as $dbFieldName => $dbFieldParams)
			{
				$this->visitDataTypeSubfield($field, $dataType, $dbFieldName, $dbFieldParams, false, $elementDataTypeSubfieldVisitor, $selectedLanguages);
			}
		}
	}
	protected function visitDataTypeSubfield($field, $dataType, $dbFieldName, $dbFieldParams, $userSelectedSubField, $elementDataTypeSubfieldsVisitor, $selectedLanguages=null)
	{
		$sqlType = (string)$dbFieldParams['sqlType'];
		$sqlType = $this->dataTypeSqlType2sqlType($sqlType);
		$subFieldType = (string)$dbFieldParams['type'];
		$multilanguage = (((string)$dbFieldParams['multiLanguage']) == '1');
		if($multilanguage)
		{
			if(!isset($selectedLanguages)) $selectedLanguages = $this->getTranslationService()->getVisibleLanguage();
			foreach($selectedLanguages as $lang => $language)
			{
				$elementDataTypeSubfieldsVisitor->actOnSubfield($field, $dataType, $dbFieldName, $subFieldType, $sqlType, $userSelectedSubField, $lang);
			}
		}
		else
		{
			$elementDataTypeSubfieldsVisitor->actOnSubfield($field, $dataType, $dbFieldName, $subFieldType, $sqlType, $userSelectedSubField);
		}
	}
	/**
	 * Maps a DataType sql type to a MySqlQueryBuilder::SQLTYPE
	 */
	public function dataTypeSqlType2sqlType($dataTypeSqlTypeName)
	{
		if(is_null($dataTypeSqlTypeName)) throw new ElementServiceException('datatype sql type name cannot be null', ElementServiceException::INVALID_ARGUMENT);
		$dataTypeSqlTypeName = strtolower($dataTypeSqlTypeName);
		switch($dataTypeSqlTypeName)
		{
			case "varchar": return MySqlQueryBuilder::SQLTYPE_VARCHAR;
			case "text": return MySqlQueryBuilder::SQLTYPE_TEXT;
			case "boolean": return MySqlQueryBuilder::SQLTYPE_BOOLEAN;
			case "date": return MySqlQueryBuilder::SQLTYPE_DATE;
			case "bigint": return MySqlQueryBuilder::SQLTYPE_BIGINT;
			case "datetime": return MySqlQueryBuilder::SQLTYPE_DATETIME;
			case "int": return MySqlQueryBuilder::SQLTYPE_INT;
			case "longblob": return MySqlQueryBuilder::SQLTYPE_LONGBLOB;
			case "blob": return MySqlQueryBuilder::SQLTYPE_BLOB;
			case "double": return MySqlQueryBuilder::SQLTYPE_DOUBLE;
			case "decimal": return MySqlQueryBuilder::SQLTYPE_DECIMAL;
			case "time": return MySqlQueryBuilder::SQLTYPE_TIME;
			default: throw new ElementServiceException("unsupported sql type $dataTypeSqlTypeName", ElementServiceException::INVALID_ARGUMENT);
		}
	}

	// SQL library

	/**
	 * cf. design document "Element Service sql queries", query id: 0909212037
	 */
	public function getSqlForSelectGroups($groupIds, $includeChildrenGroups=true, $Groups_GroupsAlias='GG', $GroupsAlias='G')
	{
		$returnValue = "-- Groups
select $GroupsAlias.id_group from Groups as $GroupsAlias
where $GroupsAlias.id_group in ($groupIds) ";

		if($includeChildrenGroups)
		{
			$returnValue .= "
union distinct
-- all children Groups
select $Groups_GroupsAlias.id_group from Groups_Groups as $Groups_GroupsAlias
where $Groups_GroupsAlias.id_group_owner in ($groupIds) ";
		}
		return $returnValue;
	}

	/**
	 * cf. design document "Element Service sql queries", query id: 0909212038
	 */
	public function getSqlForSelectGroupsP($principal, $module, $principalId, $select_Groups, $propagation,
						$User_Group_RightsAlias='UGR', $Groups_GroupsAlias='GG', $resultAlias='GP', $GroupsAlias='G')
	{
		$isInPrincipalWigiiNamespace = $this->getMySqlFacade()->getSqlBuilder()->formatBinExp($GroupsAlias.'2.wigiiNamespace', '=', $principal->getWigiiNamespace()->getWigiiNamespaceName(), MySqlQueryBuilder::SQLTYPE_VARCHAR); //add by LWR
		$returnValue = "select
$resultAlias.id_group,
max($resultAlias.canModify) as canModify,
max($resultAlias.canWriteElement) as canWriteElement,
max($resultAlias.canShareElement) as canShareElement
from (
-- direct right with principal
select
$GroupsAlias.id_group,
$User_Group_RightsAlias.canModify,
$User_Group_RightsAlias.canWriteElement,
$User_Group_RightsAlias.canShareElement
from ($select_Groups) as $GroupsAlias
inner join Users_Groups_Rights as $User_Group_RightsAlias on $GroupsAlias.id_group = $User_Group_RightsAlias.id_group
where $User_Group_RightsAlias.id_user = $principalId ";

		if($propagation == 1 or $propagation == 2)
		{
			$returnValue .= "
union distinct
-- inherited rights from parents if propagation in (1,2)
select
$GroupsAlias.id_group,
$User_Group_RightsAlias.canModify,
$User_Group_RightsAlias.canWriteElement,
$User_Group_RightsAlias.canShareElement
from
($select_Groups) as $GroupsAlias
inner join Groups_Groups as $Groups_GroupsAlias on $GroupsAlias.id_group = $Groups_GroupsAlias.id_group
inner join Users_Groups_Rights as $User_Group_RightsAlias on $Groups_GroupsAlias.id_group_owner = $User_Group_RightsAlias.id_group
where $User_Group_RightsAlias.id_user = $principalId ";
		}

		if($propagation == 2)
		{
			$returnValue .= "
union distinct
-- inherited read right from children if propagation 2
select
$GroupsAlias.id_group,
0 as canModify,
0 as canWriteElement,
0 as canShareElement
from
($select_Groups) as $GroupsAlias
inner join Groups_Groups as $Groups_GroupsAlias on $GroupsAlias.id_group = $Groups_GroupsAlias.id_group_owner
inner join Users_Groups_Rights as $User_Group_RightsAlias on $Groups_GroupsAlias.id_group = $User_Group_RightsAlias.id_group
where $User_Group_RightsAlias.id_user = $principalId ";
		}
		if($principal->isWigiiNamespaceCreator($module)){
			$returnValue .= "
union distinct
-- access with X right to all groups in principal wigiiNamespace
select
$GroupsAlias.id_group,
1 as canModify,
1 as canWriteElement,
1 as canShareElement
from ($select_Groups) as $GroupsAlias
where 1 ";

		} else if($principal->isReadAllGroupsInWigiiNamespace($module)){
			$returnValue .= "
union distinct
-- access with X right to all groups in principal wigiiNamespace
select
$GroupsAlias.id_group,
1 as canModify,
1 as canWriteElement,
1 as canShareElement
from ($select_Groups) as $GroupsAlias
inner join Groups as {$GroupsAlias}2 on $GroupsAlias.id_group = {$GroupsAlias}2.id_group
where $isInPrincipalWigiiNamespace ";

		}

		$returnValue .= "
) as $resultAlias
group by $resultAlias.id_group ";

		return $returnValue;
	}

	/**
	 * cf. design document "Element Service sql queries", query id: 0909282129
	 */
	public function getSqlForSelectElements($select_Groups, $GroupsAlias='G', $Elements_GroupsAlias='EG')
	{
		$returnValue = "select
$Elements_GroupsAlias.id_element
from
($select_Groups) as $GroupsAlias
inner join Elements_Groups $Elements_GroupsAlias on $Elements_GroupsAlias.id_group = $GroupsAlias.id_group
group by $Elements_GroupsAlias.id_element";
		return $returnValue;
	}

	/**
	 * cf. design document "Element Service sql queries", query id: 0909212039
	 */
	public function getSqlForSelectElementsP($select_GroupsP, $GroupsPAlias='GP', $Elements_GroupsAlias='EG')
	{
		$returnValue = "select
$Elements_GroupsAlias.id_element,
max($GroupsPAlias.canModify) as canModify,
max($GroupsPAlias.canWriteElement) as canWriteElement,
max($GroupsPAlias.canShareElement) as canShareElement
from
($select_GroupsP) as $GroupsPAlias
inner join Elements_Groups $Elements_GroupsAlias on $Elements_GroupsAlias.id_group = $GroupsPAlias.id_group
group by $Elements_GroupsAlias.id_element";
		return $returnValue;
	}

	/**
	 * @param int|array $elementId an element id or an array of element ids
	 */
	public function getSqlForSelectElementP($elementId, $select_GroupsP, $GroupsPAlias='GP', $Elements_GroupsAlias='EG')
	{
		$sqlB = $this->getMySqlFacade()->getSqlBuilder();
		if(!empty($elementId) && is_array($elementId)) {
			$elementId = $sqlB->formatBinExp($Elements_GroupsAlias.".id_element", 'IN', $elementId, MySqlQueryBuilder::SQLTYPE_INT);
		}
		else $elementId = $sqlB->formatBinExp($Elements_GroupsAlias.".id_element", '=', $elementId, MySqlQueryBuilder::SQLTYPE_INT);

		$returnValue = "select
$Elements_GroupsAlias.id_element,
max($GroupsPAlias.canModify) as canModify,
max($GroupsPAlias.canWriteElement) as canWriteElement,
max($GroupsPAlias.canShareElement) as canShareElement
from
($select_GroupsP) as $GroupsPAlias
inner join Elements_Groups as $Elements_GroupsAlias on $Elements_GroupsAlias.id_group = $GroupsPAlias.id_group
where $elementId
group by $Elements_GroupsAlias.id_element";
		return $returnValue;
	}

	/**
	 * @param int|array $elementId an element id or an array of element ids
	 */
	public function getSqlForSelectElementGroups($elementId, $Elements_GroupsAlias='EG')
	{
		$sqlB = $this->getMySqlFacade()->getSqlBuilder();
		if(!empty($elementId) && is_array($elementId)) {
			$elementId = $sqlB->formatBinExp($Elements_GroupsAlias.".id_element", 'IN', $elementId, MySqlQueryBuilder::SQLTYPE_INT);
		}
		else $elementId = $sqlB->formatBinExp($Elements_GroupsAlias.".id_element", '=', $elementId, MySqlQueryBuilder::SQLTYPE_INT);
		$returnValue = "select distinct $Elements_GroupsAlias.id_group from Elements_Groups as $Elements_GroupsAlias where $elementId";
		return $returnValue;
	}

	/**
	 * Selects some subelements given a selection of root elements, a sub element configuration path and/or a module.
	 * @param String $select_Elements an sql query which selects element ids in a column id_element
	 * @param LinkSelectorList $configPath sub element configuration path from root element. If null or empty, then selects first level of subelements.
	 * @param Module|String $module if set, then filters the subelements according to given module.
	 * @param boolean $includeChildren if true and module is set, then selects all subelements children, else only returns the selected level of subelements according to the config path.
	 * @return String sql select with the following columns :
	 * id_element: sub element id
	 * modulename: sub element module name
	 * id_element_root: root element id
	 * linkName_root: root element link name from which sub element is linked in the tree
	 * modulename_root: root element module name
	 * id_element_parent: parent element id
	 * linkName_parent: parent element link name from which sub element is linked
	 * modulename_parent: parent element module name
	 */
	protected function getSqlForSelectSubElements($select_Elements, $configPath, $module, $includeChildren, $ElementAlias='E') {
		$noConfigPath = !isset($configPath)||$configPath->isEmpty();
		if(empty($module) && $noConfigPath) throw new ElementServiceException('Subelement configuration path and module cannot be both empty. At least one should be filled.', ElementServiceException::INVALID_ARGUMENT);
		if(empty($select_Elements)) throw new ElementServiceException('select_Elements sql clause cannot be empty', ElementServiceException::INVALID_ARGUMENT);

		// 1. Builds from clause

		$fromClause = "($select_Elements) as $ElementAlias";

		// root elements
		$rootElementAlias = $ElementAlias.'Root';
		$fromClause .= " inner join Elements as $rootElementAlias on $rootElementAlias.id_element = $ElementAlias.id_element";
		$parentElementAlias = $rootElementAlias;
		$i=1; $aliasForRootLinkName = $ElementAlias.$i;
		$sqlB = $this->getMySqlFacade()->getSqlBuilder();

		// sub element configuration path
		if(!$noConfigPath) {
			foreach($configPath->getListIterator() as $ls) {
				$currentElementAlias = $ElementAlias.$i;
				$fromClause .= " inner join Elements as $currentElementAlias on $currentElementAlias.id_element_parent = $parentElementAlias.id_element and ".$sqlB->formatBinExp($currentElementAlias.'.linkName', '=', $ls->getFieldName(), MySqlQueryBuilder::SQLTYPE_VARCHAR);
				$parentElementAlias2 = $parentElementAlias;
				$i++; $parentElementAlias = $currentElementAlias;
			}
		}

		// sub element module
		if($module instanceof Module) $modulename = $module->getModuleName();
		else $modulename = $module;
		if(isset($modulename)) {
			$currentElementAlias = $ElementAlias.$i;
			// if include children
			if($includeChildren) {
				$ElementsElementsAlias = $currentElementAlias.'EE';
				$parentElementAlias2 = $ElementAlias.($i+1);
				$fromClause .= " inner join Elements_Elements as $ElementsElementsAlias on $ElementsElementsAlias.id_element_owner = $parentElementAlias.id_element and $ElementsElementsAlias.linkType = 'subitem'";
				$fromClause .= " inner join Elements as $currentElementAlias on $currentElementAlias.id_element = $ElementsElementsAlias.id_element and ".$sqlB->formatBinExp($currentElementAlias.'.modulename', '=', $modulename, MySqlQueryBuilder::SQLTYPE_VARCHAR);
				$fromClause .= " inner join Elements as $parentElementAlias2 on $parentElementAlias2.id_element = $currentElementAlias.id_element_parent";
				// if no sub element config path, then alias for root linkName is Elements_Elements
				if($noConfigPath) $aliasForRootLinkName = $ElementsElementsAlias;
			}
			// else
			else {
				$fromClause .= " inner join Elements as $currentElementAlias on $currentElementAlias.id_element_parent = $parentElementAlias.id_element and ".$sqlB->formatBinExp($currentElementAlias.'.modulename', '=', $modulename, MySqlQueryBuilder::SQLTYPE_VARCHAR);
				$parentElementAlias2 = $parentElementAlias;
			}
		}

		// 2. Defines result set columns

		$selectCols = "$currentElementAlias.id_element, $currentElementAlias.modulename";
		$selectCols .= ", $rootElementAlias.id_element as id_element_root, $aliasForRootLinkName.linkName as linkName_root, $rootElementAlias.modulename as modulename_root";
		$selectCols .= ", $currentElementAlias.id_element_parent, $currentElementAlias.linkName as linkName_parent, $parentElementAlias2.modulename as modulename_parent";

		return "select $selectCols from $fromClause";
	}

	/**
	 * Returns the sql that can be used to insert/update the number of subitem links knowing one or several sub element ids
	 * @param Principal $principal the principal executing the request
	 * @param int|Array $subElementId the id of one sub element belonging to the link for which we want to update the value or an array of subelement ids
	 * @param boolean $ifDeleted if true, then updates the links value as if the subitem was deleted, else updates the links value counting this link.
	 */
	public function getSqlForUpateSubElementLinksValue($principal, $subElementId, $ifDeleted=false) {
		if(empty($subElementId)) throw new ElementServiceException('subElementId cannot be null', ElementServiceException::INVALID_ARGUMENT);
		$sqlB = $this->getMySqlFacade()->getSqlBuilder();

		$timestamp = $sqlB->formatValue(time(), MySqlQueryBuilder::SQLTYPE_INT);
		$userId =  $sqlB->formatValue($principal->getRealUserId(), MySqlQueryBuilder::SQLTYPE_INT);
		$userName = $sqlB->formatValue($principal->getRealUsername(), MySqlQueryBuilder::SQLTYPE_VARCHAR);

		if(is_array($subElementId)) {
			$subElementId = $sqlB->formatBinExp('E1.id_element', 'IN', $subElementId, MySqlQueryBuilder::SQLTYPE_INT);
			$countSubElements = ($ifDeleted ? 'count(E3.id_element)-E2.nb_sel_sub_elts': 'count(E3.id_element)');

			$selectMasterElts = "select E2.id_element, E2.linkName, $countSubElements, $userId, $userName, $timestamp, $userId, $userName, $timestamp from
(select E.id_element, E1.linkName, count(E.id_element) as nb_sel_sub_elts from Elements E
inner join Elements E1 on E.id_element = E1.id_element_parent and $subElementId
group by E.id_element, E1.linkName) as E2
inner join Elements E3 on E3.id_element_parent = E2.id_element and E3.linkName = E2.linkName
group by E2.id_element, E2.linkName";
		}
		else {
			$subElementId = $sqlB->formatBinExp('E1.id_element', '=', $subElementId, MySqlQueryBuilder::SQLTYPE_INT);
			$countSubElements = ($ifDeleted ? 'count(E2.id_element)-1': 'count(E2.id_element)');

			$selectMasterElts = "SELECT E2.id_element_parent, E2.linkName, $countSubElements, $userId, $userName, $timestamp, $userId, $userName, $timestamp from Elements E1
INNER JOIN Elements E2 ON E2.id_element_parent = E1.id_element_parent and E2.linkName = E1.linkName
WHERE $subElementId";
		}

		return "INSERT INTO Links (`id_element`, `field`, `value`, `sys_user`, `sys_username`, `sys_date`, `sys_creationUser`, `sys_creationUsername`, `sys_creationDate`)
$selectMasterElts
ON DUPLICATE KEY UPDATE `id_element` = VALUES(`id_element`), `field` = VALUES(`field`), `value` = VALUES(`value`), `sys_user` = VALUES(`sys_user`), `sys_username` = VALUES(`sys_username`), `sys_date` = VALUES(`sys_date`)";
	}

	/**
	 * Lock element or list of element
	 * @param object: Element or ElementPList or ElementList
	 */
	public function lock($principal, $object){
		return $this->getDbAdminService()->lock($principal, "Elements", $object);
	}

	/**
	 * UnLock element or list of element
	 * @param object: Element or ElementPList or ElementList
	 */
	public function unLock($principal, $object){
		$this->getDbAdminService()->unLock($principal, "Elements", $object);
	}

	/**
	 * returns an array with key as element id and value basic element field if element exists in DB
	 */
	public function doesElementExists($principal, $elementId){
		return $this->doDoElementsExist($principal, array($elementId));
	}
	/**
	 * returns an array with key as element id and value basic element field if element exists in DB
	 */
	public function doElementsExist($principal, $elementPAdvancedList){
		return $this->doDoElementsExist($principal, $elementPAdvancedList->getElementIds());
	}
	protected function doDoElementsExist($principal, $elementIds){
		$this->executionSink()->publishStartOperation("doElementsExist", $principal);
		try
		{
			$this->assertPrincipalAuthorizedForDoElementsExist($principal, $elementIds);
			$rowList = RowListArrayImpl::createInstance("id_element");
			$nbRecords = $this->getMySqlFacade()->selectAll($principal,
					$this->getSqlForDoElementsExist($elementIds),
					$this->getDbAdminService()->getDbConnectionSettings($principal),
					$rowList);
			$returnValue = $rowList->getListIterator();
		}
		catch(ElementServiceException $ese)
		{
			$this->executionSink()->publishEndOperationOnError("doElementsExist", $ese, $principal);
			throw $ese;
		}
		catch (AuthorizationServiceException $asE){
			$this->executionSink()->publishEndOperationOnError("doElementsExist", $asE, $principal);
			throw $asE;
		}
		catch(Exception $e)
		{
			$this->executionSink()->publishEndOperationOnError("doElementsExist", $e, $principal);
			throw new ElementServiceException('',ElementServiceException::WRAPPING, $e);
		}
		$this->executionSink()->publishEndOperation("doElementsExist", $principal);
		return $returnValue;
	}
	protected function assertPrincipalAuthorizedForDoElementsExist($principal, $elementIds)
	{
		if(is_null($elementIds)) throw new ElementServiceException('elementPAdvancedList can not be null', ElementServiceException::INVALID_ARGUMENT);

		$autoS = $this->getAuthorizationService();
		// checks general authorization
		$autoS->assertPrincipalAuthorized($principal, "ElementService", "doElementsExist");
	}
	protected function getSqlForDoElementsExist($elementIds){
		$sqlB = $this->getMySqlFacade()->getSqlBuilder();
		$elementIds = $sqlB->formatBinExp("E.id_element", "IN", $elementIds, MySqlQueryBuilder::SQLTYPE_INT);
		return "select E.id_element, E.modulename, E.version, E.tags, E.sys_creationDate, E.sys_creationUser, E.sys_creationUsername, E.sys_date, E.sys_user, E.sys_username, E.sys_lockId, E.sys_lockMicroTime from Elements as E where $elementIds order by E.id_element";
	}
}

// ElementService tripod
class ElementServiceTripod
{
	public $elementServiceImpl;
	public $elementSqlBuilder;
	public $elementPMapper;
}

// ElementService GroupPList
class ElementServiceGroupPList extends GroupListArrayImpl implements GroupPList
{
	private $principalRights;

	// Object lifecycle

	public static function createInstance()
	{
		$returnValue = new ElementServiceGroupPList();
		$returnValue->reset();
		return $returnValue;
	}
	public function freeMemory()
	{
		parent::freeMemory();
		unset($this->principalRights);
	}

	// Principal rights

	/**
	 * Returns resulting principal rights by merging all GroupP
	 */
	public function getPrincipalRights()
	{
		return $this->principalRights;
	}

	// GroupPList implementation

	public function addGroupP($groupP)
	{
		if(isset($groupP))
		{
			$this->addGroup($groupP->getGroup());
			$pRights = $groupP->getRights();
			if(isset($pRights))
			{
				if(isset($this->principalRights))
				{
					$this->principalRights->addPrincipalRights($pRights);
				}
				else $this->principalRights = $pRights;
			}
		}
	}
}

// SQL builders

/**
 * JOIN strategy for SELECTALL
 */
class ElementSqlBuilderForGetAllElementsInGroups extends ElementSqlBuilder
{
	private $moduleAS;
	protected $principal;
	protected $groupList;
	protected $singleGroup;
	protected $pRightsFromDb;
	protected $hideDbRights = false;
	protected $includeChildrenGroups;

	// object lifecycle

	public static function createNonInitInstance()
	{
		$returnValue = new ElementSqlBuilderForGetAllElementsInGroups();
		return $returnValue;
	}

	public static function createInstance($principal, $groupList, $pRightsFromDb, $includeChildrenGroups)
	{
		$returnValue = ElementSqlBuilderForGetAllElementsInGroups::createNonInitInstance();
		$returnValue->reset($principal, $groupList, $pRightsFromDb, $includeChildrenGroups);
		return $returnValue;
	}
	public function reset($principal, $groupList, $pRightsFromDb, $includeChildrenGroups)
	{
		parent::reset();
		if(is_null($principal)) throw new ElementServiceException('principal can not be null', ElementServiceException::INVALID_ARGUMENT);
		if(is_null($groupList)) throw new ElementServiceException('groupList can not be null', ElementServiceException::INVALID_ARGUMENT);
		if($groupList instanceof Group)
		{
			$this->singleGroup = true;
			$this->groupList = $groupList;
		}
		elseif(is_array($groupList))
		{
			$this->singleGroup = false;
			if(count($groupList) == 0) throw new ElementServiceException('groupList can not be empty', ElementServiceException::INVALID_ARGUMENT);
			$this->groupList = $groupList;
		}
		elseif($groupList instanceof GroupList)
		{
			$this->singleGroup = false;
			if($groupList->isEmpty()) throw new ElementServiceException('groupList can not be empty', ElementServiceException::INVALID_ARGUMENT);
			$this->groupList = $groupList->getListIterator();
		}
		else
		{
			throw new ElementServiceException('groupList can only be a non empty instance of GroupList or an array of Group', ElementServiceException::INVALID_ARGUMENT);
		}
		$this->principal = $principal;

		$this->pRightsFromDb = $pRightsFromDb;
		$this->includeChildrenGroups = $includeChildrenGroups;
		// initialises ElementSqlBuilder
		$this->setElementJoinColumn('E1.id_element');
		$this->setFieldPrefix('F');
	}
	public function freeMemory()
	{
		parent::freeMemory();
		unset($this->groupList);
		unset($this->principal);
	}

	// dependency injection

	public function setModuleAdminService($moduleAdminService)
	{
		$this->moduleAS = $moduleAdminService;
	}
	protected function getModuleAdminService()
	{
		// autowired
		if(!isset($this->moduleAS))
		{
			$this->moduleAS = ServiceProvider::getModuleAdminService();
		}
		return $this->moduleAS;
	}

	// configuration

	/**
	 * Calling this method, will hide the DB rights column in the resulting select
	 * The columns EcanWriteElement and EcanShareElement will be calculated, but not displayed.
	 * To prevent calculating DB rights, use a pRights instead.
	 */
	public function hideDbRightsInSelectedCols() {
		$this->hideDbRights = true;
	}

	// query builder implementation

	/**
	 * Returns number of static joins in query
	 */
	public function getNumberOfStaticJoins()
	{
		return 3;
	}

	public function getSql()
	{
		$tripod = $this->getTripod();
		$eltS = $tripod->elementServiceImpl;
		if($this->pRightsFromDb){
			$principalId = $this->principal->getUserId();
			if(is_null($principalId)) throw new ElementServiceException('principal attached user can not be null', ElementServiceException::INVALID_ARGUMENT);
			$principalId = $this->formatValue($principalId, MySqlQueryBuilder::SQLTYPE_INT);
		}

		// builds groupId sql
		if($this->singleGroup)
		{
			$groupId = $this->formatValue($this->groupList->getId(), MySqlQueryBuilder::SQLTYPE_INT);
			$module = $this->groupList->getModule();
		}
		else
		{
			$groupId = ''; $n = 0;
			foreach($this->groupList as $group)
			{
				if($n == 0)
				{
					$module = $group->getModule();
				}
				elseif($n > 0)
				{
					$groupId .= ', ';
				}
				$groupId .= $this->formatValue($group->getId(), MySqlQueryBuilder::SQLTYPE_INT);
				$n++;
			}
		}

		// select groups
		$select_Groups = $eltS->getSqlForSelectGroups($groupId, $this->includeChildrenGroups, 'GG', 'G');

		// if rights are given by the database
		if($this->pRightsFromDb)
		{
			$select_Elements = $eltS->getSqlForSelectElementsP($eltS->getSqlForSelectGroupsP($this->principal, $module, $principalId,
				$select_Groups,
				$this->getModuleAdminService()->getDefaultPropagation($module), 'UGR1', 'GG1', 'GP1', 'G1'),
				'GP2','EG2');
		}
		// else ignore rights computation
		else
		{
			$select_Elements = $eltS->getSqlForSelectElements($select_Groups, 'G1', 'EG1');
		}

		// Select cols
		$includeEltDetail = $this->includeElementDetail();
		if(isset($tripod->elementPMapper)) $tripod->elementPMapper->setElementPrefix('E');
		// includes element detail
		if($includeEltDetail)
		{
			$selectCols = $this->getSqlColumnsForElement('E2', 'E');
		}
		else
		{
			$selectCols = 'E1.id_element as Eid';
		}
		// includes db rights
		if($this->pRightsFromDb && !$this->hideDbRights)
		{
			$selectCols .= ', E1.canModify as EcanModify, E1.canWriteElement as EcanWriteElement, E1.canShareElement as EcanShareElement';
		}
		// includes fields
		$s = $this->getSqlColumnsForFieldList();
		if($s != null) $selectCols .= ', '.$s; //LWR changed !== '' in != null

		// From clause
		$fromClause = "($select_Elements) as E1 ";
		if($includeEltDetail) $fromClause .= " inner join Elements as E2 on E2.id_element = E1.id_element ";
		$fromClause .= $this->getFromClauseForFieldList();

		// Where clause
		$whereClause = $this->getWhereClauseForFieldList('E2');

		// OrderBy clause
		$this->convertFieldSortingKeyListToOrderByClause('E2');
		$orderByClause = $this->getOrderByClause();

		// Build final sql
		if(isset($whereClause) && $whereClause !== '')
		{
			return "select $selectCols from $fromClause where $whereClause $orderByClause";
		}
		else
		{
			return "select $selectCols from $fromClause $orderByClause";
		}
	}
}

/**
 * JOIN strategy for SELECTALL with PAGING ACTIVE
 * Created by Medair (CWE,LMA) to optimize fetch by moving LIMIT inside the query instead of at the end.
 */
class ElementSqlBuilderForGetPagedElementsInGroups extends ElementSqlBuilder
{
	private $_debugLogger;
	private $moduleAS;
	protected $principal;
	protected $groupList;
	protected $singleGroup;
	protected $pRightsFromDb;
	protected $hideDbRights = false;
	protected $includeChildrenGroups;
	private $sqlFromStructuralFields;
	private $sqlForCountElements;
	private $elementJoinColumnForStructuralFields;
	private $offset;
	private $pageSize;

	// object lifecycle

	public static function createNonInitInstance()
	{
		$returnValue = new ElementSqlBuilderForGetPagedElementsInGroups();
		$returnValue->debugLogger()->write("Instanciates ElementSqlBuilderForGetPagedElementsInGroups");
		return $returnValue;
	}

	public static function createInstance($principal, $groupList, $pRightsFromDb, $includeChildrenGroups)
	{
		$returnValue = ElementSqlBuilderForGetAllElementsInGroupsV2::createNonInitInstance();
		$returnValue->reset($principal, $groupList, $pRightsFromDb, $includeChildrenGroups);
		return $returnValue;
	}
	public function reset($principal, $groupList, $pRightsFromDb, $includeChildrenGroups)
	{
		parent::reset();
		unset($this->sqlForCountElements); // cannot be put into freeMemory because should be kept  to be read into a second query.
		$this->offset = 0;
		$this->pageSize = 0;
		
		if(is_null($principal)) throw new ElementServiceException('principal can not be null', ElementServiceException::INVALID_ARGUMENT);
		if(is_null($groupList)) throw new ElementServiceException('groupList can not be null', ElementServiceException::INVALID_ARGUMENT);
		if($groupList instanceof Group)
		{
			$this->singleGroup = true;
			$this->groupList = $groupList;
		}
		elseif(is_array($groupList))
		{
			$this->singleGroup = false;
			if(count($groupList) == 0) throw new ElementServiceException('groupList can not be empty', ElementServiceException::INVALID_ARGUMENT);
			$this->groupList = $groupList;
		}
		elseif($groupList instanceof GroupList)
		{
			$this->singleGroup = false;
			if($groupList->isEmpty()) throw new ElementServiceException('groupList can not be empty', ElementServiceException::INVALID_ARGUMENT);
			$this->groupList = $groupList->getListIterator();
		}
		else
		{
			throw new ElementServiceException('groupList can only be a non empty instance of GroupList or an array of Group', ElementServiceException::INVALID_ARGUMENT);
		}
		$this->principal = $principal;

		$this->pRightsFromDb = $pRightsFromDb;
		$this->includeChildrenGroups = $includeChildrenGroups;
		// initialises ElementSqlBuilder
		$this->setElementJoinColumnForStructuralFields('E1.id_element');
		$this->setElementJoinColumn('E3.id_element');
		$this->setFieldPrefix('F');
	}
	public function freeMemory()
	{
		parent::freeMemory();
		unset($this->groupList);
		unset($this->principal);
		unset($this->sqlFromStructuralFields);
	}

	// dependency injection

	private function debugLogger()
	{
		if(!isset($this->_debugLogger))
		{
			$this->_debugLogger = DebugLogger::getInstance("ElementSqlBuilderForGetPagedElementsInGroups");
		}
		return $this->_debugLogger;
	}
	
	public function setModuleAdminService($moduleAdminService)
	{
		$this->moduleAS = $moduleAdminService;
	}
	protected function getModuleAdminService()
	{
		// autowired
		if(!isset($this->moduleAS))
		{
			$this->moduleAS = ServiceProvider::getModuleAdminService();
		}
		return $this->moduleAS;
	}	
	
	// configuration

	/**
	 * Calling this method, will hide the DB rights column in the resulting select
	 * The columns EcanWriteElement and EcanShareElement will be calculated, but not displayed.
	 * To prevent calculating DB rights, use a pRights instead.
	 */
	public function hideDbRightsInSelectedCols() {
		$this->hideDbRights = true;
	}

	public function setPaging($offset, $pageSize) {
		$this->offset = $offset;
		$this->pageSize = $pageSize;
	}
	protected function setElementJoinColumnForStructuralFields($elementJoinColumn)
	{
		$this->elementJoinColumnForStructuralFields = $elementJoinColumn;
	}
	protected function getElementJoinColumnForStructuralFields()
	{
		if($this->getQueryStrategy() !== ElementQueryPlanner::QSTRATEGY_JOIN)
		{
			throw new ElementServiceException('operation only supported in join strategy', ElementServiceException::UNSUPPORTED_OPERATION);
		}
		return $this->elementJoinColumnForStructuralFields;
	}
	protected function getFromClauseForStructuralFields()
	{
		$sqlT = $this->getSqlQueryType();
		if(($sqlT !==  MySqlFacade::Q_SELECTALL) &&
				($sqlT !==  MySqlFacade::Q_SELECTONE))
		{
			throw new ElementServiceException('operation only for select queries', ElementServiceException::UNSUPPORTED_OPERATION);
		}
		return $this->sqlFromStructuralFields;
	}
	
	// query builder implementation

	/**
	 * Returns number of static joins in query
	 */
	public function getNumberOfStaticJoins()
	{
		return 3;
	}

	public function getSql()
	{
		$tripod = $this->getTripod();
		$eltS = $tripod->elementServiceImpl;
		if($this->pRightsFromDb){
			$principalId = $this->principal->getUserId();
			if(is_null($principalId)) throw new ElementServiceException('principal attached user can not be null', ElementServiceException::INVALID_ARGUMENT);
			$principalId = $this->formatValue($principalId, MySqlQueryBuilder::SQLTYPE_INT);
		}

		// Creates two builders with same initial state to be able to generate SQL query into two separate parts
		$builder1 = $this; // used to select elements using paging
		$builder2 = clone $this; // used to bring data fields
		
		// ELEMENTS SELECTION
		
		// builds groupId sql
		if($builder1->singleGroup)
		{
			$groupId = $builder1->formatValue($builder1->groupList->getId(), MySqlQueryBuilder::SQLTYPE_INT);
			$module = $builder1->groupList->getModule();
		}
		else
		{
			$groupId = ''; $n = 0;
			foreach($builder1->groupList as $group)
			{
				if($n == 0)
				{
					$module = $group->getModule();
				}
				elseif($n > 0)
				{
					$groupId .= ', ';
				}
				$groupId .= $builder1->formatValue($group->getId(), MySqlQueryBuilder::SQLTYPE_INT);
				$n++;
			}
		}

		// select groups
		$select_Groups = $eltS->getSqlForSelectGroups($groupId, $builder1->includeChildrenGroups, 'GG', 'G');

		// if rights are given by the database
		if($builder1->pRightsFromDb)
		{
			$select_Elements = $eltS->getSqlForSelectElementsP($eltS->getSqlForSelectGroupsP($builder1->principal, $module, $principalId,
					$select_Groups,
					$builder1->getModuleAdminService()->getDefaultPropagation($module), 'UGR1', 'GG1', 'GP1', 'G1'),
					'GP2','EG2');
		}
		// else ignore rights computation
		else
		{
			$select_Elements = $eltS->getSqlForSelectElements($select_Groups, 'G1', 'EG1');
		}
		
		$selectCols = 'E1.id_element';
		// includes db rights
		if($builder1->pRightsFromDb && !$builder1->hideDbRights)
		{
			$selectCols .= ', E1.canModify, E1.canWriteElement, E1.canShareElement';
		}			
		
		// From clause
		$includeEltDetail = $builder1->includeElementDetail();
		$fromClause = "($select_Elements) as E1 ";
		if($includeEltDetail) $fromClause .= " inner join Elements as E2 on E2.id_element = E1.id_element ";
		$fromClause .= $builder1->getFromClauseForStructuralFields();
		
		// Where clause
		$whereClause = $builder1->getWhereClauseForFieldList('E2');		
		
		// OrderBy clause
		$builder1->convertFieldSortingKeyListToOrderByClause('E2');
		$orderByClause = $builder1->getOrderByClause();

		// Paging
		if($this->pageSize > 0) {
			$limit = 'limit '.$this->offset.','.$this->pageSize;
		}
		else $limit = '';
		
		// Build sql for page selection
		if(isset($whereClause) && $whereClause !== '')
		{
			$this->sqlForCountElements = "select count(E1.id_element) as NROWS from $fromClause where $whereClause";
			$pagedElements = "select $selectCols from $fromClause where $whereClause $orderByClause $limit";
		}
		else
		{
			$this->sqlForCountElements = "select count(E1.id_element) as NROWS from $fromClause";
			$pagedElements = "select $selectCols from $fromClause $orderByClause $limit";
		}
		//$this->debugLogger()->write($this->sqlForCountElements);
		
		
		// JOIN DATA FIELDS
		
		// Select cols		
		if(isset($tripod->elementPMapper)) $tripod->elementPMapper->setElementPrefix('E');
		// includes element detail
		if($includeEltDetail)
		{
			$selectCols = $builder2->getSqlColumnsForElement('E4', 'E');
		}
		else
		{
			$selectCols = 'E3.id_element as Eid';
		}
		// includes db rights
		if($builder2->pRightsFromDb && !$builder2->hideDbRights)
		{
			$selectCols .= ', E3.canModify as EcanModify, E3.canWriteElement as EcanWriteElement, E3.canShareElement as EcanShareElement';
		}
		// includes fields
		$s = $builder2->getSqlColumnsForFieldList();
		if($s != null) $selectCols .= ', '.$s; 
		
		// From clause
		$fromClause = "($pagedElements) as E3 ";
		if($includeEltDetail) $fromClause .= " inner join Elements as E4 on E4.id_element = E3.id_element ";
		$fromClause .= $builder2->getFromClauseForFieldList();
		
		// Build final sql
		$returnValue = "select $selectCols from $fromClause";
		return $returnValue;
	}
		
	/**
	 * Returns an SQL query that should be used to count the total number of elements regardless of pagination
	 */
	public function getSqlForCountElements() {
		return $this->sqlForCountElements;
	}
	protected function actOnFieldForSelectAllStrategyJoin($field, $dataType)
	{
		// example: select FName.value as FXNameXvalueZZ from Element E left join Strings as FName on FName.id_element = E.id_element and FName.field = Name
		$fName = $field->getFieldName();
		
		// adds columns to select only if not a structural field or an explicitely selected structural field
		if(is_null($this->structuralFields) || is_null($this->structuralFields[$fName]) || ($this->structuralFields[$fName] === true))
		{
			$this->visitDataTypeSubfields($field, $dataType);
		}
		
		// adds left join for field
		$fP = $this->getFieldPrefix();
		$tableAlias = "`".$fP.$fName."`";		
		$this->leftJoinForFieldList($this->getDbAdminService()->getDataTypeTableName($dataType),
				$tableAlias,
				"id_element",
				$this->getElementJoinColumn(),
				$this->formatBinExp("$tableAlias.field", '=', $fName, MySqlQueryBuilder::SQLTYPE_VARCHAR));
		
		// adds left join for structural field
		if(isset($this->structuralFields) && isset($this->structuralFields[$fName])) {
			$this->leftJoinForStructuralField($this->getDbAdminService()->getDataTypeTableName($dataType),
					$tableAlias,
					"id_element",
					$this->getElementJoinColumnForStructuralFields(),
					$this->formatBinExp("$tableAlias.field", '=', $fName, MySqlQueryBuilder::SQLTYPE_VARCHAR));
		}
		// records field and table alias for structural fields
		$this->structuralFields[$fName] = $field;
		$this->structuralFieldTable[$fName] = $tableAlias;
	}
	protected function leftJoinForStructuralField($tableName, $tableAlias, $colToJoin, $joinExpression, $additionalCondition=null)
	{
		if(strlen($this->sqlFromStructuralFields) > 0) $this->sqlFromStructuralFields .= ' ';
		$this->sqlFromStructuralFields .= "left join $tableName as $tableAlias on $tableAlias.$colToJoin = $joinExpression";
		if(isset($additionalCondition)) $this->sqlFromStructuralFields .= " and $additionalCondition";
	}
}

/**
 * JOIN strategy for SELECTALL
 */
class ElementSqlBuilderForGetSelectedElementsInGroups extends ElementSqlBuilder
{
	protected $elementInGroupSqlB;
	private $moduleAS;
	protected $principal;
	protected $groupList;
	protected $pRightsFromDb;
	protected $hideDbRights = false;

	// object lifecycle

	public static function createNonInitInstance()
	{
		$returnValue = new ElementSqlBuilderForGetSelectedElementsInGroups();
		return $returnValue;
	}

	public static function createInstance($elementInGroupSqlB, $principal, $groupList, $pRightsFromDb)
	{
		$returnValue = ElementSqlBuilderForGetSelectedElementsInGroups::createNonInitInstance();
		$returnValue->reset($elementInGroupSqlB, $principal, $groupList, $pRightsFromDb);
		return $returnValue;
	}
	public function reset($elementInGroupSqlB, $principal, $groupList, $pRightsFromDb)
	{
		parent::reset();
		if(is_null($elementInGroupSqlB)) throw new ElementServiceException('elementInGroupSqlB can not be null', ElementServiceException::INVALID_ARGUMENT);
		if(is_null($principal)) throw new ElementServiceException('principal can not be null', ElementServiceException::INVALID_ARGUMENT);
		if(is_null($groupList)) throw new ElementServiceException('groupList can not be null', ElementServiceException::INVALID_ARGUMENT);
		$this->elementInGroupSqlB = $elementInGroupSqlB;
		if(is_array($groupList))
		{
			$this->singleGroup = false;
			if(count($groupList) == 0) throw new ElementServiceException('groupList can not be empty', ElementServiceException::INVALID_ARGUMENT);
			$this->groupList = $groupList;
		}
		elseif($groupList instanceof GroupList)
		{
			$this->singleGroup = false;
			if($groupList->isEmpty()) throw new ElementServiceException('groupList can not be empty', ElementServiceException::INVALID_ARGUMENT);
			$this->groupList = $groupList->getListIterator();
		}
		else
		{
			throw new ElementServiceException('groupList can only be a non empty instance of GroupList or an array of Group', ElementServiceException::INVALID_ARGUMENT);
		}
		$this->principal = $principal;
		$this->pRightsFromDb = $pRightsFromDb;

		// initialises ElementSqlBuilder
		$this->setElementJoinColumn('E01.id_element');
		$this->setFieldPrefix('F');
	}
	public function freeMemory()
	{
		parent::freeMemory();
		unset($this->elementInGroupSqlB);
		unset($this->groupList);
		unset($this->principal);
	}

	// dependency injection

	public function setModuleAdminService($moduleAdminService)
	{
		$this->moduleAS = $moduleAdminService;
	}
	protected function getModuleAdminService()
	{
		// autowired
		if(!isset($this->moduleAS))
		{
			$this->moduleAS = ServiceProvider::getModuleAdminService();
		}
		return $this->moduleAS;
	}

	// configuration

	/**
	 * Calling this method, will hide the DB rights column in the resulting select
	 * The columns EcanWriteElement and EcanShareElement will be calculated, but not displayed.
	 * To prevent calculating DB rights, use a pRights instead.
	 */
	public function hideDbRightsInSelectedCols() {
		$this->hideDbRights = true;
	}

	// query builder implementation

	/**
	 * Returns number of static joins in query
	 */
	public function getNumberOfStaticJoins($elementInGroupSqlB=null, $pRightsFromDb=false)
	{
		if(is_null($elementInGroupSqlB)) $elementInGroupSqlB = $this->elementInGroupSqlB;
		return $elementInGroupSqlB->getNumberOfJoins() + 1
			+ ($pRightsFromDb || $this->pRightsFromDb ? 2 : 0);
	}

	public function getSql()
	{
		$tripod = $this->getTripod();
		$eltS = $tripod->elementServiceImpl;
		if($this->pRightsFromDb){
			$principalId = $this->principal->getUserId();
			if(is_null($principalId)) throw new ElementServiceException('principal attached user can not be null', ElementServiceException::INVALID_ARGUMENT);
			$principalId = $this->formatValue($principalId, MySqlQueryBuilder::SQLTYPE_INT);
		}
		// Select elements
		$select_Elements = $this->elementInGroupSqlB->getSqlForElementInGroup();

		// Select elements principal rights
		if($this->pRightsFromDb)
		{
			$groupId = ''; $n = 0;
			foreach($this->groupList as $group)
			{
				if($n == 0)
				{
					$module = $group->getModule();
				}
				elseif($n > 0)
				{
					$groupId .= ', ';
				}
				$groupId .= $this->formatValue($group->getId(), MySqlQueryBuilder::SQLTYPE_INT);
				$n++;
			}
			$select_Groups = $eltS->getSqlForSelectGroups($groupId, true, 'GG01', 'G01');
			$select_ElementsP = $eltS->getSqlForSelectElementsP($eltS->getSqlForSelectGroupsP($this->principal, $module, $principalId,
				$select_Groups,
				$this->getModuleAdminService()->getDefaultPropagation($module), 'UGR01', 'GG01', 'GP01', 'G02'),
				'GP02','EG02');
		}

		// Select cols
		$includeEltDetail = $this->includeElementDetail();
		if(isset($tripod->elementPMapper)) $tripod->elementPMapper->setElementPrefix('E');
		// includes element detail
		if($includeEltDetail)
		{
			$selectCols = $this->getSqlColumnsForElement('E02', 'E');
		}
		else
		{
			$selectCols = 'E01.id_element as Eid';
		}
		// includes db rights
		if($this->pRightsFromDb && !$this->hideDbRights)
		{
			$selectCols .= ', E03.canModify as EcanModify, E03.canWriteElement as EcanWriteElement, E03.canShareElement as EcanShareElement';
		}

		// includes fields
		$s = $this->getSqlColumnsForFieldList();
		if($s != null) $selectCols .= ', '.$s; //LWR changed !== '' in != null

		// From clause
		$fromClause = "($select_Elements) as E01 ";
		if($this->pRightsFromDb)
		{
			$fromClause .= " inner join ($select_ElementsP) as E03 on E03.id_element = E01.id_element ";
		}
		if($includeEltDetail) $fromClause .= " inner join Elements as E02 on E02.id_element = E01.id_element ";
		$fromClause .= $this->getFromClauseForFieldList();

		// Where clause
		$whereClause = $this->getWhereClauseForFieldList('E02');

		// OrderBy clause
		$this->convertFieldSortingKeyListToOrderByClause('E02');
		$orderByClause = $this->getOrderByClause();

		// Build final sql
		if(isset($whereClause) && $whereClause !== '')
		{
			return "select $selectCols from $fromClause where $whereClause $orderByClause";
		}
		else
		{
			return "select $selectCols from $fromClause $orderByClause";
		}
	}
}

/**
 * JOIN strategy for SELECTALL
 */
class ElementSqlBuilderForGetSelectedElements extends ElementSqlBuilder
{
	protected $elementInIdListSqlB;
	private $moduleAS;
	protected $principal;
	protected $groupList;
	protected $pRightsFromDb;

	// object lifecycle

	public static function createNonInitInstance()
	{
		$returnValue = new ElementSqlBuilderForGetSelectedElements();
		return $returnValue;
	}

	public static function createInstance($elementInIdListSqlB, $principal, $groupList, $pRightsFromDb)
	{
		$returnValue = ElementSqlBuilderForGetSelectedElements::createNonInitInstance();
		$returnValue->reset($elementInIdListSqlB, $principal, $groupList, $pRightsFromDb);
		return $returnValue;
	}
	public function reset($elementInIdListSqlB, $principal, $groupList, $pRightsFromDb)
	{
		parent::reset();
		if(is_null($elementInIdListSqlB)) throw new ElementServiceException('elementInIdListSqlB can not be null', ElementServiceException::INVALID_ARGUMENT);
		if(is_null($principal)) throw new ElementServiceException('principal can not be null', ElementServiceException::INVALID_ARGUMENT);
		if(is_null($groupList)) throw new ElementServiceException('groupList can not be null', ElementServiceException::INVALID_ARGUMENT);
		$this->elementInIdListSqlB = $elementInIdListSqlB;
		if(is_array($groupList))
		{
			$this->singleGroup = false;
			if(count($groupList) == 0) throw new ElementServiceException('groupList can not be empty', ElementServiceException::INVALID_ARGUMENT);
			$this->groupList = $groupList;
		}
		elseif($groupList instanceof GroupList)
		{
			$this->singleGroup = false;
			if($groupList->isEmpty()) throw new ElementServiceException('groupList can not be empty', ElementServiceException::INVALID_ARGUMENT);
			$this->groupList = $groupList->getListIterator();
		}
		else
		{
			throw new ElementServiceException('groupList can only be a non empty instance of GroupList or an array of Group', ElementServiceException::INVALID_ARGUMENT);
		}
		$this->principal = $principal;
		$this->pRightsFromDb = $pRightsFromDb;

		// initialises ElementSqlBuilder
		$this->setElementJoinColumn('E01.id_element');
		$this->setFieldPrefix('F');
	}
	public function freeMemory()
	{
		parent::freeMemory();
		unset($this->elementInListIdSqlB);
		unset($this->groupList);
		unset($this->principal);
	}

	// dependency injection

	public function setModuleAdminService($moduleAdminService)
	{
		$this->moduleAS = $moduleAdminService;
	}
	protected function getModuleAdminService()
	{
		// autowired
		if(!isset($this->moduleAS))
		{
			$this->moduleAS = ServiceProvider::getModuleAdminService();
		}
		return $this->moduleAS;
	}

	// query builder implementation

	/**
	 * Returns number of static joins in query
	 */
	public function getNumberOfStaticJoins($elementInIdListSqlB=null, $pRightsFromDb=false)
	{
		if(is_null($elementInIdListSqlB)) $elementInIdListSqlB = $this->elementInIdListSqlB;
		return $elementInIdListSqlB->getNumberOfJoins() + 1
			+ ($pRightsFromDb || $this->pRightsFromDb ? 2 : 0);
	}

	public function getSql()
	{
		$tripod = $this->getTripod();
		$eltS = $tripod->elementServiceImpl;
		if($this->pRightsFromDb){
			$principalId = $this->principal->getUserId();
			if(is_null($principalId)) throw new ElementServiceException('principal attached user can not be null', ElementServiceException::INVALID_ARGUMENT);
			$principalId = $this->formatValue($principalId, MySqlQueryBuilder::SQLTYPE_INT);
		}
		// Select elements
		$select_Elements = $this->elementInIdListSqlB->getSqlForElementInIdList();

		// Select elements principal rights
		if($this->pRightsFromDb)
		{
			$groupId = ''; $n = 0;
			foreach($this->groupList as $group)
			{
				if($n == 0)
				{
					$module = $group->getModule();
				}
				elseif($n > 0)
				{
					$groupId .= ', ';
				}
				$groupId .= $this->formatValue($group->getId(), MySqlQueryBuilder::SQLTYPE_INT);
				$n++;
			}
			$select_Groups = $eltS->getSqlForSelectGroups($groupId, true, 'GG01', 'G01');
			$select_ElementsP = $eltS->getSqlForSelectElementsP($eltS->getSqlForSelectGroupsP($this->principal, $module, $principalId,
				$select_Groups,
				$this->getModuleAdminService()->getDefaultPropagation($module), 'UGR01', 'GG01', 'GP01', 'G02'),
				'GP02','EG02');
		}

		// Select cols
		$includeEltDetail = $this->includeElementDetail();
		if(isset($tripod->elementPMapper)) $tripod->elementPMapper->setElementPrefix('E');
		// includes element detail
		if($includeEltDetail)
		{
			$selectCols = $this->getSqlColumnsForElement('E02', 'E');
		}
		else
		{
			$selectCols = 'E01.id_element as Eid';
		}
		// includes db rights
		if($this->pRightsFromDb)
		{
			$selectCols .= ', E03.canModify as EcanModify, E03.canWriteElement as EcanWriteElement, E03.canShareElement as EcanShareElement';
		}

		// includes fields
		$s = $this->getSqlColumnsForFieldList();
		if($s != null) $selectCols .= ', '.$s; //LWR changed !== '' in != null

		// From clause
		$fromClause = "($select_Elements) as E01 ";
		if($this->pRightsFromDb)
		{
			$fromClause .= " inner join ($select_ElementsP) as E03 on E03.id_element = E01.id_element ";
		}
		if($includeEltDetail) $fromClause .= " inner join Elements as E02 on E02.id_element = E01.id_element ";
		$fromClause .= $this->getFromClauseForFieldList();

		// Where clause
		$whereClause = $this->getWhereClauseForFieldList('E02');

		// OrderBy clause
		$this->convertFieldSortingKeyListToOrderByClause('E02');
		$orderByClause = $this->getOrderByClause();

		// Build final sql
		if(isset($whereClause) && $whereClause !== '')
		{
			return "select $selectCols from $fromClause where $whereClause $orderByClause";
		}
		else
		{
			return "select $selectCols from $fromClause $orderByClause";
		}
	}
}

/**
 * JOIN strategy for SELECTALL
 */
class ElementSqlBuilderForGetSubElementsForField extends ElementSqlBuilder
{
	protected $principal;
	protected $masterElementId;
	protected $fieldName;

	// object lifecycle

	public static function createNonInitInstance()
	{
		$returnValue = new ElementSqlBuilderForGetSubElementsForField();
		return $returnValue;
	}

	public static function createInstance($principal, $masterElementId, $fieldName)
	{
		$returnValue = ElementSqlBuilderForGetSubElementsForField::createNonInitInstance();
		$returnValue->reset($principal, $masterElementId, $fieldName);
		return $returnValue;
	}
	public function reset($principal, $masterElementId, $fieldName)
	{
		parent::reset();
		if(is_null($principal)) throw new ElementServiceException('principal can not be null', ElementServiceException::INVALID_ARGUMENT);
		if(empty($masterElementId) || empty($fieldName)) throw new ElementServiceException('masterElementId or fieldName cannot be null', ElementServiceException::INVALID_ARGUMENT);

		$this->principal = $principal;
		$this->masterElementId = $masterElementId;
		$this->fieldName = $fieldName;

		// initialises ElementSqlBuilder
		$this->setElementJoinColumn('E1.id_element');
		$this->setFieldPrefix('F');
	}
	public function freeMemory()
	{
		parent::freeMemory();
		unset($this->principal);
	}

	// query builder implementation

	/**
	 * Returns number of static joins in query
	 */
	public function getNumberOfStaticJoins()
	{
		return 1;
	}

	public function getSql()
	{
		$tripod = $this->getTripod();
		$eltS = $tripod->elementServiceImpl;

		// Select cols
		$includeEltDetail = $this->includeElementDetail();
		if(isset($tripod->elementPMapper)) $tripod->elementPMapper->setElementPrefix('E');
		// includes element detail
		if($includeEltDetail)
		{
			$selectCols = $this->getSqlColumnsForElement('E1', 'E');
		}
		else
		{
			$selectCols = 'E1.id_element as Eid';
		}
		// includes fields
		$s = $this->getSqlColumnsForFieldList();
		if($s != null) $selectCols .= ', '.$s;

		// From clause
		$fromClause = "Elements as E1 ";
		$fromClause .= $this->getFromClauseForFieldList();

		// Where clause
		$masterElementId = $this->formatBinExp('E1.id_element_parent', '=', $this->masterElementId, MySqlQueryBuilder::SQLTYPE_INT);
		$fieldName = $this->formatBinExp('E1.linkName', '=', $this->fieldName, MySqlQueryBuilder::SQLTYPE_VARCHAR);
		$whereClause = $this->getWhereClauseForFieldList('E1');

		// OrderBy clause
		$this->convertFieldSortingKeyListToOrderByClause('E1');
		$orderByClause = $this->getOrderByClause();

		// Build final sql
		if(isset($whereClause) && $whereClause !== '')
		{
			return "select $selectCols from $fromClause where $masterElementId and $fieldName and $whereClause $orderByClause";
		}
		else
		{
			return "select $selectCols from $fromClause where $masterElementId and $fieldName $orderByClause";
		}
	}
}

/**
 * SELECTONE with JOIN and DATATYPE strategy
 */
class ElementSqlBuilderForFillElement extends ElementSqlBuilder
{
	private $moduleAS;
	protected $principal;
	protected $element;
	protected $pRightsFromDb;

	// object lifecycle

	public static function createNonInitInstance()
	{
		$returnValue = new ElementSqlBuilderForFillElement();
		return $returnValue;
	}

	public static function createInstance($principal, $element, $pRightsFromDb)
	{
		$returnValue = ElementSqlBuilderForFillElement::createNonInitInstance();
		$returnValue->reset($principal, $element, $pRightsFromDb);
		return $returnValue;
	}
	public function reset($principal, $element, $pRightsFromDb)
	{
		parent::reset();
		if(is_null($principal)) throw new ElementServiceException('principal can not be null', ElementServiceException::INVALID_ARGUMENT);
		if(is_null($element)) throw new ElementServiceException('element can not be null', ElementServiceException::INVALID_ARGUMENT);
		$this->principal = $principal;
		$this->element = $element;
		$this->pRightsFromDb = $pRightsFromDb;
		// initialises ElementSqlBuilder
		$this->setElementJoinColumn('E1.id_element');
		$this->setDataTypeTableAlias('dt');
		$this->setFieldPrefix('F');
	}
	public function freeMemory()
	{
		parent::freeMemory();
		unset($this->element);
		unset($this->principal);
	}

	// dependency injection

	public function setModuleAdminService($moduleAdminService)
	{
		$this->moduleAS = $moduleAdminService;
	}
	protected function getModuleAdminService()
	{
		// autowired
		if(!isset($this->moduleAS))
		{
			$this->moduleAS = ServiceProvider::getModuleAdminService();
		}
		return $this->moduleAS;
	}

	// query builder implementation

	/**
	 * Returns number of static joins in query
	 */
	public function getNumberOfStaticJoins()
	{
		return 3;
	}

	public function getSql()
	{
		$tripod = $this->getTripod();
		$eltS = $tripod->elementServiceImpl;
		if($this->pRightsFromDb){
			$principalId = $this->principal->getUserId();
			if(is_null($principalId)) throw new ElementServiceException('principal attached user can not be null', ElementServiceException::INVALID_ARGUMENT);
			$principalId = $this->formatValue($principalId, MySqlQueryBuilder::SQLTYPE_INT);
		}
		$elementId = $this->formatValue($this->element->getId(), MySqlQueryBuilder::SQLTYPE_INT);

		// if rights are given by the database
		if($this->pRightsFromDb)
		{
			$select_Element = $eltS->getSqlForSelectElementP($elementId, $eltS->getSqlForSelectGroupsP($this->principal, $this->element->getModule(), $principalId,
				$eltS->getSqlForSelectElementGroups($elementId, 'EG1'),
				$this->getModuleAdminService()->getDefaultPropagation($this->element->getModule()), 'UGR1', 'GG1', 'GP1', 'G1'),
				'GP2', 'EG2');
		}

		// Select cols
		$includeEltDetail = $this->includeElementDetail();
		if(isset($tripod->elementPMapper)) $tripod->elementPMapper->setElementPrefix('E');
		// includes element detail
		if($includeEltDetail && $this->pRightsFromDb)
		{
			$selectCols = $this->getSqlColumnsForElement('E2', 'E');
		}
		elseif($includeEltDetail)
		{
			$selectCols = $this->getSqlColumnsForElement('E1', 'E');
		}
		else
		{
			$selectCols = 'E1.id_element as Eid';
		}

		// includes db rights
		if($this->pRightsFromDb)
		{
			$selectCols .= ', E1.canModify as EcanModify, E1.canWriteElement as EcanWriteElement, E1.canShareElement as EcanShareElement';
		}
		// includes fields
		$s = $this->getSqlColumnsForFieldList();
		if($s != null) $selectCols .= ', '.$s; //LWR changed !== '' in != null

		// From clause
		if($this->pRightsFromDb)
		{
			$fromClause = "($select_Element) as E1 ";
			if($includeEltDetail) $fromClause .= " inner join Elements as E2 on E2.id_element = E1.id_element ";
		}
		else
		{
			$fromClause = "Elements as E1 ";
		}
		if($this->getQueryStrategy() === ElementQueryPlanner::QSTRATEGY_DATATYPE)
		{
			$fromClause .= "left join ".$this->getFromClauseForFieldList()." on dt.id_element = E1.id_element ";
			$s = $this->getWhereClauseForFieldList();
			if($s != '')
			{
				$fromClause .= " and ".$s;
			}
		}
		else
		{
			$fromClause .= $this->getFromClauseForFieldList();
		}


		// Where clause
		$whereClause = "E1.id_element = $elementId ";

		// Build final sql
		if(isset($whereClause) && $whereClause !== '')
		{
			return "select $selectCols from $fromClause where $whereClause";
		}
		else
		{
			return "select $selectCols from $fromClause";
		}
	}
}


class ElementSqlBuilderForMultipleElement extends ElementSqlBuilder
{
	private $moduleAS;
	protected $principal;
	protected $element;
	private $idsAreNew;

	// object lifecycle

	public static function createNonInitInstance()
	{
		$returnValue = new self();
		return $returnValue;
	}

	public static function createInstance($principal, $element, $idsAreNew=false)
	{
		$returnValue = ElementSqlBuilderForMultipleElement::createNonInitInstance();
		$returnValue->reset($principal, $element, $idsAreNew);
		return $returnValue;
	}
	//reset principal and element only if not null (back compatibility with parent)
	public function reset($principal = null, $element = null, $idsAreNew=false)
	{
		parent::reset();
		if(is_null($principal) && !isset($this->principal)) throw new ElementServiceException('principal can not be null', ElementServiceException::INVALID_ARGUMENT);
		if(is_null($element) && !isset($this->element)) throw new ElementServiceException('element can not be null', ElementServiceException::INVALID_ARGUMENT);
		if($principal!=null) $this->principal = $principal;
		if($element != null )$this->element = $element;
		// initialises ElementSqlBuilder
		$this->setElementJoinColumn('E1.id_element');
		$this->setDataTypeTableAlias('dt');
		$this->setFieldPrefix('F');
		$this->idsAreNew = $idsAreNew;
	}
	public function freeMemory()
	{
		parent::freeMemory();
		//on reset we don't want that the element of the principal are emptied
//		unset($this->element);
//		unset($this->principal);
	}

	// dependency injection

	public function setModuleAdminService($moduleAdminService)
	{
		$this->moduleAS = $moduleAdminService;
	}
	protected function getModuleAdminService()
	{
		// autowired
		if(!isset($this->moduleAS))
		{
			$this->moduleAS = ServiceProvider::getModuleAdminService();
		}
		return $this->moduleAS;
	}

	// multiple element implementation

	private $firstElementForField;
	private $crtElement;
	protected function actOnFieldForUpdate($field, $dataType)
	{
		$multipleElement = $this->getElement();
		if(!isset($multipleElement)) throw new ElementServiceException("for update multiple, multipleElement should be set", ElementServiceException::INVALID_ARGUMENT);
		$elementList = $multipleElement->getElementList();
		if(!isset($elementList)) throw new ElementServiceException("for update multiple, elementList should be set", ElementServiceException::INVALID_ARGUMENT);

		$this->firstElementForField = true;
		foreach($elementList->getListIterator() as $listItem){
			$element = $listItem->getDbEntity();
			$this->crtElement = $element;
			if($this->firstElementForField){
				$this->setTableForUpdate($this->getDbAdminService()->getDataTypeTableName($dataType), true);
				$this->updateValue("id_element", $element->getId(), MySqlQueryBuilder::SQLTYPE_INT);
				$this->updateValue("field", $field->getFieldName(), MySqlQueryBuilder::SQLTYPE_VARCHAR);
			} else {
				$this->insertMoreRecord();
				$this->insertMoreValue($element->getId(), MySqlQueryBuilder::SQLTYPE_INT);
				$this->insertMoreValue($field->getFieldName(), MySqlQueryBuilder::SQLTYPE_VARCHAR);
			}
			$this->visitDataTypeSubfields($field, $dataType);
			//after first loop, indicates we are adding only more values
			if($this->firstElementForField) $this->firstElementForField = false;
		}
	}

	protected function actOnSubfieldForUpdate($field, $dataType, $subFieldName, $subFieldType, $sqlType, $userSelectedSubField, $lang)
	{
		$element = $this->crtElement;
//		eput($element->getId());
//		eput($element->getId());
		if($this->idsAreNew){
			$val = $element->getFieldValueBeforeInsert($field->getFieldName(), $subFieldName);
		} else {
			$val = $element->getFieldValue($field->getFieldName(), $subFieldName);
		}
//		eput(":\n");
//		eput($val);
//		eput("\n");

		if(($sqlType === MySqlQueryBuilder::SQLTYPE_BLOB) || ($sqlType === MySqlQueryBuilder::SQLTYPE_LONGBLOB))
		{
			// if blobs then update if
			// - content is not null
			// - subfield is selected by user
			// - content is null and path is null
			if(isset($val) || $userSelectedSubField) $okForUpdate = true;
			else
			{
				if($dataType->getDataTypeName() === "Files")
				{
					if($this->idsAreNew){
						$path = $element->getFieldValueBeforeInsert($field->getFieldName(), "path");
					} else {
						$path = $element->getFieldValue($field->getFieldName(), "path");
					}
					$okForUpdate = !isset($path);
				}
				else $okForUpdate = false;
			}
		}
		else $okForUpdate = true;

		if($okForUpdate)
		{
			//if multilanguage
			if(isset($lang))
			{
				if(is_array($val)) $val = $val[$lang];
				$subFieldName .= "_".$lang;
			}
			if($this->firstElementForField){
				if(in_array($subFieldName, array("sys_creationUser", "sys_creationUsername", "sys_creationDate"))){
					$this->insertValueIfNotExist($subFieldName, $this->preformatValue($val, $subFieldType, $sqlType), $sqlType);
				} else {
					$this->updateValue($subFieldName, $this->preformatValue($val, $subFieldType, $sqlType), $sqlType);
				}
			} else {
				$this->insertMoreValue($this->preformatValue($val, $subFieldType, $sqlType), $sqlType);
			}
		}
	}

}

class ElementService_ElementIdMapperForInsertMultiple implements RowList, DbEntityList
{
	private $elementPAList;
	private $counter;
	private $newElementIds;
	public function getIds(){ return $this->newElementIds; }
	/**
	 * elementPAList needs to be a list of element which have ids begining from 0
	 * and then incremented by 1
	 */
	public static function createInstance($elementPAList)
	{
		if(is_null($elementPAList)) throw new ListException("elementPAList cannot be null", ListException::INVALID_ARGUMENT);
		$returnValue = new self();
		$returnValue->elementPAList = $elementPAList;
		$returnValue->counter = 1; //counter should start to 1 because the first line is reserved for headers
		$returnValue->newElementIds = array();
		return $returnValue;
	}
	public function addRow($row)
	{
		if(is_null($row)) throw new ListException("row cannot be null", ListException::INVALID_ARGUMENT);
		$elementId = $row["id_element"];
		if(is_null($elementId)) throw new ListException("id_element field needs to be defined into row", ListException::INVALID_ARGUMENT);

		$this->newElementIds[] = $elementId;
		$this->elementPAList->getElementP($this->counter++)->getElement()->setId($elementId);
	}

	public function getListIterator(){ throw new ServiceException("unsuported in this implementation", ServiceException::UNSUPPORTED_OPERATION); }
	public function isEmpty(){ return $this->newElementIds==null; }
	public function count(){ return count($this->newElementIds); }
}