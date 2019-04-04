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
 * Subclass of ConfigurationContextImpl which supports Sub elements
 * This class works in collaboration with the ConfigServiceSubElementImpl class
 * Created by CWE on February 4th 2014
 */
class ConfigurationContextSubElementImpl extends ConfigurationContextImpl {
	private $_debugLogger;
	private $subElementConfigServices;
	
	// Object lifecycle
	
	public function __construct(){
		parent::__construct();
		$this->debugLogger()->write("creating instance");
	}
	
	public static function createInstance() {
		$returnValue = new self();
		return $returnValue;
	}
	
	// Dependency injection
	
	private function debugLogger() {
		if(!isset($this->_debugLogger)) {
			$this->_debugLogger = DebugLogger::getInstance("ConfigurationContextSubElementImpl");
		}
		return $this->_debugLogger;
	}
	
	// Context
	
	private $currentConfigContextKey; // not persisted into session
	/**
	 * Sets the current configuration key
	 */
	public function setCurrentConfigContextKey($key) {
		$this->currentConfigContextKey = $key;
		$this->debugLogger()->write("sets configuration context key '$key'");
	}
	/**
	 * Returns the current configuration key	 
	 */
	public function getCurrentConfigContextKey() {
		return $this->currentConfigContextKey;
	}
	/**
	 * Returns true if the current configuration key is set
	 */
	public function issetCurrentConfigContextKey() {
		return isset($this->currentConfigContextKey);
	}
	/**
	 * Generates a configuration context key based on the wigiiNamespace, module and groupId	 
	 */
	public function generateConfigContextKey($wigiiNamespace, $module, $groupId) {		
		if(is_object($wigiiNamespace)) {
			$wigiiNamespaceName = $wigiiNamespace->getWigiiNamespaceName();
		}
		elseif(is_string($wigiiNamespace)) $wigiiNamespaceName = $wigiiNamespace;
		else $wigiiNamespaceName = '';
		if(is_object($module)) {
			$moduleName = $module->getModuleName();
		}
		elseif(is_string($module)) $moduleName = $module;
		else $moduleName = '';				
		return "($wigiiNamespaceName($moduleName($groupId)))";
	}
	/**
	 * Generates and sets the config context key using the principal and the current execution service	 
	 * @param ExecutionService $exec
	 */
	public function generateAndSetConfigContextKey($exec) {			
		$groupId = $this->getCrtConfigGroupId($exec->getExecPrincipal(), $exec);
		if($groupId == 0) $groupId = null;
		$this->setCurrentConfigContextKey(
			$this->generateConfigContextKey($exec->getCrtWigiiNamespace(), $exec->getCrtModule(), $groupId));
	}
	
	// Sub element Config Service proxy - Navigation
	
	/**
	 * Selects the configuration of some sub elements given the master element
	 * @param Principal $principal authenticated user performing the operation
	 * @param String|Number|Element $masterElementId the id of the master element (or the master element itself) for which to retrieve sub elements configuration
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
		return $this->getCurrentSubElementConfigService()->selectSubElementsConfig($principal, $masterElementId, $fieldName);
	}
	
	/**
	 * Selects the configuration of a sub element
	 * @param Principal $principal authenticated user performing the operation
	 * @param String|Number $subElementId the id of the sub element for which to select the configuration
	 * @throws ConfigServiceException if an error occurs or if subElementId is not a sub element.
	 * Postconditions: the configuration service is centered on the configuration of this sub element.
	 * -> getCurrentModule returns the module of the sub element
	 * -> getCurrentMasterElementId returns the id of the parent of this sub element
	 * -> getCurrentFieldName returns the field name to which this sub element is linked
	 * -> ConfigService methods m, mf, ma, getFields, getParameter and getTemplatePath return
	 * values using the sub element configuration.
	 */
	public function selectSubElementConfig($principal, $subElementId) {
		return $this->getCurrentSubElementConfigService()->selectSubElementConfig($principal, $subElementId);
	}
	
	/**
	 * Unselects any sub element configuration.
	 * @param Principal $principal authenticated user performing the operation
	 * Postcondition: the ConfigService delegates all calls to the injected ConfigService,
	 * no use of the sub element config cache.
	 */
	public function unselectSubElementConfig($principal) {
		return $this->getCurrentSubElementConfigService()->unselectSubElementConfig($principal);
	}
	
	// Sub element Config Service proxy - Context
		
	/**
	 * Returns the current selected sub element module 
	 * or null if no sub element configuration has been selected
	 * @return Module
	 */
	public function getCurrentModule() {
		return $this->getCurrentSubElementConfigService()->getCurrentModule();
	}
	
	/**
	 * Returns the current selected sub element master id
	 * or null if no sub element configuration has been selected
	 * @return String|Number
	 */
	public function getCurrentMasterElementId() {
		return $this->getCurrentSubElementConfigService()->getCurrentMasterElementId();
	}
	
	/**
	 * Returns the field name under which the current sub element is linked
	 * or null if no sub element configuration has been selected
	 * @return String
	 */
	public function getCurrentFieldName() {
		return $this->getCurrentSubElementConfigService()->getCurrentFieldName();
	}
	
	/**
	 * Returns the field Xml configuration under which the current sub element is linked
	 * or null if no sub element configuration has been selected
	 * @param Principal $principal current principal
	 * @return SimpleXMLElement
	 */
	public function getCurrentFieldXml($principal) {
		return $this->getCurrentSubElementConfigService()->getCurrentFieldXml($principal);
	}
	
	/**
	 * Returns the LinkSelector under which the current sub element is linked
	 * or null if no sub element configuration has been selected
	 * @return LinkSelector
	 */
	public function getCurrentLinkSelector() {
		return $this->getCurrentSubElementConfigService()->getCurrentLinkSelector();
	}
	
	/**
	 * Returns the path from the root element to the current sub element
	 * or null if no sub element configuration has been selected
	 * @return LinkSelectorList a LinkSelectorList instance containing LinkSelector from root to the sub element.
	 * The list does not contain the sub element itself, but stops on its direct parent.
	 */
	public function getCurrentSubElementPathFromRoot() {
		return $this->getCurrentSubElementConfigService()->getCurrentSubElementPathFromRoot();
	}
	
	// ConfigService partial re-implementation
	
	private $bypassSubElementConfig;	
	
	public function getParameter($principal, $module, $name) {
		if($this->bypassSubElementConfig) return parent::getParameter($principal, $module, $name);
		$this->bypassSubElementConfig = true;
		$returnValue = $this->getCurrentSubElementConfigService()->getParameter($principal, $module, $name);
		$this->bypassSubElementConfig = false;
		return $returnValue;
	}	
	public function getFields($principal, $module, $activity, $fieldList) {
		if($this->bypassSubElementConfig) return parent::getFields($principal, $module, $activity, $fieldList);
		$this->bypassSubElementConfig = true;
		$returnValue = $this->getCurrentSubElementConfigService()->getFields($principal, $module, $activity, $fieldList);
		$this->bypassSubElementConfig = false;
		return $returnValue;
	}
	public function getTemplatePath($principal, $module, $activity) {
		if($this->bypassSubElementConfig) return parent::getTemplatePath($principal, $module, $activity);
		$this->bypassSubElementConfig = true;
		$returnValue = $this->getCurrentSubElementConfigService()->getTemplatePath($principal, $module, $activity);
		$this->bypassSubElementConfig = false;
		return $returnValue;
	}
	public function m($principal, $module) {
		if($this->bypassSubElementConfig) return parent::m($principal, $module);
		$this->bypassSubElementConfig = true;
		$returnValue = $this->getCurrentSubElementConfigService()->m($principal, $module);
		$this->bypassSubElementConfig = false;
		return $returnValue;
	}
	public function mf($principal, $module, $activity=null) {
		if($this->bypassSubElementConfig) return parent::mf($principal, $module, $activity);
		$this->bypassSubElementConfig = true;
		$returnValue = $this->getCurrentSubElementConfigService()->mf($principal, $module, $activity);
		$this->bypassSubElementConfig = false;
		return $returnValue;
	}
	public function ma($principal, $module, $activity) {
		if($this->bypassSubElementConfig) return parent::ma($principal, $module, $activity);
		$this->bypassSubElementConfig = true;
		$returnValue = $this->getCurrentSubElementConfigService()->ma($principal, $module, $activity);
		$this->bypassSubElementConfig = false;
		return $returnValue;
	}
	
	// Implementation
	
	protected function getSubElementConfigService($sessionKey) {
		if(is_null($sessionKey)) throw new ConfigServiceException("sessionKey cannot be null", ConfigServiceException::INVALID_ARGUMENT);
		if(!isset($this->subElementConfigServices)) $this->subElementConfigServices = array();
		$returnValue = $this->subElementConfigServices[$sessionKey];
		if(!isset($returnValue)) {
			$returnValue = $this->createSubElementConfigService();
			$returnValue->setConfigService($this);
			$returnValue->setSessionKey($sessionKey);
			$this->subElementConfigServices[$sessionKey] = $returnValue;
		}
		return $returnValue;
	}
	protected function getCurrentSubElementConfigService() {
		$sessionKey = $this->getCurrentConfigContextKey();
		if(!isset($sessionKey)) $sessionKey = "((()))";
		return $this->getSubElementConfigService($sessionKey);
	}
	protected function createSubElementConfigService() {
		return ServiceProvider::getSubElementConfigService();
	}
}