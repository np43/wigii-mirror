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

/**
 * An authenticated user in wigii system
 * Created by CWE on 31 mai 09
 * Modified by CWE on 5 sept 2013 (added the methods bindToNamespace, bindToRole and bindToRealUser)
 * Modified by CWE on 2 déc 2013 (added the adaptiveWigiiNamespace notion)
 *
 * WIGII RIGHTS POLICY
 *
 * Beyond UU and UGR with have 4 admin levels :
 * 1) User or group creator
 * 	a. isUserCreator
 *		i. can create a new users
 *   	ii. can delete and modify user on which the principal has a UU
 * 	b. isGroupCreator (per module)
 *		i. can create a new group under an existing parents (X rights needed on this parent)
 *		ii. can modify (rename, change parent, etc) groups on which the principal has X rights (including propagation)
 *
 * 2) Admin user or root Group creator
 * 	a. isAdminCreator
 *  	i. will be a userCreator
 * 		ii. can give access to Admin module to user the principal has a UU
 *		iii. can give isAdminCreator to user the principal has a UU
 *		iv. will be readAllUsersInWigiiNamespace
 *	b. isRootGroupCreator (par module)
 *		i. will be a groupCreator
 *		ii. can create a group with no parent, or can modify a group on which the principal has X rights to be set with no parent (in that case, add new UGR because the propagation will change when having no parent).
 *
 * 3) User or group readAll in wigiiNamespace
 * 	a. isReadAllUsersInWigiiNamespace
 *		i. will full rights on each users of the wigiiNamespace of the principal (including himself)
 *		   the write right is determine in adition with the user/admin creator
 *	b. isReadAllGroupsInWigiiNamespace (per module)
 *		i. will have full rights on each groups of the wigiiNamespace of the principal (except all private groups)
 *		   the write rights is determine in adition with the group/rootGroup creator
 *
 * 4) WigiiNamespace creator
 * 		i. will have the input box wigiiNamespace allowing the user to define the wigiiNamespace of each user or groups he can access
 *
 * 5) Module creator
 * 		not implemented yet
 */
class Principal extends Model
{
	//identity
	private $username = null;
	private $userId = null;
	private $wigiiNamespace = null;
	private $realUserId = null;
	private $realUsername = null;
	private $realWigiiNamespace = null;

	// user info
	private $userlabel = null;
	private $realUserIsPublic = null;

	//rights
	private $canModifyOwnPassword = null;
	private $canModifyRealUserPassword = null;
	private $moduleAccess = null;
	private $userCreator = null;
	private $adminCreator = null;
	private $readAllUsersInWigiiNamespace = null;
	private $groupCreator = null;
	private $rootGroupCreator = null;
	private $readAllGroupsInWigiiNamespace = null;
	private $wigiiNamespaceCreator = null;
	private $moduleEditor = null;

	//session context
	private $generalContext = null; //contains general configuration as language, email, etc..
	private $attachedUserContext = null; //contains the last context of the last module for the attachedUser

	//attachedUser
	private $attachedUser = null;
	//real user if principal is playing a role
	private $realUser = null;
	// map of roles per wigiiNamespace
	private $roleNames = null;
	private function getRoleNamesCacheKey($wigiiNamespace) {
		if(is_null($wigiiNamespace) || $wigiiNamespace == '') return WigiiNamespace::EMPTY_NAMESPACE_URL;
		else if(is_object($wigiiNamespace)) return $wigiiNamespace->getWigiiNamespaceUrl();
		else return $wigiiNamespace;
	}
	// role listener
	private $roleListener = null;	
	
	// Object lifecycle

	/**
	 * Creates an empty principal
	 */
	public static function createInstance(){
		// Delegates to the TechnicalServiceProvider the creation of an empty
		// principal instance. That allows to redefine the class used
		// to represent a principal in the system.
		$p = TechnicalServiceProvider::createPrincipalInstance();
		return $p;
	}

	protected function reset($resetSessionContext=false) {
		//identity
		unset($this->username);
		unset($this->userId);
		unset($this->wigiiNamespace);
		unset($this->realUserId);
		unset($this->realUsername);
		unset($this->realWigiiNamespace);

		// user info
		unset($this->userlabel);
		unset($this->userIsPublic);
		unset($this->realUserIsPublic);

		//rights
		unset($this->canModifyOwnPassword);
		unset($this->canModifyRealUserPassword);
		unset($this->moduleAccess);
		unset($this->userCreator);
		unset($this->adminCreator);
		unset($this->readAllUsersInWigiiNamespace);
		unset($this->groupCreator);
		unset($this->rootGroupCreator);
		unset($this->readAllGroupsInWigiiNamespace);
		unset($this->wigiiNamespaceCreator);
		unset($this->moduleEditor);

		//session context
		if($resetSessionContext) {
			unset($this->generalContext);
			unset($this->attachedUserContext);
		}

		//attachedUser
		unset($this->attachedUser);
		//real user if principal is playing a role
		unset($this->realUser);
		// map of roles per wigiiNamespace
		unset($this->roleNames);
	}

	/**
	 * Creates a principal based on an array of attributes
	 * @param $userDatas an array of attributes. Each attribute name should be equivalent to a setter without the prefix 'set'
	 */
	public static function createInstanceFromArray($userDatas){
		$p = self::createInstance();

		if(isset($userDatas["realUser"])) {
			$p->attachUser($userDatas["realUser"], true); // backs up real user to enable switch back to real user from role
			$p->attachRealUser($userDatas["realUser"]);
			$realUserD = $userDatas["realUser"]->getDetail();
			if(isset($realUserD)) {
				$p->setCanModifyRealUserPassword($realUserD->canModifyOwnPassword());
			}
		}
		if(isset($userDatas["attachedUser"])) $p->attachUser($userDatas["attachedUser"]);

		if(isset($userDatas["username"])) $p->setUsername($userDatas["username"]);
		if(isset($userDatas["userlabel"])) $p->setUserlabel($userDatas["userlabel"]);
		if(isset($userDatas["wigiiNamespace"])) $p->setWigiiNamespace($userDatas["wigiiNamespace"]);
		if(isset($userDatas["userIsPublic"])) $p->setUserIsPublic($userDatas["userIsPublic"]);

		if(isset($userDatas["canModifyOwnPassword"])) $p->setCanModifyOwnPassword($userDatas["canModifyOwnPassword"]);
		if(isset($userDatas["canModifyRealUserPassword"])) $p->setCanModifyRealUserPassword($userDatas["canModifyRealUserPassword"]);
		if(isset($userDatas["moduleAccess"])) $p->setModuleAccess($userDatas["moduleAccess"]);
		if(isset($userDatas["userCreator"])) $p->setUserCreator($userDatas["userCreator"]);
		if(isset($userDatas["adminCreator"])) $p->setAdminCreator($userDatas["adminCreator"]);
		if(isset($userDatas["readAllUsersInWigiiNamespace"])) $p->setReadAllUsersInWigiiNamespace($userDatas["readAllUsersInWigiiNamespace"]);
		if(isset($userDatas["groupCreator"])) $p->setGroupCreator($userDatas["groupCreator"]);
		if(isset($userDatas["rootGroupCreator"])) $p->setRootGroupCreator($userDatas["rootGroupCreator"]);
		if(isset($userDatas["readAllGroupsInWigiiNamespace"])) $p->setReadAllGroupsInWigiiNamespace($userDatas["readAllGroupsInWigiiNamespace"]);
		if(isset($userDatas["wigiiNamespaceCreator"])) $p->setWigiiNamespaceCreator($userDatas["wigiiNamespaceCreator"]);
		if(isset($userDatas["moduleEditor"])) $p->setModuleEditor($userDatas["moduleEditor"]);

		if(!$userDatas["info_resetSessionContext"]){
			if(isset($userDatas["info_lastSessionContext"])) $p->importSessionContext($userDatas["info_lastSessionContext"]);
		}
		return $p;
	}

	/**
	 * Creates a principal based on a user
	 * @param User $attachedUser attached user
	 * @param User $realUser if set, then attached user is a role and real user is the physical user
	 */
	public static function createInstanceFromUser($attachedUser, $realUser=null){
		$p = self::createInstance();

		if($realUser != null){
			$p->attachUser($realUser, true); // backs up real user to enable switch back to real user from role
			$p->attachRealUser($realUser);
			$userd = $realUser->getDetail();
			if($userd != null) {
				$p->setCanModifyRealUserPassword($userd->canModifyOwnPassword());
				if(!$userd->getInfo_resetSessionContext()){
					$p->importSessionContext($userd->getInfo_lastSessionContext());
				} else {
					$userd->setInfo_resetSessionContext(false);
					$userd->setInfo_lastSessionContext(null);
				}
			}
		}

		if($attachedUser != null) {
			$p->attachUser($attachedUser, true);
			$userd = $attachedUser->getDetail();
			if($userd != null && $realUser == null) //context is the one of the real user
			{
				if(!$userd->getInfo_resetSessionContext()){
					$p->importSessionContext($userd->getInfo_lastSessionContext());
				} else {
					$userd->setInfo_resetSessionContext(false);
					$userd->setInfo_lastSessionContext(null);
				}
			}
		}
		return $p;
	}

	//dependency injection

	private $_debugLogger;
	private function debugLogger()
	{
		if(!isset($this->_debugLogger))
		{
			$this->_debugLogger = DebugLogger::getInstance("Principal");
		}
		return $this->_debugLogger;
	}

	// Configuration
	
	private $adaptiveWigiiNamespace = false;
	/**
	 * Configures the principal in a way that it adapts its wigii namespace
	 * to best match the current configuration context.
	 * Tries to bind to namespace by using a principal role or in the case
	 * of a system principal, then changes the current namespace.
	 * @param Boolean $bool if true the namespace will adapt itself according to the
	 * current configuration context, else wigii namespace will not change automatically.
	 */
	public function setAdaptiveWigiiNamespace($bool) {
		$this->adaptiveWigiiNamespace = $bool;
	}
	/**
	 * Returns true if the principal is configured for adaptive wigii namespace
	 */
	public function hasAdaptiveWigiiNamespace() {
		return $this->adaptiveWigiiNamespace;
	}
	
	// Getters


	public function getUsername(){
		return $this->username;
	}
	public function getUserId(){
		if(is_null($this->username)) return null;
		else {
			if(!isset($this->userId)) $this->userId = array();
			return $this->userId[$this->username];
		}
	}
	public function getUserlabel(){
		if(is_null($this->username)) return null;
		else {
			if(!isset($this->userlabel)) $this->userlabel = array();
			return $this->userlabel[$this->username];
		}
	}
	public function isUserPublic(){
		if(is_null($this->username)) return null;
		else {
			if(!isset($this->userIsPublic)) $this->userIsPublic = array();
			return $this->userIsPublic[$this->username];
		}
	}
	public function getWigiiNamespace(){
		if(is_null($this->username)) return null;
		else {
			if(!isset($this->wigiiNamespace)) $this->wigiiNamespace = array();
			return $this->wigiiNamespace[$this->username];
		}
	}

	public function getRealUsername(){
		if($this->isPlayingRole()) return $this->realUsername;
		else return $this->getUsername();
	}
	public function isRealUserPublic(){
		if($this->isPlayingRole()) return $this->realUserIsPublic;
		else return $this->isUserPublic();
	}
	public function getRealUserId(){
		if($this->isPlayingRole()) return $this->realUserId;
		else return $this->getUserId();
	}
	public function getRealWigiiNamespace(){
		if($this->isPlayingRole()) return $this->realWigiiNamespace;
		else return $this->getWigiiNamespace();
	}

	public function getAttachedUser(){
		$userId = $this->getUserId();
		if(isset($userId)) {
			if(!isset($this->attachedUser)) $this->attachedUser = array();
			return $this->attachedUser[$userId];
		}
		else return null;
	}
	public function isPlayingRole()
	{
		return isset($this->realUserId);
	}
	public function getRealUser()
	{
		if($this->isPlayingRole()) return $this->realUser;
		else return $this->getAttachedUser();
	}

	public function getMicroTime(){
		list($usec, $sec) = explode(" ", microtime());
//		$microsec1 = (int)$sec *1000000 + (int)((float)$usec*1000000);
//		$temp = ((float)$usec + (float)$sec);
//		$microsec2 = $temp*1000000;
//		$microsec3 = (int)($temp*1000000);
		$microsec4 = $sec.substr($usec, 2, 6);
//		fput($sec." ".$usec." 1:".$microsec1." 2:".$microsec2." 3:".$microsec3." 4:".$microsec4);
		return $microsec4;
	}

	public function getExecutionId(){
		return md5(SESSION_ID.$this->getUserId());
	}

	public function getModuleAccess($moduleName = null)
	{
		if(is_null($this->username)) return null;
		else {
			if(!isset($this->moduleAccess)) $this->moduleAccess = array();
			$moduleAccess = $this->moduleAccess[$this->username];
			if(is_null($moduleAccess)) return null;
			if($moduleName != null){
			if(is_object($moduleName)) $moduleName = $moduleName->getModuleName();
				return $moduleAccess[$moduleName];
			}
			return $moduleAccess;
		}
	}
	public function getFirstNoneAdminAccessibleModule(){
		if(is_null($this->username)) return null;
		else {
			if(!isset($this->moduleAccess)) $this->moduleAccess = array();
			$moduleAccess = $this->moduleAccess[$this->username];
			if($moduleAccess == null) return null;
			foreach($moduleAccess as $module){
				if($module->isAdminModule()) continue;
				return $module;
			}
			return null;
		}
	}
	public function isUserCreator()
	{
		if(is_null($this->username)) return null;
		else {
			if(!isset($this->userCreator)) $this->userCreator = array();
			return $this->userCreator[$this->username];
		}
	}
	public function isAdminCreator()
	{
		if(is_null($this->username)) return null;
		else {
			if(!isset($this->adminCreator)) $this->adminCreator = array();
			return $this->adminCreator[$this->username];
		}
	}
	public function isReadAllUsersInWigiiNamespace()
	{
		if(is_null($this->username)) return null;
		else {
			if(!isset($this->readAllUsersInWigiiNamespace)) $this->readAllUsersInWigiiNamespace = array();
			return $this->readAllUsersInWigiiNamespace[$this->username];
		}
	}
	public function getGroupCreator($moduleName = null)
	{
		if(is_null($this->username)) return null;
		else {
			if(!isset($this->groupCreator)) $this->groupCreator = array();
			$groupCreator = $this->groupCreator[$this->username];
			if($groupCreator == null) return null;
			if($moduleName != null){
				if(is_object($moduleName)) $moduleName = $moduleName->getModuleName();
				return $groupCreator[$moduleName];
			}
			return $groupCreator;
		}
	}
	public function getRootGroupCreator($moduleName = null)
	{
		if(is_null($this->username)) return null;
		else {
			if(!isset($this->rootGroupCreator)) $this->rootGroupCreator = array();
			$rootGroupCreator = $this->rootGroupCreator[$this->username];
			if($rootGroupCreator == null) return null;
			if($moduleName != null){
				if(is_object($moduleName)) $moduleName = $moduleName->getModuleName();
				return $rootGroupCreator[$moduleName];
			}
			return $rootGroupCreator;
		}
	}
	public function getReadAllGroupsInWigiiNamespace($moduleName = null)
	{
		if(is_null($this->username)) return null;
		else {
			if(!isset($this->readAllGroupsInWigiiNamespace)) $this->readAllGroupsInWigiiNamespace = array();
			$readAllGroupsInWigiiNamespace = $this->readAllGroupsInWigiiNamespace[$this->username];
			if($readAllGroupsInWigiiNamespace == null) return null;
			if($moduleName != null){
				if(is_object($moduleName)) $moduleName = $moduleName->getModuleName();
				return $readAllGroupsInWigiiNamespace[$moduleName];
			}
			return $readAllGroupsInWigiiNamespace;
		}
	}
	public function isReadAllGroupsInWigiiNamespace($moduleName){
		if(is_null($this->username)) return null;
		else {
			if(!isset($this->readAllGroupsInWigiiNamespace)) $this->readAllGroupsInWigiiNamespace = array();
			$readAllGroupsInWigiiNamespace = $this->readAllGroupsInWigiiNamespace[$this->username];
			if($readAllGroupsInWigiiNamespace == null) return null;
			if(is_object($moduleName)) $moduleName = $moduleName->getModuleName();
			return $readAllGroupsInWigiiNamespace[$moduleName];
		}
	}
	public function isWigiiNamespaceCreator()
	{
		if(is_null($this->username)) return null;
		else {
			if(!isset($this->wigiiNamespaceCreator)) $this->wigiiNamespaceCreator = array();
			return $this->wigiiNamespaceCreator[$this->username];
		}
	}
	public function isModuleEditor()
	{
		if(is_null($this->username)) return null;
		else {
			if(!isset($this->moduleEditor)) $this->moduleEditor = array();
			return $this->moduleEditor[$this->username];
		}
	}
	public function canModifyOwnPassword()
	{
		if(is_null($this->username)) return null;
		else {
			if(!isset($this->canModifyOwnPassword)) $this->canModifyOwnPassword = array();
			return $this->canModifyOwnPassword[$this->username];
		}
	}
	public function canModifyRealUserPassword(){
		if($this->isPlayingRole()) return $this->canModifyRealUserPassword;
		else return $this->canModifyOwnPassword();
	}

	public function getRealUserAuthenticationMethod(){
		$realUser = $this->getRealUser();
		if(isset($realUser)) return $realUser->getDetail()->getAuthenticationMethod();
		else return null;
	}
	public function getRealUserAuthenticationServer(){
		$realUser = $this->getRealUser();
		if(isset($realUser)) return $realUser->getDetail()->getAuthenticationServer();
		else return null;
	}
	public function passwordExpired(){
		$realUser = $this->getRealUser();
		if(is_null($realUser)) return false;
		else {
			$userD = $realUser->getDetail();
			$passwordLife = $userD->getPasswordLife();
			$passwordDate = $userD->getPasswordDate();
			if($passwordLife != null && $passwordDate != null && (($passwordDate+$passwordLife*3600*24) < time())) return true;
			else return false;
		}
	}
	public function attachedUserPasswordExpired(){
		$attachedUser = $this->getAttachedUser();
		if(is_null($attachedUser)) return false;
		else {
			$userD = $attachedUser->getDetail();
			$passwordLife = $userD->getPasswordLife();
			$passwordDate = $userD->getPasswordDate();
			if($passwordLife != null && $passwordDate != null && (($passwordDate+$passwordLife*3600*24) < time())) return true;
			else return false;
		}
	}



	// setters



	protected function attachUser($user, $copyRights=false){
		if(is_null($user)) {
			if(is_null($this->username)) throw new ServiceException("no username defined, first define one", ServiceException::INVALID_STATE);
			else {
				if(!isset($this->userId)) $this->userId = array();
				$this->userId[$this->username] = null;
			}
		}
		else {
			$username = $user->getUsername();
			$this->setUsername($username);
			$userId = $user->getId();
			if(!isset($this->userId)) $this->userId = array();
			$this->userId[$username] = $userId;
			if(!isset($this->attachedUser)) $this->attachedUser = array();
			$this->attachedUser[$userId] = $user;
			$this->setWigiiNamespace($user->getWigiiNamespace());
			$this->setUserIsPublic($user->isPublic());
			$userD = $user->getDetail();
			if(isset($userD)) {
				$this->setUserlabel($userD->getUserlabel());

				if($copyRights) {
					$this->setModuleAccess($userD->getModuleAccess());
					$this->setUserCreator($userD->isUserCreator());
					$this->setAdminCreator($userD->isAdminCreator());
					$this->setReadAllUsersInWigiiNamespace($userD->isReadAllUsersInWigiiNamespace());
					$this->setGroupCreator($userD->getGroupCreator());
					$this->setRootGroupCreator($userD->getRootGroupCreator());
					$this->setReadAllGroupsInWigiiNamespace($userD->getReadAllGroupsInWigiiNamespace());
					$this->setWigiiNamespaceCreator($userD->isWigiiNamespaceCreator());
					$this->setModuleEditor($userD->isModuleEditor());
					$this->setCanModifyOwnPassword($userD->canModifyOwnPassword());
				}
			}
			if($this->isPlayingRole() && $user->isCalculatedRole()) {
				// caches mapping of user to wigiiNamespace
				if(!isset($this->roleNames)) $this->roleNames = array();
				$this->roleNames[$this->getRoleNamesCacheKey($user->getWigiiNamespace())] = $username;
			}
		}
	}
	protected function attachRealUser($user)
	{
		if(is_null($user)) $this->realUserId = null;
		else {
			$this->realUserId = $user->getId();
			$this->realUser = $user;
			// sets identity
			$this->setRealUsername($user->getUsername());
			$this->setRealWigiiNamespace($user->getWigiiNamespace());
			$this->setRealUserIsPublic($user->isPublic());
		}
	}

	protected function setUsername($var)
	{
		$this->username = $var;
	}
	protected function setRealUsername($var)
	{
		if(is_null($this->realUserId)) throw new ServiceException("no real user attached, first attach one", ServiceException::INVALID_STATE);
		else $this->realUsername = $var;
	}
	protected function setRealUserIsPublic($var)
	{
		if(is_null($this->realUserId)) throw new ServiceException("no real user attached, first attach one", ServiceException::INVALID_STATE);
		else $this->realUserIsPublic = $var;
	}
	protected function setUserIsPublic($var)
	{
		if(is_null($this->username)) throw new ServiceException("no username defined, first define one", ServiceException::INVALID_STATE);
		else {
			if(!isset($this->userIsPublic)) $this->userIsPublic = array();
			$this->userIsPublic[$this->username] = $var;
		}
	}
	protected function setUserlabel($var)
	{
		if(is_null($this->username)) throw new ServiceException("no username defined, first define one", ServiceException::INVALID_STATE);
		else {
			if(!isset($this->userlabel)) $this->userlabel = array();
			$this->userlabel[$this->username] = $var;
		}
	}
	protected function setWigiiNamespace($var){
		if(is_null($this->username)) throw new ServiceException("no username defined, first define one", ServiceException::INVALID_STATE);
		else {
			if(!isset($this->wigiiNamespace)) $this->wigiiNamespace = array();
			$this->wigiiNamespace[$this->username] = $var;
		}
	}
	protected function setRealWigiiNamespace($var){
		if(is_null($this->realUserId)) throw new ServiceException("no real user attached, first attach one", ServiceException::INVALID_STATE);
		else $this->realWigiiNamespace = $var;
	}
	protected function setModuleAccess($var)
	{
		if(is_null($this->username)) throw new ServiceException("no username defined, first define one", ServiceException::INVALID_STATE);
		else {
			if(!isset($this->moduleAccess)) $this->moduleAccess = array();
			$this->moduleAccess[$this->username] = $var;
		}
	}
	protected function setUserCreator($var)
	{
		if(is_null($this->username)) throw new ServiceException("no username defined, first define one", ServiceException::INVALID_STATE);
		else {
			if(!isset($this->userCreator)) $this->userCreator = array();
			$this->userCreator[$this->username] = formatBoolean($var);
		}
	}
	protected function setAdminCreator($var)
	{
		if(is_null($this->username)) throw new ServiceException("no username defined, first define one", ServiceException::INVALID_STATE);
		else {
			if(!isset($this->adminCreator)) $this->adminCreator = array();
			$this->adminCreator[$this->username] = formatBoolean($var);
		}
	}
	protected function setReadAllUsersInWigiiNamespace($var)
	{
		if(is_null($this->username)) throw new ServiceException("no username defined, first define one", ServiceException::INVALID_STATE);
		else {
			if(!isset($this->readAllUsersInWigiiNamespace)) $this->readAllUsersInWigiiNamespace = array();
			$this->readAllUsersInWigiiNamespace[$this->username] = formatBoolean($var);
		}
	}
	protected function setGroupCreator($var)
	{
		if(is_null($this->username)) throw new ServiceException("no username defined, first define one", ServiceException::INVALID_STATE);
		else {
			if(!isset($this->groupCreator)) $this->groupCreator = array();
			$this->groupCreator[$this->username] = $var;
		}
	}
	protected function setRootGroupCreator($var)
	{
		if(is_null($this->username)) throw new ServiceException("no username defined, first define one", ServiceException::INVALID_STATE);
		else {
			if(!isset($this->rootGroupCreator)) $this->rootGroupCreator = array();
			$this->rootGroupCreator[$this->username] = $var;
		}
	}
	protected function setReadAllGroupsInWigiiNamespace($var)
	{
		if(is_null($this->username)) throw new ServiceException("no username defined, first define one", ServiceException::INVALID_STATE);
		else {
			if(!isset($this->readAllGroupsInWigiiNamespace)) $this->readAllGroupsInWigiiNamespace = array();
			$this->readAllGroupsInWigiiNamespace[$this->username] = $var;
		}
	}
	protected function setWigiiNamespaceCreator($var)
	{
		if(is_null($this->username)) throw new ServiceException("no username defined, first define one", ServiceException::INVALID_STATE);
		else {
			if(!isset($this->wigiiNamespaceCreator)) $this->wigiiNamespaceCreator = array();
			$this->wigiiNamespaceCreator[$this->username] = formatBoolean($var);
		}
	}
	protected function setModuleEditor($var)
	{
		if(is_null($this->username)) throw new ServiceException("no username defined, first define one", ServiceException::INVALID_STATE);
		else {
			if(!isset($this->moduleEditor)) $this->moduleEditor = array();
			$this->moduleEditor[$this->username] = formatBoolean($var);
		}
	}
	protected function setCanModifyOwnPassword($var)
	{
		if(is_null($this->username)) throw new ServiceException("no username defined, first define one", ServiceException::INVALID_STATE);
		else {
			if(!isset($this->canModifyOwnPassword)) $this->canModifyOwnPassword = array();
			$this->canModifyOwnPassword[$this->username] = formatBoolean($var);
		}
	}
	protected function setCanModifyRealUserPassword($var)
	{
		if(is_null($this->realUserId)) throw new ServiceException("no real user attached, first attach one", ServiceException::INVALID_STATE);
		else $this->canModifyRealUserPassword = formatBoolean($var);
	}


	// Session management


	/**
	 * return a string containing the sessionContext datas
	 */
	public function serializeSessionContext(){
		return array2str(array("generalContext"=>$this->generalContext, "attachedUserContext"=>$this->attachedUserContext));
	}
	/**
	 * fill the sessionContext object from a serialized string
	 */
	public function importSessionContext($string){
		if($string == null) {
			$this->generalContext = array();
			$this->attachedUserContext = array();
			return;
		}
		$contexts = str2array($string);
		$this->generalContext = $contexts["generalContext"];
		$this->attachedUserContext = $contexts["attachedUserContext"];
	}
	public function setValueInGeneralContext($key, $value){
		if(!isset($this->generalContext)) $this->generalContext = array();
		$this->generalContext[$key] = $value;
	}
	public function getValueInGeneralContext($key){
		if(!isset($this->generalContext)) return null;
		return $this->generalContext[$key];
	}
	public function setValueInRoleContext($key, $value){
		if(!isset($this->attachedUserContext)) $this->attachedUserContext = array();
		if(!isset($this->attachedUserContext[$this->getUserId()])) $this->attachedUserContext[$this->getUserId()] = array();
		$this->attachedUserContext[$this->getUserId()][$key] = $value;
	}
	public function getValueInRoleContext($key){
		if(!isset($this->attachedUserContext)) return null;
		if(!isset($this->attachedUserContext[$this->getUserId()])) return null;
		return $this->attachedUserContext[$this->getUserId()][$key];
	}
	public function cleanKeysInRoleContext($key){
		if(isset($this->attachedUserContext)){
			foreach($this->attachedUserContext as $userId=>$data){
				unset($this->attachedUserContext[$userId][$key]);
			}
		}
	}
	public function cleanKeysInGeneralContext($key){
		if(isset($this->generalContext)){
			unset($this->generalContext[$key]);
		}
	}
	/**
	 * list of role to keep context
	 * roleIds
	 * all other context are destroyed
	 */
	public function cleanRoleContext($roleIds){
		$array = array_combine($roleIds,$roleIds);
		if(isset($this->attachedUserContext)){
			foreach($this->attachedUserContext as $roleId=>$data){
				if(!$array[$roleId]){
					unset($this->attachedUserContext[$roleId]);
				}
			}
		}
	}




	// Utilities


	/**
	 * Changes the actual wigii namespace of this principal instance to a new one
	 * This operation should be authorized by the call to AuthorizationService->assertPrincipalAuthorized(this, "Principal", "changeWigiiNamespace")
	 * If the AuthorizationService accepts then, does the change else throws AuthorizationServiceException with FORBIDDEN code if not.
	 * @param $newWigiiNamespace new Wigii namespace instance
	 */
	public function changeWigiiNamespace($newWigiiNamespace) {
		if($this->getWigiiNamespace() !== $newWigiiNamespace) {
			ServiceProvider::getAuthorizationService()->assertPrincipalAuthorized($this, "Principal", "changeWigiiNamespace");
			$this->setWigiiNamespace($newWigiiNamespace);
		}
	}

	/**
	 * Adds a new module to the actual ModuleAccess list.
	 * This operation should be authorized by the call to AuthorizationService->assertPrincipalAuthorized(this, "Principal", "addAccessToModule")
	 * If the AuthorizationService accepts then, does the change else throws AuthorizationServiceException with FORBIDDEN code if not.
	 * @param $module the module instance for which the principal should have access
	 */
	public function addAccessToModule($module) {
		if(is_null($this->username)) throw new ServiceException("no username defined, first define one", ServiceException::INVALID_STATE);
		else {
			if(!isset($this->moduleAccess)) $this->moduleAccess = array();
			$moduleName = $module->getModuleName();
			if(is_null($this->moduleAccess[$this->username])) {
				ServiceProvider::getAuthorizationService()->assertPrincipalAuthorized($this, "Principal", "addAccessToModule");
				$this->moduleAccess[$this->username] = array($moduleName => $module);
			}
			else if(is_null($this->moduleAccess[$this->username][$moduleName])) {
				ServiceProvider::getAuthorizationService()->assertPrincipalAuthorized($this, "Principal", "addAccessToModule");
				$this->moduleAccess[$this->username][$moduleName] = $module;
			}
		}
	}

	/**
	 * Binds this principal to the given namespace
	 * That means that it switches to the first calculated role of this user
	 * which belongs to the given namespace
	 * If no role is found, then doesn't bind to the namespace
	 * If actual namespace is already equal to given namespace then no swith is done
	 * @param WigiiNamespace $wigiiNamespace the wigii namespace object or URL to which to bind the principal
	 * @return true if bind is successful else false
	 */
	public function bindToWigiiNamespace($wigiiNamespace) {
		// if principal has an attached user, then switches to role
		if($this->getAttachedUser()!=null) return $this->doBindToWigiiNamespace($wigiiNamespace);	
		// else if principal is a system principal, then changes namespace
		else {
			if(!is_object($wigiiNamespace)) {
				$wigiiNamespace = ServiceProvider::getWigiiNamespaceAdminService()->getWigiiNamespace($this, $wigiiNamespace);
			}
			try {$this->changeWigiiNamespace($wigiiNamespace);}
			catch(AuthorizationServiceException $ase) {return false;}
			return true;
		}		
	}
	protected function doBindToWigiiNamespace($wigiiNamespace) {
		// gets role name in cache
		if(!isset($this->roleNames)) $this->roleNames = array();
		$roleNameCacheKey = $this->getRoleNamesCacheKey($wigiiNamespace);
		$roleName = $this->roleNames[$roleNameCacheKey];
		// if role name is not in cache, retrieves role associated to namespace
		if(!isset($roleName)) {
			// fetches calculated role for this namespace in db
			$role = UserListArrayImpl::createInstance();
			$listFilter = ListFilter::createInstance();
			$listFilter->setDesiredPageNumber(1);
			$listFilter->setPageSize(1);
			$listFilter->setFieldSelectorLogExp($this->getLogExpForRoleByWigiiNamespace($wigiiNamespace));
			// if role found, then binds to role			
			ServiceProvider::getUserAdminService()->getMyRoles($this, $role, $listFilter);
			if(!$role->isEmpty()) {
				$role = $role->getFirstUser();
				$this->bindToUser($role->getUsername());
				// puts role in cache
				$this->attachUser($role, true);
				if(isset($this->roleListener)) $this->roleListener->addUser($role);
			}
			// else is real user in required namespace, then binds to real user
			else if($this->getRoleNamesCacheKey($this->getRealWigiiNamespace()) == $roleNameCacheKey) {
				$this->bindToRealUser();
				$this->roleNames[$roleNameCacheKey] = $this->getRealUsername();
			}
			// else returns false
			else {
				//$this->debugLogger()->write("no role matching namespace");
				return false;
			}
		}
		// else binds to role
		else $this->bindToUser($roleName);
		return true;
	}
	protected function getLogExpForRoleByWigiiNamespace($wigiiNamespace) {
		if(is_object($wigiiNamespace)) $wigiiNamespaceName = $wigiiNamespace->getWigiiNamespaceName();
		else if($wigiiNamespace == '' || $wigiiNamespace == WigiiNamespace::EMPTY_NAMESPACE_URL) $wigiiNamespaceName = null;
		else $wigiiNamespaceName = $wigiiNamespace;

		$leAnd = LogExp::createAndExp();
		$le1 = LogExp::createEqualExp(FieldSelector::createInstance("isCalculatedRole"), true);
		$le2 = LogExp::createEqualExp(FieldSelector::createInstance("wigiiNamespace"), $wigiiNamespaceName);
		$leAnd->addOperand($le1);
		$leAnd->addOperand($le2);
		return $leAnd;
	}

	/**
	 * Binds this principal to the given role
	 * The role should by one of the real user roles, else doesn't bind to the role
	 * @param int $roleId the id of the role.
	 * Can also be equal to the real user id, in that case, it is equivalent to call bindToRealUser
	 * @return true if bind is successful else false
	 */
	public function bindToRole($roleId) {
		// gets role in cache
		if(!isset($this->attachedUser)) $this->attachedUser = array();
		$role = $this->attachedUser[$roleId];
		// if role is not in cache then retrieves it
		if(!isset($role)) {
			$role = ServiceProvider::getUserAdminService()->getMyRole($this, $roleId);
			if(is_null($role)) return false;
			// binds to role
			$this->bindToUser($role->getUsername());
			// puts role in cache
			$this->attachUser($role, true);
			if(isset($this->roleListener)) $this->roleListener->addUser($role);
		}
		// else binds to role
		else $this->bindToUser($role->getUsername());
		return true;
	}

	/**
	 * Binds this principal to its real user
	 */
	public function bindToRealUser() {
		$this->bindToUser($this->getRealUsername());
	}

	/**
	 * Binds this principal to an existing user in cache
	 * @param string $username the username
	 */
	protected function bindToUser($username) {
		$currentUsername = $this->getUsername();
		if($username != $currentUsername) {
			// 1. change role
			if($this->isPlayingRole() && $username != $this->getRealUsername()) {
				$this->setUsername($username);
			}
			// 2. real user to role
			else if(!$this->isPlayingRole()) {
				$this->realUserId = $this->getUserId();
				$this->realUser = $this->getAttachedUser();
				$this->setRealUsername($this->getUsername());
				$this->setRealWigiiNamespace($this->getWigiiNamespace());
				$this->setRealUserIsPublic($this->isUserPublic());
				$this->setCanModifyRealUserPassword($this->canModifyOwnPassword());
				$this->setUsername($username);
			}
			// 3. role to real user
			else {
				$this->realUserId = null;
				$this->realUser = null;
				$this->realUsername = null;
				$this->realWigiiNamespace = null;
				$this->realUserIsPublic = null;
				$this->canModifyRealUserPassword = null;
				$this->setUsername($username);
			}
		}
	}

	/**
	 * Binds this principal to a role with the highest rights he has to access this element.
	 * If no matching role is found, then current role is not changed.
	 * @param Int $elementId the ID of the element to bind to
	 * @param Module $module the element module if known
	 * @return true if bind is successful else false
	 */
	public function bindToElement($elementId,$module=null) {
		$roleId = $this->getRoleForElement($elementId,$module);
		if($roleId) return $this->bindToRole($roleId);
	}
	/**
	 * Returns the role the principal should use to access this element or null if no matching role	 
	 * @param Int $elementId the ID of the element access
	 * @param Module $module the element module if known
	 * @return Int role ID or null if no matching
	 */
	public function getRoleForElement($elementId,$module=null) {
		if(!isset($elementId)) throw new ServiceException('elementId cannot be null', ServiceException::INVALID_ARGUMENT);
		return ServiceProvider::getWigiiBPL()->adminGetPrincipalRoleForDirectAccess($this, $this, wigiiBPLParam('directAccessType','element','directAccessId',$elementId,'module',$module));
	}
	/**
	 * Binds this principal to a role with the highest rights he has to access this group.
	 * If no matching role is found, then current role is not changed.
	 * @param Int $groupId the ID of the group to bind to
	 * @param Module $module the group module if known
	 * @return true if bind is successful else false
	 */
	public function bindToGroup($groupId,$module=null) {
		$roleId = $this->getRoleForGroup($groupId,$module);
		if($roleId) return $this->bindToRole($roleId);
	}
	/**
	 * Returns the role the principal should use to access this element or null if no matching role
	 * @param Int $elementId the ID of the element access
	 * @param Module $module the group module if known
	 * @return Int role ID or null if no matching
	 */
	public function getRoleForGroup($groupId,$module=null) {
		if(!isset($groupId)) throw new ServiceException('groupId cannot be null', ServiceException::INVALID_ARGUMENT);
		return ServiceProvider::getWigiiBPL()->adminGetPrincipalRoleForDirectAccess($this, $this, wigiiBPLParam('directAccessType','group','directAccessId',$groupId,'module',$module));
	}
	
	/**
	 * Links a user list to this principal which will receive every loaded role
	 * @param UserList $userList a user list or null to detach any existing listener
	 */
	public function setRoleListener($userList) {
		if(isset($userList)) {
			if(!($userList instanceof UserList)) throw new ServiceException("user list should be an instance of UserList", ServiceException::INVALID_ARGUMENT);
			else $this->roleListener = $userList;
		}
		else $this->roleListener = null;
	}

	/**
	 * Returns the attached user list if any, else null
	 */
	public function getRoleListener() {
		return $this->roleListener;
	}

	/**
	 * Prefetches all real user roles, eventually filtered with the given listFilter
	 * If some fetched roles are already present in principal cache, then they are replaced.
	 * @param ListFilter $listFilter a list filter to filter the roles fetched in db
	 */
	public function prefetchAllRoles($listFilter=null) {
		$roles = UserListArrayImpl::createInstance();
		ServiceProvider::getUserAdminService()->getMyRoles($this, $roles, $listFilter);
		if(!$roles->isEmpty()) {
			$currentUsername = $this->getUsername();
			foreach($roles->getListIterator() as $role) {
				$this->bindToUser($role->getUsername());
				// puts role in cache
				$this->attachUser($role, true);
				if(isset($this->roleListener)) $this->roleListener->addUser($role);
			}
			$this->bindToUser($currentUsername);
		}
	}

	/**
	 * Clears all existing roles and refetches them from the db
	 * Postcondition: the principal is bound back to real user
	 * @param ListFilter $listFilter a list filter instance used to filter the roles fetched in db
	 * @param UserList $roleListener a user list instance that replaces the existing role listener if set.
	 */
	public function refetchAllRoles($listFilter=null, $roleListener=null) {
		// binds to real user
		$this->bindToRealUser();
		// fetches real user back from db
		$me = ServiceProvider::getUserAdminService()->getUser($this, $this->getRealUserId());
		if(is_null($me) || $me->getDbEntity()->getUsername() != $this->getRealUsername()) throw new ServiceException("principal does not exist anymore in db or its username has changed", ServiceException::UNAUTHORIZED);
		// copies isPublic info
		$me->getDbEntity()->setPublic($this->isRealUserPublic());
		// clears all data from principal except session and configuration
		$this->reset();
		// stores back real user
		$this->attachUser($me->getDbEntity(), true);
		// replaces listener
		if(isset($roleListener)) $this->setRoleListener($roleListener);
		// prefetches all roles
		$this->prefetchAllRoles($listFilter);
	}
}


