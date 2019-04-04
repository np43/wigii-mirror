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
 * Wigii UserAdminService implementation
 * Created by CWE on 2 juin 09
 * Modified by Medair in 2016 for maintenance purposes (see SVN log for details)
 */
class UserAdminServiceImpl implements UserAdminService
{
	private $_debugLogger;
	private $_executionSink;
	private $userCache;
	private $wigiiNamespaceAS;
	private $moduleAS;
	private $clientAS;
	private $dbAS;
	private $groupAdminServiceImpl;
	private $mysqlF;
	private $authoS;
	private $argVal;
	private $userListMapper;
	private $userPListMapper;
	private $groupRPListMapper;
	private $userURPListMapper;
	private $fslForUserWithoutDetail;
	private $fslForUser;


	private function debugLogger()
	{
		if(!isset($this->_debugLogger))
		{
			$this->_debugLogger = DebugLogger::getInstance("UserAdminServiceImpl");
		}
		return $this->_debugLogger;
	}
	private function executionSink()
	{
		if(!isset($this->_executionSink))
		{
			$this->_executionSink = ExecutionSink::getInstance("UserAdminServiceImpl");
		}
		return $this->_executionSink;
	}
	public function __construct()
	{
		$this->debugLogger()->write("creating instance");
	}


	// Dependency injection


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
	public function setClientAdminService($clientAdminService)
	{
		$this->clientAS = $clientAdminService;
	}
	protected function getClientAdminService()
	{
		// autowired
		if(!isset($this->clientAS))
		{
			$this->clientAS = ServiceProvider::getClientAdminService();
		}
		return $this->clientAS;
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
	public function setGroupAdminServiceImpl($groupAdminServiceImpl)
	{
		if(isset($groupAdminServiceImpl) && !($groupAdminServiceImpl instanceof GroupAdminServiceImpl))
		{
			throw new UserAdminServiceException("UserAdminServiceImpl depends on GroupAdminServiceImpl or a subclass", UserAdminServiceException::INVALID_ARGUMENT);
		}
		$this->groupAdminServiceImpl = $groupAdminServiceImpl;
	}
	protected function getGroupAdminServiceImpl()
	{
		// autowired
		if(!isset($this->groupAdminServiceImpl))
		{
			$this->setGroupAdminServiceImpl(ServiceProvider::getGroupAdminService());
		}
		return $this->groupAdminServiceImpl;
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
	public function setUserListMapper($userListMapper)
	{
		$this->userListMapper = $userListMapper;
	}
	protected function getUserListMapper($principal, $userList)
	{
		// autowired
		if(!isset($this->userListMapper))
		{
			$this->userListMapper = UserListMapper::createInstance($this, $principal, $userList);
		}
		else
		{
			if($this->userListMapper->isLockedForUse()) {
				return UserListMapper::createInstance($this, $principal, $userList);
			}
			else $this->userListMapper->reset($principal, $userList);
		}
		return $this->userListMapper;
	}
	public function setUserPListMapper($userPListMapper)
	{
		$this->userPListMapper = $userPListMapper;
	}
	protected function getUserPListMapper($principal, $userPList, $pRights=null)
	{
		// autowired
		if(!isset($this->userPListMapper))
		{
			$this->userPListMapper = UserPListMapper::createInstance($this, $principal, $userPList, $pRights);
		}
		else
		{
			if($this->userPListMapper->isLockedForUse()) {
				return UserPListMapper::createInstance($this, $principal, $userPList, $pRights);
			}
			else $this->userPListMapper->reset($principal, $userPList, $pRights);
		}
		return $this->userPListMapper;
	}
	public function setGroupRPListMapper($groupRPListMapper)
	{
		$this->groupRPListMapper = $groupRPListMapper;
	}
	protected function getGroupRPListMapper($principal, $userId, $groupRPList, $colPrefixR='GR', $colPrefixP='GP', $pRights=null)
	{
		// autowired
		if(!isset($this->groupRPListMapper))
		{
			$this->groupRPListMapper = GroupRPListMapper::createInstance($this->getGroupAdminServiceImpl(), $principal, $userId, $groupRPList, $colPrefixR, $colPrefixP, $pRights);
		}
		else
		{
			if($this->groupRPListMapper->isLockedForUse()) {
				return GroupRPListMapper::createInstance($this->getGroupAdminServiceImpl(), $principal, $userId, $groupRPList, $colPrefixR, $colPrefixP, $pRights);
			}
			else $this->groupRPListMapper->reset($principal, $userId, $groupRPList, $colPrefixR, $colPrefixP, $pRights);
		}
		return $this->groupRPListMapper;
	}
	public function setUserURPListMapper($userURPListMapper)
	{
		$this->userURPListMapper = $userURPListMapper;
	}
	protected function getUserURPListMapper($principal, $userId, $userURPList, $pRights = null, $pRightPrefix='P')
	{
		// autowired
		if(!isset($this->userURPListMapper))
		{
			$this->userURPListMapper = UserURPListMapper::createInstance($this, $principal, $userId, $userURPList, $pRights, $pRightPrefix);
		}
		else
		{
			if($this->userURPListMapper->isLockedForUse()) {
				return UserURPListMapper::createInstance($this, $principal, $userId, $userURPList, $pRights, $pRightPrefix);
			}
			else $this->userURPListMapper->reset($principal, $userId, $userURPList, $pRights, $pRightPrefix);
		}
		return $this->userURPListMapper;
	}

	public function setFieldSelectorListForUserWithoutDetail($fieldSelectorList)
	{
		$this->fslForUserWithoutDetail = $fieldSelectorList;
	}
	public function getFieldSelectorListForUserWithoutDetail()
	{
		// autowired
		if(!isset($this->fslForUserWithoutDetail))
		{
			$this->fslForUserWithoutDetail = FieldSelectorListArrayImpl::createInstance();
			$this->fillFieldSelectorListForUserWithoutDetail($this->fslForUserWithoutDetail);
		}
		return $this->fslForUserWithoutDetail;
	}
	protected function fillFieldSelectorListForUserWithoutDetail($fieldSelectorList)
	{
		if(!$fieldSelectorList->containsFieldSelector('id')) $fieldSelectorList->addFieldSelector('id');
		if(!$fieldSelectorList->containsFieldSelector('username')) $fieldSelectorList->addFieldSelector('username');
		if(!$fieldSelectorList->containsFieldSelector('wigiiNamespace')) $fieldSelectorList->addFieldSelector('wigiiNamespace');
		if(!$fieldSelectorList->containsFieldSelector('isRole')) $fieldSelectorList->addFieldSelector('isRole'); //add by LWR
		if(!$fieldSelectorList->containsFieldSelector('isCalculatedRole')) $fieldSelectorList->addFieldSelector('isCalculatedRole'); //add by LWR
	}

	public function setFieldSelectorListForUser($fieldSelectorList)
	{
		$this->fslForUser = $fieldSelectorList;
	}
	public function getFieldSelectorListForUser()
	{
		// autowired
		if(!isset($this->fslForUser))
		{
			$this->fslForUser = FieldSelectorListArrayImpl::createInstance(true,false);
			$this->fillFieldSelectorListForUser($this->fslForUser);
		}
		return $this->fslForUser;
	}
	protected function fillFieldSelectorListForUser($fieldSelectorList)
	{
		$this->fillFieldSelectorListForUserEdit($fieldSelectorList);
		$this->fillFieldSelectorListForUserAdminRights($fieldSelectorList);
		$this->fillFieldSelectorListForUserLoginInformation($fieldSelectorList);
		$this->fillFieldSelectorListForUserSessionContext($fieldSelectorList);
		if(!$fieldSelectorList->containsFieldSelector('moduleAccess')) $fieldSelectorList->addFieldSelector('moduleAccess');
	}
	public function fillFieldSelectorListForUserEdit($fieldSelectorList){
		$this->fillFieldSelectorListForUserWithoutDetail($fieldSelectorList);
		if(!$fieldSelectorList->containsFieldSelector('canModifyOwnPassword')) $fieldSelectorList->addFieldSelector('canModifyOwnPassword');
		if(!$fieldSelectorList->containsFieldSelector('password')) $fieldSelectorList->addFieldSelector('password');
		if(!$fieldSelectorList->containsFieldSelector('passwordHistory')) $fieldSelectorList->addFieldSelector('passwordHistory');
		if(!$fieldSelectorList->containsFieldSelector('passwordLength')) $fieldSelectorList->addFieldSelector('passwordLength');
		if(!$fieldSelectorList->containsFieldSelector('passwordLife')) $fieldSelectorList->addFieldSelector('passwordLife');
		if(!$fieldSelectorList->containsFieldSelector('passwordDate')) $fieldSelectorList->addFieldSelector('passwordDate');
		if(!$fieldSelectorList->containsFieldSelector('email', 'value')) $fieldSelectorList->addFieldSelector('email','value');
		if(!$fieldSelectorList->containsFieldSelector('email', 'proofKey')) $fieldSelectorList->addFieldSelector('email', 'proofKey');
		if(!$fieldSelectorList->containsFieldSelector('email', 'proof')) $fieldSelectorList->addFieldSelector('email', 'proof');
		if(!$fieldSelectorList->containsFieldSelector('email', 'proofStatus')) $fieldSelectorList->addFieldSelector('email', 'proofStatus');		
		if(!$fieldSelectorList->containsFieldSelector('description')) $fieldSelectorList->addFieldSelector('description');
		if(!$fieldSelectorList->containsFieldSelector('sys_date')) $fieldSelectorList->addFieldSelector('sys_date');
		if(!$fieldSelectorList->containsFieldSelector('sys_creationDate')) $fieldSelectorList->addFieldSelector('sys_creationDate');
		if(!$fieldSelectorList->containsFieldSelector('sys_user')) $fieldSelectorList->addFieldSelector('sys_user');
		if(!$fieldSelectorList->containsFieldSelector('sys_username')) $fieldSelectorList->addFieldSelector('sys_username');
		if(!$fieldSelectorList->containsFieldSelector('sys_creationUser')) $fieldSelectorList->addFieldSelector('sys_creationUser');
		if(!$fieldSelectorList->containsFieldSelector('sys_creationUsername')) $fieldSelectorList->addFieldSelector('sys_creationUsername');
		if(!$fieldSelectorList->containsFieldSelector('authenticationMethod')) $fieldSelectorList->addFieldSelector('authenticationMethod');
		if(!$fieldSelectorList->containsFieldSelector('authenticationServer')) $fieldSelectorList->addFieldSelector('authenticationServer');
	}
	protected function fillFieldSelectorListForUserLoginInformation($fieldSelectorList){
		if(!$fieldSelectorList->containsFieldSelector('info_lastLogin')) $fieldSelectorList->addFieldSelector('info_lastLogin');
		if(!$fieldSelectorList->containsFieldSelector('info_nbLogin')) $fieldSelectorList->addFieldSelector('info_nbLogin');
		if(!$fieldSelectorList->containsFieldSelector('info_lastFailedLogin')) $fieldSelectorList->addFieldSelector('info_lastFailedLogin');
		if(!$fieldSelectorList->containsFieldSelector('info_nbFailedLogin')) $fieldSelectorList->addFieldSelector('info_nbFailedLogin');
		if(!$fieldSelectorList->containsFieldSelector('info_lastLogout')) $fieldSelectorList->addFieldSelector('info_lastLogout');
	}
	protected function fillFieldSelectorListForUserSessionContext($fieldSelectorList){
		if(!$fieldSelectorList->containsFieldSelector('info_lastSessionContext')) $fieldSelectorList->addFieldSelector('info_lastSessionContext');
		if(!$fieldSelectorList->containsFieldSelector('info_resetSessionContext')) $fieldSelectorList->addFieldSelector('info_resetSessionContext');
	}
	public function fillFieldSelectorListForUserAdminRights($fieldSelectorList)
	{
		if(!$fieldSelectorList->containsFieldSelector('userCreator')) $fieldSelectorList->addFieldSelector('userCreator');
		if(!$fieldSelectorList->containsFieldSelector('adminCreator')) $fieldSelectorList->addFieldSelector('adminCreator');
		if(!$fieldSelectorList->containsFieldSelector('readAllUsersInWigiiNamespace')) $fieldSelectorList->addFieldSelector('readAllUsersInWigiiNamespace');
		if(!$fieldSelectorList->containsFieldSelector('groupCreator')) $fieldSelectorList->addFieldSelector('groupCreator');
		if(!$fieldSelectorList->containsFieldSelector('rootGroupCreator')) $fieldSelectorList->addFieldSelector('rootGroupCreator');
		if(!$fieldSelectorList->containsFieldSelector('readAllGroupsInWigiiNamespace')) $fieldSelectorList->addFieldSelector('readAllGroupsInWigiiNamespace');
		if(!$fieldSelectorList->containsFieldSelector('wigiiNamespaceCreator')) $fieldSelectorList->addFieldSelector('wigiiNamespaceCreator');
		if(!$fieldSelectorList->containsFieldSelector('moduleEditor')) $fieldSelectorList->addFieldSelector('moduleEditor');
	}

	// Service implementation
	public function doesUsernameExist($principal, $username){
		$this->executionSink()->publishStartOperation("doesUsernameExist", $principal);
		try
		{
			$this->assertPrincipalAuthorizedForDoesUsernameExist($principal);

			$dbRow = $this->getMySqlFacade()->selectOne($principal,
						$this->getSqlForCheckUsernameUnique($username),
						$this->getDbAdminService()->getDbConnectionSettings($principal));

			$returnValue = ($dbRow ? $dbRow["id_user"] : false);
		}
		catch (UserAdminServiceException $uaE){
			$this->executionSink()->publishEndOperationOnError("doesUsernameExist", $uaE, $principal);
			throw $uaE;
		}
		catch (AuthorizationServiceException $asE){
			$this->executionSink()->publishEndOperationOnError("doesUsernameExist", $asE, $principal);
			throw $asE;
		}
		catch(Exception $e)
		{
			$this->executionSink()->publishEndOperationOnError("doesUsernameExist", $e, $principal);
			throw new UserAdminServiceException('',UserAdminServiceException::WRAPPING, $e);
		}
		$this->executionSink()->publishEndOperation("doesUsernameExist", $principal);
		return $returnValue;
	}
	protected function assertPrincipalAuthorizedForDoesUsernameExist($principal)
	{
		if(is_null($principal)) throw new UserAdminServiceException('principal can not be null', UserAdminServiceException::INVALID_ARGUMENT);

		$autoS = $this->getAuthorizationService();
		// checks general authorization
		$autoS->assertPrincipalAuthorized($principal, "UserAdminService", "doesUsernameExist");

	}
	public function persistUser($principal, $user, $fieldSelectorList=null)
	{
		$this->executionSink()->publishStartOperation("persistUser", $principal);
		try
		{
			if(is_null($user)) return 0;
			$isNew = $user->isNew();

			// if user already exists in database, first reads it
			$origUserP = null; $origPRights = null;
			if(!$isNew)
			{
				$origUserP = $this->getUser($principal, $user->getId(), $fieldSelectorList);
				if(!is_null($origUserP))
				{
					$origPRights = $origUserP->getRights();
					$origUser = $origUserP->getUser();
				}
			}

			// checks authorization
			$this->assertPrincipalAuthorizedForPersistUser($principal, $isNew, $origUserP, $user->getUsername());

			// validates user
			$this->validateUser($principal, $user, $isNew, $origUser, $fieldSelectorList);

			//force rights to match hierarichal rights
			if($principal->isWigiiNamespaceCreator()){
				$user->getDetail()->calculateAdminLevelRights($user->getDetail()->getModuleAccess());
			} else {
				$user->getDetail()->calculateAdminLevelRights($principal->getModuleAccess());
			}

			// persists user
			$dbCS = $this->getDbAdminService()->getDbConnectionSettings($principal);
			$mySqlF = $this->getMySqlFacade();
			// if isNew then insert
			$returnValue = 0;
			if($isNew)
			{
				try{
					$user->setId(
						$mySqlF->insertIfUnique($principal,
							$this->getSqlForInsertUser($principal, $user, true),
							$this->getSqlForCheckUserUnique($user),
							$dbCS)
					);
				} catch(MySqlFacadeException $e) {
					if($e->getCode() == MySqlFacadeException::MYSQL_NOTUNIQUE){
						throw new UserAdminServiceException("username already used", UserAdminServiceException::USERNAME_ALREADY_USED);
					}
					throw $e;
				}
				// inserts principal ownership
				try
				{
					$mySqlF->insertOne($principal,
						$this->getSqlForInsertUserUserOwner($principal->getAttachedUser()->getId(), $user->getId()),
						$dbCS);
				}
				// if insertion error, then deletes first created user and throws exception
				catch(Exception $e)
				{
					// unlocks user
					$this->unLock($principal, $user);
					// deletes user
					try
					{
					    $this->getDbAdminService()->deleteRows($principal,
							$this->getSqlTableNameForDeleteUser(),
							$this->getSqlWhereClauseForDeleteUser($user),
							$dbCS);
					}
					catch(Exception $e1){/* does nothing, it will be automatically deleted on next delete */}
					throw $e;
				}
				// unlocks user
				$this->unLock($principal, $user);
				$returnValue = 1;
			}
			// invalid state
			elseif(is_null($origUserP))
			{
				throw new UserAdminServiceException('authorized to update user '.$user->getId().' which does not exist in database');
			}
			// else if username changed, checks for unicity before updating
			elseif($origUser->getUsername() != $user->getUsername() &&
					(is_null($fieldSelectorList) || $fieldSelectorList->containsFieldSelector("username")))
			{
				// acquires lock
				$shouldUnlock = $this->lock($principal, $user);
				try
				{
					$returnValue = $this->getMySqlFacade()->updateIfUnique($principal,
						$this->getSqlForUpdateUser($principal, $user, $origUser, $fieldSelectorList),
						$this->getSqlForCheckUserUnique($user),
						$dbCS
					);
				} catch(MySqlFacadeException $e) {
					if($shouldUnlock) $this->unLock($principal, $user);
					if($e->getCode() == MySqlFacadeException::MYSQL_NOTUNIQUE){
						throw new UserAdminServiceException("username already used", UserAdminServiceException::USERNAME_ALREADY_USED);
					}
					throw $e;
				}
			}
			// else updates all fields minus username
			else
			{
				// acquires lock
				$shouldUnlock = $this->lock($principal, $user);
				try
				{
					// updates
//					eput($fieldSelectorList);
//					eput($this->getSqlForUpdateUser($principal, $user, $origUser, $fieldSelectorList));
					$returnValue = $this->getMySqlFacade()->update($principal,
						$this->getSqlForUpdateUser($principal, $user, $origUser, $fieldSelectorList),
						$dbCS
					);
				}
				// releases lock
				catch(Exception $ne) {if($shouldUnlock) $this->unLock($principal, $user); throw $ne;}
				if($shouldUnlock) $this->unLock($principal, $user);
			}
		}
		catch (UserAdminServiceException $uaE){
			$this->executionSink()->publishEndOperationOnError("persistUser", $uaE, $principal);
			throw $uaE;
		}
		catch (AuthorizationServiceException $asE){
			$this->executionSink()->publishEndOperationOnError("persistUser", $asE, $principal);
			throw $asE;
		}
		catch(Exception $e)
		{
			$this->executionSink()->publishEndOperationOnError("persistUser", $e, $principal);
			throw new UserAdminServiceException('',UserAdminServiceException::WRAPPING, $e);
		}
		$this->executionSink()->publishEndOperation("persistUser", $principal);
		return $returnValue;
	}
	protected function assertPrincipalAuthorizedForPersistUser($principal, $isNew, $origUserP, $userErrorInfo='')
	{
		if(is_null($principal)) throw new UserAdminServiceException('principal can not be null', UserAdminServiceException::INVALID_ARGUMENT);

		$autoS = $this->getAuthorizationService();
		// checks general authorization
		$autoS->assertPrincipalAuthorized($principal, "UserAdminService", "persistUser");
		// check specific rights
		// new user/role -> admin creator or user creator
		if($isNew)
		{
			if(!$principal->isUserCreator())
			{
				$autoS->fail($principal, 'is not admin creator and not user creator, therefore can not insert new user');
			}
		}
		// existing user/role -> (admin creator or user creator or user manager in wigiiNamespace) and principal owns user
		else
		{
			if(is_null($origUserP) || is_null($origUserP->getRights()) ||
				!$origUserP->getRights()->isOwner() ||
				!$principal->isUserCreator()
			)
			{
				$autoS->fail($principal, "has no right to modify the user $userErrorInfo");
			}
			//check himself
			if(is_null($origUserP) || $origUserP->getUser()->getId() == $principal->getUserId()){
				$autoS->fail($principal, "has no right to modify himself");
			}
		}
	}
	protected function validateUser($principal, $user, $isNew, $origUser, $fieldSelectorList=null)
	{
		if(is_null($principal)) throw new UserAdminServiceException('principal can not be null', UserAdminServiceException::INVALID_ARGUMENT);
		if(is_null($user)) throw new UserAdminServiceException('user can not be null', UserAdminServiceException::INVALID_ARGUMENT);

		$val = $this->getArgValidator();

		//check wigiiNamespace and client
		if($fieldSelectorList == null || $fieldSelectorList->containsFieldSelector("wigiiNamespace")){
			$val->assert($principal->getWigiiNamespace()->getClient() == $user->getWigiiNamespace()->getClient(), 'cannot create a user for an other client: '.$user->getWigiiNamespace()->getClient()->getClientName().' than Principal client '.$principal->getWigiiNamespace()->getClient()->getClientName().'.');
			if(!$principal->isWigiiNamespaceCreator()){
//				eput($user->getWigiiNamespace()->displayDebug());
//				eput($principal->getWigiiNamespace()->displayDebug());
				if($isNew){
					$val->assert($principal->getWigiiNamespace() == $user->getWigiiNamespace(), 'Principal cannot create a user in an other wigiiNamespace: '.$user->getWigiiNamespace()->getWigiiNamespaceName());
					$val->assert($principal->getWigiiNamespace()->getClient() == $user->getWigiiNamespace()->getClient(), 'Principal cannot create a user in an other client: '.$user->getWigiiNamespace()->getClient()->getClientName());
				} else {
					$val->assert($origUser->getWigiiNamespace() == $user->getWigiiNamespace(), 'Principal cannot change wigiiNamespace of an existing user in: '.$user->getWigiiNamespace()->getWigiiNamespaceName());
					$val->assert($origUser->getWigiiNamespace()->getClient() == $user->getWigiiNamespace()->getClient(), 'Principal cannot change client of an existing user in: '.$user->getWigiiNamespace()->getClient()->getClientName());
				}
			}
		}

		$det = $user->getDetail();
		if(is_null($det)) throw new UserAdminServiceException('user detail can not be null', UserAdminServiceException::INVALID_ARGUMENT);
		if(!$isNew){
			$origDet = $origUser->getDetail();
			if(is_null($origDet)) throw new UserAdminServiceException('origUser detail can not be null', UserAdminServiceException::INVALID_ARGUMENT);
		}

		//ADMIN levels can be changed only if principal has rights on this specific admin level.
		//and is Admin user creator
		if(($fieldSelectorList == null || $fieldSelectorList->containsFieldSelector("wigiiNamespaceCreator")) && ($det->isWigiiNamespaceCreator() != ($isNew ? false : $origDet->isWigiiNamespaceCreator())))
		$val->assert($principal->isWigiiNamespaceCreator() == true, 'Principal cannot change WigiiNamespaceCreator rights',UserAdminServiceException::INVALID_RIGHT_NAMESPACECREATOR);
		if(($fieldSelectorList == null || $fieldSelectorList->containsFieldSelector("moduleEditor")) && ($det->isModuleEditor() != ($isNew ? false : $origDet->isModuleEditor())))
		$val->assert($principal->isModuleEditor() == true, 'Principal cannot change ModuleEditor rights',UserAdminServiceException::INVALID_RIGHT_MODULECREATOR);

		if(($fieldSelectorList == null || $fieldSelectorList->containsFieldSelector("readAllUsersInWigiiNamespace")) && ($det->isReadAllUsersInWigiiNamespace() != ($isNew ? false : $origDet->isReadAllUsersInWigiiNamespace()))){
			$val->assert($principal->isAdminCreator() == true, 'Principal is not admin creator',UserAdminServiceException::INVALID_RIGHT_ADMINCREATOR);
			$val->assert($principal->isReadAllUsersInWigiiNamespace() == true, 'Principal cannot change ReadAllUsersInWigiiNamespace rights',UserAdminServiceException::INVALID_RIGHT_READALLUSERSINNAMESPACE);
		}
		if(($fieldSelectorList == null || $fieldSelectorList->containsFieldSelector("userCreator")) && ($det->isUserCreator() != ($isNew ? false : $origDet->isUserCreator())))
		$val->assert($principal->isAdminCreator() == true, 'Principal cannot change UserCreator rights',UserAdminServiceException::INVALID_RIGHT_ADMINCREATOR);
		if(($fieldSelectorList == null || $fieldSelectorList->containsFieldSelector("adminCreator")) && ($det->isAdminCreator() != ($isNew ? false : $origDet->isAdminCreator())))
		$val->assert($principal->isAdminCreator() == true, 'Principal cannot change AdminCreator rights',UserAdminServiceException::INVALID_RIGHT_ADMINCREATOR);

		//check that principal has this right on the differences
		//and is Admin user creator (for group or root group creator)
		if(!$principal->isWigiiNamespaceCreator() && ($fieldSelectorList == null || $fieldSelectorList->containsFieldSelector("readAllGroupsInWigiiNamespace")) && ($det->getReadAllGroupsInWigiiNamespace() != ($isNew ? null : $origDet->getReadAllGroupsInWigiiNamespace()))){
			$val->assetArrayDiffKeyInclusion($principal->getReadAllGroupsInWigiiNamespace(), ($isNew ? null : $origDet->getReadAllGroupsInWigiiNamespace()), $det->getReadAllGroupsInWigiiNamespace(), 'Principal cannot change ReadAllGroupsInWigiiNamespace rights',UserAdminServiceException::INVALID_RIGHT_READALLGROUPSINNAMESPACE);
		}
		if(!$principal->isWigiiNamespaceCreator() && ($fieldSelectorList == null || $fieldSelectorList->containsFieldSelector("groupCreator")) && ($det->getGroupCreator() != ($isNew ? null : $origDet->getGroupCreator()))){
			$val->assert($principal->isAdminCreator() == true, 'Principal is not admin creator',UserAdminServiceException::INVALID_RIGHT_ADMINCREATOR);
			$val->assetArrayDiffKeyInclusion($principal->getGroupCreator(), ($isNew ? null : $origDet->getGroupCreator()), $det->getGroupCreator(), 'Principal cannot change GroupCreator rights',UserAdminServiceException::INVALID_RIGHT_GROUPCREATOR);
		}
		if(!$principal->isWigiiNamespaceCreator() && ($fieldSelectorList == null || $fieldSelectorList->containsFieldSelector("rootGroupCreator")) && ($det->getRootGroupCreator() != ($isNew ? null : $origDet->getRootGroupCreator()))){
			$val->assert($principal->isAdminCreator() == true, 'Principal is not admin creator',UserAdminServiceException::INVALID_RIGHT_ADMINCREATOR);
			$val->assetArrayDiffKeyInclusion($principal->getRootGroupCreator(), ($isNew ? null : $origDet->getRootGroupCreator()), $det->getRootGroupCreator(), 'Principal cannot change RootGroupCreator rights',UserAdminServiceException::INVALID_RIGHT_ROOTGROUPCREATOR);
		}
		if(!$principal->isWigiiNamespaceCreator() && ($fieldSelectorList == null || $fieldSelectorList->containsFieldSelector("moduleAccess")) && ($det->getModuleAccess() != ($isNew ? null : $origDet->getModuleAccess()))){
			$val->assetArrayDiffKeyInclusion($principal->getModuleAccess(), ($isNew ? null : $origDet->getModuleAccess()), $det->getModuleAccess(), 'Principal cannot change ModuleAccess',UserAdminServiceException::INVALID_RIGHT_MODULEACCESS);
		}
	}
	protected function getSqlForInsertUser($principal, $user, $lock)
	{
		if(is_null($user)) throw new UserAdminServiceException('user can not be null', UserAdminServiceException::INVALID_ARGUMENT);

		$fieldSelectorList = $this->getFieldSelectorListForUser();
		$mas = $this->getModuleAdminService();

		$sqlB = $this->getSqlWhereClauseBuilderForSelectUsers();
		$sqlB->setTableForInsert('Users');

		foreach($fieldSelectorList->getListIterator() as $fs)
		{
			$val = $user->getAttribute($fs);
			$doPersist=true;
			switch($fs->getFieldName())
			{
				case 'wigiiNamespace' :
					if(is_object($val)) $val = $val->getWigiiNamespaceName();
					break;
				case 'moduleAccess' :
				case 'groupCreator' :
				case 'rootGroupCreator' :
				case 'readAllGroupsInWigiiNamespace' :
					$val = $mas->moduleArrayToString($val);
					break;
				case 'id':
				case 'sys_date':
				case 'sys_creationDate':
				case 'sys_user':
				case 'sys_creationUser':
				case 'sys_username':
				case 'sys_creationUsername':
				case 'sys_lockMicroTime':
				case 'sys_lockId':
				case 'info_lastLogin':
				case 'info_nbLogin':
				case 'info_lastFailedLogin':
				case 'info_nbFailedLogin':
				case 'info_lastLogout':
				//case 'info_lastSessionContext': CWE 2016.06.08: opens the persistance of user session context from Admin console (principal email and more)
				//case 'info_resetSessionContext':
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
	protected function getSqlForUpdateUser($principal, $user, $origUser, $fieldSelectorList=null)
	{
		if(is_null($fieldSelectorList)) $fieldSelectorList = $this->getFieldSelectorListForUser();
		$mas = $this->getModuleAdminService();

		$sqlB = $this->getSqlWhereClauseBuilderForSelectUsers();
		$sqlB->setTableForUpdate('Users');

		foreach($fieldSelectorList->getListIterator() as $fs)
		{
			$val = $user->getAttribute($fs);
			if(isset($origUser)) $origVal = $origUser->getAttribute($fs);
			$doPersist=true;
			switch($fs->getFieldName())
			{
				case 'wigiiNamespace' :
					if(is_object($val)) $val = $val->getWigiiNamespaceName();
					if(is_object($origVal)) $origVal = $origVal->getWigiiNamespaceName();
					break;
				case 'moduleAccess' :
				case 'groupCreator' :
				case 'rootGroupCreator' :
				case 'readAllGroupsInWigiiNamespace' :
					$val = $mas->moduleArrayToString($val);
					$origVal = $mas->moduleArrayToString($origVal);
					break;
				case 'id':
				case 'sys_date':
				case 'sys_creationDate':
				case 'sys_user':
				case 'sys_creationUser':
				case 'sys_username':
				case 'sys_creationUsername':
				case 'sys_lockMicroTime':
				case 'sys_lockId':
				case 'info_lastLogin':
				case 'info_nbLogin':
				case 'info_lastFailedLogin':									
				case 'info_lastLogout':
				//case 'info_resetSessionContext': this needs to be updated
				//case 'info_lastSessionContext': CWE 2016.06.08: opens the persistance of user session context from Admin console (principal email and more)					
					$doPersist=false;
					break;
				case 'info_nbFailedLogin':
					// if password changed then resets info_nbFailedLogin
					if(isset($origUser) && ( 
						($origUser->getAttribute(fs('password'))!=$user->getAttribute('password')) ||
						($origUser->getAttribute(fs('passwordDate'))!=$user->getAttribute('passwordDate'))
						)) {
						$doPersist=true;
					}
					else $doPersist=false;
					break;
			}
			if($doPersist)
			{
				if(isset($origUser))
				{
					$sqlB->updateValueIfChanged($sqlB->getSqlColumnNameForFieldSelector($fs), $val, $origVal, $sqlB->getSqlDataTypeForFieldSelector($fs));
				}
				else
				{
					$sqlB->updateValue($sqlB->getSqlColumnNameForFieldSelector($fs), $val, $sqlB->getSqlDataTypeForFieldSelector($fs));
				}

			}
		}
		// forces sys_date and sys_user
		$sqlB->updateSysUser($principal);

		$sqlB->setWhereClauseSingleId('id_user', $user->getId());

		return $sqlB->getSql();
	}
	/**
	 * If user is unique then this query does not return any rows.
	 */
	protected function getSqlForCheckUserUnique($user)
	{
		if(is_null($user)) throw new UserAdminServiceException('user can not be null', UserAdminServiceException::INVALID_ARGUMENT);
		$sqlB = $this->getMySqlFacade()->getSqlBuilder();
		$id_user = $sqlB->formatBinExp('U.id_user', '!=', $user->getId(), MySqlQueryBuilder::SQLTYPE_INT);
		$username = $sqlB->formatBinExp('U.username', '=', $user->getUsername(), MySqlQueryBuilder::SQLTYPE_VARCHAR);
		return "select U.id_user from Users as U where $id_user and $username";
	}
	protected function getSqlForCheckUsernameUnique($username)
	{
		if(is_null($username)) throw new UserAdminServiceException('username can not be null', UserAdminServiceException::INVALID_ARGUMENT);
		return "select id_user from Users where username='$username'";
	}
	protected function getSqlForInsertUserUserOwner($id_user_owner, $id_user)
	{
		$sqlB = $this->getMySqlFacade()->getSqlBuilder();
		$sqlB->setTableForInsert('Users_Users');
		$sqlB->insertValue('id_user_owner', $id_user_owner, MySqlQueryBuilder::SQLTYPE_INT);
		$sqlB->insertValue('id_user', $id_user, MySqlQueryBuilder::SQLTYPE_INT);
		$sqlB->insertValue('isOwner', true, MySqlQueryBuilder::SQLTYPE_BOOLEAN);//add by LWR
		$sqlB->insertValue('hasRole', false, MySqlQueryBuilder::SQLTYPE_BOOLEAN);//add by LWR
		return $sqlB->getSql();
	}

	public function deleteAllUserRolesFromWigiiNamespace($principal, $wigiiNamespace)
	{
		$this->executionSink()->publishStartOperation("deleteAllUserRolesFromWigiiNamespace", $principal);
		try
		{
			// checks authorization
			$this->assertPrincipalAuthorizedForDeleteAllUserRolesFromWigiiNamespace($principal, $wigiiNamespace);

			$dbAS = $this->getDbAdminService();
			$dbCS = $dbAS->getDbConnectionSettings($principal);

			// deletes users/roles
			$returnValue = $dbAS->deleteRows($principal,
				$this->getSqlTableNameForDeleteUser(),
				$this->getSqlWhereClauseForDeleteAllUserRolesFromWigiiNamespace($wigiiNamespace),
				$dbCS);
		}
		catch (UserAdminServiceException $uaE){
			$this->executionSink()->publishEndOperationOnError("deleteAllUserRolesFromWigiiNamespace", $uaE, $principal);
			throw $uaE;
		}
		catch (AuthorizationServiceException $asE){
			$this->executionSink()->publishEndOperationOnError("deleteAllUserRolesFromWigiiNamespace", $asE, $principal);
			throw $asE;
		}
		catch(Exception $e)
		{
			$this->executionSink()->publishEndOperationOnError("deleteAllUserRolesFromWigiiNamespace", $e, $principal);
			throw new UserAdminServiceException('',UserAdminServiceException::WRAPPING, $e);
		}
		$this->executionSink()->publishEndOperation("deleteAllUserRolesFromWigiiNamespace", $principal);
		return $returnValue;
	}
	protected function assertPrincipalAuthorizedForDeleteAllUserRolesFromWigiiNamespace($principal, $wigiiNamespace)
	{
		if(is_null($principal)) throw new UserAdminServiceException('principal can not be null', UserAdminServiceException::INVALID_ARGUMENT);

		$autoS = $this->getAuthorizationService();
		// checks general authorization
		$autoS->assertPrincipalAuthorized($principal, "UserAdminService", "deleteAllUserRolesFromWigiiNamespace");
		// check specific rights
		// existing user/role -> (admin creator or user creator or user manager in wigiiNamespace) and principal owns user
		if(!$principal->isWigiiNamespaceCreator())
		{
			$autoS->fail($principal, "has no right to delete all users/roles of a namespace");
		}
		//check himself
		if($principal->getWigiiNamespace()->getWigiiNamespaceName()==$wigiiNamespace->getWigiiNamespaceName()){
			$autoS->fail($principal, "cannot delete own namespace");
		}
	}
	protected function getSqlWhereClauseForDeleteAllUserRolesFromWigiiNamespace($wigiiNamespace)
	{
		if(is_null($wigiiNamespace)) throw new UserAdminServiceException('wigiiNamespace can not be null', UserAdminServiceException::INVALID_ARGUMENT);
		$sqlB = $this->getMySqlFacade()->getSqlBuilder();
		return $sqlB->formatBinExp('Users.wigiiNamespace', '=', $wigiiNamespace->getWigiiNamespaceName(), MySqlQueryBuilder::SQLTYPE_VARCHAR);
	}

	public function deleteUser($principal, $userId)
	{
		$this->executionSink()->publishStartOperation("deleteUser", $principal);
		try
		{
			// reads existing user in database
			$origUserP = $this->getUser($principal, $userId);
			if(is_null($origUserP)) return 0;
			$origPRights = $origUserP->getRights();

			// checks authorization
			$this->assertPrincipalAuthorizedForDeleteUser($principal, $origUserP, $userId);
			$user = $origUserP->getUser();

			$dbAS = $this->getDbAdminService();
			$dbCS = $dbAS->getDbConnectionSettings($principal);

			// acquires lock
			$shouldUnlock = $this->lock($principal, $user);
			try
			{
				//the standard deleteRows method cannot be used to delete the associated calculated role
				//the reason is because the deleteRows method is smart and first delete any constraint in other
				//table before deleting the main item itself.
				//that means that when it comes to really do the delete of the user any other link in user_user of
				//user_group are deleted.
				//we cannot then use in the where clause of the delete references to user_user links.
				//to solve this problem i first do a select of each calculated role
				//and then remove the user and all its calculated roles
				$rowList = RowListArrayImpl::createInstance("id_user");
				$this->getMySqlFacade()->selectAll($principal,
					$this->getSqlForSelectCalculatedUser($user),
					$dbCS, $rowList);

				// deletes user
				if($dbAS->deleteRows($principal,
					$this->getSqlTableNameForDeleteUser(),
					$this->getSqlWhereClauseForDeleteUser($user, ($rowList->getListIterator() ? array_keys($rowList->getListIterator()) : null)),
					$dbCS) > 0){

					$returnValue = 1;

					//on success updates elements state_locked = 1 if sys_user = userId
					$this->getMySqlFacade()->update($principal,
						$this->getSqlForCorrectState_lockedForDeleteUser($userId),
						$dbCS);

				} else $returnValue = 0;
			}
			// release lock
			catch(Exception $ne) {if($shouldUnlock) $this->unLock($principal, $user); throw $ne;}
			if($shouldUnlock) $this->unLock($principal, $user);
		}
		catch (UserAdminServiceException $uaE){
			$this->executionSink()->publishEndOperationOnError("deleteUser", $uaE, $principal);
			throw $uaE;
		}
		catch (AuthorizationServiceException $asE){
			$this->executionSink()->publishEndOperationOnError("deleteUser", $asE, $principal);
			throw $asE;
		}
		catch(Exception $e)
		{
			$this->executionSink()->publishEndOperationOnError("deleteUser", $e, $principal);
			throw new UserAdminServiceException('',UserAdminServiceException::WRAPPING, $e);
		}
		$this->executionSink()->publishEndOperation("deleteUser", $principal);
		return $returnValue;
	}
	protected function assertPrincipalAuthorizedForDeleteUser($principal, $origUserP, $userErrorInfo='')
	{
		if(is_null($principal)) throw new UserAdminServiceException('principal can not be null', UserAdminServiceException::INVALID_ARGUMENT);

		$autoS = $this->getAuthorizationService();
		// checks general authorization
		$autoS->assertPrincipalAuthorized($principal, "UserAdminService", "deleteUser");
		// check specific rights
		// existing user/role -> (admin creator or user creator or user manager in wigiiNamespace) and principal owns user
		if(is_null($origUserP) || is_null($origUserP->getRights()) ||
			!$origUserP->getRights()->isOwner() ||
			!$principal->isUserCreator()
		)
		{
			$autoS->fail($principal, "has no right to delete the user $userErrorInfo");
		}

		//cannot delete admin (at least one admin level) if not adminCreator
		if(!$principal->isAdminCreator() && $origUserP->getUser()->getDetail()->hasAdminLevel()){
			$autoS->fail($principal, "has no right to delete admin user $userErrorInfo");
		}

		//cannot delete a user of an other wigiiNamespace if not wigiiNamespaceCreator
		if($origUserP->getUser()->getWigiiNamespace()->getWigiiNamespaceName()!=$principal->getWigiiNamespace()->getWigiiNamespaceName() && !$principal->isWigiiNamespaceCreator()){
			$autoS->fail($principal, "cannot delete the user:$userErrorInfo of an other wigiiNamespace (principal is not wigiiNamespace creator)");
		}

		//check himself
		if(is_null($origUserP) || $origUserP->getUser()->getId() == $principal->getUserId() || $origUserP->getUser()->getId() == $principal->getRealUserId()){
			$autoS->fail($principal, "cannot delete himself");
		}
	}
	protected function getSqlTableNameForDeleteGroup()
	{
		return 'Groups';
	}
	protected function getSqlTableNameForDeleteUGR()
	{
		return 'Users_Groups_Rights';
	}
	protected function getSqlTableNameForDeleteUser()
	{
		return 'Users';
	}
	private $sqlTableNameForElements;
	public function setSqlTableNameForElements($tablename){
		$this->sqlTableNameForElements;
	}
	protected function getSqlTableNameForElements(){
		if(!isset($this->sqlTableNameForElements)){
			$this->sqlTableNameForElements = 'Elements';
		}
		return $this->sqlTableNameForElements;
	}
	protected function getSqlForCorrectState_lockedForDeleteUsers($userIds){
		$sqlB = $this->getMySqlFacade()->getSqlBuilder();
		$sqlB->setTableForUpdate($this->getSqlTableNameForElements());

		$sqlB->updateValue('state_locked', false, MySqlQueryBuilder::SQLTYPE_BOOLEAN);
		$sqlB->updateValue('state_lockedInfo', "", MySqlQueryBuilder::SQLTYPE_TEXT);
		$sysUserIds = $sqlB->formatBinExp('sys_user', 'IN', $userIds, MySqlQueryBuilder::SQLTYPE_INT);
		$stateLocked = $sqlB->formatBinExp('state_locked', '=', true, MySqlQueryBuilder::SQLTYPE_BOOLEAN);
		$whereClause = "$sysUserIds and $stateLocked";
		$sqlB->setWhereClause($whereClause);
		return $sqlB->getSql();
	}
	protected function getSqlForCorrectState_lockedForDeleteUser($userId){
		$sqlB = $this->getMySqlFacade()->getSqlBuilder();
		$sqlB->setTableForUpdate($this->getSqlTableNameForElements());

		$sqlB->updateValue('state_locked', false, MySqlQueryBuilder::SQLTYPE_BOOLEAN);
		$sqlB->updateValue('state_lockedInfo', "", MySqlQueryBuilder::SQLTYPE_TEXT);
		$sysUserId = $sqlB->formatBinExp('sys_user', '=', $userId, MySqlQueryBuilder::SQLTYPE_INT);
		$stateLocked = $sqlB->formatBinExp('state_locked', '=', true, MySqlQueryBuilder::SQLTYPE_BOOLEAN);
		$whereClause = "$sysUserId and $stateLocked";
		$sqlB->setWhereClause($whereClause);
		return $sqlB->getSql();
	}
	protected function getSqlForSelectCalculatedUser($user)
	{
		if(is_null($user)) throw new UserAdminServiceException('user can not be null', UserAdminServiceException::INVALID_ARGUMENT);
		return '
SELECT u.id_user FROM '.$this->getSqlTableNameForDeleteUser().' as u WHERE
u.id_user IN (
	SELECT uu.id_user FROM Users_Users uu
	WHERE
		uu.id_user_owner = '.$user->getId().' AND
		uu.hasRole = 1

) AND u.isCalculatedRole = 1';
	}
	protected function getSqlWhereClauseForDeleteUser($user, $calculatedRoleIds=array())
	{
		if(is_null($user)) throw new UserAdminServiceException('user can not be null', UserAdminServiceException::INVALID_ARGUMENT);
		$sqlB = $this->getMySqlFacade()->getSqlBuilder();
		$calculatedRoleIds[] = $user->getId();
		return $sqlB->formatBinExp('Users.id_user', 'IN', $calculatedRoleIds, MySqlQueryBuilder::SQLTYPE_INT);
	}


	public function getUser($principal, $userId, $fieldSelectorList=null)
	{
		$this->executionSink()->publishStartOperation("getUser", $principal);
		try
		{
			// checks authorization
			if(isset($principal) && $userId == $principal->getRealUserId()) {
				$pRights = $this->assertPrincipalAuthorizedForGetMyself($principal);
			}
			else $pRights = $this->assertPrincipalAuthorizedForGetUser($principal);

			// else fetches in database
			$returnValue = null;
			// if principal has special rights
			if(isset($pRights))
			{
				$dbRow = $this->getMySqlFacade()->selectOne($principal,
						$this->getSqlForGetUser($userId, $fieldSelectorList),
						$this->getDbAdminService()->getDbConnectionSettings($principal));
				if(isset($dbRow))
				{
					// sets client
					$dbRow['client'] = $principal->getWigiiNamespace()->getClient();
					// create UserP
					$returnValue = UserP::createInstance($this->createUserInstanceFromRow($principal, $dbRow));
					$returnValue->setRights($pRights);
				}
			}
			// else rights are given by DB
			else
			{
				$dbRow = $this->getMySqlFacade()->selectOne($principal,
						$this->getSqlPForGetUser($principal, $userId, $fieldSelectorList),
						$this->getDbAdminService()->getDbConnectionSettings($principal));
				if(isset($dbRow))
				{
					// sets client
					$dbRow['client'] = $principal->getWigiiNamespace()->getClient();
					// creates UserP
					$isOwner = formatBoolean($dbRow['isOwner']);
					$returnValue = UserP::createInstance($this->createUserInstanceFromRow($principal, $dbRow, $isOwner));
					if($isOwner) $returnValue->setRights(PrincipalRights::createInstance($dbRow));
				}
			}
		}
		catch (UserAdminServiceException $uaE){
			$this->executionSink()->publishEndOperationOnError("getUser", $uaE, $principal);
			throw $uaE;
		}
		catch (AuthorizationServiceException $asE){
			$this->executionSink()->publishEndOperationOnError("getUser", $asE, $principal);
			throw $asE;
		}
		catch(Exception $e)
		{
			$this->executionSink()->publishEndOperationOnError("getUser", $e, $principal);
			throw new UserAdminServiceException('',UserAdminServiceException::WRAPPING, $e);
		}
		$this->executionSink()->publishEndOperation("getUser", $principal);
		return $returnValue;
	}
	protected function assertPrincipalAuthorizedForGetUser($principal)
	{
		$pRights = null;
		// checks general authorization
		$pRights = $this->getAuthorizationService()->assertPrincipalAuthorized($principal, "UserAdminService", "getUser");
		return $pRights;
	}
	protected function getSqlForGetUser($userId, $fieldSelectorList=null)
	{
		$sqlB = $this->getMySqlFacade()->getSqlBuilder();
		$id_user = $sqlB->formatBinExp('U.id_user', '=', $userId, MySqlQueryBuilder::SQLTYPE_INT);
		return "select ".$this->getSqlColumnsForUser('U', $fieldSelectorList)." from Users as U where $id_user";
	}
	protected function getSqlPForGetUser($principal, $userId, $fieldSelectorList=null)
	{
		$principalId = $principal->getUserId();
		if(is_null($principalId)) throw new UserAdminServiceException('principal attached user can not be null', UserAdminServiceException::INVALID_ARGUMENT);
		$sqlB = $this->getMySqlFacade()->getSqlBuilder();
		$id_user = $sqlB->formatBinExp('U.id_user', '=', $userId, MySqlQueryBuilder::SQLTYPE_INT);
		$id_user_owner = $sqlB->formatBinExp('UU.id_user_owner', '=', $principalId, MySqlQueryBuilder::SQLTYPE_INT);
		$isOwner = $sqlB->formatBinExp('UU.isOwner', '=', true, MySqlQueryBuilder::SQLTYPE_BOOLEAN);//add by LWR
		$isInPrincipalWigiiNamespace = $sqlB->formatBinExp('U.wigiiNamespace', '=', $principal->getWigiiNamespace()->getWigiiNamespaceName(), MySqlQueryBuilder::SQLTYPE_VARCHAR);//add by LWR
		return "SELECT ".$this->getSqlColumnsForUser('U', $fieldSelectorList).",
case when
	UU.id_user_owner is not null
	".($principal->isReadAllUsersInWigiiNamespace() ? " or $isInPrincipalWigiiNamespace " : "")."
	".($principal->isWigiiNamespaceCreator() ? " or 1 " : "")."
	then 1 else 0 end as isOwner
FROM Users as U
LEFT JOIN Users_Users as UU on UU.id_user = U.id_user and $id_user_owner and $isOwner
WHERE $id_user
";
	}

	public function getMyRole($principal, $roleId, $fieldSelectorList=null)
	{
		$this->executionSink()->publishStartOperation("getMyRole", $principal);
		try
		{
			// checks authorization
			$this->assertPrincipalAuthorizedForGetMyRole($principal);

			//  fetches in database
			$returnValue = null;
			$dbRow = $this->getMySqlFacade()->selectOne($principal,
					$this->getSqlForGetMyRole($principal, $roleId, $fieldSelectorList),
					$this->getDbAdminService()->getDbConnectionSettings($principal));
			if(isset($dbRow))
			{
				// sets client
				$dbRow['client'] = $principal->getWigiiNamespace()->getClient();
				//always take the details because we are in a role concept
				$returnValue = $this->createUserInstanceFromRow($principal, $dbRow, true);
			}
		}
		catch (UserAdminServiceException $uaE){
			$this->executionSink()->publishEndOperationOnError("getMyRole", $uaE, $principal);
			throw $uaE;
		}
		catch (AuthorizationServiceException $asE){
			$this->executionSink()->publishEndOperationOnError("getMyRole", $asE, $principal);
			throw $asE;
		}
		catch(Exception $e)
		{
			$this->executionSink()->publishEndOperationOnError("getMyRole", $e, $principal);
			throw new UserAdminServiceException('',UserAdminServiceException::WRAPPING, $e);
		}
		$this->executionSink()->publishEndOperation("getMyRole", $principal);
		return $returnValue;
	}
	protected function assertPrincipalAuthorizedForGetMyRole($principal)
	{
		// checks general authorization
		$this->getAuthorizationService()->assertPrincipalAuthorized($principal, "UserAdminService", "getMyRole");
	}
	protected function getSqlForGetMyRole($principal, $userId, $fieldSelectorList=null)
	{
		$principalId = $principal->getRealUserId();
		if(is_null($principalId)) throw new UserAdminServiceException('principal attached user can not be null', UserAdminServiceException::INVALID_ARGUMENT);
		$sqlB = $this->getMySqlFacade()->getSqlBuilder();
		$id_user = $sqlB->formatBinExp('U.id_user', '=', $userId, MySqlQueryBuilder::SQLTYPE_INT);
		$hasRole = $sqlB->formatBinExp('UU.hasRole', '=', true, MySqlQueryBuilder::SQLTYPE_BOOLEAN);
		$id_user_owner = $sqlB->formatBinExp('UU.id_user_owner', '=', $principalId, MySqlQueryBuilder::SQLTYPE_INT);

		return "SELECT ".$this->getSqlColumnsForUser('U', $fieldSelectorList)."
FROM Users as U
inner join Users_Users as UU on UU.id_user = U.id_user and $id_user_owner and $hasRole
where $id_user";
	}

	public function getUserWithoutDetail($principal, $userId)
	{
		$this->executionSink()->publishStartOperation("getUserWithoutDetail", $principal);
		try
		{
			// checks authorization
			$this->assertPrincipalAuthorizedForGetUserWithoutDetail($principal);

			// fetches in database
			$returnValue = null;
			$dbRow = $this->getMySqlFacade()->selectOne($principal,
					$this->getSqlForGetUser($userId, $this->getFieldSelectorListForUserWithoutDetail()),
					$this->getDbAdminService()->getDbConnectionSettings($principal));
			if(isset($dbRow))
			{
				// sets client
				$dbRow['client'] = $principal->getWigiiNamespace()->getClient();
				// create User
				$returnValue = $this->createUserInstanceFromRow($principal, $dbRow, false);
			}
		}
		catch (UserAdminServiceException $uaE){
			$this->executionSink()->publishEndOperationOnError("getUserWithoutDetail", $uaE, $principal);
			throw $uaE;
		}
		catch (AuthorizationServiceException $asE){
			$this->executionSink()->publishEndOperationOnError("getUserWithoutDetail", $asE, $principal);
			throw $asE;
		}
		catch(Exception $e)
		{
			$this->executionSink()->publishEndOperationOnError("getUserWithoutDetail", $e, $principal);
			throw new UserAdminServiceException('',UserAdminServiceException::WRAPPING, $e);
		}
		$this->executionSink()->publishEndOperation("getUserWithoutDetail", $principal);
		return $returnValue;
	}
	protected function assertPrincipalAuthorizedForGetUserWithoutDetail($principal)
	{
		// checks general authorization
		$this->getAuthorizationService()->assertPrincipalAuthorized($principal, "UserAdminService", "getUserWithoutDetail");
	}

	public function findUserByName($principal, $userName, $fieldSelectorList=null)
	{
		$this->executionSink()->publishStartOperation("findUserByName", $principal);
		try
		{
			// checks authorization
			if(isset($principal) && $userName == $principal->getUsername()) {
				$pRights = $this->assertPrincipalAuthorizedForGetMyself($principal);
			}
			else $pRights = $this->assertPrincipalAuthorizedForFindUserByName($principal);

			// fetches in database
			$returnValue = null;
			// if principal has special rights
			if(isset($pRights))
			{
				$dbRow = $this->getMySqlFacade()->selectOne($principal,
						$this->getSqlForFindUserByName($userName, $fieldSelectorList),
						$this->getDbAdminService()->getDbConnectionSettings($principal));
				if(isset($dbRow))
				{
					// sets client
					$dbRow['client'] = $principal->getWigiiNamespace()->getClient();
					// create UserP
					$returnValue = UserP::createInstance($this->createUserInstanceFromRow($principal, $dbRow));
					$returnValue->setRights($pRights);
				}
			}
			// else rights are given by DB
			else
			{
				$dbRow = $this->getMySqlFacade()->selectOne($principal,
						$this->getSqlPForFindUserByName($principal, $userName, $fieldSelectorList),
						$this->getDbAdminService()->getDbConnectionSettings($principal));
				if(isset($dbRow))
				{
					// sets client
					$dbRow['client'] = $principal->getWigiiNamespace()->getClient();
					// creates UserP
					$isOwner = formatBoolean($dbRow['isOwner']);
					$returnValue = UserP::createInstance($this->createUserInstanceFromRow($principal, $dbRow, $isOwner));
					if($isOwner) $returnValue->setRights(PrincipalRights::createInstance($dbRow));
				}
			}
		}
		catch (UserAdminServiceException $uaE){
			$this->executionSink()->publishEndOperationOnError("findUserByName", $uaE, $principal);
			throw $uaE;
		}
		catch (AuthorizationServiceException $asE){
			$this->executionSink()->publishEndOperationOnError("findUserByName", $asE, $principal);
			throw $asE;
		}
		catch(Exception $e)
		{
			$this->executionSink()->publishEndOperationOnError("findUserByName", $e, $principal);
			throw new UserAdminServiceException('',UserAdminServiceException::WRAPPING, $e);
		}
		$this->executionSink()->publishEndOperation("findUserByName", $principal);
		return $returnValue;
	}
	protected function assertPrincipalAuthorizedForFindUserByName($principal)
	{
		$pRights = null;
		// checks general authorization
		$pRights = $this->getAuthorizationService()->assertPrincipalAuthorized($principal, "UserAdminService", "findUserByName");
		return $pRights;
	}
	protected function getSqlForFindUserByName($userName, $fieldSelectorList = null)
	{
		$sqlB = $this->getMySqlFacade()->getSqlBuilder();
		$username = $sqlB->formatBinExp('U.username', '=', $userName, MySqlQueryBuilder::SQLTYPE_VARCHAR);
		return "select ".$this->getSqlColumnsForUser('U', $fieldSelectorList)." from Users as U where $username";
	}
	protected function getSqlPForFindUserByName($principal, $userName, $fieldSelectorList = null)
	{
		$principalId = $principal->getUserId();
		if(is_null($principalId)) throw new UserAdminServiceException('principal attached user can not be null', UserAdminServiceException::INVALID_ARGUMENT);
		$sqlB = $this->getMySqlFacade()->getSqlBuilder();
		$username = $sqlB->formatBinExp('U.username', '=', $userName, MySqlQueryBuilder::SQLTYPE_VARCHAR);
		$id_user_owner = $sqlB->formatBinExp('UU.id_user_owner', '=', $principalId, MySqlQueryBuilder::SQLTYPE_INT);
		$isOwner = $sqlB->formatBinExp('UU.isOwner', '=', true, MySqlQueryBuilder::SQLTYPE_BOOLEAN); //add by LWR
		$isInPrincipalWigiiNamespace = $sqlB->formatBinExp('U.wigiiNamespace', '=', $principal->getWigiiNamespace()->getWigiiNamespaceName(), MySqlQueryBuilder::SQLTYPE_VARCHAR); //add by LWR
		return "SELECT ".$this->getSqlColumnsForUser('U', $fieldSelectorList).",
case when
	UU.id_user_owner is not null
	".($principal->isReadAllUsersInWigiiNamespace()? " or $isInPrincipalWigiiNamespace " : "")."
	".($principal->isWigiiNamespaceCreator() ? " or 1 " : "")."
	then 1 else 0 end as isOwner
FROM Users as U
LEFT JOIN Users_Users as UU ON UU.id_user = U.id_user and $id_user_owner and $isOwner
where $username";
	}


	public function getAllUsers($principal, $userPList, $listFilter=null)
	{
		$this->executionSink()->publishStartOperation("getAllUsers", $principal);
		$userPListMapper = null;
		try
		{
			// checks authorization
			$pRights = $this->assertPrincipalAuthorizedForGetAllUsers($principal);

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

			$returnValue = null;
			// if principal has special rights
			if(isset($pRights))
			{
				$userPListMapper = $this->getUserPListMapper($principal, $userPList, $pRights);
				if($queryPaged)
				{
					$listFilter->setTotalNumberOfObjects($this->getMySqlFacade()->selectPage($principal,
						$this->getSqlForGetAllUsers($principal->getWigiiNamespace(), $userLogExp, $userSortingKeyList),
						$this->getDbAdminService()->getDbConnectionSettings($principal),
						$offset, $pageSize,
						$userPListMapper));
				}
				else
				{
					$returnValue = $this->getMySqlFacade()->selectAll($principal,
						$this->getSqlForGetAllUsers($principal->getWigiiNamespace(), $userLogExp, $userSortingKeyList),
						$this->getDbAdminService()->getDbConnectionSettings($principal),
						$userPListMapper);
				}
			}
			// else rights are given by DB
			else
			{
				$userPListMapper = $this->getUserPListMapper($principal, $userPList);
				if($queryPaged)
				{
					$listFilter->setTotalNumberOfObjects($this->getMySqlFacade()->selectPage($principal,
						$this->getSqlPForGetAllUsers($principal, $userLogExp, $userSortingKeyList),
						$this->getDbAdminService()->getDbConnectionSettings($principal),
						$offset, $pageSize,
						$userPListMapper));
				}
				else
				{
					$returnValue = $this->getMySqlFacade()->selectAll($principal,
						$this->getSqlPForGetAllUsers($principal, $userLogExp, $userSortingKeyList),
						$this->getDbAdminService()->getDbConnectionSettings($principal),
						$userPListMapper);
				}
			}
			if(isset($userPListMapper)) $userPListMapper->freeMemory();
		}
		catch (UserAdminServiceException $uaE){
			if(isset($userPListMapper)) $userPListMapper->freeMemory();
			$this->executionSink()->publishEndOperationOnError("getAllUsers", $uaE, $principal);
			throw $uaE;
		}
		catch (AuthorizationServiceException $asE){
			if(isset($userPListMapper)) $userPListMapper->freeMemory();
			$this->executionSink()->publishEndOperationOnError("getAllUsers", $asE, $principal);
			throw $asE;
		}
		catch(Exception $e)
		{
			if(isset($userPListMapper)) $userPListMapper->freeMemory();
			$this->executionSink()->publishEndOperationOnError("getAllUsers", $e, $principal);
			throw new UserAdminServiceException('',UserAdminServiceException::WRAPPING, $e);
		}
		$this->executionSink()->publishEndOperation("getAllUsers", $principal);
		return $returnValue;
	}
	protected function assertPrincipalAuthorizedForGetAllUsers($principal)
	{
		$pRights = null;
		// checks general authorization
		$pRights = $this->getAuthorizationService()->assertPrincipalAuthorized($principal, "UserAdminService", "getAllUsers");
		return $pRights;
	}
	/**
	 * Returns all users in wigiiNamespace
	 */
	protected function getSqlForGetAllUsers($wigiiNamespace, $userLogExp=null, $userSortingKeyList=null)
	{
		if(isset($wigiiNamespace)) $wigiiNamespace = $wigiiNamespace->getWigiiNamespaceName();
		$sqlB = $this->getSqlWhereClauseBuilderForSelectUsers('U');
		$wigiiNamespace = $sqlB->formatBinExp('U.wigiiNamespace', '=', $wigiiNamespace, MySqlQueryBuilder::SQLTYPE_VARCHAR);
		// adds where clause
		if(!is_null($userLogExp))
		{
			// reduces logExp and clones it.
			$userLogExp = $userLogExp->reduceNegation(true);
			$userLogExp = $sqlB->buildWhereClause($userLogExp);
			if(!is_null($userLogExp) && $userLogExp != '') $userLogExp = " and ".$userLogExp;
		}
		// order by clause
		if(!is_null($userSortingKeyList))
		{
			$sqlB->convertFieldSortingKeyListToOrderByClause($userSortingKeyList);
			$orderByClause = ' '.$sqlB->getOrderByClause();
		} else $orderByClause='';
		return "select ".$this->getSqlColumnsForUser('U')." from Users as U where $wigiiNamespace".$userLogExp.$orderByClause;
	}
	protected function getSqlPForGetAllUsers($principal, $userLogExp=null, $userSortingKeyList=null)
	{
		$principalId = $principal->getUserId();
		if(is_null($principalId)) throw new UserAdminServiceException('principal attached user can not be null', UserAdminServiceException::INVALID_ARGUMENT);
		$sqlB = $this->getSqlWhereClauseBuilderForSelectUsers('U');
		/* CWE 2015.10.07: optimize SQL query
		$id_user_owner = $sqlB->formatBinExp('UU.id_user_owner', '=', $principalId, MySqlQueryBuilder::SQLTYPE_INT);
		$isOwner = $sqlB->formatBinExp('UU.isOwner', '=', true, MySqlQueryBuilder::SQLTYPE_BOOLEAN); //add by LWR
		$isInPrincipalWigiiNamespace = $sqlB->formatBinExp('U.wigiiNamespace', '=', $principal->getWigiiNamespace()->getWigiiNamespaceName(), MySqlQueryBuilder::SQLTYPE_VARCHAR); //add by LWR
		// adds where clause
		if(!is_null($userLogExp))
		{
			// reduces logExp and clones it.
			$userLogExp = $userLogExp->reduceNegation(true);
			$userLogExp = $sqlB->buildWhereClause($userLogExp);
			if(!is_null($userLogExp) && $userLogExp != '') $userLogExp = " and ".$userLogExp;
		}
		// order by clause
		if(!is_null($userSortingKeyList))
		{
			$sqlB->convertFieldSortingKeyListToOrderByClause($userSortingKeyList);
			$orderByClause = ' '.$sqlB->getOrderByClause();
		} else $orderByClause='';
		return "SELECT ".$this->getSqlColumnsForUser('U').", 1 as isOwner
FROM Users as U
LEFT JOIN Users_Users as UU ON U.id_user = UU.id_user
WHERE (
	($id_user_owner and $isOwner)
	".($principal->isReadAllUsersInWigiiNamespace() ? " or $isInPrincipalWigiiNamespace " : "")."
	".($principal->isWigiiNamespaceCreator() ? " or 1 " : "")."
)".$userLogExp." GROUP BY U.id_user ".$orderByClause;
		*/
		
		// adds where clause
		if(!is_null($userLogExp))
		{
			// reduces logExp and clones it.
			$userLogExp = $userLogExp->reduceNegation(true);
			$userLogExp = $sqlB->buildWhereClause($userLogExp);
			if(!is_null($userLogExp) && $userLogExp != '') $userLogExp = " where ".$userLogExp;
		}
		else $userLogExp = '';
		// order by clause
		if(!is_null($userSortingKeyList))
		{
			$sqlB->convertFieldSortingKeyListToOrderByClause($userSortingKeyList);
			$orderByClause = ' '.$sqlB->getOrderByClause();
		} else $orderByClause='';
		
		$sql = "SELECT ".$this->getSqlColumnsForUser('U').", 1 as isOwner from Users as U ";
		// if not wigiiNamespaceCreator then sees only users owned by the principal or all from namespace
		if(!$principal->isWigiiNamespaceCreator()) {
			$sql .= " inner join (";
			// users owned by principal
			$id_user_owner = $sqlB->formatBinExp('U1.id_user_owner', '=', $principalId, MySqlQueryBuilder::SQLTYPE_INT);
			$isOwner = $sqlB->formatBinExp('U1.isOwner', '=', true, MySqlQueryBuilder::SQLTYPE_BOOLEAN);
			$sql .= "select distinct U1.id_user from Users_Users as U1 where $id_user_owner and $isOwner";			
			// all users from namespace
			if($principal->isReadAllUsersInWigiiNamespace()) {
				$sql .= " union distinct ";
				$isInPrincipalWigiiNamespace = $sqlB->formatBinExp('U2.wigiiNamespace', '=', $principal->getWigiiNamespace()->getWigiiNamespaceName(), MySqlQueryBuilder::SQLTYPE_VARCHAR);
				$sql .= "select U2.id_user from Users as U2 where $isInPrincipalWigiiNamespace";
			}
			$sql .= ") as Up on Up.id_user = U.id_user ";
		}
		$sql .= $userLogExp.$orderByClause;
		return $sql;
	}

	public function getAllUsersFromSystem($principal, $userPList, $listFilter=null)
	{
		$this->executionSink()->publishStartOperation("getAllUsersFromSystem", $principal);
		$userPListMapper = null;
		try
		{
			// checks authorization
			$pRights = $this->assertPrincipalAuthorizedForGetAllUsersFromSystem($principal);

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

			$returnValue = null;
			// if principal has special rights
			if(isset($pRights))
			{
				$userPListMapper = $this->getUserPListMapper($principal, $userPList, $pRights);
				if($queryPaged)
				{
					$listFilter->setTotalNumberOfObjects($this->getMySqlFacade()->selectPage($principal,
						$this->getSqlForGetAllUsersFromSystem($principal->getWigiiNamespace(), $userLogExp, $userSortingKeyList),
						$this->getDbAdminService()->getDbConnectionSettings($principal),
						$offset, $pageSize,
						$userPListMapper));
				}
				else
				{
					$returnValue = $this->getMySqlFacade()->selectAll($principal,
						$this->getSqlForGetAllUsersFromSystem($principal->getWigiiNamespace(), $userLogExp, $userSortingKeyList),
						$this->getDbAdminService()->getDbConnectionSettings($principal),
						$userPListMapper);
				}
			}
			// else rights are given by DB
			else
			{
				$userPListMapper = $this->getUserPListMapper($principal, $userPList);
				if($queryPaged)
				{
					$listFilter->setTotalNumberOfObjects($this->getMySqlFacade()->selectPage($principal,
						$this->getSqlPForGetAllUsersFromSystem($principal, $userLogExp, $userSortingKeyList),
						$this->getDbAdminService()->getDbConnectionSettings($principal),
						$offset, $pageSize,
						$userPListMapper));
				}
				else
				{
					$returnValue = $this->getMySqlFacade()->selectAll($principal,
						$this->getSqlPForGetAllUsersFromSystem($principal, $userLogExp, $userSortingKeyList),
						$this->getDbAdminService()->getDbConnectionSettings($principal),
						$userPListMapper);
				}
			}
			if(isset($userPListMapper)) $userPListMapper->freeMemory();
		}
		catch (UserAdminServiceException $uaE){
			if(isset($userPListMapper)) $userPListMapper->freeMemory();
			$this->executionSink()->publishEndOperationOnError("getAllUsersFromSystem", $uaE, $principal);
			throw $uaE;
		}
		catch (AuthorizationServiceException $asE){
			if(isset($userPListMapper)) $userPListMapper->freeMemory();
			$this->executionSink()->publishEndOperationOnError("getAllUsersFromSystem", $asE, $principal);
			throw $asE;
		}
		catch(Exception $e)
		{
			if(isset($userPListMapper)) $userPListMapper->freeMemory();
			$this->executionSink()->publishEndOperationOnError("getAllUsersFromSystem", $e, $principal);
			throw new UserAdminServiceException('',UserAdminServiceException::WRAPPING, $e);
		}
		$this->executionSink()->publishEndOperation("getAllUsersFromSystem", $principal);
		return $returnValue;
	}
	protected function assertPrincipalAuthorizedForGetAllUsersFromSystem($principal)
	{
		$pRights = null;
		// checks general authorization
		$pRights = $this->getAuthorizationService()->assertPrincipalAuthorized($principal, "UserAdminService", "getAllUsersFromSystem");
		return $pRights;
	}
	/**
	 * Returns all users of system
	 */
	protected function getSqlForGetAllUsersFromSystem($wigiiNamespace, $userLogExp=null, $userSortingKeyList=null)
	{
		if(isset($wigiiNamespace)) $wigiiNamespace = $wigiiNamespace->getWigiiNamespaceName();
		$sqlB = $this->getSqlWhereClauseBuilderForSelectUsers('U');
		// adds where clause
		if(!is_null($userLogExp))
		{
			// reduces logExp and clones it.
			$userLogExp = $userLogExp->reduceNegation(true);
			$userLogExp = $sqlB->buildWhereClause($userLogExp);
		}
		// order by clause
		if(!is_null($userSortingKeyList))
		{
			$sqlB->convertFieldSortingKeyListToOrderByClause($userSortingKeyList);
			$orderByClause = ' '.$sqlB->getOrderByClause();
		} else $orderByClause='';
		return "select ".$this->getSqlColumnsForUser('U')." from Users as U where ".$userLogExp.$orderByClause;
	}
	protected function getSqlPForGetAllUsersFromSystem($principal, $userLogExp=null, $userSortingKeyList=null)
	{
		$principalId = $principal->getUserId();
		if(is_null($principalId)) throw new UserAdminServiceException('principal attached user can not be null', UserAdminServiceException::INVALID_ARGUMENT);
		$sqlB = $this->getSqlWhereClauseBuilderForSelectUsers('U');
		$id_user_owner = $sqlB->formatBinExp('UU.id_user_owner', '=', $principalId, MySqlQueryBuilder::SQLTYPE_INT);
		$isOwner = $sqlB->formatBinExp('UU.isOwner', '=', true, MySqlQueryBuilder::SQLTYPE_BOOLEAN); //add by LWR
		$isInPrincipalWigiiNamespace = $sqlB->formatBinExp('U.wigiiNamespace', '=', $principal->getWigiiNamespace()->getWigiiNamespaceName(), MySqlQueryBuilder::SQLTYPE_VARCHAR); //add by LWR
		// adds where clause
		if(!is_null($userLogExp))
		{
			// reduces logExp and clones it.
			$userLogExp = $userLogExp->reduceNegation(true);
			$userLogExp = $sqlB->buildWhereClause($userLogExp);
		}
		// order by clause
		if(!is_null($userSortingKeyList))
		{
			$sqlB->convertFieldSortingKeyListToOrderByClause($userSortingKeyList);
			$orderByClause = ' '.$sqlB->getOrderByClause();
		} else $orderByClause='';
		return "SELECT ".$this->getSqlColumnsForUser('U').", IF((
	($id_user_owner and $isOwner)
	".($principal->isReadAllUsersInWigiiNamespace() ? " or $isInPrincipalWigiiNamespace " : "")."
	".($principal->isWigiiNamespaceCreator() ? " or 1 " : "")."
),1,0) as isOwner
FROM Users as U
LEFT JOIN Users_Users as UU ON U.id_user = UU.id_user
WHERE ".$userLogExp." GROUP BY U.id_user ".$orderByClause;
	}

	public function calculateAllMergedRoles($principal){
		$this->executionSink()->publishStartOperation("calculateAllMergedRoles", $principal);
		try
		{
			// checks authorization
			$this->assertPrincipalAuthorizedForCalculateAllMergedRoles($principal);

			//get all real roles of the principal
			//the UserListForCalculateAllMergedRoles
			//group by wigiiNamespace the roles
			//separate the calculated Roles if exists
			$roleList = UserListForCalculateAllMergedRoles::createInstance();
			$lf = $this->getListFilterForRoleToCalculateCalculatedRoleList();
			$this->getMyRoles($principal, $roleList, $lf);

			if($principal->isPlayingRole()){
				$realUser = $principal->getRealUser();
			} else {
				$realUser = $principal->getAttachedUser();
			}
			//add his own attached user to the list
			$roleList->addUser($realUser);

			//expired user/role are removed in the UserListForCalculateAllMergeRoleImpl

			$roleList->resetCalculatedRoleStack();

			$dbAS = $this->getDbAdminService();
			$dbCS = $dbAS->getDbConnectionSettings($principal);
			$mySqlF = $this->getMySqlFacade();

			//fput($roleList);

			//foreach existing calculated role delete all UGR
			$calculatedRoleIds = $roleList->getCalculatedRoleIds();
			if($calculatedRoleIds){
				$dbAS->deleteRows($principal,
						$this->getSqlTableNameForDeleteUGR(),
						$this->getSqlWhereClauseForDeleteUGR($calculatedRoleIds),
						$dbCS);
				$this->executionSink()->log("Remove all UGR for calculated roles: ".implode(", ", $calculatedRoleIds)." for user ".$realUser->getUsername().".");
//				fput("Remove all UGR for calculated roles: ".implode(", ", $calculatedRoleIds)." for user ".$realUser->getUsername().".");
			}

//			eput($roleList->getWigiiNamespaceIterator());
			if($roleList->getWigiiNamespaceIterator()){
				//for each wigiiNamespace create a merged role
				$nb1 = rand(1000, 9999);
				$nb2 = rand(1000, 9999);
				$nb3 = rand(1000, 9999);
				$nb4 = rand(1000, 9999);
				$nb5 = rand(1000, 9999);
				foreach($roleList->getWigiiNamespaceIterator() as $wigiiNamespaceUrl=>$wigiiNamespace){
					$calculatedRole = $roleList->popCalculatedRoleFromStack($wigiiNamespaceUrl);

					if(!$calculatedRole) $calculatedRole = $this->createUserInstance();

					//fput("calculatedRoleName: ".$calculatedRole->getUsername()." realUserName:".$realUser->getUsername());

					//if we cannot find the realUsername in the current username then reset the calculatedName
					//that means either the user has been renamed, either the calculated role is not existing yet
					if(strpos($calculatedRole->getUsername(), $realUser->getUsername())===false){
						//check availibility of this username, or if already used of 5 other username with a random number
						$calculatedRoleName = array();
						$calculatedRoleName[] = $realUser->getUsername()."@".$wigiiNamespaceUrl;
						$calculatedRoleName[] = $realUser->getUsername()."_$nb1@".$wigiiNamespaceUrl;
						$calculatedRoleName[] = $realUser->getUsername()."_$nb2@".$wigiiNamespaceUrl;
						$calculatedRoleName[] = $realUser->getUsername()."_$nb3@".$wigiiNamespaceUrl;
						$calculatedRoleName[] = $realUser->getUsername()."_$nb4@".$wigiiNamespaceUrl;
						$calculatedRoleName[] = $realUser->getUsername()."_$nb5@".$wigiiNamespaceUrl;

						//access DB to check validity of possible names
						$rowList = RowListFindFirstCalculatedNameAvailableImpl::createInstance($calculatedRoleName);
						$returnValue = $this->getMySqlFacade()->selectAll($principal,
							$this->getSqlForCheckCalculatedNameAvailibility($principal, $calculatedRoleName),
							$dbCS, $rowList
						);
						if($returnValue > 0){
							//take the first available proposition
							$calculatedRole->setUsername($rowList->getFirstFreeProposition());
						} else {
							$calculatedRole->setUsername(reset($calculatedRoleName));
						}
					}

					$calculatedRole->setWigiiNamespace($wigiiNamespace);
					$calculatedRole->setRole(true);
					$calculatedRole->setCalculatedRole(true);
					$calculatedRole->getDetail()->setModuleAccess(false); //$fullModuleAccess);

					//set admin rights as empty
					//(config on a user is no more available with this concept)
					$calculatedRole->getDetail()->setWigiiNamespaceCreator(false);
					$calculatedRole->getDetail()->setModuleEditor(false);
					$calculatedRole->getDetail()->setReadAllUsersInWigiiNamespace(false);
					$calculatedRole->getDetail()->setAdminCreator(false);
					$calculatedRole->getDetail()->setUserCreator(false);
					$calculatedRole->getDetail()->setReadAllGroupsInWigiiNamespace(false);
					$calculatedRole->getDetail()->setRootGroupCreator(false);
					$calculatedRole->getDetail()->setGroupCreator(false);

					//if calculated role does not exist insert new role + create hasRole relation with principal
					if(!$calculatedRole->getId()){
						try{
							$calculatedRole->setId(
								$mySqlF->insertIfUnique($principal,
									$this->getSqlForInsertUser($principal, $calculatedRole, true),
									$this->getSqlForCheckUserUnique($calculatedRole),
									$dbCS)
							);
							$this->executionSink()->log($calculatedRole->getUsername()." : ".$calculatedRole->getId()." has been created.");
							//fput($calculatedRole->getUsername()." : ".$calculatedRole->getId()." has been created.");
						} catch(MySqlFacadeException $e) {
							if($e->getCode() == MySqlFacadeException::MYSQL_NOTUNIQUE){
								throw new UserAdminServiceException("username already used", UserAdminServiceException::USERNAME_ALREADY_USED);
							}
							throw $e;
						}
						// inserts principal as having role
						try
						{
							$mySqlF->insertOne($principal,
								$this->getSqlForSetUserRole($realUser->getId(), $calculatedRole->getId()),
								$dbCS);
							$this->executionSink()->log($calculatedRole->getUsername()." is added as role for user ".$realUser->getUsername().".");
							//fput($calculatedRole->getUsername()." is added as role for user ".$realUser->getUsername().".");
						}
						// if insertion error, then deletes first created user and throws exception
						catch(Exception $e)
						{
							// unlocks user
							$this->unLock($principal, $calculatedRole);
							// deletes user
							try
							{
								$dbAS->deleteRows($principal,
									$this->getSqlTableNameForDeleteUser(),
									$this->getSqlWhereClauseForDeleteUser($calculatedRole),
									$dbCS);
								$this->executionSink()->log("An error occuered in creating ".$calculatedRole->getUsername().". This user is deleted.");
								//fput("An error occuered in creating ".$calculatedRole->getUsername().". This user is deleted.");
							}
							catch(Exception $e1){/* does nothing, it will be automatically deleted on next delete */}
							throw $e;
						}
						// unlocks user
						$this->unLock($principal, $calculatedRole);
					}

					//find all UGR of roles and duplicate them
					$roleIds = $roleList->getRoleIds($wigiiNamespaceUrl);
					$mySqlF->insertMultiple($principal,
						$this->getSqlForDuplicateUGRForCalculatedRole($principal, $calculatedRole->getId(), $roleIds),
						$dbCS);
					$this->executionSink()->log("Duplicate all UGR from roles: ".implode(", ", $roleIds)." for calculated role ".$calculatedRole->getUsername().".");
	//				fput("Duplicate all UGR from roles: ".implode(", ", $roleIds)." for calculated role ".$calculatedRole->getUsername().".");

					//foreach module where at least one role has access to all groups then
					//create admin UGR on each root group of the module in the wigiiNamespace
					$readAllGroupsModulenames = $roleList->getReadAllGroupsInWigiiNamespace($wigiiNamespaceUrl);
					if($readAllGroupsModulenames){
						$mySqlF->insertMultiple($principal,
							$this->getSqlForSetAdminUGRInReadAllGroupsModuleForCalculatedRole($principal, $wigiiNamespace->getWigiiNamespaceName(), $calculatedRole->getId(), $readAllGroupsModulenames),
							$dbCS);
						$this->executionSink()->log("Set Admin UGR on each root group in all modules where at least one role isReadAllGroupsInWigiiNamespace: ".implode(", ", array_keys($roleList->getReadAllGroupsInWigiiNamespace($wigiiNamespaceUrl)))." for calculated role ".$calculatedRole->getUsername().".");
	//					fput("Set Admin UGR on each root group in all modules where at least one role isReadAllGroupsInWigiiNamespace: ".implode(", ", array_keys($roleList->getReadAllGroupsInWigiiNamespace($wigiiNamespaceUrl)))." for calculated role ".$calculatedRole->getUsername().".");
					}

					//setup module access and groupCreator based on calculated UGR
					//access DB to fetch all the modules in UGR + in which there are admin
					$rowListForModuleAccess = RowListCalculateModuleAccessForCalculatedRoleImpl::createInstance($calculatedRole, $this->getModuleAdminService());
					$returnValue = $this->getMySqlFacade()->selectAll($principal,
						$this->getSqlForCalculateModuleAccessForCalculatedRole($principal, $calculatedRole->getId()),
						$dbCS, $rowListForModuleAccess
					);
					$calculatedRole->getDetail()->setModuleAccess($rowListForModuleAccess->getModuleAccess($principal));
					$calculatedRole->getDetail()->setGroupCreator($rowListForModuleAccess->getGroupCreator($principal));
					$this->executionSink()->log("ModuleAccess and GroupCreator has been setup for calculated role ".$calculatedRole->getUsername().": ".$rowListForModuleAccess->getSummaryString().".");
	//				fput("ModuleAccess and GroupCreator has been setup for calculated role ".$calculatedRole->getUsername().": ".$rowListForModuleAccess->getSummaryString().".");

					//update the calculatedRole with latest calculation
					$returnValue = $this->getMySqlFacade()->update($principal,
						$this->getSqlForUpdateUser($principal, $calculatedRole, null, null),
						$dbCS
					);
					$this->executionSink()->log("Calculated role ".$calculatedRole->getUsername()." is updated.");
	//				fput("Calculated role ".$calculatedRole->getUsername()." is updated.");
				}
			}

			//if some calculatedRole was existing but was not updated in the previous loop, delete them
			$remainingCalculatedRoles = $roleList->getCalculatedRoleStackIterator();
			if($remainingCalculatedRoles){
				$ids = array();
				foreach($remainingCalculatedRoles as $role){
					$ids[] = $role->getId();
				}
				$dbAS->deleteRows($principal,
					$this->getSqlTableNameForDeleteUser(),
					$this->getSqlForDeleteRemainingCalculatedRole($ids),
					$dbCS);
				//updates elements state_locked = 1 if sys_user = userId
				$this->getMySqlFacade()->update($principal,
					$this->getSqlForCorrectState_lockedForDeleteUsers($ids),
					$dbCS);
				$this->executionSink()->log(count($remainingCalculatedRoles)." calculatedRoles (".implode(", ", $ids).") of user ".$realUser->getUsername()." are deleted because no more needed.");
//				fput(count($remainingCalculatedRoles)." calculatedRoles (".implode(", ", $ids).") of user ".$realUser->getUsername()." are deleted because no more needed.");
			}

			$returnValue = true;
		}
		catch (UserAdminServiceException $uaE){
			$this->executionSink()->publishEndOperationOnError("calculateAllMergedRoles", $uaE, $principal);
			throw $uaE;
		}
		catch (AuthorizationServiceException $asE){
			$this->executionSink()->publishEndOperationOnError("calculateAllMergedRoles", $asE, $principal);
			throw $asE;
		}
		catch(Exception $e)
		{
			$this->executionSink()->publishEndOperationOnError("calculateAllMergedRoles", $e, $principal);
			throw new UserAdminServiceException('',UserAdminServiceException::WRAPPING, $e);
		}
		$this->executionSink()->publishEndOperation("calculateAllMergedRoles", $principal);
		return $returnValue;
	}
	protected function assertPrincipalAuthorizedForCalculateAllMergedRoles($principal)
	{
		// checks general authorization
		return $this->getAuthorizationService()->assertPrincipalAuthorized($principal, "UserAdminService", "calculateAllMergedRoles");
	}

	protected function getSqlForSetAdminUGRInReadAllGroupsModuleForCalculatedRole($principal, $wigiiNamespaceName, $calculatedRoleId, $readAllGroupsModulenames){
		if(is_null($calculatedRoleId)) throw new UserAdminServiceException('calculatedRoleId can not be null', UserAdminServiceException::INVALID_ARGUMENT);
		if(is_null($readAllGroupsModulenames)) throw new UserAdminServiceException('readAllGroupsModulenames can not be null', UserAdminServiceException::INVALID_ARGUMENT);

		$sqlB = $this->getMySqlFacade()->getSqlBuilder();
		$inWigiiNamespace = $sqlB->formatBinExp('g.wigiiNamespace', '=', $wigiiNamespaceName, MySqlQueryBuilder::SQLTYPE_VARCHAR);
		$inModule = $sqlB->formatBinExp('g.modulename', 'IN', array_keys($readAllGroupsModulenames), MySqlQueryBuilder::SQLTYPE_VARCHAR);
		$isRootGroup = $sqlB->formatBinExp('g.id_group_parent', '=', null, MySqlQueryBuilder::SQLTYPE_INT);

		$sql = "INSERT ".$this->getSqlTableNameForDeleteUGR()."
(id_user, id_group, canModify, canWriteElement, canShareElement)
SELECT
	$calculatedRoleId,
	g.id_group,
	true,
	true,
	true
FROM ".$this->getSqlTableNameForDeleteGroup()." as g
WHERE $inWigiiNamespace AND $inModule AND $isRootGroup
ON DUPLICATE KEY UPDATE
	canModify = true,
	canwriteElement = true,
	canShareElement = true
;
";
//		fput($sql);
		return $sql;
	}
	protected function getSqlForDuplicateUGRForCalculatedRole($principal, $calculatedRoleId, $roleIds){
		if(is_null($calculatedRoleId)) throw new UserAdminServiceException('calculatedRoleId can not be null', UserAdminServiceException::INVALID_ARGUMENT);
		if(is_null($roleIds)) throw new UserAdminServiceException('roleIds can not be null', UserAdminServiceException::INVALID_ARGUMENT);
		$sql = "INSERT ".$this->getSqlTableNameForDeleteUGR()."
(id_user, id_group, canModify, canWriteElement, canShareElement)
SELECT
	$calculatedRoleId,
	ugr2.id_group,
	MAX(ugr2.canModify),
	MAX(ugr2.canWriteElement),
	MAX(ugr2.canShareElement)
FROM ".$this->getSqlTableNameForDeleteUGR()." as ugr2
WHERE ugr2.id_user IN (".implode(", ",$roleIds).")
GROUP BY ugr2.id_group;
";
//		fput($sql);
		return $sql;
	}
	protected function getSqlWhereClauseForDeleteUGR($calculatedRoleIds){
		if(is_null($calculatedRoleIds)) throw new UserAdminServiceException('calculatedRoleIds can not be null', UserAdminServiceException::INVALID_ARGUMENT);
		$sqlB = $this->getMySqlFacade()->getSqlBuilder();
		return $sqlB->formatBinExp($this->getSqlTableNameForDeleteUGR().'.id_user', 'IN', $calculatedRoleIds, MySqlQueryBuilder::SQLTYPE_INT);
	}
	protected function getSqlForDeleteRemainingCalculatedRole($ids){
		if(is_null($ids)) throw new UserAdminServiceException('ids can not be null', UserAdminServiceException::INVALID_ARGUMENT);
		$sqlB = $this->getMySqlFacade()->getSqlBuilder();
		return $sqlB->formatBinExp('Users.id_user', 'IN', $ids, MySqlQueryBuilder::SQLTYPE_INT);
	}
	protected function getSqlForCalculateModuleAccessForCalculatedRole($principal, $calculatedRoleId){
		$sql = "SELECT g.moduleName as moduleName, MAX(ugr.canModify) as groupCreator
FROM ".$this->getSqlTableNameForDeleteGroup()." g
LEFT JOIN ".$this->getSqlTableNameForDeleteUGR()." ugr ON ugr.id_group = g.id_group
WHERE ugr.id_user = $calculatedRoleId AND ugr.id_group = g.id_group GROUP BY modulename;";
//		fput($sql);
		return $sql;
	}
	protected function getSqlForCheckCalculatedNameAvailibility($principal, $calculatedRoleName){
		$sql = "SELECT id_user, username
FROM ".$this->getSqlTableNameForDeleteUser()."
WHERE username IN('".implode("','", $calculatedRoleName)."');";
//		fput($sql);
		return $sql;
	}

	public function getMyRoles($principal, $roleList, $listFilter=null)
	{
		$this->executionSink()->publishStartOperation("getMyRoles", $principal);
		$userListMapper = null;
		try
		{
			// checks authorization
			$this->assertPrincipalAuthorizedForGetMyRoles($principal);
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

			$returnValue = null;

			$userListMapper = $this->getUserListMapper($principal, $roleList);
			if($queryPaged)
			{
				$listFilter->setTotalNumberOfObjects($this->getMySqlFacade()->selectPage($principal,
					$this->getSqlForGetMyRoles($principal, $userLogExp, $userSortingKeyList),
					$this->getDbAdminService()->getDbConnectionSettings($principal),
					$offset, $pageSize,
					$userListMapper));
			}
			else
			{
				$returnValue = $this->getMySqlFacade()->selectAll($principal,
					$this->getSqlForGetMyRoles($principal, $userLogExp, $userSortingKeyList),
					$this->getDbAdminService()->getDbConnectionSettings($principal),
					$userListMapper);
			}
			$userListMapper->freeMemory();
		}
		catch (UserAdminServiceException $uaE){
			if(isset($userListMapper)) $userListMapper->freeMemory();
			$this->executionSink()->publishEndOperationOnError("getMyRoles", $uaE, $principal);
			throw $uaE;
		}
		catch (AuthorizationServiceException $asE){
			if(isset($userListMapper)) $userListMapper->freeMemory();
			$this->executionSink()->publishEndOperationOnError("getMyRoles", $asE, $principal);
			throw $asE;
		}
		catch(Exception $e)
		{
			if(isset($userListMapper)) $userListMapper->freeMemory();
			$this->executionSink()->publishEndOperationOnError("getMyRoles", $e, $principal);
			throw new UserAdminServiceException('',UserAdminServiceException::WRAPPING, $e);
		}
		$this->executionSink()->publishEndOperation("getMyRoles", $principal);
		return $returnValue;
	}
	protected function assertPrincipalAuthorizedForGetMyRoles($principal)
	{
		// checks general authorization
		return $this->getAuthorizationService()->assertPrincipalAuthorized($principal, "UserAdminService", "getMyRoles");
	}
	protected function getSqlForGetMyRoles($principal, $userLogExp=null, $userSortingKeyList=null)
	{
		$principalId = $principal->getRealUserId();
		if(is_null($principalId)) throw new UserAdminServiceException('principal attached or real user can not be null', UserAdminServiceException::INVALID_ARGUMENT);

		$sqlB = $this->getSqlWhereClauseBuilderForSelectUsers('U');
		$id_user_owner = $sqlB->formatBinExp('UU.id_user_owner', '=', $principalId, MySqlQueryBuilder::SQLTYPE_INT);
		$hasRole = $sqlB->formatBinExp('UU.hasRole', '=', true, MySqlQueryBuilder::SQLTYPE_BOOLEAN);
		// adds where clause
		if(!is_null($userLogExp))
		{
			// reduces logExp and clones it.
			$userLogExp = $userLogExp->reduceNegation(true);
			$userLogExp = $sqlB->buildWhereClause($userLogExp);
			if(!is_null($userLogExp) && $userLogExp != '') $userLogExp = " and ".$userLogExp;
		}
		// order by clause
		if(!is_null($userSortingKeyList))
		{
			$sqlB->convertFieldSortingKeyListToOrderByClause($userSortingKeyList);
			$orderByClause = ' '.$sqlB->getOrderByClause();
		} else $orderByClause='';
		$temp = "SELECT ".$this->getSqlColumnsForUser('U')."
FROM Users_Users as UU
INNER JOIN Users as U ON U.id_user = UU.id_user
WHERE $id_user_owner and $hasRole
".$userLogExp.$orderByClause;
//		eput($temp);
//		fput($temp);
		return $temp;
	}

	public function getAllUserUsers($principal, $userId, $userURPList, $listFilter=null)
	{
		$this->executionSink()->publishStartOperation("getAllUserUsers", $principal);
		$userURPMapper = null;
		try
		{
			// gets userP
			if(!isset($userId)) throw new UserAdminServiceException("userId cannot be null", UserAdminServiceException::INVALID_ARGUMENT);
			$userP = $this->getUser($principal, $userId, $this->getFieldSelectorListForUserWithoutDetail());
			if(!isset($userP)) throw new UserAdminServiceException("user $userId does not exist in database", UserAdminServiceException::INVALID_ARGUMENT);

			// checks authorization
			$pRights = $this->assertPrincipalAuthorizedForGetAllUserUsers($principal, $userP);

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

			$userURPMapper = $this->getUserURPListMapper($principal, $userId, $userURPList, $pRights, 'P');
			if($queryPaged)
			{
				$listFilter->setTotalNumberOfObjects($this->getMySqlFacade()->selectPage($principal,
						$this->getSqlForGetAllUserUsers($principal, $userP->getUser(), $fieldSelectorList, 'P', $userLogExp, $userSortingKeyList),
						$this->getDbAdminService()->getDbConnectionSettings($principal), $offset, $pageSize,
						$userURPMapper));
			}
			else
			{
				$this->getMySqlFacade()->selectAll($principal,
						$this->getSqlForGetAllUserUsers($principal, $userP->getUser(), $fieldSelectorList, 'P', $userLogExp, $userSortingKeyList),
						$this->getDbAdminService()->getDbConnectionSettings($principal),
						$userURPMapper);
			}
			$returnValue = $userURPMapper->count();
			$userURPMapper->freeMemory();
		}
		catch (UserAdminServiceException $uaE){
			if(isset($userURPMapper)) $userURPMapper->freeMemory();
			$this->executionSink()->publishEndOperationOnError("getAllUserUsers", $uaE, $principal);
			throw $uaE;
		}
		catch (AuthorizationServiceException $asE){
			if(isset($userURPMapper)) $userURPMapper->freeMemory();
			$this->executionSink()->publishEndOperationOnError("getAllUserUsers", $asE, $principal);
			throw $asE;
		}
		catch(Exception $e)
		{
			if(isset($userURPMapper)) $userURPMapper->freeMemory();
			$this->executionSink()->publishEndOperationOnError("getAllUserUsers", $e, $principal);
			throw new UserAdminServiceException('',UserAdminServiceException::WRAPPING, $e);
		}
		$this->executionSink()->publishEndOperation("getAllUserUsers", $principal);
		return $returnValue;
	}
	protected function assertPrincipalAuthorizedForGetAllUserUsers($principal, $userP)
	{
		$pRights = null;
		// checks general authorization
		$pRights = $this->getAuthorizationService()->assertPrincipalAuthorized($principal, "UserAdminService", "getAllUserUsers");
		return $pRights;
	}
	protected function getSqlForGetAllUserUsers($principal, $user, $fieldSelectorList=null, $pRightPrefix='P', $userLogExp=null, $userSortingKeyList=null)
	{
		if(is_null($user)) throw new UserAdminServiceException("user cannot be null", UserAdminServiceException::INVALID_ARGUMENT);
		$userId = $user->getId();
		if(is_null($userId)) throw new UserAdminServiceException("user->id cannot be null", UserAdminServiceException::INVALID_ARGUMENT);
		$principalId = $principal->getUserId();
		if(is_null($principalId)) throw new UserAdminServiceException('principal attached user can not be null', UserAdminServiceException::INVALID_ARGUMENT);

		$sqlB = $this->getSqlWhereClauseBuilderForSelectUsers('U');
		$userId = $sqlB->formatValue($userId, MySqlQueryBuilder::SQLTYPE_INT);
		$principalId = $sqlB->formatValue($principalId, MySqlQueryBuilder::SQLTYPE_INT);
		$isInPrincipalWigiiNamespace = $sqlB->formatBinExp('U.wigiiNamespace', '=', $principal->getWigiiNamespace()->getWigiiNamespaceName(), MySqlQueryBuilder::SQLTYPE_VARCHAR); //add by LWR

		$uCols = $this->getSqlColumnsForUser('U', $fieldSelectorList);
		if($uCols != '') $uCols .= ', ';

		// adds where clause
		if(!is_null($userLogExp))
		{
			// reduces logExp and clones it.
			$userLogExp = $userLogExp->reduceNegation(true);
			$userLogExp = $sqlB->buildWhereClause($userLogExp);
			if(!is_null($userLogExp) && $userLogExp != '') $userLogExp = " and ".$userLogExp;
		}
		// order by clause
		if(!is_null($userSortingKeyList))
		{
			$sqlB->convertFieldSortingKeyListToOrderByClause($userSortingKeyList);
			$orderByClause = ' '.$sqlB->getOrderByClause();
		} else $orderByClause='';

		return "SELECT $uCols 1 as isOwner,
case when
	UUp.id_user_owner is not null
	".($principal->isReadAllUsersInWigiiNamespace() ? " or $isInPrincipalWigiiNamespace " : "")."
	".($principal->isWigiiNamespaceCreator() ? " or 1 " : "")."
	then 1 else 0 end as $pRightPrefix"."isOwner
FROM Users U
INNER JOIN Users_Users UUu ON UUu.id_user = U.id_user
LEFT JOIN Users_Users UUp ON UUp.id_user = U.id_user and UUp.isOwner = true and UUp.id_user_owner = $principalId
WHERE
	UUu.id_user_owner = $userId and UUu.isOwner = true
".$userLogExp.$orderByClause;

	}

	public function setUserOwnership($principal, $ownerId, $userId)
	{
		$this->executionSink()->publishStartOperation("setUserOwnership", $principal);
		try
		{
			if(is_null($ownerId)) throw new UserAdminServiceException("ownerId cannot be null", UserAdminServiceException::INVALID_ARGUMENT);
			if(is_null($userId)) throw new UserAdminServiceException("userId cannot be null", UserAdminServiceException::INVALID_ARGUMENT);

			$this->assertPrincipalAuthorizedForSetUserOwnership($principal, $ownerId, $userId);

			$returnValue = 0;
			$mySqlF = $this->getMySqlFacade();
			$dbCS = $this->getDbAdminService()->getDbConnectionSettings($principal);

			// updates / insert user_user ownership
			if($mySqlF->update($principal,
					$this->getSqlForSetUserOwnership($ownerId, $userId),
					$dbCS) > 0) $returnValue = 1;
		}
		catch (UserAdminServiceException $uaE){
			$this->executionSink()->publishEndOperationOnError("setUserOwnership", $uaE, $principal);
			throw $uaE;
		}
		catch (AuthorizationServiceException $asE){
			$this->executionSink()->publishEndOperationOnError("setUserOwnership", $asE, $principal);
			throw $asE;
		}
		catch(Exception $e)
		{
			$this->executionSink()->publishEndOperationOnError("setUserOwnership", $e, $principal);
			throw new UserAdminServiceException('',UserAdminServiceException::WRAPPING, $e);
		}
		$this->executionSink()->publishEndOperation("setUserOwnership", $principal);
		return $returnValue;
	}
	protected function assertPrincipalAuthorizedForSetUserOwnership($principal, $ownerId, $userId)
	{
		if(is_null($principal)) throw new UserAdminServiceException('principal can not be null', UserAdminServiceException::INVALID_ARGUMENT);
		if(is_null($ownerId)) throw new UserAdminServiceException('ownerId can not be null', UserAdminServiceException::INVALID_ARGUMENT);
		if(is_null($userId)) throw new UserAdminServiceException('userId can not be null', UserAdminServiceException::INVALID_ARGUMENT);

		$autoS = $this->getAuthorizationService();
		// checks general authorization
		$autoS->assertPrincipalAuthorized($principal, "UserAdminService", "setUserOwnership");
		//check circularity
		if($ownerId == $userId){
			$autoS->fail($principal, "Cannot give to a user himself");
		}
		//check him self
		if(!($principal->isAdminCreator() && $principal->isReadAllUsersInWigiiNamespace()) && $ownerId == $principal->getUserId()){
			$autoS->fail($principal, "cannot change his user ownership");
		}
		// checks admin rights
		if(!$principal->isAdminCreator()) $autoS->fail($principal, 'principal '.$principal->getUserId()." is not admin creator");
		// checks ownership
		if(!$this->doesPrincipalHasOwnerShipOnUsers($principal, array($ownerId, $userId))){
			$autoS->fail($principal, 'principal '.$principal->getUserId()." has no ownership on $ownerId or $userId");
		}
	}
	protected function getSqlForSetUserOwnership($ownerId, $userId)
	{
		if(is_null($ownerId)) throw new UserAdminServiceException('ownerId can not be null', UserAdminServiceException::INVALID_ARGUMENT);
		if(is_null($userId)) throw new UserAdminServiceException('userId can not be null', UserAdminServiceException::INVALID_ARGUMENT);

		$sqlB = $this->getMySqlFacade()->getSqlBuilder();
		$sqlB->setTableForUpdate('Users_Users', true);
		$sqlB->updateValue("id_user_owner", $ownerId, MySqlQueryBuilder::SQLTYPE_INT);
		$sqlB->updateValue("id_user", $userId, MySqlQueryBuilder::SQLTYPE_INT);
		$sqlB->updateValue("isOwner", true, MySqlQueryBuilder::SQLTYPE_BOOLEAN);
		return $sqlB->getSql();
	}

	public function setUserRole($principal, $userId, $roleId)
	{
		$this->executionSink()->publishStartOperation("setUserRole", $principal);
		try
		{
			if(is_null($userId)) throw new UserAdminServiceException("userId cannot be null", UserAdminServiceException::INVALID_ARGUMENT);
			if(is_null($roleId)) throw new UserAdminServiceException("roleId cannot be null", UserAdminServiceException::INVALID_ARGUMENT);

			$this->assertPrincipalAuthorizedForSetUserRole($principal, $userId, $roleId);

			$returnValue = 0;
			$mySqlF = $this->getMySqlFacade();
			$dbCS = $this->getDbAdminService()->getDbConnectionSettings($principal);

			// updates / insert user_user ownership
			if($mySqlF->update($principal,
					$this->getSqlForSetUserRole($userId, $roleId),
					$dbCS) > 0) $returnValue = 1;
		}
		catch (UserAdminServiceException $uaE){
			$this->executionSink()->publishEndOperationOnError("setUserRole", $uaE, $principal);
			throw $uaE;
		}
		catch (AuthorizationServiceException $asE){
			$this->executionSink()->publishEndOperationOnError("setUserRole", $asE, $principal);
			throw $asE;
		}
		catch(Exception $e)
		{
			$this->executionSink()->publishEndOperationOnError("setUserRole", $e, $principal);
			throw new UserAdminServiceException('',UserAdminServiceException::WRAPPING, $e);
		}
		$this->executionSink()->publishEndOperation("setUserRole", $principal);
		return $returnValue;
	}
	protected function assertPrincipalAuthorizedForSetUserRole($principal, $userId, $roleId)
	{
		if(is_null($principal)) throw new UserAdminServiceException('principal can not be null', UserAdminServiceException::INVALID_ARGUMENT);
		if(is_null($userId)) throw new UserAdminServiceException('userId can not be null', UserAdminServiceException::INVALID_ARGUMENT);
		if(is_null($roleId)) throw new UserAdminServiceException('roleId can not be null', UserAdminServiceException::INVALID_ARGUMENT);

		$autoS = $this->getAuthorizationService();
		// checks general authorization
		$autoS->assertPrincipalAuthorized($principal, "UserAdminService", "setUserRole");
		// checks admin rights
		//if(!$principal->isUserCreator()) $autoS->fail($principal, 'principal '.$principal->getUserId()." is not user creator");
		if(!$principal->getModuleAccess(Module::ADMIN_MODULE)) $autoS->fail($principal, 'principal '.$principal->getUserId()." has no access to Admin module");

		//check circularity
		if($userId == $roleId){
			$autoS->fail($principal, "Cannot give to a role itself");
		}

		// checks ownership
		//since 27/09/2012 there is no more need to have ownership on the user to change role allocation of a role
		//we can manage. That opens the ability to allocate however to roles that we can manage. This is correct
		//as it gives the ability to an administrator to give access to who he wants only on what he can manage.
//		if(!$this->doesPrincipalHasOwnerShipOnUsers($principal, array($userId))){
//			$autoS->fail($principal, 'principal '.$principal->getUserId()." has no ownership on $userId");
//		}
		if(!$this->canPrincipalGiveThoseRoles($principal, array($roleId))){
			$autoS->fail($principal, 'principal '.$principal->getUserId()." can not give role $roleId");
		}
	}
	protected function getSqlForSetUserRole($userId, $roleId)
	{
		if(is_null($userId)) throw new UserAdminServiceException('userId can not be null', UserAdminServiceException::INVALID_ARGUMENT);
		if(is_null($roleId)) throw new UserAdminServiceException('roleId can not be null', UserAdminServiceException::INVALID_ARGUMENT);

		$sqlB = $this->getMySqlFacade()->getSqlBuilder();
		$sqlB->setTableForUpdate('Users_Users', true);
		$sqlB->updateValue("id_user_owner", $userId, MySqlQueryBuilder::SQLTYPE_INT);
		$sqlB->updateValue("id_user", $roleId, MySqlQueryBuilder::SQLTYPE_INT);
		$sqlB->updateValue("hasRole", true, MySqlQueryBuilder::SQLTYPE_BOOLEAN);
		return $sqlB->getSql();
	}

	public function removeUserOwnership($principal, $ownerId, $userId)
	{
		$this->executionSink()->publishStartOperation("removeUserOwnership", $principal);
		try
		{
			if(is_null($ownerId)) throw new UserAdminServiceException("ownerId cannot be null", UserAdminServiceException::INVALID_ARGUMENT);
			if(is_null($userId)) throw new UserAdminServiceException("userId cannot be null", UserAdminServiceException::INVALID_ARGUMENT);

			$this->assertPrincipalAuthorizedForRemoveUserOwnership($principal, $ownerId, $userId);
			//from april 2010, the desicion is made that users with no ownership are not
			//automaticaly deleted because of the introduction of the raedAllUsersInWigiiNamespace admin level
//			// if remaining owners = 0 then don't removes user right
//			if($this->isLastOwnershipOn1IfRemovingOwnershipOf2($principal, $userId, $ownerId)){
//				throw new UserAdminServiceException("Could not remove last owner. User should have at least one owner.", UserAdminServiceException::DATA_INTEGRITY_ERROR);
//			}

			$mySqlF = $this->getMySqlFacade();
			$dbCS = $this->getDbAdminService()->getDbConnectionSettings($principal);

			$returnValue = 0;
			$returnValue = $mySqlF->delete($principal,
				$this->getSqlForRemoveUserOwnership($ownerId, $userId),
				$dbCS);

		}
		catch (UserAdminServiceException $uaE){
			$this->executionSink()->publishEndOperationOnError("removeUserOwnership", $uaE, $principal);
			throw $uaE;
		}
		catch (AuthorizationServiceException $asE){
			$this->executionSink()->publishEndOperationOnError("removeUserOwnership", $asE, $principal);
			throw $asE;
		}
		catch(Exception $e)
		{
			$this->executionSink()->publishEndOperationOnError("removeUserOwnership", $e, $principal);
			throw new UserAdminServiceException('',UserAdminServiceException::WRAPPING, $e);
		}
		$this->executionSink()->publishEndOperation("removeUserOwnership", $principal);
		return $returnValue;
	}

	protected function assertPrincipalAuthorizedForRemoveUserOwnership($principal, $ownerId, $userId)
	{
		if(is_null($principal)) throw new UserAdminServiceException('principal can not be null', UserAdminServiceException::INVALID_ARGUMENT);
		if(is_null($ownerId)) throw new UserAdminServiceException("ownerId cannot be null", UserAdminServiceException::INVALID_ARGUMENT);
		if(is_null($userId)) throw new UserAdminServiceException("userId cannot be null", UserAdminServiceException::INVALID_ARGUMENT);

		$autoS = $this->getAuthorizationService();
		// checks general authorization
		$autoS->assertPrincipalAuthorized($principal, "UserAdminService", "removeUserOwnership");
		// checks admin rights
		if(!$principal->isAdminCreator()) $autoS->fail($principal, 'principal '.$principal->getUserId()." is not admin creator");

		//check him self
		if(!($principal->isAdminCreator() && $principal->isReadAllUsersInWigiiNamespace()) && $ownerId == $principal->getUserId()){
			$autoS->fail($principal, "cannot change his user ownership");
		}

		//if owner is principal and userId is from an other wigiiNamespace then check isWigiiNamespaceCreator
		if($principal->getUserId()==$ownerId){
			$userPofUserId = $this->getUserWithoutDetail($principal, $userId);
			if($userPofUserId == null) $autoS->fail($principal, "userId $userId is not a valid user so cannot remove user ownership from userOwner $ownerId");
			$wigiiNamespaceOfUserId = $userPofUserId->getWigiiNamespace()->getWigiiNamespaceName();
			if($wigiiNamespaceOfUserId == $principal->getWigiiNamespace()->getWigiiNamespaceName() && !$principal->isReadAllUsersInWigiiNamespace()){
				$autoS->fail($principal, 'principal '.$principal->getUserId()." is not readAllUsersInWigiiNamespace so cannot remove his own user ownership on user $userId");
			}
			if($wigiiNamespaceOfUserId != $principal->getWigiiNamespace()->getWigiiNamespaceName() && !$principal->isWigiiNamespaceCreator()){
				$autoS->fail($principal, 'principal '.$principal->getUserId()." is not wigiiNamespace creator so cannot remove his own user ownership  on user $userId belonging to wigiiNamespace: $wigiiNamespaceOfUserId");
			}
		}

		if(!$this->doesPrincipalHasOwnerShipOnUsers($principal, array($ownerId, $userId))){
			$autoS->fail($principal, 'principal '.$principal->getUserId()." has no ownership on $ownerId or $userId");
		}
	}
	protected function getSqlForRemoveUserOwnership($ownerId, $userId)
	{
		if(is_null($ownerId)) throw new UserAdminServiceException('ownerId can not be null', UserAdminServiceException::INVALID_ARGUMENT);
		if(is_null($userId)) throw new UserAdminServiceException('userId can not be null', UserAdminServiceException::INVALID_ARGUMENT);

		$sqlB = $this->getMySqlFacade()->getSqlBuilder();
		$sqlB->setTableForDelete('Users_Users');
		$id_user_owner = $sqlB->formatBinExp('id_user_owner', '=', $ownerId, MySqlQueryBuilder::SQLTYPE_INT);
		$id_user = $sqlB->formatBinExp('id_user', '=', $userId, MySqlQueryBuilder::SQLTYPE_INT);
		$isOwner = $sqlB->formatBinExp('isOwner', '=', true, MySqlQueryBuilder::SQLTYPE_BOOLEAN);
		$sqlB->setWhereClause("$id_user_owner and $id_user and $isOwner");
		return $sqlB->getSql();
	}

	public function removeUserRole($principal, $userId, $roleId)
	{
		$this->executionSink()->publishStartOperation("removeUserRole", $principal);
		try
		{
			if(is_null($userId)) throw new UserAdminServiceException("userId cannot be null", UserAdminServiceException::INVALID_ARGUMENT);
			if(is_null($roleId)) throw new UserAdminServiceException("roleId cannot be null", UserAdminServiceException::INVALID_ARGUMENT);

			$this->assertPrincipalAuthorizedForRemoveUserRole($principal, $userId, $roleId);

			$mySqlF = $this->getMySqlFacade();
			$dbCS = $this->getDbAdminService()->getDbConnectionSettings($principal);

			$returnValue = 0;
			$returnValue = $mySqlF->delete($principal,
				$this->getSqlForRemoveUserRole($userId, $roleId),
				$dbCS);

		}
		catch (UserAdminServiceException $uaE){
			$this->executionSink()->publishEndOperationOnError("removeUserRole", $uaE, $principal);
			throw $uaE;
		}
		catch (AuthorizationServiceException $asE){
			$this->executionSink()->publishEndOperationOnError("removeUserRole", $asE, $principal);
			throw $asE;
		}
		catch(Exception $e)
		{
			$this->executionSink()->publishEndOperationOnError("removeUserRole", $e, $principal);
			throw new UserAdminServiceException('',UserAdminServiceException::WRAPPING, $e);
		}
		$this->executionSink()->publishEndOperation("removeUserRole", $principal);
		return $returnValue;
	}
	protected function assertPrincipalAuthorizedForRemoveUserRole($principal, $userId, $roleId)
	{
		if(is_null($principal)) throw new UserAdminServiceException('principal can not be null', UserAdminServiceException::INVALID_ARGUMENT);
		if(is_null($userId)) throw new UserAdminServiceException("userId cannot be null", UserAdminServiceException::INVALID_ARGUMENT);
		if(is_null($roleId)) throw new UserAdminServiceException("roleId cannot be null", UserAdminServiceException::INVALID_ARGUMENT);

		$autoS = $this->getAuthorizationService();
		// checks general authorization
		$autoS->assertPrincipalAuthorized($principal, "UserAdminService", "removeUserRole");
		// checks admin rights
		//if(!$principal->isUserCreator()) $autoS->fail($principal, 'principal '.$principal->getUserId()." is not user creator");
		if(!$principal->getModuleAccess(Module::ADMIN_MODULE)) $autoS->fail($principal, 'principal '.$principal->getUserId()." has no access to Admin module");

		//check that the roleId/userId is not as the principal
		if($principal->getUserId() == $roleId && $principal->getRealUserId() == $userId){
			$autoS->fail($principal, "principal cannot dealocate himself the role he is currently playing");
		}

		//check that the principal is not able to remove a role of himself he could not give back
		if($principal->getUserId() == $userId && !$this->doesPrincipalHasOwnerShipOnUsers($principal, array($roleId))){
			$autoS->fail($principal, "principal cannot dealocate himself a role on which he dosen't have ownership");
		}

		// checks ownership
		//since 27/09/2012 there is no more need to have ownership on the user to change role allocation of a role
		//we can manage. That opens the ability to allocate however to roles that we can manage. This is correct
		//as it gives the ability to an administrator to give access to who he wants only on what he can manage.
//		if(!$this->doesPrincipalHasOwnerShipOnUsers($principal, array($userId))){
//			$autoS->fail($principal, 'principal '.$principal->getUserId()." has no ownership on $userId");
//		}
		if(!$this->canPrincipalGiveThoseRoles($principal, array($roleId))){
			$autoS->fail($principal, 'principal '.$principal->getUserId()." can not give role $roleId");
		}
	}
	protected function getSqlForRemoveUserRole($userId, $roleId)
	{
		if(is_null($userId)) throw new UserAdminServiceException('userId can not be null', UserAdminServiceException::INVALID_ARGUMENT);
		if(is_null($roleId)) throw new UserAdminServiceException('roleId can not be null', UserAdminServiceException::INVALID_ARGUMENT);

		$sqlB = $this->getMySqlFacade()->getSqlBuilder();
		$sqlB->setTableForDelete('Users_Users');
		$id_user_owner = $sqlB->formatBinExp('id_user_owner', '=', $userId, MySqlQueryBuilder::SQLTYPE_INT);
		$id_user = $sqlB->formatBinExp('id_user', '=', $roleId, MySqlQueryBuilder::SQLTYPE_INT);
		$hasRole = $sqlB->formatBinExp('hasRole', '=', true, MySqlQueryBuilder::SQLTYPE_BOOLEAN);
		$sqlB->setWhereClause("$id_user_owner and $id_user and $hasRole");
		return $sqlB->getSql();
	}

	public function getAllRoles($principal, $roleList, $listFilter=null)
	{
		$this->executionSink()->publishStartOperation("getAllRoles", $principal);
		$userListMapper = null;
		try
		{
			// checks authorization
			$this->assertPrincipalAuthorizedForGetAllRoles($principal);

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

			$returnValue = null;
			$userListMapper = $this->getUserListMapper($principal, $roleList);
			if($queryPaged)
			{
				$listFilter->setTotalNumberOfObjects($this->getMySqlFacade()->selectPage($principal,
					$this->getSqlForGetAllRoles($principal, $userLogExp, $userSortingKeyList),
					$this->getDbAdminService()->getDbConnectionSettings($principal),
					$offset, $pageSize,
					$userListMapper));
			}
			else
			{
				$returnValue = $this->getMySqlFacade()->selectAll($principal,
					$this->getSqlForGetAllRoles($principal, $userLogExp, $userSortingKeyList),
					$this->getDbAdminService()->getDbConnectionSettings($principal),
					$userListMapper);
			}
			$userListMapper->freeMemory();
		}
		catch (UserAdminServiceException $uaE){
			if(isset($userListMapper)) $userListMapper->freeMemory();
			$this->executionSink()->publishEndOperationOnError("getAllRoles", $uaE, $principal);
			throw $uaE;
		}
		catch (AuthorizationServiceException $asE){
			if(isset($userListMapper)) $userListMapper->freeMemory();
			$this->executionSink()->publishEndOperationOnError("getAllRoles", $asE, $principal);
			throw $asE;
		}
		catch(Exception $e)
		{
			if(isset($userListMapper)) $userListMapper->freeMemory();
			$this->executionSink()->publishEndOperationOnError("getAllRoles", $e, $principal);
			throw new UserAdminServiceException('',UserAdminServiceException::WRAPPING, $e);
		}
		$this->executionSink()->publishEndOperation("getAllRoles", $principal);
		return $returnValue;
	}
	protected function assertPrincipalAuthorizedForGetAllRoles($principal)
	{
		// checks general authorization
		return $this->getAuthorizationService()->assertPrincipalAuthorized($principal, "UserAdminService", "getAllRoles");
	}
	protected function getSqlForGetAllRoles($principal, $userLogExp=null, $userSortingKeyList=null)
	{
		$principalId = $principal->getUserId();
		if(is_null($principalId)) throw new UserAdminServiceException('principal attached user can not be null', UserAdminServiceException::INVALID_ARGUMENT);
		$sqlB = $this->getSqlWhereClauseBuilderForSelectUsers('U');
		/*
		$id_user_owner = $sqlB->formatBinExp('UU.id_user_owner', '=', $principalId, MySqlQueryBuilder::SQLTYPE_INT);
		$hasRole = $sqlB->formatBinExp('UU.hasRole', '=', true, MySqlQueryBuilder::SQLTYPE_BOOLEAN);
		$isRole = $sqlB->formatBinExp('U.isRole', '=', true, MySqlQueryBuilder::SQLTYPE_BOOLEAN);
		$isInPrincipalWigiiNamespace = $sqlB->formatBinExp('U.wigiiNamespace', '=', $principal->getWigiiNamespace()->getWigiiNamespaceName(), MySqlQueryBuilder::SQLTYPE_VARCHAR); //add by LWR
		$id_user_ownerP = $sqlB->formatBinExp('UUp.id_user_owner', '=', $principalId, MySqlQueryBuilder::SQLTYPE_INT);
		$isOwnerP = $sqlB->formatBinExp('UUp.isOwner', '=', true, MySqlQueryBuilder::SQLTYPE_BOOLEAN);
		// adds where clause
		if(!is_null($userLogExp))
		{
			// reduces logExp and clones it.
			$userLogExp = $userLogExp->reduceNegation(true);
			$userLogExp = $sqlB->buildWhereClause($userLogExp);
			if(!is_null($userLogExp) && $userLogExp != '') $userLogExp = " and ".$userLogExp;
		}
		// order by clause
		if(!is_null($userSortingKeyList))
		{
			$sqlB->convertFieldSortingKeyListToOrderByClause($userSortingKeyList);
			$orderByClause = ' '.$sqlB->getOrderByClause();
		} else $orderByClause='';

		return "SELECT ".$this->getSqlColumnsForUser('U')."
from Users as U
LEFT JOIN Users_Users UU ON U.id_user = UU.id_user
LEFT JOIN Users_Users UUp ON U.id_User = UUp.id_user and $id_user_ownerP
where (
	($id_user_owner and $hasRole) or
	($isRole and (
		".($principal->isReadAllUsersInWigiiNamespace() ? " $isInPrincipalWigiiNamespace OR " : "")."
		".($principal->isWigiiNamespaceCreator() ? " 1 OR " : "")."
		($id_user_ownerP and $isOwnerP)
		)
	)
)".$userLogExp." GROUP BY U.id_user ".$orderByClause;
		*/
						
		// adds where clause
		if(!is_null($userLogExp))
		{
			// reduces logExp and clones it.
			$userLogExp = $userLogExp->reduceNegation(true);
			$userLogExp = $sqlB->buildWhereClause($userLogExp);
			if(!is_null($userLogExp) && $userLogExp != '') $userLogExp = " and ".$userLogExp;
		}
		else $userLogExp = '';
		// order by clause
		if(!is_null($userSortingKeyList))
		{
			$sqlB->convertFieldSortingKeyListToOrderByClause($userSortingKeyList);
			$orderByClause = ' '.$sqlB->getOrderByClause();
		} else $orderByClause='';
		
		$sql = "SELECT ".$this->getSqlColumnsForUser('U')." from Users as U ";
		// if not wigiiNamespaceCreator then sees only users owned by the principal or all all from users from namespace
		if(!$principal->isWigiiNamespaceCreator()) {
			$sql .= " inner join (";
			// users owned by principal
			$id_user_owner = $sqlB->formatBinExp('U1.id_user_owner', '=', $principalId, MySqlQueryBuilder::SQLTYPE_INT);
			$isOwner = $sqlB->formatBinExp('U1.isOwner', '=', true, MySqlQueryBuilder::SQLTYPE_BOOLEAN);			
			$sql .= "select distinct U1.id_user from Users_Users as U1 where $id_user_owner and $isOwner";
			// all users from namespace
			if($principal->isReadAllUsersInWigiiNamespace()) {				
				$sql .= " union distinct ";
				$isInPrincipalWigiiNamespace = $sqlB->formatBinExp('U2.wigiiNamespace', '=', $principal->getWigiiNamespace()->getWigiiNamespaceName(), MySqlQueryBuilder::SQLTYPE_VARCHAR);
				$isRole = $sqlB->formatBinExp('U2.isRole', '=', true, MySqlQueryBuilder::SQLTYPE_BOOLEAN);
				$sql .= "select U2.id_user from Users as U2 where $isRole and $isInPrincipalWigiiNamespace";
			}
			$sql .= ") as Up on Up.id_user = U.id_user ";
		}
		$isRole = $sqlB->formatBinExp('U.isRole', '=', true, MySqlQueryBuilder::SQLTYPE_BOOLEAN);
		$sql .= " where $isRole ".$userLogExp.$orderByClause;
		return $sql;
	}

	public function getAllUserRoles($principal, $userId, $roleList, $listFilter=null)
	{
		$this->executionSink()->publishStartOperation("getAllUserRoles", $principal);
		$userListMapper = null;
		try
		{
			// gets userP
			if(!isset($userId)) throw new UserAdminServiceException("userId cannot be null", UserAdminServiceException::INVALID_ARGUMENT);
			$userP = $this->getUser($principal, $userId, $this->getFieldSelectorListForUserWithoutDetail());
			if(!isset($userP)) throw new UserAdminServiceException("user $userId does not exist in database", UserAdminServiceException::INVALID_ARGUMENT);

			// checks authorization
			$this->assertPrincipalAuthorizedForGetAllUserRoles($principal, $userP);

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

			$returnValue = null;
			$userListMapper = $this->getUserListMapper($principal, $roleList);
			if($queryPaged)
			{
				$listFilter->setTotalNumberOfObjects($this->getMySqlFacade()->selectPage($principal,
						$this->getSqlForGetAllUserRoles($principal, $userP->getUser(), $fieldSelectorList, $userLogExp, $userSortingKeyList),
						$this->getDbAdminService()->getDbConnectionSettings($principal), $offset, $pageSize,
						$userListMapper));
			}
			else
			{
				$returnValue = $this->getMySqlFacade()->selectAll($principal,
						$this->getSqlForGetAllUserRoles($principal, $userP->getUser(), $fieldSelectorList, $userLogExp, $userSortingKeyList),
						$this->getDbAdminService()->getDbConnectionSettings($principal),
						$userListMapper);
			}
			$userListMapper->freeMemory();
		}
		catch (UserAdminServiceException $uaE){
			if(isset($userListMapper)) $userListMapper->freeMemory();
			$this->executionSink()->publishEndOperationOnError("getAllUserRoles", $uaE, $principal);
			throw $uaE;
		}
		catch (AuthorizationServiceException $asE){
			if(isset($userListMapper)) $userListMapper->freeMemory();
			$this->executionSink()->publishEndOperationOnError("getAllUserRoles", $asE, $principal);
			throw $asE;
		}
		catch(Exception $e)
		{
			if(isset($userListMapper)) $userListMapper->freeMemory();
			$this->executionSink()->publishEndOperationOnError("getAllUserRoles", $e, $principal);
			throw new UserAdminServiceException('',UserAdminServiceException::WRAPPING, $e);
		}
		$this->executionSink()->publishEndOperation("getAllUserRoles", $principal);
		return $returnValue;
	}
	protected function assertPrincipalAuthorizedForGetAllUserRoles($principal, $userP)
	{
		// checks general authorization
		$this->getAuthorizationService()->assertPrincipalAuthorized($principal, "UserAdminService", "getAllUserRoles");
	}
	protected function getSqlForGetAllUserRoles($principal, $user, $fieldSelectorList=null, $userLogExp=null, $userSortingKeyList=null)
	{
		if(is_null($user)) throw new UserAdminServiceException("user cannot be null", UserAdminServiceException::INVALID_ARGUMENT);
		$userId = $user->getId();
		if(is_null($userId)) throw new UserAdminServiceException("user->id cannot be null", UserAdminServiceException::INVALID_ARGUMENT);
		$principalId = $principal->getUserId();
		if(is_null($principalId)) throw new UserAdminServiceException('principal attached user can not be null', UserAdminServiceException::INVALID_ARGUMENT);

		$sqlB = $this->getSqlWhereClauseBuilderForSelectUsers('U');
		$id_user_ownerU = $sqlB->formatBinExp('UUu.id_user_owner', '=', $userId, MySqlQueryBuilder::SQLTYPE_INT);
//		$id_user_ownerP = $sqlB->formatBinExp('UUp.id_user_owner', '=', $principalId, MySqlQueryBuilder::SQLTYPE_INT);
		$hasRoleU = $sqlB->formatBinExp('UUu.hasRole', '=', true, MySqlQueryBuilder::SQLTYPE_BOOLEAN);
//		$hasRoleP = $sqlB->formatBinExp('UUp.hasRole', '=', true, MySqlQueryBuilder::SQLTYPE_BOOLEAN);
		$isRole = $sqlB->formatBinExp('U.isRole', '=', true, MySqlQueryBuilder::SQLTYPE_BOOLEAN);
//		$isOwnerP = $sqlB->formatBinExp('UUp.isOwner', '=', true, MySqlQueryBuilder::SQLTYPE_BOOLEAN);
//		$isInPrincipalWigiiNamespace = $sqlB->formatBinExp('U.wigiiNamespace', '=', $principal->getWigiiNamespace()->getWigiiNamespaceName(), MySqlQueryBuilder::SQLTYPE_VARCHAR); //add by LWR

		$uCols = $this->getSqlColumnsForUser('U', $fieldSelectorList);

		// adds where clause
		if(!is_null($userLogExp))
		{
			// reduces logExp and clones it.
			$userLogExp = $userLogExp->reduceNegation(true);
			$userLogExp = $sqlB->buildWhereClause($userLogExp);
			if(!is_null($userLogExp) && $userLogExp != '') $userLogExp = " and ".$userLogExp;
		}
		// order by clause
		if(!is_null($userSortingKeyList))
		{
			$sqlB->convertFieldSortingKeyListToOrderByClause($userSortingKeyList);
			$orderByClause = ' '.$sqlB->getOrderByClause();
		} else $orderByClause='';

//		$returnValue = "SELECT $uCols
//FROM Users U
//LEFT JOIN Users_Users UUu ON UUu.id_user = U.id_user
//LEFT JOIN Users_Users UUp ON UUp.id_user = U.id_user and $id_user_ownerP
//WHERE (
//	$id_user_ownerU and $hasRoleU and
//	(
//		($id_user_ownerP and $hasRoleP) or
//		($isRole and (
//			".($principal->isReadAllUsersInWigiiNamespace() ? " $isInPrincipalWigiiNamespace OR " : "")."
//			".($principal->isWigiiNamespaceCreator() ? " 1 OR " : "")."
//			($id_user_ownerP and $isOwnerP)
//			)
//		)
//	)
//)".$userLogExp." GROUP BY U.id_user ".$orderByClause;
		$returnValue = "SELECT $uCols
FROM Users U
LEFT JOIN Users_Users UUu ON UUu.id_user = U.id_user
WHERE (
	$id_user_ownerU and $hasRoleU
)".$userLogExp." GROUP BY U.id_user ".$orderByClause;
		return $returnValue;
	}

	public function getAllRoleUsers($principal, $roleId, $userList, $listFilter=null)
	{
		$this->executionSink()->publishStartOperation("getAllRoleUsers", $principal);
		$userListMapper = null;
		try
		{
			// gets userP
			if(!isset($roleId)) throw new UserAdminServiceException("roleId cannot be null", UserAdminServiceException::INVALID_ARGUMENT);
			$roleP = $this->getUser($principal, $roleId, $this->getFieldSelectorListForUserWithoutDetail());
			if(!isset($roleP)) throw new UserAdminServiceException("role $roleId does not exist in database", UserAdminServiceException::INVALID_ARGUMENT);

			// checks authorization
			$this->assertPrincipalAuthorizedForGetAllRoleUsers($principal, $roleP);

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

			$returnValue = null;
			$userListMapper = $this->getUserListMapper($principal, $userList);
			if($queryPaged)
			{
				$listFilter->setTotalNumberOfObjects($this->getMySqlFacade()->selectPage($principal,
						$this->getSqlForGetAllRoleUsers($principal, $roleP->getUser(), $fieldSelectorList, $userLogExp, $userSortingKeyList),
						$this->getDbAdminService()->getDbConnectionSettings($principal), $offset, $pageSize,
						$userListMapper));
			}
			else
			{
				$returnValue = $this->getMySqlFacade()->selectAll($principal,
						$this->getSqlForGetAllRoleUsers($principal, $roleP->getUser(), $fieldSelectorList, $userLogExp, $userSortingKeyList),
						$this->getDbAdminService()->getDbConnectionSettings($principal),
						$userListMapper);
			}
			$userListMapper->freeMemory();
		}
		catch (UserAdminServiceException $uaE){
			if(isset($userListMapper)) $userListMapper->freeMemory();
			$this->executionSink()->publishEndOperationOnError("getAllRoleUsers", $uaE, $principal);
			throw $uaE;
		}
		catch (AuthorizationServiceException $asE){
			if(isset($userListMapper)) $userListMapper->freeMemory();
			$this->executionSink()->publishEndOperationOnError("getAllRoleUsers", $asE, $principal);
			throw $asE;
		}
		catch(Exception $e)
		{
			if(isset($userListMapper)) $userListMapper->freeMemory();
			$this->executionSink()->publishEndOperationOnError("getAllRoleUsers", $e, $principal);
			throw new UserAdminServiceException('',UserAdminServiceException::WRAPPING, $e);
		}
		$this->executionSink()->publishEndOperation("getAllRoleUsers", $principal);
		return $returnValue;
	}
	protected function assertPrincipalAuthorizedForGetAllRoleUsers($principal, $roleP)
	{
		if(is_null($roleP)) throw new UserAdminServiceException("roleP cannot be null", UserAdminServiceException::INVALID_ARGUMENT);
		if(is_null($roleP->getUser())) throw new UserAdminServiceException("roleP->getUser cannot be null", UserAdminServiceException::INVALID_ARGUMENT);
		if(!$roleP->getUser()->isRole()) throw new UserAdminServiceException("role cannot be a user", UserAdminServiceException::INVALID_ARGUMENT);

		// checks general authorization
		$this->getAuthorizationService()->assertPrincipalAuthorized($principal, "UserAdminService", "getAllRoleUsers");
	}
	protected function getSqlForGetAllRoleUsers($principal, $role, $fieldSelectorList=null, $userLogExp=null, $userSortingKeyList=null)
	{
		if(is_null($role)) throw new UserAdminServiceException("role cannot be null", UserAdminServiceException::INVALID_ARGUMENT);
		$roleId = $role->getId();
		if(is_null($roleId)) throw new UserAdminServiceException("role->id cannot be null", UserAdminServiceException::INVALID_ARGUMENT);
		$principalId = $principal->getUserId();
		if(is_null($principalId)) throw new UserAdminServiceException('principal attached user can not be null', UserAdminServiceException::INVALID_ARGUMENT);

		$sqlB = $this->getSqlWhereClauseBuilderForSelectUsers('U');
		$id_userU = $sqlB->formatBinExp('UUu.id_user', '=', $roleId, MySqlQueryBuilder::SQLTYPE_INT);
//		$id_user_ownerP = $sqlB->formatBinExp('UUp.id_user_owner', '=', $principalId, MySqlQueryBuilder::SQLTYPE_INT);
		$hasRoleU = $sqlB->formatBinExp('UUu.hasRole', '=', true, MySqlQueryBuilder::SQLTYPE_BOOLEAN);
//		$hasRoleP = $sqlB->formatBinExp('UUp.hasRole', '=', true, MySqlQueryBuilder::SQLTYPE_BOOLEAN);
		$isRole = $sqlB->formatBinExp('U.isRole', '=', false, MySqlQueryBuilder::SQLTYPE_BOOLEAN);
//		$isOwnerP = $sqlB->formatBinExp('UUp.isOwner', '=', true, MySqlQueryBuilder::SQLTYPE_BOOLEAN);
//		$isInPrincipalWigiiNamespace = $sqlB->formatBinExp('U.wigiiNamespace', '=', $principal->getWigiiNamespace()->getWigiiNamespaceName(), MySqlQueryBuilder::SQLTYPE_VARCHAR); //add by LWR

		$uCols = $this->getSqlColumnsForUser('U', $fieldSelectorList);

		// adds where clause
		if(!is_null($userLogExp))
		{
			// reduces logExp and clones it.
			$userLogExp = $userLogExp->reduceNegation(true);
			$userLogExp = $sqlB->buildWhereClause($userLogExp);
			if(!is_null($userLogExp) && $userLogExp != '') $userLogExp = " and ".$userLogExp;
		}
		// order by clause
		if(!is_null($userSortingKeyList))
		{
			$sqlB->convertFieldSortingKeyListToOrderByClause($userSortingKeyList);
			$orderByClause = ' '.$sqlB->getOrderByClause();
		} else $orderByClause='';

//		$returnValue = "SELECT $uCols
//FROM Users U
//LEFT JOIN Users_Users UUu ON UUu.id_user = U.id_user
//LEFT JOIN Users_Users UUp ON UUp.id_user = U.id_user and $id_user_ownerP
//WHERE (
//	$id_user_ownerU and $hasRoleU and
//	(
//		($id_user_ownerP and $hasRoleP) or
//		($isRole and (
//			".($principal->isReadAllUsersInWigiiNamespace() ? " $isInPrincipalWigiiNamespace OR " : "")."
//			".($principal->isWigiiNamespaceCreator() ? " 1 OR " : "")."
//			($id_user_ownerP and $isOwnerP)
//			)
//		)
//	)
//)".$userLogExp." GROUP BY U.id_user ".$orderByClause;
		$returnValue = "SELECT $uCols
FROM Users U
LEFT JOIN Users_Users UUu ON UUu.id_user_owner = U.id_user
WHERE (
	$id_userU and $hasRoleU
)".$userLogExp." GROUP BY U.id_user ".$orderByClause;
		return $returnValue;
	}

	public function getUserRolesForClient($principal, $username, $client, $userList, $listFilter=null)
	{
		throw new UserAdminServiceException("not implemented", UserAdminServiceException::NOT_IMPLEMENTED);
	}

	public function findUserForClient($principal, $userName, $client, $fieldSelectorList=null)
	{
		$this->executionSink()->publishStartOperation("findUserForClient", $principal);
		try
		{
			if(is_null($client)) throw new UserAdminServiceException('client can not be null', UserAdminServiceException::INVALID_ARGUMENT);

			// checks authorization
			$pRights = $this->assertPrincipalAuthorizedForFindUserForClient($principal);

			// fetches in database
			$returnValue = null;
			// if principal has special rights
			if(isset($pRights))
			{
				$dbRow = $this->getMySqlFacade()->selectOne($principal,
						$this->getSqlForFindUserForClient($userName),
						$this->getDbAdminService()->getDbConnectionSettingsForClient($principal, $client));
				if(isset($dbRow))
				{
					// sets client
					$dbRow['client'] = $client;
					// create UserP
					$returnValue = UserP::createInstance($this->createUserInstanceFromRow($principal, $dbRow));
					$returnValue->setRights($pRights);
				}
			}
			// else rights are given by DB
			else
			{
				$dbRow = $this->getMySqlFacade()->selectOne($principal,
						$this->getSqlPForFindUserForClient($userName),
						$this->getDbAdminService()->getDbConnectionSettingsForClient($principal, $client));
				if(isset($dbRow))
				{
					// sets client
					$dbRow['client'] = $client;
					// creates UserP
					$returnValue = UserP::createInstance($this->createUserInstanceFromRow($principal, $dbRow));
					$returnValue->setRights(PrincipalRights::createInstance($dbRow));
				}
			}
		}
		catch (UserAdminServiceException $uaE){
			$this->executionSink()->publishEndOperationOnError("findUserForClient", $uaE, $principal);
			throw $uaE;
		}
		catch (AuthorizationServiceException $asE){
			$this->executionSink()->publishEndOperationOnError("findUserForClient", $asE, $principal);
			throw $asE;
		}
		catch(Exception $e)
		{
			$this->executionSink()->publishEndOperationOnError("findUserForClient", $e, $principal);
			throw new UserAdminServiceException('',UserAdminServiceException::WRAPPING, $e);
		}
		$this->executionSink()->publishEndOperation("findUserForClient", $principal);
		return $returnValue;
	}
	protected function assertPrincipalAuthorizedForFindUserForClient($principal)
	{
		$this->getAuthorizationService()->assertPrincipalIsAuthenticationService($principal);
		return PrincipalRights::createInstance(); /* read only rights */
	}
	protected function getSqlForFindUserForClient($userName)
	{
		return $this->getSqlForFindUserByName($userName);
	}
	protected function getSqlPForFindUserForClient($principal, $userName)
	{
		return $this->getSqlPForFindUserByName($principal, $userName);
	}


	protected function assertPrincipalAuthorizedForGetMyself($principal)
	{
		$pRights = null;
		// principal has read only rights on himself
		$pRights = PrincipalRights::createInstance();
		return $pRights;
	}

	/**
	 * @param dbRow an array straight from the DB
	 * @return User instanciated User object
	 * @precondition: dbRow contains client
	 * This implementation caches only created user without detail
	 * to prevent the user of the API to modify the cached user.
	 */
	public function createUserInstanceFromRow($principal, $dbRow, $withDetail=true)
	{
		if(!$withDetail)
		{
			if(is_null($dbRow)) $user = null;
			else $user = $this->getCachedUser($dbRow["id"], $withDetail);
			if(is_null($user))
			{
				$user = $this->doCreateUserInstanceFromRow($principal, $dbRow, $withDetail);
				$this->cacheUser($user);
			}
			return $user;
		}
		else return $this->doCreateUserInstanceFromRow($principal, $dbRow, $withDetail);
	}

	protected function createUserInstance($dbRow=null, $withDetail=true){
		return User::createInstance($dbRow, $withDetail);
	}
	/**
	 * Extension point : Does really the job of creating a user instance from a db row
	 */
	protected function doCreateUserInstanceFromRow($principal, $dbRow, $withDetail=true)
	{
		if(is_null($dbRow))
		{
			return $this->createUserInstance();
		}
		else
		{
			$mas = $this->getModuleAdminService();
			//instanciates wigiiNamespace objects
			$wigiiNamespace = $this->getWigiiNamespaceAdminService()->getWigiiNamespaceForClient($principal, $dbRow['wigiiNamespace'], $dbRow['client']);
			$dbRow['wigiiNamespace'] = $wigiiNamespace;
			//instanciates module objects
			$dbRow['moduleAccess'] = $mas->formatModuleArray($principal, $dbRow['moduleAccess']);
			$dbRow['groupCreator'] = $mas->formatModuleArray($principal, $dbRow['groupCreator']);
			$dbRow['rootGroupCreator'] = $mas->formatModuleArray($principal, $dbRow['rootGroupCreator']);
			$dbRow['readAllGroupsInWigiiNamespace'] = $mas->formatModuleArray($principal, $dbRow['readAllGroupsInWigiiNamespace']);
			//$dbRow['info_lastModule'] = $mas->getModule($principal, $dbRow['info_lastModule']);
			//sets userlabel
			if($wigiiNamespace !== $principal->getWigiiNamespace())
			{
				$dbRow['userlabel'] = $wigiiNamespace->getWigiiNamespaceName().' : '.$dbRow['username'];
			}
			else
			{
				$dbRow['userlabel'] = $dbRow['username'];
			}
			return $this->createUserInstance($dbRow, $withDetail);
		}
	}

	public function getSqlColumnsForUser($tableAlias, $fieldSelectorList = null)
	{
		if($tableAlias == '') throw new UserAdminServiceException('tableAlias must be filled', UserAdminServiceException::INVALID_ARGUMENT);
		if(is_null($fieldSelectorList))
		{
			$fieldSelectorList = $this->getFieldSelectorListForUser();
		}
		$returnValue = '';
		$sqlB = $this->getSqlWhereClauseBuilderForSelectUsers($tableAlias);
		$idSelected=false; $usernameSelected=false;
		foreach($fieldSelectorList->getListIterator() as $fs)
		{
			if($returnValue != '') $returnValue .= ', ';
			$returnValue .= $tableAlias.".".$sqlB->getSqlColumnNameForFieldSelector($fs);
			if($fs->getFieldName() === "id")
			{
				$returnValue .= " as id ";
				$idSelected = true;
			}
			elseif($fs->getFieldName() === "username") $usernameSelected=true;
		}
		// forces id if not already selected
		if(!$idSelected)
		{
			if($returnValue != '') $returnValue .= ', ';
			$returnValue .= $tableAlias.".".$sqlB->getSqlColumnNameForFieldSelector(FieldSelector::createInstance("id"))." as id ";
		}
		// forces username if not already selected
		if(!$usernameSelected)
		{
			if($returnValue != '') $returnValue .= ', ';
			$returnValue .= $tableAlias.".".$sqlB->getSqlColumnNameForFieldSelector(FieldSelector::createInstance("username"));
		}
		return $returnValue;
	}

	public function changeOwnPassword($principal, $oldPassword, $newPassword)
	{
		$this->executionSink()->publishStartOperation("changeOwnPassword", $principal);
		try
		{
			// checks authorization
			if($principal->isPlayingRole()){
				$realUser = $principal->getRealUser();
			} else {
				$realUser = $principal->getAttachedUser();
			}
			$this->assertPrincipalAuthorizedForChangeOwnPassword($principal, $realUser);

			//cheks data integrity:
			if($oldPassword == $newPassword){
				throw new UserAdminServiceException("new password cannot be the same than old one", UserAdminServiceException::INVALID_ARGUMENT);
			}
			$userD = $realUser->getDetail();

			$mySqlF = $this->getMySqlFacade();
			$dbCS = $this->getDbAdminService()->getDbConnectionSettings($principal);

			//keep old values for rollback
			$origPassword = $userD->getPassword();
			$origPasswordHistory = $userD->getPasswordHistory();
			$origPasswordDate = $userD->getPasswordDate();
			$origPasswordLength = $userD->getPasswordLength();

			//set new values:
			try{
				$userD->setClearPassword($newPassword);
			} catch (UserAdminServiceException $gaE){
				if($gaE->getCode() == UserAdminServiceException::INVALID_PASSWORD){
					throw new UserAdminServiceException($gaE->getMessage(), UserAdminServiceException::INVALID_ARGUMENT);
				}
			}
			if($this->getMySqlFacade()->update($principal,
				$this->getSqlForChangeOwnPassword($realUser, $oldPassword),
				$dbCS) < 1){
				//rollback
				$userD->setPassword($origPassword);
				$userD->getPasswordHistory($origPasswordHistory);
				$userD->getPasswordDate($origPasswordDate);
				$userD->getPasswordLength($origPasswordLength);
				throw new UserAdminServiceException("wrong password for principal ".$realUser->getUsername(), UserAdminServiceException::INVALID_PASSWORD);
			}
		}
		catch (UserAdminServiceException $gaE){
			$this->executionSink()->publishEndOperationOnError("changeOwnPassword", $gaE, $principal);
			throw $gaE;
		}
		catch (AuthorizationServiceException $asE){
			$this->executionSink()->publishEndOperationOnError("changeOwnPassword", $asE, $principal);
			throw $asE;
		}
		catch(Exception $e)
		{
			$this->executionSink()->publishEndOperationOnError("changeOwnPassword", $e, $principal);
			throw new UserAdminServiceException('',UserAdminServiceException::WRAPPING, $e);
		}
		$this->executionSink()->publishEndOperation("changeOwnPassword", $principal);
	}
	protected function assertPrincipalAuthorizedForChangeOwnPassword($principal, $realUser)
	{
		if(is_null($principal)) throw new UserAdminServiceException('principal can not be null', UserAdminServiceException::INVALID_ARGUMENT);
		if(is_null($realUser)) throw new UserAdminServiceException('realUser can not be null', UserAdminServiceException::INVALID_ARGUMENT);
		$autoS = $this->getAuthorizationService();
		// checks general authorization
		$autoS->assertPrincipalAuthorized($principal, "UserAdminService", "changeOwnPassword");
		if(!$principal->canModifyRealUserPassword()) $autoS->fail($principal, "cannot change his own password ".$principal->getRealUserId()." ".$principal->getRealUsername());
		// checks real user integrity
		if($principal->getRealUserId() != $realUser->getId()) $autoS->fail($principal, "principal real user id (".$principal->getRealUserId().") does not match attached real user id (".$realUser->getId().")");
	}
	protected function getSqlForChangeOwnPassword($user, $oldPassword){
		if(is_null($user)) throw new UserAdminServiceException('user can not be null', UserAdminServiceException::INVALID_ARGUMENT);
		if(is_null($oldPassword)) throw new UserAdminServiceException('oldPassword can not be null', UserAdminServiceException::INVALID_ARGUMENT);
		$userD = $user->getDetail();
		if(is_null($userD)) throw new UserAdminServiceException('user detail can not be null', UserAdminServiceException::INVALID_ARGUMENT);

		$sqlB = $this->getMySqlFacade()->getSqlBuilder();
		$sqlB->setTableForUpdate('Users');
		$sqlB->updateValue('password', $userD->getPassword(), MySqlQueryBuilder::SQLTYPE_VARCHAR);
		$sqlB->updateValue('passwordLife', $userD->getPasswordLife(), MySqlQueryBuilder::SQLTYPE_INT);
		$sqlB->updateValue('passwordDate', $userD->getPasswordDate(), MySqlQueryBuilder::SQLTYPE_INT);
		$sqlB->updateValue('passwordHistory', $userD->getPasswordHistory(), MySqlQueryBuilder::SQLTYPE_TEXT);
		$sqlB->updateValue('passwordLength', $userD->getPasswordLength(), MySqlQueryBuilder::SQLTYPE_TEXT);

		//where clause, integrate the oldPassword, to prevent changing without knowing previous password
		$whereIdUser = $sqlB->formatBinExp("id_user", '=', $user->getId(), MySqlQueryBuilder::SQLTYPE_INT);
		$whereOldPassword = $sqlB->formatBinExp("password", '=', md5($oldPassword), MySqlQueryBuilder::SQLTYPE_VARCHAR);

		$sqlB->setWhereClause("$whereIdUser and $whereOldPassword");

		return $sqlB->getSql();
	}

	public function changeOwnEmail($principal, $email)
	{
		$this->executionSink()->publishStartOperation("changeOwnEmail", $principal);
		try
		{
			// checks authorization
			if($principal->isPlayingRole()){
				$realUser = $principal->getRealUser();
			} else {
				$realUser = $principal->getAttachedUser();
			}
			$this->assertPrincipalAuthorizedForchangeOwnEmail($principal, $realUser);
			
			$userD = $realUser->getDetail();
	
			$mySqlF = $this->getMySqlFacade();
			$dbCS = $this->getDbAdminService()->getDbConnectionSettings($principal);
	
			//keep old values for rollback
			$origEmail = $userD->getEmail();
			
			if($origEmail === $email){
				return;
			}
			
			//set new values:
			$userD->setEmail($email);
	
			if($this->getMySqlFacade()->update($principal,
					$this->getSqlForchangeOwnEmail($realUser, $email),
					$dbCS) < 1){
						//rollback
						$userD->setEmail($origEmail);
						throw new UserAdminServiceException("Problem when saving email for ".$realUser->getUsername(), UserAdminServiceException::UNEXPECTED_ERROR);
			}
		}
		catch (UserAdminServiceException $gaE){
			$this->executionSink()->publishEndOperationOnError("changeOwnEmail", $gaE, $principal);
			throw $gaE;
		}
		catch (AuthorizationServiceException $asE){
			$this->executionSink()->publishEndOperationOnError("changeOwnEmail", $asE, $principal);
			throw $asE;
		}
		catch(Exception $e)
		{
			$this->executionSink()->publishEndOperationOnError("changeOwnEmail", $e, $principal);
			throw new UserAdminServiceException('',UserAdminServiceException::WRAPPING, $e);
		}
		$this->executionSink()->publishEndOperation("changeOwnEmail", $principal);
	}
	protected function assertPrincipalAuthorizedForchangeOwnEmail($principal, $realUser)
	{
		if(is_null($principal)) throw new UserAdminServiceException('principal can not be null', UserAdminServiceException::INVALID_ARGUMENT);
		if(is_null($realUser)) throw new UserAdminServiceException('realUser can not be null', UserAdminServiceException::INVALID_ARGUMENT);
		$autoS = $this->getAuthorizationService();
		// checks general authorization
		$autoS->assertPrincipalAuthorized($principal, "UserAdminService", "changeOwnEmail");
		// checks real user integrity
		if($principal->getRealUserId() != $realUser->getId()) $autoS->fail($principal, "principal real user id (".$principal->getRealUserId().") does not match attached real user id (".$realUser->getId().")");
	}
	protected function getSqlForchangeOwnEmail($user, $email){
		if(is_null($user)) throw new UserAdminServiceException('user can not be null', UserAdminServiceException::INVALID_ARGUMENT);
		if(is_null($email)) throw new UserAdminServiceException('email can not be null', UserAdminServiceException::INVALID_ARGUMENT);
		$userD = $user->getDetail();
		if(is_null($userD)) throw new UserAdminServiceException('user detail can not be null', UserAdminServiceException::INVALID_ARGUMENT);
	
		$sqlB = $this->getMySqlFacade()->getSqlBuilder();
		$sqlB->setTableForUpdate('Users');
		$sqlB->updateValue('email', $userD->getEmail(), MySqlQueryBuilder::SQLTYPE_VARCHAR);
		$sqlB->updateValue('emailProofKey', $userD->getEmail('proofKey'), MySqlQueryBuilder::SQLTYPE_VARCHAR);
		$sqlB->updateValue('emailProof', $userD->getEmail('proof'), MySqlQueryBuilder::SQLTYPE_VARCHAR);
		$sqlB->updateValue('emailProofStatus', $userD->getEmail('proofStatus'), MySqlQueryBuilder::SQLTYPE_VARCHAR);
	
		$whereIdUser = $sqlB->formatBinExp("id_user", '=', $user->getId(), MySqlQueryBuilder::SQLTYPE_INT);
	
		$sqlB->setWhereClause("$whereIdUser");
	
		return $sqlB->getSql();
	}
	
	public function persistUserLoginInformation($principal, $user)
	{
		$this->executionSink()->publishStartOperation("persistUserLoginInformation", $principal);
		try
		{
			// checks authorization
			$this->assertPrincipalAuthorizedForPersistUserLoginInformation($principal);

			// ok, go on
			$dbCS = $this->getDbAdminService()->getDbConnectionSettingsForClient($principal, $user->getWigiiNamespace()->getClient());
			$returnValue = $this->getMySqlFacade()->update($principal,
				$this->getSqlForPersistUserLoginInformation($user),
				$dbCS);
		}
		catch (UserAdminServiceException $uaE){
			$this->executionSink()->publishEndOperationOnError("persistUserLoginInformation", $uaE, $principal);
			throw $uaE;
		}
		catch (AuthorizationServiceException $asE){
			$this->executionSink()->publishEndOperationOnError("persistUserLoginInformation", $asE, $principal);
			throw $asE;
		}
		catch(Exception $e)
		{
			$this->executionSink()->publishEndOperationOnError("persistUserLoginInformation", $e, $principal);
			throw new UserAdminServiceException('',UserAdminServiceException::WRAPPING, $e);
		}
		$this->executionSink()->publishEndOperation("persistUserLoginInformation", $principal);
		return $returnValue;
	}
	/**
	 * In this implementation, only the AuthenticationService is authorized to call this method
	 */
	protected function assertPrincipalAuthorizedForPersistUserLoginInformation($principal)
	{
		$this->getAuthorizationService()->assertPrincipalIsAuthenticationService($principal);
	}
	protected function getSqlForPersistUserLoginInformation($user)
	{
		if(is_null($user)) throw new UserAdminServiceException('user can not be null', UserAdminServiceException::INVALID_ARGUMENT);
		$userD = $user->getDetail();
		if(is_null($userD)) throw new UserAdminServiceException('user detail can not be null', UserAdminServiceException::INVALID_ARGUMENT);

		$sqlB = $this->getMySqlFacade()->getSqlBuilder();
		$sqlB->setTableForUpdate('Users');
		$sqlB->updateValue('info_lastLogin', $userD->getInfo_lastLogin(), MySqlQueryBuilder::SQLTYPE_INT);
		$sqlB->updateValue('info_nbLogin', $userD->getInfo_nbLogin(), MySqlQueryBuilder::SQLTYPE_INT);
		$sqlB->updateValue('info_lastFailedLogin', $userD->getInfo_lastFailedLogin(), MySqlQueryBuilder::SQLTYPE_INT);
		$sqlB->updateValue('info_nbFailedLogin', $userD->getInfo_nbFailedLogin(), MySqlQueryBuilder::SQLTYPE_INT);
		$sqlB->updateValue('info_lastLogout', $userD->getInfo_lastLogout(), MySqlQueryBuilder::SQLTYPE_INT);
		$sqlB->updateValue('info_lastSessionContext', $userD->getInfo_lastSessionContext(), MySqlQueryBuilder::SQLTYPE_TEXT);
		$sqlB->updateValue('info_resetSessionContext', $userD->getInfo_resetSessionContext(), MySqlQueryBuilder::SQLTYPE_BOOLEAN);
		$sqlB->updateValue('passwordLife', $userD->getPasswordLife(), MySqlQueryBuilder::SQLTYPE_VARCHAR);
		$sqlB->updateValue('password', $userD->getPassword(), MySqlQueryBuilder::SQLTYPE_VARCHAR);
		$sqlB->updateValue('passwordDate', $userD->getPasswordDate(), MySqlQueryBuilder::SQLTYPE_INT);
		$sqlB->updateValue('passwordHistory', $userD->getPasswordHistory(), MySqlQueryBuilder::SQLTYPE_TEXT);
		$sqlB->updateValue('passwordLength', $userD->getPasswordLength(), MySqlQueryBuilder::SQLTYPE_TEXT);

		$sqlB->setWhereClauseSingleId('id_user', $user->getId());

		return $sqlB->getSql();
	}
	
	public function persistUserLogoutInformation($principal, $user)
	{
		$this->executionSink()->publishStartOperation("persistUserLogoutInformation", $principal);
		try
		{
			// checks authorization
			$this->assertPrincipalAuthorizedForPersistUserLogoutInformation($principal);
			
			// ok, go on
			$dbCS = $this->getDbAdminService()->getDbConnectionSettingsForClient($principal, $user->getWigiiNamespace()->getClient());
			$returnValue = $this->getMySqlFacade()->update($principal,
					$this->getSqlForPersistUserLogoutInformation($user),
					$dbCS);
		}
		catch (UserAdminServiceException $uaE){
			$this->executionSink()->publishEndOperationOnError("persistUserLogoutInformation", $uaE, $principal);
			throw $uaE;
		}
		catch (AuthorizationServiceException $asE){
			$this->executionSink()->publishEndOperationOnError("persistUserLogoutInformation", $asE, $principal);
			throw $asE;
		}
		catch(Exception $e)
		{
			$this->executionSink()->publishEndOperationOnError("persistUserLogoutInformation", $e, $principal);
			throw new UserAdminServiceException('',UserAdminServiceException::WRAPPING, $e);
		}
		$this->executionSink()->publishEndOperation("persistUserLogoutInformation", $principal);
		return $returnValue;
	}
	/**
	 * In this implementation, only the AuthenticationService is authorized to call this method
	 */
	protected function assertPrincipalAuthorizedForPersistUserLogoutInformation($principal)
	{
		$this->getAuthorizationService()->assertPrincipalIsAuthenticationService($principal);
	}
	protected function getSqlForPersistUserLogoutInformation($user)
	{
		if(is_null($user)) throw new UserAdminServiceException('user can not be null', UserAdminServiceException::INVALID_ARGUMENT);
		$userD = $user->getDetail();
		if(is_null($userD)) throw new UserAdminServiceException('user detail can not be null', UserAdminServiceException::INVALID_ARGUMENT);
		
		$sqlB = $this->getMySqlFacade()->getSqlBuilder();
		$sqlB->setTableForUpdate('Users');
		$sqlB->updateValue('info_lastLogout', $userD->getInfo_lastLogout(), MySqlQueryBuilder::SQLTYPE_INT);
		$sqlB->updateValue('info_lastSessionContext', $userD->getInfo_lastSessionContext(), MySqlQueryBuilder::SQLTYPE_TEXT);
		$sqlB->updateValue('info_resetSessionContext', $userD->getInfo_resetSessionContext(), MySqlQueryBuilder::SQLTYPE_BOOLEAN);
		//on logout do not persist password info
		
		$sqlB->setWhereClauseSingleId('id_user', $user->getId());
		
		return $sqlB->getSql();
	}

	public function persistUserSessionContext($principal, $user)
	{
		$this->executionSink()->publishStartOperation("persistUserSessionContext", $principal);
		try
		{
			// checks authorization
			$this->assertPrincipalAuthorizedForPersistUserSessionContext($principal);

			// ok, go on
			$dbCS = $this->getDbAdminService()->getDbConnectionSettingsForClient($principal, $user->getWigiiNamespace()->getClient());
			$returnValue = $this->getMySqlFacade()->update($principal,
				$this->getSqlForPersistUserSessionContext($user),
				$dbCS);
			//fput($this->getSqlForPersistUserSessionContext($user));
		}
		catch (UserAdminServiceException $uaE){
			$this->executionSink()->publishEndOperationOnError("persistUserSessionContext", $uaE, $principal);
			throw $uaE;
		}
		catch (AuthorizationServiceException $asE){
			$this->executionSink()->publishEndOperationOnError("persistUserSessionContext", $asE, $principal);
			throw $asE;
		}
		catch(Exception $e)
		{
			$this->executionSink()->publishEndOperationOnError("persistUserSessionContext", $e, $principal);
			throw new UserAdminServiceException('',UserAdminServiceException::WRAPPING, $e);
		}
		$this->executionSink()->publishEndOperation("persistUserSessionContext", $principal);
		return $returnValue;
	}
	/**
	 * In this implementation, only the AuthenticationService is authorized to call this method
	 */
	protected function assertPrincipalAuthorizedForPersistUserSessionContext($principal)
	{
		$this->getAuthorizationService()->assertPrincipalIsAuthenticationService($principal);
	}
	protected function getSqlForPersistUserSessionContext($user){
		if(is_null($user)) throw new UserAdminServiceException('user can not be null', UserAdminServiceException::INVALID_ARGUMENT);
		$userD = $user->getDetail();
		if(is_null($userD)) throw new UserAdminServiceException('user detail can not be null', UserAdminServiceException::INVALID_ARGUMENT);

		$sqlB = $this->getMySqlFacade()->getSqlBuilder();
		$sqlB->setTableForUpdate('Users');
		$sqlB->updateValue('info_lastSessionContext', $userD->getInfo_lastSessionContext(), MySqlQueryBuilder::SQLTYPE_TEXT);
		$sqlB->updateValue('info_resetSessionContext', $userD->getInfo_resetSessionContext(), MySqlQueryBuilder::SQLTYPE_BOOLEAN);

		$sqlB->setWhereClauseSingleId('id_user', $user->getId());

		return $sqlB->getSql();
	}

	/**
	 * Returns a where clause chunk built from a user selection LogExp
	 */
	public function getSqlWhereClauseForSelectUsers($userSelectionLogExp, $UsersAlias="U")
	{
		return $this->getSqlWhereClauseBuilderForSelectUsers($UsersAlias)->buildWhereClause($userSelectionLogExp);
	}
	public function getSqlWhereClauseBuilderForSelectUsers($UsersAlias="U")
	{
		// autowired
		if(!isset($this->whereClauseBuilderForSelectUsers))
		{
			$this->whereClauseBuilderForSelectUsers = UserWhereClauseBuilderForSelectUsers::createInstance($UsersAlias);
		}
		else
		{
			$this->whereClauseBuilderForSelectUsers->reset($UsersAlias);
		}
		return $this->whereClauseBuilderForSelectUsers;
	}
	public function setSqlWhereClauseBuilderForSelectUsers($whereClauseBuilderForSelectUsers)
	{
		$this->whereClauseBuilderForSelectUsers = $whereClauseBuilderForSelectUsers;
	}

	/**
	 * Get a ModuleAccess array which should be needed to use the admin rights,
	 * or the group rights (UGR) the user has
	 * WARNING, the groupCreator value could be changed in the userP if necessary, but no DB storage is done
	 */
	public function getModuleAccessFromRights($principal, $userP){
		$this->executionSink()->publishStartOperation("getModuleAccessFromRights", $principal);
		try
		{
			if(!isset($userP)) throw new UserAdminServiceException("userP cannot be null", UserAdminServiceException::INVALID_ARGUMENT);

			$user = $userP->getUser();
			$userD = $user->getDetail();
			$userId = $user->getId();

			// checks authorization
			$this->assertPrincipalAuthorizedForGetModuleAccessNeededFromRights($principal, $userP->getRights(), $userId);

			//look in User Details
			$moduleAccess = $userD->getModuleAccessOnGroupAdminLevelRights();
			$needsAdmin = $userD->needsAdminAccess();

			//look in UGR
			$dbAS = $this->getDbAdminService();
			$mySqlF = $this->getMySqlFacade();
			$dbCS = $dbAS->getDbConnectionSettings($principal);
			$rowList = RowListArrayImpl::createInstance("modulename");
			$nbRow = $mySqlF->selectAll($principal,
					$this->getSqlForGetUGRModule($userId),
					$dbCS, $rowList);

			$userHasAdminUGROnGroupsInModule = $userD->getGroupCreator(); //the getSqlForGetUgrModule return only module where there is group. so there where group in groupCreator where there is no more ugr, we need to have them in the loop
			if($userHasAdminUGROnGroupsInModule == null) $userHasAdminUGROnGroupsInModule = array();
			else $userHasAdminUGROnGroupsInModule = array_combine(array_keys($userHasAdminUGROnGroupsInModule), array_fill(0, count($userHasAdminUGROnGroupsInModule), false));
			if($nbRow>0) {
				foreach($rowList->getListIterator() as $modulename=>$row){
					$moduleAccess[$modulename] = $modulename;
					if(!$needsAdmin && $row["needsAdminAccess"] == 1) $needsAdmin = true;
					if($row["needsAdminAccess"] == 1) $userHasAdminUGROnGroupsInModule[$modulename] = true;
					else $userHasAdminUGROnGroupsInModule[$modulename] = false;
				}
			}

			$mas = $this->getModuleAdminService();

			//update the groupCreator as needed: WARNING, we change the userP object, but there is
			//no storage in DB
			foreach($userHasAdminUGROnGroupsInModule as $key=>$val){
				if($val){
					//add group creator for module
					if($userD->getGroupCreator()){
						$userD->setGroupCreator(array_merge($userD->getGroupCreator(), array($key=>$mas->getModule($principal, $key))));
					} else {
						$userD->setGroupCreator(array($key=>$mas->getModule($principal, $key)));
					}
				} else if(!$userD->getRootGroupCreator($key)){
					//delete group creator for module
					$temp = $userD->getGroupCreator();
					if($temp) unset($temp[$key]);
					$userD->setGroupCreator($temp);
				}
			}

			//having ownership on users, but no group with X make no sense, because you can then do nothing
			//with the users except renaming or deleting them. add will make no sense, because you cannot then
			//add them to groups
//			if(!$needsAdmin){
//				//look in UU
//				$result = $mySqlF->selectOne($principal,
//						$this->getSqlForGetUserOwnsUsers($userId),
//						$dbCS);
//				if(isset($result) and (int)$result['result'] > 0){
//					$needsAdmin = true;
//				}
//			}

			//if no module access other than admin, then no moduleaceess at all.
			if($moduleAccess == null) return array();

			if($needsAdmin){
				$moduleAccess[Module::ADMIN_MODULE] = Module::ADMIN_MODULE;
			}


			$returnValue = $mas->formatModuleArray($principal, $moduleAccess);
		}
		catch (UserAdminServiceException $uaE){
			$this->executionSink()->publishEndOperationOnError("getModuleAccessFromRights", $uaE, $principal);
			throw $uaE;
		}
		catch (AuthorizationServiceException $asE){
			$this->executionSink()->publishEndOperationOnError("getModuleAccessFromRights", $asE, $principal);
			throw $asE;
		}
		catch(Exception $e)
		{
			$this->executionSink()->publishEndOperationOnError("getModuleAccessFromRights", $e, $principal);
			throw new UserAdminServiceException('',UserAdminServiceException::WRAPPING, $e);
		}
		$this->executionSink()->publishEndOperation("getModuleAccessFromRights", $principal);
		return $returnValue;

	}
	protected function assertPrincipalAuthorizedForGetModuleAccessNeededFromRights($principal, $origPRights, $userErrorInfo='')
	{
		if(is_null($principal)) throw new UserAdminServiceException('principal can not be null', UserAdminServiceException::INVALID_ARGUMENT);

		$autoS = $this->getAuthorizationService();
		// checks general authorization
		$autoS->assertPrincipalAuthorized($principal, "UserAdminService", "getModuleAccessFromRights");
		// check specific rights
		//no other check is required. Every user can getModuleAccess on Rights of a user
		//like this a user can do this on himself without causing problems.
		//what is important is that the change of moduleAccess is not allowed

//		// existing user/role -> user creator and principal owns user
//		if(is_null($origPRights) ||
//			!$origPRights->isOwner() ||
//			!$principal->isUserCreator()
//		)
//		{
//			$autoS->fail($principal, "has no right to get module access for the user $userErrorInfo");
//		}
	}
	/**
	 * Update the moduleAccess of the user to match the rights he has
	 * this can update the groupCreator as well in a logical way (depending on admin rights and UGR with admin)
	 */
	public function matchModuleAccessOnRights($principal, $userId)
	{
		$this->executionSink()->publishStartOperation("matchModuleAccessOnRights", $principal);
		try
		{
			// reads existing user in database
			$origUserP = $this->getUser($principal, $userId);
			if(is_null($origUserP)) return 0;
			$origPRights = $origUserP->getRights();

			// checks authorization
			$this->assertPrincipalAuthorizedForMatchModuleAccessOnRights($principal, $origPRights, $userId);
			$user = $origUserP->getUser();
			$userD = $user->getDetail();

			//check if change is needed
			$tempGroupCreator = $userD->getGroupCreator();
			$newModuleAccess = $this->getModuleAccessFromRights($principal, $origUserP);
			if($tempGroupCreator == $userD->getGroupCreator() && $newModuleAccess == $userD->getModuleAccess()){
				return 0;
			}

			$user->getDetail()->setModuleAccess($newModuleAccess);
			//since 20/08/2013 context only contains information that should not be reset
//			$user->getDetail()->setInfo_resetSessionContext(true);

			$dbAS = $this->getDbAdminService();
			$mySqlF = $this->getMySqlFacade();
			$dbCS = $dbAS->getDbConnectionSettings($principal);
			$fsl = FieldSelectorListArrayImpl::createInstance();
			if($tempGroupCreator != $userD->getGroupCreator()) $fsl->addFieldSelector("groupCreator");
			if($newModuleAccess == $userD->getModuleAccess()) $fsl->addFieldSelector("moduleAccess");
			$fsl->addFieldSelector("info_resetSessionContext");
			// acquires lock
			$shouldUnlock = $this->lock($principal, $user);
			try
			{
				// performs the update
				$returnValue = $mySqlF->update($principal,
					$this->getSqlForUpdateUser($principal, $user, null, $fsl),
					$dbCS
				);
			}
			// release lock
			catch(Exception $ne) {if($shouldUnlock) $this->unLock($principal, $user); throw $ne;}
			if($shouldUnlock) $this->unLock($principal, $user);
		}
		catch (UserAdminServiceException $uaE){
			$this->executionSink()->publishEndOperationOnError("matchModuleAccessOnRights", $uaE, $principal);
			throw $uaE;
		}
		catch (AuthorizationServiceException $asE){
			$this->executionSink()->publishEndOperationOnError("matchModuleAccessOnRights", $asE, $principal);
			throw $asE;
		}
		catch(Exception $e)
		{
			$this->executionSink()->publishEndOperationOnError("matchModuleAccessOnRights", $e, $principal);
			throw new UserAdminServiceException('',UserAdminServiceException::WRAPPING, $e);
		}
		$this->executionSink()->publishEndOperation("matchModuleAccessOnRights", $principal);
		return $returnValue;
	}
	protected function assertPrincipalAuthorizedForMatchModuleAccessOnRights($principal, $origPRights, $userErrorInfo='')
	{
		if(is_null($principal)) throw new UserAdminServiceException('principal can not be null', UserAdminServiceException::INVALID_ARGUMENT);

		$autoS = $this->getAuthorizationService();
		// checks general authorization
		$autoS->assertPrincipalAuthorized($principal, "UserAdminService", "matchModuleAccessOnRights");
		// check specific rights
		// existing user/role -> user creator and principal owns user
		if(is_null($origPRights) ||
			//!$principal->isUserCreator() || we need to upgrade the moduleAccess from setting ugr and not necessarly having userCreator rights
			!$origPRights->isOwner()
		)
		{
			$autoS->fail($principal, "has no right to update module access for the user $userErrorInfo");
		}
		//??? any body can do that, because this is only keeping user data integrity.
		//??? i.e.: changing a ugr on a X group, will call this method, and p has not necessarly rights on the user.
	}
	protected function getSqlForGetUGRModule($userId)
	{
		if(is_null($userId)) throw new UserAdminServiceException('userId cannot be null', UserAdminServiceException::INVALID_ARGUMENT);
		$sqlB = $this->getMySqlFacade()->getSqlBuilder();
		$withUserId = $sqlB->formatBinExp('UGR.id_user', '=', $userId, MySqlQueryBuilder::SQLTYPE_INT);
		return "select G.modulename, max(UGR.canModify) as needsAdminAccess from Users_Groups_Rights as UGR inner join Groups as G on UGR.id_group = G.id_group where $withUserId group by G.modulename";
	}
	protected function getSqlForGetUserOwnsUsers($userId)
	{
		if(is_null($userId)) throw new UserAdminServiceException('userId cannot be null', UserAdminServiceException::INVALID_ARGUMENT);
		$sqlB = $this->getMySqlFacade()->getSqlBuilder();
		$id_user_owner = $sqlB->formatBinExp('UU.id_user_owner', '=', $userId, MySqlQueryBuilder::SQLTYPE_INT);
		$isOwner = $sqlB->formatBinExp('UU.isOwner', '=', true, MySqlQueryBuilder::SQLTYPE_BOOLEAN);
		return "SELECT count(UU.id_relation_user) as result
FROM Users_Users UU
WHERE $isOwner and $id_user_owner ";
	}


	/********************************************************
	 * ******************************************************
	 * PROTECTED METHOD USED FOR ASSERTION
	 * ******************************************************
	 ********************************************************/
	protected function doesPrincipalHasOwnerShipOnUsers($principal, $userIdArray){
		if(is_null($principal)) throw new UserAdminServiceException('principal can not be null', UserAdminServiceException::INVALID_ARGUMENT);
		if(!is_array($userIdArray) or $userIdArray == null) throw new UserAdminServiceException('userIdArray needs to be a filled array', UserAdminServiceException::INVALID_ARGUMENT);

		$returnValue = false;
		$mySqlF = $this->getMySqlFacade();
		$dbCS = $this->getDbAdminService()->getDbConnectionSettings($principal);

		$result = $mySqlF->selectOne($principal,
			$this->getSqlForDoesPrincipalHasOwnerShipOnUsers($principal, $userIdArray),
			$dbCS);

		//eput($result);
		if(isset($result) && (int)$result['result'] === count($userIdArray)){
			$returnValue = true;
		}

		return $returnValue;
	}
	protected function getSqlForDoesPrincipalHasOwnerShipOnUsers($principal, $userIdArray)
	{
		$sqlB = $this->getMySqlFacade()->getSqlBuilder();
		$userIds = $sqlB->formatBinExp('U.id_user', 'IN', $userIdArray, MySqlQueryBuilder::SQLTYPE_INT);
		$ownerIdUU = $sqlB->formatBinExp('UU.id_user_owner', '=', $principal->getUserId(), MySqlQueryBuilder::SQLTYPE_INT);
		$isOwnerUU = $sqlB->formatBinExp('UU.isOwner', '=', true, MySqlQueryBuilder::SQLTYPE_BOOLEAN);
		$inPrincipalWigiiNamespace = $sqlB->formatBinExp('U.wigiiNamespace', '=', $principal->getWigiiNamespace()->getWigiiNamespaceName(), MySqlQueryBuilder::SQLTYPE_VARCHAR);
		return "SELECT count(DISTINCT U.id_user) as result
FROM Users U
LEFT JOIN Users_Users UU ON UU.id_user = U.id_user
WHERE $userIds and (".
($principal->isReadAllUsersInWigiiNamespace() ? "($inPrincipalWigiiNamespace) OR " : "")."
".($principal->isWigiiNamespaceCreator() ? " 1 OR " : "")."
($ownerIdUU and $isOwnerUU)
) ";

	}

	protected function canPrincipalGiveThoseRoles($principal, $roleIdArray){
		if(is_null($principal)) throw new UserAdminServiceException('principal can not be null', UserAdminServiceException::INVALID_ARGUMENT);
		if(!is_array($roleIdArray) or $roleIdArray == null) throw new UserAdminServiceException('roleIdArray needs to be a filled array', UserAdminServiceException::INVALID_ARGUMENT);

		$returnValue = false;
		$mySqlF = $this->getMySqlFacade();
		$dbCS = $this->getDbAdminService()->getDbConnectionSettings($principal);

		$result = $mySqlF->selectOne($principal,
			$this->getSqlForCanPrincipalGiveThoseRoles($principal, $roleIdArray),
			$dbCS);

		//eput($result);
		if(isset($result) && (int)$result['result'] === count($roleIdArray)){
			$returnValue = true;
		}

		return $returnValue;
	}
	protected function getSqlForCanPrincipalGiveThoseRoles($principal, $roleIdArray)
	{
		$sqlB = $this->getMySqlFacade()->getSqlBuilder();
		$roleIds = $sqlB->formatBinExp('U.id_user', 'IN', $roleIdArray, MySqlQueryBuilder::SQLTYPE_INT);
		$roleIdsUU = $sqlB->formatBinExp('UU.id_user', 'IN', $roleIdArray, MySqlQueryBuilder::SQLTYPE_INT);
		$ownerIdUU = $sqlB->formatBinExp('UU.id_user_owner', '=', $principal->getUserId(), MySqlQueryBuilder::SQLTYPE_INT);
		$isOwnerUU = $sqlB->formatBinExp('UU.isOwner', '=', true, MySqlQueryBuilder::SQLTYPE_BOOLEAN);
		$hasRoleUU = $sqlB->formatBinExp('UU.hasRole', '=', true, MySqlQueryBuilder::SQLTYPE_BOOLEAN);
		$isRole = $sqlB->formatBinExp('U.isRole', '=', true, MySqlQueryBuilder::SQLTYPE_BOOLEAN);
		$isNotCalculatedRole = $sqlB->formatBinExp('U.isCalculatedRole', '!=', true, MySqlQueryBuilder::SQLTYPE_BOOLEAN);
		$inPrincipalWigiiNamespace = $sqlB->formatBinExp('U.wigiiNamespace', '=', $principal->getWigiiNamespace()->getWigiiNamespaceName(), MySqlQueryBuilder::SQLTYPE_VARCHAR);
		return "SELECT count(DISTINCT U.id_user) as result
FROM Users U
LEFT JOIN Users_Users UU ON UU.id_user = U.id_user
WHERE
$roleIds and $isRole and $isNotCalculatedRole and (
".($principal->isReadAllUsersInWigiiNamespace() ? "($inPrincipalWigiiNamespace) OR " : "")."
".($principal->isWigiiNamespaceCreator() ? " 1 OR " : "")."
($ownerIdUU and $hasRoleUU) OR
($ownerIdUU and $isOwnerUU and $isRole and $isNotCalculatedRole)
)
";

	}

	protected function isLastOwnershipOn1IfRemovingOwnershipOf2($principal, $userId1, $userId2){
		if(is_null($userId1)) throw new UserAdminServiceException('userId1 can not be null', UserAdminServiceException::INVALID_ARGUMENT);
		if(is_null($userId2)) throw new UserAdminServiceException('userId2 can not be null', UserAdminServiceException::INVALID_ARGUMENT);

		$returnValue = true;
		$mySqlF = $this->getMySqlFacade();
		$dbCS = $this->getDbAdminService()->getDbConnectionSettings($principal);

		//eput($this->getSqlForDoesPrincipalHasOwnerShipOn1AndCanGiveRoleOn2($principal->getUserId(), $userId1, $userId2, 'result'));
		$result = $mySqlF->selectOne($principal,
			$this->getSqlForIsLastOwnershipOn1IfRemovingOwnershipOf2($principal->getUserId(), $userId1, $userId2),
			$dbCS);
		//eput($result);
		if(isset($result) && (int)$result['result'] > 0){
			$returnValue = false;
		}
		return $returnValue;
	}
	protected function getSqlForIsLastOwnershipOn1IfRemovingOwnershipOf2($principal, $userId1, $userId2)
	{
		$sqlB = $this->getMySqlFacade()->getSqlBuilder();
		$isUserId1 = $sqlB->formatBinExp('UU.id_user', '=', $userId1, MySqlQueryBuilder::SQLTYPE_INT);
		$userId2IsNotOwner = $sqlB->formatBinExp('UU.id_user_owner', '!=', $userId2, MySqlQueryBuilder::SQLTYPE_INT);
		$isOwner = $sqlB->formatBinExp('UU.isOwner', '=', true, MySqlQueryBuilder::SQLTYPE_BOOLEAN);
		return "SELECT count(UU.id_relation_user) as result FROM Users_Users UU
WHERE $isUserId1 and $isOwner and $userId2IsNotOwner
";
	}

	/**
	 * Lock user or list of user
	 * @param object: User or UserPList or UserList
	 */
	public function lock($principal, $object){
		return $this->getDbAdminService()->lock($principal, $this->getSqlTableNameForDeleteUser(), $object);
	}

	/**
	 * UnLock user or list of user
	 * @param object: User or UserPList or UserList
	 */
	public function unLock($principal, $object){
		$this->getDbAdminService()->unLock($principal, $this->getSqlTableNameForDeleteUser(), $object);
	}

	/**
	 * Fills a list with all group in which the user is participan, with his respective rights,
	 * each group is associated with the principal rights
	 * principal: authenticated user performing the operation
	 * userId: the user database id from which want the groups participants
	 * module: module in wich we opperate (for propagation)
	 * groupIds: all the groups that the P can currently reached, no check is done on the validity of those ids
	 * groupRPList: the GroupRP list to be filled
	 * listFilter: filter on the list; filters columns, does pagination and sorting
	 * includePropagation: if true then user rights are dynamically calculated based on propagation,
	 * else user rights are direct UGR only.
	 * returns the number of Group added to the list
	 * throws UserAdminServiceException in case of error
	 */
	public function getAllGroups($principal, $userId, $module, $groupIds, $groupRPList, $listFilter=null, $includePropagation=false)
	{
		$this->executionSink()->publishStartOperation("getAllGroups", $principal);
		$groupRPMapper = null;
		try
		{
			// gets userP
			if(!isset($userId)) throw new UserAdminServiceException("userId cannot be null", UserAdminServiceException::INVALID_ARGUMENT);
			$userP = $this->getUser($principal, $userId, $this->getFieldSelectorListForUserWithoutDetail());
			if(!isset($userP)) throw new UserAdminServiceException("user $userId does not exist in database", UserAdminServiceException::INVALID_ARGUMENT);

			// checks authorization
			$pRights = $this->assertPrincipalAuthorizedForGetAllGroups($principal, $userP);

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

			$groupRPMapper = $this->getGroupRPListMapper($principal, $userId, $groupRPList, 'R', 'P', $pRights);
			if($queryPaged)
			{
				$listFilter->setTotalNumberOfObjects($this->getMySqlFacade()->selectPage($principal,
						$this->getSqlForGetAllGroups($principal, $userP->getUser(), $module, $groupIds, $fieldSelectorList, $groupLogExp, $includePropagation, $groupSortingKeyList),
						$this->getDbAdminService()->getDbConnectionSettings($principal), $offset, $pageSize,
						$groupRPMapper));
			}
			else
			{
				$this->getMySqlFacade()->selectAll($principal,
						$this->getSqlForGetAllGroups($principal, $userP->getUser(), $module, $groupIds, $fieldSelectorList, $groupLogExp, $includePropagation, $groupSortingKeyList),
						$this->getDbAdminService()->getDbConnectionSettings($principal),
						$groupRPMapper);
			}
			$returnValue = $groupRPMapper->count();
			$groupRPMapper->freeMemory();
		}
		catch (UserAdminServiceException $uaE){
			if(isset($groupRPMapper)) $groupRPMapper->freeMemory();
			$this->executionSink()->publishEndOperationOnError("getAllGroups", $uaE, $principal);
			throw $uaE;
		}
		catch (AuthorizationServiceException $asE){
			if(isset($groupRPMapper)) $groupRPMapper->freeMemory();
			$this->executionSink()->publishEndOperationOnError("getAllGroups", $asE, $principal);
			throw $asE;
		}
		catch(Exception $e)
		{
			if(isset($groupRPMapper)) $groupRPMapper->freeMemory();
			$this->executionSink()->publishEndOperationOnError("getAllGroups", $e, $principal);
			throw new GroupAdminServiceException('',GroupAdminServiceException::WRAPPING, $e);
		}
		$this->executionSink()->publishEndOperation("getAllGroups", $principal);
		return $returnValue;
	}
	protected function assertPrincipalAuthorizedForGetAllGroups($principal, $userP)
	{
		$autoS = $this->getAuthorizationService();
		// checks general authorization
		$pRights = $autoS->assertPrincipalAuthorized($principal, "UserAdminService", "getAllGroups");
		// check user rights
		if(is_null($userP->getRights())) {
			$autoS->fail($principal, "has no right on the user ".$userP->getId());
		}
		return $pRights;
	}
	protected function getSqlForGetAllGroups($principal, $user, $module, $groupIds, $fieldSelectorList=null, $groupLogExp=null, $includePropagation=false, $groupSortingKeyList=null)
	{
		if(is_null($user)) throw new UserAdminServiceException("user cannot be null", UserAdminServiceException::INVALID_ARGUMENT);
		$userId = $user->getId();
		if(is_null($userId)) throw new UserAdminServiceException("user->id cannot be null", UserAdminServiceException::INVALID_ARGUMENT);
		$principalId = $principal->getUserId();
		if(is_null($principalId)) throw new UserAdminServiceException('principal attached user can not be null', UserAdminServiceException::INVALID_ARGUMENT);
		if(is_null($module)) throw new UserAdminServiceException("module cannot be null", UserAdminServiceException::INVALID_ARGUMENT);
		if($groupIds==null) throw new UserAdminServiceException("groupIds cannot be null", UserAdminServiceException::INVALID_ARGUMENT);

		$sqlB = $this->getGroupAdminServiceImpl()->getSqlWhereClauseBuilderForSelectGroups('G');
		$userId = $sqlB->formatValue($userId, MySqlQueryBuilder::SQLTYPE_INT);
		if(is_array($groupIds)) $groupIds = implode(",", $groupIds);

		$principalId = $sqlB->formatValue($principalId, MySqlQueryBuilder::SQLTYPE_INT);
		if($includePropagation)
		{
			$propagation = $this->getModuleAdminService()->getDefaultPropagation($module);
		}
		else $propagation = 0;
		// group columns
		$gCols = $this->getGroupAdminServiceImpl()->getSqlColumnsForGroup('G', $fieldSelectorList);
		if($gCols != '') $gCols .= ', ';
		// user where clause
		$whereClause = '';
		if(!is_null($groupLogExp))
		{
			// reduces logExp and clones it.
			$groupLogExp = $groupLogExp->reduceNegation(true);
			$whereClause = $sqlB->buildWhereClause($groupLogExp);
			if(!is_null($whereClause) && $whereClause != '') $whereClause = " WHERE ".$whereClause;
		}
		// order by clause
		if(!is_null($groupSortingKeyList))
		{
			$sqlB->convertFieldSortingKeyListToOrderByClause($groupSortingKeyList);
			$orderByClause = ' '.$sqlB->getOrderByClause();
		} else $orderByClause='';

		// builds sql
		$returnValue = "
SELECT $gCols
	GR.canModify as RcanModify, GR.canWriteElement as RcanWriteElement, GR.canShareElement as RcanShareElement,
	GP.canModify as PcanModify, GP.canWriteElement as PcanWriteElement, GP.canShareElement as PcanShareElement
FROM (".
		$this->getSqlForSelectAllGroupsR($userId, $groupIds, $propagation, 'UGR2', 'GG2', 'GR2')
		.") as GR
INNER JOIN Groups as G on G.id_group = GR.id_group
LEFT JOIN (".
		$this->getGroupAdminServiceImpl()->getSqlForSelectAllGroupsP($principal, $module, $principalId, "G3.id_group IN ($groupIds)", $groupLogExp, $propagation, false, 'UGR3','GG3','GP3','G3')
		.") as GP on G.id_group = GP.id_group "
.$whereClause.$orderByClause;
		//echo alert($returnValue);
		return $returnValue;
	}

	/**
	 * cf. design document "User Admin Service sql queries", query id: 1110061045
	 */
	public function getSqlForSelectAllGroupsR($userId, $groupIds, $propagation,
						$User_Groups_RightsAlias='UGR', $Groups_GroupsAlias='GG', $resultAlias='GR')
	{
		$returnValue = "select
$resultAlias.id_group,
max($resultAlias.canModify) as canModify,
max($resultAlias.canWriteElement) as canWriteElement,
max($resultAlias.canShareElement) as canShareElement
from (
-- direct right with user
select
$User_Groups_RightsAlias.id_group,
$User_Groups_RightsAlias.canModify,
$User_Groups_RightsAlias.canWriteElement,
$User_Groups_RightsAlias.canShareElement
from Users_Groups_Rights as $User_Groups_RightsAlias
where $User_Groups_RightsAlias.id_user = $userId AND $User_Groups_RightsAlias.id_group IN($groupIds) ";

		if($propagation == 1 or $propagation == 2)
		{
			$returnValue .= "
union distinct
-- inherited rights from parents if propagation in (1,2)
select
$User_Groups_RightsAlias.id_group,
$User_Groups_RightsAlias.canModify,
$User_Groups_RightsAlias.canWriteElement,
$User_Groups_RightsAlias.canShareElement
from
Groups_Groups as $Groups_GroupsAlias
inner join Users_Groups_Rights as $User_Groups_RightsAlias on $Groups_GroupsAlias.id_group_owner = $User_Groups_RightsAlias.id_group
where $User_Groups_RightsAlias.id_user = $userId AND $Groups_GroupsAlias.id_group IN($groupIds) ";
		}

		if($propagation == 2)
		{
			$returnValue .= "
union distinct
-- inherited read right from children if propagation 2
select
$User_Groups_RightsAlias.id_group,
0 as canModify,
0 as canWriteElement,
0 as canShareElement
from
Groups_Groups as $Groups_GroupsAlias
inner join Users_Groups_Rights as $User_Groups_RightsAlias on $Groups_GroupsAlias.id_group = $User_Groups_RightsAlias.id_group
where $User_Groups_RightsAlias.id_user = $userId AND $Groups_GroupsAlias.id_group_owner IN($groupIds) ";
		}

		$returnValue .= "
) as $resultAlias
group by $resultAlias.id_group";

		return $returnValue;
	}

	public function doesWigiiNamespaceExist($principal, $wigiiNamespace){
		$this->executionSink()->publishStartOperation("doesWigiiNamespaceExist", $principal);
		try
		{
			if(!isset($wigiiNamespace) || $wigiiNamespace->getWigiiNamespaceName()==WigiiNamespace::EMPTY_NAMESPACE_NAME) throw new UserAdminServiceException("wigiiNamespace cannot be null", UserAdminServiceException::INVALID_ARGUMENT);

			// checks authorization
			$pRights = $this->assertPrincipalAuthorizedForDoesWigiiNamespaceExist($principal);

			$rowList = RowListArrayImpl::createInstance();
			$this->getMySqlFacade()->selectAll($principal,
						$this->getSqlForDoesWigiiNamespaceExist($principal, $wigiiNamespace),
						$this->getDbAdminService()->getDbConnectionSettings($principal),
						$rowList);

			$returnValue = !$rowList->isEmpty();
		}
		catch (UserAdminServiceException $uaE){
			$this->executionSink()->publishEndOperationOnError("doesWigiiNamespaceExist", $uaE, $principal);
			throw $uaE;
		}
		catch (AuthorizationServiceException $asE){
			$this->executionSink()->publishEndOperationOnError("doesWigiiNamespaceExist", $asE, $principal);
			throw $asE;
		}
		catch(Exception $e)
		{
			$this->executionSink()->publishEndOperationOnError("doesWigiiNamespaceExist", $e, $principal);
			throw new GroupAdminServiceException('',GroupAdminServiceException::WRAPPING, $e);
		}
		$this->executionSink()->publishEndOperation("doesWigiiNamespaceExist", $principal);
		return $returnValue;
	}
	protected function assertPrincipalAuthorizedForDoesWigiiNamespaceExist($principal)
	{
		$autoS = $this->getAuthorizationService();
		// checks general authorization
		$pRights = $autoS->assertPrincipalAuthorized($principal, "UserAdminService", "doesWigiiNamespaceExist");
		return $pRights;
	}
	protected function getSqlForDoesWigiiNamespaceExist($principal, $wigiiNamespace)
	{
		return "SELECT id_user FROM Users WHERE wigiiNamespace='".$wigiiNamespace->getWigiiNamespaceName()."';";
	}


	// Cache management

	/**
	 * Reads a user in cache given its db id and specifying if cached user has detail or not
	 * (cache separates user with detail from those without detail)
	 */
	private function getCachedUser($userId, $withDetail)
	{
		if(!isset($this->userCache)) return null;
		$returnValue = $this->userCache[$this->getUserCacheKey($userId, $withDetail)];
		if(!isset($returnValue))
		{
			$this->debugLogger()->write("user $userId not found in cache");
			return null;
		}
		return $returnValue;
	}

	private function cacheUser($user)
	{
		if(is_null($user)) return;
		$this->userCache[$this->getUserCacheKey($user->getId(), !is_null($user->getDetail()))] = $user;
		$this->debugLogger()->write('stores user');
	}

	private function getUserCacheKey($userId, $withDetail)
	{
		return "($userId($withDetail))";
	}

}

class UserWhereClauseBuilderForSelectUsers extends FieldSelectorLogExpSqlBuilder
{
	private $_debugLogger;
	private $tableAlias;

	private function debugLogger()
	{
		if(!isset($this->_debugLogger))
		{
			$this->_debugLogger = DebugLogger::getInstance("UserWhereClauseBuilderForSelectUsers");
		}
		return $this->_debugLogger;
	}

	// Object lifecycle

	public static function createInstance($userTableAlias='U')
	{
		$returnValue = new UserWhereClauseBuilderForSelectUsers();
		$returnValue->reset($userTableAlias);
		return $returnValue;
	}

	public function reset($userTableAlias='U')
	{
		parent::reset();
		$this->debugLogger()->write("reset with table alias ".$userTableAlias);
		$this->tableAlias = $userTableAlias;
	}

	// FieldSelectorLogExpBuilder implementation

	public function getTableAliasForFieldSelector($fieldSelector)
	{
		$this->debugLogger()->write("TABLE ALIAS FOR FIELDSELECTOR ".$fieldSelector->getFieldName()." ".$this->tableAlias);
		return $this->tableAlias;
	}

	public function getSqlColumnNameForFieldSelector($fieldSelector)
	{
		if(is_null($fieldSelector)) throw new UserAdminServiceException("fieldSelector cannot be null", UserAdminServiceException::INVALID_ARGUMENT);
		$fName = $fieldSelector->getFieldName();
		$subFName = $fieldSelector->getSubFieldName();
		switch($fName)
		{
			case "id": 						return "id_user";
			case "username" : 				return "username";
			case "wigiiNamespace" : 		return "wigiiNamespace";
			case "isRole" : 				return "isRole";
			case "isCalculatedRole" : 		return "isCalculatedRole";
			case "password" : 				return "password";
			case "passwordHistory" : 		return "passwordHistory";
			case "passwordLength" : 		return "passwordLength";
			case "passwordLife" : 			return "passwordLife";
			case "passwordDate" : 			return "passwordDate";
			case "email" :
				if($subFName === null){
					return "email";
				}
				switch($subFName)
				{
					case "value" :			return "email";
					case "proofKey" :		return "emailProofKey";
					case "proof" :			return "emailProof";
					case "proofStatus" :	return "emailProofStatus";
				}
			case "description" : 			return "description";
			case "sys_date" : 				return "sys_date";
			case "sys_creationDate" : 		return "sys_creationDate";
			case "sys_user" : 				return "sys_user";
			case "sys_username" : 			return "sys_username";
			case "sys_creationUsername" : 	return "sys_creationUsername";
			case "sys_creationUser" : 		return "sys_creationUser";
			case "canModifyOwnPassword" :	return "canModifyOwnPassword";
			case "moduleAccess" : 			return "moduleAccess";
			case "userCreator" : 			return "userCreator";
			case "adminCreator" : 			return "adminCreator";
			case "readAllUsersInWigiiNamespace" :return "readAllUsersInWigiiNamespace";
			case "groupCreator" : 			return "groupCreator";
			case "rootGroupCreator" :		return "rootGroupCreator";
			case "readAllGroupsInWigiiNamespace":return "readAllGroupsInWigiiNamespace";
			case "wigiiNamespaceCreator" : 		return "wigiiNamespaceCreator";
			case "moduleEditor" : 			return "moduleEditor";
			case "authenticationMethod" : 	return "authenticationMethod";
			case "authenticationServer" : 	return "authenticationServer";
			case "info_lastLogin" : 		return "info_lastLogin";
			case "info_nbLogin" :			return "info_nbLogin";
			case "info_lastFailedLogin" : 	return "info_lastFailedLogin";
			case "info_nbFailedLogin" : 	return "info_nbFailedLogin";
			case "info_lastLogout" : 		return "info_lastLogout";
			case "info_lastSessionContext": return "info_lastSessionContext";
			case "info_resetSessionContext": return "info_resetSessionContext";
			default: throw new UserAdminServiceException("invalid user attribute $fName in field selector LogExp", UserAdminServiceException::INVALID_ARGUMENT);
		}
	}

	/**
	 * Returns MySqlQueryBuilder::SQLTYPE_? for the given fieldSelector
	 * default throws MySqlQueryBuilderException::UNSUPPORTED_OPERATION,
	 * subclass should provide implementation
	 */
	public function getSqlDataTypeForFieldSelector($fieldSelector)
	{
		if(is_null($fieldSelector)) throw new UserAdminServiceException("fieldSelector cannot be null", UserAdminServiceException::INVALID_ARGUMENT);
		$fName = $fieldSelector->getFieldName();
		$subFName = $fieldSelector->getSubFieldName();
		switch($fName)
		{
			case "id": 						return MySqlQueryBuilder::SQLTYPE_INT;
			case "username" : 				return MySqlQueryBuilder::SQLTYPE_VARCHAR;
			case "wigiiNamespace" : 				return MySqlQueryBuilder::SQLTYPE_VARCHAR;
			case "isRole" :		 			return MySqlQueryBuilder::SQLTYPE_BOOLEAN;
			case "isCalculatedRole" :		return MySqlQueryBuilder::SQLTYPE_BOOLEAN;
			case "password" : 				return MySqlQueryBuilder::SQLTYPE_VARCHAR;
			case "passwordHistory" :		return MySqlQueryBuilder::SQLTYPE_TEXT;
			case "passwordLength" : 		return MySqlQueryBuilder::SQLTYPE_INT;
			case "passwordLife" : 			return MySqlQueryBuilder::SQLTYPE_INT;
			case "passwordDate" : 			return MySqlQueryBuilder::SQLTYPE_INT;
			case "email" :
				switch($subFName)
				{
					case "value" :			return MySqlQueryBuilder::SQLTYPE_VARCHAR;
					case "proofKey" :		return MySqlQueryBuilder::SQLTYPE_VARCHAR;
					case "proof" :			return MySqlQueryBuilder::SQLTYPE_VARCHAR;
					case "proofStatus" :	return MySqlQueryBuilder::SQLTYPE_INT;
				}
			case "description" : 			return MySqlQueryBuilder::SQLTYPE_TEXT;
			case "sys_date" : 				return MySqlQueryBuilder::SQLTYPE_INT;
			case "sys_creationDate" : 		return MySqlQueryBuilder::SQLTYPE_INT;
			case "sys_user" : 				return MySqlQueryBuilder::SQLTYPE_INT;
			case "sys_username" : 			return MySqlQueryBuilder::SQLTYPE_VARCHAR;
			case "sys_creationUser" : 		return MySqlQueryBuilder::SQLTYPE_INT;
			case "sys_creationUsername" : 	return MySqlQueryBuilder::SQLTYPE_VARCHAR;
			case "canModifyOwnPassword" :	return MySqlQueryBuilder::SQLTYPE_BOOLEAN;
			case "moduleAccess" : 			return MySqlQueryBuilder::SQLTYPE_TEXT;
			case "userCreator" : 			return MySqlQueryBuilder::SQLTYPE_BOOLEAN;
			case "adminCreator" : 			return MySqlQueryBuilder::SQLTYPE_BOOLEAN;
			case "readAllUsersInWigiiNamespace":	return MySqlQueryBuilder::SQLTYPE_BOOLEAN;
			case "groupCreator" : 			return MySqlQueryBuilder::SQLTYPE_TEXT;
			case "rootGroupCreator" :		return MySqlQueryBuilder::SQLTYPE_TEXT;
			case "readAllGroupsInWigiiNamespace":return MySqlQueryBuilder::SQLTYPE_TEXT;
			case "wigiiNamespaceCreator" : 		return MySqlQueryBuilder::SQLTYPE_BOOLEAN;
			case "moduleEditor" : 			return MySqlQueryBuilder::SQLTYPE_BOOLEAN;
			case "authenticationMethod" : 	return MySqlQueryBuilder::SQLTYPE_VARCHAR;
			case "authenticationServer" : 	return MySqlQueryBuilder::SQLTYPE_VARCHAR;
			case "info_lastLogin" : 		return MySqlQueryBuilder::SQLTYPE_INT;
			case "info_nbLogin" :			return MySqlQueryBuilder::SQLTYPE_INT;
			case "info_lastFailedLogin" : 	return MySqlQueryBuilder::SQLTYPE_INT;
			case "info_nbFailedLogin" : 	return MySqlQueryBuilder::SQLTYPE_INT;
			case "info_lastLogout" : 		return MySqlQueryBuilder::SQLTYPE_INT;
			case "info_lastSessionContext":	return MySqlQueryBuilder::SQLTYPE_TEXT;
			case "info_resetSessionContext":return MySqlQueryBuilder::SQLTYPE_BOOLEAN;
			default: throw new UserAdminServiceException("invalid group attribute $fName in field selector LogExp", UserAdminServiceException::INVALID_ARGUMENT);
		}
	}
	
	public function supportsSysInformation() {
		return true;
	}
}