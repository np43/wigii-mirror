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

/* wigii DbAdminService implementation
 * Created by CWE on 25 juin 09
 */
class DbAdminServiceImpl implements DbAdminService
{
	private $_debugLogger;
	private $_executionSink;
	private $authoS;
	private $mysqlF;
	private $constraintsMap;
	private $isReferencedByMap;
	private $installedDataTypes;
	private $idListMapper;
	private $valueListMapper;
	private $valueListArrayMapper;
	private $recursiveDeleteRowsState;

	/**
	 * DbConnectionSettings cache
	 * map clientName -> DbConnectionSettings
	 */
	private $dbConnectionSettingsCache;


	private function debugLogger()
	{
		if(!isset($this->_debugLogger))
		{
			$this->_debugLogger = DebugLogger::getInstance("DbAdminServiceImpl");
		}
		return $this->_debugLogger;
	}
	private function executionSink()
	{
		if(!isset($this->_executionSink))
		{
			$this->_executionSink = ExecutionSink::getInstance("DbAdminServiceImpl");
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
	public function setIdListMapper($idListMapper)
	{
		$this->idListMapper = $idListMapper;
	}
	protected function getIdListMapper()
	{
		// autowired
		if(!isset($this->idListMapper))
		{
			$this->idListMapper = IdListMapper::createInstance();
		}
		else
		{
			if($this->idListMapper->isLockedForUse()) {
				return IdListMapper::createInstance();
			}
			else $this->idListMapper->reset();
		}
		return $this->idListMapper;
	}
	public function setValueListMapper($valueListMapper)
	{
		$this->valueListMapper = $valueListMapper;
	}
	protected function getValueListMapper($valueList, $colName)
	{
		// autowired
		if(!isset($this->valueListMapper))
		{
			$this->valueListMapper = ValueListMapper::createInstance($valueList, $colName);
		}
		else
		{
			if($this->valueListMapper->isLockedForUse()) {
				return ValueListMapper::createInstance($valueList, $colName);
			}
			else $this->valueListMapper->reset($valueList, $colName);
		}
		return $this->valueListMapper;
	}
	public function setValueListArrayMapper($valueListArrayMapper)
	{
		$this->valueListArrayMapper = $valueListArrayMapper;
	}
	protected function getValueListArrayMapper($distinct=false, $possibleSeparator = null, $trim =false)
	{
		// autowired
		if(!isset($this->valueListArrayMapper))
		{
			$this->valueListArrayMapper = ValueListArrayMapper::createInstance($distinct, $possibleSeparator, $trim);
		}
		else
		{
			if($this->valueListArrayMapper->isLockedForUse()) {
				return ValueListArrayMapper::createInstance($distinct, $possibleSeparator, $trim);
			}
			else $this->valueListArrayMapper->reset($distinct, $possibleSeparator, $trim);
		}
		return $this->valueListArrayMapper;
	}
	public function setRecursiveDeleteRowsState($recursiveDeleteRowsState)
	{
		$this->recursiveDeleteRowsState = $recursiveDeleteRowsState;
	}
	protected function getRecursiveDeleteRowsState()
	{
		// autowired
		if(!isset($this->recursiveDeleteRowsState))
		{
			$this->recursiveDeleteRowsState = RecursiveDeleteRowsState::createInstance();
		}
		else
		{
			$this->recursiveDeleteRowsState->reset();
		}
		return $this->recursiveDeleteRowsState;
	}





	// service implementation


	public function getDbConnectionSettings($principal)
	{
		$this->executionSink()->publishStartOperation("getDbConnectionSettings", $principal);
		try
		{
			if(is_null($principal)) throw new DbAdminServiceException("principal cannot be null", DbAdminServiceException::INVALID_ARGUMENT);
			$returnValue = $this->doGetDbConnectionSettingsForClient($principal, $principal->getWigiiNamespace()->getClient());
		}
		catch(DbAdminServiceException $dbaE){
			$this->executionSink()->publishEndOperationOnError("getDbConnectionSettings", $dbaE, $principal);
			throw $dbaE;
		}
		catch(Exception $e)
		{
			$this->executionSink()->publishEndOperationOnError("getDbConnectionSettings", $e, $principal);
			throw new DbAdminServiceException('',DbAdminServiceException::WRAPPING, $e);
		}
		$this->executionSink()->publishEndOperation("getDbConnectionSettings", $principal);
		return $returnValue;
	}

	public function getDbConnectionSettingsForClient($principal, $client)
	{
		$this->executionSink()->publishStartOperation("getDbConnectionSettingsForClient", $principal);
		try
		{
			// checks authorization
			$this->assertPrincipalAuthorizedForGetDbConnectionSettingsForClient($principal);

			// ok, go on
			$returnValue = $this->doGetDbConnectionSettingsForClient($principal, $client);
		}
		catch (DbAdminServiceException $dbaE){
			$this->executionSink()->publishEndOperationOnError("getDbConnectionSettingsForClient", $dbaE, $principal);
			throw $dbaE;
		}
		catch (AuthorizationServiceException $asE){
			$this->executionSink()->publishEndOperationOnError("getDbConnectionSettingsForClient", $asE, $principal);
			throw $asE;
		}
		catch(Exception $e)
		{
			$this->executionSink()->publishEndOperationOnError("getDbConnectionSettingsForClient", $e, $principal);
			throw new DbAdminServiceException('',DbAdminServiceException::WRAPPING, $e);
		}
		$this->executionSink()->publishEndOperation("getDbConnectionSettingsForClient", $principal);
		return $returnValue;
	}
	/**
	 * In this implementation, only the AuthenticationService is authorized to call this method
	 */
	protected function assertPrincipalAuthorizedForGetDbConnectionSettingsForClient($principal)
	{
		$this->getAuthorizationService()->assertPrincipalIsAuthenticationService($principal);
	}

	protected function doGetDbConnectionSettingsForClient($principal, $client)
	{
		$returnValue = $this->getCachedDbConnectionSettings($client);
		if(!isset($returnValue))
		{
			$returnValue = $this->createDbConnectionSettingsInstance($principal, $client);
			$this->cacheDbConnectionSettings($client, $returnValue);
		}
		return $returnValue;
	}

	/**
	 * creates a DbConnectionSettings instance for the given client
	 * returns a DbConnectionSettings instance
	 * This method needs to be implemented, this is why it throws an UNSUPPORTED_OPERATION exception
	 */
	protected function createDbConnectionSettingsInstance($principal, $client)
	{
		//Warning, here we cannot create a DbConnectionSettings because
		//it is linked to the implementation.
		//-> throw UNSUPPORTED_OPERATION exception
		//and need to use the Web Impl to have something
		throw new DbAdminServiceException("DbConnectionSettings can not be created, because need of an implementation. Use Web Impl for i.e.", DbAdminServiceException::UNSUPPORTED_OPERATION);
	}

	public function deleteRows($principal, $tableName, $whereClause, $cnxSettings)
	{
		$this->executionSink()->publishStartOperation("deleteRows", $principal);
		try
		{
			// no special check for now, the obtention of cnxSettings is sufficient.
			// ok, to go on
			$returnValue = $this->doRecursiveDeleteRows($principal,
					$tableName, $whereClause, $cnxSettings,
					$this->getRecursiveDeleteRowsState());
		}
		catch (DbAdminServiceException $dbaE){
			$this->executionSink()->publishEndOperationOnError("deleteRows", $dbaE, $principal);
			throw $dbaE;
		}
		catch (AuthorizationServiceException $asE){
			$this->executionSink()->publishEndOperationOnError("deleteRows", $asE, $principal);
			throw $asE;
		}
		catch(Exception $e)
		{
			$this->executionSink()->publishEndOperationOnError("deleteRows", $e, $principal);
			throw new DbAdminServiceException('',DbAdminServiceException::WRAPPING, $e);
		}
		$this->executionSink()->publishEndOperation("deleteRows", $principal);
		return $returnValue;
	}

	/**
	 * Returns Wigii database cardinality constraints map
	 */
	protected function getConstraintsMap()
	{
		if(!isset($this->constraintsMap))
		{
			$this->constraintsMap = array (
				'Users_Users'			=>array('Users'=>array('id_user_owner'=>'*','id_user'=>'*')), //changed the 28 april to **. First impl was * + --> that if delete a user, this will delete also all owned users which are owned only by this user. This is not good, because their is to much risk to delete a user without wanting that all his subusers will be deleted. a readAllUsersInWigiiNamespace will always be able to manage those users.
				'Users_Groups_Rights'	=>array('Users'=>array('id_user'=>'*'),
												'Groups'=>array('id_group'=>'*')), //changed to ** the 28 april. First impl was * + --> if the last user who has the ugr is deleted then the group is deleted and subgroups --> this is not good, because we prefer keep the groups even if the last user who has an ugr is deleted. A readAllGroupInWigiiNamespace will always be able to see them.
				'Groups_Groups'			=>array('Groups'=>array('id_group_owner'=>'*','id_group'=>'*')),
				'Elements_Groups'		=>array('Elements'=>array('id_element'=>'+'), 
												'Groups'=>array('id_group'=>'*')),
				'DataType'				=>array('Elements'=>array('id_element'=>'*')),
				'Groups'				=>array('Groups'=>array('id_group_parent'=>'*')),
				'Elements_Elements'		=>array('Elements'=>array('id_element_owner' => '*', 'id_element' => '*')),
				'Elements'				=>array('Elements'=>array('id_element_parent' => '*'))
			);
		}
		return $this->constraintsMap;
	}

	/**
	 * Returns Wigii database association map
	 */
	protected function getIsReferencedByMap()
	{
		if(!isset($this->isReferencedByMap))
		{
			$this->isReferencedByMap = array(
				'Users'=>array('Users_Users','Users_Groups_Rights'),
				'Groups'=>array('Users_Groups_Rights','Groups_Groups','Groups','Elements_Groups'),
				'Elements'=>array('Elements_Groups','DataType','Elements_Elements','Elements')
			);
		}
		return $this->isReferencedByMap;
	}

	/**
	 * Returns Wigii database entity primary keys names
	 */
	protected function getIdColumnName($tableName)
	{
		switch($tableName)
		{
			case 'Users': return 'id_user';
			case 'Groups': return 'id_group';
			case 'Elements': return 'id_element';
			case 'EmailService': return 'id_email';
			default: throw new DbAdminServiceException("undeclared table name $tableName", DbAdminServiceException::INVALID_ARGUMENT);
		}
	}

	/**
	 * Returns an array with current installed datatypes names
	 */
	protected function getInstalledDataTypes()
	{
		if(!isset($this->installedDataTypes))
		{
			$this->installedDataTypes = array("Strings", "Attributs", "Booleans", "Addresses", "Varchars", "Texts", "Dates", "Times", "TimeRanges", "MultipleAttributs", "Blobs", "Files", "Urls", "Floats", "Numerics", "Emails", "Links");
		}
		return $this->installedDataTypes;
	}
	protected function setInstalledDataTypes($dataTypes)
	{
		$this->installedDataTypes = $dataTypes;
	}

	/**
	 * Returns maximum number of retry on a failed delete, default to 2
	 */
	protected function getMaxRetriesOnError()
	{
		return 2;
	}

	/**
	 * Returns maximum number of minutes an orphan can be locked without beeing deleted, default 2
	 */
	protected function getOrphansMaxLockDuration()
	{
		return 2;
	}

	/**
	 * Recursively deletes rows in the database
	 * state: RecursiveDeleteRowsState instance
	 * precondition: columns in whereClause should be prefixed with table name
	 */
	protected function doRecursiveDeleteRows($principal, $tableName, $whereClause, $cnxSettings, $state)
	{
		$returnValue = 0;

		// if $tableName is referenced by a another table T then delete all references of $tableName in T
		$referrers = $this->getIsReferencedByMap();
		$constraintsMap = $this->getConstraintsMap();
		$referrers = $referrers[$tableName];
		if(isset($referrers))
		{
			$selectRowsToDelete = $this->resolveIdList($principal,
				"select $tableName.".$this->getIdColumnName($tableName)." from $tableName where $whereClause",
				$cnxSettings
				);
			if(isset($selectRowsToDelete))
			{
				$state->appendDeletedIdList($tableName, $selectRowsToDelete);
				foreach($referrers as $t)
				{
					$constraints = $constraintsMap[$t];
					if(!isset($constraints)) throw new DbAdminServiceException("missing constraints for table $t", DbAdminServiceException::INVALID_DB_SCHEMA);
					$constraints = $constraints[$tableName];
					if(!isset($constraints)) throw new DbAdminServiceException("missing constraints for table $t=>$tableName", DbAdminServiceException::INVALID_DB_SCHEMA);
					$n = count($constraints); $i = 1;
					$tWhereClause = "";
					foreach($constraints as $colName => $cardinality)
					{
						$tWhereClause .= "$t.$colName in ($selectRowsToDelete)";
						if($i < $n) $tWhereClause .= " or ";
						$i++;
					}
					$returnValue += $this->doRecursiveDeleteRows($principal, $t, $tWhereClause, $cnxSettings, $state);
				}
			}
		}
		// delete rows from $tableName
		$returnValue += $this->doDeleteRows($principal, $tableName, $whereClause, $cnxSettings);

		// if $tableName constrains a table T then delete all records for which the constraints are violated
		$constrainedTables = $constraintsMap[$tableName];
		if(isset($constrainedTables))
		{
			foreach($constrainedTables as $t => $constraints)
			{
				if(!is_array($constraints)) throw new DbAdminServiceException("missing constraints for table $tableName=>$t", DbAdminServiceException::INVALID_DB_SCHEMA);
				foreach($constraints as $colName => $cardinality)
				{
					// only takes care of cardinality + or 1
					if($cardinality === '+' || $cardinality === '1')
					{
						$tIdColName = $this->getIdColumnName($t);
						$selectRowsToDelete = $this->getSqlForSelectOrphansToDelete($principal, $t, $tableName, $colName);
						$deletedIDs = $state->getDeletedIdList($t);
						if(isset($deletedIDs)) $selectRowsToDelete .= " and not $t.$tIdColName in ($deletedIDs)";
						$selectRowsToDelete = $this->resolveIdList($principal, $selectRowsToDelete, $cnxSettings);
						if(isset($selectRowsToDelete))
						{
							$tWhereClause = "$t.$tIdColName in ($selectRowsToDelete)";
							try
							{
								// delete all records for which the constraints are violated
								$returnValue += $this->doRecursiveDeleteRows($principal, $t, $tWhereClause, $cnxSettings, $state);
							}
							catch(Exception $e)
							{
								$maxRetries = $this->getMaxRetriesOnError();
								// tries maxTries more times to delete all records for which the constraints are violated
								for($i = 0; $i < $maxRetries; $i++)
								{
									try
									{
										// delete all records for which the constraints are violated
										$returnValue += $this->doRecursiveDeleteRows($principal, $t, $tWhereClause, $cnxSettings, $state);
										break;
									}
									catch(Exception $e1){/* does nothing, try again */}
								}
								// gives up, will be automatically cleaned next time database is accessible and a delete happens.
							}
						}
					}
				}
			}
		}
		return $returnValue;
	}
	private function resolveIdList($principal, $sql, $cnxSettings)
	{
		$idListMapper = $this->getIdListMapper();
		$returnValue = null;
		try {
			if($this->getMySqlFacade()->selectAll($principal, $sql, $cnxSettings, $idListMapper, MYSQL_NUM) > 0)
			{
				$returnValue = $idListMapper->getIdList();
			}
			$idListMapper->freeMemory();
		}
		catch(Exception $e) {
			$idListMapper->freeMemory();
		}
		return $returnValue;		
	}

	/**
	 * Sql to select orphans in $tableName to be deleted
	 * referrerTableName and referrerColName are used to determine which rows in tableName are no more referenced by referrer and therefore are orphans.
	 */
	protected function getSqlForSelectOrphansToDelete($principal, $tableName, $referrerTableName, $referrerColName)
	{
		$idColName = $this->getIdColumnName($tableName);
		//LWR, the CWE comment is deprectated. Read the one bellow. todo CWE delete not only orphans elements but also elements in non manageable groups
		//LWR: the check for group sharing is made in the ElementService when calling the deleteRow function.
		//To delete elements we are not doing a delete on Elements but only on Element-Groups (and on write groups).
		//--> so orhpan elements are the ones to delete.
		/** todo CWE, added by LWR, subject to Groups
		 * Groups normally cannot be orphans, because the GUI limit the delete of any last UGR on a group
		 * The fact that the only constraint is to have an UGR even with only read rights is not problematic
		 * because of readAllGroupInWigiiNamespace admin level, which will allow to the superadmin to always add an ugr on
		 * a user with more rights than read.
		 * There is two limit cases:
		 * 	- if there is no more user in the wigiiNamespace of the group which has readAllGroup + rootGroupCreator
		 * 	- if we delete the user which has the last UGR on the group then the group and all his content will be deleted
		 * the first case should not happen, because the first user has all rights, and it is not possible to change our own rights or to diminish our rights with a user with less rights than us
		 * the second case could be prevented on User deletion, and forcing the delete of the user only if he does not have the last right on a group
		 * or on that case we can add to the current principal an UGR with the current calculated right
		 * Subject on elements in SGroups
		 * Again on delete User there is a limit case for elements in SGroups.
		 * 	- we need to prevent that elements could be in SGroup with a sys_user on an unexisting user
		 * 		- update every sys_user = deleted user? --> elements will remain in read only in the SGroup
		 * 		- put the current principal user id in sys_user = deleted user (take ownership)? --> we don't have necessarly SRights on the same groups, it could be on unrechable groups for the principal
		 * 		- if we say that a SGroup has necessarly at least one user with a X right on it, is it enough? --> this is still a bit messy in this SGroup
		 * 		- ???
		 */

		if(is_null($principal)) throw new DbAdminServiceException("principal cannot be null", DbAdminServiceException::INVALID_ARGUMENT);
		
		// if table name is Elements then filters subitems of beeing deleted
		if($tableName === 'Elements') {
			return "select $tableName.$idColName from $tableName left join $referrerTableName on $referrerTableName.$referrerColName = $tableName.$idColName where $referrerTableName.$referrerColName is null and ".
				"$tableName.id_element_parent is null and ".
				$this->getSqlChunkToTestLockCondition(
					$this->getMySqlFacade()->getSqlBuilder(),
					$principal->getExecutionId(),
					$principal->getMicroTime(),
					$this->getLockExpirationMicrosec(),
					$tableName
					);
		}
		//deletes orphans without lock or with expired lock or with principal lock
		else return "select $tableName.$idColName from $tableName left join $referrerTableName on $referrerTableName.$referrerColName = $tableName.$idColName where $referrerTableName.$referrerColName is null and ".
				$this->getSqlChunkToTestLockCondition(
					$this->getMySqlFacade()->getSqlBuilder(),
					$principal->getExecutionId(),
					$principal->getMicroTime(),
					$this->getLockExpirationMicrosec(),
					$tableName
					);
	}

	/**
	 * Effectively deletes row in a table
	 * precondition: columns in whereClause should be prefixed with table name
	 */
	protected function doDeleteRows($principal, $tableName, $whereClause, $cnxSettings)
	{
		if($tableName === 'DataType')
		{
			$returnValue = 0;
			$mySqlF = $this->getMySqlFacade();
			foreach($this->getInstalledDataTypes() as $dataTypeTable)
			{
				$whereClause2 = str_replace('DataType.', $dataTypeTable.".", $whereClause);
				$returnValue += $mySqlF->delete($principal, "delete from $dataTypeTable where $whereClause2", $cnxSettings);
			}
			return $returnValue;
		}		
		else
		{
			//eput("delete from $tableName where $whereClause\n");
			return $this->getMySqlFacade()->delete($principal, "delete from $tableName where $whereClause", $cnxSettings);
		}
	}

	public function getDataTypeTableName($dataType)
	{
		if(is_null($dataType)) throw new DbAdminServiceException('dataType can not be null', DbAdminServiceException::INVALID_ARGUMENT);
		return $dataType->getDataTypeName();
	}
	
	/**
	 * Get the list of locked ids.
	 * @param $dbEntity can be either DbEntity or ObjectList of DbEntity
	 * If dbEntity is a list, then lock is checked on all objects.
	 * returns array of ids of locked items
	 */
	public function getLocked($principal, $tableName, $dbEntity){
		$this->executionSink()->publishStartOperation("getLocked", $principal);
		try
		{
			if(is_null($principal)) throw new DbAdminServiceException("principal cannot be null", DbAdminServiceException::INVALID_ARGUMENT);
			if(is_null($dbEntity)) throw new DbAdminServiceException("dbEntity cannot be null", DbAdminServiceException::INVALID_ARGUMENT);

			// gets ids
			if($dbEntity instanceof DbEntityList)
			{
				$dbEntityId = $dbEntity->getIds();
				$nEntities = $dbEntity->count();
			}
			elseif($dbEntity instanceof ObjectList)
			{
				$nEntities = 0;
				foreach($dbEntity->getListIterator() as $dbE)
				{
					$dbEntityId[] = $dbE->getId();
					$nEntities++;
				}
			}
			elseif(is_array($dbEntity)){
				$dbEntityId = array_keys($dbEntity);
				$nEntities = count($dbEntity);
			} else {
				$dbEntityId = $dbEntity->getId();
				$nEntities = 1;
			}
			if($nEntities == 0) throw new DbAdminServiceException("dbEntity cannot be empty", DbAdminServiceException::INVALID_ARGUMENT);

			$mySqlF = $this->getMySqlFacade();
			$dbCS = $this->getDbConnectionSettings($principal);
			$microTime = $principal->getMicroTime();
			$expiration = $this->getLockExpirationMicrosec();
			$rowList = RowListArrayImpl::createInstance($this->getIdColumnName($tableName));
			//lookup for current locked items
			$mySqlF->selectAll($principal,
				$this->getSqlForListLockedEntities($tableName,
					$microTime,
					$expiration,
					$dbEntityId),
				$dbCS, $rowList);
			$returnValue = $rowList->getKeys();
		}
		catch (DbAdminServiceException $dbaE){
			$this->executionSink()->publishEndOperationOnError("getLocked", $dbaE, $principal);
			throw $dbaE;
		}
		catch (AuthorizationServiceException $asE){
			$this->executionSink()->publishEndOperationOnError("getLocked", $asE, $principal);
			throw $asE;
		}
		catch(Exception $e)
		{
			$this->executionSink()->publishEndOperationOnError("getLocked", $e, $principal);
			throw new DbAdminServiceException('',DbAdminServiceException::WRAPPING, $e);
		}
		$this->executionSink()->publishEndOperation("getLocked", $principal);
		return $returnValue;
	}
	
	/**
	 * Locks the dbEntity/s
	 * @param $dbEntity can be either DbEntity or ObjectList of DbEntity
	 */
	public function lock($principal, $tableName, $dbEntity, $transfer=false)
	{
		$this->executionSink()->publishStartOperation("lock", $principal);
		try
		{
			if(is_null($principal)) throw new DbAdminServiceException("principal cannot be null", DbAdminServiceException::INVALID_ARGUMENT);
			if(is_null($dbEntity)) throw new DbAdminServiceException("dbEntity cannot be null", DbAdminServiceException::INVALID_ARGUMENT);

			// gets ids
			if($dbEntity instanceof DbEntityList)
			{
				$dbEntityId = $dbEntity->getIds();
				$nEntities = $dbEntity->count();
			}
			elseif($dbEntity instanceof ObjectList)
			{
				$nEntities = 0;
				foreach($dbEntity->getListIterator() as $dbE)
				{
					$dbEntityId[] = $dbE->getId();
					$nEntities++;
				}
			}
			else
			{
				$dbEntityId = $dbEntity->getId();
				$nEntities = 1;
			}
			if($nEntities == 0) throw new DbAdminServiceException("dbEntity cannot be empty", DbAdminServiceException::INVALID_ARGUMENT);

			$mySqlF = $this->getMySqlFacade();
			$dbCS = $this->getDbConnectionSettings($principal);
			$microTime = $principal->getMicroTime();
			
			// lock transfer (gets lock even if owned by someone else)
			if($transfer) {
				$this->assertPrincipalAuthorizedForTransferLock($principal);
				$mySqlF->update($principal,
					$this->getSqlForTransferLock($tableName,
						$principal->getExecutionId(),
						$microTime,
						$dbEntityId), $dbCS);
			}
			// normal locking process (gets lock if unlock or expired)
			else {
				$expiration = $this->getLockExpirationMicrosec();
				// do we acquire lock for first time or is it only a refresh ?
				$returnValue = ($mySqlF->selectOneValue($principal,
					$this->getSqlForCountUnlockedEntities($tableName,
						$microTime,
						$expiration,
						$dbEntityId),
					$dbCS) == $nEntities);
	
				// locks
				if($mySqlF->update($principal,
						$this->getSqlForLock($tableName,
							$principal->getExecutionId(),
							$microTime,
							$expiration,
							$dbEntityId),
						$dbCS) < $nEntities)
				{
					$this->unLock($principal, $tableName, $dbEntity);
					throw new AuthorizationServiceException("DbEntity was already locked", AuthorizationServiceException::OBJECT_IS_LOCKED);
				}
			}
		}
		catch (DbAdminServiceException $dbaE){
			$this->executionSink()->publishEndOperationOnError("lock", $dbaE, $principal);
			throw $dbaE;
		}
		catch (AuthorizationServiceException $asE){
			$this->executionSink()->publishEndOperationOnError("lock", $asE, $principal);
			throw $asE;
		}
		catch(Exception $e)
		{
			$this->executionSink()->publishEndOperationOnError("lock", $e, $principal);
			throw new DbAdminServiceException('',DbAdminServiceException::WRAPPING, $e);
		}
		$this->executionSink()->publishEndOperation("lock", $principal);
		return $returnValue;
	}
	/**
	 * Gets Sql to set lock
	 * tableName : name of table where to lock entities
	 * lockId : technical id identifying lock
	 * microTime : lock timestamp in microsecondes
	 * expiration : lock expiration in microsecondes
	 * dbEntityId : single Id or array of Ids
	 */
	protected function getSqlForLock($tableName, $lockId, $microTime, $expiration, $dbEntityId)
	{
		$sqlB = $this->getMySqlFacade()->getSqlBuilder();
		$sqlB->setTableForUpdate($tableName);

		$sqlB->updateValue('sys_lockMicroTime', $microTime, MySqlQueryBuilder::SQLTYPE_INT);
		$sqlB->updateValue('sys_lockId', $lockId, MySqlQueryBuilder::SQLTYPE_VARCHAR);
		$whereClause = $this->getSqlChunkToTestLockCondition($sqlB, $lockId, $microTime, $expiration)." and ";
		if(is_array($dbEntityId))
		{
			$whereClause .= $sqlB->formatBinExp($this->getIdColumnName($tableName), 'IN', $dbEntityId, MySqlQueryBuilder::SQLTYPE_INT);
		}
		else
		{
			$whereClause .= $sqlB->formatBinExp($this->getIdColumnName($tableName), '=', $dbEntityId, MySqlQueryBuilder::SQLTYPE_INT);
		}
		$sqlB->setWhereClause($whereClause);
		//fput($sqlB->getSql());
		return $sqlB->getSql();
	}
	/**
	 * In this implementation, only the RootPrincipal is authorized to transfer an existing lock to himself
	 */
	protected function assertPrincipalAuthorizedForTransferLock($principal)
	{
		$this->getAuthorizationService()->assertPrincipalIsRoot($principal);
	}
	/**
	 * Gets Sql to transfer the lock to the given ID
	 * tableName : name of table where to lock entities
	 * lockId : technical id identifying lock
	 * microTime : lock timestamp in microsecondes
	 * expiration : lock expiration in microsecondes
	 * dbEntityId : single Id or array of Ids
	 */
	protected function getSqlForTransferLock($tableName, $lockId, $microTime, $dbEntityId)
	{
		$sqlB = $this->getMySqlFacade()->getSqlBuilder();
		$sqlB->setTableForUpdate($tableName);
	
		$sqlB->updateValue('sys_lockMicroTime', $microTime, MySqlQueryBuilder::SQLTYPE_INT);
		$sqlB->updateValue('sys_lockId', $lockId, MySqlQueryBuilder::SQLTYPE_VARCHAR);
		if(is_array($dbEntityId))
		{
			$whereClause = $sqlB->formatBinExp($this->getIdColumnName($tableName), 'IN', $dbEntityId, MySqlQueryBuilder::SQLTYPE_INT);
		}
		else
		{
			$whereClause = $sqlB->formatBinExp($this->getIdColumnName($tableName), '=', $dbEntityId, MySqlQueryBuilder::SQLTYPE_INT);
		}
		$sqlB->setWhereClause($whereClause);
		//fput($sqlB->getSql());
		return $sqlB->getSql();
	}

	/**
	 * Return sql chunk which tests if lock can be acquired
	 * sqlB: a sql builder to use for column formatting
	 */
	protected function getSqlChunkToTestLockCondition($sqlB, $lockId, $microTime, $expiration, $tableAlias='')
	{
		if(is_null($sqlB)) throw new DbAdminServiceException("sqlB cannot be null", DbAdminServiceException::INVALID_ARGUMENT);
		if($tableAlias != '') $tableAlias.= ".";
		$sysLockIdColName = $tableAlias.'sys_lockId';
		$sysLockMicrotimeColName = $tableAlias.'sys_lockMicroTime';
		$sysLockId = $sqlB->formatBinExp($sysLockIdColName, '=', $lockId, MySqlQueryBuilder::SQLTYPE_VARCHAR);
		$notSysLockId = $sqlB->formatBinExp($sysLockIdColName, '!=', $lockId, MySqlQueryBuilder::SQLTYPE_VARCHAR);
		$expiration = $sqlB->formatValue($expiration, MySqlQueryBuilder::SQLTYPE_INT);
		$microTime = $sqlB->formatValue($microTime, MySqlQueryBuilder::SQLTYPE_INT);
		return "($sysLockId or $sysLockIdColName is null or $notSysLockId and ($sysLockMicrotimeColName + $expiration < $microTime))";
	}

	/**
	 * Gets sql to count number of unlocked entities
	 * tableName : name of table where to lock entities
	 * dbEntityId : single Id or array of Ids
	 */
	protected function getSqlForCountUnlockedEntities($tableName, $microTime, $expiration, $dbEntityId)
	{
		$sqlB = $this->getMySqlFacade()->getSqlBuilder();
		$expiration = $sqlB->formatValue($expiration, MySqlQueryBuilder::SQLTYPE_INT);
		$microTime = $sqlB->formatValue($microTime, MySqlQueryBuilder::SQLTYPE_INT);
		$whereClause = "(sys_lockId is null or ((sys_lockMicroTime + $expiration) < $microTime)) and ";
		$idColName = $this->getIdColumnName($tableName);
		if(is_array($dbEntityId))
		{
			$whereClause .= $sqlB->formatBinExp($idColName, 'IN', $dbEntityId, MySqlQueryBuilder::SQLTYPE_INT);
		}
		else
		{
			$whereClause .= $sqlB->formatBinExp($idColName, '=', $dbEntityId, MySqlQueryBuilder::SQLTYPE_INT);
		}
		return "select count($idColName) from $tableName where $whereClause";
	}

	/** 
	 * Gets sql to list locked entities
	 * tableName : name of table where to lock entities
	 * dbEntityId : single Id or array of Ids
	 */
	protected function getSqlForListLockedEntities($tableName, $microTime, $expiration, $dbEntityId)
	{
		$sqlB = $this->getMySqlFacade()->getSqlBuilder();
		$expiration = $sqlB->formatValue($expiration, MySqlQueryBuilder::SQLTYPE_INT);
		$microTime = $sqlB->formatValue($microTime, MySqlQueryBuilder::SQLTYPE_INT);
		$whereClause = "(sys_lockId is not null and (sys_lockMicroTime >= ($microTime - $expiration))) and ";
		$idColName = $this->getIdColumnName($tableName);
		if(is_array($dbEntityId))
		{
			$whereClause .= $sqlB->formatBinExp($idColName, 'IN', $dbEntityId, MySqlQueryBuilder::SQLTYPE_INT);
		}
		else
		{
			$whereClause .= $sqlB->formatBinExp($idColName, '=', $dbEntityId, MySqlQueryBuilder::SQLTYPE_INT);
		}
		return "select $idColName from $tableName where $whereClause";
	}
	
	/**
	 * Returns lock expiration in microsecondes
	 * default to 30min
	 */
	protected function getLockExpirationMicrosec()
	{
		return 30*60*1000000;
	}

	/**
	 * unLocks the dbEntity/s
	 * @param $dbEntity can be either DbEntity or ObjectList of DbEntity
	 */
	public function unLock($principal, $tableName, $dbEntity)
	{
		$this->executionSink()->publishStartOperation("unLock", $principal);
		try
		{
			if(is_null($principal)) throw new DbAdminServiceException("principal cannot be null", DbAdminServiceException::INVALID_ARGUMENT);
			if(is_null($dbEntity)) throw new DbAdminServiceException("dbEntity cannot be null", DbAdminServiceException::INVALID_ARGUMENT);

			// gets ids
			if($dbEntity instanceof DbEntityList)
			{
				$dbEntityId = $dbEntity->getIds();
				$nEntities = $dbEntity->count();
			}
			elseif($dbEntity instanceof ObjectList)
			{
				$nEntities = 0;
				foreach($dbEntity->getListIterator() as $dbE)
				{
					$dbEntityId[] = $dbE->getId();
					$nEntities++;
				}
			}
			else
			{
				$dbEntityId = $dbEntity->getId();
				$nEntities = 1;
			}
			if($nEntities === 0) throw new DbAdminServiceException("dbEntity cannot be empty", DbAdminServiceException::INVALID_ARGUMENT);

			$mySqlF = $this->getMySqlFacade();
			$dbCS = $this->getDbConnectionSettings($principal);
			// unlocks
			$mySqlF->update($principal,
				$this->getSqlForUnLock($tableName,
					$principal->getExecutionId(),
					$dbEntityId),
				$dbCS);
		}
		catch (DbAdminServiceException $dbaE){
			$this->executionSink()->publishEndOperationOnError("unLock", $dbaE, $principal);
			throw $dbaE;
		}
		catch (AuthorizationServiceException $asE){
			$this->executionSink()->publishEndOperationOnError("unLock", $asE, $principal);
			throw $asE;
		}
		catch(Exception $e)
		{
			$this->executionSink()->publishEndOperationOnError("unLock", $e, $principal);
			throw new DbAdminServiceException('',DbAdminServiceException::WRAPPING, $e);
		}
		$this->executionSink()->publishEndOperation("unLock", $principal);
	}
	/**
	 * Gets Sql to unlock
	 * tableName : name of table where to lock entities
	 * lockId : technical id identifying lock
	 * dbEntityId : single Id or array of Ids
	 */
	protected function getSqlForUnLock($tableName, $lockId, $dbEntityId)
	{
		$sqlB = $this->getMySqlFacade()->getSqlBuilder();
		$sqlB->setTableForUpdate($tableName);

		$sqlB->updateValue('sys_lockMicroTime', null, MySqlQueryBuilder::SQLTYPE_INT);
		$sqlB->updateValue('sys_lockId', null, MySqlQueryBuilder::SQLTYPE_VARCHAR);
		$sysLockId = $sqlB->formatBinExp('sys_lockId', '=', $lockId, MySqlQueryBuilder::SQLTYPE_VARCHAR);
		$whereClause = "$sysLockId and ";
		if(is_array($dbEntityId))
		{
			$whereClause .= $sqlB->formatBinExp($this->getIdColumnName($tableName), 'IN', $dbEntityId, MySqlQueryBuilder::SQLTYPE_INT);
		}
		else
		{
			$whereClause .= $sqlB->formatBinExp($this->getIdColumnName($tableName), '=', $dbEntityId, MySqlQueryBuilder::SQLTYPE_INT);
		}
		$sqlB->setWhereClause($whereClause);
		return $sqlB->getSql();
	}

	/**
	 * Clears all locks in table
	 * Only root principal can perform this operation
	 */
	public function clearAllLocks($principal, $tableName)
	{
		$this->executionSink()->publishStartOperation("clearAllLocks", $principal);
		try
		{
			$this->getAuthorizationService()->assertPrincipalIsRoot($principal);

			$mySqlF = $this->getMySqlFacade();
			$dbCS = $this->getDbConnectionSettings($principal);
			// unlocks all
			$mySqlF->update($principal,
				$this->getSqlForClearAllLocks($tableName),
				$dbCS);
		}
		catch (DbAdminServiceException $dbaE){
			$this->executionSink()->publishEndOperationOnError("clearAllLocks", $dbaE, $principal);
			throw $dbaE;
		}
		catch (AuthorizationServiceException $asE){
			$this->executionSink()->publishEndOperationOnError("clearAllLocks", $asE, $principal);
			throw $asE;
		}
		catch(Exception $e)
		{
			$this->executionSink()->publishEndOperationOnError("clearAllLocks", $e, $principal);
			throw new DbAdminServiceException('',DbAdminServiceException::WRAPPING, $e);
		}
		$this->executionSink()->publishEndOperation("clearAllLocks", $principal);
	}
	protected function getSqlForClearAllLocks($tableName)
	{
		$sqlB = $this->getMySqlFacade()->getSqlBuilder();
		$sqlB->setTableForUpdate($tableName);

		$sqlB->updateValue('sys_lockMicroTime', null, MySqlQueryBuilder::SQLTYPE_INT);
		$sqlB->updateValue('sys_lockId', null, MySqlQueryBuilder::SQLTYPE_VARCHAR);
		return $sqlB->getSql();
	}

	// Temp table space
	
	public function createTempTable($principal, $sqlCreationStatement, $cnxSettings, $leaseTime=120) {
		$this->executionSink()->publishStartOperation("createTempTable", $principal);
		try
		{			
			$mySqlF = $this->getMySqlFacade();
			// generates temp table name
			$tempTableName = $this->createTempTableName($principal);
			
			//1. inserts temp table into index
			$nbTries = 0;
			do {
				try {
					$nbTries++;
					$mySqlF->insertOne($principal, 
							$this->getSqlForInsertTempTableIntoIndex($principal, $tempTableName, $leaseTime), 
							$cnxSettings);
				}
				//2. if temp tables index does not exist, then creates index and retries.
				catch(MySqlFacadeException $sqle1) {
					if($sqle1->getMySqlErrorNo() == MySqlFacadeException::MYSQL_ERROR_NO_SUCH_TABLE && $nbTries < 2) {
						$this->createTempTablesIndex($principal, $cnxSettings);
					}
					else throw $sqle1;
				}
			} while($nbTries < 2);
			// 3. creates temp table
			$mySqlF->execDDLCmd($principal, 
					$this->getSqlForCreateTempTable($tempTableName, $sqlCreationStatement), 
					$cnxSettings);
			$returnValue = $tempTableName;
			// 4. cleans expired temp tables
			$this->cleanExpiredTempTables($principal, $cnxSettings);
		}
		catch(DbAdminServiceException $dbaE)
		{
			$this->executionSink()->publishEndOperationOnError("createTempTable", $dbaE, $principal);
			throw $dbaE;
		}
		catch(Exception $e)
		{
			$this->executionSink()->publishEndOperationOnError("createTempTable", $e, $principal);
			throw new DbAdminServiceException('',DbAdminServiceException::WRAPPING, $e);
		}
		$this->executionSink()->publishEndOperation("createTempTable", $principal);
		return $returnValue;
	}
	
	public function dropTempTable($principal, $tableName, $cnxSettings) {
		$this->executionSink()->publishStartOperation("dropTempTable", $principal);
		try
		{
			$mySqlF = $this->getMySqlFacade();
			// 1. drops temp table
			$mySqlF->execDDLCmd($principal, 
					$this->getSqlForDropTempTable($tableName), 
					$cnxSettings);
			// 2. removes temp table from index
			$returnValue = $mySqlF->delete($principal, 
					$this->getSqlForDeleteTempTableFromIndex($principal, $tableName), 
					$cnxSettings);
			$returnValue = ($returnValue > 0);
			// 3. cleans expired temp tables
			$this->cleanExpiredTempTables($principal, $cnxSettings);
		}
		catch(DbAdminServiceException $dbaE)
		{
			$this->executionSink()->publishEndOperationOnError("dropTempTable", $dbaE, $principal);
			throw $dbaE;
		}
		catch(Exception $e)
		{
			$this->executionSink()->publishEndOperationOnError("dropTempTable", $e, $principal);
			throw new DbAdminServiceException('',DbAdminServiceException::WRAPPING, $e);
		}
		$this->executionSink()->publishEndOperation("dropTempTable", $principal);
		return $returnValue;
	}
	
	public function touchTempTable($principal, $tableName, $cnxSettings, $newLeaseTime=null) {
		$this->executionSink()->publishStartOperation("touchTempTable", $principal);
		try
		{
			if($this->getMySqlFacade()->update($principal, 
				$this->getSqlForUpdateTempTableExpirationDate($principal, $tableName, $newLeaseTime),
				$cnxSettings) < 1) throw new DbAdminServiceException("temp table '$tableName' does not exist or is locked for deletion.", DbAdminServiceException::INVALID_ARGUMENT);
		}
		catch(DbAdminServiceException $dbaE)
		{
			$this->executionSink()->publishEndOperationOnError("touchTempTable", $dbaE, $principal);
			throw $dbaE;
		}
		catch(Exception $e)
		{
			$this->executionSink()->publishEndOperationOnError("touchTempTable", $e, $principal);
			throw new DbAdminServiceException('',DbAdminServiceException::WRAPPING, $e);
		}
		$this->executionSink()->publishEndOperation("touchTempTable", $principal);
	}
	
	/**
	 * Creates a temporary table name
	 * @param Principal $principal
	 * @return String
	 */
	protected function createTempTableName($principal) {
		if(!isset($principal)) throw new DbAdminServiceException('principal cannot be null', DbAdminServiceException::INVALID_ARGUMENT);
		return 'tempTable_'.$principal->getMicroTime().$principal->getUserId();
	}
	/**
	 * @param Principal $principal
	 * @param String $tempTableName
	 * @param int $leaseTime the lease time in minutes
	 */
	protected function getSqlForInsertTempTableIntoIndex($principal, $tempTableName, $leaseTime) {
		if(!isset($principal)) throw new DbAdminServiceException('principal cannot be null', DbAdminServiceException::INVALID_ARGUMENT);
		if(!($leaseTime > 0)) throw new DbAdminServiceException('leaseTime should be a positive integer', DbAdminServiceException::INVALID_ARGUMENT);
		$sqlB = $this->getMySqlFacade()->getSqlBuilder();
		$sqlB->setTableForInsert($this->getTempTablesIndexName());
		$sqlB->insertValue('tableName', $tempTableName, MySqlQueryBuilder::SQLTYPE_VARCHAR);
		$sqlB->insertValue('leaseTime', $leaseTime, MySqlQueryBuilder::SQLTYPE_INT);
		$sqlB->insertValue('expirationDate', time()+60*$leaseTime, MySqlQueryBuilder::SQLTYPE_DATETIME);
		$sqlB->insertValue('locked', false, MySqlQueryBuilder::SQLTYPE_BOOLEAN);
		$sqlB->updateSysUser($principal);
		return $sqlB->getSql();
	}
	/**
	 * @param String|Array|ValueList $tempTableName
	 */
	protected function getSqlForDeleteTempTableFromIndex($principal, $tempTableName) {
		$sqlB = $this->getMySqlFacade()->getSqlBuilder();
		$sqlB->setTableForDelete($this->getTempTablesIndexName());
		if(is_array($tempTableName)) {
			$sqlB->setWhereClauseMultipleId('tableName', $tempTableName, MySqlQueryBuilder::SQLTYPE_VARCHAR);
		}
		elseif($tempTableName instanceof ValueList) {
			$sqlB->setWhereClauseMultipleId('tableName', $tempTableName->getListIterator(), MySqlQueryBuilder::SQLTYPE_VARCHAR);
		}
		else $sqlB->setWhereClauseSingleId('tableName', $tempTableName, MySqlQueryBuilder::SQLTYPE_VARCHAR);
		return $sqlB->getSql();
	}
	protected function getSqlForUpdateTempTableExpirationDate($principal, $tempTableName, $newLeaseTime=null) {
		if(!isset($principal)) throw new DbAdminServiceException('principal cannot be null', DbAdminServiceException::INVALID_ARGUMENT);
		$sqlB = $this->getMySqlFacade()->getSqlBuilder();
		$sqlB->setTableForUpdate($this->getTempTablesIndexName());
		if(isset($newLeaseTime)) {
			if(!($newLeaseTime > 0)) throw new DbAdminServiceException('newLeaseTime should be a positive integer or equal to null', DbAdminServiceException::INVALID_ARGUMENT);
			$sqlB->updateValue('leaseTime', $leaseTime, MySqlQueryBuilder::SQLTYPE_INT);
			$sqlB->updateValue('expirationDate', time()+60*$leaseTime, MySqlQueryBuilder::SQLTYPE_DATETIME);
		}
		else {
			$sqlB->updateValue('expirationDate', 'DATE_ADD(NOW(), INTERVAL leaseTime MINUTE)', MySqlQueryBuilder::SQLTYPE_SQLEXP);
		}
		$sqlB->updateSysUser($principal);
		$sqlB->setWhereClause($sqlB->formatBinExp('tableName', '=', $tempTableName, MySqlQueryBuilder::SQLTYPE_VARCHAR).
			" AND ".$sqlB->formatBinExp('locked', '!=', true, MySqlQueryBuilder::SQLTYPE_BOOLEAN));
		return $sqlB->getSql();
	}
	/**
	 * @param String $sqlCreationStatement part of the SQL table creation statement,
	 * defining all the creation details such as columns, constraints, indexes and
	 * optional select statement used to populate the table.
	 * Do not provide any 'CREATE TABLE tbl_name'.
	 */
	protected function getSqlForCreateTempTable($tempTableName, $sqlCreationStatement) {
		return 'CREATE TABLE `'.$tempTableName.'` '.$sqlCreationStatement;
	}
	/**
	 * @param String|Array|ValueList $tempTableName
	 */
	protected function getSqlForDropTempTable($tempTableName) {
		if(is_array($tempTableName)) $tempTableName = implode(', `', $tempTableName);
		elseif($tempTableName instanceof ValueList) $tempTableName = implode(', `', $tempTableName->getListIterator());
		return 'DROP TABLE IF EXISTS `'.$tempTableName.'`';
	}
	/**
	 * Creates the temp table index db table
	 * @param Principal $principal
	 * @param DbConnectionSettings $cnxSettings the connection used to connect to the db
	 * hosting the temp tables and index.
	 */
	protected function createTempTablesIndex($principal, $cnxSettings)
	{
		$this->executionSink()->publishStartOperation("createTempTablesIndex", $principal);
		try
		{			
			$this->getMySqlFacade()->execDDLCmd($principal, 
					$this->getSqlForCreateTempTablesIndex(), 
					$cnxSettings);
		}		
		catch(Exception $e)
		{
			$this->executionSink()->publishEndOperationOnError("createTempTablesIndex", $e, $principal);
			throw $e;
		}
		$this->executionSink()->publishEndOperation("createTempTablesIndex", $principal);
	}	
	protected function getSqlForCreateTempTablesIndex() {
		return "CREATE TABLE IF NOT EXISTS `".$this->getTempTablesIndexName()."` (
  `id_TempTablesIndex` int(11) NOT NULL AUTO_INCREMENT,
  `tableName` varchar(64) COLLATE utf8_unicode_ci NOT NULL,
  `leaseTime` int(4) DEFAULT NULL,
  `expirationDate` DATETIME DEFAULT NULL,
  `locked` tinyint(1) DEFAULT NULL,
  `sys_creationUser` int(10) unsigned DEFAULT NULL,
  `sys_creationUsername` varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL,
  `sys_creationDate` int(10) unsigned DEFAULT NULL,
  `sys_user` int(10) unsigned DEFAULT NULL,
  `sys_username` varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL,
  `sys_date` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`id_TempTablesIndex`),
  UNIQUE KEY `tableName` (`tableName`), 
  KEY `sys_creationUser` (`sys_creationUser`),
  KEY `sys_creationDate` (`sys_creationDate`),
  KEY `sys_user` (`sys_user`),
  KEY `sys_date` (`sys_date`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
	}
	protected function getTempTablesIndexName() {
		return 'TempTablesIndex';
	}
	
	/**
	 * Deletes all expired temp tables and removes entries from the index
	 * @param Principal $principal
	 * @param DbConnectionSettings $cnxSettings the connection used to connect to the db
	 * hosting the temp tables and index.
	 */
	protected function cleanExpiredTempTables($principal, $cnxSettings) {
		$this->executionSink()->publishStartOperation("cleanExpiredTempTables", $principal);
		$expiredTempTables = null;
		$vlMapper = null;
		try
		{			
			$mySqlF = $this->getMySqlFacade();
			// 1. locks expired temp tables
			$mySqlF->update($principal, 
					$this->getSqlForLockExpiredTempTables(), 
					$cnxSettings);
			// 2. selects expired temp tables
			$expiredTempTables = $this->getValueListArrayMapper();
			$vlMapper = $this->getValueListMapper($expiredTempTables, 'tableName');
			if($mySqlF->selectAll($principal, 
					$this->getSqlForSelectAllLockedTempTables(), 
					$cnxSettings, 
					$vlMapper) > 0) {
				$vlMapper->freeMemory();
				// 3. drops expired temp tables
				$mySqlF->execDDLCmd($principal, 
						$this->getSqlForDropTempTable($expiredTempTables), 
						$cnxSettings);
				// 4. removes expired temp tables from index
				$mySqlF->delete($principal, 
						$this->getSqlForDeleteTempTableFromIndex($principal, $expiredTempTables), 
						$cnxSettings);				
				$expiredTempTables->freeMemory();
			}			
		}		
		catch(Exception $e)
		{
			if(isset($vlMapper)) $vlMapper->freeMemory();
			if(isset($expiredTempTables)) $expiredTempTables->freeMemory();
			$this->executionSink()->publishEndOperationOnError("cleanExpiredTempTables", $e, $principal);
			throw $e;
		}
		$this->executionSink()->publishEndOperation("cleanExpiredTempTables", $principal);
	}
	protected function getSqlForLockExpiredTempTables() {
		$sqlB = $this->getMySqlFacade()->getSqlBuilder();
		$sqlB->setTableForUpdate($this->getTempTablesIndexName());
		$sqlB->updateValue('locked', true, MySqlQueryBuilder::SQLTYPE_BOOLEAN);
		$sqlB->setWhereClause('expirationDate IS NOT NULL AND expirationDate < NOW()');
		return $sqlB->getSql();
	}
	protected function getSqlForSelectAllLockedTempTables() {
		return 'SELECT tableName FROM '.$this->getTempTablesIndexName().' WHERE locked = 1';
	}	
	
	// Cache management

	private function getCachedDbConnectionSettings($client)
	{
		if(!isset($this->dbConnectionSettingsCache)) return null;
		if(is_null($client)) return null;
		$returnValue = $this->dbConnectionSettingsCache[$client->getClientName()];
		if(!isset($returnValue)) return null;
		$this->debugLogger()->write('found DB connection settings for client '.$client->getClientName());
		return $returnValue;
	}

	private function cacheDbConnectionSettings($client, $dbConnectionSettings)
	{
		if(is_null($client)) return;
		$this->dbConnectionSettingsCache[$client->getClientName()] = $dbConnectionSettings;
		$this->debugLogger()->write('stores DB connection settings for client '.$client->getClientName());
	}
}



