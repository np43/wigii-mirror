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

/* ServiceProvider implementation which integrates with wigii web site
 * Created by CWE on 8 juin 09
 */
class ServiceProviderWebImpl extends ServiceProvider
{
	private $_debugLogger;
	private function debugLogger()
	{
		if(!isset($this->_debugLogger))
		{
			$this->_debugLogger = DebugLogger::getInstance("ServiceProviderWebImpl");
		}
		return $this->_debugLogger;
	}

	private $sessionCacheExecEnabled;
	protected function isSessionCacheExecEnabled(){
		return $this->sessionCacheExecEnabled;
	}
	protected function setSessionCacheExecEnabled($sessionCacheExecEnabled)
	{
		$this->sessionCacheExecEnabled = $sessionCacheExecEnabled;
	}

	private $sessionCacheBuildEnabled;
	protected function isSessionCacheBuildEnabled(){
		return $this->sessionCacheBuildEnabled;
	}
	protected function setSessionCacheBuildEnabled($sessionCacheBuildEnabled)
	{
		$this->sessionCacheBuildEnabled = $sessionCacheBuildEnabled;
	}

	private $sysConsoleDisplayEnabled;
	protected function setSysConsoleDisplayEnabled($var){
		$this->sysConsoleDisplayEnabled = $var;
	}
	protected function isSysConsoleDisplayEnabled(){
		return $this->sysConsoleDisplayEnabled;
	}

	/**
	 * Creates new instance and registers itself in API.
	 * receives the WigiiExecutor which starts the API WebImpl so that it can configure it.
	 */
	private $WigiiExecutor;
	public function setWigiiExecutor($var){
		$this->WigiiExecutor = $var;
	}
	protected function getWigiiExecutor(){
		return $this->WigiiExecutor;
	}

	public static function start($sessionCacheExecEnabled=true, $sessionCacheBuildEnabled=true, $sysConsoleDisplayEnabled=false, $wigiiExecutor=null)
	{
		$instance = new ServiceProviderWebImpl();
		$instance->setWigiiExecutor($wigiiExecutor);
		$instance->setSysConsoleDisplayEnabled($sysConsoleDisplayEnabled);
		$instance->setSessionCacheExecEnabled($sessionCacheExecEnabled);
		$instance->setSessionCacheBuildEnabled($sessionCacheBuildEnabled);
		parent::registerSingleInstance($instance);
		// informs WigiiExecutor of SystemPrincipals
		if(isset($wigiiExecutor))
		{
			$wigiiExecutor->addSystemPrincipal($instance->getSystemPrincipals());
		}
	}

//	/**
//	 * Returns default wigiiNamespace to associate with system principals
//	 * this implementation returns wigiiNamespace defined with CLIENT_NAME + current wigiiNamespace in url
//	 */
//	protected function getDefaultSystemPrincipalWigiiNamespace()
//	{
//		$n = $this->getWigiiNamespaceAdminService()->getEmptyWigiiNamespaceForDefaultClient();
//		//fill wigiiNamespace name
//		//how to get current wigiiNamespace?? execution service has not parse the urls yet
//		return $n;
//	}
//	/**
//	 * Returns default moduleAccess to associate with system principals
//	 * this implementation returns current module + admin module
//	 */
//	protected function getDefaultSystemPrincipalModuleAccess()
//	{
//		//how to get current module?? execution service has not parse the url yet
//		//do we give all module access? if yes, how to know what are all module?
//		return $this->getModuleAdminService()->formatModuleArray(null, array("Admin", "Contacts", "Filemanager"));
//	}

	protected function createConfigServiceInstance()
	{
		$returnValue = new ConfigServiceWebImpl();
		// sets configuration root folder to wigii/configs
		$returnValue->setConfigFolderPath(wigiiSystem_PATH.'configs');
		$returnValue->setDatatypeFolderPath(DATATYPE_PATH);
		$returnValue->setSessionCacheEnabled($this->isSessionCacheBuildEnabled());
		return $returnValue;
	}

	protected function createAuthenticationServiceInstance($principalList = null)
	{
		$authS = new AuthenticationServiceWebImpl(CLIENT_NAME, $principalList);
		$authS->setSessionCacheEnabled($this->isSessionCacheExecEnabled());
		// CWE 16.03.2015: keeps core ConfigService to prevent initialization loops.
		//$authS->setConfigService($this->getWigiiExecutor()->getConfigurationContext());
		return $authS;
	}

	protected function createAuthorizationServiceInstance()
	{
		return new AuthorizationServiceWebImpl();
	}

	protected function createDbAdminServiceInstance()
	{
		return new DbAdminServiceWebImpl();
	}

	protected function createClientAdminServiceInstance()
	{
		return new ClientAdminServiceWebImpl();
	}

	//in the web implementation we first check if
	//there is not an instance stored in the session...
	protected function createSessionAdminServiceInstance(){

		//look in the session if the object exists
		$wigii_api_sessionAdminService = $_SESSION["wigii_api_sessionAdminService"];
		if($wigii_api_sessionAdminService==null)
		{
			$wigii_api_sessionAdminService = new SessionAdminServiceImpl();
			$this->debugLogger()->write("SessionAdmin service started and registered in session");
		} else {
			$this->debugLogger()->write("SessionAdmin service loaded from session");
		}
		//register the object ref in the $_SESSION
		//because this is an object, the reference will stay even if the datas are changed
		//but this will work only for this instanciation
		$_SESSION["wigii_api_sessionAdminService"] = $wigii_api_sessionAdminService;

		//$wigii_api_sessionAdminService->emptySessionDatas();
		return $wigii_api_sessionAdminService;
	}

	/**
	 * in the web impl we do the start with the constante parameters
	 */
	protected function doCreateTranslationServiceInstance(){
		return new TranslationServiceImpl();
	}
	protected function createTranslationServiceInstance()
	{
		$tS = $this->doCreateTranslationServiceInstance();
		$tS->setSessionCacheEnabled($this->isSessionCacheBuildEnabled());

//		if($tS->getLanguage() == null){
//			$tS->setLanguage($DEFAULT_LANGUAGE);
//		}
//		$tS->setVisibleLanguage($VISIBLE_LANGUAGE);

//		if($tS->getLanguage() == null){
//			$tS->setLanguage(DEFAULT_LANGUAGE);
//		}
//		if(defined("VISIBLE_LANGUAGE")){
//			$instLanguage = $tS->getInstalledLanguage();
//			$visibleLanguage = preg_split("/".ValueListArrayMapper::Natural_Separators."/", VISIBLE_LANGUAGE);
//			$visibleLanguage = array_combine($visibleLanguage, $visibleLanguage);
//			$visibleLanguage = array_intersect_key($instLanguage, $visibleLanguage);
//			$tS->setVisibleLanguage($visibleLanguage);
//		}
		if($tS->getSystemDictionaryPath() == null){
			$tS->setSystemDictionaryPath(LANG_FOLDER . "dico.txt");
		}

		return $tS;
	}

	protected function createExecutionServiceInstance()
	{
		return new ExecutionServiceWebImpl();
	}

	protected function createSystemConsoleServiceInstance()
	{
		return new SystemConsoleServiceWebImpl($this->isSysConsoleDisplayEnabled());
	}

	protected function createElementServiceInstance()
	{
		$els = new ElementServiceWebImpl();
		$els->setConfigService($this->getWigiiExecutor()->getConfigurationContext());
		return $els;
	}

	protected function createGroupAdminServiceInstance()
	{
		return new GroupAdminServiceWebImpl();
	}
	protected function createUserAdminServiceInstance()
	{
		return new UserAdminServiceWebImpl();
	}

	protected function createModuleAdminServiceInstance()
	{
		$s = new ModuleAdminServiceWebImpl();
		$s->setConfigService($this->getWigiiExecutor()->getConfigurationContext());
		return $s;
	}
	
	protected function createWigiiBPLInstance()
	{
		$returnValue = new WigiiBPL();
		$returnValue->setConfigService($this->getWigiiExecutor()->getConfigurationContext());
		$returnValue->setWigiiExecutor($this->getWigiiExecutor());
		return $returnValue;
	}
	
	private $eventExpSubscriber;
	
	public static function getEventExpSubscriber()
	{
		return self::getInstance()->getEventExpSubscriberInstance();
	}
	
	/**
	 * default singleton
	 */
	protected function getEventExpSubscriberInstance()
	{
		if(!isset($this->eventExpSubscriber))
		{
			$this->eventExpSubscriber = $this->createEventExpSubscriberInstance();
		}
		return $this->eventExpSubscriber;
	}
	
	/**
	 * default as EventExpSubscriber
	 */
	protected function createEventExpSubscriberInstance()
	{
		$eventExpSubscriber = new EventExpSubscriber();
		$eventExpSubscriber->setWigiiExecutor($this->getWigiiExecutor());
		return $eventExpSubscriber;
	}
}


