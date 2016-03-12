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
 * Wigii Business Process Library
 * Created by CWE on 19.11.2014
 */
class WigiiBPL
{
	// Dependency injection
	
	private $_debugLogger;
	private $_executionSink;
	private function debugLogger()
	{
		if(!isset($this->_debugLogger))
		{
			$this->_debugLogger = DebugLogger::getInstance("WigiiBPL");
		}
		return $this->_debugLogger;
	}
	private function executionSink()
	{
		if(!isset($this->_executionSink))
		{
			$this->_executionSink = ExecutionSink::getInstance("WigiiBPL");
		}
		return $this->_executionSink;
	}

	
	private $groupAS;
	public function setGroupAdminService($groupAS)
	{
		$this->groupAS = $groupAS;
	}
	protected function getGroupAdminService()
	{
		// autowired
		if(!isset($this->groupAS))
		{
			$this->groupAS = ServiceProvider::getGroupAdminService();
		}
		return $this->groupAS;
	}
	
	private $userAS;
	public function setUserAdminService($userAS)
	{
		$this->userAS = $userAS;
	}
	protected function getUserAdminService()
	{
		// autowired
		if(!isset($this->userAS))
		{
			$this->userAS = ServiceProvider::getUserAdminService();
		}
		return $this->userAS;
	}
	
	private $moduleAS;
	public function setModuleAdminService($moduleAS)
	{
		$this->moduleAS = $moduleAS;
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
	
	private $authS;
	public function setAuthenticationService($authenticationService)
	{
		$this->authS = $authenticationService;
	}
	protected function getAuthenticationService()
	{
		// autowired
		if(!isset($this->authS))
		{
			$this->authS = ServiceProvider::getAuthenticationService();
		}
		return $this->authS;
	}
	
	private $configS;
	public function setConfigService($configService)
	{
		$this->configS = $configService;
	}
	public function getConfigService()
	{
		// autowired
		if(!isset($this->configS))
		{
			$this->configS = $this->getConfigurationContextInstance($this->getAuthenticationService()->getMainPrincipal());
		}
		return $this->configS;
	}
	
	private $wigiiExecutor;
	public function setWigiiExecutor($wigiiExecutor)
	{
		$this->wigiiExecutor = $wigiiExecutor;
	}
	/**
	 * Returns a reference to the WigiiExecutor.
	 * If in CLI context, then returns a WigiiExecutor stub on which only stateless methods are available and the getConfigurationContext() method.
	 * @return WigiiExecutor
	 */
	private function getWigiiExecutor() {
		if(!isset($this->wigiiExecutor)) {
			$this->wigiiExecutor = WigiiBPLWigiiExecutorStub::createInstance($this);
		}
		return $this->wigiiExecutor;
	}	
	
	/**
	 * Gets a new initialized ConfigurationContext instance.
	 * @param Principal $principal authenticated user performing the operation
	 * @return ConfigurationContextImpl an initialized ConfigurationContext
	 */
	protected function getConfigurationContextInstance($principal) {
		$returnValue = $this->createConfigurationContextInstance();
		$moduleAccess = $principal->getModuleAccess();
		if (!empty($moduleAccess)) {
			foreach ($moduleAccess as $module) {
				if ($module->isAdminModule()) continue;
		
				//autoloads the groupList
				$returnValue->getGroupPList($principal, $module);
			}		
		}
		return $returnValue;
	}
	protected function createConfigurationContextInstance() {
		return ConfigurationContextSubElementImpl::createInstance();
	}
	
	private $dflowS;
	public function setDataFlowService($dataFlowService)
	{
		$this->dflowS = $dataFlowService;
	}
	protected function getDataFlowService()
	{
		// autowired
		if(!isset($this->dflowS))
		{
			$this->dflowS = ServiceProvider::getDataFlowService();
		}
		return $this->dflowS;
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
	
	// System principal management
	
	/**
	 * Adds a system principal or a list of system principals to the WigiiBPL
	 */
	public function addSystemPrincipal($systemPrincipal)
	{
		if(is_null($systemPrincipal)) return;
		$this->getSystemPrincipals()->unionPrincipalList($systemPrincipal);
		$this->debugLogger()->write("received ".$systemPrincipal->count()." system principals.");
	}
	private $systemPrincipals;
	/**
	 * Returns the list of actual system principals owned by the WigiiBPL
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
	
	
	
	
	// Wigii Business Process Library
	
	/*
	 * A Wigii Business Process is exposed as a public method with the following signature
	 * public function xxxx($principal, $caller, $parameter, $executionSink=null)
	 * where :
	 * @param Principal $principal authenticated user executing the Wigii business process
	 * @param Object $caller the object calling the Wigii business process.
	 * @param WigiiBPLParameter $parameter all the necessary information needed to execute the Wigii business process.
	 * The mandatory structure of the WigiiBPLParameter should be described in the method comment so that the user knows
	 * which information needs to be submitted to the Wigii business process in order to work correctly.
	 * @param ExecutionSink $executionSink an optional ExecutionSink instance that can be used to log Wigii business process actions.
	 * @throws WigiiBPLException|Exception in case of error
	 *  
	 * The method can return something, that should be documented using the @return tag, or
	 * the method can take an additional parameter which is a subclass of ObjectList if the method fills a list as an output.
	 */
	
	
	// Wigii Administration
	
	/**
	 * Creates or updates a Role in a given namespace and merges or resets its rights on root folders or selected groups of given modules.
	 * @param Principal $principal authenticated user executing the Wigii business process
	 * @param Object $caller the object calling the Wigii business process.
	 * @param WigiiBPLParameter $parameter the adminCreateRole business process needs the following parameters to run :
	 * - roleName: String. The functional name of the role without the namespace suffix. For instance AFG-CD. It will create a role AFG-CD@wigiiNamespace, 
	 * - wigiiNamespace: String|WigiiNamespace. The WigiiNamespace (or its name) in which to create the role, 
	 * - modules: Array(moduleName=>Module)|Array(moduleName)|String|Module. The modules accessible by this role, 
	 * - groupExp: LogExp. An optional group exp which selects a subset of groups on which to create the UGRs. If null, then selects only root groups, 
	 * - adminRootGroups: Boolean. If true, then the role has admin access on all root groups, 
	 * - adminSelectedGroups: Boolean. If true, then the role has admin access on selected groups,
	 * - writeRootGroups: Boolean. If true, then the role has write access on all root groups,
	 * - writeSelectedGroups: Boolean. If true, then the role has write access on selected groups,
	 * - limitedWriteRootGroups: Boolean. If true, then the role has limited write access on all root groups,
	 * - limitedWriteSelectedGroups: Boolean. If true, then the role has limited write access on selected groups,
	 * - readRootGroups: Boolean. If true, then the role has read access on all root groups, 
	 * - readSelectedGroups: Boolean. If true, then the role has read access on selected groups,
	 * - adminTrashBin: Boolean. If true, then the role has admin access on the trashbin of selected modules,
	 * - createRootGroups: Boolean. If true, then the role can create root groups in selected modules,
	 * - manageUsers: Boolean. If true, then the role can create admin and normal users and has access to all users in namespace,
	 * - resetRights: Boolean. If true, then before setting the rights on the folders, any existing rights for this role are deleted from the database.
	 * @param ExecutionSink $executionSink an optional ExecutionSink instance that can be used to log Wigii business process actions.
	 * @throws WigiiBPLException|Exception in case of error
	 */
	public function adminCreateRole($principal, $caller, $parameter, $executionSink=null) {
		$this->executionSink()->publishStartOperation("adminCreateRole", $principal);
		try {
			if(is_null($principal)) throw new WigiiBPLException('principal cannot be null', WigiiBPLException::INVALID_ARGUMENT);
			if(is_null($parameter)) throw new WigiiBPLException('parameter cannot be null', WigiiBPLException::INVALID_ARGUMENT);
			
			$realUser = ($principal->isPlayingRole() ? $principal->getRealUser() : $principal->getAttachedUser());
			
			$roleName = $parameter->getValue('roleName');
			if(empty($roleName)) throw new WigiiBPLException('roleName cannot be null', WigiiBPLException::INVALID_PARAMETER);
			
			$wigiiNamespace = $parameter->getValue('wigiiNamespace');
			if(!$wigiiNamespace instanceof WigiiNamespace) $wigiiNamespace = $this->getWigiiNamespaceAdminService()->getWigiiNamespace($principal, $wigiiNamespace);
			
			$resetRights = ($parameter->getValue('resetRights') == true);
			$modules = $this->buildModuleArray($principal, $parameter->getValue('modules'));
			$createRootGroups = ($parameter->getValue('createRootGroups') == true);
			$adminRootGroups = ($parameter->getValue('adminRootGroups') == true);
			$adminSelectedGroups = ($parameter->getValue('adminSelectedGroups') == true);
			$writeRootGroups = ($parameter->getValue('writeRootGroups') == true);
			$writeSelectedGroups = ($parameter->getValue('writeSelectedGroups') == true);
			$limitedWriteRootGroups = ($parameter->getValue('limitedWriteRootGroups') == true);
			$limitedWriteSelectedGroups = ($parameter->getValue('limitedWriteSelectedGroups') == true);
			$readRootGroups = ($parameter->getValue('readRootGroups') == true);
			$readSelectedGroups = ($parameter->getValue('readSelectedGroups') == true);
			$manageUsers = ($parameter->getValue('manageUsers') == true);
			$groupExp = $parameter->getValue('groupExp');
			$adminTrashBin = ($parameter->getValue('adminTrashBin') == true);
			
			$userAS = $this->getUserAdminService();
			$mAS = $this->getModuleAdminService();
			$groupAS = $this->getGroupAdminService();
			
			$username = $this->adminCreateRole_buildUserNameForWigiiNamespace($roleName, $wigiiNamespace);
			$userP = $userAS->findUserByName($principal, $username);
			$fsl = FieldSelectorListArrayImpl::createInstance();
			
			// if existing role then checks for update
			if(isset($userP)) {
				$user = $userP->getUser();
				if(!$user->isRole()) throw new WigiiBPLException("role '$username' is not a role", WigiiBPLException::INVALID_ARGUMENT);
				//if($resetRights) $user->setDetail(UserDetail::createInstance());
				$newUser = false;
			}
			// else creates new role
			else {
				$user = User::createInstance();
				$newUser = true;
				$user->setRole(true); $fsl->addFieldSelector('isRole');
				$user->setUsername($username); $fsl->addFieldSelector('username');
				$user->setWigiiNamespace($wigiiNamespace); $fsl->addFieldSelector('wigiiNamespace');
				$user->getDetail()->setDescription("created the ".date("d.m.Y")." by ".$realUser->getWigiiNamespace()->getWigiiNamespaceName().":".$realUser->getUsername());
				$fsl->addFieldSelector('description');
			}
			$userD = $user->getDetail();
			
			// sets module access
			// moduleAccess = moduleAccess merge (if principal is wigiiNamespaceCreator then modules else principal moduleAccess intersect modules)
			if($principal->isWigiiNamespaceCreator()) $pModules = $modules;
			else {
				$pModules = $principal->getModuleAccess();
				if(isset($pModules)) $pModules = array_intersect_key($pModules, $modules);
				else $pModules = array();
			}
			$moduleArray = $userD->getModuleAccess();
			if(isset($moduleArray)) $moduleArray = array_merge($moduleArray, $pModules);
			else $moduleArray = $pModules;
			$userD->setModuleAccess($moduleArray);
			if(!$fsl->containsFieldSelector('moduleAccess')) $fsl->addFieldSelector('moduleAccess');
							
			// sets admin rights
			$this->adminCreateRole_setAdminRightsForModules($principal, $modules, $user, $fsl, $createRootGroups, $adminRootGroups||$adminSelectedGroups, $manageUsers, $resetRights, $executionSink);
			// adds ADMIN access
			if($userD->needsAdminAccess() && !$userD->getModuleAccess(Module::ADMIN_MODULE)) {
				$moduleArray = $userD->getModuleAccess();
				$moduleArray[Module::ADMIN_MODULE] = $mAS->getModule($principal, Module::ADMIN_MODULE);
				$userD->setModuleAccess($moduleArray);
			}
			
			// persists user
			if(!$newUser) $userAS->lock($principal, $user);
			$userAS->persistUser($principal, $user, $fsl);
			if(isset($executionSink)) $executionSink->log(($newUser ? 'inserted':'updated').' role '.$username);
			
			// User Group Rights for each module
			if($newUser) $userAS->lock($principal, $user);
			try {
				foreach($modules as $module) {
					if(isset($executionSink)) $executionSink->log('module '.$module->getModuleName());
					$this->adminCreateRole_createUserGroupRightsForModule($principal, $module, $user, $groupExp, $resetRights, $adminTrashBin,
							$adminRootGroups, $writeRootGroups, $limitedWriteRootGroups, $readRootGroups, 
							$adminSelectedGroups, $writeSelectedGroups, $limitedWriteSelectedGroups, $readSelectedGroups,
							$executionSink);
				}
				// adds the moduleAccess if not defined
				$userAS->matchModuleAccessOnRights($principal, $user->getId());
	
				$userAS->unLock($principal, $user);
			}
			catch(Exception $e) {
				$userAS->unLock($principal, $user);
				throw $e;
			}
		}
		catch(Exception $e) {
			$this->executionSink()->publishEndOperationOnError("adminCreateRole", $e, $principal);
			throw $e;
		}
		$this->executionSink()->publishEndOperation("adminCreateRole", $principal);
	}
	protected function adminCreateRole_buildUserNameForWigiiNamespace($username, $wigiiNamespace) {
		//if the username contains the defaultEmailPostfix, deletes it
		$result = array();
		$allow = '[_a-z0-9-]';
		$defaultEmailPostfix = EMAIL_postfix;
		if(preg_match('/@('.$allow.'+(\.'.$allow.'+)*)(\.'.$allow.'{2,})+$/i', $username, $result)==1){
			if($result[0] == $defaultEmailPostfix){
				$username = str_replace($result[0], "", $username);
			}
		}
		//replaces anything after an @
		$atPos = strpos($username, "@");
		if($atPos!==false){
			$username = substr($username, 0, $atPos);
		}
		if($wigiiNamespace->getWigiiNamespaceName()){
			$username = $username."@".$wigiiNamespace->getWigiiNamespaceName();
		}
		return $username;
	}
	/**
	 * sets rights according to principal rights,
	 * keep any higher existing rights except if resetRights,
	 * sets given modules,
	 * if principal is wigiiNamespaceCreator then wigiiNamespaceCreator = wigiiNamespaceCreator | false = wigiiNamespaceCreator = no change
	 * if principal is moduleEditor then moduleEditor = moduleEditor | false = moduleEditor = no change
	 * if principal is readAllUsersInWigiiNamespace then readAllUsersInWigiiNamespace = readAllUsersInWigiiNamespace | manageUsers
	 * if principal is adminCreator then adminCreator = adminCreator | false = adminCreator = no change
	 * if principal is userCreator then userCreator = userCreator | manageUsers
	 * readAllGroupsInWigiiNamespace = readAllGroupsInWigiiNamespace merge ((if principal is wigiiNamespaceCreator then modules else principal moduleAccess intersect modules) intersect null) = readAllGroupsInWigiiNamespace
	 * rootGroupCreator = rootGroupCreator merge ((if principal is wigiiNamespaceCreator then modules else principal moduleAccess intersect modules) intersect (if createRootGroups then modules else null))
	 * groupCreator = groupCreator merge ((if principal is wigiiNamespaceCreator then modules else principal moduleAccess intersect modules) intersect (if createGroups then modules else null))
	 */ 
	protected function adminCreateRole_setAdminRightsForModules($principal, $modules, $user, $fsl, 
			$createRootGroups, 
			$createGroups, 
			$manageUsers,
			$resetRights,
			$executionSink=null) {
		
		
		// gets desired modules intersected with principal ones
		if($principal->isWigiiNamespaceCreator()) $pModules = $modules;
		else {
			$pModules = $principal->getModuleAccess();
			if(isset($pModules)) $pModules = array_intersect_key($pModules, $modules);
			else $pModules = array();
		}
		
		$userD = $user->getDetail();
		
		// reset admin rights if requested
		if($resetRights) {
			/*
			 * if principal is wigiiNamespaceCreator then wigiiNamespaceCreator = false
	 		 * if principal is moduleEditor then moduleEditor = false
	 		 * if principal is readAllUsersInWigiiNamespace then readAllUsersInWigiiNamespace = false
	 		 * if principal is adminCreator then adminCreator = false
	 		 * if principal is userCreator then userCreator = false
	 		 * readAllGroupsInWigiiNamespace = if principal is wigiiNamespaceCreator then null else readAllGroupsInWigiiNamespace - principal moduleAccess
	 		 * rootGroupCreator = if principal is wigiiNamespaceCreator then null else rootGroupCreator - principal moduleAccess
	 		 * groupCreator = if principal is wigiiNamespaceCreator then null else groupCreator - principal moduleAccess
			 */
			if($principal->isWigiiNamespaceCreator()) {
				$userD->setWigiiNamespaceCreator(false);
				if(!$fsl->containsFieldSelector('wigiiNamespaceCreator')) $fsl->addFieldSelector('wigiiNamespaceCreator');
			}
			//elseif(isset($executionSink)) $executionSink->log('principal '.$principal->getUsername().' is not wigiiNamespaceCreator');
			
			if($principal->isModuleEditor()) {
				$userD->setModuleEditor(false);
				if(!$fsl->containsFieldSelector('moduleEditor')) $fsl->addFieldSelector('moduleEditor');
			}
			//elseif(isset($executionSink)) $executionSink->log('principal '.$principal->getUsername().' is not moduleEditor');
			
			if($principal->isReadAllUsersInWigiiNamespace()) {
				$userD->setReadAllUsersInWigiiNamespace(false);
				if(!$fsl->containsFieldSelector('readAllUsersInWigiiNamespace')) $fsl->addFieldSelector('readAllUsersInWigiiNamespace');
			}
			//elseif(isset($executionSink)) $executionSink->log('principal '.$principal->getUsername().' is not readAllUsersInWigiiNamespace');
			
			if($principal->isAdminCreator()) {
				$userD->setAdminCreator(false);
				if(!$fsl->containsFieldSelector('adminCreator')) $fsl->addFieldSelector('adminCreator');
			}
			//elseif(isset($executionSink)) $executionSink->log('principal '.$principal->getUsername().' is not adminCreator');
			
			if($principal->isUserCreator()) {
				$userD->setUserCreator(false);
				if(!$fsl->containsFieldSelector('userCreator')) $fsl->addFieldSelector('userCreator');
			}
			//elseif(isset($executionSink)) $executionSink->log('principal '.$principal->getUsername().' is not userCreator');
			
			$pModuleAccess = $principal->getModuleAccess();
			if(!isset($pModuleAccess)) $pModuleAccess = array();
			
			// reset readAllGroupsInWigiiNamespace
			if($principal->isWigiiNamespaceCreator()) $moduleArray = null;
			else {
				$moduleArray = $userD->getReadAllGroupsInWigiiNamespace();			
				if(isset($moduleArray)) $moduleArray = array_diff_key($moduleArray, $pModuleAccess);			
				else $moduleArray = null;
			}
			$userD->setReadAllGroupsInWigiiNamespace($moduleArray);
			if(!$fsl->containsFieldSelector('readAllGroupsInWigiiNamespace')) $fsl->addFieldSelector('readAllGroupsInWigiiNamespace');
			
			// reset rootGroupCreator
			if($principal->isWigiiNamespaceCreator()) $moduleArray = null;
			else {
				$moduleArray = $userD->getRootGroupCreator();
				if(isset($moduleArray)) $moduleArray = array_diff_key($moduleArray, $pModuleAccess);
				else $moduleArray = null;
			}
			$userD->setRootGroupCreator($moduleArray);
			if(!$fsl->containsFieldSelector('rootGroupCreator')) $fsl->addFieldSelector('rootGroupCreator');
			
			// reset groupCreator
			if($principal->isWigiiNamespaceCreator()) $moduleArray = null;
			else {
				$moduleArray = $userD->getGroupCreator();
				if(isset($moduleArray)) $moduleArray = array_diff_key($moduleArray, $pModuleAccess);
				else $moduleArray = null;
			}
			$userD->setGroupCreator($moduleArray);
			if(!$fsl->containsFieldSelector('groupCreator')) $fsl->addFieldSelector('groupCreator');
		}
		
		// sets new admin rights
		
		if($principal->isReadAllUsersInWigiiNamespace() && !$userD->isReadAllUsersInWigiiNamespace() && $manageUsers) {
			$userD->setReadAllUsersInWigiiNamespace(true);
			if(!$fsl->containsFieldSelector('readAllUsersInWigiiNamespace')) $fsl->addFieldSelector('readAllUsersInWigiiNamespace');
		}
		if($principal->isUserCreator() && !$userD->isUserCreator() && $manageUsers) {
			$userD->setUserCreator(true);
			if(!$fsl->containsFieldSelector('userCreator')) $fsl->addFieldSelector('userCreator');
		}
		
		if($createRootGroups) {
			$moduleArray = $userD->getRootGroupCreator();
			if(isset($moduleArray)) $moduleArray = array_merge($moduleArray, $pModules);
			else $moduleArray = $pModules;
			$userD->setRootGroupCreator($moduleArray);
			if(!$fsl->containsFieldSelector('rootGroupCreator')) $fsl->addFieldSelector('rootGroupCreator');
		}
		
		if($createGroups) {
			$moduleArray = $userD->getGroupCreator();
			if(isset($moduleArray)) $moduleArray = array_merge($moduleArray, $pModules);
			else $moduleArray = $pModules;
			$userD->setGroupCreator($moduleArray);
			if(!$fsl->containsFieldSelector('groupCreator')) $fsl->addFieldSelector('groupCreator');
		}
		// forces groupCreator if rootGroupCreator
		if($createRootGroups && $userD->getRootGroupCreator()!=null){
			if($userD->getGroupCreator()==null){
				$userD->setGroupCreator($userD->getRootGroupCreator());
			} else {
				$userD->setGroupCreator(array_merge($userD->getRootGroupCreator(), $userD->getGroupCreator()));
			}
			if(!$fsl->containsFieldSelector('groupCreator')) $fsl->addFieldSelector('groupCreator');
		}
	}
	
	protected function adminCreateRole_createUserGroupRightsForModule($principal, $module, $user, $groupExp, $resetRights, $adminTrashBin,
			$adminRootGroups, $writeRootGroups, $limitedWriteRootGroups, $readRootGroups,
			$adminSelectedGroups, $writeSelectedGroups, $limitedWriteSelectedGroups, $readSelectedGroups,
			$executionSink=null) {
		
		$accessRootGroups = $adminRootGroups || $writeRootGroups || $limitedWriteRootGroups || $readRootGroups;
		$accessSelectedGroups = $adminSelectedGroups || $writeSelectedGroups || $limitedWriteSelectedGroups || $readSelectedGroups;
		$userId = $user->getId();
		$wigiiNamespace = $user->getWigiiNamespace();
		
		$userAS = $this->getUserAdminService();
		$groupAS = $this->getGroupAdminService();
		$configS = $this->getConfigService();
		
		$moduleLogExp = lxAnd(lxEq(fs('wigiiNamespace'), $wigiiNamespace->getWigiiNamespaceName()), lxEq(fs('module'), $module->getModuleName()));
		// includes trashbin if needs to administrate it
		$trashBinId = $configS->getParameter($principal, $module, 'trashBinGroup');
		if(isset($trashBinId) && $adminTrashBin) $trashBinLogExp = lxEq(fs('id'), $trashBinId);
		else $trashBinLogExp = null;
		//if(isset($executionSink)) $executionSink->log('trashBinId: '.$trashBinId);
		
		// gets selected groups principal rights
		$groupPList = GroupPListArrayImpl::createInstance();
		if(isset($groupExp) && $accessSelectedGroups) {
			// filters trashbin subgroups
			if(isset($trashBinId)) {
				$trashBinSubGroups = TechnicalServiceProvider::getValueListArrayMapper();
				$groupAS->getSelectedGroupsField($this->getRootPrincipal(), fs('id'), lxEq(fs('id'), $trashBinId), -1, $trashBinSubGroups);
				$groupExp = lxAnd($groupExp, lxNotIn(fs('id'), $trashBinSubGroups->getValueListArray()));
				TechnicalServiceProvider::recycleValueListArrayMapper($trashBinSubGroups);
			}
			// gets selected groups principal rights
			$groupAS->getSelectedGroups($principal, lf($groupAS->getFieldSelectorListForGroupWithoutDetail(), lxAnd($moduleLogExp, $groupExp)), $groupPList);
			$selectedGroupsRights = $groupPList->getGroupRightsAsLetter();
		}
		else $selectedGroupsRights = array();
		// get root groups principal rights
		$groupPList->reset();
		if($accessRootGroups) {
			$rootGroupExp = lxEq(fs('id_group_parent'), null);
			$groupAS->getSelectedGroups($principal, lf($groupAS->getFieldSelectorListForGroupWithoutDetail(), lxAnd($moduleLogExp, (isset($trashBinLogExp) ? lxOr($rootGroupExp, $trashBinLogExp) : $rootGroupExp))), $groupPList);
			$rootGroupsRights = $groupPList->getGroupRightsAsLetter();
		}
		// gets trashbin if needed
		elseif(isset($trashBinLogExp)) {
			$groupAS->getSelectedGroups($principal, lf($groupAS->getFieldSelectorListForGroupWithoutDetail(), lxAnd($moduleLogExp, $trashBinLogExp)), $groupPList);
			$rootGroupsRights = $groupPList->getGroupRightsAsLetter();
		}
		else $rootGroupsRights = array();
		$groupIds = array();
		foreach($rootGroupsRights as $groupId => $v) {
			$groupIds[$groupId] = $groupId;
		}
		foreach($selectedGroupsRights as $groupId => $v) {
			$groupIds[$groupId] = $groupId;
		}
		//if(isset($executionSink)) $executionSink->log('groupIds: '.json_encode($groupIds));
		//if(isset($executionSink)) $executionSink->log('rootGroupsRights: '.json_encode($rootGroupsRights).', selectedGroupsRights: '.json_encode($selectedGroupsRights));
		
		// if reset rights then selects all groups for principal
		if($resetRights) {
			$groupPList->reset();
			$groupAS->getSelectedGroups($principal, lf($groupAS->getFieldSelectorListForGroupWithoutDetail(), $moduleLogExp), $groupPList);
			$allGroupsRights = $groupPList->getGroupRightsAsLetter();
			$accessibleGroupIds = $groupPList->getGroupIds();
		}
		else $accessibleGroupIds = $groupIds;
		//if(isset($executionSink)) $executionSink->log('accessibleGroupIds: '.json_encode($accessibleGroupIds));
		
		// lookups existing user group rights
		if(!empty($accessibleGroupIds)) {
			$groupRPList = GroupRPListArrayImpl::createInstance();
			$userAS->getAllGroups($principal, $userId, $module, $accessibleGroupIds, $groupRPList, $groupAS->getListFilterForSelectGroupWithoutDetail());
			$userGroupRights = $groupRPList->getGroupRightsAsLetter();
			
			// if reset rights then remove all existing rights of the user on the accessible groups
			if($resetRights) {
				//if(isset($executionSink)) $executionSink->log('reset rights');
				foreach($userGroupRights as $groupId=>$existingURight) {
					$pRight = $allGroupsRights[$groupId];
					if(isset($existingURight)) $existingURight = strtolower($existingURight);
					if(isset($pRight)) $pRight = strtolower($pRight);
					
					// removes user group right if principal has right to do it
					if($pRight >= $existingURight) {
						$groupAS->removeUser($principal, $groupId, $userId);
						$userGroupRights[$groupId] = null;
						if(isset($executionSink)) $executionSink->log('removed rights on group '.$groupId);
					}
					//elseif(isset($executionSink)) $executionSink->log("failed to remove UGR on groupId: $groupId, $uRight, existingURight: $existingURight, pRight: $pRight"); 
				}
			}
		}
		
		// sets user group rights for all selected groups
		if($adminRootGroups) $rootGroupRight = 'x';
		elseif($writeRootGroups) $rootGroupRight = 'w';
		elseif($limitedWriteRootGroups) $rootGroupRight = 's';
		elseif($readRootGroups) $rootGroupRight = 'r';
		else $rootGroupRight = null;
		
		if($adminSelectedGroups) $selectedGroupRight = 'x';
		elseif($writeSelectedGroups) $selectedGroupRight = 'w';
		elseif($limitedWriteSelectedGroups) $selectedGroupRight = 's';
		elseif($readSelectedGroups) $selectedGroupRight = 'r';
		else $selectedGroupRight = null;
		
		$combinedRight = ($rootGroupRight > $selectedGroupRight ? $rootGroupRight : $selectedGroupRight);
		if(empty($groupIds) && isset($executionSink)) $executionSink->log('no selected group');
		foreach($groupIds as $groupId) {
			// if trashbin group and no admin rights on trashbin, then continues
			if($groupId == $trashBinId && !$adminTrashBin) continue;
			
			// gets principal rights on current group
			// and desired right for user
			$pRight = null; $uRight = null;
			// if group is selected and also root --> takes combined right
			if(isset($rootGroupsRights[$groupId]) && isset($selectedGroupsRights[$groupId])) {
				$pRight = $rootGroupsRights[$groupId];
				$uRight = $combinedRight;
			}
			// else if root group
			elseif(isset($rootGroupsRights[$groupId])) {
				$pRight = $rootGroupsRights[$groupId];
				$uRight = $rootGroupRight;
			}
			// else if selected group
			elseif(isset($selectedGroupsRights[$groupId])) {
				$pRight = $selectedGroupsRights[$groupId];
				$uRight = $selectedGroupRight;
			}
		
			// if trashbin group then sets admin rights if needed
			if($groupId == $trashBinId && $adminTrashBin) $uRight = 'x';
			
			// gets existing user right
			$existingURight = $userGroupRights[$groupId];
			if(isset($existingURight)) $existingURight = strtolower($existingURight);
			if(isset($pRight)) $pRight = strtolower($pRight);
		
			// sets right if desired right is higher from current right and principal has rights to do it.
			if($uRight > $existingURight && $pRight >= $uRight) {
				$groupAS->setUserRight($principal, UGR::createInstance($groupId, $userId)->setRightsFromLetter($uRight));
				if(isset($executionSink)) $executionSink->log('added '.$uRight.' right on group '.$groupId);
			}
			//elseif(isset($executionSink)) $executionSink->log("failed to add UGR on groupId: $groupId, uRight: $uRight, existingURight: $existingURight, pRight: $pRight");
		}
	}
	
	/**
	 * Creates or updates roles in a given list of namespaces, using a generic role name and an optional list of divisions.
	 * If the list of divisions is provided, then the generic role name is combined to create functional role names, for example:
	 * generic role name = CD, divisions = AFG, COD, HTI, will produce three roles by namespace equal to: AFG-CD, COD-CD, HTI-CD. 
	 * For each functional role and each namespace, the wigii business process 'adminCreateRole' is called. 
	 * @param Principal $principal authenticated user executing the Wigii business process
	 * @param Object $caller the object calling the Wigii business process.
	 * @param WigiiBPLParameter $parameter the adminCreateRoleByWigiiNamespace business process needs the following parameters to run :
	 * - genericRoleName: String|Array(String). The main functional name describing the role, that will be combined with a division and a namespace. 
	 * It can be an array of strings. In that case the process will be repeated for each entry in the array. 
	 * For instance CD or DCD or Fin-User that will be derived in :
	 * AFG-CD@wigiiNamespace1, COD-CD@wigiiNamespace1, ..., AFG-CD@wigiiNamespace2, ...
	 * AFG-DCD@wigiiNamespace1, COD-DCD@wigiiNamespace1, ..., AFG-DCD@wigiiNamespace2, ...
	 * AFG-Fin-user@wigiiNamespace1, COD-Fin-user@wigiiNamespace1, ..., AFG-Fin-user@wigiiNamespace2, ...
	 * Loop by wigiiNamespace, loop by divisions.
	 * - wigiiNamespaces: Array(wigiiNamespaceName=>WigiiNamespace)|Array(wigiiNamespaceName)|String|WigiiNamespace. The list of WigiiNamespaces in which to create the roles,
	 * - modulesByWigiiNamespace: Array(wigiiNamespaceName=>Array(moduleName=>Module)|Array(moduleName)|String|Module). By namespace name, the list of modules accessible by the role,
	 * - divisions: Array(String). The list of divisions applied on the generic role name. For instance array('AFG', 'COD', 'HTI'),
	 * - WigiiBPL::adminCreateRole(*): also needs all the parameters from the adminCreateRole method.
	 * @param ExecutionSink $executionSink an optional ExecutionSink instance that can be used to log Wigii business process actions.
	 * @throws WigiiBPLException|Exception in case of error
	 */
	public function adminCreateRoleByWigiiNamespace($principal, $caller, $parameter, $executionSink=null) {
		$this->executionSink()->publishStartOperation("adminCreateRoleByWigiiNamespace", $principal);
		$currentWigiiNamespace = null;
		try {
			if(is_null($principal)) throw new WigiiBPLException('principal cannot be null', WigiiBPLException::INVALID_ARGUMENT);
			if(is_null($parameter)) throw new WigiiBPLException('parameter cannot be null', WigiiBPLException::INVALID_ARGUMENT);
			
			$genericRoleNames = $parameter->getValue('genericRoleName');
			if(empty($genericRoleNames)) throw new WigiiBPLException('genericRoleName cannot be empty', WigiiBPLException::INVALID_PARAMETER);
			if(!is_array($genericRoleNames)) $genericRoleNames = array($genericRoleNames);
			$nRoles = (count($genericRoleNames) > 1);
			
			$wigiiNamespaces = $this->buildWigiiNamespaceArray($principal, $parameter->getValue('wigiiNamespaces'));
			$modulesByWigiiNamespace = $parameter->getValue('modulesByWigiiNamespace');		
			$divisions = $parameter->getValue('divisions');
			
			// gets admin roles
			$adminRoles = $this->adminGetPrincipalAdminRolesByWigiiNamespace($principal, $caller, null, $executionSink);
			//if(isset($executionSink)) $executionSink->log('Admin roles: '.json_encode($adminRoles));
			
			$currentWigiiNamespace = $principal->getWigiiNamespace();
			foreach($genericRoleNames as $genericRoleName) {
				if(empty($genericRoleName)) throw new WigiiBPLException('genericRoleName cannot be empty', WigiiBPLException::INVALID_PARAMETER);
				if($nRoles && isset($executionSink)) {
					$executionSink->log($nRoles.'. role '.$genericRoleName);
					$nRoles = $nRoles+1;
				}
				foreach($wigiiNamespaces as $wigiiNamespace) {
					$parameter->setValue('wigiiNamespace', $wigiiNamespace);
					$wigiiNamespaceName = $wigiiNamespace->getWigiiNamespaceName();
					$parameter->setValue('modules', $modulesByWigiiNamespace[$wigiiNamespaceName]);
					
					if(isset($executionSink)) $executionSink->log('wigiiNamespace '.$wigiiNamespaceName);
					$principal->bindToRole($adminRoles[$wigiiNamespaceName]);
					if(!empty($divisions)) {
						foreach($divisions as $division) {
							$parameter->setValue('roleName', $division.'-'.$genericRoleName);
							$parameter->setValue('groupExp', lxEq(fs('groupname'), $division));
								
							$this->adminCreateRole($principal, $caller, $parameter, $executionSink);
						}
					}
					else {
						$parameter->setValue('roleName', $genericRoleName);
						$this->adminCreateRole($principal, $caller, $parameter, $executionSink);
					}
				}
			}
			$principal->bindToWigiiNamespace($currentWigiiNamespace);
		}
		catch(Exception $e) {
			$this->executionSink()->publishEndOperationOnError("adminCreateRoleByWigiiNamespace", $e, $principal);
			if(isset($currentWigiiNamespace)) $principal->bindToWigiiNamespace($currentWigiiNamespace);
			throw $e;
		}
		$this->executionSink()->publishEndOperation("adminCreateRoleByWigiiNamespace", $principal);
	}
	
	/**
	 * Fills an array with all WigiiNamespaces and Modules a Principal has access.
	 * @param Principal $principal authenticated user executing the Wigii business process
	 * @param Object $caller the object calling the Wigii business process.
	 * @param WigiiBPLParameter $parameter A WigiiBPLParameter instance with the following parameters :
	 * - refetchRoles: Boolean. Optional boolean. If true, then principal roles are refetched from the database. 
	 * Else keeps the existing role list attached to the principal. Default to false. 
	 * @param ExecutionSink $executionSink an optional ExecutionSink instance that can be used to log Wigii business process actions.
	 * @throws WigiiBPLException|Exception in case of error
	 * @return Array an array of the form Array(wigiiNamespaceName=>Array(moduleName=>moduleName))
	 */
	public function adminGetPrincipalNamespacesAndModules($principal, $caller, $parameter, $executionSink=null) {
		$this->executionSink()->publishStartOperation("adminGetPrincipalNamespacesAndModules", $principal);
		$returnValue = null;
		try {
			if(is_null($principal)) throw new WigiiBPLException('principal cannot be null', WigiiBPLException::INVALID_ARGUMENT);
			if(isset($parameter) && $parameter->getValue('refetchRoles')) $refetchRoles = true;
			else $refetchRoles = false;
			
			// if refetchRoles
			if($refetchRoles) {
				$currentWigiiNamespace = $principal->getWigiiNamespace();
				
				// gets default WigiiNamespace
				$defaultWigiiNamespace = (string)$this->getConfigService()->getParameter($principal, null, "defaultWigiiNamespace");
				if(!$defaultWigiiNamespace){
					$defaultWigiiNamespace = $principal->getRealWigiiNamespace()->getWigiiNamespaceUrl();
				}
				
				// refetches all principal roles
				$principal->refetchAllRoles(lf(null, null, fskl(fsk('wigiiNamespace'), fsk('username'))), UserListForNavigationBarImpl::createInstance($defaultWigiiNamespace));
				
				$principal->bindToWigiiNamespace($currentWigiiNamespace);
				
				$roleList = $principal->getRoleListener();
			}
			// else checks that Principal roleList is a UserListForNavigationBarImpl instance
			else {
				$roleList = $principal->getRoleListener();
				if(!($roleList instanceof UserListForNavigationBarImpl)) throw new WigiiBPLException('principal attached role list is not an instance of UserListForNavigationBarImpl', WigiiBPLException::UNSUPPORTED_OPERATION);
			}
			
			// adds default wigiiNamespace modules
			$wigiiNamespaceName = $roleList->getDefaultWigiiNamespace();
			if($wigiiNamespaceName == WigiiNamespace::EMPTY_NAMESPACE_URL) $wigiiNamespaceName = WigiiNamespace::EMPTY_NAMESPACE_NAME;
			$modules = $roleList->getDefaultWigiiNamespaceModules();
			if(!empty($modules)) {
				$modules = array_keys($modules);
				$modules = array_combine($modules, $modules);
				unset($modules[Module::ADMIN_MODULE]);
				$returnValue[$wigiiNamespaceName] = array_merge((isset($returnValue[$wigiiNamespaceName]) ? $returnValue[$wigiiNamespaceName] : array()), $modules);
			}
			// adds other wigiiNamespace modules
			$wigiiNamespaceUrls = $roleList->getOtherWigiiNamespaces();
			if(!empty($wigiiNamespaceUrls)) {
				foreach($wigiiNamespaceUrls as $wigiiNamespaceName => $modules) {
					if($wigiiNamespaceName == WigiiNamespace::EMPTY_NAMESPACE_URL) $wigiiNamespaceName = WigiiNamespace::EMPTY_NAMESPACE_NAME;
					if(!empty($modules)) {
						$modules = array_keys($modules);
						$modules = array_combine($modules, $modules);
						unset($modules[Module::ADMIN_MODULE]);
						$returnValue[$wigiiNamespaceName] = array_merge((isset($returnValue[$wigiiNamespaceName]) ? $returnValue[$wigiiNamespaceName] : array()), $modules);
					}
				}
			}
		}
		catch(Exception $e) {
			$this->executionSink()->publishEndOperationOnError("adminGetPrincipalNamespacesAndModules", $e, $principal);
			throw $e;
		}
		$this->executionSink()->publishEndOperation("adminGetPrincipalNamespacesAndModules", $principal);
		return $returnValue;
	}
	
	/**
	 * Fills an array with principal admin role ID by WigiiNamespace.
	 * Only one admin role is kept by WigiiNamespace. Priority is given to role having 'wigiiNamespaceCreator' or 'moduleEditor' rights.
	 * @param Principal $principal authenticated user executing the Wigii business process
	 * @param Object $caller the object calling the Wigii business process.
	 * @param WigiiBPLParameter $parameter A WigiiBPLParameter instance with the following parameters :
	 * - refetchRoles: Boolean. Optional boolean. If true, then principal roles are refetched from the database.
	 * Else keeps the existing role list attached to the principal. Default to false.
	 * @param ExecutionSink $executionSink an optional ExecutionSink instance that can be used to log Wigii business process actions.
	 * @throws WigiiBPLException|Exception in case of error
	 * @return Array an array of the form Array(wigiiNamespaceName=>roleId)
	 */
	public function adminGetPrincipalAdminRolesByWigiiNamespace($principal, $caller, $parameter, $executionSink=null) {
		$this->executionSink()->publishStartOperation("adminGetPrincipalAdminRolesByWigiiNamespace", $principal);
		$returnValue = null;
		try {
			if(is_null($principal)) throw new WigiiBPLException('principal cannot be null', WigiiBPLException::INVALID_ARGUMENT);
			if(isset($parameter) && $parameter->getValue('refetchRoles')) $refetchRoles = true;
			else $refetchRoles = false;
				
			// if refetchRoles
			if($refetchRoles) {
				$currentWigiiNamespace = $principal->getWigiiNamespace();
	
				// gets default WigiiNamespace
				$defaultWigiiNamespace = (string)$this->getConfigService()->getParameter($principal, null, "defaultWigiiNamespace");
				if(!$defaultWigiiNamespace){
					$defaultWigiiNamespace = $principal->getRealWigiiNamespace()->getWigiiNamespaceUrl();
				}
	
				// refetches all principal roles
				$principal->refetchAllRoles(lf(null, null, fskl(fsk('wigiiNamespace'), fsk('username'))), UserListForNavigationBarImpl::createInstance($defaultWigiiNamespace));
	
				$principal->bindToWigiiNamespace($currentWigiiNamespace);
	
				$roleList = $principal->getRoleListener();
			}
			// else checks that Principal roleList is a UserListForNavigationBarImpl instance
			else {
				$roleList = $principal->getRoleListener();
				if(!($roleList instanceof UserListForNavigationBarImpl)) throw new WigiiBPLException('principal attached role list is not an instance of UserListForNavigationBarImpl', WigiiBPLException::UNSUPPORTED_OPERATION);
			}
				
			// gets admin roles
			$adminRoleIds = $roleList->getAdminRoleIds();
			$returnValue = array();
			if (!empty($adminRoleIds)) {
				foreach ($adminRoleIds as $adminRoleId) {
					$role = $roleList->getUser($adminRoleId);
					if(isset($role)) {
						$wigiiNamespaceName = $role->getWigiiNamespace()->getWigiiNamespaceName();
						// keeps any defined superadmin or moduleeditor
						$currentRole = $returnValue[$wigiiNamespaceName];
						if(isset($currentRole)) $currentRole = $roleList->getUser($currentRole);
						if(!isset($currentRole) || !($currentRole->getDetail()->isWigiiNamespaceCreator() || $currentRole->getDetail()->isModuleEditor())) {
							$returnValue[$wigiiNamespaceName] = $adminRoleId;
						}
					}
				}
			}
		}
		catch(Exception $e) {
			$this->executionSink()->publishEndOperationOnError("adminGetPrincipalAdminRolesByWigiiNamespace", $e, $principal);
			throw $e;
		}
		$this->executionSink()->publishEndOperation("adminGetPrincipalAdminRolesByWigiiNamespace", $principal);
		return $returnValue;
	}
	
	/**
	 * Calculates the best matching role ID the principal should use to direct access the requested element or group.
	 * Returns null if principal has no roles matching the requested ID.
	 * @param Principal $principal authenticated user executing the Wigii business process
	 * @param Object $caller the object calling the Wigii business process.
	 * @param WigiiBPLParameter $parameter A WigiiBPLParameter instance with the following parameters :
	 * - directAccessType: String. One of 'element' or 'group'. Specifies the type of direct access: by element ID or group ID.
	 * - directAccessId: Int. The element ID or group ID to which the principal needs to access.
	 * - module: Module|String. The Element or Group module if known
	 * @param ExecutionSink $executionSink an optional ExecutionSink instance that can be used to log Wigii business process actions.
	 * @throws WigiiBPLException|Exception in case of error
	 * @return Int the role ID that should be used to best access the requested item or null if no match.
	 */
	public function adminGetPrincipalRoleForDirectAccess($principal, $caller, $parameter, $executionSink=null) {
		$this->executionSink()->publishStartOperation("adminGetPrincipalRoleForDirectAccess", $principal);
		$returnValue=null;
		try {
			if(is_null($principal)) throw new WigiiBPLException('principal cannot be null', WigiiBPLException::INVALID_ARGUMENT);
			if(is_null($parameter)) throw new WigiiBPLException('parameter cannot be null', WigiiBPLException::INVALID_ARGUMENT);
				
			$directAccessType = $parameter->getValue('directAccessType');
			if($directAccessType!='element' && $directAccessType!='group') throw new WigiiBPLException("directAccessType can only by one of 'element' or 'group'", WigiiBPLException::INVALID_ARGUMENT);
			$directAccessId = $parameter->getValue('directAccessId');
			if(is_null($directAccessId)) throw new WigiiBPLException('directAccessId cannot be null', WigiiBPLException::INVALID_ARGUMENT);
			$module = $parameter->getValue('module');
			
			$rootP = $this->getRootPrincipal();
			if(isset($module) && !($module instanceof Module)) $this->getModuleAdminService()->getModule($rootP, $module);
			
			// builds the list of groups for which to calculate access rights
			// in case of accessing directly an element
			if($directAccessType=='element') {
				$wigiiNamespaces = $this->elementGetWigiiNamespaceArray($principal, $this, wigiiBPLParam('elementId',$directAccessId,'module',$module));
				$groups=array();
				if(!empty($wigiiNamespaces)) {
					foreach($wigiiNamespaces as $sharings) {
						$groups=array_merge($groups,$sharings);
					}
				}
			}
			// in case of accessing directly a group
			else {
				$groups=array($directAccessId=>$directAccessId);
			}
			
			// calculates the rights for each group and keeps the WigiiNamespace which :
			// 1. is equal to current http request namespace,
			// 2. has the highest access rights
			if(!empty($groups)) {
				$parameter = wigiiBPLParam();
				$returnValue=null;
				$highestRight=null;
				foreach($groups as $groupId) {
					$parameter->setValue('group', $groupId);
					$wigiiNamespaces = $this->adminGetPrincipalRightsOnGroup($principal, $this, $parameter);					
					if(!empty($wigiiNamespaces)) {
						// checks if current http request namespace is accessible, if true, returns this one
						$wigiiNamespace= ServiceProvider::getExecutionService()->getCrtWigiiNamespace()->getWigiiNamespaceName();
						if($wigiiNamespaces[$wigiiNamespace]) {
							$returnValue=$wigiiNamespace;							
							break;						
						}
						// else extracts the namespace with highest right
						else {
							foreach($wigiiNamespaces as $wigiiNamespace=>$right) {
								if(!isset($highestRight) || ($highestRight<$right)) {
									$highestRight = $right;
									$returnValue=$wigiiNamespace;
								}
							}
						}
					}
				}
				
				// if found a matching WigiiNamespace then extracts the associated calculated roleId
				// using the attached RoleListener if defined
				$roleList = $principal->getRoleListener();
				if(($roleList instanceof UserListForNavigationBarImpl) && !$roleList->isEmpty()) {
					$returnValue = $roleList->getCalculatedRoleId($this->getWigiiNamespaceAdminService()->getWigiiNamespace($rootP, $returnValue)->getWigiiNamespaceUrl());
				}
				// else by switching to the namespace and back again.
				else {
					$currentWigiiNamespace = $principal->getWigiiNamespace();
					$principal->bindToWigiiNamespace($returnValue);
					$returnValue=$principal->getUserId();
					$principal->bindToWigiiNamespace($currentWigiiNamespace);
				}
			}
		}
		catch(Exception $e) {
			$this->executionSink()->publishEndOperationOnError("adminGetPrincipalRoleForDirectAccess", $e, $principal);
			throw $e;
		}
		$this->executionSink()->publishEndOperation("adminGetPrincipalRoleForDirectAccess", $principal);
		return $returnValue;
	}
	
	/**
	 * Fills an array with the rights a principal has on a group by WigiiNamespace
	 * @param Principal $principal authenticated user executing the Wigii business process
	 * @param Object $caller the object calling the Wigii business process.
	 * @param WigiiBPLParameter $parameter A WigiiBPLParameter instance with the following parameters :
	 * - group: Int|Group|GroupP. The group Id or group object for which to get the PrincipalRights distribution per WigiiNamespace
	 * @param ExecutionSink $executionSink an optional ExecutionSink instance that can be used to log Wigii business process actions.
	 * @throws WigiiBPLException|Exception in case of error
	 * @return Array an array of the form Array(wigiiNamespaceName=>x|w|s|r), 
	 * WigiiNamespaces with no access to group are not present in the returned array.
	 */
	public function adminGetPrincipalRightsOnGroup($principal, $caller, $parameter, $executionSink=null) {
		$this->executionSink()->publishStartOperation("adminGetPrincipalRightsOnGroup", $principal);
		$returnValue = null;
		try {
			if(is_null($principal)) throw new WigiiBPLException('principal cannot be null', WigiiBPLException::INVALID_ARGUMENT);
			if(is_null($parameter)) throw new WigiiBPLException('parameter cannot be null', WigiiBPLException::INVALID_ARGUMENT);
								
			$group = $parameter->getValue('group');
			if(is_null($group)) throw new WigiiBPLException('group cannot be null. It should be an existing group or group ID', WigiiBPLException::INVALID_ARGUMENT);
			if($group instanceof GroupP) $group = $group->getDbEntity();
			
			$rootP = $this->getRootPrincipal();
			
			// fetches group if needed
			if(!($group instanceof Group)) {
				$group = $this->getGroupAdminService()->getGroupWithoutDetail($rootP, $group);
				if(is_null($group)) return array(); // group does not exist in db, returns no rights.
			}
			
			// checks if Principal role list is already filled into memory
			$roleList = $principal->getRoleListener();
			$refetchAllRoles = !(($roleList instanceof UserListForNavigationBarImpl) && !$roleList->isEmpty());
			unset($roleList);
			 
			// gets Principal accessible WigiiNamespaces and Modules
			$wigiiNamespaces = $this->adminGetPrincipalNamespacesAndModules($principal, $this, wigiiBPLParam('refetchRoles',$refetchAllRoles));
			
			// calculates dynamic access rights of principal for all accessible WigiiNamespace matching Module
			$returnValue = array();
			if(!empty($wigiiNamespaces)) {
				$gAS = $this->getGroupAdminService();
				$groupModule = $group->getModule()->getModuleName();
				$groupId = $group->getId();
				$groupFsl = fsl(fs('id'));
				$currentWigiiNamespace = $principal->getWigiiNamespace();
				foreach($wigiiNamespaces as $wigiiNamespace=>$modules) {
					// restricts on specific group Module
					if($modules[$groupModule]) {
						// binds to each wigiiNamespace
						$principal->bindToWigiiNamespace($wigiiNamespace);
						// calculates principal rights on group 
						$groupP = $gAS->getGroup($principal,$groupId,$groupFsl);
						// if defined, stores rights in array as a letter x|w|s|r
						if(isset($groupP)) {
							$rights = $groupP->getRights();
							if(isset($rights)) {
								 $returnValue[$wigiiNamespace] = $rights->getLetter();
							}
						}
					}
				}
				$principal->bindToWigiiNamespace($currentWigiiNamespace);
			}
		}
		catch(Exception $e) {
			$this->executionSink()->publishEndOperationOnError("adminGetPrincipalRightsOnGroup", $e, $principal);
			throw $e;
		}
		$this->executionSink()->publishEndOperation("adminGetPrincipalRightsOnGroup", $principal);
		return $returnValue;
	}
	
	/**
	 * Copies a given element to a specified folder (real copy, not sharing).
	 * @param Principal $principal authenticated user executing the Wigii business process
	 * @param Object $caller the object calling the Wigii business process.
	 * @param WigiiBPLParameter $parameter the elementCopyTo business process needs the following parameters to run :
	 * - element: Element. The filled element to be copied. All the fields in the FieldList will be copied,
	 * - groupId: Int. The group id in which to copy the element. If the group has another Module than the Element, then a matching on FieldName and DataType is done.
	 * @param ExecutionSink $executionSink an optional ExecutionSink instance that can be used to log Wigii business process actions.
	 * @throws WigiiBPLException|Exception in case of error
	 */
	public function elementCopyTo($principal, $caller, $parameter, $executionSink=null) {
		$this->executionSink()->publishStartOperation("elementCopyTo", $principal);
		try {
			if(is_null($principal)) throw new WigiiBPLException('principal cannot be null', WigiiBPLException::INVALID_ARGUMENT);
			if(is_null($parameter)) throw new WigiiBPLException('parameter cannot be null', WigiiBPLException::INVALID_ARGUMENT);
			
			$element = $parameter->getValue('element');
			if(is_null($element)) throw new WigiiBPLException('element cannot be null', WigiiBPLException::INVALID_PARAMETER);
			$groupId = $parameter->getValue('groupId');
			if(is_null($groupId)) throw new WigiiBPLException('groupId cannot be null', WigiiBPLException::INVALID_PARAMETER);
			
			$this->getDataFlowService()->processDumpableObject($principal, 
					$this->buildCopyElementDataFlowConnector($element, $this->buildConfigSelectorForGroup($principal, $groupId)), 
					dfasl(
						dfas('ElementDFA',
							'setMode', '1',
							'setGroupId', $groupId
						)
					));
		}
		catch(Exception $e) {
			$this->executionSink()->publishEndOperationOnError("elementCopyTo", $e, $principal);
			throw $e;
		}
		$this->executionSink()->publishEndOperation("elementCopyTo", $principal);
	}
	
	/**
	 * Fetches an Element into the database. All fields are fetched and element is ready to be displayed.
	 * For fetching only part of the element, use the ElementService or ElementP data flow connector.
	 * @param Principal $principal authenticated user executing the Wigii business process
	 * @param Object $caller the object calling the Wigii business process.
	 * @param WigiiBPLParameter $parameter the elementFetch business process needs the following parameters to run :
	 * - elementId: Int. The ID of the element to fetch,
	 * - wigiiNamespace: WigiiNamespace|String. The WigiiNamespace in which to fetch the element
	 * - module: Module|String. The Module of the Element.
	 * @param ExecutionSink $executionSink an optional ExecutionSink instance that can be used to log Wigii business process actions.
	 * @throws WigiiBPLException|Exception in case of error
	 * @return ElementP the element filled from the database with Principal rights calculation.
	 */
	public function elementFetch($principal, $caller, $parameter, $executionSink=null) {
		$this->executionSink()->publishStartOperation("elementFetch", $principal);
		$returnValue = null;
		$crtNamespace = null; $changedNamespace = false;
		try {
			if(is_null($principal)) throw new WigiiBPLException('principal cannot be null', WigiiBPLException::INVALID_ARGUMENT);
			if(is_null($parameter)) throw new WigiiBPLException('parameter cannot be null', WigiiBPLException::INVALID_ARGUMENT);
				
			$elementId = $parameter->getValue('elementId');
			if(is_null($elementId)) throw new WigiiBPLException('elementId cannot be null', WigiiBPLException::INVALID_PARAMETER);
			
			$wigiiNamespace = $parameter->getValue('wigiiNamespace');
			// if no wigiiNamespace, then takes the Principal current namespace
			if(is_null($wigiiNamespace)) $wigiiNamespace = $principal->getWigiiNamespace();			
			if(!($wigiiNamespace instanceof WigiiNamespace)) $wigiiNamespace = $this->getWigiiNamespaceAdminService()->getWigiiNamespace($principal, $wigiiNamespace);

			// adapts principal namespace to element namespace
			$crtNamespace = $principal->getWigiiNamespace();
			$changedNamespace = $principal->bindToWigiiNamespace($wigiiNamespace);
			
			$module = $parameter->getValue('module');
			if(is_null($module)) throw new WigiiBPLException('module cannot be null', WigiiBPLException::INVALID_PARAMETER);
			if(!($module instanceof Module)) $module = $this->getModuleAdminService()->getModule($principal, $module);
						
			// creates empty element
			$element = $this->getWigiiExecutor()->createElementForForm($principal, $module, $elementId);
			// fills element
			$returnValue = $this->getElementService()->fillElement($principal, $element);	

			if($changedNamespace) $principal->bindToWigiiNamespace($crtNamespace);
		}
		catch(Exception $e) {
			if($changedNamespace && isset($crtNamespace)) $principal->bindToWigiiNamespace($crtNamespace);
			$this->executionSink()->publishEndOperationOnError("elementFetch", $e, $principal);
			throw $e;
		}
		$this->executionSink()->publishEndOperation("elementFetch", $principal);
		return $returnValue;
	}	
	
	/**
	 * Fetches an Element into the database given a business unique ID.
	 * @param Principal $principal authenticated user executing the Wigii business process
	 * @param Object $caller the object calling the Wigii business process.
	 * @param WigiiBPLParameter $parameter the elementFetchByKey business process needs the following parameters to run :
	 * - keyField: String. The name of the Field in the Element acting as a business key,
	 * - keyValue: String. The value of the business key used to select the element.
	 * - groupId: Int. The search space in which to look for the element.
	 * - fsl: FieldSelectorList. Optional FieldSelectorList instance specifying the Fields to be fetched.
	 * - resultCount: Int. Output parameter. Holds the number of matching elements in this search space for this key. 
	 * Normally should be 1, but in case of doubles, can be greater than one.
	 * @param ExecutionSink $executionSink an optional ExecutionSink instance that can be used to log Wigii business process actions.
	 * @throws WigiiBPLException|Exception in case of error
	 * @return ElementP the element filled from the database with Principal rights calculation.
	 */
	public function elementFetchByKey($principal, $caller, $parameter, $executionSink=null) {
		$this->executionSink()->publishStartOperation("elementFetchByKey", $principal);
		$returnValue = null;
		$crtNamespace = null; $hasAdaptiveWigiiNamespace = null;
		try {
			if(is_null($principal)) throw new WigiiBPLException('principal cannot be null', WigiiBPLException::INVALID_ARGUMENT);
			if(is_null($parameter)) throw new WigiiBPLException('parameter cannot be null', WigiiBPLException::INVALID_ARGUMENT);
				
			$keyField = $parameter->getValue('keyField');
			if(empty($keyField)) throw new WigiiBPLException('keyField cannot be null', WigiiBPLException::INVALID_PARAMETER);
			$keyValue = $parameter->getValue('keyValue');
			if(empty($keyValue)) throw new WigiiBPLException('keyValuecannot be null', WigiiBPLException::INVALID_PARAMETER);
			$groupId = $parameter->getValue('groupId');
			if(empty($groupId)) throw new WigiiBPLException('groupId cannot be null', WigiiBPLException::INVALID_PARAMETER);
			$fsl = $parameter->getValue('fsl');
			
			$hasAdaptiveWigiiNamespace = $principal->hasAdaptiveWigiiNamespace();
			if(!$hasAdaptiveWigiiNamespace) {
				$crtNamespace = $principal->getWigiiNamespace();
				$principal->setAdaptiveWigiiNamespace(true);
			}
			$lf = lf($fsl,lxEq(fs($keyField),$keyValue),null,1,1);
			$returnValue = $this->getDataFlowService()->processDumpableObject($principal, elementPList(lxInGR(lxEq(fs('id'),$groupId)),$lf), dfasl(dfas("NullDFA")));
			$parameter->setValue('resultCount', $lf->getTotalNumberOfObjects());
			
			if(isset($crtNamespace)) {
				$principal->bindToWigiiNamespace($crtNamespace);
				if(!$hasAdaptiveWigiiNamespace) $principal->setAdaptiveWigiiNamespace(false);
			}
		}
		catch(Exception $e) {
			if(isset($crtNamespace)) {
				$principal->bindToWigiiNamespace($crtNamespace);
				if(!$hasAdaptiveWigiiNamespace) $principal->setAdaptiveWigiiNamespace(false);
			}
			$this->executionSink()->publishEndOperationOnError("elementFetchByKey", $e, $principal);
			throw $e;
		}
		$this->executionSink()->publishEndOperation("elementFetchByKey", $principal);
		return $returnValue;
	}
	
	/**
	 * Fills an array with all WigiiNamespaces and Groups containing the given element 
	 * @param Principal $principal authenticated user executing the Wigii business process
	 * @param Object $caller the object calling the Wigii business process.
	 * @param WigiiBPLParameter $parameter the elementFetch business process needs the following parameters to run :
	 * - elementId: Int. The ID of the element to fetch,
	 * - module: Module|String. The Module of the Element (if known).
	 * @param ExecutionSink $executionSink an optional ExecutionSink instance that can be used to log Wigii business process actions.
	 * @throws WigiiBPLException|Exception in case of error
	 * @return Array an array of the form [WigiiNamespaceName=>array[group ids]], null if element does not exist in the database.
	 */
	public function elementGetWigiiNamespaceArray($principal, $caller, $parameter, $executionSink=null) {
		$this->executionSink()->publishStartOperation("elementGetWigiiNamespaceArray", $principal);
		$returnValue = null;
		try {
			if(is_null($principal)) throw new WigiiBPLException('principal cannot be null', WigiiBPLException::INVALID_ARGUMENT);
			if(is_null($parameter)) throw new WigiiBPLException('parameter cannot be null', WigiiBPLException::INVALID_ARGUMENT);
		
			$rootP = $this->getRootPrincipal();
			
			$elementId = $parameter->getValue('elementId');
			if(is_null($elementId)) throw new WigiiBPLException('elementId cannot be null', WigiiBPLException::INVALID_PARAMETER);
			$module = $parameter->getValue('module');
			if(isset($module) && !($module instanceof Module)) $this->getModuleAdminService()->getModule($rootP, $module);	
			
			// fetches element for which to fetch the containing groups
			// checks module and if sub-element
			$element = Element::createInstance(null, FieldListArrayImpl::createInstance(), WigiiBagBaseImpl::createInstance(), array('id'=>$elementId));
			$this->getElementService()->fillElement($rootP, $element, fsl(fs_e('module'),fs_e('id_element_parent')));
			
			// if sub element, then retrieves root element
			if($element->isSubElement()) {
				$rootLs = $element->getSubElementPathFromRoot()->getFirstLinkSelector();
				$element = Element::createInstance($this->getModuleAdminService()->getModule($rootP, $rootLs->getModuleName()), null, null, array('id'=>$rootLs->getOwnerElementId()));
			}

			// gets all groups containing element
			$groupPList = GroupPListArrayImpl::createInstance();
			if($this->getElementService()->getAllGroupsContainingElement($rootP, $element, $groupPList)>0) {
				$returnValue=array();
				foreach($groupPList->getListIterator() as $groupP) {
					$group = $groupP->getDbEntity();
					$wigiiNamespaceName = $group->getWigiiNamespace()->getWigiiNamespaceName();
					$groups = $returnValue[$wigiiNamespaceName];
					if(!isset($groups)) $groups=array();
					$groups[$group->getId()] = $group->getId();
					$returnValue[$wigiiNamespaceName] = $groups;
				}
			}
		}
		catch(Exception $e) {
			if($changedNamespace && isset($crtNamespace)) $principal->bindToWigiiNamespace($crtNamespace);
			$this->executionSink()->publishEndOperationOnError("elementGetWigiiNamespaceArray", $e, $principal);
			throw $e;
		}
		$this->executionSink()->publishEndOperation("elementGetWigiiNamespaceArray", $principal);
		return $returnValue;
	}
	
	/**
	 * Adds some dynamic attributes to an existing Element. The added attributes are all Mutable and Replaceable.
	 * If a DynamicAttribute already exists under the given name, it will be replaced. An exception could occur if the old dynamic attribute is not replaceable.
	 * @param Principal $principal authenticated user executing the Wigii business process
	 * @param Object $caller the object calling the Wigii business process.
	 * @param WigiiBPLParameter $parameter the elementSetDynamicAttributes business process needs the following parameters to run :
	 * - element: Element. The element to which to add Dynamic Attributes
	 * - attributes: WigiiBPLParameter. The map of dynamic attributes to add to the element. Key is the dynamic attribute name, Value is the value of the dynamic attribute.
	 * @param ExecutionSink $executionSink an optional ExecutionSink instance that can be used to log Wigii business process actions.
	 * @throws WigiiBPLException|Exception in case of error
	 * @return Element the element to which dynamic attributes where added
	 */
	public function elementSetDynamicAttributes($principal, $caller, $parameter, $executionSink=null) {
		if(is_null($principal)) throw new WigiiBPLException('principal cannot be null', WigiiBPLException::INVALID_ARGUMENT);
		if(is_null($parameter)) throw new WigiiBPLException('parameter cannot be null', WigiiBPLException::INVALID_ARGUMENT);
			
		$element = $parameter->getValue('element');
		if(!($element instanceof Element)) throw new WigiiBPLException('element should be a non null instance of Element', WigiiBPLException::INVALID_PARAMETER);
		$attributes = $parameter->getValue('attributes');
		if(!($attributes instanceof WigiiBPLParameter)) throw new WigiiBPLException('attributes should be a non null instance of WigiiBPLParameter', WigiiBPLException::INVALID_PARAMETER);
		
		// adds the dynamic attributes to the element
		if(!$attributes->isEmpty()) {
			foreach($attributes->getIterator() as $name=>$value) {
				$da = $element->getDynamicAttribute($name);
				if(!isset($da)) {
					$da = ElementDynAttrMutableValueImpl::createInstance();
					$element->setDynamicAttribute($name, $da);
				}
				$da->setValue($value);
			}
		}
		return $element;
	}
	
	// Object builders
	
	/**
	 * Returns a ConfigSelector for the given group. 
	 * Checks if there is a group config in the group hierarchy and returns the closest one.
	 * If no group config is available, then returns a ConfigSelector centered on the given group.
	 * @param Principal $principal authenticated user performing the operation
	 * @param int|Group|GroupP $group group ID or group instance or GroupP instance for which to create a ConfigSelector.
	 * @return ConfigSelector
	 */
	public function buildConfigSelectorForGroup($principal, $group) {
		if(!isset($group)) throw new WigiiBPLException('group cannot be null, should be a valid group ID or a group instance', WigiiBPLException::INVALID_ARGUMENT);
		if(!is_object($group)) {
			$group = $this->getGroupAdminService()->getGroupWithoutDetail($principal, $group);
		}
		$cc = $this->getConfigService();
		if($cc instanceof ConfigurationContextImpl) {
			$parentGroup = $cc->isConfigGroupAvailableForGroup($principal, $group);
			if(is_object($parentGroup)) $group = $parentGroup;
		}
		return ConfigSelector::createInstanceForGroupConfig(lxEq(fs('id'),$group->getId()));
	}
	
	/**
	 * Returns a GroupBasedWigiiApiClient centered on the given ConfigSelector.
	 * @param Principal $principal authenticated user performing the operation. 
	 * If principal has adaptive WigiiNamespace then binds to specified WigiiNamespace if needed.
	 * @param ConfigSelector $configSelector a ConfigSelector specifying the Group or WigiiNamespace/Module which should be used to select the configuration
	 * @return GroupBasedWigiiApiClient centered on the given Group.
	 */
	public function buildGroupBasedWigiiApiClient($principal, $configSelector) {
		if(!isset($principal)) throw new WigiiBPLException('principal is mandatory', WigiiBPLException::INVALID_ARGUMENT);
		
		$returnValue = null;
		if(isset($configSelector)) {
			// a wigiiNamespace has been specified --> adapts the Principal if needed
			$confWigiiNamespace = $configSelector->getWigiiNamespaceName();
			if(isset($confWigiiNamespace)) $confWigiiNamespace = $this->getWigiiNamespaceAdminService()->getWigiiNamespace($principal, $confWigiiNamespace);
			if(isset($confWigiiNamespace) && $principal->hasAdaptiveWigiiNamespace()) {
				$principal->bindToWigiiNamespace($confWigiiNamespace);
			}
			// a groupLogExp has been specified --> creates a GroupBasedWigiiApiClient centered on theses groups
			$groupExp = $configSelector->getGroupLogExp();
			if(isset($groupExp)) {
				$returnValue = ServiceProvider::getGroupBasedWigiiApiClient($principal, $groupExp);
				$groupList = $returnValue->getGroupList();
				// gets wigiiNamespace
				$initialized = false; $firstWigiiNamespace = null;
				$oneWigiiNamespace = true;
				foreach($groupList->getListIterator() as $group)
				{
					$wigiiNamespace = $group->getWigiiNamespace();
					if($initialized)
					{
						// checks wigiiNamespace unicity
						if($wigiiNamespace !== $firstWigiiNamespace) $oneWigiiNamespace = false;
					}
					else
					{
						$firstWigiiNamespace = $wigiiNamespace;
						$initialized = true;
					}
				}
				// adapts wigii namespace if needed
				if(is_null($confWigiiNamespace) && $oneWigiiNamespace && $principal->hasAdaptiveWigiiNamespace()) {
					$principal->bindToWigiiNamespace($firstWigiiNamespace);
				}
			}
		}
		
		if(!isset($returnValue)) $returnValue = ServiceProvider::getGroupBasedWigiiApiClient($principal, null);
		return $returnValue;
	}
	
	/**
	 * Builds a copy of a given Element which can be dumped into a DataFlow.
	 * @param Element $element element on which to do an in memory copy.
	 * @param ConfigSelector $configSelector an optional ConfigSelector used to choose the right configuration of the copied Element.
	 * If ConfigSelector points to a different module than the source element, then a matching is done of the fieldName and DataType.
	 * @return DataFlowDumpable
	 */
	public function buildCopyElementDataFlowConnector($element, $configSelector=null) {
		$returnValue = ServiceProvider::getExclusiveAccessObject('WigiiBPLCopyElementDataFlowConnector');
		$returnValue->setElement($element);
		$returnValue->setConfigSelector($configSelector);
		$returnValue->setWigiiExecutor($this->getWigiiExecutor());
		if($element instanceof FuncExpParameter) $element->registerSetterMethod('setElement', $returnValue);
		if($configSelector instanceof FuncExpParameter) $configSelector->registerSetterMethod('setConfigSelector', $returnValue);
		return $returnValue;
	}
	
	/**
	 * Creates a new empty Element, with default values initialized, ready to be displayed as a form.
	 * @param Principal $principal current principal
	 * @param ConfigSelector $configSelector the ConfigSelector specifying the configuration of the new Element.
	 * @return Element the instanciated Element (not yet saved into the database).
	 */
	public function buildNewElement($principal, $configSelector) {
		WigiiBPLException::throwNotImplemented();
	}
	
	/**
	 * Gets a matrix out of the Record as a StdClass instance
	 * @param Record $record a record containing some fields in a matrix style col1_1, col2_1, col3_1, ..., col1_n, col2_n, col3_n
	 * @param Array $columns an array with the names of the column to extract for example col1_, col2_, col3_
	 * @param Int $fromRow The start index from which to extract the matrix rows.
	 * @param Int $toRow The stop index to which to extract the matrix rows.
	 * @return StdClass a StdClass instance of the form {rows : array(StdClass as row), index : null, errors : null}
	 * @example With a module configuration containing the fields ProjectCode_1, Location_1, ProjectCode_2, Location_2, ProjectCode_3, Location_3 and data
	 * ProjectCode_1 = P1, Location_1 = L1,
	 * ProjectCode_2 = P2, Location_2 = L2,
	 * ProjectCode_3 = P3, Location_3 = L3
	 * The call of buildMatrixFromRecord(record, array("ProjectCode_", "Location_"),1,3)
	 * will return an stdClass instance of the form 
	 * {rows => array(
	 * 		{ProjectCode_ => {value=>P1, other data type subfields...}, Location_ => {value => L1, other data type subfields...}}
	 * 		{ProjectCode_ => {value=>P2, other data type subfields...}, Location_ => {value => L2, other data type subfields...}}
	 * 		{ProjectCode_ => {value=>P3, other data type subfields...}, Location_ => {value => L3, other data type subfields...}}
	 * 	), 
	 * 	index => null, 
	 * 	errors => null
	 * }
	 */
	public function buildMatrixFromRecord($record,$columns,$fromRow,$toRow) {
		if(!isset($record)) throw new WigiiBPLException('record cannot be null', WigiiBPLException::INVALID_ARGUMENT);
		$returnValue = array(
				'rows'=>$record->exportMatrix($columns, $fromRow, $toRow), 
				'index'=>null, 
				'errors'=>null
		);
		$returnValue = (object)$returnValue;
		return $returnValue;
	}
	
	// Object arrays builders
	
	/**
	 * Builds an array of Modules given an input
	 * @param Principal $principal authenticated user executing the function
	 * @param Array|String|Module $input the input used to build the array of modules. 
	 * Can be an Array(moduleName=>Module) or an Array(moduleName) or the module name or one module instance.
	 * @return Array returns an array of the form Array(moduleName=>Module) or an empty array if input is empty.
	 * @throws WigiiBPLException|Exception in case of error.
	 */
	public function buildModuleArray($principal, $input) {
		$returnValue = array();
		if(!empty($input)) {
			$mAS = $this->getModuleAdminService();
			if(is_array($input)) {
				foreach($input as $i) {
					if($i instanceof Module) $module = $mAS->getModule($principal, $i->getModuleName());
					else $module = $mAS->getModule($principal, $i);
					if(isset($module)) $returnValue[$module->getModuleName()] = $module;
				}
			}
			elseif($input instanceof Module) {
				$module = $mAS->getModule($principal, $input->getModuleName());
				if(isset($module)) $returnValue[$module->getModuleName()] = $module;
			}
			else {
				$module = $mAS->getModule($principal, $input);
				if(isset($module)) $returnValue[$module->getModuleName()] = $module;
			}
		}
		return $returnValue;
	}
	
	/**
	 * Builds an array of WigiiNamespaces given an input
	 * @param Principal $principal authenticated user executing the function
	 * @param Array|String|WigiiNamespace $input the input used to build the array of WigiiNamespaces.
	 * Can be an Array(wigiiNamespaceName=>WigiiNamespace) or an Array(wigiiNamespaceName) or the WigiiNamespace name or one WigiiNamespace instance.
	 * @return Array returns an array of the form Array(wigiiNamespaceName=>WigiiNamespace) or an empty array if input is empty.
	 * @throws WigiiBPLException|Exception in case of error.
	 */
	public function buildWigiiNamespaceArray($principal, $input) {
		$returnValue = array();
		if(!empty($input)) {
			$nAS = $this->getWigiiNamespaceAdminService();
			if(is_array($input)) {
				foreach($input as $i) {
					if($i instanceof WigiiNamespace) $wigiiNamespace = $nAS->getWigiiNamespace($principal, $i->getWigiiNamespaceName());
					else $wigiiNamespace = $nAS->getWigiiNamespace($principal, $i);
					if(isset($wigiiNamespace)) $returnValue[$wigiiNamespace->getWigiiNamespaceName()] = $wigiiNamespace;
				}
			}
			elseif($input instanceof WigiiNamespace) {
				$wigiiNamespace = $nAS->getWigiiNamespace($principal, $input->getWigiiNamespaceName());
				if(isset($wigiiNamespace)) $returnValue[$wigiiNamespace->getWigiiNamespaceName()] = $wigiiNamespace;
			}
			else {
				$wigiiNamespace = $nAS->getWigiiNamespace($principal, $input);
				if(isset($wigiiNamespace)) $returnValue[$wigiiNamespace->getWigiiNamespaceName()] = $wigiiNamespace;
			}
		}
		return $returnValue;
	}
}
/**
 * A WigiiExecutor stub to enable the reuse of stateless methods and the getConfigurationContext method.
 * Created by CWE on 16.09.2015
 */
class WigiiBPLWigiiExecutorStub extends WigiiCoreExecutor {
	/**
	 * @var WigiiBPL
	 */
	private $wigiiBPL;
	
	// Object lifecycle
	
	/**
	 * @param WigiiBPL $wigiiBPL a reference to the current WigiiBPL object
	 */
	public static function createInstance($wigiiBPL) {
		$returnValue = new self();
		$returnValue->wigiiBPL = $wigiiBPL;
		return $returnValue;
	}
	
	// WigiiExecutor stub
	
	public function getConfigurationContext() {
		return $this->wigiiBPL->getConfigService();
	}
}
/**
 * Copies an existing Element and pushes it into a DataFlow
 * Created by CWE on 17.09.2015
 */
class WigiiBPLCopyElementDataFlowConnector implements DataFlowDumpable {
	private $_debugLogger;
	private $lockedForUse = true;
	
	// Object lifecycle
	
	public function reset() {
		$this->freeMemory();
		$this->lockedForUse = true;
	}
	public function freeMemory() {
		unset($this->wigiiExecutor);
		unset($this->element);
		unset($this->configSelector);
		$this->lockedForUse = false;
	}
	
	public function isLockedForUse() {
		return $this->lockedForUse;
	}
	
	public static function createInstance($wigiiExecutor, $element, $configSelector=null) {
		$returnValue = new self();
		$returnValue->reset();
		$returnValue->setWigiiExecutor($wigiiExecutor);
		$returnValue->setElement($element);
		if(isset($configSelector)) $returnValue->setConfigSelector($configSelector);
		return $returnValue;
	}	
	
	// Dependency injection
	
	private function debugLogger()
	{
		if(!isset($this->_debugLogger))
		{
			$this->_debugLogger = DebugLogger::getInstance("WigiiBPLCopyElementDataFlowConnector");
		}
		return $this->_debugLogger;
	}
	
	// Configuration
	
	private $wigiiExecutor;
	public function setWigiiExecutor($wigiiExecutor) {
		$this->wigiiExecutor = $wigiiExecutor;
	}
	
	private $element;
	public function setElement($element) {
		$this->element = $element;
	}
	
	private $configSelector;
	public function setConfigSelector($configSelector) {
		$this->configSelector = $configSelector;
	}	
	
	// DataFlowDumpable implementation
	
	public function dumpIntoDataFlow($dataFlowService, $dataFlowContext) {		
		if(!isset($this->wigiiExecutor)) throw new DataFlowServiceException("wigiiExecutor must be set.", DataFlowServiceException::CONFIGURATION_ERROR);
		if(!isset($this->element)) return;
		
		$principal = $dataFlowContext->getPrincipal();
	
		// create WigiiAPIClient centered on config selector
		$apiClient = ServiceProvider::getWigiiBPL()->buildGroupBasedWigiiApiClient($principal, $this->configSelector);
		$configS = $apiClient->getConfigService();
		$exec = ServiceProvider::getExecutionService();
		
		// module is module defined by ConfigSelector or module of source Element
		$module = $apiClient->getModule();
		if(!isset($module) && isset($this->configSelector) && $this->configSelector->getModuleName()!=Module::EMPTY_MODULE_NAME) {
			$module = ServiceProvider::getModuleAdminService()->getModule($principal, $this->configSelector->getModuleName());
		}
		if(!isset($module)) $module = $this->element->getModule();
		
		// creates new Element
		$newElement = $this->wigiiExecutor->createElementForForm($principal, $module, null);
		
		// sets configuration
		$newFL = $newElement->getFieldList();
		$newFormBag = $newElement->getWigiiBag();
		//$this->debugLogger()->write("fills new element field list");
		$configS->getFields($principal, $module, null, $newFL);
		//if($newFL->isEmpty()) throw new DataFlowServiceException("new field list cannot be null", DataFlowServiceException::INVALID_STATE);
		// copies Element content to new Element
		$fsl = FieldSelectorListArrayImpl::createInstance(true,false);
		$fl = $this->element->getFieldList();
		if(isset($fl) && !$fl->isEmpty()) {
			$flWithSubfields = FieldWithSelectedSubfieldsListArrayImpl::createInstance($newFL);
			//$this->debugLogger()->logBeginOperation("copy element into new element");
			foreach($flWithSubfields->getListIterator() as $fieldWithSubfields) {
				$field = $fieldWithSubfields->getField();
				$dt = $field->getDataType();
				$fieldName = $field->getFieldName();
				// checks that fieldName exists in source element
				$srcField = $fl->doesFieldExist($fieldName);							
				if(isset($srcField) && isset($dt)) {
					$srcDt = $srcField->getDataType();
					// checks DataType matching
					if(!isset($srcDt) || $srcDt->getDataTypeName() != $dt->getDataTypeName()) throw new WigiiBPLException("Data type of field '$fieldName' differs between source and copy. Source config has '".$srcDt->getDataTypeName()."', config of copied element has '".$dt->getDataTypeName()."'", WigiiBPLException::CONFIGURATION_ERROR);
					// do not copy content of hidden fields with clearOnCopy=1
					$srcXml = $srcField->getXml();
					if(!($srcXml['hidden']=='1' && $srcXml['clearOnCopy']=='1')) {
						// copies content to new Element
						foreach($fieldWithSubfields->getSelectedSubfieldsIterator() as $subFieldName) {
							$value = $this->element->getFieldValue($fieldName, $subFieldName);
							$this->debugLogger()->write($fieldName.".".$subFieldName."=".(is_object($value)?get_class($value):$value));
							$newElement->setFieldValue($value, $fieldName, $subFieldName);
							// records FieldSelector in fsl
							$fsl->addFieldSelector($fieldName, $subFieldName);
						}
					}
				}
			}
			//$this->debugLogger()->logEndOperation("copy element into new element");
		}
		
		// initializes Element state on copy
		$elementPolicyEvaluator = $this->wigiiExecutor->getElementPolicyEvaluator($principal, $module);
		if(isset($elementPolicyEvaluator)) $elementPolicyEvaluator->initializeElementStateOnCopy($principal, $newElement);
			
		// empties clearOnCopy fields, empties files, copies html linked images, initialize default values.
		$form = CopyElementFormExecutor::createInstance($this->wigiiExecutor, $newElement, null, null);
		$form->initializeDefaultValues($principal, $exec);
		
		// evaluates calculated fields
		$evaluatorClassName = (string)$configS->getParameter($principal, $module, "Element_evaluator");
		$elementEvaluator = ServiceProvider::getElementEvaluator($principal, $evaluatorClassName);
		$elementEvaluator->setFormExecutor($form);
		$elementEvaluator->evaluateElement($principal, $newElement);
		if($newFormBag->hasErrors()) {
			throw new WigiiBPLException("Errors while evaluating calculated fields.\n".$newFormBag->getErrorsAsString(), WigiiBPLException::INVALID_STATE);
		}
			
		// updates Element state on save
		if(isset($elementPolicyEvaluator)) {
			$elementPolicyEvaluator->setFormExecutor($form);
			$elementPolicyEvaluator->setExecutionService($exec);
			$elementPolicyEvaluator->updateElementStateOnSave($principal, $newElement);
			if($newFormBag->hasErrors()) {
				throw new WigiiBPLException("Errors while updating element state after copy.\n".$newFormBag->getErrorsAsString(), WigiiBPLException::INVALID_STATE);
			}
		}
		
		// pushes the new element into the dataflow
		$dataFlowContext->setAttribute('GroupBasedWigiiApiClient', $apiClient, true);
		$dataFlowContext->setAttribute('FieldSelectorList', $fsl, true);
		//$this->debugLogger()->write("copied element:".json_encode($newElement->toStdClass()));
		$dataFlowService->processDataChunk($newElement, $dataFlowContext);		
	}
}
