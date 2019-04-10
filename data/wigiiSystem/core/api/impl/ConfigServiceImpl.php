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
 * Wigii main config service implementation.
 * Created by CWE on on 2 juin 09.
 * Refactored by CWE on February 26th 2014 to support dynamic xml
 */
class ConfigServiceImpl extends ConfigServiceCoreImpl
{
	private $_debugLogger;
	private $_executionSink;
	private $authS;
	private $analyzedXmlLps;	
	
	// Object lifecycle
	
	public function __construct()
	{		
		parent::__construct();
		$this->debugLogger()->write("creating instance");
		$this->setConfigControllers($this->getDefaultConfigControllers());
	}
	
	public function freeMemory() {
		parent::freeMemory();
		$this->getCoreConfigService()->freeMemory();
		$this->getParametersConfigService()->freeMemory();
		unset($this->principal);
		unset($this->analyzedXmlLps);
	}
	
	public function clearSessionData() {
		$this->getCoreConfigService()->clearSessionData();
		$this->getParametersConfigService()->clearSessionData();
		$this->clearConfigControllersSessionData();
		$this->freeMemory();
	}
	private function clearConfigControllersSessionData() {
		if(!empty($this->configControllers)) {
			$sAS = $this->getSessionAdminService();
			foreach($this->configControllers as $class) {
				$sAS->clearObjData($class);
			}
		}
	}
	
	// Dependency injection
	
	private $coreConfigService;
	private $parametersConfigService;
	private $useParametersConfigService = false; /* if true, then uses ParametersConfigService instance as CoreConfigService instance (temp flag setted in lookupParameter and read in loadConfig)*/
	/**
	 * Injects the instance of the ConfigServiceCoreImpl to be used as the main config service.
	 * This method is intented to be used only in advanced cases. 
	 * Else, the service automatically gets an instance of ConfigServiceCoreImpl.
	 * @param ConfigServiceCoreImpl $configServiceCoreImpl
	 */
	public function setCoreConfigService($configServiceCoreImpl) {
		$this->coreConfigService = $configServiceCoreImpl;
		$this->parametersConfigService = clone $configServiceCoreImpl;
	}
	protected function getCoreConfigService() {
		if(!isset($this->coreConfigService)) {
			$this->coreConfigService = $this->createCoreConfigServiceInstance();
			$this->coreConfigService->setWrappingConfigService($this);
			$this->parametersConfigService = clone $this->coreConfigService;
			// configures parametersConfigService as a slave (does not write xml in the session)
			$this->parametersConfigService->setReadOnlyXmlFromSession(true);
			// disables storage of parameters in session for the core config service, this is done only by parameters config service
			$this->coreConfigService->setParameterCacheIntoSessionEnabled(false);
			$this->debugLogger()->write('created core config service instance ('.$this->coreConfigService->getInstanceId().') of class '.get_class($this->coreConfigService));
		}
		return $this->coreConfigService;
	}
	protected function getParametersConfigService() {
		if(!isset($this->parametersConfigService)) {
			$this->getCoreConfigService();
		}
		return $this->parametersConfigService;
	}
	/**
	 * defaults to ConfigServiceCoreImpl
	 */
	protected function createCoreConfigServiceInstance() {		
		return new ConfigServiceCoreImpl();		
	}
	
	private function debugLogger()
	{
		if(!isset($this->_debugLogger))
		{
			$this->_debugLogger = DebugLogger::getInstance("ConfigServiceImpl_".$this->getInstanceId());
		}
		return $this->_debugLogger;
	}
	private function executionSink()
	{
		if(!isset($this->_executionSink))
		{
			$this->_executionSink = ExecutionSink::getInstance("ConfigServiceImpl_".$this->getInstanceId());
		}
		return $this->_executionSink;
	}
	
	public function setModuleAdminService($moduleAdminService)
	{
		$this->getCoreConfigService()->setModuleAdminService($moduleAdminService);
		$this->getParametersConfigService()->setModuleAdminService($moduleAdminService);
	}
	protected function getModuleAdminService()
	{
		return $this->getCoreConfigService()->getModuleAdminService();
	}
	
	public function setSessionAdminService($sessionAdminService){
		$this->getCoreConfigService()->setSessionAdminService($sessionAdminService);
		$this->getParametersConfigService()->setSessionAdminService($sessionAdminService);
	}
	protected function getSessionAdminService(){
		return $this->getCoreConfigService()->getSessionAdminService();
	}
	
	public function setAuthorizationService($authorizationService)
	{
		$this->getCoreConfigService()->setAuthorizationService($authorizationService);
		$this->getParametersConfigService()->setAuthorizationService($authorizationService);
	}
	protected function getAuthorizationService()
	{
		return $this->getCoreConfigService()->getAuthorizationService();
	}
	public function setAuthenticationService($authenticationService)
	{
		$this->authS = $authenticationService;
	}
	protected function getAuthenticationService()
	{
		// autowired
		if(!isset($this->authS)) {
			$this->authS = ServiceProvider::getAuthenticationService();
		}
		return $this->authS;
	}	
	public function setFieldListUnion($fieldListUnion)
	{
		$this->getCoreConfigService()->setFieldListUnion($fieldListUnion);
		$this->getParametersConfigService()->setFieldListUnion($fieldListUnion);
	}
	protected function getFieldListUnion()
	{
		return $this->getCoreConfigService()->getFieldListUnion();
	}
	
	public function setFieldSelectorFuncExpParser($fieldSelectorFuncExpParser)
	{
		$this->getCoreConfigService()->setFieldSelectorFuncExpParser($fieldSelectorFuncExpParser);
		$this->getParametersConfigService()->setFieldSelectorFuncExpParser($fieldSelectorFuncExpParser);
	}
	protected function getFieldSelectorFuncExpParser()
	{
		return $this->getCoreConfigService()->getFieldSelectorFuncExpParser();
	}
	
	// Configuration
	
	/**
	 * enables or not the loading from the SessionAdminService of the Config!
	 * that allows to deactivate this from the localhost (see ServiceProviderWebImpl for that)
	 */
	public function setSessionCacheEnabled($sessionCacheEnabled){
		// enables session or not on the core config service
		$this->getCoreConfigService()->setSessionCacheEnabled($sessionCacheEnabled);
		$this->getParametersConfigService()->setSessionCacheEnabled($sessionCacheEnabled);
		
	}
	/**
	 * This object never stores config into session, but the embedded core config service does.
	 */
	protected function isSessionCacheEnabled() {
		//return false;
		return $this->getCoreConfigService()->isSessionCacheEnabled();
	}
	
	/**
	 * Sets config folder path; adds an ending / if missing
	 */
	public function setConfigFolderPath($path)
	{
		$this->getCoreConfigService()->setConfigFolderPath($path);
		$this->getParametersConfigService()->setConfigFolderPath($path);
	}
	
	/**
	 * Returns config folder path with ending /
	 */
	public function getConfigFolderPath()
	{
		return $this->getCoreConfigService()->getConfigFolderPath();
	}
	
	/**
	 * Returns client config folder path
	 */
	public function getClientConfigFolderPath($principal)
	{
		return $this->getCoreConfigService()->getClientConfigFolderPath($principal);
	}
	
	/**
	 * Sets detatype folder path; adds an ending / if missing
	 */
	public function setDatatypeFolderPath($path)
	{
		$this->getCoreConfigService()->setDatatypeFolderPath($path);
		$this->getParametersConfigService()->setDatatypeFolderPath($path);
	}
	/**
	 * Returns datatype folder path with ending /
	 */
	public function getDatatypeFolderPath()
	{
		return $this->getCoreConfigService()->getDatatypeFolderPath();
	}
	
	public function setParameterCacheIntoSessionEnabled($bool){
		// core config service does not store parameters in session, only the parameter config service instance does this, if enabled.
		//$this->getCoreConfigService()->setParameterCacheIntoSessionEnabled($bool);
		$this->getParametersConfigService()->setParameterCacheIntoSessionEnabled($bool);
	}
	protected function isParameterCacheIntoSessionEnabled(){
		return $this->getParametersConfigService()->isParameterCacheIntoSessionEnabled();
	}
	
	public function setSharedCacheEnabled($bool){
		$this->getCoreConfigService()->setSharedCacheEnabled($bool);
		$this->getParametersConfigService()->setSharedCacheEnabled($bool);
	}
	protected function isSharedCacheEnabled(){
		return $this->getCoreConfigService()->isSharedCacheEnabled();
	}
	
	private $configControllers;
	/**
	 * Sets the list of ConfigControllers classes to be used.
	 * @param Array|String $controllers an array of ConfigController implementation class names or one class name.
	 */
	public function setConfigControllers($controllers) {
		if(!empty($controllers)) {
			if(is_array($controllers)) $this->configControllers = $controllers;
			else array($controllers);
		}
	}
	/**
	 * Returns an array with some default config controllers.
	 * @return Array in this implementation returns array('IncludeConfigController','AttributeExpConfigController','SameAsFieldConfigController')
	 */
	protected function getDefaultConfigControllers() {
	    return array('IncludeConfigController','AttributeExpConfigController','SameAsFieldConfigController');
	}
	
	// ConfigService interface re-implementation
	
	private $principal;
	
	public function getParameter($principal, $module, $name) {
		$this->principal = $principal;
		$returnValue = parent::getParameter($principal, $module, $name);
		unset($this->principal);
		return $returnValue;
	}
	public function allowGroupDynamicConfig($principal, $module) {
		$this->principal = $principal;
		$returnValue = parent::allowGroupDynamicConfig($principal, $module);
		unset($this->principal);
		return $returnValue;
	}
	public function getGroupConfigFilename($principal, $group) {
		$this->principal = $principal;
		$returnValue = parent::getGroupConfigFilename($principal, $group);
		unset($this->principal);
		return $returnValue;
	}
	public function doesGroupHasConfigFile($principal, $group) {
		$this->principal = $principal;
		$returnValue = parent::doesGroupHasConfigFile($principal, $group);
		unset($this->principal);
		return $returnValue;
	}
	public function getUserConfigFilename($principal, $module, $user) {
		$this->principal = $principal;
		$returnValue = parent::getUserConfigFilename($principal, $module, $user);
		unset($this->principal);
		return $returnValue;
	}
	public function doesUserHasConfigFile($principal, $module, $user) {
		$this->principal = $principal;
		$returnValue = parent::doesUserHasConfigFile($principal, $module, $user);
		unset($this->principal);
		return $returnValue;
	}
	public function getModuleConfigFilename($principal, $module, $wigiiNamespace) {
		$this->principal = $principal;
		$returnValue = parent::getModuleConfigFilename($principal, $module, $wigiiNamespace);
		unset($this->principal);
		return $returnValue;
	}
	public function listModuleConfigFilesForWigiiNamespace($principal, $filenameRenderer) {
		$this->principal = $principal;
		$returnValue = parent::listModuleConfigFilesForWigiiNamespace($principal, $filenameRenderer);
		unset($this->principal);
		return $returnValue;
	}
	public function getGroupParameter($principal, $group, $name) {
		$this->principal = $principal;
		$returnValue = parent::getGroupParameter($principal, $group, $name);
		unset($this->principal);
		return $returnValue;
	}
	public function getFields($principal, $module, $activity, $fieldList) {
		$this->principal = $principal;
		$returnValue = parent::getFields($principal, $module, $activity, $fieldList);
		unset($this->principal);
		return $returnValue;
	}
	public function getGroupFields($principal, $group, $activity, $fieldList) {
		$this->principal = $principal;
		$returnValue = parent::getGroupFields($principal, $group, $activity, $fieldList);
		unset($this->principal);
		return $returnValue;
	}
	public function getGroupsFields($principal, $groupList, $activity, $fieldList) {
		$this->principal = $principal;
		$returnValue = parent::getGroupsFields($principal, $groupList, $activity, $fieldList);
		unset($this->principal);
		return $returnValue;
	}
	public function getTemplatePath($principal, $module, $activity) {
		$this->principal = $principal;
		$returnValue = parent::getTemplatePath($principal, $module, $activity);
		unset($this->principal);
		return $returnValue;
	}
	public function getGroupTemplatePath($principal, $group, $activity) {
		$this->principal = $principal;
		$returnValue = parent::getGroupTemplatePath($principal, $group, $activity);
		unset($this->principal);
		return $returnValue;
	}
	public function m($principal, $module) {
		$this->principal = $principal;
		$returnValue = parent::m($principal, $module);
		unset($this->principal);
		return $returnValue;
	}
	public function mf($principal, $module, $activity=null) {
		$this->principal = $principal;
		$returnValue = parent::mf($principal, $module, $activity);
		unset($this->principal);
		return $returnValue;
	}
	public function ma($principal, $module, $activity) {
		$this->principal = $principal;
		$returnValue = parent::ma($principal, $module, $activity);
		unset($this->principal);
		return $returnValue;
	}
	public function g($principal, $group) {
		$this->principal = $principal;
		$returnValue = parent::g($principal, $group);
		unset($this->principal);
		return $returnValue;
	}
	public function gf($principal, $group, $activity=null) {
		$this->principal = $principal;
		$returnValue = parent::gf($principal, $group, $activity);
		unset($this->principal);
		return $returnValue;
	}
	public function ga($principal, $group, $activity) {
		$this->principal = $principal;
		$returnValue = parent::ga($principal, $group, $activity);
		unset($this->principal);
		return $returnValue;
	}
	public function getAdminConfigForClient($principal, $client) {
		$this->principal = $principal;
		$returnValue = parent::getAdminConfigForClient($principal, $client);
		unset($this->principal);
		return $returnValue;
	}
	
	
	// Implementation

	public function addFieldsToFieldListFromXmlConfig($principal, $fieldList, $xmlConfig, $ignoreDuplicates=false) {
		// instanciates SimpleXmlElement
		if(is_null($xmlConfig)) return;
		elseif(is_string($xmlConfig)) {
			$xmlConfig = $this->readConfigXmlNodeFromString(stripslashes($xmlConfig));
			if(!xmlConfig) throw new ConfigServiceException("invalid xml configuration string", ConfigServiceException::INVALID_ARGUMENT);
		}
		elseif(!($xmlConfig instanceof SimpleXMLElement)) throw new ConfigServiceException("xmlConfig should be an xml string or an instance of SimpleXmlElement", ConfigServiceException::INVALID_ARGUMENT);
		
		// executes any defined ConfigControllers on the xml
		$xmlConfig = CSXmlEditionContext::getInstance($xmlConfig, $this);
		$getWritableNodeCallback = CallableObject::createInstance('getWritableXml', $xmlConfig);
		
		foreach($this->configControllers as $ctlClass) {
			// gets controller
			$ctl = ServiceProvider::getExclusiveAccessObject($ctlClass);
			// processes the xml
			try {
				if($ctl->processConfigurationNode($principal, $xmlConfig->getReadableXml(), $getWritableNodeCallback, null)) {
					// if xml has been modified, then updates the readable xml based on the last modifications.
					$xmlConfig->updateReadableXml();
				}
				$ctl->freeMemory();
			}
			catch(Exception $e) {
				$xmlConfig->freeMemory();
				$ctl->freeMemory();
				throw $e;
			}
		}
		
		// fills the field list
		parent::addFieldsToFieldListFromXmlConfig($principal, $fieldList, $xmlConfig->getReadableXml(), $ignoreDuplicates);
		$xmlConfig->freeMemory();
	}
	
	protected function getLoadedField($lookupPath, $asXml=false)
	{
		$this->debugLogger()->logBeginOperation('getLoadedField');		
		$returnValue = parent::getLoadedField($lookupPath, $asXml);
		if(is_null($returnValue)) {
			// checks if we have local xml loaded
			$xmlLookupPath = $this->dropActivityNameFromLP($lookupPath);
			$xmlConfig = parent::getLoadedXml($xmlLookupPath);
			if(!is_null($xmlConfig)) {
				// if true, then loads missing fields before looking to core config service
				$this->loadFields($lookupPath, $xmlConfig);
				$returnValue = parent::getLoadedField($lookupPath, $asXml);
			}
			//else $returnValue = | $this->getCoreConfigService()->getLoadedField($lookupPath, $asXml);
			else $returnValue = $this->getCoreConfigService()->getLoadedField($lookupPath, $asXml);
		}
		$this->debugLogger()->logEndOperation('getLoadedField');
		return $returnValue;
	}
	protected function getLoadedActivity($lookupPath)
	{
		$this->debugLogger()->logBeginOperation('getLoadedActivity');
		$returnValue = parent::getLoadedActivity($lookupPath);
		if(is_null($returnValue)) {
			// checks if we have local xml loaded
			$xmlLookupPath = $this->dropActivityNameFromLP($lookupPath);
			$xmlConfig = parent::getLoadedXml($xmlLookupPath);
			if(!is_null($xmlConfig)) {
				// if true, then loads missing activity before looking to core config service
				$this->loadActivities($lookupPath, $xmlConfig);
				$returnValue = parent::getLoadedActivity($lookupPath);
			}
			else $returnValue = $this->getCoreConfigService()->getLoadedActivity($lookupPath);
		}
		$this->debugLogger()->logEndOperation('getLoadedActivity');
		return $returnValue;
	}
	protected function getLoadedTemplate($lookupPath)
	{
		$this->debugLogger()->logBeginOperation('getLoadedTemplate');
		$returnValue = parent::getLoadedTemplate($lookupPath);
		if(is_null($returnValue)) {
			// checks if we have local xml loaded
			$xmlLookupPath = $this->dropActivityNameFromLP($lookupPath);
			$xmlConfig = parent::getLoadedXml($xmlLookupPath);
			if(!is_null($xmlConfig)) {
				// if true, then loads missing template before looking to core config service
				$this->loadTemplate($lookupPath, $xmlConfig);
				$returnValue = parent::getLoadedTemplate($lookupPath);
			}
			else $returnValue = $this->getCoreConfigService()->getLoadedTemplate($lookupPath);
		}
		$this->debugLogger()->logEndOperation('getLoadedTemplate');
		return $returnValue;
	}

	protected function getLoadedDataType($dataTypeName)
	{
		return $this->getCoreConfigService()->getLoadedDataType($dataTypeName);
	}
	
	protected function getLoadedParameter($lookupPath, $name) 
	{
		$this->debugLogger()->logBeginOperation('getLoadedParameter');
		$returnValue = parent::getLoadedParameter($lookupPath, $name);
		if(is_null($returnValue)) $returnValue = $this->getParametersConfigService()->getLoadedParameter($lookupPath, $name);
		$this->debugLogger()->logEndOperation('getLoadedParameter');
		return $returnValue;
	}

	protected function getLoadedXml($lookupPath)
	{
		$this->debugLogger()->logBeginOperation('getLoadedXml');		
		$returnValue = parent::getLoadedXml($lookupPath);
		if(is_null($returnValue)) $returnValue = $this->getCoreConfigService()->getLoadedXml($lookupPath);
		$this->debugLogger()->logEndOperation('getLoadedXml');
		return $returnValue;
	}

	protected function loadConfig($moduleName, $clientName, $wigiiNamespaceName, $groupName, $username, $activityName)
	{
		//$this->debugLogger()->logBeginOperation("loadConfig");
		$sGroupName = (is_null($groupName)?'':$groupName);
		$sModuleName = (is_null($moduleName) ? '':$moduleName);
		$sClientName = (is_null($clientName) ? '':$clientName);
		$sWigiiNamespaceName = (is_null($wigiiNamespaceName) ? '':$wigiiNamespaceName);
		$sUsername = (is_null($username) ? '':$username);
		$sActivityName = (is_null($activityName)?'':$activityName);
		$lpParamAndXml = '('.$sModuleName.'('.$sClientName.'('.$sWigiiNamespaceName.'('.$sGroupName.'('.$sUsername.')))))';
		$lpField = '('.$sModuleName.'('.$sClientName.'('.$sWigiiNamespaceName.'('.$sGroupName.'('.$sUsername.'('.$sActivityName.'))))))';
		
		// checks that xml is not already loaded
		$xml = parent::getLoadedXml($lpParamAndXml);
		if(isset($xml)) {			
			// loads all unset Objects
			return $this->loadAllUnset($lpParamAndXml, $lpField, $xml);
		}				
		// else delegates to core service		
		$coreCS = ($this->useParametersConfigService ? $this->getParametersConfigService() : $this->getCoreConfigService());
		$returnValue = $coreCS->loadConfig($moduleName, $clientName, $wigiiNamespaceName, $groupName, $username, $activityName);
		// checks if xml has already been analyzed
		if(isset($this->analyzedXmlLps)) $alreadyAnalyzed = ($this->analyzedXmlLps[$lpParamAndXml] == true);
		else $alreadyAnalyzed = false;
		
		if(($returnValue || !$alreadyAnalyzed) && isset($this->configControllers)) {			
			// gets loaded xml
			$this->debugLogger()->write('gets loaded xml');
			$xml = $coreCS->getLoadedXml($lpParamAndXml);			
			if(isset($xml)) {
				$this->debugLogger()->write('analyses loaded xml');
				// dumps core XML config if enabled
				if($this->getDumpConfig()) $this->dumpConfig($xml, wigiiBPLParam('client',$sClientName,'wigiiNamespace',$sWigiiNamespaceName,'module',$sModuleName,'group',$sGroupName,'lpField',$lpField,'loadCoreConfig',true));
				$xml = CSXmlEditionContext::getInstance($xml, $this);
				$principal = (isset($this->principal) ? $this->principal: $this->getAuthenticationService()->getMainPrincipal());
				$getWritableNodeCallback = CallableObject::createInstance('getWritableXml', $xml);
				$lpArray = array('moduleName'=>$moduleName, 'clientName'=>$clientName, 'wigiiNamespaceName'=>$wigiiNamespaceName, 'groupName'=>$groupName, 'username'=>$username, 'activityName'=>$activityName);
				$modifiedXml = false;			
				foreach($this->configControllers as $ctlClass) {
					// gets controller
					$ctl = ServiceProvider::getExclusiveAccessObject($ctlClass);
					// CWE 09.04.2019: if loading only parameters, then processes xml only with compatible controllers
					if($coreCS->isLoadingOnlyParameters() && !$ctl->enabledForLoadingOnlyParameters()) continue;
					// processes the xml
					try {
						if($ctl->processConfigurationNode($principal, $xml->getReadableXml(), $getWritableNodeCallback, $lpArray)) {
							// if xml has been modified, then updates the readable xml based on the last modifications.
							$xml->updateReadableXml();
							$modifiedXml = true;
							// CWE 08.04.2019: stores intermediate modified xml in cache to allow further controllers to beneficiate from already modified xml
							if(!$coreCS->isLoadingOnlyParameters()) {
							    $coreCS->loadAll($lpParamAndXml, $lpField, $xml->getReadableXml(), false);
							}
						}
						$ctl->freeMemory();
					}
					catch(Exception $e) {						
						$xml->freeMemory();
						$ctl->freeMemory();
						throw $e;
					}					
				}
				// if xml has been modified, then stores it into the local cache and loads all remaining objects
				if($modifiedXml) {					
					$readableXml = $xml->getReadableXml();
					// dumps XML config if enabled
					if($this->getDumpConfig()) $this->dumpConfig($readableXml, wigiiBPLParam('client',$sClientName,'wigiiNamespace',$sWigiiNamespaceName,'module',$sModuleName,'group',$sGroupName,'lpField',$lpField, 'principal',$principal));
					// CWE 09.04.2019: loads parameters based on modified xml
					$coreCS->loadXml($lpParamAndXml, $readableXml);
					$coreCS->loadParameters($lpParamAndXml, $readableXml);
					// loads all Objects
					if(!$coreCS->isLoadingOnlyParameters()) {
					    $this->loadAll($lpParamAndXml, $lpField, $readableXml, false);
					}
				}
				$xml->freeMemory();	
				// remembers the fact that this lookup path has been analyzed
				if(!$coreCS->isLoadingOnlyParameters()) {
				    if(!isset($this->analyzedXmlLps)) $this->analyzedXmlLps = array();
				    $this->analyzedXmlLps[$lpParamAndXml] = true;				    
				}
			}
		}
		//$this->debugLogger()->logEndOperation("loadConfig");
		return $returnValue;
	}	
	
	protected function loadDatatypeConfig($datatype)
	{
		return $this->getCoreConfigService()->loadDatatypeConfig($datatype);
	}
	
	protected function lookupParameter($name, $moduleName, $clientName, $wigiiNamespaceName, $groupName, $username) {
		//$this->debugLogger()->logBeginOperation("lookupParameter");
		$paramCS = $this->getParametersConfigService();
		if(!$paramCS->isLoadingOnlyParameters()) {
			$paramCS->setLoadOnlyParameters(true);
			$shouldUnsetFlag = true;
		}
		else $shouldUnsetFlag = false;
		$this->useParametersConfigService = true;
		$returnValue = null;
		try {
			$returnValue = parent::lookupParameter($name, $moduleName, $clientName, $wigiiNamespaceName, $groupName, $username);
		}
		catch(Exception $e) {
			if($shouldUnsetFlag) $paramCS->setLoadOnlyParameters(false);
			$this->useParametersConfigService = false;
			throw $e;
		}
		if($shouldUnsetFlag) $paramCS->setLoadOnlyParameters(false);
		$this->useParametersConfigService = false;
		//$this->debugLogger()->logEndOperation("lookupParameter");
		return $returnValue;
	}
	
	protected function isParameterUndefined($lookupPath, $name) {
		return $this->getParametersConfigService()->isParameterUndefined($lookupPath, $name);
	}
	
	protected function markParameterAsUndefined($lookupPath, $name) {
		return $this->getParametersConfigService()->markParameterAsUndefined($lookupPath, $name);
	}
	
	protected function isActivityUndefined($lookupPath, $activityName) {
		return $this->getCoreConfigService()->isActivityUndefined($lookupPath, $activityName);
	}
	
	protected function markActivityAsUndefined($lookupPath, $activityName) {
		return $this->getCoreConfigService()->markActivityAsUndefined($lookupPath, $activityName);
	}
	
	protected function getParameterFromSessionCache($name, $sModuleName, $sClientName, $sWigiiNamespaceName, $sGroupName, $sUsername) {
		return $this->getParametersConfigService()->getParameterFromSessionCache($name, $sModuleName, $sClientName, $sWigiiNamespaceName, $sGroupName, $sUsername);
	}
	
	protected function cacheParametersInSession($lookupPath, $xml) {
		return $this->getParametersConfigService()->cacheParametersInSession($lookupPath, $xml);
	}
}
class CSXmlEditionContext implements WigiiExclusiveAccessObject {
	private $isLockedForUse;
	private $readableXml;
	private $writableXml;
	
	// Object lifecycle
	
	public static function getInstance($readableXml, $configServiceCoreImpl) {
		$returnValue = ServiceProvider::getExclusiveAccessObject('CSXmlEditionContext');
		$returnValue->setConfigServiceCoreImpl($configServiceCoreImpl);
		$returnValue->setReadableXml($readableXml);
		return $returnValue;
	}
	
	public function reset() {
		$this->freeMemory();
		$this->isLockedForUse = true;
	}
	
	public function freeMemory() {
		unset($this->readableXml);
		unset($this->writableXml);
		$this->isLockedForUse = false;
	}
	
	public function isLockedForUse() {
		return $this->isLockedForUse;
	}
	
	// Dependency injection
	
	private $configServiceCoreImpl;
	public function setConfigServiceCoreImpl($configServiceCoreImpl) {
		$this->configServiceCoreImpl = $configServiceCoreImpl;
	}
	
	// Implementation
	
	public function setReadableXml($readableXml) {
		$this->readableXml = $readableXml;
		unset($this->writableXml);
	}
	public function getReadableXml() {
		return $this->readableXml;
	}
	public function updateReadableXml() {
		if(isset($this->writableXml)) $this->readableXml = $this->writableXml;
	}
	public function getWritableXml() {
		if(!isset($this->writableXml) && isset($this->readableXml)) {
			$this->writableXml = $this->configServiceCoreImpl->cloneConfigXmlNode($this->readableXml);
		}
		return $this->writableXml;
	}
}