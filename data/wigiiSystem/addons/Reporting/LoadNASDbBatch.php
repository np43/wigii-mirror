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
 * Created on 11 July 2013
 * by LWR
 */

/**
 * A batch to load a NAS from the live database
 */
class LoadNASDbBatch extends WigiiBatch {

	//dependency injection

	private $_debugLogger;
	private function debugLogger() {
		if (!isset ($this->_debugLogger)) {
			$this->_debugLogger = DebugLogger :: getInstance("LoadNASDbBatch");
		}
		return $this->_debugLogger;
	}

	private $nasDbCs;
	public function setNASDbConnectionSettings($nasDbCs) {
		$this->nasDbCs = $nasDbCs;
	}
	protected function getNASDbConnectionSettings() {
		if(!isset($this->nasDbCs)) {
			$this->nasDbCs = DbConnectionSettings::createInstance();
			$this->nasDbCs->setHostName(NAS_DB_HOST);
			$this->nasDbCs->setUsername(NAS_DB_USER);
			$this->nasDbCs->setPassword(NAS_DB_PWD);
			$this->nasDbCs->setDbName(NAS_DB_NAME);
		}
		return $this->nasDbCs;
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

	// Batch execution

	/**
	 * $var = array(NamespaceName=>["*" | array(ModuleName[, ...])])
	 */
	private $namespaceFilter;
	public function setNamespaceFilter($var){
		$this->namespaceFilter = $var;
	}
	//default on Global namespace
	protected function getNamespaceFilter($key=null){
		if(!isset($this->namespaceFilter)){
			$this->namespaceFilter = array("Global"=>array("Contacts", "E-library", "Calendar", "Info"));
		}
		if($key) return $this->namespaceFilter[$key];
		return $this->namespaceFilter;
	}

	public function run($argc, $argv, $subArgIndex) {
		$principal = $this->getPrincipal();

		// asserts that principal is root
		$this->getAuthorizationService()->assertPrincipalIsRoot($principal);

		$dbCS = $this->getDbAdminService()->getDbConnectionSettings($principal);
		$nasDbCs = $this->getNASDbConnectionSettings();

		if($nasDbCs->getHost() != $dbCS->getHost()) {
			throw new ServiceException("the NAS database should be on the same host as the wigii database. NAS database host ='".$nasDbCs->getHost()."', wigii database host='".$dbCS->getHost()."'", ServiceException::CONFIGURATION_ERROR);
		}

		if($argc > 0 && $argv[0]!="onlyData"){
			//drop other tables
			$this->dropWigiiOtherTables($principal, $nasDbCs, $dbCS);
			//create other tables
			$this->createWigiiOtherTables($principal, $nasDbCs, $dbCS);
			//drop Wigii Access
			$this->dropWigiiAccess($principal, $nasDbCs, $dbCS);
			//replicate Access
			$this->replicateWigiiAccess($principal, $nasDbCs, $dbCS);
			//remove any content, but keep the structure of tables
			$this->cleanWigiiAccess($principal, $nasDbCs);
			//create local access
			$superAdminId = $this->createLocalSuperAdmin($principal, $nasDbCs, $this->getAllExistingModules($principal, $dbCS));
			$localAdministratorId = $this->createLocalAdministrator($principal, $nasDbCs, $superAdminId);
			$this->createOtherUsers($principal, $nasDbCs, $localAdministratorId);
		}

		//drop Wigii Data
		$this->dropWigiiData($principal, $nasDbCs, $dbCS);
		//replicate Wigii Data
		$this->replicateWigiiData($principal, $nasDbCs, $dbCS);

	}

	// Implementation

	// Wigii Other tables

	protected function getWigiiOtherTables(){
		return array("ElementStatistic", "FileStatistic", "GlobalStatistic", "EmailService", "EmailServiceAttachementsToDelete");
	}
	protected function dropWigiiOtherTables($principal, $nasDbCS, $dbCS){
			$this->getMySqlFacade()->execDDLCmd($principal,
					"DROP TABLE IF EXISTS `".implode("`,`",$this->getWigiiOtherTables())."`",
					$nasDbCS);
	}
	protected function createWigiiOtherTables($principal, $nasDbCS, $dbCS){
		foreach($this->getWigiiOtherTables() as $table){
			$this->getMySqlFacade()->execDDLCmd($principal,
					"CREATE TABLE `$table` LIKE ".$dbCS->getDbName().".`$table`",
					$nasDbCS);
		}
	}
	// DROP Wigii Access

	protected function dropWigiiAccess($principal, $nasDbCS, $dbCS) {
		$this->getMySqlFacade()->execDDLCmd($principal,
				$this->getSqlToDropWigiiAccess(),
				$nasDbCS);
	}

	protected function getSqlToDropWigiiAccess() {
		return "DROP TABLE IF EXISTS
			`Users`,
			`Users_Users`,
			`Users_Groups_Rights`
			";
	}

	// REPLICATE Wigii Access

	protected function replicateWigiiAccess($principal, $nasDbCS, $dbCS) {
		$mySqlF = $this->getMySqlFacade();
		$nasDbName = $nasDbCS->getDbName();

		$mySqlF->createTableFromSelectAll($principal,
			DbTable::createInstanceForSqlCreate($nasDbName,
			'Users', $this->getSqlCreateStatementForUsers()),
			$this->getSqlForSelectAllTable('Users'), $dbCS);

		$mySqlF->createTableFromSelectAll($principal,
			DbTable::createInstanceForSqlCreate($nasDbName,
			'Users_Users', $this->getSqlCreateStatementForUsers_Users()),
			$this->getSqlForSelectAllTable('Users_Users'), $dbCS);

		$mySqlF->createTableFromSelectAll($principal,
			DbTable::createInstanceForSqlCreate($nasDbName,
			'Users_Groups_Rights', $this->getSqlCreateStatementForUsers_Groups_Rights()),
			$this->getSqlForSelectAllTable('Users_Groups_Rights'), $dbCS);

	}
	protected function getSqlCreateStatementForUsers() {
		return "(`id_user` INT(11) NULL AUTO_INCREMENT, PRIMARY KEY (`id_user`),
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
		return "(`id_relation_user` INT(11) NULL AUTO_INCREMENT, PRIMARY KEY (`id_relation_user`),
  UNIQUE KEY `isOwner` (`id_user_owner`,`id_user`,`isOwner`),
  UNIQUE KEY `hasRole` (`id_user_owner`,`id_user`,`hasRole`)
  )";
	}
	protected function getSqlCreateStatementForUsers_Groups_Rights() {
		return "(`id_user_group_right` INT(11) NULL AUTO_INCREMENT, PRIMARY KEY (`id_user_group_right`),
  UNIQUE KEY `id_user` (`id_user`,`id_group`),
  KEY `canModify` (`canModify`),
  KEY `canWriteElement` (`canWriteElement`),
  KEY `canShareElement` (`canShareElement`)
  )";
	}

	// CLEAN Wigii Access

	protected function cleanWigiiAccess($principal, $nasDbCs){
		$this->getMySqlFacade()->execDDLCmd($principal,"TRUNCATE TABLE `Users`",$nasDbCs);
		$this->getMySqlFacade()->execDDLCmd($principal,"TRUNCATE TABLE `Users_Users`",$nasDbCs);
		$this->getMySqlFacade()->execDDLCmd($principal,"TRUNCATE TABLE `Users_Groups_Rights`",$nasDbCs);
	}

	//create SuperAdmin access
	protected function createLocalSuperAdmin($principal, $nasDbCs, $modules){
		//create the role
		$superAdminId = $this->getMySqlFacade()->insertOne($principal,
				$this->getSqlToCreateSuperAdminRole($modules),
				$nasDbCs);
		return $superAdminId;
	}
	/**
	 * @param array $modules array of modulenames to include in the SuperAdmin role access
	 */
	protected function getSqlToCreateSuperAdminRole($modules){
		$modules = implode(";", $modules);
		return '
INSERT INTO `Users` SET
`username` = 						"SuperAdmin",
`wigiiNamespace` = 					NULL,
`password` = 						md5(CONCAT("SuperAdmin",UNIX_TIMESTAMP())),
`passwordLength` = 					0,
`canModifyOwnPassword` = 			FALSE,
`authenticationMethod` = 			"usual",
`description` = 					CONCAT("Main SuperAdmin. Created on ",SYSDATE()),
`wigiiNamespaceCreator` = 			TRUE,
`moduleEditor` = 					TRUE,
`readAllUsersInWigiiNamespace` = 	TRUE,
`adminCreator` = 					TRUE,
`userCreator` = 					TRUE,
`moduleAccess` =  					"Admin;'.$modules.'",
`readAllGroupsInWigiiNamespace` = 	"'.$modules.'",
`rootGroupCreator` = 				"'.$modules.'",
`groupCreator` = 					"'.$modules.'",
`sys_date` = 						UNIX_TIMESTAMP(),
`sys_user` = 						1,
`isRole` = 							TRUE
';
	}
	protected function createLocalAdministrator($principal, $nasDbCs, $superAdminId){
		//create the local administrator
		$localAdministratorId = $this->getMySqlFacade()->insertOne($principal,
				$this->getSqlToCreateLocalAdministrator(),
				$nasDbCs);
		//link the local administrator to superAdmin role
		$this->getMySqlFacade()->insertOne($principal,
				$this->getSqlToLinkLocalAdministratorToSuperAdminRole($superAdminId, $localAdministratorId),
				$nasDbCs);
		return $localAdministratorId;
	}
	protected function getLocalAministratorPassword(){
		return "mk8urtheChrist";
	}
	protected function getSqlToCreateLocalAdministrator(){
		return '
INSERT INTO `Users` SET
	`username` = 						"administrator",
	`wigiiNamespace` = 					NULL,
	`password` = 						md5("'.$this->getLocalAministratorPassword().'"),
	`passwordLength` = 					14,
	`canModifyOwnPassword` = 			False,
	`authenticationMethod` = 			"usual",
	`description` = 					CONCAT("First user. Created on ",SYSDATE()),
	`sys_date` = 						UNIX_TIMESTAMP(),
	`sys_user` = 						1,
	`isRole` = 							FALSE
;
';
	}
	protected function getSqlToLinkLocalAdministratorToSuperAdminRole($superAdminId, $localAdministratorId){
		return "
INSERT INTO `Users_Users` (`id_relation_user` ,`id_user_owner` ,`id_user` ,`isOwner` ,`hasRole`)
VALUES (NULL , '$localAdministratorId', '$superAdminId', NULL , '1');";
	}
	protected function createOtherUsers($principal, $nasDbCs, $localAdministratorId){
		//create any other needed user
	}

	//GET All Modules

	private $allModules = null;
	protected function getAllExistingModules($principal, $cnxSettings){
		if(!isset($this->allModules)){
			$this->allModules = array();
		}
		$dbName = $cnxSettings->getDbName();
		if(!isset($this->allModules[$dbName])){
			$result = RowListArrayImpl::createInstance();
			if($this->getMySqlFacade()->selectAll($principal, $this->getSqlForSelectModules(), $cnxSettings, $result) > 0){
				$this->allModules[$dbName] = array();
				foreach($result->getListIterator() as $res){
					$this->allModules[$dbName][] = $res["Modulename"];
				}
			}
		}
		return $this->allModules[$dbName];
	}

	protected function getSqlForSelectModules() {
		return "SELECT `Modulename` FROM `Groups` GROUP BY `Modulename` ORDER BY `Modulename`";
	}

	//GET All WigiiNamespace

	private $allWigiiNamespace = null;
	protected function getAllExistingWigiiNamespace($principal, $cnxSettings){
		if(!isset($this->allWigiiNamespace)){
			$this->allWigiiNamespace = array();
		}
		$dbName = $cnxSettings->getDbName();
		if(!isset($this->allWigiiNamespace[$dbName])){
			$result = RowListArrayImpl::createInstance();
			if($this->getMySqlFacade()->selectAll($principal, $this->getSqlForSelectWigiiNamespace(), $cnxSettings, $result) > 0){
				$this->allWigiiNamespace[$dbName] = array();
				foreach($result->getListIterator() as $res){
					$this->allWigiiNamespace[$dbName][] = $res["WigiiNamespace"];
				}
			}
		}
		return $this->allWigiiNamespace[$dbName];
	}

	protected function getSqlForSelectWigiiNamespace() {
		return "SELECT `WigiiNamespace` FROM `Users` GROUP BY `WigiiNamespace` ORDER BY `WigiiNamespace`";
	}

	// GET All Data Structure tables

	protected function getDataStructureTables(){
		return array(
			"Groups",
			"Groups_Groups",
			"Elements",
			"Elements_Groups",
			"Elements_Elements"
		);
	}
	protected function getSqlDataStructureList(){
		return "`".implode("`,`", $this->getDataStructureTables())."`";
	}

	// GET All DataType tables

	protected function getDataTypeTables(){
		return array(
			"Addresses",
			"Attributs",
			"Blobs",
			"Booleans",
			"Dates",
			"Emails",
			"Files",
			"Floats",
			"Links",
			"MultipleAttributs",
			"Numerics",
			"Strings",
			"Texts",
			"TimeRanges",
			"Times",
			"Urls",
			"Varchars"
		);
	}
	protected function getSqlDataTypeList(){
		return "`".implode("`,`", $this->getDataTypeTables())."`";
	}

	// DROP Wigii Data

	protected function dropWigiiData($principal, $nasDbCS, $dbCS) {
		$this->getMySqlFacade()->execDDLCmd($principal,
				$this->getSqlToDropWigiiData(),
				$nasDbCS);
	}

	protected function getSqlToDropWigiiData() {
		return "DROP TABLE IF EXISTS
			".$this->getSqlDataStructureList().",
			".$this->getSqlDataTypeList()."
			";
	}

	// REPLICATE Wigii Data

	protected function replicateWigiiData($principal, $nasDbCS, $dbCS) {
		$mySqlF = $this->getMySqlFacade();
		$nasDbName = $nasDbCS->getDbName();

		foreach($this->getDataStructureTables() as $table){
			$mySqlF->createTableFromSelectAll($principal,
				DbTable::createInstanceForSqlCreate($nasDbName,
				$table, $this->{"getSqlCreateStatementFor".$table}()),
				$this->getSqlForSelectAllTable($table), $dbCS);
		}
		foreach($this->getDataTypeTables() as $table){
			$mySqlF->createTableFromSelectAll($principal,
				DbTable::createInstanceForSqlCreate($nasDbName,
				$table, $this->getSqlCreateStatementForDataType($table)),
				$this->getSqlForSelectAllTable($table), $dbCS);
		}
	}
	private $whereOnWigiiNamespace = null;
	protected function getWhereFilterOnWigiiNamespace($alias=null){
		if($this->getNamespaceFilter() && !isset($this->whereOnWigiiNamespace)){
			if($alias) $alias = $alias.".";
			//filter on wigiiNamespace and Modules
			$wigiiNamespaceFilter = array_keys($this->getNamespaceFilter());
			$whereStatement = "$alias`wigiiNamespace` IN('".implode("','", $wigiiNamespaceFilter)."')";
			$this->whereOnWigiiNamespace = $whereStatement;
		}
		return $this->whereOnWigiiNamespace;
	}
	private $whereOnWigiiNamespaceAndModule = null;
	protected function getWhereFilterOnWigiiNamespaceAndModule($alias=null){
		if($this->getNamespaceFilter() && !isset($this->whereOnWigiiNamespaceAndModule)){
			//filter on wigiiNamespace and Modules
			if($alias) $alias = $alias.".";
			$wigiiNamespaceFilter = array_keys($this->getNamespaceFilter());
			$whereStatement = array();
			foreach($this->getNamespaceFilter() as $wigiiNamespaceName=>$modules){
				if($modules=="*"){
					$whereStatement[] = "$alias`wigiiNamespace` = '$wigiiNamespaceName'";
				} else {
					$whereStatement[] = "$alias`wigiiNamespace` = '$wigiiNamespaceName' AND $alias`Modulename` IN('".implode("','", $modules)."')";
				}
			}
			$whereStatement = implode(" OR ", $whereStatement);
			$this->whereOnWigiiNamespaceAndModule = $whereStatement;
		}
		return $this->whereOnWigiiNamespaceAndModule;
	}
	protected function getSqlForSelectAllTable($table){
		$whereStatement = null;
		if($this->getNamespaceFilter()){
			switch($table){
				case "Users":
					//filter on wigiiNamespaces
					$whereStatement = $this->getWhereFilterOnWigiiNamespace();
					break;
				case "Groups":
					$whereStatement = $this->getWhereFilterOnWigiiNamespaceAndModule();
					break;
				case "Elements":
					//filter on existing Groups
					$whereStatement = "`id_element` IN(
							SELECT eg.id_element FROM `Elements_Groups` eg WHERE eg.id_group IN(
								SELECT g.id_group FROM Groups g WHERE ".$this->getWhereFilterOnWigiiNamespaceAndModule("g")."
							)
						)";
					break;
				//DataTypes, filter on existing Elements
				case "Addresses":
				case "Attributs":
				case "Blobs":
				case "Booleans":
				case "Dates":
				case "Emails":
				case "Files":
				case "Floats":
				case "Links":
				case "MultipleAttributs":
				case "Numerics":
				case "Strings":
				case "Texts":
				case "TimeRanges":
				case "Times":
				case "Urls":
				case "Varchars":
					$whereStatement = "`id_element` IN(
							SELECT eg.id_element FROM `Elements_Groups` eg WHERE eg.id_group IN(
								SELECT g.id_group FROM Groups g WHERE ".$this->getWhereFilterOnWigiiNamespaceAndModule("g")."
							)
						)";
					break;
			}
		}
		if($whereStatement) $whereStatement = " WHERE ".$whereStatement;
		return "SELECT * FROM `$table` $whereStatement";
	}
	protected function getSqlCreateStatementForGroups() {
		return "(`id_group` INT(11) NULL AUTO_INCREMENT, PRIMARY KEY (`id_group`),
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
	protected function getSqlCreateStatementForElements_Groups() {
		return "(`id_element_group` INT(11) NULL AUTO_INCREMENT, PRIMARY KEY (`id_element_group`),
  UNIQUE KEY `id_element` (`id_element`,`id_group`),
  KEY `id_group` (`id_group`,`id_element`))";
	}
	protected function getSqlCreateStatementForGroups_Groups() {
		return "(`id_relation_group` INT(11) NULL AUTO_INCREMENT, PRIMARY KEY (`id_relation_group`),
  UNIQUE KEY `id_group_owner` (`id_group_owner`,`id_group`))";
	}
	protected function getSqlCreateStatementForElements() {
		return "(`id_element` INT(11) NULL AUTO_INCREMENT, PRIMARY KEY (`id_element`),
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
	protected function getSqlCreateStatementForElements_Elements() {
		return "(`id_elements_elements` INT(11) NULL AUTO_INCREMENT, PRIMARY KEY (`id_elements_elements`),
  UNIQUE KEY `link` (`id_element_owner`,`id_element`,`linkName`),
  KEY `linkName` (`linkName`))";
	}
	protected function getSqlCreateStatementForDataType($dataTypeName) {
		$dataTypeS = substr($dataTypeName, 0, strlen($dataTypeName)-1); //remove the ending s

		//innoDb do not support index on text columns
		//be carefull to not include text columns such as Emails.value or MultipleAttribut.value or Blobs.value or Texts
		switch($dataTypeName){
			case "Addresses": $subCol = array("street", "zip_code", "city", "state", "country"); break;
			case "Blobs": $subCol = array(); break;
			case "Emails": $subCol = array("proofKey", "externalCode", "externalAccessLevel", "externalAccessEndDate", "externalConfigGroup", "proofStatus"); break;
			case "Files": $subCol = array("name", "date", "type", "mime", "size", "user", "username", "path"); break;
			case "MultipleAttributs": $subCol = array(); break;
			case "Texts": $subCol = array(); break;
			case "TimeRanges": $subCol = array("isAllDay", "begTime", "endTime", "begDate", "endDate"); break;
			case "Urls": $subCol = array("name", "url"); break;
			case "Varchars": $subCol = array("value_l01", "value_l02", "value_l03", "value_l04", "value_l05", "value_l06", "value_l07", "value_l08", "value_l09", "value_l10"); break;
			default : $subCol = array("value");
		}
		$subIndex = array();
		foreach($subCol as $subName){
			$subIndex[] = "KEY `$subName` (`$subName`)";
		}
		return "(`id_$dataTypeS` INT(11) NULL AUTO_INCREMENT,
	PRIMARY KEY (`id_$dataTypeS`),
	UNIQUE
		KEY `id_element` (`id_element`,`field`)
		".($subIndex ? ", ".implode(", ", $subIndex) : "")."
		)";

	}

}