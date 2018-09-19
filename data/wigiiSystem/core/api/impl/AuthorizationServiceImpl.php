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
 * Wigii AuthorizationService implementation
 * Created by CWE on 9 août 09
 * Modified by CWE on 23.09.2014 to add stamping support.
 * Modified by Medair (CWE) on 28.04.2017 to activate stamping on standard DataFlow sources
 * Modified by Medair (CWE) on 01.09.2017 to enable root principal and public principal to persist sub groups.
 */
class AuthorizationServiceImpl implements AuthorizationService
{
	private $_debugLogger;
	private $_executionSink;
	private $authS;
	private $wigiiNamespaceAS;


	private function debugLogger()
	{
		if(!isset($this->_debugLogger))
		{
			$this->_debugLogger = DebugLogger::getInstance("AuthorizationServiceImpl");
		}
		return $this->_debugLogger;
	}
	private function executionSink()
	{
		if(!isset($this->_executionSink))
		{
			$this->_executionSink = ExecutionSink::getInstance("AuthorizationServiceImpl");
		}
		return $this->_executionSink;
	}
	public function __construct()
	{
		$this->debugLogger()->write("creating instance");
	}

	// dependency injection

	public function setAuthenticationService($authenticationService){
		$this->authS = $authenticationService;
	}
	protected function getAuthenticationService(){
		// autowired
		if(!isset($this->authS)){
			$this->authS = ServiceProvider::getAuthenticationService();
		}
		return $this->authS;
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

	// service implementation

	public function fail($principal, $reason)
	{
		throw new AuthorizationServiceException("principal ".($principal == null ? "NULL" : $principal->getUsername()).' '.$reason, AuthorizationServiceException::FORBIDDEN);
	}
	public function isPrincipalAuthenticationService($principal)
	{
		return $this->getAuthenticationService()->isAuthenticationService($principal);
	}
	public function assertPrincipalIsAuthenticationService($principal)
	{
		if(! $this->isPrincipalAuthenticationService($principal)) $this->fail($principal, 'is not the AuthenticationService');
	}
	public function createAllSystemPrincipals($principalList, $wigiiNamespace=null, $moduleAccess=null)
	{
		if(is_null($principalList)) throw new AuthorizationServiceException("principalList cannot be null", AuthorizationServiceException::INVALID_ARGUMENT);
		if(is_null($wigiiNamespace))
		{
			$wigiiNamespace = $this->getWigiiNamespaceAdminService()->getEmptyWigiiNamespaceForDefaultClient();
		}
		if(is_null($moduleAccess))
		{
			//leave moduleAccess empty
		}

		// creates root principal
		$rootP = $this->createRootPrincipal($wigiiNamespace, $moduleAccess);
		$principalList->addPrincipal($rootP);
		$this->setRootPrincipal($rootP);

		// creates public principal
		$publicP = $this->createPublicPrincipal($wigiiNamespace, $moduleAccess);
		$principalList->addPrincipal($publicP);
		$this->setPublicPrincipal($publicP);


	}
	private $rootPrincipal;
	protected function getRootPrincipal() {return $this->rootPrincipal;}
	protected function setRootPrincipal($rootPrincipal) {$this->rootPrincipal = $rootPrincipal;}
	protected function createRootPrincipal($wigiiNamespace, $moduleAccess)
	{
		if(is_null($wigiiNamespace)) throw new AuthorizationServiceException("wigiiNamespace cannot be null", AuthorizationServiceException::INVALID_ARGUMENT);
		$rootPrincipal = Principal::createInstanceFromArray(array(
			"username"=>'AUTZSIRP',
			"wigiiNamespace"=>$wigiiNamespace,
		    "moduleAccess"=>$moduleAccess,
		    "groupCreator"=>$moduleAccess
		));
		return $rootPrincipal;
	}

	public function isRootPrincipal($principal)
	{
		if(is_null($principal)) return false;
		if($this->getRootPrincipal() === $principal)
		{
			return $this->getAuthenticationService()->isPrincipalValid($principal);
		}
		else return false;
	}
	public function assertPrincipalIsRoot($principal)
	{
		if(! $this->isRootPrincipal($principal)) $this->fail($principal, 'is not root');
	}
	public function findRootPrincipal($principalList)
	{
		if(is_null($principalList)) return null;
		$rootP = $this->getRootPrincipal();
		if(is_null($rootP)) return null;
		return $principalList->getPrincipal($rootP->getUsername(), $rootP->getWigiiNamespace()->getClient());
	}
	private $publicPrincipal;
	protected function getPublicPrincipal() {return $this->publicPrincipal;}
	protected function setPublicPrincipal($publicPrincipal) {$this->publicPrincipal = $publicPrincipal;}
	protected function createPublicPrincipal($wigiiNamespace, $moduleAccess)
	{
		if(is_null($wigiiNamespace)) throw new AuthorizationServiceException("wigiiNamespace cannot be null", AuthorizationServiceException::INVALID_ARGUMENT);
		$publicPrincipal = Principal::createInstanceFromArray(array(
			"username"=>'AUTZSIPP',
			"wigiiNamespace"=>$wigiiNamespace,
		    "moduleAccess"=>$moduleAccess,
		    "groupCreator"=>$moduleAccess
		));
		return $publicPrincipal;
	}

	public function isPublicPrincipal($principal)
	{
		if(is_null($principal)) return false;
		if($this->getPublicPrincipal() === $principal)
		{
			return $this->getAuthenticationService()->isPrincipalValid($principal);
		}
		else return false;
	}
	public function assertPrincipalIsPublic($principal)
	{
		if(! $this->isPublicPrincipal($principal)) $this->fail($principal, 'is not public');
	}
	public function findPublicPrincipal($principalList)
	{
		if(is_null($principalList)) return null;
		$publicP = $this->getPublicPrincipal();
		if(is_null($publicP)) return null;
		return $principalList->getPrincipal($publicP->getUsername(), $publicP->getWigiiNamespace()->getClient());
	}


	public function assertPrincipalAuthorized($principal, $serviceName, $methodName)
	{
	    if(!isset($principal)) throw new AuthorizationServiceException('principal cannot be null', AuthorizationServiceException::INVALID_ARGUMENT);
	    switch($serviceName)
		{
			case "UserAdminService":
				switch($methodName)
				{
					case "getUser":
					case "findUserByName":
						// AuthenticationService gets special read only right
						if($this->isPrincipalAuthenticationService($principal))
						{
							return PrincipalRights::createInstance();
						}
						// else no special rights
						else
						{
							$this->assertPrincipalIsRootOrHasAttachedUser($principal);
							$this->assertPrincipalHasAdminAccess($principal);
							return null; /* principal gets no special rights */
						}
					case "getMyRole":
					case "getMyRoles":
					case "getMyRolesWithoutDetail":
					case "getUserWithoutDetail":
					case "changeOwnPassword":
					case "changeOwnEmail":
					case "calculateAllMergedRoles":
						$this->assertPrincipalHasAttachedUser($principal);
						return null; /* principal gets no special rights */
					case "getAllUsersFromSystem":
						if($this->isRootPrincipal($principal)){
							$this->assertPrincipalIsRootOrHasAttachedUser($principal);
							return PrincipalRights::createInstance(array("isOwner"=>true, "canModify"=>true));
						} else {
							$this->assertPrincipalHasAttachedUser($principal);
							$this->assertPrincipalHasAdminAccess($principal);
						}
						return null;
					case "getAllRoles":
					case "getAllUserRoles":
					case "getAllRoleUsers":
					case "getAllUsers":
					case "getAllUserUsers":
					case "persistUser":
					case "calculateAdminRights":
					case "deleteUser":
					case "setUserOwnership":
					case "removeUserOwnership":
					case "setUserRole":
					case "removeUserRole":
					case "getAllGroups":
//					case "persistUserUserRight":
//					case "removeUserUserRight":
					case "matchModuleAccessOnRights":
					case "getModuleAccessFromRights":
					case "doesWigiiNamespaceExist":
					case "doesUsernameExist":
						$this->assertPrincipalHasAttachedUser($principal);
						$this->assertPrincipalHasAdminAccess($principal);
						return null; /* principal gets no special rights */
					case "deleteAllUserRolesFromWigiiNamespace":
						$this->assertPrincipalHasAttachedUser($principal);
						$this->assertPrincipalHasAdminAccess($principal);
						$this->assertPrincipalIsWigiiNamespaceCreator($principal);
						return null; /* principal gets no special rights */
				}
				break;
			case "GroupAdminService":
				switch($methodName)
				{
					case "getGroup":
					case "getAllGroups":
					case "getSelectedGroups":
						if($this->isPublicPrincipal($principal)){
						    // 01.09.2017 Medair(CWE) : public principal gains admin rights on group
							return PrincipalRights::createInstance(array("canWriteElement"=>true, "canShareElement"=>true, "canModify"=>true));
						} else if($this->isRootPrincipal($principal)){
						    // 01.09.2017 Medair(CWE) : root principal gains admin rights on group
						    return PrincipalRights::createInstance(array("canWriteElement"=>true, "canShareElement"=>true, "canModify"=>true));
						} else {
							$this->assertPrincipalHasAttachedUser($principal);
						}
						return null; /* principal gets no special rights */
					case "updateEmailNotification":
					case "updateEmailNotificationToGroups":
					case "setHtmlContent":
					case "setPortal":
						$this->assertPrincipalHasAttachedUser($principal);
						return null; /* principal gets no special rights */
					case "getGroupWithoutDetail":
					case "getGroupsWithoutDetail":
					case "getSelectedGroupsWithoutDetail":
					case "getSelectedGroupsWithChildrenWithoutDetail":
					case "getGroupsPath":
						$this->assertPrincipalIsRootOrIsPublicOrHasAttachedUser($principal);
						return null; /* principal gets no special rights */
					case "getSelectedGroupsField":
						// rootPrincipal gets special read only right
						if($this->isRootPrincipal($principal))
						{
							return PrincipalRights::createInstance();
						}
						// else no special rights
						else
						{
							$this->assertPrincipalHasAttachedUser($principal);
							return null; /* principal gets no special rights */
						}
					case "persistGroup":
					    // 01.09.2017 Medair(CWE) public principal and root principal are authorized to persist groups
					    if(!$this->isPublicPrincipal($principal) && !$this->isRootPrincipal($principal)) {
					        $this->assertPrincipalHasAttachedUser($principal);
					        $this->assertPrincipalHasAdminAccess($principal);					        
					    }
					    return null; /* principal gets no special rights */
					case "deleteGroup":
					case "setUserRight":
					case "removeUser":
					case "getAllUsers":
					case "setGroupDescription":
					case "setXmlPublish":
					case "setEmailNotification":
						$this->assertPrincipalHasAttachedUser($principal);
						$this->assertPrincipalHasAdminAccess($principal);
						return null; /* principal gets no special rights */
					case "setSubscription":
					    if(!$this->isPublicPrincipal($principal) && !$this->isRootPrincipal($principal)) {
							$this->assertPrincipalHasAttachedUser($principal);
							$this->assertPrincipalHasAdminAccess($principal);
					    }
						return null; /* principal gets no special rights */
					case "deleteGroupContent":
						$this->assertPrincipalHasAttachedUser($principal);
						return null; /* principal gets no special rights, no need to be admin to delete group content (in that case subfolders will no be touched) */
					case "deleteAllGroupsFromWigiiNamespace":
						$this->assertPrincipalHasAttachedUser($principal);
						$this->assertPrincipalHasAdminAccess($principal);
						$this->assertPrincipalIsWigiiNamespaceCreator($principal);
						return null; /* principal gets no special rights */
					case "moveGroup":
						$this->assertPrincipalIsRoot($principal);
						return null; /* principal gets no special rights */
				}
				break;
			case "ElementService":
				switch($methodName)
				{
					case "getAllElementsInGroup":
					case "getAllElementsInGroups":
					case "getSelectedElementsInGroups":
					case "getSelectedElements":
					case "countSelectedElementsDistribution":
					case "fillElement":					
						if($this->isPublicPrincipal($principal) || $this->isRootPrincipal($principal)){
							return PrincipalRights::createInstance(array("canWriteElement"=>true, "canShareElement"=>true));
						} else {
							$this->assertPrincipalHasAttachedUser($principal);
						}
						return null; /* principal gets no special rights */
					case "updateElement":
					case "deleteElement":
					case "evaluateIndicatorOnElementsInGroups":
					case "setElementState":
					case "updateSys_dateToNow":
					case "insertElement":
					case "shareElement":
					case "unshareElement":
					case "moveElement":
					case "doElementsExist":
					case "evaluateElementList":
					case "evaluateSelectedElementsInGroups":
					case "getSubElementPathFromRoot":
					case "renameSubElementsLink":					
						$this->assertPrincipalIsRootOrIsPublicOrHasAttachedUser($principal);
						return null; /* principal gets no special rights */
					case "getAllGroupsWithoutDetailContainingElements":
					case "getGroupsWithoutDetailContainingAllElements":
					case "getAllGroupsContainingElement":
					case "getGroupsPathContainingElement":
						// rootPrincipal gets special read only right
						if($this->isRootPrincipal($principal) || $this->isPublicPrincipal($principal))
						{
							return PrincipalRights::createInstance();
						}
						// else no special rights
						else
						{
							$this->assertPrincipalHasAttachedUser($principal);
							return null; /* principal gets no special rights */
						}
					case "getFieldFromGroupsContainingElement":
					case "getFieldFromGroupsContainingElements":
					case "deleteMultipleElements":
					case "shareMultipleElement":
					case "updateMultipleElement":
					case "insertMultipleElement":
					case "moveMultipleElementToModule":
						$this->assertPrincipalIsRoot($principal);
						return null; /* principal gets no special rights */
				}
				break;
			case "ModuleAdminService":
				switch($methodName)
				{
					case "getModuleFromSelectedElements":
					case "getModuleFromSelectedGroups":
					case "getModulesFromSelectedGroups":
					    $this->assertPrincipalIsRootOrIsPublicOrHasAttachedUser($principal);
						return null; /* principal gets no special rights */
				}
				break;
			case "ClientAdminService":
					switch($methodName)
					{
						case "syncDimension":
							$this->assertPrincipalIsRootOrHasAttachedUser($principal);
							return null; /* principal gets no special rights */
						case "syncCfgFields":
							if(!$this->isRootPrincipal($principal)) {
								$this->assertPrincipalHasAttachedUser($principal);
								$this->assertPrincipalHasAdminAccess($principal);
							}
							return null; /* principal gets no special rights */
					}
					break;
			case "ConfigService":
				switch($methodName)
				{
					case "listModuleConfigFilesForWigiiNamespace":
						$this->assertPrincipalHasAttachedUser($principal);
						$this->assertPrincipalHasAdminAccess($principal);
						return null; /* principal gets no special rights */
				}
				break;
			case "ServiceProvider":
				switch($methodName)
				{
					case "createClientClassInstance":
						$this->assertPrincipalIsRootOrIsPublicOrHasAttachedUser($principal);
						return null; /* principal gets no special rights */
				}
				break;
			case "Principal":
				switch($methodName)
				{
					case "changeWigiiNamespace":
					case "addAccessToModule":
						$this->assertPrincipalIsRootOrIsPublic($principal);
						return null; /* principal gets no special rights */
				}
				break;
			case "RecordEvaluator":
			    switch($methodName)
			    {
			        case "changePrincipal":	
			            $this->assertPrincipalIsRoot($principal);
			            return null; /* principal gets no special rights */
			    }
			    break;
		}
		// by default authorization service refuses grant
		throw new AuthorizationServiceException('unknown service and method, can not grant authorization', AuthorizationServiceException::FORBIDDEN);
	}

	protected function assertPrincipalHasAttachedUser($principal){
		if($principal->getAttachedUser()==null){
			throw new AuthorizationServiceException("Principal has not an attached user", AuthorizationServiceException::FORBIDDEN);
		}
		// checks principal's validity
		$this->getAuthenticationService()->assertPrincipalValid($principal);
	}
	protected function assertPrincipalHasAdminAccess($principal){
		if($principal->getModuleAccess(Module::ADMIN_MODULE)==null){
			throw new AuthorizationServiceException("Principal has not Admin access", AuthorizationServiceException::FORBIDDEN);
		}
	}
	protected function assertPrincipalIsWigiiNamespaceCreator($principal){
		if(!$principal->isWigiiNamespaceCreator()){
			throw new AuthorizationServiceException("Principal is not WigiiNamespaceCreator", AuthorizationServiceException::FORBIDDEN);
		}
	}
	protected function assertPrincipalIsRootOrHasAttachedUser($principal)
	{
		if(!$this->isRootPrincipal($principal)) $this->assertPrincipalHasAttachedUser($principal);
	}
	protected function assertPrincipalIsPublicOrHasAttachedUser($principal)
	{
		if(!$this->isPublicPrincipal($principal)) $this->assertPrincipalHasAttachedUser($principal);
	}
	protected function assertPrincipalIsRootOrIsPublic($principal)
	{
		if(!$this->isRootPrincipal($principal) && !$this->isPublicPrincipal($principal)) $this->fail($principal, 'is not root or not public');
	}
	protected function assertPrincipalIsRootOrIsPublicOrHasAttachedUser($principal)
	{
		if(!$this->isRootPrincipal($principal) && !$this->isPublicPrincipal($principal)) $this->assertPrincipalHasAttachedUser($principal);
	}
	
	/**
	 * By default, ElementPolicyEvaluator is always enabled.
	 */
	public function isElementPolicyEvaluatorEnabled($principal, $module) {
		return true;
	}
	
	// STAMPS
	
	private $stampsKeyGen = 0;
	private $stamps;
	
	public function getStamp($caller, $callbackMethod) {
		// asserts caller is authorized to get stamps
		$this->assertObjectAuthorizedToGetStamp($caller);
		if(!method_exists($caller, $callbackMethod)) throw new AuthorizationServiceException("method '$callbackMethod' does not exist on the caller and cannot be used to deliver the stamp.", AuthorizationServiceException::INVALID_ARGUMENT);
		// creates stamp
		list($usec, $sec) = explode(" ", microtime());
		$microTime = $sec.substr($usec, 2, 6);
		$stamp = $this->createStamp($this->stampsKeyGen, $microTime);
		$this->stampsKeyGen++;
		// registers stamp
		if(!isset($this->stamps)) $this->stamps = array();
		$this->stamps[$stamp->getId()] = $stamp;
		// delivers stamp
		$caller->$callbackMethod($stamp);
		return true;
	}
	
	public function isStampValid($authorizationServiceStamp) {
		if(!($authorizationServiceStamp instanceof AuthorizationServiceStamp)) return false;
		if(!isset($this->stamps)) return false;
		return ($this->stamps[$authorizationServiceStamp->getId()] === $authorizationServiceStamp);
	}
	
	/**
	 * Asserts that the given object is authorized to get stamps
	 * @param Object $object
	 * @throws AuthorizationServiceException with code FORBIDDEN if object is not authorized to get stamps.
	 */
	protected function assertObjectAuthorizedToGetStamp($object) {
		if(!isset($object) || !is_object($object)) throw new AuthorizationServiceException('object should be a non null object.', AuthorizationServiceException::INVALID_ARGUMENT);
		// checks if object is authorized to get stamps.
		if(!$this->isObjectAuthorizedToGetStamp($object)) throw new AuthorizationServiceException("class '$className' is not authorized to get AuthorizationServiceStamps.", AuthorizationServiceException::FORBIDDEN);		
	}
	/**
	 * Checks if the given class is authorized to get stamps.
	 * @param String $className the class name to check for authorization
	 * @return boolean returns true if authorized, else false.
	 */
	protected function isObjectAuthorizedToGetStamp($object) {
		if(is_a($object,"ElementPListDataFlowConnector")) return true;
		if(is_a($object,"ElementPDataFlowConnector")) return true;
		if(is_a($object,"MapObject2ElementDFA")) return true;
		if(is_a($object,"WigiiCoreExecutor")) return true;
		return false;
	}
	
	protected function createStamp($id, $microTime) {
		return AuthorizationServiceStamp::createInstance($id, $microTime);
	}
}



