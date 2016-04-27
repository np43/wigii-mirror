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

/*
 * Created on 19 march 2013
 * by CWE
 */

/**
 * A batch to load the reporting database
 */
class LoadReportingDbBatch extends WigiiBatch {

	//dependency injection

	private $_debugLogger;
	private function debugLogger() {
		if (!isset ($this->_debugLogger)) {
			$this->_debugLogger = DebugLogger :: getInstance("LoadReportingDbBatch");
		}
		return $this->_debugLogger;
	}

	private $reportingDbCs;
	public function setReportingDbConnectionSettings($reportingDbCs) {
		$this->reportingDbCs = $reportingDbCs;
	}
	protected function getReportingDbConnectionSettings() {
		if(!isset($this->reportingDbCs)) {
			$this->reportingDbCs = DbConnectionSettings::createInstance();
			$this->reportingDbCs->setHostName(REPORTING_DB_HOST);
			$this->reportingDbCs->setUsername(REPORTING_DB_USER);
			$this->reportingDbCs->setPassword(REPORTING_DB_PWD);
			$this->reportingDbCs->setDbName(REPORTING_DB_NAME);
		}
		return $this->reportingDbCs;
	}

	private $mysqlF;
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

	private $dbAS;
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

	private $eltS;
	public function setElementService($elementService)
	{
		$this->eltS = $elementService;
	}
	protected function getElementService()
	{
		// autowired
		if(!isset($this->eltS))
		{
			$this->eltS = ServiceProvider::getElementService();
// 			$eltQueryPlanner = new LoadReportingDbBatchQueryPlanner();
// 			$eltQueryPlanner->freeMemory();
			$this->eltS->setElementQueryPlanner($eltQueryPlanner);
		}
		return $this->eltS;
	}

	private $authoS;
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

	private $moduleAS;
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

	private $wigiiNamespaceAS;
	public function setWigiiNamespaceAdminService($wigiiNamespaceAdminService)
	{
		$this->wigiiNamespaceAS = $wigiiNamespaceAdminService;
	}
	protected function getWigiiNamespaceAdminService()
	{
		// autowired
		if(!isset($this->wigiiNamespaceAS))
		{
			$this->wigiiNamespaceAS = ServiceProvider::getWigiiNamespaceAdminService();
		}
		return $this->wigiiNamespaceAS;
	}

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
	
	private $gAS;
	public function setGroupAdminService($groupAdminService)
	{
		$this->gAS = $groupAdminService;
	}
	protected function getGroupAdminService()
	{
		// autowired
		if(!isset($this->gAS))
		{
			$this->gAS = ServiceProvider::getGroupAdminService();
		}
		return $this->gAS;
	}
	
	// Configuration
	
	private $numberOfTablesPerNamespaceModule;
	/**
	 * Sets the maximum expected number of tables per namespace and modules.
	 */
	public function setNumberOfTablesPerNamespaceModule($n) {
		$this->numberOfTablesPerNamespaceModule = $n;
	}
	/**
	 * Returns the maximum expected number of tables per namespace and modules.
	 * Defaults to 20.
	 */
	protected function getNumberOfTablesPerNamespaceModule() {
		if(!isset($this->numberOfTablesPerNamespaceModule)) {
			$this->numberOfTablesPerNamespaceModule = 20;
		}
		return $this->numberOfTablesPerNamespaceModule;
	}
	
	// Batch execution

	public function run($argc, $argv, $subArgIndex) {
		$principal = $this->getPrincipal();

		// asserts that principal is root
		$this->getAuthorizationService()->assertPrincipalIsRoot($principal);

		$dbCS = $this->getDbAdminService()->getDbConnectionSettings($principal);
		$reportingDbCS = $this->getReportingDbConnectionSettings();

		if($reportingDbCS->getHost() != $dbCS->getHost()) {
			throw new ServiceException("the reporting database should be on the same host as the wigii database. Reporting database host ='".$reportingDbCS->getHost()."', wigii database host='".$dbCS->getHost()."'", ServiceException::CONFIGURATION_ERROR);
		}
		$this->dropWigiiCore($principal, $reportingDbCS, $dbCS);
		$this->replicateWigiiCore($principal, $reportingDbCS, $dbCS);
		$this->updateStatistics($principal, $reportingDbCS, $dbCS);
		$this->createNamespaceAndModuleTables($principal, $reportingDbCS, $dbCS);
	}

	// Implementation


	// DROP WIGII CORE

	protected function dropWigiiCore($principal, $reportingDbCS, $dbCS) {
		$this->getMySqlFacade()->execDDLCmd($principal,
				$this->getSqlToDropWigiiCore(),
				$reportingDbCS);
	}

	protected function getSqlToDropWigiiCore() {
		return "drop table if exists `Elements_Elements`,
			`Elements_Groups`,
			`Users_Groups_Rights`,
			`Elements`,
			`Groups_Groups`,
			`Groups`,
			`Users_Users`,
			`Users`";
	}


	// REPLICATE WIGII CORE

	protected function replicateWigiiCore($principal, $reportingDbCS, $dbCS) {
		$mySqlF = $this->getMySqlFacade();
		$reportingDbName = $reportingDbCS->getDbName();

		$mySqlF->replicateWholeDbTable($principal,
			DbTable::createInstanceForSqlCreate($reportingDbName,
			'Users', $this->getSqlCreateStatementForUsers()), $dbCS);

		$mySqlF->replicateWholeDbTable($principal,
			DbTable::createInstanceForSqlCreate($reportingDbName,
			'Users_Users', $this->getSqlCreateStatementForUsers_Users()), $dbCS);

		$mySqlF->replicateWholeDbTable($principal,
			DbTable::createInstanceForSqlCreate($reportingDbName,
			'Groups', $this->getSqlCreateStatementForGroups()), $dbCS);

		$mySqlF->replicateWholeDbTable($principal,
			DbTable::createInstanceForSqlCreate($reportingDbName,
			'Groups_Groups', $this->getSqlCreateStatementForGroups_Groups()), $dbCS);

		$mySqlF->replicateWholeDbTable($principal,
			DbTable::createInstanceForSqlCreate($reportingDbName,
			'Elements', $this->getSqlCreateStatementForElements()), $dbCS);

		$mySqlF->replicateWholeDbTable($principal,
			DbTable::createInstanceForSqlCreate($reportingDbName,
			'Users_Groups_Rights', $this->getSqlCreateStatementForUsers_Groups_Rights()), $dbCS);

		$mySqlF->replicateWholeDbTable($principal,
			DbTable::createInstanceForSqlCreate($reportingDbName,
			'Elements_Groups', $this->getSqlCreateStatementForElements_Groups()), $dbCS);

		$mySqlF->replicateWholeDbTable($principal,
			DbTable::createInstanceForSqlCreate($reportingDbName,
			'Elements_Elements', $this->getSqlCreateStatementForElements_Elements()), $dbCS);
	}
	protected function getSqlCreateStatementForUsers() {
		return "(PRIMARY KEY (`id_user`),
  UNIQUE KEY `username` (`username`),
  KEY `wigiiNamespace` (`wigiiNamespace`),
  KEY `username_password` (`username`,`password`),
  KEY `isRole` (`isRole`),
  KEY `isCalculatedRole` (`isCalculatedRole`),
  KEY `sys_lockId` (`sys_lockId`),
  KEY `sys_lockMicroTime` (`sys_lockMicroTime`),
  KEY `sys_date` (`sys_date`),
  KEY `sys_user` (`sys_user`),
  KEY `sys_username` (`sys_username`),
  KEY `sys_creationDate` (`sys_creationDate`),
  KEY `sys_creationUser` (`sys_creationUser`),
  KEY `sys_creationUsername` (`sys_creationUsername`)
)";

	}
	protected function getSqlCreateStatementForUsers_Users() {
		return "(PRIMARY KEY (`id_relation_user`),
  UNIQUE KEY `isOwner` (`id_user_owner`,`id_user`,`isOwner`),
  UNIQUE KEY `hasRole` (`id_user_owner`,`id_user`,`hasRole`)
)";
	}
	protected function getSqlCreateStatementForGroups() {
		return "(PRIMARY KEY (`id_group`),
  KEY `groupname` (`groupname`),
  KEY `modulename` (`modulename`),
  KEY `wigiiNamespace` (`wigiiNamespace`),
  KEY `id_group_parent` (`id_group_parent`),
  KEY `sys_lockId` (`sys_lockId`),
  KEY `sys_lockMicroTime` (`sys_lockMicroTime`),
  KEY `sys_date` (`sys_date`),
  KEY `sys_user` (`sys_user`),
  KEY `sys_username` (`sys_username`),
  KEY `sys_creationDate` (`sys_creationDate`),
  KEY `sys_creationUser` (`sys_creationUser`),
  KEY `sys_creationUsername` (`sys_creationUsername`)
)";

	}
	protected function getSqlCreateStatementForGroups_Groups() {
		return "(PRIMARY KEY (`id_relation_group`),
  UNIQUE KEY `id_group_owner` (`id_group_owner`,`id_group`)
)";
	}
	protected function getSqlCreateStatementForElements() {
		return "(PRIMARY KEY (`id_element`),
  UNIQUE KEY `id_element` (`id_element`,`modulename`),
  KEY `created_by` (`sys_creationUser`),
  KEY `last_modif_user` (`sys_user`),
  KEY `tags` (`tags`),
  KEY `sys_lockId` (`sys_lockId`),
  KEY `state_locked` (`state_locked`),
  KEY `sys_lockMicroTime` (`sys_lockMicroTime`),
  KEY `sys_date` (`sys_date`),
  KEY `sys_user` (`sys_user`),
  KEY `sys_username` (`sys_username`),
  KEY `sys_creationDate` (`sys_creationDate`),
  KEY `sys_creationUser` (`sys_creationUser`),
  KEY `sys_creationUsername` (`sys_creationUsername`),
  KEY `state_important1` (`state_important1`),
  KEY `state_important2` (`state_important2`),
  KEY `state_hidden` (`state_hidden`),
  KEY `state_archived` (`state_archived`),
  KEY `state_deprecated` (`state_deprecated`),
  KEY `state_finalized` (`state_finalized`),
  KEY `state_approved` (`state_approved`),
  KEY `state_dismissed` (`state_dismissed`),
  KEY `state_blocked` (`state_blocked`),
  KEY `version` (`version`)
)";
	}
	protected function getSqlCreateStatementForUsers_Groups_Rights() {
		return "(PRIMARY KEY (`id_user_group_right`),
  UNIQUE KEY `id_user` (`id_user`,`id_group`),
  KEY `canModify` (`canModify`),
  KEY `canWriteElement` (`canWriteElement`),
  KEY `canShareElement` (`canShareElement`)
)";
	}
	protected function getSqlCreateStatementForElements_Groups() {
		return "(PRIMARY KEY (`id_element_group`),
  UNIQUE KEY `id_element` (`id_element`,`id_group`),
  KEY `id_group` (`id_group`,`id_element`)
)";
	}
	protected function getSqlCreateStatementForElements_Elements() {
		return "(PRIMARY KEY (`id_elements_elements`),
  UNIQUE KEY `link` (`id_element_owner`,`id_element`,`linkName`),
  KEY `linkName` (`linkName`)
)";
	}

	// 	UPDATE STATISTICS

	protected function updateStatistics($principal, $reportingDbCS, $dbCS) {
		$mySqlF = $this->getMySqlFacade();
		$reportingDbName = $reportingDbCS->getDbName();

		$mySqlF->replicateOnlyNewRecordsCreateTableIfNotExist($principal,
			DbTable::createInstanceForSqlCreate($reportingDbName,
			'GlobalStatistic', $this->getSqlCreateStatementForGlobalStatistic()), 'id_statistic', $dbCS);

		$mySqlF->replicateOnlyNewRecordsCreateTableIfNotExist($principal,
			DbTable::createInstanceForSqlCreate($reportingDbName,
			'FileStatistic', $this->getSqlCreateStatementForFileStatistic()), 'id_statistic', $dbCS);

		$mySqlF->replicateOnlyNewRecordsCreateTableIfNotExist($principal,
			DbTable::createInstanceForSqlCreate($reportingDbName,
			'ElementStatistic', $this->getSqlCreateStatementForElementStatistic()), 'id_statistic', $dbCS);
	}

	protected function getSqlCreateStatementForGlobalStatistic() {
		return "(PRIMARY KEY (`id_statistic`),
  KEY `timestamp` (`timestamp`),
  KEY `eventName` (`eventName`),
  KEY `entityName` (`entityName`),
  KEY `entityId` (`entityId`),
  KEY `modulename` (`modulename`),
  KEY `wigiiNamespace` (`wigiiNamespace`),
  KEY `userId` (`userId`),
  KEY `realUserId` (`realUserId`)
)";
	}

	protected function getSqlCreateStatementForFileStatistic() {
		return "(PRIMARY KEY (`id_statistic`),
  KEY `timestamp` (`timestamp`),
  KEY `eventName` (`eventName`),
  KEY `entityName` (`entityName`),
  KEY `modulename` (`modulename`),
  KEY `wigiiNamespace` (`wigiiNamespace`),
  KEY `userId` (`userId`),
  KEY `realUserId` (`realUserId`),
  KEY `elementId` (`elementId`),
  KEY `field` (`field`)" .
  		")";
	}

	protected function getSqlCreateStatementForElementStatistic() {
		return "(PRIMARY KEY (`id_statistic`),
  KEY `timestamp` (`timestamp`),
  KEY `eventName` (`eventName`),
  KEY `entityName` (`entityName`),
  KEY `modulename` (`modulename`),
  KEY `wigiiNamespace` (`wigiiNamespace`),
  KEY `userId` (`userId`),
  KEY `realUserId` (`realUserId`),
  KEY `elementId` (`elementId`)" .
  		")";
	}

	// CREATE NAMESPACE AND MODULE TABLES

	/**
	 * $var = array(NamespaceName=>["*" | array(ModuleName[, ...])])
	 */
	public function setNamespaceFilter($var){
		$this->namespaceFilter = $var;
	}
	private $namespaceFilter;
	protected function getNamespaceFilter($key=null){
		if($key) return $this->namespaceFilter[$key];
		return $this->namespaceFilter;
	}
	public function setNamespaceExclusions($var){
		$this->namespaceExclusions = $var;
	}
	private $namespaceExclusions;
	protected function getNamespaceExclusions($key=null){
		if($key) return $this->namespaceExclusions[$key];
		return $this->namespaceExclusions;
	}
	protected function createNamespaceAndModuleTables($principal, $reportingDbCS, $dbCS) {
		$mySqlF = $this->getMySqlFacade();

		// 1. selects namespaces and modules
		$namespacesAndModules = RowListArrayImpl::createInstance();
		if($mySqlF->selectAll($principal, $this->getSqlForSelectNamespacesAndModules(), $dbCS, $namespacesAndModules) > 0)
		{
			// 2. Iterates on each namespace and module
			$nsAS = $this->getWigiiNamespaceAdminService();
			$mAS = $this->getModuleAdminService();
			foreach ($namespacesAndModules->getListIterator() as $row) {
				$namespaceName = $row['wigiiNamespace'];
				$moduleName = $row['modulename'];
				if($this->getNamespaceExclusions()){
					if($this->getNamespaceExclusions($namespaceName)==="*"){
						continue;
					} else if(is_array($this->getNamespaceExclusions($namespaceName))){
						if(in_array($moduleName, $this->getNamespaceExclusions($namespaceName))){
							continue;
						} else {
							//execute
						}
					} else {
						//execute
					}
				}
				if($this->getNamespaceFilter()){
					if($this->getNamespaceFilter($namespaceName)==="*"){
						//execute
					} else if(is_array($this->getNamespaceFilter($namespaceName))){
						if(in_array($moduleName, $this->getNamespaceFilter($namespaceName))){
							//execute
						} else {
							continue;
						}
					} else {
						continue;
					}
				}

				//$this->debugLogger()->write("namespaceName=$namespaceName, moduleName=$moduleName");
				
				// sets current wigii namespace and module access
				$principal->changeWigiiNamespace($nsAS->getWigiiNamespace($principal, $namespaceName));
				$principal->addAccessToModule($mAS->getModule($principal, $moduleName));

				//3. creates a table with all elements in namespace and module
				$tableName = $this->getDbTableNameForElementsInNamespaceAndModule($namespaceName, $moduleName);
				$dbTable = $this->createDbTableInstanceForElementsInNamespaceAndModule($tableName, $reportingDbCS->getDbName());

				// drops old table if exists
				$mySqlF->execDDLCmd($principal,
					$this->getSqlForDropNamespaceAndModuleTable($dbTable),
					$reportingDbCS);

				// creates new table
				$nRows = $this->getElementService()->createDbTableWithSelectedElementsInGroups($principal,
					$this->getInGroupLogExpForElementsInNamespaceAndModule($namespaceName, $moduleName),
					$dbTable,
					$this->getListFilterForElementsInNamespaceAndModule($principal, $namespaceName, $moduleName));
				$this->debugLogger()->write("$tableName: $nrows inserted");
			}
		}
	}

	protected function getSqlForSelectNamespacesAndModules() {
		return "select wigiiNamespace, modulename from Groups group by wigiiNamespace, modulename order by wigiiNamespace, modulename";
	}

	protected function getSqlForDropNamespaceAndModuleTable($dbTable) {
		$tableName = $dbTable->getTableName();
		$returnValue = "drop table if exists `".$tableName."`, `"
		.$tableName."0`, `";
		$n = $this->getNumberOfTablesPerNamespaceModule();
		for($i = 1; $i < $n; $i++) {
			$returnValue .= $tableName."$i`, `";
		}
		$returnValue .= $tableName."$n`";
		return $returnValue;
	}

	private $listFilterForElementsInNamespaceAndModule;
	protected function getListFilterForElementsInNamespaceAndModule($principal, $namespaceName, $moduleName)
	{
		if(!isset($this->listFilterForElementsInNamespaceAndModule)) {
			$this->listFilterForElementsInNamespaceAndModule = ListFilter::createInstance();
		}
		$this->listFilterForElementsInNamespaceAndModule->resetPagination();
		
		// creates FieldSelectorList with all fields (without sys fields) of groups contained in this namespace and module
		$groupList = GroupListArrayImpl::createInstance();
		$this->getGroupAdminService()->getSelectedGroupsWithoutDetail($principal, lxAnd(lxEq(fs('wigiiNamespace'), $namespaceName), lxEq(fs('module'), $moduleName), lxNotEq(fs('groupname'), 'zz. Trashbin')), $groupList);
		$fsl = FieldSelectorListArrayImpl::createInstanceAsFieldList(false, false, true);
		$this->getConfigService()->getGroupsFields($principal, $groupList, null, $fsl);
		$this->listFilterForElementsInNamespaceAndModule->setConfigGroupList($groupList);
		$this->listFilterForElementsInNamespaceAndModule->setFieldSelectorList($fsl);
		
		return $this->listFilterForElementsInNamespaceAndModule;
	}

	protected function getInGroupLogExpForElementsInNamespaceAndModule($namespaceName, $moduleName) {
		return TechnicalServiceProvider::getFieldSelectorLogExpParser()->createLogExpFromString('ING(wigiiNamespace = "'.$namespaceName.'" && module = "'.$moduleName.'" && groupname != "zz. Trashbin")');
	}

	protected function getDbTableNameForElementsInNamespaceAndModule($namespaceName, $moduleName) {
		return $namespaceName." ".$moduleName;
	}

	protected function createDbTableInstanceForElementsInNamespaceAndModule($tableName, $dbName) {
		$returnValue = DbTable::createInstance($tableName, $dbName);
		$returnValue->setUserFriendlySqlColNames(true);
		$returnValue->hideDbRightsInSelectedCols(true);
		$returnValue->setTableCreationStatementDetail("(PRIMARY KEY (`Eid`)) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci");

		return $returnValue;
	}
}

// class LoadReportingDbBatchQueryPlanner extends ElementQueryPlanner {
// 	private $_debugLogger;
// 	private function debugLogger() {
// 		if (!isset ($this->_debugLogger)) {
// 			$this->_debugLogger = DebugLogger :: getInstance("LoadReportingDbBatchQueryPlanner");
// 		}
// 		return $this->_debugLogger;
// 	}
	
// 	protected function getMaxRelativeWeightOfQuery() {	
// 		$returnValue = 58000;
// 		//$this->debugLogger()->write("getMaxRelativeWeightOfQuery=$returnValue");
// 		return $returnValue;
// 	}
// }