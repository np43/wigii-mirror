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
 * Wigii service provider
 * Created by CWE on 2 juin 09
 */
class ServiceProvider
{
	private $_debugLogger;
	private $_executionSink;
	private function debugLogger()
	{
		if(!isset($this->_debugLogger))
		{
			$this->_debugLogger = DebugLogger::getInstance("ServiceProvider");
		}
		return $this->_debugLogger;
	}
	private function executionSink()
	{
		if(!isset($this->_executionSink))
		{
			$this->_executionSink = ExecutionSink::getInstance("ServiceProvider");
		}
		return $this->_executionSink;
	}

	// singleton implementation
	private static $singleton;

	protected static function getInstance()
	{
		if(!isset(self::$singleton))
		{
			self::$singleton = new ServiceProvider();
			self::$singleton->initialize();
		}
		return self::$singleton;
	}

	/**
	 * Returns true if ServiceProvider singleton is instanciated
	 */
	protected static function isUp()
	{
		return isset(self::$singleton);
	}

	/**
	 * Registers a service provider subclass as current singleton instance
	 * throws ServiceProviderException if an error occurs
	 */
	protected static function registerSingleInstance($serviceProvider)
	{
		if(isset(self::$singleton)) throw new ServiceProviderException("Service Provider has already been initialized, cannot change it dynamically", ServiceProviderException::FORBIDDEN);
		self::$singleton = $serviceProvider;
		self::$singleton->initialize();
	}

	private function initialize()
	{
		$this->getSystemPrincipals();
	}

	// System principals management

	private $systemPrincipals;
	/**
	 * Returns the list of actual system principals owned by the ServiceProvider
	 */
	protected function getSystemPrincipals()
	{
		//autowired
		if(!isset($this->systemPrincipals))
		{
			// creates system principals
			$this->systemPrincipals = PrincipalListArrayImpl::createInstance();
			// gets default client
			$wigiiNamespace = $this->getDefaultSystemPrincipalWigiiNamespace();
			$moduleAccess = $this->getDefaultSystemPrincipalModuleAccess();
			// creates all system principals
			$this->createAllSystemPrincipals($this->systemPrincipals, $wigiiNamespace, $moduleAccess);
			// informs TechnicalServiceProvider of principal list
			TechnicalServiceProvider::addSystemPrincipal($this->systemPrincipals);
		}
		return $this->systemPrincipals;
	}

	/**
	 * Fills a list with freshly created system principal instances
	 */
	protected function createAllSystemPrincipals($principalList, $wigiiNamespace, $moduleAccess)
	{
		// gets system principals from AuthorizationService
		$this->getAuthorizationServiceInstance()->createAllSystemPrincipals($principalList, $wigiiNamespace, $moduleAccess);
	}
	/**
	 * Returns default wigiiNamespace to associate with system principals
	 * this implementation returns WigiiNamespaceService->emptyWigiiNamespaceForDefaultClient
	 */
	protected function getDefaultSystemPrincipalWigiiNamespace()
	{
		return $this->getWigiiNamespaceAdminServiceInstance()->getEmptyWigiiNamespaceForDefaultClient();
	}
	/**
	 * Returns default moduleAccess to associate with system principals
	 * this implementation returns null
	 */
	protected function getDefaultSystemPrincipalModuleAccess()
	{
		return null;
	}

	// configuration
	
	/**
	 * Stores an ObjectConfigurator instance that will be used to configure
	 * each created object of the given class.
	 * Note that not all classes support this dynamic configuration pattern,
	 * this depends of the implementation decision took in each createXXX method.
	 * @param $className the class name for which to apply the configuration when objects are instanciated
	 * @param $objectConfigurator an ObjectConfigurator instance which is used to configure the new created instance
	 */
	public static function configureClass($className, $objectConfigurator) {
		TechnicalServiceProvider::configureClass($className, $objectConfigurator);
	}
	
	/**
	 * Stores an array of ObjectConfigurator instances that will be used to configure
	 * each created object of the given classes
	 * Note that not all classes support this dynamic configuration pattern,
	 * this depends of the implementation decision took in each createXXX method.
	 * @param $classConfigurations an array with key=className, value=ObjectConfigurator instance
	 */
	public static function configureClasses($classConfigurations) {
		TechnicalServiceProvider::configureClasses($classConfigurations);
	}
	
	/**
	 * Configures a given object instance using 
	 * the stored configuration if exists.
	 * @param $obj any object
	 */
	public static function configureObject($obj) {
		TechnicalServiceProvider::configureObject($obj);
	}
	
	// static service providing

	/**
	 * Returns an instance of wigii Authentication service
	 * throws ServiceProviderException if an error occurs
	 */
	public static function getAuthenticationService()
	{
		return ServiceProvider::getInstance()->getAuthenticationServiceInstance();
	}

	/**
	 * Returns an instance of wigii Authorization service
	 * throws ServiceProviderException if an error occurs
	 */
	public static function getAuthorizationService()
	{
		return ServiceProvider::getInstance()->getAuthorizationServiceInstance();
	}

	/**
	 * Returns an instance of wigii Config service
	 * throws ServiceProviderException if an error occurs
	 */
	public static function getConfigService()
	{
		return ServiceProvider::getInstance()->getConfigServiceInstance();
	}

	/**
	 * Returns an instance of wigii Element service
	 * throws ServiceProviderException if an error occurs
	 */
	public static function getElementService()
	{
		return ServiceProvider::getInstance()->getElementServiceInstance();
	}
	
	/**
	 * Returns an instance of an ElementLazyLoader
	 * throws ServiceProviderException if an error occurs
	 */
	public static function getElementLazyLoader()
	{
		return ServiceProvider::getInstance()->getElementLazyLoaderInstance();
	}

	/**
	 * Returns an instance of wigii GroupAdmin service
	 * throws ServiceProviderException if an error occurs
	 */
	public static function getGroupAdminService()
	{
		return ServiceProvider::getInstance()->getGroupAdminServiceInstance();
	}

	/**
	 * Returns an instance of wigii UserAdmin service
	 * throws ServiceProviderException if an error occurs
	 */
	public static function getUserAdminService()
	{
		return ServiceProvider::getInstance()->getUserAdminServiceInstance();
	}

	/**
	 * Returns an instance of wigii WigiiNamespaceAdmin service
	 * throws ServiceProviderException if an error occurs
	 */
	public static function getWigiiNamespaceAdminService()
	{
		return ServiceProvider::getInstance()->getWigiiNamespaceAdminServiceInstance();
	}

	/**
	 * Returns an instance of wigii ClientAdmin service
	 * throws ServiceProviderException if an error occurs
	 */
	public static function getClientAdminService()
	{
		return ServiceProvider::getInstance()->getClientAdminServiceInstance();
	}

	/**
	 * Returns an instance of wigii ModuleAdmin service
	 * throws ServiceProviderException if an error occurs
	 */
	public static function getModuleAdminService()
	{
		return ServiceProvider::getInstance()->getModuleAdminServiceInstance();
	}

	/**
	 * Returns an instance of wigii DbAdmin service
	 * throws ServiceProviderException if an error occurs
	 */
	public static function getDbAdminService()
	{
		return ServiceProvider::getInstance()->getDbAdminServiceInstance();
	}

	/**
	 * Returns an instance of wigii SessionAdmin service
	 * throws ServiceProviderException if an error occurs
	 */
	public static function getSessionAdminService()
	{
		return ServiceProvider::getInstance()->getSessionAdminServiceInstance();
	}

	/**
	 * Returns an instance of wigii TranslationAdmin service
	 * throws ServiceProviderException if an error occurs
	 */
	public static function getTranslationService()
	{
		return ServiceProvider::getInstance()->getTranslationServiceInstance();
	}

	/**
	 * Returns an instance of wigii Execution service
	 * throws ServiceProviderException if an error occurs
	 */
	public static function getExecutionService()
	{
		return ServiceProvider::getInstance()->getExecutionServiceInstance();
	}
	/**
	 * Returns an instance of wigii SystemConsole service
	 * throws ServiceProviderException if an error occurs
	 */
	public static function getSystemConsoleService()
	{
		return ServiceProvider::getInstance()->getSystemConsoleServiceInstance();
	}

	/**
	 * Returns an instance of a RecordEvaluator given its className
	 * the class can be a customized class stored in client config folder
	 * if className is null then returns default system RecordEvaluator
	 * throws ServiceProviderException if an error occurs
	 */
	public static function getRecordEvaluator($principal, $className=null)
	{
		return ServiceProvider::getInstance()->getRecordEvaluatorInstance($principal, $className);
	}

	/**
	 * Returns an instance of an ElementEvaluator given its className
	 * the class can be a customized class stored in client config folder
	 * if className is null then returns default system ElementEvaluator
	 * throws ServiceProviderException if an error occurs
	 */
	public static function getElementEvaluator($principal, $className=null)
	{
		return ServiceProvider::getInstance()->getElementEvaluatorInstance($principal, $className);
	}

	/**
	 * Returns an instance of an ElementPolicyEvaluator given its className
	 * the class can be a customized class stored in client config folder
	 * if className is null then returns default system ElementPolicyEvaluator
	 * throws ServiceProviderException if an error occurs
	 */
	public static function getElementPolicyEvaluator($principal, $className=null)
	{
		return ServiceProvider::getInstance()->getElementPolicyEvaluatorInstance($principal, $className);
	}
	
	/**
	 * Returns an instance of a FuncExpVM
	 * @param Principal $principal the principal for which to run the VM
	 * @param FuncExpEvaluator $parentEvaluator a valid FuncExpEvaluator instance used as a parent
	 * @param String $className the FuncExpVM class to use, defaults to FuncExpVM
	 */
	public static function getFuncExpVM($principal, $parentEvaluator=null, $className=null)
	{
		return ServiceProvider::getInstance()->getFuncExpVMInstance($principal, $parentEvaluator, $className);
	}
	
	/**
	 * Returns an instance of Wigii DataFlow service
	 * throws ServiceProviderException if an error occurs
	 */
	public static function getDataFlowService()
	{
		return ServiceProvider::getInstance()->getDataFlowServiceInstance();
	}
	
	/**
	 * Returns an instance of Wigii Business Process Library
	 * throws ServiceProviderException if an error occurs
	 */
	public static function getWigiiBPL()
	{
		return ServiceProvider::getInstance()->getWigiiBPLInstance();
	}
	
	/**
	 * Returns an instance of a class with exlusive access
	 * The class should implement the methods reset(), freeMemory() and isLockedForUse()
	 * When providing an instance, the service provider checks if it is lockedForUse, 
	 * if yes, then creates a new instance, resets it and returns it
	 * else resets the existing object and returns it.
	 * The method reset should set the flag isLockedForUse to true.
	 * The method freeMemory() should reset the flag isLockedForUse to false.
	 * throws ServiceProviderException in case of error
	 */
	public static function getExclusiveAccessObject($className) {
		return ServiceProvider::getInstance()->getExclusiveAccessObjectInstance($className, false /*no system principals injection*/);
	}
	
	/**
	 * Creates an instance of a class belonging to the Wigii system
	 * Checks the validity of the class through the wigii_autoload function
	 */
	public static function createWigiiObject($className) {
		return ServiceProvider::getInstance()->createWigiiObjectInstance($className);
	}
	
	/**
	 * Returns an instance of a GroupBasedWigiiApiClient centered on the given group log exp or group list.
	 * @param Principal $principal principal used to select the groups.
	 * @param LogExp|GroupList $groupLogExp the group log exp used to center the api client on some groups or a group list instance. 
	 * @return GroupBasedWigiiApiClient an instance of a GroupBasedWigiiApiClient
	 */
	public static function getGroupBasedWigiiApiClient($principal, $groupLogExp) {
		return ServiceProvider::getInstance()->getGroupBasedWigiiApiClientInstance($principal, $groupLogExp);
	}
	
	/**
	 * Returns an instance of a sub element config service
	 * @return ConfigServiceSubElementImpl an instance of a ConfigServiceSubElementImpl
	 */
	public static function getSubElementConfigService() {
		return ServiceProvider::getInstance()->getSubElementConfigServiceInstance();		
	}
	/**
	 * Clears Sub element config service session data	
	 */
	public static function clearSessionDataOfSubElementConfigService() {
		ServiceProvider::getInstance()->doClearSessionDataOfSubElementConfigService();
	}
	
	// service providing

	private $authenticationService;

	/**
	 * default as singleton
	 */
	protected function getAuthenticationServiceInstance()
	{
		if(!isset($this->authenticationService))
		{
			// creates authentication service
			$this->authenticationService = $this->createAuthenticationServiceInstance($this->getSystemPrincipals());
		}
		return $this->authenticationService;
	}

	private $authorizationService;

	/**
	 * default as singleton
	 */
	protected function getAuthorizationServiceInstance()
	{
		if(!isset($this->authorizationService))
		{
			$this->authorizationService = $this->createAuthorizationServiceInstance();
		}
		return $this->authorizationService;
	}

	private $configService;

	/**
	 * default as singleton
	 */
	protected function getConfigServiceInstance()
	{
		if(!isset($this->configService))
		{
			$this->configService = $this->createConfigServiceInstance();
		}
		return $this->configService;
	}

	private $elementService;

	/**
	 * default as singleton
	 */
	protected function getElementServiceInstance()
	{
		if(!isset($this->elementService))
		{
			$this->elementService = $this->createElementServiceInstance();
			// adds system principals
			$this->elementService->addSystemPrincipal($this->getSystemPrincipals());
		}
		return $this->elementService;
	}
	
	/**
	 * default as single call
	 */
	protected function getElementLazyLoaderInstance()
	{
		return $this->createWigiiObject('ElementLazyLoader');
	}

	private $groupAdminService;

	/**
	 * default as singleton
	 */
	protected function getGroupAdminServiceInstance()
	{
		if(!isset($this->groupAdminService))
		{
			$this->groupAdminService = $this->createGroupAdminServiceInstance();
		}
		return $this->groupAdminService;
	}

	private $userAdminService;

	/**
	 * default as singleton
	 */
	protected function getUserAdminServiceInstance()
	{
		if(!isset($this->userAdminService))
		{
			$this->userAdminService = $this->createUserAdminServiceInstance();
		}
		return $this->userAdminService;
	}

	private $wigiiNamespaceAdminService;

	/**
	 * default as singleton
	 */
	protected function getWigiiNamespaceAdminServiceInstance()
	{
		if(!isset($this->wigiiNamespaceAdminService))
		{
			$this->wigiiNamespaceAdminService = $this->createWigiiNamespaceAdminServiceInstance();
		}
		return $this->wigiiNamespaceAdminService;
	}

	private $clientAdminService;

	/**
	 * default as singleton
	 */
	protected function getClientAdminServiceInstance()
	{
		if(!isset($this->clientAdminService))
		{
			$this->clientAdminService = $this->createClientAdminServiceInstance();
		}
		return $this->clientAdminService;
	}

	private $moduleAdminService;

	/**
	 * default as singleton
	 */
	protected function getModuleAdminServiceInstance()
	{
		if(!isset($this->moduleAdminService))
		{
			$this->moduleAdminService = $this->createModuleAdminServiceInstance();
		}
		return $this->moduleAdminService;
	}

	private $dbAdminService;

	/**
	 * default as singleton
	 */
	protected function getDbAdminServiceInstance()
	{
		if(!isset($this->dbAdminService))
		{
			$this->dbAdminService = $this->createDbAdminServiceInstance();
		}
		return $this->dbAdminService;
	}

	private $sessionAdminService;

	/**
	 * default as singleton
	 */
	protected function getSessionAdminServiceInstance()
	{
		if(!isset($this->sessionAdminService))
		{
			$this->sessionAdminService = $this->createSessionAdminServiceInstance();
		}
		return $this->sessionAdminService;
	}

	private $translationService;

	/**
	 * default as singleton
	 */
	protected function getTranslationServiceInstance()
	{
		if(!isset($this->translationService))
		{
			$this->translationService = $this->createTranslationServiceInstance();
		}
		return $this->translationService;
	}

	private $executionService;

	/**
	 * default as singleton
	 */
	protected function getExecutionServiceInstance()
	{
		if(!isset($this->executionService))
		{
			$this->executionService = $this->createExecutionServiceInstance();
		}
		return $this->executionService;
	}

	private $systemConsoleService;
	/**
	 * default as singleton
	 */
	protected function getSystemConsoleServiceInstance()
	{
		if(!isset($this->systemConsoleService))
		{
			$this->systemConsoleService = $this->createSystemConsoleServiceInstance();
		}
		return $this->systemConsoleService;
	}

	private $recordEvaluators;
	/**
	 * default as singleton classes, which are reset at each call.
	 * If the singleton isLockedForEvaluation then create a new instance and does not cache it
	 */
	protected function getRecordEvaluatorInstance($principal, $className=null)
	{
		if(!isset($this->recordEvaluators)) $this->recordEvaluators = array();
		// if className is null then uses default
		if(is_null($className) || ($className == '')) $className = $this->getDefaultRecordEvaluatorClass();
		// looks in cache
		$returnValue = $this->recordEvaluators[$className];
		// not in cache then creates instance
		if(!isset($returnValue))
		{
			$returnValue = $this->createClientClassInstanceOrStandard($principal, $className);
			// checks that it is a RecordEvaluator
			if(!($returnValue instanceof RecordEvaluator)) throw new ServiceProviderException("$className is not a subclass of a RecordEvaluator", ServiceProviderException::INVALID_ARGUMENT);
			$this->recordEvaluators[$className] = $returnValue;
		} else {
			//if recordEvaluator is found, check if isLockedForEvaluation
			//in this case create a new instance and do not cache it
			if($returnValue->isLockedForEvaluation()){
				$returnValue = $this->createClientClassInstanceOrStandard($principal, $className);
				// checks that it is a RecordEvaluator
				if(!($returnValue instanceof RecordEvaluator)) throw new ServiceProviderException("$className is not a subclass of a RecordEvaluator", ServiceProviderException::INVALID_ARGUMENT);
			}
		}
		$returnValue->reset();
		return $returnValue;
	}
	protected function getDefaultRecordEvaluatorClass()
	{
		return "RecordEvaluator";
	}

	/**
	 * default as singleton classes, which are reset at each call.
	 */
	protected function getElementEvaluatorInstance($principal, $className=null)
	{
		// if className is null then uses default
		if(is_null($className) || ($className == '')) $className = $this->getDefaultElementEvaluatorClass();
		$returnValue = $this->getRecordEvaluatorInstance($principal, $className);
		// checks that it is an ElementEvaluator
		if(!($returnValue instanceof ElementEvaluator)) throw new ServiceProviderException("$className is not a subclass of a ElementEvaluator", ServiceProviderException::INVALID_ARGUMENT);
		return $returnValue;
	}
	protected function getDefaultElementEvaluatorClass()
	{
		return "ElementEvaluator";
	}

	private $elementPolicyEvaluators;
	/**
	 * defaults as exclusive access object that can be located in the client config folder as well.
	 */
	protected function getElementPolicyEvaluatorInstance($principal, $className=null) {
		if(!isset($this->elementPolicyEvaluators)) $this->elementPolicyEvaluators = array();
		$configurator = null;
		// if className is null then uses default
		if(empty($className)) {
			$className = $this->getDefaultElementPolicyEvaluatorClass();
			$cacheKey = $className;			
		}
		// else extracts any configuration
		else {
			$cacheKey = $className;
			$s = $className;
			$params = strpos($s, '(');
			if($params > 0) {				
				$className = substr($s, 0, $params);	
				$cacheKey = $className;
				$params = substr($s, $params+1, -1);
				if(!empty($params)) {
					$cacheKey = $className.'('.md5($params).')';
					// parses params func exp and evaluates it
					$params = evalfx($principal, $params);
					// prepares configurator
					if(!($params instanceof ObjectConfigurator)) {
						$configurator = ObjectConfigurator::createInstance($params);
					}
					else $configurator = $params;
				}				
			}
		}
		
		// looks in cache
		$returnValue = $this->elementPolicyEvaluators[$cacheKey];
		// not in cache then creates instance
		if(!isset($returnValue))
		{
			$returnValue = $this->createClientClassInstanceOrStandard($principal, $className);
			// checks that it is an ElementPolicyEvaluator
			if(!($returnValue instanceof ElementPolicyEvaluator)) throw new ServiceProviderException("$className is not a subclass of a ElementPolicyEvaluator", ServiceProviderException::INVALID_ARGUMENT);
			// applies special configuration
			if(isset($configurator)) $configurator->configure($returnValue);
			// puts in cache
			$this->elementPolicyEvaluators[$cacheKey] = $returnValue;
		} else {
			//if elementPolicyEvaluator is found, check if isLockedForUse
			//in this case create a new instance and do not cache it
			if($returnValue->isLockedForUse()){
				$returnValue = $this->createClientClassInstanceOrStandard($principal, $className);
				// checks that it is an ElementPolicyEvaluator
				if(!($returnValue instanceof ElementPolicyEvaluator)) throw new ServiceProviderException("$className is not a subclass of a ElementPolicyEvaluator", ServiceProviderException::INVALID_ARGUMENT);
				// applies special configuration
				if(isset($configurator)) $configurator->configure($returnValue);
			}
		}
		$returnValue->reset();
		return $returnValue;
	}
	protected function getDefaultElementPolicyEvaluatorClass() {
		return "ElementPolicyEvaluatorBaseImpl";
	}
	
	/**
	 * defaults as exclusive access object
	 */
	protected function getFuncExpVMInstance($principal, $parentEvaluator=null, $className=null) {
		if(empty($className)) $className = $this->getDefaultFuncExpVMClass();
		$returnValue = $this->getExclusiveAccessObjectInstance($className, true);
		if(!($returnValue instanceof FuncExpVM)) {
			try {if(isset($returnValue)) $returnValue->freeMemory();}catch(Exception $e) {/*nothing to do*/}
			throw new ServiceProviderException("funcExp VM instance is not an instance of FuncExpVM, but of ".get_class($returnValue), ServiceProviderException::INVALID_ARGUMENT);
		}
		// sets parent evaluator and principal
		if(isset($parentEvaluator)) $returnValue->setParentFuncExpEvaluator($parentEvaluator);
		// sets reference to FuncExpVM if parent evaluator is a RecordEvaluator
		if($parentEvaluator instanceof RecordEvaluator) $parentEvaluator->setFuncExpVM($returnValue);
		$returnValue->setPrincipal($principal);
		return $returnValue;
	}
	protected function getDefaultFuncExpVMClass() {
		return "FuncExpVM";
	}
	
	private $dataFlowService;
	/**
	 * default as singleton
	 */
	protected function getDataFlowServiceInstance()
	{
		if(!isset($this->dataFlowService))
		{
			$this->dataFlowService = $this->createDataFlowServiceInstance();
			// adds system principals
			$this->dataFlowService->addSystemPrincipal($this->getSystemPrincipals());
		}
		return $this->dataFlowService;
	}
	
	private $wigiiBPL;
	/**
	 * default as singleton
	 */
	protected function getWigiiBPLInstance()
	{
		if(!isset($this->wigiiBPL))
		{
			$this->wigiiBPL = $this->createWigiiBPLInstance();
			// adds system principals
			$this->wigiiBPL->addSystemPrincipal($this->getSystemPrincipals());
		}
		return $this->wigiiBPL;
	}
	
	private $exclusiveAccessInstances;
	/**
	 * Defaults to singletons per className except if instance isLockedForUse, 
	 * in that case returns a new fresh instance.
	 * @param Boolean $injectSystemPrincipal if true, then system principals are injected at creation time into the instance, else no injection. Default false.
	 */
	protected function getExclusiveAccessObjectInstance($className, $injectSystemPrincipals=false) {
		if(!isset($this->exclusiveAccessInstances)) $this->exclusiveAccessInstances = array();
		if(is_null($className) || $className == '') throw new ServiceProviderException("className cannot be null", ServiceProviderException::INVALID_ARGUMENT);
		$returnValue = $this->exclusiveAccessInstances[$className];
		if(!isset($returnValue)) {
			// creates and configures instance
			$returnValue = $this->createExclusiveAccessObjectInstance($className);
			// injects system principal if needed
			if($injectSystemPrincipals) $returnValue->addSystemPrincipal($this->getSystemPrincipals());
			$this->exclusiveAccessInstances[$className] = $returnValue;
		}
		elseif($returnValue->isLockedForUse()) {
			$returnValue = $this->createExclusiveAccessObjectInstance($className);
			// injects system principal if needed
			if($injectSystemPrincipals) $returnValue->addSystemPrincipal($this->getSystemPrincipals());
		}
		$returnValue->reset();
		return $returnValue;
	}
	
	/**
	 * defaults as an exclusive access object
	 */
	protected function getGroupBasedWigiiApiClientInstance($principal, $groupLogExp) {
		$returnValue = $this->getExclusiveAccessObjectInstance($this->getDefaultGroupBasedWigiiApiClientClass());
		// injects mandatory dependencies if necessary
		if(!$returnValue->hasMandatoryDependencies()) {
			$returnValue->setMandatoryDependencies($this->createElementServiceInstance(), TechnicalServiceProvider::createFuncExpBuilderInstance());
		}
		// select groups
		if($groupLogExp instanceof GroupList) $returnValue->setGroupList($groupLogExp);
		elseif($groupLogExp instanceof LogExp) $returnValue->selectGroups($principal, $groupLogExp);
		// else does not center the API client on any groups.
		return $returnValue;
	}
	/**
	 * defaults as GroupBasedWigiiApiClient
	 */
	protected function getDefaultGroupBasedWigiiApiClientClass() {
		return "GroupBasedWigiiApiClient";
	}
	
	/**
	 * defaults as an exclusive access object
	 */
	protected function getSubElementConfigServiceInstance() {
		$returnValue = $this->getExclusiveAccessObjectInstance($this->getDefaultSubElementConfigServiceClass());		
		return $returnValue;
	}
	/**
	 * defaults as ConfigServiceSubElementImpl
	 */
	protected function getDefaultSubElementConfigServiceClass() {
		return "ConfigServiceSubElementImpl";
	}
	protected function doClearSessionDataOfSubElementConfigService() {
		$cs = $this->getSubElementConfigServiceInstance();
		$cs->clearSessionData();
		$cs->freeMemory();		
	}
	
	// service creation

	/**
	 * default as AuthenticationServiceImpl
	 * possible to pass a list of predefined principals
	 */
	protected function createAuthenticationServiceInstance($principalList = null)
	{
		return new AuthenticationServiceImpl($principalList);
	}

	/**
	 * default as AuthorizationServiceImpl
	 */
	protected function createAuthorizationServiceInstance()
	{
		return new AuthorizationServiceImpl();
	}

	/**
	 * default as ConfigServiceImpl
	 */
	protected function createConfigServiceInstance()
	{
		return new ConfigServiceImpl();
	}

	/**
	 * default as ElementServiceImpl
	 */
	protected function createElementServiceInstance()
	{
		return new ElementServiceImpl();
	}

	/**
	 * default as GroupAdminServiceImpl
	 */
	protected function createGroupAdminServiceInstance()
	{
		return new GroupAdminServiceImpl();
	}

	/**
	 * default as UserAdminServiceImpl
	 */
	protected function createUserAdminServiceInstance()
	{
		return new UserAdminServiceImpl();
	}

	/**
	 * default as WigiiNamespaceAdminServiceImpl
	 */
	protected function createWigiiNamespaceAdminServiceInstance()
	{
		return new WigiiNamespaceAdminServiceImpl();
	}

	/**
	 * default as ClientAdminServiceImpl
	 */
	protected function createClientAdminServiceInstance()
	{
		return new ClientAdminServiceImpl();
	}

	/**
	 * default as ModuleAdminServiceImpl
	 */
	protected function createModuleAdminServiceInstance()
	{
		return new ModuleAdminServiceImpl();
	}

	/**
	 * defaults as DbAdminServiceImpl
	 */
	protected function createDbAdminServiceInstance()
	{
		return new DbAdminServiceImpl();
	}

	/**
	 * defaults as SessssionAdminServiceImpl
	 */
	protected function createSessionAdminServiceInstance()
	{
		return new SessionAdminServiceImpl();
	}

	/**
	 * defaults as TranslationServiceImpl
	 */
	protected function createTranslationServiceInstance()
	{
		return new TranslationServiceImpl();
	}

	/**
	 * defaults as ExecutionServiceImpl
	 */
	protected function createExecutionServiceInstance()
	{
		return new ExecutionServiceImpl();
	}

	/**
	 * defaults as SystemConsoleServiceImpl
	 */
	protected function createSystemConsoleServiceInstance()
	{
		return new SystemConsoleServiceImpl();
	}
	
	/**
	 * defaults as DataFlowServiceImpl
	 */
	protected function createDataFlowServiceInstance()
	{
		return new DataFlowServiceImpl();
	}
	
	/**
	 * defaults as WigiiBPL
	 */
	protected function createWigiiBPLInstance()
	{
		return new WigiiBPL();
	}
	
	/**
	 * defaults to new className
	 */
	protected function createExclusiveAccessObjectInstance($className) {
		return $this->createWigiiObjectInstance($className);
	}

	/**
	 * defaults to new className
	 * tries to apply any stored configuration
	 */
	protected function createWigiiObjectInstance($className) {
		wigii_autoload($className);
		$returnValue = new $className();
		// sets any configuration
		self::configureObject($returnValue);
		return $returnValue;
	}
	
	/**
	 * First tries to create a custom class, if not possible looks for standard implementation
	 */
	protected function createClientClassInstanceOrStandard($principal, $className)
	{
		// tries to create a custo class
		$custoExc = null; $stdExc = null;
		try {return $this->createClientClassInstance($principal, $className);}
		catch(Exception $e) {$custoExc = $e;}

		// not ok, then tries to create a standard implementation class
		try {return $this->createWigiiObjectInstance($className);} 
		catch(Exception $e) {$stdExc = $e;}

		// not ok, then throws back error :
		// 1. no standard class and not authorized to instanciate custom class -> throw AuthorizationException
		if($custoExc instanceof AuthorizationServiceException)
		{
			throw new AuthorizationServiceException($custoExc->getMessage().". Not authorized to instanciate custom $className and standard $className does not exist.", AuthorizationServiceException::FORBIDDEN);
		}
		// 2. no standard class and failed to instanciate custom class -> ServiceProviderException
		throw new ServiceProviderException($custoExc->getMessage()." AND ".$stdExc->getMessage(), ServiceProviderException::INVALID_ARGUMENT);
	}

	/**
	 * Creates an instance of a class located in Client config folder
	 */
	protected function createClientClassInstance($principal, $className)
	{
		$this->executionSink()->publishStartOperation("createClientClassInstance", $principal);
		try
		{
			if(is_null($className) || ($className == '')) throw new ServiceProviderException("className cannot be null", ServiceProviderException::INVALID_ARGUMENT);
			$this->assertPrincipalAuthorizedForCreateClientClassInstance($principal, $className);
			if(!class_exists($className, false))
			{
				$classFilePath = $this->getConfigServiceInstance()->getClientConfigFolderPath($principal).$className.".php";
				if(file_exists($classFilePath))
				{
					include_once ($classFilePath);
					$this->debugLogger()->write("$classFilePath has been loaded");
				}
				else throw new ServiceProviderException("file $classFilePath does not exist on disk", ServiceProviderException::INVALID_ARGUMENT);
			}
			$returnValue = new $className();
			// sets any configuration
			self::configureObject($returnValue);
		}
		/* does not log any exception, parent call will do it */
		catch(ServiceProviderException $spe)
		{
			$this->executionSink()->publishEndOperation("createClientClassInstance", $principal);
			throw $spe;
		}
		catch (AuthorizationServiceException $asE){
			$this->executionSink()->publishEndOperation("createClientClassInstance", $principal);
			throw $asE;
		}
		catch(Exception $e)
		{
			$this->executionSink()->publishEndOperation("createClientClassInstance", $principal);
			throw new ServiceProviderException('',ServiceProviderException::WRAPPING, $e);
		}
		$this->executionSink()->publishEndOperation("createClientClassInstance", $principal);
		return $returnValue;
	}
	protected function assertPrincipalAuthorizedForCreateClientClassInstance($principal, $className)
	{
		// checks general authorization
		$this->getAuthorizationServiceInstance()->assertPrincipalAuthorized($principal, "ServiceProvider", "createClientClassInstance");
	}
}


