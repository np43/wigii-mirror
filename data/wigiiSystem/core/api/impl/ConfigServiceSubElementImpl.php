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
 * Sub Elements Config Service
 * Created by CWE on February 4th 2014
 */
class ConfigServiceSubElementImpl implements ConfigService
{	
	private $_debugLogger;
	private $_executionSink;
	private $lockedForUse = true;
	private $subElementConfig;
	private $subElementConfigChanged;
	private $xmlNodeCache;
	private $fieldListCache;
	private $sessionKeys;
	private $currentConfigModule;
	private $currentConfigUrl;
	private $currentLinkSelector;
	private $currentLinkSelectorList;	
	
	// Object lifecycle
	
	public function reset() {
		$this->freeMemory();		
		$this->lockedForUse = true;
		$this->subElementConfig = array(
			'subElementPath' => array(),
			'configString' => array() 
		);		
		$this->subElementConfigChanged = false;	
		$this->xmlNodeCache = array();	
		$this->fieldListCache = array();	
	}
	
	public function freeMemory() {	
		unset($this->sessionKey);
		unset($this->sessionKeys);
		unset($this->subElementConfig);
		unset($this->xmlNodeCache);	
		unset($this->fieldListCache);
		unset($this->currentConfigModule);
		unset($this->currentConfigUrl);
		unset($this->currentLinkSelector);
		unset($this->currentLinkSelectorList);
		$this->lockedForUse = false;		
	}
	
	public function isLockedForUse() {
		return $this->lockedForUse;
	}	
	
	public function clearSessionData() {		
		$sessAS = $this->getSessionAdminService();
		// gets session keys from session
		$this->sessionKeys = $sessAS->getData($this, 'sessionKeys');
		// clears session data
		if(is_array($this->sessionKeys)) {
			foreach($this->sessionKeys as $sk) {
				$sessAS->clearData($this, $sk);
				$this->debugLogger()->write("cleared $sk from session");
			}
		}
		// clears session keys from session
		$sessAS->clearData($this, 'sessionKeys');
		$this->debugLogger()->write("session data cleared");
	}
	
	// Dependency injection

	private function debugLogger()
	{
		if(!isset($this->_debugLogger))
		{
			$this->_debugLogger = DebugLogger::getInstance("ConfigServiceSubElementImpl");
		}
		return $this->_debugLogger;
	}
	private function executionSink()
	{
		if(!isset($this->_executionSink))
		{
			$this->_executionSink = ExecutionSink::getInstance("ConfigServiceSubElementImpl");
		}
		return $this->_executionSink;
	}
	
	private $configS;
	/**
	 * Inject main parent instance of ConfigService to be used.
	 * The ConfigServiceSubElementImpl class delegates to this ConfigService instance
	 * all requests it cannot handle.	 
	 * @param ConfigService $configService
	 */
	public function setConfigService($configService)
	{
		$this->configS = $configService;		
	}
	protected function getConfigService()
	{
		// autowired
		if(!isset($this->configS))
		{
			$this->configS = ServiceProvider::getConfigService();			
		}
		return $this->configS;
	}
	private $configServiceImpl;
	/**
	 * Injects an instance of ConfigServiceImpl that is needed for some internal functions	
	 * @param ConfigServiceImpl $configServiceImpl
	 */
	public function setConfigServiceImpl($configServiceImpl) {
		if(!($configServiceImpl instanceof ConfigServiceImpl)) throw new ConfigServiceException("ConfigServiceSubElementImpl depends on ConfigServiceImpl for some functions, please inject one.", ConfigServiceException::INVALID_ARGUMENT);
		$this->configServiceImpl = $configServiceImpl;
	}
	protected function getConfigServiceImpl() {
		if(!isset($this->configServiceImpl)) {
			$this->setConfigServiceImpl(ServiceProvider::getConfigService());
		}
		return $this->configServiceImpl;
	}
	
	private $sessionAS;
	public function setSessionAdminService($sessionAdminService){
		$this->sessionAS = $sessionAdminService;
	}
	protected function getSessionAdminService(){
		// autowired
		if(!isset($this->sessionAS)){
			$this->sessionAS = ServiceProvider::getSessionAdminService();
		}
		return $this->sessionAS;
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
	
	private $dbAS;
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
	
	private $mysqlF;
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
	
	private $moduleAS;
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
	
	private $translationService;
	public function setTranslationService($translationService){
		$this->translationService = $translationService;		
	}
	protected function getTranslationService(){
		//autowired
		if(!isset($this->translationService)){
			$this->translationService = ServiceProvider::getTranslationService();
		}
		return $this->translationService;
	}
	
	// Configuration
	
	private $sessionKey;
	public function setSessionKey($key) {
		$this->sessionKey = $key;
		if(isset($this->sessionKey)) $this->loadConfigFromSession();
	}
	public function getSessionKey() {
		return $this->sessionKey;
	}
	
	// Context
		
	/**
	 * Returns the current selected sub element module 
	 * or null if no sub element configuration has been selected
	 * @return Module
	 */
	public function getCurrentModule() {
		return $this->currentConfigModule;
	}
	
	/**
	 * Returns the current selected sub element master id
	 * or null if no sub element configuration has been selected
	 * @return Scalar
	 */
	public function getCurrentMasterElementId() {
		if(isset($this->currentLinkSelector)) {
			return $this->currentLinkSelector->getOwnerElementId();
		}
		else return null;
	}
	
	/**
	 * Returns the field name under which the current sub element is linked
	 * or null if no sub element configuration has been selected
	 * @return String
	 */
	public function getCurrentFieldName() {
		if(isset($this->currentLinkSelector)) {
			return $this->currentLinkSelector->getFieldName();
		}
		else return null;
	}
	
	/**
	 * Returns the field Xml configuration under which the current sub element is linked
	 * or null if no sub element configuration has been selected
	 * @param Principal $principal current principal
	 * @return SimpleXMLElement
	 */
	public function getCurrentFieldXml($principal) {
		if(isset($this->currentLinkSelector)) {
			// gets parent XML node
			$returnValue = $this->mf($principal, $this->getModuleAdminService()->getModule($principal, $this->currentLinkSelector->getModuleName()));
			// returns field xml
			if(!isset($returnValue)) throw new ConfigServiceException('Did not find XML configuration for parent module '.$this->currentLinkSelector->getModuleName(), ConfigServiceException::CONFIGURATION_ERROR);
			$returnValue = $returnValue->{$this->currentLinkSelector->getFieldName()};
			return $returnValue;
		}
		else return null;
	}
	
	/**
	 * Returns the LinkSelector under which the current sub element is linked
	 * or null if no sub element configuration has been selected
	 * @return LinkSelector
	 */
	public function getCurrentLinkSelector() {
		return $this->currentLinkSelector;
	}
	
	/**
	 * Returns the path from the root element to the current sub element
	 * or null if no sub element configuration has been selected
	 * @return LinkSelectorList a LinkSelectorList instance containing LinkSelector from root to the sub element.
	 * The list does not contain the sub element itself, but stops on its direct parent.
	 */
	public function getCurrentSubElementPathFromRoot() {
		return $this->currentLinkSelectorList;
	}
	
	// Navigation
	
	/**
	 * Selects the configuration of some sub elements given the master element
	 * @param Principal $principal authenticated user performing the operation
	 * @param Scalar|Element $masterElementId the id of the master element (or the master element itself) for which to retrieve sub elements configuration
	 * @param String $fieldName the field name of the master element under which we want to retrieve the sub elements configuration
	 * @throws ConfigServiceException if an error occurs.
	 * Postconditions: the configuration service is centered on the configuration of these sub elements.
	 * -> getCurrentModule returns the module of the subs elements
	 * -> getCurrentMasterElementId returns the id of the given master element
	 * -> getCurrentFieldName returns the field name to which the sub elements are linked
	 * -> ConfigService methods m, mf, ma, getFields, getParameter and getTemplatePath return
	 * values using the sub elements configuration.
	 */
	public function selectSubElementsConfig($principal, $masterElementId, $fieldName) {	
		$this->executionSink()->publishStartOperation("selectSubElementsConfig", $principal);
		try {					
			// checks if masterElementId is a root element or a subelement
			// if root element, then gets the module name
			$dbRow = $this->getMySqlFacade()->selectOne($principal,
						$this->getSqlForSelectSubElementsConfig($masterElementId),
						$this->getDbAdminService()->getDbConnectionSettings($principal));
			if(is_null($dbRow)) throw new ConfigServiceException("invalid masterElementId, no element found", ConfigServiceException::INVALID_ARGUMENT);		
			
			$linkSelectorList = LinkSelectorListArrayImpl::createInstance();	
			// master element is already a subitem, then gets path to root
			if(isset($dbRow['id_element_parent'])) {
				$this->getElementService()->getSubElementPathFromRoot($principal, $masterElementId, $linkSelectorList);
			}
			
			// adds link to subitems from given master element
			$ls = LinkSelector::createInstance($masterElementId, $fieldName);
			$ls->setModuleName($dbRow['modulename']);
			$linkSelectorList->addLinkSelector($ls);
			
			// centers the configuration on the sub elements
			$this->doSelectSubElementConfig($principal, $linkSelectorList);
		}
		catch(Exception $e) {
			$this->executionSink()->publishEndOperationOnError("selectSubElementsConfig", $e, $principal);
			throw $e;
		}
		$this->executionSink()->publishEndOperation("selectSubElementsConfig", $principal);
	}
	protected function getSqlForSelectSubElementsConfig($masterElementId) {
		if(is_null($masterElementId)) throw new ConfigServiceException("masterElementId cannot be null", ConfigServiceException::INVALID_ARGUMENT);
		$masterElementId = $this->getMySqlFacade()->getSqlBuilder()->formatBinExp('id_element', '=', $masterElementId, MySqlQueryBuilder::SQLTYPE_INT);
		return "select modulename, id_element_parent from Elements where $masterElementId";
	}
	
	/**
	 * Selects the configuration of a sub element
	 * @param Principal $principal authenticated user performing the operation
	 * @param Scalar $subElementId the id of the sub element for which to select the configuration
	 * @throws ConfigServiceException if an error occurs or if subElementId is not a sub element.
	 * Postconditions: the configuration service is centered on the configuration of this sub element.
	 * -> getCurrentModule returns the module of the sub element
	 * -> getCurrentMasterElementId returns the id of the parent of this sub element
	 * -> getCurrentFieldName returns the field name to which this sub element is linked
	 * -> ConfigService methods m, mf, ma, getFields, getParameter and getTemplatePath return
	 * values using the sub element configuration.
	 */
	public function selectSubElementConfig($principal, $subElementId) {
		$this->executionSink()->publishStartOperation("selectSubElementConfig", $principal);
		try {
			$linkSelectorList = LinkSelectorListArrayImpl::createInstance();
			if($this->getElementService()->getSubElementPathFromRoot($principal, $subElementId, $linkSelectorList) > 0) {
				$this->doSelectSubElementConfig($principal, $linkSelectorList);
			}
			else throw new ConfigServiceException("sub element '$subElementId' is not a sub element", ConfigServiceException::INVALID_ARGUMENT);
		}
		catch(Exception $e) {
			$this->executionSink()->publishEndOperationOnError("selectSubElementConfig", $e, $principal);
			throw $e;
		}
		$this->executionSink()->publishEndOperation("selectSubElementConfig", $principal);
	}		
	
	/**
	 * Unselects any sub element configuration.
	 * @param Principal $principal authenticated user performing the operation
	 * Postcondition: the ConfigService delegates all calls to the injected ConfigService,
	 * no use of the sub element config cache.
	 */
	public function unselectSubElementConfig($principal) {
		if(isset($this->currentConfigModule)) {
			$this->executionSink()->publishStartOperation("unselectSubElementConfig", $principal);
			try {
				unset($this->currentConfigModule);
				unset($this->currentConfigUrl);
				unset($this->currentLinkSelector);
				unset($this->currentLinkSelectorList);
			}
			catch(Exception $e) {
				$this->executionSink()->publishEndOperationOnError("unselectSubElementConfig", $e, $principal);
				throw $e;
			}
			$this->executionSink()->publishEndOperation("unselectSubElementConfig", $principal);
		}
	}
	
	// ConfigService implementation
	
	public function allowGroupDynamicConfig($principal, $module) {
		return $this->getConfigService()->allowGroupDynamicConfig($principal, $module);
	}
	public function getGroupConfigFilename($principal, $group) {
		return $this->getConfigService()->getGroupConfigFilename($principal, $group);
	}
	public function doesGroupHasConfigFile($principal, $group) {
		return $this->getConfigService()->doesGroupHasConfigFile($principal, $group);
	}
	public function getUserConfigFilename($principal, $module, $user) {
		return $this->getConfigService()->getUserConfigFilename($principal, $module, $user);
	}
	public function doesUserHasConfigFile($principal, $module, $user) {
		return $this->getConfigService()->doesUserHasConfigFile($principal, $module, $user);
	}
	public function getModuleConfigFilename($principal, $module, $wigiiNamespace) {
		return $this->getConfigService()->getModuleConfigFilename($principal, $module, $wigiiNamespace);
	}
	public function listModuleConfigFilesForWigiiNamespace($principal, $filenameRenderer) {
		return $this->getConfigService()->listModuleConfigFilesForWigiiNamespace($principal, $filenameRenderer);
	}
	public function getGroupParameter($principal, $group, $name) {
		return $this->getConfigService()->getGroupParameter($principal, $group, $name);
	}
	public function getGroupFields($principal, $group, $activity, $fieldList) {
		return $this->getConfigService()->getGroupFields($principal, $group, $activity, $fieldList);
	}
	public function getGroupsFields($principal, $groupList, $activity, $fieldList) {
		return $this->getConfigService()->getGroupsFields($principal, $groupList, $activity, $fieldList);
	}
	public function getGroupTemplatePath($principal, $group, $activity) {
		return $this->getConfigService()->getGroupTemplatePath($principal, $group, $activity);
	}
	public function g($principal, $group) {
		return $this->getConfigService()->g($principal, $group);
	}
	public function gf($principal, $group, $activity=null) {
		return $this->getConfigService()->gf($principal, $group, $activity);
	}
	public function ga($principal, $group, $activity) {
		return $this->getConfigService()->ga($principal, $group, $activity);
	}
	public function dt($datatypeName) {
		return $this->getConfigService()->dt($datatypeName);
	}
	public function getDataType($datatypeName) {
		return $this->getConfigService()->getDataType($datatypeName);
	}
	public function getAdminConfigForClient($principal, $client) {
		return $this->getConfigService()->getAdminConfigForClient($principal, $client);
	}
	
	public function getParameter($principal, $module, $name) {
		if($this->hasConfigForModule($module)) {
			return $this->getParameterInFile($principal, $this->currentConfigUrl, $module, $name);
		}
		else return $this->getConfigService()->getParameter($principal, $module, $name);
	}	
	protected function getParameterInFile($principal, $url, $module, $name) {
		$x = $this->mInFile($principal, $url, $module)->xpath('parameters');
		if($x && isset($x[0][$name])) return (string)($x[0][$name]);
		else return null;		
	}
	public function getFields($principal, $module, $activity, $fieldList) {
		if($this->hasConfigForModule($module)) {
			return $this->getFieldsInFile($principal, $this->currentConfigUrl, $module, $activity, $fieldList);
		}
		else return $this->getConfigService()->getFields($principal, $module, $activity, $fieldList);
	}
	protected function getFieldsInFile($principal, $url, $module, $activity, $fieldList) {
		if(!isset($fieldList)) throw new ConfigServiceException("fieldList cannot be null", ConfigServiceException::INVALID_ARGUMENT);
		$moduleName = (is_object($module) ? $module->getModuleName() : $module);
		$activityName = (is_object($activity) ? $activityName = $activity->getActivityName() : $activity);		
		// looks in FieldList cache if we already have a built FieldList
		$key = "($moduleName($url($activityName)))";
		$returnValue = $this->fieldListCache[$key];
		if(!isset($returnValue)) {
			// gets Fields xml
			$x = $this->mfInFile($principal, $url, $module, $activity);
			// creates field list using main ConfigService
			$returnValue = FieldListArrayImpl::createInstance();
			$this->getConfigServiceImpl()->addFieldsToFieldListFromXmlConfig($principal, $returnValue, $x);
			// stores created FieldList in cache
			$this->fieldListCache[$key] = $returnValue;
		}
		// dumps field list into given fields list
		foreach($returnValue->getListIterator() as $f) {
			$fieldList->addField($f);
		}
	}
	public function getTemplatePath($principal, $module, $activity) {
		if($this->hasConfigForModule($module)) {
			return $this->getTemplatePathInFile($principal, $this->currentConfigUrl, $module, $activity);
		}
		else return $this->getConfigService()->getTemplatePath($principal, $module, $activity);
	}
	protected function getTemplatePathInFile($principal, $url, $module, $activity) {
		$activityName = (is_object($activity) ? $activityName = $activity->getActivityName() : $activity);
		if(is_null($activityName)) {
			$x = $this->mInFile($principal, $url, $module)->xpath('template');
		}
		else {
			$x = $this->mInFile($principal, $url, $module)->xpath($activityName.'/template');
		}	
		if($x) return $this->getConfigServiceImpl()->extractTemplatePathFromTemplate($x[0]);
		else return null;	
	}
	public function m($principal, $module) {
		if($this->hasConfigForModule($module)) {
			return $this->mInFile($principal, $this->currentConfigUrl, $module);
		}
		else return $this->getConfigService()->m($principal, $module);
	}
	protected function mInFile($principal, $url, $module) {
		$moduleName = (is_object($module) ? $module->getModuleName() : $module);
		$key = "($moduleName($url))";
		$returnValue = $this->xmlNodeCache[$key];
		if(!isset($returnValue)) {
			$returnValue = $this->loadConfigXmlNodeFromUrl($principal, $url);
			// checks presence of xml
			if(!isset($returnValue)) throw new ConfigServiceException("no XML found at url '$url'", ConfigServiceException::INVALID_ARGUMENT);
			// checks module name integrity
			if($returnValue->getName() != $moduleName) throw new ConfigServiceException("xml config fetched at url '$url' is defining module '".$returnValue->getName()."' instead of '$moduleName'", ConfigServiceException::CONFIGURATION_ERROR);
			// stores xml node in cache
			$this->xmlNodeCache[$key] = $returnValue;
		}
		return $returnValue;
	}
	public function mf($principal, $module, $activity=null) {
		if($this->hasConfigForModule($module)) {
			return $this->mfInFile($principal, $this->currentConfigUrl, $module, $activity);
		}
		else return $this->getConfigService()->mf($principal, $module, $activity);
	}
	protected function mfInFile($principal, $url, $module, $activity=null) {
		$activityName = (is_object($activity) ? $activityName = $activity->getActivityName() : $activity);
		if(is_null($activityName)) {
			$x = $this->mInFile($principal, $url, $module)->xpath('fields');
		}
		else {
			$x = $this->mInFile($principal, $url, $module)->xpath($activityName.'/fields');
		}	
		if($x) return $x[0];
		else return null;	
	}
	public function ma($principal, $module, $activity) {
		if($this->hasConfigForModule($module)) {
			return $this->maInFile($principal, $this->currentConfigUrl, $module, $activity);
		}
		else return $this->getConfigService()->ma($principal, $module, $activity);
	}
	protected function maInFile($principal, $url, $module, $activity) {
		$activityName = (is_object($activity) ? $activityName = $activity->getActivityName() : $activity);
		if(!is_null($activityName)) {
			$x = $this->mInFile($principal, $url, $module)->xpath($activityName);
			if($x) return $x[0];
			else return null;
		}
		else return null;
	}
	
	// Implementation
	
	private function loadConfigFromSession() {
		if(!isset($this->sessionKey)) throw new ConfigServiceException("sessionKey has not been set. Cannot load config from session.", ConfigServiceException::CONFIGURATION_ERROR);
		unset($this->subElementConfig);
		$sessAS = $this->getSessionAdminService();
		$this->subElementConfig = $sessAS->getData($this, $this->sessionKey);
		if(!is_array($this->subElementConfig)) $this->subElementConfig = array(
			'subElementPath' => array(),
			'configString' => array() 
		);	
		// updates session keys if needed
		$this->sessionKeys = $sessAS->getData($this, 'sessionKeys');
		if(!is_array($this->sessionKeys)) $this->sessionKeys = array();
		if(!isset($this->sessionKeys[$this->sessionKey])) {
			$this->sessionKeys[$this->sessionKey] = $this->sessionKey;
			$sessAS->storeData($this, 'sessionKeys', $this->sessionKeys);
		}	
	}
	private function saveConfigToSession() {
		if(!isset($this->sessionKey)) throw new ConfigServiceException("sessionKey has not been set. Cannot save config to session.", ConfigServiceException::CONFIGURATION_ERROR);
		$this->getSessionAdminService()->storeData($this, $this->sessionKey, $this->subElementConfig);
		$this->subElementConfigChanged = false;
	}
	/**
	 * Checks if this instance of ConfigService stores some configuration for this module
	 */
	private function hasConfigForModule($module) {
		if(!isset($this->currentConfigModule) || !isset($this->currentConfigUrl)) return false;
		if(is_object($module)) return $module === $this->currentConfigModule;
		else return $module == $this->currentConfigModuleName->getModuleName();
	}

	/**
	 * Selects the configuration of a sub element given the path from the root element
	 * @param Principal $principal authenticated user performing the operation
	 * @param LinkSelectorList $linkSelectorList a link selector list describing the path from the root element to the sub element.
	 * @throws ConfigServiceException if an error occurs.
	 * Postconditions: the configuration service is centered on the configuration of this sub element.
	 * -> getCurrentModule returns the module of the sub element
	 * -> getCurrentMasterElementId returns the id of the parent of this sub element
	 * -> getCurrentFieldName returns the field name to which this sub element is linked
	 * -> ConfigService methods m, mf, ma, getFields, getParameter and getTemplatePath return
	 * values using the sub element configuration.
	 */
	protected function doSelectSubElementConfig($principal, $linkSelectorList) {
		if(is_null($linkSelectorList) || $linkSelectorList->isEmpty()) throw new ConfigServiceException("linkSelectorList cannot be empty.", ConfigServiceException::INVALID_ARGUMENT);
		
		$mAS = $this->getModuleAdminService();
		$configPath = '';
		$configVal = null;
		$initialized = false;		
		foreach($linkSelectorList->getListIterator() as $ls) {
			if($initialized) $newConfigPath = $configPath.','.$ls->getFieldName();
			else {
				$newConfigPath = $ls->getFieldName();
				$initialized = true;
			}
			$newConfigVal = $this->subElementConfig['subElementPath'][$newConfigPath];
			if(!isset($newConfigVal)) {				
				// initializes first config val with module of root, for the others: checks module integrity.
				if(is_null($configVal)) $configVal = array('moduleName' => $ls->getModuleName(), 'url' => null);
				elseif($configVal['moduleName'] != $ls->getModuleName()) throw new ConfigServiceException("linkSelector module name (".$ls->getModuleName().") does not match module name in cache (".$configVal['moduleName'].")", ConfigServiceException::UNEXPECTED_ERROR);				
				// gets xml from previous path
				if(isset($configVal['url'])) $xml = $this->mfInFile($principal, $configVal['url'], $mAS->getModule($principal, $configVal['moduleName']));				
				else $xml = $this->getConfigService()->mf($principal, $mAS->getModule($principal, $configVal['moduleName']));
				
				// gets link field and checks link type
				$field = $xml->xpath($ls->getFieldName());
				//$this->debugLogger()->write($xml->asXml());
				if(!$field || is_null($field[0])) throw new ConfigServiceException("field '".$ls->getFieldName()."' of type Links is not defined in config of module '".$ls->getModuleName()."' for element '".$ls->getOwnerElementId()."'", ConfigServiceException::CONFIGURATION_ERROR);
				$type = (string)$field[0]['type'];
				$linkType = (string)$field[0]['linkType'];
				if($type != 'Links' || $linkType != 'subitem') throw new ConfigServiceException("field '".$ls->getFieldName()."' in module '".$ls->getModuleName()."' is not of type 'Links/subitem' as expected but of type '$type/$linkType'", ConfigServiceException::CONFIGURATION_ERROR);
				$moduleName = (string)$field[0]['module'];
				if(empty($moduleName)) throw new ConfigServiceException("field '".$ls->getFieldName()."' in module '".$ls->getModuleName()."' of type Links/subitem has no module defined", ConfigServiceException::CONFIGURATION_ERROR);
				$url = (string)$field[0]['linkConfigFile'];
				if(empty($url)) $url = null;
				
				$newConfigVal = array('moduleName' => $moduleName, 'url' => $url);
				// stores configuration in cache
				$this->subElementConfig['subElementPath'][$newConfigPath] = $newConfigVal;
				$this->subElementConfigChanged = true;
			}	
			$configPath = $newConfigPath;
			$configVal = $newConfigVal;		
		}			
		
		// updates sub element config cache into session if session key has been set
		if($this->subElementConfigChanged && isset($this->sessionKey)) {
			// if url is set then loads config content and puts it in cache 
			if(isset($configVal['url'])) $this->mInFile($principal, $configVal['url'], $configVal['moduleName']);
			// save to session
			$this->saveConfigToSession();
		}
		
		// sets current sub element for ConfigService
		$this->currentConfigModule = $mAS->getModule($principal, $configVal['moduleName']);
		$this->currentConfigUrl = $configVal['url'];
		$this->currentLinkSelector = $ls;
		$this->currentLinkSelectorList = $linkSelectorList;
	}	
	
	/**
	 * Reads an xml file given an url and returns a SimpleXml object
	 * @param Principal $principal authenticated user running the operation
	 * @param String $url url of xml file to fetch.
	 * @return SimpleXMLElement
	 */
	protected function loadConfigXmlNodeFromUrl($principal, $url) {
		// looks in cache if we already have a string for this url
		$xmlString = $this->subElementConfig['configString'][$url];
		if(is_null($xmlString)) {
			// loads xml string from url
			$xmlString = $this->getConfigXmlStringFromUrl($principal, $url);
			if(empty($xmlString)) throw new ConfigServiceException("Config file '$url' is empty", ConfigServiceException::CONFIGURATION_ERROR);
			$this->subElementConfig['configString'][$url] = $xmlString;
			$this->subElementConfigChanged = true;
		}
		// parses the xml string into an SimpleXmlObject and returns it
		return $this->readConfigXmlNodeFromString($xmlString);
	}
	
	/**
	 * Reads an xml node from a string and returns a SimpleXMLElement object
	 * In this implementation, does a simplexml_load_string
	 */
	protected function readConfigXmlNodeFromString($xmlString)
	{
		return simplexml_load_string($xmlString);
	}
	
	/**
	 * Reads a configuration xml file given an url and returns a String
	 * @param Principal $principal authenticated user running the operation
	 * @param String $url url of the text file to fetch.
	 * @return String
	 */
	protected function getConfigXmlStringFromUrl($principal, $url) {
		if(strpos($url, '..') !== false) throw new ConfigServiceException("Loading config file from '$url'. Reference to parent folder using '..' is not permitted into config file path.", ConfigServiceException::CONFIGURATION_ERROR);
		$configFilePath = $this->getConfigServiceImpl()->getClientConfigFolderPath($principal).$url;
		if(!file_exists($configFilePath)) throw new ConfigServiceException("No config file found in path '$url'", ConfigServiceException::CONFIGURATION_ERROR);
		$this->debugLogger()->write("reads config in ".$configFilePath);
		return file_get_contents($configFilePath);
	}
}
