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

/* wigii GroupAdminService implementation
 * Created by CWE on 2 juin 09
 */
class GroupAdminServiceImpl implements GroupAdminService
{
	private $_debugLogger;
	private $_executionSink;
	private $moduleAS;
	private $authoS;
	private $mysqlF;
	private $dbAS;
	private $wigiiNamespaceAS;
	private $userASImpl;
	private $elementService;
	private $groupPTreeMapper;
	private $groupListMapper;
	private $groupPListMapper;
	private $valueListMapper;
	private $userRPListMapper;
	private $fslForGroupWithoutDetail;
	private $fslForGroup;
	private $sqlBuilderForInsertLeafInGroupGroup;
	private $whereClauseBuilderForSelectGroups;
	private $inGroupLogExp2GroupLogExpConverter;
	private $groupsGroupsBuilder;

	private function debugLogger()
	{
		if(!isset($this->_debugLogger))
		{
			$this->_debugLogger = DebugLogger::getInstance("GroupAdminServiceImpl");
		}
		return $this->_debugLogger;
	}
	private function executionSink()
	{
		if(!isset($this->_executionSink))
		{
			$this->_executionSink = ExecutionSink::getInstance("GroupAdminServiceImpl");
		}
		return $this->_executionSink;
	}
	public function __construct()
	{
		$this->debugLogger()->write("creating instance");
	}


	// Dependency injection
	public function setArgValidator($argValidator)
	{
		$this->argVal = $argValidator;
	}
	protected function getArgValidator()
	{
		// autowired
		if(!isset($this->argVal))
		{
			$this->argVal = TechnicalServiceProvider::getArgValidator();
		}
		return $this->argVal;
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
	public function setAuthorizationService($authorizationService)
	{
		$this->authoS = $authorizationService;
	}
	public function getAuthorizationService()
	{
		// autowired
		if(!isset($this->authoS))
		{
			$this->authoS = ServiceProvider::getAuthorizationService();
		}
		return $this->authoS;
	}
	public function setGroupPTreeMapper($groupPTreeMapper)
	{
		$this->groupPTreeMapper = $groupPTreeMapper;
	}
	protected function getGroupPTreeMapper($principal, $pRights = null)
	{
		// autowired
		if(!isset($this->groupPTreeMapper))
		{
			$this->groupPTreeMapper = GroupPTreeMapper::createInstance($this, $principal, $pRights);
		}
		else
		{
			if($this->groupPTreeMapper->isLockedForUse()) {
				return GroupPTreeMapper::createInstance($this, $principal, $pRights);
			}
			else $this->groupPTreeMapper->reset($principal, $pRights);
		}
		return $this->groupPTreeMapper;
	}
	public function setGroupListMapper($groupListMapper)
	{
		$this->groupListMapper = $groupListMapper;
	}
	protected function getGroupListMapper($principal, $groupList, $withDetail=true)
	{
		// autowired
		if(!isset($this->groupListMapper))
		{
			$this->groupListMapper = GroupListMapper::createInstance($this, $principal, $groupList, $withDetail);
		}
		else
		{
			if($this->groupListMapper->isLockedForUse()) {
				return GroupListMapper::createInstance($this, $principal, $groupList, $withDetail);
			}
			else $this->groupListMapper->reset($principal, $groupList, $withDetail);
		}
		return $this->groupListMapper;
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
			$this->groupPListMapper = GroupPListMapper::createInstance($this, $principal, $groupPList, $pRights);
		}
		else
		{
			if($this->groupPListMapper->isLockedForUse()) {
				return GroupPListMapper::createInstance($this, $principal, $groupPList, $pRights);
			}
			else $this->groupPListMapper->reset($principal, $groupPList, $pRights);
		}
		return $this->groupPListMapper;
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
	public function setUserRPListMapper($userRPListMapper)
	{
		$this->userRPListMapper = $userRPListMapper;
	}
	protected function getUserRPListMapper($principal, $groupId, $userRPList, $pRights)
	{
		// autowired
		if(!isset($this->userRPListMapper))
		{
			$this->userRPListMapper = UserRPListMapper::createInstance($this->getUserAdminServiceImpl(), $principal, $groupId, $userRPList, $pRights);
		}
		else
		{
			if($this->userRPListMapper->isLockedForUse()) {
				return UserRPListMapper::createInstance($this->getUserAdminServiceImpl(), $principal, $groupId, $userRPList, $pRights);	
			}
			else $this->userRPListMapper->reset($principal, $groupId, $userRPList, $pRights);
		}
		return $this->userRPListMapper;
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
	public function setUserAdminServiceImpl($userAdminServiceImpl)
	{
		if(isset($userAdminServiceImpl) && !($userAdminServiceImpl instanceof UserAdminServiceImpl))
		{
			throw new GroupAdminServiceException("GroupAdminServiceImpl depends on UserAdminServiceImpl or a subclass", GroupAdminServiceException::INVALID_ARGUMENT);
		}
		$this->userAdminServiceImpl = $userAdminServiceImpl;
	}
	protected function getUserAdminServiceImpl()
	{
		// autowired
		if(!isset($this->userAdminServiceImpl))
		{
			$this->setUserAdminServiceImpl(ServiceProvider::getUserAdminService());
		}
		return $this->userAdminServiceImpl;
	}
	public function setElementService($elementService)
	{
		$this->elementService = $elementService;
	}
	protected function getElementService()
	{
		// autowired
		if(!isset($this->elementService))
		{
			$this->elementService = ServiceProvider::getElementService();
		}
		return $this->elementService;
	}
	
	/**
	 * get listFilters to fetch correct informations in Admin module
	 */
	public function getListFilterForGroupList(){
		
		$listFilter = ListFilter::createInstance();
		
		return $listFilter;
	}
	
	public function getListFilterForSubGroupsInElementList($module, $id_group_parent=null){
		
		$logExp = LogExp::createAndExp();
		if($id_group_parent!=null){
			if(!is_array($id_group_parent)) $id_group_parent = array($id_group_parent);
			$gp = LogExp::createInExp(FieldSelector::createInstance("id_group_parent"), $id_group_parent);
		}
		$m = LogExp::createEqualExp(FieldSelector::createInstance("module"), $module->getModuleName());
		
		$logExp->addOperand($gp);
		$logExp->addOperand($m);
		
		$lf = $this->getListFilterForGroupList();
		$lf->setFieldSelectorLogExp($logExp);
		return $lf;
	}
	public function getListFilterForGroupsInElementList($module, $groupIds=null){
		
		$logExp = LogExp::createAndExp();
		if($groupIds!=null){
			if(!is_array($groupIds)) $groupIds = array($groupIds);
			$gp = LogExp::createInExp(FieldSelector::createInstance("id"), $groupIds);
		}
		$m = LogExp::createEqualExp(FieldSelector::createInstance("module"), $module->getModuleName());
		
		$logExp->addOperand($gp);
		$logExp->addOperand($m);
		
		$lf = $this->getListFilterForGroupList();
		$lf->setFieldSelectorLogExp($logExp);
		return $lf;
	}
	
	/**
	 * get listFilter instance with fieldSelectorList limited without detail
	 * @param $groupIds = null : array, if defined add a fieldLogExp on the defined ids
	 */
	public function getListFilterForSelectGroupWithoutDetail($groupIds=null){
		$lf = $this->getListFilterForGroupList();
		$lf->setFieldSelectorList($this->getFieldSelectorListForGroupWithoutDetail());
		if($groupIds != null){
			$lf->setFieldSelectorLogExp(LogExp::createInExp(FieldSelector::createInstance("id"), $groupIds));
		}
		return $lf;
	}
	/**
	 * get listFilter instance with fieldSelectorList limited without detail and fieldSelectorLogExp to get only root groups in a module
	 * @param $groupIds = null : array, if defined add a fieldLogExp on the defined ids
	 */
	public function getListFilterForSelectRootGroupsWithoutDetail($module){
		$lf = $this->getListFilterForGroupList();
		$lf->setFieldSelectorList($this->getFieldSelectorListForGroupWithoutDetail());
		$logExp = LogExp::createAndExp();
		$gp = LogExp::createEqualExp(FieldSelector::createInstance("id_group_parent"), null);
		$m = LogExp::createEqualExp(FieldSelector::createInstance("module"), $module->getModuleName());
		$logExp->addOperand($gp);
		$logExp->addOperand($m);
		$lf->setFieldSelectorLogExp($logExp);
		return $lf;
	}
	
	
	public function setFieldSelectorListForGroupWithoutDetail($fieldSelectorList)
	{
		$this->fslForGroupWithoutDetail = $fieldSelectorList;
	}
	public function getFieldSelectorListForGroupWithoutDetail()
	{
		// autowired
		if(!isset($this->fslForGroupWithoutDetail))
		{
			$this->fslForGroupWithoutDetail = FieldSelectorListArrayImpl::createInstance();
			$this->fillFieldSelectorListForGroupWithoutDetail($this->fslForGroupWithoutDetail);
		}
		return $this->fslForGroupWithoutDetail;
	}
	protected function fillFieldSelectorListForGroupWithoutDetail($fieldSelectorList)
	{
		$fieldSelectorList->addFieldSelector('id');
		$fieldSelectorList->addFieldSelector('groupname');
		$fieldSelectorList->addFieldSelector('module');
		$fieldSelectorList->addFieldSelector('wigiiNamespace');
		$fieldSelectorList->addFieldSelector('id_group_parent');
		$fieldSelectorList->addFieldSelector('sys_date');
		$fieldSelectorList->addFieldSelector('sys_creationDate');
		$fieldSelectorList->addFieldSelector('sys_user');
		$fieldSelectorList->addFieldSelector('sys_username');
		$fieldSelectorList->addFieldSelector('sys_creationUser');
		$fieldSelectorList->addFieldSelector('sys_creationUsername');
	}
	public function setFieldSelectorListForGroup($fieldSelectorList)
	{
		$this->fslForGroup = $fieldSelectorList;
	}
	public function getFieldSelectorListForGroup()
	{
		// autowired
		if(!isset($this->fslForGroup))
		{
			$this->fslForGroup = FieldSelectorListArrayImpl::createInstance();
			$this->fillFieldSelectorListForGroup($this->fslForGroup);
		}
		return $this->fslForGroup;
	}
	protected function fillFieldSelectorListForGroup($fieldSelectorList)
	{
		$this->fillFieldSelectorListForGroupWithoutDetail($fieldSelectorList);
		$fieldSelectorList->addFieldSelector('description');
		$fieldSelectorList->addFieldSelector('portal');
		$fieldSelectorList->addFieldSelector('htmlContent');
		$fieldSelectorList->addFieldSelector('xmlPublish');
		$fieldSelectorList->addFieldSelector('subscription');
		$fieldSelectorList->addFieldSelector('emailNotification');
	}
	protected function getGroupsGroupsBuilder()
	{
		// autowired
		if(!isset($this->groupsGroupsBuilder))
		{
			$this->groupsGroupsBuilder = GroupsGroupsBuilder::createInstance($this);
		}
		else $this->groupsGroupsBuilder->reset();
		return $this->groupsGroupsBuilder;
	}
	public function setGroupsGroupsBuilder($groupsGroupsBuilder)
	{
		$this->groupsGroupsBuilder = $groupsGroupsBuilder;
	}

	// Service implementation

	public function persistGroup($principal, $group, $fieldSelectorList=null)
	{
		$this->executionSink()->publishStartOperation("persistGroup", $principal);
		try
		{
			if(is_null($group)) return 0;
			$isNew = $group->isNew();

			// reads parent group
			$parentGroupId = $group->getGroupParentId();
			if(isset($parentGroupId))
			{
				$parentGroupP = $this->getGroup($principal, $parentGroupId, $this->getFieldSelectorListForGroupWithoutDetail());
			}
			else $parentGroupP = null;

			// if group already exists in database, first reads it
			$origGroupP = null; $origPRights = null;
			if(!$isNew)
			{
				$origGroupP = $this->getGroup($principal, $group->getId());
				if(!is_null($origGroupP))
				{
					$origPRights = $origGroupP->getRights();
					$origGroup = $origGroupP->getGroup();
					$parentGroupChanged = ($parentGroupId != $origGroup->getGroupParentId());
					if($parentGroupChanged){
						$origParentGroupId = $origGroup->getGroupParentId();
						if(isset($origParentGroupId))
						{
							$origParentGroupP = $this->getGroup($principal, $origParentGroupId, $this->getFieldSelectorListForGroupWithoutDetail());
						}
						else $origParentGroupP = null;
					}
				}
				else $parentGroupChanged = true;
			}
			else $parentGroupChanged = true;


			// checks authorization
			$this->assertPrincipalAuthorizedForPersistGroup($principal, $group, $origGroupP, $parentGroupP, $parentGroupChanged, $origParentGroupP, $group->getGroupName());

			// validates group
			$this->validateGroup($principal, $group, $origGroupP, $parentGroupP, $parentGroupChanged, $origParentGroupP, $fieldSelectorList);

			// persists group
			$dbAS = $this->getDbAdminService();
			$dbCS = $dbAS->getDbConnectionSettings($principal);
			$mySqlF = $this->getMySqlFacade();
			// if isNew then insert
			$returnValue = 0;
			if($isNew)
			{
				// inserts group
				try {
					$group->setId(
						$mySqlF->insertOne($principal,
							$this->getSqlForInsertGroup($principal, $group, true),
							$dbCS)
						//changed the 4 October 2011, the groupname is no more unique per wigiiNamespace
//						$mySqlF->insertIfUnique($principal,
//							$this->getSqlForInsertGroup($principal, $group, true),
//							$this->getSqlForCheckGroupUnique($group),
//							$dbCS)
					);
				} catch(MySqlFacadeException $e) {
					if($e->getCode() == MySqlFacadeException::MYSQL_NOTUNIQUE){
						throw new GroupAdminServiceException("groupname already used in wigiiNamespace ".$group->getWigiiNamespace()->getWigiiNamespaceName(), GroupAdminServiceException::GROUPNAME_ALREADY_USED_IN_NAMESPACE);
					}
					throw $e;
				}
				try
				{
					// updates table Groups_Groups if parent group exists
					if(isset($parentGroupP))
					{
						$mySqlF->insertMultiple($principal,
							$this->getSqlForInsertLeafInGroupGroup($principal, $group),
							$dbCS);
					}

					// inserts principal right
					$mySqlF->insertOne($principal,
						$this->getSqlForInsertUserGroupRight($this->getUGROnGroupCreation($principal, $group)),
						$dbCS);
				}
				// if insertion error, then deletes first created group and throws exception
				catch(Exception $e)
				{
					// unlocks group
					$this->unLock($principal, $group);
					// deletes group
					try
					{
						$dbAS->deleteRows($principal,
							$this->getSqlTableNameForDeleteGroup(),
							$this->getSqlWhereClauseForDeleteGroup($group),
							$dbCS);
					}
					catch(Exception $e1){/* does nothing, it will be automatically deleted on next delete */}
					throw $e;
				}
				// unlocks group
				$this->unLock($principal, $group);
				$returnValue = 1;
			}
			// invalid state
			elseif(is_null($origGroupP))
			{
				throw new GroupAdminServiceException('authorized to update group '.$group->getId().' which does not exist in database');
			}
			// else if groupname, module or wigiiNamespace changed, checks for unicity before updating
			elseif(($origGroup->getGroupName() != $group->getGroupName()) ||
					($origGroup->getModule() !== $group->getModule()) ||
					($origGroup->getWigiiNamespace() !== $group->getWigiiNamespace()))
			{
				// acquires lock
				$shouldUnlock = $this->lock($principal, $group);
				try
				{
					// performs update
					try
					{
						$returnValue = $mySqlF->update($principal,
							$this->getSqlForUpdateGroupIgnoreParent($principal, $group, $origGroup, $fieldSelectorList),
							$dbCS
						);
						//changed the 4 October 2011, the groupname is no more unique per wigiiNamespace
//						$returnValue = $mySqlF->updateIfUnique($principal,
//							$this->getSqlForUpdateGroupIgnoreParent($principal, $group, $origGroup, $fieldSelectorList),
//							$this->getSqlForCheckGroupUnique($group),
//							$dbCS
//						);
					} catch(MySqlFacadeException $e) {
						if($e->getCode() == MySqlFacadeException::MYSQL_NOTUNIQUE){
							throw new GroupAdminServiceException("groupname already used in wigiiNamespace ".$group->getWigiiNamespace()->getWigiiNamespaceName(), GroupAdminServiceException::GROUPNAME_ALREADY_USED_IN_NAMESPACE);
						}
						throw $e;
					}
					if($parentGroupChanged) $this->changeParentGroup($principal, $group, $origGroupP, $mySqlF, $dbCS);
				}
				// releases lock
				catch(Exception $ne) {if($shouldUnlock) $this->unLock($principal, $group); throw $ne;}
				if($shouldUnlock) $this->unLock($principal, $group);
			}
			// else updates all fields minus groupname, module and wigiiNamespace
			else
			{
				// acquires lock
				$shouldUnlock = $this->lock($principal, $group);
				try
				{
					// performs update
					$returnValue = $mySqlF->update($principal,
						$this->getSqlForUpdateGroupIgnoreParent($principal, $group, $origGroup, $fieldSelectorList),
						$dbCS
					);
					if($parentGroupChanged) $this->changeParentGroup($principal, $group, $origGroupP, $mySqlF, $dbCS);
				}
				// releases lock
				catch(Exception $ne) {if($shouldUnlock) $this->unLock($principal, $group); throw $ne;}
				if($shouldUnlock) $this->unLock($principal, $group);
			}
		}
		catch (GroupAdminServiceException $gaE){
			$this->executionSink()->publishEndOperationOnError("persistGroup", $gaE, $principal);
			throw $gaE;
		}
		catch (AuthorizationServiceException $asE){
			$this->executionSink()->publishEndOperationOnError("persistGroup", $asE, $principal);
			throw $asE;
		}
		catch(Exception $e)
		{
			$this->executionSink()->publishEndOperationOnError("persistGroup", $e, $principal);
			throw new GroupAdminServiceException('',GroupAdminServiceException::WRAPPING, $e);
		}
		$this->executionSink()->publishEndOperation("persistGroup", $principal);
		return $returnValue;
	}
	private function changeParentGroup($principal, $group, $origGroupP, $mySqlF, $dbCS)
	{
		$origGroup = $origGroupP->getGroup();

		// acquires lock
		$shouldUnlock = $this->lock($principal, $group);
		try
		{
			// inserts UGR
			if(!is_null($origGroupP->getRights()))
			{
				$ugr = $this->createUGRFromGroupP($principal, $origGroupP);
				$mySqlF->update($principal,
					$this->getSqlForUpdateUserGroupRight($ugr),
					$dbCS);
			}

			// disconnects group from its old parent in Groups_Groups
			if(!is_null($origGroup->getGroupParentId()))
			{
				$mySqlF->delete($principal,
					$this->getSqlToDisconnectGroupFromParentInGroupGroup($principal, $origGroup),
					$dbCS);
			}

			// connects group to its new parent Groups_Groups
			if(!is_null($group->getGroupParentId()))
			{
				$mySqlF->insertMultiple($principal,
					$this->getSqlToConnectGroupToParentInGroupGroup($principal, $group),
					$dbCS);
			}

			// updates parentId
			$mySqlF->update($principal,
				$this->getSqlForUpdateGroupParent($principal, $group, $origGroup),
				$dbCS);
		}
		// releases lock
		catch(Exception $ne) {if($shouldUnlock) $this->unLock($principal, $group); throw $ne;}
		if($shouldUnlock) $this->unLock($principal, $group);
	}
	protected function assertPrincipalAuthorizedForPersistGroup($principal, $group, $origGroupP, $parentGroupP, $parentGroupChanged, $origParentGroupP, $groupErrorInfo='')
	{
		if(is_null($principal)) throw new GroupAdminServiceException('principal can not be null', GroupAdminServiceException::INVALID_ARGUMENT);
		
		$module = $group->getModule();
		
		$autoS = $this->getAuthorizationService();
		// checks general authorization
		$autoS->assertPrincipalAuthorized($principal, "GroupAdminService", "persistGroup");
		// check specific rights
		// new user/role -> admin creator or user creator
		if($origGroupP==null)
		{
			if($principal->getGroupCreator($module)==null)
			{
				$autoS->fail($principal, 'is not group creator, therefore can not insert new group');
			}
			if($parentGroupP == null && $principal->getRootGroupCreator($module)==null){
				$autoS->fail($principal, 'is not root group creator, therefore can not insert new root group');
			}
		}
		// existing user/role -> (admin creator or user creator or user manager in wigiiNamespace) and principal owns user
		else
		{
			$origPRights = $origGroupP->getRights();
			if(is_null($origPRights) ||
				!$origPRights->canModify() ||
				$principal->getGroupCreator($module)==null
			)
			{
				$autoS->fail($principal, "has no right to modify the group $groupErrorInfo");
			}
			//if group was root group, need RootGroupCreator to change it
			if($origGroupP->getGroup()->getGroupParentId()==0 && $principal->getRootGroupCreator($module)==null){
				$autoS->fail($principal, "cannot change root group if not rootGroupCreator $groupErrorInfo");
			}
		}
		//if parent group defined, then parentGroup should have X rights as well (to prevent changing structure)
		if($parentGroupChanged && $parentGroupP!=null && ($parentGroupP->getRights()==null || !$parentGroupP->getRights()->canModify())){
			$autoS->fail($principal, "has no X right on parent group ".$parentGroupP->getGroup()->getGroupName());
		} 
		//if orig parent group defined, then orig parentGroup should have X rights as well (if the parent group changed to prevent changing structure)
		if($parentGroupChanged && $origParentGroupP!=null && ($origParentGroupP->getRights()==null || !$origParentGroupP->getRights()->canModify())){
			$autoS->fail($principal, "has no X right on orig parent group ".$origParentGroupP->getGroup()->getGroupName());
		} 
		//this test is no more useful because of the one above. This test is usefull if we want to let
		//any X group being modified even if no rights on parent, but still want to check that if the parent is changed
		//that it is possible (need x right on parent then)
//		if($parentGroupP!=null && $parentGroupChanged && ($parentGroupP->getRights()==null || !$parentGroupP->getRights()->canModify())){
//			$autoS->fail($principal, "has no X right on parent group ".$parentGroupP->getGroup()->getGroupName());
//		}
	}
	protected function validateGroup($principal, $group, $origGroupP, $parentGroupP, $parentGroupChanged, $origParentGroupP, $fieldSelectorList=null)
	{
		$val = $this->getArgValidator();
		//check wigiiNamespace and client
		if($fieldSelectorList == null || $fieldSelectorList->containsFieldSelector("wigiiNamespace")){
			$val->assert($principal->getWigiiNamespace()->getClient() == $group->getWigiiNamespace()->getClient(), 'cannot create a group for an other client: '.$group->getWigiiNamespace()->getClient()->getClientName().' than Principal client '.$principal->getWigiiNamespace()->getClient()->getClientName().'.');
			if(!$principal->isWigiiNamespaceCreator()){
//				eput($user->getWigiiNamespace()->displayDebug());
//				eput($principal->getWigiiNamespace()->displayDebug());
				if($origGroupP != null){
					if($parentGroupP!=null){
						$val->assert($parentGroupP->getGroup()->getWigiiNamespace() == $group->getWigiiNamespace(), 'Principal cannot create a group in an other wigiiNamespace : '.$group->getWigiiNamespace()->getWigiiNamespaceName(). ' than parent group.');
					} else {
						if($origGroupP->getGroup()->getGroupParentId()==null){
							$val->assert($origGroupP->getGroup()->getWigiiNamespace() == $group->getWigiiNamespace(), 'Principal cannot change the wigiiNamespace of an existing group in: '.$group->getWigiiNamespace()->getWigiiNamespaceName());
						} else {
							$val->assert($principal->getWigiiNamespace() == $group->getWigiiNamespace(), 'Principal cannot move a group to root without changing the wigiiNamespace to the principal one. Current group wigiiNamespace: '.$group->getWigiiNamespace()->getWigiiNamespaceName());
						}
					}
				} else {
					if($parentGroupP!=null){
						$val->assert($parentGroupP->getGroup()->getWigiiNamespace() == $group->getWigiiNamespace(), 'Principal cannot create a group in an other wigiiNamespace : '.$group->getWigiiNamespace()->getWigiiNamespaceName(). ' than parent group.');
					} else {
						$val->assert($principal->getWigiiNamespace() == $group->getWigiiNamespace(), 'Principal cannot create a group in an other wigiiNamespace: '.$group->getWigiiNamespace()->getWigiiNamespaceName());
					}
				}
			}
		}
		
		//no validation is done on group activities
		
		//check that group parent is not = to current group
		if($parentGroupP!=null && $group->getGroupParentId() == $group->getId()){
			throw new GroupAdminServiceException("Parent group circularity", GroupAdminServiceException::INVALID_ARGUMENT);
		}
		
		// checks for parent circularity if parent has changed
		if($parentGroupChanged && isset($parentGroupP))
		{
			if(!is_null($this->getMySqlFacade()->selectFirst($principal,
				$this->getSqlForCheckParentGroupCircularity($group),
				$this->getDbAdminService()->getDbConnectionSettings($principal))))
				throw new GroupAdminServiceException("Parent group circularity", GroupAdminServiceException::INVALID_ARGUMENT);
		}
	}
	/**
	 * if lock then locks inserted group
	 */
	protected function getSqlForInsertGroup($principal, $group, $lock)
	{
		$fieldSelectorList = $this->getFieldSelectorListForGroup();
		$mas = $this->getModuleAdminService();

		$sqlB = $this->getSqlWhereClauseBuilderForSelectGroups();
		$sqlB->setTableForInsert('Groups');

		foreach($fieldSelectorList->getListIterator() as $fs)
		{
			$val = $group->getAttribute($fs);
			$doPersist=true;
			switch($fs->getFieldName())
			{
				case 'wigiiNamespace' :
					if(is_object($val)) $val = $val->getWigiiNamespaceName();
					break;
				case 'module':
					if(is_object($val)) $val = $val->getModuleName();
					break;
				case 'id':
				case 'sys_date':
				case 'sys_creationDate':
				case 'sys_user':
				case 'sys_username':
				case 'sys_creationUser':
				case 'sys_creationUsername':
				case 'sys_lockMicroTime':
				case 'sys_lockId':
					$doPersist=false;
					break;
			}
			if($doPersist)
			{
				$sqlB->insertValue($sqlB->getSqlColumnNameForFieldSelector($fs), $val, $sqlB->getSqlDataTypeForFieldSelector($fs));
			}
		}
		// forces sys_date and sys_user
		$sqlB->updateSysUser($principal);
		if($lock) $sqlB->lock($principal);

		return $sqlB->getSql();
	}
	/**
	 * If group is unique then this query does not return any rows.
	 * DEPRECATED since 4 October 2011, groupname is no more unique per wigiiNamespace
	 */
	protected function getSqlForCheckGroupUnique($group)
	{
		throw new GroupAdminServiceException('getSqlForCheckGroupUnique is deprecated since 4 October 2011, groupname is no more unique per wigiiNamespace', GroupAdminServiceException::DEPRECATED);
		if(is_null($group)) throw new GroupAdminServiceException('group can not be null', GroupAdminServiceException::INVALID_ARGUMENT);
		$sqlB = $this->getMySqlFacade()->getSqlBuilder();
		$id_group = $sqlB->formatBinExp('G.id_group', '!=', $group->getId(), MySqlQueryBuilder::SQLTYPE_INT);
		$groupname = $sqlB->formatBinExp('G.groupname', '=', $group->getGroupName(), MySqlQueryBuilder::SQLTYPE_VARCHAR);
		$wigiiNamespace = $group->getWigiiNamespace(); if(is_object($wigiiNamespace)) $wigiiNamespace = $wigiiNamespace->getWigiiNamespaceName();
		$wigiiNamespace = $sqlB->formatBinExp('G.wigiiNamespace', '=', $wigiiNamespace, MySqlQueryBuilder::SQLTYPE_VARCHAR);
		$module = $group->getModule(); if(is_object($module)) $module = $module->getModuleName();
		$module = $sqlB->formatBinExp('G.modulename', '=', $module, MySqlQueryBuilder::SQLTYPE_VARCHAR);
		return "select G.id_group from Groups as G where $id_group and $groupname and $module and $wigiiNamespace";
	}
	/**
	 * updates group, ignores id_group_parent field
	 * if lock then locks updated group
	 */
	protected function getSqlForUpdateGroupIgnoreParent($principal, $group, $origGroup, $fieldSelectorList=null)
	{
		if(is_null($origGroup)) throw new GroupAdminServiceException('original group can not be null', GroupAdminServiceException::INVALID_ARGUMENT);

		if(is_null($fieldSelectorList)) $fieldSelectorList = $this->getFieldSelectorListForGroup();
		$mas = $this->getModuleAdminService();

		$sqlB = $this->getSqlWhereClauseBuilderForSelectGroups();
		$sqlB->setTableForUpdate('Groups');

		foreach($fieldSelectorList->getListIterator() as $fs)
		{
			$val = $group->getAttribute($fs);
			$origVal = $origGroup->getAttribute($fs);
			$doPersist=true;
			switch($fs->getFieldName())
			{
				case 'wigiiNamespace' :
					if(is_object($val)) $val = $val->getWigiiNamespaceName();
					if(is_object($origVal)) $origVal = $origVal->getWigiiNamespaceName();
					break;
				case 'module':
					if(is_object($val)) $val = $val->getModuleName();
					if(is_object($origVal)) $origVal = $origVal->getModuleName();
					break;
				case 'id':
				case 'id_group_parent':
				case 'sys_date':
				case 'sys_creationDate':
				case 'sys_user':
				case 'sys_username':
				case 'sys_creationUser':
				case 'sys_creationUsername':
				case 'sys_lockMicroTime':
				case 'sys_lockId':
					$doPersist=false;
					break;
			}
			if($doPersist)
			{
				$sqlB->updateValueIfChanged($sqlB->getSqlColumnNameForFieldSelector($fs), $val, $origVal, $sqlB->getSqlDataTypeForFieldSelector($fs));
			}
		}
		// forces sys_date and sys_user
		$sqlB->updateSysUser($principal);

		$sqlB->setWhereClauseSingleId('id_group', $group->getId());

		return $sqlB->getSql();
	}
	/**
	 * only updates id_group_parent field
	 */
	protected function getSqlForUpdateGroupParent($principal, $group, $origGroup)
	{
		if(is_null($origGroup)) throw new GroupAdminServiceException('original group can not be null', GroupAdminServiceException::INVALID_ARGUMENT);

		$sqlB = $this->getMySqlFacade()->getSqlBuilder();
		$sqlB->setTableForUpdate('Groups');

		if(is_null($group)) throw new GroupAdminServiceException('group can not be null', GroupAdminServiceException::INVALID_ARGUMENT);

		$sqlB->updateValueIfChanged('id_group_parent', $group->getGroupParentId(), $origGroup->getGroupParentId(), MySqlQueryBuilder::SQLTYPE_INT);
		$sqlB->updateSysUser($principal);

		$sqlB->setWhereClauseSingleId('id_group', $group->getId());

		return $sqlB->getSql();
	}
	protected function getSqlForCheckParentGroupCircularity($group)
	{
		if(is_null($group)) throw new GroupAdminServiceException('group can not be null', GroupAdminServiceException::INVALID_ARGUMENT);
		$sqlB = $this->getMySqlFacade()->getSqlBuilder();
		$id_group_owner = $sqlB->formatBinExp('GG.id_group_owner', '=', $group->getId(), MySqlQueryBuilder::SQLTYPE_INT);
		$id_group = $sqlB->formatBinExp('GG.id_group', '=', $group->getGroupParentId(), MySqlQueryBuilder::SQLTYPE_INT);
		return "select GG.id_group from Groups_Groups as GG where $id_group_owner and $id_group";
	}

	/**
	 * Gets SQL insert to insert new group (without children) in Groups_Groups
	 */
	public function getSqlForInsertLeafInGroupGroup($principal, $group)
	{
		return $this->getSqlBuilderForInsertLeafInGroupGroup($principal, $group)->getSql();
	}
	public function setSqlBuilderForInsertLeafInGroupGroup($sqlBuilderForInsertLeafInGroupGroup)
	{
		$this->sqlBuilderForInsertLeafInGroupGroup = $sqlBuilderForInsertLeafInGroupGroup;
	}
	protected function getSqlBuilderForInsertLeafInGroupGroup($principal, $group)
	{
		// autowired
		if(!isset($this->sqlBuilderForInsertLeafInGroupGroup))
		{
			$this->sqlBuilderForInsertLeafInGroupGroup = GroupSqlBuilderForInsertLeafInGroupGroup::createInstance($principal, $group);
		}
		else $this->sqlBuilderForInsertLeafInGroupGroup->reset($principal, $group);
		return $this->sqlBuilderForInsertLeafInGroupGroup;
	}
	/**
	 * Gets SQL delete to disconnect existing group (with children) from its parent in Groups_Groups
	 */
	protected function getSqlToDisconnectGroupFromParentInGroupGroup($principal, $group)
	{
		return $this->getSqlBuilderToDisconnectGroupFromParentInGroupGroup($principal, $group)->getSql();
	}
	public function setSqlBuilderToDisconnectGroupFromParentInGroupGroup($sqlBuilderForInsertLeafInGroupGroup)
	{
		$this->sqlBuilderToDisconnectGroupFromParentInGroupGroup = $sqlBuilderForInsertLeafInGroupGroup;
	}
	protected function getSqlBuilderToDisconnectGroupFromParentInGroupGroup($principal, $group)
	{
		// autowired
		if(!isset($this->sqlBuilderToDisconnectGroupFromParentInGroupGroup))
		{
			$this->sqlBuilderToDisconnectGroupFromParentInGroupGroup = GroupSqlBuilderToDisconnectGroupFromParentInGroupGroup::createInstance($principal, $group);
		}
		else $this->sqlBuilderToDisconnectGroupFromParentInGroupGroup->reset($principal, $group);
		return $this->sqlBuilderToDisconnectGroupFromParentInGroupGroup;
	}
	/**
	 * Gets SQL insert to connect existing group (with children) to its parent in Groups_Groups
	 */
	protected function getSqlToConnectGroupToParentInGroupGroup($principal, $group)
	{
		return $this->getSqlBuilderToConnectGroupToParentInGroupGroup($principal, $group)->getSql();
	}
	public function setSqlBuilderToConnectGroupToParentInGroupGroup($sqlBuilderForInsertLeafInGroupGroup)
	{
		$this->sqlBuilderToConnectGroupToParentInGroupGroup = $sqlBuilderForInsertLeafInGroupGroup;
	}
	protected function getSqlBuilderToConnectGroupToParentInGroupGroup($principal, $group)
	{
		// autowired
		if(!isset($this->sqlBuilderToConnectGroupToParentInGroupGroup))
		{
			$this->sqlBuilderToConnectGroupToParentInGroupGroup = GroupSqlBuilderToConnectGroupToParentInGroupGroup::createInstance($principal, $group);
		}
		else $this->sqlBuilderToConnectGroupToParentInGroupGroup->reset($principal, $group);
		return $this->sqlBuilderToConnectGroupToParentInGroupGroup;
	}

	/**
	 * Returns default principal UGR when he creates a group
	 * defaults to canModify=true, canWriteElement=true, canShareElement=true
	 */
	protected function getUGROnGroupCreation($principal, $group)
	{
		$ugr = UGR::createInstance($group->getId(), $principal->getAttachedUser()->getId());
		$ugr->setCanModify(true);
		$ugr->setCanWriteElement(true);
		$ugr->setCanShareElement(true);
		return $ugr;
	}
	protected function createUGRFromGroupP($principal, $groupP)
	{
		$group = $groupP->getGroup();
		$rights = $groupP->getRights();
		$ugr = UGR::createInstance($group->getId(), $principal->getAttachedUser()->getId());
		$ugr->setCanModify($rights->canModify());
		$ugr->setCanWriteElement($rights->canWriteElement());
		$ugr->setCanShareElement($rights->canShareElement());
		return $ugr;
	}
	protected function getSqlForInsertUserGroupRight($ugr)
	{
		if(is_null($ugr)) throw new GroupAdminServiceException('UGR can not be null', GroupAdminServiceException::INVALID_ARGUMENT);
		$id_user = $ugr->getUserId();
		if(is_null($id_user)) throw new GroupAdminServiceException('UGR->userId can not be null', GroupAdminServiceException::INVALID_ARGUMENT);
		$id_group = $ugr->getGroupId();
		if(is_null($id_group)) throw new GroupAdminServiceException('UGR->groupId can not be null', GroupAdminServiceException::INVALID_ARGUMENT);

		$sqlB = $this->getMySqlFacade()->getSqlBuilder();
		$sqlB->setTableForInsert('Users_Groups_Rights');
		$sqlB->insertValue("id_user", $id_user, MySqlQueryBuilder::SQLTYPE_INT);
		$sqlB->insertValue("id_group", $id_group, MySqlQueryBuilder::SQLTYPE_INT);
		$sqlB->insertValue("canModify", $ugr->canModify(), MySqlQueryBuilder::SQLTYPE_BOOLEAN);
		$sqlB->insertValue("canWriteElement", $ugr->canWriteElement(), MySqlQueryBuilder::SQLTYPE_BOOLEAN);
		$sqlB->insertValue("canShareElement", $ugr->canShareElement(), MySqlQueryBuilder::SQLTYPE_BOOLEAN);
		return $sqlB->getSql();
	}
	protected function getSqlForUpdateUserGroupRight($ugr)
	{
		if(is_null($ugr)) throw new GroupAdminServiceException('UGR can not be null', GroupAdminServiceException::INVALID_ARGUMENT);
		$id_user = $ugr->getUserId();
		if(is_null($id_user)) throw new GroupAdminServiceException('UGR->userId can not be null', GroupAdminServiceException::INVALID_ARGUMENT);
		$id_group = $ugr->getGroupId();
		if(is_null($id_group)) throw new GroupAdminServiceException('UGR->groupId can not be null', GroupAdminServiceException::INVALID_ARGUMENT);

		$sqlB = $this->getMySqlFacade()->getSqlBuilder();
		$sqlB->setTableForUpdate('Users_Groups_Rights', true);
		$sqlB->updateValue("id_user", $id_user, MySqlQueryBuilder::SQLTYPE_INT);
		$sqlB->updateValue("id_group", $id_group, MySqlQueryBuilder::SQLTYPE_INT);
		$sqlB->updateValue("canModify", $ugr->canModify(), MySqlQueryBuilder::SQLTYPE_BOOLEAN);
		$sqlB->updateValue("canWriteElement", $ugr->canWriteElement(), MySqlQueryBuilder::SQLTYPE_BOOLEAN);
		$sqlB->updateValue("canShareElement", $ugr->canShareElement(), MySqlQueryBuilder::SQLTYPE_BOOLEAN);
		return $sqlB->getSql();
	}
	protected function getSqlForGetUserGroupRight($userId, $groupId, $fieldSelectorList = null)
	{
		$sqlB = $this->getMySqlFacade()->getSqlBuilder();
		$id_user = $sqlB->formatBinExp('UGR.id_user', '=', $userId, MySqlQueryBuilder::SQLTYPE_INT);
		$id_group = $sqlB->formatBinExp('UGR.id_group', '=', $groupId, MySqlQueryBuilder::SQLTYPE_INT);
		return "select ".$this->getSqlColumnsForUserGroupRight('UGR', $fieldSelectorList)." from Users_Groups_Rights as UGR where $id_user and $id_group";
	}
	protected function getSqlForDeleteUserGroupRight($ugr)
	{
		if(is_null($ugr)) throw new GroupAdminServiceException('UGR can not be null', GroupAdminServiceException::INVALID_ARGUMENT);
		$id_user = $ugr->getUserId();
		if(is_null($id_user)) throw new GroupAdminServiceException('UGR->userId can not be null', GroupAdminServiceException::INVALID_ARGUMENT);
		$id_group = $ugr->getGroupId();
		if(is_null($id_group)) throw new GroupAdminServiceException('UGR->groupId can not be null', GroupAdminServiceException::INVALID_ARGUMENT);

		$sqlB = $this->getMySqlFacade()->getSqlBuilder();
		$sqlB->setTableForDelete('Users_Groups_Rights');
		$id_user = $sqlB->formatBinExp('Users_Groups_Rights.id_user', '=', $id_user, MySqlQueryBuilder::SQLTYPE_INT);
		$id_group = $sqlB->formatBinExp('Users_Groups_Rights.id_group', '=', $id_group, MySqlQueryBuilder::SQLTYPE_INT);
		$sqlB->setWhereClause("$id_group and $id_user");
		return $sqlB->getSql();
	}
	
	public function deleteAllGroupsFromWigiiNamespace($principal, $wigiiNamespace)
	{
		$this->executionSink()->publishStartOperation("deleteAllGroupsFromWigiiNamespace", $principal);
		try
		{
			// checks authorization
			$this->assertPrincipalAuthorizedForDeleteAllGroupsFromWigiiNamespace($principal, $wigiiNamespace);
			
			$dbAS = $this->getDbAdminService();
			$dbCS = $dbAS->getDbConnectionSettings($principal);

			// deletes users/roles
			$returnValue = $dbAS->deleteRows($principal,
				$this->getSqlTableNameForDeleteGroup(),
				$this->getSqlWhereClauseForDeleteAllGroupsFromWigiiNamespace($wigiiNamespace),
				$dbCS);
		}
		catch (GroupAdminServiceException $gaE){
			$this->executionSink()->publishEndOperationOnError("deleteAllGroupsFromWigiiNamespace", $gaE, $principal);
			throw $gaE;
		}
		catch (AuthorizationServiceException $asE){
			$this->executionSink()->publishEndOperationOnError("deleteAllGroupsFromWigiiNamespace", $asE, $principal);
			throw $asE;
		}
		catch(Exception $e)
		{
			$this->executionSink()->publishEndOperationOnError("deleteAllGroupsFromWigiiNamespace", $e, $principal);
			throw new GroupAdminServiceException('',GroupAdminServiceException::WRAPPING, $e);
		}
		$this->executionSink()->publishEndOperation("deleteAllGroupsFromWigiiNamespace", $principal);
		return $returnValue;
	}
	protected function assertPrincipalAuthorizedForDeleteAllGroupsFromWigiiNamespace($principal, $wigiiNamespace)
	{
		if(is_null($principal)) throw new GroupAdminServiceException('principal can not be null', GroupAdminServiceException::INVALID_ARGUMENT);

		$autoS = $this->getAuthorizationService();
		// checks general authorization
		$autoS->assertPrincipalAuthorized($principal, "GroupAdminService", "deleteAllGroupsFromWigiiNamespace");
		// check specific rights
		// existing user/role -> (admin creator or user creator or user manager in wigiiNamespace) and principal owns user
		if(!$principal->isWigiiNamespaceCreator())
		{
			$autoS->fail($principal, "has no right to delete all Group of a namespace");
		}
		//check himself
		if($principal->getWigiiNamespace()->getWigiiNamespaceName()==$wigiiNamespace->getWigiiNamespaceName()){
			$autoS->fail($principal, "cannot delete own namespace");
		}
	}
	protected function getSqlWhereClauseForDeleteAllGroupsFromWigiiNamespace($wigiiNamespace)
	{
		if(is_null($wigiiNamespace)) throw new GroupAdminServiceException('wigiiNamespace can not be null', GroupAdminServiceException::INVALID_ARGUMENT);
		$sqlB = $this->getMySqlFacade()->getSqlBuilder();
		return $sqlB->formatBinExp('Groups.wigiiNamespace', '=', $wigiiNamespace->getWigiiNamespaceName(), MySqlQueryBuilder::SQLTYPE_VARCHAR);
	}
	
	public function deleteGroup($principal, $groupId, $moveContentAndChildrenToParent=false)
	{
		$this->executionSink()->publishStartOperation("deleteGroup", $principal);
		try
		{
			// reads existing group in database
			$origGroupP = $this->getGroup($principal, $groupId, $this->getFieldSelectorListForGroupWithoutDetail());
			if(is_null($origGroupP)) return 0;
			$origPRights = $origGroupP->getRights();

			// checks authorization
			$this->assertPrincipalAuthorizedForDeleteGroup($principal, $origGroupP, $groupId);
			$group = $origGroupP->getGroup();
			
			//check if $moveContentAndChildrenToParent that parent is not null
			if($moveContentAndChildrenToParent && $group->getGroupParentId()==null){
				throw new GroupAdminServiceException('group parent cannot be null if delete group with moveContentAndChildrenToParent=true',GroupAdminServiceException::INVALID_ARGUMENT);
			}

			$dbAS = $this->getDbAdminService();
			$dbCS = $dbAS->getDbConnectionSettings($principal);
			
			// acquires lock
			$shouldUnlock = $this->lock($principal, $group);
			try
			{
				// if $moveContentAndChildrenToParent then reconnects children to parent and elements linked on this group
				if($moveContentAndChildrenToParent)
				{
					// get direct children
					$listFilter = ListFilter::createInstance();
					$listFilter->setFieldSelectorList($this->getFieldSelectorListForGroupWithoutDetail());
					$listFilter->setFieldSelectorLogExp(LogExp::createEqualExp(FieldSelector::createInstance("id_group_parent"), $groupId));
					$groupPList = GroupPListArrayImpl::createInstance();
					$mySqlF = $this->getMySqlFacade();
					if($this->getSelectedGroups($principal, $listFilter, $groupPList) > 0)
					{
						$newChildG = Group::createInstance();
						$newParentGroupId = $group->getGroupParentId();
						foreach($groupPList->getListIterator() as $childGroupP)
						{
							$childG = $childGroupP->getGroup();

							// change parent and unlock
							$newChildG->setId($childG->getId());
							$newChildG->setWigiiNamespace($childG->getWigiiNamespace());
							$newChildG->setGroupName($childG->getGroupName());
							$newChildG->setModule($childG->getModule());
							$newChildG->setGroupParentId($newParentGroupId);
							$this->changeParentGroup($principal, $newChildG, $childGroupP, $mySqlF, $dbCS);
						}
					}
					
					//update elementGroup table with replacing groupId with parentGroupId
					$mySqlF->update($principal,
						$this->getSqlForUpdateElementGroupForMoveContentToParent($group->getId(), $group->getGroupParentId()),
						$dbCS);
				}
//				// deletes group
				if($dbAS->deleteRows($principal,
					$this->getSqlTableNameForDeleteGroup(),
					$this->getSqlWhereClauseForDeleteGroup($group),
					$dbCS) > 0) $returnValue = 1;
				else $returnValue = 0;
			}
			// releases lock
			catch(Exception $ne) {if($shouldUnlock) $this->unLock($principal, $group); throw $ne;}
			if($shouldUnlock) $this->unLock($principal, $group);
		}
		catch (GroupAdminServiceException $uaE){
			$this->executionSink()->publishEndOperationOnError("deleteGroup", $uaE, $principal);
			throw $uaE;
		}
		catch (AuthorizationServiceException $asE){
			$this->executionSink()->publishEndOperationOnError("deleteGroup", $asE, $principal);
			throw $asE;
		}
		catch(Exception $e)
		{
			$this->executionSink()->publishEndOperationOnError("deleteGroup", $e, $principal);
			throw new GroupAdminServiceException('',GroupAdminServiceException::WRAPPING, $e);
		}
		$this->executionSink()->publishEndOperation("deleteGroup", $principal);
		return $returnValue;
	}
	protected function assertPrincipalAuthorizedForDeleteGroup($principal, $origGroupP, $userErrorInfo='')
	{
		if(is_null($principal)) throw new GroupAdminServiceException('principal can not be null', GroupAdminServiceException::INVALID_ARGUMENT);

		$autoS = $this->getAuthorizationService();
		// checks general authorization
		$autoS->assertPrincipalAuthorized($principal, "GroupAdminService", "deleteGroup");
		// check specific rights
		if(is_null($origGroupP) || is_null($origGroupP->getRights()) || 
			!$origGroupP->getRights()->canModify() ||
			!$principal->getGroupCreator($origGroupP->getGroup()->getModule()) ||
			(!$principal->getRootGroupCreator($origGroupP->getGroup()->getModule()) && $origGroupP->getGroup()->getGroupParentId()==null)
			)
		{
			$autoS->fail($principal, "has no right to delete the group $userErrorInfo");
		}
	}
	protected function getSqlForUpdateElementGroupForMoveContentToParent($groupId, $parentGroupId)
	{
		if(is_null($parentGroupId)) throw new ElementServiceException('parentGroupId can not be null', GroupAdminServiceException::INVALID_ARGUMENT);
		if(is_null($groupId)) throw new ElementServiceException('groupId can not be null', GroupAdminServiceException::INVALID_ARGUMENT);
		
		$sqlB = $this->getMySqlFacade()->getSqlBuilder();
		$sqlB->setTableForUpdate('IGNORE Elements_Groups', false); //the ignore allows to not have a mysql duplicate error if the item we try to add to parent was already in the parent.
		$sqlB->updateValue('id_group', $parentGroupId, MySqlQueryBuilder::SQLTYPE_INT);
		$sqlB->setWhereClauseSingleId('id_group', $groupId);
		return $sqlB->getSql();
	}
	protected function getSqlTableNameForDeleteGroup()
	{
		return 'Groups';
	}
	protected function getSqlWhereClauseForDeleteGroup($group)
	{
		if(is_null($group)) throw new GroupAdminServiceException('group can not be null', GroupAdminServiceException::INVALID_ARGUMENT);
		$sqlB = $this->getMySqlFacade()->getSqlBuilder();
		return $sqlB->formatBinExp('Groups.id_group', '=', $group->getId(), MySqlQueryBuilder::SQLTYPE_INT);
	}

	public function rebuildGroupsGroups($principal)
	{
		$this->executionSink()->publishStartOperation("rebuildGroupsGroups", $principal);
		try
		{
			// checks authorization
			$pRights = $this->assertPrincipalAuthorizedForRebuildGroupsGroups($principal);

			// gets all Groups in DB as a tree
			$groupTree = GroupListTreeArrayImpl::createInstance();
			$this->getSelectedGroupsWithoutDetail($principal,
				LogExp::createNotEqualExp(FieldSelector::createInstance("module"), null),
				$groupTree);

			// rebuilds Groups_Groups
			$this->getGroupsGroupsBuilder()->run($principal, $groupTree);
		}
		catch (GroupAdminServiceException $gaE){
			$this->executionSink()->publishEndOperationOnError("rebuildGroupsGroups", $gaE, $principal);
			throw $gaE;
		}
		catch (AuthorizationServiceException $asE){
			$this->executionSink()->publishEndOperationOnError("rebuildGroupsGroups", $asE, $principal);
			throw $asE;
		}
		catch(Exception $e)
		{
			$this->executionSink()->publishEndOperationOnError("rebuildGroupsGroups", $e, $principal);
			throw new GroupAdminServiceException('',GroupAdminServiceException::WRAPPING, $e);
		}
		$this->executionSink()->publishEndOperation("rebuildGroupsGroups", $principal);
	}
	/**
	 * In this implementation only root principal is authorized to do this.
	 */
	protected function assertPrincipalAuthorizedForRebuildGroupsGroups($principal)
	{
		$this->getAuthorizationService()->assertPrincipalIsRoot($principal);
	}

	public function getAllGroups($principal, $module, $groupPTree, $listFilter=null)
	{
		$this->executionSink()->publishStartOperation("getAllGroups", $principal);
		$groupPMapper = null;
		try
		{
			// checks authorization
			$pRights = $this->assertPrincipalAuthorizedForGetAllGroups($principal, $module);
			//if pRights define, then any group in the module in the wigiiNamespace mathing the groupLogExp is returned
			//in this case parents groups not matching the groupLogExp are not returned
			
			// initiliazes pagination, sorting, etc.
			$fieldSelectorList = null;
			$groupLogExp = null;
			$queryPaged = false;
			if(isset($listFilter))
			{
				$fieldSelectorList = $listFilter->getFieldSelectorList();
				$groupLogExp = $listFilter->getFieldSelectorLogExp();
				if($listFilter->isPaged())
				{
					$queryPaged = true;
					$pageSize = $listFilter->getPageSize();
					$offset = ($listFilter->getDesiredPageNumber() - 1) * $pageSize;
				}
			}

			$groupPMapper = $this->getGroupPTreeMapper($principal, $pRights);
			// if principal has special rights
			if(isset($pRights))
			{
				if($queryPaged)
				{
					$listFilter->setTotalNumberOfObjects($this->getMySqlFacade()->selectPage($principal,
							$this->getSqlForGetAllGroups($principal->getWigiiNamespace(), $module, $fieldSelectorList, $groupLogExp),
							$this->getDbAdminService()->getDbConnectionSettings($principal), $offset, $pageSize,
							$groupPMapper));
				}
				else
				{
					$this->getMySqlFacade()->selectAll($principal,
							$this->getSqlForGetAllGroups($principal->getWigiiNamespace(), $module, $fieldSelectorList, $groupLogExp),
							$this->getDbAdminService()->getDbConnectionSettings($principal),
							$groupPMapper);
				}
			}
			// else rights are given by DB
			else
			{
				if($queryPaged)
				{
					$listFilter->setTotalNumberOfObjects($this->getMySqlFacade()->selectPage($principal,
							$this->getSqlPForGetAllGroups($principal, $module, $fieldSelectorList, $groupLogExp),
							$this->getDbAdminService()->getDbConnectionSettings($principal), $offset, $pageSize,
							$groupPMapper));
				}
				else
				{
					$this->getMySqlFacade()->selectAll($principal,
							$this->getSqlPForGetAllGroups($principal, $module, $fieldSelectorList, $groupLogExp),
							$this->getDbAdminService()->getDbConnectionSettings($principal),
							$groupPMapper);
				}
			}
			$returnValue = $groupPMapper->count();
			$groupPMapper->flush($groupPTree, $fieldSelectorList);			
		}
		catch (GroupAdminServiceException $gaE){
			if(isset($groupPMapper)) $groupPMapper->freeMemory();
			$this->executionSink()->publishEndOperationOnError("getAllGroups", $gaE, $principal);
			throw $gaE;
		}
		catch (AuthorizationServiceException $asE){
			if(isset($groupPMapper)) $groupPMapper->freeMemory();
			$this->executionSink()->publishEndOperationOnError("getAllGroups", $asE, $principal);
			throw $asE;
		}
		catch(Exception $e)
		{
			if(isset($groupPMapper)) $groupPMapper->freeMemory();
			$this->executionSink()->publishEndOperationOnError("getAllGroups", $e, $principal);
			throw new GroupAdminServiceException('',GroupAdminServiceException::WRAPPING, $e);
		}
		$this->executionSink()->publishEndOperation("getAllGroups", $principal);
		return $returnValue;
	}
	protected function assertPrincipalAuthorizedForGetAllGroups($principal, $module)
	{
		if(is_null($module)) throw new GroupAdminServiceException('module can not be null', GroupAdminServiceException::INVALID_ARGUMENT);
		$autoS = $this->getAuthorizationService();
		// checks general authorization
		$pRights = $autoS->assertPrincipalAuthorized($principal, "GroupAdminService", "getAllGroups");
		// checks module access
		if(is_null($pRights) && is_null($principal->getModuleAccess($module))) $autoS->fail($principal, 'has no access to module '.$module->getModuleName());
		return $pRights;
	}
	/**
	 * Returns all groups in wigiiNamespace for given module
	 */
	protected function getSqlForGetAllGroups($wigiiNamespace, $module, $fieldSelectorList=null, $groupLogExp=null)
	{
		if(isset($wigiiNamespace)) $wigiiNamespace = $wigiiNamespace->getWigiiNamespaceName();
		if(isset($module)) $module = $module->getModuleName();
		$sqlB = $this->getMySqlFacade()->getSqlBuilder();
		$wigiiNamespace = $sqlB->formatBinExp('G.wigiiNamespace', '=', $wigiiNamespace, MySqlQueryBuilder::SQLTYPE_VARCHAR);
		$module = $sqlB->formatBinExp('G.modulename', '=', $module, MySqlQueryBuilder::SQLTYPE_VARCHAR);

		if(!is_null($groupLogExp))
		{
			// reduces logExp and clones it.
			$groupLogExp = $groupLogExp->reduceNegation(true);
			$groupLogExp = $this->getSqlWhereClauseForSelectGroups($groupLogExp, 'G');
			if(!is_null($groupLogExp) && $groupLogExp != '') $groupLogExp = " and ".$groupLogExp;
		}
		
		//the parents are not fetched if dosen't fetch the groupLogExp
		return "SELECT ".$this->getSqlColumnsForGroup('G', $fieldSelectorList)." 
from Groups as G 
where $wigiiNamespace and $module $groupLogExp order by G.id_group_parent, G.wigiiNamespace, G.groupname";
	}
	protected function getSqlPForGetAllGroups($principal, $module, $fieldSelectorList=null, $groupLogExp=null)
	{
		$principalId = $principal->getUserId();
		if(is_null($principalId)) throw new GroupAdminServiceException('principal attached user can not be null', GroupAdminServiceException::INVALID_ARGUMENT);
		if(is_null($module)) throw new GroupAdminServiceException('module can not be null', GroupAdminServiceException::INVALID_ARGUMENT);
		$sqlB = $this->getMySqlFacade()->getSqlBuilder();
		$principalId = $sqlB->formatValue($principalId, MySqlQueryBuilder::SQLTYPE_INT);
		$propagation = $this->getModuleAdminService()->getDefaultPropagation($module);
		$select_Groups_whereClause = $sqlB->formatBinExp('G.modulename', '=', $module->getModuleName(), MySqlQueryBuilder::SQLTYPE_VARCHAR);

//		if(!is_null($groupLogExp))
//		{
//			// reduces logExp and clones it.
//			$groupLogExp = $groupLogExp->reduceNegation(true);
//			
//			$groupLogExp = $this->getSqlWhereClauseForSelectGroups($groupLogExp, 'G2');
//			if(!is_null($groupLogExp) && $groupLogExp != '') $groupLogExp = " where ".$groupLogExp;
//		}

		return "SELECT ".$this->getSqlColumnsForGroup('G2', $fieldSelectorList).", GP2.canModify, GP2.canWriteElement, GP2.canShareElement 
from ("
.$this->getSqlForSelectAllGroupsP($principal, $module, $principalId, $select_Groups_whereClause, $groupLogExp, $propagation, true, 'UGR','GG','GP','G')
.") as GP2 
inner join Groups as G2 on G2.id_group = GP2.id_group 
order by G2.id_group_parent, G2.wigiiNamespace, G2.groupname";
	}



	public function getGroup($principal, $groupId, $fieldSelectorList=null, $countBlockedElements=false)
	{
		$this->executionSink()->publishStartOperation("getGroup", $principal);
		try
		{
			// checks authorization
			$pRights = $this->assertPrincipalAuthorizedForGetGroup($principal);

			$returnValue = null;
			// if principal has special rights
			if(isset($pRights))
			{
				$dbRow = $this->getMySqlFacade()->selectOne($principal,
						$this->getSqlForGetGroup($groupId, $fieldSelectorList),
						$this->getDbAdminService()->getDbConnectionSettings($principal));
				if(isset($dbRow))
				{
					// sets client
					$dbRow['client'] = $principal->getWigiiNamespace()->getClient();
					// create GroupP
					$returnValue = GroupP::createInstance($this->createGroupInstanceFromRow($principal, $dbRow));
					$returnValue->setRights($pRights);
				}
			}
			// else rights are given by DB
			else
			{
				$dbCS = $this->getDbAdminService()->getDbConnectionSettings($principal);

				// 1. gets group without any rights
				$dbRow = $this->getMySqlFacade()->selectOne($principal,
						$this->getSqlForGetGroup($groupId, $fieldSelectorList),
						$dbCS);
				if(isset($dbRow))
				{
					// sets client
					$dbRow['client'] = $principal->getWigiiNamespace()->getClient();
					// create Group
					$returnValue = $this->createGroupInstanceFromRow($principal, $dbRow);

					// 2. checks module access
					$module = $returnValue->getModule();
					if(is_null($module)) throw new GroupAdminServiceException('group can not have a null module in database', GroupAdminServiceException::DATA_INTEGRITY_ERROR);
					if(!$this->getAuthorizationService()->isRootPrincipal($principal) && is_null($principal->getModuleAccess($module)))
					{
						$this->getAuthorizationService()->fail($principal, 'has not access to module '.$module->getModuleName());
					}

					// 3. gets rights according to propagation
					unset($dbRow);
					$dbRow = $this->getMySqlFacade()->selectOne($principal,
						$this->getSqlPForGetGroupId($principal, $module, $groupId,
							$this->getModuleAdminService()->getDefaultPropagation($returnValue->getModule())),
						$dbCS);
					// if some rights are defined
					if(isset($dbRow))
					{
						$returnValue = GroupP::createInstance($returnValue);
						$returnValue->setRights(PrincipalRights::createInstance($dbRow));
					}
					// else no rights
					else
					{
						// does not show GroupDetail
						$returnValue->setDetail(null);
						$returnValue = GroupP::createInstance($returnValue);
					}
				}
			}
			
			// counts blocked elements
			if($countBlockedElements && isset($returnValue)) {
				$groupDetail = $returnValue->getGroup()->getDetail();
				if(isset($groupDetail)) {
					$n = $this->getElementService()->countSelectedElementsInGroups($principal, 
							lxInGR(lxEq(fs('id'), $groupId)),
							lf(null, lxEq(fs_e('state_blocked'), true)));
					$groupDetail->setNumberOfBlockedElements($n);
				}
			}
		}
		catch (GroupAdminServiceException $gaE){
			$this->executionSink()->publishEndOperationOnError("getGroup", $gaE, $principal);
			throw $gaE;
		}
		catch (AuthorizationServiceException $asE){
			$this->executionSink()->publishEndOperationOnError("getGroup", $asE, $principal);
			throw $asE;
		}
		catch(Exception $e)
		{
			$this->executionSink()->publishEndOperationOnError("getGroup", $e, $principal);
			throw new GroupAdminServiceException('',GroupAdminServiceException::WRAPPING, $e);
		}
		$this->executionSink()->publishEndOperation("getGroup", $principal);
		return $returnValue;
	}
	protected function assertPrincipalAuthorizedForGetGroup($principal)
	{
		// checks general authorization
		return $this->getAuthorizationService()->assertPrincipalAuthorized($principal, "GroupAdminService", "getGroup");
	}
	protected function getSqlForGetGroup($groupId, $fieldSelectorList=null)
	{
		$sqlB = $this->getMySqlFacade()->getSqlBuilder();
		$id_group = $sqlB->formatBinExp('G.id_group', '=', $groupId, MySqlQueryBuilder::SQLTYPE_INT);
		return "select ".$this->getSqlColumnsForGroup('G', $fieldSelectorList)." from Groups as G where $id_group";
	}
	protected function getSqlPForGetGroupId($principal, $module, $groupId, $propagation)
	{
		$principalId = $principal->getUserId();
		if(is_null($principalId)) throw new GroupAdminServiceException('principal attached user can not be null', GroupAdminServiceException::INVALID_ARGUMENT);		
		$sqlB = $this->getMySqlFacade()->getSqlBuilder();
		$principalId = $sqlB->formatValue($principalId, MySqlQueryBuilder::SQLTYPE_INT);		
		$groupId = $sqlB->formatValue($groupId, MySqlQueryBuilder::SQLTYPE_INT);
		return $this->getSqlForSelectGroupP($principal, $module, $principalId, $groupId, $propagation);
	}
	
	public function createRowListGroupPathImplInstance($crtWigiiNamespaceName){
		return RowListGroupPathImpl::createInstance($crtWigiiNamespaceName);
	}
	
	/**
	 * getGroupsPath from groupPList
	 * groupPList / groupList / array list of groups / group ids we want to display the full path
	 * @return array[id_group]=array([id_group_parent]=>groupParentName,...,[id_group]=>[groupname]
	 * id_group represent each group id of the groupList 
	 * if the wigiiNamespace of a group is different than the principal wigiiNamespace the groupname is prefixed with "wigiiNamespace : "
	 */
	private $cache_getGroupsPath = null;
	public function resetGetGroupsPathCache(){
		$this->cache_getGroupsPath = array();
	}
	public function getGroupsPath($principal, $groupPList){
		$this->executionSink()->publishStartOperation("getGroupsPath", $principal);
		try
		{
			// checks authorization
			$this->assertPrincipalAuthorizedForGetGroupsPath($principal, $groupPList);
			
			$groupIds = array();
			if(is_object($groupPList)){
				foreach($groupPList->getListIterator() as $groupP){
					$groupIds[] = $groupP->getId();
				}
				$groupIds = implode(",", $groupIds);
			} else {
				$groupIds = implode(",", $groupPList);
			}
			
			if(!isset($this->cache_getGroupsPath)) $this->cache_getGroupsPath = array();
			if(!isset($this->cache_getGroupsPath[$groupIds])){
				$rowList = $this->createRowListGroupPathImplInstance($principal->getWigiiNamespace()->getWigiiNamespaceName());
				$this->getMySqlFacade()->selectAll($principal,
					$this->getSqlForGetGroupsPath($principal, $groupIds),
					$this->getDbAdminService()->getDbConnectionSettings($principal),
					$rowList);
				$this->cache_getGroupsPath[$groupIds] = $rowList->getPaths();
			}
			
			$returnValue = $this->cache_getGroupsPath[$groupIds];
		}
		catch (ElementServiceException $esE){
			$this->executionSink()->publishEndOperationOnError("getGroupsPath", $esE, $principal);
			throw $esE;
		}
		catch (AuthorizationServiceException $asE){
			$this->executionSink()->publishEndOperationOnError("getGroupsPath", $asE, $principal);
			throw $asE;
		}
		catch(Exception $e)
		{
			$this->executionSink()->publishEndOperationOnError("getGroupsPath", $e, $principal);
			throw new ElementServiceException('',ElementServiceException::WRAPPING, $e);
		}
		$this->executionSink()->publishEndOperation("getGroupsPath", $principal);
		return $returnValue;
	}
	protected function assertPrincipalAuthorizedForGetGroupsPath($principal, $groupPList)
	{
		if(is_null($groupPList)) throw new ElementServiceException('groupPList can not be null', ElementServiceException::INVALID_ARGUMENT);

		$autoS = $this->getAuthorizationService();
		// checks general authorization
		$autoS->assertPrincipalAuthorized($principal, "GroupAdminService", "getGroupsPath");
	}
	protected function getSqlForGetGroupsPath($principal, $groupIds)
	{
		if(is_null($groupIds)) throw new ElementServiceException("groupIds cannot be null", ElementServiceException::INVALID_ARGUMENT);
		if(is_null($principal)) throw new ElementServiceException("principal cannot be null", ElementServiceException::INVALID_ARGUMENT);

		$sqlB = $this->getSqlWhereClauseBuilderForSelectGroups('G');
		
		//the result are sorted DESC on isParent. --> that allows then the rowListGroupPathImpl to already have the parent
		//details when acting on the groups containing the Element
		$returnValue = "
select G.id_group as id, G.groupname as groupname, G.wigiiNamespace as wigiiNamespace, G.id_group_parent as id_group_parent, 0 as 'isParent' from Groups as G  
where G.id_group IN ($groupIds)
union
select G2.id_group as id, G2.groupname as groupname, G2.wigiiNamespace as wigiiNamespace, G2.id_group_parent as id_group_parent, 1 as 'isParent' from Groups as G2  
inner join Groups_Groups GG on GG.id_group_owner = G2.id_group 
where GG.id_group IN ($groupIds)
group by id
order by isParent DESC
";
		return $returnValue;
	}
	
	public function getGroupWithoutDetail($principal, $groupId)
	{
		$this->executionSink()->publishStartOperation("getGroupWithoutDetail", $principal);
		try
		{
			// checks authorization
			$this->assertPrincipalAuthorizedForGetGroupWithoutDetail($principal);

			$returnValue = null;
			// 1. gets group without any rights
			$dbRow = $this->getMySqlFacade()->selectOne($principal,
					$this->getSqlForGetGroup($groupId, $this->getFieldSelectorListForGroupWithoutDetail()),
					$this->getDbAdminService()->getDbConnectionSettings($principal));
			if(isset($dbRow))
			{
				// sets client
				$dbRow['client'] = $principal->getWigiiNamespace()->getClient();
				// create Group
				$returnValue = $this->createGroupInstanceFromRow($principal, $dbRow, false);

				// 2. checks module access
				$module = $returnValue->getModule();
				if(is_null($module)) throw new GroupAdminServiceException('group can not have a null module in database', GroupAdminServiceException::DATA_INTEGRITY_ERROR);
				//no check if principal can access this module, because group without detail. (is usefull for Public Principal)
			}
		}
		catch (GroupAdminServiceException $gaE){
			$this->executionSink()->publishEndOperationOnError("getGroupWithoutDetail", $gaE, $principal);
			throw $gaE;
		}
		catch (AuthorizationServiceException $asE){
			$this->executionSink()->publishEndOperationOnError("getGroupWithoutDetail", $asE, $principal);
			throw $asE;
		}
		catch(Exception $e)
		{
			$this->executionSink()->publishEndOperationOnError("getGroupWithoutDetail", $e, $principal);
			throw new GroupAdminServiceException('',GroupAdminServiceException::WRAPPING, $e);
		}
		$this->executionSink()->publishEndOperation("getGroupWithoutDetail", $principal);
		return $returnValue;
	}
	protected function assertPrincipalAuthorizedForGetGroupWithoutDetail($principal)
	{
		// checks general authorization
		$this->getAuthorizationService()->assertPrincipalAuthorized($principal, "GroupAdminService", "getGroupWithoutDetail");
	}

	public function getGroupsWithoutDetail($principal, $groupIds, $groupList)
	{
		$this->executionSink()->publishStartOperation("getGroupsWithoutDetail", $principal);
		$groupMapper = null;
		try
		{
			if(is_null($groupList)) throw new GroupAdminServiceException('groupList cannot be null', GroupAdminServiceException::INVALID_ARGUMENT);

			// checks authorization
			$this->assertPrincipalAuthorizedForGetGroupsWithoutDetail($principal);

			// gets groups
			$groupMapper = $this->getGroupListMapper($principal, $groupList, false);
			$this->getMySqlFacade()->selectAll($principal,
					$this->getSqlForGetGroupsWithoutDetail($groupIds),
					$this->getDbAdminService()->getDbConnectionSettings($principal),
					$groupMapper);
			$returnValue = $groupMapper->count();
			$groupMapper->freeMemory();
		}
		catch (GroupAdminServiceException $gaE){
			if(isset($groupMapper)) $groupMapper->freeMemory();
			$this->executionSink()->publishEndOperationOnError("getGroupsWithoutDetail", $gaE, $principal);
			throw $gaE;
		}
		catch (AuthorizationServiceException $asE){
			if(isset($groupMapper)) $groupMapper->freeMemory();
			$this->executionSink()->publishEndOperationOnError("getGroupsWithoutDetail", $asE, $principal);
			throw $asE;
		}
		catch(Exception $e)
		{
			if(isset($groupMapper)) $groupMapper->freeMemory();
			$this->executionSink()->publishEndOperationOnError("getGroupsWithoutDetail", $e, $principal);
			throw new GroupAdminServiceException('',GroupAdminServiceException::WRAPPING, $e);
		}
		$this->executionSink()->publishEndOperation("getGroupsWithoutDetail", $principal);
		return $returnValue;
	}
	protected function assertPrincipalAuthorizedForGetGroupsWithoutDetail($principal)
	{
		// checks general authorization
		$this->getAuthorizationService()->assertPrincipalAuthorized($principal, "GroupAdminService", "getGroupsWithoutDetail");
	}
	protected function getSqlForGetGroupsWithoutDetail($groupIds)
	{
		if(is_null($groupIds)) throw new GroupAdminServiceException("groupIds cannot be null, or a single id or an array of ids", GroupAdminServiceException::INVALID_ARGUMENT);
		$sqlB = $this->getMySqlFacade()->getSqlBuilder();
		if(is_array($groupIds))
		{
			$groupIds = $sqlB->formatBinExp("G.id_group", "IN", $groupIds, MySqlQueryBuilder::SQLTYPE_INT);
		}
		else
		{
			$groupIds = $sqlB->formatBinExp("G.id_group", "=", $groupIds, MySqlQueryBuilder::SQLTYPE_INT);
		}
		return "select ".$this->getSqlColumnsForGroup('G', $this->getFieldSelectorListForGroupWithoutDetail())
				." from Groups as G where $groupIds order by G.wigiiNamespace, G.groupname";
	}

	public function getSelectedGroupsWithoutDetail($principal, $groupSelectionLogExp, $groupList)
	{
		$this->executionSink()->publishStartOperation("getSelectedGroupsWithoutDetail", $principal);
		$groupMapper = null;
		try
		{
			if(is_null($groupList)) throw new GroupAdminServiceException('groupList cannot be null', GroupAdminServiceException::INVALID_ARGUMENT);

			// checks authorization
			$this->assertPrincipalAuthorizedForGetSelectedGroupsWithoutDetail($principal);

			// gets groups
			$groupMapper = $this->getGroupListMapper($principal, $groupList, false);
			$this->getMySqlFacade()->selectAll($principal,
					$this->getSqlForGetSelectedGroupsWithoutDetail($groupSelectionLogExp),
					$this->getDbAdminService()->getDbConnectionSettings($principal),
					$groupMapper);
			$returnValue = $groupMapper->count();
			$groupMapper->freeMemory();
		}
		catch (GroupAdminServiceException $gaE){
			if(isset($groupMapper)) $groupMapper->freeMemory();
			$this->executionSink()->publishEndOperationOnError("getSelectedGroupsWithoutDetail", $gaE, $principal);
			throw $gaE;
		}
		catch (AuthorizationServiceException $asE){
			if(isset($groupMapper)) $groupMapper->freeMemory();
			$this->executionSink()->publishEndOperationOnError("getSelectedGroupsWithoutDetail", $asE, $principal);
			throw $asE;
		}
		catch(Exception $e)
		{
			if(isset($groupMapper)) $groupMapper->freeMemory();
			$this->executionSink()->publishEndOperationOnError("getSelectedGroupsWithoutDetail", $e, $principal);
			throw new GroupAdminServiceException('',GroupAdminServiceException::WRAPPING, $e);
		}
		$this->executionSink()->publishEndOperation("getSelectedGroupsWithoutDetail", $principal);
		return $returnValue;
	}
	protected function assertPrincipalAuthorizedForGetSelectedGroupsWithoutDetail($principal)
	{
		// checks general authorization
		$this->getAuthorizationService()->assertPrincipalAuthorized($principal, "GroupAdminService", "getSelectedGroupsWithoutDetail");
	}
	protected function getSqlForGetSelectedGroupsWithoutDetail($groupSelectionLogExp)
	{
		if(is_null($groupSelectionLogExp)) throw new GroupAdminServiceException("groupSelection LogExp cannot be null", GroupAdminServiceException::INVALID_ARGUMENT);
		$sqlB = $this->getSqlWhereClauseBuilderForSelectGroups('G');
		// reduces logExp and clones it.
		$groupSelectionLogExp = $groupSelectionLogExp->reduceNegation(true);
		$whereClause = $sqlB->buildWhereClause($groupSelectionLogExp);
		return "select ".$this->getSqlColumnsForGroup('G', $this->getFieldSelectorListForGroupWithoutDetail())
				." from Groups as G where $whereClause order by G.wigiiNamespace, G.groupname";
	}
	
	public function getSelectedGroupsWithChildrenWithoutDetail($principal, $parentGroupSelectionLogExp, $groupList, $childrenGroupFilterLogExp=null, $groupFilterLogExp=null) 
	{
		$this->executionSink()->publishStartOperation("getSelectedGroupsWithChildrenWithoutDetail", $principal);
		$groupMapper = null;
		try
		{
			if(is_null($groupList)) throw new GroupAdminServiceException('groupList cannot be null', GroupAdminServiceException::INVALID_ARGUMENT);

			// checks authorization
			$this->assertPrincipalAuthorizedForGetSelectedGroupsWithChildrenWithoutDetail($principal);

			// gets groups
			$groupMapper = $this->getGroupListMapper($principal, $groupList, false);
			$this->getMySqlFacade()->selectAll($principal,
					$this->getSqlForGetSelectedGroupsWithChildrenWithoutDetail($parentGroupSelectionLogExp, $childrenGroupFilterLogExp, $groupFilterLogExp),
					$this->getDbAdminService()->getDbConnectionSettings($principal),
					$groupMapper);
			$returnValue = $groupMapper->count();
			$groupMapper->freeMemory();
		}
		catch (GroupAdminServiceException $gaE){
			if(isset($groupMapper)) $groupMapper->freeMemory();
			$this->executionSink()->publishEndOperationOnError("getSelectedGroupsWithChildrenWithoutDetail", $gaE, $principal);
			throw $gaE;
		}
		catch (AuthorizationServiceException $asE){
			if(isset($groupMapper)) $groupMapper->freeMemory();
			$this->executionSink()->publishEndOperationOnError("getSelectedGroupsWithChildrenWithoutDetail", $asE, $principal);
			throw $asE;
		}
		catch(Exception $e)
		{
			if(isset($groupMapper)) $groupMapper->freeMemory();
			$this->executionSink()->publishEndOperationOnError("getSelectedGroupsWithChildrenWithoutDetail", $e, $principal);
			throw new GroupAdminServiceException('',GroupAdminServiceException::WRAPPING, $e);
		}
		$this->executionSink()->publishEndOperation("getSelectedGroupsWithChildrenWithoutDetail", $principal);
		return $returnValue;
	}
	protected function assertPrincipalAuthorizedForGetSelectedGroupsWithChildrenWithoutDetail($principal)
	{
		// checks general authorization
		$this->getAuthorizationService()->assertPrincipalAuthorized($principal, "GroupAdminService", "getSelectedGroupsWithChildrenWithoutDetail");
	}
	protected function getSqlForGetSelectedGroupsWithChildrenWithoutDetail($parentGroupSelectionLogExp, $childrenGroupFilterLogExp=null, $groupFilterLogExp=null)
	{
		if(is_null($parentGroupSelectionLogExp)) throw new GroupAdminServiceException("parentGroupSelectionLogExp LogExp cannot be null", GroupAdminServiceException::INVALID_ARGUMENT);		
		
		/* design
		 select G.* from Groups as G
		 inner join (
		 select sG.id_group from (
		 -- select parent groups
		 select p1G.id_group from Groups as p1G
		 where p1G...
		 union
		 -- select children groups
		 select cG.id_group from Groups as p2G
		 inner join Groups_Groups GG on GG.id_group_owner = p2G.id_group
		 inner join Groups as cG on GG.id_group = cG.id_group
		 where p2G...
		 and cG...
		 group by cG.id_group
		 ) as sG
		 group by sG.id_group
		 ) as sG2 on sG2.id_group = G.id_group
		*/
		
		// select parent groups
		$parentGroupSelectionLogExp = $parentGroupSelectionLogExp->reduceNegation(true);
		$sqlParentGroups = "select p1G.id_group from Groups as p1G where ".$this->getSqlWhereClauseForSelectGroups($parentGroupSelectionLogExp, 'p1G');
		
		// select children groups
		$sqlChildrenGroups =  "select cG.id_group from Groups as p2G "
				."inner join Groups_Groups GG on GG.id_group_owner = p2G.id_group "
				."inner join Groups as cG on GG.id_group = cG.id_group "
				."where ".$this->getSqlWhereClauseForSelectGroups($parentGroupSelectionLogExp, 'p2G');
		// children filtering
		if(isset($childrenGroupFilterLogExp)) {
			$childrenGroupFilterLogExp = $childrenGroupFilterLogExp->reduceNegation(true);
			$sqlChildrenGroups .= " and ".$this->getSqlWhereClauseForSelectGroups($childrenGroupFilterLogExp, 'cG');
		}
		// children remove id duplicates
		$sqlChildrenGroups .= " group by cG.id_group";
		
		// global filter on resulting list
		if(isset($groupFilterLogExp)) {
			$groupFilterLogExp = $groupFilterLogExp->reduceNegation(true);
			$globalFilter = " where ".$this->getSqlWhereClauseForSelectGroups($groupFilterLogExp, 'G');
		}
		else $globalFilter = '';
		
		return "select ".$this->getSqlColumnsForGroup('G', $this->getFieldSelectorListForGroupWithoutDetail())." from Groups as G "
			."inner join ("
			."select sG.id_group from (".$sqlParentGroups." union ".$sqlChildrenGroups.") as sG group by sG.id_group "
			.") as sG2 on sG2.id_group = G.id_group "
			.$globalFilter." order by G.wigiiNamespace, G.groupname";
	}
	
	public function getSelectedGroups($principal, $listFilter, $groupPList)
	{
		$this->executionSink()->publishStartOperation("getSelectedGroups", $principal);
		$groupPMapper = null;
		try
		{
			if(is_null($listFilter)) throw new GroupAdminServiceException('listFilter cannot be null', GroupAdminServiceException::INVALID_ARGUMENT);
			if(is_null($groupPList)) throw new GroupAdminServiceException('groupPList cannot be null', GroupAdminServiceException::INVALID_ARGUMENT);

			// group log exp
			$groupLogExp = $listFilter->getFieldSelectorLogExp();
			if(is_null($groupLogExp)) throw new GroupAdminServiceException('listFilter->getFieldSelectorLogExp cannot be null, a group selection log exp is mandatory', GroupAdminServiceException::INVALID_ARGUMENT);

			// gets module
			$mAS = $this->getModuleAdminService();
			$module = $mAS->getModuleFromSelectedGroups($principal, $groupLogExp);
			// if module is null then it means that groupLogExp returned no groups -> return 0
			if(is_null($module))
			{
				$returnValue = 0;
			}
			else
			{
				// checks authorization
				$pRights = $this->assertPrincipalAuthorizedForGetSelectedGroups($principal, $module);
				$pRightsFromDb = !isset($pRights);

				// initiliazes pagination, sorting, etc.
				$fieldSelectorList = $listFilter->getFieldSelectorList();
				$groupSortingKeyList = $listFilter->getFieldSortingKeyList();
				if($listFilter->isPaged())
				{
					$queryPaged = true;
					$pageSize = $listFilter->getPageSize();
					$offset = ($listFilter->getDesiredPageNumber() - 1) * $pageSize;
				}
				else $queryPaged = false;

				$groupPMapper = $this->getGroupPListMapper($principal, $groupPList, $pRights);
				if($queryPaged)
				{
					$listFilter->setTotalNumberOfObjects($this->getMySqlFacade()->selectPage($principal,
							$this->getSqlForGetSelectedGroups($principal, $module, $groupLogExp,
								$mAS->getDefaultPropagation($module),
								$pRightsFromDb, $fieldSelectorList, $groupSortingKeyList),
							$this->getDbAdminService()->getDbConnectionSettings($principal), $offset, $pageSize,
							$groupPMapper));
				}
				else
				{
					$this->getMySqlFacade()->selectAll($principal,
							$this->getSqlForGetSelectedGroups($principal, $module, $groupLogExp,
								$mAS->getDefaultPropagation($module),
								$pRightsFromDb, $fieldSelectorList, $groupSortingKeyList),
							$this->getDbAdminService()->getDbConnectionSettings($principal),
							$groupPMapper);
				}
				$returnValue = $groupPMapper->count();
				$groupPMapper->freeMemory();
			}
		}
		catch (GroupAdminServiceException $gaE){
			if(isset($groupPMapper)) $groupPMapper->freeMemory();
			$this->executionSink()->publishEndOperationOnError("getSelectedGroups", $gaE, $principal);
			throw $gaE;
		}
		catch (AuthorizationServiceException $asE){
			if(isset($groupPMapper)) $groupPMapper->freeMemory();
			$this->executionSink()->publishEndOperationOnError("getSelectedGroups", $asE, $principal);
			throw $asE;
		}
		catch(Exception $e)
		{
			if(isset($groupPMapper)) $groupPMapper->freeMemory();
			$this->executionSink()->publishEndOperationOnError("getSelectedGroups", $e, $principal);
			throw new GroupAdminServiceException('',GroupAdminServiceException::WRAPPING, $e);
		}
		$this->executionSink()->publishEndOperation("getSelectedGroups", $principal);
		return $returnValue;
	}
	protected function assertPrincipalAuthorizedForGetSelectedGroups($principal, $module)
	{
		if(is_null($module)) throw new GroupAdminServiceException('module can not be null', GroupAdminServiceException::INVALID_ARGUMENT);
		$autoS = $this->getAuthorizationService();
		// checks general authorization
		$pRights = $autoS->assertPrincipalAuthorized($principal, "GroupAdminService", "getSelectedGroups");
		// checks module access
		if(is_null($pRights) && is_null($principal->getModuleAccess($module))) $autoS->fail($principal, 'has no access to module '.$module->getModuleName());

		return $pRights;
	}
	protected function getSqlForGetSelectedGroups($principal, $module, $groupLogExp, $propagation, $pRightsFromDb, $fieldSelectorList=null, $groupSortingKeyList=null)
	{
		if(is_null($groupLogExp)) throw new GroupAdminServiceException('groupLogExp cannot be null', GroupAdminServiceException::INVALID_ARGUMENT);
		if(is_null($principal)) throw new GroupAdminServiceException('principal cannot be null', GroupAdminServiceException::INVALID_ARGUMENT);

		// reduces logExp and clones it.
		$groupLogExp = $groupLogExp->reduceNegation(true);

		if($pRightsFromDb)
		{
			$select_Groups_whereClause = $this->getSqlWhereClauseBuilderForSelectGroups('G1')->buildWhereClause($groupLogExp);
		}

		$sqlB = $this->getSqlWhereClauseBuilderForSelectGroups('G');
		$gCols = $this->getSqlColumnsForGroup('G', $fieldSelectorList);

		if($pRightsFromDb)
		{
			$principalId = $principal->getUserId();
			if(is_null($principalId)) throw new GroupAdminServiceException('principal attached user can not be null', GroupAdminServiceException::INVALID_ARGUMENT);			
			$principalId = $sqlB->formatValue($principalId, MySqlQueryBuilder::SQLTYPE_INT);					
			if($gCols != '') $gCols .= ', ';
			$returnValue .= "select $gCols case when GP.id_group is null then 0 else 1 end as canRead, GP.canModify, GP.canWriteElement, GP.canShareElement";
		}
		else
		{
			if($gCols == '') $gCols .= 'G.id_group as id';
			$returnValue = "select $gCols";
		}
		$returnValue .= " from Groups as G";
		if($pRightsFromDb)
		{
			$returnValue .= " left join (".
							$this->getSqlForSelectAllGroupsP($principal, $module, $principalId, $select_Groups_whereClause, $groupLogExp,
								$propagation, false, 'UGR1', 'GG1', 'GP1', 'G1')
							.") as GP on GP.id_group = G.id_group";
		}
		// adds where clause
		$sqlB->reset('G'); //reset the where clause, we use the builder only for specific request and not for a global build
		$groupLogExp = $sqlB->buildWhereClause($groupLogExp);
		if(!is_null($groupLogExp) && $groupLogExp != '') $returnValue .= " where ".$groupLogExp;

		// order by clause
		if(!is_null($groupSortingKeyList))
		{
			$sqlB->convertFieldSortingKeyListToOrderByClause($groupSortingKeyList);
			$returnValue .= ' '.$sqlB->getOrderByClause();
		} else $returnValue .=' order by G.wigiiNamespace, G.groupname';

		return $returnValue;
	}

	public function getSelectedGroupsField($principal, $fieldSelector, $groupLogExp, $parentOrChildren, $valueList)
	{
		$this->executionSink()->publishStartOperation("getSelectedGroupsField", $principal);
		$valueMapper = null;
		try
		{
			if(is_null($valueList)) throw new GroupAdminServiceException('valueList cannot be null', GroupAdminServiceException::INVALID_ARGUMENT);

			// checks authorization
			$pRights = $this->assertPrincipalAuthorizedForGetSelectedGroupsField($principal);

			// gets groups
			$valueMapper = $this->getValueListMapper($valueList, 0);
			// if special read rights then can read all groups in principal client
			if(isset($pRights))
			{
//				fput($this->getSqlForGetSelectedGroupsField($fieldSelector, $groupLogExp, $parentOrChildren));
				$this->getMySqlFacade()->selectAll($principal,
					$this->getSqlForGetSelectedGroupsField($fieldSelector, $groupLogExp, $parentOrChildren),
					$this->getDbAdminService()->getDbConnectionSettings($principal),
					$valueMapper, MYSQL_NUM);
			}
			// else only reads groups for which principal has read access (NOT SUPPORTED FOR NOW)
			else
			{
				//if not root principal, then be careful to include the readAllGroupsInWigiiNamespace + isWigiiNamespaceCreator
				throw new AuthorizationServiceException($principal->getUsername()." has not special read rights on all groups.", AuthorizationServiceException::FORBIDDEN);
			}
			$returnValue = $valueMapper->count();
			$valueMapper->freeMemory();
		}
		catch (GroupAdminServiceException $gaE){
			if(isset($valueMapper)) $valueMapper->freeMemory();
			$this->executionSink()->publishEndOperationOnError("getSelectedGroupsField", $gaE, $principal);
			throw $gaE;
		}
		catch (AuthorizationServiceException $asE){
			if(isset($valueMapper)) $valueMapper->freeMemory();
			$this->executionSink()->publishEndOperationOnError("getSelectedGroupsField", $asE, $principal);
			throw $asE;
		}
		catch(Exception $e)
		{
			if(isset($valueMapper)) $valueMapper->freeMemory();
			$this->executionSink()->publishEndOperationOnError("getSelectedGroupsField", $e, $principal);
			throw new GroupAdminServiceException('',GroupAdminServiceException::WRAPPING, $e);
		}
		$this->executionSink()->publishEndOperation("getSelectedGroupsField", $principal);
		return $returnValue;
	}
	protected function assertPrincipalAuthorizedForGetSelectedGroupsField($principal)
	{
		// checks general authorization
		return $this->getAuthorizationService()->assertPrincipalAuthorized($principal, "GroupAdminService", "getSelectedGroupsField");
	}
	protected function getSqlForGetSelectedGroupsField($fieldSelector, $groupLogExp, $parentOrChildren)
	{
		if(is_null($fieldSelector)) throw new GroupAdminServiceException("fieldSelector cannot be null", GroupAdminServiceException::INVALID_ARGUMENT);
		if(is_null($groupLogExp)) throw new GroupAdminServiceException("groupSelection LogExp cannot be null", GroupAdminServiceException::INVALID_ARGUMENT);
		$sqlB = $this->getSqlWhereClauseBuilderForSelectGroups('G');
		// reduces logExp and clones it.
		$groupLogExp = $groupLogExp->reduceNegation(true);
		$whereClause = $sqlB->buildWhereClause($groupLogExp);
		$fsl = FieldSelectorListArrayImpl::createInstance();
		$fsl->addFieldSelectorInstance($fieldSelector);
		return $this->getSqlForSelectGroups($whereClause, $parentOrChildren, 'G', $fsl);
	}

	public function getGroupsField($principal, $fieldSelector, $groupList, $parentOrChildren, $valueList, $groupLogExp=null)
	{
		$this->executionSink()->publishStartOperation("getGroupsField", $principal);
		try
		{
			if(is_null($groupList)) throw new GroupAdminServiceException('groupList cannot be null', GroupAdminServiceException::INVALID_ARGUMENT);
			// builds groupLogExp
			$ids = array();
			foreach($groupList->getListIterator() as $group)
			{
				if($group instanceof GroupP) $group = $group->getGroup();
				$ids[] = $group->getId();
			}
			if($groupLogExp){
				$temp = LogExp::createInExp(FieldSelector::createInstance("id"), $ids);
				$andExp = LogExp::createAndExp();
				$andExp->addOperand($temp);
				$andExp->addOperand($groupLogExp);
				$groupLogExp = $andExp;
			} else {
				$groupLogExp = LogExp::createInExp(FieldSelector::createInstance("id"), $ids);
			}

			// calls getSelectedGroupsField
			$returnValue = $this->getSelectedGroupsField($principal, $fieldSelector, $groupLogExp, $parentOrChildren, $valueList);
		}
		catch (GroupAdminServiceException $gaE){
			$this->executionSink()->publishEndOperationOnError("getGroupsField", $gaE, $principal);
			throw $gaE;
		}
		catch (AuthorizationServiceException $asE){
			$this->executionSink()->publishEndOperationOnError("getGroupsField", $asE, $principal);
			throw $asE;
		}
		catch(Exception $e)
		{
			$this->executionSink()->publishEndOperationOnError("getGroupsField", $e, $principal);
			throw new GroupAdminServiceException('',GroupAdminServiceException::WRAPPING, $e);
		}
		$this->executionSink()->publishEndOperation("getGroupsField", $principal);
		return $returnValue;
	}

	public function getAllUsers($principal, $groupId, $userRPList, $listFilter=null, $includePropagation=false)
	{
		$this->executionSink()->publishStartOperation("getAllUsers", $principal);
		$userRPMapper = null;
		try
		{
			// gets groupP
			if(!isset($groupId)) throw new GroupAdminServiceException("groupId cannot be null", GroupAdminServiceException::INVALID_ARGUMENT);
			$groupP = $this->getGroup($principal, $groupId, $this->getFieldSelectorListForGroupWithoutDetail());
			if(!isset($groupP)) throw new GroupAdminServiceException("group $groupId does not exist in database", GroupAdminServiceException::INVALID_ARGUMENT);

			// checks authorization
			$pRights = $this->assertPrincipalAuthorizedForGetAllUsers($principal, $groupP);

			// initiliazes pagination, sorting, etc.
			$fieldSelectorList = null;
			$userLogExp = null;
			$userSortingKeyList = null;
			$queryPaged = false;
			if(isset($listFilter))
			{
				$fieldSelectorList = $listFilter->getFieldSelectorList();
				$userLogExp = $listFilter->getFieldSelectorLogExp();
				$userSortingKeyList = $listFilter->getFieldSortingKeyList();
				if($listFilter->isPaged())
				{
					$queryPaged = true;
					$pageSize = $listFilter->getPageSize();
					$offset = ($listFilter->getDesiredPageNumber() - 1) * $pageSize;
				}
			}

			$userRPMapper = $this->getUserRPListMapper($principal, $groupId, $userRPList, $pRights);
			if($queryPaged)
			{
				$listFilter->setTotalNumberOfObjects($this->getMySqlFacade()->selectPage($principal,
						$this->getSqlForGetAllUsers($principal, $groupP->getGroup(), $fieldSelectorList, $userLogExp, $includePropagation, $userSortingKeyList),
						$this->getDbAdminService()->getDbConnectionSettings($principal), $offset, $pageSize,
						$userRPMapper));
			}
			else
			{
				$this->getMySqlFacade()->selectAll($principal,
						$this->getSqlForGetAllUsers($principal, $groupP->getGroup(), $fieldSelectorList, $userLogExp, $includePropagation, $userSortingKeyList),
						$this->getDbAdminService()->getDbConnectionSettings($principal),
						$userRPMapper);
			}
			$returnValue = $userRPMapper->count();
			$userRPMapper->freeMemory();
		}
		catch (GroupAdminServiceException $gaE){
			if(isset($userRPMapper)) $userRPMapper->freeMemory();
			$this->executionSink()->publishEndOperationOnError("getAllUsers", $gaE, $principal);
			throw $gaE;
		}
		catch (AuthorizationServiceException $asE){
			if(isset($userRPMapper)) $userRPMapper->freeMemory();
			$this->executionSink()->publishEndOperationOnError("getAllUsers", $asE, $principal);
			throw $asE;
		}
		catch(Exception $e)
		{
			if(isset($userRPMapper)) $userRPMapper->freeMemory();
			$this->executionSink()->publishEndOperationOnError("getAllUsers", $e, $principal);
			throw new GroupAdminServiceException('',GroupAdminServiceException::WRAPPING, $e);
		}
		$this->executionSink()->publishEndOperation("getAllUsers", $principal);
		return $returnValue;
	}
	protected function assertPrincipalAuthorizedForGetAllUsers($principal, $groupP)
	{
		$autoS = $this->getAuthorizationService();
		// checks general authorization
		$pRights = $autoS->assertPrincipalAuthorized($principal, "GroupAdminService", "getAllUsers");
		// check group rights
		if(is_null($groupP->getRights())) {
			$autoS->fail($principal, "has no right on the group ".$groupP->getId());
		}
		return $pRights;
	}
	protected function getSqlForGetAllUsers($principal, $group, $fieldSelectorList=null, $userLogExp=null, $includePropagation=false, $userSortingKeyList=null)
	{
		if(is_null($group)) throw new GroupAdminServiceException("group cannot be null", GroupAdminServiceException::INVALID_ARGUMENT);
		$groupId = $group->getId();
		if(is_null($groupId)) throw new GroupAdminServiceException("group->id cannot be null", GroupAdminServiceException::INVALID_ARGUMENT);
		$principalId = $principal->getUserId();
		if(is_null($principalId)) throw new GroupAdminServiceException('principal attached user can not be null', GroupAdminServiceException::INVALID_ARGUMENT);		
				
		$sqlB = $this->getUserAdminServiceImpl()->getSqlWhereClauseBuilderForSelectUsers('U2');
		$groupId = $sqlB->formatValue($groupId, MySqlQueryBuilder::SQLTYPE_INT);
		$principalId = $sqlB->formatValue($principalId, MySqlQueryBuilder::SQLTYPE_INT);
		if($includePropagation)
		{
			$propagation = $this->getModuleAdminService()->getDefaultPropagation($group->getModule());
		}
		else $propagation = 0;
		// user columns
		$uCols = $this->getUserAdminServiceImpl()->getSqlColumnsForUser('U2', $fieldSelectorList);
		if($uCols != '') $uCols .= ', ';
		// user where clause
		$whereClause = '';
		if(!is_null($userLogExp))
		{
			// reduces logExp and clones it.
			$userLogExp = $userLogExp->reduceNegation(true);
			$whereClause = $sqlB->buildWhereClause($userLogExp);
			if(!is_null($whereClause) && $whereClause != '') $whereClause = " WHERE ".$whereClause;
		}
		// order by clause
		if(!is_null($userSortingKeyList))
		{
			$sqlB->convertFieldSortingKeyListToOrderByClause($userSortingKeyList);
			$orderByClause = ' '.$sqlB->getOrderByClause();
		} else $orderByClause='';

		// builds sql
		$returnValue = "select $uCols UR2.canModify, UR2.canWriteElement, UR2.canShareElement, case when (UU2.id_user_owner is null AND ".($principal->isWigiiNamespaceCreator()? "FALSE" : "TRUE")." AND ".($principal->isReadAllUsersInWigiiNamespace() ? " U2.wigiiNamespace <> '".$principal->getWigiiNamespace()->getWigiiNamespaceName()."'" : "TRUE").") then 0 else 1 end as isOwner from (".
			$this->getSqlForSelectAllUsersR($groupId, $propagation, $User_Groups_RightsAlias='UGR', $Groups_GroupsAlias='GG', $resultAlias='UR')
			.") as UR2 inner join Users as U2 on U2.id_user = UR2.id_user left join Users_Users as UU2 on UU2.id_user = U2.id_user and UU2.id_user_owner = $principalId"
			.$whereClause.$orderByClause;
		return $returnValue;
	}

	public function setUserRight($principal, $ugr)
	{
		$this->executionSink()->publishStartOperation("setUserRight", $principal);
		try
		{
			if(is_null($ugr)) throw new GroupAdminServiceException("ugr cannot be null", GroupAdminServiceException::INVALID_ARGUMENT);
			$mySqlF = $this->getMySqlFacade();
			$dbCS = $this->getDbAdminService()->getDbConnectionSettings($principal);

			//fetches original ugr if exists
			$origUgr = null;
			$userId = $ugr->getUserId();
			$groupId = $ugr->getGroupId();
			if(is_null($userId) || is_null($groupId)) throw new GroupAdminServiceException("userId or groupId cannot be null", GroupAdminServiceException::INVALID_ARGUMENT);
			$dbRow = $mySqlF->selectOne($principal,
					$this->getSqlForGetUserGroupRight($userId, $groupId),
					$dbCS);
			if(isset($dbRow)) $origUgr = UGR::createInstance($groupId, $userId, $dbRow);

			// checks authorization
			$returnValue = 0;
			if($this->assertPrincipalAuthorizedForSetUserRight($principal, $ugr, $origUgr))
			{
				//since 18/06/2012 the check of having at least one admin rights is removed as it is not usefull
				//the namespace creator has at least access to all groups
				//groups are never deleted even if no user can access them.
//				// counts remaining admin participants in this group after deletion
//				$remainingUsers = $mySqlF->selectOne($principal,
//					//with the userId = null, we check the real number
//					$this->getSqlForCountRemainingUsersOnSetUGR($ugr, 'result'),
//					$dbCS);
//				if(isset($remainingUsers)) $remainingUsers=$remainingUsers['result'];
//				if(!isset($remainingUsers)) $remainingUsers = 0;
//				if($remainingUsers > 0){
					// updates user group rights if needed
					if($mySqlF->update($principal,
							$this->getSqlForUpdateUserGroupRight($ugr),
							$dbCS) > 0) $returnValue = 1;
					
//				} else throw new GroupAdminServiceException("Could not remove last admin participant. Group should have at least one admin participant.", GroupAdminServiceException::DATA_INTEGRITY_ERROR);
			}
		}
		catch (GroupAdminServiceException $gaE){
			$this->executionSink()->publishEndOperationOnError("setUserRight", $gaE, $principal);
			throw $gaE;
		}
		catch (AuthorizationServiceException $asE){
			$this->executionSink()->publishEndOperationOnError("setUserRight", $asE, $principal);
			throw $asE;
		}
		catch(Exception $e)
		{
			$this->executionSink()->publishEndOperationOnError("setUserRight", $e, $principal);
			throw new GroupAdminServiceException('',GroupAdminServiceException::WRAPPING, $e);
		}
		$this->executionSink()->publishEndOperation("setUserRight", $principal);
		return $returnValue;
	}
	/**
	 * returns true if update is needed else false
	 */
	protected function assertPrincipalAuthorizedForSetUserRight($principal, $ugr, $origUgr)
	{
		if(is_null($principal)) throw new GroupAdminServiceException('principal can not be null', GroupAdminServiceException::INVALID_ARGUMENT);
		if(is_null($ugr)) throw new GroupAdminServiceException('ugr can not be null', GroupAdminServiceException::INVALID_ARGUMENT);

		$autoS = $this->getAuthorizationService();
		// checks general authorization
		$autoS->assertPrincipalAuthorized($principal, "GroupAdminService", "setUserRight");
		// check rights
		$userAS = $this->getUserAdminServiceImpl();
		//user needs ownership
		$userP = $userAS->getUser($principal, $ugr->getUserId(), $userAS->getFieldSelectorListForUserWithoutDetail());
		if($userP->getRights() == null || !$userP->getRights()->isOwner()){
			$autoS->fail($principal, "has no ownership on user ".$ugr->getUserId());
		}
		
		//group needs at least the same right
		$groupP = $this->getGroup($principal, $ugr->getGroupId(), $this->getFieldSelectorListForGroupWithoutDetail());
		if($groupP->getRights() == null || (
			($ugr->canModify() && !$groupP->getRights()->canModify()) ||
			($ugr->canWriteElement() && !$groupP->getRights()->canWriteElement()) ||
			($ugr->canShareElement() && !$groupP->getRights()->canShareElement())
			)){
			$autoS->fail($principal, "try to define higner rights on group ".$ugr->getGroupId());
		}
		
		//since 06/11/2012 the administrator can transfer access rights up to what he has access to
//		//group needs X rights
//		$groupP = $this->getGroup($principal, $ugr->getGroupId(), $this->getFieldSelectorListForGroupWithoutDetail());
//		if($groupP->getRights() == null || !$groupP->getRights()->canModify()){
//			$autoS->fail($principal, "has no X right on group ".$ugr->getGroupId());
//		}
		
		//if adding Admin check p is group creator
		if($ugr->canModify() && !$principal->getGroupCreator($groupP->getGroup()->getModule())){
			$autoS->fail($principal, "is not group creator in ".$groupP->getGroup()->getModule()->getModuleName()." (->cannot add an Admin ugr)");
		}
		//if remove Admin check p is group creator
		if($origUgr != null && $origUgr->canModify() && !$ugr->canModify() && !$principal->getGroupCreator($groupP->getGroup()->getModule())){
			$autoS->fail($principal, "is not group creator in ".$groupP->getGroup()->getModule()->getModuleName()." (->cannot remove an Admin ugr)");
		}
		
		//if add/remove Admin check p is admin creator
		if(($ugr->canModify() || ($origUgr != null && $origUgr->canModify())) && !$principal->isAdminCreator()){
			$autoS->fail($principal, "is not admin creator (->cannot remove/add an Admin ugr)");
		}
		
		//check that id_user in ugr is not the current principal
		if($origUgr != null && $origUgr->canModify() && !$ugr->canModify() && $principal->getUserId()==$ugr->getUserId() && (
			($groupP->getGroup()->getWigiiNamespace()->getWigiiNamespaceName() == $principal->getWigiiNamespace()->getWigiiNamespaceName() && !$principal->getReadAllGroupsInWigiiNamespace($groupP->getGroup()->getModule())) ||
			($groupP->getGroup()->getWigiiNamespace()->getWigiiNamespaceName() != $principal->getWigiiNamespace()->getWigiiNamespaceName() && !$principal->isWigiiNamespaceCreator())
			)
			){
			$autoS->fail($principal, "cannot downgrade own admin rights on group ".$ugr->getGroupId());
		}
		
		return true;
	}

	public function removeUser($principal, $groupId, $userId)
	{
		$this->executionSink()->publishStartOperation("removeUser", $principal);
		try
		{
			if(is_null($groupId) || is_null($userId)) throw new GroupAdminServiceException("groupId or userId cannot be null",GroupAdminServiceException::INVALID_ARGUMENT);
			$mySqlF = $this->getMySqlFacade();
			$dbCS = $this->getDbAdminService()->getDbConnectionSettings($principal);

			//if UGR does not exists does nothing
			$ugr = null;
			$dbRow = $mySqlF->selectOne($principal,
					$this->getSqlForGetUserGroupRight($userId, $groupId),
					$dbCS);
			if(isset($dbRow)) $ugr = UGR::createInstance($groupId, $userId, $dbRow);
			else return 0;

			// checks authorization
			$this->assertPrincipalAuthorizedForRemoveUser($principal, $ugr);
			
			//since 18/06/2012 the check of having at least one admin rights is removed as it is not usefull
			//the namespace creator has at least access to all groups
			//groups are never deleted even if no user can access them.
//			// counts remaining admin participants in this group after deletion
//			$remainingUsers = $mySqlF->selectOne($principal,
//				$this->getSqlForCountRemainingUsersAfterRemoval($groupId, $userId, 'result'),
//				$dbCS);
//			if(isset($remainingUsers)) $remainingUsers=$remainingUsers['result'];
//			if(!isset($remainingUsers)) $remainingUsers = 0;
//
//			// if remaining users > 0 then removes participant
//			$returnValue = 0;
//			if(0 < $remainingUsers)
//			{
				$returnValue = $mySqlF->delete($principal,
					$this->getSqlForDeleteUserGroupRight($ugr),
					$dbCS);
//			}
//			// else throw DATA_INTEGRITY_ERROR
//			else throw new GroupAdminServiceException("Could not remove last admin participant. Group should have at least one admin participant.", GroupAdminServiceException::DATA_INTEGRITY_ERROR);
		}
		catch (GroupAdminServiceException $gaE){
			$this->executionSink()->publishEndOperationOnError("removeUser", $gaE, $principal);
			throw $gaE;
		}
		catch (AuthorizationServiceException $asE){
			$this->executionSink()->publishEndOperationOnError("removeUser", $asE, $principal);
			throw $asE;
		}
		catch(Exception $e)
		{
			$this->executionSink()->publishEndOperationOnError("removeUser", $e, $principal);
			throw new GroupAdminServiceException('',GroupAdminServiceException::WRAPPING, $e);
		}
		$this->executionSink()->publishEndOperation("removeUser", $principal);
		return $returnValue;
	}
	protected function assertPrincipalAuthorizedForRemoveUser($principal, $ugr)
	{
		if(is_null($principal)) throw new GroupAdminServiceException('principal can not be null', GroupAdminServiceException::INVALID_ARGUMENT);
		if(is_null($ugr)) throw new GroupAdminServiceException('ugr can not be null', GroupAdminServiceException::INVALID_ARGUMENT);

		$autoS = $this->getAuthorizationService();
		// checks general authorization
		$autoS->assertPrincipalAuthorized($principal, "GroupAdminService", "removeUser");
		// check rights
		$userAS = $this->getUserAdminServiceImpl();
		//user needs ownership
		$userP = $userAS->getUser($principal, $ugr->getUserId(), $userAS->getFieldSelectorListForUserWithoutDetail());
		if($userP->getRights() == null || !$userP->getRights()->isOwner()){
			$autoS->fail($principal, "has no ownership on user ".$ugr->getUserId());
		}
		//group needs at least the same right
		$groupP = $this->getGroup($principal, $ugr->getGroupId(), $this->getFieldSelectorListForGroupWithoutDetail());
		if($groupP->getRights() == null || (
			($ugr->canModify() && !$groupP->getRights()->canModify()) ||
			($ugr->canWriteElement() && !$groupP->getRights()->canWriteElement()) ||
			($ugr->canShareElement() && !$groupP->getRights()->canShareElement())
			)){
			$autoS->fail($principal, "try to remove higner rights on group ".$ugr->getGroupId());
		}
		
		//since 06/11/2012 the administrator can transfer access rights up to what he has access to
//		//group needs X rights
//		$groupP = $this->getGroup($principal, $ugr->getGroupId(), $this->getFieldSelectorListForGroupWithoutDetail());
//		if($groupP->getRights() == null || !$groupP->getRights()->canModify()){
//			$autoS->fail($principal, "has no X right on group ".$ugr->getGroupId());
//		}
		
		//if remove Admin check p is group creator
		if($ugr != null && $ugr->canModify() && !$principal->getGroupCreator($groupP->getGroup()->getModule())){
			$autoS->fail($principal, "is not group creator in ".$groupP->getGroup()->getModule()->getModuleName()." (->cannot remove an Admin ugr)");
		}
		//if remove Admin check p is admin creator
		if($ugr != null && $ugr->canModify() && !$principal->isAdminCreator()){
			$autoS->fail($principal, "is not admin creator (->cannot remove an Admin ugr)");
		}
		
		//check that id_user in ugr is not the current principal
		if($principal->getUserId()==$ugr->getUserId() && (
			($groupP->getGroup()->getWigiiNamespace()->getWigiiNamespaceName() == $principal->getWigiiNamespace()->getWigiiNamespaceName() && !$principal->getReadAllGroupsInWigiiNamespace($groupP->getGroup()->getModule())) ||
			($groupP->getGroup()->getWigiiNamespace()->getWigiiNamespaceName() != $principal->getWigiiNamespace()->getWigiiNamespaceName() && !$principal->isWigiiNamespaceCreator())
			)
			){
			$autoS->fail($principal, "cannot remove own rights on group ".$ugr->getGroupId());
		}
	}
	/**
	 * Counts number of remaining group administrators after participant removal
	 */
	protected function getSqlForCountRemainingUsersAfterRemoval($groupId, $userId, $countColName='result')
	{
		$sqlB = $this->getMySqlFacade()->getSqlBuilder();
		$groupId = $sqlB->formatBinExp('UGR.id_group', '=', $groupId, MySqlQueryBuilder::SQLTYPE_INT);
		$userId = $sqlB->formatBinExp('UGR.id_user', '!=', $userId, MySqlQueryBuilder::SQLTYPE_INT);
		return "select count(UGR.id_user) as $countColName from Users_Groups_Rights as UGR where $groupId and $userId and UGR.canModify=1";
	}
	protected function getSqlForCountRemainingUsersOnSetUGR($ugr, $countColName='result')
	{
		$sqlB = $this->getMySqlFacade()->getSqlBuilder();
		$groupId = $sqlB->formatBinExp('UGR.id_group', '=', $ugr->getGroupId(), MySqlQueryBuilder::SQLTYPE_INT);
		$notUserId = $sqlB->formatBinExp('UGR.id_user', '!=', $ugr->getUserId(), MySqlQueryBuilder::SQLTYPE_INT);
		if($ugr->canModify()) $addOne = "+1";
		else $addOne = "";
		return "select count(UGR.id_user) $addOne as $countColName from Users_Groups_Rights as UGR where ($groupId and $notUserId and UGR.canModify=1)";
	}

	public function removeAllUsers($principal, $groupId, $includeChildrenGroups=false)
	{
		$this->executionSink()->publishStartOperation("removeAllUsers", $principal);
		try
		{
			if(is_null($groupId)) throw new GroupAdminServiceException("groupId cannot be null",GroupAdminServiceException::INVALID_ARGUMENT);
			
			// reads existing group in database
			$origGroupP = $this->getGroup($principal, $groupId, $this->getFieldSelectorListForGroupWithoutDetail());
			if(is_null($origGroupP)) return 0;
			$origPRights = $origGroupP->getRights();
			
			// checks authorization: same rights as to delete a group
			$this->assertPrincipalAuthorizedForDeleteGroup($principal, $origGroupP, $groupId);
			
			// deletes all UGR from Group (and subgroups)
			$returnValue = $this->getMySqlFacade()->delete($principal,
					$this->getSqlForRemoveAllUsers($groupId, $includeChildrenGroups),
					$this->getDbAdminService()->getDbConnectionSettings($principal));
		}
		catch (GroupAdminServiceException $gaE){
			$this->executionSink()->publishEndOperationOnError("removeAllUsers", $gaE, $principal);
			throw $gaE;
		}
		catch (AuthorizationServiceException $asE){
			$this->executionSink()->publishEndOperationOnError("removeAllUsers", $asE, $principal);
			throw $asE;
		}
		catch(Exception $e)
		{
			$this->executionSink()->publishEndOperationOnError("removeAllUsers", $e, $principal);
			throw new GroupAdminServiceException('',GroupAdminServiceException::WRAPPING, $e);
		}
		$this->executionSink()->publishEndOperation("removeAllUsers", $principal);
		return $returnValue;
	}
	protected function getSqlForRemoveAllUsers($groupId, $includeChildrenGroups)
	{
		$sqlB = $this->getMySqlFacade()->getSqlBuilder();
		if($includeChildrenGroups) {
			$gId = $sqlB->formatBinExp('G.id_group', '=', $groupId, MySqlQueryBuilder::SQLTYPE_INT);
			$gOwnerId = $sqlB->formatBinExp('GG.id_group_owner', '=', $groupId, MySqlQueryBuilder::SQLTYPE_INT);
			return "delete from Users_Groups_Rights where id_group in (select G.id_group from Groups as G where $gId union select GG.id_group from Groups_Groups as GG where $gOwnerId)";
		}
		else {
			$gId = $sqlB->formatBinExp('id_group', '=', $groupId, MySqlQueryBuilder::SQLTYPE_INT);
			return "delete from Users_Groups_Rights where $gId";
		} 		
	}
	
	public function moveGroup($principal, $groupId, $newParentGroupId, $keepUserGroupRights=false) {
		$this->executionSink()->publishStartOperation("moveGroup", $principal);
		try
		{
			if(is_null($groupId) || is_null($newParentGroupId)) throw new GroupAdminServiceException("groupId or newParentGroupId cannot be null",GroupAdminServiceException::INVALID_ARGUMENT);
			if($groupId == $newParentGroupId) throw new GroupAdminServiceException("Parent group circularity", GroupAdminServiceException::INVALID_ARGUMENT);
					
			// checks authorization
			$this->assertPrincipalAuthorizedForMoveGroup($principal);
			
			// reads existing group in database
			$group = $this->getGroupWithoutDetail($principal, $groupId);
			$newParentGroup = $this->getGroupWithoutDetail($principal, $newParentGroupId);
			
			// checks group existence
			if(is_null($group)) throw new GroupAdminServiceException("group '$groupId' does not exist in the database", GroupAdminServiceException::INVALID_ARGUMENT);
			if(is_null($newParentGroup)) throw new GroupAdminServiceException("new parent group '$newParentGroupId' does not exist in the database", GroupAdminServiceException::INVALID_ARGUMENT);
			// checks module integrity
			if($group->getModule() !== $newParentGroup->getModule()) throw new GroupAdminServiceException('new parent group module ('.$newParentGroup->getModule()->getModuleName().') is not equal to current group module ('.$group->getModule()->getModuleName().')', GroupAdminServiceException::INVALID_ARGUMENT);
			// if keep user group rights, then namespace should match
			if($keepUserGroupRights && $group->getWigiiNamespace() !== $newParentGroup->getWigiiNamespace()) throw new GroupAdminServiceException('new parent group namespace ('.$newParentGroup->getWigiiNamespace()->getWigiiNamespaceName().') is not equal to current group namespace ('.$group->getWigiiNamespace()->getWigiiNamespaceName().')', GroupAdminServiceException::INVALID_ARGUMENT);
			
			$dbAS = $this->getDbAdminService();
			$dbCS = $dbAS->getDbConnectionSettings($principal);
			$mySqlF = $this->getMySqlFacade();
			$newGroup = clone $group;
			$newGroup->setGroupParentId($newParentGroupId);
			$newGroup->setWigiiNamespace($newParentGroup->getWigiiNamespace());
			
			// checks deep parent circularity
			if(!is_null($mySqlF->selectFirst($principal, $this->getSqlForCheckParentGroupCircularity($newGroup), $dbCS))) throw new GroupAdminServiceException("Parent group circularity", GroupAdminServiceException::INVALID_ARGUMENT);
			
			// changes parent
			$this->changeParentGroup($principal, $newGroup, GroupP::createInstance($group), $mySqlF, $dbCS);
			
			// if not keep rights, then deletes them
			if(!$keepUserGroupRights) {
				$mySqlF->delete($principal, $this->getSqlForRemoveAllUsers($groupId, true), $dbCS);
			}
			
			// update namespace of group and children groups if changed
			if($newGroup->getWigiiNamespace() !== $group->getWigiiNamespace()) {
				$mySqlF->update($principal, $this->getSqlForUpdateGroupWigiiNamespace($principal, $newGroup), $dbCS);
			}
		}
		catch (GroupAdminServiceException $gaE){
			$this->executionSink()->publishEndOperationOnError("moveGroup", $gaE, $principal);
			throw $gaE;
		}
		catch (AuthorizationServiceException $asE){
			$this->executionSink()->publishEndOperationOnError("moveGroup", $asE, $principal);
			throw $asE;
		}
		catch(Exception $e)
		{
			$this->executionSink()->publishEndOperationOnError("moveGroup", $e, $principal);
			throw new GroupAdminServiceException('',GroupAdminServiceException::WRAPPING, $e);
		}
		$this->executionSink()->publishEndOperation("moveGroup", $principal);
		return $returnValue;
	}
	protected function assertPrincipalAuthorizedForMoveGroup($principal)
	{
		if(is_null($principal)) throw new GroupAdminServiceException('principal can not be null', GroupAdminServiceException::INVALID_ARGUMENT);
	
		$autoS = $this->getAuthorizationService();
		// checks general authorization
		$autoS->assertPrincipalAuthorized($principal, "GroupAdminService", "moveGroup");
	}
	/**
	 * only updates wigiiNamespace field of group and its subgroups.
	 */
	protected function getSqlForUpdateGroupWigiiNamespace($principal, $group)
	{
		if(is_null($group)) throw new GroupAdminServiceException('group can not be null', GroupAdminServiceException::INVALID_ARGUMENT);
	
		$sqlB = $this->getMySqlFacade()->getSqlBuilder();
		$sqlB->setTableForUpdate('Groups');
	
		$sqlB->updateValue('wigiiNamespace', $group->getWigiiNamespace()->getWigiiNamespaceName(), MySqlQueryBuilder::SQLTYPE_VARCHAR);
		$sqlB->updateSysUser($principal);
	
		$whereClause = $sqlB->formatBinExp('id_group', '=', $group->getId(), MySqlQueryBuilder::SQLTYPE_INT);
		$childrenGroups = $sqlB->formatBinExp('GG.id_group_owner', '=', $group->getId(), MySqlQueryBuilder::SQLTYPE_INT);
		$whereClause .= " or id_group in (select GG.id_group from Groups_Groups as GG where $childrenGroups)";
		$sqlB->setWhereClause($whereClause);
	
		return $sqlB->getSql();
	}
	
	public function addEmailNotificationToGroups($principal, $groupIds, $email){
		return $this->updateEmailNotificationToGroups($principal, $groupIds, $email, SUPDOP_ADD);
	}
	public function eraseEmailNotificationToGroups($principal, $groupIds, $email){
		return $this->updateEmailNotificationToGroups($principal, $groupIds, $email, SUPDOP_DEL);
	}
	protected function updateEmailNotificationToGroups($principal, $groupIds, $email, $updateOp){
		$this->executionSink()->publishStartOperation("updateEmailNotificationToGroups", $principal);
		try
		{
			// check validity of groupIds
			if(!isset($groupIds)) throw new GroupAdminServiceException("groupIds cannot be null", GroupAdminServiceException::INVALID_ARGUMENT);
			
			$lf = $this->getListFilterForSelectGroupWithoutDetail($groupIds);
			if($updateOp == SUPDOP_ADD){ //check the read access for add. No check is done to unsubscribe
				$originalGroupIds = $groupIds;
				$groupList = GroupListAdvancedImpl :: createInstance(false);
				$this->getSelectedGroups($principal, $lf, $groupList);
				$groupIds = $groupList->getReadGroups()->getIds();
				if(!$groupIds) throw new GroupAdminServiceException("groupIds cannot be null. Original groupIds was: ".implode(",",$originalGroupIds).' for operator: '.$updateOp, GroupAdminServiceException::INVALID_ARGUMENT);
			}
			
			// checks authorization
			$this->assertPrincipalAuthorizedForUpdateEmailNotificationToGroups($principal);

			$mySqlF = $this->getMySqlFacade();
			$dbCS = $this->getDbAdminService()->getDbConnectionSettings($principal);
			// no lock acuirement. The subscription to one email adress to receive email notification is based on a loos risk decision. We expect that there is not enough risk that the subcribtion could be rewrited in some groups that on the same time someone was editing this particular field of the group in the administration console
			$idFieldSelector = FieldSelector::createInstance("id");
			// updates notification emails
			$returnValue = $mySqlF->updateField($principal, FieldSelector::createInstance("emailNotification"), "Groups",
								$lf->getFieldSelectorLogExp(),
								$email, $updateOp, $this->getSqlWhereClauseBuilderForSelectGroups(),
								$idFieldSelector, $dbCS, "/".ValueListArrayMapper::Natural_Separators."/", ", ");
			
		}
		catch (GroupAdminServiceException $gaE){
			$this->executionSink()->publishEndOperationOnError("updateEmailNotificationToGroups", $gaE, $principal);
			throw $gaE;
		}
		catch (AuthorizationServiceException $asE){
			$this->executionSink()->publishEndOperationOnError("updateEmailNotificationToGroups", $asE, $principal);
			throw $asE;
		}
		catch(Exception $e)
		{
			$this->executionSink()->publishEndOperationOnError("updateEmailNotificationToGroups", $e, $principal);
			throw new GroupAdminServiceException('',GroupAdminServiceException::WRAPPING, $e);
		}
		$this->executionSink()->publishEndOperation("updateEmailNotificationToGroups", $principal);
		return $returnValue;
	}
	protected function assertPrincipalAuthorizedForUpdateEmailNotificationToGroups($principal)
	{
		if(is_null($principal)) throw new GroupAdminServiceException('principal can not be null', GroupAdminServiceException::INVALID_ARGUMENT);
		$autoS = $this->getAuthorizationService();
		// checks general authorization
		$autoS->assertPrincipalAuthorized($principal, "GroupAdminService", "updateEmailNotificationToGroups");
	}
	
	public function addToEmailNotification($principal, $groupId, $emails)
	{
		$this->updateEmailNotification($principal, $groupId, $emails, SUPDOP_ADD);
	}
	public function eraseFromEmailNotification($principal, $groupId, $emails)
	{
		$this->updateEmailNotification($principal, $groupId, $emails, SUPDOP_DEL);
	}
	
	protected function updateEmailNotification($principal, $groupId, $emails, $updateOp)
	{
		$this->executionSink()->publishStartOperation("updateEmailNotification", $principal);
		try
		{
			// gets groupP
			if(!isset($groupId)) throw new GroupAdminServiceException("groupId cannot be null", GroupAdminServiceException::INVALID_ARGUMENT);
			$groupP = $this->getGroup($principal, $groupId, $this->getFieldSelectorListForGroupWithoutDetail());
			if(!isset($groupP)) throw new GroupAdminServiceException("group $groupId does not exist in database", GroupAdminServiceException::INVALID_ARGUMENT);

			// checks authorization
			$this->assertPrincipalAuthorizedForUpdateEmailNotification($principal, $groupP);

			$mySqlF = $this->getMySqlFacade();
			$dbCS = $this->getDbAdminService()->getDbConnectionSettings($principal);
			// acquires lock
			$shouldUnlock = $this->lock($principal, $groupP);
			try
			{
				$idFieldSelector = FieldSelector::createInstance("id");
				// updates notification emails
				$mySqlF->updateField($principal, FieldSelector::createInstance("emailNotification"), "Groups",
									LogExp::createEqualExp($idFieldSelector, $groupId),
									$emails, $updateOp, $this->getSqlWhereClauseBuilderForSelectGroups(),
									$idFieldSelector, $dbCS, "/".ValueListArrayMapper::Natural_Separators."/", ", ");
			}
			// releases lock
			catch(Exception $ne) {if($shouldUnlock) $this->unLock($principal, $groupP); throw $ne;}
			if($shouldUnlock) $this->unLock($principal, $groupP);
		}
		catch (GroupAdminServiceException $gaE){
			$this->executionSink()->publishEndOperationOnError("updateEmailNotification", $gaE, $principal);
			throw $gaE;
		}
		catch (AuthorizationServiceException $asE){
			$this->executionSink()->publishEndOperationOnError("updateEmailNotification", $asE, $principal);
			throw $asE;
		}
		catch(Exception $e)
		{
			$this->executionSink()->publishEndOperationOnError("updateEmailNotification", $e, $principal);
			throw new GroupAdminServiceException('',GroupAdminServiceException::WRAPPING, $e);
		}
		$this->executionSink()->publishEndOperation("updateEmailNotification", $principal);
	}
	protected function assertPrincipalAuthorizedForUpdateEmailNotification($principal, $groupP)
	{
		if(is_null($principal)) throw new GroupAdminServiceException('principal can not be null', GroupAdminServiceException::INVALID_ARGUMENT);
		if(is_null($groupP)) throw new GroupAdminServiceException('groupP can not be null', GroupAdminServiceException::INVALID_ARGUMENT);
		$autoS = $this->getAuthorizationService();
		// checks general authorization
		$autoS->assertPrincipalAuthorized($principal, "GroupAdminService", "updateEmailNotification");
		// checks read access on group
		if(is_null($groupP->getRights())) $autoS->fail($principal, "cannot read group ".$groupP->getGroup()->getId()." ".$groupP->getGroup()->getGroupName());
	}

	public function setGroupDescription($principal, $groupId, $description)
	{
		$this->executionSink()->publishStartOperation("setGroupDescription", $principal);
		try
		{
			// gets groupP
			if(!isset($groupId)) throw new GroupAdminServiceException("groupId cannot be null", GroupAdminServiceException::INVALID_ARGUMENT);
			$groupP = $this->getGroup($principal, $groupId, $this->getFieldSelectorListForGroupWithoutDetail());
			if(!isset($groupP)) throw new GroupAdminServiceException("group $groupId does not exist in database", GroupAdminServiceException::INVALID_ARGUMENT);

			// checks authorization
			$this->assertPrincipalAuthorizedForSetGroupDescription($principal, $groupP);

			$mySqlF = $this->getMySqlFacade();
			$dbCS = $this->getDbAdminService()->getDbConnectionSettings($principal);

			// acquires lock
			$shouldUnlock = $this->lock($principal, $groupP);
			try
			{
				// updates description
				$idFieldSelector = FieldSelector::createInstance("id");
				$mySqlF->updateField($principal, FieldSelector::createInstance("description"), "Groups",
									LogExp::createEqualExp($idFieldSelector, $groupId),
									$description, SUPDOP_SET, $this->getSqlWhereClauseBuilderForSelectGroups(),
									$idFieldSelector, $dbCS);
			}
			// releases lock
			catch(Exception $ne) {if($shouldUnlock) $this->unLock($principal, $groupP); throw $ne;}
			if($shouldUnlock) $this->unLock($principal, $groupP);
		}
		catch (GroupAdminServiceException $gaE){
			$this->executionSink()->publishEndOperationOnError("setGroupDescription", $gaE, $principal);
			throw $gaE;
		}
		catch (AuthorizationServiceException $asE){
			$this->executionSink()->publishEndOperationOnError("setGroupDescription", $asE, $principal);
			throw $asE;
		}
		catch(Exception $e)
		{
			$this->executionSink()->publishEndOperationOnError("setGroupDescription", $e, $principal);
			throw new GroupAdminServiceException('',GroupAdminServiceException::WRAPPING, $e);
		}
		$this->executionSink()->publishEndOperation("setGroupDescription", $principal);
	}
	protected function assertPrincipalAuthorizedForSetGroupDescription($principal, $groupP)
	{
		if(is_null($principal)) throw new GroupAdminServiceException('principal can not be null', GroupAdminServiceException::INVALID_ARGUMENT);
		if(is_null($groupP)) throw new GroupAdminServiceException('groupP can not be null', GroupAdminServiceException::INVALID_ARGUMENT);
		$autoS = $this->getAuthorizationService();
		// checks general authorization
		$autoS->assertPrincipalAuthorized($principal, "GroupAdminService", "setGroupDescription");
		// checks canModify right on group
		$pRights = $groupP->getRights();
		$group = $groupP->getGroup();
		if(is_null($pRights)) $autoS->fail($principal, "cannot read group ".$group->getId()." ".$group->getGroupName());
		if(!$pRights->canModify()) $autoS->fail($principal, "cannot modify group ".$group->getId()." ".$group->getGroupName());
	}
	public function setHtmlContent($principal, $groupId, $value)
	{
		$this->executionSink()->publishStartOperation("setHtmlContent", $principal);
		try
		{
			// gets groupP
			if(!isset($groupId)) throw new GroupAdminServiceException("groupId cannot be null", GroupAdminServiceException::INVALID_ARGUMENT);
			$groupP = $this->getGroup($principal, $groupId, $this->getFieldSelectorListForGroupWithoutDetail());
			if(!isset($groupP)) throw new GroupAdminServiceException("group $groupId does not exist in database", GroupAdminServiceException::INVALID_ARGUMENT);

			// checks authorization
			$this->assertPrincipalAuthorizedForSetHtmlContent($principal, $groupP);

			$mySqlF = $this->getMySqlFacade();
			$dbCS = $this->getDbAdminService()->getDbConnectionSettings($principal);

			// acquires lock
			$shouldUnlock = $this->lock($principal, $groupP);
			try
			{
				// updates htmlContent
				$idFieldSelector = FieldSelector::createInstance("id");
				$mySqlF->updateField($principal, FieldSelector::createInstance("htmlContent"), "Groups",
									LogExp::createEqualExp($idFieldSelector, $groupId),
									$value, SUPDOP_SET, $this->getSqlWhereClauseBuilderForSelectGroups(),
									$idFieldSelector, $dbCS);
				}
			// releases lock
			catch(Exception $ne) {if($shouldUnlock) $this->unLock($principal, $groupP); throw $ne;}
			if($shouldUnlock) $this->unLock($principal, $groupP);
		}
		catch (GroupAdminServiceException $gaE){
			$this->executionSink()->publishEndOperationOnError("setHtmlContent", $gaE, $principal);
			throw $gaE;
		}
		catch (AuthorizationServiceException $asE){
			$this->executionSink()->publishEndOperationOnError("setHtmlContent", $asE, $principal);
			throw $asE;
		}
		catch(Exception $e)
		{
			$this->executionSink()->publishEndOperationOnError("setHtmlContent", $e, $principal);
			throw new GroupAdminServiceException('',GroupAdminServiceException::WRAPPING, $e);
		}
		$this->executionSink()->publishEndOperation("setHtmlContent", $principal);
	}
	protected function assertPrincipalAuthorizedForSetHtmlContent($principal, $groupP)
	{
		if(is_null($principal)) throw new GroupAdminServiceException('principal can not be null', GroupAdminServiceException::INVALID_ARGUMENT);
		if(is_null($groupP)) throw new GroupAdminServiceException('groupP can not be null', GroupAdminServiceException::INVALID_ARGUMENT);
		$autoS = $this->getAuthorizationService();
		// checks general authorization
		$autoS->assertPrincipalAuthorized($principal, "GroupAdminService", "setHtmlContent");
		// checks write Element right on group
		$pRights = $groupP->getRights();
		$group = $groupP->getGroup();
		if(is_null($pRights)) $autoS->fail($principal, "cannot read group ".$group->getId()." ".$group->getGroupName());
		if(!$pRights->canWriteElement()) $autoS->fail($principal, "cannot write elements in group ".$group->getId()." ".$group->getGroupName());
	}
	public function setPortal($principal, $groupId, $value)
	{
		$this->executionSink()->publishStartOperation("setPortal", $principal);
		try
		{
			// gets groupP
			if(!isset($groupId)) throw new GroupAdminServiceException("groupId cannot be null", GroupAdminServiceException::INVALID_ARGUMENT);
			$groupP = $this->getGroup($principal, $groupId, $this->getFieldSelectorListForGroupWithoutDetail());
			if(!isset($groupP)) throw new GroupAdminServiceException("group $groupId does not exist in database", GroupAdminServiceException::INVALID_ARGUMENT);

			// checks authorization
			$this->assertPrincipalAuthorizedForSetPortal($principal, $groupP);

			$mySqlF = $this->getMySqlFacade();
			$dbCS = $this->getDbAdminService()->getDbConnectionSettings($principal);

			// acquires lock
			$shouldUnlock = $this->lock($principal, $groupP);
			try
			{
				// updates portal
				$idFieldSelector = FieldSelector::createInstance("id");
				$mySqlF->updateField($principal, FieldSelector::createInstance("portal"), "Groups",
									LogExp::createEqualExp($idFieldSelector, $groupId),
									$value, SUPDOP_SET, $this->getSqlWhereClauseBuilderForSelectGroups(),
									$idFieldSelector, $dbCS);
			}
			// releases lock
			catch(Exception $ne) {if($shouldUnlock) $this->unLock($principal, $groupP); throw $ne;}
			if($shouldUnlock) $this->unLock($principal, $groupP);
		}
		catch (GroupAdminServiceException $gaE){
			$this->executionSink()->publishEndOperationOnError("setPortal", $gaE, $principal);
			throw $gaE;
		}
		catch (AuthorizationServiceException $asE){
			$this->executionSink()->publishEndOperationOnError("setPortal", $asE, $principal);
			throw $asE;
		}
		catch(Exception $e)
		{
			$this->executionSink()->publishEndOperationOnError("setPortal", $e, $principal);
			throw new GroupAdminServiceException('',GroupAdminServiceException::WRAPPING, $e);
		}
		$this->executionSink()->publishEndOperation("setPortal", $principal);
	}
	protected function assertPrincipalAuthorizedForSetPortal($principal, $groupP)
	{
		if(is_null($principal)) throw new GroupAdminServiceException('principal can not be null', GroupAdminServiceException::INVALID_ARGUMENT);
		if(is_null($groupP)) throw new GroupAdminServiceException('groupP can not be null', GroupAdminServiceException::INVALID_ARGUMENT);
		$autoS = $this->getAuthorizationService();
		// checks general authorization
		$autoS->assertPrincipalAuthorized($principal, "GroupAdminService", "setPortal");
		// checks write Element right on group
		$pRights = $groupP->getRights();
		$group = $groupP->getGroup();
		if(is_null($pRights)) $autoS->fail($principal, "cannot read group ".$group->getId()." ".$group->getGroupName());
		if(!$pRights->canWriteElement()) $autoS->fail($principal, "cannot write elements in group ".$group->getId()." ".$group->getGroupName());
	}
	public function setEmailNotification($principal, $groupId, $value)
	{
		$this->executionSink()->publishStartOperation("setEmailNotification", $principal);
		try
		{
			// gets groupP
			if(!isset($groupId)) throw new GroupAdminServiceException("groupId cannot be null", GroupAdminServiceException::INVALID_ARGUMENT);
			$groupP = $this->getGroup($principal, $groupId, $this->getFieldSelectorListForGroupWithoutDetail());
			if(!isset($groupP)) throw new GroupAdminServiceException("group $groupId does not exist in database", GroupAdminServiceException::INVALID_ARGUMENT);

			// checks authorization
			$this->assertPrincipalAuthorizedForSetEmailNotification($principal, $groupP);

			$mySqlF = $this->getMySqlFacade();
			$dbCS = $this->getDbAdminService()->getDbConnectionSettings($principal);

			// acquires lock
			$shouldUnlock = $this->lock($principal, $groupP);
			try
			{
				// updates emailNotification
				$idFieldSelector = FieldSelector::createInstance("id");
				$mySqlF->updateField($principal, FieldSelector::createInstance("emailNotification"), "Groups",
									LogExp::createEqualExp($idFieldSelector, $groupId),
									$value, SUPDOP_SET, $this->getSqlWhereClauseBuilderForSelectGroups(),
									$idFieldSelector, $dbCS);
			}
			// releases lock
			catch(Exception $ne) {if($shouldUnlock) $this->unLock($principal, $groupP); throw $ne;}
			if($shouldUnlock) $this->unLock($principal, $groupP);
		}
		catch (GroupAdminServiceException $gaE){
			$this->executionSink()->publishEndOperationOnError("setEmailNotification", $gaE, $principal);
			throw $gaE;
		}
		catch (AuthorizationServiceException $asE){
			$this->executionSink()->publishEndOperationOnError("setEmailNotification", $asE, $principal);
			throw $asE;
		}
		catch(Exception $e)
		{
			$this->executionSink()->publishEndOperationOnError("setEmailNotification", $e, $principal);
			throw new GroupAdminServiceException('',GroupAdminServiceException::WRAPPING, $e);
		}
		$this->executionSink()->publishEndOperation("setEmailNotification", $principal);
	}
	protected function assertPrincipalAuthorizedForSetEmailNotification($principal, $groupP)
	{
		if(is_null($principal)) throw new GroupAdminServiceException('principal can not be null', GroupAdminServiceException::INVALID_ARGUMENT);
		if(is_null($groupP)) throw new GroupAdminServiceException('groupP can not be null', GroupAdminServiceException::INVALID_ARGUMENT);
		$autoS = $this->getAuthorizationService();
		// checks general authorization
		$autoS->assertPrincipalAuthorized($principal, "GroupAdminService", "setEmailNotification");
		// checks canModify right on group
		$pRights = $groupP->getRights();
		$group = $groupP->getGroup();
		if(is_null($pRights)) $autoS->fail($principal, "cannot read group ".$group->getId()." ".$group->getGroupName());
		if(!$pRights->canModify()) $autoS->fail($principal, "cannot modify group ".$group->getId()." ".$group->getGroupName());
	}
	public function setXmlPublish($principal, $groupId, $value)
	{
		$this->executionSink()->publishStartOperation("setXmlPublish", $principal);
		try
		{
			// gets groupP
			if(!isset($groupId)) throw new GroupAdminServiceException("groupId cannot be null", GroupAdminServiceException::INVALID_ARGUMENT);
			$groupP = $this->getGroup($principal, $groupId, $this->getFieldSelectorListForGroupWithoutDetail());
			if(!isset($groupP)) throw new GroupAdminServiceException("group $groupId does not exist in database", GroupAdminServiceException::INVALID_ARGUMENT);

			// checks authorization
			$this->assertPrincipalAuthorizedForSetXmlPublish($principal, $groupP);

			$mySqlF = $this->getMySqlFacade();
			$dbCS = $this->getDbAdminService()->getDbConnectionSettings($principal);

			// acquires lock
			$shouldUnlock = $this->lock($principal, $groupP);
			try
			{
				// updates xmlPublish
				$idFieldSelector = FieldSelector::createInstance("id");
				$mySqlF->updateField($principal, FieldSelector::createInstance("xmlPublish"), "Groups",
									LogExp::createEqualExp($idFieldSelector, $groupId),
									$value, SUPDOP_SET, $this->getSqlWhereClauseBuilderForSelectGroups(),
									$idFieldSelector, $dbCS);
			}
			// releases lock
			catch(Exception $ne) {if($shouldUnlock) $this->unLock($principal, $groupP); throw $ne;}
			if($shouldUnlock) $this->unLock($principal, $groupP);
		}
		catch (GroupAdminServiceException $gaE){
			$this->executionSink()->publishEndOperationOnError("setXmlPublish", $gaE, $principal);
			throw $gaE;
		}
		catch (AuthorizationServiceException $asE){
			$this->executionSink()->publishEndOperationOnError("setXmlPublish", $asE, $principal);
			throw $asE;
		}
		catch(Exception $e)
		{
			$this->executionSink()->publishEndOperationOnError("setXmlPublish", $e, $principal);
			throw new GroupAdminServiceException('',GroupAdminServiceException::WRAPPING, $e);
		}
		$this->executionSink()->publishEndOperation("setXmlPublish", $principal);
	}
	protected function assertPrincipalAuthorizedForSetXmlPublish($principal, $groupP)
	{
		if(is_null($principal)) throw new GroupAdminServiceException('principal can not be null', GroupAdminServiceException::INVALID_ARGUMENT);
		if(is_null($groupP)) throw new GroupAdminServiceException('groupP can not be null', GroupAdminServiceException::INVALID_ARGUMENT);
		$autoS = $this->getAuthorizationService();
		// checks general authorization
		$autoS->assertPrincipalAuthorized($principal, "GroupAdminService", "setXmlPublish");
		// checks canModify right on group
		$pRights = $groupP->getRights();
		$group = $groupP->getGroup();
		if(is_null($pRights)) $autoS->fail($principal, "cannot read group ".$group->getId()." ".$group->getGroupName());
		if(!$pRights->canModify()) $autoS->fail($principal, "cannot modify group ".$group->getId()." ".$group->getGroupName());
	}
	public function setSubscription($principal, $groupId, $value)
	{
		$this->executionSink()->publishStartOperation("setSubscription", $principal);
		try
		{
			// gets groupP
			if(!isset($groupId)) throw new GroupAdminServiceException("groupId cannot be null", GroupAdminServiceException::INVALID_ARGUMENT);
			$groupP = $this->getGroup($principal, $groupId, $this->getFieldSelectorListForGroupWithoutDetail());
			if(!isset($groupP)) throw new GroupAdminServiceException("group $groupId does not exist in database", GroupAdminServiceException::INVALID_ARGUMENT);

			// checks authorization
			$this->assertPrincipalAuthorizedForSetSubscription($principal, $groupP);

			$mySqlF = $this->getMySqlFacade();
			$dbCS = $this->getDbAdminService()->getDbConnectionSettings($principal);

			// acquires lock
			$shouldUnlock = $this->lock($principal, $groupP);
			try
			{
				// updates subscription
				$idFieldSelector = FieldSelector::createInstance("id");
				$mySqlF->updateField($principal, FieldSelector::createInstance("subscription"), "Groups",
									LogExp::createEqualExp($idFieldSelector, $groupId),
									$value, SUPDOP_SET, $this->getSqlWhereClauseBuilderForSelectGroups(),
									$idFieldSelector, $dbCS);
			}
			// releases lock
			catch(Exception $ne) {if($shouldUnlock) $this->unLock($principal, $groupP); throw $ne;}
			if($shouldUnlock) $this->unLock($principal, $groupP);
		}
		catch (GroupAdminServiceException $gaE){
			$this->executionSink()->publishEndOperationOnError("setSubscription", $gaE, $principal);
			throw $gaE;
		}
		catch (AuthorizationServiceException $asE){
			$this->executionSink()->publishEndOperationOnError("setSubscription", $asE, $principal);
			throw $asE;
		}
		catch(Exception $e)
		{
			$this->executionSink()->publishEndOperationOnError("setSubscription", $e, $principal);
			throw new GroupAdminServiceException('',GroupAdminServiceException::WRAPPING, $e);
		}
		$this->executionSink()->publishEndOperation("setSubscription", $principal);
	}
	protected function assertPrincipalAuthorizedForSetSubscription($principal, $groupP)
	{
		if(is_null($principal)) throw new GroupAdminServiceException('principal can not be null', GroupAdminServiceException::INVALID_ARGUMENT);
		if(is_null($groupP)) throw new GroupAdminServiceException('groupP can not be null', GroupAdminServiceException::INVALID_ARGUMENT);
		$autoS = $this->getAuthorizationService();
		// checks general authorization
		$autoS->assertPrincipalAuthorized($principal, "GroupAdminService", "setSubscription");
		// checks canModify right on group
		$pRights = $groupP->getRights();
		$group = $groupP->getGroup();
		if(is_null($pRights)) $autoS->fail($principal, "cannot read group ".$group->getId()." ".$group->getGroupName());
		if(!$pRights->canModify()) $autoS->fail($principal, "cannot modify group ".$group->getId()." ".$group->getGroupName());
	}

	/**
	 * @param dbRow an array straight from the DB
	 * @return instanciated Group object
	 * @precondition: dbRow contains client
	 */
	public function createGroupInstanceFromRow($principal, $dbRow, $withDetail=true)
	{
		if(is_null($dbRow))
		{
			return Group::createInstance();
		}
		else
		{
			$mas = $this->getModuleAdminService();
			//instanciates wigiiNamespace objects
			$wigiiNamespace = $this->getWigiiNamespaceAdminService()->getWigiiNamespaceForClient($principal, $dbRow['wigiiNamespace'], $dbRow['client']);
			$dbRow['wigiiNamespace'] = $wigiiNamespace;
			//instanciates module objects
			$dbRow['module'] = $mas->getModule($principal, $dbRow['module']);
			return Group::createInstance($dbRow, $withDetail);
		}
	}

	/**
	 * Returns sql columns for Group
	 * if fslOnly then uses only FieldSelectors present in FieldSelectorList, doesn't complete with mandatory fields.
	 */
	public function getSqlColumnsForGroup($tableAlias, $fieldSelectorList = null, $fslOnly=false)
	{
		if($tableAlias == '') throw new GroupAdminServiceException('tableAlias must be filled', GroupAdminServiceException::INVALID_ARGUMENT);
		if(is_null($fieldSelectorList))
		{
			$fieldSelectorList = $this->getFieldSelectorListForGroup();
		}
		$returnValue = '';
		$sqlB = $this->getSqlWhereClauseBuilderForSelectGroups($tableAlias);
		$idSelected=false; $groupnameSelected=false; $moduleSelected=false; $wigiiNamespaceSelected=false;
		foreach($fieldSelectorList->getListIterator() as $fs)
		{
			if($returnValue != '') $returnValue .= ', ';
			$returnValue .= $tableAlias.".".$sqlB->getSqlColumnNameForFieldSelector($fs);
			if($fs->getFieldName() === "id")
			{
				$returnValue .= " as id ";
				$idSelected = true;
			}
			elseif($fs->getFieldName() === "groupname") $groupnameSelected=true;
			elseif($fs->getFieldName() === "module")
			{
				$returnValue .= " as module ";
				$moduleSelected=true;
			}
			elseif($fs->getFieldName() === "wigiiNamespace") $wigiiNamespaceSelected=true;
		}
		if(!$fslOnly)
		{
			// forces id if not already selected
			if(!$idSelected)
			{
				if($returnValue != '') $returnValue .= ', ';
				$returnValue .= $tableAlias.".".$sqlB->getSqlColumnNameForFieldSelector(FieldSelector::createInstance("id"))." as id ";
			}
			// forces groupname if not already selected
			if(!$groupnameSelected)
			{
				if($returnValue != '') $returnValue .= ', ';
				$returnValue .= $tableAlias.".".$sqlB->getSqlColumnNameForFieldSelector(FieldSelector::createInstance("groupname"));
			}
			// forces module if not already selected
			if(!$moduleSelected)
			{
				if($returnValue != '') $returnValue .= ', ';
				$returnValue .= $tableAlias.".".$sqlB->getSqlColumnNameForFieldSelector(FieldSelector::createInstance("module"))." as module ";
			}
			// forces wigiiNamespace if not already selected
			if(!$wigiiNamespaceSelected)
			{
				if($returnValue != '') $returnValue .= ', ';
				$returnValue .= $tableAlias.".".$sqlB->getSqlColumnNameForFieldSelector(FieldSelector::createInstance("wigiiNamespace"));
			}
		}
		return $returnValue;
	}

	public function getSqlColumnsForUserGroupRight($tableAlias, $fieldSelectorList = null)
	{
		if($tableAlias == '') throw new GroupAdminServiceException('tableAlias must be filled', GroupAdminServiceException::INVALID_ARGUMENT);
		if(isset($fieldSelectorList))
		{
			$returnValue = '';
			foreach($fieldSelectorList->getListIterator() as $fs)
			{
				$fName = $fs->getFieldName();
				switch($fName)
				{
					case "id_user": 			if($returnValue != '') $returnValue .= ', '; $returnValue .= "$tableAlias.id_user "; break;
					case "id_group" : 			if($returnValue != '') $returnValue .= ', '; $returnValue .= "$tableAlias.id_group "; break;
					case "canModify" : 			if($returnValue != '') $returnValue .= ', '; $returnValue .= "$tableAlias.canModify "; break;
					case "canWriteElement" : 	if($returnValue != '') $returnValue .= ', '; $returnValue .= "$tableAlias.canWriteElement "; break;
					case "canShareElement" : 	if($returnValue != '') $returnValue .= ', '; $returnValue .= "$tableAlias.canShareElement "; break;
					default: throw new GroupAdminServiceException("invalid UGR attribute $fName in field selector list", GroupAdminServiceException::INVALID_ARGUMENT);
				}
			}
			return $returnValue;
		}
		else return "$tableAlias.id_user,
$tableAlias.id_group,
$tableAlias.canModify,
$tableAlias.canWriteElement,
$tableAlias.canShareElement";
	}

	// SQL library


	/**
	 * cf. design document "Group Admin Service sql queries", query id: 0909212034
	 */
	public function getSqlForSelectGroupP($principal, $module, $principalId, $groupId, $propagation,
						$User_Group_RightsAlias='UGR', $Groups_GroupsAlias='GG', $resultAlias='GP', $GroupsAlias='G')
	{
		$isInPrincipalWigiiNamespace = $this->getMySqlFacade()->getSqlBuilder()->formatBinExp($GroupsAlias.'.wigiiNamespace', '=', $principal->getWigiiNamespace()->getWigiiNamespaceName(), MySqlQueryBuilder::SQLTYPE_VARCHAR); //add by LWR
		//".($principal->isReadAllGroupsInWigiiNamespace($module) ? " or $isInPrincipalWigiiNamespace " : "")."
		$returnValue = "select
$resultAlias.id_group,
max($resultAlias.canModify) as canModify,
max($resultAlias.canWriteElement) as canWriteElement,
max($resultAlias.canShareElement) as canShareElement
from (
-- direct right with principal
select
$User_Group_RightsAlias.id_group,
$User_Group_RightsAlias.canModify,
$User_Group_RightsAlias.canWriteElement,
$User_Group_RightsAlias.canShareElement
from Users_Groups_Rights as $User_Group_RightsAlias
where $User_Group_RightsAlias.id_user = $principalId and $User_Group_RightsAlias.id_group = $groupId ";

		if($propagation == 1 or $propagation == 2)
		{
			$returnValue .= "
union distinct
-- inherited rights from parents if propagation in (1,2)
select
GG.id_group,
$User_Group_RightsAlias.canModify,
$User_Group_RightsAlias.canWriteElement,
$User_Group_RightsAlias.canShareElement
from
Groups_Groups as $Groups_GroupsAlias
inner join Users_Groups_Rights as $User_Group_RightsAlias on $Groups_GroupsAlias.id_group_owner = $User_Group_RightsAlias.id_group
where $User_Group_RightsAlias.id_user = $principalId and $Groups_GroupsAlias.id_group = $groupId ";
		}

		if($propagation == 2)
		{
			$returnValue .= "
union distinct
-- inherited read right from children if propagation 2
select
$Groups_GroupsAlias.id_group_owner,
0 as canModify,
0 as canWriteElement,
0 as canShareElement
from
Groups_Groups as $Groups_GroupsAlias
inner join Users_Groups_Rights as $User_Group_RightsAlias on $Groups_GroupsAlias.id_group = $User_Group_RightsAlias.id_group
where $User_Group_RightsAlias.id_user = $principalId and $Groups_GroupsAlias.id_group_owner = $groupId ";
		}
		if($principal->isWigiiNamespaceCreator()){
			$returnValue .= "
union distinct
-- access with X right to all groups in principal wigiiNamespace
select
$GroupsAlias.id_group,
1 as canModify,
1 as canWriteElement,
1 as canShareElement
from Groups as $GroupsAlias
where $GroupsAlias.id_group = $groupId ";
		} else if($principal->isReadAllGroupsInWigiiNamespace($module)){
			$returnValue .= "
union distinct
-- access with X right to all groups in principal wigiiNamespace
select
$GroupsAlias.id_group,
1 as canModify,
1 as canWriteElement,
1 as canShareElement
from Groups as $GroupsAlias
where $isInPrincipalWigiiNamespace and $GroupsAlias.id_group = $groupId ";
		}

		$returnValue .= "
) as $resultAlias
group by $resultAlias.id_group ";

		return $returnValue;
	}

	/**
	 * cf. design document "Group Admin Service sql queries", query id: 0909212035
	 */
	public function getSqlForSelectAllGroupsP($principal, $module, $principalId, $select_Groups_whereClause, $groupLogExp, $propagation, $includeInaccessibleParents=false,
						$User_Group_RightsAlias='UGR', $Groups_GroupsAlias='GG', $resultAlias='GP', $GroupsAlias='G')
	{
		//this function will filter the groups and children with the groupLogExp.
		//parents will always be retrived, but could be retrived with no rights if dosen't match the groupLogExp
		if(!is_null($groupLogExp)){
			$groupLogExp = $groupLogExp->reduceNegation(true);
			$groupLogExpStr = $this->getSqlWhereClauseForSelectGroups($groupLogExp, $GroupsAlias);
			if(!is_null($groupLogExpStr) && $groupLogExpStr != '') $groupLogExpStr = " and ".$groupLogExpStr." ";
		}
		$isInPrincipalWigiiNamespace = $this->getMySqlFacade()->getSqlBuilder()->formatBinExp($GroupsAlias.'.wigiiNamespace', '=', $principal->getWigiiNamespace()->getWigiiNamespaceName(), MySqlQueryBuilder::SQLTYPE_VARCHAR); //add by LWR
		$returnValue = "select
$resultAlias.id_group,
max($resultAlias.canModify) as canModify,
max($resultAlias.canWriteElement) as canWriteElement,
max($resultAlias.canShareElement) as canShareElement
from (
-- direct group access
select
$User_Group_RightsAlias.id_group,
$User_Group_RightsAlias.canModify,
$User_Group_RightsAlias.canWriteElement,
$User_Group_RightsAlias.canShareElement
from
Users_Groups_Rights as $User_Group_RightsAlias
inner join Groups as $GroupsAlias on $GroupsAlias.id_group = $User_Group_RightsAlias.id_group
where $User_Group_RightsAlias.id_user = $principalId and $select_Groups_whereClause $groupLogExpStr ";
		
		if($propagation == 1 or $propagation == 2)
		{
			$returnValue .= "
union distinct
-- child group access if propagation in (1,2)
select
$Groups_GroupsAlias.id_group,
$User_Group_RightsAlias.canModify,
$User_Group_RightsAlias.canWriteElement,
$User_Group_RightsAlias.canShareElement
from
Users_Groups_Rights as $User_Group_RightsAlias
inner join Groups_Groups as $Groups_GroupsAlias on $Groups_GroupsAlias.id_group_owner = $User_Group_RightsAlias.id_group
inner join Groups as $GroupsAlias on $GroupsAlias.id_group = $Groups_GroupsAlias.id_group
where $User_Group_RightsAlias.id_user = $principalId and $select_Groups_whereClause $groupLogExpStr ";
		}

		if($propagation == 2 && $groupLogExpStr==null)
		{
			$returnValue .= "
union distinct
-- parent group access in read only mode if propagation=2
select
$Groups_GroupsAlias.id_group_owner,
0 as canModify,
0 as canWriteElement,
0 as canShareElement
from
Users_Groups_Rights as $User_Group_RightsAlias
inner join Groups_Groups as $Groups_GroupsAlias on $Groups_GroupsAlias.id_group = $User_Group_RightsAlias.id_group
inner join Groups as $GroupsAlias on $GroupsAlias.id_group = $Groups_GroupsAlias.id_group_owner
where $User_Group_RightsAlias.id_user = $principalId and $select_Groups_whereClause $groupLogExpStr ";
		}
		else if($includeInaccessibleParents && $groupLogExpStr!=null)
		{
			//in that case there is some children accessible by propagation, with no direct right on it which needs parents as well
			//or children accessible beacause in same wigiiNamespace if readAllGroupsInWigiiNamespace
			//or if wigiiNamespace creator
			$returnValue .= "
union distinct
-- add parent group not accessible by default but contains at least one UGR in parents of group found
select
$Groups_GroupsAlias.id_group_owner,
null as canModify,
null as canWriteElement,
null as canShareElement
from
Groups as $GroupsAlias
inner join Groups_Groups as $Groups_GroupsAlias ON $Groups_GroupsAlias.id_group = $GroupsAlias.id_group
inner join Groups_Groups as {$Groups_GroupsAlias}p ON {$Groups_GroupsAlias}p.id_group = $GroupsAlias.id_group
inner join Users_Groups_Rights as $User_Group_RightsAlias ON $User_Group_RightsAlias.id_user = $principalId and {$Groups_GroupsAlias}p.id_group_owner = $User_Group_RightsAlias.id_group
where $select_Groups_whereClause $groupLogExpStr
union distinct
-- parent group not accessible by default and UGR defined on the group found
select
$Groups_GroupsAlias.id_group_owner,
null as canModify,
null as canWriteElement,
null as canShareElement
from
Groups as $GroupsAlias
inner join Groups_Groups as $Groups_GroupsAlias ON $Groups_GroupsAlias.id_group = $GroupsAlias.id_group
inner join Users_Groups_Rights as $User_Group_RightsAlias ON $User_Group_RightsAlias.id_user = $principalId and $GroupsAlias.id_group = $User_Group_RightsAlias.id_group 
where $select_Groups_whereClause $groupLogExpStr
";
		}
		else if($includeInaccessibleParents)
		{
			$returnValue .= "
union distinct
-- parent group not accessible by default
select
$Groups_GroupsAlias.id_group_owner,
null as canModify,
null as canWriteElement,
null as canShareElement
from
Users_Groups_Rights as $User_Group_RightsAlias
inner join Groups as $GroupsAlias on $GroupsAlias.id_group = $User_Group_RightsAlias.id_group
inner join Groups_Groups as $Groups_GroupsAlias on $Groups_GroupsAlias.id_group = $User_Group_RightsAlias.id_group
where $User_Group_RightsAlias.id_user = $principalId and $select_Groups_whereClause $groupLogExpStr ";
		}
		if($principal->isWigiiNamespaceCreator()){
			$returnValue .= "
union distinct
-- access with X right to all groups in principal wigiiNamespace
select
$GroupsAlias.id_group,
1 as canModify,
1 as canWriteElement,
1 as canShareElement
from Groups as $GroupsAlias
where $select_Groups_whereClause $groupLogExpStr ";
			if($includeInaccessibleParents && $groupLogExpStr != null){
				$returnValue .= "
union distinct
-- access with null right to all parent groups in principal wigiiNamespace of group found
select
$Groups_GroupsAlias.id_group_owner,
null as canModify,
null as canWriteElement,
null as canShareElement
from Groups as $GroupsAlias
inner join Groups_Groups as $Groups_GroupsAlias on $Groups_GroupsAlias.id_group = $GroupsAlias.id_group
where $select_Groups_whereClause $groupLogExpStr ";
			}
			
		} else if($principal->isReadAllGroupsInWigiiNamespace($module)){
			$returnValue .= "
union distinct
-- access with X right to all groups in principal wigiiNamespace
select
$GroupsAlias.id_group,
1 as canModify,
1 as canWriteElement,
1 as canShareElement
from Groups as $GroupsAlias
where $isInPrincipalWigiiNamespace and $select_Groups_whereClause $groupLogExpStr ";
			if($includeInaccessibleParents && $groupLogExpStr != null){
				$returnValue .= "
union distinct
-- access with null right to all parent groups in principal wigiiNamespace of group found
select
$Groups_GroupsAlias.id_group_owner,
null as canModify,
null as canWriteElement,
null as canShareElement
from Groups as $GroupsAlias
inner join Groups_Groups as $Groups_GroupsAlias on $Groups_GroupsAlias.id_group = $GroupsAlias.id_group
where $isInPrincipalWigiiNamespace and $select_Groups_whereClause $groupLogExpStr ";
			}
		}
		$returnValue .= "
) as $resultAlias
group by $resultAlias.id_group ";
//		echo alert($returnValue);
		return $returnValue;
	}

	/**
	 * cf. design document "Group Admin Service sql queries", query id: 0909212036
	 */
	public function getSqlForSelectAllUsersR($groupId, $propagation,
						$User_Groups_RightsAlias='UGR', $Groups_GroupsAlias='GG', $resultAlias='UR')
	{
		$returnValue = "select
$resultAlias.id_user,
max($resultAlias.canModify) as canModify,
max($resultAlias.canWriteElement) as canWriteElement,
max($resultAlias.canShareElement) as canShareElement
from (
-- direct right with user
select
$User_Groups_RightsAlias.id_user,
$User_Groups_RightsAlias.canModify,
$User_Groups_RightsAlias.canWriteElement,
$User_Groups_RightsAlias.canShareElement
from Users_Groups_Rights as $User_Groups_RightsAlias
where $User_Groups_RightsAlias.id_group = $groupId ";

		if($propagation == 1 or $propagation == 2)
		{
			$returnValue .= "
union distinct
-- inherited rights from parents if propagation in (1,2)
select
$User_Groups_RightsAlias.id_user,
$User_Groups_RightsAlias.canModify,
$User_Groups_RightsAlias.canWriteElement,
$User_Groups_RightsAlias.canShareElement
from
Groups_Groups as $Groups_GroupsAlias
inner join Users_Groups_Rights as $User_Groups_RightsAlias on $Groups_GroupsAlias.id_group_owner = $User_Groups_RightsAlias.id_group
where $Groups_GroupsAlias.id_group = $groupId ";
		}

		if($propagation == 2)
		{
			$returnValue .= "
union distinct
-- inherited read right from children if propagation 2
select
$User_Groups_RightsAlias.id_user,
0 as canModify,
0 as canWriteElement,
0 as canShareElement
from
Groups_Groups as $Groups_GroupsAlias
inner join Users_Groups_Rights as $User_Groups_RightsAlias on $Groups_GroupsAlias.id_group = $User_Groups_RightsAlias.id_group
where $Groups_GroupsAlias.id_group_owner = $groupId ";
		}

		$returnValue .= "
) as $resultAlias
group by $resultAlias.id_user";

		return $returnValue;
	}

	/**
	 * cf. design document "Group Admin Service sql queries", query id: 1001112242
	 * select_Groups_whereClause : where clause string using GroupsAlias
	 * parentOrChildren = -1, 0, 1, 2 = with children, only selected groups, with parents, both
	 * fieldSelectorList : Group columns selection, if null then all.
	 * if fslOnly then uses only FieldSelectors present in FieldSelectorList, doesn't complete with mandatory fields.
	 */
	public function getSqlForSelectGroups($select_Groups_whereClause, $parentOrChildren, $GroupsAlias='G', $fieldSelectorList=null, $fslOnly=false)
	{
		$returnValue = "select ".$this->getSqlColumnsForGroup('G', $fieldSelectorList, $fslOnly)."
from Groups as $GroupsAlias
where $select_Groups_whereClause ";

		if($parentOrChildren == -1 or $parentOrChildren == 2)
		{
			$returnValue .= "
union distinct
-- all children Groups
select ".$this->getSqlColumnsForGroup('CG', $fieldSelectorList)."
from Groups as $GroupsAlias
inner join Groups_Groups as GG on GG.id_group_owner = $GroupsAlias.id_group
inner join Groups as CG on CG.id_group = GG.id_group
where $select_Groups_whereClause ";
		}

		if($parentOrChildren == 1 or $parentOrChildren == 2)
		{
			$returnValue .= "
union distinct
-- all parent Groups
select ".$this->getSqlColumnsForGroup('PG', $fieldSelectorList)."
from Groups as $GroupsAlias
inner join Groups_Groups as GG on GG.id_group = $GroupsAlias.id_group
inner join Groups as PG on PG.id_group = GG.id_group_owner
where $select_Groups_whereClause ";
		}

		return $returnValue;
	}

	/**
	 * Returns a where clause chunk built from a group selection LogExp
	 */
	public function getSqlWhereClauseForSelectGroups($groupSelectionLogExp, $GroupsAlias="G")
	{
		return $this->getSqlWhereClauseBuilderForSelectGroups($GroupsAlias)->buildWhereClause($groupSelectionLogExp);;
	}
	public function getSqlWhereClauseBuilderForSelectGroups($GroupsAlias="G")
	{
		// autowired
		if(!isset($this->whereClauseBuilderForSelectGroups))
		{
			$this->whereClauseBuilderForSelectGroups = GroupWhereClauseBuilderForSelectGroups::createInstance($GroupsAlias);
		}
		else
		{
			$this->whereClauseBuilderForSelectGroups->reset($GroupsAlias);
		}
		return $this->whereClauseBuilderForSelectGroups;
	}
	public function setSqlWhereClauseBuilderForSelectGroups($whereClauseBuilderForSelectGroups)
	{
		$this->whereClauseBuilderForSelectGroups = $whereClauseBuilderForSelectGroups;
	}

	/**
	 * Converts a log exp based on InGroup/NotInGroup expressions to a Group selection log exp.
	 * if clone is true then inGroupLogExp is cloned to not touch it, else inGroupLogExp is transformed
	 * returns converted Group selection log exp
	 */
	public function convertInGroupLogExp2GroupLogExp($inGroupLogExp, $clone=true)
	{
		return $this->getInGroupLogExp2GroupLogExpConverter()->convert($inGroupLogExp, $clone);
	}
	protected function getInGroupLogExp2GroupLogExpConverter()
	{
		// autowired
		if(!isset($this->inGroupLogExp2GroupLogExpConverter))
		{
			$this->inGroupLogExp2GroupLogExpConverter = GroupInGroupLogExp2GroupLogExpConverter::createInstance();
		}
		return $this->inGroupLogExp2GroupLogExpConverter;
	}
	public function setInGroupLogExp2GroupLogExpConverter($inGroupLogExp2GroupLogExpConverter)
	{
		$this->inGroupLogExp2GroupLogExpConverter = $inGroupLogExp2GroupLogExpConverter;
	}

	/**
	 * Lock group or list of group
	 * @param object: Group or GroupPList or GroupList
	 */
	public function lock($principal, $object){
		return $this->getDbAdminService()->lock($principal, $this->getSqlTableNameForDeleteGroup(), $object);
	}

	/**
	 * UnLock group or list of group
	 * @param object: Group or GroupPList or GroupList
	 */
	public function unLock($principal, $object){
		$this->getDbAdminService()->unLock($principal, $this->getSqlTableNameForDeleteGroup(), $object);
	}

	public function getOrCreateSubGroupByName($principal, $parentGroupId, $groupName) {
		if(!isset($parentGroupId)) throw new GroupAdminServiceException('parentGroupId cannot be null', GroupAdminServiceException::INVALID_ARGUMENT);
		if(empty($groupName)) throw new GroupAdminServiceException('groupName cannot be null', GroupAdminServiceException::INVALID_ARGUMENT);		
		// 1. fetch group in db
		$groupPList = GroupPListArrayImpl::createInstance();
		$listFilter = lf($this->getFieldSelectorListForGroupWithoutDetail(), 
				lxAnd(lxEq(fs('groupname'), $groupName), lxEq(fs('id_group_parent'), $parentGroupId)), 
				null, 1, 1);
		$this->getSelectedGroups($principal, $listFilter, $groupPList);
		switch($listFilter->getTotalNumberOfObjects()) {
			case 0:
				// 2. reads parent group
				$parentGroup = $this->getGroupWithoutDetail($principal, $parentGroupId);
				if(!isset($parentGroup)) throw new GroupAdminServiceException("parent group $parentGroupId does not exist in the database", GroupAdminServiceException::INVALID_ARGUMENT);
				// 3. creates new group
				$groupP = GroupP::createInstance(Group::createInstance(array(
						'groupname' => $groupName,
						'wigiiNamespace' => $parentGroup->getWigiiNamespace(),
						'module' => $parentGroup->getModule(),
						'id_group_parent' => $parentGroup->getId()
				)));
				$this->persistGroup($principal, $groupP->getGroup());
				$groupP->setRights(PrincipalRights::createInstance(array('canWriteElement' => true)));
				break;
			case 1: $groupP = reset($groupPList->getListIterator()); break;
			default: throw new GroupAdminServiceException("group $groupName is not a unique child of group $parentGroupId", GroupAdminServiceException::INVALID_ARGUMENT);
		}
		return $groupP;
	}
}

// SQL builders

class GroupSqlBuilderForInsertLeafInGroupGroup extends MySqlQueryBuilder implements RowList
{
	private $mysqlF;
	private $dbAS;
	protected $groupId;
	private $parentCount;

	// Object life cycle

	public static function createInstance($principal, $group)
	{
		$returnValue = new GroupSqlBuilderForInsertLeafInGroupGroup();
		$returnValue->reset($principal, $group);
		return $returnValue;
	}
	public function reset($principal, $group)
	{
		parent::reset();
		if(is_null($group)) throw new GroupAdminServiceException('group can not be null', GroupAdminServiceException::INVALID_ARGUMENT);
		$this->groupId = $group->getId();
		$this->parentCount = 0;
		$parentGroupId = $group->getGroupParentId();
		if(is_null($parentGroupId)) throw new GroupAdminServiceException('group->parentGroupId can not be null', GroupAdminServiceException::INVALID_ARGUMENT);

		// inserts leaf
		$this->setTableForInsert("Groups_Groups");
		$this->insertValue("id_group_owner", $parentGroupId, MySqlQueryBuilder::SQLTYPE_INT);
		$this->insertValue("id_group", $this->groupId, MySqlQueryBuilder::SQLTYPE_INT);

		// inserts new leaf reference on all grand parents
		$this->getMySqlFacade()->selectAll($principal,
			$this->getSqlForSelectAllParentIds($parentGroupId),
			$this->getDbAdminService()->getDbConnectionSettings($principal),
			$this);
	}

	// dependency injection

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

	// Sql builder implementation

	protected function getSqlForSelectAllParentIds($groupId)
	{
		$id_group = $this->formatBinExp("GG.id_group","=", $groupId, MySqlQueryBuilder::SQLTYPE_INT);
		return "select GG.id_group_owner from Groups_Groups as GG where $id_group group by GG.id_group_owner";
	}

	// RowList implementation

	public function addRow($row)
	{
		if(isset($row))
		{
			$parentGroupId = $row["id_group_owner"];
			if(isset($parentGroupId))
			{
				$this->insertMoreRecord();
				$this->insertMoreValue($parentGroupId, MySqlQueryBuilder::SQLTYPE_INT);
				$this->insertMoreValue($this->groupId, MySqlQueryBuilder::SQLTYPE_INT);
				$this->parentCount++;
			}
		}
	}
	public function getListIterator()
	{
		throw new GroupAdminServiceException("GroupSqlBuilderForInsertLeafInGroupGroup::RowList is write oly", GroupAdminServiceException::UNSUPPORTED_OPERATION);
	}
	public function isEmpty()
	{
		return ($this->parentCount == 0);
	}
	public function count()
	{
		return $this->parentCount;
	}
}

class GroupSqlBuilderToConnectGroupToParentInGroupGroup extends GroupSqlBuilderForInsertLeafInGroupGroup implements RowList
{
	private $fillChildrenState;
	private $children;

	// Object life cycle

	public static function createInstance($principal, $group)
	{
		$returnValue = new GroupSqlBuilderToConnectGroupToParentInGroupGroup();
		$returnValue->reset($principal, $group);
		return $returnValue;
	}
	public function reset($principal, $group)
	{
		if(is_null($group)) throw new GroupAdminServiceException('group can not be null', GroupAdminServiceException::INVALID_ARGUMENT);
		$this->groupId = $group->getId();
		// gets group children
		$this->fillChildrenState = true;
		unset($this->children);
		$this->getMySqlFacade()->selectAll($principal,
			$this->getSqlForSelectAllChildrenIds($this->groupId),
			$this->getDbAdminService()->getDbConnectionSettings($principal),
			$this);

		// connects group (with children) to parent
		$this->fillChildrenState = false;
		parent::reset($principal, $group);

		// connects children to parent
		$parentGroupId = $group->getGroupParentId();
		if(isset($parentGroupId))
		{
			if(isset($this->children))
			{
				foreach($this->children as $groupId)
				{
					$this->insertMoreRecord();
					$this->insertMoreValue($parentGroupId, MySqlQueryBuilder::SQLTYPE_INT);
					$this->insertMoreValue($groupId, MySqlQueryBuilder::SQLTYPE_INT);
				}
			}
		}
	}

	// Sql builder implementation

	protected function getSqlForSelectAllChildrenIds($groupId)
	{
		$id_group_owner = $this->formatBinExp("GG.id_group_owner","=", $groupId, MySqlQueryBuilder::SQLTYPE_INT);
		return "select GG.id_group from Groups_Groups as GG where $id_group_owner group by GG.id_group";
	}

	// RowList implementation

	public function addRow($row)
	{
		if($this->fillChildrenState)
		{
			if(isset($row))
			{
				$groupId = $row["id_group"];
				if(isset($groupId))
				{
					$this->children[$groupId] = $groupId;
				}
			}
		}
		else
		{
			parent::addRow($row);
			if(isset($row))
			{
				$parentGroupId = $row["id_group_owner"];
				if(isset($parentGroupId))
				{
					if(isset($this->children))
					{
						foreach($this->children as $groupId)
						{
							$this->insertMoreRecord();
							$this->insertMoreValue($parentGroupId, MySqlQueryBuilder::SQLTYPE_INT);
							$this->insertMoreValue($groupId, MySqlQueryBuilder::SQLTYPE_INT);
						}
					}
				}
			}
		}
	}
}

class GroupSqlBuilderToDisconnectGroupFromParentInGroupGroup extends MySqlQueryBuilder implements RowList
{
	private $mysqlF;
	private $dbAS;
	private $groupGroupIds;
	private $groupGroupCount;

	// Object life cycle

	public static function createInstance($principal, $group)
	{
		$returnValue = new GroupSqlBuilderToDisconnectGroupFromParentInGroupGroup();
		$returnValue->reset($principal, $group);
		return $returnValue;
	}
	public function reset($principal, $group)
	{
		parent::reset();
		if(is_null($group)) throw new GroupAdminServiceException('group can not be null', GroupAdminServiceException::INVALID_ARGUMENT);
		$groupId = $group->getId();

		// gets all children ids to disconnect
		$this->groupGroupIds = '';
		$this->groupGroupCount = 0;
		$this->getMySqlFacade()->selectAll($principal,
			$this->getSqlForSelectAllChildrenIdsToDisconnect($groupId),
			$this->getDbAdminService()->getDbConnectionSettings($principal),
			$this);

		// prepares delete query
		$this->setTableForDelete('Groups_Groups');
		$groupId = $this->formatValue($groupId, MySqlQueryBuilder::SQLTYPE_INT);
		$this->setWhereClause("id_group = $groupId ".
			($this->groupGroupCount > 0 ? "or id_relation_group in (".$this->groupGroupIds.")" : ''));
	}

	// dependency injection

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

	// Sql builder implementation

	protected function getSqlForSelectAllChildrenIdsToDisconnect($groupId)
	{
		return "select
GG.id_relation_group
from Groups_Groups GG
inner join Groups_Groups as GG1 on GG.id_group_owner = GG1.id_group_owner and GG1.id_group = $groupId
inner join Groups_Groups as GG2 on GG.id_group = GG2.id_group and GG2.id_group_owner = $groupId";
	}

	// RowList implementation

	public function addRow($row)
	{
		if(isset($row))
		{
			$groupGroupId = $row["id_relation_group"];
			if(isset($groupGroupId))
			{
				if($this->groupGroupCount > 0)
				{
					$this->groupGroupIds .= ", ";
				}
				$this->groupGroupIds .= $groupGroupId;
				$this->groupGroupCount++;
			}
		}
	}
	public function getListIterator()
	{
		throw new GroupAdminServiceException("GroupSqlBuilderToDisconnectGroupFromParentInGroupGroup::RowList is write oly", GroupAdminServiceException::UNSUPPORTED_OPERATION);
	}
	public function isEmpty()
	{
		return ($this->groupGroupCount == 0);
	}
	public function count()
	{
		return $this->groupGroupCount;
	}
}

class GroupWhereClauseBuilderForSelectGroups extends FieldSelectorLogExpSqlBuilder
{
	private $tableAlias;

	// Object lifecycle

	public static function createInstance($groupTableAlias='G')
	{
		$returnValue = new GroupWhereClauseBuilderForSelectGroups();
		$returnValue->reset($groupTableAlias);
		return $returnValue;
	}

	public function reset($groupTableAlias='G')
	{
		parent::reset();
		$this->tableAlias = $groupTableAlias;
	}

	// FieldSelectorLogExpBuilder implementation

	public function getTableAliasForFieldSelector($fieldSelector)
	{
		return $this->tableAlias;
	}

	public function getSqlColumnNameForFieldSelector($fieldSelector)
	{
		if(is_null($fieldSelector)) throw new GroupAdminServiceException("fieldSelector cannot be null", GroupAdminServiceException::INVALID_ARGUMENT);
		$fName = $fieldSelector->getFieldName();
		switch($fName)
		{
			case "id": 					return "id_group";
			case "groupname" : 			return "groupname";
			case "module" : 			return "modulename";
			case "wigiiNamespace" : 	return "wigiiNamespace";
			case "description" : 		return "description";
			case "portal" : 			return "portal";
			case "htmlContent" : 		return "htmlContent";
			case "id_group_parent" : 	return "id_group_parent";
			case "subscription" : 		return "subscription";
			case "emailNotification" : 	return "emailNotification";
			case "xmlPublish" : 		return "xmlPublish";
			case "sys_date" : 			return "sys_date";
			case "sys_creationDate" : 	return "sys_creationDate";
			case "sys_user" : 			return "sys_user";
			case "sys_username" : 		return "sys_username";
			case "sys_creationUser" : 	return "sys_creationUser";
			case "sys_creationUsername":return "sys_creationUsername";
			case "sys_lockMicroTime" : 	return "sys_lockMicroTime";
			case "sys_lockId" : 		return "sys_lockId";
			default: throw new GroupAdminServiceException("invalid group attribute $fName in field selector LogExp", GroupAdminServiceException::INVALID_ARGUMENT);
		}
	}

	/**
	 * Returns MySqlQueryBuilder::SQLTYPE_? for the given fieldSelector
	 * default throws MySqlQueryBuilderException::UNSUPPORTED_OPERATION,
	 * subclass should provide implementation
	 */
	public function getSqlDataTypeForFieldSelector($fieldSelector)
	{
		if(is_null($fieldSelector)) throw new GroupAdminServiceException("fieldSelector cannot be null", GroupAdminServiceException::INVALID_ARGUMENT);
		$fName = $fieldSelector->getFieldName();
		switch($fName)
		{
			case "id": 					return MySqlQueryBuilder::SQLTYPE_INT;
			case "groupname" : 			return MySqlQueryBuilder::SQLTYPE_VARCHAR;
			case "module" : 			return MySqlQueryBuilder::SQLTYPE_VARCHAR;
			case "wigiiNamespace" : 	return MySqlQueryBuilder::SQLTYPE_VARCHAR;
			case "description" : 		return MySqlQueryBuilder::SQLTYPE_TEXT;
			case "portal" : 			return MySqlQueryBuilder::SQLTYPE_TEXT;
			case "htmlContent" : 		return MySqlQueryBuilder::SQLTYPE_TEXT;
			case "id_group_parent" : 	return MySqlQueryBuilder::SQLTYPE_INT;
			case "subscription" : 		return MySqlQueryBuilder::SQLTYPE_TEXT;
			case "emailNotification" : 	return MySqlQueryBuilder::SQLTYPE_TEXT;
			case "xmlPublish" : 		return MySqlQueryBuilder::SQLTYPE_TEXT; //SQLTYPE_VARCHAR;
			case "sys_date" : 			return MySqlQueryBuilder::SQLTYPE_INT;
			case "sys_creationDate" : 	return MySqlQueryBuilder::SQLTYPE_INT;
			case "sys_user" : 			return MySqlQueryBuilder::SQLTYPE_INT;
			case "sys_username" : 		return MySqlQueryBuilder::SQLTYPE_VARCHAR;
			case "sys_creationUser" : 	return MySqlQueryBuilder::SQLTYPE_INT;
			case "sys_creationUsername":return MySqlQueryBuilder::SQLTYPE_VARCHAR;
			case "sys_lockMicroTime" : 	return MySqlQueryBuilder::SQLTYPE_INT;
			case "sys_lockId" : 		return MySqlQueryBuilder::SQLTYPE_VARCHAR;
			default: throw new GroupAdminServiceException("invalid group attribute $fName in field selector LogExp", GroupAdminServiceException::INVALID_ARGUMENT);
		}
	}

	public function actOnInGroup($inGroupLogExp)
	{
		$inGroupLogExp->getGroupSelectionLogExp()->acceptLogExpVisitor($this);
	}
	public function actOnNotInGroup($notInGroupLogExp)
	{
		/* nothing to do; cuts tree visit. */
	}
	public function actOnAndExp($andLogExp)
	{
		$ops = $andLogExp->getOperands();
		if(isset($ops))
		{
			$firstOp = true;
			foreach($ops as $logExp)
			{
				if($firstOp) $firstOp = false;
				elseif(!($logExp instanceof LogExpNotInGroup))
				{
					$this->whereClause .= " AND ";
				}
				$logExp->acceptLogExpVisitor($this);
			}
		}
	}
	public function actOnOrExp($orLogExp)
	{
		$ops = $orLogExp->getOperands();
		if(isset($ops))
		{
			$firstOp = true;
			$this->whereClause .= " (";
			foreach($ops as $logExp)
			{
				if($firstOp) $firstOp = false;
				elseif(!($logExp instanceof LogExpNotInGroup))
				{
					$this->whereClause .= " OR ";
				}
				$logExp->acceptLogExpVisitor($this);
			}
			$this->whereClause .= ") ";
		}
	}
	public function actOnNotExp($notLogExp)
	{
		$logExp = $notLogExp->getLogExp();
		if(isset($logExp) && !($logExp instanceof LogExpNotInGroup))
		{
			$this->whereClause .= " NOT(";
			$logExp->acceptLogExpVisitor($this);
			$this->whereClause .= ") ";
		}
	}
}

/**
 * Converts LogExp containing InGroup and NotInGroup expressions to a Group selection LogExp
 */
class GroupInGroupLogExp2GroupLogExpConverter implements LogExpVisitor
{
	// Object lifecycle

	public static function createInstance()
	{
		return new GroupInGroupLogExp2GroupLogExpConverter();
	}

	// Service implementation

	/**
 	 * Converts LogExp containing InGroup and NotInGroup expressions to a Group selection LogExp
 	 * if clone then inGroupLogExp is cloned to not destroy it
 	 * returns converted logexp
 	 * throws GroupAdminServiceException in case of error
 	 */
	public function convert($inGroupLogExp, $clone=true)
	{
		if(is_null($inGroupLogExp)) throw new GroupAdminServiceException("inGroupLogExp cannot be null", GroupAdminServiceException::INVALID_ARGUMENT);
		return $inGroupLogExp->reduceNegation($clone)->acceptLogExpVisitor($this);
	}

	// LogExp visitor implementation

	public function actOnAndExp($andLogExp)
	{
		$returnValue = LogExp::createOrExp();
		foreach($andLogExp->getOperands() as $logExp)
		{
			$clogExp = $logExp->acceptLogExpVisitor($this);
			if(isset($clogExp))
			{
				$returnValue->addOperand($clogExp);
			}
		}
		return $returnValue;
	}
	public function actOnOrExp($orLogExp)
	{
		$returnValue = LogExp::createOrExp();
		foreach($orLogExp->getOperands() as $logExp)
		{
			$clogExp = $logExp->acceptLogExpVisitor($this);
			if(isset($clogExp))
			{
				$returnValue->addOperand($clogExp);
			}
		}
		return $returnValue;
	}
	public function actOnInGroup($inGroupLogExp)
	{
		return $inGroupLogExp->getGroupSelectionLogExp();
	}
	public function actOnNotInGroup($notInGroupLogExp)
	{
		/* cuts tree visit */
	}

	public function actOnNotExp($notLogExp)
	{
		throw new GroupAdminServiceException("NOT exp should not exist anymore after negation has been reduced", GroupAdminServiceException::UNEXPECTED_ERROR);
	}
	public function actOnSmaller($obj, $val)
	{
		throw new GroupAdminServiceException("binary expression is not supported in InGroup and NotInGroup expressions", GroupAdminServiceException::UNSUPPORTED_OPERATION);
	}
	public function actOnSmallerEq($obj, $val)
	{
		throw new GroupAdminServiceException("binary expression is not supported in InGroup and NotInGroup expressions", GroupAdminServiceException::UNSUPPORTED_OPERATION);
	}
	public function actOnGreater($obj, $val)
	{
		throw new GroupAdminServiceException("binary expression is not supported in InGroup and NotInGroup expressions", GroupAdminServiceException::UNSUPPORTED_OPERATION);
	}
	public function actOnGreaterEq($obj, $val)
	{
		throw new GroupAdminServiceException("binary expression is not supported in InGroup and NotInGroup expressions", GroupAdminServiceException::UNSUPPORTED_OPERATION);
	}
	public function actOnEqual($obj, $val)
	{
		throw new GroupAdminServiceException("binary expression is not supported in InGroup and NotInGroup expressions", GroupAdminServiceException::UNSUPPORTED_OPERATION);
	}
	public function actOnNotEqual($obj, $val)
	{
		throw new GroupAdminServiceException("binary expression is not supported in InGroup and NotInGroup expressions", GroupAdminServiceException::UNSUPPORTED_OPERATION);
	}
	public function actOnIn($obj, $vals)
	{
		throw new GroupAdminServiceException("binary expression is not supported in InGroup and NotInGroup expressions", GroupAdminServiceException::UNSUPPORTED_OPERATION);
	}
	public function actOnNotIn($obj, $vals)
	{
		throw new GroupAdminServiceException("binary expression is not supported in InGroup and NotInGroup expressions", GroupAdminServiceException::UNSUPPORTED_OPERATION);
	}
	public function actOnLike($obj, $val)
	{
		throw new GroupAdminServiceException("binary expression is not supported in InGroup and NotInGroup expressions", GroupAdminServiceException::UNSUPPORTED_OPERATION);
	}
	public function actOnMatchAgainst($obj, $val)
	{
		throw new GroupAdminServiceException("binary expression is not supported in InGroup and NotInGroup expressions", GroupAdminServiceException::UNSUPPORTED_OPERATION);
	}
	public function actOnNotLike($obj, $val)
	{
		throw new GroupAdminServiceException("binary expression is not supported in InGroup and NotInGroup expressions", GroupAdminServiceException::UNSUPPORTED_OPERATION);
	}
}

/**
 * Rebuilds Groups_Groups table given a GroupListTreeArrayImpl
 */
class GroupsGroupsBuilder implements TreeInDepthVisitor
{
	private $principal;
	protected $groupAdminServiceImpl;

	// Object lifecycle

	public static function createInstance($groupAdminServiceImpl)
	{
		$returnValue = new self();
		$returnValue->groupAdminServiceImpl = $groupAdminServiceImpl;
		$returnValue->reset();
		return $returnValue;
	}
	public function reset()
	{
		unset($this->principal);
	}

	// Dependency injection

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
	public function setAuthorizationService($authorizationService)
	{
		$this->authoS = $authorizationService;
	}
	public function getAuthorizationService()
	{
		// autowired
		if(!isset($this->authoS))
		{
			$this->authoS = ServiceProvider::getAuthorizationService();
		}
		return $this->authoS;
	}

	// Service implementation

	/**
	 * Rebuilds Groups_Groups based on the given GroupListTreeArrayImpl
	 * In this implementation only the rootPrincipal can execute this method
	 */
	public function run($principal, $groupListTreeArrayImpl)
	{
		$this->assertPrincipalAuthorizedForRun($principal);
		if(is_null($groupListTreeArrayImpl)) throw new GroupAdminServiceException("groupListTreeArrayImpl cannot be null", GroupAdminServiceException::INVALID_ARGUMENT);
		$this->setPrincipal($principal);

		// deletes all rows in Groups_Groups
		$dbAS = $this->getDbAdminService();
		$dbAS->deleteRows($principal,
			$this->getSqlTableNameForDeleteGroupsGroups(), "true",
			$dbAS->getDbConnectionSettings($principal));

		// rebuilds Groups_Groups
		$groupListTreeArrayImpl->visitInDepth($this);
	}
	protected function assertPrincipalAuthorizedForRun($principal)
	{
		$this->getAuthorizationService()->assertPrincipalIsRoot($principal);
	}
	protected function getSqlTableNameForDeleteGroupsGroups()
	{
		return "Groups_Groups";
	}

	protected function setPrincipal($principal)
	{
		$this->principal = $principal;
	}
	protected function getPrincipal()
	{
		return $this->principal;
	}

	// Tree Visitor

	public function actOnTreeNode($object, $depth, $numberOfChildren)
	{
		// if parent is set
		if(!is_null($object->getGroupParentId()))
		{
			$p = $this->getPrincipal();
			$this->getMySqlFacade()->insertMultiple($p,
				$this->groupAdminServiceImpl->getSqlForInsertLeafInGroupGroup($p, $object),
				$this->getDbAdminService()->getDbConnectionSettings($p));
		}
		return true;
	}
	public function actAfterTreeNode($object, $depth, $visitedAllChildren)
	{
		return true;
	}
}