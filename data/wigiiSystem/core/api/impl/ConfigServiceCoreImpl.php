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
 * Config Service core implementation, intented to be subclassed or used internally.
 * Created by CWE on 2 juin 09.
 * Renamed in ConfigServiceCoreImpl by CWE on February 26th 2014.
 */
class ConfigServiceCoreImpl implements ConfigService
{
	// instances management
	
	private static $instanceCounter = 0;	
	
	// instance implementation
		
	private $instanceId;
	private $_debugLogger;
	private $_executionSink;
	private $configFolderPath;
	private $datatypeFolderPath;
	private $loadedConfigFiles;
	private $loadedXmlPaths;
	private $moduleAS;
	private $sessionAS;
	private $authoS;
	private $mysqlF;
	private $dbAS;
	private $authS;
	private $fieldSelectorFuncExpParser;
	private $fieldListUnion;
	private $wrapper;
	private $groupDynamicConfigParamCache;
	private $undefinedParametersCache;	
	private $undefinedActivitiesCache;
	private $parametersSessionCache;
	private $dumpConfigEnabled=false;
	private $dumpPath;
	
	/**
	 * map (module(client(wigiiNamespace(group(user)))))=> map name=>value
	 */
	private $parameters;
	/**
	 * cache recording for each lookup path, the associated successfull lookup path.
	 * (name(module(client(wigiiNamespace(group(user)))))) => (module(client(wigiiNamespace(group(user)))))
	 */
	private $parametersLookupCache;

	/**
	 * map (module(client(wigiiNamespace(group(user(activity)))))))=>fields
	 * fields is an array with two entries :
	 * fields[0]: fields as an XML node
	 * fields[1]: fields as an array of fields
	 */
	private $fields;
	/**
	 * cache recording for each lookup path, the associated successfull lookup path.
	 */
	private $fieldsLookupCache;

	/**
	 * map (module(client(wigiiNamespace(group(user(activity)))))))=>
	 * activities is an array with one entry :
	 * activities[0]: activity as an XML node
	 */
	private $activity;
	/**
	 * cache recording for each lookup path, the associated successfull lookup path.
	 */
	private $activityLookupCache;

	/**
	 * map (module(client(wigiiNamespace(group(user(activity)))))))=>template
	 */
	private $template;
	/**
	 * cache recording for each lookup path, the associated successfull lookup path.
	 */
	private $templateLookupCache;
	/**
	 * map (module(client(wigiiNamespace(group(user)))))=> xml node
	 */
	private $xml;
	/**
	 * cache recording for each lookup path, the associated successfull lookup path.
	 */
	private $xmlLookupCache;	
	/**
	 * map (module(client(wigiiNamespace(group(user)))))=> xml string
	 */
	private $xmlstring;

	/**
	 * map dataTypeName => DataType instance
	 */
	private $dataTypes;

	/**
	 * map __datatype__(dataTypeName) => xml string
	 */
	private $dataTypeXmlString;

	// Object lifecycle
	
	public function __construct()
	{
		$this->instanceId = self::$instanceCounter++;
		$this->debugLogger()->write("creating instance");
		$this->setSessionCacheEnabled(true);
		$this->setParameterCacheIntoSessionEnabled(true);
		$this->setSharedCacheEnabled(false);
	}
	
	public function freeMemory(){
		unset($this->loadedConfigFiles);
		unset($this->loadedXmlPaths);
		unset($this->dataTypes);
		unset($this->xmlstring);
		unset($this->dataTypeXmlString);
		unset($this->xmlLookupCache);
		unset($this->xml);
		unset($this->templateLookupCache);
		unset($this->template);
		unset($this->activityLookupCache);
		unset($this->activity);
		unset($this->fieldsLookupCache);
		unset($this->fields);
		unset($this->parametersLookupCache);
		unset($this->parameters);
		unset($this->groupDynamicConfigParamCache);
		unset($this->undefinedParametersCache);
		unset($this->undefinedActivitiesCache);
		unset($this->parametersSessionCache);
	}

	public function clearSessionData() {
		if($this->isSessionCacheEnabled()) {					
			$this->freeMemory();
			$sessAS = $this->getSessionAdminService();
			$clearSomething = false;
			if(!$this->isReadOnlyXmlFromSession()) {
				$sessAS->clearData($this, "xmlstring");
				$sessAS->clearData($this, "dataTypeXmlString");
				$sessAS->clearData($this, "loadedConfigFiles");
				$sessAS->clearData($this, "undefinedParameters");
				$sessAS->clearData($this, "undefinedActivities");
				$clearSomething = true;
			}
			if(!$this->isReadOnlyParametersFromSession()) {
				$sessAS->clearData($this, "parametersSessionCache");
				$clearSomething = true;
			}
			if($clearSomething) $this->debugLogger()->write('session data cleared');
		}
	}
	
	protected function getInstanceId() {
		return $this->instanceId;
	}
	
	// Dependency injection
	
	private function debugLogger()
	{
		if(!isset($this->_debugLogger))
		{
			$this->_debugLogger = DebugLogger::getInstance("ConfigServiceCoreImpl_".$this->getInstanceId());
		}
		return $this->_debugLogger;
	}
	private function executionSink()
	{
		if(!isset($this->_executionSink))
		{
			$this->_executionSink = ExecutionSink::getInstance("ConfigServiceCoreImpl_".$this->getInstanceId());
		}
		return $this->_executionSink;
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
	
	public function setFieldListUnion($fieldListUnion)
	{
		$this->fieldListUnion = $fieldListUnion;
	}
	protected function getFieldListUnion()
	{
		// autowired
		if(!isset($this->fieldListUnion))
		{
			$this->fieldListUnion = CSFieldListUnion::createInstance();
		}
		else $this->fieldListUnion->reset();
		return $this->fieldListUnion;
	}
	
	public function setFieldSelectorFuncExpParser($fieldSelectorFuncExpParser)
	{
		$this->fieldSelectorFuncExpParser = $fieldSelectorFuncExpParser;
	}
	protected function getFieldSelectorFuncExpParser()
	{
		// autowired
		if(!isset($this->fieldSelectorFuncExpParser))
		{
			$this->fieldSelectorFuncExpParser = TechnicalServiceProvider::getFieldSelectorFuncExpParser();
		}
		return $this->fieldSelectorFuncExpParser;
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
	
	/**
	 * Sets the ConfigServiceCoreImpl which wraps this current instance.
	 * This method is called by the wrapper to inform the wrapped instance of itself to allow
	 * protected methods delegation.
	 * @param ConfigServiceCoreImpl $configServiceCoreImpl
	 */
	public function setWrappingConfigService($configServiceCoreImpl) {
		$this->wrapper = $configServiceCoreImpl;
	}
	
	
	// Configuration

		
	private $sessionCacheEnabled;
	/**
	 * Enables or not the caching of xml and parameters into the session.
	 * Activated by default.
	 */
	public function setSessionCacheEnabled($sessionCacheEnabled){
		$this->sessionCacheEnabled = $sessionCacheEnabled;
	}
	/**
	 * @return boolean
	 */
	protected function isSessionCacheEnabled(){
		return $this->sessionCacheEnabled;
	}
	
	private $sharedCacheEnabled;
	/**
	 * Enables or not the shared cache of xml and parameters in the database.
	 * This works only if session cache is also enabled.
	 * Activated by default.
	 */
	public function setSharedCacheEnabled($sharedCacheEnabled){
		$this->sharedCacheEnabled = $sharedCacheEnabled;
	}
	/**
	 * @return boolean
	 */
	protected function isSharedCacheEnabled(){
		return $this->sharedCacheEnabled;
	}
	
	/**
	 * Sets config folder path; adds an ending / if missing
	 */
	public function setConfigFolderPath($path)
	{
		if(is_null($path))
		{
			$this->configFolderPath = null;
		}
		else
		{
			$tpath = trim($path);
			if($tpath == '')
			{
				$this->configFolderPath = null;
			}
			elseif($tpath{strlen($tpath)-1} == '/')
			{
				$this->configFolderPath = $tpath;
			}
			else
			{
				$this->configFolderPath = $tpath.'/';
			}
		}
		//on garde en session cette information
		if($this->isSessionCacheEnabled()) $this->getSessionAdminService()->storeData($this,"configFolderPath", $this->configFolderPath);
	}

	/**
	 * Returns config folder path with ending /
	 */
	public function getConfigFolderPath()
	{
		if(!isset($this->configFolderPath)){
			if($this->isSessionCacheEnabled()) $this->configFolderPath = $this->getSessionAdminService()->getData($this,"configFolderPath");
			else return null;
		}
		return $this->configFolderPath;
	}

	/**
	 * Returns client config folder path
	 */
	public function getClientConfigFolderPath($principal)
	{
		if(is_null($principal)) throw new ConfigServiceException("principal cannot be null", ConfigServiceException::INVALID_ARGUMENT);
		$clientName = $principal->getWigiiNamespace()->getClient()->getClientName();
		if(is_null($clientName) || ($clientName == '')) throw new ConfigServiceException("principal is attached to EmptyClient which does not have a specific config folder.", ConfigServiceException::INVALID_ARGUMENT);
		return $this->getConfigFolderPath().$clientName."/";
	}

	/**
	 * Sets detatype folder path; adds an ending / if missing
	 */
	public function setDatatypeFolderPath($path)
	{
		if(is_null($path))
		{
			$this->datatypeFolderPath = null;
		}
		else
		{
			$tpath = trim($path);
			if($tpath == '')
			{
				$this->datatypeFolderPath = null;
			}
			elseif($tpath{strlen($tpath)-1} == '/')
			{
				$this->datatypeFolderPath = $tpath;
			}
			else
			{
				$this->datatypeFolderPath = $tpath.'/';
			}
		}
		//on garde en session cette information
		if($this->isSessionCacheEnabled()) $this->getSessionAdminService()->storeData($this,"datatypeFolderPath", $this->datatypeFolderPath);
	}
	/**
	 * Returns datatype folder path with ending /
	 */
	public function getDatatypeFolderPath()
	{
		if(!isset($this->datatypeFolderPath)){
			if($this->isSessionCacheEnabled()) $this->datatypeFolderPath = $this->getSessionAdminService()->getData($this,"datatypeFolderPath");
			else return null;
		}
		return $this->datatypeFolderPath;
	}
	
	private $parameterCacheIntoSessionEnabled;
	/**
	 * Enables or not the caching of the parameters into the session.
	 * Works only if session is enabled.
	 * Enabled by default.
	 */
	public function setParameterCacheIntoSessionEnabled($bool){
		$this->parameterCacheIntoSessionEnabled = $bool;
	}
	protected function isParameterCacheIntoSessionEnabled(){
		return $this->parameterCacheIntoSessionEnabled;
	}
	
	private $loadOnlyParameters = false;
	/**
	 * Configures the ConfigService so that it loads only the parameters section of config files.
	 * Defaults to false.
	 */
	public function setLoadOnlyParameters($bool) {
		$this->loadOnlyParameters = $bool;
		$this->debugLogger()->write("setLoadOnlyParameters=".($bool?'true':'false'));
	}
	protected function isLoadingOnlyParameters() {
		return $this->loadOnlyParameters;
	}
	
	private $readyOnlyXmlFromSession = false;
	/**
	 * Configures the ConfigService to only read xml from the session, but nevers writes it into the session.
	 * This is used if this instance of the ConfigService acts as a slave of another ConfigService.
	 * Defaults to false (read-write of xml in session).
	 * Works only if session is enabled.
	 */
	public function setReadOnlyXmlFromSession($bool) {
		$this->readyOnlyXmlFromSession = $bool;
	}
	protected function isReadOnlyXmlFromSession() {
		return $this->readyOnlyXmlFromSession;
	}
	
	private $readOnlyParametersFromSession = false;
	/**
	 * Configures the ConfigService to only read parameters from the session, but nevers writes them into the session.
	 * This is used if this instance of the ConfigService acts as a slave of another ConfigService.
	 * Defaults to false (read-write of parameters in session).
	 * Works only if session is enabled and parameter cache into session is enabled.
	 */
	public function setReadOnlyParametersFromSession($bool) {
		$this->readOnlyParametersFromSession = $bool;
	}
	protected function isReadOnlyParametersFromSession() {
		return $this->readOnlyParametersFromSession;
	}
	
	/**
	 * Enables or not the dumping of all dynamically generated XML configuration files in folder LOG_PATH/ConfigService.
	 * The dumped configuration file name is WigiiClient_WigiiNamespace_Module_Timestamp.xml
	 * At the beginining of the file, it contains an xml comment with some info about the context in which this dynamically generated XML file occured.
	 * This feature is used for debugging runtime configuration issues.
	 * This feature is disabled by default, can be activated in config.php by calling ServiceProvider::getConfigService()->setDumpConfig(true);
	 */
	public function setDumpConfig($enabled) {
		$this->dumpConfigEnabled=$enabled;
	}
	protected function getDumpConfig() {
		return $this->dumpConfigEnabled;
	}
	/**
	 * Defines the path on disk where to dump the configuration files.
	 * @param String $path should point to an existing path on disk
	 */
	public function setDumpPath($path) {
		$this->dumpPath=$path;
	}
	protected function getDumpPath() {
		if(!isset($this->dumpPath)) {
			$this->dumpPath=dirname($_SERVER["SCRIPT_FILENAME"])."/".LOG_PATH.'ConfigService/';
			// checks the existence of dump path, if not creates directory.
			if(!file_exists($this->dumpPath)) @mkdir($this->dumpPath,0777,true);
		}
		return $this->dumpPath;
	}
	
	// Service implementation
	

	public function getParameter($principal, $module, $name)
	{
		$this->executionSink()->publishStartOperation("getParameter", $principal);
		try
		{
			$moduleName = (is_string($module)? $module: (is_null($module)? null : $module->getModuleName()));
			$clientName = $principal->getWigiiNamespace()->getClient()->getClientName();
			$wigiiNamespaceName = $principal->getWigiiNamespace()->getWigiiNamespaceName();
			$username = $principal->getUserId();//since aprile 10, use id inplace of username $principal->getUsername();
			if(!isset($this->parameters)) $this->parameters = array();
			$returnValue = $this->lookupParameter($name, $moduleName, $clientName, $wigiiNamespaceName, null, $username);
		}
		catch(Exception $e)
		{
			$this->executionSink()->publishEndOperationOnError("getParameter", $e, $principal);
			throw new ConfigServiceException('',ConfigServiceException::WRAPPING, $e);
		}
		$this->executionSink()->publishEndOperation("getParameter", $principal);
		return $returnValue;
	}
	public function allowGroupDynamicConfig($principal, $module)
	{		
		$moduleName = (is_string($module)? $module: (is_null($module)? '' : $module->getModuleName()));
		$username = $principal->getUserId();
		$lp = "($moduleName($username))";
		if(!isset($this->groupDynamicConfigParamCache)) $this->groupDynamicConfigParamCache = array();
		$returnValue = $this->groupDynamicConfigParamCache[$lp];
		if($returnValue === true || $returnValue === false) {
			$this->debugLogger()->write("found cached parameter Group_enableDynamicConfig for lp $lp with value '".($returnValue?'true':'false')."'");
			return $returnValue;
		}
		else {
			$returnValue = $this->formatBoolean($this->getParameter($principal, $module, "Group_enableDynamicConfig"));
			$this->groupDynamicConfigParamCache[$lp] = $returnValue;
			return $returnValue; 
		}
	}
	/**
	 * return the config file path for a global config for this group (without wigiiNamespace selection)
	 * the reason of making the wigiiNamespace to null, is to make the configuration available from any principal
	 */
	public function getGroupConfigFilename($principal, $group){
		$files = $this->buildConfigFilePath($group->getModule()->getModuleName(), $group->getWigiiNamespace()->getClient()->getClientName(), null, $group->getId(), null, null);
		return reset($files);
	}
	private $doesGroupHasConfigFile_file_exists = null;
	public function doesGroupHasConfigFile($principal, $group){
		$key = $group->getId().$group->getModule()->getModuleName();
		if(!isset($this->doesGroupHasConfigFile_file_exists)) $this->doesGroupHasConfigFile_file_exists = array();
		if(!isset($this->doesGroupHasConfigFile_file_exists[$key])){
			$this->doesGroupHasConfigFile_file_exists[$key] = file_exists($this->getGroupConfigFilename($principal, $group));
		}
		return $this->doesGroupHasConfigFile_file_exists[$key];
	}
	/**
	 * return the config file path for a global config for this group (without wigiiNamespace selection)
	 */
	public function getUserConfigFilename($principal, $module, $user){
		$files = $this->buildConfigFilePath($module->getModuleName(), $user->getWigiiNamespace()->getClient()->getClientName(), $user->getWigiiNamespace()->getWigiiNamespaceName(), null, $user->getId(), null);
		return reset($files);
	}
	private $doesUserHasConfigFile_file_exists = null;
	public function doesUserHasConfigFile($principal, $module, $user){
		$key = $user->getId().($module ? $module->getModuleName() : "");
		if(!isset($this->doesUserHasConfigFile_file_exists)) $this->doesUserHasConfigFile_file_exists = array();
		if(!isset($this->doesUserHasConfigFile_file_exists[$key])){
			$this->doesUserHasConfigFile_file_exists[$key] = file_exists($this->getUserConfigFilename($principal, $module, $user));
		}
		return $this->doesUserHasConfigFile_file_exists[$key];
	}
	public function getModuleConfigFilename($principal, $module, $wigiiNamespace){
		$files = $this->buildConfigFilePath($module->getModuleName(), ($wigiiNamespace ? $wigiiNamespace->getClient()->getClientName() : $principal->getWigiiNamespace()->getClient()->getClientName()), ($wigiiNamespace ? $wigiiNamespace->getWigiiNamespaceName() : null), null, null, null);
		return reset($files);
	}
	public function getGroupParameter($principal, $group, $name)
	{
		$this->executionSink()->publishStartOperation("getGroupParameter", $principal);
		try
		{
			if(is_null($group)) throw new ConfigServiceException('group can not be null', ConfigServiceException::INVALID_ARGUMENT);
			$groupName = $group->getId(); //since april 10 use Id in place of name
			$moduleName = $group->getModule()->getModuleName();
			$clientName = $principal->getWigiiNamespace()->getClient()->getClientName();
			$wigiiNamespaceName = $principal->getWigiiNamespace()->getWigiiNamespaceName();
			$username = $principal->getUserId(); //since april 10 use Id in place of name $principal->getUsername();

			if(!isset($this->parameters)) $this->parameters = array();
			$returnValue = $this->lookupParameter($name, $moduleName, $clientName, $wigiiNamespaceName, $groupName, $username);
		}
		catch(ConfigServiceException $cse)
		{
			$this->executionSink()->publishEndOperationOnError("getGroupFields", $cse, $principal);
			throw $cse;
		}
		catch(Exception $e)
		{
			$this->executionSink()->publishEndOperationOnError("getGroupParameter", $e, $principal);
			throw new ConfigServiceException('',ConfigServiceException::WRAPPING, $e);
		}
		$this->executionSink()->publishEndOperation("getGroupParameter", $principal);
		return $returnValue;
	}

	public function getTemplatePath($principal, $module, $activity)
	{
		$this->executionSink()->publishStartOperation("getTemplatePath", $principal);
		try
		{
			$groupName = '';
			$moduleName = (is_string($module)? $module: (is_null($module)? null : $module->getModuleName()));
			$clientName = $principal->getWigiiNamespace()->getClient()->getClientName();
			$wigiiNamespaceName = $principal->getWigiiNamespace()->getWigiiNamespaceName();
			$username = $principal->getUserId(); //since april 10 use Id in place of name
			$activityName = (is_null($activity) ? null : $activity->getActivityName());
			$returnValue = $this->doGetTemplate($moduleName, $clientName, $wigiiNamespaceName, $groupName, $username, $activityName);

			$returnValue = $this->extractTemplatePathFromTemplate($returnValue);
		}
		catch(ConfigServiceException $cse)
		{
			$this->executionSink()->publishEndOperationOnError("getTemplatePath", $cse, $principal);
			throw $cse;
		}
		catch(Exception $e)
		{
			$this->executionSink()->publishEndOperationOnError("getTemplatePath", $e, $principal);
			throw new ConfigServiceException('',ConfigServiceException::WRAPPING, $e);
		}
		$this->executionSink()->publishEndOperation("getTemplatePath", $principal);
		return $returnValue;
	}
	/**
	 * Interprets the path attribute from a template node in a configuration file
	 * and returns a valid OS path.
	 * @param SimpleXmlNode $template template node in module xml config
	 */
	public function extractTemplatePathFromTemplate($template){
		if(isset($this->wrapper)) return $this->wrapper->extractTemplatePathFromTemplate($template);
		
		if(!isset($template)) return null;
		if($template["inClientFolder"]=="1"){
			return CLIENT_CONFIG_PATH.(string)$template["path"];
		} else {
			return CLIENT_CONFIG_PATH."../template/".(string)$template["path"];
		}
	}
	public function getGroupTemplatePath($principal, $group, $activity)
	{
		$this->executionSink()->publishStartOperation("getGroupTemplatePath", $principal);
		try
		{
			if(is_null($group)) throw new ConfigServiceException('group can not be null', ConfigServiceException::INVALID_ARGUMENT);
			$groupName = $group->getId(); //since april 10 use Id in place of name
			$moduleName = $group->getModule()->getModuleName();
			$clientName = $principal->getWigiiNamespace()->getClient()->getClientName();
			$wigiiNamespaceName = $principal->getWigiiNamespace()->getWigiiNamespaceName();
			$username = $principal->getUserId(); //since april 10 use Id in place of name
			$activityName = (is_null($activity) ? null : $activity->getActivityName());

			$returnValue = $this->doGetTemplate($moduleName, $clientName, $wigiiNamespaceName, $groupName, $username, $activityName);
			$returnValue = $this->extractTemplatePathFromTemplate($returnValue);
		}
		catch(ConfigServiceException $cse)
		{
			$this->executionSink()->publishEndOperationOnError("getGroupTemplatePath", $cse, $principal);
			throw $cse;
		}
		catch(Exception $e)
		{
			$this->executionSink()->publishEndOperationOnError("getGroupTemplatePath", $e, $principal);
			throw new ConfigServiceException('',ConfigServiceException::WRAPPING, $e);
		}
		$this->executionSink()->publishEndOperation("getGroupTemplatePath", $principal);
		return $returnValue;
	}

	public function getFields($principal, $module, $activity, $fieldList)
	{
		$this->executionSink()->publishStartOperation("getFields", $principal);
		try
		{
			$groupName = '';
			$moduleName = (is_string($module)? $module: (is_null($module)? null : $module->getModuleName()));
			$clientName = $principal->getWigiiNamespace()->getClient()->getClientName();
			$wigiiNamespaceName = $principal->getWigiiNamespace()->getWigiiNamespaceName();
			$username = $principal->getUserId(); //since april 10 use Id in place of name
			$activityName = (is_null($activity) ? null : $activity->getActivityName());
			$returnValue = $this->doGetFields($moduleName, $clientName, $wigiiNamespaceName, $groupName, $username, $activityName, $fieldList);
		}
		catch(Exception $e)
		{
			$this->executionSink()->publishEndOperationOnError("getFields", $e, $principal);
			throw new ConfigServiceException('',ConfigServiceException::WRAPPING, $e);
		}
		$this->executionSink()->publishEndOperation("getFields", $principal);
		return $returnValue;
	}
	public function getGroupFields($principal, $group, $activity, $fieldList)
	{
		$this->executionSink()->publishStartOperation("getGroupFields", $principal);
		try
		{
			if(is_null($group)) throw new ConfigServiceException('group can not be null', ConfigServiceException::INVALID_ARGUMENT);
			$groupName = $group->getId(); //since april 10 use Id in place of name
			$moduleName = $group->getModule()->getModuleName();
			$clientName = $principal->getWigiiNamespace()->getClient()->getClientName();
			$wigiiNamespaceName = $principal->getWigiiNamespace()->getWigiiNamespaceName();
			$username = $principal->getUserId(); //since april 10 use Id in place of name
			$activityName = (is_null($activity) ? null : $activity->getActivityName());

			$returnValue = $this->doGetFields($moduleName, $clientName, $wigiiNamespaceName, $groupName, $username, $activityName, $fieldList);
		}
		catch(ConfigServiceException $cse)
		{
			$this->executionSink()->publishEndOperationOnError("getGroupFields", $cse, $principal);
			throw $cse;
		}
		catch(Exception $e)
		{
			$this->executionSink()->publishEndOperationOnError("getGroupFields", $e, $principal);
			throw new ConfigServiceException('',ConfigServiceException::WRAPPING, $e);
		}
		$this->executionSink()->publishEndOperation("getGroupFields", $principal);
		return $returnValue;
	}
	public function getGroupsFields($principal, $groupList, $activity, $fieldList)
	{
		$this->executionSink()->publishStartOperation("getGroupsFields", $principal);
		try
		{
			if(is_null($groupList)) throw new ConfigServiceException('groupList cannot be null', ConfigServiceException::INVALID_ARGUMENT);
			if(is_object($groupList)) $groupList = $groupList->getListIterator();
			elseif(!is_array($groupList)) throw new ConfigServiceException('groupList should be or an ObjectList or an array', ConfigServiceException::INVALID_ARGUMENT);
			$initialized = false; $returnValue = 0;
			$module = null; $allowGroupDynamicConfig = false;
			$fieldListUnion = null;
			foreach($groupList as $group)
			{
				if(!$initialized)
				{
					$module = $group->getModule();
					$allowGroupDynamicConfig = $this->allowGroupDynamicConfig($principal, $module);
					if($allowGroupDynamicConfig) $fieldListUnion = $this->getFieldListUnion();
					$initialized = true;
				}
				// checks module
				elseif($group->getModule() !== $module) throw ConfigServiceException("module should be equal for all groups in groupList", ConfigServiceException::INVALID_ARGUMENT);

				// if config at group level
				if($allowGroupDynamicConfig)
				{
					$this->getGroupFields($principal, $group, $activity, $fieldListUnion);
				}
			}
			// if config at group level flushes fieldList union
			if($allowGroupDynamicConfig)
			{
				$returnValue = $fieldListUnion->count();
				$fieldListUnion->flush($fieldList);
			}
			// else if config at module level then returns module field list
			else
			{
				$returnValue = $this->getFields($principal, $module, $activity, $fieldList);
			}
		}
		catch(ConfigServiceException $cse)
		{
			$this->executionSink()->publishEndOperationOnError("getGroupsFields", $cse, $principal);
			throw $cse;
		}
		catch(Exception $e)
		{
			$this->executionSink()->publishEndOperationOnError("getGroupsFields", $e, $principal);
			throw new ConfigServiceException('',ConfigServiceException::WRAPPING, $e);
		}
		$this->executionSink()->publishEndOperation("getGroupsFields", $principal);
		return $returnValue;
	}
	private function doGetFields($moduleName, $clientName, $wigiiNamespaceName, $groupName, $username, $activityName, $fieldList){
		if(!isset($this->fields)) $this->fields = array();
		$farray = $this->lookupFields($moduleName, $clientName, $wigiiNamespaceName, $groupName, $username, $activityName, false);
		$returnValue = 0;

		//this happens if we try to get a config where nothing is defined
		if(!isset($farray)) throw new ConfigServiceException("No fields defined for: $moduleName, $clientName, $wigiiNamespaceName, $groupName, $username, $activityName",ConfigServiceException::INVALID_ARGUMENT);
			
		try {
			foreach($farray as $f)
			{		
				$fieldList->addField($f);
				$returnValue++;
			}				
		}
		catch(Exception $e) {
			if($e->getCode()!=ServiceException::OPERATION_CANCELED) {	
				// CWE 03.03.2016: signals duplicate field error to monitoring system
				$message='FieldList addField error on field #'.($returnValue+1).' : ('.$e->getCode().') '.$e->getMessage().".\n";
				$first=true;
				$message.='FieldList is: ';
				foreach($farray as $f) {
					if($first) $first=false;
					else $message.= ', ';
					$message.= $f->getFieldName();
				}
				$message.= "\n";
				$xml=$this->lookupFields($moduleName, $clientName, $wigiiNamespaceName, $groupName, $username, $activityName, true);
				
				$sGroupName = (is_null($groupName)?'':$groupName);
				$sModuleName = (is_null($moduleName) ? '':$moduleName);
				$sClientName = (is_null($clientName) ? '':$clientName);
				$sWigiiNamespaceName = (is_null($wigiiNamespaceName) ? '':$wigiiNamespaceName);
				$sUsername = (is_null($username) ? '':$username);
				$sActivityName = (is_null($activityName)?'':$activityName);
				$lp='('.$sModuleName.'('.$sClientName.'('.$sWigiiNamespaceName.'('.$sGroupName.'('.$sUsername.'('.$sActivityName.'))))))';
							
				if(isset($xml)) {
					$info=wigiiBPLParam('client',$sClientName,'wigiiNamespace',$sWigiiNamespaceName,'module',$sModuleName,'group',$sGroupName,'lpField',$lp);
					$this->dumpConfig($xml, $info);
					$message.="XML for $lp is in file ".$info->getValue('xmlFileName');				
				}
				else $message.= "no XML for $lp";
				ServiceProvider::getClientAdminService()->signalFatalError(new ConfigServiceException($message,ConfigServiceException::CONFIGURATION_ERROR));
			}
			throw $e;			
		}
		return $returnValue;
	}
	private function doGetTemplate($moduleName, $clientName, $wigiiNamespaceName, $groupName, $username, $activityName){
		if(!isset($this->template)) $this->template = array();
		$returnValue = $this->lookupTemplate($moduleName, $clientName, $wigiiNamespaceName, $groupName, $username, $activityName);

		//this happens if we try to get a config where nothing is defined
		if(!isset($returnValue)) throw new ConfigServiceException("No Template defined for: $moduleName, $clientName, $wigiiNamespaceName, $groupName, $username, $activityName",ConfigServiceException::NO_TEMPLATE_FOUND);

		return $returnValue;
	}
	public function m($principal, $module)
	{
		$this->executionSink()->publishStartOperation("m", $principal);
		try
		{
			$moduleName = (is_string($module)? $module: (is_null($module)? null : $module->getModuleName()));
			$clientName = $principal->getWigiiNamespace()->getClient()->getClientName();
			$wigiiNamespaceName = $principal->getWigiiNamespace()->getWigiiNamespaceName();
			$username = $principal->getUserId(); //since april 10 use Id in place of name

			if(!isset($this->xml)) $this->xml = array();
			$returnValue = $this->lookupXml($moduleName, $clientName, $wigiiNamespaceName, null, $username);
		}
		catch(Exception $e)
		{
			$this->executionSink()->publishEndOperationOnError("m", $e, $principal);
			throw new ConfigServiceException('',ConfigServiceException::WRAPPING, $e);
		}
		$this->executionSink()->publishEndOperation("m", $principal);
		return $returnValue;
	}
	public function mf($principal, $module, $activity=null)
	{
		$this->executionSink()->publishStartOperation("mf", $principal);
		try
		{
			$moduleName = (is_string($module)? $module: (is_null($module)? null : $module->getModuleName()));
			$clientName = $principal->getWigiiNamespace()->getClient()->getClientName();
			$wigiiNamespaceName = $principal->getWigiiNamespace()->getWigiiNamespaceName();
			$username = $principal->getUserId(); //since april 10 use Id in place of name
			$activityName = (is_null($activity) ? null : $activity->getActivityName());

			if(!isset($this->fields)) $this->fields = array();
			$returnValue = $this->lookupFields($moduleName, $clientName, $wigiiNamespaceName, '', $username, $activityName, true);
		}
		catch(Exception $e)
		{
			$this->executionSink()->publishEndOperationOnError("mf", $e, $principal);
			throw new ConfigServiceException('',ConfigServiceException::WRAPPING, $e);
		}
		$this->executionSink()->publishEndOperation("mf", $principal);
		return $returnValue;
	}
	public function ma($principal, $module, $activity)
	{
		$this->executionSink()->publishStartOperation("ma", $principal);
		try
		{
			if(is_null($activity)) throw new ConfigServiceException('activity can not be null', ConfigServiceException::INVALID_ARGUMENT);
			$moduleName = (is_string($module)? $module: (is_null($module)? null : $module->getModuleName()));
			$clientName = $principal->getWigiiNamespace()->getClient()->getClientName();
			$wigiiNamespaceName = $principal->getWigiiNamespace()->getWigiiNamespaceName();
			$username = $principal->getUserId(); //since april 10 use Id in place of name
			$activityName = $activity->getActivityName();

			if(!isset($this->activities)) $this->activities = array();
			$returnValue = $this->lookupActivity($moduleName, $clientName, $wigiiNamespaceName, '', $username, $activityName);
		}
		catch(Exception $e)
		{
			$this->executionSink()->publishEndOperationOnError("ma", $e, $principal);
			throw new ConfigServiceException('',ConfigServiceException::WRAPPING, $e);
		}
		$this->executionSink()->publishEndOperation("ma", $principal);
		return $returnValue;
	}
	public function g($principal, $group)
	{
		$this->executionSink()->publishStartOperation("g", $principal);
		try
		{
			if(is_null($group)) throw new ConfigServiceException('group can not be null', ConfigServiceException::INVALID_ARGUMENT);
			$groupName = $group->getId(); //since april 10 use Id in place of name
			$moduleName = $group->getModule()->getModuleName();
			$clientName = $principal->getWigiiNamespace()->getClient()->getClientName();
			$wigiiNamespaceName = $principal->getWigiiNamespace()->getWigiiNamespaceName();
			$username = $principal->getUserId(); //since april 10 use Id in place of name

			if(!isset($this->xml)) $this->xml = array();
			$returnValue = $this->lookupXml($moduleName, $clientName, $wigiiNamespaceName, $groupName, $username);
		}
		catch(ConfigServiceException $cse)
		{
			$this->executionSink()->publishEndOperationOnError("g", $cse, $principal);
			throw $cse;
		}
		catch(Exception $e)
		{
			$this->executionSink()->publishEndOperationOnError("g", $e, $principal);
			throw new ConfigServiceException('',ConfigServiceException::WRAPPING, $e);
		}
		$this->executionSink()->publishEndOperation("g", $principal);
		return $returnValue;
	}
	public function gf($principal, $group, $activity=null)
	{
		$this->executionSink()->publishStartOperation("gf", $principal);
		try
		{
			if(is_null($group)) throw new ConfigServiceException('group can not be null', ConfigServiceException::INVALID_ARGUMENT);
			$groupName = $group->getId(); //since april 10 use Id in place of name
			$moduleName = $group->getModule()->getModuleName();
			$clientName = $principal->getWigiiNamespace()->getClient()->getClientName();
			$wigiiNamespaceName = $principal->getWigiiNamespace()->getWigiiNamespaceName();
			$username = $principal->getUserId(); //since april 10 use Id in place of name
			$activityName = (is_null($activity) ? null : $activity->getActivityName());

			if(!isset($this->fields)) $this->fields = array();
			$returnValue = $this->lookupFields($moduleName, $clientName, $wigiiNamespaceName, $groupName, $username, $activityName, true);
		}
		catch(ConfigServiceException $cse)
		{
			$this->executionSink()->publishEndOperationOnError("gf", $cse, $principal);
			throw $cse;
		}
		catch(Exception $e)
		{
			$this->executionSink()->publishEndOperationOnError("gf", $e, $principal);
			throw new ConfigServiceException('',ConfigServiceException::WRAPPING, $e);
		}
		$this->executionSink()->publishEndOperation("gf", $principal);
		return $returnValue;
	}

	public function ga($principal, $group, $activity)
	{
		$this->executionSink()->publishStartOperation("ga", $principal);
		try
		{
			if(is_null($group)) throw new ConfigServiceException('group can not be null', ConfigServiceException::INVALID_ARGUMENT);
			if(is_null($activity)) throw new ConfigServiceException('activity can not be null', ConfigServiceException::INVALID_ARGUMENT);
			$groupName = $group->getId(); //since april 10 use Id in place of name
			$moduleName = $group->getModule()->getModuleName();
			$clientName = $principal->getWigiiNamespace()->getClient()->getClientName();
			$wigiiNamespaceName = $principal->getWigiiNamespace()->getWigiiNamespaceName();
			$username = $principal->getUserId(); //since april 10 use Id in place of name
			$activityName = $activity->getActivityName();
				
			if(!isset($this->activities)) $this->activities = array();
			$returnValue = $this->lookupActivity($moduleName, $clientName, $wigiiNamespaceName, $groupName, $username, $activityName, true);
		}
		catch(ConfigServiceException $cse)
		{
			$this->executionSink()->publishEndOperationOnError("ga", $cse, $principal);
			throw $cse;
		}
		catch(Exception $e)
		{
			$this->executionSink()->publishEndOperationOnError("ga", $e, $principal);
			throw new ConfigServiceException('',ConfigServiceException::WRAPPING, $e);
		}
		$this->executionSink()->publishEndOperation("ga", $principal);
		return $returnValue;
	}

	public function dt($datatypeName){
		$this->executionSink()->publishStartOperation("dt");
		try
		{
			if(!isset($this->xml)) $this->xml = array();
			$returnValue = $this->lookupDatatypeXml($datatypeName)->dbFields;
		}
		catch(Exception $e)
		{
			$this->executionSink()->publishEndOperationOnError("dt", $e);
			throw new ConfigServiceException('',ConfigServiceException::WRAPPING, $e);
		}
		$this->executionSink()->publishEndOperation("dt");
		return $returnValue;
	}
	public function getDataType($datatypeName){
		$this->executionSink()->publishStartOperation("getDataType");
		try
		{
			if(!isset($this->dataTypes)) $this->dataTypes = array();
			$returnValue = $this->lookupDataType($datatypeName);
		}
		catch(Exception $e)
		{
			$this->executionSink()->publishEndOperationOnError("getDataType", $e);
			throw new ConfigServiceException('',ConfigServiceException::WRAPPING, $e);
		}
		$this->executionSink()->publishEndOperation("getDataType");
		return $returnValue;
	}

	public function getAdminConfigForClient($principal, $client)
	{
		$this->executionSink()->publishStartOperation("getAdminConfigForClient", $principal);
		try
		{
			// checks authorization
			$this->assertPrincipalAuthorizedForGetAdminConfigForClient($principal);

			// ok, go on
			$moduleName = $this->getModuleAdminService()->getAdminModule($principal)->getModuleName();
			$clientName = $client->getClientName();
			if(!isset($this->xml)) $this->xml = array();
			$returnValue = $this->lookupXml($moduleName, $clientName, null, null, null);
		}
		catch (AuthorizationServiceException $asE){
			$this->executionSink()->publishEndOperationOnError("getAdminConfigForClient", $asE, $principal);
			throw $asE;
		}
		catch(Exception $e)
		{
			$this->executionSink()->publishEndOperationOnError("getAdminConfigForClient", $e, $principal);
			throw new ConfigServiceException('',ConfigServiceException::WRAPPING, $e);
		}
		$this->executionSink()->publishEndOperation("getAdminConfigForClient", $principal);
		return $returnValue;
	}
	/**
	 * In this implementation, only the AuthenticationService is authorized to call this method
	 */
	protected function assertPrincipalAuthorizedForGetAdminConfigForClient($principal)
	{
		if(isset($this->wrapper)) return $this->wrapper->assertPrincipalAuthorizedForGetAdminConfigForClient($principal);
		
		$this->getAuthorizationService()->assertPrincipalIsAuthenticationService($principal);
	}

	public function listModuleConfigFilesForWigiiNamespace($principal, $filenameRenderer){
		if(isset($this->wrapper)) return $this->wrapper->listModuleConfigFilesForWigiiNamespace($principal, $filenameRenderer);
		
		$this->executionSink()->publishStartOperation("listModuleConfigFilesForWigiiNamespace", $principal);
		try
		{
			$this->assertPrincipalAuthorizedForListModuleConfigFilesForWigiiNamespace($principal);
			$wigiiNamespaceName = $principal->getWigiiNamespace()->getWigiiNamespaceName();
			$sWigiiNamespaceName = (is_null($wigiiNamespaceName) || $wigiiNamespaceName == '' ? '' : strtolower($this->alignFilepathChunkWithOSConstraint($wigiiNamespaceName)));
			foreach($principal->getModuleAccess() as $module){
				$cfiles = $this->buildConfigFilePath($module->getModuleName(), $principal->getWigiiNamespace()->getClient()->getClientName(), $wigiiNamespaceName , null, null, null);
				foreach($cfiles as $cfile){
					$cfilename = basename($cfile);
					if(strpos($cfilename, $sWigiiNamespaceName."_")===0){
						$filenameRenderer->actOnFilename($cfile);
					}
				}
			}
		}
		catch (AuthorizationServiceException $asE){
			$this->executionSink()->publishEndOperationOnError("listModuleConfigFilesForWigiiNamespace", $asE, $principal);
			throw $asE;
		}
		catch(Exception $e)
		{
			$this->executionSink()->publishEndOperationOnError("listModuleConfigFilesForWigiiNamespace", $e, $principal);
			throw new ConfigServiceException('',ConfigServiceException::WRAPPING, $e);
		}
		$this->executionSink()->publishEndOperation("listModuleConfigFilesForWigiiNamespace", $principal);
	}
	protected function assertPrincipalAuthorizedForListModuleConfigFilesForWigiiNamespace($principal)
	{
		if(isset($this->wrapper)) return $this->wrapper->assertPrincipalAuthorizedForListModuleConfigFilesForWigiiNamespace($principal);
		
		$autoS = $this->getAuthorizationService();
		// checks general authorization
		$autoS->assertPrincipalAuthorized($principal, "ConfigService", "listModuleConfigFilesForWigiiNamespace");
		// check rights
		if(!$principal->isModuleEditor()){
			$autoS->fail($principal, "has no rights list module config files for wigiiNamespace");
		}
	}
	/**
	 * WARNING, since 15 april 10, groupname and username are groupId or userId
	 * builds an array of configuration file names with full path using :
	 * moduleName, clientName, wigiiNamespaceName, groupName, username and activityName, each of these can be null.
	 * this implementation builds a path equal to one of these 8 possibilities:
	 * 1. configFolderPath/clientName/wigiiNamespaceName_moduleName_groupName_username_config_gu.xml
	 * 2. configFolderPath/clientName/wigiiNamespaceName_moduleName_groupName_config_g.xml
	 * 2.1 configFolderPath/clientName/moduleName_groupName_config_g.xml
	 * 3. configFolderPath/clientName/wigiiNamespaceName_moduleName_username_config.xml
	 * 4. configFolderPath/clientName/wigiiNamespaceName_moduleName_config.xml
	 * 5. configFolderPath/clientName/moduleName_config.xml
	 * 5.1 configFolderPath/clientName/wigiiNamespaceName_config.xml
	 * 6. configFolderPath/clientName/config.xml
	 * 7. configFolderPath/moduleName_config.xml
	 * 8. configFolderPath/config.xml
	 * where wigiiNamespaceName, groupName and username are converted to lowercase.
	 * activityName is ignored, but is passed to the function if subclasses want to use it.
	 * returns null if everything is empty.
	 */
	protected function buildConfigFilePath($moduleName, $clientName, $wigiiNamespaceName, $groupName, $username, $activityName)
	{
		if(isset($this->wrapper)) return $this->wrapper->buildConfigFilePath($moduleName, $clientName, $wigiiNamespaceName, $groupName, $username, $activityName);
		
		$sModuleName = (is_null($moduleName) || $moduleName == '' ? '' : $this->alignFilepathChunkWithOSConstraint($moduleName));
		$sClientName = (is_null($clientName) || $clientName == '' ? '' : $this->alignFilepathChunkWithOSConstraint($clientName));
		$sWigiiNamespaceName = (is_null($wigiiNamespaceName) || $wigiiNamespaceName == '' ? '' : strtolower($this->alignFilepathChunkWithOSConstraint($wigiiNamespaceName)));
		$sUsername = (is_null($username) || $username == '' ? '' : strtolower($this->alignFilepathChunkWithOSConstraint($username)));
		$sGroupName = (is_null($groupName) || $groupName == '' ? '' : strtolower($this->alignFilepathChunkWithOSConstraint($groupName)));

		if($sClientName == '')
		{
			// 7. configFolderPath/moduleName_config.xml
			if($sModuleName != '')
			{
				$returnValue[] = $this->getConfigFolderPath().$sModuleName.'_config.xml';
			}
			// 8. configFolderPath/config.xml
			else
			{
				$returnValue[] = $this->getConfigFolderPath().'config.xml';
				//$returnValue[] = $this->getConfigFolderPath().'_config.xml';
			}
		}
		else
		{
			$sClientPath = $sClientName.'/';

			// 1. configFolderPath/clientName/wigiiNamespaceName_moduleName_groupName_username_config_gu.xml
			if($sModuleName != '' && $sGroupName != '' && $sUsername != '')
			{
				if($sWigiiNamespaceName == '')
				{
					$returnValue[] = $this->getConfigFolderPath().$sClientPath.$sModuleName.'_'.$sGroupName.'_'.$sUsername.'_config_gu.xml';
				} else {
					$returnValue[] = $this->getConfigFolderPath().$sClientPath.$sWigiiNamespaceName.'_'.$sModuleName.'_'.$sGroupName.'_'.$sUsername.'_config_gu.xml';
				}
			}
			// 2. configFolderPath/clientName/wigiiNamespaceName_moduleName_groupName_config_g.xml
			// 2.1 configFolderPath/clientName/moduleName_groupName_config_g.xml
			elseif($sModuleName != '' && $sGroupName != '' && $sUsername == '')
			{
				if($sWigiiNamespaceName == '')
				{
					$returnValue[] = $this->getConfigFolderPath().$sClientPath.$sModuleName.'_'.$sGroupName.'_config_g.xml';
				} else {
					$returnValue[] = $this->getConfigFolderPath().$sClientPath.$sWigiiNamespaceName.'_'.$sModuleName.'_'.$sGroupName.'_config_g.xml';
				}
			}
			// 3. configFolderPath/clientName/wigiiNamespaceName_moduleName_username_config.xml
			elseif($sModuleName != '' && $sGroupName == '' && $sUsername != '')
			{
				if($sWigiiNamespaceName == '')
				{
					$returnValue[] = $this->getConfigFolderPath().$sClientPath.$sModuleName.'_'.$sUsername.'_config.xml';
				} else {
					$returnValue[] = $this->getConfigFolderPath().$sClientPath.$sWigiiNamespaceName.'_'.$sModuleName.'_'.$sUsername.'_config.xml';
				}
			}
			// 4. configFolderPath/clientName/wigiiNamespaceName_moduleName_config.xml
			elseif($sModuleName != '' && $sGroupName == '' && $sUsername == '')
			{
				if($sWigiiNamespaceName == '')
				{
					$returnValue[] = $this->getConfigFolderPath().$sClientPath.$sModuleName.'_config.xml';
				} else {
					$returnValue[] = $this->getConfigFolderPath().$sClientPath.$sWigiiNamespaceName.'_'.$sModuleName.'_config.xml';
				}
			}
			// 5. configFolderPath/clientName/moduleName_config.xml
			elseif($sWigiiNamespaceName == '' && $sModuleName != '' && $sGroupName == '' && $sUsername == '')
			{
				$returnValue[] = $this->getConfigFolderPath().$sClientPath.$sModuleName.'_config.xml';
			}
			// 5.1 configFolderPath/clientName/wigiiNamespaceName_config.xml
			elseif($sWigiiNamespaceName != '' && $sModuleName == '' && $sGroupName == '' && $sUsername == '')
			{
				$returnValue[] = $this->getConfigFolderPath().$sClientPath.$sWigiiNamespaceName.'_config.xml';
			}
			// 6. configFolderPath/clientName/config.xml
			elseif($sWigiiNamespaceName == '' && $sModuleName == '' && $sGroupName == '' && $sUsername == '')
			{
				$returnValue[] = $this->getConfigFolderPath().$sClientPath.'config.xml';
			}
			else
			{
				$returnValue = null;
			}
		}
		return $returnValue;
	}
	protected function buildDatatypeConfigFilePath($datatype)
	{
		if(isset($this->wrapper)) return $this->wrapper->buildDatatypeConfigFilePath($datatype);
		else {
			if($datatype != '')
			{
				$returnValue = array();
				$returnValue[] = $this->getDatatypeFolderPath().$datatype.'_config.xml';
			} else {
				$returnValue = null;
			}
			return $returnValue;
		}
	}

	/**
	 * Aligns a string representing a file path chunk to match OS constraints
	 * In this implementation, spaces are replaced by underscores.
	 */
	protected function alignFilepathChunkWithOSConstraint($filepathChunk)
	{
		if(isset($this->wrapper)) return $this->wrapper->alignFilepathChunkWithOSConstraint($filepathChunk);
		else return str_replace(' ', '_', $filepathChunk);
	}

	/**
	 * Check if config file is available for reading
	 * In this implementation, only does a file_exists.
	 */
	protected function isConfigFileAvailable($configFilePath)
	{
		if(isset($this->wrapper)) return $this->wrapper->isConfigFileAvailable($configFilePath);
		else return file_exists($configFilePath);
	}

	/**
	 * Reads config file and returns a SimpleXMLElement object
	 * In this implementation, does a simplexml_load_file
	 */
	protected function readConfigFile($configFilePath)
	{
		if(isset($this->wrapper)) return $this->wrapper->readConfigFile($configFilePath);
		else {
			$this->debugLogger()->write("reads config in ".$configFilePath);		
			return simplexml_load_file($configFilePath);
		}
	}

	/**
	 * Clones a configuration xml node
	 * In this implementation uses simplexml_load_string
	 */
	public function cloneConfigXmlNode($xmlConfig)
	{
		if(isset($this->wrapper)) return $this->wrapper->cloneConfigXmlNode($xmlConfig);
		else return simplexml_load_string($xmlConfig->asXML());
	}

	/**
	 * Reads an xml node from a string and returns a SimpleXMLElement object
	 * In this implementation, does a simplexml_load_string
	 */
	protected function readConfigXmlNodeFromString($xmlString)
	{
		if(isset($this->wrapper)) return $this->wrapper->readConfigXmlNodeFromString($xmlString);
		else {
			//$this->debugLogger()->write('parses: '.$xmlString);
			return simplexml_load_string($xmlString);
		}
	}

	/**
	 * Writes a configuration xml node represented by a SimpleXMLElement object to a string.
	 * In this implementation calls asXML() method
	 */
	protected function writeConfigXmlNodeToString($xmlConfig)
	{
		if(isset($this->wrapper)) return $this->wrapper->writeConfigXmlNodeToString($xmlConfig);
		else return $xmlConfig->asXML();
	}

	// Utilities

	/**
	 * 1. first looks for (module(client(wigiiNamespace(group(user)))))[name]
	 * 2. else  looks for (module(client(wigiiNamespace(group()))))[name]
	 * 2.1 else  looks for (module(client((group()))))[name]
	 * 3. else  looks for (module(client(wigiiNamespace((user)))))[name]
	 * 4. else  looks for (module(client(wigiiNamespace(()))))[name]
	 * 5. else  looks for (module(client((()))))[name]
	 * 6. else  looks for (module(((()))))[name]
	 * 6.1. else  looks for ((client(wigiiNamespace(()))))[name]
	 * 6.2. else  looks for ((client((()))))[name]
	 * 7. else  looks for ((((()))))[name]
	 */
	protected function lookupParameter($name, $moduleName, $clientName, $wigiiNamespaceName, $groupName, $username)
	{
		if(!isset($this->parameters)) return null;

		$sGroupName = (is_null($groupName)?'':$groupName);
		$sName = (is_null($name)?'':$name);
		$sModuleName = (is_null($moduleName) ? '':$moduleName);
		$sClientName = (is_null($clientName) ? '':$clientName);
		$sWigiiNamespaceName = (is_null($wigiiNamespaceName) ? '':$wigiiNamespaceName);
		$sUsername = (is_null($username) ? '':$username);

		// first looks in lookup cache
		$originalLp = '('.$sName.'('.$sModuleName.'('.$sClientName.'('.$sWigiiNamespaceName.'('.$sGroupName.'('.$sUsername.'))))))';
		$xmlOriginalLp = '('.$sModuleName.'('.$sClientName.'('.$sWigiiNamespaceName.'('.$sGroupName.'('.$sUsername.')))))';
		$this->debugLogger()->write("lookupParameter for originalLp:".$originalLp);
		$lp = $this->getParameterCachedLookupPath($originalLp);
		if(!is_null($lp))
		{
			$this->debugLogger()->write("lookupParameter found lp:".$lp);
			$result = $this->getLoadedParameter($lp, $sName);
			if(!is_null($result)){
				$this->debugLogger()->write("lookupParameter found result for lp".$lp." sName:".$sName);
				return $result;
			}
		}
		// else if shared cache enabled, then looks if original parameter lp is not marked as undefined
		if($this->isSharedCacheEnabled() && $this->isParameterUndefined($xmlOriginalLp, $sName)) {
			$this->debugLogger()->write("parameter is undefined");
			return null;
		}
		// else looks in xml lookup cache
		$lp = $this->getXmlCachedLookupPath($xmlOriginalLp);
		if(!is_null($lp)) {
			$result = $this->getLoadedParameter($lp, $sName);
			if(!is_null($result)){
				// caches lookup path for future searches
				$this->cacheParameterLookupPath($originalLp, $lp);
				return $result;
			}
			// checks that parameter is not marked as undefined
			if($this->isParameterUndefined($lp, $sName)) {
				if($this->isSharedCacheEnabled()) {
					$this->markParameterAsUndefined($xmlOriginalLp, $sName);
				}
				$this->debugLogger()->write("parameter is undefined");
				return null;
			}
		}
		// else looks in session cache
		if($this->isParameterCacheIntoSessionEnabled()) {
			$result = $this->getParameterFromSessionCache($sName, $sModuleName, $sClientName, $sWigiiNamespaceName, $sGroupName, $sUsername);
			if(!is_null($result)) {
				$this->debugLogger()->write("found parameter ".$sName." in session cache with value = ".$result);
				return $result;
			}
		}
		
		// else looks up in map
		$undefinedParamLp = null; 
		$topestUndefinedParamLp = $xmlOriginalLp;
		if($sModuleName != '')
		{
			if($sClientName != '')
			{
				/* empty wigiiNamespace is permitted
				 if($sWigiiNamespaceName != '')
				 {
				*/
				if($sGroupName != '')
				{
					if($sUsername != '')
					{
						// 1. //
						$lp = '('.$sModuleName.'('.$sClientName.'('.$sWigiiNamespaceName.'('.$sGroupName.'('.$sUsername.')))))';
						if(is_null($xmlOriginalLp)) $xmlOriginalLp = $lp;
						$result = $this->getLoadedParameter($lp, $sName);
						if(!is_null($result))
						{
							// caches lookup path for future searches
							$this->cacheParameterLookupPath($originalLp, $lp);
							// caches xml lookup path
							if(!$this->isParameterCacheIntoSessionEnabled() && is_null($this->getXmlCachedLookupPath($xmlOriginalLp))) $this->cacheXmlLookupPath($xmlOriginalLp, $lp);
							return $result;
						}
						if($this->loadConfig($sModuleName, $sClientName, $sWigiiNamespaceName, $sGroupName, $sUsername, null))
						{
							if(!is_null($this->getLoadedXml($lp))) {
								// caches xml lookup path
								if(!$this->isParameterCacheIntoSessionEnabled()) {
									if(is_null($this->getXmlCachedLookupPath($xmlOriginalLp))) $this->cacheXmlLookupPath($xmlOriginalLp, $lp);
									if($xmlOriginalLp != $lp) $this->cacheXmlLookupPath($lp, $lp);
								}
								
								$result = $this->getLoadedParameter($lp, $sName);
								if(!is_null($result))
								{
									// caches lookup path for future searches
									$this->cacheParameterLookupPath($originalLp, $lp);
									return $result;
								}
								else $xmlOriginalLp = null;
							}
						}
					}
					// 2. //
					if($sWigiiNamespaceName != ''){
						$lp = '('.$sModuleName.'('.$sClientName.'('.$sWigiiNamespaceName.'('.$sGroupName.'()))))';
						if(is_null($xmlOriginalLp)) $xmlOriginalLp = $lp;
						$result = $this->getLoadedParameter($lp, $sName);
						if(!is_null($result))
						{
							// caches lookup path for future searches
							$this->cacheParameterLookupPath($originalLp, $lp);
							// caches xml lookup path
							if(!$this->isParameterCacheIntoSessionEnabled() && is_null($this->getXmlCachedLookupPath($xmlOriginalLp))) $this->cacheXmlLookupPath($xmlOriginalLp, $lp);
							return $result;
						}
						if($this->loadConfig($sModuleName, $sClientName, $sWigiiNamespaceName, $sGroupName, null, null))
						{
							if(!is_null($this->getLoadedXml($lp))) {
								// caches xml lookup path
								if(!$this->isParameterCacheIntoSessionEnabled()) {
									if(is_null($this->getXmlCachedLookupPath($xmlOriginalLp))) $this->cacheXmlLookupPath($xmlOriginalLp, $lp);
									if($xmlOriginalLp != $lp) $this->cacheXmlLookupPath($lp, $lp);
								}
								
								$result = $this->getLoadedParameter($lp, $sName);
								if(!is_null($result))
								{
									// caches lookup path for future searches
									$this->cacheParameterLookupPath($originalLp, $lp);
									return $result;
								}
								else $xmlOriginalLp = null;
							}
						}
					}
					// 2.1 //
					$lp = '('.$sModuleName.'('.$sClientName.'(('.$sGroupName.'()))))';
					if(is_null($xmlOriginalLp)) $xmlOriginalLp = $lp;
					$result = $this->getLoadedParameter($lp, $sName);
					if(!is_null($result))
					{
						// caches lookup path for future searches
						$this->cacheParameterLookupPath($originalLp, $lp);
						// caches xml lookup path
						if(!$this->isParameterCacheIntoSessionEnabled() && is_null($this->getXmlCachedLookupPath($xmlOriginalLp))) $this->cacheXmlLookupPath($xmlOriginalLp, $lp);
						return $result;
					}
					if($this->loadConfig($sModuleName, $sClientName, null, $sGroupName, null, null))
					{
						if(!is_null($this->getLoadedXml($lp))) {
							// caches xml lookup path
							if(!$this->isParameterCacheIntoSessionEnabled()) {
								if(is_null($this->getXmlCachedLookupPath($xmlOriginalLp))) $this->cacheXmlLookupPath($xmlOriginalLp, $lp);
								if($xmlOriginalLp != $lp) $this->cacheXmlLookupPath($lp, $lp);
							}
							
							$result = $this->getLoadedParameter($lp, $sName);
							if(!is_null($result))
							{
								// caches lookup path for future searches
								$this->cacheParameterLookupPath($originalLp, $lp);
								return $result;
							}
							else $xmlOriginalLp = null;
						}
					}
				}
				// 3. //
				if($sUsername != '')
				{
					$lp = '('.$sModuleName.'('.$sClientName.'('.$sWigiiNamespaceName.'(('.$sUsername.')))))';
					if(is_null($xmlOriginalLp)) $xmlOriginalLp = $lp;
					$result = $this->getLoadedParameter($lp, $sName);
					if(!is_null($result))
					{
						// caches lookup path for future searches
						$this->cacheParameterLookupPath($originalLp, $lp);
						// caches xml lookup path
						if(!$this->isParameterCacheIntoSessionEnabled() && is_null($this->getXmlCachedLookupPath($xmlOriginalLp))) $this->cacheXmlLookupPath($xmlOriginalLp, $lp);
						return $result;
					}
					if($this->loadConfig($sModuleName, $sClientName, $sWigiiNamespaceName, null, $sUsername, null))
					{
						if(!is_null($this->getLoadedXml($lp))) {
							// caches xml lookup path
							if(!$this->isParameterCacheIntoSessionEnabled()) {
								if(is_null($this->getXmlCachedLookupPath($xmlOriginalLp))) $this->cacheXmlLookupPath($xmlOriginalLp, $lp);
								if($xmlOriginalLp != $lp) $this->cacheXmlLookupPath($lp, $lp);
							}
							
							$result = $this->getLoadedParameter($lp, $sName);
							if(!is_null($result))
							{
								// caches lookup path for future searches
								$this->cacheParameterLookupPath($originalLp, $lp);
								return $result;
							}
							else $xmlOriginalLp = null;
						}
					}
				}								
				// 4. //
				$lp = '('.$sModuleName.'('.$sClientName.'('.$sWigiiNamespaceName.'(()))))';
				// looks if parameter is marked as undefined.
				// if its the case, then cuts the search and returns null.
				if($this->isParameterUndefined($lp, $sName)) {		
					if($this->isSharedCacheEnabled() && isset($topestUndefinedParamLp)) {
						$this->markParameterAsUndefined($topestUndefinedParamLp, $sName);
					}		
					$this->debugLogger()->write("parameter is undefined");
					return null;
				}	
				// else records the undefined parameter lookup path
				else $undefinedParamLp = $lp;
				if(is_null($xmlOriginalLp)) $xmlOriginalLp = $lp;
				$result = $this->getLoadedParameter($lp, $sName);
				if(!is_null($result))
				{
					// caches lookup path for future searches
					$this->cacheParameterLookupPath($originalLp, $lp);
					// caches xml lookup path
					if(!$this->isParameterCacheIntoSessionEnabled() && is_null($this->getXmlCachedLookupPath($xmlOriginalLp))) $this->cacheXmlLookupPath($xmlOriginalLp, $lp);
					return $result;
				}
				if($this->loadConfig($sModuleName, $sClientName, $sWigiiNamespaceName, null, null, null))
				{
					if(!is_null($this->getLoadedXml($lp))) {
						// caches xml lookup path
						if(!$this->isParameterCacheIntoSessionEnabled()) {
							if(is_null($this->getXmlCachedLookupPath($xmlOriginalLp))) $this->cacheXmlLookupPath($xmlOriginalLp, $lp);
							if($xmlOriginalLp != $lp) $this->cacheXmlLookupPath($lp, $lp);
						}
						
						$result = $this->getLoadedParameter($lp, $sName);
						if(!is_null($result))
						{
							// caches lookup path for future searches
							$this->cacheParameterLookupPath($originalLp, $lp);
							return $result;
						}
						else $xmlOriginalLp = null;
					}
				}
				/*}*/

				// only if wigiiNamespace is not empty, because else with have already done it before
				// because of allowed empty wigiiNamespaces
				// 5. //
				if($sWigiiNamespaceName != '')
				{
					$lp = '('.$sModuleName.'('.$sClientName.'((()))))';
					if(is_null($xmlOriginalLp)) $xmlOriginalLp = $lp;
					$result = $this->getLoadedParameter($lp, $sName);
					if(!is_null($result))
					{
						// caches lookup path for future searches
						$this->cacheParameterLookupPath($originalLp, $lp);
						// caches xml lookup path
						if(!$this->isParameterCacheIntoSessionEnabled() && is_null($this->getXmlCachedLookupPath($xmlOriginalLp))) $this->cacheXmlLookupPath($xmlOriginalLp, $lp);
						return $result;
					}
					if($this->loadConfig($sModuleName, $sClientName, null, null, null, null))
					{
						if(!is_null($this->getLoadedXml($lp))) {
							// caches xml lookup path
							if(!$this->isParameterCacheIntoSessionEnabled()) {
								if(is_null($this->getXmlCachedLookupPath($xmlOriginalLp))) $this->cacheXmlLookupPath($xmlOriginalLp, $lp);
								if($xmlOriginalLp != $lp) $this->cacheXmlLookupPath($lp, $lp);
							}
							
							$result = $this->getLoadedParameter($lp, $sName);
							if(!is_null($result))
							{
								// caches lookup path for future searches
								$this->cacheParameterLookupPath($originalLp, $lp);
								return $result;
							}
							else $xmlOriginalLp = null;
						}
					}
				}
			}
				
			// 6. //
			$lp = '('.$sModuleName.'(((()))))';
			if(is_null($xmlOriginalLp)) $xmlOriginalLp = $lp;
			$result = $this->getLoadedParameter($lp, $sName);
			if(!is_null($result))
			{
				// caches lookup path for future searches
				$this->cacheParameterLookupPath($originalLp, $lp);
				// caches xml lookup path
				if(!$this->isParameterCacheIntoSessionEnabled() && is_null($this->getXmlCachedLookupPath($xmlOriginalLp))) $this->cacheXmlLookupPath($xmlOriginalLp, $lp);
				return $result;
			}
			if($this->loadConfig($sModuleName, null, null, null, null, null))
			{
				if(!is_null($this->getLoadedXml($lp))) {
					// caches xml lookup path
					if(!$this->isParameterCacheIntoSessionEnabled()) {
						if(is_null($this->getXmlCachedLookupPath($xmlOriginalLp))) $this->cacheXmlLookupPath($xmlOriginalLp, $lp);
						if($xmlOriginalLp != $lp) $this->cacheXmlLookupPath($lp, $lp);
					}
					
					$result = $this->getLoadedParameter($lp, $sName);
					if(!is_null($result))
					{
						// caches lookup path for future searches
						$this->cacheParameterLookupPath($originalLp, $lp);
						return $result;
					}
					else $xmlOriginalLp = null;
				}
			}
		}

		// 6.1 //
		if($sClientName != '' && $sWigiiNamespaceName != ''){
			$lp = '(('.$sClientName.'('.$sWigiiNamespaceName.'(()))))';
			if(is_null($xmlOriginalLp)) $xmlOriginalLp = $lp;
			if(is_null($undefinedParamLp)) {
				// looks if parameter is marked as undefined.
				// if its the case, then cuts the search and returns null.
				if($this->isParameterUndefined($lp, $sName)) {
					if($this->isSharedCacheEnabled() && isset($topestUndefinedParamLp)) {
						$this->markParameterAsUndefined($topestUndefinedParamLp, $sName);
					}
					$this->debugLogger()->write("parameter is undefined");
					return null;
				}
				// else records the undefined parameter lookup path
				else $undefinedParamLp = $lp;
			}
			$result = $this->getLoadedParameter($lp, $sName);
			if(!is_null($result))
			{
				// caches lookup path for future searches
				$this->cacheParameterLookupPath($originalLp, $lp);
				// caches xml lookup path
				if(!$this->isParameterCacheIntoSessionEnabled() && is_null($this->getXmlCachedLookupPath($xmlOriginalLp))) $this->cacheXmlLookupPath($xmlOriginalLp, $lp);
				return $result;
			}
			if($this->loadConfig(null, $sClientName, $sWigiiNamespaceName, null, null, null))
			{
				if(!is_null($this->getLoadedXml($lp))) {
					// caches xml lookup path
					if(!$this->isParameterCacheIntoSessionEnabled()) {
						if(is_null($this->getXmlCachedLookupPath($xmlOriginalLp))) $this->cacheXmlLookupPath($xmlOriginalLp, $lp);
						if($xmlOriginalLp != $lp) $this->cacheXmlLookupPath($lp, $lp);
					}
					
					$result = $this->getLoadedParameter($lp, $sName);
					if(!is_null($result))
					{
						// caches lookup path for future searches
						$this->cacheParameterLookupPath($originalLp, $lp);
						return $result;
					}
					else $xmlOriginalLp = null;
				}
			}
		}

		// 6.2 //
		if($sClientName != ''){
			$lp = '(('.$sClientName.'((()))))';
			if(is_null($xmlOriginalLp)) $xmlOriginalLp = $lp;
			if(is_null($undefinedParamLp)) {
				// looks if parameter is marked as undefined.
				// if its the case, then cuts the search and returns null.
				if($this->isParameterUndefined($lp, $sName)) {
					if($this->isSharedCacheEnabled() && isset($topestUndefinedParamLp)) {
						$this->markParameterAsUndefined($topestUndefinedParamLp, $sName);
					}
					$this->debugLogger()->write("parameter is undefined");
					return null;
				}
				// else records the undefined parameter lookup path
				else $undefinedParamLp = $lp;
			}
			$result = $this->getLoadedParameter($lp, $sName);
			if(!is_null($result))
			{
				// caches lookup path for future searches
				$this->cacheParameterLookupPath($originalLp, $lp);
				// caches xml lookup path
				if(!$this->isParameterCacheIntoSessionEnabled() && is_null($this->getXmlCachedLookupPath($xmlOriginalLp))) $this->cacheXmlLookupPath($xmlOriginalLp, $lp);
				return $result;
			}
			if($this->loadConfig(null, $sClientName, null, null, null, null))
			{
				if(!is_null($this->getLoadedXml($lp))) {
					// caches xml lookup path
					if(!$this->isParameterCacheIntoSessionEnabled()) {
						if(is_null($this->getXmlCachedLookupPath($xmlOriginalLp))) $this->cacheXmlLookupPath($xmlOriginalLp, $lp);
						if($xmlOriginalLp != $lp) $this->cacheXmlLookupPath($lp, $lp);
					}
					
					$result = $this->getLoadedParameter($lp, $sName);
					if(!is_null($result))
					{
						// caches lookup path for future searches
						$this->cacheParameterLookupPath($originalLp, $lp);
						return $result;
					}
					else $xmlOriginalLp = null;
				}
			}
		}

		// 7. //
		$lp = '((((()))))';
		if(is_null($xmlOriginalLp)) $xmlOriginalLp = $lp;
		if(is_null($undefinedParamLp)) {
			// looks if parameter is marked as undefined.
			// if its the case, then cuts the search and returns null.
			if($this->isParameterUndefined($lp, $sName)) {
				if($this->isSharedCacheEnabled() && isset($topestUndefinedParamLp)) {
					$this->markParameterAsUndefined($topestUndefinedParamLp, $sName);
				}			
				$this->debugLogger()->write("parameter is undefined");
				return null;
			}
			// else records the undefined parameter lookup path
			else $undefinedParamLp = $lp;
		}
		$result = $this->getLoadedParameter($lp, $sName);
		if(!is_null($result))
		{
			// caches lookup path for future searches
			$this->cacheParameterLookupPath($originalLp, $lp);
			// caches xml lookup path
			if(!$this->isParameterCacheIntoSessionEnabled() && is_null($this->getXmlCachedLookupPath($xmlOriginalLp))) $this->cacheXmlLookupPath($xmlOriginalLp, $lp);
			return $result;
		}
		if($this->loadConfig(null, null, null, null, null, null))
		{
			if(!is_null($this->getLoadedXml($lp))) {
				// caches xml lookup path
				if(!$this->isParameterCacheIntoSessionEnabled()) {
					if(is_null($this->getXmlCachedLookupPath($xmlOriginalLp))) $this->cacheXmlLookupPath($xmlOriginalLp, $lp);
					if($xmlOriginalLp != $lp) $this->cacheXmlLookupPath($lp, $lp);
				}
				
				$result = $this->getLoadedParameter($lp, $sName);
				if(!is_null($result))
				{
					// caches lookup path for future searches
					$this->cacheParameterLookupPath($originalLp, $lp);
					return $result;
				}
				else $xmlOriginalLp = null;
			}
		}

		$this->debugLogger()->write("parameter not found");
		if(isset($undefinedParamLp)) {
			$this->markParameterAsUndefined($undefinedParamLp, $sName);
			if($this->isSharedCacheEnabled() && isset($topestUndefinedParamLp)) {
				$this->markParameterAsUndefined($topestUndefinedParamLp, $sName);
			}
		}
		return null;
	}

	protected function getLoadedParameter($lookupPath, $name)
	{
		if(!isset($this->parameters)) return null;
		$this->debugLogger()->write("looks up for parameter ".$name." in ".$lookupPath);
		$xml = $this->parameters[$lookupPath];
		if(!isset($xml)) return null;
		$returnValue = $xml[$name];
		if(!isset($returnValue)) return null;
		$this->debugLogger()->write("found parameter ".$name." with value = ".$returnValue);
		return $returnValue;
	}

	private function getParameterCachedLookupPath($lookupPath)
	{
		if(!isset($this->parametersLookupCache)) return null;
		return $this->parametersLookupCache[$lookupPath];
	}

	private function cacheParameterLookupPath($lookupPath, $successfullLookupPath)
	{
		$this->debugLogger()->write("lookupParameter cacheParameterLookupPath for $lookupPath with $successfullLookupPath");
		$this->parametersLookupCache[$lookupPath] = $successfullLookupPath;
	}

	/**
	 * if activity is defined then each possibility without activita is skipped
	 *
	 * 1. first looks for (module(client(wigiiNamespace(group(user(activity))))))
	 * 2. else  looks for (module(client(wigiiNamespace(group(user())))))
	 * 3. else  looks for (module(client(wigiiNamespace(group((activity))))))
	 * 3.1 else  looks for (module(client((group((activity))))))
	 * 4. else  looks for (module(client(wigiiNamespace(group(())))))
	 * 4.1 else  looks for (module(client((group(())))))
	 * 5. else  looks for (module(client(wigiiNamespace((user(activity))))))
	 * 6. else  looks for (module(client(wigiiNamespace((user())))))
	 * 7. else  looks for (module(client(wigiiNamespace(((activity))))))
	 * 8. else  looks for (module(client(wigiiNamespace((())))))
	 * 9. else  looks for (module(client((((activity))))))
	 * 10.else  looks for (module(client(((())))))
	 * 11.else  looks for (module(((((activity))))))
	 * 12.else  looks for (module((((())))))
	 * 17.else  looks for ((client(wigiiNamespace(((activity))))))
	 * 13.else  looks for ((client((((activity))))))
	 * 14.else  looks for ((client(((())))))
	 * 15.else  looks for ((((((activity))))))
	 * 16.else  looks for (((((())))))
	 */
	private function lookupTemplate($moduleName, $clientName, $wigiiNamespaceName, $groupName, $username, $activityName)
	{
		if(!isset($this->template)) return null;
		$sGroupName = (is_null($groupName)?'':$groupName);
		$sModuleName = (is_null($moduleName) ? '':$moduleName);
		$sClientName = (is_null($clientName) ? '':$clientName);
		$sWigiiNamespaceName = (is_null($wigiiNamespaceName) ? '':$wigiiNamespaceName);
		$sUsername = (is_null($username) ? '':$username);
		$sActivityName = (is_null($activityName)?'':$activityName);

		// first looks in lookup cache
		$originalLp = '('.$sModuleName.'('.$sClientName.'('.$sWigiiNamespaceName.'('.$sGroupName.'('.$sUsername.'('.$sActivityName.'))))))';
		$lp = $this->getTemplateCachedLookupPath($originalLp);

		if(!is_null($lp))
		{
			$result = $this->getLoadedTemplate($lp);
			if(!is_null($result)) return $result;
		}

		// else looks up in map
		if($sModuleName != '')
		{
			if($sClientName != '')
			{
				/* empty wigiiNamespace is permitted
				 if($sWigiiNamespaceName != '')
				 {
				*/
				if($sGroupName != '')
				{
					if($sUsername != '')
					{
						if($sActivityName != '')
						{
							// 1. //
							$lp = '('.$sModuleName.'('.$sClientName.'('.$sWigiiNamespaceName.'('.$sGroupName.'('.$sUsername.'('.$sActivityName.'))))))';
							$result = $this->getLoadedTemplate($lp);
							if(!is_null($result))
							{
								// caches lookup path for future searches
								$this->cacheTemplateLookupPath($originalLp, $lp);
								return $result;
							}
							if($this->loadConfig($sModuleName, $sClientName, $sWigiiNamespaceName, $sGroupName, $sUsername, $sActivityName))
							{
								$result = $this->getLoadedTemplate($lp);
								if(!is_null($result))
								{
									// caches lookup path for future searches
									$this->cacheTemplateLookupPath($originalLp, $lp);
									return $result;
								}
							}
						} else { //LWR, add the else, if activityName set, then never lookup for lp without activity

							// 2. //
							$lp = '('.$sModuleName.'('.$sClientName.'('.$sWigiiNamespaceName.'('.$sGroupName.'('.$sUsername.'())))))';
							$result = $this->getLoadedTemplate($lp);
							if(!is_null($result))
							{
								// caches lookup path for future searches
								$this->cacheTemplateLookupPath($originalLp, $lp);
								return $result;
							}
							if($this->loadConfig($sModuleName, $sClientName, $sWigiiNamespaceName, $sGroupName, $sUsername, null))
							{
								$result = $this->getLoadedTemplate($lp);
								if(!is_null($result))
								{
									// caches lookup path for future searches
									$this->cacheTemplateLookupPath($originalLp, $lp);
									return $result;
								}
							}
						}
					}

					if($sActivityName != '')
					{
						// 3. //
						if($sWigiiNamespaceName!=''){
							$lp = '('.$sModuleName.'('.$sClientName.'('.$sWigiiNamespaceName.'('.$sGroupName.'(('.$sActivityName.'))))))';
							$result = $this->getLoadedTemplate($lp);
							if(!is_null($result))
							{
								// caches lookup path for future searches
								$this->cacheTemplateLookupPath($originalLp, $lp);
								return $result;
							}
							if($this->loadConfig($sModuleName, $sClientName, $sWigiiNamespaceName, $sGroupName, null, $sActivityName))
							{
								$result = $this->getLoadedTemplate($lp);
								if(!is_null($result))
								{
									// caches lookup path for future searches
									$this->cacheTemplateLookupPath($originalLp, $lp);
									return $result;
								}
							}
						}
						// 3.1 //
						$lp = '('.$sModuleName.'('.$sClientName.'(('.$sGroupName.'(('.$sActivityName.'))))))';
						$result = $this->getLoadedTemplate($lp);
						if(!is_null($result))
						{
							// caches lookup path for future searches
							$this->cacheTemplateLookupPath($originalLp, $lp);
							return $result;
						}
						if($this->loadConfig($sModuleName, $sClientName, null, $sGroupName, null, $sActivityName))
						{
							$result = $this->getLoadedTemplate($lp);
							if(!is_null($result))
							{
								// caches lookup path for future searches
								$this->cacheTemplateLookupPath($originalLp, $lp);
								return $result;
							}
						}
					} else { //LWR, add the else, if activityName set, then never lookup for lp without activity

						// 4. //
						if($sWigiiNamespaceName!=''){
							$lp = '('.$sModuleName.'('.$sClientName.'('.$sWigiiNamespaceName.'('.$sGroupName.'(())))))';
							$result = $this->getLoadedTemplate($lp);
							if(!is_null($result))
							{
								// caches lookup path for future searches
								$this->cacheTemplateLookupPath($originalLp, $lp);
								return $result;
							}
							if($this->loadConfig($sModuleName, $sClientName, $sWigiiNamespaceName, $sGroupName, null, null))
							{
								$result = $this->getLoadedTemplate($lp);
								if(!is_null($result))
								{
									// caches lookup path for future searches
									$this->cacheTemplateLookupPath($originalLp, $lp);
									return $result;
								}
							}
						}
						// 4.1 //
						$lp = '('.$sModuleName.'('.$sClientName.'(('.$sGroupName.'(())))))';
						$result = $this->getLoadedTemplate($lp);
						if(!is_null($result))
						{
							// caches lookup path for future searches
							$this->cacheTemplateLookupPath($originalLp, $lp);
							return $result;
						}
						if($this->loadConfig($sModuleName, $sClientName, null, $sGroupName, null, null))
						{
							$result = $this->getLoadedTemplate($lp);
							if(!is_null($result))
							{
								// caches lookup path for future searches
								$this->cacheTemplateLookupPath($originalLp, $lp);
								return $result;
							}
						}
					}
				}

				if($sUsername != '')
				{
					if($sActivityName != '')
					{
						// 5. //
						$lp = '('.$sModuleName.'('.$sClientName.'('.$sWigiiNamespaceName.'(('.$sUsername.'('.$sActivityName.'))))))';
						$result = $this->getLoadedTemplate($lp);
						if(!is_null($result))
						{
							// caches lookup path for future searches
							$this->cacheTemplateLookupPath($originalLp, $lp);
							return $result;
						}
						if($this->loadConfig($sModuleName, $sClientName, $sWigiiNamespaceName, null, $sUsername, $sActivityName))
						{
							$result = $this->getLoadedTemplate($lp);
							if(!is_null($result))
							{
								// caches lookup path for future searches
								$this->cacheTemplateLookupPath($originalLp, $lp);
								return $result;
							}
						}
					} else { //LWR, add the else, if activityName set, then never lookup for lp without activity

						// 6. //
						$lp = '('.$sModuleName.'('.$sClientName.'('.$sWigiiNamespaceName.'(('.$sUsername.'())))))';
						$result = $this->getLoadedTemplate($lp);
						if(!is_null($result))
						{
							// caches lookup path for future searches
							$this->cacheTemplateLookupPath($originalLp, $lp);
							return $result;
						}
						if($this->loadConfig($sModuleName, $sClientName, $sWigiiNamespaceName, null, $sUsername, null))
						{
							$result = $this->getLoadedTemplate($lp);
							if(!is_null($result))
							{
								// caches lookup path for future searches
								$this->cacheTemplateLookupPath($originalLp, $lp);
								return $result;
							}
						}
					}
				}

				if($sActivityName != '')
				{
					// 7. //
					$lp = '('.$sModuleName.'('.$sClientName.'('.$sWigiiNamespaceName.'((('.$sActivityName.'))))))';
					$result = $this->getLoadedTemplate($lp);
					if(!is_null($result))
					{
						// caches lookup path for future searches
						$this->cacheTemplateLookupPath($originalLp, $lp);
						return $result;
					}
					if($this->loadConfig($sModuleName, $sClientName, $sWigiiNamespaceName, null, null, $sActivityName))
					{
						$result = $this->getLoadedTemplate($lp);
						if(!is_null($result))
						{
							// caches lookup path for future searches
							$this->cacheTemplateLookupPath($originalLp, $lp);
							return $result;
						}
					}
				} else { //LWR, add the else, if activityName set, then never lookup for lp without activity

					// 8. //
					$lp = '('.$sModuleName.'('.$sClientName.'('.$sWigiiNamespaceName.'((())))))';
					$result = $this->getLoadedTemplate($lp);
					if(!is_null($result))
					{
						// caches lookup path for future searches
						$this->cacheTemplateLookupPath($originalLp, $lp);
						return $result;
					}
					if($this->loadConfig($sModuleName, $sClientName, $sWigiiNamespaceName, null, null, null))
					{
						$result = $this->getLoadedTemplate($lp);
						if(!is_null($result))
						{
							// caches lookup path for future searches
							$this->cacheTemplateLookupPath($originalLp, $lp);
							return $result;
						}
					}
				}
				/*}*/

				// only if wigiiNamespace is not empty, because else with have already done it before
				// because of allowed empty wigiiNamespaces
				if($sWigiiNamespaceName != '')
				{
					if($sActivityName != '')
					{
						// 9. //
						$lp = '('.$sModuleName.'('.$sClientName.'(((('.$sActivityName.'))))))';
						$result = $this->getLoadedTemplate($lp);
						if(!is_null($result))
						{
							// caches lookup path for future searches
							$this->cacheTemplateLookupPath($originalLp, $lp);
							return $result;
						}
						if($this->loadConfig($sModuleName, $sClientName, null, null, null, $sActivityName))
						{
							$result = $this->getLoadedTemplate($lp);
							if(!is_null($result))
							{
								// caches lookup path for future searches
								$this->cacheTemplateLookupPath($originalLp, $lp);
								return $result;
							}
						}
					} else { //LWR, add the else, if activityName set, then never lookup for lp without activity

						// 10. //
						$lp = '('.$sModuleName.'('.$sClientName.'(((())))))';
						$result = $this->getLoadedTemplate($lp);
						if(!is_null($result))
						{
							// caches lookup path for future searches
							$this->cacheTemplateLookupPath($originalLp, $lp);
							return $result;
						}
						if($this->loadConfig($sModuleName, $sClientName, null, null, null, null))
						{
							$result = $this->getLoadedTemplate($lp);
							if(!is_null($result))
							{
								// caches lookup path for future searches
								$this->cacheTemplateLookupPath($originalLp, $lp);
								return $result;
							}
						}
					}
				}
			}

			if($sActivityName != '')
			{
				// 11. //
				$lp = '('.$sModuleName.'((((('.$sActivityName.'))))))';
				$result = $this->getLoadedTemplate($lp);
				if(!is_null($result))
				{
					// caches lookup path for future searches
					$this->cacheTemplateLookupPath($originalLp, $lp);
					return $result;
				}
				if($this->loadConfig($sModuleName, null, null, null, null, $sActivityName))
				{
					$result = $this->getLoadedTemplate($lp);
					if(!is_null($result))
					{
						// caches lookup path for future searches
						$this->cacheTemplateLookupPath($originalLp, $lp);
						return $result;
					}
				}
			} else { //LWR, add the else, if activityName set, then never lookup for lp without activity

				// 12. //
				$lp = '('.$sModuleName.'((((())))))';
				$result = $this->getLoadedTemplate($lp);
				if(!is_null($result))
				{
					// caches lookup path for future searches
					$this->cacheTemplateLookupPath($originalLp, $lp);
					return $result;
				}
				if($this->loadConfig($sModuleName, null, null, null, null, null))
				{
					$result = $this->getLoadedTemplate($lp);
					if(!is_null($result))
					{
						// caches lookup path for future searches
						$this->cacheTemplateLookupPath($originalLp, $lp);
						return $result;
					}
				}
			}
		}

		if($sClientName !='')
		{
			if($sActivityName != ''){
				// 17.else  looks for ((client(wigiiNamespace(((activity)))))) //
				if($sWigiiNamespaceName != ''){
					$lp = '(('.$sClientName.'('.$sWigiiNamespaceName.'((('.$sActivityName.'))))))';
					$result = $this->getLoadedTemplate($lp);
					if(!is_null($result))
					{
						// caches lookup path for future searches
						$this->cacheTemplateLookupPath($originalLp, $lp);
						return $result;
					}
					if($this->loadConfig(null, $sClientName, $sWigiiNamespaceName, null, null, $sActivityName))
					{
						$result = $this->getLoadedTemplate($lp);
						if(!is_null($result))
						{
							// caches lookup path for future searches
							$this->cacheTemplateLookupPath($originalLp, $lp);
							return $result;
						}
					}
				}
				// 13. //
				$lp = '(('.$sClientName.'(((('.$sActivityName.'))))))';
				$result = $this->getLoadedTemplate($lp);
				if(!is_null($result))
				{
					// caches lookup path for future searches
					$this->cacheTemplateLookupPath($originalLp, $lp);
					return $result;
				}
				if($this->loadConfig(null, $sClientName, null, null, null, $sActivityName))
				{
					$result = $this->getLoadedTemplate($lp);
					if(!is_null($result))
					{
						// caches lookup path for future searches
						$this->cacheTemplateLookupPath($originalLp, $lp);
						return $result;
					}
				}

			} else { //LWR, add the else, if activityName set, then never lookup for lp without activity

				// 14. //
				$lp = '(('.$sClientName.'(((())))))';
				$result = $this->getLoadedTemplate($lp);
				if(!is_null($result))
				{
					// caches lookup path for future searches
					$this->cacheTemplateLookupPath($originalLp, $lp);
					return $result;
				}
				if($this->loadConfig(null, $sClientName, null, null, null, null))
				{
					$result = $this->getLoadedTemplate($lp);
					if(!is_null($result))
					{
						// caches lookup path for future searches
						$this->cacheTemplateLookupPath($originalLp, $lp);
						return $result;
					}
				}
			}
		}

		if($sActivityName != ''){
			// 15. //
			$lp = '(((((('.$sActivityName.'))))))';
			$result = $this->getLoadedTemplate($lp);
			if(!is_null($result))
			{
				// caches lookup path for future searches
				$this->cacheTemplateLookupPath($originalLp, $lp);
				return $result;
			}
			if($this->loadConfig(null, null, null, null, null, $sActivityName))
			{
				$result = $this->getLoadedTemplate($lp);
				if(!is_null($result))
				{
					// caches lookup path for future searches
					$this->cacheTemplateLookupPath($originalLp, $lp);
					return $result;
				}
			}
		} else { //LWR, add the else, if activityName set, then never lookup for lp without activity

			// 16. //
			$lp = '(((((())))))';
			$result = $this->getLoadedTemplate($lp);
			if(!is_null($result))
			{
				// caches lookup path for future searches
				$this->cacheTemplateLookupPath($originalLp, $lp);
				return $result;
			}
			if($this->loadConfig(null, null, null, null, null, null))
			{
				$result = $this->getLoadedTemplate($lp);
				if(!is_null($result))
				{
					// caches lookup path for future searches
					$this->cacheTemplateLookupPath($originalLp, $lp);
					return $result;
				}
			}
		}

		$this->debugLogger()->write("Template not found");
		return null;
	}


	/**
	 *
	 * if activity is defined, then each possibility without the activity is skipped
	 *
	 * 1. first looks for (module(client(wigiiNamespace(group(user(activity))))))
	 * 2. else  looks for (module(client(wigiiNamespace(group(user())))))
	 * 3. else  looks for (module(client(wigiiNamespace(group((activity))))))
	 * 3.1 else  looks for (module(client((group((activity))))))
	 * 4. else  looks for (module(client(wigiiNamespace(group(())))))
	 * 4.1 else  looks for (module(client((group(())))))
	 * 5. else  looks for (module(client(wigiiNamespace((user(activity))))))
	 * 6. else  looks for (module(client(wigiiNamespace((user())))))
	 * 7. else  looks for (module(client(wigiiNamespace(((activity))))))
	 * 8. else  looks for (module(client(wigiiNamespace((())))))
	 * 9. else  looks for (module(client((((activity))))))
	 * 10.else  looks for (module(client(((())))))
	 * 11.else  looks for (module(((((activity))))))
	 * 12.else  looks for (module((((())))))
	 * 17.else  looks for ((client(wigiiNamespace(((activity))))))
	 * 13.else  looks for ((client((((activity))))))
	 * 14.else  looks for ((client(((())))))
	 * 15.else  looks for ((((((activity))))))
	 * 16.else  looks for (((((())))))
	 *
	 * if asXml = true returns the fields xml node instead of an array of fields
	 */
	private function lookupFields($moduleName, $clientName, $wigiiNamespaceName, $groupName, $username, $activityName, $asXml=false)
	{
			if(!isset($this->fields)) return null;
			$sGroupName = (is_null($groupName)?'':$groupName);
			$sModuleName = (is_null($moduleName) ? '':$moduleName);
			$sClientName = (is_null($clientName) ? '':$clientName);
			$sWigiiNamespaceName = (is_null($wigiiNamespaceName) ? '':$wigiiNamespaceName);
			$sUsername = (is_null($username) ? '':$username);
			$sActivityName = (is_null($activityName)?'':$activityName);

			// first looks in lookup cache
			$originalLp = '('.$sModuleName.'('.$sClientName.'('.$sWigiiNamespaceName.'('.$sGroupName.'('.$sUsername.'('.$sActivityName.'))))))';
			$xmlOriginalLp = '('.$sModuleName.'('.$sClientName.'('.$sWigiiNamespaceName.'('.$sGroupName.'('.$sUsername.')))))';
			$this->debugLogger()->write("lookupFields for originalLp:".$originalLp);
			$lp = $this->getFieldCachedLookupPath($originalLp);

			if(!is_null($lp))
			{
				$this->debugLogger()->write("lookupFields found lp:".$lp);
				$result = $this->getLoadedField($lp, $asXml);
				// in case xml is not yet loaded, loads from shared cache				
				if(is_null($result) && $this->isSharedCacheEnabled()) {
					$lpArr = $this->splitLp($lp);
					if($this->loadConfig($lpArr['moduleName'], $lpArr['clientName'], $lpArr['wigiiNamespaceName'], $lpArr['groupName'], $lpArr['username'], $lpArr['activityName'])) {
						$result = $this->getLoadedField($lp, $asXml);
					}
				}
				if(!is_null($result)){
					$this->debugLogger()->write("lookupFields found result for lp".$lp);
					return $result;
				}
			}
			// else looks in xml lookup cache
			$lp = $this->getXmlCachedLookupPath($xmlOriginalLp);
			if(!is_null($lp)) {
				$lpField = $this->addActivityNameToLP($lp, $sActivityName);
				$this->debugLogger()->write("gets fields from xml for lp: ".$lpField);
				$result = $this->getLoadedField($lpField, $asXml);
				if(!is_null($result)){
					// caches lookup path for future searches
					$this->cacheFieldLookupPath($originalLp, $lpField);
					return $result;
				}
				$xml = $this->getLoadedXml($lp);
				// in case xml is not yet loaded, loads from shared cache
				if(is_null($xml) && $this->isSharedCacheEnabled()) {					
					$lpArr = $this->splitLp($lp);
					if($this->loadConfig($lpArr['moduleName'], $lpArr['clientName'], $lpArr['wigiiNamespaceName'], $lpArr['groupName'], $lpArr['username'], $lpArr['activityName'])) {
						$result = $this->getLoadedField($lpField, $asXml);
						if(!is_null($result)){
							// caches lookup path for future searches
							$this->cacheFieldLookupPath($originalLp, $lpField);
							return $result;
						}
					}
				}
				elseif(isset($xml) && $this->loadAllUnset($lp, $lpField, $xml)) {
					$result = $this->getLoadedField($lpField, $asXml);
					if(!is_null($result)){
						// caches lookup path for future searches
						$this->cacheFieldLookupPath($originalLp, $lpField);
						return $result;
					}
				}
			}
			
			// else looks up in map
			if($sModuleName != '')
			{
				if($sClientName != '')
				{
					/* empty wigiiNamespace is permitted
					 if($sWigiiNamespaceName != '')
					 {
					*/
					if($sGroupName != '')
					{
						if($sUsername != '')
						{
							if($sActivityName != '')
							{
								// 1. //
								$lp = '('.$sModuleName.'('.$sClientName.'('.$sWigiiNamespaceName.'('.$sGroupName.'('.$sUsername.'('.$sActivityName.'))))))';
								$result = $this->getLoadedField($lp, $asXml);
								if(!is_null($result))
								{
									// caches lookup path for future searches
									$this->cacheFieldLookupPath($originalLp, $lp);
									return $result;
								}
								if($this->loadConfig($sModuleName, $sClientName, $sWigiiNamespaceName, $sGroupName, $sUsername, $sActivityName))
								{
									$result = $this->getLoadedField($lp, $asXml);
									if(!is_null($result))
									{
										// caches lookup path for future searches
										$this->cacheFieldLookupPath($originalLp, $lp);
										return $result;
									}
								}

							} else { //LWR, add the else, if activityName set, then never lookup for lp without activity

								// 2. //
								$lp = '('.$sModuleName.'('.$sClientName.'('.$sWigiiNamespaceName.'('.$sGroupName.'('.$sUsername.'())))))';
								$result = $this->getLoadedField($lp, $asXml);
								if(!is_null($result))
								{
									// caches lookup path for future searches
									$this->cacheFieldLookupPath($originalLp, $lp);
									return $result;
								}
								if($this->loadConfig($sModuleName, $sClientName, $sWigiiNamespaceName, $sGroupName, $sUsername, null))
								{
									$result = $this->getLoadedField($lp, $asXml);
									if(!is_null($result))
									{
										// caches lookup path for future searches
										$this->cacheFieldLookupPath($originalLp, $lp);
										return $result;
									}
								}
							}
						}

						if($sActivityName != '')
						{
							// 3. //
							if($sWigiiNamespaceName != ''){
								$lp = '('.$sModuleName.'('.$sClientName.'('.$sWigiiNamespaceName.'('.$sGroupName.'(('.$sActivityName.'))))))';
								$result = $this->getLoadedField($lp, $asXml);
								if(!is_null($result))
								{
									// caches lookup path for future searches
									$this->cacheFieldLookupPath($originalLp, $lp);
									return $result;
								}
								if($this->loadConfig($sModuleName, $sClientName, $sWigiiNamespaceName, $sGroupName, null, $sActivityName))
								{
									$result = $this->getLoadedField($lp, $asXml);
									if(!is_null($result))
									{
										// caches lookup path for future searches
										$this->cacheFieldLookupPath($originalLp, $lp);
										return $result;
									}
								}
							}
							// 3.1 //
							$lp = '('.$sModuleName.'('.$sClientName.'(('.$sGroupName.'(('.$sActivityName.'))))))';
							$result = $this->getLoadedField($lp, $asXml);
							if(!is_null($result))
							{
								// caches lookup path for future searches
								$this->cacheFieldLookupPath($originalLp, $lp);
								return $result;
							}
							if($this->loadConfig($sModuleName, $sClientName, null, $sGroupName, null, $sActivityName))
							{
								$result = $this->getLoadedField($lp, $asXml);
								if(!is_null($result))
								{
									// caches lookup path for future searches
									$this->cacheFieldLookupPath($originalLp, $lp);
									return $result;
								}
							}
						} else { //LWR, add the else, if activityName set, then never lookup for lp without activity

							// 4. //
							if($sWigiiNamespaceName != ''){
								$lp = '('.$sModuleName.'('.$sClientName.'('.$sWigiiNamespaceName.'('.$sGroupName.'(())))))';
								$result = $this->getLoadedField($lp, $asXml);
								if(!is_null($result))
								{
									// caches lookup path for future searches
									$this->cacheFieldLookupPath($originalLp, $lp);
									return $result;
								}
								if($this->loadConfig($sModuleName, $sClientName, $sWigiiNamespaceName, $sGroupName, null, null))
								{
									$result = $this->getLoadedField($lp, $asXml);
									if(!is_null($result))
									{
										// caches lookup path for future searches
										$this->cacheFieldLookupPath($originalLp, $lp);
										return $result;
									}
								}
							}
							// 4.1 //
							$lp = '('.$sModuleName.'('.$sClientName.'(('.$sGroupName.'(())))))';
							$result = $this->getLoadedField($lp, $asXml);
							if(!is_null($result))
							{
								// caches lookup path for future searches
								$this->cacheFieldLookupPath($originalLp, $lp);
								return $result;
							}
							if($this->loadConfig($sModuleName, $sClientName, null, $sGroupName, null, $sActivityName))
							{
								$result = $this->getLoadedField($lp, $asXml);
								if(!is_null($result))
								{
									// caches lookup path for future searches
									$this->cacheFieldLookupPath($originalLp, $lp);
									return $result;
								}
							}
						}
					}

					if($sUsername != '')
					{
						if($sActivityName != '')
						{
							// 5. //
							$lp = '('.$sModuleName.'('.$sClientName.'('.$sWigiiNamespaceName.'(('.$sUsername.'('.$sActivityName.'))))))';
							$result = $this->getLoadedField($lp, $asXml);
							if(!is_null($result))
							{
								// caches lookup path for future searches
								$this->cacheFieldLookupPath($originalLp, $lp);
								return $result;
							}
							if($this->loadConfig($sModuleName, $sClientName, $sWigiiNamespaceName, null, $sUsername, $sActivityName))
							{
								$result = $this->getLoadedField($lp, $asXml);
								if(!is_null($result))
								{
									// caches lookup path for future searches
									$this->cacheFieldLookupPath($originalLp, $lp);
									return $result;
								}
							}
						} else { //LWR, add the else, if activityName set, then never lookup for lp without activity

							// 6. //
							$lp = '('.$sModuleName.'('.$sClientName.'('.$sWigiiNamespaceName.'(('.$sUsername.'())))))';
							$result = $this->getLoadedField($lp, $asXml);
							if(!is_null($result))
							{
								// caches lookup path for future searches
								$this->cacheFieldLookupPath($originalLp, $lp);
								return $result;
							}
							if($this->loadConfig($sModuleName, $sClientName, $sWigiiNamespaceName, null, $sUsername, null))
							{
								$result = $this->getLoadedField($lp, $asXml);
								if(!is_null($result))
								{
									// caches lookup path for future searches
									$this->cacheFieldLookupPath($originalLp, $lp);
									return $result;
								}
							}
						}
					}

					if($sActivityName != '')
					{
						// 7. //
						$lp = '('.$sModuleName.'('.$sClientName.'('.$sWigiiNamespaceName.'((('.$sActivityName.'))))))';
						$result = $this->getLoadedField($lp, $asXml);
						if(!is_null($result))
						{
							// caches lookup path for future searches
							$this->cacheFieldLookupPath($originalLp, $lp);
							return $result;
						}
						if($this->loadConfig($sModuleName, $sClientName, $sWigiiNamespaceName, null, null, $sActivityName))
						{
							$result = $this->getLoadedField($lp, $asXml);
							if(!is_null($result))
							{
								// caches lookup path for future searches
								$this->cacheFieldLookupPath($originalLp, $lp);
								return $result;
							}
						}
					} else { //LWR, add the else, if activityName set, then never lookup for lp without activity

						// 8. //
						$lp = '('.$sModuleName.'('.$sClientName.'('.$sWigiiNamespaceName.'((())))))';
						$result = $this->getLoadedField($lp, $asXml);
						if(!is_null($result))
						{
							// caches lookup path for future searches
							$this->cacheFieldLookupPath($originalLp, $lp);
							return $result;
						}
						if($this->loadConfig($sModuleName, $sClientName, $sWigiiNamespaceName, null, null, null))
						{
							$result = $this->getLoadedField($lp, $asXml);
							if(!is_null($result))
							{
								// caches lookup path for future searches
								$this->cacheFieldLookupPath($originalLp, $lp);
								return $result;
							}
						}
					}
					/*}*/

					// only if wigiiNamespace is not empty, because else with have already done it before
					// because of allowed empty wigiiNamespaces
					if($sWigiiNamespaceName != '')
					{
						if($sActivityName != '')
						{
							// 9. //
							$lp = '('.$sModuleName.'('.$sClientName.'(((('.$sActivityName.'))))))';
							$result = $this->getLoadedField($lp, $asXml);
							if(!is_null($result))
							{
								// caches lookup path for future searches
								$this->cacheFieldLookupPath($originalLp, $lp);
								return $result;
							}
							if($this->loadConfig($sModuleName, $sClientName, null, null, null, $sActivityName))
							{
								$result = $this->getLoadedField($lp, $asXml);
								if(!is_null($result))
								{
									// caches lookup path for future searches
									$this->cacheFieldLookupPath($originalLp, $lp);
									return $result;
								}
							}
						}  else { //LWR, add the else, if activityName set, then never lookup for lp without activity

							// 10. //
							$lp = '('.$sModuleName.'('.$sClientName.'(((())))))';
							$result = $this->getLoadedField($lp, $asXml);
							if(!is_null($result))
							{
								// caches lookup path for future searches
								$this->cacheFieldLookupPath($originalLp, $lp);
								return $result;
							}
							if($this->loadConfig($sModuleName, $sClientName, null, null, null, null))
							{
								$result = $this->getLoadedField($lp, $asXml);
								if(!is_null($result))
								{
									// caches lookup path for future searches
									$this->cacheFieldLookupPath($originalLp, $lp);
									return $result;
								}
							}
						}
					}
				}

				if($sActivityName != '')
				{
					// 11. //
					$lp = '('.$sModuleName.'((((('.$sActivityName.'))))))';
					$result = $this->getLoadedField($lp, $asXml);
					if(!is_null($result))
					{
						// caches lookup path for future searches
						$this->cacheFieldLookupPath($originalLp, $lp);
						return $result;
					}
					if($this->loadConfig($sModuleName, null, null, null, null, $sActivityName))
					{
						$result = $this->getLoadedField($lp, $asXml);
						if(!is_null($result))
						{
							// caches lookup path for future searches
							$this->cacheFieldLookupPath($originalLp, $lp);
							return $result;
						}
					}
				} else { //LWR, add the else, if activityName set, then never lookup for lp without activity

					// 12. //
					$lp = '('.$sModuleName.'((((())))))';
					$result = $this->getLoadedField($lp, $asXml);
					if(!is_null($result))
					{
						// caches lookup path for future searches
						$this->cacheFieldLookupPath($originalLp, $lp);
						return $result;
					}
					if($this->loadConfig($sModuleName, null, null, null, null, null))
					{
						$result = $this->getLoadedField($lp, $asXml);
						if(!is_null($result))
						{
							// caches lookup path for future searches
							$this->cacheFieldLookupPath($originalLp, $lp);
							return $result;
						}
					}
				}
			}

			if($sClientName !='')
			{
				if($sActivityName != ''){
					// 17.else  looks for ((client(wigiiNamespace(((activity)))))) //
					if($sWigiiNamespaceName != ''){
						$lp = '(('.$sClientName.'('.$sWigiiNamespaceName.'((('.$sActivityName.'))))))';
						$result = $this->getLoadedField($lp, $asXml);
						if(!is_null($result))
						{
							// caches lookup path for future searches
							$this->cacheFieldLookupPath($originalLp, $lp);
							return $result;
						}
						if($this->loadConfig(null, $sClientName, $sWigiiNamespaceName, null, null, $sActivityName))
						{
							$result = $this->getLoadedField($lp, $asXml);
							if(!is_null($result))
							{
								// caches lookup path for future searches
								$this->cacheFieldLookupPath($originalLp, $lp);
								return $result;
							}
						}
					}
					// 13. //
					$lp = '(('.$sClientName.'(((('.$sActivityName.'))))))';
					$result = $this->getLoadedField($lp, $asXml);
					if(!is_null($result))
					{
						// caches lookup path for future searches
						$this->cacheFieldLookupPath($originalLp, $lp);
						return $result;
					}
					if($this->loadConfig(null, $sClientName, null, null, null, $sActivityName))
					{
						$result = $this->getLoadedField($lp, $asXml);
						if(!is_null($result))
						{
							// caches lookup path for future searches
							$this->cacheFieldLookupPath($originalLp, $lp);
							return $result;
						}
					}
				} else { //LWR, add the else, if activityName set, then never lookup for lp without activity

					// 14. //
					$lp = '(('.$sClientName.'(((())))))';
					$result = $this->getLoadedField($lp, $asXml);
					if(!is_null($result))
					{
						// caches lookup path for future searches
						$this->cacheFieldLookupPath($originalLp, $lp);
						return $result;
					}
					if($this->loadConfig(null, $sClientName, null, null, null, null))
					{
						$result = $this->getLoadedField($lp, $asXml);
						if(!is_null($result))
						{
							// caches lookup path for future searches
							$this->cacheFieldLookupPath($originalLp, $lp);
							return $result;
						}
					}
				}
			}

			if($sActivityName != ''){
				// 15. //
				$lp = '(((((('.$sActivityName.'))))))';
				$result = $this->getLoadedField($lp, $asXml);
				if(!is_null($result))
				{
					// caches lookup path for future searches
					$this->cacheFieldLookupPath($originalLp, $lp);
					return $result;
				}
				if($this->loadConfig(null, null, null, null, null, $sActivityName))
				{
					$result = $this->getLoadedField($lp, $asXml);
					if(!is_null($result))
					{
						// caches lookup path for future searches
						$this->cacheFieldLookupPath($originalLp, $lp);
						return $result;
					}
				}
			} else { //LWR, add the else, if activityName set, then never lookup for lp without activity

				// 16. //
				$lp = '(((((())))))';
				$result = $this->getLoadedField($lp, $asXml);
				if(!is_null($result))
				{
					// caches lookup path for future searches
					$this->cacheFieldLookupPath($originalLp, $lp);
					return $result;
				}
				if($this->loadConfig(null, null, null, null, null, null))
				{
					$result = $this->getLoadedField($lp, $asXml);
					if(!is_null($result))
					{
						// caches lookup path for future searches
						$this->cacheFieldLookupPath($originalLp, $lp);
						return $result;
					}
				}
			}

			$this->debugLogger()->write("fields not found");
			return null;
	}

	/**
	 *
	 * activity is defined, then each possibility without the activity is skipped
	 *
	 * 1. first looks for (module(client(wigiiNamespace(group(user(activity))))))
	 * 3. else  looks for (module(client(wigiiNamespace(group((activity))))))
	 * 3.1 else  looks for (module(client((group((activity))))))
	 * 5. else  looks for (module(client(wigiiNamespace((user(activity))))))
	 * 7. else  looks for (module(client(wigiiNamespace(((activity))))))
	 * 9. else  looks for (module(client((((activity))))))
	 * 11.else  looks for (module(((((activity))))))
	 * 17.else  looks for ((client(wigiiNamespace(((activity))))))
	 * 13.else  looks for ((client((((activity))))))
	 * 15.else  looks for ((((((activity))))))
	 */
	private function lookupActivity($moduleName, $clientName, $wigiiNamespaceName, $groupName, $username, $activityName)
	{
		if(!isset($this->activities)) return null;
		$sGroupName = (is_null($groupName)?'':$groupName);
		$sModuleName = (is_null($moduleName) ? '':$moduleName);
		$sClientName = (is_null($clientName) ? '':$clientName);
		$sWigiiNamespaceName = (is_null($wigiiNamespaceName) ? '':$wigiiNamespaceName);
		$sUsername = (is_null($username) ? '':$username);
		$sActivityName = (is_null($activityName)?'':$activityName);

		// first looks in lookup cache
		$originalLp = '('.$sModuleName.'('.$sClientName.'('.$sWigiiNamespaceName.'('.$sGroupName.'('.$sUsername.'('.$sActivityName.'))))))';
		$xmlOriginalLp = '('.$sModuleName.'('.$sClientName.'('.$sWigiiNamespaceName.'('.$sGroupName.'('.$sUsername.')))))';
		$this->debugLogger()->write("lookupActivity for originalLp:".$originalLp);
		$lp = $this->getActivityCachedLookupPath($originalLp);

		if(!is_null($lp))
		{
			$this->debugLogger()->write("lookupActivity found lp:".$lp);
			$result = $this->getLoadedActivity($lp);
			// in case xml is not yet loaded, loads from shared cache
			if(is_null($result) && $this->isSharedCacheEnabled()) {
				$lpArr = $this->splitLp($lp);
				if($this->loadConfig($lpArr['moduleName'], $lpArr['clientName'], $lpArr['wigiiNamespaceName'], $lpArr['groupName'], $lpArr['username'], $lpArr['activityName'])) {
					$result = $this->getLoadedActivity($lp);
				}
			}			
			if(!is_null($result)){
				$this->debugLogger()->write("lookupActivity found result for lp:".$lp);
				return $result;
			}
		}
		// else if shared cache enabled, then looks if original activity lp is not marked as undefined
		if($this->isSharedCacheEnabled() && $this->isActivityUndefined($xmlOriginalLp, $sActivityName)) {
			$this->debugLogger()->write("activity is undefined");
			return null;
		}
		// else looks in xml lookup cache
		$lp = $this->getXmlCachedLookupPath($xmlOriginalLp);
		if(!is_null($lp)) {
			$lpActivity = $this->addActivityNameToLP($lp, $sActivityName);
			$this->debugLogger()->write("gets activity from xml for lp: ".$lpActivity);
			$result = $this->getLoadedActivity($lpActivity);
			if(!is_null($result)){
				// caches lookup path for future searches
				$this->cacheActivityLookupPath($originalLp, $lpActivity);
				return $result;
			}
			$xml = $this->getLoadedXml($lp);
			// in case xml is not yet loaded, loads from shared cache
			if(is_null($xml) && $this->isSharedCacheEnabled()) {
				$lpArr = $this->splitLp($lp);
				if($this->loadConfig($lpArr['moduleName'], $lpArr['clientName'], $lpArr['wigiiNamespaceName'], $lpArr['groupName'], $lpArr['username'], $lpArr['activityName'])) {
					$result = $this->getLoadedActivity($lpActivity);
					if(!is_null($result)){
						// caches lookup path for future searches
						$this->cacheActivityLookupPath($originalLp, $lpActivity);
						return $result;
					}
				}
			}
			elseif(isset($xml) && $this->loadAllUnset($lp, $lpActivity, $xml)) {
				$result = $this->getLoadedActivity($lpActivity);
				if(!is_null($result)){
					// caches lookup path for future searches
					$this->cacheActivityLookupPath($originalLp, $lpActivity);
					return $result;
				}
			}
			// checks that activity is not marked as undefined
			if($this->isActivityUndefined($lp, $sActivityName)) {
				if($this->isSharedCacheEnabled()) {
					$this->markActivityAsUndefined($xmlOriginalLp, $sActivityName);
				}
				$this->debugLogger()->write("activity is undefined");
				return null;
			}
		}
		
		// else looks up in map
		$undefinedActivityLp = null;
		$topestUndefinedActivityLp = $xmlOriginalLp;
		if($sModuleName != '')
		{
			if($sClientName != '')
			{
				/* empty wigiiNamespace is permitted
				 if($sWigiiNamespaceName != '')
				 {
				*/
				if($sGroupName != '')
				{
					if($sUsername != '')
					{
						if($sActivityName != '')
						{
							// 1. //
							$lp = '('.$sModuleName.'('.$sClientName.'('.$sWigiiNamespaceName.'('.$sGroupName.'('.$sUsername.'('.$sActivityName.'))))))';
							$result = $this->getLoadedActivity($lp);
							if(!is_null($result))
							{
								// caches lookup path for future searches
								$this->cacheActivityLookupPath($originalLp, $lp);
								return $result;
							}
							if($this->loadConfig($sModuleName, $sClientName, $sWigiiNamespaceName, $sGroupName, $sUsername, $sActivityName))
							{
								$result = $this->getLoadedActivity($lp);
								if(!is_null($result))
								{
									// caches lookup path for future searches
									$this->cacheActivityLookupPath($originalLp, $lp);
									return $result;
								}
							}

						}
					}

					if($sActivityName != '')
					{
						// 3. //
						if($sWigiiNamespaceName != ''){
							$lp = '('.$sModuleName.'('.$sClientName.'('.$sWigiiNamespaceName.'('.$sGroupName.'(('.$sActivityName.'))))))';
							$result = $this->getLoadedActivity($lp);
							if(!is_null($result))
							{
								// caches lookup path for future searches
								$this->cacheActivityLookupPath($originalLp, $lp);
								return $result;
							}
							if($this->loadConfig($sModuleName, $sClientName, $sWigiiNamespaceName, $sGroupName, null, $sActivityName))
							{
								$result = $this->getLoadedActivity($lp);
								if(!is_null($result))
								{
									// caches lookup path for future searches
									$this->cacheActivityLookupPath($originalLp, $lp);
									return $result;
								}
							}
						}
						// 3.1 //
						$lp = '('.$sModuleName.'('.$sClientName.'(('.$sGroupName.'(('.$sActivityName.'))))))';
						$result = $this->getLoadedActivity($lp);
						if(!is_null($result))
						{
							// caches lookup path for future searches
							$this->cacheActivityLookupPath($originalLp, $lp);
							return $result;
						}
						if($this->loadConfig($sModuleName, $sClientName, null, $sGroupName, null, $sActivityName))
						{
							$result = $this->getLoadedActivity($lp);
							if(!is_null($result))
							{
								// caches lookup path for future searches
								$this->cacheActivityLookupPath($originalLp, $lp);
								return $result;
							}
						}
					}
				}

				if($sUsername != '')
				{
					if($sActivityName != '')
					{
						// 5. //
						$lp = '('.$sModuleName.'('.$sClientName.'('.$sWigiiNamespaceName.'(('.$sUsername.'('.$sActivityName.'))))))';
						$result = $this->getLoadedActivity($lp);
						if(!is_null($result))
						{
							// caches lookup path for future searches
							$this->cacheActivityLookupPath($originalLp, $lp);
							return $result;
						}
						if($this->loadConfig($sModuleName, $sClientName, $sWigiiNamespaceName, null, $sUsername, $sActivityName))
						{
							$result = $this->getLoadedActivity($lp);
							if(!is_null($result))
							{
								// caches lookup path for future searches
								$this->cacheActivityLookupPath($originalLp, $lp);
								return $result;
							}
						}
					}
				}

				if($sActivityName != '')
				{
					// 7. //
					$lp = '('.$sModuleName.'('.$sClientName.'('.$sWigiiNamespaceName.'((('.$sActivityName.'))))))';
					$xmlLp = '('.$sModuleName.'('.$sClientName.'('.$sWigiiNamespaceName.'(()))))';
					// looks if activity is marked as undefined.
					// if its the case, then cuts the search and returns null.
					if($this->isActivityUndefined($xmlLp, $sActivityName)) {
						if($this->isSharedCacheEnabled() && isset($topestUndefinedActivityLp)) {
							$this->markActivityAsUndefined($topestUndefinedActivityLp, $sActivityName);
						}
						$this->debugLogger()->write("activity is undefined");
						return null;
					}
					// else records the undefined activity lookup path
					else $undefinedActivityLp = $xmlLp;
					$result = $this->getLoadedActivity($lp);
					if(!is_null($result))
					{
						// caches lookup path for future searches
						$this->cacheActivityLookupPath($originalLp, $lp);
						return $result;
					}
					if($this->loadConfig($sModuleName, $sClientName, $sWigiiNamespaceName, null, null, $sActivityName))
					{
						$result = $this->getLoadedActivity($lp);
						if(!is_null($result))
						{
							// caches lookup path for future searches
							$this->cacheActivityLookupPath($originalLp, $lp);
							return $result;
						}
					}
				}
				/*}*/

				// only if wigiiNamespace is not empty, because else with have already done it before
				// because of allowed empty wigiiNamespaces
				if($sWigiiNamespaceName != '')
				{
					if($sActivityName != '')
					{
						// 9. //
						$lp = '('.$sModuleName.'('.$sClientName.'(((('.$sActivityName.'))))))';
						$result = $this->getLoadedActivity($lp);
						if(!is_null($result))
						{
							// caches lookup path for future searches
							$this->cacheActivityLookupPath($originalLp, $lp);
							return $result;
						}
						if($this->loadConfig($sModuleName, $sClientName, null, null, null, $sActivityName))
						{
							$result = $this->getLoadedActivity($lp);
							if(!is_null($result))
							{
								// caches lookup path for future searches
								$this->cacheActivityLookupPath($originalLp, $lp);
								return $result;
							}
						}
					}
				}
			}

			if($sActivityName != '')
			{
				// 11. //
				$lp = '('.$sModuleName.'((((('.$sActivityName.'))))))';
				$result = $this->getLoadedActivity($lp);
				if(!is_null($result))
				{
					// caches lookup path for future searches
					$this->cacheActivityLookupPath($originalLp, $lp);
					return $result;
				}
				if($this->loadConfig($sModuleName, null, null, null, null, $sActivityName))
				{
					$result = $this->getLoadedActivity($lp);
					if(!is_null($result))
					{
						// caches lookup path for future searches
						$this->cacheActivityLookupPath($originalLp, $lp);
						return $result;
					}
				}
			}
		}

		if($sClientName !='')
		{
			if($sActivityName != ''){
				if($sWigiiNamespaceName != ''){
					// 17. //
					$lp = '(('.$sClientName.'('.$sWigiiNamespaceName.'((('.$sActivityName.'))))))';
					$result = $this->getLoadedActivity($lp);
					if(!is_null($result))
					{
						// caches lookup path for future searches
						$this->cacheActivityLookupPath($originalLp, $lp);
						return $result;
					}
					if($this->loadConfig(null, $sClientName, $sWigiiNamespaceName, null, null, $sActivityName))
					{
						$result = $this->getLoadedActivity($lp);
						if(!is_null($result))
						{
							// caches lookup path for future searches
							$this->cacheActivityLookupPath($originalLp, $lp);
							return $result;
						}
					}
				}
				// 13. //
				$lp = '(('.$sClientName.'(((('.$sActivityName.'))))))';
				$result = $this->getLoadedActivity($lp);
				if(!is_null($result))
				{
					// caches lookup path for future searches
					$this->cacheActivityLookupPath($originalLp, $lp);
					return $result;
				}
				if($this->loadConfig(null, $sClientName, null, null, null, $sActivityName))
				{
					$result = $this->getLoadedActivity($lp);
					if(!is_null($result))
					{
						// caches lookup path for future searches
						$this->cacheActivityLookupPath($originalLp, $lp);
						return $result;
					}
				}
			}
		}

		if($sActivityName != ''){
			// 15. //
			$lp = '(((((('.$sActivityName.'))))))';
			$result = $this->getLoadedActivity($lp);
			if(!is_null($result))
			{
				// caches lookup path for future searches
				$this->cacheActivityLookupPath($originalLp, $lp);
				return $result;
			}
			if($this->loadConfig(null, null, null, null, null, $sActivityName))
			{
				$result = $this->getLoadedActivity($lp);
				if(!is_null($result))
				{
					// caches lookup path for future searches
					$this->cacheActivityLookupPath($originalLp, $lp);
					return $result;
				}
			}
		}

		$this->debugLogger()->write("activity not found");
		if(isset($undefinedActivityLp)) {
			$this->markActivityAsUndefined($undefinedActivityLp, $sActivityName);
			if($this->isSharedCacheEnabled() && isset($topestUndefinedActivityLp)) {
				$this->markActivityAsUndefined($topestUndefinedActivityLp, $sActivityName);
			}
		}
		return null;
	}

	protected function getLoadedField($lookupPath, $asXml=false)
	{
		if(!isset($this->fields)) return null;
		$this->debugLogger()->write("looks up for fields in ".$lookupPath);
		$returnValue = $this->fields[$lookupPath];
		if(!isset($returnValue)) return null;
		$this->debugLogger()->write('found fields');
		if($asXml) return $returnValue[0];
		else return $returnValue[1];
	}

	protected function getLoadedActivity($lookupPath)
	{
		if(!isset($this->activities)) return null;
		$this->debugLogger()->write("looks up for activity in ".$lookupPath);
		$returnValue = $this->activities[$lookupPath];
		if(!isset($returnValue)) return null;
		$this->debugLogger()->write('found Activity');
		return $returnValue;
	}

	protected function getLoadedTemplate($lookupPath)
	{
		if(!isset($this->template)) return null;
		$this->debugLogger()->write("looks up for Template in ".$lookupPath);
		$returnValue = $this->template[$lookupPath];
		if(!isset($returnValue)) return null;
		$this->debugLogger()->write('found Templates');
		return $returnValue;
	}

	protected function getLoadedDataType($dataTypeName)
	{
		if(!isset($this->dataTypes)) return null;
		$this->debugLogger()->write("looks up for DataType ".$dataTypeName);
		$returnValue = $this->dataTypes[$dataTypeName];
		if(!isset($returnValue)) return null;
		$this->debugLogger()->write('found DataType');
		return $returnValue;
	}

	private function getTemplateCachedLookupPath($lookupPath)
	{
		if(!isset($this->templateLookupCache)) return null;
		return $this->templateLookupCache[$lookupPath];
	}

	private function cacheTemplateLookupPath($lookupPath, $successfullLookupPath)
	{
		$this->templateLookupCache[$lookupPath] = $successfullLookupPath;
	}

	private function getActivityCachedLookupPath($lookupPath)
	{
		if(!isset($this->activityLookupCache) && $this->isSessionCacheEnabled() && $this->isSharedCacheEnabled()) {
			$this->activityLookupCache = $this->getSessionAdminService()->getData($this, 'activityLookupCache');
		}
		if(!isset($this->activityLookupCache)) return null;
		return $this->activityLookupCache[$lookupPath];
	}

	private function cacheActivityLookupPath($lookupPath, $successfullLookupPath)
	{
		$this->debugLogger()->write("lookupActivity cacheActivityLookupPath for $lookupPath with $successfullLookupPath");
		$this->activityLookupCache[$lookupPath] = $successfullLookupPath;
		// stores activity lookup cacke in navigation session
		if($this->isSessionCacheEnabled() && $this->isSharedCacheEnabled()) {
			$this->getSessionAdminService()->storeData($this, 'activityLookupCache', $this->activityLookupCache, true);
		}
	}

	private function getFieldCachedLookupPath($lookupPath)
	{
		if(!isset($this->fieldsLookupCache) && $this->isSessionCacheEnabled() && $this->isSharedCacheEnabled()) {
			$this->fieldsLookupCache = $this->getSessionAdminService()->getData($this, 'fieldsLookupCache');
		}
		if(!isset($this->fieldsLookupCache)) return null;
		return $this->fieldsLookupCache[$lookupPath];
	}

	private function cacheFieldLookupPath($lookupPath, $successfullLookupPath)
	{
		$this->debugLogger()->write("lookupFields cacheFieldLookupPath for $lookupPath with $successfullLookupPath");
		$this->fieldsLookupCache[$lookupPath] = $successfullLookupPath;
		// stores field lookup cacke in navigation session
		if($this->isSessionCacheEnabled() && $this->isSharedCacheEnabled()) {
			$this->getSessionAdminService()->storeData($this, 'fieldsLookupCache', $this->fieldsLookupCache, true);
		}
	}

	/**
	 * 1. first looks for (module(client(wigiiNamespace(group(user)))))
	 * 2. else  looks for (module(client(wigiiNamespace(group()))))
	 * 2.1 else  looks for (module(client((group()))))
	 * 3. else  looks for (module(client(wigiiNamespace((user)))))
	 * 4. else  looks for (module(client(wigiiNamespace(()))))
	 * 5. else  looks for (module(client((()))))
	 * 6. else  looks for (module(((()))))
	 * 6.1. else  looks for ((client(wigiiNamespace(()))))
	 * 6.2. else  looks for ((client((()))))
	 * 7. else  looks for ((((()))))
	 */
	private function lookupXml($moduleName, $clientName, $wigiiNamespaceName, $groupName, $username)
	{
		if(!isset($this->xml)) return null;

		$sGroupName = (is_null($groupName)?'':$groupName);
		$sModuleName = (is_null($moduleName) ? '':$moduleName);
		$sClientName = (is_null($clientName) ? '':$clientName);
		$sWigiiNamespaceName = (is_null($wigiiNamespaceName) ? '':$wigiiNamespaceName);
		$sUsername = (is_null($username) ? '':$username);

		// first looks in lookup cache
		$originalLp = '('.$sModuleName.'('.$sClientName.'('.$sWigiiNamespaceName.'('.$sGroupName.'('.$sUsername.')))))';
		$this->debugLogger()->write("lookupXml for originalLp:".$originalLp);
		$lp = $this->getXmlCachedLookupPath($originalLp);
		if(!is_null($lp))
		{
			$this->debugLogger()->write("lookupXml found lp:".$lp);
			$result = $this->getLoadedXml($lp);
			// in case xml is not yet loaded, loads from shared cache
			if(is_null($result) && $this->isSharedCacheEnabled()) {
				$lpArr = $this->splitLp($lp);
				if($this->loadConfig($lpArr['moduleName'], $lpArr['clientName'], $lpArr['wigiiNamespaceName'], $lpArr['groupName'], $lpArr['username'], $lpArr['activityName'])) {
					$result = $this->getLoadedXml($lp);
				}
			}
			if(!is_null($result)){
				$this->debugLogger()->write("lookupXml found result for lp".$lp);
				return $result;
			}
		}

		// else looks up in map
		if($sModuleName != '')
		{
			if($sClientName != '')
			{
				/* empty wigiiNamespace is permitted
				 if($sWigiiNamespaceName != '')
				 { */

				if($sGroupName != '')
				{
					if($sUsername != '')
					{
						// 1. //
						$lp = '('.$sModuleName.'('.$sClientName.'('.$sWigiiNamespaceName.'('.$sGroupName.'('.$sUsername.')))))';
						$result = $this->getLoadedXml($lp);
						if(!is_null($result))
						{
							// caches lookup path for future searches
							$this->cacheXmlLookupPath($originalLp, $lp);
							return $result;
						}
						if($this->loadConfig($sModuleName, $sClientName, $sWigiiNamespaceName, $sGroupName, $sUsername, null))
						{
							$result = $this->getLoadedXml($lp);
							if(!is_null($result))
							{
								// caches lookup path for future searches
								$this->cacheXmlLookupPath($originalLp, $lp);
								return $result;
							}
						}
					}

					// 2. //
					if($sWigiiNamespaceName != ''){
						$lp = '('.$sModuleName.'('.$sClientName.'('.$sWigiiNamespaceName.'('.$sGroupName.'()))))';
						$result = $this->getLoadedXml($lp);
						if(!is_null($result))
						{
							// caches lookup path for future searches
							$this->cacheXmlLookupPath($originalLp, $lp);
							return $result;
						}
						if($this->loadConfig($sModuleName, $sClientName, $sWigiiNamespaceName, $sGroupName, null, null))
						{
							$result = $this->getLoadedXml($lp);
							if(!is_null($result))
							{
								// caches lookup path for future searches
								$this->cacheXmlLookupPath($originalLp, $lp);
								return $result;
							}
						}
					}
					// 2.1 //
					$lp = '('.$sModuleName.'('.$sClientName.'(('.$sGroupName.'()))))';
					$result = $this->getLoadedXml($lp);
					if(!is_null($result))
					{
						// caches lookup path for future searches
						$this->cacheXmlLookupPath($originalLp, $lp);
						return $result;
					}
					if($this->loadConfig($sModuleName, $sClientName, null, $sGroupName, null, null))
					{
						$result = $this->getLoadedXml($lp);
						if(!is_null($result))
						{
							// caches lookup path for future searches
							$this->cacheXmlLookupPath($originalLp, $lp);
							return $result;
						}
					}
				}

				if($sUsername != '')
				{
					$lp = '('.$sModuleName.'('.$sClientName.'('.$sWigiiNamespaceName.'(('.$sUsername.')))))';
					$result = $this->getLoadedXml($lp);
					if(!is_null($result))
					{
						// caches lookup path for future searches
						$this->cacheXmlLookupPath($originalLp, $lp);
						return $result;
					}
					if($this->loadConfig($sModuleName, $sClientName, $sWigiiNamespaceName, null, $sUsername, null))
					{
						$result = $this->getLoadedXml($lp);
						if(!is_null($result))
						{
							// caches lookup path for future searches
							$this->cacheXmlLookupPath($originalLp, $lp);
							return $result;
						}
					}
				}

				$lp = '('.$sModuleName.'('.$sClientName.'('.$sWigiiNamespaceName.'(()))))';
				$result = $this->getLoadedXml($lp);
				if(!is_null($result))
				{
					// caches lookup path for future searches
					$this->cacheXmlLookupPath($originalLp, $lp);
					return $result;
				}
				if($this->loadConfig($sModuleName, $sClientName, $sWigiiNamespaceName, null, null, null))
				{
					$result = $this->getLoadedXml($lp);
					if(!is_null($result))
					{
						// caches lookup path for future searches
						$this->cacheXmlLookupPath($originalLp, $lp);
						return $result;
					}
				}
				/* } empty wigiiNamespace permitted */

				// only if wigiiNamespace is not empty, because else with have already done it before
				// because of allowed empty wigiiNamespaces
				if($sWigiiNamespaceName != '')
				{
					$lp = '('.$sModuleName.'('.$sClientName.'((()))))';
					$result = $this->getLoadedXml($lp);
					if(!is_null($result))
					{
						// caches lookup path for future searches
						$this->cacheXmlLookupPath($originalLp, $lp);
						return $result;
					}
					if($this->loadConfig($sModuleName, $sClientName, null, null, null, null))
					{
						$result = $this->getLoadedXml($lp);
						if(!is_null($result))
						{
							// caches lookup path for future searches
							$this->cacheXmlLookupPath($originalLp, $lp);
							return $result;
						}
					}
				}
			}

			$lp = '('.$sModuleName.'(((()))))';
			$result = $this->getLoadedXml($lp);
			if(!is_null($result))
			{
				// caches lookup path for future searches
				$this->cacheXmlLookupPath($originalLp, $lp);
				return $result;
			}
			if($this->loadConfig($sModuleName, null, null, null, null, null))
			{
				$result = $this->getLoadedXml($lp);
				if(!is_null($result))
				{
					// caches lookup path for future searches
					$this->cacheXmlLookupPath($originalLp, $lp);
					return $result;
				}
			}
		} else {
			//if no module, but client and wigiiNamespace define
			if($sWigiiNamespaceName != ''){
				$lp = '(('.$sClientName.'('.$sWigiiNamespaceName.'(()))))';
				$result = $this->getLoadedXml($lp);
				if(!is_null($result))
				{
					// caches lookup path for future searches
					$this->cacheXmlLookupPath($originalLp, $lp);
					return $result;
				}
				if($this->loadConfig(null, $sClientName, $sWigiiNamespaceName, null, null, null))
				{
					$result = $this->getLoadedXml($lp);
					if(!is_null($result))
					{
						// caches lookup path for future searches
						$this->cacheXmlLookupPath($originalLp, $lp);
						return $result;
					}
				}
			}
			//if no module, but client define
			$lp = '(('.$sClientName.'((()))))';
			$result = $this->getLoadedXml($lp);
			if(!is_null($result))
			{
				// caches lookup path for future searches
				$this->cacheXmlLookupPath($originalLp, $lp);
				return $result;
			}
			if($this->loadConfig(null, $sClientName, null, null, null, null))
			{
				$result = $this->getLoadedXml($lp);
				if(!is_null($result))
				{
					// caches lookup path for future searches
					$this->cacheXmlLookupPath($originalLp, $lp);
					return $result;
				}
			}

		}

		$lp = '((((()))))';
		$result = $this->getLoadedXml($lp);
		if(!is_null($result))
		{
			// caches lookup path for future searches
			$this->cacheXmlLookupPath($originalLp, $lp);
			return $result;
		}
		if($this->loadConfig(null, null, null, null, null, null))
		{
			$result = $this->getLoadedXml($lp);
			if(!is_null($result))
			{
				// caches lookup path for future searches
				$this->cacheXmlLookupPath($originalLp, $lp);
				return $result;
			}
		}

		$this->debugLogger()->write("xml not found");
		return null;
	}

	/**
	 * looks for __datatype__(datatype)
	 */
	private function lookupDatatypeXml($datatype)
	{
		if(!isset($this->xml)) return null;

		// first looks in lookup cache
		$originalLp = '__datatype__('.$datatype.')';
		$lp = $this->getXmlCachedLookupPath($originalLp);
		if(!is_null($lp))
		{
			$result = $this->getLoadedXml($lp);
			if(!is_null($result)) return $result;
		}

		// else looks up in map
		if($datatype != '')
		{
			$lp = '__datatype__('.$datatype.')';
			$result = $this->getLoadedXml($lp);
			if(!is_null($result))
			{
				// caches lookup path for future searches
				$this->cacheXmlLookupPath($originalLp, $lp);
				return $result;
			}
			if($this->loadDatatypeConfig($datatype))
			{
				$result = $this->getLoadedXml($lp);
				if(!is_null($result))
				{
					// caches lookup path for future searches
					$this->cacheXmlLookupPath($originalLp, $lp);
					return $result;
				}
			}
		}

		$this->debugLogger()->write("xml not found");
		return null;
	}
	private function lookupDataType($datatypeName)
	{
		if(!isset($this->dataTypes)) return null;

		if($datatypeName != '')
		{
			$result = $this->getLoadedDataType($datatypeName);
			if(!is_null($result))
			{
				return $result;
			}
			if($this->loadDatatypeConfig($datatypeName))
			{
				$result = $this->getLoadedDataType($datatypeName);
				if(!is_null($result))
				{
					return $result;
				}
			}
		}

		$this->debugLogger()->write("DataType not found");
		return null;
	}
	protected function getLoadedXml($lookupPath)
	{
		if(!isset($this->xml)) return null;
		$this->debugLogger()->write("looks up for xml in ".$lookupPath);
		$returnValue = $this->xml[$lookupPath];
		if(!isset($returnValue)) return null;
		$this->debugLogger()->write('found xml');
		return $returnValue;
	}	

	private function getXmlCachedLookupPath($lookupPath)
	{
		if(!isset($this->xmlLookupCache) && $this->isSessionCacheEnabled() && $this->isSharedCacheEnabled()) {
			$this->xmlLookupCache = $this->getSessionAdminService()->getData($this, 'xmlLookupCache');
		}
		if(!isset($this->xmlLookupCache)) return null;
		return $this->xmlLookupCache[$lookupPath];
	}

	private function cacheXmlLookupPath($lookupPath, $successfullLookupPath)
	{
		$this->debugLogger()->write("lookupXml cacheXmlLookupPath for $lookupPath with $successfullLookupPath");
		$this->xmlLookupCache[$lookupPath] = $successfullLookupPath;
		// stores field lookup cacke in navigation session
		if($this->isSessionCacheEnabled() && $this->isSharedCacheEnabled()) {
			$this->getSessionAdminService()->storeData($this, 'xmlLookupCache', $this->xmlLookupCache, true);
		}
	}

	// Configuration loading

	/**
	 * @return Boolean returns true if something has been loaded, else false.
	 */
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
		$returnValue = false;
		//$this->debugLogger()->write("loadConfig for lpParamAndXml:".$lpParamAndXml." lpField:".$lpField);
			
		// checks that xml is not already loaded
		$xml = $this->getLoadedXml($lpParamAndXml);
		if(isset($xml)) {
			return $this->loadAllUnset($lpParamAndXml, $lpField, $xml);
		}
		elseif(isset($this->loadedXmlPaths) && isset($this->loadedXmlPaths[$lpParamAndXml])) return false;
		else {
			if(!isset($this->loadedXmlPaths)) $this->loadedXmlPaths = array();
			$this->loadedXmlPaths[$lpParamAndXml] = time();
		}
						
		// else tries to load from cached xml string
		if($this->loadFromXmlStringCache($lpParamAndXml, $lpField)) return true;

		// then loads from configuration files
		$filenames = $this->buildConfigFilePath($moduleName, $clientName, $wigiiNamespaceName, $groupName, $username, $activityName);
		if(!isset($this->loadedConfigFiles)){
			//first look in session
			if($this->isSessionCacheEnabled()) $this->loadedConfigFiles = $this->getSessionAdminService()->getData($this,"loadedConfigFiles");
			if(!isset($this->loadedConfigFiles)){
				//otherwise define empty array()
				$this->loadedConfigFiles = array();
			}
		}

		foreach($filenames as $filename)
		{
			// loads config file if not yet loaded
			//$this->debugLogger()->write("checks if ".$filename." is loaded");
			if(!isset($this->loadedConfigFiles[$filename]) || 
				$this->loadedConfigFiles[$filename] > 0)
			{
				// clears session data if file was read but no more present in cache
				// this occurs when ConfigService switches from shared db cache mode to session cache or vice-versa.
				if($this->loadedConfigFiles[$filename] > 0) $this->loadedConfigFiles = array();
				
				// load file only if exists on disk
				if($this->isConfigFileAvailable($filename))
				{
					$xmlConfig = $this->readConfigFile($filename);
					// if loading a namespace config file (wigiiNamespace_config.xml), then checks that it is not a module config file
					// --> the file should only contain parameters and activities, no <fields> section.
					if(empty($moduleName) && !empty($wigiiNamespaceName) && $xmlConfig->xpath('fields')) return false;
					$returnValue = true;
					// xml config contains an add section or a delete section then
					// - first loads parent dependency
					// - executes adds and delete section
					$xmlAdds = $xmlConfig->xpath('adds');
					$xmlDeletes = $xmlConfig->xpath('deletes');
					if($xmlAdds || $xmlDeletes)
					{
						// loads parent xml
						$parentlp = $this->loadParentConfig($moduleName, $clientName, $wigiiNamespaceName, $groupName, $username, $activityName);
						// gets parent xml
						$this->debugLogger()->write('looks up xml parent');
						$xmlConfig = $this->lookupXml($parentlp['moduleName'],$parentlp['clientName'],$parentlp['wigiiNamespaceName'],$parentlp['groupName'],$parentlp['username']);
						if(isset($xmlConfig)) {
							// clones parent xml
							$this->debugLogger()->write('clones xml parent');
							$xmlConfig = $this->cloneConfigXmlNode($xmlConfig);
	
							// executes adds
							if($xmlAdds)
							{
								$this->addConfigElements($xmlAdds[0], $xmlConfig);
							}
							// executes deletes
							if($xmlDeletes)
							{
								$this->deleteConfigElements($xmlDeletes[0], $xmlConfig);
							}
							// loads updated configuration
							$this->loadAll($lpParamAndXml, $lpField, $xmlConfig);
						}
					}
					// else loads configuration
					else
					{
						$this->loadAll($lpParamAndXml, $lpField, $xmlConfig);
					}
					// marks file as loaded by registering timestamp.
					$this->loadedConfigFiles[$filename] = time();
				}
				else
				{
					// if file does not exist, still mark it as loaded not to search for file again
					$this->loadedConfigFiles[$filename] = 0;
					$this->debugLogger()->write('marks '.$filename.' as missing');
				}
			}
		}

		if($this->isSessionCacheEnabled() && !$this->isReadOnlyXmlFromSession()) $this->getSessionAdminService()->storeData($this,"loadedConfigFiles", $this->loadedConfigFiles, $this->isSharedCacheEnabled());
		//$this->debugLogger()->logEndOperation("loadConfig");
		return $returnValue;
	}

	/**
	 * Dumps the given XML into a file located in dumpPath
	 * @param SimpleXMLElement $xml xml configuration to dump into a file
	 * @param WigiiBPLParameter $info contextual information :
	 * - lpField: Configuration Lookup Path
	 * - loadAllUnset: boolean. If true, means that XML was already into memory, but some parts where not mapped to objects (this occurs when loading Activities or Fields after loading Parameters).
	 * If false, then it means that it is a fresh loading of the XML file from disk.
	 * - loadCoreConfig: boolean. If true, means that XML was loaded from Core ConfigService before beeing analyzed
	 * - principal: Principal. Current principal if known.
	 */
	protected function dumpConfig($xml,$info) {
		$this->debugLogger()->logBeginOperation('dumpConfig');
		if(!isset($xml)) return;
		if(!isset($info)) $info=wigiiBPLParam();
		$p = $info->getValue('principal');
		if(!isset($p)) $p=$this->getAuthenticationService()->getMainPrincipal();
		$exec = ServiceProvider::getExecutionService();
	
		// dumps only fresh files
		if($info->getValue('loadAllUnset')) {
			$this->debugLogger()->write('xml already into memory, not dumped');
			$this->debugLogger()->logEndOperation('dumpConfig');
			return;
		}
	
		// prepares contextual info
		$contextInfo="<!-- ConfigService XML dump ".date('Y-m-d h:i:s')."\n";
		$contextInfo.='request: '.$exec->getCrtRequest()."\n";
		$contextInfo.='wigiiNamespace: '.$exec->getCrtWigiiNamespace()->getWigiiNamespaceUrl()."\n";
		$contextInfo.='module: '.$exec->getCrtModule()->getModuleUrl()."\n";
		$contextInfo.='action: '.$exec->getCrtAction()."\n";
		$contextInfo.='principalNamespace: '.$p->getWigiiNamespace()->getWigiiNamespaceUrl()."\n";
		$contextInfo.='configLP: '.$info->getValue('lpField')."\n";
		$contextInfo.='alreadyInMemory: '.$info->getValue('loadAllUnset')."\n";
		$contextInfo.='core config: '.$info->getValue('loadCoreConfig')."\n";
		$contextInfo.="-->\n";
		$xml=$xml->asXML();
		if(!preg_match('/(\<\?xml.*?\?\>)/', $xml)) $xml=$contextInfo."\n".$xml;
		else $xml=preg_replace('/(\<\?xml.*?\?\>)/', '\\1'."\n".$contextInfo,$xml);
		// writes to file
		$fileName='';
		$s=$info->getValue('client');
		if($s) $fileName.=$s;
		$s=$info->getValue('wigiiNamespace');
		if($s) $fileName.=($fileName?'_':'').$s;
		$s=$info->getValue('module');
		if($s) $fileName.=($fileName?'_':'').$s;
		$s=$info->getValue('group');
		if($s) $fileName.=($fileName?'_':'').$s;
		$fileName.=($fileName?'_':'').udate('Ymdhisu');
		$fileName.='.xml';
	
		$filePath=$this->getDumpPath().$fileName;
		$f=@fopen($filePath,'w');
		if($f) {
			if(@fwrite($f,$xml)) {
				$this->debugLogger()->write('dumped xml');
				$info->setValue('xmlFileName', $fileName);
			}
			@fclose($f);
			$old = umask(0000);
			@chmod($filePath, 0666);
			umask($old);
		}
		else $this->debugLogger()->write('could not dump xml to '.$filePath);
		$this->debugLogger()->logEndOperation('dumpConfig');
	}
	
	/**
	 * returns true if something has been loaded, else false.
	 */
	protected function loadDatatypeConfig($datatype)
	{
		$lpXml = '__datatype__('.$datatype.')';
		$returnValue = false;

		// first tries to load from cached xml string
		if($this->loadFromXmlDatatypeStringCache($lpXml)) return true;

		// then loads from configuration files
		$filenames = $this->buildDatatypeConfigFilePath($datatype);
		if(!isset($this->loadedConfigFiles)){
			//first look in session
			if($this->isSessionCacheEnabled()) $this->loadedConfigFiles = $this->getSessionAdminService()->getData($this,"loadedConfigFiles");
			if(!isset($this->loadedConfigFiles)){
				//otherwise define empty array()
				$this->loadedConfigFiles = array();
			}
		}
		foreach($filenames as $filename)
		{
			// loads config file if not yet loaded
			//$this->debugLogger()->write("checks if ".$filename." is loaded");
			if(!isset($this->loadedConfigFiles[$filename]))
			{
				// load file only if exists on disk
				if($this->isConfigFileAvailable($filename))
				{
					$xmlConfig = $this->readConfigFile($filename);
					$returnValue = true;

					$this->loadDatatype($lpXml, $xmlConfig);

					// marks file as loaded by registering timestamp.
					$this->loadedConfigFiles[$filename] = time();
				}
				else
				{
					// if file does not exist, still mark it as loaded not to search for file again
					$this->loadedConfigFiles[$filename] = 0;
					$this->debugLogger()->write('marks '.$filename.' as missing');
				}
			}
		}

		if($this->isSessionCacheEnabled() && !$this->isReadOnlyXmlFromSession()) $this->getSessionAdminService()->storeData($this,"loadedConfigFiles", $this->loadedConfigFiles);
		return $returnValue;
	}

	private function addConfigElements($xmlAdds, $xmlConfig)
	{
		$this->debugLogger()->logBeginOperation('addConfigElements');
		if(!isset($xmlAdds)) return;
		if(!isset($xmlConfig)) return;
		
		$xml=$xmlConfig;
		foreach($xmlAdds->item as $i=>$item){		
			if((string)$item["createNode"]!=null){
				eval('simplexml_addChild($xml->'.$item["parent"].',"'.$item["createNode"].'", "'.$item["value"].'");');
			} elseif ((string)$item["createAtt"]!=null){
				eval('simplexml_addAttribute($xml->'.$item["parent"].',"'.$item["createAtt"].'", "'.$item["value"].'");');
			} elseif ((string)$item["addNodesTo"]!=null){
				foreach($item->children() as $j=>$subItem){
					if ((string)$item["beforeNode"] != null) $beforeNode = '$xml->'.$item["beforeNode"].'';
					else $beforeNode = "null";
					if ((string)$item["addNodesTo"] == "#root#") $addNodesTo = '$xml';
					else $addNodesTo = '$xml->'.$item["addNodesTo"];
					eval('simplexml_appendChild('.$addNodesTo.', $subItem, '.$beforeNode.'); ');
				}
			} else {
				eval('return $xml->'.$item["node"].' = "'.$item["value"].'";');
			}
		}		
		$this->debugLogger()->logEndOperation('addConfigElements');
	}
	private function deleteConfigElements($xmlDeletes, $xmlConfig)
	{
		$this->debugLogger()->logBeginOperation('deleteConfigElements');
		if(!isset($xmlDeletes)) return;
		if(!isset($xmlConfig)) return;

		$xml=$xmlConfig;
		foreach($xmlDeletes->item as $i=>$item){			
			eval('simplexml_removeNode($xml->'.$item["node"].');');
		}		
		$this->debugLogger()->logEndOperation('deleteConfigElements');
	}	

	/**
	 * Loads parent config recursively following this path :
	 *   (module(client(wigiiNamespace(group(user(activity))))))
	 * 	     (module(client(wigiiNamespace(group((activity))))))
	 *           (module(client(wigiiNamespace((user(activity))))))
	 *               (module(client(wigiiNamespace(((activity))))))
	 *                  (module(client((((activity))))))
	 *                      (module(((((activity))))))
	 *
	 * returns lookup path arguments to parent xml config as an array of strings [moduleName, clientName, wigiiNamespaceName, groupName, username]
	 */
	private function loadParentConfig($moduleName, $clientName, $wigiiNamespaceName, $groupName, $username, $activityName)
	{
		$sModuleName = (is_null($moduleName) || $moduleName == '' ? '' : $moduleName);
		$sClientName = (is_null($clientName) || $clientName == '' ? '' : $clientName);
		$sWigiiNamespaceName = (is_null($wigiiNamespaceName) || $wigiiNamespaceName == '' ? '' : $wigiiNamespaceName);
		$sUsername = (is_null($username) || $username == '' ? '' : $username);
		$sGroupName = (is_null($groupName) || $groupName == '' ? '' : $groupName);
		$sActivityName = (is_null($activityName) || $activityName == '' ? '' : $activityName);

		// warning: empty wigiiNamespace is allowed and valid.
		//(module(client(wigiiNamespace(group(user(activity)))))) -> (module(client(wigiiNamespace(group((activity))))))
		if($sModuleName != '' && $sClientName != '' && $sGroupName != '' && $sUsername != '')
		{
			$this->debugLogger()->write("loads parent configuration");
			$this->loadConfig($moduleName, $clientName, $wigiiNamespaceName, $groupName, null, $activityName);
			// lookup path to xml parent ignoring activity
			return array('moduleName'=>$sModuleName,'clientName'=>$sClientName,'wigiiNamespaceName'=>$sWigiiNamespaceName,'groupName'=>$sGroupName,'username'=>null);
		}
		//(module(client(wigiiNamespace(group((activity)))))) -> (module(client(wigiiNamespace(((activity))))))
		elseif($sModuleName != '' && $sClientName != '' && $sGroupName != '' && $sUsername == '')
		{
			$this->debugLogger()->write("loads parent configuration");
			$this->loadConfig($moduleName, $clientName, $wigiiNamespaceName, null, null, $activityName);
			// lookup path to xml parent ignoring activity
			return array('moduleName'=>$sModuleName,'clientName'=>$sClientName,'wigiiNamespaceName'=>$sWigiiNamespaceName,'groupName'=>null,'username'=>null);
		}
		//(module(client(wigiiNamespace((user(activity)))))) -> (module(client(wigiiNamespace(((activity))))))
		elseif($sModuleName != '' && $sClientName != '' && $sGroupName == '' && $sUsername != '')
		{
			$this->debugLogger()->write("loads parent configuration");
			$this->loadConfig($moduleName, $clientName, $wigiiNamespaceName, null, null, $activityName);
			// lookup path to xml parent ignoring activity
			return array('moduleName'=>$sModuleName,'clientName'=>$sClientName,'wigiiNamespaceName'=>$sWigiiNamespaceName,'groupName'=>null,'username'=>null);
		}
		//(module(client(wigiiNamespace(((activity)))))) -> (module(client((((activity))))))
		elseif($sModuleName != '' && $sClientName != '' && $sWigiiNamespaceName != '' && $sGroupName == '' && $sUsername == '')
		{
			$this->debugLogger()->write("loads parent configuration");
			$this->loadConfig($moduleName, $clientName, null, null, null, $activityName);
			// lookup path to xml parent ignoring activity
			return array('moduleName'=>$sModuleName,'clientName'=>$sClientName,'wigiiNamespaceName'=>null,'groupName'=>null,'username'=>null);
		}
		//(module(client((((activity)))))) -> (module(((((activity))))))
		elseif($sModuleName != '' && $sClientName != '' && $sWigiiNamespaceName == '' && $sGroupName == '' && $sUsername == '')
		{
			$this->debugLogger()->write("loads parent configuration");
			$this->loadConfig($moduleName, null, null, null, null, $activityName);
			// lookup path to xml parent ignoring activity
			return array('moduleName'=>$sModuleName,'clientName'=>null,'wigiiNamespaceName'=>null,'groupName'=>null,'username'=>null);
		}
		//(module(((((activity)))))) -> ((((((activity))))))
		elseif($sModuleName != '' && $sClientName == '' && $sWigiiNamespaceName == '' && $sGroupName == '' && $sUsername == '')
		{
			$this->debugLogger()->write("loads parent configuration");
			$this->loadConfig(null, null, null, null, null, $activityName);
			// lookup path to xml parent ignoring activity
			return array('moduleName'=>null,'clientName'=>null,'wigiiNamespaceName'=>null,'groupName'=>null,'username'=>null);
		}
		return null;
	}


	// XML string cache management

	/**
	 * returns true if something has been loaded from cache, else false
	 */
	private function loadFromXmlDatatypeStringCache($xmlLookupPath)
	{
		$xmlString = $this->getCachedDataTypeXmlString($xmlLookupPath);
		if(!isset($xmlString)) return false;
		$xmlConfig = $this->readConfigXmlNodeFromString($xmlString);
		$this->loadDatatype($xmlLookupPath, $xmlConfig, false);
		return true;
	}
	private function getCachedDataTypeXmlString($lookupPath)
	{
		// first looks into session
		if(!isset($this->dataTypeXmlString)) {
			if($this->isSessionCacheEnabled()) $this->dataTypeXmlString = $this->getSessionAdminService()->getData($this,"dataTypeXmlString");
			if(!isset($this->dataTypeXmlString)) $this->dataTypeXmlString = array();
		}
		// else looks into shared cache if enabled
		if($this->isSessionCacheEnabled() && $this->isSharedCacheEnabled() && !isset($this->dataTypeXmlString[$lookupPath])) {
			$this->dataTypeXmlString[$lookupPath] = $this->getXmlStringFromSharedCache($lookupPath);
			if(isset($this->dataTypeXmlString[$lookupPath])) $this->getSessionAdminService()->storeData($this,"dataTypeXmlString", $this->dataTypeXmlString, false);
		}
		$this->debugLogger()->write("looks up for cached dataType xml string in ".$lookupPath);
		$returnValue = $this->dataTypeXmlString[$lookupPath];
		if(!isset($returnValue)) return null;
		$this->debugLogger()->write('found dataType xml string');
		return $returnValue;
	}
	private function cacheDataTypeXmlString($lookupPath, $xmlConfigString)
	{
		$this->debugLogger()->write("cacheDataTypeXmlString for $lookupPath with $xmlConfigString");
		$this->dataTypeXmlString[$lookupPath] = $xmlConfigString;
		if($this->isSessionCacheEnabled() && !$this->isReadOnlyXmlFromSession()) {
			// stores xml into shared cache if enabled
			if($this->isSharedCacheEnabled()) {
				$this->storeXmlStringInSharedCache($lookupPath, $xmlConfigString);
			}
			// stores into the session
			$this->getSessionAdminService()->storeData($this,"dataTypeXmlString", $this->dataTypeXmlString, false);
		}
		$this->debugLogger()->write('caches dataType xml config string in '.$lookupPath);
	}
	/**
	 * returns true if something has been loaded from cache, else false
	 */
	private function loadFromXmlStringCache($xmlLookupPath, $fieldsLookupPath)
	{
		$xmlString = $this->getCachedXmlString($xmlLookupPath);
		if(!isset($xmlString)) return false;
		$xmlConfig = $this->readConfigXmlNodeFromString($xmlString);
		return $this->loadAllUnset($xmlLookupPath, $fieldsLookupPath, $xmlConfig);
	}	
	private function getCachedXmlString($lookupPath)
	{
		// first looks into session
		if(!isset($this->xmlstring)) {
			if($this->isSessionCacheEnabled()) $this->xmlstring = $this->getSessionAdminService()->getData($this,"xmlstring");
			if(!isset($this->xmlstring)) $this->xmlstring = array();
		}
		// else looks into shared cache if enabled
		if($this->isSessionCacheEnabled() && $this->isSharedCacheEnabled() && !isset($this->xmlstring[$lookupPath])) {
			$this->xmlstring[$lookupPath] = $this->getXmlStringFromSharedCache($lookupPath);
			if(isset($this->xmlstring[$lookupPath])) $this->getSessionAdminService()->storeData($this,"xmlstring", $this->xmlstring, true);
		}
		$this->debugLogger()->write("looks up for cached xml string in ".$lookupPath);
		$returnValue = $this->xmlstring[$lookupPath];
		if(!isset($returnValue)) return null;
		$this->debugLogger()->write('found xml string');
		return $returnValue;
	}
	private function cacheXmlString($lookupPath, $xmlConfigString)
	{
		$this->debugLogger()->write("cacheXmlString for $lookupPath with $xmlConfigString");
		$this->xmlstring[$lookupPath] = $xmlConfigString;
		if($this->isSessionCacheEnabled() && !$this->isReadOnlyXmlFromSession()) {			
			// stores xml into shared cache if enabled
			if($this->isSharedCacheEnabled()) {
				$this->storeXmlStringInSharedCache($lookupPath, $xmlConfigString);
			}
			// stores into the session
			$this->getSessionAdminService()->storeData($this,"xmlstring", $this->xmlstring, $this->isSharedCacheEnabled());
		}
		$this->debugLogger()->write('caches xml config string in '.$lookupPath);
	}


	// Undefined parameters cache management
	
	/**
	 * Returns true if the given parameter is marked as undefined into the cache.
	 * Returns false if the given parameter is not marked as undefined, 
	 * that means that either the parameter is defined or that the cache does not know yet its status
	 * @param String $lookupPath lookup path to the parameter
	 * @param String $name parameter name
	 */
	protected function isParameterUndefined($lookupPath, $name) {
		if(!isset($this->undefinedParametersCache)) {
			if($this->isSessionCacheEnabled()) $this->undefinedParametersCache = $this->getSessionAdminService()->getData($this, "undefinedParameters");
			if(!is_array($this->undefinedParametersCache)) $this->undefinedParametersCache = array();
		}
				
		$this->debugLogger()->write("looks if parameter '$name' is undefined for lookup path $lookupPath");	
		return isset($this->undefinedParametersCache['('.$name.$lookupPath.')']);
	}
	/**
	 * Marks a parameter as beeing undefined for the given lookup path
	 * @param String $lookupPath lookup path to the parameter
	 * @param String $name parameter name
	 */
	protected function markParameterAsUndefined($lookupPath, $name) {
		if(!isset($this->undefinedParametersCache)) {
			if($this->isSessionCacheEnabled()) $this->undefinedParametersCache = $this->getSessionAdminService()->getData($this, "undefinedParameters");
			if(!is_array($this->undefinedParametersCache)) $this->undefinedParametersCache = array();
		}
				
		$this->undefinedParametersCache['('.$name.$lookupPath.')'] = true;
		$this->debugLogger()->write("marks parameter '$name' as undefined for $lookupPath");
				
		if($this->isSessionCacheEnabled() && !$this->isReadOnlyParametersFromSession()) {
			$this->getSessionAdminService()->storeData($this,"undefinedParameters", $this->undefinedParametersCache, $this->isSharedCacheEnabled());
		}
	}
	
	// Parameters session cache
	
	/**
	 * Gets the value of a parameter from the session cache.
	 * Looks for :
	 * 1. first looks for (module(client(wigiiNamespace(group(user)))))[name]
	 * 2. else  looks for (module(client(wigiiNamespace(group()))))[name]
	 * 2.1 else  looks for (module(client((group()))))[name]
	 * 3. else  looks for (module(client(wigiiNamespace((user)))))[name]
	 * 4. else  looks for (module(client(wigiiNamespace(()))))[name]
	 * 5. else  looks for (module(client((()))))[name]
	 * 6. else  looks for (module(((()))))[name]
	 * 6.1. else  looks for ((client(wigiiNamespace(()))))[name]
	 * 6.2. else  looks for ((client((()))))[name]
	 * 7. else  looks for ((((()))))[name]
	 */
	protected function getParameterFromSessionCache($name, $sModuleName, $sClientName, $sWigiiNamespaceName, $sGroupName, $sUsername) {
		$this->debugLogger()->logBeginOperation("getParameterFromSessionCache");
		if(!$this->loadOnlyParameters) {
			$this->loadOnlyParameters = true;
			$shouldUnsetFlag = true;
		}
		else $shouldUnsetFlag = false;
		
		$returnValue = null;
		try {
			$returnValue = $this->doGetParameterFromSessionCache($name, $sModuleName, $sClientName, $sWigiiNamespaceName, $sGroupName, $sUsername);
		}
		catch(Exception $e) {
			if($shouldUnsetFlag) $this->loadOnlyParameters = false;
			throw $e;
		}
		if($shouldUnsetFlag) $this->loadOnlyParameters = false;
		$this->debugLogger()->logEndOperation("getParameterFromSessionCache");
		return $returnValue;
	}
	protected function doGetParameterFromSessionCache($name, $sModuleName, $sClientName, $sWigiiNamespaceName, $sGroupName, $sUsername) {
		if(!isset($this->parametersSessionCache)) {
			if($this->isSessionCacheEnabled()) $this->parametersSessionCache = $this->getSessionAdminService()->getData($this, "parametersSessionCache");
			if(!is_array($this->parametersSessionCache)) $this->parametersSessionCache = array();
		}
		
		// first looks in lookup cache
		$originalLp = '(session('.$name.'('.$sModuleName.'('.$sClientName.'('.$sWigiiNamespaceName.'('.$sGroupName.'('.$sUsername.')))))))';
		$this->debugLogger()->write("getParameterFromSessionCache for originalLp:".$originalLp);
		$lp = $this->getParameterCachedLookupPath($originalLp);
		if(!is_null($lp))
		{
			$this->debugLogger()->write("getParameterFromSessionCache found lp:".$lp);
			$result = $this->parametersSessionCache[$lp];
			if(!is_null($result)){
				$this->debugLogger()->write("getParameterFromSessionCache found result for lp".$lp." name:".$name);
				return $result;
			}
		}
		
		if($sModuleName != '')
		{
			if($sClientName != '')
			{
				/* empty wigiiNamespace is permitted
				 if($sWigiiNamespaceName != '')
				 {
				*/
				if($sGroupName != '')
				{
					if($sUsername != '')
					{
						// 1. //
						$lp = '('.$sModuleName.'('.$sClientName.'('.$sWigiiNamespaceName.'('.$sGroupName.'('.$sUsername.')))))';
						// looks up in shared cache if enabled
						if($this->isSharedCacheEnabled() && $this->isSessionCacheEnabled() && !isset($this->parametersSessionCache['('.$lp.')'])) {
							$this->loadParametersFromSharedCache($lp);
							if(isset($this->parametersSessionCache['('.$lp.')'])) $this->getSessionAdminService()->storeData($this, "parametersSessionCache", $this->parametersSessionCache, $this->isSharedCacheEnabled());
						}
						// if not found, then loads from file
						if(!isset($this->parametersSessionCache['('.$lp.')']) &&
							!$this->loadConfig($sModuleName, $sClientName, $sWigiiNamespaceName, $sGroupName, $sUsername, null)) {
							$this->cacheParametersInSession($lp, null);	
						}
						$lp = '('.$name.$lp.')';
						$result = $this->parametersSessionCache[$lp];
						if(!is_null($result)) {
							// caches lookup path for future searches
							$this->cacheParameterLookupPath($originalLp, $lp);
							return $result;
						}
					}
					// 2. //
					if($sWigiiNamespaceName != ''){
						$lp = '('.$sModuleName.'('.$sClientName.'('.$sWigiiNamespaceName.'('.$sGroupName.'()))))';
						// looks up in shared cache if enabled
						if($this->isSharedCacheEnabled() && $this->isSessionCacheEnabled() && !isset($this->parametersSessionCache['('.$lp.')'])) {
							$this->loadParametersFromSharedCache($lp);
							if(isset($this->parametersSessionCache['('.$lp.')'])) $this->getSessionAdminService()->storeData($this, "parametersSessionCache", $this->parametersSessionCache, $this->isSharedCacheEnabled());
						}
						// if not found, then loads from file
						if(!isset($this->parametersSessionCache['('.$lp.')']) &&
							!$this->loadConfig($sModuleName, $sClientName, $sWigiiNamespaceName, $sGroupName, null, null)) {
							$this->cacheParametersInSession($lp, null);
						}
						$lp = '('.$name.$lp.')';
						$result = $this->parametersSessionCache[$lp];
						if(!is_null($result)) {
							// caches lookup path for future searches
							$this->cacheParameterLookupPath($originalLp, $lp);
							return $result;
						}
					}
					// 2.1 //
					$lp = '('.$sModuleName.'('.$sClientName.'(('.$sGroupName.'()))))';
					// looks up in shared cache if enabled
					if($this->isSharedCacheEnabled() && $this->isSessionCacheEnabled() && !isset($this->parametersSessionCache['('.$lp.')'])) {
						$this->loadParametersFromSharedCache($lp);
						if(isset($this->parametersSessionCache['('.$lp.')'])) $this->getSessionAdminService()->storeData($this, "parametersSessionCache", $this->parametersSessionCache, $this->isSharedCacheEnabled());
					}
					// if not found, then loads from file
					if(!isset($this->parametersSessionCache['('.$lp.')']) &&
						!$this->loadConfig($sModuleName, $sClientName, null, $sGroupName, null, null)) {
						$this->cacheParametersInSession($lp, null);
					}
					$lp = '('.$name.$lp.')';
					$result = $this->parametersSessionCache[$lp];
					if(!is_null($result)) {
						// caches lookup path for future searches
						$this->cacheParameterLookupPath($originalLp, $lp);
						return $result;
					}
				}
				// 3. //
				if($sUsername != '')
				{
					$lp = '('.$sModuleName.'('.$sClientName.'('.$sWigiiNamespaceName.'(('.$sUsername.')))))';
					// looks up in shared cache if enabled
					if($this->isSharedCacheEnabled() && $this->isSessionCacheEnabled() && !isset($this->parametersSessionCache['('.$lp.')'])) {
						$this->loadParametersFromSharedCache($lp);
						if(isset($this->parametersSessionCache['('.$lp.')'])) $this->getSessionAdminService()->storeData($this, "parametersSessionCache", $this->parametersSessionCache, $this->isSharedCacheEnabled());
					}
					// if not found, then loads from file
					if(!isset($this->parametersSessionCache['('.$lp.')']) &&
						!$this->loadConfig($sModuleName, $sClientName, $sWigiiNamespaceName, null, $sUsername, null)) {
						$this->cacheParametersInSession($lp, null);
					}
					$lp = '('.$name.$lp.')';
					$result = $this->parametersSessionCache[$lp];
					if(!is_null($result)) {
						// caches lookup path for future searches
						$this->cacheParameterLookupPath($originalLp, $lp);
						return $result;
					}
				}								
				// 4. //
				$lp = '('.$sModuleName.'('.$sClientName.'('.$sWigiiNamespaceName.'(()))))';
				// looks up in shared cache if enabled
				if($this->isSharedCacheEnabled() && $this->isSessionCacheEnabled() && !isset($this->parametersSessionCache['('.$lp.')'])) {
					$this->loadParametersFromSharedCache($lp);
					if(isset($this->parametersSessionCache['('.$lp.')'])) $this->getSessionAdminService()->storeData($this, "parametersSessionCache", $this->parametersSessionCache, $this->isSharedCacheEnabled());
				}
				// if not found, then loads from file
				if(!isset($this->parametersSessionCache['('.$lp.')']) &&
					!$this->loadConfig($sModuleName, $sClientName, $sWigiiNamespaceName, null, null, null)) {
					$this->cacheParametersInSession($lp, null);
				}
				$lp = '('.$name.$lp.')';
				$result = $this->parametersSessionCache[$lp];
				if(!is_null($result)) {
					// caches lookup path for future searches
					$this->cacheParameterLookupPath($originalLp, $lp);
					return $result;
				}
				/*}*/

				// only if wigiiNamespace is not empty, because else with have already done it before
				// because of allowed empty wigiiNamespaces
				// 5. //
				if($sWigiiNamespaceName != '')
				{
					$lp = '('.$sModuleName.'('.$sClientName.'((()))))';
					// looks up in shared cache if enabled
					if($this->isSharedCacheEnabled() && $this->isSessionCacheEnabled() && !isset($this->parametersSessionCache['('.$lp.')'])) {
						$this->loadParametersFromSharedCache($lp);
						if(isset($this->parametersSessionCache['('.$lp.')'])) $this->getSessionAdminService()->storeData($this, "parametersSessionCache", $this->parametersSessionCache, $this->isSharedCacheEnabled());
					}
					// if not found, then loads from file
					if(!isset($this->parametersSessionCache['('.$lp.')']) &&
						!$this->loadConfig($sModuleName, $sClientName, null, null, null, null)) {
						$this->cacheParametersInSession($lp, null);
					}
					$lp = '('.$name.$lp.')';
					$result = $this->parametersSessionCache[$lp];
					if(!is_null($result)) {
						// caches lookup path for future searches
						$this->cacheParameterLookupPath($originalLp, $lp);
						return $result;
					}
				}
			}
				
			// 6. //
			$lp = '('.$sModuleName.'(((()))))';
			// looks up in shared cache if enabled
			if($this->isSharedCacheEnabled() && $this->isSessionCacheEnabled() && !isset($this->parametersSessionCache['('.$lp.')'])) {
				$this->loadParametersFromSharedCache($lp);
				if(isset($this->parametersSessionCache['('.$lp.')'])) $this->getSessionAdminService()->storeData($this, "parametersSessionCache", $this->parametersSessionCache, $this->isSharedCacheEnabled());
			}
			// if not found, then loads from file
			if(!isset($this->parametersSessionCache['('.$lp.')']) &&
				!$this->loadConfig($sModuleName, null, null, null, null, null)) {
				$this->cacheParametersInSession($lp, null);
			}
			$lp = '('.$name.$lp.')';
			$result = $this->parametersSessionCache[$lp];
			if(!is_null($result)) {
				// caches lookup path for future searches
				$this->cacheParameterLookupPath($originalLp, $lp);
				return $result;
			}
		}

		// 6.1 //
		if($sClientName != '' && $sWigiiNamespaceName != ''){
			$lp = '(('.$sClientName.'('.$sWigiiNamespaceName.'(()))))';
			// looks up in shared cache if enabled
			if($this->isSharedCacheEnabled() && $this->isSessionCacheEnabled() && !isset($this->parametersSessionCache['('.$lp.')'])) {
				$this->loadParametersFromSharedCache($lp);
				if(isset($this->parametersSessionCache['('.$lp.')'])) $this->getSessionAdminService()->storeData($this, "parametersSessionCache", $this->parametersSessionCache, $this->isSharedCacheEnabled());
			}
			// if not found, then loads from file
			if(!isset($this->parametersSessionCache['('.$lp.')']) &&
				!$this->loadConfig(null, $sClientName, $sWigiiNamespaceName, null, null, null)) {
				$this->cacheParametersInSession($lp, null);
			}
			$lp = '('.$name.$lp.')';
			$result = $this->parametersSessionCache[$lp];
			if(!is_null($result)) {
				// caches lookup path for future searches
				$this->cacheParameterLookupPath($originalLp, $lp);
				return $result;
			}
		}

		// 6.2 //
		if($sClientName != ''){
			$lp = '(('.$sClientName.'((()))))';
			// looks up in shared cache if enabled
			if($this->isSharedCacheEnabled() && $this->isSessionCacheEnabled() && !isset($this->parametersSessionCache['('.$lp.')'])) {
				$this->loadParametersFromSharedCache($lp);
				if(isset($this->parametersSessionCache['('.$lp.')'])) $this->getSessionAdminService()->storeData($this, "parametersSessionCache", $this->parametersSessionCache, $this->isSharedCacheEnabled());
			}
			// if not found, then loads from file
			if(!isset($this->parametersSessionCache['('.$lp.')']) &&
				!$this->loadConfig(null, $sClientName, null, null, null, null)) {
				$this->cacheParametersInSession($lp, null);
			}
			$lp = '('.$name.$lp.')';
			$result = $this->parametersSessionCache[$lp];
			if(!is_null($result)) {
				// caches lookup path for future searches
				$this->cacheParameterLookupPath($originalLp, $lp);
				return $result;
			}
		}

		// 7. //
		$lp = '((((()))))';
		// looks up in shared cache if enabled
		if($this->isSharedCacheEnabled() && $this->isSessionCacheEnabled() && !isset($this->parametersSessionCache['('.$lp.')'])) {
			$this->loadParametersFromSharedCache($lp);
			if(isset($this->parametersSessionCache['('.$lp.')'])) $this->getSessionAdminService()->storeData($this, "parametersSessionCache", $this->parametersSessionCache, $this->isSharedCacheEnabled());
		}
		// if not found, then loads from file
		if(!isset($this->parametersSessionCache['('.$lp.')']) &&
			!$this->loadConfig(null, null, null, null, null, null)) {
			$this->cacheParametersInSession($lp, null);
		}
		$lp = '('.$name.$lp.')';
		$result = $this->parametersSessionCache[$lp];
		if(!is_null($result)) {
			// caches lookup path for future searches
			$this->cacheParameterLookupPath($originalLp, $lp);
			return $result;
		}
		
		//$this->debugLogger()->write("session cache: ".json_encode($this->parametersSessionCache));
		$this->debugLogger()->write("parameter not found in session cache");
		return null;
	}
	
	/**
	 * Caches the parameters from the given xml node into the session under the specified lookup path	
	 */
	protected function cacheParametersInSession($lookupPath, $xml) {
		if($this->isSessionCacheEnabled() && $this->isParameterCacheIntoSessionEnabled() && !$this->isReadOnlyParametersFromSession()) {
			// first loads from session
			if(!isset($this->parametersSessionCache)) {
				$this->parametersSessionCache = $this->getSessionAdminService()->getData($this, "parametersSessionCache");
				if(!is_array($this->parametersSessionCache)) $this->parametersSessionCache = array();
			}
			// if empty, then synchronizes from shared cache if enabled
			if($this->isSharedCacheEnabled() && !isset($this->parametersSessionCache['('.$lookupPath.')'])) {
				$this->loadParametersFromSharedCache($lookupPath);
			}			
			
			if(isset($xml)) {
				if(!isset($this->parametersSessionCache['('.$lookupPath.')'])) {
					foreach($xml->attributes() as $k => $v) {
						$this->parametersSessionCache['('.$k.$lookupPath.')'] = (string)$v;
					}
					$this->parametersSessionCache['('.$lookupPath.')'] = true;
					// if shared data enabled, then stores parameters into the db
					if($this->isSharedCacheEnabled()) {
						$this->storeParametersInSharedCache($lookupPath, $xml);
					}
					// updates the session
					$this->getSessionAdminService()->storeData($this, "parametersSessionCache", $this->parametersSessionCache, $this->isSharedCacheEnabled());
					$this->debugLogger()->write("stores the parameters of lp $lookupPath into the session");
				}
			}
			else {
				if(!isset($this->parametersSessionCache['('.$lookupPath.')'])) {					
					$this->parametersSessionCache['('.$lookupPath.')'] = false;
					// if shared data enabled, then stores parameters into the db
					if($this->isSharedCacheEnabled()) {
						$this->storeParametersInSharedCache($lookupPath, $xml);
					}
					// updates the session
					$this->getSessionAdminService()->storeData($this, "parametersSessionCache", $this->parametersSessionCache, $this->isSharedCacheEnabled());
					$this->debugLogger()->write("records into the session the absence of parameters for lp $lookupPath");
				}
			}
		}
	}
	
	// Undefined activities cache management
	
	/**
	 * Returns true if the given activity is marked as undefined into the cache.
	 * Returns false if the given activity is not marked as undefined,
	 * that means that either the activity is defined or that the cache does not know yet its status
	 * @param String $lookupPath lookup path to the activity
	 * @param String $activityName activity name
	 */
	protected function isActivityUndefined($lookupPath, $activityName) {
		if(!isset($this->undefinedActivitiesCache)) {
			if($this->isSessionCacheEnabled()) $this->undefinedActivitiesCache = $this->getSessionAdminService()->getData($this, "undefinedActivities");
			if(!is_array($this->undefinedActivitiesCache)) $this->undefinedActivitiesCache = array();
		}
				
		$this->debugLogger()->write("looks if activity '$activityName' is undefined for lookup path $lookupPath");
		return isset($this->undefinedActivitiesCache['('.$activityName.$lookupPath.')']);
	}
	/**
	 * Marks an activity as beeing undefined for the given lookup path
	 * @param String $lookupPath lookup path to the activity
	 * @param String $activityName activity name
	 */
	protected function markActivityAsUndefined($lookupPath, $activityName) {
		if(!isset($this->undefinedActivitiesCache)) {
			if($this->isSessionCacheEnabled()) $this->undefinedActivitiesCache = $this->getSessionAdminService()->getData($this, "undefinedActivities");
			if(!is_array($this->undefinedActivitiesCache)) $this->undefinedActivitiesCache = array();
		}
		
		$this->undefinedActivitiesCache['('.$activityName.$lookupPath.')'] = true;
		$this->debugLogger()->write("marks activity '$activityName' as undefined for $lookupPath");
		
		if($this->isSessionCacheEnabled() && !$this->isReadOnlyXmlFromSession()) {
			$this->getSessionAdminService()->storeData($this,"undefinedActivities", $this->undefinedActivitiesCache, $this->isSharedCacheEnabled());
		}
	}
	
	// XML loading functions

	protected function loadAll($xmlLookupPath, $fieldsLookupPath, $xmlConfig, $cacheXmlString=true)
	{
		if($cacheXmlString)
		{
			$this->cacheXmlString($xmlLookupPath, $this->writeConfigXmlNodeToString($xmlConfig));
		}
		//Warning, those method are also called in loadAllUnset!!!! don't forget to add them as well overthere
		$this->loadXml($xmlLookupPath, $xmlConfig);
		$this->loadParameters($xmlLookupPath, $xmlConfig);
		if(!$this->loadOnlyParameters) {
			$this->loadActivities($fieldsLookupPath, $xmlConfig);
			$this->loadFields($fieldsLookupPath, $xmlConfig);
			$this->loadTemplate($fieldsLookupPath, $xmlConfig);
		}
	}
	/**
	 * Only loads the ones which are not in cache
	 */
	protected function loadAllUnset($xmlLookupPath, $fieldsLookupPath, $xmlConfig)
	{
		$returnValue = false;
		if(is_null($this->getLoadedXml($xmlLookupPath)))
		{
			$this->loadXml($xmlLookupPath, $xmlConfig);
			if(!$this->loadOnlyParameters) $returnValue = true; // if loading an xml file without any parameters, still records that something is loaded.
			if($this->loadParameters($xmlLookupPath, $xmlConfig)) $returnValue = true;
		}
		if(!$this->loadOnlyParameters) {
			if(is_null($this->getLoadedActivity($fieldsLookupPath))) {
				if($this->loadActivities($fieldsLookupPath, $xmlConfig)) $returnValue = true;
			}
			if(is_null($this->getLoadedField($fieldsLookupPath, true))) {
				if($this->loadFields($fieldsLookupPath, $xmlConfig)) $returnValue = true;
			}
			if(is_null($this->getLoadedTemplate($fieldsLookupPath))) {
				if($this->loadTemplate($fieldsLookupPath, $xmlConfig)) $returnValue = true;
			}
		}
		return $returnValue;
	}
	private function loadDatatype($xmlLookupPath, $xmlConfig, $cacheXmlString=true)
	{
		if($cacheXmlString)
		{
			$this->cacheDataTypeXmlString($xmlLookupPath, $this->writeConfigXmlNodeToString($xmlConfig));
		}
		$this->loadXml($xmlLookupPath, $xmlConfig);
		$this->loadDataTypeInstance($xmlConfig);
	}
	private function loadDataTypeInstance($xmlConfig)
	{
		$x = $xmlConfig->xpath('dbFields');
		$dtName = $xmlConfig->getName();
		if($x)
		{
			$dt = $this->createDataType($dtName);
			$dt->setXml($x[0]);
			$this->dataTypes[$dtName] = $dt;
			if($this->debugLogger()->isEnabled()) $this->debugLogger()->write('stores datatype '.$this->dataTypes[$dtName]->getDataTypeName().' in '.$dtName);
		}
		else
		{
			unset($this->dataTypes[$dtName]);
		}
	}
	/**
	 * Creates a specific instance of a DataType given its name
	 */
	protected function createDataType($dataTypeName)
	{
		if(isset($this->wrapper)) return $this->wrapper->createDataType($dataTypeName);
		
		$returnValue = DataType::createInstance();
		$returnValue->setDataTypeName($dataTypeName);
		return $returnValue;
	}
	private function loadXml($lookupPath, $xmlConfig)
	{
		$this->xml[$lookupPath] = $xmlConfig;
		$this->debugLogger()->write('stores xml config in '.$lookupPath);		
	}
	private function loadParameters($lookupPath, $xmlConfig)
	{
		$x = $xmlConfig->xpath('parameters');
		if($x)
		{
			$this->parameters[$lookupPath] = $x[0];
			$this->debugLogger()->write('stores parameters in '.$lookupPath);
			// caches the parameters into the session
			if($this->isParameterCacheIntoSessionEnabled()) $this->cacheParametersInSession($lookupPath, $x[0]);
			return true;
		}
		else
		{
			unset($this->parameters[$lookupPath]);
			if($this->isParameterCacheIntoSessionEnabled()) $this->cacheParametersInSession($lookupPath, null);
			return false;
		}
	}
	protected function loadActivities($lookupPath, $xmlConfig)
	{
		$activityName = $this->getActivityNameFromLP($lookupPath);
		$x = false;
		if($activityName != null){
			$x = $xmlConfig->xpath($activityName);
		}
		if($x)
		{
			$this->activities[$lookupPath] = $x[0];
			$this->debugLogger()->write('stores activity in '.$lookupPath);
			return true;
		}
		else
		{
			unset($this->activities[$lookupPath]);
			return false;
		}
	}
	protected function loadFields($lookupPath, $xmlConfig)
	{
		$activityName = $this->getActivityNameFromLP($lookupPath);
		if($activityName == null){
			$x = $xmlConfig->xpath('fields');
		} else {
			$x = $xmlConfig->xpath($activityName.'/fields');
		}
		if($x)
		{
			$farray = array(); $singleCount=0;
			$fdistrib=array(); $doubleCount=0;
			foreach($x[0]->children() as $fxml)
			{
				$f = Field::createInstance();
				$f->setXml($fxml);
				$f->setFieldName($fxml->getName());				

				// sets datatype
				$dtName = (string)$fxml["type"];
				$this->debugLogger()->write('sets datatype '.$dtName.' to field '.$f->getFieldName());

				$dt = $this->getDataType($dtName);
				$f->setDataType($dt);
				
				// if datatype exists, then records field distribution to detect doubles or SimpleXMLElement memory mix up
				$count=0;
				if(isset($dt)) {
					$count=$fdistrib[$f->getFieldName()];
					$count++;
					$fdistrib[$f->getFieldName()]=$count;
					// if a double entry exists, start recording a double sequence
					if($count>1) $doubleCount++;
				}
				// does not record any double fields (except for free texts)
				if($count<2) $farray[] = $f;
				$singleCount++;
								
				// sets funcExp is exists
				$funcExp = (string)$fxml["funcExp"];
				if($funcExp != '')
				{
					$funcExp = $this->getFieldSelectorFuncExpParser()->createFuncExpFromString($funcExp);
					$f->setFuncExp($funcExp);
				}							
			}
			// CWE 03.03.2016 signals duplicate field error to monitoring system
			if($doubleCount > 0) {								
				// compares the number of children given by the count method to the actual count of fields
				// if differs then suspects a memory error and compensates it by sending back only the cleaned up array, 
				// else throws a ConfigurationServiceException field already exists.
				$showError=($x[0]->count()==$singleCount);				
				
				$lpContext=$this->splitLp($lookupPath);
				// if showError then throws an exception
				if($showError) {
					if($doubleCount>1) $message="The fields '";
					else $message="The field '";
					$first=true;
					foreach($fdistrib as $fieldName=>$count) {
						if($count>1) {
							if($first) $first=false;
							else $message.= ', ';
							$message.= $fieldName;
						}
					}
					if($doubleCount>1) $message.= "' are defined twice in config for ".$lpContext['moduleName'];
					else $message.= "' is defined twice in config for ".$lpContext['moduleName'];
					if($lpContext['activityName'] || $lpContext['wigiiNamespaceName'] || $lpContext['groupName']) {
						$first=true;
						$message.= ' (';
						if($lpContext['activityName']) {
							$first=false;
							$message.='activity: '.$lpContext['activityName'];
						}
						if($lpContext['wigiiNamespaceName']) {
							if($first) $first=false;
							else $message.= ', ';
							$message.='namespace: '.$lpContext['wigiiNamespaceName'];
						}
						if($lpContext['groupName']) {
							if($first) $first=false;
							else $message.= ', ';
							$message.='group: '.$lpContext['groupName'];
						}
						$message.= ')';
					}
					throw new ConfigServiceException($message, ConfigServiceException::CONFIGURATION_ERROR);
				}
				// else only signals the error to the monitoring system
				else {
					$message="Compensates duplicate fields error in ConfigService. Found $doubleCount duplicated fields against a total of ".count($fdistrib)." fields.";
					if(isset($x[0])) {
						$info=wigiiBPLParam('client',$lpContext['clientName'],'wigiiNamespace',$lpContext['wigiiNamespaceName'],'module',$lpContext['moduleName'],'group',$lpContext['groupName'],'lpField',$lookupPath);
						$this->dumpConfig($x[0], $info);
						$message.=" Dumped XML for $lookupPath is in file ".$info->getValue('xmlFileName');
					}
					else $message.= " No dumped XML for $lookupPath";
					ServiceProvider::getClientAdminService()->signalFatalError(new ConfigServiceException($message,ConfigServiceException::CONFIGURATION_ERROR));
				}
			}
			
			// stores xml node
			$this->fields[$lookupPath][0] = $x[0];
			// stores array of fields
			$this->fields[$lookupPath][1] = $farray;
			$this->debugLogger()->write('stores fields in '.$lookupPath);
			return true;
		}
		else
		{
			unset($this->fields[$lookupPath]);
			return false;
		}
	}
	/**
	 * Adds some new fields to an existing field list, given an xml configuration string or a SimpleXmlElement
	 */
	public function addFieldsToFieldListFromXmlConfig($principal, $fieldList, $xmlConfig, $ignoreDuplicates=false) {
		if(is_null($xmlConfig)) return;
		elseif(is_string($xmlConfig)) {
			$xmlConfig = $this->readConfigXmlNodeFromString(stripslashes($xmlConfig));
			if(!xmlConfig) throw new ConfigServiceException("invalid xml configuration string", ConfigServiceException::INVALID_ARGUMENT);
		}
		elseif(!($xmlConfig instanceof SimpleXMLElement)) throw new ConfigServiceException("xmlConfig should be an xml string or an instance of SimpleXmlElement", ConfigServiceException::INVALID_ARGUMENT);
		foreach($xmlConfig->children() as $fxml)
		{
			$f = Field::createInstance();
			$f->setXml($fxml);
			$f->setFieldName($fxml->getName());

			// sets datatype
			$dtName = (string)$fxml["type"];
			$this->debugLogger()->write('sets datatype '.$dtName.' to field '.$f->getFieldName());

			$dt = $this->getDataType($dtName);
			$f->setDataType($dt);

			// sets funcExp is exists
			$funcExp = (string)$fxml["funcExp"];
			if($funcExp != '')
			{
				$funcExp = $this->getFieldSelectorFuncExpParser()->createFuncExpFromString($funcExp);
				$f->setFuncExp($funcExp);
			}
				
			// adds the field in the field list
			if($ignoreDuplicates) {
				try {$fieldList->addField($f);}
				catch(ListException $le){
					if($le->getCode() != ListException::ALREADY_EXISTS) throw $le;
					else {
						$existingField = $fieldList->getField($f->getFieldName());
						$existingField->setDataType($f->getDataType());
						$existingField->setFuncExp($f->getFuncExp());
						$existingField->setXml($f->getXml());
						$existingField->disableCalculation($f->isCalculationDisabled());
					}
				}
			}
			else $fieldList->addField($f);
		}
	}
	protected function loadTemplate($lookupPath, $xmlConfig)
	{
		$activityName = $this->getActivityNameFromLP($lookupPath);
		if($activityName == null){
			$x = $xmlConfig->xpath('template');
		} else {
			$x = $xmlConfig->xpath($activityName.'/template');
		}
		if($x)
		{
			$this->template[$lookupPath] = $x[0];
			$this->debugLogger()->write('stores Template in '.$lookupPath);
			return true;
		}
		else
		{
			unset($this->template[$lookupPath]);
			return false;
		}
	}
	private function getActivityNameFromLP($lookupPath){
		$rtv = explode("(",$lookupPath);
		$rtv = $rtv[6];
		$rtv = str_replace("))))))","", $rtv);
		return $rtv;
	}
	private function addActivityNameToLP($lookupPath, $activityName) {
		$rtv = explode("(",$lookupPath);
		$rtv[5] = str_replace(")))))", "", $rtv[5]);
		$rtv[6] = $activityName.'))))))';
		return implode('(', $rtv);
	}
	protected function dropActivityNameFromLP($lookupPath) {
		$rtv = explode("(",$lookupPath);
		$rtv = array_slice($rtv, 0, 6);
		return implode('(', $rtv).')))))';
	}
	/**
	 * Splits the lp and fills an associative array
	 * @param String $lp an lp of the form (moduleName(clientName(wigiiNamespaceName(groupName(username(activityName)))))), activityName is optional.
	 * @return Array an array of the form array('moduleName'=>$moduleName, 'clientName'=>$clientName, 'wigiiNamespaceName'=>$wigiiNamespaceName, 'groupName'=>$groupName, 'username'=>$username, 'activityName'=>$activityName) 
	 */
	protected function splitLp($lp) {
		$returnValue = array();
		if(!empty($lp)) {
			$lp = explode("(", $lp);
			foreach($lp as $i => $name) {
				$name = str_replace(')', '', $name);
				switch($i) {
					case 1: $returnValue['moduleName'] = $name; break;
					case 2: $returnValue['clientName'] = $name; break;
					case 3: $returnValue['wigiiNamespaceName'] = $name; break;
					case 4: $returnValue['groupName'] = $name; break;
					case 5: $returnValue['username'] = $name; break;
					case 6: $returnValue['activityName'] = $name; break;
				}
			}
		}
		return $returnValue;
	}
	protected function formatBoolean($var)
	{
		if(isset($this->wrapper)) return $this->wrapper->formatBoolean($var);
		
		if($var == "0" ||
				$var == null ||
				$var === "FALSE" ||
				$var === "NULL" || ///warning, if you don't put === then with a true you will always equal to a string
				$var === false)
					return false;
				else return true;
	}
	
	
	// Shared cache into database

	protected function storeXmlStringInSharedCache($lookupPath, $xmlConfigString) {
		$principal = $this->getAuthenticationService()->getMainPrincipal();
		$this->executionSink()->publishStartOperation("storeXmlStringInSharedCache", $principal);
		try
		{
			$this->getMySqlFacade()->update($principal,
					$this->getSqlForStoreXmlStringInSharedCache($lookupPath, $xmlConfigString),
					$this->getDbAdminService()->getDbConnectionSettings($principal)
			);
		}
		catch(Exception $e)
		{
			$this->executionSink()->publishEndOperationOnError("storeXmlStringInSharedCache", $e, $principal);
			//throw $e;
			return false;
		}
		$this->executionSink()->publishEndOperation("storeXmlStringInSharedCache", $principal);
		return true;
	}
	protected function getSqlForStoreXmlStringInSharedCache($lookupPath, $xmlConfigString) {
		$sqlB = $this->getMySqlFacade()->getSqlBuilder();
		$sqlB->setTableForUpdate('ConfigService_xml', true);
		$sqlB->updateValue('xmlLp', $lookupPath, MySqlQueryBuilder::SQLTYPE_VARCHAR);
		$sqlB->updateValue('xml', $xmlConfigString, MySqlQueryBuilder::SQLTYPE_TEXT);
		$timestamp = time();
		$sqlB->insertValue('sys_creationDate', $timestamp, MySqlQueryBuilder::SQLTYPE_INT);
		$sqlB->insertValue('sys_date', $timestamp, MySqlQueryBuilder::SQLTYPE_INT);
		return $sqlB->getSql();
	}
	
	protected function getXmlStringFromSharedCache($lookupPath) {
		$principal = $this->getAuthenticationService()->getMainPrincipal();
		$this->executionSink()->publishStartOperation("getXmlStringFromSharedCache", $principal);
		$returnValue = null;
		try
		{
			$returnValue = $this->getMySqlFacade()->selectOne($principal,
					$this->getSqlForGetXmlStringFromSharedCache($lookupPath),
					$this->getDbAdminService()->getDbConnectionSettings($principal)
			);
			if(isset($returnValue)) $returnValue = $returnValue['xml'];
		}
		catch(Exception $e)
		{
			$this->executionSink()->publishEndOperationOnError("getXmlStringFromSharedCache", $e, $principal);
			//throw $e;
			return null;
		}
		$this->executionSink()->publishEndOperation("getXmlStringFromSharedCache", $principal);
		return $returnValue;
	}
	protected function getSqlForGetXmlStringFromSharedCache($lookupPath) {
		$sqlB = $this->getMySqlFacade()->getSqlBuilder();
		return "select `xml` from ConfigService_xml where ".$sqlB->formatBinExp('`xmlLp`', '=', $lookupPath, MySqlQueryBuilder::SQLTYPE_VARCHAR);
	}
	
	protected function storeParametersInSharedCache($lookupPath, $xml) {
		$principal = $this->getAuthenticationService()->getMainPrincipal();
		$this->executionSink()->publishStartOperation("storeParametersInSharedCache", $principal);
		try
		{
			$this->getMySqlFacade()->update($principal,
					$this->getSqlForStoreParametersInSharedCache($lookupPath, $xml),
					$this->getDbAdminService()->getDbConnectionSettings($principal)
			);
		}
		catch(Exception $e)
		{
			$this->executionSink()->publishEndOperationOnError("storeParametersInSharedCache", $e, $principal);
			//throw $e;
			return false;
		}
		$this->executionSink()->publishEndOperation("storeParametersInSharedCache", $principal);
		return true;
	}
	protected function getSqlForStoreParametersInSharedCache($lookupPath, $xml) {
		$sqlB = $this->getMySqlFacade()->getSqlBuilder();
		$sqlB->setTableForUpdate('ConfigService_parameters', true);
		$timestamp = time();
		if(isset($xml)) {
			// records the presence of parameters
			$sqlB->updateValue('lp', '('.$lookupPath.')', MySqlQueryBuilder::SQLTYPE_VARCHAR);
			$sqlB->updateValue('xmlLp', $lookupPath, MySqlQueryBuilder::SQLTYPE_VARCHAR);
			$sqlB->updateValue('name', null, MySqlQueryBuilder::SQLTYPE_VARCHAR);
			$sqlB->updateValue('value', '1', MySqlQueryBuilder::SQLTYPE_VARCHAR);
			$sqlB->updateValue('sys_creationDate', $timestamp, MySqlQueryBuilder::SQLTYPE_INT);
			$sqlB->updateValue('sys_date', $timestamp, MySqlQueryBuilder::SQLTYPE_INT);
			// records the parameters values
			foreach($xml->attributes() as $k => $v) {
				$sqlB->insertMoreRecord();
				$sqlB->insertMoreValue('('.$k.$lookupPath.')', MySqlQueryBuilder::SQLTYPE_VARCHAR);
				$sqlB->insertMoreValue($lookupPath, MySqlQueryBuilder::SQLTYPE_VARCHAR);
				$sqlB->insertMoreValue($k, MySqlQueryBuilder::SQLTYPE_VARCHAR);
				$sqlB->insertMoreValue((string)$v, MySqlQueryBuilder::SQLTYPE_VARCHAR);
				$sqlB->insertMoreValue($timestamp, MySqlQueryBuilder::SQLTYPE_INT);
				$sqlB->insertMoreValue($timestamp, MySqlQueryBuilder::SQLTYPE_INT);
			}
		}
		else {
			// records the absence of parameters
			$sqlB->updateValue('lp', '('.$lookupPath.')', MySqlQueryBuilder::SQLTYPE_VARCHAR);
			$sqlB->updateValue('xmlLp', $lookupPath, MySqlQueryBuilder::SQLTYPE_VARCHAR);
			$sqlB->updateValue('name', null, MySqlQueryBuilder::SQLTYPE_VARCHAR);
			$sqlB->updateValue('value', '0', MySqlQueryBuilder::SQLTYPE_VARCHAR);
			$sqlB->updateValue('sys_creationDate', $timestamp, MySqlQueryBuilder::SQLTYPE_INT);
			$sqlB->updateValue('sys_date', $timestamp, MySqlQueryBuilder::SQLTYPE_INT);
		}
		return $sqlB->getSql();
	}
	
	protected function loadParametersFromSharedCache($lookupPath) {
		$principal = $this->getAuthenticationService()->getMainPrincipal();
		$this->executionSink()->publishStartOperation("loadParametersFromSharedCache", $principal);
		$rowListCallback = null;
		$returnValue = false;
		try
		{
			if(!isset($this->parametersSessionCache)) $this->parametersSessionCache = array();
			$rowListCallback = TechnicalServiceProvider::getRowlistCallback('addRowToParametersSessionCache', $this);
			$returnValue = ($this->getMySqlFacade()->selectAll($principal,
					$this->getSqlForLoadParametersFromSharedCache($lookupPath),
					$this->getDbAdminService()->getDbConnectionSettings($principal),
					$rowListCallback
			) > 0);
			$this->debugLogger()->write('loaded '.$rowListCallback->count().' parameters from db for lp '.$lookupPath);
			if(isset($rowListCallback)) $rowListCallback->freeMemory();
		}
		catch(Exception $e)
		{
			$this->executionSink()->publishEndOperationOnError("loadParametersFromSharedCache", $e, $principal);
			if(isset($rowListCallback)) $rowListCallback->freeMemory();
			//throw $e;
		}
		$this->executionSink()->publishEndOperation("loadParametersFromSharedCache", $principal);
		return $returnValue;
	}
	/**
	 * RowList addRow callback for loadParametersFromSharedCache method.
	 */
	public function addRowToParametersSessionCache($rowListCallback, $row) {
		$this->parametersSessionCache[$row['lp']] = $row['value'];
	}
	protected function getSqlForLoadParametersFromSharedCache($lookupPath) {
		$sqlB = $this->getMySqlFacade()->getSqlBuilder();
		return "select `lp`, `value` from ConfigService_parameters where ".$sqlB->formatBinExp('`xmlLp`', '=', $lookupPath, MySqlQueryBuilder::SQLTYPE_VARCHAR);
	}
}


// UNION of fieldlists

class CSFieldListUnion implements FieldList
{
	private $fieldList;

	public static function createInstance()
	{
		$returnValue = new CSFieldListUnion();
		$returnValue->reset();
		return $returnValue;
	}

	public function reset()
	{
		$this->freeMemory();
		$this->fieldList = array();
	}

	protected function freeMemory()
	{
		unset($this->fieldList);
	}

	// FieldList implementation

	public function getListIterator()
	{
		return $this->fieldList;
	}

	public function isEmpty()
	{
		return ($this->count() === 0);
	}

	public function count()
	{
		return count($this->fieldList);
	}

	public function addField($field)
	{
		$this->fieldList[$field->getFieldName()] = $field;
	}

	public function getField($fieldName)
	{
		return $this->fieldList[$fieldName];
	}
	public function doesFieldExist($fieldName)
	{
		return $this->fieldList[$fieldName];
	}

	/**
	 * Flushes the field list into another field list.
	 * This one is emptied.
	 */
	public function flush($fieldList)
	{
		if(!isset($fieldList)) throw new ServiceException("fieldList can not be null", ServiceException::INVALID_ARGUMENT);
		foreach($this->getListIterator() as $f)
		{
			$fieldList->addField($f);
		}
		$this->freeMemory();
	}
}