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

/** 
 * Wigii Business Process Library
 * Created by CWE on 19.11.2014
 * Modified by Medair (CWE) on 15.12.2016 to protect against Cross Site Scripting
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
	
	private $emailService;
	public function getEmailService() {
	    if (!isset ($this->emailService)) {
	        $this->emailService = TechnicalServiceProvider :: getEmailService();
	    }
	    return $this->emailService;
	}
	
	public function getNotificationService() {
    	return $this->getWigiiExecutor()->getNotificationService();
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
	
	private $authoSStamp;
	public function setAuthorizationServiceStamp($stamp) {
	    $this->authoSStamp = $stamp;
	}
	protected function consumeAuthorizationServiceStamp() {
	    $returnValue = $this->authoSStamp;
	    $this->authoSStamp=null;
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
	 * Sets an UGR on a given group for a given user Id.
	 * @param Principal $principal authenticated user executing the Wigii business process
	 * @param Object $caller the object calling the Wigii business process.
	 * @param WigiiBPLParameter $parameter A WigiiBPLParameter instance with the following parameters :
	 * - group: Int|Group|GroupP. The group Id or group object for which to set the UGR
	 * - user: Int|User|UserP. The user Id or user object for which to set the UGR
	 * - right: String. User access rights on group. One of x=group admin, w=write element, s=share element, r=read element.
	 * - allowDowngrade: Boolean. If true, then allows to down grade existing rights on this folder. Else higher rights are kept. Default to false.
	 * @param ExecutionSink $executionSink an optional ExecutionSink instance that can be used to log Wigii business process actions.
	 * @throws WigiiBPLException|Exception in case of error
	 * @return Int 1 if UGR has been inserted or modified in DB, 0 if UGR already existed and didn't need to be modified.
	 */
	public function adminSetUserGroupRight($principal, $caller, $parameter, $executionSink=null) {
	    $this->executionSink()->publishStartOperation("adminSetUserGroupRight", $principal);
	    $returnValue = null;
	    try {
	        if(is_null($principal)) throw new WigiiBPLException('principal cannot be null', WigiiBPLException::INVALID_ARGUMENT);
	        if(is_null($parameter)) throw new WigiiBPLException('parameter cannot be null', WigiiBPLException::INVALID_ARGUMENT);
	        // checks parameters
	        $groupId = $parameter->getValue('group');
	        if(is_object($groupId)) $groupId = $groupId->getId();
	        if($groupId == null) throw new WigiiBPLException('group cannot be null', WigiiBPLException::INVALID_ARGUMENT);	        
	        $userId = $parameter->getValue('user');
	        if(is_object($userId)) $userId = $userId->getId();
	        if($userId == null) throw new WigiiBPLException('user cannot be null', WigiiBPLException::INVALID_ARGUMENT);
	        $right = $parameter->getValue('right');
	        switch($right) {
	            case 'x':
	            case 'w':
	            case 's':
	            case 'r':
	               break;
	            default: throw new WigiiBPLException('right should be one of x,w,s,r', WigiiBPLException::INVALID_ARGUMENT);
	        }
	        $allowDowngrade = ($parameter->getValue('allowDowngrade')==true);
	        
	        // 1. fetches original ugr
	        $origUgr = $this->getUGR($principal, $groupId, $userId);
	        // 2. sets UGR in database if did not exist or if rights are higher or if allowDowngrade and rights are lower. 
	        if(!isset($origUgr) || 
	           $right > $origUgr->getLetter() ||
	           $allowDowngrade && $right < $origUgr->getLetter() ) {
	               
	           $ugr = UGR::createInstance($groupId, $userId);
	           $ugr->setRightsFromLetter($right);
	           ServiceProvider::getGroupAdminService()->setUserRight($principal, $ugr);
	           ServiceProvider::getUserAdminService()->matchModuleAccessOnRights($principal, $userId);
	           $returnValue = 1;
	        }
	        // else no change
	        else $returnValue = 0;
	    }
	    catch(Exception $e) {
	        $this->executionSink()->publishEndOperationOnError("adminSetUserGroupRight", $e, $principal);
	        throw $e;
	    }
	    $this->executionSink()->publishEndOperation("adminSetUserGroupRight", $principal);
	    return $returnValue;
	}
	
	/**
	 * Returns the current listContext
	 * @param Principal $principal authenticated user executing the Wigii business process
	 * @return ListContext
	 */
	public function getListContext($principal){
		$exec = ServiceProvider::getExecutionService();
		return $this->getListContext($principal, $exec->getCrtWigiiNamespace(), $exec->getCrtModule(), "elementList");
	}
	
	/**
	 * Checks if principal is a SuperAdmin (has at least one role with WigiiNamespaceCreator to true)
	 * @param Principal $principal
	 * @return boolean true if Principal is a SuperAdmin else false
	 */
	public function adminIsPrincipalSuperAdmin($principal) {
	    if(!isset($principal)) throw new WigiiBPLException('principal cannot be null', WigiiBPLException::INVALID_ARGUMENT);

	    // 1. root principal or public principal are not super admin roles
	    if($principal->getAttachedUser() == null) return false;
	    
	    // 2. Ensures to have Principal role list loaded
	    if(!($principal->getRoleListener() instanceof UserListForNavigationBarImpl)) {
	        $defaultWigiiNamespace = (string)$this->getConfigService()->getParameter($principal, null, "defaultWigiiNamespace");
	        if(!$defaultWigiiNamespace) $defaultWigiiNamespace = $principal->getRealWigiiNamespace()->getWigiiNamespaceUrl();
	        $principal->refetchAllRoles($this->getUserAdminService()->getListFilterForNavigationBar(), UserListForNavigationBarImpl::createInstance($defaultWigiiNamespace));
	    }
	    
	    // 3. Checks existence of SuperAdmin role into Principal role list
	    foreach($principal->getRoleListener()->getAdminRoleIds() as $adminId) {
	        $adminRole = $principal->getRoleListener()->getUser($adminId);
	        // finds first super admin role
	        if($adminRole->getDetail()->isWigiiNamespaceCreator()) {
	            return true;
	        }
	    }
	    return false;
	}
	
	/**
	 * Checks if principal is a config editor in the current namespace
	 * @param Principal $principal
	 * @return boolean true if Principal can edit config else false
	 */
	public function adminIsPrincipalConfigEditor($principal) {
	    if(!isset($principal)) throw new WigiiBPLException('principal cannot be null', WigiiBPLException::INVALID_ARGUMENT);
	    
	    // 1. root principal or public principal are not config editors
	    if($principal->getAttachedUser() == null) return false;
	    
	    // 2. Ensures to have Principal role list loaded
	    if(!($principal->getRoleListener() instanceof UserListForNavigationBarImpl)) {
	        $defaultWigiiNamespace = (string)$this->getConfigService()->getParameter($principal, null, "defaultWigiiNamespace");
	        if(!$defaultWigiiNamespace) $defaultWigiiNamespace = $principal->getRealWigiiNamespace()->getWigiiNamespaceUrl();
	        $principal->refetchAllRoles($this->getUserAdminService()->getListFilterForNavigationBar(), UserListForNavigationBarImpl::createInstance($defaultWigiiNamespace));
	    }
	    
	    // 3. Checks existence of SuperAdmin role or Module Editor role (in same namespace) into Principal role list
	    $wigiiNamespace = $principal->getWigiiNamespace()->getWigiiNamespaceName();
	    foreach($principal->getRoleListener()->getAdminRoleIds() as $adminId) {
	        $adminRole = $principal->getRoleListener()->getUser($adminId);
	        // finds first super admin role
	        if($adminRole->getDetail()->isWigiiNamespaceCreator()) {
	            return true;
	        }
	        // checks if same namespace and module editor
	        if($adminRole->getWigiiNamespace()->getWigiiNamespaceName()==$wigiiNamespace && $adminRole->getDetail()->isModuleEditor()) {
	            return true;
	        }
	    }
	    return false;
	}
	
	/**
	 * print an element using a template and return the html
	 * @param Principal $principal authenticated user executing the Wigii business process
	 * @param Object $caller the object calling the Wigii business process.
	 * @param WigiiBPLParameter $parameter the elementPrintWithTemplate business process needs the following parameters to run :
	 * - element: Element. The filled element to be printed.
	 * - template: String. Optional, the name of the template in the Print activity. If not defined then takes Print->template
	 * @param ExecutionSink $executionSink an optional ExecutionSink instance that can be used to log Wigii business process actions.
	 * @throws WigiiBPLException|Exception in case of error
	 * @return String : html
	 */
	public function elementPrintWithTemplate($principal, $caller, $parameter, $executionSink=null) {
		$this->executionSink()->publishStartOperation("elementPrint", $principal);
		$configS = $this->getConfigService();
		$returnValue = null;
		try {
			if(is_null($principal)) throw new WigiiBPLException('principal cannot be null', WigiiBPLException::INVALID_ARGUMENT);
			if(is_null($parameter)) throw new WigiiBPLException('parameter cannot be null', WigiiBPLException::INVALID_ARGUMENT);
			
			$element = $parameter->getValue('element');
			if(is_null($element)) throw new WigiiBPLException('element cannot be null', WigiiBPLException::INVALID_PARAMETER);
			$template = $parameter->getValue('template');
			
			//wraps the element as read only
			$elementP = ElementP::createInstance($element);
			$elementP->setRights(PrincipalRights::createInstance());
			
			$form = PrintElementFormExecutor :: createInstance($this->getWigiiExecutor(), $element, $elementP, "detailElement_form", null);
			$form->setIsForPrint(true);
			if($this->getWigiiExecutor()->isWorkzoneViewDocked()) $form->setCorrectionWidth(0);
			else $form->setCorrectionWidth(43);
			$form->setLabelWidth(0 + $configS->getParameter($principal, $element->getModule(), "elementLabelWidth"));
			$form->setTotalWidth(0 + $configS->getParameter($principal, $element->getModule(), "elementTotalWidth"));
			
			//buffer template and return the code
			ob_start();
			$form->printWithTemplate($principal, ServiceProvider::getExecutionService(), $template, $parameter);
			$returnValue = ob_get_clean();
			
		}
		catch(Exception $e) {
			$this->executionSink()->publishEndOperationOnError("elementPrint", $e, $principal);
			throw $e;
		}
		$this->executionSink()->publishEndOperation("elementPrint", $principal);
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
	 * Updates element sharing.
	 * @param Principal $principal authenticated user executing the Wigii business process
	 * @param Object $caller the object calling the Wigii business process.
	 * @param WigiiBPLParameter $parameter the elementUpdateSharing business process needs the following parameters to run :
	 * - element: Element. The filled element for which to update the sharing.
	 * - newGroupIds: Array. Array of group IDs in which to share the element. 
	 *   If not given, calls the method getLinkedIdGroupInRecord on the element to determine in which group to share the element.
	 *   If sharing groups are determined based on element values, then movePriority is also considered and 
	 *   if appropriate, the element will be moved in corresponding folder instead of shared.
	 * - oldGroupIds: Array. An array with the existing/deprecated groups from which to remove the sharing. 
	 *   If a group ID is present in newGroupIds and oldGroupIds then sharing will remain active.
	 * - wigiiEventsSubscriber: Boolean|MultiplexedEvent|WigiiEvents. If an instance of MultiplexedEvent or WigiiEvents is given, 
	 *   then Wigii Share events are triggered and passed trough the given subscriber,
	 *   if explicit boolean false is given, then no share events are triggered,
	 *   by default share events are triggered and passed to the standard Wigii events dispatcher.
	 * - refreshGUI: Boolean. If true, then Wigii web user interface is asked to clear its group caches when sharing of element changes.
	 *   If explicitely set to false, then nothing is sent to Wigii GUI. By default, GUI is refreshed.
	 * @param ExecutionSink $executionSink an optional ExecutionSink instance that can be used to log Wigii business process actions.
	 * @throws WigiiBPLException|Exception in case of error
	 */
	public function elementUpdateSharing($principal, $caller, $parameter, $executionSink=null) {
		$this->executionSink()->publishStartOperation("elementUpdateSharing", $principal);
		try {
			if(is_null($principal)) throw new WigiiBPLException('principal cannot be null', WigiiBPLException::INVALID_ARGUMENT);
			if(is_null($parameter)) throw new WigiiBPLException('parameter cannot be null', WigiiBPLException::INVALID_ARGUMENT);
			
			$element = $parameter->getValue('element');
			if(is_null($element)) throw new WigiiBPLException('element cannot be null', WigiiBPLException::INVALID_PARAMETER);
			if($element->isSubElement()) throw new WigiiBPLException('subelement cannot be shared into groups',WigiiBPLException::UNSUPPORTED_OPERATION);
			
			$wigiiEventsSubscriber = $parameter->getValue('wigiiEventsSubscriber');
			if(is_null($wigiiEventsSubscriber)) $wigiiEventsSubscriber = $this->getWigiiExecutor()->throwEvent();
			
			$refreshGUI = $parameter->getValue('refreshGUI');
			if(is_null($refreshGUI)) $refreshGUI = true;
			
			$exec = ServiceProvider::getExecutionService();
			$elS = $this->getElementService();
			$configS = $this->getConfigService();
			
			// gets new group ids
			$newGids = $parameter->getValue('newGroupIds');
			$moveId=null;
			if($newGids == null) {
				$gids = ValueListArrayMapper::createInstance ( true, ValueListArrayMapper::Natural_Separators, true );
				$element->getLinkedIdGroupInRecord ( $principal, $gids );
				$newGids = $gids->getListIterator ();
				// new group ids are given from element value, then also consider moveId
				$moveId=true;
			}
			if ($newGids == null) $newGids = array ();
			
			// gets old group ids
			$oldGids = $parameter->getValue('oldGroupIds');
			if ($oldGids == null) $oldGids = array ();			
			
			// only add the ones which was not set before
			if ($newGids && $oldGids) {
				$orgNew = $newGids;
				$newGids = array_diff_key ( $newGids, $oldGids ); 
				$oldGids = array_diff_key ( $oldGids, $orgNew );
			}
						
			// checks if element should be moved instead of shared 
			if($moveId) $moveId = $element->getMoveGroupInRecord();
			if($moveId) {
				$moveId = $this->getWigiiExecutor()->evaluateConfigParameter($principal, $exec, $moveId, $element);
			}
			
			if($wigiiEventsSubscriber) $this->getWigiiExecutor ()->getNotificationService ()->blockNotificationPostingValue ();
			if ($newGids || $moveId) {
				if($moveId){
					$this->debugLogger()->write( "Move element in " . $moveId );
					$moveId = explode(";", $moveId); //if the moveId contains multiple groups then move in multiple
					if(is_array($moveId)) $moveId = array_combine($moveId, $moveId);
					//move in multiple groups is still taking the move piority logic, it is just the one being selected could be a result of multiple groups and not only one
					$elS->moveElement ( $this->getRootPrincipal (), $element->getId (), $moveId );
					if(is_array($moveId)){
						$newGids = array_merge($newGids, $moveId);
					} else {
						$newGids[$moveId] = $moveId;
					}
				} else{
					$this->debugLogger()->write( "Share element in " . implode(",",$newGids) );
					$elS->shareElement ( $this->getRootPrincipal (), $element->getId (), $newGids );
				}
				$gpl = GroupListArrayImpl::createInstance ();
				$this->getGroupAdminService()->getGroupsWithoutDetail ( $principal, $newGids, $gpl );
				foreach ( $gpl->getListIterator () as $group ) {
					// notification here do not follow the skipNotification as it is a sharing notification and not an update notification
					if($wigiiEventsSubscriber) $this->getWigiiExecutor ()->throwEvent ()->shareElement ( PWithElementWithGroup::createInstance ( $principal, $element, $group ) );
					if($refreshGUI) $exec->invalidCache ( $principal, 'moduleView', 'groupSelectorPanel', "groupSelectorPanel/selectGroup/" . $group->getId () );
				}
			}
			if ($oldGids) {
				$this->debugLogger()->write( "Unshare element from " . implode(",",$oldGids) );
				$elS->unshareElement ( $this->getRootPrincipal (), $element->getId (), $oldGids );
				$gpl = GroupListArrayImpl::createInstance ();
				ServiceProvider::getGroupAdminService ()->getGroupsWithoutDetail ( $principal, $oldGids, $gpl );
				$currentGroups = $configS->getGroupPList ( $principal, $exec->getCrtModule () )->getIds ();
				foreach ( $gpl->getListIterator () as $group ) {
					// notification here do not follow the skipNotification as it is a sharing notification and not an update notification
					if($wigiiEventsSubscriber) $wigiiEventsSubscriber->unshareElement ( PWithElementWithGroup::createInstance ( $principal, $element, $group ) );
					if($refreshGUI) $exec->invalidCache ( $principal, 'moduleView', 'groupSelectorPanel', "groupSelectorPanel/selectGroup/" . $group->getId () );
					unset ( $currentGroups [$group->getId ()] );
				}
				if (! $currentGroups) {
					// if the item is moved out of all the current groups then remove it from the list
					if($refreshGUI) $exec->addJsCode ( "removeElementInList('" . $element->getId () . "');" );
				}
			}
			if($wigiiEventsSubscriber) $this->getWigiiExecutor ()->getNotificationService ()->unblockNotificationPostingValue ();
		}
		catch(Exception $e) {
			$this->executionSink()->publishEndOperationOnError("elementUpdateSharing", $e, $principal);
			throw $e;
		}
		$this->executionSink()->publishEndOperation("elementUpdateSharing", $principal);
	}
	
	/**
	 * Evaluates the calculated fields of an Element
	 * @param Principal $principal authenticated user executing the Wigii business process
	 * @param Object $caller the object calling the Wigii business process.
	 * @param WigiiBPLParameter $parameter the elementEvalCalcFields business process needs the following parameters to run :
	 * - element: Element. The filled element containing the Fields to be re-calculated
	 * - fieldName: String|FieldSelector. Optional, one field name for which to recalculate the value.
	 * - fslForUpdate: FieldSelectorList. Optional FieldSelectorList instance to be filled with the list of calculated fields which got updated.
	 * - elementEvaluatorClassName: String. Optional, a specific ElementEvaluator class to use, 
	 * if not defined takes ElementEvaluator linked to Element or current execution module.
	 * @param ExecutionSink $executionSink an optional ExecutionSink instance that can be used to log Wigii business process actions.
	 * @throws WigiiBPLException|Exception in case of error
	 */
	public function elementEvalCalcFields($principal, $caller, $parameter, $executionSink=null) {
		$this->executionSink()->publishStartOperation("elementEvalCalcFields", $principal);
		try {
			if(is_null($principal)) throw new WigiiBPLException('principal cannot be null', WigiiBPLException::INVALID_ARGUMENT);
			if(is_null($parameter)) throw new WigiiBPLException('parameter cannot be null', WigiiBPLException::INVALID_ARGUMENT);
	
			$element = $parameter->getValue('element');
			if(is_null($element)) throw new WigiiBPLException('element cannot be null', WigiiBPLException::INVALID_PARAMETER);
			// gets field to re-calculate
			$fieldName = $parameter->getValue('fieldName');
			if($fieldName instanceof FieldSelector) $fieldName = $fieldName->getFieldName();
			if(!empty($fieldName)) $field = $element->getFieldList()->getField($fieldName);
			else $field = null;
			// get RecordEvaluator instance
			$exec = ServiceProvider::getExecutionService();
			$elementEvaluatorClassName = $parameter->getValue('elementEvaluatorClassName');
			if(empty($elementEvaluatorClassName)) {
				if($element instanceof Element) $elementEvaluatorClassName = (string)$this->getConfigService()->getParameter($principal, $element->getModule(), "Element_evaluator");
				if(empty($elementEvaluatorClassName)) $elementEvaluatorClassName = (string)$this->getConfigService()->getParameter($principal, $exec->getCrtModule(), "Element_evaluator");
			}
			$evaluator = ServiceProvider::getRecordEvaluator($principal, $elementEvaluatorClassName);			
			// injects current data flow context if defined
			if(method_exists($evaluator, 'setDataFlowContext')) $evaluator->setDataFlowContext($parameter->getValue('dataFlowContext'));			
			// evaluates calculated fields
			$evaluator->evaluateRecord($principal, $element, $field);
			
			// records calculated fields which where updated in provided FieldSelectorList
			$fslForUpdate = $parameter->getValue('fslForUpdate');
			if($fslForUpdate instanceof FieldSelectorList) {
			    // records single calculated field
			    if(isset($field)) {
			        if(!$fslForUpdate->containsFieldSelector($field->getFieldName())) $fslForUpdate->addFieldSelector($field->getFieldName());
			    }
			    // else records all calculated fields
			    else {
    			    foreach($element->getFieldList()->getListIterator() as $f)
    			    {
    			        if($f->isCalculated() && !$f->isCalculationDisabled() && !$fslForUpdate->containsFieldSelector($f->getFieldName())) {
    			            $fslForUpdate->addFieldSelector($f->getFieldName());
    			        }
    			    }
			    }
			}
		}
		catch(Exception $e) {
			$this->executionSink()->publishEndOperationOnError("elementEvalCalcFields", $e, $principal);
			throw $e;
		}
		$this->executionSink()->publishEndOperation("elementEvalCalcFields", $principal);
	}
	
	/**
	 * Given an Element extracts the values of a set of Fields, create an object for each value and dumps them into a given ValueList.
	 * @param Principal $principal authenticated user executing the Wigii business process
	 * @param Object $caller the object calling the Wigii business process.
	 * @param WigiiBPLParameter $parameter the elementExtractFieldSet business process needs the following parameters to run :
	 * - element: Element. The filled element containing the Fields to be extracted
	 * - fieldNamePrefix: String|Array. The prefix (or array of prefixes) used to select the Fields to be extracted.
	 * - hierarchySep: String. Optional, a separator which is used to split matching Field names into several levels.
	 * - valueList: ValueList. Optional, an open ValueList instance in which to dump the created objects	 
	 * @param ExecutionSink $executionSink an optional ExecutionSink instance that can be used to log Wigii business process actions.
	 * @throws WigiiBPLException|Exception in case of error
	 * @return int|Array if ValueList is defined, then returns the number of created objects, else returns an array containing the extracted objects.
	 * The extracted objects are StdClass instances of the form :
	 * {
	 * 	fieldName: complete FieldName
	 *  if fieldNamePrefix or hierarchySep is defined, then
	 *  	nbLevels: the number of hierarchical levels found in FieldName.
	 *  	level_i (i=0..nbLevels-1): level_0 is FieldName prefix, level_1, ..., level_i are FieldName parts as splitted using hierarchySep.
	 * }
	 * @example Consider an Element with the Fields
	 * <A1_1_1 type="Attributs"/> with value 2
	 * <A1_1_2 type="Attributs"/> with value 5
	 * <A1_1_3 type="Attributs"/> with value 1
	 * <A1_2_1 type="Attributs"/> with value 9
	 * <A1_2_2 type="Attributs"/> with value null
	 * <A1_2_3 type="Attributs"/> with value 3
	 * <A2_1_1 type="Attributs"/> with value 2
	 * <A2_1_2 type="Attributs"/> with value 1
	 * 
	 * calling elementExtractFieldSet($principal,$me,wigiiBPLParam("element", $anElement, "fieldNamePrefix", array("A1","A2"), "hierarchySep", "_"))
	 * 
	 * will return an array of StdClasses [
	 *  {fieldName: "A1_1_1", nbLevels: 3, level_0: "A1",level_1: 1, level_2: 1, value: 2},
	 *  {fieldName: "A1_1_2", nbLevels: 3, level_0: "A1",level_1: 1, level_2: 2, value: 5},
	 *  {fieldName: "A1_1_3", nbLevels: 3, level_0: "A1",level_1: 1, level_2: 3, value: 1},
	 *  {fieldName: "A1_2_1", nbLevels: 3, level_0: "A1",level_1: 2, level_2: 1, value: 9},
	 *  {fieldName: "A1_2_2", nbLevels: 3, level_0: "A1",level_1: 2, level_2: 2, value: null},
	 *  {fieldName: "A1_2_3", nbLevels: 3, level_0: "A1",level_1: 2, level_2: 3, value: 3},
	 *  {fieldName: "A2_1_1", nbLevels: 3, level_0: "A2",level_1: 1, level_2: 1, value: 2},
	 *  {fieldName: "A2_1_2", nbLevels: 3, level_0: "A1",level_1: 1, level_2: 2, value: 1}
	 * ]
	 */
	public function elementExtractFieldSet($principal, $caller, $parameter, $executionSink=null) {
		$this->executionSink()->publishStartOperation("elementEvalCalcFields", $principal);
		$returnValue = null;
		try {
			if(is_null($principal)) throw new WigiiBPLException('principal cannot be null', WigiiBPLException::INVALID_ARGUMENT);
			if(is_null($parameter)) throw new WigiiBPLException('parameter cannot be null', WigiiBPLException::INVALID_ARGUMENT);
	
			$element = $parameter->getValue('element');
			if(is_null($element)) throw new WigiiBPLException('element cannot be null', WigiiBPLException::INVALID_PARAMETER);
			$fieldNamePrefix = $parameter->getValue('fieldNamePrefix');
			if(empty($fieldNamePrefix)) $fieldNamePrefix = null;
			elseif(!is_array($fieldNamePrefix)) $fieldNamePrefix = array($fieldNamePrefix);
			$hierarchySep = $parameter->getValue('hierarchySep');
			if(empty($hierarchySep)) $hierarchySep = null;
			$valueList = $parameter->getValue('valueList');
			if(!isset($valueList)) $returnValue = array();
			else $returnValue = 0;
			
			$currentPrefix=null;
			if(isset($fieldNamePrefix)) {
				$nPrefix = count($fieldNamePrefix);
				$iPrefix = 0;
				$currentPrefix = $fieldNamePrefix[$iPrefix];
			}
			// goes through the FieldList
			foreach($element->getFieldList()->getListIterator() as $field) {
				$dt = $field->getDataType();
				// only takes fields with a DataType having the value subfield
				if($dt && !$dt->hasSubfields()) {
					$fieldName = $field->getFieldName();					
					// filters Field using prefix if defined
					if(isset($currentPrefix)) {
						$i=0;
						// tests all possibles prefixes and stops on first matching						
						while($i<$nPrefix && strpos($fieldName, $currentPrefix)!==0) {
							$iPrefix++; if($iPrefix >= $nPrefix) $iPrefix = 0;
							$currentPrefix = $fieldNamePrefix[$iPrefix];		
							$i++;
						}
						// no match, skip field
						if($i>=$nPrefix) continue;
					}
					if(isset($fieldName)) {
						// splits according to hierarchySep if defined
						if(isset($hierarchySep)) $levels = explode($hierarchySep, $fieldName);							
						elseif(isset($currentPrefix)) $levels=array($currentPrefix);
						else $levels=array($fieldName);
						// builds object
						$nbLevels = count($levels);						
						$o = array('fieldName'=>$fieldName,'nbLevels'=>$nbLevels);
						for($i=0;$i<$nbLevels;$i++) {
							$o['level_'.$i] = $levels[$i];
						}
						$o['value'] = $element->getFieldValue($fieldName);
						$o = (object)$o;
						// stores object into output
						if(isset($valueList)) {
							$valueList->addValue($o);
							$returnValue++;
						}
						else $returnValue[] = $o;
					}
				}				
			}
		}
		catch(Exception $e) {
			$this->executionSink()->publishEndOperationOnError("elementExtractFieldSet", $e, $principal);
			throw $e;
		}
		$this->executionSink()->publishEndOperation("elementExtractFieldSet", $principal);
		return $returnValue;
	}
	
	/**
	 * Moves the content of a field of type Files to another field of type Files
	 * @param Principal $principal authenticated user executing the Wigii business process
	 * @param Object $caller the object calling the Wigii business process.
	 * @param WigiiBPLParameter $parameter the elementMoveFileField business process needs the following parameters to run :
	 * - element: Element. The filled element containing the two fields of type Files
	 * - fromField: String. The field name containing the File to move.
	 * - toField: String. The field name to which to move the File.
	 * - swap: Boolean. If true, then File contained in toField will go in fromField, else File contained in toField is deleted.
	 * @param ExecutionSink $executionSink an optional ExecutionSink instance that can be used to log Wigii business process actions.
	 * @throws WigiiBPLException|Exception in case of error
	 */
	public function elementMoveFileField($principal, $caller, $parameter, $executionSink=null) {
		$this->executionSink()->publishStartOperation("elementMoveFileField", $principal);
		try {
			if(is_null($principal)) throw new WigiiBPLException('principal cannot be null', WigiiBPLException::INVALID_ARGUMENT);
			if(is_null($parameter)) throw new WigiiBPLException('parameter cannot be null', WigiiBPLException::INVALID_ARGUMENT);
				
			$element = $parameter->getValue('element');
			if(is_null($element)) throw new WigiiBPLException('element cannot be null', WigiiBPLException::INVALID_PARAMETER);
			$fromField = $parameter->getValue('fromField');
			if(is_null($fromField)) throw new WigiiBPLException('fromField cannot be null', WigiiBPLException::INVALID_PARAMETER);
			if($fromField instanceof FieldSelector) $fromField = $fromField->getFieldName();
			if(!($element->getFieldList()->getField($fromField)->getDataType() instanceof Files)) throw new WigiiBPLException('fromField should be of datatype Files', WigiiBPLException::INVALID_PARAMETER);			
			
			$toField = $parameter->getValue('toField');
			if(is_null($toField)) throw new WigiiBPLException('toField cannot be null', WigiiBPLException::INVALID_PARAMETER);
			if($toField instanceof FieldSelector) $toField = $toField->getFieldName();
			if(!($element->getFieldList()->getField($toField)->getDataType() instanceof Files)) throw new WigiiBPLException('toField should be of datatype Files', WigiiBPLException::INVALID_PARAMETER);
			
			$swap = $parameter->getValue('swap');
			
			// extracts files information
			$files = $element->toStdClass(fsl(fs($fromField),fs($toField)));
			
			// moves files on disk
			$fe = BasicFormExecutor::createInstance($this->getWigiiExecutor(), $element, null, null);
			$exec = ServiceProvider::getExecutionService();
			
			
			$fieldXml = $element->getFieldList()->getField($toField)->getXml();
			$toFieldKeepHistory = ($fieldXml['keepHistory']>0);
			$fieldXml = $element->getFieldList()->getField($fromField)->getXml();
			$fromFieldKeepHistory = ($fieldXml['keepHistory']>0);
			
			// unlinks toField files history
			$toDir = $fe->getHistoryDir($principal, $exec, $element, $toField);			
			$ok = @rename($toDir, $toDir.'_old');
			// renames fromField files history to toField files history
			if($ok && $fromFieldKeepHistory) {
				$fromDir = $fe->getHistoryDir($principal, $exec, $element, $fromField);
				$ok = @rename($fromDir, $toDir);
			}			
			if($ok) {
				// if swap renames toField files history to fromField files history
				if($swap && $toFieldKeepHistory) {
					if(!$fromDir) $fromDir=str_replace($toField, $fromField, $toDir);
					@rename($toDir.'_old', $fromDir);
				}
				// deletes toField files history
				else {
					rrmdir($toDir.'_old',true);
				}
				// if no swap deletes toField file
				if(!$swap) {
					if (isImage($element->getFieldValue($toField, "mime"))) @unlink(FILES_PATH."tn_".$element->getFieldValue($toField, "path"));
					@unlink(FILES_PATH.$element->getFieldValue($toField, "path"));
				}
			}
			// else rollback
			else @rename($toDir.'_old', $toDir);
			
			// changes Files values in element and saves to DB
			if($ok) {
				$fslForUpdate = FieldSelectorListArrayImpl::createInstance();
				
				// updates toField with values from fromField
				foreach($files->{$fromField} as $subfield=>$val) {
					$element->setFieldValue($val,$toField,$subfield);
					$fslForUpdate->addFieldSelector($toField,$subfield);
				}
				// if swap, updates fromField with values from toField
				if($swap) {
					foreach($files->{$toField} as $subfield=>$val) {
						$element->setFieldValue($val,$fromField,$subfield);
						$fslForUpdate->addFieldSelector($fromField,$subfield);
					}
				}
				// else clears fromField
				else {
					foreach($files->{$toField} as $subfield=>$val) {
						$element->setFieldValue(null,$fromField,$subfield);
						$fslForUpdate->addFieldSelector($fromField,$subfield);
					}
				}
				
				// saves to DB
				$this->getElementService()->updateElement($principal, $element, $fslForUpdate);
			}
			else throw new WigiiBPLException("could not move File from field $fromField to field $toField", WigiiBPLException::FORBIDDEN);
		}
		catch(Exception $e) {
			$this->executionSink()->publishEndOperationOnError("elementMoveFileField", $e, $principal);
			throw $e;
		}
		$this->executionSink()->publishEndOperation("elementMoveFileField", $principal);
	}
	
	/**
	 * Persists a field of type Files from an Element, which content is currently stored into the TEMPORARYUPLOADEDFILE_path folder.
	 * @param Principal $principal authenticated user executing the Wigii business process
	 * @param Object $caller the object calling the Wigii business process.
	 * @param WigiiBPLParameter $parameter the elementPersistFileField business process needs the following parameters to run :
	 * - element: Element. The filled element containing the field of type Files
	 * - fieldName: String. The field name containing the File to persist.
	 * @param ExecutionSink $executionSink an optional ExecutionSink instance that can be used to log Wigii business process actions.
	 * @throws WigiiBPLException|Exception in case of error
	 */
	public function elementPersistFileField($principal, $caller, $parameter, $executionSink=null) {
		$this->executionSink()->publishStartOperation("elementPersistFileField", $principal);
		try {
			if(is_null($principal)) throw new WigiiBPLException('principal cannot be null', WigiiBPLException::INVALID_ARGUMENT);
			if(is_null($parameter)) throw new WigiiBPLException('parameter cannot be null', WigiiBPLException::INVALID_ARGUMENT);
	
			$element = $parameter->getValue('element');
			if(is_null($element)) throw new WigiiBPLException('element cannot be null', WigiiBPLException::INVALID_PARAMETER);
			$fieldName = $parameter->getValue('fieldName');
			if(is_null($fieldName)) throw new WigiiBPLException('fieldName cannot be null', WigiiBPLException::INVALID_PARAMETER);
			if($fieldName instanceof FieldSelector) $fieldName = $fieldName->getFieldName();
			$field=$element->getFieldList()->getField($fieldName);
			if(!($field->getDataType() instanceof Files)) throw new WigiiBPLException("field '$fieldName' should be of datatype Files", WigiiBPLException::INVALID_PARAMETER);
							
			$fe = BasicFormExecutor::createInstance($this->getWigiiExecutor(), $element, null, null);
			$exec = ServiceProvider::getExecutionService();
			$elS = $this->getElementService();
			
			$fslForUpdate = FieldSelectorListArrayImpl::createInstance();
			$fslForUpdate->addFieldSelector($fieldName, "path");
			$fslForUpdate->addFieldSelector($fieldName, "name");
			$fslForUpdate->addFieldSelector($fieldName, "size");
			$fslForUpdate->addFieldSelector($fieldName, "type");
			$fslForUpdate->addFieldSelector($fieldName, "mime");
			$fslForUpdate->addFieldSelector($fieldName, "date");
			$fslForUpdate->addFieldSelector($fieldName, "user");
			$fslForUpdate->addFieldSelector($fieldName, "username");
			$fslForUpdate->addFieldSelector($fieldName, "version");
			$fslForUpdate->addFieldSelector($fieldName, "thumbnail");
			$fslForUpdate->addFieldSelector($fieldName, "content");
			$fslForUpdate->addFieldSelector($fieldName, "textContent");
									
			// gets old File field information
			$oldElement = $this->getWigiiExecutor()->createElementForForm($principal, $element->getModule(), $element->getId());
			$oldElement = $elS->fillElement($principal, $oldElement,$fslForUpdate);
			if(!isset($oldElement)) throw new WigiiBPLException('Element with ID '.$element->getId().' is not accessible to user '.$principal->getRealUsername().' or Element does not exist in database', WigiiBPLException::INVALID_PARAMETER);
			$isBlocked = $oldElement->isParentElementState_blocked();
			$oldElement=$oldElement->getDbEntity();
			// Prevents updating a File of a blocked element
			if($isBlocked || $oldElement->isState_blocked()) throw new WigiiBPLException('Element with ID '.$element->getId().' is blocked and cannot be updated.', WigiiBPLException::NOT_ALLOWED);
			// moves temp file to wigii bag if needed
			$storeFileInWigiiBag = ($this->getConfigService()->getParameter ($principal, null, "storeFileContentIntoDatabase" ) == "1");
			if($storeFileInWigiiBag) $fe->moveTempFileToWigiiBag($element, $field);
			// updates element into database
			$elS->updateElement($principal, $element, $fslForUpdate);
			// updates files on disk
			$fe->updateFilesOnDiskForField($principal, $exec, $element, $field, $storeFileInWigiiBag, $oldElement);
		}
		catch(Exception $e) {
			$this->executionSink()->publishEndOperationOnError("elementPersistFileField", $e, $principal);
			throw $e;
		}
		$this->executionSink()->publishEndOperation("elementPersistFileField", $principal);
	}
	
	/**
	 * Persists a field of type Files from an Element, which content is posted to the application
	 * @param Principal $principal authenticated user executing the Wigii business process
	 * @param mixed $caller the class object reference calling the Wigii business process.
	 * @param WigiiBPLParameter $parameter the elementPersistFileField business process needs the following parameters to run :
	 * - element: Element. The filled element containing the field of type Files
	 * - fieldName: String. The field name containing the File to persist.
	 * - formFieldName: String Optional defaults to <fieldName>_file
	 * @param ExecutionSink $executionSink an optional ExecutionSink instance that can be used to log Wigii business process actions.
	 * @throws WigiiBPLException|Exception in case of error
	 */
	public function elementPersistFileFieldFromPost(Principal $principal, $caller, WigiiBPLParameter $parameter, ExecutionSink $executionSink = null) {
		$this->executionSink()->publishStartOperation("elementPersistFileFieldFromPost", $principal);
		try {
			// Check params are passed in as expected
			$element = $parameter->getValue('element');
			if (!is_a($element, 'Element')) throw new WigiiBPLException('$parameter element must be a valid element', WigiiBPLException::INVALID_PARAMETER);
	
			$fieldName = $parameter->getValue('fieldName');
			if (empty($fieldName)) throw new WigiiBPLException('fieldName cannot be empty', WigiiBPLException::INVALID_PARAMETER);
				
			//generate formFieldName
			$formFieldName = $parameter->getValue('formFieldName');
			if (is_null($formFieldName)) $formFieldName = $fieldName . '_file';
			$elementFileAdminService = TechnicalServiceProvider::getElementFileAdminService();
			$elementFileAdminService->stagePostedFile($principal, $element, $fieldName, $formFieldName);
			$this->elementPersistFileField($principal, $caller, $parameter, $executionSink);
		} catch (Exception $e) {
			$this->executionSink()->publishEndOperationOnError("elementPersistFileFieldFromPost", $e, $principal);
			throw $e;
		}
		$this->executionSink()->publishEndOperation("elementPersistFileFieldFromPost", $principal);
	}
	
	/**
	 * Inserts a new element into the database. No fields are saved, only the element is created and the ID is returned.
	 * If element already exists, then nothing is done and current element ID is returned.
	 * The element has a valid ElementInfo attached so that it can be further modified and the persisted using the ElementDFA data flow activitiy.
	 * @param Principal $principal authenticated user executing the Wigii business process
	 * @param Object $caller the object calling the Wigii business process.
	 * @param WigiiBPLParameter $parameter the elementInsert business process needs the following parameters to run :
	 * - element: Element. The new element to be inserted into the database
	 * - groupId: int. The group ID in which to add the element
	 * @param ExecutionSink $executionSink an optional ExecutionSink instance that can be used to log Wigii business process actions.
	 * @throws WigiiBPLException|Exception in case of error
	 * @return int the new element ID
	 */
	public function elementInsert($principal, $caller, $parameter, $executionSink=null) {
	    $this->executionSink()->publishStartOperation("elementInsert", $principal);
	    $returnValue = null;
	    try {
	        if(is_null($principal)) throw new WigiiBPLException('principal cannot be null', WigiiBPLException::INVALID_ARGUMENT);
	        if(is_null($parameter)) throw new WigiiBPLException('parameter cannot be null', WigiiBPLException::INVALID_ARGUMENT);
	        
	        $element = $parameter->getValue('element');
	        if(!isset($element)) throw new WigiiBPLException('element cannot be null', WigiiBPLException::INVALID_ARGUMENT);
	        
	        if($element->isNew()) {
    	        // inserts element in database
	           $this->getElementService()->insertElement($principal, $element, $parameter->getValue('groupId'), fsl(fs_e('id')));
	           // extracts element info and stamps it
	           if($this->getAuthorizationService()->getStamp($this, "setAuthorizationServiceStamp")) {
	               ElementP::createInstance($element)->computeElementInfo($principal, null, $this->consumeAuthorizationServiceStamp());
	           }
	        }
	        $returnValue = $element->getId();
	    }
	    catch(Exception $e) {
	        $this->executionSink()->publishEndOperationOnError("elementInsert", $e, $principal);
	        throw $e;
	    }
	    $this->executionSink()->publishEndOperation("elementInsert", $principal);
	    return $returnValue;
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
	 * - groupId: Int|LogExp. The search space in which to look for the element.
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
			if(!($groupId instanceof LogExp)) $groupId = lxEq(fs('id'),$groupId);
			$fsl = $parameter->getValue('fsl');
			
			$hasAdaptiveWigiiNamespace = $principal->hasAdaptiveWigiiNamespace();
			if(!$hasAdaptiveWigiiNamespace) {
				$crtNamespace = $principal->getWigiiNamespace();
				$principal->setAdaptiveWigiiNamespace(true);
			}
			$lf = lf($fsl,lxEq(fs($keyField),$keyValue),null,1,1);
			$returnValue = $this->getDataFlowService()->processDumpableObject($principal, elementPList(lxInGR($groupId),$lf), dfasl(dfas("NullDFA")));
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
	
	/**
	 * Adds a comment to a field of type Blobs into a given Element.
	 * @param Principal $principal authenticated user executing the Wigii business process
	 * @param Object $caller the object calling the Wigii business process.
	 * @param WigiiBPLParameter $parameter the elementAddComment business process needs the following parameters to run :
	 * - element: Element. The element to which to add a comment
	 * - fieldName: String|FieldSelector. The name of the field to which to add a comment. Should be of type Blobs.
	 * - comment: String. Evaluates to a String that will added as a comment.
	 * @param ExecutionSink $executionSink an optional ExecutionSink instance that can be used to log Wigii business process actions.
	 * @throws WigiiBPLException|Exception in case of error
	 * @return Element the element to which the comment was added
	 */
	public function elementAddComment($principal, $caller, $parameter, $executionSink=null) {
	    if(is_null($principal)) throw new WigiiBPLException('principal cannot be null', WigiiBPLException::INVALID_ARGUMENT);
	    if(is_null($parameter)) throw new WigiiBPLException('parameter cannot be null', WigiiBPLException::INVALID_ARGUMENT);
	    
	    $element = $parameter->getValue('element');
	    if(!($element instanceof Record)) throw new WigiiBPLException('element should be a non null instance of Element', WigiiBPLException::INVALID_PARAMETER);
	    $fieldName = $parameter->getValue('fieldName');
	    if($fieldName instanceof FieldSelector) $fieldName = $fieldName->getFieldName();
	    elseif(!isset($fieldName)) throw new WigiiBPLException('fieldName cannot be empty', WigiiBPLException::INVALID_PARAMETER);
	    if(!$element->getFieldList()->doesFieldExist($fieldName)) throw new WigiiBPLException("fieldName '".$fieldName."' is not a valid field in the record", WigiiBPLException::INVALID_ARGUMENT);
	    
	    $comment = $parameter->getValue('comment');
	    
	    $result="";
	    $fxml=$element->getFieldList()->getField($fieldName)->getXml();
	    $isHtmlArea = ($fxml["htmlArea"] == "1");
	    $isJournal = ($fxml["isJournal"] == "1");
	    
	    $header = date("d.m.Y H:i")." ".$principal->getRealUsername();
	    
	    // removes html from comment if not an html area
	    if(!$isHtmlArea) {
	        $html2text = new Html2text();
	        $html2text->setHtml($comment);
	        $comment = $html2text->getText();
	    }
	    
	    if($isJournal) {
	        if($isHtmlArea) {
	            $result .= '<p style="color:#666;">&gt; ';
	            $result .= $header;
	            $result .= "</p>";
	            $result .= '<p>'.$comment.'</p>';
	            $result .= "<p>&nbsp;</p>";
	        }
	        else {
	            $result .= "> ";
	            $result .= $header;
	            $result .= "\n";
	            $result .= $comment."\n";
	            $result .= "\n";
	        }
	    }
	    else {
	        if($isHtmlArea) {
	            $result .= "<p>".$header.' '.$comment."</p>";
	            $result .= "<p>&nbsp;</p>";
	        }
	        else {
	            $result .= $header.' '.$comment."\n";
	            $result .= "\n";
	        }
	    }
	    
	    $result .= $element->getFieldValue($fieldName);
	    $element->setFieldValue($result, $fieldName);
	    return $element;
	}
	
	/**
	 * Returns the trashbin group associated to a module or an element. Or an array with the trashbin and all its sub-groups.
	 * @param Principal $principal authenticated user executing the Wigii business process
	 * @param Object $caller the object calling the Wigii business process.
	 * @param WigiiBPLParameter $parameter the groupGetTrashbin business process needs the following parameters to run :
	 * - forModule: Module|Element|ElementP|String. Optional. Module or module name or Element for which to fetch the trashbin. If not given, takes the current module.
	 * - includeSubGroups: Boolean. It true, trashbin sub-folders are also fetched, else only takes the trashbin. By default, subfolders are not fetched. 
	 * - returnAttribute: String. The name of the group attribute to return. Defaults to id. If 'group' then returns Group object.
	 * @param ExecutionSink $executionSink an optional ExecutionSink instance that can be used to log Wigii business process actions.
	 * @throws WigiiBPLException|Exception in case of error
	 * @return int|Group|Array the trashbin ID or the trashbin group or an array of group ids or groups, or null if no trashbin defined.
	 * If an array is returned, then the trashbin comes first, followed by the sub-groups.
	 */
	public function groupGetTrashbin($principal, $caller, $parameter, $executionSink=null) {
	    $this->executionSink()->publishStartOperation("groupGetTrashbin", $principal);
	    $returnValue = null;
	    try {
	        if(is_null($principal)) throw new WigiiBPLException('principal cannot be null', WigiiBPLException::INVALID_ARGUMENT);
	        if(is_null($parameter)) throw new WigiiBPLException('parameter cannot be null', WigiiBPLException::INVALID_ARGUMENT);
	        
	        // extracts module where to fetch the trashbin
	        $forModule = $parameter->getValue('forModule');
	        if($forModule instanceof ElementP) $forModule = $forModule->getElement();
	        if($forModule instanceof Element) $forModule = $forModule->getModule();
	        if(!isset($forModule)) $forModule = ServiceProvider::getExecutionService()->getCrtModule();
	        if(!($forModule instanceof Module)) $forModule = $this->getModuleAdminService()->getModule($principal, $forModule);
	        
	        $returnAttribute = $parameter->getValue('returnAttribute');
	        if(!isset($returnAttribute)) $returnAttribute = 'id';
	        
	        // extracts trashbin id
	        $trashBinGroupId = (string)$this->getConfigService()->getParameter($principal, $forModule, 'trashBinGroup');
	        if(isset($trashBinGroupId)) {
	            $groupAS = $this->getGroupAdminService();
    	        // checks trashbin validity
    	        $trashBinGroup = $groupAS->getGroupWithoutDetail($principal, $trashBinGroupId);
    	        if(is_null($trashBinGroup) || $trashBinGroup->getModule() !==  $forModule) {
    	            throw new GroupAdminServiceException("trashBinGroup $trashBinGroupId is not valid group of module ".$forModule->getModuleName(), GroupAdminServiceException::CONFIGURATION_ERROR);
    	        }
    	        // extracts subfolders
    	        if($parameter->getValue('includeSubGroups')) {
    	            $trashBinSubGroups = GroupListArrayImpl::createInstance();
    	            $groupAS->getSelectedGroupsWithChildrenWithoutDetail($principal,lxEq(fs('id'),$trashBinGroupId),$trashBinSubGroups);
    	            // builds multiple result and puts trashbin first.
    	            $returnValue = array();
    	            if($returnAttribute == 'group') $returnValue[$trashBinGroupId] = $trashBinGroup;
    	            else $returnValue[$trashBinGroupId] = $trashBinGroup->getAttribute($returnAttribute);
    	            foreach ($trashBinSubGroups->getListIterator() as $subgroup) {
    	                if($returnAttribute == 'group') $returnValue[$subgroup->getId()] = $subgroup;
    	                else $returnValue[$subgroup->getId()] = $subgroup->getAttribute($returnAttribute);
    	            }
    	        }
    	        // builds single result
    	        else {
    	            if($returnAttribute == 'group') $returnValue = $trashBinGroup;
    	            else $returnValue = $trashBinGroup->getAttribute($returnAttribute);
    	        }
	        }
	    }
	    catch(Exception $e) {
	        $this->executionSink()->publishEndOperationOnError("groupGetTrashbin", $e, $principal);
	        throw $e;
	    }
	    $this->executionSink()->publishEndOperation("groupGetTrashbin", $principal);
	    return $returnValue;
	}
	
	/**
	 * Synchronizes the elements contained in a group with the files present in a physical folder on the file system
	 * @param Principal $principal authenticated user executing the Wigii business process
	 * @param Object $caller the object calling the Wigii business process.
	 * @param WigiiBPLParameter $parameter the groupSyncElementsWithFileSystem business process needs the following parameters to run :
	 * - group: int|Group|GroupP. Group (or group id) in which to synchronize the elements
	 * - folderPath: String. Optional folder path on server where to look for the files to synchronize. 
	 *   If not given, takes Group portal url field which should be of type file://xxx
	 * - linkGroupToFolder: Boolean. Optional. If true, then group portal url is updated with folder path and group is persisted,  
	 *   sub groups are created and linked to sub folders (sub content is not synchronized. To synchronize whole tree, set includeChildrenGroups=true) 
	 *   If false, only elements are linked to files on file system. Defaults to false.
	 * - includeChildrenGroups: Boolean. Optional. If true, then updates whole tree recursively. Defaults to false.
	 * - fieldName: String. Field of type Files mapped to the file in the file system. 
	 *   If not given, takes first field of type Files having attribute uniqueInGroup=1. 
	 *   File name is used as a mapping key. If several elements match file name, then takes first match.
	 * - noCalculation: Boolean. If true, then element calculated fields are not re-calculated. By default calculation is active.
	 * - noDeletion: Boolean. If true, then existing elements linked to non-existing files are not deleted. 
	 * By default, elements linked to non-existing files are deleted. Deletion protection can also be configured using an Element_beforeDeleteExp. 
	 * - refreshGUI: Boolean. If true, then Wigii web user interface is asked to clear its group caches when sharing of element changes.
	 *   If explicitely set to false, then nothing is sent to Wigii GUI. By default, GUI is refreshed.
	 * @param ExecutionSink $executionSink an optional ExecutionSink instance that can be used to log Wigii business process actions.
	 * @throws WigiiBPLException|Exception in case of error	 
	 */
	public function groupSyncElementsWithFileSystem($principal, $caller, $parameter, $executionSink=null) {
	    $this->executionSink()->publishStartOperation("groupSyncElementsWithFileSystem", $principal);
	    $returnValue = null;
	    try {
	        if(is_null($principal)) throw new WigiiBPLException('principal cannot be null', WigiiBPLException::INVALID_ARGUMENT);
	        if(is_null($parameter)) throw new WigiiBPLException('parameter cannot be null', WigiiBPLException::INVALID_ARGUMENT);	        
	        
	        $exec = ServiceProvider::getExecutionService();
	        $groupAS = $this->getGroupAdminService();
	        
	        // extracts parameters
	        
	        $group = $parameter->getValue('group');
	        if(!is_object($group)) $group = $this->getGroupAdminService()->getGroup($principal, $group);
	        if($group instanceof GroupP) $group = $group->getDbEntity();
	        if(!isset($group)) throw new WigiiBPLException('group cannot be null', WigiiBPLException::INVALID_ARGUMENT);
            $origNS = $principal->getWigiiNamespace();
            $principal->bindToWigiiNamespace($group->getWigiiNamespace());
	        
	        $linkGroupToFolder = $parameter->getValue('linkGroupToFolder');
	        $includeChildrenGroups = $parameter->getValue('includeChildrenGroups');
	        $persistGroupPortal=($linkGroupToFolder==true);
	        $portalRec = $this->getWigiiExecutor()->createActivityRecordForForm($principal, Activity::createInstance("groupPortal"), $group->getModule());
	        $portalRec->getWigiiBag()->importFromSerializedArray($group->getDetail()->getPortal(), $portalRec->getActivity());	        
	        
	        $fieldName = $parameter->getValue('fieldName');
	        if(!$fieldName) {	            
	            $fieldName = $this->getWigiiExecutor()->doesCrtModuleHasIsKeyField($principal, $group);
	            if($fieldName) $fieldName = $fieldName->getName();
	            else $fieldName = null;
	        }
	        if(!$fieldName) throw new WigiiBPLException('no field name is given, and no key or unique field is defined in configuration. Configure a field of type Files with isUniqueInGroup=1', WigiiBPLException::INVALID_ARGUMENT);
	        
	        $noCalculation = ($parameter->getValue('noCalculation')==true);
	        $refreshGUI = $parameter->getValue('refreshGUI');
	        if(is_null($refreshGUI)) $refreshGUI = true;
	        
	        $folderPath = $parameter->getValue('folderPath');
	        if(empty($folderPath)) {
	            // extracts folder path from group portal	            
	            $folderPath = $portalRec->getFieldValue("url", "url");
	            // evaluates any given FuncExp
	            $folderPath = $this->evaluateConfigParameter($principal,$folderPath);
	            $persistGroupPortal=false;
	        }
	        if(empty($folderPath)) throw new WigiiBPLException('no folderPath is given and group is not linked to a web address of type file://', WigiiBPLException::INVALID_ARGUMENT);
	        // checks folderPath
	        if(strpos($folderPath, 'file://')===false) $folderPath='file://'.$folderPath;
	        // removes ending slash
	        if(substr($folderPath, -1)=="/") $folderPath = substr($folderPath,0,-1);	        
	        $physicalPath = resolveFilePath($folderPath);
	        if(!file_exists($physicalPath)) throw new WigiiBPLException('folder path '.$folderPath.' does not point to any existing and valid folder on the server',WigiiBPLException::NOT_ALLOWED);
	        // adds ending slash
	        $folderPath.= '/';
	        
	        // persists folder path in group portal
	        if($persistGroupPortal) {
	            $portalRec->setFieldValue($folderPath, "url", "url");
	            $group->getDetail()->setPortal($portalRec->getWigiiBag()->exportAsSerializedArray($portalRec->getActivity()));
	            $groupAS->setPortal($principal, $group->getId(), $group->getDetail()->getPortal());
	        }
	        
	        // lists all files contained in directory
	        $files = scandir($physicalPath);
	        // maps each file to an element and stores sub folders for later.
	        if($files) {	            
	            $ctx = (object)array("subFolders"=>array());
	            $dfasl = dfasl(
	                dfas("CallbackDFA","setProcessDataChunkCallback",function($data,$callbackDFA) use($ctx,$physicalPath,$folderPath) {
	                    // skips . and .. directories
	                    if($data=='.' || $data=='..') return;
	                    $filename = $physicalPath."/".$data;
	                    // stores sub directory for later usage
	                    if(is_dir($filename)) $ctx->subFolders[] = $data;
	                    // else extracts file extension and sub fields
	                    else {
    	                    $ext = explode(".", $data);    	                    
    	                    if(count($ext) > 1){    	                        
    	                        $ext = end($ext);
    	                        $ext = ".".$ext;
    	                    } else $ext = "";
    	                    // builds file subfields
    	                    $returnValue = array();
    	                    $returnValue["type"] = strtolower($ext);
    	                    $returnValue["size"] = filesize($filename);
    	                    $returnValue["name"] = basename($data,$ext);
    	                    $returnValue["date"] = date("Y-m-d H:i:s",filemtime($filename));
    	                    if($ext!='') $returnValue["mime"] = typeMime($ext);
    	                    $returnValue["path"] = $folderPath.$data;
    	                    // pushes file subfields down in the flow
    	                    $callbackDFA->writeResultToOutput((object)$returnValue);
	                    }
	                }),
	                dfas("MapObject2ElementDFA","setGroupId",$group->getId(),"setElementSelectorMethod",function($data,$dataFlowContext) use($fieldName){
	                    // selects element having same name (with extension)
	                    return lxAnd(lxEq(fs($fieldName,'name'),$data->name),lxEq(fs($fieldName,'type'),$data->type));
	                },
	                "setObject2ElementMap",array(
	                    "name"=>fs($fieldName,'name'),
	                    "type"=>fs($fieldName,'type'),
	                    "size"=>fs($fieldName,'size'),
	                    "date"=>fs($fieldName,'date'),
	                    "mime"=>fs($fieldName,'mime'),
	                    "path"=>fs($fieldName,'path')
	                ))
	            );
	            // Element recalculation
	            if(!$noCalculation) $dfasl->addDataFlowActivitySelectorInstance(dfas('ElementRecalcDFA'));
	            // Persist element in database
	            $dfasl->addDataFlowActivitySelectorInstance(dfas('ElementDFA','setMode',1,'setReloadElementAfterInsert',false));
	            // Extracts processed element ids
	            $dfasl->addDataFlowActivitySelectorInstance(dfas('MapElement2ValueDFA','setElement2ValueFuncExp',fs_e('id')));
	            $dfasl->addDataFlowActivitySelectorInstance(dfas('ArrayBufferDFA'));
	            // runs data flow
	            $processedEltIds = sel($principal,array2df($files),$dfasl);	            
	            // deletes element linked to non-existing files
	            if(!$parameter->getValue('noDeletion')) {
	                // selects all elements not yet processed and having a file in the linked folder
	                sel($principal,elementPList(lxInG(lxEq(fs('id'),$group->getId())),
	                    lf(fsl(fs_e('id'),fs($fieldName,'path')),lxAnd(lxNotIn(fs_e('id'), $processedEltIds), lxLike(fs($fieldName,'path'),$folderPath.'%')))),dfasl(
	                   /* deletes element if linked file does not exist */
	                   dfas('ElementDFA','setMode',ElementDFA::MODE_MIXED,'setIgnoreLockedElements',true,'setDecisionMethod',function($elementP,$dataFlowContext) use($fieldName){
	                       $returnValue = ElementDFA::MODE_IGNORE;
	                       $filePath = resolveFilePath($elementP->getDbEntity()->getFieldValue($fieldName,'path'));
	                       if($filePath && !file_exists($filePath)) $returnValue = ElementDFA::MODE_DELETE;
	                       return $returnValue;
	                   })
	                ));
	            }
	            // invalidates list view cache
	            if($refreshGUI) $exec->invalidCache ( $principal, 'moduleView', 'groupSelectorPanel', "groupSelectorPanel/selectGroup/" . $group->getId () );
	            
	            // creates sub groups based on sub folders
	            if(!empty($ctx->subFolders) && ($linkGroupToFolder || $includeChildrenGroups)) {
	                foreach($ctx->subFolders as $subFolder) {
	                    // gets or creates group based on subFolder name
	                    $subGroup = $groupAS->getOrCreateSubGroupByName($principal, $group->getId(), $subFolder);
	                    $subGroup = $subGroup->getDbEntity();
	                    // calls recursively groupSyncElementsWithFileSystem to populate all sub groups
	                    if($includeChildrenGroups) {
	                        $parameter->setValue('group', $subGroup);
	                        $parameter->setValue('folderPath',$folderPath.$subFolder);
	                        $parameter->setValue('linkGroupToFolder', true);
	                        $parameter->setValue('refreshGUI', false);
	                        $parameter->setValue('fieldName', $fieldName);
	                        $this->groupSyncElementsWithFileSystem($principal, $this, $parameter,$executionSink);
	                    }	                    
	                    // else only persists folder path in group portal
	                    else {
    	                    $portalRec = $this->getWigiiExecutor()->createActivityRecordForForm($principal, Activity::createInstance("groupPortal"), $exec->getCrtModule());
    	                    $portalRec->getWigiiBag()->importFromSerializedArray($subGroup->getDetail()->getPortal(), $portalRec->getActivity());
    	                    $portalRec->setFieldValue($folderPath.$subFolder, "url", "url");
    	                    $subGroup->getDetail()->setPortal($portalRec->getWigiiBag()->exportAsSerializedArray($portalRec->getActivity()));
    	                    $groupAS->setPortal($principal, $subGroup->getId(), $subGroup->getDetail()->getPortal());
    	                    // invalidates list view cache of sub group
    	                    if($refreshGUI) $exec->invalidCache ( $principal, 'moduleView', 'groupSelectorPanel', "groupSelectorPanel/selectGroup/" . $subGroup->getId () );
	                    }
	                }
	                // refreshes group panel
	                if($refreshGUI) {
	                    $exec->addRequests("groupPanel/".$exec->getCrtWigiiNamespace()->getWigiiNamespaceUrl()."/".$exec->getCrtModule()->getModuleName()."/display/groupPanel");
	                }
	            }
	        }
	        $principal->bindToWigiiNamespace($origNS);
	    }
	    catch(Exception $e) {
	        $this->executionSink()->publishEndOperationOnError("groupSyncElementsWithFileSystem", $e, $principal);
	        $principal->bindToWigiiNamespace($origNS);
	        throw $e;
	    }
	    $this->executionSink()->publishEndOperation("groupSyncElementsWithFileSystem", $principal);
	    return $returnValue;
	}
	
	/**
	 * Activates xml publication on a given group
	 * @param Principal $principal authenticated user executing the Wigii business process
	 * @param Object $caller the object calling the Wigii business process.
	 * @param WigiiBPLParameter $parameter the groupActivateXmlPublish business process needs the following parameters to run :
	 * - group: int|Group|GroupP. Group (or group id) for which to activate the xml publish
	 * - recalc: Boolean. If xml publish was already active on group and recalc is true, then a new xml feed code is calculated;
	 * else if xml publish was not active now, but xml feed code already exist and recalc is false, then old xml code is kept. By default a new code is calculated.
	 * @param ExecutionSink $executionSink an optional ExecutionSink instance that can be used to log Wigii business process actions.
	 * @throws WigiiBPLException|Exception in case of error
	 * @return String the xml feed code
	 */
	public function groupActivateXmlPublish($principal, $caller, $parameter, $executionSink=null) {
	    $this->executionSink()->publishStartOperation("groupActivateXmlPublish", $principal);
	    $returnValue = null;
	    try {
	        if(is_null($principal)) throw new WigiiBPLException('principal cannot be null', WigiiBPLException::INVALID_ARGUMENT);
	        if(is_null($parameter)) throw new WigiiBPLException('parameter cannot be null', WigiiBPLException::INVALID_ARGUMENT);
	        
	        $groupAS = $this->getGroupAdminService();
	        
	        // extracts parameters	        
	        $group = $parameter->getValue('group');
	        if(!is_object($group)) $group = $this->getGroupAdminService()->getGroup($principal, $group);
	        if($group instanceof GroupP) $group = $group->getDbEntity();
	        if(!isset($group)) throw new WigiiBPLException('group cannot be null', WigiiBPLException::INVALID_ARGUMENT);
	        $origNS = $principal->getWigiiNamespace();
	        $principal->bindToWigiiNamespace($group->getWigiiNamespace());
	        $recalc = $parameter->getValue('recalc');
	        
	        // loads xml publish activity
	        $xmlPublishRec = $this->getWigiiExecutor()->createActivityRecordForForm($principal, Activity::createInstance("groupXmlPublish"), $group->getModule());
	        $xmlPublishRec->getWigiiBag()->importFromSerializedArray($group->getDetail()->getXmlPublish(), $xmlPublishRec->getActivity());
	        $modified=false;
	        
	        // if xml feed code already exists
	        if($xmlPublishRec->getFieldValue('xmlPublishCode')!=null) {
	            // if already active and recalc, then calculates a new code
	            if($xmlPublishRec->getFieldValue('enableGroupXmlPublish') && $recalc) {
	                $xmlPublishRec->setFieldValue($group->getDetail()->getNewXmlPublishCode($principal, $group), "xmlPublishCode");
	                $modified=true;
	            }
	            // else if not already active and recalc strictly not false then calculates a new code
	            elseif(!$xmlPublishRec->getFieldValue('enableGroupXmlPublish')) {
	                if($recalc!==false) $xmlPublishRec->setFieldValue($group->getDetail()->getNewXmlPublishCode($principal, $group), "xmlPublishCode");
	                $xmlPublishRec->setFieldValue(true, "enableGroupXmlPublish");
	                $modified=true;
	            }
	        }
	        // else calculates a code
	        else {
	            $xmlPublishRec->setFieldValue($group->getDetail()->getNewXmlPublishCode($principal, $group), "xmlPublishCode");
	            $xmlPublishRec->setFieldValue(true, "enableGroupXmlPublish");
	            $modified=true;
	        }
	        $returnValue = $xmlPublishRec->getFieldValue('xmlPublishCode');
	        
	        // persists xml publish activity
	        if($modified) {	           
	           $xmlPublishRec->setFieldValue(false, "recalcXmlPublishCode");	        
	           $group->getDetail()->setXmlPublish($xmlPublishRec->getWigiiBag()->exportAsSerializedArray($xmlPublishRec->getActivity()));
	           $groupAS->setXmlPublish($principal, $group->getId(), $group->getDetail()->getXmlPublish());
	        }
	        $principal->bindToWigiiNamespace($origNS);
	    }
	    catch(Exception $e) {
	        $this->executionSink()->publishEndOperationOnError("groupActivateXmlPublish", $e, $principal);
	        $principal->bindToWigiiNamespace($origNS);
	        throw $e;
	    }
	    $this->executionSink()->publishEndOperation("groupActivateXmlPublish", $principal);
	    return $returnValue;
	}
	
	/**
	 * Fetches some posted data from HTTP POST request and builds an object based on the provided type
	 * @param Principal $principal authenticated user executing the Wigii business process
	 * @param Object $caller the object calling the Wigii business process.
	 * @param WigiiBPLParameter $parameter A WigiiBPLParameter instance with the following parameters :
	 * - type: String. One of 'element','activity','json','xml','fx'
	 * - currentObject: Element|ActivityRecord|StdClass|SimpleXmlElement. Current instanciated object that will be merged with posted data.
	 * - configSelector: ConfigSelector. If type is element or activity, specifies where to fetch the Configuration.
	 * - activity: Activity|String. If type is activity, then specifies the Activity for which to fetch the Configuration.
	 * @param ExecutionSink $executionSink an optional ExecutionSink instance that can be used to log Wigii business process actions.
	 * @throws WigiiBPLException|Exception in case of error
	 * @return Element|ActivityRecord|StdClass|SimpleXMLElement|FuncExp the instanciated object or null if nothing is posted
	 */
	public function dataFetchFromPost($principal, $caller, $parameter, $executionSink=null) {
		$this->executionSink()->publishStartOperation("dataFetchFromPost", $principal);
		$returnValue = null;
		try {
			if(is_null($principal)) throw new WigiiBPLException('principal cannot be null', WigiiBPLException::INVALID_ARGUMENT);
			if(is_null($parameter)) throw new WigiiBPLException('parameter cannot be null', WigiiBPLException::INVALID_ARGUMENT);
		
			$type = $parameter->getValue('type');
			$currentObject = $parameter->getValue('currentObject');
			
			switch($type) {
				case 'element': 
					if(!isset($currentObject)) {
						$currentObject=$this->buildNewElement($principal, $parameter->getValue('configSelector'));
					}
					$returnValue=$this->recordFetchFromPost($principal, $currentObject);
					break;
				case 'activity':
					//$configSelector=$parameter->getValue('configSelector');
					WigiiBPLException::throwNotImplemented();
					//$this->getWigiiExecutor()->createActivityRecordForForm($principal, $activity, $module)
					break;
				case 'json':
					$returnValue=$this->objectFetchFromPost($principal, $currentObject);
					break;
				case 'xml':
					$returnValue=$this->xmlFetchFromPost($principal);
					break;
				case 'fx':
					$returnValue=$this->fxFetchFromPost($principal);
					break;
				default: throw new WigiiBPLException("type '$type' is not supported. Should be one of 'element','activity','json','xml' or 'fx'", WigiiBPLException::INVALID_ARGUMENT);
			}
		}
		catch(Exception $e) {
			$this->executionSink()->publishEndOperationOnError("dataFetchFromPost", $e, $principal);
			throw $e;
		}
		$this->executionSink()->publishEndOperation("dataFetchFromPost", $principal);
		return $returnValue;
	}
	/**
	 * Fetches the record from the HTTP POST
	 * @param Principal $principal current principal executing the request
	 * @param Record $record posted data should be merged into the given Record
	 * @return Record the filled record
	 */
	protected function recordFetchFromPost($principal,$record) {
		WigiiBPLException::throwNotImplemented();
	}
	/**
	 * Fetches a json object from the HTTP POST
	 * @param Principal $principal current principal executing the request
	 * @param StdClass $obj posted data should be merged into the given StdClass instance
	 * @return StdClass the filled object
	 */
	protected function objectFetchFromPost($principal,$obj) {
		$returnValue=null;
		// gets post data
		if($_SERVER['REQUEST_METHOD']=='POST') {
			$data=trim(file_get_contents('php://input'));			
			if($data) {
				$returnValue=json_decode($data);
				if(json_last_error()) throw new WigiiBPLException("invalid json data.\n".$data, WigiiBPLException::INVALID_PARAMETER);
			}
			// merges data with current obj
			if($returnValue && $obj) {
				foreach($returnValue as $f=>$v) {
					$obj->{$f} = $v;
				}
				$returnValue=$obj;
			}		
			elseif($obj) $returnValue=$obj;
		}
		return $returnValue;
	}
	/**
	 * Fetches some xml from the HTTP POST
	 * @param Principal $principal current principal executing the request	 
	 * @return SimpleXmlElement the instanciated XML data
	 */
	protected function xmlFetchFromPost($principal) {
		$returnValue=null;
		// gets post data
		if($_SERVER['REQUEST_METHOD']=='POST') {
			$data=trim(file_get_contents('php://input'));		
			if($data) {
				$returnValue=simplexml_load_string($data);			
				if($returnValue===false) throw new WigiiBPLException("invalid xml data.\n".$data, WigiiBPLException::INVALID_PARAMETER);
			}
		}
		return $returnValue;
	}
	/**
	 * Fetches a FuncExp from the HTTP POST
	 * @param Principal $principal current principal executing the request
	 * @return FuncExp the instanciated FuncExp
	 */
	protected function fxFetchFromPost($principal) {
		$returnValue=null;
		// gets post data
		if($_SERVER['REQUEST_METHOD']=='POST') {
			$data=trim(file_get_contents('php://input'));
			if($data) {
				$returnValue=str2fx($data);				
			}
		}
		// sets Origin as Public
		if($returnValue instanceof FuncExp) $returnValue->setOriginIsPublic();
		return $returnValue;
	}
		
	/**
	 * Send email process using the underlying Wigii EmailService queue.
	 * @param Principal $principal authenticated user executing the Wigii business process
	 * @param Object $caller the object calling the Wigii business process.
	 * @param WigiiBPLParameter $parameter A WigiiBPLParameter instance with the following parameters :
	 * - to: String|Array|ValueList. The recipient email or list of recipients emails.
	 * - subject: String. The email subject.
	 * - content: String. The email content. Supports HTML.
	 * Optional parameters:
	 * - from: String. The sender's email address. If not specified uses the principal's email. If explicitely set to false, then uses the notification from address (no-reply@xxx)
	 * - hideRecipients: Boolean. If true, then recipients addresses are hidden to each other using bcc technology. (explicit cc list is always visible)
	 * - copySender: Boolean. If true, then sender receives a copy of the sent email.
	 * - mergeData: Array. Mail merge data array. See Wigii EmailService for more information on the format.
	 * - cc: String|Array|ValueList. Visible copy email or list of emails.
	 * - bcc: String|Array|ValueList. Hidden copy email or list of emails.
	 * - mailActivity: String|Activity. A specific activity from which to retrieve the email template. By default uses 'BaseEmail' activity. 
	 * If mailActivity is explicitely equal to false, then no template is loaded and original content is used as body. 
	 * If a mail activity is defined, then the bag of parameters is accessible through the $parameter variable allowing to do some mail merge activity.
	 * The $body variable is initialized with the provided email content.
	 * Standard variables are accessible like $p, $exec, $configS and $trm.	 
	 * @param ExecutionSink $executionSink an optional ExecutionSink instance that can be used to log Wigii business process actions.
	 * @throws WigiiBPLException|Exception in case of error
	 */
	public function sendEmail($principal, $caller, $parameter, $executionSink=null) {
	    $this->executionSink()->publishStartOperation("sendEmail", $principal);
	    $returnValue = null;
	    try {
	        if(is_null($principal)) throw new WigiiBPLException('principal cannot be null', WigiiBPLException::INVALID_ARGUMENT);
	        if(is_null($parameter)) throw new WigiiBPLException('parameter cannot be null', WigiiBPLException::INVALID_ARGUMENT);
	        
	        $to = $parameter->getValue('to');
	        if(empty($to) || ($to instanceOf ValueList) && $to->isEmpty()) throw new WigiiBPLException('recipients list cannot be empty', WigiiBPLException::INVALID_ARGUMENT);
	        
	        $exec = ServiceProvider::getExecutionService();
	        $configS = $this->getConfigService();
	        $p = $principal;
	        $trm = $this->getWigiiExecutor()->createTRM();
	        
	        // gets email activity and email template
	        $mailActivity = $parameter->getValue('mailActivity');
	        $body = $parameter->getValue('content');
	        if($mailActivity !== false) {
    	        if(!isset($mailActivity)) $mailActivity = Activity::createInstance("BaseEmail");
    	        if(!($mailActivity instanceof Activity)) $mailActivity = Activity::createInstance($mailActivity);
    	        $templatePath = $configS->getTemplatePath($p, $exec->getCrtModule(), $mailActivity);
    	        
    	        $options = $parameter;/* exports the bag into the $options variable to make it available into the template */
    	        // prepares email body based on email template    	       
    	        ob_start();
    	        include($templatePath);
    	        $body = ob_get_clean();
	        }
	        
	        // creates email instance
	        $emailS = $this->getEmailService();
	        $email = $emailS->getEmailInstance();
	        
	        // fills subject and body
	        $subject = $parameter->getValue('subject');
	        if(empty($subject)) $subject = $trm->getBaseEmailSubject();
	        $email->setSubject($subject);
	        $email->setBodyHtml($body);
	        
	        // fills senders address
	        $copySender = $parameter->getValue('copySender');
	        $from = $parameter->getValue('from');
	        if($from===false) $from = (string)$configS->getParameter($p, null, "emailNotificationFrom");
	        elseif(empty($from)) {
	            $from = $p->getValueInGeneralContext("email");
	            if(empty($from)) $from = (string)$configS->getParameter($p, null, "emailNotificationFrom");
	        }
	        if($from == (string)$configS->getParameter($p, null, "emailNotificationFrom")) $copySender = false;	        
	        // Checks for authorized direct sender
	        elseif(defined("EmailService_sendOnBehalfOfUser") && EmailService_sendOnBehalfOfUser) $emailS->isEmailAuthorizedDirectSender($p,$from,$p->getRealUsername());
	        $email->setFrom($from);
	        
	        // fills recipients
	        $email->clearRecipients();
	        $hideRecipients = ($parameter->getValue('hideRecipients')==true);
	        if(is_array($to)) {
	            foreach($to as $recEmail) {
	                if($recEmail != $from) {
	                    if($hideRecipients) $email->addBcc($recEmail);
	                    else $email->addTo($recEmail);
	                }
	            }
	        }
	        elseif($to instanceof ValueList) {
	            foreach($to->getListIterator() as $recEmail) {
	                if($recEmail != $from) {
	                    if($hideRecipients) $email->addBcc($recEmail);
	                    else $email->addTo($recEmail);
	                }
	            }
	        }
	        elseif($to != $from) {
	            if($hideRecipients) $email->addBcc($to);
	            else $email->addTo($to);
	        }
	        if($copySender) $email->addTo($from);
	        elseif($hideRecipients)  $email->addTo((string)$configS->getParameter($p, null, "emailNotificationFrom"));
	        
	        // Add cc
	        $cc = $parameter->getValue('cc');
	        if(is_array($cc)) {
	            foreach($cc as $recEmail) {
	                if($recEmail != $from) $email->addCc($recEmail);
	            }
	        }
	        elseif($cc instanceof ValueList) {
	            foreach($cc->getListIterator() as $recEmail) {
	                if($recEmail != $from) $email->addCc($recEmail);               
	            }
	        }
	        elseif(!empty($cc) && $cc != $from) {
	            $email->addCc($cc);
	        }
	        // Add bcc
	        $bcc = $parameter->getValue('bcc');
	        if(is_array($bcc)) {
	            foreach($bcc as $recEmail) {
	                if($recEmail != $from) $email->addBcc($recEmail);
	            }
	        }
	        elseif($bcc instanceof ValueList) {
	            foreach($bcc->getListIterator() as $recEmail) {
	                if($recEmail != $from) $email->addBcc($recEmail);
	            }
	        }
	        elseif(!empty($bcc) && $bcc != $from) {
	            $email->addBcc($bcc);
	        }
	        
	        // sends email using EmailService
	        if($email->hasRecipients()) $emailS->send($p,$email,$parameter->getValue('mergeData'));
	        elseif($executionSink) $executionSink->log('mail not sent, no recipients');
	    }
	    catch(Exception $e) {
	        $this->executionSink()->publishEndOperationOnError("sendEmail", $e, $principal);
	        throw $e;
	    }
	    $this->executionSink()->publishEndOperation("sendEmail", $principal);
	    return $returnValue;
	}
	
	/**
	 * Converts given HTML content to pdf format
	 * @param Principal $principal authenticated user executing the Wigii business process
	 * @param Object $caller the object calling the Wigii business process.
	 * @param WigiiBPLParameter $parameter A WigiiBPLParameter instance with the following parameters :
	 * - htmlContent: String. HTML content to be converted to pdf.
	 * Optional parameters:
	 * - pageOrientation: String. One of portrait or landscape. Defaults to portrait.
	 * - pageSize: String. A standard page size name, like A4. Defaults to A4. 
	 * - asEmailAttachement: Boolean. If true, outputs the pdf document as a base64 mime multi-part email attachment (RFC 2045)
	 * - fileName: String. pdf file name to be used when attached to emails.
	 * @param ExecutionSink $executionSink an optional ExecutionSink instance that can be used to log Wigii business process actions.
	 * @throws WigiiBPLException|Exception in case of error
	 * @return String raw pdf byte stream to be saved as a file or sent further.
	 */
	public function html2pdf($principal, $caller, $parameter, $executionSink=null) {
	    $this->executionSink()->publishStartOperation("html2pdf", $principal);
	    $returnValue = null;
	    $tcpdf = null;
	    try {
	        if(is_null($principal)) throw new WigiiBPLException('principal cannot be null', WigiiBPLException::INVALID_ARGUMENT);
	        if(is_null($parameter)) throw new WigiiBPLException('parameter cannot be null', WigiiBPLException::INVALID_ARGUMENT);
	        
	        // extracts parameters
	        $htmlContent = $parameter->getValue('htmlContent');
	        $pageOrientation = $parameter->getValue('pageOrientation');
	        switch($pageOrientation) {
	            case 'portrait': $pageOrientation = 'P'; break;
	            case 'landscape': $pageOrientation = 'L'; break;	   
	            default: $pageOrientation = 'P';
	        }
	        $pageSize = $parameter->getValue('pageSize');
	        if(!isset($pageSize)) $pageSize = 'A4';
	        $fileName = $parameter->getValue('fileName');
	        if(!isset($fileName)) $fileName = date('YmdHis');
	        
	        // creates tcpdf object instance
	        $tcpdf = TechnicalServiceProvider::getTCPDF($pageOrientation,$pageSize);
	        
	        // generates pdf from html
	        $tcpdf->AddPage();
	        $tcpdf->writeHTML($htmlContent);
	        
	        // gets output and frees tcpdf object
	        $returnValue = $tcpdf->Output($fileName,($parameter->getValue('asEmailAttachement')?'E':'S'));
	        if(isset($tcpdf)) $tcpdf->_destroy(true);	        
	    }
	    catch(Exception $e) {
	        $this->executionSink()->publishEndOperationOnError("html2pdf", $e, $principal);
	        if(isset($tcpdf)) $tcpdf->_destroy(true);
	        throw $e;
	    }	    
	    $this->executionSink()->publishEndOperation("html2pdf", $principal);
	    return $returnValue;
	}
	
	/**
	 * Evaluates a FuncExp in the context of the given Record.
	 * @param Principal $p principal executing the request
	 * @param FuncExp $fx the FuncExp instance to evaluate
	 * @param Record $rec record for which to get an FuncExpEvaluator. If null, returns a custom ElementEvaluator depending of current module.
	 * @return mixed FuncExp result
	 */
	public function evaluateFuncExp($principal,$fx,$rec=null) {
	    return $this->getWigiiExecutor()->evaluateFuncExp($principal, ServiceProvider::getExecutionService(), $fx, $rec);
	}
	
	/**
	 * Evaluates a Configuration Parameter which can be either a constant or a FuncExp.
	 * @param Principal $p principal executing the request
	 * @param String $parameter the configuration parameter to evaluate
	 * @param Record $rec record for which to get an FuncExpEvaluator. If null, returns a custom ElementEvaluator depending of current module.
	 * @return mixed FuncExp result
	 */
	public function evaluateConfigParameter($p,$parameter,$rec=null) {
	    return $this->getWigiiExecutor()->evaluateConfigParameter($p, ServiceProvider::getExecutionService(), $parameter, $rec);
	}
	
	/**
	 * Generates a complete module xml configuration which uses (by inclusion) the fields defined in a given configuration file.
	 * @param Principal $principal authenticated user executing the Wigii business process
	 * @param Object $caller the object calling the Wigii business process.
	 * @param WigiiBPLParameter $parameter A WigiiBPLParameter instance with the following parameters :
	 * - xmlFile: String. The name of an existing Wigii configuration file to load.
	 * - moduleName: String. The name of the module to use.
	 * - moduleConfigTemplate: String. The name of the module configuration template to use as a base. Defaults to config_moduleTemplate.xml
	 * - outputAsString: Boolean. If true, module xml configuration is returned as string, else it is returned as a SimpleXmlElement. Defaults to true.
	 * @param ExecutionSink $executionSink an optional ExecutionSink instance that can be used to log Wigii business process actions.
	 * @throws WigiiBPLException|Exception in case of error
	 * @return String|SimpleXMLElement
	 */
	public function useConfig($principal, $caller, $parameter, $executionSink=null) {
	    $this->executionSink()->publishStartOperation("useConfig", $principal);
	    $returnValue = null;
	    try {
	        if(is_null($principal)) throw new WigiiBPLException('principal cannot be null', WigiiBPLException::INVALID_ARGUMENT);
	        if(is_null($parameter)) throw new WigiiBPLException('parameter cannot be null', WigiiBPLException::INVALID_ARGUMENT);
	        
	        $returnValue = sel($principal, moduleXml2df($parameter->getValue('xmlFile')),dfasl(
                dfas("ReduceCfgFieldForIncludeDFA"),
                dfas("CfgField2ModuleXmlDFA",
                    "setModuleConfigTemplateFilename",$parameter->getValue('moduleConfigTemplate'),
                    "setModuleName",$parameter->getValue('moduleName'),
                    "setOutputAsString",$parameter->getValue('outputAsString')!==false)
            ));
	    }
	    catch(Exception $e) {
	        $this->executionSink()->publishEndOperationOnError("useConfig", $e, $principal);
	        throw $e;
	    }
	    $this->executionSink()->publishEndOperation("useConfig", $principal);
	    return $returnValue;
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
	
	private $groupConfigCache=null;
	/**
	 * Returns the group to be used as a config group, given a group as a point of reference.
	 * Checks if there is a group config in the group hierarchy and returns the closest one.
	 * If no group config is available, then returns the given group.
	 * @param Principal $principal authenticated user performing the operation
	 * @param int|Group|GroupP $group group ID or group instance or GroupP instance for which to check if a configuration file is available.
	 * @return Group
	 */
	public function getConfigGroupForGroup($principal, $group) {
	    $this->debugLogger()->logBeginOperation('getConfigGroupForGroup');
	    if(!isset($group)) throw new WigiiBPLException('group cannot be null, should be a valid group ID or a group instance', WigiiBPLException::INVALID_ARGUMENT);
	    $returnValue=null;	    
	    if(!is_object($group)) {
	        $group = $this->getGroupAdminService()->getGroupWithoutDetail($principal, $group);
	    }
	    $group = $group->getDbEntity();
	    if(!isset($this->groupConfigCache)) $this->groupConfigCache = array();
	    $returnValue = $this->groupConfigCache[$group->getId()];
	    if(!$returnValue) {
    	    $returnValue = $this->isConfigGroupAvailableForGroup($principal, $group);
    	    if(!$returnValue) $returnValue = $group;
    	    $this->groupConfigCache[$group->getId()] = $returnValue;
	    }
	    $this->debugLogger()->logEndOperation('getConfigGroupForGroup');
	    return $returnValue;
	}
	
	/**
	 * Returns group accessible by default by the principal in the given module
	 * @param Principal $principal authenticated user performing the operation
	 * @param Module|String $module module for which to get the default accessible group
	 * @param WigiiBPLParameter $options optional bag of parameters to configure the default group calculation. It supports:
	 * - namespace: WigiiNamespace|String. WigiiNamespace if different from current namespace
	 * - write: Boolean. If true, then forces to get a group with write rights. If false, then gets first group at least with read rights. Default to false.
	 * @return GroupP GroupP or null if principal cannot access given module
	 */
	public function getDefaultGroupForModule($principal, $module, $options=null) {
	    $this->debugLogger()->logBeginOperation('getDefaultGroupForModule');
	    if(!isset($principal)) throw new WigiiBPLException('principal cannot be null', WigiiBPLException::INVALID_ARGUMENT);
	    if(!isset($module)) throw new WigiiBPLException('module cannot be null', WigiiBPLException::INVALID_ARGUMENT);
	    if(!($module instanceof Module)) $module = ServiceProvider::getModuleAdminService()->getModule($principal, $module);
	    $wigiiNamespace = null;
	    $write=false;
	    if(isset($options)) {
	        $wigiiNamespace = $options->getValue('namespace');
	        if(isset($wigiiNamespace) && !($wigiiNamespace instanceof WigiiNamespace)) {
	            $wigiiNamespace = ServiceProvider::getWigiiNamespaceAdminService()->getWigiiNamespace($principal, $wigiiNamespace);
	        }
	        $write = ($options->getValue('write')==true);
	    }
	    
	    $returnValue=null;
	    // if group belongs to another namespace, tries to bind principal to new namespace
	    if(isset($wigiiNamespace) && $wigiiNamespace->getWigiiNamespaceName() != $principal->getWigiiNamespace()->getWigiiNamespaceName()) {
	        $origNS = $principal->getWigiiNamespace();
	        $principal->bindToWigiiNamespace($wigiiNamespace);
	    }
	    else $origNS = null;
	    // gets first accessible (or write) group
	    if($write) $returnValue = $this->getWigiiExecutor()->getConfigurationContext()->getFirstWriteGroupInModule($principal,$module);	    
	    else $returnValue = $this->getWigiiExecutor()->getConfigurationContext()->getFirstReadGroupInModule($principal,$module);
		// if no group found, tries to force ConfigurationContext cache refresh
	    if(!isset($returnValue)) {
	        $this->debugLogger()->write('no accessible group found in cache, refreshes ConfigurationContext');
	        $this->getWigiiExecutor()->getConfigurationContext()->getGroupPList($principal, $module);
	        if($write) $returnValue = $this->getWigiiExecutor()->getConfigurationContext()->getFirstWriteGroupInModule($principal,$module);
	        else $returnValue = $this->getWigiiExecutor()->getConfigurationContext()->getFirstReadGroupInModule($principal,$module);
	    }
		// if no group found, tries to fetch again database to ensure query on fresh data
	    if(!isset($returnValue)) {
	        $this->debugLogger()->write('no accessible group found in cache, queries database');
	        $returnValue = GroupListAdvancedImpl::createInstance(false);
	        $groupAS = $this->getGroupAdminService();
	        $groupAS->getAllGroups($principal, $module, $returnValue,lf($groupAS->getFieldSelectorListForGroupWithoutDetail()));
	        if($write) $returnValue = $returnValue->getWriteGroups()->getFirst();
	        else $returnValue = $returnValue->getReadGroups()->getFirst();
	    }
	    // checks result data
	    if(isset($returnValue)) {
	        // write ==> getRights->canWriteElement
	        if($write && (is_null($returnValue->getRights()) || !$returnValue->getRights()->canWriteElement())) {
	            $this->debugLogger()->write('write request, but no write group found');
	            $returnValue = null;
	        }
	        // read ==> getRights not null
	        elseif(!$write && is_null($returnValue->getRights())) {
	            $this->debugLogger()->write('read request, but no accessible group found');
	            $returnValue = null;
	        }
	    }
	    if(isset($origNS)) $principal->bindToWigiiNamespace($origNS);
	    $this->debugLogger()->logEndOperation('getDefaultGroupForModule');
	    return $returnValue;
	}
	
	private $getUGRCache=null;
	/**
	 * Returns any existing explicit User Group Right object between the given user id and group id.
	 * Does not calculate rights based on rights inheritance. Returns null if no explicit UGR exists in database between this group and user.
	 * @param Principal $principal authenticated user performing the operation
	 * @param int $groupId existing group id in database. No check is done on the validity of the group id.
	 * @param int $userId existing user id in the databse. No check is done on the validity of the user id.
	 * @return UGR or null if nothing exists in database
	 */
	public function getUGR($principal, $groupId, $userId) {
	    $this->debugLogger()->logBeginOperation('getUGR');
	    $returnValue=null;
	    // checks arguments
	    if(!isset($groupId)) throw new WigiiBPLException('groupId cannot be null', WigiiBPLException::INVALID_ARGUMENT);
	    if(!isset($userId)) throw new WigiiBPLException('userId cannot be null', WigiiBPLException::INVALID_ARGUMENT);
	    // checks if UGR is already in cache
	    if(!isset($this->getUGRCache)) $this->getUGRCache = array();
	    $returnValue = $this->getUGRCache[$groupId."_".$userId];
	    if($returnValue===null) {
    	    // builds sql
    	    $mySqlF = TechnicalServiceProvider::getMySqlFacade();	    
    	    $sqlB = $mySqlF->getSqlBuilder();
    	    $id_user = $sqlB->formatBinExp('UGR.id_user', '=', $userId, MySqlQueryBuilder::SQLTYPE_INT);
    	    $id_group = $sqlB->formatBinExp('UGR.id_group', '=', $groupId, MySqlQueryBuilder::SQLTYPE_INT);
    	    $sql = "select UGR.id_user, UGR.id_group, UGR.canModify, UGR.canWriteElement, UGR.canShareElement from Users_Groups_Rights as UGR where $id_user and $id_group";
    	    // queries database
    	    $dbCS = ServiceProvider::getDbAdminService()->getDbConnectionSettings($principal);
    	    $returnValue = $mySqlF->selectOne($principal, $sql, $dbCS);
    	    // builds result
    	    if(isset($returnValue)) $returnValue = UGR::createInstance($groupId,$userId,$returnValue);
    	    // puts result in cache. Explicit false if nothing in db
    	    $this->getUGRCache[$groupId."_".$userId] = (isset($returnValue)?$returnValue:false);
	    }
	    // if cache recorded that no UGR in db, then returns null
	    elseif($returnValue===false) $returnValue = null;
	    $this->debugLogger()->logEndOperation('getUGR');
	    return $returnValue;
	}
	
	/**
	 * Given a group, finds the closest group (in the hierarchy) having a configuration file.
	 * If no group with config is found then returns null.
	 * @param Principal $principal authenticated user performing the operation
	 * @param int|Group|GroupP $group group ID or group instance or GroupP instance for which to check if a configuration file is available.
	 * @return Group returns found Group with configuration file or null if not found.
	 */
	public function isConfigGroupAvailableForGroup($principal, $group){
	    if(!isset($group)) throw new WigiiBPLException('group cannot be null, should be a valid group ID or a group instance', WigiiBPLException::INVALID_ARGUMENT);
	    $returnValue=null;
	    if(!is_object($group)) {
	        $group = $this->getGroupAdminService()->getGroupWithoutDetail($principal, $group);
	    }
	    $cc = $this->getConfigService();
	    $origNS = $principal->getWigiiNamespace();
	    $principal->bindToWigiiNamespace($group->getWigiiNamespace());
	    if($cc->doesGroupHasConfigFile($principal, $group)) $returnValue = $group;
	    else if($cc instanceof ConfigurationContextImpl) {
	        $parentGroup = $cc->isConfigGroupAvailableForGroup($principal, $group);
	        if(is_object($parentGroup)) $returnValue = $parentGroup;
	    }
	    $principal->bindToWigiiNamespace($origNS);
	    return (isset($returnValue)? $returnValue->getDbEntity():$returnValue);
	}
	
	/**
	 * Creates a FieldSelectList instance containing all calculated on fetch dependencies for the given sub elements. 
	 * @param Principal $principal current principal running the operation
	 * @param int $parentElementId parent element ID of the requested sub elements
	 * @param string $linkName the field of type Links containing the sub elements
	 * @param FieldSelectorList $currentFsl current FieldSelectorList to be cloned and expanded with the FuncExp dependencies.
	 * @return FieldSelectorList the new FieldSelectorList instance containing all the required field selectors
	 */
	public function buildFslForSubElementWithFxDependencies($principal,$parentElementId,$linkName,$currentFsl) {
	    $this->debugLogger()->logBeginOperation('buildFslForSubElementWithFxDependencies');
	    $returnValue = FieldSelectorListArrayImpl::createInstance(false,false,$currentFsl);
        $seCS = $this->getConfigService();
        $seCS->selectSubElementsConfig($principal, $parentElementId, $linkName);
        $fieldList = FormFieldList :: createInstance(null);
        $seCS->getFields($principal, $seCS->getCurrentModule(), null, $fieldList);
        foreach ($fieldList->getListIterator() as $field) {
            // Gets dependencies of calculated on fetch fields, present in the actual field selector list
            if ($field->isCalculated() && $field->shouldCalculateOnFetch() && $returnValue->containsField($field->getFieldName())) {
                $field->getFuncExpDependencies($returnValue);
            }
        }
        $this->debugLogger()->logEndOperation('buildFslForSubElementWithFxDependencies');
        return $returnValue;
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
